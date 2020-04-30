<?php


$mysql_server_name = '127.0.0.1'; //改成自己的mysql数据库服务器

$mysql_username = 'uservide'; //改成自己的mysql数据库用户名

$mysql_password = 'CHjduTY79&3CKLp'; //改成自己的mysql数据库密码

$mysql_database = 'video'; //改成自己的mysql数据库名

$conn=mysqli_connect($mysql_server_name,$mysql_username,$mysql_password,$mysql_database); //连接数据库

//连接数据库错误提示
print_r($conn);
if (mysqli_connect_errno($conn)) {

    die("连接 MySQL 失败: " . mysqli_connect_error());

}

mysqli_query($conn,"set names utf8"); //数据库编码格式

// mysqli_set_charset($conn,"utf8");//设置默认客户端字符集。

// mysqli_select_db($conn,$mysql_database); //更改连接的默认数据库

//查询代码

$sql = "select * from art";

$query = mysqli_query($conn,$sql);

while($row = mysqli_fetch_array($query)){

    echo $row['art_id'];

}

//查询代码

// 释放结果集+关闭MySQL数据库连接

//mysqli_free_result($result);

mysqli_close($conn);