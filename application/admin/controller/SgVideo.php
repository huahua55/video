<?php

namespace app\admin\controller;

use think\console\command\make\Model;
use think\Db;
use function GuzzleHttp\Promise\unwrap;
use function Qiniu\entry;

class SgVideo extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->assign('title', '思古数据管理');
        return $this->fetch('admin@sgvideo/index');
    }


    public function index1(){
        $param = input();
        $where = [];
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['vod_status'] = isset($param['vod_status']) ? $param['vod_status'] : '';
        $param['vod_type'] = isset($param['vod_type']) ? $param['vod_type'] : '';
        $param['type'] = isset($param['type']) ? $param['type'] : '';
        $param['state'] = isset($param['state']) ? $param['state'] : '';
        $param['succ_type'] = isset($param['succ_type']) ? $param['succ_type'] : '';
        $param['idName'] = isset($param['idName']) ? $param['idName'] : '';
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];
        if ($param['type'] != '') {
            $where['type'] = ['eq', $param['type']];
        }
        if ($param['vod_status'] != '') {
            $where['vod_status'] = ['eq', $param['vod_status']];
        }
        if ($param['vod_type'] != '') {
            $where['vod_type'] = ['eq', $param['vod_type']];
        }
        if ($param['succ_type'] != '') {
            $where['succ_type'] = ['eq', $param['succ_type']];
        }
        if ($param['idName'] != '') {
            $where['vod_name'] = ['like', '%' . $param['idName'] . '%'];
        }
        $order = 'id desc';
        $res = model('SgVideo')->listData(
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
     * 视频详情
     * @return [type] [description]
     */
    public function info()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            $data = Db::name('vod')->field('vod_name')->where(['vod_id' => $param['vod_id']])->find();
            $video_data = Db::name('video')->where(['vod_id' => $param['vod_id']])->find();
//            p($video_data);
            if (empty($data)) {
                return $this->error('相关视频ID错误');
            }
            if (!empty($video_data)){

                $param['video_id'] = $video_data['id'];
            }
            $param['vod_name'] = $data['vod_name'];
//            p($param);
            $save_video = model('SgVideo')->saveData($param);
            if ($save_video['code'] > 1) {
                return $this->error($save_video['msg']);
            }
            return $this->success($save_video['msg']);
        }

        $id = input('id');
        $where = [];
        $where['id'] = $id;
        $res = model('SgVideo')->infoData($where);
        $info = $res['info'];
        $this->assign('info', $info);
        $this->assign('title', '视频信息');
        return $this->fetch('admin@sgvideo/info');
    }


    public function list()
    {
        $id = input('id');
        $this->assign('id', $id);
        $this->assign('title', '集列表');
        return $this->fetch('admin@sgvideo/list');
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
        $param['succ_type'] = isset($param['succ_type']) ? $param['succ_type'] : '';
        $param['idName'] = isset($param['idName']) ? $param['idName'] : '';
        if ($param['succ_type'] != '') {
            $where['succ_type'] = ['eq', $param['succ_type']];
        }
        if ($param['idName'] != '') {
            $where['vod_name'] = ['like', '%' . $param['idName'] . '%'];
        }
        if ($param['id'] != ''){
            $where['sg_id'] = ['eq', $param['id']];
        }

        $order = 'id desc';
        $res = model('SgVideo')->listData1(
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


    public function tinfo(){
        if (Request()->isPost()) {
            $param = input('post.');
            $save_video = model('SgVideo')->tsaveData($param);
            if ($save_video['code'] > 1) {
                return $this->error($save_video['msg']);
            }
            return $this->success($save_video['msg']);
        }

        $id = input('id');
        $where = [];
        $where['id'] = $id;

        $res = model('SgVideo')->tinfoData($where);
        $info = $res['info'];
        $this->assign('info', $info);

        $this->assign('title', '集视频信息');
        return $this->fetch('admin@sgvideo/tinfo');
    }



}