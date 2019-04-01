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
use app\admin\model\UserApplyTradeCash;
use app\admin\model\UserCurrencyAccount;
use app\home\model\UserTradeDepute;
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
            return $this->fetch('historical');
        }
        $list = Db::name('user_trade_depute')->where($map)->select();
        $this->assign('type', $type);
        $this->assign('status', $status);
        $this->assign('list', $list);
        return $this->fetch('entrustment');
    }

    //币币交易
    public function currencyExchange()
    {
        $user_info = session('user');

        $this->assign('user_info', $user_info);
        return $this->fetch('currencyExchange');
    }



}