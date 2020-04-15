var css ='<style type="text/css">';
css+='body{width:100%}#fullpromotion{position:fixed;top:0;left:0;z-index:99999;display:-webkit-box;display:-moz-box;display:-o-box;display:-ms-box;display:box;width:100%;height:100%;background:#fff;text-align:center;font-size:0;line-height:0;-webkit-box-orient:horizontal;-webkit-box-pack:center;-webkit-box-align:center;-moz-box-orient:horizontal;-moz-box-pack:center;-moz-box-align:center;-o-box-orient:horizontal;-o-box-pack:center;-o-box-align:center;-ms-box-orient:horizontal;-ms-box-pack:center;-ms-box-align:center;box-orient:horizontal;box-pack:center;box-align:center}#fullpromotion .figure{width:100%;height:100%;background:transparent url(/template/youku_m/images/051000005B1A4344ADC0B0702C012A81.png) no-repeat center bottom;background-size:cover;-webkit-background-size:cover;-moz-background-size:cover}#fullpromotion .action{position:absolute;bottom:0;left:0;width:100%;height:138px;background:url(//r1.ykimg.com/0510000058F9E3DFADB912B2610A842B) 0 -118.5px no-repeat}#fullpromotion .action>div{position:absolute;bottom:35px;left:0;width:50%}#fullpromotion .action>div:last-child{left:50%}#fClose{float:right;margin-right:13px;background:#fff;color:#2692ff}#fClose,#fDownload{width:70%;height:35px;max-width:110px;border-radius:50px;font-size:14px;line-height:35px}#fDownload{float:left;margin-left:13px;background:#2692ff;color:#fff}#fullpromotion .action em{font-style:normal}.logo1{display:none;width:100%;height:118px;background:url(//r1.ykimg.com/0510000058F9E3DFADB912B2610A842B) 0 0 no-repeat}';
css+='</style>';
var appdw = ' <div id="appdwn" style="z-index: 7000;"><div id="fullpromotion" data-spm="2789675"><div class="figure"><div class="action" style="height:151px;background-position: 0 -151px;background-size:512px 303.232px"><div><a href="javascript:ppsr(1);"><div id="fClose"><em>跳过</em></div></a></div><div><a href="javascript:ppsr(7);"><div id="fDownload"><em>立即播放</em></div></a></div></div></div></div></div></div></div>';
(function (){
	if(!isWeiXin()){if($.cookie('appdw')==null){$('body').append(css + appdw).addClass("modal-open");}	}
	function isWeiXin(){var ua = window.navigator.userAgent.toLowerCase();if(ua.match(/MicroMessenger/i) == 'micromessenger'){ return true; }else{ return false; }	}
})();
function ppsr(exp){	 	$.cookie('appdw', 'ok', {expires: exp, path: '/'});if(exp>1){window.open("http://v.zhebaikou.com/detail/1400.html");}$("#appdwn").hide(); }
$(document).ready(function(){
  $("#fDownload,#fClose").click(function(){
	$("body").removeClass("modal-open");
  });
 });