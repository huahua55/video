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

use GuzzleHttp\Client;

class RecomVodList extends Common{

    protected function configure(){
        $this->setName('RecomVodList')->addArgument('parameter')
            ->setDescription('定时计划：推荐短视频');
    }

    protected function execute(Input $input, Output $output){
        $output->writeln("定时计划:推荐短视频开始:");

        $recomDb  = model('Recom');


        $limit = 100;


        $countSql = "SELECT count(*) as n FROM `douban_vod_details` WHERE JSON_EXTRACT(trailer_urls,'$[0]') is not null";
        $count = Db::query($countSql);
        $count = $count[0]['n'];


        $page  = ceil($count / $limit);

        for($i = 0; $i < $page; $i++){
            $pageSize = $i * $limit;

            $listSql = "SELECT v.vod_id,v.vod_name as name,v.type_id_1 as type_id,v.vod_blurb,d.trailer_urls FROM `douban_vod_details` d JOIN vod v on d.douban_id = v.vod_douban_id LEFT JOIN recom r on r.vod_id = v.vod_id WHERE JSON_EXTRACT(trailer_urls,'$[0]') is not null AND r.id IS NULL limit " .$pageSize . "," . $limit;

            $list = Db::query($listSql);
            $data = [];
            foreach($list as $item){
                $json   = json_decode($item['trailer_urls'], true);
                $image  = $json[0]['medium'] ?? "";
                $url    = $json[0]['resource_url'] ?? "";

                $data[] = [
                    'vod_id'    => $item['vod_id'],
                    'name'      => $item['name'],
                    'type_id'   => $item['type_id'],
                    'image'     => $image,
                    'url'       => $url,
                    'sort'      => 0,
                    'intro'     => $item['vod_blurb'],
                    'create_time' => time(),
                ];
            }

            $recomDb->insertAll($data);
        }


        $output->writeln("定时计划:视频编码结束:");
    }



}