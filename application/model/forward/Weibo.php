<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/12
 * Time: 4:27 PM
 */
namespace app\model\forward;

class Weibo extends \app\core\ApiModel{

    public function base62_decode($mid = false) {
        $alphabei = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //组织需要解码的mid
        $_mid_arr = str_split(strrev($mid), 4);
        foreach ($_mid_arr as &$val) {
            $val = strrev($val);
        }
        $mid_arr = array_reverse($_mid_arr);
        $base = strlen($alphabei);

        $_arr = [];
        //得到每部分对应的码
        foreach ($mid_arr as &$val) {
            $idx = 0;
            $num = 0;
            $midlen = strlen($val);
            $val = str_split($val);
            foreach ($val as &$v) {
                $power = $midlen - ($idx + 1);
                $num += strpos($alphabei, $v) * pow($base, $power);
                $idx += 1;
            }
            $_arr[] = (int) $num;
        }
        $_num = '';
        //补齐差位
        foreach ($_arr as $_k => $_v) {
            if ($_k != 0) {
                $_x = '';
                $_len = strlen($_v);
                $_c = 7 - $_len;
                if ($_c != 0) {
                    for ($_a = 0; $_a < $_c; $_a++) {
                        $_x .= '0';
                    }
                    $_v = $_x . $_v;
                }
            }
            $_num .= $_v;
        }
        return $_num;
    }

    /**
     * 添加主转发表
     * @param $data
     */
    public function addMain($data){
        $date = date('Y-m-d H:i:s');
        $sql = 'insert into `weibo_forward_batch` (user_id,account_id,source_url,source_mid,followed_url,followed_mid,content,return_data,pending_time,forward_status,forward_failure_reason,date_added)
                values (?,?,?,?,?,?,?,?,?,?,?,?)';
        \think\Db::execute($sql,[$data['user_id'],$data['account_id'],$data['source_url'],$data['source_mid'],$data['followed_url'],$data['followed_mid'],
                                $data['content'],$data['return_data'],$data['pending_time'],$data['forward_status'],$data['forward_failure_reason'],$date]);
        return \think\Db::getLastInsID();
    }

    //添加跟转
    public function addChild($data){
        $date = date('Y-m-d H:i:s');
        $sql = 'insert into `weibo_forward_batch_detail` (weibo_forward_batch_id,level,account_id,source_url,source_mid,followed_url,followed_mid,content,return_data,pending_time,forward_status,forward_failure_reason,date_added)
                values (?,?,?,?,?,?,?,?,?,?,?,?,?)';
        \think\Db::execute($sql,[$data['weibo_forward_batch_id'],$data['level'],$data['account_id'],$data['source_url'],$data['source_mid'],$data['followed_url'],$data['followed_mid'],
            $data['content'],$data['return_data'],$data['pending_time'],$data['forward_status'],$data['forward_failure_reason'],$date]);
        return \think\Db::getLastInsID();
    }

    //修改微博主转发表
    public function editMainForward($id,$data){
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "`{$key}`='{$value}'";
        }
        $set = implode(',', $set);
        $sql = "UPDATE `weibo_forward_batch` SET {$set} WHERE `weibo_forward_batch_id`=?";
        \think\Db::execute($sql, [$id]);
        return true;
    }

    //修改跟转表
    public function editChildForward($id,$data){
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "`{$key}`='{$value}'";
        }
        $set = implode(',', $set);
        $sql = "UPDATE `weibo_forward_batch_detail` SET {$set} WHERE `weibo_forward_batch_detail_id`=?";
        \think\Db::execute($sql, [$id]);
        return true;
    }

    //处理转发
    public function forwardHandle(){
        //主转发
        $sql = "select  weibo_forward_batch_id, account_id,source_url,source_mid,content from `weibo_forward_batch` where forward_status = 1 and DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s') > pending_time";
        $res = $this -> findAll($sql);
        $accountBasicModel = new \app\model\account\Basic();
        foreach ($res as $k => $v){
            //防止主转发异常重复转发，先设置为转发中
            $tmp['forward_status'] = 2;
            $this -> editMainForward($tmp,$v['weibo_forward_batch_id']);
            $accountInfo = $accountBasicModel -> getAccountAuth($v['account_id']);
            $mainAccessToken = $accountInfo['access_token'];
            $mainSendParam = http_build_query([
                'access_token' => $mainAccessToken,
                'id' => $v['source_mid'],
                'status' => $v['content'],
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
                    setAccountTokenInvalid($v['account_id']);
                }
                $followedUrl = null;
                $followedMid = null;
                $forwardStatus = 4; //转发状态
                $forwardFailureReason = $res['error']; //转发失败原因
            }
            $returnData = addslashes(json_encode($res,JSON_UNESCAPED_UNICODE));
            $mainEditData = [
                'followed_url' => $followedUrl,
                'followed_mid' => $followedMid,
                'forward_status' => $forwardStatus,
                'forward_failure_reason' => $forwardFailureReason,
                'return_data' => $returnData
            ];
            $this -> editMainForward($v['weibo_forward_batch_id'],$mainEditData);
        }
        //处理跟转
        $sql = "select wfbd.weibo_forward_batch_detail_id,wfbd.account_id,wfbd.content,wfb.followed_url,wfb.followed_mid from `weibo_forward_batch_detail` as wfbd left join 
                weibo_forward_batch as wfb on wfbd.weibo_forward_batch_id = wfb.weibo_forward_batch_id
                where wfbd.forward_status = 1 and DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s') > wfbd.pending_time";
        $child = $this -> findAll($sql);
        if(!empty($child)){
            foreach ($child as $key => $val){
                //防止主转发异常重复转发，先设置为转发中
                $tmp['forward_status'] = 2;
                $this -> editChildForward($val['weibo_forward_batch_detail_id'],$tmp);
                $childAccountInfo = $accountBasicModel -> getAccountAuth($val['account_id']);
                $childAccessToken = $childAccountInfo['access_token'];
                $childendParam = http_build_query([
                    'access_token' => $childAccessToken,
                    'id' => $val['followed_mid'],
                    'status' => $val['content'],
                ]);
                $url = 'https://c.api.weibo.com/2/statuses/repost/biz.json';
                $res =  curl_request($url,'POST',$childendParam);
                if(empty($res['error_code'])){
                    $followedUrl = 'https://m.weibo.cn/'.$res['user']['id'].$res['mid'];
                    $followedMid = $res['mid'];
                    $forwardStatus = 3;
                    $forwardFailureReason = null;
                }else{
                    if($res['error_code'] == 21332){
                        //toekn失效
                        setAccountTokenInvalid($val['account_id']);
                    }
                    $followedUrl = null;
                    $followedMid = null;
                    $forwardStatus = 4; //转发状态
                    $forwardFailureReason = $res['error']; //转发失败原因
                }
                $returnData = addslashes(json_encode($res,JSON_UNESCAPED_UNICODE));
                $childEditData = [
                    'source_url' => $val['followed_url'],
                    'source_mid' => $val['followed_mid'],
                    'followed_url' => $followedUrl,
                    'followed_mid' => $followedMid,
                    'forward_status' => $forwardStatus,
                    'forward_failure_reason' => $forwardFailureReason,
                    'return_data' => $returnData
                ];
                $this -> editChildForward($val['weibo_forward_batch_detail_id'],$childEditData);
            }
        }
    }
}