<?php 
if (!defined('FLGALLERY_VERSION')) { header('HTTP/1.0 403 Forbidden'); exit('Access denied'); }

require_once ABSPATH.'wp-admin/includes/upgrade.php';
dbDelta("
	CREATE TABLE `{$table_name}` (
		`id`			BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		`order`			BIGINT NOT NULL DEFAULT '0',
		`author`		BIGINT UNSIGNED NOT NULL,
		`title`			VARCHAR(255) NOT NULL,
		`description`	TEXT NOT NULL,
		`preview`		VARCHAR(255) NOT NULL,
		`created`		DATETIME NOT NULL,
		`modified`		DATETIME NOT NULL,
		PRIMARY KEY		(`id`),
		KEY				(`order`, `author`, `created`, `modified`),
		KEY				(`title`)
	) DEFAULT CHARSET = {$wpdb->charset}
");


?>