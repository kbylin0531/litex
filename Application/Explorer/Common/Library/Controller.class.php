<?php
namespace Application\Explorer\Common\Library;
use PLite\Core\URL;
use PLite\Util\Helper\ClientAgent;

/**
 * Class Controller
 * @package Application\Explorer\Common
 */
abstract class Controller extends \PLite\Library\Controller{

    public $in;
    public $db;
    public $config;	// 全局配置
    public $tpl;	// 模板目录,default as 'user'
    public $values;	// 模板变量
    public $L;
    /**
     * @var FileCache
     */
    protected $sql;

    //user
    protected $user;  //用户相关信息
//    private $auth;  //用户所属组权限
    protected $notCheck;
    /**
     * 构造函数
     */
    function __construct(){
        global $in,$config,$db;

        include_once PATH_APP.'/Explorer/Common/Function/web.function.php';
        include_once PATH_APP.'/Explorer/Common/Function/file.function.php';
        include_once PATH_APP.'/Explorer/Common/Function/util.function.php';
        include_once PATH_APP.'/Explorer/Common/Function/common.function.php';

        $this->constant();

        @set_time_limit(600);//10min pathInfoMuti,search,upload,download...
        @ini_set('session.cache_expire',600);

        $this -> db  = $db;
        $this -> L 	 = $this->initLang();
        $this -> config = &ExplorerUtils::getAppConfig();
        $this -> in = &$GLOBALS['in'];
        $this -> values['config'] = &$config;
        $this -> values['in'] = &$in;

        ExplorerUtils::checkLogin();
    }
    /**
     * 语言包加载：优先级：cookie获取>自动识别
     * 首次没有cookie则自动识别——存入cookie,过期时间无限
     * @return array
     */
    private function initLang(){
        if (isset($_COOKIE['kod_user_language'])) {
            $lang = $_COOKIE['kod_user_language'];
        }else{//没有cookie
            $lang = ClientAgent::getClientLang();
            switch (substr($lang,0,2)) {
                case 'zh':
                    if ($lang != 'zn-TW'){
                        $lang = 'zh-CN';
                    }
                    break;
                case 'en':$lang = 'en';break;
                default:$lang = 'en';break;
            }
            $lang = str_replace('-', '_',$lang);
            setcookie('kod_user_language',$lang, time()+3600*24*365);
        }
        if ($lang == '') $lang = 'en';

        $lang = str_replace(array('/','\\','..','.'),'',$lang);
        define('LANGUAGE_TYPE', $lang);
        return $GLOBALS['L'] = include(LANGUAGE_PATH.LANGUAGE_TYPE.'/main.php');
    }

    private function constant(){
        $web_root = str_replace(P($_SERVER['SCRIPT_NAME']),'',P(dirname(dirname(__FILE__))).'/index.php').'/';
        if (substr($web_root,-10) == 'index.php/') {//解决部分主机不兼容问题
            $web_root = P($_SERVER['DOCUMENT_ROOT']).'/';
        }
        define('WEB_ROOT',$web_root);
        define('HOST', (is_HTTPS() ? 'https://' :'http://').$_SERVER['HTTP_HOST'].'/');
        define('BASIC_PATH',    PATH_BASE.'/');
        define('APPHOST',       HOST.str_replace(WEB_ROOT,'',BASIC_PATH));//程序根目录
        define('TEMPLATE',		PATH_APP .'/Explorer/View/');	//模版文件路径

        define('LIB_DIR',		PATH_APP .'/Explorer/Common/Library/');		//库目录
        define('DATA_PATH',     PATH_APP .'/Explorer/Common/Data');       //用户数据目录
        define('LANGUAGE_PATH', DATA_PATH .'/i18n/');        //多语言目录
        define('USER_SYSTEM',   PATH_RUNTIME .'/USER_SYSTEM/');      //用户数据存储目录
        define('DATA_THUMB',    PATH_RUNTIME .'/DATA_THUMB/');       //缩略图生成存放

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


        define('ST',REQUEST_CONTROLLER);
        define('ACT',REQUEST_ACTION);
    }

}