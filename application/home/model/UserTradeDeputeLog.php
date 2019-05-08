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
    public static $trade_status = [
        self::STATUS1 => '成交',

    ];
}
