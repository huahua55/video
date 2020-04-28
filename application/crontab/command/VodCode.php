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
    protected $powerDb;//vodDb
    protected $ffmpeg;//ffmpeg

    protected function configure()
    {
        //https://api.daicuo.cc/douban/feifeicms/?id=26759908
        // http://api.maccms.com/douban/?callback=douban&id=26759908
        $this->vodDb = Db::name('vod');     //db
        $this->powerDb = Db::name('vod_resolving_power');     //db
        $this->setName('vodCode')->addArgument('parameter')
            ->setDescription('定时计划：视频编码');
    }

    protected function execute(Input $input, Output $output)
    {

        $output->writeln("定时计划:视频编码开启:");
        $myparme = $input->getArguments();
        $parameter = $myparme['parameter'];
        //参数转义解析
        $param = $this->ParSing($parameter);
        $type = $param['type'] ?? ''; //从1 开始爬取
        $id = $param['id'] ?? ''; //从1 开始爬取
        $f = $param['f'] ?? ''; //从1 开始爬取
        if($f == 'mac'){
            $this->ffmpeg = '/usr/local/Cellar/ffmpeg/4.2.2_2/bin/ffmpeg';
        }else{
            $this->ffmpeg = '/usr/bin/ffmpeg';
        }

        //编辑
        $vod_id = $this->powerDb->field('vod_id')->order('vod_id desc')->find();
        if (!empty($type) && $type == 1) {
            Cache::set('vod_resolving_power_id', 1);
        }
        $is_vod_id = Cache::get('vod_id_list_douban_score');
        if(!empty($id)){
            $where['vod_id'] = ['gt', $id];
        }else{
            if (!empty($is_vod_id)) {
                $where['vod_id'] = ['gt', $is_vod_id];
            }
        }
        if(!empty($vod_id)){
            $vod_id =  $vod_id['vod_id']??1;
            $where['vod_id'] = ['gt', $vod_id];
        }
        $start = 0;
        $page = 1;
        $limit = 20;
        $is_true = true;
        $order = 'vod_id asc';
        $where['vod_id'] = ['gt', 1];
        while ($is_true) {//进入循环 取出数据
            $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start);
            foreach ($douBanScoreData['list'] as $d_key => $d_val) {
                Cache::set('vod_id_list_douban_score', $d_val['vod_id']);
                log::info('视频编码开启-list');
                $vod_play_from_data = explode('$$$', $d_val['vod_play_from']);
//              $vod_play_server_data  =  explode('$$$',$d_val['vod_play_server']);
//              $vod_play_note_data  =  explode('$$$',$d_val['vod_play_note']);
                $vod_play_url_data = explode('$$$', $d_val['vod_play_url']);
                foreach ($vod_play_from_data as $vod_play_from_key => $vod_play_from_val) {
                    if (strpos($vod_play_from_val, 'm3u8') !== false) { //存在
                        log::info('视频编码开启-播放器-存在m3u8');
                        if (isset($vod_play_url_data[$vod_play_from_key])) {//存在
                            log::info('视频编码开启-播放器-视频存在m3u8');
                            $vod_play_url_key_data_str = $vod_play_url_data[$vod_play_from_key];//取出值
                            $vod_play_url_key_data = explode('#', $vod_play_url_key_data_str);//取出来一共有-多少集
                            foreach ($vod_play_url_key_data as $vod_play_url_key_url_key => $vod_play_url_key_url_val) {
                                $is_encryption = false;//是否有key
                                log::info('视频编码开启-播放器-视频-循环开启');
                                $vod_play_url_key_url_val_str = explode('$', $vod_play_url_key_url_val);
                                if (isset($vod_play_url_key_url_val_str[1])) {
                                    log::info('视频编码开启-url-存在');
                                    $vod_play_url_key_url_val = $vod_play_url_key_url_val_str[1];
                                }
                                log::info('视频编码开启-url-' . $vod_play_url_key_url_val);
//                                if ($vod_play_from_val == 'mbckm3u8' || $vod_play_from_val == '135m3u8' || $vod_play_from_val == 'wlm3u8' || $vod_play_from_val == 'ckm3u8' || $vod_play_from_val == 'zuidam3u8') {
//                                    continue;
//                                }
                                $vod_play_url_key_url_val_text_url = '';
                                $get_vod_ts = '';
                                $home = '';
                                if ($vod_play_from_val == 'wlm3u8' || $vod_play_from_val == '135m3u8' || $vod_play_from_val == 'zkm3u8') {
                                    log::info('视频编码开启-' . $vod_play_from_val . '-存在');
                                    $get_vod_ts = mac_curl_get($vod_play_url_key_url_val);
                                    $vod_play_url_key_url_val_text_url = $vod_play_url_key_url_val;
                                }

//                                print_r($vod_play_from_val);die;

                                if ($vod_play_from_val == 'zuidam3u8') {
                                    log::info('视频编码开启-' . $vod_play_from_val . '-存在');

                                    $vod_play_url_key_url_val_text = mac_curl_get($vod_play_url_key_url_val);
                                    $vod_play_url_key_url_val_text = explode("\n", $vod_play_url_key_url_val_text);
                                    $vod_play_url_key_url_val_text_url = $vod_play_url_key_url_val_text[2] ?? '';
                                    $home = parse_url($vod_play_url_key_url_val);
                                    $vod_play_url_key_url_val_text_url = str_replace($home['path'], $vod_play_url_key_url_val_text_url, $vod_play_url_key_url_val);
                                    if (strpos($vod_play_url_key_url_val_text_url, 'http') !== false && strpos($vod_play_url_key_url_val_text_url, 'm3u8') !== false) {
                                        log::info('视频编码开启-' . $vod_play_from_val . '-存在http-m3u8');
                                        $get_vod_ts = mac_curl_get($vod_play_url_key_url_val_text_url);//获取ts
                                    }
                                }
                                if ($vod_play_from_val == 'mbckm3u8' || $vod_play_from_val == 'ckm3u8') {
                                    log::info('视频编码开启-' . $vod_play_from_val . '-存在');
                                    $vod_play_url_key_url_val_text = mac_curl_get($vod_play_url_key_url_val);
                                    $vod_play_url_key_url_val_text = explode("\n", $vod_play_url_key_url_val_text);
                                    $vod_play_url_key_url_val_text_url = $vod_play_url_key_url_val_text[2] ?? '';
                                    $vod_play_url_key_url_val_text_url = str_replace('index.m3u8', $vod_play_url_key_url_val_text_url, $vod_play_url_key_url_val);
                                    if (strpos($vod_play_url_key_url_val_text_url, 'http') !== false && strpos($vod_play_url_key_url_val_text_url, 'm3u8') !== false) {
                                        log::info('视频编码开启-' . $vod_play_from_val . '-存在http-m3u8');
                                        $get_vod_ts = mac_curl_get($vod_play_url_key_url_val_text_url);//获取ts
                                    }
                                }
//                                var_dump($get_vod_ts);die;
                                if (strpos($get_vod_ts, 'ts') !== false) {
                                    log::info('视频编码开启--' . $vod_play_from_val . '-存在ts'.$d_val['vod_id']);
                                    $vod_ts = explode("\n", $get_vod_ts);
                                    $t_url_str = '';
                                    $FfmpegEncryptionText = '';
                                    if (!empty($vod_ts)) {
                                        foreach ($vod_ts as $t_key => $t_val) {
                                            if (strpos($t_val, 'key.key') !== false) {
                                                $is_encryption = true;
                                                $t_val = str_replace('key.key', '%s', $t_val);
                                            }
                                            if (strpos($t_val, 'ts') != false) {
                                                if ($vod_play_from_val == 'wlm3u8') {
                                                    $t_val = array_pop(explode('/', $t_val));
                                                }
                                                if ($vod_play_from_val == 'zuidam3u8') {
                                                    $t_array = explode('/', $t_val);
                                                    $scheme = $home['scheme'] ?? 'http';
                                                    $host = $home['host'] ?? '';
                                                    $t_val = array_pop($t_array);
                                                    $vod_play_url_key_url_val_text_url = $scheme . '://' . $host . implode('/', $t_array) . '/index.m3u8';
                                                }
                                                $t_url_str = $t_val;
                                                $FfmpegEncryptionText .= '%s' . "\r\n";
                                                $FfmpegEncryptionText .= '#EXT-X-ENDLIST' . "\r\n";
                                                break;
                                            } else {
                                                $FfmpegEncryptionText .= $t_val . "\r\n";
                                            }
                                        }
                                    }
                                    if (!empty($t_url_str)) {
                                        log::info('视频编码开启存在--' . $t_url_str . '-存在ts');
                                        if($vod_play_from_val == 'zkm3u8'){
                                            $str_p = 'playlist.m3u8';
                                        }else{
                                            $str_p = 'index.m3u8';
                                        }
                                        $vod_play_url_key_url_val_text_ts_url = str_replace($str_p, $t_url_str, $vod_play_url_key_url_val_text_url);
                                        //type_id_1 type_id id 播放器 集 5dsHHdzp1025000.ts
                                        $collection = findNum($vod_play_url_key_url_val_str[0] ?? '');
                                        if (empty($collection)) {
                                            $collection = $vod_play_url_key_url_val_str[0] ?? '';
                                        }
                                        $ts_path_dir = ROOT_PATH . 'static' . DS . 'vod_ts' . DS . $d_val['type_id_1'] . DS . $d_val['type_id'] . DS . $d_val['vod_id'] . DS . $vod_play_from_val . DS . $collection;
                                        $ts_path = $ts_path_dir . DS . $t_url_str;//ts文件
                                        $ts_new_path = $ts_path_dir . DS . 'new_' . $t_url_str;//new_ts文件
                                        $power_where['path'] = $ts_new_path;
                                        $powerData = $this->getFind($power_where);
                                        if (!empty($powerData)) {
                                            log::info('视频编码开启存在--过滤' . $d_val['vod_id']);
                                            continue; //过滤
                                        }
                                        $key_path = $ts_path_dir . DS . 'key.key';//key
                                        $index1_m3u8_path = $ts_path_dir . DS . 'index1.m3u8';//第一步
                                        $index2_m3u8_path = $ts_path_dir . DS . 'index2.m3u8';//第二步
                                        $index_last_m3u8_path = $ts_path_dir . DS . 'index_last.m3u8';//第三步
                                        $vod_play_url_key_url_val_text_ts_key_key_url = str_replace('index.m3u8', 'key.key', $vod_play_url_key_url_val_text_url);
                                        mac_mkdirss($ts_path_dir);//创建目录
                                        //下载 index1.mou8
                                        $this->get_list_vod($vod_play_url_key_url_val, $index1_m3u8_path);
                                        //下载 index2.mou8
                                        $this->get_list_vod($vod_play_url_key_url_val_text_url, $index2_m3u8_path);
                                        //获取top ts 为了转码
                                        $top_index_text = $this->getFfmpegEncryptionText($FfmpegEncryptionText, $key_path, $ts_path, $is_encryption);
                                        $this->get_list_vod(null, $index_last_m3u8_path, $top_index_text);
                                        //下载key
                                        if ($is_encryption == true) {
                                            $this->get_list_vod($vod_play_url_key_url_val_text_ts_key_key_url, $key_path);
                                        }
                                        //是否存在分辨率
                                        $vod_play_url_key_url_val_text_p1 = $vod_play_url_key_url_val_text[1] ?? '';
//
                                        if (strpos($vod_play_url_key_url_val_text_p1, 'RESOLUTION=') !== false) {
                                            log::info('视频编码开启-RESOLUTION-存在--');
                                            $resolution = explode('RESOLUTION=', $vod_play_url_key_url_val_text_p1)[1] ?? '';
                                            $this->get_list_vod($vod_play_url_key_url_val_text_ts_url, $ts_path);
                                            $resolution_data = $this->getVideoInfo($ts_path);
                                        } else {
                                            //下载ts
//                                            var_dump($vod_play_url_key_url_val_text_ts_url);
                                            $this->get_list_vod($vod_play_url_key_url_val_text_ts_url, $ts_path);
                                            if ($is_encryption == true) { //解码视频
                                                $path = $ts_new_path;
                                                //new ts 转换存储地址 + 视频信息
                                                $resolution_data = $this->getFFmpegData($index_last_m3u8_path, $path);
                                                if (empty($resolution_data)) {
                                                    log::info('视频编码开启存在--过滤');
                                                    $resolution_data = $this->getVideoInfo($path);
                                                }
                                            } else {
                                                $path = $ts_path;
                                                $resolution_data = $this->getVideoInfo($path);
                                            }
                                            //入库data
                                            $resolution = $resolution_data['resolution'] ?? '';
                                        }
                                        $res = $this->getAdd($d_val['vod_id'], $d_val['vod_name'],$vod_play_from_val, $collection, $resolution, $ts_new_path, 1, $resolution_data);
                                        if ($res) {
                                            if(!empty($resolution_data['code_name'])){
                                                if (file_exists($ts_path)) { //先删除ts
                                                    log::info('视频编码--先删除---视频');
                                                    unlink($ts_path);
                                                }
                                                if (file_exists($ts_new_path)) { //先删除ts
                                                    log::info('视频编码--先删除--new-视频');
                                                    unlink($ts_new_path);
                                                }
                                            }

                                            log::info('视频编码开启存在--添加入库' . $d_val['vod_id']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $output->writeln("定时计划:视频编码结束:");
    }


    //获取top index
    public function getFfmpegEncryptionText($FfmpegEncryptionText, $key, $ts, $is_encryption = false)
    {
        if ($is_encryption == false) {
            return sprintf($FfmpegEncryptionText, $ts);
        } else {
            return sprintf($FfmpegEncryptionText, $key, $ts);
        }
    }


    //下载到本地
    public function get_list_vod($url = null, $path = '/', $text = '')
    {
        if (!empty($text)) {
            $vod_ts_data = $text;
        } else {
            $vod_ts_data = mac_curl_get($url);
        }
        $fp2 = @fopen($path, 'w');
        fwrite($fp2, $vod_ts_data);
        fclose($fp2);
    }

    // 取出数据豆瓣评分为空数据
    protected function getFind($where)
    {
        return $this->powerDb->where($where)->find();
    }

    // 取出数据豆瓣评分为空数据
    protected function getAdd($id, $name ,$vod_play_from_val, $collection, $resolution, $ts_new_path, $state, $resolution_data)
    {
        $install_data = [];
        $install_data['vod_id'] = $id;
        $install_data['title'] = $name;
        $install_data['player'] = $vod_play_from_val;
        $install_data['code'] = $resolution_data['code']??1;
        $install_data['code_name'] = $resolution_data['code_name']??'';
        $install_data['collection'] = $collection;
        $install_data['resolution'] = $resolution;
        $install_data['path'] = $ts_new_path;
        $install_data['state'] = $state;
        $install_data['text'] = json_encode($resolution_data, true);
        return $this->powerDb->insert($install_data);
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

    //new ts 转换存储地址
    public function getFFmpegData($index_last_m3u8_path, $ts_new_path)
    {
        ///usr/bin/ffmpeg
        //usr/local/Cellar/ffmpeg/4.2.2_2/bin/ffmpeg
        $ffmpeg_path =   $this->ffmpeg.' -allowed_extensions ALL -protocol_whitelist "file,http,crypto,tcp" -i %s -c copy %s 2>&1';
//        print_r($ffmpeg_path);die;
        $ffmpeg_str_shell = sprintf($ffmpeg_path, $index_last_m3u8_path, $ts_new_path);
        //调用php的exec方法去执行脚本
        exec($ffmpeg_str_shell, $output, $return_val);
        return $this->getVideoInfo($ts_new_path, $output);
    }

    public function getVideoInfo($file, $is_data = [])
    {
//        $ffmpeg_path = '/usr/local/ffmpeg2/bin/ffmpeg -i "%s" 2>&1';
        if (!empty($is_data)) {
            $info = implode(',', $is_data);
        } else {
            $ffmpeg_path =   $this->ffmpeg.' -i "%s" 2>&1';
//            print_r($ffmpeg_path);die;
            $command = sprintf($ffmpeg_path, $file);
            ob_start();
            passthru($command);
            $info = ob_get_contents();
            ob_end_clean();
        }

        $data = array();
        if (preg_match("/Duration: (.*?), start: (.*?), bitrate: (\d*) kb\/s/", $info, $match)) {
            $data['duration'] = $match[1]; //播放时间
            $arr_duration = explode(':', $match[1]);
            $data['seconds'] = $arr_duration[0] * 3600 + $arr_duration[1] * 60 + $arr_duration[2]; //转换播放时间为秒数
            $data['start'] = $match[2]; //开始时间
            $data['bitrate'] = $match[3]; //码率(kb)
        }
        if (preg_match("/Video: (.*?), (.*?), (.*?)[,\s]/", $info, $match)) {
            $data['vcodec'] = $match[1]; //视频编码格式
            $data['vformat'] = $match[2]; //视频格式
            $data['resolution'] = $match[3]; //视频分辨率
            $arr_resolution = explode('x', $match[3]);
            $data['width'] = $arr_resolution[0];
            $data['height'] = $arr_resolution[1];
            if($data['height']<480 && $data['width'] < 640){
                $data['code'] = 1;//省流量
                $data['code_name'] = '省流';//省流量
            }else if(($data['height']<608 && $data['width'] < 1080 )  && ($data['height']>480 && $data['width'] >640)){
                $data['code'] = 2;//
                $data['code_name'] = '高清480P';
            }else if(($data['height']<1080  && $data['width'] < 1920) && ($data['height']>608 && $data['width'] > 1080)){
                $data['code'] = 3;
                $data['code_name'] = '超清720P';
            }else if(($data['height']< 2160 && $data['width'] < 3840) && ($data['height']>1080 && $data['width'] > 1920)){
                $data['code'] = 4;
                $data['code_name'] = '蓝光';
            }else if($data['height']>= 2160 && $data['width'] >= 3840){
                $data['code'] = 5;
                $data['code_name'] = '4K';
            }else{
                $data['code'] = 2;//省流量
                $data['code_name'] = '高清480P';//省流量
            }
        }
        if (preg_match("/Audio: (\w*), (\d*) Hz/", $info, $match)) {
            $data['acodec'] = $match[1]; //音频编码
            $data['asamplerate'] = $match[2]; //音频采样频率
        }
        if (isset($data['seconds']) && isset($data['start'])) {
            $data['play_time'] = $data['seconds'] + $data['start']; //实际播放时间
        }
        $data['size'] = filesize($file); //文件大小
        return $data;
    }
    //获取分辨率
    public function getCode(){
        return [
            '320x240'=>'省流',
            '640x480'=>'高清480P',
            '720x576'=>'高清480P',
            '704x396'=>'高清480P',
            '704x576'=>'高清480P',
            '1024x768'=>'超清720P',
            '1080x608'=>'超清720P',
            '1280x720'=>'超清720P',
            '1280x960' => '超清960Р',
            '1920x1080' => '蓝光',//c
            '2048x1536'=>'3MP',
            '2560x1440' =>'4MP',
            '2592x2048' =>'5MP',
            '3264x2448' =>'8MP',
            '3840x2160' =>'4K'
        ];
    }


}