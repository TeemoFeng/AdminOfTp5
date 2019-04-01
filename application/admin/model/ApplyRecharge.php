<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/13 0013
 * Time: 23:34
 */
namespace app\admin\model;
use think\Model;
use think\Db;
//申请充值model
class ApplyRecharge extends Model
{
    const NOT_LOOK      = 1;
    const HAVE_PASS     = 2;
    const NO_PASS       = 3;
    //申请充值状态
    public static $status = [
        self::NOT_LOOK  => '未审核',
        self::HAVE_PASS => '已通过',
        self::NO_PASS   => '未通过',
    ];

    //充值方式 recharge_method
    const YINHANGKA = 1;
    const ZHIFUBAO   = 2;
    const WEIXIN    = 3;
    public static $recharge_method = [
        self::YINHANGKA => '银行卡',
        self::ZHIFUBAO  => '支付宝',
        self::WEIXIN    => '微信',

    ];

    public static $cash_method = [
        self::YINHANGKA => '银行卡',
        self::ZHIFUBAO  => '支付宝',
    ];

    protected $autoWriteTimestamp = 'datetime';
}