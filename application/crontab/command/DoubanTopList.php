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
        $delWhere = [];
        $delWhere['vod_id'] = 0;
        $delWhere['type_id'] = 0;
        $delWhere['status'] = 0;

        //获取豆瓣top list 榜单
//        $this->getDouBanTopList();
        //获取腾讯top list 榜单
        $this->getTxTopList($x);
        Db::name('douban_recommend')->whereOr($delWhere)->delete();
        $sql = 'DELETE FROM douban_recommend WHERE vod_id IN (SELECT vid FROM ( SELECT MIN( vod_id ) AS vid FROM douban_recommend WHERE vod_id > 0 GROUP BY vod_id HAVING count( vod_id ) > 1 ) a)';
        $res = Db::execute($sql);
        if($res){
            log::info('delete');
        }
        $output->writeln("开启采集:采集豆瓣热门end:");
    }

    //获取豆瓣top list 榜单
    public function getDouBanTopList()
    {

        //获取top代理ip
        $this->get_zm_port();
        foreach ($this->search_url as $k => $v) {
            sleep(1);

            $str_data = $this->getUrl($v);
            $mac_curl_get_data = array_pop(explode("\r\n", $str_data));
//            $mac_curl_get_data = mac_curl_get($v);
            $getSearchData = json_decode($mac_curl_get_data, true);
            log::info('采集豆瓣热门-url-::' . $v);
            log::info('采集豆瓣热门-url-data::' . $mac_curl_get_data);
            if (isset($getSearchData['subjects']) && !empty($getSearchData['subjects'])) {
                foreach ($getSearchData['subjects'] as $sub_key => $sub_val) {

                    $getDouBan['name'] = mac_trim_all(mac_characters_format($sub_val['title']));
                    $getDouBan['cj_type'] = ['neq',1];
                    $douBanName = $this->getDouBanRecommendFindData($getDouBan);
                    if(!empty($douBanName)){ //名字相等且不是自己的类型
                        continue; //暂时过滤
                    }
                    //查询视频表 豆瓣id不等于空
                    $vodDouBanFindWhere['vod_name'] =mac_trim_all(mac_characters_format($sub_val['title']));
                    $vodDouBanFindWhere['vod_douban_id'] = $sub_val['id'];
                    $vodDouBanFindData = $this->vodDb->field('vod_id,type_id_1,type_id,vod_name as name')->whereOr($vodDouBanFindWhere)->find();
                    //查询推荐表 豆瓣id不等于空
                    $getDouBanRecommendFindWhere['douban_id'] = $sub_val['id'];
                    $douBanRecommendFindData = $this->getDouBanRecommendFindData($getDouBanRecommendFindWhere);
                    $reCommend['name'] =  $vodDouBanFindWhere['vod_name'] ;
                    if (!empty($vodDouBanFindData)) {
                        $reCommend['status'] = 1;
                        $type_id =  $vodDouBanFindData['type_id_1'];
                        if($vodDouBanFindData['type_id_1'] == 0){
                            $type_id =  $vodDouBanFindData['type_id'];
                        }
                        $reCommend['type_id'] = $type_id;
                        $reCommend['vod_id'] = $vodDouBanFindData['vod_id'];
                        $reCommend['douban_id'] = $sub_val['id'];
                        $reCommend['time'] = date('Y-m-d', time());
                    } else {
                        $reCommend['type_id'] = $k;
                        $reCommend['status'] = 0;
                        $reCommend['vod_id'] = 0;
                        $reCommend['douban_id'] = $sub_val['id'];
                        $reCommend['time'] = date('Y-m-d', time());
                    }
                    if (!empty($douBanRecommendFindData)) {
                        if (empty($vodDouBanFindData)) {
                            unset( $reCommend['time']);
                        }
                        $reCommend['cj_type'] = 1;
                        $result = Db::name('douban_recommend')->where($douBanRecommendFindData)->update($reCommend);
                    }else{
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
    public function getTxTopList($x)
    {
        $this->get_zm_port();
        $this->ql = QueryList::getInstance();
        //选择mac扩展还是 linux 扩展
        if (!empty($x) && $x == 'mac') {
            $ph_js_path = ROOT_PATH . 'extend/phantomjs_macosx/bin/phantomjs';
        } else {
            $ph_js_path = ROOT_PATH . 'extend/phantomjs_linux/bin/phantomjs';
        }
        //使用queryList + PhantomJs
        $this->ql->use(PhantomJs::class, $ph_js_path);
        $this->ql->use(PhantomJs::class, $ph_js_path, 'browser');
        $cookie = 'pgv_pvi=2732309504; RK=Z6hEqhwGfS; ptcz=af928aa6f58b53e8c7479815705f88322d9cbe2f68c46123061af88daa555b45; pgv_pvid=2187433834; pgv_info=ssid=s479668352; ac_wx_user=; tvfe_boss_uuid=cfa79a938ae6ff34; ts_refer=www.baidu.com/link; ts_uid=5158460776; bucket_id=9231005; video_guid=03b88500393cabe4; video_platform=2; ptag=www_baidu_com|channel; ad_play_index=65; qv_als=ioO5+ra6gDY5IKnUA11588918113T2RFkQ==';
        $tx_url = [
            'https://v.qq.com/channel/cartoon?_all=1&channel=cartoon&listpage=1&sort=18&offset=0&pagesize=30',
            'https://v.qq.com/channel/cartoon?_all=1&channel=cartoon&listpage=1&sort=18&offset=30&pagesize=20',
        ];
        foreach ($tx_url as $tx_k => $tx_v) {
            try {
                libxml_use_internal_errors(true);
                $mac_curl_get_data = $this->ql->browser(function (\JonnyW\PhantomJs\Http\RequestInterface $r) use ($tx_v, $cookie) {
                    $r->setMethod('GET');
                    $r->addHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9');
//                          $r->addHeader('Referer', $url);
                    $r->addHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
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
//            type_id =4
            if (!empty($getSearchData)) {
                foreach ($getSearchData as $get_k => $get_v) {
                    $where = [];
//                    $where['type_id'] = 4;
                    $TxWhere['vod_name'] =  $where['name'] = mac_trim_all(mac_characters_format($get_v['name']));
                    $res = $this->getDouBanRecommendFindData($where);
                    $DouBanRes = $this->getVodTxDouBanFindData($TxWhere);
                    if (!empty($res)) {
                        $reCommend['name'] = $where['name'];
                        if (!empty($DouBanRes)) {
                            $reCommend['status'] = 1;
                            $type_id =  $DouBanRes['type_id_1'];
                            if($DouBanRes['type_id_1'] == 0){
                               $type_id =  $DouBanRes['type_id'];
                            }
                            $reCommend['type_id'] = $type_id;
                            $reCommend['vod_id'] = $DouBanRes['vod_id'];
                            $reCommend['douban_id'] = $DouBanRes['douban_id'] ?? 0;
                            $reCommend['time'] = date('Y-m-d', time());
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
                            $type_id =  $DouBanRes['type_id_1'];
                            if($DouBanRes['type_id_1'] == 0){
                                $type_id =  $DouBanRes['type_id'];
                            }
                            $reCommend['type_id'] =$type_id;
                            $reCommend['vod_id'] = $DouBanRes['vod_id'];
                            $reCommend['douban_id'] = $DouBanRes['douban_id'] ?? 0;
                        } else {
                            $reCommend['type_id'] = 4;
                            $reCommend['status'] = 0;
                            $reCommend['vod_id'] = 0;
                            $reCommend['douban_id'] = 0;
                        }
                        $reCommend['time'] = date('Y-m-d', time());
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
    protected function test(){
/*        $empty_where = [];
        $install_data = [];
        $mac_url = $this->get_search_id . $sub_val['id'];//获取mac Cms信息
        $getCmsData = $this->getCmsData($mac_url);
        if (!empty($getCmsData)) {
            if (isset($getCmsData['status']) && $getCmsData['status'] == 200 && !empty($getCmsData['data'])) {
                $getData = $getCmsData['data'];
                $empty_where['vod_director'] = $getData['vod_director'];
                if (!empty($getData['vod_title'])) {
                    $empty_where_or['vod_name'] = mac_characters_format($getData['vod_name']);
                    if (!empty(mac_characters_format($getData['vod_name']))) {
                        $sql = "vod_name = '" . mac_characters_format($getData['vod_name']) . "'  or vod_douban_id= '" . $sub_val['id'] . "'";
                    } else {
                        $sql = "vod_douban_id= '" . $sub_val['id'] . "'";
                    }
                    $res = $this->getVodDouBanFindORData($empty_where, $sql);
                } else {
                    $empty_where['vod_name'] = mac_characters_format($getData['vod_name']);
                    $res = $this->getVodDouBanFindData($empty_where);
                }
                if (!empty($res)) {
                    $install_data = $res;
                    //添加淘豆id和评分
                    $getDataCms = $this->getFFConTent($getData);
                    $getDataCms['vod_douban_id'] = $sub_val['id'];
                    $this->vodDb->where(['vod_id' => $res['vod_id']])->update($getDataCms);;
                }
            }
        }
        $install_data['time'] = date("Y-m-d", time());
        $install_data['douban_id'] = $sub_val['id'];
        $install_data['name'] = $sub_val['title'];
        $getDouBanRecommendFindWhere['douban_id'] = $sub_val['id'];
        $douBanRecommendFindData = $this->getDouBanRecommendFindData($getDouBanRecommendFindWhere);
        if (!empty($douBanRecommendFindData)) {
            $vodDouBanFindData['status'] = 1;
            $vodDouBanFindData['time'] = date("Y-m-d", time());
            $result = Db::name('douban_recommend')->where($getDouBanRecommendFindWhere)->update($vodDouBanFindData);
        } else {
            if (empty($install_data)) {
                $install_data['vod_id'] = 0;
                $install_data['type_id'] = 0;
                $install_data['status'] = 0;
            }
            $result = Db::name('douban_recommend')->insert($install_data);
        }
        if ($result) {
            log::info('采集豆瓣热门-succ' . $sub_val['title']);
        } else {
            log::info('采集豆瓣热门-error' . $sub_val['title']);
        }*/

    }

}