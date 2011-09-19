<?php 

@ini_set('memory_limit', '256M');

define( 'FLGALLERY_NAME',		'flgallery' );


define( 'FLGALLERY_DEBUG',		isset($_GET['debug'])	);	
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


define( 'flgallery_defaultImageQuality', 85 );


if ( version_compare(PHP_VERSION, '5.1', '>=') )
	define( 'FLGALLERY_PHP5', true );
else
	define( 'FLGALLERY_PHP4', true );


?>