<?php 

namespace Xiphe\ResponsiveImages\controllers;

/**
 * ResponsiveImages is a PHP Class served by !THE MASTER
 *
 * This class serves verry small images (50px width) on pageload and then loads
 * bigger versions later by using javascript.
 * It can use realpath or Wordpress attachement_IDs as source.
 *
 * @copyright Copyright (c) 2013, Hannes Diercks
 * @author    Hannes Diercks <xiphe@gmx.de>
 * @version   1.0.0
 * @link      https://github.com/Xiphe/ResponsiveImages/
 * @package   ResponsiveImages
 */
class Main {
	// Errors: 3

	/* ----------------- *
     *  CLASS CONSTANTS  *
     * ----------------- */

	const NS = 'Xiphe\ResponsiveImages\controllers';

    /* ------------------ *
     *  STATIC VARIABLES  *
     * ------------------ */

    /**
     * This is a singleton.
     * 
     * @var boolean
     */
	private static $_instance = null;

	/**
	 * Turns true after initiation.
	 *
	 * @var boolean
	 */
	private static $_initiated = false;

	/**
	 * The configuration.
	 *
	 * @var array
	 */
	private static $_configuration = array();

	/**
	 * Array of Callbacks
	 *
	 * @var array
	 */
	private static $_callbacks = array();

	/* -------------------- *
     *  INSTANCE VARIABLES  *
     * -------------------- */


	public $cacheLivetime = 0;

	private $tmpDir = false;

	private $_active = false;

	/**
	 * Action Hooks into Wordpress.
	 * @var array
	 */
	protected $actions_ = array(
		'wp_head',
		'wp_ajax_tm_responsiveimageget' => array(
			'wp_ajax_nopriv_tm_responsiveimageget',
			'wp_ajax_tm_responsiveimageget'
		),
		'wp_ajax_tm_responsiveslideshowget' => array(
			'wp_ajax_nopriv_tm_responsiveslideshowget',
			'wp_ajax_tm_responsiveslideshowget'
		),
		'wp_ajax_tm_responsiveimagetouched' => array(
			'wp_ajax_nopriv_tm_responsiveimagetouched',
			'wp_ajax_tm_responsiveimagetouched'
		),
		'shutdown'
	);

	protected $filters_ = array(
		'the_content'
	);

    /* -------------------- *
     *  INITIATION METHODS  *
     * -------------------- */

    public function __construct() {
    	if (null !== self::$_instance) {
    		return self::$_instance;
    	}

    	return $this;
    }

	/**
	 * First initiation.
	 * 
	 * @return void
	 */
	public function init()
	{	
		if (null === self::$_instance) {
			self::$_instance = new Main;
		}

		/*
		 * Do not initiate twice.
		 */
		if (self::$_initiated) {
			return $this;
		}
		self::$_initiated = true;

		/*
		 * Load Config
		 */
		self::$_configuration = json_decode(
			file_get_contents(XIPHE_RESPONSIVEIMAGES_BASEPATH . 'config.json')
		);

		$this
			->doCallback('configurationLoaded', array(self::$_configuration, $this))
			->checkNoJs();

		if (is_dir(XIPHE_RESPONSIVEIMAGES_BASEPATH . self::$_configuration->cacheDir)) {
			self::$_configuration->cacheDir = XIPHE_RESPONSIVEIMAGES_BASEPATH . 
				self::$_configuration->cacheDir;
		}

		if (!is_dir(self::$_configuration->cacheDir)) {
			throw new \ErrorException("Invalid cache directory.", 1);
		}

		if (!is_writable(self::$_configuration->cacheDir)) {
			throw new \ErrorException("Cache directory is not writable.", 2);
		}

		$this->doCallback('initiated');
	}

 	/* ------------------------ *
     *  CALLBACK FUNCTIONALITY  *
     * ------------------------ */

	public function doCallback($name, array $values)
	{	
		/*
		 * Hook into Wordpress if wanted.
		 */
		if (self::$_configuration->hookIntoWordpress && class_exists('\WP')) {
			call_user_func_array(
				'do_action',
				array_merge(
					(array) sprintf('xiphe_responsiveimges_%s', strtolower($name)),
					$values
				)
			);
		}

		/*
		 * Do own callbacks.
		 */
		if (empty(self::$_callbacks[$name])) {
			return $this;
		}

		foreach (self::$_callbacks[$name] as $prio => $callbacks) {
			foreach ($callbacks as $callback) {
				call_user_func_array($callback, $values);
			}
		}

		return $this;
	}

	public function addCallback($name, $callback, $prio = 10) {
		if (!is_callable($callback)) {
			throw new \Exception("Callback not callable.", 3);
			return false;
		}
		self::$_callbacks[$name][$prio] = $callback;
		sort(self::$_callbacks[$name]);
		return $this;
	}

	// TODO
	// public function removeCallback($name, $callback, $prio = 10) {
	// }

	/* -------------- *
     *  CONFIG STUFF  *
     * -------------- */

	public function getConfig($key)
	{
		return self::$_configuration->$key;
	}

	public function setConfig($key, $value)
	{
		self::$_configuration->$key = $value;
		return $this;
	}

	public function unsetConfig($key)
	{
		unset(self::$_configuration->$key);
		return $this;
	}


	/* -------------- *
     *  NOJS METHODS  *
     * -------------- */

	public function checkNoJs()
	{
		$key = sprintf('%snojsfallback', self::$_configuration->prefix);
		if (isset($_GET[$key]) && $_GET[$key] === 'now') {
			setcookie($key, 'active', time()+intval(self::$_configuration->nojsCookieDuration));
			$_COOKIE[$key] = 'active';
			$this->doCallback('nojsActivated');
		}
	}


	/* --------------- *
     *  CONTENT PARTS  *
     * --------------- */


	public function getJs($baseUrl, $format = 'html')
	{

	}

	public function getNojsFallback()
	{

	}

	public function getJsVars()
	{
		
	}


	public function wp_head()
	{
		if (!$this->_active) {
			return false;
		}

		if (!isset($_COOKIE['tmri_nojsfallback']) || $_COOKIE['tmri_nojsfallback'] !== 'active') {
			$noJsLink = add_query_arg('tmri_nojsfallback', 'now', X\THETOOLS::get_currentUrl());
			core\THEBASE::sget_HTML()->s_noscript()
				->rederect(array(
					'content' => '0; URL='.$noJsLink
				))
			->end();
		}
	}

    /* -------------- *
     *  AJAX METHODS  *
     * -------------- */

    /**
     * When the full-size image was directly found by javascript this ajax method
     * will be called to prevent the requested images from being deleted from cache.
     *
     * @return void
     */
    public function wp_ajax_tm_responsiveimagetouched()
    {
    	if (!$this->_active) {
			return false;
		}

    	$i = 0;

    	foreach ($_REQUEST['data'] as $image => $data) {
    		/*
			 * Verify Nonce.
			 */
			if (!X\THEWPTOOLS::verify_noprivnonce(
				esc_attr($data[key($data)]),
				'tm-responsive',
				$image
			)) {
				/*
				 * Nonce failed - continue with next image.
				 */
				continue;
			}

			$i++;

			/*
			 * Cleanup the path.
			 */
			$image = X\THETOOLS::get_directPath(esc_attr($image));

			/*
			 * If image is not an ID - Add the ABSPATH const.
			 */
			if (!is_numeric($image) && defined('ABSPATH')) {
				$image = ABSPATH.$image;
			}

			/*
			 * Get the real image file.
			 */
			$origin = $this->_get_baseImageFile($image);

			/*
			 * Register touches - touchedImages will be saved on shutdown.
			 */
			foreach ($data as $width => $nonce) {
				$image = $this->get_imagefile($origin, esc_attr($width));
				$this->touch($origin, $image);
			}
    	}

    	/*
    	 * Exit the script.
    	 */
    	if ($i > 0) {
			$this->_exit('ok', 'Touches registered.', 0);
    	} else {
			$this->_exit('error', 'Nothing touched.', 0);
    	}
    }

	/**
	 * Ajax getter for the full image url.
	 *
	 * @access public
	 * @return void
	 */
	public function wp_ajax_tm_responsiveimageget()
	{
		if (!$this->_active) {
			return false;
		}

		/*
		 * Cleanup the path.
		 */
		$img = X\THETOOLS::get_directPath(esc_attr($_REQUEST['image']));

		/*
		 * Verify Nonce.
		 */
		if (!isset($_REQUEST['nonce'])
		 || !X\THEWPTOOLS::verify_noprivnonce(
				esc_attr($_REQUEST['nonce']),
				'tm-responsive',
				$img
			)
		) {
			$this->_exit('error', 'Authentication failed.', 1);
		}

		/*
		 * If image is not an ID - Add the ABSPATH const.
		 */
		if (!is_numeric($img) && defined('ABSPATH')) {
			$img = ABSPATH.$img;
		}

		/*
		 * Get the requested image url and send it to the output array.
		 */
		$this->_r['uri'] = $this->get_url(
			$img,
			esc_attr($_REQUEST['width'])
		);

		/*
		 * Check if the image was available.
		 */
		if ($this->_r['uri'] == false) {
			$this->_exit('error', 'Image not available.', 2);
		}

		/*
		 * Exit script and print the json encoded output array.
		 */
		$this->_exit('ok', 'URI is attached.', 0);
	}

	/**
	 * Ajax getter for the next slideshow-image.
	 *
	 * @access public
	 * @return void
	 */
	public function wp_ajax_tm_responsiveslideshowget() {
		if (!$this->_active) {
			return false;
		}

		/*
		 * Verify nonce.
		 */
		if (!isset($_REQUEST['nonce'])
		 || !X\THEWPTOOLS::verify_noprivnonce(
				esc_attr($_REQUEST['nonce']),
				'tm-responsive',
				esc_attr($_REQUEST['image'])
			)
		) {
			$this->_exit('error', 'Authentication failed.', 2);
		}

		/*
		 * Get the next image and put it into the output array.
		 */
		$this->_r['img'] = trim($this->get_image(
			esc_attr($_REQUEST['image']),
			esc_attr($_REQUEST['width']),
			(isset($_REQUEST['class']) ? esc_attr($_REQUEST['class']) : null),
			(isset($_REQUEST['id']) ? esc_attr($_REQUEST['id']) : null),
			(isset($_REQUEST['alt']) ? esc_attr($_REQUEST['alt']) : null),
			(isset($_REQUEST['title']) ? esc_attr($_REQUEST['title']) : null)
		));

		/*
		 * Check if the image was available.
		 */
		if (empty($this->_r['img'])) {
			$this->_exit('error', 'Image not available.', 2);
		}

		/*
		 * Exit script and print the json encoded output array.
		 */
		$this->_exit('ok', 'Image is attached', 0);
	}


    /* ---------------- *
     *  GETTER METHODS  *
     * ---------------- */

    /**
     * Getter for an image url in the specified with.
     *
     * @access public
     * @param  string $image attachment ID or image path
     * @param  mixed  $width the targeted image width.
     * @return mixed         the image url or false if image not available.
     */
	public function get_url($image, $width = 'auto')
	{
		if (!$this->_active) {
			return false;
		}

		$origin = $image;
		if (!($image = $this->_get_baseImageFile($image))) {
			return false;
		}

		$height = $this->_get_dims($image, $width);
		if ($height === false) {
			return false;
		}

		return $this->_get_imageUrl($image, $width, $height);
	}

	/**
	 * Getter for the absolute image file.
	 *
	 * @access public
	 * @param  string  $image attachment ID or image path
	 * @param  mixed   $width the targeted image width.
	 * @param  boolean $round whether or not the end site should be rounded.
	 * @return bookean        the image file path or false if image is not available.
	 */
	public function get_imagefile($image, $width = 'auto', $round = true)
	{
		if (!$this->_active) {
			return false;
		}

		if (!($image = $this->_get_baseImageFile($image))) {
			return false;
		}
		$height = $this->_get_dims($image, $width, $round);
		if ($height === false) {
			return false;
		}
		return $this->_get_imageFile($image, $width, $height);
	}

	/**
	 * Getter for an array of tag attributes that should be attached to 
	 * an html-tag when its using $image as background image.
	 *
	 * @access public
	 * @param  string $image    attachment ID or image path
	 * @param  mixed  $maxWidth the targeted image width
	 * @return mixed            the attr array or false if image is not available.
	 */
	public function get_bg_imageAttrs($image, $maxWidth = 'auto')
	{
		if (!$this->_active) {
			return false;
		}

		$slideshow = $this->_is_slideshow($image);

		$origin = $image;
		if (!($image = $this->_get_baseImageFile($image))) {
			return false;
		}

		if (!is_numeric($origin) && defined('ABSPATH')) {
			$origin = str_replace(
				X\THETOOLS::unify_slashes(ABSPATH),
				'',
				X\THETOOLS::unify_slashes($origin)
			);
		}

		$ratio;
		$loadedHeight;
		$loadWidth = $this->_get_loadWidh($image, $maxWidth, $loadedHeight, $ratio);
		if ($loadWidth === false) {
			return array();
		}

		$url = $this->get_url(
			$image, 
			$loadWidth
		);
		if ($url === false) {
			return array();
		}

		return array(
			'style' => "background-image: url('$url');",
			'class' => 'tm-responsiveimage tm-responsivebgimage tm-loading',
			'data-ratio' => $ratio,
			'data-origin' => $origin,
			'data-loaded' => $loadWidth,
			'data-maxwidth' => $maxWidth,
			'data-nonce' => X\THEWPTOOLS::create_noprivnonce('tm-responsive', $origin),
		);
	}

	/**
	 * Getter for an responsive image tag.
	 *
	 * @access public
	 * @param  string  $image    attachment ID or image path.
	 * @param  mixed   $width    the targeted image width
	 * @param  mixed   $addClass optional additional classes for the img tag
	 * @param  string  $addId    optional id for the img tag
	 * @param  string  $alt      optional alt attr for the tag. Set to false to disable the alt.
	 * @param  string  $title    optional title attr for the tag. Set to false to disable the title.
	 * @return mixed             the image tag or false on error.
	 */
	public function get_image($image, $maxWidth = 'auto', $addClass = false, $addId = null, $alt = null, $title = null)
	{
		if (!$this->_active) {
			return false;
		}

		/*
		 * Check if $image is array or object initiate slideshow and use first entry as startimage.
		 */
		$slideshow = $this->_is_slideshow($image);

		/*
		 * Check if alt-text is provided or image meta-alt is provided.
		 */
		if ($alt === null && is_numeric($image)) {
			$alt = get_post_meta($image, '_wp_attachment_image_alt', true);
		}

		if ($title === null && is_numeric($image)) {
			$title = get_the_title($image);
		}

		/*
		 * Convert attachment ids into image paths and check if image file is existent.
		 */
		$origin = $image;
		if (!($image = $this->_get_baseImageFile($image))) {
			return false;
		}

		if (!is_numeric($origin) && defined('ABSPATH')) {
			$origin = str_replace(
				X\THETOOLS::unify_slashes(ABSPATH),
				'',
				X\THETOOLS::unify_slashes($origin)
			);
		}

		
		$loadHeight;
		$ratio;
		$loadWidth = $this->_get_loadWidh($image, $maxWidth, $loadHeight, $ratio);
		if ($loadWidth === false) {
			return '';
		}

		if (isset($_COOKIE['tmri_nojsfallback']) && $_COOKIE['tmri_nojsfallback'] === 'active') {
			$loadWidth = $maxWidth;
		}
		$url = $this->get_url(
			$image, 
			$loadWidth
		);

		if ($url === false) {
			return '';
		}

		$args = array(
			'src' => $url,
			'class' => trim('tm-loading tm-responsiveimage'.(!empty($addClass) ? ' '.str_replace('tm-responsiveimage', '', $addClass) : '')),
			'data-ratio' => $ratio,
			'data-origin' => $origin,
			'data-loaded' => $loadWidth,
			'data-maxwidth' => $maxWidth,
			'data-nonce' => X\THEWPTOOLS::create_noprivnonce('tm-responsive', $origin),
			'width' => '100%',
			'id' => $addId
		);
		if (isset($alt)) {
			$args['alt'] = $alt;
			$args['data-fixalt'] = true;
		}
		if (isset($title)) {
			$args['title'] = $title;
			$args['data-fixtitle'] = true;
		}
		if ($slideshow) {
			foreach ($slideshow as $k => $ss) {
				if (!is_numeric($ss) && defined('ABSPATH')) {
					$slideshow[$k] = str_replace(ABSPATH, '', $ss);
				}
			}
			$args['data-slideshow'] = implode(',', $slideshow);
			$args['data-slidenonce'] = X\THEWPTOOLS::create_noprivnonce('tm-responsive', $args['data-slideshow']);
		}
		return core\THEBASE::sget_HTML()->r_img($args);
	}

	/**
	 * Echo wrapper for $this->get_image();
	 * 
	 * @access public
	 * @param  string  $image    attachment ID or image path.
	 * @param  mixed   $width    the targeted image width
	 * @param  mixed   $addClass optional additional classes for the img tag
	 * @param  string  $addId    optional id for the img tag
	 * @param  string  $alt      optional alt attr for the tag. Set to false to disable the alt.
	 * @param  string  $title    optional title attr for the tag. Set to false to disable the title.
	 * @return void
	 */
	public function image($image, $width = 'auto', $addClass = false, $addId = null, $alt = null, $title = null) {
		if (!$this->_active) {
			return false;
		}

		echo $this->get_image($image, $width, $addClass, $addId, $alt, $title);
	}

	public function touch($original, $image)
	{
		if (!$this->_active) {
			return false;
		}

		$match = X\THETOOLS::getPathsBase($image, $original, true);

		$image = $this->compressPath($image, $original);

		$this->touchedImages[$match][$original][$image] = time();
	}

	public function compressPath($slave, $master)
	{
		if (!$this->_active) {
			return false;
		}

		$masterSub = dirname($master).DS.pathinfo($master, PATHINFO_FILENAME);
		$slave = str_replace('"', '&quot;', $slave);
		$slave = str_replace($masterSub, '"_"', $slave);

		return $slave;
	}

	public function extractPath($slave, $master)
	{
		if (!$this->_active) {
			return false;
		}

		$masterSub = dirname($master).DS.pathinfo($master, PATHINFO_FILENAME);
		$slave = str_replace('"_"', $masterSub, $slave);
		$slave = str_replace('&quot;', '"', $slave);

		return $slave;
	}


    /* ------------------ *
     *  INTERNAL METHODS  *
     * ------------------ */

    public static function the_content($content)
    {	
    	if (!self::inst()->_active) {
			return false;
		}

    	$PQ = X\THETOOLS::pq($content);
    	$HTML = core\THEBASE::sget_HTML();
    	foreach ($PQ->find('img') as $Img) {
			$cls = pq($Img)->attr('class');
			$m;
			if (preg_match('/wp-image-([0-9]+)/', $cls, $m)) {
				$w = pq($Img)->attr('width');
				$r = $HTML->ris_span(array(
					'class' => 'tm-responsiveimage_wrap',
					'style' => "display: inline-block; width: 100%; max-width: {$w}px;",
						
				));
				$r .= self::inst()->get_image(intval($m[1]), $w);
				$r .= $HTML->r_close();

				pq($Img)->replaceWith($r);
			}
    	}
    	$content = $PQ->htmlOuter();
    	return $content;
    }

    /**
     * Checks if any images were not touched in the last (cacheLivetime)
     * And tries to delete those.
     *
     * @return void
     */
    public static function checkCache()
    {	
    	if (!$this->_active) {
			return false;
		}

    	if ((!defined('DOING_CRON') || !DOING_CRON)
    		&& (!defined('Xiphe_THEDEBUG_ResponsiveImages_deleteall') || !Xiphe_THEDEBUG_ResponsiveImages_deleteall)
    	) {
    		return;
    	}


    	$obj = self::inst();

    	if (defined('Xiphe_THEDEBUG_ResponsiveImages_deleteall') && Xiphe_THEDEBUG_ResponsiveImages_deleteall) {
    		$obj->cacheLivetime = -1;
    	}

    	foreach ($obj->touchedImages as $basePath => $images) {
    		foreach ($images as $original => $data) {
    			$delete = !file_exists($basePath.$original);
    			foreach ($data as $image => $touched) {
    				if ($delete || (intval($touched) + $obj->cacheLivetime) < time()) {
    					$key = $image;
    					$image = $obj->extractPath($image, $original);
    					$file = $basePath.$image;
	    				if (file_exists($file) && is_writable($file)) {
	    					unlink($file);

	    					$empty = function ($dir) {
	    						$files = scandir($dir);
	    						$files = array_flip($files);
	    						unset($files['.']);
	    						unset($files['..']);
	    						return !count($files);
	    					};
	    					$parent = dirname($file);
	    					while($empty($parent) && is_writable($parent)) {
	    						rmdir($parent);
	    						$parent = dirname($parent);
	    					}
	    				}
	    				unset($obj->touchedImages[$basePath][$original][$key]);
	    				if (empty($obj->touchedImages[$basePath][$original])) {
	    					unset($obj->touchedImages[$basePath][$original]);
	    					if (empty($obj->touchedImages[$basePath])) {
	    						unset($obj->touchedImages[$basePath]);
	    					}
	    				}
    				}
    			}
    		}
    	}
    }

    /**
     * Gets the dimensions in wich the image should be loaded.
     *
     * @access private 
     * @param  string  $image   attachment ID or image path.
     * @param  mixed   $width   the targeted image width
     * @param  mixed   $height  the targeted image height
     * @return intager          the loading width.
     */
	private function _get_loadWidh($image, &$width, &$height, &$ratio)
	{
		/*
		 * Check if image should be delivered in full size directly.
		 * (drct) prefix before actual with.
		 */
		$direct = false;
		if (substr($width, 0, 4) == 'drct') {
			$direct = true;
			$width = intval(str_replace('drct', '', $width));
		}

		$maxWidth;

		/*
		 * Get the potential dimensions.
		 */		
		$height = $this->_get_dims($image, $width, false, $ratio, $maxWidth);
		if ($height === false) {
			return false;
		}
		/*
		 * Get the url of mini-thumb or direct full image if drct prefix was set.
		 */
		if ($direct) {
			$loadWidth = $width;
			$this->_get_dims($image, $loadWidth, false, $ratio, $maxWidth);
		} else {
			$loadWidth = 50;
		}
		$width = $maxWidth;
		return $loadWidth;		
	}

	/**
	 * Checks if the given image is single or slideshow.
	 * 
     * @access private 
	 * @param  string  $image  attachment ID or image path.
	 * @return boolean
	 */
	private function _is_slideshow(&$image)
	{
		$slideshow = false;
		if (is_string($image)) {
			$image = explode(',', $image);
		} else {
			return false;
		}

		if (count($image) > 1) {
			$slideshow = array();
			foreach ($image as $k => $img) {
				if (!isset($theimg)) {
					$theimg = $img;
				} else {
					$slideshow[] = $img;
				}
			}
			$image = $theimg;
			if (empty($slideshow)) {
				$slideshow = false;
			} else {
				$slideshow[] = $image;
			}
		} else {
			$image = $image[0];
		}
		return $slideshow;
	}

	/**
	 * Getter for the url of the image in the given dimensions
	 *
     * @access private 
	 * @param  string  $image   attachment ID or image path.
     * @param  mixed   $width   the targeted image width
     * @param  mixed   $height  the targeted image height
	 * @return string           the url
	 */
	private function _get_imageUrl($image, $width, $height)
	{
		return $this->_gen_imageUrlFrom(
			$this->_get_imageFile($image, $width, $height)
		);
	}

	/**
	 * Replaces the ABSPATH in given File with the wordpress installation url
	 * and cleans up the directory separators.
	 *
	 * @access private 
	 * @param  string $file the image file
	 * @return string       the image url
	 */
	private function _gen_imageUrlFrom($file)
	{
		$file = X\THETOOLS::unify_slashes($file);
		$abspath = X\THETOOLS::unify_slashes(ABSPATH);
		$url = X\THETOOLS::slash(get_bloginfo('wpurl'));

		$path = str_replace($abspath, '', $file);
		if ($path === $file) {
			$url = dirname($url);
			$path = str_replace(dirname($abspath), '', $file);
		}
		$url = X\THETOOLS::slash($url);

		$url .= X\THETOOLS::unPreSlash($path, true);

		return $url;
	}

	/**
	 * Getter for the real image filepath.
	 *
	 * If the image does not exist it will be generated.
	 *
	 * @access private 
	 * @param  string $image  the original image file
	 * @param  mixed  $width  the targeted width
	 * @param  mixed  $height the targeted height
	 * @return string         the image filepath
	 */
	private function _get_imageFile($image, $width, $height)
	{
		$file = $this->_build_imageFileName($image, $width, $height);
		if (!file_exists($file)) {
			$this->_gen_image($image, $file, $width, $height);
		}
		return $file;
	}

	/**
	 * Constructs the new image file name by adding -[width]x[height] to the end of the name.
	 * 
	 * @access private 
	 * @param  string $image  the original image file
	 * @param  mixed  $width  the targeted width
	 * @param  mixed  $height the targeted height
	 * @return string         the target image path
	 */
	private function _build_imageFileName($image, $width, $height) {
		$origin = $image;

		$path = str_replace(dirname($this->uploadDir), '', $image);
		if ($path === $image) {
			$path = str_replace(X\THETOOLS::unDS(ABSPATH, true), '', $image);
		}
		$path = dirname($path).DS;
		$file = pathinfo($image, PATHINFO_FILENAME).'-'.$width.'x'.$height.'.'.pathinfo($image, PATHINFO_EXTENSION);
		$image = X\THETOOLS::unDS($this->tmpDir).$path.$file;

		$this->touch($origin, $image);
		return $image;
	}

	/**
	 * If the targeed image does not exist - this method generates the new, resized image.
	 * 
	 * @access private 
	 * @param  string  $original path to the original image file
	 * @param  string  $target   path where the resized image should be stored
	 * @param  intager $width    the resize width
	 * @param  intager $height   the resize height
	 * @return boolean           true if the image creation was successfull
	 */
	private function _gen_image($original, $target, $width, $height)
	{
		$tmp = ini_get('memory_limit');
		ini_set('memory_limit', '1024M');

		$type = wp_check_filetype($original);
		$type = $type['type'];

		switch ($type) {
			case 'image/gif':
				$original = imagecreatefromgif($original);
				break;
			case 'image/png':
				$original = imagecreatefrompng($original);
				break;
			default:
				$original = imagecreatefromjpeg($original);
				break;
		}
		$new_image = imagecreatetruecolor($width, $height);

		if ($type == 'image/png' || $type == 'image/gif') {
			imagealphablending($new_image, false);
			imagesavealpha($new_image,true);
			$transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
			imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
		}

      	$sharpenMatrix = array(
            array(-1.2, -1, -1.2),
            array(-1, 20, -1),
            array(-1.2, -1, -1.2)
        );

        // calculate the sharpen divisor
        $divisor = array_sum(array_map('array_sum', $sharpenMatrix));           

        $offset = 0;
       
        // apply the matrix
        imageconvolution($original, $sharpenMatrix, $divisor, $offset); 

      	imagecopyresampled(
      		$new_image,
      		$original, 
      		0, 0, 0, 0,
      		$width,
      		$height,
      		imagesx($original),
      		imagesy($original)
      	);

      	if (!is_dir(dirname($target))) {
      		mkdir(dirname($target), 0777, true);
      	}

      	switch ($type) {
			case 'image/gif':
				$r = imagegif($new_image, $target);
				break;
			case 'image/png':
				$r = imagepng($new_image, $target, 0, PNG_NO_FILTER);
				break;
			default:
				$r = imagejpeg($new_image, $target, 100);
				break;
		}
		imagedestroy($original);
		imagedestroy($new_image);

		ini_set('memory_limit', $tmp);
		return $r;
	}

	/**
	 * Geter for the appropriate dimenseions of the target image.
	 * 
	 * @param  string  $image the original image file
	 * @param  mixed   $width the targeted image width
	 * @param  boolean $round whether or not the size should be rounded.
	 * @return intager        the height for the target image.
	 */
	private function _get_dims($image, &$width, $round = true, &$ratio = null, &$fullWidth = null)
	{
		$tmp = ini_get('memory_limit');
		ini_set('memory_limit', '1024M');
		$dims = @getimagesize($image);
		ini_set('memory_limit', $tmp);

		if (!is_array($dims)) {
			debug('Failed to receive image size from image: '.$image, 3);
			debug(@fileperms($image), 'fileperms');
			return false;
		}
		$height = $dims[1];
		$ratio = round($dims[0]/$dims[1], 4);

		$fullWidth = $dims[0];

		if ($width == 'auto') {
			$width = $dims[0];
			return $dims[1];
		}

		if (strstr($width, '%')) {
			$width = floatval('0.'.str_replace('%', '', $width));
			$width = round($dims[0]*$width);
		}


		if ($round) {
			if($width < 200) {
				$rnd = 50;
			} elseif($width < 1000) {
				$rnd = 100;
			} else {
				$rnd = 200;
			}
			$width = ceil(intval($width)/$rnd)*$rnd;
		}
		if ($width > $dims[0]) {
			$width = $dims[0];
			return $dims[1];
		}

		return round($width/$ratio);
	}

	/**
	 * Getter for the original image file.
	 * Converts attachment IDs into real image paths.
	 * 
	 * @param  string $image attachment ID or image path
	 * @return string        the original image path or false on error.
	 */
	private function _get_baseImageFile($image) {
		if (!is_int($image)) {
			if (file_exists($image)) {
				return $image;
			} 
			$image = intval($image);
		}
		$image = wp_get_attachment_metadata($image);
		if (!is_array($image)) {
			return false;
		}
		$ft = wp_check_filetype($image['file']);
		$fts = explode('/', $ft['type']);
		if ($fts[0] != 'image') {
			return false;
		} 
		$dirs = wp_upload_dir();
		return X\THETOOLS::DS(realpath($dirs['path'])).$image['file'];
	}

	public function shutdown() {
		update_option('Xiphe\THEMASTER\ResponsiveImages', $this->touchedImages);
	}
}
?>