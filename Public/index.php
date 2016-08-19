<?php
const DEBUG_MODE_ON = false;
const PAGE_TRACE_ON = true;
const LITE_ON = false;
const INSPECT_ON = false;

include '/home/asus/webroot/litex/PLite/entry.php';
//include '../PLite/entry.php';
//include '../PLite/entry.lite.php';

function wechat($id){
    $wechat = new \Application\System\Library\Service\MessageService($id);
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
    'CACHE_URL_ON'      => false,
    'CACHE_PATH_ON'     => true,
    'CONFIGGER' => [],
    'ROUTER'    => [
        'STATIC_ROUTE_ON'   => false,
        'WILDCARD_ROUTE_ON' => true,
        'WILDCARD_ROUTE_RULES'    => [
            '/wechat/[num]'   => 'wechat',
        ],
        'REGULAR_ROUTE_RULES'   => [
            //test in 'http://tool.oschina.net/regex/'
            '\/index.php\?(\w[\w\d_-]+)\/(\w[\w\d_-]+).*'    => function($controller,$action){
                return [
                    'm' => 'Explorer',
                    'c' => $controller,
                    'a' => $action,
                ];
            }
        ],
    ],

]);