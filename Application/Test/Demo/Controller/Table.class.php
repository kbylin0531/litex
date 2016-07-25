<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/25/16
 * Time: 1:36 PM
 */

namespace Application\Test\Demo\Controller;


use PLite\Library\Controller;

class Table extends Controller {

    public function dynamic(){
        $this->display();
    }


    public function basic(){
        $this->display();
    }
    public function responsive(){
        $this->display();
    }

    public function editable(){
        $this->display();
    }



}