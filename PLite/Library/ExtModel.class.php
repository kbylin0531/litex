<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/14/16
 * Time: 10:29 AM
 */

namespace PLite\Library;
use PLite\Util\Helper\ClientAgent as UserAgent;

/**
 * Class ExtModel 扩展自ThinkPHP
 * @package PLite\Library
 */
class ExtModel extends Model {

    // 操作状态
    const MODEL_INSERT          =   1;      //  插入模型数据
    const MODEL_UPDATE          =   2;      //  更新模型数据
    const MODEL_BOTH            =   3;      //  包含上面两种方式
    const MUST_VALIDATE         =   1;      // 必须验证
    const EXISTS_VALIDATE       =   0;      // 表单存在字段则验证
    const VALUE_VALIDATE        =   2;      // 表单值不为空则验证

    private $data = null;

    /**
     * 创建数据对象 但不保存到数据库
     * @access public
     * @param mixed $data 创建数据
     * @param string $oprate_type 操作类型，更新还是插入
     * @return mixed
     */
    public function gather($data='', $oprate_type='') {
        // 如果没有传值默认取POST数据
        if(empty($data)) {
            $data   =   Input::think('post.');
        }elseif(is_object($data)){
            $data   =   get_object_vars($data);
        }
        // 验证数据
        if(empty($data) or !is_array($data)) {
            $this->error = 'Error : no data or data is not array!';
            return false;
        }

        // 状态
        $oprate_type = $oprate_type?:(!empty($data[$this->getPk()])?self::MODEL_UPDATE:self::MODEL_INSERT);

        // 检查字段映射
        $data =	$this->parseFieldsMap($data,self::FORM_TO_DB);

        // 检测提交字段的合法性
        if(isset($this->_options['fields'])) { // $this->field('field1,field2...')->create()
            $fields =   $this->_options['fields'];
            unset($this->_options['fields']);
        }elseif($oprate_type == self::MODEL_INSERT && isset($this->insertFields)) {
            $fields =   $this->insertFields;
        }elseif($oprate_type == self::MODEL_UPDATE && isset($this->updateFields)) {
            $fields =   $this->updateFields;
        }
        if(isset($fields)) {
            if(is_string($fields)) {
                $fields =   explode(',',$fields);
            }
            // 判断令牌验证字段
//            if(C('TOKEN_ON'))   $fields[] = C('TOKEN_NAME', null, '__hash__');
            foreach ($data as $key=>$val){
                if(!in_array($key,$fields)) {
                    unset($data[$key]);
                }
            }
        }

        // 数据自动验证
        if(!$this->autoValidation($data,$oprate_type)) return false;

        // 验证完成生成数据对象
        $fields =   $this->getFields();
        foreach ($data as $key=>$val){
            if(!in_array($key,$fields)) {
                unset($data[$key]);
            }
        }

        // 创建完成对数据进行自动处理
        $this->autoOperation($data,$oprate_type);
        // 赋值当前数据对象
        $this->data =   $data;
        // 返回创建的数据以供其他调用
        return $data;
    }

    /**
     * 自动完成定义
     * @var array
     */
    protected $_autoFinish            =   array();

    /**
     * 自动表单处理
     * @access public
     * @param array $data 创建数据
     * @param string $type 创建类型
     * @return mixed
     */
    private function autoOperation(&$data,$type) {
        // 自动填充
        if($this->_autoFinish) {
            foreach ($this->_autoFinish as $auto){
                // 填充因子定义格式
                // array('field','填充内容','填充条件','附加规则',[额外参数])
                if(empty($auto[2])) $auto[2] =  self::MODEL_INSERT; // 默认为新增的时候自动填充
                if( $type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                    if(empty($auto[3])) $auto[3] =  'string';
                    switch(trim($auto[3])) {
                        case 'function':    //  使用函数进行填充 字段的值作为参数
                        case 'callback': // 使用回调方法
                            $args = isset($auto[4])?(array)$auto[4]:array();
                            if(isset($data[$auto[0]])) {
                                array_unshift($args,$data[$auto[0]]);
                            }
                            if('function'==$auto[3]) {
                                $data[$auto[0]]  = call_user_func_array($auto[1], $args);
                            }else{
                                $data[$auto[0]]  =  call_user_func_array(array(&$this,$auto[1]), $args);
                            }
                            break;
                        case 'field':    // 用其它字段的值进行填充
                            $data[$auto[0]] = $data[$auto[1]];
                            break;
                        case 'ignore': // 为空忽略
                            if($auto[1]===$data[$auto[0]])
                                unset($data[$auto[0]]);
                            break;
                        case 'string':
                        default: // 默认作为字符串填充
                            $data[$auto[0]] = $auto[1];
                    }
                    if(isset($data[$auto[0]]) && false === $data[$auto[0]] )   unset($data[$auto[0]]);
                }
            }
        }
        return $data;
    }

    /**
     * 字段映射定义
     * array( '表单字段名称'=>'数据库字段名称'  );
     * @var array
     */
    protected $_fieldmap = [];
    const DB_TO_FORM = 1;
    const FORM_TO_DB = 0;
    /**
     * 处理字段映射
     * @access public
     * @param array $data 当前数据
     * @param integer $type 类型 0 写入 1 读取
     * @return array
     */
    public function parseFieldsMap($data,$type=self::DB_TO_FORM) {
        // 检查字段映射
        if($this->_fieldmap) {
            foreach ($this->_fieldmap as $key=> $val){
                if($type===self::DB_TO_FORM) { // 读取
                    if(isset($data[$val])) {
                        $data[$key] =   $data[$val];
                        unset($data[$val]);
                    }
                }else{
                    if(isset($data[$key])) {
                        $data[$val] =   $data[$key];
                        unset($data[$key]);
                    }
                }
            }
        }
        return $data;
    }


    /**
     * 自动验证定义
     *
     * 数据验证有两种方式：
     *  静态方式：在模型类里面通过$_validate属性定义验证规则。
     *  动态方式：XXXXXXXXXXXXXX
     *
     * 验证规则的定义是统一的规则，定义格式为：
     *     array(
     *          array(验证字段1,验证规则,错误提示,[验证条件,附加规则,验证时间]),
     *          ......
     *      );
     * @var array
     */
    protected $_validate        =   array();

    /**
     * 自动验证
     * @param array $data
     * @return bool|string 只有但会true时表示没有发生错误
     */
    protected function validate($data=null){
        var_export($data);
        return true;
    }

    /**
     * 自动表单验证
     * @access protected
     * @param array $data 创建数据
     * @param string $type 创建类型
     * @return boolean
     */
    protected function autoValidation($data,$type) {
        if($this->_validate) { // 如果设置了数据自动验证则进行数据验证
            foreach($this->_validate as $key=>$val) {
                // 验证因子定义格式: array(field,rule,message,condition,type,when,params)
                $field = &$val[0];
                $message = &$val[2];
                // 判断是否需要执行验证
                if(empty($val[5]) || ( $val[5]== self::MODEL_BOTH && $type < 3 ) || $val[5]== $type ) {
                    if(0===strpos($message,'{%') && strpos($message,'}')){
                        // 支持提示信息的多语言 使用 {%语言定义} 方式
                        $message  = substr($message,2,-1);
                    }
                    $val[3]  =  isset($val[3])?$val[3]:self::EXISTS_VALIDATE;
                    $val[4]  =  isset($val[4])?$val[4]:'regex';
                    // 判断验证条件
                    switch($val[3]) {
                        case self::MUST_VALIDATE:   // 必须验证 不管表单是否有设置该字段
                            if(false === $this->_validationField($data,$val))
                                return false;
                            break;
                        case self::VALUE_VALIDATE:    // 值不为空的时候才验证
                            if('' != trim($data[$field]))
                                if(false === $this->_validationField($data,$val))
                                    return false;
                            break;
                        default:    // 默认表单存在该字段就验证
                            if(isset($data[$field]))
                                if(false === $this->_validationField($data,$val))
                                    return false;
                    }
                }
            }
            // 批量验证的时候最后返回错误
            if(!empty($this->error)) return false;
        }
        return true;
    }

    /**
     * 验证表单字段 支持批量验证
     * 如果批量验证返回错误的数组信息
     * @access protected
     * @param array $data 创建数据
     * @param array $val 验证因子
     * @return boolean
     */
    private function _validationField($data,$val) {
        if(false === $this->_validationFieldItem($data,$val)){
            $this->error = $val[2];//错误信息
        }
        return false;
    }

    //验证类型
    const VALIDATE_TYPE_FUNCTION = 'function';
    const VALIDATE_TYPE_CALLBACK = 'callback';
    const VALIDATE_TYPE_COMPARE  = 'compare';
    const VALIDATE_TYPE_UNIQUE   = 'unique';
    const VALIDATE_TYPE_ADDITION = 'addition';
    /**
     * 根据验证因子验证字段
     * @access protected
     * @param array $data 创建数据
     * @param array $val 验证因子
     * @return boolean
     */
    protected function _validationFieldItem($data,$val) {
        switch(strtolower(trim($val[4]))) {
            case 'function':// 使用函数进行验证
            case 'callback':// 调用方法进行验证
                //函数参数获取
                $args = !empty($val[6])?(array)$val[6]:[];
                //元素一代表的函数是否合法
                if(is_string($val[0]) && strpos($val[0], ',')) $val[0] = explode(',', $val[0]);
                if(is_array($val[0])){ // 支持多个字段验证
                    $_data = [];
                    foreach($val[0] as $field) $_data[$field] = $data[$field];
                    array_unshift($args, $_data);//整体作为第一个元素插入
                }else{ //验证单个字段
                    array_unshift($args, $data[$val[0]]);
                }
                return call_user_func_array('function'===$val[4]?$val[1]:[&$this, $val[1]], $args);
            case 'compare': // 验证两个字段是否相同
                return $data[$val[0]] === $data[$val[1]];
            case 'unique': // 验证某个值是否唯一
                if(is_string($val[0]) && strpos($val[0],','))
                    $val[0]  =  explode(',',$val[0]);
                $map = array();
                if(is_array($val[0])) {
                    // 支持多个字段验证
                    foreach ($val[0] as $field)
                        $map[$field]   =  $data[$field];
                }else{
                    $map[$val[0]] = $data[$val[0]];
                }
                $pk =   $this->getPk();
                if(!empty($data[$pk]) && is_string($pk)) { // 完善编辑的时候验证唯一
                    $map[$pk] = array('neq',$data[$pk]);
                }
                if($this->where($map)->find())   return false;
                return true;
            case 'addition':
            default:  // 检查附加规则
                return $this->_check($data[$val[0]],$val[1],$val[4]);
        }
    }
    /**
     * 验证数据 支持 in between equal length regex expire ip_allow ip_deny
     * @access public
     * @param string $value 验证数据
     * @param mixed $rule 验证表达式
     * @param string $type 验证方式 默认为正则验证
     * @return boolean
     */
    private function _check($value,$rule,$type='regex'){
        $type   =   strtolower(trim($type));
        switch($type) {
            case 'in': // 验证是否在某个指定范围之内 逗号分隔字符串或者数组
            case 'notin':
                $range   = is_array($rule)? $rule : explode(',',$rule);
                return $type == 'in' ? in_array($value ,$range) : !in_array($value ,$range);
            case 'between': // 验证是否在某个范围
            case 'notbetween': // 验证是否不在某个范围
                if (is_array($rule)){
                    $min    =    $rule[0];
                    $max    =    $rule[1];
                }else{
                    list($min,$max)   =  explode(',',$rule);
                }
                return $type == 'between' ? $value>=$min && $value<=$max : $value<$min || $value>$max;
            case 'equal': // 验证是否等于某个值
                return $value == $rule;
            case 'notequal': // 验证是否等于某个值
                return $value != $rule;
            case 'length': // 验证长度
                $length  =  mb_strlen($value,'utf-8'); // 当前数据长度
                if(strpos($rule,',')) { // 长度区间
                    list($min,$max)   =  explode(',',$rule);
                    return $length >= $min && $length <= $max;
                }else{// 指定长度
                    return $length == $rule;
                }
            case 'expire':
                list($start,$end)   =  explode(',',$rule);
                if(!is_numeric($start)) $start   =  strtotime($start);
                if(!is_numeric($end)) $end   =  strtotime($end);
                return $_SERVER['REQUEST_TIME'] >= $start && $_SERVER['REQUEST_TIME'] <= $end;
            case 'ip_allow': // IP 操作许可验证
                return in_array(UserAgent::getClientIP(),explode(',',$rule));
            case 'ip_deny': // IP 操作禁止验证
                return !in_array(UserAgent::getClientIP(),explode(',',$rule));
            case 'regex':
            default:    // 默认使用正则验证 可以使用验证类中定义的验证名称
                // 检查附加规则
                return $this->regex($value,$rule);
        }
    }
    //内置验证正则表达式
    const VALIDATE_REGEX_RULE_REQUIRE = 'require';
    const VALIDATE_REGEX_RULE_EMAIL   = 'email';
    const VALIDATE_REGEX_RULE_URL     = 'url';
    const VALIDATE_REGEX_RULE_NUMBER  = 'number';
    const VALIDATE_REGEX_RULE_ZIP     = 'zip';
    const VALIDATE_REGEX_RULE_INTERGER= 'integer';
    const VALIDATE_REGEX_RULE_DOUBLE  = 'double';
    const VALIDATE_REGEX_RULE_ENGLISH = 'english';
    /**
     * 使用正则验证数据
     * @access public
     * @param string $value  要验证的数据
     * @param string $rule 验证规则
     * @return boolean
     */
    private function regex($value,$rule) {
        //内置的正则表达式
        $validate = array(
            'require'   =>  '/\S+/',
            'email'     =>  '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url'       =>  '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'currency'  =>  '/^\d+(\.\d+)?$/',
            'number'    =>  '/^\d+$/',
            'zip'       =>  '/^\d{6}$/',
            'integer'   =>  '/^[-\+]?\d+$/',
            'double'    =>  '/^[-\+]?\d+(\.\d+)?$/',
            'english'   =>  '/^[A-Za-z]+$/',
        );
        // 检查是否匹配内置的正则表达式
        isset($validate[strtolower($rule)]) and $rule = $validate[strtolower($rule)];
        return preg_match($rule,$value) === 1;
    }



}