<?php
namespace app\admin\controller;
use app\admin\model\BonusSet;
use app\admin\model\UserCurrencyAccount;
use app\admin\model\UserNode;
use app\admin\model\UserReferee;
use app\admin\model\Users as UsersModel;
use app\user\controller\User;
use app\user\model\UserRunningLog;
use think\console\Input;
use think\Db;
use think\db\Where;
use think\Validate;

class Users extends Common
{
    //会员列表
    public function index()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $where = $this->makeSearch($data);
            $page = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $list = db('users')->alias('u')
                ->join(config('database.prefix') . 'user_level ul', 'u.level = ul.level_id', 'left')
                ->join(config('database.prefix') . 'user_currency_account c', 'c.user_id = u.id', 'left')
                ->join(config('database.prefix') . 'bonus_set d', 'ul.level_id = d.level_id', 'left')
                ->field('u.*,ul.level_name,c.cash_currency_num,c.cash_input_num,c.corpus,c.activation_num,c.consume_num,c.transaction_num,c.rate,d.declaration')
                ->where($where)
                ->order('u.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['reg_time'] = date('Y-m-d H:s', $v['reg_time']);
                $list['data'][$k]['active_time'] = date('Y-m-d H:s', $v['active_time']);
                $list['data'][$k]['status'] = UsersModel::$acstatus[$v['status']];
                $list['data'][$k]['enabled'] = UsersModel::$vastatus[$v['enabled']];
                $list['data'][$k]['baodan_center'] = UsersModel::$bdstatus[$v['baodan_center']];
                $list['data'][$k]['is_report'] = UsersModel::$yhstatus[$v['is_report']];
                $tuijian_user = UsersModel::where(['id' => $v['pid']])->value('username');
                $jidianren_user = UsersModel::where(['id' => $v['npid']])->value('username');
                $baodan_user = UsersModel::where(['usernum' => $v['baodan_user']])->value('username');
                $list['data'][$k]['referee'] = $v['referee'] . '【' . $tuijian_user . '】';
                $list['data'][$k]['contact_person'] = $v['contact_person'] . '【' . $jidianren_user . '】';
                $list['data'][$k]['baodan_user'] = $v['baodan_user'] . '【' . $baodan_user . '】';
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
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
        $where['u.status'] = 1;
        if (!empty($data['status'])) {
            if ($data['status'] == 1) {
                $where['u.enabled'] = 0; //无效会员
            }
            if ($data['status'] == 2) {
                $where['u.enabled'] = 1; //有效会员
            }
            if ($data['status'] == 3) {
                $where['u.is_lock'] = 1; //冻结会员
            }
            if ($data['status'] == 4) {
                $where['u.baodan_center'] = 1; //报单中心
            }
        }
        if (!empty($data['start_time']) && empty($data['end_time'])) {
            $start_time = strtotime($data['start_time'] . ' 00:00:00');
            $where['u.reg_time'] = array('egt', $start_time);
        }
        if (!empty($data['end_time']) && empty($data['start_time'])) {
            $end_time = strtotime($data['end_time'] . ' 23:59:59');
            $where['u.reg_time'] = array('elt', $end_time);
        }
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $start_time = strtotime($data['start_time'] . ' 00:00:00');
            $end_time = strtotime($data['end_time'] . ' 23:59:59');
            $where['u.reg_time'] = array('between time', array($start_time, $end_time));
        }
        if (!empty($data['key'])) {
            $where['u.id|u.usernum|u.email|u.mobile|u.username'] = array('like', '%' . $data['key'] . '%');
        }
        return $where;
    }

    //设置会员状态
    public function usersState()
    {
        $id = input('post.id');
        $is_lock = input('post.is_lock');
        if (db('users')->where('id=' . $id)->update(['is_lock' => $is_lock]) !== false) {
            return ['status' => 1, 'msg' => '设置成功!'];
        } else {
            return ['status' => 0, 'msg' => '设置失败!'];
        }
    }

    public function edit($id = '')
    {
        if (request()->isPost()) {
            $user = db('users');
            $data = input('post.');
            $level = explode(':', $data['level']);
            $data['level'] = $level[1];
            $province = explode(':', $data['province']);
            $data['province'] = isset($province[1]) ? $province[1] : '';
            $city = explode(':', $data['city']);
            $data['city'] = isset($city[1]) ? $city[1] : '';
            $district = explode(':', $data['district']);
            $data['district'] = isset($district[1]) ? $district[1] : '';
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = md5($data['password']);
            }
            if ($user->update($data) !== false) {
                $result['msg'] = '会员修改成功!';
                $result['url'] = url('index');
                $result['code'] = 1;
            } else {
                $result['msg'] = '会员修改失败!';
                $result['code'] = 0;
            }
            return $result;
        } else {
            $province = db('Region')->where(array('pid' => 1))->select();
            $user_level = db('user_level')->order('sort')->select();
            $info = UsersModel::get($id);
            $bank = db('Bank')->order('id ASC')->select();

            $this->assign('info', json_encode($info, true));
            $this->assign('title', lang('edit') . lang('user'));
            $this->assign('province', json_encode($province, true));
            $this->assign('user_level', json_encode($user_level, true));

            $this->assign('bank', json_encode($bank, true)); //银行列表
            $city = db('Region')->where(array('pid' => $info['province']))->select();
            $this->assign('city', json_encode($city, true));
            $district = db('Region')->where(array('pid' => $info['city']))->select();
            $this->assign('district', json_encode($district, true));
            return $this->fetch();
        }
    }

    public function getRegion()
    {
        $Region = db("region");
        $pid = input("pid");
        $arr = explode(':', $pid);
        $map['pid'] = $arr[1];
        $list = $Region->where($map)->select();
        return $list;
    }

    public function usersDel()
    {
        db('users')->delete(['id' => input('id')]);
        db('oauth')->delete(['uid' => input('id')]);
        return $result = ['code' => 1, 'msg' => '删除成功!'];
    }


    public function delall()
    {
        $map[] = array('id', 'IN', input('param.ids/a'));
        db('users')->where($map)->delete();
        $result['msg'] = '删除成功！';
        $result['code'] = 1;
        $result['url'] = url('index');
        return $result;
    }

    //重置密码
    public function resetPas()
    {
        $map[] = array('id', 'IN', input('param.ids/a'));
        $data['password'] = md5('123456');
        $data['safeword'] = '123456';
        $res = db('users')->where($map)->update($data);
        if ($res === false) {
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

        $ids = input('param.ids');
        $map[] = array('id', 'IN', input('param.ids/a'));
        $data['baodan_center'] = 1;
        $res = db('users')->where($map)->update($data);
        if ($res === false) {
            return ['code' => 0, 'msg' => '设置失败，请重试'];
        }

//        $bonus_ratio = BonusSet::where(['level_id' => 5])->find();
//        $baodan_ratio = Db::name('bonus_ext_set')->where(['id' => 1])->value('baodan_ratio');
//        $baodan_ratio = bcdiv($baodan_ratio, 100, 2);
//        $jiangli = bcmul($bonus_ratio['declaration'],$baodan_ratio, 4); //保单f4奖励

        foreach ($ids as $v) {
            //查询用户是否已经是保单中心
            $baodan_center = Db::name('users')->where(['id' => $v])->value('baodan_center');
            if ($baodan_center == 1) {
                continue;
            }

//            $user_account = UserCurrencyAccount::where(['user_id' => $v])->find();
//            $cash_num = bcadd($user_account['cash_currency_num'], $jiangli, 4); //现金币
//            $corpus = bcadd($user_account['corpus'], $bonus_ratio['declaration'], 4); //本金账户
//            UserCurrencyAccount::where(['user_id' => $v])->update(['cash_currency_num' => $cash_num, 'corpus' => $corpus]);
            //记录用户f4奖励
//            UserRunningLog::create([
//                'user_id'  =>  $v,
//                'about_id' =>  $v,
//                'running_type'  => UserRunningLog::TYPE21,
//                'account_type'  => 1,
//                'change_num'    => $jiangli,
//                'balance'       => $cash_num,
//                'create_time'   => time(),
//                'remark'        => '报单中心奖励'
//            ]);
        }

        return ['code' => 1, 'msg' => '设置报单中心成功', 'url' => url('index')];
    }

    //取消报单中心
    public function cancelBaodan()
    {
        $ids = input('param.ids');
        $map[] = array('id', 'IN', input('param.ids/a'));
        $data['baodan_center'] = 0;

        $res = db('users')->where($map)->update($data);
        if ($res === false) {
            return ['code' => 0, 'msg' => '设置失败，请重试'];
        }

        return ['code' => 1, 'msg' => '取消报单中心成功', 'url' => url('index')];
    }

    //访问前台
    public function userJump()
    {
        $user_id = input('id');
        $user_info = UsersModel::get($user_id);
        if (empty($user_info)) {
            return ['code' => 0, 'msg' => '未找到该用户'];

        } else {
            if (empty($user_info['mobile'])) {
                $info['username'] = $user_info['email'];
            } else {
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
        if (empty($user_currency_account)) {
            $user_currency_account['cash_currency_num'] = '0.0000';
            $user_currency_account['corpus'] = '0.0000';
            $user_currency_account['activation_num'] = '0.0000';
            $user_currency_account['consume_num'] = '0.0000';
            $user_currency_account['transaction_num'] = '0.0000';
        }
        $user_level = db('user_level')->where(['level_id' => $user_info['level']])->find();
        $user_info['level_name'] = $user_level['level_name'];
        if (empty($user_info['email'])) {
            $user_info['email'] = '无';
        }

        if ($user_info['enabled'] == 0) {
            $user_info['enabled'] = '不是有效会员';
        } else {
            $user_info['enabled'] = '是有效会员';
        }
        if ($user_info['baodan_center'] == 0) {
            $user_info['baodan_center'] = '否';
        } else {
            $user_info['baodan_center'] = '是';
        }
        $this->assign('user_info', $user_info);
        $this->assign('user_currency_account', $user_currency_account);
        return $this->fetch('userDetail');

    }


    /***********************************会员组***********************************/
    public function userGroup()
    {
        if (request()->isPost()) {
            $list = db('user_level')
                ->alias('a')
                ->join(config('database.prefix') . 'bonus_set b', 'a.level_id = b.level_id', 'left')
                ->order('a.sort')
                ->select();

            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list, 'rel' => 1];
        }
        return $this->fetch();
    }

    public function groupAdd()
    {
        if (request()->isPost()) {
            $data = input('post.');
            db('user_level')->insert($data);
            $result['msg'] = '会员组添加成功!';
            $result['url'] = url('userGroup');
            $result['code'] = 1;
            return $result;
        } else {
            $this->assign('title', lang('add') . "会员组");
            $this->assign('info', 'null');
            return $this->fetch('groupForm');
        }
    }

    public function groupEdit()
    {
        if (request()->isPost()) {
            $data2 = $data = input('post.');
            unset($data['declaration']);
            db('user_level')->update($data);
            db('bonus_set')->where(['level_id' => $data2['level_id']])->update(['declaration' => $data2['declaration']]);

            $result['msg'] = '会员组修改成功!';
            $result['url'] = url('userGroup');
            $result['code'] = 1;
            return $result;
        } else {
            $map['a.level_id'] = input('param.level_id');
            $info = db('user_level')
                ->alias('a')
                ->where($map)
                ->join(config('database.prefix') . 'bonus_set b', 'a.level_id = b.level_id', 'left')
                ->find();
            $this->assign('title', lang('edit') . "会员组");
            $this->assign('info', json_encode($info, true));
            return $this->fetch('groupForm');
        }
    }

    public function groupDel()
    {
        $level_id = input('level_id');
        if (empty($level_id)) {
            return ['code' => 0, 'msg' => '会员组ID不存在！'];
        }
        db('user_level')->where(array('level_id' => $level_id))->delete();
        return ['code' => 1, 'msg' => '删除成功！'];
    }

    public function groupOrder()
    {
        $userLevel = db('user_level');
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
            $data = input('post.');
//            $province       = explode(':',$data['province']);

//            $data['province'] = isset($province[1]) ? $province[1] : '';
//            $city           = explode(':',$data['city']);
//            $data['city']   = isset( $city[1]) ? $city[1] : '';
//            $district       = explode(':',$data['district']);
//            $data['district'] = isset( $district[1]) ? $district[1] : '';

            if (empty($data['mobile'])) return ['code' => 0, 'msg' => '手机号不能为空'];
            //验证
            $check = [
                'usernum' => $data['usernum'],
                'username' => $data['username'],
                'mobile' => $data['mobile'],
                'password' => $data['password'],
                'confirmPwd' => $data['confirmPwd'],
                'safeword' => $data['safeword'],
                'confirmSafePwd' => $data['confirmSafePwd'],
            ];
            //检测
            $validate = new \app\admin\validate\Users();
            $result = $validate->check($check);
            if (!$result) {
                return ['code' => 0, 'msg' => $validate->getError()];
            }

            //验证会员编号是否重复
            $check_user = UsersModel::where(['usernum' => $data['usernum']])->find();
            if ($check_user) {
                return $result = ['code' => 0, 'msg' => '该会员编号已存在'];
            }

            //检测密码是否相等
            if ($data['password'] != $data['confirmPwd']) return ['code' => 0, 'msg' => '两次输入的登录密码不一致'];
            if ($data['safeword'] != $data['confirmSafePwd']) return ['code' => 0, 'msg' => '两次输入的安全密码不一致'];

            $data['password'] = md5($data['password']);
            $data['pwd'] = lock_url($data['password']);
            $data['nickname'] = $data['username'];
            //接入用户和推荐人关系
            $referrr_info = UsersModel::get(['usernum' => $data['referee']]);
            if(empty($referrr_info)){
                return $result = ['code' => 0, 'msg' => '推荐人不存在，请重新选择'];
            }
            $data['pid'] = $referrr_info['id'];
            $data['referee'] = $referrr_info['usernum'];

            //报单人
            $baodan_info = UsersModel::get(['usernum' => $data['baodan_user']]);
            if (empty($baodan_info)) {
                return $result = ['code' => 0, 'msg' => '报单中心不存在，请重新选择'];
            }
            if($baodan_info['baodan_center'] != 1){
                return $result = ['code' => 0, 'msg' => '该账号不是有效的报单中心，请重新选择'];
            }
            $data['baodan_user'] = $baodan_info['usernum'];
            //接点人
            $node_info = UsersModel::get(['usernum' => $data['contact_person']]);
            if (empty($node_info)) {
                return $result = ['code' => 0, 'msg' => '接点人不存在，请重新选择'];
            }
            $data['npid'] = $node_info['id'];
            $data['contact_person'] = $node_info['usernum'];

            //是否报备银行
            if (!empty($data['bank_id']) && !empty($data['bank_user']) && !empty($data['bank_account']) && !empty($data['bank_desc'])) {
                $data['is_report'] = 1; //报备银行
            } else {
                $data['is_report'] = 0;
            }
            $data['create_time'] = date('Y-m-d', time()); //添加时间方便做折线图
            $new_user_id = UsersModel::create($data);
            if ($new_user_id) {
                //推荐人邀请成功用户，修改users表 have_tree 为1
                UsersModel::where(['id' => $referrr_info['id']])->update(['have_tree' => 1]);
                $user_referee_model = new UserReferee();
                $user_node_model = new UserNode();
                if ($data['pid'] == 1) {
                    //接入用户和推荐人关系
                    $data2['user_id'] = $new_user_id->id;
                    $data2['user_son_str'] = 1 . ',';
                    $data2['deep'] = count(explode(',', $data2['user_son_str'])) - 1;

                } else {
                    $son_str = $user_referee_model->where(['user_id' => $referrr_info['id']])->value('user_son_str');
                    //接入用户和推荐人关系
                    $data2['user_id'] = $new_user_id->id;
                    $data2['user_son_str'] = $son_str . $referrr_info["id"] . ',';
                    $data2['deep'] = count(explode(',', $data2['user_son_str'])) - 1;

                }

                if($data['npid'] == 1){
                    //接入用户和接点人的关系
                    $data3['user_id'] = $new_user_id->id;
                    $data3['user_son_str'] = 1 . ',';
                    $data3['deep'] = count(explode(',', $data3['user_son_str'])) - 1;
                }else{
                    $son_node_str = $user_node_model->where(['user_id' => $node_info['id']])->value('user_son_str');

                    //接入用户和接点人的关系
                    $data3['user_id'] = $new_user_id->id;
                    $data3['user_son_str'] = $son_node_str . $node_info['id'] . ',';
                    $data3['deep'] = count(explode(',', $data3['user_son_str'])) - 1;
                }

                UserReferee::create($data2);
                UserNode::create($data3);
                //获取当前设置的汇率
                $sate = db('bonus_ext_set')->where(['id' => 1])->value('money_change');
                //创建用户钱包账户
                $account['user_id'] = $new_user_id->id;
                $account['rate'] = $sate;
                UserCurrencyAccount::create($account);

                return ['code' => 1, 'msg' => '注册成功', 'url' => url('index')];
            } else {
                return ['code' => 0, 'msg' => '注册失败'];
            }

        } else {
            $province = db('Region')->where(array('pid' => 1))->select();
            $user_level = db('user_level')->order('sort')->select();
            $bank = db('Bank')->order('id ASC')->select();
            $user_num = createVipNum();
            $this->assign('province', json_encode($province, true));
            $this->assign('user_level', json_encode($user_level, true)); //会员级别
            $this->assign('bank', $bank); //银行列表
            $this->assign('usernum', $user_num); //会员编号

            return $this->fetch();
        }

    }

    //验证推荐人和接点人报单中心
    public function validateUser()
    {
        $search = input('post.search');
        $type = input('post.type');
        if (empty($search) || empty($type)) {
            return ['code' => 0, 'msg' => '此用户不存在'];
        }
        //推荐人/接点人/报单中心
        $where['usernum'] = $search;
        $user_info = UsersModel::get($where);

        if (empty($user_info)) {
            return ['code' => 0, 'msg' => '此用户不存在'];
        }
        if ($user_info['status'] == 0) {
            return ['code' => 0, 'msg' => '此用户未激活'];
        }
        if ($type == 3 && $user_info['baodan_center'] == 0) {
            return ['code' => 0, 'msg' => '此用户不是报单中心'];
        }

        return ['code' => 1, 'name' => $user_info['username']];


    }

    //未激活会员列表
    public function noActiceList()
    {
        if (request()->isPost()) {

            $data = input('post.');
            $where = $this->noActiveSearch($data);
            $page = $data['page'] ? $data['page'] : 1;
            $pageSize = $data['limit'] ? $data['limit'] : config('pageSize');
            $where['status'] = 0;
            $list = db('users')
                ->where($where)
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['reg_time'] = date('Y-m-d H:s', $v['reg_time']);
                $tuijian_user = UsersModel::where(['id' => $v['pid']])->value('username');
                $jidianren_user = UsersModel::where(['id' => $v['npid']])->value('username');
                $baodan_user = UsersModel::where(['usernum' => $v['baodan_user']])->value('username');
                $list['data'][$k]['referee'] = $v['referee'] . '【' . $tuijian_user . '】';
                $list['data'][$k]['contact_person'] = $v['contact_person'] . '【' . $jidianren_user . '】';
                $list['data'][$k]['baodan_user'] = $v['baodan_user'] . '【' . $baodan_user . '】';
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }

        return $this->fetch('noActiceList');
    }

    //搜索
    public function noActiveSearch($data)
    {
        $where = new Where();
        if (!empty($data['start_time']) && empty($data['end_time'])) {
            $start_time = strtotime($data['start_time']);
            $where['reg_time'] = array('egt', $start_time);
        }
        if (!empty($data['end_time']) && empty($data['start_time'])) {
            $end_time = strtotime($data['end_time']);
            $where['reg_time'] = array('elt', $end_time);
        }
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $start_time = strtotime($data['start_time']);
            $end_time = strtotime($data['end_time']);
            $where['reg_time'] = array('between time', array($start_time, $end_time));
        }
        if (!empty($data['key'])) {
            $where['id|usernum|email|mobile|username'] = array('like', '%' . $data['key'] . '%');
        }
        return $where;
    }


    //会员激活
    public function userActice()
    {
        $user_info = db('users')->where(['id' => input('id')])->find();
        if ($user_info) {
            $user_name = $user_info['usernum'] . '【' . $user_info['username'] . '】';
            $this->assign('user_name', $user_name);
            $this->assign('id', input('id'));
            //获取用户级别表
            $user_level = db('user_level')->order('level_id')->select();

            $this->assign('user_level', $user_level);
        }
        return $this->fetch('userActice');
    }

    //确认激活
    public function sureActice()
    {
        if (request()->isPost()) {
            $data = input('post.');
            if (empty($data['id'])) {
                return ['code' => 0, 'msg' => '请选择用户'];
            }
            if (empty($data['level'])) {
                return ['code' => 0, 'msg' => '请选择会员级别'];
            }

            //根据会员级别做直推奖励
            $save['level'] = $data['level'];
            $save['status'] = 1;
            $save['enabled'] = 1;
            $save['active_time'] = time();
            if ($data['level'] == 1) {
                $bonus_ratio = BonusSet::where(['level_id' => 1])->find();

            } elseif ($data['level'] == 2) {
                $bonus_ratio = BonusSet::where(['level_id' => 2])->find();
            } elseif ($data['level'] == 3) {
                $bonus_ratio = BonusSet::where(['level_id' => 3])->find();
            } elseif ($data['level'] == 4) {
                $bonus_ratio = BonusSet::where(['level_id' => 4])->find();
            } elseif ($data['level'] == 5) {
                $bonus_ratio = BonusSet::where(['level_id' => 5])->find();

                $save['baodan_center'] = 1; //投资额到1万直接设置为报单中心
            }

            //查找用户的推荐人/报单中心信息
            $user_info = UsersModel::where(['id' => $data['id']])->find();
            //判断用户是否重复激活、
            if($user_info['status'] == 1){
                return ['code' => 0, 'msg' => '该会员已激活，请勿重复激活'];
            }
            $referee = UsersModel::where(['usernum' => $user_info['referee']])->find();
            $baodan_user = UsersModel::where(['usernum' => $user_info['baodan_user']])->find(); //保单中心信息

            Db::startTrans();
            //更新用户信息
            $res = UsersModel::where(['id' => $data['id']])->update($save);
            if ($res === false) {
                Db::rollback();
                return ['code' => 0, 'msg' => '激活失败请重试'];
            }
            if ($baodan_user['id'] != 1) {
                //报单中心奖励
                $baodan_ratio = Db::name('bonus_ext_set')->where(['id' => 1])->value('baodan_ratio');
                $baodan_ratio = bcdiv($baodan_ratio, 100, 2);
                $jiangli = bcmul($bonus_ratio['declaration'], $baodan_ratio, 4); //保单奖励
                $baodan_account = UserCurrencyAccount::where(['user_id' => $baodan_user['id']])->find();
                if (empty($baodan_account)) {
                    Db::rollback();
                    return ['code' => 0, 'msg' => '报单中心用户钱包不存在'];
                }
                $cash_currency_num2 = bcadd($baodan_account['cash_currency_num'], $jiangli, 4);

                $baodan_res = UserCurrencyAccount::where(['user_id' => $baodan_user['id']])->update(['cash_currency_num' => $cash_currency_num2]);
                if ($baodan_res === false) {
                    Db::rollback();
                    return ['code' => 0, 'msg' => '激活失败请重试'];
                }
                //记录用户报单奖励
                $log = UserRunningLog::create([
                    'user_id' => $baodan_user['id'],
                    'about_id' => $data['id'],
                    'running_type' => UserRunningLog::TYPE21,
                    'account_type' => 1,
                    'change_num' => $jiangli,
                    'balance' => $cash_currency_num2,
                    'create_time' => time(),
                    'remark' => '报单中心奖励'
                ]);
                if ($log === false) {
                    Db::rollback();
                    return ['code' => 0, 'msg' => '激活失败请重试'];
                }
            }

            if ($referee['id'] != 1) {
                //直推奖励
                $bonus_ratio2 = BonusSet::where(['level_id' => $referee['level']])->find();
                $ratio = bcdiv($bonus_ratio2['bonus_ratio'], 100, 4);
                $reward = bcmul($bonus_ratio['declaration'], $ratio, 4);

                //查询推荐人钱包信息
                $referee_account = UserCurrencyAccount::where(['user_id' => $referee['id']])->find();
                if (empty($referee_account)) {
                    Db::rollback();
                    return ['code' => 0, 'msg' => '推荐人钱包不存在'];
                }
                $cash_currency_num = bcadd($referee_account['cash_currency_num'], $reward, 4);
                $res2 = UserCurrencyAccount::where(['user_id' => $referee['id']])->update(['cash_currency_num' => $cash_currency_num]);

                if ($res2 === false) {
                    Db::rollback();
                    return ['code' => 0, 'msg' => '激活失败请重试'];
                }

                //记录收益日志
                $log2 = UserRunningLog::create([
                    'user_id' => $referee['id'],
                    'about_id' => $data['id'],
                    'running_type' => UserRunningLog::TYPE19,
                    'account_type' => 1,
                    'change_num' => $reward,
                    'balance' => $cash_currency_num,
                    'create_time' => time()
                ]);
                if ($log2 === false) {
                    Db::rollback();
                    return ['code' => 0, 'msg' => '激活失败请重试'];
                }
            }

            //更新用户本金钱包
            $res3 = UserCurrencyAccount::where(['user_id' => $data['id']])->update(['corpus' => $bonus_ratio['declaration']]);

            if ($res3 === false) {
                Db::rollback();
                return ['code' => 0, 'msg' => '激活失败请重试'];
            }

            $log_baodan = UserRunningLog::create([
                'user_id'  =>  $data['id'],
                'about_id' =>  -session('aid'),
                'running_type'  => UserRunningLog::TYPE37,
                'account_type'  => 2,
                'change_num'    => 0,
                'balance'       => 0,
                'create_time'   => time(),
                'remark'        => '后台激活用户'
            ]);
            if($log_baodan == false){
                Db::rollback();
                return ['code' => 0, 'msg' => '激活失败请重试'];
            }

            Db::commit();
            UserReferee::where(['user_id' => $data['id']])->update(['enabled' => 1]);
            UserNode::where(['user_id' => $data['id']])->update(['enabled' => 1]);

            //记录用户激活
            UserRunningLog::create([
                'user_id' => $data['id'],
                'about_id' => $data['id'],
                'running_type' => UserRunningLog::TYPE22,
                'account_type' => 5,
                'change_num' => $bonus_ratio['declaration'],
                'balance' => $bonus_ratio['declaration'],
                'create_time' => time()
            ]);

            return ['code' => 1, 'msg' => '激活成功', 'url' => url('admin/users/noActiceList')];

        } else {
            return ['code' => 0, 'msg' => '请求出错'];
        }
    }


    //获取报单额
    public function getBaodan()
    {
        if(request()->isPost()){
            $level = input('post.level');
           $val =  Db::name('bonus_set')->where(['level_id' => $level])->value('declaration');
           if(!empty($val)){
               return ['code' => 1, 'val' => $val];
           }else{
               return ['code' => 0, 'msg' => '查找失败'];
           }


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
                ->field('u.id,u.referee,u.username,u.usernum,u.pid,u.have_tree,ul.level_name')
                ->where($where)
                ->order('u.id asc')
                ->select();
            $list = [];
            array_map(function ($v) use (&$list) {
                $list[] = [
                    'id' => $v['id'],
                    'name' => $v['usernum'] . '(' . $v['username'] . ' 级别:' . $v['level_name']. ')',
                    'isParent'  => $v['have_tree'],
                    'icon' => "/static/admin/images/user.png"
                ];
            },$user_info);

            return $list;

        }

        return $this->fetch('userTree');
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
                ->field('u.id,u.usernum,u.username,ul.level_name,ul.level_id')
                ->where($where)
                ->find();

            $user_info['children'] = db('users')
                ->alias('u')
                ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                ->field('u.id,u.usernum,u.username,ul.level_name,ul.level_id')
                ->where($where2)
                ->select();

            if(!empty($user_info['children'])){
                foreach($user_info['children'] as $key => $val){
                    $where3['npid'] = $val['id'];
                    $user_info['children'][$key]['children'] = db('users')
                        ->alias('u')
                        ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                        ->field('u.id,u.usernum,u.username,ul.level_name,ul.level_id')
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