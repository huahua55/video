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


class EditVod extends Command
{
    protected $vodDb;//db
    protected $ql;//querylist
    protected $type;//day

    protected function configure()
    {
        //db
        $this->vodDb = Db::name('vod');
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
            $this->type = $param['type']??'';
            $ids = $param['id']??'';

            $start = 0;
            $page = 1;
            $limit = 20;
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
                            if(strpos($v['vod_remarks'],'完结') !== false){
                                $update['vod_serial'] = $v['vod_total'];
                            }elseif (strpos($v['vod_remarks'],'集全') !== false){
                                $update['vod_serial'] = $v['vod_total'];
                            }elseif (strpos($v['vod_remarks'],'集(全)') !== false){
                                $update['vod_serial'] = $v['vod_total'];
                            }elseif (strpos($v['vod_remarks'],'全集') !== false){
                                $update['vod_serial'] = $v['vod_total'];
                            }
                            elseif (strpos($v['vod_remarks'],'大结局') !== false){
                                $update['vod_serial'] = $v['vod_total'];
                            }else{
                                $vod_serial =findNum($v['vod_remarks']);
                                $vod_serial = empty($vod_serial)?0:$vod_serial;
                                $update['vod_serial'] = $vod_serial =   max($vod_serial,$v['vod_serial']);
                            }
                        }
                        if(strpos($v['vod_name'],'[') !== false || strpos($v['vod_name'],'【') !== false || strpos($v['vod_name'],'（') !== false || strpos($v['vod_name'],'(') !== false || strpos($v['vod_name'],' ') !== false){
                             $vod_name = $v['vod_name'];
                             $vod_name =str_replace('[','',$vod_name);
                             $vod_name =str_replace(']','',$vod_name);
                             $vod_name =str_replace('【','',$vod_name);
                             $vod_name =str_replace('】','',$vod_name);
                             $vod_name =str_replace('（','',$vod_name);
                             $vod_name =str_replace('）','',$vod_name);
                             $vod_name =str_replace('(','',$vod_name);
                             $vod_name =str_replace(')','',$vod_name);
                             preg_match_all('/[\x7f-\xff]+[ ]/',$vod_name,$matches);
                             if(!empty($matches) && isset($matches[0])){
                                foreach ($matches[0] as $m_k => $m_v) {
                                    $vod_name = str_replace($m_v,str_replace(' ','',$m_v),$vod_name);

                                }
                            }
                             $update['vod_name'] = $vod_name;
                        }
                        if(!empty($update)){
                            log::info('修改update::-'.$v['vod_id'].'-'.$v['vod_name'].'-'.json_encode($update,true));
                            $res =$this->vodDb->where(['vod_id'=>$v['vod_id']])->update($update);
                            if($res){
                                log::info('修改成功');
                            }else{
//                                $isWhere['vod_director'] = $update['vod_director'];
                                $isWhere['vod_name'] =  [['eq', $update['vod_name']],['eq', $v['vod_name']],'or'];
                                $findData =  $this->vodDb->field('vod_id')->where($isWhere)->select();
                                $findData = array_flip(array_unique(array_column($findData,'vod_id')));
                                if(count($findData) >= 2){
                                    if(isset($findData[$v['vod_id']])){
//                                        $this->vodDb->where(['vod_id'=>$v['vod_id']])->delete();
                                        log::info('修改删除update::-'.$v['vod_id']);
                                    }
                                }
                            }
                        }
                    }
                    $page = $page + 1;
                }
            }
            $sql ='DELETE FROM vod WHERE vod_id IN (SELECT vid FROM ( SELECT MAX( vod_id ) AS vid FROM vod WHERE vod_id > 0 GROUP BY vod_name, vod_director HAVING count( vod_id ) > 1 ) a)';
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


    protected function ParSing($parameter)
    {
        $parameter_array = array();
        $arry = explode('#', $parameter);
        foreach ($arry as $key => $value) {
            $zzz = explode('=', $value);
            $parameter_array[$zzz[0]] = $zzz[1]??'';

        }
        return $parameter_array;

    }


}