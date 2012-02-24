<?php
/**
 * Demand Bridge to use phpless in a customizable way
 *
 * Config file to set program default
 *
 * This is required to ensure that this package will work properly; especially some paths need to be predefined.
 * Supported parsing modes are demand parsing, compiling and both parallel.
 *
 * The demand mode provided kinda fetching of really necessary GET vars to customize the rendering.
 * In this mode there are two important things:
 * - a cache file for faster return of teh css stuff
 * - returned headers to control caching and make this hole thing even faster
 *
 * The compile mode is actually the base functionality of phpless - of course it has been optimized a bit.
 *
 * @package PhpLessDemandBridge
 * @link https://github.com/MorphexX/PhpLessDemandBridge
 * @link https://github.com/leafo/lessphp
 * @link http://code.google.com/p/cssmin/
 * @author Andy Hausmann <andy.hausmann@gmx.de>
 * @copyright 2011-2012 Andy Hausmann <andy.hausmann@gmx.de>
 */

$config = array(

	// Rendering mode
	// String/keyword: demand, compile, both - can be overridden via GET var 'mode'
	'mode' => 'demand',
	// Boolean: Be aware of performance loss when activating!
	'recursiveChangeDetection' => 1,

	// LESS root file
	// String: path/to/file.less, relative to css.php - can be overridden via GET var 'file'
	'lessFile' => './files/styles.less',

	// Stylesheet compiling dir
	// String: path/to/dir/ to put the compiled CSS in, relative to css.php
	'compilePath' => './files/',

	// Misc stuff
	'expires' => '+1 month', // int: seconds to add / keyword: e.g. +1 month
	'minify' => 1, // boolean
	'debug' => 0, // boolean
);