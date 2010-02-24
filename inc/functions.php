<?php 

if (!function_exists('flgallery_versionValue')) :
function flgallery_versionValue($version)
{
	$value = 0;
	$ver = explode('.', $version);
	$l = count($ver) - 1;
	for ($i = $l; $i >= 0; $i--)
		$value += $ver[$i] * pow(100, 3-$i);

	return $value;
}
endif;

if (!function_exists('file_put_contents')) :
function file_put_contents($filename, $data)
{
	$fp = fopen($filename, 'rb');
	$res = fwrite($fp, $data);
	fclose($fp);

	return $res;
}
endif;

if (!function_exists('memory_get_usage')) :
function memory_get_usage($real_usage = false)
{
	return 0;
}
endif;

if (!function_exists('strisplashes')) :
function strisplashes($text) {
	return $text;
}
endif;


if (!function_exists('print_pre')) {
function print_pre($var)
{
	print '<pre>';
	print_r($var);
	print '</pre>';
}}


?>