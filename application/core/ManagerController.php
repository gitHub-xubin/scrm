<?php

namespace app\core;

use think\Controller;

/**
 * 管理后台
 *
 */
class ManagerController extends Controller {

    /**
     * 定义返回格式
     * @var array 
     */
    protected $_response = ['code' => 0, 'message' => '', 'data' => ''];
    protected $_user_id;
    protected $token;
    public function _initialize() {
        $r = \think\Request::instance();
        $_m     = $r->module();

        $_c = strtolower($r->controller());
        $_a = strtolower($r->action());

        $_filter = [
            'logon' => ['logon'],
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
        if (!$token) {
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
