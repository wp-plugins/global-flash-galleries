<?php 
if (!defined('WP_ADMIN')) { header('HTTP/1.0 403 Forbidden'); exit('Access denied'); }

require_once ABSPATH.'wp-admin/includes/upgrade.php';
dbDelta("
	CREATE TABLE `{$table_name}` (
		`id`			BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		`album_id`		BIGINT UNSIGNED NOT NULL,
		`gallery_id`	BIGINT UNSIGNED NOT NULL,
		`order`			BIGINT NOT NULL DEFAULT '0',
		`type`			VARCHAR(50) NOT NULL,
		`path`			VARCHAR(255) NOT NULL,
		`name`			VARCHAR(255) NOT NULL,
		`title`			VARCHAR(255) NOT NULL,
		`description`	TEXT NOT NULL,
		`link`			VARCHAR(255) NOT NULL,
		`target`		VARCHAR(50) NOT NULL,
		`width`			INT UNSIGNED NOT NULL DEFAULT '0',
		`height`		INT UNSIGNED NOT NULL DEFAULT '0',
		`size`			INT UNSIGNED NOT NULL DEFAULT '0',
		PRIMARY KEY		(`id`),
		KEY				(`album_id`, `gallery_id`, `order`, `type`, `size`),
		KEY				(`path`),
		KEY				(`title`)
	) DEFAULT CHARSET = {$wpdb->charset}
");


?>