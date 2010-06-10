<?php  if (!defined('WP_ADMIN')) { header('HTTP/1.0 403 Forbidden'); exit('Access denied'); } ;echo '
<script type="text/javascript">//<![CDATA[
	jQuery(\'#selectPictures\').css({ background:\'none\' });
	jQuery(\'#select-images-form\').fadeIn(500);
//]]></script>

';

$albums = $wpdb->get_results("
	SELECT *
	FROM `{$plugin->dbAlbums}`
	ORDER BY `title` ASC
");

;echo '<form id="select-images-form" action="" method="post" style="display:none;">
<input type="hidden" name="action" value="addImages" />
<input type="hidden" name="gallery_id" value="'; echo $a['gallery_id']; ;echo '" />
<input type="hidden" name="order" value="'; echo $a['order']; ;echo '">

<div style="margin:1em 0 2em 0;"><a class="button" href="?page=flgallery/media&amp;action=addNewAlbum">+ New Album</a></div>

<ul id="select-albums">
';
	foreach ($albums as $album)
	{
		$images = $wpdb->get_results("
			SELECT *
			FROM `{$plugin->dbImages}`
			WHERE
				`album_id` = '{$album->id}' AND
				`gallery_id` = 0
			ORDER BY `order` ASC
		");
		if ( $imagesCount = count($images) )
		{
;echo '	<li id="select-album-'; echo $album->id; ;echo '" style="clear:left; margin:15px 0 20px;">
		<input class="select-album" type="checkbox" name="albums[]" value="'; echo $album->id; ;echo '" style="vertical-align:baseline;" />
		<span>
			<a class="select-album" href="#select-album-'; echo $album->id; ;echo '" style="font-size:18px; text-decoration:none; border-bottom:1px dotted; color:#21759b;">'; echo $album->title; ;echo '</a>
			<span class="album-count" style="font-size:14px; color:#555;">('; echo $imagesCount; ;echo ')</span>
			<small style="margin:0 0.5em;"><a href="?page=flgallery/media&amp;action=addMediaPage&amp;album_id='; echo $album->id; ;echo '" style="color:#777;">Add Pictures</a></small>
		</span>

		<ul id="select-album-'; echo $album->id; ;echo '-images" style="margin:5px 5px 10px; display:none;">';

			foreach ($images as $image)
			{
				$imageObject = new flgalleryImage($image->id);
				$imageObject->_set($image);

				$previewURL = $func->url( $imageObject->resized(array('height'=>64)) );
;echo '			<li style="float:left; width:150px; height:90px; overflow:hidden; margin:15px;">
				<label for="select-image-'; echo $image->id; ;echo '">
					<span style="display:block;">
						<img src="'; echo $previewURL; ;echo '" alt="'; echo $image->name; ;echo '" />
					</span>
					<span style="white-space:nowrap;">
						<input type="checkbox" class="select-image" id="select-image-'; echo $image->id; ;echo '" name="images[]" value="'; echo $image->id; ;echo '" />
						<small title="'; echo $image->name; ;echo '" style="font-size:10px;">'; echo $func->short_filename($image->name, 20, ''); ;echo '</small>
					</span>
				</label>
			</li>
';
			}
;echo '		</ul>
		<div style="clear:left;"></div>
	</li>
';
		}
	}
;echo '</ul>
<div id="selected-images-count" style="position:absolute; bottom:1.5em;"></div>
</form>

<script type="text/javascript">//<![CDATA[
jQuery(document).ready(function($) {

	function updateImagesCount() {
		selectedImagesCount = 0;
		$(\'#select-albums input.select-image[type=checkbox]\').each(function() {
			if (this.checked)
				selectedImagesCount++;
		});
		if (selectedImagesCount)
			$(\'#selected-images-count\').html(\'You have selected \'+ selectedImagesCount +\' picture\'+ (selectedImagesCount > 1 ? \'s.\' : \'.\'));
		else
			$(\'#selected-images-count\').html(\'You have not selected pictures.\');
	}

	$(\'#select-albums input.select-album[type=checkbox]\').click(function() {
		var checked = this.checked;
		$(\'#\' + $(this).parent().attr(\'id\') + \'-images input[type=checkbox]\').each(function() {
			this.checked = checked;
		});
		updateImagesCount();
	});

	$(\'#select-albums input.select-image[type=checkbox]\').click(function() {
		updateImagesCount();
	});

	$(\'#select-albums a.select-album\').toggle(
		function() {
			$(\'#\' + $(this).parent().parent().attr(\'id\') + \'-images\')
				.css({ display:\'block\' });

			return false;
		},
		function() {
			$(\'#\' + $(this).parent().parent().attr(\'id\') + \'-images\')
				.css({ display:\'none\' });

			return false;
		}
	);

});
//]]></script>
';
?>