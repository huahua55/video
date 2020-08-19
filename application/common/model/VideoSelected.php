<?php

namespace app\common\model;

use think\Db;
use think\Cache;
use think\helper\Arr;

class VideoSelected extends Base
{
    // 设置数据表（不含前缀）
    protected $name = 'video_selected';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

    // 自动完成
    protected $auto = [];
    protected $insert = [];
    protected $update = [];

    public function listData($whereOr = [], $where, $order, $page = 1, $limit = 20, $start = 0)
    {

        if (empty($whereOr) && empty($where['where_a']) && empty($where['where_b'])) {
            return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>0,'limit'=>$limit,'total'=>0,'list'=>[]];
        }
        $video_domain = Db::table('video_domain')->where('type', 2)->find();
        $video_examine = Db::table('video_examine')->column(null,'id');
        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        //a.id,a.type_pid,a.type_id,a.vod_name,a.vod_sub,a.vod_en,a.vod_tag,a.vod_pic,a.vod_pic_thumb,a.vod_pic_slide,a.vod_actor,a.vod_director,a.vod_writer,a.vod_behind,a.vod_blurb,a.vod_remarks,a.vod_pubdate,a.vod_total,a.vod_serial,a.vod_tv,a.vod_weekday,a.vod_area,a.vod_lang,a.vod_year,a.vod_version,a.vod_state,a.vod_duration,a.vod_isend,a.vod_douban_id,a.vod_douban_score,a.vod_time,a.vod_time_add,a.is_from,a.is_examine,a.vod_status,a.vod_time_auto_up
        //b.id,b.video_id,b.task_id,b.title,b.collection,b.vod_url,b.type,b.status,b.e_id,b.is_examine,b.resolution,b.bitrate,b.duration,b.size,b.time_up,b.time_auto_up
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        
        $field_a = 'a.id as aid,a.type_pid,a.type_id,a.vod_name,a.vod_sub,a.vod_en,a.vod_tag,a.vod_pic,a.vod_pic_thumb,a.vod_pic_slide,a.vod_actor,a.e_id,a.vod_director,a.vod_writer,a.vod_behind,a.vod_blurb,a.vod_remarks,a.vod_pubdate,a.vod_total,a.vod_serial,a.vod_tv,a.vod_weekday,a.vod_area,a.vod_lang,a.vod_year,a.vod_version,a.vod_state,a.vod_duration,a.vod_isend,a.vod_douban_id,a.vod_douban_score,a.vod_time,a.vod_time_add,a.is_from,a.is_examine,a.vod_status,a.vod_time_auto_up,a.vod_id';

        $field_b = 'b.id as bid,b.video_id,b.task_id,b.title,b.collection,b.vod_url,b.type,b.status,b.e_id as b_eid,b.is_examine as b_is_examine,b.resolution,b.bitrate,b.duration,b.size,b.time_up,b.time_auto_up,b.is_replace,b.is_sync';

        // 获取未上传的video_id
        if ( !empty( $where['where_b'] ) ) {
            $video_ids = Db::name('video_collection_selected')->alias('b')->field('b.video_id')->where($where['where_b'])->group('b.video_id')->select();
            $video_ids = array_column($video_ids, 'video_id');
            $where['where_a']['a.id'] = ['in', $video_ids];
        }

        $total = Db::name('VideoSelected')
                    ->alias( 'a' )
                    ->where(function ($query) use ($whereOr) {
                        $query->whereOr( $whereOr );
                    })
                    ->where( $where['where_a'] )->limit($limit_str)->count();
        $videos = Db::name('VideoSelected')
                ->alias( 'a' )
                ->field( $field_a )
                ->where(function ($query) use ($whereOr) {
                    $query->whereOr( $whereOr );
                })
                ->where( $where['where_a'] )
                ->order( $order )->limit( $limit_str )->select();
        $list = [];

        $where_b = $where['where_b'];

        if (isset($where['where_a']['a.vod_status']) && $where['where_a']['a.vod_status'] != "") {
            $where_b['b.status'] = $where['where_a']['a.vod_status'];
        }
        if (isset($where['where_a']['a.is_examine']) && $where['where_a']['a.is_examine'] != "") {
            $where_b['b.is_examine'] = $where['where_a']['a.is_examine'];
        }
        
        foreach ($videos as $v) {

            // 主集
            $video_collection_count = Db::name('video_collection_selected')
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
                    $video_total = Db::name('vod')
                            ->where( 'vod_id', $v['vod_id'] )
                            ->column('vod_total')[0];
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

            $video_collection = Db::name('video_collection_selected')
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
        // 获取图片域名
        $video_img_domain = Db::table('video_domain')->field('img_domain')->find();
        $info['video_img_domain'] = $video_img_domain['img_domain'];

        return ['code' => 1, 'msg' => '获取成功', 'info' => $info];
    }

    public function saveData($data)
    {
        $validate = \think\Loader::validate('video_selected');
        if(!$validate->check($data)){
            return ['code'=>1001,'msg'=>'参数错误：'.$validate->getError() ];
        }
        $key = 'video_selected_'.$data['id'];
        Cache::rm($key);
        $key = 'video_selected_'.$data['vod_en'];
        Cache::rm($key);
        $key = 'video_selected_'.$data['id'].'_'.$data['vod_en'];
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
        if(!empty($data['id'])){
            $where=[];
            $where['id'] = ['eq',$data['id']];
            $res = $this->allowField(true)->where($where)->update($data);

            // 根据video_id获取任务标中的vod_id
            $video_selected_data = $this->field('vod_id')->where( $where )->find();

            if (empty( $video_selected_data['vod_id'] )) {
                Db::rollback();
                return ['code'=>1002,'msg'=>'保存失败：缺失vod_id'];
            }

            // 更新主表数据 即vod表
            $data['vod_id'] = $video_selected_data['vod_id'];
            $data['vod_time'] = time();
            $save_vod = model('vod')->allowField(true)->update( $data );
        }
        else{
            $data['vod_time_add'] = time();
            $data['vod_time'] = time();
            $res = $this->allowField(true)->insert($data);
        }
        if(false === $res && $save_vod === false){
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

    /**
     * 根据集id替换普通视频
     * @param  int $id 视频集id
     * @return [type]     [description]
     *
     * 流程：：
     * 
     * 1、根据集id获取vod_url、集collection、视频video_id
     * 2、根据视频id去video_selected表获取vod_id
     * 3、根据vod_id去普通视频表video中查询数据是否存在
     *     不存在
     *         返回错误并提示没有关联到视频
     *     存在
     *         4、根据id查询普通视频集表中查询数据是否存在
     *             存在
     *                 5、根据id、集collection去普通视频集表中查询数据是否存在
     *                     存在  替换vod_url
     *                         成功
     *                             提交事务
     *                             6、更新普通视频集表、修改精选集表is_replace为1(已替换)、修改普通视频集表is_selected为1(是精选)、修改普通视频表vod_time_auto_up为当前时间、如果是电影修改普通视频表is_selected为1是精选
     *                         失败
     *                             回滚事务
     *                      不存在  插入该条数据
     *                          成功
     *                              提交事务
     *                              7、更新普通视频集表、修改精选集表is_replace为1(已替换)、修改普通视频集表is_selected为1(是精选)、修改普通视频表vod_time_auto_up为当前时间、如果是电影修改普通视频表is_selected为1是精选
     *                          失败
     *                              回滚事务
     *               不存在
     *                   8、从精选集表中查询出所有的集插入普通视频集表中
     *  
     */
    public function replaceVideo( $id ){

        if (empty($id)) {
            return ['code' => 1001, 'msg' => '缺失集id'];
        }
        // 1、根据集id获取vod_url、集collection、视频video_id
        $selected_collection_where['id'] = $id;
        $selected_collection_where['video_id'] = ['neq', 0];
        $selected_collection_info = Db::table('video_collection_selected')
                                    ->field('vod_url,collection,video_id,type,status,e_id,is_examine,resolution,bitrate,duration,size')
                                    ->where($selected_collection_where)->find();
        if (empty($selected_collection_info)) {
            return ['code' => 1001, 'msg' => '该视频集不存在'];
        }

        if (empty($selected_collection_info['vod_url'])) {
            return ['code' => 1001, 'msg' => '该集地址vod_url不存在'];
        }

        if (empty($selected_collection_info['video_id']) || $selected_collection_info['video_id'] == 0) {
            return ['code' => 1001, 'msg' => '该集未关联视频'];
        }

        if (empty($selected_collection_info['collection']) || $selected_collection_info['collection'] == 0) {
            return ['code' => 1001, 'msg' => '该视频集数为0'];
        }
        // 2、根据视频id去video_selected表获取vod_id
        $selected_video_info = $this->field('vod_id,vod_status,is_examine,e_id,is_from')->where('id', $selected_collection_info['video_id'])->find();
        if (empty($selected_video_info['vod_id']) || $selected_video_info['vod_id'] == 0) {
            return ['code' => 1001, 'msg' => '该精选视频不存在'];
        }
        // 3、根据vod_id去普通视频表video中查询数据是否存在
        $video_info = model('video')
                        ->field('id,type_pid,type_id')
                        ->where('vod_id', $selected_video_info['vod_id'])->find();
        if (empty($video_info)) {
            return ['code' => 1001, 'msg' => '没有关联到普通视频'];
        }

        $replace_video = self::_replaceVideo($id, $selected_collection_info, $video_info, $selected_video_info);

        return $replace_video;
    }

    /**
     * 替换视频
     * @param  [type] $id                       精选视频集i主键d
     * @param  [type] $selected_collection_info 精选视频集信息
     * @param  [type] $video_info               视频信息
     * @return [type]                           [description]
     */
    private function _replaceVideo($id, $selected_collection_info, $video_info, $selected_video_info){
        // 4、根据普通视频id查询普通视频集表中查询数据是否存在
        $video_collection_exist = Db::table('video_collection')
                                    ->field('id')
                                    ->where('video_id', $video_info['id'])->find();
        Db::startTrans();

        $add_video_collection = ['code' => 1, 'msg' => '添加普通视频集成功'];
        $is_replace_all = false;
        $need_edit_video_collection = true;

        if (!empty($video_collection_exist)) {
            $video_collection_where['collection'] = $selected_collection_info['collection'];
            $video_collection_where['video_id'] = $video_info['id'];
            // 根据id、集collection去普通视频集表中查询数据是否存在
            $video_collection_info = Db::table('video_collection')
                                    ->field('id')
                                    ->where($video_collection_where)->find();

            if (empty($video_collection_info)) {
                // 插入该条数据
                $get_selected_collection = self::_getSelectedCollection($id, $video_info['id']);
                if (empty($get_selected_collection)) {
                    Db::rollback();
                    return ['code' => 1001, 'msg' => '要插入的视频集不存在'];
                }
                $add_video_collection = self::_addVideoCollection($get_selected_collection);
                $need_edit_video_collection = false;
            }
        } else {
            $is_replace_all = true;
            $need_edit_video_collection = false;
            // 8、从精选集表中查询出所有的集插入普通视频集表中
            $get_all_selected_collection = self::_getAllSelectedCollection($selected_collection_info['video_id'], $video_info['id']);
            if (empty($get_all_selected_collection)) {
                Db::rollback();
                return ['code' => 1001, 'msg' => '要插入的所有视频集不存在'];
            }
            $add_video_collection = self::_addVideoCollection($get_all_selected_collection);
        }

        if ($add_video_collection['code'] > 1) {
            Db::rollback();
            return $add_video_collection;
        }

        // 7、更新普通视频集表、修改精选集表is_replace为1(已替换)、修改普通视频集表is_selected为1(是精选)、修改普通视频表vod_time_auto_up为当前时间、如果是电影修改普通视频表is_selected为1是精选
        $edit_link_table = self::_editLinkTable($id, 
                                                $selected_collection_info,
                                                $video_info,
                                                $is_replace_all,
                                                $need_edit_video_collection,
                                                $selected_video_info
                                                );
        if ($edit_link_table['code'] == 1) {
            Db::commit();
            return ['code' => 1, 'msg' => '替换成功'];
        } else {
            Db::rollback();
            return $edit_link_table;
        }
    }

    /**
     * 获取精选视频集
     * @param  [type] $selected_video_id 精选视频id
     * @param  [type] $video_id          普通视频id
     * @return [type]                    [description]
     */
    private function _getSelectedCollection($selected_collection_id, $video_id){
        $selected_collection_info = Db::table('video_collection_selected')
                                        ->field('code,title,collection,vod_url,type,status,is_sync,e_id,is_examine,resolution,bitrate,duration,size')
                                        ->where('id', $selected_collection_id)
                                        ->select();
        return self::_filterSelectedCollection($selected_collection_info, $video_id);
        
    }

    /**
     * 获取精选视频所有集
     * @param  [type] $selected_video_id 精选视频id
     * @param  [type] $video_id          普通视频id
     * @return [type]                    [description]
     */
    private function _getAllSelectedCollection($selected_video_id, $video_id){
        $selected_collection_info = Db::table('video_collection_selected')
                                        ->field('code,title,collection,vod_url,type,status,is_sync,e_id,is_examine,resolution,bitrate,duration,size')
                                        ->where('video_id', $selected_video_id)
                                        ->select();
        return self::_filterSelectedCollection($selected_collection_info, $video_id);
        
    }

    /**
     * 过滤精选视频集数据
     * @param  [type] $selected_collection_info [description]
     * @param  [type] $video_id                 [description]
     * @return [type]                           [description]
     */
    private function _filterSelectedCollection($selected_collection_info, $video_id){
        $data = [];
        $video_vod_model = model('video_vod');
        // 过滤视频字段
        foreach ($selected_collection_info as $value) {
            $video_vod_where['video_id'] = $video_id;
            $video_vod_where['collection'] = $value['collection'];
            // 根据普通视频id获取任务id
            $video_vod_info = $video_vod_model->field('id')->where($video_vod_where)->find();
            if (!empty($video_vod_info)) {
                $data[] = [
                    'video_id' => $video_id,
                    'task_id' => $video_vod_info['id'],
                    'title' => $value['title'],
                    'collection' => $value['collection'],
                    'vod_url' => $value['vod_url'],
                    'type' => $value['type'],
                    'status' => $value['status'],
                    'e_id' => $value['e_id'],
                    'is_examine' => $value['is_examine'],
                    'resolution' => $value['resolution'],
                    'bitrate' => $value['bitrate'],
                    'duration' => $value['duration'],
                    'size' => $value['size'],
                    'is_selected' => 1,
                    'time_up' => time()
                ];
            }
        }
        return $data;
    }

    /**
     * 插入普通视频集
     * @param  [type] $selected_video_id 精选视频id
     * @param  [type] $video_id          普通视频id
     * @return [type]                    [description]
     */
    private function _addVideoCollection($data){
        // 插入普通视频
        $add_video_collection = Db::table('video_collection')->insertAll($data);
        if ($add_video_collection != count($data)) {
            return ['code' => 1001, 'msg' => '添加普通视频集失败'];
        }
        return ['code' => 1, 'msg' => '添加普通视频集成功'];
    }

    /**
     * 更新相关联的表
     * @param  [type] $id                       精选视频集i主键d
     * @param  [type] $selected_collection_info 精选视频集信息
     * @param  [type] $video_info               视频信息
     * @return [type]                           [description]
     */
    private function _editLinkTable($id, $selected_collection_info, $video_info, $is_replace_all, $need_edit_video_collection, $selected_video_info){
        // 7、更新普通视频集表、修改精选集表is_replace为1(已替换)、修改普通视频集表is_selected为1(是精选)、修改普通视频表vod_time_auto_up为当前时间、如果是电影修改普通视频表is_selected为1是精选/vod_status/e_id/is_examine/is_from
        
        if ($is_replace_all) {
            // 精选集表
            $video_selected_collection_where['video_id'] = $selected_collection_info['video_id'];
            $edit_selected_collection = Db::table('video_collection_selected')
                                    ->where($video_selected_collection_where)->setField('is_replace', 1);
            if ($edit_selected_collection === false) {
                return ['code' => 1001, 'msg' => '更新精选集表失败'];
            }
        } else {
            // 精选集表
            $video_selected_collection_where['id'] = $id;
            $edit_selected_collection = Db::table('video_collection_selected')
                                    ->where($video_selected_collection_where)->setField('is_replace', 1);
            if ($edit_selected_collection === false) {
                return ['code' => 1001, 'msg' => '更新精选集表失败'];
            }
        }
        if ($need_edit_video_collection) {
            // 普通视频集表
            $video_collection_where['video_id'] = $video_info['id'];
            $video_collection_where['collection'] = $selected_collection_info['collection'];
            $video_collection_data['vod_url'] = $selected_collection_info['vod_url'];
            $video_collection_data['type'] = $selected_collection_info['type'];
            $video_collection_data['status'] = $selected_collection_info['status'];
            $video_collection_data['e_id'] = $selected_collection_info['e_id'];
            $video_collection_data['is_examine'] = $selected_collection_info['is_examine'];
            $video_collection_data['resolution'] = $selected_collection_info['resolution'];
            $video_collection_data['bitrate'] = $selected_collection_info['bitrate'];
            $video_collection_data['duration'] = $selected_collection_info['duration'];
            $video_collection_data['size'] = $selected_collection_info['size'];
            $video_collection_data['is_selected'] = 1;
            $edit_video_collection = Db::table('video_collection')
                                        ->where($video_collection_where)->update($video_collection_data);
            if ($edit_video_collection === false) {
                return ['code' => 1001, 'msg' => '更新普通视频集表失败'];
            }
        }
        
        // 修改普通视频表vod_time_auto_up为当前时间
        $video_where['id'] = $video_info['id'];
        $time = date('Y-m-d H:i:s', time());
        
        // 如果是电影修改普通视频表is_selected为1是精选
        if ($video_info['type_pid'] == 1 || ($video_info['type_pid'] == 0 && $video_info['type_id'] >= 6 && $video_info['type_id'] <= 12)) {
            // 电影
            $video_data['vod_status'] = $selected_video_info['vod_status'];
            $video_data['e_id'] = $selected_video_info['e_id'];
            $video_data['is_examine'] = $selected_video_info['is_examine'];
            $video_data['is_from'] = $selected_video_info['is_from'];
            $video_data['vod_time_auto_up'] = $time;
            $video_data['is_selected'] = 1;
            $edit_video = Db::table('video')
                        ->where($video_where)->update($video_data);
        } else {
            $edit_video = Db::table('video')
                        ->where($video_where)->setField('vod_time_auto_up', $time);
        }
        if ($edit_video === false) {
            return ['code' => 1001, 'msg' => '更新普通视频表自动更新时间失败'];
        }
        return ['code' => 1, 'msg' => '更新成功'];
    }
}