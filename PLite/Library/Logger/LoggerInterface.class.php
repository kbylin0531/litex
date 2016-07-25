<?php
/**
 * Created by linzhv@outlook.com.
 * User: linzh
 * Date: 2016/6/22
 * Time: 11:25
 */
namespace PLite\Library\Logger;
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