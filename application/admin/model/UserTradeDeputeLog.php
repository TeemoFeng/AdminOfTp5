<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/4/7
 * Time: 10:45
 */
namespace app\admin\model;

use think\Model;

class UserTradeDeputeLog extends Model
{
    const STATUS1 = 1;
    const STATUS2 = 2;
    const STATUS3 = 3;
    const STATUS4 = 4;

    const TYPE1 = 1;
    const TYPE2 = 2;

    public static $status = [
        self::STATUS1 => '待付款',
        self::STATUS2 => '确定付款',
        self::STATUS3 => '已完成',
        self::STATUS4 => '取消',
    ];

    public static $status2 = [
        self::STATUS1 => '成功',
        self::STATUS2 => '失败',
    ];

    public static $trade_type = [
        self::TYPE1 => '<span style="color: red">买入</span>',
        self::TYPE2 => '<span style="color: green">卖出</span>',
    ];
}