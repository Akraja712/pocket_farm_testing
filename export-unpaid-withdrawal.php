<?php
include_once('includes/crud.php');
$db = new Database();
$db->connect();
date_default_timezone_set('Asia/Kolkata');
$currentdate = date('Y-m-d');

$sql = "SELECT 
u.name AS `Beneficiary Name (Mandatory) Full name of the customer - eg: Bruce Wayne`, 
CONCAT('', u.account_num, '') AS `Beneficiary Account number (Mandatory) Beneficiary Account number to which the money should be transferred`, 
u.ifsc AS `IFSC code (Mandatory) IFSC code of beneficary's bank. eg:KKBK0000958`, 
w.amount AS `Amount (Mandatory) Amount that needs to be transfered. Eg: 100.00`, 
NULL AS `Description / Purpose (Optional) For Internal Reference eg: For salary`
FROM users u JOIN  withdrawals w ON u.id = w.user_id AND w.status = 0";

       $db->sql($sql);
    $developer_records = $db->getResult();

	
	$filename = "unpaid-withdrawals-data".date('Ymd') . ".xls";			
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=\"$filename\"");	
	$show_coloumn = false;
	if(!empty($developer_records)) {
	  foreach($developer_records as $record) {
		if(!$show_coloumn) {
		  // display field/column names in first row
		  echo implode("\t", array_keys($record)) . "\n";
		  $show_coloumn = true;
		}
		echo implode("\t", array_values($record)) . "\n";
	  }
	}
	exit;  
?>
