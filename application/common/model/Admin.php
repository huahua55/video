<?php
namespace app\common\model;
use think\Db;

class Admin extends Base {
    // 设置数据表（不含前缀）
    protected $name = 'admin';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

    // 自动完成
    protected $auto       = [];
    protected $insert     = [];
    protected $update     = [];

    public function getAdminStatusTextAttr($val,$data)
    {
        $arr = [0=>'禁用',1=>'启用'];
        return $arr[$data['admin_status']];
    }

    public function listData($where,$order,$page,$limit=20)
    {
        // 管理员id为1和2超级管理员看全部
        $admin_id = cookie('admin_id');
        if (!in_array($admin_id, [1, 2])) {
            // 根据角色组获取该角色组下的用户
            $get_role_user = model('admin_role')->getRoleByUserId($admin_id);
            $get_role_group_admin_ids = model('roles')->getRoleGroupAdminIdsByRoleId($get_role_user['data']['role_id'], $admin_id);
            if (empty($get_role_group_admin_ids['data'])) {
                return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>0,'limit'=>$limit,'total'=>0,'list'=>$list];
            } else {
                $get_role_group_admin_ids['data'][] = $admin_id;
                $where['admin_id'] = ['in', $get_role_group_admin_ids['data']];
            }
        }

        $total = $this->where($where)->count();
        $list = Db::name('Admin')->where($where)->order($order)->page($page)->limit($limit)->select();
        return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>ceil($total/$limit),'limit'=>$limit,'total'=>$total,'list'=>$list];
    }

    public function infoData($where,$field='*')
    {
        if(empty($where) || !is_array($where)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }
        $info = $this->field($field)->where($where)->find();

        if(empty($info)){
            return ['code'=>1002,'msg'=>'获取数据失败'];
        }
        $info = $info->toArray();

        $info['admin_pwd'] = '';
        return ['code'=>1,'msg'=>'获取成功','info'=>$info];
    }

    public function saveData($data)
    {
        // if(!empty($data['admin_auth'])){
        //     $data['admin_auth'] = ','.join(',',$data['admin_auth']).',';
        // }
        // else{
        //     $data['admin_auth'] = '';
        // }
        Db::startTrans();
        $validate = \think\Loader::validate('Admin');
        if(!empty($data['admin_id'])){
            if(!$validate->scene('edit')->check($data)){
                return ['code'=>1001,'msg'=>'参数错误：'.$validate->getError() ];
            }

            if(empty($data['admin_pwd'])){
                unset($data['admin_pwd']);
            }
            else{
                $data['admin_pwd'] = md5($data['admin_pwd']);
            }
            $where=[];
            $where['admin_id'] = ['eq',$data['admin_id']];
            $res = $this->where($where)->update($data);
        }
        else{
            if(!$validate->scene('edit')->check($data)){
                return ['code'=>1002,'msg'=>'参数错误：'.$validate->getError() ];
            }
            $data['admin_auth'] = '';
            $data['admin_pwd'] = md5($data['admin_pwd']);
            $res = $this->insert($data);
            $data['admin_id'] = $this->getLastInsID();
        }

        // 用户角色
        $user_role = model('admin_role')->saveData($data['admin_id'], $data['role_id']);
        if(false === $res && $user_role['code'] != 1){
            Db::rollback();
            return ['code'=>1003,'msg'=>''.$this->getError() ];
        }
        Db::commit();
        return ['code'=>1,'msg'=>'保存成功'];
    }

    public function delData($where)
    {
        $res = $this->where($where)->delete();
        if($res===false){
            return ['code'=>1001,'msg'=>'删除失败'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'删除成功'];
    }

    public function fieldData($where,$col,$val)
    {
        if(!isset($col) || !isset($val)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }

        $data = [];
        $data[$col] = $val;
        $res = $this->where($where)->update($data);
        if($res===false){
            return ['code'=>1002,'msg'=>'设置失败'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'设置成功'];
    }

    public function login($data)
    {
        if(empty($data['admin_name']) || empty($data['admin_pwd'])  ) {
            return ['code'=>1001,'msg'=>'参数错误'];
        }

        if(!captcha_check($data['verify'])){
            return ['code'=>1002,'msg'=>'验证码错误'];
        }

        $where=[];
        $where['admin_name'] = ['eq',$data['admin_name']];
        $where['admin_pwd'] = ['eq',md5($data['admin_pwd'])];
        $where['admin_status'] = ['eq',1];

        $row = $this->where($where)->find();

        if(empty($row)){
            return ['code'=>1003,'msg'=>'账号或密码错误或禁用状态'];
        }
        // 暂时去掉单点登录限制
        // $random = md5(rand(10000000,99999999));
        $random = 10000001;
        $ip = sprintf('%u',ip2long(request()->ip()));
        if($ip>2147483647){
            $ip=0;
        }
        $update['admin_login_ip'] = $ip;
        $update['admin_login_time'] = time();
        $update['admin_login_num'] = $row['admin_login_num'] + 1;
        $update['admin_random'] = $random;
        $update['admin_last_login_time'] = $row['admin_login_time'];
        $update['admin_last_login_ip'] = $row['admin_login_ip'];

        // 更新权限
        $get_user_role = self::_getUserRole($row['admin_id']);
        if ($get_user_role['code'] == 1) {
            $update['admin_auth'] = $get_user_role['data'];
        }

        $res = $this->where($where)->update($update);
        if($res===false){
            return ['code'=>1004,'msg'=>'更新登录信息失败'];
        }

        cookie('admin_id',$row['admin_id']);
        cookie('admin_name',$row['admin_name']);
        cookie('admin_check',md5($random . $row['admin_name'] .$row['admin_id']) );

        return ['code'=>1,'msg'=>'登录成功'];
    }

    public function logout()
    {
        cookie('admin_id',null);
        cookie('admin_name',null);
        cookie('admin_check',null);

        return ['code'=>1,'msg'=>'退出成功'];
    }

    public function checkLogin()
    {
        $admin_id = cookie('admin_id');
        $admin_name = cookie('admin_name');
        $admin_check = cookie('admin_check');

        if(empty($admin_id) || empty($admin_name) || empty($admin_check)){
            return ['code'=>1001, 'msg'=>'未登录'];
        }

        $where = [];
        $where['admin_id'] = $admin_id;
        $where['admin_name'] = $admin_name;
        $where['admin_status'] =1 ;

        $info = $this->where($where)->find();
        if(empty($info)){
            return ['code'=>1002,'msg'=>'未登录'];
        }
        $info = $info->toArray();

        $login_check = md5($info['admin_random'] . $info['admin_name'] .$info['admin_id']) ;
        if($login_check != $admin_check){
            return ['code'=>1003,'msg'=>'未登录'];
        }
        return ['code'=>1,'msg'=>'已登录','info'=>$info];
    }

    /**
     * 获取用户角色和权限
     * @param  [type] $admin_id [description]
     * @return [type]           [description]
     */
    private function _getUserRole($admin_id) {
        $role_info = model('admin_role')->getRoleByUserId($admin_id);
        if ($role_info['code'] > 1) {
            return $role_info;
        }
        return model('role_rule_link')->getRuleByRoleId($role_info['data']['role_id']);
    }
    
}