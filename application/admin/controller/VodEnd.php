<?php

namespace app\admin\controller;

use app\common\util\Pinyin;
use think\Cache;
use think\Db;
use think\Log;
use Exception;

class VodEnd extends Base
{
    protected $vodLogDb; // 视频表



    public function __construct()
    {
        parent::__construct();
        $this->vodLogDb = Db::name('video');
    }


    public function index()
    {
        $param['task_date']= empty($param['task_date']) ?date("Y-m-d"): $param['limit'];
        $this->assign('title', '未完结数据');
        $this->assign('param', $param);
        return $this->fetch('admin@vodend/index');
    }

    public function index1()
    {
        $video_selected_domain = Db::table('video_domain')->where('type', 2)->find();
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];
        $param['task_date']= empty($param['task_date']) ?'': $param['task_date'];


        $order = 'vod_time_auto_up desc';
        $where = [];
        $where['vod_isend'] = 0;

        if (!empty($param['idName'])) {
            $where['vod_name'] = ['like','%'.$param['idName'].'%'];
        }
        if (!empty($param['task_date'])) {
            $where['vod_time_auto_up'] = ['like','%'.$param['task_date'].'%'];
        }
        $res = self::_listData(
            [],
            $where,
            $order,
            $param['page'],
            $param['limit']
        );
        $data['page'] = $res['page'];
        $data['limit'] = $res['limit'];
        $data['param'] = $param;
        $data['code'] = 0;
        $data['count'] = $res['total'];
        foreach ($res['list'] as $k=>$v){
            if(!empty($v['vod_pic'])){
                $res['list'][$k]['vod_pic'] = $video_selected_domain['img_domain'] .$v['vod_pic'];
            }
        }
        $data['data'] = $res['list'];
        return $this->success('succ', null, $data);
    }


    private function _listData($whereOr = [], $where, $order, $page = 1, $limit = 20, $start = 0)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodLogDb->where($where)->limit($limit_str)->count();
        $list = $this->vodLogDb->order($order)->where($where)->limit($limit_str)->select();
        return ['code' => 1, 'msg' => '数据列表', 'page' => $page, 'pagecount' => ceil($total / $limit), 'limit' => $limit, 'total' => $total, 'list' => $list];
    }


    /**
     * end_up
     * @return [type] [description]
     */
    public function end_up()
    {
        $param = input();
        $ids = $param['ids'];

        if (!empty($ids)) {
            $where = [];
            $where['id'] = ['in', $ids];
            $Update['vod_isend'] = 1;
            $res = $this->vodLogDb->where($where)->update($Update);
            if (false !== $res) {
                return $this->success('成功');
            }
            return $this->error('失败');
        }
        return $this->error('参数错误');
    }

    /**
     * 删除结果
     * @return [type] [description]
     */
    public function del()
    {
        $param = input();
        $ids = $param['ids'];
        if (!empty($ids)) {
            $where = [];
            $where['id'] = ['in', $ids];
            $res = $this->checkVideoCollectionDb->where($where)->delete();
            if (false !== $res) {
                return $this->success('删除成功');
            }
            return $this->error('删除失败');
        }
        return $this->error('参数错误');
    }

}