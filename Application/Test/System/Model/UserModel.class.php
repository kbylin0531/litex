<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/14/16
 * Time: 2:39 PM
 */

namespace Application\System\Model;
use PLite\Library\Logger;
use PLite\Library\Model;
use PLite\Util\Helper\ClientAgent;

class UserModel extends Model{

    protected $tablename = 'sy_member';

    const LOGIN_USERNAME = 0;
    const LOGIN_PHONE = 1;
    const LOGIN_EMAIL = 2;

    /**
     * 根据用户名获取用户信息
     * @param $username
     * @return bool|mixed
     */
    public function getInfo($username){
        $result = $this->where(['username'=>$username])->find();
        return $result;
    }



    /**
     * 检查登陆
     * @param string $account 账户名称，可以是用户名、邮箱和手机号
     * @param string $password
     * @param int $type
     * @return bool|array 返回false时表示登陆失败，可以通过error方法获取错误信息
     */
    public function checkLogin($account, $password, $type=self::LOGIN_USERNAME){
        $where = [];
        switch ($type){
            case self::LOGIN_EMAIL:
                $where['email'] = $account;
                break;
            case self::LOGIN_PHONE:
                $where['phone'] = $account;
                break;
            case self::LOGIN_USERNAME:
            default:
                $where['username'] = $account;
        }
        $user = $this->fields('profile,birthday,email,id,nickname,phone,last_login_ip,last_login_time,sex,username,password')->where($where)->find();
        if(false === $user){
            if(!DEBUG_MODE_ON){
                Logger::write($this->error());
                $this->error = '服务端发生了错误！';
            }
            return false;//发生了错误，可能存在的问题是吧数据库错误的信息报告给了前端用户
        }elseif(!$user){//空数组
            $this->error = '用户不存在';
            return false;
        }else{
//            \PLite\dumpout($password,$user);
            if($password === $user['password']){
                unset($user['password']);
                return $user;
            }else{
                $this->error = '密码不正确！';
                return false;
            }
        }
    }

    /**
     * 获取用户列表
     * @param int $status
     * @return array|bool
     */
    public function listMember($status =  1){
        return $this->where('status = '.intval($status))->select();
    }

    /**
     * 添加用户
     * @param array $info
     * @return bool
     */
    public function createMember(array $info){
        $ip = ClientAgent::getClientIP();
        $convention = [
            'username'  => null,
            'sex'  => '1',
            'nickname'  => null,
            'phone'  => null,
            'email'  => null,
            'reg_ip'        => $ip,
            'reg_time'      => date('Y-m-d'),
            'last_login_ip'     => null,
            'last_login_time'   => null,
            'status'  => '1',
            'birthday'  => null,
            'password'  => md5(sha1('123456')),//初始密码
            'avatar'  => null,
        ];

        $info = array_merge($convention,$info);
        $ramdom = str_replace('.','',''.microtime(true));
        if(empty($info['nickname'])) $info['nickname'] = '匿名用户_'.$ramdom;
        if(empty($info['username'])) $info['username'] = 'sy_'.$ramdom;

        foreach ($info as $key=>$value) {
            if(!isset($value)) unset($info[$key]);
        }
//        \PLite\dumpout($info);
        return $this->fields($info)->create();
    }

    /**
     * 删除用户
     * @param int $uid
     * @return bool
     */
    public function deleteMember($uid){
        return $this->fields(['status'=>0])->where('id = '.intval($uid))->update();
    }

    /**
     * 修改用户信息
     * @param array $info
     * @return bool
     */
    public function updateMember(array $info){
        if(!isset($info['id'])){
            $this->error = '缺少用户ID信息，无法完成更新';
            return false;
        }
        $id = $info['id'];
        unset($info['id']);
        return $this->fields($info)->where('id = '.intval($id))->update();
    }

}
