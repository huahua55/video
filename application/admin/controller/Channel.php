<?php
namespace app\admin\controller;
use think\Db;

class Channel extends Base
{
    public function __construct(){
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

        $order='id desc';
        $res = model('Channel')->listData($where,$order,$param['page'],$param['limit']);

        $this->assign('list',$res['list']);
        $this->assign('total',$res['total']);
        $this->assign('page',$res['page']);
        $this->assign('limit',$res['limit']);


        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param',$param);
        $this->assign('title','渠道配置管理');
        return $this->fetch('admin@channel/index');
    }

    public function info(){
        if (Request()->isPost()) {
            $param = input();
            if(!is_numeric($param['recom_vod'])){
                return $this->error("视频ID错误");
            }
            $res = model('Channel')->saveData($param);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }

        $id = input('id');
        $where=[];
        $where['id'] = ['eq',$id];
        $res = model('Channel')->infoData($where);

        $this->assign('info',$res['info']);

        $this->assign('title','渠道信息');
        return $this->fetch('admin@channel/info');
    }

    public function del()
    {
        $param = input();
        $ids = $param['ids'];

        if(!empty($ids)){
            $where=[];
            $where['id'] = ['in',$ids];
            $res = model('Channel')->delData($where);
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

            $data['recom_vod'] = $param['recom_vod'][$k];
            if(!is_numeric($param['recom_vod'][$k])){
                continue;
            }
            if (empty($data['name'])) {
                $data['name'] = '未知';
            }
            $res = model('Channel')->saveData($data);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
        }
        $this->success($res['msg']);
    }

    public function field(){
        $param = input();
        $ids = $param['ids'];
        $col = $param['col'];
        $val = $param['val'];

        if(!empty($ids) && in_array($col,['states']) && in_array($val,['0','1'])){
            $where=[];
            $where['id'] = ['in',$ids];

            $res = model('Channel')->fieldData($where,$col,$val);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error('参数错误');
    }



}
