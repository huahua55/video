<?php

namespace app\admin\controller;

use think\Cache;
use think\Db;
use think\Log;
use Exception;

class CheckVideoCollection extends Base
{
    protected $videoDb; // 视频表
    protected $videoCollectionDb; // 视频集表
    protected $videoSelectedDb; // 精选视频表
    protected $videoSelectedCollectionDb; // 精选视频集表
    protected $checkVideoCollectionDb; // 校验结果表


    public function __construct()
    {
        parent::__construct();

        $this->videoDb = Db::name('video');
        $this->videoCollectionDb = Db::name('video_collection');
        $this->videoSelectedDb = Db::name('video_selected');
        $this->videoSelectedCollectionDb = Db::name('video_collection_selected');
        $this->checkVideoCollectionDb = Db::name('check_video_collection');
    }

    public function index()
    {
        $this->assign('title', '视频数据管理');
        return $this->fetch('admin@checkvideocollection/index');
    }

    public function index1()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];

        $order = 'video_id desc';

        $res = self::_listData(
                    [],
                    [],
                    $order, 
                    $param['page'],
                    $param['limit']
                );
        $data['page'] = $res['page'];
        $data['limit'] = $res['limit'];
        $data['param'] = $param;


        $data['code'] = 0;
        $data['count'] = $res['total'];
        $data['data'] = $res['list'];

        return $this->success('succ', null, $data);
    }

    /**
     * 列表数据
     * @param  array   $whereOr [description]
     * @param  [type]  $where   [description]
     * @param  [type]  $order   [description]
     * @param  integer $page    [description]
     * @param  integer $limit   [description]
     * @param  integer $start   [description]
     * @return [type]           [description]
     */
    private function _listData($whereOr = [], $where, $order, $page = 1, $limit = 20, $start = 0)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        
        $field_a = 'id,video_id,vod_name,eq_collections,lack_collections,more_collections,lack_collections_total';

        $total = $this->checkVideoCollectionDb->limit($limit_str)->count();

        $list = $this->checkVideoCollectionDb
                ->field( $field_a )
                ->order( $order )->limit( $limit_str )->select();

        return ['code' => 1, 'msg' => '数据列表', 'page' => $page, 'pagecount' => ceil($total / $limit), 'limit' => $limit, 'total' => $total, 'list' => $list];
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

    /**
     * 执行校验
     * @return [type] [description]
     */
    public function execute()
    {
        $current_video_id = input('current_video_id');
        $current_selected_video_id = input('current_selected_video_id');
        $video_type = input('video_type')?input('video_type'):1;
        // 输出到日志文件
        // 1、查video表  排除电影 type_pid!=1 or type_id not between 6 and 12
        // 2、根据video表 查 video_collection 按 collection 升序
        // 3、比较 集 的连续性  按 升序排列
        //     不连续     返回缺失的 集
        //     集数相同   记录错误
        //     连续       集数是否和总集数的差异
        //         集数 大于 总集数   记录错误
        //         集数 等于 总集数   不做处理
        //         集数 小于 总集数   记录缺失的集数

        if ($video_type == 1) {
            // 处理普通视频
            self::_checkVideoWhile($current_video_id);
        } else if ($video_type == 2) {
            // 处理精选视频
            return $this->error('精选视频不做处理');
            // self::_checkVideoSelectedWhile($current_selected_video_id);
        } else {
            return $this->error('视频类型错误');
        }
        
        // 返回执行到的video_id
        $current_video_id = Cache::get('video_current_select_video_id');
        $current_selected_video_id = Cache::get('video_selected_current_select_video_id');

        $succ_data['current_video_id'] = $current_video_id ? $current_video_id : '';
        $succ_data['current_selected_video_id'] = $current_selected_video_id ? $current_selected_video_id : '';

        return $this->success('校验完成', null, $succ_data);
    }

    /**
     * 普通视频while
     * @return [type] [description]
     */
    private function _checkVideoWhile($current_video_id){

        $is_true = true;
        if (!empty($current_video_id)) {
            Cache::set('video_current_select_video_id', $current_video_id);
        } else {
            Cache::set('video_current_select_video_id', '');
        }

        while ($is_true) {
            $return = self::_checkVideo();
            if ($return['error'] == '-1') {
                $is_true = false;
                Cache::rm('video_current_select_video_id');
            }
        }
    }

    /**
     * 处理普通视频
     * @return [type] [description]
     */
    private function _checkVideo(){
        $current_select_video_id = Cache::get('video_current_select_video_id');
        $video_where['vod_status'] = ['neq', 2];
        // $video_where['vod_time_add'] = ['lt', strtotime('2020-08-11 00:00:00')];
        if (!empty($current_select_video_id)) {
            $video_where['id'] = ['lt', $current_select_video_id];
        }
        // 查video表  排除电影 type_pid!=1 or type_id not between 6 and 12
        $video_info = $this->videoDb->field('id,vod_total,vod_name,type_pid')
                                    ->where('type_pid != 1 or type_id not between 6 and 12')
                                    ->where($video_where)
                                    ->order('id desc')
                                    ->limit('0, 30')
                                    ->select();
        if (empty($video_info)) {
            // 为空 终止程序
            return ['error' => '-1'];
        }
        $add_data = [];
        foreach ($video_info as $k => $v) {
            // 根据video表 查 video_collection 按 collection 升序
            $video_collection_where['video_id'] = $v['id'];
            $video_collection_where['status'] = ['neq', 2];
            $video_collection_where['collection'] = ['ELT', 1000];
            $video_collection_info = $this->videoCollectionDb->field('collection')
                                    ->where($video_collection_where)
                                    ->order('collection asc')
                                    ->select();
            
            if (empty($video_collection_info)) {
                continue;
            }

            $collections = array_column($video_collection_info, 'collection');

            $return = self::_checkNumContinue($collections, $v['vod_total']);

            Cache::set('video_current_select_video_id', $v['id']);

            if ($return['need_log']) {
                $add_data[] = [
                    'video_id' => $v['id'],
                    'vod_name' => $v['vod_name'],
                    'eq_collections' => $return['eq_collections'],
                    'lack_collections' => $return['lack_collections'],
                    'more_collections' => $return['with_vod_total']['more_collections'],
                    'lack_collections_total' => $return['with_vod_total']['lack_collections']
                ];
            }
        }
        if (!empty($add_data)) {
            // 处理结果 写入文件
            self::_setReturnToTable($add_data);
        }
        return ['error' => '1'];
    }

    /**
     * 精选视频while
     * @return [type] [description]
     */
    private function _checkVideoSelectedWhile($current_selected_video_id){
        
        $is_true = true;
        if (!empty($current_selected_video_id)) {
            Cache::set('video_selected_current_select_video_id', $current_selected_video_id);
        } else {
            Cache::set('video_selected_current_select_video_id', '');
        }
        
        while ($is_true) {
            $return = self::_checkVideoSelected();
            if ($return['error'] == '-1') {
                $is_true = false;
                Cache::rm('video_selected_current_select_video_id');
            }
        }
    }

    /**
     * 处理精选视频
     * @return [type] [description]
     */
    private function _checkVideoSelected(){
        $current_select_video_id = Cache::get('video_selected_current_select_video_id');

        $video_selected_where['vod_status'] = ['neq', 2];
        // $video_selected_where['vod_time_add'] = ['lt', strtotime('2020-08-11 00:00:00')];
        if (!empty($current_select_video_id)) {
            $video_selected_where['id'] = ['lt', $current_select_video_id];
        }
        // 查video表  排除电影 type_pid!=1 or type_id not between 6 and 12
        $video_selected_info = $this->videoSelectedDb->field('id,vod_total,vod_name')
                                    ->where('type_pid != 1 or type_id not between 6 and 12')
                                    ->where($video_selected_where)
                                    ->order('id desc')
                                    ->limit('0, 20')
                                    ->select();
        if (empty($video_selected_info)) {
            // 为空 终止程序
            return ['error' => '-1'];
        }

        $add_data = [];
        foreach ($video_selected_info as $k => $v) {
            // 根据video表 查 video_collection 按 collection 升序
            $video_collection_selected_where['video_id'] = $v['id'];
            $video_collection_selected_where['status'] = ['neq', 2];
            $video_collection_selected_where['collection'] = ['ELT', 1000];
            $video_collection_selected_info = $this->videoSelectedCollectionDb->field('collection')
                                    ->where($video_collection_selected_where)
                                    ->order('collection asc')
                                    ->select();
            
            if (empty($video_collection_selected_info)) {
                continue;
            }

            $collections = array_column($video_collection_selected_info, 'collection');

            $return = self::_checkNumContinue($collections, $v['vod_total']);

            Cache::set('video_selected_current_select_video_id', $v['id']);

            if ($return['need_log']) {
                $add_data[] = [
                    'video_id' => $v['id'],
                    'vod_name' => $v['vod_name'],
                    'eq_collections' => $return['eq_collections'],
                    'lack_collections' => $return['lack_collections'],
                    'more_collections' => $return['with_vod_total']['more_collections'],
                    'lack_collections_total' => $return['with_vod_total']['lack_collections']
                ];
            }
        }
        if (!empty($add_data)) {
            // 处理结果 写入文件
            self::_setReturnToTable($add_data);
        }
        return ['error' => '1'];
    }

    /**
     * 检验数字的连续性
     * @param  [type] $array [description]
     * @return [type]        [description]
     */
    private function _checkNumContinue( $collections, $vod_total ){
        // 比较 集 的连续性  按 升序排列
        sort($collections);

        $count = count($collections);
        // 是否是连续的
        $is_continue = true;
        // 相等的集
        $eq_collections = '';
        // 缺失的集
        $lack_collections = '';
        // 和总集数比较
        $with_vod_total = [];
        // 返回值
        $return = [
            'error' => 0,
            'msg' => '成功',
            'eq_collections' => '',
            'lack_collections' => '',
            'with_vod_total' => [],
            'need_log' => true
        ];
        // 集数不等于1
        if ($count != 1){

            for ($i = 0; $i < $count - 1; $i++) {
                // 不连续  记录缺失的 集
                if ($collections[$i + 1] - $collections[$i] > 1){
                    $lack_collections_arr = range($collections[$i], $collections[$i + 1]);
                    array_pop($lack_collections_arr);
                    array_shift($lack_collections_arr);
                    $lack_collections .= implode(',', $lack_collections_arr) . ',';
                    $is_continue = false;
                }

                // 集数相同   记录错误
                if ($collections[$i + 1] - $collections[$i] == 0) {
                    $eq_collections .= $collections[$i] . ',';
                    $is_continue = false;
                }

                // 最后的集  不再比较
                if (($i+1) == ($count - 1)) {
                    break;
                }
            }
            
            $lack_collections = rtrim($lack_collections, ',');
            $eq_collections = rtrim($eq_collections, ',');

            // 是否缺失第1集到$collections[0]的集
            if ($collections[0] != 1) {
                $lack_collections_arr_1 = range(1, $collections[0]);
                array_pop($lack_collections_arr_1);
                $lack_collections = implode(',', $lack_collections_arr_1) . ',' . $lack_collections;
            }
        }

        if ($vod_total != 0) {
            // 集数和总集数的差异 返回多余的集  总集数为0现在没有比较意义
            $with_vod_total = self::_withVodTotalCompore($count, $vod_total, array_pop($collections));
        } else {
            // 总集数为0现在没有比较意义,不做比较
            $with_vod_total = ['more_collections' => '', 'lack_collections' => ''];
        }

        if ($is_continue && (empty($with_vod_total['lack_collections']) && empty($with_vod_total['more_collections']))) {
            $return['need_log'] = false;
        }

        $return['eq_collections'] = $eq_collections;
        $return['lack_collections'] = $lack_collections;
        $return['with_vod_total'] = $with_vod_total;
        return $return;
    }

    /**
     * 处理结果到数据库
     * @param  [type] $return 结果
     * @param  [type] $type   视频类型
     * @return [type]        [description]
     */
    private function _setReturnToTable( $data ){

        $add =  $this->checkVideoCollectionDb->insertAll($data);
        if (count($data) == $add) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 集数和总集数的差异
     * @param  [type] $count       集数量
     * @param  [type] $vod_total   总集数
     * @return [type]        [description]
     */
    private function _withVodTotalCompore( $count, $vod_total, $last_collections ){
        // 缺失的集
        $lack_collections = '';
        // 多余的集
        $more_collections = '';

        if ($count > $vod_total) {
            if (($count - $vod_total) == 1) {
                $more_collections_arr = [$last_collections];
            } else {
                // 集数 大于 总集数   记录错误
                $more_collections_arr = range($vod_total, $last_collections);
                array_shift($more_collections_arr);
            }
            $more_collections = implode(',', $more_collections_arr);
        }

        if ($count < $vod_total) {
            // 集数 小于 总集数   记录缺失的集数
            $lack_collections_arr = range($last_collections, $vod_total);
            array_shift($lack_collections_arr);
            $lack_collections = implode(',', $lack_collections_arr);
        }
        return ['more_collections' => $more_collections, 'lack_collections' => $lack_collections];
    }
}