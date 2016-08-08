<?php
/**
 * Created by PhpStorm.
 * User: linzhv
 * Date: 16-8-8
 * Time: 上午11:20
 */

namespace Application\Test\Controller;


class Env {

    public function info(){
        phpinfo();
    }

    public function test(){
        if (extension_loaded('gd')){
            $gdinfo=gd_info();
            if( $gdinfo['FreeType Support'])
                echo "GD done,and FreeType done ";
            else
                echo 'GD done,but FreeType none!';
        }else{
            echo "gd not found ";
        }
    }

}