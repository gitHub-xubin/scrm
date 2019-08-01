<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/6/21
 * Time: 2:09 PM
 */
namespace app\model\account;
class Account extends \app\core\ApiModel{

    /**
     * 添加微博账号详情
     * @param $data
     * @return string
     */
    public function  addAccountWBDetail($data){
        $time = date('Y-m-d H:i:s');
        $sql = "insert into `account_weibo_detail` (account_id,followers_count,friends_count,statuses_count,favourites_count,created,updated)values (?,?,?,?,?,?,?)";
        \think\Db::execute($sql,[$data['account_id'],$data['followers_count'],$data['friends_count'],$data['statuses_count'],$data['favourites_count'],$time,$time]);
        return \think\Db::getLastInsID();
    }

    /**
     * 编辑微博详账号情表
     * @param $id
     * @param $data
     * @return bool
     */
    public function edit($id, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "`{$key}`='{$value}'";
        }
        $set = implode(',', $set);
        $sql = "UPDATE `account_weibo_detail` SET {$set} WHERE `account_id`=?";
        \think\Db::execute($sql, [$id]);
        return true;
    }

    /**
     * 添加账户组关系表
     * @param $data
     * @return string
     */
    public function addTeamAccount($data){
        $sql = "insert into `team_to_account` (team_id,account_id)values (?,?)";
        \think\Db::execute($sql,[$data['team_id'],$data['account_id']]);
        return true;
    }

    public function getTeamAccount($user_id,$teamId,$accountId = ''){
        $where = "where t.user_id = $user_id and t.team_id = $teamId";
        if($accountId){
            $where .= ' and tta.account_id = '.$accountId;
        }
        $sql = 'select * from `team_to_account` as tta left  join `team` as t  on t.team_id = tta.team_id '.$where;
        $res = $this -> find($sql);
        return $res;
    }

    public function delTeamAccount($account,$team_id = null){
        if($team_id){
            $sql = "delete from `team_to_account` where team_id = ? and account_id = ?";
            \think\Db::execute($sql,[$team_id,$account]);
        }else{
            $sql = "delete from `team_to_account` where account_id = ?";
            \think\Db::execute($sql,[$account]);
        }
        return true;
    }

    /**
     * 账户列表
     * @return array|mixed
     */
    public function getLists($store_id,$userId,$page = 0, $pageSize = 10,$status = 1,$account_platform_id = '',$teamId='',$name = '',$isAll=null) {
        $order = ['c.account_id DESC'];
        $where[] = 'c.store_id = '.$store_id;
        $where[] = 'c.status = '.$status;
        $where[] = 't.user_id = '.$userId;

        if ($name) {
            $where[] = "(c.a_name like '%{$name}%')";
        }
        if($teamId){
            $where[] = "t.team_id = $teamId";
        }
        if($account_platform_id){
            $where[] = "c.account_platform_id = $account_platform_id";
        }
        //var_dump($where);die;
        if(empty($isAll)){
            $sql = "SELECT c.account_id,c.auth_status, c.account_platform_id, c.a_name, c.header_url, c.gender, c.date_added,  t.name as team_name , t.team_id 
                from `account` as c 
                left join `team_to_account` as tta on tta.account_id = c.account_id
                left join `team` as t on t.team_id = tta.team_id ";
            $sql_count = "SELECT count(*) as `count` from `account` as c 
                left join `team_to_account` as tta on tta.account_id = c.account_id
                left join `team` as t on t.team_id = tta.team_id ";
            $return = $this->_getList($sql, $sql_count, $where, $order, true, $page, $pageSize);
        }else{
            $sql = 'select team_id,master from `user` where user_id = ?';
            $team = $this -> find($sql,[$userId]);
            $start = $page * $pageSize;
            $sql_limit = " limit {$start}, {$pageSize}";
            unset($where[2]);
            $sql_where =  implode(' and ', $where);
            $sql_order = ' order by ' . implode(' , ', $order);
            if($team['master'] == 1){
                $sql = "select c.account_id, c.auth_status, c.account_platform_id, c.a_name, c.header_url, c.gender, c.date_added from `account` as c  where ";
                $sql_count = "select count(*) as count from `account` as c  where ";
                $return['count'] = $this->find($sql_count . $sql_where );
                $return['list'] = $this->findAll($sql . $sql_where . $sql_order . $sql_limit);
            }else{
                $sql = "select c.account_id, c.auth_status, c.account_platform_id, c.a_name, c.header_url, c.gender, c.date_added from  `team_to_account` as tta left join `account` as c ON tta.account_id = c.account_id where tta.team_id = ".$team['team_id']." and ";
                $sql_count = "select count(*) as count from  `team_to_account` as tta left join `account` as c ON tta.account_id = c.account_id where tta.team_id = ".$team['team_id']." and ";
                $return['count'] = $this->find($sql_count . $sql_where );
                $return['list'] = $this->findAll($sql . $sql_where . $sql_order . $sql_limit);
            }
        }
        return $return;
    }

    //未分组账号列表
    public function getUnclassiFiedLists($store_id,$page = 0,$pageSize =10,$status = 1,$account_platform_id = '',$name = ''){
        $order = ['account_id DESC'];
        $where[] = 'store_id = '.$store_id;
        $where[] = 'status = '.$status;
        if ($name) {
            $where[] = "(a_name like '%{$name}%')";
        }
        if($account_platform_id){
            $where[] = "account_platform_id = $account_platform_id";
        }
        $start = $page * $pageSize;
        $sql_limit = " limit {$start}, {$pageSize}";
        $sql_where =  implode(' and ', $where);
        $sql_order = ' order by ' . implode(' , ', $order);
        $sql = "select account_id,auth_status, account_platform_id, a_name, header_url, gender, date_added from `account`  where account_id not in (select DISTINCT(account_id) from `team_to_account`) and ";
        $sql_count = "select count(*) as count from `account`  where account_id not in (select DISTINCT(account_id) from `team_to_account`) and ";
        //echo $sql . $sql_where . $sql_order . $sql_limit;die;
        $return['count'] = $this->find($sql_count . $sql_where );
        $return['list'] = $this->findAll($sql . $sql_where . $sql_order . $sql_limit);

        return $return;
    }

    //获取账号组下的用户
    public function getTeamUser($teamId,$storeId){
        $sql = 'select * from `user` where team_id = ? and store_id = ?';
        $res = $this -> find($sql,[$teamId,$storeId]);
        return $res;
    }

    //获取各平台账号列表（不分页）
    public function getAccountList($storeId,$userId,$accountPlatformId = 1){
        $sql = 'select team_id,master from `user` where user_id = ?';
        $team = $this -> find($sql,[$userId]);
        if($team['master'] == 1){
            $sql = "select account_id,a_name from `account` where store_id = ? and account_platform_id = ? and  status = 1 and auth_status = 1";
            $res = $this-> findAll($sql,[$storeId,$accountPlatformId]);
        }else{
            $sql = "select a.account_id,a.a_name from  `team_to_account` as tta left join `account` as a ON tta.account_id = a.account_id where tta.team_id = ? and a.account_platform_id = ? and a.store_id = ? and a.status = 1 and a.auth_status = 1";
            $res = $this -> findAll($sql,[$team['team_id'],$accountPlatformId,$storeId]);
        }
        //echo \think\Db::getLastSql();die;
        return $res;
    }

    //WechatRefreshToken  微信刷新token
    public function wechatRefreshToken(){
        $sql = "select count(*) as count  from `account`  where unix_timestamp(`expired_time`) - unix_timestamp(now()) <= 300  and account_platform_id = 3 and status = 1";
        $count = $this -> find($sql);
        $num = 20;//每次20个账号
        $frequency = ceil($count['count']/$num); //循环次数
        $wxopenConf = config('wechatOpen');
        $component_access_token_json = file_get_contents('/home/scrm_api/component_access_token.json');
        $component_access_token = json_decode($component_access_token_json,TRUE);
        $accountBasicModel = new \app\model\account\Basic();
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=".$component_access_token['component_access_token'];
        for($i = 1;$i <= $frequency; $i++){
            $sql = "select account_id,auth from `account`  where unix_timestamp(`expired_time`) - unix_timestamp(now()) <= 300  and account_platform_id = 3 and status = 1 limit 20";
            $res = $this -> findAll($sql);
            if($res){
                foreach ($res as $k => $v){
                    $auth = json_decode($v['auth'],TRUE);
                    $param = [
                        'component_appid' =>    $wxopenConf['appid'],
                        'authorizer_appid' => $auth['authorizer_appid'],
                        'authorizer_refresh_token' => $auth['authorizer_refresh_token']
                    ];
                    $res = curl_request($url,'POST',$param);
                    //var_dump($res);die;
                    if(!empty($res['authorizer_access_token'])){
                        $newAuth = json_encode(
                            [
                                'authorizer_appid' => $auth['authorizer_appid'],
                                'authorizer_access_token' => $res['authorizer_access_token'],
                                'authorizer_refresh_token' => $res['authorizer_refresh_token'],
                            ]
                        );
                        $expired_time = date('Y-m-d H:i:s',time() + $res['expires_in']);
                        $accountdata = [
                            'auth' => $newAuth,
                            'auth_status' => 1,
                            'expired_time' => $expired_time,
                            'date_modified' => date('Y-m-d H:i:s'),
                        ];
                        $accountBasicModel->edit($v['account_id'], $accountdata);
                    }
                }
            }
        }
    }

    //微信 刷新component_access_token，有效期2小时
    public function refreshComponentAccessToken(){
        $wxopenConf = config('wechatOpen');
        $ticket = file_get_contents('/home/scrm_api/component_verify_ticket.txt');
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_component_token";
        $getAccessTokenParam = [
            'component_appid' => $wxopenConf['appid'],
            'component_appsecret' => $wxopenConf['appsecret'],
            'component_verify_ticket' => $ticket,
        ];
        $rs =  curl_request($url,"POST",$getAccessTokenParam);
        if(!empty($rs['component_access_token'])) {
            file_put_contents('/home/scrm_api/component_access_token.json', json_encode($rs));
        }
    }
}