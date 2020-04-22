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

class DoubanScore extends Command
{
    protected $vodDb;//db
    protected $search_url_re = 'https://search.douban.com/movie/subject_search?search_text=%s&cat=1002';//豆瓣搜索接口
    protected $search_url = 'https://movie.douban.com/j/subject_suggest?q=%s';//豆瓣搜索接口
    protected $get_search_id = 'http://api.maccms.com/douban/?callback=douban&id=';//cms 通过id获取内容
   protected $ql;//querylist


    protected $proxy_username = 'zhangshanap1';
    protected $proxy_passwd = '76836051';
    protected $proxy_server = '183.129.244.16';
    protected $proxy_port = '88';
    protected $pattern = 'json';//API访问返回信息格式：json和text可选
    protected $num = 10;//获取代理端口数量


    protected $key_name = 'user_name=';
    protected $key_timestamp = 'timestamp=';
    protected $key_md5 = 'md5=';
    protected $key_pattern = 'pattern=';
    protected $key_num = 'number=';
    protected $key_port = 'port=';

    protected function configure()
    {
        //db
        $this->vodDb = Db::name('vod');
        $this->ql = QueryList::getInstance();
//        $ph_js_path = ROOT_PATH.'extend/phantomjs_macosx/bin/phantomjs';
        $ph_js_path = ROOT_PATH.'extend/phantomjs_linux/bin/phantomjs';
        $this->ql->use(PhantomJs::class,$ph_js_path);
        $this->ql->use(PhantomJs::class,$ph_js_path,'browser');
//        print_r($ph_js_path);die;
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
    protected function getCookie($url){
        $client = new Client();
        $response = $client->get($url);
        // 获取响应头部信息
        $headers = $response->getHeaders();
        $cookie = "";
        foreach ($headers['Set-Cookie'] as $k) {
            if(strpos(explode(';', $k)[0],'ll') !==false ){
                $cookie .= explode(';', $k)[0].';';
            }
            if(strpos(explode(';', $k)[0],'bid') !==false ){
                $cookie .= explode(';', $k)[0].'';
            }
        }
        return $cookie;
    }
    protected function newCookie($cookies){
        $cookie = 'll="108288";bid=h4nqLajQEBo; douban-fav-remind=1; __gads=ID=f547fc5d1024460e:T=1584933974:S=ALNI_MYnz5KEHQFfcZy0gMy6CM04qFHEGg;  _vwo_uuid_v2=DE8FD61CD60225FE96D81709B68421C2D|866f6dabae9a822d17e89ca947c01f78; __yadk_uid=HPbvxvJ9JN8yUqI6foqDYbhNLOHg2OMc; __utmc=30149280; push_noty_num=0; push_doumail_num=0; __utmv=30149280.21552; douban-profile-remind=1; __utmz=30149280.1587373187.4.3.utmcsr=baidu|utmccn=(organic)|utmcmd=organic; dbcl2="215524010:bdDl9E8vVTg"; ck=m31b; ap_v=0,6.0; ct=y; _pk_ref.100001.2939=%5B%22%22%2C%22%22%2C1587439340%2C%22https%3A%2F%2Fmovie.douban.com%2F%22%5D; _pk_ses.100001.2939=*; __utma=30149280.1772134204.1587359482.1587432721.1587439341.7; __utmt=1; _pk_id.100001.2939=1deb2b5e8988f44c.1587174800.9.1587439359.1587434637.; __utmb=30149280.9.9.1587439359009';
        $cookieArray =  explode(';',$cookie);

        $cookieArray[16]='_pk_ref.100001.2939'.urlencode('["","",time(),"https://movie.douban.com/"]');
        $cookieArray[21]='30149280.9.9.'.time().rand(0,9).rand(1,6).rand(0,6);
        $cookieArray[3]=str_replace('T=1584933974','T='.time(), $cookieArray[3]);
        $cookieArray[11]=str_replace('1587373187',time(), $cookieArray[11]);
        $cookieArray[18]=str_replace('1587439341',time(), $cookieArray[18]);
        $cookieArray[20]=str_replace('1587439359',time()+11, $cookieArray[20]);
        $cookieArray[20]=str_replace('1587174800',time()+600, $cookieArray[20]);
        $cookieArray[0]=$cookies;
        unset($cookieArray[1]);
        return implode($cookieArray,';');
    }
    protected function execute(Input $input, Output $output)
    {

        $lcs = new similarText();
        $myparme = $input->getArguments();
        $parameter = $myparme['parameter'];
        if($parameter == 1){
            Cache::set('vod_id_list_douban_score', 1);
        }
//
        //实例简单演示如何正确获取代理端口，使用代理服务测试访问https://ip.cn，验证后释放代理端口
//        $file = 'log.txt';
//        $port = '';//代理端口变量
//
//        $codeArray = [];
//        try {
//            $open_url = $this->get_open_url();
//            $r = file_get_contents($open_url);
//            $result = iconv("gb2312", "utf-8//IGNORE", $r);
//            $codeArray = json_decode($result,true);
//            echo $result . "\n <br>";
//            file_put_contents($file, date('Y-m-d H:i:s', time()) . PHP_EOL . 'open_url||' . $result . PHP_EOL, FILE_APPEND);
//            $json_arr = json_decode($result, true);
//            $code = $json_arr['code'];
//            if ($code == 108) {
//                $reset_url = $this->get_reset_url();
//                $r = file_get_contents($reset_url);
//            } else if ($code == 100) {
//                $port = strval($json_arr['port'][0]);
//            }
//
//        } catch (\Exception $e) {
//            file_put_contents($file, 'open_url||' . $e . PHP_EOL, FILE_APPEND);
//        }
//
//        $ran =  rand(1,$codeArray['number']);
//        $portAr = $codeArray['port'][$ran];



        // 输出到日志文件
        $output->writeln("开启采集:采集豆瓣评分");
        //开启代理
//        $A = $this->getDouBan();
//        p($A);
        //开始cookie
        $cookies =  $this->getCookie('https://movie.douban.com/');
        $cookie = $this->newCookie($cookies);

        $start = 0;
        $page = 1;
        $limit = 20;
        $is_true = true;
        $where = [
            'vod_douban_id' => 0,
        ];
        $is_vod_id = Cache::get('vod_id_list_douban_score');
        if (!empty($is_vod_id)) {
            $where['vod_id'] = ['gt', $is_vod_id];
        }

//        $startTime =  date("Y-m-d 00:00:00",time());
//        $endTime =  date("Y-m-d 23:59:59",time());
//        $where['vod_time'] =['between',[strtotime($startTime),strtotime($endTime)]];
        $order = 'vod_id asc';

        //进入循环 取出数据
        while ($is_true) {
            //取出数据
            $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
//            print_r( $this->vodDb->getlastsql());die;
//            print_r($douBanScoreData);die;
            $pagecount = $douBanScoreData['pagecount'] ?? 0;

            if ($page > $pagecount) {
                $is_true = false;
                log::info('采集豆瓣评分结束...');
                $output->writeln("结束....");
                break;
            }

            foreach ($douBanScoreData['list'] as $k => $v) {
                $is_log = false;
                $mac_curl_get_data = '';
               $sleep =  rand(3,10);
                sleep($sleep);
                $url = sprintf($this->search_url_re, urlencode('平'));
                try {
                    $mac_curl_get_data = $this->ql->browser(function (\JonnyW\PhantomJs\Http\RequestInterface $r) use($url,$cookie){
                        $r->setMethod('GET');
                        $r->addHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8');
                        // $r->addHeader('Referer', 'http://cq.meituan.com/s/%E5%90%83%E9%A5%AD/');
                        $r->addHeader('User-Agent', 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 YaBrowser/18.4.0.2080 Yowser/2.5 Safari/537.36');
                        $r->addHeader('Cookie', $cookie);
                        $r->setUrl($url);
                        $r->setTimeout(10000); // 10 seconds
                        $r->setDelay(3); // 3 seconds
                        return $r;
                    },false,[
                        '--proxy' => "183.129.244.16:15485",
//                        '--proxy' => $this->proxy_server.":55096",
                        '--proxy-type' => 'http',
//                        '--ssl-protocol' =>'any',
                        '--load-images'=>'no',
                        '--ignore-ssl-errors' =>true,
                    ])->rules([
                        'rating_nums' => ['.rating_nums','text'],
                        'title' => ['a','text'],
                        'link' => ['a','href'],
                        'abstract' => ['.abstract','text'],
                        'abstract_2' => ['.abstract_2','text'],
                    ])->range('.item-root')->query()->getData();
                } catch (Exception $e) {
                    Log::info('err--过滤' . $url);
                    continue;
                }
                p($mac_curl_get_data);die;

                $getSearchData = objectToArray($mac_curl_get_data);
//                print_r($getSearchData);
                log::info('采集豆瓣评分-url-::' . $url);
//                log::info('采集豆瓣评分-url-data::' . $getSearchData);
                if (!empty($getSearchData)){
                    foreach ($getSearchData as $da_k=>$as_k){
                        log::info('采集豆瓣评分-title1-::' . mac_trim_all($v['vod_name']));
                        log::info('采集豆瓣评分-title2-::' . $as_k['title']);
//                        if(mac_trim_all($v['vod_name']) == mac_trim_all($as_k['title'])){
                          $rade = $lcs->getSimilar(mac_trim_all($v['vod_name']),mac_trim_all($as_k['title'])) *100 ;
                         log::info('采集豆瓣评分-比例::' . $rade);
                          if($rade> 50){
                            log::info('采集豆瓣评分-title-su-::' . $as_k['title']);
                            $link =  explode('subject',$as_k['link']);
                            $get_search_id = $link[1] ?? '';
                            $get_search_id = str_replace('/','',$get_search_id);
                            if(!empty($get_search_id)){
                                log::info('采集豆瓣评分-ok-id::' . $get_search_id);
                                $get_url_search_id = $this->get_search_id . $get_search_id;
                                $get_url_search_id_data = mac_curl_get($get_url_search_id);
                                $get_url_search_id_data = str_replace('douban(', '', $get_url_search_id_data);
                                $get_url_search_id_data = str_replace(');', '', $get_url_search_id_data);
                                $get_url_search_id_data = $this->isJsonBool($get_url_search_id_data, true);
                                if (!empty($get_url_search_id_data) && $get_url_search_id_data['code'] == 1 && !empty($get_url_search_id_data['data'])) {
                                    $res = $get_url_search_id_data['data'];
                                    if(($res['vod_name'] == $v['vod_name'] || $res['vod_name'] == $v['vod_sub']) &&  ($v['vod_director'] == $res['vod_director'])  ){
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
                                                log::info('采集豆瓣评分-succ::' . $v['vod_name']);
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
        $output->writeln("end....");
    }
    function strToUtf8($str){
        $encode = mb_detect_encoding($str, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
        if($encode == 'UTF-8'){
            return $str;
        }else{
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
            $vod_data['vod_tag'] = trim(mb_substr($res['vod_tag'], 0, 100));
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

    public function headers(){
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

    public function getDouBan()
    {


        //实例简单演示如何正确获取代理端口，使用代理服务测试访问https://ip.cn，验证后释放代理端口
        $file = 'log.txt';
        $port = '';//代理端口变量


        $test_url = 'https://movie.douban.com/j/subject_suggest?q=清平乐'; //测试访问链接
        try {
            $open_url = $this->get_open_url();
            var_dump($open_url);
//            p($open_url);
            $r = file_get_contents($open_url);
            $result = iconv("gb2312", "utf-8//IGNORE", $r);
            $code = json_decode($result,true);
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

//        var_dump($test_url);
//        $tmp = $this->testing($test_url, $port);
        print_r(1);die;
//        p($tmp);
//        p($port);
//        echo 'test_proxy|| httpCode:' . $tmp . "\n <br>";
        file_put_contents($file, 'test_proxy|| httpCode:' . 200 . PHP_EOL, FILE_APPEND);
        try {
            $close_url = $this->get_close_url($port);
            $r = file_get_contents($close_url);
            $result = iconv("gb2312", "utf-8//IGNORE", $r);
            echo 'close_url||' . $result;
            file_put_contents($file, 'close_url||' . $result . PHP_EOL, FILE_APPEND);
        } catch (\Exception $e) {
            file_put_contents($file, 'close_url||' . $e . PHP_EOL, FILE_APPEND);
        }

    }


}