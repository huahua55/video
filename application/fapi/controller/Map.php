<?php
namespace app\fapi\controller;
use think\Controller;

class Map extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        return $this->label_fetch('map/index');
    }

}
