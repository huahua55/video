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
use app\common\model\Push;

class CheckVideoExist extends Common
{
    protected $videoDb; // 视频表
    protected $vodDb; // 主视频表

    protected function configure()
    {
        $this->videoDb = Db::name('video');
        $this->vodDb = Db::name('vod');
        $this->videoVodDb = Db::name('video_vod');
        $this->setName('CheckVideoExist')->addArgument('parameter')
            ->setDescription('定时计划：video已经有的标出来，没有的，任务优先级低的把优先级调成99，没分解成任务的分解出来优先级调成99。库里没有这个电影的标出来，人工处理');
    }

    protected function execute(Input $input, Output $output)
    {
        // 输出到日志文件
        $output->writeln("定时计划：检查视频是否存在:start...");
        try {
            self::_checkVideoWhile();
        } catch (Exception $e) {
            $output->writeln("检查视频是否存在异常：" . $e);
        }
        $output->writeln("定时计划：检查视频是否存在:end...");
    }

    /**
     * 视频while
     * @return [type] [description]
     */
    private function _checkVideoWhile(){
        $is_true = true;
        Cache::set('current_select_video_id', '');
        while ($is_true) {
            $return = self::_checkVideo();
            if ($return['error'] == '-1') {
                $is_true = false;
            }
        }
    }

    /**
     * 处理视频
     * @return [type] [description]
     *
     */
    private function _checkVideo(){
        $current_select_video_id = Cache::get('current_select_video_id');

        if (!empty($current_select_video_id)) {
            $video_where['id'] = ['GT', $current_select_video_id];
        }

        $db_config = 'mysql://root:root@127.0.0.1:3306/video#utf8';
        $check_video_info = Db::connect($db_config)
                ->table('check_video_exist')->field('id,name,class,actor,director,area,year')
                                    ->where($video_where)
                                    ->order('id asc')
                                    ->limit('0, 20')
                                    ->select();
        if (empty($check_video_info)) {
            // 为空 终止程序
            return ['error' => '-1'];
        }
        // 处理流程
        self::_filterVideo( $check_video_info );
    }

    /**
     * 过滤视频
     * @param  [type] $data [description]
     * @return [type]       [description]
     *
     * 
     * video已经有的标出来，没有的，任务优先级低的把优先级调成99，没分解成任务的分解出来优先级调成99。库里没有这个电影的标出来，人工处理
     *
     * 
     * 流程：：：
     * 1、根据视频名称查询video表
     *     存在 exist_video_table = 存在
     *     不存在 
     *         2、检查video_vod任务表
     *             存在   把weight优先级调成99
     *             不存在 
     *                 3、检查vod表
     *                     存在   分解成任务并把weight优先级调成99
     *                     不存在 exist_vod_table = 不存在
     */
    private function _filterVideo( $data ){
        foreach ($data as $k => $v) {
            log::info('------------视频名称为' . $v['name'] . '开始：：start------------');
            // 存储任务表更新数据
            $remote_data['video_vod_edit_data'] = [];
            // 存储本地更新数据
            $local_data = [];
            $local_data['id'] = $v['id'];
            // 1、根据视频名称查询video表
            $video_where['vod_name'] = $v['name'];
            $video_info = $this->videoDb->field('id')->where( $video_where )->find();
            if (!empty( $video_info )) {
                // 存在
                $local_data['exist_video_table'] = '存在';

                // 检查任务表
                $video_vod_exist = $this->videoVodDb->field('id')->where( $video_where )->find();
                if (!empty( $video_vod_exist )) {
                    $local_data['exist_video_vod_table'] = '存在';
                } else {
                    $local_data['exist_video_vod_table'] = '不存在';
                }

                // 检查主表
                $vod_exist = $this->vodDb->field('vod_id')->where( $video_where )->find();
                if (!empty( $vod_exist )) {
                    $local_data['exist_vod_table'] = '存在';
                } else {
                    $local_data['exist_vod_table'] = '不存在';
                }
            } else {
                // 不存在
                $local_data['exist_video_table'] = '不存在';
                // 2、检查video_vod任务表
                $video_vod_info = $this->videoVodDb->field('id,weight')->where( $video_where )->find();
                if (!empty( $video_vod_info )) {
                    // 存在
                    $local_data['exist_video_vod_table'] = '存在';

                    // 检查任务表中是否存在
                    $vod_exist = $this->vodDb->field('vod_id')->where( $video_where )->find();
                    if (!empty( $vod_exist )) {
                        $local_data['exist_vod_table'] = '存在';
                        // 把weight优先级调成99
                        $remote_data['video_vod_edit_data']['vod_id'] = $vod_exist['vod_id'];
                    } else {
                        $local_data['exist_vod_table'] = '不存在';
                    }
                    
                } else {
                    // 不存在
                    $local_data['exist_video_vod_table'] = '不存在';
                    // 3、检查vod表
                    $vod_info = $this->vodDb->field('vod_id,vod_play_url')->where( $video_where )->find();
                    if (!empty( $vod_info )) {
                        // 存在
                        $local_data['exist_vod_table'] = '存在';
                        // 分解成任务并把weight优先级调成99
                        log::info('分解任务开始：：start------------');

                        self::_resolveVod( $vod_info );

                        // 检查分解任务是否成功
                        $resolve_vod_success = self::_resolveVodSuccess( $video_where );
                        if ( $resolve_vod_success ) {
                            $local_data['resolve_vod_success'] = '已分解,任务表已存在';
                        } else {
                            $local_data['resolve_vod_success'] = '分解失败,任务表不存在';
                        }
                        
                        log::info('分解任务结束：：end------------');

                        $remote_data['video_vod_edit_data']['vod_id'] = $vod_info['vod_id'];
                    } else {
                        // 不存在
                        $local_data['exist_vod_table'] = '不存在';
                    }
                }
                Cache::set('current_select_video_id', $v['id']);
            }
            // 数据库操作
            $table_result = self::_setReturnToTable( $remote_data, $local_data );
            log::info('------------视频名称为' . $v['name'] . '结束：：end------------');
        }
    }

    /**
     * 分解任务
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function _resolveVod( $data ){
        // 是否存在 m3u8
        if ( strpos($data['vod_play_url'], '.m3u8') === false ) {
            log::info('分解任务失败，当前视频不存在 m3u8');
            return false;
        }

        $vod_id = $data['vod_id'];
        $video_vod_exist = $this->videoVodDb->field('id')->where( 'vod_id', $vod_id )->find();
        // 分解任务
        $push =  new Push();
        if(empty($video_vod_exist)){
            $push->getWhile($vod_id);
        }else{
            $push->getWhile2($vod_id);
        }
    }

    /**
     * 检查分解任务是否成功
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    private function _resolveVodSuccess( $where ){
        $resolve_vod_result = $this->videoVodDb->field('id')->where( $where )->find();
        if ( !empty($resolve_vod_result) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 处理结果
     * @param  [type] $return 结果
     * @param  [type] $type   视频类型
     * @return [type]        [description]
     */
    private function _setReturnToTable( $remote_data, $local_data ){
        Db::startTrans();

        $result_remote = true;
        if (!empty( $remote_data['video_vod_edit_data'] )) {
            // 处理结果 更新远程数据库
            $result_remote = self::_setReturnToRemoteTable($remote_data);
        }
        
        $result_local = true;
        if (!empty( $local_data )) {
            // 处理结果 更新本地数据库
            $result_local = self::_setReturnToLocalTable($local_data);
        }

        if ($result_remote && $result_local) {
            Db::commit(); 
        } else {
            Db::rollback();
        }
        
    } 

    /**
     * 处理结果到远程数据库
     * @param  [type] $return 结果
     * @param  [type] $type   视频类型
     * @return [type]        [description]
     */
    private function _setReturnToRemoteTable( $data ){
        try{
            $edit_video_vod_by_id = true;
            if ( isset( $data['video_vod_edit_data']['id'] ) && !empty( $data['video_vod_edit_data']['id'] ) ) {
                $edit_video_vod_by_id = self::_editVideoVodById( $data['video_vod_edit_data']['id'] );
            }

            $edit_video_vod_by_vod_id = true;
            if ( isset( $data['video_vod_edit_data']['vod_id'] ) && !empty( $data['video_vod_edit_data']['vod_id'] ) ) {
                $edit_video_vod_by_vod_id = self::_editVideoVodByVodId( $data['video_vod_edit_data']['vod_id'] );
            }
            if ($edit_video_vod_by_id && $edit_video_vod_by_vod_id) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            log::info('远程数据库操作异常：：' . $e);
            return false;
        }
        
    } 

    /**
     * 根据id修改任务表
     * @return [type] [description]
     */
    private function _editVideoVodById( $id ){
        // 更新任务表 包含主键id
        if (!empty($id)) {
            $edit_video_vod = $this->videoVodDb->where('id', $id)->setField('weight', 99);;
            if ($edit_video_vod === false) {
                log::info('更新远程数据库video_vod失败：：id=' . $id);
                return false;
            }
            log::info('更新远程数据库video_vod成功：：id=' . $id );
            return true;
        } else {
            log::info('更新远程数据库video_vod失败：：丢失主键id');
            return false;
        }
    }

    /**
     * 根据vod_id修改任务表
     * @return [type] [description]
     */
    private function _editVideoVodByVodId( $vod_id ){
        // 更新任务表 包含主键id
        if (!empty($vod_id)) {
            $edit_video_vod = $this->videoVodDb->where('vod_id', $vod_id)->setField('weight', 99);;
            if ($edit_video_vod === false) {
                log::info('更新远程数据库video_vod失败：：vod_id=' . $vod_id );
                return false;
            }
            log::info('更新远程数据库video_vod成功：：vod_id=' . $vod_id );
            return true;
        } else {
            log::info('更新远程数据库video_vod失败：：丢失vod_id');
            return false;
        }
    }

    /**
     * 处理结果到本地数据库
     * @param  [type] $return 结果
     * @param  [type] $type   视频类型
     * @return [type]        [description]
     */
    private function _setReturnToLocalTable( $data ){
        $db_config = 'mysql://root:root@127.0.0.1:3306/video#utf8';
        try{
            $result = Db::connect($db_config) //创建数据库连接
                ->table('check_video_exist') //选择数据表
                ->update($data);
            if ($result === false) {
                log::info('更新本地数据库check_video_exist失败：：data=' . json_encode($data) );
                return false;
            }
            log::info('更新本地数据库check_video_exist成功：：data=' . json_encode($data) );
            return true;
        } catch (\Exception $e) {
            log::info('更新本地数据库异常：：' . $e);
            return false;
        }
        
    } 
}