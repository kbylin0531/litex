<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 6:06 PM
 */
namespace {
    use PLite\AutoLoader;
    use PLite\Core\Dispatcher;
    use PLite\Core\Router;
    use PLite\Core\URL;
    use PLite\Debugger;
    use PLite\Library\Response;
    use PLite\PLiteException;

//---------------------------------- mode constant -------------------------------------//
    defined('DEBUG_MODE_ON') or define('DEBUG_MODE_ON', true);
    defined('PAGE_TRACE_ON') or define('PAGE_TRACE_ON', true);//在处理微信签名检查时会发生以外的错误
    defined('LITE_ON')       or define('LITE_ON', true);
    defined('INSPECT_ON')    or define('INSPECT_ON',false);

//---------------------------------- environment constant -------------------------------------//
    define('REQUEST_MICROTIME',microtime(true));
    DEBUG_MODE_ON and $GLOBALS['_status_begin'] = [
        REQUEST_MICROTIME,
        memory_get_usage(),
    ];
    define('IS_CLIENT',PHP_SAPI === 'cli');
    define('IS_WINDOWS',false !== stripos(PHP_OS, 'WIN'));
    define('IS_REQUEST_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ));
    define('IS_METHOD_POST',$_SERVER['REQUEST_METHOD'] === 'POST');//“GET”, “HEAD”，“POST”，“PUT”

    define('REQUEST_TIME',$_SERVER['REQUEST_TIME']);
    define('HTTP_PREFIX', (isset ($_SERVER ['HTTPS']) and $_SERVER ['HTTPS'] === 'on') ? 'https://' : 'http://' );
    define('__PUBLIC__',dirname($_SERVER['SCRIPT_NAME']));

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
        ];

        /**
         * 初始化应用程序
         * @param array|null $config
         * @return void
         */
        public static function init(array $config=null){
            DEBUG_MODE_ON and Debugger::import('app_begin',$GLOBALS['_status_begin']);
            Debugger::status('app_init_begin');
            $config and self::$_config = array_merge(self::$_config,$config);

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

                if(LITE_ON){ //rebuild if lite file not exist
                    Debugger::status('create_lite_begin');
//                Storage::write($this->_litepath,LiteBuilder::compileInBatch(self::$_classes));
                    Debugger::status('create_lite_begin');
                }
                Response::flushOutput();
                Debugger::status('script_shutdown');
            });
//            PLiteException::throwing(['cuowu fasengfdfdfdf',23232]);
//            trigger_error('fasdsdsdsdsd');
            Debugger::status('app_init_done');
        }

        /**
         * start application
         * @param array|null $config
         */
        public static function start(array $config=null){
            self::init($config);

            //parse uri
            $result = self::$_config['ROUTE_ON']?Router::parse():null;
            $result or $result = URL::parse();
            //URL中解析结果合并到$_GET中，$_GET的其他参数不能和之前的一样，否则会被解析结果覆盖,注意到$_GET和$_REQUEST并不同步，当动态添加元素到$_GET中后，$_REQUEST中不会自动添加
            empty($result['p']) or $_GET = array_merge($_GET,$result['p']);

            Debugger::status('dispatch_begin');

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
    }
}

namespace PLite {

    use PLite\Library\Response;
    use PLite\Util\SEK;

    /**
     * Class Debugger
     * @package PLite
     */
    class Debugger {
        /**
         * @var bool
         */
        private static $_allowTrace = true;
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
         * 开启Trace
         * @return void
         */
        final public static function openTrace(){
            self::$_allowTrace = true;
        }

        /**
         * 关闭trace
         * @return void
         */
        final public static function closeTrace(){
            self::$_allowTrace = false;
        }

        /**
         * 显示trace页面
         * @return true 实际返回void
         */
        public static function showTrace(){
            if(!self::$_allowTrace) return true;//如果被禁止了trace页面,则不显示该页面
            //吞吐率  1秒/单次执行时间
            if(count(self::$_status) > 1){
                $last  = end(self::$_status);
                $first = reset(self::$_status);            //注意先end后reset
                $stat = [
                    1000*round($last[0] - $first[0], 6),
                    number_format(($last[1] - $first[1]), 6)
                ];
            }else{
                $stat = [0,0];
            }
            $reqs = empty($stat[0])?'Unknown':1000*number_format(1/$stat[0],8).' req/s';

            //包含的文件数组
            $files  =  get_included_files();
            $info   =   [];
            foreach ($files as $key=>$file){
                $info[] = $file.' ( '.number_format(filesize($file)/1024,2).' KB )';
            }

            //运行时间与内存开销
            $fkey = null;
            $cmprst = ['Total' => "{$stat[0]}ms",];
            foreach(self::$_status as $key=>$val){
                if(null === $fkey){
                    $fkey = $key;
                    continue;
                }
                $cmprst["[$fkey --> $key] "] =
                    number_format(1000 * floatval(self::$_status[$key][0] - self::$_status[$fkey][0]),6).'ms&nbsp;&nbsp;'.
                    number_format((floatval(self::$_status[$key][1] - self::$_status[$fkey][1])/1024),2).' KB';
                $fkey = $key;
            }
            $vars = [
                'trace' => [
                    'General'       => [
                        'Request'   => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']).' '.$_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_METHOD'],
                        'Time'      => "{$stat[0]}ms",
                        'QPS'       => $reqs,//吞吐率
                        'SessionID' => session_id(),
                        'Cookie'    => var_export($_COOKIE,true),
                        'Obcache-Size'  => number_format((ob_get_length()/1024),2).' KB (Unexpect Trace Page!)',//不包括trace
                    ],
                    'Trace'         => self::$_traces,
                    'Files'         => array_merge(['Total'=>count($info)],$info),
                    'Status'        => $cmprst,
                    'GET'           => $_GET,
                    'POST'          => $_POST,
                    'SERVER'        => $_SERVER,
                    'FILES'         => $_FILES,
                    'ENV'           => $_ENV,
                    'SESSION'       => isset($_SESSION)?$_SESSION:['SESSION state disabled'],//session_start()之后$_SESSION数组才会被创建
                    'IP'            => [
                        '$_SERVER["HTTP_X_FORWARDED_FOR"]'  =>  isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:'NULL',
                        '$_SERVER["HTTP_CLIENT_IP"]'  =>  isset($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:'NULL',
                        '$_SERVER["REMOTE_ADDR"]'  =>  $_SERVER['REMOTE_ADDR'],
                        'getenv("HTTP_X_FORWARDED_FOR")'  =>  getenv('HTTP_X_FORWARDED_FOR'),
                        'getenv("HTTP_CLIENT_IP")'  =>  getenv('HTTP_CLIENT_IP'),
                        'getenv("REMOTE_ADDR")'  =>  getenv('REMOTE_ADDR'),
                    ],
                ],
            ];
            \PLite::loadTemplate('trace',$vars,false);//参数三表示不清空之前的缓存区
            return true;
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
//                    dumpout($clsnm,$path,is_file($path));
                    IS_WINDOWS and $path = str_replace('/', '\\', realpath($path));
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
            if(IS_REQUEST_AJAX){
                if($e instanceof \Exception){
                    Response::failed($e->getMessage());
                }else{
                    Response::failed(var_export($e,true));
                }
            }
            ob_get_level() > 0 and ob_end_clean();
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
                    \PLite::loadTemplate('exception',$vars);
                }else{
                }
            }else{
                \PLite::loadTemplate('user_error');
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
        final public static function handleError($errno,$errstr,$errfile,$errline){
            IS_REQUEST_AJAX and Response::failed([$errno,$errstr,$errfile,$errline]);
            ob_get_level() > 0 and ob_end_clean();
            if(!is_string($errstr)) $errstr = serialize($errstr);
            $trace = debug_backtrace();
            $vars = [
                'message'   => "C:{$errno}   S:{$errstr}",
                'position'  => "File:{$errfile}   Line:{$errline}",
                'trace'     => $trace, //be careful
            ];
            if(DEBUG_MODE_ON){
                \PLite::loadTemplate('error',$vars);
            }else{
                \PLite::loadTemplate('user_error');
            }
            exit;
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
         * @param string|array|null $conf config name of config array.if set to null, it will refer to class constant 'CONF_NAME'
         * @return void
         */
        public static function _init_class_($clsnm=null,$conf=null){
            $clsnm or $clsnm = static::class;
            if(!isset(self::$_configs[$clsnm])){
                //get convention
                self::$_configs[$clsnm] = SEK::classConstant($clsnm,'CONF_CONVENTION',[]);

                //load the outer config
                if(null === $conf) $conf = SEK::classConstant($clsnm,'CONF_NAME',null);//outer constant name
                if(is_string($conf)) $conf = Lite::load($conf);
//            \PLite\dumpout($conf,self::$_configs[$clsnm]);
                is_array($conf) and SEK::merge(self::$_configs[$clsnm],$conf,true);
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
                        $temp and SEK::merge($result,$temp);
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

    class ConfigHandler {

        public function get(){

        }

        public function cache(){

        }

    }

}