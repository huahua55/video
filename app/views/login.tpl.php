<?php
  $css[] = 'login/index.css';
  $webpack = 'login_index';
?>
<div id="login" class='pc-login' v-cloak>
	<template>
		<div class="login-logo">
			<a href="/"><img src="/static/img/pc/login/logo.white.png" alt="卓铭保险" class='logo'></a>
			<span class="g-ep-logo">会员{{isRegister ? "注册" : "登录"}}</span>
		</div>
		<div class="login-form">
			<ul class="tabs clearfix">
				<li @click="tabTo('register')"><a :class="{hover: isRegister}" href='javascript:;'>注册</a></li>		
				<li @click="tabTo('login')"><a :class="{hover: !isRegister}" href='javascript:;'>登录</a></li>
			</ul>

			<div class="form">
				<div class="left">
					<form>
						<ul class="login-form-list">
							<li>
								<input type="text" name="mobile" id="mobile" maxlength="11" v-model="mobile">
								<label for="mobile" :class="{focus: mobile.trim() != ''}">手机号码</label>
							</li>
							<li>
								<input type="text" name="verify_code" id="verifyCode" class="min" maxlength="4" v-model="verify_code" autocomplete="off">
								<div class="verify_code" ref="graphWrap"></div>
								<label for="verifyCode" :class="{focus: verify_code.trim() != ''}">右侧图形验证码</label>
							</li>
							<li class="vcode">
								<input type="text" name="mobile_code" id="mobileCode" maxlength="6" v-model="mobile_code">
								<label for="mobileCode" :class="{focus: mobile_code.trim() != ''}">短信验证码</label>
								<a href="javascript:;" @click="sendCode">{{timer > 0 ? timer + 's' : captcha_text}}</a>
							</li>
							<li class='check-out'>
								<div class="check-out-checkbox" v-show="isRegister">
									<label for="TOS">
										<input type="checkbox" name="TOS" id='TOS' v-model="TOS">
										<span>我已阅读并同意<a href="/about/agreement/register" target="_blank">《卓铭保险服务协议》</a></span>
									</label>
								</div>
							</li>
							<li class="center">
								<button type="submit" class="g-btn g-btn-major" @click="nextStep">{{isRegister ? "注册" : "登录"}}</button>
							</li>
							<li>
								<p class="hint theme-danger">{{errorMessage}}</p>
							</li>
						</ul>
					</form>
				</div>
				<div class="right">
					<div class="download-app">
						<div class='app-qrcode'>
							<img src="/static/img/pc/app.png" alt="下载卓铭保险">
						</div>
						<div class="text">扫码下载卓铭保险App</div>
					</div>
				</div>
			</div>

		</div>
	</template>
</div>

<script>
	window.APP_DATA = {
		login_jump_url: <?=json_encode($jump)?>, 
		need_graph_code: <?=json_encode($need_graph_code)?>
	}
</script>

<script src="/static/js/lib/jsencrypt.min.js"></script>
<script src="<?= _url('/fe/dist/pc/manifest.js')?>"></script>
<script src="<?= _url('/fe/dist/pc/vendor.js')?>"></script>
<script src="<?= _url('/fe/dist/pc/'.$webpack.'.js')?>"></script>