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

}