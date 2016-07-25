<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 9:58 PM
 */

namespace PLite\Core;
use PLite\Core\Dao\DaoDriver;
use PLite\Lite;
use PDO;
use PDOStatement;
use PDOException;
use PLite\PLiteException as Exception;

/**
 * Class Dao
 * Database access object
 * @package PLite\Core
 */
class Dao extends Lite {
    const CONF_NAME = 'dao';
    const CONF_CONVENTION = [
        'AUTO_ESCAPE_ON'    => true,
        'PRIOR_INDEX' => 0,
        'DRIVER_CLASS_LIST' => [
            'PLite\\Core\\Dao\\MySQL',
            'PLite\\Core\\Dao\\Oci',
            'PLite\\Core\\Dao\\SQLServer',
        ],
        'DRIVER_CONFIG_LIST' => [
            [
                'dbname'    => 'xor',//选择的数据库
                'username'  => 'lin',
                'password'  => '123456',
                'host'      => 'localhost',
                'port'      => '3306',
                'charset'   => 'UTF8',
                'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
                'options'   => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
                ],
            ],
            [
                'type'      => 'Oci',//数据库类型
                'dbname'    => 'xor',//选择的数据库
                'username'  => 'lin',
                'password'  => '123456',
                'host'      => 'localhost',
                'port'      => '3306',
                'charset'   => 'UTF8',
                'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
                'options'   => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
                ],
            ],
            [
                'type'      => 'Sqlsrv',//数据库类型
                'dbname'    => 'xor',//选择的数据库
                'username'  => 'lin',
                'password'  => '123456',
                'host'      => 'localhost',
                'port'      => '3306',
                'charset'   => 'UTF8',
                'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
                'options'   => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
                ],
            ],
        ],
    ];

    /**
     * 每一个Dao的驱动实现
     * @var DaoDriver
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

    /**
     * 获取Dao实例
     * 去过要获取自定义配置的PDO对象,建议使用原生的代替
     * @param int|string|array $driver_index 驱动器角标
     * @return Dao
     */
    public static function getInstance($driver_index=null){
        static $_daoInstances = [];//Dao[]
        if(!isset($_daoInstances[$driver_index])){
            $_daoInstances[$driver_index] = new Dao($driver_index);
        }
        return $_daoInstances[$driver_index];
    }

    /**
     * Dao constructor.
     * @param string|int $index
     */
    private function __construct($index){
        $this->_driver = self::getDriver($index);
    }

    /**
     * 获取PDO对象上发生的错误
     * [
     *      0   => SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard).
     *      1   => Driver-specific error code.
     *      2   => Driver-specific error message.
     * ]
     * If the SQLSTATE error code is not set or there is no driver-specific error,
     * the elements following element 0 will be set to NULL .
     * @param PDO $pdo PDO对象或者继承类的实例
     * @return null|string null表示未发生错误,string表示序列化的错误信息
     */
    public static function fetchPdoError(PDO $pdo=null){
        $pdoError = $pdo->errorInfo();
        return null !== $pdoError[0]? "PDO Error:{$pdoError[0]} >>> [{$pdoError[1]}]:[{$pdoError[2]}]":null;// PDO错误未被设置或者错误未发生,0位的值为null
    }

    /**
     * 获取PDOStatemnent对象上查询时发生的错误
     * 错误代号参照ANSI CODE ps: https://docs.oracle.com/cd/F49540_01/DOC/server.815/a58231/appd.htm
     * @param PDOStatement|null $statement 发生了错误的PDOStatement对象
     * @return string|null 错误未发生时返回null
     */
    public static function fetchPdoStatementError(PDOStatement $statement){
        $stmtError = $statement->errorInfo();
        return 0 !== intval($stmtError[0])?"Error Code:[{$stmtError[0]}]::[{$stmtError[1]}]:[{$stmtError[2]}]":null;//代号为0时表示错误未发生
    }

    /********************************* 基本的查询功能(发生了错误可以查询返回值是否是false,getError可以获取错误的详细信息(每次调用这些功能前都会清空之前的错误)) ***************************************************************************************/
    /**
     * 简单地查询一段SQL，并且将解析出所有的结果集合
     * @param string $sql 查询的SQL
     * @param array $inputs 输入参数
     *                          如果输入参数未设置或者为null（显示声明），则直接查询
     *                          如果输入参数为非空数组，则使用PDOStatement对象查询
     * @return array|false 返回array类型表述查询结果，返回false表示查询出错，可能是数据表不存在等数据库返回的错误信息
     */
    public function query($sql,array $inputs=null){
        $this->error = null;
//        \PLite\dumpout($sql,$inputs,$this->_driver);
        if(empty($inputs)){
            //直接使用PDO的查询功能
            try{
                $statement = $this->_driver->query($sql);//返回PDOstatement,失败时返回false(或者抛出异常)，视错误的处理方式而定

                if(false !== $statement){
                    //query成功时返回PDOStatement对象
                    return $statement->fetchAll();//成功返回
                }else{
                    $this->error = Dao::fetchPdoError($this->_driver);
                }
            }catch(\PDOException $e){
                $this->error = $e->getMessage();
            }
        }else{
            try {
                //简介调用PDOStatement的查询功能
                $statement = $this->_driver->prepare($sql);//可能returnfalse或者抛出错误
                if(false == $statement){
                    $this->error = Dao::fetchPdoError($this->_driver);
                }
                if(false === $statement->execute($inputs)){/*execute不会抛出异常*/
                    $this->error = Dao::fetchPdoStatementError($statement);
                }
                return $statement->fetchAll();
            }catch(\PDOException $e){
                $this->error = $e->getMessage();
            }
//            \PLite\dumpout($this->error);
        }
        return false;
    }
    /**
     * 简单地执行Insert、Delete、Update操作
     * @param string $sql 待查询的SQL语句，如果未设置输入参数则需要保证SQL已经被转义
     * @param array|null $inputs 输入参数,具体参考query方法的参数二
     * @return int|false 返回受到影响的行数，但是可能不会太可靠，需要用===判断返回值是0还是false
     *                   返回false表示了错误，可以用getError获取错误信息
     */
    public function exec($sql,array $inputs=[]){
//        static $c = 0;
        $this->error = null;
        if(empty($inputs)){
            //调用PDO的查询功能
            try{
                $rst = $this->_driver->exec($sql);
                if(false !== $rst) return $rst;
                $this->error = Dao::fetchPdoError($this->_driver);
            }catch (\PDOException $e){
                $this->error = $e->getMessage();
            }
        }else{ //调用PDOStatement的查询功能
            try {
                $statement = $this->_driver->prepare($sql);
                if(false === $statement){
                    $this->error = Dao::fetchPdoError($this->_driver);
                }else{
//                    \PLite\dumpout($inputs);
                    array_walk($inputs,function ($value){
//                        \PLite\dumpout($value);
                        if(is_array($value)){
                            Exception::throwing(['元素类型需要设置成可转换成string类型的类型!',var_export($value)]);
                        }
                    } );
                    if(false !== $statement->execute($inputs)){
//                        $c ++;
                        return $statement->rowCount();
                    }
                }
            }catch(\PDOException $e){
                $this->error  =$e->getMessage();
            }
        }
        return false;
    }

    /**
     * 返回PDO驱动或者上一个PDO语句对象上发生的错误的信息（具体驱动的错误号和错误信息）
     * 注意：调用此函数后会将错误信息清空
     * @return string 返回错误信息字符串，没有错误发生时返回空字符串
     */
    public function getError(){
        return $this->error;
    }

    /********************************* 高级查询功能(支持链式调用相应的错误的处理必须是异常处理,与无法通过getError获取这些错误的详细信息,但可以通过$e->getMessage()获取详细信息) ***************************************************************************************/
    /**
     * 准备一段SQL
     * @param string $sql 查询的SQL，当参数二指定的ID存在，只有在参数一布尔值不为false时，会进行真正地prepare
     * @param array $option prepare方法参数二
     * @return $this 返回prepare返回的对象，如果失败则返回null
     * @throws Exception 如果出错将抛出错误
     */
    public function prepare($sql,array $option=[]){
        $error = null;
        try{
            $this->curStatement = $this->_driver->prepare($sql,$option);//prepare失败抛出异常后赋值过程结束,$this->curStatement可能依旧指向之前的SQLStatement对象（可能不为null）
            $this->curStatement or $error = Dao::fetchPdoError($this->_driver);
        }catch(\PDOException $e){  $error = $e->getMessage(); }
        $error and Exception::throwing($error);
        return $this;
    }

    /**
     * 执行查询功能，返回的结果是bool表示是否执行成功
     * @param array|null $input_parameters
     *                  一个元素个数和将被执行的 SQL 语句中绑定的参数一样多的数组。所有的值作为 PDO::PARAM_STR 对待。
     *                  不能绑定多个值到一个单独的参数,如果在 input_parameters 中存在比 PDO::prepare() 预处理的SQL 指定的多的键名，
     *                  则此语句将会失败并发出一个错误。(这个错误在php 5.2.0版本之前是默认忽略的)
     * @return bool bool值表示执行结果，当不存在执行对象时返回null，可以通过rowCount方法获取受到影响行数，或者getError获取错误信息
     */
    public function execute(array $input_parameters = null){
        $this->curStatement or Exception::throwing('No Statement to execute!');
        //出错时设置错误信息，注：PDOStatement::execute返回bool类型的结果 参数数目不正确时候会抛出异常"Invalid parameter number"
        $this->curStatement->execute($input_parameters) or Exception::throwing(Dao::fetchPdoStatementError($this->curStatement));
        return true;
    }

    /**
     * 返回一个包含结果集中所有剩余行的数组
     * 此数组的每一行要么是一个列值的数组，要么是属性对应每个列名的一个对象
     * @param int|null $fetch_style
     *          想要返回一个包含结果集中单独一列所有值的数组，需要指定 PDO::FETCH_COLUMN ，
     *          通过指定 column-index 参数获取想要的列。
     *          想要获取结果集中单独一列的唯一值，需要将 PDO::FETCH_COLUMN 和 PDO::FETCH_UNIQUE 按位或。
     *          想要返回一个根据指定列把值分组后的关联数组，需要将 PDO::FETCH_COLUMN 和 PDO::FETCH_GROUP 按位或
     * @param int $fetch_argument
     *                  参数一为PDO::FETCH_COLUMN时，返回指定以0开始索引的列（组合形式如上）
     *                  参数一为PDO::FETCH_CLASS时，返回指定类的实例，映射每行的列到类中对应的属性名
     *                  参数一为PDO::FETCH_FUNC时，将每行的列作为参数传递给指定的函数，并返回调用函数后的结果
     * @param array $constructor_args 参数二为PDO::FETCH_CLASS时，类的构造参数
     * @return array
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $constructor_args = null){
        $this->curStatement or Exception::throwing('No Statement to fetch!');
        $param = [];
        isset($fetch_style)         and $param[0] = $fetch_style;
        isset($fetch_argument)      and $param[1] = $fetch_argument;
        isset($constructor_args)    and $param[2] = $constructor_args;
        return call_user_func_array(array($this->curStatement,'fetchAll'),$param);
    }

    /**
     * 从结果集中获取下一行
     * @param int $fetch_style
     *              \PDO::FETCH_ASSOC 关联数组
     *              \PDO::FETCH_BOUND 使用PDOStatement::bindColumn()方法时绑定变量
     *              \PDO::FETCH_CLASS 放回该类的新实例，映射结果集中的列名到类中对应的属性名
     *              \PDO::FETCH_OBJ   返回一个属性名对应结果集列名的匿名对象
     * @param int $cursor_orientation 默认使用\PDO::FETCH_ORI_NEXT，还可以是PDO::CURSOR_SCROLL，PDO::FETCH_ORI_ABS，PDO::FETCH_ORI_REL
     * @param int $cursor_offset
     *              参数二设置为PDO::FETCH_ORI_ABS(absolute)时，此值指定结果集中想要获取行的绝对行号
     *              参数二设置为PDO::FETCH_ORI_REL(relative) 时 此值指定想要获取行相对于调用 PDOStatement::fetch() 前游标的位置
     * @return mixed 此函数（方法）成功时返回的值依赖于提取类型。在所有情况下，失败都返回 FALSE
     */
    public function fetch($fetch_style = null, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0){
        $this->curStatement or Exception::throwing('No Statement to fetch!');
        return $this->curStatement->fetch($fetch_style,$cursor_orientation,$cursor_offset);
    }

    /**
     * 返回上一个由对应的 PDOStatement 对象执行DELETE、 INSERT、或 UPDATE 语句受影响的行数
     * 如果上一条由相关 PDOStatement 执行的 SQL 语句是一条 SELECT 语句，有些数据可能返回由此语句返回的行数
     * 但这种方式不能保证对所有数据有效，且对于可移植的应用不应依赖于此方式
     * @return int
     * @throws Exception
     */
    public function rowCount(){
        $this->curStatement or Exception::throwing('No Statement to count the affected rows!');
        return $this->curStatement->rowCount();
    }

    /**
     * 释放到数据库服务的连接，以便发出其他 SQL 语句(新的参数绑定)，使得该SQL语句处于一个可以被再次执行的状态
     * 当上一个执行的 PDOStatement 对象仍有未取行时，此方法对那些不支持再执行一个 PDOStatement 对象的数据库驱动非常有用。
     * 如果数据库驱动受此限制，则可能出现失序错误的问题
     * PDOStatement::Cursor() 要么是一个可选驱动的特有方法（效率最高）来实现，要么是在没有驱动特定的功能时作为一般的PDO 备用来实现
     * <note>
     *      ① 语意上相当于下面的语句的执行结果
     *          do {
     *              while ($stmt->fetch());
     *              if (!$stmt->nextRowset()) break;
     *          } while (true);
     * </note>
     * @return bool 成功时返回 TRUE， 或者在失败时返回 FALSE
     */
    public function closeCursor(){
        $this->curStatement or Exception::throwing('No Statement to close!');
        return $this->curStatement->closeCursor();
    }

    /****************************** 事务功能 ***************************************************************************************

    /**
     * 开启事务
     * @return bool
     */
    public function beginTransaction(){
        $result = $this->_driver->beginTransaction();
//        dumpout($result,$this->driver->inTransaction());
        return $result;
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit(){
        $this->_driver->inTransaction() or Exception::throwing('Not in transaction!');
        return $this->_driver->commit();
    }
    /**
     * 回滚事务
     * @return bool
     */
    public function rollBack(){
        return $this->_driver->rollBack();
    }
    /**
     * 确认是否在事务中
     * @return bool
     */
    public function inTransaction(){
        return $this->_driver->inTransaction();
    }

    /************************************** 驱动实现扩展方法 ******************************************************************************************/

    /**
     * 转义保留字字段名称
     * @param string $fieldname 字段名称
     * @return string
     */
    public function escape($fieldname){
        return $this->_driver->escape($fieldname);
    }

    /**
     * 返回最后插入行的ID或序列值
     * NoteL:
     *  官方：在不同的 PDO 驱动之间，此方法可能不会返回一个有意义或一致的结果，因为底层数据库可能不支持自增字段或序列的概念
     *  推测只要是自增字段就可以把其值返回
     * @param string $name 应该返回ID的那个序列对象的名称
     * @return string
     */
    public function lastInsertId($name=null){
        return $this->_driver->lastInsertId($name);
    }
}