<?php

namespace app\admin\controller;

use think\console\command\make\Model;
use think\Db;

class FfmpegToUp extends Base
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->assign('title', '定时任务管理');
        return $this->fetch('admin@ffmpegtoup/index');
    }

    public function list()
    {
        $vod_id = input('vod_id');
        $this->assign('vod_id', $vod_id);
        $this->assign('title', '集列表');
        return $this->fetch('admin@ffmpegtoup/list');
    }

    /**
     * 视频列表
     * @return [type] [description]
     */
    public function index2()
    {
        $param = input();
        $where = [];
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];
        if (!empty($param['vod_id'])) {
            $where['vod_id'] = ['eq', $param['vod_id']];
        }
        if ($param['is_down'] != '') {
            $where['is_down'] = ['eq', $param['is_down']];
        }
        if ($param['idName'] != '') {
            $where['vod_name'] = ['like', '%' . $param['idName'] . '%'];
        }
        $order = 'id desc';
        $res = model('FfmpegTpUp')->listData1(
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
        $data['msg'] = 'succ';
        $data['data'] = $res['list'];

        return $this->success('succ', null, $data);
    }

    /**
     * 视频列表
     * @return [type] [description]
     */
    public function index1()
    {
        $param = input();
        $where = [];
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['is_tr_up'] = isset($param['is_tr_up']) ? $param['is_tr_up'] : '1';
        $param['state'] = isset($param['state']) ? $param['state'] : '';
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];

        $where['is_tr_up'] = ['eq', $param['is_tr_up']];
        if ($param['state'] != '') {
            $where['state'] = ['eq', $param['state']];
        }
        if ($param['idName'] != '') {
            $where['video_name'] = ['like', '%' . $param['idName'] . '%'];
        }
        $order = 'id desc';
        $res = model('FfmpegTpUp')->listData(
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
        $data['msg'] = 'succ';
        $data['data'] = $res['list'];

        return $this->success('succ', null, $data);
    }

    public function tinfo()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            $param['is_sync'] = $param['is_down'];
            $save_video = model('FfmpegTpUp')->tsaveData($param);
            if ($save_video['code'] > 1) {
                return $this->error($save_video['msg']);
            }
            return $this->success($save_video['msg']);
        }

        $id = input('id');
        $where = [];
        $where['id'] = $id;

        $res = model('FfmpegTpUp')->tinfoData($where);
        $info = $res['info'];
        $this->assign('info', $info);

        $this->assign('title', '集视频信息');
        return $this->fetch('admin@ffmpegtoup/tinfo');
    }

    /**
     * 视频详情
     * @return [type] [description]
     */
    public function info()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            $data = Db::name('vod')->field('vod_name')->where(['vod_id' => $param['vod_id']])->find();
            if (empty($data)) {
                return $this->error('参数错误');
            }
            if ($param['pid'] == 1) {
                $param['tid'] = 3;
            } elseif ($param['pid'] == 2) {
                $param['tid'] = 13;
            } elseif ($param['pid'] == 3) {
                $param['tid'] = 15;
            } elseif ($param['pid'] == 4) {
                $param['tid'] = 20;
            }
            $param['video_name'] = $data['vod_name'];
//            p($param);
            $save_video = model('FfmpegTpUp')->saveData($param);
            if ($save_video['code'] > 1) {
                return $this->error($save_video['msg']);
            }
            return $this->success($save_video['msg']);
        }

        $id = input('id');
        $where = [];
        $where['id'] = $id;

        $res = model('FfmpegTpUp')->infoData($where);

        $info = $res['info'];
//        p($info);
        if (empty($info)) {
            $info['state'] = 0;
            $info['is_tr_up'] = 1;
            $info['url'] = '';
            $info['pid'] = '2';
            $info['weight'] = '99';
            $info['type'] = '0';
            $info['collection'] = '0';
            $info['up_time_sum'] = '0';
            $info['hour'] = '0';
            $info['branch'] = '0';
        }
        $this->assign('info', $info);

        $this->assign('title', '视频信息');
        return $this->fetch('admin@ffmpegtoup/info');
    }


}
