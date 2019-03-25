<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/23 0023
 * Time: 19:34
 */
namespace app\user\controller;

use app\admin\model\ApplyCash;
use app\user\model\UserApplyCash;
use app\user\model\UserRunningLog;
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

            //记录币种转换日志
//            UserRunningLog::create([
//                'user_id'  =>  $user_id,
//                'about_id' =>  $user_id,
//                'running_type'  => UserRunningLog::TYPE34,
//                'account_type'  => 1,
//                'change_num'    => -$shoxufei,
//                'balance'       => $cash_currency_num,
//                'create_time'   => time()
//            ]);


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
                    'currency_type' => 1, //沙特链
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
                if(UserApplyCash::create($save)){
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
                    'currency_type' => 3, //消费钱包
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
                if(UserApplyCash::create($save)){
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
        $cash_method = UserApplyCash::$cash_method;
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
        $user_id = session('user.id');
        $user_info = db('users')->where(['id' => $user_id])->find();
        $bank_list = db('bank')->select();
        $this->assign('user_info', $user_info);
        $this->assign('bank_list', $bank_list);
        return $this->fetch('userWithtrawInformation');
    }

    //动态奖转阿美币动态列表
    public function toAmeibiList()
    {

    }
    
    //本金转换
    public function corpusConvert()
    {
        
    }

    //阿美币记录列表
    public function aMeibiLogList()
    {}


}