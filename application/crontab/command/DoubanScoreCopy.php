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
    protected $search_url_re = 'https://search.douban.com/movie/subject_search?search_text=%s&cat=1002';//豆瓣搜索接口
    protected $search_url = 'https://movie.douban.com/j/subject_suggest?q=%s';//豆瓣搜索接口
    protected $get_search_id = 'http://api.douban.com/v2/movie/subject/%s?apikey=0df993c66c0c636e29ecbb5344252a4a';
    protected $ql;//querylist
    protected $num = 5;//获取代理端口数量


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
        $list = $this->vodDb->field('vod_id,vod_sub,vod_name,vod_class,vod_actor,vod_director,vod_douban_id,vod_douban_score')->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }


    protected function execute(Input $input, Output $output)
    {
        // 输出到日志文件
        $output->writeln("开启采集:采集豆瓣评分");
        try {
            //字符串对比算法
            $lcs = new similarText();
            //cli模式接受参数
            $myparme = $input->getArguments();
            $parameter = $myparme['parameter'];
            //参数转义解析
            $param = $this->ParSing($parameter);
            $type = $param['type'] ?? ''; //从1 开始爬取
            $x = $param['x'] ?? '';
            $id = $param['id'] ?? '';
            $g = $param['g'] ?? '';
            $port_type = $param['port_type'] ?? '';
            if (!empty($type) && $type == 1) {
                Cache::set('vod_id_list_douban_score', 1);
            }
            //开始cookie
            $cookies = $this->getCookie('https://movie.douban.com/');
            $start = 0;
            $page = 1;
            $limit = 20;
            $is_true = true;
            $where = [
                'vod_douban_id' => 0,
            ];
            $is_vod_id = Cache::get('vod_id_list_douban_score');
            if (!empty($id)) {
                $where['vod_id'] = ['gt', $id];
            } else {
                if (!empty($is_vod_id)) {
                    $where['vod_id'] = ['gt', $is_vod_id];
                }
            }


//        $startTime =  date("Y-m-d 00:00:00",time());
//        $endTime =  date("Y-m-d 23:59:59",time());
//        $where['vod_time'] =['between',[strtotime($startTime),strtotime($endTime)]];
            $order = 'vod_id asc';
            $cookie = $this->newCookie($cookies);
            //进入循环 取出数据
            while ($is_true) {
                $this->get_zm_port();
                if (empty($this->get_port)) {
                    $this->get_zm_port();
                }
                //取出数据
                $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
//            print_r( $this->vodDb->getlastsql());die;
                $pagecount = $douBanScoreData['pagecount'] ?? 0;
                if ($page > $pagecount) {
                    $is_true = false;
                    log::info('采集豆瓣评分结束...');
                    $output->writeln("结束....");
                    break;
                }

                foreach ($douBanScoreData['list'] as $k => $v) {
                    $error_count = 1;
                    $error_i_count = 1;
                    $is_log = false;
                    //开启代理
                    $url = sprintf($this->search_url, urlencode($v['vod_name']));
                    try {
//                        if(empty($this->get_port)){
                        $this->get_zm_port();
//                        }
                        usleep(50000);
//                        $cookie = 'bid=tre-gFuRDCw; Expires=Fri, 23-Apr-21 10:03:41 GMT; Domain=.douban.com; Path=/';
                        if ($port_type == 1) {
                            $str_data = $this->getUrl($url);
                            $mac_curl_get_data = array_pop(explode("\r\n", $str_data));
                        } else {
                            $mac_curl_get_data = $this->ql->get($url, null, [
                                // 设置代理
//                            'proxy' => 'http://183.129.244.16:55466',
                                'proxy' => 'http://' . $this->proxy_server . ":" . $this->get_port,
                                //设置超时时间，单位：秒
                                'timeout' => 30,
                                'headers' => [
                                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                                    'User-Agent' => mac_ua_all(rand(0, 17)),
                                    'Cookie' => $cookie
                                ]
                            ])->getHtml();
                        }
                        $mac_curl_get_data = json_decode($mac_curl_get_data, true);
                        Log::info('err--proxyi-' . $this->proxy_server . ":" . $this->get_port);
                    } catch (Exception $e) {
                        $error_i_count++;
                        if ($error_i_count > 18) {
                            $is_true = false;
                            exit("错误i----");
                            break;
                        }
                        Log::info('err--过滤' . $url);
                        Log::info('err--过滤' . $e . '---' . $url);
                        Log::info('err--proxyerr_i-' . $this->proxy_server . ":" . $this->get_port);
                        continue;
                    }
                    if (empty($mac_curl_get_data)) {
                        Log::info('err--proxyerr_i-' . $this->proxy_server . ":" . $this->get_port);
                        $error_count++;
                        if ($error_count > 18) {
                            $is_true = false;
                            exit("错误----");
                            break;
                        }
                    }
                    log::info('采集豆瓣评分-url-:' . $url);
                    if (!empty($mac_curl_get_data)) {
                        log::info('采集豆瓣评分-url2-::');
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
                                $deas_data['time'] = time();
                                try {
                                    Db::name('douban_vod_details')->insert($deas_data);
                                } catch (\Exception $e) {
                                    log::info('采集豆瓣评分-数据重复添加::' . $as_k['title']);
                                }
                                if ($g == 1) {
                                    log::info('采集豆瓣评分-title-su-::g' . $as_k['title'] . '---' . $v['vod_id']);
                                } else {
                                    //                        if(mac_trim_all($v['vod_name']) == mac_trim_all($as_k['title'])){
                                    $rade = $lcs->getSimilar(mac_trim_all(mac_characters_format($v['vod_name'])), mac_trim_all(mac_characters_format($as_k['title']))) * 100;
                                    log::info('采集豆瓣评分-比例::' . $rade);
                                    if ($rade > 50) {
                                        log::info('采集豆瓣评分-title-su-::' . $as_k['title'] . '---' . $v['vod_id']);
                                        if (!empty($as_k['id'])) {
                                            log::info('采集豆瓣评分-ok-id::' . $as_k['id']);
                                            $get_url_search_id = sprintf($this->get_search_id, $as_k['id']);
                                            try {
//                                                if(empty($this->get_port)){
                                                $this->get_zm_port();
//                                                }
                                                usleep(50000);
                                                if ($port_type == 1) {
                                                    $str_data = $this->getUrl($url);
                                                    $get_url_search_id_data = array_pop(explode("\r\n", $str_data));
                                                } else {
                                                    $get_url_search_id_data = $this->ql->get($get_url_search_id, null, [
                                                        // 设置代理
                                                        'proxy' => 'http://' . $this->proxy_server . ":" . $this->get_port,
                                                        //设置超时时间，单位：秒
                                                        'timeout' => 30,
                                                        'headers' => [
                                                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                                                            'User-Agent' => mac_ua_all(rand(0, 17)),
                                                            'Cookie' => $cookie
                                                        ]
                                                    ])->getHtml();
                                                }
                                                $get_url_search_id_data = json_decode($get_url_search_id_data, true);
                                                Log::info('err--proxyb-' . $this->proxy_server . ":" . $this->get_port);
                                            } catch (Exception $e) {
                                                Log::info('err--过滤' . $e . $url);
                                                Log::info('err--proxyerrb-' . $this->proxy_server . ":" . $this->get_port);
                                                continue;
                                            }

                                            if (!empty($get_url_search_id_data)) {
                                                $vod_data = $this->getConTent($get_url_search_id_data, $as_k['id']);

                                                $vod_director = $vod_data['vod_director'] ?? '';
                                                $title = $vod_data['title'] ?? '';
                                                $title_lang = $vod_data['vod_lang'] ?? '';
                                                $title_lang = $title . $title_lang;
                                                $vod_actor = $vod_data['vod_actor'] ?? '';
                                                //相似度
                                                $vod_actor_rade = mac_intersect(mac_trim_all($v['vod_actor']), mac_trim_all($vod_actor));
                                                log::info('采集豆瓣评分-rade:' . $v['vod_actor'] . '--' . $vod_actor . '-rade--' . $vod_actor_rade . '-radename--' . $rade);
                                                if (($vod_actor_rade > 85 || $rade > 95 || $title == mac_characters_format($v['vod_name']) || $title == mac_trim_all(mac_characters_format($v['vod_sub'])) || $title_lang == mac_trim_all(mac_characters_format($v['vod_sub']))) && ($v['vod_director'] == $vod_director)) {
                                                    if (isset($vod_data['title'])) {
                                                        unset($vod_data['title']);
                                                    }
                                                    if (!empty($vod_data)) {
                                                        log::info('vod--');
                                                        $whereId = [];
                                                        $whereId['vod_id'] = $v['vod_id'];
                                                        try {
                                                            $up_res = $this->vodDb->where($whereId)->update($vod_data);
                                                        } catch (Exception $e) {
                                                            log::info('采集豆瓣评分-过滤::');
                                                            continue;
                                                        }
                                                        if ($up_res) {
                                                            log::info('采集豆瓣评分-vod-succ::' . $v['vod_name'] . '---' . $v['vod_id']);
                                                        }
                                                    }
                                                }
                                                $details_data = [];
                                                $details_data['name'] = $title;
                                                $details_data['name_as'] = $vod_data['vod_sub'] ?? '';
                                                $details_data['vod_director'] = $vod_data['vod_director'] ?? '';
                                                $details_data['vod_actor'] = $vod_data['vod_actor'] ?? '';
                                                $details_data['score'] = $vod_data['vod_douban_score'] ?? '0.0';
                                                $details_data['text'] = json_encode($vod_data, true);
                                                if (!empty($details_data)) {
                                                    $where_id = [];
                                                    $where_id['douban_id'] = $as_k['id'];
                                                    $up_res = $this->cmsDb->where($where_id)->update($details_data);
                                                    if ($up_res) {
                                                        log::info('采集豆瓣评分-deteils-succ::' . $v['vod_name'] . '---' . $v['vod_id']);
                                                    }
                                                }

                                            }

                                        }
                                    }
                                }
                            }
                        }
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

    protected function getConTent($get_url_search_id_data, $id)
    {
        $vod_data = [];
        if (isset($get_url_search_id_data['aka'])) {
            array_push($get_url_search_id_data['aka'], $get_url_search_id_data['original_title']);
            $get_url_search_id_data['aka'] = array_unique($get_url_search_id_data['aka']);
            $vod_data['vod_sub'] = implode(',', $get_url_search_id_data['aka']);
        }
        if (isset($get_url_search_id_data['episodes_count'])) {
            $vod_data['vod_total'] = $get_url_search_id_data['episodes_count'] ?? '';
            // $vod_data['vod_serial'] ='';
        }
        if (isset($get_url_search_id_data['languages'])) {
            $vod_data['vod_lang'] = implode(',', $get_url_search_id_data['languages']);
        }
        if (isset($get_url_search_id_data['has_video']) && $get_url_search_id_data['has_video'] == false) {
            $vod_data['vod_state'] = '暂无上映';
        } else {
            $vod_data['vod_state'] = '正片';
        }

        if (isset($get_url_search_id_data['countries'])) {
            $vod_data['vod_area'] = implode(',', $get_url_search_id_data['countries']);
        }
        if (isset($get_url_search_id_data['casts'])) {
            $vod_data['vod_actor'] = implode(',', array_column($get_url_search_id_data['casts'], 'name'));
        }

        if (isset($get_url_search_id_data['directors'])) {
            $vod_data['vod_director'] = mac_substring(implode(',', array_column($get_url_search_id_data['directors'], 'name')), 255);
        }
        if (isset($get_url_search_id_data['writers'])) {
            $vod_data['vod_writer'] = implode(',', array_column($get_url_search_id_data['writers'], 'name'));
        }
        if (isset($get_url_search_id_data['pubdate']) && !empty($get_url_search_id_data['pubdate'])) {
            $vod_data['vod_pubdate'] = $get_url_search_id_data['pubdate'];
        }
        if (isset($get_url_search_id_data['rating']['average'])) {
            $vod_data['vod_douban_score'] = $vod_data['vod_score_all'] = $get_url_search_id_data['rating']['average'];
        }
        if (isset($get_url_search_id_data['ratings_count'])) {
            $vod_data['vod_score_num'] = $get_url_search_id_data['ratings_count'];
        }
//        if (isset($get_url_search_id_data['summary'])) {
//            $vod_data['vod_blurb'] = $get_url_search_id_data['summary'];
//        }
        if (isset($get_url_search_id_data['durations'][0])) {
            $vod_data['vod_duration'] = $get_url_search_id_data['durations'][0];
            if (strpos($vod_data['vod_duration'], '(') !== false) {
                $vod_data['vod_duration'] = explode('(', $vod_data['vod_duration'])[0] ?? $vod_data['vod_duration'];
            }
        }
        $vod_data['vod_douban_id'] = $id;
        if (isset($get_url_search_id_data['genres'])) {
            $vod_data['vod_tag'] = $vod_data['vod_class'] = implode(',', $get_url_search_id_data['genres']);
        }
        if (isset($get_url_search_id_data['title'])) {
            $vod_data['title'] = mac_trim_all(mac_characters_format($get_url_search_id_data['title']));
        }
        if (isset($get_url_search_id_data['share_url'])) {
            $vod_data['vod_reurl'] = $get_url_search_id_data['share_url'];
        }
        $vod_data['vod_author'] = '豆瓣';
        return $vod_data;
    }


    //获取getDate
    public function getPortData()
    {
        $get_port = $this->getPort(0, true);
//                    time() > ($this->times + 180)
        if (empty($get_port)) {
            $get_port = $this->getPort(3, true);
        }
        $get_port_count = rand(1, count($get_port));
        if (count($get_port) < $this->num) {
            $get_port = $this->getPort(3, true);
            $get_port_count = rand(1, count($get_port));
        }
        $k = $get_port_count - 1;
        $this->get_port = isset($get_port[$k]) ? $get_port[$k] : '';

    }

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

    public function getUrl($targetUrl)
    {
        // 要访问的目标页面
        $proxyServer = "http" . ":" . "http://" . $this->proxy_server . ":" . $this->get_port;
        // 隧道身份信息
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $targetUrl);

        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // 设置代理服务器
        curl_setopt($ch, CURLOPT_PROXYTYPE, 0); //http
//
//        curl_setopt($ch, CURLOPT_PROXYTYPE, 5); //sock5

        curl_setopt($ch, CURLOPT_PROXY, $proxyServer);

        // 设置隧道验证信息
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;)");

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_setopt($ch, CURLOPT_HEADER, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}