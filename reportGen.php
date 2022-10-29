/* Copyright Eric Baller and other contributors; Licensed MIT */

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// generate a report based on a reading. User may not be logged in.
function reportGen($codename=NULL) {
	global $log;

	$pdo=pdoConnect();

	if(!isset($codename)) {
		$codename="demo";
		}
	$filepath = "report";
	$htmlfile = $filepath.".html";
	$pdffile = $filepath.".pdf";
	$warnings[]="Creating " . $filepath;
	$account=1;

	// create report
	ob_start(); // Start buffer  
	require('report.php'); // Include PDF layout file  
	$html = ob_get_contents(); // Get the contents
	ob_end_clean(); // Close buffers  

$result = file_put_contents($htmlfile,$html);

$command=array();
$command[]='../bin/wkhtmltopdf';
$command[]='--javascript-delay 1000';
$command[]='--load-error-handling ignore';
$command[]='--no-outline';
$command[]='--margin-bottom 5';
$command[]='--margin-top 5';
$command[]='--margin-right 5';
$command[]='--margin-left 5';
// were disabled
$command[]='-s Letter';
$command[]='--disable-smart-shrinking';
$command[]='--print-media-type';

$command[] = escapeshellarg($htmlfile);
$command[] = escapeshellarg($pdffile);

exec(implode(' ', $command),$result);

return $result;
}

function reportSend($codename = 'demo') {

global $log;
fwrite($log, "\n -- reportSend -- \n");
fwrite($log, $codename);

require_once 'bpd.php';

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

	$lead = getLead($codename);
	$to = getEmail($lead);

	$email = new PHPMailer();

	if( ! $email ) {
		$warnings[] = "No mail transport available. Report not sent.";
fwrite($log, "\nNO MAILER\n");
		return false;
		}

fwrite($log, print_r($to, TRUE));
	$email->From      = 'EUI@hvac20.com';
	$email->FromName  = 'HVAC 2.0';
	$email->Subject   = 'Your home performance report';
	$email->AddAddress( $to );
	$email->AddCC('EUI@hvac20.com', 'EUI Calculator');

	$email->SetFrom('EUI@hvac20.com', 'EUI Calculator');
	$email->AddReplyTo('EUI@hvac20.com', 'EUI Calculator');

	ob_start();
	require('email.html');
	$body = ob_get_contents();
	ob_end_clean();
	$email->Body = str_replace("SITE",$codename,$body);
	$email->AltBody="Your report is attached.";

	$filename = "report.pdf";
	$filepath = $filename;
fwrite($log, $filepath);
	$email->AddAttachment($filepath, $filename, 'base64', 'application/pdf');

	// cloudways elasticmail
	//Enable SMTP debugging.
	$email->SMTPDebug = 1;                           
	//Set PHPMailer to use SMTP.
	$email->isSMTP();        
	//Set SMTP host name                      
	$email->Host = "smtp.elasticemail.com";
	//Set this to true if SMTP host requires authentication to send email
	$email->SMTPAuth = true;                      
	//Provide username and password
	$email->Username = "YOUR SENDER HERE";
	$email->Password = "YOUR PASSWORD HERE";
	//If SMTP requires TLS encryption then set it
	$email->SMTPSecure = "tls";                       
	//Set TCP port to connect to
	$email->Port = 2525;                    

	$result = $email->Send();
fwrite($log, $result);
	return $result;
}
?>
