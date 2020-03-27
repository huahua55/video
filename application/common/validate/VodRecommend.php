<?php
namespace app\common\validate;
use think\Validate;

class VodRecommend extends Validate
{
    protected $rule =   [
        'name'  => 'require|max:20',
    ];

    protected $message  =   [
        'name.require' => '名称必须',
        'name.max'     => '名称最多不能超过20个字符',
    ];
}