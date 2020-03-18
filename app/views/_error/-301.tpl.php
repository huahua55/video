<?php
$ua = $_COOKIE['_ua'];
if($ua == 'mobile' || ($ua != 'pc' && preg_match('/android|iphone|ipad|ipod|IEMobile/i', $_SERVER['HTTP_USER_AGENT']))){
	include(dirname(__FILE__) . '/../../views_mobile/_error/404.tpl.php');
	return;
}
?>
<!doctype html>
<html lang="zh-cmn-Hans">
<?php _widget('_component/common/head', array(
	'css' => $css,
	'webpack' => $webpack,
	'js_lib' => $js_lib)); 
?>
<body>
<style>
.error {
	padding: 148px;
	font-size: 16px;
	color: #444A54;
	text-align: center;
}
.error > .img-wrap {
	margin-bottom: 26px;
}
.error > .img-wrap > img {
	width: 170px;
}
.error > .btn-wrap {
	padding-top: 52px;
	width: 148px;
	margin: 0 auto;
}
.error > .btn-wrap > .g-btn {
	width: 148px;
}

.g-btn-minor, .g-btn-minor:hover {
	border-color: transparent;
	background: transparent;
	color:  #2793fa !important;
}
</style>
<div class="error">
	<div class="img-wrap">
		<img src="/static/img/-301.png" alt="404" />
	</div>
	<p>保险公司正在生成电子保单</p>
	<p>请您耐心等待···</p>
	<div class="btn-wrap">
		<a href="javascript:;" onclick="history.go(-1)" class="g-btn g-btn-major">返回上一页</a>
		<a href="javascript:;" onclick="location.reload()" class="g-btn g-btn-minor">刷新重试</a>
	</div>
</div>
</body>
</html>