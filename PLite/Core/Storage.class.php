<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 9:55 PM
 */

namespace PLite\Core;
use PLite\Lite;
use PLite\core\storage\StorageInterface;

/**
 * Class Storage 持久化存储类
 * 实际文件可能写在伺服器的文件中，也可能存放到数据库文件中，或者远程文件服务器中
 * @package PLite\Core
 */
class Storage extends Lite {

    const CONF_NAME = 'storage';
    const CONF_CONVENTION = [
        'PRIOR_INDEX' => 0,
        'DRIVER_CLASS_LIST' => [
            'PLite\\Core\\Storage\\File',
        ],
        'DRIVER_CONFIG_LIST' => [
            [
                'READ_LIMIT_ON'     => true,
                'WRITE_LIMIT_ON'    => true,
                'READABLE_SCOPE'    => PATH_BASE,
                'WRITABLE_SCOPE'    => PATH_RUNTIME,

                'READOUT_MAX_SIZE'          => 2097152,//2M限制,对于文本文件已经足够
                'OS_ECNODE'         => 'GB2312', // 文件系统编码格式,如果是英文环境下可能是UTF-8,GBK,GB2312以外的编码格式
                'READOUT_ENCODE'    => 'UTF-8', // 读出时转化的成的编码格式
                'WRITEIN_ENCODE'    => 'UTF-8', // 写入时转化的编码格式
            ]
        ],

    ];

    /**
     * 目录存在与否
     */
    const IS_DIR    = -1;
    const IS_FILE   = 1;
    const IS_EMPTY  = 0;

    /**
     * @var StorageInterface
     */
    private static $driver = null;

    /**
     * 获取文件内容
     * @param string $filepath 文件路径
     * @param string $fileEncoding 文件内容实际编码
     * @param bool $recursion 如果读取到的文件是目录,是否进行递归读取,默认为false
     * @return string|array|false|null 返回文件时间内容;返回null表示在访问的范围之外
     */
    public static function read($filepath, $fileEncoding=null,$recursion=false){
        self::$driver or self::$driver = self::getDriver();
        return self::$driver->read($filepath,$fileEncoding,$recursion);
    }

    /**
     * 文件写入
     * @param string $filepath 文件名
     * @param string $content 文件内容
     * @param string $write_encode 写入编码
     * @param string $text_encode 文本本身的编码格式
     * @return bool 是否成功写入|返回null表示在访问的范围之外
     */
    public static function write($filepath,$content,$write_encode='UTF-8',$text_encode='UTF-8') {
        self::$driver or self::$driver = self::getDriver();
        return self::$driver->write($filepath,$content,$write_encode,$text_encode);
    }

    /**
     * 文件追加写入
     * @access public
     * @param string $filename  文件名
     * @param string $content  追加的文件内容
     * @param string $write_encode 文件写入编码
     * @return bool|null 是否成功写入,返回null表示无法访问该范围的文件
     */
    public static function append($filename,$content,$write_encode='UTF-8'){
        self::$driver or self::$driver = self::getDriver();
        return self::$driver->append($filename,$content,$write_encode);
    }

    /**
     * 文件是否存在
     * @param string $filename  文件名
     * @return int 0表示目录不存在,<0表示是目录 >0表示是文件,可以用Storage的三个常量判断
     */
    public static function has($filename){
        self::$driver or self::$driver = self::getDriver();
        return self::$driver->has($filename);
    }


    /**
     * 设定文件的访问和修改时间
     * @param string $filename 文件路径
     * @param int $mtime  文件最后修改时间
     * @param int $atime  文件最后访问时间
     * @return bool 是否成功|返回null表示在访问的范围之外
     */
    public static function touch($filename, $mtime = null, $atime = null){
        self::$driver or self::$driver = self::getDriver();
        return self::$driver->touch($filename,$mtime,$atime);
    }

    /**
     * 文件删除
     * @param string $filename 文件名
     * @param bool $recursion 删除的目标是目录时,若目录下存在文件,是否进行递归删除,默认为false
     * @return bool 是否成功删除|返回null表示在访问的范围之外
     */
    public static function unlink($filename,$recursion=false){
        self::$driver or self::$driver = self::getDriver();
        return self::$driver->unlink($filename,$recursion);
    }

    /**
     * 返回文件内容上次的修改时间
     * @param string $filepath 文件路径
     * @param int $mtime 修改时间
     * @return int|bool|null 如果是修改时间的操作返回的bool;如果是获取修改时间,则返回Unix时间戳;返回null表示在访问的范围之外
     */
    public static function mtime($filepath,$mtime=null){
        self::$driver or self::$driver = self::getDriver();
        return self::$driver->mtime($filepath,$mtime);
    }

    /**
     * 获取文件大小
     * @param string $filename 文件路径信息
     * @return int|false|null 按照字节计算的单位;返回null表示在访问的范围之外
     */
    public static function size($filename){
        self::$driver or self::$driver = self::getDriver();
        return self::$driver->size($filename);
    }

    /**
     * 创建文件夹
     * 如果文件夹已经存在，则修改权限
     * @param string $fullpath 文件夹路径
     * @param int $auth 文件权限，八进制表示
     * @return bool|null 返回null表示在访问的范围之外
     */
    public static function mkdir($fullpath,$auth = 0755){
        self::$driver or self::$driver = self::getDriver();
        return self::$driver->mkdir($fullpath,$auth);
    }

    /**
     * 修改文件权限
     * @param string $filepath 文件路径
     * @param int $auth 文件权限
     * @return bool 是否成功修改了该文件|返回null表示在访问的范围之外
     */
    public static function chmod($filepath,$auth = 0755){
        self::$driver or self::$driver = self::getDriver();
        return self::$driver->chmod($filepath,$auth);
    }

}