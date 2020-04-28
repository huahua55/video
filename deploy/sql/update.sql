CREATE TABLE `vod_douban_error` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT '' COMMENT 'title',
  `vod_id` int(11) NOT NULL DEFAULT '0' COMMENT '视频表id',
  `douban_id` int(11) NOT NULL DEFAULT '0' COMMENT '豆瓣id',
  `count` int(11) NOT NULL DEFAULT '0' COMMENT '错误次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `vod_id` (`vod_id`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频错误次数';