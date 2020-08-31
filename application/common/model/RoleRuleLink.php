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
		if (empty($role_id) || empty($data)) {
			return ['code' => 1002, 'msg' => '参数错误'];
		}
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
		if (empty($where)) {
			return ['code' => 1001, 'msg' => '删除角色权限关系缺失条件'];
		}

		$res = $this->where($where)->delete();
		if ($res === false) {
			return ['code' => 1001, 'msg' => '删除角色权限关联关系失败：' . $this->getError()];
		}
		return ['code' => 1, 'msg' => '删除角色权限关联关系成功'];
	}

	/**
	 * 获取角色已关联权限
	 * @param  [type] $role_id [description]
	 * @return [type]          [description]
	 */
	public function getRoleHasLinkRule( $role_id ) {
		if (empty($role_id)) {
			return ['code' => 1001, 'msg' => '缺少角色id'];
		}

		$where['role_id'] = $role_id;
		$field = 'rule_id';
		$info = $this->field($field)->where($where)->select();
		$ids = array_column($info, 'rule_id');
		return ['code' => 1, 'msg' => '获取角色已关联权限成功', 'data' => $ids];
	}

	/**
     * 处理角色管理的权限
     * @param [type] $rule_ids [description]
     * @param [type] $role_id  [description]
     */
    public function addRoleLinkRule( $rule_ids, $role_id ) {
        if (empty($role_id) || empty($rule_ids) ) {
            return ['code' => 1001, 'msg' => '操作角色权限关系参数错误'];
        }
        // 根据角色id删除原有关系
        $del_where['role_id'] = ['eq', $role_id];
        $del_links = $this->delData($del_where);

        if ($del_links['code'] > 1) {
            return $del_links;
        }

        // 增加新的关系
        $data = [];
        foreach ($rule_ids as $k => $v) {
        	if (!empty($v) && !is_array($v)) {
        		$data[] = [
	                'role_id' => $role_id,
	                'rule_id' => $v,
	                'add_time' => time()
	            ];
        	}
        	
        	if (!empty($v) && is_array($v)) {
        		$data[] = [
	                'role_id' => $role_id,
	                'rule_id' => $k,
	                'add_time' => time()
	            ];
	            foreach ($v as $v1) {
	            	$data[] = [
		                'role_id' => $role_id,
		                'rule_id' => $v1,
		                'add_time' => time()
		            ];
	            }
	        }
        }

        $add = $this->insertAll($data);
        if ($add == count($data)) {
            return ['code' => 1, 'msg' => '操作角色权限关系成功'];
        }
        return ['code' => 1001, 'msg' => '操作角色权限关系失败'];
    }
}