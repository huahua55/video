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
    protected $detailsDb;//db
    protected $get_mac_id = 'https://api.maccms.com/douban/?callback=douban&id=';//cms 通过id获取内容
    protected $get_feifei_id = 'https://api.daicuo.cc/douban/feifeicms/?id=';//cms 通过id获取内容
    protected $get_douban_id = 'https://api.douban.com/v2/movie/subject/%s?apikey=0df993c66c0c636e29ecbb5344252a4a';
    protected $ql;//querylist

    protected function configure()
    {
        $this->ql = QueryList::getInstance();
        //db  优化详情表数据
        $this->detailsDb = Db::name('douban_vod_details');
        $this->setName('cmsDouban')->addArgument('parameter')
            ->setDescription('优化：详情表');
    }
    // 取出详情表数据数据
    protected function getData($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;

        $total = $this->detailsDb->where($where)->count();
        $list = $this->detailsDb->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }


    //从豆瓣接口获取内容
    public function getDouBanApi($douban_id){
        $url = sprintf($this->get_douban_id, $douban_id);
        $this->get_zm_port();//开启芝麻代理
        $str_data = $this->getUrl($url);
        $get_url_data = array_pop(explode("\r\n", $str_data));
        $get_url_data = json_decode($get_url_data,true);
        //开启飞蚁代理
//        $this->getPortData();
//        $cookie = $this->newCookie($this->getCookie('',false));
//      $get_url_data =   $this->queryListUrl($this->ql,$url,$cookie,true);
        //不用代理
//        $cookie = $this->newCookie($this->getCookie('',false));
//        $get_url_data =  $this->queryListUrl( $this->ql ,$url,$cookie);
//        var_dump($this->add_whitelist('23.224.163.201'));die;
        if(!empty($get_url_data)){
            //获取名称
            $vod_data = $this->getDouBanApiData($get_url_data);
            $upDetails = [];
            $upDetails['text'] = json_encode($vod_data,true);
            $upDetails['type'] = 7;
            $upDetails['name'] = $upDetails['title'] = $vod_data['vod_name']??'';
            $upDetails['link'] = $vod_data['vod_reurl']??'';
            $upDetails['abstract'] = '';
            $upDetails['abstract_2'] = '';
            $upDetails['score'] =  $upDetails['rating_nums'] = $vod_data['vod_douban_score']??0;
            $upDetails['time'] = date("Y-m-d H:i:s",time());
            $upDetails['name_as'] =$vod_data['vod_sub']??'';
            $upDetails['vod_director'] =$vod_data['vod_director']??'';
            $upDetails['vod_actor'] =$vod_data['vod_actor']??'';
            $this-> upDetails($douban_id,$upDetails);
        }else{
            $this->getFeiFeiApi($douban_id);
        }
        return true;
    }

    //从飞飞接口获取内容
    public function getFeiFeiApi($douban_id){
        $url =$this->get_feifei_id. $douban_id;
        $get_url_data =  $this->queryListUrl( $this->ql ,$url,'');
//        $get_url_data = json_decode(mac_curl_get($url),true);
        if(isset($get_url_data['data']) && isset($get_url_data['status']) && $get_url_data['status'] == 200 && !empty($get_url_data['data'])){
            //获取飞飞采集内容
            $vod_data = $this->getFFApiData($get_url_data['data']);
            $upDetails = $this->getDetailPublic($vod_data);
            $upDetails['type'] = 8;
            $upDetails['vod_douban_id'] = $douban_id;
            $this-> upDetails($douban_id,$upDetails);
        }else{
            $this->getMacApi($douban_id);
        }
        return true;
    }
    //从mac接口获取内容
    public function getMacApi($douban_id){
//        sleep(1);
        $url = $this->get_mac_id. $douban_id;
        $get_url_data =  $this->queryListUrl($this->ql,$url,'',false,false);
        //        $get_url_data = json_decode(mac_curl_get($url),true);
        $get_url_data = str_replace('douban(', '', $get_url_data);
        $get_url_data = str_replace(');', '', $get_url_data);
        $get_url_data = $this->isJsonBool($get_url_data, true);
        if(isset($get_url_data['data']) && isset($get_url_data['code']) && $get_url_data['code'] == 1 && !empty($get_url_data['data'])){
            //获取飞飞采集内容
            $vod_data = $this->getMacApiData($get_url_data['data']);
            $upDetails = $this->getDetailPublic($vod_data);
            $upDetails['type'] = 9;
            $this-> upDetails($douban_id,$upDetails);
        }
        return true;
    }
    //修改详情表
    public function upDetails($douban_id,$data){
        $res =  $this->detailsDb->where(['douban_id'=>$douban_id])->update($data);
        if($res){
            log::info('优化：详情表-up-succ' .$res);
        }else{
            log::info('优化：详情表-up-error' .$res);
        }
    }

    protected function execute(Input $input, Output $output)
    {
        // 输出到日志文件
        $output->writeln("优化：详情表-开始");
        $myparme = $input->getArguments();
        $parameter = $myparme['parameter'];
        //参数转义解析
        $param = $this->ParSing($parameter);
        $type = $param['type'] ?? 1;
        $id = $param['id'] ?? '';
        try {
            $start = 0;
            $page = 1;
            $limit = 40;
            $is_true = true;
            $where = [];
//            $where['type'] = 1;
            $where['name'] = '';
            if (!empty($ids)) {
                $where['id'] = ['gt', $id];
            }
            $order = 'id asc';
            //进入循环 取出数据
            while ($is_true) {
                $douBanScoreData = $this->getData($where, $order, $page, $limit, $start);
                if (!empty($douBanScoreData)) {
                    log::info('优化：详情表-进入foreach');
                    $pagecount = $douBanScoreData['pagecount'] ?? 0;
                    if ($page > $pagecount) {
                        $is_true = false;
                        log::info('优化：详情表-结束...');
                        $output->writeln("结束...");
                        break;
                    }
                    foreach ($douBanScoreData['list'] as $k => $v) {

                        $douban_id =  $v['douban_id'];
                        if($type == 1 ){
                            $this->getDouBanApi($douban_id);//7
                        }else if($type ==2){
                            $this->getFeiFeiApi($douban_id);//8
                        }else if($type ==3){
                            $this->getMacApi($douban_id);//9
                        }
                    }
                    $page = $page + 1;
                }
            }
        } catch (Exception $e) {
            $output->writeln("end...");
            log::info('优化：' . $e);
        }
        $output->writeln("end....");
    }
}