<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/22 0022
 * Time: 23:32
 */
namespace app\user\controller;

use app\admin\model\UserCurrencyAccount;
use app\admin\model\UserNode;
use app\admin\model\UserReferee;
use app\user\model\UserRunningLog;
use think\Db;
use think\db\Where;
use app\admin\model\BonusSet;
use app\admin\model\Users as UsersModel;

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
            $baodan_center = session('user.baodan_center');
            if($baodan_center == 1){
                $where['baodan_user'] = session('user.usernum'); //报单中心获取注册的用户
            }else{
                $where['pid'] = $user_id; //普通用户获取自己推荐会员

            }
            $where['status'] = 1;
            $where['sys_type'] = 1;
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
                $tuijian_user = UsersModel::where(['id' => $v['pid']])->value('username');
                $jidianren_user = UsersModel::where(['id' => $v['npid']])->value('username');
                $baodan_user = UsersModel::where(['usernum' => $v['baodan_user']])->value('username');
                $list['data'][$k]['referee'] = $v['referee'] . '【' .$tuijian_user . '】';
                $list['data'][$k]['contact_person'] = $v['contact_person'] . '【' .$jidianren_user . '】';
                $list['data'][$k]['baodan_user'] = $v['baodan_user'] . '【' .$baodan_user . '】';
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

            $where['baodan_user'] = session('user.usernum'); //报单中心获取注册的用户
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
                $tuijian_user = UsersModel::where(['id' => $v['pid']])->value('username');
                $jidianren_user = UsersModel::where(['id' => $v['npid']])->value('username');
                $baodan_user = UsersModel::where(['usernum' => $v['baodan_user']])->value('username');
                $list['data'][$k]['referee'] = $v['referee'] . '【' .$tuijian_user . '】';
                $list['data'][$k]['contact_person'] = $v['contact_person'] . '【' .$jidianren_user . '】';
                $list['data'][$k]['baodan_user'] = $v['baodan_user'] . '【' .$baodan_user . '】';
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }

        return $this->fetch('noActivateList');


    }

    //会员激活
    public function userActive()
    {
        $user_info = db('users')->where(['id'=>input('id')])->find();
        if($user_info){
            $user_name = $user_info['usernum'].'【'.$user_info['username'].'】';
            $this->assign('user_name', $user_name);
            $this->assign('id', input('id'));
            //获取用户级别表
            $user_level = db('user_level')->order('level_id')->select();

            $this->assign('user_level', $user_level);
        }
        return $this->fetch('userActive');
    }

    //获取报单额
    public function getBaodan()
    {
        if(request()->isPost()){
            $level = input('post.level');
            $val =  Db::name('bonus_set')->where(['level_id' => $level])->value('declaration');
            if(!empty($val)){
                return ['code' => 1, 'val' => $val];
            }else{
                return ['code' => 0, 'msg' => '查找失败'];
            }


        }

    }

    //激活提交
    public function sureActive()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['id'])){
                return ['code' => 0, 'msg' => '请选择用户'];
            }
            if(empty($data['level'])){
                return ['code' => 0, 'msg' => '请选择会员级别'];
            }
            //根据会员级别做直推奖励
            $save['level'] = $data['level'];
            $save['status'] = 1;
            $save['enabled'] = 1;
            $save['active_time'] = time();
            if($data['level'] == 1){
                $bonus_ratio = BonusSet::where(['level_id' => 1])->find();

            }elseif ($data['level'] == 2){
                $bonus_ratio = BonusSet::where(['level_id' => 2])->find();
            }elseif ($data['level'] == 3){
                $bonus_ratio = BonusSet::where(['level_id' => 3])->find();
            }elseif ($data['level'] == 4){
                $bonus_ratio = BonusSet::where(['level_id' => 4])->find();
            }elseif ($data['level'] == 5){
                $bonus_ratio = BonusSet::where(['level_id' => 5])->find();

                $save['baodan_center'] = 1; //投资额到1万直接设置为报单中心
            }


            //查找用户的推荐人/报单中心信息
            $user_info = UsersModel::where(['id' => $data['id']])->find();
            //判断用户是否重复激活、
            if($user_info['status'] == 1){
                return ['code' => 0, 'msg' => '该会员已激活，请勿重复激活'];
            }
            $referee = UsersModel::where(['usernum' => $user_info['referee']])->find();
            $baodan_user = UsersModel::where(['usernum' => $user_info['baodan_user']])->find(); //保单中心信息
            //查询报单中心激活钱包数量
            $activation_num = UserCurrencyAccount::where(['user_id' => $baodan_user['id']])->value('activation_num');
            if(bccomp($bonus_ratio['declaration'],$activation_num,4) == 1){
                return ['code' => 0, 'msg' => '激活钱包余额不足，激活失败'];
            }


            Db::startTrans();
            //更新用户信息
            $res = UsersModel::where(['id' => $data['id']])->update($save);
            if($res === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '激活失败请重试'];
            }
            //报单中心激活钱包减少
            $activation_blance = bcsub($activation_num, $bonus_ratio['declaration'], 4);
            $res4 = UserCurrencyAccount::where(['user_id' => $baodan_user['id']])->update(['activation_num' => $activation_blance]);
            if($res4 === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '激活失败请重试'];
            }

            $log_baodan = UserRunningLog::create([
                'user_id'  =>  $baodan_user['id'],
                'about_id' =>  $data['id'],
                'running_type'  => UserRunningLog::TYPE13,
                'account_type'  => 2,
                'change_num'    => $bonus_ratio['declaration'],
                'balance'       => $activation_blance,
                'create_time'   => time(),
                'remark'        => '激活用户报单扣除'
            ]);
            if($log_baodan == false){
                Db::rollback();
                return ['code' => 0, 'msg' => '激活失败请重试'];
            }

            if($baodan_user['id'] != 1){
                //报单中心奖励
                $baodan_ratio = Db::name('bonus_ext_set')->where(['id' => 1])->value('baodan_ratio');
                $baodan_ratio = bcdiv($baodan_ratio, 100, 2);
                $jiangli = bcmul($bonus_ratio['declaration'],$baodan_ratio, 4); //保单奖励
                $baodan_account = UserCurrencyAccount::where(['user_id' => $baodan_user['id']])->find();
                if(empty($baodan_account)){
                    Db::rollback();
                    return ['code' =>0, 'msg' => '报单中心用户钱包不存在'];
                }
                $cash_currency_num2 = bcadd($baodan_account['cash_currency_num'],$jiangli,4);

                $baodan_res = UserCurrencyAccount::where(['user_id' => $baodan_user['id']])->update(['cash_currency_num' => $cash_currency_num2]);
                if($baodan_res === false){
                    Db::rollback();
                    return ['code' => 0, 'msg' => '激活失败请重试'];
                }
                //记录用户报单奖励
                $log = UserRunningLog::create([
                    'user_id'  =>  $baodan_user['id'],
                    'about_id' =>  $data['id'],
                    'running_type'  => UserRunningLog::TYPE21,
                    'account_type'  => 1,
                    'change_num'    => $jiangli,
                    'balance'       => $cash_currency_num2,
                    'create_time'   => time(),
                    'remark'        => '报单中心奖励'
                ]);
                if($log == false){
                    Db::rollback();
                    return ['code' => 0, 'msg' => '激活失败请重试'];
                }
            }

            if($referee['id'] != 1){
                //直推奖励
                $bonus_ratio2 = BonusSet::where(['level_id' => $referee['level']])->find();
                $ratio = bcdiv($bonus_ratio2['bonus_ratio'],100,4);
                $reward = bcmul($bonus_ratio['declaration'], $ratio,4);

                //查询推荐人钱包信息
                $referee_account = UserCurrencyAccount::where(['user_id' => $referee['id']])->find();
                if(empty($referee_account)){
                    Db::rollback();
                    return ['code' =>0, 'msg' => '推荐人钱包不存在'];
                }
                $cash_currency_num = bcadd($referee_account['cash_currency_num'],$reward,4);
                $res2 = UserCurrencyAccount::where(['user_id' => $referee['id']])->update(['cash_currency_num' => $cash_currency_num]);

                if($res2 === false){
                    Db::rollback();
                    return ['code' => 0, 'msg' => '激活失败请重试'];
                }

                //记录收益日志
                $log2 = UserRunningLog::create([
                    'user_id'  =>  $referee['id'],
                    'about_id' =>  $data['id'],
                    'running_type'  => UserRunningLog::TYPE19,
                    'account_type'  => 1,
                    'change_num'    => $reward,
                    'balance'       =>  $cash_currency_num,
                    'create_time'   => time()
                ]);
                if($log2 == false){
                    Db::rollback();
                    return ['code' => 0, 'msg' => '激活失败请重试'];
                }
            }

            //更新用户本金钱包
            $res3 = UserCurrencyAccount::where(['user_id' => $data['id']])->update(['corpus' => $bonus_ratio['declaration']]);

            if($res3 === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '激活失败请重试'];
            }


            Db::commit();
            UserReferee::where(['user_id' => $data['id']])->update(['enabled' => 1]);
            UserNode::where(['user_id' => $data['id']])->update(['enabled' => 1]);

            //记录用户激活
            UserRunningLog::create([
                'user_id'  =>  $data['id'],
                'about_id' =>  $data['id'],
                'running_type'  => UserRunningLog::TYPE22,
                'account_type'  => 5,
                'change_num'    => $bonus_ratio['declaration'],
                'balance'       => $bonus_ratio['declaration'],
                'create_time'   => time()
            ]);

            return ['code' => 1, 'msg' => '激活成功', 'url' => url('notActivate')];

        }else{
            return ['code' => 0, 'msg' => '请求出错'];
        }
    }





    //报单中心删除自己推荐的无效会员
    public function usersDel(){
        db('users')->delete(['id'=>input('id')]);
        db('oauth')->delete(['uid'=>input('id')]);
        return $result = ['code'=>1,'msg'=>'删除成功!'];
    }


    //直推架构树
    public function usertree()
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
                ->field('u.id,u.referee,u.username,u.usernum,u.pid,u.have_tree,ul.level_name')
                ->where($where)
                ->order('u.id asc')
                ->select();
            $list = [];
            array_map(function ($v) use (&$list) {
                $list[] = [
                    'id' => $v['id'],
                    'name' => $v['usernum'] . '(' . $v['username'] . ' 级别:' . $v['level_name']. ')',
                    'isParent'  => $v['have_tree'],
                    'icon' => "/static/admin/images/user.png"
                ];
            },$user_info);

            return $list;

        }
        return $this->fetch('userTree');
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
//            $province       = explode(':',$data['province']);
//            $data['province'] = isset($province[1]) ? $province[1] : '';
//            $city           = explode(':',$data['city']);
//            $data['city']   = isset( $city[1]) ? $city[1] : '';
//            $district       = explode(':',$data['district']);
//            $data['district'] = isset( $district[1]) ? $district[1] : '';
            if (empty($data['mobile'])) return ['code' => 0, 'msg' => '手机号不能为空'];

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
            //验证会员编号是否重复
            $check_user = UsersModel::where(['usernum' => $data['usernum']])->find();
            if ($check_user) {
                return ['code' => 0, 'msg' => '该会员编号已存在'];
            }
            //检测密码是否相等
            if($data['password'] != $data['confirmPwd']) return ['code' => 0, 'msg' => '两次输入的登录密码不一致'];
            if($data['safeword'] != $data['confirmSafePwd']) return ['code' => 0, 'msg' => '两次输入的安全密码不一致'];

            $data['password'] = md5($data['password']);
            $data['pwd'] = lock_url($data['password']); //加密 用户找回密码
            $data['nickname'] = $data['username'];
            //推荐人关系
            $referrr_info =  UsersModel::get(['usernum' => $data['referee']]);
            if(empty($referrr_info)){
                return ['code' => 0, 'msg' => '推荐人不存在，请重新选择'];
            }
            $data['pid'] = $referrr_info['id'];
            $data['referee'] = $referrr_info['usernum'];

            //报单人
            $baodan_info    = UsersModel::get(['usernum' => $data['baodan_user']]);
            if (empty($baodan_info)) {
                return ['code' => 0, 'msg' => '报单中心不存在，请重新选择'];
            }
            if($baodan_info['baodan_center'] != 1){
                return ['code' => 0, 'msg' => '该账号不是有效的报单中心，请重新选择'];
            }
            $data['baodan_user'] = $baodan_info['usernum'];

            //接点人
            $node_info    = UsersModel::get(['usernum' => $data['contact_person']]);
            if (empty($node_info)) {
                return ['code' => 0, 'msg' => '接点人不存在，请重新选择'];
            }
            $data['npid'] = $node_info['id'];
            $data['contact_person'] = $node_info['usernum'];
            //是否报备银行
            if(!empty($data['bank_id']) && !empty($data['bank_user']) && !empty($data['bank_account']) &&!empty($data['bank_desc'])){
                $data['is_report'] = 1; //报备银行
            }else{
                $data['is_report'] = 0;
            }
            $data['create_time'] = date('Y-m-d',time()); //添加时间方便做折线图
            $new_user_id = UsersModel::create($data);
            if ($new_user_id) {
                //推荐人邀请成功用户，修改users表 have_tree 为1
                UsersModel::where(['id' => $referrr_info['id']])->update(['have_tree' => 1]);
                $user_referee_model = new UserReferee();
                $user_node_model = new UserNode();
                if($data['pid'] == 1){
                    //接入用户和接点人关系
                    $data2['user_id'] = $new_user_id->id;
                    $data2['user_son_str'] = 1 . ',';
                    $data2['deep'] = count(explode(',', $data2['user_son_str']))-1;

                }else{
                    $son_str = $user_referee_model->where(['user_id' => $referrr_info['id'] ])->value('user_son_str');
                    //接入用户和推荐人的关系
                    $data2['user_id'] = $new_user_id->id;
                    $data2['user_son_str'] = $son_str. $referrr_info["id"] .',';
                    $data2['deep'] = count(explode(',', $data2['user_son_str']))-1;

                }

                if($data['npid'] == 1){
                    //接入用户和接点人的关系
                    $data3['user_id'] = $new_user_id->id;
                    $data3['user_son_str'] = 1 . ',';
                    $data3['deep'] = count(explode(',', $data3['user_son_str'])) - 1;
                }else{
                    $son_node_str = $user_node_model->where(['user_id' => $node_info['id']])->value('user_son_str');

                    //接入用户和接点人的关系
                    $data3['user_id'] = $new_user_id->id;
                    $data3['user_son_str'] = $son_node_str . $node_info['id'] . ',';
                    $data3['deep'] = count(explode(',', $data3['user_son_str'])) - 1;
                }
                UserReferee::create($data2);
                UserNode::create($data3);
                //获取当前设置的汇率
                $sate = db('bonus_ext_set')->where(['id' => 1])->value('money_change');
                //创建用户钱包账户
                $account['user_id'] = $new_user_id->id;
                $account['rate'] = $sate;
                UserCurrencyAccount::create($account);

                return ['code' => 1, 'msg' => '注册成功', 'url' => url('userList')];
            } else {
                return ['code' => 0, 'msg' => '注册失败'];
            }

        } else {
            $province   = db('Region')->where ( array('pid'=>1) )->select();
            $user_level = db('user_level')->order('sort')->select();
            $bank = db('Bank')->order('id ASC')->select();
            $user_num = createVipNum();
            //推荐用户/报单中心 填自己
            $user_info = session('user');
            $this->assign('province', json_encode($province,true));
            $this->assign('user_info', $user_info);
            $this->assign('user_level', json_encode($user_level, true)); //会员级别
            $this->assign('bank', $bank); //银行列表
            $this->assign('usernum', $user_num); //会员编号

            return $this->fetch();
        }

    }

    //验证推荐人和接点人报单中心
    public function validateUser()
    {
        $search = input('post.search');
        $type = input('post.type');
        if(empty($search) || empty($type)){
            return ['code' => 0, 'msg' => '此用户不存在'];
        }
        if($type == 1){
            //推荐人
            $where['usernum'] = $search;
        }elseif($type == 2){
            //接点人
            $where['usernum'] = $search;
        }elseif($type == 3){
            $where['usernum'] = $search;
        }

        $user_info = UsersModel::get($where);
        if(empty($user_info)){
            return ['code' => 0, 'msg' => '此用户不存在'];
        }else{
            return ['code' => 1, 'name' => $user_info['username']];
        }


    }



}