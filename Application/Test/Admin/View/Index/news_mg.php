<?
	include("../include/config.inc.php");
	if(!$_SESSION['LoginOK']) ToURL("/manage/login.php");
	
	MysqlConn();
	if(empty($_REQUEST['action'])) $action = "list";
	else $action = $_REQUEST['action'];
	
	$file_table_name = "news";
	$file_title = "图文";
	
	$page = intval($_REQUEST['page']);
	$start_catg = $_REQUEST['start_catg'];
	$style = intval($_REQUEST['style']);
	$keyword = $_REQUEST['keyword'];
	$param2 = $_REQUEST['param2'];
	
	function GetAction($action) {
		global $file_table_name,$file_title;
		if($action == "add") return "添加".$file_title;
		else if($action == "modify") return "修改".$file_title;
		else if($action == "transfer") return "批量转移".$file_title;
		else if($action == "list") return $file_title."列表";
		else if($action == "remove") return "删除".$file_title;
	}
	
	function showForm($action) {
		global $PHP_SELF,$MYSQL,$SiteImgNews,$file_table_name,$file_title,$dbpre,$start_catg,$page,$style,$keyword,$param2,$id,$catg_code,$title,$content,$simage,$hit,$addtime;
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
<script type="text/javascript" language="javascript">
<!--
function trim(str){
	return str.replace(/^\s*|\s*$/g,"");
}
function checkFormAction(){
	catg_code = trim($("#catg_code").val());
	title = trim($("#title").val());
	if(catg_code==''){
		$("#tip").html("*请选择所属栏目");
		$("#catg_code").focus();
		return false;
	}
	if(title==''){
		$("#tip").html("*请输入<?=$file_title?>标题");
		$("#title").focus();
		return false;
	}
}
-->
</script>
<script type="text/javascript" src="../include/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="../include/ueditor/ueditor.all.min.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		var ue = UE.getEditor('content');
	});
</script>
<form name="form1" enctype="multipart/form-data" action="<?=$PHP_SELF?>" method="post" onSubmit="return checkFormAction();">
<input type="hidden" name="action" value="<?=$action?>" />
<input type="hidden" name="start_catg" value="<?=$start_catg?>" />
<input type="hidden" name="style" value="<?=$style?>" />
<input type="hidden" name="keyword" value="<?=$keyword?>" />
<input type="hidden" name="param2" value="<?=$param2?>" />
<input type="hidden" name="page" value="<?=$page?>" />
<? if($action == "modify"){ ?>
<input type="hidden" name="id" value="<?=$id?>" />
<? } ?>
<div class="formbody">
	<ul class="forminfo">
		<li id="tip" class="jserror"></li>
		
		<li><label>所属栏目<b>*</b></label><div class="vocation">
			<select name="catg_code" id="catg_code" class="neisel">
				<option value="">请选择...</option>
				<?
					if(is_end($start_catg) || empty($start_catg)) 
						echo CodeList($start_catg,0,$catg_code);
					else
						echo "<option value='".$start_catg."' selected>".code2name($start_catg)."</option>";
				?>
			</select>
		</div></li>
		<li><label><?=$file_title?>标题：<b>*</b></label><input name="title" id="title" type="text" class="dfinput" value="<?=$title?>" style="width:500px;" /></li>	
		<?
			if ($simage<>"") $str1="<a href='".$SiteImgNews.$simage."' rel='exgroup' title='".$title."'><img src='images/bimg.gif' width='20' height='20' title='预览' border='0' /></a>&nbsp;&nbsp;<a href='?action=delimg&id=".$id."&start_catg=".$start_catg."&style=".$style."&keyword=".$keyword."&param2=".$param2."&page=".$page."'>删除该图</a>"; else $str1="<font color=red>无图</font>";
		?>
		<li><label>上传图片：</label><input name="simage" type="file" class="dfinput" value="" /><i><?=$str1?></i></li>	
		<li><script id="content" name="content" type="text/plain" style="width:98%;height:250px;"><?=$content;?></script></li>	
		<li><label>发布时间：<b>*</b></label><input name="addtime" id="addtime" type="text" class="dfinput" value="<?=(($addtime=="")?date("Y-m-j H:i:s"):$addtime)?>" style="width:200px;" /></li>		
		<li><label>&nbsp;</label><input name="<?=$action?>" type="submit" class="btn" value="确认保存"/></li>
	</ul>
</div>
</form>
<? } ?>

<?
	function showFormTo($action,$info) {
		global $PHP_SELF,$MYSQL,$SiteImgNews,$file_table_name,$file_title,$dbpre,$start_catg,$page,$style,$keyword,$param2,$cinfo,$catg_code;
?>
<script type="text/javascript" language="javascript">
<!--
function trim(str){
	return str.replace(/^\s*|\s*$/g,"");
}
function checkFormAction(){
	catg_code = trim($("#catg_code").val());
	if(catg_code==''){
		$("#tip").html("*请选择转移栏目");
		$("#catg_code").focus();
		return false;
	}
}
-->
</script>
<form name="form1" enctype="multipart/form-data" action="<?=$PHP_SELF?>" method="post" onSubmit="return checkFormAction();">
<input type="hidden" name="action" value="<?=$action?>" />
<input type="hidden" name="start_catg" value="<?=$start_catg?>" />
<input type="hidden" name="style" value="<?=$style?>" />
<input type="hidden" name="keyword" value="<?=$keyword?>" />
<input type="hidden" name="param2" value="<?=$param2?>" />
<input type="hidden" name="page" value="<?=$page?>" />
<?
	$option = "";
	if (is_array($cinfo)) {
		foreach($cinfo as $value){
			$option.= " or id=".$value."";
		}
	}
?>
<input type="hidden" name="cinfo" value="<?=$option?>" />
<div class="formbody">
	<ul class="forminfo">
		<li id="tip" class="jserror"></li>
		
		<li><label>转移栏目<b>*</b></label><div class="vocation">
			<select name="catg_code" id="catg_code" class="neisel">
				<option value="">请选择...</option>
				<?
					$start_catg = $file_table_name;
					if(is_end($start_catg) || empty($start_catg)) 
						echo CodeList($start_catg,0,$catg_code);
					else
						echo "<option value='".$start_catg."' selected>".code2name($start_catg)."</option>";
				?>
			</select>
		</div></li>		
		<li><label>&nbsp;</label><input name="<?=$action?>" type="submit" class="btn" value="确认保存"/><i></i></li>
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

<script type="text/javascript" src="../include/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>
<script type="text/javascript" src="../include/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" type="text/css" href="../include/fancybox/jquery.fancybox-1.3.4.css" media="screen" />
<script type="text/javascript">
	$(document).ready(function() {
	
		$("a[rel=exgroup]").fancybox({
			'transitionIn'		: 'elastic',
			'transitionOut'		: 'elastic',
			'titlePosition' 	: 'over',
			'titleFormat'		: function(title, currentArray, currentIndex, currentOpts) {
				return '<span id="fancybox-title-over">Image ' + (currentIndex + 1) + ' / ' + currentArray.length + (title.length ? ' &nbsp; ' + title : '') + '</span>';
			}
		});

	});
</script>
</head>
<body>
	<div class="place">
    	<span>位置：</span>
		<ul class="placeul">
			<li><a href="<?=$file_table_name?>_mg.php?start_catg=<?=$start_catg?>&style=<?=$style?>"><?=$file_title?>管理</a></li>
			<li><?=code2name($start_catg)?></li>
			<li><?=GetAction($action)?></li>
		</ul>
    </div>
    
    <div class="rightinfo">
	
		<?
		if($action == "list"){
			if ($style==1){
				$app=12;
			}elseif ($style==2){
				$app=7;
			}elseif ($style==3){
				$app=10;
			}
			if(empty($page)) $page=1;
			if ($keyword<>"") $where.=" and  (title like '%".$keyword."%')";
			if ($param2<>"") {
				$where.=" and (catg_code like '".$param2."-%' or catg_code = '".$param2."')";
			}else{
				$where.=" and (catg_code like '".$start_catg."-%' or catg_code = '".$start_catg."')";
			}
			$sql = "select id,title,catg_code,addtime,simage,hit from ".$dbpre.$file_table_name." where 1=1 $where order by id desc";
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
		<form action="?start_catg=<?=$start_catg?>&style=<?=$style?>" method="post"><ul class="seachform">
			<li><label>所属栏目</label><div class="vocation">
				<select name="param2" id="param2" class="srhsel">
					<option value="">请选择...</option>
					<?
						if(is_end($start_catg) || empty($start_catg)) 
							echo CodeList($start_catg,0,$param2);
						else
							echo "<option value='".$start_catg."' selected>".code2name($start_catg)."</option>";
					?>
				</select>
			</div></li>
			<li><label><?=$file_title?>标题</label><input name="keyword" type="text" class="scinput" value="<?=$keyword?>" style="width:250px;" /></li>
			<li><label>&nbsp;</label><input name="scbtn" type="submit" class="scbtn" value="查询"/></li>
		</ul></form>
		
		<div class="tools">
			<ul class="toolbar">
				<a href="<?=$PHP_SELF."?action=add&start_catg=".$start_catg."&style=".$style?>"><li><span><img src="images/t01.png" /></span>添加</li></a>
				<? if ($style<>3){ ?><a onClick="document.delForm.action.value='transfer';delForm.submit();"><li><span><img src="images/t02.png" /></span>批量转移</li></a><? } ?>
				<a onClick="document.delForm.action.value='AllDel';delForm.submit();"><li><span><img src="images/t03.png" /></span>删除</li></a>
			</ul>
		</div>
		
		<? if ($art_count>=7){ ?>
			<?=managePage("&start_catg=".$start_catg."&style=".$style."&keyword=".$keyword."&param2=".$param2,$page,$pages,$art_count)?>
		<? } ?>
    
		<form name="delForm" method="post" action="<?=$PHP_SELF?>">
		<input type="hidden" name="action" value="" />
		<input type="hidden" name="start_catg" value="<?=$start_catg?>" />
		<input type="hidden" name="style" value="<?=$style?>" />
		<input type="hidden" name="page" value="<?=$page?>" />
		<input type="hidden" name="keyword" value="<?=$keyword?>" />
		<input type="hidden" name="param2" value="<?=$param2?>" />
		
		<? if ($style==1){ ?>
		<table class="tablelist">
			<thead>
			<tr>
			<th width="34"><input name="chkAll" type="checkbox" onClick="CheckAll(delForm)" value="checkbox" /></th>
			<th width="100">编号<i class="sort"><img src="images/px.gif" /></i></th>
			<th width="150">所属栏目</th>
			<th><?=$file_title?>标题</th>
			<th width="100">点击次数</th>
			<th width="150">发布时间</th>
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
				<td><?=code2name($row["catg_code"])?></td>
				<td><?=$row["title"]?></td>
				<td><?=$row["hit"]?></td>
				<td><?=$row["addtime"]?></td>
				<td class="operate">
					<a href="<?=$PHP_SELF?>?action=modify&id=<?=$row["id"]?>&page=<?=$page?>&start_catg=<?=$start_catg?>&style=<?=$style?>&keyword=<?=$keyword?>&param2=<?=$param2?>" class="tablelink">修改</a>&nbsp;|&nbsp;
					<a href="<?=$PHP_SELF?>?action=remove&id=<?=$row["id"]?>&page=<?=$page?>&start_catg=<?=$start_catg?>&style=<?=$style?>&keyword=<?=$keyword?>&param2=<?=$param2?>" onClick="return confirmDel();" class="tablelink"> 删除</a></td>
				</td>
				</tr> 
			<? } ?>	     
			</tbody>
		</table>
		<script type="text/javascript">
		$('.tablelist tbody tr:odd').addClass('odd');
		</script>
		
		<? }elseif ($style==2){ ?>
		<table class="imgtable">
		<thead>
		<tr>
		<th width="56"><input name="chkAll" type="checkbox" onClick="CheckAll(delForm)" value="checkbox" /></th>
		<th width="120">缩略图</th>
		<th width="150">所属栏目</th>
		<th><?=$file_title?>标题</th>
		<th width="100">点击次数</th>
		<th width="120">操作</th>
		</tr>
		</thead>
		<tbody>
			<? 
				while($row = mysql_fetch_array($query)){
					$imgstr = "<img src='images/nopic.jpg' width='80' height'60' border='0' />";
					if (trim($row["simage"])<>""){
						$imgstr = '<a href="'.$SiteImgNews.$row["simage"].'" rel="exgroup" title="'.$row["title"].'"><img src="'.$SiteImgNews.$row["simage"].'" border="0" width="80"  height="60" /></a>';
					}
			?>
			<tr>
			<td><input type="checkbox" name="cinfo[]" value="<?=$row["id"]?>" /></td>
			<td class="imgtd"><?=$imgstr?></td>
			<td><?=code2name($row["catg_code"])?><p>ID: <?=$row["id"]?></p></td>
			<td><?=$row["title"]?><p>发布时间：<?=$row["addtime"]?></p></td>
			<td><?=$row["hit"]?></td>
			<td class="operate">
				<a href="<?=$PHP_SELF?>?action=modify&id=<?=$row["id"]?>&page=<?=$page?>&start_catg=<?=$start_catg?>&style=<?=$style?>&keyword=<?=$keyword?>&param2=<?=$param2?>" class="tablelink">修改</a>&nbsp;|&nbsp;
				<a href="<?=$PHP_SELF?>?action=remove&id=<?=$row["id"]?>&page=<?=$page?>&start_catg=<?=$start_catg?>&style=<?=$style?>&keyword=<?=$keyword?>&param2=<?=$param2?>" onClick="return confirmDel();" class="tablelink"> 删除</a></td>
			</td>
			</tr>
			<? } ?>
		</tbody>
		</table>
		<script type="text/javascript">
		$('.imgtable tbody tr:odd').addClass('odd');
		</script>
		<? }elseif ($style==3){ ?>
		<ul class="imglist">
    		<? 
				while($row = mysql_fetch_array($query)){
					$imgstr = "<img src='images/nopic.jpg' width='168' height'127' border='0' />";
					if (trim($row["simage"])<>""){
						$imgstr = '<a href="'.$SiteImgNews.$row["simage"].'" rel="exgroup" title="'.$row["title"].'"><img src="'.$SiteImgNews.$row["simage"].'" border="0" width="168"  height="127" /></a>';
					}
			?>
			<li>
				<span><?=$imgstr?></span>
				<h2 title="<?=$row["title"]?>"><?=sub_str($row["title"],14)?></h2>
				<p>
					<a href="<?=$PHP_SELF?>?action=modify&id=<?=$row["id"]?>&page=<?=$page?>&start_catg=<?=$start_catg?>&style=<?=$style?>&keyword=<?=$keyword?>&param2=<?=$param2?>" class="tablelink">修改</a>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="<?=$PHP_SELF?>?action=remove&id=<?=$row["id"]?>&page=<?=$page?>&start_catg=<?=$start_catg?>&style=<?=$style?>&keyword=<?=$keyword?>&param2=<?=$param2?>" onClick="return confirmDel();" class="tablelink"> 删除</a>
				</p>
			</li>
			<? } ?>
		</ul>
		<? } ?>
		</form>
    	<? } ?>
    </div>
	
	
<?
if($action == "add"){
    if(isset($_REQUEST['add'])){
		$catg_code = $_REQUEST['catg_code'];
		$title = $_REQUEST['title'];
		$content = $_REQUEST['content'];
		$addtime = $_REQUEST['addtime'];
		
		if(empty($catg_code)) $err = "未指定所属栏目或指定栏目下有子栏目";
        $sql = "select *from ".$dbpre."catg where catg_code like '".$catg_code."-%'";
        $query = $MYSQL->query($sql);
        if(mysql_num_rows($query)>0) $err = "[".code2name($catg_code)."]不是最底层的栏目必须!";
        if(empty($title))     $err = "必须填写文章标题";
        if(isset($err)) {
        	echo "<br><center><font color=#ff0000 ><b>错误：".$err."</b></font></center><br><br>";
       		showForm("add");
        	exit();
        }
		
		if(!empty($_FILES['simage']['name'])){
        	if (!check_file_type($_FILES['simage']['tmp_name'],$_FILES['simage']['name'], $allow_pic_types)){
				ErrBack("图片格式错误");
			}
			$ext = strtolower(substr($_FILES['simage']['name'], strrpos($_FILES['simage']['name'], '.')));
			$simage_str=md5(uniqid(microtime(),1)).$ext;
			copy($_FILES['simage']['tmp_name'],$SiteImgNews.$simage_str);
        }
		
 		if(@$MYSQL->query("insert into ".$dbpre.$file_table_name." (catg_code,title,content,simage,addtime) values ('$catg_code','$title','$content','$simage_str','$addtime')",$db)){
        	$id = $MYSQL->insert_id();
           	echo "<br><br><br><center>".$file_title." <b>".$title."</b> 添加成功!</cetner>";
        }else
        	ErrBack("出现错误！".$file_title."未添加成功。");
        //echo "<br><a href=\"".$PHP_SELF."?action=add&style=".$style."&start_catg=".$catg_code."\">继续添加".$file_title."</a>";
		echo "<br><a href=\"".$PHP_SELF."?start_catg=".$start_catg."&style=".$style."\">返回".$file_title."列表</a>";
	} else {
    	showForm("add");
	}
}
if($action == "modify") {
	$id = $_REQUEST['id'];
	if(isset($_REQUEST['modify'])){
		$catg_code = $_REQUEST['catg_code'];
		$title = $_REQUEST['title'];
		$content = $_REQUEST['content'];
		$addtime = $_REQUEST['addtime'];
		
		if(empty($catg_code)) $err = "必须指定所属栏目";
    	if(empty($title)) $err = "必须填写".$file_title."标题";
        if(isset($err)) {
        	echo "<br><center><font color=#ff0000 ><b>错误：".$err."</b></font></center><br><br>";
            showForm("modify");
            exit;
        }
		
		if(!empty($_FILES['simage']['name'])){
        	if (!check_file_type($_FILES['simage']['tmp_name'],$_FILES['simage']['name'], $allow_pic_types)){
				ErrBack("图片格式错误");
			}
			$ext = strtolower(substr($_FILES['simage']['name'], strrpos($_FILES['simage']['name'], '.')));
			$simage_str=md5(uniqid(microtime(),1)).$ext;
			copy($_FILES['simage']['tmp_name'],$SiteImgNews.$simage_str);

			$img = @$MYSQL->query("select simage from ".$dbpre.$file_table_name." where id=".$id);
			$row = mysql_fetch_array($img);
			if($row["simage"])
				@unlink($SiteImgNews.$row["simage"]);
        }
		
		$where = "";
		if(!empty($simage_str)) $where .=",simage = '".$simage_str."'";
        $qry_update = "update ".$dbpre.$file_table_name." set catg_code='$catg_code',title='$title',content='".$content."',addtime='$addtime' ".$where." where id=$id";
        if(@$MYSQL->query($qry_update))
			ErrTo($file_title." ".$title." 修改成功!",$file_table_name."_mg.php?start_catg=".$start_catg."&style=".$style."&page=".$page."&keyword=".$keyword."&param2=".$param2);
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
if($action == "transfer") {
	if(isset($_REQUEST['transfer'])){
	
		$cinfo = $_REQUEST['cinfo'];
		$catg_code = $_REQUEST['catg_code'];
		if($MYSQL->query("update ".$dbpre.$file_table_name." set catg_code='$catg_code' where 1<>1 ".$cinfo." ")){
			ErrTo("您所选的信息已成功转移",$file_table_name."_mg.php?page=".$page."&start_catg=".$start_catg."&style=".$style."&keyword=".$keyword."&param2=".$param2);
		} else {
			echo "<script>alert('出现错误！信息转移未成功。');history.go(-1);</script>";
		}
	} else {
		$cinfo = $_REQUEST['cinfo'];
		if (is_array($cinfo)) {
			foreach($cinfo as $value){
				$where.= " or id='".$value."'";
			}
			if(empty($where)) ErrTo("请选择要转移的信息",$file_table_name."_mg.php?start_catg=".$start_catg."&style=".$style."&page=".$page."&keyword=".$keyword."&param2=".$param2);
			showFormTo("transfer",$cinfo);
		} else {
			ErrTo("请选择要转移的信息",$file_table_name."_mg.php?start_catg=".$start_catg."&style=".$style."&page=".$page."&keyword=".$keyword."&param2=".$param2);
		}
    }
}
if($action == "AllDel") {
	$cinfo = $_REQUEST['cinfo'];
	if (is_array($cinfo)) {
		foreach($cinfo as $value){
			$where.= " or id='".$value."'";
			$img = @$MYSQL->query("select simage from ".$dbpre.$file_table_name." where id='".$value."'");
			$row = mysql_fetch_array($img);
			if($row["simage"])
				@unlink($SiteImgNews.$row["simage"]);
		}
		if(empty($where)) die("<script>alert('请选择要删除的信息!');history.go(-1);</script>");
		if($MYSQL->query("delete from ".$dbpre.$file_table_name." where 1<>1 ".$where." ")){
			ErrTo("您所选的信息已成功删除",$file_table_name."_mg.php?page=".$page."&start_catg=".$start_catg."&style=".$style."&keyword=".$keyword."&param2=".$param2);
		} else {
			echo "<script>alert('出现错误！信息删除未成功。');history.go(-1);</script>";
		}
	} else {
		echo "<script>alert('请选择要删除的信息!');history.go(-1);</script>";
	}
}
if($action == "remove") {
	$id = $_REQUEST['id'];
	$img = @$MYSQL->query("select simage from ".$dbpre.$file_table_name." where id=".$id);
	$row = mysql_fetch_array($img);
	if($row["simage"])
		@unlink($SiteImgNews.$row["simage"]);
   	if($MYSQL->query("delete from ".$dbpre.$file_table_name." where id=".$id))
     	ErrTo("信息已删除",$file_table_name."_mg.php?start_catg=".$start_catg."&style=".$style."&keyword=".$keyword."&param2=".$param2."&page=".$page);
   	else
     	ErrBack("出现错误，信息无法删除。");
}
if($action == "delimg") {
	$id = $_REQUEST['id'];
	$img = @$MYSQL->query("select simage from ".$dbpre.$file_table_name." where id=".$id);
	$row = mysql_fetch_array($img);
	if($row["simage"])
		@unlink($SiteImgNews.$row["simage"]);
   	if($MYSQL->query("update ".$dbpre.$file_table_name." set simage='' where id=".$id))
     	ErrTo("图片已删除",$file_table_name."_mg.php?action=modify&id=".$id."&start_catg=".$start_catg."&style=".$style."&keyword=".$keyword."&page=".$page."&param2=".$param2);
   	else
     	ErrBack("出现错误，图片无法删除。");
}
?>
</body>
</html>