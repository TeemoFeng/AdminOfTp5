<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/26
 * Time: 13:54
 */
namespace app\user\model;

use think\Model;

class UserDynamicAmeiBonus extends Model
{
    const STATUS1 = 1;
    const STATUS2 = 2;
    const STATUS3 = 3;

    public static $status = [
        self::STATUS1 => '冻结',
        self::STATUS2 => '已发放',
        self::STATUS3 => '未发放',
    ];
}