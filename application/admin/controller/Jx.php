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
        $jx_list = [
            '大亨影院：oopw.top' => 'oopw',
            'IDC：idc126.net' => 'idc126',
        ];

        if (Request()->isPost()) {
            $param = input('post.');
            $data = [];
            if (!$param['play_url']) {
                return $this->error('视频播放地址不能为空');
            }
            $method = 'jx_'.$param['method'];
            if (!method_exists($this, $method)) {
                return $this->error('解析地址选择错误');
            }
            $data = $this->$method($param['play_url']);
            if ($data) {
                return $this->success('解析成功', null, $data);
            }
            return $this->error('解析失败');
        }

        $param = input();
        $this->assign('jx_list', $jx_list);
        $this->assign('title','视频解析');
        return $this->fetch('admin@jx/index');
    }

    private function jx_oopw($play_url)
    {
        $data = [];
        $jx_url = 'http://api.oopw.top/api.php?url=';
        $url = $jx_url.urlencode($play_url);
        $resp = mac_curl_get($url);
        if ($resp) {
            $resp = trim($resp, '(');
            $resp = trim($resp, ');');
        } else {
            return $data;
        }
        $resp = json_decode($resp, true);

        if (!$resp || !is_array($resp)) {
            return $data;
        }
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
        return $data;
    }

    private function jx_idc126($play_url)
    {
        $data = [];
        $jx_url = 'https://jx.idc126.net/jx/api.php';
        $resp = mac_curl_post($jx_url, ['url' => $play_url]);
        $resp = json_decode($resp, true);
        if (!$resp || !is_array($resp)) {
            return $data;
        }
        if ($resp['code'] == 200) {
            $data[0]['flag'] = $resp['title'];
            $data[0]['video'][] = '1$'.$resp['url'];
        }
        return $data;
    }

}
