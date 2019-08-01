<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/10
 * Time: 4:51 PM
 */
namespace app\model\statistics;

class Analysis extends \app\core\ApiModel{
    /**
     * 统计本周微博账号数据
     * @param $start
     * @param $end
     * @param $lastStart
     * @param $lastEnd
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function analysisAccount($storeId,$start,$end,$lastStart,$lastEnd){
        $sql = "select sum(fans_number) as fans_number, sum(readings_number) as readings_number , sum(plays_number) as plays_number , sum(interactions_number) as interactions_number from `weibo_analysis_account_data`
                where weibo_date >= '$start' and weibo_date <= '$end' and account_id in (select account_id from `account` where store_id = $storeId)";
        $data['week'] = $this -> find($sql);
        $lastSql = "select sum(fans_number) as fans_number, sum(readings_number) as readings_number , sum(plays_number) as plays_number , sum(interactions_number) as interactions_number  from `weibo_analysis_account_data`
                where weibo_date >= '$lastStart' and weibo_date <= '$lastEnd' and account_id in (select account_id from `account` where store_id = $storeId)";
        $data['lastWeek'] = $this -> find($lastSql);
        //echo $sql."\n".$lastSql;die;
        return $data;
    }

    //半年粉丝数
    public function halfYear($storeId,$time){
        $dateTime = date('Y-m-d',$time);
        $startTime = date('Y-m',strtotime($dateTime.'-6 month')).'-01';
        $halfYearFansSql = "select sum(fans_number) as fans_number,DATE_FORMAT(weibo_date,'%Y-%m') as date_time from `weibo_analysis_account_data` 
                            where DATE_FORMAT(weibo_date,'%Y-%m-%d') >= '$startTime' and DATE_FORMAT(weibo_date,'%Y-%m-%d') <= '$dateTime' 
                            and account_id in (select account_id from `account` where store_id = $storeId) group by DATE_FORMAT(weibo_date,'%Y-%m')";
        $res = $this -> findAll($halfYearFansSql);
        //echo $halfYearFansSql;die;
        return $res;
    }
}