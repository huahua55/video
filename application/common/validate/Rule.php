<?php
namespace app\common\validate;
use think\Validate;

class Rule extends Validate {
	protected $rule = [
		'rule_name' => 'require|max:60',
		'controller' => 'require|alphaDash|max:60',
		'action' => 'require|alphaDash|max:60',
		'status' => 'require|in:0,1',
	];

	protected $message = [
		'rule_name.require' => '权限名称必须',
		'rule_name.max' => '权限名称最多不能超过60个字符',
		'controller.require' => '控制器名必须',
		'controller.alphaDash' => '控制器名只能是字母和数字，下划线_及破折号-',
		'controller.max' => '控制器名最多不能超过60个字符',
		'action.require' => '方法名必须',
		'action.alphaDash' => '方法名只能是字母和数字，下划线_及破折号-',
		'action.max' => '方法名最多不能超过60个字符',
		'status.require' => '权限状态必须',
		'status.in' => '权限状态必须为0或1',
	];

	protected $scene = [
		'add' => ['rule_name', 'controller', 'action', 'status'],
		'edit' => ['rule_name', 'controller', 'action', 'status'],
	];

}