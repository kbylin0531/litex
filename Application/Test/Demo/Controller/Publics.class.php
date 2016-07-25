<?php

/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/25/16
 * Time: 11:29 AM
 */
namespace Application\Test\Demo\Controller;

use PLite\Library\Controller;

class Publics extends Controller{

    public function login(){
        $this->display();
    }

    public function lockScreen(){
        $this->display();
    }

    public function show404(){
        $this->display('404');
    }

    public function show500(){
        $this->display('500');
    }

    public function boxView(){
        $this->display();
    }

}