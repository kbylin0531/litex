<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/26/16
 * Time: 7:57 PM
 */

namespace Application\Admin\Model;
use PLite\Library\Model;

class WebsiteModel extends Model {

    protected $tablename = 'lx_website';
    protected $fields = [
        'name'          => null,
        'value'         => null,
        'title'         => null,
        'description'   => null,
    ];

//---------------------------------------- for general query -------------------------------------------------------------------------------//
    /**
     * @param bool $name_as_key
     * @return array|false
     */
    public function lists($name_as_key=false){
        $list = $this->select();
        if($list and $name_as_key){
            $temp = [];
            foreach ($list as $item){
                $temp[$item['name']] = $item['value'];
                unset($item['name']);
            }
            $list = $temp;
        }
        return $list;
    }
    /**
     * @param bool $format
     * @return array|false
     */
    public function getSideMenu($format=false){
        $list = $this->table('lx_menu')->where('type = 1')->order('`order` desc')->select();
        if($list and $format){
            $temp = [];
            foreach ($list as $item){
                $parent = $item['parent'];
                $id = $item['id'];
                unset($item['parent']);
                if($parent){
                    // is_sub
                    if(isset($temp[$parent])){
                        empty($temp[$parent]['children']) and $temp[$parent]['children'] = [];
                    }else{
                        $temp[$parent] = ['children'=>[]];
                    }
                    $temp[$parent]['children'][] = $item;
                }else{
                    //is top
                    if(isset($temp[$id])){
                        //has set children
                        $temp[$id] = array_merge($temp[$id],$item);//because item do not contain children
                    }else{
                        $temp[$id] = $item;
                    }
                }
            }
            $list = $temp;
        }
        return $list;
    }

    /**
     * @return array|false
     */
    public function getUserMenu(){
        $list = $this->table('lx_menu')->where('type = 2')->order('`order` desc')->select();
        return $list;
    }

//---------------------------------------- for management -------------------------------------------------------------------------------//

    public function revise(){

    }


}