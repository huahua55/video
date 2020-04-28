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

class DoubanScore extends Common
{
    protected $vodDb;//db
    protected $search_url_re = 'https://search.douban.com/movie/subject_search?search_text=%s&cat=1002';//豆瓣搜索接口
    protected $search_url = 'https://movie.douban.com/j/subject_suggest?q=%s';//豆瓣搜索接口
    protected $get_search_id = 'http://api.maccms.com/douban/?callback=douban&id=';//cms 通过id获取内容
    protected $ql;//querylist


    protected function configure()
    {
        //db
        $this->vodDb = Db::name('vod');
        $this->ql = QueryList::getInstance();
        //获取豆瓣id
        $this->setName('DoubanScore')->addArgument('parameter')
            ->setDescription('定时计划：采集豆瓣评分');
    }

    // 取出数据豆瓣评分为空数据
    protected function getVodDoubanScoreData($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodDb->where($where)->count();
        $list = $this->vodDb->field('vod_id,vod_name,vod_class,vod_actor,vod_director,vod_douban_id,vod_douban_score')->where($where)->order($order)->limit($limit_str)->select();
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
            if (!empty($type) && $type == 1) {
                Cache::set('vod_id_list_douban_score', 1);
            }
            //选择mac扩展还是 linux 扩展
            if (!empty($x) && $x == 'mac') {
                $ph_js_path = ROOT_PATH . 'extend/phantomjs_macosx/bin/phantomjs';
            } else {
                $ph_js_path = ROOT_PATH . 'extend/phantomjs_linux/bin/phantomjs';
            }
            //使用queryList + PhantomJs
            $this->ql->use(PhantomJs::class, $ph_js_path);
            $this->ql->use(PhantomJs::class, $ph_js_path, 'browser');


            //开启代理
            if($this->get_port  == false){
                sleep(3);
                $this->get_port =   $this->getPort();
                log::info('get_port-::' );
            }
//        p($A);
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
            if(!empty($id)){
                $where['vod_id'] = ['gt', $id];
            }else{
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
                    $is_log = false;
                    $mac_curl_get_data = '';
//               $sleep =  rand(3,10);
//                    sleep(1);

                    if (time() > $this->times + (60 * 3)) {
                        if($this->get_port  == false){
                            sleep(3);
                            $this->get_port =   $this->getPort();
                            log::info('get_port-::' );
                        }
                    }
                    $url = sprintf($this->search_url_re, urlencode($v['vod_name']));
//                var_dump($url);
                    try {
                        $mac_curl_get_data = $this->ql->browser(function (\JonnyW\PhantomJs\Http\RequestInterface $r) use ($url, $cookie) {
                            $r->setMethod('GET');
                            $r->addHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9');
//                        $r->addHeader('Referer', $url);
                            $r->addHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36');
                            $r->addHeader('Cookie', $cookie);
//                        $r->addHeader('Host', 'search.douban.com');
//                        $r->addHeader('DNT', 1);
//                        $r->addHeader('Sec-Fetch-User', '?1');
//                        $r->addHeader('Upgrade-Insecure-Requests', '1');
                            $r->setUrl($url);
                            $r->setTimeout(10000); // 10 seconds
                            $r->setDelay(3); // 3 seconds
                            return $r;
                        }, false, [
//                        '--proxy' => "183.129.244.16:17238",
                            '--proxy' => $this->proxy_server . ":" . $this->get_port,
                            '--proxy-type' => 'http',
//                        '--ssl-protocol' =>'any',
                            '--load-images' => 'no',
//                        '--ignore-ssl-errors' =>true,
//                    ])->getHtml();
                        ])->rules([
                            'rating_nums' => ['.rating_nums', 'text'],
                            'title' => ['a', 'text'],
                            'link' => ['a', 'href'],
                            'abstract' => ['.abstract', 'text'],
                            'abstract_2' => ['.abstract_2', 'text'],
                        ])->range('.item-root')->query()->getData();
                        Log::info('err--proxy-' . $this->proxy_server . ":" . $this->get_port);
                    } catch (Exception $e) {
                        Log::info('err--过滤' . $url);
                        continue;
                    }

                    $getSearchData = objectToArray($mac_curl_get_data);

                    if (empty($mac_curl_get_data)) {
                        log::info('采集豆瓣评分-url-err::');
                        $error_count++;
                        if ($error_count > 10) {
                            log::info('采集豆瓣评分-url-err1::');
                            if($this->get_port  == false){
                                sleep(3);
                                $this->get_port =   $this->getPort();
                                log::info('get_port-::' );
                            }
                            if ($error_count > 15) {
                                Log::info('err--过滤' . $url);
                                continue;
                            }
                        }
                    }
//                print_r($getSearchData);
                    log::info('采集豆瓣评分-url-::' . $url);
//                log::info('采集豆瓣评分-url-data::' . $getSearchData);
                    if (!empty($getSearchData)) {
                        log::info('采集豆瓣评分-url2-::');
                        foreach ($getSearchData as $da_k => $as_k) {
                            log::info('采集豆瓣评分-title1-::' . mac_trim_all($v['vod_name']));
                            log::info('采集豆瓣评分-title2-::' . $as_k['title']);
                            $link = explode('subject', $as_k['link']);
                            $get_search_id = $link[1] ?? '';
                            $get_search_id = str_replace('/', '', $get_search_id);
                            $deas_data = $as_k;
                            $deas_data['douban_id'] = $get_search_id;
                            $deas_data['time'] = time();
                            try {
                                Db::name('douban_vod_details')->insert($deas_data);
                            } catch (\Exception $e) {
                                log::info('采集豆瓣评分-数据重复添加::' . $as_k['title']);
                            }
                            if($g == 1){
                                log::info('采集豆瓣评分-title-su-::g'  . $as_k['title'].'---'.$v['vod_id']);
                            }else{
                                //                        if(mac_trim_all($v['vod_name']) == mac_trim_all($as_k['title'])){
                                $rade = $lcs->getSimilar(mac_trim_all($v['vod_name']), mac_trim_all($as_k['title'])) * 100;
                                log::info('采集豆瓣评分-比例::' . $rade);
                                if ($rade > 50) {
                                    log::info('采集豆瓣评分-title-su-::' . $as_k['title'].'---'.$v['vod_id']);
                                    if (!empty($get_search_id)) {
                                        log::info('采集豆瓣评分-ok-id::' . $get_search_id);
                                        $get_url_search_id = $this->get_search_id . $get_search_id;
                                        $get_url_search_id_data = mac_curl_get($get_url_search_id);
                                        $get_url_search_id_data = str_replace('douban(', '', $get_url_search_id_data);
                                        $get_url_search_id_data = str_replace(');', '', $get_url_search_id_data);
                                        $get_url_search_id_data = $this->isJsonBool($get_url_search_id_data, true);
                                        if (!empty($get_url_search_id_data) && $get_url_search_id_data['code'] == 1 && !empty($get_url_search_id_data['data'])) {
                                            $res = $get_url_search_id_data['data'];
                                            if (($res['vod_name'] == $v['vod_name'] || $res['vod_name'] == $v['vod_sub']) && ($v['vod_director'] == $res['vod_director'])) {
                                                $is_log = true;
                                                $vod_data = $this->getConTent($res);
                                                if (empty($v['vod_sub']) && $v['vod_name'] != $res['vod_name']) {
                                                    $vod_data['vod_sub'] = $res['vod_name'];
                                                }
                                                if (!empty($vod_data)) {
                                                    $whereId = [];
                                                    $whereId['vod_id'] = $v['vod_id'];
                                                    if (isset($vod_data['vod_doucore'])) {
                                                        unset($vod_data['vod_doucore']);
                                                    }
                                                    $up_res = $this->vodDb->where($whereId)->update($vod_data);
                                                    if ($up_res) {
                                                        log::info('采集豆瓣评分-succ::' . $v['vod_name'].'---'.$v['vod_id']);
                                                    }
                                                }
                                            }

                                        }
                                    }
                                }
                            }


                        }

                    }
//                p(1);
                    Cache::set('vod_id_list_douban_score', $v['vod_id']);
                    if ($is_log == false) {
                        log::info('采集豆瓣评分-过滤::' . $v['vod_name']);
                    }
                }
                $page = $page + 1;
            }
        } catch (Exception $e) {
            $output->writeln("end.3.".$e);
            file_put_contents('log.txt', 'close_url||' . $e . PHP_EOL, FILE_APPEND);
        }
        $output->writeln("end....");
    }


    protected function getConTent($res)
    {
        $vod_data = [];
        //总集数
        if (isset($res['vod_total'])) {
            $vod_data['vod_total'] = $res['vod_total'];
        }
        //连载数
        if (isset($res['vod_serial']) && !empty($res['vod_serial'])) {
            $vod_data['vod_serial'] = trim($res['vod_serial']);
        }
        // $vod_data['vod_name'] = $res['vod_name'];
        //  $vod_data['vod_pic'] = $res['vod_pic'];

        //对白语言
        if (isset($res['vod_lang'])) {
            $vod_data['vod_lang'] = $res['vod_lang'];
        }
        //资源类别
        if (isset($res['vod_state'])) {
            $vod_data['vod_state'] = $res['vod_state'];
        }
        //视频标签
        if (isset($res['vod_tag'])) {
//            $vod_data['vod_tag'] = trim(mb_substr($res['vod_tag'], 0, 100));
            $vod_data['vod_tag'] = mac_format_text(trim($res['vod_class']));
        }

        //发行地区
        if (isset($res['vod_area'])) {
            $vod_data['vod_area'] = trim($res['vod_area']);
        }
        //主演列表
        if (isset($res['vod_actor'])) {
            $vod_data['vod_actor'] = $res['vod_actor'];
        }
        //导演
        if (isset($res['vod_director'])) {
            $vod_data['vod_director'] = trim($res['vod_director']);
        }
        //上映日期
        if (isset($res['vod_pubdate'])) {
            $vod_data['vod_pubdate'] = mac_format_text(trim($res['vod_pubdate']));
        }
        //编剧
        if (isset($res['vod_writer'])) {
            $vod_data['vod_writer'] = mac_format_text($res['vod_writer']);
        }
        //平均分
        if (isset($res['vod_score'])) {
            $vod_data['vod_score'] = trim($res['vod_score']);
        }
        //评分次数
        if (isset($res['vod_score_num'])) {
            $vod_data['vod_score_num'] = $res['vod_score_num'];
        }
        //总评分
        if (isset($res['vod_score_all'])) {
            $vod_data['vod_score_all'] = $res['vod_score_all'];
        }
//        //简介
//        if (isset($res['vod_content'])){
//            $vod_content = trim($res['vod_content']);
//            $vod_data['vod_blurb'] = "'$vod_content'";
//        }
        //时长
        if (isset($res['vod_duration'])) {
            $vod_data['vod_duration'] = trim($res['vod_duration']);
        }

        //豆瓣id
        if (isset($res['vod_douban_id'])) {
            $vod_data['vod_douban_id'] = $res['vod_douban_id'];
        }
        //豆瓣评分
        if (isset($res['vod_douban_score'])) {
            $vod_data['vod_douban_score'] = $res['vod_douban_score'];
        }
        //扩展分类
        if (isset($res['vod_class'])) {
            $vod_data['vod_class'] = mac_format_text(trim($res['vod_class']));
        }
        //来源地址
        if (isset($res['vod_reurl'])) {
            $vod_data['vod_reurl'] = trim($res['vod_reurl']);
        }
        //编辑人
        if (isset($res['vod_author'])) {
            $vod_data['vod_author'] = $res['vod_author'];
        }
        return $vod_data;
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


    //获取代理端口
    public function getDouBan()
    {
        //实例简单演示如何正确获取代理端口，使用代理服务测试访问https://ip.cn，验证后释放代理端口
        $file = 'log.txt';
        $port = '';//代理端口变量
        try {
            $open_url = $this->get_open_url();
//            p($open_url);
            $r = file_get_contents($open_url);
            $result = iconv("gb2312", "utf-8//IGNORE", $r);
            $code = json_decode($result, true);
            echo $result . "\n <br>";
            file_put_contents($file, date('Y-m-d H:i:s', time()) . PHP_EOL . 'open_url||' . $result . PHP_EOL, FILE_APPEND);
            $json_arr = json_decode($result, true);
            $code = $json_arr['code'];
            if ($code == 108) {
                $reset_url = $this->get_reset_url();
                $r = file_get_contents($reset_url);
            } else if ($code == 100) {
                $port = strval($json_arr['port'][0]);
            }

        } catch (\Exception $e) {
            file_put_contents($file, 'open_url||' . $e . PHP_EOL, FILE_APPEND);
        }
        return $port;
    }



}