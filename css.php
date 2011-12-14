<?php
/**
 * Demand Bridge to use phpless in a customizable way
 *
 * @author Andy Hausmann <andy.hausmann@gmx.de>
 * @package lessphp
 * @subpackage PhpLessDemandBridge
 * @todo Fetch some GETVars to process a parsing mode and the LESS root
 * @todo Return or compile/save the LESS stuff
 */

// Bench: start time
$start = microtime(true);


// Gzip output for faster transfer to client
ini_set('zlib.output_compression', 2048);
ini_set('zlib.output_compression_level', 4);
if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) &&
	substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') &&
	function_exists('ob_gzhandler') &&
	!ini_get('zlib.output_compression') &&
	((!ini_get('zlib.output_compression') || intval(ini_get('zlib.output_compression')) == 0))
){
	ob_start('ob_gzhandler');
}
else{
	ob_start();
}


// Include libs
include('./config.php');
require_once($config['compiler']);


// Init file config
list($less_fname, $css_fname) = array($config['less'], $config['css']);


// Process files if passed through GETvars
if (isset($_GET['file'])) {
	// Handle LESS file trough request (ftr)
	$less_ftr = htmlspecialchars($_GET['file']);
	// Get files dir by config
	$less_ftrFileInfo = pathinfo($less_fname);
	// Update LESS root file in config
	$less_fname = $less_ftrFileInfo['dirname'] . '/' . $less_ftr;
}


// Let's roll!
if (file_exists($less_fname)) {
	// load the cache
	$cache_fname = $less_fname.".cache";
	if (file_exists($cache_fname)) {
		$cache = unserialize(file_get_contents($cache_fname));
	} else {
		$cache = $less_fname;
	}

	// Recompile and save if its necessary
	$new_cache = lessc::cexecute($cache);
	// Collect timestamps
	$cache_tstamp['prev'] = &$cache['updated'];
	$cache_tstamp['cur'] = &$new_cache['updated'];
	if (!is_array($cache) || $cache_tstamp['cur'] > $cache_tstamp['prev']) {
		file_put_contents($cache_fname, serialize($new_cache));
		// Compile Stylesheet
		//file_put_contents($css_fname, $new_cache['compiled']);
	}
}


// Bench: end time
$end = microtime(true);

// Collect debug info
$times = array(
	'OLD__' => $cache_tstamp['prev'],
	'NEW__' =>$cache_tstamp['cur'],
	'BS___' => filemtime($less_fname),
	'BENCH' => $end - $start . ' s'
);

unset($cache, $new_cache);