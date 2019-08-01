<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/5/20
 * Time: 4:40 PM
 */
namespace app\model\account;

class Basic extends \app\core\ApiModel{

    /**
     * 添加账号
     * @param $data
     * @return string
     */
    public function addAccount($data){
            $time = date('Y-m-d H:i:s');
            $sql = 'insert into `account` (store_id,account_platform_id,a_name,uid,header_url,gender,auth,auth_status,date_added,date_modified,expired_time)
                    values (?,?,?,?,?,?,?,?,?,?,?)';
            \think\Db::execute($sql,[$data['store_id'],$data['account_platform_id'],$data['a_name'],$data['uid'],$data['header_url'],$data['gender'],$data['auth'],$data['auth_status'],$time,$time,$data['expired_time']]);
            return \think\Db::getLastInsID();
    }

    /**
     * 获取账号信息
     * @param $value
     * @param $key
     * @param $column
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAccount($value,$column,$where_s=''){
        $where = [];
        foreach ($value as $k => $v){
            $where[] = "$k = '$v'";
        }
        $sql_where = implode( " and " , $where);
        if($where_s){
            $sql_where = $sql_where.' and '.$where_s;
        }
        $sql = "select $column from `account` where $sql_where";
        $res = $this -> find($sql);
        return $res;
    }

    public function edit($id, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "`{$key}`='{$value}'";
        }
        $set = implode(',', $set);
        $sql = "UPDATE `account` SET {$set} WHERE `account_id`=?";
        \think\Db::execute($sql, [$id]);
        return true;
    }

    //refreshToken刷新access_token
    public function refreshToken(){
        $redirect_uri = "http://scrm-api.shifuhui.net";
        $sql = "select count(*) as count  from `account`  where unix_timestamp(`expired_time`) - unix_timestamp(now()) <= 86400 and account_platform_id = 1 and status = 1";
        $count = $this -> find($sql);
        $num = 20;//每次20个账号
        $frequency = ceil($count['count']/$num); //循环次数
        $client_id = config('weibo')['client_id'];
        $client_secret = config('weibo')['client_secret'];
        for($i = 1;$i <= $frequency; $i++){
            $sql = "select account_id,auth from `account`  where unix_timestamp(`expired_time`) - unix_timestamp(now()) <= 86400 and account_platform_id = 1 and status = 1 order by account_id asc limit 20";
            $result = $this -> findAll($sql);
            if($result){
                foreach ($result as $k => $v){
                    $antu = json_decode($v['auth'],TRUE);
                    //一天以内授权即将到期的账号，重新获取access_token
                    $url = "https://api.weibo.com/oauth2/access_token?client_id=$client_id&client_secret=$client_secret&grant_type=refresh_token&redirect_uri=$redirect_uri&refresh_token=".$antu['refresh_token'];
                    $res = curl_request($url,"GET");
                    if($res){
                        $res['expired_time'] = time() + $res['expires_in'];
                        $anth = json_encode($res);
                        $account_id = $v['account_id'];
                        $updataParam['auth'] = $anth;
                        $this -> edit($account_id,$updataParam);
                    }else{
                        $account_id = $v['account_id'];
                        $updataParam['auth_status'] = 2;//授权失效
                        $this -> edit($account_id,$updataParam);
                    }
                }

            }
        }
        echo 'ok';
    }

    //获取账号授权信息
    public function getAccountAuth($accountId){
        $value = ['account_id' => $accountId];
        $column = 'auth';
        $accountInfo = $this -> getAccount($value,$column);
        $accountAuthInfo = json_decode($accountInfo['auth'],true);
        return $accountAuthInfo;
    }

    //获取公司
    public function getStore($id){
        $sql = 'select * from `store` where store_id = ?';
        $res = $this -> find($sql,[$id]);
        return $res;
    }

    //删除账号
    public function del($userId){
        $sql = "delete from `user` where user_id = ?";
        \think\Db::execute($sql,[$userId]);
        return true;
    }
}