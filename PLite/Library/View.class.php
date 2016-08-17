<?php

namespace PLite\Library;
use PLite\Lite;

/**
 * Class View
 * @method void assign(string|array $tpl_var,mixed $value=null,bool $nocache=false) static 保存控制器分配的变量
 * @method void registerParsingString(string $str,string|int $replacement) static 设置模板替换字符串
 * @method void display(array $context,int  $cache_id = null,int $compile_id = null,int $parent = null) static 显示模板
 * @package PLite\Library
 */
class View extends Lite {


    const CONF_NAME = 'view';
    const CONF_CONVENTION = [
        'PRIOR_INDEX' => 1,
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

        //模板文件提示错误信息模板
        'TEMPLATE_EMPTY_PATH'   => 'notpl',
    ];

}