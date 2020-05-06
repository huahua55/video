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

class CmsVodScore extends Command
{
    protected $vodDb;//db
    protected $get_search_id = 'http://api.maccms.com/douban/?callback=douban&id=';//cms 通过id获取内容
    protected $cmsDb;//db

    protected function configure()
    {

        //db
        $this->vodDb = Db::name('vod');
        $this->cmsDb = Db::name('douban_vod_details');
        $this->ql = QueryList::getInstance();
        //获取豆瓣id
        $this->setName('cmsVodScore')->addArgument('parameter')
            ->setDescription('定时计划：采集cmsVodScore');
    }

    protected function execute(Input $input, Output $output)
    {

        // 输出到日志文件
        $output->writeln("开启采集:合并cmsVodScore评分:");
        try {
            //字符串对比算法
            $lcs = new similarText();
            //cli模式接受参数
            $myparme = $input->getArguments();
            $parameter = $myparme['parameter'];
            //参数转义解析
            $param = $this->ParSing($parameter);

            $type = $param['type']??'';

            $start = 0;
            $page = 1;
            $limit = 20;
            $is_true = true;
            $where = [];
            if($type == 1){
                $where['vod_douban_id'] = ['gt', 0];
            }else{
                $where['vod_douban_id'] = 0;
            }
            $order = 'vod_id asc';

            //进入循环 取出数据
            while ($is_true) {
                //取出数据
                $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
                if (!empty($douBanScoreData)) {
                    $pagecount = $douBanScoreData['pagecount'] ?? 0;

                    if ($page > $pagecount) {
                        $is_true = false;
                        log::info('合并cmsVodScore结束...');
                        $output->writeln("结束...");
                        break;
                    }
                    foreach ($douBanScoreData['list'] as $k => $v) {
                        log::info('合并cmsVodScore进入foreach',$v['vod_name']);
                        $is_log = false;
                        $is_error = false;
                        $mac_curl_get_data = [];
//                        sleep(1);
                        $cms_where['name'] = ['neq', ''];
                        $cms_where['douban_id'] = ['gt', 0];
                        if($type == 1){
                            $cms_where['douban_id'] = $v['vod_douban_id'];
                            $cms_data = $this->cmsDb->where($cms_where)->find();
                        }else{
                            $cms_where['name'] =mac_characters_format($v['vod_name']);
//                            $cms_where1['name_as'] =array(array('eq',$v['vod_sub']),array('eq',$v['vod_sub']), 'or');
                            $cms_where['vod_director'] = $v['vod_director'];
                            $cms_data = $this->cmsDb->where($cms_where)->find();
                        }

//                     print_r($getSearchData);
                        if (!empty($cms_data)) {
                            log::info('合并cmsVodScore-try_su:');
                            if (!empty($cms_data)) {
                                log::info('合并cmsVodScore豆瓣评分-su-::');
                                $res = json_decode($cms_data['text'],true);
                                $vod_data = $this->getConTent($res);
                                $is_log = true;
                                if (!empty($vod_data)) {
                                    $whereId = [];
                                    $whereId['vod_id'] = $v['vod_id'];
                                    if (isset($vod_data['vod_doucore'])) {
                                        unset($vod_data['vod_doucore']);
                                    }
                                    $up_res = $this->vodDb->where($whereId)->update($vod_data);
                                    if ($up_res) {
                                        log::info('合并cmsVodScore-succ::' . $v['vod_name']);
                                    }
                                }
                            }
                        }else{
                            if($type == 1){
                                $url = $this->get_search_id . $v['vod_douban_id'];
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
                                if (!empty($mac_curl_get_data)) {
                                    if (!empty($mac_curl_get_data) && $mac_curl_get_data['code'] == 1 && !empty($mac_curl_get_data['data'])) {
                                        $res = $mac_curl_get_data['data'];
                                        $whereId['vod_id'] = $v['vod_id'];
                                        $vod_data['vod_tag'] =  mac_format_text(trim($res['vod_class']));
                                        $up_res = $this->vodDb->where($whereId)->update($vod_data);
                                        if ($up_res) {
                                            log::info('合集CmsDoubasucc::' );
                                        }
                                    }
                                }
                            }
                        }
                        if ($is_log == false) {
                            log::info('合并cmsVodScore-过滤::' . $v['vod_name']);
                        }

                    }
                    $page = $page + 1;
                }
            }
        } catch (Exception $e) {
            $output->writeln("end.".$e);
            log::info('合并cmsVodScore-error::'.$e);
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
        $list = $this->vodDb->field('vod_id,vod_name,vod_class,vod_actor,vod_director,vod_douban_id,vod_douban_score')->where($where)->order($order)->limit($limit_str)->select();
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
//        if (isset($res['vod_duration'])) {
//            $vod_data['vod_duration'] = trim($res['vod_duration']);
//        }

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
    protected function ParSing($parameter)
    {
        $parameter_array = array();
        $arry = explode('#', $parameter);
        if(!empty($arry)){
            foreach ($arry as $key => $value) {
                $zzz = explode('=', $value);
                $parameter_array[$zzz[0]] = $zzz[1]??'';

            }
        }
        return $parameter_array;

    }


}