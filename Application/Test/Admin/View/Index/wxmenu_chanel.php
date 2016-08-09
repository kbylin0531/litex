<?
	include($_SERVER['DOCUMENT_ROOT']."/include/config.inc.php");
	if(!$_SESSION['LoginOK']) ToURL("/login.php");
	MysqlConn();
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?
$domain = 'http://'.$_SERVER['HTTP_HOST'];
function detailinfo(){
	global $MYSQL,$dbpre;
	$query = $MYSQL->query("select * from ".$dbpre."config where id=1 limit 0,1");
	$DetailRs = array();
	if (mysql_num_rows($query) >0) {
		$DetailRs = mysql_fetch_array($query);
    }
    return $DetailRs;
}

function post($url, $jsonData){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS,$jsonData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','charset:utf-8'));
	$result = curl_exec($ch) ;
	curl_close($ch);
	return $result;
}

$detailObj=detailinfo();
$posturl="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".trim($detailObj["appid"])."&secret=".trim($detailObj["appsecret"]);
$token_json=post($posturl,"");
$access_token_json=json_decode($token_json);
$access_token=$access_token_json->access_token;

$url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$access_token;
$temparr = json_decode(post($url,''),true);
$resmsg = $temparr['errmsg'];
if ($resmsg == "ok"){
	ErrTo("自定义菜单已撤销","wxmenu_mg.php");
}else{
	ErrTo("菜单撤销失败，请联系管理员","wxmenu_mg.php");
}
?>