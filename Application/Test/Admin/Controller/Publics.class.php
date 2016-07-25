<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/15/16
 * Time: 2:19 PM
 */

namespace Application\Admin\Controller;
use Application\System\Library\Service\LoginService;
use PLite\Library\Controller;
use PLite\Debugger;

class Publics extends Controller{

    public function register(){
        $this->display();
    }
    public function reset(){
        $this->display();
    }
    
    public function none(){
        $this->display('404');
    }

    public function login($username='',$password='',$remember=false){
        if(IS_METHOD_POST){
            if(!$username or !$password){
                $this->redirect('/Admin/User/login#'.urlencode('用户名或者密码不能为空'));
            }
            $result = LoginService::getInstance()->login($username,$password,$remember);
            if(true !== $result){
                $this->redirect('/Admin/Publics/login#'.urlencode($result));
            }else{
                $this->redirect('/Admin/Index/index');
            }
            exit();
        }
        $this->display();
    }

    /**
     * 注销登录
     */
    public function logout(){
        LoginService::getInstance()->logout();
        $this->redirect('/System/Member/Public/login');
    }

}