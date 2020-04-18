<?php

namespace app\crontab\command;

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

class DoubanScore extends Command
{
    protected $vodDb ;//db
    protected $search_url_re= 'https://search.douban.com/movie/subject_search?search_text=%s&cat=1002';//豆瓣搜索接口
    protected $search_url= 'https://movie.douban.com/j/subject_suggest?q=%s';//豆瓣搜索接口
    protected $get_search_id= 'http://api.maccms.com/douban/?callback=douban&id=';//cms 通过id获取内容
//    protected $get_search_id= 'http://api.douban.com/v2/movie/subject/%s?apikey=0df993c66c0c636e29ecbb5344252a4a';//cms 通过id获取内容
//30393997

    protected function configure()
    {
        //db
        $this->vodDb = Db::name('vod');
        //获取豆瓣id
        $this->setName('DoubanScore')->addArgument('parameter')
            ->setDescription('定时计划：采集豆瓣评分');
    }

    // 取出数据豆瓣评分为空数据
    protected function getVodDoubanScoreData($where,$order,$page,$limit,$start){

        $limit_str = ($limit * ($page-1) + $start) .",".$limit;
        $total =  $this->vodDb->where($where)->count();
        $list = $this->vodDb->field('vod_id,vod_name,vod_class,vod_actor,vod_director,vod_douban_id,vod_douban_score')->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount'=>ceil($total/$limit),'list'=>$list];
    }

    protected function execute(Input $input, Output $output)
    {
        // 输出到日志文件
        $output->writeln("开启采集:采集豆瓣评分");
        $start = 0;
        $page  = 1;
        $limit = 20;
        $is_true = true;
        $where = [
            'vod_douban_id'=>0,
        ];
//        $startTime =  date("Y-m-d 00:00:00",time());
//        $endTime =  date("Y-m-d 23:59:59",time());
//        $where['vod_time'] =['between',[strtotime($startTime),strtotime($endTime)]];
        $order='vod_id desc';
        //进入循环 取出数据
        while ($is_true){
            //数据
            $douBanScoreData = $this-> getVodDoubanScoreData($where,$order,$page,$limit,$start);
            $pagecount = $douBanScoreData['pagecount'] ?? 0;
            if($page > $pagecount){
                $is_true = false;
                log::info('采集豆瓣评分结束...');
                $output->writeln("结束....");
                break;
            }
            foreach ($douBanScoreData['list'] as $k=>$v){
                $is_log = false;
//                $v['vod_name'] = '斗罗大陆';
                $heads = [
                    'Accept'=> '*/*',
                    'Access-Control-Allow-Origin'=> 'https://search.douban.com',
                    'Accept-Encoding'=> 'gzip, deflate, br',
                    'Accept-Language'=> 'zh-CN,zh;q=0.9,en;q=0.8',
                    'Connection'=> 'keep-alive',
                    'DNT'=> '1',
                    'Content-Type'=> 'application/json; charset=utf-8',
                    'Host'=> 'movie.douban.com',
                    'Origin'=> 'https://search.douban.com',
                    'Referer'=> sprintf($this->search_url_re,$v['vod_name']),
                    'Sec-Fetch-Dest'=>'empty',
                    'Sec-Fetch-Mode'=>'cors',
                    'Sec-Fetch-Site'=>'same-site',
                    'X-Content-Type-Options'=>'nosniff',
                    'X-DAE-App'=>'movie',
                    'X-DAE-Instance'=>'default',
                    'X-Douban-Mobileapp'=>'0',
                    'X-DOUBAN-NEWBID'=>'lPbsZAEfswI',
                    'X-Xss-Protection'=>'1; mode=block',
                    'Remote Address'=>'154.8.131.165:443',
                    'User-Agent'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36',
                ];

                $url = sprintf($this->search_url,$v['vod_name']);

//                $url = $this->search_url.$v['vod_name'];
                //获取豆瓣id
                $getSearchData = json_decode(mac_curl_get($url,$heads,'bid=lPbsZAEfswI; Expires=Sun, 18-Apr-21 10:10:52 GMT; Domain=.douban.com; Path=/'),true);
                if(!empty($getSearchData) && isset($getSearchData[0])){

                   if(isset($getSearchData[0]['id'])){
                       $get_url_search_id = $this->get_search_id.$getSearchData[0]['id'];
                       $get_url_search_id_data = mac_curl_get($get_url_search_id);
                       $get_url_search_id_data =str_replace('douban(','',$get_url_search_id_data);
                       $get_url_search_id_data =str_replace(');','',$get_url_search_id_data);
                       $get_url_search_id_data = $this->isJsonBool($get_url_search_id_data,true);
                       if(!empty($get_url_search_id_data) && $get_url_search_id_data['code'] == 1 && !empty($get_url_search_id_data['data'])){
                           $res  =  $get_url_search_id_data['data'];
                           $is_log = true;
                           $vod_data = $this->getConTent($res);
                           if(empty($v['vod_sub']) && $v['vod_name'] != $res['vod_name']){
                               $vod_data['vod_sub'] = $res['vod_name'];
                           }

                           if(!empty($vod_data)){
                               $whereId = [];
                               $whereId['vod_id'] = $v['vod_id'];
                               if(isset($vod_data['vod_doucore'])){
                                   unset($vod_data['vod_doucore']);
                               }
                               $up_res = $this->vodDb->where($whereId)->update($vod_data);
                               if($up_res){
                                   log::info('采集豆瓣评分-succ::'.$v['vod_name']);
                               }
                           }
                       }
                   }
                    sleep(8);
                }

                if($is_log == false){
                    log::info('采集豆瓣评分-过滤::'.$v['vod_name']);
                }
                sleep(2);
            }
            $page = $page + 1;
        }
        $output->writeln("end....");
    }

    protected function isJsonBool($data = '', $assoc = false)
    {
        $data = json_decode($data, $assoc);
        if (($data && is_object($data)) || (is_array($data) && !empty($data))) {
            return $data;
        }
        return false;
    }


    protected function getConTent($res){
        $vod_data = [];
        //总集数
        if (isset($res['vod_total'])){
            $vod_data['vod_total'] = $res['vod_total'];
        }
        //连载数
        if (isset($res['vod_serial']) && !empty($res['vod_serial'])){
            $vod_data['vod_serial'] = trim($res['vod_serial']);
        }
        // $vod_data['vod_name'] = $res['vod_name'];
        //  $vod_data['vod_pic'] = $res['vod_pic'];

        //对白语言
        if (isset($res['vod_lang'])){
            $vod_data['vod_lang'] = $res['vod_lang'];
        }
        //资源类别
        if (isset($res['vod_state'])){
            $vod_data['vod_state'] = $res['vod_state'];
        }
        //视频标签
        if (isset($res['vod_tag'])){
            $vod_data['vod_tag'] = trim(mb_substr($res['vod_tag'],0,100));
        }

        //发行地区
        if (isset($res['vod_area'])){
            $vod_data['vod_area'] = trim($res['vod_area']);
        }
        //主演列表
        if (isset($res['vod_actor'])){
            $vod_data['vod_actor'] = $res['vod_actor'];
        }
        //导演
        if (isset($res['vod_director'])){
            $vod_data['vod_director'] =trim($res['vod_director']);
        }
        //上映日期
        if (isset($res['vod_pubdate'])){
            $vod_data['vod_pubdate'] = mac_format_text(trim($res['vod_pubdate']));
        }
        //编剧
        if (isset($res['vod_writer'])){
            $vod_data['vod_writer'] = mac_format_text($res['vod_writer']);
        }
        //平均分
        if (isset($res['vod_score'])){
            $vod_data['vod_score'] = trim($res['vod_score']);
        }
        //评分次数
        if (isset($res['vod_score_num'])){
            $vod_data['vod_score_num'] = $res['vod_score_num'];
        }
        //总评分
        if (isset($res['vod_score_all'])){
            $vod_data['vod_score_all'] = $res['vod_score_all'];
        }
//        //简介
//        if (isset($res['vod_content'])){
//            $vod_content = trim($res['vod_content']);
//            $vod_data['vod_blurb'] = "'$vod_content'";
//        }
        //时长
        if (isset($res['vod_duration'])){
            $vod_data['vod_duration'] = trim($res['vod_duration']);
        }

        //豆瓣id
        if (isset($res['vod_douban_id'])){
            $vod_data['vod_douban_id'] = $res['vod_douban_id'];
        }
        //豆瓣评分
        if (isset($res['vod_douban_score'])){
            $vod_data['vod_douban_score'] = $res['vod_douban_score'];
        }
        //扩展分类
        if (isset($res['vod_class'])){
            $vod_data['vod_class'] =mac_format_text(trim($res['vod_class'])) ;
        }
        //来源地址
        if (isset($res['vod_reurl'])){
            $vod_data['vod_reurl'] = trim($res['vod_reurl']);
        }
        //编辑人
        if (isset($res['vod_author'])){
            $vod_data['vod_author'] = $res['vod_author'];
        }
        return $vod_data;
    }






}