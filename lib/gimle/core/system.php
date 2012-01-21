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

	private static $_sqlconnections = array();

	public static $config = array();

	/**
	 * Autoload.
	 *
	 * @param string $name
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
			self::$_sqlconnections[$key] = new Mysql(System::$config['db'][$key]);
		}
		return self::$_sqlconnections[$key];
	}

	/**
	 * Execute an external program.
	 *
	 * @param string $exec Command
	 * @return array
	 */
	public static function run ($exec) {
		$filename = tempnam(TEMP_DIR, 'tmp_');
		touch($filename);
		exec($exec . ' 2> ' . $filename, $stout, $return);
		$sterr = explode("\n", trim(file_get_contents($filename)));
		unlink($filename);
		return array('command' => $exec, 'stout' => $stout, 'sterr' => $sterr, 'return' => $return);
	}
}
