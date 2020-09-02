<?php

namespace app\common\model;

use Symfony\Component\DependencyInjection\Tests\Compiler\D;
use think\Db;
use think\Cache;
use app\common\util\Pinyin;

# 11.23
class Task extends Base
{
    // 设置数据表（不含前缀）
    protected $name = 'task';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    protected $autoWriteTimestamp = true;

    // 自动完成
    protected $auto = [];
    protected $insert = [];
    protected $update = [];


    public function getAddTypeTextAttr($t = '')
    {
        $arr = [1 => '上映年代', 2 => '自动更新时间', 3 => '添加入库时间',4 => '豆瓣权重',];
        if ($t != '') {
            return $arr[$t];
        } else {
            return $arr;
        }
    }

    public function getAdminRoletextAttr($t = '')
    {
        $admin_id = Db::name('admin_role')->field('admin_id')->where(['role_id' => 3])->column('admin_id');
        $arr = Db::name('admin')->where(['admin_status' => 1])->whereIn('admin_id', $admin_id)->column('admin_name', 'admin_id');
        if ($t != '') {
            return $arr[$t];
        } else {
            return $arr;
        }
    }

    public function getTopicStatusTextAttr($val, $data)
    {
        $arr = [0 => '禁用', 1 => '启用'];
        return $arr[$data['topic_status']];
    }

    public function countData($where)
    {
        $total = $this->where($where)->count();
        return $total;
    }

    public function listData($where, $order, $page = 1, $limit = 20, $start = 0, $field = '*', $totalshow = 1)
    {
        $admin_array = Db::name('admin')->column('admin_name', 'admin_id');
        if (!is_array($where)) {
            $where = json_decode($where, true);
        }
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        if ($totalshow == 1) {
            $total = $this->where($where)->count();
        }
//        p($where);

        $list = Db::name('task')->where($where)->order($order)->limit($limit_str)->select();
        foreach ($list as $k => $v) {
            if($v['success_sum'] >= $v['total_sum']){
                $list[$k]['status'] = 1;
            }else{
                $list[$k]['status'] = 0;
            }
            $list[$k]['ad_user_id_name'] = $admin_array[$v['ad_user_id']] ?? '';
            $list[$k]['admin_id_name'] = $admin_array[$v['admin_id']] ?? '';
            $list[$k]['add_type_name'] = $this->getAddTypeTextAttr($v['add_type']);
            if ($v['task_type'] == 1) {
                $list[$k]['task_type_name'] = '自动分配';
            } else {
                $list[$k]['task_type_name'] = '手动分配';
            }
        }


        return ['code' => 1, 'msg' => '数据列表', 'page' => $page, 'pagecount' => ceil($total / $limit), 'limit' => $limit, 'total' => $total, 'list' => $list];
    }


    public function infoData($where, $id)
    {
        $add_type_array = $this->getAddTypeTextAttr();

        if (empty($id)) {
            $info['add_type_array'] = $add_type_array;
            $info['ad_user_id_array'] = $this->getAdminRoletextAttr();
            $info['ad_user_id'] = array_shift(array_keys($info['ad_user_id_array']));
            $info['add_type'] = 1;
            $info['total_sum'] = 100;
            $info['task_date'] = date("Y-m-d", time());
        } else {
            $info = Db::name('Task')->where($where)->find();
            $info['add_type_array'] = $add_type_array;
            $info['ad_user_id_array'] = $this->getAdminRoletextAttr();
        }

        return ['code' => 1, 'msg' => '获取成功', 'info' => $info];
    }

//    public function date_log($orby,$total_sum)
//    {
//        $log_sql = "select id as video_vod_id,vod_id,video_id,vod_name from video_vod where vod_id in (select tg.vod_id from (SELECT vv.vod_id FROM `task_log` RIGHT  JOIN `video_vod` `vv` ON `vv`.`vod_id`=`task_log`.`vod_id` WHERE task_log.id IS Null and vv.is_sync>0   GROUP BY vv.vod_id  HAVING  min(vv.is_examine) = 0  ORDER BY ".$orby." LIMIT " . $total_sum . ") as tg)";
//        return Db::query($log_sql);
//    }
    public function date_log($orby,$total_sum)
    {
        $log_sql = "select v.vod_id, v.id as video_id,v.vod_status as video_status,v.is_examine as video_is_examine,v.vod_name,vc.task_id as video_vod_id,vc.collection,vc.id as collection_id,vc.`status` as collection_status,vc.is_examine as collection_is_examine from video as v  RIGHT JOIN video_collection as vc on v.id = vc.video_id  where vc.video_id in (select tg.id from (SELECT vv.id FROM `task_log` RIGHT  JOIN `video` `vv` ON `vv`.`id`=`task_log`.`video_id` WHERE task_log.id IS Null  GROUP BY vv.id ORDER BY ".$orby." LIMIT " . $total_sum . ") as tg)";
        return Db::query($log_sql);
    }



    public function saveData($data)
    {
        Db::startTrans();
        try {
            $res = '';
            if (!empty($data['id'])) {
                $where = [];
                $where['id'] = ['eq', $data['id']];
                $res_data = Db::name('task')->where($where)->find();
                if (!empty($res_data)){
                    if($res_data['total_sum'] > $data['total_sum']){
                        $total_sum = $res_data['total_sum'] - $data['total_sum'];
                        if($total_sum > 0){
                            $sql = "delete from task_log where vod_id in (select a.vod_id from (SELECT vod_id from task_log where ad_user_id = '".$res_data['ad_user_id']."' and task_date = '".$res_data['task_date']."' GROUP BY vod_id HAVING MIN(`status`) = 0 LIMIT ".$total_sum.") as a)";
                            $res1 = Db::query($sql);
                        }
                        $res = Db::name('task')->where($where)->update($data);
                    }else{
                        $total_sum = $data['total_sum'] - $res_data['total_sum'];
                        $orby = "vv.vod_year";
                        if($data['add_type'] == 1){
                            $orby = "vv.vod_year";
                        }elseif ($data['add_type'] == 2){
                            $orby = "vv.vod_time_auto_up";
                        }elseif ($data['add_type'] == 3){
                            $orby = "vv.vod_time_add";
                        }elseif ($data['add_type'] == 4){
                            $orby = "vv.vod_douban_score";
                        }
                        $date_log_list = $this->date_log($orby,$total_sum);
                        foreach ($date_log_list as $k => $v) {
                            $date_log_list[$k]['task_id'] = $data['id'];
                            $date_log_list[$k]['add_type'] = $data['add_type'];
                            $date_log_list[$k]['task_date'] = $data['task_date'];
                            $date_log_list[$k]['ad_user_id'] = $data['ad_user_id'];
                            $date_log_list[$k]['task_type'] = $data['task_type']??2;
                            $date_log_list[$k]['status'] = $data['status']??0;
                            $date_log_list[$k]['add_time'] = time();
                            $date_log_list[$k]['log'] = '添加';
                        }
                        $res1 = Db::name('task_log')->insertAll($date_log_list);
                        $res = Db::name('task')->where($where)->update($data);
                    }
                }
            } else {
                $data['add_time'] = time();
                $data['admin_id'] = cookie('admin_id');
                $data['task_type'] = 2;
                $data['status'] = 0;
                $orby = "vv.vod_year";
                if($data['add_type'] == 1){
                    $orby = "vv.vod_year";
                }elseif ($data['add_type'] == 2){
                    $orby = "vv.vod_time_auto_up";
                }elseif ($data['add_type'] == 3){
                    $orby = "vv.vod_time_add";
                }elseif ($data['add_type'] == 4){
                    $orby = "vv.vod_douban_score";
                }
                $res_id = Db::name('task')->insertGetId($data);
                $date_log_list = $this->date_log($orby,$data['total_sum']);
                foreach ($date_log_list as $k => $v) {
                    $date_log_list[$k]['task_id'] = $res_id;
                    $date_log_list[$k]['add_type'] = $data['add_type'];
                    $date_log_list[$k]['task_date'] = $data['task_date'];
                    $date_log_list[$k]['ad_user_id'] = $data['ad_user_id'];
                    $date_log_list[$k]['task_type'] = $data['task_type']??2;
                    $date_log_list[$k]['status'] = $data['status']??0;
                    $date_log_list[$k]['add_time'] = time();
                    $date_log_list[$k]['log'] = '添加';
                    $res = Db::name('task_log')->insert($date_log_list[$k]);
                }
            }
            if($res){
                Db::commit();
            }else{
                throw new \Exception('添加失败');
            }
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 1002, 'msg' => '保存失败：' . $e->getMessage()];
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
            return ['code' => 1002, 'msg' => '设置失败：' . $this->getError()];
        }

        $list = $this->field('topic_id,topic_name,topic_en')->where($where)->select();
        foreach ($list as $k => $v) {
            $key = 'topic_detail_' . $v['topic_id'];
            Cache::rm($key);
            $key = 'topic_detail_' . $v['topic_en'];
            Cache::rm($key);
        }

        return ['code' => 1, 'msg' => '设置成功'];
    }


}