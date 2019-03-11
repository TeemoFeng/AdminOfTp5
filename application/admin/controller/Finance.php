<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/11 0011
 * Time: 22:00
 */
namespace app\admin\controller;
use app\admin\model\Currency;
use think\Db;
use think\facade\Request;
class Finance extends Common
{
    //动态奖金转阿美币列表
    public function convert()
    {

    }

    //转账锁仓列表
    public function lockPosition()
    {

    }

    //奖金统计列表
    public function bonusStatistics()
    {
        
    }

    //公司拨比列表
    public function allocationRatio()
    {
        
    }

    //财务流水列表
    public function financialFlow()
    {
        
    }

    //货币充值
    public function currencyRecharge()
    {
        //获取币种类型
        $currency_model = model('currency');
        $currency_list = $currency_model->select();
        $this->assign('currency_list', $currency_list);
        return $this->fetch('currencyRecharge');
    }

    //货币扣除
    public function currencyDeduction()
    {
        //获取币种类型
        $currency_model = model('currency');
        $currency_list = $currency_model->select();
        $this->assign('currency_list', $currency_list);
        return $this->fetch('currencyDeduction');

    }
    
    //申请充值列表
    public function applicationRecharge()
    {
        
    }

    //申请提现列表
    public function applicationForCash()
    {

    }
    

}