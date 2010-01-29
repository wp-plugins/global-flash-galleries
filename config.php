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
define( 'FLGALLERY_XML',		'xml'	);			
define( 'FLGALLERY_TPL',		'tpl'	);			

define( 'FLGALLERY_HREF', str_replace('%7E', '~', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].(empty($_REQUEST['page']) ? '' : '?page='.$_REQUEST['page'])) );


define( 'flgallery_defaultImageQuality', 85 );


?>