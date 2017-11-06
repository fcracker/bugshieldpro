<?php
// Like Forum/ library / core/ class.cookieidentity.php
class Forum_CookieIdentity {
   
   public $UserID = NULL;
   
   public $CookieName;
   public $CookiePath;
   public $CookieDomain;
   public $VolatileMarker;
   public $CookieHashMethod;
   public $CookieSalt;
   
   public function __construct($Config = NULL) {
      $this->Init($Config);
   }
   
   public function Init($Config = NULL) {
//      $Config = array("Salt" => "SO7161ESF1",
      $Config = array("Salt" => "SO7161ESF1",
						    "Name" => "Vanilla",
						    "Path" => "/",
						    "Domain" => "",
						    "HashMethod" => "md5");   
      $DefaultConfig = array("Salt" => "SO7161ESF1",
						    "Name" => "Vanilla",
						    "Path" => "/",
						    "Domain" => "",
						    "HashMethod" => "md5");

               
      $this->CookieName = ArrayValue('Name', $Config, $DefaultConfig['Name']);
      $this->CookiePath = ArrayValue('Path', $Config, $DefaultConfig['Path']);
      $this->CookieDomain = ArrayValue('Domain', $Config, $DefaultConfig['Domain']);
      $this->CookieHashMethod = ArrayValue('HashMethod', $Config, $DefaultConfig['HashMethod']);
      $this->CookieSalt = ArrayValue('Salt', $Config, $DefaultConfig['Salt']);
      $this->VolatileMarker = $this->CookieName.'-Volatile';
   }
   

   /**
    * Returns $this->_HashHMAC with the provided data, the default hashing method
    * (md5), and the server's COOKIE.SALT string as the key.
    *
    * @param string $Data The data to place in the hash.
    */
   protected static function _Hash($Data, $CookieHashMethod, $CookieSalt) {
      return self::_HashHMAC($CookieHashMethod, $Data, $CookieSalt);
   }
   
   /**
    * Returns the provided data hashed with the specified method using the
    * specified key.
    *
    * @param string $HashMethod The hashing method to use on $Data. Options are MD5 or SHA1.
    * @param string $Data The data to place in the hash.
    * @param string $Key The key to use when hashing the data.
    */
   protected static function _HashHMAC($HashMethod, $Data, $Key) {
      $PackFormats = array('md5' => 'H32', 'sha1' => 'H40');

      if (!isset($PackFormats[$HashMethod]))
         return false;

      $PackFormat = $PackFormats[$HashMethod];
      // this is the equivalent of "strlen($Key) > 64":
      if (isset($Key[63]))
         $Key = pack($PackFormat, $HashMethod($Key));
      else
         $Key = str_pad($Key, 64, chr(0));

      $InnerPad = (substr($Key, 0, 64) ^ str_repeat(chr(0x36), 64));
      $OuterPad = (substr($Key, 0, 64) ^ str_repeat(chr(0x5C), 64));

      return $HashMethod($OuterPad . pack($PackFormat, $HashMethod($InnerPad . $Data)));
   }
   
   /**
    * Generates the user's session cookie.
    *
    * @param int $UserID The unique id assigned to the user in the database.
    * @param boolean $Persist Should the user's session remain persistent across visits?
    */
   public function SetIdentity($UserID, $Persist = FALSE) {
      if(is_null($UserID)) {
         $this->_ClearIdentity();
         return;
      }
      
      $this->UserID = $UserID;
      
      if ($Persist !== FALSE) {
         // Note: 2592000 is 60*60*24*30 or 30 days
         $Expiration = $Expire = time() + 2592000;
      } else {
         // Note: 172800 is 60*60*24*2 or 2 days
         $Expiration = time() + 172800;
         // Note: setting $Expire to 0 will cause the cookie to die when the browser closes.
         $Expire = 0;
      }

      // Create the cookie.
      $KeyData = $UserID.'-'.$Expiration;
      $this->_SetCookie($this->CookieName, $KeyData, array($UserID, $Expiration), $Expire);
      $this->SetVolatileMarker($UserID);
   }
   
   public function SetVolatileMarker($UserID) {
      if (is_null($UserID))
         return;
      
      // Note: 172800 is 60*60*24*2 or 2 days
      $Expiration = time() + 172800;
      // Note: setting $Expire to 0 will cause the cookie to die when the browser closes.
      $Expire = 0;
      
      $KeyData = $UserID.'-'.$Expiration;
      $this->_SetCookie($this->VolatileMarker, $KeyData, array($UserID, $Expiration), $Expire);
   }
   
   protected function _SetCookie($CookieName, $KeyData, $CookieContents, $Expire) {
      self::SetCookie($CookieName, $KeyData, $CookieContents, $Expire, $this->CookiePath, $this->CookieDomain, $this->CookieHashMethod, $this->CookieSalt);
   }
   
   public static function SetCookie($CookieName, $KeyData, $CookieContents, $Expire, $Path = NULL, $Domain = NULL, $CookieHashMethod = NULL, $CookieSalt = NULL) {
//      $s = "CookieName=".$CookieName."--KeyData=".$KeyData."--CookieContents=".$CookieContents."--Expire=".$Expire."--Path=".$Path."--Domain=".$Domain."--CookieHashMethod=".$CookieHashMethod."--CookieSalt=".$CookieSalt;
//		if($CookieName!="Vanilla" && $CookieName!="Vanilla-Volatile") die($s);      
      if (is_null($Path))
         $Path = Gdn::Config('Garden.Cookie.Path', '/');

      if (is_null($Domain))
         $Domain = Gdn::Config('Garden.Cookie.Domain', '');

      // If the domain being set is completely incompatible with the current domain then make the domain work.
      $CurrentHost = $_SERVER['HTTP_HOST'];
      if (!StringEndsWith($CurrentHost, trim($Domain, '.')))
         $Domain = '';
   
      if (!$CookieHashMethod)
         $CookieHashMethod = Gdn::Config('Garden.Cookie.HashMethod');
      
      if (!$CookieSalt)
         $CookieSalt = Gdn::Config('Garden.Cookie.Salt');
      
      // Create the cookie contents
      $Key = self::_Hash($KeyData, $CookieHashMethod, $CookieSalt);
      $Hash = self::_HashHMAC($CookieHashMethod, $KeyData, $Key);
      $Cookie = array($KeyData,$Hash,time());
      if (!is_null($CookieContents)) {
         if (!is_array($CookieContents)) $CookieContents = array($CookieContents);
         $Cookie = array_merge($Cookie, $CookieContents);
      }
         
      $CookieContents = implode('|',$Cookie);

      // Create the cookie.
      supersession($CookieName, $CookieContents, $Expire, $Path, $Domain);

//      setcookie($CookieName, $CookieContents, $Expire, $Path, $Domain);
//      $_COOKIE[$CookieName] = $CookieContents;
   }
   
}

   function ArrayValue($Needle, $Haystack, $Default = FALSE) {
      $Result = GetValue($Needle, $Haystack, $Default);
		return $Result;
   }
   	function GetValue($Key, &$Collection, $Default = FALSE, $Remove = FALSE) {
		$Result = $Default;
		if(is_array($Collection) && array_key_exists($Key, $Collection)) {
			$Result = $Collection[$Key];
         if($Remove)
            unset($Collection[$Key]);
		} elseif(is_object($Collection) && property_exists($Collection, $Key)) {
			$Result = $Collection->$Key;
         if($Remove)
            unset($Collection->$Key);
      }
			
      return $Result;
	}
	
	   function StringEndsWith($A, $B, $CaseInsensitive = FALSE) {
      if (strlen($A) < strlen($B))
         return FALSE;
      elseif (strlen($B) == 0)
         return TRUE;
      else
         return substr_compare($A, $B, -strlen($B), strlen($B), $CaseInsensitive) == 0;
   }