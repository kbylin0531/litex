<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 9:50 PM
 */

namespace PLite\Core\Cache;

/**
 * 文件类型缓存类
 * @author    liu21st <liu21st@gmail.com>
 */
class File implements CacheInterface {

    protected $options = [
        'expire'        => 0,
        'cache_subdir'  => false,
        'path_level'    => 1,
        'prefix'        => '',
        'length'        => 0,
        'path'          => PATH_RUNTIME.'Cache/File/',
        'data_compress' => false,
    ];

    /**
     * 架构函数
     * @param array $options
     * @access public
     */
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (substr($this->options['path'], -1) != '/') {
            $this->options['path'] .= '/';
        }
        $this->init();
    }

    /**
     * 检查文件系统是否可用
     * @return bool
     */
    public function available(){
        file_exists($this->options['path']) or mkdir($this->options['path'],0777,true);
        return is_writeable($this->options['path']);
    }

    /**
     * 初始化检查
     * @access private
     * @return boolean
     */
    private function init(){
        // 创建项目缓存目录
        if (!is_dir($this->options['path'])) {
            if (!mkdir($this->options['path'], 0755, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 取得变量的存储文件名
     * @access private
     * @param string $name 缓存变量名
     * @return string
     */
    private function filename($name)
    {
        $name = md5($name);
        if ($this->options['cache_subdir']) {
            // 使用子目录
            $dir = '';
            $len = $this->options['path_level'];
            for ($i = 0; $i < $len; $i++) {
                $dir .= $name{$i} . '/';
            }
            if (!is_dir($this->options['path'] . $dir)) {
                mkdir($this->options['path'] . $dir, 0755, true);
            }
            $filename = $dir . $this->options['prefix'] . $name . '.php';
        } else {
            $filename = $this->options['prefix'] . $name . '.php';
        }
        return $this->options['path'] . $filename;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed|null 缓存不存在时返回null
     */
    public function get($name){
        $filename = $this->filename($name);
        if (!is_file($filename)) {
            return null;//缓存文件不存在
        }
        $content = file_get_contents($filename);
        if (false !== $content) {
            $expire = (int) substr($content, 8, 12);
            if (0 != $expire && time() > filemtime($filename) + $expire) {
                //缓存过期删除缓存文件
                unlink($filename);
                return null;//缓存过期
            }
            $content = substr($content, 20, -3);
            if ($this->options['data_compress'] && function_exists('gzcompress')) {
                //启用数据压缩
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            return $content===false? null:$content;
        } else {
            return null;
        }
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param int $expire  有效时间 0为永久
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $filename = $this->filename($name);
        $data     = serialize($value);
        if ($this->options['data_compress'] && function_exists('gzcompress')) {
            //数据压缩
            $data = gzcompress($data, 3);
        }
        $data   = "<?php\n//" . sprintf('%012d', $expire) . $data . "\n?>";
        $result = file_put_contents($filename, $data);
        if ($result) {
            if ($this->options['length'] > 0) {
                // 记录缓存队列
                $queue_file = dirname($filename) . '/__info__.php';
                $queue      = unserialize(file_get_contents($queue_file));
                if (!$queue) {
                    $queue = [];
                }
                if (false === array_search($name, $queue)) {
                    array_push($queue, $name);
                }

                if (count($queue) > $this->options['length']) {
                    // 出列
                    $key = array_shift($queue);
                    // 删除缓存
                    unlink($this->filename($key));
                }
                file_put_contents($queue_file, serialize($queue));
            }
            clearstatcache();
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool 缓存文件不存在时执行删除操作返回false，文件存在时的返回值是unlink的返回值
     */
    public function delete($name){
        $name = $this->filename($name);
        return is_file($name)? unlink($name) : false;
    }

    /**
     * 清除缓存
     * @access public
     * @param string $name 缓存变量名,效果等同于rm方法
     * @return int 返回成功删除的缓存数目，否则返回false
     */
    public function clean($name=null){
        if(isset($name)) return $this->delete($name) === true?1:0;

//        $path = $this->options['temp'];//修正为以下
        $path = $this->options['path'];
        if ($dir = opendir($path)) {
            $c = 0;
            while ($file = readdir($dir)) {
                if(!is_dir($file)){
                    unlink($path . $file);//不删除目录，只针对文件进行删除
                    ++ $c;
                }
            }
            closedir($dir);
            return $c;
        }else{
            return 0;
        }
    }
}