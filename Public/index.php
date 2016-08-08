<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/13/16
 * Time: 6:24 PM
 */

const DEBUG_MODE_ON = true;
const PAGE_TRACE_ON = true;
const LITE_ON = false;
const INSPECT_ON = false;

include '../PLite/entry.php';

function wechat($id){
    $wechat = new \Application\System\Library\Service\MessageService($id);
    \PLite\Util\ExtDebugger::closeTrace();
    if(isset($_GET['echostr'])){//valid
        if($wechat->checkSignature()){
            exit($_GET['echostr']);
        }
    }else{
        $wechat->receive() and $wechat->response(function()use($wechat){
            return $wechat->responseImage('4xTsGsBzxKorv-03Tn1Zq-lCcIIQSublVuDS2ToYtHg');
        });
    }
    exit();
}

PLite::start([
    'CONFIGGER' => [],
    'ROUTER'    => [
        'STATIC_ROUTE_ON'   => false,
        'WILDCARD_ROUTE_ON' => true,
        'WILDCARD_ROUTE_RULES'    => [
            '/wechat/[num]'   => 'wechat',
        ],
    ],

]);