<?php
namespace app\common\model;
use think\Db;
use think\Cache;
use think\helper\Arr;

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
        if(!is_array($where)){
            $where = json_decode($where,true);
        }
        $limit_str = ($limit * ($page-1) + $start) .",".$limit;
        if(empty($whereOr)){
            $total = Db::name('Vod')->alias('a')->field('a.vod_name,b.id as b_id,b.is_section as b_is_section,b.reason as b_reason,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'right')->where($where)->order($order)->limit($limit_str)->count();
            $list = Db::name('Vod')->alias('a')->field('a.vod_name,b.id as b_id,b.set_down_url as b_set_down_url,b.examine_id as b_examine_id,b.is_examine as b_is_examine,b.is_section as b_is_section,b.reason as b_reason,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.down_time as b_down_time,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'right')->where($where)->order($order)->limit($limit_str)->select();

        }else{
            $total = Db::name('Vod')->alias('a')->field('a.vod_name,b.id as b_id,b.is_section as b_is_section,b.reason as b_reason,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'right')->where($where)->whereOr($whereOr)->order($order)->limit($limit_str)->count();
            $list = Db::name('Vod')->alias('a')->field('a.vod_name,b.id as b_id,b.set_down_url as b_set_down_url,b.examine_id as b_examine_id,b.is_examine as b_is_examine,b.is_section as b_is_section,b.reason as b_reason,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.down_time as b_down_time,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'right')->whereOr($whereOr)->where($where)->order($order)->limit($limit_str)->select();

        }

//        $list = Db::name('Vod')->getLas
//        p( Db::name('Vod')->getLastSql());
         $video_domain = Db::table('video_domain')->find();

//        p($list);
        foreach ($list as &$v){
            $b_set_down_url =  mac_json_decode($v['b_set_down_url']);
            $where_collection = [];
            $where_collection['video_id'] = $v['b_video_id'];
            $where_collection['collection'] = 1;
            $video_collection =   Db::table('video_collection')->where($where_collection)->find();
            $v['examine_txt'] = '';
            if($v['b_examine_id'] != 0){
                $video_examine = [];
                $video_examine['id']= $v['b_examine_id'];
                $video_examine_data =   Db::table('video_examine')->where($where_collection)->find();
                if(!empty($video_examine_data)){
                    $v['examine_txt'] = $video_examine_data['reasons'];
                }
            }
            if(empty($video_collection)){
                $v['mu_url'] = '';
            }else{
                $v['mu_url'] = $video_domain['vod_domain'] . $video_collection['vod_url'];
            }
            $v['surl']=  implode("@@@",$b_set_down_url);
        }
//        p($list);
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