<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'app\tools\command\RefreshToken',   //刷新access_token
    'app\tools\command\DelayRelease',   //处理延时处理微博
    'app\tools\command\CheckMPUrl',     //检查秒拍视频审核状态
    'app\tools\command\WeiBoForward',   //微博转发跟转处理
    'app\tools\command\WechatRefreshToken', //刷新微信access_token
    'app\tools\command\RefreshComponentAccessToken', //刷新微信 component_access_token
    'app\tools\command\RefreshMPUrl', //获取秒拍视频地址
];
