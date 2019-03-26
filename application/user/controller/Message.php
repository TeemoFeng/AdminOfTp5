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
                'title'   => $data['title'],
                'content' => htmlspecialchars($data['content']),
                'status'  => 1,
                'type'    => 1,
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
            if(empty($data['type']) || empty($user_id)){
                return ['code' => 0, 'msg' => '账户类型或用户不能为空'];
            }

            $page   = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            if($data['type'] == 1){ //发件箱
                $where['a.type'] = 1;
                $where['a.from_id'] = $user_id;
                $list = db('user_message')
                    ->alias('a')
                    ->join(config('database.prefix').'users u','a.from_id = u.id','left')
                    ->field('a.*,u.username')
                    ->where($where)
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();

            }else{
                $where['a.type'] = 2;
                $where['to_id'] = $user_id;
                $list = db('user_message')
                    ->alias('a')
                    ->join(config('database.prefix').'users u','a.to_id = u.id','left')
                    ->field('a.*,u.username')
                    ->where($where)
                    ->paginate(array('list_rows'=>$pageSize, 'page'=>$page))
                    ->toArray();
            }


            if(empty($list))
                $list = [];
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['content'] = htmlspecialchars_decode($v['content']);
                if($data['type'] == 1){
                    $list['data'][$k]['from_user'] = $v['username'];
                    $list['data'][$k]['to_user'] = '管理员';
                }else{
                    $list['data'][$k]['from_user'] = '管理员';
                    $list['data'][$k]['to_user'] = $v['username'];
                }
            }

            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }

        return $this->fetch('messageList');
    }






}
