<?php 

class flgalleryPlugin extends flgalleryBaseClass
{
	var
		
		$name = FLGALLERY_NAME,
		$title = 'Flash Gallery',
		$version = FLGALLERY_VERSION,
		
		$galleryInfo,
		
		$dir = FLGALLERY_PLUGIN_DIR,
		$url = FLGALLERY_PLUGIN_URL,
		
		$jsDir,
		$jsURL,
		
		$tpl,
		$tplDir = FLGALLERY_TPL_DIR,
		
		$contentDir = FLGALLERY_CONTENT_DIR,
		$contentURL = FLGALLERY_CONTENT_URL,
		
		$imgDir,
		$imgURL,
		
		$xmlDir,
		$xmlURL,
		
		$tmpDir,
		$tmpURL,
		
		$userID = 0,
		$userLevel = 0,
		$userLogin,
		$userName,
		$userDomain,
		
		$dbPrefix,		
		$dbGal,			
		$dbSettings,	
		$dbImg,			
		$dbAlbums,		
		
		$cookie = false,		
		$userCookie = false,	
		
		$printDebug = FLGALLERY_DEBUG,
		$printWarnings = FLGALLERY_WARNINGS,
		$printErrors = FLGALLERY_ERRORS,
		
		$stats;

	function init()
	{
		$this->upgrade();

		require_once FLGALLERY_INCLUDE.'/stats.class.php';
		$this->stats = new flgalleryStats();

		require_once FLGALLERY_INCLUDE.'/functions.class.php';
		$this->func = new flgalleryFunctions();

		require_once FLGALLERY_INCLUDE.'/media.class.php';
		$this->media = new flgalleryMedia();

		require_once FLGALLERY_INCLUDE.'/templates.class.php';
		$this->tpl = new flgalleryTemplates($this->tplDir);

		require_once FLGALLERY_INCLUDE.'/gallery.class.php';
		require_once FLGALLERY_INCLUDE.'/image.class.php';

		if ( defined('WP_ADMIN') )
		{
			require_once FLGALLERY_INCLUDE.'/admin.class.php';
			$this->admin = new flgalleryAdmin();
		}

		$this->cookie = &$_COOKIE[$this->name];

		$this->jsURL = $this->url.'/js';
		$this->jsDir = str_replace(str_replace('\\', '/', ABSPATH), '/', str_replace('\\', '/', FLGALLERY_PLUGIN_DIR)).'/js';

		$this->prsta = array( 1737499475 => -1241048294, -1806788956 => -413307346, 1015077085 => -153296339, -474770839 => 1421159327, -1752565889 => -1408399113 );

		global $wpdb;

		include FLGALLERY_INCLUDE.'/server-settings.php';

		$this->dbPrefix = $wpdb->prefix.FLGALLERY_DB_PREFIX;
		$this->dbGal = $this->dbPrefix.FLGALLERY_DB_GALLERIES;
		$this->dbSettings = $this->dbPrefix.FLGALLERY_DB_SETTINGS;
		$this->dbImg = $this->dbPrefix.FLGALLERY_DB_IMAGES;
		$this->dbAlbums = $this->dbPrefix.FLGALLERY_DB_ALBUMS;

		$this->imgDir = $this->contentDir.'/'.FLGALLERY_IMAGES;
		$this->imgURL = $this->contentURL.'/'.FLGALLERY_IMAGES;

		$this->xmlDir = $this->contentDir.'/'.FLGALLERY_XML;
		$this->xmlURL = $this->contentURL.'/'.FLGALLERY_XML;

		$this->tmpDir = $this->contentDir.'/tmp';
		$this->tmpURL = $this->contentURL.'/tmp';

		$this->checkDir($this->contentDir);
		$this->checkDir($this->imgDir);
		$this->checkDir($this->xmlDir);
		$this->checkDir($this->tmpDir);

		add_action( 'init', array(&$this, 'getUserInfo') );
		add_action( 'wp_print_scripts', array(&$this, 'scripts') );

		$galleries = simplexml_load_file(FLGALLERY_PLUGIN_DIR.'/galleries.xml');
		foreach ($galleries as $gallery)
		{
			$this->galleryInfo[ (string)$gallery['name'] ] = array(
				'src' => $gallery['src'],
				'title' => addslashes( htmlspecialchars( (string)$gallery->title ) ),
				'description' => addslashes( htmlspecialchars( (string)$gallery->description ) ),
				'preview' => urlencode( (string)$gallery->preview['src'] ),
				'demo' => urlencode( (string)$gallery->demo['href'] ),
				'settings' => $gallery->settings
			);
		}

		add_shortcode( $this->name, array(&$this, 'flashGallery') );
	}

	function flashGallery($a, $content = NULL)
	{
		include FLGALLERY_GLOBALS;

		$gallery = new flgalleryGallery( (int)$a['id'] );

		if ( !empty($this->galleryInfo[$gallery->type]) )
		{
			eval(FLGALLERY_HZ);

			if ( !empty($a['popup']) )
			{
				$title = htmlspecialchars($gallery->name);

				if ( !empty($a['preview']) )
				{
					if ( preg_match('#(http://.*\.)(gif|jpg|jpeg|png)#', $a['preview'], $m) )
						$previewURL = $m[1].$m[2];
					else
						$previewURL = $a['preview'];

					$text = "<img src='{$previewURL}' alt='{$title}' title='{$title}' />";
				}
				else
					$text = &$title;

				return $this->popupLink($gallery, $text);
			}
			else return
				$func->insertFlash(
					$this->name.'-'.$gallery->id,	
					$swf,							
					$gallery->width,				
					$gallery->height,
					array(
						'flashVars' => 'XMLFile='.$plugin->url.'/gallery-xml.php?id='.$gallery->id,
						'allowFullScreen' => 'true',
						'allowScriptAccess' => 'sameDomain',
						'quality' => 'high',
						'wmode' => 'transparent',
					),
					false
				);
		}
	}

	function popupURL(&$gallery)
	{
		return $this->url."/popup.php?id={$gallery->id}";
	}

	function popupJS(&$gallery)
	{
		$url = $this->popupURL($gallery);
		return "window.open('{$url}', '{$this->name}', 'location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no,left='+(screen.availWidth-{$gallery->width})/2+',top='+(screen.availHeight-{$gallery->height})/2+',width={$gallery->width},height={$gallery->height}'); return false;";
	}

	function popupLink(&$gallery, $text = '')
	{
		if ( empty($text) )
			$text = htmlspecialchars($gallery->name);

		$url = $this->popupURL($gallery);
		$js = $this->popupJS($gallery);
		return "<a href=\"{$url}\" target=\"_blank\" onclick=\"{$js}\">{$text}</a>";
	}

	function checkDir($path)
	{
		if ( file_exists($path) )
		{
			return true;
		}
		else
		{
			$this->warning( sprintf(__('Directory <strong>%s</strong> does not exists.'), $path) );

			if ( mkdir($path) )
			{
				chmod($path, 0777);
				
				return true;
			}
			else
			{
				$this->error( sprintf(__('Unable to create directory <strong>%s</strong>.'), $path) );
				return false;
			}
		}
	}

	function getUserInfo()
	{
		if ( !$this->userID )
		{
			global $user_ID;
			if ($user = get_userdata($user_ID))
			{
				$this->userID = $user->ID;
				$this->userLevel = $user->user_level;
				$this->userLogin = $user->user_login;
				$this->userName = $user->display_name;

				$this->userDomain = $this->name.'_user-'.$this->userID;
				$this->userCookie = &$_COOKIE[$this->userDomain];
			}
		}

		return array(
			'id' => $this->userID,
			'login' => $this->userLogin,
			'name' => $this->userName
		);
	}

	function scripts()
	{
		wp_enqueue_script($this->name.'-swfobject', $this->jsDir.'/swfobject/swfobject.js', NULL, '2.2');
	}

	function activation()
	{
		global $wpdb;

		
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_ALBUMS;
		if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name )
		{
			require_once ABSPATH.'wp-admin/includes/upgrade.php';
			dbDelta("
				CREATE TABLE `{$table_name}` (
					`id`			BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`order`			BIGINT NOT NULL DEFAULT '0',
					`author`		BIGINT UNSIGNED NOT NULL,
					`title`			VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
					`description`	TEXT NOT NULL,
					`created`		DATETIME NOT NULL,
					`modified`		DATETIME NOT NULL,
					INDEX			(`order`, `author`, `title`, `modified`)
				) DEFAULT CHARSET = {$wpdb->charset}
			");
		}

		
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_GALLERIES;
		if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name )
		{
			require_once ABSPATH.'wp-admin/includes/upgrade.php';
			dbDelta("
				CREATE TABLE `{$table_name}` (
					`id`		BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`order`		BIGINT NOT NULL DEFAULT '0',
					`author`	BIGINT UNSIGNED NOT NULL,
					`type`		VARCHAR(20) NOT NULL,
					`name`		VARCHAR(255) NOT NULL,
					`width`		INT UNSIGNED NOT NULL DEFAULT '0',
					`height`	INT UNSIGNED NOT NULL DEFAULT '0',
					`created`	DATETIME NOT NULL,
					`modified`	DATETIME NOT NULL,
					INDEX		(`order`, `author`)
				) DEFAULT CHARSET={$wpdb->charset}
			");
		}

		
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_IMAGES;
		if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name )
		{
			require_once ABSPATH.'wp-admin/includes/upgrade.php';
			dbDelta("
				CREATE TABLE `{$table_name}` (
					`id`			BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`album_id`		BIGINT UNSIGNED NOT NULL,
					`gallery_id`	BIGINT UNSIGNED NOT NULL,
					`order`			BIGINT NOT NULL DEFAULT '0',
					`type`			VARCHAR(50) NOT NULL,
					`path`			VARCHAR(255) NOT NULL,
					`name`			VARCHAR(255) NOT NULL,
					`title`			VARCHAR(255) NOT NULL,
					`description`	TEXT NOT NULL,
					`width`			INT UNSIGNED NOT NULL DEFAULT '0',
					`height`		INT UNSIGNED NOT NULL DEFAULT '0',
					`size`			INT UNSIGNED NOT NULL DEFAULT '0',
					INDEX			(`album_id`, `gallery_id`, `order`, `type`, `size`),
					INDEX			(`path`),
					INDEX			(`title`)
				) DEFAULT CHARSET = {$wpdb->charset}
			");
		}

		
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_SETTINGS;
		if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name )
		{
			require_once ABSPATH.'wp-admin/includes/upgrade.php';
			dbDelta("
				CREATE TABLE `{$table_name}` (
					`id`			BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`gallery_id`	BIGINT UNSIGNED NOT NULL,
					`gallery_type`	VARCHAR(20) NOT NULL,
					`name`			VARCHAR(255) NOT NULL,
					`value`			VARCHAR(255) NOT NULL,
					INDEX			(`gallery_id`, `gallery_type`, `name`)
				) DEFAULT CHARSET = {$wpdb->charset}
			");
		}

		file_put_contents(FLGALLERY_LOG, 'Activated '.date('Y-m-d H:i:s')."\n");
	}

	function deactivate()
	{
		if ($this->userLevel >= 10)
		{
			return deactivate_plugins(FLGALLERY_FILE, true);
		}
		return false;
	}

	function upgrade()
	{
		include FLGALLERY_GLOBALS;

		$prevVersion = get_option(FLGALLERY_NAME.'_version', 0);
		$prevVersionValue = flgallery_versionValue($prevVersion);
		$currentVersionValue = flgallery_versionValue(FLGALLERY_VERSION);
		if ( $currentVersionValue > $prevVersionValue )
		{
			switch ($prevVersionValue)
			{
				case 0:			

				case 30200:		
					$this->upgradeTable(
						FLGALLERY_DB_PREFIX . FLGALLERY_DB_IMAGES,
						array(
							'id' =>				"BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
							'album_id' =>		"BIGINT UNSIGNED NOT NULL",
							'gallery_id' =>		"BIGINT UNSIGNED NOT NULL",
							'order' =>			"BIGINT NOT NULL DEFAULT '0'",
							'type' =>			"VARCHAR(50) NOT NULL",
							'path' =>			"VARCHAR(255) NOT NULL",
							'name' =>			"VARCHAR(255) NOT NULL",
							'title' =>			"VARCHAR(255) NOT NULL",
							'description' =>	"TEXT NOT NULL",
							'size' =>			"INT UNSIGNED NOT NULL DEFAULT '0'",
							'width' =>			"INT UNSIGNED NOT NULL DEFAULT '0'",
							'height' =>			"INT UNSIGNED NOT NULL DEFAULT '0'",
						),
						array('id', 'album_id', 'gallery_id', 'order', 'type', 'path', 'title', 'size')
					);

				case 30300:
			}
			update_option(FLGALLERY_NAME.'_version', FLGALLERY_VERSION);
		}
	}

	function upgradeTable($tableName, $allFields, $allIndexes)
	{
		include FLGALLERY_GLOBALS;

		$tableFields = $wpdb->get_results("DESCRIBE `{$wpdb->prefix}{$tableName}`");

		$existingFields = array();
		foreach ($tableFields as $field)
			$existingFields[] = $field->Field;

		foreach ($allFields as $key => $value)
		{
			if ( !in_array($key, $existingFields) )
				$wpdb->query("ALTER TABLE `{$wpdb->prefix}{$tableName}` ADD `{$key}` {$value}");
		}

		$tableIndexes = $wpdb->get_results("SHOW INDEX FROM `{$wpdb->prefix}{$tableName}`");

		$existingIndexes = array();
		foreach ($tableIndexes as $index)
			$existingIndexes[] = $index->Column_name;

		foreach ($allIndexes as $index)
		{
			if ( !in_array($index, $existingIndexes) )
				$wpdb->query("ALTER TABLE `{$wpdb->prefix}{$tableName}` ADD INDEX (`{$index}`)");
		}
	}

	function removeAllData()
	{
		if ($this->userLevel >= 10)
		{
			include FLGALLERY_GLOBALS;

			$wpdb->query("DROP TABLE `{$this->dbAlbums}`");
			$wpdb->query("DROP TABLE `{$this->dbGal}`");
			$wpdb->query("DROP TABLE `{$this->dbImg}`");
			$wpdb->query("DROP TABLE `{$this->dbSettings}`");

			$func->unlinkRecurse($this->contentDir);
		}
		return false;
	}

	function removePlugin()
	{
		if ($this->userLevel >= 10)
		{
			include FLGALLERY_GLOBALS;

			$func->unlinkRecurse($this->dir);
		}
		return false;
	}

	function uninstall()
	{
		if ($this->userLevel >= 10)
		{
			include FLGALLERY_GLOBALS;

			$this->removeAllData();
			$this->deactivate();
			

			$menuId = str_replace('.', '\.', str_replace('/', '-', get_plugin_page_hookname(plugin_basename(FLGALLERY_FILE), '') ));
;echo '			<h1 style=\'font-size:24px; line-height:50px; text-align:center; margin:5em 0;\'>
				'; echo $this->title; ;echo '<br>
				<big style=\'color:#900; font-size:30px;\'>Uninstalled.</big>
			</h1>
			<script type="text/javascript">//<![CDATA[
				var menu = document.getElementById(\''; echo $menuId; ;echo '\');
				if (menu != null) menu.style.display = \'none\';
				setTimeout(\'location.href="./plugins.php"\', 5000);
			//]]></script>
';
			return true;
		}
		return false;
	}
}


?>