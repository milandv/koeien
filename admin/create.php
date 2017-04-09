<?PHP
	require('../cgi-bin/connect.inc');
	include('../functions.php');
	
	if ($_POST["submit"]) {
		$name=$_POST["name"];
		$start_date=$_POST["start_date"];
		$end_date=$_POST["end_date"];
		$admin_email=$_POST["admin_email"];
		$timezone=$_POST["timezone"];
		$weekend=$_POST["weekend"];
		$graze=$_POST["graze"];

		$errors=0;
		if ($name=='') {
			$name_error=1;
			$errors=1;
		}
		if ($start_date=='') {
			$start_date_error=1;
			$errors=1;
		}
		if ($end_date=='') {
			$end_date_error=1;
			$errors=1;
		}
		if ($admin_email=='') {
			$admin_email_error=1;
			$errors=1;
		}
		if ($timezone=='') {
			$timezone_error=1;
			$errors=1;
		}
		if ($weekend=='') {
			$weekend=0;
		}
		if ($graze=='') {
			$graze=1;
		}

		if ($errors==0) {
			$hash = random_gen(8);
			$qry = mysql_query("INSERT INTO commons_instances (hash, name, timezone, start_date, admin_email, end_date, include_weekends, default_graze) VALUES ('$hash', '$name', $timezone, '$start_date', '$admin_email', '$end_date', $weekend, $graze)");
			$instance_id = mysql_insert_id();

			$qry = mysql_query("INSERT INTO commons_global_stats (instance_id, ts, commons_size, global_health, self_set) VALUES ($instance_id, '$start_date', 0, 100, 0)");
			header("Location: index.php");
		}

	} else {
		$weekend=0;
		$graze=1;
		$timezone='';
	}
?> 

<html>
 <head>


        <link rel="stylesheet" href="../css/jquery-ui-1.8.18.custom.css">
        <script src="../js/jquery-1.7.1.min.js"></script>
        <script src="../js/jquery-ui-1.8.18.custom.min.js"></script>
        <link rel="stylesheet" href="http://jqueryui.com/demos/demos.css">

        <script type="text/javascript">
        $(document).ready(function(){
		$ ("#datepicker_start" ).datepicker();
		$( "#datepicker_start" ).datepicker( "option", "dateFormat", 'yy-mm-dd' );
		$ ("#datepicker_end" ).datepicker();
		$( "#datepicker_end" ).datepicker( "option", "dateFormat", 'yy-mm-dd' );
        });
        </script>

	<style type="text/css">
		.error {color: red}
	</style>

 </head>

 <body>
<h1>Create a new Commons</h1>

<form action="" method="POST">

<table border="0">
 <tr>
  <td><label for="name" <?PHP if ($name_error) {echo "class='error'";} ?> >Commons Name</label></td>
  <td><input name="name" id="name" value="<?PHP echo $name ?>"></td>
 </tr>
 <tr>
  <td><label for="start_date" <?PHP if ($start_date_error) {echo "class='error'";} ?>>Start Date</label></td>
  <td><input type="text" id="datepicker_start" name="start_date" value=<?PHP echo $start_date ?>></td>
 </tr>
 <tr>
  <td><label for="end_date" <?PHP if ($end_date_error) {echo "class='error'";} ?>>End Date</label></td>
  <td><input type="text" id="datepicker_end" name="end_date" value=<?PHP echo $end_date ?>></td>
 </tr>
 <tr>
  <td><label for="admin_email" <?PHP if ($admin_email_error) {echo "class='error'";} ?> >Administrator's Email</label></td>
  <td><input name="admin_email" id="admin_email" value="<?PHP echo $admin_email ?>"> <i>(Separate multiple emails with commas)</i></td>
 </tr>
 <tr>
  <td><label for="timezone" <?PHP if ($timezone_error) {echo "class='error'";} ?>>Timezone</label></td>
  <td><select name="timezone">
	<option value="">Select ...</option>
	<?PHP 
		for ($i=0; $i<24; $i++) {
			$hr = -11+$i;
			echo "<option value='$hr'";
			if ($timezone==$hr) {
				echo "selected='selected'";
			}
			if ($i<11) {
				echo ">GMT$hr</option>";
			} else {
				echo ">GMT+$hr</option>";
			}
		}
	?>
      </select> <i>(Note: the server is east coast (GMT-6) so for PT use GMT-9 and for others adjust accordingly.)</i>
  </td>
 </tr>
 <tr>
  <td><label for="weekend" <?PHP if ($weekend_error) {echo "class='error'";} ?>>Graze on weekends</label></td>
  <td>
    <input type="radio" name="weekend" id="weekend" value="0" <?PHP if ($weekend==0) { echo "checked";} ?>> no
    <input type="radio" name="weekend" id="weekend" value="1" <?PHP if ($weekend==1) { echo "checked";} ?>> yes
  </td>
 </tr>
 <tr>
  <td><label for="graze" <?PHP if ($graze_error) {echo "class='error'";} ?>>Default graze</label></td>
  <td>
    <input type="radio" name="graze" id="graze" value="0" <?PHP if ($graze==0) { echo "checked";} ?>> no
    <input type="radio" name="graze" id="graze" value="1" <?PHP if ($graze==1) { echo "checked";} ?>> yes
  </td>
 </tr>

 <tr>
  <td colspan="2" align="center"><input type="submit" name="submit" id="submit" value="Create Commons"></td>
 </tr>
</table>

</form>

 </body>
</html>
