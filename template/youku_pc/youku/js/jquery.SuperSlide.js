$(document).ready(function() {
	function e(a) {
		f = a;
		b.eq(a)
			.addClass('current')
			.siblings()
			.removeClass();
		c.eq(a)
			.siblings()
			.stop()
			.hide()
			.animate({
					opacity: 0
				},
				600
			);
		c.eq(a)
			.stop()
			.show()
			.animate({
					opacity: 1
				},
				600
			);
		d.eq(a)
			.stop()
			.animate({
					opacity: 1,
					top: -10
				},
				600
			)
			.siblings('b')
			.stop()
			.animate({
					opacity: 0,
					top: -40
				},
				600
			);
	}

	function a() {
		f++;
		f == b.length && (f = 0);
		e(f);
	}
	var b = $('#lunbonum a'),
		c = $('#lunhuanback a'),
		d = $('.lunhuancenter b'),
		f = 0;
	b.each(function(a) {
		$(this).mouseover(function() {
			e(a);
		});
	});
	var k = setInterval(a, 4e3);
	b.hover(
		function() {
			clearInterval(k);
		},
		function() {
			k = setInterval(a, 4e3);
		}
	);
});

$(document).ready(function() {
	$('#js-list1 li').bind('click', function() {
		var e = $(this).index(),
			a = $('#num-tab1 > div');
		$(this)
			.removeClass()
			.addClass('cur')
			.siblings()
			.removeClass();
		a.removeClass('cur').animate({
			opacity: '0'
		}, 100);
		a.eq(e)
			.addClass('cur')
			.animate({
				opacity: '1'
			}, 100);
	});
	$('#js-list2 li').bind('click', function() {
		var e = $(this).index(),
			a = $('#num-tab2 > div');
		$(this)
			.removeClass()
			.addClass('cur')
			.siblings()
			.removeClass();
		a.removeClass('cur').animate({
			opacity: '0'
		}, 100);
		a.eq(e)
			.addClass('cur')
			.animate({
				opacity: '1'
			}, 100);
	});
});

$(function() {
	$('#main-bg').addClass('main-bg');
	for (var i = 0; i < $('.num-tab .num-tab-main').length; i++) {
		series($('.num-tab .num-tab-main').eq(i), 20, 16);
	}
	$('#myCarousel').carousel({
		pause: true,
		interval: false
	});

	$('.top-nav-more-large').mouseover(function() {
		$('.top-nav-more-large ul').show();
	});
	$('.top-nav-more-large').mouseleave(function() {
		$('.top-nav-more-large ul').hide();
	});

	$('.u-record').mouseover(function() {
		$('#u-record').addClass('dropdown-open');
	});
	$('.u-record').mouseleave(function() {
		$('#u-record').removeClass('dropdown-open');
	});

	$('.u-app').mouseover(function() {
		$('#u-app').addClass('dropdown-open');
	});
	$('.u-app').mouseleave(function() {
		$('#u-app').removeClass('dropdown-open');
	});

	$('.u-login').mouseover(function() {
		$('#u-login').addClass('dropdown-open');
	});
	$('.u-login').mouseleave(function() {
		$('#u-login').removeClass('dropdown-open');
	});

	$('.ff-wd').mouseover(function() {
		$('.autocomplete-suggestions').addClass('topmain-fixed');
	});
});

$(function() {
	$(document).ready(function() {
		$('.aside-btnl').click(function() {
			$('.aside-btnl').toggleClass('u-ele-focus');
			$('.m-player').toggleClass('m-player-open');
			$('.m-player').toggleClass('m-player-close');
		});
	});
	$(document).ready(function() {
		$('.yk_dm_button').click(function() {
			$('.yk_dmswitch_box').toggleClass('yk_dm_enable');
		});
	});

	$(document).ready(function() {
		$('.tab-more').click(function() {
			$('.drama-list').toggleClass('drama-lists');
		});
	});
	$(document).ready(function() {
		$('.btnnext').click(function() {
			$('.btnprev').show();
		});
	});
	$(function() {
		$(document).on('click', function() {
			$('.programDetail').fadeOut();
			$('.icon-jiantou').addClass('unrotate');
			$('.icon-jiantou').removeClass('rotate');
		});
		$(document).on('click', '.yk-modules', function(event) {
			event.stopPropagation();
			$('.programDetail').fadeToggle();
			$('.icon-jiantou').toggleClass('unrotate');
			$('.icon-jiantou').toggleClass('rotate');
		});
		$(document).on('click', '.programDetail', function(event) {
			event.stopPropagation();
		});
	});

	$(window).scroll(function() {
		var c = $('.cms_player').height() + 400;
		$(this).scrollTop() > c ?
			($('.mini').addClass('miniplayer'),
				$('#cms_player').css({
					height: '280px'
				}),
				$(window).resize(function() {
					$('#cms_player').css({
						height: '280px'
					});
				})) :
			($('.mini').removeClass('miniplayer'),
				$('#cms_player').css({
					height: ''
				}),
				$(window).resize(function() {
					$('#cms_player').css({
						height: ''
					});
				}));
	});

	$(function() {
		var c = !1,
			d,
			e,
			b = $('.mini');
		b.bind('click mousedown', function(a) {
			a = a || window.event;
			'click' != a.type &&
				'mousedown' == a.type &&
				((c = !0), (d = a.pageX - parseInt(b.css('left'))), (e = a.pageY - parseInt(b.css('top'))));
		});
		$(document)
			.mousemove(function(a) {
				if (c) {
					var b = $('.mini');
					le = a.pageX - d;
					to = a.pageY - e;
					winH = $(window).height();
					winW = $(window).width();
					cW = winW - b.width() - 20;
					cH = winH - b.height() - 20;
					0 > le ? (le = 0) : le > cW && (le = cW);
					0 > to ? (to = 0) : to > cH && (to = cH);
					b.css({
						top: to,
						left: le
					});
				}
			})
			.mouseup(function() {
				c = !1;
			});
	});
});

$(function() {
	$(window).scroll(function() {
		if ($(window).scrollTop() >= 540) {
			$('.top-main').addClass('topmain-fixed');
			$('.autocomplete-suggestions').addClass('topmain-fixed');
		} else {
			$('.top-main').removeClass('topmain-fixed');
			$('.autocomplete-suggestions').removeClass('topmain-fixed');
		}
	});
});
$(function() {
	$('#myTab1 a').hover(function() {
		$(this).tab('show');
	});
});
$(function() {
	$('#goTop').hide();
	$(function() {
		$(window).scroll(function() {
			if ($(window).scrollTop() > 100) {
				$('#goTop').fadeIn();
			} else {
				$('#goTop').fadeOut();
			}
		});

		$('#goTop').click(function() {
			$('body,html').animate({
				scrollTop: 0
			}, 500);
			return false;
		});
	});
});
$(function() {
	$('.test').css('height', '0');
	$('.test').headBand({
		height: '3',
		'background-color:': '#44bffa',
		background: 'linear-gradient(to right,#70df00,#44bffa)'
	});
});

function hideText(e, conLen, str1, str2) {
	textBox = document.getElementById(e);
	if ('' == conText) {
		conText = textBox.innerHTML;
	}
	if (navigator.appName.indexOf('Explorer') > -1) {
		if (textBox.innerText.length < conLen) {
			return;
		}
		textBox.innerHTML = textBox.innerText.substr(0, conLen);
	} else {
		if (textBox.textContent.length < conLen) {
			return;
		}
		textBox.innerHTML = textBox.textContent.substr(0, conLen);
	}
	textBox.innerHTML +=
		'...</div><a class="js-open btmn" href="javascript:;" onclick="showText(\'' +
		e +
		"','" +
		conLen +
		"', '" +
		str1 +
		"', '" +
		str2 +
		'\');return false" target="_self">展开&gt;&gt;' +
		str1 +
		'</a>';
}

function showText(e, conLen, str1, str2) {
	textBox = document.getElementById(e);
	textBox.innerHTML =
		conText +
		'</div><a class="js-open btmn" href="javascript:;" onclick="hideText(\'' +
		e +
		"', '" +
		conLen +
		"', '" +
		str1 +
		"', '" +
		str2 +
		'\');return false" target="_self">收起&lt;&lt;' +
		str2 +
		'</a>';
}

(function($) {
	$.fn.extend({
		headBand: function(option) {
			var ViewH = $(window).height(),
				ScrollH = $('body')[0].scrollHeight,
				S_V = ScrollH - ViewH,
				getThis =
				this.prop('className') !== '' ?
				'.' + this.prop('className') :
				this.prop('id') !== '' ?
				'#' + this.prop('id') :
				this.prop('nodeName');
			$(window).scroll(function() {
				var ViewH_s = $(this).height(),
					ScrollH_s = $('body')[0].scrollHeight,
					ScoT_s = $(this).scrollTop(),
					Band_w = 100 - ((ScrollH_s - ViewH_s - ScoT_s) / S_V) * 100;
				defaultSetting = {
					background: 'green',
					height: 3,
					width: Band_w + '%'
				};
				setting = $.extend(defaultSetting, option);
				$(getThis).css({
					background: setting.background,
					top: '0',
					'z-index': '99999',
					height: setting.height + 'px',
					width: defaultSetting.width
				});
			});
			return this;
		}
	});
})(jQuery);

$(function() {
	$('#statetab ul li').mouseover(function() {
		TabSelect('#statetab ul li', '.statetab .tabcon', 'active', $(this));
	});
	$('#statetab1 ul li').mouseover(function() {
		TabSelect('#statetab1 ul li', '.statetab1 .tabcon', 'active', $(this));
	});

	function TabSelect(tab, con, addClass, obj) {
		var $_self = obj;
		var $_nav = $(tab);
		$_nav.removeClass(addClass), $_self.addClass(addClass);
		var $_index = $_nav.index($_self);
		var $_con = $(con);
		$_con.removeClass('active');
		$_con.eq($_index).addClass('active');
	}
});

function addFavorite(obj, opts) {
	var _t, _u;
	if (typeof opts != 'object') {
		_t = document.title;
		_u = location.href;
	} else {
		_t = opts.title || document.title;
		_u = opts.url || location.href;
	}
	try {
		window.external.addFavorite(_u, _t);
	} catch (e) {
		if (window.sidebar) {
			obj.href = _u;
			obj.title = _t;
			obj.rel = 'sidebar';
		} else {
			alert('抱歉，您所使用的浏览器无法完成此操作。\n\n请使用 Ctrl + D 将本页加入收藏夹！');
		}
	}
}

/*
 var OriginTitile = document.title;
    var titleTime;
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            document.title = '(つェ⊂) 我藏好了哦~ ' + OriginTitile;
            clearTimeout(titleTime);
        }
        else {
            document.title = '(*´∇｀*) 被你发现啦~ ' + OriginTitile;
            titleTime = setTimeout(function() {
                document.title = OriginTitile;
            }, 1000);
        }
    });
 */

