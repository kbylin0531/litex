<?php
/**
 * Created by linzhv@outlook.com.
 * User: linzh
 * Date: 2016/6/21
 * Time: 21:42
 */
namespace PLite\Library;
use PLite\AutoConfig;
use PLite\PLiteException;

/**
 * Class Cookie Cookie操作类
 *
 * 修改自Thinkphp5RC2
 *
 * @package PLite\Library
 */
class Cookie {
    use AutoConfig;
    const CONF_NAME = 'cookie';
    const CONF_CONVENTION = [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ];

    public static function __init(){
        self::getConfig('httponly',false) and ini_set('session.cookie_httponly', 1);
    }

    /**
     * 判断Cookie数据
     * @param string        $name cookie名称
     * @param string|null   $prefix cookie前缀
     * @return bool
     */
    public static function has($name, $prefix = null) {
        $prefix = !is_null($prefix) ? $prefix : self::getConfig('prefix');
        $name   = $prefix . $name;
        return isset($_COOKIE[$name]);
    }

    /**
     * 设置或者获取cookie作用域（前缀）
     * @param string $prefix
     * @return string
     */
    public static function prefix($prefix = null) {
        if(null === $prefix){
            return self::getConfig('prefix');
        }else{
            //修改默认的配置
            return self::setConfig('prefix',$prefix)?$prefix:PLiteException::throwing('update failed!');
        }
    }

    /**
     * Cookie 设置、获取、删除
     * @param string $name  cookie名称
     * @param mixed  $value cookie值
     * @param mixed  $option 可选参数 可能会是 null|integer|string
     * @return mixed
     */
    public static function set($name, $value = '', $option = null){
        // 参数设置(会覆盖黙认设置)
        $config  = &self::getConfig();
        if (isset($option)) {
            if (is_numeric($option)) {
                $option = ['expire' => $option];
            } elseif (is_string($option)) {
                parse_str($option, $option);
            }
            $config = array_merge($config, array_change_key_case($option));
        }
        $name = $config['prefix'] . $name;
        // 设置cookie
        if (is_array($value)) {
            array_walk($value,function (&$val){
                empty($val) or $val = urlencode($val);
            });
            $value = 'think:' . json_encode($value);
        }
        $expire = !empty($config['expire']) ? $_SERVER['REQUEST_TIME'] + intval($config['expire']) : 0;
        if ($config['setcookie']) {
            setcookie($name, $value, $expire, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
        }
        $_COOKIE[$name] = $value;
    }

    /**
     * Cookie获取
     * @param string $name cookie名称
     * @param string|null $prefix cookie前缀
     * @return mixed
     */
    public static function get($name, $prefix = null) {
        if(!isset($prefix)){
            $config = self::getConfig();
            $prefix = isset($config['prefix'])?$config['prefix']:'';
        }
        $name   = $prefix . $name;
        if (isset($_COOKIE[$name])) {
            $value = $_COOKIE[$name];
            if (0 === strpos($value, 'think:')) {
                $value = substr($value, 6);
                $value = json_decode($value, true);
                array_walk($value,function (&$val){
                    empty($val) or $val = urldecode($val);
                });
            }
            return $value;
        } else {
            return null;
        }
    }

    /**
     * Cookie删除
     * @param string $name cookie名称
     * @param string|null $prefix cookie前缀
     * @return mixed
     */
    public static function delete($name, $prefix = null){
        $config = self::getConfig();
        $prefix = isset($prefix) ? $prefix : $config['prefix'];
        $name   = $prefix . $name;
        if ($config['setcookie']) {
            setcookie($name, '', REQUEST_TIME - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
        }
        // 删除指定cookie
        unset($_COOKIE[$name]);
    }

    /**
     * Cookie清空
     * @param string|null $prefix cookie前缀
     * @return mixed
     */
    public static function clear($prefix = null) {
        // 清除指定前缀的所有cookie
        if($_COOKIE){
            $config = self::getConfig();
            $prefix = isset($prefix) ? $prefix : $config['prefix'];
            if ($prefix) {
                // 如果前缀为空字符串将不作处理直接返回
                foreach ($_COOKIE as $key => $val) {
                    if (0 === strpos($key, $prefix)) {
                        if ($config['setcookie']) {
                            setcookie($key, '', $_SERVER['REQUEST_TIME'] - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
                        }
                        unset($_COOKIE[$key]);
                    }
                }
            }
        }
    }

}