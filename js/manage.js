jQuery(document).ready(function($) {

	$('.flgallery .galleries-list .gallery .album .add .submit').attr('disabled', 'disabled');
	$('.flgallery .galleries-list .gallery .album .add .inFile').change(
		function() {
			$('.flgallery .galleries-list .gallery .album .add .submit').attr('disabled', 'disabled');
			var submit = '#'+this.parentNode.parentNode.id+' .submit';
			$(submit).removeAttr('disabled');
			$(submit).attr('title', 'Start Upload');
		}
	);

	function showPictures(galleryID)
	{
		$('.flgallery #'+ galleryID +' .show-pictures').animate({ fontSize:'32px', paddingTop:'70px', borderWidth:'2px', letterSpacing: '8px' }, 500);
		$('.flgallery #'+ galleryID +' .show-pictures a').css('border-width', '2px');

		if ( navigator.userAgent.match(/MSIE/i) )
			$('#'+ galleryID +' .full .album').css('visibility', 'hidden');
		else
			$('#'+ galleryID +' .full .album').fadeIn(1500);

		$('#'+ galleryID +' .full').slideDown(
			500,
			function() {
				$('#'+ galleryID +' .toggle').attr('title', 'Hide Pictures');
				$('#'+ galleryID +' .toggle span').css('background-position', 'top');
				$('#'+ galleryID +' .full .album').css('visibility', 'visible');
			}
		);
	}

	function hidePictures(galleryID)
	{
		$('.flgallery #'+ galleryID +' .show-pictures').animate({ fontSize:'18px', paddingTop:'0px', borderWidth:'1px', letterSpacing: '1px' }, 500);
		$('.flgallery #'+ galleryID +' .show-pictures a').css('border-width', '1px');

		$('#'+ galleryID +' .full').fadeOut(
			500,
			function() {
				$('#'+ galleryID +' .toggle').attr('title', 'Show Pictures');
				$('#'+ galleryID +' .toggle span').css('background-position', 'bottom');
			}
		);
	}
	$('.flgallery .show-pictures').css({ fontSize:'18px', paddingTop:'0px', borderWidth:'1px', letterSpacing: '1px' });

	function toggle(galleryID)
	{
		if ( $('#'+ galleryID + ' .full').css('display') == 'none' )
			showPictures(galleryID);
		else
			hidePictures(galleryID);
	}


	$(".flgallery .gallery .toggle").click(
		function() {
			toggle(this.parentNode.parentNode.parentNode.id);
			return false;
		}
	);

	$('.flgallery .gallery .show-pictures a').click(
		function() {
			toggle(this.parentNode.parentNode.parentNode.parentNode.id);
			return false;
		}
	);

	$(".flgallery .gallery .handlediv").click(
		function() {
			$('#'+this.parentNode.id+' .inside').slideToggle(500);
		}
	);
	$(".flgallery .gallery .hndle").click(
		function() {
			//$(".flgallery .gallery .inside").slideUp(500);
			$('#'+this.parentNode.id+' .inside').toggle();
		}
	);
	//if (flgallery.galleriesCount > 4)
		//$(".flgallery .gallery .inside").hide();

	$('.flgallery .gallery .album .items').sortable({
		start: function(event, ui) {
			$('.flgallery .gallery .album .items .image .menu a.button').hide();
			$('.flgallery .gallery .album .items .image .menu ul').hide();
		},
		stop: function(event, ui) {
			$.ajax({
				type: 'POST',
				url: flgallery.adminAjax,
				data: {
					action: 'flgalleryAdmin',
					ajax_action: 'sortImages',
					gallery_id: $('.gallery:has(#'+ ui.item.attr('id')+ ')').attr('id').replace(/[^&\d]/g, ''),
					album_id: $('.album:has(#'+ ui.item.attr('id')+ ')').attr('id').replace(/[^&\d]/g, ''),
					images_order: $(this).sortable('serialize').replace(/[^&\d]/g, '')
				},
				success: function(res, status) {
				}
			});
		},
		cursor: 'move',
		revert: 200
	});
	//$(".flgallery .gallery .album .items .image").css('cursor', 'move');

	//$('.flgallery .gallery .album .items .image .title').hide();
	$('.flgallery .gallery .album .items .image .menu ul').hide();

	$('.flgallery .gallery .album .items .image').hover(
		function() {
			//$('.flgallery .gallery .album .items .image .menu').hide();
			//$('#'+this.parentNode.id+' .title').show();
			//$('#'+this.parentNode.id+' .menu').show();
			$('#'+this.parentNode.id+' .menu a.button').fadeIn(700);
		},
		function() {
			//$('#'+this.parentNode.id+' .title').hide();
			$('#'+this.parentNode.id+' .menu a.button').hide();
			//$('#'+this.parentNode.id+' .menu ul').hide(333);
		}
	);
	/*$('.flgallery .gallery .album .items .image .menu').mouseout(
		function() {
			jQuery('#'+this.parentNode.parentNode.id+' .menu ul').fadeOut(300);
		}
	);*/
	$('.flgallery .gallery .album .items .image .menu a.button').hide();
	$('.flgallery .gallery .album .items .image .menu a.button').mouseover(
		function() {
			$('#'+this.parentNode.parentNode.parentNode.id+' .menu ul').show(500);
		}
	);

	$('.flgallery .publishDialog').dialog({
		title: 'Publish Gallery',
		autoOpen: false,
		modal: true,
		width: 400,
		resizable: false,
		buttons: {
			"OK": function() {
				$(this).dialog("close");
			}
		}
	});

	//$('a.fullsize').imageZoom({ speed:500 });

});
