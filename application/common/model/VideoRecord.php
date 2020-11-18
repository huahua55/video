<?php

namespace app\common\model;
use think\Db;

class VideoRecord extends Base {
	// 设置数据表（不含前缀）
	protected $name = 'video_record';

	// 定义时间戳字段名
	protected $createTime = '';
	protected $updateTime = '';

	// 自动完成
	protected $auto = [];
	protected $insert = [];
	protected $update = [];

	/**
	 * 列表数据
	 * @param  [type]  $whereOr [description]
	 * @param  [type]  $where   [description]
	 * @param  [type]  $order   [description]
	 * @param  integer $page    [description]
	 * @param  integer $limit   [description]
	 * @param  integer $start   [description]
	 * @return [type]           [description]
	 */
	public function listData($whereOr = [], $where, $order, $page = 1, $limit = 20, $start = 0) {
		$limit_str = ($limit * ($page - 1) + $start) . "," . $limit;

		$field_a = 'id,vod_name,type,release_time';

		$total = $this->where($where)->limit($limit_str)->count();

		$list = $this->field($field_a)
			->where($where)
			->order($order)->limit($limit_str)->select();

		return ['code' => 1, 'msg' => '数据列表', 'page' => $page, 'pagecount' => ceil($total / $limit), 'limit' => $limit, 'total' => $total, 'list' => $list];
	}

	/**
	 * 详情
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	public function infoData($where, $field = '*') {
		if (empty($where) || !is_array($where)) {
			return ['code' => 1001, 'msg' => '参数错误'];
		}

        $field = 'id,vod_name';

		$info = $this->field($field)->where($where)->find();

		if (empty($info)) {
			return ['code' => 1002, 'msg' => '获取数据失败'];
		}
		$info = $info->toArray();

		return ['code' => 1, 'msg' => '获取成功', 'info' => $info];
	}

	/**
	 * 保存或者更新
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function saveData($data) {
		if (empty($data['vod_name'])) {
			return ['code' => 1001, 'msg' => '视频名称不能为空'];
		}

        $data['vod_name'] = htmlspecialchars(urldecode($data['vod_name']));
		if (!empty($data['id'])) {
			$where = [];
			$where['id'] = ['eq', $data['id']];
			$data['update_time'] = time();
			$res = $this->allowField(true)->where($where)->update($data);

		} else {
		    $data_info = $this->where(['vod_name'=> $data['vod_name']])->find();
		    if (empty($data_info)){
                $data['create_time'] = time();
                $data['update_time'] = time();
                unset($data['id']);
                $res = $this->allowField(true)->insert($data);
            }else{
                $res= true;
            }
		}
		if (false === $res) {
			return ['code' => 1002, 'msg' => '保存失败：' . $this->getError()];
		}
		return ['code' => 1, 'msg' => '保存成功'];
	}

    /**
     * 删除数据
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    public function delData($ids)
    {
        if (empty($ids)) {
            return ['code' => 1001, 'msg' => '参数错误'];
        }

        $where['id'] = ['in', $ids];

        $res = $this->where($where)->delete();
        if ($res === false) {
            return ['code' => 1001, 'msg' => '删除失败：' . $this->getError()];
        }
        return ['code' => 1, 'msg' => '删除成功'];
    }

}