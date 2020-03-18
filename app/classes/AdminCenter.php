<?php
class AdminCenter
{
	static $error = '';
	static $connect_timeout = 3;
	static $request_timeout = 3;

	// 测试环境
	static $api_url_base = '';
	static $web_url_base = '';
	static $front_url_base = '';
	private static $domain = '';
	private static $secret = '';
	
	//init
	static function init($domain=null, $secret=null){
		if(self::$domain){
			return;
		}
		session_start();
		
		if(defined('ENV') && ENV == 'online'){
			// api 不需要 HTTPS
			self::$api_url_base = 'http://auc.svc.charminginsurance.cn/svc';
			self::$web_url_base = 'https://auc.charminginsurance.cn';
			self::$front_url_base = 'https://www.charminginsurance.cn';
			self::$domain = 'admin.charminginsurance.cn';
			self::$secret = 'NLqEGXJm4BGD7a4kj';
		}else{
			self::$api_url_base = 'http://dev.auc.svc.charminginsurance.cn/svc';
			self::$web_url_base = 'https://dev.auc.charminginsurance.cn';
			self::$front_url_base = 'https://dev.charminginsurance.cn';
			self::$domain = 'dev.admin.charminginsurance.cn';
			self::$secret = '123456';
		}

		if($domain){
			self::$domain = $domain;
		}
		if($secret){
			self::$secret = $secret;
		}
	}

	static function get_login_url($jump=null){
		self::init();
		$domain = self::$domain;
		$url = self::$web_url_base . "/sso/login?is_sso=1&domain={$domain}";
		$url .= "&jump=" . urlencode($jump);
		
		return $url;
	}
	
	static function get_logout_url($jump=null){
		self::init();
		$domain = self::$domain;
		$url = self::$web_url_base . "/sso/logout?is_sso=1&domain={$domain}";
		$url .= "&jump=" . urlencode($jump);
		
		return $url;
	}
	
	static function auth(){
		self::init();
		$sess = $_SESSION['user'];
		if(!$sess || strtotime($sess['expire']) < time()){
			return null;
		}
		return $sess;
	}

	static function login($token){
		$data = self::request('sso/get_session', array('token'=>$token));

		$md5 = md5(self::$secret . $data['timestamp'] . $data['user']);
		if($md5 !== $data['sign']){
			throw new Exception("Unauthorized response from uc: $resp.");
		}

		// 解出 session 信息
		$sess = @json_decode($data['user'], true);
		if(!$sess || !is_array($sess) || !$sess['uid']){
			throw new Exception("Session not found: $resp.");
		}
		$sess['id'] = $sess['uid'];
		$sess['expire'] = date('Y-m-d 03:00:00', time() + 86400);
		
		$_SESSION['user'] = $sess;
		return $sess;
	}
	
	static function logout(){
		self::init();
		unset($_SESSION['user']);
	}

	static function is_root($admin_id){
		$privs = self::get_all_privileges($admin_id);
		return (bool)($privs['is_root']);
	}
	
	static function check_privilege($admin_id, $path){
		self::init();
		if(self::is_root($admin_id)){
			return true;
		}
		$ps = explode('?', $path);
		$path = $ps[0];
		$path = trim($path, '/');
		$privs = self::get_all_privileges($admin_id);

		//Logger::info('admin_id:'.$admin_id.',privs:'.json_encode($privs));

		if(isset($privs['deny_urls'][$path])){
			return false;
		}

		$ps = explode('/', $path);
		if ($ps[count($ps)-1] != 'index') {
			$ps[] = 'index';
		}
		$tmp = array();
		foreach($ps as $p){
			$tmp[] = $p;
			$url = join('/', $tmp);
			if(isset($privs['allow_urls'][$url])){
				return true;
			}
		}
		return false;
	}
	
	private static function get_all_privileges($admin_id){
		static $cache = array();
		if(!isset($cache[$admin_id])){
			$cache[$admin_id] = self::request('privilege/all', array('admin_id'=>$admin_id));
		}
		return $cache[$admin_id];
	}
	
	private static function make_sign($params, $secret){
		unset($params['sign']);
		ksort($params);
		$str = '';
		foreach($params as $k=>$v){
			$str .= $k . "=" . $v . "&";
		}
		$str .= "secret=" . $secret;
		return strtolower(md5($str));
	}
	
	private static function request($path, $params=array()){
		self::init();
		ltrim($path, '/');
		$url = self::$api_url_base . '/' . $path;

		$params['domain'] = self::$domain;
		$params['timestamp'] = time();
		$params['sign'] = self::make_sign($params, self::$secret);
		
		// 根据 query_str 的长度决定 GET/POST 参数
		$data = array();
		$data['sign'] = $params['sign'];
		unset($params['sign']);
		$total_len = 0;
		foreach($params as $k=>$v){
			$len = strlen($v);
			$total_len += $len;
			$is_post_data = false;
			if($len > 128 || $total_len > 1024){
				$is_post_data = true;
			}else if(stripos($k, 'password') !== false || stripos($k, 'pwd') !== false){
				$is_post_data = true;
			}
			if($is_post_data){
				$data[$k] = $v;
				unset($params[$k]);
			}
		}
		
		$url .= '?' . http_build_query($params);
		$resp = self::http_post($url, $data);
		
		$ret = @json_decode($resp, true);
		if(!is_array($ret)){
			throw new Exception("admincenter error! bad resp: $resp.");
		}
		if($ret['code'] != 1){
			throw new Exception("admincenter error! resp: {$ret['message']}");
		}
		return $ret['data'];
	}

	private static function http_post($url, $data){
		self::$error = '';
		$ch = curl_init($url) ;
		curl_setopt($ch, CURLOPT_POST, 1) ;
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ;
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::$request_timeout);
		$result = @curl_exec($ch) ;
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		self::$error = curl_error($ch);
		curl_close($ch) ;
		
		if($http_code != 200){
			throw new Exception("HTTP $http_code," . self::$error);
		}
		return $result;
	}
}
