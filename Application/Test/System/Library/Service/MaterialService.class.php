<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/22/16
 * Time: 1:57 PM
 */

namespace Application\System\Library\Service;
use PLite\Core\Storage;
use PLite\Util\Helper\Network;
use PLite\Util\SEK;

/**
 * Class MaterialService 素材管理接口
 * 注意：
 * 1、新增的永久素材也可以在公众平台官网素材管理模块中看到
 * 2、永久素材的数量是有上限的，请谨慎新增。图文消息素材和图片素材的上限为5000，其他类型为1000
 * 3、素材的格式大小等要求与公众平台官网一致。具体是，图片大小不超过2M，支持bmp/png/jpeg/jpg/gif格式，
 *    语音大小不超过5M，长度不超过60秒，支持mp3/wma/wav/amr格式
 *
 *
 * @package Application\Wechat\Common\Library
 */
class MaterialService extends WechatService {

    //媒体文件类型，分别有图片（image）、语音（voice）、视频（video）和缩略图（thumb）
    const MEDIA_TYPE_IMAGE = 'image';
    const MEDIA_TYPE_VOICE = 'voice';
    const MEDIA_TYPE_VIDEO = 'video';
    const MEDIA_TYPE_THUMB = 'thumb';

//---------------------------------- 添加素材 ----------------------------------------------------------------//
    /**
     * 新增永久图文素材
     * {
     * "articles": [{
     *  "title": TITLE,
     *  "thumb_media_id": THUMB_MEDIA_ID, //图文消息的封面图片素材id（必须是永久mediaID）
     *  "author": AUTHOR,
     *  "digest": DIGEST, //图文消息的摘要，仅有单图文消息才有摘要，多图文此处为空
     *  "show_cover_pic": SHOW_COVER_PIC(0 / 1), //是否显示封面，0为false，即不显示，1为true，即显示
     *  "content": CONTENT, //图文消息的具体内容，支持HTML标签，必须少于2万字符，小于1M，且此处会去除JS
     *  "content_source_url": CONTENT_SOURCE_URL //图文消息的原文地址，即点击“阅读原文”后的URL
     *  },
     *  //若新增的是多图文素材，则此处应还有几段articles结构
     *  ]}
     *
     * 注意：在图文消息的具体内容中，将过滤外部的图片链接，开发者可以通过下述接口上传图片得到URL，放到图文内容中使用
     *
     * @param array $articles 文章列表
     * @return string 返回的即为新增的图文消息素材的media_id
     */
    public function addNews(array $articles){
        $url = 'https://api.weixin.qq.com/cgi-bin/material/add_news?access_token='.$this->getAccessToken();
        $items = [
            'title' ,
            'thumb_media_id' ,
            'author' ,
            'digest' ,
            'show_cover_pic' ,
            'content' ,
            'content_source_url' ,
        ];
        //检查有效性
        foreach ($articles as $article) {
            foreach ($items as $item){
                if(empty($article[$item])){
                    $this->error = "添加文章时'$item'不能为空！";
                    return false;
                }
            }
        }
        $result = Network::postFile($url,$articles);
        if (empty($result['errcode'])) {
            return $result['media_id'];
        }else{
            $this->error = $result['errmsg'];
            return false;
        }
    }

    /**
     * 上传文章图片
     * @param array $media
     * @return bool |string 返回上传图片的url，可用于后续群发中，放置到图文消息中
     */
    public function addNewsImg($media){
        $url = 'https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$this->getAccessToken();

        //form-data中媒体文件标识，有filename、filelength、content-type等信息
        $result = Network::post($url,$media);
        if (empty($result['errcode'])) {
            return $result['url'];
        }else{
            $this->error = $result['errmsg'];
            return false;
        }
    }

    /**
     * 新增其他类(非news)型永久素材
     * @param string $type 媒体文件类型，分别有图片（image）、语音（voice）、视频（video）和缩略图（thumb）
     * @param string $path filepath
     * @param array $others other parameters
     * @return bool  {"":MEDIA_ID,"":URL} 或者 media_id
     *              media_id:新增的永久素材的media_id
     *              url:新增的图片素材的图片URL（仅新增图片素材时会返回该字段）
     */
    private function _addMaterial($type,$path,array $others=null){
        $at = $this->getAccessToken();
        $url = "api.weixin.qq.com/cgi-bin/material/add_material?access_token={$at}";
        $others or $others = [];
        $others['type'] = $type;
        if(!is_readable($path)){
            $this->error = "File '$path' unreadable,it may not exist!";
            return false;
        }
        $result = Network::postFile($url,$path,$others);
        if (empty($result['errcode'])) {
            return json_decode($result,true);
        }else{
            $this->error = $result['errmsg'];
            return false;
        }
    }

    /**
     * @param $type
     * @param int $offset
     * @param int $count
     * @return bool|array
     */
    private function _getMaterialListByType($type,$offset=0,$count=20){
        $accessToken = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$accessToken;
        $params = [
            'type'  =>$type,
            'offset'=>$offset,
            'count' =>$count,
        ];
        $result = Network::post($url,$params);
        \PLite\dumpout($result);
        if(false === $result){
            $this->error = 'Failed to post data!';
        }elseif(!empty($result['errcode'])){
            $this->error = $this->getErrorInfo($result['errcode']);
        }else{
            return $result;
        }
        return false;
    }

    /**
     * 上传语音资源
     * @param string $path 文件路径
     * @return false|array 返回media_id ，发生错误时返回false并设置错误信息
     */
    public function addVoice($path){
        $type = self::MEDIA_TYPE_VOICE;
        if(!is_readable($path)){
            $this->error = "file '{$path}' unreadable!";
            return false;
        }
        $result = $this->_addMaterial($type, [
            'media' => '@' .$path,
        ]);
        if (false === $result) {
            $this->error = $result['errmsg'];
            return false;
        }
        return $result;
    }
    /**
     * 上传缩略图资源
     * @param string $path 文件路径
     * @return false|string 返回media_id，发生错误时返回false并设置错误信息
     */
    public function addThumb($path){
        $type = self::MEDIA_TYPE_THUMB;
        $params = [
            'type'  => $type,
            'media' => '@' . realpath ( $path ),
        ];
        $result = $this->_addMaterial($type,$params);
        if (false === $result) {
            $this->error = $result['errmsg'];
            return false;
        }
        return $result['media_id'];
    }

    /**
     * 上传图片资源
     * @param string $path 文件路径
     * @return false|array ['media_id'=>'','url'=>''],发生错误时返回false并设置错误信息
     */
    public function addImage($path){
        $type = self::MEDIA_TYPE_IMAGE;
        $result = $this->_addMaterial($type,$path);
        \PLite\dumpout($result);
        if(empty($result['errmsg'])){
            return $result;
        }elseif($result){/* is array */
            $this->error = $result['errmsg'];
        }
        return false;
    }

    /**
     * 上传视频资料
     * @param string $path 视频的路径
     * @param string $title 标题
     * @param string $introduction 简介
     * @return false|string 返回media_id，发生错误时返回false并设置错误信息
     */
    public function addVideo($path,$title,$introduction=''){
        $type = self::MEDIA_TYPE_VIDEO;
        $params = [
            'type'  => $type,
            'media' => '@' . realpath ( $path ),
            'description'   => SEK::toJson([
                'title'         => $title,
                'introduction'  => $introduction,
            ]),
        ];
        $result = $this->_addMaterial($type,$params);
        if (false === $result) {
            $this->error = $result['errmsg'];
            return false;
        }
        return $result['media_id'];
    }


//---------------------------------- 获取素材 ----------------------------------------------------------------//

    /**
     * 根据media_id来获取永久素材
     * @param string $media_id 要获取的素材的media_id
     * @return bool|mixed
     */
    public function getMaterial($media_id){
        $url = 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token='.$this->getAccessToken();
        $params = [
            'media_id'  => $media_id,//要获取的素材的media_id
        ];
        $result = Network::postFile($url,$params);
        if (empty($result['errcode'])) {
            return $result;
        }else{
            $this->error = $result['errmsg'];
            return false;
        }
    }

    /**
     * 获取图文素材url
     * 格式：
     * {
     * "news_item"://多图文消息有多篇文章
     * [{
     * "title":TITLE,                           //图文消息的标题
     * "thumb_media_id"::THUMB_MEDIA_ID,        //图文消息的封面图片素材id（必须是永久mediaID）
     * "show_cover_pic":SHOW_COVER_PIC(0/1),    //是否显示封面，0为false，即不显示，1为true，即显示
     * "author":AUTHOR,                         //作者
     * "digest":DIGEST,                         //图文消息的摘要，仅有单图文消息才有摘要，多图文此处为空
     * "content":CONTENT,                       //图文消息的具体内容，支持HTML标签，必须少于2万字符，小于1M，且此处会去除JS
     * "url":URL,                               //图文页的URL
     * "content_source_url":CONTENT_SOURCE_URL  //图文消息的原文地址，即点击“阅读原文”后的URL
     * }...]}
     * @param $media_id
     * @return mixed
     */
    public function getNews($media_id) {
        $news = $this->getMaterial($media_id);
        return $news['news_item'];
    }

    /**
     * 获取视频消息素材
     * 返回格式：
     * {
     *  "title":TITLE,
     *  "description":DESCRIPTION,
     *  "down_url":DOWN_URL,  //下载链接
     * }
     * @param string $media_id
     * @return array
     */
    public function getVideo($media_id){
        $news = $this->getMaterial($media_id);
        return $news['news_item'];
    }

    /**
     * 下载图片资源
     * @param string $media_id
     * @param bool $return 是否讲获取素材的内容返回
     * @return string 直接返回文件内容
     */
    public function getImage($media_id,$return=false){
        $data = $this->getMaterial($media_id);
        if(false !== $data){
            if($return){
                return $data;
            }else{
                $path = 'Public/upload/wechat/image/'.$media_id.'.jpg';
                if(Storage::getInstance()->write(PATH_BASE.$path,$data)){
                    return __ROOT__.'/'.$path;
                }
            }
        }
        return false;
    }

    /**
     * 获取永久素材的列表
     * @param int $offset
     * @param int $count
     * @return array|false
     */
    public function getImageList($offset=0,$count=20){
        return $this->_getMaterialListByType(self::MEDIA_TYPE_IMAGE,$offset,$count);
    }

    /**
     * 下载音频资源
     * @param string $media_id
     * @param bool $return 是否讲获取素材的内容返回
     * @return string 直接返回文件内容
     */
    public function getVoice($media_id,$return=false){
        $data = $this->getMaterial($media_id);
        if(false !== $data){
            if($return){
                return $data;
            }else{
                $path = 'Public/upload/wechat/voice/'.$media_id.'.jpg';
                if(Storage::write(PATH_BASE.$path,$data)){
                    return __ROOT__.'/'.$path;
                }
            }
        }
        return false;
    }

//---------------------------------- 删除素材 ---------------------------------------------------------------------------//
    /**
     * 删除素材
     * @param string $media_id
     * @return bool 删除失败时设置错误信息
     */
    public function deleteMaterial($media_id){
        $url = 'https://api.weixin.qq.com/cgi-bin/material/del_material?access_token='.$this->getAccessToken();
        $params = [
            'media_id'  => $media_id,
        ];
        $result = Network::postFile($url,$params);
        if(intval($result['errcode']) === 0){
            return true;
        }else{
            $this->error = $result['errmsg'];
            return false;
        }
    }

//---------------------------------- 修改素材 ---------------------------------------------------------------------------//
    /**
     * 开发者可以通过本接口对永久图文素材进行修改。
     * @param $media_id
     * @param $articles
     * @param null $index
     */
    public function updateNews($media_id,array $articles,$index=null){
        $url = 'https://api.weixin.qq.com/cgi-bin/material/update_news?access_token='.$this->getAccessToken();
    }
    /**
     * 获取素材总数
     * voice_count	语音总数量
     * video_count	视频总数量
     * image_count	图片总数量
     * news_count	图文总数量
     * @param string $type
     */
    public function getMaterialcount($type=null){
        $url = 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=';
    }

    /**
     * 获取永久素材的列表
     * @param string $type 素材的类型，图片（image）、视频（video）、语音 （voice）、图文（news）
     * @param int $limit 返回素材的数量，取值在1到20之间
     * @param int $offset 从全部素材的该偏移位置开始返回，0表示从第一个素材 返回
     */
    public function batchgetMaterial($type,$limit=20,$offset=0){
        $url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=';

    }

}