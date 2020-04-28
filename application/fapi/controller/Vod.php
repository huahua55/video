<?php

namespace app\fapi\controller;

class Vod extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    // 首页
    public function index()
    {
        // banner
        $lp = [
            'type_id' => 0,
            'order' => 'asc',
            'by' => 'sort',
        ];
        $nav = model("Banner")->listCacheData($lp)['list'];
        $this->assign('banner', $nav);
        // 公告
        $lp = [
            'type' => 17,
            'order' => 'asc',
            'by' => 'level',
        ];
        $art = model("Art")->listCacheData($lp)['list'];
        $this->assign('art', $art);

        // 热门推荐
        $lp = [
            'type_id' => 0,
            'order' => 'asc',
            'by' => 'sort',
        ];
        $recommend = model("VodRecommend")->listCacheData($lp)['list'];
        $this->assign('vod_recommend', $recommend);

        return $this->context->as_array();
    }

    // 视频分类首页
    public function type()
    {
        $this->label_type();
        $param = $this->context->param;
        // banner
        $lp = [
            'type_id' => $param['id'],
            'order' => 'asc',
            'by' => 'sort',
        ];
        $nav = model("Banner")->listCacheData($lp)['list'];
        $this->assign('banner', $nav);

        // 热门推荐
        $lp = [
            'type_id' => $param['id'],
            'order' => 'asc',
            'by' => 'sort',
        ];
        $recommend = model("VodRecommend")->listCacheData($lp)['list'];
        $this->assign('vod_recommend', $recommend);
        return $this->context->as_array();
    }

    // 视频分类筛选页
    public function show()
    {
        $this->label_type();
        $lp = [
            'num' => 20,
            'paging' => 'yes',
            'type' => 'current',
            'order' => 'desc',
            'by' => 'time'

        ];
        $list = model("Vod")->listCacheData($lp);
        $this->assign('list', $list);

        return $this->context->as_array();
    }

    public function search()
    {
        $param = mac_param_url();
        $this->check_search($param);
        $this->assign('param',$param);
        $lp = [
            'num' => 10,
            'paging' => 'yes',
            'order' => 'desc',
            'by' => 'time'

        ];
        $list = model("Vod")->listCacheData($lp);
        if ($list['list']) {
            foreach ($list['list'] as &$v) {
                unset($v['vod_play_url']);
            }
        }
        $this->assign('list', $list);
        return $this->context->as_array();
    }

    public function play()
    {
        $this->label_vod_play();
        return $this->context->as_array();
    }

}
