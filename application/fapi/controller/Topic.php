<?php
namespace app\fapi\controller;
use think\Controller;
use think\helper\Arr;

class Topic extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->label_topic_index();
        $lp = [
            'num' => 5,
            'paging' => 'yes',
            'order' => 'asc',
            'by' => 'level'
        ];
        $list = model("Topic")->listCacheData($lp);
        $list['list'] = array_values($list['list']);
        if ($list['list']) {
            foreach ($list['list'] as &$v) {
                $v['topic_pic'] = mac_url_img($v['topic_pic']);
                unset($v);
            }
        }
        $this->assign('list', $list);
        return $this->context->as_array();
    }

    public function search()
    {
        $param = mac_param_url();
        $this->check_search($param);
        $this->assign('param',$param);
        return $this->context->as_array();
    }

    public function detail()
    {
        $this->label_topic_detail();
        if ($this->context->obj['vod_list']) {
            foreach ($this->context->obj['vod_list'] as &$v) {
                $v = Arr::only($v, ['vod_id', 'vod_name', 'vod_pic']);
                unset($v);
            }
        }
        return $this->context->as_array();
    }

}
