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

class CmsVodScore extends Common
{
    protected $vodDb;//db
    protected $cmsDb;//db

    protected function configure()
    {
        //db 从cms表中导入数据
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

            $type = $param['type'] ?? '';
            $id = $param['id'] ?? '';

            $start = 0;
            $page = 1;
            $limit = 20;
            $is_true = true;
            $where = [];
            if ($type == 1) {
                $where['vod_douban_id'] = ['gt', 0];
            } else {
                $where['vod_douban_id'] = 0;
            }
            if (!empty($id)) {
                $where['vod_douban_id'] = ['gt', $id];
            }
            $cha_id= Cache::get('cms_vod_id_puth');
            if(empty($cha_id)){
                Cache::set('cms_vod_id_puth',1);
                $where['vod_id'] = ['gt', 1];
            }else{
                $where['vod_id'] = ['gt', $cha_id];
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
                        Cache::set('cms_vod_id_puth',$v['vod_id']);
                        log::info('合并cmsVodScore进入foreach', $v['vod_name']);
                        $is_log = false;
                        $cms_where['name'] = ['neq', ''];
                        $cms_where['douban_id'] = ['gt', 0];
                        if ($type == 1) {
                            $cms_where['douban_id'] = $v['vod_douban_id'];
                            $cms_data = $this->cmsDb->where($cms_where)->find();
                        } else {
                            $cms_data = [];
                            //第一次匹配
                            $vod_name = mac_trim_all(mac_characters_format($v['vod_name']));
                            $vod_sub = mac_trim_all(mac_characters_format($v['vod_sub']));
                            $cms_where1['name'] = array(array('like', "%" . $vod_name . "%"), array('like', "%" . $vod_sub . "%"), 'or');
//                            $cms_where1['name_as'] = array(array('like', "%" . $vod_name . "%"), array('like', "%" . $vod_sub . "%"), 'or');
                            if(empty($v['vod_director'])){
                                continue;
                            }
                            $cms_where['vod_director'] = $v['vod_director'];

//                            var_dump($v['vod_actor']);
//                            var_dump($v['vod_director']);
                            $cms_data_array = $this->cmsDb->where($cms_where)->whereOr($cms_where1)->select();
                            foreach ($cms_data_array as $cda_k => $cda_v) {
                                $cda_name = mac_trim_all(mac_characters_format($cda_v['name']));
                                var_dump($vod_name . '---' . $cda_name);
                                $cda_v_data = json_decode($cda_v['text'], true);
                                if (mac_trim_all($cda_v['vod_director']) != mac_trim_all($v['vod_director'])) {
                                    continue;
                                }
                                $vod_actor_rade = mac_intersect(mac_trim_all($cda_v['vod_actor']), mac_trim_all($v['vod_actor']));
                                $rade = $lcs->getSimilar($vod_name, $cda_name) * 100;
                                if ($vod_actor_rade < 85 && $rade < 95) {
                                    continue;
                                }
                                if (($rade == 100 || $rade > 95 || $vod_actor_rade > 90) && mac_trim_all($cda_v['vod_director']) == mac_trim_all($v['vod_director'])) {
                                    if (!empty($v['vod_year']) && isset($cda_v_data['vod_year'])) {
                                        if ($v['vod_year'] == $cda_v_data['vod_year']) {
                                            $cms_data = $cda_v;
                                            break;
                                        }
                                    }else{
                                        $cms_data = $cda_v;
                                        break;
                                    }
                                }
                            }
                        }
                        if (!empty($cms_data)) {
                            log::info('合并cmsVodScore豆瓣评分-su-::');
                            $vod_data = json_decode($cms_data['text'], true);
                            $is_log = true;
                            if (!empty($vod_data)) {
                                $whereId = [];
                                $whereId['vod_id'] = $v['vod_id'];
                                if (isset($vod_data['vod_name'])) {
                                    unset($vod_data['vod_name']);
                                }
                                var_dump('info----------------'.$whereId['vod_id']);
                                $up_res = $this->vodDb->where($whereId)->update($vod_data);
                                if ($up_res) {
                                    log::info('合并cmsVodScore-succ::' . $v['vod_name']);
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
            $output->writeln("end." . $e);
            log::info('合并cmsVodScore-error::' . $e);
        }
        $output->writeln("end....");
    }

    // 取出数据豆瓣评分为空数据
    protected function getVodDoubanScoreData($where, $order, $page, $limit, $start)
    {
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodDb->where($where)->count();
        $list = $this->vodDb->field('vod_id,vod_year,vod_name,vod_class,vod_actor,vod_director,vod_douban_id,vod_douban_score')->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }

}