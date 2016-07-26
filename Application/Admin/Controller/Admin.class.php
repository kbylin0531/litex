<?php

/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/25/16
 * Time: 5:36 PM
 */
namespace Application\Admin\Controller;
use Application\Admin\Model\MemberModel;
use Application\Admin\Model\WebsiteModel;
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

    /**
     * @param string|null $template
     * @param array|null $pageinfo
     */
    protected function show($template=null,array $pageinfo=null){
        $this->assign('userinfo',self::$memberModel->getLoginInfo());
        $model = new WebsiteModel();
        //is different by website
        $webinfo = $model->lists(true);
        $menu_list = $model->getSideMenu(true);
        $user_menu_list = $model->getUserMenu();
        $webinfo['menu_list'] = $menu_list;
        $webinfo['user_menu'] = $user_menu_list;
        $this->assign('website',$webinfo);
        //is different by page
        $this->assign('page',[
            'active_id' => 3,
            'title'         => 'This is an heading title',
            'breadcrumb'    => [
                [
                    'title' => '222',
                    'url'   => '#',
                ],
                [
                    'title' => '444',
                    'url'   => '#',
                ],
            ],
        ]);

        null === $template and $template = SEK::backtrace(SEK::ELEMENT_FUNCTION,SEK::PLACE_FORWARD);
        $this->display($template /* substr($template,4) 第五个字符开始 */);
    }

}