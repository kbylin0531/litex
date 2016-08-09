<?
include("../include/config.inc.php");
if (!$_SESSION['LoginOK']) ToURL("/manage/login.php");

MysqlConn();
if (empty($_REQUEST['action'])) $action = "list";
else $action = $_REQUEST['action'];

$catg_code = $_REQUEST['catg_code'];

function GetAction($action)
{
    if ($action == "add") return "添加栏目";
    else if ($action == "modify") return "修改栏目";
    else if ($action == "list") return "栏目列表";
    else if ($action == "remove") return "删除栏目";
}
    ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?= $system_title ?></title>

</head>

</html>