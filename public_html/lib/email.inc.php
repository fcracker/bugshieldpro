<?php
function sendTemplateEmail($from, $fromName, $replyTo, $to, $bcc, $templateFile, $emailTags,$attach_file=""){
	global $cfg;
	$return = false;
	if(file_exists($templateFile)){
		$template = file_get_contents($templateFile);
		foreach($emailTags as $tag => $code){
			$template = str_replace("<!--tag:".$tag."-->", $code, $template);
		}
		$content = explode("\n", $template, 2);
		//print_r($content);die();
		$return = sendEmail($from, $fromName, $replyTo, $to, $bcc, $content[0], $content[1], $cfg['email']['HTML'],$attach_file);
	}
	return $return;
}

function sendEmail($from, $fromName, $replyTo, $to, $bcc, $subject, $content, $isHTML,$attach_file=""){
	global $cfg;
	$mail = new PHPMailer();
	$mail->CharSet = 'UTF-8';
	$mail->SetLanguage('lang-en');
	
	if($cfg['email']['sendmail']){
		$mail->IsMail(); // set mailer to use PHP mail() function
	}else{
		$mail->IsSMTP(); // use smtp
		$mail->Host = $cfg['email']['smtp'];
		$mail->Port = $cfg['email']['port'];
		$mail->SMTPAuth = $cfg['email']['auth'];
		if($mail->SMTPAuth){
			$mail->Username = $cfg['email']['user'];
			$mail->Password = $cfg['email']['password'];
		}
	}
	
	$mail->From = $from;
	$mail->FromName = $fromName;
	$mail->AddReplyTo($replyTo);
	$mail->AddAddress($to);
	if($bcc != "") $mail->AddBCC($bcc);
	$mail->IsHTML($isHTML);
	$mail->Subject = $subject;
	$mail->Body = $content;
	
	//check if we have an attachament
	if(strlen($attach_file)) {
		if(file_exists($attach_file)) {
			$mail->AddAttachment($attach_file);
		}	
	}
	
	return $mail->Send();
}
?>