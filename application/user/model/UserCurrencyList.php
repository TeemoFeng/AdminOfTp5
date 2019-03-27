<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/27
 * Time: 17:22
 */

namespace app\user\model;

use think\Model;

class UserCurrencyList extends Model
{
    public static $status = [
        0 => '锁仓',
        1 => '解仓'
    ];
}