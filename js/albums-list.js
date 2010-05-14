jQuery(document).ready(function($) {

$('#button-addNewAlbum').click(function() {
	document.body.style.cursor = 'wait';

	$('div.flgallery')
		.fadeOut(500)
		.load(
			flgallery.adminAjax,
			{
				action: 'flgalleryAdmin',
				ajax_action: 'addNewAlbum'
			},
			function() {
				$(this)
					.hide()
					.fadeIn(500);
				jQuery('#album_title').focus();

				document.body.style.cursor = 'default';
			}
		);
	return false;
});

//$('.albums-list tbody').sortable({ cursor:'move' });

});
