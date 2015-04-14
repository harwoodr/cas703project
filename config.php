<?PHP
$serverurl = "http://techserv.ece.mcmaster.ca/~harwood/project/";
$db_user = "cas703";
$db_pass = "swordfish";
$db_host = "localhost";
$db_type = "mysql";

$salt = "pepper";

$basedsn = $db_type ."://". $db_user .":". $db_pass ."@". $db_host ."/";

require_once ('../vendor/autoload.php');
require_once ('../vendor/adodb/adodb-php/adodb.inc.php');
require_once ('../vendor/adodb/adodb-php/adodb-active-record.inc.php');

function authCheck($userName,$token){
	//quick and dirty
	global $serverurl;
	$ch = curl_init($serverurl."auth/".$userName."/".$token);
	curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	return ($info['http_code']=='204');
}



function dumpit($var){
	echo "<hr><pre>";
	var_dump($var);
	echo "</pre><hr>";
}
