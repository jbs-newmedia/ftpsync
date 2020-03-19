<?php
include 'class.jbsnm_ftpsync.inc.php';

$ftp_server='';
$ftp_user='';
$ftp_pass='';
$ftp_secure=true; // optional/
$ftp_port=21; // optional
$ftp_timeout=90; // optional
$ftp_pasv=true;
$remote_dir='';
$backup_dir='';

$Sync=new JBSNM_FTPSync();

if ($Sync->connect($ftp_server, $ftp_user, $ftp_pass, $ftp_secure, $ftp_port, $ftp_timeout)!==true) {
	die($Sync->getErrorInfo());
}
if ($Sync->setPasv($ftp_pasv)!==true) {
	die($Sync->getErrorInfo());
}
if ($Sync->sync($remote_dir, $backup_dir)!==true) {
	die($Sync->getErrorInfo());
}
if ($Sync->close()!==true) {
	die($Sync->getErrorInfo());
}

if ($Sync->getLog()==[]) {
	die('sync ok');
} else {
	foreach ($Sync->getLog() as $log) {
		echo $log.'<br/>';
	}
}

?>