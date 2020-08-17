<?php

namespace app\common\model;

use think\Db;
use think\Cache;
use think\helper\Arr;

class Svideo extends Base {

    // 设置数据表（不含前缀）
    protected $name = 'svideo';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

    // 自动完成
    protected $auto = [];
    protected $insert = [];
    protected $update = [];

    public function listData($whereOr = [], $where,$order,$page=1,$limit=20,$start=0)
    {

        // if (empty($whereOr) && empty($where)) {
        //     return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>0,'limit'=>$limit,'total'=>0,'list'=>[]];
        // }

        $video_domain = Db::table('video_domain')->find();
        $video_examine = Db::table('video_examine')->column(null,'id');

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;

        $field_a = "id,vod_id,count('vod_id') as total,name";

        $field_b = 'id,vod_id,name,collection,pic_url,play_url,local_file_describe,status,is_examine,e_id';

        $total = Db::name('Svideo')
                    ->where( $whereOr )
                    ->where( $where )->group('vod_id')->limit($limit_str)->count();

        $videos = Db::name('Svideo')
                ->field( $field_a )
                ->where( $whereOr )
                ->where( $where )
                ->order( $order )->group('vod_id')->limit( $limit_str )->select();
        $list = [];
        $vod_ids = array_column($videos, 'vod_id');

        if (!empty($vod_ids)) {

            $video_collections = Db::name('Svideo')
                    ->field( $field_b )
                    ->where( $whereOr )
                    ->where( $where )
                    ->where('vod_id', 'in', $vod_ids)
                    ->order( 'collection asc' )
                    ->select();
            foreach ($videos as $v) {
                // 获取视频总集数
                $video_total = $v['total'];
                $where['vod_id'] = $v['vod_id'];
                if (isset($where['status']) && $where['status'] == 1) {
                    $on_line_video = $video_total;
                    $off_line_video = 0;
                } else if (isset($where['status']) && $where['status'] == 2) {
                    $on_line_video = 0;
                    $off_line_video = $video_total;
                } else {
                    $on_line_video = $this->where( $whereOr )->where( $where )->where( 'status', 1 )->count();
                    $status_is_zero = $this->where( $whereOr )->where( $where )->where( 'status', 0 )->count();
                    if ($on_line_video == 0 && $status_is_zero == $video_total) {
                        $off_line_video = 0;
                    } else {
                        $off_line_video = $video_total - $on_line_video;
                    }
                }

                $list[] = [
                        'bid' => $v['vod_id'] . '_' . $v['vod_id'],
                        'name' => $v['name'],
                        'vod_id' => $v['vod_id'],
                        'is_master' => 1,
                        'collection' => $video_total,
                        'pid' => 0,
                        'pic_url' => '',
                        'id' => '',
                        'vod_url' => '',
                        'status' => '',
                        'is_examine' => '',
                        'on_line_video' => $on_line_video,
                        'off_line_video' => $off_line_video,
                        'local_file_describe' => '',
                        'reasons' => ''
                    ]; 
            }
            foreach ($video_collections as $v1) {
                if (substr_count($v1['pic_url'], 'http') == 0) {
                    $pic_url = $video_domain['img_domain'] . $v1['pic_url'];
                } else {
                    $pic_url = $v1['pic_url'];
                }
                $vod_id = $v1['vod_id'];
                $v1['pid'] = $v1['vod_id'] . '_' . $v1['vod_id'];
                $v1['vod_id'] = $vod_id;
                $v1['is_master'] = 0;
                $v1['pic_url'] = $pic_url;
                $v1['name'] = $v1['name'];
                $v1['vod_url'] = '';
                $v1['bid'] = $v1['id'];
                $v1['reasons'] = isset($video_examine[$v1['e_id']])?$video_examine[$v1['e_id']]:'';
                $list[] = $v1;
            }
        }
        return ['code' => 1, 'msg' => '数据列表', 'page' => $page, 'pagecount' => ceil($total / $limit), 'limit' => $limit, 'total' => $total, 'list' => $list];
    }

    /**
     * 审核原因
     * @param  [type]  $where [description]
     * @param  [type]  $order [description]
     * @param  integer $page  [description]
     * @param  integer $limit [description]
     * @param  integer $start [description]
     * @return [type]         [description]
     */
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

    /**
     * 视频详情
     * @param  [type] $where [description]
     * @param  string $field [description]
     * @return [type]        [description]
     */
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

    /**
     * 保存
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function saveData($data)
    {
        $validate = \think\Loader::validate('Svideo');
        if(!$validate->check($data)){
            return ['code'=>1001,'msg'=>'参数错误：'.$validate->getError() ];
        }

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

}