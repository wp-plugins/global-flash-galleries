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

$swfURL = $gallery->get_swf();
if ( empty($_REQUEST['frontend']) && !preg_match('#/swf-commercial/'.preg_quote($gallery->type).'.swf$#', $swfURL) )
{
	$trialMessage = sprintf(
		_('Order the full version of %s to make possible display more than %d pictures.', 'flgallery'),
		'<a href="http://flash-gallery.com/wordpress-plugin/order/" target="_blank">'.
			$flgalleryPlugin->galleryInfo[$gallery->type]['title'].
		'</a>',
		$flgalleryPlugin->limitations[$gallery->type]
	);
}

;echo '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'; echo $gallery->name; ;echo '</title>
<style type="text/css">
body {
	margin: 0;
}
.trial {
	font-family: Tahoma, sans-serif;
	font-size: 11px;
	height: 25px;
	overflow: hidden;
	white-space: nowrap;
	background: #ffc;
	color: #000;
}
.trial div {
	padding: 5px 8px;
}
.trial a {
	color: #03c;
}
</style>
</head>

<body>
<table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0">
'; if (!empty($trialMessage)) : ;echo '<tr>
	<td class="trial"><div>'; echo $trialMessage; ;echo '</div></td>
</tr>
'; endif; ;echo '<tr>
	<td>'; echo $gallery->get_html(); ;echo '</td>
</tr>
</table>
</body>
</html>';
?>