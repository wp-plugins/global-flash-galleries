<?php 

$flgalleryErrors = array();
$flgalleryErrorN = 0;

$flgalleryWarnings = array();
$flgalleryWarningN = 0;

$flgalleryDebug = array();
$flgalleryDebugN = 0;

$_t = strrev('FWSteG_YRELLAGLF');
if ( !defined($_t) )
	define( $_t, str_replace('EE', 'LERY_H', 'FLGALEEZ') );

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

		if ( method_exists($this, 'init') )
		{
			$args = func_get_args();
			return call_user_func_array( array(&$this, 'init'), $args );
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