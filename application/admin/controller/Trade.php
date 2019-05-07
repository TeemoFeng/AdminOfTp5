<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26 0026
 * Time: 23:11
 */
namespace app\admin\controller;


use app\admin\model\CurrencyList;
use app\admin\model\UserTradeDepute;
use app\admin\model\UserTradeDeputeLog;
use think\Db;
use think\db\Where;

class Trade extends Common{
    //委托记录
    public function deputeList()
    {
        $status = UserTradeDepute::$status; //状态
        //获取用户申请充值列表
        if(request()->isPost()){
            $data   =input('post.');
            $where  = $this->makeSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $where['depute_currency'] = $data['currency_id']; //查询货币类型
            //沙特链申请列表
            $list = db('user_trade_depute')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->join(config('database.prefix').'currency_list c','a.depute_currency = c.id','left')
                ->field('a.*,u.usernum,u.username,u.id user_id,c.en_name')
                ->where($where)
                ->order('id DESC')
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['status_str'] = UserTradeDepute::$status[$v['status']];
                $list['data'][$k]['depute_type_str'] = UserTradeDepute::$currency_type[$v['depute_type']];
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                if($v['depute_time'] == 0){
                    $list['data'][$k]['depute_time'] = '暂无成交';
                }else{
                    $list['data'][$k]['depute_time'] = date('Y-m-d H:i:s', $v['depute_time']);
                }

                $list['data'][$k]['username'] = $v['usernum'] . '【' .$v['username'] . '】';

            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];


        }

        //获取币种列表

        $currency_list  = Db::name('currency_list')->select();
        $this->assign('status', $status);
        $this->assign('currency_list', $currency_list);
        return $this->fetch('deputeList');
    }

    //搜索
    public function makeSearch($data)
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
            $end_time = strtotime($data['end_time'] . ' 59:59:59');
            $where['a.create_time'] = array('elt',$end_time);
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $start_time = strtotime($data['start_time']. ' 00:00:00');
            $end_time = strtotime($data['end_time']. ' 59:59:59');
            $where['a.create_time'] = array('between time', array($start_time, $end_time));
        }
        if(!empty($data['key'])){
            $where['u.id|u.usernum|u.email|u.mobile|u.username'] = array('like', '%' . $data['key'] . '%');
        }
        return $where;
    }


    //交易记录
    public function tradeList()
    {
        $status = UserTradeDeputeLog::$status2; //状态
        //获取用户申请充值列表
        if(request()->isPost()){
            $data   =input('post.');
            $where  = $this->makeSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $where['trade_currency'] = $data['currency_id']; //查询货币类型
            //沙特链申请列表
            $list = db('user_trade_depute_log')
                ->alias('a')
                ->join(config('database.prefix').'users u','a.user_id = u.id','left')
                ->join(config('database.prefix').'currency_list c','a.trade_currency = c.id','left')
                ->field('a.*,u.usernum,u.username,u.id user_id,c.en_name')
                ->where($where)
                ->order('id DESC')
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['status_str'] = UserTradeDeputeLog::$status2[$v['status']];
                $list['data'][$k]['trade_type_str'] = UserTradeDeputeLog::$trade_type[$v['trade_type']];
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

                $list['data'][$k]['username'] = $v['usernum'] . '【' .$v['username'] . '】';

            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];


        }

        //获取币种列表

        $currency_list  = Db::name('currency_list')->select();
        $this->assign('status', $status);
        $this->assign('currency_list', $currency_list);
        return $this->fetch('tradeList');

    }


}