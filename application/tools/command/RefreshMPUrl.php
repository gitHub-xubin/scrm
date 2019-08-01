<?php
/**
 * 获取秒拍地址
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/25
 * Time: 11:51 AM
 */
namespace app\tools\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
class RefreshMPUrl extends Command{
    protected function configure() {
        $this->setName('RefreshMPUrl')->setDescription('return RefreshMPUrl ');
    }

    protected function execute(Input $input, Output $output) {
        $this->RefreshMPUrl();
    }

    public function RefreshMPUrl() {
        try {
            $cront = new \app\model\material\Basic();
            $cront->refreshMPUrl();
        } catch (\Exception $e) {
            \think\Log::record('--cront--RefreshMPUrl--' . $e->getLine() . '--' . $e->getMessage() . '--' . $e->getFile());
        }
    }
}