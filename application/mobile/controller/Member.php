<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/21 0021
 * Time: 22:54
 */
namespace app\mobile\controller;
use think\Db;

class Member extends Common{

    public function initialize()
    {
        parent::initialize();
        $this->uid = session('user.id');
    }

    //会员管理首页
    public function index()
    {
        return $this->fetch('index');
    }

    //报单中心注册用户
    public function register()
    {
        $bank = db('Bank')->order('id ASC')->select(); //银行列表
        $user_num = createVipNum();
        $this->assign('bank', $bank);
        $this->assign('usernum', $user_num); //会员编号
        return $this->fetch('register');
    }

    //会员列表
    public function userList()
    {
        return $this->fetch('userList');
    }

    //未激活列表
    public function noActivateList()
    {
        return $this->fetch('noActivateList');
    }

    //会员激活
    public function userActive()
    {
        $user_info = db('users')->where(['id'=>input('id')])->find();
        if($user_info){
            $user_name = $user_info['usernum'].'【'.$user_info['username'].'】';
            $this->assign('user_name', $user_name);
            $this->assign('id', input('id'));
            //获取用户级别表
            $user_level = db('user_level')->order('level_id')->select();

            $this->assign('user_level', $user_level);
        }
        return $this->fetch('userActive');
    }


    //原点复投
    public function originReset()
    {
        //复投数量设置
        $origin_num = db('bonus_ext_set')->where(['id' => 1])->field('double_throw_num')->find();
        //现金币余额
        $cash_currency_num = db('user_currency_account')->where(['user_id' => $this->uid])->value('cash_currency_num');
        if(empty($cash_currency_num)) $cash_currency_num = '0.0000';

        $can_user_num = floatval($cash_currency_num);
        $this->assign('origin_num', $origin_num['double_throw_num']); //复投设置的数量
        $this->assign('cash_currency_num', $cash_currency_num); //现金币余额
        $this->assign('can_use_num', $can_user_num); //现金币可用数量
        return $this->fetch('originReset');
    }

    //撤回复投
    public function withdraw()
    {
        $origin_num = db('bonus_ext_set')->where(['id' => 1])->field('double_throw_recall')->find();
        //现金币余额
        $cash_input_num = db('user_currency_account')->where(['user_id' => $this->uid])->value('cash_input_num');
        if(empty($cash_input_num)) $cash_input_num = '0.0000';

        $can_reset_num = floatval($cash_input_num);
        $this->assign('origin_num', $origin_num['double_throw_recall']); //撤回复投扣除比率
        $this->assign('can_reset_num', $can_reset_num); //可撤回复投数量
        $this->assign('cash_input_num', $cash_input_num); //复投数量
        return $this->fetch('withdraw');
    }




}