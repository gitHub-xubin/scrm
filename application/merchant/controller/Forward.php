<?php
/**
 * 微博转发
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/12
 * Time: 10:37 AM
 */
namespace app\merchant\controller;

class Forward extends \app\core\MerchantController{

    public function forward(){
        $forwardJson = input('forwardJson');
        $forwardData = json_decode($forwardJson,TRUE);
        $mainAccount = $forwardData['account_id'];
        $mainUrl = $forwardData['url'];
        $mainContent = $forwardData['content'];
        $mainSendTime = $forwardData['reprint_date'];
        $childData = !empty($forwardData['follow_account']) ?$forwardData['follow_account'] :0;
        if(empty($mainAccount) or empty($mainUrl) or empty($mainContent) or empty($mainSendTime)){
                $this -> _response['code'] = 10001;
                $this -> _response['message'] = '缺少参数';
                return $this -> _response;
        }
        $tmpStrPointer = strpos($mainUrl,'?');
        if($tmpStrPointer){
            $tmpStr = substr($mainUrl,0,$tmpStrPointer);
        }else{
            $tmpStr = $mainUrl;
        }
        $codeStr = substr($tmpStr,8);
        $codeArr = explode('/',$codeStr);
        $forwardWeiboModel = new \app\model\forward\Weibo();
        if(intval($codeArr[2]) > 0){
            $mainMid = $codeArr[2];
        }else{
            $mainMid = $forwardWeiboModel -> base62_decode($codeArr[2]);
        }
        //主转发即使发送
        $accountBasicModel = new \app\model\account\Basic();
        $accountInfo = $accountBasicModel -> getAccountAuth($mainAccount);
        $mainAccessToken = $accountInfo['access_token'];
        if(time() >= strtotime($mainSendTime)){
            $mainSendParam = http_build_query([
                'access_token' => $mainAccessToken,
                'id' => $mainMid,
                'status' => $mainContent,
            ]);
            $url = 'https://c.api.weibo.com/2/statuses/repost/biz.json';
            $res =  curl_request($url,'POST',$mainSendParam);
            if(empty($res['error_code'])){
                $followedUrl = 'https://m.weibo.cn/'.$res['user']['id'].$res['mid'];
                $followedMid = $res['mid'];
                $forwardStatus = 3;
                $forwardFailureReason = null;
            }else{
                if($res['error_code'] == 21332){
                    //toekn失效
                    setAccountTokenInvalid($mainAccount);
                }
                $followedUrl = null;
                $followedMid = null;
                $forwardStatus = 4; //转发状态
                $forwardFailureReason = $res['error']; //转发失败原因
            }
            $returnData = addslashes(json_encode($res,JSON_UNESCAPED_UNICODE));
       }else{
            $followedUrl = null;
            $followedMid = null;
            $forwardStatus = 1;
            $forwardFailureReason = null;
            $returnData = null;
        }
        $mainData = [
            'user_id' => $this -> _user_id,
            'account_id' => $mainAccount,
            'source_url' => $mainUrl,
            'source_mid' => $mainMid,
            'followed_url' => $followedUrl,
            'followed_mid' => $followedMid,
            'content' => $mainContent,
            'return_data' => $returnData,
            'pending_time' => $mainSendTime,
            'forward_status' => $forwardStatus,
            'forward_failure_reason' => $forwardFailureReason
        ];
        \think\Db::startTrans();
        try{
            $mainId = $forwardWeiboModel -> addMain($mainData);
            if($forwardStatus == 4){
                $childStatus = 4;
            }else{
                $childStatus = 1;
            }
            //主转发成功，处理跟转
            if(!empty($childData)){
                $level = 1;
                foreach ($childData as $k => $v){
                    if($v['reprint_date'] < $mainSendTime){
                        $this -> _response['code'] = 10001;
                        $this -> _response['message'] = '跟转时间不能再主转发之前';
                        return $this -> _response;
                    }
                    if($k>0){
                        if($childData[$k-1]['pending_time'] > $childData[$k]['pending_time']){
                            $this -> _response['code'] = 10001;
                            $this -> _response['message'] = '跟转时间不能再主转发之前';
                            return $this -> _response;
                        }
                    }
                    $childForwardData = [
                        'weibo_forward_batch_id' => $mainId,
                        'level' => $level,
                        'account_id' => $v['account_id'],
                        'source_url' => $followedUrl,
                        'source_mid' => $followedMid,
                        'followed_url' => null,
                        'followed_mid' => null,
                        'content' => $v['content'],
                        'return_data' => null,
                        'pending_time' => $v['reprint_date'],
                        'forward_status' => $childStatus,
                        'forward_failure_reason' => null,
                    ];
                    $forwardWeiboModel -> addChild($childForwardData);
                    $level +=1;
                }
            }
            $this -> _response['code'] = 0;
            $this -> _response['message'] = 'success';
            \think\Db::commit();
        }catch (\think\Exception $e){
            $this -> _response['code'] = -1;
            $this -> _response['message'] = 'fail';
            var_dump($e);
            \think\Db::rollback();
        }
        return $this -> _response;
    }

}