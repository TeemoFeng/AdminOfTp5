<?php
namespace app\user\controller;
use app\user\model\UserLoginLog;
use think\Controller;
use think\facade\Request;
use think\captcha\Captcha;
class Login extends Common {
    protected $sys;
    public function initialize(){
//        if (session('user.id')) {
//            $this->redirect('index/index');
//        }
        $this->sys = cache('System');
        $this->assign('sys',$this->sys);
    }
    public function index(){

        $table = db('users');
        if(request()->isPost()) {
            $username = input('username');
            $password = input('password');

            $login_method = isMobile();
            if($login_method === true){
                $login_method = 'APP';
                $url = url('mobile/index/index');
            }else{
                if($this->sys['code']=='open'){
                    $code = input('vercode');
                    if(!$this->check($code)){
                        return array('code' => 0, 'msg' => '验证码错误');
                    }
                }
                $login_method = 'WEB';
                $url = url('home/index/index');
            }

            if(!$username || !$password){
                return array('code'=>0,'msg'=>'请填写账号或密码');
            }

            $user = $table->where("usernum","=",$username)->find();
            $userLogin = new UserLoginLog();
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            $user_IP = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];


            if(!$user){
                return array('code'=>0,'msg'=>'账号不存在!');
            }elseif(md5($password) != $user['password']){

                $userLogin->save([
                    'user_id'       => $user['id'],
                    'login_method'  => $login_method,
                    'status'        => 0,
                    'ip'            => $user_IP,
                    'create_time'   => date('Y-m-d H:i:s',time()),
                ]);
                return array('code'=>0,'msg'=>'密码错误!');
            }elseif($user['is_lock'] == 1){
                return array('code'=>0,'msg'=>'账号异常已被锁定！！！');
            }else{
                $sessionUser = $table->where("id",'=',$user['id'])->find();
                $openid = db('oauth')->where([['uid','=',$user['id']],['type','=','qq']])->value('openid');
                if($openid){
                    $sessionUser['qq'] = 1;
                }
                //记录用户登录日志
                $userLogin->save([
                    'user_id'       =>  $user['id'],
                    'login_method'  =>  $login_method,
                    'status'        => 1,
                    'ip'            => $user_IP,
                    'create_time'   => date('Y-m-d H:i:s',time()),
                ]);

                session('user',$sessionUser);
                return array('code'=>1,'msg'=>'登录成功','url' => $url);
            }
        }elseif(request()->isGet() && !empty(input('get.mobile'))){
            $mobile = input('get.mobile');

            $password = input('get.password');
            $user = $table->where(["usernum" => $mobile, 'password' => $password])->find();

            session('user', $user);
            return $this->redirect(url('user/index/index'));


        }else{
            $plugin = db('plugin')->where(['type'=>'login','status'=>1])->select();
            $this->assign('plugin', $plugin);
            $this->assign('title','会员登录');
            return $this->fetch('login');
        }
    }
    public function verify(){
        $config =    [
            // 验证码字体大小
            'fontSize'    =>    25,
            // 验证码位数
            'length'      =>    4,
            // 关闭验证码杂点
            'useNoise'    =>    false,
            'bg'          =>    [255,255,255],
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }
    public function check($code){
        return captcha_check($code);
    }

    public function reg(){
        if(Request::isAjax()) {
            $data = input('post.');
            if(!$data['email'] || !$data['password']){
                return array('code'=>-1,'msg'=>'请输入手机号或密码');
            }
            $code = $data['code'];
            $mobile_send = session('mobile_'.$data['email']);
            $code_time = $mobile_send->time;
            $mobile_code = $mobile_send->code;
            if((time()-$code_time) > 300){
                return array('code' => 0, 'msg' => '验证码已过期，请重新发送');
            }
            if ($mobile_code != $code) {
                return array('code' => 0, 'msg' => '验证码错误');
            }else{
                session('mobile_'.$data['email'],null); //清除session
            }

            $is_validated = 0 ;
            if(is_email($data['email'])){
                $is_validated = 1;
                $data['email_validated'] = 1;
                if(get_user_info($data['email'],1)){
                    return array('code'=>-1,'msg'=>'邮箱账号已存在');
                }
            }
            if(is_mobile_phone($data['email'])){
                $is_validated = 1;
                $data['mobile_validated'] = 1;
                $data['mobile'] = $data['email']; //手机注册
                unset($data['email']);
                if(get_user_info($data['mobile'],2)){
                    return array('code'=>-1,'msg'=>'手机账号已存在');
                }
            }
            if($is_validated != 1){
                return array('code'=>0,'msg'=>'请用手机号注册');
            }

            //验证两次密码是否匹配
            if($data['password'] != $data['password2']){
                return array('code'=>-1,'msg'=>'两次输入密码不一致');
            }
            unset($data['password2']);
            unset($data['vercode']);
            unset($data['code']);
            //验证是否存在用户名
            $user_num = createVipNum();
            $data['usernum'] = $user_num;
            $data['password'] = md5($data['password']);
            $data['reg_time'] = time();
            $data['pwd'] = lock_url($data['password']);
            $data['safeword'] = '123456';
            $id = db('users')->insertGetId($data);
            if($id === false){
                return array('code'=>-1,'msg'=>'注册失败');
            }
            $user = db('users')->field('id,username')->where("id", $id)->find();
            session('user',$user);
            return array('code'=>1,'msg'=>'注册成功','result'=>$user);
        }else {
            $plugin = db('plugin')->where(['type' => 'login', 'status' => 1])->select();
            $this->assign('plugin', $plugin);
            $this->assign('title', '会员注册');
            return $this->fetch('register');
        }
    }

    //找回密码
    public function retrieve()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['mobile']) || empty($data['code'])){
                return ['code' => 0, 'msg' => '手机号或者验证码不能为空'];
            }
            $table = db('users');
            //获取验证码待写
            $mobile_send = session('mobile_'.$data['mobile']);
            $code_time = $mobile_send->time;
            $mobile_code = $mobile_send->code;
            if((time()-$code_time) > 300){
                return array('code' => 0, 'msg' => '验证码已过期，请重新发送');
            }
            if ($mobile_code != $data['code']) {
                return array('code' => 0, 'msg' => '验证码错误');
            }else{
                session('mobile_'.$data['email'],null); //清除session
            }

            $user = $table->where("mobile","=",$data['mobile'])->find();
            if(!$user){
                return ['code' => 0, 'msg' => '用户不存在'];
            }
            //给用户发送他的密码
            if(!empty($user['mobile'])){
                //手机不空发送短信信息
                $pwd = unlock_url($user['pwd']);
                sendSmsForPass($user['mobile'], $pwd);
                return ['code' => 1, 'msg' => '密码已经发送到您的手机'];
            }else{
                //邮箱不空发送邮箱信息
                return ['code' => 0, 'msg' => '用户不存在'];
            }


        }else{
            return ['code' => 0, 'msg' =>'非法请求'];
        }
    }

    //找回密码旧
    public function forget(){
        if(request()->isPost()) {
            $sender = input('email');
            $code =input('code');
            $inValid = true;  //验证码失效
            if(!$code){
                return array('code'=>-1,'msg'=>'请输入邮件验证码');
            }
            $data = session('validate_code');
            $timeOut = $data['time'];
            if($data['code'] != $code || $data['sender']!=$sender){
                $inValid = false;
            }
            $password = input('password');
            $password2 = input('password2');
            if($password != $password2){
                return array('code'=>-1,'msg'=>'两次输入密码不一致');
            }
            if(empty($data)){
                return array('code'=>-1,'msg'=>'请先获取验证码');
            }elseif($timeOut < time()){
                return array('code'=>-1,'msg'=>'验证码已超时失效');
            }elseif(!$inValid) {
                return array('status'=>-1,'msg'=>'验证失败,验证码有误');
            }else{
                $data['is_check'] = 1; //标示验证通过
                session('validate_code',$data);
                db('users')->where('email',$sender)->update(['password'=>md5($password)]);
                return array('code'=>1,'msg'=>'密码找回成功！');
            }
        }else{
            $this->assign('title','找回密码');
            return $this->fetch('retrieve');
        }
    }
    /**
     * 发送邮件验证码
     * @param $sender 接收人
     * @return json
     */
    public function sendEmail(){
        if(request()->isPost()) {
            $sender = input('email');
            $sms_time_out = 180;
            //获取上一次的发送时间
            $send = session('validate_code');
            if (!empty($send) && $send['time'] > time() && $send['sender'] == $sender) {
                //在有效期范围内 相同号码不再发送
                return json(['code' => -1, 'msg' => '规定时间内,不要重复发送验证码']);
            }
            $code = mt_rand(1000, 9999);
            //检查是否邮箱格式
            if (!is_email($sender)) {
                return json(['code' => -1, 'msg' => '邮箱码格式有误']);
            }
            $send = send_email($sender, '验证码', '您好，你的验证码是：' . $code);
            if ($send) {
                $info['code'] = $code;
                $info['sender'] = $sender;
                $info['is_check'] = 0;
                $info['time'] = time() + $sms_time_out; //有效验证时间
                session('validate_code', $info);
                return json(['code' => 1, 'msg' => '验证码已发送，请注意查收']);
            }else{
                return array('code' => -1, 'msg' => '验证码发送失败,请联系管理员');
            }
        }else{
            return json(['code' => -1, 'msg' => '非法请求']);
        }
    }

    //发送短信验证码
    public function mobileSms()
    {
        $mobile = input('post.mobile');
        if(empty($mobile)){
            return ['code' => 0, 'msg' => '手机号码不能为空'];
        }
        if(!preg_match("/^1[34578]\d{9}$/", $mobile)){
            return ['code' => 0, 'msg' => '输入的手机号码不正确'];
        }
        //获取上一次的发送时间
        $send = session('mobile_'.$mobile);
        if (!empty($send) && (time()-$send->time) < 60) {
            //在有效期范围内 相同号码不再发送
            return ['code' => 0, 'msg' => '规定时间内,不要重复发送验证码'];
        }

        $code = (string)rand('100000','999999');
        $mobile_set = (object)[
            'code' => $code,
            'time' => time(),

        ];

        session('mobile_'.$mobile, $mobile_set);
        $res = sendSms($mobile, $code);
        $res = json_decode($res,true);
        if($res['Code'] == 0){
            return ['code' => 1, 'mobile_code' => $code, 'msg' => '短信发送成功'];
        }else{
            return ['code' => 0, 'msg' => '短信发送失败'];
        }
    }

    //校验短信验证码
    public function checkMobileSms()
    {

    }

}