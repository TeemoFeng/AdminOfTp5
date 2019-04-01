<?php
namespace app\admin\controller;
use app\admin\model\UserCurrencyAccount;
use app\admin\model\UserNode;
use app\admin\model\UserReferee;
use app\admin\model\Users as UsersModel;
use app\user\controller\User;
use think\db\Where;
use think\Validate;

class Users extends Common{
    //会员列表
    public function index(){
        if(request()->isPost()){

            $data   =input('post.');
            $where  = $this->makeSearch($data);
            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $list=db('users')->alias('u')
                ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                ->join(config('database.prefix').'user_currency_account c','c.user_id = u.id','left')
                ->field('u.*,ul.level_name,c.cash_currency_num,c.cash_input_num,c.corpus,c.activation_num,c.consume_num,c.transaction_num')
                ->where($where)
                ->order('u.id desc')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['reg_time'] = date('Y-m-d H:s',$v['reg_time']);
                $list['data'][$k]['status'] = UsersModel::$acstatus[$v['status']];
                $list['data'][$k]['enabled'] = UsersModel::$vastatus[$v['enabled']];
                $list['data'][$k]['baodan_center'] = UsersModel::$bdstatus[$v['baodan_center']];
                $list['data'][$k]['is_report'] = UsersModel::$yhstatus[$v['is_report']];
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }

        //status
        $status = UsersModel::$status;
        $this->assign('status', $status);
        return $this->fetch();
    }

    //搜索
    public function makeSearch($data)
    {
        $where = new Where();
        if(!empty($data['status'])){
            if($data['status'] == 1){
                $where['u.enabled'] = 0; //无效会员
            }
            if($data['status'] ==2){
                $where['u.enabled'] = 1; //有效会员
            }
            if($data['status'] == 3){
                $where['u.is_lock'] = 1; //冻结会员
            }
            if($data['status'] == 4){
                $where['u.baodan_center'] = 1; //报单中心
            }
        }
        if(!empty($data['start_time']) && empty($data['end_time'])){
            $start_time = strtotime($data['start_time']);
            $where['u.reg_time'] = array('egt', $start_time);
        }
        if(!empty($data['end_time']) && empty($data['start_time'])){
            $end_time = strtotime($data['end_time']);
            $where['u.reg_time'] = array('elt',$end_time);
        }
        if(!empty($data['start_time']) && !empty($data['end_time'])){
            $start_time = strtotime($data['start_time']);
            $end_time = strtotime($data['end_time']);
            $where['u.reg_time'] = array('between time', array($start_time, $end_time));
        }
        if(!empty($data['key'])){
            $where['u.id|u.email|u.mobile|u.username'] = array('like', '%' . $data['key'] . '%');
        }
        return $where;
    }

    //设置会员状态
    public function usersState(){
        $id=input('post.id');
        $is_lock=input('post.is_lock');
        if(db('users')->where('id='.$id)->update(['is_lock'=>$is_lock])!==false){
            return ['status'=>1,'msg'=>'设置成功!'];
        }else{
            return ['status'=>0,'msg'=>'设置失败!'];
        }
    }
    public function edit($id=''){
        if(request()->isPost()){
            $user = db('users');
            $data = input('post.');
            $level =explode(':',$data['level']);
            $data['level'] = $level[1];
            $province =explode(':',$data['province']);
            $data['province'] = isset( $province[1])?$province[1]:'';
            $city =explode(':',$data['city']);
            $data['city'] = isset( $city[1])?$city[1]:'';
            $district =explode(':',$data['district']);
            $data['district'] = isset( $district[1])?$district[1]:'';
            if(empty($data['password'])){
                unset($data['password']);
            }else{
                $data['password'] = md5($data['password']);
            }
            if ($user->update($data)!==false) {
                $result['msg'] = '会员修改成功!';
                $result['url'] = url('index');
                $result['code'] = 1;
            } else {
                $result['msg'] = '会员修改失败!';
                $result['code'] = 0;
            }
            return $result;
        }else{
            $province = db('Region')->where ( array('pid'=>1) )->select ();
            $user_level=db('user_level')->order('sort')->select();
            $info = UsersModel::get($id);
            $bank = db('Bank')->order('id ASC')->select();

            $this->assign('info',json_encode($info,true));
            $this->assign('title',lang('edit').lang('user'));
            $this->assign('province',json_encode($province,true));
            $this->assign('user_level',json_encode($user_level,true));

            $this->assign('bank', json_encode($bank, true)); //银行列表
            $city = db('Region')->where ( array('pid'=>$info['province']) )->select ();
            $this->assign('city',json_encode($city,true));
            $district = db('Region')->where ( array('pid'=>$info['city']) )->select ();
            $this->assign('district',json_encode($district,true));
            return $this->fetch();
        }
    }

    public function getRegion(){
        $Region=db("region");
        $pid = input("pid");
        $arr = explode(':',$pid);
        $map['pid']=$arr[1];
        $list=$Region->where($map)->select();
        return $list;
    }

    public function usersDel(){
        db('users')->delete(['id'=>input('id')]);
        db('oauth')->delete(['uid'=>input('id')]);
        return $result = ['code'=>1,'msg'=>'删除成功!'];
    }
    public function delall(){
        $map[] =array('id','IN',input('param.ids/a'));
        db('users')->where($map)->delete();
        $result['msg'] = '删除成功！';
        $result['code'] = 1;
        $result['url'] = url('index');
        return $result;
    }

    //重置密码
    public function resetPas()
    {
        $map[] =array('id','IN',input('param.ids/a'));
        $data['password'] = md5('123456');
        $data['safeword'] = '123456';
        $res = db('users')->where($map)->update($data);
        if($res === false){
            return ['code' => 0, 'msg' => '重置失败，请重试'];
        }
        $result['msg'] = '密码重置成功！';
        $result['code'] = 1;
        $result['url'] = url('index');
        return $result;
    }

    //设置报单中心
    public function setBaodan()
    {
        $map[] =array('id','IN',input('param.ids/a'));
        $data['baodan_center'] = 1;
        $res = db('users')->where($map)->update($data);
        if($res === false){
            return ['code' => 0, 'msg' => '设置失败，请重试'];
        }
        return ['code' => 1, 'msg' => '设置报单中心成功', 'url' => url('index')];
    }

    //取消报单中心
    public function cancelBaodan()
    {
        $map[] =array('id','IN',input('param.ids/a'));
        $data['baodan_center'] = 0;
        $res = db('users')->where($map)->update($data);
        if($res === false){
            return ['code' => 0, 'msg' => '设置失败，请重试'];
        }
        return ['code' => 1, 'msg' => '取消报单中心成功', 'url' => url('index')];
    }

    //访问前台
    public function userJump()
    {
        $user_id = input('id');
        $user_info = UsersModel::get($user_id);
        if(empty($user_info)){
            return ['code' => 0, 'msg' => '未找到该用户'];

        }else{
            if(empty($user_info['mobile'])){
                $info['username'] = $user_info['email'];
            }else{
                $info['username'] = $user_info['mobile'];
            }
            $info['password'] = $user_info['password'];
        }
        return ['code' => 1, 'info' => $info];
    }


    //会员详情
    public function userDetail($id)
    {
        //获取用户信息
        $user_info = UsersModel::get($id);
        $user_currency_account = db('user_currency_account')->where(['user_id' => $id])->find();
        if(empty($user_currency_account)){
            $user_currency_account['cash_currency_num'] = '0.0000';
            $user_currency_account['corpus'] = '0.0000';
            $user_currency_account['activation_num'] = '0.0000';
            $user_currency_account['consume_num'] = '0.0000';
            $user_currency_account['transaction_num'] = '0.0000';
        }
        $user_level = db('user_level')->where(['level_id' => $user_info['level']])->find();
        $user_info['level_name'] = $user_level['level_name'];
        if(empty($user_info['email'])){
            $user_info['email'] = '无';
        }

        if($user_info['enabled'] == 0){
            $user_info['enabled'] = '不是有效会员';
        }else{
            $user_info['enabled'] = '是有效会员';
        }
        if($user_info['baodan_center'] == 0){
            $user_info['baodan_center'] = '否';
        }else{
            $user_info['baodan_center'] = '是';
        }
        $this->assign('user_info', $user_info);
        $this->assign('user_currency_account', $user_currency_account);
        return $this->fetch('userDetail');

    }


    /***********************************会员组***********************************/
    public function userGroup(){
        if(request()->isPost()){
            $userLevel=db('user_level');
            $list=$userLevel->order('sort')->select();
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list,'rel'=>1];
        }
        return $this->fetch();
    }
    public function groupAdd(){
        if(request()->isPost()){
            $data = input('post.');
            db('user_level')->insert($data);
            $result['msg'] = '会员组添加成功!';
            $result['url'] = url('userGroup');
            $result['code'] = 1;
            return $result;
        }else{
            $this->assign('title',lang('add')."会员组");
            $this->assign('info','null');
            return $this->fetch('groupForm');
        }
    }
    public function groupEdit(){
        if(request()->isPost()) {
            $data = input('post.');
            db('user_level')->update($data);
            $result['msg'] = '会员组修改成功!';
            $result['url'] = url('userGroup');
            $result['code'] = 1;
            return $result;
        }else{
            $map['level_id'] = input('param.level_id');
            $info = db('user_level')->where($map)->find();
            $this->assign('title',lang('edit')."会员组");
            $this->assign('info',json_encode($info,true));
            return $this->fetch('groupForm');
        }
    }
    public function groupDel(){
        $level_id=input('level_id');
        if (empty($level_id)){
            return ['code'=>0,'msg'=>'会员组ID不存在！'];
        }
        db('user_level')->where(array('level_id'=>$level_id))->delete();
        return ['code'=>1,'msg'=>'删除成功！'];
    }
    public function groupOrder(){
        $userLevel=db('user_level');
        $data = input('post.');
        $userLevel->update($data);
        $result['msg'] = '排序更新成功!';
        $result['url'] = url('userGroup');
        $result['code'] = 1;
        return $result;
    }

    //会员注册
    public function register()
    {
        if (request()->isPost()) {
            $data   = input('post.');
            $level          = explode(':',$data['level']); //ng 获取的值要单独去除number:
            $data['level']  = $level[1]; //默认会员等级为注册会员
//            $province       = explode(':',$data['province']);
//            $data['province'] = isset($province[1]) ? $province[1] : '';
//            $city           = explode(':',$data['city']);
//            $data['city']   = isset( $city[1]) ? $city[1] : '';
//            $district       = explode(':',$data['district']);
//            $data['district'] = isset( $district[1]) ? $district[1] : '';
            if (empty($data['mobile'])) return ['code' => 0, 'msg' => '手机号不能为空'];
            $check_user = UsersModel::where(['mobile' => $data['mobile']])->find();
            if ($check_user) {
                return $result = ['code' => 0, 'msg' => '该手机号已存在'];
            }
            //验证
            $check = [
                'usernum'           => $data['usernum'],
                'username'          => $data['username'],
                'mobile'            => $data['mobile'],
                'password'          => $data['password'],
                'confirmPwd'        => $data['confirmPwd'],
                'safeword'          => $data['safeword'],
                'confirmSafePwd'    => $data['confirmSafePwd'],
            ];
            //检测
            $validate = new \app\admin\validate\Users();
            $result = $validate->check($check);
            if (!$result) {
                return ['code' => 0, 'msg' => $validate->getError()];
            }
            //检测密码是否相等
            if($data['password'] != $data['confirmPwd']) return ['code' => 0, 'msg' => '两次输入的登录密码不一致'];
            if($data['safeword'] != $data['confirmSafePwd']) return ['code' => 0, 'msg' => '两次输入的安全密码不一致'];

            $data['password'] = md5($data['password']);
            //接入用户和推荐人关系
            if ($data['referee'] == '0000') {
                $data['pid'] = 0;
                $data['referee'] = '0000|公司';
            } else {
                //查询推荐人id
               $referrr_info =  UsersModel::get(['usernum' => $data['referee']]);
               $data['pid'] = $referrr_info['id'];
               $data['referee'] = $referrr_info['usernum'] .'|' .$referrr_info['username'];

            }

            if($data['contact_person'] == '0000'){
                $data['npid'] = 0;
                $data['contact_person'] = '0000|公司';
            }else{
                //查询接点人id
                $node_info    = UsersModel::get(['usernum' => $data['contact_person']]);
                $data['npid'] = $node_info['id'];
                $data['contact_person'] = $node_info['usernum'] .'|' .$node_info['username'];
            }

            //是否报备银行
            if(!empty($data['bank_id']) && !empty($data['bank_user']) && !empty($data['bank_account']) &&!empty($data['bank_desc'])){
                $data['is_report'] = 1; //报备银行
            }else{
                $data['is_report'] = 0;
            }

            $data['create_time'] = date('Y-m-d',time()); //添加时间方便做折线图
            $new_user_id = UsersModel::create($data);
            if ($new_user_id) {
                //推荐人邀请成功用户，修改users表 have_tree 为1
                UsersModel::where('id', $referrr_info['id'])->update(['have_tree' => 1]);
                $user_referee_model = new UserReferee();
                $user_node_model = new UserNode();
                if($data['pid'] == 0){
                    //接入用户和接点人关系
                    $data2['user_id'] = $new_user_id;
                    $data2['user_son_str'] = 0 . ',';
                    //接入用户和推荐人的关系
                    $data3['user_id'] = $new_user_id;
                    $data3['user_son_str'] = 0 . ',';
                }else{
                    $son_str = $user_referee_model->where(['user_id' => $referrr_info['id'] ])->value('user_son_str');
                    $son_node_str = $user_node_model->where(['user_id' => $node_info['id']])->value('user_son_str');
                    //接入用户和接点人关系
                    $data2['user_id'] = $new_user_id;
                    $data2['user_son_str'] = $son_str . $referrr_info["id"];
                    //接入用户和推荐人的关系
                    $data3['user_id'] = $new_user_id;
                    $data3['user_son_str'] = $son_node_str . $node_info['id'];
                }

                UserReferee::create($data2);
                UserNode::create($data3);
                //获取当前设置的汇率
                $sate = db('bonus_ext_set')->where(['id' => 1])->value('money_change');
                //创建用户钱包账户
                $account['user_id'] = $new_user_id;
                $account['rate'] = $sate;
                UserCurrencyAccount::create($account);

                return ['code' => 1, 'msg' => '注册成功', 'url' => url('index')];
            } else {
                return ['code' => 0, 'msg' => '注册失败'];
            }

        } else {
            $province   = db('Region')->where ( array('pid'=>1) )->select();
            $user_level = db('user_level')->order('sort')->select();
            $bank = db('Bank')->order('id ASC')->select();
            $user_num = createVipNum();
            $this->assign('province',json_encode($province,true));
            $this->assign('user_level', json_encode($user_level, true)); //会员级别
            $this->assign('bank', json_encode($bank, true)); //银行列表
            $this->assign('usernum', $user_num); //会员编号

            return $this->fetch();
        }

    }

    //验证推荐人和接点人
    public function validateUser()
    {
        $search = input('post.search');
        $type = input('post.type');
        if(empty($search) || empty($type)){
            return ['code' => 0, 'msg' => '请求不合法'];
        }
        if($type == 1){
            //推荐人
            $where['usernum'] = $search;
        }else{
            //接点人
            $where['usernum'] = $search;
        }

        $user_info = UsersModel::get($where);
        if(empty($user_info)){
            return ['code' => 0, 'msg' => '此用户不存在'];
        }else{
            return ['code' => 1, 'name' => $user_info['username']];
        }


    }

    //直推架构树
    public function usertree()
    {
        if (request()->isPost()) {
            $where = [];
            if(array_key_exists( 'id',$_REQUEST)) {
                $pId = $_REQUEST['id'];
                $pId = htmlspecialchars($pId);
                if ($pId == null || $pId == "") $pId = "0";
                $where['u.pid'] = $pId;

            }else{
                $where['u.pid'] = 0;
            }
            if(array_key_exists( 'key',$_REQUEST)){
                $referee = $_REQUEST['key'];
                if(!empty($referee)){
                    unset($where);
                    $where['u.username'] = $referee;
                }
            }
            $user_info = db('users')
                ->alias('u')
                ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                ->field('u.id,u.referee,u.username,u.pid,u.have_tree,ul.level_name')
                ->where($where)
                ->order('u.id asc')
                ->select();
            $list = [];
            array_map(function ($v) use (&$list) {
                $list[] = [
                    'id' => $v['id'],
                    'name' => $v['referee'] . '(' . $v['username'] . ' 级别:' . $v['level_name']. ')',
                    'isParent'  => $v['have_tree'],
                    'icon' => "/static/admin/images/user.png"
                ];
            },$user_info);

            return $list;

        }

        return $this->fetch('UserTree');
    }

    //会员概况图示
    public function userChart()
    {
        if(request()->post()){
            //获取各个级别用户数量
            $user_count = db('users')
                ->group('level')
                ->field('level,count("id") as count')
                ->select();
            $user_count_list = [];
            $user_level = [
                '1' => 'F',
                '2' => 'F1',
                '3' => 'F2',
                '4' => 'F3',
                '5' => 'F4'
            ];
            $color = [
                '1' => '#2dc6c8',
                '2' => '#b6a2dd',
                '3' => '#5ab1ee',
                '4' => '#d7797f',
                '5' => '#dcc97f',
            ];

            foreach($user_count as $key => $val){
                $user_count_list[$key]['num'] = $val['count'];
                $user_count_list[$key]['level'] = $user_level[$val['level']];
                $user_count_list[$key]['color'] = $color[$val['level']];

            }

            //获取前七天所有注册会员的数量和未注册会员的数量
            $year = date("Y");
            $month = date("m");
            $day = date("d");
            $end_time = mktime(23,59,59,$month,$day,$year);//当天结束时间戳
            $start_time = $end_time-(7*86400); //获取7天前的时间戳
            $where = [$start_time, $end_time];
            $user_info = db('users')
                ->whereTime('reg_time','between', $where)
                ->group('create_time')
                ->field('id,create_time as date,count("id") as count')
                ->select();

            //获取未激活会员信息
            $where2['status'] = 0; //未激活状态
            $user_no_active = db('users')
                ->whereTime('reg_time','between', $where)
                ->where($where2)
                ->group('create_time')
                ->field('id,create_time as date,count("id") as count')
                ->select();

            return ['level_chart' => $user_count_list, 'user_chart' => $user_info, 'user_no_active' => $user_no_active];
        }

        return $this->fetch('userChartNew');
    }

    //会员接点图
    public function userContact()
    {
        if(request()->isPost()){
            $search = input('post.search');
            $id = input('post.id');
            if(!empty($search)){
                //如果是搜索查询
                $first_node = UsersModel::get(['usernum' => $search]);
                $where['id'] = $first_node['id'];
                $where2['npid'] = $first_node['id'];
            }else{
                if($id == 0){ //初始化
                    //查询upid = 0
                    $first_node = UsersModel::get(['npid' => 0]);
                    $where['id'] = $first_node['id'];
                    $where2['npid'] = $first_node['id'];
                }else{
                    $where['id']  = $id;
                    $where2['npid'] = $id;
                }

            }
            $user_info = db('users')
                ->alias('u')
                ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                ->field('u.id,u.usernum,u.username,ul.level_name')
                ->where($where)
                ->find();

            $user_info['children'] = db('users')
                ->alias('u')
                ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                ->field('u.id,u.usernum,u.username,ul.level_name')
                ->where($where2)
                ->select();

            if(!empty($user_info['children'])){
                foreach($user_info['children'] as $key => $val){
                    $where3['npid'] = $val['id'];
                    $user_info['children'][$key]['children'] = db('users')
                        ->alias('u')
                        ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                        ->field('u.id,u.usernum,u.username,ul.level_name')
                        ->where($where3)
                        ->select();
                }
            }
            return ['code' => 1, 'data' => $user_info];
//            $html = '<li>';
//            $html .= '<div>';
//            $html .= '<a href="#" class="layui-btn layui-btn-normal layui-btn-sm" id="resetBtn" data-type="0">编号：'.$user_info["usernum"].'</a><br/>';
//            $html .= '<a href="#" class="layui-btn layui-btn-normal layui-btn-sm" id="resetBtn" data-type="0">姓名：'.$user_info["username"].'</a><br/>';
//            $html .= '<a href="#" class="layui-btn layui-btn-normal layui-btn-sm" id="resetBtn" data-type="0">级别：'.$user_info["level_name"].'</a>';
//            $html .= '</div>';
//            if(isset($user_info['child'])){
//                $html .= '<ul>';
//                foreach ($user_info['child'] as $k => $v){
//                    $html .= '<li>';
//                    $html .= '<div>';
//                    $html .= '<a href="#" class="layui-btn layui-btn-normal layui-btn-sm" id="resetBtn" data-type="0">编号：'.$v["usernum"].'</a><br/>';
//                    $html .= '<a href="#" class="layui-btn layui-btn-normal layui-btn-sm" id="resetBtn" data-type="0">姓名：'.$v["username"].'</a><br/>';
//                    $html .= '<a href="#" class="layui-btn layui-btn-normal layui-btn-sm" id="resetBtn" data-type="0">级别：'.$v["level_name"].'</a>';
//                    $html .= '</div>';
//                    if(isset($v['child'])){
//                        $html .= '<ul>';
//                        foreach ($v['child'] as $kk => $vv){
//                            $html .= '<li>';
//                            $html .= '<div>';
//                            $html .= '<a href="#" class="layui-btn layui-btn-normal layui-btn-sm" id="resetBtn" data-type="0">编号：'.$vv["usernum"].'</a><br/>';
//                            $html .= '<a href="#" class="layui-btn layui-btn-normal layui-btn-sm" id="resetBtn" data-type="0">姓名：'.$vv["username"].'</a><br/>';
//                            $html .= '<a href="#" class="layui-btn layui-btn-normal layui-btn-sm" id="resetBtn" data-type="0">级别：'.$vv["level_name"].'</a>';
//                            $html .= '</div>';
//                            $html .= '</li>';
//                        }
//                        $html .= '</ul>';
//
//                    }else{
//                        $html .= '</li>';
//                    }
//
//                }
//                $html .= '</ul>';
//            }
//
//            $html .= '</li>';

        }

        return $this->fetch('userContact');
    }



}