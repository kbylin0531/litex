<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/25/16
 * Time: 9:10 PM
 */

namespace Application\Test\Demo\Controller;


use PLite\Library\Controller;

class Form extends Controller
{

    public function advance(){
        $this->display();
    }
    public function layout(){
        $this->display();
    }
    public function validation(){
        $this->display();
    }
    public function wizard(){
        $this->display();
    }
}