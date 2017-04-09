<?PHP
	require('../cgi-bin/connect.inc');
	$hash = $_GET['i'];
	$user_id = $_GET['u'];

	$qry = mysql_query("SELECT instance_id, start_date, timezone, DATE(now() + INTERVAL (6 + timezone) HOUR) as today, HOUR(now() + INTERVAL (6 + timezone) HOUR) as hour, DAYOFWEEK(now() + INTERVAL (6 + timezone) HOUR) as dayofweek, name, include_weekends FROM commons_instances WHERE hash='$hash'");
	while ($row = mysql_fetch_array($qry)) {
		$instance_id=$row['instance_id'];
		$start_date = $row['start_date'];
		$timezone = $row['timezone'];
		$today = $row['today'];
		$hour = $row['hour'];
		$dayofweek = $row['dayofweek'];
		$name = $row['name'];
		$include_weekends = $row['include_weekends'];
	}

	$qry = mysql_query("SELECT COUNT(*) FROM commons_users WHERE instance_id=$instance_id");
	while ($row = mysql_fetch_array($qry)) {
		$user_count = $row[0];
		$capacity = 25*$row[0];
	}

	$qry = mysql_query("SELECT student_id, firstname, lastname, email FROM commons_users WHERE user_id=$user_id");
	while ($row = mysql_fetch_array($qry)) {
		$student_id = $row['student_id'];
		$firstname = $row['firstname'];
		$lastname = $row['lastname'];
		$email = $row['email'];
	}
?>

<html>
<head>

 <style type="text/css">
        table.nonmain {border-collapse:collapse;
                        background:#EFF4FB url(http://www.roscripts.com/images/teaser.gif) repeat-x;
                        font:0.8em/145% 'Trebuchet MS',helvetica,arial,verdana;
                        color: #333;
                        }

        table.nonmain {border:1px solid black;}
        table.nonmain td, table.nonmain th {border:1px solid black; padding: 2px;}

        table.nonmain tbody tr{ background:#fafafa}

	#graph1 {width: 410px; float: left;}
	#graph2 {width: 410px; float: left; margin-left: 20px;}
 </style>
</head>

<body>
<h2>Admin page for <?PHP echo $name ?></h2>

<h3>Details for <?PHP echo "$firstname $lastname"; ?></h3>
<p>[student id = <?PHP echo $student_id ?> | email = <?PHP echo $email ?>]</p>
<table class="nonmain">
<tr>
 <th>Commons Day</th>
 <th>Date</th>
 <th>Cows</th>
 <th>Health</th>
 <th>Production</th>
 <th>Cash on Hand</th>
 <th>Set Grazing Order</th>
 <th>Logged In</th>
</tr>
<?PHP 
	$qry = mysql_query("SELECT DATEDIFF(ch.ts, '$start_date') as commons_day, ch.ts, herd_size, avg_health, avg_production, cash,self_set, cll.login_id IS NOT NULL as logged_in FROM commons_herds ch JOIN commons_grazing_orders cgo ON (ch.user_id=cgo.user_id AND cgo.ts = ch.ts) JOIN commons_users_cash_summary cucs ON (ch.user_id=cucs.user_id AND cucs.ts=ch.ts - INTERVAL 1 DAY) LEFT JOIN commons_login_log cll ON (cll.user_id=ch.user_id AND DATE(cll.ts + INTERVAL (6 + '$timezone') HOUR) = ch.ts - INTERVAL 1 DAY) WHERE ch.user_id=$user_id GROUP BY 1");

	while ($row = mysql_fetch_array($qry)) {
		echo <<<EOT
<tr>
 <td>$row[commons_day]</td>
 <td>$row[ts]</td>
 <td>$row[herd_size]</td>
 <td>$row[avg_health]</td>
 <td>$row[avg_production]</td>
 <td>$row[cash]</td>
 <td>$row[self_set]</td>
 <td>$row[logged_in]</td>
</tr>
EOT;
	}
?>

</table>


</body>
</html>
