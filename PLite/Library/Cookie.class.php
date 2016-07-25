<?php
/**
 * Created by linzhv@outlook.com.
 * User: linzh
 * Date: 2016/6/21
 * Time: 21:42
 */
namespace PLite\Library;
use PLite\Lite;

/**
 * Class Cookie Cookie操作类
 *
 * 修改自Thinkphp5RC2
 *
 * @package PLite\Library
 */
class Cookie extends Lite{
    const CONF_NAME = 'cookie';
    const CONF_CONVENTION = [
        'PREFIX'    => '',// COOKIE 名称前缀
        'EXPIRE'    => 0,// COOKIE 保存时间
        'PATH'      => '/',// COOKIE 保存路径
        'DOMAIN'    => '',// COOKIE 有效域名
        'SECURE'    => false,//  COOKIE 启用安全传输
        'HTTPONLY'  => '',// HTTPONLY设置
        'SETCOOKIE' => true,// 是否使用 SETCOOKIE
    ];

    private static $config = [];

    public static function _init_class_($clsnm = null, $conf = null){
        parent::_init_class_($clsnm, $conf);
        self::$config = self::getConfig();
    }

    /**
     * 设置或者获取cookie作用域（前缀）
     * @param string $prefix
     * @return string
     */
    public static function prefix($prefix = null) {
        if(null === $prefix){
            return self::$config['PREFIX'];
        }else{
            //修改默认的配置
            self::setConfig('PREFIX',$prefix);
            return $prefix;
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
        if (!is_null($option)) {
            if (is_numeric($option)) {
                $option = ['EXPIRE' => $option];
            } elseif (is_string($option)) {
                parse_str($option, $option);
            }
            self::$config = array_merge(self::$config, array_change_key_case($option));
        }
        $name = self::$config['PREFIX'] . $name;
        // 设置cookie
        if (is_array($value)) {
            array_walk_recursive($value, 'json_format_protect', 'encode');
            $value = 'think:' . json_encode($value);
        }
        $expire = !empty(self::$config['EXPIRE']) ? time() + intval(self::$config['EXPIRE']) : 0;
        if (self::$config['SETCOOKIE']) {
            setcookie($name, $value, $expire, self::$config['PATH'], self::$config['DOMAIN'], self::$config['SECURE'], self::$config['HTTPONLY']);
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
        $prefix = !is_null($prefix) ? $prefix : self::$config['PREFIX'];
        $name   = $prefix . $name;
        if (isset($_COOKIE[$name])) {
            $value = $_COOKIE[$name];
            if (0 === strpos($value, 'think:')) {
                $value = substr($value, 6);
                $value = json_decode($value, true);
                array_walk_recursive($value, 'json_format_protect', 'decode');
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
        $prefix = !is_null($prefix) ? $prefix : self::$config['PREFIX'];
        $name   = $prefix . $name;
        if (self::$config['SETCOOKIE']) {
            setcookie($name, '', time() - 3600, self::$config['PATH'], self::$config['DOMAIN'], self::$config['SECURE'], self::$config['HTTPONLY']);
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
        if (empty($_COOKIE)) {
            return;
        }

        // 要删除的cookie前缀，不指定则删除config设置的指定前缀
        $prefix = !is_null($prefix) ? $prefix : self::$config['PREFIX'];
        if ($prefix) {
            // 如果前缀为空字符串将不作处理直接返回
            foreach ($_COOKIE as $key => $val) {
                if (0 === strpos($key, $prefix)) {
                    if (self::$config['SETCOOKIE']) {
                        setcookie($key, '', time() - 3600, self::$config['PATH'], self::$config['DOMAIN'], self::$config['SECURE'], self::$config['HTTPONLY']);
                    }
                    unset($_COOKIE[$key]);
                }
            }
        }
        return;
    }

}