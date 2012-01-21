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
	 * Parse a ini file and keep typecasting.
	 *
	 * @param string $filename
	 */
	public static function parseIniFile ($filename) {
		$return = array();
		$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($lines === false) {
			return false;
		}
		if (!empty($lines)) {
			foreach ($lines as $linenum => $linestr) {
				if (substr($linestr, 0, 1) === ';') {
					continue;
				}
				$line = explode(' = ', $linestr);
				$key = trim($line[0]);
				if ((isset($line[1])) && (substr($key, 0, 1) !== '[')) {
					if ((substr($line[1], 0, 1) === '"') && (substr($line[1], -1, 1) === '"')) {
						$value = str_replace(array('\\"', '\\\\'), array('"', '\\'), substr($line[1], 1, -1));
					}
					elseif (ctype_digit($line[1])) {
						$value = (int)$line[1];
					}
					elseif ($line[1] === 'true') {
						$value = true;
					}
					elseif ($line[1] === 'false') {
						$value = false;
					}
					elseif ($line[1] === 'null') {
						$value = null;
					}
					elseif (filter_var($line[1], FILTER_VALIDATE_FLOAT) !== false) {
						$value = (float)$line[1];
					}
					else {
						trigger_error('Unknown value in ini file on line ' . ($linenum + 1) . ': ' . $linestr, E_USER_WARNING);
					}
					if (isset($value)) {
						if (!isset($lastkey)) {
							$return[$key] = $value;
						}
						else {
							$return = array_merge_recursive_distinct($return, string_to_nested_array($lastkey, array($key => $value)));
						}
					}
				}
				else {
					$lastkey = substr($key, 1, -1);
				}
			}
		}
		return $return;
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
