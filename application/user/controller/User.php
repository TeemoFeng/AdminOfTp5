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
                $list['data'][$k]['reg_time'] = date('Y-m-d H:s',$v['reg_time']);
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }

        return $this->fetch('noActivateList');


    }

    //报单中心删除自己推荐的无效会员
    public function usersDel(){
        db('users')->delete(['id'=>input('id')]);
        db('oauth')->delete(['uid'=>input('id')]);
        return $result = ['code'=>1,'msg'=>'删除成功!'];
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
                    'icon' => "/static/admin/images/user.png"
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

    //报单中心用户注册
    public function register()
    {
        if (request()->isPost()) {
            $data   = input('post.');
            $level          = explode(':',$data['level']); //ng 获取的值要单独去除number:
            $data['level']  = $level[1]; //默认会员等级为注册会员
//            $province       = explode(':',$data['province']);
//            $data['province'] = isset($province[1]) ? $province[1] : '';
//            $city           = explode(':',$data['city']);
//            $data['city']   = isset( $city[1]) ? $city[1] : '';
//            $district       = explode(':',$data['district']);
//            $data['district'] = isset( $district[1]) ? $district[1] : '';
            if (empty($data['mobile'])) return ['code' => 0, 'msg' => '手机号不能为空'];
            $check_user = UsersModel::where(['mobile' => $data['mobile']])->find();
            if ($check_user) {
                return $result = ['code' => 0, 'msg' => '该手机号已存在'];
            }
            //验证
            $check = [
                'usernum'           => $data['usernum'],
                'username'          => $data['username'],
                'mobile'            => $data['mobile'],
                'password'          => $data['password'],
                'confirmPwd'        => $data['confirmPwd'],
                'safeword'          => $data['safeword'],
                'confirmSafePwd'    => $data['confirmSafePwd'],
            ];
            //检测
            $validate = new \app\admin\validate\Users();
            $result = $validate->check($check);
            if (!$result) {
                return ['code' => 0, 'msg' => $validate->getError()];
            }
            //检测密码是否相等
            if($data['password'] != $data['confirmPwd']) return ['code' => 0, 'msg' => '两次输入的登录密码不一致'];
            if($data['safeword'] != $data['confirmSafePwd']) return ['code' => 0, 'msg' => '两次输入的安全密码不一致'];

            $data['password'] = md5($data['password']);
            //接入用户和推荐人关系
            if ($data['referee'] == '0000') {
                $data['pid'] = 0;
                $data['referee'] = '0000|公司';
            } else {
                //查询推荐人id
                $referrr_info =  UsersModel::get(['usernum' => $data['referee']]);
                $data['pid'] = $referrr_info['id'];
                $data['referee'] = $referrr_info['usernum'] .'|' .$referrr_info['username'];

            }

            if($data['contact_person'] == '0000'){
                $data['npid'] = 0;
                $data['contact_person'] = '0000|公司';
            }else{
                //查询接点人id
                $node_info    = UsersModel::get(['usernum' => $data['contact_person']]);
                $data['npid'] = $node_info['id'];
                $data['contact_person'] = $node_info['usernum'] .'|' .$node_info['username'];
            }

            //是否报备银行
            if(!empty($data['bank_id']) && !empty($data['bank_user']) && !empty($data['bank_account']) &&!empty($data['bank_desc'])){
                $data['is_report'] = 1; //报备银行
            }else{
                $data['is_report'] = 0;
            }
            $data['enabled'] = 1;
            $data['create_time'] = date('Y-m-d',time()); //添加时间方便做折线图
            $new_user_id = UsersModel::create($data);
            if ($new_user_id) {
                //推荐人邀请成功用户，修改users表 have_tree 为1
                UsersModel::where('id', $referrr_info['id'])->update(['have_tree' => 1]);
                $user_referee_model = new UserReferee();
                $user_node_model = new UserNode();
                if($data['pid'] == 0){
                    //接入用户和接点人关系
                    $data2['user_id'] = $new_user_id;
                    $data2['user_son_str'] = 0 . ',';
                    //接入用户和推荐人的关系
                    $data3['user_id'] = $new_user_id;
                    $data3['user_son_str'] = 0 . ',';
                }else{
                    $son_str = $user_referee_model->where(['user_id' => $referrr_info['id'] ])->value('user_son_str');
                    $son_node_str = $user_node_model->where(['user_id' => $node_info['id']])->value('user_son_str');
                    //接入用户和接点人关系
                    $data2['user_id'] = $new_user_id;
                    $data2['user_son_str'] = $son_str . $referrr_info["id"];
                    //接入用户和推荐人的关系
                    $data3['user_id'] = $new_user_id;
                    $data3['user_son_str'] = $son_node_str . $node_info['id'];
                }

                UserReferee::create($data2);
                UserNode::create($data3);
                //获取当前设置的汇率
                $sate = db('bonus_ext_set')->where(['id' => 1])->value('money_change');
                //创建用户钱包账户
                $account['user_id'] = $new_user_id;
                $account['rate'] = $sate;
                UserCurrencyAccount::create($account);

                return ['code' => 1, 'msg' => '注册成功', 'url' => url('index')];
            } else {
                return ['code' => 0, 'msg' => '注册失败'];
            }

        } else {
            $province   = db('Region')->where ( array('pid'=>1) )->select();
            $user_level = db('user_level')->order('sort')->select();
            $bank = db('Bank')->order('id ASC')->select();
            $user_num = createVipNum();
            $this->assign('province',json_encode($province,true));
            $this->assign('user_level', json_encode($user_level, true)); //会员级别
            $this->assign('bank', json_encode($bank, true)); //银行列表
            $this->assign('usernum', $user_num); //会员编号

            return $this->fetch();
        }

    }


}