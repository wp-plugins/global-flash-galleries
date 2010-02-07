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
			$this->auth = " `{$plugin->dbGal}`.`author`='{$plugin->userID}' ";

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
				FROM `{$plugin->dbGal}`
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

	function saveNew()
	{
		include FLGALLERY_GLOBALS;

		$order = $wpdb->get_var("
			SELECT MAX(`order`) as `order`
			FROM `{$plugin->dbGal}`
		");
		if ( $order === false )
		{
			$order = 0;
			$this->warning($wpdb->last_error);
			$this->debug($wpdb->last_query);
		}

		$this->order = $order + 1;

		$insert = $wpdb->insert(
			$plugin->dbGal,
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

	function save()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$conditions = array( 'id' => $this->id );
			if ($plugin->userLevel < 10)
				$conditions['author'] = $plugin->userID;

			$update = $wpdb->update(
				$plugin->dbGal,
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

	function getSettings($reload = false)
	{
		include FLGALLERY_GLOBALS;

		if ( $reload || empty($this->settings) )
		{
			$results = $wpdb->get_results("
				SELECT *
				FROM
					`{$plugin->dbGal}`,
					`{$plugin->dbSettings}`
				WHERE
					`{$plugin->dbGal}`.`id` = `{$plugin->dbSettings}`.`gallery_id` AND
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
				foreach ( $plugin->galleryInfo[$this->type]['settings']->group as $group )
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

						$this->settingsInfo[$name] = $param;

						$this->settingsForm[$name] = array(
							'title' => htmlspecialchars( (string)$param->title ),
							'description' => htmlspecialchars( (string)$param->description ),
							'input' => $func->input($param->input, $name, "settings[$name]", $this->settings[$name])
						);
					}
				}
				if ( !empty($plugin->galleryInfo[$this->type]['settings']->param) )
				foreach ( $plugin->galleryInfo[$this->type]['settings']->param as $param )
				{
					if ( is_object($param) )
						$paramAtt = $param->attributes();
					else
						$paramAtt = (object)$param;

					$name = (string)$paramAtt->name;

					if ( empty($this->settings[$name]) )
						$this->settings[$name] = (string)$paramAtt->default;

					if ( defined('WP_ADMIN') )
					{
						$this->settingsInfo[$name] = $param;

						$this->settingsForm[$name] = array(
							'title' => htmlspecialchars( (string)$param->title ),
							'description' => htmlspecialchars( (string)$param->description ),
							'input' => $func->input($param->input, $name, "settings[$name]", $this->settings[$name])
						);
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

	function saveSettings()
	{
		if ( $this->getSettings(false) )
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

	function delete()
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$this->clearXmlCache();

			$res = $wpdb->query("
				DELETE FROM `{$plugin->dbGal}`
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

	function html($template, $echo = true)
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;
			$out = '';

			if ( defined('WP_ADMIN') )
			{
				$imagesData = '';

				$images = $wpdb->get_results("
					SELECT *
					FROM `{$plugin->dbImg}`
					WHERE `gallery_id` = '{$this->id}'
					ORDER BY `order` ASC
				");
				if ($images !== false)
				{
					foreach ($images as $img)
					{
						$image = new Image($img);

						$img->galleryID = $this->id;
						$img->url = $plugin->imgURL .'/'. $img->path;
						$img->previewURL = $func->url( $image->resized(array( 'height' => 120 )) );
						$img->href = $admpage->href;
						if ( empty($img->title) )
							$img->title = $func->filenameToTitle($img->name);

						$img->title = htmlspecialchars(stripslashes( $img->title ));
						$img->description = htmlspecialchars(stripslashes( $img->description ));

						$imagesData .= $tpl->parse('manage/image-preview', $img);
					}
				}

				$out .= $tpl->parse(
					$template,
					array_merge(
						array(
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
							'galleryInfo_preview' => $plugin->galleryInfo[$this->type]['preview'],
							'popupJS' => $plugin->popupJS($this)
						),
						get_object_vars($this)
					)
				);
			}
			else
			{
			}

			if ($echo) echo $out;
			return $out;
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

	function xml($echo = false)
	{
		if ($this->id)
		{
			include FLGALLERY_GLOBALS;

			$plugin->lastGalleryType = $this->type;

			$galleryTemplate = 'gallery-xml/'.$this->type.'.xml';
			$itemTemplate = 'gallery-xml/'.$this->type.'-item.xml';

			$images = $wpdb->get_results("
				SELECT `path`, `name`, `title`, `description`
				FROM `{$plugin->dbImg}`
				WHERE `gallery_id` = '{$this->id}'
				ORDER BY `order`
			");
			if ($images !== false)
			{
				$a = $this->getSettings();
				$a['imagesFolder'] = $plugin->imgURL.'/';
				$a['thumbnailsFolder'] = $plugin->imgURL.'/';
				$a['soundsFolder'] = $plugin->contentURL.'/sounds/';
				$a['loader'] = 'true';
				$a['items'] = '';

				if ( $this->type == 'PhotoFlow' && $a['colorScheme'] == 'custom' )
					$a['custom'] = 'true';

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
						'link' => ''
					);
					$a['items'] .= $plugin->tpl->parse($itemTemplate, $item);
				}

				$xml = $plugin->tpl->parse($galleryTemplate, $a);
				$xml = strisplashes($xml);
				$this->cacheXML($xml);

				if ($echo)
					echo $xml;

				return $xml;
			}
		}
		return false;
	}
}


?>