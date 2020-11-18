<?php
namespace app\admin\controller;
use think\console\command\make\Model;
use think\Db;

class VideoRecord extends Base{

    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
        $this->assign('title','视频记录管理');
        return $this->fetch('admin@videorecord/index');
    }

    /**
     * 视频列表
     * @return [type] [description]
     */
    public function index1()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];

        // 过滤搜索条件
        $search_data = self::_filterSearchData( $param );

        $order = 'id desc';

        $res = model('VideoRecord')->listData(
                    $search_data['whereOr'],
                    $search_data['where'],
                    $order, 
                    $param['page'],
                    $param['limit']
                );
        $data['page'] = $res['page'];
        $data['limit'] = $res['limit'];
        $data['param'] = $param;
//        p($res['list']);

        $data['code'] = 0;
        $data['count'] = $res['total'];
        $data['msg'] = 'succ';
        $data['data'] = $res['list'];

        return $this->success('succ', null, $data);
    }

    /**
     * 信息维护
     * @return [type] [description]
     */
    public function info()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            try {
                $save_video = model('VideoRecord')->saveData( $param );
                if($save_video['code']>1){
                    return $this->error($save_video['msg']);
                }
                return $this->success($save_video['msg']);
            } catch (\Exception $e) {
                return $this->error('添加成功');
            }
        }

        $id = input('id');
        $where=[];
        $where['id'] = $id;

        $res = model('VideoRecord')->infoData( $where );

        $info = $res['info'];
        $this->assign('info',$info);

        $this->assign('title','视频信息');
        return $this->fetch('admin@videorecord/info');
    }

    /**
     * 删除
     * @return [type] [description]
     */
    public function del() {
        $param = input();
        $ids = isset($param['ids']) ? $param['ids'] : '';

        $res = model('VideoRecord')->delData($ids);

        if($res['code'] > 1){
            return $this->error($res['msg']);
        }
        return $this->success($res['msg']);
    }


    /**
     * 过滤搜索条件
     * @param string $data [description]
     */
    private function _filterSearchData( $param='' )
    {
        $where = [];
        $whereOr = [];
        if (!empty($param['vod_name'])) {
            $name = htmlspecialchars(urldecode($param['vod_name']));
            $where = "( INSTR(vod_name,'{$name}') > 0 )";
        }

        return ['whereOr' => $whereOr, 'where' => $where];
    }
}
