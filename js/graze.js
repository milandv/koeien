// graze.php script

// Add event handlers to each delete button
$('.delete-btn').click(function() {
	callDelete($(this).attr('pid'));
});

// Use Ajax to send delete request to functions.php
function callDelete(dpid) {
	jQuery.ajax({
		type: "POST",
		url: 'functions.php',
		data: {pid: dpid},

		success: function (output) {   
			console.log(output); 
		}
				
		});
	location.reload(false); // Refresh page to update bulletin
}