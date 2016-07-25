<?php

/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/23/16
 * Time: 6:14 PM
 */
namespace Application\Admin\Model;


use PLite\Library\Model;
use PLite\Util\SEK;

class MaterialImageModel extends Model
{

    protected $tablename = 'sy_material_image';

    protected $fields = [
        'path' => null,
        'url' => null,
        'media_id' => null,
        'aid' => null,
        'title' => null,
        'ctime' => null,
        'description' => null,
    ];

    /**
     * @return MaterialImageModel
     */
    public static function getInstance(){
        return parent::getInstance();
    }

    public function hasMedia($media_id){
        return $this->where([
            'media_id'  => $media_id
        ])->count();
    }

    public function updateMedia($media_id,array $info){
        $data = $this->fields;
        SEK::merge($data,$info);
        SEK::filter($data);
        return $this->fields($data)->where([
            'media_id'  => $media_id
        ])->update();
    }

    /**
     * @param array $info
     * @return int
     */
    public function add(array $info){
        $data = $this->fields;
        SEK::merge($data,$info);
        SEK::filter($data);
        $data['ctime'] = REQUEST_TIME;
        return $this->fields($data)->create();
    }

    /**
     * @param $id
     * @return bool
     */
    public function del($id){
        return $this->where('id = ' . intval($id))->delete();
    }

    /**
     * @param array $info
     * @return array|bool
     */
    public function get(array $info){
        $data = $this->fields;
        SEK::merge($data,$info);
        SEK::filter($data);
        return $this->where($data)->select();
    }


}