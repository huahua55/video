<?php
error_reporting(E_ALL & ~E_NOTICE);

define('APP_PATH', dirname(__FILE__) . '/app');

define('IPHP_PATH', '/data/lib/iphp');
require_once(IPHP_PATH . '/loader.php');

// 微信支付临时配置
// $_SERVER['REMOTE_ADDR'] = '219.142.132.98';
App::run();

if(defined('ENV') && ENV == 'dev'){
	$db = Db::instance();
	$path = base_path();
	Logger::debug("path: /$path, db query count: {$db->query_count}");
}
