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
        $config = $GLOBALS['config']['site'];
        $this->assign($config);

        $this->_param = input();
    }

}