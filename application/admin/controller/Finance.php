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
use app\user\model\UserRunningLog;
use think\Db;
use think\db\Where;
use think\Exception;
use think\facade\Request;
class Finance extends Common
{
    //动态奖金转阿美币列表
    public function convert()
    {
        //获取用户动态转阿美币的列表
        if(request()->isPost()){
            $data   = input('post.');
            $where  = $this->convertSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');

            $list = db('user_dynamic_amei_bonus')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->field('a.*,u.usernum,u.username')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['status'] = UserDynamicAmeiBonus::$status[$v['status']];
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }

        return $this->fetch('convert');
    }

    //搜索条件
    public function convertSearch($data)
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
        
    }

    //财务流水列表
    public function financialFlow()
    {
        $running_type = UserRunningLog::$running_type;
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['account_type'])){
                return ['code' => 0, 'msg' => '账户类型不能为空'];
            }
            $where  = $this->makeSearch2($data);
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

        return $this->fetch('financialFlow');
    }

    public function makeSearch2($data)
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



    //货币充值
    public function currencyRecharge()
    {
        //待解决用户币种余额问题
        if(request()->isPost()){
            //如果是充值提交
            $data = input('post.');
            $admin_id = session('aid'); //管理员id
            $data['admin_id'] = $admin_id;
            if(CurrencyRecharge::create($data)){
                return ['code' => 1, 'msg' => '充值成功'];
            }else{
                return ['code' => 0, 'msg' => '充值失败'];
            }
        }else{
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
        //待解决用户币种余额问题 扣除不能大于用户所拥有的最大值
        if(request()->isPost()){
            $data = input('post.');
            $admin_id = session('aid'); //管理员id
            $data['admin_id'] = $admin_id;
            if(CurrencyRecharge::create($data)){
                return ['code' => 1, 'msg' => '扣除成功'];
            }else{
                return ['code' => 0, 'msg' => '扣除失败'];
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
        //获取用户申请充值列表
        if(request()->isPost()){
            $data   =input('post.');
            $where  = $this->makeSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $list = db('apply_recharge')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->field('a.*,u.mobile,u.username')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->order('a.id desc')
                ->toArray();
            $cash_method = ApplyRecharge::$recharge_method;
            array_walk($list, function (&$v) use($status,$cash_method) {
                $v['status_str']  = $status[$v['status']];
                $v['apply_method'] = $cash_method[$v['recharge_method']];
            });
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }

        $this->assign('status', $status);
        return $this->fetch('applyRechargeList');
    }

    //搜索
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

    //申请详情
    public function applyDetail()
    {


    }

    //申请充值单个删除
    public function applyDel()
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
        //获取用户申请充值列表
        if(request()->isPost()){
            $data   =input('post.');
            $where  = $this->makeSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $list = db('user_apply_cash')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->field('a.*,u.mobile,u.username,u.alipay')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->order('a.id desc')
                ->toArray();
            $cash_method = ApplyRecharge::$recharge_method;
            array_walk($list, function (&$v) use($status,$cash_method) {
                $v['status_str']  = $status[$v['status']];
                $v['cash_method'] = $cash_method[$v['cash_method']];
            });
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

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

    //提现申请删除
    public function applyCashDel()
    {

    }

    //提现申请批量删除
    public function applyCashDelall()
    {

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
                    ->where('id|email|mobile|username','like',"%".$key."%")
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