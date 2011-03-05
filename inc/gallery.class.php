<?php 

class flgalleryGallery extends flgalleryBaseClass
{
	var
		$id = 0,
		$type = 'default',
		$name = '',
		$author = 0,
		$authorName,
		$created = '',
		$modified = '',
		$order = 0,
		$width = 550,
		$height = 400,
		$settings = NULL,
		$settingsInfo = NULL,
		$settingsForm = NULL,
		$swfURL,
		$xmlFilePath,
		$xmlFileURL;

	function init($a)
	{
		include FLGALLERY_GLOBALS;

		if ($plugin->userLevel >= 5)
			$this->auth = ' 1 ';
		else
			$this->auth = " `{$plugin->dbGalleries}`.`author`='{$plugin->userID}' ";

		if ( is_object($a) )
			$a = get_object_vars($a);
		else if ( !is_array($a) )
			$a = array('id' => $a);

		if ( !empty($a['id']) )
		{
			$this->id = (int)$a['id'];
			if ( !$this->load() )
				$this->id = 0;
		}
		else
		{
			$this->create($a);
		}

		if ($this->id)
		{
			$filename = "/{$this->id}.xml";
			$this->xmlFilePath = $plugin->xmlDir.$filename;
			$this->xmlFileURL = $plugin->xmlURL.$filename;
		}
	}

	function create($a)
	{
		include FLGALLERY_GLOBALS;

		$this->id = 0;
		$this->type = empty($a['type']) ? 'default' : $a['type'];
		$this->name = empty($a['name']) ? __('New Gallery', $plugin->name) : $a['name'];
		$this->author = $plugin->userID;
		$this->modified = $this->created = date('Y-m-d H:i:s');
	}

	function load()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$gallery = $wpdb->get_row("
				SELECT *
				FROM `{$plugin->dbGalleries}`
				WHERE `id` = '{$this->id}'
			");
			if ( empty($gallery) )
			{
				$this->error( sprintf(__('Unable to load Gallery #%s (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error) );
				$this->debug( "SQL: {$wpdb->last_query}", array('Error', $this->errorN) );
				return false;
			}
			else
			{
				foreach ($gallery as $key => $val)
				{
					if ( isset($this->$key) )
						$this->$key = htmlspecialchars(stripslashes($val));
				}
				$author = get_userdata($this->author);
				$this->authorName = $author->display_name;
			}
		}
		else
			return false;

		return $this->id;
	}

	function save()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$conditions = array( 'id' => $this->id );
			if ($plugin->userLevel < 5)
				$conditions['author'] = $plugin->userID;

			$update = $wpdb->update(
				$plugin->dbGalleries,
				array(
					'order' => $this->order,
					'author' => $this->author,
					'type' => $this->type,
					'name' => $this->name,
					'width' => $this->width,
					'height' => $this->height,
					'created' => $this->created,
					'modified' => date('Y-m-d H:i:s'),
				),
				$conditions
			);
			if ($update === false)
			{
				$this->error($wpdb->last_error);
				$this->debug($wpdb->last_query, array('Error', $this->errorN));
				return false;
			}
			else
			{
				$this->clearXmlCache();
				return $this->id;
			}
		}
		else
			return $this->saveNew();
	}

	function saveNew()
	{
		include FLGALLERY_GLOBALS;

		$order = $wpdb->get_var("
			SELECT MAX(`order`) as `order`
			FROM `{$plugin->dbGalleries}`
		");
		if ( $order === false )
		{
			$order = 0;
			$this->warning($wpdb->last_error);
			$this->debug($wpdb->last_query);
		}

		$this->order = $order + 1;

		$insert = $wpdb->insert(
			$plugin->dbGalleries,
			array(
				'order' => $this->order,
				'type' => $this->type,
				'name' => $this->name,
				'author' => $this->author,
				'created' => $this->created,
				'modified' => date('Y-m-d H:i:s'),
				'width' => $this->width,
				'height' => $this->height
			)
		);
		if ( $insert === false )
		{
			$this->error($wpdb->last_error);
			$this->debug($wpdb->last_query, array('Error', $this->errorN));
			return false;
		}
		else
			return $wpdb->insert_id;

	}

	function delete()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$this->clearXmlCache();

			$res = $wpdb->query("
				DELETE FROM `{$plugin->dbGalleries}`
				WHERE `id` = '{$this->id}' AND {$this->auth}
			");
			if ($res !== false)
			{
				$delImages = $wpdb->query("
					DELETE FROM `{$plugin->dbImages}`
					WHERE `gallery_id` = '{$this->id}'
				");

				$delSettings = $wpdb->query("
					DELETE FROM `{$plugin->dbSettings}`
					WHERE `gallery_id` = '{$this->id}'
				");

				return $delImages !== false && $delSettings !== false;
			}
			else
			{
				$this->error( sprintf(__('Unable to delete Gallery #%s (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error) );
				$this->debug( "SQL: {$wpdb->last_query}", array('Error', $this->errorN) );
			}
		}
		return false;
	}

	function arrange($order)
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			if ( is_array($order) )
			{
				$IDs = "`id`='". implode("' OR `id`='", $order). "'";

				$images = $wpdb->get_results("
					SELECT `id`, `order`
					FROM `{$plugin->dbImages}`
					WHERE ({$IDs})
					ORDER BY `id`
				");
				if ( $images !== false )
				{
					$minOrder = (int)$wpdb->get_var("
						SELECT MIN(`order`)
						FROM `{$plugin->dbImages}`
						WHERE ({$IDs})
					");

					$result = array();

					foreach ($images as $image)
					{
						$imageOrder = array_search($image->id, $order) + $minOrder;
						$upd = $wpdb->update(
							$plugin->dbImages,
							array('order' => $imageOrder),
							array('id' => $image->id)
						);
						if ( $upd !== false)
							$result[$image->id] = $imageOrder;
					}

					return $result;
				}
			}
			else if ( is_string($order) )
			{
				switch ($order)
				{
					case 'desc':
						$order = 'DESC';
						break;
					default:
						$order = 'ASC';
				}
				$images = $wpdb->get_results("
					SELECT `id`
					FROM `{$plugin->dbImages}`
					WHERE `gallery_id` = '{$this->id}'
					ORDER BY `title` {$order}, `name` {$order}
				");
				if ($images !== false)
				{
					$result = array();

					$n = 0;
					foreach ($images as $image)
					{
						$upd = $wpdb->update(
							$plugin->dbImages,
							array('order' => $n++),
							array('id' => $image->id)
						);
						if ( $upd !== false)
							$result[$image->id] = $order;
					}

					return $result;
				}
			}
		}
		return false;
	}

	function get_settings($reload = false)
	{
		include FLGALLERY_GLOBALS;

		if ( $reload || empty($this->settings) )
		{
			$results = $wpdb->get_results("
				SELECT *
				FROM
					`{$plugin->dbGalleries}`,
					`{$plugin->dbSettings}`
				WHERE
					`{$plugin->dbGalleries}`.`id` = {$plugin->dbSettings}.`gallery_id` AND
					`{$plugin->dbSettings}`.`gallery_id` = '{$this->id}' AND
					`{$plugin->dbSettings}`.`gallery_type` = '{$this->type}'
			");
			if ( $results !== false )
			{
				foreach ($results as $res)
				{
					$this->settings[$res->name] = htmlspecialchars(stripslashes($res->value));
				}

				if ( !empty($plugin->galleryInfo[$this->type]['settings']->group) )
				{
					if ( defined('FLGALLERY_PHP5') )
					{
						$groups = &$plugin->galleryInfo[$this->type]['settings']->group;
					}
					else
					{
						if ( !is_array($plugin->galleryInfo[$this->type]['settings']->group) )
							$groups = array( $plugin->galleryInfo[$this->type]['settings']->group );
						else
							$groups = $plugin->galleryInfo[$this->type]['settings']->group;
					}

					foreach ( $groups as $group )
					{
						$groupAtt = $group->attributes();
						foreach ( $group->items->param as $param )
						{
							if ( is_object($param) )
								$paramAtt = $param->attributes();
							else
								$paramAtt = (object)$param;

							$name = (string)$groupAtt->name . '.' . (string)$paramAtt->name;

							if ( !isset($this->settings[$name]) )
								$this->settings[$name] = (string)$paramAtt->default;

							if ( defined('WP_ADMIN') )
							{
								$this->settingsInfo[$name] = $param;

								$this->settingsForm[$name] = array(
									'title' => htmlspecialchars( (string)$param->title ),
									'description' => isset($param->description) ? htmlspecialchars( (string)$param->description ) : '',
									'input' => $func->input($param->input, $name, "settings[$name]", $this->settings[$name])
								);
							}
						}
					}
				}

				if ( !empty($plugin->galleryInfo[$this->type]['settings']->param) )
				{
					if ( defined('FLGALLERY_PHP5') )
					{
						$params = &$plugin->galleryInfo[$this->type]['settings']->param;
					}
					else
					{
						if ( !is_array($plugin->galleryInfo[$this->type]['settings']->param) )
							$params = array( $plugin->galleryInfo[$this->type]['settings']->param );
						else
							$params = $plugin->galleryInfo[$this->type]['settings']->param;
					}

					foreach ( $params as $param )
					{
						if ( is_object($param) )
							$paramAtt = $param->attributes();
						else
							$paramAtt = (object)$param;

						$name = (string)$paramAtt->name;

						if ( !isset($this->settings[$name]) )
							$this->settings[$name] = (string)$paramAtt->default;

						if ( defined('WP_ADMIN') )
						{
							$this->settingsInfo[$name] = $param;

							$this->settingsForm[$name] = array(
								'title' => htmlspecialchars( (string)$param->title ),
								'description' => isset($param->description) ? htmlspecialchars( (string)$param->description ) : '',
								'input' => $func->input($param->input, $name, "settings[$name]", $this->settings[$name])
							);
						}
					}
				}
			}
			else
			{
				$this->error( sprintf(__('Unable to load Gallery #%s Settings (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error) );
				$this->debug( "SQL: {$wpdb->last_query}", array('Error', $this->errorN) );
				return false;
			}
		}
		return $this->settings;
	}

	function save_settings()
	{
		if ( $this->get_settings(false) )
		{
			include FLGALLERY_GLOBALS;

			$results = $wpdb->get_results("
				SELECT `id`, `name`
				FROM `{$plugin->dbSettings}`
				WHERE
					`gallery_id` = '{$this->id}' AND
					`gallery_type` = '{$this->type}'
			");
			if ($results)
			{
				foreach ($results as $res)
				{
					$keys[$res->name] = $res->id;
				}
			}

			foreach ($this->settings as $name => $value)
			{
				if ( isset($keys[$name]) )
				{
					$update = $wpdb->update(
						$plugin->dbSettings,
						array(
							'value' => $value
						),
						array(
							'id' => $keys[$name]
						)
					);
				}
				else
				{
					$insert = $wpdb->insert(
						$plugin->dbSettings,
						array(
							'gallery_id' => $this->id,
							'gallery_type' => $this->type,
							'name' => $name,
							'value' => $value
						)
					);
				}
			}
			$this->clearXmlCache();
			return true;
		}
		return false;
	}

	function reset_settings()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$this->clearXmlCache();

			return $wpdb->query("
				DELETE FROM `{$plugin->dbSettings}`
				WHERE
					`gallery_id` = '{$this->id}' AND
					`gallery_type` = '{$this->type}'
			");
		}
	}


	function get_items()
	{
		include FLGALLERY_GLOBALS;

		return $wpdb->get_results("
			SELECT *
			FROM `{$plugin->dbImages}`
			WHERE `gallery_id` = '{$this->id}'
			ORDER BY `order` ASC
		");
	}

	function get_swf()
	{
		if ( empty($this->swfURL) )
		{
			include FLGALLERY_GLOBALS;

			$src = $plugin->galleryInfo[$this->type]['src'];

			if ( function_exists('flgallery_commercial_getSWF') )
				return $this->swf = flgallery_commercial_getSWF($src);

			$c = md5($src);
			if ( !empty($plugin->points[$c]) )
			{
				$path = '/swf/'.$src;
				$stat = stat( FLGALLERY_PLUGIN_DIR.$path );
				if ( md5((string)$stat[7]) != $plugin->points[$c] )
					return '';

				return $this->swfURL = FLGALLERY_PLUGIN_URL.$path;
			}
		}
		return $this->swfURL;
	}

	function get_popupURL()
	{
		include FLGALLERY_GLOBALS;

		return $plugin->url."/popup.php?id={$this->id}";
	}

	function get_popupJS()
	{
		include FLGALLERY_GLOBALS;

		$url = $this->get_popupURL();

		$height = $this->height;

		if ( !defined('WP_ADMIN') )
			$url .= '&frontend=1';
		else
			$height += 25;

		return "window.open('{$url}', '{$plugin->name}', 'location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no,left='+(screen.availWidth-{$this->width})/2+',top='+(screen.availHeight-{$height})/2+',width={$this->width},height={$height}'); return false;";
	}

	function get_popupLink( $text )
	{
		$url = $this->get_popupURL();
		$onclick = $this->get_popupJS();

		if ( preg_match('/(.*?)#(.*?)#(.*)/s', $text, $m) )
		{
			$text_before = $m[1];
			$text = $m[2];
			$text_after = $m[3];
		}
		else
		{
			$text_before = '';
			$text_after = '';
		}

		return "{$text_before}<a href=\"{$url}\" onclick=\"{$onclick}\">{$text}</a>{$text_after}";
	}

	function get_html()
	{
		include FLGALLERY_GLOBALS;

		$items = $this->get_items();
		if ( $items !== false )
		{
			$altContent = "\n";
			foreach ($items as $item)
			{
				$image = new flgalleryImage($item);
				$thumbnail = $image->resized( array('height' => 120) );
				$thumbnailURL = $func->url($thumbnail);

				$item->title = htmlspecialchars($item->title);
				$item->description = htmlspecialchars($item->description);

				$altContent .= "\t\t<li><a href=\"{$plugin->imgURL}/{$item->path}\"><img src=\"{$thumbnailURL}\" alt=\"{$item->title}\" /></a>{$item->description}</li>\n";
			}
			$altContent = '<ol class="flgallery-altcontent">'.$altContent."\t</ol>";
		}
		else
		{
			$altContent = '<a class="flgallery-altcontent" href="http://www.adobe.com/go/getflashplayer" rel="nofollow"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a>';
		}

		$flash = $func->flash(
			$plugin->name.'-'.$this->id,	
			$this->get_swf(),				
			$this->width,					
			$this->height,
			array(
				'flashVars' => 'XMLFile='.$plugin->url.'/gallery-xml.php?id='.$this->id,
				'allowFullScreen' => 'true',
				'allowScriptAccess' => 'sameDomain',
				'quality' => 'high',
				'wmode' => 'transparent',
				'swfversion' => '9.0.45.0',
			),
			$altContent,
			false
		);

		$html = '<div class="flgallery-'.$this->id.' flgallery-'.strtolower($this->type).' flgallery-embed">'.$flash.'</div>';

		$html = preg_replace('/[\r\n\s\t]+/', ' ', $html);
		$html = preg_replace('/\s*([<>])\s*/', '$1', $html);

		return $html;
	}

	function get_xml()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$plugin->lastGalleryType = $this->type;

			$galleryTemplate = 'gallery-xml/'.$this->type.'.xml';
			$albumTemplate = 'gallery-xml/'.$this->type.'-album.xml';
			$itemTemplate = 'gallery-xml/'.$this->type.'-item.xml';

			$maxImageWidth = 2880;
			$maxImageHeight = $this->type == 'PhotoFlow' ? 1440 : 2880;

			$images = $wpdb->get_results("
				SELECT `album_id`, `path`, `name`, `title`, `description`, `link`, `target`
				FROM `{$plugin->dbImages}`
				WHERE
					`gallery_id` = '{$this->id}' AND
					`width` <= {$maxImageWidth} AND
					`height` <= {$maxImageHeight}
				ORDER BY `order`
			");
			if ($images !== false)
			{
				$a = $this->get_settings();
				$a['imagesFolder'] = $plugin->imgURL.'/';
				$a['thumbnailsFolder'] = $plugin->imgURL.'/';
				$a['soundsFolder'] = $plugin->contentURL.'/sounds/';
				$a['loader'] = 'true';
				$a['items'] = '';

				if ( $this->type == 'PhotoFlow' && $a['colorScheme'] == 'custom' )
					$a['custom'] = 'true';

				if ( $this->type == 'Zen' && $a['initialState'] == 'Show Albums' )
					$a['haveAlbums'] = true;

				foreach ($images as $img)
				{
					if ( !empty($img->description) )
					{
						$description = ( !empty($img->title) ? rtrim($img->title, '. ') . '. ' : '' ) . $img->description;
					}
					else
					{
						if ( !empty($img->title) )
							$description = $img->title;
						else
							$description = '';
							
					}
					$item = array(
						'source' => $img->path,
						'thumbnail' => '',
						'description' => htmlspecialchars(stripslashes( $description )),
						'link' => htmlspecialchars(stripslashes( $img->link )),
						'target' => $img->target
					);

					if ( !empty($a['haveAlbums']) )
						$albumItems[$img->album_id] .= $plugin->tpl->parse($itemTemplate, $item);
					else
						$a['items'] .= $plugin->tpl->parse($itemTemplate, $item);
				}

				if ( !empty($a['haveAlbums']) )
				{
					$a['albums'] = '';
					foreach ( $albumItems as $album_id => $items )
					{
						$description = $wpdb->get_var("
							SELECT `title`
							FROM `{$plugin->dbAlbums}`
							WHERE `id` = '{$album_id}'
						");

						$a['albums'] .= $plugin->tpl->parse($albumTemplate, array(
							'icon' => '',
							'thumbnailsFolder' => $plugin->imgURL.'/',
							'imagesFolder' => $plugin->imgURL.'/',
							'description' => htmlspecialchars(stripslashes( $description )),
							'items' => $items
						));
					}
				}

				$xml = $plugin->tpl->parse($galleryTemplate, $a);

				if ( function_exists('strisplashes') )
					$xml = strisplashes($xml);

				$this->cacheXML($xml);

				return $xml;
			}
		}
		return false;
	}

	function cacheXML($xml)
	{
		if ($this->id)
		{
			if ( $fp = fopen($this->xmlFilePath, 'w') )
			{
				$w = fwrite($fp, $xml);
				fclose($fp);
				return $w;
			}
		}
		return false;
	}

	function clearXmlCache()
	{
		if ($this->id)
		{
			if ( file_exists($this->xmlFilePath) )
				return unlink($this->xmlFilePath);
		}
		return false;
	}

}


?>