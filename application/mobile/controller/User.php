<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/4/16
 * Time: 13:27
 */
namespace app\mobile\controller;
use app\admin\model\ApplyRecharge;
use app\admin\model\UserCurrencyAccount;
use app\home\model\UserTradeDeputeLog;
use app\user\model\Currency;
use app\user\model\UserApplyActive;
use app\user\model\UserApplyShateCash;
use app\user\model\UserApplyTradeCash;
use clt\Lunar;
use think\Db;

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

    //充值提现记录
    public function rechargeAndcash()
    {
        $where['user_id'] = $this->uid; //默认查询卖单

        if(request()->isPost()){
            $data   =input('post.');
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            if($data['type'] == 1){
                //充值记录
                $list = db('user_apply_active')
                    ->where($where)
                    ->order('id DESC')
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();
                foreach ($list['data'] as $k => $v){
                    $list['data'][$k]['status'] = UserApplyShateCash::$status[$v['status']];
                    $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                    $list['data'][$k]['pay_method'] = ApplyRecharge::$cash_method[$v['pay_method']];

                }
                return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

            }

            if($data['type'] == 2){
                //提现记录
                $list = db('user_apply_trade_cash')
                    ->where($where)
                    ->order('id DESC')
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['status'] = UserApplyShateCash::$status[$v['status']];
                    $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

                }
                return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
            }


        }
        return $this->fetch('rechargeAndcash');
    }

    //成交记录
    public function orderList()
    {
        if(request()->isPost()){
            $data   =input('post.');
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $where['users_id'] = $this->uid;
//            $where['trade_status'] = UserTradeDeputeLog::STATUS3;
            //订单记录
            $list = db('user_trade_depute_log')
                ->where($where)
                ->order('id DESC')
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k => $v){
                $list['data'][$k]['trade_status_str'] = UserTradeDeputeLog::$trade_status[$v['trade_status']];
                $list['data'][$k]['trade_type_str'] = UserTradeDeputeLog::$trade_type[$v['trade_type']];
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $list['data'][$k]['currency_name'] = Db::name('currency_list')->where(['id' => $v['trade_currency']])->value('name');
                if(empty($list['data'][$k]['currency_name'])){
                    $list['data'][$k]['currency_name'] = '阿美币';
                }

            }

            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];


        }
        return $this->fetch('orderList');
    }

    //用户交易详情
    public function orderDetail()
    {
        $id = input('id');
        $user_info = session('user');
        $trade_count = Db::name('user_trade_depute')->where(['user_id' => $user_info['id'], 'status' => ['in', '2,3']])->count();
        $order_info = Db::name('user_trade_depute_log')->where(['id' => $id])->find();  //订单详情
        $order_info['trade_status_str'] = UserTradeDeputeLog::$trade_status[$order_info['trade_status']];
        $order_info['create_time'] = date('Y-m-d H:i:s', $order_info['create_time']);
        //获取卖家信息
        $sell_info = Db::name('users')->where(['id' => $order_info['about_id']])->find();
        $bank_name = Db::name('bank')->where(['id' => $sell_info['bank_id']])->value('bank_name');
        $sell_info['bank_name'] = $bank_name;
        $this->assign('order_info', $order_info);
        $this->assign('user_info', $user_info);
        $this->assign('trade_count', $trade_count);
        $this->assign('sell_info', $sell_info);
        return $this->fetch('orderDetail');
    }


    //个人资料
    public function userInfo()
    {
        $user_info = session('user');
        $this->assign('user_info', $user_info);
        return $this->fetch('userInfo');
    }

    //报备银行信息
    public function baobei()
    {
        $user_info = session('user');
        $bank = db('Bank')->order('id ASC')->select(); //银行列表

        $this->assign('user_info', $user_info);
        $this->assign('bank', $bank);
        //判断用户是否报备银行
        if($user_info['is_report'] == 0){
            return $this->fetch('baobei');
        }else{
            return $this->fetch('havebaobei');

        }
    }

    //修改密码
    public function updatePas()
    {
        return $this->fetch('safeCenter');
    }

    //修改登录密码
    public function loginPas()
    {
        return $this->fetch('loginPas');
    }

    //修改安全密码
    public function safePas()
    {
        return $this->fetch('safePas');
    }

    //结算系统
    public function userCenter()
    {
        return $this->fetch('userCenter');
    }






}