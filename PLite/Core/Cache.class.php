<?php

/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 7:03 PM
 */
namespace PLite\Core;
use PLite\Lite;

class Cache extends Lite{

    const CONF_NAME = 'cache';
    const CONF_CONVENTION = [
        'PRIOR_INDEX' => 0,
        'DRIVER_CLASS_LIST' => [
            'PLite\\Core\\Cacher\\File',
            'PLite\\Core\\Cacher\\Memcache',
        ],
        'DRIVER_CONFIG_LIST' => [
            [
                //from thinkphp ,match case
                'expire'        => 0,
                'cache_subdir'  => false,
                'path_level'    => 1,
                'prefix'        => '',
                'length'        => 0,
                'path'          => PATH_RUNTIME.'Cache/File/',
                'data_compress' => false,
            ],
            [
                'host'      => 'localhost',
                'port'      => 11211,
                'expire'    => 0,
                'prefix'    => '',
                'timeout'   => 1000, // 超时时间（单位：毫秒）
                'persistent'=> true,
                'length'    => 0,
            ],
        ],
    ];


    /**
     * 读取次数
     * @var int
     */
    private static $readTimes   = 0;
    /**
     * 写入次数
     * @var int
     */
    private static $writeTimes  = 0;
    /**
     * to declare the driver type
     * @var CacherInterface
     */
    protected $driver = null;

    /**
     * 获取读取缓存次数
     * @return int
     */
    public function getReadTimes(){
        return self::$readTimes;
    }

    /**
     * 获取写入缓存的速度
     * @return int
     */
    public function getWriteTimes(){
        return self::$writeTimes;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $replace 值不存在时的替代
     * @return mixed
     */
    public function get($name,$replace=null){
        ++ self::$readTimes;
        $result = $this->driver->get($name);
        return $result === null ? $replace : $result;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param int $expire  有效时间 0为永久
     * @return bool
     */
    public function set($name, $value, $expire = null){
        ++ self::$writeTimes;
        return $this->driver->set($name, $value, $expire);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function delete($name){
        ++ self::$writeTimes;
        return $this->driver->delete($name);
    }

    /**
     * 清除全部缓存
     * @access public
     * @return boolean
     */
    public function clean(){
        ++ self::$writeTimes;
        return $this->driver->clean();
    }

    /**
     * 加载静态输出脚本
     */
    public static function loadStatic(){}
    public static function saveStatic(){}
    public static function hasStatic(){}
}