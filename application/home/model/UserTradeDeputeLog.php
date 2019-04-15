<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/4/2
 * Time: 16:33
 */
namespace app\home\model;
use think\Model;
use think\Db;
class UserTradeDeputeLog extends Model
{
    const TYPE1 = 1;
    const TYPE2 = 2;

    public static $trade_type = [
        self::TYPE1 => '买入',
        self::TYPE2 => '卖出',
    ];

    const STATUS1 = 1;
    const STATUS2 = 2;
    const STATUS3 = 3;
    const STATUS4 = 4;
    public static $trade_status = [
        self::STATUS1 => '待付款',
        self::STATUS2 => '已付款',
        self::STATUS3 => '已成交',
        self::STATUS4 => '取消',

    ];
}
