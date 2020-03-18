<head>
	<meta charset="utf-8">
	<title><?=$html['title']? $html['title']:'卓铭保险-全球高端互联网保险服务平台'?></title>
	<?php if($responsive) { ?>
		<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
	<?php }?>
	<meta name="keywords" content="<?=$html['keywords']? $html['keywords']:'卓铭保险,保险,互联网保险'?>">
	<meta name="description" content="<?=$html['desc']? $html['desc']:'卓铭保险是全球高端互联网保险服务平台，联合全球顶级的保险集团BUPA、美国信诺保险等数10家国内外专业的保险公司，致力于为当代精英人群提供全球高端保险服务，买保险就上卓铭保险网。'?>">
	<meta name="renderer" content="webkit">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<script>
	(function(){
		window.CONFIG = <?=json_encode(App::$config['jsconf'])?>;
		var isIE = !!window.VBArray;
		if (isIE && !+[1,] && !document.documentMode) { //less than IE8
			window.location.href = '/update';
		}
	})();
	(function () { // 防止被iframe
		try {
			if (top.location.hostname !== window.location.hostname) {
				top.location.href = 'https://charminginsurance.cn';
			}
		} catch(e) {
			top.location.href = 'https://charminginsurance.cn';
		}
	})();
	</script>
	<script type="text/javascript">
	if (typeof Promise == "undefined") {
		var script = document.createElement('script');
		script.src = "<?=_url('/static/js/polyfill/polyfill.min.js')?>"
		document.head.appendChild(script)
	}
	</script>
	<?php if ($qiye) { ?>
		<link rel="stylesheet" type="text/css" href="<?=_url('/static/css/pc/qiye/base.css')?>" >
	<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="<?=_url('/static/css/pc/common/base.css') ?>" >
	<?php } ?>
	<?php if(!$qiye && !$hide_header) { ?>
		<link rel="stylesheet" type="text/css" href="<?=_url('/static/css/pc/common/header.css') ?>">
	<?php } ?>
	<?php if($css){ foreach ($css as $css_file) { ?>
		<?php if ($qiye) { ?>
			<link rel="stylesheet" type="text/css" href="<?=_url('/static/css/pc/qiye/'. $css_file) ?>">
		<?php } else { ?>
			<link rel="stylesheet" type="text/css" href="<?=_url('/static/css/pc/'. $css_file) ?>">
		<?php } ?>
	<?php } } ?>
	<?php if($webpack) { ?>
		<link rel="stylesheet" type="text/css" href="<?=_url('/fe/dist/pc/'.$webpack.'.css') ?>" id="build_css">
	<?php } ?>
	<?php if(!$webpack && $js_lib == 'vue') { ?>
		<?php if(ENV == 'online') { ?>
			<script src="/static/js/lib/vue-2.2.1.min.js"></script>
		<?php } else { ?>
			<script src="/static/js/lib/vue-2.2.1.js"></script>
		<?php } ?>
	<?php } ?>
</head>