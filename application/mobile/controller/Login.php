<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/4/19
 * Time: 15:31
 */
namespace app\mobile\controller;
use clt\Lunar;
class Login extends Common{


    //登录
    public function index()
    {
        return $this->fetch('login');
    }

    //注册
    public function reg()
    {
        return $this->fetch('register');
    }

    //忘记密码
    public function forget()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['mobile']) || empty($data['code'])){
                return ['code' => 0, 'msg' => '手机号或者验证码不能为空'];
            }
            $table = db('users');
            //获取验证码待写
            $mobile_send = session('mobile_'.$data['mobile']);
            $code_time = $mobile_send->time;
            $mobile_code = $mobile_send->code;
            if((time()-$code_time) > 300){
                return array('code' => 0, 'msg' => '验证码已过期，请重新发送');
            }
            if($data['password'] != $data['password2']){
                return array('code'=>-1,'msg'=>'两次输入密码不一致');
            }

            if ($mobile_code != $data['code']) {
                return array('code' => 0, 'msg' => '验证码错误');
            }else{
                session('mobile_'.$data['email'],null); //清除session
            }

            $res = db('users')->where('mobile',$data['mobile'])->update(['password'=>md5($data['password'])]);
            if($res === false){
                return array('code' => 0, 'msg' => '设置失败');
            }
            return array('code' => 1, 'msg'=>'密码设置成功！');


        }
        return $this->fetch('forget');
    }
}