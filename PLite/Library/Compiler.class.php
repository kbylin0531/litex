<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 9:52 PM
 */
namespace PLite\library;
use PLite\Util\SEK;

/**
 * Class Compiler
 * 伪编译器
 * 将多个类文件压缩到一个文件中，加快加载速度
 * 注意：可能需要消耗更多内存
 * @package PLite\Core
 */
class Compiler {
    /**
     * 编译类文件
     * @param string $content 文件内容
     * @param bool|false $isfile 标记参数一指的是文件内容还是文件路径，默认为文件内容
     * @return string 编译后的文件内容
     */
    public static function compile($content,$isfile=false){
        if($isfile) $content = file_get_contents($content);
        $content    =   php_strip_whitespace($content);//删除php代码中的注释和空格
        $content    =   trim(substr($content, 5));//去除 '<?php'
        // 替换命名空间
        if(0===strpos($content,'namespace')){
            $content    =   preg_replace('/namespace\s(.*?);/','namespace \\1{',$content,1);
        }else{
            $content    =   'namespace {'.$content;
        }
        //去除  '? >'   也有可能不会带这个
        if ('?>' == substr($content, -2)){
            $content    = substr($content, 0, -2);
        }
        return "{$content} }";
    }

    public static function compileInBatch(array $filelist){
        $content = '';
        // 编译文件
        foreach ($filelist as $file){
            if(is_file($file)){
                if(!isset($_cache[$file])){
                    $content .= self::compile($file);
                    $_cache[$file] = true;
                }
            }
        }
        // 生成运行Lite文件
        return SEK::stripWhiteSpace('<?php '.$content);
    }

    /**
     * 将编译后的文件写入持久化
     * @param string $path 文件路径
     * @param string $content 文件内容
     * @return int
     */
    public static function overwrite($path,$content){
        return file_put_contents($path,$content);
    }
}