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

class Common extends Command
{

    protected $times;//超时time
    protected $get_port;//port

    //代理使用
    protected $proxy_username = 'zhangshanap3';
    protected $proxy_passwd = '64343975';
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


    public function ParSing($parameter)
    {
        $parameter_array = array();
        $arry = explode('#', $parameter);
        foreach ($arry as $key => $value) {
            $zzz = explode('=', $value);
            $parameter_array[$zzz[0]] = $zzz[1] ?? '';

        }
        return $parameter_array;

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
        Cache::set('vod_times_cj_open_url', time());
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

    //获取芝麻代理ip
    public function get_zm_port($i = false)
    {
        //查找是否存在ip
        $port_log_data = Db::name('port_log')->where(['state' => 1, 'type' => 1])->select();
        $port_data = [];
        if (empty($port_log_data) || $i == true) {
            $url = 'http://webapi.http.zhimacangku.com/getip?num=2&type=2&pro=&city=0&yys=0&port=1&time=1&ts=1&ys=1&cs=1&lb=1&sb=0&pb=4&mr=2&regions=';
            $data = mac_curl_get($url);
            $data = json_decode($data, true);
            if ($data['code'] == 0 && !empty($data['data'])) {
                foreach ($data['data'] as $k => $v) {
                    $port_data[$k]['ip'] = trim($v['ip']);
                    $port_data[$k]['port'] = trim($v['port']);
                    $port_data[$k]['expire_time'] = date("Y-m-d H:i:s",strtotime(trim($v['expire_time'])) - 1*60);;
                    $port_data[$k]['type'] = 1;
                    $port_data[$k]['state'] = 1;
                }
                Db::name('port_log')->insertAll($port_data);
            }
        } else {
            foreach ($port_log_data as $k => $v) {
                if (time() > strtotime($v['expire_time'])) {
                    unset($port_log_data[$k]);
                    Db::name('port_log')->where(['id' => $v['id']])->update(['state'=>2]);
                }
            }
        }
        $count = count($port_log_data);//数量
        if($count < 2){
            $this->get_zm_port(true);
        }
        $count = count($port_log_data);//数量
        $rand = rand(1, $count);
        $rand = $rand - 1;
        if ($rand < 0) {
            $rand = 0;
        }
//        echo 'httpCode:' . json_encode($port_log_data[$rand], true) . "\n <br>";
        $this->proxy_server = $port_log_data[$rand]['ip'];
        $this->get_port = $port_log_data[$rand]['port'];
        $this->times = strtotime($port_log_data[$rand]['expire_time']);
    }

    //获取余额
    public function get_zm_port_money()
    {
        $url = 'wapi.http.cnapi.cc/index/index/get_my_balance?neek=113896&appkey=9351187cca0de8584202f4257a5b17f2';
        return mac_curl_get($url);
    }

//使用代理进行测试 url为使用代理访问的链接，auth_port为代理端口
    public function testing($url, $auth_port, $s = 1)
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
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        }

        $file_contents = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($s = 1) {
            return $httpCode;
        } else {
            return $file_contents;
        }

    }

    //更新cookie
    protected function getCookie($url, $is = false)
    {
        if ($is != true) {
            $str = [
//                0=>'h4nqLajQEBo',
                1 => '4OXcKrHanRU',
                2 => 'a0drRqH-g-0',
                3 => 'BsAnBI9c75E',
                4 => 'JjYVVQQYcPo',
                5 => 'XdwAvGpjDG4',
                6 => 'cfFvjnR3szU',
                7 => 'cggEZdzRju0',
                8 => 'R6TmEajPUrU',
                9 => 'geb-TWyfavc',
            ];
            $str_count = rand(0, count($str));
            return 'll="108288";bid=' . $str[$str_count] . '';
        } else {
            $client = new Client();
            $response = $client->get($url);
            // 获取响应头部信息
            $headers = $response->getHeaders();
            $cookie = "";
            foreach ($headers['Set-Cookie'] as $k) {
                if (strpos(explode(';', $k)[0], 'll') !== false) {
                    $cookie .= explode(';', $k)[0] . ';';
                }
                if (strpos(explode(';', $k)[0], 'bid') !== false) {
                    $cookie .= explode(';', $k)[0] . '';
                }
            }
        }
        return $cookie;
    }

    protected function newCookie($cookies)
    {
        $cookie = 'll="108288";bid=h4nqLajQEBo; douban-fav-remind=1; __gads=ID=f547fc5d1024460e:T=1584933974:S=ALNI_MYnz5KEHQFfcZy0gMy6CM04qFHEGg;  _vwo_uuid_v2=DE8FD61CD60225FE96D81709B68421C2D|866f6dabae9a822d17e89ca947c01f78; __yadk_uid=HPbvxvJ9JN8yUqI6foqDYbhNLOHg2OMc; __utmc=30149280; push_noty_num=0; push_doumail_num=0; __utmv=30149280.21552; douban-profile-remind=1; __utmz=30149280.1587373187.4.3.utmcsr=baidu|utmccn=(organic)|utmcmd=organic; dbcl2="215524010:bdDl9E8vVTg"; ck=m31b; ap_v=0,6.0; ct=y; _pk_ref.100001.2939=%5B%22%22%2C%22%22%2C1587439340%2C%22https%3A%2F%2Fmovie.douban.com%2F%22%5D; _pk_ses.100001.2939=*; __utma=30149280.1772134204.1587359482.1587432721.1587439341.7; __utmt=1; _pk_id.100001.2939=1deb2b5e8988f44c.1587174800.9.1587439359.1587434637.; __utmb=30149280.9.9.1587439359009';
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

    public function update_url_proxy($error_count, $url)
    {
        $error_count++;
        if ($error_count > 10) {
            $tmp = $this->testing($url, $this->get_port);
            if ($tmp != 200 && $this->times + (50 * 3)) {
                $this->get_port = $this->getPort(); //重新构成代理端口
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

    protected function get_query_url()
    {
        $time_stamp = $this->get_timestamp();
        $md5_str = $this->get_md5_str($this->proxy_username . $this->proxy_passwd . strval($time_stamp));
        return 'http://' . $this->proxy_server . ':'
            . $this->proxy_port . '/query?' . $this->key_name . $this->proxy_username .
            '&' . $this->key_timestamp . strval($time_stamp) .
            '&' . $this->key_md5 . $md5_str .
            '&' . $this->key_pattern . $this->pattern;
    }

    //返回请求分配代理端口URL链接
    public function add_whitelist($ip = 0)
    {
        $time_stamp = $this->get_timestamp();
        $md5_str = $this->get_md5_str($this->proxy_username . $this->proxy_passwd . strval($time_stamp));
        return 'http://' . $this->proxy_server . ':'
            . $this->proxy_port . '/add_whitelist?' . $this->key_name . $this->proxy_username .
            '&' . $this->key_timestamp . strval($time_stamp) .
            '&' . $this->key_md5 . $md5_str .
            '&' . $this->key_pattern . $this->pattern .
            '&' . 'user_ip=' . $ip;
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

    public function getPort($a = 0, $port_list = false)
    {
        $file = 'log.txt';
        if (!empty($times)) {
            $this->times = Cache::get('vod_times_cj_open_url');
        }

        if ($a >= 3) {
            $open_data = mac_curl_get($this->get_open_url());
            file_put_contents($file, date('Y-m-d H:i:s', time()) . '-|open_url||' . $open_data . PHP_EOL, FILE_APPEND);
            $code = $open_data['code'] ?? '';
            $left_ip = $open_data['left_ip'] ?? '';
            if (!empty($open_data) && $code == 100 && $left_ip > 1) {
                if (!empty($open_data['port'])) {
                    if ($port_list == true) {
                        return $open_data['port'];
                    } else {
                        return $open_data['port'][0];
                    }
                }
            } else {
//                exit('终止');
            }
        }
        $queryData = $this->get_query_url();
        try {
            $result = mac_curl_get($queryData);
            $result = iconv("gb2312", "utf-8//IGNORE", $result);
            $queryData = json_decode($result, true);
            echo $result . "\n <br>";

            $code = $queryData['code'];
            if ($code == 108) {
                $reset_url = $this->get_reset_url();
                $r = file_get_contents($reset_url);
            } else if ($code == 100 && $queryData['left_ip'] > 1) {
                if (!empty($queryData['port'])) {
                    $handler = fopen($file, "r");
                    $logs_data = [];
                    while (!feof($handler)) {
                        $m = fgets($handler, 4096); //fgets逐行读取，4096最大长度，默认为1024
                        if (substr_count($m, $queryData['port'][0]) > 0) //查找字符串
                        {
                            $logs_data[] = explode('-|', $m)[0] ?? '';
                        }
                    }
                    $this->times = strtotime(array_pop($logs_data));
                    Cache::set('vod_times_cj_open_url', $this->times);
                    if ($port_list == true) {
                        return $queryData['port'];
                    } else {
                        return $queryData['port'][0];
                    }
                } else {
                    $a++;
                    $this->getPort($a, $port_list);
                }
                if ($port_list == true) {
                    return $queryData['port'];
                } else {
                    return strval($queryData['port'][0]);
                }
            }
        } catch (\Exception $e) {
            file_put_contents($file, 'open_url||' . $e . PHP_EOL, FILE_APPEND);
        }
        return false;
    }


    //获取cms data
    public function getCmsData($url)
    {
        $get_url_search_id_data = mac_curl_get($url);
        return $this->isJsonBool($get_url_search_id_data, true);
    }

    //查看 是否是 json
    protected function isJsonBool($data = '', $assoc = false)
    {
        $data = json_decode($data, $assoc);
        if (($data && is_object($data)) || (is_array($data) && !empty($data))) {
            return $data;
        }
        return false;
    }

    public function strToUtf8($str)
    {
        $encode = mb_detect_encoding($str, array("ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5'));
        if ($encode == 'UTF-8') {
            return $str;
        } else {
            return mb_convert_encoding($str, 'UTF-8', $encode);
        }
    }

    //获取豆瓣采集
    protected function getDouBanApiData($get_url_search_id_data)
    {
        $vod_data = [];
        if (isset($get_url_search_id_data['aka'])) {
            array_push($get_url_search_id_data['aka'], $get_url_search_id_data['original_title']);
            $get_url_search_id_data['aka'] = array_unique($get_url_search_id_data['aka']);
            $vod_data['vod_sub'] = implode(',', $get_url_search_id_data['aka']);
        }
        if (isset($get_url_search_id_data['id'])) {
            $vod_data['vod_douban_id'] = $get_url_search_id_data['id'];
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
        if(!isset($get_url_search_id_data['pubdate']) && empty($vod_data['vod_pubdate'])){
            if (isset($get_url_search_id_data['pubdates']) && !empty($get_url_search_id_data['pubdates'])) {
                $vod_data['vod_pubdate'] = $get_url_search_id_data['pubdates'];
                if (strpos($vod_data['vod_pubdate'], '(') !== false) {
                    $vod_data['vod_pubdate'] = explode('(', $vod_data['vod_pubdate'])[0] ?? $vod_data['vod_pubdate'];
                }
            }
        }
        if (isset($get_url_search_id_data['rating']['average'])) {
            $vod_data['vod_douban_score'] = $vod_data['vod_score_all'] = $get_url_search_id_data['rating']['average'];
        }
        if (isset($get_url_search_id_data['ratings_count'])) {
            $vod_data['vod_score_num'] = $get_url_search_id_data['ratings_count'];
        }
        if (isset($get_url_search_id_data['year'])) {
            $vod_data['vod_year'] = $get_url_search_id_data['year'];
        }
        if (isset($get_url_search_id_data['durations'][0])) {
            $vod_data['vod_duration'] = $get_url_search_id_data['durations'][0];
            if (strpos($vod_data['vod_duration'], '(') !== false) {
                $vod_data['vod_duration'] = explode('(', $vod_data['vod_duration'])[0] ?? $vod_data['vod_duration'];
            }
        }
        if (isset($get_url_search_id_data['genres'])) {
            $vod_data['vod_tag'] = $vod_data['vod_class'] = implode(',', $get_url_search_id_data['genres']);
        }
        if (isset($get_url_search_id_data['title'])) {
            $vod_data['vod_name'] = mac_trim_all(mac_characters_format($get_url_search_id_data['title']));
        }
        if (isset($get_url_search_id_data['share_url'])) {
            $vod_data['vod_reurl'] = $get_url_search_id_data['share_url'];
        }
        $vod_data['vod_author'] = '豆瓣';
        return $vod_data;
    }
    //获取飞飞采集内容
    protected function getFFApiData($res)
    {
        $vod_data = [];
        //总集数
        if (isset($res['vod_total'])) {
            $vod_data['vod_total'] = $res['vod_total'];
        }
        //连载数
        if (isset($res['vod_continu']) && !empty($res['vod_continu'])) {
            $vod_data['vod_serial'] = mac_vod_remarks($res['vod_continu'], $res['vod_total']);
        }
        $vod_data['vod_name'] = mac_trim_all(mac_characters_format( $res['vod_name']));
//        $vod_data['vod_pic'] = $res['vod_pic']??'';
        //对白语言
        if (isset($res['vod_language']) && !empty($res['vod_language'])) {
            $vod_data['vod_lang'] = $res['vod_language'];
        }
        //资源类别
        if (isset($res['vod_state']) && !empty($res['vod_state'])) {
            $vod_data['vod_state'] = mac_filter_trim($res['vod_state']);
        }
        //视频标签
        if (isset($res['vod_type']) && !empty($res['vod_type'])) {
            $vod_data['vod_tag'] = mac_format_text(trim($res['vod_type']));
            $vod_data['vod_tag'] = str_replace('/', ',', $vod_data['vod_tag']);
        }
        //发行地区
        if (isset($res['vod_area']) && !empty($res['vod_area'])) {
            $vod_data['vod_area'] = mac_filter_trim(trim($res['vod_area']));
        }
        //主演列表
        if (isset($res['vod_actor']) && !empty($res['vod_actor'])) {
            $vod_data['vod_actor'] = $res['vod_actor'];
            $vod_data['vod_actor'] = str_replace('更多...', '', $vod_data['vod_actor']);
            $vod_data['vod_actor'] = mac_filter_trim(mac_substring(str_replace('/', ',', $vod_data['vod_actor']), 255));
        }
        //导演
        if (isset($res['vod_director']) && !empty($res['vod_director'])) {
            $vod_data['vod_director'] = trim($res['vod_director']);
            $vod_data['vod_director'] = mac_filter_trim(mac_substring(str_replace('/', ',', $vod_data['vod_director']), 255));
        }
        //上映日期
        if (isset($res['vod_filmtime']) && !empty($res['vod_filmtime'])) {
            $vod_pubdate = mac_format_text(trim($res['vod_filmtime']));
            if (strpos($vod_pubdate, '(')) {
                $vod_pubdate = explode('(', $vod_pubdate)[0] ?? '';
            }
            $vod_data['vod_pubdate'] = $vod_pubdate;
        }
        //编剧
        if (isset($res['vod_writer']) && !empty($res['vod_writer'])) {
            $vod_data['vod_writer'] = mac_format_text($res['vod_writer']);
            $vod_data['vod_writer'] = mac_filter_trim(str_replace('/', ',', $vod_data['vod_writer']));
        }
        //平均分
        if (isset($res['vod_gold']) && !empty($res['vod_gold'])) {
            $vod_data['vod_score'] = trim($res['vod_gold']);
        }
        //评分次数
        if (isset($res['vod_score_num']) && !empty($res['vod_score_num'])) {
            $vod_data['vod_score_num'] = $res['vod_score_num'];
        }
        //总评分
        if (isset($res['vod_score_all']) && !empty($res['vod_score_all'])) {
            $vod_data['vod_score_all'] = $res['vod_score_all'];
        }
        //时长
        if (isset($res['vod_length']) && !empty($res['vod_length'])) {
            $vod_data['vod_duration'] = trim($res['vod_length']);
        }
//        //豆瓣id
//        if (isset($res['vod_douban_id']) && !empty($res['vod_douban_id'])) {
//            $vod_data['vod_douban_id'] = $res['vod_douban_id'];
//        }
        //豆瓣评分
        if (isset($res['vod_douban_score']) && !empty($res['vod_douban_score'])) {
            $vod_data['vod_douban_score'] = $res['vod_douban_score'];
        }
        //扩展分类
        if (isset($res['vod_type']) && !empty($res['vod_type'])) {
            $vod_data['vod_class'] = mac_format_text(trim($res['vod_type']));
            $vod_data['vod_class'] = str_replace('/', ',', $vod_data['vod_class']);
        }
        //来源地址
        if (isset($res['vod_reurl']) && !empty($res['vod_reurl'])) {
            $vod_data['vod_reurl'] = trim($res['vod_reurl']);
        }
        //编辑人
        if (isset($res['vod_inputer']) && !empty($res['vod_inputer'])) {
            if ($res['vod_inputer'] == 'douban') {
                $vod_data['vod_author'] = '豆瓣';
            } else {
                $vod_data['vod_author'] = $res['vod_inputer'];
            }
        }
        if (isset($res['vod_year']) && !empty($res['vod_year'])) {
            $vod_data['vod_year'] = trim($res['vod_year']);
        }

        //副本名称
        if (isset($res['vod_title']) && !empty($res['vod_title'])) {
            $vod_data['vod_sub'] = $res['vod_title'];
            $vod_data['vod_sub'] = mac_characters_format( $vod_data['vod_sub']);
        }
        return $vod_data;
    }

    //获取mac管理
    protected function getMacApiData($res)
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
         $vod_data['vod_name'] =  mac_trim_all(mac_characters_format( $res['vod_name']));
        //副本名称
        if (isset($res['vod_sub']) && !empty($res['vod_sub'])) {
            $vod_data['vod_sub'] = mac_filter_trim(str_replace('/',',',mac_characters_format(  $res['vod_sub'])));
        }
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
        if (isset($res['vod_year'])) {
            $vod_data['vod_year'] = $res['vod_year'];
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

    //获取公共的详情数据添加
    protected function getDetailPublic($vod_data){
        $upDetails =[];
        $upDetails['text'] = json_encode($vod_data,true);
        $upDetails['name'] = $upDetails['title'] = $vod_data['vod_name']??'';
        $upDetails['link'] = $vod_data['vod_reurl']??'';
        $upDetails['abstract'] = '';
        $upDetails['abstract_2'] = '';
        $upDetails['score'] =  $upDetails['rating_nums'] = $vod_data['vod_douban_score']??0;
        $upDetails['time'] = date("Y-m-d H:i:s",time());
        $upDetails['name_as'] =$vod_data['vod_sub']??'';
        $upDetails['vod_director'] =$vod_data['vod_director']??'';
        $upDetails['vod_actor'] =$vod_data['vod_actor']??'';
        return $upDetails;
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



    public function queryListUrl($ql,$url,$cookie = '',$proxy = false,$json_code = true){
        $header = [
            //设置超时时间，单位：秒
            'timeout' => 30,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'User-Agent' => mac_ua_all(rand(0, 17)),
                'Cookie' => $cookie
            ]
        ];
        if(empty($header)){
            unset($header['headers']['Cookie']);
        }
        if($proxy == true){
            // 设置代理
            $header['proxy'] = 'http://' . $this->proxy_server . ":" . $this->get_port;
        }
        try {
            $get_url_search_id_data = $ql->get($url, null,$header )->getHtml();
            if($json_code == true){
                return json_decode($get_url_search_id_data, true);
            }else{
                return $get_url_search_id_data;
            }
        } catch (Exception $e) {
            log::info('err-'.$e);
        }
        return false;

    }

}