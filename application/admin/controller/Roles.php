<?php

namespace app\admin\controller;

class roles extends Base {
	public function __construct() {
		parent::__construct();
	}

	/**
	 * 首页
	 * @return [type] [description]
	 */
	public function index() {
		$this->assign('title', '角色数据管理');
		return $this->fetch('admin@roles/index');
	}

	/**
	 * 列表页
	 * @return [type] [description]
	 */
	public function index1() {
		$param = input();
		$param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
		$param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];

		// 过滤搜索条件
		$search_data = self::_filterSearchData($param);

		$order = 'id desc';

		$res = model('roles')->listData(
			$search_data['whereOr'],
			$search_data['where'],
			$order,
			$param['page'],
			$param['limit']
		);
		$data['page'] = $res['page'];
		$data['limit'] = $res['limit'];
		$data['param'] = $param;

		$data['code'] = 0;
		$data['count'] = $res['total'];
		$data['data'] = $res['list'];

		return $this->success('succ', null, $data);
	}

	/**
	 * 信息维护
	 * @return [type] [description]
	 */
	public function info() {

		if (Request()->isPost()) {
			// 更新或添加
			$param = input('post.');
			$save_video = model('roles')->saveData($param);
			if ($save_video['code'] > 1) {
				return $this->error($save_video['msg']);
			}
			return $this->success($save_video['msg']);
		}

        $role_id = input('id');

		$where['id'] = $role_id;

		$res = model('roles')->infoData($where);

        // 管理员id为1和2超级管理员看全部
        $admin_id = cookie('admin_id');
        if (!in_array($admin_id, [1, 2])) {
            // 获取用户所拥有的角色 
            $user_role_group = model('adminRole')->getRoleByUserId(cookie('admin_id'));
        } else {
            $user_role_group['data']['role_id'] = '';
        }
        // 获取所有权限
        $all_rules = self::_getAllRule($user_role_group['data']['role_id']);
        if (!empty($role_id)) {
            // 获取已关联权限
            $has_link_rules = self::_getRoleHasLinkRule( $role_id );
            if ($has_link_rules['code'] > 1) {
                return $this->error($has_link_rules['msg']);
            }
        } else {
            $has_link_rules['data'] = [];
        }

        $all_rules = self::_filterRule( $all_rules, $has_link_rules['data'] );

        $this->assign('all_rules', $all_rules);
        $this->assign('user_role_group', $user_role_group['data']);
        $this->assign('admin_id', $admin_id);

		$info = $res['info'];
		$this->assign('info', $info);

		$this->assign('title', '角色信息');
		return $this->fetch('admin@roles/info');
	}

	/**
	 * 字段修改
	 * @return [type] [description]
	 */
	public function field() {
		$param = input();
		$ids = isset($param['ids']) ? $param['ids'] : '';
		$col = isset($param['col']) ? $param['col'] : '';
		$val = isset($param['val']) ? $param['val'] : '';

		$res = model('roles')->fieldData($ids, $col, $val);
        if($res['code'] > 1){
            return $this->error($res['msg']);
        }
        return $this->success($res['msg']);
	}

	/**
	 * 删除
	 * @return [type] [description]
	 */
	public function del() {
		$param = input();
		$ids = isset($param['ids']) ? $param['ids'] : '';

		$res = model('roles')->delData($ids);

        if($res['code'] > 1){
            return $this->error($res['msg']);
        }
        return $this->success($res['msg']);
	}

	/**
	 * 过滤搜索条件
	 * @param string $data [description]
	 */
	private function _filterSearchData($param = '') {
		$where = [];
		$whereOr = [];
		if (!empty($param['role_name'])) {
			$whereOr['role_name'] = htmlspecialchars(urldecode($param['role_name']));
		}
		if (isset($param['status']) && $param['status'] != "") {
			$where['status'] = $param['status'];
		}

		return ['whereOr' => $whereOr, 'where' => $where];
	}

    /**
     * 获取所有权限
     * @return [type] [description]
     */
    private function _getAllRule($role_id) {
        $all_rules = model('rule')->getAllRule($role_id);
        return $all_rules;
    }

    /**
     * 获取角色已关联的权限
     * @return [type] [description]
     */
    private function _getRoleHasLinkRule( $role_id ) {
        $role_has_link_rule = model('role_rule_link')->getRoleHasLinkRule( $role_id );
        return $role_has_link_rule;
    }

    /**
     * 过滤权限
     * @param  [type] $all_rules      [description]
     * @param  [type] $has_link_rules [description]
     * @return [type]                 [description]
     */
    private function _filterRule( $all_rules, $has_link_rules ) {
        foreach ($all_rules as $k => $v) {
            // 改子级下所选中的数量
            $selected_count = 0;
            // 权限总数
            $children_info_count = count($v['children_info']);
            foreach ($v['children_info'] as $k1 => $v1) {
                if (in_array( $v1['id'], $has_link_rules )) {
                    $selected_count = $selected_count + 1;
                    $all_rules[$k]['children_info'][$k1]['checked'] = 'checked';
                } else {
                    $all_rules[$k]['children_info'][$k1]['checked'] = '';
                }
            }

            if (($selected_count == $children_info_count && 
                            $selected_count != 0 && $children_info_count != 0) || 
                (in_array( $v['id'], $has_link_rules ) && $v['parent_id'] == 0 && empty($v['children_info']))
                ) {
                $all_rules[$k]['checked'] = 'checked';
            } else {
                $all_rules[$k]['checked'] = '';
            }
        }

        return $all_rules;
    }
}