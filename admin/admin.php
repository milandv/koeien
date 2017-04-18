<?PHP
	require('../cgi-bin/connect.inc');
	$hash = $_GET['i'];

	$qry = mysql_query("SELECT instance_id, start_date, DATE(now() + INTERVAL (6 + timezone) HOUR) as today, HOUR(now() + INTERVAL (6 + timezone) HOUR) as hour, DAYOFWEEK(now() + INTERVAL (6 + timezone) HOUR) as dayofweek, name, include_weekends FROM commons_instances WHERE hash='$hash'");
	while ($row = mysql_fetch_array($qry)) {
		$instance_id=$row['instance_id'];
		$start_date = $row['start_date'];
		$today = $row['today'];
		$hour = $row['hour'];
		$dayofweek = $row['dayofweek'];
		$name = $row['name'];
		$include_weekends = $row['include_weekends'];
	}

	$qry = mysql_query("SELECT 1 FROM commons_instances WHERE hash='$hash' AND HOUR(now() + INTERVAL (6 + timezone) HOUR) BETWEEN 0 AND 8");
	$hour_check = mysql_num_rows($qry);

                //last order_date
                $qry = mysql_query("SELECT max(cgo.ts) FROM commons_users cu JOIN commons_grazing_orders cgo USING(user_id) WHERE instance_id=$instance_id");
                if (mysql_num_rows($qry) > 0) {
                        while ($row = mysql_fetch_array($qry)) {
                                $max_order_date = $row[0];
                        }
			//1 day?? 2 day??
                        $qry2 = mysql_query("SELECT COUNT(*) FROM commons_users cu JOIN commons_grazing_orders cgo USING(user_id) WHERE instance_id=$instance_id AND cgo.ts='$max_order_date'-INTERVAL 1 DAY AND approved=1");
                        while ($row2 = mysql_fetch_array($qry2)) {
                                $user_count = $row2[0];
                                $capacity = 25*$user_count;
                        }
                } else {
                        $qry = mysql_query("SELECT COUNT(*) FROM commons_users WHERE instance_id=$instance_id AND approved=1");
                        while ($row = mysql_fetch_array($qry)) {
                           $user_count = $row[0];
                           $capacity = 25*$row[0];
                        }
                }


	$qry = mysql_query("SELECT global_health FROM commons_global_stats WHERE instance_id=$instance_id AND ts<'$today' ORDER BY ts DESC LIMIT 1");
	while ($row = mysql_fetch_array($qry)) {
		$current_global_health = $row['global_health'];
	}

	$qry = mysql_query("SELECT SUM(cows) as new_total FROM commons_grazing_orders cgo JOIN commons_users cu USING(user_id) WHERE instance_id=$instance_id AND cgo.ts='$today' - INTERVAL 1 DAY");
	while ($row = mysql_fetch_array($qry)) {
		$new_commons_size = $row['new_total'];
	}

	$new_per_capacity = number_format(100*$new_commons_size/$capacity);
	if ($new_per_capacity>100) {
		$adjustment_factor = (100*100 - ($new_per_capacity - 100)*($new_per_capacity-100))/(100*100);
		$suggested_health = $current_global_health * $adjustment_factor;
	} elseif ($new_per_capacity<100) {
		$adjustment_factor = 2-(100*100 - (100-$new_per_capacity)*(100-$new_per_capacity))/(100*100);
		$suggested_health = $current_global_health * $adjustment_factor;
		if ($suggested_health>100) { $suggested_health=100; }
	} else {
		$adjustment_factor = 1;
		$suggested_health = $current_global_health;
	}
	if ($adjustment_factor<0) { $adjustment_factor=0; }

	if ($_POST["submit"]) {
		$new_health = $_POST['new_health'];
		if ($new_health>=0 && $new_health<=100) {
			$check_qry = mysql_query("SELECT * FROM commons_global_stats WHERE ts='$today'");
			$today_check = mysql_num_rows($check_qry);
			if ($today_check>0) {
				$qry = mysql_query("UPDATE commons_global_stats SET global_health='$new_health' WHERE ts='$today'");
			} else {
				$qry = mysql_query("INSERT INTO commons_global_stats (instance_id, ts, commons_size, global_health, self_set) VALUES ($instance_id, '$today', $new_commons_size, '$new_health', 1)");
			}
		}
	}
	
?>

<html>
<head>

 <script language="javascript" type="text/javascript" src="../js/jquery-1.7.1.min.js"></script>
 <script language="javascript" type="text/javascript" src="../js/jquery.flot.js"></script>

<script type="text/javascript">
$(document).ready(function(){

	var cur_hist = [];
<?PHP
	$qry = mysql_query("SELECT 10*(FLOOR(cows/10)), COUNT(*) FROM commons_users cu JOIN commons_grazing_orders cgo USING(user_id) WHERE instance_id=$instance_id AND cgo.ts='$today' - INTERVAL 1 DAY AND approved=1 GROUP BY 1");
	while ($row = mysql_fetch_array($qry)) {
		$a = $row[0];
		$b = $row[1];
		echo "cur_hist.push([$a,$b]);";
	}
?>

	$.plot($("#hist_placeholder"), [ {data:cur_hist, bars: {show:true, barWidth:8}}], {xaxis: {min: 0, ticks: 10}});

	var global_cows = [];
	var global_health = [];

<?PHP
	$qry = mysql_query("SELECT DATEDIFF(ts,'$start_date'), commons_size, global_health FROM commons_global_stats WHERE instance_id=$instance_id");
	while ($row = mysql_fetch_array($qry)) {
		$a = $row[0];
		$b = $row[1];
		$c = $row[2];
		echo "global_cows.push([$a,$b]);";
		echo "global_health.push([$a,$c]);";
	}
?>
	var markings = [
		{ color: '#888', lineWidth: 2, yaxis: {from: <?PHP echo $capacity ?>, to: <?PHP echo $capacity ?>} }
	];

	$.plot($("#global_placeholder"), 
	[ {data: global_cows, label: "total cows", lines: {show: true}, points: {show: true}}, 
	  {data: global_health, label: "global health", yaxis: 2}
	],
	{ yaxes: [ {min: 0}, {min:0, max: 115, alignTicksWithAxis: 1, position: "right"} ],
	legend: { position: "se" },
	grid: {markings: markings}
	});
});
</script>


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
<h3>Health to date</h3>
<table class="nonmain">
<tr>
 <th>Date</th>
 <th>Commons Size</th>
 <th>Capacity</th>
 <th>Global Health</th>
</tr>
<?PHP 
	$qry = mysql_query("SELECT IF(ts = '$today', 'Today', DATE_FORMAT(ts, '%a, %b. %D')) as date, commons_size, global_health FROM commons_global_stats WHERE instance_id=$instance_id");
	while ($row = mysql_fetch_array($qry)) {
		$per_capacity = number_format(100*$row[commons_size]/$capacity, 2);
		echo <<<EOT
<tr>
 <td>$row[date]</td>
 <td>$row[commons_size]</td>
 <td>${per_capacity}%</td>
 <td>$row[global_health]</td>
</tr>
EOT;
	}
?>

</table>

<?PHP if ($hour_check) { ?>

<h3>Tomorrow's global health</h3>
<table>
<tr>
 <td>Current health:</td>
 <td><?PHP echo $current_global_health ?>%</td>
</tr>
<tr>
 <td>Tomorrow's commons size:</td>
 <td><?PHP echo $new_commons_size ?> (<?PHP echo $new_per_capacity ?>% of capacity)</td>
</tr>
<tr>
 <td>Suggested adjustment factor:</td>
 <td><?PHP echo number_format($adjustment_factor,3) ?></td>
</tr>
<tr>
 <td>Suggested new health:</td>
 <td><?PHP echo number_format($suggested_health,2) ?>%</td>
</tr>
</table>

<p>Absent any action, this suggested health will go into effect at 9 am. You can also set the new global health manually below:</p>

<form action="" method="POST">
<strong>new global health</strong>: <input type="text" name="new_health" size="4"><input type="submit" name="submit" id="submit" value="submit">
</form>

<?PHP } else { ?>

<p>Tomorrow's global health will be calculated at midnight. You will then have until 9a to enter an adjusted value if you wish. Otherwise the default value will be used. A form will display here during that time.</p>

<?PHP } ?>


    <div id="graph1">
    <h3>Yesterday's Cow Distribution</h3>
    <div id="hist_placeholder" style="width:400px;height:300px; float:left; margin-right: 20px"></div>
    <!-- /graph1 --></div>

    <div id="graph2">
    <h3>Total Cows and Global Health</h3>
    <div id="global_placeholder" style="width:400px;height:300px; float:left; margin-right: 20px"></div>
    <!-- /graph2 --></div>

    <div style="clear: both"><!-- --></div>

<h4>Yesterday's Orders</h4>
<table class="nonmain">
<tr>
 <th>user_id</th><th>student id</th><th>name</th><th>cows</th><th></th>
</tr>
<?PHP
	$qry = mysql_query("SELECT user_id, student_id, firstname, lastname, cows FROM commons_users cu JOIN commons_grazing_orders cgo USING(user_id) WHERE instance_id=$instance_id AND cgo.ts='$today' - INTERVAL 1 DAY AND approved=1 ORDER BY 2");
	while ($row = mysql_fetch_array($qry)) {
		echo <<<EOT
<tr>
 <td>$row[user_id]</td>
 <td>$row[student_id]</td>
 <td>$row[firstname] $row[lastname]</td>
 <td>$row[cows]</td>
 <td><a href="indiv.php?i=$hash&u=$row[user_id]">details</a></td>
EOT;
	}
?>
</table>

</body>
</html>
