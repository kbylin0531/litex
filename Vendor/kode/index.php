<?php

    include(CORER_DIR.'Application.class.php');
//    include(BASIC_PATH.'config/setting.php');
    $config = include PATH_BASE.'/Application/Explorer/Common/config.php';

    $_COOKIE = stripslashes_deep($_COOKIE);
    $_GET	 = stripslashes_deep($_GET);
    $_POST	 = stripslashes_deep($_POST);
    $in = array_merge($_GET,$_POST);
    $remote = array_get($in,0);
    $remote = explode('/',trim($remote[0],'/'));
    $in['URLremote'] = $remote;

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