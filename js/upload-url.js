function addfromurl_cancel(e)
{
	jQuery(document).ready(function($) {
		$(e).fadeOut(500, function() {
			$(e).remove();
		});
	});
}

jQuery(document).ready(function($) {
	$('#addfromurl_items .nojs').remove();
	$('#addfromurl_items .item').prepend('<div class="ui-state-default ui-corner-all" style="position:absolute; z-index:1; right:3.5em; top:1em; padding:0;"><a class="ui-icon ui-icon-close" title="Cancel Upload" href="#" onclick="addfromurl_cancel(this.parentNode.parentNode); return false;"></a></div>');
	$('#flgalleryUpload').css('backgroundImage', 'none');
	$('#flgalleryUploadForm').fadeIn(500);
	$('#addfromurl .add .button').removeAttr('disabled');
});

var addfromurl_itemsCount = 1;

function addfromurl_addItem()
{
	jQuery(document).ready(function($) {
		addfromurl_itemsCount++;
		var item = $('#addfromurl_items #addfromurl_item-0').clone();
		item
			.attr( 'id', 'addfromurl_item-' + addfromurl_itemsCount )
			.html( item.html().replace(/-id-0/g, '-id-' + addfromurl_itemsCount) )
			.appendTo('#addfromurl_items')
			.show(500);
	});
}
