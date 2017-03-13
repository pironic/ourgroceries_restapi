<?php
// coded by Michael Writhe. last updated March 5 2017
// OurGroceries.com connector
// super dirty and effective. get the google assistant all up in this grocery list!

//initalize the variables as empty strings.
$email = $password = $item = "";
require_once('config.php');

// validate the requests and set the 3 variables accordingly.
if(isset($_POST['email']) && isset($_POST['password'])) {
	// not an API request, so lets require the user to know their ourgroceries.com user/pass. will work for any user.
	$email = $_POST['email'];
	$password = $_POST['password'];
	if (isset($_POST['item']) && strlen($_POST['item']) > 0) {
		$item = $_POST['item'];
	}
} elseif (isset($_GET['key']) && $_GET['key'] == $_config['apikey']) {
	// is this an API request? if a valid key is provided, remember the user/pass for use
	$email = $_config['email'];
	$password = $_config['password'];
	if (isset($_GET['item']) && strlen($_GET['item']) > 0) {
		$item = $_GET['item'];
	}
} else {
	// nothing was passed, lets just make sure that the email/pass are still blank. 
	$email = '';
	$password = '';	
}

// show the login form and prefill the items we can
?><form method="POST">
	<input type="email" placeholder="Email Address" name="email" required<?php if (strlen($email) >0){ echo " value=\"".$email."\""; } ?>>
	<input type="password" name="password" placeholder="Password" required<?php if (strlen($password) >0){ echo " value=\"".$password."\""; } ?>><br>
	<input name="item" placeholder="Item to add" required<?php if (strlen($item) >0){ echo " value=\"".$item."\""; } ?>>
	<button type=submit>submit</button>
</form><?php

// clean up the item to be proper case with no trailing or leading spaces.
$item = ucwords(trim($item));

if (strlen($email) > 0 && strlen ($password) > 0 && strlen($item) > 0) {	
	// the email/pass/item should all be set and we're ready to go!
	
	// just a little feedback for the user
	echo "adding: " . $item;
	
	// establish a connection to ourgroceries by first logging in and getting their login cookie.
	$url = "https://www.ourgroceries.com/sign-in";
	$a = new mycurl($url,true,30,1,false,true,false);
	$a->setPost("emailAddress={$email}&action=sign-me-in&password={$password}&staySignedIn=on");
	$a->createCurl();
	preg_match('/Set-Cookie: (.*);Path/', $a, $auth);
	
	// now that we've had dessert, time to add our item to the list.	
	$url = "https://www.ourgroceries.com/your-lists/";
	$c = new mycurl($url,true,30,1,false,true,false);
	// this is my list and owner id. i'm not sure what will happen if someone with a valid ourgroceries id tries to add an item to this list... death?
	// prob not that important. security by obsecurity. nobody knows about this url.
	$c->setPost('{"command":"insertItem","listId":"'.$_config['listId'].'","value":"'.$item.'","teamId":"'.$_config['teamId'].'"}');
	$headers = array('Expect:');
	array_push($headers, "Content-Type: application/json");
	array_push($headers, "Cookie: ".$auth[1]);
	$c->setHeaders($headers);
	$c->createCurl();
	
	// just a little more feedback of what happened with the request. 
	// super basic. does not even validate the list exists or is owned by the email/user combo. 
	echo "<br>\nstatus: " . $c->getHttpStatus();
	if ($c->getHttpStatus() != 200) {
		echo " : Invalid credentials. Item not added.";
	} else {
		echo " : Item added.";
	}
	//print_r($c->getHttpStatus());
} else {
	// either the user/pass was not passed to the add item function or the item was cleaned into oblivion, which means it was a dirty item.
	echo "Invalid request. Please validate the item and auth info before proceeding.";
}



// function to make item titles proper case
function ucname($string) {
    $string =ucwords(strtolower($string));

    foreach (array('-', '\'') as $delimiter) {
      if (strpos($string, $delimiter)!==false) {
        $string =implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
      }
    }
    return $string;
}

// helper class to make requests via php.net comments section on curl manpage
class mycurl { 
     protected $_useragent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1'; 
     protected $_url; 
     protected $_followlocation; 
     protected $_timeout; 
     protected $_maxRedirects; 
     protected $_cookieFileLocation = './cookie.txt'; 
     protected $_post; 
     protected $_postFields; 
     protected $_referer ="http://www.google.com"; 
	 protected $_headers = array('Expect:');

     protected $_session; 
     protected $_webpage; 
     protected $_includeHeader; 
     protected $_noBody; 
     protected $_status; 
     protected $_binaryTransfer; 
     public    $authentication = 0; 
     public    $auth_name      = ''; 
     public    $auth_pass      = ''; 

     public function useAuth($use){ 
       $this->authentication = 0; 
       if($use == true) $this->authentication = 1; 
     } 

     public function setName($name){ 
       $this->auth_name = $name; 
     } 
     public function setPass($pass){ 
       $this->auth_pass = $pass; 
     } 

     public function __construct($url,$followlocation = true,$timeOut = 30,$maxRedirecs = 4,$binaryTransfer = false,$includeHeader = false,$noBody = false) 
     { 
         $this->_url = $url; 
         $this->_followlocation = $followlocation; 
         $this->_timeout = $timeOut; 
         $this->_maxRedirects = $maxRedirecs; 
         $this->_noBody = $noBody; 
         $this->_includeHeader = $includeHeader; 
         $this->_binaryTransfer = $binaryTransfer; 

         $this->_cookieFileLocation = dirname(__FILE__).'/cookie.txt'; 

     } 

     public function setReferer($referer){ 
       $this->_referer = $referer; 
     } 

     public function setCookiFileLocation($path) 
     { 
         $this->_cookieFileLocation = $path; 
     } 

     public function setPost ($postFields) 
     { 
        $this->_post = true; 
        $this->_postFields = $postFields; 
     } 
	 
	 public function setHeaders($headers)
	 {
		 $this->_headers = $headers;
	 }

     public function setUserAgent($userAgent) 
     { 
         $this->_useragent = $userAgent; 
     } 

     public function createCurl($url = 'nul') 
     { 
        if($url != 'nul'){ 
          $this->_url = $url; 
        } 

         $s = curl_init(); 

         curl_setopt($s,CURLOPT_URL,$this->_url); 
         curl_setopt($s,CURLOPT_HTTPHEADER,$this->_headers); 
         curl_setopt($s,CURLOPT_TIMEOUT,$this->_timeout); 
         curl_setopt($s,CURLOPT_MAXREDIRS,$this->_maxRedirects); 
         curl_setopt($s,CURLOPT_RETURNTRANSFER,true); 
         curl_setopt($s,CURLOPT_FOLLOWLOCATION,$this->_followlocation); 
         curl_setopt($s,CURLOPT_COOKIEJAR,$this->_cookieFileLocation); 
         curl_setopt($s,CURLOPT_COOKIEFILE,$this->_cookieFileLocation); 

         if($this->authentication == 1){ 
           curl_setopt($s, CURLOPT_USERPWD, $this->auth_name.':'.$this->auth_pass); 
         } 
         if($this->_post) 
         { 
             curl_setopt($s,CURLOPT_POST,true); 
             curl_setopt($s,CURLOPT_POSTFIELDS,$this->_postFields); 

         } 

         if($this->_includeHeader) 
         { 
               curl_setopt($s,CURLOPT_HEADER,true); 
         } 

         if($this->_noBody) 
         { 
             curl_setopt($s,CURLOPT_NOBODY,true); 
         } 
         /* 
         if($this->_binary) 
         { 
             curl_setopt($s,CURLOPT_BINARYTRANSFER,true); 
         } 
         */ 
         curl_setopt($s,CURLOPT_USERAGENT,$this->_useragent); 
         curl_setopt($s,CURLOPT_REFERER,$this->_referer); 

         $this->_webpage = curl_exec($s);
                   $this->_status = curl_getinfo($s,CURLINFO_HTTP_CODE); 
         curl_close($s); 

     } 

   public function getHttpStatus() 
   { 
       return $this->_status; 
   } 

   public function __tostring(){ 
      return $this->_webpage; 
   } 
} 
?> 
