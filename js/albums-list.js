jQuery(document).ready(function($) {

$('#button-addNewAlbum').click(function() {
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
			}
		);
	return false;
});

//$('.albums-list tbody').sortable({ cursor:'move' });

});
