<?php
namespace app\admin\controller;
use app\admin\model\Users as UsersModel;
use think\Validate;

class Users extends Common{
    //会员列表
    public function index(){
        if(request()->isPost()){
            $key=input('post.key');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $list=db('users')->alias('u')
                ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                ->field('u.*,ul.level_name')
                ->where('u.email|u.mobile|u.username','like',"%".$key."%")
                ->order('u.id desc')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['reg_time'] = date('Y-m-d H:s',$v['reg_time']);
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
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
            $check_user = UsersModel::get(['mobile' => $data['mobile']]);
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
            if (empty($data['referee'])) {
                $data['pid'] = 0;
            } else {
                //查询推荐人id
               $referrr_info =  UsersModel::get(['referee' => $data['referee']]);
               $data['gid'] = $referrr_info['id'];
            }
            $data['create_time'] = date('Y-m-d',time()); //添加时间方便做折线图
            if (UsersModel::create($data)) {
                //推荐人邀请成功用户，修改users表 have_tree 为1
                UsersModel::where('id', $referrr_info['id'])->update(['have_tree' => 1]);
                return ['code' => 1, 'msg' => '注册成功', 'url' => url('index')];
            } else {
                return ['code' => 0, 'msg' => '注册失败'];
            }


        } else {
            $province   = db('Region')->where ( array('pid'=>1) )->select ();
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

        return $this->fetch('userContactTest');
    }



}