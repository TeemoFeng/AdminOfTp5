<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/22 0022
 * Time: 23:32
 */
namespace app\user\controller;

use app\user\model\UserRunningLog;
use think\db\Where;

class User extends Common{
    public function initialize(){
        parent::initialize();


    }
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
        $where = new Where();
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
            $where['a.create_time'] = array('between time', array($data['start_time'], $data['end_time']));
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
        $user_id = session('user.id');
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['cash_input_num'])){
                return ['code' => 0, 'msg' => '复投数量不能为空'];
            }
            if((int)$data['cash_input_num'] > (int)$data['can_use_num']){
                return ['code' => 0, 'msg' => '复投数量不能大于可用数量'];
            }
            //查询用户账户信息
            $user_cash_account = db('user_currency_account')->where(['user_id' => $user_id])->find();
            if(empty($user_cash_account)){
                return ['code' => 0, 'msg' => '数据异常'];
            }
            $cash_input_num     = bcadd($user_cash_account['cash_input_num'], $data['cash_input_num'], 4); //用户累计投入数量
            $cash_currency_num  = bcsub($user_cash_account['cash_currency_num'],$data['cash_input_num'],4); //用户现金币数量
            $save['cash_input_num'] = $cash_input_num;
            $save['cash_currency_num'] = $cash_currency_num;
            $res = db('user_currency_account')->where(['user_id' => $user_id])->update($save);
            if($res === false){
                return ['code' => 0, 'msg' => '复投失败，请重试'];
            }
            //记录流水日志
            UserRunningLog::create([
                'user_id'  =>  $user_id,
                'about_id' =>  $user_id,
                'running_type'  => UserRunningLog::TYPE16,
                'account_type'  => 1,
                'change_num'    => -$data['cash_input_num'],
                'balance'       =>  $cash_currency_num,
                'create_time'   => time()
            ]);

            return ['code' => 1, 'msg' => '复投成功'];
        }
        //复投数量设置
        $origin_num = db('bonus_ext_set')->where(['id' => 1])->field('double_throw_num')->find();
        //现金币余额
        $cash_currency_num = db('user_currency_account')->where(['user_id' => $user_id])->value('cash_currency_num');
        if(empty($cash_currency_num)) $cash_currency_num = '0.0000';

        $can_user_num = floatval($cash_currency_num);
        $this->assign('origin_num', $origin_num['double_throw_num']); //复投设置的数量
        $this->assign('cash_currency_num', $cash_currency_num); //现金币余额
        $this->assign('can_use_num', $can_user_num); //现金币可用数量

        return $this->fetch('originReset');

    }

    //原点撤回
    public function withdraw()
    {

        $user_id = session('user.id');
        $origin_num = db('bonus_ext_set')->where(['id' => 1])->field('double_throw_recall')->find();
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['recall_num'])){
                return ['code' => 0, 'msg' => '撤回复投数量不能为空'];
            }
            if((int)$data['recall_num'] > (int)$data['can_reset_num']){
                return ['code' => 0, 'msg' => '撤回复投数量不能大于可用数量'];
            }
            //查询用户账户信息
            $user_cash_account = db('user_currency_account')->where(['user_id' => $user_id])->find();
            if(empty($user_cash_account)){
                return ['code' => 0, 'msg' => '数据异常'];
            }
            if(empty($origin_num)){
                return ['code' => 0, 'msg' => '配置错误'];
            }

            $origin_num = $origin_num['double_throw_recall']/100;
            $rate = bcsub('1',$origin_num,2);
            $recall_num = bcmul($data['recall_num'],$rate,4);
            $shoxufei = bcmul($data['recall_num'],$origin_num,4); //撤回复投手续费
            $cash_currency_num    = bcadd($user_cash_account['cash_currency_num'], $recall_num, 4); //用户累计投入数量
            $cash_input_num  = bcsub($user_cash_account['cash_input_num'], $data['recall_num'],4); //用户现金币数量
            $save['cash_input_num'] = $cash_input_num;
            $save['cash_currency_num'] = $cash_currency_num;
            $res = db('user_currency_account')->where(['user_id' => $user_id])->update($save);
            if($res === false){
                return ['code' => 0, 'msg' => '撤回复投失败，请重试'];
            }
            //记录撤回复投流水日志
            UserRunningLog::create([
                'user_id'  =>  $user_id,
                'about_id' =>  $user_id,
                'running_type'  => UserRunningLog::TYPE33,
                'account_type'  => 1,
                'change_num'    => $data['recall_num'],
                'balance'       => $cash_currency_num,
                'create_time'   => time()
            ]);
            //记录撤回复投手续费日志
            UserRunningLog::create([
                'user_id'  =>  $user_id,
                'about_id' =>  $user_id,
                'running_type'  => UserRunningLog::TYPE34,
                'account_type'  => 1,
                'change_num'    => -$shoxufei,
                'balance'       => $cash_currency_num,
                'create_time'   => time()
            ]);

            return ['code' => 1, 'msg' => '撤回复投成功'];
        }
        //现金币余额
        $cash_input_num = db('user_currency_account')->where(['user_id' => $user_id])->value('cash_input_num');
        if(empty($cash_input_num)) $cash_input_num = '0.0000';

        $can_reset_num = floatval($cash_input_num);
        $this->assign('origin_num', $origin_num['double_throw_recall']); //撤回复投扣除比率
        $this->assign('can_reset_num', $can_reset_num); //可撤回复投数量
        $this->assign('cash_input_num', $cash_input_num); //复投数量
        return $this->fetch('withdraw');

    }

}