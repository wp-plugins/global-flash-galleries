<?php 
error_reporting(0);

$gallery_id = (int)$_REQUEST['id'];
if ( empty($gallery_id) )
	exit();

$wp_config = '../../../wp-config.php';
if ( !file_exists($wp_config) ) $wp_config = $_SERVER['DOCUMENT_ROOT'].'/wp-config.php';
require_once $wp_config;

$gallery = new flgalleryGallery($gallery_id);
$galleryHTML = $flgalleryPlugin->flashGallery( array('id' => $gallery->id) );

;echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'; echo $gallery->name; ;echo '</title>
<style type="text/css">
body {
	margin: 0;
}
</style>
</head>

<body>
'; echo $galleryHTML; ;echo '
</body>
</html>';
?>