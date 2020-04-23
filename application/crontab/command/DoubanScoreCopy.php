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

class DoubanScoreCopy extends Command
{
    protected $vodDb;//db
    protected $cmsDb;//db
    protected $search_url_re = 'https://search.douban.com/movie/subject_search?search_text=%s&cat=1002';//豆瓣搜索接口
    protected $search_url = 'https://movie.douban.com/j/subject_suggest?q=%s';//豆瓣搜索接口
    protected $get_search_id = 'http://api.douban.com/v2/movie/subject/%s?apikey=0df993c66c0c636e29ecbb5344252a4a';
    protected $ql;//querylist
    protected $times;//超时time
    protected $get_port;//port


    //代理使用
    protected $proxy_username = 'zhangshanap1';
    protected $proxy_passwd = '76836051';
    protected $proxy_server = '183.129.244.16';
    protected $proxy_port = '88';
    protected $pattern = 'json';//API访问返回信息格式：json和text可选
    protected $num = 1;//获取代理端口数量
    protected $key_name = 'user_name=';
    protected $key_timestamp = 'timestamp=';
    protected $key_md5 = 'md5=';
    protected $key_pattern = 'pattern=';
    protected $key_num = 'number=';
    protected $key_port = 'port=';

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

    //返回当前时间戳（单位为 ms）
    public function get_timestamp()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

//进行md5加密
    public function get_md5_str($str)
    {
        return md5($str);
    }

//返回请求分配代理端口URL链接
    public function get_open_url()
    {
        $this->times = time();
        $time_stamp = $this->get_timestamp();
        $md5_str = $this->get_md5_str($this->proxy_username . $this->proxy_passwd . strval($time_stamp));
        return 'http://' . $this->proxy_server . ':'
            . $this->proxy_port . '/open?' . $this->key_name . $this->proxy_username .
            '&' . $this->key_timestamp . strval($time_stamp) .
            '&' . $this->key_md5 . $md5_str .
            '&' . $this->key_pattern . $this->pattern .
            '&' . $this->key_num . strval($this->num);
    }

//返回释放代理端口URL链接
    public function get_close_url($auth_port)
    {
        $time_stamp = $this->get_timestamp();
        $md5_str = $this->get_md5_str($this->proxy_username . $this->proxy_passwd . strval($time_stamp));
        return 'http://' . $this->proxy_server . ':'
            . $this->proxy_port . '/close?' . $this->key_name . $this->proxy_username .
            '&' . $this->key_timestamp . strval($time_stamp) .
            '&' . $this->key_md5 . $md5_str .
            '&' . $this->key_pattern . $this->pattern .
            '&' . $this->key_port . strval($auth_port);
    }

//返回重置本用户已使用ip URL链接
    public function get_reset_url()
    {
        $time_stamp = $this->get_timestamp();
        $md5_str = $this->get_md5_str($this->proxy_username . $this->proxy_passwd . strval($time_stamp));
        return 'http://' . $this->proxy_server . ':'
            . $this->proxy_port . '/reset_ip?' . $this->key_name . $this->proxy_username .
            '&' . $this->key_timestamp . strval($time_stamp) .
            '&' . $this->key_md5 . $md5_str .
            '&' . $this->key_pattern . $this->pattern;
    }

//使用代理进行测试 url为使用代理访问的链接，auth_port为代理端口
    public function testing($url, $auth_port)
    {
        $ch = curl_init();
        $timeout = 30;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
        curl_setopt($ch, CURLOPT_PROXY, $this->proxy_server); //代理服务器地址
        curl_setopt($ch, CURLOPT_PROXYPORT, $auth_port); //代理服务器端口
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式
        //如果访问为https协议
        if (substr($url, 0, 5) == "https") {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        }

        $file_contents = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $httpCode;
    }

    //更新cookie
    protected function getCookie($url)
    {
        return 'll="108288";bid=h4nqLajQEBo';
    }

    protected function newCookie($cookies)
    {
        $cookie = 'll="108288";bid=4OXcKrHanRU; douban-fav-remind=1; __gads=ID=f547fc5d1024460e:T=1584933974:S=ALNI_MYnz5KEHQFfcZy0gMy6CM04qFHEGg;  _vwo_uuid_v2=DE8FD61CD60225FE96D81709B68421C2D|866f6dabae9a822d17e89ca947c01f78; __yadk_uid=HPbvxvJ9JN8yUqI6foqDYbhNLOHg2OMc; __utmc=30149280; push_noty_num=0; push_doumail_num=0; __utmv=30149280.21552; douban-profile-remind=1; __utmz=30149280.1587373187.4.3.utmcsr=baidu|utmccn=(organic)|utmcmd=organic; dbcl2="215524010:bdDl9E8vVTg"; ck=m31b; ap_v=0,6.0; ct=y; _pk_ref.100001.2939=%5B%22%22%2C%22%22%2C1587439340%2C%22https%3A%2F%2Fmovie.douban.com%2F%22%5D; _pk_ses.100001.2939=*; __utma=30149280.1772134204.1587359482.1587432721.1587439341.7; __utmt=1; _pk_id.100001.2939=1deb2b5e8988f44c.1587174800.9.1587439359.1587434637.; __utmb=30149280.9.9.1587439359009';
        $cookieArray = explode(';', $cookie);

        $cookieArray[16] = '_pk_ref.100001.2939' . urlencode('["","",time(),"https://movie.douban.com/"]');
        $cookieArray[21] = '30149280.9.9.' . time() . rand(0, 9) . rand(1, 6) . rand(0, 6);
        $cookieArray[3] = str_replace('T=1584933974', 'T=' . time(), $cookieArray[3]);
        $cookieArray[11] = str_replace('1587373187', time(), $cookieArray[11]);
        $cookieArray[18] = str_replace('1587439341', time(), $cookieArray[18]);
        $cookieArray[20] = str_replace('1587439359', time() + 11, $cookieArray[20]);
        $cookieArray[20] = str_replace('1587174800', time() + 600, $cookieArray[20]);
        $cookieArray[0] = $cookies;
        unset($cookieArray[1]);
        return implode($cookieArray, ';');
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

            //开启代理
            $this->get_port = $this->getDouBan();
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
                    sleep(2);
                    if (time() > $this->times + (60 * 3)) {
                        $this->get_port = $this->getDouBan();
                    }
                    $url = sprintf($this->search_url, urlencode($v['vod_name']));
//                var_dump($url);
                    try {
//                        $cookie = 'bid=tre-gFuRDCw; Expires=Fri, 23-Apr-21 10:03:41 GMT; Domain=.douban.com; Path=/';
                        $mac_curl_get_data = $this->ql->get($url, null, [
                            // 设置代理
//                            'proxy' => 'http://183.129.244.16:55466',
                            'proxy' => 'http://'. $this->proxy_server . ":" . $this->get_port,
                            //设置超时时间，单位：秒
                            'timeout' => 30,
                            'headers' => [
                                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36',
                                'Cookie' => $cookie
                            ]
                        ])->getHtml();
                        $mac_curl_get_data = json_decode($mac_curl_get_data, true);
                        Log::info('err--proxy-' . $this->proxy_server . ":" . $this->get_port);
                    } catch (Exception $e) {
                        Log::info('err--过滤' . $url);
                        continue;
                    }
                    if (empty($mac_curl_get_data)) {
                        log::info('采集豆瓣评分-url-err::');
                        $error_count++;
                        if ($error_count > 10) {
                            log::info('采集豆瓣评分-url-err1::');
                            $tmp = $this->testing($url, $this->get_port);
                            if ($tmp != 200 && $this->times + (50 * 3)) {
                                $this->get_port = $this->getDouBan(); //重新构成代理端口
                                echo 'test_proxy|| httpCode:' . $tmp . "\n <br>";
                                file_put_contents('log.txt', 'test_proxy|| httpCode:' . $tmp . PHP_EOL, FILE_APPEND);
                                try {
                                    $close_url = $this->get_close_url($this->get_port);
                                    $r = file_get_contents($close_url);
                                    $result = iconv("gb2312", "utf-8//IGNORE", $r);
                                    echo 'close_url||' . $result;
                                    file_put_contents('log.txt', 'close_url||' . $result . PHP_EOL, FILE_APPEND);
                                } catch (Exception $e) {
                                    file_put_contents('log.txt', 'close_url||' . $e . PHP_EOL, FILE_APPEND);
                                }
                            }
                        }
                    }
                    log::info('采集豆瓣评分-url-::' . $url);
                    if (!empty($mac_curl_get_data)) {
                        log::info('采集豆瓣评分-url2-::');
                        foreach ($mac_curl_get_data as $da_k => $as_k) {
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
                                $rade = $lcs->getSimilar(mac_trim_all($v['vod_name']), mac_trim_all($as_k['title'])) * 100;
                                log::info('采集豆瓣评分-比例::' . $rade);
                                if ($rade > 50) {
                                    log::info('采集豆瓣评分-title-su-::' . $as_k['title'] . '---' . $v['vod_id']);
                                    if (!empty($as_k['id'])) {
                                        log::info('采集豆瓣评分-ok-id::' . $as_k['id']);
                                        $get_url_search_id = sprintf($this->get_search_id, $as_k['id']);
                                        try {
                                            sleep(2);
                                            $get_url_search_id_data = $this->ql->get($get_url_search_id, null, [
                                                // 设置代理
                                             'proxy' => 'http://'. $this->proxy_server . ":" . $this->get_port,
                                                //设置超时时间，单位：秒
                                                'timeout' => 30,
                                                'headers' => [
                                                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                                                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36',
                                                    'Cookie' => $cookie
                                                ]
                                            ])->getHtml();
                                            $get_url_search_id_data = json_decode($get_url_search_id_data, true);
                                            Log::info('err--proxy-' . $this->proxy_server . ":" . $this->get_port);
                                        } catch (Exception $e) {
                                            Log::info('err--过滤' . $e . $url);
                                            continue;
                                        }

                                        if (!empty($get_url_search_id_data)) {

                                            $vod_data = $this->getConTent($get_url_search_id_data, $as_k['id']);
                                            $vod_director = $vod_data['vod_director']??'';
                                            if (($vod_data['title'] == $v['vod_name'] || $vod_data['title'] == $v['vod_sub']) && ($v['vod_director'] == $vod_director)) {
                                                $details_data = [];
                                                $details_data['name'] = $vod_data['title'] ?? '';
                                                $details_data['name_as'] = $vod_data['vod_sub'] ?? '';
                                                $details_data['vod_director'] = $vod_data['vod_director'] ?? '';
                                                $details_data['vod_actor'] = $vod_data['vod_actor'] ?? '';
                                                $details_data['score'] = $vod_data['vod_douban_score'] ?? '0.0';
                                                $details_data['text'] = json_encode($vod_data, true);
                                                if (isset($vod_data['title'])) {
                                                    unset($vod_data['title']);
                                                }
                                                if (!empty($vod_data)) {
                                                    log::info('vod--');
                                                    $whereId = [];
                                                    $whereId['vod_id'] = $v['vod_id'];
                                                    $up_res = $this->vodDb->where($whereId)->update($vod_data);
                                                    if ($up_res) {
                                                        log::info('采集豆瓣评分-vod-succ::' . $v['vod_name'] . '---' . $v['vod_id']);
                                                    }
                                                }
                                                if (!empty($details_data)) {
                                                    log::info('vo222d--');
                                                    $where_id = [];
//                                                    var_dump(1);die;
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

    function strToUtf8($str)
    {
        $encode = mb_detect_encoding($str, array("ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5'));
        if ($encode == 'UTF-8') {
            return $str;
        } else {
            return mb_convert_encoding($str, 'UTF-8', $encode);
        }
    }

    protected function isJsonBool($data = '', $assoc = false)
    {
        $data = json_decode($data, $assoc);
        if (($data && is_object($data)) || (is_array($data) && !empty($data))) {
            return $data;
        }
        return false;
    }


    protected function getConTent($get_url_search_id_data, $id)
    {
        $vod_data = [];
        if (isset($get_url_search_id_data['aka'])) {
            $vod_data['vod_sub'] = implode('/', $get_url_search_id_data['aka']);
        }
        if (isset($get_url_search_id_data['episodes_count'])) {
            $vod_data['vod_total'] = $get_url_search_id_data['episodes_count'] ?? '';
            // $vod_data['vod_serial'] ='';
        }
        if (isset($get_url_search_id_data['languages'])) {
            $vod_data['vod_lang'] = implode('/', $get_url_search_id_data['languages']);
        }
        $vod_data['vod_state'] = '正片';
        if (isset($get_url_search_id_data['countries'])) {
            $vod_data['vod_area'] = implode('/', $get_url_search_id_data['countries']);
        }
        if (isset($get_url_search_id_data['casts'])) {
            $vod_data['vod_actor'] = implode(',', array_column($get_url_search_id_data['casts'], 'name'));
        }
        if (isset($get_url_search_id_data['directors'])) {
            $vod_data['vod_director'] = implode(',', array_column($get_url_search_id_data['directors'], 'name'));
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
//        if($vod_data['vod_score_all'] == 0 || empty($vod_data['vod_score_all'])){
//            $vod_data['vod_score'] = intval($vod_data['vod_score_num']) ;
//        }else{
//            $vod_data['vod_score'] = intval($vod_data['vod_score_all']);
//        }
//        if (isset($vod_data['vod_score_num']) && isset($vod_data['vod_score_all'])) {

//        }
        if (isset($get_url_search_id_data['summary'])) {
            $vod_data['vod_blurb'] = $get_url_search_id_data['summary'];
        }
        if (isset($get_url_search_id_data['durations'][0])) {
            $vod_data['vod_duration'] = $get_url_search_id_data['durations'][0];
        }
        $vod_data['vod_douban_id'] = $id;
        if (isset($get_url_search_id_data['genres'])) {
            $vod_data['vod_tag'] = $vod_data['vod_class'] = implode(',', $get_url_search_id_data['genres']);
        }
        if (isset($get_url_search_id_data['title'])) {
            $vod_data['title'] = $get_url_search_id_data['title'];
        }
        if (isset($get_url_search_id_data['share_url'])) {
            $vod_data['vod_reurl'] = $get_url_search_id_data['share_url'];
        }
        $vod_data['vod_author'] = '豆瓣';
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

    protected function ParSing($parameter)
    {
        $parameter_array = array();
        $arry = explode('#', $parameter);
        foreach ($arry as $key => $value) {
            $zzz = explode('=', $value);
            $parameter_array[$zzz[0]] = $zzz[1]??'';

        }
        return $parameter_array;

    }


}