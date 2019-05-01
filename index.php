<?php
// coded by Michael Writhe. last updated May 1, 2019
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
    if ($email == $_config['email'] && $password == $_config['password'])
        $listId = $_config['listId'];
    else
        $listId = "";
    if (isset($_POST['item']) && strlen($_POST['item']) > 0) {
        $item = $_POST['item'];
    }
} elseif (isset($_GET['key']) && $_GET['key'] == $_config['apikey']) {
    // is this an API request? if a valid key is provided, remember the user/pass for use
    $email = $_config['email'];
    $password = $_config['password'];
    $listId = $_config['listId'];
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
    <input name="listId" placeholder="ListID" <?php if (strlen($listId) >0){ echo " value=\"".$listId."\""; } ?>>
    <input name="item" placeholder="Item to add" <?php if (strlen($item) >0){ echo " value=\"".$item."\""; } ?>>
    <button type=submit>submit</button>
</form><?php

// clean up the item to be proper case with no trailing or leading spaces.
$item = ucwords(trim($item));

if (strlen($email) > 0 && strlen ($password) > 0 && strlen ($listId) > 0 && strlen($item) > 0) {
    // the email/pass/item should all be set and we're ready to go!

    // just a little feedback for the user
    echo "adding: " . $item;

    // establish a connection to ourgroceries by first logging in and getting their login cookie.
    $data_string = "emailAddress={$email}&action=sign-me-in&password={$password}&staySignedIn=on";
    $ch = curl_init('https://www.ourgroceries.com/sign-in');
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,   // return web page
        CURLOPT_HEADER         => true,   // return headers
        CURLOPT_USERAGENT      => "OurGroceries API by pironic",
        CURLOPT_POSTFIELDS     => $data_string,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_HTTPHEADER     => array(
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($data_string))
    ));
    $result = curl_exec($ch); curl_close($ch);
    if (!$result)
        throw new Exception ("error 64");

    // parse out the auth cookie for future requests.
    preg_match('/Set-Cookie: (.*);Path/', $result, $auth);
    if (!isset($auth[1]) || strlen($auth[1]) < 1)
        throw new Exception ("no auth cookie found in response");
    else
        $cookie = $auth[1];

    // let's also get the TeamID with the cookie.
    $ch = curl_init('https://www.ourgroceries.com/your-lists/');
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,   // return web page
        CURLOPT_HEADER         => false,   // return headers
        CURLOPT_USERAGENT      => "OurGroceries API by pironic",
        CURLOPT_HTTPHEADER     => array(
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: '. $cookie)
    ));
    $result = curl_exec($ch); curl_close($ch);
    if (!$result)
        throw new Exception ("error 84");
    preg_match('/g_teamId = "((:?[A-Z]|[a-z])+)";/', $result, $teamId);
    if (!isset($teamId[1]) || strlen($teamId[1]) < 1)
        throw new Exception ("no teamId found");
    else
        $teamId = $teamId[1];

    // time to add our item to the list.
    $data_string = '{"command":"insertItem","listId":"'.$listId.'","value":"'.$item.'","teamId":"'.$teamId.'"}';
    $ch = curl_init('https://www.ourgroceries.com/your-lists/');
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,   // return web page
        CURLOPT_HEADER         => true,   // return headers
        CURLOPT_USERAGENT      => "OurGroceries API by pironic",
        CURLOPT_POSTFIELDS     => $data_string,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_HTTPHEADER     => array(
            'Accept: application/json, text/javascript, */*',
            'Content-Type: application/json; charset=UTF-8',
            'Cookie: '. $cookie,
            'Content-Length: ' . strlen($data_string),
            'X-Requested-With: XMLHttpRequest')
    ));
    $result = curl_exec($ch);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // just a little more feedback of what happened with the request.
    echo "<br>\nstatus: " . $http_code;
    if ($http_code != 200) {
        echo " : Invalid credentials. Item not added.";
    } else {
        echo " : Item added.";
    }
} else {
    // either the user/pass was not passed to the add item function or the item was cleaned into oblivion, which means it was a dirty item.
    echo "Invalid request. Please validate the item and auth info before proceeding.";
}
?> 
