<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 8:48 PM
 */
namespace PLite\Util;
use PLite\PLiteException;

/**
 * Class SEK System execute kits
 * @package PLite
 */
final class SEK {

    /**
     * decompose the params from request
     * @param string $name parameter name
     * @param array|null $src
     */
    public static function decomposeParam($name,array $src=null){
        $src or $src = &$_REQUEST;
        if(isset($src[$name])){
            $temp = [];
            parse_str($src[$name],$temp);
            $_POST = array_merge($_POST,$temp);
            $src = array_merge($src,$temp);
            $_GET = array_merge($_GET,$temp);
            unset($src[$name]);
        }
    }

    public static function arrayRandom(array $arr){
        return $arr[mt_rand(0,count($arr)-1)];
    }


    /**
     * Returns the MIME types array from config/mimes.php
     *
     * @return	array
     */
    public static function &getMimes(){
        static $_mimes;
        $_mimes  or  $_mimes = include PATH_PLITE.'/Common/mime.php';
        return $_mimes;
    }

    /**
     * 根据文件名后缀获取响应文件类型
     * @param string $suffix 后缀名，不包括点号
     * @return null|string
     */
    public static function getMimeBysuffix($suffix){
        static $mimes = null;
        $mimes or $mimes = include dirname(__DIR__).'/Common/mime.php';
        isset($mimes[$suffix]) or PLiteException::throwing();
        return $mimes[$suffix];
    }
    /**
     * 解析资源文件地址
     * 模板文件资源位置格式：
     *      ModuleA/ModuleB@ControllerName/ActionName:themeName
     * @param array|null $context 模板调用上下文环境，包括模块、控制器、方法和模板主题
     * @return array 类型由参数三决定
     */
    public static function parseTemplatePath($context){
        $path = PATH_BASE."/Application/{$context['m']}/View/{$context['c']}/";
        isset($context['t']) and $path = "{$path}{$context['t']}/";
        $path = "{$path}{$context['a']}";
        return $path;
    }

    //-------------------------------------------------------------------------------------
    //--------------------------- For Router and url Creater ----------------------------------------------
    //-------------------------------------------------------------------------------------
    /**
     * 模块序列转换成数组形式
     * 且数组形式的都是大写字母开头的单词形式
     * @param string|array $modules 模块序列
     * @param string $mmbridge 模块之间的分隔符
     * @return array
     * @throws \Exception
     */
    public static function toModulesArray($modules, $mmbridge='/'){
        if(is_string($modules)){
            if(false === stripos($modules,$mmbridge)){
                $modules = [$modules];
            }else{
                $modules = explode($mmbridge,$modules);
            }
        }
        is_array($modules) or PLiteException::throwing('Parameter should be an array!');
        return array_map(function ($val) {
            return Utils::styleStr($val,1);
        }, $modules);
    }

    /**
     * 模块学列数组转换成模块序列字符串
     * 模块名称全部小写化
     * @param array|string $modules 模块序列
     * @param string $mmb
     * @return string
     * @throws PLiteException
     */
    public static function toModulesString($modules,$mmb='/'){
        if(is_array($modules)){
            $modules = implode($mmb,$modules);
        }
        is_string($modules) or PLiteException::throwing('Invalid Parameters!');
        return trim($modules,' /');
    }
    /**
     * 将参数序列装换成参数数组，应用Router模块的配置
     * @param string $params 参数字符串
     * @param string $ppb
     * @param string $pkvb
     * @return array
     */
    public static function toParametersArray($params,$ppb='/',$pkvb='/'){//解析字符串成数组
        $pc = [];
        if($ppb !== $pkvb){//使用不同的分割符
            $parampairs = explode($ppb,$params);
            foreach($parampairs as $val){
                $pos = strpos($val,$pkvb);
                if(false === $pos){
                    //非键值对，赋值数字键
                }else{
                    $key = substr($val,0,$pos);
                    $val = substr($val,$pos+strlen($pkvb));
                    $pc[$key] = $val;
                }
            }
        }else{//使用相同的分隔符
            $elements = explode($ppb,$params);
            $count = count($elements);
            for($i=0; $i<$count; $i += 2){
                if(isset($elements[$i+1])){
                    $pc[$elements[$i]] = $elements[$i+1];
                }else{
                    //单个将被投入匿名参数,先废弃
                }
            }
        }
        return $pc;
    }

    /**
     * 将参数数组转换成参数序列，应用Router模块的配置
     * @param array $params 参数数组
     * @param string $ppb
     * @param string $pkvb
     * @return string
     */
    public static function toParametersString(array $params=null,$ppb='/',$pkvb='/'){
        //希望返回的是字符串是，返回值是void，直接修改自$params
        if(empty($params)) return '';
        $temp = '';
        if($params){
            foreach($params as $key => $val){
                $temp .= "{$key}{$pkvb}{$val}{$ppb}";
            }
            return substr($temp,0,strlen($temp) - strlen($ppb));
        }else{
            return $temp;
        }
    }

    /**
     * 调用位置
     */
    const PLACE_BACKWORD           = 0; //表示调用者自身的位置
    const PLACE_SELF               = 1;// 表示调用调用者的位置
    const PLACE_FORWARD            = 2;
    const PLACE_FURTHER_FORWARD    = 3;
    /**
     * 信息组成
     */
    const ELEMENT_FUNCTION = 1;
    const ELEMENT_FILE     = 2;
    const ELEMENT_LINE     = 4;
    const ELEMENT_CLASS    = 8;
    const ELEMENT_TYPE     = 16;
    const ELEMENT_ARGS     = 32;
    const ELEMENT_ALL      = 0;

    /**
     * 获取调用者本身的位置
     * @param int $elements 为0是表示获取全部信息
     * @param int $place 位置属性
     * @return array|string
     */
    public static function backtrace($elements=self::ELEMENT_ALL, $place=self::PLACE_SELF) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
//        \PLite\dump($trace);
        $result = [];
        if($elements){
            $elements & self::ELEMENT_ARGS     and $result[self::ELEMENT_ARGS]    = isset($trace[$place]['args'])?$trace[$place]['args']:null;
            $elements & self::ELEMENT_CLASS    and $result[self::ELEMENT_CLASS]   = isset($trace[$place]['class'])?$trace[$place]['class']:null;
            $elements & self::ELEMENT_FILE     and $result[self::ELEMENT_FILE]    = isset($trace[$place]['file'])?$trace[$place]['file']:null;
            $elements & self::ELEMENT_FUNCTION and $result[self::ELEMENT_FUNCTION]= isset($trace[$place]['function'])?$trace[$place]['function']:null;
            $elements & self::ELEMENT_LINE     and $result[self::ELEMENT_LINE]    = isset($trace[$place]['line'])?$trace[$place]['line']:null;
            $elements & self::ELEMENT_TYPE     and $result[self::ELEMENT_TYPE]    = isset($trace[$place]['type'])?$trace[$place]['type']:null;
            1 === count($result) and $result = array_shift($result);//一个结果直接返回
        }else{
            $result = $trace[$place];
        }
        return $result;
    }

    /**
     * 解析模板位置
     * 测试代码：
    $this->parseTemplateLocation('ModuleA/ModuleB@ControllerName/ActionName:themeName'),
    $this->parseTemplateLocation('ModuleA/ModuleB@ControllerName/ActionName'),
    $this->parseTemplateLocation('ControllerName/ActionName:themeName'),
    $this->parseTemplateLocation('ControllerName/ActionName'),
    $this->parseTemplateLocation('ActionName'),
    $this->parseTemplateLocation('ActionName:themeName')
     * @param string $location 模板位置
     * @return array
     */
    public static function parseLocation($location){
        //资源解析结果：元素一表示解析结果
        $result = [];

        //-- 解析字符串成数组 --//
        $tpos = strpos($location,':');
        //解析主题
        if(false !== $tpos){
            //存在主题
            $result['t'] = substr($location,$tpos+1);//末尾的pos需要-1-1
            $location = substr($location,0,$tpos);
        }
        //解析模块
        $mcpos = strpos($location,'@');
        if(false !== $mcpos){
            $result['m'] = substr($location,0,$mcpos);
            $location = substr($location,$mcpos+1);
        }
        //解析控制器和方法
        $capos = strpos($location,'/');
        if(false !== $capos){
            $result['c'] = substr($location,0,$capos);
            $result['a'] = substr($location,$capos+1);
        }else{
            $result['a'] = $location;
        }

        isset($result['t']) or $result['t'] = null;
        isset($result['m']) or $result['m'] = null;
        isset($result['c']) or $result['c'] = null;
        isset($result['a']) or $result['a'] = null;

        return $result;
    }

    /**
     * 去除代码中的空白和注释
     * @param string $content 代码内容
     * @return string
     */
    public static function stripWhiteSpace($content) {
        $stripStr   = '';
        //分析php源码
        $tokens     = token_get_all($content);
        $last_space = false;
        for ($i = 0, $j = count($tokens); $i < $j; $i++) {
            if (is_string($tokens[$i])) {
                $last_space = false;
                $stripStr  .= $tokens[$i];
            } else {
                switch ($tokens[$i][0]) {
                    //过滤各种php注释
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        break;
                    //过滤空格
                    case T_WHITESPACE:
                        if (!$last_space) {
                            $stripStr  .= ' ';
                            $last_space = true;
                        }
                        break;
                    case T_START_HEREDOC:
                        $stripStr .= "<<<PLite\n";
                        break;
                    case T_END_HEREDOC:
                        $stripStr .= "PLite;\n";
                        for($k = $i+1; $k < $j; $k++) {
                            if(is_string($tokens[$k]) && $tokens[$k] == ';') {
                                $i = $k;
                                break;
                            } else if($tokens[$k][0] == T_CLOSE_TAG) {
                                break;
                            }
                        }
                        break;
                    default:
                        $last_space = false;
                        $stripStr  .= $tokens[$i][1];
                }
            }
        }
        return $stripStr;
    }

    /**
     * 数组递归遍历
     * @param array $array 待递归调用的数组
     * @param callable $filter 遍历毁掉函数
     * @param bool $keyalso 是否也应用到key上
     * @return array
     */
    public static function arrayRecursiveWalk(array $array, callable $filter,$keyalso=false) {
        static $recursive_counter = 0;
        if (++ $recursive_counter > 1000) die( 'possible deep recursion attack' );
        $result = [];
        foreach ($array as $key => $val) {
            $result[$key] = is_array($val) ? self::arrayRecursiveWalk($val,$filter,$keyalso) : call_user_func($filter, $val);

            if ($keyalso and is_string ( $key )) {
                $new_key = $filter ( $key );
                if ($new_key != $key) {
                    $array [$new_key] = $array [$key];
                    unset ( $array [$key] );
                }
            }
        }
        -- $recursive_counter;
        return $result;
    }



    /**
     * 将数组转换为JSON字符串（兼容中文）
     * @access public
     * @param array $array 要转换的数组
     * @param string $filter
     * @return string
     */
    public static function toJson(array $array,$filter='urlencode') {
        self::arrayRecursiveWalk($array, $filter, true );
        $json = json_encode ( $array );
        return urldecode ( $json );
    }

    /**
     * 数据签名认证
     * @param  mixed  $data 被认证的数据
     * @return string       签名
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public static function dataSign($data) {
        //数据类型检测
        if(!is_array($data)){
            $data = (array)$data;
        }
        ksort($data); //排序
        $code = http_build_query($data); //url编码并生成query字符串
        $sign = sha1($code); //生成签名
        return $sign;
    }
}