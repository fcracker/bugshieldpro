<?php
function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function ip2country($ipAddr)
{
   //function to find country and city name from IP address
   //Developed by Roshan Bhattarai 
   //Visit http://roshanbh.com.np for this script and more.
  
  //verify the IP address for the  
  ip2long($ipAddr)== -1 || ip2long($ipAddr) === false ? trigger_error("Invalid IP", E_USER_ERROR) : "";
  // This notice MUST stay intact for legal use
  $ipDetail=array(); //initialize a blank array
  //get the XML result from hostip.info
  $xml = file_get_contents("http://api.hostip.info/?ip=".$ipAddr);
  
  preg_match("@<countryAbbrev>(.*?)</countryAbbrev>@si",$xml,$matches);
  //assign the country name to the $ipDetail array 
  return $matches[1];
} 
  
$client_ip = "202.98.5.68";//getRealIpAddr();
$client_country =  "US";//ip2country($client_ip);
?>