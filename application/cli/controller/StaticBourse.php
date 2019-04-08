<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/31
 * Time: 22:49
 */
namespace app\cli\controller;
use app\admin\controller\Users;
use app\admin\model\CurrencyList;
use app\admin\model\UserCurrencyAccount;
use app\admin\model\UserReferee;
use app\home\model\UserTradeDepute;
use app\user\model\UserCurrencyList;
use app\user\model\UserDynamicAmeiBonus;
use app\user\model\UserExceotGrant;
use app\user\model\UserRunningLog;
use think\Db;
use think\db\Where;

class StaticBourse {

    //用户每日静态收益发放
    public function userStaticBourse()
    {
        set_time_limit(0);
        $bonus = db('bonus_set')->select();
        $static_set = [];
        foreach ($bonus as $key => $val){
            $static_set[$key] = bcdiv($val['static_gains'],100,4);
        }
        $userCurrencyAccount = new UserCurrencyAccount();
        $cursor = $userCurrencyAccount->where('status',1)->cursor();
        foreach($cursor as $account){
            $user_shate = bcadd($account['corpus'], $account['cash_input_num'],4);
            if($account['level_id'] == 1){
                $shouyi = bcmul($user_shate,$static_set[0],4);
            }elseif ($account['level_id'] == 2){
                $shouyi = bcmul($user_shate,$static_set[1],4);
            }elseif ($account['level_id'] == 3){
                $shouyi = bcmul($user_shate,$static_set[2],4);
            }elseif ($account['level_id'] == 4){
                $shouyi = bcmul($user_shate,$static_set[3],4);
            }elseif ($account['level_id'] == 5){
                $shouyi = bcmul($user_shate,$static_set[4],4);
            }

            $cash_currency_num = $account['cash_currency_num'] + $shouyi;
            $res = UserCurrencyAccount::where(['id' => $account['id']])->update(['cash_currency_num' => $cash_currency_num]);
            if($res !== false){
                //记录流水日志
                UserRunningLog::create([
                    'user_id'  =>  $account['user_id'],
                    'about_id' =>  $account['user_id'],
                    'running_type'  => UserRunningLog::TYPE18,
                    'account_type'  => 1,
                    'change_num'    => $shouyi,
                    'balance'       => $cash_currency_num,
                    'create_time'   => time()
                ]);
            }else{
                UserExceotGrant::create([
                    'user_id'       =>  $account['user_id'],
                    'change_num'    =>  $shouyi,
                    'create_time'   =>  time()
                ]);
            }

        }

    }

    //用户动态奖发放
    public function dynamic()
    {
        set_time_limit(0);
        //获取用户拿取设置
        $user_level = Db::name('user_level')->column('bonus_level','level_id');
        $promotion_award = Db::name('user_level')->column('promotion_award','level_id');

        $rule = [
            '1' => [
                '1' => $promotion_award[0],
                '2' => $promotion_award[0],
                '3' => $promotion_award[0],
                '4' => $promotion_award[0],
                '5' => $promotion_award[0],
            ],
            '2' => [
                '1' => $promotion_award[0],
                '2' => $promotion_award[1],
                '3' => $promotion_award[1],
                '4' => $promotion_award[1],
                '5' => $promotion_award[1],
            ],
            '3' => [
                '1' => $promotion_award[0],
                '2' => $promotion_award[1],
                '3' => $promotion_award[2],
                '4' => $promotion_award[2],
                '5' => $promotion_award[2],
            ],
            '4' => [
                '1' => $promotion_award[0],
                '2' => $promotion_award[1],
                '3' => $promotion_award[2],
                '4' => $promotion_award[3],
                '5' => $promotion_award[3],
            ],
            '5' => [
                '1' => $promotion_award[0],
                '2' => $promotion_award[1],
                '3' => $promotion_award[2],
                '4' => $promotion_award[3],
                '5' => $promotion_award[4],
            ],
        ];

        //阿美币实时价格
        $amei_price = db('user_trade_depute')->order('create_time DESC')->find();
        $price = $amei_price['price'];

        $user_count = db('user_referee')->where(['enabled' => 1])->count();
        $page_size = 500;
        $page = ceil($user_count/$page_size);

        for($i = 0, $i < $page; $i++;){

            $user_list = db('user_referee')
                ->alias('a')
                ->join(config('database.prefix').'users b','a.user_id = b.id','left')
                ->where(['a.enabled' => 1])
                ->limit($i*$page_size,500)
                ->select();
            foreach ($user_list as $k => $v){
                if($v['level'] > 0){
                    //获取用户的汇率
                    $rate = UserCurrencyAccount::where(['user_id' => $v['user_id']])->value('rate');
                    $this->getBouseCount($v['user_id'], $user_level[$v['level']], $rule, $rate, $price); //用户id,用户拿取层数

                }

            }

        }
    }

    //获取用户推荐人数
    public function getBouseCount($uid, $level, &$rule, $rate, $price)
    {

        $deep = db('user_referee')->where(['user_id' => $uid])->value('deep'); //当前用户层数
        //获取阿美币的id
        $currency_id = CurrencyList::where(['en_name' => 'AMB'])->value('id');
        $where = new Where();
        $where['enabled'] = 1;
        $where['deep'] = ['<=', $deep+$level]; //查询层数
        $where[] = ['exp',Db::raw("FIND_IN_SET($uid,user_son_str)")];
        $son_list = db('user_referee')->where($where)->select();
        if(!empty($son_list)) {
            $jiangli_all = 0;
            foreach ($son_list as $k => $v){
                $son_level = Users::where(['id' => $v['user_id'], 'enabled' => 1])->value('level');
                $jiangli_one = $rule[$level][$son_level];
//                $jiangli_all += $rule[$level][$son_level];

                //加入动态转换表
                $this->sendBouse($uid,$v['user_id'], $jiangli_one, $rate, $price, $currency_id);
            }


        }else{
            return false;
        }


    }

    //添加到动态转换表
    public function sendBouse($uid, $sonid, $jiangli, $rate, $price, $currency_id)
    {
        $user_name = Users::where(['id' => $uid])->value('usernum');
        $from_user = Users::where(['id' => $sonid])->value('usernum');
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $today_time = mktime(23,59,59,$month,$day,$year);//当天结束时间戳
        $end_time = $today_time+(10*86400);
        //奖励数量
        $jiangli_num = bcmul($jiangli,0.5, 4);
        //应发阿美币数量
        $val = bcmul($jiangli_num, $rate, 4);
        $num = bcdiv($val, $price, 4);
        $data = [
            'user_id' => $uid,
            'dynamic_bonus' => $jiangli_num,
            'rate' => $rate,
            'ameibi_price' => $price,
            'ameibi_num' => $num,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:is',time()),
            'grant_time' => date('Y-m-d', $end_time),
            'remark'     => '用户【'.$user_name. '】动态转换阿美币因【' . $from_user .'】'

        ];

        //添加ambei动态表
        UserDynamicAmeiBonus::create($data);


        $freeze_num = UserCurrencyList::where(['user_id' => $uid,'currency_id' => $currency_id])->find();
        if(empty($freeze_num)) {
            $user_currency = [
                'user_id'       => $uid,
                'currency_id'   => $currency_id,
                'freeze_num'    => $num,
            ];
            UserCurrencyList::create($user_currency);

        }else{
            $freeze_num = bcadd($freeze_num['freeze_num'], $num, 4);
            //添加用户币种列表
            $user_currency = [
                'freeze_num'    => $freeze_num,
            ];
            UserCurrencyList::where(['user_id' => $uid,'currency_id' => $currency_id])->update($user_currency);

        }


        $user_account = UserCurrencyAccount::where(['user_id' => $uid])->value('cash_currency_num');
        $user_account = bcadd($user_account, $jiangli,4);
        //添加动态奖流水日志
        UserRunningLog::create([
            'user_id'  =>  $uid,
            'about_id' =>  $sonid,
            'running_type'  => UserRunningLog::TYPE20,
            'account_type'  => 1,
            'change_num'    => $jiangli,
            'balance'       =>  $user_account,
            'create_time'   => time(),
            'remark'        => '动态奖奖励'.$jiangli,
        ]);

        //添加阿美币转换日志
        $chang_num = bcsub($user_account, $jiangli_num, 4); //转换阿美币
        UserRunningLog::create([
            'user_id'  =>  $uid,
            'about_id' =>  $sonid,
            'running_type'  => UserRunningLog::TYPE23,
            'account_type'  => 1,
            'change_num'    => $jiangli_num,
            'balance'       => $chang_num,
            'create_time'   => time(),
            'remark'        => '动态奖装换阿美币因【'.$from_user.'】',
        ]);

    }

    //发送动态奖
    public function grantToUserCurrencyList()
    {

    }


    //自动撮合交易
    public function autoTrade()
    {
        //1.查询用户 买币列表
        $where = new Where();
        $where['depute_status'] = 1; //正在委托
        $where['depute_type'] = 1; //买入
        $where['status'] = ['in', '1,2']; //未完成成交的
        $buy_list = UserTradeDepute::where($where)->select();

        //2.查询用户 卖币列表
        $where = new Where();
        $where['depute_status'] = 1; //正在委托
        $where['depute_type'] = 2; //买入
        $where['status'] = ['in', '1,2']; //未完成成交的
        $sell_list = UserTradeDepute::where($where)->select();

        //3.碰撞产生交易
        foreach ($buy_list as $k => $v){
            foreach ($sell_list as $kk => $vv){
                //如果买入价格高于卖价且卖家数量大于等于买家要购买的数量
                $sell_count = bcsub($vv['num'], $vv['have_trade'], 4);
                if(bccomp($v['price'], $vv['price']) >=0 && bccomp($sell_count, $v['num'] >=0)){
                    //创建交易订单
                   $order_num =  createOrderNum();
                   $all_num = bcmul($v['num'], $v['price'],4);
                   $poundage = bcmul($all_num, 0.0001,4);
                   $sum = bcsub($all_num, $poundage, 4);
                   $add_buy = [
                       'user_id'    => $v['user_id'],
                       'about_id'   => $vv['user_id'],
                       'order_num'  => $order_num,
                       'trade_num'  => $v['num'], //买家购买量
                       'trade_currency' => $v['depute_currency'],
                       'price'          => $v['price'],
                       'poundage'        => 0,
                       'sum'             => $sum,
                       'trade_depute_id' => $v['id'],
                       'trade_type'      => 1, //买入
                       'trade_status'    => 1, //未完成
                       'create_time'     => time()

                   ];
                    $order_num2 =  createOrderNum();
                    $add_sell = [
                        'user_id'    => $vv['user_id'],
                        'about_id'   => $v['user_id'],
                        'order_num'  => $order_num2,
                        'trade_num'  => $v['num'], //买家购买量
                        'trade_currency'  => $v['depute_currency'],
                        'price'           => $v['price'],
                        'poundage'        => $poundage,
                        'sum'             => $sum,
                        'trade_depute_id' => $vv['id'],
                        'trade_type'      => 2, //卖出
                        'trade_status'    => 1, //未完成
                        'create_time'     => time()

                    ];

                    Db::name('user_trade_depute_log')->insert($add_buy);
                    Db::name('user_trade_depute_log')->insert($add_sell);


                }
            }
        }


    }


}



