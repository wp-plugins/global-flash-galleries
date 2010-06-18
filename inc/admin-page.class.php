<?php  if (defined('WP_ADMIN')) {

class flgalleryAdminPage extends flgalleryBaseClass
{
	var $className = 'flgalleryAdminPage';
	var $galleriesCount = 0;

	var $href = FLGALLERY_HREF;

	function head($name, $class = '')
	{
		include FLGALLERY_GLOBALS;

		if ( !empty($_REQUEST) )
			$this->debug('REQUEST: '.htmlspecialchars(var_export($_REQUEST, true)));

		echo "\n\n<!-- begin {$plugin->name} -->\n";
		echo "<link rel='stylesheet' type='text/css' href='{$plugin->url}/css/admin.css' />\n";
		echo
			'<div class="wrap">' .
			( !empty($class) ? "<div class='{$plugin->name}'><div class='{$class}'>" : '' ).
			"\n\n<h2>". __($name, $plugin->name) . "</h2>\n\n";
;echo '		<script type="text/javascript">//<![CDATA[
			var flgallery = {
				pluginURL : \''; echo $plugin->url; ;echo '\',
				adminAjax : \''; echo admin_url('admin-ajax.php'); ;echo '\'
			};
		//]]>
		</script>
';
	}

	function actionButton($title, $action, $a = array(), $confirm = NULL, $attributes = array())
	{
		include FLGALLERY_GLOBALS;

		$primary = '';
		if ( is_array($title) )
		{
			if ( !empty($title[2]) || $title[1] === true )
				$primary = '-primary button';

			if ( !empty($title[1]) && is_string($title[1]) )
				$description = $title[1];

			$title = $title[0];
		}
		$title = __($title, $plugin->name);

		$out =
			"\n<form class='actionButton' id='action-{$action}' action='{$admpage->href}' method='post'>\n".
			"\t<input type='hidden' name='action' value='{$action}' />\n";
		if ( !empty($a) )
		{
			if ( is_object($a) )
				$a = get_object_vars($a);

			if ( is_array($a) )
			{
				foreach ($a as $name => $value)
				{
					$out .= "\t<input type='hidden' name='{$name}' value='{$value}' />\n";
				}
			}
		}
		$atts = '';
		if ( is_array($attributes) && count($attributes) )
		{
			foreach ($attributes as $name => $value)
				$atts .= " {$name}='{$value}'";
		}
		$out .=
			"\t<input type='submit' class='button{$primary}' id='button-{$action}' value='{$title}'" .
			(!empty($description) ? " title='{$description}'" : '') .
			$atts .
			(!empty($confirm) ? " onclick='return confirm(\"".str_replace('"', '\"' , $confirm)."\");'" : '') .
			" />\n" .
			"</form>\n";

		return $out;
	}

	function about()
	{
		include FLGALLERY_GLOBALS;

		$tpl->t('about/plugin-info',
			array(
				'version' => $plugin->version,
				'pluginDir' => $plugin->dir,
				'pluginURL' => $plugin->url,
				'contentDir' => $plugin->contentDir,
				'contentURL' => $plugin->contentURL,
				'imgDir' => $plugin->imgDir,
				'imgURL' => $plugin->imgURL,
				'uninstall' => $this->actionButton('Uninstall Plugin', 'uninstallPlugin', NULL, 'All plugin data, galleries, images and other media \nwill be PERMANENTLY REMOVED from server!\n\nAre you sure?'),
				'isAdmin' => ($plugin->userLevel >= 10)
			)
		);

		include FLGALLERY_INCLUDE.'/server-settings.php';
;echo '		<div class="metabox-holder"><div class="postbox-container" style="width:60%;">
			<div class="postbox" id="server-settings">
			<h3 class="hndle"><span>'; _e('Server Settings', $plugin->name); ;echo '</span></h3>
			<div class="inside">
				<!--<p class="sub"></p>-->
				<table class="table" cellspacing="0" border="0">
					<tbody>
						<tr>
							<th>'; _e('Operating System', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo PHP_OS; ;echo '</td>
						</tr>
						<tr class="even">
							<th>'; _e('Server', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $_SERVER["SERVER_SOFTWARE"]; ;echo '</td>
						</tr>
						<tr>
							<th>'; _e('PHP Version', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $php_version; ;echo '</td>
						</tr>
						<tr class="even">
							<th>'; _e('MySQL Version', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $sqlversion; ;echo '</td>
						</tr>
						<tr>
							<th style="vertical-align:top;">'; _e('SQL Mode', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $sql_mode; ;echo '</td>
						</tr>
						<tr class="even">
							<th>'; _e('PHP Safe Mode', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $safe_mode; ;echo '</td>
						</tr>
						<tr>
							<th>'; _e('PHP Allow URL fopen', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $allow_url_fopen; ;echo '</td>
						</tr>
						<tr class="even">
							<th>'; _e('Memory Usage', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $memory_usage; ;echo '</td>
						</tr>
						<tr>
							<th>'; _e('PHP Memory Limit', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $memory_limit; ;echo '</td>
						</tr>
						<tr class="even">
							<th>'; _e('PHP Max Upload Size', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $upload_max; ;echo '</td>
						</tr>
						<tr>
							<th>'; _e('PHP Max Post Size', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $post_max; ;echo '</td>
						</tr>
						<tr class="even">
							<th>'; _e('PHP Max Script Execute Time', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $max_execute; ;echo 's</td>
						</tr>
						<!-- <tr>
							<th>'; _e('PHP Exif support', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $exif; ;echo '</td>
						</tr>
						<tr class="even">
							<th>'; _e('PHP IPTC support', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $iptc; ;echo '</td>
						</tr> -->
						<tr>
							<th>'; _e('PHP XML support', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $xml; ;echo '</td>
						</tr>
						<tr class="even">
							<th>'; _e('GD Library', $plugin->name); ;echo '<span>:</span></th>
							<td>'; echo $gd_version; ;echo '</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div></div></div>
		<div class="clear"></div>
';
	}

	function manageGalleries()
	{
		include FLGALLERY_GLOBALS;

		$this->head('Galleries', 'manage');

		
		$tpl->t(
			'manage/galleries-panel',
			array(
				'addNewGallery' => $admpage->actionButton( array('+ New Gallery', true), 'addNewGallery' )
			)
		);

		
		$this->galleriesList();

		
		$func->js("flgallery.galleriesCount = {$this->galleriesCount};");
	}

	function newGallery($a = array(), $data = array())
	{
		include FLGALLERY_GLOBALS;
		$galleryInfo = &$plugin->galleryInfo;

		if ( empty($a['name']) )
			$a['name'] = 'New Gallery';

		if ( empty($a['type']) )
			$a['type'] = 'default';
;echo '		<form id="newGalleryForm" action="" method="post">
';
			if ( !empty($data) && is_array($data) )
			{
				foreach ($data as $name => $value)
					echo "<input type='hidden' name='{$name}' value='{$value}' />\n";
			}
;echo '		<table width="100%">
			<tr class="name field" valign="top">
				<td class="label"><label for="galleryName">'; _e('Name', $plugin->name); ;echo '</label></td>
				<td class="value" width="100%"><input id="galleryName" name="gallery[name]" value="'; echo $a['name']; ;echo '" tabindex="10" /></td>
			</tr>
			<tr class="type field" valign="top">
				<td class="label"><label for="galleryType">'; _e('Type', $plugin->name); ;echo '</label></td>
				<td class="value">
					<div><select id="galleryType" name="gallery[type]" tabindex="20">
';
						foreach ($galleryInfo as $type => $gallery)
						{
							if ($type == $a['type'])
								$atts = " selected='selected'";

							echo "<option value='{$type}'{$atts}>{$gallery['title']}</option>\n";
						}
					;echo '</select></div>
					<div class="navigation" style="visibility:hidden;">
						<input type="button" class="button" value="&laquo; Back" tabindex="50" />
						<input type="button" class="button" value="Next &raquo;" tabindex="40" />
					</div>
					<div class="preview"><a href="#" title="'; _e('Online Demo', $plugin->name); ;echo '" target="_blank"><img src="" alt="" /></a></div>
					<div class="description">&nbsp;</div>
				</td>
			</tr>
			<tr class="submit">
				<td></td>
				<td>
					<input type="hidden" name="action" value="createGallery" />
					<input type="submit" class="button-primary" name="OK" value="'; _e('Create Gallery', $plugin->name); ;echo '" tabindex="30" />
					<input type="submit" class="button" name="cancel" value="'; _e('Cancel', $plugin->name); ;echo '" tabindex="60" />
				</td>
			</tr>
		</table>
		</form>
		<script type="text/javascript">//<![CDATA[
			var galleryInfo = {
';
				foreach ($galleryInfo as $key => $info)
				{
					echo
						"\t\t'{$key}': {\n".
						"			title: '{$info['title']}',\n".
						"			description: '{$info['description']}',\n".
						"			preview: '{$info['preview']}',\n".
						"			demo: '{$info['demo']}'\n".
						"\t\t},\n";
				}
;echo '				none : \'\'
			};
			jQuery(document).ready(function($) {
				$(\'#galleryType\').change(
					function() {
						$(\'#newGalleryForm .preview img\').attr(\'src\', \''; echo $plugin->url; ;echo '/img/galleries/\' + unescape(galleryInfo[this.value].preview));
						$(\'#newGalleryForm .preview a\').attr(\'href\', unescape(galleryInfo[this.value].demo));
						$(\'#newGalleryForm .description\').html(galleryInfo[this.value].description);
					}
				);
				$(\'#galleryType\').change();
				$(\'#galleryName\').focus();
			});
		//]]>
		</script>
';
	}

	function galleriesList()
	{
		include FLGALLERY_GLOBALS;

		$gallery_id = empty($_REQUEST['gallery_id']) ? NULL : (int)$_REQUEST['gallery_id'];
		$showImgs = empty($_REQUEST['imgs']) ? 0 : (int)$_REQUEST['imgs'];

		$galleries = $wpdb->get_results("
			SELECT `id`, `author`
			FROM `{$plugin->dbGalleries}`
			WHERE
				{$plugin->userLevel} >= 5 OR
				`author` = '{$plugin->userID}'
			ORDER BY `order` DESC, `created` DESC
		");
		if ( !empty($galleries) )
		{
			$galleriesHTML = '';
			foreach ($galleries as $gal)
			{
				$this->galleriesCount++;
				$gallery = new flgalleryGallery($gal->id);
				$galleriesHTML .= $this->galleryPreview($gallery, 'manage/gallery', false);
			}
			$tpl->t( 'manage/galleries-list', array('galleries' => $galleriesHTML) );
		}
;echo '
		<script type="text/javascript" src="'; echo $plugin->url; ;echo '/js/manage.js"></script>
';
		if ($gallery_id && $showImgs)
			$func->js("jQuery(document).ready(function($) {
				flgallery.showPictures('gallery-{$gallery_id}');
			});");
	}

	function galleryPreview(&$gallery, $template, $echo = true)
	{
		if ( defined('WP_ADMIN') && $gallery->id )
		{
			include FLGALLERY_GLOBALS;
			$out = '';

			$imagesData = '';

			$images = $gallery->get_items();
			if ($images !== false)
			{
				foreach ($images as $img)
				{
					$image = new flgalleryImage($img);

					$img->galleryID = $gallery->id;
					$img->url = $plugin->imgURL .'/'. $img->path;
					$thumbnail = $image->resized(array( 'height' => 120 ));
					$img->previewURL = $thumbnail ? $func->url($thumbnail) : $img->url;
					$img->href = $admpage->href;
					if ( empty($img->title) )
						$img->title = $func->filenameToTitle($img->name);

					$img->title = htmlspecialchars(stripslashes( $img->title ));
					$img->description = htmlspecialchars(stripslashes( $img->description ));

					$imagesData .= $tpl->parse('manage/image-preview', $img);
				}
			}

			$created = sprintf( __("%s @ %s", $plugin->name),
				mysql2date( $plugin->dateFormat, get_date_from_gmt($gallery->created) ),
				mysql2date( $plugin->timeFormat, get_date_from_gmt($gallery->created) )
			);
			$modified = sprintf( __("%s @ %s", $plugin->name),
				mysql2date( $plugin->dateFormat, get_date_from_gmt($gallery->modified) ),
				mysql2date( $plugin->timeFormat, get_date_from_gmt($gallery->modified) )
			);

			$out .= $tpl->parse(
				$template,
				array_merge(
					get_object_vars($gallery),
					array(
						'created' => $created,
						'modified' => $modified,
						'deleteGallery' => $admpage->actionButton(
							array('Delete', 'Delete Gallery'),
							'deleteGallery',
							array('gallery_id' => $gallery->id),
							sprintf(__('Delete Gallery? \n\n%s. "%s" by %s', $plugin->name), $gallery->id, $gallery->name, $gallery->authorName)
						),
						'options' => $admpage->actionButton(
							array('Options', 'Customize Flash Gallery'),
							'galleryOptions',
							array('gallery_id' => $gallery->id)
						),
						'pluginURL' => $plugin->url,
						'imgURL' => $plugin->imgURL,
						'href' => $admpage->href,
						'full' => !empty($_REQUEST['imgs']) && (int)$_REQUEST['gallery_id'] == $gallery->id ? 'full' : NULL,
						'images' => $imagesData,
						'galleryInfo' => $plugin->galleryInfo[$gallery->type],
						'popupJS' => $gallery->get_popupJS(),
						'hasItems' => count($images) > 0,
						'moreThanOneItem' => count($images) > 1
					)
				)
			);

			if ($echo) echo $out;
			return $out;
		}
		return false;
	}

	function galleryOptions( $gallery, $options = array() )
	{
		include FLGALLERY_GLOBALS;

		if ( $gallery->get_settings() )
		{
			$types = '';
			foreach ($plugin->galleryInfo as $key => $value)
			{
				$selected = $key == $gallery->type ? " selected='selected'" : '';
				$types .= "<option value='{$key}'{$selected}>{$value['title']}</option>\n";
			}

			$images = $wpdb->get_results("
				SELECT `id`
				FROM `{$plugin->dbImages}`
				WHERE `gallery_id` = '{$gallery->id}'
			");
			if ( count($images) )
				$flash = $plugin->flashGallery( array('id' => $gallery->id) );
			else
				$flash = '';

			$swfURL = $gallery->get_swf();
			if ( !preg_match('#/swf-commercial/'.preg_quote($gallery->type).'.swf$#', $swfURL) )
			{
				$trialNotice = sprintf(
					_('Order the full version of %s to remove the copyright banner and make it possible to display more than %d&nbsp;pictures.', $plugin->name),
					'<a href="http://flash-gallery.com/wordpress-plugin/order/" target="_blank">'.
						$plugin->galleryInfo[$gallery->type]['title'].
					'</a>',
					$plugin->limitations[$gallery->type]
				);
			}
			else
				$trialNotice = '';

			$a = array_merge(
				array(
					'flash' => $flash,
					'flash_id' => $plugin->name.'-'.$gallery->id,
					'gallery_id' => $gallery->id,
					'types' => $types,
					'name' => $gallery->name,
					'width' => $gallery->width,
					'width2' => $gallery->width + 32,
					'height' => $gallery->height,
					'settingsPanel' => $plugin->tpl->parse(
						"gallery-settings/{$gallery->type}",
						$gallery->settingsForm
					),
					'trialNotice' => $trialNotice,
					'pluginURL' => $plugin->url
				),
				$options
			);
			$tpl->t('options/gallery', $a);
		}
		else
			return false;
	}

	function editImage($image_id)
	{
		include FLGALLERY_GLOBALS;

		$image = new flgalleryImage($image_id);
		$image->load();

		if ( $image->id )
		{
			$image->title = htmlspecialchars(stripslashes( $image->title ));
			$image->description = htmlspecialchars(stripslashes( $image->description ));
			$image->link = htmlspecialchars(stripslashes( $image->link ));
			$image->imgURL = $plugin->imgURL;
			$image->href = $admpage->href;
			$image->target_blank = empty($image->target) || $image->target == '_blank';
			$image->target_self = $image->target == '_self';

			if ( empty($image->title) )
				$image->title = $func->filenameToTitle($image->name);

			$tpl->t('manage/image-edit', $image);

			return $image->id;
		}
		return false;
	}

	function tabmenu($items, $current = '', $query = '')
	{
		include FLGALLERY_GLOBALS;

		echo "<ul class='tab-menu'>\n";
		foreach ($items as $name => $title)
		{
			$class = $name == $current ? 'selected' : '';
			echo "\t<li class='{$class}'><a href='{$admpage->href}{$query}&amp;tab={$name}'>{$title}</a></li>\n";
		}
		echo "</ul>\n";
	}

	function foot()
	{
		include FLGALLERY_GLOBALS;

		$plugin->stats->stop();
		$this->debug("{$plugin->stats->queries} queries, {$plugin->stats->time} seconds");

		echo "<div id='flgallery-info'>\n";
			if ($plugin->printErrors) $plugin->admin->printErrors();
			if ($plugin->printWarnings) $plugin->admin->printWarnings();
			if ($plugin->printDebug) $plugin->admin->printDebug();
		echo "\n</div>\n";

		echo "\n\n</div></div></div><!-- end {$plugin->name} -->\n\n";
	}

}

} 
?>