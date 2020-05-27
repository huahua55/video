<?php

namespace app\crontab\command;

use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Log;
use function GuzzleHttp\Psr7\_caseless_remove;
use Exception;
use QL\Ext\PhantomJs;
use QL\QueryList;

class DoubanTopList extends Common
{
    protected $vodDb;//db
    protected $isTrue = false;//
    protected $ql;//querylist
    protected $search_url = [
        '1' => 'https://movie.douban.com/j/search_subjects?type=movie&tag=%E7%83%AD%E9%97%A8&sort=recommend&page_limit=20&page_start=0',
        '2' => 'https://movie.douban.com/j/search_subjects?type=tv&tag=%E7%83%AD%E9%97%A8&sort=recommend&page_limit=20&page_start=0',
//        'tv_dm' => 'https://movie.douban.com/j/search_subjects?type=tv&tag=%E6%97%A5%E6%9C%AC%E5%8A%A8%E7%94%BB&sort=recommend&page_limit=20&page_start=0',

    ];
    protected $get_search_id = 'https://api.daicuo.cc/douban/feifeicms/?id=';//cms 通过id获取内容
    protected $get_tv_tag = ["热门"];//电视剧 热门 ,"日本动画"
    protected $get_movie_tag = ["热门"];//电影 热门


    protected function configure()
    {
        $this->ql = QueryList::getInstance();
        //db
        $this->vodDb = Db::name('vod');
        //获取豆瓣id
        $this->setName('dbTopList')->addArgument('parameter')
            ->setDescription('定时计划：采集豆瓣热门');
    }

    // 取出数据豆瓣id数据
    protected function getVodDouBanFindData($where)
    {
        return $this->vodDb->field('vod_id,type_id_1 as type_id, vod_name as name')->where($where)->find();
    }

    // 取出数据豆瓣id数据
    protected function getVodTxDouBanFindData($where)
    {
        return $this->vodDb->field('vod_id,type_id_1,type_id, vod_name as name,vod_douban_id as douban_id')->where($where)->find();
    }

    // 取出数据豆瓣id数据
    protected function getVodDouBanFindORData($where, $whereOr)
    {
        return $this->vodDb->field('vod_id,type_id_1 as type_id, vod_name as name')->where($where)->where($whereOr)->find();
    }

    // 取出数据爬取豆瓣的推荐的数据
    protected function getDouBanRecommendFindData($where)
    {
        return Db::name('douban_recommend')->field('id')->where($where)->find();
    }


    public function cePing($c, $url = '', $type = '')
    {
        if ($c > 5) {
            return false;
        }
        $isTrue = false;
        $this->get_zm_port();
        $cookies = $this->getCookie('');
        $cookie = $this->newCookie($cookies);
        $str_data = $this->queryListUrl($this->ql, $url, $cookie, $proxy = true);
        if (isset($str_data['subjects']) && !empty($str_data['subjects'])) {
            $isTrue = true;
        }
        if (!empty($this->get_port) && $isTrue == true) {
            $delWhere['type_id'] = $type;
            Db::name('douban_recommend')->where($delWhere)->delete();
            return $str_data;
        } else {
            $c++;
            $this->cePing($c);
        }
        return false;
    }

    protected function execute(Input $input, Output $output)
    {


        // 输出到日志文件
        $output->writeln("开启采集:采集豆瓣热门:");
        //cli模式接受参数
        $myparme = $input->getArguments();
        $parameter = $myparme['parameter'];
        //参数转义解析
        $param = $this->ParSing($parameter);
        $type = $param['type'] ?? ''; //从1 开始爬取
        $x = $param['x'] ?? '';
        $update = $param['update'] ?? '';//
        $delWhere = [];
        $delWhere['vod_id'] = 0;
        $delWhere['type_id'] = 0;
        $delWhere['status'] = 0;
        //获取豆瓣top list 榜单
        $this->getDouBanTopList($update);//是否强制更新
        //获取腾讯top list 榜单
        $this->getTxTopList($x, $update);
//        Db::name('douban_recommend')->whereOr($delWhere)->delete();
//        $sql = 'DELETE FROM douban_recommend WHERE vod_id IN (SELECT vid FROM ( SELECT MIN( vod_id ) AS vid FROM douban_recommend WHERE vod_id > 0 GROUP BY vod_id HAVING count( vod_id ) > 1 ) a)';
//        $res = Db::execute($sql);
//        if($res){
//            log::info('delete');
//        }
        $output->writeln("开启采集:采集豆瓣热门end:");
    }

    //获取豆瓣top list 榜单
    public function getDouBanTopList($update)
    {


        $dateTime = date('Y-m-d', time());
        foreach ($this->search_url as $k => $v) {
            $is_update = false;
            if (!empty($update)) {
                $is_update = true;
            } else {
                $whereTime['time'] = $dateTime;
                $whereTime['type_id'] = $k;
                $res = $this->getDouBanRecommendFindData($whereTime);
                if (empty($res)) {
                    $is_update = true;
                }
            }
            //更新
            if ($is_update == true) {
                $getSearchData = $this->cePing(1, $v, $k);//请求并删除数据
                foreach ($getSearchData['subjects'] as $sub_key => $sub_val) {
                    //查询视频表 豆瓣id不等于空
//                    $vodDouBanFindWhere['vod_name'] =mac_trim_all(mac_characters_format($sub_val['title']));
                    $vodDouBanFindWhere['vod_douban_id'] = $sub_val['id'];
                    $vodDouBanFindWhere['vod_name'] = mac_trim_all(mac_characters_format($sub_val['title']));
                    $vodDouBanFindData = $this->vodDb->field('vod_id,type_id_1,type_id,vod_name as name,vod_play_from,vod_play_url,vod_area,vod_douban_id')->whereOr($vodDouBanFindWhere)->select();
                    $vodDouBanFindNewData = $this->getVodDouBanFindDataList($vodDouBanFindData,$sub_val['id']);//处理
                    //查询推荐表 豆瓣id不等于空
                    $getDouBanRecommendFindWhere['douban_id'] = $sub_val['id'];
                    $douBanRecommendFindData = $this->getDouBanRecommendFindData($getDouBanRecommendFindWhere);
                    $reCommend['name'] = $vodDouBanFindWhere['vod_name'];
                    if (!empty($vodDouBanFindNewData)) {
                        $reCommend['status'] = 1;
                        $reCommend['type_id'] = $k;
                        $reCommend['vod_id'] = $vodDouBanFindNewData['vod_id'];
                        $reCommend['douban_id'] = $sub_val['id'];
                        $reCommend['time'] = $dateTime;
                        $reCommend['country_sort'] = getCountrySort($vodDouBanFindNewData['vod_area']);
                    } else {
                        $reCommend['type_id'] = $k;
                        $reCommend['status'] = 0;
                        $reCommend['vod_id'] = 0;
                        $reCommend['douban_id'] = $sub_val['id'];
                        $reCommend['time'] = $dateTime;
                        $reCommend['country_sort'] = 0;
                    }
                    if (!empty($douBanRecommendFindData)) {
                        if (empty($vodDouBanFindNewData)) {
                            unset($reCommend['time']);
                        }
                        $reCommend['cj_type'] = 1;
                        $result = Db::name('douban_recommend')->where($douBanRecommendFindData)->update($reCommend);
                    } else {
                        $reCommend['cj_type'] = 1;
                        $result = Db::name('douban_recommend')->insert($reCommend);
                    }
                    if ($result) {
                        log::info('采集豆瓣热门-succ' . $reCommend['name']);
                    } else {
                        log::info('采集豆瓣热门-error' . $reCommend['name']);
                    }

                }
            }
        }
    }


    //获取腾讯top list 榜单 国漫
    public function getTxTopList($x, $update)
    {
        $this->get_zm_port();
        $dateTime = date('Y-m-d', time());
        //选择mac扩展还是 linux 扩展
        if (!empty($x) && $x == 'mac') {
            $ph_js_path = ROOT_PATH . 'extend/phantomjs_macosx/bin/phantomjs';
        } else {
            $ph_js_path = ROOT_PATH . 'extend/phantomjs_linux/bin/phantomjs';
        }
        //使用queryList + PhantomJs
        $this->ql->use(PhantomJs::class, $ph_js_path);
        $this->ql->use(PhantomJs::class, $ph_js_path, 'browser');
        $cookie = 'pgv_info=ssid=s9901614760; pgv_pvid=7182506310; tvfe_boss_uuid=3da8ca32a697db78; ad_play_index=22; video_guid=a53302aaebb14b76; video_platform=2; ts_last=v.qq.com/channel/cartoon; ts_uid=7263585730; bucket_id=9231003';
        $tx_url = [
            'https://v.qq.com/channel/cartoon?_all=1&channel=cartoon&listpage=1&sort=18&offset=0&pagesize=30',
            'https://v.qq.com/channel/cartoon?_all=1&channel=cartoon&listpage=1&sort=18&offset=30&pagesize=20',
        ];
        foreach ($tx_url as $tx_k => $tx_v) {
            $is_update = false;
            if (!empty($update)) {
                $is_update = true;
            } else {
                $whereTime['time'] = $dateTime;
                $whereTime['type_id'] = 4;
                $res = $this->getDouBanRecommendFindData($whereTime);
                if (empty($res)) {
                    $is_update = true;
                }
            }
            if ($is_update == true) {
                $rand =rand(1,16);
                try {
                    libxml_use_internal_errors(true);
                    $mac_curl_get_data = $this->ql->browser(function (\JonnyW\PhantomJs\Http\RequestInterface $r) use ($tx_v, $cookie,$rand) {
                        $r->setMethod('GET');
                        $r->addHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9');
//                          $r->addHeader('Referer', $url);
                        $r->addHeader('User-Agent', mac_ua_all($rand));
                        $r->addHeader('Cookie', $cookie);
                        $r->setUrl($tx_v);
                        return $r;
                    }, false, [
//                        '--proxy' => "183.129.244.16:51134",
                        '--proxy' => $this->proxy_server . ":" . $this->get_port,
                        '--proxy-type' => 'http',
                        '--load-images' => 'no',
//                    ])->getHtml();
                    ])->rules([
                        'name' => ['a', 'title'],
                    ])->range('.list_item')->query()->getData();
                    $getSearchData = objectToArray($mac_curl_get_data);
                } catch (Exception $e) {
                    log::info('腾讯--过滤' . $tx_v);
                    continue;
                }
                if (!empty($getSearchData)) {
                    if($tx_k == 0){
                        $delWhere['type_id'] = 4;
                        Db::name('douban_recommend')->where($delWhere)->delete();
                    }
                    foreach ($getSearchData as $get_k => $get_v) {
                        $where = [];
//                    $where['type_id'] = 4;
                        $TxWhere['vod_name'] = $where['name'] = mac_trim_all(mac_characters_format($get_v['name']));
                        $res = $this->getDouBanRecommendFindData($where);
                        $vodDouBanFindData = $this->vodDb->field('vod_id,type_id_1,type_id,vod_name as name,vod_play_from,vod_play_url,vod_area')->where($TxWhere)->select();
                        $DouBanRes = $this->getVodDouBanFindDataList($vodDouBanFindData);//处理
                        if (!empty($res)) {
                            $reCommend['name'] = $where['name'];
                            if (!empty($DouBanRes)) {
                                $reCommend['status'] = 1;
//                            $type_id =  $DouBanRes['type_id_1'];
//                            if($DouBanRes['type_id_1'] == 0){
//                               $type_id =  $DouBanRes['type_id'];
//                            }
//                            $reCommend['type_id'] = $type_id;
                                $reCommend['type_id'] = 4;
                                $reCommend['vod_id'] = $DouBanRes['vod_id'];
                                $reCommend['douban_id'] = $DouBanRes['douban_id'] ?? 0;
                                $reCommend['time'] = $dateTime;
                            } else {
                                $reCommend['type_id'] = 4;
                                $reCommend['status'] = 0;
                                $reCommend['vod_id'] = 0;
                                $reCommend['douban_id'] = 0;
                            }
                            $result = Db::name('douban_recommend')->where($res)->update($reCommend);
                            if ($result) {
                                log::info('采集腾讯热门-succ' . $where['name']);
                            } else {
                                log::info('采集腾讯热门-error' . $where['name']);
                            }
                        } else {
                            $reCommend['name'] = $where['name'];
                            if (!empty($DouBanRes)) {
                                $reCommend['status'] = 1;
//                            $type_id =  $DouBanRes['type_id_1'];
//                            if($DouBanRes['type_id_1'] == 0){
//                                $type_id =  $DouBanRes['type_id'];
//                            }
//                            $reCommend['type_id'] =$type_id;
                                $reCommend['type_id'] = 4;
                                $reCommend['vod_id'] = $DouBanRes['vod_id'];
                                $reCommend['douban_id'] = $DouBanRes['douban_id'] ?? 0;
                                $reCommend['country_sort'] = getCountrySort($DouBanRes['vod_area']);
                            } else {
                                $reCommend['type_id'] = 4;
                                $reCommend['status'] = 0;
                                $reCommend['vod_id'] = 0;
                                $reCommend['douban_id'] = 0;
                                $reCommend['country_sort'] = 0;
                            }
                            $reCommend['time'] = $dateTime;
                            $reCommend['cj_type'] = 2;
                            $result = Db::name('douban_recommend')->insert($reCommend);
                            if ($result) {
                                log::info('采集腾讯热门-succ' . $where['name']);
                            } else {
                                log::info('采集腾讯热门-error' . $where['name']);
                            }
                        }
                    }
                }
            }
        }
    }


    //处理数组
    protected function getVodDouBanFindDataList($vodDouBanFindData,$douban_id = '')
    {
        $DouBanRes = [];
        foreach ($vodDouBanFindData as $ky => $v) {
            if(!empty($douban_id)){
                 if($v['vod_douban_id'] == $douban_id){
                     $vodDouBanFindData[$ky]['count'] = 100;
                 }
            }
            $vod_play_from_list = [];
            $vod_play_url_list = [];
            $count = [];
            if (!empty($v['vod_area'])) {
                $vod_area = array_filter(explode(',', $v['vod_area']));
                if (!empty($vod_area)) {
                    $vodDouBanFindData[$ky]['vod_area'] = $vod_area[0] ?? '';
                } else {
                    $vodDouBanFindData[$ky]['vod_area'] = '';
                }
            }
            if (!empty($v['vod_play_from'])) {
                $vod_play_from_list = explode('$$$', $v['vod_play_from']);
            }
            if (!empty($v['vod_play_url'])) {
                $vod_play_url_list = explode('$$$', $v['vod_play_url']);
            }
            foreach ($vod_play_from_list as $ks => $vs) {
                $count[$ks] = count(mac_play_list_one($vod_play_url_list[$ks], $vs));
            }
            unset($vodDouBanFindData[$ky]['vod_play_from']);
            unset($vodDouBanFindData[$ky]['vod_play_url']);
            $vodDouBanFindData[$ky]['count'] = max($count);
        }
        array_multisort(array_column($vodDouBanFindData, 'count'), SORT_STRING | SORT_FLAG_CASE | SORT_NATURAL, $vodDouBanFindData);
        $DouBanRes = array_pop($vodDouBanFindData);
        return $DouBanRes;
    }

}