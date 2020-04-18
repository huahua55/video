<?php
namespace app\admin\controller;

class Jx extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            $param['website_content'] = str_replace( $GLOBALS['config']['upload']['protocol'].':','mac:',$param['website_content']);
            $res = model('Website')->saveData($param);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }

        $param = input();
        $jx_list = [
            'oopw.top' => 'http://api.oopw.top/api.php?url=',
        ];

        $this->assign('title','视频解析');
        return $this->fetch('admin@jx/index');
    }

}
