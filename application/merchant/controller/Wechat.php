<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/16
 * Time: 11:25 AM
 */
namespace app\merchant\controller;
use think\Request;
require_once EXTEND_PATH.'wxDecrypt/wxBizMsgCrypt.php';

class Wechat extends \app\core\MerchantController{

    public function applyWechatAuth(){
        $wxopenConf = config('wechatOpen');
        $ticket = file_get_contents('/home/scrm_api/component_verify_ticket.txt');
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_component_token";
        $getAccessTokenParam = [
            'component_appid' => $wxopenConf['appid'],
            'component_appsecret' => $wxopenConf['appsecret'],
            'component_verify_ticket' => $ticket,
        ];
        $rs =  curl_request($url,"POST",$getAccessTokenParam);
        if(!empty($rs['component_access_token'])){
            file_put_contents('/home/scrm_api/component_access_token.json',json_encode($rs));
            $pre_auth_code_param = [
                'component_appid' => $wxopenConf['appid'],
            ];
            $pre_auth_code_url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.$rs['component_access_token'];
            $pre_auth_code_data = curl_request($pre_auth_code_url,'POST',$pre_auth_code_param);
            if(!empty($pre_auth_code_data['pre_auth_code'])){
                $appid = $wxopenConf['appid'];
                $_code = urldecode($pre_auth_code_data['pre_auth_code']);
                //$redirect_uri = urlencode("http://scrm-web.shifuhui.net/manage/account");
                $redirect_uri = urlencode("http://scrm-web.shifuhui.net/api/index.php?s=merchant/wechat/addwechataccount");
                //$authorizer_access_token_url = "https://mp.weixin.qq.com/safe/bindcomponent?action=bindcomponent&auth_type=3&no_scan=1&component_appid=$appid&pre_auth_code=$_code&redirect_uri=$redirect_uri&auth_type=1&biz_appid=xxxx#wechat_redirect";
                $authorizer_access_token_url = "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=$appid&pre_auth_code=$_code&redirect_uri=$redirect_uri&auth_type=1";
                $this -> _response['code'] = 0;
                $this -> _response['data'] = $authorizer_access_token_url;
              //  header("location:$authorizer_access_token_url");
            }else{
                $this -> _response['code'] = -2;
                $this -> _response['message'] = '授权失败';
            }
        }else{
            $this -> _response['code'] = -2;
            $this -> _response['message'] = '授权失败';
        }
        return $this -> _response;
    }

    //微信开放平台授权回调
    public function authCallback(){
        $request = Request::instance();
        $data = $request->param();
        $timeStamp    = $data['timestamp'];
        $nonce        = $data['nonce'];
        $msg_sign     = $data['msg_signature'];
        $encryptMsg = file_get_contents('php://input');
        file_put_contents('/home/scrm_api/authXml.json',json_encode($data));
        $wxopenConf = config('wechatOpen');
        $WXBizMsgCrypt = new \WXBizMsgCrypt($wxopenConf['token'],$wxopenConf['salt'],$wxopenConf['appid']);
        $xml_tree = new \DOMDocument();
        $xml_tree->loadXML($encryptMsg);
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        $encrypt = $array_e->item(0)->nodeValue;

        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt);
        $msg = '';
        $errCode = $WXBizMsgCrypt->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
        if ($errCode == 0){
            $xml = new \DOMDocument();
            $xml->loadXML($msg);
            $array_type = $xml->getElementsByTagName('InfoType');
            $InfoType = $array_type->item(0)->nodeValue;
            switch ( $InfoType )
            {
                case 'component_verify_ticket': //请求ticket
                    $array_e = $xml->getElementsByTagName('ComponentVerifyTicket');
                    $component_verify_ticket = $array_e->item(0)->nodeValue;
                    //保存
                    //Cache::put('ticket',$component_verify_ticket,11);
                    file_put_contents('/home/scrm_api/component_verify_ticket.txt',$component_verify_ticket);
                    break;
                case 'unauthorized':   //取消授权
                    $array_appid = $xml->getElementsByTagName('AuthorizerAppid');
                    $authorizer_appid = $array_appid->item(0)->nodeValue;
                    //取消授权的业务
                    break;
                case 'updateauthorized'://更新授权
                    $array_code = $xml->getElementsByTagName('AuthorizationCode');
                    $code = $array_code->item(0)->nodeValue;
                    file_put_contents('/home/scrm_api/AuthorizationCode.txt',$code);
                    //授权code 的业务
                    break;
                default:
                    echo "false"; die();
                    break;
            }
            echo 'success';
        } else{
            echo "false";
        }
    }

    //第三方公众号授权回调
    public function storeWechatCallback(){
        $appid = input('appid');
        echo $appid;
    }

    //微信授权 authorizer_access_token
    public function addWechatAccount(){
        $wxopenConf = config('wechatOpen');
        $auth_code = input('auth_code');
        $component_access_token_json = file_get_contents('/home/scrm_api/component_access_token.json');
        $component_access_token = json_decode($component_access_token_json,TRUE);
        $data = [
            'component_appid' => $wxopenConf['appid'],
            'authorization_code' => $auth_code,
        ];
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='.$component_access_token['component_access_token'];
        $res = curl_request($url,'POST',$data);
        if(!empty($res['authorization_info'])){
            $authorizer_appid = $res['authorization_info']['authorizer_appid'];                 //授权方appid
            $authorizer_access_token = $res['authorization_info']['authorizer_access_token'];   //授权方接口令牌
            $expires_in = $res['authorization_info']['expires_in'];                             //有效期
            $authorizer_refresh_token = $res['authorization_info']['authorizer_refresh_token']; //凭据刷新令牌
            header('location:http://scrm-web.shifuhui.net/manage/account?authorizer_appid='.$authorizer_appid.'&authorizer_access_token='.$authorizer_access_token.'&authorizer_refresh_token='.$authorizer_refresh_token.'&expires_in='.$expires_in);
        }
    }

    //添加微信账号
    public function addWechat(){
        $authorizerAppid = input('authorizerAppid');
        $authorizerRefreshToken = urldecode(input('authorizerRefreshToken'));
        $authorizerAccessToken = urldecode(input('authorizerAccessToken'));
        $expiresIn = input('expiresIn');
        if(empty($authorizerAppid) or empty($authorizerRefreshToken) or empty($authorizerAccessToken)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $accountBasicModel = new \app\model\account\Basic();
        $value = ['uid' =>  $authorizerAppid, 'store_id' => $this -> store_id];
        $column = 'account_id';
        $info = $accountBasicModel -> getAccount($value,$column);
        $wxopenConf = config('wechatOpen');
        $component_access_token_json = file_get_contents('/home/scrm_api/component_access_token.json');
        $component_access_token = json_decode($component_access_token_json,TRUE);
        $getInfoParam = [
            'component_appid' => $wxopenConf['appid'],
            'authorizer_appid' => $authorizerAppid,
        ];
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token='.$component_access_token['component_access_token'];
        $accountInfo = curl_request($url,'POST',$getInfoParam);
        $auth = json_encode(
            [
                'authorizer_appid' => $authorizerAppid,
                'authorizer_access_token' => $authorizerAccessToken,
                'authorizer_refresh_token' => $authorizerRefreshToken,
            ]
        );
        $expired_time = date('Y-m-d H:i:s',time() + $expiresIn);
        if(empty($info)){
            //新增微信账号
            if(!empty($accountInfo['authorizer_info'])){
                $accountdata = [
                    'uid' => $authorizerAppid,
                    'a_name' => $accountInfo['authorizer_info']['nick_name'],
                    'gender' => null,
                    'header_url' => $accountInfo['authorizer_info']['head_img'],
                    'account_platform_id' => 3,
                    'auth' => $auth,
                    'auth_status' => 1,
                    'store_id' => $this -> store_id,
                    'expired_time' => $expired_time
                ];
                $accountId = $accountBasicModel -> addAccount($accountdata);
            }
        }else{
            //更新账号
            $accountId = $info['account_id'];
            if(!empty($accountInfo['authorizer_info'])) {
                $accountdata = [
                    'a_name' => $accountInfo['authorizer_info']['nick_name'],
                    'gender' => null,
                    'header_url' => $accountInfo['authorizer_info']['head_img'],
                    'auth' => $auth,
                    'auth_status' => 1,
                    'expired_time' => $expired_time,
                    'date_modified' => date('Y-m-d H:i:s'),
                    'status' => 1
                ];
                $accountBasicModel->edit($accountId, $accountdata);
            }
        }
        $info = [
            'accountId' => $accountId,
            'name' => $accountInfo['authorizer_info']['nick_name'],
            'header_url' => $accountInfo['authorizer_info']['head_img']
        ];
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $info;
        return $this -> _response;
    }

    //上传微信图文素材中图片
    public function uploadImg(){
        $accountId = input('accountId');
        $img = input('imgId');
        if(empty($accountId) or empty($img)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $materialBasicModel = new \app\model\material\Basic();
        $accountBasicModel = new \app\model\account\Basic();
        $accountAuthInfo = $accountBasicModel -> getAccountAuth($accountId);
        $filePath = $this -> getResource($img,1);
        $fileSize = filesize($filePath);
        if($fileSize > 1048576){
            unlink($filePath);
            $this -> _response['code'] = 10010;
            $this -> _response['message'] = '文件过大';
            return $this -> _response;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$accountAuthInfo['authorizer_access_token'];
        $data = array('media' => new \CURLFile($filePath));
        $resJson = doPost($url,$data);
        $resData = json_decode($resJson,TRUE);
        if(empty($resData['errcode'])){
            $materialData['wechat_url'] = $resData['url'];
            $materialBasicModel -> edit($img,$materialData);
        }
        unlink($filePath);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = 'ok';
        return $this -> _response;
    }

    //上传永久素材
    public function uploadPermanentMaterial(){
        $accountId = input('accountId');
        $fileId = input('fileId');
        $type = input('type');
        $title = input('title');
        $introduction = input('introduction');
        if(empty($accountId) or empty($fileId) or empty($type)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        if(!in_array($type,array(1,2))){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '参数错误';
            return $this -> _response;
        }
        $materialBasicModel = new \app\model\material\Basic();
        $accountBasicModel = new \app\model\account\Basic();
        $accountAuthInfo = $accountBasicModel -> getAccountAuth($accountId);
        $filePath = $this -> getResource($fileId,$type);
        $fileSize = filesize($filePath);
        $ext = pathinfo($filePath,PATHINFO_EXTENSION);
        if($type == 1){
            $fileType = "image";
            if($fileSize > 2097152){
                $this -> _response['code'] = 10010;
                $this -> _response['message'] = '文件过大';
                return $this -> _response;
            }
            if(!in_array($ext,array('bmp','png','jpeg','jpg','gif'))){
                $this -> _response['code'] = 10011;
                $this -> _response['message'] = '文件类型，微信不支持';
                return $this -> _response;
            }
        }elseif ($type == 2){
            if(empty($title) or empty($introduction)){
                $this -> _response['code'] = 10001;
                $this -> _response['message'] = '缺少参数';
                return $this -> _response;
            }
            $fileType = "video";
            if($fileSize > 10485760){
                $this -> _response['code'] = 10010;
                $this -> _response['message'] = '文件过大';
                return $this -> _response;
            }
            if($ext != 'mp4'){
                $this -> _response['code'] = 10011;
                $this -> _response['message'] = '文件类型，微信不支持';
                return $this -> _response;
            }
        }else{
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '类型错误';
            return $this -> _response;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=".$accountAuthInfo['authorizer_access_token']."&type=".$fileType;
        if($type == 1){
            $data = ['media' => new \CURLFile(realpath($filePath))];
        }elseif ($type == 2){
            $fileJson = json_encode(
                [
                    'title' => $title,
                    'introduction' => $introduction
                ]
            );
            $data = [
                        'media' =>  new \CURLFile(realpath($filePath)),
                        'description' => $fileJson
                    ];
        }
        $resJson = doPost($url,$data);
        $resData = json_decode($resJson,TRUE);
        unlink($filePath);
        if(empty($resData['errcode'])){
            $materialData['media_id'] = $resData['media_id'];
            if($type == 1){
                $materialData['wechat_permanent_url'] = $resData['url'];
            }
            $materialBasicModel -> edit($fileId,$materialData);
            $this -> _response['code'] = 0;
            $this -> _response['message'] = 'ok';
        }else{
            $this -> _response['code'] = -1;
            $this -> _response['message'] = 'fail';
        }
        return $this -> _response;
    }

    //下载七牛资源至服务器
    private function getResource($resourceId,$type){
        $materialBasicModel = new \app\model\material\Basic();
        $weiboModel = new \app\model\release\Weibo();
        $materialValue = ['id' => $resourceId];
        $materialColnum = 'url';
        $imgContent = $materialBasicModel -> getMaterial($materialValue,$materialColnum);
        if($type == 1){
            $qiniuConf = config('qiniu_conf')['scrm-image']['domain'];
        }else{
            $qiniuConf = config('qiniu_conf')['scrm-video']['domain'];
        }
        $imgContent = $qiniuConf.$imgContent['url'];
        $fileName = $weiboModel -> download($imgContent,'/home/scrm_api/public/cache_file/');
        $filePath = '/home/scrm_api/public/cache_file/'.$fileName;
        return $filePath;
    }

    //上传微信图文素材
    public function uploadImgText(){
        $title = input('title');

    }
}