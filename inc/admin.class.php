<?php if (defined('WP_ADMIN')) {

class flgalleryAdmin extends flgalleryBaseClass
{
	function init()
	{
		require_once FLGALLERY_INCLUDE . '/adminPage.class.php';
		$this->page = new flgalleryAdminPage();

		if (!empty($_REQUEST['page']) && ($_REQUEST['page'] == 'flgallery' || $_REQUEST['page'] == 'flgallery/media')) {
			add_action('admin_init', array(&$this, 'wpInit'));
			add_action('admin_print_scripts', array(&$this, 'scripts'));
		}

		add_action('admin_menu', array(&$this, 'menu'));
		add_action('wp_ajax_flgalleryAdmin', array(&$this, 'ajax'));
	}

	function wpInit()
	{
		include FLGALLERY_GLOBALS;

		if (!empty($_REQUEST['orderBy']) || !empty($_REQUEST['order'])) {
			$cookie = $plugin->userCookie ? unserialize(base64_decode($plugin->userCookie)) : array();
			$cookie['albumsList']['orderBy'] =& $_REQUEST['orderBy'];
			$cookie['albumsList']['order'] =& $_REQUEST['order'];
			setcookie($plugin->userDomain, $plugin->userCookie = base64_encode(serialize($cookie)), time() + 86400 * 24, '/');
		}

		$action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
		switch ($action) {
			case 'downloadImage':
				$image_id = (int)$_REQUEST['image_id'];

				$gallery_id = (int)$_REQUEST['gallery_id'];
				$gallery = new flgalleryGallery($gallery_id);
				if ($gallery->id == $gallery_id)
					$admin->downloadImage($image_id);

				break;

			default:
				break;
		}
	}

	function menu()
	{
		include FLGALLERY_GLOBALS;

		add_menu_page(
			$plugin->title,
			$plugin->shortTitle,
			'edit_posts',
			$plugin->name,
			array('flgalleryAdmin', 'manage'),
			FLGALLERY_PLUGIN_URL.'/img/gallery-icon-16x16.png'
		);
		add_submenu_page(
			$plugin->name,
			__('Manage Galleries', $plugin->name),
			__('Galleries', $plugin->name),
			'edit_posts',
			$plugin->name,
			array('flgalleryAdmin', 'manage')
		);
		add_submenu_page(
			$plugin->name,
			__('Media Library', $plugin->name),
			__('Media', $plugin->name),
			'edit_posts',
			$plugin->name.'/media',
			array('flgalleryMedia', 'manage')
		);
		add_submenu_page(
			$plugin->name,
			__('About', $plugin->name).' '.$plugin->title,
			__('About', $plugin->name),
			'edit_posts',
			$plugin->name.'/about',
			array('flgalleryAdmin', 'about')
		);
	}

	function scripts()
	{
		include FLGALLERY_GLOBALS;

		$wp_version = get_bloginfo('version');
		if (version_compare($wp_version, '3.1', '>=')) {
			// WordPress 3.1 and newer
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-widget');
			wp_enqueue_script('jquery-ui-mouse');
			wp_enqueue_script('jquery-ui-position');
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_script('jquery-ui-draggable');
			wp_enqueue_script('jquery-ui-resizable');
			wp_enqueue_script('jquery-ui-slider');
			wp_enqueue_script('jquery-ui-sortable');
		} else {
			// WordPress 3.0.x and older
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_script('jquery-ui-draggable');
			wp_enqueue_script('jquery-ui-resizable');
			wp_enqueue_script('jquery-ui-slider', $plugin->jsDir . '/ui.slider.js', array('jquery', 'jquery-ui-core'), '1.7.3');
			wp_enqueue_script('jquery-ui-sortable');
		}

		wp_enqueue_script('swfobject');
		wp_enqueue_script('swfupload');
		wp_enqueue_script('swfupload-queue');
		wp_enqueue_script('jquery-scrollTo', $plugin->jsDir . '/jquery.scrollTo.js', array('jquery'), '1.4.2');
		wp_enqueue_script($plugin->name . '-farbtastic', $plugin->jsDir . '/farbtastic.js', array('jquery'), '1.2');
?>
<link rel="stylesheet" type="text/css" href="<?php echo $plugin->url; ?>/css/jquery/jquery-ui.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $plugin->url; ?>/css/farbtastic/farbtastic.css" />
<?php
	}

	function manage()
	{
		include FLGALLERY_GLOBALS;
		$plugin->getUserInfo();

		$action = empty($_REQUEST['action']) ? NULL : $_REQUEST['action'];

		if (!empty($_REQUEST['gallery_id'])) // Gallery actions
		{
			$gallery_id = (int)$_REQUEST['gallery_id'];
			$gallery = new flgalleryGallery($gallery_id);
			if ($gallery->id == $gallery_id) {
				$admin->galleryAction($gallery, $action);
			} else {
				echo '<h1>' . __('Access Denied.') . '</h1>';
			}
		} else {
			switch ($action) {
				case 'addNewGallery':
					$admpage->head('Create', 'new-gallery');
					$admpage->newGallery();
					break;

				case 'createGallery':
					if (!empty($_POST['OK'])) {
						$data = array(
							'name' => sanitize_text_field(stripslashes($_POST['gallery']['name'])),
							'type' => sanitize_text_field(stripslashes($_POST['gallery']['type']))
						);
						if ($admin->createGallery($data)) {
							$func->locationReset();
						}
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

		switch ($action) {
			case 'arrangeGallery':
				$gallery->arrange($_POST['order']);
				$admpage->manageGalleries();
				break;

			case 'galleryOptions':
				$admpage->head('Gallery Options', 'gallery-options');
				$admpage->galleryOptions($gallery);
				break;

			case 'changeGalleryOptions':
				if (!empty($_POST['OK']) || !empty($_POST['update']) || !empty($_POST['exportXML'])) {
					$gallery_type = $gallery->type;
					$typeChanged = false;

					if (!empty($_POST['gallery'])) {
						$gallery->name = sanitize_text_field(stripslashes($_POST['gallery']['name']));
						$gallery->type = sanitize_text_field(stripslashes($_POST['gallery']['type']));
						$gallery->width = (int)$_POST['gallery']['width'];
						$gallery->height = (int)$_POST['gallery']['height'];

						$gallery->save();

						if ($gallery->type != $gallery_type) {
							$typeChanged = true;
						}
					}

					if (!empty($_POST['settings']) && !$typeChanged) {
						$gallery->getSettings();

						foreach ($gallery->settingsInfo as $key => $param) {
							if (isset($_POST['settings'][$key])) {
								$gallery->settings[$key] = sanitize_text_field(stripslashes($_POST['settings'][$key]));
							} else {
								$paramInputAtt = $param->input->attributes();
								if ((string)$paramInputAtt->type == 'checkbox') {
									$values = explode('|', (string)$paramInputAtt->value);
									$gallery->settings[$key] = trim($values[1]);
								}
							}
						}

						$gallery->saveSettings();
					}

					if (!empty($_POST['update'])) {
						$admpage->head('Gallery Options', 'gallery-options');
						$func->locationReset("&action=galleryOptions&gallery_id={$gallery->id}");
						$admpage->galleryOptions($gallery);
						break;
					}

					if (!empty($_POST['exportXML'])) {
						$admpage->manageGalleries();
						$xmlUrl = admin_url('admin-ajax.php') . "?action=flgalleryXml&gallery_id={$gallery->id}&blog_id={$plugin->blogID}&download";
						$func->redirect($xmlUrl);
						break;
					}
				}

				if (!empty($_POST['resetOptions'])) {
					$gallery->resetSettings();

					$admpage->head('Gallery Options', 'gallery-options');
					$func->locationReset("&action=galleryOptions&gallery_id={$gallery->id}");
					$admpage->galleryOptions($gallery);
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
				if (!empty($_POST['OK']) && !empty($_POST['image'])) {
					$image_id = (int)$_POST['image_id'];
					$applyToCopies = !empty($_POST['applyToCopies']);
					$data = array(
						'name' => sanitize_file_name(stripslashes($_POST['image']['name'])),
						'title' => sanitize_text_field(stripslashes($_POST['image']['title'])),
						'description' => sanitize_text_field(stripslashes($_POST['image']['description'])),
						'link' => sanitize_text_field(stripslashes($_POST['image']['link'])),
						'target' => sanitize_text_field(stripslashes($_POST['image']['target']))
					);
					if ($media->saveImage($image_id, $data, $applyToCopies)) {
						$gallery->save();
					}
				}
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'downloadImage':
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'deleteGallery':
				if (!empty($_POST['gallery_id'])) {
					$gallery->delete();
				}
				$func->locationReset('');
				$admpage->manageGalleries();
				break;

			case 'deleteImage':
				if (wp_verify_nonce($_REQUEST['nonce'], 'deleteImage')) {
					$image_id = (int)$_REQUEST['image_id'];
					$admin->deleteImage($image_id, $gallery);
				}
				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			case 'uploadPage':
				$media->uploadPage(array('gallery_id' => $gallery->id));
				break;

			case 'upload':
				if (!empty($_POST['OK'])) {
					$media->uploadPictures(array('gallery_id' => $gallery->id));
					$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
					$admpage->manageGalleries();
				} else {
					$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
					$admpage->manageGalleries();
				}
				break;

			case 'addImages':
				switch ($_POST['order']) {
					case 'before':
						$order = (int)$wpdb->get_var("
							SELECT MIN(`order`)
							FROM `{$plugin->dbImages}`
							WHERE `gallery_id` = '{$gallery->id}'
						");
						$orderInc = -1;
						$_POST['images'] = array_reverse($_POST['images']);
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

				foreach ($_POST['images'] as $image_id) {
					$order += $orderInc;
					$media->copyImage($image_id, array('gallery_id' => $gallery->id, 'order' => $order));
				}

				$gallery->save();

				$func->locationReset("&gallery_id={$gallery->id}&imgs=1#gallery-{$gallery->id}");
				$admpage->manageGalleries();
				break;

			default:
				$admpage->manageGalleries();
				break;
		}
	}

	function ajax()
	{
		include FLGALLERY_GLOBALS;
		$plugin->getUserInfo();

		$request = array_merge($_GET, $_POST);

		if (isset($request['gallery_id']) && is_array($request['gallery_id']))
			$request['gallery_id'] = $request['gallery_id'][0];

		switch ($request['ajax_action']) {
			case 'getGalleryItemsHtml':
				if (!empty($request['gallery_id'])) {
					$gallery = new flgalleryGallery((int)$request['gallery_id']);
				} else {
					$gallery = null;
				}

				echo $admpage->getGalleryItemsHtml($gallery);
				break;

			case 'getAlbumItemsHtml':
				echo $media->getAlbumItemsHtml($request['album_id']);
				break;

			case 'sortImages':
				if (!empty($_POST['gallery_id'])) {
					$admin->sortImages($request);
				}
				break;

			case 'deleteImage':
				if (wp_verify_nonce($request['nonce'], 'deleteImage')) {
					$gallery = !empty($request['gallery_id']) ? new flgalleryGallery((int)$request['gallery_id']) : null;
					$this->deleteImage($request['image_id'], $gallery);
				}
				break;

			case 'selectPictures':
				$media->selectPictures($request);
				break;

			case 'addNewAlbum':
				$media->addNewAlbum();
				break;

			case 'editAlbum':
				$media->editAlbum($request['album_id']);
				break;

			case 'editImage':
				$image_id = (int)$request['image_id'];
				$admpage->head('Picture Properties', 'image-properties');
				$admpage->editImage($image_id);
				break;

			case 'getWpMediaLibraryJson':
				$offset = (int)$request['offset'];
				$limit = (int)$request['limit'];
				$media->getWpMediaLibraryJson($offset, $limit);
				break;
		}

		exit();
	}

	function about()
	{
		include FLGALLERY_GLOBALS;

		$action = empty($_REQUEST['action']) ? NULL : $_REQUEST['action'];
		switch ($action) {
			case 'uninstallPlugin':
				if ($plugin->uninstall()) {
					return true;
				}
			default:
				$admpage->head($plugin->title, 'about');
				$admpage->about();
				break;
		}
		$admpage->foot();
	}

	function createGallery($data = NULL)
	{
		if (!empty($data)) {
			$gallery = new flgalleryGallery($data);
			if ($id = $gallery->save()) {
				return $id;
			}
		}
		return false;
	}

	function uploadImage()
	{
		include FLGALLERY_GLOBALS;

		$ext = $func->fileExtByMime($func->fileMime($_FILES['image']['name']));
		$path = basename($func->uniqueFile($plugin->imgDir . "/%s{$ext}"));

		if ($func->upload('image', $plugin->imgDir, $path)) {
			$gallery_id = (int)$_REQUEST['gallery_id'];
			$album_id = (int)$_REQUEST['album_id'];

			$max = $_REQUEST['to'] == 'top' ? 'MIN' : 'MAX';

			$order = $wpdb->get_var("
				SELECT {$max}(`order`)
				FROM `{$plugin->dbImages}`
				WHERE `gallery_id` = '{$gallery_id}'
			");
			if ($order === false) {
				$order = 0;
				$this->warning($wpdb->last_error);
				$this->debug($wpdb->last_query, array('Warning', $this->warningN));
			}

			$order += $_REQUEST['to'] == 'top' ? -1 : 1;

			$insert = $wpdb->insert(
				$plugin->dbImages,
				array(
					'album_id' => $album_id,
					'gallery_id' => $gallery_id,
					'order' => $order,
					'type' => $func->fileMime($path),
					'path' => $path,
					'name' => $_FILES['image']['name'],
					'title' => '',
					'description' => '',
					'link' => '',
					'target' => '',
					'width' => 0,
					'height' => 0,
					'size' => $_FILES['image']['size']
				)

			);
			if ($insert !== false) {
				$gallery = new flgalleryGallery($gallery_id);
				$gallery->save();
				return $wpdb->insert_id;
			} else {
				$this->error($wpdb->last_error);
				$this->debug($wpdb->last_query, array('Error', $this->errorN));
				return false;
			}
		} else {
			return false;
		}
	}

	function downloadImage($image_id)
	{
		include FLGALLERY_GLOBALS;

		$image_id = (int)$image_id;

		$image = $wpdb->get_row("
			SELECT `type`, `name`, `path`
			FROM `{$plugin->dbImages}`
			WHERE `id` = '{$image_id}'
		");
		if ($image !== false) {
			header("Content-Type: {$image->type}");
			header("Content-Disposition: attachment; filename=\"{$image->name}\"");

			readfile($plugin->imgDir . '/' . $image->path);
		} else {
			return false;
		}
	}

	function sortImages($a)
	{
		$order = explode('&', $a['images_order']);

		$gallery_id =& $a['gallery_id'];
		$album_id =& $a['album_id'];

		if (!empty($gallery_id)) {
			$gallery = new flgalleryGallery($gallery_id);
			if ($gallery->arrange($order)) {
				$gallery->save();
			}
		}
	}

	function deleteImage($image_id, $gallery = null)
	{
		include FLGALLERY_GLOBALS;

		$image_id = (int)$image_id;

		$image = $wpdb->get_row("
			SELECT *
			FROM `{$plugin->dbImages}`
			WHERE `id` = '{$image_id}'
		");
		if ($image) {
			$res = $wpdb->query("
				DELETE FROM `{$plugin->dbImages}`
				WHERE `id` = '{$image_id}'
			");
			if ($res !== false) {
				$copies = $wpdb->get_results($wpdb->prepare("
					SELECT *
					FROM `{$plugin->dbImages}`
					WHERE `path` = %s
				", $image->path));

				if ($copies !== false && count($copies) == 0) {
					preg_match('/(.*)(\..*)/', $image->path, $fname);

					if (strpos($image->path, '/') === 0) {
						$fname[1] = md5($fname[1]);
					} else {
						// Delete image
						unlink($plugin->imgDir . '/' . $image->path);
					}

					// Delete thumbnails
					$func->recurse($plugin->tmpDir, '#^img-' . preg_quote($fname[1], '#') . '\..+#i', 'unlink');
				}

				if ($gallery) {
					$gallery->save();
				}
			} else {
				return false;
			}
		} else {
			return false;
		}

		return $image->id;
	}

	function printMessages($a, $class)
	{
		if (!empty($a)) {
			foreach ($a as $sender => $events) {
				print
					"<div class='flgallery-{$class}'>" .
					"<h4><em>{$sender}</em> <strong>{$class}</strong>:</h4>\n";

				foreach ($events as $e) {
					echo "\n<div>{$e}</div>";
				}

				print "</div>\n";
			}
		}
	}

	function printErrors()
	{
		global $flgalleryErrors;
		$this->printMessages($flgalleryErrors, 'errors');
	}

	function printWarnings()
	{
		global $flgalleryWarnings;
		$this->printMessages($flgalleryWarnings, 'warnings');
	}

	function printDebug()
	{
		global $flgalleryDebug;
		$this->printMessages($flgalleryDebug, 'debug');
	}
}

}
