<?php

namespace app\crontab\command;

use JonnyW\PhantomJs\Http\PdfRequest;
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

class VodCode extends Common
{
    protected $vodDb;//vodDb
    protected function configure()
    {
        //https://api.daicuo.cc/douban/feifeicms/?id=26759908
       // http://api.maccms.com/douban/?callback=douban&id=26759908
        $this->vodDb = Db::name('vod');     //db
        $this->setName('vodCode')->addArgument('parameter')
            ->setDescription('定时计划：视频编码');
    }

    protected function execute(Input $input, Output $output){

        $output->writeln("定时计划:视频编码开启:");
        $start = 0;
        $page = 1;
        $limit = 20;
        $is_true = true;
        $order = 'vod_id asc';
        $where['vod_id'] = ['gt',1];
        while ($is_true) {//进入循环 取出数据
            $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
            foreach ($douBanScoreData['list'] as $d_key => $d_val){
                log::info('视频编码开启-list');
              $vod_play_from_data  =  explode('$$$',$d_val['vod_play_from']);
//              $vod_play_server_data  =  explode('$$$',$d_val['vod_play_server']);
//              $vod_play_note_data  =  explode('$$$',$d_val['vod_play_note']);
              $vod_play_url_data  =  explode('$$$',$d_val['vod_play_url']);
              foreach ($vod_play_from_data as $vod_play_from_key => $vod_play_from_val){
                  if(strpos($vod_play_from_val,'m3u8') !== false){ //存在
                      log::info('视频编码开启-播放器-存在m3u8');
                      if(isset($vod_play_url_data[$vod_play_from_key])){//存在
                          log::info('视频编码开启-播放器-视频存在m3u8');
                          $vod_play_url_key_data_str =  $vod_play_url_data[$vod_play_from_key];//取出值
                          $vod_play_url_key_data = explode('#',$vod_play_url_key_data_str);//取出来一共有-多少集
                          foreach ($vod_play_url_key_data as $vod_play_url_key_url_key => $vod_play_url_key_url_val){
                              log::info('视频编码开启-播放器-视频-循环开启');
                              $vod_play_url_key_url_val_str  =  explode('$',$vod_play_url_key_url_val);
                              if(isset($vod_play_url_key_url_val_str[1])){
                                  log::info('视频编码开启-url-存在');
                                  $vod_play_url_key_url_val = $vod_play_url_key_url_val_str[1];
                              }
                              log::info('视频编码开启-url-'.$vod_play_url_key_url_val);
                              if($vod_play_from_val == 'mbckm3u8'){
                                  log::info('视频编码开启-'.$vod_play_from_val.'-存在');
                                  $vod_play_url_key_url_val_text = mac_curl_get($vod_play_url_key_url_val);
                                  $vod_play_url_key_url_val_text =   explode("\n",$vod_play_url_key_url_val_text);
                                  $vod_play_url_key_url_val_text_url =  $vod_play_url_key_url_val_text[2]??'';
                                  $vod_play_url_key_url_val_text_url =str_replace('index.m3u8',$vod_play_url_key_url_val_text_url,$vod_play_url_key_url_val);
                                  var_dump($vod_play_url_key_url_val);
                                  if(strpos($vod_play_url_key_url_val_text_url,'http')!==false && strpos($vod_play_url_key_url_val_text_url,'m3u8')!== false ){
                                      log::info('视频编码开启-'.$vod_play_from_val.'-存在http-m3u8');
                                      $get_vod_ts = mac_curl_get($vod_play_url_key_url_val_text_url);//获取ts
                                      if(strpos($get_vod_ts,'ts')!== false){
                                          log::info('视频编码开启--'.$vod_play_from_val.'-存在ts');
                                          $vod_ts = explode("\n",$get_vod_ts);
                                          $t_url_str= '';
                                          $FfmpegEncryptionText= '';
                                          if(!empty($vod_ts)){
                                              foreach ($vod_ts as $t_key => $t_val){
                                                 if(strpos($t_val,'ts') != false){
                                                     $t_url_str = $t_val;
                                                     $FfmpegEncryptionText .='#EXT-X-ENDLIST'."\r\n";
                                                     break;
                                                 }else{
                                                     $FfmpegEncryptionText .=$t_val."\r\n";
                                                 }
                                              }
                                          }
                                          print_r($FfmpegEncryptionText);die;
                                          if(!empty($t_url_str)){
                                              log::info('视频编码开启存在--'.$t_url_str.'-存在ts');
                                              $vod_play_url_key_url_val_text_ts_url = str_replace('index.m3u8',$t_url_str,$vod_play_url_key_url_val_text_url);
                                              //type_id_1 type_id id 播放器 集 5dsHHdzp1025000.ts
                                              $ts_path_dir =ROOT_PATH.'static'.DS.'vod_ts'.DS.$d_val['type_id_1'].DS.$d_val['type_id'].DS.$d_val['vod_id'].DS.$vod_play_from_val.DS.findNum($vod_play_url_key_url_val_str[0]);
                                              $ts_path =$ts_path_dir.DS.$t_url_str;//ts文件
                                              $key_path =$ts_path_dir.DS.'key.key';//key
                                              $index1_m3u8_path =$ts_path_dir.DS.'index1.m3u8';//第一步
                                              $index2_m3u8_path =$ts_path_dir.DS.'index2.m3u8';//第二步
                                              $index_last_m3u8_path =$ts_path_dir.DS.'index_last.m3u8';//第三步
                                              $vod_play_url_key_url_val_text_ts_key_key_url = str_replace('index.m3u8','key.key',$vod_play_url_key_url_val_text_url);
                                              mac_mkdirss($ts_path_dir);//创建目录
                                              //下载 index1.mou8
                                              $this->get_list_vod($vod_play_url_key_url_val,$index1_m3u8_path);
                                              //下载 index2.mou8
                                              $this->get_list_vod($vod_play_url_key_url_val_text_url,$index2_m3u8_path);
                                              //下载ts
                                              $this->get_list_vod($vod_play_url_key_url_val_text_ts_url,$ts_path);
                                              //下载key
                                              $this->get_list_vod($vod_play_url_key_url_val_text_ts_key_key_url,$key_path);
                                              //获取top ts 为了转码
                                              $top_index_text = $this->getFfmpegEncryptionText($key_path,$ts_path);
                                              $this->get_list_vod(null,$index_last_m3u8_path,$top_index_text);
                                              //ffmpeg -allowed_extensions ALL -protocol_whitelist "file,http,crypto,tcp" -i  /Users/zongbozhu/Downloads/wwwroot/php/qdhy/sp1/video/static/vod_ts/2/16/43/mbckm3u8/01/index_last.m3u8 -c copy /Users/zongbozhu/Downloads/wwwroot/php/qdhy/sp1/video/static/vod_ts/2/16/43/mbckm3u8/01/a.ts -loglevel verbose


                                              var_dump($ts_path_dir);
                                              print_r($vod_play_url_key_url_val_text_ts_url);die;

//                                              move_uploaded_file();
                                              print_r($vod_play_url_key_url_val_text_ts_url);die;
                                          }
                                         p(1);

                                      }

                                      print_r($get_vod_ts);

                                  }

                                  v($vod_play_url_key_url_val_text_url);
                              }
                              //https://cdn.mb33.vip/20191029/S80XQGTn/1200kb/hls/index.m3u8
                              // "https://cdn.mb33.vip/20191029/S80XQGTn/index.m3u8"
//                              1200kb/hls/index.m3u8
                              var_dump($d_val['vod_name']);
                              var_dump($vod_play_from_val);
                              v($vod_play_url_key_url_val);
                          }
                      }
                  }
              }



              $vod_play_url_data  =  explode('$$$',$d_val['vod_play_url']);
//                v($d_val['vod_play_url']);
//                var_dump($vod_play_from_data);die;
//                var_dump($vod_play_server_data);
//                var_dump($vod_play_note_data);
                var_dump($vod_play_url_data);
                p(1);
            }
            p($douBanScoreData);




        }

        p(2);



        $output->writeln("定时计划:视频编码结束:");
    }




    //获取top index
    public function getFfmpegEncryptionText($key,$ts){
        $FfmpegEncryptionText = '#EXTM3U
#EXT-X-TARGETDURATION:10
#EXT-X-MEDIA-SEQUENCE:0
#EXT-X-KEY:METHOD=AES-128,URI="%s"
%s
#EXT-X-ENDLIST
';
        return sprintf($FfmpegEncryptionText,$key,$ts);

    }


    //下载到本地
    public function get_list_vod($url = null,$path = '/',$text = ''){
        if(!empty($text)){
            $vod_ts_data = $text;
        }else{
            $vod_ts_data = mac_curl_get($url);
        }
        $fp2 = @fopen($path, 'w');
        fwrite($fp2, $vod_ts_data);
        fclose($fp2);
    }
    // 取出数据豆瓣评分为空数据
    protected function getVodDoubanScoreData($where, $order, $page, $limit, $start)
    {
           //`vod_play_from`    '播放组',
           //`vod_play_server`  '播放服务器组',
           // `vod_play_note`   '播放备注',
           //`vod_play_url`     播放地址',
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodDb->where($where)->count();
        $list = $this->vodDb->field('vod_id,type_id_1,type_id,vod_name,vod_play_from,vod_play_server,vod_play_note,vod_play_url')->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }
}