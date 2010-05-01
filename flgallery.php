<?php
/*
Plugin Name: Global Flash Gallery
Plugin URI:  http://flash-gallery.com/wordpress-gallery
Description: In this WordPress plugin we joined several galleries that can be operated from a single shell. You can upload photos and use them in several galleries simultaneously. You can adjust the galleries' settings and publish them in your posts.
Version: 0.6.6
Author: flgallery
Author URI: http://flash-gallery.com/
*/

define( 'FLGALLERY_VERSION',	'0.6.6' );

require_once 'config.php';

define( 'FLGALLERY_FILE', __FILE__ );
define( 'FLGALLERY_HREF', str_replace('%7E', '~', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].(empty($_REQUEST['page']) ? '' : '?page='.$_REQUEST['page'])) );


define( 'FLGALLERY_SITE_DIR', realpath(ABSPATH) );
define( 'FLGALLERY_SITE_URL', get_option('siteurl') );

define( 'FLGALLERY_PLUGIN_DIR', dirname(__FILE__) );
define( 'FLGALLERY_PLUGIN_URL', plugins_url('', __FILE__) );

define( 'FLGALLERY_INCLUDE', FLGALLERY_PLUGIN_DIR.'/inc' );

define( 'FLGALLERY_GLOBALS', FLGALLERY_INCLUDE.'/globals.php' );

define( 'FLGALLERY_TPL_DIR', FLGALLERY_PLUGIN_DIR.'/tpl' );

define( 'FLGALLERY_CONTENT_DIR', WP_CONTENT_DIR.'/'.FLGALLERY_CONTENT );
define( 'FLGALLERY_CONTENT_URL', WP_CONTENT_URL.'/'.FLGALLERY_CONTENT );

define( 'FLGALLERY_LOG', FLGALLERY_CONTENT_DIR.'/log.txt' );

require_once FLGALLERY_INCLUDE.'/functions.php';
require_once FLGALLERY_INCLUDE.'/base.class.php';
require_once FLGALLERY_INCLUDE.'/plugin.class.php';

$flgalleryPlugin = new flgalleryPlugin();

register_activation_hook( __FILE__, array(&$flgalleryPlugin, 'activate') );


?>