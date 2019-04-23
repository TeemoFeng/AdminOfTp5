<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/4/16
 * Time: 14:36
 */
namespace app\mobile\controller;

use app\admin\model\CurrencyList;
use app\admin\model\UserCurrencyAccount;
use app\home\model\UserTradeDepute;
use app\home\model\UserTradeDeputeLog;
use app\user\model\UserCurrencyList;
use clt\Lunar;
use think\Db;
use think\db\Where;

class Trade extends Common{

    public function initialize()
    {
        parent::initialize();
        $this->uid = session('user.id');
    }
    //手机版交易首页
    public function index(){
        $user_info = session('user');
        $amei_infos = CurrencyList::where(['en_name' => 'AMB'])->find();
        //获取用户交易账户
        $user_account = UserCurrencyAccount::where(['user_id' => $user_info['id']])->find();
        if(empty($user_account)){
            $user_account['transaction_num'] = 0;
        }
        //获取用户阿美币
        $user_amei = UserCurrencyList::where(['user_id' => $user_info['id'],'currency_id' => $amei_infos['id']])->find();
        $user_account['ameibi_num'] = $user_amei['num'] ?: 0;
        $user_account['ameibi_freeze_num'] = $user_amei['freeze_num'] ?: 0;
        $user_account['ameibi_lock_num'] = $user_amei['lock_num'] ?: 0;

        $amei_info = CurrencyList::where(['status' => 'open', 'en_name' => 'AMB'])->find();
        $high = db('user_trade_depute_log')->where(['trade_status' =>2, 'trade_type' => 2])->order('price DESC')->find();
        $low = db('user_trade_depute_log')->where(['trade_status' =>2, 'trade_type' => 2])->order('price ASC')->find();
        $new_price = db('user_trade_depute_log')->where(['trade_status' =>3])->order('id DESC')->value('price');
        if(empty($new_price)) $new_price = $amei_info['price'];
        $map = new Where();
        $map['trade_status'] = 3;
        $start_time = strtotime(date('Y-m-d 00:00:00'));
        $end_time = strtotime(date('Y-m-d 23:59:59'));
        $map['create_time'] = array('between time', array($start_time, $end_time));
        $vol = db('user_trade_depute_log')->where($map)->order('id DESC')->sum('trade_num');

        $amb_price = 0;
        if(!empty($new_price)){
            $amb_price = $new_price;
        }

        //获取阿美币
        $amei_info['last'] = $new_price ? (string)$new_price : $amei_info['price']; //最新价
        $amei_info['high'] = $high ? (string)$high : $amei_info['price']; //最高价
        $amei_info['low'] = $low ? (string)$low : $amei_info['price'];//最低价
        $amei_info['vol'] = $vol ? (string)$low : 0;//24成交量
        $h_l = bcsub($amei_info['price'], $new_price,4);
        $h_l_s = bcdiv($h_l, $amei_info['price']);
        if($h_l_s < 0){
            $amei_info['h_l_f'] = 1; //负数
        }else{
            $amei_info['h_l_f'] = 2;
        }
        $amei_info['ratio'] = $h_l_s;//涨幅
        $currency_list[] = $amei_info;

        //从委托表中取出卖价前7位
        $list_sell = db('user_trade_depute')->where(['depute_type' => 2,'depute_status' => 1])->order('price DESC')->limit(7)->select();
        $count1 = count($list_sell);
        foreach ($list_sell as $key => $val){

            $amei_account = UserCurrencyList::where(['user_id' => $val['user_id'],'currency_id' => $amei_infos['id']])->value('num');
            if(empty($amei_account)){
                $amei_account = 0;
            }
            $list_sell[$key]['account'] = $amei_account;
            $list_sell[$key]['num2'] = $count1-$key;

        }

        //获取用户托管买币前7位
        $list_buy = db('user_trade_depute')->where(['depute_type' => 1, 'depute_status' => 1])->order('price DESC')->limit(7)->select();
        $count2 = count($list_buy);
        foreach ($list_buy as $key => $val){
            $amei_account = UserCurrencyList::where(['user_id' => $val['user_id'],'currency_id' => $amei_infos['id']])->value('num');
            if(empty($amei_account)){
                $amei_account = 0;
            }
            $list_buy[$key]['account'] = $amei_account;
            $list_buy[$key]['num2'] = $count2-$key;
        }
        //实时成交前10位
        $trade_list = db('user_trade_depute_log')->where(['trade_status' => 2])->limit(10)->order('create_time DESC')->select();
        foreach ($trade_list as $k => $v){
            $trade_list[$k]['time'] = date('m-d H:i', $v['crate_time']);
            $trade_list[$k]['type_str'] = UserTradeDeputeLog::$trade_type[$v['trade_type']] ;
        }
        //当前委托 获取用户委托的前10条
        $this->deputeList();
        //历史委托 获取用户委托的前10条
        $this->deputeListHis();
        $type = UserTradeDepute::$trade_type;
        $status = UserTradeDepute::$status;
        $this->assign('user_info', $user_info);
        $this->assign('type', $type);
        $this->assign('status', $status);
        $this->assign('amei_info', $amei_info);
        $this->assign('user_account', $user_account);
        $this->assign('currency_list', $currency_list);
        $this->assign('list_sell', $list_sell); //挂单卖出列表
        $this->assign('list_buy', $list_buy);   //挂单购买列表
        $this->assign('amb_price', $amb_price); //阿美币现在价格
        $this->assign('trade_list', $trade_list); //实时成交列表
        return $this->fetch('index');
    }

    //交易所获取用户委托列表
    public function deputeList()
    {
        //当前委托 获取用户委托的前10条
        $user_info = session('user');
        $where['depute_status'] = 1;
        $where['user_id'] = $user_info['id'];
        //根绝用户id获取推荐的人员信息
        $list = Db::name('user_trade_depute')
            ->where($where)
            ->order('id DESC')
            ->limit(10)
            ->select();
        $this->assign('depute_list', $list);

    }
    //交易所获取用户委托列表
    public function deputeListHis()
    {
        //当前委托 获取用户委托的前10条
        $user_info = session('user');
        $where['depute_status'] = 2;
        $where['user_id'] = $user_info['id'];
        //根绝用户id获取推荐的人员信息
        $list2 = Db::name('user_trade_depute')
            ->where($where)
            ->order('id DESC')
            ->limit(10)
            ->select();
        $this->assign('depute_list_his', $list2);
    }

    //k线图
    public function kxian()
    {
        return $this->fetch('kxian');
    }

}