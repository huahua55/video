<?php

namespace app\api\controller;

use think\Controller;
use app\common\controller\All;
use Exception;
use think\Db;

class Text extends All
{
    public $_param;
    protected $vodDb;//db

    public function __construct()
    {
        parent::__construct();
        $this->_param = input();
        $this->vodDb = Db::name('vod');
    }

    // 取出数据豆瓣评分为空数据
    protected function getVodDoubanScoreData($where, $order, $page, $limit, $start, $t = '')
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        if (empty($t)) {
            $total = $this->vodDb->where($where)->count();
        } else {
            $total = $t;
        }
        $list = $this->vodDb->field('vod_id')->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }


    public function index()
    {
            set_time_limit(0);
            $param = $this->_param;
            $t_path = $path =     RUNTIME_PATH.DS.'Text'.DS;
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }

            $host = $param['host'] ?? '';
            if(empty($host)){
                $host = 'www.lanhv.tv';
            }
            $path= $path.$host.'_';
            $where = [];

            $p_tid = $param['p_tid'] ?? '';
            if (!empty($p_tid)) {
                $where['type_id_1'] = ['eq', $p_tid];
                $path= $path.$p_tid.'_';
            }
            $tid = $param['tid'] ?? '';
            if (!empty($tid)) {
                $where['type_id'] = ['eq', $tid];
                $path= $path.$tid.'_';
            }
            $year = $param['year'] ?? '';
            if (!empty($year)) {
                $where['vod_year'] = ['eq', $tid];
                $path= $path.$tid.'_';
            }
            $path = $path.'.txt';
//           print_r($path);die;
            $total = $param['total'] ?? '';//条数
            $start = 0;
            $page = 1;
            $limit = 20;

            $is_true = true;
            $order = 'vod_id asc';
            if (empty($where)) {
                return json_encode(['code' => 0, 'msg' => 'err,没有参数'], true);
            }

            if(is_file($path)){
                unlink($path);
            }
            $count = 0;
            //进入循环 取出数据
            while ($is_true) {
                //取出数据
                $douBanScoreData = $this->getVodDoubanScoreData($where, $order, $page, $limit, $start, $total);
                $pagecount = $douBanScoreData['pagecount'] ?? 0;
                if ($page > $pagecount) {
                    $is_true = false;
                    break;
                }
                if(empty($douBanScoreData['list'])){
                    $is_true = false;
                    break;
                }
//                print_r($path);die;
//                echo "<pre>";
//                print_r($douBanScoreData['list']);die;
                foreach ($douBanScoreData['list'] as $k=>$v){
                    if(is_array($v['vod_id'])){
                        continue;
                    }
                    $count = $count+1;
                    if(!empty($total)){
                        if($count > $total){
                            $is_true = false;
                            break;
                        }
                    }
                    $url = 'https://www.lanhu.tv/vod/play/id/'.$v['vod_id'].'/sid/1/nid/1.html' ."\r\n";
                    file_put_contents($path, $url, FILE_APPEND);
                }
                $page = $page + 1;
            }
        $path_name = str_replace($t_path,'',$path);
        header("Content-type: text/plain");			//Mime-Type类型
        header("Content-Disposition:attachment;filename = ".$path_name);	//弹出保存框的形式下载文件(附件)
        readfile($path);	//返回从文件中读入的字节数
        die();
    }

}