<?php

/**
 * Created by linzhv@outlook.com.
 * User: linzh
 * Date: 2016/6/21
 * Time: 18:28
 */
namespace PLite\Core\Configger;

/**
 * Interface ConfigInterface 配置处理接口
 * 继承该借口的类将使用其实现的方法读取和写入配置
 * @package Kbylin\System\Core\Config
 */
interface ConfiggerInterface {

    /**
     * 读取单个的配置
     * @param string $item 配置文件名称
     * @return array|null 返回配置数组，不存在指定配置时候返回null
     */
    public function read($item);

    /**
     * 写入单个持久化的配置
     * @param string $item 配置文件名称
     * @param array $config 写入的配置信息
     * @param bool $cover 是否覆盖原先的配置,默认为false
     * @return bool 返回false表示写入失败
     */
    public function write($item,array $config,$cover=false);

}