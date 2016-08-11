<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 6:28 PM
 */
namespace PLite;

use PLite\Util\SEK;

/**
 * session管理函数
 * @param string|array $name session名称 如果为数组则表示进行session设置
 * @param mixed $value session值
 * @return mixed
 */
function session($name='',$value='') {
    $prefix   =  'PLite_';
    if(is_array($name)) { // session初始化 在session_start 之前调用
        if(isset($name['name']))            session_name($name['name']);
        if(isset($name['path']))            session_save_path($name['path']);
        if(isset($name['domain']))          ini_set('session.cookie_domain', $name['domain']);
        if(isset($name['expire']))          ini_set('session.gc_maxlifetime', $name['expire']);
        if(isset($name['use_trans_sid']))   ini_set('session.use_trans_sid', $name['use_trans_sid']?1:0);
        if(isset($name['use_cookies']))     ini_set('session.use_cookies', $name['use_cookies']?1:0);
        if(isset($name['cache_limiter']))   session_cache_limiter($name['cache_limiter']);
        if(isset($name['cache_expire']))    session_cache_expire($name['cache_expire']);
    }elseif('' === $value){
        if(''===$name){
            // 获取全部的session
            return $prefix ? $_SESSION[$prefix] : $_SESSION;
        }elseif(0===strpos($name,'[')) { // session 操作
            if('[pause]'==$name){ // 暂停session
                session_write_close();
            }elseif('[start]'==$name){ // 启动session
                session_start();
            }elseif('[destroy]'==$name){ // 销毁session
                $_SESSION =  array();
                session_unset();
                session_destroy();
            }elseif('[regenerate]'==$name){ // 重新生成id
                session_regenerate_id();
            }
        }elseif(0===strpos($name,'?')){ // 检查session
            $name   =  substr($name,1);
            if(strpos($name,'.')){ // 支持数组
                list($name1,$name2) =   explode('.',$name);
                return $prefix?isset($_SESSION[$prefix][$name1][$name2]):isset($_SESSION[$name1][$name2]);
            }else{
                return $prefix?isset($_SESSION[$prefix][$name]):isset($_SESSION[$name]);
            }
        }elseif(is_null($name)){ // 清空session
            if($prefix) {
                unset($_SESSION[$prefix]);
            }else{
                $_SESSION = array();
            }
        }elseif($prefix){ // 获取session
            if(strpos($name,'.')){
                list($name1,$name2) =   explode('.',$name);
                return isset($_SESSION[$prefix][$name1][$name2])?$_SESSION[$prefix][$name1][$name2]:null;
            }else{
                return isset($_SESSION[$prefix][$name])?$_SESSION[$prefix][$name]:null;
            }
        }else{
            if(strpos($name,'.')){
                list($name1,$name2) =   explode('.',$name);
                return isset($_SESSION[$name1][$name2])?$_SESSION[$name1][$name2]:null;
            }else{
                return isset($_SESSION[$name])?$_SESSION[$name]:null;
            }
        }
    }elseif(is_null($value)){ // 删除session
        if(strpos($name,'.')){
            list($name1,$name2) =   explode('.',$name);
            if($prefix){
                unset($_SESSION[$prefix][$name1][$name2]);
            }else{
                unset($_SESSION[$name1][$name2]);
            }
        }else{
            if($prefix){
                unset($_SESSION[$prefix][$name]);
            }else{
                unset($_SESSION[$name]);
            }
        }
    }else{ // 设置session
        if($prefix){
            if (!isset($_SESSION[$prefix])) {
                $_SESSION[$prefix] = array();
            }
            $_SESSION[$prefix][$name]   =  $value;
        }else{
            $_SESSION[$name]  =  $value;
        }
    }
    return null;
}

/**
 * 获取输入参数 支持过滤和默认值
 * 使用方法:
 * <code>
 *  I('id',0); 获取id参数 自动判断get或者post
 *  I('post.name','','htmlspecialchars'); 获取$_POST['name']
 *  I('get.'); 获取$_GET
 * </code>
 *
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法
 * @param mixed $datas 要获取的额外数据源
 * @return mixed
 */
function I($name, $default = '', $filter = null, $datas = null) {
    if (strpos ( $name, '.' )) { // 指定参数来源
        list ( $method, $name ) = explode ( '.', $name, 2 );
    } else { // 默认为自动判断
        $method = 'param';
    }
    switch (strtolower ( $method )) {
        case 'get' :
            $input = & $_GET;
            break;
        case 'post' :
            $input = & $_POST;
            break;
        case 'put' :
            parse_str ( file_get_contents ( 'php://input' ), $input );
            break;
        case 'param' :
            switch ($_SERVER ['REQUEST_METHOD']) {
                case 'POST' :
                    $input = $_POST;
                    break;
                case 'PUT' :
                    parse_str ( file_get_contents ( 'php://input' ), $input );
                    break;
                default :
                    $input = $_GET;
            }
            break;
        case 'path' :
            $input = array ();
            if (! empty ( $_SERVER ['PATH_INFO'] )) {
                $depr = '/';
                $input = explode ( $depr, trim ( $_SERVER ['PATH_INFO'], $depr ) );
            }
            break;
        case 'request' :
            $input = & $_REQUEST;
            break;
        case 'session' :
            $input = & $_SESSION;
            break;
        case 'cookie' :
            $input = & $_COOKIE;
            break;
        case 'server' :
            $input = & $_SERVER;
            break;
        case 'globals' :
            $input = & $GLOBALS;
            break;
        case 'data' :
            $input = & $datas;
            break;
        default :
            return NULL;
    }
    if ('' == $name) { // 获取全部变量
        $data = $input;
        SEK::arrayRecursiveWalk  ( $data, 'filter_exp' );
        $filters = isset ( $filter ) ? $filter : 'htmlspecialchars';
        if ($filters) {
            if (is_string ( $filters )) {
                $filters = explode ( ',', $filters );
            }
            foreach ( $filters as $filter ) {
                $data = SEK::arrayRecursiveWalk ( $filter, $data ); // 参数过滤
            }
        }
    } elseif (isset ( $input [$name] )) { // 取值操作
        $data = $input [$name];
        is_array ( $data ) && SEK::arrayRecursiveWalk  ( $data, 'filter_exp' );
        $filters = isset ( $filter ) ? $filter : 'htmlspecialchars';
        if ($filters) {
            if (is_string ( $filters )) {
                $filters = explode ( ',', $filters );
            } elseif (is_int ( $filters )) {
                $filters = array (
                    $filters
                );
            }

            foreach ( $filters as $filter ) {
                if (function_exists ( $filter )) {

                    $data = is_array ( $data ) ? SEK::arrayRecursiveWalk ( $filter, $data ) : $filter ( $data ); // 参数过滤
                } else {
                    $data = filter_var ( $data, is_int ( $filter ) ? $filter : filter_id ( $filter ) );
                    if (false === $data) {
                        return isset ( $default ) ? $default : NULL;
                    }
                }
            }
        }
    } else { // 变量默认值
        $data = isset ( $default ) ? $default : NULL;
    }
    return $data;
}
/**
 * Function usable
 *
 * Executes a function_exists() check, and if the Suhosin PHP
 * extension is loaded - checks whether the function that is
 * checked might be disabled in there as well.
 *
 * This is useful as function_exists() will return FALSE for
 * functions disabled via the *disable_functions* php.ini
 * setting, but not for *suhosin.executor.func.blacklist* and
 * *suhosin.executor.disable_eval*. These settings will just
 * terminate script execution if a disabled function is executed.
 *
 * The above described behavior turned out to be a bug in Suhosin,
 * but even though a fix was commited for 0.9.34 on 2012-02-12,
 * that version is yet to be released. This function will therefore
 * be just temporary, but would probably be kept for a few years.
 *
 * @link	http://www.hardened-php.net/suhosin/
 * @param	string	$function_name	Function to check for
 * @return	bool	TRUE if the function exists and is safe to call,
 *			FALSE otherwise.
 */
function function_usable($function_name)
{
    static $_suhosin_func_blacklist;

    if (function_exists($function_name))
    {
        if ( ! isset($_suhosin_func_blacklist))
        {
            $_suhosin_func_blacklist = extension_loaded('suhosin')
                ? explode(',', trim(ini_get('suhosin.executor.func.blacklist')))
                : array();
        }

        return ! in_array($function_name, $_suhosin_func_blacklist, TRUE);
    }

    return FALSE;
}
/**
 * Determines if the current version of PHP is equal to or greater than the supplied value
 *
 * @param	string
 * @return	bool	TRUE if the current version is $version or higher
 */
function is_php($version)
{
    static $_is_php;
    $version = (string) $version;

    if ( ! isset($_is_php[$version]))
    {
        $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
    }

    return $_is_php[$version];
}

