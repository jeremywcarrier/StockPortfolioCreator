<!DOCTYPE html>
<html>
<head>
<title>Portfolio Manager</title>
<?php //include("includes/head.php"); ?>
</head>
<body>

<?php include("includes/errorhandling.php"); ?>
<?php include("includes/functions.php"); ?>
<?php include("includes/database.php"); ?>
<?php include("includes/YahooFinanceAPI.php"); ?>

<?php

if(!isset($_SESSION) && session_id() === '') {
    session_start();
	//echo session_id() . '<br />';
}
//session_destroy();

// Using Google APIS:

// <script type="text/javascript" src="http://www.google.com/jsapi"></script>
// <script>
// contry_code = google.loader.ClientLocation.address.country_code
// city = google.loader.ClientLocation.address.city
// region = google.loader.ClientLocation.address.region
// </script>

//test
 // foreach($_POST as $name => $content) { // Most people refer to $key => $value
    // echo "The HTML name: $name <br>";
    // echo "The content of it: $content <br>";
 // }
//UTC_TIMESTAMP()

if (isset($_POST["isLogout"]) && isLoggedOn()) {
	logout();
	$_SESSION["notice"] = "Logout successful.";
	session_write_close();
	header("Location: " . $_SERVER["PHP_SELF"]); //$_SERVER["SCRIPT_URI"]);
	exit();
}
else if (isset($_POST["islogin"]) && !isLoggedOn()) {
	$canlogin = true;
	if (isset($_POST["loginacct"]) && isset($_POST["loginpw"]) && validateLogin($_POST["loginacct"],$_POST["loginpw"]))  {

		$hd = mysql_connect($host, $username, $password) or die ("Unable to connect.");
		mysql_select_db ($db, $hd) or die ("Unable to access database.");
		
		$loginacct = mysql_real_escape_string($_POST["loginacct"]);
		$loginpw = $_POST["loginpw"];
		
		$query = mysql_query("SELECT * FROM user_accounts WHERE user_name = '$loginacct'", $hd) or die ("Unable to run query.");
		$nrows = mysql_num_rows($query);
		
		if ($nrows > 0) {
			$row = mysql_fetch_assoc($query);
			$storedhash = $row["user_pw"];
			$decodedsalt = $row["user_salt"];
			$amount = $row["user_amount"];
			
			if(getHashedPassword($loginpw, base64_encode($decodedsalt)) === $storedhash) {
				$salt = getSalt();
				$newdecodedsalt = base64_decode($salt);
				//$encodedSalt = base64_encode($decodedSalt);
				$newhash = getHashedPassword($loginpw, $salt);
				$query = mysql_query("UPDATE user_accounts SET user_pw = '$newhash', user_salt = '$newdecodedsalt' WHERE user_name = '$loginacct'", $hd) or die ("Unable to run query.");
				$_SESSION["user_acct"] = $loginacct;
				$_SESSION["user_amount"] = $amount;
				$_SESSION["notice"] = "Login successful - $loginacct.";
			}
			else $canlogin = false;
		}
		else $canlogin = false;
	}
	else $canlogin = false;	

	mysql_close($hd);

	if (!$canlogin) {
		$_SESSION["notice"] = "Login failed.";
		if (isset($_SESSION["loginattempts"])) {
			$_SESSION["loginattempts"] = $_SESSION["loginattempts"] + 1; //limit the number of failed login attempts.
		}
		else $_SESSION["loginattempts"] = 1;
	}
	session_write_close();
	header("Location: " . $_SERVER["PHP_SELF"]); //$_SERVER["SCRIPT_URI"]);
	exit();
}
else if (isset($_POST["issignup"]) && !isLoggedOn()) {
	if (isset($_POST["signupacct"]) && isset($_POST["signuppw"]) && isset($_POST["signuppw2"]) && isset($_POST["signupamount"]) && validateSignup($_POST["signupacct"], $_POST["signuppw"], $_POST["signuppw2"], $_POST["signupamount"])) {
		
		$hd = mysql_connect($host, $username, $password) or die ("Unable to connect.");
		mysql_select_db ($db, $hd) or die ("Unable to access database.");
		
		$acct = mysql_real_escape_string($_POST["signupacct"]);
		$pw1 = $_POST["signuppw"];
		$pw2 = $_POST["signuppw2"];
		$amount = mysql_real_escape_string($_POST["signupamount"]);
		
		$ip = getIP();
		
		$query = mysql_query("SELECT * FROM user_accounts WHERE user_name = '$acct'", $hd) or die ("Unable to run query.");
		$nrows = mysql_num_rows($query);
		
		$query = mysql_query("SELECT * FROM user_accounts WHERE user_ip = '$ip'", $hd) or die ("Unable to run query.");
		$nrows2 = mysql_num_rows($query);
		
		if ($nrows > 0) {
			$_SESSION["notice"] = "Account name already exists.";
		}
		else if ($nrows2 > 5) {
			$_SESSION["notice"] = "Account was not made - too many accounts made by the IP.";
		}
		else {
			$salt = getSalt();
			$decodedSalt = base64_decode($salt);
			//$encodedSalt = base64_encode($decodedSalt);
			$hashedPW = getHashedPassword($pw1, $salt);
			$time = $_SERVER["REQUEST_TIME"];
			$query = mysql_query("INSERT INTO user_accounts (user_name, user_pw, user_salt, user_ip, user_utc_date, user_amount, user_starting_amount) VALUES ('$acct', '$hashedPW', '$decodedSalt', '$ip', '$time', '$amount', '$amount')", $hd) or die ("Unable to create account.");
			$_SESSION["notice"] = "Account successfully created.";
		}
		
		mysql_close($hd);
	}
	else $_SESSION["notice"] = "Account could not be created.";
	
	session_write_close();
	header("Location: " . $_SERVER["PHP_SELF"]); //$_SERVER["SCRIPT_URI"]);
	exit();
}
else if (isset($_POST["buyTickerValue"]) && isLoggedOn()) {
	$buyValues = explode("|", $_POST["buyTickerValue"]);
	$buyTicker = $buyValues[0];
	$buyPrice = floatval($buyValues[1]);
	$buyExchange = $buyValues[2];
	$buyShares = intval($buyValues[3]);
	$userAcct = $_SESSION["user_acct"];
	
	$hd = mysql_connect($host, $username, $password) or die ("Unable to connect.");
	mysql_select_db ($db, $hd) or die ("Unable to access database.");
	
	$query = mysql_query("SELECT * FROM user_accounts WHERE user_name = '$userAcct'", $hd) or die ("Unable to run query.");
	$nrows = mysql_num_rows($query);
	
	if ($nrows > 0) { 
		$row = mysql_fetch_assoc($query);
		$amount = floatval($row["user_amount"]);
		$buyAmount = ($buyPrice * $buyShares);
		
		if ($amount >= $buyAmount) {
			$amount -= $buyAmount;
			$_SESSION["user_amount"] = $amount;
			$time = $_SERVER["REQUEST_TIME"];
			
			$query = mysql_query("UPDATE user_accounts SET user_amount='$amount' WHERE user_name='$userAcct'", $hd) or die ("Unable to run query.");
			
			$query = mysql_query("SELECT shares, purchase_value FROM user_data WHERE user_name='$userAcct' AND ticker='$buyTicker' AND exchange='$buyExchange'", $hd) or die ("Unable to run query.");
			$nrowsTck = mysql_num_rows($query);
			if ($nrowsTck > 0) { //update
				$rowTck = mysql_fetch_assoc($query);
				$shares = intval($rowTck["shares"]) + $buyShares;
				$purchaseValue = floatval($rowTck["purchase_value"]) + ($buyShares * $buyPrice);
				$query = mysql_query("UPDATE user_data SET purchase_value='$purchaseValue', shares='$shares', last_purchase_price='$buyPrice', purchase_utc_date='$time' WHERE user_name='$userAcct' AND ticker='$buyTicker' AND exchange='$buyExchange'", $hd) or die ("Unable to run query.");
			}
			else { //insert
				$purchaseValue = ($buyShares * $buyPrice);
				$companyName = '';
				try {
					$symbol = (explode(".",  $buyTicker));
					$url = 'http://www.google.ca/finance?q=' . str_replace("TSX","TSE",$buyExchange) . ':' . $symbol[0];
					$doc = new DOMDocument();
					@$doc->loadHTMLFile($url);
					$xpath = new DOMXPath($doc);
					if (!is_null($xpath)) {
						$urlTitle = explode(":", ($xpath->query('//title')->item(0)->nodeValue));
						$companyName = $urlTitle[0];
					}
				}
				catch (Exception $e) { }
					
				$query = mysql_query("INSERT INTO user_data (user_name, ticker, company_name, exchange, shares, purchase_value, purchase_utc_date, last_purchase_price) VALUES ('$userAcct', '$buyTicker', '$companyName', '$buyExchange', '$buyShares', '$purchaseValue', '$time', '$buyPrice')", $hd) or die ("Unable to run query.");
			}
			$_SESSION["notice"] = $buyShares . " share(s) of " . $buyTicker . " were successfully bought. " . "<span style='color:red;'>-$" . number_format($buyAmount, 2) . "</span>";
		}
		else $_SESSION["notice"] = "Error - shares could not be added.";
	}
	else $_SESSION["notice"] = "Error - shares could not be added.";
		
	session_write_close();
	header("Location: " . $_SERVER["PHP_SELF"]); //$_SERVER["SCRIPT_URI"]);
	exit();
}
else if (isset($_POST["sellTickerValue"]) && isLoggedOn()) {
	$sellValues = explode("|", $_POST["sellTickerValue"]);
	$sellTicker = $sellValues[0];
	$sellExchange = $sellValues[1];
	$sellPrice = floatval($sellValues[2]); //maybe update with the current price?
	$sellTotalShares = intval($sellValues[3]);
	$sellShares = intval($sellValues[4]);
	$userAcct = $_SESSION["user_acct"];
	$userAmount = $_SESSION["user_amount"];
	
	if ($sellTotalShares >= $sellShares) {
		//should check database for updating
		$newShares = $sellTotalShares - $sellShares;
		$sellAmount = $sellShares * $sellPrice;
		$newUserAmount = $userAmount + $sellAmount;
		
		$hd = mysql_connect($host, $username, $password) or die ("Unable to connect.");
		mysql_select_db ($db, $hd) or die ("Unable to access database.");
		
		$query = mysql_query("UPDATE user_accounts SET user_amount='$newUserAmount' WHERE user_name='$userAcct'", $hd) or die ("Unable to run query.");
		$_SESSION["user_amount"] = $newUserAmount;
		
		if ($newShares > 0) {
			$query = mysql_query("SELECT shares, purchase_value FROM user_data WHERE user_name='$userAcct' AND ticker='$sellTicker' AND exchange='$sellExchange'", $hd) or die ("Unable to run query.");
			$nrowsTck = mysql_num_rows($query);
			if ($nrowsTck > 0) {
				$rowTck = mysql_fetch_assoc($query);
				$purchaseValue = floatval($rowTck["purchase_value"]);
				$totalShares = intval($rowTck["shares"]);
				$newPurchaseValue = $purchaseValue - round((($purchaseValue / $totalShares) * $sellShares), 2);
				$query = mysql_query("UPDATE user_data SET shares='$newShares', purchase_value='$newPurchaseValue' WHERE user_name='$userAcct' AND ticker='$sellTicker' AND exchange='$sellExchange'", $hd) or die ("Unable to run query.");
			}
		}
		else { //remove data
			$query = mysql_query("DELETE FROM user_data WHERE user_name='$userAcct' AND ticker='$sellTicker' AND exchange='$sellExchange'", $hd) or die ("Unable to run query.");
		}
		$sellSymbol = str_replace(".TO", "", $sellTicker);
		$_SESSION["notice"] = $sellShares . " share(s) of " . $sellSymbol . " were successfully sold. " . "<span style='color:green;'>+$" . number_format($sellAmount, 2) . "</span>";
	}
	else $_SESSION["notice"] = "Error - shares could not be sold.";
	
	session_write_close();
	header("Location: " . $_SERVER["PHP_SELF"]); //$_SERVER["SCRIPT_URI"]);
	exit();
}
else if (!isLoggedOn()) {


}


if (isset($_SESSION["notice"])) {
	echo $_SESSION["notice"];
	unset($_SESSION['notice']);
}
?>


<style type="text/css">

.tableSpaced td, .tableSpaced th { 
	padding: 5px 5px; 
	white-space: nowrap;
}
.spanOpacity { 
	opacity: 0.7;
}
.positiveChange { 
	color: green; 
}
.arrowChange.positiveChange:after { 
	content: url('images/uparrow.png');
}
.negativeChange { 
	color: red; 
}
.arrowChange.negativeChange:after { 
	content: url('images/downarrow.png');
} 
.negativeChange:before { 
	content: "";
} 
.tooltip {
	display: inline;
	position: relative;
}
.tooltip:hover:after{
    background: rgba(255,255,255,.75);
	border: 1px black solid;
    border-radius: 5px;
    bottom: 26px;
    content: attr(title);
    left: 10%;
    padding: 5px 15px;
    position: absolute;
    z-index: 98;
	width: 175px;
	white-space: pre-wrap;
	font-style: normal;
	text-transform:none;

}
</style>
<noscript>
	<style type="text/css">.submitButton, #loginDiv, #accountDiv { display:none; } </style>
	<p style="color:red;">Please enable JavaScript.</p>
</noscript> 

<?php if (isLoggedOn()) {  ?>
<div id="accountDiv">
	<form action="portfolio.php" method="post" id="logoutForm">
		<input type="submit" class="submitButton" value="Log Out" id="logoutButton" name="logoutButton" disabled="disabled" />
		<input id="isLogout" name="isLogout" type="hidden" />
	</form>
	<script type="text/javascript">
		document.getElementById("logoutButton").disabled = false;
		var userAmount = <?php if (isset($_SESSION["user_amount"])) echo $_SESSION["user_amount"]; else echo 0; ?>;
	</script>
	<?php echo "<div style=''>" . $_SESSION["user_acct"] . ' : $' . number_format($_SESSION["user_amount"], 2) . "</div>"; ?>
	<div id="portfolioArea"><fieldset><legend><strong>Your Portfolio: </strong></legend>
	<?php 
		$hd = mysql_connect($host, $username, $password) or die ("Unable to connect.");
		mysql_select_db ($db, $hd) or die ("Unable to access database.");
		$acct = $_SESSION["user_acct"];
		$query = mysql_query("SELECT user_starting_amount FROM user_accounts WHERE user_name = '$acct'", $hd) or die ("Unable to run query.");
		$nrows = mysql_num_rows($query);
		$startingAmount = 1;
		if ($nrows > 0) {
			$row = mysql_fetch_assoc($query);
			$startingAmount = $row["user_starting_amount"];
		}
		$query = mysql_query("SELECT * FROM user_data WHERE user_name = '$acct' ORDER BY ticker", $hd) or die ("Unable to run query.");
		$nrows = mysql_num_rows($query);
		$currentPortfolioValue = $_SESSION["user_amount"];
		if ($nrows > 0)  {
			$portfolio = array();
			$tickers = array();
			while ($row = mysql_fetch_assoc($query)) {
				$portfolio[] = array_copy($row);
				$tickers[] = $row["ticker"];
			}
			try {
				$y = api($tickers);
			}
			catch (Exception $e) {  echo "<span style='color:red;font-weight:bold;'>Error getting data.</span><br />"; }
			
			if (count($y) > 0) {
				$count = 0;
				echo '<table class="tableSpaced" cellspacing="0"><tr><th>Name</th><th>Symbol</th><th>Stock Exchange</th><th>Shares</th><th>Last Purchase Price</th><th>Current Price</th><th>Last Change</th><th>Purchase Value</th><th>Current Value</th><th>$ Change</th><th>% Change</th><th></th></tr>';
				//while ($row = mysql_fetch_assoc($query)) {
				foreach ($portfolio as $tickerRow) {
					if ($count % 2 != 0) echo '<tr style="background-color:azure">';
					else echo '<tr>';
					$name = (strlen($y[$count]["Name"]) > 1) ? $y[$count]["Name"] : '';
					$ticker = $tickerRow['ticker'];
					$symbol = (explode(".",  $ticker));
					$exchange = $tickerRow['exchange'];
					$companyName = $tickerRow['company_name'];
					
					echo '<td><span ' . ((strlen($companyName) > 0) ? 'class="tooltip" title="' . $companyName . '"' : '') . ' style="text-transform:uppercase;font-style:italic;">' . $name . '</span></td>';
					echo '<td><a href="https://www.google.ca/finance?q=' . str_replace("TSX","TSE",$exchange) . ':' . $symbol[0] . '">' . $symbol[0] . '</a></td>';
					echo '<td>' . $exchange . '</td>';
					$shares =  $tickerRow['shares'];
					echo '<td>' . $shares . '</td>';
					echo '<td>$' . number_format($tickerRow['last_purchase_price'], 2) . '</td>';

					$open = $y[$count]["Open"];
					$close = $y[$count]["PreviousClose"];
					$dayRange = $y[$count]["DaysRange"];
					$yearRange = $y[$count]["YearRange"];
					$oneYrTarget = $y[$count]["OneyrTargetPrice"];
					
					$currentPrice = $y[$count]["LastTradePriceOnly"];
					echo '<td><a class="tooltip" title="Last Open: $' . $open . ' &#10;Last Close: $' . $close . ' &#10;Day Range: $' . str_replace("- ","- $",$dayRange) . ' &#10;Year Range: $' . str_replace("- ","- $",$yearRange) . ' &#10;One Year Target: $' . $oneYrTarget . '">$' . number_format($currentPrice, 2) . '</a></td>';
					
					$changeRealTime = $y[$count]["ChangeRealtime"];
					$changeRealTimeNumber = (float)$changeRealTime;
					$changeRealTimeDirection = '';
					if ($changeRealTimeNumber > 0) $changeRealTimeDirection = 'positiveChange';
					else if ($changeRealTimeNumber < 0) $changeRealTimeDirection = 'negativeChange';
					echo '<td><span class="arrowChange ' . $changeRealTimeDirection . '">' . number_format($changeRealTime, 2) . '</span></td>';
					
					$purchaseValue = $tickerRow['purchase_value'];
					echo '<td>$' . number_format($purchaseValue, 2) . '</td>';
					$currentValue = ($currentPrice * $shares);
					$currentPortfolioValue += $currentValue;
					echo '<td>$' . number_format($currentValue, 2) . '</td>';
					$absoluteChange = (round($currentValue, 2) - round($purchaseValue, 2));
					$percentChange = round(0, 2);
					if ($purchaseValue != 0) $percentChange = round(((($currentValue / $purchaseValue) - 1) * 100), 2);
					$changeDirection = '';
					if ($absoluteChange > 0) $changeDirection = 'positiveChange';
					else if ($absoluteChange < 0) $changeDirection = 'negativeChange';
					echo '<td><span class="' . $changeDirection . '">$' . number_format($absoluteChange, 2) . '</span></td>';
					echo '<td><span class="' . $changeDirection . '">' . number_format($percentChange, 2) . '%</span></td>';

					echo '<td><form action="portfolio.php" method="post" id="sellTickerForm' . $count . '">';
					echo '<input size="10" type="text" id="sellticker' . $count . '" name="sellticker' . $count . '" maxlength="10" onkeyup="formatSellShares(this, ' . $count . ')" onkeydown="if (event.keyCode == 13) {sellTicker(' . $count . ');return false;}" />';
					echo '<input type="button" class="submitButton" value="Sell Shares" id="sellTickerButton' . $count . '" name="sellTickerButton' . $count . '" onclick="sellTicker(' . $count . ')" disabled="disabled" />';
					echo '<input id="sellTickerValue' . $count . '" name="sellTickerValue" value="' . $ticker . '|' . $exchange . '|' . $currentPrice . '|' . $shares . '" type="hidden" />';
					echo '</form></td>';
					echo '</tr>';
					$count++;
					// foreach ($row as $key => $value) {
						// echo "<td>Key: $key; <br/>Value: $value</td>";
					// }	
				}
				echo '</table>'; 
			}
			else echo "Error with retrieving data.";
		}
		else {
			echo "Portfolio is currently empty.<br />";
		}
		mysql_close($hd);
	?>
	</fieldset></div>
	<?php 
	$return = number_format(((($currentPortfolioValue - $startingAmount) / $startingAmount) * 100), 2);
	echo "<div style='margin-top:0px;margin-bottom:10px;'>Total Value : $" . number_format($currentPortfolioValue, 2) . " | Total Return : " . $return . "% </div>"; ?>
	<form>Search for Ticker: <input type="text" size="10" name="searchticker" id="searchticker" maxlength="10" onkeyup="formatTicker(this)" onkeydown="if (event.keyCode == 13) {queryTicker();return false;}" /><input type="button" class="submitButton" value="Go" id="tickerButton" name="tickerButton" onclick="queryTicker()" /></form>
	<div id="tickerresult"></div><div id="tickerresultlist" style="display:none;"></div>
</div>
<?php } else { ?> 
<div id="loginDiv">
	<form action="portfolio.php" method="post" id="loginForm" onsubmit="return validateForm(this)" >
		<strong>Login: </strong>
		Username: <input type="text" placeholder="Username" name="loginacct" id="loginacct" maxlength="50" onkeyup="maxlen(this, 50)" autocomplete="off" />
		Password: <input type="password" placeholder="Password" name="loginpw" id="loginpw" maxlength="50" onkeyup="maxlen(this, 50)" autocomplete="off" />
		<input type="submit" class="submitButton" value="Submit" id="loginButton" name="loginButton" disabled="disabled" />
		<input id="islogin" name="islogin" type="hidden" />
		OR 
		<input type="button" name="createButton" id="createButton" value="Create Account" onclick="showSignupForm()" disabled="disabled" />
		
		<!-- <input type="button" name="scoreButton" id="scoreButton" value="OTPP Score Board" onclick="showScoreForm()" disabled="disabled" /> -->
	</form>
	<form action="portfolio.php" method="post" id="signupForm" onsubmit="return validateForm(this)" style="display:none;">
		<fieldset><legend><strong>New Account: </strong></legend>
		<table>
		<tr><td>Username: </td><td><input type="text" name="signupacct" id="signupacct" maxlength="50" onkeyup="maxlen(this, 50)"/></td></tr>
		<tr><td>Password: </td><td><input type="password" name="signuppw" id="signuppw" maxlength="50" onkeyup="maxlen(this, 50)" /></td></tr>
		<tr><td>Confirm Password: </td><td><input type="password" name="signuppw2" id="signuppw2" maxlength="50" onkeyup="maxlen(this, 50)" /></td></tr>
		<tr><td>Starting Amount ($1000 - $1000000): </td><td><input type="text" name="signupamount" id="signupamount" maxlength="10" onkeyup="formatAmount(this)" value="10000" /></td></tr>
		<tr><td><label><input type="checkbox" name="fx" id="fxcheckbox" value="1-1 CAD/USD Rate" disabled="disabled" checked="checked" />1-1 CAD/USD Rate</label>
		<?php 
			$url = 'http://www.google.ca/finance?q=CADUSD';
			$doc = new DOMDocument();
			@$doc->loadHTMLFile($url);
			$xpath = new DOMXPath($doc);
			$rate - '_ USD';
			if (!is_null($xpath)) {
				$nodes = $xpath->query('//span[@class="bld"]');
				foreach($nodes as $node) {
					$nodeValue = $node->nodeValue;
					if ((strlen($nodeValue) > 1) && (strpos($nodeValue, 'USD') !== FALSE)) {
						$rate = $nodeValue;
						break;
					}
				}
			}
			echo ' (<em>1 CAD = ' . $rate . '</em>)';
		?>
		</td></tr>
		<tr><td><label><input type="checkbox" name="brokerage" id="brokeragecheckbox" value="2% Transaction Fee" disabled="disabled"/>2% Transaction Fee</label></td></tr>
		<tr><td>Exchanges: <label><input type="checkbox" name="tsx" id="tsxcheckbox" value="TSX" disabled="disabled" checked="checked" />TSX</label>
		<label><input type="checkbox" name="nyse" id="nysecheckbox" value="NYSE" disabled="disabled" checked="checked" />NYSE</label>
		<label><input type="checkbox" name="NASDAQ" id="NASDAQcheckbox" value="NASDAQ" disabled="disabled" checked="checked" />NASDAQ</label></td></tr>
		<tr><td><input type="submit" class="submitButton" value="Submit" id="signupButton" name="signupButton" disabled="disabled" /><span id="warning" style="color:red;margin-left:5px;"></span>
		<input id="issignup" name="issignup" type="hidden" /></td></tr>
		</table>
		<span><small>Note: Password is hashed (with a strong hash) and salted. A new hash/salt made at every login.</small></span>
		</fieldset>
	</form>
	<form id="scoreForm" style="display:none;">
		<fieldset><legend><strong>Market Makers: </strong></legend>
		<?php 
			/* 
			$userArray = array(  
				'JC' => 0,
				'Curtis' => 0,
				'Shardul' => 0, 
				'jon' => 0,
				'minju' => 0,
				'c-money' => 0,
				'jon2' => 0
			);
			$totalUserValue = 0;
			
			$hd = mysql_connect($host, $username, $password) or die ("Unable to connect.");
			mysql_select_db ($db, $hd) or die ("Unable to access database.");
			
			foreach ($userArray as $key => $value) {
				$totalValue = 0;
							
				$query = mysql_query("SELECT * FROM user_accounts WHERE user_name = '$key'", $hd) or die ("Unable to run query.");
				$nrows = mysql_num_rows($query);
				if ($nrows > 0)  {
					$row = mysql_fetch_assoc($query);
					$totalValue = $row["user_amount"];
				}
				
				$dataquery = mysql_query("SELECT * FROM user_data WHERE user_name = '$key'", $hd) or die ("Unable to run query.");
				$ndatarows = mysql_num_rows($dataquery);
				if ($ndatarows > 0)  {
					$portfolio = array();
					$tickers = array();
					while ($datarow = mysql_fetch_assoc($dataquery)) {
						$portfolio[] = array_copy($datarow);
						$tickers[] = $datarow["ticker"];
					}
					try {
						$y = api($tickers);
					}
					catch (Exception $e) { echo "<span style='color:red;font-weight:bold;'>Error getting data.</span><br />"; }
					
					if (count($y) > 0) {
						$count = 0;
						foreach ($portfolio as $tickerRow) {
							$shares =  $tickerRow['shares'];
							$currentPrice = $y[$count]["LastTradePriceOnly"];
							$totalValue += ($currentPrice * $shares);
							$count++;
						}
					}
				}
				
				$userArray[$key] = $totalValue;
				$totalUserValue += $totalValue;
			}
			
			mysql_close($hd);
			
			arsort($userArray);
			$averageUserValue = ($totalUserValue / count($userArray));
			
			foreach ($userArray as $key => $value) {
				$textColor = 'black';
				if ($value > $averageUserValue) {
					$textColor = 'green';
				}
				else if ($value < $averageUserValue) {
					$textColor = 'red';
				}
				echo $key . ' : <span style="color:' . $textColor . '">$' . number_format($value, 2) . '</span><br />';
			}
			
			*/
		?>
	</form>
</div>
	<p style="font-size:small;margin-top:0.2em;">Last Modified: <?php echo date ("F d, Y", getlastmod()); ?></p>
<script type="text/javascript">
	document.getElementById("loginButton").disabled = false;
	document.getElementById("signupButton").disabled = false;
	document.getElementById("createButton").disabled = false;
	document.getElementById("scoreButton").disabled = false;
</script>

<?php }  ?>





<script src="scripts/functions.js"></script>
<script src="scripts/jquery-1.9.1-min.js"></script>

</body>
</html> 