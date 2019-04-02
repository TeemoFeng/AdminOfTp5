<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26 0026
 * Time: 23:36
 */
namespace app\admin\model;
use think\Model;
use think\Db;
class CurrencyList extends Model
{
    const STATUS1 = 'close';
    const STATUS2 = 'open';
    public static $status = [
        self::STATUS1 => '未上线',
        self::STATUS2 => '已上线',
    ];

    public static $trade_status = [
        self::STATUS1 => '未开启',
        self::STATUS2 => '已开启',
    ];
}