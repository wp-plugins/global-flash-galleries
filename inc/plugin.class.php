<?php 

class flgalleryPlugin extends flgalleryBaseClass
{
	var
		
		$name = FLGALLERY_NAME,
		$title = 'Global Flash Galleries',
		$shortTitle = 'Flash Galleries',
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
		
		$imgBlacklistPath,
		
		$xmlDir,
		$xmlURL,
		
		$uploadsDir,
		$uploadsURL,
		
		$tmpDir,
		$tmpURL,
		
		$site,
		
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
		require_once FLGALLERY_INCLUDE.'/stats.class.php';
		$this->stats = new flgalleryStats();

		require_once FLGALLERY_INCLUDE.'/functions.class.php';
		$this->func = new flgalleryFunctions();

		require_once FLGALLERY_INCLUDE.'/site.class.php';
		$this->site = new flgallerySite();

		require_once FLGALLERY_INCLUDE.'/templates.class.php';
		$this->tpl = new flgalleryTemplates( $this->tplDir, array('plugin' => &$this) );

		require_once FLGALLERY_INCLUDE.'/gallery.class.php';
		require_once FLGALLERY_INCLUDE.'/image.class.php';

		if ( defined('WP_ADMIN') )
		{
			require_once FLGALLERY_INCLUDE.'/admin.class.php';
			$this->admin = new flgalleryAdmin();

			require_once FLGALLERY_INCLUDE.'/media.class.php';
			$this->media = new flgalleryMedia();
		}

		$this->upgrade();

		$this->cookie = &$_COOKIE[$this->name];

		$this->jsURL = $this->url.'/js';
		$this->jsDir = str_replace(str_replace('\\', '/', ABSPATH), '/', str_replace('\\', '/', FLGALLERY_PLUGIN_DIR)).'/js';

		global $wpdb;

		$this->dbPrefix = $wpdb->prefix.FLGALLERY_DB_PREFIX;
		$this->dbGal = $this->dbPrefix.FLGALLERY_DB_GALLERIES;
		$this->dbSettings = $this->dbPrefix.FLGALLERY_DB_SETTINGS;
		$this->dbImg = $this->dbPrefix.FLGALLERY_DB_IMAGES;
		$this->dbAlbums = $this->dbPrefix.FLGALLERY_DB_ALBUMS;

		$this->imgDir = $this->contentDir.'/'.FLGALLERY_IMAGES;
		$this->imgURL = $this->contentURL.'/'.FLGALLERY_IMAGES;

		$this->xmlDir = $this->contentDir.'/'.FLGALLERY_XML;
		$this->xmlURL = $this->contentURL.'/'.FLGALLERY_XML;

		$this->uploadsDir = $this->contentDir.'/'.FLGALLERY_UPLOADS;
		$this->uploadsURL = $this->contentURL.'/'.FLGALLERY_UPLOADS;

		$this->tmpDir = $this->contentDir.'/'.FLGALLERY_TEMP;
		$this->tmpURL = $this->contentURL.'/'.FLGALLERY_TEMP;

		$this->imgBlacklistPath = $this->tmpDir.'/!blacklist.txt';

		$this->checkDir($this->contentDir);
		$this->checkDir($this->imgDir);
		$this->checkDir($this->xmlDir);
		$this->checkDir($this->uploadsDir);
		$this->checkDir($this->tmpDir);

		add_action( 'init', array(&$this, 'getUserInfo') );
		add_action( 'wp_print_scripts', array(&$this, 'scripts') );

		$this->init_galleryInfo();

		add_shortcode( $this->name, array(&$this, 'flashGallery') );
	}

	function init_galleryInfo()
	{
		if ( defined('FLGALLERY_PHP5') )
		{
			
			$galleries = simplexml_load_file(FLGALLERY_PLUGIN_DIR.'/galleries.xml');
		}
		else
		{
			
			include_once 'simplexml.class.php';
			$simplexml = new simplexml();
			$galleries = $simplexml->xml_load_file(FLGALLERY_PLUGIN_DIR.'/galleries.xml');
		}

		foreach ($galleries->gallery as $gallery)
		{
			$galleryAtt = $gallery->attributes();
			$galleryPreviewAtt = $gallery->preview->attributes();
			$galleryDemoAtt = $gallery->demo->attributes();

			$this->galleryInfo[ (string)$galleryAtt->name ] = array(
				'src' => (string)$galleryAtt->src,
				'title' => addslashes( htmlspecialchars( (string)$gallery->title ) ),
				'description' => addslashes( htmlspecialchars( (string)$gallery->description ) ),
				'preview' => urlencode( (string)$galleryPreviewAtt->src ),
				'demo' => urlencode( (string)$galleryDemoAtt->href ),
				'settings' => $gallery->settings
			);
		}
	}

	function flashGallery($a, $content = NULL)
	{
		include FLGALLERY_GLOBALS;

		$gallery = new flgalleryGallery( $a['id'] );

		if ( !empty($this->galleryInfo[$gallery->type]) )
		{
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

				return $gallery->get_popupLink($text);
			}
			else
			{
				return $gallery->get_html();
			}
		}
	}

	function checkDir($path)
	{
		if ( is_dir($path) )
		{
			if ( is_readable($path) && is_writable($path) )
				return true;
			else
			{
				if ( @chmod($path, 0777) )
					return true;
				else
				{
					$this->error( sprintf(__('Directory <strong>%s</strong> is not writeable. Please set directory permissions to 777.'), $path) );
					return false;
				}
			}
		}
		else
		{
			$this->warning( sprintf(__('Directory <strong>%s</strong> does not exists.'), $path) );

			if ( @mkdir($path) )
			{
				@chmod($path, 0777);
				
				return true;
			}
			else
			{
				$this->error( sprintf(__('Unable to create directory <strong>%s</strong>. Please create directory with permissions 777 manually.'), $path) );
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

	function activate()
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
					`title`			VARCHAR(255) NOT NULL,
					`description`	TEXT NOT NULL,
					`preview`		VARCHAR(255) NOT NULL,
					`created`		DATETIME NOT NULL,
					`modified`		DATETIME NOT NULL,
					INDEX			(`order`, `author`, `title`, `created`, `modified`)
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
					INDEX		(`author`, `order`, `created`, `modified`)
				) DEFAULT CHARSET = {$wpdb->charset}
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
					`link`			VARCHAR(255) NOT NULL,
					`target`		VARCHAR(50) NOT NULL,
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

		$this->log( 'Activated '.FLGALLERY_NAME.' '.FLGALLERY_VERSION );
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
		if (defined('FLGALLERY_SMODE')) eval(FLGALLERY_SMODE);

		$prevVersion = get_option(FLGALLERY_NAME.'_version', 0);
		$prevVersionValue = flgallery_versionValue($prevVersion);
		$currentVersionValue = flgallery_versionValue(FLGALLERY_VERSION);
		if ( $currentVersionValue != $prevVersionValue )
		{
			if ( $prevVersionValue < 50000 )	
			{
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
						'link' =>			"VARCHAR(255) NOT NULL",
						'target' =>			"VARCHAR(50) NOT NULL",
						'width' =>			"INT UNSIGNED NOT NULL DEFAULT '0'",
						'height' =>			"INT UNSIGNED NOT NULL DEFAULT '0'",
						'size' =>			"INT UNSIGNED NOT NULL DEFAULT '0'",
					),
					array('id', 'album_id', 'gallery_id', 'order', 'type', 'path', 'title', 'size')
				);
				$this->upgradeTable(
					FLGALLERY_DB_PREFIX . FLGALLERY_DB_ALBUMS,
					array(
						'id' =>				"BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
						'order' =>			"BIGINT NOT NULL DEFAULT '0'",
						'author' =>			"BIGINT UNSIGNED NOT NULL",
						'title' =>			"VARCHAR(255) NOT NULL",
						'description' =>	"TEXT NOT NULL",
						'preview' =>		"VARCHAR(255) NOT NULL",
						'created' =>		"DATETIME NOT NULL",
						'modified' =>		"DATETIME NOT NULL",
					),
					array('order', 'author', 'title', 'created', 'modified')
				);
			}

			update_option(FLGALLERY_NAME.'_version', FLGALLERY_VERSION);
			$this->log( "Upgraded from {$prevVersion} to ".FLGALLERY_VERSION );
		}

		$this->points = array(
			-1241048294 => 1737499475,	
			-413307346 => -1806788956,	
			-153296339 => 1015077085,	
			1421159327 => -474770839,	
			-1408399113 => -1752565889	
		);
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

	function log( $text )
	{
		if ( $log = @fopen(FLGALLERY_LOG, 'a') )
		{
			fwrite( $log, date('Y-m-d H:i:s')."\t{$text}\n" );
			fclose( $log );
		}
	}

}


?>