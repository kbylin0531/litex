<?php

namespace {
    use PLite\AutoLoader;
    use PLite\Core\Cache;
    use PLite\Debugger;
    use PLite\PLiteException;
    use PLite\Response;
    use PLite\Utils;
    use PLite\Core\Dispatcher;
    use PLite\Core\Router;

    const LITE_VERSION = 0.728;
//---------------------------------- mode constant -------------------------------------//
    defined('DEBUG_MODE_ON') or define('DEBUG_MODE_ON', true);
    defined('PAGE_TRACE_ON') or define('PAGE_TRACE_ON', true);//在处理微信签名检查时会发生以外的错误
//    defined('LITE_ON')       or define('LITE_ON', true);
    defined('INSPECT_ON')    or define('INSPECT_ON',false);

    defined('FS_ENCODING')  or define('FS_ENCODING','GB2312');//file system encoding

    const EXCEPTION_CLEAN = false;//it will clean the output before if error or exception occur
    const DRIVER_KEY_WITH_PARAM = false;//for trait 'PLite\D' ,if set to true,it will serialize the parameters of contructor which may use a lot of resource


//---------------------------------- environment constant -------------------------------------//
    //It is different to thinkphp that the beginning time is the time of request comming
    //and ThinkPHP is just using the time of calling 'microtime(true)' which ignore the loading and parsing of "ThinkPHP.php" and its include files.
    //It could always keeped in 10ms from request beginning to script shutdown.
    define('REQUEST_MICROTIME', isset($_SERVER['REQUEST_TIME_FLOAT'])? $_SERVER['REQUEST_TIME_FLOAT']:microtime(true));//(int)($_SERVER['REQUEST_TIME_FLOAT']*1000)
    define('REQUEST_TIME',$_SERVER['REQUEST_TIME']);

    //record status at the beginning
    $GLOBALS['_status_begin'] = [
        REQUEST_MICROTIME,
        memory_get_usage(),
    ];
    const IS_CLIENT = PHP_SAPI === 'cli';
    define('IS_WINDOWS',false !== stripos(PHP_OS, 'WIN'));//const IS_WINDOWS = PHP_OS === 'WINNT';
    define('IS_REQUEST_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ));
    define('IS_METHOD_POST',$_SERVER['REQUEST_METHOD'] === 'POST');//“GET”, “HEAD”，“POST”，“PUT”

    define('HTTP_PREFIX', (isset ($_SERVER ['HTTPS']) and $_SERVER ['HTTPS'] === 'on') ? 'https://' : 'http://' );
    define('__PUBLIC__',rtrim(empty($_SERVER['SERVER_PORT']) || 80 === $_SERVER['SERVER_PORT']?
        HTTP_PREFIX.$_SERVER['SERVER_NAME']:
        HTTP_PREFIX.$_SERVER['SERVER_NAME'].':80'.dirname($_SERVER['SCRIPT_NAME']),'/'));//define('__PUBLIC__',dirname($_SERVER['SCRIPT_NAME']));

//---------------------------------- general constant ------------------------------//
    const TYPE_BOOL     = 'boolean';
    const TYPE_INT      = 'integer';
    const TYPE_FLOAT    = 'double';//double ,  float
    const TYPE_STR      = 'string';
    const TYPE_ARRAY    = 'array';
    const TYPE_OBJ      = 'object';
    const TYPE_RESOURCE = 'resource';
    const TYPE_NULL     = 'NULL';
    const TYPE_UNKNOWN  = 'unknown type';

    const PRIOR_INDEX           = 'PRIOR_INDEX';
    const DRIVER_CLASS_LIST     = 'DRIVER_CLASS_LIST';
    const DRIVER_CONFIG_LIST    = 'DRIVER_CONFIG_LIST';

    const AJAX_JSON     = 0;
    const AJAX_XML      = 1;
    const AJAX_STRING   = 2;

//---------------------------------- path constant -------------------------------------//
    define('PATH_BASE', IS_WINDOWS?str_replace('\\','/',dirname(__DIR__)):dirname(__DIR__));
    defined('APP_DIR')  or define('APP_DIR','Application');//dir name opposide to base path
    defined('APP_PATH') or define('PATH_APP',PATH_BASE.'/'.APP_DIR);
    const PATH_PLITE    = PATH_BASE.'/PLite';
    const PATH_CONFIG   = PATH_BASE.'/Config';
    const PATH_RUNTIME  = PATH_BASE.'/Runtime';
    const PATH_PUBLIC   = PATH_BASE.'/Public';

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

        ];

        /**
         * 初始化应用程序
         * @param array|null $config
         * @return void
         */
        public static function init(array $config=null){
            Debugger::import('app_begin',$GLOBALS['_status_begin']);
            Debugger::status('app_init_begin');
            $config and self::$_config = Utils::merge(self::$_config,$config);

            //environment
            version_compare(PHP_VERSION,'5.4.0','<') and die('Require php >= 5.4 !');
            date_default_timezone_set(self::$_config['ZONE']) or die('Date default timezone set failed!');

            //error  display
            error_reporting(DEBUG_MODE_ON?-1:E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);//php5.3version use code: error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
            ini_set('display_errors',DEBUG_MODE_ON?1:0);

            //behavior
            spl_autoload_register([AutoLoader::class,'load']) or die('Faile to register class autoloader!');
            self::registerErrorHandler(self::$_config['ERROR_HANDLER']);
            self::registerExceptionHandler(self::$_config['EXCEPTION_HANDLER']);

            register_shutdown_function(function (){/* called when script shut down */
                PAGE_TRACE_ON and !IS_REQUEST_AJAX and Debugger::trace();//show the trace info
//                $gap = microtime(true) - $GLOBALS['_status_begin'][0];//begin to end
                Debugger::status('script_shutdown');
            });

            //function pack
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
            self::$_app_need_inited = false;
        }

        private static $_app_need_inited = true;

        /**
         * start application
         * @param array|null $config
         */
        public static function start(array $config=null){
            self::$_app_need_inited and self::init($config);
            Debugger::status('app_start');
            $identify = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

            $content = Cache::get($identify,null);
            if(null !== $content){
                Debugger::trace('load from cache');
                echo $content;
            }else{
                //打开输出控制缓冲
                ob_start();


                Router::__initializationize();
                //parse uri
                $result = self::$_config['ROUTE_ON']?Router::parseRoute():null;
                $result or $result = Router::parseURL();
                //URL中解析结果合并到$_GET中，$_GET的其他参数不能和之前的一样，否则会被解析结果覆盖,注意到$_GET和$_REQUEST并不同步，当动态添加元素到$_GET中后，$_REQUEST中不会自动添加
                empty($result['p']) or $_GET = array_merge($_GET,$result['p']);

                Debugger::status('dispatch_begin');
                //dispatch
                $ckres = Dispatcher::checkDefault($result['m'],$result['c'],$result['a']);

                //在执行方法之前定义常量,为了能在控制器的构造函数中使用这三个常量
                define('REQUEST_MODULE',$ckres['m']);//请求的模块
                define('REQUEST_CONTROLLER',$ckres['c']);//请求的控制器
                define('REQUEST_ACTION',$ckres['a']);//请求的操作

                $result = Dispatcher::exec();
                echo $content = Response::getOutput();

                //exec的结果将用于判断输出缓存，如果为int，表示缓存时间，0表示无限缓存,将来将创造更多的扩展，目前仅限于int
                if(isset($result)){
                    Cache::set($identify,$content,$result)?Debugger::trace('build cache success!'):Debugger::trace('failed to build cache!');
                }
            }
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

    use PLite\Core\Configger;
    use PLite\Util\Helper\XMLHelper;
    use PLite\Util\SEK;
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
         * @var bool
         */
        protected static $_allowTrace = true;
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
         * 开启Trace
         * @return void
         */
        public static function openTrace(){
            self::$_allowTrace = true;
        }

        /**
         * 关闭trace
         * @return void
         */
        public static function closeTrace(){
            self::$_allowTrace = false;
        }

        /**
         * 记录运行时的内存和时间状态
         * @param null|string $tag tag of runtime point
         * @return void
         */
        public static function status($tag){
            self::$_status[$tag] = [
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
         * @return string|bool
         */
        public static function trace($message=null){
            if(!DEBUG_MODE_ON) return false;
            if(null === $message and self::$_allowTrace){
                return SEK::showTrace(self::$_status,self::$_traces);
            }else{
                $location = debug_backtrace();
                $location = "{$location[0]['file']}:{$location[0]['line']}";
                if(func_num_args() > 1) $message = var_export(func_get_args(),true);
                if(!is_string($message)) $message = var_export($message,true);
                return self::$_traces[$location] = $message;
            }
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
            //auto config class,defined by commoon
            Utils::callStatic($clsnm,'__initializationize');
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
        public static function handleException($e) {
            if(IS_REQUEST_AJAX){
                exit($e->getMessage());
            }
            EXCEPTION_CLEAN and ob_get_level() > 0 and ob_end_clean();
            $trace = $e->getTrace();
            if(!empty($trace[0])){
                empty($trace[0]['file']) and $trace[0]['file'] = 'Unkown file';
                empty($trace[0]['line']) and $trace[0]['line'] = 'Unkown line';

                $vars = [
                    'message'   => get_class($e).' : '.$e->getMessage(),
                    'position'  => 'File:'.$trace[0]['file'].'   Line:'.$trace[0]['line'],
                    'trace'     => $trace,
                ];
                if(DEBUG_MODE_ON){
                    Utils::loadTemplate('exception',$vars);
                }
            }else{
                Utils::loadTemplate('user_error');
            }
            exit;
        }

        /**
         * handel the error
         * @param int $errno error number
         * @param string $errstr error message
         * @param string $errfile error occurring file
         * @param int $errline error occurring file line number
         * @return void
         */
        public static function handleError($errno,$errstr,$errfile,$errline){
            IS_REQUEST_AJAX and exit($errstr);
            EXCEPTION_CLEAN and ob_get_level() > 0 and ob_end_clean();
            if(!is_string($errstr)) $errstr = serialize($errstr);
            $trace = debug_backtrace();
            $vars = [
                'message'   => "C:{$errno}   S:{$errstr}",
                'position'  => "File:{$errfile}   Line:{$errline}",
                'trace'     => $trace, //be careful
            ];
            if(DEBUG_MODE_ON){
                Utils::loadTemplate('error',$vars);
            }else{
                Utils::loadTemplate('user_error');
            }
            exit;
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
         * @param string $clsnm class name
         * @param string $method method name
         * @return mixed|null
         */
        public static function callStatic($clsnm,$method){
            $callable = "{$clsnm}::{$method}()";
            if(is_callable($callable)){
                try{
                    return $clsnm::$method();
                }catch (\Exception $e){
                    Debugger::trace($e->getMessage());
                }
            }
            return null;
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

    }

    /**
     * Class Response 输出控制类
     * @package PLite\library
     */
    class Response {

        /**
         * 返回的消息类型
         */
        const MESSAGE_TYPE_SUCCESS = 1;
        const MESSAGE_TYPE_WARNING = -1;
        const MESSAGE_TYPE_FAILURE = 0;

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
         * @param bool $clean
         * @return string
         */
        public static function getOutput($clean=true){
            if(ob_get_level()){
                $content = ob_get_contents();
                $clean and ob_end_clean();
                return $content;
            }else{
                return '';
            }
        }

        /**
         * HTTP Protocol defined status codes
         * @param int $code
         * @param string $message
         */
        public static function sendHttpStatus($code,$message='') {
            static $_status = null;
            if(!$message){
                $_status or $_status = array(
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
                $message = isset($_status[$code])?$_status[$code]:'';
            }
            ob_get_level() > 0 and ob_end_clean();
            header("HTTP/1.1 {$code} {$message}");
        }

        /**
         * 向浏览器客户端发送不缓存命令
         * @param bool $clean clean the output before,important and default to true
         * @return void
         */
        public static function sendNocache($clean=true){
            $clean and ob_get_level() > 0 and ob_end_clean();
            header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
            header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
            header( 'Cache-Control: no-store, no-cache, must-revalidate' );
            header( 'Cache-Control: post-check=0, pre-check=0', false );
            header( 'Pragma: no-cache' );
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
        public static function ajaxBack($data, $type = AJAX_JSON, $options = 0){
            ob_get_level() > 0 and ob_end_clean();
            Debugger::closeTrace();
            switch (strtoupper($type)) {
                case AJAX_JSON :// 返回JSON数据格式到客户端 包含状态信息
                    header('Content-Type:application/json; charset=utf-8');
                    exit(json_encode($data, $options));
                case AJAX_XML :// 返回xml格式数据
                    header('Content-Type:text/xml; charset=utf-8');
                    exit(XMLHelper::encode($data));
                case AJAX_STRING:
                    header('Content-Type:text/plain; charset=utf-8');
                    exit($data);
                default:
                    PLiteException::throwing('Invalid output type!');
            }
        }
    }

    /**
     * Class D
     * Making library driver based
     * @package PLite
     */
    trait AutoDrive{
        /**
         * @var array drivers
         */
        private static $_ds= [];

        /**
         * set or get the current driver in use
         * @param string|null $dname driver class name
         * @param mixed $params parameters for driver construct
         * @return array|null it will resturn null if no driver exist
         */
        public static function using($dname=null, $params=null){
            $clsnm = static::class;
            isset(self::$_ds[$clsnm]) or self::$_ds[$clsnm] = [];

            if(null === $dname){
                //get the first element
                $driver = end(self::$_ds[$clsnm]);
                return  false === $driver?null:$driver;
            }

            $drivers = &self::$_ds[$clsnm];
            $key = DRIVER_KEY_WITH_PARAM?md5($dname.serialize($params)):$dname;
            if(isset($drivers[$key])){
                //put in the tail without constructor
                $temp = $drivers[$key];
                unset($drivers[$key]);
                $drivers[$key] = $temp;
            }else{
                //set element which will be in the end
                $drivers[$key] = new $dname($params);
            }
            return $drivers[$key];
        }
    }

    /**
     * Class AutoConfig
     * Make the library config to be configured
     * @package PLite
     */
    trait AutoConfig {
        /**
         * 类的静态配置
         * @var array
         */
        private static $_cs = [];

        /**
         * initialize the class with config
         * :eg the name of this method is much special to make class initialize automaticlly
         * @param null|string $clsnm class-name
         * @return void
         */
        public static function __initializationize($clsnm=null){
            $clsnm or $clsnm = static::class;
            if(!isset(self::$_cs[$clsnm])){
                //get convention
                self::$_cs[$clsnm] = Utils::constant($clsnm,'CONF_CONVENTION',[]);

                //load the outer config
                $conf = Configger::load($clsnm);
                $conf and is_array($conf) and self::$_cs[$clsnm] = Utils::merge(self::$_cs[$clsnm],$conf,true);

                Debugger::trace("Class '{$clsnm}' __initializationized!");
            }
            //auto init
            Utils::callStatic($clsnm,'__init');
        }

        /**
         * 获取该类的配置（经过用户自定义后）
         * @return array
         */
        final protected static function &getConfig(){
            $clsnm = static::class;
            isset(self::$_cs[$clsnm]) or self::$_cs[$clsnm] = [];
            $replacement = null;
            return self::$_cs[$clsnm];
        }

    }

    /**
     * Class I
     * Make single instance or identify-based instance possible
     * @package PLite
     */
    trait AutoInstance {

        protected static $_is = [];

        /**
         * Get instance by identify
         * @param string|int $identify
         * @return object
         */
        public static function getInstance($identify=0){
            $clsnm = static::class;
            isset(self::$_is[$clsnm]) or self::$_is[$clsnm] = [];
            if(!isset(self::$_is[$clsnm][$identify])){
                self::$_is[$clsnm][$identify] = new $clsnm($identify);
            }
            return self::$_is[$clsnm][$identify];
        }

    }

    /**
     * Class Lite
     * @property array $config
     *  'sample class' => [
     *      'PRIOR_INDEX' => 0,//默认驱动ID，类型限定为int或者string
     *      'DRIVER_CLASS_LIST' => [],//驱动类的列表
     *      'DRIVER_CONFIG_LIST' => [],//驱动类列表参数
     *  ]
     * @package PLite
     */
    abstract class Lite {
        use AutoConfig , AutoDrive;

        /**
         * 类实例的驱动
         * @var object
         */
        private static $_drivers = [
            /************************************
            'sample class' => Object
             ************************************/
        ];

        /**
         * it maybe a waste of performance
         * @param string|int|null $identify it will get the default index if set to null
         * @return object
         */
        protected static function driver($identify=null){
            $clsnm = static::class;
            isset(self::$_drivers[$clsnm]) or self::$_drivers[$clsnm] = [];
            $config = null;

            //get default identify
            if(null === $identify) {
                $config = static::getConfig();
                if(isset($config[PRIOR_INDEX])){
                    $identify = $config[PRIOR_INDEX];
                }else{
                    PLiteException::throwing("No driver identify for '{$clsnm}' been specified '{$identify}' !");
                }
            }

            //instance a driver for this identify
            if(!isset(self::$_drivers[$clsnm][$identify])){
                $config or $config = static::getConfig();
                if(isset($config[DRIVER_CLASS_LIST][$identify])){
                    //获取驱动类名称
                    $driver = $config[DRIVER_CLASS_LIST][$identify];
                    $param  = empty($config[DRIVER_CONFIG_LIST][$identify])?null:$config[DRIVER_CONFIG_LIST][$identify];
                    //设置实例驱动
                    self::$_drivers[$clsnm][$identify] = $param? new $driver($param): new $driver();
                }else{
                    PLiteException::throwing("No driver for identify '$identify'!");
                }
            }

            return self::$_drivers[$clsnm][$identify];
        }

        /**
         * Use driver method as its static method
         * @param string $method method name
         * @param array $arguments method arguments
         * @return mixed
         */
        public static function __callStatic($method, $arguments) {
            $driver = self::driver();
            if(!method_exists($driver,$method)){
                $clsnm = static::class;
                PLiteException::throwing("Method '{$method}' do not exist in driver '{$clsnm}'!");
            }
            return call_user_func_array([self::driver(), $method], $arguments);
        }
    }
}