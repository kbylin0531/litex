<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 9:51 PM
 */

namespace PLite\Core\Cache;
use PLite\Core\CacheInterface;
use PLite\PLiteException as Exception;

/**
 * Class Memcache
 *
 *
 * 注意，访问了错误的服务器而导致无法连接会将报连接池出错
 * 如：MemcachePool::get()
 *
 * 支持memcache集群，详细请见章节
 *
 *
 * @package Kbylin\System\Core\Cache
 */
class Memcache implements CacheInterface{
    /**
     * @var \Memcache
     */
    protected $handler = null;

    /**
     * 连接属性
     * @var array
     */
    protected $options = [
        'host'      => '127.0.0.1',
        'port'      => 11211,
        'expire'    => 0, // 0表示永不过期
        'prefix'    => '',
        'timeout'   => 1000, // 连接超时时间，默认1秒（单位：毫秒） 注意，如果设置为0将无法建立任何连接，并且会出现MemcachePool::get()的错误
        'persistent'=> true,
        'length'    => 0,
    ];

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     * @throws Exception
     */
    public function __construct(array $options = []) {

        if (!extension_loaded('memcache')) {
            Exception::throwing('Memcache unusable!');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        $this->handler = new \Memcached();
        // 支持集群
        if(false !== strpos($this->options['host'],',')){
            $hosts = explode(',', $this->options['host']);
        }else{
            $hosts = [$this->options['host']];
        }
        if(false !== strpos($this->options['port'],',')){
            $ports = explode(',', $this->options['port']);
        }else{
            $ports = [$this->options['port']];
        }
        if (empty($ports[0]))  $ports[0] = 11211;

        // 建立连接
        foreach ($hosts as $i => $host) {
            $port = isset($ports[$i]) ? $ports[$i] : $ports[0];
            //添加成功时并不会测试是否可用
            $result = $this->handler->addserver($host, $port, $this->options['persistent'], 1 , $this->options['timeout']);
            if(!$result){
                Exception::throwing('Failed to connect memcache server!');
            }
        }
    }

    /**
     * 测试链接是否可用
     *
     * 如果不可用，请检查IP和端口是否正确，并检查防火墙是否限制了该端口的访问
     * @return bool
     */
    public function available(){
        return $this->handler->set('______test_avalable_______', '');
    }

    /**
     * 读取缓存
     *
     * Memcache::get()
     * Returns the string associated with the <b>key</b> or
     * an array of found key-value pairs
     * Returns <b>FALSE</b> on failure, <b>key</b> is not found or
     * <b>key</b> is an empty
     *
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $replacement
     * @return string|array |null 返回false时候表示出现了错误
     */
    public function get($name,$replacement=null)
    {
        $val = $this->handler->get($this->options['prefix'] . $name);
        return false === $val?$replacement:$val;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return bool
     */
    public function set($name, $value, $expire = null) {
        if (NULL === $expire)  $expire = $this->options['expire'];
        $name = $this->options['prefix'] . $name;

        $result = $this->handler->set($name, $value, 0, $expire);

        if ($result) {//参数三 MEMCACHE_COMPRESSED
            if ($this->options['length'] > 0) {
                // 记录缓存队列
                $queue = $this->handler->get('__info__');
                if (!$queue) {
                    $queue = [];
                }
                if (false === array_search($name, $queue)) {
                    array_push($queue, $name);
                }
                if (count($queue) > $this->options['length']) {
                    // 出列
                    $key = array_shift($queue);
                    // 删除缓存
                    $this->handler->delete($key);
                }
                $this->handler->set('__info__', $queue);
            }
            return true;
        }
        return false;
    }

    /**
     * 删除缓存
     * @param string  $name 缓存变量名
     * @param int $timeout
     * @return bool
     */
    public function delete($name, $timeout = 0){
        return $this->handler->delete($this->options['prefix'].$name, $timeout);
    }

    /**
     * 清除缓存
     * Flush all existing items at the server
     * @access public
     * @return bool
     */
    public function clean(){
        return $this->handler->flush();
    }

}