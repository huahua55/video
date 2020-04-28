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