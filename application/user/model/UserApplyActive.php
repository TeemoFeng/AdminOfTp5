<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/14 0014
 * Time: 18:49
 */
namespace app\user\model;

use think\Model;

class UserApplyActive extends Model
{
    const STATUS1 = 1;
    const STATUS2 = 2;
    const STATUS3 = 3;

    public static $status = [
        self::STATUS1 => '未审核',
        self::STATUS2 => '已审核',
        self::STATUS3 => '未通过',
    ];
}