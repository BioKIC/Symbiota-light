<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$CHARSET);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$con = MySQLiConnectionFactory::getCon("readonly");
$ident = $con->real_escape_string($_REQUEST["ident"]);
$collId = $con->real_escape_string($_REQUEST["collid"]);

$responseStr = "";
$sql = "SELECT exchangeid ".
	"FROM omoccurexchange ".
	'WHERE identifier = "'.$ident.'" AND collid = '.$collId;
//echo $sql;
$result = $con->query($sql);
while ($row = $result->fetch_object()) {
	$returnArr[] = $row->exchangeid;
}
$result->close();
if(!($con === false)) $con->close();

//output the response
echo json_encode($returnArr);
?>