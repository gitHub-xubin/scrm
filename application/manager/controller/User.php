<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/5/17
 * Time: 2:11 PM
 */
namespace app\manager\controller;

class User extends \app\core\ManagerController{

    public function add(){
        $data['username'] = input('username');
        $password = input('password');
        $confirmPassword  = input('confirmPassword');
        $data['mobile'] = input('mobile');
        $data['email'] = input('email');

        $rules = [
            ['username','require','用户名必须'],
            ['password','require','密码必须'],
            ['confirmPassword','require','确认密码必须'],
            ['mobile','require|number','手机号必须|手机号格式不正确'],
            ['email','require','邮箱必须'],
        ];
        $validate = new \think\Validate($rules);
        if ($validate->check(['username'=>$data['username'],'password'=>$password,'confirmPassword' => $confirmPassword,
                            'mobile' => $data['mobile'],'email' => $data['email']]) == false) {
            $this->_response['code'] = 10001;
            $this->_response['message'] = $validate->getError();
            return $this->_response;
        }
        if($password != $confirmPassword){
            $this->_response['code'] = 10001;
            $this->_response['message'] = '密码不一致，请重新输入';
            return $this->_response;
        }
        $memberBasicModel = new \app\model\member\Basic();
        $column = 'user_id';
        $memberInfo = $memberBasicModel -> getUserByName($data['username'],$column);
        if($memberInfo){
            $this->_response['code'] = 10001;
            $this->_response['message'] = '用户名已存在';
            return $this->_response;
        }
        $data['salt'] = rand(000000,999999);
        $data['password'] = md5($data['salt'].$confirmPassword);
        $data['token'] = $this -> token;
        $data['ip'] =  $_SERVER['REMOTE_ADDR'];
        $id = $memberBasicModel -> addUser($data);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = '添加成功';
        $this -> _response['data'] = $id;
        return $this->_response;
    }

    public function  edit(){
        $id = input('id/d');
        $data['username'] = input('username');
        $password = input('password');
        $confirmPassword  = input('confirmPassword');

        $rules = [
            ['user_id','require|number','用户名id|id类型错误'],
            ['username','require','用户名必须'],
        ];
        $validate = new \think\Validate($rules);
        if ($validate->check(['user_id'=>$id,'username'=>$data['username']]) == false) {
            $this->_response['code'] = 10001;
            $this->_response['message'] = $validate->getError();
            return $this->_response;
        }
        $memberBasicModel = new \app\model\member\Basic();
        $column = 'user_id';
        $value = ['username' => $data['username']];
        $memberInfo = $memberBasicModel -> getUserByKey($value,$column);
        if($memberInfo){
            $this->_response['code'] = 10001;
            $this->_response['message'] = '用户名已存在';
            return $this->_response;
        }
        if(!empty($password)){
            if($password != $confirmPassword){
                $this->_response['code'] = 10001;
                $this->_response['message'] = '密码不一致，请重新输入';
                return $this->_response;
            }
        }
        $data['salt'] = rand(000000,999999);
        $data['password'] = md5($data['salt'].$confirmPassword);
        $sta = $memberBasicModel -> editUser($id,$data);
        if($sta){
            $this->_response['code'] = 0;
            $this->_response['message'] = '修改成功';
        }else{
            $this->_response['code'] = -1;
            $this->_response['message'] = '修改异常';
        }
        return $this->_response;
    }

    public function del(){
        $id = input('id/d');
        if(empty($id)){
            $this->_response['code'] = 10001;
            $this->_response['message'] = '缺少参数';
            return $this->_response;
        }
        $memberBasicModel = new \app\model\member\Basic();
        $column = 'user_id';
        $value = ['user_id' => $id];
        $memberInfo = $memberBasicModel -> getUserByKey($value,$column);
        if(!$memberInfo){
            $this->_response['code'] = 10001;
            $this->_response['message'] = '用户名不存在';
            return $this->_response;
        }
        $memberBasicModel -> delUser($id);
        $this->_response['code'] = 0;
        $this->_response['message'] = '删除成功';
        return $this->_response;
    }

    public function userList(){
        $page = input('page/d',0);
        $pageSize = input('pageSize/d',10);
        $page = max($page - 1,0);
        $username = input('username');
        $memberModel = new \app\model\member\Member();
        $list = $memberModel -> getLists($page,$pageSize,$username);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $list;
        return $this -> _response;
    }

}