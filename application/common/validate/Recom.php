<?php
namespace app\common\validate;
use think\Validate;

class Recom extends Validate
{
    protected $rule =   [
        'name'  => 'require|max:100',
    ];

    protected $message  =   [
        'name.require' => '名称必须',
        'name.max'     => '名称最多不能超过100个字符',
    ];
}