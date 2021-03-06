<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/31
 * Time: 22:49
 */
namespace app\cli\controller;
use app\admin\controller\Users;
use app\admin\model\Currency;
use app\admin\model\CurrencyList;
use app\admin\model\UserCurrencyAccount;
use app\admin\model\UserReferee;
use app\home\model\UserTradeDepute;
use app\user\model\UserCurrencyList;
use app\user\model\UserDynamicAmeiBonus;
use app\user\model\UserExceotGrant;
use app\user\model\UserRunningLog;
use think\Cache;
use think\Controller;
use think\Db;
use think\db\Where;
use think\Log;

class Bourse extends Controller {

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
            if($account['user_id'] == 1){
                continue;
            }
            //查询用户级别
            $user_info = Db::name('users')->where(['id' => $account['user_id']])->field('level,enabled')->find();
            $level_id = $user_info['level'];
            //如果用户没有级别直接略过
            if($level_id == 0 || $user_info['enabled'] == 0){
                continue;
            }

            $user_shate = bcadd($account['corpus'], $account['cash_input_num'],4);

            if($level_id == 1){
                $shouyi = bcmul($user_shate,$static_set[0],4);
            }elseif ($level_id == 2){
                $shouyi = bcmul($user_shate,$static_set[1],4);
            }elseif ($level_id == 3){
                $shouyi = bcmul($user_shate,$static_set[2],4);
            }elseif ($level_id == 4){
                $shouyi = bcmul($user_shate,$static_set[3],4);
            }elseif ($level_id == 5){
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
        $stime=microtime(true); //获取程序开始执行的时间


        set_time_limit(0);
        //获取用户拿取设置
        $user_level = Db::name('user_level')->column('bonus_level','level_id');
        $promotion_award = Db::name('user_level')->column('promotion_award','level_id');
        $rule = [
            '1' => [
                '1' => $promotion_award[1],
                '2' => $promotion_award[1],
                '3' => $promotion_award[1],
                '4' => $promotion_award[1],
                '5' => $promotion_award[1],
            ],
            '2' => [
                '1' => $promotion_award[1],
                '2' => $promotion_award[2],
                '3' => $promotion_award[2],
                '4' => $promotion_award[2],
                '5' => $promotion_award[2],
            ],
            '3' => [
                '1' => $promotion_award[1],
                '2' => $promotion_award[2],
                '3' => $promotion_award[3],
                '4' => $promotion_award[3],
                '5' => $promotion_award[3],
            ],
            '4' => [
                '1' => $promotion_award[1],
                '2' => $promotion_award[2],
                '3' => $promotion_award[3],
                '4' => $promotion_award[4],
                '5' => $promotion_award[4],
            ],
            '5' => [
                '1' => $promotion_award[1],
                '2' => $promotion_award[2],
                '3' => $promotion_award[3],
                '4' => $promotion_award[4],
                '5' => $promotion_award[5],
            ],
        ];

        //阿美币实时价格
        $amei_price = db('user_trade_depute_log')->where(['trade_status' => 3])->order('create_time DESC')->value('price');
        if(empty($amei_price)){
            $amei_price = Db::name('currency_list')->where(['en_name' => 'AMB'])->value('price');
        }
        $price = $amei_price;
        $user_count = db('user_node')->where(['enabled' => 1])->count();
        $page_size = 200;
        $page = ceil($user_count/$page_size);
        //获取阿美币的id
        $currency_id = CurrencyList::where(['en_name' => 'AMB'])->value('id');
        for($i = 0; $i < $page; $i++){
            $user_list = db('user_node')
                ->alias('a')
                ->join(config('database.prefix').'users b','a.user_id = b.id','left')
                ->field('a.*,b.level')
                ->where(['a.enabled' => 1])
                ->limit($i*$page_size,$page_size)
                ->select();

            foreach ($user_list as $k => $v){
                if($v['level'] > 0){
                    //获取用户的汇率
                    $rate = UserCurrencyAccount::where(['user_id' => $v['user_id']])->value('rate');
                    $this->getBouseCount($v['user_id'], $v['level'], $user_level[$v['level']], $rule, $rate, $price, $currency_id); //用户id,用户拿取层数

                }

            }

        }

        /**
         *你要运行的程序
         **/
        $etime=microtime(true);//获取程序执行结束的时间
        $total=$etime-$stime;   //计算差值
        echo "<br />[页面执行时间：{$total} ]秒";
    }

    //获取用户推荐人数
    public function getBouseCount($uid, $user_level, $level, &$rule, $rate, $price, $currency_id)
    {

        $deep = db('user_node')->where(['user_id' => $uid])->value('deep'); //当前用户层数

        $where = new Where();
        $where['enabled'] = 1;
        $where['deep'] = ['<=', $deep+$level]; //查询层数
        $where[] = ['exp',Db::raw("FIND_IN_SET($uid,user_son_str)")];
        $son_list = db('user_node')->where($where)->select();
        if(!empty($son_list)) {
            $jiangli_all = 0;
            foreach ($son_list as $k => $v){
                $son_level = Db::name('users')->where(['id' => $v['user_id'], 'enabled' => 1])->value('level');
                if (empty($son_level)) {
                    continue;
                }
                $jiangli_one = $rule[$user_level][$son_level];
//                $jiangli_all += $rule[$level][$son_level];

                //加入动态转换表
                $this->sendBouse($uid,$v['user_id'], $jiangli_one, $rate, $price, $currency_id);
                usleep(50000);
            }


        }else{
            return false;
        }


    }

    //添加到动态转换表
    public function sendBouse($uid, $sonid, $jiangli, $rate, $price, $currency_id)
    {
        $user_name = Db::name('users')->where(['id' => $uid])->value('usernum');
        $from_user = Db::name('users')->where(['id' => $sonid])->value('usernum');
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
            'create_time' => date('Y-m-d H:i:s',time()),
            'grant_time' => date('Y-m-d', $end_time),
            'remark'     => '用户【'.$user_name. '】动态转换阿美币因【' . $from_user .'】'

        ];

        //添加ambei动态表
        UserDynamicAmeiBonus::create($data);

        //添加用户货币列表冻结数量
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
        //更新用户沙特链数量
        UserCurrencyAccount::where(['user_id' => $uid])->update(['cash_currency_num' => $user_account]);
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
        //更新用户沙特链数量
        UserCurrencyAccount::where(['user_id' => $uid])->update(['cash_currency_num' => $chang_num]);

        UserRunningLog::create([
            'user_id'  =>  $uid,
            'about_id' =>  $sonid,
            'running_type'  => UserRunningLog::TYPE23,
            'account_type'  => 1,
            'change_num'    => -$jiangli_num,
            'balance'       => $chang_num,
            'create_time'   => time(),
            'remark'        => '动态奖转换阿美币因【'.$from_user.'】',
        ]);

    }

    //发放动态奖
    public function grantToUserCurrencyList()
    {
        set_time_limit(0);
        //获取阿美币的id
        $currency_id = CurrencyList::where(['en_name' => 'AMB'])->value('id');
        $today = date('Y-m-d', time());
        $user_count = db('user_dynamic_amei_bonus')->where(['grant_time' => $today,'status' => 1])->count();
        $page_size = 500;
        $page = ceil($user_count/$page_size);

        for($i = 0, $i < $page; $i++;){

            $grant_list = db('user_dynamic_amei_bonus')
                ->where(['grant_time' => $today,'status' => 1])
                ->limit($i*$page_size,500)
                ->select();
            foreach ($grant_list as $k => $v){
                $user_amei_account =  UserCurrencyList::where(['user_id' => $v['user_id'],'currency_id' => $currency_id])->find();
                $amei_num = bcsub($user_amei_account['freeze_num'], $v['ameibi_num'],4); //冻结数减去今天发放数量
                $amei_all = bcadd($user_amei_account['num'], $v['ameibi_num'], 4); //用户可用数量
                //更新用户币种列表
                UserCurrencyList::where(['id' => $user_amei_account['id']])->update(['num' => $amei_all, 'freeze_num' => $amei_num]);
                //更新动态表状态
                UserDynamicAmeiBonus::where(['id' => $v['id']])->update(['status' => 2]); //更新为已发放

            }

        }

    }

    //每日公司拨比
    public function companyDayRunning()
    {

        $beginYesterday = mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $endYesterday = mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
        //查询昨天报单总额
        $where = new Where();
        $where['running_type'] = 22;
        $where['create_time'] = array('between time', array($beginYesterday, $endYesterday));
        $all_sum = Db::name('user_running_log')->where($where)->sum('change_num'); //总收入
        //计算昨天发送静态奖励
        $where2['running_type'] = 18;
        $where2['create_time'] = array('between time', array($beginYesterday, $endYesterday));
        $all_jing = Db::name('user_running_log')->where($where)->sum('change_num'); //总静态收益
        //计算昨天发送动态奖励
        $where3['running_type'] = 20;
        $where3['create_time'] = array('between time', array($beginYesterday, $endYesterday));
        $all_dong = Db::name('user_running_log')->where($where)->sum('change_num'); //总动态收益
        $all_dong = bcmul($all_dong,2,4);
        //计算昨天直推奖励
        $where3['running_type'] = 19;
        $where3['create_time'] = array('between time', array($beginYesterday, $endYesterday));
        $all_zhi = Db::name('user_running_log')->where($where)->sum('change_num'); //总直推收益

        //计算昨天报单奖励
        $where3['running_type'] = 21;
        $where3['create_time'] = array('between time', array($beginYesterday, $endYesterday));
        $all_bao = Db::name('user_running_log')->where($where)->sum('change_num'); //总报单收益

        $expenses = bcadd($all_jing, $all_dong, 4);
        $expenses = bcadd($expenses, $all_zhi, 4);
        $expenses = bcadd($expenses, $all_bao, 4);

        $data = [
            'income'    => $all_sum,
            'expenses'  => $expenses,
            'time'      => date('Y-m-d',$endYesterday)
        ];
        Db::name('company_day_running')->insert($data);

    }


    //自动撤销昨天的挂单
    public function cancelDepute()
    {
        set_time_limit(0);
        $depute_count = Db::name('user_trade_depute')->where(['depute_status' => 1])->count();
        //手续费比率
        $bonus_set = db('bonus_ext_set')->where('id',1)->find(); //提现设置
        $trans_ratio = $bonus_set['trans_ratio'];
        $trans_ratio = bcdiv($trans_ratio, 100, 4);

        $page_size = 100;
        $page = ceil($depute_count/$page_size);
        //获取阿美币的id
        $currency_id = CurrencyList::where(['en_name' => 'AMB'])->value('id');
        for($i = 0; $i < $page; $i++){
            $depute_list = db('user_trade_depute')
                ->where(['depute_status' => 1])
                ->limit($i*$page_size,$page_size)
                ->select();

            foreach ($depute_list as $k => $v){
                //如果是买单
                if($v['depute_type'] == UserTradeDepute::DEPUTE1){
                    //查询用户交易钱包
                    $user_account = UserCurrencyAccount::where(['user_id' => $v['user_id']])->find();
                    //剩余
                    $sheng = bcsub($v['num'], $v['have_trade'], 4);
                    if($sheng == 0){
                        //更新该委托状态
                        Db::name('user_trade_depute')->where(['id' => $v['id']])->update(['status' => 3, 'depute_status' => 2]);
                        continue;
                    }

                    $shengyu = bcmul($sheng, $v['price'], 4); //剩余
                    $shengyu_pou = bcmul($shengyu, $trans_ratio, 4);    //剩余应退手续费
                    $shengyu_all = bcadd($shengyu, $shengyu_pou ,4);    //剩余总共需要退款
                    $jiaoyi_num = bcadd($user_account['transaction_num'], $shengyu_all, 2);
                    //更新用户交易钱包
                    Db::startTrans();
                    $res = UserCurrencyAccount::where(['user_id' => $v['user_id']])->update(['transaction_num' => $jiaoyi_num]);
                    if($res === false){
                        Db::rollback();
                        continue;
                    }

                    //更新用户委托状态
                    $res2= UserTradeDepute::where(['id' => $v['id']])->update(['status' =>UserTradeDepute::STATUS5, 'depute_status' => 2]);
                    if($res2 === false){
                        Db::rollback();
                        continue;
                    }
                    $res3 = UserRunningLog::create([
                        'user_id'  =>  $v['user_id'],
                        'about_id' =>  $v['user_id'],
                        'running_type'  => UserRunningLog::TYPE31, //撤销挂单
                        'account_type'  => Currency::TRADE,
                        'change_num'    => $shengyu_all,
                        'balance'       => $jiaoyi_num,
                        'create_time'   => time(),
                        'remark'        => '撤销委托返还交易钱包'
                    ]);
                    if($res3 === false){
                        Db::rollback();
                        continue;
                    }

                    Db::commit();

                }else{
                    //卖单

                    $currency_account = Db::name('user_currency_list')->where(['user_id' => $v['user_id'], 'currency_id' => $currency_id])->find();

                    if($v['status'] == 1){
                        $cancel_num = $v['num'];
                    }
                    if($v['status'] == 2){
                        $cancel_num = bcsub($v['num'], $v['have_trade'], 4);
                    }

                    $currency_num = bcadd($currency_account['num'], $cancel_num, 4);

                    Db::startTrans();
                    $res = UserCurrencyList::where(['user_id' => $v['user_id'],'currency_id' => $currency_id])->update(['num' => $currency_num]);
                    if($res === false){
                        Db::rollback();
                        continue;
                    }
                    //更新用户委托状态
                    $res2= UserTradeDepute::where(['id' => $v['id']])->update(['status' =>UserTradeDepute::STATUS5, 'depute_status' => 2]);
                    if($res2 === false){
                        Db::rollback();
                        continue;
                    }

                    //记录阿美比撤单返回
                    $run_log = [
                        'user_id'   => $v['user_id'],
                        'about_id'  => $v['user_id'],
                        'running_type' => UserRunningLog::TYPE31,
                        'currency_from' => $currency_id,
                        'currency_to'   => $currency_id,
                        'change_num'    => $cancel_num,
                        'create_time'   => time(),
                        'remark'        => '阿美币撤销挂单返回'

                    ];
                    $res7 = Db::name('currency_running_log')->insert($run_log);
                    if($res7 === 0){
                        Db::rollback();
                        continue;
                    }

                    Db::commit();
                }

            }

        }
    }


    public function createOrderNum()
    {
       $num1 =  time();
       $num2 =  rand(01,99);
       return 'JF'.$num1.$num2;
    }

    //导入用户
    public function exportUser()
    {
        set_time_limit(0);
//        $count = Db::name('old_user')->count();
        $page = 500;
//        $size = ceil($count/$page);
        // 0-5  5-10 10-15 15-20 20-25  25-30
//            $list = Db::name('old_user')->limit(23000, 1000)->select();
            $list = Db::name('old_user')->where(['sys_type' => 1])->select();
            foreach ($list as $k => $v) {
                if($v['user_name'] == 'admin') {
                    continue;
                }
                if ($v['id'] == 223 || $v['id'] == 227) {
                    continue;
                }
                $add['usernum'] = $v['user_name'] ? $v['user_name'] : '';
                $add['username'] = $v['true_name'] ? $v['true_name'] : '';
                $add['nickname'] = $v['true_name'] ? $v['true_name'] : '';
                $add['is_report'] = $v['with_re']; //是否报备
                $add['email'] = $v['email'] ? $v['email'] : ''; //是否报备
                $add['mobile'] = $v['mobile'] ? $v['mobile'] : ''; //手机号
                $add['npid'] = $v['parentid']; //节点关系
                $add['pid'] = $v['refereeid']; //推荐关系
                $add['sys_type'] = $v['sys_type']; //系统类型
                $add['level'] = $v['user_level']; //系统类型
                $add['have_tree'] = 0;
                if ($v['referee_number'] > 0) {
                    $add['have_tree'] = 1;
                }
                if ($v['my_server_center'] > 0) {
                    $my_baodan_center = Db::name('old_user')->where(['id' => $v['my_server_center']])->value('user_name');
                    $add['baodan_user'] = $my_baodan_center; //报单人
                }

                $add['reg_time'] = $v['reg_time'] ? $v['reg_time'] : 0; //注册时间
                $add['active_time'] = $v['activation_time'] ? $v['activation_time'] : 0; //激活时间
                $add['baodan_center'] = $v['is_server_center']; //是否报单中心
                $add['avatar'] = $v['user_face']; //用户头像
                $add['status'] = 0;
                if ($v['status'] > 0) {
                    $add['status'] = 1; //用户状态
                }
                $add['enabled'] = $v['valid_status']; //是否有效
                $add['password'] = 'e10adc3949ba59abbe56e057f20f883e'; //密码
                $add['pwd'] = lock_url($add['password']);
                $add['safeword'] = '123456';
                $add['last_login'] = $v['last_login_time'] ? $v['last_login_time'] : 0;
                if (!empty($v['refereeid'])) {
                   $referee = Db::name('old_user')->where(['id' => $v['refereeid']])->value('user_name');
                    $add['referee'] = $referee ? $referee : '';
                } else {
                    $add['referee'] = '';
                }

                if (!empty($v['parentid'])) {
                    $contact_person = Db::name('old_user')->where(['id' => $v['parentid']])->value('user_name');
                    $add['contact_person'] = $contact_person ? $contact_person : '';
                } else {
                    $add['contact_person'] = '';
                }


                $add['old_user_id'] = $v['id'];
                $id = Db::name('users')->insertGetId($add); //添加用户

                $account['user_id'] = $id;
                $account['cash_currency_num'] = $v['balance_bonus']; //现金币数量
                $account['cash_input_num'] = $v['balance_repeat']; //复投数量
                $account['corpus'] = $v['balance_heart']; //本金账户余额
                $account['activation_num'] = $v['balance_cash']; //激活钱包余额
                $account['consume_num'] = $v['balance_shopping']; //消费钱包余额
                $account['transaction_num'] = $v['balance_integral']; //交易账户余额
                $account['rate'] = $v['activ_lv'];
                $account['status'] = $v['valid_status']; //
                $account['level_id'] = $v['user_level'];

                Db::name('user_currency_account')->insert($account); //添加账户信息
                usleep(5000);
            }


    }

    //处理接点关系
    public function handleRelation()
    {

        set_time_limit(0);
//        $list = Db::name('users')->where('id', ['>=', 21000], ['<', 24000], 'and')->field('id,old_user_id,enabled')->select();
        $list = Db::name('users')->field('id,old_user_id,enabled')->select();
        foreach ($list as $k => $v) {
            //查询原先关系
            $nodeRela =  Db::name('old_user_parent')->where(['userid' => $v['old_user_id']])->order('distance DESC')->column('parentid');
            if (empty($nodeRela)) {
               continue;
            }

            //查询原先id现在对应的用户id
            $map = new Where();
            $map['old_user_id'] = ['in', $nodeRela];
            $newRela =  Db::name('users')->where($map)->column('id');
            $add['user_id'] = $v['id'];
            $add['user_son_str'] = implode(",", $newRela) . ',';
            $add['enabled'] = $v['enabled'];
            $add['deep'] = count($newRela);
            Db::name('user_node')->insert($add);

        }

    }


    //处理推荐关系
    public function handleRelationReferee()
    {
        set_time_limit(0);
        //        $list = Db::name('users')->where('id', ['>=', 21000], ['<', 24000], 'and')->field('id,old_user_id,enabled')->select();
        $list = Db::name('users')->field('id,old_user_id,enabled')->select();
        foreach ($list as $k => $v) {
            //查询原先关系
            $nodeRela =  Db::name('old_user_referee')->where(['userid' => $v['old_user_id']])->order('distance DESC')->column('refereeid');
            if (empty($nodeRela)) {
                continue;
            }

            //查询原先id现在对应的用户id
            $map = new Where();
            $map['old_user_id'] = ['in', $nodeRela];
            $newRela =  Db::name('users')->where($map)->column('id');
            $add['user_id'] = $v['id'];
            $add['user_son_str'] = implode(",", $newRela) . ',';
            $add['enabled'] = $v['enabled'];
            $add['deep'] = count($newRela);
            Db::name('user_referee')->insert($add);

        }

    }


    //处理用户表中推荐关系和接点关系
    public function handelNodeAndReferee()
    {
        set_time_limit(0);
//        $list = Db::name('users')->where('id', ['>=', 21000], ['<', 24000], 'and')->field('id,old_user_id,pid,npid')->select();
        $list = Db::name('users')->field('id,old_user_id,pid,npid')->select();
        foreach ($list as $k => $v) {
            //更新pid
            if ($v['pid'] > 0) {
                $now_pid_id =  Db::name('users')->where(['old_user_id' => $v['pid']])->value('id');
                if ($now_pid_id) {
                    Db::name('users')->where(['id' => $v['id']])->update(['pid' => $now_pid_id]);
                }

            }

            //更新npid
            if ($v['npid'] > 0) {
                $now_pid_id =  Db::name('users')->where(['old_user_id' => $v['npid']])->value('id');
                if($now_pid_id){
                    Db::name('users')->where(['id' => $v['id']])->update(['npid' => $now_pid_id]);
                }

            }




        }

    }




//    public function export()
//    {
//        $goods = cache("Kdexcel");
//        $sql_txt = fopen("D:/sql.txt", "w");
//
//        foreach ($goods as $k => $v) {
//            if ($k == 0) {
//                continue;
//            }
//            $cost = number_format($v[1], 2);;
//            $sql = "UPDATE mkl_goods SET `cost_coin`=45,`cost`=" . $cost ." WHERE id=" .$v[0] . ";\n";
//            fwrite($sql_txt, $sql);
//
//        }
//
//        fclose($sql_txt);
//    }


}



