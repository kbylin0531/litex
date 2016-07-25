<?php

/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/25/16
 * Time: 5:36 PM
 */
namespace Application\Admin\Controller;
use Application\Admin\Model\MemberModel;
use PLite\Library\Controller;
use PLite\Util\SEK;

abstract class Admin extends Controller {
    /**
     * @var MemberModel
     */
    protected static $memberModel = null;
    /**
     * IndexController constructor.
     */
    public function __construct(){
        self::$memberModel or self::$memberModel = new MemberModel();
        $status = self::$memberModel->isLogin();
        if(!$status){
            $this->redirect('/Admin/Publics/login');
        }
        define('REQUEST_PATH','/'.REQUEST_MODULE.'/'.REQUEST_CONTROLLER.'/'.REQUEST_ACTION);
    }

    protected function show($template=null){
        $this->assign('userinfo',self::$memberModel->getLoginInfo());
        
        null === $template and $template = SEK::backtrace(SEK::ELEMENT_FUNCTION,SEK::PLACE_FORWARD);
        $this->display($template /* substr($template,4) 第五个字符开始 */);
    }

}