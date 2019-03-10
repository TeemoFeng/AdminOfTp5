<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/9 0009
 * Time: 15:36
 */

namespace app\admin\controller;

use think\Exception;
use think\exception\ErrorException;

class Direct extends Common{
    public function index()
    {
        //获取币种名称
        $currency = db('currency')->order('id')->select();
        $currency_arr = !empty($currency) ? $currency : '';
        //获取奖金类型
        $bonus_type = db('bonus_type')->order('id')->select();
        //获取会员级别/接点领取层数
        $user_level = db('user_level')->order('level_id')->select();
        //获取报单额/静态收益比例/直推奖比例[理解为投资分红]
        $fenhong_info = db('bonus_set')->select();
        //获取其他设置
        $other_set = db('bonus_ext_set')->where('id',1)->find();

        $this->assign('currency_arr', $currency_arr);
        $this->assign('bonus_type', $bonus_type);
        $this->assign('user_level', $user_level);
        $this->assign('fenhong_info', $fenhong_info);
        $this->assign('other_set', $other_set);
        return $this->fetch();
    }

    public function configSet()
    {
        try{
            if(request()->isPost()){
                $data        = input('post.');
                $table_name  = $data['table_name'];
                $table_field = $data['data_field'];
                $table_value = $data['data_value'];
                $id = $data['id'];
                if(empty($table_name) || empty($table_field) || empty($table_value) || empty($id)){
                    return ['code' => 0, 'msg' => '参数错误'];
                }
                if($table_name == 'user_level'){
                    $where['level_id'] = $id;
                }else{
                    $where['id'] = $id;
                }
                //更新用户设置参数
                $res = db($table_name)->where($where)->update([$table_field => $table_value]);
                if(false !== $res){
                    return ['code' => 1, 'msg' => '修改成功'];
                }
                return ['code' => 0, 'msg' => '修改失败'];
            }else{
                throw new Exception('请求出错');
            }
        }catch (Exception $e){
            return ['code' => 0, 'msg' =>$e->getMessage()];
        }

    }

}