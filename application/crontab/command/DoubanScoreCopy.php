<?php

namespace app\crontab\command;

use similar_text\similarText;
use think\Cache;
use app\common\model\Type;
use app\common\model\CollectOk;
use app\common\model\Collect;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Log;

use GuzzleHttp\Client;
use Exception;
use QL\Ext\PhantomJs;
use QL\QueryList;

class DoubanScoreCopy extends Common
{
    protected $vodDb;//db
    protected $cmsDb;//db
    protected $search_url = 'https://movie.douban.com/j/subject_suggest?q=%s';//豆瓣搜索接口
    protected $get_search_id = 'http://api.douban.com/v2/movie/subject/%s?apikey=0df993c66c0c636e29ecbb5344252a4a';
    protected $ql;//querylist

    // 视频关联表trait
    use LinkTablesTrait;

    protected function configure()
    {
        //db
        $this->cmsDb = Db::name('douban_vod_details');
        $this->vodDb = Db::name('vod');
        $this->ql = QueryList::getInstance();
        //获取豆瓣id
        $this->setName('doubanScoreCopy')->addArgument('parameter')
            ->setDescription('定时计划：采集豆瓣评分');
    }

    // 取出数据豆瓣评分为空数据
    protected function getVodDoubanScoreData($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodDb->where($where)->count();
        $list = $this->vodDb->field('vod_id,vod_year,vod_sub,vod_name,vod_class,vod_actor,vod_director,vod_douban_id,vod_douban_score,vod_time')->where($where)->orderRaw('CONVERT(vod_year,SIGNED) desc')->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }


    protected function execute(Input $input, Output $output)
    {

        // 输出到日志文件
        $output->writeln("开启采集:采集豆瓣评分");
        try {
            // 字符串对比算法
            $lcs = new similarText();
            // cli模式接受参数
            $myparme = $input->getArguments();
            $parameter = $myparme['parameter'];
            // 参数转义解析
            $param = $this->ParSing($parameter);
            $type = $param['type'] ?? ''; //从1 开始爬取
            $x = $param['x'] ?? '';
            $id = $param['id'] ?? '';
            $g = $param['g'] ?? '';
            $port_type = $param['port_type'] ?? '';
            if (!empty($type) && $type == 1) {
                Cache::set('vod_id_list_douban_score', 1);
            }
            // 开始cookie
            $cookies = $this->getCookie('');
            $start = 0;
            $page = 1;
            $limit = 20;
            $is_true = true;
            $where = [
                // 'vod_douban_id' => 0,
            ];
            Cache::set('vod_id_list_douban_score', '');
            $is_vod_id = Cache::get('vod_id_list_douban_score');
            // Cache::set('vod_time_list_douban_score', '');
            if (!empty($id)) {
                $where['vod_id'] = ['gt', $id];
            } else {
                if (!empty($is_vod_id)) {
                    $where['a.vod_id'] = ['gt', $is_vod_id];
                }
            }
            // 只修改精选表数据
            // $where = self::_editVideoSelectWhere();
            // $startTime =  date("Y-m-d 00:00:00",time());
            // $endTime =  date("Y-m-d 23:59:59",time());
            // $where['vod_time'] =['between',[strtotime($startTime),strtotime($endTime)]];

            $order = 'a.vod_id asc';
            $cookie = $this->newCookie($cookies);
            //进入循环 取出数据
            while ($is_true) {
                $this->get_zm_port();
                if (empty($this->get_port)) {
                    $this->get_zm_port();
                }

                // 取出数据
                $douBanScoreData = self::_getVideoData($where, $order, $page, $limit, $start);
                // print_r( $this->vodDb->getlastsql());die;
                $pagecount = $douBanScoreData['pagecount'] ?? 0;
                if ($page > $pagecount) {
                    $is_true = false;
                    log::info('采集豆瓣评分结束...');
                    $output->writeln("采集豆瓣评分-结束....");
                    break;
                }

                foreach ($douBanScoreData['list'] as $k => $v) {
                    $error_count = 1;
                    $error_i_count = 1;
                    $is_log = false;
                    if ( $v['vod_douban_id'] ) {
                        $url = sprintf($this->get_search_id, $v['vod_douban_id']);
                    } else {
                        //开启代理
                        $url = sprintf($this->search_url, urlencode($v['vod_name']));
                    }
                    
                    try {
                        // if(empty($this->get_port)){
                        $this->get_zm_port();
                        // }
                        usleep(500000);
                        // $cookie = 'bid=tre-gFuRDCw; Expires=Fri, 23-Apr-21 10:03:41 GMT; Domain=.douban.com; Path=/';
                        if ($port_type == 1) {
                            $str_data = $this->getUrl($url);
                            $str_data = explode("\r\n", $str_data);
                            $mac_curl_get_data = array_pop($str_data);
                        } else {
                            $mac_curl_get_data = self::_qlRequest( $url, $cookie );
                        }
                        $mac_curl_get_data = json_decode($mac_curl_get_data, true);
                    } catch (Exception $e) {
                        $error_i_count++;
                        if ($error_i_count > 18) {
                            $is_true = false;
                            exit("采集豆瓣评分-错误----超过最大请求次数");
                            break;
                        }
                        Log::info('采集豆瓣评分-err--过滤' . $e . '---' . $url);
                        continue;
                    }
                    if (empty($mac_curl_get_data)) {

                        // 针对名称查询为空的情况再次请求三次
                        for ($i=0; $i < 3; $i++) {
                            $this->get_zm_port();
                            usleep(500000);
                            $mac_curl_get_data = self::_qlRequest( $url, $cookie );
                            $mac_curl_get_data = json_decode($mac_curl_get_data, true);
                        }
                        if (empty($mac_curl_get_data)) {
                            Log::info('采集豆瓣评分-3次循环请求--vod_douban_id::' . $v['vod_douban_id'] . '  数据为空');
                            Log::info('采集豆瓣评分-3次循环请求--vod_name::' . $v['vod_name'] . '  数据为空');
                        } else {
                            Log::info('采集豆瓣评分-3次循环请求--vod_douban_id::' . $v['vod_douban_id'] . '  数据不为空');
                            Log::info('采集豆瓣评分-3次循环请求--vod_name::' . $v['vod_name'] . '  数据不为空');
                        }
                    }
                    if (empty($mac_curl_get_data)) {
                        $error_count++;
                        if ($error_count > 18) {
                            $is_true = false;
                            exit("采集豆瓣评分-错误----未获取到数据且超过最大请求次数");
                            break;
                        }
                    }
                    Log::info('采集豆瓣评分-err--proxyerr_i-' . $this->proxy_server . ":" . $this->get_port);
                    log::info('采集豆瓣评分-url-:' . $url);
                    if (!empty($mac_curl_get_data) && (!$v['vod_douban_id'] || $v['vod_douban_id'] == 0)) {
                        log::info('采集豆瓣评分-no-douban-id-开始::' . $v['vod_id']);
                        foreach ($mac_curl_get_data as $da_k => $as_k) {
                            if ($da_k == 0 || $da_k == 1 || $da_k == 2) {
                                log::info('采集豆瓣评分-title1-::' . mac_trim_all($v['vod_name']));
                                log::info('采集豆瓣评分-title2-::' . $as_k['title']);
                                $deas_data['title'] = $as_k['title'];
                                $deas_data['link'] = $as_k['url'];
                                $deas_data['douban_id'] = $as_k['id'];
                                $deas_data['abstract'] = '';
                                $deas_data['abstract_2'] = '';
                                $deas_data['rating_nums'] = '0.0';
                                $deas_data['time'] = date("Y-m-d H:i:s",time());
                                $deas_data['type'] = 7;
                                try {
                                    // douban_id 在数据库中是唯一键 相同会报错
                                    Db::name('douban_vod_details')->insert($deas_data);
                                } catch (\Exception $e) {
                                    log::info('采集豆瓣评分-数据重复添加::' . $as_k['title']);
                                }
                                if ($g == 1) {
                                    log::info('采集豆瓣评分-title-su-::g' . $as_k['title'] . '---' . $v['vod_id']);
                                } else {
                                    // if(mac_trim_all($v['vod_name']) == mac_trim_all($as_k['title'])){
                                    $rade = $lcs->getSimilar(mac_trim_all(mac_characters_format($v['vod_name'])), mac_trim_all(mac_characters_format($as_k['title']))) * 100;
                                    log::info('采集豆瓣评分-比例::' . $rade);
                                    if ($rade > 50) {
                                        log::info('采集豆瓣评分-title-su-::' . $as_k['title'] . '---' . $v['vod_id']);
                                        if (!empty($as_k['id'])) {
                                            log::info('采集豆瓣评分-ok-id::' . $as_k['id']);
                                            $get_url_search_id = sprintf($this->get_search_id, $as_k['id']);
                                            try {
                                                // if(empty($this->get_port)){
                                                $this->get_zm_port();
                                                // }
                                                usleep(500000);
                                                if ($port_type == 1) {
                                                    $str_data = $this->getUrl($url);
                                                    $str_data =  explode("\r\n", $str_data);
                                                    $get_url_search_id_data = array_pop($str_data);
                                                } else {
                                                    $get_url_search_id_data = self::_qlRequest( $get_url_search_id, $cookie );
                                                }
                                                $get_url_search_id_data = json_decode($get_url_search_id_data, true);
                                                Log::info('采集豆瓣评分-err--proxyb-' . $this->proxy_server . ":" . $this->get_port);
                                            } catch (Exception $e) {
                                                Log::info('采集豆瓣评分-err--过滤' . $e . $url);
                                                Log::info('采集豆瓣评分-err--proxyerrb-' . $this->proxy_server . ":" . $this->get_port);
                                                continue;
                                            }
                                            self::_editDataByDouBanInfo($as_k['id'], $v, $rade, $lcs, $get_url_search_id_data);
                                        }
                                    }
                                }
                            }
                        }
                        log::info('采集豆瓣评分-no-douban-id-结束::' . $v['vod_id']);
                    } else {
                        log::info('采集豆瓣评分-has-douban-id-开始::' . $v['vod_douban_id'] . '---' . $v['vod_id']);
                        if (!empty($v['vod_douban_id'])) {
                            self::_editDataByDouBanInfo($v['vod_douban_id'], $v, null, $lcs, $mac_curl_get_data);
                        }
                        log::info('采集豆瓣评分-has-douban-id-结束::' . $v['vod_douban_id'] . '---' . $v['vod_id']);
                    }

                    Cache::set('vod_id_list_douban_score', $v['vod_id']);
                    if ($is_log == false) {
                        log::info('采集豆瓣评分-过滤::' . $v['vod_name']);
                    }
                }
                $page = $page + 1;
            }
        } catch (Exception $e) {
            $output->writeln("end.3." . $e);
            $output->writeln("end.311." . $this->vodDb->getlastsql());
            file_put_contents('log.txt', 'close_url||' . $e . PHP_EOL, FILE_APPEND);
        }
        $output->writeln("end....");
    }

    /**
     * 根据豆瓣信息修改相关表信息
     * @param  [type] $as_k      [description]
     * @param  [type] $v         [description]
     * @param  [type] $port_type [description]
     * @param  [type] $url       [description]
     * @param  [type] $cookie    [description]
     * @param  [type] $lcs       [description]
     * @return [type]            [description]
     */
    private function _editDataByDouBanInfo($douban_id = NULL, $v = NULL, $rade = NULL, $lcs, $data = NULL){
        
        if (!empty($data)) {
            Db::startTrans();
            $up_details = true;
            if(!empty($douban_id)){
                // 过滤豆瓣详情数据
                $upDetails = self::_filterDeteilsData( $data );

                $up_details = $this->upDetails($douban_id,$upDetails);
            }

            $vod_data = $this->getDouBanApiData($data);

            $vod_director = $vod_data['vod_director'] ?? '';
            $title = $vod_data['vod_name'] ?? '';
            $title_lang = $vod_data['vod_lang'] ?? '';
            $title_lang = $title . $title_lang;
            $vod_actor = $vod_data['vod_actor'] ?? '';

            if ( empty($rade) ) {
                $rade = $lcs->getSimilar(mac_trim_all(mac_characters_format($v['vod_name'])), mac_trim_all(mac_characters_format($vod_data['vod_name']))) * 100;
            }

            // 相似度
            $vod_actor_rade = mac_intersect(mac_trim_all($v['vod_actor']), mac_trim_all($vod_actor));

            $v['vod_sub'] = isset($v['vod_sub'])?$v['vod_sub']:'';
            $up_res = true;
            $edit_link_table = true;
            $continue = false;

            // 求导演交集
            $old_vod_director = array_filter(explode(',', mac_format_text($v['vod_director'])));
            $new_vod_director = array_filter(explode(',', mac_format_text($vod_director)));

            $vod_director_intersect = array_intersect($old_vod_director, $new_vod_director);

            log::info('相似度:::vod_actor_rade:' . $vod_actor_rade . '---rade:' . $rade . '---vod_name:' . mac_characters_format($v['vod_name']) . '---title:' . $title . '---title|title_lang:' . mac_trim_all(mac_characters_format($v['vod_sub'])) . '---vod_director:' . $v['vod_director'] . '-' . $vod_director . 'vod_director_intersect:' . count($vod_director_intersect));

            if (($vod_actor_rade > 85 || 
                $rade > 95 || 
                $title == mac_characters_format($v['vod_name']) || 
                $title == mac_trim_all(mac_characters_format($v['vod_sub'])) || 
                $title_lang == mac_trim_all(mac_characters_format($v['vod_sub']))) && 
                (count($vod_director_intersect) >= 1)) {
                log::info('采集豆瓣评分-相似度比对成功::OK');
                // if (!empty($v['vod_year']) && isset($vod_data['vod_year'])) {
                //     if ($v['vod_year'] == $vod_data['vod_year']) {
                        if (isset($vod_data['title'])) {
                            unset($vod_data['title']);
                        }
                        if (!empty($vod_data)) {
                            $whereId = [];
                            $whereId['vod_id'] = $v['vod_id'];
                            
                            try {
                                $up_res = $this->vodDb->where($whereId)->update($vod_data);
                                // 更新关联表数据
                                $edit_link_table = $this->editLinkTablesTraitFun($v['vod_id'], $vod_data);
                                $continue = true;
                            } catch (Exception $e) {
                                log::info('采集豆瓣评分-更新关联表异常::' . $e);
                                $continue = false;
                            }
                        }
                //     }
                // }
            }
            log::info('更新各关联表状态：'.$continue.'---'.$up_details.'---'.$up_res.'---'.$edit_link_table);
            if ($continue && $up_details !== false && $up_res !== false && $edit_link_table) {
                Db::commit();
                log::info('采集豆瓣评分-vod-succ::' . $v['vod_name'] . '---' . $v['vod_id']);
            } else {
                Db::rollback();
                log::info('采集豆瓣评分-vod-fail::' . $v['vod_name'] . '---' . $v['vod_id']);
            }
        }
    }
    //修改详情表
    public function upDetails($douban_id,$data){

        $res =  $this->cmsDb->where(['douban_id'=>$douban_id])->update($data);
        if($res !== false){
            log::info('优化：详情表-up-succ' .$res);
        }else{
            log::info('优化：详情表-up-error' .$res);
        }
        return $res;
    }

    /**
     * 过滤豆瓣详情数据
     * @param  [type] $get_url_search_id_data [description]
     * @return [type]                         [description]
     */
    private function _filterDeteilsData( $get_url_search_id_data ){
        $vod_data_list_data = $this->getDouBanApiData($get_url_search_id_data);
        $upDetails = [];
        $upDetails['text'] = json_encode($vod_data_list_data,true);
        $upDetails['type'] = 7;
        $upDetails['name'] = $upDetails['title'] = $vod_data_list_data['vod_name']??'';
        $upDetails['link'] = $vod_data_list_data['vod_reurl']??'';
        $upDetails['abstract'] = '';
        $upDetails['abstract_2'] = '';
        $upDetails['score'] =  $upDetails['rating_nums'] = $vod_data_list_data['vod_douban_score']??0;
        $upDetails['time'] = date("Y-m-d H:i:s",time());
        $upDetails['name_as'] =$vod_data_list_data['vod_sub']??'';
        $upDetails['vod_director'] =$vod_data_list_data['vod_director']??'';
        $upDetails['vod_actor'] =$vod_data_list_data['vod_actor']??'';
        $upDetails['trailer_urls'] =$get_url_search_id_data['trailers']??[];
        if(!empty($upDetails['trailer_urls'])){
            $upDetails['type'] = 6;
            $get_trailers =  $get_url_search_id_data['trailers'];
            if(!empty($get_trailers)){
                $get_trailers =  mac_array_del_column($get_trailers,['subject_id','alt','small','id']);
                foreach ($get_trailers as $trailer_key => $trailer_val){
                    $get_trailers[$trailer_key]['title'] = mac_str_is_html($trailer_val['title']);
                }
            }
            $upDetails['trailer_urls'] =json_encode($get_trailers,true);
            $upDetails['douban_json'] =json_encode($get_url_search_id_data,true);
        }else{
            $upDetails['trailer_urls'] = json_encode([],true);
            if ( empty($get_url_search_id_data)) {
                $upDetails['douban_json'] = json_encode([],true);
            } else {
                $upDetails['douban_json'] = json_encode($get_url_search_id_data,true);
            }
            
        }
        return $upDetails;
    }

    /**
     * 发送ql请求
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    private function _qlRequest( $url, $cookie ){
        $data = $this->ql->get($url, null, [
            // 设置代理
            'proxy' => 'http://' . $this->proxy_server . ":" . $this->get_port,
            // 设置超时时间，单位：秒
            'timeout' => 30,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'User-Agent' => mac_ua_all(rand(0, 16)),
                'Cookie' => $cookie
            ]
        ])->getHtml();

        return $data;
    }

    /**
     * 更新video数据
     * @param  [type] $where [description]
     * @param  [type] $order [description]
     * @param  [type] $page  [description]
     * @param  [type] $limit [description]
     * @param  [type] $start [description]
     * @return [type]        [description]
     */
    private function _getVideoData($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = Db::name('video_vod')
                        ->alias('a')
                        ->join('video b','a.video_id = b.id', 'INNER')
                        ->where($where)
                        // ->where('a.type_id_1 = 2 and b.type_pid = 2 and (b.vod_douban_id = 0 or b.vod_total = 0)')
                        ->where('b.vod_tag like "%国产%"')
                        ->group('a.video_id')
                        ->count();

        $video_vod = Db::name('video_vod')
                        ->alias('a')
                        ->field('a.vod_id')
                        ->join('video b','a.video_id = b.id', 'INNER')
                        ->where($where)
                        // ->where('a.type_id_1 = 2 and b.type_pid = 2 and (b.vod_douban_id = 0 or b.vod_total = 0)')
                        ->where('b.vod_tag like "%国产%"')
                        ->group('a.video_id')
                        ->order($order)
                        ->limit($limit_str)
                        ->select();
// print_r( Db::name('video_vod')->getlastsql());die;
        $vod_ids = array_unique(array_column($video_vod, 'vod_id'));
        $vod_where['vod_id'] = ['in', $vod_ids];

        $list = $this->vodDb->field('vod_id,vod_year,vod_sub,vod_name,vod_class,vod_actor,vod_director,vod_douban_id,vod_douban_score,vod_time')->where($vod_where)->select();

        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }

    /**
     * 修改精选表条件
     * @return [type] [description]
     */
    private function _editVideoSelectWhere()
    {
        $video_selected = Db::name('video_selected')->field('vod_id')->group('vod_id')->select();
        $vod_ids = array_unique(array_column($video_selected, 'vod_id'));
        $where['vod_id'] = ['in', $vod_ids];
        
        return $where;
    }

    //暂时废弃
    public function headers()
    {
        $heads = [
//                    'Accept'=> '*/*',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Access-Control-Allow-Origin' => 'https://search.douban.com',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            'Connection' => 'keep-alive',
            'DNT' => '1',
            'Cache-Control' => 'max-age=0',
            'Content-Type' => 'application/json; charset=utf-8',
            'Host' => 'movie.douban.com',
            'Origin' => 'https://search.douban.com',
//            'Referer' => sprintf($this->search_url_re, $v['vod_name']),
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-site',
            'X-Content-Type-Options' => 'nosniff',
            'X-DAE-App' => 'movie',
            'X-DAE-Instance' => 'default',
            'Sec-Fetch-User' => '?1',
            'X-Douban-Mobileapp' => '0',
            'X-DOUBAN-NEWBID' => 'lPbsZAEfswI',
            'Upgrade-Insecure-Requests' => '1',
            'X-Xss-Protection' => '1; mode=block',
            'Remote Address' => '154.8.131.165:443',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36',
        ];
    }
}