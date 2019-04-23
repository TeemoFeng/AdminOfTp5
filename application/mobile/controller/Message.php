<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/4/23
 * Time: 10:00
 */
namespace app\mobile\controller;

use think\Db;

class Message extends Common
{
    //信息首页 默认收件箱
    public function index()
    {
        return $this->fetch('index');
    }

    //发件箱
    public function outbox()
    {
        return $this->fetch('outbox');
    }

    //发布信息
    public function publish()
    {
        return $this->fetch('publish');
    }

    //查看信息详情
    //信息详情
    public function lookUp()
    {
        $id = input('get.id');
        $user_info = session('user');
        $mes_info = db('user_message')->where(['id' => $id])->find(); //查询信息表id
        if($mes_info['to_id'] == 0){
            //发件箱
            $user_name = Db::name('users')->where(['id' => $mes_info['from_id']])->value('username');
            $mes_info['username'] = $user_name;

        }else{
            //收件箱
            $mes_info['username'] = '管理员';
            Db::name('user_message')->where(['id' => $id])->update(['is_read' => 1]);

        }
        $mes_list = db('user_message_reply')
            ->where(['message_id' => $id])
            ->order('create_time ASC')
            ->select();

        //查询是否存在回复信息
        if(empty($mes_list)){
            $mes_list = [];
        }else{
            foreach ($mes_list as $k => $v){
                if($v['to_id'] == 0){
                    $mes_list[$k]['from_user'] = $user_info['username'];
                }else{
                    $mes_list[$k]['from_user'] = '管理员';
                }
            }
        }

        $this->assign('user_info',$user_info);
        $this->assign('mes_info', $mes_info);
        $this->assign('mes_list', $mes_list);
        return $this->fetch('messageDetail');

    }


}