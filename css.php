<?php

$lessCfg = array(
	'compiler' => './lessphp/lessc.inc.php',
	'bootstrap' => './files/bootstrap.less',
	'stylesheet' => './files/style.css',
);

require_once($lessCfg['compiler']);

class PhpLessDemandBridge extends lessc
{
	function __construct()
	{
		
	}
}

$lessDemandObj = new PhpLessDemandBridge();

try {

} catch (exception $e) {

}

unset($lessCfg, $lessDemandObj);