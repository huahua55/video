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


class DoubanTopList extends Common
{
    protected $vodDb;//db
    protected $search_url = [
        'tv' => 'https://movie.douban.com/j/search_subjects?type=tv&tag=%E7%83%AD%E9%97%A8&sort=recommend&page_limit=20&page_start=0',
//        'tv_dm' => 'https://movie.douban.com/j/search_subjects?type=tv&tag=%E6%97%A5%E6%9C%AC%E5%8A%A8%E7%94%BB&sort=recommend&page_limit=20&page_start=0',
        'movie' => 'https://movie.douban.com/j/search_subjects?type=movie&tag=%E7%83%AD%E9%97%A8&sort=recommend&page_limit=20&page_start=0',
    ];
    protected $get_tv_tag = ["热门"];//电视剧 热门 ,"日本动画"
    protected $get_movie_tag = ["热门"];//电影 热门



    protected function configure()
    {
        //db
        $this->vodDb = Db::name('vod');
        //获取豆瓣id
        $this->setName('doubanTopList')->addArgument('parameter')
            ->setDescription('定时计划：采集豆瓣热门');
    }

    // 取出数据豆瓣id数据
    protected function getVodDouBanFindData($where)
    {
      return  $this->vodDb->field('vod_id,type_id_1 as type_id, vod_name as name')->whereOr(function($query) use($where){
            $query->whereOr("replace(`vod_name`,' ','') = '".$where['vod_name']."' ");
            $query->whereOr("replace(`vod_sub`,' ','') = '".$where['vod_sub']."' ");
            $query->whereOr("vod_douban_id = '".$where['id']."' ");
        })->find();
    }

    // 取出数据爬取豆瓣的推荐的数据
    protected function getDouBanRecommendFindData($where)
    {
        return Db::name('douban_recommend')->field('id')->where($where)->find();
    }


    protected function execute(Input $input, Output $output)
    {
        $port= $this->getPort();
        if($port  ==false){
            log::info('get_port-::' );
            sleep(3);
            $port = $this->getPort();
        }
        $heads = [
            'Accept' => '*/*',
//                    'Accept'=> 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            'Connection' => 'keep-alive',
            'DNT' => '1',
            'Cache-Control' => 'max-age=0',
            'Content-Type' => 'application/json; charset=utf-8',
            'Host' => 'movie.douban.com',
            'Origin' => 'https://search.douban.com',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Content-Type-Options' => 'nosniff',
            'X-Xss-Protection' => '1; mode=block',
            'Remote Address' => '154.8.131.165:443',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36',
        ];

        $cookie = 'bid=h4nqLajQEBo; douban-fav-remind=1; __gads=ID=f547fc5d1024460e:T=1584933974:S=ALNI_MYnz5KEHQFfcZy0gMy6CM04qFHEGg; ll="108288"; __yadk_uid=YtQ3MJmZAPkUGuuQXMJVwIUlrNH54m9L; _vwo_uuid_v2=DE8FD61CD60225FE96D81709B68421C2D|866f6dabae9a822d17e89ca947c01f78; __utmz=223695111.1587220615.6.4.utmcsr=search.douban.com|utmccn=(referral)|utmcmd=referral|utmcct=/movie/subject_search; __utmz=30149280.1587220892.11.8.utmcsr=baidu|utmccn=(organic)|utmcmd=organic; _pk_ref.100001.4cf6=%5B%22%22%2C%22%22%2C1587346321%2C%22https%3A%2F%2Fsearch.douban.com%2Fmovie%2Fsubject_search%3Fsearch_text%3D%25E9%25AC%25BC%25E5%25BA%2597%25E5%258F%25A6%25E6%259C%2589%25E4%25B8%25BB%26cat%3D1002%22%5D; _pk_ses.100001.4cf6=*; ap_v=0,6.0; __utma=30149280.367404461.1584933975.1587220892.1587346322.12; __utmb=30149280.0.10.1587346322; __utmc=30149280; __utma=223695111.831800547.1587174690.1587220615.1587346322.7; __utmb=223695111.0.10.1587346322; __utmc=223695111; _pk_id.100001.4cf6=cbda30d4a1bb8093.1587174690.7.1587347875.1587220615.';
        // 输出到日志文件
        $output->writeln("开启采集:采集豆瓣热门:");
        foreach ($this->search_url as $k => $v) {
            if ($k == 'tv' || $k == 'tv_dm') {
                $heads['Referer'] = 'https://movie.douban.com/tv/';
            }else{
                $heads['Referer'] = 'https://movie.douban.com/explore';
            }
            if($port != false){
                $mac_curl_get_data = $this->testing($v,$port);
            }else{
                $mac_curl_get_data = mac_curl_get($v, $heads, $cookie);
            }

            $getSearchData = json_decode($mac_curl_get_data, true);
            log::info('采集豆瓣热门-url-::' . $v);
            log::info('采集豆瓣热门-url-data::' . $mac_curl_get_data);
            if (isset($getSearchData['subjects']) && !empty($getSearchData['subjects'])) {
                foreach ($getSearchData['subjects'] as $sub_key => $sub_val) {
                    //存在豆瓣id 不采集数据
                    $getDouBanRecommendFindWhere['douban_id'] = $sub_val['id'];
                    $douBanRecommendFindData = $this->getDouBanRecommendFindData($getDouBanRecommendFindWhere);
                    if (!empty($douBanRecommendFindData)) {
                        $vodDouBanFindData['status'] = 0;
                        log::info('采集豆瓣热门-存在过滤-::' . $sub_val['id']);
                        $update_where['time'] = date("Y-m-d",time());
                        Db::name('douban_recommend')->where($getDouBanRecommendFindWhere)->update($update_where);
                        continue;
                    }
                    $vodDouBanFindWhere['vod_name'] = mac_trim_all($sub_val['title']);
                    $vodDouBanFindWhere['vod_sub'] = mac_trim_all($sub_val['title']);
                    $vodDouBanFindWhere['id'] = $sub_val['id'];
                    $vodDouBanFindData = $this->getVodDouBanFindData($vodDouBanFindWhere);
                    $vodDouBanFindData['douban_id'] = $sub_val['id'];
                    $vodDouBanFindData['time'] = date("Y-m-d", time());
                    if (empty($vodDouBanFindData)) {
                        $vodDouBanFindData['status'] = 0;
                        $vodDouBanFindData['name'] = $sub_val['title'];
                        $res = Db::name('douban_recommend')->insert($vodDouBanFindData);
                        log::info('采集豆瓣热门-vod不存在过滤-::' . $sub_val['title']);
                    } else {
                        $vodDouBanFindData['status'] = 1;
                        $res = Db::name('douban_recommend')->where(['douban_id'=>$sub_val['id']])->update($vodDouBanFindData);
                    }
                    if ($res) {
                        log::info('采集豆瓣热门-succ' . $sub_val['title']);
                    } else {
                        log::info('采集豆瓣热门-error' . $sub_val['title']);
                    }
                }
            }
        }
        $output->writeln("开启采集:采集豆瓣热门end:");
    }
}