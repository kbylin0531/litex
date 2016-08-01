<?php
/**
 * Created by PhpStorm.
 * User: linzhv
 * Date: 7/29/16
 * Time: 5:19 PM
 */
namespace framework\library;
use PDO;
use PDOStatement;
use PDOException;
use PLite\Core\Dao;

/**
 * Class DataAccess Dao for php5.3
 * @package PLite\Core
 */
class DataAccess {

    protected $config = array(
        'AUTO_ESCAPE_ON'    => true,

        'class' => 'framework\\library\\MySQL',
        'config'=> array(
            'dbname'    => 'test',//选择的数据库
            'username'  => 'lin',
            'password'  => '123456',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'UTF8',
            'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
            'options'   => array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
            ),
        ),
    );


    /**
     * 每一个Dao的驱动实现
     * @var PDO
     */
    protected $_driver = null;
    /**
     * 当前操作的PDOStatement对象
     * @var PDOStatement
     */
    private $curStatement = null;
    /**
     * SQL执行发生的错误信息
     * @var string|null
     */
    private $error = null;

    private static $_instances = array();

    /**
     * Dao constructor.
     * @param string|int $index
     */
    private function __construct($index){
        $this->_driver = self::getDriver($index);
    }

    /**
     * 获取Dao实例
     * 去过要获取自定义配置的PDO对象,建议使用原生的代替
     * @param int|string|array $driver_index 驱动器角标
     * @return DataAccess
     */
    public static function getInstance($driver_index=null){
        if(!isset(self::$_instances[$driver_index])){
            self::$_instances[$driver_index] = new DataAccess($driver_index);
        }
        return self::$_instances[$driver_index];
    }



}



class MySQL extends PDO {

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
     * 使用语句编译SELECT语句
     * @param array $components SQL组件
     * @return string
     */
    protected function compileSelect($components){
        $components['distinct'] and $components['distinct'] = 'distinct';//为true或者1时转化为distinct关键字

        $sql = "SELECT {$components['distinct']} \n{$components['fields']} \nFROM {$components['table']} \r\n";

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