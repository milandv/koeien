<?php
	require('cgi-bin/connect.inc');

	$hash = $_GET['h'];

	$invoice_qry = mysql_query("SELECT invoice_id, cuto.ts, user_id, avg_herd, revenue, taxable_revenue, tax, remaining FROM commons_users_taxes_owed cuto JOIN commons_users cu USING(user_id) WHERE cu.hash='$hash' AND remaining>0");
	$hash_check = mysql_num_rows($invoice_qry);


        //Function to sanitize values received from the form. Prevents SQL injection
        function clean($str) {
                $str = @trim($str);
                if(get_magic_quotes_gpc()) {
                        $str = stripslashes($str);
                }
                return mysql_real_escape_string($str);
        }
	if ($_POST["submit"]) {
	        $amount = clean($_POST['amount']);
	        $totaldue = clean($_POST['totaldue']);

		$errors=0;
	        if (!(is_numeric($amount) && $amount>0 && $amount<= $totaldue)) {
	                $errmsg_arr[] = "Error: Payment amount must be between 0.01 and $totaldue";
			$errors=1;
	        }

		if ($errors==0) {
			$coh_qry = mysql_query("SELECT user_id, cucs.ts, cash FROM commons_users cu JOIN commons_users_cash_summary cucs USING(user_id) WHERE hash='$hash' ORDER BY cucs.ts DESC LIMIT 1");
			while ($coh_row = mysql_fetch_array($coh_qry)) {
				$coh_ts = $coh_row['ts'];
				$coh = $coh_row['cash'];
				$user_id = $coh_row['user_id'];
			}

			$payment_qry = mysql_query("SELECT invoice_id, cuto.ts, user_id, avg_herd, revenue, taxable_revenue, tax, remaining FROM commons_users_taxes_owed cuto JOIN commons_users cu USING(user_id) WHERE cu.hash='$hash' AND remaining>0");
			$payment_remaining=$amount;
			while ($row = mysql_fetch_array($payment_qry)) {
			  if ($payment_remaining>0) {
				$current_due = $row['remaining'];
				$current_invoice_id = $row['invoice_id'];
				$user_id = $row['user_id'];

				if ($payment_remaining < $current_due) {
					$new_due = $current_due - $payment_remaining;
					$paid = $payment_remaining;
					$payment_remaining = 0;
				} else {
					$new_due = 0;
					$paid = $current_due;
					$payment_remaining = $payment_remaining - $current_due;
				}	

				$update_qry = mysql_query("UPDATE commons_users_taxes_owed SET remaining=$new_due WHERE invoice_id=$current_invoice_id");
				$insert_qry = mysql_query("INSERT INTO commons_users_taxes_paid (user_id, invoice_id, ts, tax_paid) VALUES ($user_id, $current_invoice_id, DATE(now()), $paid)");
			  }
			}		
			$new_coh = $coh - $amount;
			$update_qry = mysql_query("UPDATE commons_users_cash_summary SET cash = $new_coh WHERE user_id=$user_id AND ts='$coh_ts'");
			$payment_success=1;
		}
	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Commons Revenue Service Payment Submission Center</title>
 <style>
        @import url(http://fonts.googleapis.com/css?family=Bowlby+One+SC);
        @import url(http://fonts.googleapis.com/css?family=Inika:400,700);

        body    {font-family:Inika}
        h1      {font-family:'Bowlby One SC'}
        #wrapper        {width:900px;
                        margin:auto}
        #top            {margin-bottom:4em}
        #narrow         {width:495px;
                        float:left}
        #wide           {width:395px;
                        float:right}
        .box            {padding:1ex;
                        border:1px solid black;
                        margin-bottom:2em;}
        .box h2         {margin-top:-1em;
                        margin-left:5px;
                        width:100px;
                        background:white;
                        text-align:center;}
        table           {width:60%}
        td       {text-align:center}

        #submit         {background:black;color:white;font-weight:bold;font-family:inerit;font-size:125%;border:5px double white;cursor: hand; cursor: pointer;}
        #submit:hover   {background:#888;}

        #pasture        {border:1px solid black;width:3em;font-family:inherit}

        form div                            { overflow: hidden; margin: 0 0 5px 0; }
        input[type=text]                    { width: 40px; font: bold 20px Helvetica, sans-serif; padding: 3px 0 0 0; text-align: center; }
        .button                             { margin: 0 0 0 5px; text-indent: -9999px; cursor: pointer; width: 29px; height: 29px; float: left; text-align: center; background: url(img/buttons.png) no-repeat; }
        .dec                                { background-position: 0 -29px; }

        #order_submitted        {color: red;}
        #order_updated  {color: red; text-align: center;}

        .tweet {border: solid 1px black; padding: 10px;}
        .tweetimg {float: left; margin-right: 20px;}
 </style>


</head>
<body>
<?PHP
                foreach($errmsg_arr as $msg) {
                        echo '<p style="color: red" align="center">',$msg,'</p>';
                }
?>

<?PHP if ($payment_success) { ?>

<p style="color: red" align="center">Thank you! Your payment has been submitted.</p>

<?PHP } ?>


<?PHP if ($hash_check==0) { ?>

<center>
<p style="font-size: 3em;">You have no taxes due at this time. Have a nice day.</p>
</center>

<?PHP } else { ?>

<center>
<h3>Your Tax Invoices:</h3>
</center>

<table width="500" border="0" align="center" cellpadding="2" cellspacing="0">
<tr>
 <th>invoice number</th>
 <th>date</th>
 <th>avg herd size</th>
 <th>total revenue</th>
 <th>taxable revenue</th>
 <th>tax rate</th>
 <th>tax amount</th>
 <th>amount due</th>
 <th>status</th>
</tr>

<?PHP

$total_due=0;

$coh_qry = mysql_query("SELECT user_id, cucs.ts, cash FROM commons_users cu JOIN commons_users_cash_summary cucs USING(user_id) WHERE hash='$hash' ORDER BY cucs.ts DESC LIMIT 1");
while ($coh_row = mysql_fetch_array($coh_qry)) {
	$coh_ts = $coh_row['ts'];
	$coh = $coh_row['cash'];
}

$invoice_qry = mysql_query("SELECT invoice_id, cuto.ts, cuto.ts + INTERVAL 2 DAY as due_date, CASE WHEN cuto.ts<DATE(NOW())-INTERVAL 2 DAY THEN 1 ELSE 0 END as past_due, user_id, avg_herd, revenue, taxable_revenue, tax, remaining FROM commons_users_taxes_owed cuto JOIN commons_users cu USING(user_id) WHERE cu.hash='$hash'");
while ($row = mysql_fetch_array($invoice_qry)) {
	$invoice_id = $row['invoice_id'];
	$ts = $row['ts'];
	$due_date = $row['due_date'];
	$avg_herd = $row['avg_herd'];
	$revenue = number_format($row['revenue'],2);
	$taxable_revenue = number_format($row['taxable_revenue'],2);
	$tax = number_format($row['tax'],2);
	$remaining = number_format($row['remaining'],2);
	$total_due = $total_due + $remaining;

	if ($avg_herd>20 && $avg_herd<=50) {
		$tax_rate='5%';
	} elseif ($avg_herd>50 && $avg_herd<=100) {
		$tax_rate='10%';
	} else {
		$tax_rate='15%';
	}

	if ($remaining=='0.00') {
		$style="style='color:#aaa;'";
	} else {
		$style="style='color:#000;'";
	}

	if ($remaining==0) {
		$status = 'paid';
	} elseif ($row['past_due']==1) {
		$status = 'past due';
	} else {
		$status = "due $due_date";
	}

echo "
<tr $style>
 <td>$invoice_id</td>
 <td>$ts</td>
 <td>$avg_herd</td>
 <td>$revenue</td>
 <td>$taxable_revenue</td>
 <td>$tax_rate</td>
 <td>$tax</td>
 <td>$remaining</td>
 <td>$status</td>
</tr>
";
}
?>

<tr>
 <td><br /><b>total due:</b></td>
 <td></td>
 <td></td>
 <td></td>
 <td></td>
 <td></td>
 <td></td>
 <td><br /><b><?php echo "$total_due"; ?></b></td>
</tr>

<tr>
 <td colspan="8"><span style="font-size:0.7em"><i>Note: all past due invoices have a 7.6% finance fee applied to them per tax period</i></td>
</tr>
</table>



<form id="payTaxes" name="payTaxes" method="post" action="mytaxes.php?h=<?PHP echo $hash ?>" style="margin-top:30px;">
<input type="hidden" name="totaldue" value="<?php echo "$total_due"; ?>">
  <table width="200" border="0" align="center" cellpadding="2" cellspacing="0">
    <tr>
      <td><span style="color:#aaa;">Your total cash on hand: <?PHP echo number_format($coh,2); ?></span></td>
      <td><b>Payment Amount:</b> &nbsp;<input type="text" name="amount"> &nbsp;&nbsp; <input type="submit" name="submit" value="Submit Payment" /></td>
    </tr>
  </table>
</form>


<?PHP } ?>
</body>
</html>
