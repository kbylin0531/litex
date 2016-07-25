<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/15/16
 * Time: 8:19 PM
 */

namespace Application\System\Model;
use PLite\Library\Model;
use PLite\Util\SEK;

/**
 * Class AccountModel 公众号模型
 * @package Application\Wechat\Model
 */
class AccountModel extends Model {

    protected $tablename = 'sy_account';
    protected $fields = [
        'uid'                   => null,
        'name'                  => null,
        'origin_id'             => null,
        'wechat'                => null,
        'token'                 => null,
        'type'                  => null,
        'appid'                 => null,
        'appsecret'             => null,
        'encodingaeskey'        => null,
        'access_token'          => null,
        'access_token_expire'   => null,
    ];

    /**
     * 获取该用户管理的公众号列表
     * @param int $uid 用户ID
     * @return bool|mixed
     */
    public function getAccountList($uid){
        $list = $this->where('uid = '.intval($uid))->select();
        return $list;
    }

    /**
     * @return AccountModel
     */
    public static function getInstance(){
        return parent::getInstance();
    }

    /**
     * 根据ID获取公众号信息
     * @param int $id 公众号ID
     * @return false|array
     */
    public function getAccountById($id){
        return $this->where('id = '.intval($id))->find();
    }

    /**
     * 创建账户
     * @param array $info 创建信息
     * @return false|int 返回false表示发生了错误，返回int表示创建的用户ID
     */
    public function createAccount(array $info){
        $data = $this->fields;
        SEK::merge($data,$info,false);
        $result = $this->fields($data)->create();
        if(!$result){
            return false;
        }else{
            return $this->lastInsertId();
        }
    }

    public function updateAccount($id,array $info){
        $data = $this->fields;
        SEK::merge($data,$info,false);
        SEK::filter($data,null);
        return $this->fields($data)->where('id = '.intval($id))->update();
    }

}