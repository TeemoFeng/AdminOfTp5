<?php
namespace app\home\controller;
use app\admin\model\CurrencyList;
use app\user\model\Users;
use think\Db;
use clt\Lunar;
use think\db\Where;

//use think\facade\Env;
class Index extends Common{
    public function initialize(){
        if(session('user.id')){
            $this->userInfo=db('users')->alias('u')
                ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                ->where('u.id','=',session('user.id'))
                ->field('u.*,ul.level_name')
                ->find();
            $this->assign('user_info',$this->userInfo);
        }
        $system = Db::name('system')->find();
        $this->assign('system', $system);
    }

    public function index()
    {
        //首页各项待加
        //获取广告列表
        $guanggao_list = Db::name('ad')->select();
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
        $user = session('user');
        $this->assign('user', $user);
        $this->assign('guanggao_list', $guanggao_list);

        return $this->fetch('index');
    }

    //10秒刷新一次阿美币信息
    public function getAmbInfo()
    {
        if(request()->isPost()){
            //获取阿美币信息
            $start_time = strtotime(date('Y-m-d 00:00:00'));
            $end_time = strtotime(date('Y-m-d 23:59:59'));
            $amei_info = CurrencyList::where(['status' => 'open', 'en_name' => 'AMB'])->find();
            $where1 = new Where();
            $where1['trade_type'] = 2;
            $where1['create_time'] = array('between time', array($start_time, $end_time));
            $high = db('user_trade_depute_log')->where($where1)->order('price DESC')->value('price');
            $low = db('user_trade_depute_log')->where($where1)->order('price ASC')->value('price');
            //昨天收盘价
            $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
            $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
            $where2 = new Where();
            $where2['trade_type'] = 2;
            $where2['create_time'] = array('between time', array($beginYesterday, $endYesterday));
            $new_price = Db::name('user_trade_depute_log')->where($where2)->order('id DESC')->value('price');
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
            $amei_info['to_btc'] = '0';
            //涨跌24
            if(empty($high)){
                $high = (string)$amei_info['price'];
            }
            if(empty($low)){
                $low = (string)$amei_info['price'];
            }
            if(empty($new_price)){
                $new_price = $amei_info['price'];
            }
            $h_l = bcsub($amei_info['price'], $new_price,4);
            $h_l_s = bcdiv($h_l, $amei_info['price']);
            if($h_l_s < 0){
                $amei_info['ratio_1'] = (string)-$h_l_s; //负数
            }else{
                $amei_info['ratio_1'] = (string)+$h_l_s;
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
                $amei_info['ratio_7'] = (string)-$h_l_s;
            }else{
                $amei_info['ratio_7'] = (string)+$h_l_s;
            }
            return ['code' => 1, 'data' => $amei_info];
        }

    }


    public function index_old(){
        $order = input('order','createtime');
        $time = time();
        $list=db('article')->alias('a')
            ->join(config('database.prefix').'category c','a.catid = c.id','left')
            ->field('a.id,c.catdir,c.catname')
            ->order($order.' desc')
            ->where('createtime', '>', $time)
            ->limit('15')
            ->select();
        foreach ($list as $k=>$v){
            $list[$k]['time'] = toDate($v['createtime']);
            $list[$k]['url'] = url('home/'.$v['catdir'].'/info',array('id'=>$v['id'],'catId'=>$v['catid']));
        }
        $this->assign('list', $list);
        if(!isMobile()){
            $m= $thisDate = date("m");
            $d= $thisDate = date("d");
            $y= $thisDate = date("Y");
            $Lunar=new Lunar();
            //获取农历日期
            $nonliData = $Lunar->convertSolarToLunar($y,$m,$d);
            $nonliData = $nonliData[1].'-'.$nonliData[2];
            $feastId = db('feast')->where(array('feast_date'=>$nonliData,'type'=>2))->value('id');
            $style='';
            $js='';
            if($feastId){
                $element = db('feast_element')->where('pid',$feastId)->select();
                $style = '<style>';
                $js = '';
                foreach ($element as $k=>$v){
                    $style .= $v['css'];
                    $js .= $v['js'];
                }
                $style .= '</style>';

            }else{
                $feastId = db('feast')->where(array('feast_date'=>$m.'-'.$d,'type'=>1))->value('id');
                if($feastId){
                    $element = db('feast_element')->where('pid',$feastId)->select();
                    $style = '<style>';
                    $js = '';
                    foreach ($element as $k=>$v){
                        $style .= $v['css'];
                        $js .= $v['js'];
                    }
                    $style .= '</style>';
                }
            }
            $this->assign('style', $style);
            $this->assign('js', $js);
        }
        return $this->fetch();
    }
    public function senmsg(){
        $data = input('post.');
        $data['addtime'] = time();
        $data['ip'] = getIp();
        $data['content'] = safe_html($data['content']);
        db('message')->insert($data);
        $result['status'] = 1;
        return $result;
    }
    public function down($id=''){
        $map['id'] = $id;
        $files = Db::name('download')->where($map)->find();
        return download(Env::get('root_path').'public'.$files['files'], $files['title']);
    }
}