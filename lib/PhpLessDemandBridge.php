<?php
/**
 * Demand Bridge to use phpless in a customizable way
 *
 * Parsing/compiling stuff.
 *
 * @package PhpLessDemandBridge
 * @link https://github.com/MorphexX/PhpLessDemandBridge
 * @link https://github.com/leafo/lessphp
 * @link http://code.google.com/p/cssmin/
 * @author Andy Hausmann <andy.hausmann@gmx.de>
 * @copyright 2011-2012 Andy Hausmann <andy.hausmann@gmx.de>
 *
 * @todo Correct a bug which causes a rendering error in case of a missing slash at the end of a path
 * @todo PHPDoc
 */
class PhpLessDemandBridge
{
	/**
	 * Holds the LESS Bootstrap config - used to find and compile LESS.
	 *
	 * @var array
	 */
	protected $less = array(
		'path' => './files/',
		'name' => 'styles',
		'ext' => 'less'
	);

	/**
	 * Holds the CSS config - used to compile LESS and save the result into a CSS file.
	 *
	 * @var array
	 */
	protected $css = array(
		'path' => './files/',
		'name' => 'styles',
		'ext' => 'css'
	);

	/**
	 * Holds the cache file config - used for demand rendering
	 *
	 * @var array
	 */
	protected $cache = array(
		'path' => './cache/',
		'name' => 'styles',
		'ext' => 'cache'
	);

	/**
	 * Holds the Rendering config.
	 *
	 * @var array
	 */
	protected $rendering = array(
		'available' => array('demand', 'compile', 'both'),
		'fallback' => 'demand',
		'selected' => ''
	);

	/**
	 * CSS minification flag.
	 *
	 * @var bool
	 */
	protected $minify = FALSE;

	/**
	 * Holds the used class names
	 *
	 * This is required to check for class availability before starting the processes.
	 *
	 * @var array
	 */
	protected $classes = array(
		'lessc' => 'lessc',
		'cssmin' => 'cssmin'
	);

	/**
	 * Stores some kind of fingerprint for the etag
	 *
	 * @var string
	 */
	protected $fingerprint = '';


	/**
	 * Magical voodoo super function.
	 *
	 * Checks whether the LESS Compiler is available or not.
	 * If its not, the whole package cannot proceed and will throw an exception.
	 *
	 * @todo Overhaul this to check for/return an already cached file (.cache on demand) in case the compiler isn't available for some reason - on compile mode here should nothing else be done.
	 * @return \PhpLessDemandBridge
	 */
	public function __construct()
	{
		if (!class_exists($this->classes['lessc'])) {
			throw new exception("LESS Compiler (' . $this->classes['lessc'] . ') not found; cannot proceed.");
		}
	}

	/**
	 * Initialization of the whole Config.
	 *
	 * @param array $cfg Configuration Array found in ../config.php
	 * @return PhpLessDemandBridge
	 * @throws exception
	 */
	public function init($cfg)
	{
		$this->setRendering($cfg['mode']);
		$this->minify = intval($cfg['minify']);

		if ($filename = $this->setLess($cfg['lessFile'])) {

			$this->setCompilePath($cfg['compilePath']);
			$this->setFilenames($filename);

		} else {
			throw new exception("load error: failed to find or access LESS root file.");
		}

		return $this;
	}

	/**
	 * Fetches and returns the compiled CSS.
	 *
	 * @return string
	 */
	public function getCss()
	{
		return $this->processLess(TRUE);
	}

	/**
	 * Initiates the CSS compiler.
	 *
	 * return void
	 */
	public function compile()
	{
		$this->processLess();
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getFingerprint()
	{
		return $this->fingerprint;
	}

	/**
	 * @param $file
	 * @param $tstamp
	 * @deprecated
	 */
	protected function setFingerprint($file, $tstamp)
	{
		$this->fingerprint = md5($file . $tstamp);
	}

	/**
	 * Main LESS processing
	 *
	 * Check for timestamps and updates, if necessary, the .cache or .css storage file.
	 * If CSS minification has been activated, this will be done first, right before storing or returning the code.
	 *
	 * @param bool $return Flag to additionally return the compiled CSS
	 * @param bool $force Flag to force compilation and skip the cache
	 * @return void|string Compiled CSS
	 */
	public function processLess($return = FALSE, $force = FALSE)
	{
		// Get file references
		list ($less_fname, $css_fname, $cache_fname) = array(
			$this->getFileRefFromConfig('less'),
			$this->getFileRefFromConfig('css'),
			$this->getFileRefFromConfig('cache')
		);

		// Try to get a cached version of CSS
		if (file_exists($cache_fname)) {
			$contentCache = unserialize(file_get_contents($cache_fname));
		} else {
			$contentCache = $less_fname;
		}

		// Recompile LESS
		$contentNew = lessc::cexecute($contentCache);
		// Set Fingerpring
		$this->setFingerprint($less_fname, $contentNew['updated']);
		// CSS Minification
		if ($this->minify) $contentNew['compiled'] = $this->getMinified($contentNew['compiled']);

		if (!is_array($contentCache)
			|| $contentNew['updated'] > $contentCache['updated']
			|| !file_exists($css_fname)
			|| $force === TRUE
		) {
			$rendering = $this->getRendering();
			if ($rendering === 'demand' || $rendering === 'both')
				file_put_contents($cache_fname, serialize($contentNew));
			if ($rendering === 'compile' || $rendering === 'both')
				file_put_contents($css_fname, $contentNew['compiled']);
		}

		return ($return === TRUE)
			? $contentNew['compiled']
			: FALSE;

	}

	/**
	 * Minifies given CSS.
	 *
	 * The CSS minification is being done through the awesome cssmin.
	 *
	 * @param string $css Compiled raw CSS
	 * @return string The Minified CSS
	 */
	public function getMinified($css)
	{
		return (class_exists($this->classes['cssmin']))
			? cssmin::minify($css)
			: $css;
	}

	/**
	 * Provides a possibility to access the file configuration
	 *
	 * Returns either a specific config value of a file config,
	 * like the compile path of the css, or a completely build path to a file.
	 *
	 * @param string $prop Property/Keyword (less, css, cache)
	 * @param null $data Data/Keyword (path, name, ext)
	 * @return bool|string
	 */
	public function getFileRefFromConfig($prop, $data = NULL)
	{
		$prop =& $this->$prop;
		if (!empty($data) && isset($prop[$data])) {
			// Return specific value
			return $prop[$data];
		} elseif ($data === NULL && isset($prop)) {
			// Build and return complete path/to/file
			return $prop['path'] . $prop['name'] . '.' . $prop['ext'];
		} else return FALSE;
	}

	/**
	 * Sets the remaining file names (css and cache file)
	 *
	 * The CSS and Cache file names will be set depending on the LESS root filename.
	 *
	 * The Demand Bridge has the ability to parse different LESS roots defined by GET vars,
	 * so it is necessary to ensure some kind of filename dependency to avoid unintentional
	 * overwrites od already compiles CSS and Cache files.
	 *
	 * @param string $name LESS root filename
	 * @return void
	 */
	public function setFilenames($name)
	{
		list($this->css['name'], $this->cache['name']) = array($name, $name);
	}

	/**
	 * Sets the CSS compile path based on config
	 *
	 * @param string $path Path to compile the CSS file into
	 * @return void
	 */
	public function setCompilePath($path)
	{
		if (is_dir($path)) $this->css['path'] = $path;
	}

	/**
	 * Initializes the LESS root file
	 *
	 * Additionally sets some base configuration for LESS compilation and will return it's filename
	 * if the LESS root file is available and accessible.
	 *
	 * @param array $less LESS root file path, name and ext
	 * @return bool|string Depending on the availability of the LESS root file
	 */
	public function setLess($less)
	{
		if (file_exists($less) && is_readable($less)) {
			$fArr = $this->getFileInfo($less);
			$this->less = $fArr;
			return $fArr['name'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Rendering getter
	 *
	 * @return string
	 */
	public function getRendering()
	{
		return $this->rendering['selected'];
	}

	/**
	 * Rendering setter
	 *
	 * @param string $mode Rendering mode
	 * @return void
	 */
	public function setRendering($mode)
	{
		$this->rendering['selected'] = $this->validateRendering($mode);
	}

	/**
	 * Analyizes the given file reference
	 *
	 * @param string $fileRef Path/to/file
	 * @return array Fileinfo
	 */
	protected function getFileInfo($fileRef)
	{
		$fi = pathinfo($fileRef);
		return array(
			'path' => $fi['dirname'] . '/',
			'name' => $fi['filename'],
			'ext' => $fi['extension']
		);
	}

	/**
	 * Validates and sets the rendering
	 *
	 * @param string $mode Rendering
	 * @return string Configured rendering
	 */
	protected function validateRendering($mode)
	{
		return (in_array($mode, $this->rendering['available']))
			? $mode
			: $this->rendering['fallback'];
	}


	/**
	 * Is utilized for reading data from inaccessible members.
	 *
	 * @param $name string
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->$name;
	}


	/**
	 * Run when writing data to inaccessible members.
	 *
	 * @param $name string
	 * @param $value mixed
	 * @return void
	 */
	public function __set($name, $value)
	{
		$this->$name = $value;
	}

}
