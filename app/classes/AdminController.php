<?php
class AdminController extends Controller
{
	function init($ctx){
		parent::init($ctx);
		
		Db::load_balance(); // 自动读写分离
		{
			$user = AdminCenter::auth();
			if(!$user){
				$jump = _url(base_path(), $_GET);
				_redirect('admin/sso/login', array('jump'=>$jump));
				return;
			}
			$ctx->user = $user;
			$ctx->is_root = AdminCenter::is_root($ctx->user['id']);
		
			$url = base_path();
			$allow = AdminCenter::check_privilege($ctx->user['id'], $url);
			if (!$allow) {
				_throw("访问 [$url] 权限不足", 403);	
			}
		}

		$this->admin_logs($ctx);
	}
	
	private function admin_logs($ctx){
		$url = base_path();
		$params = $_GET + $_POST;
		$params = $this->brief_val($params);
		$user = preg_replace('/@.*$/', '', $ctx->user['email']);
		Logger::debug("admin_logs({$user}): $url, " . Text::json_encode($params));
	}
	
	private function brief_val($v){
		if(is_array($v)){
			foreach($v as $k=>$vv){
				if(stripos($k, 'password') !== false || stripos($k, 'pwd') !== false){
					$vv = '***';
				}
				$v[$k] = $this->brief_val($vv);
			}
		}else if(is_object($v)){
			foreach($v as $k=>$vv){
				if(stripos($k, 'password') !== false || stripos($k, 'pwd') !== false){
					$vv = '***';
				}
				$v->$k = $this->brief_val($vv);
			}
		}else{
			$len = 32;
			if(mb_strlen($v) > $len){
				$v = mb_substr($v, 0, $len) . '...';
			}
		}
		return $v;
	}
}
