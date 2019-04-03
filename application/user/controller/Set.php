<?php
namespace app\user\controller;
use app\user\model\UserBindLog;
use app\user\model\UserLoginLog;
use app\user\model\Users;
use think\console\Input;
use think\Db;
class Set extends Common{
    protected $uid;
    public function initialize()
    {
        parent::initialize();
        $this->uid=session('user.id');
    }

    public function index(){
        if(request()->isPost()){
            $data = input('post.');
            $user = db('users');
            $oldEmail = $user->where('id',$this->uid)->value('email');
            if(Db::name('users')->where([['email','=',$data['email']],['id','neq',$this->uid]])->find()){
                return ['code'=>0,'msg'=>'该邮箱已被注册！'];
            }

            if($oldEmail != $data['email']){
                $data['email_validated'] = 0;
            }
            if (Db::name('users')->where('id',$this->uid)->update($data)!==false) {
                $result['msg'] = '编辑资料成功!';
                $result['code'] = 1;
            } else {
                $result['msg'] = '编辑资料失败!';
                $result['code'] = 0;
            }
            return $result;
        }else{
            $province = Db::name('Region')->where ('pid',1)->select ();
            $this->assign('province',$province);
            $city = Db::name('Region')->where ( 'pid',$this->userInfo['province'])->select ();
            $this->assign('city',$city);
            $district = Db::name('Region')->where ('pid',$this->userInfo['city'])->select ();
            $this->assign('district',$district);
            $this->assign('title','基本设置');
            return $this->fetch();
        }
    }
    public function getRegion(){
        $list=Db::name("region")->where('pid',input("pid"))->select();
        return $list;
    }
    public function avatar(){
        $data = input('post.');
        db('users')->where(['id'=>$this->uid])->update($data);
        return true;
    }
    /**
     * 修改密码
     * @param $old_password  旧密码
     * @param $new_password  新密码
     * @param $confirm_password 确认新 密码
     */
    public function repass(){
        $old_password  = input('post.nowpass');
        $new_password = input('post.pass');
        $confirm_password = input('post.repass');

        if(strlen($new_password) < 6)
            return array('code'=>0,'msg'=>'密码不能低于6位字符');
        if($new_password != $confirm_password)
            return array('code'=>0,'msg'=>'两次密码输入不一致');
        //验证原密码
        if(($this->userInfo['password'] != '' && md5($old_password) != $this->userInfo['password']))
            return array('code'=>0,'msg'=>'密码验证失败');
        if(db('users')->where("id", $this->uid)->update(array('password'=>md5($new_password)))!==false){
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            $user_IP = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
            UserBindLog::create([
                'user_id'   => $this->uid,
                'type'      => UserBindLog::type6,
                'ip'        => $user_IP,
                'create_time'   => date('Y-m-d H:i:s',time()),

            ]);
            return array('code' =>1,'msg'=>'修改成功');
        }else{
            return array('code' =>0,'msg'=>'修改失败');
        }
    }

    //修改资金密码
    public function reZJpass(){
        $old_password  = input('post.oldSafe');
        $new_password = input('post.newSafe');
        $confirm_password = input('post.reSafe');

        if(strlen($new_password) < 6)
            return array('code'=>0,'msg'=>'密码不能低于6位字符');
        if($new_password != $confirm_password)
            return array('code'=>0,'msg'=>'两次密码输入不一致');
        //验证原密码
        if(($this->userInfo['safeword'] != '' && $old_password != $this->userInfo['safeword']))
            return array('code'=>0,'msg'=>'密码验证失败');
        if(db('users')->where("id", $this->uid)->update(array('safeword'=>$new_password))!==false){
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            $user_IP = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
            UserBindLog::create([
                'user_id'   => $this->uid,
                'type'      => UserBindLog::type7,
                'ip'        => $user_IP,
                'create_time'   => date('Y-m-d H:i:s',time()),

            ]);
            return array('code' =>1,'msg'=>'修改成功');
        }else{
            return array('code' =>0,'msg'=>'修改失败');
        }
    }

    //绑定邮箱
    public function bindEmail()
    {
        $email  = input('post.email');
        $code = input('post.code'); //邮箱验证码

        if(!is_email($email)){
            return ['code' => 0, 'msg' => '邮箱格式不正确'];
        }
        //获取邮箱验证码
        $email_code = true;
        //验证原密码
        if($code != $email_code)
            return array('code' => 0,'msg' => '验证码不正确');
        if(db('users')->where("id", $this->uid)->value('email')){
            return array('code' => 0,'msg' => '已绑定邮箱');
        }
        if(db('users')->where("id", $this->uid)->update(array('email'=>$email))!==false){
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            $user_IP = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
            UserBindLog::create([
                'user_id'   => $this->uid,
                'type'      => UserBindLog::type3,
                'ip'        => $user_IP,
                'create_time'   => date('Y-m-d H:i:s',time()),

            ]);
            return array('code' =>1,'msg'=>'绑定成功');
        }else{
            return array('code' =>0,'msg'=>'绑定失败');
        }
    }

    //绑定手机
    public function bindMobile()
    {
        $mobile  = input('post.mobile');
        $code = input('post.code'); //手机验证码
        dump($mobile);
        //获取手机验证码
        $mobile_code = session('user_'.$mobile);
        //验证原密码
        if($code != $mobile_code)
            return array('code' => 0,'msg' => '验证码不正确');

        if(db('users')->where("id", $this->uid)->update(array('mobile'=>$mobile))!==false){
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            $user_IP = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
            UserBindLog::create([
                'user_id'   => $this->uid,
                'type'      => UserBindLog::type4,
                'ip'        => $user_IP,
                'create_time'   => date('Y-m-d H:i:s',time()),

            ]);
            return array('code' =>1,'msg'=>'绑定成功');
        }else{
            return array('code' =>0,'msg'=>'绑定失败');
        }
    }


    //上传头像
    public function uploadAvatar()
    {
        $avator  = input('post.avatar');

        if(db('users')->where("id", $this->uid)->update(array('avatar'=>$avator))!==false){


            return array('code' =>1,'msg'=>'上传成功');
        }else{
            return array('code' =>0,'msg'=>'上传失败');
        }

    }

    //绑定手机
    public function editMobile()
    {
        $mobile  = input('post.mobile');
        $code = input('post.code'); //手机验证码

        //获取手机验证码
        $mobile_code = session('user_'.$mobile);
        //验证原密码
        if($code != $mobile_code)
            return array('code' => 0,'msg' => '验证码不正确');

        if(db('users')->where("id", $this->uid)->update(array('mobile'=>$mobile))!==false){
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            $user_IP = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
            UserBindLog::create([
                'user_id'   => $this->uid,
                'type'      => UserBindLog::type4,
                'ip'        => $user_IP,
                'create_time'   => date('Y-m-d H:i:s',time()),

            ]);
            return array('code' =>1,'msg'=>'绑定成功');
        }else{
            return array('code' =>0,'msg'=>'绑定失败');
        }
    }

    public function unbind(){
        db('oauth')->where("uid",$this->uid)->delete();
        session('user.qq','0');
        return array('code'=>1,'msg'=>'QQ已解绑','action'=>url('index'));
    }

    //个人设置
    public function setup()
    {
        //获取用户的收款账号信息
        $userModel = new Users();
        $id = session('user.id');
        if(empty($id))
            $this->redirect('home/index/index');
        $user_info = $userModel->where(['id' => $id])->find();
        $bank_name = db('bank')->where(['id' => $user_info['bank_id']])->value('bank_name');
        $user_info['bank_name'] = $bank_name;
        $account_num = 0;
        if(!empty($user_info['bank_account']) && !empty($user_info['alipay_account']) && !empty($user_info['weixin_account'])){
            $account_num = 3;
        }
        $bank = db('Bank')->order('id ASC')->select(); //银行列表
        $province   = db('Region')->where ( array('pid'=>1) )->select();
        $this->assign('province',$province);
        $this->assign('bank_list', $bank); //银行列表
        $this->assign('user_info', $user_info);
        $this->assign('account_num', $account_num);
        return $this->fetch('personSetting');
    }

    //保存个人设置
    public function personSetSave()
    {
        if(request()->isPost()){
            $data = input('post.');
            $userModel = new Users();
            $id = $data['id'];
            if($data['type'] == 1){
                if(empty($data['username']) || empty($data['bank_id']) || empty($data['province']) || empty($data['city']) || empty($data['bank_account']) || empty($data['id']) || empty($data['bank_pass']) || empty($data['type'])){
                    return ['code' => 0, 'msg' => '缺少必填项'];
                }
                //添加银行卡
                $bank = $userModel->where(['id' => $id])->value('bank_id');
                if($bank){
                    return ['code' => 0, 'msg' => '已绑定银行卡'];
                }

                $data['is_report'] = 1; //银行卡报备
                $res = Users::update($data);


            }elseif($data['type'] == 2){

                //添加支付宝
                if(empty($data['username']) || empty($data['id']) || empty($data['alipay_account']) || empty($data['type']) || empty($data['alipay_img_code']) || empty($data['alipay_pass'])){
                    return ['code' => 0, 'msg' => '缺少必填项'];
                }
                $bank = $userModel->where(['id' => $data['id']])->value('alipay_account');
                if($bank){
                    return ['code' => 0, 'msg' => '已绑定支付宝'];
                }
                $res = Users::update($data);

            }elseif($data['type'] == 3){
                //添加微信
                if(empty($data['username']) || empty($data['id']) || empty($data['weixin_account']) || empty($data['type']) || empty($data['weixin_img_code']) || empty($data['weixin_pass'])){
                    return ['code' => 0, 'msg' => '缺少必填项'];
                }
                $bank = $userModel->where(['id' => $data['id']])->value('weixin_account');
                if($bank){
                    return ['code' => 0, 'msg' => '已绑定微信'];
                }
                $res =  Users::update($data);

            }

            if($res !== false){
                return ['code' => 1, 'msg' => '添加成功'];
            }else{
                return ['code' => 0, 'msg' => '添加失败'];
            }

        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }
    }

    public function getRegion2(){
        $Region=db("region");
        $pid = input("pid");
        $map['pid'] = $pid;
        $list= $Region->where($map)->select();
        return $list;
    }

    //删除支付信息
    public function delPaymethod()
    {
        $data = input('post.');
        if(request()->isPost()){
            if(empty($data['id']) || empty($data['type'])){
                return ['code' => 0, 'msg' => '非法请求'];
            }
            if($data['type'] == 1){
                $save['bank_id'] = 0;
                $save['bank_user'] = '';
                $save['bank_account'] = '';
                $save['bank_pass'] = '';
                $save['bank_desc'] = '';
                $res = Users::where(['id' => $data['id']])->update($save);
            }elseif ($data['type'] == 2){
                $save['alipay_account'] = '';
                $save['alipay_img_code'] = '';
                $save['alipay_pass'] = '';
                $res = Users::where(['id' => $data['id']])->update($save);
            }elseif ($data['type'] == 3){
                $save['weixin_account'] = '';
                $save['weixin_img_code'] = '';
                $save['weixin_pass'] = '';
                $res = Users::where(['id' => $data['id']])->update($save);
            }
        }

        if($res !== false){
            return ['code' => 1, 'msg' => '删除成功'];
        }else{
            return ['code' => 0, 'msg' => '删除失败'];
        }

    }


    //账号安全
    public function security()
    {
        //查询用户信息
        $user_id = session('user.id');
        $user_info = get_user_info($user_id,0);
        session('user',$user_info);
        if(empty($user_info))
            $this->redirect('home/index/index');
        $userLoingModel = new UserLoginLog();
        $userBindModel = new UserBindLog();
        //安全级别
        $num = 0;
        if(empty($user_info['mobile'])){
            $num ++;
        }
        if(empty($user_info['email'])){
            $num ++;
        }
        if(empty($user_info['username'])){
            $num ++;
        }
        //登录记录
        $login_list = $userLoingModel->where(['user_id' => $user_info['id']])->limit(20)->select();
        foreach ($login_list as $key => $val){
            $login_list[$key]['status'] = UserLoginLog::$status[$val['status']];
        }

        //绑定记录
        $bind_list = $userBindModel->where(['user_id' => $user_info['id']])->limit(20)->select();
        foreach ($bind_list as $key => $val){
            $bind_list[$key]['type'] = UserBindLog::$type_list[$val['type']];
        }
        $this->assign('user_info', $user_info);
        $this->assign('login_list', $login_list);
        $this->assign('bind_list', $bind_list);
        $this->assign('num', $num);
        return $this->fetch('security');
    }

}