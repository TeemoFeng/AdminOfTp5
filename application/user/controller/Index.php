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
        //获取user_id，如果是后台点查看用户信息
        $user_id = input('get.user_id');
        if(!empty($user_id)){
            if (!session('aid')) {
                $this->redirect('user/index/index');
            }
            session('uid',$user_id); //设置用户id
        }
        // 获取缓存数据
        $authRule = cache('UserAuthRule');
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
        $user_id = session('uid');
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

    //用户推荐的用户列表
    public function userList()
    {
        if(request()->isPost()){
            $key=input('post.key');
            $data   =input('post.');
            $where  = $this->makeSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');

            $user_id = session('uid');
            $where['pid'] = $user_id;
            $where['status'] = 1;
            //根绝用户id获取推荐的人员信息
            $userModel = new Users();
            $list = db('users')
                ->alias('u')
                ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                ->field('u.*,ul.level_name')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['active_time'] = date('Y-m-d H:s',$v['active_time']);
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }

        return $this->fetch();


    }


    public function makeSearch($data)
    {
        $where = [];
        if(!empty($data['status'])){
            $where['a.status'] = $data['status'];
        }
        if(!empty($data['start_time']) && empty($data['end_time'])){
            $where['a.create_time'] = array('egt', $data['start_time']);
        }
        if(!empty($data['end_time']) && empty($data['start_time'])){
            $where['a.create_time'] = array('elt',$data['end_time']);
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $where['a.create_time'] = array('between', array($data['start_time'], $data['end_time']));
        }
        if(!empty($data['key'])){
            $where['u.id|u.email|u.mobile|u.username'] = array('like', '%' . $data['key'] . '%');
        }
        return $where;
    }


}