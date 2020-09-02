<?php

namespace app\common\model;

class Rule extends Base {
	// 设置数据表（不含前缀）
	protected $name = 'rule';

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

		$field_a = 'id,rule_name,status,controller,action,parent_id';

        if (empty($whereOr)) {
            $where_str = '';
        } else {
            $where_str = "INSTR(`rule_name`,'" . $whereOr['rule_name'] . "') > 0 OR `id` = '" .  $whereOr['rule_name'] . "'";
        }
        $where['parent_id'] = 0;

		$total = $this->where($where_str)->where($where)->limit($limit_str)->count();

		$parents = $this->field($field_a)
			->where($where_str)
			->where($where)
			->order($order)->limit($limit_str)->select();

		$parent_ids = array_unique(array_column($parents, 'id'));
		unset($where['parent_id']);

		$where['parent_id'] = ['in', $parent_ids];
		$childrens = $this->field($field_a)
			->where($where_str)
			->where($where)
			->order($order)->select();
		$list = $childrens;

		foreach ($parents as $v) {
			$list[] = $v;
		}

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
		$validate = \think\Loader::validate('rule');
		if (!$validate->check($data)) {
			return ['code' => 1001, 'msg' => '参数错误：' . $validate->getError()];
		}

		if (!empty($data['id'])) {
			$where = [];
			$where['id'] = ['eq', $data['id']];
			$data['update_time'] = time();
			$res = $this->allowField(true)->where($where)->update($data);
		} else {
			$data['add_time'] = time();
			$res = $this->allowField(true)->insert($data);
		}
		if (false === $res) {
			return ['code' => 1002, 'msg' => '保存失败：' . $this->getError()];
		}
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
	public function delData($ids, $is_master) {
		if (empty($ids)) {
            return ['code' => 1001, 'msg' => '参数错误'];
        }

        // 如果是父级，则删除自己即下级
        $whereOr = [];
        if ($is_master == 0) {
        	$whereOr['parent_id'] = ['in', $ids];
        }
		$where['id'] = ['in', $ids];
		$res = $this->where($where)->whereOr($whereOr)->delete();
		if ($res === false) {
			return ['code' => 1001, 'msg' => '删除失败：' . $this->getError()];
		}
		return ['code' => 1, 'msg' => '删除成功'];
	}

	/**
	 * 获取父级分类
	 * @return [type] [description]
	 */
	public function getParentList($role_id = '') {
		$rule_info['data'] = [];
        if (!empty($role_id)) {
        	$rule_info = model('role_rule_link')->getRoleHasLinkRule( $role_id );
        	if (!empty($rule_info['data'])) {
        		$where['id'] = ['in', $rule_info['data']];
        	} else {
        		return [];
        	}
        }
		$where['parent_id'] = 0;
		$where['status'] = 1;
		$field = 'id,rule_name,parent_id';
		$list = $this->field($field)->where($where)->select();
		return ['list' => objectToArray($list), 'rule_ids' => $rule_info['data']];
	}

	/**
	 * 获取父级分类
	 * @return [type] [description]
	 */
	public function getChildrenListByParentId( $parent_id, $rule_ids  = null ) {
		if (!empty($rule_ids)) {
			$where['id'] = ['in', $rule_ids];
		}
		
		$where['parent_id'] = $parent_id;
		$where['status'] = 1;
		$field = 'id,rule_name,parent_id';
		$list = $this->field($field)->where($where)->select();
		return objectToArray($list);
	}

	/**
	 * 获取所有权限
	 * @return [type] [description]
	 */
	public function getAllRule($role_id) {
		$parent_info = $this->getParentList($role_id);
		$rule_ids = $parent_info['rule_ids'];
		if (!empty($parent_info['list'])) {
			// 获取子级
			foreach ($parent_info['list'] as $k => $v) {
				$parent_info['list'][$k]['children_info'] = $this->getChildrenListByParentId( $v['id'], $rule_ids );
			}
		}
		
		return $parent_info['list'];
	}

	/**
	 * 更新权限
	 * @return [type] [description]
	 */
	public function updateRule() {
		//权限列表
        $menus = @include MAC_ADMIN_COMM . 'auth.php';

        foreach($menus as $k1=>$v1){
            foreach($v1['sub'] as $k2=>$v2){
        		if ($v2['show'] == 1) {
        			// 校验权限是否存在
        			$parent_rule_where['controller'] = $v2['controller'];
        			$parent_rule_where['action'] = $v2['action'];
        			$parent_has_exist = $this->field('id')->where($parent_rule_where)->find();
        			if (empty($parent_has_exist)) {
        				// 菜单栏
            			$parent_rule_data['rule_name'] = $v2['name'];
            			$parent_rule_data['controller'] = $v2['controller'];
            			$parent_rule_data['parent_id'] = 0;
            			$parent_rule_data['action'] = $v2['action'];
            			$parent_rule_data['status'] = 1;
            			$parent_rule_data['add_time'] = time();
            			$this->insert($parent_rule_data);
        			}
        		}

        		if ($v2['show'] == 0) {
            		// 子级
            		// 校验权限是否存在
        			$children_rule_where['controller'] = $v2['controller'];
        			$children_rule_where['action'] = $v2['action'];
        			$children_has_exist = $this->field('id')->where($children_rule_where)->find();

        			if (empty($children_has_exist)) {
        				// 根据控制器找到parent_id
        				$parent_where['controller'] = $v2['controller'];
        				$parent_info = $this->field('id')->where($parent_where)->find();
        				if (empty($parent_info)) {
        					continue;
        				}
        				// 菜单栏
            			$children_rule_data['rule_name'] = $v2['name'];
            			$children_rule_data['controller'] = $v2['controller'];
            			$children_rule_data['action'] = $v2['action'];
            			$children_rule_data['parent_id'] = $parent_info['id'];
            			$children_rule_data['status'] = 1;
            			$children_rule_data['add_time'] = time();
            			$this->insert($children_rule_data);
        			}
        		}
            }
        }
        return ['code' => 0, 'msg' => '更新成功'];
	}
}