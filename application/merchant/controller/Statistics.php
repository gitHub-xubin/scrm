<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/10
 * Time: 4:01 PM
 */
namespace app\merchant\controller;

class Statistics extends \app\core\MerchantController{

    //首页周统计数据
    public function weekStatistics(){
        $time = '1561824000';
        $startTime = date('Y-m-d',($time-((date('w',$time)==0?7:date('w',$time))-1)*24*3600));//本周一
        $endTime = date('Y-m-d',$time);
        $lastweekBegin = date('Y-m-d',strtotime($startTime) - 7*24*3600);//上周周一
        $lastweekend = date('Y-m-d',strtotime($startTime) - 24*3600);//上周周日
        $analysisModel = new \app\model\statistics\Analysis();
        $res = $analysisModel -> analysisAccount($this -> store_id,$startTime,$endTime,$lastweekBegin,$lastweekend);
        if(empty($res['week']['fans_number'])){
            $res['fansIncreaseRation'] = 0;
        }else{
            $data['fansIncreaseRation'] = bcmul(bcdiv(bcsub($res['week']['fans_number'] ,$res['lastWeek']['fans_number']),$res['lastWeek']['fans_number'],4),100,4);
        }
        if(empty($res['week']['readings_number'])){
            $res['readingsIncreaseNumber'] = 0;
        }else{
            $data['readingsIncreaseNumber'] = bcmul(bcdiv(bcsub($res['week']['readings_number'] ,$res['lastWeek']['readings_number']),$res['lastWeek']['readings_number'],4),100,4);
        }
        if(empty($res['week']['plays_number'])){
            $data['playsIncreaseNumber'] = 0;
        }else{
            $data['playsIncreaseNumber'] = bcmul(bcdiv(bcsub($res['week']['plays_number'] ,$res['lastWeek']['plays_number']),$res['lastWeek']['plays_number'],4),100,4);
        }
        if(empty($res['week']['interactions_number'])){
            $data['interactionsIncreaseNumber'] = 0;
        }else{
            $data['interactionsIncreaseNumber'] = bcmul(bcdiv(bcsub($res['week']['interactions_number'] ,$res['lastWeek']['interactions_number']),$res['lastWeek']['interactions_number'],4),100,4);
        }
        $data['fans_number'] = $res['week']['fans_number'];
        $data['readings_number'] = $res['week']['readings_number'];
        $data['plays_number'] = $res['week']['plays_number'];
        $data['interactions_number'] = $res['week']['interactions_number'];
        //近半年粉丝数
        $result = $analysisModel -> halfYear($this -> store_id, $time);
        $data['halfYearFans'] = $result;
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $data;
        return $this -> _response;
    }

    public function accountDataStatistics(){
        $startTime = input('startTime');
        $endTime = input('endTime');

        if(empty($startTime) and empty($endTime)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $accountDataModel = new \app\model\statistics\AccountData();
        $res = $accountDataModel -> accountDataList($startTime,$endTime,$this ->store_id);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = 'ok';
        $this -> _response['data'] = $res;
        return $this -> _response;
    }
}