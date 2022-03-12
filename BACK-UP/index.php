<?php

$label = '
/***************************************************
	Backup2Mail v2.45.
	by riskiakbar279
****************************************************/
';
							

/***************************************************
	Database settings
****************************************************/
	$db_server			= 'localhost';				// Database server, usually "localhost"
 	$db_name 			= '';						// Database name, leave empty for 'all databases'
	$db_user 		    = '';						// Database username
	$db_pass 		    = '';						// Database password



/***************************************************
	E-mail settings
****************************************************/
 	$website            = 'yoursite.com';			// Your site's domain (without www. part)
	$send_to 		    = 'mail@yoursite.com';		// backup file will be sent to?



/***************************************************
    Misc options
****************************************************/
 	$delete_backup	= true;							// delete gziped database from server after sending?

    $send_log       = true;							// send follow-up report?
 													// - true = send log file to an e-mail after each backup transfer
 													// - false = don't send log file, just leave it on the server



/***************************************************
	If everything goes well, you shouldn't
	modify anything below.
****************************************************/
























error_reporting(E_ALL);

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Backup2mail status [' . $website . ']</title>
		<style type="text/css">body { background: #000; color: #0f0; font-family: \'Courier New\', Courier; }</style>
	</head>
	<body>';

function date_stamp() {
	global $html_output;
	$backup_date = date('Y-m-d-H-i');
	echo 'Database backup date: ' . $backup_date . '<br />';
	return $backup_date;
}

function backup_filename() {
    global $db_name, $date_stamp, $html_output;
	$db_backup_filename = ($db_name == '' ? 'all_databases' : $db_name) . '_' . $date_stamp . '.sql.gz';
	echo 'Database backup file: ' . $db_backup_filename . '<br />';
	return $db_backup_filename;
}

function db_dump() {
	global $db_server, $db_name, $db_user, $db_pass, $backup_filename, $html_output;
	$cmd = 'mysqldump -u ' . $db_user . ' -h ' . $db_server . ' --password=' . $db_pass . ' ' . ($db_name == '' ? '--all-databases' : $db_name) . ' | gzip > ' . $backup_filename;
	$dump_status = (passthru($cmd) === false) ? 'No' : 'Yes';
	echo 'Command: ' . $cmd . '<br />';
	echo 'Command executed? ' . $dump_status . '<br />';
	return $dump_status;
}

function send_attachment($file, $file_is_db = true) {
	global $send_to, $website, $delete_backup, $html_output;

	$sent       = 'No';

	$subject    = 'MySQL backup - ' . ($file_is_db ? 'db dump' : 'report') . ' [' . $website . ']';
    $boundary   = md5(uniqid(time()));
    $mailer     = 'Sent by riskiakbar279 (c) riski xi huaren, www.tolkitseonew.my.id.';

	$body = 'Database backup file:' . "\n" . ' - ' . $file . "\n\n";
	$body .= '---' . "\n" . $mailer;

	$headers  = 'From: website@' . $website . "\n";
	$headers .= 'MIME-Version: 1.0' . "\n";
	$headers .= 'Content-type: multipart/mixed; boundary="' . $boundary . '";' . "\n";
	$headers .= 'This is a multi-part message in MIME format. ';
	$headers .= 'If you are reading this, then your e-mail client probably doesn\'t support MIME.' . "\n";
	$headers .= $mailer . "\n";
	$headers .= '--' . $boundary . "\n";

	$headers .= 'Content-Type: text/plain; charset="iso-8859-1"' . "\n";
	$headers .= 'Content-Transfer-Encoding: 7bit' . "\n";
	$headers .= $body . "\n";
	$headers .= '--' . $boundary . "\n";

	$headers .= 'Content-Disposition: attachment;' . "\n";
	$headers .= 'Content-Type: Application/Octet-Stream; name="' . $file . "\"\n";
	$headers .= 'Content-Transfer-Encoding: base64' . "\n\n";
	$headers .= chunk_split(base64_encode(implode('', file($file)))) . "\n";
	$headers .= '--' . $boundary . '--' . "\n";

	if (mail($send_to, $subject, $body, $headers)) {
		$sent = 'Yes';		
		echo ($file_is_db ? 'Backup file' : 'Report') . ' sent to ' . $send_to . '.<br />';
		if ($file_is_db) {
			if ($delete_backup) {
	            unlink($file);
				echo 'Backup file REMOVED from disk.<br />';
			} else {
				echo 'Backup file LEFT on disk.<br />';
			}
		}
	} else {
		echo '<span style="color: #f00;">' . ($file_is_db ? 'Database' : 'Report') . ' not sent! Please check your mail settings.</span><br />';
	}
	
	
	return $sent;
}

function write_log() {
    global $backup_filename, $date_stamp, $send_log, $label;

    $log_file = 'backup_log.txt';
    if (!$handle = fopen($log_file, 'a+')) exit;
	if (chmod($log_file, 0644) && is_writable($log_file)) {

    	echo '<h2>Mysqldump</h2>';
		$dumped         = db_dump();

		echo '<h2>Sending</h2>';
		$sent           = send_attachment($backup_filename);

	    $log_content    = "\n" . $date_stamp . "\t\t\t" . $dumped . "\t\t\t" . $sent;

	    if (filesize($log_file) == '0') {
			$log_header  = $label . "\n\n";
			$log_header .= 'Backup log' . "\n";
			$log_header .= '----------------------------------------------' . "\n";
			$log_header .= 'DATESTAMP:					DUMPED		MAILED' . "\n";
			$log_header .= '----------------------------------------------';
			if (fwrite($handle, $log_header) === false) exit;
		}

	    if (fwrite($handle, $log_content) === false) exit;

	}

	fclose($handle);

	if ($send_log) {
		send_attachment($log_file, false);
	}
}



echo '<h2>Setup</h2>';
$date_stamp         = date_stamp();
$backup_filename    = backup_filename();
$init               = write_log();

echo '<br /><br />...<br /><br />If all letters are green and you received the files, you\'re good to go!<br />Move this file to your account root folder now.</body></html>';

?>
