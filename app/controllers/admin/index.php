<?php
// 首页不做权限验证, 所以不继承 AdminController
class IndexController extends Controller
{
	function init($ctx){
		parent::init($ctx);
		$ctx->user = AdminCenter::auth();
		if(!$ctx->user){
			_redirect('admin/sso/login');
		}
	}

	function index($ctx) {

	}

	function login($ctx) {

	}

}