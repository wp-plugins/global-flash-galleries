<?php

class flgalleryGalleryPopup
{
	function render()
	{
		global $flgalleryPlugin, $flgalleryProducts;

		$gallery_id = (int)$_REQUEST['gallery_id'];
		if (empty($gallery_id)) {
			exit;
		}

		$gallery = new flgalleryGallery($gallery_id);

		if (!file_exists($gallery->xmlFilePath)) {
			$gallery->getXml();
		}

		$gallery->width = '100%';
		$gallery->height = '100%';

		if (empty($_REQUEST['frontend']) && empty($flgalleryProducts[$gallery->getSignature()])) {
			$trialNotice = sprintf(
				__('Order the full version of %s to make it possible to display more than %d&nbsp;pictures.', 'flgallery'),
				'<a href="http://flash-gallery.com/wordpress-plugin/order/" target="_blank">' .
				$flgalleryPlugin->galleryInfo[$gallery->type]['title'] .
				'</a>',
				$flgalleryPlugin->limitations[$gallery->type]
			);
		} else {
			$trialNotice = '';
		}

		remove_action('wp_head', 'wp_admin_bar_header');
		remove_action('wp_head', '_admin_bar_bump_cb');
		remove_action('wp_footer', 'wp_admin_bar_render', 1000);
		add_filter('show_admin_bar', '__return_false');
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo esc_html($gallery->name); ?></title>
<style type="text/css">
body, * html body {
	margin: 0 !important;
}
html {
	margin: 0 !important;
}
#wpadminbar {
	display: none !important;
}
<?php if (!empty($trialNotice)): ?>
.trial {
	font-family: Tahoma, sans-serif;
	font-size: 11px;
	height: 25px;
	padding: 0;
	border: none;
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
<?php endif; ?>
.flgallery-embed {
	height: 100%;
}
</style>
<script type="text/javascript" src="<?php echo includes_url('js/jquery/jquery.js'); ?>"></script>
<script type="text/javascript" src="<?php echo includes_url('js/swfobject.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $flgalleryPlugin->getJsGalleryUrl(); ?>"></script>
</head>

<body>
<table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0">
<?php if (!empty($trialNotice)): ?>
<tr>
	<td class="trial"><div><?php echo $trialNotice; ?></div></td>
</tr>
<?php endif; ?>
<tr>
	<td><?php echo $gallery->getHtml(); ?></td>
</tr>
</table>
<?php wp_footer(); ?>
</body>
</html>
<?php
		exit;
	}
}
