<?php

namespace app\admin\controller;

class rule extends Base {
	public function __construct() {
		parent::__construct();
	}

	/**
	 * 首页
	 * @return [type] [description]
	 */
	public function index() {
		$this->assign('title', '权限数据管理');
		return $this->fetch('admin@rule/index');
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

		$res = model('rule')->listData(
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
			$save_video = model('rule')->saveData($param);
			if ($save_video['code'] > 1) {
				return $this->error($save_video['msg']);
			}
			return $this->success($save_video['msg']);
		}

		$where['id'] = input('id');

		$res = model('rule')->infoData($where);

		// 获取所有父级分类
		$parent_list = model('rule')->getParentList();

		$info = $res['info'];
		$this->assign('info', $info);
		$this->assign('parent_list', $parent_list);

		$this->assign('title', '权限信息');
		return $this->fetch('admin@rule/info');
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

		return model('rule')->fieldData($ids, $col, $val);
	}

	/**
	 * 删除
	 * @return [type] [description]
	 */
	public function del() {
		$param = input();
		$ids = isset($param['ids']) ? $param['ids'] : '';
		$is_master = $param['is_master'];

		return model('rule')->delData($ids, $is_master);
	}

	/**
	 * 更新权限
	 * @return [type] [description]
	 */
	public function updateRule() {
		return model('rule')->updateRule($ids, $is_master);
	}

	/**
	 * 过滤搜索条件
	 * @param string $data [description]
	 */
	private function _filterSearchData($param = '') {
		$where = [];
		$whereOr = [];
		if (!empty($param['rule_name'])) {
			$whereOr['rule_name'] = htmlspecialchars(urldecode($param['rule_name']));
		}
		if (!empty($param['controller'])) {
			$where['controller'] = htmlspecialchars(urldecode($param['controller']));
		}
		if (!empty($param['action'])) {
			$where['action'] = htmlspecialchars(urldecode($param['action']));
		}
		if (isset($param['status']) && $param['status'] != "") {
			$where['status'] = $param['status'];
		}

		return ['whereOr' => $whereOr, 'where' => $where];
	}
}