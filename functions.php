<?PHP
// Authorize session and connect to database for certain functions
require_once('auth.php');
require('cgi-bin/connect.inc');

$user_id = $_SESSION['SESS_ID'];

// Find out if user is instructor
$is_instructor = 0; // default

$sql = "SELECT
		is_instructor
	FROM
		commons_users
	WHERE
		user_id = " . $user_id;

$result = mysql_query($sql);

if(!$result) {
	echo 'Error verifying instructor status';
}

else {
	$row = mysql_fetch_assoc($result);
	$is_instructor = $row['is_instructor'];
}

// Call delete post if Ajax request has been received
if(isset($_POST['pid'])) {
    deletePost($_POST['pid']);
}

// Begin functions

function deletePost($pid) {
	// Deletes post #pid if it was posted by the deleting user, or if the deleting user is set as an instructor
	
	global $user_id;
	global $is_instructor;
	
	$sql = "DELETE FROM
		posts
	WHERE
		post_id = " . $pid;
	
	if(!$is_instructor) {
		$sql .= " AND post_author = " . $user_id;
	}
	
	$result = mysql_query($sql);
	
	//handle error TODO
	if(!$result) {
		echo 'Error deleting post';
	}
	else {
		if(mysql_affected_rows() > 0) {
			echo 'Post #' . $pid . ' successfully deleted';
		}
		else {
			echo 'Access denied or post does not exist';
		}
	}
}

function random_gen($length)
{
  $random= "";
  srand((double)microtime()*1000000);
  $char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $char_list .= "abcdefghijklmnopqrstuvwxyz";
  $char_list .= "1234567890";
  // Add the special characters to $char_list if needed

  for($i = 0; $i < $length; $i++)
  {
	$random .= substr($char_list,(rand()%(strlen($char_list))), 1);
  }
  return $random;
}
?>
