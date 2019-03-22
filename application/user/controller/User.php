<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/22 0022
 * Time: 23:32
 */
namespace app\user\controller;

class User extends Common{
    //用户推荐的用户列表
    public function userList()
    {
        if(request()->isPost()){
            $data   = input('post.');
            $where  = $this->makeSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');

            $user_id = session('user.id');
            $where['pid'] = $user_id;
            $where['status'] = 1;
            //根绝用户id获取推荐的人员信息
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

        return $this->fetch('userList');


    }

    //搜索条件
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

    //未激活列表
    public function notActivate()
    {
        if(request()->isPost()){
            $data   = input('post.');
            $where  = $this->makeSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');

            $user_id = session('user.id');
            $where['pid'] = $user_id;
            $where['status'] = 0;
            //根绝用户id获取推荐的人员信息
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

        return $this->fetch('userList');


    }

    //直推架构树
    public function userTree()
    {
        if (request()->isPost()) {
            $where = [];
            $user_id = session('user.id');
            if(array_key_exists( 'id',$_REQUEST)) {
                $pId = $_REQUEST['id'];
                $pId = htmlspecialchars($pId);
                if ($pId == null || $pId == "") $pId = $user_id;
                $where['u.pid'] = $pId;

            }else{
                $where['u.pid'] = $user_id;
            }
            if(array_key_exists( 'key',$_REQUEST)){
                $referee = $_REQUEST['key'];
                if(!empty($referee)){
                    unset($where);
                    $where['u.username'] = $referee;
                }
            }
            $user_info = db('users')
                ->alias('u')
                ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                ->field('u.id,u.referee,u.username,u.pid,u.have_tree,ul.level_name')
                ->where($where)
                ->order('u.id asc')
                ->select();
            $list = [];
            array_map(function ($v) use (&$list) {
                $list[] = [
                    'id' => $v['id'],
                    'name' => $v['referee'] . '(' . $v['username'] . ' 级别:' . $v['level_name']. ')',
                    'isParent'  => $v['have_tree'],
                ];
            },$user_info);

            return $list;

        }
        return $this->fetch('UserTree');
    }

    //原点复投
    public function originReset()
    {
        //复投数量
        $origin_num = db('bonus_ext_set')->where(['id' => 1])->field('double_throw_num')->find();
        $this->assign('origin_num', $origin_num['double_throw_num']);
        return $this->fetch('originReset');

    }

    //原点撤回
    public function withdraw()
    {
        $origin_num = db('bonus_ext_set')->where(['id' => 1])->field('double_throw_recall')->find();
        $this->assign('origin_num', $origin_num['double_throw_recall']);
        return $this->fetch('withdraw');

    }

}