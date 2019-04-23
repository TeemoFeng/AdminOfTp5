<?php
namespace app\mobile\controller;
use think\Db;
use clt\Leftnav;
use think\Controller;
class Common extends Controller
{
    public function initialize()
    {
        if(!isMobile()){
            $this->redirect('home/index/index');
        }

        $this->assign('user_info', session('user'));


        $sys = cache('System');
        $this->assign('sys',$sys);

    }
    //空操作
    public function _empty(){
        return $this->error('空操作，返回上次访问页面中...');
    }

    //退出登陆
    public function logout(){
        session('user',null);
        $this->redirect('mobile/login/index');
    }
}
