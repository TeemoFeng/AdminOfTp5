<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20 0020
 * Time: 23:48
 */
namespace app\home\controller;
use think\Db;
class User extends Common
{
    //前台用户首页
    public function index()
    {
        // 获取缓存数据
        $authRule = cache('UserAuthRule');
        if(!$authRule){
            //2019-3-20添加区分前后台权限
            $authRule = db('auth_rule')->where(['menustatus'=>1,'type' => 2])->order('sort')->select();
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
}
