<?php
namespace app\user\controller;
use think\Db;
use kuange\qqconnect\QC;
use think\Controller;
class Callback extends Controller{
    public function qq()
    {
        $qc = new QC();
        $access_token =  $qc->qq_callback();
        $openid = $qc->get_openid();
        $qc = new QC($access_token, $openid);
        $qq_user_info = $qc->get_user_info();
        // 待处理用户逻辑
        $uid = Db::name('oauth')->where([['openid','=',$openid],['type','=','qq']])->value('uid');
        if (session('user.id')) {
            //绑定QQ
            if($uid){
                $this->error('该QQ号已绑定账号！','index/index');
            }else{
                if (session('user.avatar') == '') {
                    Db::name('users')->where('id', session('user.id'))->update(['avatar' => $qq_user_info['figureurl_qq_2']]);
                }
                if (session('user.username') == '') {
                    Db::name('users')->where('id', session('user.id'))->update(['username' => $qq_user_info['nickname']]);
                }
                $data['uid'] = session('user.id');
                $data['openid'] = $openid;
                $data['type'] ='qq';
                Db::name('oauth')->insert($data);
                Db::name('users')->where('id',session('user.id'))->update(['last_login'=>time()]);
                $user = Db::name('users')->where('id',session('user.id'))->find();
                $user['qq']='1';
                session('user',$user);
                $this->success('QQ号绑定成功！','index/index');
            }
        }else{
            if($uid){
                $user = Db::name('users')->where('id', $uid)->find();
                if ($user['avatar'] == '') {
                    Db::name('users')->where('id', $uid)->update(['avatar' => $qq_user_info['figureurl_qq_2']]);
                }
                if ($user['username'] == '') {
                    Db::name('users')->where('id', $uid)->update(['username' => $qq_user_info['nickname']]);
                }
                Db::name('users')->where('id',$uid)->update(['last_login'=>time()]);
                $user = Db::name('users')->where('id',$uid)->find();
                $user['qq']='1';
                session('user',$user);
                $this->success('登录成功！','index/index');
            }else{
                $data['username'] =  $qq_user_info['nickname'];
                $data['avatar'] =  $qq_user_info['figureurl_qq_2'];
                $data['reg_time'] =  time();
                $data['last_login'] =  time();
                $data['password'] =  md5('123456');
                $data['sex'] =  ($qq_user_info['gender']=='男')?1:0;
                $data2['uid'] = Db::name('users')->insertGetId($data);
                $data2['openid'] = $openid;
                $data2['type'] = 'qq';
                Db::name('oauth')->insert($data2);

                $user = Db::name('users')->where('id',$data2['uid'])->find();
                $user['qq']='1';
                session('user',$user);

                $this->success('登录成功！','index/index');
            }
        }
    }
}