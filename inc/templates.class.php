<?php 

class flgalleryTemplates extends flgalleryBaseClass
{
	var
		$dir,
		$templates = array();
	var
		$vars,
		$tags;

	function init($dir = 'tpl', $vars = array(), $tags = '{}')
	{
		$this->dir = $dir;
		$this->tags = $tags;

		if ( is_object($vars) )
			$vars = get_object_vars($vars);

		if ( is_array($vars) )
			$this->vars = $vars;
	}

	function parse($templateName, $a = NULL, $echo = false, $tags = NULL)
	{
		return $this->t($templateName, $a, $echo, $tags);
	}

	function t($templateName, $a = array(), $echo = true, $tags = NULL)
	{
		$t = &$this->templates[$templateName];
		if ( !isset($t) )
		{
			$templateFile = ($path = $this->dir.'/'.$templateName);
			if (!file_exists($templateFile)) $templateFile = $path.'.html';
			if (!file_exists($templateFile)) $templateFile = $path.'.xml';
			if (!file_exists($templateFile)) $templateFile = $path.'.tpl';
			if (!file_exists($templateFile))
			{
				$t = NULL;
				$this->error( sprintf('Template not found: <strong>%s</strong>', $templateName) );
				return false;
			}

			$t = file_get_contents($templateFile);
		}

		if ( !empty($t) )
		{
			if ( is_object($a) ) $a = get_object_vars($a);

			if ( is_array($a) )
			{
				if ( count($this->vars) )
					$a = array_merge($this->vars, $a);

				if ( empty($tags) )
					$tags = &$this->tags;

				$out = $this->_fast($t, $a, $tags, $this->tplCache[$templateName]);
			}
			else
				$out = &$t;

			if ( $echo )
				echo $out;
		}
		else
			return NULL;

		return $out;
	}


	function _fast($text, &$a, &$tags, &$cache)
	{
		$qtags = array( preg_quote($tags[0]), preg_quote($tags[1]) );

		$m = &$cache['tags'];
		if ( isset($m) || preg_match_all('#'.$qtags[0].'(IF|TRUE|FALSE)\s+?(.*?)\s*?'.$qtags[1].'(.*?)'.$qtags[0].'/(IF|TRUE|FALSE)'.$qtags[1].'#ms', $text, $m_tags) && $m = $m_tags )
		{
			foreach ($m[0] as $i => $val)
			{
				if ( $m[1][$i] == $m[4][$i] )
				{
					switch ( $m[1][$i] )
					{
						case 'IF':
							if ( preg_match_all('#([\w]+[\w\d\._]*|[\W]+)\s*#', $m[2][$i], $vars) )
							{
								$result = false;
								$prevOp = '|';
								foreach ($vars[1] as $varName)
								{
									switch ($varName)
									{
										case '** ':
											$prevOp = '&';
											break;

										case '|| ':
											$prevOp = '|';
											break;

										default:
											$varValue = $this->_getElement($varName, $a);
											switch ($prevOp)
											{
												case '|':
													$result = $result || !empty($varValue);
													break;

												case '&':
												default:
													$result = $result && !empty($varValue);
													break;
											}
									}
								}
								if ( $result )
									$text = str_replace($val, $m[3][$i], $text);
								else
									$text = str_replace($val, '', $text);
							}
							break;

						case 'TRUE':
							$e = $this->_getElement($m[2][$i], $a);
							if ( !empty($e) && strtolower($e) != 'false' )
								$text = str_replace($val, $m[3][$i], $text);
							else
								$text = str_replace($val, '', $text);
							break;

						case 'FALSE':
							$e = $this->_getElement($m[2][$i], $a);
							if ( empty($e) || strtolower($e) == 'false' )
								$text = str_replace($val, $m[3][$i], $text);
							else
								$text = str_replace($val, '', $text);
							break;
					}
				}
			}
		}

		$m = &$cache['vars'];
		if ( isset($m) || preg_match_all('#'.$qtags[0].'([\w_]+[\w\d\._]*)'.$qtags[1].'#', $text, $m_vars) && $m = $m_vars )
		{
			if ( is_object($a) ) $a = get_object_vars($a);
			if ( is_array($a) )
			{
				$keys = array_unique($m[1]);
				foreach ($keys as $key)
				{
					if ( ($value = array_key_exists($key, $a) ? $a[$key] : false) !== false || (($value = $this->_getElement($key, $a)) !== false ) )
						$text = str_replace($tags[0].$key.$tags[1], (string)$value, $text);
				}
			}
		}

		return $text;
	}

	function _getElement($e, &$a)
	{
		$b = $a;
		$p = explode('.', $e);

		$k2 = '';
		foreach ($p as $key)
		{
			if ( is_object($b) ) $b = get_object_vars($b);

			if ( array_key_exists($key, $b) ) {
				$b = &$b[$key];
				$k2 = '';
			}
			else {
				$k2 .= $key;
				if ( array_key_exists($k2, $b) ) {
					$b = &$b[$k2];
					$k2 = '';
				}
				else
					$k2 .= '.';
			}
		}
		return is_array($b) ? false : $b;
	}
}


?>