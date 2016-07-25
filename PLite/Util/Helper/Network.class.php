<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/1/27
 * Time: 9:44
 */
namespace PLite\Util\Helper;
use PLite\Core\Storage;
use PLite\Util\SEK;

/**
 * Class Network 网络相关工具类
 * @package Kbylin\System\Utils
 */
class Network {

//----------------------- HTTP方法 ------------------------------------------//

    /**
     * 采集远程文件
     * @access public
     * @param string $remote 远程文件名
     * @param string $local 本地保存文件名
     * @return mixed
     */
    static public function curlDownload($remote,$local) {
        $cp = curl_init($remote);
        $fp = fopen($local,"w");
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
        curl_exec($cp);
        curl_close($cp);
        fclose($fp);
    }

    /**
     * 使用 fsockopen 通过 HTTP 协议直接访问(采集)远程文件
     * 如果主机或服务器没有开启 CURL 扩展可考虑使用
     * fsockopen 比 CURL 稍慢,但性能稳定
     * @static
     * @access public
     * @param string $url 远程URL
     * @param array $conf 其他配置信息
     *        int   limit 分段读取字符个数
     *        string post  post的内容,字符串或数组,key=value&形式
     *        string cookie 携带cookie访问,该参数是cookie内容
     *        string ip    如果该参数传入,$url将不被使用,ip访问优先
     *        int    timeout 采集超时时间
     *        bool   block 是否阻塞访问,默认为true
     * @return mixed
     */
    static public function fsockopenDownload($url, $conf = array()) {
        $return = '';
        if(!is_array($conf)) return $return;

        $matches = parse_url($url);
        !isset($matches['host']) 	&& $matches['host'] 	= '';
        !isset($matches['path']) 	&& $matches['path'] 	= '';
        !isset($matches['query']) 	&& $matches['query'] 	= '';
        !isset($matches['port']) 	&& $matches['port'] 	= '';
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;

        $conf_arr = array(
            'limit'		=>	0,
            'post'		=>	'',
            'cookie'	=>	'',
            'ip'		=>	'',
            'timeout'	=>	15,
            'block'		=>	TRUE,
        );

//        foreach (array_merge($conf_arr, $conf) as $k=>$v) ${$k} = $v;//动态变量设置(编辑器不认识)
        $conf_arr = array_merge($conf_arr, $conf);
        $post = $conf_arr['post'];
        $limit = $conf_arr['limit'];
        $cookie = $conf_arr['cookie'];
        $ip = $conf_arr['ip'];
        $timeout = $conf_arr['timeout'];
        $block = $conf_arr['block'];

        if($post) {
            if(is_array($post))
            {
                $post = http_build_query($post);
            }
            $out  = "POST $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= 'Content-Length: '.strlen($post)."\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cache-Control: no-cache\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
            $out .= $post;
        } else {
            $out  = "GET $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
        }
        $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if(!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if(!$status['timed_out']) {
                while (!feof($fp)) {
                    if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
                        break;
                    }
                }

                $stop = false;
                while(!feof($fp) && !$stop) {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
            @fclose($fp);
            return $return;
        }
    }

    /**
     * @param string $url
     * @param string $file
     * @param int $timeout
     * @return false|string
     */
    public static function download($url, $file='', $timeout=60) {
        if($file){
            $basename = pathinfo($file,PATHINFO_BASENAME);
            $dir = realpath(dirname($file));
//            \PLite\dumpout($file,realpath(dirname($file)),is_dir(realpath($dir)));
        }else{
            $basename = md5($url.REQUEST_MICROTIME);//应对变化的事件
            $dir = PATH_PUBLIC.'/download';
        }
        if(!is_dir($dir) or !is_writeable($dir)){
            Storage::mkdir($dir,0766);
        }
        $file = "{$dir}/{$basename}";

        $context = stream_context_create([
            'http'=>[
                'method'    =>  'GET',
                'header'    =>  "",
                'timeout'   =>  $timeout
            ],
        ]);
        if(@copy($url, $file, $context)) {
            return $file;
        } else {
            return false;
        }
    }

    /**
     * 下载文件
     * 可以指定下载显示的文件名，并自动发送相应的Header信息
     * 如果指定了content参数，则下载该参数的内容
     * @static
     * @access public
     * @param string $filename 下载文件名
     * @param string $showname 下载显示的文件名
     * @param string $content  下载的内容
     * @param integer $expire  下载内容浏览器缓存时间
     * @return void
     * @throws \Exception
     */
    static public function download2 ($filename, $showname='',$content='',$expire=180) {
        if(is_file($filename)) {
            $length = filesize($filename);
        }elseif(is_file($filename)) {
            $length = filesize($filename);
        }elseif($content != '') {
            $length = strlen($content);
        }else {
            throw new \Exception("file '$filename' not found !");
        }
        if(empty($showname)) {
            $showname = $filename;
        }
        $showname = basename($showname);
        if(!empty($filename)) {
            $finfo 	= 	new \finfo(FILEINFO_MIME);
            $type 	= 	$finfo->file($filename);
        }else{
            $type	=	"application/octet-stream";
        }
        //发送Http Header信息 开始下载
        header("Pragma: public");
        header("Cache-control: max-age=".$expire);
        //header('Cache-Control: no-store, no-cache, must-revalidate');
        header("Expires: " . gmdate("D, d M Y H:i:s",time()+$expire) . "GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s",time()) . "GMT");
        header("Content-Disposition: attachment; filename=".$showname);
        header("Content-Length: ".$length);
        header("Content-type: ".$type);
        header('Content-Encoding: none');
        header("Content-Transfer-Encoding: binary" );
        if($content == '' ) {
            readfile($filename);
        }else {
            echo($content);
        }
        exit();
    }

    /**
     * 显示HTTP Header 信息
     * @param string $header
     * @param bool|true $echo 是否直接输出但不返回
     * @return string
     */
    static function getHeaderInfo($header='',$echo=true) {
        ob_start();
        $headers   	= getallheaders();
        if(!empty($header)) {
            $info 	= $headers[$header];
            echo($header.':'.$info."\n"); ;
        }else {
            foreach($headers as $key=>$val) {
                echo("$key:$val\n");
            }
        }
        $output 	= ob_get_clean();
        if($echo) echo nl2br($output);
        return $output;
    }

    const DATA_TYPE_TEXT = 0;
    const DATA_TYPE_JSON = 1;




//----------------------- CURL方法 ------------------------------------------//

    /**
     * 模拟GET请求
     *
     * @param string $url
     * @param string $data_type
     *
     * @return mixed
     *
     * Examples:
     * ```
     * HttpCurl::get('http://api.example.com/?a=123&b=456', 'json');
     * ```
     */
    public static function get($url, $data_type='json') {
        $cl = curl_init();
        if(stripos($url, 'https://') !== false) {
            curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($cl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($cl, CURLOPT_URL, $url);
        curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1 );
        $content = curl_exec($cl);
        $status = curl_getinfo($cl);
        curl_close($cl);
        if (isset($status['http_code']) && $status['http_code'] == 200) {
            return $data_type === 'json'?json_decode($content,true):$content;
        } else {
            return false;
        }
    }


    /**
     * 模拟POST请求
     *
     * @param string $url
     * @param array $fields
     * @param string $data_type
     *
     * @return mixed
     *
     * Examples:
     * ```
     * HttpCurl::post('http://api.example.com/?a=123', array('abc'=>'123', 'efg'=>'567'), 'json');
     * HttpCurl::post('http://api.example.com/', '这是post原始内容', 'json');
     * 文件post上传
     * XX HttpCurl::post('http://api.example.com/', array('abc'=>'123', 'file1'=>'@/data/1.jpg'), 'json');
     * ```
     */
    static public function post($url, array $fields, $data_type='json') {
        $cl = curl_init($url);
        curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($cl, CURLOPT_POST, true);
        curl_setopt($cl, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($cl, CURLOPT_RETURNTRANSFER, true );
        $content = curl_exec($cl);
//        \PLite\dumpout(curl_getinfo($cl),$content,$fields);
        curl_close($cl);
        if(false === $content){
//            $status = curl_getinfo($cl);//(isset($status['http_code']) && $status['http_code'] == 200)
            return false;
        }else{
            $data_type == 'json' and $content = json_decode($content,true);
            return $content;
        }
    }

    /**
     * use class 'CURLFile' instead of
     * @param $url
     * @param $path
     * @param $others
     * @return mixed
     */
    public static function postFile($url,$path=null,array $others=null){
        set_time_limit (0);
        $ch = curl_init($url);
        $others or $others = [];
        $path  and $others['media'] =  new \CURLFile($path);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $others);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public static function https_request($url,array $data = null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
//        curl_setopt ( $curl, CURLOPT_SAFE_UPLOAD, false);//curl_setopt(): Disabling safe uploads is no longer supported
        if ($data){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


}