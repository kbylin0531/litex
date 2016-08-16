<?php
namespace PLite\Util;
use PLite\PLiteException;
use PLite\Utils;

/**
 * Class SEK System execute kits
 * @package PLite
 */
final class SEK {

    /**
     * 显示trace页面
     * @param array $status
     * @param array $trace
     * @return true 实际返回void
     */
    public static function showTrace(array $status,array $trace){
        //吞吐率  1秒/单次执行时间
        if(count($status) > 1){
            $last  = end($status);
            $first = reset($status);            //注意先end后reset
            $stat = [
                1000*round($last[0] - $first[0], 6),
                number_format(($last[1] - $first[1]), 6)
            ];
        }else{
            $stat = [0,0];
        }
        $reqs = empty($stat[0])?'Unknown':1000*number_format(1/$stat[0],8).' req/s';

        //包含的文件数组
        $files  =  get_included_files();
        $info   =   [];
        foreach ($files as $key=>$file){
            $info[] = $file.' ( '.number_format(filesize($file)/1024,2).' KB )';
        }

        //运行时间与内存开销
        $fkey = null;
        $cmprst = ['Total' => "{$stat[0]}ms",];
        foreach($status as $key=>$val){
            if(null === $fkey){
                $fkey = $key;
                continue;
            }
            $cmprst["[$fkey --> $key] "] =
                number_format(1000 * floatval($status[$key][0] - $status[$fkey][0]),6).'ms&nbsp;&nbsp;'.
                number_format((floatval($status[$key][1] - $status[$fkey][1])/1024),2).' KB';
            $fkey = $key;
        }
        $vars = [
            'General'       => [
                'Request'   => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']).' '.$_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_METHOD'],
                'Time'      => "{$stat[0]}ms",
                'QPS'       => $reqs,//吞吐率
                'Session ID' => session_id(),
                'Obcache-Size'  => number_format((ob_get_length()/1024),2).' KB (Unexpect Trace Page!)',//不包括trace
            ],
            'Trace'         => $trace,
            'Files'         => array_merge(['Total'=>count($info)],$info),
            'Status'        => $cmprst,
            'GET'           => $_GET,
            'POST'          => $_POST,
            'REQUEST'       => $_REQUEST,
            'SERVER'        => $_SERVER,
            'ENV'           => $_ENV,
            'COOKIE'        => empty($_COOKIE)?[]:$_COOKIE,
            'SESSION'       => isset($_SESSION)?$_SESSION:['SESSION state disabled'],//session_start()之后$_SESSION数组才会被创建
            'IP'            => [
                '$_SERVER["HTTP_X_FORWARDED_FOR"]'  =>  isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:'NULL',
                '$_SERVER["HTTP_CLIENT_IP"]'  =>  isset($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:'NULL',
                '$_SERVER["REMOTE_ADDR"]'  =>  $_SERVER['REMOTE_ADDR'],
                'getenv("HTTP_X_FORWARDED_FOR")'  =>  getenv('HTTP_X_FORWARDED_FOR'),
                'getenv("HTTP_CLIENT_IP")'  =>  getenv('HTTP_CLIENT_IP'),
                'getenv("REMOTE_ADDR")'  =>  getenv('REMOTE_ADDR'),
            ],
        ];


        $nav = '';
        $win = '';
        foreach($vars as $key => $value){
            $nav .= "<span style=\"color:#000;padding-right:12px;height:30px;line-height: 30px;display:inline-block;margin-right:3px;cursor: pointer;font-weight:700\">$key</span>";
            $win .= '<div style="display:none;"><ol style="padding: 0; margin:0">';
            if(is_array($value)){
                foreach ($value as $k=>$val){
                    if(!is_string($val)) $val = var_export($val,true);
                    $win .='<li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px"><span style="color: blue">' .
                        (is_numeric($k) ? '' : $k.':</span>') .
                        "<span  style='color:black'>{$val}</span></li>";
                }
            }else{
                $win .= htmlspecialchars($value,ENT_COMPAT,'utf-8');
            }
            $win .= '</ol></div>';
        }

        echo <<< endline
    <div style="border-left:thin double #ccc;position: fixed;bottom:0;right:0;font-size:14px;width:1280px;z-index: 999999;color: #000;text-align:left;font-family:'Times New Roman';cursor:default;">
        <div id="ptt" style="display: none;background:white;margin:0;height: 512px;">
            <!-- 导航条 -->
            <div id="pttt" style="height:32px;padding: 6px 12px 0;border-bottom:1px solid #ececec;border-top:1px solid #ececec;font-size:16px">
                {$nav}
            </div>
            <!-- 详细窗口 -->
            <div id="pttc" style="overflow:auto;height:478px;padding: 0; line-height: 24px">
                {$win}
            </div>
        </div>
        <!-- 关闭按钮 -->
        <div id="ptc" style="display:none;text-align:right;height:15px;position:absolute;top:10px;right:12px;cursor: pointer;">
            <img style="vertical-align:top;" src="data:image/png;base64,R0lGODlhDwAPAJEAAAAAAAMDA////wAAACH/C1hNUCBEYXRhWE1QPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4wLWMwNjAgNjEuMTM0Nzc3LCAyMDEwLzAyLzEyLTE3OjMyOjAwICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M1IFdpbmRvd3MiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MUQxMjc1MUJCQUJDMTFFMTk0OUVGRjc3QzU4RURFNkEiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MUQxMjc1MUNCQUJDMTFFMTk0OUVGRjc3QzU4RURFNkEiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDoxRDEyNzUxOUJBQkMxMUUxOTQ5RUZGNzdDNThFREU2QSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDoxRDEyNzUxQUJBQkMxMUUxOTQ5RUZGNzdDNThFREU2QSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PgH//v38+/r5+Pf29fTz8vHw7+7t7Ovq6ejn5uXk4+Lh4N/e3dzb2tnY19bV1NPS0dDPzs3My8rJyMfGxcTDwsHAv769vLu6ubi3trW0s7KxsK+urayrqqmop6alpKOioaCfnp2cm5qZmJeWlZSTkpGQj46NjIuKiYiHhoWEg4KBgH9+fXx7enl4d3Z1dHNycXBvbm1sa2ppaGdmZWRjYmFgX15dXFtaWVhXVlVUU1JRUE9OTUxLSklIR0ZFRENCQUA/Pj08Ozo5ODc2NTQzMjEwLy4tLCsqKSgnJiUkIyIhIB8eHRwbGhkYFxYVFBMSERAPDg0MCwoJCAcGBQQDAgEAACH5BAAAAAAALAAAAAAPAA8AAAIdjI6JZqotoJPR1fnsgRR3C2jZl3Ai9aWZZooV+RQAOw==" />
        </div> 
    </div>
    <!-- 开启按钮 -->
    <div id="pto" style="height:30px;float:right;text-align: right;overflow:hidden;position:fixed;bottom:0;right:0;color:#000;line-height:30px;cursor:pointer;">
        <div style="background:#232323;color:#FFF;padding:0 6px;float:right;line-height:30px;font-size:14px"></div>
    <img style="width: 30px" title="ShowPageTrace" src="data:image/png;base64,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" />
    </div>
    <script type="text/javascript">
    (function(){
        var tab_tit  = document.getElementById('pttt').getElementsByTagName('span');
        var tab_cont = document.getElementById('pttc').getElementsByTagName('div');
        var open     = document.getElementById('pto');
        var close    = document.getElementById('ptc');
        var trace    = document.getElementById('ptt');
        var cookie   = document.cookie.match(/_spt=(\d\|\d)/);
        var history  = (cookie && typeof cookie[1] != 'undefined' && cookie[1].split('|')) || [0,0];
        open.onclick = function(){
            trace.style.display = '';
            close.style.display = '';
            history[0] = 1;
            document.cookie = '_spt='+history.join('|');
        };
        close.onclick = function(){
            trace.style.display = 'none';
            open.style.display = 'block';
            history[0] = 0;
            document.cookie = '_spt='+history.join('|');
        };
        for(var i = 0; i < tab_tit.length; i++){
            tab_tit[i].onclick = (function(i){
                return function(){
                    for(var j = 0; j < tab_cont.length; j++){
                        tab_cont[j].style.display = 'none';
                        tab_tit[j].style.color = '#999';
                    }
                    tab_cont[i].style.display = 'block';
                    tab_tit[i].style.color = '#000';
                    history[1] = i;
                    document.cookie = '_spt='+history.join('|')
                }
            })(i);
        }
        parseInt(history[0]) && open.click();
        (tab_tit[history[1]] || tab_tit[0]).click();
    })();
    </script>
endline;
        return true;
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
     * @param \PDO $pdo PDO对象或者继承类的实例
     * @return null|string null表示未发生错误,string表示序列化的错误信息
     */
    public static function fetchPdoError(\PDO $pdo){
        $pdoError = $pdo->errorInfo();
        return null !== $pdoError[0]? "PDO Error:{$pdoError[0]} >>> [{$pdoError[1]}]:[{$pdoError[2]}]":null;// PDO错误未被设置或者错误未发生,0位的值为null
    }

    /**
     * 获取PDOStatemnent对象上查询时发生的错误
     * 错误代号参照ANSI CODE ps: https://docs.oracle.com/cd/F49540_01/DOC/server.815/a58231/appd.htm
     * @param \PDOStatement $statement 发生了错误的PDOStatement对象
     * @return string|null 错误未发生时返回null
     */
    public static function fetchPdoStatementError(\PDOStatement $statement){
        $stmtError = $statement->errorInfo();
        return 0 !== intval($stmtError[0])?"Error Code:[{$stmtError[0]}]::[{$stmtError[1]}]:[{$stmtError[2]}]":null;//代号为0时表示错误未发生
    }

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
        [
            SEK::parseLocation('ModuleA/ModuleB@ControllerName/ActionName:themeName'),
            SEK::parseLocation('ModuleA/ModuleB@ControllerName/ActionName'),
            SEK::parseLocation('ControllerName/ActionName:themeName'),
            SEK::parseLocation('ControllerName/ActionName'),
            SEK::parseLocation('ActionName'),
            SEK::parseLocation('ActionName:themeName'),
        ]
     * @param string $location 模板位置
     * @return array
     */
    public static function parseLocation($location){
        //资源解析结果：元素一表示解析结果
        $result = [
            't' => null,
            'm' => null,
            'c' => null,
            'a' => null,
        ];

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
        for ($i = 0, $len = count($tokens); $i < $len; $i++) {
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
                        for($k = $i+1; $k < $len; $k++) {
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
        is_array($data) or $data = [$data];
        ksort($data);
        return sha1(http_build_query($data));
    }
}