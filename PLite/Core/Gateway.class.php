<?php
/**
 * Created by PhpStorm.
 * User: linzhv
 * Date: 8/15/16
 * Time: 7:09 PM
 */

namespace PLite\Core;

/**
 * Class Gateway
 * @package PLite\Core
 */
class Gateway {

    protected static $_blacklist = [];

    protected static $_whitelist = [];

    public static function check($remoteip){}

    public static function addBlacklist($remoteip){}

    public static function removeBlacklist($remoteip){}

    public static function addWhitelist($remoteip){}

    public static function removeWhitelist($remoteip){}



}