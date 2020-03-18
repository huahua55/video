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
	padding: 80px;
	font-size: 16px;
	color: #0A0B0C;
	text-align: center;
}
.error > .img-wrap {
	margin-bottom: 26px;
}
.error > .img-wrap > img {
	width: 294px;
}
.error > .btn-wrap {
	padding-top: 52px;
}
.error > .btn-wrap > .g-btn {
	width: 148px;
}
</style>
<div class="error">
	<div class="img-wrap">
		<img src="/static/img/pc/404.png" alt="404" />
	</div>
	<p>很抱歉，您要访问的页面不存在</p>
	<div class="btn-wrap">
		<a href="/" class="g-btn g-btn-major">返回首页</a>
	</div>
</div>
</body>
</html>