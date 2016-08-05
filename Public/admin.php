<?php
/**
 * User: linzh<784855684@qq.com>
 * Datetime: 8/3/16 9:45 AM
 */
const DEBUG_MODE_ON = true;
const PAGE_TRACE_ON = true;
const LITE_ON = false;
const INSPECT_ON = false;

include '../PLite/entry.php';
PLite::init();
$s = new \PLite\Extension\Sphinx\SphinxClient();
$s->SetServer("localhost", 6712);
//$s->SetMatchMode(SPH_MATCH_ANY);
$s->SetMaxQueryTime(3);

$result = $s->Query("test");

var_dump($result);