<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept ,Authorization');
header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE'); // 允许请求的类型
// [ 应用入口文件 ]

// 定义应用目录
define('ROOT_PATH', realpath(__DIR__ . '/../') . '/');
define('APP_PATH', __DIR__ . '/../application/');
define('CONF_PATH', __DIR__ . '/../config/');
// 加载框架引导文件
require ROOT_PATH . '../thinkphp/start.php';
