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
 * @link https://github.com/leafo/lessphp
 * @link http://code.google.com/p/cssmin/
 * @author Andy Hausmann <andy.hausmann@gmx.de>
 * @copyright 2011 Andy Hausmann <andy.hausmann@gmx.de>
 * @version 0.5.0
 *
 * @todo Optimize mode handling
 * @todo Optimize debugging stuff
 * @todo Add ability of force recompiling
 * @todo Implement rendering mode 'both'
 */

// Bench: start time
$start = microtime(true);

// Include config
include ('./config.php');
// Init config
list ($debug, $err, $expires) = array(
	$config['debug'], 
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
if ( $config['check_import_mtime'] ) {
  if ( file_exists($config['lessFile']) ) {
    $less_file_dir = dirname($config['lessFile']);
    $mtimes = $config['lessFile'] . strval(filemtime($config['lessFile']));
    $files_found = array();
    function get_file_mtimes($file) {
      global $mtimes, $files_found, $less_file_dir;
      if ( ! isset($files_found[$file]) ) {
        $files_found[$file] = true;
        if ( file_exists($file) ) {
          $contents = file_get_contents($file);
          if ( preg_match_all('/\@import\s+[\'"](\S+)[\'"]/', $contents, $m) ) {
            foreach ( $m[1] as $import_file ) {
              get_file_mtimes($less_file_dir . '/' . $import_file);
            }
          } else {
            $mtimes .= $file . strval(filemtime($file)); 
          }  
        } else {
          $mtimes .= $file;
        }
      }
    }
    get_file_mtimes($config['lessFile']);
    $fingerprint = $mtimes;
  } else {
    $fingerprint = $config['lessFile'];
  }
} else {
  $fingerprint = (file_exists($config['lessFile']))
    ? $config['lessFile'] . filemtime($config['lessFile'])
    : $config['lessFile'];
}
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
	){
		ob_start('ob_gzhandler');
	} else{
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
	} elseif ($mode === 'demand'){
		$css = $DemandBridge->getCss();
		// Not needed because of etag implementation above
		//$etag = $DemandBridge->getFingerprint();

		header('Content-Type: text/css');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: ' .gmdate('D, d M Y H:i:s', (!empty($expires)) ? $expires : time()). ' GMT');
		//header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header("Vary: Accept-Encoding");
		header('ETag: ' . $etag);
		echo $css;

	}
}

// Bench: end time
$end = microtime(true);
