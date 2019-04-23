<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/4/22
 * Time: 14:16
 */
namespace app\mobile\controller;
use app\user\model\UserApplyShateCash;
use app\user\model\UserApplyTradeCash;
use think\Db;

class Finance extends Common
{

    public function initialize()
    {
        parent::initialize();
        $this->uid = session('user.id');
    }

    //财务管理首页
    public function index()
    {
        return $this->fetch('index');
    }

    //财务流水
    public function runningAccount()
    {
        return $this->fetch('runningAccount');
    }

    //币种转换
    public function currencyConversion()
    {
        //查询用户钱包
        $user_purse = db('user_currency_account')->where(['user_id' => $this->uid])->find();
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

    //申请提现
    public function applyCash()
    {
        $type = Request()->param('type');
        if(empty($type)){
            $type = 1;
        }
        //bonus 设置
        $principal_recall = db('bonus_ext_set')->where(['id' => 1])->field('extract_num,extract_deduction_ratio,waller_excract_ratio,waller_excract_multiple')->find();
        //获取用户和账户信息
        $where['a.id'] = $this->uid;
        $user_info = db('users')
            ->alias('a')
            ->join(config('database.prefix').'user_currency_account b','a.id = b.user_id','left')
            ->field('a.*,b.cash_currency_num,b.rate,b.consume_num')
            ->where($where)
            ->find();

        //用户可提现的人民币数值
        $cash_currency_num = $user_info['cash_currency_num']; //可提现的沙特数
        $rate = $user_info['rate']; //汇率
        //可提人民币
        $cny_num =  bcmul($cash_currency_num, $rate, 2);

        //账户类型
        $currency = db('currency')->select();
        $currency_arr = [];
        foreach ($currency as $key => $val){
            $currency_arr[] = $val['name'];
        }
        //银行列表
        $bank_list = db('bank')->select();
        //提现方式
        $cash_method = UserApplyShateCash::$cash_method;
        $this->assign('user_info', $user_info);
        $this->assign('cny_num', $cny_num); //可提人民币
        $this->assign('rate', $rate); //该用户汇率
        $this->assign('principal_recall', $principal_recall); //该用户汇率
        $this->assign('currency_arr', $currency_arr); //钱包类型
        $this->assign('bank_list', $bank_list);       //银行列表
        $this->assign('cash_method', $cash_method);       //提现方式
        $this->assign('type', $type);       //访问选择tab
        return $this->fetch('applyCash');
    }

    //动态奖转阿美币列表
    public function toAmeibiList()
    {

        return $this->fetch('toAmeibiList');
    }

    //本金转换
    public function corpusConvert()
    {
        $principal_recall = db('bonus_ext_set')->where(['id' => 1])->field('principal_recall')->find();
        //查询用户钱包
        $user_purse = db('user_currency_account')->where(['user_id' => $this->uid])->find();
        if(empty($user_purse)){
            $user_purse ['cash_currency_num'] = 0.0000; //沙特链余额
            $user_purse ['corpus'] = 0.0000;            //用户本金账户余额
        }

        $change_currency[1] = '本金账户100%转换沙特链';
        $this->assign('change_currency', $change_currency);
        $this->assign('user_purse', $user_purse);
        $this->assign('principal_recall', $principal_recall['principal_recall']); //转换扣除比例
        return $this->fetch('corpusConvert');
    }

    //阿美币记录列表
    public function aMeibiLogList()
    {

        return $this->fetch('aMeibiLogList');
    }


}