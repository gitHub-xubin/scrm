<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/6/18
 * Time: 10:34 AM
 */
namespace app\model\team;

class Team extends \app\core\ApiModel{

    /**
     * 获取账号组列表
     * @param $store_id
     * @return mixed
     */
    public function getTeamList($store_id,$user_id){
        $sql = "select team_id,name from `team` where  store_id = ? and user_id = ?";
        $res = $this -> findAll($sql,[$store_id,$user_id]);
        return $res;
    }

    /**
     * 获取子账号下的所有未分组账户
     * @param $userId
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function childUnclassiFiedList($userId,$account_platform_id = '',$page = 0,$pageSize = 10){
        //所属运营组
        $sql = 'select team_id from `user` where user_id = ?';
        $res = $this -> find($sql,[$userId]);
        //子账号建的组
        $sql = "select team_id from `team` where user_id = ?";
        $userTeamArr = $this -> findAll($sql,[$userId]);
        if(!empty($account_platform_id)){
            $where_s = " and a.account_platform_id = $account_platform_id";
        }else{
            $where_s = '';
        }
        if(!empty($userTeamArr)){
            $userTeamIdArr = [];
            foreach ($userTeamArr as $v){
                $userTeamIdArr[] = $v['team_id'];
            }
            $userTeamStr = implode(',',$userTeamIdArr);
            //子账号已分组的账户
            $result = $this -> getChildTeamToAccount($userId);
            if(!empty($result)){
                $notAccount = [];
                foreach ($result as $v){
                    $notAccount[] = $v['account_id'];
                }
                $notAccountStr = implode(',',$notAccount);
            }
            $sql = "select a.account_id, a.account_platform_id, a.a_name, a.header_url, a.gender, a.date_added from `account` as a left join `team_to_account` as tta on tta.account_id = a.account_id 
                where tta.team_id = ".$res['team_id']." and tta.team_id not in ($userTeamStr) and a.account_id not in ($notAccountStr) ".$where_s;
            $sql_count = "select count(*) as count from `account` as a left join `team_to_account` as tta on tta.account_id = a.account_id 
                where tta.team_id = ".$res['team_id']." and tta.team_id not in ($userTeamStr) and a.account_id not in ($notAccountStr) ".$where_s;
        }else{
            $sql = "select a.account_id, a.account_platform_id, a.a_name, a.header_url, a.gender, a.date_added from `account` as a left join `team_to_account` as tta on tta.account_id = a.account_id 
                where tta.team_id = ".$res['team_id'].$where_s;
            $sql_count = "select count(*) as count from `account` as a left join `team_to_account` as tta on tta.account_id = a.account_id 
                where tta.team_id = ".$res['team_id'].$where_s;
        }
        $order = ['a.account_id desc'];
        $start = $page * $pageSize;
        $sql_limit = " limit $start, $pageSize";
        $sql_order = ' order by ' . implode(' , ', $order);
        $return['count'] = $this->find($sql_count );
        $return['list'] = $this->findAll($sql . $sql_order . $sql_limit);
        return $return;
    }

    //获取子账号以分组的账户
    public function getChildTeamToAccount($userId){
        $sql = "select team_id from `team` where user_id = ?";
        $userTeamArr = $this -> findAll($sql,[$userId]);
        if(!empty($userTeamArr)){
            $userTeamIdArr = [];
            foreach ($userTeamArr as $v){
                $userTeamIdArr[] = $v['team_id'];
            }
            $userTeamStr = implode(',',$userTeamIdArr);
            $sql = "select a.account_id from `team_to_account` as tta left  join `account` as a on tta.account_id = a.account_id where tta.team_id in ($userTeamStr)";
            $res = $this -> findAll($sql);
        }else{
            $res = [];
        }
        return $res;
    }
}