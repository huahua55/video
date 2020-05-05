<?php

namespace app\crontab\command;

use JonnyW\PhantomJs\Http\PdfRequest;
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

class DoubanScoreJs extends Common
{
    protected $vodDb;//db
    protected $search_url_re = 'https://search.douban.com/movie/subject_search?search_text=%s&cat=1002';//豆瓣搜索接口
    protected $search_url = 'https://movie.douban.com/j/subject_suggest?q=%s';//豆瓣搜索接口
    protected $ql;//querylist
    protected $cmsDb;//db
    protected $vod_errorDb;//db

    protected function configure()
    {
        //db
        $this->vodDb = Db::name('vod');
        $this->vod_errorDb = Db::name('vod_douban_error');
        $this->cmsDb = Db::name('douban_vod_details');
        $this->ql = QueryList::getInstance();
        //获取豆瓣id
        $this->setName('doubanScoreJs')->addArgument('parameter')
            ->setDescription('定时计划：采集豆瓣评分');
    }

    // 取出数据豆瓣评分为空数据
    protected function getVodDoubanScoreData($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodDb->alias('a')->join('vod_douban_error b', 'a.vod_id=b.vod_id', 'LEFT')->where($where)->count();
        $list = $this->vodDb->field('a.vod_id,a.vod_sub,a.vod_name,a.vod_class,a.vod_actor,a.vod_director,a.vod_douban_id,a.vod_douban_score')->alias('a')->join('vod_douban_error b', 'a.vod_id=b.vod_id', 'LEFT')->where($where)->order($order)->limit($limit_str)->select();
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
            $q = $param['q'] ?? '';
            if (!empty($type) && $type == 1) {
                Cache::set('vod_id_list_douban_id', 1);
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
            $this->get_port = $this->getPort();
            if ($this->get_port == false) {
                $this->get_port = $this->getPort();
                log::info('get_port-::');
            }
//        p($A);
            //开始cookie
            $cookies = $this->getCookie('https://movie.douban.com/');
            $start = 0;
            $page = 1;
            $limit = 20;
            $is_true = true;
            $where = ['a.vod_douban_id' => 0];
//            $where['b.count'] =['eq',0];
            $is_vod_id = Cache::get('vod_id_list_douban_id');
            if (!empty($id)) {
                $where['a.vod_id'] = ['gt', $id];
            } else {
                if (!empty($is_vod_id)) {
                    $where['a.vod_id'] = ['gt', $is_vod_id];
                } else {
                    Cache::set('vod_id_list_douban_id', 1);
                    $where['a.vod_id'] = ['gt', $is_vod_id];
                }
            }
            if (!empty($q)) {
                $q_vod_id = $this->vod_errorDb->field('vod_id')->order('id desc')->find();
                if (isset($q_vod_id) && !empty($q_vod_id_data['vod_id'])) {
                    $where['a.vod_id'] = ['gt', $q_vod_id_data['vod_id']];
                }

            }
            $where['b.count'] = ['EXP', Db::raw('IS NULL')];
//        $startTime =  date("Y-m-d 00:00:00",time());
//        $endTime =  date("Y-m-d 23:59:59",time());
//        $where['vod_time'] =['between',[strtotime($startTime),strtotime($endTime)]];
            log::info('js-采集豆瓣评分where...' . json_encode($where, true));
            $order = 'a.vod_id asc';
            $cookie = $this->newCookie($cookies);
            while ($is_true) {//进入循环 取出数据
                //取出数据
                $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
                $pagecount = $douBanScoreData['pagecount'] ?? 0;
                if ($page > $pagecount) {
                    $is_true = false;
                    log::info('js-采集豆瓣评分结束...');
                    $output->writeln("结束....");
                    break;
                }
                foreach ($douBanScoreData['list'] as $k => $v) {
                    $del_sql = 'DELETE FROM vod_douban_error where douban_id = 0';
                     Db::execute($del_sql);
                    $c = 0;
                    $get_search_id = 0;
                    $e_err = false;
                    $error_count = 1;
                    $is_log = false;
                    $mac_curl_get_data = '';
//                    sleep(1);
                    $this->times = Cache::get('vod_times_cj_open_url');
                    if (time() > ($this->times + 180) || empty($this->get_port)) {
                        $this->get_port = $this->getPort($c);
                        $c++;
                    }
                    $url = sprintf($this->search_url_re, urlencode($v['vod_name']));
                    $startTime = microtime(TRUE);
                    try {
                        libxml_use_internal_errors(true);
                        $mac_curl_get_data = $this->ql->browser(function (\JonnyW\PhantomJs\Http\RequestInterface $r) use ($url, $cookie) {
                            $r->setMethod('GET');
                            $r->addHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9');
//                          $r->addHeader('Referer', $url);
                            $r->addHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36');
                            $r->addHeader('Cookie', $cookie);
                            $r->setUrl($url);
//                            $r->setTimeout(10000); // 10 seconds
//                            $r->setDelay(2); // 3 seconds
                            return $r;
                        }, false, [
//                        '--proxy' => "183.129.244.16:51134",
                            '--proxy' => $this->proxy_server . ":" . $this->get_port,
                            '--proxy-type' => 'http',
                            '--load-images' => 'no',
//                    ])->getHtml();
                        ])->rules([
                            'rating_nums' => ['.rating_nums', 'text'],
                            'title' => ['a', 'text'],
                            'link' => ['a', 'href'],
                            'abstract' => ['.abstract', 'text'],
                            'abstract_2' => ['.abstract_2', 'text'],
                        ])->range('.item-root')->query()->getData();
                        log::info('js-err--proxy-' . $this->proxy_server . ":" . $this->get_port);

                        $getSearchData = objectToArray($mac_curl_get_data);
                        unset($mac_curl_get_data);
//                        log::info('js-data-' .json_encode($getSearchData,true));
                    } catch (Exception $e) {
                        log::info('js-err--过滤' . $url);
                        continue;
                    }
                    $b_time = microtime(TRUE) - $startTime;
                    log::info('js-xn-' . $b_time);
                    unset($b_time);
                    unset($startTime);
                    if (empty($getSearchData)) {
                        log::info('js-采集豆瓣评分-url-err::');//更新 代理
                        $this->get_port = $this->getPort();
                    }

                    log::info('js-采集豆瓣评分-url-::' . $url);
                    if (!empty($getSearchData)) {
                        foreach ($getSearchData as $da_k => $as_k) {
                            if ($da_k == 0 || $da_k == 1 || $da_k == 2) {
                                log::info('js-采集豆瓣评分-title1-::' . mac_trim_all($v['vod_name']));
                                log::info('js-采集豆瓣评分-title2-::' . $as_k['title']);
                                $get_search_id = $this->vod_details_install($as_k);    //添加 详情表数据
                                if ($g == 1) {  //不爬取详情页面数据
                                    log::info('js-采集豆瓣评分-title-su-::g' . $as_k['title'] . '---' . $v['vod_id']);
                                } else {
                                    $__startT = microtime(TRUE);
                                    $e_err = $this->vod_douBan_details($lcs, $v, $as_k, $cookie, $get_search_id, $e_err); //采集详情页面数据
                                    $c_time = microtime(TRUE) - $__startT;
                                    log::info('js--xnd-' . $c_time);
                                    unset($c_time);
                                    unset($__startT);
                                }
                            }
                        }
                    }
                    unset($mac_curl_get_data);
                    Cache::set('vod_id_list_douban_id', $v['vod_id']);
                    if ($is_log == false) {
                        log::info('js-采集豆瓣评分-过滤::' . $v['vod_name']);
                    }
                    $this->addUpError($v, $get_search_id, $e_err);
                }
                $page = $page + 1;
            }
        } catch (Exception $e) {
            $output->writeln("end.3." . $e);
            $output->writeln("end.3." . $this->cmsDb->getlastsql());
            file_put_contents('log.txt', 'close_url||' . $e . PHP_EOL, FILE_APPEND);
        }
        $output->writeln("end....");
    }


    //采集详情页面
    public function vod_douBan_details($lcs, $v, $as_k, $cookie, $get_search_id, $e_err)
    {
        log::info('js-采集豆瓣评vod_douBan_details:' . $get_search_id);

        $getDetailsData = [];
        $link_url = $as_k['link'];
        if (!empty($get_search_id) && $get_search_id > 0) { //存在id
            log::info('js-采集豆瓣评分-ok-id::' . $get_search_id);
            try {
                libxml_use_internal_errors(true);
                $mac_curl_get_details_data = $this->ql->browser(function (\JonnyW\PhantomJs\Http\RequestInterface $r) use ($link_url, $cookie) {
                    $r->setMethod('GET');
                    $r->addHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9');
                    $r->addHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36');
                    $r->addHeader('Cookie', $cookie);
                    $r->setUrl($link_url);
//                    $r->setTimeout(10000); // 10 seconds
//                    $r->setDelay(2); // 3 seconds
                    return $r;
                }, false, [
//                        '--proxy' => "183.129.244.16:17238",
                    '--proxy' => $this->proxy_server . ":" . $this->get_port,
                    '--proxy-type' => 'http',
                    '--load-images' => 'no',
//                    ])->getHtml();
                ])->rules([
                    'vod_name' => ['h1 >span:eq(0)', 'text'],
                    'vod_year' => ['h1 >span:eq(1)', 'text', '', function ($content) {
                        $content = mac_trim_all(str_replace('(', '', $content));
                        $content = mac_trim_all(str_replace(')', '', $content));
                        return $content;
                    }],
                    'vod_douban_score' => ['.rating_num', 'text'],
                    'vod_score_all' => ['.rating_people >span', 'text'],
//                    'vod_blurb' => ['#link-report', 'text'],
                    'vod_text' => ['#info', 'html', '', function ($content) {
//                        log::info('js-datall-content---' .$content);
                        $ex_data = [];
                        $ex_arr = explode("<br>", trim($content));
                        $strpos_data = [
                            'vod_director' => '导演:',
                            'vod_actor' => '主演:',
                            'vod_writer' => '编剧:',
                            'vod_tag' => '类型:',
                            'vod_class' => '类型:',
                            'vod_area' => '制片国家/地区:',
                            'vod_lang' => '语言:',
                            'vod_pubdate' => '首播:',
                            'vod_duration' => '片长:',
                            'vod_total' => '集数:',
                            'vod_sub' => '又名:',
                        ];
                        foreach ($ex_arr as $ex_k => $ex_v) {
                            if (empty($ex_v)) {
                                continue;
                            }
                            //去掉html 标记
                            $ex_v = strip_tags($ex_v);
                            foreach ($strpos_data as $s_k => $s_v) {
                                if (strpos($ex_v, $s_v) !== false) {
                                    if ($s_k == 'vod_duration') {
                                        if (strpos($ex_v, '/') !== false) {
                                            $ex_arr=explode('/',$ex_v);
                                            $ex_v = $ex_arr[0]??'';
                                            if (strpos($ex_v, ':') !== false) {
                                                $ex_arr=explode(':',$ex_v);
                                                $ex_v = $ex_arr[1]??'';
                                            }
                                        }
                                        $ex_v = mac_trim_all(str_replace('单集', '', $ex_v));
                                    }
                                    if ($s_k == 'vod_pubdate') {
                                        $ex_v = str_replace('上映日期:', '首播:', $ex_v);
                                        $ex_v = explode('(', $ex_v)[0] ?? $ex_v;
                                    }
                                    if ($s_k == 'vod_actor') {
                                        $ex_v = trim(mb_substr($ex_v, 0, 240));
                                    }
                                    if ($s_k == 'vod_total') {
                                        $ex_v = trim(findNum($ex_v));
                                    }
                                    $ex_data[$s_k] = mac_trim_all(str_replace($s_v, '', $ex_v));
                                    $ex_data[$s_k] =str_replace('/',',', $ex_data[$s_k]);;
                                }
                            }
                            $ex_data['vod_author'] = '豆瓣';
                        }
                        return $ex_data;
                    }],
                ])->range('#content')->query()->getData();

                log::info('js-err-iiii---proxy-' . $this->proxy_server . ":" . $this->get_port);
                $mac_curl_get_details_data = objectToArray($mac_curl_get_details_data);
                if (isset($mac_curl_get_details_data[0]) && !empty($mac_curl_get_details_data[0])) {
                    $detailsData = $mac_curl_get_details_data[0];
                    $detailsDataText = $detailsData['vod_text'];
                    unset($detailsData['vod_text']);
                    $getDetailsData = array_merge($detailsData, $detailsDataText);
                    $getDetailsData['vod_douban_id'] = $get_search_id;
                    $getDetailsData['vod_score'] = $getDetailsData['vod_douban_score'];
                    $getDetailsData['vod_reurl'] = $link_url;
                    $getDetailsData['vod_total'] = $detailsDataText['vod_total'] ?? '0';
                    if ($getDetailsData['vod_score'] <= 0) {
                        $getDetailsData['vod_score_num'] = 0;
                    } else {
                        $getDetailsData['vod_score_num'] = intval($getDetailsData['vod_score_all'] / $getDetailsData['vod_score']);
                    }
                    unset($detailsData);
                    unset($detailsDataText);
                }
            } catch (Exception $e) {
                log::info('js-err--过滤iii' . $link_url);
            }

//            log::info('js-datall-' .json_encode($getDetailsData,true));
            if (!empty($getDetailsData)) {
                //更新详情表
                $this->vod_details_update($get_search_id, $getDetailsData, $v);
                $rade = $lcs->getSimilar(mac_trim_all($v['vod_name']), mac_trim_all($as_k['title'])) * 100;
                log::info('js-采集豆瓣评分-比例::' . $rade);
                if ($rade > 40) {
                    //名字或者别名 和导演相等
                    $getDetailsData['vod_sub'] = $getDetailsData['vod_sub'] ?? '';
                    $getDetailsData['vod_director'] = $getDetailsData['vod_director'] ?? '';
                    $v['vod_sub'] = $v['vod_sub'] ?? '';
                    $v['vod_director'] = $v['vod_director'] ?? '';
                    if ((mac_trim_all($getDetailsData['vod_name']) == mac_trim_all($v['vod_name']) || (mac_trim_all($getDetailsData['vod_sub']) == mac_trim_all($v['vod_sub'])) || (mac_trim_all($getDetailsData['vod_sub']) == mac_trim_all($v['vod_name']))) && mac_trim_all($getDetailsData['vod_director']) == mac_trim_all($v['vod_director'])) {
                        $whereId = [];
                        $whereId['vod_id'] = $v['vod_id'];
                        $up_res = $this->vodDb->where($whereId)->update($getDetailsData);
                        if ($up_res) {
                            $e_err = true;
                            log::info('js-采集豆瓣评分-succ::' . $v['vod_name'] . '---' . $v['vod_id']);
                        }
                        unset($whereId);
                    }
                }
            }
        }
        unset($getDetailsData);
        unset($mac_curl_get_details_data);
        return $e_err;
    }


    public function addUpError($v, $douban_id, $e_err)
    {
        log::info('js-addUpError::');
        $error_where = [];
        $error_where['vod_id'] = $v['vod_id'];
        $error_data = $this->vod_errorDb->where($error_where)->find();
        if (empty($error_data)) {
            log::info('js-addUpError::su--' . $douban_id);
//            if ($douban_id > 0) {
            $deas_data['vod_id'] = $v['vod_id'];
            $deas_data['title'] = $v['vod_name'] ?? '';
            $deas_data['douban_id'] = $douban_id;
            if ($e_err == false) {
                $deas_data['count'] = 1;
            } else {
                $deas_data['count'] = 0;
            }
            try {
                log::info('js-addUpError::su??');
                $this->vod_errorDb->insert($deas_data);
            } catch (\Exception $e) {
                log::info('js-采集豆瓣评分-err0r数据重复添加::' . $v['vod_id'] . $e);
            }
//            }
        } else {
            log::info('js-addUpError::count');
            if ($e_err == false) {
                $deas_data['count'] = $error_data['count'] + 1;
            } else {
                $deas_data['count'] = 0;
            }
            $deas_data['douban_id'] = $douban_id;
            log::info('js-addUpError::count-up');
            $deas_data['douban_id'] = $douban_id;

            $this->vod_errorDb->where($error_where)->update($deas_data);
        }
    }

    //插入详情数据
    protected function vod_details_install($as_k)
    {
        $link = explode('subject', $as_k['link']);
        $get_search_id = $link[1] ?? '';
        $get_search_id = str_replace('/', '', $get_search_id);
        $deas_data = $as_k;
        $deas_data['douban_id'] = $get_search_id;
        log::info('js-采集豆瓣评分add-.id' . $get_search_id);
        log::info('js-采集豆瓣评分add-.url -id' . $as_k['link']);
        if ($get_search_id > 0) {
            $deas_data['time'] = time();
            try {
                log::info('js-采集豆瓣评分suc');
                $t_data = $this->cmsDb->where(['douban_id' => $get_search_id])->find();
                if (empty($t_data)) {
                    $this->cmsDb->insert($deas_data);
                }

            } catch (\Exception $e) {
                log::info('js-采集豆瓣评分-数据重复添加::' . $as_k['title'] . $e);
            }
        }
        return $get_search_id;
    }

    //更新详情表
    protected function vod_details_update($get_search_id, $getDetailsData, $v)
    {
        $details_data = [];
        $details_data['name'] = $getDetailsData['vod_name'] ?? '';
        $details_data['name_as'] = trim(mb_substr($getDetailsData['vod_sub'] ?? '', 0, 200));
        $details_data['vod_director'] = $getDetailsData['vod_director'] ?? '';
        $details_data['vod_actor'] = $getDetailsData['vod_actor'] ?? '';
        $details_data['score'] = $getDetailsData['vod_douban_score'] ?? '0.0';
        $details_data['text'] = json_encode($getDetailsData, true);
        if (!empty($details_data)) {//首先更新详情表
            log::info('js-vo222d--');
            $where_id = [];
            $where_id['douban_id'] = $get_search_id;
            $up_res = $this->cmsDb->where($where_id)->update($details_data);
            if ($up_res) {
                log::info('js-采集豆瓣评分-deteils-succ::' . $v['vod_name'] . '---' . $v['vod_id']);
            }
        }
    }
}