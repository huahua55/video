<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>管理后台</title>
</head>
<body>

<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <?php _widget('admin/_menu')?>
</div>

<div class="container">

	<?php _view(); ?>

	<div class="footer">
		Copyright&copy;2014-<?=date('Y')?> charmingcapital. All rights reserved.
		<?php printf('%.2f', 1000*(microtime(1) - APP_TIME_START)); ?> ms
	</div>

</div>

</body>
</html>
