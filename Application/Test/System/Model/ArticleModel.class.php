<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/24/16
 * Time: 9:19 PM
 */

namespace Application\System\Model;
use PLite\Library\Model;

class ArticleModel extends Model {

    protected $tablename = 'sy_article';

    protected $fields = [
        'title'     => null,
        'content'   => null,
        'summary'   => null,
        'author'    => null,
        'ctime'     => null,
        'etime'     => null,
        'category'  => null,
        'thumb'     => null,//thumbnail
    ];

    public function add(array $info){
        $data = $this->data($info);
        return $this->fields($data)->create();
    }

    public function lists($fields = null,array $where = null){
        $fields and $this->fields($fields);
        $where and $this->where($where);
        return $this->select();
    }

    public function remove($id){
        return $this->where($id)->delete();
    }

    public function edit($id,array $info){
        return $this->fields($info)->where('id = '.intval($id))->$this->update();
    }

}