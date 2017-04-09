<?PHP
	require('../cgi-bin/connect.inc');
	
	$qry = mysql_query("SELECT instance_id, hash, name, DATEDIFF(now(),start_date)+1 AS days, DATEDIFF(end_date,start_date)+1 AS total_days FROM commons_instances WHERE end_date>now()-INTERVAL 7 YEAR");
	$instance_count = mysql_num_rows($qry);
?>

<html>
 <head>
	
 </head>

 <body>
<h1>Welcome, Commons Administrator</h1>

<?PHP if ($instance_count>0) { ?>
<h2>Administer a Commons</h2>

<table border="1">
<tr>
 <th>Administer</th>
 <th>Name</th>
 <th>Day</th>
</tr>
<?PHP
	while ($row=mysql_fetch_array($qry)) {
		echo <<<EOT
<tr>
 <td><a href="admin.php?i=$row[hash]">here</a></td>
 <td>$row[name]</td>
 <td>$row[days] of $row[total_days]</td>
</tr>
EOT;
	}
?>
</table>

<p><i>&mdash; or &mdash;</i></p>

<?PHP } else { ?>
<p>There are no current commons instances running.</p>

<?PHP } ?>

<p><a href="create.php">Create a new commons</a></p>
 </body>
</html>
