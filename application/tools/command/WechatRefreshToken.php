<?php
/**
 * 刷新微信access_token
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/22
 * Time: 1:52 PM
 */
namespace app\tools\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
class WechatRefreshToken extends Command{
    protected function configure() {
        $this->setName('WechatRefreshToken')->setDescription('return WechatRefreshToken ');
    }

    protected function execute(Input $input, Output $output) {
        $this->WechatRefreshToken();
    }

    public function WechatRefreshToken() {
        try {
            $cront = new \app\model\account\Account();
            $cront->WechatRefreshToken();
        } catch (\Exception $e) {
            \think\Log::record('--cront--WechatRefreshToken--' . $e->getLine() . '--' . $e->getMessage() . '--' . $e->getFile());
        }
    }
}