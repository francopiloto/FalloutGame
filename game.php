
<?php
	session_start();
	
	$words = 
	[
	    "dream","back","extra-large","interrupt","aspiring","complex","insidious","yard","disillusioned",
		"hushed","rule","plan","first","crooked","thank","ugliest","ball","owe","pet","spill","stocking",
		"fear","luxuriant","chivalrous","loss","tawdry","dare","expert","scintillating","shade","month",
		"deep","supreme","theory","flat","bikes","carpenter","irate","balance","smooth","quicksand",
		"crib","rabbits","racial","measure","hurried","pull","famous","sip","week","annoyed",
		"recondite","wren","bury","rampant","tough","abnormal","recess","bubble","fruit","oranges","kaput",
		"receive","clip","punishment","fuel","can","horse","disappear","daughter","sloppy","route","meaty",
		"flimsy","reach","vast","normal","store","competition","polish","friction","moor","religion","cap",
		"tame","hydrant","same","boorish","inconclusive","announce","unwritten","babies","cow","science",
		"alcoholic","mix","moon","responsible","lamentable"
	];
	
	$symbols = ['!','@','#','$','&','*','(',')','-','=','+','§','[','{','ª','}',']','º',';',':','?','/','|','£','¢'];
	
	$numColumns = 2;
	$numLines = 16;
	$columnWidth = 10;
	$maxAttempts = 4;	
	
	$buffer = [];
	$log = [];
	
	
	if ($_SERVER["REQUEST_METHOD"] == "GET") 
	{
		$log = [];
		$_SESSION["log"] = serialize($log);
		
		$_SESSION["attempts"] = $maxAttempts;
		$_SESSION["password"] = "";
		
		createPage(true, $maxAttempts);
	}
	else if ($_SERVER["REQUEST_METHOD"] == "POST")
	{		
		$attempts = $_SESSION["attempts"] - 1;	
		
		if ($attempts <= 0)
		{
			createResultsPage(false);
			return;
		}
		
		if (array_key_exists("log", $_SESSION)) {
			$log = unserialize($_SESSION["log"]);
		}
		
		$_SESSION["attempts"] = $attempts;
		
		$password = $_SESSION["password"];
		$numCorrectLetters = compare($password, $_POST["selected"]);
		
		if ($numCorrectLetters == strlen($password)) {
			createResultsPage(true);
		}
		else 
		{
			array_push($log, "<p>", $_POST["selected"], "</p>");
			array_push($log, "<p> denied ", $numCorrectLetters, "/", strlen($password) ,"</p>");
			
			$_SESSION["log"] = serialize($log);
		
			createPage(false, $attempts);
		}		
	}	
	
	function createPage($reset, $attempts)
	{
		global $buffer;
		global $log;
		
		echo "<html>";
		echo "<head>";
		echo "<link type='text/css' rel='stylesheet' href='game.css'>";
		echo "</head>";
		echo "<body>";
				
		if ($reset || !array_key_exists("buffer", $_SESSION))
		{			
			init();
			$_SESSION["buffer"] = serialize($buffer);
		}
		else {
			$buffer = unserialize($_SESSION["buffer"]);
		}
		
		echo implode($buffer);

		echo "<div class='column'>";
		
		echo "<div>";
		
		for ($i = 0; $i < $attempts; $i++) {
			echo "<span class='square'></span>";
		}
		
		echo "</div>";
		
		echo implode($log);
		echo "<p id='output'></p>";
		echo "</div>";
		
		echo "<form method='POST'><input type='hidden' id='selected' name='selected'/></form>";
		
		echo "</body>";
		echo "</html>";
	}
	
	function createResultsPage($winner)
	{
		echo "<html>";
		echo "<head>";
		echo "<link type='text/css' rel='stylesheet' href='game.css'>";
		echo "</head>";
		echo "<body>";
		echo "<div class='results'>";
		
		if ($winner) {
			echo "<h1>You Win!!!</h1>";
		}
		else {
			echo "<h1 style='color:red;'>You Lose!!!</h1>";
		}
		
		echo "<a href='game.php'>Play again</a>";
		
		echo "</div>";
		echo "</body>";
		echo "</html>";
	}
					
	function init()
	{
		global $words;
		global $symbols;						
		global $numColumns;
		global $numLines;
		global $columnWidth;
		
		global $buffer;
		
		$numOptionsPerColumn = 7;
		$totalCharsPerColumn = $columnWidth * $numLines;
		
		$options = [];				
		$maxWordIndex = count($words) - 1;

		$buffer = [];	
		
		for ($i = 0; $i < $numColumns; $i++)
		{			
			array_push($buffer, "<div class='column'>");
			
			// Select random words to be the options
			$numSymbols = $totalCharsPerColumn;
			
			for ($j = 0; $j < $numOptionsPerColumn; $j++)
			{
				$index = rand(0, $maxWordIndex);
				$selected = $words[$index];
				
				$options[$i][$j] = [$selected, 1, 1];
				$numSymbols -= strlen($selected) + 2;
						
				array_splice($words, $index, 1);
				array_push($words, $selected);
				
				$maxWordIndex--;
			}			
							
			// compute the number of symbols to insert before and after each word
			for ($j = 0; $j < $numSymbols; $j++) {
				$options[$i][rand(0,$numOptionsPerColumn-1)][rand(1,2)]++;
			}

			// create all chars to be printed in this column
			$numSymbols = 0;
			
			for ($j = 0; $j < $numOptionsPerColumn; $j++)
			{	
				// inserting symbols before a word				
				insertSymbols($numSymbols, $options[$i][$j][1]);			
				
				// inserting the word
				$word = $options[$i][$j][0];
				$max = $numSymbols + strlen($word);
				$pos = 0;
								
				array_push($buffer, "<span class='option'>");
				
				while ($numSymbols < $max)
				{
					array_push($buffer, $word[$pos++]);
					$numSymbols++;
					
					if ($numSymbols % $columnWidth == 0) {
						array_push($buffer, "<br>");
					}
				}				
				
				array_push($buffer, "</span>");
				
				// inserting symbols after a word				
				insertSymbols($numSymbols, $options[$i][$j][2]);				
			}			
			
			array_push($buffer, "</div>");
		}

		// select the correct password
		$_SESSION["password"] = $options[rand(0,$numColumns-1)][rand(0,$numOptionsPerColumn-1)][0];
		
		//echo $_SESSION["password"];
	}
	
	function insertSymbols(&$pos, $length)
	{
		global $symbols;
		global $columnWidth;
		global $buffer;
		
		$maxSymbolIndex = count($symbols) - 1;
		$length += $pos;
		
		while ($pos < $length) 
		{
			array_push($buffer, $symbols[rand(0,$maxSymbolIndex)]);
			$pos++;
			
			if ($pos % $columnWidth == 0) {
				array_push($buffer, "<br>");
			}
		}
	}
	
	function compare($password, $guess)
	{
		if ($guess == $password) {
			strlen($password);
		}
		
		$max = min(strlen($password), strlen($guess));
		$numCorrectLetters = 0;
		
		for ($i = 0; $i < $max; $i++)
		{
			if($guess[$i] == $password[$i]) {
				$numCorrectLetters++;
			}
		}
		
		return $numCorrectLetters;
	}
?>

<script  src="https://code.jquery.com/jquery-3.3.1.min.js"
         integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
		 crossorigin="anonymous">
</script>

  
<script type='text/javascript'>
	$().ready(function()
	{
		$('.option')
			.on('mouseover', function() 
			{	
				$(this).addClass('hover');
				$('#output').html($(this).html().replace('<br>',''));
			})
			.on('mouseleave', function() 
			{	
				$(this).removeClass('hover');
				$('#output').html('');
			})
			.on('click', function() 
			{
				$('#selected').val($('#output').html());
				$('form').submit();
			});
	});
</script>



