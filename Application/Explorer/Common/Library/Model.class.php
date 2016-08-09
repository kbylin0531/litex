<?php
/**
 * Created by PhpStorm.
 * User: linzhv
 * Date: 8/8/16
 * Time: 9:14 PM
 */

namespace Application\Explorer\Common\Library;

/**
 * Class Model
 * @package Application\Explorer\Common
 */
abstract class Model {

    public $db = null;
    public $in;
    public $config;

    /**
     * Model constructor.
     */
    function __construct(){
        global $config, $in;
        $this -> in = $in;
        $this -> config = $config;
    }

    function db(){
        return $this ->db;
    }


}