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



$instance_qry = mysql_query("SELECT instance_id, DATE(now() + INTERVAL (6 + timezone) HOUR) as processing_date, default_graze, include_weekends FROM commons_instances WHERE now() + INTERVAL (6 +  timezone) HOUR BETWEEN start_date AND end_date AND HOUR(now() + INTERVAL (6 + timezone) HOUR)=9");

while ($instance_row = mysql_fetch_array($instance_qry)) {
        $instance_id = $instance_row['instance_id'];
        $processing_date = $instance_row['processing_date'];
        $default_graze = $instance_row['default_graze'];
        $include_weekends = $instance_row['include_weekends'];
	$today = $processing_date;

        if ($include_weekends == 0) {
                //don't process on Sunday or Monday
                $qry = mysql_query("SELECT DAYOFWEEK('$processing_date')");
                while ($row = mysql_fetch_array($qry)) { $dayofweek=$row[0]; }

                if ($dayofweek == 1 || $dayofweek == 2) {
			echo "Skipping instance_id = $instance_id. We rest on the sabbath around here. \n";
                        continue;
                }
        }

        echo "Starting instance_id = $instance_id \n";

        // get order date (aka yesterday)
        $qry = mysql_query("SELECT '$processing_date' - INTERVAL 1 DAY");
        while ($row = mysql_fetch_array($qry)) { $order_date = $row[0]; }


	// grab global health parameter
	$qry = mysql_query("SELECT global_health FROM commons_global_stats WHERE instance_id=$instance_id AND ts<'$processing_date' ORDER BY ts DESC LIMIT 1");
	while ($row = mysql_fetch_array($qry)) { $old_global_health = $row['global_health']; }

	//See if today's is already been set by hand
	$qry = mysql_query("SELECT global_health FROM commons_global_stats WHERE instance_id=$instance_id AND ts='$processing_date'");
	if (mysql_num_rows($qry)>0) {
		while ($row = mysql_fetch_array($qry)) { $new_global_health = $row['global_health']; }
		echo "health set by hand at $new_global_health \n";
	} else {
		//need to set it here. same as on admin page
		//last order_date
		$qry = mysql_query("SELECT max(cgo.ts) FROM commons_users cu JOIN commons_grazing_orders cgo USING(user_id) WHERE instance_id=$instance_id");
		if (mysql_num_rows($qry) > 0) {
			while ($row = mysql_fetch_array($qry)) {
				$max_order_date = $row[0];
			}
			$qry2 = mysql_query("SELECT COUNT(*) FROM commons_users cu JOIN commons_grazing_orders cgo USING(user_id) WHERE instance_id=$instance_id AND cgo.ts='$max_order_date' AND approved=1");
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
echo "user_count - $user_count and capacity = $capacity \n";

	        $qry = mysql_query("SELECT SUM(cows) as new_total FROM commons_grazing_orders cgo JOIN commons_users cu USING(user_id) WHERE instance_id=$instance_id AND cgo.ts='$today' - INTERVAL 1 DAY AND approved=1");
	        while ($row = mysql_fetch_array($qry)) {
	                $new_commons_size = $row['new_total'];
	        }

		$current_global_health = $old_global_health;
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

		// record for posterity
		$new_global_health = $suggested_health;
		$qry = mysql_query("INSERT INTO commons_global_stats (instance_id, ts, commons_size, global_health, self_set) VALUES ($instance_id, '$today', $new_commons_size, '$new_global_health', 0)");
	}

	// set new cow healths
	if ($old_global_health==0) { 
		$adjustment_factor=0;
	} else {
		$adjustment_factor = $new_global_health / $old_global_health;
	}
echo "old =$old_global_health ; new = $new_global_health ; adj = $adjustment_factor \n";

	if ($adjustment_factor == 0) { 
		$production_adjustment_factor = 0; 
	} else {
		$production_adjustment_factor = 7/16 + (9/16)*$adjustment_factor;
	}

	$qry = mysql_query("INSERT INTO commons_cows_health_history SELECT cow_id, '$today', IF(cow_health*$adjustment_factor>100, 100, cow_health*$adjustment_factor) FROM commons_cows JOIN commons_users USING(user_id) JOIN commons_cows_current_health USING(cow_id) WHERE instance_id=$instance_id AND is_active=1");
	$qry = mysql_query("UPDATE commons_cows_current_health ccch JOIN commons_cows_health_history cchh ON(ccch.cow_id=cchh.cow_id AND cchh.ts='$today') SET ccch.cow_health = cchh.cow_health");

	$qry = mysql_query("INSERT INTO commons_cows_production_history SELECT cow_id, '$today', IF(cow_production*$production_adjustment_factor>100,100,cow_production*$production_adjustment_factor) FROM commons_cows JOIN commons_users USING(user_id) JOIN commons_cows_current_production USING(cow_id) WHERE instance_id=$instance_id AND is_active=1");
	$qry = mysql_query("UPDATE commons_cows_current_production cccp JOIN commons_cows_production_history ccph ON(cccp.cow_id=ccph.cow_id AND ccph.ts='$today') SET cccp.cow_production = ccph.cow_production");


	// write to herd_summary
	#$qry = mysql_query("INSERT INTO commons_herds SELECT user_id, '$today', COUNT(*), AVG(cow_health), AVG(cow_production) FROM commons_users JOIN commons_cows USING(user_id) JOIN commons_cows_current_health USING(cow_id) JOIN commons_cows_current_production USING(cow_id) WHERE instance_id=$instance_id AND is_active=1 GROUP BY 1");
	$qry = mysql_query("INSERT INTO commons_herds SELECT cu.user_id, '$today', COUNT(DISTINCT cc.cow_id), IF(AVG(cow_health)>0, AVG(cow_health),0), IF(AVG(cow_production)>0,AVG(cow_production),0) FROM commons_users cu LEFT JOIN commons_cows cc ON(cu.user_id=cc.user_id AND is_active=1) LEFT JOIN commons_cows_current_health USING(cow_id) LEFT JOIN commons_cows_current_production USING(cow_id) WHERE instance_id=$instance_id GROUP BY 1");

}

?>
