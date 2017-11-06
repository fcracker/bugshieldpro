<?php
function show_antispam_code(){
	global $cfg;
	$return = false;
	$ext = get_loaded_extensions();
	foreach($ext as $key => $value){
		if(strtolower($value) == 'gd') $return = true;
	}
	if($return){
		if(isset($cfg['site']['antiSpamTmpDir'])){
			if(!file_exists($cfg['site']['antiSpamTmpDir'])) $return = false;
		}else if(!file_exists(ini_get('upload_tmp_dir'))){
			$return = false;
		}
	}
	if(isset($cfg['site']['disableAntiSpam'])){
		if($cfg['site']['disableAntiSpam'] == 1) $return = false;
	}
	return $return;
}

function get_antispam_code(){
	global $cfg;
	$return = '';
	$tempDir = ini_get('upload_tmp_dir');
	if(isset($cfg['site']['antiSpamTmpDir'])) $tempDir = $cfg['site']['antiSpamTmpDir'];
	while(($authNum = rand() % 100000) < 10000);
	srand((double)microtime()*1000000);
	$im = ImageCreate(62, 18);
	$black = ImageColorAllocate($im, 0, 0, 0);
	$white = ImageColorAllocate($im, 255, 255, 255);
	$gray = ImageColorAllocate($im, 200, 200, 200);
	ImageFill($im, 0, 0, $gray);
	ImageString($im, 5, 10, 2, $authNum, $black);
	for($index = 0; $index < 200; $index++){
		$randcolor = ImageColorAllocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
		ImageSetPixel($im, rand()%70, rand()%30, $randcolor);
	}
	$return = md5(md5($authNum).$cfg['site']['cookieToken']);
	imagegif($im, $tempDir.'/'.$return.'.gif');
	imagedestroy($im);
	return $return;
}
?>