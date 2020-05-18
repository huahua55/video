<?php
namespace app\admin\controller;
use think\Db;

class TypeSeo extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->assign('title','视频分类管理');
    }


    public function index()
    {
        $param = input();
        $key =  $param['domain_key']??0;
        //获取域名
        $domain_conf = config('domain');
        $domain_conf = array_keys($domain_conf);
        $new_table = '';
//        p($key);die;
        if (!isset($domain_conf[$key])){
            return $this->error('打开失败，请联系技术人员');
        }
        foreach ($domain_conf as $k => $v){
            $tableName = $v.'_type';
            $tableName = str_replace('.','_',$tableName);
            if($key == $k){
                $new_table = $tableName;
            }
            //是否存在这张表
            if(isTable($tableName) !=true){
                //复制表
               copyTable($tableName,'type');
            }
        }
        $order='type_sort asc';
        $where=[];
        $model =  new \app\common\model\TypeSeo($new_table);
        $res = $model->listData($where,$order,'tree');
        $list_count =[];
        //视频数量
        $tmp = model('Vod')->field('type_id_1,type_id,count(vod_id) as cc')->where($where)->group('type_id_1,type_id')->select();
        foreach($tmp as $k=>$v){
            $list_count[$v['type_id_1']] += $v['cc'];
            $list_count[$v['type_id']] = $v['cc'];
        }
        //文章数量
        $tmp = model('Art')->field('type_id_1,type_id,count(art_id) as cc')->where($where)->group('type_id_1,type_id')->select();
        foreach($tmp as $k=>$v){
            $list_count[$v['type_id_1']] += $v['cc'];
            $list_count[$v['type_id']] = $v['cc'];
        }

        //演员数量
        $tmp = model('Actor')->field('type_id_1,type_id,count(actor_id) as cc')->where($where)->group('type_id_1,type_id')->select();
        foreach($tmp as $k=>$v){
            $list_count[$v['type_id_1']] += $v['cc'];
            $list_count[$v['type_id']] = $v['cc'];
        }
        //网址数量
        $tmp = model('Website')->field('type_id_1,type_id,count(website_id) as cc')->where($where)->group('type_id_1,type_id')->select();
        foreach($tmp as $k=>$v){
            $list_count[$v['type_id_1']] += $v['cc'];
            $list_count[$v['type_id']] = $v['cc'];
        }

        //重新整合
        foreach($res['list'] as $k=>$v){
            $res['list'][$k]['cc'] = intval($list_count[$v['type_id']]);
            foreach($v['child'] as $k2=>$v2){
                $res['list'][$k]['child'][$k2]['cc'] = intval($list_count[$v2['type_id']]);
            }
        }

        $this->assign('domain_list',$domain_conf);
        $this->assign('domain_key',$domain_conf[$key]);
        $this->assign('list',$res['list']);
        $this->assign('total',$res['total']);
        $this->assign('title','分类管理');
        return $this->fetch('admin@typeseo/index');
    }

    public function info()
    {

        $param = input();

        $key =  $param['domain_key']??'';
        //获取域名
        $domain_conf = config('domain');

        if (!isset($domain_conf[$key])){
            return $this->error('打开失败，请联系技术人员');
        }
        $new_table = $key.'_type';
        $new_table = str_replace('.','_',$new_table);
        $model =  new \app\common\model\TypeSeo($new_table);

        if (Request()->isPost()) {
            $param = input('post.');
            $res = $model->saveData($param);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            $model->setCache();
            return $this->success($res['msg']);
        }

        $id = input('id');
        $pid = input('pid');
        $where=[];
        $where['type_id'] = ['eq',$id];

        $res = $model->infoData($where);

        $where=[];
        $where['type_id'] = ['eq',$pid];
        $resp = $model->infoData($where);

        $this->assign('info',$res['info']);
        $this->assign('infop',$resp['info']);
        $this->assign('pid',$pid);

        $where=[];
        $where['type_pid'] = ['eq','0'];
        $order='type_sort asc';
        $parent =$model->listData($where,$order);
        $this->assign('parent',$parent['list']);

        return $this->fetch('admin@typeseo/info');
    }

    public function del()
    {
        $param = input();
        $ids = $param['ids'];
        $key =  $param['domain_key']??'';
        //获取域名
        $domain_conf = config('domain');

        if (!isset($domain_conf[$key])){
            return $this->error('打开失败，请联系技术人员');
        }
        $new_table = $key.'_type';
        $new_table = str_replace('.','_',$new_table);
        $model =  new \app\common\model\TypeSeo($new_table);

        if(!empty($ids)){
            $where=[];
            $where['type_id'] = ['in',$ids];
            $res = $model->delData($where);
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
        $key =  $param['domain_key']??'';
        //获取域名
        $domain_conf = config('domain');

        if (!isset($domain_conf[$key])){
            return $this->error('打开失败，请联系技术人员');
        }
        $new_table = $key.'_type';
        $new_table = str_replace('.','_',$new_table);
        $model =  new \app\common\model\TypeSeo($new_table);

        if(!empty($ids) && in_array($col,['type_status']) && in_array($val,['0','1'])){
            $where=[];
            $where['type_id'] = ['in',$ids];

            $res = $model->fieldData($where,$col,$val);
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
        $key =  $param['domain_key']??'';
        //获取域名
        $domain_conf = config('domain');

        if (!isset($domain_conf[$key])){
            return $this->error('打开失败，请联系技术人员');
        }
        $new_table = $key.'_type';
        $new_table = str_replace('.','_',$new_table);
        $model =  new \app\common\model\TypeSeo($new_table);
        foreach ($ids as $k=>$id) {

            $data = [];
            $data['type_id'] = intval($id);
            $data['type_name'] = $param['type_name_'.$id];
            $data['type_sort'] = $param['type_sort_'.$id];
            $data['type_en'] = $param['type_en_'.$id];
            $data['type_tpl'] = $param['type_tpl_'.$id];
            $data['type_tpl_list'] = $param['type_tpl_list_'.$id];
            $data['type_tpl_detail'] = $param['type_tpl_detail_'.$id];

            if (empty($data['type_name'])) {
                $data['type_name'] = '未知';
            }

            $res = $model->saveData($data);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
        }
        $this->success($res['msg']);
    }
}
