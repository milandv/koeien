<?php
	require('cgi-bin/connect.inc');

	$hash = $_GET['h'];

	$qry = mysql_query("SELECT user_id, student_id, cu.ts as cu_ts FROM commons_reset_requests crr JOIN commons_users cu USING(user_id) WHERE crr.hash='$hash' AND crr.ts > now() - INTERVAL 15 MINUTE");
	$hash_check = mysql_num_rows($qry);

	if ($hash_check>0) {
		while ($row = mysql_fetch_array($qry)) {
			$user_id = $row['user_id'];
			$student_id = $row['student_id'];
			$cu_ts = $row['cu_ts'];
		}
	} else {
		header("Location: pwd_request.php");
	}

        //Function to sanitize values received from the form. Prevents SQL injection
        function clean($str) {
                $str = @trim($str);
                if(get_magic_quotes_gpc()) {
                        $str = stripslashes($str);
                }
                return mysql_real_escape_string($str);
        }
	if ($_POST["submit"]) {
	        $password = clean($_POST['password']);
	        $cpassword = clean($_POST['cpassword']);

		$errors=0;
	        if($password == '') {
	                $errmsg_arr[] = 'Password missing';
			$errors=1;
	        }
	        if($cpassword == '') {
	                $errmsg_arr[] = 'Confirm password missing';
			$errors=1;
	        }
	        if( strcmp($password, $cpassword) != 0 ) {
	                $errmsg_arr[] = 'Passwords do not match';
			$errors=1;
	        }

		if ($errors==0) {
			$qry = mysql_query("UPDATE commons_users SET passwd='".md5($_POST['password'])."', ts='$cu_ts' WHERE user_id=$user_id");
			
			$reset_success=1;
		}

	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Login Form</title>
<link href="loginmodule.css" rel="stylesheet" type="text/css" />
</head>
<body>
<?PHP
                echo '<ul class="err">';
                foreach($errmsg_arr as $msg) {
                        echo '<li>',$msg,'</li>';
                }
                echo '</ul>';
?>

<?PHP if ($reset_success) { ?>

<p style="color: red" align="center">Password reset successfully. To log in, click <a href="login.php">here</a>.</p>

<?PHP } else { ?>

<form id="resetForm" name="resetForm" method="post" action="pwd_reset.php?h=<?PHP echo $hash ?>">
  <table width="300" border="0" align="center" cellpadding="2" cellspacing="0">
    <tr>
      <th>Student Number </th>
      <td><?PHP echo $student_id ?></td>
    </tr>
    <tr>
      <th>New Password</th>
      <td><input name="password" type="password" class="textfield" id="password" /></td>
    </tr>
    <tr>
      <th>Confirm New Password </th>
      <td><input name="cpassword" type="password" class="textfield" id="cpassword" /></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td><input type="submit" name="submit" value="Reset Password" /></td>
    </tr>
  </table>
</form>

<?PHP } ?>
</body>
</html>
