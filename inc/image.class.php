<?php 

class Image extends flgalleryBaseClass
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

	function resized($size)
	{
		include FLGALLERY_GLOBALS;

		$this->check();

		$newWidth = round( empty($size['width']) ? $this->width * ($size['height'] / $this->height) : $size['width'] );
		$newHeight = round( empty($size['height']) ? $this->height * ($size['width'] / $this->width) : $size['height'] );

		preg_match('/(.*)(\..*)/', $this->path, $fname);
		$newPath = $plugin->tmpDir."/{$fname[1]}-{$newWidth}x{$newHeight}{$fname[2]}";

		if ( !file_exists($newPath) )
		{
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
				$srcImage = $imagecreate($plugin->imgDir.'/'.$this->path);
				$dstImage = imagecreatetruecolor($newWidth, $newHeight);

				
				imagefill($dstImage, 0, 0, imagecolorallocate($dstImage, 0xFF, 0xFF, 0xFF) );
				
				imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $this->width, $this->height);

				if ( !$imageout($dstImage, $newPath, $quality) )
					return false;
			}
			else
				return false;
		}

		return $newPath;
	}
}


?>