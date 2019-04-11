<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/23 0023
 * Time: 19:34
 */
namespace app\user\controller;

use app\admin\model\ApplyCash;
use app\admin\model\CurrencyList;
use app\admin\model\UserCurrencyAccount;
use app\user\model\UserApplyCash;
use app\user\model\UserApplyConsumeCash;
use app\user\model\UserApplyShateCash;
use app\user\model\UserCurrencyList;
use app\user\model\UserDynamicAmeiBonus;
use app\user\model\UserRunningLog;
use app\user\model\Users;
use think\Db;
use think\db\Where;
use think\Request;

class Finance extends Common{
    public function initialize(){
        parent::initialize();


    }

    //财务流水
    public function runningAccount()
    {
        $running_type = UserRunningLog::$running_type;
        if(request()->isPost()){
            $data = input('post.');
            $user_id = session('user.id');
            if(empty($data['account_type']) || empty($user_id)){
                return ['code' => 0, 'msg' => '账户类型或用户不能为空'];
            }
            $data['user_id'] = $user_id;
            $where  = $this->makeSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $list = db('user_running_log')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->join(config('database.prefix').'users ab','a.about_id = ab.id','left')
                ->field('a.*,u.username,ab.username about_user')
                ->where($where)
                ->order('a.id DESC')
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            if(empty($list))
                $list = [];
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['create_time'] = date('Y-m-d',$v['create_time']);
                $list['data'][$k]['running_type'] = $running_type[$v['running_type']];

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

        return $this->fetch('runningAccount');
    }

    public function makeSearch($data)
    {
        $where = new Where();
        $where['a.account_type'] = 1;
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
            $where['a.id|a.email|a.mobile|a.username'] = array('like', '%' . $data['key'] . '%');
        }

        return $where;
    }

    //币种转换
    public function currencyConversion()
    {
        $user_id = session('user.id');
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['change_currency'])){
                return ['code' => 0, 'msg' => '转换类型不能为空'];
            }
            if(empty($data['change_num'])){
                return ['code' => 0, 'msg' => '转换数量不能为空'];
            }
            if((int)$data['change_num'] > (int)$data['cash_currency_num']){
                return ['code' => 0, 'msg' => '转换数量不能超过刷过沙特链余额'];
            }

            //默认现金币转换阿美币
            $user_accpunt = UserCurrencyAccount::where(['user_id' => $user_id])->find();
            $cash_num = bcsub($user_accpunt['cash_currency_num'], $data['change_num'],4); //减去转换的现金币
            $ameibi_num = bcmul($data['change_num'], $user_accpunt['rate'], 4);
            $save = [
                'cash_currency_num' => $cash_num,
            ];
            $res = UserCurrencyAccount::where(['user_id' => $user_id])->update($save); //更新用户钱包
            $currency_id = CurrencyList::where(['en_name' => 'AMB'])->value('id');
            //更新用户ameibi数量
            $user_currency = UserCurrencyList::where(['user_id' => $user_id,'currency_id' => $currency_id])->find();
            if(empty($user_currency)) {
                $currency = [
                    'user_id'       => $user_id,
                    'currency_id'   => $currency_id,
                    'num'           => $ameibi_num,
                ];
                UserCurrencyList::create($currency);

            }else{
                $num = bcadd($user_currency['num'], $ameibi_num, 4);
                //添加用户币种列表
                $currency = [
                    'num' => $num,
                ];
                UserCurrencyList::where(['user_id' => $user_id,'currency_id' => $currency_id])->update($currency);

            }

            if($res !== false){
                //现金币转阿美币记录
                UserRunningLog::create([
                    'user_id'  =>  $user_id,
                    'about_id' =>  $user_id,
                    'running_type'  => UserRunningLog::TYPE25,
                    'account_type'  => 1,
                    'change_num'    => -$data['change_num'],
                    'balance'       => $cash_num,
                    'create_time'   => time()
                ]);

                $currency = CurrencyList::where(['en_name' => 'AMB'])->find();

                //添加币种流水记录
                $add = [
                    'user_id'       => $user_id,
                    'about_id'      => $user_id,
                    'running_type'  => UserRunningLog::TYPE25,
                    'currency_to'   => $currency['id'],
                    'change_num'    => $ameibi_num,
                    'create_time'   => time(),
                    'remark'        => '现金币转换阿美币,阿美币增加',
                ];

                Db::name('currency_running_log')->insert($add);
                return ['code' => 1, 'msg' => '转换成功'];

            }else{
                return ['code' => 0, 'msg' => '装换失败请重试'];
            }



            //





        }

        //查询用户钱包
        $user_purse = db('user_currency_account')->where(['user_id' => $user_id])->find();
        if(empty($user_purse)){
            $user_purse ['cash_currency_num'] = 0.0000; //沙特链余额
            $user_purse ['corpus'] = 0.0000;            //用户本金账户余额
            $user_purse ['consume_num'] = 0.0000;       //消费钱包余额
            $user_purse ['activation_num'] = 0.0000;    //激活钱包余额
            $user_purse ['transaction_num'] = 0.0000;   //交易钱包余额
        }
        $principal_recall = db('bonus_ext_set')->where(['id' => 1])->field('principal_recall')->find();
        $change_currency[1] = '沙特链转换100%阿美币';
        $this->assign('change_currency', $change_currency);
        $this->assign('user_purse', $user_purse);
        $this->assign('principal_recall', $principal_recall['principal_recall']);

        return $this->fetch('currencyConversion');
    }
    
    //提现管理
    public function cashManagement()
    {
        if(request()->isPost()){
            $user_id = session('user.id');
            $data = input('post.');
            if(empty($user_id) || empty($data['type'])){
                return ['code' => 0, 'msg' => '非法请求'];
            }
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            if($data['type'] == 1){
                //沙特链申请列表
                $where['a.user_id'] = $user_id;
                $list = db('user_apply_shate_cash')
                    ->alias('a')
                    ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                    ->field('a.*,u.usernum,u.username')
                    ->where($where)
                    ->order('id DESC')
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['status'] = UserApplyShateCash::$status[$v['status']];
                    $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                    $list['data'][$k]['cash_method'] = UserApplyShateCash::$cash_method[$v['cash_method']];
                }
                return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

            }

            if($data['type'] == 2){
                //消费钱包申请列表
                $where['a.user_id'] = $user_id;
                $list = db('user_apply_consume_cash')
                    ->alias('a')
                    ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                    ->field('a.*,u.usernum,u.username')
                    ->where($where)
                    ->order('id DESC')
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['status'] = UserApplyShateCash::$status[$v['status']];
                    $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                    $list['data'][$k]['cash_method'] = UserApplyShateCash::$cash_method[$v['cash_method']];
                }
                return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
            }

        }

        //账户类型
        $currency = db('currency')->select();
        $currency_arr = [];
        foreach ($currency as $key => $val){
            $currency_arr[] = $val['name'];
        }

        $status = ApplyCash::$status;
        $this->assign('currency_arr', $currency_arr);
        $this->assign('status', $status);
        return $this->fetch('cashManagement');
        
    }

    //申请提现
    public function applyCash()
    {
        //bonus 设置
        $principal_recall = db('bonus_ext_set')->where(['id' => 1])->field('extract_num,extract_deduction_ratio,waller_excract_ratio,waller_excract_multiple')->find();
        $user_id = session('user.id');
        if(request()->isPost()){
            //如果是提交
            $data = input('post.');
            if(empty($data['user_id']) || empty($data['cash_type']))
                return ['code' => 0, 'msg' => '非法请求'];
            if($data['cash_type'] == 1){
                //1 沙特提交
                if(empty($data['extract_num'])){
                    return ['code' => 0, 'msg' => '提取数量不能为空'];
                }
                if(bccomp($principal_recall['extract_num'],$data['extract_num'],4) == 1){
                    return ['code' => 0, 'msg' => '提取数量不能小于'.$principal_recall['extract_num'].'个'];
                }
                //查询用户沙特链数量
                $user_account = db('user_currency_account')->where(['user_id' => $user_id])->find();
                if(bccomp($data['extract_num'],$user_account['cash_currency_num'],4) == 1){
                    return ['code' => 0, 'msg' => '提取数量不能大于余额'];
                }
                $rate = $user_account['rate'] ? $user_account['rate'] : 7.0;
                $cash_sum = bcmul($data['extract_num'], $rate, 2); //总额
                $lixi = $principal_recall['extract_deduction_ratio']/100;
                $poundage = bcmul($cash_sum, $lixi, 2); //手续费
                $real_sum = bcsub($cash_sum, $poundage, 2); //实际到账
                //加入提现表
                $save = [
                    'user_id' => $user_id,
                    'cash_num' => $data['extract_num'], //提取数量
                    'cash_sum' => $data['extract_num'], //提现金额就是提取数量 $
                    'real_sum' => $real_sum,//到账金额
                    'poundage' => $poundage,//手续费
                    'status'   => 1,
                    'create_time' => time()
                ];
                //用户钱包
                $cash_currency_num = bcsub($user_account['cash_currency_num'], $data['extract_num'], 4);
                $user_account = [
                    'cash_currency_num' => $cash_currency_num
                ];
                Db::startTrans();
                if(UserApplyShateCash::create($save)){
                    //记录log
                    $log = [
                        'user_id'  =>  $user_id,
                        'about_id' =>  $user_id,
                        'running_type'  => UserRunningLog::TYPE5,
                        'account_type'  => 1,
                        'change_num'    => -$poundage,
                        'balance'       => 0.00,
                        'create_time'   => time()
                    ];
                    //用户钱包减少
                    $res = db('user_currency_account')->where(['user_id' => $user_id])->update($user_account);
                    if($res === false){
                        Db::rollback();
                        return ['code' => 0, 'msg' => '申请失败，请重试'];
                    }
                    if(UserRunningLog::create($log)){
                        Db::commit();
                    }else{
                        Db::rollback();
                        return ['code' => 0, 'msg' => '申请失败，请重试'];
                    }
                    return ['code' => 1, 'msg' => '申请成功'];
                }else{
                    Db::rollback();
                    return ['code' => 0, 'msg' => '申请失败，请重试'];
                }


            }elseif($data['cash_type'] == 2){
                //1 消费钱包提交
                if(empty($data['consume_num'])){
                    return ['code' => 0, 'msg' => '提取数量不能为空'];
                }
                if(bccomp($principal_recall['waller_excract_ratio'],$data['consume_num'],4) == 1){
                    return ['code' => 0, 'msg' => '提取数量不能小于'.$principal_recall['extract_num'].'个'];
                }
                //查询用户消费钱包数量
                $user_account = db('user_currency_account')->where(['user_id' => $user_id])->find();
                if(bccomp($data['extract_num'],$user_account['consume_num'],4) == 1){
                    return ['code' => 0, 'msg' => '提取数量不能大于余额'];
                }
                $rate = $user_account['rate'] ? $user_account['rate'] : 7.0;
                $cash_sum = bcmul($data['consume_num'], $rate, 2); //总额
                $lixi = $principal_recall['extract_deduction_ratio']/100;
                $poundage = bcmul($cash_sum, $lixi, 2); //手续费
                $real_sum = bcsub($cash_sum, $poundage, 2); //实际到账
                //加入提现表
                $save = [
                    'user_id' => $user_id,
                    'cash_num' => $data['consume_num'], //提取数量
                    'cash_sum' => $data['consume_num'], //提现金额就是提取数量 $
                    'real_sum' => $real_sum,//到账金额
                    'poundage' => $poundage,//手续费
                    'status'   => 1,
                    'create_time' => time()
                ];
                //用户消费钱包
                $consume_num = bcsub($user_account['consume_num'], $data['consume_num'], 4);
                $user_account = [
                    'consume_num' => $consume_num
                ];

                Db::startTrans();
                if(UserApplyConsumeCash::create($save)){
                    //记录log
                    $log = [
                        'user_id'  =>  $user_id,
                        'about_id' =>  $user_id,
                        'running_type'  => UserRunningLog::TYPE5,
                        'account_type'  => 1,
                        'change_num'    => -$poundage,
                        'balance'       => 0.00,
                        'create_time'   => time()
                    ];
                    //用户钱包减少
                    $res = db('user_currency_account')->where(['user_id' => $user_id])->update($user_account);
                    if($res === false){
                        Db::rollback();
                        return ['code' => 0, 'msg' => '申请失败，请重试'];
                    }
                    if(UserRunningLog::create($log)){
                        Db::commit();
                    }else{
                        Db::rollback();
                        return ['code' => 0, 'msg' => '申请失败，请重试'];
                    }
                    return ['code' => 1, 'msg' => '申请成功'];
                }else{
                    Db::rollback();
                    return ['code' => 0, 'msg' => '申请失败，请重试'];
                }


            }else{
                return ['code' => 0, 'msg' => '非法请求'];
            }

        }
        $type = Request()->param('type');
        if(empty($type)){
            $type = 1;
        }
        //获取用户和账户信息
        $where['a.id'] = $user_id;
        $user_info = db('users')
            ->alias('a')
            ->join(config('database.prefix').'user_currency_account b','a.id = b.user_id','left')
            ->field('a.*,b.cash_currency_num,b.rate,b.consume_num')
            ->where($where)
            ->find();

        //用户可提现的人民币数值
        $cash_currency_num = $user_info['cash_currency_num']; //可提现的沙特数
        $rate = $user_info['rate']; //汇率
        //可提人民币
        $cny_num =  bcmul($cash_currency_num, $rate, 2);

        //账户类型
        $currency = db('currency')->select();
        $currency_arr = [];
        foreach ($currency as $key => $val){
            $currency_arr[] = $val['name'];
        }
        //银行列表
        $bank_list = db('bank')->select();
        //提现方式
        $cash_method = UserApplyShateCash::$cash_method;
        $this->assign('user_info', $user_info);
        $this->assign('cny_num', $cny_num); //可提人民币
        $this->assign('rate', $rate); //该用户汇率
        $this->assign('principal_recall', $principal_recall); //该用户汇率
        $this->assign('currency_arr', $currency_arr); //钱包类型
        $this->assign('bank_list', $bank_list);       //银行列表
        $this->assign('cash_method', $cash_method);       //提现方式
        $this->assign('type', $type);       //访问选择tab
        return $this->fetch('applyCash');

    }

    //报备提现信息
    public function userWithtrawInformation()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['alipay_account']) || empty($data['bank_id']) || empty($data['bank_user']) || empty($data['bank_account']) || empty($data['user_id'])){
                return ['code' => 0, 'msg' => '缺少必填项'];
            }
            $userModel = new Users();
            $res = $userModel->allowField(['alipay_account','bank_id','bank_user','bank_account','bank_desc'])->save($data, ['id' => $data['user_id']]);
            if($res === false){
                return ['code' => 0, 'msg' => '保存失败，请重试'];
            }
            return ['code' => 1, 'msg' => '保存成功'];

        }
        $user_id = session('user.id');
        $user_info = db('users')->where(['id' => $user_id])->find();
        $bank_list = db('bank')->select();
        $this->assign('user_info', $user_info);
        $this->assign('bank_list', $bank_list);
        //判断用户是否报备银行
        if($user_info['is_report'] == 0){
            return $this->fetch('userWithtrawInformationForm');
        }else{
            return $this->fetch('userWithtrawInformation');

        }

    }


    //本金转换
    public function corpusConvert()
    {
        $user_id = session('user_id');
        $principal_recall = db('bonus_ext_set')->where(['id' => 1])->field('principal_recall')->find();

        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['change_num'])){
                return ['code' => 0, 'msg' => '转换数量不能为空'];
            }
            //查询用户消费钱包数量
            $user_account = db('user_currency_account')->where(['user_id' => $user_id])->find();
            if(bccomp($data['change_num'],$user_account['corpus'],4) == 1){
                return ['code' => 0, 'msg' => '提取数量不能大于本金余额'];
            }

            //沙特链余额增加，本金账户余额减少
            $corpus = bcsub($user_account['corpus'], $data['change_num'], 4); //本金账户减去扣除的
            $rate = 1-$principal_recall['principal_recall']/100;
            $real_num = bcmul($data['change_num'], $rate, 4);
            $poundage = bcsub($data['change_num'], $real_num, 4); //手续扣除
            $cash_currency_num = bcadd($user_account['cash_currency_num'], $real_num, 4); //实际装换多少沙特链
            Db::startTrans();
            $res = db('user_currency_account')->where(['user_id' => $user_id])->update(['cash_currency_num' => $cash_currency_num, 'corpus' => $corpus]);
            if($res !== false){
                //记录log
                $log = [
                    'user_id'  =>  $user_id,
                    'about_id' =>  $user_id,
                    'running_type'  => UserRunningLog::TYPE24, //转换手续费
                    'account_type'  => 5,
                    'change_num'    => -$poundage,
                    'balance'       => $cash_currency_num,
                    'create_time'   => time()
                ];
                //用户转换之后失去所有收益，变成无效会员
               $res2 = db('users')->where(['id' => $user_id])->update(['enabled' => 0]);
                if($res2 === false){
                    Db::rollback();
                    return ['code' => 0, 'msg' => '转换失败，请重试'];
                }

                if(UserRunningLog::create($log)){
                    Db::commit();
                }else{
                    Db::rollback();
                    return ['code' => 0, 'msg' => '转换失败，请重试'];
                }
                return ['code' => 1, 'msg' => '转换成功'];
            }else{
                Db::rollback();
                return ['code' => 0, 'msg' => '转换失败，请重试'];
            }

        }

        //查询用户钱包
        $user_purse = db('user_currency_account')->where(['user_id' => $user_id])->find();
        if(empty($user_purse)){
            $user_purse ['cash_currency_num'] = 0.0000; //沙特链余额
            $user_purse ['corpus'] = 0.0000;            //用户本金账户余额
        }

        $change_currency[1] = '本金账户100%转换沙特链';
        $this->assign('change_currency', $change_currency);
        $this->assign('user_purse', $user_purse);
        $this->assign('principal_recall', $principal_recall['principal_recall']); //转换扣除比例

        return $this->fetch('corpusConvert');
    }

    //动态奖转阿美币动态列表
    public function toAmeibiList()
    {
        //获取用户动态转阿美币的列表
        if(request()->isPost()){
            $data   = input('post.');
            $where  = $this->makeSearch2($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');

            $user_id = session('user.id');
            $where['a.user_id'] = $user_id;

            //根绝用户id获取推荐的人员信息
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

        return $this->fetch('toAmeibiList');

    }

    //搜索条件
    public function makeSearch2($data)
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

    //阿美币记录列表
    public function aMeibiLogList()
    {
        return $this->fetch('aMeibiLogList');
    }


}