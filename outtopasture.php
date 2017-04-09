<?PHP
        require_once('auth.php');
	require('cgi-bin/connect.inc');

	$instance_id = $_SESSION['SESS_INSTANCE_ID'];
	$qry = mysql_query("SELECT 1 FROM commons_instances WHERE HOUR(now() + INTERVAL (6 + timezone) HOUR) >=10 AND instance_id=$instance_id");
	if (mysql_num_rows($qry) > 0) {
		header("Location: graze.php");
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

	$user_id = $_SESSION['SESS_ID'];

?>

<html>
<head>
 <title><?PHP echo $commons_name ?></title>
 <style>
	@import url(http://fonts.googleapis.com/css?family=Bowlby+One+SC);
	@import url(http://fonts.googleapis.com/css?family=Inika:400,700);

	body	{font-family:Inika}
	h1	{font-family:'Bowlby One SC'}
	#wrapper	{width:900px;
			margin:auto}
	#top		{margin-bottom:4em}
	#narrow		{width:495px;
			float:left}
	#wide		{width:395px;
			float:right}
	.box		{padding:1ex;
			border:1px solid black;
			margin-bottom:2em;}
	.box h2		{margin-top:-1em;
			margin-left:5px;
			width:100px;
			background:white;
			text-align:center;}
	table		{width:100%}
	//td + td		{text-align:right}
	
	#submit		{background:black;color:white;font-weight:bold;font-family:inerit;font-size:125%;border:5px double white;cursor: hand; cursor: pointer;}
	#submit:hover	{background:#888;}

	#pasture	{border:1px solid black;width:3em;font-family:inherit}

	form div                            { overflow: hidden; margin: 0 0 5px 0; }
	input[type=text]                    { float: left; width: 40px; font: bold 20px Helvetica, sans-serif; padding: 3px 0 0 0; text-align: center; }
	.button                             { margin: 0 0 0 5px; text-indent: -9999px; cursor: pointer; width: 29px; height: 29px; float: left; text-align: center; background: url(img/buttons.png) no-repeat; }
	.dec                                { background-position: 0 -29px; }

	#order_submitted	{color: red;}
	#order_updated	{color: red; text-align: center;}
 </style>


</head>
<body>

<div id="wrapper">

<div id="header">
	<h1><?PHP echo $commons_name ?></h1>
</div>

<div id="top">
<p>It is past midnight and the cows are sleeping now. Early this morning they will be brought out to the commons to graze. Please return after 10 AM to set orders for the next day.</p>

 </div>

</div>




</div>
