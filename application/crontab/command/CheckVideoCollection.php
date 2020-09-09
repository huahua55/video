<?php

namespace app\crontab\command;

use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Log;
use Exception;

class CheckVideoCollection extends Common
{
    protected $videoDb; // 视频表
    protected $videoCollectionDb; // 视频集表
    protected $videoSelectedDb; // 精选视频表
    protected $videoSelectedCollectionDb; // 精选视频集表


    protected function configure()
    {
        $this->videoDb = Db::name('video');
        $this->videoCollectionDb = Db::name('video_collection');
        $this->videoSelectedDb = Db::name('video_selected');
        $this->videoSelectedCollectionDb = Db::name('video_collection_selected');
        $this->setName('CheckVideoCollection')->addArgument('parameter')
            ->setDescription('定时计划：检查视频集是否完整');
    }

    protected function execute(Input $input, Output $output)
    {
        // 输出到日志文件
        $output->writeln("定时计划：检查视频集是否完整:start...");
        try {
            // 1、查video表  排除电影 type_pid!=1 or type_id not between 6 and 12
            // 2、根据video表 查 video_collection 按 collection 升序
            // 3、比较 集 的连续性  按 升序排列
            //     不连续     返回缺失的 集
            //     集数相同   记录错误
            //     连续       集数是否和总集数的差异
            //         集数 大于 总集数   记录错误
            //         集数 等于 总集数   不做处理
            //         集数 小于 总集数   记录缺失的集数

            // 处理普通视频
            self::_checkVideoWhile();
            self::_logWrite('------------------------------------------------');
            // 精选和普通视频分割
            $data['video_id'] = '';
            $data['vod_name'] = '';
            $data['eq_collections'] = '';
            $data['lack_collections'] = '';
            $data['more_collections'] = '';
            $data['lack_collections_total'] = '';
            // 处理结果 写入文件
            // self::_setReturnToTable($data);
            // 处理精选视频
            // self::_checkVideoSelectedWhile();

        } catch (Exception $e) {
            $output->writeln("检查视频集是否完整异常信息：" . $e);
        }
        $output->writeln("定时计划：检查视频集是否完整:end...");
    }

    /**
     * 普通视频while
     * @return [type] [description]
     */
    private function _checkVideoWhile(){
        $is_true = true;
        Cache::set('video_current_select_video_id', '');

        self::_logWrite('普通视频,检查视频集是否完整::start------');
        while ($is_true) {
            $return = self::_checkVideo();
            if ($return['error'] == '-1') {
                $is_true = false;
            }
        }
        self::_logWrite('普通视频,检查视频集是否完整::end------');
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
                                    ->limit('0, 20')
                                    ->select();
        // echo $this->videoDb->getLastSql();die;
        if (empty($video_info)) {
            // 为空 终止程序
            return ['error' => '-1'];
        }
        foreach ($video_info as $k => $v) {
            // 根据video表 查 video_collection 按 collection 升序
            $video_collection_where['video_id'] = $v['id'];
            $video_collection_where['status'] = ['neq', 2];
            $video_collection_where['collection'] = ['ELT', 1000];
            $video_collection_info = $this->videoCollectionDb->field('collection')
                                    ->where($video_collection_where)
                                    ->order('collection asc')
                                    ->select();
            // echo $this->videoCollectionDb->getLastSql();die;
            
            if (empty($video_collection_info)) {
                self::_logWrite('------视频：id=' . $v['id'] . ',视频名称=' . $v['vod_name'] . '::集数等于0'  . ',视频类型=' . $v['type_pid']);
                continue;
            }

            $collections = array_column($video_collection_info, 'collection');
            self::_logWrite('------视频：id=' . $v['id'] . ',视频名称=' . $v['vod_name'] . '-----start-----');

            $return = self::_checkNumContinue($collections, $v['vod_total']);

            Cache::set('video_current_select_video_id', $v['id']);

            if ($return['need_log']) {
                self::_logWrite('记录集数信息');
                $data['video_id'] = $v['id'];
                $data['vod_name'] = $v['vod_name'];
                $data['eq_collections'] = $return['eq_collections'];
                $data['lack_collections'] = $return['lack_collections'];
                $data['more_collections'] = $return['with_vod_total']['more_collections'];
                $data['lack_collections_total'] = $return['with_vod_total']['lack_collections'];
                // 处理结果 写入文件
                $result = self::_setReturnToTable($data);
                if ($result) {
                    self::_logWrite('写入数据库成功');
                }
            }
            self::_logWrite('------视频：id=' . $v['id'] . ',视频名称=' . $v['vod_name'] . '-----end-----');
        }
    }

    /**
     * 精选视频while
     * @return [type] [description]
     */
    private function _checkVideoSelectedWhile(){
        $is_true = true;
        Cache::set('video_selected_current_select_video_id', '');
        self::_logWrite('精选视频,检查视频集是否完整::start------');
        while ($is_true) {
            $return = self::_checkVideoSelected();
            if ($return['error'] == '-1') {
                $is_true = false;
            }
        }
        self::_logWrite('精选视频,检查视频集是否完整::end------');
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
        // echo $this->videoDb->getLastSql();die;
        foreach ($video_selected_info as $k => $v) {
            // 根据video表 查 video_collection 按 collection 升序
            $video_collection_selected_where['video_id'] = $v['id'];
            $video_collection_selected_where['status'] = ['neq', 2];
            $video_collection_selected_where['collection'] = ['ELT', 1000];
            $video_collection_selected_info = $this->videoSelectedCollectionDb->field('collection')
                                    ->where($video_collection_selected_where)
                                    ->order('collection asc')
                                    ->select();
                                    // echo $this->videoSelectedCollectionDb->getLastSql();die;
            
            if (empty($video_collection_selected_info)) {
                self::_logWrite('------视频：id=' . $v['id'] . ',视频名称=' . $v['vod_name'] . '::集数等于0');
                continue;
            }

            $collections = array_column($video_collection_selected_info, 'collection');

            $return = self::_checkNumContinue($collections, $v['vod_total']);

            Cache::set('video_selected_current_select_video_id', $v['id']);

            if ($return['need_log']) {
                self::_logWrite('------视频：id=' . $v['id'] . ',视频名称=' . $v['vod_name'] . '::');
                $data['video_id'] = $v['id'];
                $data['vod_name'] = $v['vod_name'];
                $data['eq_collections'] = $return['eq_collections'];
                $data['lack_collections'] = $return['lack_collections'];
                $data['more_collections'] = $return['with_vod_total']['more_collections'];
                $data['lack_collections_total'] = $return['with_vod_total']['lack_collections'];
                // 处理结果 写入文件
                $result = self::_setReturnToTable($data);
                if ($result) {
                    self::_logWrite('写入数据库成功');
                }
            }
        }
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
            $with_vod_total = ['more_collections' => '', 'lack_collections' => ''];
            // self::_logWrite('总集数为0现在没有比较意义,不做比较');
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
        // 本地连接线上数据库时使用     现在本地不能连接线上数据库
        // $db_config = 'mysql://root:root@127.0.0.1:3306/video#utf8';
        // try{
        //     return Db::connect($db_config) //创建数据库连接
        //         ->table('check_video_collection') //选择数据表
        //         ->insert($data);
        // } catch (\Exception $e) {
        //     self::_logWrite('写入数据库异常：：' . $e);
        // }
        
        try{
            return Db::name('check_video_collection')->insert($data);
        } catch (\Exception $e) {
            self::_logWrite('写入数据库异常：：' . $e);
        }
        
    }


    /**
     * 处理结果
     * @param  [type] $return 结果
     * @param  [type] $type   视频类型
     * @return [type]        [description]
     */
    private function _setReturn( $return ){
        if (!empty($return['eq_collections'])) {
            self::_logWrite('存在集数等同的，集数为('. $return['eq_collections'] . ')');
        }

        if (!empty($return['lack_collections'])) {
            self::_logWrite('存在缺失的集，集数为('. $return['lack_collections'] . ')');
        }

        if (!empty($return['with_vod_total'])) {
            if (!empty($return['with_vod_total']['more_collections'])) {
                self::_logWrite('视频集数连续但现有集数大于总集数，存在多余的集，集数为('. $return['with_vod_total']['more_collections'] . ')');
            }

            if (!empty($return['with_vod_total']['lack_collections'])) {
                self::_logWrite('视频集数连续但现有集数小于总集数，存在缺失的集，集数为('. $return['with_vod_total']['lack_collections'] . ')');
            }
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

    /**
     * 重新定义日志文件路径存储采集比较信息
     * @param  [type] $log_content [description]
     * @return [type]              [description]
     */
    private function _logWrite($log_content){
        $dir = LOG_PATH .'check_collect'. DS;
        if (!file_exists($dir)){
            mkdir($dir,0777,true);
        }
        \think\Log::init([
            'type' => \think\Env::get('log.type', 'test'),
            'path' => $dir,
            'level' => ['info'],
            'max_files' => 30]);
        \think\Log::info($log_content);
    }
}