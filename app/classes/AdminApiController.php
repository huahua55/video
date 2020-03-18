<?php

class AdminApiController extends AjaxController
{
	function init($ctx){
		parent::init($ctx);

		$ctx->user = AdminCenter::auth();
		if(!$ctx->user){
			_throw('非法请求');
		}

		header("content-type:application/json; charset=utf-8");
		$url = base_path();
		$allow = AdminCenter::check_privilege($ctx->user['id'], $url);
		if (!$allow) {
			_throw("访问 [$url] 权限不足", 403);
		}
	}
}
