<?php 
error_reporting(0);
ob_start();

header('Cache-Control: no-cache, must-revalidate');
header('Expires: '.date('r'));

if ( !empty($_REQUEST['id']) )
{
	require_once 'config.php';

	$gallery_id = (int)$_REQUEST['id'];

	preg_match('|(.*?/)(wp-content)/.*|i', str_replace('\\', '/', __FILE__), $m);
	$abspath = $m[1];
	$content_dir = $m[1].$m[2];

	$flgalleryXmlFile = $content_dir.'/'.FLGALLERY_CONTENT.'/'.FLGALLERY_XML."/{$gallery_id}.xml";
	if ( file_exists($flgalleryXmlFile) )
	{
		header('Content-Type: text/xml; charset=UTF-8');
		ob_end_clean();

		readfile($flgalleryXmlFile);
	}
	else
	{
		require_once $abspath.'wp-config.php';

		$flgalleryGallery = new flgalleryGallery( $gallery_id );

		header('Content-Type: text/xml; charset=UTF-8');
		ob_end_clean();

		$flgalleryGallery->xml(true);
	}
}
exit();


?>