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
use function GuzzleHttp\Psr7\_caseless_remove;
use Exception;


class PushData extends Common
{


    protected $vodModel;
    protected $videoModel;
    protected $videoVodModel;
    protected $videoCollectionModel;

    protected function configure()
    {
        $config = config('log');
        $config['keyp'] = 'data';
        $this->vodModel = Db::name('vod');
        $this->videoVodModel = Db::name('video_vod');
        $this->setName('pushData')->addArgument('parameter')->setDescription("获取数据-插入任务表");//这里的setName和php文件名一致,setDescription随意
    }


    /*
     * 下载
     */
    protected function execute(Input $input, Output $output)
    {

        set_time_limit(0);
        $output->writeln('获取数据-插入任务表-获取数据开始:init');
        $myparme = $input->getArguments();
        $parameter = $myparme['parameter'];
        //参数转义解析
        $param = $this->ParSing($parameter);
        $name = $param['name'] ?? 'all';
        if ($name == 'all') {
            //这里写业务逻辑
            $this->getWhile2();
            //不存在添加
            $this->getWhile();
        } else if ($name == 'up') {
            //这里写业务逻辑
            $this->getWhile2();
        } else {
            //这里写业务逻辑
            $this->getWhile();
        }
        $output->writeln("结束...");
    }

    protected function getWhile()
    {
        $start = 0;
        $page = 1;
        $limit = 20;
        $is_true = true;
        $order = 'a.vod_id desc';
        //where
        $vod_where = [];
        $vod_where['a.type_id'] = ['in', '6,7,8,9,10,11,12,13,14,15,16,24']; //电影
//        ['13','14','15','16','24'];
        $vod_where['a.vod_year'] = ['gt', 2000];//年代限制
//        $vod_where['a.vod_area']  = array(array('like','%韩国%'), array('like','%美国%'), 'or');
        //$vod_where['vod_lang']  = array(array('like','%英语%'), array('like','%韩语%'),  'or');
//        $vod_where['a.vod_douban_id']  = ['gt',0]; //豆瓣限制
//        $vod_where['a.vod_douban_score']  = ['gt',7];
        $vod_where['a.vod_play_url'] = array(array('like', '%.m3u8%'), array('like', '%.mp4%'), 'or');
        $vod_where['a.vod_down_url'] = array(array('like', '%.m3u8%'), array('like', '%.mp4%'), 'or');
        $vod_where['b.is_down'] = ['EXP', Db::raw('IS NULL')];
        while ($is_true) {
            $data = $this->getDataJoin($vod_where, $order, $page, $limit, $start);
            if (!empty($data)) {
                $pagecount = $data['pagecount'] ?? 0;
                if ($page > $pagecount) {
                    $is_true = false;
                    break;
                }
                if (!empty($data['list'])) {
                    foreach ($data['list'] as $key => $val) {
                        $vod_collection_url = $this->getUrlLike($val);
                    }
                }
            }
        }
    }

    protected function getWhile2()
    {


        $start = 0;
        $page = 1;
        $limit = 20;
        $is_true = true;
        $order = 'a.vod_id desc';
        $vod_where = [];
        $vod_where['a.type_id'] = ['in', '6,7,8,9,10,11,12,13,14,15,16,24']; //电影
//        $s = strtotime(date("Y-m-d H:00:00",time()));
//        $e = strtotime(date("Y-m-d H:59:59",time()));
        $vod_where['a.vod_time'] = ['gt', 2000];//
//        $vod_where['a.vod_time'] = ['between', [$s, $e]];
        $vod_where['a.vod_year'] = ['gt', 2000];//年代限制
        $vod_where['b.is_sync'] = ['neq', 1];
        $vod_where['b.is_section'] = ['neq', 1];
        $vod_where['b.is_down'] = ['neq', 1];
        $vod_where['b.vod_id'] = ['eq', 452786];//
        $vod_where['a.vod_play_url'] = array(array('like', '%.m3u8%'), array('like', '%.mp4%'), 'or');
        $vod_where['a.vod_down_url'] = array(array('like', '%.m3u8%'), array('like', '%.mp4%'), 'or');
        while ($is_true) {

            $data = $this->getDataJoin1($vod_where, $order, $page, $limit, $start);
             log::write('页码-'.$page.'-共-'.$data['pagecount'] ?? 0);

            $pagecount = $data['pagecount'] ?? 0;
            if ($page > $pagecount) {
                $is_true = false;
                break;
            }
            if (!empty($data)) {
                if (!empty($data['list'])) {
                    foreach ($data['list'] as $key => $val) {
//                        p($val);
                        $val['chren'] = $this->videoVodModel->where(['vod_id' => $val['b_vod_id']])->select();
                        $chren_data = $this->childrenUnArr($val['chren']);
                        $this->getUrlLike($val, '.m3u8', 'update', $chren_data);
                    }
                }
            } else {
                break;
            }
            $page = $page + 1;

        }

    }


    protected function childrenUnArr($arr)
    {
        $new_array = [];
        foreach ($arr as $k => $v) {
            if (!isset($new_array[$v['collection']])) {
                $new_array[$v['collection']] = $v;
            } else {
                $i_data = [];
                $id = $new_array[$v['collection']]['id'];
                $task_id_find = Db::table('video_err_abnormal')->where(['task_id' => $v['id']])->find();
                $task_id_id = Db::table('video_err_abnormal')->where(['task_id' => $id])->find();
                if (empty($task_id_find)) {
                    $i_data[0]['task_id'] = $v['id'];
                }
                if (empty($task_id_id)) {
                    $i_data[1]['task_id'] = $id;
                }
                if (!empty($i_data)) {
                    Db::table('video_err_abnormal')->insertAll($i_data);
                }
            }
        }
        return $new_array;
    }

    //获取列表
    protected function getIndexData($cj_from_arr, $cj_url_arr, $cj_server_arr, $cj_note_arr, $type)
    {
        $collect_filter = [];
        foreach ($cj_from_arr as $kk => $vv) {
            if (empty($vv)) {
                unset($cj_from_arr[$kk]);
                unset($cj_url_arr[$kk]);
                unset($cj_server_arr[$kk]);
                unset($cj_note_arr[$kk]);
                continue;
            }
            $cj_url_arr_str = $cj_url_arr[$kk] ?? '';
            $cj_url_arr[$kk] = rtrim($cj_url_arr_str, '#');
            $cj_server_arr[$kk] = $cj_server_arr[$kk] ?? '';
            $cj_note_arr[$kk] = $cj_note_arr[$kk] ?? "";
            if (isset($cj_url_arr[$kk])) {
                $count = substr_count($cj_url_arr[$kk], $type);
                if ($count == 0) {
                    unset($cj_from_arr[$kk]);
                    unset($cj_url_arr[$kk]);
                    unset($cj_server_arr[$kk]);
                    unset($cj_note_arr[$kk]);
                    continue;
                }
                $vData = explode('#', $cj_url_arr[$kk]);
                foreach ($vData as $v_k => $v_v) {
                    $count = substr_count($v_v, $type);
                    if ($count != 0) {
                        $collect_filter[$vv][$v_k] = $v_v;
                    }
                }
            }
        }
        return $collect_filter;
    }

    //获取所有连接
    protected function getAll($v, $type)
    {
        $cj_play_from_arr = explode('$$$', $v['vod_play_from']);
        $cj_play_url_arr = explode('$$$', $v['vod_play_url']);
        $cj_play_server_arr = explode('$$$', $v['vod_play_server']);
        $cj_play_note_arr = explode('$$$', $v['vod_play_note']);
        $cj_down_from_arr = explode('$$$', $v['vod_down_from']);
        $cj_down_url_arr = explode('$$$', $v['vod_down_url']);
        $cj_down_server_arr = explode('$$$', $v['vod_down_server']);
        $cj_down_note_arr = explode('$$$', $v['vod_down_note']);
        $collect_filter = [];
        //播放连接
        $collect_filter['play'] = $this->getIndexData($cj_play_from_arr, $cj_play_url_arr, $cj_play_server_arr, $cj_play_note_arr, $type);
        if (empty($collect_filter['play'])) {
            $collect_filter['play'] = $this->getIndexData($cj_down_from_arr, $cj_down_url_arr, $cj_down_server_arr, $cj_down_note_arr, $type);
        }
        $collect_filter['down'] = $this->getIndexData($cj_down_from_arr, $cj_down_url_arr, $cj_down_server_arr, $cj_down_note_arr, '.mp4');
        return $collect_filter;
    }

    protected function pingJieUrl($collect_filter, $type = 'play')
    {
        $new_play_url = [];
        $key_data = array_keys($collect_filter[$type]);
        $key_data_new = [];//挑选值比较大的key
        foreach ($key_data as $k_data => $v_data) {
            $key_data_new[$v_data] = count($collect_filter[$type][$v_data]);
        }//挑选值比较大的key
        $max_key = array_search(max($key_data_new), $key_data_new);
//           unset($key_data_new[$max_key]);
        foreach ($collect_filter[$type][$max_key] as $key_data => $val_data) {
            $collect_push = [];
            foreach ($key_data_new as $itemKey => $itemVal) {
                $key_url = $collect_filter[$type][$itemKey][$key_data] ?? '';
                $count = substr_count($key_url, '$');
                if ($count == 0) {
                    $key_url = '第'.($key_data + 1).'集$'.$key_url;
                }
                $collect_push[] = $key_url;
            }
            //down_url
            //m3u8_url
            $collect_push = array_filter($collect_push);
            if(!empty($collect_push)){
                if ($type == 'play') {
                    $new_play_url[$key_data]['m3u8_url'] = implode('#', $collect_push);
                } else {
                    $new_play_url[$key_data]['down_url'] = implode('#', $collect_push);
                }
            }
        }
        return $new_play_url;
    }

    protected function findTitle($k_p_val, $type = 0)
    {
        $array = explode('#', $k_p_val['m3u8_url']);
        foreach ($array as $k => $v) {
            $count = substr_count($v, '$');
            if ($count > 0) {
                return explode("$", $v)[0] ?? '';
            }
        }
        return '';
    }

    protected function vodData($v, $title, $new_down_url, $k_p_play, $k_p_val)
    {
        $new_url['vod_name'] = $v['vod_name'] ?? '';
        $new_url['type_id'] = $v['type_id'] ?? '';
        $new_url['type_id_1'] = $v['type_id_1'] ?? '';
        $new_url['down_ts_url'] = '';
        $new_url['down_mp4_url'] = '';
        $new_url['collection'] = intval($title);
        $new_url['is_down'] = 0;
        $new_url['is_sync'] = 0;
        $new_url['is_section'] = 0;
        $new_url['is_down_mp4'] = 0;
        $new_url['is_down_m3u8'] = 0;
        $new_url['type'] = 2;
        $new_url['examine_id'] = 0;
        $new_url['reason'] = '';
        $new_url['size'] = '';
        $new_url['bitrate'] = '';
        $new_url['resolution'] = '';
        $new_url['duration'] = '';
        $new_url['video_id'] = 0;
        $new_url['sum'] = 0;
        $new_url['down_add_time'] = time();
        $new_url['up_time'] = time();
        $new_url['down_time'] = time();
        $new_url['code'] = '-1';
        $new_url['vod_id'] = $v['vod_id'];
        $new_url['weight'] = $v['vod_douban_score'] ?? '0';
        $new_url['down_url'] = $new_down_url[$k_p_play]['down_url'] ?? '';
        $new_url['m3u8_url'] = $k_p_val['m3u8_url'] ?? '';
        return $new_url;
    }

    public function getUrlLike($v, $type = '.m3u8', $i = 'install', $n = [])
    {
        //验证地址
        $collect_filter = $this->getAll($v, $type);
        $new_down_url = [];
        if (!empty($collect_filter['down'])) {
            $new_down_url = $this->pingJieUrl($collect_filter, 'down');
        }
        if (!empty($collect_filter['play'])) {
            $new_play_url = $this->pingJieUrl($collect_filter, 'play');
            foreach ($new_play_url as $k_p_play => $k_p_val) {
                if ($i == 'install') {
                    $title = $this->findTitle($k_p_val, 0);
                    if (!empty($title)) {
                        $title = findNumAll($title);
                        if ($v['type_id_1'] == 0) {
                            $v['type_id_1'] = getTypePid($v['type_id']);
                        }
                        if ($v['type_id_1'] == 1) {
                            $title = 1;
                        }
                        $n_url = $this->vodData($v, $title, $new_down_url, $k_p_play, $k_p_val);
                        if (!empty($n_url)) {
                            $res = $this->videoVodModel->insert($n_url);
                            if ($res) {
                                log::write('成功q3-' . $v['b_vod_id']);
                            } else {
                                log::write('失败q3-' . $v['b_vod_id']);
                            }
                        }
                    }
                } else {
                    $title = $this->findTitle($k_p_val, 0);
                    if (!empty($title)) {
                        $title = intval(findNumAll($title));
                        if ($v['type_id_1'] == 0) {
                            $v['type_id_1'] = getTypePid($v['type_id']);
                        }
                        if ($v['type_id_1'] == 1) {
                            $title = 1;
                        }
                        if (isset($n[$title])) {
                            if ($n[$title]['is_sync'] != 1) {
                                $up_data = $this->vodData($v, $title, $new_down_url, $k_p_play, $k_p_val);
                                if ($up_data['m3u8_url'] != $v['b_m3u8_url']) {
                                    $res = $this->videoVodModel->where(['id' => $n[$title]['id']])->update($up_data);
                                    if ($res) {
                                        log::write('成功q-' . $n[$title]['id']);
                                    } else {
                                        log::write('失败q-' . $n[$title]['id']);
                                    }
                                }
                            }
                        } else {
                            $n_url = $this->vodData($v, $title, $new_down_url, $k_p_play, $k_p_val);
                            if (!empty($n_url)) {
                                $res = $this->videoVodModel->insert($n_url);
                                if ($res) {
                                    log::write('成功q1-' . $v['b_vod_id']);
                                } else {
                                    log::write('失败q2-' . $v['b_vod_id']);
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    /*
     * 获取date 数据
     */
    protected function getData($where, $order, $page, $limit, $start)
    {
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodModel->where($where)->count();
        $list = $this->vodModel->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }

    protected function getDataJoin($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodModel->alias('a')->field('a.vod_id,a.type_id,a.type_id_1,a.vod_douban_score,a.vod_name,a.vod_down_url,a.vod_down_note,a.vod_down_server,a.vod_down_from,a.type_id,b.video_id as b_video_id,b.is_down,b.is_section,b.is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'LEFT')->count();
        $list = $this->vodModel->alias('a')->field('a.vod_id,a.type_id,a.vod_play_from,a.vod_play_server,a.vod_play_note,a.type_id_1,a.vod_play_url,a.vod_douban_score,a.vod_name,a.vod_down_url,a.vod_down_note,a.vod_down_server,a.vod_down_from,b.collection,a.type_id,b.video_id as b_video_id,b.is_down,b.is_section,b.is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'LEFT')->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }

    protected function getDataJoin1($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodModel->alias('a')->field('a.vod_id,a.type_id,a.type_id_1,a.vod_douban_score,a.vod_name,a.vod_down_url,a.vod_down_note,a.vod_down_server,a.vod_down_from,a.type_id,b.video_id as b_video_id,b.is_down,b.is_section,b.is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'RIGHT')->group('b.vod_id')->count();
        $list = $this->vodModel->alias('a')->field('a.vod_id,a.type_id,a.vod_play_from,a.vod_play_server,a.vod_play_note,a.type_id_1,a.vod_play_url,a.vod_douban_score,a.vod_name,a.vod_down_url,b.is_down,a.vod_down_note,a.vod_down_server,a.vod_down_from,a.type_id,b.vod_name as b_vod_name,b.m3u8_url as b_m3u8_url,b.id as bid,b.vod_id as b_vod_id')->join('video_vod b', 'a.vod_id=b.vod_id', 'RIGHT')->group('b.vod_id')->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }
}