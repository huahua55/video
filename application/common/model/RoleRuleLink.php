<?php

namespace app\common\model;

use think\Db;

class RoleRuleLink extends Base {
	// 设置数据表（不含前缀）
	protected $name = 'role_rule_link';

	// 定义时间戳字段名
	protected $createTime = '';
	protected $updateTime = '';

	// 自动完成
	protected $auto = [];
	protected $insert = [];
	protected $update = [];

	/**
	 * 保存或者更新
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function saveData($role_id, $data) {
		Db::startTrans();
		// 删除角色权限关联关系
		$del_where['role_id'] = $role_id;
		$del_links = $this->delData($del_where);
		if ($del_links['code'] > 1) {
			Db::rollback();
			return $del_links;
		}
		$count = count($data);

		$data['add_time'] = time();
		$res = $this->allowField(true)->insertAll($data);
		if ($count == $res) {
			Db::rollback();
			return ['code' => 1002, 'msg' => '保存失败：' . $this->getError()];
		}
		Db::commit();    
		return ['code' => 1, 'msg' => '保存成功'];
	}

	/**
	 * 删除操作
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	public function delData($where) {
		$res = $this->where($where)->delete();
		if ($res === false) {
			return ['code' => 1001, 'msg' => '删除角色权限关联关系失败：' . $this->getError()];
		}
		return ['code' => 1, 'msg' => '删除角色权限关联关系成功'];
	}
}