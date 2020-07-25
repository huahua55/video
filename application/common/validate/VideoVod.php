<?php
namespace app\common\validate;
use think\Validate;

class VideoVod extends Validate
{

    protected $regex = [ 'weight' => '/^([1-9][\d]{0,1}|0)(\.[\d]{1})?$/'];

    protected $rule =   [
        'weight'  => 'regex:weight',
    ];

    protected $message  =   [
        'weight.regex' => '排序不能超过3位的整数或者小数且小数点保留一位'
    ];

    protected $scene = [
        'edit'  =>  ['weight'],
    ];

}