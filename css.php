<?php
/**
 * Demand Bridge to use phpless in a customizable way
 *
 * Bootstrap / main logic
 *
 * Handling of rendering mode selection and everything else.
 *
 * @package PhpLessDemandBridge
 * @link https://github.com/MorphexX/PhpLessDemandBridge
 * @author Andy Hausmann <andy.hausmann@gmx.de>
 * @copyright 2011 Andy Hausmann <andy.hausmann@gmx.de>
 * @version 0.1.0
 *
 * @todo Optimize GETvar-fetching to provide the ability to choose the rendering mode
 * @todo Optimite mode handling
 * @todo Optimize debugging stuff
 * @todo Add ability of force recompiling
 * @todo Implement Client-side caching, this implies: sending headers and usage of etag
 * @todo Implement rendering mode 'both'
 */

// Bench: start time
$start = microtime(true);


// Gzip output for faster transfer to client
ini_set('zlib.output_compression', 2048);
ini_set('zlib.output_compression_level', 4);
if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])
	&& substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')
	&& function_exists('ob_gzhandler')
	&& !ini_get('zlib.output_compression')
	&& ((!ini_get('zlib.output_compression') || intval(ini_get('zlib.output_compression')) == 0))
){
	ob_start('ob_gzhandler');
} else{
	ob_start();
}


// Include libs
include ('./config.php');
include ('./lib/lessphp/lessc.inc.php');
include ('./lib/CssMin.php');
include ('./lib/PhpLessDemandBridge.php');


// Init config
list($debug, $err) = array($config['debug'], FALSE);


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


// Try Container!
$DemandBridge = new PhpLessDemandBridge();
$DemandBridge->init($config);

$mode = $DemandBridge->getRendering();

if ($mode === 'compile') {
	$DemandBridge->compile();
} elseif ($mode === 'demand'){
	$css = $DemandBridge->getCss();
	echo $css;
}


// Bench: end time
$end = microtime(true);


/*
if (!$err) {
	// Collect debug info
	$times = array(
		'OLD__' => $tstamp['prev'],
		'NEW__' => $tstamp['cur'],
		'BS___' => $tstamp['root'],
		'BENCH' => $end - $start . ' s'
	);
	if ($debug) {
		print_r($times);
	}
}
*/