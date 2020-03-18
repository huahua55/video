<?php
class SsoController extends Controller
{
	function init($ctx){
		parent::init($ctx);
		$ctx->user = AdminCenter::auth();
	}
	
	function login($ctx){
		if($ctx->user){
			_redirect('admin');
		}

		$jump = '';
		$url = AdminCenter::get_login_url($jump);
		echo <<<HTML
		<div style="padding: 100px 0; text-align: center; font-size: 18px;">
			正在跳转至 <a href="$url">后台统一管理中心登录</a> ...
			<meta http-equiv="Refresh" content="2; url=$url" />
		</div>
HTML;
	}
	
	function callback($ctx){
		$sess = AdminCenter::login($_GET['token']);
		if(!$sess){
			_throw('登录失败!');
		}
		
		$jump = trim($_GET['jump']);
		$domain = Html::host();
		if(!preg_match("/^http(s)?:\/\/[^\/]*$domain\//", $jump)){
			$jump = "http://$domain/";
		}
		
		$jump_e = htmlspecialchars($jump);
		echo <<<HTML
		<div style="padding: 100px 0; text-align: center; font-size: 18px;">
			登录成功, 正在跳转至 <a href="$jump">$jump_e</a> ...
			<meta http-equiv="Refresh" content="2; url=$jump" />
		</div>
HTML;
	}
	
	function logout($ctx){
		if($ctx->user){
			AdminCenter::logout();
			$ctx->user = null;

			$jump = _action('logout');
			$url = AdminCenter::get_logout_url($jump);
			echo <<<HTML
			<div style="padding: 100px 0; text-align: center; font-size: 18px;">
				正在跳转至 <a href="$url">后台统一管理中心</a> ...
				<meta http-equiv="Refresh" content="2; url=$url" />
			</div>
HTML;
		}else{
			echo <<<HTML
			<div style="padding: 100px 0; text-align: center; font-size: 18px;">
				已经退出
			</div>
HTML;
		}
	}
}
