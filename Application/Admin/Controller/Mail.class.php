<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/25/16
 * Time: 9:10 PM
 */

namespace Application\Admin\Controller;

class Mail extends Admin {

    public function lists(){
        $this->display();
    }

    /**
     * compose an email
     */
    public function compose(){
        $this->display();
    }

    public function detail(){
        $this->display();
    }
}