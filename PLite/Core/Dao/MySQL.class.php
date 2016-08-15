<?php
namespace PLite\Core\Dao;
use PLite\Core\DaoDriver;

/**
 * Class MySQL
 * @package PLite\Core\Dao
 */
class MySQL extends DaoDriver {

    public function escape($field){
        return "`{$field}`";
    }

    /**
     * 根据配置创建DSN
     * @param array $config 数据库连接配置
     * @return string
     */
    public function buildDSN(array $config){
        $dsn  =  "mysql:host={$config['host']}";
        if(isset($config['dbname'])){
            $dsn .= ";dbname={$config['dbname']}";
        }
        if(!empty($config['port'])) {
            $dsn .= ';port=' . $config['port'];
        }
        if(!empty($config['socket'])){
            $dsn  .= ';unix_socket='.$config['socket'];
        }
        if(!empty($config['charset'])){
            //为兼容各版本PHP,用两种方式设置编码
//            $this->options[\PDO::MYSQL_ATTR_INIT_COMMAND]    =   'SET NAMES '.$config['charset'];
            $dsn  .= ';charset='.$config['charset'];
        }
        return $dsn;
    }


    /**
     * 取得数据表的字段信息
     * @access public
     * @param string $tableName 数据表名称
     * @return array
     */
    public function getFields($tableName) {
        list($tableName) = explode(' ', $tableName);
        if(strpos($tableName,'.')){
            list($dbName,$tableName) = explode('.',$tableName);
            $sql   = 'SHOW COLUMNS FROM `'.$dbName.'`.`'.$tableName.'`';
        }else{
            $sql   = 'SHOW COLUMNS FROM `'.$tableName.'`';
        }

        $result = $this->query($sql);
        $info   =   array();
        if($result) {
            foreach ($result as $key => $val) {
                if(\PDO::CASE_LOWER != $this->getAttribute(\PDO::ATTR_CASE)){
                    $val = array_change_key_case ( $val ,  CASE_LOWER );
                }
                $info[$val['field']] = array(
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => (bool) ($val['null'] === ''), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @param string $dbName
     * @return array
     */
    public function getTables($dbName=null) {
        $sql    = empty($dbName)?'SHOW TABLES ;':"SHOW TABLES FROM {$dbName};";
        $result = $this->query($sql);
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * 字段和表名处理(关机那字处理)
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key) {
        $key   =  trim($key);
        if(!is_numeric($key) && !preg_match('/[,\'\"\*\(\)`.\s]/',$key)) {//中间不存在,'"*()`以及空格 ??? .不是匹配全部吗
            $key = '`'.$key.'`';
        }
        return $key;
    }

    /**
     * 编译组件成适应当前数据库的SQL字符串
     * @param array $components  复杂SQL的组成部分
     * @param int $actiontype 操作类型
     * @return string
     */
    public function compile(array $components,$actiontype){
//        $_components = array(
//            'distinct'  => '',
//            'fields'    => ' * ', //查询的表域情况
//            'table'     => null,
//            'join'      => null,     //join部分，需要带上join关键字
//            'where'     => null, //where部分
//            'group'     => null, //分组 需要带上group by
//            'having'    => null,//having子句，依赖$group存在，需要带上having部分
//            'order'     => null,//排序，不需要带上order by
//            'limit'     => 2,
//            'offset'    => 5,
//        );
    }

    /**
     * 使用语句编译SELECT语句
     * @param array $components SQL组件
     * @return string
     */
    protected function compileSelect($components){
        $components['distinct'] and $components['distinct'] = 'distinct';//为true或者1时转化为distinct关键字

        $sql = "SELECT {$components['distinct']} \r\n{$components['fields']} \r\nFROM {$components['table']} \r\n";

        //group by，having 加上关键字(对于如group by的组合关键字，只要判断第一个是否存在)如果不是以该关键字开头  则自动添加
        if($components['join']){
            $sql .= "{$components['join']} \r\n";
        }
        if($components['where']){
//            $components['where'] = ((0 !== stripos(trim($components['where']),'where'))?'WHERE ':'').$components['where'];
            $sql .= "WHERE {$components['where']} \r\n";
        }
        if($components['group'] ){
//            $components['group'] = ((0 !== stripos(trim($components['group']),'group'))?'GROUP BY ':'').$components['group'];
            $sql .= "GROUP BY {$components['group']} \r\n";
        }
        if( $components['having']){
//            $components['having'] = ((0 !== stripos(trim($components['having']),'having'))?'HAVING ':'').$components['having'];
            $sql .= "HAVING {$components['having']} \r\n";
        }
        //去除order by
//        $components['order'] = preg_replace_callback('|order\s*by|i',function(){return '';},$components['order']);

        if($components['order']) $sql .= "ORDER BY {$components['order']} \r\n";

        //是否偏移
        if($components['limit']){
            if($components['offset']) $components['offset'] .= ',';
            $sql .= "LIMIT {$components['offset']}{$components['limit']} \r\n";
        }
        return $sql;
    }
}