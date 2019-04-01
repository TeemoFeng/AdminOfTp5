<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/29
 * Time: 11:46
 */
namespace app\admin\model;
use think\Model;

class UserApplyTradeCash extends Model
{
    //提现申请管理员审核通过
    public function cashPass()
    {
        //用户钱包账户减去交易钱包额度
        $user_transaction_num =  UserCurrencyAccount::where(['user_id' => $user_info['id']])->value('transaction_num'); //获取交易钱包余额
        $transaction_num = bcsub($user_transaction_num, $data['cash_sum'], 2);
        $up_data = [
            'transaction_num' => $transaction_num,
        ];
        $res2 = UserCurrencyAccount::where(['user_id' => $user_info['id']])->update($up_data);
        if($res2 !== false){
            Db::commit();
            //提现扣除记录
            UserRunningLog::create([
                'user_id'  =>  $user_info['id'],
                'about_id' =>  $user_info['id'],
                'running_type'  => UserRunningLog::TYPE5, //提现扣除
                'account_type'  => 4,
                'change_num'    => -$data['cash_sum'],
                'balance'       => $transaction_num,
                'create_time'   => time(),
                'remark'        => $data['remark']
            ]);
        }else{
            Db::rollback();
            return ['code' => 0, 'msg' => '提现失败'];
        }




        UserRunningLog::create([
            'user_id'  =>  $data['user_id'],
            'about_id' =>  -session('aid'),
            'running_type'  => UserRunningLog::TYPE2,
            'account_type'  => $data['currency_id'],
            'change_num'    => -$data['amount'],
            'balance'       => $blance,
            'create_time'   => time(),
            'remark'        => $data['remark']
        ]);
        UserRunningLog::create([
            'user_id'  =>  $data['user_id'],
            'about_id' =>  -session('aid'),
            'running_type'  => UserRunningLog::TYPE2,
            'account_type'  => $data['currency_id'],
            'change_num'    => -$data['amount'],
            'balance'       => $blance,
            'create_time'   => time(),
            'remark'        => $data['remark']
        ]);
    }
}