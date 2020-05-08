<?php
namespace app\common\model;
use think\Db;

class Channel extends Base{

    protected $createTime = false;

    public function listData($where,$order,$page,$limit=20)
    {
        $total = $this->where($where)->count();
        $list = Db::name('Channel')->where($where)->order($order)->page($page)->limit($limit)->select();
        return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>ceil($total/$limit),'limit'=>$limit,'total'=>$total,'list'=>$list];
    }

    public function infoData($where=[],$field='*')
    {
        if(empty($where) || !is_array($where)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }
        $info = Db::name("Channel")->field($field)->where($where)->find();

        if(empty($info)){
            return ['code'=>1002,'msg'=>'获取数据失败'];
        }

        return ['code'=>1,'msg'=>'获取成功','info'=>$info];
    }

    public function saveData($data)
    {
        $data['create_time'] = time();
        if(!empty($data['id'])){
            $where=[];
            $where['id'] = ['eq',$data['id']];

            $res = Db::name('Channel')->where($where)->update($data);
        }
        else{
            $res = Db::name('Channel')->insert($data);
        }
        if(false === $res){
            return ['code'=>1002,'msg'=>'保存失败：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'保存成功'];
    }

    public function delData($where){
        //删除content
        $res = Db::name('Channel')->where($where)->delete();

        if($res===false){
            return ['code'=>1001,'msg'=>'删除失败'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'删除成功'];
    }

    public function fieldData($where,$col,$val){
        if(!isset($col) || !isset($val)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }

        $data = [];
        $data[$col] = $val;
        $res = $this->allowField(true)->where($where)->update($data);
        if($res===false){
            return ['code'=>1001,'msg'=>'设置失败：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'设置成功'];
    }
}
