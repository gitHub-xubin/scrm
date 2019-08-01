<?php
/**
 * 处理延时发布的微博
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/7/1
 * Time: 3:06 PM
 */

namespace app\tools\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
class DelayRelease extends Command{
    protected function configure() {
        $this->setName('DelayRelease')->setDescription('return DelayRelease ');
    }

    protected function execute(Input $input, Output $output) {
        $this->delayRelease();
    }

    public function delayRelease() {
        try {
            $cront = new \app\model\release\Weibo();
            $cront->delayRelease();
        } catch (\Exception $e) {
            \think\Log::record('--cront--DelayRelease--' . $e->getLine() . '--' . $e->getMessage() . '--' . $e->getFile());
        }
    }
}