<?php
namespace PLite\Core\Storage;
use PLite\Core\Storage;
use PLite\Core\StorageInterface;
use PLite\PLiteException;
use PLite\Utils;

/**
 * Class File 文件系统驱动类基类
 * @package PLite\Core\Storage
 */
class File implements StorageInterface {

    protected $config = [];

    public function __construct(array $config=null){
        $config and $this->config = array_merge($this->config,$config);
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
        $path = Utils::toSystemEncode($path);
        is_string($scopes) and $scopes = [$scopes];

        foreach ($scopes as $scope){
            if(Utils::checkInScope($temp,$scope)){
                return true;
            }
        }
        return false;
    }

    /**
     * 检查是否有读取权限
     * @param string $path 路径
     * @return bool
     */
    private function checkReadableWithRevise(&$path){
        return $this->checkAccessableWithRevise($path,$this->config['READ_LIMIT_ON'],$this->config['READABLE_SCOPE']);
    }

    /**
     * 检查是否有写入权限
     * @param string $path 路径
     * @return bool
     */
    private function checkWritableWithRevise(&$path){
        return $this->checkAccessableWithRevise($path,$this->config['WRITE_LIMIT_ON'],$this->config['WRITABLE_SCOPE']);
    }

//----------------------------------------------------------------------------------------------------------------------
//------------------------------------ 读取 -----------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------------

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
    public function readDir($dirpath, $recursion=false, $_isouter=true){
        static $_file = [];
        static $_dirpath_toread = null;
        if(!$this->checkReadableWithRevise($filepath)) return null;

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
                $_file[Utils::toProgramEncode(substr($fullpath,$index+strlen($_dirpath_toread)))] =
                    str_replace('\\','/',Utils::toProgramEncode($fullpath));
            }

            if($recursion and is_dir($fullpath)) {
                $_isouter = "{$_isouter}/{$filename}";
                $this->readDir($fullpath,$recursion,false);//递归,不清空
            }
        }
        closedir($handler);//关闭目录指针
        return $_file;
    }
    /**
     * 读取文件,参数参考read方法
     * @param string $filepath
     * @param string $file_encoding
     * @param string $readout_encoding
     * @param int|null $maxlen Maximum length of data read. The default of php is to read until end of file is reached. But I limit to 4 MB
     * @return false|string 读取失败返回false
     */
    public function read($filepath, $file_encoding='UTF-8',$readout_encoding='UTF-8',$maxlen=4094304){
        if(!$this->checkReadableWithRevise($filepath)) return null;
        $content = file_get_contents($filepath,null,null,null,$maxlen);//限制大小为2M
        if(false === $content) return false;//false on failure
        if(null === $file_encoding or $file_encoding === $readout_encoding){
            return $content;//return the raw content or what the read is what the need
        }else{
            $readoutEncode = "{$readout_encoding}//IGNORE";
            if(is_string($file_encoding) and false === strpos($file_encoding,',')){
                return iconv($file_encoding,$readoutEncode,$content);
            }
            return mb_convert_encoding($content,$readoutEncode,$file_encoding);
        }
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
     * @return int|bool|null 如果是修改时间的操作返回的bool;如果是获取修改时间,则返回Unix时间戳;
     */
    public function mtime($filepath,$mtime=null){
        if(!$this->checkReadableWithRevise($filepath)) return null;
        return file_exists($filepath)?null === $mtime?filemtime($filepath):touch($filepath,$mtime):false;
    }

    /**
     * 获取文件按大小
     * @param string $filepath 文件路径
     * @return int|false|null 按照字节计算的单位;
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
     * @param string $dir 文件夹路径
     * @param int $auth 文件夹权限
     * @return bool 文件夹已经存在的时候返回false,成功创建返回true
     */
    public function mkdir($dir, $auth = 0766){
        if(!$this->checkWritableWithRevise($dir)) return false;
        return is_dir($dir)?chmod($dir,$auth):mkdir($dir,$auth,true);
    }

    /**
     * 修改文件权限
     * @param string $path 文件路径
     * @param int $auth 文件权限
     * @return bool 是否成功修改了该文件|返回null表示在访问的范围之外
     */
    public function chmod($path, $auth = 0766){
        if(!$this->checkWritableWithRevise($path)) return null;
        return file_exists($path)?chmod($path,$auth):false;
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
        $this->checkAndMakeSubdir($filepath) or PLiteException::throwing("Check path '$filepath' failed");
        return touch($filepath, $mtime,$atime);
    }

    /**
     * 删除文件
     * 删除目录时必须保证该目录为空,or set parameter 2 as true
     * @param string $filepath 文件或者目录的路径
     * @param bool $recursion 删除的目标是目录时,若目录下存在文件,是否进行递归删除,默认为false
     * @return bool
     */
    public function unlink($filepath,$recursion=false){
        if(!$this->checkWritableWithRevise($filepath)) return null;
        if(is_file($filepath)){
            return unlink($filepath);
        }elseif(is_dir($filepath)){
            return $this->rmdir($filepath,$recursion);
        }
        return false; //file do not exist
    }
    /**
     * @param string $filepath
     * @param string $content
     * @param string $write_encode Encode of the text to write
     * @param string $text_encode encode of content,it will be 'UTF-8' while scruipt file is encode with 'UTF-8',but sometime it's not expect
     * @return bool
     */
    public function write($filepath,$content,$write_encode='UTF-8',$text_encode='UTF-8'){
        if(!$this->checkWritableWithRevise($filepath)) return null;
        $this->checkAndMakeSubdir($filepath) or PLiteException::throwing("Check path '$filepath' failed");
        //文本编码检测
        if($write_encode !== $text_encode){//写入的编码并非是文本的编码时进行转化
            $content = iconv($text_encode,"{$write_encode}//IGNORE",$content);
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
     * @return bool
     */
    public function append($filepath,$content,$write_encode='UTF-8',$text_encode='UTF-8'){
        if(!$this->checkWritableWithRevise($filepath)) return null;
        //文件不存在时
        if(!is_file($filepath)) return $this->write($filepath,$content,$write_encode,$text_encode);

        //打开文件
        $handler = fopen($filepath,'a+');//追加方式，如果文件不存在则无法创建
        if(false === $handler) return false;//open failed

        //编码处理
        $write_encode !== $text_encode and $content = iconv($text_encode,"{$write_encode}//IGNORE",$content);

        //关闭文件
        $rst = fwrite($handler,$content); //出现错误时返回false
        if(false === fclose($handler)) return false;//close failed

        return $rst > 0;
    }

    /**
     * 文件父目录检测
     * @param string $path the path must be encode with file system
     * @param int $auth
     * @return bool
     */
    private function checkAndMakeSubdir($path, $auth = 0766){
        $path = dirname($path);
        if(!is_dir($path)) return $this->mkdir($path,$auth);
        if(!is_writeable($path)) return $this->chmod($path,$auth);
        return true;
    }

    /**
     * 删除文件夹
     * 注意:@rmdir($dirpath); 也无法阻止报错
     * @param string $dir 文件夹名路径
     * @param bool $recursion 是否递归删除
     * @return bool
     */
    private function rmdir($dir, $recursion=false){
        if(!is_dir($dir)) return false;
        //扫描目录
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if($file === '.' or $file === '..') continue;

            if(!$recursion) {//存在其他文件或者目录,非true时循环删除
                closedir($dh);
                return false;
            }
            $dir = IS_WINDOWS?str_replace('\\','/',"{$dir}/{$file}"):"{$dir}/{$file}";
            if(!$this->unlink($dir,$recursion)) return false;
        }
        closedir($dh);
        return rmdir($dir);
    }
}