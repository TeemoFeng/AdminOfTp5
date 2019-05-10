<?php
namespace app\mobile\controller;
use app\admin\model\CurrencyList;
use clt\Lunar;
use think\db\Where;

class Index extends Common{
    public function initialize(){
        parent::initialize();

    }
    public function index(){
        return view();
    }


    public function zoushitu()
    {
        //获取阿美币信息
        $start_time = strtotime(date('Y-m-d 00:00:00'));
        $end_time = strtotime(date('Y-m-d 23:59:59'));
        $amei_info = CurrencyList::where(['status' => 'open', 'en_name' => 'AMB'])->find();
        $where1 = new Where();
        $where1['trade_type'] = 2;
        $where1['create_time'] = array('between time', array($start_time, $end_time));
        $high = db('user_trade_depute_log')->where($where1)->order('price DESC')->value('price');
        $low = db('user_trade_depute_log')->where($where1)->order('price ASC')->value('price');
        //价格
        $new_price = db('user_trade_depute_log')->where($where1)->order('id DESC')->value('price');
        //7天前的价格
        $where2 = new Where();
        $where2['trade_type'] = 2;
        $start7_time = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
        $end7_time = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));
        $where2['create_time'] = array('between time', array($start7_time, $end7_time));
        $day7_price = db('user_trade_depute_log')->where($where2)->order('id DESC')->value('price');

        //交易份额
        $ratio = bcdiv($amei_info['virtual_trans_num'], $amei_info['num'], 2);
        $amei_info['ratio']  = $ratio * 100 .'%';
        //市值
        $num = bcdiv($amei_info['num'], 7, 4);
        $amei_info['num'] = '$'. bcdiv($num, 1000000000, 4) . 'G';
        //成交量
        $amei_info['virtual_trans_num'] = '$'. bcdiv($amei_info['virtual_trans_num'], 1000000, 4);

        //价格
        $amei_info['to_btc'] = 0;
        //涨跌24
        if(empty($high)){
            $high = $amei_info['price'];
        }
        if(empty($low)){
            $low = $amei_info['price'];
        }
        if(empty($new_price)){
            $new_price = $amei_info['price'];
        }
        $h_l = bcsub($amei_info['price'], $new_price,4);
        $h_l_s = bcdiv($h_l, $amei_info['price']);
        if($h_l_s < 0){
            $amei_info['ratio_1'] = -$h_l_s; //负数
        }else{
            $amei_info['ratio_1'] = +$h_l_s;
        }
        //最新价
        if(!empty($new_price)){
            $amei_info['now_price'] = bcdiv($new_price, 7, 4);
        }else{
            $amei_info['now_price'] = bcdiv($amei_info['price'], 7, 4);
        }
        //涨跌7天
        $h_l = bcsub($day7_price, $new_price,4);
        $h_l_s = bcdiv($h_l, $amei_info['price']);
        if($h_l_s < 0){
            $amei_info['ratio_7'] = -$h_l_s;
        }else{
            $amei_info['ratio_7'] = +$h_l_s;
        }
        $this->assign('amei_info', $amei_info);
        return $this->fetch('zoushitu');
    }

    public function btc()
    {
        return $this->fetch('btc');
    }

    public function bch()
    {
        return $this->fetch('bch');
    }

    public function eos()
    {
        return $this->fetch('eos');
    }

    public function eth()
    {
        return $this->fetch('eth');
    }

    public function ltc()
    {
        return $this->fetch('ltc');
    }

    public function xlm()
    {
        return $this->fetch('xlm');
    }

    public function xrp()
    {
        return $this->fetch('xrp');
    }
}