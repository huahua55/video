<?php

namespace app\crontab\command;

use similar_text\similarText;
use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Log;

use Exception;


class EditVod extends Common
{
    protected $vodDb;//db
    protected $detailsDb;//db
    protected $ql;//querylist
    protected $type;//day

    protected function configure()
    {
        //db
        $this->vodDb = Db::name('vod');
        $this->detailsDb = Db::name('douban_vod_details');
        //获取豆瓣id
        $this->setName('editVod')->addArgument('parameter')
            ->setDescription('定时计划：修改视频表');
    }

    protected function execute(Input $input, Output $output)
    {

        // 输出到日志文件
        $output->writeln("定时计划：修改视频表");
        try {
            //cli模式接受参数
            $myparme = $input->getArguments();
            $parameter = $myparme['parameter'];
            //参数转义解析
            $param = $this->ParSing($parameter);
            $d_type = $param['d_type']??'';
            if($d_type == 1){//详情表优化
               $this->up_vod_details();
               $output->writeln("out");
               exit('完结');
            }
            $this->type = $param['type']??'';
            $ids = $param['id']??'';


            $start = 0;
            $page = 1;
            $limit = 40;
            $is_true = true;
            $where = [];


            $where['vod_id'] = ['gt', 1];
            if(!empty($ids)){
                $where['vod_id'] = ['gt', $ids];
            }

            $order = 'vod_id asc';
//            $where['vod_name'] = array(
//                ['like', "%[%"],
//                ['like',"%]%"],
//                ['like',"%【%"],
//                ['like',"%】%"],
//                ['like',"%（%"],
//                ['like',"%）%"],
//                ['like',"%(%"],
//                ['like',"%)%"],
//                ['like',"% %"]
//            ,'or');

            //进入循环 取出数据
            while ($is_true) {
                //取出数据
                $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
                if (!empty($douBanScoreData)) {
                    log::info('修改视频表进入foreach');
                    $pagecount = $douBanScoreData['pagecount'] ?? 0;
                    if ($page > $pagecount) {
                        $is_true = false;
                        log::info('修改视频表结束...');
                        $output->writeln("结束...");
                        break;
                    }
                    foreach ($douBanScoreData['list'] as $k => $v) {

                        $tid = intval($v['type_id_1']);
                        $update=[];
                        if($tid == 2){
                            //获取集数
                            $vod_serial =   mac_vod_remarks($v['vod_remarks'], $v['vod_total']);
                            if($vod_serial > $v['vod_serial']){
                                $update['vod_serial'] =  $vod_serial;
                            }
                        }
                        $name = mac_characters_format($v['vod_name']);
                        if((strpos($v['vod_name'],'[') !== false || strpos($v['vod_name'],'【') !== false || strpos($v['vod_name'],'（') !== false || strpos($v['vod_name'],'(') !== false || strpos($v['vod_name'],' ') !== false) || ($name !=$v['vod_name'])  ){
                            $update['vod_name']  =$name;
                        }
                        if(strpos($v['vod_director'],'/') !== false ){
                            $update['vod_director']  =str_replace('/',',',$v['vod_director']);
                        }
                        if(strpos($v['vod_actor'],'/') !== false ){
                            $update['vod_actor']  =str_replace('/',',',$v['vod_actor']);
                        }
                        if(strpos($v['vod_class'],'/') !== false ){
                            $update['vod_class']  =str_replace('/',',',$v['vod_class']);
                        }
                        if(strpos($v['vod_sub'],'/') !== false ){
                            $update['vod_sub']  =str_replace('/',',',$v['vod_sub']);
                        }
                        if(strpos($v['vod_writer'],'/') !== false ){
                            $update['vod_writer']  =str_replace('/',',',$v['vod_writer']);
                        }
                        if(!empty($update)){
                            log::info('修改update::-'.$v['vod_id'].'-'.$v['vod_name'].'-'.json_encode($update,true));
                            $res =$this->vodDb->where(['vod_id'=>$v['vod_id']])->update($update);
                            if ($res > 1){
                                log::info('修改成功');
                            }else{
                                log::info('修改err----'.$v['vod_id']);
//                                $this->vodDb->where(['vod_id'=>$v['vod_id']])->save(['vod_status'=>0]);
//                                $isWhere['vod_director'] = $update['vod_director'];
//                                $isWhere['vod_name'] =  [['eq', $update['vod_name']],['eq', $v['vod_name']],'or'];
//                                $findData =  $this->vodDb->field('vod_id')->where($isWhere)->select();
//                                $findData = array_flip(array_unique(array_column($findData,'vod_id')));
//                                if(count($findData) >= 2){
//                                    if(isset($findData[$v['vod_id']])){
////                                        $this->vodDb->where(['vod_id'=>$v['vod_id']])->delete();
//                                        log::info('修改删除update::-'.$v['vod_id']);
//                                    }
//                                }
                            }
                        }
                    }
                    $page = $page + 1;
                }
            }
            $sql ='DELETE FROM vod WHERE vod_id IN (SELECT vid FROM ( SELECT MAX( vod_id ) AS vid FROM vod WHERE vod_id > 0 GROUP BY vod_name,vod_director HAVING count( vod_id ) > 1 ) a)';
            $res = Db::execute($sql);
            if($res){
                log::info('delete');
            }
        } catch (Exception $e) {
            $output->writeln("end....");
            log::info('修改error::'.$e);
        }

        $output->writeln("end....");
    }



    // 取出数据豆瓣详情数据
    protected function getVodDoubanData($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        if( $this->type == 1){ //
            $total = $this->detailsDb->where($where)->whereTime('vod_time','today')->count();
            $list = $this->detailsDb->where($where)->whereTime('vod_time','today')->order($order)->limit($limit_str)->select();
        }else{
            $total = $this->detailsDb->where($where)->count();
            $list = $this->detailsDb->where($where)->order($order)->limit($limit_str)->select();
        }
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }

    // 取出数据豆瓣评分为空数据
    protected function getVodDoubanScoreData($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        if( $this->type == 1){ //
            $total = $this->vodDb->where($where)->whereTime('vod_time','today')->count();
            $list = $this->vodDb->where($where)->whereTime('vod_time','today')->order($order)->limit($limit_str)->select();
        }else{
            $total = $this->vodDb->where($where)->count();
            $list = $this->vodDb->where($where)->order($order)->limit($limit_str)->select();
        }
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }


    //update
    public function up_vod_details(){
        $start = 0;
        $page = 1;
        $limit = 40;
        $is_true = true;
        $where = [];
        $where['id'] = ['gt', 0];
        if(!empty($ids)){
            $where['id'] = ['gt', $ids];
        }

        $order = 'id asc';
        //进入循环 取出数据
        while ($is_true) {
            //取出数据
            $douBanScoreData = $this->getVodDoubanData($where, $order, $page, $limit, $start);
            if (!empty($douBanScoreData)) {
                $pagecount = $douBanScoreData['pagecount'] ?? 0;
                if ($page > $pagecount) {
                    $is_true = false;
                    log::info('结束...');
                    break;
                }
                foreach ($douBanScoreData['list'] as $k => $v) {
                    $name = mac_characters_format($v['name']);
                    if((strpos($v['name'],'[') !== false || strpos($v['name'],'【') !== false || strpos($v['name'],'（') !== false || strpos($v['name'],'(') !== false || strpos($v['name'],' ') !== false) || ($name !=$v['name'])  ){
                        $update['name']  =$name;
                    }
                    if(strpos($v['vod_director'],'/') !== false ){
                        $update['vod_director']  =str_replace('/',',',$v['vod_director']);
                    }
                    $v['vod_actor']  =str_replace('更多...','',$v['vod_actor']);
                    if(strpos($v['vod_actor'],'/') !== false ){
                        $update['vod_actor']  =str_replace('/',',',$v['vod_actor']);
                    }
                    if(strpos($v['name_as'],'/') !== false ){
                        $update['name_as']  =str_replace('/',',',$v['name_as']);
                    }

                    $text = json_decode($v['text'],true);
                    foreach ($text as $t_k=>$t_v){
                        if(strpos($text[$t_k],'/') !== false ){
                            $text[$t_k]  =str_replace('/',',',$text[$t_k]);
                        }
                    }
                    $update['text'] = json_encode($text,true);
                    if(!empty($update)){
                        log::info('修改update::-'.$v['id'].'-'.'-');
                        $res =$this->detailsDb->where(['id'=>$v['id']])->update($update);
                        if ($res > 1){
                            log::info('修改成功');
                        }else{
                            log::info('修改err----'.$v['id']);
                        }
                    }
                }
                $page = $page + 1;
            }
        }
    }




}