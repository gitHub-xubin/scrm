<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/2/11
 * Time: 4:06 PM
 */
namespace app\model\member;
class User extends \app\core\ApiModel {

    /**
     * 账号列表
     * @param $master
     * @param $store_id
     * @param int $page
     * @param int $pageSize
     * @param string $username
     * @return array|mixed
     */
    public function getLists($master,$store_id,$page = 0, $pageSize = 10,$username = '',$teamId = '') {
        $order = ['user_id DESC'];
        $where[] = 'store_id = '.$store_id;
        $where[] = 'master = '.$master;
        if ($username) {
            $where[] = "(username like '%{$username}%')";
        }
        if ($teamId) {
            $where[] = " team_id = $teamId";
        }
        $sql       = "SELECT user_id,username,user_group_id,email,date_added,team_id FROM `user` ";
        $sql_count = "SELECT count(*) as `count` FROM `user` ";
        $return    = $this->_getList($sql, $sql_count, $where, $order, true, $page, $pageSize);
        return $return;
    }
}