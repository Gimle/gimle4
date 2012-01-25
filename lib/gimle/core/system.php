<?php namespace gimle\core;
/**
 * This files holds the System class.
 *
 * @package core
 */
/**
 * System class.
 */
class System {
	/**
	 * Array containing the search paths for autoloading.
	 *
	 * @var array
	 */
	public static $autoloadPrependPaths = array(SITE_DIR, CORE_DIR);

	/**
	 * Array containing values from the config files.
	 *
	 * @var array
	 */
	public static $config = array();

	/**
	 * Array holding the initialized mysql connections.
	 *
	 * @var array
	 */
	private static $_sqlconnections = array();

	/**
	 * Autoload.
	 *
	 * @param string $name
	 * @return void
	 */
	public static function autoload ($name) {
		foreach (static::$autoloadPrependPaths as $autoloadPrependPath) {
			$file = $autoloadPrependPath . 'lib' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, strtolower($name)) . '.php';
			if (file_exists($file)) {
				require $file;
				if (method_exists($name, 'initialize')) {
					call_user_func(array($name, 'initialize'));
				}
				break;
			}
		}
	}

	/**
	 * Create a new or return already initialized database object.
	 *
	 * @param string $key the database key.
	 * @return object Database object.
	 */
	public static function mysql ($key) {
		if ((!array_key_exists($key, self::$_sqlconnections)) || (!self::$_sqlconnections[$key] instanceof Mysql)) {
			self::$_sqlconnections[$key] = new Mysql(System::$config['mysql'][$key]);
		}
		return self::$_sqlconnections[$key];
	}

	/**
	 * Colorize a string according to the envoriment settings.
	 *
	 * @todo Check enviroment settings.
	 *
	 * @param string $content
	 * @param string $color
	 * @param string $background
	 * @return string
	 */
	public static function colorize ($content, $color, $background) {
		$template = '<span style="color: %s;">%s</span>';
		if (substr($color, 0, 6) === 'range:') {
			$config = json_decode(substr($color, 6), true);
			if ($config['type'] === 'alert') {
				$state = ($config['value'] / $config['max']);
				if ($state >= 1) {
					return sprintf($template, '#ff0000', $content);
				}
				elseif ($state === 0.5) {
					return sprintf($template, '#ffff00', $content);
				}
				elseif ($state < 0.5) {
					return sprintf($template, '#' . str_pad(dechex(round($state * 511)), 2, '0', STR_PAD_LEFT) . 'ff00', $content);
				}
				else {
					$state = (0.5 - ($state - 0.5));
					return sprintf($template, '#ff' . str_pad(dechex(round(($state) * 511)), 2, '0', STR_PAD_LEFT) . '00', $content);
				}
			}
		}
		elseif ($color === 'gray') {
			return sprintf($template, 'gray', $content);
		}
		elseif ($color === 'string') {
			return sprintf($template, 'green', $content);
		}
		elseif ($color === 'int') {
			return sprintf($template, 'red', $content);
		}
		elseif ($color === 'lightgray') {
			if ($background === 'black') {
				return sprintf($template, 'darkgray', $content);
			}
			return sprintf($template, 'lightgray', $content);
		}
		elseif ($color === 'bool') {
			return sprintf($template, 'purple', $content);
		}
		elseif ($color === 'float') {
			return sprintf($template, 'dodgerblue', $content);
		}
		elseif ($color === 'error') {
			return sprintf($template, 'deeppink', $content);
		}
		elseif ($color === 'recursion') {
			return sprintf($template, 'darkorange', $content);
		}
		elseif ($background === 'black') {
			return sprintf($template, 'white', $content);
		}
		else {
			return $content;
		}
	}
}
