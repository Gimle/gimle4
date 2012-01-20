<?php namespace gimle\core;
/**
 * This files handles Configuration.
 *
 * @package core
 */
/**
 * Configuration class.
 */
class Config {
	/**
	 * Parse the config files. Should only be initialized once from the init.
	 *
	 * @return void
	 */
	public static function parse () {
		if (!$config = self::parseIniFile(CORE_DIR . 'config.ini')) {
			$config = array();
		}
		if ($file = self::parseIniFile(SITE_DIR . 'config.php')) {
			$config = array_merge_recursive_distinct($config, $file);
		}
		if ($file = self::parseIniFile(SITE_DIR . 'config.ini')) {
			$config = array_merge_recursive_distinct($config, $file);
		}

		unset($config['core']);
		if (isset($config['timezone'])) {
			date_default_timezone_set($config['timezone']);
			unset($config['timezone']);
		}
		else {
			date_default_timezone_set('CET');
		}

		if (!defined('ENV_LEVEL')) {
			//@todo Figure out how to load env level from config file.
			define('ENV_LEVEL', ENV_LIVE);
		}

		if ((isset($config['admin']['ips'])) && (isset($_SERVER['REMOTE_ADDR'])) && (ip_in_ranges($_SERVER['REMOTE_ADDR'], $config['admin']['ips']))) {
			define('FROM_ADMIN_IP', true);
		}
		else {
			define('FROM_ADMIN_IP', false);
		}

		if (!defined('TEMP_DIR')) {
			if (isset($config['temp'])) {
				define('TEMP_DIR', $config['temp']);
				unset($config['temp']);
			}
			else {
				define('TEMP_DIR', rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
			}
		}

		if (!defined('CACHE_DIR')) {
			if (isset($config['cache']['path'])) {
				define('CACHE_DIR', $config['cache']['path']);
				unset($config['cache']['path']);
			}
			else {
				define('CACHE_DIR', TEMP_DIR);
			}
		}

		if (!defined('BASE_PATH')) {
			if ((isset($config['base'])) && (!is_array($config['base']))) {
				define('BASE_PATH', $config['base']);
				define('BASE_PATH_LIVE', BASE_PATH);
			}
			else {
				$base = 'http';
				$port = '';
				if (isset($_SERVER['HTTPS'])) {
					$base .= 's';
					if ($_SERVER['SERVER_PORT'] !== '443') {
						$port = ':' . $_SERVER['SERVER_PORT'];
					}
				}
				elseif ($_SERVER['SERVER_PORT'] !== '80') {
					$port = ':' . $_SERVER['SERVER_PORT'];
				}
				$base .= '://';
				$base = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://';
				$host = explode(':', $_SERVER['HTTP_HOST']);
				$base .= $host[0] . $port . '/';

				$base .= ltrim($_SERVER['SCRIPT_NAME'], '/');
				if (mb_strlen(basename($_SERVER['SCRIPT_NAME'])) > 0) {
					$base = substr($base, 0, -mb_strlen(basename($base)));
				}

				if ((isset($config['base'])) && (!empty($config['base']))) {
					foreach ($config['base'] as $value) {
						if (!is_array($value)) {
							if (isset($config['base']['path'])) {
								$base = $config['base']['path'];
							}
							if (isset($config['base']['live'])) {
								$live = $config['base']['live'];
							}
							break;
						}
						if (isset($value['path'])) {
							if (((isset($value['start'])) && ($value['start'] === substr($base, 0, strlen($value['start'])))) || ((isset($value['regex'])) && (preg_match($value['regex'], $base)))){
								$base = $value['path'];
								if (isset($value['live'])) {
									$live = $value['live'];
								}
								break;
							}
						}
					}
				}
				define('BASE_PATH', $base);
				if (isset($live)) {
					define('BASE_PATH_LIVE', $live);
				}
				else {
					define('BASE_PATH_LIVE', BASE_PATH);
				}
			}
		}

		if (isset($config['extensions'])) {
			if (!empty($config['extensions'])) {
				array_pop(System::$autoloadPrependPaths);
				foreach ($config['extensions'] as $extensionPath) {
					System::$autoloadPrependPaths[] = $extensionPath;
				}
				System::$autoloadPrependPaths[] = CORE_DIR;
			}
		}

		Options::$config = $config;

		Server::initialize();

		mb_internal_encoding('utf-8');
	}

	/**
	 * Parse a ini or php file.
	 *
	 * @param string $filename the full path to the file to parse.
	 * @return array|bool array with the read configuration file, or false upon failure.
	 */
	private static function parseIniFile ($filename) {
		if (file_exists($filename)) {
			if (substr($filename, -4, 4) === '.ini') {
				$config = System::parseIniFile($filename);
			}
			elseif (substr($filename, -4, 4) === '.php') {
				require $filename;
			}
			if ((isset($config)) && (!empty($config))) {
				return $config;
			}
		}
		return false;
	}
}
