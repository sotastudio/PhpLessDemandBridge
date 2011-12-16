<?php
/**
 * Demand Bridge to use phpless in a customizable way
 *
 * @package PhpLessDemandBridge
 * @link https://github.com/MorphexX/PhpLessDemandBridge
 * @author Andy Hausmann <andy.hausmann@gmx.de>
 * @copyright 2011 Andy Hausmann <andy.hausmann@gmx.de>
 * @version 0.1.0
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
		'path' 	=> './files/',
		'name' 	=> 'styles',
		'ext'	=> 'less'
	);


    /**
     * Holds the CSS config - used to compile LESS and save the result into a CSS file.
     *
     * @var array
     */
    protected $css = array(
        'path' 	=> './files/',
        'name' 	=> 'styles',
		'ext'	=> 'css'
    );


	/**
	 * Holds the cache file config - used for demand rendering
	 *
	 * @var array
	 */
	protected $cache = array(
		'path' => './cache/',
		'name' => 'styles',
		'ext'  => 'cache'
	);


    /**
     * Holds the Rendering config.
     *
     * @var array
     */
    protected $rendering = array(
        'available' => array('demand', 'compile', 'both'),
        'fallback'  => 'demand',
		'selected'	=> ''
    );


	protected $minify = FALSE;


	/**
	 * Holds the used class names
	 *
	 * This is required to check for class availability before starting the processes.
	 *
	 * @var array
	 */
	protected $classes = array(
		'lessc'		=> 'lessc',
		'cssmin'	=> 'cssmin'
	);


	/**
	 * Magical voodoo super function
	 *
	 * Checks whether the LESS Compiler is available or not.
	 * If its not, the whole package cannot proceed and will throw an exception.
	 *
	 * @todo Overhaul this to check for/return an already cached file (.cache on demand) in case the compiler isn't available for some reason - on compile mode here should nothing else be done.
	 * @param $cClass string LESS Compiler Clasname
	 * @return void
	 */
	public function __construct()
	{
		if (!class_exists($this->classes['lessc'])){
			throw new exception("LESS Compiler (' . $this->classes['lessc'] . ') not found; cannot proceed.");
			exit();
		}
	}


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


	public function getCss()
	{
		return $this->processLess(TRUE);
	}


	public function compile()
	{
		$this->processLess();
	}


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
		// CSS Minification
		if ($this->minify) $contentNew['compiled'] = $this->getMinified($contentNew['compiled']);

		if (!is_array($contentCache)
			|| $contentNew['updated'] > $contentCache['updated']
			|| !file_exists($css_fname)
			|| $force === TRUE)
		{
			$rendering = $this->getRendering();
			if ($rendering === 'demand' || $rendering === 'both')
				file_put_contents($cache_fname, serialize($contentNew));
			if ($rendering === 'compile' || $rendering === 'both')
				file_put_contents($css_fname, $contentNew['compiled']);
		}

		if ($return === TRUE) return $contentNew['compiled'];

	}


	public function getMinified($in)
	{
		return (class_exists($this->classes['cssmin']))
			? cssmin::minify($in)
			: $in;
	}


	public function getFileRefFromConfig($prop, $data = NULL)
	{
		$prop =& $this->$prop;
		if (!empty($data) && isset($prop[$data])) {
			return $prop[$data];
		} elseif ($data === NULL && isset($prop)) {
			return $prop['path'] . $prop['name'] . '.' . $prop['ext'];
		} else return FALSE;
	}


	public function setFilenames($name)
	{
		list($this->css['name'], $this->cache['name']) = array($name, $name);
	}


	public function setCompilePath($path)
	{
		if (is_dir($path)) $this->css['path'] = $path;
	}


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


	public function getRendering()
	{
		return $this->rendering['selected'];
	}


	public function setRendering($mode)
	{
		$this->rendering['selected'] = $this->validateRendering($mode);
	}


	private function getFileInfo($fileRef)
	{
		$fi = pathinfo($fileRef);
		return array(
			'path' 	=> $fi['dirname'] . '/',
			'name'  => $fi['filename'],
			'ext' 	=> $fi['extension']
		);
	}


	public static function debug($v)
	{
		echo '<pre>' . "\n";
		if (is_array($v)) {
			print_r($v);
		} else {
			var_dump($v);
		}
		echo '</pre>' . "\n";
	}


	/**
	 * @static
	 * @param $mode
	 * @return string
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



	/**
	 * Returns the compiled CSS code when it is converted to a string.
	 *
	 * @todo Implement logic
	 * @return string
	 */
	public function __toString()
	{
		// return demand obj.
	}

}
