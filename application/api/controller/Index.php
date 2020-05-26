<?php
namespace app\api\controller;
use think\Controller;
use think\Cache;
use think\Db;

class Index extends Base{

    private $sort = [
        1 => "vod_douban_score desc",   // 评分最高
        2 => "vod_pubdate desc"        // 最近更新
    ];

    public function __construct(){
        parent::__construct();
    }

    // 首页导航
    public function home_nav(){

        $cache_time = intval($GLOBALS['config']['api']['vod']['cachetime']);
        $cach_name = 'home_nav';
        $list = Cache::get($cach_name);
        if(empty($list) || $cache_time == 0) {
            $lp = [
                'type_status'   => 1,
                'type_pid'      => 0,
            ];
            $list  = model("Type")->listData($lp,"type_id asc");

            $data[] = ['id' => 0, 'name' => "推荐", 'img' => "",];
            $list = $list['list'] ?? [];
            $array = [];

            foreach($list as $key=>$item){
                $array[$key]['id']      = $item['type_id'];
                $array[$key]['name']    = $item['type_name'];
                $array[$key]['img']     = $item['img'] ?? "";
                $array[$key]['msg']     = type_extend($item['type_extend']) ?? [];
            }
            $list = array_merge($data, $array);

            if($cache_time > 0) {
                Cache::set($cach_name, $list, $cache_time);
            }
        }

        return json_return($list);
    }

    // 首页导航下数据
    public function home_data(){
        $id = $this->_param['id'] ?? 0;
        $idArr = [0,1,2,3,4,5,33];
        if(!in_array($id,$idArr)){
            return json_return([]);
        }
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
            $getSlide[$k]['img']    = mac_url_img($item['img']);
            $getSlide[$k]['url']    = $item['rel_vod'];
            $getSlide[$k]['type']   = 1;
        }

        // 内容
        if ($id == 0){
            $getListBlock = $this->tuijian();
        }else{
            $cache_time = intval($GLOBALS['config']['api']['vod']['cachetime']);
            $cach_name = 'home_data_'.$id;
            $getListBlock = Cache::get($cach_name) ? Cache::get($cach_name) : [];
            if(empty($getListBlock) || $cache_time == 0) {
                $fatherRes = [];

                // 单独查询 纪录片
                if ($id == 33) {
                    $where = [
                        'type_id' => $id
                    ];
                    $fatherRes = model("Type")->listData($where, "type_sort asc");
                    $fatherRes = $fatherRes['list'] ?? [];
                }

                $where = [
                    'type_pid' => $id,
                    'type_status' => 1,
                ];
                $sonRes = model("Type")->listData($where, "type_sort asc");
                $sonRes = $sonRes['list'] ?? [];

                $res = array_merge($sonRes, $fatherRes);

                foreach ($res as $item) {
                    $r = $item["type_id"];
                    $d = array(
                        'name' => $item['type_name'],
                        'nav' => 1,
                        'type' => 1,
                        'data' => $this->getVodList($r, 6, 1),
                        'extend' => selectOption($id, $item['type_name']),
                    );

                    array_push($getListBlock, $d);
                }

                // 电影、电视剧 加上最近热播
                if (in_array($id, [1, 2, 4])) {
                    $doubanRecomData = [];
                    $where = [
                        'd.type_id' => ['eq', $id],
                        'd.status' => ['eq', '1'],
                        'd.vod_id' => ['neq', '0'],
                        'v.vod_play_from' => ['like', '%3u8%'],
                    ];
                    // 电影取三条
                    $doubanList = model("douban_recommend")
                        ->alias('d')
                        ->field('d.vod_id')
                        ->join('vod v', 'd.vod_id = v.vod_id', 'left')
                        ->where($where)->order('d.id asc')->limit(6)->select();
                    $doubanIds = implode(",", array_column($doubanList, 'vod_id'));
                    $doubanRecomData[] = [
                        'name' => "最近热播",
                        'nav' => 0,
                        'type' => 4,
                        'data' => $this->vodStrData($doubanIds),
                        'extend' => [],
                    ];
                    $getListBlock = array_merge($doubanRecomData, $getListBlock);
                }

                // 存入缓存
                if($cache_time > 0) {
                    Cache::set($cach_name, $getListBlock, $cache_time);
                }

            }
        }

        $list = array(
            'name'  => '',
            'slide' => $getSlide,
            'video' => $getListBlock,
        );

        return json_return($list);
    }

    // 推荐 猜你在追 + 豆瓣推荐  + 今日推荐 + 热门
    public function tuijian(){
        // 猜你在追
        $guessDatas = [];
        $guessData = $this->guessUserMovies(3);
        if($guessData){
            $guessDatas[] = [
                'type'  => 3,
                'nav'   => 0,
                'id'    => 0,
                'msg'   => "",
                'name'  => "猜你在追",
                'data'  => $guessData,
            ];
        }

        $cache_time = intval($GLOBALS['config']['api']['vod']['cachetime']);
        $cach_name = 'home_tuijian';
        $list = Cache::get($cach_name);
        if(empty($list) || $cache_time == 0) {
            // 豆瓣推荐
            $doubanData = $this->doubanRecom();

            // 后台推荐配置
            $tuijianData = [];
            $tuijian = model("VodRecommend")->listData(['status' => 1,"type_id" => 0], "sort asc" );
            $tuijian = $tuijian['list'] ?? [];
            foreach($tuijian as $item){
                $tuijianData[] = [
                    'type'  => 2,
                    'nav'   => 0,
                    'id'    => $item['id'],
                    'msg'   => "",
                    'name'  => $item['name'],
                    'data'  => $this->vodStrData($item['rel_ids']),
                ];
            }

            $model = model("douban_recommend");
            $where = [
                'r.status'    => ['eq','1'],
                'r.vod_id'    => ['neq','0'],
            ];
            $apiListData  = $model->apiListData(array_merge($where,['r.type_id'=>['eq',1]]), 3, "id asc", 6);
            $apiListData2 = $model->apiListData(array_merge($where,['r.type_id'=>['eq',2]]), 3, "vod_time asc", 6);
            // 本地热门
            $data = [
                [
                    'type'  => 1,
                    'nav'   => 1,
                    'id'    => 1,
                    'msg'   => json_encode(getScreen(1),JSON_UNESCAPED_UNICODE),
                    'name'  => '热播电影',
                    'data'  => $apiListData,
                ],
                [
                    'type'  => 1,
                    'nav'   => 1,
                    'id'    => 2,
                    'msg'   => json_encode(getScreen(2),JSON_UNESCAPED_UNICODE),
                    'name'  => '热播剧',
                    'data'  => $apiListData2,
                ],
                [
                    'type'  => 1,
                    'nav'   => 1,
                    'id'    => 3,
                    'msg'   => json_encode(getScreen(3),JSON_UNESCAPED_UNICODE),
                    'name'  => '热播综艺',
                    'data'  => $this->getVodList(3,6,1),
                ],
                [
                    'type'  => 1,
                    'nav'   => 1,
                    'id'    => 4,
                    'msg'   => json_encode(getScreen(4),JSON_UNESCAPED_UNICODE),
                    'name'  => '热播动漫',
                    'data'  => $this->getVodList(4,6,1),
                ],
            ];

            $list = array_merge($doubanData,$tuijianData,$data);
            if($cache_time > 0) {
                Cache::set($cach_name, $list, $cache_time);
            }
        }

        $datas = array_merge($guessDatas,$list);
        return $datas;
    }

    // 豆瓣推荐
    public function doubanRecom($limit = 3){
        $doubanData = [];
        $model = model("douban_recommend");
        $where = [
            'd.status'    => ['eq','1'],
            'd.vod_id'    => ['neq','0'],
            'v.vod_play_from'    => ['like','%3u8%'],
        ];
        // 电影取三条
        $ids  = $model
            ->alias('d')
            ->field('d.vod_id')
            ->join('vod v','d.vod_id = v.vod_id','left')
            ->where(array_merge($where,['d.type_id'=>['eq',1]]))
            ->order('d.id asc')
            ->limit($limit)
            ->select();
        $ids2 = $model
            ->alias('d')
            ->field('d.vod_id')
            ->join('vod v','d.vod_id = v.vod_id','left')
            ->where(array_merge($where,['d.type_id'=>['eq',2]]))
            ->order('d.id asc')
            ->limit($limit)
            ->select();
        $ids  = objectToArray($ids);
        $ids2 = objectToArray($ids2);

        $doubanList = array_merge($ids,$ids2);
        $doubanIds  = implode(",",array_column($doubanList,'vod_id'));

        $doubanData[] = [
            'type'  => 4,
            'nav'   => 0,
            'id'    => 0,
            'msg'   => "",
            'name'  => "最近热播",
            'data'  => $this->vodStrData($doubanIds),
        ];

        return $doubanData;
    }

    // 查看更多
    public function recomData(){
        $type  = $this->_param['type'] ?? 2;    // 2热播后台配置banner  3猜你在追  4 最近热播-豆瓣列表
        $id    = $this->_param['id'] ?? 0;      // type 类型为 2 获取banner id  其他传0
        $page  = $this->_param['page'] ?? 1;

        $list = [];
        switch($type){
            case 2;
                $list = $this->recommendMore($id);
                break;
            case 3;
                $list = $this->guessUserMovies();
                break;
            case 4;
                $list = $this->doubanMore($page,$id);
                break;
        }
        return json_return($list);
    }

    // 分类视频
    public function getVodList($id,$limit,$page){
        $order = $this->sort[2];
        if($id == 2){
            $order = "vod_time desc";
        }
        $pageSize = ($page - 1) * $limit;

        $lp = [
            'type_id'         => ['eq',$id],
            'vod_play_from'   => ['like','%3u8%'],
        ];
        $info = model("Vod")
            ->field('vod_id,vod_name,vod_pic,vod_douban_score,vod_score,vod_remarks,type_id,type_id_1,vod_serial')
            ->where($lp)
            ->order($order)
            ->limit($pageSize,$limit)
            ->select();
        $info = objectToArray($info);

        $array = array();
        foreach($info as $r){
            $d = array(
                'img'   => mac_url_img($r['vod_pic']),
                'id'    => $r['vod_id'],
                'name'  => $r['vod_name'],
                'score' => $r['vod_douban_score'] == 0 ? randomFloat(5,8) : $r['vod_douban_score'],
                'msg'   => vodRemark($r),
            );
            array_push($array,$d);
        }
        return $array;
    }

    // 后台配置 推荐视频
    public function vodStrData($vodIds, $sort = ""){
        $vodIds = explode(",",$vodIds);

        $lp = [
            'vod_id'   => ['in',$vodIds],
        ];
        $info = model("Vod")->listData($lp,  $sort, 1, 6);

        $info = $info['list'] ?? [];
        $array = array();

        foreach($vodIds as $item){
            foreach($info as $r) {
                if ($item == $r['vod_id'] ){
                    $d = array(
                        'img'   => mac_url_img($r['vod_pic']),
                        'id'    => $r['vod_id'],
                        'name'  => $r['vod_name'],
                        'score' => $r['vod_douban_score'] == 0 ? randomFloat(5,8) : $r['vod_douban_score'],
                        'msg'   => vodRemark($r),
                    );
                    array_push($array,$d);
                }
            }
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
            'type_id'   => $info["type_id_1"],
            'img'       => mac_url_img($info["vod_pic"]),
            'msg'       => vodRemark($info),
            'score'     => $info['vod_douban_score'] == 0 ? randomFloat(5,8) : $info['vod_douban_score'],
            'type'      => $info["vod_area"] ,
            'director'  => $info["vod_director"],
            'actor'     => $info["vod_actor"],
            'info'      => $info["vod_content"],
            'playcode'  => $info["vod_play_from"],
            'playlist'  => $info["vod_play_url"],
            'downcode'  => $info["vod_down_from"],
            'downlist'  => $info["vod_down_url"],
        );
        return json_return($data);
    }

    // 搜索
    public function search(){
        $key  = $this->_param['key'] ?? "";
        $page = $this->_param['page'] ?? 1;
        $limit = 10;
        $pageSize = ($page - 1) * $limit;

        $cache_time = intval($GLOBALS['config']['api']['vod']['cachetime']);
        $cach_name = 'home_search_' . $key;
        $data = Cache::get($cach_name);
        if(empty($data) || $cache_time == 0) {

            $sql = "SELECT vod_id,vod_pic,vod_name,vod_content,vod_remarks,type_id,type_id_1,vod_serial FROM `vod` WHERE vod_name LIKE '%".$key."%' OR vod_sub LIKE '%".$key."%' OR vod_actor LIKE '%".$key."%' OR vod_director LIKE '%".$key."%' ORDER BY  ( ( CASE WHEN vod_name LIKE '" . $key . "%' THEN 3 ELSE 0 END ) +
        ( CASE WHEN vod_name LIKE '%" . $key . "%' THEN 1 ELSE 0 END )) DESC," . $this->sort[2] . ",vod_serial DESC LIMIT " . $pageSize . "," . $limit;
            $res = Db::query($sql);


            $r = [];
            $l = [];
            foreach($res as $item){
                if($item['vod_name'] == $key){
                    $r[] = $item;
                }else{
                    $l[] = $item;
                }
            }
            $res = array_merge($r,$l);

            $data = [];
            foreach($res as $r){
                $d = array(
                    'img'   => mac_url_img($r['vod_pic']),
                    'name'  => $r['vod_name'],
                    'msg'   => vodRemark($r),
                    'text'  => $r['vod_content'],
                    'url'   => $r['vod_id'],
                );
                array_push($data,$d);
            }
            if($cache_time > 0) {
                Cache::set($cach_name, $data, $cache_time);
            }
        }

        // 记录用户搜索数据
        $time = time();
        $sql = "insert into search_keyword(name,times,create_time) values('".$key."','1','".$time."') ON DUPLICATE KEY UPDATE times = times + 1 ";
        Db::query($sql);

        return json_return($data);
    }

    // 热搜关键词
    public function search_hot(){
        $douban = model('douban_recommend');

        $data = [];
        for($i = 1; $i <= 2; $i++){
            $where = [
                'type_id'   => ['eq',$i],
                'status'    => ['eq','1'],
                'vod_id'    => ['neq','0'],
            ];
            $list = $douban->field("name")->where($where)->limit(10)->order('id asc')->select();
            $list = objectToArray($list);
            $data = array_merge(array_column($list,'name'),$data);
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
        $sort   = $this->_param['sort'] ?? "最近更新";

        $sortArray = ["评分最高" => 1, "最近更新" => 2 ];
        $sort = $sortArray[$sort] ?? 2;

        $type = $type == "全部" ? "" : $type;
        $area = $area == "全部" ? "" : $area;
        $year = $year == "全部" ? "" : $year;

        $where = [];
        if($id != 0  ){
            // 纪录片 单独查询
            if($id == 33){
                $where['type_id']   = ['eq',$id];
            }else{
                $where['type_id_1']   = ['eq',$id];
            }

        }

        if($type != ""){
            $where['vod_tag']   = ['like','%'.$type.'%'];
        }

        $keyWordArrNew = ['美国','法国','英国','意大利','德国'];
        if($area != ""){
            if($area == "国产" || $area == "大陆" ){
                $where['vod_area']   = ['like','%大陆%'];
            }else if($area == "欧美" ){
                $where['vod_area']   = ['in', $keyWordArrNew];
            }else{
                $where['vod_area']   = ['like','%' . $area .'%'];
            }
        }

        if($year != ""){
            $where['vod_year']   = ['eq',$year];
        }

        $where['vod_play_from']   = ['like', '%3u8%'];

        // 排序
        $sort = $this->sort[$sort];

        $info = model("Vod")->listData($where, $sort, $page, 18);
        $info = $info['list'] ?? [];

        $array = array();
        foreach($info as $r){
            $d = array(
                'img'   => mac_url_img($r['vod_pic']),
                'id'    => $r['vod_id'],
                'name'  => $r['vod_name'],
                'score' => $r['vod_douban_score'] == 0 ? randomFloat(5,8) : $r['vod_douban_score'],
                'msg'   => vodRemark($r),
            );
            array_push($array,$d);
        }

        return json_return($array);
    }

    // 用户记录日志
    public function userLog(){
        // 用户注册
        $mac        = $this->_param['mac'] ??  "" ;
        $type       = $this->_param['type'] ??  "" ;
        $rid        = $this->_param['rid'] ??  "" ;
        $sid        = $this->_param['sid'] ??  "" ;
        $nid        = $this->_param['nid'] ??  "" ;
        $channel    = $this->_param['channel'] ??  0 ;
        $version    = $this->_param['version'] ??  "" ;

        if($mac == "" || $type == "" || $rid == ""){
            return json_return("参数错误",0);
        }

        $userModel = model("User");
        $userRes = $userModel->infoData(['user_name' => $mac],"user_id");

        $versionInfo =  model("AppVersion")->where(['app_version'=> $version])->find();
        $version_id  = isset($versionInfo['id']) ? $versionInfo['id'] : 0;

        if($userRes['code'] == 1002){
            $channelInfo =  model("Channel")->where(['name'=> $channel])->find();
            $channel_id  = isset($channelInfo['id']) ? $channelInfo['id'] : 0;

            $userModel->saveData([
                'user_name'     => $mac,
                'user_pwd'      => "123456",
                'channel_id'    => $channel_id,
                'version_id'    => $version_id,
                'user_reg_time' => time(),
            ]);
            $userId = $userModel::getLastInsID();
        }else{
            $userId = $userRes['info']['user_id'];
        }

        $data = [
            'user_id'    => $userId ,   // 用户ID
            'ulog_mid'   => 1 ,         // 模块 1视频 2文章 3专题 8演员
            'ulog_type'  => $type ,     // 类型 1浏览 2收藏 3想看 4点播 5下载
            'ulog_rid'   => $rid ,      // 关联ID ：视频ID
            'ulog_sid'   => $sid ,      // 来源 ：播放源
            'ulog_nid'   => $nid ,      // 第几集
            'app_version'   => $version , // App版本
        ];
        $res = model("Ulog")->saveData($data);
        if($res['code'] != 1){
            return json_return('记录日志失败');
        }

        return json_return(['保存成功']);
    }

    // 猜你在追电视剧
    public function guessUserMovies($limit = 18){
        $mac    = $this->_param['mac'] ??  "" ;
        $page   = $this->_param['page'] ?? 1;
        $pageSize = ( $page - 1 ) * 18;
        if($mac == ""){
            return [];
        }

        // 查询用户信息
        $where = [
            'user_name' => $mac,
        ];
        $userInfo = model("User")->infoData($where,"user_id");
        $userInfo = $userInfo['info'] ?? [];
        if($userInfo == ""){
            return [];
        }
        $userId = $userInfo['user_id'] ?? 0;

        // 查询用户 最近日志信息
        $logWhere = [
            'u.user_id'   => ['eq',$userId],
            'u.ulog_mid'  => ['eq',1],
            'u.ulog_type' => ['in',[2,3,4,5]],
        ];

        $field = "v.vod_id,v.vod_name,v.type_id,v.type_id_1,v.vod_pic,v.vod_score,v.vod_douban_score,v.vod_remarks,v.vod_total,v.vod_serial";
        $userLog = model("Ulog")
            ->alias('u')
            ->field($field)
            ->join('vod v', 'u.ulog_rid = v.vod_id')
            ->where($logWhere)
            ->group('u.ulog_rid')
            ->order('u.ulog_time desc')
            ->limit($pageSize,$limit)
            ->select();
        $vodList = objectToArray($userLog);

        // 获取相应 影视数据
        $array = [];
        foreach($vodList as $r){
            $d = array(
                'img'   => mac_url_img($r['vod_pic']),
                'id'    => $r['vod_id'],
                'name'  => $r['vod_name'],
                'score' => $r['vod_douban_score'] == 0 ? randomFloat(5,8) : $r['vod_douban_score'],
                'msg'   => vodRemark($r),
            );
            array_push($array,$d);
        }
        return $array;
    }

    // 相似关联 影视
    public function relation(){
        $id    = $this->_param['id'] ??  "" ;
        if($id == ""){
            return json_return([]);
        }

        $model  = model("vod");
        $lp = [
            'vod_id' => $id
        ];
        $info = $model->field("vod_id,type_id,type_id_1,vod_actor")->where($lp)->find();
        $info = objectToArray($info);

        $symbol = strpos($info['vod_actor'],',') ?  ',' : '/';
        $actor = explode($symbol,$info['vod_actor']) ?? "";
        $actor = $actor[0] != "" ? $actor[0] : "";

        $field = "vod_id,vod_name,vod_pic,vod_score,vod_douban_score,vod_remarks,type_id,type_id_1,vod_total,vod_serial";

        $where = [
            'vod_id'    => ['neq',$id],
            'vod_actor' => ['like', "%".$actor."%"],
            'vod_play_from' => ['like', "%3u8%"],
            'vod_time_add' => ['lt', time()],
        ];

        if($info['type_id'] <= 4 || $info['type_id'] == 33) {
            $where['type_id|type_id_1'] = ['eq',$info['type_id']];
        }else if($info['type_id'] > 4 && $info['type_id'] < 33 ){
            $where['type_id_1'] = ['eq',$info['type_id_1']];
        }

        $res = $model->field($field)->where($where)->order('vod_score desc')->limit(6)->select();
        $res = objectToArray($res);

        $count = count($res);
        if($count < 6){
            unset($where['vod_actor']);
            $limit = $count >= 6 ? 6 : 6 - $count;
            $res2 = $model->field($field)->where($where)->order('vod_score desc')->limit($limit)->select();
            $res = array_merge($res,$res2);
        }

        $res = objectToArray($res);

        $array = [];
        foreach($res as $r){
            $d = array(
                'img'   => mac_url_img($r['vod_pic']),
                'id'    => $r['vod_id'],
                'name'  => $r['vod_name'],
                'score' => $r['vod_douban_score'] == 0 ? randomFloat(5,8) : $r['vod_douban_score'],
                'msg'   => vodRemark($r),
            );
            array_push($array,$d);
        }
        return json_return($array);
    }

    // 豆瓣推荐列表
    public function doubanMore($page,$id = 0){
        $limit = 18;
        $pageSize = ( $page - 1 ) * $limit;

        $where = [
            'r.status'    => ['eq','1'],
            'r.vod_id'    => ['neq','0'],
            'v.vod_play_from'   => ['like','%3u8%'],
        ];

        if($id != 0){
            $where['r.type_id'] = ['eq',$id];
        }
        $model = model("douban_recommend");

        if($id == 0){
            $list  = $model->alias('r')
                ->field('r.vod_id,r.name,v.vod_pic,r.type_id as r_type_id,v.vod_score,v.type_id,v.type_id_1,v.vod_total,v.vod_serial,v.vod_douban_score,v.vod_remarks')
                ->join('vod v','r.vod_id = v.vod_id','left')
                ->where($where)
                ->order('id asc')
                ->select();
            $list = objectToArray($list);
            $data = [];
            foreach($list as $item){
                 $data[$item['r_type_id']][] = $item;
            }

            $size = count($data[1]) > count($data[2]) ? count($data[1]) : count($data[2]); //取出元素最多的数组循环

            $arr = array();
            for($i=0; $i < $size; $i++){
                if(isset($data[1][$i])){
                    array_push($arr,$data[1][$i]); //将数组压入新的变量
                }
                if(isset($data[2][$i])){
                    array_push($arr,$data[2][$i]); //将数组压入新的变量
                }
            }

            if(isset($data[3]) && !empty($data[3])){
                $arr = array_merge($arr,$data[3]);
            }

            if(isset($data[4]) && !empty($data[4])){
                $arr = array_merge($arr,$data[4]);
            }
            // 分页最终数组
            $return = array_slice($arr,$pageSize ,$limit);
            $datas = [];
            foreach($return as &$item){
                $datas[] = [
                    'img'   => mac_url_img($item['vod_pic']),
                    'id'    => $item['vod_id'],
                    'name'  => $item['name'],
                    'score' => $item['vod_douban_score'] > 0 ? $item['vod_douban_score'] : $item['vod_score'],
                    'msg'   => vodRemark($item),
                ];
            }
        }else{
            // 分页最终数组
            $datas = $model->apiListData($where,$pageSize);

        }

        return $datas;
    }

    // recommend
    public function recommendMore($id){
        // 后台推荐配置
        $recommend = model("VodRecommend")->where(['id'=>$id])->find();
        $recommend = objectToArray($recommend);

        return $this->vodStrData($recommend['rel_ids']);
    }

    // 渠道推广视频ID
    public function channelRecom(){
        $mac        = $this->_param['mac'] ??  "";
        $keys       = $this->_param['keys'] ??  "";
        $version    = $this->_param['version'] ??  "" ;
        if($mac == "" || $keys == ""){
            return json_return(['vod_id'=>0]);
        }

        $userModel = model("User");
        // 老用户 不返回视频
        $user = $userModel->where(['user_name'=>['eq',$mac]])->find();
        if($user){
            return json_return(['vod_id'=>0]);
        }else{
            $channelInfo =  model("Channel")->where(['name'=> $keys])->find();
            $channel_id  = isset($channelInfo['id']) ? $channelInfo['id'] : 0;
            $version_id  = 0;
            if($version){
                $versionInfo =  model("AppVersion")->where(['app_version'=> $version])->find();
                $version_id  = isset($versionInfo['id']) ? $versionInfo['id'] : 0;
            }
            // 添加用户
            $userModel->saveData([
                'user_name'     => $mac,
                'user_pwd'      => "123456",
                'channel_id'    => $channel_id,
                'version_id'    => $version_id,
                'user_reg_time' => time(),
            ]);
        }

        $vodId =  model("Channel")
            ->field('recom_vod')
            ->where(['name' => ['eq',$keys]])
            ->find();
        $vodId = objectToArray($vodId);
        $vodId = $vodId['recom_vod'] ?? 0;

        return json_return(['vod_id'=>$vodId]);
    }


    // 推荐短视频
    public function recomVodList(){
        $page  = $this->_param['page'] ?? 1;
        $limit = 10;
        $pageSize = ($page - 1) * $limit;

        $where = [
            'r.states' => ['eq',0],
            'r.image' => ['neq',""],
            'r.url' => ['neq',""],
        ];

        $list =  model("Recom")
            ->alias('r')
            ->field('r.id,r.vod_id,v.vod_name,r.name,r.image,r.url,r.intro')
            ->join('vod v','v.vod_id = r.vod_id')
            ->where($where)
            ->limit($pageSize,$limit)
            ->select();
        $list = objectToArray($list);
       
        foreach($list as &$item){
            $item['image']  = mac_url_img($item['image']);
            $item['url']    = mac_url_img($item['url']);
        }

        return json_return($list);
    }

    // 联想词汇查询
    public function relationWord(){
        $word  = $this->_param['word'] ?? "";

        $model = model("Vod");
        $order = $this->sort[2];
        $list = $model->field('vod_id,vod_name')->where([
                "vod_name" => ['like',$word."%"],
            ])->order($order)->limit(10)->select();
        $list = objectToArray($list);
        
        return json_return($list);
    }

    // 清楚缓存
    public function clearData(){
        $name = $this->_param['name'] ?? 0;
        Cache::rm($name);
    }
}
