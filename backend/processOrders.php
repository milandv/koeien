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

### CLASS.EMAIL.PHP

//** ©William Fowler (wmfwlr@cogeco.ca) 
//** MAY 13/2004, Version 1.1
//** - added support for CC and BCC fields.
//** - added support for multipart/alternative messages.
//** - added ability to create attachments manually using literal content.
//** DECEMBER 15/2003, Version 1.0 

  if(isset($GLOBALS["emailmsgclass_php"])) { return; }  //** onlyinclude once. 
  $GLOBALS["emailmsgclass_php"] = 1;                    //** filewas included. 

//** the newline character(s) to be used when generating an email message. 

  define("EmailNewLine", "\r\n"); 

//** the unique X-Mailer identifier for emails sent with this tool. 

  define("EmailXMailer", "PHP-EMAIL,v1.1 (William Fowler)"); 

//** the default charset values for both text and HTML emails. 

  define("DefaultCharset", "iso-8859-1"); 

//**!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! 
//**EMAIL_MESSAGE_CLASS_DEFINITION******************************************** 
//** The Email class wrappers PHP's mail function into a class capable of 
//** sending attachments and HTML email messages. Custom headers can also be 
//** included as if using the mail function. 

class Email 
{ 
//** (String) the recipiant email address, or comma separated addresses. 

  var $To = null; 

//** (String) the recipiant addresses to receive a copy. Can be a comma
//** separated addresses. 

  var $Cc = null; 

//** (String) the recipiant addresses to receive a hidden copy. Can be a 
//** comma separated addresses. 

  var $Bcc = null; 

//** (String) the email address of the message sender. 

  var $From = null; 

//** (String) the subject of the email message. 

  var $Subject = null; 

//** (String) body content for the message. Must be plain text or HTML based
//** on the 'TextOnly' field. This field is ignored if 
//** SetMultipartAlternative() is called with valid content.

  var $Content = null;

//** an array of EmailAttachment instances to be sent with this message. 

  var $Attachments; 

//** any custom header information that must be used when sending email. 

  var $Headers = null; 

//** whether email to be sent is a text email or a HTML email. 

  var $TextOnly = true; 

//** the charset of the email to be sent (initially none, let type decide). 

  var $Charset = null; 

//** Create a new email message with the parameters provided. 

  function Email($to=null, $from=null, $subject=null, $headers=null) 
  { 
    $this->To = $to; 
    $this->From = $from; 
    $this->Subject = $subject; 
    $this->Headers = $headers;   

//** create an empty array for attachments. NULL out attachments used for
//** multipart/alternative messages initially.
   
    $this->Attachments = Array();    
    $this->Attachments["text"] = null;
    $this->Attachments["html"] = null;
  } 
//** Returns: Boolean
//** Set this email message to contain both text and HTML content.
//** If successful all attachments and content are ignored.

  function SetMultipartAlternative($text=null, $html=null)
  {
//** non-empty content for the text and HTML version is required.

    if(strlen(trim(strval($html))) == 0 || strlen(trim(strval($text))) == 0)
      return false;
    else
    {
//** create the text email attachment based on the text given and the standard
//** plain text MIME type.

      $this->Attachments["text"] = new EmailAttachment(null, "text/plain");
      $this->Attachments["text"]->LiteralContent = strval($text);

//** create the html email attachment based on the HTML given and the standard
//** html text MIME type.

      $this->Attachments["html"] = new EmailAttachment(null, "text/html");
      $this->Attachments["html"]->LiteralContent = strval($html);

      return true;  //** operation was successful.
    }
  }
//** Returns: Boolean 
//** Create a new file attachment for the file (and optionally MIME type) 
//** given. If the file cannot be located no attachment is created and 
//** FALSE is returned. 

  function Attach($pathtofile, $mimetype=null) 
  { 
//** create the appropriate email attachment. If the attachment does not 
//** exist the attachment is not created and FALSE is returned. 

    $attachment = new EmailAttachment($pathtofile, $mimetype); 
    if(!$attachment->Exists()) 
      return false; 
    else 
    { 
      $this->Attachments[] = $attachment;  //** add the attachment to list. 
      return true;                         //** attachment successfully added. 
    } 
  } 
//** Returns: Boolean 
//** Determine whether or not the email message is ready to be sent. A TO and 
//** FROM address are required. 

  function IsComplete() 
  { 
    return (strlen(trim($this->To)) > 0 && strlen(trim($this->From)) > 0); 
  } 
//** Returns: Boolean 
//** Attempt to send the email message. Attach all files that are currently 
//** valid. Send the appropriate text/html message. If not complete FALSE is 
//** returned and no message is sent. 

  function Send() 
  { 
    if(!$this->IsComplete())  //** message is not ready to send. 
      return false;           //** no message will be sent. 

//** generate a unique boundry identifier to separate attachments. 

    $theboundary = "-----" . md5(uniqid("EMAIL")); 

//** the from email address and the current date of sending. 

    $headers = "Date: " . date("r", time()) . EmailNewLine .
               "From: $this->From" . EmailNewLine;

//** if a non-empty CC field is provided add it to the headers here.

    if(strlen(trim(strval($this->Cc))) > 0)
      $headers .= "CC: $this->Cc" . EmailNewLine;
    
//** if a non-empty BCC field is provided add it to the headers here.

    if(strlen(trim(strval($this->Bcc))) > 0)
      $headers .= "BCC: $this->Bcc" . EmailNewLine;

//** add the custom headers here, before important headers so that none are 
//** overwritten by custom values. 

    if($this->Headers != null && strlen(trim($this->Headers)) > 0) 
      $headers .= $this->Headers . EmailNewLine; 

//** determine whether or not this email is mixed HTML and text or both.

    $isMultipartAlternative = ($this->Attachments["text"] != null &&
                               $this->Attachments["html"] != null);

//** determine the correct MIME type for this message.

    $baseContentType = "multipart/" . ($isMultipartAlternative ? 
                                       "alternative" : "mixed");

//** add the custom headers, the MIME encoding version and MIME typr for the 
//** email message, the boundry for attachments, the error message if MIME is 
//** not suppported. 

    $headers .= "X-Mailer: " . EmailXMailer . EmailNewLine . 
                "MIME-Version: 1.0" . EmailNewLine . 
                "Content-Type: $baseContentType; " .
                "boundary=\"$theboundary\"" . EmailNewLine . EmailNewLine; 

//** if a multipart message add the text and html versions of the content.

    if($isMultipartAlternative)
    {
//** add the text and html versions of the email content.

      $thebody = "--$theboundary" . EmailNewLine . 
                  $this->Attachments["text"]->ToHeader() . EmailNewLine .
                 "--$theboundary" . EmailNewLine . 
                  $this->Attachments["html"]->ToHeader() . EmailNewLine; 
    }
//** if either only html or text email add the content to the email body.

    else
    {
//** determine the proper encoding type and charset for the message body. 

      $theemailtype = "text/" . ($this->TextOnly ? "plain" : "html"); 
      if($this->Charset == null) 
        $this->Charset = DefaultCharset;

//** add the encoding header information for the body to the content. 

      $thebody = "--$theboundary" . EmailNewLine . 
                 "Content-Type: $theemailtype; charset=$this->Charset" . 
                  EmailNewLine . "Content-Transfer-Encoding: 8bit" . 
                  EmailNewLine . EmailNewLine . $this->Content . 
                  EmailNewLine . EmailNewLine; 

//** loop over the attachments for this email message and attach the files 
//** to the email message body. Only if not multipart alternative.

      foreach($this->Attachments as $attachment) 
      { 
 //** check for NULL attachments used by multipart alternative emails. Do not
 //** attach these.

        if($attachment != null)
        {
          $thebody .= "--$theboundary" . EmailNewLine . 
                       $attachment->ToHeader() . EmailNewLine; 
        }
      } 
    }
//** end boundry marker is required.

    $thebody .= "--$theboundary--"; 

//** attempt to send the email message. Return the operation success. 

    return mail($this->To, $this->Subject, $thebody, $headers); 
  } 
} 
//******************************************END_EMAIL_MESSAGE_CLASS_DEFINITION 
//**!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! 

//**!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! 
//**EMAIL_ATTACHMENT_CLASS_DEFINITION***************************************** 
//** The EmailAttachment class links a file in the file system to the 
//** appropriate header to be included in an email message. if the file does 
//** not exist the attachment will not be sent in any email messages. It can
//** also be used to generate an attachment from literal content provided.

class EmailAttachment 
{ 
//** (String) the full path to the file to be attached. 

  var $FilePath = null; 

//** (String) the MIME type for the file data of this attachment. 

  var $ContentType = null; 

//** binary content to be used instead the contents of a file.

  var $LiteralContent = null;

//** Creates a new email attachment ffrom the file path given. If no content 
//** type is given the default 'application/octet-stream' is used. 

  function EmailAttachment($pathtofile=null, $mimetype=null) 
  { 
//** if no MIME type is provided use the default value specifying binary data. 
//** Otherwise use the MIME type provided. 

    if($mimetype == null || strlen(trim($mimetype)) == 0) 
      $this->ContentType = "application/octet-stream"; 
    else 
      $this->ContentType = $mimetype; 

    $this->FilePath = $pathtofile;  //** save the path to the file attachment. 
  } 
//** Returns: Boolean
//** Determine whether literal content is provided and should be used as the
//** attachment rather than a file.

  function HasLiteralContent()
  {
    return (strlen(strval($this->LiteralContent)) > 0);
  }
//** Returns: String 
//** Get the binary string data to be used as this attachment. If literal
//** content is provided is is used, otherwise the contents of the file path
//** for this attachment is used. If no content is available NULL is returned.

  function GetContent()
  {
//** non-empty literal content is available. Use that as the attachment.
//** Assume the user has used correct MIME type.

    if($this->HasLiteralContent())
      return $this->LiteralContent;

//** no literal content available. Try to get file data.

    else
    {
      if(!$this->Exists())  //** file does not exist.
        return null;        //** no content is available.
      else
      {
//** open the file attachment in binary mode and read the contents. 

        $thefile = fopen($this->FilePath, "rb"); 
        $data = fread($thefile, filesize($this->FilePath));
        fclose($thefile);
        return $data; 
      }
    }
  }
//** Returns: Boolean 
//** Determine whether or not the email attachment has a valid, existing file 
//** associated with it. 

  function Exists() 
  { 
    if($this->FilePath == null || strlen(trim($this->FilePath)) == 0) 
      return false; 
    else 
      return file_exists($this->FilePath); 
  } 
//** Returns: String 
//** Generate the appropriate header string for this email attachment. If the 
//** the attachment content does not exist NULL is returned. 

  function ToHeader() 
  { 
    $attachmentData = $this->GetContent();  //** get content for the header.
    if($attachmentData == null)             //** no valid attachment content.
      return null;                          //** no header can be generted. 

//** add the content type and file name of the attachment. 

    $header = "Content-Type: $this->ContentType;"; 

//** if an attachment then add the appropriate disposition and file name(s).
  
    if(!$this->HasLiteralContent())
    {
      $header .= " name=\"" . basename($this->FilePath) . "\"" . EmailNewLine .
                 "Content-Disposition: attachment; filename=\"" . 
                  basename($this->FilePath) . "\""; 
    }
    $header .= EmailNewLine;

//** add the key for the content encoding of the attachment body to follow. 

    $header .= "Content-Transfer-Encoding: base64" . EmailNewLine . 
                EmailNewLine; 

//** add the attachment data to the header. encode the binary data in BASE64 
//** and break the encoded data into the appropriate chunks. 

    $header .= chunk_split(base64_encode($attachmentData), 76, EmailNewLine) .
               EmailNewLine; 

    return $header;  //** return the headers generated by file. 
  } 
} 
//***************************************END_EMAIL_ATTACHMENT_CLASS_DEFINITION 
//**!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! 

###

$instance_qry = mysql_query("SELECT instance_id, name, admin_email, DATE(now() + INTERVAL (6 + timezone) HOUR) as processing_date, DATEDIFF(now() + INTERVAL (6 + timezone) HOUR, start_date)+1 as day, default_graze, include_weekends, DATEDIFF(end_date, start_date) as total_days FROM commons_instances WHERE now() + INTERVAL (6 +  timezone) HOUR BETWEEN start_date AND end_date AND HOUR(now() + INTERVAL (6 + timezone) HOUR)=0");

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
        while ($row = mysql_fetch_array($qry)) { $current_global_health = $row['global_health']; }

	$default_cow_health=$current_global_health;  #was 100 previously

	// deal with blank users
	echo "Dealing with blank orders ...\n";
	$qry = mysql_query("SELECT cu.user_id FROM commons_users cu LEFT JOIN commons_grazing_orders cgo ON(cgo.user_id=cu.user_id AND cgo.ts='$order_date') WHERE instance_id=$instance_id AND cgo.user_id IS NULL AND approved=1");
	while ($row = mysql_fetch_array($qry)) {
		$user_id=$row[0];
echo "filling in order for user_id = $user_id\n";
		if ($default_graze) {
			$lastorder_qry = mysql_query("SELECT cows FROM commons_grazing_orders WHERE user_id=$user_id ORDER BY ts DESC LIMIT 1");
			if (mysql_num_rows($lastorder_qry)==0) {
				$lastorder=0;
			} else {
				while ($lastorder_row = mysql_fetch_array($lastorder_qry)) { $lastorder = $lastorder_row['cows']; }
			}
		} else {
			$lastorder = 0;
		}

		$insrt_qry = mysql_query("INSERT INTO commons_grazing_orders (user_id, ts, cows, self_set) VALUES ($user_id, '$order_date', $lastorder, 0)");
	}

	// deal with purchases and sales and production
	echo "Dealing with Purchases and Sales (and production)...\n";
	$user_qry = mysql_query("SELECT user_id FROM commons_users WHERE instance_id = $instance_id AND approved=1");
	while ($user_row = mysql_fetch_array($user_qry)) {
		$user_id = $user_row['user_id'];

		// current_order
		$qry = mysql_query("SELECT cows FROM commons_grazing_orders WHERE user_id=$user_id ORDER BY ts DESC LIMIT 1");
		while ($row = mysql_fetch_array($qry)) { $current_order = $row['cows']; }

		// previous_order
		$qry = mysql_query("SELECT cows FROM commons_grazing_orders WHERE user_id=$user_id ORDER BY ts DESC LIMIT 1,1");
		if (mysql_num_rows($qry)==0) {
			$last_order=0;
		} else {
			while ($row = mysql_fetch_array($qry)) { $last_order = $row['cows']; }
		}
		if ($last_order == '') { $last_order = 0; }

		// get current account balance
		$cash_qry = mysql_query("SELECT cash FROM commons_users_cash_summary WHERE user_id=$user_id ORDER BY ts DESC LIMIT 1");
		while ($cash_row = mysql_fetch_array($cash_qry)) { $old_balance = $cash_row['cash']; }

		if ($current_order == $last_order) {
			// cash stays the same
			$new_balance = $old_balance;
		} elseif ($current_order > $last_order) {
			// buy cows
			$diff = $current_order - $last_order;
			$cow_cost = 1000*$diff;
			$new_balance = $old_balance - $cow_cost;
			// create cows
			for ($i=1; $i<=$diff; $i++) {
				$cow_qry = mysql_query("INSERT INTO commons_cows (user_id, date_created, is_active) VALUES ($user_id, '$order_date', 1)");
				$cow_id = mysql_insert_id();
				$health_query = mysql_query("INSERT INTO commons_cows_current_health (cow_id, cow_health) VALUES ($cow_id, $default_cow_health)");
				$health_query2 = mysql_query("INSERT INTO commons_cows_health_history (cow_id, ts, cow_health) VALUES ($cow_id, '$order_date', $default_cow_health)");
				$production_query = mysql_query("INSERT INTO commons_cows_current_production (cow_id, cow_production) VALUES ($cow_id, $default_cow_health)");
				$production_query2 = mysql_query("INSERT INTO commons_cows_production_history (cow_id, ts, cow_production) VALUES ($cow_id, '$order_date', $default_cow_health)");
			}

			// record purchase
			$purchase_qry = mysql_query("INSERT INTO commons_users_cash_purchases (user_id, ts, cows, amt) VALUES ($user_id, '$order_date', $diff, $cow_cost)");
		} else {
			// sell cows;
			$diff = $last_order - $current_order;
			$sale_total=0;
			$sale_query = mysql_query("SELECT cow_id, cow_health, date_created FROM commons_cows JOIN commons_cows_current_health USING(cow_id) WHERE user_id=$user_id AND is_active=1 ORDER BY cow_health, cow_id LIMIT $diff");
			while ($sale_row = mysql_fetch_array($sale_query)) {
				$cow_id = $sale_row['cow_id'];
				$cow_health = $sale_row['cow_health'];
				$date_created = $sale_row['date_created'];

				$sale_price = 1000*($cow_health/100);
				$sale_total = $sale_total+$sale_price;
                                $new_balance = $old_balance + $sale_total;
				
				// inactivate this cow
				$update_qry = mysql_query("UPDATE commons_cows SET is_active=0, date_created='$date_created' WHERE cow_id=$cow_id");
			}

			// record sale
			$sale_qry = mysql_query("INSERT INTO commons_users_cash_sales (user_id, ts, cows, amt) VALUES ($user_id, '$order_date', $diff, $sale_total)");

		}
		echo "... user_id = $user_id - old_balance = $old_balance ; new_balance = $new_balance\n";

		//production
		$production_qry = mysql_query("INSERT INTO commons_users_cash_production SELECT $user_id, '$processing_date', COUNT(*), SUM(.2*cow_production), $price_per_liter*SUM(.2*cow_production) FROM commons_cows JOIN commons_cows_current_production USING(cow_id) WHERE user_id=$user_id AND is_active=1");

		$production_val_qry = mysql_query("SELECT amt FROM commons_users_cash_production WHERE user_id=$user_id AND ts='$processing_date'");
		while ($production_val_row = mysql_fetch_array($production_val_qry)) {
			$production_amt = $production_val_row['amt'];
		}
		$new_balance = $new_balance + $production_amt;
		echo "... with production added - old_balance = $old_balance ; new_balance = $new_balance\n";
	
		$insrt_qry = mysql_query("INSERT INTO commons_users_cash_summary (user_id, ts, cash) VALUES ($user_id, '$processing_date', '$new_balance')");
	}

	// send report to admin
	$qry = mysql_query("SELECT COUNT(*) as users, SUM(cows) as total_cows FROM commons_grazing_orders cgo JOIN commons_users cu USING(user_id) WHERE instance_id=$instance_id AND DATE(cgo.ts) = '$order_date'");
	while ($row = mysql_fetch_array($qry)) {
		$users = $row[users];
		$total_cows = $row[total_cows];
		$capacity = 25*$users;
	}

	$Sender = 'Cow Commons Admin <info@grazingcows.org>';
	$Recipient = $admin_email;

$textVersion = "Dear Administrator,

This is an automated report on the state of the $name commons.

Orders for tomorrow have just been processed. $users farmers will be grazing $total_cows cows. Capacity for this commons is $capacity cows.

To see (and adjust) the effects on health go the admin link: http://grazingcows.org/admin.
";

$htmlVersion = "<p>Dear Administrator,</p>

<p>This is an automated report on the state of the $name commons.</p>

<p>Orders for tomorrow have just been processed. <b>$users</b> farmers will be grazing $total_cows cows. Capacity for this commons is $capacity cows.</p>

<p>To see (and adjust) the effects on health go the admin link: <a href='http://grazingcows.org/admin'>http://grazingcows.org/admin</a></p>
";

$msg = new Email($Recipient, $Sender, "Commons Report for $name - day $day");
$msg->SetMultipartAlternative($textVersion, $htmlVersion);
$SendSuccess = $msg->Send();
	

}
?>
