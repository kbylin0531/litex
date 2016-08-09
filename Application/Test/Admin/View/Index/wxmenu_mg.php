<?
	include("../include/config.inc.php");
	if(!$_SESSION['LoginOK']) ToURL("/manage/login.php");
	
	MysqlConn();
	if(empty($_REQUEST['action'])) $action = "list";
	else $action = $_REQUEST['action'];
	
	$file_table_name = "wxmenu";
	$file_name = "wxmenu";
	$file_title = "自定义菜单";
	
	function GetAction($action) {
		global $file_table_name,$file_name,$file_title;
		if($action == "add") return "添加".$file_title;
		else if($action == "modify") return "修改".$file_title;
		else if($action == "list") return $file_title."列表";
		else if($action == "remove") return "删除".$file_title;
	}
	
	function showForm($action) {
		global $PHP_SELF,$MYSQL,$SiteImgMsg,$file_table_name,$file_name,$file_title,$dbpre,$id,$menucode,$title,$keywords,$linkurl,$ps,$ifenable,$addtime;
		$id = $_REQUEST['id'];
		$ps = 0;
		$ifenable = "Y";
		if($action == "modify"){
			$query = $MYSQL->query("select * from ".$dbpre.$file_table_name." where id=".$id);
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
	title = trim($("#title").val());
	keywords = trim($("#keywords").val());
	ps = trim($("#ps").val());
	if(title==''){
		$("#tip").html("*请输入主菜单名称");
		$("#title").focus();
		return false;
	}
	if(keywords==''){
		$("#tip").html("*请输入关键字");
		$("#keywords").focus();
		return false;
	}
	if(ps==''){
		$("#tip").html("*请输入排序");
		$("#ps").focus();
		return false;
	}
}
-->
</script>
<form name="form1" enctype="multipart/form-data" action="<?=$PHP_SELF?>" method="post" onSubmit="return checkFormAction();">
<input type="hidden" name="action" value="<?=$action?>" />
<? if($action == "modify"){ ?>
<input type="hidden" name="id" value="<?=$id?>" />
<? } ?>
<div class="formbody">
	<ul class="forminfo">
		<li id="tip" class="jserror"></li>
		
		<li><label>选择菜单</label><div class="vocation">
			<select name="menucode" id="menucode" class="neisel">
				<option value="0">根目录</option>
				<?
					$sql_menu = "select id,title from ".$dbpre."wxmenu where menucode = 0 order by ps desc,id asc";
					$query_menu = @$MYSQL->query($sql_menu);
					while ($row_menu = @mysql_fetch_array($query_menu)){
				?>
				<option value="<?=$row_menu["id"]?>" <? if ($menucode==$row_menu["id"]) echo "selected"; ?>><?=$row_menu["title"]?></option>
				<?
					}
				?>
			</select>
		</div></li>
		<li><label>主菜单名称<b>*</b></label><input name="title" id="title" type="text" class="dfinput" value="<?=$title?>" /></li>	
		<li><label>触发关键词<b>*</b></label><input name="keywords" id="keywords" type="text" class="dfinput" value="<?=$keywords?>" /></li>	
		<li><label>外链地址</label><input name="linkurl" id="linkurl" type="text" class="dfinput" value="<?=$linkurl?>" style="width:500px;" /></li>
		<li><label>排序<b>*</b></label><input name="ps" id="ps" type="text" class="dfinput" value="<?=$ps?>" style="width:100px;" /></li>
		<li><label>是否启用</label><cite><input type="radio" name="ifenable" value="Y" <? if ($ifenable =="Y") echo "checked"; ?> />是&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="ifenable" value="N" <? if ($ifenable =="N") echo "checked"; ?> >否</cite></li>
		<li><label>&nbsp;</label><input name="<?=$action?>" type="submit" class="btn" value="确认保存"/></li>
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
<script type="text/javascript">
$(document).ready(function(e) {
    $(".neisel").uedSelect({
		width : 345			  
	});
	$(".srhsel").uedSelect({
		width : 150
	});
});
</script>
</head>
<body>
	<div class="place">
    	<span>位置：</span>
		<ul class="placeul">
			<li><a href="<?=$file_name?>_mg.php"><?=$file_title?>管理</a></li>
			<li><?=GetAction($action)?></li>
		</ul>
    </div>
    
    <div class="rightinfo">
	
		<?
		if($action == "list"){
		?>
		
		<div class="tools">
			<ul class="toolbar">
				<a href="<?=$PHP_SELF."?action=add"?>"><li><span><img src="images/t01.png" /></span>添加</li></a>
				<a onClick="document.delForm.action.value='AllDel';delForm.submit();"><li><span><img src="images/t03.png" /></span>删除</li></a>
				<a href="<?=$file_name?>_create.php"><li><span><img src="images/t06.png" /></span>发布菜单</li></a>
				<a href="<?=$file_name?>_chanel.php"><li><span><img src="images/t07.png" /></span>撤销菜单</li></a>
			</ul>
		</div>
    
		<form name="delForm" method="post" action="<?=$PHP_SELF?>">
		<input type="hidden" name="action" value="" />
		
		<table class="tablelist">
			<thead>
			<tr>
			<th width="34"><input name="chkAll" type="checkbox" onClick="CheckAll(delForm)" value="checkbox" /></th>
			<th width="60">排序</th>
			<th>主菜单名称</th>
			<th width="150">触发关键字</th>
			<th width="100">是否启用</th>
			<th width="120">操作</th>
			</tr>
			</thead>
			<tbody>
			<? 
				$query = $MYSQL->query("select id,title,keywords,ps,ifenable from ".$dbpre.$file_table_name." where 1=1 and menucode = 0 order by ps desc,id asc");
				while($row = mysql_fetch_array($query)){
					$ifenable = "";
					if (trim($row["ifenable"])=="Y"){
						$ifenable = "启用";
					}else{
						$ifenable = "<span style='color:#FF0000'>不启用</span>";
					}
			?>
				<tr>
				<td><input type="checkbox" name="cinfo[]" value="<?=$row["id"]?>" /></td>
				<td><?=$row["ps"]?></td>
				<td><strong><?=$row["title"]?></strong></td>
				<td><?=$row["keywords"]?></td>
				<td><?=$ifenable?></td>
				<td class="operate">
					<a href="<?=$PHP_SELF?>?action=modify&id=<?=$row["id"]?>" class="tablelink">修改</a>&nbsp;|&nbsp;
					<a href="<?=$PHP_SELF?>?action=remove&id=<?=$row["id"]?>" onClick="return confirmDel();" class="tablelink"> 删除</a></td>
				</td>
				</tr>
				
				<?
					$queryt = $MYSQL->query("select id,title,keywords,ps,ifenable from ".$dbpre.$file_table_name." where menucode = ".$row["id"]." order by ps desc,id asc");
					while($rowt = mysql_fetch_array($queryt)){
						$ifenablet = "";
						if (trim($rowt["ifenable"])=="Y"){
							$ifenablet = "启用";
						}else{
							$ifenablet = "<span style='color:#FF0000'>不启用</span>";
						}
				?> 
				<tr>
				<td><input type="checkbox" name="cinfo[]" value="<?=$rowt["id"]?>" /></td>
				<td><?=$rowt["ps"]?></td>
				<td>　　├&nbsp;<?=$rowt["title"]?></td>
				<td><?=$rowt["keywords"]?></td>
				<td><?=$ifenablet?></td>
				<td class="operate">
					<a href="<?=$PHP_SELF?>?action=modify&id=<?=$rowt["id"]?>" class="tablelink">修改</a>&nbsp;|&nbsp;
					<a href="<?=$PHP_SELF?>?action=remove&id=<?=$rowt["id"]?>" onClick="return confirmDel();" class="tablelink"> 删除</a></td>
				</td>
				</tr>
				<? } ?>
			<? } ?>	     
			</tbody>
		</table>
		<script type="text/javascript">
		$('.tablelist tbody tr:odd').addClass('odd');
		</script>
		</form>
    	<? } ?>
    </div>
	
	
<?
if($action == "add"){
    if(isset($_REQUEST['add'])){
		$menucode = $_REQUEST['menucode'];
		$title = $_REQUEST['title'];
		$keywords = $_REQUEST['keywords'];
		$linkurl = $_REQUEST['linkurl'];
		$ps = $_REQUEST['ps'];
		$ifenable = $_REQUEST['ifenable'];
		
 		if(@$MYSQL->query("insert into ".$dbpre.$file_table_name." (menucode,title,keywords,linkurl,ps,ifenable) values ('$menucode','$title','$keywords','$linkurl',$ps,'$ifenable')",$db)){
        	$id = $MYSQL->insert_id();
           	echo "<br><br><br><center>".$file_title." <b>".$title."</b> 添加成功!</cetner>";
        }else
        	ErrBack("出现错误！".$file_title."未添加成功。");
		echo "<br><a href=\"".$file_name."_mg.php\">返回".$file_title."列表</a>";
	} else {
    	showForm("add");
	}
}
if($action == "modify") {
	$id = $_REQUEST['id'];
	if(isset($_REQUEST['modify'])){
		$menucode = $_REQUEST['menucode'];
		$title = $_REQUEST['title'];
		$keywords = $_REQUEST['keywords'];
		$linkurl = $_REQUEST['linkurl'];
		$ps = $_REQUEST['ps'];
		$ifenable = $_REQUEST['ifenable'];
		
		$where = "";
        $qry_update = "update ".$dbpre.$file_table_name." set menucode='$menucode',title='$title',keywords='$keywords',linkurl='$linkurl',ps=$ps,ifenable='$ifenable' ".$where." where id=$id";
        if(@$MYSQL->query($qry_update))
			ErrTo($file_title." ".$title." 修改成功!",$file_name."_mg.php");
        else{
         	ErrBack("服务器忙,暂时无法处理数据!!请与管理员联系!!");
        }
	} else {
        if($id){
        	showForm("modify");
        }else{
            ErrBack("没有指定".$file_title."信息ID");
		}
    }
}
if($action == "AllDel") {
	$cinfo = $_REQUEST['cinfo'];
	if (is_array($cinfo)) {
		foreach($cinfo as $value){
			$where.= " or id='".$value."'";
		}
		if(empty($where)) die("<script>alert('请选择要删除的信息!');history.go(-1);</script>");
		if($MYSQL->query("delete from ".$dbpre.$file_table_name." where 1<>1 ".$where." ")){
			ErrTo("您所选的信息已成功删除",$file_name."_mg.php");
		} else {
			echo "<script>alert('出现错误！信息删除未成功。');history.go(-1);</script>";
		}
	} else {
		echo "<script>alert('请选择要删除的信息!');history.go(-1);</script>";
	}
}
if($action == "remove") {
	$id = $_REQUEST['id'];
   	if($MYSQL->query("delete from ".$dbpre.$file_table_name." where id=".$id))
     	ErrTo("信息已删除",$file_name."_mg.php");
   	else
     	ErrBack("出现错误，信息无法删除。");
}
?>
</body>
</html>