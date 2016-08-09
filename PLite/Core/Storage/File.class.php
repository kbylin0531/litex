<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 9:57 PM
 */

namespace PLite\Core\Storage;
use PLite\Core\Storage;
use PLite\Util\SEK;

/**
 * Class File 文件系统驱动类基类
 * @package PLite\Core\Storage
 */
class File implements StorageInterface {

    /**
     * 惯例配置
     * @var array
     */
    private $convention = [
        'READ_LIMIT_ON'     => true,
        'WRITE_LIMIT_ON'    => true,
        'READABLE_SCOPE'    => PATH_BASE,
        'WRITABLE_SCOPE'    => PATH_RUNTIME,

        'READOUT_MAX_SIZE'  => 2097152,//2M限制,对于文本文件已经足够
        'OS_ECNODE'         => 'GB2312', // 文件系统编码格式,如果是英文环境下可能是UTF-8,GBK,GB2312以外的编码格式
        'READOUT_ENCODE'    => 'UTF-8', // 读出时转化的成的编码格式
        'WRITEIN_ENCODE'    => 'UTF-8', // 写入时转化的编码格式
    ];

    /**
     * File constructor.
     * @param array $config
     */
    public function __construct(array $config){
        $this->convention = array_merge($this->convention,$config);
    }

    /**
     * 转换成php处理文件系统时所用的编码
     * 即UTF-8转GB2312
     * @param string $str 待转化的字符串
     * @param string $strencode 该字符串的编码格式
     * @return string|false 转化失败返回false
     */
    public function toSystemEncode($str,$strencode='UTF-8'){
        return iconv($strencode,$this->convention['OS_ECNODE'].'//IGNORE',$str);
    }

    /**
     * 转换成程序使用的编码
     * 即GB2312转UTF-8
     * @param string $str 待转换的字符串
     * @return string|false 转化失败返回false
     */
    public function toProgramEncode($str){
        return iconv($this->convention['OS_ECNODE'],'UTF-8//IGNORE',$str);
    }

    /**
     * 检查目标目录是否可读取 并且对目标字符串进行修正处理
     *
     * $accesspath代表的是可以访问的目录
     * $path 表示正在访问的文件或者目录
     *
     * @param string $path 路径
     * @param bool $limiton 是否限制了访问范围
     * @param string|[] $scopes 范围
     * @return bool 表示是否可以访问
     */
    private function checkAccessableWithRevise(&$path,$limiton,$scopes){
        if(!$limiton or !$scopes) return true;
        $temp = dirname($path);//修改的目录
        $path = $this->toSystemEncode($path);
        if(is_string($scopes)){
            $scopes = [$scopes];
        }

        foreach ($scopes as $scope){
            if(SEK::checkPathContainedInScope($temp,$scope)){
                return true;
            }
        }
//        \PLite\dumpout($scopes,$temp);
        return false;
    }

    /**
     * 检查是否有读取权限
     * @param string $path 路径
     * @return bool
     */
    private function checkReadableWithRevise(&$path){
        return $this->checkAccessableWithRevise($path,$this->convention['READ_LIMIT_ON'],$this->convention['READABLE_SCOPE']);
    }

    /**
     * 检查是否有写入权限
     * @param string $path 路径
     * @return bool
     */
    private function checkWritableWithRevise(&$path){
        return $this->checkAccessableWithRevise($path,$this->convention['WRITE_LIMIT_ON'],$this->convention['WRITABLE_SCOPE']);
    }

//----------------------------------------------------------------------------------------------------------------------
//------------------------------------ 读取 -----------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------

    /**
     * 获取文件内容
     * 注意：
     *  页面是utf-8，file_get_contents的页面是gb2312，输出时中文乱码
     * @param string $filepath 文件路径,php源码中格式是UTF-8，需要转成GB2312才能使用
     * @param string|array $file_encoding 文件内容实际编码,可以是数组集合或者是编码以逗号分开的字符串
     * @param bool $recursion 如果读取到的文件是目录,是否进行递归读取,默认为false
     * @return string|array|false|null 返回文件时间内容;返回null表示在访问的范围之外
     */
    public function read($filepath, $file_encoding=null,$recursion=false){//,$output_encode='UTF-8'
        if(!$this->checkReadableWithRevise($filepath)) return null;

        if(is_file($filepath)){
            return $this->_readFile($filepath, $file_encoding);
        }elseif(is_dir($filepath)){
            return $this->_readDir($filepath,$recursion);
        }else{
            //文件不存在
            return false;
        }
    }

    /**
     * 读取文件,参数参考read方法
     * @param string $filepath
     * @param string $file_encoding
     * @return false|string 读取失败返回false
     */
    private function _readFile($filepath, $file_encoding){
        $content = file_get_contents($filepath,null,null,null,$this->convention['READOUT_MAX_SIZE']);//限制大小为2M
        if(false === $content) return false;

        if(null === $file_encoding){
            return $content;
        }else{
            if($file_encoding === $this->convention['READOUT_ENCODE']) return $content;
            $readoutEncode = $this->convention['READOUT_ENCODE'].'//IGNORE';
            if(is_string($file_encoding) && false === strpos($file_encoding,',')){
                return iconv($file_encoding,$readoutEncode,$content);
            }
            return mb_convert_encoding($content,$readoutEncode,$file_encoding);
        }
    }

    /**
     * 读取文件夹内容，并返回一个数组(不包含'.'和'..')
     * array(
     *      //文件名称(相对于带读取的目录而言) => 文件内容
     *      'filename' => 'file full path',
     * );
     * @param $dirpath
     * @param bool $recursion 是否进行递归读取
     * @param bool $_isouter 辅助参数,用于判断是外部调用还是内部的
     * @return array
     */
    private function _readDir($dirpath, $recursion=false, $_isouter=true){
        static $_file = [];
        static $_dirpath_toread = null;

        if(true === $_isouter){
            //外部调用,初始化
            $_file = [];
            $_dirpath_toread = $dirpath;
        }

        $handler = opendir($dirpath);
        while (($filename = readdir( $handler )) !== false) {//未读到最后一个文件时候返回false
            if ($filename === '.' or $filename === '..' ) continue;

            $fullpath = "{$dirpath}/{$filename}";//子文件的完整路径

            if(file_exists($fullpath)) {
                $index = strpos($fullpath,$_dirpath_toread);
                $_file[$this->toProgramEncode(substr($fullpath,$index+strlen($_dirpath_toread)))] = str_replace('\\','/',$this->toProgramEncode($fullpath));
            }

            if($recursion and is_dir($fullpath)) {
                $_isouter = "{$_isouter}/{$filename}";
                $this->_readDir($fullpath,$recursion,false);//递归,不清空
            }
        }
        closedir($handler);//关闭目录指针
        return $_file;
    }

    /**
     * 确定文件或者目录是否存在
     * 相当于 is_file() or is_dir()
     * @param string $filepath 文件路径
     * @return int 0表示目录不存在,<0表示是目录 >0表示是文件,可以用Storage的三个常量判断
     */
    public function has($filepath){
        if(!$this->checkReadableWithRevise($filepath)) return null;
        if(is_dir($filepath)) return Storage::IS_DIR;
        if(is_file($filepath)) return Storage::IS_FILE;
        return Storage::IS_EMPTY;
    }

    /**
     * 返回文件内容上次的修改时间
     * @param string $filepath 文件路径
     * @param int $mtime 修改时间
     * @return int|bool|null 如果是修改时间的操作返回的bool;如果是获取修改时间,则返回Unix时间戳;返回null表示在访问的范围之外
     */
    public function mtime($filepath,$mtime=null){
        if(!$this->checkReadableWithRevise($filepath)) return null;
        return file_exists($filepath)?null === $mtime?filemtime($filepath):touch($filepath,$mtime):false;
    }

    /**
     * 获取文件按大小
     * @param string $filepath 文件路径
     * @return int|false|null 按照字节计算的单位;返回null表示在访问的范围之外
     */
    public function size($filepath){
        if(!$this->checkReadableWithRevise($filepath)) return null;
        return file_exists($filepath)?filesize($filepath):false;//即便是加了@filesize也无法防止系统的报错
    }

//----------------------------------------------------------------------------------------------------------------------
//------------------------------------ 写入 -----------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------
    /**
     * 创建文件夹
     * @param string $dirpath 文件夹路径
     * @param int $auth 文件夹权限
     * @return bool|null 返回null表示在访问的范围之外
     */
    public function mkdir($dirpath,$auth = 0755){
        if(!$this->checkWritableWithRevise($dirpath)) return false;
        return $this->_makeDir($dirpath,$auth);
    }

    /**
     * 设定文件的访问和修改时间
     * 注意的是:内置函数touch在文件不存在的情况下会创建新的文件,此时创建时间可能大于修改时间和访问时间
     *         但是如果是在上层目录不存在的情况下
     * @param string $filepath 文件路径
     * @param int $mtime 文件修改时间
     * @param int $atime 文件访问时间，如果未设置，则值设置为mtime相同的值
     * @return bool 是否成功|返回null表示在访问的范围之外
     */
    public function touch($filepath, $mtime = null, $atime = null){
        if(!$this->checkWritableWithRevise($filepath)) return null;
        if(!file_exists($filepath)){
            $this->_makeDir(dirname($filepath));
        }
        return touch($filepath, $mtime,$atime);
    }

    /**
     * 修改文件权限
     * @param string $filepath 文件路径
     * @param int $auth 文件权限
     * @return bool 是否成功修改了该文件|返回null表示在访问的范围之外
     */
    public function chmod($filepath,$auth = 0755){
        if(!$this->checkWritableWithRevise($dirpath)) return false;
        return file_exists($filepath)?chmod($filepath,$auth):false;
    }

    /**
     * 创建文件夹
     * @param string $dirpath 文件夹路径
     * @param int $auth 文件夹权限
     * @return bool 文件夹已经存在的时候返回false,成功创建返回true
     */
    private function _makeDir($dirpath,$auth = 0755){
//        \PLite\dumpout(is_dir($dirpath),$dirpath,mkdir($dirpath,$auth,true));
        return is_dir($dirpath)?chmod($dirpath,$auth):mkdir($dirpath,$auth,true);
    }

    /**
     * 删除文件
     * 删除目录时必须保证该目录为空
     * @param string $filepath 文件或者目录的路径
     * @param bool $recursion 删除的目标是目录时,若目录下存在文件,是否进行递归删除,默认为false
     * @return bool 是否成功删除|返回null表示在访问的范围之外
     */
    public function unlink($filepath,$recursion=false){
        if(!$this->checkWritableWithRevise($filepath)) return null;
        if(is_file($filepath)){
            return unlink($filepath);
        }elseif(is_dir($filepath)){
            return $this->_removeDir($filepath,$recursion);
        }else{
            return false;
        }
    }

    /**
     * 删除文件夹
     * 注意:@rmdir($dirpath); 也无法阻止报错
     * @param string $dirpath 文件夹名路径
     * @param bool $recursion 是否递归删除
     * @return bool 目录不存在返回false
     */
    private function _removeDir($dirpath,$recursion=false){
        if(!is_dir($dirpath)) return false;
        //扫描目录

        $dh = opendir($dirpath);
        while ($file = readdir($dh)) {
            if($file === '.' or $file === '..') continue;

            if(!$recursion) {//存在其他文件或者目录,非true时循环删除
                closedir($dh);
                return false;
            }

            $path = str_replace('\\','/',"{$dirpath}/{$file}");
            if(is_dir($path) and !$this->_removeDir($path,$recursion)) return false;
            if(is_file($path) and !unlink($path)) return false;
        }
        closedir($dh);
        return rmdir($dirpath);
    }



    /**
     * 将指定内容写入到文件中
     * @param string $filepath 文件路径
     * @param string $content 要写入的文件内容
     * @param string $write_encode 写入文件时的编码
     * @param string $text_encode 文本本身的编码格式,默认使用UTF-8的编码格式
     * @return bool 是否成功写入|返回null表示在访问的范围之外
     */
    public function write($filepath,$content,$write_encode=null,$text_encode='UTF-8'){
//        \PLite\dump($this->convention,$filepath,$this->checkWritableWithRevise($filepath));
        if(!$this->checkWritableWithRevise($filepath)) return null;
        return $this->_write($filepath,$content,$write_encode,$text_encode);
    }

    /**
     * @param $filepath
     * @param $content
     * @param null $write_encode
     * @param string $text_encode
     * @return bool
     */
    private function _write($filepath,$content,$write_encode=null,$text_encode='UTF-8'){
        //文件父目录检测
        $dir = dirname($filepath);
        if(!is_dir($dir)) $this->_makeDir($dir);

        //文本编码检测
        null === $write_encode and $write_encode = $this->convention['WRITEIN_ENCODE'];
        if($write_encode !== $text_encode){//写入的编码并非是文本的编码时进行转化
            $content = iconv($text_encode,$write_encode.'//IGNORE',$content);
        }

        //文件写入
        return file_put_contents($filepath,$content) > 0;
    }

    /**
     * 将指定内容追加到文件中
     * @param string $filepath 文件路径
     * @param string $content 要写入的文件内容
     * @param string $write_encode 写入文件时的编码
     * @param string $text_encode 文本本身的编码格式,默认使用UTF-8的编码格式
     * @return bool|null 是否成功写入,返回null表示无法访问该范围的文件
     */
    public function append($filepath,$content,$write_encode=null,$text_encode='UTF-8'){
        if(!$this->checkWritableWithRevise($filepath)) return null;

        //文件不存在时
        if(!is_file($filepath)) return $this->_write($filepath,$content,$write_encode);

        //打开文件
        $handler = fopen($filepath,'a+');//追加方式，如果文件不存在则无法创建
        if(false === $handler) return false;

        //编码处理
        null === $write_encode and $write_encode = $this->convention['WRITEIN_ENCODE'];
        $write_encode !== $text_encode and $content = iconv($text_encode,$write_encode,$content);

        //关闭文件
        $rst = fwrite($handler,$content); //出现错误时返回false
        if(false === fclose($handler)) return false;

        return $rst > 0;
    }

}