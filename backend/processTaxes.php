<?PHP
    define('DB_HOST', 'localhost');
    define('DB_USER', 'milandev_dfo');
    define('DB_PASSWORD', 'soD7sOPN');
    define('DB_DATABASE', 'milandev_cows');
	
        //Connect to mysql server
        $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
        if(!$link) {
                die('Failed to connect to server: ' . mysql_error());
        }

        //Select database
        $db = mysql_select_db(DB_DATABASE);
        if(!$db) {
                die("Unable to select database");
        }

###

$instance_qry = mysql_query("SELECT instance_id, name, admin_email, DATE(now() + INTERVAL (6 + timezone) HOUR) as processing_date, DATEDIFF(now() + INTERVAL (6 + timezone) HOUR, start_date)+1 as day, default_graze, include_weekends, DATEDIFF(end_date, start_date) as total_days FROM commons_instances WHERE now() + INTERVAL (6 +  timezone) HOUR BETWEEN start_date AND end_date AND HOUR(now() + INTERVAL (6 + timezone) HOUR)=1 AND DAYOFWEEK(NOW())%3=2 AND taxes_levied=1");

##for debugging
#$instance_qry = mysql_query("SELECT instance_id, name, admin_email, DATE(now() + INTERVAL (6 + timezone) HOUR) as processing_date, DATEDIFF(now() + INTERVAL (6 + timezone) HOUR, start_date)+1 as day, default_graze, include_weekends, DATEDIFF(end_date, start_date) as total_days FROM commons_instances WHERE now() + INTERVAL (6 +  timezone) HOUR BETWEEN start_date AND end_date AND instance_id=12");

$finance_rate = 0.076; # late fee penalty


while ($instance_row = mysql_fetch_array($instance_qry)) {
	$instance_id = $instance_row['instance_id'];
	$name = $instance_row['name'];
	$admin_email = $instance_row['admin_email'];
	$commons_day = $instance_row['day'];
	$processing_date = $instance_row['processing_date'];
	$default_graze = $instance_row['default_graze'];
	$include_weekends = $instance_row['include_weekends'];
	$total_days = $instance_row['total_days'];
	$price_per_liter = 100 / $total_days;  # since 20L * price per liter * total days = 2*value_of_a_cow = 2000

	echo "Starting instance_id = $instance_id \n";

	// get order date (aka yesterday)
	$qry = mysql_query("SELECT '$processing_date' - INTERVAL 1 DAY");
	while ($row = mysql_fetch_array($qry)) { $order_date = $row[0]; }

        // grab global health parameter
        $qry = mysql_query("SELECT global_health FROM commons_global_stats WHERE instance_id=$instance_id AND ts<'$processing_date' ORDER BY ts DESC LIMIT 1");
        while ($row = mysql_fetch_array($qry)) { $current_global_health = $row['global_health']; }

	// fine previous late invoices
	$qry = mysql_query("SELECT invoice_id, user_id, remaining FROM commons_users_taxes_owed cuto JOIN commons_users cu USING(user_id) WHERE instance_id=$instance_id AND remaining>0");
	while ($row = mysql_fetch_array($qry)) {
		$invoice_id=$row[0];
		$remaining=$row[2];
		$new_remaining=(1+$finance_rate)*$remaining;

		$update_qry = mysql_query("UPDATE commons_users_taxes_owed SET remaining=$new_remaining WHERE invoice_id=$invoice_id");
	}

	// assess new taxes
	echo "Assessing new taxes ...\n";
	$qry = mysql_query("SELECT user_id, ROUND(AVG(cows),1) avg_herd, SUM(amt) revenue, ROUND((SUM(amt)/AVG(cows))*(IF(AVG(cows)-20<0,0,AVG(cows)-20)),2) as taxable_revenue FROM commons_users_cash_production cucp JOIN commons_users cu USING(user_id) WHERE instance_id=$instance_id AND cucp.ts>'$processing_date' - INTERVAL 3 DAY GROUP BY 1");
	while ($row = mysql_fetch_array($qry)) {
		$user_id=$row[0];
		$avg_herd=$row[1];
		$revenue=$row[2];
		$taxable_revenue=$row[3];


		$tax=0;
		if ($avg_herd<=20) {
			//no tax levied
			$tax=0;
		} elseif ($avg_herd>20 && $avg_herd<=50) {
			$tax = 0.05*$taxable_revenue;
		} elseif ($avg_herd>50 && $avg_herd<=100) {
			$tax = 0.1*$taxable_revenue;
		} else {
			$tax = 0.15*$taxable_revenue;
		}
		if ($tax>0) {
			$insert_qry = mysql_query("INSERT INTO commons_users_taxes_owed (user_id, ts, avg_herd, revenue, taxable_revenue, tax, paid, remaining) VALUES ($user_id, '$processing_date', $avg_herd, $revenue, $taxable_revenue, $tax, 0, $tax)");
			$invoice_id = mysql_insert_id();
		}
	}
}
?>
