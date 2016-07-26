<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/25/16
 * Time: 9:52 PM
 */

namespace Application\Admin\Controller;

/**
 * Class Website manage the website
 * @package Application\Admin\Controller
 */
class Website extends Admin {

    public function info(){
        $this->show();
    }

    public function menu(){
        $this->display();
    }

}