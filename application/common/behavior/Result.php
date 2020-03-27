<?php

namespace app\common\behavior;

use think\Request;
use think\Response;

class Result
{
    public function run(Response $response)
    {
        if(defined('ENTRANCE') && ENTRANCE == 'fapi') {
            if (!Request::instance()->exception) {
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