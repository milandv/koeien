<?php

        require('cgi-bin/connect.inc');
        include('functions.php');
	include('class.Email.php');


if ($_POST["submit"]) {
	$student_id = $_POST["student_id"];
	if ($student_id) {
		$qry = mysql_query("SELECT user_id, email FROM commons_users WHERE student_id='$student_id'");
		$user_found = mysql_num_rows($qry);
		if ($user_found>0) {
			while ($row = mysql_fetch_array($qry)) {
				$user_id = $row[0];
				$email = $row[1];
			}
			#create reset hash
			$reset_hash = random_gen(12);
			$qry = mysql_query("SELECT * FROM commons_reset_requests WHERE hash='$reset_hash'");			
			if (mysql_num_rows($qry)>0) {
				//in the odd chance this reset hash has been used ... change it
				$reset_hash = $reset_hash.'2';
			}

			#put in database
			$qry = mysql_query("INSERT INTO commons_reset_requests (user_id, hash) VALUES ($user_id, '$reset_hash')");

			#send email
			$textVersion = "To reset your password, please use the following link. This link is specific to you and will expire in 15 minutes.

			http://grazingcows.org/pwd_reset.php?h=$reset_hash
";

			$htmlVersion = "<p>To reset your password, please use the following link. This link is specific to you and will expire in 15 minutes.</p>

                        <p><a href='http://grazingcows.org/pwd_reset.php?h=$reset_hash'>http://grazingcows.org/pwd_reset.php?h=$reset_hash</a></p>
";

			$msg = new Email($email, 'GrazingCows.org', "Password Reset Request");
			$msg->SetMultipartAlternative($textVersion, $htmlVersion);
			$SendSuccess = $msg->Send();
		
			$request_sent=1;
		} else {
			$user_not_found=1;
		}
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Logged Out</title>
<link href="loginmodule.css" rel="stylesheet" type="text/css" />
</head>
<body>
<h1>Password Reset</h1>
<p align="center">&nbsp;</p>
<h4 align="center" class="err">Request a password reset link</h4>

<?PHP if ($user_not_found) { ?>
<p align="center" style="color: red">User not found!</p>
<?PHP } ?>
<?PHP if ($request_sent) { ?>
<p align="center" style="color: red">A password reset link has been sent to the email on file for this student. Look for the email in your inbox (or spam folder) and use the link to reset your password. The link will expire in 15 minutes.</p>
<?PHP } ?>

<p align="center">To reset your password, enter your student number below. An email will be sent to the address you provided with a link to reset your password.</p>

<form action="pwd_request.php" method="POST">
  <table width="300" border="0" align="center" cellpadding="2" cellspacing="0">
    <tr>
      <td><b>Student Number</b></td>
      <td><input name="student_id" type="student_id" class="textfield" id="student_id" /></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td><input type="submit" name="submit" value="Submit" /></td>
    </tr>
  </table>


</form>
</body>
</html>
