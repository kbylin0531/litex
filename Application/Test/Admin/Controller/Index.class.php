<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/14/16
 * Time: 4:54 PM
 */
namespace Application\Admi32323n\Controller;
use Application\System\Library\Service\LoginService;
use Application\System\Library\Service\MenuService;
use Application\System\Model\AccountModel;
use PLite\Debugger;
use PLite\Library\Logger;
use PLite\Library\Response;
use PLite\Library\Session;

class Index extends Admin{

    public function index(){
        $uid = (new LoginService())->getLoginInfo('id');

        $accountModel = new AccountModel();
        $list = $accountModel->getAccountList(1);
        if(false === $list){
            Logger::write($accountModel->error());
            $list = [];
        }
        $this->assign('datalist',json_encode($list));
        $this->show();
    }

    public function manege($aid){
        $this->assign('aid',$aid);
        $this->show();
    }

    public function updateAccount($id,$name,$origin_id,$wechat,$token,$type,$appid,$appsecret,$encodingaeskey){
        empty($name) and Response::failed('公众号名称不能为空！');
        empty($origin_id) and Response::failed('原始ID不能为空！');
        empty($wechat) and Response::failed('微信号不能为空！');
        $accountModel = new AccountModel();
        $result = $accountModel->updateAccount($id,[
            'name'      => $name,
            'origin_id' => $origin_id,
            'wechat'    => $wechat,
            'token'     => $token,
            'type'      => $type,
            'appid'                 => $appid,
            'appsecret'             => $appsecret,
            'encodingaeskey'        => $encodingaeskey,
        ]);
        if($result){
            Session::set('account_id',$result);
            Session::set('account_token',$token);
            Response::ajaxBack([
                'type'  => 2,
            ]);
        }else{
            Response::ajaxBack([
                'type'  => 0,
                'message'   => 'failed to update！',
            ]);
        }
    }


    public function main(){
        $this->show();
    }

    public function top(){
        Debugger::closeTrace();
        $this->show();
    }

    public function left(){
        $this->assign('sidemenu',(new MenuService(1))->getSideMenu());
        Debugger::closeTrace();
        $this->show();
    }




}