# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.26-0ubuntu0.18.04.1)
# Database: video
# Generation Time: 2020-03-21 10:07:06 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table actor
# ------------------------------------------------------------

CREATE TABLE `actor` (
  `actor_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `type_id_1` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '一级分类id',
  `actor_name` varchar(255) NOT NULL DEFAULT '' COMMENT '姓名',
  `actor_en` varchar(255) NOT NULL DEFAULT '' COMMENT '拼音',
  `actor_alias` varchar(255) NOT NULL DEFAULT '' COMMENT '别名',
  `actor_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `actor_lock` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '锁定',
  `actor_letter` char(1) NOT NULL DEFAULT '' COMMENT '首字母',
  `actor_sex` char(1) NOT NULL DEFAULT '',
  `actor_color` varchar(6) NOT NULL DEFAULT '' COMMENT '高亮颜色',
  `actor_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `actor_blurb` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `actor_remarks` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `actor_area` varchar(20) NOT NULL DEFAULT '' COMMENT '地区',
  `actor_height` varchar(10) NOT NULL DEFAULT '' COMMENT '身高',
  `actor_weight` varchar(10) NOT NULL DEFAULT '' COMMENT '体重',
  `actor_birthday` varchar(10) NOT NULL DEFAULT '' COMMENT '生日',
  `actor_birtharea` varchar(20) NOT NULL DEFAULT '' COMMENT '出生地',
  `actor_blood` varchar(10) NOT NULL DEFAULT '' COMMENT '血型',
  `actor_starsign` varchar(10) NOT NULL DEFAULT '' COMMENT '星座',
  `actor_school` varchar(20) NOT NULL DEFAULT '' COMMENT '毕业院校',
  `actor_works` varchar(255) NOT NULL DEFAULT '' COMMENT '主要作品多个逗号相连',
  `actor_tag` varchar(255) NOT NULL DEFAULT '' COMMENT 'tags',
  `actor_class` varchar(255) NOT NULL DEFAULT '' COMMENT '扩展分类',
  `actor_level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '推荐值',
  `actor_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `actor_time_add` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `actor_time_hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击时间',
  `actor_time_make` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '生成时间',
  `actor_hits` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `actor_hits_day` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `actor_hits_week` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `actor_hits_month` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `actor_score` decimal(3,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '平均分',
  `actor_score_all` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总评分',
  `actor_score_num` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '评分次数',
  `actor_up` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '顶数',
  `actor_down` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '踩数',
  `actor_tpl` varchar(30) NOT NULL DEFAULT '' COMMENT '自定义模板',
  `actor_jumpurl` varchar(150) NOT NULL DEFAULT '' COMMENT '跳转url',
  `actor_content` text NOT NULL COMMENT '详情',
  PRIMARY KEY (`actor_id`),
  KEY `type_id` (`type_id`) USING BTREE,
  KEY `type_id_1` (`type_id_1`) USING BTREE,
  KEY `actor_name` (`actor_name`) USING BTREE,
  KEY `actor_en` (`actor_en`) USING BTREE,
  KEY `actor_letter` (`actor_letter`) USING BTREE,
  KEY `actor_level` (`actor_level`) USING BTREE,
  KEY `actor_time` (`actor_time`) USING BTREE,
  KEY `actor_time_add` (`actor_time_add`) USING BTREE,
  KEY `actor_sex` (`actor_sex`),
  KEY `actor_area` (`actor_area`),
  KEY `actor_up` (`actor_up`),
  KEY `actor_down` (`actor_down`),
  KEY `actor_tag` (`actor_tag`),
  KEY `actor_class` (`actor_class`),
  KEY `actor_score` (`actor_score`),
  KEY `actor_score_all` (`actor_score_all`),
  KEY `actor_score_num` (`actor_score_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='演员表';



# Dump of table admin
# ------------------------------------------------------------

CREATE TABLE `admin` (
  `admin_id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(30) NOT NULL DEFAULT '',
  `admin_pwd` char(32) NOT NULL DEFAULT '',
  `admin_random` char(32) NOT NULL DEFAULT '',
  `admin_status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `admin_auth` text NOT NULL COMMENT '权限列表',
  `admin_login_time` int(10) unsigned NOT NULL DEFAULT '0',
  `admin_login_ip` int(10) unsigned NOT NULL DEFAULT '0',
  `admin_login_num` int(10) unsigned NOT NULL DEFAULT '0',
  `admin_last_login_time` int(10) unsigned NOT NULL DEFAULT '0',
  `admin_last_login_ip` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`admin_id`),
  KEY `admin_name` (`admin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台管理员';



# Dump of table art
# ------------------------------------------------------------

CREATE TABLE `art` (
  `art_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `type_id_1` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '一级分类id',
  `group_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '用户组id',
  `art_name` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `art_sub` varchar(255) NOT NULL DEFAULT '' COMMENT '副标题',
  `art_en` varchar(255) NOT NULL DEFAULT '' COMMENT '别名',
  `art_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态0未审1已审',
  `art_letter` char(1) NOT NULL DEFAULT '' COMMENT '首字母',
  `art_color` varchar(6) NOT NULL DEFAULT '' COMMENT '颜色',
  `art_from` varchar(30) NOT NULL DEFAULT '' COMMENT '来源',
  `art_author` varchar(30) NOT NULL DEFAULT '' COMMENT '作者',
  `art_tag` varchar(100) NOT NULL DEFAULT '' COMMENT 'tags',
  `art_class` varchar(255) NOT NULL DEFAULT '' COMMENT '扩展分类',
  `art_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '主图',
  `art_pic_thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `art_pic_slide` varchar(255) NOT NULL DEFAULT '' COMMENT '幻灯图',
  `art_blurb` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `art_remarks` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `art_jumpurl` varchar(150) NOT NULL DEFAULT '' COMMENT '跳转url',
  `art_tpl` varchar(30) NOT NULL DEFAULT '' COMMENT '独立模板',
  `art_level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '推荐等级',
  `art_lock` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '锁定',
  `art_points` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '访问整个文章所需点数',
  `art_points_detail` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '访问每一页所需点数',
  `art_up` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '顶数',
  `art_down` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '踩数',
  `art_hits` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总点击量',
  `art_hits_day` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '日点击量',
  `art_hits_week` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '周点击量',
  `art_hits_month` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '月点击量',
  `art_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `art_time_add` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `art_time_hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击时间',
  `art_time_make` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '生成时间',
  `art_score` decimal(3,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '平均分',
  `art_score_all` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总评分',
  `art_score_num` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '评分次数',
  `art_rel_art` varchar(255) NOT NULL DEFAULT '' COMMENT '关联文章',
  `art_rel_vod` varchar(255) NOT NULL DEFAULT '' COMMENT '关联视频',
  `art_pwd` varchar(10) NOT NULL DEFAULT '' COMMENT '访问密码',
  `art_pwd_url` varchar(255) NOT NULL DEFAULT '' COMMENT '密码获取链接',
  `art_title` mediumtext NOT NULL COMMENT '页标题',
  `art_note` mediumtext NOT NULL COMMENT '页备注',
  `art_content` mediumtext NOT NULL COMMENT '页详细介绍',
  PRIMARY KEY (`art_id`),
  KEY `type_id` (`type_id`) USING BTREE,
  KEY `type_id_1` (`type_id_1`) USING BTREE,
  KEY `art_level` (`art_level`) USING BTREE,
  KEY `art_hits` (`art_hits`) USING BTREE,
  KEY `art_time` (`art_time`) USING BTREE,
  KEY `art_letter` (`art_letter`) USING BTREE,
  KEY `art_down` (`art_down`) USING BTREE,
  KEY `art_up` (`art_up`) USING BTREE,
  KEY `art_tag` (`art_tag`) USING BTREE,
  KEY `art_name` (`art_name`) USING BTREE,
  KEY `art_enn` (`art_en`) USING BTREE,
  KEY `art_hits_day` (`art_hits_day`) USING BTREE,
  KEY `art_hits_week` (`art_hits_week`) USING BTREE,
  KEY `art_hits_month` (`art_hits_month`) USING BTREE,
  KEY `art_time_add` (`art_time_add`) USING BTREE,
  KEY `art_time_make` (`art_time_make`) USING BTREE,
  KEY `art_lock` (`art_lock`),
  KEY `art_score` (`art_score`),
  KEY `art_score_all` (`art_score_all`),
  KEY `art_score_num` (`art_score_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章管理';



# Dump of table card
# ------------------------------------------------------------

CREATE TABLE `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_no` varchar(16) NOT NULL DEFAULT '' COMMENT '卡号',
  `card_pwd` varchar(8) NOT NULL DEFAULT '' COMMENT '密码',
  `card_money` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '面值',
  `card_points` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `card_use_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '使用状态',
  `card_sale_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '出售状态',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `card_add_time` int(10) unsigned NOT NULL DEFAULT '0',
  `card_use_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用时间',
  PRIMARY KEY (`card_id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `card_add_time` (`card_add_time`) USING BTREE,
  KEY `card_use_time` (`card_use_time`) USING BTREE,
  KEY `card_no` (`card_no`),
  KEY `card_pwd` (`card_pwd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值卡';



# Dump of table cash
# ------------------------------------------------------------

CREATE TABLE `cash` (
  `cash_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `cash_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态',
  `cash_points` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `cash_money` decimal(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '金额',
  `cash_bank_name` varchar(60) NOT NULL DEFAULT '' COMMENT '银行',
  `cash_bank_no` varchar(30) NOT NULL DEFAULT '' COMMENT '账号',
  `cash_payee_name` varchar(30) NOT NULL DEFAULT '' COMMENT '姓名',
  `cash_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '时间',
  `cash_time_audit` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  PRIMARY KEY (`cash_id`),
  KEY `user_id` (`user_id`),
  KEY `cash_status` (`cash_status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现记录';



# Dump of table cj_content
# ------------------------------------------------------------

CREATE TABLE `cj_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nodeid` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `url` char(255) NOT NULL,
  `title` char(100) NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `nodeid` (`nodeid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table cj_history
# ------------------------------------------------------------

CREATE TABLE `cj_history` (
  `md5` char(32) NOT NULL,
  PRIMARY KEY (`md5`),
  KEY `md5` (`md5`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table cj_node
# ------------------------------------------------------------

CREATE TABLE `cj_node` (
  `nodeid` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `lastdate` int(10) unsigned NOT NULL DEFAULT '0',
  `sourcecharset` varchar(8) NOT NULL DEFAULT '' COMMENT '目标编码 GBK、UTF-8、BIG5',
  `sourcetype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '网址类型 1、序列网站 2、多个网页 3、单一网页',
  `urlpage` text NOT NULL COMMENT '采集网址',
  `pagesize_start` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '页码配置',
  `pagesize_end` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '页码配置',
  `page_base` char(255) NOT NULL DEFAULT '' COMMENT '页码配置 每次增加',
  `par_num` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `url_contain` char(100) NOT NULL DEFAULT '' COMMENT '网址配置：网址中必须包含XX',
  `url_except` char(100) NOT NULL DEFAULT '' COMMENT '网址配置：网址中不得包XX',
  `url_start` char(100) NOT NULL DEFAULT '' COMMENT '采集区间',
  `url_end` char(100) NOT NULL DEFAULT '' COMMENT '采集区间',
  `title_rule` char(100) NOT NULL DEFAULT '' COMMENT '标题规则 匹配规则',
  `title_html_rule` text NOT NULL COMMENT '标题规则 过滤规则 <p([^>]*)>(.*)</p>[|]',
  `type_rule` char(100) NOT NULL DEFAULT '' COMMENT '分类规则 匹配规则',
  `type_html_rule` text NOT NULL COMMENT '分类规则 过滤规则 <p([^>]*)>(.*)</p>[|]',
  `content_rule` char(100) NOT NULL DEFAULT '' COMMENT '内容规则 匹配规则',
  `content_html_rule` text NOT NULL COMMENT '内容规则 过滤规则',
  `content_page_start` char(100) NOT NULL,
  `content_page_end` char(100) NOT NULL,
  `content_page_rule` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `content_page` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `content_nextpage` char(100) NOT NULL,
  `down_attachment` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `watermark` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `coll_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `customize_config` text NOT NULL COMMENT '自定义规则',
  `program_config` text NOT NULL,
  `mid` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '采集模块 1：视频 2：文章',
  PRIMARY KEY (`nodeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='采集-自定义规则';



# Dump of table collect
# ------------------------------------------------------------

CREATE TABLE `collect` (
  `collect_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `collect_name` varchar(30) NOT NULL DEFAULT '',
  `collect_url` varchar(255) NOT NULL DEFAULT '',
  `collect_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '接口类型 1、xml 2、json',
  `collect_mid` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '资源类型 1、视频 2、文章 3、演员 4、角色 5、网址',
  `collect_appid` varchar(30) NOT NULL DEFAULT '',
  `collect_appkey` varchar(30) NOT NULL DEFAULT '',
  `collect_param` varchar(100) NOT NULL DEFAULT '',
  `collect_filter` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '地址过滤 0、不过滤 1、新增+更新 2、新增 3、更新',
  `collect_filter_from` varchar(255) NOT NULL DEFAULT '' COMMENT '过滤代码',
  `collect_opt` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '数据操作 0、新增+更新 1、新增 2、更新 如果某个资源作为副资源不想新增数据，可以只勾选更新',
  PRIMARY KEY (`collect_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自定义资源';



# Dump of table comment
# ------------------------------------------------------------

CREATE TABLE `comment` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comment_mid` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '模块id，1视频2文字3专题',
  `comment_rid` int(10) unsigned NOT NULL DEFAULT '0',
  `comment_pid` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `comment_status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态0未审核1已审核',
  `comment_name` varchar(60) NOT NULL DEFAULT '' COMMENT '昵称',
  `comment_ip` int(10) unsigned NOT NULL DEFAULT '0',
  `comment_time` int(10) unsigned NOT NULL DEFAULT '0',
  `comment_content` varchar(255) NOT NULL DEFAULT '',
  `comment_up` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '顶数',
  `comment_down` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '踩数',
  `comment_reply` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `comment_report` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '举报',
  PRIMARY KEY (`comment_id`),
  KEY `comment_mid` (`comment_mid`) USING BTREE,
  KEY `comment_rid` (`comment_rid`) USING BTREE,
  KEY `comment_time` (`comment_time`) USING BTREE,
  KEY `comment_pid` (`comment_pid`),
  KEY `user_id` (`user_id`),
  KEY `comment_reply` (`comment_reply`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评论';



# Dump of table gbook
# ------------------------------------------------------------

CREATE TABLE `gbook` (
  `gbook_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gbook_rid` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `gbook_status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态0未审核1已审核',
  `gbook_name` varchar(60) NOT NULL DEFAULT '' COMMENT '昵称',
  `gbook_ip` int(10) unsigned NOT NULL DEFAULT '0',
  `gbook_time` int(10) unsigned NOT NULL DEFAULT '0',
  `gbook_reply_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复时间',
  `gbook_content` varchar(255) NOT NULL DEFAULT '' COMMENT '留言内容',
  `gbook_reply` varchar(255) NOT NULL DEFAULT '' COMMENT '回复内容',
  PRIMARY KEY (`gbook_id`),
  KEY `gbook_rid` (`gbook_rid`) USING BTREE,
  KEY `gbook_time` (`gbook_time`) USING BTREE,
  KEY `gbook_reply_time` (`gbook_reply_time`) USING BTREE,
  KEY `user_id` (`user_id`),
  KEY `gbook_reply` (`gbook_reply`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='留言板';



# Dump of table group
# ------------------------------------------------------------

CREATE TABLE `group` (
  `group_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(30) NOT NULL DEFAULT '',
  `group_status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `group_type` text NOT NULL COMMENT '可以访问的一级分类id',
  `group_popedom` text NOT NULL COMMENT '可以访问的一级分类id及子页面权限',
  `group_points_day` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '包天价格',
  `group_points_week` smallint(6) NOT NULL DEFAULT '0' COMMENT '包周价格',
  `group_points_month` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '包月价格',
  `group_points_year` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '包年价格',
  `group_points_free` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0',
  PRIMARY KEY (`group_id`),
  KEY `group_status` (`group_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员组';



# Dump of table link
# ------------------------------------------------------------

CREATE TABLE `link` (
  `link_id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `link_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型0文字1图片',
  `link_name` varchar(60) NOT NULL DEFAULT '' COMMENT '名称',
  `link_sort` smallint(6) NOT NULL DEFAULT '0' COMMENT '排序',
  `link_add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `link_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `link_logo` varchar(255) NOT NULL DEFAULT '' COMMENT '图标',
  PRIMARY KEY (`link_id`),
  KEY `link_sort` (`link_sort`) USING BTREE,
  KEY `link_type` (`link_type`) USING BTREE,
  KEY `link_add_time` (`link_add_time`),
  KEY `link_time` (`link_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='友情链接列表';



# Dump of table msg
# ------------------------------------------------------------

CREATE TABLE `msg` (
  `msg_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `msg_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `msg_to` varchar(30) NOT NULL DEFAULT '',
  `msg_code` varchar(10) NOT NULL DEFAULT '',
  `msg_content` varchar(255) NOT NULL DEFAULT '',
  `msg_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`msg_id`),
  KEY `msg_code` (`msg_code`),
  KEY `msg_time` (`msg_time`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table order
# ------------------------------------------------------------

CREATE TABLE `order` (
  `order_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `order_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0、未支付 1、已支付',
  `order_code` varchar(30) NOT NULL DEFAULT '' COMMENT '单号',
  `order_price` decimal(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `order_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下单时间',
  `order_points` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `order_pay_type` varchar(10) NOT NULL DEFAULT '' COMMENT '支付类型',
  `order_pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `order_remarks` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`order_id`),
  KEY `order_code` (`order_code`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `order_time` (`order_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table plog
# ------------------------------------------------------------

CREATE TABLE `plog` (
  `plog_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id_1` int(10) NOT NULL DEFAULT '0',
  `plog_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1、积分充值 2、注册推广 3、访问推广 4、三季分销 5、积分升级 6、积分消费 7、积分提现',
  `plog_points` smallint(6) unsigned NOT NULL DEFAULT '0',
  `plog_time` int(10) unsigned NOT NULL DEFAULT '0',
  `plog_remarks` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`plog_id`),
  KEY `user_id` (`user_id`),
  KEY `plog_type` (`plog_type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分日志';



# Dump of table role
# ------------------------------------------------------------

CREATE TABLE `role` (
  `role_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_rid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联视频id',
  `role_name` varchar(255) NOT NULL DEFAULT '' COMMENT '角色名',
  `role_en` varchar(255) NOT NULL DEFAULT '' COMMENT '拼音',
  `role_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `role_lock` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '锁定',
  `role_letter` char(1) NOT NULL DEFAULT '' COMMENT '首字母',
  `role_color` varchar(6) NOT NULL DEFAULT '' COMMENT '高亮颜色',
  `role_actor` varchar(255) NOT NULL DEFAULT '' COMMENT '演员名称',
  `role_remarks` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `role_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `role_sort` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `role_level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '推荐值',
  `role_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `role_time_add` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `role_time_hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击时间',
  `role_time_make` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '生成时间',
  `role_hits` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `role_hits_day` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `role_hits_week` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `role_hits_month` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `role_score` decimal(3,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '平均分',
  `role_score_all` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总评分',
  `role_score_num` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '评分次数',
  `role_up` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '顶数',
  `role_down` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '踩数',
  `role_tpl` varchar(30) NOT NULL DEFAULT '' COMMENT '自定义模板',
  `role_jumpurl` varchar(150) NOT NULL DEFAULT '' COMMENT '跳转url',
  `role_content` text NOT NULL COMMENT '详情',
  PRIMARY KEY (`role_id`),
  KEY `role_rid` (`role_rid`),
  KEY `role_name` (`role_name`),
  KEY `role_en` (`role_en`),
  KEY `role_letter` (`role_letter`),
  KEY `role_actor` (`role_actor`),
  KEY `role_level` (`role_level`),
  KEY `role_time` (`role_time`),
  KEY `role_time_add` (`role_time_add`),
  KEY `role_score` (`role_score`),
  KEY `role_score_all` (`role_score_all`),
  KEY `role_score_num` (`role_score_num`),
  KEY `role_up` (`role_up`),
  KEY `role_down` (`role_down`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色';



# Dump of table tmpart
# ------------------------------------------------------------

CREATE TABLE `tmpart` (
  `id1` int(10) unsigned DEFAULT NULL,
  `name1` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table tmpwebsite
# ------------------------------------------------------------

CREATE TABLE `tmpwebsite` (
  `id1` int(10) unsigned DEFAULT NULL,
  `name1` varchar(60) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table topic
# ------------------------------------------------------------

CREATE TABLE `topic` (
  `topic_id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `topic_name` varchar(255) NOT NULL DEFAULT '' COMMENT '标题名称',
  `topic_en` varchar(255) NOT NULL DEFAULT '' COMMENT '别名',
  `topic_sub` varchar(255) NOT NULL DEFAULT '' COMMENT '副标题',
  `topic_status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `topic_sort` smallint(6) unsigned NOT NULL DEFAULT '0',
  `topic_letter` char(1) NOT NULL DEFAULT '' COMMENT '首字母',
  `topic_color` varchar(6) NOT NULL DEFAULT '' COMMENT '高亮颜色',
  `topic_tpl` varchar(30) NOT NULL DEFAULT '' COMMENT '模板文件',
  `topic_type` varchar(255) NOT NULL DEFAULT '' COMMENT '扩展分类',
  `topic_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `topic_pic_thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `topic_pic_slide` varchar(255) NOT NULL DEFAULT '' COMMENT '幻灯图',
  `topic_key` varchar(255) NOT NULL DEFAULT '' COMMENT 'seo关键字',
  `topic_des` varchar(255) NOT NULL DEFAULT '' COMMENT 'seo描述',
  `topic_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'seo标题',
  `topic_blurb` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `topic_remarks` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `topic_level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '推荐值',
  `topic_up` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '顶数',
  `topic_down` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '踩数',
  `topic_score` decimal(3,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '平均分',
  `topic_score_all` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总评分',
  `topic_score_num` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总评次',
  `topic_hits` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总点击',
  `topic_hits_day` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '日点击',
  `topic_hits_week` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '周点击',
  `topic_hits_month` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '月点击',
  `topic_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `topic_time_add` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `topic_time_hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击时间',
  `topic_time_make` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览模式为静态模式时静态页面生成时间，以此判断页面是否可浏览',
  `topic_tag` varchar(255) NOT NULL DEFAULT '' COMMENT 'tag收录 自动收录包含tag的视频和文章；’,‘分隔',
  `topic_rel_vod` text COMMENT '关联视频id ’,‘分隔',
  `topic_rel_art` text COMMENT '关联文章id ’,‘分隔',
  `topic_content` text COMMENT '详细介绍',
  `topic_extend` text COMMENT '扩展配置',
  PRIMARY KEY (`topic_id`),
  KEY `topic_sort` (`topic_sort`) USING BTREE,
  KEY `topic_level` (`topic_level`) USING BTREE,
  KEY `topic_score` (`topic_score`) USING BTREE,
  KEY `topic_score_all` (`topic_score_all`) USING BTREE,
  KEY `topic_score_num` (`topic_score_num`) USING BTREE,
  KEY `topic_hits` (`topic_hits`) USING BTREE,
  KEY `topic_hits_day` (`topic_hits_day`) USING BTREE,
  KEY `topic_hits_week` (`topic_hits_week`) USING BTREE,
  KEY `topic_hits_month` (`topic_hits_month`) USING BTREE,
  KEY `topic_time_add` (`topic_time_add`) USING BTREE,
  KEY `topic_time` (`topic_time`) USING BTREE,
  KEY `topic_time_hits` (`topic_time_hits`) USING BTREE,
  KEY `topic_name` (`topic_name`),
  KEY `topic_en` (`topic_en`),
  KEY `topic_up` (`topic_up`),
  KEY `topic_down` (`topic_down`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='专题';



# Dump of table type
# ------------------------------------------------------------

CREATE TABLE `type` (
  `type_id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(60) NOT NULL DEFAULT '',
  `type_en` varchar(60) NOT NULL DEFAULT '' COMMENT '别名',
  `type_sort` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '排序号',
  `type_mid` smallint(6) unsigned NOT NULL DEFAULT '1' COMMENT '所属模块',
  `type_pid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '父分类ID',
  `type_status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态 1：开启 0：关闭',
  `type_tpl` varchar(30) NOT NULL DEFAULT '' COMMENT '分类页模板',
  `type_tpl_list` varchar(30) NOT NULL DEFAULT '' COMMENT '筛选页模板',
  `type_tpl_detail` varchar(30) NOT NULL DEFAULT '' COMMENT '详情页模板',
  `type_tpl_play` varchar(30) NOT NULL DEFAULT '' COMMENT '播放页模板',
  `type_tpl_down` varchar(30) NOT NULL DEFAULT '' COMMENT '下载页模板',
  `type_key` varchar(255) NOT NULL DEFAULT '' COMMENT 'seo关键字',
  `type_des` varchar(255) NOT NULL DEFAULT '' COMMENT 'seo描述信息',
  `type_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'seo标题',
  `type_union` varchar(255) NOT NULL DEFAULT '',
  `type_extend` text COMMENT '扩展配置json',
  `type_logo` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图标',
  `type_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '分类封面',
  `type_jumpurl` varchar(150) NOT NULL DEFAULT '' COMMENT '跳转链接',
  PRIMARY KEY (`type_id`),
  KEY `type_sort` (`type_sort`) USING BTREE,
  KEY `type_pid` (`type_pid`) USING BTREE,
  KEY `type_name` (`type_name`),
  KEY `type_en` (`type_en`),
  KEY `type_mid` (`type_mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频分类';



# Dump of table ulog
# ------------------------------------------------------------

CREATE TABLE `ulog` (
  `ulog_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ulog_mid` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ulog_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `ulog_rid` int(10) unsigned NOT NULL DEFAULT '0',
  `ulog_sid` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ulog_nid` smallint(6) unsigned NOT NULL DEFAULT '0',
  `ulog_points` smallint(6) unsigned NOT NULL DEFAULT '0',
  `ulog_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ulog_id`),
  KEY `user_id` (`user_id`),
  KEY `ulog_mid` (`ulog_mid`),
  KEY `ulog_type` (`ulog_type`),
  KEY `ulog_rid` (`ulog_rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table user
# ------------------------------------------------------------

CREATE TABLE `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` smallint(6) unsigned NOT NULL DEFAULT '0',
  `user_name` varchar(30) NOT NULL DEFAULT '',
  `user_pwd` varchar(32) NOT NULL DEFAULT '',
  `user_nick_name` varchar(30) NOT NULL DEFAULT '',
  `user_qq` varchar(16) NOT NULL DEFAULT '',
  `user_email` varchar(30) NOT NULL DEFAULT '',
  `user_phone` varchar(16) NOT NULL DEFAULT '',
  `user_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `user_portrait` varchar(100) NOT NULL DEFAULT '' COMMENT '头像',
  `user_portrait_thumb` varchar(100) NOT NULL DEFAULT '',
  `user_openid_qq` varchar(40) NOT NULL DEFAULT '',
  `user_openid_weixin` varchar(40) NOT NULL DEFAULT '',
  `user_question` varchar(255) NOT NULL DEFAULT '',
  `user_answer` varchar(255) NOT NULL DEFAULT '',
  `user_points` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `user_points_froze` int(10) unsigned NOT NULL DEFAULT '0',
  `user_reg_time` int(10) unsigned NOT NULL DEFAULT '0',
  `user_reg_ip` int(10) unsigned NOT NULL DEFAULT '0',
  `user_login_time` int(10) unsigned NOT NULL DEFAULT '0',
  `user_login_ip` int(10) unsigned NOT NULL DEFAULT '0',
  `user_last_login_time` int(10) unsigned NOT NULL DEFAULT '0',
  `user_last_login_ip` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上次登录ip',
  `user_login_num` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `user_extend` smallint(6) unsigned NOT NULL DEFAULT '0',
  `user_random` varchar(32) NOT NULL DEFAULT '',
  `user_end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'vip截止期限',
  `user_pid` int(10) unsigned NOT NULL DEFAULT '0',
  `user_pid_2` int(10) unsigned NOT NULL DEFAULT '0',
  `user_pid_3` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  KEY `type_id` (`group_id`) USING BTREE,
  KEY `user_name` (`user_name`),
  KEY `user_reg_time` (`user_reg_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';



# Dump of table visit
# ------------------------------------------------------------

CREATE TABLE `visit` (
  `visit_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT '0' COMMENT 'url参数ID',
  `visit_ip` int(10) unsigned NOT NULL DEFAULT '0',
  `visit_ly` varchar(100) NOT NULL DEFAULT '' COMMENT 'refer',
  `visit_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`visit_id`),
  KEY `user_id` (`user_id`),
  KEY `visit_time` (`visit_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推广记录';



# Dump of table vod
# ------------------------------------------------------------

CREATE TABLE `vod` (
  `vod_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` smallint(6) NOT NULL DEFAULT '0' COMMENT '视频分类',
  `type_id_1` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '视频一级分类id',
  `group_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '用户组id',
  `vod_name` varchar(255) NOT NULL DEFAULT '' COMMENT '视频标题',
  `vod_sub` varchar(255) NOT NULL DEFAULT '' COMMENT '视频副标题',
  `vod_en` varchar(255) NOT NULL DEFAULT '' COMMENT '视频别名 如：拼音、英文名',
  `vod_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态 0：未审核、1：已审核',
  `vod_letter` char(1) NOT NULL DEFAULT '' COMMENT '视频首字母',
  `vod_color` varchar(6) NOT NULL DEFAULT '' COMMENT '高亮颜色',
  `vod_tag` varchar(100) NOT NULL DEFAULT '' COMMENT '视频标签',
  `vod_class` varchar(255) NOT NULL DEFAULT '' COMMENT '扩展分类 如：国产,网剧',
  `vod_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '视频图片',
  `vod_pic_thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '视频缩略图',
  `vod_pic_slide` varchar(255) NOT NULL DEFAULT '' COMMENT '视频海报图',
  `vod_actor` varchar(255) NOT NULL DEFAULT '' COMMENT '主演列表',
  `vod_director` varchar(255) NOT NULL DEFAULT '' COMMENT '导演',
  `vod_writer` varchar(100) NOT NULL DEFAULT '' COMMENT '编剧',
  `vod_behind` varchar(100) NOT NULL DEFAULT '' COMMENT '幕后',
  `vod_blurb` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `vod_remarks` varchar(100) NOT NULL DEFAULT '' COMMENT '备注 如：完结',
  `vod_pubdate` varchar(100) NOT NULL DEFAULT '' COMMENT '上映日期',
  `vod_total` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总集数',
  `vod_serial` varchar(20) NOT NULL DEFAULT '0' COMMENT '连载数',
  `vod_tv` varchar(30) NOT NULL DEFAULT '' COMMENT '电视频道',
  `vod_weekday` varchar(30) NOT NULL DEFAULT '' COMMENT '节目周期',
  `vod_area` varchar(20) NOT NULL DEFAULT '' COMMENT '发行地区 如：大陆,香港',
  `vod_lang` varchar(10) NOT NULL DEFAULT '' COMMENT '对白语言',
  `vod_year` varchar(10) NOT NULL DEFAULT '' COMMENT '上映年代 如：2019',
  `vod_version` varchar(30) NOT NULL DEFAULT '' COMMENT '影片版本 如：高清版,TV',
  `vod_state` varchar(30) NOT NULL DEFAULT '' COMMENT '资源类别 如：正片,预告片',
  `vod_author` varchar(60) NOT NULL DEFAULT '' COMMENT '编辑人',
  `vod_jumpurl` varchar(150) NOT NULL DEFAULT '' COMMENT '跳转URL',
  `vod_tpl` varchar(30) NOT NULL DEFAULT '' COMMENT '内容页模板',
  `vod_tpl_play` varchar(30) NOT NULL DEFAULT '' COMMENT '播放页模板',
  `vod_tpl_down` varchar(30) NOT NULL DEFAULT '' COMMENT '下载页模板',
  `vod_isend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已完结，是否连载完毕',
  `vod_lock` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否锁定',
  `vod_level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '推荐级别',
  `vod_copyright` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启版权提示',
  `vod_points` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '访问整个视频所需积分',
  `vod_points_play` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '每集点播付费',
  `vod_points_down` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '每集下载付费',
  `vod_hits` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总点击量',
  `vod_hits_day` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '日点击量',
  `vod_hits_week` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '周点击量',
  `vod_hits_month` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '月点击量',
  `vod_duration` varchar(10) NOT NULL DEFAULT '' COMMENT '时长',
  `vod_up` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '顶数',
  `vod_down` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '踩数',
  `vod_score` decimal(3,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '平均分',
  `vod_score_all` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总评分',
  `vod_score_num` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '评分次数',
  `vod_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `vod_time_add` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `vod_time_hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击时间',
  `vod_time_make` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '生成时间',
  `vod_trysee` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '试看时长 单位（分钟）',
  `vod_douban_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '豆瓣ID',
  `vod_douban_score` decimal(3,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '豆瓣评分',
  `vod_reurl` varchar(255) NOT NULL DEFAULT '' COMMENT '来源地址',
  `vod_rel_vod` varchar(255) NOT NULL DEFAULT '' COMMENT '关联视频ids',
  `vod_rel_art` varchar(255) NOT NULL DEFAULT '' COMMENT '关联文章ids',
  `vod_pwd` varchar(10) NOT NULL DEFAULT '' COMMENT '访问内容页密码',
  `vod_pwd_url` varchar(255) NOT NULL DEFAULT '' COMMENT '获取密码链接',
  `vod_pwd_play` varchar(10) NOT NULL DEFAULT '' COMMENT '访问播放页密码',
  `vod_pwd_play_url` varchar(255) NOT NULL DEFAULT '' COMMENT '获取密码链接',
  `vod_pwd_down` varchar(10) NOT NULL DEFAULT '' COMMENT '访问下载页密码',
  `vod_pwd_down_url` varchar(255) NOT NULL DEFAULT '' COMMENT '获取密码链接',
  `vod_content` text NOT NULL COMMENT '详细介绍',
  `vod_play_from` varchar(255) NOT NULL DEFAULT '' COMMENT '播放组',
  `vod_play_server` varchar(255) NOT NULL DEFAULT '' COMMENT '播放服务器组',
  `vod_play_note` varchar(255) NOT NULL DEFAULT '' COMMENT '播放备注',
  `vod_play_url` mediumtext NOT NULL COMMENT '播放地址',
  `vod_down_from` varchar(255) NOT NULL DEFAULT '' COMMENT '下载组',
  `vod_down_server` varchar(255) NOT NULL DEFAULT '' COMMENT '下载服务器组',
  `vod_down_note` varchar(255) NOT NULL DEFAULT '' COMMENT '下载备注',
  `vod_down_url` mediumtext NOT NULL COMMENT '下载地址',
  `vod_plot` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否包含分集剧情',
  `vod_plot_name` mediumtext NOT NULL COMMENT '分集剧情名称',
  `vod_plot_detail` mediumtext NOT NULL COMMENT '分集剧情详情',
  PRIMARY KEY (`vod_id`),
  KEY `type_id` (`type_id`) USING BTREE,
  KEY `type_id_1` (`type_id_1`) USING BTREE,
  KEY `vod_level` (`vod_level`) USING BTREE,
  KEY `vod_hits` (`vod_hits`) USING BTREE,
  KEY `vod_letter` (`vod_letter`) USING BTREE,
  KEY `vod_name` (`vod_name`) USING BTREE,
  KEY `vod_year` (`vod_year`) USING BTREE,
  KEY `vod_area` (`vod_area`) USING BTREE,
  KEY `vod_lang` (`vod_lang`) USING BTREE,
  KEY `vod_tag` (`vod_tag`) USING BTREE,
  KEY `vod_class` (`vod_class`) USING BTREE,
  KEY `vod_lock` (`vod_lock`) USING BTREE,
  KEY `vod_up` (`vod_up`) USING BTREE,
  KEY `vod_down` (`vod_down`) USING BTREE,
  KEY `vod_en` (`vod_en`) USING BTREE,
  KEY `vod_hits_day` (`vod_hits_day`) USING BTREE,
  KEY `vod_hits_week` (`vod_hits_week`) USING BTREE,
  KEY `vod_hits_month` (`vod_hits_month`) USING BTREE,
  KEY `vod_plot` (`vod_plot`) USING BTREE,
  KEY `vod_points_play` (`vod_points_play`) USING BTREE,
  KEY `vod_points_down` (`vod_points_down`) USING BTREE,
  KEY `group_id` (`group_id`) USING BTREE,
  KEY `vod_time_add` (`vod_time_add`) USING BTREE,
  KEY `vod_time` (`vod_time`) USING BTREE,
  KEY `vod_time_make` (`vod_time_make`) USING BTREE,
  KEY `vod_actor` (`vod_actor`) USING BTREE,
  KEY `vod_director` (`vod_director`) USING BTREE,
  KEY `vod_score_all` (`vod_score_all`) USING BTREE,
  KEY `vod_score_num` (`vod_score_num`) USING BTREE,
  KEY `vod_total` (`vod_total`) USING BTREE,
  KEY `vod_score` (`vod_score`) USING BTREE,
  KEY `vod_version` (`vod_version`),
  KEY `vod_state` (`vod_state`),
  KEY `vod_isend` (`vod_isend`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频列表';



# Dump of table website
# ------------------------------------------------------------

CREATE TABLE `website` (
  `website_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `type_id_1` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '一级分类id',
  `website_name` varchar(60) NOT NULL DEFAULT '' COMMENT '网址名',
  `website_sub` varchar(255) NOT NULL DEFAULT '' COMMENT '副标',
  `website_en` varchar(255) NOT NULL DEFAULT '' COMMENT '拼音',
  `website_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `website_letter` char(1) NOT NULL DEFAULT '' COMMENT '首字母',
  `website_color` varchar(6) NOT NULL DEFAULT '' COMMENT '高亮颜色',
  `website_lock` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '锁定',
  `website_sort` int(10) NOT NULL DEFAULT '0' COMMENT '排序',
  `website_jumpurl` varchar(255) NOT NULL DEFAULT '' COMMENT '跳转url',
  `website_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '截图',
  `website_logo` varchar(255) NOT NULL DEFAULT '' COMMENT 'logo',
  `website_area` varchar(20) NOT NULL DEFAULT '' COMMENT '地区',
  `website_lang` varchar(10) NOT NULL DEFAULT '' COMMENT '语言',
  `website_level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '推荐值',
  `website_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `website_time_add` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `website_time_hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击时间',
  `website_time_make` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '生成时间',
  `website_hits` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `website_hits_day` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `website_hits_week` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `website_hits_month` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `website_score` decimal(3,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '平均分',
  `website_score_all` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总评分',
  `website_score_num` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '评分次数',
  `website_up` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '顶数',
  `website_down` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '踩数',
  `website_referer` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总来路',
  `website_referer_day` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '日来路',
  `website_referer_week` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '周来路',
  `website_referer_month` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '月来路',
  `website_tag` varchar(100) NOT NULL DEFAULT '' COMMENT 'tags',
  `website_class` varchar(255) NOT NULL DEFAULT '' COMMENT '扩展分类',
  `website_remarks` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `website_tpl` varchar(30) NOT NULL DEFAULT '' COMMENT '自定义模板',
  `website_blurb` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `website_content` mediumtext NOT NULL COMMENT '详情',
  PRIMARY KEY (`website_id`),
  KEY `type_id` (`type_id`),
  KEY `type_id_1` (`type_id_1`),
  KEY `website_name` (`website_name`),
  KEY `website_en` (`website_en`),
  KEY `website_letter` (`website_letter`),
  KEY `website_sort` (`website_sort`),
  KEY `website_lock` (`website_lock`),
  KEY `website_time` (`website_time`),
  KEY `website_time_add` (`website_time_add`),
  KEY `website_hits` (`website_hits`),
  KEY `website_hits_day` (`website_hits_day`),
  KEY `website_hits_week` (`website_hits_week`),
  KEY `website_hits_month` (`website_hits_month`),
  KEY `website_time_make` (`website_time_make`),
  KEY `website_score` (`website_score`),
  KEY `website_score_all` (`website_score_all`),
  KEY `website_score_num` (`website_score_num`),
  KEY `website_up` (`website_up`),
  KEY `website_down` (`website_down`),
  KEY `website_level` (`website_level`),
  KEY `website_tag` (`website_tag`),
  KEY `website_class` (`website_class`),
  KEY `website_referer` (`website_referer`),
  KEY `website_referer_day` (`website_referer_day`),
  KEY `website_referer_week` (`website_referer_week`),
  KEY `website_referer_month` (`website_referer_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网址列表';




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
