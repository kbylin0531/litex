<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/25/16
 * Time: 9:08 PM
 */

namespace Application\Admin\Controller;

class Directory extends Admin {

    public function chat(){
        $this->display();
    }
    /**
     * 电话簿
     */
    public function directory(){
        $this->display();
    }
}