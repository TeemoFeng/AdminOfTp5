<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26 0026
 * Time: 23:11
 */
namespace app\admin\controller;


use app\admin\model\CurrencyList;
use app\user\model\UserCurrencyList;
use app\user\model\UserRunningLog;
use think\Db;
use think\db\Where;
use think\facade\Request;

class Currency extends Common{
    //币种列表
    public function currencyList()
    {
        if(request()->isPost()){
            $key  =input('post.key');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $list = db('currency_list')
                ->where('name|en_name','like',"%".$key."%")
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();;
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['status'] = CurrencyList::$status[$v['status']];
                $list['data'][$k]['trade_status'] = CurrencyList::$status[$v['trade_status']];
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }

        return $this->fetch('currencyList');
    }

    //添加货币
    public function addCurrency()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['name'])){
                return ['code' => 0, 'msg' => '币种名称不能为空'];
            }

            if (empty($data['en_name'])) return ['code' => 0, 'msg' => '英文标示不能为空'];
            $check_user = CurrencyList::get(['en_name' => $data['en_name']]);
            if ($check_user) {
                return $result = ['code' => 0, 'msg' => '该英文标识已经存在'];
            }
            $data['create_time'] = date('Y-m-d H:i:s',time());
            $data['update_time'] = date('Y-m-d H:i:s',time());
            if(CurrencyList::create($data)){
                return ['code' => 1, 'msg' => '创建成功','url' => url('currency/currencyList')];
            }else{
                return ['code' => 0, 'msg' => '创建失败，请重试'];
            }

        }

        return $this->fetch('addCurrency');
    }

    //编辑货币
    public function editCurrency($id = '')
    {
        $table = db('currency_list');
        if(Request::isAjax()) {
            $data = Request::except('file');
            if($table->update($data)!==false) {
                return json(['code' => 1, 'msg' => '修改成功!', 'url' => url('currency/currencyList')]);
            } else {
                return json(array('code' => 0, 'msg' =>'修改失败！'));
            }
        }else{
            $info = $table->find($id);
            $this->assign('info', json_encode($info,true));
            return $this->fetch('editCurrency');

        }
    }

    //删除币种
    public function delCurrency()
    {
        db('currency_list')->delete(['id'=>input('id')]);
        return $result = ['code'=>1,'msg'=>'删除成功!'];
    }


    //用户货币
    public function userCurrency()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['currency_id'])){
                return ['code' => 0, 'msg' => '非法请求'];
            }
            $where = $this->searchWhere($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $list = db('users')
                ->alias('a')
                ->join(config('database.prefix').'user_currency_list b','a.id = b.user_id','left')
                ->field('a.username,a.usernum,a.mobile,b.*')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['status'] = UserCurrencyList::$status[$v['status']];
                $list['data'][$k]['username'] = $v['usernum']. '【' .$v['username'] .'】';
                $list['data'][$k]['name'] = '阿美币';
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }

        //获取币种列表
        $currency_list = db('currency_list')->select();
        $this->assign('currency_list', $currency_list);
        return $this->fetch('userCurrency');
    }

    public function searchWhere($data)
    {
        $where = new Where();
        if(!empty($data['currency_id'])){
            $where['b.currency_id'] = $data['currency_id'];
        }
        if(!empty($data['key'])){
            if(!empty($data['key'])){
                $where['a.id|a.email|a.usernum|a.mobile|a.username'] = array('like', '%' . $data['key'] . '%');
            }
        }
        return $where;
    }

    //币种流水记录[币种转换记录]
    public function currencyRunLog()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['currency_id'])){
                return ['code' => 0, 'msg' => '非法请求'];
            }
            $where = $this->searchWhere2($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $list = db('currency_running_log')
                ->alias('a')
                ->join(config('database.prefix').'users b','a.user_id = b.id','left')
                ->join(config('database.prefix').'users c','a.about_id = c.id','left')
                ->join(config('database.prefix').'currency_list d','a.currency_to = d.id','left')
                ->field('a.*,b.usernum formnum,b.username formname,b.mobile,c.usernum aboutnum, c.username aboutname,d.en_name')
                ->order('a.id DESC')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['running_str'] = UserRunningLog::$running_type[$v['running_type']];
                $list['data'][$k]['fromuser'] = $v['formnum']. '【' .$v['formname'] .'】';
                if(empty($v['aboutnum'])){
                    //如果相关用户是管理员
                    $ab_id = abs($v['about_id']);
                    $ab_user = Db::name('admin')->where(['admin_id' => $ab_id])->value('username');
                    $list['data'][$k]['aboutuser'] = $ab_user . '【管理员】';
                }else{
                    $list['data'][$k]['aboutuser'] = $v['aboutnum'] . '【' . $v['aboutname'] . '】';

                }
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }

        //获取币种列表
        $currency_list = db('currency_list')->select();
        $this->assign('currency_list', $currency_list);
        return $this->fetch('currencyRunLog');
        
    }

    public function searchWhere2($data)
    {
        $where = new Where();
        if(!empty($data['currency_id'])){
            $where['a.currency_to'] = $data['currency_id'];
        }
        if(!empty($data['key'])){
            if(!empty($data['key'])){
                $where['b.id|b.email|b.mobile|b.username'] = array('like', '%' . $data['key'] . '%');
            }
        }
        return $where;
    }

    //冲币
    public function recharge()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['val'])){
                return ['code' => 0, 'msg' => '充值数量不能为空'];
            }
            if($data['val'] <= 0){
                return ['code' => 0, 'msg' => '充值数量必须为正数'];
            }
            if(!is_numeric($data['val'])){
                return ['code' => 0, 'msg' => '请填写有效的充值金额'];
            }

            if(bccomp($data['val'], 0.0001, 4) < 0){
                return ['code' => 0, 'msg' => '充值金额不能小于0.0001'];
            }

            //获取用户币种信息
            $currency_info = Db::name('user_currency_list')->where(['id' => $data['id']])->find();
            $new_num = bcadd($currency_info['num'], $data['val'], 4);
            //更新用户币种数量
            Db::startTrans();
            $res = Db::name('user_currency_list')->where(['id' => $data['id']])->update(['num' => $new_num]);

            if($res !== false){
                //添加币种流水记录
                $run_log = [
                    'user_id'   => $currency_info['user_id'],
                    'about_id'  => -session('aid'),
                    'running_type' => UserRunningLog::TYPE1,
                    'currency_from' => $currency_info['currency_id'],
                    'currency_to'   => $currency_info['currency_id'],
                    'change_num'    => $data['val'],
                    'create_time'   => time(),
                    'remark'        => '后台充值'

                ];
                $res7 = Db::name('currency_running_log')->insert($run_log);
                if($res7 === 0){
                    Db::rollback();
                    return ['code' => 0, 'msg' => '充币失败'];
                }

                Db::commit();
                return ['code' => 1, 'msg' => '充币成功'];
            }else{
                Db::rollback();
                return ['code' => 0, 'msg' => '充币失败'];
            }



        }else{
            return ['code' => 0, 'msg' => '请求出错'];
        }
    }

    //扣币
    public function deduction()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['val'])){
                return ['code' => 0, 'msg' => '扣除数量不能为空'];
            }
            if($data['val'] <= 0){
                return ['code' => 0, 'msg' => '扣除数量必须为正数'];
            }
            if(!is_numeric($data['val'])){
                return ['code' => 0, 'msg' => '请填写有效的金额'];
            }

            if(bccomp($data['val'], 0.0001, 4) < 0){
                return ['code' => 0, 'msg' => '扣除金额不能小于0.0001'];
            }

            //获取用户币种信息
            $currency_info = Db::name('user_currency_list')->where(['id' => $data['id']])->find();
            $new_num = bcsub($currency_info['num'], $data['val'], 4);
            //更新用户币种数量
            Db::startTrans();
            $res = Db::name('user_currency_list')->where(['id' => $data['id']])->update(['num' => $new_num]);

            if($res !== false){
                //添加币种流水记录
                $run_log = [
                    'user_id'   => $currency_info['user_id'],
                    'about_id'  => -session('aid'),
                    'running_type' => UserRunningLog::TYPE2,
                    'currency_from' => $currency_info['currency_id'],
                    'currency_to'   => $currency_info['currency_id'],
                    'change_num'    => -$data['val'],
                    'create_time'   => time(),
                    'remark'        => '后台扣除'

                ];
                $res7 = Db::name('currency_running_log')->insert($run_log);
                if($res7 === 0){
                    Db::rollback();
                    return ['code' => 0, 'msg' => '扣币失败'];
                }

                Db::commit();
                return ['code' => 1, 'msg' => '扣币成功'];
            }else{
                Db::rollback();
                return ['code' => 0, 'msg' => '扣币失败'];
            }



        }else{
            return ['code' => 0, 'msg' => '请求出错'];
        }
    }

    //锁仓
    public function lockPosition()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['this_lock_num'])){
                return ['code' => 0, 'msg' => '锁仓数量不能为空'];
            }
            if($data['this_lock_num'] <= 0){
                return ['code' => 0, 'msg' => '锁仓数量必须为正数'];
            }
            if(!is_numeric($data['this_lock_num'])){
                return ['code' => 0, 'msg' => '请正确填写锁仓数量'];
            }

            if(bccomp($data['this_lock_num'], 0.0001, 4) < 0){
                return ['code' => 0, 'msg' => '锁仓数量不能小于0.0001'];
            }

            $currency_info = Db::name('user_currency_list')->where(['id' => $data['id']])->find();
            if(bccomp($currency_info['num'], $data['this_lock_num'], 4) < 0){
                return ['code' => 0, 'msg' => '锁仓数量不能大于可用数量'];
            }

            $new_num = bcsub($currency_info['num'], $data['this_lock_num'], 4); //可用数量减去锁仓数量
            $lock_num = bcadd($currency_info['lock_num'], $data['this_lock_num'], 4); //锁仓数量加上本次锁仓数

            Db::startTrans();
            $up = [
                'num' => $new_num,
                'lock_num' => $lock_num,
            ];
            $res = Db::name('user_currency_list')->where(['id' => $data['id']])->update($up);
            if($res !== false){
                //添加币种流水记录
                $run_log = [
                    'user_id'   => $currency_info['user_id'],
                    'about_id'  => -session('aid'),
                    'running_type' => UserRunningLog::TYPE35,
                    'currency_from' => $currency_info['currency_id'],
                    'currency_to'   => $currency_info['currency_id'],
                    'change_num'    => -$data['this_lock_num'],
                    'create_time'   => time(),
                    'remark'        => '锁仓扣除'

                ];
                $res7 = Db::name('currency_running_log')->insert($run_log);
                if($res7 === 0){
                    Db::rollback();
                    return ['code' => 0, 'msg' => '锁仓失败'];
                }

                Db::commit();
                return ['code' => 1, 'msg' => '锁仓成功'];
            }else{
                Db::rollback();
                return ['code' => 0, 'msg' => '锁仓失败'];
            }


        }else{
            $id = input('id');
            //获取用户币种信息
            $currency_info = Db::name('user_currency_list')->where(['id' => $id])->find();
            $user_info = Db::name('users')->where(['id' => $currency_info['user_id']])->find();
            $currency_info['username'] = $user_info['usernum'] . '【' . $user_info['username'] . '】';
            $this->assign('info', $currency_info);
            return $this->fetch('lockPosition');

        }
    }

    //解仓
    public function solutionWarehouse()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['this_lock_num'])){
                return ['code' => 0, 'msg' => '解仓数量不能为空'];
            }
            if($data['this_lock_num'] <= 0){
                return ['code' => 0, 'msg' => '解仓数量必须为正数'];
            }
            if(!is_numeric($data['this_lock_num'])){
                return ['code' => 0, 'msg' => '请正确填写解仓数量'];
            }

            if(bccomp($data['this_lock_num'], 0.0001, 4) < 0){
                return ['code' => 0, 'msg' => '解仓数量不能小于0.0001'];
            }

            $currency_info = Db::name('user_currency_list')->where(['id' => $data['id']])->find();
            if(bccomp($currency_info['lock_num'], $data['this_lock_num'], 4) < 0){
                return ['code' => 0, 'msg' => '解仓数量不能大于锁仓数量'];
            }

            $new_num = bcadd($currency_info['num'], $data['this_lock_num'], 4); //可用数量加上解仓数量
            $lock_num = bcsub($currency_info['lock_num'], $data['this_lock_num'], 4); //锁仓数量减去本次解仓数

            Db::startTrans();
            $up = [
                'num' => $new_num,
                'lock_num' => $lock_num,
            ];
            $res = Db::name('user_currency_list')->where(['id' => $data['id']])->update($up);
            if($res !== false){
                //添加币种流水记录
                $run_log = [
                    'user_id'   => $currency_info['user_id'],
                    'about_id'  => -session('aid'),
                    'running_type' => UserRunningLog::TYPE36,
                    'currency_from' => $currency_info['currency_id'],
                    'currency_to'   => $currency_info['currency_id'],
                    'change_num'    => $data['this_lock_num'],
                    'create_time'   => time(),
                    'remark'        => '解仓'

                ];
                $res7 = Db::name('currency_running_log')->insert($run_log);
                if($res7 === 0){
                    Db::rollback();
                    return ['code' => 0, 'msg' => '解仓失败'];
                }

                Db::commit();
                return ['code' => 1, 'msg' => '解仓成功'];
            }else{
                Db::rollback();
                return ['code' => 0, 'msg' => '解仓失败'];
            }


        }else{
            $id = input('id');
            //获取用户币种信息
            $currency_info = Db::name('user_currency_list')->where(['id' => $id])->find();
            $user_info = Db::name('users')->where(['id' => $currency_info['user_id']])->find();
            $currency_info['username'] = $user_info['usernum'] . '【' . $user_info['username'] . '】';
            $this->assign('info', $currency_info);
            return $this->fetch('solutionWarehouse');

        }
    }


}