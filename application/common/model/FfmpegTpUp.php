<?php

namespace app\common\model;

use think\Db;
use think\Cache;
use think\helper\Arr;

class FfmpegTpUp extends Base {

    // 设置数据表（不含前缀）
    protected $name = 'ffmpeg_to_up';



    // 自动完成
    protected $auto = [];
    protected $insert = [];
    protected $update = [];

    public function listData( $where,$order,$page=1,$limit=20,$start=0)
    {


        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = Db::name('ffmpeg_to_up')
                    ->where( $where )->count();

        $list = Db::name('ffmpeg_to_up')
                ->where( $where )
                ->order( $order )->limit( $limit_str )->select();
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

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = Db::name('video_selected_task')
            ->where( $where )->count();

        $list = Db::name('video_selected_task')
            ->where( $where )
            ->order( $order )->limit( $limit_str )->select();
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