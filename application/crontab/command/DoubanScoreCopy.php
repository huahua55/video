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
        $list = $this->vodDb->field('vod_id,vod_year,vod_sub,vod_name,vod_class,vod_actor,vod_director,vod_douban_id,vod_douban_score')->where($where)->order($order)->limit($limit_str)->select();
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
            Cache::set('vod_id_list_douban_score', '');
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
                'vod_id' => 338249
            ];
            $is_vod_id = Cache::get('vod_id_list_douban_score');
            if (!empty($id)) {
                $where['vod_id'] = ['gt', $id];
            } else {
                if (!empty($is_vod_id)) {
                    $where['vod_id'] = ['gt', $is_vod_id];
                }
            }
            // $startTime =  date("Y-m-d 00:00:00",time());
            // $endTime =  date("Y-m-d 23:59:59",time());
            // $where['vod_time'] =['between',[strtotime($startTime),strtotime($endTime)]];
            $order = 'vod_id asc';
            $cookie = $this->newCookie($cookies);
            //进入循环 取出数据
            while ($is_true) {
                $this->get_zm_port();
                if (empty($this->get_port)) {
                    $this->get_zm_port();
                }

                // 取出数据
                $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
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
                        // Log::info('1111111：：：' . $mac_curl_get_data);
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
                        $error_count++;
                        if ($error_count > 18) {
                            $is_true = false;
                            exit("采集豆瓣评分-错误----");
                            break;
                        }
                    }
                    Log::info('采集豆瓣评分-err--proxyerr_i-' . $this->proxy_server . ":" . $this->get_port);
                    log::info('采集豆瓣评分-url-:' . $url);
                    $mac_curl_get_data = $this->func1();
                    // $mac_curl_get_data = json_decode('[{"episode":"","img":"https://img9.doubanio.com\/view\/photo\/s_ratio_poster\/public\/p2184459296.webp","title":"女子分手专家","url":"https:\/\/movie.douban.com\/subject\/25892152\/?suggest=%E5%A5%B3%E5%AD%90%E5%88%86%E6%89%8B%E4%B8%93%E5%AE%B6","type":"movie","year":"2014","sub_title":"女子分手专家","id":"25892152"}]', true);
                    if (!empty($mac_curl_get_data) && !$v['vod_douban_id']) {
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
                                                // Log::info('2222222：：：' . $get_url_search_id_data);
                                                $get_url_search_id_data = json_decode($get_url_search_id_data, true);
                                                Log::info('采集豆瓣评分-err--proxyb-' . $this->proxy_server . ":" . $this->get_port);
                                            } catch (Exception $e) {
                                                Log::info('采集豆瓣评分-err--过滤' . $e . $url);
                                                Log::info('采集豆瓣评分-err--proxyerrb-' . $this->proxy_server . ":" . $this->get_port);
                                                continue;
                                            }
                                            // $get_url_search_id_data = $this->func1();
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
     * 根据豆瓣id修改信息
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
            log::info('相似度:::vod_actor_rade:' . $vod_actor_rade . '---rade:' . $rade . '---vod_name:' . mac_characters_format($v['vod_name']) . '---title|title_lang:' . mac_trim_all(mac_characters_format($v['vod_sub'])) . '---vod_director:' . $v['vod_director'] . '-' . $vod_director);

            $v['vod_sub'] = isset($v['vod_sub'])?$v['vod_sub']:'';
            $up_res = true;
            $edit_link_table = true;
            $continue = false;

            if (($vod_actor_rade > 85 || 
                $rade > 95 || 
                $title == mac_characters_format($v['vod_name']) || 
                $title == mac_trim_all(mac_characters_format($v['vod_sub'])) || 
                $title_lang == mac_trim_all(mac_characters_format($v['vod_sub']))) && 
                ($v['vod_director'] == $vod_director)) {
                if (!empty($v['vod_year']) && isset($vod_data['vod_year'])) {
                    if ($v['vod_year'] == $vod_data['vod_year']) {
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
                    }
                }
            }
            log::info('更新各关联表状态'.$continue.'---'.$up_details.'---'.$up_res.'---'.$edit_link_table);
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
            $upDetails['douban_json'] = json_encode([],true);
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
    public function func1()
    {
        return json_decode('{"rating": {"max": 10, "average": 3.3, "details": {"1": 93.0, "3": 21.0, "2": 45.0, "5": 5.0, "4": 1.0}, "stars": "20", "min": 0}, "reviews_count": 2, "videos": [{"source": {"literal": "miguvideo", "pic": "http://img3.doubanio.com\/f\/movie\/d3e1f41e6a89962d1a2860dabb04b2d4e0c8df89\/pics\/movie\/video-miguvideo.png", "name": "\u54aa\u5495\u89c6\u9891"}, "sample_link": "https:\/\/m.miguvideo.com\/mgs\/msite\/prd\/detail.html?cid=608674293&amp;pwId=d01197d3076b4164af82983c408bb996", "video_id": "3001995763", "need_pay": false}], "wish_count": 12, "original_title": "\u5973\u5b50\u5206\u624b\u4e13\u5bb6", "blooper_urls": [], "collect_count": 302, "images": {"small": "http://img9.doubanio.com\/view\/photo\/s_ratio_poster\/public\/p2184459296.webp", "large": "http://img9.doubanio.com\/view\/photo\/s_ratio_poster\/public\/p2184459296.webp", "medium": "http://img9.doubanio.com\/view\/photo\/s_ratio_poster\/public\/p2184459296.webp"}, "douban_site": "", "year": "2014", "popular_comments": [{"rating": {"max": 5, "value": 1.0, "min": 0}, "useful_count": 0, "author": {"uid": "59141432", "avatar": "http://img3.doubanio.com\/icon\/u59141432-12.jpg", "signature": "\u81ea\u662f\u5e74\u5c11,\u97f6\u534e\u503e\u590d", "alt": "https:\/\/www.douban.com\/people\/59141432\/", "id": "59141432", "name": "\u82a5\u672b\u597d\u545b"}, "subject_id": "25892152", "content": "\u6211\u4e3a\u4ec0\u4e48\u4f1a\u70b9\u5f00\u8fd9\u73a9\u610f\u3002\u3002\u3002\u3002\u3002\u3002\u3002", "created_at": "2014-05-27 15:36:35", "id": "810751115"}, {"rating": {"max": 5, "value": 2.0, "min": 0}, "useful_count": 0, "author": {"uid": "51029451", "avatar": "http://img9.doubanio.com\/icon\/u51029451-4.jpg", "signature": "", "alt": "https:\/\/www.douban.com\/people\/51029451\/", "id": "51029451", "name": "\u6d77\u661f\u65e0\u68a6"}, "subject_id": "25892152", "content": "\u5c0f\u6210\u672c", "created_at": "2014-11-27 00:28:18", "id": "866583890"}, {"rating": {"max": 5, "value": 1.0, "min": 0}, "useful_count": 0, "author": {"uid": "CobraCB", "avatar": "http://img9.doubanio.com\/icon\/u2072028-5.jpg", "signature": "Alles ist eine fickener Krieg", "alt": "https:\/\/www.douban.com\/people\/CobraCB\/", "id": "2072028", "name": "CobraCB"}, "subject_id": "25892152", "content": "\u8bf4\u5f97\u6ca1\u9519\u786e\u5b9e\u662f\u9662\u7ebf\u54c1\u8d28\uff0c\u662f\u8ddf\u5927\u591a\u6570\u56fd\u4ea7\u70c2\u7247\u540c\u6837\u7684\u9662\u7ebf\u54c1\u8d28\uff0c\u4e3b\u89d2\u4eec\u8fd8\u662f\u597d\u597d\u5750\u53f0\u5427\uff0c\u706f\u5149\u6697\u4e9b\u53ef\u80fd\u770b\u8d77\u6765\u8fd8\u8212\u670d\u4e9b\u3002", "created_at": "2014-06-19 14:53:41", "id": "817729442"}, {"rating": {"max": 5, "value": 1.0, "min": 0}, "useful_count": 0, "author": {"uid": "S7vv", "avatar": "http://img9.doubanio.com\/icon\/u51691062-4.jpg", "signature": "\u6211\u4e0d\u60f3\u98de\u6211\u53ea\u8981\u4f60", "alt": "https:\/\/www.douban.com\/people\/S7vv\/", "id": "51691062", "name": "\u805a\u7fbd\u6210\u7ffc"}, "subject_id": "25892152", "content": "\u4e8c\u903c\u73a9\u610f\u79c0\u4e0b\u9650\u3002\u771f\u4e0d\u5982\u53bb\u770b\u90e8\u5341\u5e74\u524d\u7684\u65e5\u672c\u6bdb\u7247\uff0c\u81f3\u5c11\u4eba\u5bb6\u4e13\u4e1a\u4e14\u656c\u4e1a\u3002", "created_at": "2014-06-01 16:30:36", "id": "812184555"}], "alt": "https:\/\/movie.douban.com\/subject\/25892152\/", "id": "25892152", "mobile_url": "https:\/\/movie.douban.com\/subject\/25892152\/mobile", "photos_count": 80, "pubdate": "", "title": "\u5973\u5b50\u5206\u624b\u4e13\u5bb6", "do_count": null, "has_video": true, "share_url": "http:\/\/m.douban.com\/movie\/subject\/25892152", "seasons_count": null, "languages": ["\u6c49\u8bed\u666e\u901a\u8bdd"], "schedule_url": "", "writers": [{"avatars": null, "name_en": "", "name": "\u80e1\u5578", "alt": null, "id": null}], "pubdates": ["2014-05-22"], "website": "", "tags": ["\u7231\u60c5", "\u4e2d\u56fd\u7535\u5f71", "\u4e2d\u56fd", "\u7092\u4f5c", "\u8ddf\u98ce", "\u65e0\u826f", "2014", "\u9752\u6625", "\u7535\u5f71", "\u56fd\u4ea7\u7535\u5f71"], "has_schedule": false, "durations": [], "genres": ["\u7231\u60c5"], "collection": null, "trailers": [], "episodes_count": null, "trailer_urls": [], "has_ticket": false, "bloopers": [], "clip_urls": [], "current_season": null, "casts": [{"avatars": null, "name_en": "", "name": "\u8499\u7490", "alt": null, "id": null}], "countries": ["\u4e2d\u56fd\u5927\u9646"], "mainland_pubdate": "", "photos": [{"thumb": "https://img1.doubanio.com\/view\/photo\/m\/public\/p2184472848.webp", "image": "https://img1.doubanio.com\/view\/photo\/l\/public\/p2184472848.webp", "cover": "https://img1.doubanio.com\/view\/photo\/sqs\/public\/p2184472848.webp", "alt": "https:\/\/movie.douban.com\/photos\/photo\/2184472848\/", "id": "2184472848", "icon": "https://img1.doubanio.com\/view\/photo\/s\/public\/p2184472848.webp"}, {"thumb": "https://img9.doubanio.com\/view\/photo\/m\/public\/p2184472535.webp", "image": "https://img9.doubanio.com\/view\/photo\/l\/public\/p2184472535.webp", "cover": "https://img9.doubanio.com\/view\/photo\/sqs\/public\/p2184472535.webp", "alt": "https:\/\/movie.douban.com\/photos\/photo\/2184472535\/", "id": "2184472535", "icon": "https://img9.doubanio.com\/view\/photo\/s\/public\/p2184472535.webp"}, {"thumb": "https://img3.doubanio.com\/view\/photo\/m\/public\/p2184471521.webp", "image": "https://img3.doubanio.com\/view\/photo\/l\/public\/p2184471521.webp", "cover": "https://img3.doubanio.com\/view\/photo\/sqs\/public\/p2184471521.webp", "alt": "https:\/\/movie.douban.com\/photos\/photo\/2184471521\/", "id": "2184471521", "icon": "https://img3.doubanio.com\/view\/photo\/s\/public\/p2184471521.webp"}, {"thumb": "https://img3.doubanio.com\/view\/photo\/m\/public\/p2184470312.webp", "image": "https://img3.doubanio.com\/view\/photo\/l\/public\/p2184470312.webp", "cover": "https://img3.doubanio.com\/view\/photo\/sqs\/public\/p2184470312.webp", "alt": "https:\/\/movie.douban.com\/photos\/photo\/2184470312\/", "id": "2184470312", "icon": "https://img3.doubanio.com\/view\/photo\/s\/public\/p2184470312.webp"}, {"thumb": "https://img3.doubanio.com\/view\/photo\/m\/public\/p2185023673.webp", "image": "https://img3.doubanio.com\/view\/photo\/l\/public\/p2185023673.webp", "cover": "https://img3.doubanio.com\/view\/photo\/sqs\/public\/p2185023673.webp", "alt": "https:\/\/movie.douban.com\/photos\/photo\/2185023673\/", "id": "2185023673", "icon": "https://img3.doubanio.com\/view\/photo\/s\/public\/p2185023673.webp"}, {"thumb": "https://img3.doubanio.com\/view\/photo\/m\/public\/p2185023670.webp", "image": "https://img3.doubanio.com\/view\/photo\/l\/public\/p2185023670.webp", "cover": "https://img3.doubanio.com\/view\/photo\/sqs\/public\/p2185023670.webp", "alt": "https:\/\/movie.douban.com\/photos\/photo\/2185023670\/", "id": "2185023670", "icon": "https://img3.doubanio.com\/view\/photo\/s\/public\/p2185023670.webp"}, {"thumb": "https://img9.doubanio.com\/view\/photo\/m\/public\/p2185023665.webp", "image": "https://img9.doubanio.com\/view\/photo\/l\/public\/p2185023665.webp", "cover": "https://img9.doubanio.com\/view\/photo\/sqs\/public\/p2185023665.webp", "alt": "https:\/\/movie.douban.com\/photos\/photo\/2185023665\/", "id": "2185023665", "icon": "https://img9.doubanio.com\/view\/photo\/s\/public\/p2185023665.webp"}, {"thumb": "https://img3.doubanio.com\/view\/photo\/m\/public\/p2184472910.webp", "image": "https://img3.doubanio.com\/view\/photo\/l\/public\/p2184472910.webp", "cover": "https://img3.doubanio.com\/view\/photo\/sqs\/public\/p2184472910.webp", "alt": "https:\/\/movie.douban.com\/photos\/photo\/2184472910\/", "id": "2184472910", "icon": "https://img3.doubanio.com\/view\/photo\/s\/public\/p2184472910.webp"}, {"thumb": "https://img3.doubanio.com\/view\/photo\/m\/public\/p2184472901.webp", "image": "https://img3.doubanio.com\/view\/photo\/l\/public\/p2184472901.webp", "cover": "https://img3.doubanio.com\/view\/photo\/sqs\/public\/p2184472901.webp", "alt": "https:\/\/movie.douban.com\/photos\/photo\/2184472901\/", "id": "2184472901", "icon": "https://img3.doubanio.com\/view\/photo\/s\/public\/p2184472901.webp"}, {"thumb": "https://img3.doubanio.com\/view\/photo\/m\/public\/p2184472843.webp", "image": "https://img3.doubanio.com\/view\/photo\/l\/public\/p2184472843.webp", "cover": "https://img3.doubanio.com\/view\/photo\/sqs\/public\/p2184472843.webp", "alt": "https:\/\/movie.douban.com\/photos\/photo\/2184472843\/", "id": "2184472843", "icon": "https://img3.doubanio.com\/view\/photo\/s\/public\/p2184472843.webp"}], "summary": "2014\u4e2d\u56fd\u4e92\u8054\u7f51\u6700\u5177\u9662\u7ebf\u54c1\u8d28\u7684\u65f6\u5c1a\u7231\u60c5\u7535\u5f71\u300a\u5973\u5b50\u5206\u624b\u4e13\u5bb6\u300b\uff0c\u8bb2\u8ff0\u4e86\u56db\u4e2a\u6027\u683c\u5404\u5f02\u7684\u65f6\u5c1a\u90fd\u5e02\u5973\u6027\uff0c\u56e0\u66fe\u53d7\u8fc7\u611f\u60c5\u521b\u4f24\uff0c\u5bf9\u7231\u60c5\u770b\u6cd5\u504f\u6fc0\u800c\u7ec4\u6210\u4e86\u5973\u5b50\u53cd\u7231\u540c\u76df\uff0c\u4e3a\u4e86\u201c\u8ba9\u66f4\u591a\u4eba\u770b\u6e05\u7231\u60c5\u7684\u771f\u76f8\u201d\u800c\u4e0d\u65ad\u62c6\u6563\u60c5\u4fa3\u3002\u5728\u557c\u7b11\u7686\u975e\u7684\u62c6\u6563\u8fc7\u7a0b\u4e2d\uff0c\u56db\u4eba\u770b\u4f3c\u4e0d\u65ad\u5426\u8ba4\u7740\u7231\u60c5\uff0c\u5176\u5b9e\u4e5f\u662f\u5728\u627e\u5bfb\u4e00\u4efd\u771f\u6b63\u7684\u611f\u60c5\uff0c\u5f53\u7231\u60c5\u5230\u6765\u65f6\uff0c\u8eab\u4e3a\u5206\u624b\u4e13\u5bb6\u7684\u56db\u4eba\u8be5\u5982\u4f55\u6289\u62e9\uff1f\u4e2d\u56fd\u65b0\u5a92\u4f53\u6700\u65f6\u5c1a\u6d6a\u6f2b\u7684\u7231\u60c5\u7535\u5f71\uff0c2014\u5e745\u670820\u65e5\u5168\u7f51\u611f\u52a8\u4e0a\u6620\uff0c\u4ee5\u7231\u4e4b\u540d\uff0c\u4e3a\u7231\u677e\u7ed1\uff01", "clips": [], "subtype": "movie", "directors": [{"avatars": {"small": "http://img9.doubanio.com\/view\/celebrity\/s_ratio_celebrity\/public\/p1498572788.54.webp", "large": "http://img9.doubanio.com\/view\/celebrity\/s_ratio_celebrity\/public\/p1498572788.54.webp", "medium": "http://img9.doubanio.com\/view\/celebrity\/s_ratio_celebrity\/public\/p1498572788.54.webp"}, "name_en": "Tian Mai", "name": "\u9ea6\u7530", "alt": "https:\/\/movie.douban.com\/celebrity\/1337140\/", "id": "1337140"}], "comments_count": 92, "popular_reviews": [{"rating": {"max": 5, "value": 1.0, "min": 0}, "title": "\u5c45\u7136\u662f\u552f\u4e00\u7684\u5f71\u8bc4\uff0c\u89c2\u5f71\u540e\u56db\u5927\u4e0d\u80fd\u5fcd", "subject_id": "25892152", "author": {"uid": "74821075", "avatar": "http://img1.doubanio.com\/icon\/user_normal.jpg", "signature": "", "alt": "https:\/\/www.douban.com\/people\/74821075\/", "id": "74821075", "name": "\u6ce8\u518c"}, "summary": "1.\u5c31\u8fd9\u6837\u7684\u51e0\u4e2a\u5973\u4eba\uff0c\u4e5f\u96be\u602a\u88ab\u7529\uff0c\u6837\u8c8c\u975e\u5e38\u666e\u901a\uff0c\u5c5e\u4e8e\u90a3\u79cd\u4e0d\u5316\u5986\u522b\u51fa\u95e8\uff0c\u5316\u4e86\u5986\u50cf\u5c0f\u59d0\u4ee5\u4e3a\u81ea\u5df1\u6709\u80fd\u529b\u62c6\u6563\u522b\u4eba\uff0c\u5176\u5b9e\u672c\u8eab\u5b9e\u529b\u548c\u8d44\u672c\u5c31\u4e0d\u884c 2.\u51e0\u4e2a\u5973\u4e3b\u7684\u601d\u60f3\u4e5f\u592a\u9f8c\u9f8a\u4e86\u5427\u3002 3.\u628a\u7537\u4eba\u548c\u60c5\u4fa3\u60f3\u7684\u592a\u574f\u4e86 4.\u91cc\u9762\u7684\u60c5\u4fa3...", "alt": "https:\/\/movie.douban.com\/review\/6679737\/", "id": "6679737"}, {"rating": {"max": 5, "value": 2.0, "min": 0}, "title": "\u7ecf\u8d39\u4e0d\u591f\u5c31\u5728\u5267\u672c\u4e0a\u591a\u82b1\u70b9\u5fc3\u601d\u597d\u4e48", "subject_id": "25892152", "author": {"uid": "67960619", "avatar": "http://img9.doubanio.com\/icon\/u67960619-6.jpg", "signature": "\u6545\u5c06\u519b\uff0c\u53ef\u66fe\u5f52\u5434\u5730\uff1f", "alt": "https:\/\/www.douban.com\/people\/67960619\/", "id": "67960619", "name": "\u4e0b\u6cc9"}, "summary": "\u9898\u6750\u9009\u7684\u4e0d\u9519\uff0c\u4e0d\u8fc7\u8fd9\u7f16\u5267\u548c\u5bfc\u6f14\u6709\u5f85\u5546\u8bae\u3002 \u5982\u679c\u8fd9\u90e8\u7535\u5f71\u4ece\u5934\u81f3\u5c3e\u90fd\u63cf\u5199\u4f7f\u574f\u5206\u624b\uff0c\u6700\u540e\u5c0f\u4e09\u7fd8\u4e86\u5899\u89d2\uff0c\u4e5f\u7b97\u795e\u4f5c\u3002\u4e0d\u7136\u5c31\u76f4\u63a5\u575a\u6301\u7231\u60c5\u5fe0\u8d1e\u4e0d\u6e1d\uff0c\u5ba3\u8a00\u8fd9\u4e16\u4e0a\u5c31\u662f\u6709\u5b8c\u5168\u4e0d\u4f1a\u88ab\u8d28\u7591\u7684\u611f\u60c5\uff0c\u4e5f\u7b97\u662f\u7b26\u5408\u4e3b\u6d41\u3002 \u8c01\u77e5\u7f16\u5267...", "alt": "https:\/\/movie.douban.com\/review\/6727364\/", "id": "6727364"}], "ratings_count": 251, "aka": ["Women\u2019s breakup expert"]}',true);
    }
}