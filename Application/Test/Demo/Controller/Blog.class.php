<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/25/16
 * Time: 2:09 PM
 */

namespace Application\Test\Demo\Controller;


use PLite\Library\Controller;

class Blog extends Controller {

    public function detail(){
        $this->display();
    }

    public function lists(){
        $this->display();
    }

}