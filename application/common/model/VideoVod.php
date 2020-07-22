<?php
namespace app\common\model;
use think\Db;
use think\Cache;
use think\helper\Arr;
use think\Log;

class videoVod extends Base {
    // 设置数据表（不含前缀）
    protected $name = 'video_vod';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

    // 自动完成
    protected $auto       = [];
    protected $insert     = [];
    protected $update     = [];

    public function listData($whereOr =[],$where,$order,$page=1,$limit=20,$start=0)
    {
        log::write('点击事件'.msectime());

        log::write('域名表开始--'.msectime());
        $video_domain = Db::table('video_domain')->find();
        log::write('域名表结束-'.Db::table('video_domain')->getLastSql().'-'.msectime());

        log::write('审核表开始--'.msectime());
        $video_examine = Db::table('video_examine')->column(null,'id');
        log::write('审核表结束-'.Db::table('video_examine')->getLastSql().'-'.msectime());

        if(!is_array($where)){
            $where = json_decode($where,true);
        }
        $limit_str = ($limit * ($page-1) + $start) .",".$limit;
        log::write('两表联查sql开始--'.msectime());
        if(empty($whereOr)){
            $total = Db::table('video_vod')->alias('b')->field('a.vod_name,b.id as b_id,b.examine_id as b_examine_id,b.sum as b_sum,b.is_examine as b_is_examine,b.is_section as b_is_section,b.reason as b_reason,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.down_time as b_down_time,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync')->join('vod a', 'b.vod_id=a.vod_id', 'left')->where($where)->order($order)->limit($limit_str)->count();
            $list = Db::table('video_vod')->alias('b')->field('a.vod_name,b.id as b_id,b.examine_id as b_examine_id,b.sum as b_sum,b.is_examine as b_is_examine,b.is_section as b_is_section,b.reason as b_reason,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.down_time as b_down_time,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync')->join('vod a', 'b.vod_id=a.vod_id', 'left')->where($where)->order($order)->limit($limit_str)->select();
        }else{
            $total = Db::table('video_vod')->alias('b')->field('a.vod_name,b.id as b_id,b.examine_id as b_examine_id,b.sum as b_sum,b.is_examine as b_is_examine,b.is_section as b_is_section,b.reason as b_reason,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.down_time as b_down_time,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync')->join('vod a', 'b.vod_id=a.vod_id', 'left')->whereOr($whereOr)->where($where)->order($order)->limit($limit_str)->count();
            $list = Db::table('video_vod')->alias('b')->field('a.vod_name,b.id as b_id,b.examine_id as b_examine_id,b.sum as b_sum,b.is_examine as b_is_examine,b.is_section as b_is_section,b.reason as b_reason,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.down_time as b_down_time,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync')->join('vod a', 'b.vod_id=a.vod_id', 'left')->whereOr($whereOr)->where($where)->order($order)->limit($limit_str)->select();
        }
        log::write('两表联查sql开始-'.Db::table('video_vod')->getLastSql().'-'.msectime());
//        p(Db::table('video_vod')->getLastSql());
        $listId= array_column($list,'b_id');
        $listId= array_diff($listId, [0]);
        $where_collection = [];
        $where_collection['task_id'] = ['in',$listId];
//        p(1);
        log::write('集的表查询sql开始-'.msectime());
        $collection_list =  Db::table('video_collection')->where($where_collection)->column(null,'task_id');
        log::write('集的表查询sql结束-'.Db::table('video_collection')->getLastSql().'-'.msectime());
        log::write('数组处理开始-'.'-'.msectime());
        foreach ($list as $k=>$v){
            $list[$k]['examine_txt'] = '';
            $list[$k]['mu_url'] = '';
            if (isset($collection_list[$v['b_id']])){
                $list[$k]['mu_url'] = $video_domain['vod_domain'] . $collection_list[$v['b_id']]['vod_url'];
            }
            if($v['b_examine_id'] != 0){
                if(isset($video_examine[$v['b_examine_id']])){
                    $list[$k]['examine_txt'] = $video_examine[$v['b_examine_id']];
                }
            }
        }
        log::write('数组处理结束-'.'-'.msectime());
        //分类
        return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>ceil($total/$limit),'limit'=>$limit,'total'=>$total,'list'=>$list];
    }

    public function listData1($where,$order,$page=1,$limit=20,$start=0)
    {
        if(!is_array($where)){
            $where = json_decode($where,true);
        }
        $limit_str = ($limit * ($page-1) + $start) .",".$limit;
        $total = Db::table('video_examine')->where($where)->order($order)->count();
        $list = Db::table('video_examine')->where($where)->order($order)->limit($limit_str)->select();
        return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>ceil($total/$limit),'limit'=>$limit,'total'=>$total,'list'=>$list];
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

        if(empty($num)){
            $num = 20;
        }
        if($start>1){
            $start--;
        }
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }
        if (!in_array($by, ['id', 'sort'])) {
            $by = 'id';
        }
        if (!empty($type)) {
            if($type=='current'){
                $type = intval( $GLOBALS['type_id'] );
            }
        }
        $where['type_id'] = $type;

        $order = $by . ' ' . $order;

        $cach_name = $GLOBALS['config']['app']['cache_flag']. '_' . md5('vod_recommend_listcache_'.join('&',$where).'_'.$order.'_'.$page.'_'.$num.'_'.$start);
        $res = Cache::get($cach_name);
        if($GLOBALS['config']['app']['cache_core']==0 || empty($res)) {
            $res = $this->listData($where, $order, $page, $num, $start);
            $cache_time = $GLOBALS['config']['app']['cache_time'];
            if(intval($cachetime)>0){
                $cache_time = $cachetime;
            }
            if($GLOBALS['config']['app']['cache_core']==1) {
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

    public function infoData($where,$field='*')
    {
        if(empty($where) || !is_array($where)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }
        $info = $this->field($field)->where($where)->find();

        if(empty($info)){
            return ['code'=>1002,'msg'=>'获取数据失败'];
        }
        $info = $info->toArray();

        return ['code'=>1,'msg'=>'获取成功','info'=>$info];
    }

    public function saveData($data)
    {
        $data['down_time'] = time();
        if(!empty($data['id'])){
            $where=[];
            $where['id'] = ['eq',$data['id']];
            unset($data['down_time']);
            $res = $this->allowField(true)->where($where)->update($data);
        }
        else{
            $res = $this->allowField(true)->insert($data);
        }
        if(false === $res){
            return ['code'=>1002,'msg'=>'保存失败：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'保存成功'];
    }

    public function delData($where)
    {
        $res = $this->where($where)->delete();
        if($res===false){
            return ['code'=>1001,'msg'=>'删除失败：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'删除成功'];
    }

    public function fieldData($where,$col,$val)
    {
        if(!isset($col) || !isset($val)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }

        $data = [];
        $data[$col] = $val;
        $res = $this->allowField(true)->where($where)->update($data);
        if($res===false){
            return ['code'=>1001,'msg'=>'设置失败：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'设置成功'];
    }

}