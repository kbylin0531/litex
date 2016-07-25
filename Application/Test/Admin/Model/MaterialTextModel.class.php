<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/23/16
 * Time: 10:39 PM
 */

namespace Application\Admin\Model;
use PLite\Library\Model;

class MaterialTextModel extends Model {

    protected $tablename = 'sy_material_text';

    protected $accoutid = null;

    public function __construct($id){
        parent::__construct();
        $this->accoutid = intval($id);
    }

    /**
     * 添加文本素材
     * @param string $content 文本内容
     * @return int 文本内容ID
     */
    public function createText($content){
        return $this->fields([
            'aid'       => $this->accoutid,
            'content'   => $content,
            'mtime'     => REQUEST_TIME,
        ])->create();
    }

    public function countTextList(){
        return $this->where('aid ='.$this->accoutid)->count();
    }

    /**
     * @param $id
     * @return array|false
     */
    public function getText($id){
        $aid = $this->accoutid;
        return $this->where(" id = {$id} and aid = {$aid} ")->find();
    }

    /**
     * 获取文本内容
     * @param int $offset
     * @param int $limit
     * @param string|null $search TODO:multi key
     * @return array|bool
     */
    public function getTextList($offset=0,$limit=10,$search=null){
        if($search){
            $where = 'aid = '.$this->accoutid.' and content like \'%'.$search.'%\'';
        }else{
            $where = 'aid = '.$this->accoutid;
        }
//        \Soya\dumpout($where);
        $result = $this->where($where)->limit($limit,$offset)->order('mtime desc')->select();
        if(false === $result){
            return false;
        }else{
            return $result;
        }
    }

    /**
     * 修改文本内容
     * @param string $id
     * @param string $content
     * @return bool mysql判断是否更新成功依据内容是否发生变化，只要不出现错误都可以认为是修改成功的
     */
    public function updateText($id,$content){
        $aid = $this->accoutid;
        $id = intval($id);
        $result = $this->fields([
            'content'=> $content,
            'mtime'  => REQUEST_TIME,])->where(" id = {$id} and aid = {$aid} ")->update();
        if(false === $result){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 删除文本内容
     * @param int $id
     * @return bool
     */
    public function deleteText($id){
        $aid = $this->accoutid;
        $id = intval($id);
        $result = $this->where(" id = {$id} and aid = {$aid} ")->delete();
        if(false === $result){
            return false;
        }else{
            return true;
        }
    }
}