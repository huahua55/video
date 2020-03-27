<?php
namespace app\common\exception;

use Exception;
use think\exception\Handle;
use think\Request;
use think\Log;

class Http extends Handle
{
    private $code;
    private $msg;
    private $errorCode;

    public function render(Exception $e) {
        if (\think\Env::get('app_debug')) {
            //return parent::render($e);
        }
        $this->code = $e->getCode();
        $this->msg = $e->getMessage();
        $this->errorCode = $e->getCode();
        $this->recordErrorLog($e);
        Request::instance()->bind('exception', true);

        $result = [
            'code' => $this->errorCode,
            'msg' => $this->msg,
            'data' => null,
        ];
        return json($result, $this->code);
    }

    // 将异常写入日志
    private function recordErrorLog(Exception $e) {
        Log::record($e->getMessage(), 'error');
    }

}