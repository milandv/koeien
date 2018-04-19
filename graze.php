<?PHP
        require_once('auth.php');
	require('cgi-bin/connect.inc');

	$instance_id = $_SESSION['SESS_INSTANCE_ID'];
	$qry = mysql_query("SELECT 1 FROM commons_instances WHERE TIME(now()+ INTERVAL (6 + timezone) HOUR)<'09:15' AND instance_id=$instance_id");
	if (mysql_num_rows($qry) > 0) {
		header("Location: outtopasture.php");
	}


	$qry = mysql_query("SELECT name, start_date, DATE(now() + INTERVAL(6 + timezone) HOUR) as today, DATEDIFF(now() + INTERVAL (6 + timezone) HOUR, start_date)+1 as day, timezone, default_graze FROM commons_instances WHERE instance_id=$instance_id");
	while ($instance_row = mysql_fetch_array($qry)) {
		$commons_name = $instance_row['name'];
		$commons_start_date = $instance_row['start_date'];
		$today = $instance_row['today'];
		$commons_day = $instance_row['day'];
		$timezone = $instance_row['timezone'];
		$default_graze = $instance_row['default_graze'];
	}

        // grab global health parameter
        $qry = mysql_query("SELECT global_health FROM commons_global_stats WHERE instance_id=$instance_id AND ts<='$today' ORDER BY ts DESC LIMIT 1");
        while ($row = mysql_fetch_array($qry)) { $global_health = $row['global_health']; }

	$grass_level = ceil($global_health/10);
	if ($grass_level>9) {$grass_level=9;}
	if ($grass_level<1) {$grass_level=1;}

	$user_id = $_SESSION['SESS_ID'];

	// cash on hand
	$qry = mysql_query("SELECT cash FROM commons_users_cash_summary WHERE user_id=$user_id ORDER BY ts DESC LIMIT 1");
	while ($cash_row = mysql_fetch_array($qry)) {
		$max_cows = floor($cash_row['cash']/1000);
		$cash_on_hand = number_format($cash_row['cash']);
	}

        // hash for taxes
        $qry = mysql_query("SELECT hash FROM commons_users WHERE user_id=$user_id LIMIT 1");
        while ($hash_row = mysql_fetch_array($qry)) {
                $hash = $hash_row[0];
        }

	// herd overview
	$qry = mysql_query("SELECT herd_size, avg_health FROM commons_herds WHERE user_id=$user_id ORDER BY ts DESC LIMIT 1");
	while ($herd_row = mysql_fetch_array($qry)) {
		$herd_size = $herd_row['herd_size'];
		$herd_avg_health = number_format($herd_row['avg_health']);
		$max_graze = $max_cows + $herd_size;
	}

	//check for today's submission
	$qry = mysql_query("SELECT * FROM commons_grazing_orders WHERE user_id=$user_id AND ts='$today'");
	$order_check = mysql_num_rows($qry);
	if ($order_check>0) { $order_submitted=1; }
	if ($_POST["submit"]) {
		$order = $_POST["pasture"];
		if ($order>=0 && $order<=$max_graze) {
			if ($order_check>0) {
				$qry = mysql_query("UPDATE commons_grazing_orders SET cows=$order WHERE user_id=$user_id AND ts = '$today'");
				$order_updated = 1;
			} else {
				$qry = mysql_query("INSERT INTO commons_grazing_orders (user_id, ts, cows, self_set) VALUES ($user_id, '$today', $order, 1)");
				$order_submitted = 1;
			}
		}
	}


	$qry = mysql_query("SELECT cows FROM commons_grazing_orders WHERE user_id=$user_id AND ts = '$today' LIMIT 1");
	$grazing_order_check = mysql_num_rows($qry);
	if ($grazing_order_check>0) {
		while ($grazing_row = mysql_fetch_array($qry)) {
			$last_order = $grazing_row['cows'];
		}
		if ($last_order == '' || $last_order == 0) {
			$last_order = $herd_size;
		}
	} else {
		$last_order = 0;
		$last_order = $herd_size;
	}
	if (!$default_graze) {
		$last_order = 0;
	}

	if ($_POST["start_reminders"]) {
		$qry = mysql_query("INSERT INTO commons_users_alerts (user_id) VALUES ($user_id)");
	}
	if ($_POST["stop_reminders"]) {
		$qry = mysql_query("DELETE FROM commons_users_alerts WHERE user_id=$user_id");
	}

	$qry = mysql_query("SELECT 1 FROM commons_users_alerts WHERE user_id=$user_id");
	$alert_check = mysql_num_rows($qry);

?>

<html>
<head>
 <title><?PHP echo $commons_name ?></title>
 <style>
	@import url(https://fonts.googleapis.com/css?family=Bowlby+One+SC);
	@import url(https://fonts.googleapis.com/css?family=Inika:400,700);
 </style>

 <link rel="stylesheet" type="text/css" href="css/style_graze.css">

 <script src="js/jquery-1.7.1.min.js"></script>

 <script type="text/javascript">
   $(document).ready(function(){
	$(".button").click(function() {
	    var $button = $(this);
	    var oldValue = $("#pasture").val();
	
	    if ($button.text() == "+") {
		  if (oldValue < <?PHP echo $max_graze ?>) {
		     var newVal = parseFloat(oldValue) + 1;
		  } else {
		     var newVal = oldValue;
		  }
		} else {
		  // Don't allow decrementing below zero
		  if (oldValue >= 1) {
		      var newVal = parseFloat(oldValue) - 1;
		  } else {
		      var newVal=oldValue;
		  }
		}
		$("#pasture").val(newVal);
		if (newVal != oldValue) {
			if (newVal < <?PHP echo $herd_size ?>) {
				var diff = <?PHP echo $herd_size ?> - newVal;
				if (diff==1) {
					$("#transaction").html('You will <strong>sell '+diff+' cow</strong> at midnight');
				} else {
					$("#transaction").html('You will <strong>sell '+diff+' cows</strong> at midnight');
				}
			} else if (newVal > <?PHP echo $herd_size ?>) {
				var diff = newVal - <?PHP echo $herd_size ?>;
				if (diff==1) {
					$("#transaction").html('You will <strong>buy '+diff+' cow</strong> at midnight');
				} else {
					$("#transaction").html('You will <strong>buy '+diff+' cows</strong> at midnight');
				}
			} else {
				$("#transaction").html('Currently: no change from yesterday.');
			}
		}
	});
	$("#order_updated").fadeOut(5000);
   });
 </script>


</head>
<body>

<div id="wrapper">

<div id="header">
	<h1><?PHP echo $commons_name ?> &nbsp;&nbsp; <img src="img/grass<?PHP echo $grass_level ?>.svg"></h1>
</div>

<?PHP include 'tweets.php'; ?>

<div id="top">
<p style="text-align:justify">Welcome farmer <?PHP echo "$_SESSION[SESS_LAST_NAME]"; ?>. Today is <strong>Day <?PHP echo $commons_day ?></strong>. Please set the number of cows you would like to send to pasture below. If you send more than you currently own, you will automatically buy as many cows as you can afford to make up the difference. If you send fewer than you currently own, you will automatically sell off the excess. You must send at least one cow. Happy farming, and good luck.</p>

<form action="graze.php" method="POST">
<?PHP if ($alert_check) { ?>
<p>You are currently <span style="color: green">signed up</span> for email reminders.
 <input type="submit" name="stop_reminders" value="Stop Reminders">
</p>
<?PHP } else { ?>
<p>You are currently <span style="color: red">not signed up</span> for email reminders. 
<form action="graze.php" method="POST">
 <input type="submit" name="start_reminders" value="Start Reminders">
</p>
<?PHP } ?>
</form>
</div>


<div id="narrow">

 <div class="box">
	<h2>Pasture</h2>
	<form action="graze.php" method="POST">
	<table>
	<tr>
		<td><img src="img/cowhead.svg" /></td>
		<td style="text-align:center">
		Yesterday you grazed <?PHP echo $herd_size ?> cows<br />
		How many would you like to graze today: <br />
		<input id="pasture" type="text" name="pasture" value="<?PHP echo $last_order ?>"/> 
		<div class="inc button">+</div><div class="dec button">-</div>
		<input id="submit" name="submit" type="submit" Value="Set number"/></td>
	</tr>
	</table>
<?PHP if ($order_updated) { ?>
	<p id="order_updated">Grazing order updated!</p>
<?PHP } ?>
<?PHP if ($order_submitted) { ?>
	<div id="order_submitted">Grazing order for tomorrow is set at <?PHP echo $last_order ?> cows.</div>
<?PHP } ?>
	<p style="text-align:center" id="transaction">
<?PHP 
	if ($herd_size == $last_order) {
		echo "Currently: no change from yesterday.";
	} elseif ($herd_size < $last_order) {
		$diff = $last_order - $herd_size;
		if ($diff == 1) {
			echo "You will <strong> buy $diff cow</strong> at midnight";
		} else {
			echo "You will <strong> buy $diff cows</strong> at midnight";
		}
	} else {
		$diff = $herd_size - $last_order;
		if ($diff == 1) {
			echo "You will <strong> sell $diff cow</strong> at midnight";
		} else {
			echo "You will <strong> sell $diff cows</strong> at midnight";
		}
	}
?>
</p>
 </div>


 <div class="box">
	<h2>Farm</h2>
	<table>
	<tr>
		<td><img src="img/money.svg" /></td>
		<td>&#36; <?PHP echo $cash_on_hand ?></td>
	</tr>
	<tr>
		<td><img src="img/cowmain.svg" /></td>
		<td><?PHP echo $herd_size ?> cows</td>
	</tr>
	<tr>
		<td><img src="img/heart.svg" /></td>
		<td><?PHP echo $herd_avg_health ?>% health</td>
	</tr>

	</table>
 </div>


</div>

<div id="wide">

 <div class="box">
	<h2>Ledger</h2>

<?PHP 
	if ($last_order != $herd_size) {
		if ($last_order>$herd_size) {
			$buying_amount = $last_order - $herd_size;
			echo <<<EOT
			<tr>
				<td><img src="img/cowtag.svg" /></td>
				<td>+$buying_amount cows</td>
				<td colspan="2"><i>(pending for tomorrow)</i></td>
			</tr>
EOT;
		} else {
			$selling_amount = $herd_size - $last_order;
			echo <<<EOT
			<tr>
				<td><img src="img/cowsold.svg" /></td>
				<td>-$selling_amount cows</td>
				<td colspan="2"><i>(pending for tomorrow)</i></td>
			</tr>
EOT;

		}
	}
	
	if ($commons_day == 1 && $last_order == $herd_size) {
		echo "<p>No transactions have been recorded yet.</p>";
	} else {
		echo "<table>";
		for ($i=0; $i<3; $i++) {
			// production
			$qry = mysql_query("SELECT ts, DATEDIFF(ts,'$commons_start_date') as day, liters, amt FROM commons_users_cash_production WHERE user_id=$user_id ORDER BY ts DESC LIMIT $i,1");
			if (mysql_num_rows($qry)>0) {
				while ($row=mysql_fetch_array($qry)) {
					$ts=$row['ts'];
					$day = $row['day'];
					$liters = number_format($row['liters']);
					$amt = number_format($row['amt'],2);
				}
				echo <<<EOT
		<tr>
			<td><img src="img/bottle.svg" /></td>
			<td>$liters L</td>
			<td>&#36; $amt</td>
			<td>day $day</td>
		</tr>
EOT;

				// purchases
				$qry = mysql_query("SELECT cows, amt FROM commons_users_cash_purchases WHERE user_id=$user_id AND ts = '$ts' - INTERVAL 1 DAY");
				if (mysql_num_rows($qry)>0) {
					while ($row=mysql_fetch_array($qry)) {
						$bought_cows = $row['cows'];
						$bought_amt = number_format($row['amt'],2);
					}
					echo <<<EOT
			<tr>
				<td><img src="img/cowtag.svg" /></td>
				<td>+$bought_cows cows</td>
				<td>&#36; -$bought_amt </td>
				<td>day $day</td>
			</tr>
EOT;
				}
				// sales 
				$qry = mysql_query("SELECT cows, amt FROM commons_users_cash_sales WHERE user_id=$user_id AND ts = '$ts' - INTERVAL 1 DAY");
				if (mysql_num_rows($qry)>0) {
					while ($row=mysql_fetch_array($qry)) {
						$sold_cows = $row['cows'];
						$sold_amt = number_format($row['amt'],2);
					}
					echo <<<EOT
			<tr>
				<td><img src="img/cowsold.svg" /></td>
				<td>-$sold_cows cows</td>
				<td>&#36; $sold_amt</td>
				<td>day $day</td>
			</tr>
EOT;
				}

			}

			// sales
		}
		echo "</table>";
	}
?>

 </div>

</div>




</div>
