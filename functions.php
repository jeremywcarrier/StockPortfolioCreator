<?php 

function isLoggedOn() {
	if (isset($_SESSION["user_acct"])) return true;
	return false;
}
function logout() {
	if (isset($_SESSION["user_acct"])){
		unset($_SESSION['user_acct']);
		foreach($_SESSION as $key=>$value)
		{
			unset($_SESSION[$key]);
		}
	}
}

//ensures an ip address is both a valid IP and does not fall within a private network range
function isIP($ip) 
{
	return (filter_var($ip, FILTER_VALIDATE_IP)); //FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
}
//gets ip address - REMOTE_ADDR most reliable otherwise can be spoofed
function getIP() 
{
	// if (isset($_SERVER["HTTP_CLIENT_IP"])) return $_SERVER["HTTP_CLIENT_IP"];
    // elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) return $_SERVER["HTTP_X_FORWARDED_FOR"];
    // elseif (isset($_SERVER["HTTP_X_FORWARDED"])) return $_SERVER["HTTP_X_FORWARDED"];
    // elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])) return $_SERVER["HTTP_FORWARDED_FOR"];
    // elseif (isset($_SERVER["HTTP_FORWARDED"])) return $_SERVER["HTTP_FORWARDED"];
    // else
	return $_SERVER["REMOTE_ADDR"];
}
//generates a random salt
function getSalt() 
{
	return base64_encode(strtr(strtr(base64_encode(mcrypt_create_iv(256, MCRYPT_DEV_URANDOM)), '+', '.'), '$', '.')); //512
}
//generates a hsshed + salted password result (much more complex than the simple hash function)
function getHashedPassword($password, $salt)
{
	return hash('whirlpool', hash('sha512', $password) . $salt);
	//this php version does not support crypt with hash specification - http://php.net/manual/en/function.crypt.php
	//return crypt(hash('whirlpool', $password . $salt), '$6$rounds=1000$' . $salt . '$');  //SHA512 - $6$ - hashing algorithm would run 1000 times to produce the final hash
}

function validateSignup($acct, $pw1, $pw2, $amount) {
	$illegalchars = '/[\(\)\<\>\,\;\:\/\"\[\]\\\\]/';
	//$amountchars = '/[\d*\.?\d*/]';
	try { 
		if (strlen(utf8_decode($acct)) < 2 || strlen(utf8_decode($acct)) > 50 || preg_match($illegalchars, $acct) || 
		strlen(utf8_decode($pw1)) < 6 || strlen(utf8_decode($pw1)) > 50 || preg_match($illegalchars, $pw1) || $pw1 != $pw2) return false;
		
		if (strlen($amount) < 2 || !is_numeric($amount) || (double)$amount < 1000 || (double)$amount > 1000000) {
			return false;
		}

		return true;
	}
	catch (Exception $e) { return false; }
}
function validateLogin($acct, $pw) {
	$illegalchars = '/[\(\)\<\>\,\;\:\/\"\[\]\\\\]/';
	
	if (strlen(utf8_decode($acct)) < 2 || strlen(utf8_decode($acct)) > 50 || preg_match($illegalchars, $acct) || 
	strlen(utf8_decode($pw)) < 6 || strlen(utf8_decode($pw)) > 50 || preg_match($illegalchars, $pw)) return false;
	
	return true;
}


function array_copy($arr) {
    $newArray = array();
    foreach ($arr as $key => $value) {
        if (is_array($value)) $newArray[$key] = array_copy($value);
        else if (is_object($value)) $newArray[$key] = clone $value;
        else $newArray[$key] = $value;
    }
    return $newArray;
}

?>