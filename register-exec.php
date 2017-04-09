<?php
	//Start session
	session_start();
	
	//Include database connection details
	require_once('config.php');
        include('functions.php');
	
	//Array to store validation errors
	$errmsg_arr = array();
	
	//Validation error flag
	$errflag = false;
	
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
	
	//Function to sanitize values received from the form. Prevents SQL injection
	function clean($str) {
		$str = @trim($str);
		if(get_magic_quotes_gpc()) {
			$str = stripslashes($str);
		}
		return mysql_real_escape_string($str);
	}
	
	//Sanitize the POST values
	$student_id = clean($_POST["student_id"]);
	$fname = clean($_POST["fname"]);
	$lname = clean($_POST['lname']);
	$email = clean($_POST['email']);
	$login = clean($_POST['email']);
	$password = clean($_POST['password']);
	$cpassword = clean($_POST['cpassword']);
	$instance_id = clean($_POST['instance_id']);
	
	//Input Validations
	if($student_id == '') {
		$errmsg_arr[] = 'Student number missing';
		$errflag = true;
	}
	if($fname == '') {
		$errmsg_arr[] = 'First name missing';
		$errflag = true;
	}
	if($lname == '') {
		$errmsg_arr[] = 'Last name missing';
		$errflag = true;
	}
	if($email == '') {
		$errmsg_arr[] = 'E-mail missing';
		$errflag = true;
	}
	if($login == '') {
		$errmsg_arr[] = 'Login ID missing';
		$errflag = true;
	}
	if($password == '') {
		$errmsg_arr[] = 'Password missing';
		$errflag = true;
	}
	if($cpassword == '') {
		$errmsg_arr[] = 'Confirm password missing';
		$errflag = true;
	}
	if( strcmp($password, $cpassword) != 0 ) {
		$errmsg_arr[] = 'Passwords do not match';
		$errflag = true;
	}
	if($instance_id == '') {
		$errmsg_arr[] = 'Select a class';
		$errflag = true;
	}
	
	//Check for duplicate student ID
	if($student_id != '') {
		$qry = "SELECT * FROM commons_users WHERE student_id='$student_id' AND approved=1";
		$result = mysql_query($qry);
		if($result) {
			if(mysql_num_rows($result) > 0) {
				$errmsg_arr[] = 'Login for this student ID already exists';
				$errflag = true;
			}
			@mysql_free_result($result);
		}
		else {
			die("Query failed");
		}
	}
	if($login != '') {
		$qry = "SELECT * FROM commons_users WHERE login='$login' AND approved=1";
		$result = mysql_query($qry);
		if($result) {
			if(mysql_num_rows($result) > 0) {
				$errmsg_arr[] = 'Login for this email address already exists';
				$errflag = true;
			}
			@mysql_free_result($result);
		}
		else {
			die("Query failed");
		}
	}
	
	
	//If there are input validations, redirect back to the registration form
	if($errflag) {
		$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		session_write_close();
		header("location: register.php");
		exit();
	}

	//Create INSERT query
	$hash = random_gen(8);
	$qry = mysql_query("INSERT INTO commons_users (hash, instance_id, student_id, firstname, lastname, email, login, passwd, approved) VALUES('$hash', '$instance_id', '$student_id', '$fname','$lname', '$email', '$login','".md5($_POST['password'])."',1)");
	#$result = @mysql_query($qry);
	$user_id = mysql_insert_id();

	$qry = mysql_query("SELECT start_date FROM commons_instances WHERE instance_id=$instance_id");
	while ($row = mysql_fetch_array($qry)) {
		$start_date = $row[0];
	}
	
	//Allot starting cash
	$qry = mysql_query("INSERT INTO commons_users_cash_summary (user_id, ts, cash) VALUES ($user_id, '$start_date', 100000)");

	//Initialize herd
	$qry = mysql_query("INSERT INTO commons_herds (user_id, ts, herd_size, avg_health, avg_production) VALUES ($user_id, '$start_date', 0, 100, 100)");

	//Start alerts
	$qry = mysql_query("INSERT INTO commons_users_alerts (user_id) VALUES ($user_id)");

	//Check whether the query was successful or not
	if($user_id) {
		$to='devries@chem.ucsb.edu';
		$subject = "New cow farmer registration from $fname $lname";
		
		$emailmessage = "New login created by $fname $lname - $email.";
		$headers = "From: milandv@atlas.medialayer.net";
	
		#mail($to, $subject, $emailmessage, $headers);
		
		header("location: register-success.php");
		exit();
	}else {
		die("Query failed");
	}
?>
