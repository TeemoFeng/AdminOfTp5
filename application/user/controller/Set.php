<?php
namespace app\user\controller;
use app\user\model\Users;
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
                return ['status'=>0,'msg'=>'该邮箱已被注册！'];
            }

            if($oldEmail != $data['email']){
                $data['email_validated'] = 0;
            }
            if (Db::name('users')->where('id',$this->uid)->update($data)!==false) {
                $result['msg'] = '编辑资料成功!';
                $result['status'] = 1;
            } else {
                $result['msg'] = '编辑资料失败!';
                $result['status'] = 0;
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
            return array('status'=>0,'msg'=>'密码不能低于6位字符');
        if($new_password != $confirm_password)
            return array('status'=>0,'msg'=>'两次密码输入不一致');
        //验证原密码
        if(($this->userInfo['password'] != '' && md5($old_password) != $this->userInfo['password']))
            return array('status'=>0,'msg'=>'密码验证失败');
        if(db('users')->where("id", $this->uid)->update(array('password'=>md5($new_password)))!==false){

            return array('status'=>1,'msg'=>'修改成功','action'=>url('index'));
        }else{
            return array('status'=>0,'msg'=>'修改失败');
        }
    }
    public function unbind(){
        db('oauth')->where("uid",$this->uid)->delete();
        session('user.qq','0');
        return array('status'=>1,'msg'=>'QQ已解绑','action'=>url('index'));
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
        if(!empty($user_info['bank_account']) && !empty($user_info['alipay_account']) && !empty($user_info['wexin_account'])){
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

            if(empty($data['bank_user']) || empty($data['bank_id']) || empty($data['province']) || empty($data['city']) || empty($data['bank_account']) || empty($data['id']) || empty($data['safeword']) || empty($data['type'])){
                return ['code' => 0, 'msg' => '缺少必填项'];
            }
            $userModel = new Users();
            $id = $data['id'];
            if($data['type'] == 1){
                //添加银行卡
                $bank = $userModel->where(['id' => $id])->value('bank_id');
                if($bank){
                    return ['code' => 0, 'msg' => '已绑定银行卡'];
                }
                $res = Users::update($data);


            }elseif($data['type'] == 2){
                dump($data);die;
                //添加支付宝
                $bank = $userModel->where(['id' => $data['id']])->value('alipay_account');
                if($bank){
                    return ['code' => 0, 'msg' => '已绑定支付宝'];
                }
                $res = Users::update($data);

            }elseif($data['type'] == 3){
                //添加微信
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


}