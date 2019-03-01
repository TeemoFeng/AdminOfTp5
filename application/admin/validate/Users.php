<?php
namespace app\admin\validate;

use think\Validate;

class Users extends Validate
{
    protected $rule =   [
        'usernum'       => 'require',
        'username'      => 'require|length:4,25',
        'mobile'        => 'require|number|length:11',
        'password'      => 'require|length:6,25',
        'confirmPwd'    => 'require|length:6,25',
        'safeword'      => 'require|length:6,25',
        'confirmSafePwd'=> 'require|length:6,25',
    ];


    protected $message  =   [
        'usernum.require'       => '用户编号不能为空',
        'username.require'      => '用户名不能为空',
        'username.length'       => '用户名在4到25个字符之间',
        'mobile.require'        => '手机号不能为空',
        'mobile.length'         => '手机号长度必须是11位',
        'password.require'      => '密码不能为空',
        'password.length'       => '密码长度在6到25个字符之间',
        'confirmPwd.require'    => '确认登录密码不能为空',
        'confirmPwd.length'     => '确认登录密码要在6到25个字符之间',
        'safeword.require'      => '安全密码不能为空',
        'safeword.length'       => '安全密码要在6到25个字符之间',
        'confirmSafePwd.require'=> '确认安全密码不能为空',
        'confirmSafePwd.length' => '确认安全密码要在6到25个字符之间',
    ];
}