<?php
/**
 * Created by linzhv@outlook.com
 * User: asus
 * Date: 16-6-14
 * Time: 15:32
 */
namespace PLite\Util\Encrypt;

/**
 * Class Encrypt
 * based on base64.
 * @package PLite\Util
 */
class Base64{

    /**
     * 加密字符串
     * @param string $data 字符串
     * @param string $key 加密key
     * @param integer $expire 有效期（秒）
     * @return string
     */
    public static function encrypt($data,$key,$expire=0) {
        $expire = sprintf('%010d', $expire ? $expire + time():0);
        $key  = md5($key);
        $data = base64_encode($expire.$data);
        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        $char = $str    =   '';

        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }

        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
        }
        return str_replace(['+','/','='], ['-','_',''] ,base64_encode($str));
    }

    /**
     * 解密字符串
     * @param string $data 字符串
     * @param string $key 加密key
     * @return string
     */
    public static function decrypt($data,$key) {
        $key    = md5($key);
        $data   = str_replace(array('-','_'),array('+','/'),$data);
        $mod4   = strlen($data) % 4;
        $mod4 and $data .= substr('====', $mod4);
        $data   = base64_decode($data);

        $x      = 0;
        $len    = strlen($data);
        $l      = strlen($key);
        $char   = $str = '';

        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }

        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1))<ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            }else{
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        $data   = base64_decode($str);
        $expire = substr($data,0,10);
        return ($expire > 0 && $expire < time())?'':substr($data,10);
    }
}