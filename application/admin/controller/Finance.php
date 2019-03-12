<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/11 0011
 * Time: 22:00
 */
namespace app\admin\controller;
use app\admin\model\Currency;
use app\admin\model\CurrencyRecharge;
use think\Db;
use think\Exception;
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
        if(request()->isPost()){
            //如果是充值提交
            $data = input('post.');
            $admin_id = session('aid'); //管理员id
            $data['admin_id'] = $admin_id;
            if(CurrencyRecharge::create($data)){
                return ['code' => 1, 'msg' => '充值成功'];
            }else{
                return ['code' => 0, 'msg' => '充值失败'];
            }
        }else{
            //获取币种类型
            $currency_model = model('currency');
            $currency_list = $currency_model->select();
            $this->assign('currency_list', $currency_list);
            return $this->fetch('currencyRecharge');
        }

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

    //根据条件搜索用户
    public function searchUser()
    {
        try{
            if(request()->isPost()){
                $key = input('post.key');
                if(empty($key))
                    throw new Exception('请输入搜索条件');
                $list=db('users')
                    ->where('id|email|mobile|username','like',"%".$key."%")
                    ->order('id desc')
                    ->select();
                if(empty($list))
                    $list = [];
                return ['code' => 1, 'list' => $list];
            }else{
                throw new Exception('请求出错');
            }
        }catch (Exception $e){
            return ['code' => 0, 'msg' => $e->getMessage()];
        }

    }
    

}