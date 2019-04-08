<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/4/7
 * Time: 10:45
 */
namespace app\admin\model;

use think\Model;

class UserTradeDepute extends Model
{
    const STATUS1 = 1;
    const STATUS2 = 2;
    const STATUS3 = 3;
    const STATUS4 = 4;
    const STATUS5 = 5;

    const TYPE1 = 1;
    const TYPE2 = 2;

    public static $status = [
        self::STATUS1 => '未成交',
        self::STATUS2 => '部分成交',
        self::STATUS3 => '完成成交',
        self::STATUS4 => '撤单处理中',
        self::STATUS5 => '已撤销',
    ];

    public static $currency_type = [
        self::TYPE1 => '<span style="color: red">买入</span>',
        self::TYPE2 => '<span style="color: green">卖出</span>',
    ];
}