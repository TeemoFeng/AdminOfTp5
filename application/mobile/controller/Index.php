<?php
namespace app\mobile\controller;
use clt\Lunar;
class Index extends Common{
    public function initialize(){
        parent::initialize();
    }
    public function index(){
        return view();
    }

    //登录
    public function login()
    {
        return $this->fetch('login');
    }

    //注册
    public function reg()
    {
        return $this->fetch('register');
    }



}