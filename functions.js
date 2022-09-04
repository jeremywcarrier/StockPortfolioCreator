		if (!String.prototype.trim) {
		String.prototype.trim = function () {  
			return this.replace(/^\s+|\s+$/g,'');  
		};  
	}
	if (!Array.prototype.indexOf) { 
		Array.prototype.indexOf = function(obj, start) {
			 for (var i = (start || 0), j = this.length; i < j; i++) {
				 if (this[i] === obj) { return i; }
			 }
			 return -1;
		}
	}
	
	function maxlen(field, max) {
		field.value = field.value.trim();
		if (field.value.length > max) {				
			field.value = field.value.substring(0, max);
		}
	}
	function formatAmount(field) {
		maxlen(field, 10);
		if (field.value.contains(".")) {
			var index = field.value.indexOf(".");
			field.value = field.value.substring(0, (index + 3));
			field.value = field.value.substring(0, index + 1) + field.value.substring(index + 1).replace(".","");
			if (index == 0) field.value = field.value.replace(".", "");
		}
		field.value = field.value.replace(/[^\d.]/g, '');
	}
					
	
	function validateForm(form) {
		var illegalChars = /[\(\)\<\>\,\;\:\\\/\"\[\]]/; 
		
		if (form.attributes["id"].value === "loginForm") {
			accountString = form.loginacct.value = form.loginacct.value.trim();
			passwordString = form.loginpw.value = form.loginpw.value.trim();
			if (accountString.length < 2 || accountString.length > 50) {
				return false;
			}
			else if (accountString.match(illegalChars)) {
				return false;
			}
			// if (passwordString.length < 8 || passwordString.length > 50) {
				// return false;
			// }
			// else if (passwordString.match(illegalChars)) {
				// return false;
			// }
			document.getElementById("loginButton").disabled = true;
		}
		else if (form.attributes["id"].value === "signupForm")  {
			accountString = form.signupacct.value = form.signupacct.value.trim();
			passwordString =  form.signuppw.value = form.signuppw.value.trim();
			password2String = form.signuppw2.value = form.signuppw2.value.trim();
			form.signupamount.value = form.signupamount.value.trim();
			if (form.signupamount.value.indexOf(".") == (form.signupamount.value.length - 1)) form.signupamount.value = form.signupamount.value + "0";
			amountString = form.signupamount.value;
			
			if (accountString.length < 2 || accountString.length > 50) {
				document.getElementById('warning').innerHTML = 'Invalid username. Length must be between 2 - 50.' 
				return false;
			}
			else if (accountString.match(illegalChars)) {
				document.getElementById('warning').innerHTML = 'Invalid username. Username cannot contain illegal characters.' 
				return false;
			}
			if (passwordString.length < 8 || passwordString.length > 50) {
				document.getElementById('warning').innerHTML = 'Invalid password. Length must be between 8 - 50.' 
				return false;
			}
			else if (passwordString.match(illegalChars)) {
				document.getElementById('warning').innerHTML = 'Invalid password. Password cannot contain illegal characters.' 
				return false;
			}
			else if (passwordString != password2String) {
				document.getElementById('warning').innerHTML = 'Confirmed paassword does not match. Cannot copy + paste passsword.';
				return false;
			}
			
			if (amountString.length < 1) {
				document.getElementById('warning').innerHTML = 'Invalid amount - missing.' 
				return false;
			}
			else { 
				var indexSlice = 0;
				for (i = 0; i < amountString.length; i++) {
					if (amountString.charAt(i) == '0') indexSlice = i;
					else break;
				}
				if (indexSlice > 0) { 
					amountString = form.signupamount.value = amountString.substring(indexSlice + 1, amountString.length);
					if (amountString.charAt(0) == '.') amountString = form.signupamount.value = '0' + amountString;
				}
				var amountValue = parseFloat(amountString);
				if (amountValue < 1000 || amountValue > 1000000) {
					document.getElementById('warning').innerHTML = 'Invalid starting amount. Must be between $1000 - $1000000.' 
					return false;
				}
			}			
			document.getElementById("signupButton").disabled = true;
		}

		return true;
	}
	
	function showSignupForm() {
		document.getElementById('scoreForm').style.display = 'none';
		var signupForm = document.getElementById('signupForm'); 
		if (signupForm.style.display == 'none') {
			signupForm.style.display = 'block';
		}
		else {
			signupForm.style.display = 'none';
		}
		//document.getElementById('signupForm').style.display = '';
	}
	function showScoreForm() {
		document.getElementById('signupForm').style.display = 'none';
		var scoreForm = document.getElementById('scoreForm'); 
		if (scoreForm.style.display == 'none') {
			scoreForm.style.display = 'block';
		}
		else {
			scoreForm.style.display = 'none';
		}
		//document.getElementById('scoreForm').style.display = '';
	}
	
	
	function formatTicker(field) {
		maxlen(field, 10);
		field.value = field.value.replace(/[^0-9a-zA-Z.-]/g,"");
	}
	
	function queryTicker() {
		document.getElementById("searchticker").value = document.getElementById("searchticker").value.trim().toUpperCase();
		var searchData = $("#searchticker").serialize(); //html encoding
		var searchTicker = document.getElementById("searchticker").value;  //
		$.ajax({
			type: "POST",
			url: "apiquery.php",
			data: searchData, //"ticker="+searchTicker,
			dataType: "json", //"text",
			success: queryTickerCallback,
			beforeSend: function() {
                document.getElementById("searchticker").disabled = true;
				document.getElementById("tickerButton").disabled = true;
            },
            complete: function() {
                document.getElementById("searchticker").disabled = false;
				document.getElementById("tickerButton").disabled = false;
            },
			error: function (request, status, error) {
				document.getElementById("searchticker").disabled = false;
				document.getElementById("tickerButton").disabled = false;
				document.getElementById("tickerresult").innerHTML = "Error. Ticker not able to be searched.";
				//alert(request.responseText);
			}
		});
	}
	 
	function queryTickerCallback(data) {
		document.getElementById("tickerresult").innerHTML = '';
		if (!((String(data).trim()).indexOf("error") == -1)) document.getElementById("tickerresult").innerHTML = "Error. Ticker not able to be searched.";
		else if (data != null && (String(data).trim()).indexOf("failed") == -1) {
			var strResult = "<table class='tableSpaced'>";
			$.each(data, function(index, data) {
				var symbolOnly = data.Symbol.replace(".TO","").trim();
				var exchange = data.StockExchange;
				if (exchange.toUpperCase().indexOf("NASDAQ") != -1) exchange = "NASDAQ";
				else if (exchange.toUpperCase().indexOf("TORONTO") != -1) exchange = "TSX";
				var strBuy = '<form action="portfolio.php" method="post" id="buyTickerForm' + index + '" style="margin:0"><input size="10" type="text" name="buyticker' + index + '" id="buyticker' + index + '" maxlength="10" onkeyup="formatBuyShares(this, ' + index + ')" onkeydown="if (event.keyCode == 13) {buyTicker(' + index + ');return false;}" /><input type="button" class="submitButton" value="Buy Shares" id="buyTickerButton' + index + '" name="buyTickerButton' + index + '" onclick="buyTicker(' + index + ')" disabled="disabled" /><input id="buyTickerValue' + index + '" name="buyTickerValue" value="' + data.Symbol + '|' + data.Price + '|' + exchange + '" type="hidden" /></form>'
				strResult += "<tr><td><span class='spanOpacity'>Symbol:</span> " + symbolOnly + "</td><td><span class='spanOpacity'>Stock Exchange:</span> " + exchange + "</td><td><span class='spanOpacity'>Price (close or last traded):</span> $" + data.Price + "</td><td>" + strBuy + "</td></tr>";
			});
			strResult += "</table>";
			document.getElementById("tickerresult").innerHTML = strResult;
		}
		else document.getElementById("tickerresult").innerHTML = "No ticker found.";
	}
	
	function formatBuyShares(field, index) {
		maxlen(field, 10);
		field.value = field.value.replace(/[^\d]/g, '');
		var canBuyDisabled = true;
		if (field.value.length > 0) {
			var shares = field.value;
			var stockPrice = parseFloat(document.getElementById("buyTickerValue" + index).value.split("|")[1]);
			if (shares > 0 && stockPrice > 0 && userAmount >= (shares * stockPrice)) canBuyDisabled = false; 
		}
		document.getElementById("buyTickerButton" + index).disabled = canBuyDisabled;
	}
	
	function buyTicker(index) {
		if (!document.getElementById("buyTickerButton" + index).disabled) {
			var shares =  document.getElementById("buyticker" + index).value;
			var ticker = document.getElementById("buyTickerValue" + index).value.split("|")[0];
			var stockPrice = parseFloat(document.getElementById("buyTickerValue" + index).value.split("|")[1]);
			if (shares > 0 && userAmount >= (shares * stockPrice)) {
				document.getElementById("buyTickerValue" + index).value = document.getElementById("buyTickerValue" + index).value + "|" + shares;
				document.getElementById("buyTickerForm" + index).submit();
			}
		}
	}
	
	function formatSellShares(field, index) {
		maxlen(field, 10);
		field.value = field.value.replace(/[^\d]/g, '');
		var canBuyDisabled = true;
		if (field.value.length > 0) {
			var shares = field.value;
			var totalshares =  parseInt(document.getElementById("sellTickerValue" + index).value.split("|")[3]);
			if (shares > 0 && shares <= totalshares) canBuyDisabled = false; 
		}
		document.getElementById("sellTickerButton" + index).disabled = canBuyDisabled;
	}
	
	function sellTicker(index) {
		if (!document.getElementById("sellTickerButton" + index).disabled) {
			var shares =  document.getElementById("sellticker" + index).value;
			var totalshares =  parseInt(document.getElementById("sellTickerValue" + index).value.split("|")[3]);
			if (shares > 0 && shares <= totalshares && !document.getElementById("sellTickerButton" + index).disabled) {
				document.getElementById("sellTickerValue" + index).value = document.getElementById("sellTickerValue" + index).value + "|" + shares;
				document.getElementById("sellTickerButton" + index).disabled = true;
				document.getElementById("sellTickerForm" + index).submit();
			}
		}
	}