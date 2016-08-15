<?php
namespace PLite\Library;
use PLite\PLiteException;


/**
 * Class Monitor 资源监视器
 * 修改自Thinkphp框架Hook类
 * @package PLite\Library
 */
class Hook {

    /**
     * 标签集合
     * key可能是某种有意义的方法，也可能是一个标识符
     * value可能是闭包函数或者类名称
     * 如果是value类名称，则key可能是其调用的方法的名称（此时会检查这个类中是否存在这个方法），也可能是一个标识符
     * @var array
     */
    private static $tags = [];

    /**
     * 动态注册行为
     * @param string $tag 标签名称
     * @param mixed|array $behavior 行为名称,为array类型时将进行批量注册
     * @return void
     */
    public static function register($tag, $behavior) {
        if (!isset(self::$tags[$tag]))  self::$tags[$tag] = [];

        if (is_array($behavior)) {
            self::$tags[$tag] = array_merge(self::$tags[$tag], $behavior);
        } else {
            self::$tags[$tag][] = $behavior;
        }
    }

    /**
     * 监听标签的行为
     * @param string $tag 标签名称
     * @param mixed $params 传入回调闭包或者对象方法的参数
     * @return void
     */
    public static function listen($tag, &$params = null) {
        if (isset(self::$tags[$tag])) {
            foreach(self::$tags[$tag] as $name){
                if (false === self::exec($name, $tag, $params)) {
                    // 如果返回false 则中断行为执行
                    return;
                }
            }
        }
    }

    /**
     * 执行某个行为
     * @param string $callableorclass 闭包或者类名称
     * @param string $tag 方法名（标签名）
     * @param Mixed $params 方法的参数
     * @return mixed
     * @throws PLiteException
     */
    private static function exec($callableorclass, $tag = '', &$params = null) {
        if ($callableorclass instanceof \Closure) {
            //如果是闭包，则直接执行闭包函数
            return $callableorclass($params);
        }elseif(is_string($callableorclass)){
            $obj = new $callableorclass();
            return ('' !== $tag && is_callable([$obj, $tag])) ?
                $obj->$tag($params) : /*如果目标对象中存在这个$tag，则是callable的*/
                call_user_func([$obj,'run'],$params)/*$obj->run($params)*/;
        }else{
            PLiteException::throwing('Wrong tag:'.var_export($callableorclass,true));return false;
        }
    }
}