<?php

namespace app\crontab\command;

use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Log;
use function GuzzleHttp\Psr7\_caseless_remove;
use Exception;


class PushData extends Common
{


    protected $vodModel;
    protected $videoModel;
    protected $videoVodModel;
    protected $videoCollectionModel;
    protected function configure()
    {
        $config = config('log');
        $config['keyp'] = 'data';
        $this->vodModel = Db::name('vod');
        $this->videoVodModel = Db::name('video_vod');
        $this->setName('pushData')->setDescription("获取数据-插入任务表");//这里的setName和php文件名一致,setDescription随意
    }

    /*
     * 下载
     */
    protected function execute(Input $input, Output $output)
    {

        set_time_limit(0);
        $output->writeln('获取数据-插入任务表-获取数据开始:init');
        //这里写业务逻辑
        $start = 0;
        $page = 1;
        $limit = 20;
        $is_true = true;
        $order = 'a.vod_id desc';
        //where
        $vod_where = [];
        $vod_where['a.type_id'] = ['in','6,7,8,9,10,11,12,13,14,15,16,24']; //电影
//        ['13','14','15','16','24'];
        $vod_where['a.vod_year'] = ['gt', 2000];//年代限制
//        $vod_where['a.vod_area']  = array(array('like','%韩国%'), array('like','%美国%'), 'or');
        //$vod_where['vod_lang']  = array(array('like','%英语%'), array('like','%韩语%'),  'or');
//        $vod_where['a.vod_douban_id']  = ['gt',0]; //豆瓣限制
//        $vod_where['a.vod_douban_score']  = ['gt',7];
        $vod_where['a.vod_play_url'] = array(array('like', '%.m3u8%'), array('like', '%.mp4%'), 'or');
        $vod_where['a.vod_down_url'] = array(array('like', '%.m3u8%'), array('like', '%.mp4%'), 'or');
        $vod_where['b.is_down'] = ['EXP', Db::raw('IS NULL')];
        while ($is_true) {
            $data = $this->getDataJoin($vod_where, $order, $page, $limit, $start);
            if (!empty($data)) {
                $pagecount = $data['pagecount'] ?? 0;
                if ($page > $pagecount) {
                    $is_true = false;
                    $output->writeln("获取数据-插入任务表-结束...");
                    break;
                }
                if (!empty($data['list'])) {
                    foreach ($data['list'] as $key => $val) {
                        $vod_play_url = explode('$$$', $val['vod_play_url']);
                        $vod_down_url = explode('$$$', $val['vod_down_url']);
                        $type_id_1 = getTypePid($val['type_id']);
                        if ($type_id_1 == 1) {
                            $this->installData($val, $vod_down_url, $vod_play_url);
                        } else {
                            $vod_collection_url = $this->getUrlExplode($vod_play_url);
                            if (empty($vod_collection_url)) {
                                $vod_collection_url = $this->getUrlExplode($vod_down_url);
                            }
                            $vod_down_collection_url = $this->getUrlExplode($vod_down_url, $type = '.mp4');
                            p($vod_collection_url,1);
                            p($vod_down_collection_url,1);

                            if (!empty($vod_collection_url)) {
                                foreach ($vod_collection_url as $k => $vod_collection_url_value) {
                                    $vod_down_collection_url_val = $vod_down_collection_url[$k] ?? "";
                                    $this->installData($val, $vod_down_collection_url_val, $vod_collection_url_value);
                                }
                            }
                        }
                    }
                }
            }
            $page = $page + 1;
        }
        $output->writeln("结束...");
    }

    protected function installData($val, $vod_down_url, $vod_play_url)
    {
        $vod_down_urls = '';
        $vod_play_urls = '';
        $video_data = [];
        $video_data['is_down'] = 0;
        $video_data['reason'] = '';
        $n_reason = "";
        if (!empty($vod_down_url)) {
            $vod_down_urls = $this->getUrlData($vod_down_url, $type = '.mp4');
            $count = substr_count($vod_down_urls, '.mp4');
            if ($count > 1) {
                $video_data['is_down'] = 4;
                $n_reason .= 'mp4出现' . $count . '次';
            }
            $vod_play_url_down = $this->getUrlData($vod_down_url, $type = '.m3u8');
            if(!empty($vod_play_url_down)){
                $vod_play_url = $vod_play_url_down;
            }
            $vod_play_url_count = substr_count($vod_play_url_down, '.m3u8');
            if ($vod_play_url_count > 1) {
                $video_data['is_down'] = 4;
                $n_reason .= 'm3u8出现' . $count . '次';
            }
        }
        if (!empty($vod_play_url)) {
            $vod_play_urls = $this->getUrlData($vod_play_url, $type = '.m3u8');
            $count = substr_count($vod_play_urls, '.m3u8');
            if ($count > 1) {
                $video_data['is_down'] = 4;
                $n_reason .= 'mp4出现' . $count . '次';
            }
        }
        $video_data['reason'] = $n_reason;
        $video_data['is_sync'] = 0;
        $video_data['is_section'] = 0;
        $video_data['video_id'] = 0;
        $video_data['down_add_time'] = time();
        $video_data['down_time'] = time();
        $video_data['code'] = -1;
        $video_data['vod_id'] = $val['vod_id'];
        $video_data['weight'] = $val['vod_douban_score'] ?? '0';
        $video_data['down_url'] = $vod_down_urls;
        $video_data['m3u8_url'] = $vod_play_urls;
        $res = $this->videoVodModel->insert($video_data);
        if ($res) {


        } else {


        }
    }

    protected function getUrlExplode($vod_play_url, $type = '.m3u8')
    {
        $vod_collection_url = [];
        $vod_collection_url_explode = $vod_play_url[0] ?? '';
        if (!empty($vod_collection_url_explode)) {
            $vod_collection_url = explode('#', $vod_collection_url_explode);
            foreach ($vod_collection_url as $k => $v) {
                $count = substr_count($v, $type);
                if ($count == 0) {
                    unset($vod_collection_url[$k]);
                }
            }
        }
        return $vod_collection_url;
    }

    protected function getUrlData($vod_down_url, $type = '.mp4')
    {
        $vod_down_urls = '';
        if (is_array($vod_down_url)) {
            foreach ($vod_down_url as $s_k => $s_v) {
                $s_v_url = substr_count($s_v, $type);
                if ($s_v_url > 0) {
                    $vod_down_urls = $s_v;
                    break;
                }
            }
        } else {
            $s_v_url = substr_count($vod_down_url, $type);
            if ($s_v_url > 0) {
                $vod_down_urls = $vod_down_url;
            }
        }

        return $vod_down_urls;
    }

    /*
     * 获取date 数据
     */
    protected function getData($where, $order, $page, $limit, $start)
    {
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodModel->where($where)->count();
        $list = $this->vodModel->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }

    protected function getDataJoin($where, $order, $page, $limit, $start)
    {

        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        $total = $this->vodModel->alias('a')->field('a.vod_id,a.type_id,a.type_id_1,a.vod_douban_score,a.vod_name,a.vod_down_url,a.vod_down_note,a.vod_down_server,a.vod_down_from,a.type_id,b.video_id as b_video_id,b.is_down,b.is_section,b.is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'LEFT')->count();
        $list = $this->vodModel->alias('a')->field('a.vod_id,a.type_id,a.type_id_1,a.vod_play_url,a.vod_douban_score,a.vod_name,a.vod_down_url,a.vod_down_note,a.vod_down_server,a.vod_down_from,a.type_id,b.video_id as b_video_id,b.is_down,b.is_section,b.is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'LEFT')->where($where)->order($order)->limit($limit_str)->select();
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }
}