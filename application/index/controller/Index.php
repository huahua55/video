<?php
namespace app\index\controller;

class Index extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $param = mac_param_url();

        $this->assign('param',$param);
        return $this->label_fetch('index/index');
    }

    public function test()
    {

    }

}
