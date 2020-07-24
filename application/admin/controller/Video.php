<?php

namespace app\admin\controller;

use think\console\command\make\Model;
use think\Db;

class Video extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->assign('title', '视频数据管理');
        return $this->fetch('admin@video/index');
    }

    public function index1()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];

        // 过滤搜索条件
        $search_data = self::_filterSearchData( $param );

        $order = 'vod_time_auto_up desc';

        $res = model('video')->listData(
                    $search_data['whereOr'],
                    $search_data['where'],
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
        $res = model('video')->listData1($where, $order, $param['page'], $param['limit']);
        $data['code'] = 0;
        $data['count'] = $res['total'];
        $data['msg'] = 'succ';
        $data['data'] = $res['list'];
        return $data;
    }

    /**
     * 审核状态修改
     * @return [type] [description]
     */
    public function updateExamine()
    {
        $param = input();
        // 集表主键id
        $collection_id = $param['id'] ?? '';
        // 审核理由表主键id
        $examine_id = $param['examine_id'] ?? '';
        $is_examine = $param['is_examine'] ?? '';
        $data['code'] = 0;
        $data['msg'] = 'error';
        $data['data'] = [];
        if ( !empty( $collection_id ) ) {

            $collection_where['id'] = $collection_id;
            // 根据集表主键id获取相关数据
            $collention_info = self::_getCollectionData( $collection_where );
            
            // 获取视频信息
            $vedio_info = self::_getCollectionData( $collention_info['video_id'] ); 
            
            Db::startTrans();

            $video_edit = true;
            if ( $vedio_info['type_pid'] == 1 ) {
                // 电影 此时需要修改video表
                $video_where['id'] = $collention_info['video_id'];
                $video_edit_data['is_examine'] = $is_examine;
                $video_edit = Db::table('video')->where( $video_where )->update($video_edit_data);
            }

            // 修改集表
            $collection_edit_data['is_examine'] = $is_examine;
            $collection_edit_data['e_id'] = $examine_id;
            $collection_edit_data['time_up'] = time();
            $video_collection_edit = Db::table('video_collection')->where( $collection_where )->update( $collection_edit_data );
            
            if ( $video_edit !== false && $video_collection_edit !== false ) {
                Db::commit();

                $data['msg'] = 'succ';
            } else {
                Db::rollback();
            }
        }
        return $data;
    }


    public function info()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            $save_video = model('video')->saveData( $param );
            if($save_video['code']>1){
                return $this->error($save_video['msg']);
            }
            return $this->success($save_video['msg']);
        }

        $id = input('id');
        $where=[];
        $where['id'] = $id;

        // 获取集
        $video_collection_data = Db::table('video_collection')->field('id,video_id')->where( $where )->find();

        $video_where['id'] = $video_collection_data['video_id'];
        $res = model('video')->infoData( $video_where );


        $info = $res['info'];
        $this->assign('info',$info);

        //分类
        $type_tree = model('Type')->getCache('type_tree');
        $this->assign('type_tree',$type_tree);

        //地区、语言
        $config = config('maccms.app');
        $area_list = explode(',',$config['vod_area']);
        $lang_list = explode(',',$config['vod_lang']);
        $this->assign('area_list',$area_list);
        $this->assign('lang_list',$lang_list);


        $this->assign('title','视频信息');
        return $this->fetch('admin@video/info');
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

    /**
     * 根据集表主键id获取视频信息
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    private function _getCollectionData( $where )
    {
        return Db::table('video_collection')->field('video_id,task_id,collection')->where( $where )->find();
    }

    /**
     * 根据集表主键id获取视频信息
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    private function _getVedioData( $where )
    {
        return Db::table('video')->field('type_pid')->where( $where )->find();
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

    public function updateStatus()
    {
        $param = input();
        // 集表主键id
        $collection_id = $param['id'] ?? '';
        // 审核理由表主键id
        $status = $param['status'] ?? '';
        $data['code'] = 0;
        $data['msg'] = 'error';
        $data['data'] = [];
        $collection_where['id'] = $collection_id;

        $is_master = $param['is_master'];
        if ($is_master != 1 && $is_master != 0) {
            return $data;
        }
        // 根据集表主键id获取相关数据
        $collention_info = self::_getCollectionData( $collection_where );

        Db::startTrans();

        $video_edit = true;
        if ( $is_master == 1 ) {
            // 处理除电影以外的状态
            // 主集 此时需要修改video表
            $video_where['id'] = $collection_id;
            $video_edit_data['vod_status'] = $status;
            $video_edit_data['vod_time'] = time();
            $video_edit = Db::table('video')->where( $video_where )->update($video_edit_data);

            // 根据视频id获取所有的集id
            $video_collention_datas = Db::table('video_collection')->field('id')->where( ['video_id' => $collention_info['video_id']] )->select();

            $collection_where['id'] = ['in', array_column( $video_collention_datas, 'id')];
        } else {
            // 获取视频信息
            $video_where['id'] = $collention_info['video_id'];

            $vedio_info = self::_getVedioData( $video_where );
            $video_is_film_edit = true;

            if ( $vedio_info['type_pid'] == 1 ) {
                // 是电影
                $video_edit_data['vod_status'] = $status;
                $video_edit_data['vod_time'] = time();
                $video_is_film_edit = Db::table('video')->where( $video_where )->update($video_edit_data);
            }
        }

        // 修改集表
        $collection_edit_data['status'] = $status;
        $collection_edit_data['time_up'] = time();
        $video_collection_edit = Db::table('video_collection')->where( $collection_where )->update( $collection_edit_data );

        if ( $video_edit !== false && $video_collection_edit !== false && $video_is_film_edit !== false ) {
            Db::commit();

            $data['msg'] = 'succ';
        } else {
            Db::rollback();
        }
        return $data;
    }
    /**
     * 过滤搜索条件
     * @param string $data [description]
     */
    private function _filterSearchData( $param='' )
    {
        $where_a = [];
        $whereOr = [];
        if (!empty($param['idName'])) {
            $param['idName'] = htmlspecialchars(urldecode($param['idName']));
            $whereOr['a.vod_name'] = ['like', '%' . $param['idName'] . '%'];
            $whereOr['a.id'] = $param['idName'];
        }
        if (isset($param['b_is_examine']) && $param['b_is_examine'] != "") {
            $where_a['a.is_examine'] = $param['b_is_examine'];
        }
        if (isset($param['vod_status']) && $param['vod_status'] != "") {
            $where_a['a.vod_status'] = $param['vod_status'];
        }

        return ['whereOr' => $whereOr, 'where' => [ 'where_a' => $where_a]];
    }
}
