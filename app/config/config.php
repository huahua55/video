<?php
define('ENV', 'dev');

return array(
	'env' => ENV,
	'logger' => array(
		'level' => 'debug', // none/off|(LEVEL)
		'dump' => 'file', // none|html|file, 可用'|'组合
		'files' => array( // ALL|(LEVEL)
			'ALL'	=> "/data/applogs/video/" . date('Y-m') . '.log',
		),
	),
	'db' => array(
		'host' => '127.0.0.1',
		'dbname' => 'video',
		'username' => 'test',
		'password' => '123456',
		'charset' => 'utf8',
		'readonly_db' => array(
			'host' => '127.0.0.1',
			'dbname' => 'video',
			'username' => 'test',
			'password' => '123456',
			'charset' => 'utf8',
		),
	),
	'ssdb' => array(
		'host' => '127.0.0.1',
		'port' => 8888,
	),
	'internal_api' => array(
		'white_list' => array(
			'127.0.0.1'
		),
	),
	'jsconf' => array(
		/*encrypt publish key 失效时间*/
		'ENCRYPT_TIMEOUT'   => 120000,
		'SERVER_TIME' => time() * 1000//服务器时间戳（毫秒）
	),
);
