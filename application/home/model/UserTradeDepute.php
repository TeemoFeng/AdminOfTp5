<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/31
 * Time: 15:46
 */
namespace app\home\model;
use think\Model;
use think\Db;
class UserTradeDepute extends Model
{
    const TYPE1 = 1;
    const TYPE2 = 2;

    public static $trade_type = [
        self::TYPE1 => '买单',
        self::TYPE2 => '卖单',
    ];

    const DEPUTE1 = 1;
    const DEPUTE2 = 2;

    const STATUS1 = 1;
    const STATUS2 = 2;
    const STATUS3 = 3;
    const STATUS4 = 4;
    const STATUS5 = 5;
    public static $status = [
          self::STATUS1 => '未成交',
          self::STATUS2 => '部分成交',
          self::STATUS3 => '完全成交',
          self::STATUS4 => '撤单处理中',
          self::STATUS5 => '已撤销',

    ];
}

