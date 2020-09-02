<?php

namespace app\common\model;
use think\Db;

class Roles extends Base {
	// 设置数据表（不含前缀）
	protected $name = 'roles';

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

		$field_a = 'id,role_name,status';

        if (empty($whereOr)) {
            $where_str = '';
        } else {
            $where_str = "INSTR(`role_name`,'" . $whereOr['role_name'] . "') > 0 OR `id` = '" .  $whereOr['role_name'] . "'";
        }

        // 管理员id为1和2超级管理员看全部
        $admin_id = cookie('admin_id');
        if (!in_array($admin_id, [1, 2])) {
            $where['create_admin_id'] = $admin_id;
        }

		$total = $this->where($where_str)->where($where)->limit($limit_str)->count();

		$list = $this->field($field_a)
			->where($where_str)
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
		$validate = \think\Loader::validate('roles');
		if (!$validate->check($data)) {
			return ['code' => 1001, 'msg' => '参数错误：' . $validate->getError()];
		}


        $data['role_name'] = htmlspecialchars(urldecode($data['role_name']));
        Db::startTrans();
		if (!empty($data['id'])) {
			$where = [];
			$where['id'] = ['eq', $data['id']];
			$data['update_time'] = time();
            $data['update_admin_id'] = cookie('admin_id');
			$res = $this->allowField(true)->where($where)->update($data);

            $role_id = $data['id'];
		} else {
            $data['create_admin_id'] = cookie('admin_id');
			$data['add_time'] = time();
			$res = $this->allowField(true)->insert($data);

            $role_id = $this->getLastInsID();
		}

        if (!empty($data['rule_ids'])) {
            // 处理角色关联的权限
            $set_role_link_rule = model('role_rule_link')->addRoleLinkRule( $data['rule_ids'], $role_id );
            if ($set_role_link_rule['code'] > 1) {
                Db::rollback();
                return $set_role_link_rule;
            }
        }

        // 同步更新角色
        $edit_admin_auth = self::_editAdminAuth($role_id);
        if ($edit_admin_auth['code'] > 1) {
            Db::rollback();
            return $edit_admin_auth;
        }

        // 同步更新角色组
        $edit_admin_group_auth = self::_editAdminGroupAuth($role_id);
        if ($edit_admin_group_auth['code'] > 1) {
            Db::rollback();
            return $edit_admin_group_auth;
        }
        
		if (false === $res) {
            Db::rollback();
			return ['code' => 1002, 'msg' => '保存失败：' . $this->getError()];
		}
        Db::commit();
		return ['code' => 1, 'msg' => '保存成功'];
	}

	/**
	 * 自动修改
	 * @param  [type] $where [description]
	 * @param  [type] $col   [description]
	 * @param  [type] $val   [description]
	 * @return [type]        [description]
	 */
	public function fieldData($ids, $col, $val) {
		if (empty($ids) || empty($col)) {
			return ['code' => 1001, 'msg' => '参数错误'];
		}

		$data = [];

		$data[$col] = $val;
		$data['update_time'] = time();

		$where['id'] = ['in', $ids];

		$res = $this->where($where)->update($data);
		if ($res === false) {
			return ['code' => 1002, 'msg' => '设置失败' . $this->getError()];
		}
		return ['code' => 1, 'msg' => '设置成功'];
	}

	/**
	 * 删除操作
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	public function delData($ids) {

        if (empty($ids)) {
            return ['code' => 1001, 'msg' => '参数错误'];
        }

        // 角色是否已关联用户
        $role_has_link_user = model('admin_role')->roleHasLinkUser($ids);

        if ($role_has_link_user['code'] > 1) {
            return $role_has_link_user;
        }

        Db::startTrans();
        // 删除关联关系表
        $del_where['role_id'] = ['in', $ids];
        $del_links = model('role_rule_link')->delData($del_where);

        if ($del_links['code'] > 1) {
            Db::rollback();
            return $del_links;
        }

        $where['id'] = ['in', $ids];
		$res = $this->where($where)->delete();
		if ($res === false) {
            Db::rollback();
			return ['code' => 1001, 'msg' => '删除失败：' . $this->getError()];
		}
        Db::commit();
		return ['code' => 1, 'msg' => '删除成功'];
	}

    /**
     * 可用的角色列表
     * @return [type] [description]
     */
    public function allRoleList() {
        // 管理员id为1和2超级管理员看全部
        $admin_id = cookie('admin_id');
        if (!in_array($admin_id, [1, 2])) {
            $get_role = model('admin_role')->getRoleByUserId( $admin_id );
            $where['create_admin_id'] = $admin_id;
            $where['parent_id'] = $get_role['data']['role_id'];
        }
        $where['status'] = 1;
        $field = 'id,role_name,parent_id';
        $role_infos = $this->field($field)->where($where)->select();
        if (!in_array($admin_id, [1, 2])) {
            return $role_infos;
        } else {
            return self::_formatRole($role_infos);
        }
    }

    /**
     * 格式化角色 超级管理员使用
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function _formatRole($data) {
        $list = [];
        foreach (objectToArray($data) as $k => $v) {
            if ($v['parent_id'] == 0) {
                $list[$v['id']] = $v; 
            } else {
                $list[$v['parent_id']]['children'][] = $v; 
            }
        }
        return $list;
    }

    /**
     * 同步更新用户权限   避免用户登录更新
     * @param  [type] $role_id [description]
     * @return [type]          [description]
     */
    private function _editAdminAuth($role_id) {
        $admin_info = model('admin_role')->alias('r')
                        ->field('a.admin_id')
                        ->join('admin a', 'a.admin_id=r.admin_id', 'left')
                        ->where('r.role_id', $role_id)
                        ->select();
        $get_rule_by_role_id = model('role_rule_link')->getRuleByRoleId($role_id);
        $admin_ids = array_column($admin_info, 'admin_id');

        if ($get_rule_by_role_id['code'] == 1) {
            $admin_where['admin_id'] = ['in', $admin_ids];
            $admin_edit_data['admin_auth'] = $get_rule_by_role_id['data'];

            $edit_admin = model('admin')->where($admin_where)->update($admin_edit_data);
            if ($edit_admin !== false) {
                return ['code' => 1, 'msg' => '更新用户权限成功'];
            } else {
                return ['code' => 1001, 'msg' => '更新用户权限失败'];
            }
        } else {
            return $get_rule_by_role_id;
        }
    }

    /**
     * 同步更新角色组
     * @param  [type] $role_id [description]
     * @return [type]          [description]
     */
    private function _editAdminGroupAuth($role_id) {
        // 是否是角色组
        $is_role_group = $this->alias('r')->field('l.role_id,group_concat(l.rule_id) as rule_ids,a.admin_id')
                            ->join('role_rule_link l', 'r.id=l.role_id', 'left')
                            ->join('admin_role a', 'r.id = a.role_id', 'left')
                            ->where('r.parent_id', $role_id)
                            ->group('l.role_id')
                            ->select();
        if (empty($is_role_group)) {
            return ['code' => 1, 'msg' => '该角色不属于角色组'];
        }
        $rule_info = model('role_rule_link')->getRoleHasLinkRule($role_id);
        if ($rule_info['code'] > 1) {
            return $rule_info;
        }
        $rule_ids = $rule_info['data'];
        
        foreach ($is_role_group as $key => $value) {
            $old_rule_ids = explode(',', $value['rule_ids']);

            $role_diff = array_diff($old_rule_ids,$rule_ids);
            if (count($role_diff) > 0) {
                // 角色存在更新  需要删除
                $role_rule_link_where['role_id'] = $value['role_id'];
                $role_rule_link_where['rule_id'] = ['in', $role_diff];
                model('role_rule_link')->where($role_rule_link_where)->delete();

                $get_rule_by_role_id = model('role_rule_link')->getRuleByRoleId($value['role_id']);
                $admin_where['admin_id'] = ['eq', $value['admin_id']];
                $admin_edit_data['admin_auth'] = $get_rule_by_role_id['data'];
                model('admin')->where($admin_where)->update($admin_edit_data);
            }
        }
        return ['code' => 1, 'msg' => '更新角色组成功'];
        
    }
    /**
     * 根据角色id和创建角色的用户id获取该角色组下的用户
     * @param  [type] $role_id  [description]
     * @param  [type] $admin_id [description]
     * @return [type]           [description]
     */
    public function getRoleGroupAdminIdsByRoleId($role_id, $create_admin_id) {
        if (empty($role_id) || empty($create_admin_id)) {
            return ['code' => 1001, 'msg' => '参数错误'];
        }
        $roles_where['r.parent_id'] = $role_id;
        $roles_where['r.create_admin_id'] = $create_admin_id;
        $admin_info = $this->alias('r')->field('a.admin_id')
                        ->join('admin_role a', 'a.role_id=r.id', 'inner')
                        ->where($roles_where)
                        ->select();
        $admin_ids = array_unique(array_column($admin_info, 'admin_id'));
        return ['code' => 1, 'msg' => '获取成功', 'data' => $admin_ids];
    }
}