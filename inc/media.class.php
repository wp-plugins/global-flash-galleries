<?php 

class flgalleryMedia extends flgalleryBaseClass
{
	function manage()
	{
		include FLGALLERY_GLOBALS;

		$action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
		if ( !empty($_REQUEST['doaction2']) )
			$action = empty($_REQUEST['action2']) ? '' : $_REQUEST['action2'];

		$album_id = empty($_REQUEST['album_id']) ? 0 : (int)$_REQUEST['album_id'];

		switch ($action)
		{


			case 'addNewAlbum':
				$admpage->head('Create', 'new-album');
				$plugin->tpl->t( 'media/new-album', array('href'=>$admpage->href) );
				break;

			case 'createAlbum':
				if ( !empty($_REQUEST['OK']) )
					$media->createAlbum($_REQUEST['album']);

				$func->locationReset('&tab=albums');
				$media->mainPage();
				break;

			case 'editAlbum':
				if ( !empty($album_id) )
					$media->editAlbum($album_id);
				else
					$media->mainPage('albums');
				break;

			case 'updateAlbum':
				if ( !empty($_REQUEST['update']) )
				{
					if ( !empty($album_id) )
					{
						$data = $_REQUEST['album'];
						$data['modified'] = $now = $func->now();
						$wpdb->update( $plugin->dbAlbums, $data, array('id' => $album_id) );
					}
					$func->locationReset('&action=editAlbum&album_id='.$album_id);
					$media->editAlbum($album_id);
				}
				else
					$media->mainPage('albums');
				break;

			case 'deleteAlbum':
				$media->deleteAlbum($album_id);
				$func->locationReset('&tab=albums');
				$media->mainPage();
				break;


			case 'editImage':
				$picture_id = (int)$_REQUEST['image_id'];
				$admpage->head('Picture Properties', 'image-properties');
				$admpage->editImage($picture_id);
				break;

			case 'saveImage':
				if ( !empty($_REQUEST['OK']) && !empty($_REQUEST['image']) )
				{
					$image_id = (int)$_REQUEST['image_id'];
					$admin->saveImage($image_id, $_REQUEST['image']);
				}
				$func->locationReset('&action=editAlbum&album_id='.$album_id);
				$media->editAlbum($album_id);
				break;

			case 'downloadImage':
				$func->locationReset('&action=editAlbum&album_id='.$album_id);
				$media->editAlbum($album_id);
				break;

			case 'deleteImages':
				if ( !empty($_REQUEST['image_id']) && is_array($_REQUEST['image_id']) )
				{
					foreach ($_REQUEST['image_id'] as $image_id)
						$media->deleteImage( (int)$image_id );
				}
				$func->locationReset('&action=editAlbum&album_id='.$album_id);
				$media->editAlbum($album_id);
				break;

			case 'deleteImage':
				if ( !empty($_REQUEST['image_id']) )
				{
					$image_id = (int)$_REQUEST['image_id'];
					$media->deleteImage($image_id);
				}
				$func->locationReset('&action=editAlbum&album_id='.$album_id);
				$media->editAlbum($album_id);
				break;


			case 'addMediaPage':
				$media->addMediaPage( array('album_id'=>$album_id) );
				break;

			case 'addMedia':
				if ( !empty($_REQUEST['OK']) )
				{
					if ( !empty($album_id) )
					{
						$media->addMedia( array('album_id' => $album_id) );
						$func->locationReset('&action=editAlbum&album_id='.$album_id);
						$media->editAlbum($album_id);
					}
				}
				else
				{
					if ( !empty($album_id) )
						$tab = 'albums';

					$func->locationReset('&tab='.$tab);
					$media->mainPage($tab);
				}
				break;


			case 'albumToGallery':
				$media->albumToGallery($album_id);
				break;

			case 'createGallery':
				if ( !empty($_REQUEST['OK']) )
				{
					$gallery_id = $admin->createGallery($_REQUEST['gallery']);

					if ( !empty($album_id) && $gallery_id )
					{
						$images = $wpdb->get_results("
							SELECT *
							FROM `{$plugin->dbImg}`
							WHERE
								`album_id` = '{$album_id}' AND
								`gallery_id` = '0'
							ORDER BY `order` ASC
						");
						if ($images !== false && count($images))
						{
							foreach ($images as $image)
							{
								unset($image->id);
								$image->gallery_id = $gallery_id;
								$image->album_id = $album_id;

								$wpdb->insert( $plugin->dbImg, get_object_vars($image) );
							}
						}
						$admpage->head('Gallery Options', 'gallery-options');
						$admpage->galleryOptions( new flgalleryGallery($gallery_id) );
					}
				}
				else
					$media->mainPage('albums');
				break;

			case 'changeGalleryOptions':
				if ( !empty($_REQUEST['OK']) || !empty($_REQUEST['update']) )
				{
					if ( !empty($_REQUEST['gallery']) )
					{
						$gallery = new flgalleryGallery($_REQUEST['gallery_id']);
						foreach ($_REQUEST['gallery'] as $key => $value)
						{
							if ( isset($gallery->$key) )
								$gallery->$key = $value;
						}
						$gallery->save();
					}
					if ( !empty($_REQUEST['settings']) )
					{
						$gallery->getSettings();
						foreach ($gallery->settingsInfo as $key => $param)
						{
							if ( isset($_REQUEST['settings'][$key]) )
								$gallery->settings[$key] = trim($_REQUEST['settings'][$key]);
							else
							{
								if ( (string)$param->input['type'] == 'checkbox' )
								{
									$values = explode( '|', (string)$param->input['value'] );
									$gallery->settings[$key] = trim($values[1]);
								}
							}
						}
						$gallery->saveSettings();
					}
					if ( !empty($_REQUEST['update']) )
					{
						$admpage->head('Gallery Options', 'gallery-options');
						$func->locationReset("&action=galleryOptions&gallery_id={$gallery->id}");
						$admpage->galleryOptions($gallery->id);
						break;
					}
				}
				if ( !empty($_REQUEST['resetOptions']) )
				{
					$admpage->head('Gallery Options', 'gallery-options');
					$func->locationReset("&action=galleryOptions&gallery_id={$gallery->id}");
					$admpage->galleryOptions($gallery->id);
					break;
				}
				$func->locationReset("&gallery_id={$gallery->id}#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			default:
				$media->mainPage();
				break;
		}

		$admpage->foot();
	}

	function mainPage($tab = false)
	{
		include FLGALLERY_GLOBALS;

		$admpage->head('Media', 'media');

		echo "<script type='text/javascript' src='{$plugin->jsURL}/albums-list.js'></script>\n";

		if (!$tab)
			$tab = empty($_REQUEST['tab']) ? 'albums' : $_REQUEST['tab'];

		$admpage->tabmenu(
			array(
				'albums' => 'Albums',
				
				
				
			),
			$tab
		);

		echo "<div class='tab-content'>\n";
		switch ($tab)
		{
			case 'albums':
				$media->albums();
				break;
		}
		echo "</div>\n";
	}

	function albums()
	{
		include FLGALLERY_GLOBALS;

		echo $admpage->actionButton(array('+ New Album', true), 'addNewAlbum');

		if ( empty($_REQUEST['orderBy']) )
		{
			$cookie = unserialize( base64_decode($plugin->userCookie) );
			$_REQUEST['orderBy'] = &$cookie['albumsList']['orderBy'];
			$_REQUEST['order'] = &$cookie['albumsList']['order'];
		}

		$orderDefault = 'title';

		$orderBy = empty($_REQUEST['orderBy']) ? $orderDefault : strtolower($_REQUEST['orderBy']);
		$order = empty($_REQUEST['order']) ? ($orderBy == 'modified' || $orderBy == 'size' ? 'desc' : 'asc') : strtolower($_REQUEST['order']);
		if ($order == 'desc')
		{
			$orderR = 'asc';
			$orderU = 'DESC';
		}
		else
		{
			$orderR = 'desc';
			$orderU = 'ASC';
		}

		$orderCols = array(
			'title' =>		'Title',
			'author' =>		'Author',
			'modified' =>	'Modified',
			'size' =>		'Size',
		);
		if ( !array_key_exists($orderBy, $orderCols) )
			$order = $orderDefault;

		$albums = $wpdb->get_results("
			SELECT
				a.`id`,
				a.`title`,
				a.`description`,
				a.`created`,
				a.`modified`,
				u.`display_name` as `author`,
				SUM(i.`size`) as `size`
			FROM
				`{$plugin->dbAlbums}` a LEFT JOIN
				`{$plugin->dbImg}` i ON
					a.`id` = i.`album_id` AND
					i.`gallery_id` = '0',
				`{$wpdb->users}` u
			WHERE
				a.`author` = u.`ID` AND
				(a.`author` = '{$plugin->userID}' OR {$plugin->userLevel} >= 10)
			GROUP BY a.`id`
			ORDER BY `{$orderBy}` {$order}, a.`title` ASC
		");

		if ( $albums !== false && count($albums) )
		{
			foreach ($orderCols as $key => $value)
			{
				if ($key == $orderBy)
				{
					$arrow = $order == 'asc' ? '&nbsp;&#9650;' : '&nbsp;&#9660;';
					$th[$key] = "<a href='{$admpage->href}&amp;tab=albums&amp;orderBy={$key}&amp;order={$orderR}'>{$value}{$arrow}</a>";
				}
				else
				{
					$arrow = '';
					$th[$key] = "<a href='{$admpage->href}&amp;tab=albums&amp;orderBy={$key}' title='Sort by {$value}'>{$value}{$arrow}</a>";
				}

			}

			echo
				"<table class='albums-list widefat' width='100%' cellspacing='0' border='0'>\n" .
				"<thead>\n" .
				"	<tr>\n" .
				"		<th class='album-title'>{$th['title']}</th>\n" .
				"		<th class='album-author'>{$th['author']}</th>\n" .
				"		<th class='album-date'>{$th['modified']}</th>\n" .
				"		<th class='album-size'>{$th['size']}</th>\n" .
				"		<th class='album-operation last' width='25%'>Operation</th>\n" .
				"	</tr>\n" .
				"</thead>\n" .
				"<tbody>\n";

			foreach ($albums as $album)
			{
				$album->href = $admpage->href;

				if ($album->size) {
					$album->sizeK = round($album->size / 1024) .'&nbsp;KB';
					$createAtts = array();
				}
				else {
					$album->sizeK = '&mdash;&nbsp;';
					$createAtts = array('disabled' => 'disabled');
				}

				$album->addPictures = $admpage->actionButton( 'Add Pictures', 'addMediaPage', array('album_id'=>$album->id) );
				$album->createGallery = $admpage->actionButton( 'Create Gallery', 'albumToGallery', array('album_id'=>$album->id), NULL, $createAtts );
				$album->delete = $admpage->actionButton( 'Delete Album', 'deleteAlbum', array('album_id'=>$album->id), 'Delete Album?\n\n"'.$album->title.'"\n' );

				$plugin->tpl->t('media/album', $album);
			}
			echo
				"</tbody>\n" .
				"</table>\n";
		}
	}

	function albumToGallery($album_id, $images = array())
	{
		include FLGALLERY_GLOBALS;

		$album = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbAlbums}`
			WHERE `id` = '{$album_id}'
		");
		if ($album !== false)
		{
			$admpage->head('New Gallery', 'new-gallery');
			$admpage->newGallery( array('name' => $album->title), array('album_id' => $album_id) );
		}
	}

	function createAlbum($a)
	{
		include FLGALLERY_GLOBALS;

		$order = $wpdb->get_var("
			SELECT MAX(`order`)
			FROM `{$plugin->dbAlbums}`
		");
		$order = $order === false ? 0 : (int)$order + 1;

		$wpdb->insert(
			$plugin->dbAlbums,
			array(
				'order' => $order,
				'author' => $plugin->userID,
				'title' => $a['title'],
				'description' => $a['description'],
				'created' => $now = $func->now(),
				'modified' => $now,
			)
		);
	}

	function editAlbum($album_id)
	{
		include FLGALLERY_GLOBALS;

		$album = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbAlbums}`
			WHERE `id` = '{$album_id}'
			LIMIT 1
		");
		if ($album !== false)
		{
			$admpage->head('Album Properties', 'album');

			$picturesHTML = '';
			$pictures = $wpdb->get_results("
				SELECT *
				FROM `{$plugin->dbImg}`
				WHERE
					`album_id` = '{$album_id}' AND
					`gallery_id` = '0'
				ORDER BY `order` ASC
			");
			if ( $pictures !== false && count($pictures) )
			{
				foreach ($pictures as $picture)
				{
					$image = new Image($picture);

					$picture->size = round($picture->size / 1024) .'&nbsp;KB';
					$picture->url = $plugin->imgURL .'/'. $picture->path;
					$picture->previewURL = $func->url( $image->resized(array( 'height' => 80 )) );
					$picture->href = $admpage->href .'&amp;album_id='. $album_id;
					$picturesHTML .= $tpl->parse('album/picture', $picture);
				}
			}
			$album->jsURL = $plugin->jsURL;
			$album->href = $admpage->href;
			$album->addPictures = $admpage->actionButton( array('Add Pictures', true), 'addMediaPage', array('album_id'=>$album_id) );

			$createAtts = $picturesHTML ? array() : array('disabled'=>'disabled');
			$album->createGallery = $admpage->actionButton( 'Create Flash Gallery', 'albumToGallery', array('album_id'=>$album_id), NULL, $createAtts );

			$album->pictures = $picturesHTML;
			$tpl->t('album/album', $album);
		}
	}

	function deleteAlbum($album_id)
	{
		include FLGALLERY_GLOBALS;

		$wpdb->query("
			DELETE FROM `{$plugin->dbAlbums}`
			WHERE `id` = '{$album_id}'
		");
		$wpdb->query("
			DELETE FROM `{$plugin->dbImg}`
			WHERE
				`album_id` = '{$album_id}' AND
				`gallery_id` = '0'
		");
	}

	function deleteImage($image_id)
	{
		include FLGALLERY_GLOBALS;

		$image = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbImg}`
			WHERE `id` = '{$image_id}'
		");
		if ($image !== false)
		{
			$res = $wpdb->query("
				DELETE FROM `{$plugin->dbImg}`
				WHERE `id` = '{$image_id}'
			");
			if ($res !== false)
			{
				$copies = $wpdb->get_results("
					SELECT *
					FROM `{$plugin->dbImg}`
					WHERE `path` = '{$image->path}'
				");
				if ( $copies !== false && count($copies) == 0 )
				{
					
					unlink( $plugin->imgDir.'/'.$image->path );
					
					preg_match('/(.*)(\..*)/', $image->path, $fname);
					$func->recurse( $plugin->tmpDir, '#^'.preg_quote($fname[1]).'-.*#i', 'unlink' );
				}
			}
			else
				return false;
		}
		else
			return false;

		return $image->id;
	}

	function addMediaPage($a)
	{
		include FLGALLERY_GLOBALS;
		global $startText;
		if ( empty($startText) )
			$startText = "Start Upload";

		$admpage->head('Add Pictures', 'addMediaPage');

		if ( !empty($a['album_id']) )
			$objectID = '&amp;album_id='.( (int)$_REQUEST['album_id'] );

		if ( !empty($a['gallery_id']) )
			$objectID = '&amp;gallery_id='.( (int)$_REQUEST['gallery_id'] );

		$tab = empty($_REQUEST['tab']) ? 'standard' : $_REQUEST['tab'];
		$admpage->tabmenu(
			array(
				
				'standard' =>	'Browser Uploader',
				'url' =>		'Add from URL',
				
				'directory' =>	'Import from FTP Folder',
			),
			$tab, '&amp;action=addMediaPage'.$objectID
		);

		switch ($tab)
		{
			case 'standard':
				$addMediaPage = $media->standardUploader($a, false);
				break;
			case 'url':
				$addMediaPage = $media->addFromURL($a, false);
				break;
			case 'directory':
				$addMediaPage = $media->importFolder($a, false);
				break;
			default:
				$addMediaPage = '';
		}

		$tpl->t( 'media/add', array(
			'href' => $admpage->href . $objectID,
			'content' => $addMediaPage,
			'start' => $startText
		));
	}

	function standardUploader(&$a, $echo = true)
	{
		include FLGALLERY_GLOBALS;

		$a['jsURL'] = $plugin->jsURL;
		$a['href'] = $admpage->href;

		$out = $tpl->parse('media/add-stdupload', $a);

		if ($echo)
			echo $out;

		return $out;
	}

	function addFromURL(&$a, $echo = true)
	{
		include FLGALLERY_GLOBALS;
		global $startText;
		$startText = 'Add URLs';

		$a['jsURL'] = $plugin->jsURL;
		$a['href'] = $admpage->href;

		$out = $tpl->parse('media/add-url', $a);

		if ($echo)
			echo $out;

		return $out;
	}

	function importFolder(&$a, $echo = true)
	{
		include FLGALLERY_GLOBALS;
		global $startText;
		$startText = 'Import';

		$a['jsURL'] = $plugin->jsURL;
		$a['uploadsURL'] = $plugin->uploadsURL;
		$a['href'] = $admpage->href;

		$out = $tpl->parse('media/add-directory', $a);

		if ($echo)
			echo $out;

		return $out;
	}

	function addMedia($a)
	{
		include FLGALLERY_GLOBALS;

		$album_id = empty($a['album_id']) ? 0 : $a['album_id'];
		$gallery_id = empty($a['gallery_id']) ? 0 : $a['gallery_id'];

		$order = $wpdb->get_var("
			SELECT MAX(`order`)
			FROM `{$plugin->dbImg}`
			WHERE
				`album_id` = '{$album_id}' AND
				`gallery_id` = '{$gallery_id}'
		");
		if ( $order === false )
		{
			$order = 0;
			$this->warning($wpdb->last_error);
			$this->debug($wpdb->last_query, array('Warning', $this->warningN));
		}

		$added = $media->addFiles($plugin->imgDir);
		if ( count($added) )
		{
			foreach ($added as $key => $path)
			{
				if ( !empty($path) )
				{
					$fullPath = $plugin->imgDir.'/'.$path;
					list($width, $height) = $imageSize = getimagesize($fullPath);

					$insert = $wpdb->insert(
						$plugin->dbImg,
						array(
							'album_id' =>	$album_id,
							'gallery_id' =>	$gallery_id,
							'order' =>		++$order,
							'type' =>		$imageSize['mime'],
							'path' =>		$path,
							'name' =>		basename( $this->files[$key] ),
							'title' =>		$_POST['stdupload_title'][$key],
							'description'=>	$_POST['stdupload_description'][$key],
							'width' =>		$width,
							'height' =>		$height,
							'size' =>		filesize($fullPath)
						)
					);
					if ($insert !== false)
					{
						$this->error($wpdb->last_error);
						$this->debug($wpdb->last_query, array('Error', $media->errorN));
					}
				}
			}
			if ( !empty($album_id) )
			{
				$wpdb->update( $plugin->dbAlbums, array('modified' => $func->now()), array('id' => $album_id) );
			}
		}
	}

	function addFiles($destDir)
	{
		include FLGALLERY_GLOBALS;

		if ( !empty($_FILES['stdUpload_file']) )
		{
			foreach ( $_FILES['stdUpload_file']['name'] as $key => $name )
			{
				if ( !empty($name) )
				{
					$this->files[$key] = $name;
					$ext = $func->fileExtByMIME( $_FILES['stdUpload_file']['type'][$key] );
					$destNames[$key] = basename( $func->uniqueFile($destDir."/%s{$ext}") );
				}
			}
			return $func->upload('stdUpload_file', $destDir, $destNames);
		}

		if ( !empty($_POST['addFromURL_file']) && is_array($_POST['addFromURL_file']) )
		{
			$URLs = array();
			foreach ( $_POST['addFromURL_file'] as $key => $url )
			{
				if ( preg_match('#^(http://|)(.*)#i', $url, $m) && !empty($m[2]) )
				{
					$this->files[$key] = $url;
					$ext = $func->fileExtByMIME( $func->fileMIME($url) );
					$destNames[$key] = basename( $func->uniqueFile($destDir."/%s{$ext}") );
				}
			}
			return $func->copyURLs( $this->files, $destDir, $destNames );
		}

		if ( !empty($_POST['importFolder_path']) )
		{
			$path = $_POST['importFolder_path'];
			$path = preg_replace('#^'.get_option('home').'/(.*)#', '$1', $path);
			$path = ABSPATH . $path;

			$this->files = array();
			$func->recurse( $path, '#.+#', array(&$this, 'addFile') );

			foreach ($this->files as $key => $path)
			{
				$ext = $func->fileExtByMIME( $func->fileMIME($path) );
				$destNames[$key] = basename( $func->uniqueFile($destDir."/%s{$ext}") );
			}

			return $func->copyFiles( $this->files, $destDir, $destNames, empty($_POST['importFolder_dontDelete']) );
		}
	}

	function addFile($filename)
	{
		$this->files[] = $filename;
	}
}


?>