<?php

namespace app\common\model;

use think\Db;
use think\Cache;
use think\helper\Arr;
use think\Log;

class TaskLog extends Base
{
    // 设置数据表（不含前缀）
    protected $name = 'task_log';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

    // 自动完成
    protected $auto = [];
    protected $insert = [];
    protected $update = [];

    public function listData($whereOr = [], $where, $order, $page = 1, $limit = 20, $start = 0)
    {


//        if (empty($where) && empty($whereOr)) {
//            return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>0,'limit'=>$limit,'total'=>0,'list'=>[]];
//        }
        $video_domain = Db::table('video_domain')->find();

        $video_examine = Db::table('video_examine')->column(null, 'id');
        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $inId = Db::table('task_log')
            ->where(function ($query) use ($whereOr) {
                $query->whereOr($whereOr);
            })->where($where)->group('video_id')->order($order)->limit($limit_str)->column('video_id');
//        p($inId);
        $inId = array_unique($inId);
        $total = Db::table('task_log')->whereIn('video_id', $inId)->group('video_id')->count();

        $list = Db::table('task_log')->whereIn('video_id', $inId)->order($order)->select();

        $getVideoCount = array_column(Db::table('task_log')->field('video_id,count(id) as video_js')->whereIn('video_id', $inId)->group('video_id')->select(), 'video_js', 'video_id');
        # vod
        $array_vod_id = array_diff(array_unique(array_column($list, 'vod_id')), [0]);
        $array_vod = Db::name('vod')->field('vod_id,type_id,type_id_1,vod_name,vod_sub,vod_en,vod_class,vod_pic,vod_pic_thumb,vod_pic_slide,vod_actor,vod_director,vod_writer,vod_behind,vod_remarks,vod_pubdate,vod_total,vod_serial,vod_weekday,vod_area,vod_lang,vod_year,vod_version,vod_time,vod_time_add')->whereIn('vod_id', $array_vod_id)->select();
        $array_vod = array_column($array_vod, null, 'vod_id');

        # video
        $array_video_vod = Db::name('video')->whereIn('id', $inId)->column(null, 'id');
        # video_collection
        $array_vod_collection_vods = Db::name('video_collection')->field('id,video_id,task_id,title,collection,vod_url,type,status,e_id,is_examine,resolution,bitrate,duration,size,is_selected')->whereIn('video_id', $inId)->select();
        $array_vod_collection_vod = [];
        foreach ($array_vod_collection_vods as $value) {
            $array_vod_collection_vod[$value['task_id']] = $value;
        }
        $q = [];
        $q_list = [];
        foreach ($list as $key_video_vod => &$val_video_vod) {
           $ii =  $val_video_vod['collection'];
//            p($array_video_vod[$val_video_vod['video_id']]['type_pid']);
            $val_video_vod['type_pid'] = isset($array_video_vod[$val_video_vod['video_id']]['type_pid']) ? $array_video_vod[$val_video_vod['video_id']]['type_pid'] : 0;
            $val_video_vod['vod_pic'] = isset($array_video_vod[$val_video_vod['video_id']]['vod_pic']) ? $array_video_vod[$val_video_vod['video_id']]['vod_pic'] : '';
            if (substr_count($val_video_vod['vod_pic'], 'http') == 0) {
                $val_video_vod['vod_pic'] = $video_domain['img_domain'] . $val_video_vod['vod_pic'];
            }
            $t = $val_video_vod['video_id'];
            if (!isset($q[$t])) {
                $val_video_vod['is_master'] = 1;
                $val_video_vod['bid'] = $val_video_vod['video_id'] . '_' . $val_video_vod['video_id'];
                $val_video_vod['pid'] = 0;
                if (isset($array_vod[$val_video_vod['vod_id']])) {
                    $video_total = 1;
                    if ($array_vod[$val_video_vod['vod_id']]['type_id_1'] == 1) {
                        // 电影 总集数默认为1
                        $video_total = 1;
                    } else {
                        if ($array_vod[$val_video_vod['vod_id']]['type_id'] >= 6 && $array_vod[$val_video_vod['vod_id']]['type_id'] <= 12) {
                            $video_total = 1;
                        } else {
                            if (isset($array_vod[$val_video_vod['vod_id']])) {
                                $video_total = $array_vod[$val_video_vod['vod_id']]['vod_total'];
                            }
                        }
                    }
                    $val_video_vod['collection'] = $video_total . '-' . $getVideoCount[$val_video_vod['video_id']] ?? 0;
                } else {
                    $val_video_vod['collection'] = 1 . '-' . $getVideoCount[$val_video_vod['video_id']] ?? 0;
                }
                $val_video_vod['m_reasons'] = isset($video_examine[$array_video_vod[$val_video_vod['video_id']]['e_id']]) ? $video_examine[$array_video_vod[$val_video_vod['video_id']]['e_id']] : '';
                $val_video_vod['m_eid'] = isset($array_video_vod[$val_video_vod['video_id']]['e_id']) ? $array_video_vod[$val_video_vod['video_id']]['e_id'] : 0;
                $val_video_vod['vod_actor'] = isset($array_video_vod[$val_video_vod['video_id']]['vod_actor']) ? $array_video_vod[$val_video_vod['video_id']]['vod_actor'] : '';
                $val_video_vod['vod_director'] = isset($array_video_vod[$val_video_vod['video_id']]['vod_director']) ? $array_video_vod[$val_video_vod['video_id']]['vod_director'] : '';
                $val_video_vod['m_status'] = $val_video_vod['video_status'];
                $q_list[] = $val_video_vod;
                $q[$t] = 1;
            }
            $val_video_vod['collection'] =$ii;
            $val_video_vod['m_status'] = $val_video_vod['collection_status'];
            $val_video_vod['m_eid'] = isset($array_vod_collection_vod[$val_video_vod['video_vod_id']]['e_id']) ? $array_vod_collection_vod[$val_video_vod['video_vod_id']]['e_id'] : 0;
            $val_video_vod['is_master'] = 0;
            $val_video_vod['vod_url'] = $video_domain['vod_domain'] . $array_vod_collection_vod[$val_video_vod['video_vod_id']]['vod_url'];
            $val_video_vod['m_reasons'] = isset($video_examine[$array_vod_collection_vod[$val_video_vod['video_vod_id']]['e_id']]) ? $video_examine[$array_vod_collection_vod[$val_video_vod['video_vod_id']]['e_id']] : '';
            $val_video_vod['bid'] = $val_video_vod['collection_id'];
            $val_video_vod['m_is_examine'] = $val_video_vod['collection_is_examine'];
            $val_video_vod['pid'] = $val_video_vod['video_id'] . '_' . $val_video_vod['video_id'];
            $val_video_vod['vod_actor'] = '';
            $val_video_vod['vod_director'] = '';
            $q_list[] = $val_video_vod;
        }
        unset($list);
        unset($q);
        unset($getVideoCount);
        unset($array_vod);
        unset($array_video_vod);
        unset($array_vod_collection_vod);
        //分类
        return ['code' => 1, 'msg' => '数据列表', 'page' => $page, 'pagecount' => ceil($total / $limit), 'limit' => $limit, 'total' => $total, 'list' => $q_list];
    }

    public function listData1($where, $order, $page = 1, $limit = 20, $start = 0)
    {
        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = Db::table('video_examine')->where($where)->order($order)->count();
        $list = Db::table('video_examine')->where($where)->order($order)->limit($limit_str)->select();
        return ['code' => 1, 'msg' => '数据列表', 'page' => $page, 'pagecount' => ceil($total / $limit), 'limit' => $limit, 'total' => $total, 'list' => $list];
    }

    public function listCacheData($lp)
    {
        if (!is_array($lp)) {
            $lp = json_decode($lp, true);
        }

        $order = $lp['order'];
        $by = $lp['by'];
        $type = $lp['type'];
        $start = intval(abs($lp['start']));
        $num = intval(abs($lp['num']));
        $cachetime = $lp['cachetime'];

        $page = 1;
        $where = [];

        if (empty($num)) {
            $num = 20;
        }
        if ($start > 1) {
            $start--;
        }
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }
        if (!in_array($by, ['id', 'sort'])) {
            $by = 'id';
        }
        if (!empty($type)) {
            if ($type == 'current') {
                $type = intval($GLOBALS['type_id']);
            }
        }
        $where['type_id'] = $type;

        $order = $by . ' ' . $order;

        $cach_name = $GLOBALS['config']['app']['cache_flag'] . '_' . md5('vod_recommend_listcache_' . join('&', $where) . '_' . $order . '_' . $page . '_' . $num . '_' . $start);
        $res = Cache::get($cach_name);
        if ($GLOBALS['config']['app']['cache_core'] == 0 || empty($res)) {
            $res = $this->listData($where, $order, $page, $num, $start);
            $cache_time = $GLOBALS['config']['app']['cache_time'];
            if (intval($cachetime) > 0) {
                $cache_time = $cachetime;
            }
            if ($GLOBALS['config']['app']['cache_core'] == 1) {
                Cache::set($cach_name, $res, $cache_time);
            }
        }
        if ($res['list']) {
            foreach ($res['list'] as &$v) {
                if ($v['rel_ids']) {
                    $arr_vod_ids = explode(',', $v['rel_ids']);
                    $lp = [
                        'ids' => $v['rel_ids']
                    ];
                    $vods = array_column(model("Vod")->listCacheData($lp)['list'], null, 'vod_id');
                    foreach ($arr_vod_ids as $vod_id) {
                        // $v['rel_vod'][] = Arr::only($vods[$vod_id], ['vod_id', 'vod_name', 'vod_pic']);
                        if (isset($vods[$vod_id])) {
                            $v['rel_vod'][] = $vods[$vod_id];
                        }
                    }
                }

            }
            unset($v);
        }
        return $res;
    }

    public function infoData($where, $field = '*')
    {
        if (empty($where) || !is_array($where)) {
            return ['code' => 1001, 'msg' => '参数错误'];
        }
        $info = $this->field($field)->where($where)->find();

        if (empty($info)) {
            return ['code' => 1002, 'msg' => '获取数据失败'];
        }
        $info = $info->toArray();

        return ['code' => 1, 'msg' => '获取成功', 'info' => $info];
    }

    public function saveData($data)
    {
        $data['down_time'] = time();
        if (!empty($data['id'])) {
            $where = [];
            $where['id'] = ['eq', $data['id']];
            unset($data['down_time']);
            $res = $this->allowField(true)->where($where)->update($data);
        } else {
            $res = $this->allowField(true)->insert($data);
        }
        if (false === $res) {
            return ['code' => 1002, 'msg' => '保存失败：' . $this->getError()];
        }
        return ['code' => 1, 'msg' => '保存成功'];
    }

    public function delData($where)
    {
        $res = $this->where($where)->delete();
        if ($res === false) {
            return ['code' => 1001, 'msg' => '删除失败：' . $this->getError()];
        }
        return ['code' => 1, 'msg' => '删除成功'];
    }

    public function fieldData($where, $col, $val)
    {
        if (!isset($col) || !isset($val)) {
            return ['code' => 1001, 'msg' => '参数错误'];
        }

        $data = [];
        $data[$col] = $val;
        $res = $this->allowField(true)->where($where)->update($data);
        if ($res === false) {
            return ['code' => 1001, 'msg' => '设置失败：' . $this->getError()];
        }
        return ['code' => 1, 'msg' => '设置成功'];
    }

    /**
     * 更新权重
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function editWeight($data)
    {
        $validate = \think\Loader::validate('VideoVod');
        if (!$validate->check($data)) {
            return ['code' => 1001, 'msg' => '参数错误：' . $validate->getError()];
        }
        if (!empty($data['id'])) {
            Db::startTrans();
            $where['vod_id'] = ['eq', $data['id']];
            unset($data['id']);
            unset($data['vod_id']);
            $data['up_time'] = time();
            $res = $this->allowField(true)->where($where)->update($data);
        } else {
            return ['code' => 1001, 'msg' => '参数错误'];
        }
        if (false === $res) {
            Db::rollback();
            return ['code' => 1002, 'msg' => '保存失败：' . $this->getError()];
        }
        Db::commit();
        return ['code' => 1, 'msg' => '保存成功'];
    }

}