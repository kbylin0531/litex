<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 6:06 PM
 */
namespace {
    use PLite\AutoLoader;
    use PLite\Configger;
    use PLite\Debugger;
    use PLite\Dispatcher;
    use PLite\Response;
    use PLite\PLiteException;
    use PLite\Router;
    use PLite\Utils;

    const LITE_VERSION = 0.85;
//---------------------------------- mode constant -------------------------------------//
    defined('DEBUG_MODE_ON') or define('DEBUG_MODE_ON', true);
    defined('PAGE_TRACE_ON') or define('PAGE_TRACE_ON', true);//在处理微信签名检查时会发生以外的错误
//    defined('LITE_ON')       or define('LITE_ON', true);
    defined('INSPECT_ON')    or define('INSPECT_ON',false);

    defined('FS_ENCODING')  or define('FS_ENCODING','GB2312');//file system encoding

//---------------------------------- environment constant -------------------------------------//
    //It is different to thinkphp that the beginning time is the time of request comming
    //and ThinkPHP is just using the time of calling 'microtime(true)' which ignore the loading and parsing of "ThinkPHP.php" and its include files.
    //It could always keeped in 10ms from request beginning to script shutdown.
    defined('REQUEST_MICROTIME')  or define('REQUEST_MICROTIME', isset($_SERVER['REQUEST_TIME_FLOAT'])? $_SERVER['REQUEST_TIME_FLOAT']:microtime(true));//(int)($_SERVER['REQUEST_TIME_FLOAT']*1000)
    DEBUG_MODE_ON and $GLOBALS['_status_begin'] = [
        REQUEST_MICROTIME,
        memory_get_usage(),
    ];
    const IS_CLIENT = PHP_SAPI === 'cli';
    define('IS_WINDOWS',false !== stripos(PHP_OS, 'WIN'));
    define('IS_REQUEST_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ));
    define('IS_METHOD_POST',$_SERVER['REQUEST_METHOD'] === 'POST');//“GET”, “HEAD”，“POST”，“PUT”

    define('REQUEST_TIME',$_SERVER['REQUEST_TIME']);
    define('HTTP_PREFIX', (isset ($_SERVER ['HTTPS']) and $_SERVER ['HTTPS'] === 'on') ? 'https://' : 'http://' );
//    define('__PUBLIC__',dirname($_SERVER['SCRIPT_NAME']));
    define('__PUBLIC__',empty($_SERVER['SERVER_PORT']) || 80 === $_SERVER['SERVER_PORT']?
        HTTP_PREFIX.$_SERVER['SERVER_NAME']:
        HTTP_PREFIX.$_SERVER['SERVER_NAME'].':80'.dirname($_SERVER['SCRIPT_NAME']));

//---------------------------------- variable type constant ------------------------------//
    const TYPE_BOOL     = 'boolean';
    const TYPE_INT      = 'integer';
    const TYPE_FLOAT    = 'double';//double ,  float
    const TYPE_STR      = 'string';
    const TYPE_ARRAY    = 'array';
    const TYPE_OBJ      = 'object';
    const TYPE_RESOURCE = 'resource';
    const TYPE_NULL     = 'NULL';
    const TYPE_UNKNOWN  = 'unknown type';

//---------------------------------- path constant -------------------------------------//
    define('PATH_BASE', IS_WINDOWS?str_replace('\\','/',dirname(__DIR__)):dirname(__DIR__));
    defined('APP_DIR')  or define('APP_DIR','Application');//dir name opposide to base path
    defined('APP_PATH') or define('PATH_APP',PATH_BASE.'/'.APP_DIR);
    const PATH_PLITE    = PATH_BASE.'/PLite';
    const PATH_CONFIG   = PATH_BASE.'/Config';
    const PATH_RUNTIME  = PATH_BASE.'/Runtime';
    const PATH_PUBLIC   = PATH_BASE.'/Public';

    require __DIR__.'/Common/function.php';

    /**
     * Class PLite
     */
    final class PLite {

        /**
         * 错误处理函数
         * @var callable
         */
        private static $_errorhanler = null;

        /**
         * 异常处理函数
         * @var callable
         */
        private static $_exceptionhandler = null;

        /**
         * 惯例配置
         * @var array
         */
        private static $_config = [
            'ZONE'          => 'Asia/Shanghai',
            'PARAMSET_NAME' => '_PARAMS_',
            'ERROR_HANDLER'     => null,
            'EXCEPTION_HANDLER' => null,
            'ROUTE_ON'          => true,

            //string
            'FUNCTION_PACK' => null,

            //core config
            'CONFIGGER'     => null,
            'ROUTER'        => null,
            'DISPATCHER'    => null,
        ];

        /**
         * 初始化应用程序
         * @param array|null $config
         * @return void
         */
        public static function init(array $config=null){
            DEBUG_MODE_ON and Debugger::import('app_begin',$GLOBALS['_status_begin']);
            Debugger::status('app_init_begin');
            $config and self::$_config = Utils::merge(self::$_config,$config);

            version_compare(PHP_VERSION,'5.4.0','<') and die('Require php >= 5.4 !');
            date_default_timezone_set(self::$_config['ZONE']) or die('Date default timezone set failed!');

            //error  display
            error_reporting(DEBUG_MODE_ON?-1:E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);//php5.3version use code: error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
            ini_set('display_errors',DEBUG_MODE_ON?1:0);

            spl_autoload_register([AutoLoader::class,'load']) or die('Faile to register class autoloader!');
            self::registerErrorHandler(self::$_config['ERROR_HANDLER']);
            self::registerExceptionHandler(self::$_config['EXCEPTION_HANDLER']);
            register_shutdown_function(function (){/* called when script shut down */
                PAGE_TRACE_ON and !IS_REQUEST_AJAX and Debugger::showTrace();//show the trace info
                Response::flushOutput();
                Debugger::status('script_shutdown');
            });

            if(self::$_config['FUNCTION_PACK']){
                if(is_string(self::$_config['FUNCTION_PACK'])){
                    include PATH_BASE.self::$_config['FUNCTION_PACK'];
                }elseif(is_array(self::$_config['FUNCTION_PACK'])){
                    foreach (self::$_config['FUNCTION_PACK'] as $item){
                        include PATH_BASE.$item;
                    }
                }else{
                    PLiteException::throwing("Invalid config!".self::$_config['FUNCTION_PACK']);
                }
            }

            Debugger::status('app_init_done');
        }

        /**
         * start application
         * @param array|null $config
         */
        public static function start(array $config=null){
            self::init($config);

            Configger::init(self::$_config['CONFIGGER']);
            Router::init(self::$_config['ROUTER']);

            //parse uri
            $result = self::$_config['ROUTE_ON']?Router::parseRoute():null;
            $result or $result = Router::parseURL();
            //URL中解析结果合并到$_GET中，$_GET的其他参数不能和之前的一样，否则会被解析结果覆盖,注意到$_GET和$_REQUEST并不同步，当动态添加元素到$_GET中后，$_REQUEST中不会自动添加
            empty($result['p']) or $_GET = array_merge($_GET,$result['p']);

            Debugger::status('dispatch_begin');

            Dispatcher::init(self::$_config['DISPATCHER']);
            //dispatch
            Dispatcher::checkDefault($result['m'],$result['c'],$result['a']);
            Dispatcher::exec();
        }

        /**
         * register error handler for user error
         * @param callable|null $handler
         * @return void
         */
        private static function registerErrorHandler(callable $handler=null){
            self::$_errorhanler = $handler?$handler:[PLiteException::class,'handleError'];
            set_error_handler(self::$_errorhanler);
        }

        /**
         * register exception handler
         * @param callable|null $handler
         * @return void
         */
        private static function registerExceptionHandler(callable $handler=null){
            self::$_exceptionhandler = $handler?$handler:[PLiteException::class,'handleException'];
            set_exception_handler(self::$_exceptionhandler);
        }

        /**
         * 注销错误和异常处理回调函数
         * @return void
         */
        public static function unregisterAll(){
            restore_exception_handler();
            restore_error_handler();
        }
    }
}

namespace PLite {

    use PLite\Util\Helper\XMLHelper;
    use PLite\Library\ExtDebugger;


//-----------------------------------------------------------------------------------------
//---------------------------- RUNCTION OF FRAMEWOEK BEGIN --------------------------------
//-----------------------------------------------------------------------------------------
    function _var_dump($params, $traces, $withhead=true){
        $color='#';$str='9ABCDEF';//随机浅色背景
        for($i=0;$i<6;$i++) $color=$color.$str[rand(0,strlen($str)-1)];
        $str = "<pre style='background: {$color};width: 100%;padding: 10px'>";
        if($withhead) $str .= "<h3 style='color: midnightblue'><b>F:</b>{$traces[0]['file']} << <b>L:</b>{$traces[0]['line']} >> </h3>";
        foreach ($params as $key=>$val) $str .= '<b>P '.$key.':</b><br />'.var_export($val, true).'<br />';
        return $str.'</pre>';
    }
    /**
     * @param ...
     * @return void
     */
    function dumpout(){
        echo _var_dump(func_get_args(),debug_backtrace());
        exit();
    }
    function _export(){
        echo _var_dump(func_get_args(),debug_backtrace(),false);
    }
    /**
     * @param ...
     * @return void
     */
    function dump(){
        echo _var_dump(func_get_args(),debug_backtrace());
    }

//-----------------------------------------------------------------------------------------
//---------------------------- RUNCTION OF FRAMEWOEK END ----------------------------------
//-----------------------------------------------------------------------------------------

    /**
     * Class Debugger
     * @package PLite
     */
    class Debugger {
        /**
         * 运行时的内存和时间状态
         * @var array
         */
        private static $_status = [];
        /**
         * 跟踪记录
         * @var array
         */
        private static $_traces = [];

        /**
         * 记录运行时的内存和时间状态
         * @param null|string $tag tag of runtime point
         * @return void
         */
        public static function status($tag){
            DEBUG_MODE_ON and self::$_status[$tag] = [
                microtime(true),
                memory_get_usage(),
            ];
        }

        /**
         * import status
         * @param string $tag
         * @param array $status
         */
        public static function import($tag,array $status){
            self::$_status[$tag] = $status;
        }

        /**
         * 记录下跟踪信息
         * @param string|mixed $message
         * @param ...
         * @return string
         */
        public static function trace($message){
            $location = debug_backtrace();
            $location = "{$location[0]['file']}:{$location[0]['line']}";
            if(func_num_args() > 1) $message = var_export(func_get_args(),true);
            if(!is_string($message)) $message = var_export($message,true);
            return self::$_traces[$location] = $message;
        }

        /**
         * show trace
         * @return true
         */
        public static function showTrace(){
            ExtDebugger::showTrace(self::$_status,self::$_traces);
        }
    }

    /**
     * Class AutoLoader
     * @package PLite
     */
    class AutoLoader {

        /**
         * 类名和类路径映射表
         * @var array
         */
        private static $_classes = [];

        public static function load($clsnm){
            if(isset(self::$_classes[$clsnm])) {
                include_once self::$_classes[$clsnm];
            }else{
                $pos = strpos($clsnm,'\\');
                if(false === $pos){
                    $file = PATH_BASE . "/{$clsnm}.class.php";//class file place deside entrance file if has none namespace
                    if(is_file($file)) include_once $file;
                }else{
                    $path = PATH_BASE.'/'.str_replace('\\', '/', $clsnm).'.class.php';
                    if(is_file($path)) include_once self::$_classes[$clsnm] = $path;
                }
            }
            //auto init class
            $funcname = '_init_class_';
            is_callable("{$clsnm}::{$funcname}") and $clsnm::$funcname();
        }
    }

    /**
     * Class PLiteException
     * Using 'ExtDebugger' to avoid Loading unnecessary code in normal execute
     * @package PLite
     */
    class PLiteException extends \Exception {
        /**
         * Construct the exception. Note: The message is NOT binary safe.
         * @link http://php.net/manual/en/exception.construct.php
         * @param string $message [optional] The Exception message to throw.
         * @param int $code [optional] The Exception code.
         * @param \Exception $previous [optional] The previous exception used for the exception chaining. Since 5.3.0
         * @since 5.1.0
         */
        public function __construct($message, $code=0, \Exception $previous=null){
            $this->message = is_string($message)?$message:var_export($message,true);
        }

        /**
         * 直接抛出异常信息
         * @param ...
         * @return false
         * @throws PLiteException
         */
        public static function throwing(){
            $clsnm = static::class;//extend class name
            throw new $clsnm(func_get_args());
        }

        /**
         * handler the exception throw by runtime-processror or user
         * @param \Exception $e ParseError(newer in php7) or Exception
         * @return void
         */
        final public static function handleException($e) {
            ExtDebugger::handleException($e);
        }

        /**
         * handel the error
         * @param int $errno error number
         * @param string $errstr error message
         * @param string $errfile error occurring file
         * @param int $errline error occurring file line number
         * @return void
         */
        final public static function handleError($errno,$errstr,$errfile,$errline){
            ExtDebugger::handleError($errno,$errstr,$errfile,$errline);
        }
    }

    /**
     * Class Lite
     * @package PLite
     */
    abstract class Lite {
        /**
         * 类实例库
         * @var array
         */
        private static $_instances = [];
        /**
         * 类的静态配置
         * @var array
         */
        private static $_configs = [
            /************************************
            'sample class' => [
            'PRIOR_INDEX' => 0,//默认驱动ID，类型限定为int或者string
            'DRIVER_CLASS_LIST' => [],//驱动类的列表
            'DRIVER_CONFIG_LIST' => [],//驱动类列表参数
            ]
             ************************************/
        ];
        /**
         * 类实例的驱动
         * @var object
         */
        private static $_drivers = [
            /************************************
            'sample class' => Object
             ************************************/
        ];


//------------------------------------ single instance mode -------------------------------------------------------------------------------//
        /**
         * 获取指定标识符的实例
         * @param string|null $identify 驱动ID，为null时表示获取默认值
         * @return object
         */
        public static function getInstance($identify=null){
            $clsnm = static::class;
            isset(self::$_instances[$clsnm]) or self::$_instances[$clsnm] = [];
            if(!isset(self::$_instances[$clsnm][$identify])){
                self::$_instances[$clsnm][$identify] = new $clsnm($identify);
            }
            return self::$_instances[$clsnm][$identify];
        }

        /**
         * it maybe a waste of performance
         * @param string|int|null $identify it will get the default index if set to null
         * @return null|object
         */
        protected static function getDriver($identify=null){
            $clsnm = static::class;
            isset(self::$_drivers[$clsnm]) or self::$_drivers[$clsnm] = [];
            $config = null;
            //get default identify
            if(!isset($identify)) {
                $config = static::getConfig();
                if(isset($config['PRIOR_INDEX'])){
                    $identify = $config['PRIOR_INDEX'];
                }else{
                    return null;
                }
            }

            if(!isset(self::$_drivers[$clsnm][$identify])){
                isset($config) or $config = static::getConfig();
                if(isset($config['DRIVER_CLASS_LIST'][$identify])){
                    //获取驱动类名称
                    $driver = $config['DRIVER_CLASS_LIST'][$identify];

                    //设置实例驱动
                    self::$_drivers[$clsnm][$identify] = isset($config['DRIVER_CONFIG_LIST'][$identify])?
                            new $driver($config['DRIVER_CONFIG_LIST'][$identify]) : new $driver();
                }else{
                    PLiteException::throwing('No driver!', $clsnm, $identify);
                }
            }

            return self::$_drivers[$clsnm][$identify];
        }

        /**
         * 初始化类的配置
         * @param null|string $clsnm 类名称
         * @return void
         */
        public static function _init_class_($clsnm=null){
            $clsnm or $clsnm = static::class;
            if(!isset(self::$_configs[$clsnm])){
                //get convention
                self::$_configs[$clsnm] = Utils::constant($clsnm,'CONF_CONVENTION',[]);

                //load the outer config
                $conf = Configger::load($clsnm);
                $conf and is_array($conf) and self::$_configs[$clsnm] = Utils::merge(self::$_configs[$clsnm],$conf,true);
            }
        }

        /**
         * 获取该类的配置（经过用户自定义后）
         * @param string|null $key 配置项名称
         * @param mixed $replacement 如果参数一指定的配置项不存在时默认代替的配置项
         * @return array
         */
        protected static function getConfig($key=null,$replacement=null){
            isset(self::$_configs[static::class]) or self::$_configs[static::class] = [];
            if(null !== $key){
                $static_config = &self::$_configs[static::class];
                if(strpos($key,'.')){//存在且在大于0的位置
                    $keys = explode('.',$key);
                    $len = count($keys);
                    for($i = 0; $i < $len; $i++){
                        if(isset($static_config[$key])){
                            if($i === $len - 1){//最后一项
                                return isset($static_config[$key])?$static_config[$key]:$replacement;
                            }
                        }else{
                            return $replacement;
                        }
                        $static_config = & $static_config[$key];
                    }
                }else{
                    return isset($static_config[$key])?$static_config[$key]:$replacement;
                }
            }
            return self::$_configs[static::class];
        }

        /**
         * 设置临时配置，下次请求将会清空
         * @param string $key
         * @param mixed $value
         * @return bool
         */
        protected static function setConfig($key, $value){
            isset(self::$_configs[static::class]) or self::$_configs[static::class] = [];
            if(strpos($key,'.')){//存在且在大于0的位置
                $keys = explode('.',$key);
                $len = count($keys);
                $conf = &self::$_configs[static::class];
                for($i = 0; $i < $len; $i++){
                    if(!isset($conf[$key])){
                        if($i === $len - 1){
                            //最后一项
                            $conf[$key] = $value;
                        }else{
                            $conf[$key] = [];
                        }
                    }
                    $conf = & $conf[$key];
                }
            }else{
                self::$_configs[static::class][$key] = $value;
            }
        }

        /**
         * 检查类的初始化
         * @param bool $do 未初始化时是否自动初始化
         * @return bool 是否初始化
         */
        protected static function checkInit($do=true){
            if(!isset(self::$_configs[static::class])){
                if($do) {
                    static::_init_class_();
                }else{
                    return false;
                }
            }
            return true;
        }

        /**
         * 读取用户配置
         * @param string|array $name config item name,mapping to filename(not include suffix,and be careful with '.',it will replace with '/')
         * @return array 返回配置数组，配置文件不存在是返回空数组
         */
        public static function load($name) {
            $result = [];

            $type = gettype($name);
            switch ($type){
                case 'array'://for multiple config
                    foreach($name as $item){
                        $temp = self::load($item);
                        $temp and $result = Utils::merge($result,$temp);
                    }
                    break;
                case 'string':
                    if(false !== strpos('.', $name)){//if == 0,it will worked nice
                        $name = str_replace('.', '/' ,$name);
                    }
                    $path = PATH_CONFIG."/{$name}.php";
                    is_file($path) and $result = include $path;
                    break;
                default:
            }
            return $result;
        }
    }
    class Configger {
        /**
         * 配置类型
         * 值使用字符串而不是效率更高的数字是处于可以直接匹配后缀名的考虑
         */
        const TYPE_PHP     = 'php';
        const TYPE_INI     = 'ini';
        const TYPE_YAML    = 'yaml';
        const TYPE_XML     = 'xml';
        const TYPE_JSON    = 'json';

        /**
         * @var string config file-build path
         */
        private static $configs_path = PATH_RUNTIME.'/configs.php';
        /**
         * @var array map of class fullname of its config name
         */
        private static $_map = [];

        /**
         * @var array config of this class
         */
        private static $_config = [
            'AUTO_BUILD'        => true,
            'AUTO_CLASS_LIST'   => [
                'PLite\\Core\\Dao',
                'PLite\\Library\\View',
            ],
            'USER_CONFIG_PATH'  => PATH_RUNTIME.'/Config/',
        ];
        /**
         * @var array
         */
        private static $_cache = null;

        /**
         * Init the config cache
         * @param array $config
         * @return void
         */
        public static function init(array $config=null){
            $config and self::$_config = array_merge(self::$_config,$config);
            if(self::$configs_path and is_readable(self::$configs_path)){
                self::$_cache = include self::$configs_path;
            }elseif(self::$_config['AUTO_BUILD'] and !empty(self::$_config['AUTO_CLASS_LIST'])){
                foreach (self::$_config['AUTO_CLASS_LIST'] as $clsnm){
                    self::getOuterConfig($clsnm);
                }
                //Closure is not suggest in config file due to var_export could not do well with closure
                // it will be translated to 'method Closure::__set_state()'
                Storage::write(self::$configs_path,'<?php return '.var_export(self::$_cache,true).';');
            }
            is_array(self::$_cache) or self::$_cache = [];
        }

        /**
         * get class config
         * @param string $clsnm class name
         * @param bool $refresh is rerfresh the config
         * @return array
         * @throws PLiteException
         */
        public static function load($clsnm,$refresh=false){
            if($refresh or null === self::$_cache) self::init();
            return isset(self::$_cache[$clsnm])?self::$_cache[$clsnm]:self::getOuterConfig($clsnm);
        }

        /**
         * read the outer class config (instead of modifying the class self)
         * @param string $clsnm class name
         * @return array
         */
        private static function getOuterConfig($clsnm){
            $cname = Utils::constant($clsnm,'CONF_NAME',null);//outer constant name
            self::$_map[$cname] = $clsnm;
            strpos('.', $cname) and $cname = str_replace('.', '/' ,$cname);
            $path = PATH_CONFIG."/{$cname}.php";
            return self::$_cache[$clsnm] = is_readable($path)?include $path:[];
        }

        /**
         * read the user-defined config
         * @param string $identify config identify
         * @return array|null 返回配置数组，不存在指定配置时候返回null
         */
        public static function read($identify){
            static $_cache = [];
            if(!isset($_cache[$identify])) {
                $path = self::id2path($identify,true);
                if(null === $path) return null;//文件不存在，返回null
                $content = file_get_contents($path);
                if(null === $content) return null;
                $config = @unserialize($content);//无法反序列化的内容会抛出错误E_NOTICE，使用@进行忽略，但是不要忽略返回值
                $_cache[$identify] = false === $config ? null : $config;
            }
            return $_cache[$identify];
        }

        /**
         * write user-config to file
         */
        public static function write(){

        }

        /**
         * 将配置项转换成配置文件路径
         * @param string $item 配置项
         * @param mixed $check 检查文件是否存在
         * @return null|string 返回配置文件路径，参数二位true并且文件不存在时返回null
         */
        protected static function id2path($item,$check=true){
            $dir = self::$_config['USER_CONFIG_PATH'];
            if(!is_dir($dir) or !(is_readable($dir) and is_writable($dir))) mkdir($dir,0744,true);
            $path = "{$dir}/{$item}.php";
            return ($check and !is_file($path))?null:$path;
        }

        public static function cache(){}

        /**
         * 加载配置文件
         * @param string $path 配置文件的路径
         * @param string|null $type 配置文件的类型,参数为null时根据文件名称后缀自动获取
         * @param callable $parser 配置解析方法 有些格式需要用户自己解析
         * @return array
         */
        public static function parse($path,$type=null,callable $parser=null){
            isset($type) or $type = pathinfo($path, PATHINFO_EXTENSION);
            switch ($type) {
                case self::TYPE_PHP:
                    return include $path;
                case self::TYPE_INI:
                    return parse_ini_file($path);
                case self::TYPE_YAML:
                    return yaml_parse_file($path);
                case self::TYPE_XML:
                    return (array)simplexml_load_file($path);
                case self::TYPE_JSON:
                    return json_decode(file_get_contents($path), true);
                default:
                    return $parser?$parser($path):PLiteException::throwing('无法解析配置文件');
            }
        }

    }

    /**
     * Class Filer
     * file operator
     * @package PLite
     */
    class Storage {

        /**
         * 目录存在与否
         */
        const IS_DIR    = -1;
        const IS_FILE   = 1;
        const IS_EMPTY  = 0;

        private static $_config = [
            'READ_LIMIT_ON'     => true,
            'WRITE_LIMIT_ON'    => true,
            'READABLE_SCOPE'    => PATH_BASE,
            'WRITABLE_SCOPE'    => PATH_RUNTIME,
        ];

        /**
         * @param array|null $config the config to apply
         */
        public static function init(array $config=null){
            $config and self::$_config = array_merge(self::$_config,$config);
        }

        /**
         * 检查目标目录是否可读取 并且对目标字符串进行修正处理
         *
         * $accesspath代表的是可以访问的目录
         * $path 表示正在访问的文件或者目录
         *
         * @param string $path 路径
         * @param bool $limiton 是否限制了访问范围
         * @param string|[] $scopes 范围
         * @return bool 表示是否可以访问
         */
        private static function checkAccessableWithRevise(&$path,$limiton,$scopes){
            if(!$limiton or !$scopes) return true;
            $temp = dirname($path);//修改的目录
            $path = Utils::toSystemEncode($path);
            if(is_string($scopes)){
                $scopes = [$scopes];
            }

            foreach ($scopes as $scope){
                if(Utils::checkInScope($temp,$scope)){
                    return true;
                }
            }
            return false;
        }

        /**
         * 检查是否有读取权限
         * @param string $path 路径
         * @return bool
         */
        private static function checkReadableWithRevise(&$path){
            return self::checkAccessableWithRevise($path,self::$_config['READ_LIMIT_ON'],self::$_config['READABLE_SCOPE']);
        }

        /**
         * 检查是否有写入权限
         * @param string $path 路径
         * @return bool
         */
        private static function checkWritableWithRevise(&$path){
            return self::checkAccessableWithRevise($path,self::$_config['WRITE_LIMIT_ON'],self::$_config['WRITABLE_SCOPE']);
        }
//----------------------------------------------------------------------------------------------------------------------
//------------------------------------ 读取 -----------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------

        /**
         * 读取文件夹内容，并返回一个数组(不包含'.'和'..')
         * array(
         *      //文件名称(相对于带读取的目录而言) => 文件内容
         *      'filename' => 'file full path',
         * );
         * @param $dirpath
         * @param bool $recursion 是否进行递归读取
         * @param bool $_isouter 辅助参数,用于判断是外部调用还是内部的
         * @return array
         */
        public static function readDir($dirpath, $recursion=false, $_isouter=true){
            static $_file = [];
            static $_dirpath_toread = null;
            if(!self::checkReadableWithRevise($filepath)) return null;

            if(true === $_isouter){
                //外部调用,初始化
                $_file = [];
                $_dirpath_toread = $dirpath;
            }

            $handler = opendir($dirpath);
            while (($filename = readdir( $handler )) !== false) {//未读到最后一个文件时候返回false
                if ($filename === '.' or $filename === '..' ) continue;

                $fullpath = "{$dirpath}/{$filename}";//子文件的完整路径

                if(file_exists($fullpath)) {
                    $index = strpos($fullpath,$_dirpath_toread);
                    $_file[Utils::toProgramEncode(substr($fullpath,$index+strlen($_dirpath_toread)))] =
                        str_replace('\\','/',Utils::toProgramEncode($fullpath));
                }

                if($recursion and is_dir($fullpath)) {
                    $_isouter = "{$_isouter}/{$filename}";
                    self::readDir($fullpath,$recursion,false);//递归,不清空
                }
            }
            closedir($handler);//关闭目录指针
            return $_file;
        }
        /**
         * 读取文件,参数参考read方法
         * @param string $filepath
         * @param string $file_encoding
         * @param string $readout_encoding
         * @param int|null $maxlen Maximum length of data read. The default of php is to read until end of file is reached. But I limit to 4 MB
         * @return false|string 读取失败返回false
         */
        public static function readFile($filepath, $file_encoding='UTF-8',$readout_encoding='UTF-8',$maxlen=4094304){
            if(!self::checkReadableWithRevise($filepath)) return null;
            $content = file_get_contents($filepath,null,null,null,$maxlen);//限制大小为2M
            if(false === $content) return false;//false on failure
            if(null === $file_encoding or $file_encoding === $readout_encoding){
                return $content;//return the raw content or what the read is what the need
            }else{
                $readoutEncode = "{$readout_encoding}//IGNORE";
                if(is_string($file_encoding) and false === strpos($file_encoding,',')){
                    return iconv($file_encoding,$readoutEncode,$content);
                }
                return mb_convert_encoding($content,$readoutEncode,$file_encoding);
            }
        }

        /**
         * 确定文件或者目录是否存在
         * 相当于 is_file() or is_dir()
         * @param string $filepath 文件路径
         * @return int 0表示目录不存在,<0表示是目录 >0表示是文件,可以用Storage的三个常量判断
         */
        public static function exits($filepath){
            if(!self::checkReadableWithRevise($filepath)) return null;
            if(is_dir($filepath)) return Storage::IS_DIR;
            if(is_file($filepath)) return Storage::IS_FILE;
            return Storage::IS_EMPTY;
        }

        /**
         * 返回文件内容上次的修改时间
         * @param string $filepath 文件路径
         * @param int $mtime 修改时间
         * @return int|bool|null 如果是修改时间的操作返回的bool;如果是获取修改时间,则返回Unix时间戳;
         */
        public static function mtime($filepath,$mtime=null){
            if(!self::checkReadableWithRevise($filepath)) return null;
            return file_exists($filepath)?null === $mtime?filemtime($filepath):touch($filepath,$mtime):false;
        }

        /**
         * 获取文件按大小
         * @param string $filepath 文件路径
         * @return int|false|null 按照字节计算的单位;
         */
        public static function size($filepath){
            if(!self::checkReadableWithRevise($filepath)) return null;
            return file_exists($filepath)?filesize($filepath):false;//即便是加了@filesize也无法防止系统的报错
        }

//----------------------------------------------------------------------------------------------------------------------
//------------------------------------ 写入 -----------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
        /**
         * 创建文件夹
         * @param string $dir 文件夹路径
         * @param int $auth 文件夹权限
         * @return bool 文件夹已经存在的时候返回false,成功创建返回true
         */
        public static function mkdir($dir, $auth = 0744){
            if(!self::checkWritableWithRevise($dir)) return false;
            return is_dir($dir)?chmod($dir,$auth):mkdir($dir,$auth,true);
        }

        /**
         * 修改文件权限
         * @param string $path 文件路径
         * @param int $auth 文件权限
         * @return bool 是否成功修改了该文件|返回null表示在访问的范围之外
         */
        public static function chmod($path, $auth = 0755){
            if(!self::checkWritableWithRevise($path)) return null;
            return file_exists($path)?chmod($path,$auth):false;
        }
        /**
         * 设定文件的访问和修改时间
         * 注意的是:内置函数touch在文件不存在的情况下会创建新的文件,此时创建时间可能大于修改时间和访问时间
         *         但是如果是在上层目录不存在的情况下
         * @param string $filepath 文件路径
         * @param int $mtime 文件修改时间
         * @param int $atime 文件访问时间，如果未设置，则值设置为mtime相同的值
         * @return bool 是否成功|返回null表示在访问的范围之外
         */
        public static function touch($filepath, $mtime = null, $atime = null){
            if(!self::checkWritableWithRevise($filepath)) return null;
            self::checkAndMakeSubdir($filepath);
            return touch($filepath, $mtime,$atime);
        }

        /**
         * 删除文件
         * 删除目录时必须保证该目录为空,or set parameter 2 as true
         * @param string $filepath 文件或者目录的路径
         * @param bool $recursion 删除的目标是目录时,若目录下存在文件,是否进行递归删除,默认为false
         * @return bool
         */
        public static function unlink($filepath,$recursion=false){
            if(!self::checkWritableWithRevise($filepath)) return null;
            if(is_file($filepath)){
                return unlink($filepath);
            }elseif(is_dir($filepath)){
                return self::rmdir($filepath,$recursion);
            }
            return false; //file do not exist
        }
        /**
         * @param string $filepath
         * @param string $content
         * @param string $write_encode Encode of the text to write
         * @param string $text_encode encode of content,it will be 'UTF-8' while scruipt file is encode with 'UTF-8',but sometime it's not expect
         * @return bool
         */
        public static function write($filepath,$content,$write_encode='UTF-8',$text_encode='UTF-8'){
            if(!self::checkWritableWithRevise($filepath)) return null;
            self::checkAndMakeSubdir($filepath);
            //文本编码检测
            if($write_encode !== $text_encode){//写入的编码并非是文本的编码时进行转化
                $content = iconv($text_encode,"{$write_encode}//IGNORE",$content);
            }

            //文件写入
            return file_put_contents($filepath,$content) > 0;
        }

        /**
         * 将指定内容追加到文件中
         * @param string $filepath 文件路径
         * @param string $content 要写入的文件内容
         * @param string $write_encode 写入文件时的编码
         * @param string $text_encode 文本本身的编码格式,默认使用UTF-8的编码格式
         * @return bool
         */
        public static function append($filepath,$content,$write_encode='UTF-8',$text_encode='UTF-8'){
            if(!self::checkWritableWithRevise($filepath)) return null;
            //文件不存在时
            if(!is_file($filepath)) return self::write($filepath,$content,$write_encode,$text_encode);

            //打开文件
            $handler = fopen($filepath,'a+');//追加方式，如果文件不存在则无法创建
            if(false === $handler) return false;//open failed

            //编码处理
            $write_encode !== $text_encode and $content = iconv($text_encode,"{$write_encode}//IGNORE",$content);

            //关闭文件
            $rst = fwrite($handler,$content); //出现错误时返回false
            if(false === fclose($handler)) return false;//close failed

            return $rst > 0;
        }

        /**
         * 文件父目录检测
         * @param string $path the path must be encode with file system
         * @param int $auth
         */
        private static function checkAndMakeSubdir($path, $auth = 0744){
            $path = dirname($path);
            if(!is_dir($path)) self::mkdir($path,$auth);
            if(!is_writeable($path)) self::chmod($path,$auth);
        }

        /**
         * 删除文件夹
         * 注意:@rmdir($dirpath); 也无法阻止报错
         * @param string $dir 文件夹名路径
         * @param bool $recursion 是否递归删除
         * @return bool
         */
        public static function rmdir($dir, $recursion=false){
            if(!is_dir($dir)) return false;
            //扫描目录
            $dh = opendir($dir);
            while ($file = readdir($dh)) {
                if($file === '.' or $file === '..') continue;

                if(!$recursion) {//存在其他文件或者目录,非true时循环删除
                    closedir($dh);
                    return false;
                }
                $dir = IS_WINDOWS?str_replace('\\','/',"{$dir}/{$file}"):"{$dir}/{$file}";
                if(!self::unlink($dir,$recursion)) return false;
            }
            closedir($dh);
            return rmdir($dir);
        }
    }

    /**
     * Class Utils
     * general utils for this framework
     * @package PLite
     */
    class Utils {

        /**
         * 加载显示模板
         * @param string $tpl template name in folder 'Tpl'
         * @param array|null $vars vars array to extract
         * @param bool $clean it will clean the output cache if set to true
         * @param bool $isfile 判断是否是模板文件
         */
        public static function loadTemplate($tpl,array $vars=null, $clean=true, $isfile=false){
            $clean and ob_get_level() > 0 and ob_end_clean();
            $vars and extract($vars, EXTR_OVERWRITE);
            $path = ($isfile or is_file($tpl))?$tpl:PATH_PLITE."/tpl/{$tpl}.php";
            is_file($path) or $path = PATH_PLITE.'/tpl/systemerror.php';
            include $path;
        }
        /**
         * 将C风格字符串转换成JAVA风格字符串
         * C风格      如： sub_string
         * JAVA风格   如： SubString
         * @param string $str
         * @param int $ori it will translate c to java style if $ori is set to true value and java to c style on false
         * @return string
         */
        public static function styleStr($str,$ori=1){
            static $cache = [];
            $key = "{$str}.{$ori}";
            if(!isset($cache[$key])){
                $cache[$key] = $ori?
                    ucfirst(preg_replace_callback('/_([a-zA-Z])/',function($match){return strtoupper($match[1]);},$str)):
                    strtolower(ltrim(preg_replace('/[A-Z]/', '_\\0', $str), '_'));
            }
            return $cache[$key];
        }

        /**
         * 自动从运行环境中获取URI
         * 直接访问：
         *  http://www.xor.com:8056/                => '/'
         *  http://localhost:8056/_xor/             => '/_xor/'  ****** BUG *******
         * @param bool $reget 是否重新获取，默认为false
         * @return null|string
         */
        public static function pathInfo($reget=false){
            static $uri = '/';
            if($reget or '/' === $uri){
                if(isset($_SERVER['PATH_INFO'])){
                    //如果设置了PATH_INFO则直接获取之
                    $uri = $_SERVER['PATH_INFO'];
                }else{
                    $scriptlen = strlen($_SERVER['SCRIPT_NAME']);
                    if(strlen($_SERVER['REQUEST_URI']) > $scriptlen){
                        $pos = strpos($_SERVER['REQUEST_URI'],$_SERVER['SCRIPT_NAME']);
                        if(false !== $pos){
                            //在不支持PATH_INFO...或者PATH_INFO不存在的情况下(URL省略将被认定为普通模式)
                            //REQUEST_URI获取原生的URL地址进行解析(返回脚本名称后面的部分)
                            if(0 === $pos){//PATHINFO模式
                                $uri = substr($_SERVER['REQUEST_URI'], $scriptlen);
                            }else{
                                //重写模式
                                $uri = $_SERVER['REQUEST_URI'];
                            }
                        }
                    }else{}//URI短于SCRIPT_NAME，则PATH_INFO等于'/'
                }
            }
            return $uri;
        }

        /**
         * 转换成php处理文件系统时所用的编码
         * 即UTF-8转GB2312
         * @param string $str 待转化的字符串
         * @param string $strencode 该字符串的编码格式
         * @return string|false 转化失败返回false
         */
        public static function toSystemEncode($str,$strencode='UTF-8'){
            return iconv($strencode,FS_ENCODING.'//IGNORE',$str);
        }

        /**
         * 转换成程序使用的编码
         * 即GB2312转UTF-8
         * @param string $str 待转换的字符串
         * @param string $program_encoding
         * @return string|false 转化失败返回false
         */
        public static function toProgramEncode($str, $program_encoding='UTF-8'){
            return iconv(FS_ENCODING,"{$program_encoding}//IGNORE",$str);
        }

        /**
         * 获取类常量
         * use defined() to avoid error of E_WARNING level
         * @param string $class 完整的类名称
         * @param string $constant 常量名称
         * @param mixed $replacement 不存在时的代替
         * @return mixed
         */
        public static function constant($class,$constant,$replacement=null){
            if(!class_exists($class,true)) return $replacement;
            $constant = "{$class}::{$constant}";
            return defined($constant)?constant($constant):$replacement;
        }

        /**
         * 将参数二的配置合并到参数一种，如果存在参数一数组不存在的配置项，跳过其设置
         * @param array $dest dest config
         * @param array $sourse sourse config whose will overide the $dest config
         * @param bool|false $cover it will merge the target in recursion while $cover is true
         *                  (will perfrom a high efficiency for using the built-in function)
         * @return mixed
         */
        public static function merge(array $dest,array $sourse,$cover=false){
            foreach($sourse as $key=>$val){
                $exists = key_exists($key,$dest);
                if($cover){
                    //覆盖模式
                    if($exists and is_array($dest[$key])){
                        //键存在 为数组
                        $dest[$key] = self::merge($dest[$key],$val,true);
                    }else{
                        //key not exist or not array 直接覆盖
                        $dest[$key] = $val;
                    }
                }else{
                    //非覆盖模式
                    $exists and $dest[$key] = $val;
                }
            }
            return $dest;
        }

        /**
         * 过滤掉数组中与参数二计算值相等的值，可以是保留也可以是剔除
         * @param array $array
         * @param callable|array|mixed $comparer
         * @param bool $leave
         * @return void
         */
        public static function filter(array &$array, $comparer=null, $leave=true){
            static $result = [];
            $flag = is_callable($comparer);
            $flag2 = is_array($comparer);
            foreach ($array as $key=>$val){
                if($flag?$comparer($key,$val):($flag2?in_array($val,$comparer):($comparer === $val))){
                    if($leave){
                        unset($array[$key]);
                    }else{
                        $result[$key] = $val;
                    }
                }
            }
            $leave or $array = $result;
        }

        /**
         * 从字面商判断$path是否被包含在$scope的范围内
         * @param string $path 路径
         * @param string $scope 范围
         * @return bool
         */
        public static function checkInScope($path, $scope) {
            if (false !== strpos($path, '\\')) $path = str_replace('\\', '/', $path);
            if (false !== strpos($scope, '\\')) $scope = str_replace('\\', '/', $scope);
            $path = rtrim($path, '/');
            $scope = rtrim($scope, '/');
            return (IS_WINDOWS ? stripos($path, $scope) : strpos($path, $scope)) === 0;
        }



        //-------------------------------------------------------------------------------------
        //--------------------------- For Router and url Creater ----------------------------------------------
        //-------------------------------------------------------------------------------------
        /**
         * 模块序列转换成数组形式
         * 且数组形式的都是大写字母开头的单词形式
         * @param string|array $modules 模块序列
         * @param string $mmbridge 模块之间的分隔符
         * @return array
         * @throws \Exception
         */
        public static function toModulesArray($modules, $mmbridge='/'){
            if(is_string($modules)){
                if(false === stripos($modules,$mmbridge)){
                    $modules = [$modules];
                }else{
                    $modules = explode($mmbridge,$modules);
                }
            }
            is_array($modules) or PLiteException::throwing('Parameter should be an array!');
            return array_map(function ($val) {
                return Utils::styleStr($val,1);
            }, $modules);
        }

        /**
         * 模块学列数组转换成模块序列字符串
         * 模块名称全部小写化
         * @param array|string $modules 模块序列
         * @param string $mmb
         * @return string
         * @throws PLiteException
         */
        public static function toModulesString($modules,$mmb='/'){
            if(is_array($modules)){
                $modules = implode($mmb,$modules);
            }
            is_string($modules) or PLiteException::throwing('Invalid Parameters!');
            return trim($modules,' /');
        }
        /**
         * 将参数序列装换成参数数组，应用Router模块的配置
         * @param string $params 参数字符串
         * @param string $ppb
         * @param string $pkvb
         * @return array
         */
        public static function toParametersArray($params,$ppb='/',$pkvb='/'){//解析字符串成数组
            $pc = [];
            if($ppb !== $pkvb){//使用不同的分割符
                $parampairs = explode($ppb,$params);
                foreach($parampairs as $val){
                    $pos = strpos($val,$pkvb);
                    if(false === $pos){
                        //非键值对，赋值数字键
                    }else{
                        $key = substr($val,0,$pos);
                        $val = substr($val,$pos+strlen($pkvb));
                        $pc[$key] = $val;
                    }
                }
            }else{//使用相同的分隔符
                $elements = explode($ppb,$params);
                $count = count($elements);
                for($i=0; $i<$count; $i += 2){
                    if(isset($elements[$i+1])){
                        $pc[$elements[$i]] = $elements[$i+1];
                    }else{
                        //单个将被投入匿名参数,先废弃
                    }
                }
            }
            return $pc;
        }

        /**
         * 将参数数组转换成参数序列，应用Router模块的配置
         * @param array $params 参数数组
         * @param string $ppb
         * @param string $pkvb
         * @return string
         */
        public static function toParametersString(array $params=null,$ppb='/',$pkvb='/'){
            //希望返回的是字符串是，返回值是void，直接修改自$params
            if(empty($params)) return '';
            $temp = '';
            if($params){
                foreach($params as $key => $val){
                    $temp .= "{$key}{$pkvb}{$val}{$ppb}";
                }
                return substr($temp,0,strlen($temp) - strlen($ppb));
            }else{
                return $temp;
            }
        }

    }

    /**
     * Class Dispatcher
     * 将URI解析结果调度到指定的控制器下的方法下
     * @package PLite\Core
     */
    class Dispatcher {

        private static $_module = null;
        private static $_controller = null;
        private static $_action = null;
        /**
         * @var array
         */
        private static $_config = [
            //空缺时默认补上,Done!
            'INDEX_MODULE'      => 'Home',
            'INDEX_CONTROLLER'  => 'Index',
            'INDEX_ACTION'      => 'index',
        ];

        /**
         * @param array|null $config
         */
        public static function init(array $config=null){
            $config and self::$_config = array_merge(self::$_config,$config);
        }

        /**
         * 匹配空缺补上默认
         * @param string|array $modules
         * @param string $ctrler
         * @param string $action
         * @return void
         */
        public static function checkDefault($modules,$ctrler,$action){
            self::$_module      = $modules?$modules:self::$_config['INDEX_MODULE'];
            self::$_controller  = $ctrler?$ctrler:self::$_config['INDEX_CONTROLLER'];
            self::$_action      = $action?$action:self::$_config['INDEX_ACTION'];

            self::$_module and is_array(self::$_module) and self::$_module = implode('/',self::$_module);
        }

        public static function checkCache($modules=null,$ctrler=null,$action=null){

        }

        /**
         * 制定对应的方法
         * @param string $modules
         * @param string $ctrler
         * @param string $action
         * @return mixed
         * @throws PLiteException
         */
        public static function exec($modules=null,$ctrler=null,$action=null){
            null === $modules   and $modules = self::$_module;
            null === $ctrler    and $ctrler = self::$_controller;
            null === $action    and $action = self::$_action;

            Debugger::trace($modules,$ctrler,$action);

            $modulepath = PATH_BASE."/Application/{$modules}";//linux 不识别 \\

            strpos($modules,'/') and $modules = str_replace('/','\\',$modules);

            //模块检测
            is_dir($modulepath) or PLiteException::throwing("Module '{$modules}' not found!");

            //在执行方法之前定义常量,为了能在控制器的构造函数中使用这三个常量
            define('REQUEST_MODULE',$modules);//请求的模块
            define('REQUEST_CONTROLLER',$ctrler);//请求的控制器
            define('REQUEST_ACTION',$action);//请求的操作

            //控制器名称及存实性检测
            $className = "Application\\{$modules}\\Controller\\{$ctrler}";
            class_exists($className) or PLiteException::throwing($modules,$className);
            $classInstance =  new $className();
            //方法检测
            method_exists($classInstance,$action) or PLiteException::throwing($modules,$className,$action);
            $method = new \ReflectionMethod($classInstance, $action);

            $result = null;
            if ($method->isPublic() and !$method->isStatic()) {//仅允许非静态的公开方法
                //方法的参数检测
                if ($method->getNumberOfParameters()) {//有参数
                    $args = self::fetchMethodArgs($method);
                    //执行方法
                    $result = $method->invokeArgs($classInstance, $args);
                } else {//无参数的方法调用
                    $result = $method->invoke($classInstance);
                }
            } else {
                PLiteException::throwing($className, $action);
            }

            Debugger::status('execute_end');
            return $result;
        }



        /**
         * 获取传递给盖饭昂奋的参数
         * @param \ReflectionMethod $targetMethod
         * @return array
         * @throws PLiteException
         */
        private static function fetchMethodArgs(\ReflectionMethod $targetMethod){
            //获取输入参数
            $vars = $args = [];
            switch(strtoupper($_SERVER['REQUEST_METHOD'])){
                case 'POST':$vars    =  array_merge($_GET,$_POST);  break;
                case 'PUT':parse_str(file_get_contents('php://input'), $vars);  break;
                default:$vars  =  $_GET;
            }
            //获取方法的固定参数
            $methodParams = $targetMethod->getParameters();
            //遍历方法的参数
            foreach ($methodParams as $param) {
                $paramName = $param->getName();

                if(isset($vars[$paramName])){
                    $args[] =   $vars[$paramName];
                }elseif($param->isDefaultValueAvailable()){
                    $args[] =   $param->getDefaultValue();
                }else{
                    return PLiteException::throwing("目标缺少参数'{$param}'!");
                }
            }
            return $args;
        }

        /**
         * 加载当前访问的模块的指定配置
         * 配置目录在模块目录下的'Common/Conf'
         * @param string $name 配置名称,多个名称以'/'分隔
         * @param string $type 配置类型,默认为php
         * @return array
         */
        public static function load($name,$type=Configger::TYPE_PHP){
            if(!defined('REQUEST_MODULE')) return PLiteException::throwing('\'load\'必须在\'exec\'方法之后调用!');//前提是正确制定过exec方法
            $path = PATH_BASE.'/Application/'.REQUEST_MODULE.'/Common/Config/';

            if(Storage::exits($path) === Storage::IS_DIR){
                $file = "{$path}/{$name}.".$type;
                return Configger::load($file);
            }
            return [];
        }

    }


    /**
     * Class Response 输出控制类
     * @package PLite\library
     */
    class Response {

        /**
         * 数据返回形式
         */
        const AJAX_JSON     = 0;
        const AJAX_XML      = 1;
        const AJAX_STRING   = 2;

        /**
         * 返回的消息类型
         */
        const MESSAGE_TYPE_SUCCESS = 1;
        const MESSAGE_TYPE_WARNING = -1;
        const MESSAGE_TYPE_FAILURE = 0;

        /**
         * 向浏览器客户端发送不缓存命令
         * @param bool $clean clean the output before,important and default to true
         * @return void
         */
        public static function sendNocache($clean=true){
            $clean and self::cleanOutput();
            header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
            header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
            header( 'Cache-Control: no-store, no-cache, must-revalidate' );
            header( 'Cache-Control: post-check=0, pre-check=0', false );
            header( 'Pragma: no-cache' );
        }

        /**
         * HTTP Protocol defined status codes
         * @param int $code
         */
        public static function sendHttpStatus($code) {
            static $_status = array(
                // Informational 1xx
                100 => 'Continue',
                101 => 'Switching Protocols',

                // Success 2xx
                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                203 => 'Non-Authoritative Information',
                204 => 'No Content',
                205 => 'Reset Content',
                206 => 'Partial Content',

                // Redirection 3xx
                300 => 'Multiple Choices',
                301 => 'Moved Permanently',
                302 => 'Found',  // 1.1
                303 => 'See Other',
                304 => 'Not Modified',
                305 => 'Use Proxy',
                // 306 is deprecated but reserved
                307 => 'Temporary Redirect',

                // Client Error 4xx
                400 => 'Bad Request',
                401 => 'Unauthorized',
                402 => 'Payment Required',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                406 => 'Not Acceptable',
                407 => 'Proxy Authentication Required',
                408 => 'Request Timeout',
                409 => 'Conflict',
                410 => 'Gone',
                411 => 'Length Required',
                412 => 'Precondition Failed',
                413 => 'Request Entity Too Large',
                414 => 'Request-URI Too Long',
                415 => 'Unsupported Media Type',
                416 => 'Requested Range Not Satisfiable',
                417 => 'Expectation Failed',

                // Server Error 5xx
                500 => 'Internal Server Error',
                501 => 'Not Implemented',
                502 => 'Bad Gateway',
                503 => 'Service Unavailable',
                504 => 'Gateway Timeout',
                505 => 'HTTP Version Not Supported',
                509 => 'Bandwidth Limit Exceeded'
            );
            self::cleanOutput();
            isset($_status[$code]) and header('HTTP/1.1 '.$code.' '.$_status[$code]);
        }

        /**
         * 清空输出缓存
         * @return void
         */
        public static function cleanOutput(){
            ob_get_level() > 0 and ob_end_clean();
        }

        /**
         * flush the cache to client
         */
        public static function flushOutput(){
            ob_get_level() and ob_end_flush();
        }

        /**
         * return the request in ajax way
         * and call this method will exit the script
         * @access protected
         * @param mixed $data general type of data
         * @param int $type AJAX返回数据格式
         * @param int $options 传递给json_encode的option参数
         * @return void
         * @throws \Exception
         */
        public static function ajaxBack($data, $type = self::AJAX_JSON, $options = 0){
            self::cleanOutput();
            switch (strtoupper($type)) {
                case self::AJAX_JSON :// 返回JSON数据格式到客户端 包含状态信息
                    header('Content-Type:application/json; charset=utf-8');
                    exit(json_encode($data, $options));
                case self::AJAX_XML :// 返回xml格式数据
                    header('Content-Type:text/xml; charset=utf-8');
                    exit(XMLHelper::encode($data));
                case self::AJAX_STRING:
                    header('Content-Type:text/plain; charset=utf-8');
                    exit($data);
                default:
                    PLiteException::throwing('Invalid output type!');
            }
        }
    }

    /**
     * Class Router
     * @package PLite\Core
     */
    class Router {

        private static $_config = [
            //------------------------
            //For URL route
            //------------------------
            'URI_ROUTE_ON'          => true,//总开关,是否对URI地址进行路由
            'STATIC_ROUTE_ON'       => true,
            //静态路由规则
            'STATIC_ROUTE_RULES'    => [],
            'WILDCARD_ROUTE_ON'     => false,
            //通配符路由规则,具体参考CodeIgniter
            'WILDCARD_ROUTE_RULES'  => [],
            'REGULAR_ROUTE_ON'      => true,
            //正则表达式 规则
            'REGULAR_ROUTE_RULES'   => [],

            //------------------------
            //For URL parser
            //------------------------
            //API模式，直接使用$_GET
            'API_MODE_ON'   => false,
            //API模式 对应的$_GET变量名称
            'API_MODULES_VARIABLE'   => '_m',//该模式下使用到多层模块时涉及'MM_BRIDGE'的配置
            'API_CONTROLLER_VARIABLE'   => '_c',
            'API_ACTION_VARIABLE'   => '_a',

            //普通模式
            'MASQUERADE_TAIL'   => '.html',
            //重写模式下 消除的部分，对应.htaccess文件下
            'REWRITE_HIDDEN'      => '/index.php',
            'MM_BRIDGE'     => '/',//模块与模块之间的连接
            'MC_BRIDGE'     => '/',
            'CA_BRIDGE'     => '/',
            //*** 必须保证操作与控制器之间的符号将是$_SERVER['PATH_INFO']字符串中第一个出现的,为了更好地显示URL，参数一般通过POST传递
            //特别注意的是若使用了问号，则后面的字符串将被认为是请求参数
            'AP_BRIDGE'     => '-',
            'PP_BRIDGE'     => '-',//参数与参数之间的连接桥
            'PKV_BRIDGE'    => '-',//参数的键值对之前的连接桥

            //是否开启域名部署（包括子域名部署）
            'DOMAIN_DEPLOY_ON'  => true,
            //子域名部署模式下 的 完整域名
            'DOMAIN_NAME'       =>'linzhv.com',
            //是否将子域名段和模块进行映射
            'SUBDOMAIN_AUTO_MAPPING_ON' => false,
            //子域名部署规则
            //注意参与array_flip()函数,键值互换
            'SUBDOMAIN_MAPPINIG' => [],

            //使用的协议名称
            'SERVER_PROTOCOL' => 'http',
            //使用的端口号，默认为80时会显示为隐藏
            'SERVER_PORT'   => 80,
        ];


        /**
         * 返回解析结果
         * @var array
         */
        private static $result = [
            'm' => null,
            'c' => null,
            'a' => null,
            'p' => null,
        ];


        /**
         * @param array|null $config
         */
        public static function init(array $config=null){
            $config and self::$_config = array_merge(self::$_config,$config);
        }

        /**
         * 解析路由规则
         * @param string|null $url 请求路径
         * @return array|null|string
         */
        public static function parseRoute($url=null){
            $url or $url = $_SERVER['REQUEST_URI'];

            //静态路由
            if(self::$_config['STATIC_ROUTE_ON'] and self::$_config['STATIC_ROUTE_RULES']){
                if(isset(self::$_config['STATIC_ROUTE_RULES'][$url])){
                    return self::$_config['STATIC_ROUTE_RULES'][$url];
                }
            }
            //规则路由
            if(self::$_config['WILDCARD_ROUTE_ON'] and self::$_config['WILDCARD_ROUTE_RULES']){
                foreach(self::$_config['WILDCARD_ROUTE_RULES'] as $pattern => $rule){
                    // Convert wildcards to RegEx（from CI）
                    //any对应非/的任何字符 num对应数字
                    $pattern = str_replace(array('[any]', '[num]'), array('([^/]+)', '([0-9]+)'), $pattern);
//                $pattern = preg_replace('/\[.+?\]/','([^/\[\]]+)',$pattern);//非贪婪匹配
                    $rst = self::_matchRegular($pattern,$rule, $url);
                    if(null !== $rst) return $rst;
                }
            }
            //正则路由
            if(self::$_config['REGULAR_ROUTE_ON'] and self::$_config['REGULAR_ROUTE_RULES']){
                foreach(self::$_config['REGULAR_ROUTE_RULES'] as $pattern => $rule){
                    $rst = self::_matchRegular($pattern,$rule, $url);
                    if(null !== $rst) return $rst;
                }
            }
            return null;
        }



        /**
         * 使用正则表达式匹配uri
         * @param string $pattern 路由规则
         * @param array|string|callable $rule 路由导向结果
         * @param string $uri 传递进来的URL字符串
         * @return array|string|null
         */
        private static function _matchRegular($pattern, $rule, $uri){
            $result = null;
            // do the RegEx match? use '#' to ignore '/'
            if (preg_match('#^'.$pattern.'$#', $uri, $matches)) {
                if(is_array($rule)){
                    $len = count($matches);
                    for($i = 1; $i < $len; $i++){
                        $key = '$'.$i;
                        if(isset($rule['$'.$i])){
                            $v = (string)$rule[$key];
                            if(strpos($v,'.')){
                                $a = explode('.',$v);
                                empty($rule[$a[0]]) and $rule[$a[0]] = [];
                                $rule[$a[0]][$a[1]] = $matches[$i];
                            }else{
                                $rule[$v] = $matches[$i];
                            }
                        }else{
                            empty($rule['o']) and $rule['o'] = [];
                            $rule['o'][] = $matches[$i];
                        }
                        unset($rule[$key]);
                    }
                    $result = $rule;
                }elseif(is_string($rule)){
                    $result = preg_replace('#^'.$pattern.'$#', $rule, $uri);//参数一代表的正则表达式从参数三的字符串中寻找匹配并替换到参数二代表的字符串中
                }elseif(is_callable($rule)){
                    array_shift($matches);
                    // Execute the callback using the values in matches as its parameters.
                    $result = call_user_func_array($rule, $matches);//参数二是完整的匹配
                    if($result === true){
                        //返回true表示直接完成
                        exit();
                    }elseif(!is_string($result) and !is_array($result)){
                        //要求结果必须返回string或者数组
                        return null;
                    }
                }
            }
            return $result;
        }

        /**
         * 解析URI
         * @param string $uri 请求的URI
         * @param string $hostname
         * @return array
         */
        public static function parseURL($uri=null,$hostname=null){
            //API模式下
            if(self::$_config['API_MODE_ON']){
                self::parseInAPI();
            }else{
                $uri or $uri = Utils::pathInfo(true);
                //解析域名部署
                if(self::$_config['DOMAIN_DEPLOY_ON']){
                    $hostname or $hostname = $_SERVER['SERVER_NAME'];
                    self::parseHostname($hostname);//如果绑定了模块，之后的解析将无法指定模块
                }
                //检查、寻找和解析URI路由 'URI_ROUTE_ON'
                //普通模式下解析URI地址
                self::parseInCommon($uri);
            }
            return self::$result;
        }

        /**
         * 按照API模式进行解析(都组最快)
         * 保持原样
         * @return void
         */
        private static function parseInAPI(){
            Debugger::status('fetchurl_in_topspeed_begin');
            $vars = [
                self::$_config['API_MODULES_VARIABLE'],
                self::$_config['API_CONTROLLER_VARIABLE'],
                self::$_config['API_ACTION_VARIABLE'],
            ];
            //获取模块名称
            isset($_GET[$vars[0]]) and self::$result['m'] = $_GET[$vars[0]];
            //获取控制器名称
            isset($_GET[$vars[1]]) and self::$result['c'] = $_GET[$vars[1]];
            //获取操作名称，类方法不区分大小写
            isset($_GET[$vars[2]]) and self::$result['a'] = $_GET[$vars[2]];
            //参数为剩余的变量
            unset($_GET[$vars[0]],$_GET[$vars[1]],$_GET[$vars[2]]);
            self::$result['p'] = $_GET;

            Debugger::status('fetchurl_in_topspeed_end');
        }

        /**
         * 按照普通模式进行URI解析
         * @param string $uri 待解析的URI
         * @return void
         */
        private static function parseInCommon($uri){
            Debugger::status('parseurl_in_common_begin');
            $bridges = [
                'mm'  => self::$_config['MM_BRIDGE'],
                'mc'  => self::$_config['MC_BRIDGE'],
                'ca'  => self::$_config['CA_BRIDGE'],
                'ap'  => self::$_config['AP_BRIDGE'],
                'pp'  => self::$_config['PP_BRIDGE'],
                'pkv'  => self::$_config['PKV_BRIDGE'],
            ];
            self::stripMasqueradeTail($uri);

            //-- 解析PATHINFO --//
            //截取参数段param与定位段local
            $papos          = strpos($uri,$bridges['ap']);
            $mcapart = null;
            $pparts = '';
            if(false === $papos){
                $mcapart  = trim($uri,'/');//不存在参数则认定PATH_INFO全部是MCA的部分，否则得到结果substr($uri,0,0)即空字符串
            }else{
                $mcapart  = trim(substr($uri,0,$papos),'/');
                $pparts   = substr($uri,$papos + strlen($bridges['ap']));
            }

            //-- 解析MCA部分 --//
            //逆向检查CA是否存在衔接
            $mcaparsed = self::parseMCA($mcapart,$bridges);
            self::$result = array_merge(self::$result,$mcaparsed);

            //-- 解析参数部分 --//
            self::$result['p'] = Utils::toParametersArray($pparts,$bridges['pp'],$bridges['pkv']);
            Debugger::status('parseurl_in_common_end');
        }

        /**
         * 解析主机名
         * 如果找到了对应的主机名称，则绑定到对应的模块
         * @param string $hostname 访问的主机名
         * @return bool 返回是否绑定了模块
         */
        private static function parseHostname($hostname){
            $subdomain = strstr($hostname,self::$_config['DOMAIN_NAME'],true);
            if($subdomain !== false){
                $subdomain = rtrim($subdomain,'.');
                if(isset(self::$_config['SUBDOMAIN_MAPPINIG'][$subdomain])){
                    self::$result['m'] = self::$_config['SUBDOMAIN_MAPPINIG'][$subdomain];
                }elseif(self::$_config['SUBDOMAIN_AUTO_MAPPING_ON']){
                    if(false !== strpos($subdomain,'.')){
                        self::$result['m'] = array_map(function ($val) {
                            return Utils::styleStr($val,1);
                        }, explode('.',$subdomain));
                    }else{
                        self::$result['m'] = ucfirst($subdomain);
                    }
                }
            }
            return false;
        }

        /**
         * 解析"模块、控制器、操作"
         * @param $mcapart
         * @param $bridges
         * @return array
         */
        private static function parseMCA($mcapart,$bridges){
            $parsed = ['m'=>null,'c'=>null,'a'=>null];
            $capos = strrpos($mcapart,$bridges['ca']);
            if(false === $capos){
                //找不到控制器与操作之间分隔符（一定不存在控制器）
                //先判断位置部分是否为空字符串来决定是否有操作名称
                if(strlen($mcapart)){
                    //位置字段全部是字符串的部分
                    $parsed['a'] = $mcapart;
                }else{
                    //没有操作部分，MCA全部使用默认的
                }
            }else{
                //apos+CA_BRIDGE 后面的部分全部算作action
                $parsed['a'] = substr($mcapart,$capos+strlen($bridges['ca']));

                //CA存在衔接符 则说明一定存在控制器
                $mcalen = strlen($mcapart);
                $mcpart = substr($mcapart,0,$capos-$mcalen);//去除了action的部分

                if(strlen($mcapart)){
                    $mcpos = strrpos($mcpart,$bridges['mc']);
                    if(false === $mcpos){
                        //不存在模块
                        if(strlen($mcpart)){
                            //全部是控制器的部分
                            $parsed['c'] = $mcpart;
                        }else{
                            //没有控制器部分，则使用默认的
                        }
                    }else{
                        //截取控制器的部分
                        $parsed['c']   = substr($mcpart,$mcpos+strlen($bridges['mc']));

                        //既然存在MC衔接符 说明一定存在模块
                        $mpart = substr($mcpart,0,$mcpos-strlen($mcpart));//以下的全是模块部分的字符串
                        if(strlen($mpart)){
                            if(false === strpos($mpart,$bridges['mm'])){
                                $parsed['m'] = $mpart;
                            }else{
                                $parsed['m'] = explode($bridges['mm'],$mpart);
                            }
                        }else{
                            //一般存在衔接符的情况下不为空,但也考虑下特殊情况
                        }
                    }
                }else{
                    //一般存在衔接符的情况下不为空,但也考虑下特殊情况
                }
            }
            return $parsed;
        }
        /**
         * 删除伪装的url后缀
         * @param string|array $uri 需要去除尾巴的字符串或者字符串数组（当数组中存在其他元素时忽略）
         * @return void
         */
        private static function stripMasqueradeTail(&$uri){
            $uri = trim($uri);
            $position = stripos($uri,self::$_config['MASQUERADE_TAIL']);
            //$position === false 表示 不存在伪装的后缀或者相关带嫌疑的url部分

            if(false !== $position and strlen($uri) === ($position + strlen(self::$_config['MASQUERADE_TAIL'])) ){
                //伪装的后缀存在且只出现在最后的位置时
                $uri = substr($uri,0,$position);
            }
        }
    }
}