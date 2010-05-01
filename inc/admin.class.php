<?php  if (defined('WP_ADMIN')) {

class flgalleryAdmin extends flgalleryBaseClass
{
	function init()
	{
		require_once FLGALLERY_INCLUDE.'/admin-page.class.php';

		$this->page = new flgalleryAdminPage();

		add_action( 'admin_init', array(&$this, 'wpInit') );
		add_action( 'admin_menu', array(&$this, 'menu') );
		add_action( 'admin_print_scripts', array(&$this, 'scripts') );

		add_action( 'wp_ajax_flgalleryAdmin', array(&$this, 'ajax') );
	}

	function wpInit()
	{
		include FLGALLERY_GLOBALS;

		if ( !empty($_REQUEST['orderBy']) || !empty($_REQUEST['order']) )
		{
			$cookie = $plugin->userCookie ? unserialize( base64_decode($plugin->userCookie) ) : array();
			$cookie['albumsList']['orderBy'] = $_REQUEST['orderBy'];
			$cookie['albumsList']['order'] = $_REQUEST['order'];
			setcookie( $plugin->userDomain, $plugin->userCookie = base64_encode(serialize($cookie)), time() + 86400*24, '/' );
		}

		$action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
		switch ($action)
		{
			case 'downloadImage':
				$image_id = (int)$_REQUEST['image_id'];

				
					$admin->downloadImage($image_id);

				break;

			default:
		}
	}

	function menu()
	{
		include FLGALLERY_GLOBALS;

		add_menu_page(
			$plugin->title,
			$plugin->shortTitle,
			2,
			$plugin->name,
			array('flgalleryAdmin', 'manage'),
			FLGALLERY_PLUGIN_URL.'/img/gallery-icon-16x16.png'
		);
		add_submenu_page(
			$plugin->name,
			__('Manage Galleries', $plugin->name),
			__('Galleries', $plugin->name),
			2,
			$plugin->name,
			array('flgalleryAdmin', 'manage')
		);
		add_submenu_page(
			$plugin->name,
			__('Media Library', $plugin->name),
			__('Media', $plugin->name),
			2,
			$plugin->name.'/media',
			array('flgalleryMedia', 'manage')
		);
		add_submenu_page(
			$plugin->name,
			__('About', $plugin->name).' '.$plugin->title,
			__('About', $plugin->name),
			2,
			$plugin->name.'/about',
			array('flgalleryAdmin', 'about')
		);
	}

	function scripts()
	{
		include FLGALLERY_GLOBALS;

		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_script('jquery-ui-draggable');
		wp_enqueue_script('jquery-ui-slider', $plugin->jsDir.'/jquery/ui.slider.js', array('jquery', 'jquery-ui-core'), '1.7.2');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('jquery-ui-resizable');
		
		wp_enqueue_script('farbtastic-nosharp', $plugin->jsDir.'/jquery/farbtastic-nosharp.js', array('jquery'), '1.2');
		wp_enqueue_script($plugin->name.'-swfobject', $plugin->jsDir.'/swfobject/swfobject.js', array(), '2.2');
		
;echo '<link rel="stylesheet" type="text/css" href="'; echo $plugin->url; ;echo '/css/jquery/ui.all.css" />
<link rel="stylesheet" type="text/css" href="'; echo $plugin->url; ;echo '/css/farbtastic/farbtastic.css" />
';
	}

	function manage()
	{
		include FLGALLERY_GLOBALS;

		$action = empty($_REQUEST['action']) ? NULL : $_REQUEST['action'];

		if ( !empty($_REQUEST['gallery_id']) )	
		{
			$gallery_id = (int)$_REQUEST['gallery_id'];
			$gallery = new flgalleryGallery($gallery_id);
			if ($gallery->id == $gallery_id)	
			{
				$admin->galleryAction($gallery, $action);
			}
			else
			{
				echo '<h1>'. __('Access Denied.') .'</h1>';
			}
		}
		else
		{
			switch ($action)
			{
				case 'addNewGallery':
					$admpage->head('Create', 'new-gallery');
					$admpage->newGallery();
					break;

				case 'createGallery':
					if ( !empty($_REQUEST['OK']) )
					{
						$admin->createGallery($_REQUEST['gallery']);
						$func->locationReset();
					}
					$admpage->manageGalleries();
					break;

				default:
					$admpage->manageGalleries();
			}
		}

		$admpage->foot();
	}

	function galleryAction($gallery, $action = '')
	{
		include FLGALLERY_GLOBALS;

		switch ($action)
		{
			case 'arrangeGallery':
				$gallery->arrange($_REQUEST['order']);
				$admpage->manageGalleries();
				break;

			case 'galleryOptions':
				$admpage->head('Gallery Options', 'gallery-options');
				$admpage->galleryOptions($gallery);
				break;

			case 'changeGalleryOptions':
				if ( !empty($_REQUEST['OK']) || !empty($_REQUEST['update']) || !empty($_REQUEST['exportXML']) )
				{
					$gallery_type = $gallery->type;
					$typeChanged = false;

					if ( !empty($_REQUEST['gallery']) )
					{
						foreach ($_REQUEST['gallery'] as $key => $value)
						{
							if ( isset($gallery->$key) )
								$gallery->$key = $value;
						}
						$gallery->save();
						if ( $_REQUEST['gallery']['type'] != $gallery_type )
							$typeChanged = true;
					}

					if ( !empty($_REQUEST['settings']) && !$typeChanged )
					{
						$gallery->get_settings();
						foreach ($gallery->settingsInfo as $key => $param)
						{
							if ( isset($_REQUEST['settings'][$key]) )
								$gallery->settings[$key] = trim($_REQUEST['settings'][$key]);
							else
							{
								$paramInputAtt = $param->input->attributes();
								if ( (string)$paramInputAtt->type == 'checkbox' )
								{
									$values = explode( '|', (string)$paramInputAtt->value );
									$gallery->settings[$key] = trim($values[1]);
								}
							}
						}
						$gallery->save_settings();
					}

					if ( !empty($_REQUEST['update']) )
					{
						$admpage->head('Gallery Options', 'gallery-options');
						$func->locationReset("&action=galleryOptions&gallery_id={$gallery->id}");
						$admpage->galleryOptions($gallery->id);
						break;
					}

					if ( !empty($_REQUEST['exportXML']) )
					{
						$admpage->manageGalleries();
						$func->redirect($plugin->url."/gallery-xml.php?id={$gallery->id}&download");
						break;
					}
				}

				if ( !empty($_REQUEST['resetOptions']) )
				{
					$gallery->reset_settings();

					$admpage->head('Gallery Options', 'gallery-options');
					$func->locationReset("&action=galleryOptions&gallery_id={$gallery->id}");
					$admpage->galleryOptions($gallery->id);
					break;
				}

				$func->locationReset("&gallery_id={$gallery->id}#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'uploadImage':
				$admin->uploadImage();
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'editImage':
				$admpage->head('Picture Properties', 'image-properties');
				$image_id = (int)$_REQUEST['image_id'];
				$admpage->editImage($image_id);
				break;

			case 'saveImage':
				if ( !empty($_REQUEST['OK']) && !empty($_REQUEST['image']) )
				{
					$image_id = (int)$_REQUEST['image_id'];
					if ( $this->saveImage($image_id, $_REQUEST['image']) )
						$gallery->save();
				}
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'downloadImage':
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'deleteGallery':
				$gallery->delete();
				$func->locationReset('');
				$admpage->manageGalleries();
				break;

			case 'deleteImage':
				$image_id = (int)$_REQUEST['image_id'];
				$admin->deleteImage($image_id, $gallery);
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'uploadPage':
				$media->uploadPage( array('gallery_id' => $gallery->id) );
				break;

			case 'upload':
				if ( !empty($_REQUEST['OK']) )
				{
					$media->uploadPictures( array('gallery_id' => $gallery->id) );
					$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
					$admpage->manageGalleries();
				}
				else
				{
					$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
					$admpage->manageGalleries();
				}
				break;

			case 'addImages':
				switch ( $_REQUEST['order'] )
				{
					case 'before':
						$order = (int)$wpdb->get_var("
							SELECT MIN(`order`)
							FROM `{$plugin->dbImages}`
							WHERE `gallery_id` = '{$gallery->id}'
						");
						$orderInc = -1;
						$_REQUEST['images'] = array_reverse($_REQUEST['images']);
						break;

					default:
						$order = (int)$wpdb->get_var("
							SELECT MAX(`order`)
							FROM `{$plugin->dbImages}`
							WHERE `gallery_id` = '{$gallery->id}'
						");
						$orderInc = 1;
						break;
				}

				foreach ( $_REQUEST['images'] as $image_id )
				{
					$order += $orderInc;
					$media->copyImage( $image_id, array('gallery_id' => $gallery->id, 'order' => $order) );
				}

				$gallery->save();

				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			default:
				$admpage->manageGalleries();
		}
	}

	function ajax()
	{
		include FLGALLERY_GLOBALS;

		switch ($_REQUEST['ajax_action'])
		{
			case 'sortImages':
				$admin->sortImages($_REQUEST);
				break;

			case 'deleteImage':
				if ( !empty($_REQUEST['gallery_id']) )
				{
					$gallery = new flgalleryGallery( (int)$_REQUEST['gallery_id'] );
				}
				$this->deleteImage($_REQUEST['image_id'], $gallery);
				break;

			case 'selectPictures':
				$media->selectPictures($_REQUEST);
				break;

			case 'addNewAlbum':
				$media->addNewAlbum();
				break;

			case 'editAlbum':
				$media->editAlbum($_REQUEST['album_id']);
				break;

			case 'editImage':
				$image_id = (int)$_REQUEST['image_id'];
				$admpage->head('Picture Properties', 'image-properties');
				$admpage->editImage($image_id);
				break;
		}

		exit();
	}

	function about()
	{
		include FLGALLERY_GLOBALS;

		$action = empty($_REQUEST['action']) ? NULL : $_REQUEST['action'];
		switch ($action)
		{
			case 'uninstallPlugin':
				if ( $plugin->uninstall() )
					return true;
			default:
				$admpage->head($plugin->title, 'about');
				$admpage->about();
		}
		$admpage->foot();
	}

	function createGallery($data = NULL)
	{
		if ( !empty($data) )
		{
			$gallery = new flgalleryGallery($data);
			if ( $id = $gallery->save() )
				return $id;
		}
		return false;
	}

	function uploadImage()
	{
		include FLGALLERY_GLOBALS;

		$ext = $func->fileExtByMIME( $func->fileMIME($_FILES['image']['name']) );
		$path = basename( $func->uniqueFile($plugin->imgDir."/%s{$ext}") );

		if ( $func->upload( 'image', $plugin->imgDir, $path ) )
		{
			$gallery_id = (int)$_REQUEST['gallery_id'];
			$album_id = (int)$_REQUEST['album_id'];

			$max = $_REQUEST['to'] == 'top' ? 'MIN' : 'MAX';

			$order = $wpdb->get_var("
				SELECT {$max}(`order`)
				FROM `{$plugin->dbImages}`
				WHERE
					`gallery_id` = '{$gallery_id}'
			");
			if ( $order === false )
			{
				$order = 0;
				$this->warning($wpdb->last_error);
				$this->debug($wpdb->last_query, array('Warning', $this->warningN));
			}

			$order += $_REQUEST['to'] == 'top' ? -1 : 1;

			$insert = $wpdb->insert(
				$plugin->dbImages,
				array(
					'album_id' =>	$album_id,
					'gallery_id' =>	$gallery_id,
					'order' =>		$order,
					'type' =>		$func->fileMIME($path),
					'path' =>		$path,
					'name' =>		$_FILES['image']['name'],
					'title' =>		'',
					'description'=>	'',
					'link' =>		'',
					'target' =>		'',
					'width' =>		0,
					'height' =>		0,
					'size' =>		$_FILES['image']['size']
				)

			);
			if ( $insert !== false )
			{
				$gallery = new flgalleryGallery($gallery_id);
				$gallery->save();
				return $wpdb->insert_id;
			}
			else
			{
				$this->error($wpdb->last_error);
				$this->debug($wpdb->last_query, array('Error', $this->errorN));
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	function saveImage($image_id, $a)
	{
		$image = new flgalleryImage($image_id);

		$image->set(
			array(
				'name' => $a['name'],
				'title' => $a['title'],
				'description' => $a['description'],
				'link' => $a['link'],
				'target' => $a['target']
			)
		);
		return $image->save();
	}

	function downloadImage($image_id)
	{
		include FLGALLERY_GLOBALS;

		$image = $wpdb->get_row("
			SELECT `type`, `name`, `path`
			FROM `{$plugin->dbImages}`
			WHERE `id` = '{$image_id}'
		");
		if ($image !== false)
		{
			header("Content-Type: {$image->type}");
			header("Content-Disposition: attachment; filename=\"{$image->name}\"");

			readfile($plugin->imgDir.'/'.$image->path);
		}
		else
			return false;
	}

	function sortImages($a)
	{
		$order = explode('&', $a['images_order']);

		$gallery_id = &$a['gallery_id'];
		$album_id = &$a['album_id'];

		if ( !empty($gallery_id) )
		{
			$gallery = new flgalleryGallery($gallery_id);
			if ( $gallery->arrange($order) )
				$gallery->save();
		}
	}

	function deleteImage($image_id, $gallery = false)
	{
		include FLGALLERY_GLOBALS;

		$image = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbImages}`
			WHERE `id` = '{$image_id}'
		");
		if ($image !== false)
		{
			$res = $wpdb->query("
				DELETE FROM `{$plugin->dbImages}`
				WHERE `id` = '{$image_id}'
			");
			if ($res !== false)
			{
				$copies = $wpdb->get_results("
					SELECT *
					FROM `{$plugin->dbImages}`
					WHERE `path` = '{$image->path}'
				");
				if ( $copies !== false && count($copies) == 0 )
				{
					
					unlink( $plugin->imgDir.'/'.$image->path );
					
					preg_match('/(.*)(\..*)/', $image->path, $fname);
					$func->recurse( $plugin->tmpDir, '#^'.preg_quote($fname[1]).'-.*#i', 'unlink' );
				}

				if ($gallery)
					$gallery->save();
			}
			else
				return false;
		}
		else
			return false;

		return $image->id;
	}

	function printMessages($a, $class)
	{
		if ( !empty($a) )
		{
			foreach ($a as $sender => $events)
			{
				print
					"<div class='flgallery-{$class}'>".
						"<h4><em>{$sender}</em> <strong>{$class}</strong>:</h4>\n";

				foreach ($events as $e)
					echo "\n<div>{$e}</div>";

				print "</div>\n";
			}
		}
	}
	function printErrors() {
		global $flgalleryErrors;
		$this->printMessages($flgalleryErrors, 'errors');
	}
	function printWarnings() {
		global $flgalleryWarnings;
		$this->printMessages($flgalleryWarnings, 'warnings');
	}
	function printDebug() {
		global $flgalleryDebug;
		$this->printMessages($flgalleryDebug, 'debug');
	}
}

} 
?>