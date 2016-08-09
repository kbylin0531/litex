<?
	include("../include/config.inc.php");
	if(!$_SESSION['LoginOK']) ToURL("/manage/login.php");

	MysqlConn();
	if(empty($_REQUEST['action'])) $action = "list";
	else $action = $_REQUEST['action'];
	
	function GetAction($action) {
        if($action == "list") return "文件目录列表";
	}
?>
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
<script type="text/javascript" src="js/quickView.js"></script>

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
			<li><a href="file_mg.php">文件管理</a></li>
			<li>文件目录列表</li>
		</ul>
    </div>
    
    <? if($action == "list"){?>
    <table class="filetable">
		<thead>
			<tr>
			<th width="33%">名称</th>
			<th width="15%">修改日期</th>
			<th width="10%">类型</th>
			<th width="12%">大小</th>
			<th width="30%"></th>
			</tr>    	
		</thead>
    	<tbody>
		<? dir_size($fileDir); ?>   
		</tbody>
    </table>
	<? } ?>
</body>
</html>