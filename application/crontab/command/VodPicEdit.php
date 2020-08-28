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
use similar_text\similarText;

class VodPicEdit extends Common
{
    protected $videoDb; // 视频表

    protected function configure()
    {
        $this->vodDb = Db::name('vod');
        $this->setName('VodPicEdit')->addArgument('parameter')
            ->setDescription('定时计划：更新vod表图片');
    }

    protected function execute(Input $input, Output $output)
    {
        // 输出到日志文件
        $output->writeln("定时计划：更新vod表图片:start...");
        try {
            $myparme = $input->getArguments();
            $parameter = $myparme['parameter'];
            //参数转义解析
            $param = $this->ParSing($parameter);
            $name = $param['name'] ?? '';
            Log::info('$name::'.$name);

            $is_true = true;
            Cache::set('video_selected_current_select_video_id', '');
            while ($is_true) {
                $current_vod_id = Cache::get('video_selected_current_select_video_id');
                Log::info('current_vod_id::'. $current_vod_id);
                $vod_where = [];
                if (!empty($current_vod_id)) {
                    $vod_where['vod_id'] = ['EGT', $current_vod_id];
                }
                $vod_info = $this->vodDb->field('vod_id,vod_name,vod_content,vod_blurb,vod_actor,vod_director,type_id,vod_play_url,type_id_1')
                    ->where('vod_pic','not like',['%20200826%','%20200827%','%20200825%', '%20200828%'],'AND')
                    ->where($vod_where)
                    ->order('vod_id asc')
                    ->limit('0, 20')
                    ->select();
                // echo $this->vodDb->getLastSql();die;
                if (empty($vod_info)) {
                    $is_true = false;
                } else {
                    $current_index = Cache::get('vod_pic_current_index');
                    foreach ($vod_info as $v) {
                        Cache::set('vod_pic_current_index', $current_index + 1);
                        Log::info('视频id为::'. $v['vod_id']  . '视频名称为::' . $v['vod_name'] . '更新开始-----');
                        Cache::set('video_selected_current_select_video_id', $v['vod_id']);
                        self::_getData($v, $name);
                        Log::info('视频id为::'. $v['vod_id']  . '视频名称为::' . $v['vod_name'] . '更新结束-----');
                    }
                }

            }


        } catch (Exception $e) {
            $output->writeln("定时计划：更新vod表图片异常信息：" . $e);
        }
        $output->writeln("定时计划：更新vod表图片:end...");
    }

    private function _getData($info, $name)
    {
        if (!empty($info['vod_name'])) {
            $zy_list =  [
                'ok'=>'cjflag=80ded8e39c08122688a152ca5f4544c0&cjurl=https%3A%2F%2Fcj.okzy.tv%2Finc%2Fapi1s_subname.php&h=&t=&ids=&wd='.$info['vod_name'].'&type=1&mid=1&opt=0&filter=0&filter_from=&param=&ac=list',
                'zd'=> 'cjflag=ebde4a475b33db4e5628cd905dafd343&cjurl=http%3A%2F%2Fwww.zdziyuan.com%2Finc%2Fapi.php&h=&t=&ids=&wd='.$info['vod_name'].'&type=1&mid=1&opt=0&filter=0&filter_from=&param=&ac=list',
                'zx'=> 'ac=list&cjflag=fae10fd072c8e9f85fd79a8de702683b&cjurl=http%3A%2F%2Fapi.zuixinapi.com%2Finc%2Fapi.php&h=&t=&ids=&wd='.$info['vod_name'].'&type=1&mid=1&opt=0&filter=0&filter_from=&param=&page=1&limit=',
            ];
            foreach ($zy_list as $zy_val =>$zy_val){
                @parse_str($zy_val, $output);
                $vod_xml_info = $this->vod_xml_id($output);
                if(!empty($vod_xml_info) && !isset($vod_xml_info['code'])){
                    $name = $zy_val;
                    Log::info('查询到的数据'.json_encode($vod_xml_info));
                    break;
                }
            }

            $config = config('maccms.collect');
            $config = $config['vod'];

            if (!empty($vod_xml_info)) {

                $type_list = model('Type')->getCache('type_list');
                foreach ($vod_xml_info as $v) {
                    if ($info['vod_name'] != $info['vod_name']) {
                        continue;
                    }
                    if ($name == 'ok') {
                        $param_1 = 'ac=cj&cjflag=80ded8e39c08122688a152ca5f4544c0&cjurl=https%3A%2F%2Fcj.okzy.tv%2Finc%2Fapi1s_subname.php&h=&t=&ids='.$v['vod_id'].'&wd='.$v['vod_name'].'&type=1&mid=1&opt=0&filter=0&filter_from=&param=';
                    }
                    if ($name == 'zd') {
                        $param_1 = 'ac=cj&cjflag=80ded8e39c08122688a152ca5f4544c0&cjurl=http%3A%2F%2Fwww.zdziyuan.com%2Finc%2Fapi.php&h=&t=&ids='.$v['vod_id'].'&wd='.$v['vod_name'].'&type=1&mid=1&opt=0&filter=0&filter_from=&param=';
                    }
                    if ($name == 'zx') {
                        $param_1 = 'ac=cj&cjflag=fae10fd072c8e9f85fd79a8de702683b&cjurl=http%3A%2F%2Fapi.zuixinapi.com%2Finc%2Fapi.php&h=&t=&ids='.$v['vod_id'].'&wd='.$info['vod_name'].'&type=1&mid=1&opt=0&filter=0&filter_from=&param=&page=1&limit=';
                    }

                    Log::info('$param_1::'.$param_1);
                    @parse_str($param_1, $output_1);
                    $res_vod_xml = $this->vod_xml($output_1);
                    if ($res_vod_xml['code'] == 1) {
                        foreach ($res_vod_xml['data'] as $v1) {
                            if ($v1['type_id'] == 0) {
                                continue;
                            }
                            $v1['type_id_1'] = intval($type_list[$v1['type_id']]['type_pid']);
                            $cj_play_from_arr = explode('$$$', $v1['vod_play_from']);
                            $cj_play_url_arr = explode('$$$', $v1['vod_play_url']);
                            foreach ($cj_play_from_arr as $kk => $vv) {
                                if (empty($vv)) {
                                    unset($cj_play_from_arr[$kk]);
                                    unset($cj_play_url_arr[$kk]);
                                    continue;
                                }
                                if (empty($players[$vv])) {
                                    unset($cj_play_from_arr[$kk]);
                                    unset($cj_play_url_arr[$kk]);
                                    continue;
                                }

                                $cj_play_url_arr[$kk] = rtrim($cj_play_url_arr[$kk] ?? '', '#');
                            }


                            $new_check_data['vod_content'] = $v1['vod_content'];
                            $new_check_data['vod_blurb'] = $v1['vod_blurb'];
                            $new_check_data['vod_actor'] = $v1['vod_actor'];
                            $new_check_data['vod_director'] = $v1['vod_director'];
                            $new_check_data['type_id'] = $v1['type_id'];
                            $new_check_data['vod_play_url'] = (string)join('$$$', $cj_play_url_arr);
                            $new_check_data['type_id_1'] = $v1['type_id_1'];

                            $old_check_data['vod_content'] = $info['vod_content'];
                            $old_check_data['vod_blurb'] = $info['vod_blurb'];
                            $old_check_data['vod_actor'] = $info['vod_actor'];
                            $old_check_data['vod_director'] = $info['vod_director'];
                            $old_check_data['type_id'] = $info['type_id'];
                            $old_check_data['vod_play_url'] = $info['vod_play_url'];
                            $old_check_data['type_id_1'] = $info['type_id_1'];
                            self::_logWrite('视频名称为::' . $v['vod_name'] . '数据库vod_id::' . $info['vod_id'] . '当前顺序：：' . Cache::get('vod_pic_current_index'));
                            $check_vod_rade = self::_checkVodRade($old_check_data, $new_check_data);
                            if($check_vod_rade){
                                $tmp = $this->syncImages($config['pic'], $v1['vod_pic'], 'vod');
                                $edit_data['vod_pic'] = (string)$tmp['pic'];
                                $edit_data['vod_time'] = time();
                                $where['vod_id'] = $info['vod_id'];
                                $result = $this->vodDb->where($where)->update($edit_data);
                                Log::info('视频id为::'. $info['vod_id']  . '视频名称为::' . $info['vod_name'] . '更新结果为：：'.$result);
                            }
                        }
                    } else {
                        Log::info(json_encode($res_vod_xml));
                    }
                }
            }
        }
    }


    public function vod_xml($param, $html = '')
    {

        // 获取缓存中的当前页
        $cache_current_page = Cache::get('collect_ok_current_page');
        if (empty($param['h'])) {
            if (!empty($cache_current_page) && empty($param['page'])) {
                $param['page'] = $cache_current_page;
            }
        }

        $url_param = [];
        $url_param['ac'] = $param['ac'] ?? '';
        $url_param['t'] = $param['t'] ?? '';
        $url_param['pg'] = is_numeric($param['page']) ? $param['page'] : '';
        $url_param['h'] = $param['h'] ?? '';
        $url_param['ids'] = $param['ids'] ?? '';
        $url_param['wd'] = $param['wd'] ?? '';
        if (empty($param['h']) && !empty($param['rday'])) {
            $url_param['h'] = $param['rday'];
        }

        if ($param['ac'] != 'list') {
            $url_param['ac'] = 'videolist';
        }

        $url = $param['cjurl'] ?? '';
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= http_build_query($url_param) . base64_decode($param['param']);
        $html = mac_curl_get($url);

        if (empty($html)) {
            return ['code' => 1001, 'msg' => '连接API资源库失败，通常为服务器网络不稳定或禁用了采集'];
        }

        $xml = @simplexml_load_string($html);
        if (empty($xml)) {
            $labelRule = '<pic>' . "(.*?)" . '</pic>';
            $labelRule = mac_buildregx($labelRule, "is");
            preg_match_all($labelRule, $html, $tmparr);
            $ec = false;
            foreach ($tmparr[1] as $tt) {
                if (strpos($tt, '[CDATA') === false) {
                    $ec = true;
                    $ne = '<pic>' . '<![CDATA[' . $tt . ']]>' . '</pic>';
                    $html = str_replace('<pic>' . $tt . '</pic>', $ne, $html);
                }
            }
            if ($ec) {
                $xml = @simplexml_load_string($html);
            }
            if (empty($xml)) {
                return ['code' => 1002, 'msg' => 'XML格式不正确，不支持采集'];
            }
        }

        $array_page = [];
        $array_page['page'] = (string)$xml->list->attributes()->page;
        $array_page['pagecount'] = (string)$xml->list->attributes()->pagecount;
        $array_page['pagesize'] = (string)$xml->list->attributes()->pagesize;
        $array_page['recordcount'] = (string)$xml->list->attributes()->recordcount;
        $array_page['url'] = $url;
        if (empty($param['h'])) {
            // 记录当前页数  防止人为停掉任务导致的从第一页开始爬取数据
            if ($array_page['page'] >= $array_page['pagecount']) {
                Cache::set('collect_ok_current_page', '');
            } else {
                Cache::set('collect_ok_current_page', $array_page['page']);
            }
        }

        $type_list = model('Type')->getCache('type_list');
        $bind_list = config('bind');


        $key = 0;
        $array_data = [];
        foreach ($xml->list->video as $video) {
            $bind_key = $param['cjflag'] . '_' . (string)$video->tid;
            if ($bind_list[$bind_key] > 0) {
                $array_data[$key]['type_id'] = $bind_list[$bind_key];
            } else {
                $array_data[$key]['type_id'] = 0;
            }
            //$array_data[$key]['type_id'] = (string)$video->tid;
            $array_data[$key]['vod_name'] = (string)$video->name;
            $array_data[$key]['vod_sub'] = (string)$video->subname;
            $array_data[$key]['vod_remarks'] = (string)$video->note;
            $array_data[$key]['type_name'] = (string)$video->type;
            $array_data[$key]['vod_pic'] = (string)$video->pic;
            $array_data[$key]['vod_lang'] = (string)$video->lang;
            $array_data[$key]['vod_area'] = (string)$video->area;
            $array_data[$key]['vod_year'] = (string)$video->year;
            $array_data[$key]['vod_serial'] = (string)$video->state;
            $array_data[$key]['vod_actor'] = (string)$video->actor;
            $array_data[$key]['vod_director'] = (string)$video->director;
            $array_data[$key]['vod_content'] = (string)$video->des;

            $array_data[$key]['vod_status'] = 1;
            $array_data[$key]['vod_type'] = $array_data[$key]['list_name'] ?? '';
//            if(empty($array_data[$key]['vod_type'])){
//                $array_data[$key]['type_id'] = (string)$video->tid;
//            }
            $array_data[$key]['vod_time'] = (string)$video->last;
            $array_data[$key]['vod_total'] = 0;
            $array_data[$key]['vod_isend'] = 1;
            if ($array_data[$key]['vod_serial']) {
                $array_data[$key]['vod_isend'] = 0;
            }
            //格式化地址与播放器
            $array_from = [];
            $array_url = [];
            $array_server = [];
            $array_note = [];
            //videolist|list播放列表不同
            if ($count = count($video->dl->dd)) {
                for ($i = 0; $i < $count; $i++) {
                    $array_from[$i] = (string)$video->dl->dd[$i]['flag'];
                    $array_url[$i] = $this->vod_xml_replace((string)$video->dl->dd[$i]);
                    $array_server[$i] = 'no';
                    $array_note[$i] = '';

                }
            } else {
                $array_from[] = (string)$video->dt;
                $array_url[] = '';
                $array_server[] = '';
                $array_note[] = '';
            }

            if (strpos(base64_decode($param['param']), 'ct=1') !== false) {
                $array_data[$key]['vod_down_from'] = implode('$$$', $array_from);
                $array_data[$key]['vod_down_url'] = implode('$$$', $array_url);
                $array_data[$key]['vod_down_server'] = implode('$$$', $array_server);
                $array_data[$key]['vod_down_note'] = implode('$$$', $array_note);
            } else {
                $array_data[$key]['vod_play_from'] = implode('$$$', $array_from);
                $array_data[$key]['vod_play_url'] = implode('$$$', $array_url);
                $array_data[$key]['vod_play_server'] = implode('$$$', $array_server);
                $array_data[$key]['vod_play_note'] = implode('$$$', $array_note);
            }

            $key++;
        }

        $array_type = [];
        $key = 0;
        //分类列表
        if ($param['ac'] == 'list') {
            foreach ($xml->class->ty as $ty) {
                $array_type[$key]['type_id'] = (string)$ty->attributes()->id;
                $array_type[$key]['type_name'] = (string)$ty;
                $key++;
            }
        }
        $res = ['code' => 1, 'msg' => 'xml', 'page' => $array_page, 'type' => $array_type, 'data' => $array_data];
        return $res;
    }

    public function vod_xml_id($param, $html = '')
    {

        $url_param = [];
        $url_param['ac'] = $param['ac'] ?? '';
        $url_param['t'] = $param['t'] ?? '';
        $url_param['pg'] = is_numeric($param['page']) ? $param['page'] : '';
        $url_param['h'] = $param['h'] ?? '';
        $url_param['ids'] = $param['ids'] ?? '';
        $url_param['wd'] = $param['wd'] ?? '';
        if (empty($param['h']) && !empty($param['rday'])) {
            $url_param['h'] = $param['rday'];
        }

        if ($param['ac'] != 'list') {
            $url_param['ac'] = 'videolist';
        }

        $url = $param['cjurl'] ?? '';
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= http_build_query($url_param) . base64_decode($param['param']);
        $html = mac_curl_get($url);

        if (empty($html)) {
            return ['code' => 1001, 'msg' => '连接API资源库失败，通常为服务器网络不稳定或禁用了采集'];
        }

        $xml = @simplexml_load_string($html);
        if (empty($xml)) {
            $labelRule = '<pic>' . "(.*?)" . '</pic>';
            $labelRule = mac_buildregx($labelRule, "is");
            preg_match_all($labelRule, $html, $tmparr);
            $ec = false;
            foreach ($tmparr[1] as $tt) {
                if (strpos($tt, '[CDATA') === false) {
                    $ec = true;
                    $ne = '<pic>' . '<![CDATA[' . $tt . ']]>' . '</pic>';
                    $html = str_replace('<pic>' . $tt . '</pic>', $ne, $html);
                }
            }
            if ($ec) {
                $xml = @simplexml_load_string($html);
            }
            if (empty($xml)) {
                return ['code' => 1002, 'msg' => 'XML格式不正确，不支持采集'];
            }
        }


        $key = 0;
        $array_data = [];
        foreach ($xml->list->video as $video) {

            $array_data[$key]['vod_id'] = (string)$video->id;
            $array_data[$key]['vod_name'] = (string)$video->name;

            $key++;
        }
        Log::info(json_encode($array_data));
        return $array_data;
    }

    public function vod_xml_replace($url)
    {
        $array_url = array();
        $arr_ji = explode('#', str_replace('||', '//', $url));
        foreach ($arr_ji as $key => $value) {
            $urlji = explode('$', $value);
            if (count($urlji) > 1) {
                $array_url[$key] = $urlji[0] . '$' . trim($urlji[1]);
            } else {
                $array_url[$key] = trim($urlji[0]);
            }
        }
        return implode('#', $array_url);
    }

    /**
     * 视频相似度比较
     * @param  [type] $old_check_data [description]
     * @param  [type] $new_check_data [description]
     * @return [type]                 [description]
     */
    private function _checkVodRade( $old_check_data, $new_check_data ){
        $check_vod_content_rade = 0;
        $check_vod_blurb_rade = 0;
        $vod_actor_count = 0;
        $vod_director_count = 0;
        $vod_play_url_rade = 0;
        // 校验视频内容百分比
        if (!empty($old_check_data['vod_content']) && !empty($new_check_data['vod_content'])) {
            $check_vod_content_rade = self::_checkVodContentRade($old_check_data['vod_content'], $new_check_data['vod_content']);
        }
        // 简介比
        if (!empty($old_check_data['vod_blurb']) && !empty($new_check_data['vod_blurb'])) {
            $check_vod_blurb_rade = self::_checkVodContentRade($old_check_data['vod_blurb'], $new_check_data['vod_blurb']);
        }
        // 主演比
        if (!empty($old_check_data['vod_actor']) && !empty($new_check_data['vod_actor'])) {
            $vod_actor_count = self::_arrayIntersectCount(mac_trim_all($old_check_data['vod_actor']), mac_trim_all($new_check_data['vod_actor']));
        }
        // 导演比
        if (!empty($old_check_data['vod_director']) && !empty($new_check_data['vod_director'])) {
            $vod_director_count = self::_arrayIntersectCount(mac_trim_all($old_check_data['vod_director']), mac_trim_all($new_check_data['vod_director']));
        }
        // 类型比
        if ($old_check_data['type_id_1'] == 0) {
            $old_type_pid = get_type_pid_type_id($old_check_data['type_id']);
        } else {
            $old_type_pid = $old_check_data['type_id_1'];
        }

        if ($new_check_data['type_id_1'] == 0) {
            $new_type_pid = get_type_pid_type_id($new_check_data['type_id']);
        } else {
            $new_type_pid = $new_check_data['type_id_1'];
        }
        if (!empty($old_check_data['vod_play_url']) && !empty($new_check_data['vod_play_url'])) {
            // 链接比
            $new_play_url = explode('$$$', $new_check_data['vod_play_url']);
            $old_play_url = explode('$$$', $old_check_data['vod_play_url']);
            foreach ($new_play_url as $v) {
                $new_play_url_arr = implode(',', explode('#', $v));
                foreach ($old_play_url as $v1) {
                    $old_play_url_arr = implode(',', explode('#', $v1));
                    $play_url_rade = mac_intersect($new_play_url_arr, $old_play_url_arr);
                    if ($play_url_rade >= 80) {
                        $vod_play_url_rade = $play_url_rade;
                        break;
                    }
                }
            }
        }

        $condition = [];
        if ($check_vod_content_rade >= 50) {
            $condition['check_vod_content_rade'] = $check_vod_content_rade;
        }
        if ($check_vod_blurb_rade >= 50) {
            $condition['check_vod_blurb_rade'] = $check_vod_blurb_rade;
        }
        if ($vod_actor_count >= 1) {
            $condition['vod_actor_count'] = $vod_actor_count;
        }
        if ($vod_director_count >= 1) {
            $condition['vod_director_count'] = $vod_director_count;
        }
        if ($vod_play_url_rade >= 95) {
            $condition['vod_play_url_rade'] = $vod_play_url_rade;
        }
        if ($old_type_pid == $new_type_pid) {
            $condition['type_pid_eq'] = 1;
        }

        self::_logWrite("ok视频相似度：：" . '内容:' . $check_vod_content_rade . '简介:' . $check_vod_blurb_rade . '主演:' . $vod_actor_count . '导演:' . $vod_director_count . "链接:" . $vod_play_url_rade . "类型:" . $old_type_pid . '-' . $new_type_pid . '最终条件:' . json_encode($condition));

        if ( count($condition) >= 2 ){
            return true;
        } else {
            return false;
        }

    }

    //交集相似度
    private function _arrayIntersectCount($str1, $str2)
    {
        $array1 = array_filter(explode(',', $str1));
        $array2 = array_filter(explode(',', $str2));
        $count = array_intersect($array1, $array2);
        return count( $count );
    }

    /**
     * 重新定义日志文件路径存储采集比较信息
     * @param  [type] $log_content [description]
     * @return [type]              [description]
     */
    private function _logWrite($log_content){
        $dir = LOG_PATH .'collect'. DS;
        if (!file_exists($dir)){
            mkdir($dir,0777,true);
        }
        \think\Log::init([
            'type' => \think\Env::get('log.type', 'test'),
            'path' => $dir,
            'level' => ['info'],
            'max_files' => 30]);
        \think\Log::info($log_content);
    }

    /**
     * 比较详情百分比
     * @param  [type] $old_content [description]
     * @param  [type] $new_content [description]
     * @return [type]              [description]
     */
    private function _checkVodContentRade($old_content = NULL, $new_content = NULL)
    {
        // 字符串对比算法
        $lcs = new similarText();

        $rade = $lcs->getSimilar(mac_trim_all(mac_characters_format($old_content)), mac_trim_all(mac_characters_format($new_content))) * 100;

        return $rade;
    }

    public function syncImages($pic_status, $pic_url, $flag = 'vod')
    {
        if ($pic_status == 1) {
            $img_url = model('Image')->down_load($pic_url, $GLOBALS['config']['upload'], $flag);
            $link = MAC_PATH . $img_url;
            $link = str_replace('mac:', $GLOBALS['config']['upload']['protocol'] . ':', $img_url);

            if ($img_url == $pic_url) {
                $des = '<a href="' . $link . '" target="_blank">' . $link . '</a><font color=red>下载失败!</font>';
            } else {
                $pic_url = $img_url;
                $des = '<a href="' . $link . '" target="_blank">' . $link . '</a><font color=green>下载成功!</font>';
            }
        }
        return ['pic' => $pic_url, 'msg' => $des];
    }

    //代码解析(urlget传参模式)

    public function ParSing($parameter)
    {
        $parameter_array = array();
        $arry = explode('#', $parameter);
        foreach ($arry as $key => $value) {
            $zzz = explode('=', $value);
            $parameter_array[$zzz[0]] = $zzz[1];

        }
        return $parameter_array;

    }
}