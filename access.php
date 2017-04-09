<?php
	require_once('auth.php');

	$sort=$_GET['sort'];
	if ($sort == "") {
		if ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2) {
			$sort = 'A';
		} else {
			$sort = 'D';
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>DfO</title>
<link href="loginmodule.css" rel="stylesheet" type="text/css" />
</script>
<script language="JavaScript">
<!--
function gotosite(site) {
        if (site != "") {
                self.location=site;
        }
}
//-->
</script>


</head>
<body>
<h1>Welcome <?php echo $_SESSION['SESS_FIRST_NAME'];?></h1>

<?PHP 
if ($_SESSION['SESS_ADMIN_TYPE'] == 1) {
echo "
<a href=volunteer.php>Volunteer Database</a> | <a href=referral.php>Referral Database</a> | <a href=referralsuccess.php>Referral Success Rates</a> | <a href=logout.php>Logout</a>
";
}

if ($_SESSION['SESS_ADMIN_TYPE'] == 2) {
echo "
<a href=volunteer.php>Volunteer Database</a> | <a href=referralsuccess.php>Referral Success Rates</a> | <a href=logout.php>Logout</a>
";
}

if ($_SESSION['SESS_ADMIN_TYPE'] == 3) {
echo "
<a href=logout.php>Logout</a>
";
}
?>
<p>This is a password protected area only accessible to members. </p>

	<TABLE>
	<tr>
	<td>
	<FORM NAME="sortform" onsubmit="gotosite(document.sortform.url.options[document.sortform.url.selectedIndex].value);return false">
	 <p class=gen>Display:
		<SELECT NAME="url" onchange="gotosite(this.options[this.selectedIndex].value)" SIZE=1>

			<option value="">Sort the list by:</option>
			<?PHP
			if ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2) {
		echo "	
			<option value=index.php?sort=A>Date</option>
			<option value=index.php?sort=B>Zip Code</option>
";
			}
			?>
			<option value="index.php?sort=C">State</option>
			<option value="index.php?sort=D">Last Name</option>
			<option value="index.php?sort=E">Type</option>
			<option value="index.php?sort=F">No Duplicates</option>
			<?PHP
			if ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2) {
		echo "	
			<option value=index.php?sort=G>Today Only</option>
";
			}
			?>
		</SELECT>

	</FORM>
	</td>
	<td>
	<FORM method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	   <p>or search by Keyword:	<INPUT TYPE="text" name="keyword">
	   <INPUT TYPE="submit" name="submit" value="Submit"></p>
	</FORM>
	</td>
	</TABLE>

<?php 
	require('connect.inc');

	if ( isset( $_GET['submit'] ) ) {
		$sort="";
		$keyword = $_GET['keyword'];
		print "Keyword = $keyword";	
		$qry = "SELECT letter.*, city, state FROM letter, zipcodes WHERE zip=zipcode and (institution REGEXP '$keyword' or title REGEXP '$keyword' or email REGEXP '$keyword' or degrees REGEXP '$keyword' or lname REGEXP '$keyword' or fname REGEXP '$keyword' or zip REGEXP '$keyword' or date REGEXP '$keyword') GROUP BY email ORDER BY date";
		$result=mysql_query($qry);
	}

	if ($sort=='A' && ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2)) {
		$sortby='date';
	}
	if ($sort=='B' && ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2)) {
		$sortby='zip';
	}
	if ($sort=='C') {
		$sortby='state';
	}
	if ($sort=='D') {
		$sortby='lname';
	}
	if ($sort=='E') {
		$sortby='signatory_type';
	}
	if ($sort=='F') {
		$sortby='unique e-mail';
	}
	if ($sort=='G') { 
		if ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2) {
			$sortby='today only';
		} else {
			$sortby='';
		}
	}


      if ( !$sort=="") {
	echo "<br>sorted by = $sortby";
	if ($sort == 'F') {
		$qry = "SELECT letter.*, city, state FROM letter, zipcodes WHERE zip=zipcode GROUP BY email ORDER BY date";
		$result=mysql_query($qry);
	} elseif ($sort == 'G' && ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2)) {
		$qry = "SELECT letter.*, city, state FROM letter, zipcodes WHERE zip=zipcode and (date_format(current_date, '%j')-date_format(date,'%j'))=0 ORDER BY date";
		$result=mysql_query($qry);
	} elseif ($sort =='C') {
		$qry = "SELECT letter.*, city, state FROM letter, zipcodes WHERE zip=zipcode ORDER BY $sortby, signatory_type, lname";
		$result=mysql_query($qry);
	} else {
		$qry = "SELECT letter.*, city, state FROM letter, zipcodes WHERE zip=zipcode ORDER BY $sortby";
		$result=mysql_query($qry);
	}
      }

	if (mysql_num_rows($result)>0) {
		echo "<table border=1>
			<tr>
			<th></th>
			<th>First</th>				
			<th>Last</th>";
		if ($_SESSION['SESS_ADMIN_TYPE'] == 1) {
			echo "<th>E-mail</th>";
		}
		if ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2) {
			echo "<th>Zip</th>";
		}
		echo "	<th>city</th>
			<th>state</th>
			<th>Degrees</th>
			<th>Institution</th>
			<th>Position</th>
			<th>Type</th>";
		if ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2) {
			echo "<th>Date</th>";
		}
		echo "	</tr>";	
		$i=1;
		while ($row = mysql_fetch_array($result)) {
			$city = $row["city"];
                	$city = strtolower($city);
                	$city = ucwords($city);


			echo "<tr>
				<td>$i</td>
				<td>$row[fname]</td>
				<td>$row[lname]</td>";
			if ($_SESSION['SESS_ADMIN_TYPE'] == 1) {
				echo "<td>$row[email]</td>";
			}
			if ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2) {
				echo "<td>$row[zip]</td>";
			}
			echo "	<td>$city</td>
				<td>$row[state]</td>
				<td>$row[degrees]</td>
				<td>$row[institution]</td>
				<td>$row[title]</td>
				<td>$row[signatory_type]</td>";
			if ($_SESSION['SESS_ADMIN_TYPE'] == 1 || $_SESSION['SESS_ADMIN_TYPE'] == 2) {
				echo "<td>$row[date]</td>";
			}
			echo "</tr>";
			$i=$i+1;
		}
		echo "</table>";
	} else {
		die("Query failed");
	}

?>


</body>
</html>
