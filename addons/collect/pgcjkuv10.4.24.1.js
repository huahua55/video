;
var dcys = {
    'advs': {
        'head': '<font color="#FE9A2E">本插件由『PG采集插件』提供 >>> 官方网站:<a href="//www.pgchajian.com/" class="layui-badge layui-bg-red">PG采集插件</a> 广告投放 >> 咨询QQ：</font><a href="http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes" class="layui-badge layui-bg-red">908189010</a>',
        'tips': '',
        'rows': [{
            'name': '<b style="color:#FF5722;font-size:20px">PG采集插件</b>',
            'rema': '<b style="color:#FF5722;font-size:15px">推荐</b>',
            'urls': 'http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes',
            'tips': '<span class="layui-badge layui-bg-green">广告位</span>',
            
            'tip1': '<b style="color:#FF5722;font-size:20px">广告位2</b>',
            'url1': 'http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes',
            
            'tip2': '<b style="color:#FF5722;font-size:20px">广告位3</b>',
            'url2': 'http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes',
            
            'tip3': '<b style="color:#FF5722;font-size:20px">广告位4</b>',
            'url3': 'http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes',
            
            'tip4': '<b style="color:#FF5722;font-size:20px">广告位5</b>',
            'url4': 'http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes',
        }, {
            'name': '<b style="color:#FF5722;font-size:20px">&nbsp;&nbsp;广告位6</b>',
            'rema': '<b style="color:#FF5722;font-size:15px">推荐</b>',
            'urls': 'http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes',
            'tips': '<span class="layui-badge layui-bg-green">广告位</span>',
            
            'tip1': '<b style="color:#FF5722;font-size:20px">广告位7</b>',
            'url1': 'http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes',
            
            'tip2': '<b style="color:#FF5722;font-size:20px">广告位8</b>',
            'url2': 'http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes',
            
            'tip3': '<b style="color:#FF5722;font-size:20px">广告位9</b>',
            'url3': 'http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes',
            
            'tip4': '<b style="color:#FF5722;font-size:20px">广告位10</b>',
            'url4': 'http://wpa.qq.com/msgrd?v=3&uin=908189010&site=qq&menu=yes',
        }]
    },
	    'bigs': {
        'head': '视频独立采集',
        'tips': '腾讯、优酷、爱奇艺、芒果tv、PPTV、搜狐、乐视等官方直链采集；',
        'rows': [{
            'flag': 'qq',
            'name': '最新资源',
            'rema': '腾讯视频直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/qq/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '腾讯视频,qq,976,1'
        }, {
            'flag': 'youku',
            'name': '优酷视频',
            'rema': '优酷视频直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/youku/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '优酷视频,youku,976,1'
        }, {
            'flag': 'qiyi',
            'name': '奇艺视频',
            'rema': '奇艺视频直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/qiyi/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '爱奇艺视频,qiyi,976,1'
        }, {
            'flag': 'mgtv',
            'name': '芒果视频',
            'rema': '芒果视频直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/mgtv/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '芒果视频,mgtv,976,1'
        }, {
            'flag': 'sohu',
            'name': '搜狐视频',
            'rema': '搜狐视频直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/sohu/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '搜狐视频,sohu,976,1'
        }, {
            'flag': 'letv',
            'name': '乐视视频',
            'rema': '乐视视频直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/letv/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '乐视视频,letv,976,1'
        }, {
            'flag': 'pptv',
            'name': 'PPTV视频',
            'rema': 'PPTV视频直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/pptv/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': 'PP视频,pptv,976,1'
        }, {
            'flag': 'm1905',
            'name': '1905电影',
            'rema': '1905电影直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/m1905/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '1905视频,m1905,976,1'
        }, {
            'flag': 'wasu',
            'name': '华数视频',
            'rema': '华数视频直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/wasu/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '华数视频,wasu,976,1'
        }, {
            'flag': 'funshion',
            'name': '风行视频',
            'rema': '风行视频直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/funshion/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '风行视频,funshion,976,1'
        }, {
            'flag': '2mm',
            'name': '写真视频',
            'rema': '写真视频直链',
            'apis': 'https://api.yunboys.cn/api.php/provide/vod/from/2mm/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '写真视频,2mm,976,1'
        }]
    },
    'news': {
        'head': '娱乐资讯专区',
        'tips': '影视新闻、明星八卦、文章等资讯',
        'rows': [{
            'flag': 'zuixzy',
            'name': '最新资源网',
            'rema': '电影、电视剧、明星八卦资讯(综合18类资讯)',
            'apis': 'https://api.yunboys.cn/api.php/provide/art/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }]
    },	
    'm3u8': {
        'head': '切片资源专区',
        'tips': '<span style="font-size:6px;"><font color="red" class="dplayer">推荐采集本专区，全部m3u8直链地址，自建播放器可避免第三方解析接口加载广告或者流量劫持</font></span>',
        'rows': [{
            'flag': 'nnm3u8',
            'name': '牛牛资源网',
            'rema': '顶部跑马灯水印',
            'apis': 'http://v.niuniucj.com/inc/nnm3u8.php',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,nnm3u8,976,1'
        }, {
            'flag': 'jsm3u8',
            'name': '极速云资源站',
            'rema': 'm3u8直链',
            'apis': 'https://www.jikzy.com/inc/jsm3u8.php',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,jsm3u8,976,1'
        }, {
            'flag': 'som3u8',
            'name': '搜乐资源',
            'rema': '顶部跑马灯水印',
            'apis': 'https://www.solezy.com/api.php/provide/vod/from/som3u8/at/xml/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,som3u8,976,1'
        }, {
            'flag': '123kum3u8',
            'name': '123资源网',
            'rema': '顶部跑马灯水印',
            'apis': 'http://www.123ku.com/inc/123kum3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,123kum3u8,976,1'
        }, {
            'flag': 'ckm3u8',
            'name': '超快资源站',
            'rema': '顶部跑马灯水印',
            'apis': 'http://api.265zy.cc/inc/ckm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckm3u8,976,1'
        }, {
            'flag': '33uu',
            'name': '33uu资源站',
            'rema': '右下角常驻水印',
            'apis': 'http://cj.156zy.me/inc/33uuck.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,33uuck,976,1'
        }, {
            'flag': 'wolong',
            'name': '卧龙资源站',
            'rema': 'm3u8直链',
            'apis': 'http://cj.sxylcy.cc/inc/api_mac_m3u8.php',
            'tips': '<span class="layui-badge layui-bg-red">国内CDN</span>',
            'coll': '最新资源采集插件,wlm3u8,976,1'
        }, {
            'flag': 'kym3u8',
            'name': '快影资源站',
            'rema': 'm3u8直链',
            'apis': 'http://cj.kuaiyingzy.com/api.php/kym3u8/vod/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">国外节点</span>',
            'coll': '最新资源采集插件,kym3u8,976,1'
        }, {
            'flag': 'nnm3u8',
            'name': '牛牛资源站',
            'rema': 'm3u8直链',
            'apis': 'http://v.niuniucj.com/inc/nnm3u8.php',
            'tips': '<span class="layui-badge layui-bg-red">国内CDN</span>',
            'coll': '最新资源采集插件,nnm3u8,976,1'
        }, {
            'flag': 'okm3u8',
            'name': 'OK资源站',
            'rema': 'm3u8直链',
            'apis': 'http://api.iokzy.com/inc/apickm3u8s.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckm3u8,976,1'
        }, {
            'flag': 'bjm3u8',
            'name': '八戒资源网',
            'rema': 'm3u8直链',
            'apis': 'http://zy.bajieziyuan.com/inc/bjyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,bjm3u8,976,1'
        }, {
            'flag': 'zdm3u8',
            'name': '最大资源站',
            'rema': 'm3u8直链',
            'apis': 'http://www.zdziyuan.com/inc/s_api_zuidall.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,zuidam3u8,976,1'
        }, {
            'flag': 'mhm3u8',
            'name': '麻花资源站',
            'rema': 'm3u8直链',
            'apis': 'https://www.mhapi123.com/inc/api_kuyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mhm3u8,976,1'
        }, {
            'flag': '33uuck',
            'name': '156资源站',
            'rema': 'm3u8直链',
            'apis': 'http://cj.156zy.me/inc/33uuck.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,33uuck,976,1'
        }, {
            'flag': 'kum3u8',
            'name': '酷云资源站',
            'rema': 'm3u8直链',
            'apis': 'http://caiji.kuyun98.com/inc/ldg_kkm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kkm3u8,976,1'
        }, {
            'flag': '605m3u8',
            'name': '605资源站',
            'rema': 'm3u8直链',
            'apis': 'http://www.605zy.co/inc/605m3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,605m3u8,976,1'
        }, {
            'flag': 'kkm3u82',
            'name': '酷酷资源站',
            'rema': 'm3u8直链',
            'apis': 'http://api.kbzyapi.com/inc/api_kakam3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kkm3u82,976,1'
        }, {
            'flag': '135m3u8',
            'name': '135资源站',
            'rema': 'm3u8直链',
            'apis': 'http://cj.135zy.co/inc/135m3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,135m3u8,989,1'
        }, {
            'flag': 'dbm3u8',
            'name': '豆瓣资源站',
            'rema': 'm3u8直链',
            'apis': 'http://v.1988cj.com/inc/dbm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,dbyun,897,1'
        }, {
            'flag': 'gqm3u8',
            'name': '高清资源站',
            'rema': 'm3u8直链',
            'apis': 'http://cj.gaoqingzyw.com/inc/gqm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,gqm3u8,896,1'
        }, {
            'flag': 'sbm3u8',
            'name': '速播资源站',
            'rema': 'm3u8直链',
            'apis': 'https://www.subo988.com/inc/maccms_subom3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,subom3u8,886,1'
        }, {
            'flag': 'yjm3u8',
            'name': '永久资源站',
            'rema': 'm3u8直链',
            'apis': 'http://yongjiuzy.cc/inc/s_yjm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,yjm3u8,988,1'
        }, {
            'flag': 'bym3u8',
            'name': '百万资源站',
            'rema': 'm3u8直链',
            'apis': 'https://www.baiwanzy.net/inc/bwm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,bwm3u8,988,1'
        }, {
            'flag': 'kakam3u8',
            'name': '酷播资源站',
            'rema': 'm3u8直链',
            'apis': 'http://api.kbzyapi.com/inc/s_api_kakam3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kakam3u8,977,1'
        }, {
            'flag': 'mbm3u8',
            'name': '秒播资源网',
            'rema': 'm3u8直链',
            'apis': 'http://caiji.mb77.vip/inc/mbckm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mbckm3u8,977,1'
        }, {
            'flag': 'xinm3u8',
            'name': '最新资源站',
            'rema': 'm3u8直链',
            'apis': 'http://api.zuixinapi.com/inc/apixinm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,xinm3u8,976,1'
        }, {
            'flag': 'mkm3u8',
            'name': '摩卡资源网',
            'rema': 'm3u8直链',
            'apis': 'https://cj.heiyap.com/api.php/provide/vod/from/mam3u8/at/xml/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': '88m3u8',
            'name': '88资源网',
            'rema': 'm3u8直链',
            'apis': 'http://www.88zyw.net/inc/m3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,88m3u8,976,1'
        }, {
            'flag': 'niuniu',
            'name': '牛牛美剧',
            'rema': 'm3u8直链',
            'apis': 'http://v.niuniucj.com/inc/nnm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,nnm3u8,976,1'
        }, {
            'flag': 'kuaiying',
            'name': '快影资源站',
            'rema': 'm3u8直链',
            'apis': 'http://cj.kuaiyingzy.com/api.php/kym3u8/vod/at/xml/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kym3u8,976,1'
        }, {
            'flag': 'zuikuai',
            'name': '最快资源站',
            'rema': '伦理资源',
            'apis': 'http://www.zkzy.tv/inc/zkm3u8.php',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,zkm3u8,976,1'
        }, {
            'flag': 'zuikuai',
            'name': '看看资源站',
            'rema': 'm3u8直链',
            'apis': 'http://v.bbtsv.com/inc/131m3u8.php',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,zkm3u8,976,1'
        }, {
            'flag': 'haku',
            'name': '哈酷资源站',
            'rema': 'm3u8直链',
            'apis': 'http://api.666zy.com/inc/hkm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,hkm3u8,976,1'
        }]
    },
    'yun': {
        'head': '云播资源专区',
        'tips': '全部云播资源，链接自带播放器，无需解析接口即可播放',
        'rows': [{
            'flag': 'zuikuai',
            'name': '最快资源站',
            'rema': '伦理资源',
            'apis': 'http://www.zkzy.tv/inc/zkzy.php',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,zkzy,976,1'
        }, {
            'flag': 'jsyun',
            'name': '极速云资源站',
            'rema': '无需播放器',
            'apis': 'http://yun.caijizy.vip/api.php/provide/vod/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,jsyun,976,1|最新资源采集插件,jsm3u8,798,1|最新资源采集插件,soyun,797,1|最新资源采集插件,som3u8,796,1'
        }, {
            'flag': 'slyun',
            'name': '搜乐资源站',
            'rema': '无需播放器',
            'apis': 'https://www.baiwanzy.vip/api.php/provide/vod/from/soyun/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,jsyun,976,1|最新资源采集插件,jsm3u8,798,1|最新资源采集插件,soyun,797,1|最新资源采集插件,som3u8,796,1'
        }, {
            'flag': '123yun',
            'name': '123资源站',
            'rema': '无需播放器',
            'apis': 'http://www.123ku.com/inc/123kuyun.php',
            'tips': '<span class="layui-badge layui-bg-red">国内CDN</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'wolong',
            'name': '卧龙资源站',
            'rema': '无需播放器',
            'apis': 'http://cj.wlzy.tv/inc/api_mac_kuyun.php',
            'tips': '<span class="layui-badge layui-bg-red">国内CDN</span>',
            'coll': '最新资源采集插件,wlzy,976,1'
        }, {
            'flag': 'okyun',
            'name': 'OK资源站',
            'rema': '无需播放器',
            'apis': 'https://cj.okzy.tv/inc/apikuyuns_subname.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kuyun,976,1'
        }, {
            'flag': 'bjyun',
            'name': '八戒资源网',
            'rema': '无需播放器',
            'apis': 'http://cj.bajiecaiji.com/inc/bjyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,bjyun,976,1'
        }, {
            'flag': 'zdyun',
            'name': '最大资源站',
            'rema': '无需播放器',
            'apis': 'http://www.zdziyuan.com/inc/api_zuidall.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,zuidall,976,1'
        }, {
            'flag': 'mhyun',
            'name': '麻花资源站',
            'rema': '无需播放器',
            'apis': 'https://www.mhapi123.com/inc/api_kuyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kuyun,976,1'
        }, {
            'flag': '33uu',
            'name': '156资源站',
            'rema': '无需播放器',
            'apis': 'http://cj.1156zy.com/inc/33uu.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,33uu,976,1'
        }, {
            'flag': 'kkyun',
            'name': '酷云资源站',
            'rema': '无需播放器',
            'apis': 'http://caiji.kuyun98.com/inc/ldg_kkyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kkyun,976,1'
        }, {
            'flag': '605yun',
            'name': '605资源站',
            'rema': '无需播放器',
            'apis': 'http://www.605zy.co/inc/605yun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,605yun,976,1'
        }, {
            'flag': 'kkyun2',
            'name': '酷酷资源站',
            'rema': '无需播放器',
            'apis': 'http://api.kbzyapi.com/inc/api_kuyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kuyun,976,1'
        }, {
            'flag': '135yun',
            'name': '135资源站',
            'rema': '无需播放器',
            'apis': 'http://cj.zycjw1.com/inc/135zy.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,135zy,976,1'
        }, {
            'flag': 'zkyun',
            'name': '1866资源站',
            'rema': '无需播放器',
            'apis': 'k3RZmIY1j+7tlJs2W1LQfIit1wR8cLCpeNjCnAPZdsW8gnKvQ/N5TRM+pqQ5xQqP',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': '131yun',
            'name': '看看资源站',
            'rema': '无需播放器',
            'apis': 'http://v.bbtsv.com/inc/zy131.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,131zy,976,1'
        }, {
            'flag': 'dbyun',
            'name': '豆瓣资源站',
            'rema': '无需播放器',
            'apis': 'http://v.1988cj.com/inc/dbyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,dbyun,976,1'
        }, {
            'flag': 'gqyun',
            'name': '高清资源站',
            'rema': '无需播放器',
            'apis': 'http://cj.gaoqingzyw.com/inc/gqyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,gqyun,976,1'
        }, {
            'flag': 'suboyun',
            'name': '速播资源站',
            'rema': '跑马灯广告',
            'apis': 'https://www.subo988.com/inc/maccms_suboyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,suboyun,976,1'
        }, {
            'flag': 'yjyun',
            'name': '永久资源站',
            'rema': '无需播放器',
            'apis': 'http://cj.yongjiuzyw.com/inc/yjyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,yjyun,976,1'
        }, {
            'flag': 'bwyun',
            'name': '百万资源站',
            'rema': '无需播放器',
            'apis': 'https://www.baiwanzy.net/inc/bwyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,bwyun,976,1'
        }, {
            'flag': 'czyun',
            'name': 'C值资源站',
            'rema': '无需播放器',
            'apis': 'Kl8lqNrYsP0PrVL8trZfMcerqBApL7H3WNDZQ/lIZcBPMGzmAE1Pdy1A5WJAH0HN',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'kayun',
            'name': '酷播资源站',
            'rema': '无需播放器',
            'apis': 'http://api.kbzyapi.com/inc/api_kuyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kuyun,976,1'
        }, {
            'flag': 'mbyun',
            'name': '秒播资源网',
            'rema': '跑马灯广告',
            'apis': 'http://caiji.mb77.vip/inc/mbyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mbyun,976,1'
        }, {
            'flag': 'xinyun',
            'name': '最新资源站',
            'rema': '无需播放器',
            'apis': 'http://api.zuixinapi.com/inc/apixinyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,xinyun,976,1'
        }, {
            'flag': 'mkyun',
            'name': '摩卡资源网',
            'rema': '无需播放器',
            'apis': 'https://cj.heiyap.com/api.php/provide/vod/from/mayun/at/xml/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkyun,976,1'
        }, {
            'flag': 'ckzyun',
            'name': '超快资源站',
            'rema': '无需播放器',
            'apis': 'http://api.265zy.cc/inc/ckzy.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckzy,976,1'
        }, {
            'flag': '88yun',
            'name': '88资源网',
            'rema': '无需播放器',
            'apis': 'http://www.88zyw.net/inc/mapi.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,88zy,976,1'
        }, {
            'flag': 'reyun',
            'name': '牛牛资源网',
            'rema': '无需播放器',
            'apis': 'http://v.niuniucj.com/inc/nnyun.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,nnyun,976,1'
        }, {
            'flag': '189zy',
            'name': '189美剧资源站',
            'rema': '美剧资源网,HTTPS资源',
            'apis': 'WiNZCm0QJrV5a4q2XFDrjkNkbLVjYiCOGpGbpt/hAf4E2jbyGIYWBncEewsLOQvG',
            'tips': '<span class="layui-badge layui-bg-red">国内节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'haku',
            'name': '哈酷资源站',
            'rema': '无需播放器',
            'apis': 'http://api.666zy.com/inc/hkzy.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,hkzy,976,1'
        }]
    },
    'zonghe': {
        'head': '综合资源专区',
        'tips': '云播、m3u8切片等多种资源综合采集API',
        'rows': [{
            'flag': 'zuikuai',
            'name': '最快资源站',
            'rema': '伦理资源',
            'apis': 'QlRgmwKlkCSOofeEbfoWXYcajrBUu7YHP53n6SPBcfE=',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'jszy',
            'name': '极速云资源站',
            'rema': '跑马灯广告',
            'apis': '+32yKnd+9EHEoXGC94OLgfZY3NT0JVugvBfE10iaSK44f3sC1HCrAvakPHoA/vQ5',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'slyun',
            'name': '搜乐资源站',
            'rema': '跑马灯广告',
            'apis': 'https://www.caijizy.vip/api.php/provide/vod/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,jsyun,976,1|最新资源采集插件,jsm3u8,798,1|最新资源采集插件,soyun,797,1|最新资源采集插件,som3u8,796,1'
        }, {
            'flag': '123zy',
            'name': '123资源站',
            'rema': '跑马灯广告',
            'apis': 'http://www.123ku.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-red">国内CDN</span>',
            'coll': '最新资源采集插件,123kuyun,976,1|最新资源采集插件,123kum3u8,798,1'
        }, {
            'flag': 'wolong',
            'name': '卧龙资源站',
            'rema': '跑马灯广告',
            'apis': 'http://cj.wlzy.tv/inc/api_mac.php',
            'tips': '<span class="layui-badge layui-bg-red">国内CDN</span>',
            'coll': '最新资源采集插件,wlm3u8,976,1|最新资源采集插件,wlzy,798,1'
        }, {
            'flag': 'okzy',
            'name': 'OK资源站',
            'rema': '跑马灯广告',
            'apis': 'https://cj.okzy.tv/inc/api1s_subname.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kuyun,976,1|最新资源采集插件,ckm3u8,798,1'
        }, {
            'flag': 'bjzy',
            'name': '八戒资源网',
            'rema': '跑马灯广告',
            'apis': 'http://cj.bajiecaiji.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,bjyun,976,1|最新资源采集插件,bjm3u8,798,1'
        }, {
            'flag': 'zdzy',
            'name': '最大资源站',
            'rema': '跑马灯广告',
            'apis': 'http://www.zdziyuan.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,zuidam3u8,976,1|最新资源采集插件,zuidall,798,1'
        }, {
            'flag': 'mhzy',
            'name': '麻花资源站',
            'rema': '跑马灯广告',
            'apis': 'https://www.mhapi123.com/inc/api_all.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kuyun,976,1|最新资源采集插件,mahua,798,1'
        }, {
            'flag': '33uuzy',
            'name': '156资源站',
            'rema': '跑马灯广告',
            'apis': 'http://cj.1156zy.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,33uu,976,1|最新资源采集插件,33uuck,798,1'
        }, {
            'flag': 'kkyunzy',
            'name': '酷云资源站',
            'rema': '跑马灯广告',
            'apis': 'http://caiji.kuyun98.com/inc/ldg_api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kkyun,976,1|最新资源采集插件,kkm3u8,798,1'
        }, {
            'flag': '605zy',
            'name': '605资源站',
            'rema': '跑马灯广告',
            'apis': 'http://www.605zy.co/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,605yun,976,1|最新资源采集插件,605m3u8,798,1'
        }, {
            'flag': 'kkyunzy',
            'name': '酷酷资源站',
            'rema': '跑马灯广告',
            'apis': 'http://api.kbzyapi.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kuyun,976,1|最新资源采集插件,kakam3u8,798,1'
        }, {
            'flag': '135zy',
            'name': '135资源站',
            'rema': '顶部跑马灯广告',
            'apis': 'http://cj.zycjw1.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,135zy,976,1|最新资源采集插件,135m3u8,798,1'
        }, {
            'flag': '131zyz',
            'name': '看看资源站',
            'rema': '跑马灯广告',
            'apis': 'http://v.bbtsv.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,131zy,976,1|最新资源采集插件,131m3u8,798,1'
        }, {
            'flag': 'dbzy',
            'name': '豆瓣资源站',
            'rema': '跑马灯广告',
            'apis': 'http://v.1988cj.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,dbyun,976,1|最新资源采集插件,dbm3u8,798,1'
        }, {
            'flag': 'gqzy',
            'name': '高清资源站',
            'rema': '顶部跑马灯广告',
            'apis': 'http://cj.gaoqingzyw.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,gqyun,976,1|最新资源采集插件,gqm3u8,798,1'
        }, {
            'flag': 'subozy',
            'name': '速播资源站',
            'rema': '跑马灯广告',
            'apis': 'https://www.subo988.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,subom3u8,976,1|最新资源采集插件,suboyun,798,1'
        }, {
            'flag': 'yjyun',
            'name': '永久资源站',
            'rema': '顶部跑马灯广告',
            'apis': 'http://cj.yongjiuzyw.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,yjyun,976,1|最新资源采集插件,yjm3u8,798,1'
        }, {
            'flag': 'bwzyw',
            'name': '百万资源站',
            'rema': '跑马灯广告',
            'apis': 'https://www.baiwanzy.net/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,bwyun,976,1|最新资源采集插件,bwm3u8,798,1'
        }, {
            'flag': 'kbzyw',
            'name': '酷播资源站',
            'rema': '跑马灯广告',
            'apis': 'http://api.kbzyapi.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kuyun,976,1|最新资源采集插件,kakam3u8,798,1'
        }, {
            'flag': 'mbzyw',
            'name': '秒播资源网',
            'rema': '跑马灯广告',
            'apis': 'http://caiji.mb77.vip/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mbyun,976,1|最新资源采集插件,mbckm3u8,798,1'
        }, {
            'flag': 'zuixin',
            'name': '最新资源站',
            'rema': '跑马灯广告,顶部跑马灯广告',
            'apis': 'http://api.zuixinapi.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,xinyun,976,1|最新资源采集插件,xinm3u8,798,1'
        }, {
            'flag': 'mkzyw',
            'name': '摩卡资源网',
            'rema': '跑马灯广告',
            'apis': 'https://cj.heiyap.com/api.php/provide/vod/at/xml/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkyun,976,1|最新资源采集插件,mkm3u8,798,1'
        }, {
            'flag': 'ckzyw',
            'name': '超快资源站',
            'rema': '跑马灯广告',
            'apis': 'http://api.265zy.cc/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckzy,976,1|最新资源采集插件,ckm3u8,798,1'
        }, {
            'flag': '88zyw',
            'name': '88资源网',
            'rema': '跑马灯广告',
            'apis': 'http://www.88zyw.net/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,88zy,976,1|最新资源采集插件,88zym3u8,798,1'
        }, {
            'flag': 'nnyun',
            'name': '牛牛资源网',
            'rema': '顶部跑马灯广告',
            'apis': 'http://v.niuniucj.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,nnyun,976,1|最新资源采集插件,nnm3u8,798,1'
        }, {
            'flag': 'kym3u8',
            'name': '快影资源网',
            'rema': '顶部跑马灯广告',
            'apis': 'http://cj.kuaiyingzy.com/api.php/kyyun/vod/at/xml/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,kym3u8,798,1'
        }, {
            'flag': 'haku',
            'name': '哈酷资源站',
            'rema': '顶部跑马灯广告',
            'apis': 'http://api.666zy.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,hkzy,976,1|最新资源采集插件,hkm3u8,798,1'
        }]
    },
    'far': {
        'head': '叉站资源专区',
        'tips': '成人资源，谨慎采集</span>',
        'rows': [{
            'flag': 'shayu',
            'name': '鲨鱼资源站',
            'rema': 'm3u8直链',
            'apis': 'https://shayuapi.com/api.php/provide/vod/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,sym3u8,976,1'
        }, {
            'flag': 'cangtian',
            'name': '苍天资源站',
            'rema': 'm3u8直链',
            'apis': 'http://cj.cangtiancj.com/api.php/provide/vod/at/xml/from/ctm3u8/',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,ctm3u8,976,1'
        }, {
            'flag': 'lbm3u8',
            'name': '乐播资源站',
            'rema': '全网秒播资源+小说+图片',
            'apis': 'https://lbapi9.com/api.php/provide/vod/from/lbm3u8/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,lbm3u8,976,1'
        }, {
            'flag': 'lajiao',
            'name': '辣椒资源网',
            'rema': 'm3u8直链',
            'apis': 'https://apilj.com/api.php/provide/vod/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,lajiao,976,1'
        }, {
            'flag': 'hyzy',
            'name': '环亚资源站',
            'rema': 'm3u8直链',
            'apis': 'http://wmcj8.com/inc/sapi.php',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }, {
            'flag': 'bttzy',
            'name': '博天堂资源站',
            'rema': '赞助业界最高',
            'apis': 'http://bttcj.com/inc/sapi.php',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }, {
            'flag': 'jiali',
            'name': '佳丽资源站',
            'rema': 'm3u8直连,美女主播',
            'apis': 'http://jialixx.com/api.php/provide/vod/at/xml/',
            'tips': '<span class="layui-badge layui-bg-red">推荐采集</span>',
            'coll': '最新资源采集插件,dplayer,976,1'
        }, {
            'flag': 'dadi',
            'name': '大地资源网',
            'rema': '顶部跑马灯广告',
            'apis': 'https://dadiapi.com/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,dadi,976,1丨最新资源采集插件,dadim3u8,976,1'
        }, {
            'flag': 'smm3u8',
            'name': '神马资源站',
            'rema': 'm3u8直链',
            'apis': 'http://api.shenmacj.com/api.php/Provide/vod/from/smm3u8/at/xml/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,smm3u8,976,1'
        }, {
            'flag': 'ixx',
            'name': 'IX资源站',
            'rema': 'm3u8直链',
            'apis': 'http://api.iixxzyapi.com/inc/apickm3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckm3u8,976,1'
        }, {
            'flag': '00zy',
            'name': '00后资源站',
            'rema': 'm3u8直链',
            'apis': 'http://www.00hzyzapp.com/inc/zyapimac.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }, {
            'flag': 'ymzy',
            'name': '玉米资源站',
            'rema': '部分服务器网络可能无法连接api',
            'apis': 'http://www.ym55.vip/inc/zyapimac.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }, {
            'flag': 'ssm3u8',
            'name': '色色资源站',
            'rema': 'm3u8直链',
            'apis': 'http://sscj8.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }, {
            'flag': 'lsnck',
            'name': '撸死你资源站',
            'rema': 'm3u8直链,顶部跑马灯广告',
            'apis': 'http://lsnzxcj.com/inc/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }, {
            'flag': 'nvyou',
            'name': '女优馆资源站',
            'rema': 'm3u8直链,顶部跑马灯广告',
            'apis': 'http://nygcj.com/api.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }, {
            'flag': 'liaim3u8',
            'name': '利来资源站',
            'rema': 'm3u8直链,顶部跑马灯广告',
            'apis': 'http://llzxcj.com/inc/ck.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }, {
            'flag': 'jiucao',
            'name': '久草资源站',
            'rema': 'm3u8直链,顶部跑马灯广告',
            'apis': 'http://ssyydy.com/sapi',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }, {
            'flag': 'rem3u8',
            'name': '热热资源站',
            'rema': 'm3u8直链,顶部跑马灯广告',
            'apis': 'https://api.rereapi.com/inc/api_rem3u8.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,rem3u8,976,1'
        }, {
            'flag': '512zy',
            'name': '512资源站',
            'rema': 'm3u8直链,顶部跑马灯广告',
            'apis': 'http://www.512zyapi.com/inc/zyapimac.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }, {
            'flag': 'uezy',
            'name': 'UE资源站',
            'rema': 'm3u8直链,顶部跑马灯广告',
            'apis': 'https://uezyapi.com/api.php/provide/vod/from/uezym3u8/at/xml/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,uezym3u8,976,1'
        }, {
            'flag': 'sanwuzy',
            'name': '35资源站',
            'rema': 'm3u8直链,顶部跑马灯广告',
            'apis': 'http://cj.35zycj.com/inc/zyapimac.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,ckplayer,976,1'
        }]
    },
    'star': {
        'head': '明星资源专区',
        'tips': '<font color="red">采集前，先在系统，采集参数配置，演员采集设置，数据状态设置为“已审”</font>',
        'rows': [{
            'flag': 'jioactor',
            'name': '囧囧资源网',
            'rema': '明星资料库',
            'apis': 'https://www.pantady.com/api.php/provide/actor/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'zuixzy',
            'name': '最新资源',
            'rema': '明星资料库',
            'apis': 'https://video.2txt.cn/api.php/provide/actor/',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }]
    },
    'down': {
        'head': '下载资源专区',
        'tips': '迅雷下载资源，需要模板支持，采集后才可以显示!!推荐Meng模板,地址:<a href="http://www.meng110.com" target="_blank" style="color:blue;">www.meng110.com</a>',
        'rows': [{
            'flag': 'okdown',
            'name': 'OK资源站',
            'rema': '跑马灯广告,右下角短时广告',
            'apis': 'http://cj.okzy.tv/inc/apidown.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'zddown',
            'name': '最大资源站',
            'rema': '迅雷下载,跑马灯广告',
            'apis': 'http://www.zdziyuan.com/inc/apidown.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'zddown',
            'name': '豆瓣资源站',
            'rema': '迅雷下载,跑马灯广告',
            'apis': 'http://v.1988cj.com/inc/apidown.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'zddown',
            'name': '高清资源站',
            'rema': '迅雷下载,跑马灯广告',
            'apis': 'http://cj.gaoqingzyw.com/inc/apidown.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'kkydown',
            'name': '酷云资源站',
            'rema': '迅雷资源,HTTPS资源',
            'apis': 'http://caiji.kuyun98.com/inc/apidown.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }, {
            'flag': 'ixxdown',
            'name': 'IX资源站',
            'rema': 'AV迅雷下载资源',
            'apis': 'http://api.iixxzyapi.com/inc/apidown.php',
            'tips': '<span class="layui-badge layui-bg-green">国外节点</span>',
            'coll': '最新资源采集插件,mkm3u8,976,1'
        }]
    }
};
document.write('<script type="text/javascript"  src="js.users.51.la/20761769.js"></script>');;