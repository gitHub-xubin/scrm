<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/5/16
 * Time: 5:06 PM
 */
namespace app\merchant\controller;

class Logon extends \app\core\MerchantController{

    public function Logon(){
        $email = input('email');
        $password = input('password');

        $rules = [
            ['email','require|email','邮箱必须|邮箱格式不正确'],
            ['password','require','密码必须'],
        ];
        $validate = new \think\Validate($rules);
        if ($validate->check(['email'=>$email,'password'=>$password]) == false) {
            $this->_response['code'] = 10001;
            $this->_response['message'] = $validate->getError();
            return $this->_response;
        }
        $memberBasicModel = new \app\model\member\Basic();
        $column = 'user_id,store_id,username as userName,email,password,salt,master';
        $value = ['email' => $email];
        $userInfo = $memberBasicModel -> getUserByKey($value,$column);
        if(empty($userInfo)){
            $this->_response['code'] = 10002;
            $this->_response['message'] = '用户不存在';
            return $this->_response;
        }
        if(md5($userInfo['salt'].$password) != $userInfo['password']){
            $this->_response['code'] = 10002;
            $this->_response['message'] = '密码错误';
            return $this->_response;
        }
        $userInfo['token'] = $memberBasicModel -> getToken($userInfo);
        $data['token'] = $userInfo['token'];
        $data['ip'] = $_SERVER['SERVER_ADDR'];
        unset($userInfo['password'],$userInfo['salt']);
        $memberBasicModel -> editUser($userInfo['user_id'],$data);
        $accountModel = new \app\model\account\Basic();
        $masterValue= ['store_id' => $userInfo['store_id'],'master' => 1];
        $masterColumn = 'username';
        $master = $memberBasicModel -> getUserByKey($masterValue,$masterColumn);
        $userInfo['master_name'] = $master['username'];
        $store = $accountModel -> getStore($userInfo['store_id']);
        $userInfo['store_name'] = $store['name'];
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $userInfo;
        return $this -> _response;
    }

}