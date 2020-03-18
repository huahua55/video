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
		<img src="/static/img/pc/500.png" alt="500" />
	</div>
	<?php
		$msg = htmlspecialchars($_e->getMessage());
		if(ENV != 'online'){
			debug_print_backtrace();
		}
		if(strpos($msg, 'in SQL:') !== false || strpos($msg, 'db error') !== false){
			Logger::error($_e);
			$msg = '系统繁忙: 10';
		}
	?>
	<?php if ($msg) { ?>
	<p><?= $msg ?></p>
	<?php } ?>
	<?php if ($html_content && $html_content != $html_tittle_text) { ?>
	<p><?=$html_content?></p>
	<?php } else { ?>
	<p>很遗憾，您可以重新尝试或继续其他操作！</p>
	<?php } ?>
	<div class="btn-wrap">
		<?php if ($error_link && $error_link_name) { ?>
		<a href="<?= _url($error_link) ?>" class="g-btn g-btn-major"><?=htmlspecialchars($error_link_name)?></a>
		<?php } else if ($html_left_button_text || $html_right_button_text) { ?>
			<?php if ($html_left_button_text) { ?>
		<a href="<?= _url($html_left_button_link ? $html_left_button_link : '/') ?>" class="g-btn g-btn-major"><?=htmlspecialchars($html_left_button_text)?></a>
			<?php } ?>
			<?php if ($html_right_button_text) { ?>
		<a href="<?=_url($html_right_button_link ? $html_right_button_link : '/')?>" class="g-btn g-btn-default-major"><?=htmlspecialchars($html_right_button_text)?></a>
			<?php } ?>
		<?php } else { ?>
		<a href="<?= $back_link ? _url($back_link) : _url('/') ?>" class="g-btn g-btn-major">返回首页</a>
		<?php } ?>
	</div>
</div>
</body>
</html>