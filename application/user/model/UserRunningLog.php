<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/24 0024
 * Time: 11:08
 */
namespace app\user\model;

use think\Model;

class UserRunningLog extends Model
{
    const TYPE1 = 1;
    const TYPE2 = 2;
    const TYPE3 = 3;
    const TYPE4 = 4;
    const TYPE5 = 5;
    const TYPE6 = 6;
    const TYPE7 = 7;
    const TYPE8 = 8;
    const TYPE9 = 9;
    const TYPE10 = 10;
    const TYPE11 = 11;
    const TYPE12 = 12;
    const TYPE13 = 13;
    const TYPE14 = 14;
    const TYPE15 = 15;
    const TYPE16 = 16;
    const TYPE17 = 17;
    const TYPE18 = 18;
    const TYPE19 = 19;
    const TYPE20 = 20;
    const TYPE21 = 21;
    const TYPE22 = 22;
    const TYPE23 = 23;
    const TYPE24 = 24;
    const TYPE25 = 25;
    const TYPE26 = 26;
    const TYPE27 = 27;
    const TYPE28 = 28;
    const TYPE29 = 29;
    const TYPE30 = 30;
    const TYPE31 = 31;
    const TYPE32 = 32;
    const TYPE33 = 33;
    const TYPE34 = 34;
    const TYPE35 = 35;
    const TYPE36 = 36;
    public static $running_type = [
        self::TYPE1 => '后台充值',
        self::TYPE2 => '后台扣除',
        self::TYPE3 => '申请充值',
        self::TYPE4 => '提现拒绝返还',
        self::TYPE5 => '提现扣除',
        self::TYPE6 => '转账增加',
        self::TYPE7 => '转账扣除',
        self::TYPE8 => '转换增加',
        self::TYPE9 => '转换扣除',
        self::TYPE10 => '在线充值',
        self::TYPE11 => '购物扣除',
        self::TYPE12 => '取消订单返还',
        self::TYPE13 => '报单扣除',
        self::TYPE14 => '重消',
        self::TYPE15 => '扣税',
        self::TYPE16 => '复投',
        self::TYPE17 => '提成',
        self::TYPE18 => '静态收益',
        self::TYPE19 => '直推奖',
        self::TYPE20 => '动态奖',
        self::TYPE21 => '报单中心奖',
        self::TYPE22 => '激活成功本金增加',
        self::TYPE23 => '动态奖转换阿美币',
        self::TYPE24 => '转换手续费',
        self::TYPE25 => '现金币转换阿美币',
        self::TYPE26 => '提现手续费',
        self::TYPE27 => '提现扣除给油卡',
        self::TYPE28 => '交易增加',
        self::TYPE29 => '交易扣除',
        self::TYPE30 => '交易手续费',
        self::TYPE31 => '撤销挂单',
        self::TYPE32 => '挂单扣除',
        self::TYPE33 => '撤回复投',
        self::TYPE34 => '撤回复投手续费',
        self::TYPE35 => '锁仓扣除',
        self::TYPE36 => '截仓扣除'
    ];





}