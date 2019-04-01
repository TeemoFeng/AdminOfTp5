<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/30
 * Time: 23:05
 */
namespace app\user\model;

use think\Model;

class UserBindLog extends Model
{
    const type1 = 1;
    const type2 = 2;
    const type3 = 3;
    const type4 = 4;
    const type5 = 5;
    const type6 = 6;
    const type7 = 7;

    public static $type_list = [
        self::type1 => '实名认证',
        self::type2 => '绑定手机',
        self::type3 => '绑定邮箱',
        self::type4 => '修改手机绑定',
        self::type5 => '修改邮箱绑定',
        self::type6 => '修改登录密码',
        self::type7 => '修改资金密码',
    ];
}