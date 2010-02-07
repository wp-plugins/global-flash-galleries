<?php 

global $wpdb, $flgalleryPlugin;

$plugin = &$flgalleryPlugin;
$func = &$flgalleryPlugin->func;
$tpl = &$flgalleryPlugin->tpl;
$media = &$flgalleryPlugin->media;

if ( defined('WP_ADMIN') )
{
	$admin = &$flgalleryPlugin->admin;
	$admpage = &$flgalleryPlugin->admin->page;
}


?>