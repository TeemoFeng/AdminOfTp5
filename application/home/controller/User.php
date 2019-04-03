<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/31
 * Time: 11:58
 */
namespace app\home\controller;
use app\admin\model\ApplyCash;
use app\admin\model\ApplyRecharge;
use app\admin\model\Currency;
use app\admin\model\CurrencyList;
use app\admin\model\UserApplyTradeCash;
use app\admin\model\UserCurrencyAccount;
use app\home\model\UserTradeDepute;
use app\home\model\UserTradeDeputeLog;
use app\user\model\UserRunningLog;
use think\Db;
use think\db\Where;
use think\Session;

class User extends Common
{

    protected $uid;
    public function initialize()
    {
        parent::initialize();
        $this->uid=session('user.id');
    }

    public function userHome()
    {
        //用户信息
        $user_info = session('user');
        //交易记录

        //上架记录

        //
        $this->assign('user_info', $user_info);
        return $this->fetch('userHome');
    }

    //二维码
    public function ic()
    {
        //获取自己邀请的佣金排名
        return $this->fetch('ic');
    }
    
    //提现
    public function withdrawCash()
    {
        $user_info = session('user');
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['cash_method'])){
                return ['code' => 0, 'msg' => '提现方式不能为空'];
            }
            if(empty($data['mobile'])){
                return ['code' => 0, 'msg' => '请绑定手机号'];
            }
            if(empty($data['bank_user']) || empty($data['bank_account'])){
                return ['code' => 0, 'msg' => '尚未进行实名账户认证'];
            }
            if(empty($data['cash_sum']) || empty($data['real_sum'])){
                return ['code' => 0, 'msg' => '提取金额不能为空'];
            }

            $poundage = bcsub($data['cash_sum'],$data['real_sum'], 2);
            $save['user_id'] = $user_info['id'];
            $save['cash_sum'] = $data['cash_sum'];
            $save['real_sum'] = $data['real_sum'];
            $save['poundage'] = $poundage;
            $save['status'] = ApplyCash::NOT_LOOK;
            $save['create_time'] = time();
            $save['cash_method'] = $data['cash_method'];
            $save['remark'] = $data['remark'];

            Db::startTrans();
            $ins = UserApplyTradeCash::create($save);
            if($ins){
                //用户钱包账户减去交易钱包额度
                $user_transaction_num =  UserCurrencyAccount::where(['user_id' => $user_info['id']])->value('transaction_num'); //获取交易钱包余额
                $transaction_num = bcsub($user_transaction_num, $data['cash_sum'], 2); //用户钱包剩余
                $up_data = [
                    'transaction_num' => $transaction_num,
                ];

                $res2 = UserCurrencyAccount::where(['user_id' => $user_info['id']])->update($up_data);
                if($res2 !== false){
                    Db::commit();
                    //提现扣除记录
                    $tran_num = bcsub($user_transaction_num, $data['real_sum'], 2); //用户钱包减去真是交易剩余
                    UserRunningLog::create([
                        'user_id'  =>  $user_info['id'],
                        'about_id' =>  $user_info['id'],
                        'running_type'  => UserRunningLog::TYPE5, //提现扣除
                        'account_type'  => Currency::TRADE,
                        'change_num'    => -$data['real_sum'],
                        'balance'       => $tran_num,
                        'create_time'   => time(),
                        'remark'        => $data['remark'],
                        'order_id'      => $ins->id,
                        'status'        => 0, //提现扣除装填默认为0 待批准之后更新显示
                    ]);
                    //提现手续费记录
                    $tran_num2 = bcsub($tran_num, $data['poundage'], 2); //继续减去手续费剩余
                    UserRunningLog::create([
                        'user_id'  =>  $user_info['id'],
                        'about_id' =>  $user_info['id'],
                        'running_type'  => UserRunningLog::TYPE26,
                        'account_type'  => Currency::TRADE,
                        'change_num'    => -$data['poundage'],
                        'balance'       => $tran_num2,
                        'create_time'   => time(),
                        'remark'        => $data['remark'],
                        'order_id'      => $ins->id,
                        'status'        => 0, //提现扣除装填默认为0 待批准之后更新显示
                    ]);
                    //提现给油卡记录
                    $tran_num3 = bcsub($tran_num2, $data['geiyouka'], 2); //继续减去手续费剩余
                    UserRunningLog::create([
                        'user_id'  =>  $user_info['id'],
                        'about_id' =>  $user_info['id'],
                        'running_type'  => UserRunningLog::TYPE27,
                        'account_type'  => Currency::TRADE,
                        'change_num'    => -$data['geiyouka'],
                        'balance'       => $tran_num3,
                        'create_time'   => time(),
                        'remark'        => $data['remark'],
                        'order_id'      => $ins->id,
                        'status'        => 0, //提现扣除装填默认为0 待批准之后更新显示
                    ]);

                    return ['code' => 1, 'msg' => '提现申请成功'];

                }else{
                    Db::rollback();
                    return ['code' => 0, 'msg' => '申请失败请重试'];
                }
            }else{
                Db::rollback();
                return ['code' => 0, 'msg' => '申请失败请重试'];
            }

        }
        $bank = db('Bank')->order('id ASC')->select(); //银行列表
        $cash_method = ApplyRecharge::$cash_method; //提现方式
        $bonus_set = db('bonus_ext_set')->where('id',1)->find(); //提现设置
        $trade_account = UserCurrencyAccount::where(['user_id' => $user_info['id']])->value('transaction_num');  //用户交易账户余额
        //获取用户最近10次提现记录
        $user_cash_list = UserApplyTradeCash::where(['user_id' => $user_info['id']])->limit(10)->select();
        $status = ApplyCash::$status;
        $this->assign('user_info', $user_info);
        $this->assign('cash_method', $cash_method);
        $this->assign('bank_list', $bank);
        $this->assign('bonus_set', $bonus_set);
        $this->assign('trade_account', $trade_account);
        $this->assign('user_cash_list', $user_cash_list);
        $this->assign('status', $status);
        return $this->fetch('withdrawCash');
    }


    //历史委托订单
    public function historical()
    {
        $type = UserTradeDepute::$trade_type;
        $status = UserTradeDepute::$status;
        $user_id = session('user.id');
        $map['depute_status'] = 2;
        $map['user_id'] = $user_id;
        //交易币种现在只支持阿美币
        //交易记录
        if(request()->isPost()){

            $data   = input('post.');
            $where  = $this->makeSearch($data, 2);

            $where['user_id'] = $user_id;
            //根绝用户id获取推荐的人员信息
            $list = Db::name('user_trade_depute')
                ->where($where)
                ->select();
            $this->assign('type', $type);
            $this->assign('status', $status);
            $this->assign('list', $list);
            $this->assign('search', $data);
            return $this->fetch('historical');
        }
        $list = Db::name('user_trade_depute')->where($map)->select();
        $this->assign('type', $type);
        $this->assign('status', $status);
        $this->assign('list', $list);
        return $this->fetch('historical');

    }

    //搜索条件
    public function makeSearch($data, $type)
    {
        $where = new Where();
        $where['depute_status'] = $type;
        if(!empty($data['status'])){
            $where['status'] = $data['status'];
        }

        if(!empty($data['depute_type'])){
            $where['depute_type'] = $data['depute_type'];
        }
        if(!empty($data['time'])){
            $time_arr = explode(' - ',$data['time']);
            $start_time = strtotime($time_arr[0]);
            $end_time = strtotime($time_arr[1]);
            $where['create_time'] = array('between time', array($start_time, $end_time));
        }
        return $where;
    }

    //当前委托订单
    public function entrustment()
    {

        $type = UserTradeDepute::$trade_type;
        $status = UserTradeDepute::$status;
        $user_id = session('user.id');
        $map['depute_status'] = 1;
        $map['user_id'] = $user_id;
        //交易币种现在只支持阿美币
        //交易记录
        if(request()->isPost()){

            $data   = input('post.');
            $where  = $this->makeSearch($data,1);
            $where['user_id'] = $user_id;
            //根绝用户id获取推荐的人员信息
            $list = Db::name('user_trade_depute')
                ->where($where)
                ->select();
            $this->assign('type', $type);
            $this->assign('status', $status);
            $this->assign('list', $list);
            $this->assign('search', $data);
            return $this->fetch('entrustment');
        }
        $list = Db::name('user_trade_depute')->where($map)->select();
        $this->assign('type', $type);
        $this->assign('status', $status);
        $this->assign('list', $list);
        return $this->fetch('entrustment');
    }

    //交易大厅
    public function currencyExchange()
    {
        $user_info = session('user');
        //获取用户交易账户
        $user_account = UserCurrencyAccount::where(['user_id' => $user_info['id']])->find();
        //币种列表
        $currency_list = CurrencyList::where(['status' => 'open'])->select();
        $amb_price = 0;

        foreach ($currency_list as $k => $v){
            if($v['en_name'] == 'AMB'){
                $trade = db('user_trade_depute_log')->where(['status' =>1,'trade_type' => 2])->order('price DESC')->find();
                $currency_list[$k]['price_s'] = 0; //阿美币最新价格
                $amb_price = $trade;
            }else{
                $currency_list[$k]['price_s'] = 0;
            }
            $currency_list[$k]['high'] = 34.15; //最高价
            $currency_list[$k]['last'] = 33.15; //最新成交价
            $currency_list[$k]['low'] = 32.05; //最低价
            $currency_list[$k]['vol'] = 10532696.3919; //24成交量
            $currency_list[$k]['ratio'] = 0.3; //24成交量

        }

        //阿美币
        $amei_info = $currency_list[0];

        //从委托表中取出卖价前7位
        $list_sell = db('user_trade_depute')->where(['depute_type' => 2,'depute_status' => 1])->order('price DESC')->select();
        foreach ($list_sell as $key => $val){
            $user_account = UserCurrencyAccount::where(['user_id' => $val['user_id']])->find();
            $list_sell[$key]['account'] = $user_account['ameibi_num'];
            $list_sell[$key]['num2'] = 7-$key;

        }

        //获取用户托管买币前7位
        $list_buy = db('user_trade_depute')->where(['depute_type' => 1, 'depute_status' => 1])->order('price DESC')->select();
        foreach ($list_buy as $key => $val){
            $user_account = UserCurrencyAccount::where(['user_id' => $val['user_id']])->find();
            $list_buy[$key]['account'] = $user_account['ameibi_num'];
            $list_buy[$key]['num2'] = 7-$key;
        }
        //实时成交前30位
        $trade_list = db('user_trade_depute_log')->where(['status' => 1])->limit(30)->order('create_time DESC')->select();
        foreach ($trade_list as $k => $v){
            $trade_list[$k]['time'] = date('m-d H:i', $v['crate_time']);
            $trade_list[$k]['type_str'] = UserTradeDeputeLog::$trade_type[$v['trade_type']] ;
        }

        $this->assign('user_info', $user_info);
        $this->assign('amei_info', $amei_info);
        $this->assign('user_account', $user_account);
        $this->assign('currency_list', $currency_list);
        $this->assign('list_sell', $list_sell); //挂单卖出列表
        $this->assign('list_buy', $list_buy);   //挂单购买列表
        $this->assign('amb_price', $amb_price); //阿美币现在价格
        $this->assign('trade_list', $trade_list); //实时成交列表
        return $this->fetch('currencyExchange');
    }


    //实时获取数据
    public function getCurrencyExchange()
    {
        if(request()->isAjax()){
            $list_sell = db('user_trade_depute')->where(['depute_type' => 2,'depute_status' => 1])->order('price DESC')->select();
            foreach ($list_sell as $key => $val){
                $user_account = UserCurrencyAccount::where(['user_id' => $val['user_id']])->find();
                $list_sell[$key]['account'] = $user_account['ameibi_num'];
                $list_sell[$key]['num2'] = 7-$key;

            }
            //获取用户托管买币前7位
            $list_buy = db('user_trade_depute')->where(['depute_type' => 1, 'depute_status' => 1])->order('price DESC')->select();
            foreach ($list_buy as $key => $val){
                $user_account = UserCurrencyAccount::where(['user_id' => $val['user_id']])->find();
                $list_buy[$key]['account'] = $user_account['ameibi_num'];
                $list_buy[$key]['num2'] = 7-$key;
            }
            $trade_list = db('user_trade_depute_log')->where(['status' => 1])->limit(30)->order('create_time DESC')->select();
            foreach ($trade_list as $k => $v){
                $trade_list[$k]['time'] = date('H:i:s', $v['crate_time']);
                $trade_list[$k]['type_str'] = UserTradeDeputeLog::$trade_type[$v['trade_type']] ;
            }

            return['code' => 1, 'sell' => $list_sell, 'buy' => $list_buy, 'trade' => $trade_list];
        }

    }

    //买入阿美币提交
    public function buyCurrency()
    {
        if(request()->isPost()){
            $data = input('post.');
            //记录到用户交易委托表
            if(empty($data['user_id']) || empty($data['buy_num']) || empty($data['trade_num']) || empty($data['poundage'])){
                return ['code' => 0, 'msg' => '缺少必填项'];
            }
            $save = [
                'user_id'           => $data['user_id'],
                'depute_type'       => $data['depute_type'],
                'status'            => 1,
                'price'             => $data['price'],
                'depute_currency'   => 1, //购买币种默认为阿美币
                'trade_currency'    => 1, //购买币种默认为阿美币
                'num'               => $data['buy_num'], //购买数量
                'sum'               => $data['sum'],
                'poundage'          => $data['poundage'],
                'real_sum'          => $data['trade_num'],
                'depute_status'     => UserTradeDepute::DEPUTE1,
                'create_time'       => time()
            ];
            Db::startTrans();
            $res =  UserTradeDepute::create($save);
            if($res !== false){
                $user_transaction_num =  UserCurrencyAccount::where(['user_id' => $data['user_id']])->value('transaction_num'); //获取交易钱包余额
                $transaction_num = bcsub($user_transaction_num, $data['sum'], 2); //用户交易钱包直接减钱（人民币）
                $up_data = [
                    'transaction_num' => $transaction_num,
                ];

                $res2 = UserCurrencyAccount::where(['user_id' => $data['user_id']])->update($up_data);
                if($res2 !== false){
                    Db::commit();
                    //购买阿美币记录
                    $tran_num = bcsub($user_transaction_num, $data['sum'], 2); //用户钱包减去交易总数
//                    UserRunningLog::create([
//                        'user_id'  =>  $data['user_id'],
//                        'about_id' =>  $data['user_id'],
//                        'running_type'  => UserRunningLog::TYPE29, //交易扣除
//                        'account_type'  => Currency::TRADE,
//                        'change_num'    => -$data['sum'],
//                        'balance'       => $tran_num,
//                        'create_time'   => time(),
//                        'remark'        => $data['remark'],
//                        'order_id'      => $res->id,
//                        'status'        => 1, //提现扣除装填默认为0 待批准之后更新显示
//                    ]);
//                    //购买手续费记录
//                    $tran_num2 = bcsub($tran_num, $data['poundage'], 2); //继续减去手续费剩余
//                    UserRunningLog::create([
//                        'user_id'  =>  $data['user_id'],
//                        'about_id' =>  $data['user_id'],
//                        'running_type'  => UserRunningLog::TYPE30,
//                        'account_type'  => Currency::TRADE,
//                        'change_num'    => -$data['poundage'],
//                        'balance'       => $tran_num2,
//                        'create_time'   => time(),
//                        'remark'        => $data['remark'],
//                        'order_id'      => $res->id,
//                        'status'        => 1,
//                    ]);

                    return ['code' => 1, 'msg' => '购买挂单上架成功'];

                }else{
                    Db::rollback();
                    return ['code' => 0, 'msg' => '购买申请失败请重试'];
                }
            }else{
                Db::rollback();
                return ['code' => 0, 'msg' => '购买申请失败请重试'];
            }


        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }
    }

    //卖出阿美币提交
    public function sellCurrency()
    {
        if(request()->isPost()){
            $data = input('post.');
            //记录到用户交易委托表
            if(empty($data['user_id']) || empty($data['sell_num']) || empty($data['trade_num']) || empty($data['poundage'])){
                return ['code' => 0, 'msg' => '缺少必填项'];
            }
            $save = [
                'user_id'           => $data['user_id'],
                'depute_type'       => $data['depute_type'],
                'status'            => 1,
                'price'             => $data['price'],
                'depute_currency'   => 1, //卖出币种默认为阿美币
                'trade_currency'    => 1, //卖出币种默认为阿美币
                'num'               => $data['sell_num'], //卖出数量
                'sum'               => $data['sum'],
                'poundage'          => $data['poundage'],
                'real_sum'          => $data['trade_num'],
                'depute_status'     => UserTradeDepute::DEPUTE1, //正在委托
                'create_time'       => time()
            ];
            Db::startTrans();
            $res =  UserTradeDepute::create($save);
            if($res !== false){
                $ameibi_num =  UserCurrencyAccount::where(['user_id' => $data['user_id']])->value('ameibi_num'); //获取交易钱包阿美币余额
                $ameibi_num = bcsub($ameibi_num, $data['num'], 2); //用户阿美币钱包直接减数量
                $up_data = [
                    'ameibi_num' => $ameibi_num,
                ];

                $res2 = UserCurrencyAccount::where(['user_id' => $data['user_id']])->update($up_data);
                if($res2 !== false){
                    Db::commit();
                    //卖出阿美币数量记录
//                    $tran_num = bcsub($ameibi_num, $data['num'], 2); //用户钱包减去真是交易剩余
//                    UserRunningLog::create([
//                        'user_id'  =>  $data['user_id'],
//                        'about_id' =>  $data['user_id'],
//                        'running_type'  => UserRunningLog::TYPE29, //交易扣除
//                        'account_type'  => Currency::TRADE,
//                        'change_num'    => -$data['trade_num'],
//                        'balance'       => $tran_num,
//                        'create_time'   => time(),
//                        'remark'        => $data['remark'],
//                        'order_id'      => $res->id,
//                        'status'        => 1, //提现扣除装填默认为0 待批准之后更新显示
//                    ]);
//                    //购买手续费记录
//                    $tran_num2 = bcsub($tran_num, $data['poundage'], 2); //继续减去手续费剩余
//                    UserRunningLog::create([
//                        'user_id'  =>  $data['user_id'],
//                        'about_id' =>  $data['user_id'],
//                        'running_type'  => UserRunningLog::TYPE30,
//                        'account_type'  => Currency::TRADE,
//                        'change_num'    => -$data['poundage'],
//                        'balance'       => $tran_num,
//                        'create_time'   => time(),
//                        'remark'        => $data['remark'],
//                        'order_id'      => $res->id,
//                        'status'        => 1,
//                    ]);

                    return ['code' => 1, 'msg' => '购买挂单上架成功'];

                }else{
                    Db::rollback();
                    return ['code' => 0, 'msg' => '购买申请失败请重试'];
                }
            }else{
                Db::rollback();
                return ['code' => 0, 'msg' => '购买申请失败请重试'];
            }


        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }

    }

    //用户托管记录
    public function otcTrade()
    {
        return $this->fetch('trade');
    }




}