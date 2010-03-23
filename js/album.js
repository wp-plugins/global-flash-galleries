jQuery(document).ready(function($) {

$('#album_pictures thead .check-column input, #album_pictures tfoot .check-column input').click( function() {
	$('#album_pictures .check-column input').attr('checked', this.checked);
});

/*
var albumDescriptionTimer = false;
var changeHeight = function (element, height, speed, delay) {
	if (albumDescriptionTimer != false)
		clearTimeout(albumDescriptionTimer);
	albumDescriptionTimer = setTimeout(
		function() {
			$(element).animate({ height:height }, speed);
		},
		delay
	);
};

$('#album_description').focus(function() {
	changeHeight(this, '10em', 500, 300);
});
$('#album_description').blur(function() {
	changeHeight(this, '5em', 1000, 5000);
});
*/
});