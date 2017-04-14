<?PHP


$twitter_name = str_replace(" ", "", $commons_name);
# find first monday after start date
        $qry = mysql_query("SELECT (9-DAYOFWEEK(start_date))%7+7 as first_tweet_day FROM commons_instances WHERE instance_id=$instance_id");
        while ($instance_row = mysql_fetch_array($qry)) {
                $first_tweet_day = $instance_row['first_tweet_day'];
        }

#for debugging
#$first_tweet_day=$first_tweet_day-1;

if ($commons_day > $first_tweet_day && $commons_day<=($first_tweet_day+4)) {

	# find the price of milk
	$qry = mysql_query("SELECT DATE_FORMAT(ts,'%b %e') as tweet_date, global_health, DATEDIFF(end_date,start_date) as commons_length FROM commons_instances JOIN commons_global_stats USING(instance_id) WHERE ts>=start_date + INTERVAL ($first_tweet_day-1) DAY AND instance_id=$instance_id ORDER BY ts LIMIT 1");
        while ($tweet_row = mysql_fetch_array($qry)) {
                $tweet_date = $tweet_row['tweet_date'];
                $global_health = $tweet_row['global_health'];
                $commons_length = $tweet_row['commons_length'];
        }

	$milk_amt = round(20*7*(7/16+(9/16)*($global_health/100)));
	$milk_profit = round($milk_amt * 100 / $commons_length,2);

echo <<< EOT
<div id="tweet" class="tweet">
<p><span class="tweetimg"><img src="img/cowtweet.png"></span><span class="tweettxt"><strong>$twitter_name</strong> <span style="color: #aaa;">@TheCommons • $tweet_date</span><br/> Congrats to @FarmerYemina whose 7 cows produced $milk_amt liters of milk today which sold for &#36;$milk_profit! #MooooreMilk</span>
<span style="clear: both;">&nbsp;</span>
</p>
</div>
EOT;


}

if ($commons_day > ($first_tweet_day + 4) && $commons_day <= ($first_tweet_day + 8)) {

	# find the size of the commons
	$qry = mysql_query("SELECT DATE_FORMAT(ts,'%b %e') as tweet_date, commons_size FROM commons_instances JOIN commons_global_stats USING (instance_id) WHERE ts>=start_date + INTERVAL ($first_tweet_day + 3) DAY AND instance_id=$instance_id ORDER BY ts LIMIT 1");
	while ($tweet_row=mysql_fetch_array($qry)) {
		$tweet_date = $tweet_row['tweet_date'];
		$commons_size = $tweet_row['commons_size'];
	}

	$qry = mysql_query("SELECT COUNT(*) as farmer_count FROM commons_users WHERE instance_id=$instance_id");
	while ($tweet_row=mysql_fetch_array($qry)) {
		$farmer_count = $tweet_row['farmer_count'];
	}
	
	$avg_herd = round($commons_size/$farmer_count);

echo <<< EOT
<div id="tweet" class="tweet">
<p><span class="tweetimg"><img src="img/cowtweet.png"></span><span class="tweettxt"><strong>$twitter_name</strong> <span style="color: #aaa;">@TheCommons • $tweet_date</span><br/>New study shows record high $commons_size cows on the commons today. Avg farmer has $avg_herd cows. #cowntingcows</span>
<span style="clear: both;">&nbsp;</span>
</p>
</div>
EOT;
}
?>

