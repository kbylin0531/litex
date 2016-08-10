<?php
namespace Application\Explorer\Common\Library;
use PLite\Core\URL;
use PLite\Library\FileCache;
use PLite\Response;
use PLite\Storage;
use PLite\Util\Helper\ClientAgent;
use PLite\Utils;

class ExplorerUtils {

    private static $_config = [
        'noCheck'   => ['login'],
    ];

    /**
     * @param array $config
     */
    public static function init(array $config){
        self::$_config = Utils::merge(self::$_config,$config);
    }


    /**
     * 语言包加载：优先级：cookie获取>自动识别
     * 首次没有cookie则自动识别——存入cookie,过期时间无限
     * @return array
     */
    public static function &getLangConfig(){
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

    /**
     * @return array
     */
    public static function &getComein(){
        return $GLOBALS['in'] = [REQUEST_MODULE,REQUEST_ACTION,];
    }

    public static function &getAppConfig(){
        if(isset($GLOBALS['config'])){
            $GLOBALS['config'] = [
                //配置数据,可在setting_user.php中更改覆盖
                'settings' => [
                    'download_url_time' => 0,            //下载地址生效时间，按秒计算，0代表不限制，默认不限制
                    'upload_chunk_size' => 1024 * 1024 * 2,    //上传分片大小；默认1M
                    'version_desc' => 'product',
                ],

                //初始化系统配置
                'setting_system_default' => [
                    'system_password' => rand_string(10),
                    'system_name' => "KodExplorer",
                    'system_desc' => "--资源管理器",
                    'path_hidden' => ".DS_Store,.gitignore",//目录列表隐藏的项
                    'auto_login' => "0",            // 是否自动登录；登录用户为guest
                    'first_in' => "explorer",    // 登录后默认进入[explorer desktop,editor]
                    'new_user_app' => "365日历,pptv直播,ps,qq音乐,搜狐影视,时钟,天气,水果忍者,计算器,豆瓣电台,音悦台,icloud",
                    'new_user_folder' => "download,music,image,desktop",
                ],

                // 配置项可选值
                'setting_all' => array(
                    'language' => "en:English,zh_CN:简体中文,zh_TW:繁體中文",
                    'themeall' => "default/:<b>areo blue</b>:default,simple/:<b>simple</b>:simple,metro/:<b>metro</b>:metro,metro/blue_:metro-blue:color,metro/leaf_:metro-green:color,metro/green_:metro-green+:color,metro/grey_:metro-grey:color,metro/purple_:metro-purple:color,metro/pink_:metro-pink:color,metro/orange_:metro-orange:color",
                    'codethemeall' => "chrome,clouds,crimson_editor,eclipse,github,solarized_light,tomorrow,xcode,ambiance,idle_fingers,monokai,pastel_on_dark,solarized_dark,tomorrow_night_blue,tomorrow_night_eighties",
                    'wallall' => "1,2,3,4,5,6,7,8,9,10,11,12,13",
                    'musicthemeall' => "ting,beveled,kuwo,manila,mp3player,qqmusic,somusic,xdj",
                    'moviethemeall' => "webplayer,qqplayer,vplayer,tvlive,youtube"
                ),
                //新用户初始化配置
                'setting_default' => array(
                    'list_type' => "icon",        // list||icon
                    'list_sort_field' => "name",        // name||size||ext||mtime
                    'list_sort_order' => "up",        // asc||desc
                    'theme' => "simple/",    // app theme [default,simple,metro/,metro/black....]
                    'codetheme' => "clouds",    // code editor theme
                    'wall' => "7",            // wall picture
                    'musictheme' => "mp3player",    // music player theme
                    'movietheme' => "webplayer"    // movie player theme
                ),
                //初始化默认菜单配置
                'setting_menu_default' => array(
                    array('name' => 'desktop', 'type' => 'system', 'url' => 'index.php?desktop', 'target' => '_self', 'use' => '1'),
                    array('name' => 'explorer', 'type' => 'system', 'url' => 'index.php?explorer', 'target' => '_self', 'use' => '1'),
                    array('name' => 'editor', 'type' => 'system', 'url' => 'index.php?editor', 'target' => '_self', 'use' => '1'),
                    array('name' => 'adminer', 'type' => '', 'url' => './lib/plugins/adminer/', 'target' => '_blank', 'use' => '1')
                ),

                //权限配置；精确到需要做权限控制的控制器和方法
                //需要权限认证的Action,root组无视权限
                'role_setting' => array(
                    'explorer' => array(
                        'mkdir', 'mkfile', 'pathRname', 'pathDelete', 'zip', 'unzip', 'pathCopy', 'pathChmod',
                        'pathCute', 'pathCuteDrag', 'pathCopyDrag', 'clipboard', 'pathPast', 'pathInfo',
                        'serverDownload', 'fileUpload', 'search', 'pathDeleteRecycle',
                        'fileDownload', 'zipDownload', 'fileDownloadRemove', 'fileProxy', 'officeView', 'officeSave'),
                    'app' => array('user_app', 'init_app', 'add', 'edit', 'del'),//
                    'user' => array('changePassword'),//可以设立公用账户
                    'editor' => array('fileGet', 'fileSave'),
                    'userShare' => array('set', 'del'),
                    'setting' => array('set', 'system_setting', 'php_info'),
                    'fav' => array('add', 'del', 'edit'),
                    'member' => array('get', 'add', 'del', 'edit'),
                    'group' => array('get', 'add', 'del', 'edit'),
                ),

                //数据地址定义。
                'pic_thumb' => BASIC_PATH . 'data/thumb/',        // 缩略图生成存放地址
                'cache_dir' => BASIC_PATH . 'data/cache/',        // 缓存文件地址
                'app_startTime' => REQUEST_MICROTIME,                    //起始时间

                //系统编码配置
                'app_charset' => 'utf-8',            //该程序整体统一编码
                'check_charset' => 'ASCII,UTF-8,GBK',//文件打开自动检测编码

                'autorun' => array(
                    array('controller' => 'user', 'function' => 'loginCheck'),
                    array('controller' => 'user', 'function' => 'authCheck')
                ),
                'system_os' => IS_WINDOWS ? 'windows' : 'linux',
                'system_charset' => IS_WINDOWS ? 'gbk' : 'utf-8',
            ];
        }
        return $GLOBALS['config'];
    }

    /**
     * redirect to login page
     */
    public static function goLogin(){
        touch(DATA_USER_SYSTEM.'install.lock');
        URL::redirect(__PUBLIC__.'/index.php?user/login');
        exit;
    }

    /**
     * 检查用户是否登录
     * @return bool
     */
    public static function checkLogin(){
        //共享页面
        if(REQUEST_CONTROLLER === 'share' or in_array(REQUEST_ACTION,self::$_config['noCheck'])) return true;

        if($_SESSION['kod_login']===true and !empty($_SESSION['kod_user']['name'])){
            define('USER',USER_PATH.$_SESSION['kod_user']['name'].'/');//personal dir
            define('USER_TEMP',USER.'data/temp/');
            define('USER_RECYCLE',USER.'recycle/');
            if (!file_exists(USER)) {
                Storage::mkdir(USER);
            }
            if ($_SESSION['kod_user']['role'] == 'root') {
                define('MYHOME',USER.'home/');
                define('HOME','');
                $GLOBALS['web_root'] = WEB_ROOT;//服务器目录
                $GLOBALS['is_root'] = 1;
            }else{
                define('MYHOME','/');
                define('HOME',USER.'home/');
                $GLOBALS['web_root'] = str_replace(WEB_ROOT,'',HOME);//从服务器开始到用户目录
                $GLOBALS['is_root'] = 0;
            }
            $config = self::getAppConfig();
            $config['user_share_file']   = USER.'data/share.php';    // 收藏夹文件存放地址.
            $config['user_fav_file']     = USER.'data/fav.php';    // 收藏夹文件存放地址.
            $config['user_seting_file']  = USER.'data/config.php'; //用户配置文件
            $config['user']  = FileCache::load($config['user_seting_file']);//用户设置
            if($config['user']['theme']==''){
                $config['user'] = $config['setting_default'];
            }
        }else if($_COOKIE['kod_name']!='' && $_COOKIE['kod_token']!=''){
            //cookie未过期
            $member = new FileCache(DATA_USER_SYSTEM.'member.json');
            $user = $member->get($_COOKIE['kod_name']);
            if (is_array($user) and isset($user['password'])) {
                if(md5($user['password'].get_client_ip()) == $_COOKIE['kod_token']){
                    session_start();//re start
                    $_SESSION['kod_login'] = true;
                    $_SESSION['kod_user']= $user;
                    setcookie('kod_name', $_COOKIE['kod_name'], time()+3600*24*7);
                    setcookie('kod_token',$_COOKIE['kod_token'],time()+3600*24*7); //密码的MD5值再次md5
                    Response::cleanOutput();
                    header('location:'.get_url());
                    exit;
                }else{
                    self::logout();
                }
            }
        }else{
            file_exists(DATA_USER_SYSTEM.'install.lock') or URL::redirect(__PUBLIC__.'/index.php/app/install');
        }
        return false;
    }

    /**
     * 用户登出
     */
    public static function logout(){
        session_start();
        Response::cleanOutput();
        setcookie('PHPSESSID', '', time()-3600,'/');
        setcookie('kod_name', '', time()-3600);
        setcookie('kod_token', '', time()-3600);
        setcookie('kod_user_language', '', time()-3600);
        session_destroy();
        header('location:__PUBLIC__/index.php?user/login');
        exit;
    }

}