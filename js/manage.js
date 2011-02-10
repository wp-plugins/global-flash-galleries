var selectedImagesCount = 0;

jQuery('.flgallery .galleries-list .gallery .add .submit')
	.css({ opacity:0.5 })
	.attr('disabled', 'disabled');

jQuery(document).ready(function($) {

	flgallery.pictureControls = new Array();

	var showPictures = flgallery.showPictures = function(galleryID)
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

				if (flgallery.pictureControls[galleryID] == undefined) {
					flgallery.pictureControls[galleryID] = true;

					$('#'+ galleryID +' .add .inFile').change(function() {
						var submit = '#'+this.parentNode.parentNode.id+' .submit';
						$(submit)
							.css({ opacity:1 })
							.removeAttr('disabled');

						$(submit).attr('title', 'Start Upload');
					});

					$('#'+ galleryID +' .uploadImage label')
						.css({ height:'100px' })
						.attr('title', 'Select Pictures')
						.click(function() {
							$('#select-images-form').css({ display:'none'} );
							$('#selectPictures')
								.dialog('open')
								.css({ background: 'url('+flgallery.pluginURL+'/img/loader.gif) no-repeat center' });

							$.ajax({
								url: flgallery.adminAjax,
								type: 'GET',
								data: {
									action: 'flgalleryAdmin',
									ajax_action: 'selectPictures',
									gallery_id: this.htmlFor.match(/(\d+)/)[1],
									order: $(this.parentNode.parentNode.parentNode).hasClass('before') ? 'before' : 'after'
								},
								success: function(data) {
									$('#selectPictures')
										.html(data)
										.css({ background: 'none' });
								}
							});

							return false;
						});

					$('#'+ galleryID +' .items .image .menu .delete a').click(function() {
						if ( confirm('Delete Picture?') ) {
							var
								image_id = this.href.match(/image_id=(\d+)/)[1],
								gallery_id = this.href.match(/gallery_id=(\d+)/)[1];

							$('#image-'+image_id).fadeOut(500, function() {
								$(this).remove();
							});

							$.ajax({
								type: 'GET',
								url: flgallery.adminAjax,
								data: {
									action: 'flgalleryAdmin',
									ajax_action: 'deleteImage',
									image_id: image_id,
									gallery_id: gallery_id
								}
							});
						}
						return false;
					});

					$('#'+ galleryID +' .items').sortable({
						start: function(event, ui) {
							$('#'+ galleryID +' .items .image .menu a.button').hide();
							$('#'+ galleryID +' .items .image .menu ul').hide();
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
								success: function() {
								}
							});
						},
						cursor: 'move',
						revert: 200
					});
					//$('#'+ galleryID +' .items .image').css('cursor', 'move');

					//$('#'+ galleryID +' .items .image .title').hide();
					$('#'+ galleryID +' .items .image .menu ul').hide();

					$('#'+ galleryID +' .items .image').hover(
						function() {
							//$('#'+ galleryID +' .items .image .menu').hide();
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
					/*$('#'+ galleryID +' .items .image .menu').mouseout(
						function() {
							jQuery('#'+this.parentNode.parentNode.id+' .menu ul').fadeOut(300);
						}
					);*/
					$('#'+ galleryID +' .items .image .menu a.button').hide();
					$('#'+ galleryID +' .items .image .menu a.button').mouseover(
						function() {
							$('#'+this.parentNode.parentNode.parentNode.id+' .menu ul').show(500);
						}
					);
				}
			}
		);
	};

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


	$('.flgallery .publishDialog').dialog({
		title: 'Publish Gallery',
		autoOpen: false,
		modal: true,
		width: 400,
		resizable: false,
		buttons: {
			'OK': function() {
				$(this).dialog('close');
			}
		}
	});


	$('.flgallery .selectPictures').dialog({
		title: 'Select Pictures',
		autoOpen: false,
		modal: true,
		width: 800,
		height: 550,
		resizable: true,
		buttons: {
			'OK': function() {
				if (selectedImagesCount)
					$('#select-images-form').submit();

				$(this).dialog('close');
			}
		}
	});

});
