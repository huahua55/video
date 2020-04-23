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

class CmsDouban extends Command
{
    protected $vodDb;//db
    protected $get_search_id = 'http://api.maccms.com/douban/?callback=douban&id=';//cms 通过id获取内容
    protected $ql;//querylist

    protected function configure()
    {
        //db
        $this->vodDb = Db::name('douban_vod_details');
        $this->ql = QueryList::getInstance();
        //获取豆瓣id
        $this->setName('cmsDouban')->addArgument('parameter')
            ->setDescription('定时计划：采集CmsDouban');
    }

    protected function execute(Input $input, Output $output)
    {

        // 输出到日志文件
        $output->writeln("开启采集:采集CmsDouban评分");
        try {
            //字符串对比算法
            $lcs = new similarText();
            //cli模式接受参数
            $myparme = $input->getArguments();
            $parameter = $myparme['parameter'];
            //参数转义解析
            $param = $this->ParSing($parameter);
            $type = $param['type']??'';
            $ids = $param['id']??'';

            $start = 0;
            $page = 1;
            $limit = 20;
            $is_true = true;
            $where = [];
            $where['name'] = ['eq', ''];
            $where['douban_id'] = ['gt', 0];
            if(!empty($ids)){
                $where['id'] = ['gt', $ids];
            }
//            $where['name_as'] = ['eq', ''];
            if($type ==1){
                $order = 'id desc';
            }else{
                $order = 'id asc';
            }

            //进入循环 取出数据
            while ($is_true) {
                //取出数据
                $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
                if (!empty($douBanScoreData)) {
                    log::info('采集CmsDouban进入foreach');
                    $pagecount = $douBanScoreData['pagecount'] ?? 0;
                    if ($page > $pagecount) {
                        $is_true = false;
                        log::info('采集CmsDouban结束...');
                        $output->writeln("结束...");
                        break;
                    }
                    foreach ($douBanScoreData['list'] as $k => $v) {
                        $is_log = false;
                        $is_error = false;
                        $mac_curl_get_data = '';
                        sleep(1);
                        $url = $this->get_search_id . $v['douban_id'];
                        log::info('采集CmsDoubanUrl:', $url);
                        try {
                            $mac_curl_get_data = mac_curl_get($url);
                            $mac_curl_get_data = str_replace('douban(', '', $mac_curl_get_data);
                            $mac_curl_get_data = str_replace(');', '', $mac_curl_get_data);
                            $mac_curl_get_data = $this->isJsonBool($mac_curl_get_data, true);
                            Log::info('采集CmsDouban-try:');
                        } catch (Exception $e) {
                            Log::info('err--过滤' . $url);
                            continue;
                        }
//                     print_r($getSearchData);
                        if (!empty($mac_curl_get_data)) {
                            log::info('采集CmsDouban-try_su:',$mac_curl_get_data);
                            if (!empty($mac_curl_get_data) && $mac_curl_get_data['code'] == 1 && !empty($mac_curl_get_data['data'])) {
                                log::info('采集CmsDouban-try_-su-::');
                                $res = $mac_curl_get_data['data'];
                                $is_log = true;
                                $is_error = true;
                                $whereId['id'] = $v['id'];
                                $vod_data['name'] = $res['vod_name'];
                                $vod_data['name_as'] = $res['vod_sub'];
                                $vod_data['vod_director'] = $res['vod_director'];
                                $vod_data['vod_actor'] = $res['vod_actor'];
                                $vod_data['score'] = $res['vod_score'];
                                $vod_data['text'] = json_encode($res,true);
                                $up_res = $this->vodDb->where($whereId)->update($vod_data);
                                if ($up_res) {
                                    log::info('CmsDouban-try-succ::' . $v['name']);
                                }
                            }
                        }
                        if ($is_log == false) {
                            log::info('采集CmsDoubanUrl-过滤::' . $v['title']);
                        }
                        if ($is_error == false) {
                            $whereErrId['id'] = $v['id'];
                            $vod_err_data['error_count'] =$v['error_count'] + 1;
                            $this->vodDb->where($whereErrId)->update($vod_err_data);
                        }

                    }
                    $page = $page + 1;
                }
            }
        } catch (Exception $e) {
            $output->writeln("end1111....");
            log::info('采集CmsDoubanUrl-error::');
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

    // 取出数据豆瓣评分为空数据
    protected function getVodDoubanScoreData($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;

        $total = $this->vodDb->where($where)->count();
        $list = $this->vodDb->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
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
            $parameter_array[$zzz[0]] = $zzz[1];

        }
        return $parameter_array;

    }


}