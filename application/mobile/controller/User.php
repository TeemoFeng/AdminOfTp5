<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/4/16
 * Time: 13:27
 */
namespace app\mobile\controller;
use app\admin\model\UserCurrencyAccount;
use clt\Lunar;
class User extends Common{

    public function initialize()
    {
        parent::initialize();
        $this->uid = session('user.id');
    }
    //账户页面
    public function index(){
        //获取用户账号信息
        $account_info = UserCurrencyAccount::where(['user_id' => $this->uid])->find();
        $this->assign('account_info', $account_info);
        return $this->fetch('user');
    }
}