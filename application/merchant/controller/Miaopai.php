<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/9
 * Time: 10:44 AM
 */
namespace app\merchant\controller;

class Miaopai extends \app\core\MerchantController{

    public function uploadVideo(){
        $videoId = input('videoId');
        $imgUrl = input('imgUrl');
        $category = input('category');
        $title = input('title');
        $description = input('description');
        $accountId = input('accountId');
        $time = date('Y-m-d H:i:s');
        $materialBasicModel = new \app\model\material\Basic();
        $value = ['id' => $videoId,'store_id' => $this -> store_id];
        $col = 'url';
        $materialInfo = $materialBasicModel -> getMaterial($value,$col);
        if(!empty($materialInfo)){
            $videoDomain = config('qiniu_conf')['scrm-video']['domain'];
            $videoUrl = $videoDomain.$materialInfo['url'];
        }else{
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '视频id错误';
            return $this -> _response;
        }
        $accountBasicModel = new \app\model\account\Basic();
        $value = ['account_id'=>$accountId,'account_platform_id' => 2,'store_id' => $this ->store_id];
        $column = 'auth';
        $accountInfo = $accountBasicModel -> getAccount($value,$column);
        if(empty($accountInfo)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '账号信息不存在哦';
            return $this -> _response;
        }
        $accountCookie = json_decode($accountInfo['auth'],TRUE)['cookie'];
        //秒拍上传初始化
        $initParam = [
            'cookie' => $accountCookie,
            'video_url' => $videoUrl
        ];
        $url = 'http://scrm_public_api.shifuhui.net/mp_upload_init';
        $header[] = "Content-Type:application/json";
        $res = curl_request($url,'POST',$initParam,$header);
        if($res['code'] != 200){
            if($res['code'] == 10001){
                $this -> _response['code'] = -4;
                $this -> _response['message'] = '每天最多上传5条视频';
                return $this -> _response;
            }
            if($res['code'] == 10003){
                $this -> _response['code'] = -5;
                $this -> _response['message'] = '秒拍系统错误，请稍后再试';
                return $this -> _response;
            }
            if($res['code'] == 10004){
                setAccountTokenInvalid($accountId);
                $this -> _response['code'] = -2;
                $this -> _response['message'] = '秒拍授权失效';
                return $this -> _response;
            }
            file_put_contents('/home/scrm_api/runtime/mpfail.log',$time."-----秒拍上传初始化-----".json_encode($initParam)."-------\n".$time."-----秒拍上传初始化返回信息------".json_encode($res,JSON_UNESCAPED_UNICODE)."--------\n",FILE_APPEND);
            $this -> _response['code'] = -3;
            $this -> _response['message'] = '上传失败，请重新上传-1';
            return $this -> _response;
        }
        $imgKey = $res['data'][0]['mp_keys']['data'][0]['image_base64key'];
        $scid = $res['data'][0]['mp_keys']['data'][0]['scid'];
        $imgToken =  $res['data'][0]['mp_keys']['data'][0]['image_token'];
        //校验秒拍上传视频信息
        $url = "http://scrm_public_api.shifuhui.net/check_video_status";
        $checkParam = [
            'cookie' => $accountCookie,
            'scid' => $scid
        ];
        $res = curl_request($url,"POST",$checkParam,$header);
        if($res['code'] != 200){
            file_put_contents('/home/scrm_api/runtime/mpfail.log',$time."-----校验秒拍上传数据-----".json_encode($checkParam)."-------\n".$time."-----校验秒拍上传数据返回信息------".json_encode($res,JSON_UNESCAPED_UNICODE)."--------\n",FILE_APPEND);
            if($res['code'] == 10005){
                $this -> _response['code'] = -6;
                $this -> _response['message'] = '您无法重复发布同一视频';
                return $this -> _response;
            }
            $this -> _response['code'] = -3;
            $this -> _response['message'] = '上传失败，请重新上传-2';
            return $this -> _response;
        }
        $h = $res['data'][0]['video_info']['data']['ext']['h'];
        $w = $res['data'][0]['video_info']['data']['ext']['w'];
        //视频发布
        $url = "http://scrm_public_api.shifuhui.net/publish_video";
        $uploadParam = [
            'cookie' => $accountCookie,
            'key' => $imgKey,
            'token' => $imgToken,
            'w' => $w,
            'h' => $h,
            'title' => $title,
            'ftitle' => $description,
            'category' => $category,
            'cover_url' => $imgUrl,
            'scid' => $scid
        ];
        $res = curl_request($url,"POST",$uploadParam,$header);
        if($res['code'] != 200){
            file_put_contents('/home/scrm_api/runtime/mpfail.log',$time."-----视频发布-----".json_encode($uploadParam)."-------\n".$time."-----视频发布返回信息------".json_encode($res,JSON_UNESCAPED_UNICODE)."--------\n",FILE_APPEND);
            $this -> _response['code'] = 3;
            $this -> _response['message'] = '上传失败，请重新上传-3';
            return $this -> _response;
        }
        //获取秒拍视频链接
        $data['scid'] = $scid;
        $data['mp_account_id'] = $accountId;
        $materialBasicModel -> edit($videoId,$data);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = 'success';
        return $this -> _response;
    }

    //单独上传秒拍封面
    /*public function uploadCover(){
        $image = request()->file('image');
        if(empty($image)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $qiniu = new \qiniu();
        $reslut = $qiniu->upload_image($image,'scrm-image');
        if($reslut['code']!='0'){
            $this -> _response['code'] = $reslut['code'];
            $this -> _response['message'] = $reslut['message'];
            return $this -> _response;
        }
        $url = $reslut['data'];
        $qiniuConf = config('qiniu_conf')['scrm-image']['domain'];
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $qiniuConf.$url;
        return $this -> _response;
    }*/
}