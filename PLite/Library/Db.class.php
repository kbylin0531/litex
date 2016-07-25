<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace PLite\Library;
use PLite\Lite;
use PLite\PLiteException;

/**
 * ThinkPHP 数据库中间层实现类
 */
class Db extends Lite{

    const CONF_NAME = 'think/model';
    const CONF_CONVENTION = [
        /* 数据库设置 */
        'DB_TYPE'               =>  '',     // 数据库类型
        'DB_HOST'               =>  '', // 服务器地址
        'DB_NAME'               =>  '',          // 数据库名
        'DB_USER'               =>  '',      // 用户名
        'DB_PWD'                =>  '',          // 密码
        'DB_PORT'               =>  '',        // 端口
        'DB_PREFIX'             =>  '',    // 数据库表前缀
        'DB_PARAMS'          	=>  array(), // 数据库连接参数
        'DB_DEBUG'  			=>  TRUE, // 数据库调试模式 开启后可以记录SQL日志
        'DB_FIELDS_CACHE'       =>  true,        // 启用字段缓存
        'DB_CHARSET'            =>  'utf8',      // 数据库编码默认采用utf8
        'DB_DEPLOY_TYPE'        =>  0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'DB_RW_SEPARATE'        =>  false,       // 数据库读写是否分离 主从式有效
        'DB_MASTER_NUM'         =>  1, // 读写分离后 主服务器数量
        'DB_SLAVE_NO'           =>  '', // 指定从服务器序号
    ];


    private static $instance   =  array();     //  数据库连接实例
    private static $_instance  =  null;   //  当前数据库连接实例

    /**
     * 取得数据库类实例
     * @static
     * @access public
     * @param mixed $config 连接配置
     * @return Object 返回数据库驱动类
     */
    static public function getInstance($config=array()) {
        self::checkInit(true);
        $md5    =   md5(serialize($config));
        if(!isset(self::$instance[$md5])) {
            // 解析连接参数 支持数组和字符串
            $options    =   self::parseConfig($config);
            // 兼容mysqli
            if('mysqli' == $options['type']) $options['type']   =   'mysql';
            // 如果采用lite方式 仅支持原生SQL 包括query和execute方法
            $class  =   !empty($options['lite'])?  'PLite\\Vendor\\Think\\Db\Lite' :   'PLite\\Vendor\\Think\\Db\\Driver\\'.ucwords(strtolower($options['type']));
            if(class_exists($class)){
                self::$instance[$md5]   =   new $class($options);
            }else{
                // 类没有定义
//                E(L('_NO_DB_DRIVER_').': ' . $class);
                PLiteException::throwing('_NO_DB_DRIVER_:'.$class);
            }
        }
        self::$_instance    =   self::$instance[$md5];
        return self::$_instance;
    }

    /**
     * 数据库连接参数解析
     * @static
     * @access private
     * @param mixed $config
     * @return array
     */
    static private function parseConfig($config){
        if(!empty($config)){
            if(is_string($config)) {
                return self::parseDsn($config);
            }
            $config =   array_change_key_case($config);
            $config = array (
                'type'          =>  $config['db_type'],
                'username'      =>  $config['db_user'],
                'password'      =>  $config['db_pwd'],
                'hostname'      =>  $config['db_host'],
                'hostport'      =>  $config['db_port'],
                'database'      =>  $config['db_name'],
                'dsn'           =>  isset($config['db_dsn'])?$config['db_dsn']:null,
                'params'        =>  isset($config['db_params'])?$config['db_params']:null,
                'charset'       =>  isset($config['db_charset'])?$config['db_charset']:'utf8',
                'deploy'        =>  isset($config['db_deploy_type'])?$config['db_deploy_type']:0,
                'rw_separate'   =>  isset($config['db_rw_separate'])?$config['db_rw_separate']:false,
                'master_num'    =>  isset($config['db_master_num'])?$config['db_master_num']:1,
                'slave_no'      =>  isset($config['db_slave_no'])?$config['db_slave_no']:'',
                'debug'         =>  isset($config['db_debug'])?$config['db_debug']:DEBUG_MODE_ON,/* APP_DEBUG */
                'lite'          =>  isset($config['db_lite'])?$config['db_lite']:false,
            );
        } else {
            $conf = self::getConfig();
            $config = array (
                'type'          =>  $conf['DB_TYPE'],
                'username'      =>  $conf['DB_USER'],
                'password'      =>  $conf['DB_PWD'],
                'hostname'      =>  $conf['DB_HOST'],
                'hostport'      =>  $conf['DB_PORT'],
                'database'      =>  $conf['DB_NAME'],
                'dsn'           =>  $conf['DB_DSN'],
                'params'        =>  $conf['DB_PARAMS'],
                'charset'       =>  $conf['DB_CHARSET'],
                'deploy'        =>  $conf['DB_DEPLOY_TYPE'],
                'rw_separate'   =>  $conf['DB_RW_SEPARATE'],
                'master_num'    =>  $conf['DB_MASTER_NUM'],
                'slave_no'      =>  $conf['DB_SLAVE_NO'],
                'debug'         =>  $conf['DB_DEBUG'],
                'lite'          =>  $conf['DB_LITE'],
            );
        }
        return $config;
    }



    /**
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/DbName?param1=val1&param2=val2#utf8
     * @static
     * @access private
     * @param string $dsnStr
     * @return array
     */
    static private function parseDsn($dsnStr) {
        if( empty($dsnStr) ){return false;}
        $info = parse_url($dsnStr);
        if(!$info) {
            return false;
        }
        $dsn = array(
            'type'      =>  $info['scheme'],
            'username'  =>  isset($info['user']) ? $info['user'] : '',
            'password'  =>  isset($info['pass']) ? $info['pass'] : '',
            'hostname'  =>  isset($info['host']) ? $info['host'] : '',
            'hostport'  =>  isset($info['port']) ? $info['port'] : '',
            'database'  =>  isset($info['path']) ? substr($info['path'],1) : '',
            'charset'   =>  isset($info['fragment'])?$info['fragment']:'utf8',
        );
        
        if(isset($info['query'])) {
            parse_str($info['query'],$dsn['params']);
        }else{
            $dsn['params']  =   array();
        }
        return $dsn;
     }

    // 调用驱动类的方法
    static public function __callStatic($method, $params){
        return call_user_func_array(array(self::$_instance, $method), $params);
    }
}
