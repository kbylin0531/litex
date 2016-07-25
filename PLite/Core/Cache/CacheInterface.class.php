<?php

/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 9:49 PM
 */
namespace PLite\Core\Cache;
/**
 * Interface CacheInterface 缓存驱动接口
 * @package Kbylin\System\Library\Cache
 */
interface CacheInterface{
    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name);

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param int $expire  有效时间，0为永久（以秒计时）
     * @return boolean
     */
    public function set($name, $value, $expire = 0);

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function delete($name);

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clean();
}