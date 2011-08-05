<?php 
error_reporting(0);

$gallery_id = (int)$_REQUEST['id'];
if ( empty($gallery_id) )
	exit();

require_once 'wp-load.php';

$gallery = new flgalleryGallery($gallery_id);
$gallery->width = '100%';
$gallery->height = '100%';

$swfURL = $gallery->get_swf();
if ( empty($_REQUEST['frontend']) && !preg_match('#/swf-commercial/'.preg_quote($gallery->type).'.swf$#', $swfURL) )
{
	$trialNotice = sprintf(
		__('Order the full version of %s to make it possible to display more than %d&nbsp;pictures.', 'flgallery'),
		'<a href="http://flash-gallery.com/wordpress-plugin/order/" target="_blank">'.
			$flgalleryPlugin->galleryInfo[$gallery->type]['title'].
		'</a>',
		$flgalleryPlugin->limitations[$gallery->type]
	);
}
else
	$trialNotice = '';

remove_action('wp_head', 'wp_admin_bar_header');
remove_action('wp_head', '_admin_bar_bump_cb');
remove_action('wp_footer', 'wp_admin_bar_render', 1000);
add_filter('show_admin_bar', '__return_false');

;echo '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'; echo $gallery->name; ;echo '</title>
'; wp_head(); ;echo '<style type="text/css">
body, * html body {
	margin: 0 !important;
}
html {
	margin: 0 !important;
}
#wpadminbar {
	display: none !important;
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
.flgallery-embed {
	height: 100%;
}
</style>
</head>

<body>
<table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0">
'; if (!empty($trialNotice)) : ;echo '<tr>
	<td class="trial"><div>'; echo $trialNotice; ;echo '</div></td>
</tr>
'; endif; ;echo '<tr>
	<td>'; echo $gallery->get_html(); ;echo '</td>
</tr>
</table>
'; wp_footer(); ;echo '</body>
</html>';
?>