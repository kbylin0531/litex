<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/15/16
 * Time: 3:16 PM
 */

namespace Application\Admin\Controller;
use PLite\Library\Controller;
use PLite\Util\SEK;
use Application\System\Library\Service\LoginService;

abstract class Admin extends Controller {

    /**
     * IndexController constructor.
     * @param null $identify
     */
    public function __construct($identify=null){
        $status = LoginService::getInstance()->isLogin();
        if(!$status){
            $this->redirect('/Admin/Publics/login');
        }
        define('REQUEST_PATH','/'.REQUEST_MODULE.'/'.REQUEST_CONTROLLER.'/'.REQUEST_ACTION);
    }

    protected function show($template=null){
        $info = LoginService::getInstance()->getLoginInfo();
        $this->assign([
            'user_info'     => $info,
            'user_message'  => $this->getMessage(),
            'user_update'   => $this->getUpdate(),
            'project_list'  => [
                [
                    'title' => 'Code Geass',
                    'percent'   => '50',
                ]
            ],
            'sidemenu_list' => [
                [
                    'title' => 'Home',
                    'icon'  => 'home',
                ],
                [
                    'title' => 'Files',
                    'icon'  => 'folder',
                    'children'  => [
                        [
                            'title' => 'XXX',
                        ],

                        [
                            'title' => 'YYY',
                        ]
                    ],
                ],
            ],

        ]);

        //获取调用自己的函数
        null === $template and $template = SEK::backtrace(SEK::ELEMENT_FUNCTION,SEK::PLACE_FORWARD);
        $this->display($template /* substr($template,4) 第五个字符开始 */);
    }

    protected function getMessage(){
        return [
            'title' => 'Messages',
            'num'   => 5,
        ];
    }

    protected function getUpdate(){
        return [
            'title' => 'Updates',
            'num'   => 9,
        ];
    }

}