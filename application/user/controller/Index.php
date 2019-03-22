<?php
namespace app\user\controller;
use app\user\model\Users;
use think\Input;
use think\Session;

class Index extends Common{
    public function initialize(){
        parent::initialize();


    }

//    public function index(){
//        $this->assign('title','会员中心');
//        return $this->fetch();
//    }

    //前台用户首页
    public function index()
    {

        // 获取缓存数据
        $authRule = cache('UserAuthRule');
//        cache('UserAuthRule', null);
        if(!$authRule){
            //2019-3-20添加区分前后台权限
            $authRule = db('user_auth_rule')->where(['menustatus'=>1])->order('sort')->select();
            cache('UserAuthRule', $authRule, 3600);
        }
        //声明数组
        $menus = array();
        foreach ($authRule as $key=>$val){
            $authRule[$key]['href'] = url($val['href']);
            if($val['pid']==0){

                $menus[] = $val;
            }
        }
        foreach ($menus as $k=>$v){
            foreach ($authRule as $kk=>$vv){
                if($v['id']==$vv['pid']){
                    $menus[$k]['children'][] = $vv;
                }
            }
        }
        $this->assign('menus',json_encode($menus,true));
        return $this->fetch();

    }

    public function main()
    {
        //用户信息
        $user_arr = [];
        $user_id = session('user.id');
        $userModel = new Users();
        $where['id'] = $user_id;
        $user_info = db('users')
            ->alias('u')
            ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
            ->field('u.id,u.usernum,u.username,u.referee,u.baodan_center,ul.level_name')
            ->where($where)
            ->find();
        //推荐人数
        $count = $userModel->where(['pid' => $user_id])->count();
        $baodancenter = Users::$baodancenter;
        $user_arr[] = [
            'title' => '会员编号：',
            'name'  => $user_info['usernum'],
        ];
        $user_arr[]= [
            'title' => '会员姓名：',
            'name'  => $user_info['username'],
        ];
        $user_arr[]= [
            'title' => '会员级别：',
            'name'  => $user_info['level_name'],
        ];
        $user_arr[]= [
            'title' => '报单中心：',
            'name'  => $baodancenter[$user_info['baodan_center']],
        ];
        $user_arr[]= [
            'title' => '推荐人：',
            'name'  => $user_info['referee'],
        ];
        $user_arr[]= [
            'title' => '推荐人数：',
            'name'  => $count,
        ];

        //用户账户信息


        $this->assign('user_arr', $user_arr);

        return $this->fetch();
    }




}