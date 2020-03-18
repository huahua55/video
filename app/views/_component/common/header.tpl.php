<?php 
	$nav = $_SERVER['REQUEST_URI'];
	if(preg_match('/^\/qiye(\W|$)/i', $nav)){
		$page = 'qiye';
	} else if (preg_match('/^\/claim(\W|$)/i', $nav)) {
		$page = 'claim';
	} else if(preg_match('/^\/login(\W|$)|\/register(\W|$)/i', $nav)) {
		$page = 'login';
	} else if(preg_match('/^\/product(\W|$)/i', $nav)) {
		$page = 'product';
	} else {
		$page = 'index';
	}
?>
<div class="topbar">
	<div class="l-container">
		欢迎致电：400-666-9007（每天9:00-20:00）
		<a href="javascript:" class="iconfont wechat-icon">&#xe66f;
			<div class="topbar-qr-code">
				<p>扫一扫关注卓铭保险微信</p>
				<div class="img-wrap">
					<img src="/static/img/pc/qcode.jpg" alt="公众号：charminginsurance" />
				</div>
				<p>微信号：CharmingInsurance</p>
			</div>
		</a>
		<div class="topbar-control">
			<?php if ($page == 'qiye') { ?>
				<?php if ($profile) { ?>
					<div class="topbar-item-login">
						<a href="/qiye/account"><?=htmlspecialchars(substr_replace($profile->mobile, '****', 3, -4))?></a><!--
						--><a href="/qiye/logout">退出登录</a>
					</div>
				<?php } else { ?>
					<div class="topbar-item-login">
						<a href="/qiye/login">企业登录</a><!--
						--><a href="/qiye/register">企业注册</a>
					</div>
				<?php } ?>
			<?php } else { ?>
				<?php if ($profile) { ?>
					<div class="topbar-item-login">
						<a href="/user"><?=htmlspecialchars($profile->nickname) ? htmlspecialchars($profile->nickname) : htmlspecialchars(substr_replace($profile->mobile, '****', 3, -4))?></a><!--
						--><a href="/logout">退出登录</a>
					</div>
				<?php } else { ?>
					<div class="topbar-item-login">
						<a href="/login">登录</a><!--
						--><a href="/register">注册</a>
					</div>
				<?php } ?>
			<?php } ?>

			<a href="javascript:" class="topbar-item topbar-app">卓铭保险App
				<div class="topbar-qr-code">
					<p>扫一扫下载卓铭保险App</p>
					<div class="img-wrap">
						<img src="/static/img/pc/app.png" alt="公众号：charminginsurance" />
					</div>
				</div>
			</a>
			<a href="/about/help" class="topbar-item">帮助中心</a>
		</div>
	</div>
</div>
<?php if ($page != 'login') { ?>
<header class="g-header<?=$transparent_header?' transparent':''?>">
	<div class="l-container">
		<a href="/" class="g-logo" title="卓铭保险"></a>
		<nav class="g-nav">
			<ul class="g-nav-list">
				<li><a href="/" <?=$page=='index'?'class="active"':''?>>首页</a></li>
				<li><a href="/product" <?=$page=='product'?'class="active"':''?>>保险产品</a></li>
				<li><a href="/qiye" <?=$page=='qiye'?'class="active"':''?>>企业团险</a></li>
				<li><a href="/claim" <?=$page=='claim'?'class="active"':''?>>无忧理赔</a></li>
			</ul>
		</nav>
		<?php if ($page == 'qiye') { ?>
		<nav class="g-nav fr">
			<ul class="g-nav-list">
				<li><a class="hover-animation" href="/qiye/account">企业账户</a></li>
			</ul>
		</nav>
		<?php } else { ?>
		<nav class="g-nav fr">
			<ul class="g-nav-list">
				<li><a class="hover-animation" href="/user">个人中心</a></li>
			</ul>
		</nav>
		<?php } ?>
	</div>
</header>
<?php } ?>