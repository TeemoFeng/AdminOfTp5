<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/30
 * Time: 23:05
 */
namespace app\user\model;

use think\Model;

class UserLoginLog extends Model
{
    const status1 = 0;
    const status2 = 1;
    const type1 = 1;
    const type2 = 2;
    public static $login_method = [
        self::type1 => 'WEB',
        self::type1 => 'APP',
    ];

    public static $status = [
        self::status1 => '失败',
        self::status2 => '成功',
    ];
}