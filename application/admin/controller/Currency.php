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
                ->field('a.id,a.username,a.usernum,b.*')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['status'] = UserCurrencyList::$status[$v['status']];
                $list['data'][$k]['username'] = $v['usernum'].$v['username'];
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
                $where['a.id|a.email|a.mobile|a.username'] = array('like', '%' . $data['key'] . '%');
            }
        }
        return $where;
    }

    //币种流水记录
    public function currencyRunLog()
    {
        
    }
}