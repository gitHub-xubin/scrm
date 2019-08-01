<?php
/**
 * 各平台账号授权管理
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/5/20
 * Time: 3:53 PM
 */
namespace app\manager\controller;

class Account extends \app\core\ManagerController{

    public function addAccount(){
        $data['account_platform_id'] = input('account_platform_id/d');
        if(empty($data['account_platform_id'])){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '平台未选择';
            return $this -> _response;
        }
        $data['a_name'] = input('account');
        switch ($data['account_platform_id']){
            case 1:
                $data['a_name'] = input('a_name');
                $data['app_id'] = input('app_id');
                $data['app_token'] = input('app_token');
                $res = $this -> addBJAccount($data);
                break;
            case 2:
                $res = $this -> addQEAccount($data);
                break;
            case 3:
                $res = $this -> addBiliAccount($data);
                break;
            default:
                echo '类型不存在';die;
        }
        if($res['code'] == 0){
            $this -> _response['code'] = 0;
            $this -> _response['message'] = '添加成功';
        }else{
            $this -> _response['code'] = -1;
            $this -> _response['message'] = $res['message'];
        }
        return $this -> _response;
    }

    /**
     * 添加百家号
     * @param $data
     * @return array
     */
    public function addBJAccount($data){
        $rules = [
            ['a_name','require','账号名称必须'],
            ['app_id','require','appid必须'],
            ['app_token','require','app_token必须'],
        ];
        $validate = new \think\Validate($rules);
        if(!$validate -> check(['a_name' => $data['a_name'],'app_id'=>$data['app_id'],'app_token'=>$data['app_token']])){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = $validate -> getError();
            return $this -> _response;
        }
        $accountBasicModel = new \app\model\account\Basic();
        $info = $accountBasicModel -> getAllByWhere('account_id,auth',"account_platform_id = ".$data['account_platform_id']);
        if($info){
            foreach ($info as $k => $v){
                $auth = json_decode($v['auth'],TRUE);
                if($data['app_id'] == $auth['app_id']){
                    return ['code'=> -1,'message' =>'账号已添加'];
                }
            }
        }
        $data['user_id'] = $this -> _user_id;
        $auth = ['app_id' => $data['app_id'],'app_token' => $data['app_token']];
        $data['auth'] = json_encode($auth);

        $id = $accountBasicModel -> addAccount($data);
        return ['code'=> 0,'data' =>$id];
    }


}