<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/15/16
 * Time: 12:05 PM
 */
namespace Application\System\Library\Service;
use PLite\Core\URL;

/**
 * Class MenuService 自定义菜单创建接口
 * @package Application\System\Library\Service
 */
class MenuService extends WechatService{

    /**
     * @return array
     */
    public function getSideMenu(){
        return [
            [
                'icon'  => 'fa-list',
                'title' => '通用管理',
                'children'  => [
                    [
                        'url'   => URL::url('category'),
                        'title' => '栏目配置',
                    ],
                    [
                        'url'   => URL::url('news_mg',['start_catg'=>'news','style'=>1]),
                        'title' => '图文管理',
                    ],
                    [
                        'url'   => URL::url('product_mg',['start_catg'=>'product','style'=>1]),
                        'title' => '产品管理',
                    ],
                    [
                        'url'   => URL::url('adv_mg',['start_catg'=>'adv','style'=>2]),
                        'title' => '广告管理',
                    ],
                    [
                        'url'   => URL::url('feed_mg'),
                        'title' => '留言管理',
                    ],
                    [
                        'url'   => URL::url('file_mg'),
                        'title' => '文件管理',
                    ],
                ]
            ],
            [
                'icon'  => 'fa-list',
                'title' => '微信管理',
                'children'  => [
                    [
                        'url'   => URL::url('replytext_mg',['start_catg'=>'msg-1','style'=>1]),
                        'title' => '文本回复',
                    ],
                    [
                        'url'   => URL::url('replynews_mg',['start_catg'=>'msg-2','style'=>2]),
                        'title' => '图文回复',
                    ],
                    [
                        'url'   => URL::url('wxmenu_mg'),
                        'title' => '自定义菜单',
                    ],
                    [
                        'url'   => URL::url('adv_mg',['start_catg'=>'adv-3','style'=>2]),
                        'title' => '首页幻灯片',
                    ],
                    [
                        'url'   => URL::url('wxconfig'),
                        'title' => '微信设置',
                    ],
                ]
            ],
        ];
    }


    /**
     * 自定义菜单创建
     * @var string
     */
    protected $create_url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=';
    /**
     * 自定义菜单查询接口
     * @var string
     */
    protected $get_url    = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=';
    /**
     * 自定义菜单删除接口
     * @var string
     */
    protected $delete_url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=ACCESS_TOKEN';


    //------------------- 个性化菜单：不同的团体看到不同的菜单 -----------------------------//
    protected $delconditional_create_url = 'https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=';
    protected $delconditional_delete_url = 'https://api.weixin.qq.com/cgi-bin/menu/delconditional?access_token=';
    protected $delconditional_trymatch_url = 'https://api.weixin.qq.com/cgi-bin/menu/trymatch?access_token=';



}
