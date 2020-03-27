<?php
namespace app\common\validate;
use think\Validate;

class Banner extends Validate
{
    protected $rule =   [
        'img'  => 'require|max:255',
        'link'   => 'require|max:255',
    ];

    protected $message  =   [
        'img.require' => '图片必须',
        'img.max'     => '图片地址最多不能超过255个字符',
        'link.require'   => '链接地址必须',
        'link.max'     => '链接地址最多不能超过255个字符',
    ];
}