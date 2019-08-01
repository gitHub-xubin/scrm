<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/6/17
 * Time: 4:01 PM
 */
namespace app\merchant\controller;
class Team extends \app\core\MerchantController{

    //添加账号组
    public function addTeam(){
        $team = input('teamName');
        if(empty($team)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '请输入名称';
            return $this -> _response;
        }
        $teamBasicModel = new \app\model\team\Basic();
        $column = 'team_id,store_id,user_id,name';
        $value = ['name' => $team, 'store_id' => $this ->store_id,'user_id' => $this -> _user_id];
        $teamInfo = $teamBasicModel -> getTeam($value,$column);
        if($teamInfo){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '小组已存在';
            return $this -> _response;
        }
        $id = $teamBasicModel -> addTeam($team,$this -> store_id,$this ->_user_id);
        if($id){
            $this -> _response['code'] = 0;
            $this -> _response['message'] ='ok';
        }else{
            $this -> _response['code'] = -1;
            $this -> _response['message'] = 'fail';
        }
        return $this -> _response;
    }

    //编辑组名
    public function  edit(){
        $teamName = input('teamName');
        $teamId = input('teamId');
        if(empty($teamName) or empty($teamId)){
            $this -> _response['code'] = -1;
            $this -> _response['message'] = '参数缺失';
            return $this -> _response;
        }
        $teamBasicModel = new \app\model\team\Basic();
        $value = ['team_id' => $teamId, 'store_id' => $this ->store_id];
        $column = 'team_id,store_id,name';
        $teamInfo = $teamBasicModel -> getTeam($value,$column);
        if(empty($teamInfo)){
            $this -> _response['code'] = -1;
            $this -> _response['message'] = '账号组不存在';
            return $this -> _response;
        }
        $value = ['name' => $teamName, 'store_id' => $this ->store_id,'user_id' => $this ->_user_id];
        $column = 'team_id,store_id,user_id,name';
        $teamInfo = $teamBasicModel -> getTeam($value,$column);
        if($teamInfo){
            $this -> _response['code'] = -1;
            $this -> _response['message'] = '账号组已存在';
            return $this -> _response;
        }
        $data['name'] = $teamName;
        $teamBasicModel -> editTeam($teamId,$data);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = 'ok';
        return $this -> _response;
    }

    //账号组列表
    public function teamList(){
        $teamModel = new \app\model\team\Team();
        $res = $teamModel -> getTeamList($this -> store_id,$this ->_user_id);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $res;
        return $this -> _response;
    }

    //删除组
    public function del(){
        $teamId = input('teamId');
        if(empty($teamId)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '组ID必须';
            return $this -> _response;
        }
        $teamBasicModel = new \app\model\team\Basic();
        $teamModel = new \app\model\account\Account();
        $info = $teamModel -> getTeamUser($teamId,$this->store_id);
        $_info = $teamModel -> getTeamAccount($this ->_user_id,$teamId);
        if(!empty($info) or !empty($_info)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '分组下有账户，不能删除';
            return $this -> _response;
        }
        $teamBasicModel -> del($teamId);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = 'ok';
        return $this -> _response;
    }

}