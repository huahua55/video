<!doctype html>
<html lang="zh-cmn-Hans">
<?php _widget('_component/common/head', array(
	'css' => $css,
	'hide_header' => $hide_header,
	'webpack' => $webpack,
	'js_lib' => $js_lib)); 
?>
<body>
<?php _widget('_component/common/header', []); ?>
<?php _view(); ?>
<?php !$hide_footer && _widget('_component/common/footer'); ?>
<?php ENV == 'online' && _widget('_component/common/analyse'); ?>
<?php ENV != 'online' && printf('<p>渲染时间: %.2f ms</p>', 1000*(microtime(1) - APP_TIME_START)); ?>
</body>
</html>
