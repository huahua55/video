<?php

namespace app\admin\controller;

use think\console\command\make\Model;
use think\Db;

class VideoVod extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->assign('title', '下载任务管理');
        return $this->fetch('admin@videovod/index');
    }

    public function index1()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];

//        p($param);
        $where = [];
        $whereOr = [];

        if (!empty($param['idName'])) {
            $param['idName'] = htmlspecialchars(urldecode($param['idName']));
            $whereOr['a.vod_name'] = ['instr', $param['idName']];
            $whereOr['b.id'] = $param['idName'];
        }
        if (isset($param['b_is_down']) && $param['b_is_down'] != "") {
            $where['b.is_down'] = $param['b_is_down'];
        }
        if (isset($param['b_is_examine']) && $param['b_is_examine'] != "") {
            $where['b.is_examine'] = $param['b_is_examine'];
        }
        if (isset($param['b_is_section']) && $param['b_is_section'] != "") {
            $where['b.is_section'] = $param['b_is_section'];
        }
        if (isset($param['b_is_sync']) && $param['b_is_sync'] != "") {
            $where['b.is_sync'] = $param['b_is_sync'];
        }
        if (isset($param['b_code']) && $param['b_code'] != "") {
            $where['b.code'] = $param['b_code'];
        }
        $order = 'b.weight desc,b.down_time desc';
        if (isset($param['field']) && $param['field'] != "") {
            if ($param['field'] == 'b_weight') {
                $order = 'b.weight ' . $param['order'] . '';
            }
            if ($param['field'] == 'b_down_time') {
                $order = 'b.down_time ' . $param['order'] . '';
            }
            if ($param['field'] == 'b_id') {
                $order = 'b.id ' . $param['order'] . '';
            }
        }
        $res = model('VideoVod')->listData($whereOr, $where, $order, $param['page'], $param['limit']);
        $this->assign('list', $res['list']);
        $this->assign('total', $res['total']);
        $this->assign('page', $res['page']);
        $this->assign('limit', $res['limit']);
        $this->assign('param', $param);
        $this->assign('title', '下载任务管理');

        $data['code'] = 0;
        $data['count'] = $res['total'];
        $data['msg'] = 'succ';
        $data['data'] = $res['list'];
        return $data;
    }

    public function getExamine()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];
        $where = [];

        if (!empty($param['name'])) {
            ;
            $where['reasons'] = ['like', '%' . $param['name'] . '%'];
        }
        $order = 'id desc';
        $res = model('VideoVod')->listData1($where, $order, $param['page'], $param['limit']);
        $data['code'] = 0;
        $data['count'] = $res['total'];
        $data['msg'] = 'succ';
        $data['data'] = $res['list'];
        return $data;
    }

    public function updateExamine()
    {
        $param = input();
        $id = $param['id'] ?? '';
        $examine_id = $param['examine_id'] ?? '';
        $is_examine = $param['is_examine'] ?? '';
        $data['code'] = 0;
        $data['msg'] = 'error';
        $data['data'] = [];
        if (!empty($id)) {


            $where['id'] = $id;
            $collection_where['task_id'] = $id;
            $update = [];
            $collection_update = [];
            if (!empty($examine_id) || $examine_id == 0) {
                $update['examine_id'] = $examine_id;
                $collection_update['e_id'] = $examine_id;
            }
            if (!empty($is_examine) || $is_examine == 0) {
                $update['is_examine'] = $is_examine;
                $collection_update['is_examine'] = $is_examine;
                $getDataListWhere['id'] = $id;
                $vodVideoData = $this->getDataList($getDataListWhere);
                if (!empty($vodVideoData) && $vodVideoData['b_video_id'] > 0) {
                    $pid = getTypePid($vodVideoData['type_id']);
//                    p($pid);
                    if ($pid == 1) {
                        $videoWhere = [];
                        $videoWhere['id'] = $vodVideoData['b_video_id'];
                        $videoDataUpdate['is_examine'] = $is_examine;
                        Db::table('video')->where($videoWhere)->update($videoDataUpdate);
                    }
                }
            }


            $update['up_time'] = time();
            $collection_update['time_up'] = time();
            $res = Db::table('video_vod')->where($where)->update($update);
            $res_collection = Db::table('video_collection')->where($collection_where)->update($collection_update);
            if ($res && $res_collection) {
                $data['msg'] = 'succ';
            }
        }
        return $data;
    }


    public function info()
    {
//        p(11);

        if (Request()->isPost()) {
            $param = input();

            if (empty($param)) {
                return $this->error('参数错误');
            }
            $count = count(explode(',', $param['rel_ids']));
            if ($count > 1) {
                return $this->error('只能选择一个视频');
            }
            if (!empty($param['history_down_url'])) {
                $history_down_url = array_unique(array_filter(explode("\n", $param['history_down_url'])));
                if (!empty($history_down_url)) {
                    $param['history_down_url'] = json_encode($history_down_url, true);
                }
            } else {
                $param['history_down_url'] = json_encode([], true);
            }
            if (empty($param['resolution'])) {
                unset($param['resolution']);
            }
            $param['vod_id'] = $param['rel_ids'];
            unset($param['rel_ids']);
            if ($param['is_down'] == 0) {
                $param['code'] = -1;
            }
            $param['down_add_time'] = time();
            $param['down_time'] = time();
            $param['up_time'] = time();
            $res = model('VideoVod')->saveData($param);
            if ($res['code'] > 1) {
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }

        $id = input('id');
        $where = [];
        $where['id'] = ['eq', $id];
        $res = model('VideoVod')->infoData($where);

        $weight = $res['info']['weight'] ?? 99;
        $res['info']['weight'] = $weight;
        $res['info']['rel_ids'] = $res['info']['vod_id'] ?? '';
        if ($res['info']['rel_ids'] == 0) {
            $res['info']['rel_ids'] = '';
        }
        $history_down_url = json_decode($res['info']['history_down_url'], true);
//        p($history_down_url);
        if (!empty($history_down_url)) {
//            p(implode("\n",$history_down_url));
            $res['info']['history_down_url'] = implode("\n", $history_down_url);
        }
//        p($res);die;
        $this->assign('info', $res['info']);
        $this->assign('title', '编辑');
        return $this->fetch('admin@videovod/info');
    }

    public function del()
    {
        $param = input();
        $ids = $param['ids'];

        if (!empty($ids)) {
            $where = [];
            $where['id'] = ['in', $ids];
            $res = model('VodRecommend')->delData($where);
            if ($res['code'] > 1) {
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error('参数错误');
    }

    public function getDataList($where)
    {
        return Db::table('vod')->alias('a')->field('a.vod_id,a.type_id,a.type_id_1,a.group_id,a.vod_name,a.vod_sub,a.vod_en,a.vod_status,a.vod_letter,a.vod_color,a.vod_tag,a.vod_class,a.vod_plot_detail,b.id as b_id,b.is_section as b_is_section,b.reason as b_reason,a.vod_douban_id,a.vod_douban_score,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'right')->where($where)->find();
    }

    public function batch()
    {
        $param = input();
        $ids = $param['ids'];
        foreach ($ids as $k => $id) {
            $data = [];
            $data['id'] = intval($id);
            $data['name'] = $param['name'][$k];
            $data['sort'] = $param['sort'][$k];
            $data['rel_ids'] = $param['rel_ids'][$k];

            if (empty($data['name'])) {
                $data['name'] = '未知';
            }
            $res = model('VodRecommend')->saveData($data);
            if ($res['code'] > 1) {
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

        if (!empty($ids) && in_array($col, ['status']) && in_array($val, ['0', '1'])) {
            $where = [];
            $where['id'] = ['in', $ids];

            $res = model('VodRecommend')->fieldData($where, $col, $val);
            if ($res['code'] > 1) {
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error('参数错误');
    }

}
