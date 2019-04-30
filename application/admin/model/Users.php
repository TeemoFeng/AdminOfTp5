<?php
namespace app\admin\model;

use think\Model;

class Users extends Model
{
    const INVALID   = 1; //无效
    const VALID     = 2; //有效
    const ISLOCK    = 3; //冻结
    const BAODAN    = 4; //报单中心

    public static $status = [
        self::INVALID   => '无效会员',
        self::VALID     => '有效会员',
        self::ISLOCK    => '冻结会员',
        self::BAODAN    => '报单中心',

    ];

    //会员激活状态
    public static $acstatus = [
        0 => '<i class="layui-icon layui-icon-close"></i>',
        1 => '<i class="layui-icon layui-icon-ok"></i>',
    ];

    //是否有效状态
    public static $vastatus = [
        0 => '<i class="layui-icon layui-icon-close"></i>',
        1 => '<i class="layui-icon layui-icon-ok"></i>',
    ];

    //是否有效状态
    public static $vastatus2 = [
        0 => '否',
        1 => '是',
    ];

    //是否报单中心
    public static $bdstatus = [
        0 => '<i class="layui-icon layui-icon-close-fill"></i>',
        1 => '<i class="layui-icon layui-icon-ok-circle"></i>',
    ];

    //是否报单中心
    public static $bdstatus2 = [
        0 => '否',
        1 => '是',
    ];

    //是否报单中心
    public static $yhstatus = [
        0 => '未报备',
        1 => '已报备',
    ];


	protected $name = 'users';
    protected $type       = [
        // 设置addtime为时间戳类型（整型）
        'reg_time' => 'timestamp:Y-m-d H:i:s',
    ];
	// birthday修改器
	protected function setpwdAttr($value){
			return md5($value);
	}

    protected static function init()
    {
        self::beforeInsert(function ($data) {
            $data['reg_time'] = time();
        });
    }



}