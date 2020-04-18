$(function() {
	$("img").each(function() {
		var wid = $(this).innerWidth();
		var hei = $(this).innerHeight();
		if (wid > hei) {
			$(this).addClass("bj2")
			// $(this).css('background-image', "url(../images/errorLogo-bj1.png)");
			// $(this).attr('src', "images/errorLogo-bj2.png");
		} else {
			$(this).addClass("bj1")
			// $(this).css('background-image', "url(../images/errorLogo-bj1.png)");
			// $(this).attr('src', "images/errorLogo-bj1.png");
		}
	});
	/*!
	 * =====================================================
	 * 网站日志
	 * =====================================================
	 */
	console.log("%c%c本模板使用苹果CMS V10版本,多功能模板，苹果CMS V10全功能模板", "line-height:28px;",
		"line-height:28px;padding:4px 0px;color:#fff;font-size:16px;background-image:-webkit-gradient(linear,left top,right top,color-stop(0,#ff22ff),color-stop(1,#5500ff));color:transparent;-webkit-background-clip:text;"
	);
	/*!
	 * =====================================================
	 * 监听滚动条高度
	 * =====================================================
	 */
	var scroll = new auiScroll({
		listen: true,
		distance: 200
	}, function(ret) {
		if (ret.scrollTop > 1) {
			$('.scroll-to-comment').show();
		}
		if (ret.scrollTop > 40) {
			$('.scroll-to-top').show();
		} else {
			$('.scroll-to-top').hide();
		}
	});
	/*!
	 * =====================================================
	 * 返回顶部
	 * =====================================================
	 */
	$(".scroll-to-top").on("click", function() {
		$('body,html').animate({
			scrollTop: 0
		}, 500);
	});
	var popup = new auiPopup();

	function showPopup() {
		popup.show(document.getElementById("top-right"))
	}
	// $(document).ready(function() {
	// 	$(".win-xuanji").click(function() {
	// 		$(".popXuanji").addClass("popWinMask_transition");
	// 		$(".popXuanji .playSource_popWin").addClass("popWin_transition");
	// 		$("body").addClass("modal-open");
	// 	});
	// });
	// $(document).ready(function() {
	// 	$(".hdwrap").click(function() {
	// 		$(".popXianlu").addClass("popWinMask_transition");
	// 		$(".popXianlu .playSource_popWin").addClass("popWin_transition");
	// 		$("body").addClass("modal-open");
	// 	});
	// });
	// $(document).ready(function() {
	// 	$(".hide_popWin").click(function() {
	// 		// $(".popWinMask").removeClass("popWinMask_transition");
	// 		$(".playSource_popWin").removeClass("popWin_transition");
	// 		$("body").removeClass("modal-open");
	// 	});
	// });
	$(".popXuanji li").on("click", function() {
		var e = $(this).index(),
			a = $(".num-tab > div");
		b = $(".pSource > span");
		c = $(".popXuanji");
		d = $(".popXuanji .playSource_popWin");
		h = $("body");
		$(this).removeClass().addClass("cur").siblings().removeClass();
		a.removeClass("cur").animate({
			opacity: '0'
		}, 100);
		a.eq(e).addClass("cur").animate({
			opacity: '1'
		}, 100);
		// b.removeClass("cur");
		// b.eq(e).addClass("cur");
		// c.removeClass("popWinMask_transition");
		// d.removeClass("popWin_transition");
		// h.removeClass("modal-open");
	})
	// 
	$("#drama-nav span").on("click",function(){
		var index = $(this).index();
		$(this).addClass("on").siblings().removeClass("on")
		$("#drama-main>li").eq(index).addClass("on").siblings().removeClass("on")
	})
	$(".nav-main span").on("click",function(){
		var index = $(this).index();
		$(this).addClass("on").siblings().removeClass("on")
		$(".download-main .download-item li").eq(index).addClass("on").siblings().removeClass("on")
	})
	
	// 
	$(document).ready(function() {
		$(".popXianlu li").bind("click", function() {
			var e = $(this).index(),
				c = $(".popXianlu");
			d = $(".popXianlu .playSource_popWin");
			h = $("body");
			$(this).removeClass().addClass("cur").siblings().removeClass();
			c.removeClass("popWinMask_transition");
			d.removeClass("popWin_transition");
			h.removeClass("modal-open");
		})
	});
	for (var i = 0; i < $(".albumSelect .num-tab-main").length; i++) {
		series($(".albumSelect .num-tab-main").eq(i), 20, 16);
	}
	// $(".albumDetailIntroTxt").click(function() {
	// 	console.log($(this).data("content"))
	// 	$(this).text($(this).data("content"));
	// });
	var num = 0,
		suolue, xiangq;
	$("#expand").click(function() {
		if (num == 0) {
			suolue = $(this).siblings("span").html()
			xiangq = $(this).parent().data("content")
			num++
		}
		console.log(typeof($(this).html()))
		if ($(this).html() == "展开") {
			console.log("1")
			$(this).html("收起")
			$(this).siblings("span").html(xiangq)
		} else {
			$(this).html("展开")
			$(this).siblings("span").html(suolue)
		}
	})
	// 历史记录
	var jsonstr = MAC.Cookie.Get('wap_history'),
		jsonstrList = "",
		jsondata = [],
		html = '',
		booleanTag = true,
		link = document.URL;
	// 判断是否为play页面
	if ($("#player").length != 0) {
		if (jsonstr != undefined) {
			jsondata = encode(jsonstr);
			// 替换
			for (i = 0; i < jsondata.length; i++) {
				if (jsondata[i].name == name) {
					jsondata[i].link = link
					jsondata.splice(i, 1)
					jsonstrList = {
						"name": name,
						"link": link
					}
					jsondata.unshift(jsonstrList)
					booleanTag = false
				}
			}
			if (booleanTag) {
				jsonstrList = {
					"name": name,
					"link": link
				}
				jsondata.unshift(jsonstrList)
			}
		} else {
			// 存
			jsonstrList = {
				"name": name,
				"link": link
			}
			jsondata.unshift(jsonstrList)
		}
		jsonstr = decode(jsondata)
		console.log(jsondata)
		MAC.Cookie.Set('wap_history', jsonstr);
	}
	if (jsonstr != undefined) {
		jsondata = encode(jsonstr);
		for (i = 0; i < jsondata.length; i++) {
			html += "<a href='" + jsondata[i].link + "' class='history-item'><span>" + jsondata[i].name +
				"</span><span>[继续播放]</span></a>"
		}
	} else {
		html += '<a href="javascript:;" class = "history-item" style="display: block;">暂无浏览记录</a>';
	}
	$(".history-items").html(html)
	// 清空
	$(".claerWap").on("click", function() {
		MAC.Cookie.Del('wap_history');
		if ($.cookie('wap_history') == undefined) {
			$(".history-items").html('<a href="javascript:;" class = "history-item" style="display: block;">已清空！</a>')
			alert("你已清空历史记录！")
		}
	})
	// 封
	function decode(str) {
		for (var i = 0; i < str.length; i++) {
			str[i] = JSON.stringify(jsondata[i])
		}
		var staer = str.join("_")
		return staer
	}
	// 解
	function encode(str) {
		var staer = str.split("_")
		for (var i = 0; i < staer.length; i++) {
			staer[i] = JSON.parse(staer[i])
		}
		return staer
	}
	// 加载默认图片
	// $('img').error(function() {
	// 	console.log("????")
	// 	var wid = $(this).innerWidth();
	// 	var hei = $(this).innerHeight();
	// 	if (wid > hei) {
	// 		$(this).attr('src', "images/errorLogo-bj2.png");
	// 	} else {
	// 		$(this).attr('src', "images/errorLogo-bj1.png");
	// 	}
	// })
});
