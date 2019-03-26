<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/26
 * Time: 17:57
 */

namespace app\user\model;

use think\Model;

class UserApplyTradeCash extends Model
{
    public static $cash_method = [
        1 => '银行卡提现',
    ];
}