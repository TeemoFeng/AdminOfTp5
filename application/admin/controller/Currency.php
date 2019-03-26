<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26 0026
 * Time: 23:11
 */
namespace app\admin\controller;


use app\admin\model\CurrencyList;

class Currency extends Common{
    //币种列表
    public function currencyList()
    {
        if(request()->isPost()){
            $data = input('post.');
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $list = db('currency_list')
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

    public function addCurrency()
    {
        return $this->fetch('addCurrency');
    }

    public function editCurrency()
    {

    }

    public function delCurrency()
    {

    }


    //用户货币
    public function userCurrency()
    {
        
    }

    //币种流水记录
    public function currencyRunLog()
    {
        
    }
}