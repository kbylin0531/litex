<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/22/16
 * Time: 3:44 PM
 */

namespace Application\System\Library\Service;
use PLite\Util\Helper\Network;

/**
 * Class MessageService 消息处理
 * @package Application\Wechat\Common\Library
 */
class MessageService extends WechatService{

    //消息类型
    const TYPE_TEXT         = 'text';//文本消息
    const TYPE_IMAGE        = 'image';//图片消息
    const TYPE_VOICE        = 'voice';//语音消息
    const TYPE_VIDEO        = 'video';//视频消息
    const TYPE_SHORTVIDEO   = 'shortvideo';//小视频消息
    const TYPE_LOCATION     = 'location';//地理位置消息
    const TYPE_LINK         = 'link';//链接消息
    const TYPE_EVENT        = 'event';//事件消息
    //事件消息类型
    const EVENT_TYPE_SUBSCRIBE  = 'subscribe';//订阅
    const EVENT_TYPE_UNSUBSCRIBE = 'unsubscribe';//取消订阅
    const EVENT_TYPE_SCAN       = 'SCAN';//扫描带参数二维码事件(已经关注的情况下)
    const EVENT_TYPE_LOCATION   = 'LOCATION';//上报地理位置事件
    const EVENT_TYPE_CLICK      = 'CLICK';//自定义菜单事件-点击菜单拉取消息时的事件推送
    const EVENT_TYPE_VIEW       = 'VIEW';//自定义菜单事件-点击菜单跳转链接时的事件推送

    /**
     * 接受到的消息对象
     * @var Object
     */
    private $MsgEntity = '';
    /**
     * 接受到的消息类型
     * @var string
     */
    private $MsgType = '';
    /**
     * 发送方帐号（一个OpenID）
     * @var string
     */
    private $FromUserName = '';
    /**
     * 开发者微信号
     * @var string
     */
    private $ToUserName = '';
    /**
     * 消息创建时间(差不多是发送时间)
     * @var int
     */
    private $CreateTime = 0;

    /**
     * 接收消息
     * @return bool 返回是否接收成功并解析成功
     */
    public function receive(){
        //获取消息
        $postStr = file_get_contents('php://input');//$GLOBALS["HTTP_RAW_POST_DATA"];
        if($postStr){
            if(($this->MsgEntity = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA)) !== false){
                //基本信息
                $this->MsgType = (string) $this->MsgEntity->MsgType;
                $this->FromUserName = (string) $this->MsgEntity->FromUserName;
                $this->ToUserName = (string) $this->MsgEntity->ToUserName;
                $this->CreateTime = (string) $this->MsgEntity->CreateTime;
                return true;
            }
        }
        return false;
    }

    /**
     * 返回消息
     * 注：
     *  function(
     *      string $MsgType, 消息类型
     *      SimpleXMLElement $MsgEntity 消息体
     * )
     * @param callable|null $handler 消息处理
     */
    public function response(callable $handler=null){
        if(!$this->MsgType) exit('无法获取消息类型，请正确地调用receive方法后调用response！');
        if($handler){
            $result = $handler($this->MsgType,$this->MsgEntity);
        }else{
            $result = $this->test();
        }
        echo $result;
    }

    /**
     * 测试函数
     * @return string
     */
    private function test(){
        switch ($this->MsgType){
            case self::TYPE_TEXT:
                $result = $this->responseText('接受到文本消息');
                break;
            case self::TYPE_IMAGE:
                $result = $this->responseText('接受到TYPE_IMAGE消息');
                break;
            case self::TYPE_LINK:
                $result = $this->responseText('接受到TYPE_LINK消息');
                break;
            case self::TYPE_LOCATION:
                $result = $this->responseText('接受到TYPE_LOCATION消息');
                break;
            case self::TYPE_SHORTVIDEO:
                $result = $this->responseText('接受到TYPE_SHORTVIDEO消息');
                break;
            case self::TYPE_VIDEO:
                $result = $this->responseText('接受到TYPE_VIDEO消息');
                break;
            case self::TYPE_VOICE:
                $result = $this->responseText('接受到TYPE_VOICE消息');
                break;
            case self::TYPE_EVENT:
                switch ($this->getEventType()){
                    case self::EVENT_TYPE_CLICK:
                        $result = $this->responseText('接受到了EVENT_TYPE_CLICK 事件');
                        break;
                    case self::EVENT_TYPE_LOCATION:
                        $result = $this->responseText('接受到了 EVENT_TYPE_LOCATION 事件');
                        break;
                    case self::EVENT_TYPE_SCAN:
                        $result = $this->responseText('接受到了 EVENT_TYPE_SCAN 事件');
                        break;
                    case self::EVENT_TYPE_SUBSCRIBE:
                        $result = $this->responseText('接受到了 EVENT_TYPE_SUBSCRIBE 事件');
                        break;
                    case self::EVENT_TYPE_UNSUBSCRIBE:
                        $result = $this->responseText('接受到了 EVENT_TYPE_UNSUBSCRIBE 事件');
                        break;
                    case self::EVENT_TYPE_VIEW:
                        $result = $this->responseText('接受到了 EVENT_TYPE_VIEW 事件');
                        break;
                    default:
                        $result = $this->responseText('无法识别的事件类型');
                }
                break;
            default:
                $result = $this->responseText('无法识别的消息内容');
        }
        return $result;
    }

    /**
     * 获取事件类型
     * @return string|null 返回事件名称字符串，非事件的情况下返回false
     */
    public function getEventType(){
        if($this->MsgType == self::TYPE_EVENT){//前者为SimpleXMLElement对象(自动转化为string类型)，只能用==判断
            return $this->MsgEntity->Event;
        }
        return false;
    }
//------------------------------------- 被动回复用户消息 ---------------------------------------------------------------------------//
    /**
     * 回复文本消息
     * @param string $content 回复内容
     * @return string
     */
    public function responseText($content){
        $result = sprintf('<xml><ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[text]]></MsgType>
                            <Content><![CDATA[%s]]></Content></xml>',
            $this->FromUserName, $this->ToUserName, REQUEST_TIME, $content);
        return $result;
    }

    /**
     * 回复图片消息
     * @param string $MediaId 通过素材管理中的接口上传多媒体文件，得到的id。
     * @return string
     */
    public function responseImage($MediaId) {
        $result = sprintf('<xml><ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[image]]></MsgType>
                            <Image><MediaId><![CDATA[%s]]></MediaId></Image></xml>',
            $this->FromUserName, $this->ToUserName, REQUEST_TIME,$MediaId);
        return $result;
    }

    /**
     * 回复语音消息
     * @param int $MediaId 通过素材管理中的接口上传多媒体文件，得到的id
     * @return string
     */
    public function responseVoice($MediaId){
        $result = sprintf('<xml><ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Voice><MediaId><![CDATA[%s]]></MediaId></Voice></xml>',
            $this->FromUserName, $this->ToUserName, REQUEST_TIME,$MediaId);
        return $result;
    }

    /**
     * 回复视频消息
     * @param int $MediaId 通过素材管理中的接口上传多媒体文件，得到的id
     * @param string|null $title 视频消息的标题(不设置时设置为null)
     * @param string|null $describe 视频消息的描述(不设置时设置为null)
     * @return string
     */
    public function responseVideo($MediaId,$title=null,$describe=null){
        $title = $title?sprintf('<Title><![CDATA[%s]]></Title>',$title):'';
        $describe = $describe?sprintf('<Description><![CDATA[%s]]></Description>',$describe):'';
        $result = sprintf('<xml><ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Video><MediaId><![CDATA[%s]]></MediaId> %s $s </Video> </xml>',
            $this->FromUserName, $this->ToUserName, REQUEST_TIME,$MediaId,$title,$describe);
        return $result;
    }

    /**
     * 回复音乐消息
     * @param int $ThumbMediaId 缩略图的媒体id，通过素材管理中的接口上传多媒体文件，得到的id
     * @param string|null $Title 音乐标题
     * @param string|null $Description 音乐描述
     * @param string|null $MusicURL 音乐链接
     * @param string|null $HQMusicUrl 高质量音乐链接，WIFI环境优先使用该链接播放音乐
     * @return string
     */
    public function responseMusic($ThumbMediaId,$Title=null,$Description=null,$MusicURL=null,$HQMusicUrl=null){
        $Title = $Title?sprintf('<Title><![CDATA[%s]]></Title>',$Title):'';
        $Description = $Description?sprintf('<Description><![CDATA[%s]]></Description>',$Description):'';
        $MusicURL = $MusicURL?sprintf('<MusicUrl><![CDATA[%s]]></MusicUrl>',$MusicURL):'';
        $HQMusicUrl = $HQMusicUrl?sprintf('<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>',$HQMusicUrl):'';
        $result = sprintf('<xml><ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Music><ThumbMediaId><![CDATA[%s]]></ThumbMediaId></Music></xml><',
            $this->FromUserName, $this->ToUserName, REQUEST_TIME,$Title,$Description,$MusicURL,$HQMusicUrl,$ThumbMediaId);
        return $result;
    }

    /**
     * 回复图文消息
     * 格式如下： [
     *              [
     *                      'Title'=>'',
     *                      'Description'=>'',
     *                      'PicUrl'=>'',
     *                      'Url'=>''
     *              ],
     *              [
     *                      'Title'=>'',
     *                      'Description'=>'',
     *                      'PicUrl'=>'',
     *                      'Url'=>''
     *              ] ,
     *           ];
     * @param array $news 消息数组
     * @return string
     */
    public function responseNews(array $news){
        $item_str = '';
        foreach ($news as $item) {
            $item_str .= '<item>';
            empty($item['Title']) or $item_str .= sprintf('<Title><![CDATA[%s]]></Title>',$item['Title']);
            empty($item['Description']) or $item_str .= sprintf('<Description><![CDATA[%s]]></Description>',$item['Description']);
            empty($item['PicUrl']) or $item_str .= sprintf('<PicUrl><![CDATA[%s]]></PicUrl>',$item['PicUrl']);
            empty($item['Url']) or $item_str .= sprintf('<Url><![CDATA[%s]]></Url>',$item['Url']);
            $item_str .= '</item>';
        }
        $result = sprintf("<xml><ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[news]]></MsgType>
                            <ArticleCount>%s</ArticleCount>
                            <Articles>{$item_str}</Articles></xml>",
            $this->FromUserName, $this->ToUserName, REQUEST_TIME,count($news));
        return $result;
    }
    /**
     * 添加到微信日志
     * @param array|string $data 保存数据
     * @param string $data_post 接受到的数据
     * @param bool $wechat 通过微信客服接口直接回复调试信息，默认为false
     */
    protected function addWeixinLog($data, $data_post = '', $wechat = false) {
        $log ['cTime'] = time ();
        $log ['cTime_format'] = date ( 'Y-m-d H:i:s', $log ['cTime'] );
        $log ['data'] = is_array ( $data ) ? serialize ( $data ) : $data;
        $log ['data_post'] = is_array ( $data_post ) ? serialize ( $data_post ) : $data_post;
        if ($wechat) {
            $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $this->getAccessToken();
//            $param ['touser'] = $this->getOpenId();
            $param ['msgtype'] = 'text';
            $param ['text'] ['content'] = 'data: ' . $log ['data'] . '<br/> data_post: ' . $log ['data_post'];
            Network::post( $url, $param );
        }
    }


    /**
     * 发送回复消息到微信平台
     * @param array $msg
     * @param $msgType
     * @return mixed|string
     */
    protected function _replyData(array $msg, $msgType) {
        $msg ['ToUserName'] = $this->FromUserName;
        $msg ['FromUserName'] = $this->ToUserName;
        $msg ['CreateTime'] = REQUEST_TIME;
        $msg ['MsgType'] = $msgType;

        $xml = new \SimpleXMLElement ( '<xml></xml>' );
        $this->_data2xml ( $xml, $msg );
        $sReplyMsg = $xml->asXML ();//回复的明文消息
        if ($_GET ['encrypt_type'] == 'aes') {
            //TODO:WXBizMsgCrypt
        }
        return $sReplyMsg;
    }

    /**
     * 组装xml数据
     * @param \SimpleXMLElement $xml
     * @param array $data
     * @param string $item 元素名称
     */
    private function _data2xml(\SimpleXMLElement $xml,array $data, $item = 'item') {
        foreach ( $data as $key => $value ) {
            $isnum = is_numeric($key);
            if (is_array($value ) or is_object($value)) {
                $child = $xml->addChild ( $isnum?$item:$key );
                $this->_data2xml ( $child, $value, $item );
            } else {
                if ($isnum) {
                    $xml->addChild ( $key, $value );
                } else {
                    $child = $xml->addChild ( $key );
                    $node = dom_import_simplexml ( $child );
                    $node->appendChild ( $node->ownerDocument->createCDATASection ( $value ) );
                }
            }
        }
    }

    /**
     * 回复第三方接口消息
     * @param $url
     * @param $rawData
     * @return mixed|true|false false on failure
     */
    protected function relayPart3($url, $rawData)     {
        $headers = array("Content-Type: text/xml; charset=utf-8");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * 字节转Emoji表情
     * emoji就是表情符号；词义来自日语（えもじ，e-moji，moji在日语中的含义是字符）
     * 表情符号现已普遍应用于手机短信和网络聊天软件。
     * emoji表情符号，在外国的手机短信里面已经是很流行使用的一种表情。
     * 在国内的微信和微信中也被采用。
     * @param int $cp 例如：0x1F1E8
     * @return string
     */
    protected function bytes_to_emoji($cp)     {
        if ($cp > 0x10000) {       # 4 bytes
            return chr(0xF0 | (($cp & 0x1C0000) >> 18)) . chr(0x80 | (($cp & 0x3F000) >> 12)) . chr(0x80 | (($cp & 0xFC0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else if ($cp > 0x800) {   # 3 bytes
            return chr(0xE0 | (($cp & 0xF000) >> 12)) . chr(0x80 | (($cp & 0xFC0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else if ($cp > 0x80) {    # 2 bytes
            return chr(0xC0 | (($cp & 0x7C0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else {                    # 1 byte
            return chr($cp);
        }
    }


}