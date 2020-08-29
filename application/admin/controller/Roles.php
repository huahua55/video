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
	public function list() {
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
		$data['msg'] = 'succ';
		$data['data'] = $res['list'];

		return $data;
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

		$where['id'] = input('id');

		$res = model('roles')->infoData($where);

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

		return model('roles')->fieldData($ids, $col, $val);
	}

	/**
	 * 删除
	 * @return [type] [description]
	 */
	public function del() {
		$param = input();
		$ids = isset($param['ids']) ? $param['ids'] : '';

		return model('roles')->delData($ids);
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
}