<?php
/**
 * Created by linzhv@outlook.com.
 * User: linzh
 * Date: 2016/6/22
 * Time: 11:24
 */
namespace PLite\Library;
use PLite\Lite;
use PLite\PLiteException as Exception;
/**
 * Interface LogInterface 日志接口
 * Interface LoggerInterface
 */
interface LoggerInterface {

    /**
     * 写入日志信息
     * 如果日志文件已经存在，则追加到文件末尾
     * @param string $key 日志文件位置或者标识符（一个日志文件或者日志组是唯一的）
     * @param string|array $content 日志内容
     * @return bool 写入是否成功
     */
    public function write($key, $content);

    /**
     * 读取日志文件内容
     * 如果设置了参数二，则参数一将被认定为文件名
     * @param string $key 日志文件位置或者标识符（一个日志文件或者日志组是唯一的）
     * @return string|null 返回日志内容,指定的日志不存在时返回null
     */
    public function read($key);

}
/**
 * Class Log 日志管理类
 * @package Kbylin\System\Core
 */
class Logger extends Lite{

    // 日志级别 从上到下，由低到高
    const EMERG     = 'EMERG';  // 严重错误: 导致系统崩溃无法使用
    const ALERT     = 'ALERT';  // 警戒性错误: 必须被立即修改的错误
    const CRIT      = 'CRIT';  // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const ERR       = 'ERR';  // 一般错误: 一般性错误
    const WARN      = 'WARN';  // 警告性错误: 需要发出警告的错误
    const NOTICE    = 'NOTIC';  // 通知: 程序可以运行但是还不够完美的错误
    const INFO      = 'INFO';  // 信息: 程序输出信息
    const DEBUG     = 'DEBUG';  // 调试: 调试信息
    const SQL       = 'SQL';  // SQL：SQL语句 注意只在调试模式开启时有效

    // 日志信息
    private static $log       =  [];

    const CONF_NAME = 'log';
    const CONF_CONVENTION = [
        'PRIOR_INDEX' => 0,//默认的驱动标识符，类型为int或者string
        'DRIVER_CLASS_LIST' => [
            'PLite\\Library\\Logger\\File',
        ],//驱动类列表

        'LOG_RATE'      => Logger::LOGRATE_DAY,
        //Think\Log
        'LOG_TIME_FORMAT'   =>  ' c ',
        'LOG_FILE_SIZE'     =>  2097152,
        'LOG_PATH'  => PATH_RUNTIME.'/Log',
        // 允许记录的日志级别
        'LOG_LEVEL'         =>  true,//'EMERG,ALERT,CRIT,ERR,WARN,NOTIC,INFO,DEBUG,SQL',
    ];

    /**
     * 系统预设的级别，用户也可以自定义
     */
    const LOG_LEVEL_DEBUG = 'Debug';//错误和调试
    const LOG_LEVEL_TRACE = 'Trace';//记录日常操作的数据信息，以便数据丢失后寻回

    /**
     * 日志频率
     * LOGRATE_DAY  每天一个文件的日志频率
     * LOGRATE_HOUR 每小时一个文件的日志频率，适用于较频繁的访问
     */
    const LOGRATE_HOUR = 0;
    const LOGRATE_DAY = 1;

    /**
     * 获取日志文件的UID（Unique Identifier）
     * @param string $level 日志界别
     * @param string $datetime 日志时间标识符，如“2016-03-17/09”日期和小时之间用'/'划分
     * @return string 返回UID
     * @throws Exception
     */
    protected static function fetchLogUID($level=self::LOG_LEVEL_DEBUG,$datetime=null){
        if(isset($datetime)){
            $path = PATH_RUNTIME."/Log/{$level}/{$datetime}.log";
        }else{
            $date = date('Y-m-d');
            $rate = self::getConfig('LOG_RATE');
            $rate or $rate = self::LOGRATE_DAY;
            switch($rate){
                case self::LOGRATE_DAY:
                    $path = PATH_RUNTIME."/Log/{$level}/{$date}.log";
                    break;
                case self::LOGRATE_HOUR:
                    $hour = date('H');
                    $path = PATH_RUNTIME."/Log/{$level}/{$date}/{$hour}.log";
                    break;
                default:
                    return Exception::throwing("日志频率未定义：'{$rate}'");
            }
        }
        return $path;
    }

    /**
     * 写入日志信息
     * 如果日志文件已经存在，则追加到文件末尾
     * @param string|array $content 日志内容
     * @param string $level 日志级别
     * @return string 写入内容返回
     * @Exception FileWriteFailedException
     */
    public static function write($content,$level=self::LOG_LEVEL_DEBUG){
        return self::driver()->write(self::fetchLogUID($level),$content);
    }

    /**
     * 读取日志文件内容
     * 如果设置了参数二，则参数一将被认定为文件名
     * @param string $datetime 日志文件生成的大致时间，记录频率为天时为yyyy-mm-dd,日志频率为时的时候为yyyy-mmmm-dd:hh
     * @param null|string $level 日志级别
     * @return string|array 如果按小时写入，则返回数组
     */
    public static function read($datetime, $level=self::LOG_LEVEL_DEBUG){
        return self::driver()->read(self::fetchLogUID($level,$datetime));
    }

    /**
     * 写入DEBUG信息到日志中
     * @param ...
     * @return void
     */
    public static function debug(){
        $content = '';
        $params = func_get_args();
        foreach($params as $val){
            $content .= var_export($val,true);
        }
        self::write($content,self::LOG_LEVEL_DEBUG);
    }

    /**
     * 记录日志 并且会过滤未经设置的级别
     * @static
     * @access public
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param boolean $record  是否强制记录
     * @return $this
     */
    public static function record($message,$level=self::ERR,$record=false) {
        if($record ){
            self::$log[] =   "{$level}: {$message}\r\n";
        }else{
            $allowlevel = self::getConfig('LOG_LEVEL');
            if(true === $allowlevel or false !== strpos($allowlevel,$level)) {
                self::$log[] =   "{$level}: {$message}\r\n";
            }
        }
    }

    /**
     * 日志保存
     * @static
     * @access public
     * @param string $destination  写入目标
     * @return void
     */
    public static function save($destination='') {
        if(empty(self::$log)) return ;

        $config = self::getConfig();

        if(empty($destination)){
            $destination = $config['LOG_PATH'].date('y_m_d').'.log';
        }
        $message    =   implode('',self::$log);
        self::_write($message,$destination);
        // 保存后清空日志缓存
        self::$log = array();
    }

    /**
     * 日志写入接口
     * @access public
     * @param string $log 日志信息
     * @param string $destination  写入目标
     * @return void
     */
    public static function _write($log,$destination='') {
        $config = self::getConfig();
        $now = date($config['LOG_TIME_FORMAT']);
        if(empty($destination)){
            $destination = $config['LOG_PATH'].date('y_m_d').'.log';
        }
        // 自动创建日志目录
        $log_dir = dirname($destination);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if(is_file($destination) && floor($config['LOG_FILE_SIZE']) <= filesize($destination) ){
            rename($destination,dirname($destination).'/'.time().'-'.basename($destination));
        }
        error_log("[{$now}] ".$_SERVER['REMOTE_ADDR'].' '.$_SERVER['REQUEST_URI']."\r\n{$log}\r\n", 3,$destination);
    }

}