<? 
	include_once("../include/config.inc.php");
	MysqlConn();
	
	if ($_REQUEST['act']=="validmsg"){
		$id = $_REQUEST['id'];
		$keywords = trim($_REQUEST['keywords']);
		if ($id!=""){
			$query = $MYSQL->query("select * from ".$dbpre."msg where keywords='".$keywords."' and id<>".$id);
			$cnt = mysql_num_rows($query);
			if ($cnt==1){
				echo "false";
			}else{
				echo "ok";
			}
		}else{
			$query = $MYSQL->query("select * from ".$dbpre."msg where keywords='".$keywords."' ");
			$cnt = mysql_num_rows($query);
			if ($cnt==1){
				 echo "false";
			}else{
				 echo "ok";
			}
		}
	}
?>