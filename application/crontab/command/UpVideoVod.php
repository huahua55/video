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

use GuzzleHttp\Client;

class UpVideoVod extends Common
{

    protected function configure()
    {
        $this->vodModel = Db::name('vod');
        $this->videoVodModel = Db::name('video_vod');
        $this->videoModel = Db::name('video');
        $this->setName('upVideoVod')->setDescription("获取数据");//这里的setName和php文件名一致,setDescription随意
    }


    public function title_cl($m3u8_url_key){
        if (substr_count($m3u8_url_key, '-') >0 and substr_count($m3u8_url_key, 'http') == 0 ){
            $m3u8_url_key = trim(str_replace('-','',$m3u8_url_key));
        }
        if (substr_count($m3u8_url_key, '期') == 0 and substr_count($m3u8_url_key, '集') == 0 ){
            $m3u8_url_key = $m3u8_url_key . '期';
        }
        if (substr_count($m3u8_url_key, '第') > 0 and substr_count($m3u8_url_key, '集') > 0 ){
            print_r(11);die;
            $m3u8_url_key = str_replace('集','期',$m3u8_url_key);;
        }
        if (substr_count($m3u8_url_key, '期') == 0 and substr_count($m3u8_url_key, '下') > 0){
            $m3u8_url_key = str_replace('下','期下',$m3u8_url_key);
        }
        if (substr_count($m3u8_url_key, '期') == 0 and substr_count($m3u8_url_key, '上') > 0){
            $m3u8_url_key = str_replace('上','期上',$m3u8_url_key);
        }
        if (substr_count($m3u8_url_key, '下期') > 0){
            $m3u8_url_key = str_replace('下期','期下',$m3u8_url_key);
        }
        if (substr_count($m3u8_url_key, '上期') > 0){
            $m3u8_url_key = str_replace('上期','期上',$m3u8_url_key);
        }
        return $m3u8_url_key;
    }
    protected function get_tit_ca($v_v){
        $vData = explode('#', $v_v);
        foreach ($vData as $kk => $vv) {
            $v_list_key = explode('$', $vv);
            if(substr_count($v_list_key[0], 'http')==0){
                $m3u8_url_key =$this->title_cl($v_list_key[0]);
                $vData[$kk] = $m3u8_url_key .'$'.$v_list_key[1];
            }
        }
        return implode('#', $vData);
    }

    protected function execute(Input $input, Output $output)
    {

        $output->writeln('init');
        $sql = "SELECT id,m3u8_url from video_vod WHERE type_id  in (3, 25, 26, 27, 28)";
        $list = Db::query($sql);
        foreach ($list as $key => $val) {
            $m3u8_url = $this->get_tit_ca($val['m3u8_url']);
//            p($m3u8_url);
            if ($m3u8_url != $val['m3u8_url']){
                $video_data['m3u8_url'] = $m3u8_url;
                $res = Db::table('video_vod')->where(['id' => $val['id']])->update($video_data);
            }
        }

        $output->writeln("结束...");
    }
    /*
     * 下载
     */
    protected function execute1(Input $input, Output $output)
    {

        $output->writeln('init');
        //这里写业务逻辑
        $is_true = true;
        $start = 0;
        $page = 1;
        $limit = 10;
        $order = 'id';
        $vod_where = [];
        $vod_where['collection'] = 0;
        while ($is_true) {
            $data = $this->getData($vod_where, $order, $page, $limit, $start);
            if (!empty($data)) {
                $pagecount = $data['pagecount'] ?? 0;
                if ($page > $pagecount) {
                    $is_true = false;
                    $output->writeln("结束...");
                    break;
                }
                if (!empty($data['list'])) {
                    $inId = array_unique(array_column($data['list'], 'vod_id'));
                    $where = [];
                    $where['vod_id'] = ['in', $inId];
                    $array_vod = $this->vodModel->where($where)->column(null, 'vod_id');
                    foreach ($data['list'] as $key => $val) {
                        $video_data = [];
                        if (isset($array_vod[$val['vod_id']])) {
                            $video_data['type_id'] = $array_vod[$val['vod_id']]['type_id'];
                            $pid = $video_data['type_id_1'] = $array_vod[$val['vod_id']]['type_id_1'];
                            $video_data['vod_name'] = $array_vod[$val['vod_id']]['vod_name'];
                            if ($video_data['type_id_1'] == 0) {
                                $pid = getTypePid($video_data['type_id'], $i = 1);
                            }
                            if ($pid == 1) {
                                $video_data['collection'] = 1;
                            } else {
                                $title = $val['m3u8_url'];
                                if (empty($title)) {
                                    $title = $val['down_url'];
                                }
                                $title = explode('$', explode('#', $title)[0] ?? '')[0] ?? '';
                                $video_data['collection'] = findNumAll($title);
                            }
                            $res = $this->videoVodModel->where(['id' => $val['id']])->update($video_data);
                            if ($res) {
                                log::write('成功' . $val['vod_id'] . '-' . $video_data['vod_name']);
                            } else {
                                log::write('失败' . $val['vod_id'] . '-' . $video_data['vod_name']);
                            }
                        }
                    }
                }
            }
            $page = $page + 1;
        }
        $output->writeln("结束...");
    }

    protected function getUrlData($vod_down_url, $type = '.mp4')
    {
        $vod_down_urls = '';
        foreach ($vod_down_url as $s_k => $s_v) {
            $s_v_url = substr_count($s_v, $type);
            if ($s_v_url > 0) {
                $vod_down_urls = $s_v;
                break;
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
        $total = $this->videoVodModel->where($where)->count();
        $list = $this->videoVodModel->where($where)->order($order)->limit($limit_str)->select();
//        v($this->videoVodModel->getlastsql(),1);
        return ['pagecount' => ceil($total / $limit), 'list' => $list];
    }

    /*
  * 获取date 数据
  */
    protected function getDataVod($where)
    {
        return $this->vodModel->where($where)->find();
    }

    /*
 * 获取date 数据
 */
    protected function getDataList($where)
    {
//vod_id,type_id,type_id_1,group_id,vod_name,vod_sub,vod_en,vod_status,vod_letter,vod_color,vod_tag,vod_class,vod_pic,vod_pic_thumb,vod_pic_slide,vod_actor,vod_director,vod_writer,vod_behind,vod_blurb,vod_remarks,vod_pubdate,vod_total,vod_serial,vod_tv,vod_weekday,vod_area,vod_lang,vod_year,vod_version,vod_state,vod_author,vod_jumpurl,vod_tpl,vod_tpl_play,vod_tpl_down,vod_isend,vod_lock,vod_level,vod_copyright,vod_points,vod_points_play,vod_points_down,vod_hits,vod_hits_day,vod_hits_week,vod_hits_month,vod_duration,vod_up,vod_down,vod_score,vod_score_all,vod_score_num,vod_time,vod_time_add,vod_time_hits,vod_time_make,vod_trysee,vod_douban_id,vod_douban_score,vod_reurl,vod_rel_vod,vod_rel_art,vod_pwd,vod_pwd_url,vod_pwd_play,vod_pwd_play_url,vod_pwd_down,vod_pwd_down_url,vod_content,vod_play_from,vod_play_server,vod_play_note,vod_play_url,vod_down_from,vod_down_server,vod_down_note,vod_down_url,vod_plot,vod_plot_name,vod_plot_detail
//a.vod_id,a.type_id,a.type_id_1,a.group_id,a.vod_name,a.vod_sub,a.vod_en,a.vod_status,a.vod_letter,a.vod_color,a.vod_tag,a.vod_class,a.vod_pic,a.vod_pic_thumb,a.vod_pic_slide,a.vod_actor,a.vod_director,a.vod_writer,a.vod_behind,a.vod_blurb,a.vod_remarks,a.vod_pubdate,a.vod_total,a.vod_serial,a.vod_tv,a.vod_weekday,a.vod_area,a.vod_lang,a.vod_year,a.vod_version,a.vod_state,a.vod_author,a.vod_jumpurl,a.vod_tpl,a.vod_tpl_play,a.vod_tpl_down,a.vod_isend,a.vod_lock,a.vod_level,a.vod_copyright,a.vod_points,a.vod_points_play,a.vod_points_down,a.vod_hits,a.vod_hits_day,a.vod_hits_week,a.vod_hits_month,a.vod_duration,a.vod_up,a.vod_down,a.vod_score,a.vod_score_all,a.vod_score_num,a.vod_time,a.vod_time_add,a.vod_time_hits,a.vod_time_make,a.vod_trysee,a.vod_douban_id,a.vod_douban_score,a.vod_reurl,a.vod_rel_vod,a.vod_rel_art,a.vod_pwd,a.vod_pwd_url,a.vod_pwd_play,a.vod_pwd_play_url,a.vod_pwd_down,a.vod_pwd_down_url,a.vod_content,a.vod_play_from,a.vod_play_server,a.vod_play_note,a.vod_play_url,a.vod_down_from,a.vod_down_server,a.vod_down_note,a.vod_down_url,a.vod_plot,a.vod_plot_name,a.vod_plot_detail
        return $this->vodModel->alias('a')->field('a.vod_id,a.type_id,a.type_id_1,a.group_id,a.vod_name,a.vod_sub,a.vod_en,a.vod_status,a.vod_letter,a.vod_color,a.vod_tag,a.vod_class,a.vod_plot_detail,b.id as b_id,b.is_section as b_is_section,b.reason as b_reason,a.vod_douban_id,a.vod_douban_score,b.code as b_code,b.vod_id as b_vod_id,b.video_id as b_video_id,b.down_ts_url as b_down_ts_url,b.down_mp4_url as b_down_mp4_url,b.down_url as b_down_url,b.weight as b_weight,b.is_down as b_is_down,b.is_sync as b_is_sync')->join('video_vod b', 'a.vod_id=b.vod_id', 'right')->where($where)->find();
    }


}