<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/25
 * Time: 14:19
 */
namespace app\user\model;

use think\Model;

class UserApplyShateCash extends Model
{
    const STATUS1 = 1;
    const STATUS2 = 2;
    const STATUS3 = 3;
    //用户提现方式
    public static $cash_method = [
        1 => '银行卡提现',
        2 => '支付宝提现',
    ];

    public static $status = [
        self::STATUS1 => '未审核',
        self::STATUS2 => '已审核',
        self::STATUS3 => '未通过',
    ];
}