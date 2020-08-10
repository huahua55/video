<?php

namespace app\common\model;

use think\Db;
use think\Cache;
use think\helper\Arr;

class Video extends Base
{
    // 设置数据表（不含前缀）
    protected $name = 'video';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

    // 自动完成
    protected $auto = [];
    protected $insert = [];
    protected $update = [];

    public function listData($whereOr = [], $where, $order, $page = 1, $limit = 20, $start = 0)
    {
        $video_domain = Db::table('video_domain')->find();
        $video_examine = Db::table('video_examine')->column(null,'id');
        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        //a.id,a.type_pid,a.type_id,a.vod_name,a.vod_sub,a.vod_en,a.vod_tag,a.vod_pic,a.vod_pic_thumb,a.vod_pic_slide,a.vod_actor,a.vod_director,a.vod_writer,a.vod_behind,a.vod_blurb,a.vod_remarks,a.vod_pubdate,a.vod_total,a.vod_serial,a.vod_tv,a.vod_weekday,a.vod_area,a.vod_lang,a.vod_year,a.vod_version,a.vod_state,a.vod_duration,a.vod_isend,a.vod_douban_id,a.vod_douban_score,a.vod_time,a.vod_time_add,a.is_from,a.is_examine,a.vod_status,a.vod_time_auto_up
        //b.id,b.video_id,b.task_id,b.title,b.collection,b.vod_url,b.type,b.status,b.e_id,b.is_examine,b.resolution,b.bitrate,b.duration,b.size,b.time_up,b.time_auto_up
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        
        $field_a = 'a.id as aid,a.type_pid,a.type_id,a.vod_name,a.vod_sub,a.vod_en,a.vod_tag,a.vod_pic,a.vod_pic_thumb,a.vod_pic_slide,a.vod_actor,a.e_id,a.vod_director,a.vod_writer,a.vod_behind,a.vod_blurb,a.vod_remarks,a.vod_pubdate,a.vod_total,a.vod_serial,a.vod_tv,a.vod_weekday,a.vod_area,a.vod_lang,a.vod_year,a.vod_version,a.vod_state,a.vod_duration,a.vod_isend,a.vod_douban_id,a.vod_douban_score,a.vod_time,a.vod_time_add,a.is_from,a.is_examine,a.vod_status,a.vod_time_auto_up';

        $field_b = 'b.id as bid,b.video_id,b.task_id,b.title,b.collection,b.vod_url,b.type,b.status,b.e_id as b_eid,b.is_examine as b_is_examine,b.resolution,b.bitrate,b.duration,b.size,b.time_up,b.time_auto_up';

        $total = Db::name('Video')
                    ->alias( 'a' )
                    ->whereOr( $whereOr )->where( $where['where_a'] )->limit($limit_str)->count();
        $videos = Db::name('Video')
                ->alias( 'a' )
                ->field( $field_a )
                ->where( $where['where_a'] )
                ->whereOr( $whereOr )
                ->order( $order )->limit( $limit_str )->select();
        $list = [];

        $where_b = [];

        if (isset($where['where_a']['a.vod_status']) && $where['where_a']['a.vod_status'] != "") {
            $where_b['b.status'] = $where['where_a']['a.vod_status'];
        }
        if (isset($where['where_a']['a.is_examine']) && $where['where_a']['a.is_examine'] != "") {
            $where_b['b.is_examine'] = $where['where_a']['a.is_examine'];
        }

        foreach ($videos as $v) {

            // 主集
            $video_collection_count = Db::name('video_collection')
                ->alias( 'b' )
                ->where( 'b.video_id', $v['aid'] )
                ->count();

            // 获取视频总集数
            if ($v['type_pid'] == 1) {
                // 电影 总集数默认为1
                $video_total = 1;
            } else {
                if ($v['type_id'] >= 6 && $v['type_id'] <= 12) {
                    $video_total = 1;
                } else {
                    $video_total = $v['vod_total'];
                }
            }

            $list[] = [
                    'vod_name' => $v['vod_name'],
                    'video_id' => $v['aid'],
                    'bid' => $v['aid'] . '_' . $v['aid'],
                    'is_master' => 1,
                    'collection' => $video_total . '-' . $video_collection_count,
                    'm_reasons' => isset($video_examine[$v['e_id']])?$video_examine[$v['e_id']]:'',
                    'm_time_auto_up' => $v['vod_time_auto_up'],
                    'm_eid' => $v['e_id'],
                    'm_status' => $v['vod_status'],
                    'pid' => 0,
                    'type_pid' => $v['type_pid'],
                    'vod_pic' => ''
                ];
            $video_collection = Db::name('video_collection')
                ->alias( 'b' )
                ->field( $field_b )
                ->where( 'b.video_id', $v['aid'] )
                ->where( $where_b )
                ->order( 'b.collection asc' )
                ->select();
            foreach ($video_collection as $v1) {
                $v1['pid'] = $v['aid'] . '_' . $v['aid'];
                $v1['m_eid'] = $v1['b_eid'];
                $v1['m_time_auto_up'] = $v1['time_auto_up'];
                $v1['m_reasons'] = isset($video_examine[$v1['b_eid']])?$video_examine[$v1['b_eid']]:'';
                $v1['m_status'] = $v1['status'];
                $v1['is_master'] = 0;
                $v1['vod_pic'] = $v['vod_pic'];
                $v1['vod_name'] = $v['vod_name'];
                $v1['aid'] = $v['aid'];
                $v1['type_pid'] = $v['type_pid'];
                $list[] = $v1;
            }
            
        }
        
        foreach ($list as $k_list => &$v_list) {

            if (substr_count($v_list['vod_pic'], 'http') == 0) {
                $v_list['vod_pic'] = $video_domain['img_domain'] . $v_list['vod_pic'];
            }
            if(!empty($v_list['vod_url'])){
                $v_list['vod_url'] = $video_domain['vod_domain'] . $v_list['vod_url'];
            }
        }

        return ['code' => 1, 'msg' => '数据列表', 'page' => $page, 'pagecount' => ceil($total / $limit), 'limit' => $limit, 'total' => $total, 'list' => $list];
    }

    public function listData1($where, $order, $page = 1, $limit = 20, $start = 0)
    {
        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = Db::table('video_examine')->where($where)->order($order)->count();
        $list = Db::table('video_examine')->where($where)->order($order)->limit($limit_str)->select();
        return ['code' => 1, 'msg' => '数据列表', 'page' => $page, 'pagecount' => ceil($total / $limit), 'limit' => $limit, 'total' => $total, 'list' => $list];
    }

    public function listCacheData($lp)
    {
        if (!is_array($lp)) {
            $lp = json_decode($lp, true);
        }

        $order = $lp['order'];
        $by = $lp['by'];
        $type = $lp['type'];
        $start = intval(abs($lp['start']));
        $num = intval(abs($lp['num']));
        $cachetime = $lp['cachetime'];

        $page = 1;
        $where = [];

        if (empty($num)) {
            $num = 20;
        }
        if ($start > 1) {
            $start--;
        }
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }
        if (!in_array($by, ['id', 'sort'])) {
            $by = 'id';
        }
        if (!empty($type)) {
            if ($type == 'current') {
                $type = intval($GLOBALS['type_id']);
            }
        }
        $where['type_id'] = $type;

        $order = $by . ' ' . $order;

        $cach_name = $GLOBALS['config']['app']['cache_flag'] . '_' . md5('vod_recommend_listcache_' . join('&', $where) . '_' . $order . '_' . $page . '_' . $num . '_' . $start);
        $res = Cache::get($cach_name);
        if ($GLOBALS['config']['app']['cache_core'] == 0 || empty($res)) {
            $res = $this->listData($where, $order, $page, $num, $start);
            $cache_time = $GLOBALS['config']['app']['cache_time'];
            if (intval($cachetime) > 0) {
                $cache_time = $cachetime;
            }
            if ($GLOBALS['config']['app']['cache_core'] == 1) {
                Cache::set($cach_name, $res, $cache_time);
            }
        }
        if ($res['list']) {
            foreach ($res['list'] as &$v) {
                if ($v['rel_ids']) {
                    $arr_vod_ids = explode(',', $v['rel_ids']);
                    $lp = [
                        'ids' => $v['rel_ids']
                    ];
                    $vods = array_column(model("Vod")->listCacheData($lp)['list'], null, 'vod_id');
                    foreach ($arr_vod_ids as $vod_id) {
                        // $v['rel_vod'][] = Arr::only($vods[$vod_id], ['vod_id', 'vod_name', 'vod_pic']);
                        if (isset($vods[$vod_id])) {
                            $v['rel_vod'][] = $vods[$vod_id];
                        }
                    }
                }

            }
            unset($v);
        }
        return $res;
    }

    public function infoData($where, $field = '*')
    {
        if (empty($where) || !is_array($where)) {
            return ['code' => 1001, 'msg' => '参数错误'];
        }
        $info = $this->field($field)->where($where)->find();

        if (empty($info)) {
            return ['code' => 1002, 'msg' => '获取数据失败'];
        }
        $info = $info->toArray();

        return ['code' => 1, 'msg' => '获取成功', 'info' => $info];
    }

    public function saveData($data)
    {
        $validate = \think\Loader::validate('video');
        if(!$validate->check($data)){
            return ['code'=>1001,'msg'=>'参数错误：'.$validate->getError() ];
        }
        $key = 'video_detail_'.$data['video_id'];
        Cache::rm($key);
        $key = 'video_detail_'.$data['vod_en'];
        Cache::rm($key);
        $key = 'video_detail_'.$data['video_id'].'_'.$data['vod_en'];
        Cache::rm($key);

        //分类
        if(mac_get_type_list() != false){
            $new_table =  mac_get_type_list_model();
            $model =  new \app\common\model\TypeSeo($new_table);
            $type_list = $model->getCache();
        }else{
            $type_list = model('Type')->getCache('type_list');
        }
        $type_info = $type_list[$data['type_id']];
        $data['type_pid'] = $type_info['type_pid'];

        if(empty($data['vod_en'])){
            $data['vod_en'] = Pinyin::get($data['vod_name']);
        }

        if(empty($data['vod_blurb'])){
            $data['vod_blurb'] = mac_substring( strip_tags($data['vod_content']) ,100);
        }

        if($data['uptime']==1){
            $data['vod_time'] = time();
        }
        if($data['uptag']==1){
            $data['vod_tag'] = mac_get_tag($data['vod_name'], $data['vod_content']);
        }
        unset($data['uptime']);
        unset($data['uptag']);

        Db::startTrans();
        $save_vod = true;
        $save_vedio_vod = true;
        if(!empty($data['id'])){
            $where=[];
            $where['id'] = ['eq',$data['id']];
            $res = $this->allowField(true)->where($where)->update($data);

            $id = $data['id'];

            unset( $data['id'] );

            $data['up_time'] = time();
            // 修改vedio_vod表信息
            $save_vedio_vod = model('video_vod')->allowField(true)->where( ['video_id' => $id] )->update( $data );

            unset( $data['up_time'] );
            
            // 根据video_id获取任务标中的vod_id
            $video_vod_data = Db::table('video_vod')->field('id,vod_id')->where( ['video_id' => $id] )->find();

            if (empty( $video_vod_data['vod_id'] )) {
                Db::rollback();
                return ['code'=>1002,'msg'=>'保存失败：缺失vod_id'];
            }

            // 更新主表数据 即vod表
            $data['vod_id'] = $video_vod_data['vod_id'];
            $data['vod_time'] = time();
            $save_vod = model('vod')->allowField(true)->update( $data );
            unset( $data['vod_time'] );
        }
        else{
            $data['vod_time_add'] = time();
            $data['vod_time'] = time();
            $res = $this->allowField(true)->insert($data);
        }
        if(false === $res && $save_vod === false && $save_vedio_vod === false){
            Db::rollback();
            return ['code'=>1002,'msg'=>'保存失败：'.$this->getError() ];
        }
        Db::commit(); 
        return ['code'=>1,'msg'=>'保存成功'];
    }

    public function delData($where)
    {
        $res = $this->where($where)->delete();
        if ($res === false) {
            return ['code' => 1001, 'msg' => '删除失败：' . $this->getError()];
        }
        return ['code' => 1, 'msg' => '删除成功'];
    }

    public function fieldData($where, $col, $val)
    {
        if (!isset($col) || !isset($val)) {
            return ['code' => 1001, 'msg' => '参数错误'];
        }

        $data = [];
        $data[$col] = $val;
        $res = $this->allowField(true)->where($where)->update($data);
        if ($res === false) {
            return ['code' => 1001, 'msg' => '设置失败：' . $this->getError()];
        }
        return ['code' => 1, 'msg' => '设置成功'];
    }

}