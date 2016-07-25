<?php


return [
    'STATIC_ROUTE_ON'   => false,
    'WILDCARD_ROUTE_ON' => true,
    'WILDCARD_ROUTE_RULES'    => [
        '/wechat/[num]'   => function($id){
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
        },
    ],
];