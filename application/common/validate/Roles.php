<?php
namespace app\common\validate;
use think\Validate;

class Roles extends Validate {
	protected $rule = [
		'role_name' => 'require|max:60',
		'status' => 'require|in:0,1',
	];

	protected $message = [
		'role_name.require' => '角色名称必须',
		'role_name.max' => '角色名称最多不能超过60个字符',
		'status.require' => '角色状态必须',
		'status.in' => '角色状态必须为0或1',
	];

	protected $scene = [
		'add' => ['role_name', 'status'],
		'edit' => ['role_name', 'status'],
	];

}