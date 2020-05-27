<?php

//echo 'ok';/

//composer  require php-ffmpeg/php-ffmpeg

$i = $_GET['i']??'';
$p = $_GET['p']??'';

print_r(getUrls('https://api.douban.com/v2/movie/subject/26759908?apikey=0df993c66c0c636e29ecbb5344252a4a',$i,$p));

//使用代理进行测试 url为使用代理访问的链接，auth_port为代理端口
function getUrls($url,$i,$p)
{

    $ch = curl_init();
    $timeout = 30;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
    curl_setopt($ch, CURLOPT_PROXY, $i); //代理服务器地址
    curl_setopt($ch, CURLOPT_PROXYPORT, $p); //代理服务器端口
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式
    //如果访问为https协议
    if (substr($url, 0, 5) == "https") {
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
    }

    $file_contents = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//    var_dump($httpCode);die;
    return $file_contents;

}





