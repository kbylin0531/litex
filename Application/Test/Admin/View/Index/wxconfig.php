<?
	include("../include/config.inc.php");
	if(!$_SESSION['LoginOK']) ToURL("/manage/login.php");
	
	MysqlConn();
	if(empty($_REQUEST['action'])) $action = "modify";
	else $action = $_REQUEST['action'];
	
	$file_title = "微信设置";
	$file_table_name = "config";
	$file_name = "wxconfig";
	
	function GetAction($action) {
		global $file_title;
		if($action == "modify") return "修改".$file_title;
	}
	
	function showForm($action) {
		global $PHP_SELF,$MYSQL,$file_title,$file_table_name,$file_name,$dbpre,$token,$apiurl,$appid,$appsecret;
		if($action == "modify"){
			$query = $MYSQL->query("select * from ".$dbpre.$file_table_name." where id=1");
			$row = mysql_fetch_array($query);
			foreach($row as $key =>$value){
				${$key} = $value;
			}
		}
		@reset($HTTP_POST_VARS);
		while (@list($key,$value) = @each($HTTP_POST_VARS)){
			${$key} = $value;
		}
?>
<script type="text/javascript" language="javascript">
<!--
function trim(str){
	return str.replace(/^\s*|\s*$/g,"");
}
function checkFormAction(){
	token = trim($("#token").val());
	apiurl = trim($("#apiurl").val());
	appid = trim($("#appid").val());
	appsecret = trim($("#appsecret").val());
	if(token==''){
		$("#tip").html("*请输入Token");
		$("#token").focus();
		return false;
	}
	if(apiurl==''){
		$("#tip").html("*请输入接口地址");
		$("#apiurl").focus();
		return false;
	}
	if(appid==''){
		$("#tip").html("*请输入AppID");
		$("#appid").focus();
		return false;
	}
	if(appsecret==''){
		$("#tip").html("*请输入AppSecret");
		$("#appsecret").focus();
		return false;
	}
}
-->
</script>
<form name="form1" enctype="multipart/form-data" action="<?=$PHP_SELF?>" method="post" onSubmit="return checkFormAction();">
<input type="hidden" name="action" value="<?=$action?>" />
<div class="formbody">
	<ul class="forminfo">
		<li id="tip" class="jserror"></li>
	
		<li><label>Token<b>*</b></label><input name="token" id="token" type="text" class="dfinput" value="<?=$token?>" /></li>
		<li><label>接口地址<b>*</b></label><input name="apiurl" id="apiurl" type="text" class="dfinput" value="<?=$apiurl?>" /></li>
		<li><label>AppID<b>*</b></label><input name="appid" id="appid" type="text" class="dfinput" value="<?=$appid?>" /></li>
		<li><label>AppSecret<b>*</b></label><input name="appsecret" id="appsecret" type="text" class="dfinput" value="<?=$appsecret?>" /></li>
		<li><label>&nbsp;</label><input name="change" type="submit" class="btn" value="确认保存"/></li>
	</ul>
</div>
</form>
<? } ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?=$system_title?></title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/select.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.idTabs.min.js"></script>
<script type="text/javascript" src="js/select-ui.min.js"></script>
<script type="text/javascript" src="js/common.js"></script>
</head>
<body>
	<div class="place">
    	<span>位置：</span>
		<ul class="placeul">
			<li><a href="<?=$file_name?>.php"><?=$file_title?>修改</a></li>
		</ul>
    </div>
<?
if($action == "modify") {
	if(isset($_REQUEST['change'])){
		$token = $_REQUEST['token'];
		$apiurl = $_REQUEST['apiurl'];
		$appid = $_REQUEST['appid'];
		$appsecret = $_REQUEST['appsecret'];
		
		$where = "";
        $qry_update = "update ".$dbpre.$file_table_name." set token='$token',apiurl='$apiurl',appid='$appid',appsecret='$appsecret' ".$where." where id=1";
        if(@$MYSQL->query($qry_update))
			ErrTo($file_title."修改成功!",$file_name.".php");
        else{
         	ErrBack("服务器忙,暂时无法处理数据!!请与管理员联系!!");
        }
	} else {
        showForm("modify");
    }
}
?>
</body>
</html>