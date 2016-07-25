<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 9:53 PM
 */

namespace PLite\Core;
use PLite\Lite;
use PLite\core\configger\ConfiggerInterface;

class Configger extends Lite{

    const CONF_NAME = 'config';
    const CONF_CONVENTION = [
        'PRIOR_INDEX' => 0,
        'DRIVER_CLASS_LIST' => [
            'PLite\\Core\\Cofigger\\File',
        ],
        'DRIVER_CONFIG_LIST' => [
            [
                'CUSTOM_CONF_PATH'  => PATH_RUNTIME.'Config/', // 用户自定义配置目录
            ]
        ],
        'CONFIG_CACHE_LIST'     => [],
        'CONFIG_CACHE_EXPIRE'   => 0,//0表示永不过期
    ];

    /**
     * 配置缓存
     * @var array
     */
    protected static $confcache = [];
    /**
     * @var ConfiggerInterface
     */
    protected $_driver = null;

    /**
     * 读取所有全局配置
     * @param string|array $list 配置列表,可以是数组，也可以是都好分隔的字符串
     * @return array 返回全部配置，配置名称为键
     */
    public static function readAllGlobal($list=null){
        if(null === $list){
            $config = self::getConfig();
            $list = $config['CONFIG_CACHE_LIST'];
        }
        if(is_string($list)) $list = explode(',',$list);
        //无法读取驱动内部的缓存或者缓存不存在  => 重新读取配置并生成缓存
        foreach($list as $item){
            self::$confcache[$item] = self::loadConfig($item);
        }
        return self::$confcache;
    }

    /**
     * 创建配置系统全局缓存
     * @param array $cachedata 缓存的数据
     * @param int $expire 缓存时间
     * @return bool 创建缓存是否成功
     * @throws Exception
     */
    public static function setGlobalCache(array $cachedata=null, $expire=null){
        if(!isset($cachedata,$expire)){
            self::checkInit(true);
            $config = self::getConfig();
            if(!isset($config['CONFIG_CACHE_LIST'],$config['CONFIG_CACHE_EXPIRE'])){
                //检验获取的配置项是否合理
                Exception::throwing('Config item of "CONFIG_CACHE_LIST" or "CONFIG_CACHE_EXPIRE" not found!');
            }
            null === $cachedata and $cachedata = self::readAllGlobal($config['CONFIG_CACHE_LIST']);
            null === $expire  and $expire = $config['CONFIG_CACHE_EXPIRE'];
        }
        return Cacher::getInstance(0)->set(self::CONF_NAME,$cachedata,$expire);
    }

    /**
     * 加载配置缓存
     * @return array|null
     */
    public static function getGlobalCache(){
        return Cacher::getInstance(0)->get(self::CONF_NAME);
    }

    /**
     * 获取配置信息
     * 示例：
     *  database.DB_CONNECT.0.type
     * 除了第一段外要注意大小写
     * @param string|null|array $items 配置项
     * @param mixed|null $replacement 当指定的配置项不存在时,仅仅在获取第二段开始的部分时有效
     * @return mixed 返回配置信息数组
     * @throws Exception
     */
    public function getGlobal($items=null,$replacement=null){
        static $globals = null;
        if(null === $globals){
            $globals = self::getGlobalCache();
            if(null === $globals){
                $globals = self::readAllGlobal();
                self::setGlobalCache($globals);
            }
        }
        null === $globals and Exception::throwing('Failed to get global config!');

        $configes = null;//配置分段，如果未分段则保持null的值
        //检查参数并设置分段
        if(null === $items){
            //默认参数时返回全部
            return $globals;
        }elseif(is_string($items)){
            $configes = false === strpos($items,'.')?[$items]:explode('.',$items);
        }elseif(is_array($items)){
            $configes = $items;
        }

        //获取第一段的配置
        $rtn = $globals[array_shift($configes)];

        //如果为true表示是经过分段的
        if($configes){
            foreach($configes as $val){
                if(isset($rtn[$val])){
                    $rtn = $rtn[$val];
                }else{
                    return $replacement;
                }
            }
        }
        return $rtn;
    }

    /**
     * 写入自定义配置项
     * @param string $itemname 自定义配置项名称
     * @param array $config 配置数组
     * @param int $expire 以秒计算的缓存时间
     * @return bool 写入成功与否
     */
    public function writeCustom($itemname,array $config,$expire=null){
        return $this->_driver->write($itemname,$config,$expire);
    }

    /**
     * 读取自定义配置
     * @param string $itemname 自定义配置项名称
     * @param mixed|null $replacement 当指定的配置项不存在的时候的替代值
     * @return array|mixed 配置项存在的情况下返回array，否则返回参数$replacement的值
     */
    public function readCustom($itemname,$replacement=null){
        $result = $this->_driver->read($itemname);
        return null === $result?$replacement:$result;
    }


    /**
     * 设置临时配置项
     * 下次请求时临时的配置将被清空
     * <code>
     *  UDK::dump(Configer::get());
     *  Configer::set('custom.NAME.VALUE',true);
     *  UDK::dump(Configer::get());
     * </code>
     * @param string $items 配置项名称，同get方法，可以是分段的设置
     * @param mixed $value 配置项的值
     * @return bool
     */
    public function set($items,$value){
        //检查配置缓存

        $configes = null;//配置分段，如果未分段则保持null的值
        if(false !== strpos($items,'.')){
            $configes = explode('.',$items);
            $items = array_shift($configes);
        }
        if(!isset(self::$confcache[$items])){//不存在该配置
            if(!is_array(self::loadConfig($items))){
                return false;//不存在该配置，设置失败
            }
        }

        $confvars = &self::$confcache[$items];
        if($configes){
            foreach($configes as $item){
                if(!isset($confvars[$item])){
                    $confvars[$item] = [];
                }
                $confvars = &$confvars[$item];
            }
        }
        $confvars = $value;
        return true;
    }

    /**
     * 配置类型
     * 值使用字符串而不是效率更高的数字是处于可以直接匹配后缀名的考虑
     */
    const TYPE_PHP     = 'php';
    const TYPE_INI     = 'ini';
    const TYPE_YAML    = 'yaml';
    const TYPE_XML     = 'xml';
    const TYPE_JSON    = 'json';

    /**
     * 加载配置文件
     * @param string $path 配置文件的路径
     * @param string|null $type 配置文件的类型,参数为null时根据文件名称后缀自动获取
     * @param callable $parser 配置解析方法 有些格式需要用户自己解析
     * @return array
     */
    public static function load($path,$type=null,callable $parser=null){
        isset($type) or $type = pathinfo($path, PATHINFO_EXTENSION);
        switch ($type) {
            case self::TYPE_PHP:
                return include $path;
            case self::TYPE_INI:
                return parse_ini_file($path);
            case self::TYPE_YAML:
                return yaml_parse_file($path);
            case self::TYPE_XML:
                return (array)simplexml_load_file($path);
            case self::TYPE_JSON:
                return json_decode(file_get_contents($path), true);
            default:
                return $parser?$parser($path):Exception::throwing('无法解析配置文件');
        }
    }

}