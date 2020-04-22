<?php
namespace app\common\validate;
use think\Validate;

class Banner extends Validate
{
    protected $rule =   [
        'img'  => 'require|max:255',
        'rel_vod'  => 'number',
    ];

    protected $message  =   [
        'img.require' => '图片必须',
        'img.max'     => '图片地址最多不能超过255个字符',
        'rel_vod.number'  => '关联视频必须是数字且唯一',
    ];
}