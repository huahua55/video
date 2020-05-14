CREATE TABLE IF NOT EXISTS `vod_douban_error` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT '' COMMENT 'title',
  `vod_id` int(11) NOT NULL DEFAULT '0' COMMENT '视频表id',
  `douban_id` int(11) NOT NULL DEFAULT '0' COMMENT '豆瓣id',
  `count` int(11) NOT NULL DEFAULT '0' COMMENT '错误次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `vod_id` (`vod_id`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频错误次数';

CREATE TABLE IF NOT EXISTS `vod_resolving_power` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vod_id` int(11) NOT NULL COMMENT '视频表id',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '视频名称',
  `player` varchar(30) NOT NULL DEFAULT '' COMMENT '播放器',
  `collection` varchar(100) NOT NULL COMMENT '第几集 或者高清',
  `resolution` varchar(255) NOT NULL DEFAULT '' COMMENT '分辨率',
  `code` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1、省流2、高清  3、超清4、蓝光 5、4K',
  `code_name` varchar(30) NOT NULL DEFAULT '' COMMENT '别名',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '视频路径',
  `state` tinyint(2) NOT NULL DEFAULT '1' COMMENT '1 存在 2 删除',
  `text` json DEFAULT NULL COMMENT 'Json',
  PRIMARY KEY (`id`),
  KEY `vod_id` (`vod_id`) USING BTREE,
  KEY `path` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频 播放器 集 分辨率';

CREATE TABLE IF NOT EXISTS `port_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(20) DEFAULT NULL COMMENT 'ip',
  `port` varchar(10) DEFAULT NULL COMMENT '端口',
  `expire_time` varchar(20) DEFAULT NULL COMMENT '失效时间',
  `state` tinyint(2) DEFAULT '1' COMMENT '1 ok 2 失效',
  `type` tinyint(2) DEFAULT '1' COMMENT '1 芝麻代理',
  PRIMARY KEY (`id`),
  KEY `expire_time` (`expire_time`),
  KEY `type` (`type`,`state`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理日志表';

CREATE TABLE `recom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vod_id` int(11) NOT NULL DEFAULT '0' COMMENT '视频ID',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '视频名称',
  `type_id` int(11) NOT NULL DEFAULT '0' COMMENT '分类ID',
  `image` varchar(200) NOT NULL DEFAULT '' COMMENT '视频首针图片',
  `url` text NOT NULL COMMENT '视频地址',
  `sort` int(6) NOT NULL,
  `intro` text NOT NULL COMMENT '视频简介',
  `states` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0开启 1关闭',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '上传时间',
  PRIMARY KEY (`id`),
  KEY `type_id` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推荐短视频';

CREATE TABLE `tmpvod` (
  `id1` int(10) unsigned DEFAULT NULL,
  `name1` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tmpart` (
  `id1` int(10) unsigned DEFAULT NULL,
  `name1` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `channel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '渠道名称',
  `keys` varchar(100) NOT NULL DEFAULT '' COMMENT '客户端名称keys',
  `states` smallint(1) NOT NULL DEFAULT '0' COMMENT '关闭状态 0开启  1关闭',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `recom_vod` int(11) NOT NULL DEFAULT '0' COMMENT '推广视频ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='渠道表';
