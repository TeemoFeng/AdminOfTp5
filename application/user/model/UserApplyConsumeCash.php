<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/26
 * Time: 17:54
 */
namespace app\user\model;

use think\Model;

class UserApplyConsumeCash extends Model
{
    //用户提现方式
    public static $cash_method = [
        1 => '银行卡提现',
    ];
}