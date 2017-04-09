<?php
	require('cgi-bin/connect.inc');
	session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Login Form</title>
<link href="loginmodule.css" rel="stylesheet" type="text/css" />
</head>
<body>
<?php
	if( isset($_SESSION['ERRMSG_ARR']) && is_array($_SESSION['ERRMSG_ARR']) && count($_SESSION['ERRMSG_ARR']) >0 ) {
		echo '<ul class="err">';
		foreach($_SESSION['ERRMSG_ARR'] as $msg) {
			echo '<li>',$msg,'</li>'; 
		}
		echo '</ul>';
		unset($_SESSION['ERRMSG_ARR']);
	}

        $qry = mysql_query("SELECT instance_id, hash, name FROM commons_instances WHERE now()<end_date ORDER BY instance_id DESC LIMIT 1");
        $instance_count = mysql_num_rows($qry);

?>
<form id="loginForm" name="loginForm" method="post" action="register-exec.php">
  <table width="300" border="0" align="center" cellpadding="2" cellspacing="0">
    <tr>
      <th>Student Number </th>
      <td><input name="student_id" type="text" class="textfield" id="student_id" /></td>
    </tr>
    <tr>
      <th>First Name </th>
      <td><input name="fname" type="text" class="textfield" id="fname" /></td>
    </tr>
    <tr>
      <th>Last Name </th>
      <td><input name="lname" type="text" class="textfield" id="lname" /></td>
    </tr>
    <tr>
      <th>e-mail </th>
      <td><input name="email" type="text" class="textfield" id="email" /></td>
    </tr>
    <tr>
      <th>Password</th>
      <td><input name="password" type="password" class="textfield" id="password" /></td>
    </tr>
    <tr>
      <th>Confirm Password </th>
      <td><input name="cpassword" type="password" class="textfield" id="cpassword" /></td>
    </tr>
<?PHP 
	if ($instance_count==1) { 
		while ($row = mysql_fetch_array($qry)) {
			$instance_id=$row[instance_id];
		}
		echo "<input type='hidden' name='instance_id' value='$instance_id'>";
	} else {
		echo "<tr>
			<th>Class</th>
			<td><select name='instance_id'>
				<option value=''>Select ...</option>";
		while ($row = mysql_fetch_array($qry)) {
			echo "<option value='$row[instance_id]'>$row[name]</option>";
		}
		echo "</select></td></tr>";
	}	
?>

    <tr>
      <td>&nbsp;</td>
      <td><input type="submit" name="Submit" value="Register" /></td>
    </tr>
  </table>
</form>
</body>
</html>
