<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/6/26
 * Time: 10:38 AM
 */
namespace app\merchant\controller;

class ReleaseWeibo extends \app\core\MerchantController{

    //发送纯文本微博
    public function releaseText(){
        $accountId = input('accountId');
        $text = input('text');
        $timing = input('timing');
        $releaseTime = input('releaseTime');
        $rules = [
            ['accountId','require|number|>:0','账号id必须|账号类型错误|账号ID要大于0'],
            ['text','require','内容必须'],
            ['timing','require','发送类型必须']
        ];
        $validate = new \think\Validate($rules);
        if ($validate->check(['accountId'=>$accountId,'text'=>$text,'timing' => $timing]) == false) {
            $this->_response['code'] = 10001;
            $this->_response['message'] = $validate->getError();
            return $this->_response;
        }
        if($timing == 2){
            if(empty($releaseTime)){
                $this->_response['code'] = 10001;
                $this->_response['message'] = '定时转发时，转发时间必填';
                return $this->_response;
            }
            if(time() > strtotime($releaseTime)){
                $this->_response['code'] = 10001;
                $this->_response['message'] = '定时转发时，转发时间必须大于当前时间';
                return $this->_response;
            }
        }
        $accountInfo = $this -> getAccessToken($accountId);
        $weiboModel = new \app\model\release\Weibo();
        if($accountInfo){
            $content = json_encode(['content'=>$text],JSON_UNESCAPED_UNICODE);
            $id= '';
            if($timing == 1){
                //即使发送
                $authInfo = json_decode($accountInfo['auth'],TRUE);
                $access_token = $authInfo['access_token'];
                $param = http_build_query([
                    'access_token' => "$access_token",
                    'status' => $text
                ]);
                $url = "https://c.api.weibo.com/2/statuses/update/biz.json";
                $res = curl_request($url,"POST",$param);
                if(!empty($res['error_code'])){
                    if($res['error_code'] == 21332){
                        //toekn失效
                        setAccountTokenInvalid($accountId);
                    }
                    $status = -1;
                    $mid = '';
                }else{
                    $mid = $res['mid'];
                    $status = 1;
                }
                $insertData = [
                    'mid' => $mid,
                    'account_id' => $accountId,
                    'content' => $content,
                    'timing' => 1,
                    'release_time' => null,
                    'user_id' => $this -> _user_id,
                    'status' => $status,
                    'type' => 1
                ];
                $id = $weiboModel -> add($insertData);
            }elseif($timing == 2){
                //定时发送
                $insertData = [
                    'mid' => '',
                    'account_id' => $accountId,
                    'content' => $content,
                    'timing' => 2,
                    'release_time' => $releaseTime,
                    'user_id' => $this -> _user_id,
                    'status' => 0,
                    'type' => 1
                ];
                $id = $weiboModel -> add($insertData);
            }
            if($id){
                $this -> _response['code'] = 0;
                $this -> _response['message'] = 'ok';
            }else{
                $this -> _response['code'] = -1;
                $this -> _response['message'] = 'fail';
            }
        }else{
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '账号未授权';
        }
        return $this -> _response;
    }

    //发送图文
    public function releaseImgText(){
        $accountId = input('accountId');
        $text = input('text');
        $imgArr = input('imgArr');
        $timing = input('timing');
        $releaseTime = input('releaseTime');
        $rules = [
            ['accountId','require|number|>:0','账号id必须|账号类型错误|账号ID要大于0'],
            ['text','require','内容必须'],
            ['imgArr','require','图片地址必须'],
            ['timing','require','发送类型必须']
        ];
        $validate = new \think\Validate($rules);
        if ($validate->check(['accountId'=>$accountId,'imgArr'=>$imgArr,'text'=>$text,'timing' => $timing]) == false) {
            $this->_response['code'] = 10001;
            $this->_response['message'] = $validate->getError();
            return $this->_response;
        }
        if($timing == 2){
            if(empty($releaseTime)){
                $this->_response['code'] = 10001;
                $this->_response['message'] = '定时转发时，转发时间必填';
                return $this->_response;
            }
            if(time() > strtotime($releaseTime)){
                $this->_response['code'] = 10001;
                $this->_response['message'] = '定时转发时，转发时间必须大于当前时间';
                return $this->_response;
            }
        }
        $accountInfo = $this -> getAccessToken($accountId);
        $weiboModel = new \app\model\release\Weibo();
        if($accountInfo){
            $content = json_encode(['content'=>$text,'img'=>$imgArr],JSON_UNESCAPED_UNICODE);
            $id= '';
            $authInfo = json_decode($accountInfo['auth'],TRUE);
            $access_token = $authInfo['access_token'];
            if($timing == 1){
                //即使发送
                //文件上传至微博
                $picArr = [];
                $imgArr = json_decode($imgArr,TRUE);
                $url = 'https://c.api.weibo.com/2/statuses/upload_pic/biz.json';
                foreach ($imgArr as $v){
                    $fileName = $weiboModel -> download($v,'/home/scrm_api/public/cache_file/');
                    if($fileName){
                        $tmpFile[] = '/home/scrm_api/public/cache_file/'.$fileName;
                        $picArr[] = $weiboModel -> uploadFile($access_token,$url,"/home/scrm_api/public/cache_file/$fileName");
                    }
                }
                //var_dump($picArr);die;
                if(empty($picArr)){
                    $this -> _response['code'] = -1;
                    $this -> _response['message'] = '图片上传失败';
                    return $this -> _response;
                }
                $picId = implode(',',$picArr);
                $param = http_build_query([
                    'access_token' => "$access_token",
                    'status' => $text,
                    'pic_id' => "$picId",
                ]);
                //var_dump($param);die;
                $url = "https://c.api.weibo.com/2/statuses/upload_url_text/biz.json";
                $res = curl_request($url,"POST",$param);
                if(!empty($res['error_code'])){
                    if($res['error_code'] == 21332){
                        //toekn失效
                        setAccountTokenInvalid($accountId);
                    }
                    $status = -1;
                    $mid = '';
                }else{
                    $mid = $res['mid'];
                    $status = 1;
                }
                $insertData = [
                    'mid' => $mid,
                    'account_id' => $accountId,
                    'content' => $content,
                    'timing' => 1,
                    'release_time' => null,
                    'user_id' => $this -> _user_id,
                    'status' => $status,
                    'type' => 2
                ];
                $id = $weiboModel -> add($insertData);
                if(!empty($tmpFile)){
                    foreach ($tmpFile as $v){
                        unlink($v);
                    }
                }
            }elseif($timing == 2){
                //定时发送
                $insertData = [
                    'mid' => '',
                    'account_id' => $accountId,
                    'content' => $content,
                    'timing' => 2,
                    'release_time' => $releaseTime,
                    'user_id' => $this -> _user_id,
                    'status' => 0,
                    'type' => 2
                ];
                $id = $weiboModel -> add($insertData);
            }
            if($id){
                $this -> _response['code'] = 0;
                $this -> _response['message'] = 'ok';
            }else{
                $this -> _response['code'] = -1;
                $this -> _response['message'] = 'fail';
            }
        }else{
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '账号未授权';
        }
        return $this -> _response;
    }

    //获取授权信息
    private function getAccessToken($accountId){
        $accountBasicModel = new \app\model\account\Basic();
        $value = ['account_id'=>$accountId];
        $column = 'auth';
        $accountInfo = $accountBasicModel -> getAccount($value,$column);
        return $accountInfo;
    }

    //检查视频连接
    public function checkVideoUrl(){
        $videoUrl = input('videoUrl');
        if(empty($videoUrl)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $model = new \app\model\release\Weibo();
        $accountId = $model ->getStoreAccessToken($this -> store_id);
        $accountInfo = $this -> getAccessToken($accountId['account_id']);
        $authInfo = json_decode($accountInfo['auth'],TRUE);
        $access_token = $authInfo['access_token'];
        $param = http_build_query([
            'access_token' => $access_token,
            'url_long' => $videoUrl
        ]);
        $url = "https://c.api.weibo.com/2/short_url/shorten/biz.json";
        $res = curl_request($url,"POST",$param);
        if(empty($res['urls'][0]['object_id'])){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '非视频连接';
        }else{
            $this -> _response['code'] = 0;
            $this -> _response['data'] = $res['urls'][0]['url_short'];
        }
        return $this -> _response;
    }

    //发布视频
    public function releaseVideo(){
        $accountId = input('accountId');
        $text = input('text');
        $timing = input('timing');
        $releaseTime = input('releaseTime');
        $rules = [
            ['accountId','require|number|>:0','账号id必须|账号类型错误|账号ID要大于0'],
            ['text','require','内容必须'],
            ['timing','require','发送类型必须']
        ];
        $validate = new \think\Validate($rules);
        if ($validate->check(['accountId'=>$accountId,'text'=>$text,'timing' => $timing]) == false) {
            $this->_response['code'] = 10001;
            $this->_response['message'] = $validate->getError();
            return $this->_response;
        }
        $weiboModel = new \app\model\release\Weibo();
        if($timing == 2){
            if(empty($releaseTime)){
                $this->_response['code'] = 10001;
                $this->_response['message'] = '定时转发时，转发时间必填';
                return $this->_response;
            }
            if(time() > strtotime($releaseTime)){
                $this->_response['code'] = 10001;
                $this->_response['message'] = '定时转发时，转发时间必须大于当前时间';
                return $this->_response;
            }
        }
        $accountInfo = $this -> getAccessToken($accountId);
        if($accountInfo){
            $content = json_encode(['content'=>$text],JSON_UNESCAPED_UNICODE);
            $id= '';
            $authInfo = json_decode($accountInfo['auth'],TRUE);
            $access_token = $authInfo['access_token'];
            if($timing == 1){
                //即使发送
                $param = http_build_query([
                    'access_token' => "$access_token",
                    'status' => $text
                ]);
                $url = "https://c.api.weibo.com/2/statuses/update/biz.json";
                $res = curl_request($url,"POST",$param);
                //var_dump($res);die;
                if(!empty($res['error_code'])){
                    if($res['error_code'] == 21332){
                        //toekn失效
                        setAccountTokenInvalid($accountId);
                    }
                    $status = -1;
                    $mid = '';
                }else{
                    $mid = $res['mid'];
                    $status = 1;
                }
                $insertData = [
                    'mid' => $mid,
                    'account_id' => $accountId,
                    'content' => $content,
                    'timing' => 1,
                    'release_time' => null,
                    'user_id' => $this -> _user_id,
                    'status' => $status,
                    'type' => 3
                ];
                $id = $weiboModel -> add($insertData);
            }elseif($timing == 2){
                //定时发送
                $insertData = [
                    'mid' => '',
                    'account_id' => $accountId,
                    'content' => $content,
                    'timing' => 2,
                    'release_time' => $releaseTime,
                    'user_id' => $this -> _user_id,
                    'status' => 0,
                    'type' => 3
                ];
                $id = $weiboModel -> add($insertData);
            }
            if($id){
                $this -> _response['code'] = 0;
                $this -> _response['message'] = 'ok';
            }else{
                $this -> _response['code'] = -1;
                $this -> _response['message'] = 'fail';
            }
        }else{
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '账号未授权';
        }
        return $this -> _response;
    }
    //微博列表
    public function getList(){
        $page = input('page/d',0);
        $page = max($page - 1,0);
        $pageSize = input('pageSize/d',10);
        $keywords = input('keywords');
        $model = new \app\model\release\Weibo();
        $res = $model -> getList($this -> store_id,$page,$pageSize,$keywords);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $res;
        return $this -> _response;
    }

    //获取最新发布的微博
    public function getNewWb(){
        $model = new \app\model\release\Weibo();
        $res = $model -> getNewWb($this -> _user_id);
        $data['content'] = json_decode($res['content'],TRUE);
        $data['type'] = $res['type'];
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $data;
        return $this -> _response;
    }
}