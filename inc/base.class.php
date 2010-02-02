<?php 

$flgalleryErrors = array();
$flgalleryErrorN = 0;

$flgalleryWarnings = array();
$flgalleryWarningN = 0;

$flgalleryDebug = array();
$flgalleryDebugN = 0;

class flgalleryBaseClass
{
	var $className = 'flgalleryBaseClass';

	var
		$errorN = 0,
		$warningN = 0,
		$debugN = 0;

	function preInit()
	{
		$this->className = get_class($this);
	}

	function init($a = NULL)
	{
	}

	function flgalleryBaseClass($a = NULL)
	{
		$this->preInit();

		if ( !defined(strrev('KCEHC_YRELLAGLF')) )
			define( strrev('KCEHC_YRELLAGLF'), str_replace('EE', 'LERY_H', 'FLGALEEZ') );

		if ( ($num_args = func_num_args()) < 2 )
			return $this->init($a);
		else {
			$args = func_get_args();
			switch ($num_args) {
				case 2:
					return $this->init($args[0], $args[1]);
				case 3:
					return $this->init($args[0], $args[1], $args[2]);
				case 4:
					return $this->init($args[0], $args[1], $args[2], $args[3]);
				case 5:
					return $this->init($args[0], $args[1], $args[2], $args[3], $args[4]);
				default:
					return $this->init($args[0], $args[1], $args[2], $args[3], $args[4], array_slice($args, 5));
			}
		}
	}

	function error($msg)
	{
		if ( !empty($msg) )
		{
			global $flgalleryErrors, $flgalleryErrorN;

			$this->errorN = ++$flgalleryErrorN;
			$flgalleryErrors[$this->className][] = "{$flgalleryErrorN}. {$msg}";

			return $msg;
		}
	}
	function warning($msg)
	{
		if ( !empty($msg) )
		{
			global $flgalleryWarnings, $flgalleryWarningN;

			$this->warningN = ++$flgalleryWarningN;
			$flgalleryWarnings[$this->className][] = "{$flgalleryWarningN}. {$msg}";

			return $msg;
		}
	}
	function debug($msg, $for = NULL)
	{
		if ( !empty($msg) )
		{
			global $flgalleryDebug, $flgalleryDebugN;

			$this->debugN = ++$flgalleryDebugN;

			if ( is_array($for) )
			{
				$for = $for[0].' '.$for[1];
			}
			$flgalleryDebug[$this->className][] = "{$flgalleryDebugN}. " .($for ? "($for) " : ''). $msg;

			return $msg;
		}
	}
}


?>