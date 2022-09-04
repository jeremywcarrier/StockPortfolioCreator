<?php include("includes/YahooFinanceAPI.php"); ?>

<?php 

if (isset($_POST["ticker"]) || isset($_POST["searchticker"])) {
	$ticker = "";
	if (isset($_POST["ticker"])) $ticker = $_POST["ticker"];
	else if (isset($_POST["searchticker"])) $ticker = $_POST["searchticker"];
	$results = array();
	$y = api(array($ticker, $ticker . ".TO"));
	if ($y[0] == "error") return die(json_encode("error"));
	for ($i = 0; $i < count($y); $i++) {
		if (strlen($y[$i]['ErrorIndicationreturnedforsymbolchangedinvalid']) == 0 && 
		(strpos(strtoupper($y[$i]['StockExchange']),'NASDAQ') !== false || strpos(strtoupper($y[$i]['StockExchange']),'TORONTO') !== false || strpos(strtoupper($y[$i]['StockExchange']),'NYSE') !== false) ) { 
			$jsondata = array();
			$Symbol = "";
			if (strlen($y[$i]['Symbol']) > 0) $Symbol = $y[$i]['Symbol'];
			$jsondata['Symbol'] = $Symbol;
			$StockExchange = "";
			if (strlen($y[$i]['StockExchange']) > 0) $StockExchange = $y[$i]['StockExchange'];
			$jsondata['StockExchange'] = $StockExchange;
			$Price = "";
			if (strlen($y[$i]['LastTradePriceOnly']) > 0) $Price = $y[$i]['LastTradePriceOnly'];
			$jsondata['Price'] = $Price;
			
			//$resultTicker = array($y[$i]['Symbol'], $y[$i]['StockExchange'], $y[$i]['LastTradePriceOnly']);
		
			//if (strlen($y[$i]['Symbol'] > 0)) $resultTicker[] = "TEST1 " . $y[$i]['Symbol']; 
			//if (strlen($y[$i]['StockExchange'] > 0)) $resultTicker[] = "TEST2 " . $y[$i]['StockExchange']; 
			//if (strlen($y[$i]['LastTradePriceOnly'] > 0)) $resultTicker[] = "TEST3 " . $y[$i]['LastTradePriceOnly']; 
			$results[] = $jsondata;
		}
	}

	//die(json_encode( $y[0]['Name']);

	// die(json_encode($y));
	if (count($results) == 0) $results = "failed";
	die(json_encode($results));

}
else die(json_encode("failed"));
 
?>