<?php namespace gimle\core;
/**
 * This files holds the Server class.
 *
 * @package core
 */
/**
 * Server class.
 */
class Server {
	/**
	 * Page array.
	 *
	 * @var array
	 */
	private static $_page = array();

	/**
	 * Initialization.
	 *
	 * @return void
	 */
	public static function initialize () {
		if (!defined('THIS_PATH')) {
			if ((isset($_SERVER['PATH_INFO'])) && (trim($_SERVER['PATH_INFO'], '/') != '')) {
				self::$_page = explode('/', trim($_SERVER['PATH_INFO'], '/'));
			}

			define('THIS_PATH', BASE_PATH . Server::pageString());
			define('THIS_PATH_LIVE', BASE_PATH_LIVE . Server::pageString());
		}
	}

	/**
	 * Retrieve all or part of the page url.
	 *
	 * @param bool|int $num Optional
	 * @return bool|string|array
	 */
	public static function page ($num = false) {
		if ($num !== false) {
			if (isset(self::$_page[$num])) {
				$return = self::$_page[$num];
			}
			else {
				$return = false;
			}
		}
		else {
			$return = self::$_page;
		}
		return $return;
	}

	/**
	 * Get the page url as string.
	 *
	 * @return string
	 */
	private static function pageString () {
		$array = self::page();
		return implode('/', $array) . (!empty($array) ? '/' : '');
	}
}
