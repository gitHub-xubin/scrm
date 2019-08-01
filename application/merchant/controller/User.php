<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/5/17
 * Time: 2:11 PM
 */
namespace app\merchant\controller;
use think\Cache;
use think\Exception;
class User extends \app\core\MerchantController{

    public function register(){
        $mail = input('email');
        $userName = input('userName');
        $enterpriseName = input('enterpriseName');
        $password = input('password');
        $confirmPassword = input('confirmPassword');
        $signature = input('signature');
        $rules = [
            ['email','require|email','邮箱必须|邮箱格式不正确'],
            ['userName','require','姓名必须'],
            ['enterpriseName','require','企业名称必须'],
            ['password','require','密码必须'],
            ['confirmPassword','require','确认密码必须'],
            ['signature','require','signature必须']
        ];
        $validate = new \think\Validate($rules);
        if($validate -> check([
                'email' => $mail,
                'userName' => $userName,
                'enterpriseName' => $enterpriseName,
                'password' => $password,
                'confirmPassword' => $confirmPassword,
                'signature' => $signature
            ]) == false){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = $validate -> getError();
            return $this -> _response;
        }
        if($signature != Cache::pull($mail)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = 'Signature Error';
            return $this -> _response;
        }
        if($password != $confirmPassword){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '密码不一致';
            return $this -> _response;
        }
        $memberBasicModel = new \app\model\member\Basic();
        $column = "store_id";
        $store = $memberBasicModel -> getStoreByName($enterpriseName,$column);
        if($store){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '企业已存在，请重新注册';
            return $this -> _response;
        }
        $column = 'user_id';
        $value = ['email' => $mail];
        $memberInfo = $memberBasicModel -> getUserByKey($value,$column);
        if($memberInfo){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '邮箱已存在，请重新注册';
            return $this -> _response;
        }
        \think\Db::startTrans();
        try{
            $param['store_id'] = $memberBasicModel -> addStore($enterpriseName);
            $param['salt'] = rand(000000,999999);
            $param['password'] = md5($param['salt'].$password);
            $param['username'] = $userName;
            $param['email'] = $mail;
            $param['ip'] = $_SERVER['SERVER_ADDR'];
            $param['master'] = 1;
            $param['team_id'] = null;
            $userInfo['user_id'] = $memberBasicModel -> addUser($param);
            $userInfo['userName'] = $userName;
            $userInfo['store_id'] = $param['store_id'];
            $data['token'] = $memberBasicModel -> getToken($userInfo);
            $memberBasicModel -> editUser($userInfo['user_id'],$data);
            $userInfo['token'] = $data['token'];
            $this -> _response['code'] = 0;
            $this -> _response['data'] = $userInfo;
            \think\Db::commit();
        }catch (\think\Exception $e){
            $this -> _response['code'] = -1;
            \think\Db::rollback();
        }
        return $this -> _response;
    }
    public function checkEmail(){
        $tomail = input('email');
        $code = input('code');
        $_code = Cache::pull($tomail);
        if($code != $_code){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '验证码不正确';
        }else{
            $signature = md5(rand(000000,999999));
            Cache::set($tomail,$signature,600);//设置注册验证串，到期时间10分钟
            $this -> _response['code'] = 0;
            $this -> _response['message'] = 'ok';
            $this -> _response['data'] = $signature;
        }
        return $this -> _response;
    }
    public function sendMail(){
        $tomail = input('email');
        $type = input('type');
        $rules = [
            ['email','require|email','邮箱必须|邮箱不正确'],
            ['type','require|number|>:0','类型必须|类型不正确'],
        ];
        $validate = new \think\Validate($rules);
        if ($validate->check(['email'=>$tomail,'type' => $type]) == false) {
            $this->_response['code'] = 10001;
            $this->_response['message'] = $validate->getError();
            return $this->_response;
        }
        $name = 'User';
        $subject = 'Your authentication code';
        $key = rand(000000,999999);
        $keyEndTime = date("Y-m-d H:i:s", time()+300);
        if($type == 1){
            //注册邮件内容
            $body = '<p>
                <br/>
                </p>
                <p>
                    <span style="color: rgb(0, 176, 240); font-size: 36px;"><strong style="white-space: normal;">速报SCRM</strong></span>
                </p>
                <p>
                    <span style="color: rgb(0, 176, 240); font-size: 36px;"><span style="font-size: 20px; color: rgb(0, 0, 0);">Your email address:'.$tomail.'</span></span>
                </p>
                <p>
                    Thank you for registering&quot;<strong>速报SCRM&quot;</strong>，Your authentication code：'.$key.'
                </p>
                <p>
                    Effective deadline：'.$keyEndTime.'
                </p>
                <p>
                    Copy and paste the above verification code to the registration page verification code.
                </p>';
        }else{
            //忘记密码邮件内容
            $body = '<p>
                <br/>
                </p>
                <p>
                    <span style="color: rgb(0, 176, 240); font-size: 36px;"><strong style="white-space: normal;">速报SCRM</strong></span>
                </p>
                <p>
                    <span style="color: rgb(0, 176, 240); font-size: 36px;"><span style="font-size: 20px; color: rgb(0, 0, 0);">Your email address:'.$tomail.'</span></span>
                </p>
                <p>
                    Thank you for use&quot;<strong>速报SCRM&quot;</strong>，Your authentication code：'.$key.'
                </p>
                <p>
                    Effective deadline：'.$keyEndTime.'
                </p>
                <p>
                    Copy and paste the above verification code into the Forgot Password page verification code.
                </p>';
        }

        Cache::set($tomail, $key, 300);
        $state = send_mail($tomail,$name,$subject,$body);
        if($state){
            $this -> _response['code'] = 0;
            $this -> _response['message'] = 'ok';
        }else{
            $this -> _response['code'] = 0;
            $this -> _response['message'] = '发送失败';
        }
        return $this -> _response;
    }

    //添加子账号
    public function addChildAccount(){
        $userName = input('userName');
        $email = input('email');
        $password = input('password');
        $confirmPassword = input('confirmPassword');
        $team_id = input('teamId');
        $rules = [
            ['email','require|email','邮箱必须|邮箱格式不正确'],
            ['userName','require','姓名必须'],
            ['password','require','密码必须'],
            ['confirmPassword','require','确认密码必须'],
        ];
        $validate = new \think\Validate($rules);
        if($validate -> check([
                'email' => $email,
                'userName' => $userName,
                'password' => $password,
                'confirmPassword' => $confirmPassword,
            ]) == false){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = $validate -> getError();
            return $this -> _response;
        }
        if($password != $confirmPassword){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '密码不一致';
            return $this -> _response;
        }
        $memberBasicModel = new \app\model\member\Basic();
        $value = ['email' => $email,'store_id' => $this ->store_id];
        $col = 'user_id';
        $userInfo = $memberBasicModel -> getUserByKey($value,$col);
        if(!empty($userInfo)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '邮箱已占用';
            return $this -> _response;
        }
        $salt = rand(000000,999999);
        $param = [
            'username' => $userName,
            'email' => $email,
            'store_id' => $this -> store_id,
            'salt' => $salt,
            'ip' => '',
            'password' => md5($salt.$password),
            'master' => 2,
            'team_id' => $team_id,
        ];
        $user_id = $memberBasicModel -> addUser($param);
        if($user_id){
            $this -> _response['code'] = 0;
            $this -> _response['message'] = 'ok';
        }else{
            $this -> _response['code'] = -1;
            $this -> _response['message'] = 'fail';
        }
        return $this -> _response;
    }

    //编辑子账号
    public function  edit(){
        $userId = input('userId/d');
        $data['username'] = input('userName');
        $password = input('password');
        $confirmPassword  = input('confirmPassword');
        $data['email'] = input('email');
        $data['team_id'] = input('teamId');
        $rules = [
            ['user_id','require|number','用户名id必须|id类型错误'],
            ['username','require','用户名必须'],
            ['email','require|email','邮箱必须|邮箱格式不正确'],
        ];
        $validate = new \think\Validate($rules);
        if ($validate->check(['user_id'=>$userId,'username'=>$data['username'],'email' => $data['email']]) == false) {
            $this->_response['code'] = 10001;
            $this->_response['message'] = $validate->getError();
            return $this->_response;
        }
        $memberBasicModel = new \app\model\member\Basic();
        $column = 'user_id';
        $value = ['username' => $data['username'],'store_id' => $this -> store_id];
        $where_s = 'user_id <> '.$userId;
        $memberInfoForuserName = $memberBasicModel -> getUserByKey($value,$column,$where_s);
        if($memberInfoForuserName){
            $this->_response['code'] = 10001;
            $this->_response['message'] = '用户名已存在';
            return $this->_response;
        }
        $column = 'user_id';
        $value = ['email' => $data['email'],'store_id' => $this -> store_id];
        $where_s = 'user_id <> '.$userId;
        $memberInfoForEmail = $memberBasicModel -> getUserByKey($value,$column,$where_s);
        if($memberInfoForEmail){
            $this->_response['code'] = 10001;
            $this->_response['message'] = '邮箱已存在';
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
        $sta = $memberBasicModel -> editUser($userId,$data);
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
        $username = input('userName');
        $teamId =  input('teamId');
        $memberModel = new \app\model\member\User();
        $list = $memberModel -> getLists(2,$this->store_id,$page,$pageSize,$username,$teamId);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $list;
        return $this -> _response;
    }

    //忘记密码
     public function forgetPassword(){
         $mail = input('email');
         $code = input('code');
         $password = input('password');
         $confirmPassword = input('confirmPassword');
         $_code = Cache::pull($mail);
         if($code != $_code){
             $this -> _response['code'] = 10001;
             $this -> _response['message'] = '验证码不正确';
             return $this -> _response;
         }
         if($password != $confirmPassword){
             $this -> _response['code'] = 10001;
             $this -> _response['message'] = '密码不一致';
             return $this -> _response;
         }
         $column = 'user_id';
         $value = ['email' => $mail];
         $memberBasicModel = new \app\model\member\Basic();
         $memberInfo = $memberBasicModel -> getUserByKey($value,$column);
         if(empty($memberInfo)){
             $this -> _response['code'] = 10001;
             $this -> _response['message'] = '该邮箱未注册，请使用已注册邮箱';
             return $this -> _response;
         }
         $data['salt'] = rand(000000,999999);
         $data['password'] = md5($data['salt'].$confirmPassword);
         $sta = $memberBasicModel -> editUser($memberInfo['user_id'],$data);
         if($sta){
             $this->_response['code'] = 0;
             $this->_response['message'] = '修改成功';
         }else{
             $this->_response['code'] = -1;
             $this->_response['message'] = '修改异常';
         }
         return $this->_response;
     }
}