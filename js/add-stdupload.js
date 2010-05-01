jQuery(document).ready(function($) {
	$('#stdUpload_items .nojs').remove();
	$('#stdUpload_items .item').prepend('<div class="ui-state-default ui-corner-all" style="position:absolute; z-index:1; right:3.5em; top:1em; padding:0;"><a class="ui-icon ui-icon-close" title="Cancel Upload" href="#" onclick="stdUpload_cancel(this.parentNode.parentNode); return false;"></a></div>');
	$('#flgalleryAddMedia').css('backgroundImage', 'none');
	$('#flgalleryAddMediaForm').fadeIn(500);
	$('#stdUpload .add .button').removeAttr('disabled');
	$('#stdUpload_items .item .file').change(function() {
		stdUpload_addItem();
	});
});


var stdUpload_itemsCount = 1;

function stdUpload_cancel(e)
{
	jQuery(document).ready(function($) {
		$(e).fadeOut(500, function() {
			$(e).remove();
		});
	});
}

function stdUpload_addItem()
{
	jQuery(document).ready(function($) {
		stdUpload_itemsCount++;
		var item = $('#stdUpload_items #stdUpload_item-0').clone();
		item
			.attr( 'id', 'stdUpload_item-' + stdUpload_itemsCount )
			.html( item.html().replace(/-id-0/g, '-id-' + stdUpload_itemsCount) )
			.appendTo('#stdUpload_items')
			.show(500);

		$('#stdUpload_items .item .file').change(function() {
			stdUpload_addItem();
		});
	});
}
