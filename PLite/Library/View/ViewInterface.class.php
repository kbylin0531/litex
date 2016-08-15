<?php
/**
 * Created by linzhv@outlook.com.
 * User: linzh
 * Date: 2016/6/22
 * Time: 12:23
 */

namespace PLite\Library\View;

/**
 * Interface ViewInterface
 * @package PLite\Library
 */
interface ViewInterface {

    /**
     * 让模板引擎知道调用的相关上下文环境
     * @param array $context 上下文环境，包括模块、控制器、方法和模板信息可供设置使用
     * @return $this
     */
    public function setContext(array $context);

    /**
     * 保存控制器分配的变量
     * @param string $tpl_var
     * @param null $value
     * @param bool $nocache
     * @return $this
     */
    public function assign($tpl_var,$value=null,$nocache=false);

    /**
     * 获取所有替换字符串
     * @return array
     */
    public function getParsingString();

    /**
     * 设置模板替换字符串
     * @param string $str
     * @param string $replacement
     * @return void
     */
    public function registerParsingString($str,$replacement);
    /**
     * 显示模板
     * @param string $context 全部模板引擎通用的
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return void
     */
    public function display($context = null, $cache_id = null, $compile_id = null, $parent = null);

}