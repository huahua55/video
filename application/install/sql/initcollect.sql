# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 58.216.10.14 (MySQL 5.7.26-log)
# Database: video
# Generation Time: 2020-04-24 01:32:10 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table collect
# ------------------------------------------------------------

LOCK TABLES `collect` WRITE;
/*!40000 ALTER TABLE `collect` DISABLE KEYS */;

INSERT INTO `collect` (`collect_id`, `collect_name`, `collect_url`, `collect_type`, `collect_mid`, `collect_appid`, `collect_appkey`, `collect_param`, `collect_filter`, `collect_filter_from`, `collect_opt`)
VALUES
	(1,'卧龙影视资源站','https://cj.wlzy.tv/api/provide/vod/',2,1,'','','',0,'',0),
	(2,'OK资源网','https://cj.okzy.tv/inc/api1s_subname.php',1,1,'','','',0,'',0),
	(3,'OK资源网迅雷','https://cj.okzy.tv/inc/apidown_subname.php',1,1,'','','&ct=1',0,'',0),
	(4,'135资源迅雷','http://cj.zycjw1.com/inc/api.php',1,1,'','','&ct=1',0,'',0),
	(5,'135资源','http://cj.zycjw1.com/inc/api.php',1,1,'','','',0,'',0),
	(6,'1886资源','http://cj.1886zy.co/inc/api.php',1,1,'','','',0,'',0),
	(7,'1886资源迅雷下载','http://cj.1886zy.co/inc/api.php',1,1,'','','&ct=1',0,'',0),
	(8,'秒播资源站','http://caiji.mb77.vip/inc/api.php',1,1,'','','',0,'',0),
	(9,'秒播资源迅雷站','http://caiji.mb77.vip/inc/api.php',1,1,'','','&ct=1',0,'',0),
	(10,'最大资源站','http://www.zdziyuan.com/inc/api.php',1,1,'','','',0,'',0),
	(11,'最大资源站迅雷','http://www.zdziyuan.com/inc/api.php',1,1,'','','&ct=1',0,'',0);

/*!40000 ALTER TABLE `collect` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
