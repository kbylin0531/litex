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
    \PLite\Debugger::closeTrace();
    if(isset($_GET['echostr'])){
        //valid
        if($wechat->checkSignature()){
            exit($_GET['echostr']);
        }
    }else{
        $wechat->receive() and $wechat->response(function($type,$entity)use($wechat){
//                    $content = "消息类型是'$type':   \n消息体：";
            return $wechat->responseImage('4xTsGsBzxKorv-03Tn1Zq-lCcIIQSublVuDS2ToYtHg');
//                    return $wechat->responseText($content."XSDSDSDS");
        });
    }
    exit();
}


PLite::start();