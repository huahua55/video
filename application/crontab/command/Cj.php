<?php

namespace app\crontab\command;

use think\Cache;
use app\common\model\Type;
use app\common\model\CollectOk;
use app\common\model\Collect;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Log;

class Cj extends Command
{
    protected $Collect = '';

    protected function configure()
    {
        $this->setName('Cj')->addArgument('parameter')
            ->setDescription('定时计划：采集数据');
    }

    protected function execute(Input $input, Output $output)
    {
        // 输出到日志文件
        $output->writeln("CjCommand:");
        $myparme = $input->getArguments();
        $parameter = $myparme['parameter'];
        //参数转义解析
        $param = $this->ParSing($parameter);
        $name = $param['name'] ?? '';

        //执行对应的方法
        // 定时器需要执行的内容
        $list = config('timming');
        foreach ($list as $k => $v) {
            if (!empty($name) && $v['name'] != $name) {
                continue;
            }
            if (!empty($v['runtime'])) {
                $oldweek = date('w', $v['runtime']);
                $oldhours = date('H', $v['runtime']);
            }
            $curweek = date('w', time());
            $curhours = date("H", time());
            if (strlen($oldhours) == 1 && intval($oldhours) < 10) {
                $oldhours = '0' . $oldhours;
            }
            if (strlen($curhours) == 1 && intval($curhours) < 10) {
                $curhours = substr($curhours, 1, 1);
            }
            $last = (!empty($v['runtime']) ? date('Y-m-d H:i:s', $v['runtime']) : '从未');
            $status = $v['status'] == '1' ? '开启' : '关闭';

            //测试
            //$v['runtime']=0;

            /*    if( ($v['status']=='1' && ( empty($v['runtime']) || ($oldweek."-".$oldhours) != ($curweek."-".$curhours)
                        && strpos($v['weeks'],$curweek)!==false && strpos($v['hours'],$curhours)!==false)) ) {*/
//
//            if ((isset($param['force']) && !empty($param['force'])) || ($v['status'] == '1' && (empty($v['runtime']) || ($oldweek . "-" . $oldhours) != ($curweek . "-" . $curhours)
//                        && strpos($v['weeks'], $curweek) !== false && strpos($v['hours'], $curhours) !== false))) {
                $output->writeln('任务：' . $v['name'] . '，状态：' . $status . '，上次执行时间：' . $last . '---执行');
                Log::info('任务：' . $v['name'] . '，状态：' . $status . '，上次执行时间：' . $last . '---执行');
                $list[$k]['runtime'] = time();

                $res = mac_arr2file(APP_PATH . 'extra/timming.php', $list);
//            var_dump($res);die;
                if ($res === false) {
                    mac_echo('保存配置文件失败，请重试!');
                }
                $file = $v['file'];
                $re_txt = $this->$file($v['param']);
                Log::info($re_txt);
                $count1 = substr_count('连接API资源库失败', $re_txt);
//                if ($count1 > 0) {
//                    $key = 'ding_'.$name;
//                    $key_val = Cache::get($key);
//                    $key_count = 0;
//                    if (empty($key_val)){
//                        $day_time =date("Y-m-d H:i:s",time() + 60*30);
//                        $day_time = $day_time . '*0';
//                        Cache::set($key,$day_time,0);
//                        $key_count = 1;
//                    }else{
//                        $key_val_list = explode('*',$key_val);
////                    var_dump(date("Y-m-d H:i:s",time()));
////                    var_dump($key_val_list[0]);
//                        if (time() > strtotime($key_val_list[0])){
//                            $key_counts =  ($key_val_list[1]+ 1);
//                            if ($key_count >= 5){
//                                $day_time =date("Y-m-d H:i:s",time() + 60*30);
//                                $day_time = $day_time . '*1';
//                                Cache::set($key,$day_time,0);
//                                $key_count = 5;
//                            }else{
//                                $day_time =date("Y-m-d H:i:s",time() + 60*30);
//                                $day_time = $day_time . '*1';
//                                Cache::set($key,$day_time,0);
//                                $key_count = 0;
//                            }
//                        }else{
//                            $key_count =  ($key_val_list[1]+ 1);
//                            $day_time = $key_val_list[0] . '*' . $key_count;
//                            Cache::set($key,$day_time,0);
//                        }
//                    }
//                    if ($key_count >= 5){
//                        $count3 = substr_count('ok', $name);
//                        if ($count3 > 0) {
//                            $zd_sh_path = APP_PATH_CRONTAB.'cj_zd.sh';
//                            exec($zd_sh_path, $res, $rc);
//                        }else{
//                            mac_echo('ok采集，请重试!');
//                            $ok_sh_path = APP_PATH_CRONTAB.'cj_ok.sh';
//                            exec($ok_sh_path, $res, $rc);
//                        }
//                    }
//                }
                unset($re_txt);
                die;
//            } else {
//                mac_echo('任务：' . $v['name'] . '，状态：' . $status . '，上次执行时间：' . $last . '---跳过');
//            }
        }

        $output->writeln("end....");
    }

    //代码解析(urlget传参模式)

    protected function ParSing($parameter)
    {
        $parameter_array = array();
        $arry = explode('#', $parameter);
        foreach ($arry as $key => $value) {
            $zzz = explode('=', $value);
            $parameter_array[$zzz[0]] = $zzz[1];

        }
        return $parameter_array;

    }

    public function collect($param)
    {
        @parse_str($param, $output);
        $cjurl = $output['cjurl'] ?? '';
        //ok站点走不同的采集数据
        if (strpos($cjurl, 'okzy') !== false) {
            $this->Collect = 'CollectOk';
        } else {
            $this->Collect = 'Collect';
        }
       return $this->api($output);
    }

    public function api($param = [])
    {
        
        if (!empty($param['pg'])) {
            $param['page'] = $param['pg'];
            unset($param['pg']);
        }
        if ($param['mid'] == '' || $param['mid'] == '1') {
            return $this->vod($param);
        } elseif ($param['mid'] == '2') {
            return $this->art($param);
        } elseif ($param['mid'] == '8') {
            return $this->actor($param);
        } elseif ($param['mid'] == '9') {
            return $this->role($param);
        } elseif ($param['mid'] == '11') {
            return $this->website($param);
        }
        return [];
    }

    public function vod($param)
    {
        if ($param['ac'] != 'list') {
            Cache::set('collect_break_vod', url('collect/api') . '?' . http_build_query($param));
        }
        $res = model($this->Collect)->vod($param);
//        p($res);
        if ($res['code'] > 1) {
            mac_echo($res['msg']);
            return $res['msg'];
        }

        if ($param['ac'] == 'list') {

            $bind_list = config('bind');
            $type_list = model('Type', 'common\model')->getCache('type_list');

            foreach ($res['type'] as $k => $v) {
                $key = $param['cjflag'] . '_' . $v['type_id'];
                $res['type'][$k]['isbind'] = 0;
                $local_id = intval($bind_list[$key]);
                if ($local_id > 0) {
                    $res['type'][$k]['isbind'] = 1;
                    $res['type'][$k]['local_type_id'] = $local_id;
                    $type_name = $type_list[$local_id]['type_name'];
                    if (empty($type_name)) {
                        $type_name = '未知分类';
                    }
                    $res['type'][$k]['local_type_name'] = $type_name;
                }
            }
            $param['res'] = $res;
            $param['param'] = $param;
            $param['param_str'] = http_build_query($param);
            return $param;
        }

        mac_echo('<style type="text/css">body{font-size:12px;color: #333333;line-height:21px;}span{font-weight:bold;color:#FF0000}</style>');
        return model($this->Collect)->vod_data($param, $res);

    }


    public function art($param)
    {
        if ($param['ac'] != 'list') {
            Cache::set('collect_break_art', url('collect/api') . '?' . http_build_query($param));
        }
        $res = model($this->Collect)->art($param);
        if ($res['code'] > 1) {
            return $this->error($res['msg']);
        }

        if ($param['ac'] == 'list') {

            $bind_list = config('bind');
            $type_list = model('Type')->getCache('type_list');

            foreach ($res['type'] as $k => $v) {
                $key = $param['cjflag'] . '_' . $v['type_id'];
                $res['type'][$k]['isbind'] = 0;
                $local_id = intval($bind_list[$key]);
                if ($local_id > 0) {
                    $res['type'][$k]['isbind'] = 1;
                    $res['type'][$k]['local_type_id'] = $local_id;
                    $type_name = $type_list[$local_id]['type_name'];
                    if (empty($type_name)) {
                        $type_name = '未知分类';
                    }
                    $res['type'][$k]['local_type_name'] = $type_name;
                }
            }

            $this->assign('page', $res['page']);
            $this->assign('type', $res['type']);
            $this->assign('list', $res['data']);

            $this->assign('total', $res['page']['recordcount']);
            $this->assign('page', $res['page']['page']);
            $this->assign('limit', $res['page']['pagesize']);

            $param['page'] = '{page}';
            $param['limit'] = '{limit}';
            $this->assign('param', $param);

            $this->assign('param_str', http_build_query($param));

            return $this->fetch('admin@collect/art');
        }

        mac_echo('<style type="text/css">body{font-size:12px;color: #333333;line-height:21px;}span{font-weight:bold;color:#FF0000}</style>');
        model($this->Collect)->art_data($param, $res);
    }

    public function actor($param)
    {
        if ($param['ac'] != 'list') {
            Cache::set('collect_break_actor', url('collect/api') . '?' . http_build_query($param));
        }
        $res = model($this->Collect)->actor($param);
        if ($res['code'] > 1) {
            return $this->error($res['msg']);
        }

        if ($param['ac'] == 'list') {

            $bind_list = config('bind');
            $type_list = model('Type')->getCache('type_list');

            foreach ($res['type'] as $k => $v) {
                $key = $param['cjflag'] . '_' . $v['type_id'];
                $res['type'][$k]['isbind'] = 0;
                $local_id = intval($bind_list[$key]);
                if ($local_id > 0) {
                    $res['type'][$k]['isbind'] = 1;
                    $res['type'][$k]['local_type_id'] = $local_id;
                    $type_name = $type_list[$local_id]['type_name'];
                    if (empty($type_name)) {
                        $type_name = '未知分类';
                    }
                    $res['type'][$k]['local_type_name'] = $type_name;
                }
            }

            $this->assign('page', $res['page']);
            $this->assign('type', $res['type']);
            $this->assign('list', $res['data']);

            $this->assign('total', $res['page']['recordcount']);
            $this->assign('page', $res['page']['page']);
            $this->assign('limit', $res['page']['pagesize']);

            $param['page'] = '{page}';
            $param['limit'] = '{limit}';
            $this->assign('param', $param);

            $this->assign('param_str', http_build_query($param));

            return $this->fetch('admin@collect/actor');
        }

        mac_echo('<style type="text/css">body{font-size:12px;color: #333333;line-height:21px;}span{font-weight:bold;color:#FF0000}</style>');
        model($this->Collect)->actor_data($param, $res);
    }

    public function role($param)
    {
        if ($param['ac'] != 'list') {
            Cache::set('collect_break_role', url('collect/api') . '?' . http_build_query($param));
        }
        $res = model($this->Collect)->role($param);
        if ($res['code'] > 1) {
            return $this->error($res['msg']);
        }

        if ($param['ac'] == 'list') {

            $bind_list = config('bind');
            $type_list = model('Type')->getCache('type_list');

            foreach ($res['type'] as $k => $v) {
                $key = $param['cjflag'] . '_' . $v['type_id'];
                $res['type'][$k]['isbind'] = 0;
                $local_id = intval($bind_list[$key]);
                if ($local_id > 0) {
                    $res['type'][$k]['isbind'] = 1;
                    $res['type'][$k]['local_type_id'] = $local_id;
                    $type_name = $type_list[$local_id]['type_name'];
                    if (empty($type_name)) {
                        $type_name = '未知分类';
                    }
                    $res['type'][$k]['local_type_name'] = $type_name;
                }
            }

            $this->assign('page', $res['page']);
            $this->assign('type', $res['type']);
            $this->assign('list', $res['data']);

            $this->assign('total', $res['page']['recordcount']);
            $this->assign('page', $res['page']['page']);
            $this->assign('limit', $res['page']['pagesize']);

            $param['page'] = '{page}';
            $param['limit'] = '{limit}';
            $this->assign('param', $param);

            $this->assign('param_str', http_build_query($param));

            return $this->fetch('admin@collect/role');
        }

        mac_echo('<style type="text/css">body{font-size:12px;color: #333333;line-height:21px;}span{font-weight:bold;color:#FF0000}</style>');
        model($this->Collect)->role_data($param, $res);
    }

    public function website($param)
    {
        if ($param['ac'] != 'list') {
            Cache::set('collect_break_website', url('collect/api') . '?' . http_build_query($param));
        }
        $res = model($this->Collect)->website($param);
        if ($res['code'] > 1) {
            return $this->error($res['msg']);
        }

        if ($param['ac'] == 'list') {

            $bind_list = config('bind');
            $type_list = model('Type')->getCache('type_list');

            foreach ($res['type'] as $k => $v) {
                $key = $param['cjflag'] . '_' . $v['type_id'];
                $res['type'][$k]['isbind'] = 0;
                $local_id = intval($bind_list[$key]);
                if ($local_id > 0) {
                    $res['type'][$k]['isbind'] = 1;
                    $res['type'][$k]['local_type_id'] = $local_id;
                    $type_name = $type_list[$local_id]['type_name'];
                    if (empty($type_name)) {
                        $type_name = '未知分类';
                    }
                    $res['type'][$k]['local_type_name'] = $type_name;
                }
            }

            $this->assign('page', $res['page']);
            $this->assign('type', $res['type']);
            $this->assign('list', $res['data']);

            $this->assign('total', $res['page']['recordcount']);
            $this->assign('page', $res['page']['page']);
            $this->assign('limit', $res['page']['pagesize']);

            $param['page'] = '{page}';
            $param['limit'] = '{limit}';
            $this->assign('param', $param);

            $this->assign('param_str', http_build_query($param));

            return $this->fetch('admin@collect/website');
        }

        mac_echo('<style type="text/css">body{font-size:12px;color: #333333;line-height:21px;}span{font-weight:bold;color:#FF0000}</style>');
        model($this->Collect)->website_data($param, $res);
    }


}