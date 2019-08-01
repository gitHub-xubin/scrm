<?php
/**
 * 检查秒拍视频审核状态
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/9
 * Time: 3:42 PM
 */
namespace app\tools\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
class CheckMPUrl extends Command{
    protected function configure() {
        $this->setName('CheckMPUrl')->setDescription('return CheckMPUrl ');
    }

    protected function execute(Input $input, Output $output) {
        $this->CheckMPUrl();
    }

    public function CheckMPUrl() {
        try {
            $cront = new \app\model\material\Material();
            $cront->checkMpUrl();
        } catch (\Exception $e) {
            \think\Log::record('--cront--CheckMPUrl--' . $e->getLine() . '--' . $e->getMessage() . '--' . $e->getFile());
        }
    }
}