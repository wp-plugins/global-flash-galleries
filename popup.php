<?php 
error_reporting(0);

$gallery_id = (int)$_REQUEST['id'];
if ( empty($gallery_id) )
	exit();

$wp_config = '../../../wp-config.php';
if ( !file_exists($wp_config) ) $wp_config = $_SERVER['DOCUMENT_ROOT'].'/wp-config.php';
require_once $wp_config;

$gallery = new flgalleryGallery($gallery_id);
$gallery->width = '100%';
$gallery->height = '100%';

;echo '<html>
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
'; echo $gallery->get_html(); ;echo '
</body>
</html>';
?>