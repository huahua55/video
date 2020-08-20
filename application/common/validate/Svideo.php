<?php
namespace app\common\validate;
use think\Validate;

class Svideo extends Validate
{
    protected $rule =   [
        'name'  => 'require|max:255',
        'type_pid'  => 'require',
    ];

    protected $message  =   [
        'name.require' => '名称必须',
        'name.max'     => '名称最多不能超过255个字符',
        'type_pid.require' => '分类必须',
    ];

    protected $scene = [
        'add'  =>  ['name','type_pid'],
        'edit'  =>  ['name','type_pid'],
    ];

}