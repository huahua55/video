<?php

namespace app\crontab\command;

use think\Db;
use think\Log;

/**
 * 视频关联表同步更新数据trait
 *
 * 不作为定时任务处理
 */
trait LinkTablesTrait{

	/**
     * 修改关联表数据
     * @param  [type] $vod_id [description]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    protected function editLinkTablesTraitFun($vod_id = NULL, $data = NULL){

        log::info($vod_id . '：修改视频关联表开始------');

        if ( empty( $vod_id ) || empty( $data ) ) {
            log::info('修改视频关联表缺失参数：vod_id=' . $vod_id . ';data=' . json_encode($data));
            return false;
        }

        $edit_video_vod_table = self::_editVideoVodTable($vod_id, $data);
        $edit_video_table = self::_editVideoTable($vod_id, $data);
        $edit_video_selected_table = self::_editVideoSelectedVod($vod_id, $data);

        log::info($vod_id . '：修改视频关联表结束------');

        if ($edit_video_vod_table && $edit_video_table && $edit_video_selected_table) {
        	return true;
        }
        return false;
    }

    /**
     * 修改任务表
     * @param  [type] $vod_id [description]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    private function _editVideoVodTable( $vod_id, $data ){
    	// 根据vod_id检查任务表数据是否存在
        $video_vod_where['vod_id'] = ['eq', $vod_id];
        $video_vod_check = Db::name('video_vod')->where($video_vod_where)->column('vod_id');
        if ( empty( $video_vod_check ) ) {
            log::info('任务表数据不存在：vod_id=' . $vod_id);
            return false;
        }
        // 根据vod_id修改任务表
        $video_vod_data['vod_name'] = $data['vod_name'];
        $video_vod_data['up_time'] = time();
        $save_video_vod = Db::name('video_vod')->where($video_vod_where)->update($video_vod_data);
        if ($save_video_vod !== false ) {
            log::info('修改任务表成功：vod_id=' . $vod_id);
            return true;
        } else {
        	log::info('修改任务表失败：vod_id=' . $vod_id . ';data' . json_encode($video_vod_data));
            return false;
        }
    }

    /**
     * 修改视频表
     * @param  [type] $vod_id [description]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    private function _editVideoTable( $vod_id, $data ){
    	// 根据vod_id从任务表中查询出video_id不为0的且vod_id=$vod_id相关联的video_id
    	$video_vod_where['vod_id'] = ['eq', $vod_id];
        $video_vod_where['video_id'] = ['neq', 0];
        $video_id = Db::name('video_vod')->where($video_vod_where)->column('video_id');
        if ( empty( $video_id ) ) {
            log::info('vod_id关联的video_id不存在：vod_id=' . $vod_id);
            return false;
        }

        // 根据video_id检查video表数据是否存在
        $video_where['id'] = ['eq', $video_id[0]];
        $video_check = Db::name('video')->where($video_where)->column('id');
        if ( empty( $video_check ) ) {
            log::info('video表数据不存在：video_id=' . $video_id);
            return false;
        }

        // 根据video_id修改video表数据
        $video_data = self::_filterVideoDataTraitFun( $data, 'doubanScoreCopy', true );
        $save_video = Db::name('video')->where($video_where)->update($video_data);
        if ($save_video !== false ) {
        	log::info('修改video表成功：video_id=' . $video_id[0]);
            return true;
        } else {
        	log::info('修改video表失败：video_id=' . $video_id[0] . ';data' . json_encode($video_data));
            return false;
        }
    }

    /**
     * 修改精选表
     * @param  [type] $vod_id [description]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    private function _editVideoSelectedVod( $vod_id, $data ){
    	// 根据vod_id修改video_selected表数据
        $video_selected_where['vod_id'] = $vod_id;
        $video_data = self::_filterVideoDataTraitFun( $data, 'doubanScoreCopy', true );
        $save_video_selected = Db::name('video_selected')->where($video_selected_where)->update($video_data);
        if ($save_video_selected !== false ) {
            log::info('修改video_selected表成功：vod_id=' . $vod_id);
            return true;
        } else {
        	log::info('修改video_selected表失败：vod_id=' . $vod_id . ';data' . json_encode($video_data));
            return false;
        }
    }

    /**
     * 过滤video数据
     * @param  array    $data           数据
     * @return string   $from           数据来源 按照定时任务文件名定义 可以定义所需数据
     * @return boolen   $useCommonData  是否使用公共数据
     */
    // vod_name vod_sub vod_en vod_tag vod_pic vod_pic_thumb vod_pic_slide vod_actor vod_director vod_writer vod_behind vod_blurb vod_remarks vod_pubdate vod_total vod_serial vod_tv vod_weekday vod_area vod_lang vod_year vod_version vod_state vod_duration vod_isend vod_douban_id vod_douban_score vod_time
    
    private function _filterVideoDataTraitFun( $data = NULL, $from = 'doubanScoreCopy', $useCommonData = true ){

        $video_data = [];
        if ( $useCommonData ) {
            $video_data['vod_name'] = $data['vod_name'];
            $video_data['vod_sub'] = isset($data['vod_sub'])?$data['vod_sub']:'';
            $video_data['vod_tag'] = isset($data['vod_tag'])?$data['vod_tag']:'';
            $video_data['vod_actor'] = isset($data['vod_actor'])?$data['vod_actor']:'';
            $video_data['vod_director'] = isset($data['vod_director'])?$data['vod_director']:'';
            $video_data['vod_writer'] = isset($data['vod_writer'])?$data['vod_writer']:'';
            $video_data['vod_pubdate'] = isset($data['vod_pubdate'])?$data['vod_pubdate']:'';
            $video_data['vod_total'] = isset($data['vod_total'])?$data['vod_total']:0;
            $video_data['vod_area'] = isset($data['vod_area'])?$data['vod_area']:'';
            $video_data['vod_lang'] = isset($data['vod_lang'])?$data['vod_lang']:'';
            $video_data['vod_year'] = isset($data['vod_year'])?$data['vod_year']:'';
            $video_data['vod_state'] = $data['vod_state'];
            $video_data['vod_duration'] = isset($data['vod_duration'])?$data['vod_duration']:'0';
            $video_data['vod_douban_id'] = $data['vod_douban_id'];
            $video_data['vod_douban_score'] = isset($data['vod_douban_score'])?$data['vod_douban_score']:'0.0';
            $video_data['vod_time'] = time();
        }
        switch ($from) {
            case 'doubanScoreCopy':
                break;
            default:
                break;
        }

        return $video_data;
    }
}
?>