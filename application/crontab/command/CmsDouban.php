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

class CmsDouban extends Common
{
    protected $vodDb;//db
//    protected $get_search_id = 'http://api.maccms.com/douban/?callback=douban&id=';//cms 通过id获取内容
    protected $get_search_id = 'https://api.daicuo.cc/douban/feifeicms/?id=';//cms 通过id获取内容
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
            $where['error_count'] = ['lt', 10];
            if(!empty($ids)){
                $where['id'] = ['gt', $ids];
            }
            if($type ==1){
                $order = 'id desc';
            }else{
                $order = 'id asc';
            }

            //进入循环 取出数据
            while ($is_true) {
                //取出数据
                $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
//                print_r($douBanScoreData);die;
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
//                        sleep(1);
                        $url = $this->get_search_id . $v['douban_id'];
                        log::info('采集CmsDoubanUrl:', $url);
                        try {

                            $mac_curl_get_data = $this->getCmsData($url);
                            Log::info('采集CmsDouban-try:');
                        } catch (Exception $e) {
                            Log::info('err--过滤' . $url);
                            continue;
                        }
//                     print_r($getSearchData);
                        if (!empty($mac_curl_get_data)) {
                            log::info('采集CmsDouban-try_su:',$mac_curl_get_data);
                            if(isset($mac_curl_get_data['status']) && $mac_curl_get_data['status'] == 200  && !empty($mac_curl_get_data['data'])){
                                log::info('采集CmsDouban-try_-su-::');
                                $resdata = $mac_curl_get_data['data'];
                                $res = $this->getFFConTent($resdata);
                                $is_log = true;
                                $is_error = true;
                                $whereId['id'] = $v['id'];
                                $vod_data['name'] = mac_characters_format($resdata['vod_name']);
                                $vod_data['name_as'] = $res['vod_sub']??'';
                                $vod_data['vod_director'] = $res['vod_director']??'';
                                $vod_data['vod_actor'] = $res['vod_actor']??'';
                                $vod_data['score'] = $res['vod_score']??'';
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
                        if ($is_error != true) {
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
            log::info('采集CmsDoubanUrl-error::'.$e);
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

}