<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/31
 * Time: 22:49
 */
namespace app\cli\controller;
use app\admin\model\UserCurrencyAccount;
use app\user\model\UserExceotGrant;
use app\user\model\UserRunningLog;

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
            if($account['level_id'] == 1){
                $shouyi = bcmul($account['corpus'],$static_set[0],4);
            }elseif ($account['level_id'] == 2){
                $shouyi = bcmul($account['corpus'],$static_set[1],4);
            }elseif ($account['level_id'] == 3){
                $shouyi = bcmul($account['corpus'],$static_set[2],4);
            }elseif ($account['level_id'] == 4){
                $shouyi = bcmul($account['corpus'],$static_set[3],4);
            }elseif ($account['level_id'] == 5){
                $shouyi = bcmul($account['corpus'],$static_set[4],4);
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


}



