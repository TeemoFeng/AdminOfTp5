<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/23 0023
 * Time: 19:34
 */
namespace app\user\controller;

use app\admin\model\ApplyCash;
use app\user\model\UserRunningLog;

class Finance extends Common{
    public function initialize(){
        parent::initialize();


    }

    //财务流水
    public function runningAccount()
    {
        $running_type = UserRunningLog::$running_type;
        if(request()->isPost()){
            $data = input('post.');
            $user_id = session('user.id');
            if(empty($data['account_type']) || empty($user_id)){
                return ['code' => 0, 'msg' => '账户类型或用户不能为空'];
            }
            $data['user_id'] = $user_id;
            $where  = $this->makeSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $list = db('user_running_log')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->join(config('database.prefix').'users ab','a.about_id = ab.id','left')
                ->field('a.*,u.username,ab.username about_user')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            if(empty($list))
                $list = [];
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['create_time'] = date('Y-m-d',$v['create_time']);
                $list['data'][$k]['running_type'] = $running_type[$v['running_type']];

            }

            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }
        //账户类型
        $currency = db('currency')->select();
        $currency_arr = [];
        foreach ($currency as $key => $val){
            $currency_arr[] = $val['name'];
        }
        //流水类型
        $this->assign('running_type', $running_type);
        $this->assign('currency_arr', $currency_arr);

        return $this->fetch('runningAccount');
    }

    public function makeSearch($data)
    {
        $where = [];
        $where['a.account_type'] = 1;
        if(!empty($data['account_type'])){
            $where['a.account_type'] = $data['account_type']; //流水类型
        }

        if(!empty($data['running_type'])){
            $where['a.running_type'] = $data['running_type']; //流水类型
        }
        if(!empty($data['start_time']) && empty($data['end_time'])){
            $start_time = strtotime($data['start_time']);
            $where['a.create_time'] = array('egt', $start_time);
        }
        if(!empty($data['end_time']) && empty($data['start_time'])){
            $end_time = strtotime($data['end_time']);
            $where['a.create_time'] = array('elt',$end_time);
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $start_time = strtotime($data['start_time']);
            $end_time = strtotime($data['end_time']);
            $where['a.create_time'] = array('between time', array($start_time, $end_time));
        }
        if(!empty($data['key'])){
            $where['a.id|a.email|a.mobile|a.username'] = array('like', '%' . $data['key'] . '%');
        }

        return $where;
    }

    //币种转换
    public function currencyConversion()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['change_currency'])){
                return ['code' => 0, 'msg' => '转换类型不能为空'];
            }
            if(empty($data['change_num'])){
                return ['code' => 0, 'msg' => '转换数量不能为空'];
            }



        }

        $user_id = session('user.id');

        //查询用户钱包
        $user_purse = db('user_currency_account')->where(['user_id' => $user_id])->find();
        if(empty($user_purse)){
            $user_purse ['cash_currency_num'] = 0.0000; //沙特链余额
            $user_purse ['corpus'] = 0.0000;            //用户本金账户余额
            $user_purse ['consume_num'] = 0.0000;       //消费钱包余额
            $user_purse ['activation_num'] = 0.0000;    //激活钱包余额
            $user_purse ['transaction_num'] = 0.0000;   //交易钱包余额
        }
        $principal_recall = db('bonus_ext_set')->where(['id' => 1])->field('principal_recall')->find();
        $change_currency[1] = '沙特链转换100%阿美币';
        $this->assign('change_currency', $change_currency);
        $this->assign('user_purse', $user_purse);
        $this->assign('principal_recall', $principal_recall['principal_recall']);

        return $this->fetch('currencyConversion');
    }
    
    //提现管理
    public function cashManagement()
    {

        //账户类型
        $currency = db('currency')->select();
        $currency_arr = [];
        foreach ($currency as $key => $val){
            $currency_arr[] = $val['name'];
        }
        $status = ApplyCash::$status;
        $this->assign('currency_arr', $currency_arr);
        $this->assign('status', $status);
        return $this->fetch('cashManagement');
        
    }
    
    //报备提现信息
    public function userWithtrawInformation()
    {
        
    }

    //动态奖转阿美币动态列表
    public function toAmeibiList()
    {

    }
    
    //本金转换
    public function corpusConvert()
    {
        
    }

    //阿美币记录列表
    public function aMeibiLogList()
    {}


}