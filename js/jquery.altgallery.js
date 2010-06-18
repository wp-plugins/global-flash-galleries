/**
 *	altgallery, jQuery Plugin
 *
 *	@version 0.0.1
 */
;(function($) {
	$.fn.altgallery = function(options)
	{
		options = $.extend({
			width: '550px',
			height: '400px'
		}, options);

		if ( options.width.match(/^\d+$/) )
			options.width += 'px';

		if ( options.height.match(/^\d+$/) )
			options.height += 'px';

		var
			matchedObject = this,
			gallery,
			galleryHTML,
			images = new Array(),
			thumbnails;

		function setThumbnailsClick() {
			thumbnails = gallery.children('div.altgallery-thumbnails');
			thumbnails.find('a').click(function() {
				openImage(this);
				return false;
			});
		}

		function init() {
			matchedObject.wrap('<div class="altgallery" />');
			gallery = matchedObject.parent('div.altgallery');

			var i = 0;
			gallery.find('a').each(function() {
				var img = $(this).find('img');
				var title;
				images[i] = {
					id:			i,
					source:		this.href,
					thumbnail:	img.attr('src'),
					alt:		img.attr('alt'),
					title:		(title = img.attr('title')) == undefined ? this.title : title
				};
				i++;
			});

			gallery.empty();

			gallery.css({
				position:	'relative',
				padding:	0,
				width:		options.width,
				height:		options.height,
				overflow:	'hidden',
				textAlign:	'center',
				border:		'1px solid #ddd',
			});

			gallery.append('<div class="altgallery-thumbnails" />');
			thumbnails = gallery.children('div.altgallery-thumbnails');
			thumbnails.css({
				height:		options.height,
				overflow:	'scroll',
				textAlign:	'left'
			});

			for (i in images) {
				var image = images[i];
				thumbnails.append('<a id="altgallery-image-'+image.id+'" href="'+image.source+'"><img alt="" src="'+image.thumbnail+'" border="0" style="vertical-align:middle; margin:2%; width:20%;" /></a>');
			}
			thumbnails.wrapInner('<div style="margin:1% 0 1% 1%;" />');

			setThumbnailsClick();
		}

		function openImage(element) {
			var
				image_id = element.id.match(/\d+/),
				image = images[image_id];

			galleryHTML = gallery.html();
			gallery
				.empty()
				.append('<a href="#"><img alt="" src="'+image.source+'" height="'+options.height+'" border="0" /></a>')
				.children('a').click(function() {
					gallery.html(galleryHTML);
					setThumbnailsClick();
					return false;
				});
		}

		init();
	};
})(jQuery);
