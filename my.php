<?php


$mysql_server_name = 'rm-j6c57u1v6upihhwqq.mysql.rds.aliyuncs.com'; //改成自己的mysql数据库服务器
//$mysql_server_name = 'rm-j6c57u1v6upihhwqq6o.mysql.rds.aliyuncs.com'; //改成自己的mysql数据库服务器

$mysql_username = 'uservide'; //改成自己的mysql数据库用户名

$mysql_password = 'CHjduTY793CKLp'; //改成自己的mysql数据库密码

$mysql_database = 'video'; //改成自己的mysql数据库名

$conn=mysqli_connect($mysql_server_name,$mysql_username,$mysql_password,$mysql_database); //连接数据库

//连接数据库错误提示
if (mysqli_connect_errno($conn)) {

    die("连接 MySQL 失败: " . mysqli_connect_error());

}

mysqli_query($conn,"set names utf8"); //数据库编码格式

// mysqli_set_charset($conn,"utf8");//设置默认客户端字符集。

// mysqli_select_db($conn,$mysql_database); //更改连接的默认数据库

//查询代码

$sql = "SELECT `a`.`vod_name`,b.id as b_id,b.examine_id as b_examine_id,b.sum as b_sum,b.is_examine as b_is_examine,b.is_section as b_is_section,b.reason as b_reason,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.down_time as b_down_time,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync FROM `video_vod` `b` LEFT JOIN `vod` `a` ON `b`.`vod_id`=`a`.`vod_id` WHERE  INSTR(`a`.`vod_name`,'张三') > 0  OR `b`.`id` = '张三' ORDER BY `b`.`weight` DESC,`b`.`down_time` DESC LIMIT 0,10";

$query = mysqli_query($conn,$sql);
while($row = mysqli_fetch_array($query)){
    var_dump($row['vod_name']??'');
}

//查询代码

// 释放结果集+关闭MySQL数据库连接

//mysqli_free_result($result);

mysqli_close($conn);