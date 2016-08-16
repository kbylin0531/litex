<?php
namespace PLite\Core;
use PLite\AutoConfig;
use PLite\Debugger;
use PLite\Util\SEK;
use PLite\Utils;

/**
 * Class Router
 * @package PLite\Core
 */
class Router {

    use AutoConfig;

    const CONF_NAME = 'route';
    const CONF_CONVENTION = [
        //------------------------
        //For URL route
        //------------------------
        'URI_ROUTE_ON'          => true,//总开关,是否对URI地址进行路由
        'STATIC_ROUTE_ON'       => true,
        //静态路由规则
        'STATIC_ROUTE_RULES'    => [],
        'WILDCARD_ROUTE_ON'     => false,
        //通配符路由规则,具体参考CodeIgniter
        'WILDCARD_ROUTE_RULES'  => [],
        'REGULAR_ROUTE_ON'      => true,
        //正则表达式 规则
        'REGULAR_ROUTE_RULES'   => [],

        //------------------------
        //For URL parser
        //------------------------
        //API模式，直接使用$_GET
        'API_MODE_ON'   => false,
        //API模式 对应的$_GET变量名称
        'API_MODULES_VARIABLE'      => '_m',//该模式下使用到多层模块时涉及'MM_BRIDGE'的配置
        'API_CONTROLLER_VARIABLE'   => '_c',
        'API_ACTION_VARIABLE'       => '_a',

        //普通模式
        'MASQUERADE_TAIL'   => '.html',
        //重写模式下 消除的部分，对应.htaccess文件下
        'REWRITE_HIDDEN'      => '/index.php',
        'MM_BRIDGE'     => '/',//模块与模块之间的连接
        'MC_BRIDGE'     => '/',
        'CA_BRIDGE'     => '/',
        //*** 必须保证操作与控制器之间的符号将是$_SERVER['PATH_INFO']字符串中第一个出现的,为了更好地显示URL，参数一般通过POST传递
        //特别注意的是若使用了问号，则后面的字符串将被认为是请求参数
        'AP_BRIDGE'     => '-',
        'PP_BRIDGE'     => '-',//参数与参数之间的连接桥
        'PKV_BRIDGE'    => '-',//参数的键值对之前的连接桥

        //是否开启域名部署（包括子域名部署）
        'DOMAIN_DEPLOY_ON'  => true,
        //子域名部署模式下 的 完整域名
        'DOMAIN_NAME'       =>'linzhv.com',
        //是否将子域名段和模块进行映射
        'SUBDOMAIN_AUTO_MAPPING_ON' => false,
        //子域名部署规则
        //注意参与array_flip()函数,键值互换
        'SUBDOMAIN_MAPPINIG' => [],

        //使用的协议名称
        'SERVER_PROTOCOL' => 'http',
        //使用的端口号，默认为80时会显示为隐藏
        'SERVER_PORT'   => 80,
    ];

    /**
     * 返回解析结果
     * @var array
     */
    private static $result = [
        'm' => null,
        'c' => null,
        'a' => null,
        'p' => null,
    ];

    /**
     * 解析路由规则
     * @param string|null $url 请求路径
     * @return array|null|string
     */
    public static function parseRoute($url=null){
        $url or $url = $_SERVER['REQUEST_URI'];

        strpos($url,'//') !== false and $url = str_replace('//','/',$url);
        $_config = self::getConfig();
        //静态路由
        if($_config['STATIC_ROUTE_ON'] and $_config['STATIC_ROUTE_RULES']){
            if(isset($_config['STATIC_ROUTE_RULES'][$url])){
                return $_config['STATIC_ROUTE_RULES'][$url];
            }
        }
        //规则路由
        if($_config['WILDCARD_ROUTE_ON'] and $_config['WILDCARD_ROUTE_RULES']){
            foreach($_config['WILDCARD_ROUTE_RULES'] as $pattern => $rule){
                // Convert wildcards to RegEx（from CI）
                //any对应非/的任何字符,num对应数字 ,id表示identify，即标识符
                $pattern = str_replace(['[any]', '[num]', '[id]'], ['([^/]+)', '([0-9]+)', '(\w[\w_\d]?)'], $pattern);//$pattern = preg_replace('/\[.+?\]/','([^/\[\]]+)',$pattern);//非贪婪匹配
                $rst = self::_matchRegular($pattern,$rule, $url);
                if(null !== $rst) return $rst;
            }
        }
        //正则路由
        if($_config['REGULAR_ROUTE_ON'] and $_config['REGULAR_ROUTE_RULES']){
            foreach($_config['REGULAR_ROUTE_RULES'] as $pattern => $rule){
                $rst = self::_matchRegular($pattern,$rule, $url);
                if(null !== $rst) return $rst;
            }
        }
        return null;
    }

    /**
     * 将参数序列装换成参数数组，应用Router模块的配置
     * @param string $params 参数字符串
     * @param string $ppb
     * @param string $pkvb
     * @return array
     */
    public static function fetchKeyValuePair($params,$ppb='/',$pkvb='/'){//解析字符串成数组
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
     * 使用正则表达式匹配uri
     * @param string $pattern 路由规则
     * @param array|string|callable $rule 路由导向结果
     * @param string $uri 传递进来的URL字符串
     * @return array|string|null
     */
    private static function _matchRegular($pattern, $rule, $uri){
        $result = null;
        // do the RegEx match? use '#' to ignore '/'
        if (preg_match('#^'.$pattern.'$#', $uri, $matches)) {
            if(is_array($rule)){
                $len = count($matches);
                for($i = 1; $i < $len; $i++){
                    $key = '$'.$i;
                    if(isset($rule['$'.$i])){
                        $v = (string)$rule[$key];
                        if(strpos($v,'.')){
                            $a = explode('.',$v);
                            empty($rule[$a[0]]) and $rule[$a[0]] = [];
                            $rule[$a[0]][$a[1]] = $matches[$i];
                        }else{
                            $rule[$v] = $matches[$i];
                        }
                    }else{
                        empty($rule['o']) and $rule['o'] = [];
                        $rule['o'][] = $matches[$i];
                    }
                    unset($rule[$key]);
                }
                $result = $rule;
            }elseif(is_string($rule)){
                $result = preg_replace('#^'.$pattern.'$#', $rule, $uri);//参数一代表的正则表达式从参数三的字符串中寻找匹配并替换到参数二代表的字符串中
            }elseif(is_callable($rule)){
                array_shift($matches);
                // Execute the callback using the values in matches as its parameters.
                $result = call_user_func_array($rule, $matches);//参数二是完整的匹配
                if($result === true){
                    //返回true表示直接完成
                    exit();
                }elseif(!is_string($result) and !is_array($result)){
                    //要求结果必须返回string或者数组
                    return null;
                }
            }
        }
        return $result;
    }

    /**
     * 解析URI
     * @param string $uri 请求的URI
     * @param string $hostname
     * @return array
     */
    public static function parseURL($uri=null,$hostname=null){
        $_config = self::getConfig();
        //API模式下
        if($_config['API_MODE_ON']){
            self::parseInAPI();
        }else{
            $uri or $uri = Utils::pathInfo(true);
            //解析域名部署
            if($_config['DOMAIN_DEPLOY_ON']){
                $hostname or $hostname = $_SERVER['SERVER_NAME'];
                self::parseHostname($hostname);//如果绑定了模块，之后的解析将无法指定模块
            }
            //检查、寻找和解析URI路由 'URI_ROUTE_ON'
            //普通模式下解析URI地址
            self::parseInCommon($uri);
        }
        return self::$result;
    }

    /**
     * 按照API模式进行解析(都组最快)
     * 保持原样
     * @return void
     */
    private static function parseInAPI(){
        DEBUG_MODE_ON and Debugger::status('fetchurl_in_topspeed_begin');
        $_config = self::getConfig();
        $vars = [
            $_config['API_MODULES_VARIABLE'],
            $_config['API_CONTROLLER_VARIABLE'],
            $_config['API_ACTION_VARIABLE'],
        ];
        //获取模块名称
        isset($_GET[$vars[0]]) and self::$result['m'] = $_GET[$vars[0]];
        //获取控制器名称
        isset($_GET[$vars[1]]) and self::$result['c'] = $_GET[$vars[1]];
        //获取操作名称，类方法不区分大小写
        isset($_GET[$vars[2]]) and self::$result['a'] = $_GET[$vars[2]];
        //参数为剩余的变量
        unset($_GET[$vars[0]],$_GET[$vars[1]],$_GET[$vars[2]]);
        self::$result['p'] = $_GET;

        DEBUG_MODE_ON and Debugger::status('fetchurl_in_topspeed_end');
    }

    /**
     * 按照普通模式进行URI解析
     * @param string $uri 待解析的URI
     * @return void
     */
    private static function parseInCommon($uri){
        DEBUG_MODE_ON and Debugger::status('parseurl_in_common_begin');
        $_config = self::getConfig();
        $bridges = [
            'mm'  => $_config['MM_BRIDGE'],
            'mc'  => $_config['MC_BRIDGE'],
            'ca'  => $_config['CA_BRIDGE'],
            'ap'  => $_config['AP_BRIDGE'],
            'pp'  => $_config['PP_BRIDGE'],
            'pkv'  => $_config['PKV_BRIDGE'],
        ];
        self::stripMasqueradeTail($uri);

        //-- 解析PATHINFO --//
        //截取参数段param与定位段local
        $papos          = strpos($uri,$bridges['ap']);
        $mcapart = null;
        $pparts = '';
        if(false === $papos){
            $mcapart  = trim($uri,'/');//不存在参数则认定PATH_INFO全部是MCA的部分，否则得到结果substr($uri,0,0)即空字符串
        }else{
            $mcapart  = trim(substr($uri,0,$papos),'/');
            $pparts   = substr($uri,$papos + strlen($bridges['ap']));
        }

        //-- 解析MCA部分 --//
        //逆向检查CA是否存在衔接
        $mcaparsed = self::parseMCA($mcapart,$bridges);
        self::$result = array_merge(self::$result,$mcaparsed);

        //-- 解析参数部分 --//


        self::$result['p'] = self::fetchKeyValuePair($pparts,$bridges['pp'],$bridges['pkv']);
        DEBUG_MODE_ON and Debugger::status('parseurl_in_common_end');
    }

    /**
     * 解析主机名
     * 如果找到了对应的主机名称，则绑定到对应的模块
     * @param string $hostname 访问的主机名
     * @return bool 返回是否绑定了模块
     */
    private static function parseHostname($hostname){
        $_config = self::getConfig();
        $subdomain = strstr($hostname,$_config['DOMAIN_NAME'],true);
        if($subdomain !== false){
            $subdomain = rtrim($subdomain,'.');
            if(isset($_config['SUBDOMAIN_MAPPINIG'][$subdomain])){
                self::$result['m'] = $_config['SUBDOMAIN_MAPPINIG'][$subdomain];
            }elseif($_config['SUBDOMAIN_AUTO_MAPPING_ON']){
                if(false !== strpos($subdomain,'.')){
                    self::$result['m'] = array_map(function ($val) {
                        return Utils::styleStr($val,1);
                    }, explode('.',$subdomain));
                }else{
                    self::$result['m'] = ucfirst($subdomain);
                }
            }
        }
        return false;
    }

    /**
     * 解析"模块、控制器、操作"
     * @param $mcapart
     * @param $bridges
     * @return array
     */
    private static function parseMCA($mcapart,$bridges){
        $parsed = ['m'=>null,'c'=>null,'a'=>null];
        $capos = strrpos($mcapart,$bridges['ca']);
        if(false === $capos){
            //找不到控制器与操作之间分隔符（一定不存在控制器）
            //先判断位置部分是否为空字符串来决定是否有操作名称
            if(strlen($mcapart)){
                //位置字段全部是字符串的部分
                $parsed['a'] = $mcapart;
            }else{
                //没有操作部分，MCA全部使用默认的
            }
        }else{
            //apos+CA_BRIDGE 后面的部分全部算作action
            $parsed['a'] = substr($mcapart,$capos+strlen($bridges['ca']));

            //CA存在衔接符 则说明一定存在控制器
            $mcalen = strlen($mcapart);
            $mcpart = substr($mcapart,0,$capos-$mcalen);//去除了action的部分

            if(strlen($mcapart)){
                $mcpos = strrpos($mcpart,$bridges['mc']);
                if(false === $mcpos){
                    //不存在模块
                    if(strlen($mcpart)){
                        //全部是控制器的部分
                        $parsed['c'] = $mcpart;
                    }else{
                        //没有控制器部分，则使用默认的
                    }
                }else{
                    //截取控制器的部分
                    $parsed['c']   = substr($mcpart,$mcpos+strlen($bridges['mc']));

                    //既然存在MC衔接符 说明一定存在模块
                    $mpart = substr($mcpart,0,$mcpos-strlen($mcpart));//以下的全是模块部分的字符串
                    if(strlen($mpart)){
                        if(false === strpos($mpart,$bridges['mm'])){
                            $parsed['m'] = $mpart;
                        }else{
                            $parsed['m'] = explode($bridges['mm'],$mpart);
                        }
                    }else{
                        //一般存在衔接符的情况下不为空,但也考虑下特殊情况
                    }
                }
            }else{
                //一般存在衔接符的情况下不为空,但也考虑下特殊情况
            }
        }
        return $parsed;
    }
    /**
     * 删除伪装的url后缀
     * @param string|array $uri 需要去除尾巴的字符串或者字符串数组（当数组中存在其他元素时忽略）
     * @return void
     */
    private static function stripMasqueradeTail(&$uri){
        $uri = trim($uri);
        $_config = self::getConfig();
        $position = stripos($uri,$_config['MASQUERADE_TAIL']);
        //$position === false 表示 不存在伪装的后缀或者相关带嫌疑的url部分

        if(false !== $position and strlen($uri) === ($position + strlen($_config['MASQUERADE_TAIL'])) ){
            //伪装的后缀存在且只出现在最后的位置时
            $uri = substr($uri,0,$position);
        }
    }

    //--------------------------------------------------------------------------------------------


    /**
     * 创建URL
     * @param string|array $modules 模块序列
     * @param string $contler 控制器名称
     * @param string $action 操作名称
     * @param array|null $params 参数
     * @return string 可以访问的URI
     */
    public static function create($modules=null,$contler=null,$action=null,array $params=null){
        $modules or $modules = REQUEST_MODULE;
        $contler or $contler = REQUEST_CONTROLLER;
        $action or $action = REQUEST_ACTION;

        $_config = self::getConfig();
        if($_config['API_MODE_ON']){
            $uri = self::getBasicUrl().self::createInAPI($modules,$contler,$action,$params);
        }else{
            //反向域名地址
            $moduleUsed = false;
            if($_config['DOMAIN_DEPLOY_ON']){
                $hostname = self::createHostname($modules,$moduleUsed);//如果绑定了模块，之后的解析将无法指定模块
            }else{
                $hostname = $_SERVER['SERVER_NAME'];
            }
//            \PLite\dumpout($modules);
            $uri = self::getBasicUrl(null,$hostname).'/'.
                self::createInCommon($moduleUsed?null:$modules,$contler,$action,$params);
        }
        return $uri;
    }


    /**
     * 按照API模式创建URL地址
     * @param array|string $modules
     * @param string $contler
     * @param string $action
     * @param array|null $params
     * @return string
     */
    public static function createInAPI($modules,$contler,$action,array $params=null){
        $_config = self::getConfig();
        is_array($modules) and $modules = SEK::toModulesString($modules,$_config['MM_BRIDGE']);
        empty($params) and $params = [];
        return '?'.http_build_query(array_merge($params,array(
            $_config['API_MODULES_VARIABLE']       => $modules,
            $_config['API_CONTROLLER_VARIABLE']    => $contler,
            $_config['API_ACTION_VARIABLE']        => $action,
        )));
    }

    /**
     * 获取主机名称
     * @param string|array $modules
     * @param bool $flag
     * @return null|string
     */
    public static function createHostname($modules,&$flag){
        $_config = self::getConfig();
        //模块标识符
        $mid = is_array($modules)?SEK::toModulesString($modules,$_config['MM_BRIDGE']):$modules;
        $rmapping = array_flip($_config['SUBDOMAIN_MAPPINIG']);
        if(isset($rmapping[$mid])){
            $hostname = $rmapping[$mid];
        }elseif($_config['SUBDOMAIN_AUTO_MAPPING_ON']){
            if(is_string($modules)){
                $modules = strtolower(str_replace('/','.',$modules));
            }else{
                $modules = implode('.',$modules);
            }
            $hostname = $modules;
        }else{
            return $_SERVER['SERVER_NAME'];
        }
        $flag = true;//标注模块信息已经注入到域名中了
        return $hostname.'.'.$_config['DOMAIN_NAME'];
    }

    /**
     * @param null $modules
     * @param null $contler
     * @param null $action
     * @param array|null $params
     * @return string
     */
    public static function createInCommon($modules=null,$contler=null,$action=null,array $params=null){
        $uri = '';
        $_config = self::getConfig();
        $modules and $uri .= is_array($modules)?implode($_config['MM_BRIDGE'],$modules):$modules;
//        \PLite\dumpout($modules,$uri);
        $contler and $uri .= ''===$uri?$contler:$_config['MC_BRIDGE'].$contler;
        $action and $uri .= $_config['CA_BRIDGE'].$action;
        $params and $uri .= $_config['AP_BRIDGE'].SEK::toParametersString($params,$_config['PP_BRIDGE'],$_config['PKV_BRIDGE']);
        return $uri;
    }

    /**
     * 获取基础URI
     * 当端口号为80时隐藏之
     * @param string|null $protocol 协议
     * @param string|null $hostname 主机名称
     * @param bool $full 是否取完整
     * @return string 返回URI的基础部分
     */
    public static function getBasicUrl($protocol=null,$hostname=null,$full=false){
        static $uri = [];
        $key = md5($protocol . '' . $hostname);
        if(!isset($uri[$key])){
            $uri[$key] = $full?
                (isset($protocol)?$protocol:$_SERVER['REQUEST_SCHEME']) .'://'. (isset($hostname)?$hostname:$_SERVER['SERVER_NAME']).
                (80 == $_SERVER['SERVER_PORT']?'':':'.$_SERVER['SERVER_PORT']).$_SERVER['SCRIPT_NAME']:
                $_SERVER['SCRIPT_NAME'];
        }
        return $uri[$key];
    }

    /**
     * 判断是否是重定向链接
     * 判断依据：
     *  ①以http或者https开头
     *  ②以'/'开头的字符串
     * @param string $link 链接地址
     * @return bool
     */
    public static function isRedirectLink($link){
        $link = trim($link);
        return (0 === strpos($link, 'http')) or (0 === strpos($link,'/')) or (0 === strpos($link, 'https'));
    }

    /**
     * 重定向
     * @param string $url 重定向地址
     * @param int $time
     * @param string $message
     * @return void
     */
    public static function redirect($url,$time=0,$message=''){
        //多行URL地址支持
        $url = str_replace(['\n','\r'], '', $url);
        $message or $message = "系统将在{$time}秒之后自动跳转到{$url}！";

        if(headers_sent()){//检查头部是否已经发送
            exit("<meta http-equiv='Refresh' content='{$time};URL={$url}'>{$message}");
        }else{
            if(0 === $time){
                header('Location: ' . $url);
            }else{
                header("refresh:{$time};url={$url}");
                exit($message);
            }
        }
    }

    /**
     * $url规则如：
     *  .../Ma/Mb/Cc/Ad
     * 依次从后往前解析出操作，控制器，模块(如果存在模块将被认定为完整的模块路径)
     * @param string $url 快速创建的URL字符串
     * @param array $params GET参数数组
     * @return string
     */
    public static function url($url=null,array $params=[]){
        //解析参数中的$url
        empty($params) and $params = [];
        if(!$url){
            return self::create(null,null,null,$params);
        }
        $hashpos = strpos($url,'#');
        if($hashpos){
            $hash = substr($url,$hashpos+1);
            $url = substr($url,0,$hashpos);
        }else{
            $hash = '';
        }
        $parts = @explode('/',trim($url,'/'));

//        \PLite\dumpout($hash,$url,$parts);
        //调用URLHelper创建URL
        $action  = array_pop($parts);
        $ctler   = $action?array_pop($parts):null;
        $modules = $ctler?$parts:null;
        $url = self::create($modules,$ctler,$action,$params);
//        \PLite\dumpout($modules,$ctler,$action,$url);
        if($hash) $url .= '#'.$hash;
        return $url;
    }
}