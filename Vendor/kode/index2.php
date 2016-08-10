<?php


    $_COOKIE = stripslashes_deep($_COOKIE);
    $_GET	 = stripslashes_deep($_GET);
    $_POST	 = stripslashes_deep($_POST);
    $in = array_merge($_GET,$_POST);
    $remote = isset($in[0])?$in[0]:null;
    $remote = explode('/',trim($remote[0],'/'));
    $GLOBALS['in']['URLremote'] = $remote;

    if(isset($in['PHPSESSID'])){//office edit post
        session_id($in['PHPSESSID']);
    }

    @session_start();
    check_post_many();
    session_write_close();//避免session锁定问题;之后要修改$_SESSION 需要先调用session_start()

    //APP begin
    init_lang();
    init_setting();
    (new Application())->run();