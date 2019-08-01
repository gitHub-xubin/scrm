<?php

namespace app\core;

use think\Controller;

/**
 * 商家后台
 *
 * @author aoxiang
 */
class MerchantController extends Controller {

    /**
     * 定义返回格式
     * @var array 
     */
    protected $_response = ['code' => 0, 'message' => '', 'data' => ''];
    protected $_user_id;
    protected $token;
    protected $store_id;
    public function _initialize() {
        $r = \think\Request::instance();

        $_c = strtolower($r->controller());
        $_a = strtolower($r->action());

        $_filter = [
            'logon' => ['logon'],
            'user' => ['sendmail','checkemail','register','forgetpassword'],
            'wechat' => ['authcallback','storewechatcallback','addwechataccount'],
        ];
        if (isset($_filter[$_c])) {
            $_f_v = $_filter[$_c];
            if ($_f_v == '*') {
                return;
            }
            if (in_array($_a, $_f_v)) {
                return;
            }
        }

        $token = $r->header('Authorization');
        if (empty($token)) {
            $this->_response['code']    = 1000;
            $this->_response['message'] = '不合法用户';
            echo json_encode($this->_response);
            exit;
        }
        $memberBasicModel = new \app\model\member\Basic();
        $decodeToken = $memberBasicModel -> check($token);
        if($decodeToken){
            $decodeToken = (array)$decodeToken;
            $this -> _user_id = $decodeToken['user_id'];
            $this -> token = $token;
            $this -> store_id = $decodeToken['store_id'];
        }else{
            $this->_response['code']    = 1000;
            $this->_response['message'] = '请重新登录';
            echo json_encode($this->_response);
            exit;
        }
    }

    /**
     * 异常信息收集
     * @param Exception $e
     */
    public function _error($e) {
        $this->_response['code']    = $e->getCode();
        $this->_response['message'] = $e->getMessage();
    }

}
