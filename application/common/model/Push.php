<?php
namespace app\common\model;
use think\Db;
use think\Cache;
use app\common\util\Pinyin;
use think\Log;

class Push extends Base {

    protected $zy_list= [3,25,26,27,28];
    // 设置数据表（不含前缀）
    public function getWhile($id)
    {
        p($this->zy_list);
        $start = 0;
        $page = 1;
        $page1 = 1;
        $limit = 1;
        $is_true = true;
        $order = 'a.vod_id desc';
        //where
        $vod_where = [];
        $vod_where['a.type_id'] = ['in', '1,2,3,4,6,7,8,9,10,11,12,13,14,15,16,24,19,20,21,22,25,26,27,28,29']; //电影

        $vod_where['a.vod_play_url'] = array('like', '%.m3u8%');
        $vod_where['a.vod_id'] = $id;
        $vod_where['b.is_down'] = ['EXP', Db::raw('IS NULL')];
        $pagecount = $this->getDataJoinit($vod_where, $order, $page, $limit, $start);
        while ($is_true) {
            $data = $this->getDataJoini($vod_where, $order, $page, $limit, $start);
            log::write('页码-' . $page1 . '-共-' . $pagecount);
            mac_echo('页码-' . $page1 . '-共-' . $pagecount);
            if (!empty($data)) {
                if ($page1 > $pagecount) {
                    $is_true = false;
                    break;
                }
                if (!empty($data)) {
                    foreach ($data as $key => $val) {
                        mac_echo('开始处理' . $val['vod_name']);
                        $vod_collection_url = $this->getUrlLike($val);
                    }
                }
            } else {
                break;
            }
            $page1 = $page + 1;
        }
    }

    public function getWhile2($id)
    {
        p($this->zy_list);

        $start = 0;
        $page = 1;
        $limit = 1;
        $is_true = true;
        $order = 'b.weight desc';
        $vod_where = [];
        $vod_where['a.type_id'] = ['in', '1,2,3,4,6,7,8,9,10,11,12,13,14,15,16,24,19,20,21,22,25,26,27,28,29']; //电影
        $vod_where['a.vod_play_url'] = array('like', '%.m3u8%');
        $vod_where['a.vod_id'] = $id;
        $pagecount = $this->getDataJoinT($vod_where, $order, $page, $limit, $start);
        while ($is_true) {
            $data = $this->getDataJoin1($vod_where, $order, $page, $limit, $start);
            log::write('页码-' . $page . '-共-' . $pagecount);
            mac_echo('页码-' . $page . '-共-' . $pagecount);
            if (!empty($data)) {
                if ($page > $pagecount) {
                    $is_true = false;
                    break;
                }
                if (!empty($data)) {
                    foreach ($data as $key => $val) {
                        mac_echo('开始处理' . $val['b_vod_name']);
                        $val['chren'] = Db::name('video_vod')->where(['vod_id' => $val['b_vod_id']])->select();
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
            if (in_array($v['type_id'],$this->zy_list)){
                $m3u8_url_key=  explode('$',explode('#',$v['m3u8_url'])[0])[0];
            }else{
                $m3u8_url_key = $v['collection'];
            }
            if (!isset($new_array[$m3u8_url_key])) {
                $new_array[$m3u8_url_key] = $v;
            }
        }
        return $new_array;
    }

    //获取列表
    protected function getIndexData($v, $cj_from_arr, $cj_url_arr, $cj_server_arr, $cj_note_arr, $type)
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
                    $v_v_m3u8_url = $v_v;
                    $count = substr_count($v_v, $type);
                    if ($count != 0) {
                        $count2 = substr_count($v_v, '$');
                        if ($count2 > 0) {
                            $def_k = $v_k + 1;
                            $title = explode("$", $v_v)[0] ?? $def_k;
                            $count3 = substr_count($title, '特辑');
                            if ($count3 > 0) {
                                continue;
                            }
                            if ($v['type_id_1'] == 0) {
                                $v['type_id_1'] = getTypePid($v['type_id']);
                            }
                            if ($v['type_id_1'] == 1 || empty($v['type_id_1'])) {
                                $title = 1;
                            }
                            $new_v_k_ = intval(findNumAll($title));
                        } else {
                            $new_v_k_ = $v_k + 1;
                            if (substr_count($v_v, 'http') > 0) {
                                $v_v = '第' . ($new_v_k_) . '集$' . $v_v;
                            }
                        }
                        if (in_array($v['type_id'],$this->zy_list)){
                            $m3u8_url_key=  explode('$',explode('#',$v_v_m3u8_url)[0])[0];
                            if(!empty($m3u8_url_key)){
                                if (!isset($collect_filter[$vv][$m3u8_url_key])) {
                                    $collect_filter[$vv][$m3u8_url_key] = $v_v;
                                }
                            }else{
                                if (!isset($collect_filter[$vv][$new_v_k_])) {
                                    $collect_filter[$vv][$new_v_k_] = $v_v;
                                }
                            }
                        }else{
                            if (!isset($collect_filter[$vv][$new_v_k_])) {
                                $collect_filter[$vv][$new_v_k_] = $v_v;
                            }
                        }
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
        $collect_filter['play'] = $this->getIndexData($v, $cj_play_from_arr, $cj_play_url_arr, $cj_play_server_arr, $cj_play_note_arr, $type);
        if (empty($collect_filter['play'])) {
            $collect_filter['play'] = $this->getIndexData($v, $cj_down_from_arr, $cj_down_url_arr, $cj_down_server_arr, $cj_down_note_arr, $type);
        }
        $collect_filter['down'] = $this->getIndexData($v, $cj_down_from_arr, $cj_down_url_arr, $cj_down_server_arr, $cj_down_note_arr, '.mp4');
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
        foreach ($collect_filter[$type][$max_key] as $key_data_k => $val_data) {
            $collect_push = [];
            foreach ($key_data_new as $itemKey => $itemVal) {
                $key_url = $collect_filter[$type][$itemKey][$key_data_k] ?? '';
                if (!empty($key_url)) {
                    $collect_push[] = $key_url;
                }
            }

            //down_url
            //m3u8_url
            $collect_push = array_filter($collect_push);
            if (!empty($collect_push)) {
                if ($type == 'play') {
                    $new_play_url[$key_data_k]['m3u8_url'] = implode('#', $collect_push);
                } else {
                    $new_play_url[$key_data_k]['down_url'] = implode('#', $collect_push);
                }
            }
        }
        return $new_play_url;
    }


    protected function vodData($v, $title, $new_down_url, $k_p_play, $k_p_val, $i = 'i')
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
        if ($i == 'i') {
            $new_url['down_add_time'] = time();
        }
        $new_url['up_time'] = time();
        $new_url['down_time'] = time();
        $new_url['code'] = '-1';
        $new_url['vod_id'] = $v['vod_id'];
        $new_url['weight'] = '0';
        if ($i != 'i') {
            $new_url['weight'] = $v['b_weight'] ?? '0';
        } else {
            $b_weight = 98 - (2020 - $v['vod_year']);
            if ($b_weight < 0) {
                $b_weight = 0;
            }
            if ($b_weight > 99) {
                $b_weight = 98;
            }
            $new_url['weight'] = $b_weight;
        }
//        $new_url['weight'] = $v['vod_douban_score'] ?? '0';
        $new_url['down_url'] = $new_down_url[$k_p_play]['down_url'] ?? '';
        $new_url['m3u8_url'] = $k_p_val['m3u8_url'] ?? '';
        return $new_url;
    }

    protected function getFindVideo($id, $collection)
    {
        $where = [];
        $where['vod_id'] = $id;
        $where['collection'] = $collection;
        return Db::name('video_vod')->where($where)->find();

    }

    protected function getFindLikeVideo($id, $collection)
    {
        $where = [];
        $where['vod_id'] = $id;
        $where['m3u8_url'] =  array('like', '%'.$collection.'%');
        return Db::name('video_vod')->where($where)->find();
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
                    $title = findTitle($k_p_val, 0);
                    if (!empty($title)) {
                        $title = findNumAll($title);
                        if ($v['type_id_1'] == 0) {
                            $v['type_id_1'] = getTypePid($v['type_id']);
                        }
                        if ($v['type_id_1'] == 1) {
                            $title = 1;
                        }
                        if (in_array($v['type_id'],$this->zy_list)) {
                            $getFindVideo = $this->getFindLikeVideo($v['vod_id'], $k_p_play);
                        }else{
                            $getFindVideo = $this->getFindVideo($v['vod_id'], intval($title));
                        }
                        if (empty($getFindVideo)) {
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
                    }
                } else {
                    $title = findTitle($k_p_val, 0);
                    if (!empty($title)) {
                        $title = intval(findNumAll($title));
                        if ($v['type_id_1'] == 0) {
                            $v['type_id_1'] = getTypePid($v['type_id']);
                        }
                        if ($v['type_id_1'] == 1) {
                            $title = 1;
                        }

                        if (in_array($v['type_id'],$this->zy_list)){
                            $new_key = $k_p_play;
                        }else{
                            $new_key = $title;
                        }

                        if (isset($n[$new_key])) {
//                            print_r($k_p_val);
                            if ($n[$new_key]['is_sync'] != 1) {
                                $up_data = $this->vodData($v, $title, $new_down_url, $k_p_play, $k_p_val, 'u');
                                if ($up_data['m3u8_url'] != $v['b_m3u8_url']) {
                                    $res = $this->videoVodModel->where(['id' => $n[$new_key]['id']])->update($up_data);
                                    if ($res) {
                                        log::write('成功q-' . $n[$title]['id']);
                                    } else {
                                        log::write('失败q-' . $n[$title]['id']);
                                    }
                                }
                            }
                        } else {
                            if (in_array($v['type_id'],$this->zy_list)) {
                                $getFindVideo = $this->getFindLikeVideo($v['vod_id'], $k_p_play);
                            }else{
                                $getFindVideo = $this->getFindVideo($v['vod_id'], intval($title));
                            }
                            if (empty($getFindVideo)) {
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
    }


    /*
     * 获取date 数据
     */
    protected function getData1($where, $order, $page, $limit, $start)
    {
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = Db::name('vod')->where($where)->count();
        $list = Db::name('vod')->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }

    protected function getDataJoinit($where, $order, $page, $limit, $start)
    {

        $total = Db::name('vod')->alias('a')->field('a.vod_id,a.vod_year,a.type_id,a.type_id_1,a.vod_douban_score,a.vod_name,a.vod_down_url,a.vod_down_note,a.vod_down_server,a.vod_down_from,a.type_id,b.video_id as b_video_id,b.is_down,b.is_section,b.is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'LEFT')->where($where)->order($order)->count();
        return ceil($total / $limit);
    }

    protected function getDataJoini($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        return Db::name('vod')->alias('a')->field('a.vod_id,a.vod_year,a.type_id,a.vod_play_from,a.vod_play_server,a.vod_play_note,a.type_id_1,a.vod_play_url,a.vod_douban_score,a.vod_name,a.vod_down_url,a.vod_down_note,a.vod_down_server,a.vod_down_from,b.collection,a.type_id,b.video_id as b_video_id,b.is_down,b.is_section,b.is_sync,b.weight as b_weight')->join('video_vod b', 'a.vod_id=b.vod_id', 'LEFT')->where($where)->order($order)->limit($limit_str)->select();
    }

    protected function getDataJoinT($where, $order, $page, $limit, $start)
    {
        $total = Db::name('vod')->alias('a')->field('a.vod_id,a.vod_year,a.type_id,a.type_id_1,a.vod_douban_score,a.vod_name,a.vod_down_url,a.vod_down_note,a.vod_down_server,a.vod_down_from,a.type_id,b.video_id as b_video_id,b.is_down,b.is_section,b.is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'RIGHT')->group('b.vod_id')->where($where)->order($order)->count();
        $pagecount = ceil($total / $limit);
        return $pagecount;
    }

    protected function getDataJoin1($where, $order, $page, $limit, $start)
    {
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        return Db::name('vod')->alias('a')->field('a.vod_id,a.vod_year,a.type_id,a.vod_play_from,a.vod_play_server,a.vod_play_note,a.type_id_1,a.vod_play_url,a.vod_douban_score,a.vod_name,a.vod_down_url,b.is_down,a.vod_down_note,a.vod_down_server,a.vod_down_from,a.type_id,b.vod_name as b_vod_name,b.m3u8_url as b_m3u8_url,b.id as bid,b.vod_id as b_vod_id,b.weight as b_weight')->join('video_vod b', 'a.vod_id=b.vod_id', 'RIGHT')->group('b.vod_id')->where($where)->order($order)->limit($limit_str)->select();
    }

}