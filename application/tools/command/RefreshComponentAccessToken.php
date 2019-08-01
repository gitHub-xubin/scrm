<?php
/**
 * 刷新微信 component_access_token
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/22
 * Time: 3:51 PM
 */
namespace app\tools\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class RefreshComponentAccessToken extends Command {

    protected function configure() {
        $this->setName('RefreshComponentAccessToken')->setDescription('return RefreshComponentAccessToken ');
    }

    protected function execute(Input $input, Output $output) {
        $this->RefreshComponentAccessToken();
    }

    //刷新access_token
    public function RefreshComponentAccessToken() {
        try {
            $cront = new \app\model\account\Account();
            $cront->RefreshComponentAccessToken();
        } catch (\Exception $e) {
            \think\Log::record('--cront--RefreshComponentAccessToken--' . $e->getLine() . '--' . $e->getMessage() . '--' . $e->getFile());
        }
    }

}