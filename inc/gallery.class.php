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
		$xmlFilePath,
		$xmlFileURL;

	function init($a)
	{
		include FLGALLERY_GLOBALS;

		if ($plugin->userLevel >= 10)
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
			if ($plugin->userLevel < 10)
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
					`{$plugin->dbGalleries}`.`id` = `{$plugin->dbSettings}`.`gallery_id` AND
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

	function get_html()
	{
		include FLGALLERY_GLOBALS;

		return
			$func->insertFlash(
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
					'swfversion' => '9.0.45.0'
				),
				false
			);
	}

	function adminPreview($template, $echo = true)
	{
		if ( defined('WP_ADMIN') && $this->id )
		{
			include FLGALLERY_GLOBALS;
			$out = '';

			$imagesData = '';

			$images = $wpdb->get_results("
				SELECT *
				FROM `{$plugin->dbImages}`
				WHERE `gallery_id` = '{$this->id}'
				ORDER BY `order` ASC
			");
			if ($images !== false)
			{
				foreach ($images as $img)
				{
					$image = new flgalleryImage($img);

					$img->galleryID = $this->id;
					$img->url = $plugin->imgURL .'/'. $img->path;
					$thumbnail = $image->resized(array( 'height' => 120 ));
					$img->previewURL = $thumbnail ? $func->url($thumbnail) : $img->url;
					$img->href = $admpage->href;
					if ( empty($img->title) )
						$img->title = $func->filenameToTitle($img->name);

					$img->title = htmlspecialchars(stripslashes( $img->title ));
					$img->description = htmlspecialchars(stripslashes( $img->description ));

					$imagesData .= $tpl->parse('manage/image-preview', $img);
				}
			}

			$created = sprintf( __("%s @ %s", $plugin->name),
				mysql2date( $plugin->dateFormat, get_date_from_gmt($this->created) ),
				mysql2date( $plugin->timeFormat, get_date_from_gmt($this->created) )
			);
			$modified = sprintf( __("%s @ %s", $plugin->name),
				mysql2date( $plugin->dateFormat, get_date_from_gmt($this->modified) ),
				mysql2date( $plugin->timeFormat, get_date_from_gmt($this->modified) )
			);

			$out .= $tpl->parse(
				$template,
				array_merge(
					get_object_vars($this),
					array(
						'created' => $created,
						'modified' => $modified,
						'deleteGallery' => $admpage->actionButton(
							array('Delete', 'Delete Gallery'),
							'deleteGallery',
							array('gallery_id' => $this->id),
							sprintf(__('Delete Gallery? \n\n%s. "%s" by %s', $plugin->name), $this->id, $this->name, $this->authorName)
						),
						'options' => $admpage->actionButton(
							array('Options', 'Customize Flash Gallery'),
							'galleryOptions',
							array('gallery_id' => $this->id)
						),
						'pluginURL' => $plugin->url,
						'imgURL' => $plugin->imgURL,
						'href' => $admpage->href,
						'full' => !empty($_REQUEST['imgs']) && (int)$_REQUEST['gallery_id'] == $this->id ? 'full' : NULL,
						'images' => $imagesData,
						'galleryInfo' => $plugin->galleryInfo[$this->type],
						'popupJS' => $this->get_popupJS(),
						'hasItems' => count($images) > 0,
						'moreThanOneItem' => count($images) > 1
					)
				)
			);

			if ($echo) echo $out;
			return $out;
		}
		return false;
	}

	function get_swf()
	{
		include FLGALLERY_GLOBALS;

		$src = $plugin->galleryInfo[$this->type]['src'];

		if ( function_exists('flgallery_commercial_getSWF') )
			return flgallery_commercial_getSWF($src);

		
		
		
			$path = '/swf/'.$src;
			
			
				

			return FLGALLERY_PLUGIN_URL.$path;
		
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
		return "window.open('{$url}', '{$plugin->name}', 'location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no,left='+(screen.availWidth-{$this->width})/2+',top='+(screen.availHeight-{$this->height})/2+',width={$this->width},height={$this->height}'); return false;";
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

			$images = $wpdb->get_results("
				SELECT `album_id`, `path`, `name`, `title`, `description`, `link`, `target`
				FROM `{$plugin->dbImages}`
				WHERE `gallery_id` = '{$this->id}'
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
							$description = $func->filenameToTitle($img->name);
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