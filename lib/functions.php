<?php
/**
 * Demand Bridge to use phpless in a customizable way
 *
 * Helper functions
 *
 * @package PhpLessDemandBridge
 * @link https://github.com/andyhausmann/PhpLessDemandBridge
 * @link https://github.com/leafo/lessphp
 * @link http://code.google.com/p/cssmin/
 * @author Andy Hausmann <andy.hausmann@gmx.de>
 * @copyright 2011-2012 Andy Hausmann <andy.hausmann@gmx.de>
 */

/**
 * Fingerprint Getter - used for Caching
 *
 * Fetches fingerprints based on file timestamps.
 * These timestamps are being written to a string which results in a md5'd hash for the eTag later on.
 *
 * @param $file Less file to receive the fingerprint from.
 * @param null $importPath Path to the Less file
 * @param bool $recursive Flag to activate recursive timestamp checks
 * @return string
 */
function getFingerprint($file, $recursive = FALSE) {

	if (file_exists($file)) {
		$fp = '';
		$fp .= strval(filemtime($file));

		if ($recursive) {

			$importPath = dirname($file);
			$contents = file_get_contents($file);

			if ( preg_match_all('/\@import\s+[\'"](\S+)[\'"]/', $contents, $m) ) {
				foreach ( $m[1] as $importFile ) {
					$fp .= strval(getFingerprint($importPath . '/' . $importFile));
				}
			}

		}

		return $fp;

	} else {
		throw new exception("load error: failed to find or access LESS file to receive fingerprint from: " . $file);
	}

}