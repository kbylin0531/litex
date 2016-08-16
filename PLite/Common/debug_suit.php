<?php
namespace PLite;
function _buildMessage($params,$traces){
    $color='#';$str='9ABCDEF';//随机浅色背景
    for($i=0;$i<6;$i++) $color=$color.$str[rand(0,strlen($str)-1)];
    $str = "<pre style='background: {$color};width: 100%;padding: 10px;margin: 0'><h3 style='color: midnightblue'><b>F:</b>{$traces[0]['file']} << <b>L:</b>{$traces[0]['line']} >> </h3>";
    foreach ($params as $key=>$val) $str .= '<b>Parameter-'.$key.':</b><br />'.var_export($val, true).'<br />';
    return $str.'</pre>';
}
/**
 * @param ... it will return all message debugged if sum of parameters is zero
 * @return string|array
 */
function debug(){
    static $_messages = [];
    if(func_num_args()){
        return $_messages[] = _buildMessage(func_get_args(),debug_backtrace());
    }else{
        return $_messages;
    }
}
/**
 * @param ...
 */
function dump(){
    echo _buildMessage(func_get_args(),debug_backtrace());
}

/**
 * @param ...
 * @return void
 */
function dumpout(){
    echo _buildMessage(func_get_args(),debug_backtrace());
    exit();
}
