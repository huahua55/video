<?php
namespace app\admin\controller;
use think\console\command\make\Model;
use think\Db;

class Svideo extends Base{

    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
        $this->assign('title','推荐短视频管理');
        return $this->fetch('admin@svideo/index');
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

        $res = model('Svideo')->listData(
                    $search_data['whereOr'],
                    $search_data['where'],
                    $order, 
                    $param['page'],
                    $param['limit']
                );
        $data['page'] = $res['page'];
        $data['limit'] = $res['limit'];
        $data['param'] = $param;


        $data['code'] = 0;
        $data['count'] = $res['total'];
        $data['msg'] = 'succ';
        $data['data'] = $res['list'];

        return $data;
    }

    /**
     * 视频详情
     * @return [type] [description]
     */
    public function info()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            $save_video = model('svideo')->saveData( $param );
            if($save_video['code']>1){
                return $this->error($save_video['msg']);
            }
            return $this->success($save_video['msg']);
        }

        $id = input('id');
        $where=[];
        $where['id'] = $id;

        $res = model('svideo')->infoData( $where );

        $info = $res['info'];
        $this->assign('info',$info);

        //分类
        $type_tree = model('Type')->getCache('type_tree');
        $this->assign('type_tree',$type_tree);

        $this->assign('title','视频信息');
        return $this->fetch('admin@svideo/info');
    }

    /**
     * 获取审核条件
     * @return [type] [description]
     */
    public function getExamine()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) < 1 ? $this->_pagesize : $param['limit'];
        $where = [];

        if (!empty($param['name'])) {
            ;
            $where['reasons'] = ['like', '%' . $param['name'] . '%'];
        }
        $order = 'id desc';
        $res = model('svideo')->listData1($where, $order, $param['page'], $param['limit']);
        $data['code'] = 0;
        $data['count'] = $res['total'];
        $data['msg'] = 'succ';
        $data['data'] = $res['list'];
        return $data;
    }

    /**
     * 审核状态修改
     * @return [type] [description]
     */
    public function updateExamine()
    {
        $param = input();
        // 主键id
        $id = $param['id'] ?? '';
        $is_examine = $param['is_examine'] ?? '';
        // 审核理由表主键id
        $examine_id = $param['examine_id'] ?? '';
        $data['code'] = 0;
        $data['msg'] = 'error';
        $data['data'] = [];
        if ( !empty( $id ) ) {
            $video_where['id'] = $id;
            $video_edit_data['is_examine'] = $is_examine;
            $video_edit_data['e_id'] = $examine_id;
            $video_edit = Db::table('svideo')->where( $video_where )->update( $video_edit_data );
            
            if ( $video_edit !== false ) {
                $data['msg'] = 'succ';
            }
        }
        return $data;
    }

    /**
     * 修改视频状态
     * @return [type] [description]
     */
    public function updateStatus()
    {
        $param = input();
        // 集表主键id
        $collection_id = $param['id'] ?? '';
        // 审核理由表主键id
        $status = $param['status'] ?? '';
        $data['code'] = 0;
        $data['msg'] = 'error';
        $data['data'] = [];

        $video_where['id'] = ['in', implode(',', array_unique($collection_id))];

        Db::startTrans();

        $video_edit_data['status'] = $status;
        // 根据主键id修改相关视频
        $video_edit = Db::table('svideo')->where( $video_where )->update($video_edit_data);

        if ( $video_edit !== false ) {
            Db::commit();
            $data['msg'] = 'succ';
        } else {
            Db::rollback();
        }
        return $data;
    }

    /**
     * 过滤搜索条件
     * @param string $data [description]
     */
    private function _filterSearchData( $param='' )
    {
        $where = [];
        $whereOr = [];
        if (!empty($param['name'])) {
            $name = htmlspecialchars(urldecode($param['name']));
            $whereOr = "( INSTR(name,'{$name}') > 0 OR id = '{$name}' )";

        }
        if (isset($param['is_examine']) && $param['is_examine'] != "") {
            $where['is_examine'] = $param['is_examine'];
        }
        if (isset($param['status']) && $param['status'] != "") {
            $where['status'] = $param['status'];
        }

        return ['whereOr' => $whereOr, 'where' => $where];
    }
}
