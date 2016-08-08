<?php

/**
 * Created by PhpStorm.
 * User: linzhv
 * Date: 8/8/16
 * Time: 9:12 PM
 */
namespace Application\Explore\Common;

/**
 * Class Controller
 * @package Application\Explore\Common
 */
abstract class Controller {

    public $in;
    public $db;
    public $config;	// 全局配置
    public $tpl;	// 模板目录
    public $values;	// 模板变量
    public $L;

    /**
     * 构造函数
     */
    function __construct(){
        global $in,$config,$db,$L;

        include_once PATH_APP.'/Explorer/Common/web.function.php';
        include_once PATH_APP.'/Explorer/Common/file.function.php';
        include_once PATH_APP.'/Explorer/Common/util.function.php';
        include_once PATH_APP.'/Explorer/Common/common.function.php';

        @set_time_limit(600);//10min pathInfoMuti,search,upload,download...
        @ini_set('session.cache_expire',600);

        $this->constant();

        $this -> db  = $db;
        $this -> L 	 = $L;
        $this -> config = &$config;
        $this -> in = &$in;
        $this -> values['config'] = &$config;
        $this -> values['in'] = &$in;
    }

    private function constant(){
        $web_root = str_replace(P($_SERVER['SCRIPT_NAME']),'',P(dirname(dirname(__FILE__))).'/index.php').'/';
        if (substr($web_root,-10) == 'index.php/') {//解决部分主机不兼容问题
            $web_root = P($_SERVER['DOCUMENT_ROOT']).'/';
        }
        define('WEB_ROOT',$web_root);
        define('HOST', (is_HTTPS() ? 'https://' :'http://').$_SERVER['HTTP_HOST'].'/');
        define('BASIC_PATH',    P(__DIR__.'/'));
        define('APPHOST',       HOST.str_replace(WEB_ROOT,'',BASIC_PATH));//程序根目录
        define('TEMPLATE',		BASIC_PATH .'template/');	//模版文件路径
        define('CONTROLLER_DIR',BASIC_PATH .'controller/'); //控制器目录
        define('MODEL_DIR',		BASIC_PATH .'model/');		//模型目录
        define('LIB_DIR',		BASIC_PATH .'lib/');		//库目录
        define('FUNCTION_DIR',	LIB_DIR .'function/');		//函数库目录
        define('CLASS_DIR',		LIB_DIR .'class/');			//内目录
        define('CORER_DIR',		LIB_DIR .'core/');			//核心目录
        define('DATA_PATH',     BASIC_PATH .'data/');       //用户数据目录
        define('LOG_PATH',      DATA_PATH .'log/');         //日志目录
        define('USER_SYSTEM',   DATA_PATH .'system/');      //用户数据存储目录
        define('DATA_THUMB',    DATA_PATH .'thumb/');       //缩略图生成存放
        define('LANGUAGE_PATH', DATA_PATH .'i18n/');        //多语言目录

        define('STATIC_JS','app');  //_dev(开发状态)||app(打包压缩)
        define('STATIC_LESS','css');//less(开发状态)||css(打包压缩)
        define('STATIC_PATH',"./static/");//静态文件目录
        //define('STATIC_PATH','http://static.kalcaddle.com/static/');//静态文件统分离,可单独将static部署到CDN

        /*
         可以自定义【用户目录】和【公共目录】;移到web目录之外，
         可以使程序更安全, 就不用限制用户的扩展名权限了;
         */
        define('USER_PATH',     DATA_PATH .'User/');        //用户目录
        //自定义用户目录；需要先将data/User移到别的地方 再修改配置，例如：
        //define('USER_PATH',   DATA_PATH .'/Library/WebServer/Documents/User');
        define('PUBLIC_PATH',   DATA_PATH .'public/');     //公共目录
        //公共共享目录,读写权限跟随用户目录的读写权限 再修改配置，例如：
        //define('PUBLIC_PATH','/Library/WebServer/Documents/Public/');

        /*
         * office服务器配置；默认调用的微软的接口，程序需要部署到外网。
         * 本地部署weboffice 引号内填写office解析服务器地址 形如:  http://---/view.aspx?src=
         */
        define('OFFICE_SERVER',"https://view.officeapps.live.com/op/view.aspx?src=");
        define('KOD_VERSION','3.21');
    }


    /**
     * 加载模型
     * @param string $class
     */
    public function loadModel($class){
        $args = func_get_args();
        $this -> $class = call_user_func_array('init_model', $args);
        return $this -> $class;
    }

    /**
     * 加载类库文件
     * @param string $class
     */
    public function loadClass($class){
        if (1 === func_num_args()) {
            $this -> $class = new $class;
        } else {
            $reflectionObj = new \ReflectionClass($class);
            $args = func_get_args();
            array_shift($args);
            $this -> $class = $reflectionObj -> newInstanceArgs($args);
        }
        return $this -> $class;
    }

    /**
     * 显示模板
     *
     * @param
     * @param
     */
    protected function assign($key,$value){
        $this->values[$key] = $value;
    }
    /**
     * 显示模板
     * @param
     */
    protected function display($tpl_file){
        global $L,$LNG;
        extract($this->values);
        require($this->tpl.$tpl_file);
    }



}