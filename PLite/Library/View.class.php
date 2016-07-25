<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 10:15 PM
 */

namespace PLite\Library;
use PLite\Lite;
use PLite\Library\View\ViewInterface;
use PLite\Util\SEK;

class View extends Lite {

    const CONF_NAME = 'view';
    const CONF_CONVENTION = [
        'PRIOR_INDEX' => 0,
        'DRIVER_CLASS_LIST' => [
            'PLite\\Library\\View\\Smarty',
            'PLite\\Library\\View\\Think',
        ],
        'DRIVER_CONFIG_LIST' => [
            [
                'SMARTY_DIR'        => PATH_BASE.'/Vendor/smarty3/libs/',
                'TEMPLATE_CACHE_DIR'    => PATH_RUNTIME.'/View/',
                'SMARTY_CONF'       => [
                    //模板变量分割符号
                    'left_delimiter'    => '{',
                    'right_delimiter'   => '}',
                    //缓存开启和缓存时间
                    'caching'        => true,
                    'cache_lifetime'  => 15,
                ],
            ],
            [
                'CACHE_ON'         => true,//缓存是否开启
                'CACHE_EXPIRE'     => 10,//缓存时间，0便是永久缓存,仅以设置为30
                'CACHE_UPDATE_CHECK'=> true,//是否检查模板文件是否发生了修改，如果发生修改将更新缓存文件（实现：检测模板文件的时间是否大于缓存文件的修改时间）

                'CACHE_PATH'       => PATH_RUNTIME.'/View/',
                'TEMPLATE_SUFFIX'  =>  '.html',     // 默认模板文件后缀
                'CACHFILE_SUFFIX'  =>  '.php',      // 默认模板缓存后缀
                'TAGLIB_BEGIN'     =>  '<',  // 标签库标签开始标记
                'TAGLIB_END'       =>  '>',  // 标签库标签结束标记
                'L_DELIM'          =>  '{',            // 模板引擎普通标签开始标记
                'R_DELIM'          =>  '}',            // 模板引擎普通标签结束标记
                'DENY_PHP'         =>  false, // 默认模板引擎是否禁用PHP原生代码
                'DENY_FUNC_LIST'   =>  'echo,exit',    // 模板引擎禁用函数
                'VAR_IDENTIFY'     =>  'array',     // 模板变量识别。留空自动判断,参数为'obj'则表示对象

                'TMPL_PARSE_STRING'=> [],//用户自定义的字符替换
            ],
        ],

        //模板文件后缀名
//        'TEMPLATE_SUFFIX'   => 'html',
        //模板文件提示错误信息模板
        'TEMPLATE_EMPTY_PATH'   => 'notpl',
    ];

    /**
     * 调用本类display的方法的上下文环境
     * @var array
     */
    protected static $_context = null;

    /**
     * 类实例的驱动
     * @var ViewInterface
     */
    protected static $_driver = null;

    /**
     * 保存控制器分配的变量
     * @param string $tpl_var
     * @param null $value
     * @param bool $nocache
     * @return void
     */
    public static function assign($tpl_var,$value=null,$nocache=false){
        self::$_driver or self::$_driver = self::getDriver();
        self::$_driver->assign($tpl_var,$value,$nocache);
    }


    /**
     * 设置模板替换字符串
     * @param string|array $str
     * @param string $replacement
     * @return void
     */
    public static function registerParsingString($str,$replacement){
        self::$_driver or self::$_driver = self::getDriver();
        if(is_array($str)){
            foreach ($str as $key=>$val){
                self::$_driver->registerParsingString($key,$val);
            }
        }else{
            self::$_driver->registerParsingString($str,$replacement);
        }
    }
    /**
     * 显示模板
     * @param array $context 模板调用上下文环境，包括模块、控制器、方法和模板主题
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return void
     */
    public static function display(array $context, $cache_id = null, $compile_id = null,$parent = null){
        self::$_driver or self::$_driver = self::getDriver();
        $template = SEK::parseTemplatePath($context);
        self::$_driver->setContext($context)->display($template,$cache_id,$compile_id,$parent);
    }

}