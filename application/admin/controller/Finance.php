<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/11 0011
 * Time: 22:00
 */
namespace app\admin\controller;
use app\admin\model\ApplyCash;
use app\admin\model\ApplyRecharge;
use app\admin\model\Currency;
use app\admin\model\CurrencyRecharge;
use app\admin\model\UserApplyConsumeCash;
use app\admin\model\UserApplyTradeCash;
use app\admin\model\UserCurrencyAccount;
use app\user\model\UserApplyActive;
use app\user\model\UserApplyShateCash;
use app\user\model\UserDynamicAmeiBonus;
use app\user\model\UserRunningLog;
use think\Db;
use think\db\Where;
use think\Exception;
use app\admin\model\Users as UsersModel;
use think\facade\Request;
class Finance extends Common
{
    //动态奖金转阿美币列表
    public function convert()
    {
        $data = Request::param();
        $where  = $this->convertSearch($data);
        if(!empty($data['export'])){
            $this->_exportCovert($where);
        }
        //获取用户动态转阿美币的列表
        if(request()->isPost()){
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');

            $list = db('user_dynamic_amei_bonus')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->field('a.*,u.usernum,u.username')
                ->where($where)
                ->order('a.id DESC')
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['status'] = UserDynamicAmeiBonus::$status[$v['status']];
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }

        return $this->fetch('convert');
    }

    //动态奖金转阿美币列表转出
    public function _exportCovert($where)
    {
        $list = db('user_dynamic_amei_bonus')
            ->alias('a')
            ->join(config('database.prefix').'users u','a.user_id = u.id','left')
            ->field('a.*,u.usernum,u.username')
            ->where($where)
            ->order('a.id DESC')
            ->select();
        $list2 = [];
        foreach ($list as $k=>$v){
            $list2[$k]['id'] = $v['id'];
            $list2[$k]['usernum'] = $v['usernum'];
            $list2[$k]['username'] = $v['username'];
            $list2[$k]['dynamic_bonus'] = $v['dynamic_bonus'];
            $list2[$k]['rate'] = $v['rate'];
            $list2[$k]['ameibi_price'] = $v['ameibi_price'];
            $list2[$k]['ameibi_num'] = $v['ameibi_num'];
            $list2[$k]['status'] = UserDynamicAmeiBonus::$status[$v['status']];
            $list2[$k]['create_time'] = $v['create_time'];
            $list2[$k]['grant_time'] = $v['grant_time'];
            $list2[$k]['remark'] = $v['remark'];
        }
        $file_name = '动态奖转换阿美币';
        $headArr = ['id','用户编号','用户名','动态奖金$','1$=￥汇率','阿美币价格￥','应发阿美币数量','状态','创建时间','发放时间','备注'];
        $this->excelExport($file_name, $headArr, $list2);
    }


    //搜索条件
    public function convertSearch($data)
    {
        $where = new Where();
        if(!empty($data['start_time']) && empty($data['end_time'])){
            $start_time = $data['start_time'] . ' 23:59:59';
            $where['a.create_time'] = array('egt', $start_time);
        }
        if(!empty($data['end_time']) && empty($data['start_time'])){
            $end_time = $data['end_time'] . ' 23:59:59';
            $where['a.create_time'] = array('elt', $end_time);
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $start_time = $data['start_time'] . ' 00:00:00';
            $end_time = $data['end_time'] . ' 23:59:59';
            $where['a.create_time'] = array('between time', array($start_time, $end_time));
        }
        if(!empty($data['key'])){
            $where['u.id|u.usernum|u.email|u.mobile|u.username'] = array('like', '%' . $data['key'] . '%');
        }
        return $where;
    }

    //转账锁仓列表
    public function lockPosition()
    {

    }

    //奖金统计列表
    public function bonusStatistics()
    {
        
    }

    //公司拨比列表
    public function allocationRatio()
    {
        $data = Request::param();
        $where  = $this->makeSearch3($data);

        if(!empty($data['export'])){
            if($data['type'] == 1){
                $this->_exportBobi($where);
            }
        }

        if(request()->isPost()){
            if(empty($data['type'])){
                return ['code' => 0, 'msg' => '非法请求'];
            }
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            if($data['type'] == 1){
                //日拨比
                $list = db('company_day_running')
                    ->where($where)
                    ->order('id DESC')
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['subside'] = bcsub($v['income'], $v['expenses'], 4);
                    if($v['expenses'] == 0){
                        $list['data'][$k]['ratio'] = '0%';
                    }else{
                        $ratio = bcdiv($v['expenses'],$v['income'], 4);
                        $list['data'][$k]['ratio'] = ($ratio*100).'%';
                    }


                }
                return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

            }else{
                //总拨比
                $list = db('company_day_running')
                    ->field('sum(income) income,sum(expenses) expenses')
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['subside'] = bcsub($v['income'], $v['expenses'], 4);
                    if($v['expenses'] == 0){
                        $list['data'][$k]['ratio'] = '0%';
                    }else{
                        $ratio = bcdiv($v['expenses'],$v['income'], 4);
                        $list['data'][$k]['ratio'] = ($ratio*100).'%';
                    }

                    $list['data'][$k]['time'] = date('Y-m-d',time());
                }
                return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];


            }

        }

        return $this->fetch('allocationRatio');
    }

    //公司拨比导出
    public function _exportBobi($where)
    {
        $list = db('company_day_running')
            ->where($where)
            ->order('id DESC')
            ->select();
        $list2 = [];
        foreach ($list as $k=>$v){
            $list2[$k]['id'] = $v['id'];
            $list2[$k]['time'] = $v['time'];
            $list2[$k]['income'] = $v['income'];
            $list2[$k]['expenses'] = $v['expenses'];
            $list2[$k]['subside'] = bcsub($v['income'], $v['expenses'], 4);
            if($v['expenses'] == 0){
                $list2[$k]['ratio'] = '0%';
            }else{
                $ratio = bcdiv($v['expenses'],$v['income'], 4);
                $list2[$k]['ratio'] = ($ratio*100).'%';
            }

        }
        $file_name = '公司拨比';
        $headArr = ['id','时间','收入','支出','沉淀','拨比率'];
        $this->excelExport($file_name, $headArr, $list2);

    }

    //拨比条件搜索
    public function makeSearch3($data)
    {
        $where = new Where();

        if(!empty($data['start_time']) && empty($data['end_time'])){
            $where['a.create_time'] = array('egt', $data['start_time']);
        }
        if(!empty($data['end_time']) && empty($data['start_time'])){
            $where['a.create_time'] = array('elt',$data['end_time']);
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){

            $where['a.create_time'] = array('between time', array($data['start_time'], $data['end_time']));
        }
        return $where;
    }

    //财务流水列表
    public function financialFlow()
    {
        $running_type = UserRunningLog::$running_type;
        $data = Request::param();
        $where  = $this->makeSearch2($data);

        if(!empty($data['export'])){
            $list = db('user_running_log')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->join(config('database.prefix').'users ab','a.about_id = ab.id','left')
                ->field('a.*,u.username,u.usernum,ab.username about_user,ab.usernum aboutnum')
                ->order('a.id ASC')
                ->where($where)
                ->select();
            if(empty($list))
                $list = [];
            $list2 = [];
            foreach ($list as $k=>$v){
                $list2[$k]['id'] = $v['id'];
                $list2[$k]['username'] = $v['usernum'] . '【' . $v['username'] . '】';
                if(empty($v['about_user'])){
                    //如果相关用户是管理员
                    $ab_id = abs($v['about_id']);
                    $ab_user = Db::name('admin')->where(['admin_id' => $ab_id])->value('username');
                    $list2[$k]['about_user'] = $ab_user;
                }else{
                    $list2[$k]['about_user'] = $v['aboutnum'] . '【' . $v['about_user'] . '】';

                }
                $list2[$k]['running_type'] = $running_type[$v['running_type']];
                $list2[$k]['change_num'] = $v['change_num'];
                $list2[$k]['balance'] = $v['balance'];
                $list2[$k]['create_time'] = date('Y-m-d',$v['create_time']);
                $list2[$k]['remark'] = $v['remark'];


            }
            if($data['account_type'] == 1){
                $file_name = '财务流水-沙特链';
            }elseif($data['account_type'] == 2){
                $file_name = '财务流水-激活钱包';
            }elseif($data['account_type'] == 3){
                $file_name = '财务流水-消费钱包';
            }elseif($data['account_type'] == 4){
                $file_name = '财务流水-交易账号';
            }elseif($data['account_type'] == 5){
                $file_name = '财务流水-本金账户';
            }
            $headArr = ['id','用户','相关用户','流水类型','变更数量','余额','流水时间','备注'];
            $this->excelExport($file_name, $headArr, $list2);

        }
        if(request()->isPost()){
            if(empty($data['account_type'])){
                return ['code' => 0, 'msg' => '账户类型不能为空'];
            }

            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $list = db('user_running_log')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->join(config('database.prefix').'users ab','a.about_id = ab.id','left')
                ->field('a.*,u.username,u.usernum,ab.username about_user,ab.usernum aboutnum')
                ->order('a.id DESC')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            if(empty($list))
                $list = [];
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['create_time'] = date('Y-m-d',$v['create_time']);
                $list['data'][$k]['running_type'] = $running_type[$v['running_type']];
                $list['data'][$k]['username'] = $v['usernum'] . '【' . $v['username'] . '】';
                if(empty($v['about_user'])){
                    //如果相关用户是管理员
                    $ab_id = abs($v['about_id']);
                    $ab_user = Db::name('admin')->where(['admin_id' => $ab_id])->value('username');
                    $list['data'][$k]['about_user'] = $ab_user;
                }else{
                    $list['data'][$k]['about_user'] = $v['aboutnum'] . '【' . $v['about_user'] . '】';

                }

            }

            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }
        //账户类型
        $currency = db('currency')->select();
        $currency_arr = [];
        foreach ($currency as $key => $val){
            $currency_arr[] = $val['name'];
        }
        //流水类型
        $this->assign('running_type', $running_type);
        $this->assign('currency_arr', $currency_arr);

        return $this->fetch('financialFlow');
    }



    public function makeSearch2($data)
    {
        $where = new Where();
        $where['a.account_type'] = 1;
        $where['a.status'] = 1;
        if(!empty($data['account_type'])){
            $where['a.account_type'] = $data['account_type']; //流水类型
        }

        if(!empty($data['running_type'])){
            $where['a.running_type'] = $data['running_type']; //流水类型
        }
        if(!empty($data['start_time']) && empty($data['end_time'])){
            $start_time = strtotime($data['start_time']);
            $where['a.create_time'] = array('egt', $start_time);
        }
        if(!empty($data['end_time']) && empty($data['start_time'])){
            $end_time = strtotime($data['end_time']);
            $where['a.create_time'] = array('elt',$end_time);
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $start_time = strtotime($data['start_time']);
            $end_time = strtotime($data['end_time']);
            $where['a.create_time'] = array('between time', array($start_time, $end_time));
        }
        if(!empty($data['key'])){
            $where['u.id|u.usernum|u.email|u.mobile|u.username'] = array('like', '%' . $data['key'] . '%');
        }

        return $where;
    }



    private function _export()
    {

    }


    //货币充值
    public function currencyRecharge()
    {
        if(request()->isPost()){
            //如果是充值提交
            $data = input('post.');
            $admin_id = session('aid'); //管理员id
            $data['admin_id'] = $admin_id;
            if(empty($data['currency_id']) || empty($data['user_id']) || empty($data['amount'])){
                return ['code' => 0, 'msg' => '缺少必填项'];
            }

            if($data['amount'] <= 0){
                return ['code' => 0, 'msg' => '输入金额不正确'];
            }


            //用户钱包
            $user_currency_account = db('user_currency_account')->where(['user_id' => $data['user_id']])->find();
            if(empty($user_currency_account)){
                //如果用户不存在钱包
                //创建用户钱包账户
                $sate = db('bonus_ext_set')->where(['id' => 1])->value('money_change');
                $account['user_id'] = $data['user_id'];
                $account['rate'] = $sate;
                if($data['currency_id'] == 1){
                    $account['cash_currency_num'] = $data['amount'];
                }elseif ($data['currency_id'] == 2){
                    $account['activation_num'] = $data['amount'];
                }elseif ($data['currency_id'] == 3){
                    $account['consume_num'] = $data['amount'];
                }elseif ($data['currency_id'] == 4){
                    $account['transaction_num'] = $data['amount'];
                }elseif ($data['currency_id'] == 5){
                    $account['corpus'] = $data['amount'];
                }
                $blance = $data['amount'];
                $res = UserCurrencyAccount::create($account);
            }else{
                //加上用户之前的余额
                if($data['currency_id'] == 1){
                    $blance =  $account['cash_currency_num']  = bcadd($user_currency_account['cash_currency_num'], $data['amount'], 4);
                }elseif ($data['currency_id'] == 2){
                    $blance = $account['activation_num']      = bcadd($user_currency_account['activation_num'], $data['amount'], 4);
                }elseif ($data['currency_id'] == 3){
                    $blance = $account['consume_num']         = bcadd($user_currency_account['consume_num'], $data['amount'], 4);
                }elseif ($data['currency_id'] == 4){
                    $blance = $account['transaction_num']     = bcadd($user_currency_account['transaction_num'], $data['amount'], 4);
                }elseif ($data['currency_id'] == 5){
                    $blance = $account['corpus']              = bcadd($user_currency_account['corpus'], $data['amount'], 4);
                }
                $res = UserCurrencyAccount::where('id',$user_currency_account['id'])->update($account);

            }
            Db::startTrans();
            if($res === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '充值失败请重试'];
            }else{
                //添加充值记录
               $res2 =  UserRunningLog::create([
                    'user_id'  =>  $data['user_id'],
                    'about_id' =>  -session('aid'),
                    'running_type'  => UserRunningLog::TYPE1,
                    'account_type'  => $data['currency_id'],
                    'change_num'    => $data['amount'],
                    'balance'       =>  $blance,
                    'create_time'   => time(),
                    'remark'        => $data['remark']
                ]);
               if($res2 !== false){
                   Db::commit();
                   return ['code' => 1, 'msg' => '充值成功'];
               }else{
                   Db::rollback();
                   return ['code' => 0, 'msg' => '充值失败请重试'];
               }

            }

        }else{
            if(request()->isGet()){
                $id = input('get.id');
                $type = input('get.type');
                $user = db('users')->where(['id' => $id])->find();
                $this->assign('user',$user);
                $this->assign('type',$type);
            }else{
                $this->assign('user', '');
                $this->assign('type', '');
            }
            //获取币种类型
            $currency_model = model('currency');
            $currency_list = $currency_model->select();
            $this->assign('currency_list', $currency_list);

            return $this->fetch('currencyRecharge');

        }

    }

    //货币扣除
    public function currencyDeduction()
    {
        if(request()->isPost()){
            $data = input('post.');
            $admin_id = session('aid'); //管理员id
            $data['admin_id'] = $admin_id;
            if(empty($data['currency_id']) || empty($data['user_id']) || empty($data['amount'])){
                return ['code' => 0, 'msg' => '缺少必填项'];
            }
            if($data['amount'] <= 0){
                return ['code' => 0, 'msg' => '输入金额不正确'];
            }
            //用户钱包
            $user_currency_account = db('user_currency_account')->where(['user_id' => $data['user_id']])->find();
            if(empty($user_currency_account)){
                //如果用户不存在钱包
                //创建用户钱包账户
                $sate = db('bonus_ext_set')->where(['id' => 1])->value('money_change');
                $account['user_id'] = $data['user_id'];
                $account['rate'] = $sate;
                $blance = '0.0000';
                UserCurrencyAccount::create($account);
                return ['code' => 0, 'msg' => '用户所剩余额不足'];
            }else{
                if($data['currency_id'] == 1){
                    if(bccomp($data['amount'], $user_currency_account['cash_currency_num'], 4) == 1){
                        return ['code' => 0, 'msg' => '扣除数量大于账户余额'];
                    }
                    $blance =  $account['cash_currency_num']  = bcsub($user_currency_account['cash_currency_num'], $data['amount'], 4);
                }elseif ($data['currency_id'] == 2){
                    if(bccomp($data['amount'], $user_currency_account['activation_num'], 4) == 1){
                        return ['code' => 0, 'msg' => '扣除数量大于账户余额'];
                    }
                    $blance = $account['activation_num']      = bcsub($user_currency_account['activation_num'], $data['amount'], 4);
                }elseif ($data['currency_id'] == 3){
                    if(bccomp($data['amount'], $user_currency_account['consume_num'], 4) == 1){
                        return ['code' => 0, 'msg' => '扣除数量大于账户余额'];
                    }
                    $blance = $account['consume_num']         = bcsub($user_currency_account['consume_num'], $data['amount'], 4);
                }elseif ($data['currency_id'] == 4){
                    if(bccomp($data['amount'], $user_currency_account['transaction_num'], 4) == 1){
                        return ['code' => 0, 'msg' => '扣除数量大于账户余额'];
                    }
                    $blance = $account['transaction_num']     = bcsub($user_currency_account['transaction_num'], $data['amount'], 4);
                }elseif ($data['currency_id'] == 5){
                    if(bccomp($data['amount'], $user_currency_account['corpus'], 4) == 1){
                        return ['code' => 0, 'msg' => '扣除数量大于账户余额'];
                    }
                    $blance = $account['corpus']              = bcsub($user_currency_account['corpus'], $data['amount'], 4);
                }
                $res = UserCurrencyAccount::where('id',$user_currency_account['id'])->update($account);
                Db::startTrans();
                if($res === false){
                    Db::rollback();
                    return ['code' => 0, 'msg' => '扣除失败请重试'];
                }else{
                    //添加扣除记录
                    $res2 =  UserRunningLog::create([
                        'user_id'  =>  $data['user_id'],
                        'about_id' =>  -session('aid'),
                        'running_type'  => UserRunningLog::TYPE2,
                        'account_type'  => $data['currency_id'],
                        'change_num'    => -$data['amount'],
                        'balance'       => $blance,
                        'create_time'   => time(),
                        'remark'        => $data['remark']
                    ]);
                    if($res2 !== false){
                        Db::commit();
                        return ['code' => 1, 'msg' => '扣除成功'];
                    }else{
                        Db::rollback();
                        return ['code' => 0, 'msg' => '扣除失败请重试'];
                    }

                }

            }

        }else{
            //获取币种类型
            $currency_model = model('currency');
            $currency_list = $currency_model->select();
            $this->assign('currency_list', $currency_list);
            return $this->fetch('currencyDeduction');
        }

    }


    //申请充值列表
    public function applicationRecharge()
    {
        $status = ApplyRecharge::$status;
        $data = Request::param();
        $where  = $this->applySearch($data);
        if(!empty($data['export'])){
            //申请列表
            $list = db('user_apply_active')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->field('a.*,u.usernum,u.username')
                ->where($where)
                ->order('a.id ASC')
                ->select();
            if(empty($list))
                $list = [];
            $list2 = [];
            foreach ($list as $k=>$v){
                $list2[$k]['id'] = $v['id'];
                $list2[$k]['usernum'] = $v['usernum'];
                $list2[$k]['username'] = $v['username'];
                $list2[$k]['amount'] = $v['amount'];
                $list2[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $list2[$k]['status'] = UserApplyActive::$status[$v['status']];
            }
            $file_name = '申请充值';

            $headArr = ['id','用户编号','用户名','充值金额($)','申请时间','状态'];
            $this->excelExport($file_name, $headArr, $list2);
        }
        //获取用户申请充值列表
        if(request()->isPost()){

            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');

            //申请列表
            $list = db('user_apply_active')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->field('a.*,u.usernum,u.username')
                ->where($where)
                ->order('a.id DESC')
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['status'] = UserApplyActive::$status[$v['status']];
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }

        $this->assign('status', $status);
        return $this->fetch('applyRechargeList');
    }

    //拒绝充值激活钱包申请
    public function declineActive()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['id'])){
                return ['code' => 0, 'msg' => '操作失败'];
            }
            $save = [
                'reason' => $data['reason'],
                'status' => 3
            ];
            $res = UserApplyActive::where(['id' => $data['id']])->update($save);
            if($res === false){
                return ['code' => 0, 'msg' => '操作失败请重试'];
            }
            return ['code' => 1, 'msg' => '操作成功'];
        }else{
            return ['code' => 0, 'msg' => '操作失败'];
        }
    }

    //充值批准
    public function sureActive()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['id'])){
                return ['code' => 0, 'msg' => '操作失败'];
            }
            $save = [
                'status' => 2
            ];
            $active_info =  Db::name('user_apply_active')->where(['id' => $data['id']])->find();
            Db::startTrans();
            $res = Db::name('user_apply_active')->where(['id' => $data['id']])->update($save);
            if($res === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败请重试'];
            }
            //更新用户激活钱包
            $user_active_account = UserCurrencyAccount::where(['user_id' => $active_info['user_id']])->find();
            $active_num = bcadd($user_active_account['activation_num'], $active_info['amount'],4);

            $res2 = UserCurrencyAccount::where(['user_id' => $active_info['user_id']])->update(['activation_num' => $active_num]);
            if($res2 === false){
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败请重试'];
            }
            Db::commit();
            UserRunningLog::create([
                'user_id'  =>  $active_info['user_id'],
                'about_id' =>  $active_info['user_id'],
                'running_type'  => UserRunningLog::TYPE1,
                'account_type'  => 2,
                'change_num'    => $active_info['amount'],
                'balance'       => $active_num,
                'create_time'   => time(),
                'remark'        => '激活钱包充值'
            ]);


            return ['code' => 1, 'msg' => '操作成功'];
        }else{
            return ['code' => 0, 'msg' => '操作失败'];
        }
    }


    public function applySearch($data)
    {
        $where = new Where();

        if(!empty($data['status'])){
            $where['a.status'] = $data['status'];
        }
        if(!empty($data['start_time']) && empty($data['end_time'])){
            $start_time = strtotime($data['start_time'] . ' 00:00:00');
            $where['a.create_time'] = array('egt', $start_time);
        }
        if(!empty($data['end_time']) && empty($data['start_time'])){
            $end_time = strtotime($data['end_time'] . ' 23:59:59');
            $where['a.create_time'] = array('elt',$end_time);
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $start_time = strtotime($data['start_time'] . ' 00:00:00');
            $end_time = strtotime($data['end_time'] . ' 23:59:59');
            $where['a.create_time'] = array('between time', array($start_time, $end_time));
        }
        if(!empty($data['key'])){
            $where['u.id|u.email|u.mobile|u.username'] = array('like', '%' . $data['key'] . '%');
        }
        return $where;
    }

    //搜索
    public function makeSearch($data)
    {
        $where = new Where();
        $where['a.del'] = 1;
        if(!empty($data['status'])){
            $where['a.status'] = $data['status'];
        }
        if(!empty($data['start_time']) && empty($data['end_time'])){
            $start_time = strtotime($data['start_time'] . ' 00:00:00');
            $where['a.create_time'] = array('egt', $start_time);
        }
        if(!empty($data['end_time']) && empty($data['start_time'])){
            $end_time = strtotime($data['end_time'] . ' 23:59:59');
            $where['a.create_time'] = array('elt',$end_time);
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $start_time = strtotime($data['start_time'] . ' 00:00:00');
            $end_time = strtotime($data['end_time'] . ' 23:59:59');
            $where['a.create_time'] = array('between time', array($start_time, $end_time));
        }
        if(!empty($data['key'])){
            $where['u.id|u.email|u.mobile|u.username'] = array('like', '%' . $data['key'] . '%');
        }
        return $where;
    }

    //申请详情
    public function applyDetail()
    {


    }


    //申请充值批量删除
    public function applyReachrgeDelall()
    {

    }

    //申请提现列表
    public function applicationForCash()
    {
        $status = ApplyCash::$status;
        $data = Request::param();
        $where  = $this->makeSearch($data);
        if(!empty($data['export'])){
            $this->_exportCash($where, $data);
        }
        //获取用户申请充值列表
        if(request()->isPost()){
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            if($data['type'] == 1){
                //沙特链申请列表
                $list = db('user_apply_shate_cash')
                    ->alias('a')
                    ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                    ->field('a.*,u.usernum,u.username,u.id user_id,u.alipay_account')
                    ->where($where)
                    ->order('id DESC')
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['status_str'] = UserApplyShateCash::$status[$v['status']];
                    $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                    $list['data'][$k]['cash_method'] = UserApplyShateCash::$cash_method[$v['cash_method']];
                    $list['data'][$k]['type'] = $data['type'];
                }
                return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

            }

            if($data['type'] == 2){
                //消费钱包申请列表
                $list = db('user_apply_consume_cash')
                    ->alias('a')
                    ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                    ->field('a.*,u.usernum,u.username,u.id user_id,u.alipay_account')
                    ->where($where)
                    ->order('id DESC')
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['status_str'] = UserApplyShateCash::$status[$v['status']];
                    $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                    $list['data'][$k]['cash_method'] = UserApplyShateCash::$cash_method[$v['cash_method']];
                    $list['data'][$k]['type'] = $data['type'];
                }
                return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
            }

            if($data['type'] == 3){
                //交易账号申请列表
                $list = db('user_apply_trade_cash')
                    ->alias('a')
                    ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                    ->field('a.*,u.usernum,u.username,u.id user_id,u.alipay_account')
                    ->where($where)
                    ->order('id DESC')
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['status_str'] = UserApplyShateCash::$status[$v['status']];
                    $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                    $list['data'][$k]['cash_method'] = UserApplyShateCash::$cash_method[$v['cash_method']];
                    $list['data'][$k]['type'] = $data['type'];
                }
                return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
            }

        }
        $currency = db('currency')->select();
        $currency_arr = [];
        foreach ($currency as $key => $val){
            $currency_arr[] = $val['name'];
        }
        $this->assign('status', $status);
        $this->assign('currency_arr', $currency_arr);
        return $this->fetch('applicationForCash');
    }

    //提现申请导出
    private function _exportCash($where, $data)
    {
        if($data['type'] == 1){
            //沙特链申请列表
            $list = db('user_apply_shate_cash')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->field('a.*,u.usernum,u.username,u.id user_id,u.alipay_account')
                ->where($where)
                ->order('id DESC')
                ->select();

            $list2 = [];
            foreach ($list as $k => $v){
                $list2[$k]['id'] = $v['id'];
                $list2[$k]['usernum'] = $v['usernum'];
                $list2[$k]['username'] = $v['username'];
                $list2[$k]['cash_sum'] = $v['cash_sum'];
                $list2[$k]['cash_method'] = UserApplyShateCash::$cash_method[$v['cash_method']];
                $list2[$k]['poundage'] = $v['poundage'];
                $list2[$k]['real_sum'] = $v['real_sum'];
                $list2[$k]['alipay_account'] = $v['alipay_account'];
                $list2[$k]['status_str'] = UserApplyShateCash::$status[$v['status']];
                $list2[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

            }
            $file_name = '申请提现-沙特链';
            $headArr = ['id','用户编号','用户名','提现金额($)','提现方式','手续费','到账金额','支付宝账户','状态','申请时间'];
            $this->excelExport($file_name, $headArr, $list2);

        }

        if($data['type'] == 2){
            //消费钱包申请列表
            $list = db('user_apply_consume_cash')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->field('a.*,u.usernum,u.username,u.id user_id,u.alipay_account')
                ->where($where)
                ->order('id DESC')
                ->select();

            $list2 = [];
            foreach ($list as $k => $v){
                $list2[$k]['id'] = $v['id'];
                $list2[$k]['usernum'] = $v['usernum'];
                $list2[$k]['username'] = $v['username'];
                $list2[$k]['cash_sum'] = $v['cash_sum'];
                $list2[$k]['cash_method'] = UserApplyShateCash::$cash_method[$v['cash_method']];
                $list2[$k]['poundage'] = $v['poundage'];
                $list2[$k]['real_sum'] = $v['real_sum'];
                $list2[$k]['alipay_account'] = $v['alipay_account'];
                $list2[$k]['status_str'] = UserApplyShateCash::$status[$v['status']];
                $list2[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

            }
            $file_name = '申请提现-消费钱包';
            $headArr = ['id','用户编号','用户名','提现金额($)','提现方式','手续费','到账金额','支付宝账户','状态','申请时间'];
            $this->excelExport($file_name, $headArr, $list2);
        }

        if($data['type'] == 3){
            //交易账号申请列表
            $list = db('user_apply_trade_cash')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->field('a.*,u.usernum,u.username,u.id user_id,u.alipay_account')
                ->where($where)
                ->order('id DESC')
                ->select();

            $list2 = [];
            foreach ($list as $k => $v){
                $list2[$k]['id'] = $v['id'];
                $list2[$k]['usernum'] = $v['usernum'];
                $list2[$k]['username'] = $v['username'];
                $list2[$k]['cash_sum'] = $v['cash_sum'];
                $list2[$k]['cash_method'] = UserApplyShateCash::$cash_method[$v['cash_method']];
                $list2[$k]['poundage'] = $v['poundage'];
                $list2[$k]['real_sum'] = $v['real_sum'];
                $list2[$k]['alipay_account'] = $v['alipay_account'];
                $list2[$k]['status_str'] = UserApplyShateCash::$status[$v['status']];
                $list2[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

            }
            $file_name = '申请提现-交易账号';
            $headArr = ['id','用户编号','用户名','提现金额','提现方式','手续费','到账金额','支付宝账户','状态','申请时间'];
            $this->excelExport($file_name, $headArr, $list2);
        }

    }

    //提现申请删除
    public function applyCashDel()
    {
        $data = input('post.');
        if(!empty($data['type']) && !empty($data['id'])){
            if($data['type'] == 1){
                Db::name('user_apply_shate_cash')
                    ->update(['del' => 0, 'id' => $data['id']]);
            }elseif ($data['type'] == 2){
                Db::name('user_apply_consume_cash')
                    ->where('id', $data['id'])
                    ->update(['del' => 0]);
            }elseif ($data['type'] == 3){
                Db::name('user_apply_trade_cash')
                    ->where('id', $data['id'])
                    ->update(['del' => 0]);
            }
            return $result = ['code'=>1,'msg'=>'删除成功!'];

        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }
    }

    //拒绝 要将用户的余额返回
    public function decline()
    {
        if(request()->isPost()){
            $data= input('post.');
            if(!empty($data['type']) && !empty($data['id'])){
                if($data['type'] == 1){
                    $res = Db::name('user_apply_shate_cash')
                        ->where('id', $data['id'])
                        ->update(['status' => 3, 'reason' => $data['reason']]);

                    if(false === $res) {
                        return ['code' => 0, 'msg' => '操作失败'];
                    }
                    //
                    $tarde_info = UserApplyShateCash::where('id',$data['id'])->find();
                    $cash_currency_num = UserCurrencyAccount::where(['user_id' => $tarde_info['user_id']])->value('cash_currency_num');
                    ;
                    $cash_currency_num2 = bcadd($tarde_info['cash_num'],$cash_currency_num,4);
                    $res2 = UserCurrencyAccount::where(['user_id' => $tarde_info['user_id']])->update(['cash_currency_num' => $cash_currency_num2]);
                    if(false === $res2){
                        return ['code' => 0, 'msg' => '操作失败'];
                    }
                }elseif ($data['type'] == 2){
                    $res = Db::name('user_apply_consume_cash')
                        ->where('id', $data['id'])
                        ->update(['status' => 3, 'reason' => $data['reason']]);

                    if(false === $res) {
                        return ['code' => 0, 'msg' => '操作失败'];
                    }
                    $tarde_info =  UserApplyConsumeCash::where('id',$data['id'])->find();
                    $consume_num = UserCurrencyAccount::where(['user_id' => $tarde_info['user_id']])->value('consume_num');
                    $cash_currency_num2 = bcadd($tarde_info['cash_num'],$consume_num,4);
                    $res2 = UserCurrencyAccount::where(['user_id' => $tarde_info['user_id']])->update(['consume_num' => $cash_currency_num2]);
                    if(false === $res2){
                        return ['code' => 0, 'msg' => '操作失败'];
                    }
                }elseif ($data['type'] == 3){
                    $res = Db::name('user_apply_trade_cash')
                        ->where('id', $data['id'])
                        ->update(['status' => 3, 'reason' => $data['reason']]);

                    if(false === $res) {
                        return ['code' => 0, 'msg' => '操作失败'];
                    }
                    $tarde_info = UserApplyTradeCash::where('id',$data['id'])->value('cash_sum');
                    $transaction_num = UserCurrencyAccount::where(['user_id' => $tarde_info['user_id']])->value('transaction_num');
                    $cash_currency_num2 = bcadd($tarde_info['cash_num'],$transaction_num,2);
                    $res2 = UserCurrencyAccount::where(['user_id' => $tarde_info['user_id']])->update(['transaction_num' => $cash_currency_num2]);
                    if(false === $res2){
                        return ['code' => 0, 'msg' => '操作失败'];
                    }

                }
                return ['code' => 1, 'msg' => '操作成功'];
            }else{
                return ['code' => 0, 'msg' => '非法请求'];
            }
        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }

    }

    //提现详情
    public function cashDetail()
    {
        $user_id = input('user_id');
        if(empty($user_id)){
            return ['code' => 0, 'msg' => '非法请求'];
        }
        //查询用户信息
        $user_info = UsersModel::get($user_id);
        $bank_name = db('bank')->where(['id' => $user_info['bank_id']])->value('bank_name');
        $user_info['bank_name'] = $bank_name;
        return ['code' => 1, 'info' => $user_info];

    }

    //提现批量批准
    public function mulApprove()
    {
        if(request()->isPost()){
            $data = input('post.');

            $map[] =array('id','IN',$data['ids']);
            if(!empty($data['type']) && !empty($data['ids'])){
                if($data['type'] == 1){
                    $res = Db::table(config('database.prefix').'user_apply_shate_cash')
                        ->where($map)
                        ->update(['status' => 2]);

                    if(false === $res) {
                        return ['code' => 0, 'msg' => '操作失败'];
                    }

                    //流水记录生效
                    $map2[] =array('order_id','IN',$data['ids']);
                    $map2[] =array('account_type','=', 1);
                    UserRunningLog::where($map2)->update(['status' => 1]);

                }elseif ($data['type'] == 2){
                    $res = Db::table(config('database.prefix').'user_apply_consume_cash')
                        ->where($map)
                        ->update(['status' => 2]);
                    if(false === $res) {
                        return ['code' => 0, 'msg' => '操作失败'];
                    }

                    //流水记录生效
                    $map2[] =array('order_id','IN',$data['ids']);
                    $map2[] =array('account_type','=', 3);
                    UserRunningLog::where($map2)->update(['status' => 1]);
                }elseif ($data['type'] == 3){
                    $res = Db::table(config('database.prefix').'user_apply_trade_cash')
                        ->where($map)
                        ->update(['status' => 2]);
                    if(false === $res) {
                        return ['code' => 0, 'msg' => '操作失败'];
                    }

                    //流水记录生效
                    $map2[] =array('order_id','IN',$data['ids']);
                    $map2[] =array('account_type','=', 4);
                    UserRunningLog::where($map2)->update(['status' => 1]);

                }
                return $result = ['code'=>1,'msg'=>'操作成功!'];

            }else{
                return ['code' => 0, 'msg' => '非法请求'];
            }
        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }
    }

    //提现申请批量删除
    public function applyCashDelall()
    {
        if(request()->isPost()){
            $data = input('post.');

            $map[] =array('id','IN',$data['ids']);
            if(!empty($data['type']) && !empty($data['ids'])){
                if($data['type'] == 1){
                    Db::table(config('database.prefix').'user_apply_shate_cash')
                        ->where($map)
                        ->update(['del' => 0]);
                }elseif ($data['type'] == 2){
                    Db::table(config('database.prefix').'user_apply_consume_cash')
                        ->where($map)
                        ->update(['del' => 0]);
                }elseif ($data['type'] == 3){
                    Db::table(config('database.prefix').'user_apply_trade_cash')
                        ->where($map)
                        ->update(['del' => 0]);
                }
                return $result = ['code'=>1,'msg'=>'删除成功!'];

            }else{
                return ['code' => 0, 'msg' => '非法请求'];
            }
        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }

    }

    //货币充值/扣除根据条件搜索用户
    public function searchUser()
    {
        try{
            if(request()->isPost()){
                $key = input('post.key');
                if(empty($key))
                    throw new Exception('请输入搜索条件');
                $list=db('users')
                    ->where('id|usernum|email|mobile|username','like',"%".$key."%")
                    ->order('id desc')
                    ->select();
                if(empty($list))
                    $list = [];
                return ['code' => 1, 'list' => $list];
            }else{
                throw new Exception('请求出错');
            }
        }catch (Exception $e){
            return ['code' => 0, 'msg' => $e->getMessage()];
        }

    }
    

}