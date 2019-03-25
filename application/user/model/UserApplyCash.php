<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/25
 * Time: 14:19
 */
namespace app\user\model;

use think\Model;

class UserApplyCash extends Model
{
    //用户提现方式
    public static $cash_method = [
        1 => '银行卡提现',
    ];
}