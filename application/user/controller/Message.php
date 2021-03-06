<?php
/**
 * Created by PhpStorm.
 * User: Uroaming
 * Date: 2019/3/26
 * Time: 14:26
 */
namespace app\user\controller;
use app\user\model\UserMessage;
use think\console\Input;
use think\Db;
use think\db\Where;

class Message extends Common
{
    //发布消息
    public function publish()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['title']) || empty($data['content'])){
                return ['code' => 1, 'msg' => '标题或内容不能为空'];
            }
            $user_id = session('user.id');
            $save = [
                'from_id' => $user_id,
                'to_id'   => 0,
                'title'   => $data['title'],
                'content' => htmlspecialchars($data['content']),
                'status'  => 1,
                'create_time' => date('Y-m-d H:i:s', time()),
            ];
            if(UserMessage::create($save)){
                return ['code' => 1, 'msg' => '发送成功'];
            }
            return ['code' => 0, 'msg' => '发送失败，请重试'];

        }
        return $this->fetch('publish');
    }

    //消息列表
    public function messageList()
    {

        if(request()->isPost()){
            $data = input('post.');
            $user_id = session('user.id');
            if(empty($user_id)){
                return ['code' => 0, 'msg' => '用户不能为空'];
            }

            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            if($data['type'] == 1){ //发件箱
                $where['from_id'] = $user_id;

            }elseif($data['type'] == 2){
                $where['to_id'] = $user_id;
            }

            $list = db('user_message')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                ->toArray();

            if(empty($list))
                $list = [];
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['content'] = htmlspecialchars_decode($v['content']);
                if($v['is_read'] == 0){
                    $list['data'][$k]['read_str'] = '<span style="color: #FF5722;">未读</span>';
                }else{
                    $list['data'][$k]['read_str'] = '<span>已读</span>';
                }
                if($v['type'] == 1){
                    $list['data'][$k]['from_user'] = $this->userInfo['username'];
                    $list['data'][$k]['to_user'] = '管理员';
                }else{
                    $list['data'][$k]['from_user'] = '管理员';
                    $list['data'][$k]['to_user'] = $this->userInfo['username'];
                }

            }

            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }
        $type = 1;
        if(request()->isGet() && !empty(input('get.type'))){
            $type = input('get.type');
        }
        $this->assign('type', $type);
        return $this->fetch('messageList');
    }

    //信息详情
    public function lookUp()
    {
        $id = input('get.id');

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
                    $mes_list[$k]['from_user'] = $this->userInfo['username'];
                }else{
                    $mes_list[$k]['from_user'] = '管理员';
                }
            }
        }

        $this->assign('user_info',$this->userInfo);
        $this->assign('mes_info', $mes_info);
        $this->assign('mes_list', $mes_list);
        return $this->fetch('messageDetail');

    }

    private function findMessage($id)
    {
        $arr = [];
        $info = db('user_message_reply')
            ->where(['pid' => $id])
            ->find();
        if(!empty($info)){
            $arr[] = $info;
            $this->findMessage($info['id']);
        }
        return $arr;
    }

    //回复提交
    public function reply()
    {
        if(request()->isPost()){
            $data = input('post.');
            if(empty($data['content']) || empty($data['id'])){
                return ['code' => 0, 'msg' => '回复内容不能为空'];
            }

            $add = [
                'message_id' => $data['id'],
                'content' => $data['content'],
                'from_id' => $this->userInfo['id'],
                'to_id' => 0,
                'create_time'  => date('Y-m-d H:i:s'),
            ];
            $res =  Db::name('user_message_reply')->insert($add);
            if($res !== false){
                return ['code' => 1, 'msg' => '回复成功'];

            }else{
                return ['code' => 0, 'msg' => '回复失败'];

            }

        }else{
            return ['code' => 0, 'msg' => '非法请求'];
        }
    }


}
