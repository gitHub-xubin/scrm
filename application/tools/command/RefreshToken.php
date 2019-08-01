<?php
/**
 * 刷新微博access_token
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/6/20
 * Time: 3:46 PM
 */

namespace app\tools\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class RefreshToken extends Command {

    protected function configure() {
        $this->setName('RefreshToken')->setDescription('return RefreshToken ');
    }

    protected function execute(Input $input, Output $output) {
        $this->refreshToken();
    }

    //刷新access_token
    public function refreshToken() {
        try {
            $cront = new \app\model\account\Basic();
            $cront->refreshToken();
        } catch (\Exception $e) {
            \think\Log::record('--cront--refreshToken--' . $e->getLine() . '--' . $e->getMessage() . '--' . $e->getFile());
        }
    }

}
