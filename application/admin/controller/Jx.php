<?php
namespace app\admin\controller;

class Jx extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            $data = [];
            $url = $param['jx_url'].urlencode($param['play_url']);
            $resp = mac_curl_get($url);
            if ($resp) {
                $resp = trim($resp, '(');
                $resp = trim($resp, ');');
            } else {
                return $this->error('解析失败');
            }
            $resp = json_decode($resp, true);
            if (!$resp || !is_array($resp)) {
                return $this->error('解析失败');
            }
            if (strpos($param['jx_url'], 'oopw')) {
                if ($resp['code'] == 200) {
                    foreach ($resp['info'] as $k => $info) {
                        $data[$k]['flag'] = $info['flag_name'].':'.$info['flag'];
                        $data[$k]['video'] = [];
                        foreach ($info['video'] as $item) {
                            list($desc, $url, $play_from) = explode('$', $item);
                            $data[$k]['video'][] = $desc.'$'.$url;
                        }
                    }
                }
            }
            if ($data) {
                return $this->success('解析成功', null, $data);
            }
            return $this->error('解析失败');
        }

        $param = input();
        $jx_list = [
            '大亨影院：oopw.top' => 'http://api.oopw.top/api.php?url=',
        ];
        $this->assign('jx_list', $jx_list);
        $this->assign('title','视频解析');
        return $this->fetch('admin@jx/index');
    }

}
