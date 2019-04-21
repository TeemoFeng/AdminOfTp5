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
use app\user\model\UserCurrencyList;
use app\user\model\UserRunningLog;
use app\user\model\Users;
use think\console\Input;
use think\Db;
use think\db\Where;
use think\Session;

class User extends Common
{

    protected $uid;
    public function initialize()
    {
        parent::initialize();
        if(session('user')){
            $this->assign('user_info', session('user'));
        }
        $system = Db::name('system')->find();
        $this->assign('system', $system);
        $this->uid=session('user.id');
    }

    public function userHome()
    {
        //交易记录

        //上架记录

        //
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
        $this->assign('cash_method', $cash_method);
        $this->assign('bank_list', $bank);
        $this->assign('bonus_set', $bonus_set);
        $this->assign('trade_account', $trade_account);
        $this->assign('user_cash_list', $user_cash_list);
        $this->assign('status', $status);
        $this->assign('user_info', $user_info);
        return $this->fetch('withdrawCash');
    }


    //历史委托订单
    public function historical()
    {
        $type = UserTradeDepute::$trade_type;
        $status = UserTradeDepute::$status;
        $user_id = session('user.id');

        //交易币种现在只支持阿美币
        //交易记录
        $data   = request()->param();
        $where  = $this->makeSearch($data, 2);
        $where['depute_status'] = 2;
        $where['user_id'] = $user_id;
        //根绝用户id获取推荐的人员信息
        $list = Db::name('user_trade_depute')
            ->where($where)
            ->paginate(10,false,['query' => request()->param()]);
        $page = $list->render();

        $this->assign('search', $data);
        $this->assign('type', $type);
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('page', $page);
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

        //交易币种现在只支持阿美币
        //交易记录
        $data   = request()->param();
        $where  = $this->makeSearch($data,1);
        $where['depute_status'] = 1;
        $where['user_id'] = $user_id;
        //根绝用户id获取推荐的人员信息
        $list = Db::name('user_trade_depute')
            ->where($where)
            ->paginate(10,false,['query' => request()->param()]);
        $page = $list->render();
        $this->assign('type', $type);
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('search', $data);
        $this->assign('page', $page);
        return $this->fetch('entrustment');


    }

    //交易大厅
    public function currencyExchange()
    {
        $user_info = session('user');
        //阿美币
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
        //实时成交前30位
        $trade_list = db('user_trade_depute_log')->where(['trade_status' => 2])->limit(30)->order('create_time DESC')->select();
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

        return $this->fetch('currencyExchange');
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


    //撤销托管
    public function cancelDepute()
    {
        $id = input('post.id');
        if(empty($id) || empty($this->uid)){
            return ['code' => 0, 'msg' => '非法请求'];
        }
        $user_info = session('user');

        //用户撤销委托
        $depute_info = UserTradeDepute::where(['id' => $id])->find();

        if(empty($depute_info)){
            return ['code' => 0, 'msg' => '未找到委托记录'];
        }
        if($depute_info['status'] == 3){
            return ['code' => 0, 'msg' => '该订单已完成，不需要撤单'];
        }
        if($depute_info['status'] == 4){
            return ['code' => 0, 'msg' => '该订单已撤单'];
        }
        if($depute_info['lock'] == 1){
            return ['code' => 0, 'msg' => '该订单已匹配成功，暂不可撤销'];
        }

        //买单
        if($depute_info['depute_type'] == UserTradeDepute::DEPUTE1){
            //查询用户交易钱包
            $user_account = UserCurrencyAccount::where(['user_id' => $depute_info['user_id']])->find();
            $jiaoyi_num = bcadd($user_account['transaction_num'], $depute_info['sum'], 2);
            //更新用户交易钱包
            DB::startTrans();
            $res = UserCurrencyAccount::where(['user_id' => $depute_info['user_id']])->update(['transaction_num' => $jiaoyi_num]);
            if($res === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '撤单失败请重试'];
            }

            //更新用户委托状态
            $res2= UserTradeDepute::where(['id' => $id])->update(['status' =>UserTradeDepute::STATUS5, 'depute_status' => 2]);
            if($res2 === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '撤单失败请重试'];
            }
            UserRunningLog::create([
                'user_id'  =>  $depute_info['user_id'],
                'about_id' =>  $depute_info['user_id'],
                'running_type'  => UserRunningLog::TYPE31, //撤销挂单
                'account_type'  => Currency::TRADE,
                'change_num'    => $depute_info['sum'],
                'balance'       => $jiaoyi_num,
                'create_time'   => time(),
                'remark'        => '撤销委托返还交易钱包'
            ]);
            Db::commit();

        }else{
            $amei_infos = CurrencyList::where(['en_name' => 'AMB'])->find();
            //查询用户币种钱包

            $currency_account = Db::name('user_currency_list')->where(['user_id' => $depute_info['user_id'], 'currency_id' =>$amei_infos['id']])->find();

            if($depute_info['status'] == 1){
                $cancel_num = $depute_info['num'];
            }
            if($depute_info['status'] == 2){
                $cancel_num = bcsub($depute_info['num'], $depute_info['have_trade']);
            }

            $currency_num = bcadd($currency_account['num'], $cancel_num, 4);
            DB::startTrans();
            $res = UserCurrencyList::where(['user_id' => $depute_info['user_id'],'currency_id' =>$amei_infos['id']])->update(['num' => $currency_num]);
            if($res === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '撤单失败请重试'];
            }
            //更新用户委托状态
            $res2= UserTradeDepute::where(['id' => $id])->update(['status' =>UserTradeDepute::STATUS5, 'depute_status' => 2]);
            if($res2 === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '撤单失败请重试'];
            }
            Db::commit();
            UserRunningLog::create([
                'user_id'  =>  $depute_info['user_id'],
                'about_id' =>  $depute_info['user_id'],
                'running_type'  => UserRunningLog::TYPE31, //撤销挂单
                'account_type'  => Currency::TRADE,
                'change_num'    => $cancel_num,
                'balance'       => $currency_num,
                'create_time'   => time(),
                'remark'        => '撤销委托返还阿美币'
            ]);


        }
        return ['code' => 1, 'msg' => '撤销成功'];

    }


    //实时获取数据
    public function getCurrencyExchange()
    {
        if(request()->isAjax()){
            $amei_infos = CurrencyList::where(['en_name' => 'AMB'])->find();
            $list_sell = db('user_trade_depute')->where(['depute_type' => 2,'depute_status' => 1])->order('price DESC')->limit(7)->select();
            foreach ($list_sell as $key => $val){

                $amei_account = UserCurrencyList::where(['user_id' => $val['user_id'],'currency_id' => $amei_infos['id']])->value('num');
                if(empty($amei_account)){
                    $amei_account = 0;
                }
                $list_sell[$key]['account'] = $amei_account;
                $list_sell[$key]['num2'] = 7-$key;


            }
            //获取用户托管买币前7位
            $list_buy = db('user_trade_depute')->where(['depute_type' => 1, 'depute_status' => 1])->order('price DESC')->limit(7)->select();
            foreach ($list_buy as $key => $val){
                $amei_account = UserCurrencyList::where(['user_id' => $val['user_id'],'currency_id' => $amei_infos['id']])->value('num');
                if(empty($amei_account)){
                    $amei_account = 0;
                }
                $list_buy[$key]['account'] = $amei_account;
                $list_buy[$key]['num2'] = 7-$key;
            }
            $trade_list = db('user_trade_depute_log')->where(['trade_status' => 2])->limit(30)->order('create_time DESC')->select();
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
            $user_transaction_num =  UserCurrencyAccount::where(['user_id' => $data['user_id']])->value('transaction_num'); //获取交易钱包余额
            if(bccomp($user_transaction_num, $data['trade_num']) < 0){
                return ['code' => 0, 'msg' => '交易钱包余额不足'];
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

                $transaction_num = bcsub($user_transaction_num, $data['trade_num'], 2); //用户交易钱包直接减钱（人民币）
                if($transaction_num < 0){
                    $transaction_num = 0.0000;
                }

                $up_data = [
                    'transaction_num' => $transaction_num,
                ];

                $res2 = UserCurrencyAccount::where(['user_id' => $data['user_id']])->update($up_data);
                if($res2 !== false){
                    Db::commit();

                    //购买手续费记录
                    $tran_num2 = bcsub($user_transaction_num, $data['poundage'], 2); //继续减去手续费剩余
                    UserRunningLog::create([
                        'user_id'  =>  $data['user_id'],
                        'about_id' =>  $data['user_id'],
                        'running_type'  => UserRunningLog::TYPE32,
                        'account_type'  => Currency::TRADE,
                        'change_num'    => -$data['poundage'],
                        'balance'       => $tran_num2,
                        'create_time'   => time(),
                        'remark'        => '委托购买阿美币扣除',
                    ]);

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
            //阿美币
            $amei_infos = CurrencyList::where(['en_name' => 'AMB'])->find();

            //获取用户阿美币
            $ameibi_num = UserCurrencyList::where(['user_id' => $data['user_id'],'currency_id' => $amei_infos['id']])->value('num');
            if(bccomp($ameibi_num, $data['sell_num']) < 0){
                return ['code' => 0, 'msg' => '阿美币余额不足'];
            }
            $user_transaction_num =  UserCurrencyAccount::where(['user_id' => $data['user_id']])->value('transaction_num'); //获取交易钱包余额
            if(bccomp($user_transaction_num, $data['poundage']) < 0){
                return ['code' => 0, 'msg' => '交易钱包余额不足，不能低于手续费用'];
            }
            $save = [
                'user_id'           => $data['user_id'],
                'depute_type'       => $data['depute_type'],
                'status'            => 1,
                'price'             => $data['price'],
                'depute_currency'   => $amei_infos['id'], //卖出币种默认为阿美币
                'trade_currency'    => $amei_infos['id'], //卖出币种默认为阿美币
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

                $ameibi_num = bcsub($ameibi_num, $data['sell_num'], 2); //用户阿美币钱包直接减数量
                $up_data = [
                    'num' => $ameibi_num,
                ];


                $res3 = UserCurrencyList::where(['user_id' => $data['user_id'],'currency_id' => $amei_infos['id']])->update($up_data);
                if($res3 !== false){
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
//                    //卖出手续费记录

                    $tran_num2 = bcsub($user_transaction_num, $data['poundage'], 2); //继续减去手续费剩余
                    if($tran_num2 < 0){
                        $tran_num2 = 0;
                    }
                    $up_data2 = [
                        'transaction_num' => $tran_num2,
                    ];

                    $res2 = UserCurrencyAccount::where(['user_id' => $data['user_id']])->update($up_data2);
                    if($res2 === false){
                        Db::rollback();
                        return ['code' => 0, 'msg' => '购买申请失败请重试'];
                    }
                    UserRunningLog::create([
                        'user_id'  =>  $data['user_id'],
                        'about_id' =>  $data['user_id'],
                        'running_type'  => UserRunningLog::TYPE32,
                        'account_type'  => Currency::TRADE,
                        'change_num'    => -$data['poundage'],
                        'balance'       => $tran_num2,
                        'create_time'   => time(),
                        'remark'        => '委托卖出阿美币手续费扣除',

                    ]);

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


    //订单列表
    public function order()
    {
        //获取用户类型
        $user_info = session('user');
        $trade_type = UserTradeDeputeLog::$trade_type;
        //默认查询用户购买的订单
        $search   = request()->param();
        $where['users_id'] = $user_info['id']; //默认查询卖单
        if(!empty($search['trade_type'])){
            if($search['trade_type'] == 1){
                $where['user_id'] = $user_info['id'];

            }
            if($search['trade_type'] == 2){
                $where['about_id'] = $user_info['id'];

            }
        }
        if(!empty($search['trade_status'])){
            $where['trade_status'] = $search['trade_status'];
        }

        $order_list = Db::name('user_trade_depute_log')->where($where)->order('id DESC')->paginate(10,false,['query' => request()->param()])->each(function ($v, $k){
            $v['trade_status_str'] = UserTradeDeputeLog::$trade_status[$v['trade_status']];
            $v['trade_type_str'] = UserTradeDeputeLog::$trade_type[$v['trade_type']];
            //获取交易对象
            $about_user = Db::name('users')->where(['id' => $v['about_id']])->value('mobile');
            $v['about_user'] = $about_user;
            return $v;
        });
//        $order_list = Db::name('user_trade_depute')->order('id DESC')->paginate(10,false,['query' => request()->param()]);
        $page = $order_list->render();

        $this->assign('trade_type', $trade_type);
        $this->assign('order_list', $order_list);
        $this->assign('page', $page);
        $this->assign('search', $search);
        $this->assign('trade_status', UserTradeDepute::$status);
        $this->assign('user_info', $user_info);
        return $this->fetch('order');
    }

    //用户交易详情
    public function tradeDetail()
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

    //买家确认付款
    public function paySure()
    {
        if(request()->isPost()) {
            $id = input('post.id');
            $order_num = input('post.order_num');
            //跟新订单状态
            Db::name('user_trade_depute_log')->where(['order_num' => $order_num])->update(['trade_status' => 2]); //已付款
            return ['code' => 1, 'msg' => '成功'];
        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }

    }

    //买家取消付款
    public function cancelOrder(){
        if(request()->isPost()) {
            $id = input('post.id');
            $order_num = input('post.order_num');
            $user_id = session('user.id');
            //记录用户当天取消次数
            $cancel_log = Db::name('user_cancel_order_log')->where(['user_id' => $user_id, 'time' => date('Y-m-d', time())])->find();
            if(empty($cancel_log)){
                $data = [
                    'user_id' => $user_id,
                    'num'     => 1,
                    'time'    => date('Y-m-d', time())
                ];
                Db::name('user_cancel_order_log')->insert($data);
            }else{
                Db::table('user_cancel_order_log')
                    ->where('id', $cancel_log['id'])
                    ->setInc('num');
            }

            //跟新订单状态
            Db::startTrans();
            $res = Db::name('user_trade_depute_log')->where(['order_num' => $order_num])->update(['trade_status' => 4]); //取消
            if($res !== false){
                $depute_ids = Db::name('user_trade_depute_log')->where(['order_num' => $order_num])->column('trade_depute_id');
                $res2 = Db::name('user_trade_depute')->where(['id' => ['in', $depute_ids]])->update(['lock' => 0]); //取消锁定

                if($res2 != false){
                    Db::commit();
                    return ['code' => 1, 'msg' => '取消成功'];
                }else{
                    Db::rollback();
                    return ['code' => 0, 'msg' => '取消失败'];
                }
            }
            Db::rollback();
            return ['code' => 0, 'msg' => '取消失败'];

        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }
    }

    //卖家确认收款
    public function sureOrder()
    {
        if(request()->isPost()){
            //获取订单id
            $id = input('post.id');
            if(empty($id)){
                return ['code' => 0, 'msg' => '未获取到订单'];
            }

            //查找订单号
            $order_info = Db::name('user_trade_depute_log')->where(['id' => $id])->find();
            Db::startTrans();
            $res = Db::name('user_trade_depute_log')->where(['order_num' => $order_info['order_num']])->update(['trade_status' => 3]); //确认收款，完成成交
            if($res === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败请重试'];
            }
            $currency_id = CurrencyList::where(['en_name' => 'AMB'])->value('id');

            //修改托管状态
            $depute_ids = Db::name('user_trade_depute_log')->where(['order_num' => $order_info['order_num']])->select();

            foreach($depute_ids as $v){
                //如果是购买人
                if($v['trade_type'] == 1){
                    $res2 = Db::name('user_trade_depute')->where(['id' => $v['trade_depute_id']])->update(['depute_status' => 2, 'depute_time' => time(), 'status' => 3, 'have_trade' => $v['trade_num']]);
                    if($res2 === false){
                        Db::rollback();
                        return ['code' => 0, 'msg' => '操作失败请重试'];
                    }

                    //更新买家货币数量
                    $user_currecny = UserCurrencyList::where(['user_id' => $v['user_id'],'currency_id' => $currency_id])->find();
                    if(empty($user_currecny)) {
                        $user_currency = [
                            'user_id'       => $v['user_id'],
                            'currency_id'   => $currency_id,
                            'num'           => $v['trade_num'],
                        ];
                        $amei_num = $v['trade_num'];
                        UserCurrencyList::create($user_currency);

                    }else{
                        $amei_num = bcadd($user_currecny['num'], $v['trade_num'], 4);
                        //添加用户币种列表
                        $user_currency = [
                            'num'    => $amei_num,
                        ];
                        $res3 = UserCurrencyList::where(['user_id' => $v['user_id'],'currency_id' => $currency_id])->update($user_currency);
                        if($res3 === false){
                            Db::rollback();
                            return ['code' => 0, 'msg' => '操作失败请重试'];
                        }
                    }

                    $res4= UserRunningLog::create([
                        'user_id'  =>  $v['user_id'],
                        'about_id' =>  $v['about_id'],
                        'running_type'  => UserRunningLog::TYPE28, //交易增加
                        'account_type'  => Currency::TRADE,
                        'change_num'    => $v['trade_num'],
                        'balance'       => $amei_num,
                        'create_time'   => time(),
                    ]);

                    if($res4 == 0){
                        Db::rollback();
                        return ['code' => 0, 'msg' => '操作失败请重试'];
                    }

                }
                //如果是卖家
                if($v['trade_type'] == 2){
                    //查看挂单数量
                    $sell_num = Db::name('user_trade_depute')->where(['id' => $v['trade_depute_id']])->find();
                    //部分成交
                    if(bccomp($sell_num['num'], $v['trade_num']) > 0){
                        $res5 = Db::name('user_trade_depute')->where(['id' => $v['trade_depute_id']])->update(['depute_status' => 1,'depute_time' => time(), 'status' => 2, 'have_trade' => $v['trade_num']]); //
                    }else{
                        $res5 = Db::name('user_trade_depute')->where(['id' => $v['trade_depute_id']])->update(['depute_status' => 2, 'depute_time' => time(), 'status' => 3, 'have_trade' => $v['trade_num']]); //
                    }

                    if($res5 === false){
                        Db::rollback();
                        return ['code' => 0, 'msg' => '操作失败请重试'];
                    }

                    //卖家记录
                    $user_currecny = UserCurrencyList::where(['user_id' => $v['user_id'],'currency_id' => $currency_id])->find();
                    $res6 = UserRunningLog::create([
                        'user_id'  =>  $v['user_id'],
                        'about_id' =>  $v['about_id'],
                        'running_type'  => UserRunningLog::TYPE29,
                        'account_type'  => Currency::TRADE,
                        'change_num'    => -$v['trade_num'],
                        'balance'       => $user_currecny['num'],
                        'create_time'   => time(),
                    ]);
                    if($res6 == 0){
                        Db::rollback();
                        return ['code' => 0, 'msg' => '操作失败请重试'];
                    }

                }

            }
            Db::commit();
            return ['code' => 1, 'msg' => '操作成功'];
        }else{
            return ['code' => 0, 'msg' => '操作失败请重试'];
        }



    }




}