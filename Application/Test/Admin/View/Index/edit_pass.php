<?
	include("../include/config.inc.php");
	if(!$_SESSION['LoginOK']) ToURL("/manage/login.php");
	
	MysqlConn();
	if(empty($_REQUEST['action'])) $action = "modify";
	$file_title = "密码";
	
	function GetAction($action) {
		global $file_title;
		if($action == "modify") return "修改".$file_title;
	}
	
	function showForm() {
		global $PHP_SELF,$MYSQL,$file_title,$dbpre;
?>
<script type="text/javascript" language="javascript">
<!--
function trim(str){
	return str.replace(/^\s*|\s*$/g,"");
}
function checkFormAction(){
	newpass = trim($("#newpass").val());
	confirmpass = trim($("#confirmpass").val());
	if(newpass==''){
		$("#tip").html("*请输入新<?=$file_title?>");
		$("#newpass").focus();
		return false;
	}
	if(confirmpass==''){
		$("#tip").html("*请输入确认<?=$file_title?>");
		$("#confirmpass").focus();
		return false;
	}
	if(newpass != confirmpass){
		$("#tip").html("*输入的密码不一致，请重新输入");
		$("#confirmpass").focus();
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
	
		<li><label>新<?=$file_title?>：<b>*</b></label><input name="newpass" id="newpass" type="password" class="dfinput" value="<?=$newspass?>" /></li>
		<li><label>确认<?=$file_title?>：<b>*</b></label><input name="confirmpass" id="confirmpass" type="password" class="dfinput" value="<?=$confirmpass?>" /></li>
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
			<li><a href="edit_pass.php"><?=$file_title?>修改</a></li>
		</ul>
    </div>
<?
if($action == "modify") {
	if(isset($_REQUEST['change'])){
		$confirmpass = md5($_REQUEST['confirmpass']);
		
		$where = "";
        $qry_update = "update ".$dbpre."manager set passwd='$confirmpass' ".$where." where id=".$_SESSION['ManageId'];
        if(@$MYSQL->query($qry_update))
			ErrTo($file_title."修改成功，请退出后重新登录!","login.php?op=logout");
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