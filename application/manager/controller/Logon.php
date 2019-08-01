<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/5/16
 * Time: 5:06 PM
 */
namespace app\manager\controller;

class Logon extends \app\core\ManagerController{

    public function Logon(){
        $userName = input('userName');
        $password = input('password');

        $rules = [
            ['userName','require','用户名必须'],
            ['password','require','密码必须'],
        ];
        $validate = new \think\Validate($rules);
        if ($validate->check(['userName'=>$userName,'password'=>$password]) == false) {
            $this->_response['code'] = 10001;
            $this->_response['message'] = $validate->getError();
            return $this->_response;
        }
        $memberBasicModel = new \app\model\member\Basic();
        $column = 'user_id,username,mobile,email,password,salt';
        $userInfo = $memberBasicModel -> getUserByName($userName,$column);
        if(empty($userInfo)){
            $this->_response['code'] = 10001;
            $this->_response['message'] = '用户不存在';
            return $this->_response;
        }
        if(md5($userInfo['salt'].$password) != $userInfo['password']){
            $this->_response['code'] = 10001;
            $this->_response['message'] = '密码错误';
            return $this->_response;
        }
        $userInfo['token'] = $memberBasicModel -> getToken($userInfo);
        $data['token'] = $userInfo['token'];
        unset($userInfo['password'],$userInfo['salt']);
        $memberBasicModel -> editUser($userInfo['user_id'],$data);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $userInfo;
        return $this -> _response;
    }

}