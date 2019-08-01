<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/15
 * Time: 5:13 PM
 */

namespace app\model\statistics;

class AccountData extends \app\core\ApiModel{

    public function accountDataList($startTiem,$endTime,$storeId){
        $sql = "select sum(waad.fans_number) as fans_number, sum(waad.fans_incr) as fans_incr, sum(waad.fans_decr) as fans_decr,
                sum(waad.readings_number) as readings_number , sum(waad.forwards_number) as forwards_number,
                sum(waad.plays_number) as plays_number , sum(waad.comments_number) as comments_number,
                sum(waad.attitudes_number) as attitudes_number, sum(waad.interactions_number) as interactions_number,
                sum(waad.interactions_number) as interactions_number , a.a_name from `weibo_analysis_account_data` as waad  left join 
                `account` as a on waad.account_id = a.account_id 
                where waad.weibo_date >= '$startTiem' and waad.weibo_date <= '$endTime' and waad.account_id in (select account_id from `account` where store_id = $storeId) 
                ";
        $res = $this -> findAll($sql);
        return $res;
    }
}