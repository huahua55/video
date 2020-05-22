<?php
namespace app\admin\controller;

class Banner extends Base
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

        if(!empty($param['wd'])){
            $param['wd'] = htmlspecialchars(urldecode($param['wd']));
            $where['name'] = ['like','%'.$param['wd'].'%'];
        }
        if($param['type'] == null){
            $param['type'] = '9999';
        }
        if($param['type'] != '9999' && $param['type'] >= 0 && $param['type']!= null){
            $where['type_id'] = ['eq',$param['type']];
        }
        //分类
        $type_tree = model('Type')->getCache('type_tree');
        $type_tree_array=[];
        $type_tree_array['type_id']=0;
        $type_tree_array['type_name']='首页';
        $type_tree_array['type_mid']=1;
        array_unshift($type_tree,$type_tree_array);
//        p($type_tree);die;
        $this->assign('type_tree',$type_tree);

        $order='id desc';
        $res = model('Banner')->listData($where,$order,$param['page'],$param['limit']);
        $this->assign('list',$res['list']);
        $this->assign('total',$res['total']);
        $this->assign('page',$res['page']);
        $this->assign('limit',$res['limit']);

        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param',$param);
        $this->assign('title','轮播配置管理');
        return $this->fetch('admin@banner/index');
    }

    public function info()
    {
        if (Request()->isPost()) {
            $param = input();
            $res = model('Banner')->saveData($param);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }

        $id = input('id');
        $where=[];
        $where['id'] = ['eq',$id];
        $res = model('Banner')->infoData($where);

        $this->assign('info',$res['info']);

        //分类
        $type_tree = model('Type')->getCache('type_tree');
        $this->assign('type_tree',$type_tree);

        $this->assign('title','推荐内容信息');
        return $this->fetch('admin@banner/info');
    }

    public function del()
    {
        $param = input();
        $ids = $param['ids'];

        if(!empty($ids)){
            $where=[];
            $where['id'] = ['in',$ids];
            $res = model('Banner')->delData($where);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error('参数错误');
    }

    public function batch()
    {
        $param = input();
        $ids = $param['ids'];
        foreach ($ids as $k=>$id) {
            $data = [];
            $data['id'] = intval($id);
            $data['name'] = $param['name'][$k];
            $data['sort'] = $param['sort'][$k];
            $data['link'] = $param['link'][$k];

            $res = model('Banner')->saveData($data);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
        }
        $this->success($res['msg']);
    }

    public function field()
    {
        $param = input();
        $ids = $param['ids'];
        $col = $param['col'];
        $val = $param['val'];

        if(!empty($ids) && in_array($col,['status']) && in_array($val,['0','1'])){
            $where=[];
            $where['id'] = ['in',$ids];

            $res = model('Banner')->fieldData($where,$col,$val);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error('参数错误');
    }

}
