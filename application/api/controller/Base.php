<?php
namespace app\api\controller;
use think\Controller;
use app\common\controller\All;

class Base extends All
{
    public $_param;

    public function __construct()
    {
        parent::__construct();

        $this->_param = input();

    }

    public function _empty(){
        header("HTTP/1.0 404 Not Found");
        echo  '请求地址错误';
        exit;
    }

}