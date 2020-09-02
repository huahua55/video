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
use Exception;

class Task extends Common
{
    protected $videoDb; // 视频表
    protected $videoCollectionDb; // 视频集表
    protected $videoSelectedDb; // 精选视频表
    protected $videoSelectedCollectionDb; // 精选视频集表


    protected function configure()
    {
        $this->setName('Task')->addArgument('parameter')
            ->setDescription('任务：定时生成');
    }
    public function getAdminRoletextAttr($t = '')
    {
        $admin_id = Db::name('admin_role')->field('admin_id')->where(['role_id' => 3])->column('admin_id');
        $arr = Db::name('admin')->where(['admin_status' => 1])->whereIn('admin_id', $admin_id)->column('admin_name', 'admin_id');
        if ($t != '') {
            return $arr[$t];
        } else {
            return $arr;
        }
    }
    public function date_log($orby,$total_sum)
    {
        $log_sql = "select v.vod_id, v.id as video_id,v.vod_status as video_status,v.is_examine as video_is_examine,v.vod_name,vc.task_id as video_vod_id,vc.collection,vc.id as collection_id,vc.`status` as collection_status,vc.is_examine as collection_is_examine from video as v  RIGHT JOIN video_collection as vc on v.id = vc.video_id  where vc.video_id in (select tg.id from (SELECT vv.id FROM `task_log` RIGHT  JOIN `video` `vv` ON `vv`.`id`=`task_log`.`video_id` WHERE task_log.id IS Null  GROUP BY vv.id ORDER BY ".$orby." LIMIT " . $total_sum . ") as tg)";
        return Db::query($log_sql);
    }
    protected function execute(Input $input, Output $output)
    {

        $sql = "select v.vod_id, v.id as video_id,v.vod_status as video_status,v.is_examine as video_is_examine,v.vod_name,vc.task_id as video_vod_id,vc.collection,vc.id as collection_id,vc.`status` as collection_status,vc.is_examine as collection_is_examine from video as v  RIGHT JOIN video_collection as vc on v.id = vc.video_id  where vc.id in (select tgg.id from (SELECT vv.id FROM `task_log` RIGHT JOIN `video_collection` `vv` ON `vv`.`id`=`task_log`.`collection_id` WHERE task_log.collection_id IS Null and  vv.video_id in (select tg.video_id from (SELECT vv.video_id FROM `task_log` left JOIN `video_collection` `vv` ON `vv`.`video_id`=`task_log`.`video_id`  GROUP BY vv.video_id) as tg) GROUP BY vv.id) as tgg)";
        $sql_er= Db::query($sql);
        $new_sql_er= array_unique(array_column($sql_er,'video_id'));
        foreach ($new_sql_er as $sqlk=>$sqlv){
            $res_ar = Db::name('task_log')->where(['video_id'=>$sqlv])->find();
            if($res_ar){
                foreach ($sql_er as $sql_erk=>$sql_erv){
                    if($sqlv != $sql_erv['video_id']){
                        continue;
                    }else{
                        $sql_erv['task_id'] = $res_ar['task_id'];
                        $sql_erv['add_type'] = $res_ar['add_type'];
                        $sql_erv['task_date'] = $res_ar['task_date'];
                        $sql_erv['ad_user_id'] = $res_ar['ad_user_id'];
                        $sql_erv['task_type'] = $res_ar['task_type']??2;
                        $sql_erv['status'] = 0;
                        $sql_erv['add_time'] = time();
                        $sql_erv['log'] = '添加';
//                        p($sql_erv);
                        $res = Db::name('task_log')->insert($sql_erv);
                    }
                }
            }
        }

        p($sql_er);

        // 输出到日志文件
        $output->writeln("任务：定时生成:start...");
        $where['task_date']=date("Y-m-d");
        $list = Db::name('task')->where($where)->select();
        foreach ($list as $k=>$v){
            $up_where['id'] = $v['id'];
            if($v['success_sum']>=$v['total_sum']){
                $up_date['status'] = 1;
            }else{
                $up_date['status'] = 0;
            }
            Db::name('task')->where($up_where)->update($up_where);
        }
        $user_data = $this->getAdminRoletextAttr();
        foreach ($user_data as $uk=>$uv){

            $info['add_type'] = 1;
            $orby = "vv.vod_year";
            if($info['add_type'] == 1){
                $orby = "vv.vod_year";
            }elseif ($info['add_type'] == 2){
                $orby = "vv.vod_time_auto_up";
            }elseif ($info['add_type'] == 3){
                $orby = "vv.vod_time_add";
            }elseif ($info['add_type'] == 4){
                $orby = "vv.vod_douban_score";
            }
            $date_log_list = $this->date_log($orby,100);
            $total_sum = count(array_unique(array_column($date_log_list,'vod_id')));
            $info['ad_user_id'] = $uk;
            $info['status'] = 0;

            $info['total_sum'] = $total_sum;
            $info['task_type'] = 1;
            $info['task_date'] = date("Y-m-d", time());
            $info['admin_id'] = 2;
            $info['add_time'] = time();
            $info['success_sum'] =0;
            Db::startTrans();

            try {
                $res_id = Db::name('task')->insertGetId($info);
                foreach ($date_log_list as $k => $v) {
                    $date_log_list[$k]['task_id'] = $res_id;
                    $date_log_list[$k]['add_type'] = $info['add_type'];
                    $date_log_list[$k]['task_date'] = $info['task_date'];
                    $date_log_list[$k]['ad_user_id'] = $info['ad_user_id'];
                    $date_log_list[$k]['task_type'] = $info['task_type']??2;
                    $date_log_list[$k]['status'] = $info['status']??0;
                    $date_log_list[$k]['add_time'] = time();
                    $date_log_list[$k]['log'] = '添加';
                    $res = Db::name('task_log')->insert($date_log_list[$k]);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }
        }

        $output->writeln("任务：定时生成:end...");
    }

}