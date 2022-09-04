<?php
//http://developer.yahoo.com/yql/
//class YahooFinanceAPI
//{
    //public 
	//TSX/NYSE/NASDAQ
	//$api_url = 'http://query.yahooapis.com/v1/public/yql';
// $y = new YahooFinanceAPI;
// $y->api(array('SLV','GLD'));
    /**
     * @param array $tickers The array of ticker symbols
     * @param array|bool $fields Array of fields to get from the returned XML
     * document, or if true use default fields, or if false return XML
     *
     * @return array|string The array of data or the XML document
     */
   // public 
	function api ($tickers,$fields=true) {
		//http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20yahoo.finance.quotes%20where%20symbol%20in%20%28%22GOOG%22%29&env=store://datatables.org/alltableswithkeys&debug=true&diagnostics=true
        // set url
        $url = 'http://query.yahooapis.com/v1/public/yql';
        $url .= '?q=select%20*%20from%20yahoo.finance.quotes%20where%20symbol%20in%20%28%22'.implode(',',$tickers).'%22%29&env=store://datatables.org/alltableswithkeys&debug=true&diagnostics=true';

        // set fields
        if ($fields===true || empty($fields)) {
            $fields = array(
                    'Symbol','Name','StockExchange','Change','ChangeRealtime','Volume','PercentChange',
                    'LastTradeRealtimeWithTime','LastTradeWithTime','LastTradePriceOnly','LastTradeTime',
                    'LastTradeDate', 'ErrorIndicationreturnedforsymbolchangedinvalid', 'OneyrTargetPrice','DaysRange','YearRange','Open','PreviousClose'
                    );
        }
		//Ask, AverageDailyVolume, Bid, AskRealtime, BidRealtime, BookValue, Change_PercentChange, Change, Comission, ChangeRealtime, AfterHoursChangeRealtime, DividendShare, LastTradeDate, TradeDate, EarningsShare, 
		//ErrorIndicationreturnedforsymbolchangedinvalid, EPSEstimateCurrentYear, EPSEstimateNextYear, EPSEstimateNextQuarter, DaysLow, DaysHigh, YearLow, YearHigh, HoldingsGainPercent, AnnualizedGain, HoldingsGain,
		//HoldingsGainPercentRealtime, HoldingsGainRealtime, MoreInfo, OrderBookRealtime, MarketCapitalization, MarketCapRealtime, EPITDA, ChangeFromYearLow, PercentChangeFromYearLow, LastTradeRealtimeWithTime, ChangePercentRealtime,
		//ChangeFromYearHigh, PercebtChangeFromYearHigh, LastTradeWithTime, LastTradePriceOnly, HighLimit, LowLimit, DaysRange, DaysRangeRealtime, FiftydayMovingAverage, TwoHundreddayMovingAverage, ChangeFromTwoHundreddayMovingAverage.
		//PercentChangeFromTwoHundreddayMovingAverage, ChangeFromFiftydayMovingAverage, PercentChangeFromFiftydayMovingAverage, Name, Notes, Open, PreviousClose, PricePaid, ChangeinPercent, PriceSales, PriceBook, ExDividendDate, PERatio,
		//DividendPayDate, PERatioRealtime, PEGRatio, PriceEPSEstimateCurrentYear, PriceEPSEstimateNextYear, Symbol, SharesOwned, ShortRatio, LastTradeTime, TickerTrend, OneyrTargetPrice, Volume, HoldingsValue, HoldingsValueRealtime, YearRange, 
		//DaysValueChange, DaysValueChangeRealtime, StockExchange, DividendYield, PercentChange
		
        // make request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); 

        // parse response
        if (!empty($fields)) {
            $xml = new SimpleXMLElement($resp);
            $data = array();
            $row = array();
            // $time = time();
            if(is_object($xml)){
                foreach($xml->results->quote as $quote){
                    $row = array();
                    foreach ($fields as $field) {
                        $row[$field] = (string) $quote->$field;
                    }
                    $data[] = $row;
                }
				if (count($data) == 0) {
					$data[] = "error";
				}
            }
        } else {
            $data = $resp;
        }

        return $data;
    }
//}


// function curl($url){
    // $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL,$url);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    // return curl_exec($ch);
    // curl_close ($ch);
// }

// $symbol = $_GET["s"];
// $csv = "http://download.finance.yahoo.com/d/quotes.csv?s=" . $symbol . "&f=sl1d1t1c1ohgv&e=.csv";

// $yahoo = curl($csv);

//The split information is in the "Dividend Only" CSV:
//http://ichart.finance.yahoo.com/x?s=IBM&a=00&b=2&c=1962&d=04&e=25&f=2011&g=v&y=0&z=30000
