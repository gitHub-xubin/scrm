<?php
/**
 * 微博转发跟转处理
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/15
 * Time: 3:08 PM
 */
namespace app\tools\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class WeiBoForward extends Command{
    protected function configure() {
        $this->setName('WeiBoForward')->setDescription('return WeiBoForward ');
    }

    protected function execute(Input $input, Output $output) {
        $this->forward();
    }

    public function forward() {
        try {
            $cront = new \app\model\forward\Weibo();
            $cront->forwardHandle();
        } catch (\Exception $e) {
            \think\Log::record('--cront--WeiBoForward--' . $e->getLine() . '--' . $e->getMessage() . '--' . $e->getFile());
        }
    }
}