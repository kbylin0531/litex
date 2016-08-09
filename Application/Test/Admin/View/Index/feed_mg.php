<?
	include("../include/config.inc.php");
	if(!$_SESSION['LoginOK']) ToURL("/manage/login.php");
	
	MysqlConn();
	if(empty($_REQUEST['action'])) $action = "list";
	else $action = $_REQUEST['action'];
	
	$file_table_name = "feed";
	$file_title = "留言";
	
	$page = intval($_REQUEST['page']);
	$keyword = $_REQUEST['keyword'];
	
	function GetAction($action) {
		global $file_table_name,$file_title;
		if($action == "modify") return "查看".$file_title;
		else if($action == "list") return $file_title."列表";
		else if($action == "remove") return "删除".$file_title;
	}
	
	function showForm($action) {
		global $PHP_SELF,$MYSQL,$file_table_name,$file_title,$dbpre,$page,$keyword,$id,$title,$name1,$tel,$email,$content,$addtime;
		$id = $_REQUEST['id'];
		if($action == "modify"){
			$query = $MYSQL->query("select *,date_format(addtime,'%Y-%m-%d %H:%i') as day from ".$dbpre.$file_table_name." where id=".$id);
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
<form name="form1" enctype="multipart/form-data" action="<?=$PHP_SELF?>" method="post" onSubmit="return checkFormAction();">
<input type="hidden" name="action" value="<?=$action?>" />
<input type="hidden" name="keyword" value="<?=$keyword?>" />
<input type="hidden" name="page" value="<?=$page?>" />
<? if($action == "modify"){ ?>
<input type="hidden" name="id" value="<?=$id?>" />
<? } ?>
<div class="formbody">
	<ul class="forminfo">
		<li id="tip" class="jserror"></li>
		
		<li><label><?=$file_title?>标题：</label><input name="title" id="title" type="text" class="dfinput" value="<?=$title?>" disabled="disabled" style="width:500px;" /></li>	
		<li><label><?=$file_title?>姓名：</label><input name="name1" id="name1" type="text" class="dfinput" value="<?=$name1?>" disabled="disabled" /></li>	
		<li><label><?=$file_title?>电话：</label><input name="tel" id="tel" type="text" class="dfinput" value="<?=$tel?>" disabled="disabled" /></li>	
		<li><label><?=$file_title?>邮箱：</label><input name="email" id="email" type="text" class="dfinput" value="<?=$email?>" disabled="disabled" /></li>	
		<li><label><?=$file_title?>内容：</label><textarea id="content" name="content" style="width:500px;height:150px;" class="dfinput2"><?=$content;?></textarea></li>	
		<li><label>留言时间：</label><input name="addtime" id="addtime" type="text" class="dfinput" value="<?=$addtime?>" disabled="disabled" style="width:200px;" /></li>		
		<li><label>&nbsp;</label><input name="<?=$action?>" type="button" class="btn" value="返回上一页" onClick="javascript:window.location='<?=$file_table_name?>_mg.php?page=<?=$page?>&keyword=<?=$keyword?>';" /></li>
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
			<li><a href="<?=$file_table_name?>_mg.php"><?=$file_title?>管理</a></li>
			<li><?=GetAction($action)?></li>
		</ul>
    </div>
    
    <div class="rightinfo">
	
		<?
		if($action == "list"){
			$app=12;
			if(empty($page)) $page=1;
			if ($keyword<>"") $where.=" and  (title like '%".$keyword."%')";
			$sql = "select id,title,name1,tel,addtime from ".$dbpre.$file_table_name." where 1=1 $where order by id desc";
			$query = $MYSQL->query($sql);
			$art_count = mysql_num_rows($query);
			if(empty($page)){
				$page=1;
				$start=0;
			}else
				$start = ($page-1)*$app;
			$sql.= " limit ".$start.",".$app;
			$pages = ceil($art_count/$app);
			$query = $MYSQL->query($sql);
		?>
		<form action="" method="post"><ul class="seachform">
			<li><label><?=$file_title?>标题</label><input name="keyword" type="text" class="scinput" value="<?=$keyword?>" style="width:250px;" /></li>
			<li><label>&nbsp;</label><input name="scbtn" type="submit" class="scbtn" value="查询"/></li>
		</ul></form>
		
		<div class="tools">
			<ul class="toolbar">
				<a onClick="document.delForm.action.value='AllDel';delForm.submit();"><li><span><img src="images/t03.png" /></span>删除</li></a>
			</ul>
		</div>
		
		<? if ($art_count>=7){ ?>
			<?=managePage("&keyword=".$keyword,$page,$pages,$art_count)?>
		<? } ?>
    
		<form name="delForm" method="post" action="<?=$PHP_SELF?>">
		<input type="hidden" name="action" value="" />
		<input type="hidden" name="page" value="<?=$page?>" />
		<input type="hidden" name="keyword" value="<?=$keyword?>" />
		
		<table class="tablelist">
			<thead>
			<tr>
			<th width="34"><input name="chkAll" type="checkbox" onClick="CheckAll(delForm)" value="checkbox" /></th>
			<th width="100">编号<i class="sort"><img src="images/px.gif" /></i></th>
			<th><?=$file_title?>标题</th>
			<th width="100">姓名</th>
			<th width="150">电话</th>
			<th width="150">留言时间</th>
			<th width="120">操作</th>
			</tr>
			</thead>
			<tbody>
			<? 
				while($row = mysql_fetch_array($query)){
			?>
				<tr>
				<td><input type="checkbox" name="cinfo[]" value="<?=$row["id"]?>" /></td>
				<td><?=$row["id"]?></td>
				<td><?=$row["title"]?></td>
				<td><?=$row["name1"]?></td>
				<td><?=$row["tel"]?></td>
				<td><?=$row["addtime"]?></td>
				<td class="operate">
					<a href="<?=$PHP_SELF?>?action=modify&id=<?=$row["id"]?>&page=<?=$page?>&keyword=<?=$keyword?>" class="tablelink">查看</a>&nbsp;|&nbsp;
					<a href="<?=$PHP_SELF?>?action=remove&id=<?=$row["id"]?>&page=<?=$page?>&keyword=<?=$keyword?>" onClick="return confirmDel();" class="tablelink"> 删除</a></td>
				</td>
				</tr> 
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
if($action == "modify") {
	$id = $_REQUEST['id'];
	if(isset($_REQUEST['modify'])){
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
			ErrTo("您所选的信息已成功删除",$file_table_name."_mg.php?page=".$page."&keyword=".$keyword);
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
     	ErrTo("信息已删除",$file_table_name."_mg.php?page=".$page."&keyword=".$keyword);
   	else
     	ErrBack("出现错误，信息无法删除。");
}
?>
</body>
</html>