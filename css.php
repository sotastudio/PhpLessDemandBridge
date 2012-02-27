<?php
/**
 * Demand Bridge to use phpless in a customizable way
 *
 * Bootstrap / main logic
 *
 * Handling of rendering mode selection and everything else.
 *
 * @package PhpLessDemandBridge
 * @link https://github.com/andyhausmann/PhpLessDemandBridge
 * @link https://github.com/leafo/lessphp
 * @link http://code.google.com/p/cssmin/
 * @author Andy Hausmann <andy.hausmann@gmx.de>
 * @copyright 2011-2012 Andy Hausmann <andy.hausmann@gmx.de>
 */

// Bench: start time
$start = microtime(true);

// Include config and helper functions
include ('./lib/config.php');
include ('./lib/functions.php');

// Init config
list ($debug, $err, $expires) = array(
	$config['debug'], // not implemented yet
	FALSE,
	(is_int($config['expires']) ? time() + intval($config['expires']) : strtotime($config['expires']))
);


// Process GET var: file; contains the filename of the LESS root file
if (isset($_GET['file'])) {
	$less_ftr = htmlspecialchars($_GET['file']);
	$less_ftrFileInfo = pathinfo($config['lessFile']);
	$config['lessFile'] = $less_ftrFileInfo['dirname'] . '/' . $less_ftr;
}
// Process GET var: mode; contains the rendering mode
if (isset($_GET['mode'])) {
	$config['mode'] = htmlspecialchars($_GET['mode']);
}


// Fetch etag / kinda fingerprint to implement client-side caching
$fingerprint = (file_exists($config['lessFile']))
	? getFingerprint($config['lessFile'], intval($config['recursiveChangeDetection']))
	: $config['lessFile'];
$etag = md5($fingerprint);


if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
	// Browser already has the file so we tell him nothing changed and exit
	header('HTTP/1.1 304 Not Modified');
	exit();

} else {

	// Gzip output for faster transfer to client
	ini_set('zlib.output_compression', 2048);
	ini_set('zlib.output_compression_level', 4);
	if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])
		&& substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')
		&& function_exists('ob_gzhandler')
		&& !ini_get('zlib.output_compression')
		&& ((!ini_get('zlib.output_compression') || intval(ini_get('zlib.output_compression')) == 0))
	) {
		ob_start('ob_gzhandler');
	} else {
		ob_start();
	}

	// Include advanced libs
	include ('./lib/lessphp/lessc.inc.php');
	include ('./lib/CssMin.php');
	include ('./lib/PhpLessDemandBridge.php');

	// Try Container!
	$DemandBridge = new PhpLessDemandBridge();
	$DemandBridge->init($config);
	$mode = $DemandBridge->getRendering();


	if ($mode === 'compile') {
		$DemandBridge->compile();
	} elseif ($mode === 'demand') {
		$css = $DemandBridge->getCss();
		header('Content-Type: text/css');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: ' . gmdate('D, d M Y H:i:s', (!empty($expires)) ? $expires : time()) . ' GMT');
		header("Vary: Accept-Encoding");
		header('ETag: ' . $etag);
		echo $css;
	}
}

// Bench: end time
$end = microtime(true);