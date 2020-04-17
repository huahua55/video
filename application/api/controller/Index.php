<?php
namespace app\api\controller;
use think\Controller;
use think\Cache;

class Index extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->_param = input();
    }

    // 首页导航
    public function home_nav(){
        $lp = [
            'type_status'   => 1,
            'type_pid'      => 0,
        ];
        $list  = model("Type")->listData($lp,"type_id asc");

        $data[] = ['id'    => 0, 'name'  => "推荐",'img'   => "",];
        $list = $list['list'] ?? [];
        $array = [];



        foreach($list as $key=>$item){
            $array[$key]['id']      = $item['type_id'];
            $array[$key]['name']    = $item['type_name'];
            $array[$key]['img']     = $item['img'] ?? "";
            $array[$key]['msg']     = type_extend($item['type_extend']) ?? [];
        }
        $list = array_merge($data , $array);
        return json_return($list);
    }

    // 首页导航下数据
    public function home_data(){
        $id = $this->_param['id'] ?? 0;

        // 轮播图
        $lp = [
            'status'   => 1,
            'type_id'  => $id,
        ];
        $bannel = model("Banner")->listData($lp,"sort desc");
        $bannel = $bannel['list'] ?? [];
        $getSlide = [];
        foreach($bannel as $k=>$item){
            $getSlide[$k]['id']     = $item['id'];
            $getSlide[$k]['name']   = $item['name'];
            $getSlide[$k]['img']    = imageDir($item['img']);
            $getSlide[$k]['url']    = $item['link'];
            $getSlide[$k]['type']   = 1;
        }

        $getListBlock = [];
        // 内容
        if ($id == 0){
            $getListBlock = $this->tuijian();
        }else{
            $where = [
                'type_pid'   => $id
            ];
            $res =  model("Type")->listData($where,"type_sort desc");
            $res = $res['list'] ?? [];
            if($res == []){
                $res =  model("Type")->listData(['type_id' => $id],"type_sort desc");
                $res = $res['list'] ?? [];
            }
            foreach($res as $item){
                $r = $item["type_id"];
                $d = array(
                    'name' => $item['type_name'],
                    'data' => $this->getVodList($r,6,1),
                );

                array_push($getListBlock,$d);
            }
        }

        $list = array(
            'name'  => '',
            'slide' => $getSlide,
            'video' => $getListBlock,
        );

        return json_return($list);
    }

    // 推荐
    public function tuijian(){
        $data = array(
            array(
                'id'    => 1,
                'msg'   => json_encode(getScreen(1),JSON_UNESCAPED_UNICODE),
                'name'  => '热播电影',
                'data'  => $this->getVodList(1,6,1),
            ),
            array(
                'id'    => 2,
                'msg'   => json_encode(getScreen(2),JSON_UNESCAPED_UNICODE),
                'name'  => '热播剧',
                'data'  => $this->getVodList(2,6,1),
            ),
            array(
                'id'    => 3,
                'msg'   => json_encode(getScreen(3),JSON_UNESCAPED_UNICODE),
                'name'  => '热播动漫',
                'data'  => $this->getVodList(3,6,1),
            ),
            array(
                'id'    => 4,
                'msg'   => json_encode(getScreen(4),JSON_UNESCAPED_UNICODE),
                'name'  => '热播综艺',
                'data'  => $this->getVodList(4,6,1),
            ),
        );
        return $data;
    }

    // 分类视频
    public function getVodList($id,$limit,$page){
        $lp = [
            'type_id'   => $id,
        ];
        $info = model("Vod")->listData($lp, "vod_level desc",$page,$limit);

        $info = $info['list'] ?? [];
        $array = array();
        foreach($info as $r){
            $msg = $r['vod_continu'];
            if ($msg == null || $msg == 0){
                $msg = $r['vod_year'];
            }else{
                $msg = "更新至".$msg."集";
            }
            $d = array(
                'img'=> imageDir($r['vod_pic']),
                'id' => $r['vod_id'],
                'name'=>$r['vod_name'],
                'score'=>$r['vod_score'],
                'msg'=>$msg,
            );
            array_push($array,$d);
        }
        return $array;
    }


    // 详情
    public function vod(){
        $id = $this->_param['id'] ?? 0;

        $where['vod_status'] = array('eq', 1);
        $where['vod_id']     = array('eq', $id);
        $info = model("Vod")->infoData($where);
        $info = $info['info'] ?? [];

        $data = array(
            'name'      => $info["vod_name"],
            'img'       => imageDir($info["vod_pic"]),
            'msg'       => $info["vod_year"],
            'score'     => $info["vod_score"],
            'type'      => $info["vod_area"] ,
            'info'      => $info["vod_content"],
            'playcode'  => $info["vod_play_from"],
            'playlist'  => $info["vod_play_url"],
        );
        return json_return($data);
    }

    // 详情
    public function search(){
        $key = $this->_param['key'] ?? "";

        $where = [
            "vod_name|vod_sub|vod_actor|vod_director"  => ["like", '%'.$key.'%'],
        ];
        $res = model("Vod")->listData($where, "vod_level desc");
        $res = $res['list'] ?? [];

        $data = [];
        foreach($res as $r){
            $d = array(
                'img'   => imageDir($r['vod_pic']),
                'name'  => $r['vod_name'],
                'msg'   => $r['vod_content'],
                'url'   => $r['vod_id'],
            );
            array_push($data,$d);
        }
        return json_return($data);
    }


    // 筛选
    public function screen(){
        $id     = $this->_param['id'] ?? 0;
        $page   = $this->_param['page'] ?? 1;
        $type   = $this->_param['type'] ?? "";
        $area   = $this->_param['area'] ?? "";
        $year   = $this->_param['year'] ?? "";

        $type = $type == "类型" ? "" : $type;
        $area = $area == "地区" ? "" : $area;
        $year = $year == "年份" ? "" : $year;

        $where = [];
        if($id != 0){
            $where['type_id']   = ['eq',$id];
        }

        if($type != ""){
            $where['vod_class']   = ['eq',$type];
        }

        if($area != ""){
            $where['vod_area']   = ['eq',$area];
        }

        if($year != ""){
            $where['vod_year']   = ['eq',$year];
        }

        $info = model("Vod")->listData($where, "vod_level desc", $page);
        $info = $info['list'] ?? [];

        $array = array();
        foreach($info as $r){
            $msg = $r['vod_continu'] ?? "";
            if ($msg == "" || $msg == 0){
                $msg = $r['vod_year'];
            }else{
                $msg = "更新至".$msg."集";
            }
            $d = array(
                'img'   => imageDir($r['vod_pic']),
                'id'    => $r['vod_id'],
                'name'  => $r['vod_name'],
                'score' => $r['vod_score'],
                'msg'   => $msg,
            );
            array_push($array,$d);
        }
        return json_return($array);
    }

}
