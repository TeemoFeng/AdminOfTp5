<?php
namespace app\admin\controller;
use app\admin\model\UserApplyConsumeCash;
use app\admin\model\UserApplyShateCash;
use app\admin\model\UserApplyTradeCash;
use app\admin\model\UserCurrencyAccount;
use app\user\model\UserMessage;
use think\Db;
use app\admin\model\Users;
use think\facade\Env;
class Index extends Common
{
    public function initialize(){
        parent::initialize();
    }
    public function index(){
        //导航
        // 获取缓存数据
        $authRule = cache('authRule');
        if(!$authRule){
            //2019-3-20添加区分前后台权限
            $authRule = db('auth_rule')->where(['menustatus'=>1,'type' => 1])->order('sort')->select();
            cache('authRule', $authRule, 3600);
       }
        //声明数组
        $menus = array();
        foreach ($authRule as $key=>$val){
            $authRule[$key]['href'] = url($val['href']);
            if($val['pid']==0){
                if(session('aid')!=1){
                    if(in_array($val['id'],$this->adminRules)){
                        $menus[] = $val;
                    }
                }else{
                    $menus[] = $val;
                }
            }
        }
        foreach ($menus as $k=>$v){
            foreach ($authRule as $kk=>$vv){
                if($v['id']==$vv['pid']){
                    if(session('aid')!=1) {
                        if (in_array($vv['id'], $this->adminRules)) {
                            $menus[$k]['children'][] = $vv;
                        }
                    }else{
                        $menus[$k]['children'][] = $vv;
                    }
                }
            }
        }

        $user_mes_count = UserMessage::where(['from_id' => 0,'is_read' => 0])->count();
        $user_mes_all_count= UserMessage::where(['to_id' => 0,'is_read' => 0])->count();

        $this->assign('menus',json_encode($menus,true));
        $this->assign('user_mes_count',$user_mes_count);
        $this->assign('user_mes_all_count',$user_mes_all_count);
        return $this->fetch();
    }
//    public function main(){
//        $version = Db::query('SELECT VERSION() AS ver');
//        $config  = [
//            'url'             => $_SERVER['HTTP_HOST'],
//            'document_root'   => $_SERVER['DOCUMENT_ROOT'],
//            'server_os'       => PHP_OS,
//            'server_port'     => $_SERVER['SERVER_PORT'],
//            'server_ip'       => $_SERVER['SERVER_ADDR'],
//            'server_soft'     => $_SERVER['SERVER_SOFTWARE'],
//            'php_version'     => PHP_VERSION,
//            'mysql_version'   => $version[0]['ver'],
//            'max_upload_size' => ini_get('upload_max_filesize')
//        ];
//        $this->assign('config', $config);
//        return $this->fetch();
//    }

    public function main(){
        //获取会员总数
        $user_model = new Users();
        $user_num = $user_model->count();
        //新增会员
        $no_ac_num = $user_model->where(['status' => 0])->count();

        //获取申请充值的用户数量
        $apply_recharge = 0;
        //获取申请提现的用户数量
        $apply_shate_cash = UserApplyShateCash::where(['status' => 1])->count();
        $apply_trade_cash = UserApplyTradeCash::where(['status' => 1])->count();
        $apply_consume_cash = UserApplyConsumeCash::where(['status' => 1])->count(); //消费钱包
        $apply_cash = $apply_shate_cash+$apply_trade_cash+$apply_consume_cash;
        $init = [
            'user_num'          => $user_num,
            'no_ac_num'         => $no_ac_num,
            'apply_recharge'    => $apply_recharge,
            'apply_cash'        => $apply_cash,
        ];

        //数量显示数组
        $now_date = date('Y-m-d');
        $today_add_user = $user_model->where(['create_time' => $now_date, 'status' => 1])->count(); //获取今天注册有效会员
        $today_noval_user = $user_model->where(['create_time' => $now_date, 'status' => 0])->count(); //获取今天注册无效会员
        $lock_user = $user_model->where(['is_lock' => 1])->count(); //获取今天冻结会员
        $touzi = db('user_currency_account')
            ->field('sum(corpus) corpus,sum(cash_input_num) cash_input_num')
            ->find();
        $num_arr = [];
        $num_arr[] = ['name' => '今天新增有效会员', 'num' => $today_add_user];
        $num_arr[] = ['name' => '今天新增无效会员', 'num' => $today_noval_user];
        $num_arr[] = ['name' => '冻结会员', 'num' => $lock_user];
        $num_arr[] = ['name' => '平台投资总额', 'num' => $touzi['corpus']];
        $num_arr[] = ['name' => '平台复投数量', 'num' => $touzi['cash_input_num']];
        //公司财务
        $finance = db('company_day_running')
            ->field('sum(income) income,sum(expenses) expenses')
            ->find();
        $general_all = $finance['income'];
        $total_expenditure = $finance['expenses'];
        $total_precipitation = bcsub($finance['income'], $finance['expenses'], 4);
        $ratio = bcdiv($finance['expenses'],$finance['income'], 4);
        $allocation_ratio = ($ratio*100).'%';

        $finance_arr[] = ['name' => '总收入', 'num' => $general_all];
        $finance_arr[] = ['name' => '总支出', 'num' => $total_expenditure];
        $finance_arr[] = ['name' => '总沉淀', 'num' => $total_precipitation];
        $finance_arr[] = ['name' => '拨比',   'num' => $allocation_ratio];
        //近7天内公司财务
        $finance_7day = db('company_day_running')
            ->order('time DESC')
            ->limit(7)
            ->select();
        foreach($finance_7day as $k => $v){
            $finance_7day[$k]['subside'] = bcsub($v['income'], $v['expenses'], 4);
            $ratio = bcdiv($v['expenses'],$v['income'], 4);
            $finance_7day[$k]['ratio'] = ($ratio*100).'%';
            $finance_7day[$k]['time'] = date('Y-m-d',time());
        }


        $this->assign('init', $init);
        $this->assign('num_arr', $num_arr);
        $this->assign('finance_arr', $finance_arr);
        $this->assign('finance_7day', $finance_7day);

        return $this->fetch();
    }

    //公司收入支出拨比走势图数据
    public function expenditureAndGeneral()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['start_time']) && empty($data['end_time'])){
                //初始化最近七天的
                $year = date("Y");
                $month = date("m");
                $day = date("d");
                $end_time = mktime(23,59,59,$month,$day,$year);//当天结束时间戳
                $start_time = $end_time-(7*86400); //获取7天前的时间戳
                $end_time2 = date('Y-m-d', time());
                $start_time = date('Y-m-d', $start_time);
                $where = [$start_time, $end_time2];
                $finance = db('company_day_running')
                    ->whereTime('time','between', $where)
                    ->select();
                foreach($finance as $k => $v){
                    $finance[$k]['subside'] = bcsub($v['income'], $v['expenses'], 4);
                    $ratio = bcdiv($v['expenses'],$v['income'], 4);
                    $finance[$k]['ratio'] = ($ratio*100);
                }

                return ['finance' => $finance];

            }

        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }

    }



    public function navbar(){
        return $this->fetch();
    }
    public function nav(){
        return $this->fetch();
    }
    public function clear(){
        $R = Env::get('runtime_path');
        if ($this->_deleteDir($R)) {
            $result['info'] = '清除缓存成功!';
            $result['status'] = 1;
        } else {
            $result['info'] = '清除缓存失败!';
            $result['status'] = 0;
        }
        $result['url'] = url('admin/index/index');
        return $result;
    }
    private function _deleteDir($R)
    {
        $handle = opendir($R);
        while (($item = readdir($handle)) !== false) {
            if ($item != '.' and $item != '..') {
                if (is_dir($R . '/' . $item)) {
                    $this->_deleteDir($R . '/' . $item);
                } else {
                    if (!unlink($R . '/' . $item))
                        die('error!');
                }
            }
        }
        closedir($handle);
        return rmdir($R);
    }

    //退出登陆
    public function logout(){
        session(null);
        $this->redirect('login/index');
    }
    
}
