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
use Exception;

class CjUpdateTime extends Common
{
    protected $Collect = '';
    protected $vodDb;//db


    protected function configure()
    {
        $this->vodDb = Db::name('vod');
        $this->setName('cjUpdateTime')->addArgument('parameter')
            ->setDescription('定时计划：修改数据库的更新时间');
    }

    protected function execute(Input $input, Output $output)
    {


        // 输出到日志文件
        $output->writeln("CjUpdateTime:");
        $myparme = $input->getArguments();
        $parameter = $myparme['parameter'];
        //参数转义解析
        $param = $this->ParSing($parameter);
        $xtime = $param['xtime'] ?? '';

        $start = 0;
        $page = 1;
        $limit = 1000;
        $is_true = true;
        $year = date("Y");//年数
//        $times = date("Y-m-d");

        $where = [];
        $where['vod_douban_id'] = ['neq', 3];
        $where['vod_id'] = ['gt', 317360];

        $order = 'vod_id asc';
        //进入循环 取出数据
        while ($is_true) {
            //取出数据
//            usleep(50000);
            $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
            if (!empty($douBanScoreData)) {
                $pagecount = $douBanScoreData['pagecount'] ?? 0;
                if ($page > $pagecount) {
                    $is_true = false;
                    $output->writeln("结束...");
                    break;
                }
                foreach ($douBanScoreData['list'] as $k => $v) {
                    $upWhere = [];
                    if (strpos($v['vod_actor'], '更多...') !== false) {
                        $vod_actor = explode('更多...', $v['vod_actor']);
                        $upWhere['vod_actor'] = $vod_actor[0] ?? '';
                        unset($vod_actor);
                    }
                    $vod_blurb = mac_str_is_html($v['vod_blurb']);
                    if($vod_blurb != false){
                        $upWhere['vod_blurb'] = $vod_blurb;
                    }
                    $vod_content = mac_str_is_html($v['vod_content']);
                    if($vod_content != false){
                        $upWhere['vod_content'] = $vod_content;
                    }
//                    if (strpos($v['vod_reurl'], ',') !== false) {
//                        $upWhere['vod_reurl'] = str_replace(',', '/', $v['vod_reurl']);
//                    }
//                    //处理下分类
//                    if ($v['type_id_1'] == 0) {
//                        $v['type_id_1'] = $v['type_id'];
//                    }
//                    $vod_pubdate = false;
//                    if (strpos($v['vod_pubdate'], $year) !== false) {
//                        $vod_pubdate = true;
//                    }
//                    if(!empty($v['vod_year'])){
//                        $vod_year = $v['vod_year'];
//                        $vod_year_len =  strlen($vod_year);
//                        $year_len   = strlen($year);
//                        if($vod_year_len > $year_len){
//                            $vod_year =$upWhere['vod_year']= substr($vod_year,0,$year_len);
//                        }
//                        if($vod_year > $year){
//                            $upWhere['vod_year']= $year;
//                        }
//
//                    }
                    //编辑条数 电视剧和动漫
                    if($v['type_id_1'] == 0){
                        $v['type_id_1'] = getTypePid($v['type_id']);
//                        print_r(  $v['type_id_1']);die;
                    }
                    if($v['type_id_1'] == 2 || $v['type_id_1'] == 4){
                        $serial = mac_vod_remarks($v['vod_remarks'],$v['vod_total']);
                        if($serial > $v['vod_serial']){
                            $upWhere['vod_serial']= $serial;
                        }

                    }
                    //完结 或者 不是这一年 或者 上映时间
//                    if ($v['vod_isend'] == 1 || $year != $v['vod_year'] || $vod_pubdate != true || mac_vod_remarks_is_v($v['vod_remarks']) == true) {
//                        if ($v['type_id_1'] == 2 || $v['type_id_1'] == 4|| $v['type_id_1'] == 33) {
//                            if (!empty($v['vod_year'])) {
//                                $upWhere['vod_time'] = strtotime($v['vod_year'].'-01-01');
//                            }
//                            if (!empty($v['vod_pubdate'])) {
//                                $upWhere['vod_time'] = strtotime($v['vod_pubdate']);
//                            }
//                            $st_time =  date("Y-m-d",$v['vod_time']);
//                            if(strpos($st_time,$v['vod_year']) !== false){
//                                if(empty($v['vod_pubdate'])){
//                                    if(time() > $v['vod_time']){
//                                        $upWhere['vod_time'] = $v['vod_time'];
//                                    }
//                                }else{
//                                    $vod_pubdate_months =  strtotime("+3 months", strtotime($v['vod_pubdate']));
//                                    if($vod_pubdate_months > $v['vod_time']){
//                                        $upWhere['vod_time'] = $v['vod_time'];
//                                    }
//                                }
//                            }
//                        }
//                        if ($v['type_id_1'] == 1) {
//                            if (!empty($v['vod_year'])) {
//                                $upWhere['vod_time'] = strtotime($v['vod_year'].'-01-01');
//                            }
//                            if (!empty($v['vod_pubdate'])) {
//                                $upWhere['vod_time'] = strtotime($v['vod_pubdate']);
//                            }
//                            if (empty($upWhere['vod_time'])) {
//                                $upWhere['vod_time'] = $v['vod_time_add'];
//                            }
//                        }
//
//                        if ($v['type_id_1'] == 3) {
//                            if (!empty($v['vod_year'])) {
//                                $upWhere['vod_time'] = strtotime($v['vod_year'].'-01-01');
//                            }
//                            if (!empty($v['vod_pubdate'])) {
//                                $upWhere['vod_time'] = strtotime($v['vod_pubdate']);
//                            }
//                            $num_remarks = mac_vod_remarks($v['vod_remarks'], $v['vod_total']);
//                            if ($num_remarks != 0 && is_numeric($num_remarks) && strlen($num_remarks) > 4) {
//                                if (!empty($v['vod_year'])) {
//                                    $num_remarks = substr($num_remarks, -4);
//                                    $r_num_remarks = $v['vod_year'] . '-' . substr($num_remarks, 0, 2) . '-' . substr($num_remarks, -2);
////                                    if (empty($upWhere['vod_time'])) {
//                                    $upWhere['vod_time'] = strtotime($r_num_remarks);
////                                    }
//                                }
//                            }
//
//                            if (empty($upWhere['vod_time'])) {
//                                $upWhere['vod_time'] = $v['vod_time_add'];
//                            }
//                        }
//                    }
////                   }
//                    if(isset($upWhere['vod_time'])){
//                        if(strpos($upWhere['vod_time'],'-') !== false){
////                                $upWhere['vod_time'] = $v['vod_time_add'];
//                            unset($upWhere['vod_time']);
//                        }
//                    }
                    if(!empty($upWhere)){
                        try {
                            log::info('time-su::' . $v['vod_id']);
                            $this->vodDb->where(['vod_id'=>$v['vod_id']])->update($upWhere);
                        } catch (Exception $e) {
                            log::info('time-error::' . $e);
                            log::info('time-error::' . $upWhere);
                            $output->writeln("end.311---:" . $e);
                            $output->writeln("end.311---:" . $this->vodDb->getlastsql());
                        }
                    }
                }
            }
            $page = $page + 1;
            log::info('页码' . $page);
        }
        $output->writeln("CjUpdateTimeEd....");
    }


    // 取出数据豆瓣评分为空数据
    protected function getVodDoubanScoreData($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodDb->where($where)->count();
        $list = $this->vodDb->field('vod_id,type_id,vod_reurl,vod_content,type_id_1,vod_year,vod_remarks,vod_pubdate,vod_total,vod_serial,vod_tv,vod_weekday,vod_isend,vod_time,vod_time_add,vod_time_hits,vod_time_make,vod_plot,vod_blurb,vod_plot_name,vod_sub,vod_name,vod_class,vod_actor,vod_director,vod_douban_id,vod_douban_score')->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }


}