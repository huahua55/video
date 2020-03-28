<?php

namespace app\common\behavior;

use think\Request;
use think\Response;

class Result
{
    public function run(Response $response)
    {
        if(defined('ENTRANCE') && ENTRANCE == 'fapi') {
            // 验证码直接输出
            if (strtolower(Request::instance()->controller()) == 'verify') {
                return;
            }
            if (!Request::instance()->exception) {
                if (Request::instance())
                $data = $response->getData();
                $response->data([
                    'code' => 1,
                    'msg' => '',
                    'data' => $data
                ]);
            }
        }
    }
}