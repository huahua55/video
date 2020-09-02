<?php

namespace app\common\model;

use think\Db;

class AdminRole extends Base {
	// 设置数据表（不含前缀）
	protected $name = 'admin_role';

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
	public function saveData($admin_id, $role_id) {
		if (empty($admin_id)) {
			return ['code' => 1002, 'msg' => '参数错误'];
		}

		$where['admin_id'] = $admin_id;
		if (!empty($role_id)) {
			// 用户是否已绑定角色
			$get_user_role = $this->getRoleByUserId($admin_id);
			if ($get_user_role['code'] > 1) {
				// 添加
				$data['admin_id'] = $admin_id;
				$data['role_id'] = $role_id;
				$data['add_time'] = time();
				$res = $this->allowField(true)->insert($data);
			} else {
				// 更新
				$data['update_time'] = time();
				$data['role_id'] = $role_id;
				$res = $this->allowField(true)->where($where)->update($data);
			}
		} else {
			// 删除角色
			$del_role = $this->delData($where);
			if ($del_role['code'] > 1) {
				return $del_role;
			}
		}

		// 更新用户权限
		$get_rule_by_role_id = model('role_rule_link')->getRuleByRoleId($role_id);
		if ($get_rule_by_role_id['code'] == 1) {
            $admin_where['admin_id'] = ['eq', $admin_id];
            $admin_edit_data['admin_auth'] = $get_rule_by_role_id['data'];

            $edit_admin = model('admin')->where($admin_where)->update($admin_edit_data);

        } else {
            return $get_rule_by_role_id;
        }
		
		if (false !== $res && $edit_admin !== false) {
			return ['code' => 1, 'msg' => '保存成功'];
		}
		return ['code' => 1002, 'msg' => '保存失败：' . $this->getError()];
	}

	/**
	 * 删除操作
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	public function delData($where) {
		if (empty($where)) {
			return ['code' => 1001, 'msg' => '删除角色缺失条件'];
		}

		$res = $this->where($where)->delete();
		if ($res === false) {
			return ['code' => 1001, 'msg' => '删除角色失败：' . $this->getError()];
		}
		return ['code' => 1, 'msg' => '删除角色成功'];
	}

	/**
	 * 获取角色已关联权限
	 * @param  [type] $role_id [description]
	 * @return [type]          [description]
	 */
	public function roleHasLinkUser( $role_id ) {
		if (empty($role_id)) {
			return ['code' => 1001, 'msg' => '缺少角色id'];
		}

		$where['role_id'] = $role_id;
		$field = 'id';
		$info = $this->field($field)->where($where)->find();
		if (empty($info)) {
			return ['code' => 1, 'msg' => '该角色未关联用户'];
		}
		return ['code' => 1001, 'msg' => '该角色已关联用户'];
	}

	/**
     * 根据管理员id获取角色
     * @param [type] $rule_ids [description]
     * @param [type] $role_id  [description]
     */
    public function getRoleByUserId( $admin_id ) {
        if (empty($admin_id)) {
            return ['code' => 1001, 'msg' => '缺失管理员id'];
        }
        $where['admin_id'] = $admin_id;
        $field = 'a.role_id,r.role_name';
       	$info = $this->alias('a')->field($field)
       					->join('roles r', 'a.role_id=r.id', 'left')
       					->where($where)->find();

       	if (!empty($info)) {
            return ['code' => 1, 'msg' => '获取管理员角色成功', 'data' => $info];
        }
        return ['code' => 1001, 'msg' => '未获取到管理员角色', 'data' => []];
    }
}