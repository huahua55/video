<?php
namespace app\admin\controller;
use think\Db;
use think\Cache;

class Urlsend extends Base
{
    var $_lastid='';
    var $_cache_name ='';

    public function __construct()
    {
        parent::__construct();

        $this->_param = input();
    }

    public function index()
    {
        $param = input();
        $urlsend_config = config('urlsend');


        $key =  $param['domain_key']??0;
        //获取域名
        $domain_conf = config('domain');
        $domain_conf = array_keys($domain_conf);
        if (!isset($domain_conf[$key])){
            return $this->error('打开失败，请联系技术人员');
        }
        if (Request()->isPost()) {
            $config = input();
            $config_new['urlsend'] = $config['urlsend'];
            foreach ($domain_conf as $k => $v){
                if(!isset($urlsend_config[$v])){
                    $urlsend_config[$v] = [
                        'sm_push_token'=>'',
                        'baidu_push_token'=>'',
                    ];
                }
                if($domain_conf[$key] == $v){
                    $urlsend_config[$v] = $config_new['urlsend'];
                }
            }
            $res = mac_arr2file(APP_PATH . 'extra/urlsend.php', $urlsend_config);
            if ($res === false) {
                return $this->error('保存失败，请重试!');
            }
            return $this->success('保存成功!');
        }


        foreach ($domain_conf as $k => $v){
            if(!isset($urlsend_config[$v])){
                $urlsend_config[$v] = [
                    'sm_push_token'=>'',
                    'baidu_push_token'=>'',
                ];
            }
        }

        $res = mac_arr2file(APP_PATH . 'extra/urlsend.php', $urlsend_config);
//        if ($res === false) {
//            return $this->error('保存失败，请重试!');
//        }

        $this->assign('config',$urlsend_config);

        $this->assign('domain_list',$domain_conf);
        $this->assign('domain_key',$domain_conf[$key]);
        $this->assign('urlsend_list',$urlsend_config[$domain_conf[$key]]);

        $urlsend_break_baidu_push = Cache::get('urlsend_break_baidu_push');
        $urlsend_break_baidu_bear = Cache::get('urlsend_break_baidu_bear');

        $this->assign('urlsend_break_baidu_push',$urlsend_break_baidu_push);
        $this->assign('urlsend_break_baidu_bear',$urlsend_break_baidu_bear);

        $this->assign('title','URL推送管理');
        return $this->fetch('admin@urlsend/index');
    }



    public function push($pp=[])
    {
        if(!empty($pp)){
            $this->_param = $pp;
        }
        if($this->_param['ac']=='baidu_push'){
            $this->baidu_push();
        }
        elseif($this->_param['ac']=='sm_push'){
            $this->sm_push();
        }
        else{
            $this->error('参数错误');
        }
    }

    public function data()
    {
        mac_echo('<style type="text/css">body{font-size:12px;color: #333333;line-height:21px;}span{font-weight:bold;color:#FF0000}</style>');

        $list = [];
        $mid = $this->_param['mid'];
        $this->_param['page'] = intval($this->_param['page']) <1 ? 1 : $this->_param['page'];
        $this->_param['limit'] = intval($this->_param['limit']) <1 ? 500 : $this->_param['limit'];
        $ids = $this->_param['ids'];
        $ac2 = $this->_param['ac2'];
        $site = $this->_param['site']??'';

        $today = strtotime(date('Y-m-d'));
        $where = [];
        $this->_cache_name = 'urlsend_cach_'.$mid.'_'.$ac2;
        $data = Cache::get($this->_cache_name);
        $col = '';
        switch($mid)
        {
            case 1:
                $where['vod_status'] = ['eq',1];

                if($ac2=='today'){
                    $where['vod_time_add'] = ['gt',$today];
                }
                if(!empty($ids)){
                    $where['vod_id'] = ['in',$ids];
                }
                elseif(!empty($data)){
                    $where['vod_id'] = ['gt', $data];
                }

                $col = 'vod';
                $order = 'vod_id asc';
                $fun = 'mac_url_vod_detail';
                $res = model('Vod')->listData($where,$order,$this->_param['page'],$this->_param['limit']);
                break;
            case 2:
                $where['art_status'] = ['eq',1];

                if($ac2=='today'){
                    $where['art_time_add'] = ['gt',$today];

                }
                if(!empty($ids)){
                    $where['art_id'] = ['in',$ids];
                }
                elseif(!empty($data)){
                    $where['art_id'] = ['gt', $data];
                }

                $col = 'art';
                $order = 'art_id asc';
                $fun = 'mac_url_art_detail';
                $res = model('Art')->listData($where,$order,$this->_param['page'],$this->_param['limit']);
                break;
            case 3:
                $where['topic_status'] = ['eq',1];

                if($ac2=='today'){
                    $where['topic_time_add'] = ['gt',$today];

                }
                if(!empty($ids)){
                    $where['topic_id'] = ['in',$ids];
                }
                elseif(!empty($data)){
                    $where['topic_id'] = ['gt', $data];
                }

                $col = 'topic';
                $order = 'topic_id asc';
                $fun = 'mac_url_topic_detail';
                $res = model('Topic')->listData($where,$order,$this->_param['page'],$this->_param['limit']);
                break;
            case 8:
                $where['actor_status'] = ['eq',1];

                if($ac2=='today'){
                    $where['actor_time_add'] = ['gt',$today];

                }
                if(!empty($ids)){
                    $where['actor_id'] = ['in',$ids];
                }
                elseif(!empty($data)){
                    $where['actor_id'] = ['gt', $data];
                }
                $col = 'actor';
                $order = 'actor_id asc';
                $fun = 'mac_url_actor_detail';
                $res = model('Actor')->listData($where,$order,$this->_param['page'],$this->_param['limit']);
                break;
            case 9:
                $where['role_status'] = ['eq',1];

                if($ac2=='today'){
                    $where['role_time_add'] = ['gt',$today];

                }
                if(!empty($ids)){
                    $where['role_id'] = ['in',$ids];
                }
                elseif(!empty($data)){
                    $where['role_id'] = ['gt', $data];
                }
                $col = 'role';
                $order = 'role_id asc';
                $fun = 'mac_url_role_detail';
                $res = model('Role')->listData($where,$order,$this->_param['page'],$this->_param['limit']);
                break;
            case 11:
                $where['website_status'] = ['eq',1];

                if($ac2=='today'){
                    $where['website_time_add'] = ['gt',$today];

                }
                if(!empty($ids)){
                    $where['website_id'] = ['in',$ids];
                }
                elseif(!empty($data)){
                    $where['website_id'] = ['gt', $data];
                }
                $col = 'website';
                $order = 'website_id asc';
                $fun = 'mac_url_website_detail';
                $res = model('Website')->listData($where,$order,$this->_param['page'],$this->_param['limit']);
                break;
        }

        if(empty($res['list'])){
            mac_echo('没有获取到数据');
            return;
        }

        mac_echo('共'.$res['total'].'条数据等待推送，分'.$res['pagecount'].'页推送，当前第'.$res['page'].'页');
        $maccms = config('maccms');

        $urls = [];
        foreach($res['list'] as $k=>$v){
            $val =   $fun($v);
            if(!empty($site)){
                $val = str_replace($maccms['site']['site_url'],$site,$val);
            }
            $urls[$v[$col.'_id']] =  $val;
            $this->_lastid = $v[$col.'_id'];
            mac_echo($v[$col.'_id'] . '、'. $v[$col . '_name'] . '&nbsp;<a href="'.$urls[$v[$col.'_id']].'">'.$urls[$v[$col.'_id']].'</a>');
        }

        $res['urls'] = $urls;
        return $res;
    }

    public function baidu_push()
    {

        $res = $this->data();
        Cache::set('urlsend_break_baidu_push', url('urlsend/push').'?'. http_build_query($this->_param) );


        if (!empty($res['urls'])) {
            $type = $this->_param['type']; //urls: 添加, update: 更新, del: 删除
            $site =  $this->_param['site'];
            $urlsend_config = config('urlsend');
            $urlsend_val = $urlsend_config[$site]??[];
            $site =  $GLOBALS['http_type'] .$this->_param['site'];
            $token = $urlsend_val['baidu_push_token']??'';
            if (empty($type)) {
                $type = 'urls';
            }
            $api = 'http://data.zz.baidu.com/' . $type . '?site=' . $site . '&token=' . $token;
            $head = ['Content-Type: text/plain'];
            $data = implode("\n", $res['urls']);
            $r = mac_curl_post($api, $data, $head);
            $json = json_decode($r,true);
            if(!$json){
                mac_echo('请求失败，请重试');
                return;
            }
            elseif($json['error']){
                mac_echo('发生错误：'. $json['message'] );
                return;
            }
            Cache::set($this->_cache_name, $this->_lastid);
            mac_echo('推送成功'.$json['success'].'条；当天剩余可推'.$json['remain'].'条。');
        }

        if ($res['page'] >= $res['pagecount']) {
            Cache::rm('urlsend_break_baidu_push');

            mac_echo('数据推送完毕');
            if(ENTRANCE=='admin') {
                mac_jump(url('urlsend/index'), 3);
            }
        }
        else {
            $url = url('urlsend/baidu_push') . '?' . http_build_query($this->_param);
            if(ENTRANCE=='admin') {
                mac_jump($url, 3);
            }
        }

    }

    public function sm_push()
    {
        $res = $this->data();
        Cache::set('urlsend_break_baidu_bear', url('urlsend/push').'?'. http_build_query($this->_param) );


        if(!empty($res['urls'])){
            $type = $this->_param['type']; //realtime实时, batch历史
            $site = $this->_param['site']; //realtime实时, batch历史
            $urlsend_config = config('urlsend');
            $urlsend_val = $urlsend_config[$site]??[];
            $token = $urlsend_val['sm_push_token']??'';
            if(empty($type)){
                $type = 'realtime';
            }
            $api = 'http://data.zhanzhang.sm.cn/push?site='.$site.'&user_name=pzz2364@outlook.com&resource_name=mip_add&token='.$token.'';
            $head = ['Content-Type: text/plain'];
            $data = implode("\n", $res['urls']);
            $r = mac_curl_post($api, $data, $head);
            $json = json_decode($r,true);
            if(!$json){
                mac_echo('请求失败，请重试');
                return;
            }else if($json['returnCode'] != 200){
                mac_echo('发生错误：'. $json['errorMsg'] );
                return;
            }
            Cache::set($this->_cache_name, $this->_lastid);
            if($type=='realtime'){
                mac_echo('实时推送'.$json['success_realtime'].'条；实时剩余可推送'.$json['remain_realtime'].'条.');
            }
            else{
                mac_echo('历史推送'.$json['success_batch'].'条；历史剩余可推送'.$json['remain_batch'].'条；');
            }
        }

        if ($res['page'] >= $res['pagecount']) {
            Cache::rm('urlsend_break_baidu_bear');
            mac_echo('数据推送完毕');
            if(ENTRANCE=='admin') {
                mac_jump(url('urlsend/index'), 3);
            }
        }
        else {
            $url = url('urlsend/sm_push') . '?' . http_build_query($this->_param);
            if(ENTRANCE=='admin') {
                mac_jump($url, 3);
            }
        }
    }

}
