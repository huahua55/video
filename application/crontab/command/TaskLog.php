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

class TaskLog extends Common
{
    protected $videoDb; // 视频表
    protected $videoCollectionDb; // 视频集表
    protected $videoSelectedDb; // 精选视频表
    protected $videoSelectedCollectionDb; // 精选视频集表


    protected function configure()
    {
        $this->setName('TaskLog')->addArgument('parameter')
            ->setDescription('任务：定时生成');
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
        $output->writeln("任务：定时生成:end...");
    }

}