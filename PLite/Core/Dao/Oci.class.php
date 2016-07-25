<?php
/**
 * Created by linzhv@outlook.com.
 * User: linzh
 * Date: 2016/6/21
 * Time: 20:35
 */

namespace PLite\Core\Dao;


class Oci extends DaoDriver {

    /**
     * 根据配置创建DSN
     * @param array $config 数据库连接配置
     * @return string
     */
    public function buildDSN(array $config){
        $dsn  =   'oci:dbname=//'.$config['hostname'].($config['port']?':'.$config['port']:'').'/'.$config['dbname'];
        if(!empty($config['charset'])) {
            $dsn  .= ';charset='.$config['charset'];
        }
        return $dsn;
    }

    public function escape($field){
        return "\"{$field}\"";
    }

    /**
     * 编译组件成适应当前数据库的SQL字符串
     * @param array $components  复杂SQL的组成部分
     * @param int $actiontype 操作类型
     * @return string
     */
    public function compile(array $components,$actiontype){}
}