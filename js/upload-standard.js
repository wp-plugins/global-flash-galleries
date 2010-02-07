function stdupload_cancel(e)
{
	jQuery(document).ready(function($) {
		$(e).fadeOut(500, function() {
			$(e).remove();
		});
	});
}

jQuery(document).ready(function($) {
	$('#stdupload_items .nojs').remove();
	$('#stdupload_items .item').prepend('<div class="ui-state-default ui-corner-all" style="position:absolute; z-index:1; right:3.5em; top:1em; padding:0;"><a class="ui-icon ui-icon-close" title="Cancel Upload" href="#" onclick="stdupload_cancel(this.parentNode.parentNode); return false;"></a></div>');
	$('#flgalleryUpload').css('backgroundImage', 'none');
	$('#flgalleryUploadForm').fadeIn(500);
	$('#stdupload .add .button').removeAttr('disabled');
});

var stdupload_itemsCount = 1;

function stdupload_addItem()
{
	jQuery(document).ready(function($) {
		stdupload_itemsCount++;
		var item = $('#stdupload_items #stdupload_item-0').clone();
		item
			.attr( 'id', 'stdupload_item-' + stdupload_itemsCount )
			.html( item.html().replace(/-id-0/g, '-id-' + stdupload_itemsCount) )
			.appendTo('#stdupload_items')
			.show(500);
	});	
}
