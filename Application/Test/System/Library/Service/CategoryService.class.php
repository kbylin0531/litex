<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/15/16
 * Time: 12:28 PM
 */

namespace Application\System\Library\Service;


use PLite\Library\Model;

class CategoryService extends Model{

    protected $tablename = 'sy_category';

    public function getCategoryList(){
        return $this->select();
    }



}