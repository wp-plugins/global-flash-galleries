<?php 

define( 'FLGALLERY_NAME',		'flgallery' );


define( 'FLGALLERY_DEBUG',		false	);		
define( 'FLGALLERY_WARNINGS',	true	);		
define( 'FLGALLERY_ERRORS',		true	);		


define( 'FLGALLERY_DB_PREFIX',		FLGALLERY_NAME.'_'	);	
define( 'FLGALLERY_DB_GALLERIES',	'galleries'	);			
define( 'FLGALLERY_DB_SETTINGS',	'settings'	);			
define( 'FLGALLERY_DB_IMAGES',		'images'	);			
define( 'FLGALLERY_DB_ALBUMS',		'albums'	);			


define( 'FLGALLERY_CONTENT',	FLGALLERY_NAME	);	
define( 'FLGALLERY_IMAGES',		'images'	);		
define( 'FLGALLERY_XML',		'xml'		);		
define( 'FLGALLERY_TPL',		'tpl'		);		
define( 'FLGALLERY_UPLOADS',	'uploads'	);		
define( 'FLGALLERY_TEMP',		'tmp'		);		


define( 'FLGALLERY_PLUGIN_DIR', dirname(__FILE__) );
define( 'FLGALLERY_PLUGIN_URL', plugins_url('', __FILE__) );

define( 'FLGALLERY_INCLUDE', FLGALLERY_PLUGIN_DIR.'/inc' );

define( 'FLGALLERY_GLOBALS', FLGALLERY_INCLUDE.'/globals.php' );

define( 'FLGALLERY_TPL_DIR', FLGALLERY_PLUGIN_DIR.'/tpl' );

define( 'FLGALLERY_CONTENT_DIR', WP_CONTENT_DIR.'/'.FLGALLERY_CONTENT );
define( 'FLGALLERY_CONTENT_URL', WP_CONTENT_URL.'/'.FLGALLERY_CONTENT );

define( 'FLGALLERY_LOG', FLGALLERY_CONTENT_DIR.'/log.txt' );


define( 'flgallery_defaultImageQuality', 85 );


?>