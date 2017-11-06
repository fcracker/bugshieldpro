<?php
class Cipher {
    private $securekey, $iv;
    function __construct() {
        $this->securekey = hash('sha256','online trade training 10/22/10',TRUE);
        //$this->iv = mcrypt_create_iv(64);
    }
    function encrypt($input) {
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->securekey, $input, MCRYPT_MODE_ECB));
    }
    function decrypt($input) {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->securekey, base64_decode($input), MCRYPT_MODE_ECB));
    }
}

/*
	$cipher = new Cipher();
	
	$encryptedtext = $cipher->encrypt("my mother is beautiful.");
	echo "->encrypt = $encryptedtext<br />";
	
	$decryptedtext = $cipher->decrypt($encryptedtext);
	echo "->decrypt = $decryptedtext<br />";
*/

?>