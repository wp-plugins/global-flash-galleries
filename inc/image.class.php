<?php 

class flgalleryImage extends flgalleryBaseClass
{
	var
		$id,
		$album_id,
		$gallery_id,
		$order,
		$type,
		$path,
		$name,
		$title,
		$description,
		$link,
		$target,
		$width,
		$height,
		$size;

	function init($a)
	{
		if ( is_object($a) )
			$a = get_object_vars($a);
		else if ( !is_array($a) )
			$a = array('id' => $a);

		$this->set($a);
	}

	function check()
	{
		include FLGALLERY_GLOBALS;

		$fullPath = $plugin->imgDir .'/'. $this->path;
		if ( file_exists($fullPath) )
		{
			$save = false;
			if ( empty($this->type) || empty($this->width) || empty($this->height) )
			{
				if ( $imagesize = getimagesize($fullPath) )
				{
					list($this->width, $this->height) = $imagesize;
					$this->type = $imagesize['mime'];

					$save = true;
				}
			}
			if ( empty($this->size) )
			{
				$this->size = filesize($fullPath);
				$save = true;
			}
			if ($save)
				$this->save();
		}
		else
		{
			$this->error("File not exists: ".$fullPath);
			return false;
		}
	}

	function set($a)
	{
		foreach ($a as $key => $value)
			$this->$key = $value;
	}

	function load()
	{
		if ( $this->id = (int)$this->id )
		{
			include FLGALLERY_GLOBALS;

			$image = $wpdb->get_row("
				SELECT * FROM `{$plugin->dbImg}`
				WHERE `id` = '{$this->id}'
			");
			if ($image !== false)
			{
				$this->set($image);
			}
			else
			{
				$this->error( sprintf(__('Unable to load Image #%s (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error) );
				$this->debug( "SQL: {$wpdb->last_query}", array('Error', $this->errorN) );
				return false;
			}
		}
		else
			return false;

		return $this->id;
	}

	function save()
	{
		if ( $this->id = (int)$this->id )
		{
			include FLGALLERY_GLOBALS;

			$update = $wpdb->update( $plugin->dbImg,
				array(
					'album_id' => (int)$this->album_id,
					'gallery_id' => (int)$this->gallery_id,
					'order' => (int)$this->order,
					'type' => $this->type,
					'path' => $this->path,
					'name' => $this->name,
					'title' => $this->title,
					'description' => $this->description,
					'link' => $this->link,
					'target' => $this->target,
					'width' => (int)$this->width,
					'height' => (int)$this->height,
					'size' => (int)$this->size
				),
				array( 'id' => $this->id )
			);
			if ($update === false)
			{
				$this->error( sprintf(__('Unable to save Image #%s (DB Error: %s)', $plugin->name), $this->id, $wpdb->last_error) );
				$this->debug( "SQL: {$wpdb->last_query}", array('Error', $this->errorN) );
				return false;
			}
		}
		else
			return false;

		return $this->id;
	}

	function addToBlacklist($path)
	{
		include FLGALLERY_GLOBALS;

		$blacklist = explode("\n", @file_get_contents($plugin->imgBlacklistPath));

		$key = array_search($path, $blacklist);
		if ($key === false)
		{
			$blacklist[] = $path;
			return file_put_contents( $plugin->imgBlacklistPath, trim(implode("\n", $blacklist)) );
		}
	}

	function removeFromBlacklist($path)
	{
		include FLGALLERY_GLOBALS;

		$blacklist = explode("\n", @file_get_contents($plugin->imgBlacklistPath));

		$key = array_search($path, $blacklist);
		if ($key !== false)
		{
			unset( $blacklist[$key] );
			return file_put_contents( $plugin->imgBlacklistPath, trim(implode("\n", $blacklist)) );
		}
	}

	function inBlacklist($path)
	{
		include FLGALLERY_GLOBALS;

		$blacklist = explode("\n", @file_get_contents($plugin->imgBlacklistPath));

		return array_search($path, $blacklist);
	}

	function resized($size)
	{
		if ( $this->inBlacklist($this->path) !== false )
			return false;

		include FLGALLERY_GLOBALS;

		$this->check();

		$newWidth = round( empty($size['width']) ? $this->width * ($size['height'] / $this->height) : $size['width'] );
		$newHeight = round( empty($size['height']) ? $this->height * ($size['width'] / $this->width) : $size['height'] );

		preg_match('/(.*)(\..*)/', $this->path, $fname);
		$newPath = $plugin->tmpDir."/{$fname[1]}-{$newWidth}x{$newHeight}{$fname[2]}";

		if ( !file_exists($newPath) )
		{
			$this->addToBlacklist($this->path);	

			switch ($this->type)
			{
				case 'image/gif':
					$imagecreate = 'imagecreatefromgif';
					$imageout = 'imagegif';
					$quality = 100;
					break;
				case 'image/jpeg':
					$imagecreate = 'imagecreatefromjpeg';
					$imageout = 'imagejpeg';
					$quality = empty($size['quality']) ? flgallery_defaultImageQuality : $size['quality'];
					break;
				case 'image/png':
					$imagecreate = 'imagecreatefrompng';
					$imageout = 'imagepng';
					$quality = 7;
					break;
				default:
					return false;
			}

			if ( function_exists($imagecreate) && function_exists($imageout) )
			{
				
				$memoryNeed = ($this->width * $this->height * 5) + ($newWidth * $newHeight * 5) + 1048576;
				if ( $func->get_freeMemory() < $memoryNeed )
				{
					$currentLimit = $func->mToBytes( @ini_get('memory_limit') );
					$newLimit = $memoryNeed + memory_get_usage() + 1048576;
					if ( $newLimit > $currentLimit )
						@ini_set( 'memory_limit', $newLimit );
				}
				if ( $func->get_freeMemory() < $memoryNeed )
					return false;

				$srcImage = $imagecreate($plugin->imgDir.'/'.$this->path);
				$dstImage = imagecreatetruecolor($newWidth, $newHeight);

				
				imagefill($dstImage, 0, 0, imagecolorallocate($dstImage, 0xFF, 0xFF, 0xFF) );
				
				imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $this->width, $this->height);

				imagedestroy($srcImage);

				$res = $imageout($dstImage, $newPath, $quality);

				imagedestroy($dstImage);

				if ( !$res )
					return false;
			}
			else
				return false;

			$this->removeFromBlacklist($this->path);	
		}

		return $newPath;
	}
}


?>