<?php
namespace app\admin\controller;
use think\Db;

class Task extends Base
{
    public function __construct()
    {
        parent::__construct();
    }
    public function index()
    {
        $param = input();
        $param['page'] = intval($param['page']) <1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) <1 ? $this->_pagesize : $param['limit'];

        $where=[];
        if(in_array($param['status'],['0','1'],true)){
            $where['status'] = ['eq',$param['status']];
        }
        if(isset($param['ad_user_id']) && $param['ad_user_id'] >0){
            $where['ad_user_id'] = ['eq',$param['ad_user_id']];
        }
        if(!empty($param['task_date'])){
            $param['task_date'] = htmlspecialchars(urldecode($param['task_date']));
            $where['task_date'] = ['eq',$param['task_date']];
        }
        $list_admin_role= model('Task')->getAdminRoletextAttr();
//        p($list_admin_role);

        $order='id desc';
        $res = model('Task')->listData($where,$order,$param['page'],$param['limit']);
        $this->assign('list',$res['list']);
        $this->assign('total',$res['total']);
        $this->assign('page',$res['page']);
        $this->assign('list_admin_role',$list_admin_role);
        $this->assign('limit',$res['limit']);
        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param',$param);
        $this->assign('title','任务管理');
        return $this->fetch('admin@task/index');
    }

    public function info()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            $res = model('Task')->saveData($param);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        $id = input('id');
        $where=[];
        $where['id'] = ['eq',$id];
        $res = model('task')->infoData($where,$id);
        $this->assign('info',$res['info']);
        return $this->fetch('admin@task/info');
    }

    public function del()
    {
        $param = input();
        $ids = $param['ids'];

        if(!empty($ids)){
            $where=[];
            $where['id'] = ['in',$ids];
            $res = model('Task')->delData($where);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error('参数错误');
    }

    public function field()
    {
        $param = input();
        $ids = $param['ids'];
        $col = $param['col'];
        $val = $param['val'];

        if(!empty($ids) && in_array($col,['topic_status','topic_level']) ){
            $where=[];
            $where['topic_id'] = ['in',$ids];

            $res = model('Topic')->fieldData($where,$col,$val);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error('参数错误');
    }

}
