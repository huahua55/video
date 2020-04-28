<?php
namespace app\api\controller;

class MyError
{
    public function _empty()
    {
        header("HTTP/1.0 404 Not Found");
        echo  '请求地址错误';
        exit;
    }
}