<?php
/**
 * Gimle 4
 *
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @version 4.0
 * @link http://gimlé.org/
 * @package core
 */

namespace gimle\core;

if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
	$_SERVER['REQUEST_TIME_FLOAT'] = (float) number_format(microtime(true), 3, '.', '');
}

/**
 * The local absolute location of gimle4.
 * @var string CORE_DIR
 */
define('CORE_DIR', __DIR__ . DIRECTORY_SEPARATOR);

/**
 * A preset for development comparison.
 * @var int
 */
define('ENV_DEV', 1);
/**
 * A preset for test comparison.
 * @var int
 */
define('ENV_TEST', 2);
/**
 * A preset for stage comparison.
 * @var int
 */
define('ENV_STAGE', 4);
/**
 * A preset for preproduction comparison.
 * @var int
 */
define('ENV_PREPROD', 8);
/**
 * A preset for live comparison.
 * @var int
 */
define('ENV_LIVE', 16);
/**
 * A preset for cli mode comparison.
 * @var int
 */
define('ENV_CLI', 32);
/**
 * A preset for web mode comparison.
 * @var int
 */
define('ENV_WEB', 64);


if (!defined('SITE_DIR')) {
	if (getenv('SITE_DIR') !== false) {
		define('SITE_DIR', getenv('SITE_DIR'));
	}
	else {
		$cutpoint = strrpos(dirname($_SERVER['SCRIPT_FILENAME']), DIRECTORY_SEPARATOR);
		/**
		 * The local absolute location of the site.
		 *
		 * To override this, define the SITE_DIR before gimle4 is loaded.
		 * The location should be absolute, and end with a trailing slash.
		 *
		 * @var string
		 */
		define('SITE_DIR', substr($_SERVER['SCRIPT_FILENAME'], 0, $cutpoint) . DIRECTORY_SEPARATOR);
		unset($cutpoint);
	}
}

/**
 * The basename of the site dir.
 */
define('SITE_BASENAME', substr(trim(SITE_DIR, DIRECTORY_SEPARATOR), strrpos(trim(SITE_DIR, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR) + 1));

/**
 * Retrieve the current page from the url.
 *
 * @param mixed $part Integer for a part, or false to return the complete array.
 * @return mixed array, string or boolean.
 * <p>if <em>$part</em> is false, an array is returned with all the set parts of the url.</p>
 * <p>if <em>$part</em> is an integer, a string representation of the corresponding part of the url is returned, or false if not set.</p>
 */
function page ($part = false) {
	$path = array();
	if ((isset($_SERVER['PATH_INFO'])) && (trim($_SERVER['PATH_INFO'], '/') != '')) {
		$path = explode('/', trim($_SERVER['PATH_INFO'], '/'));
	} elseif ((!isset($_SERVER['PATH_INFO'])) && (isset($_SERVER['REQUEST_URI'])) && (trim($_SERVER['REQUEST_URI'], '/') != '')) {
		$path = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
	}
	if ($part !== false) {
		if (isset($path[$part])) {
			return $path[$part];
		}
		return false;
	}
	return $path;
}

/**
 * Merge two or more arrays recursivly and preserve keys.
 *
 * Values will overwrite previous array for every additional array passed to the method.
 * Add the boolean value false to the end to have latest array control the order.
 *
 * @param array $array Variable list of arrays to recursively merge.
 * @return array The merged array.
 */
function array_merge_recursive_distinct ($array) {
	$arrays = func_get_args();
	$reposition = false;
	if (is_bool($arrays[count($arrays) - 1])) {
		if ($arrays[count($arrays) - 1]) {
			$reposition = true;
		}
		array_pop($arrays);
	}
	if (count($arrays) > 1) {
		array_shift($arrays);
		foreach ($arrays as $array2) {
			if (!empty($array2)) {
				foreach ($array2 as $key => $val) {
					if (is_array($array2[$key])) {
						$array[$key] = ((isset($array[$key])) && (is_array($array[$key])) ? array_merge_recursive_distinct($array[$key], $array2[$key], $reposition) : $array2[$key]);
					}
					else {
						if ((isset($array[$key])) && ($reposition === true)) {
							unset($array[$key]);
						}
						$array[$key] = $val;
					}
				}
			}
		}
	}
	return $array;
}

/**
 * Convert a separated string to a nested array.
 *
 * @param string $key The key of the array to search in.
 * @param mixed $value The value to be set.
 * @param string $separator The separator string to look for. Default is a dot (.).
 * @return array The resulting array.
 */
function string_to_nested_array ($key, $value, $separator = '.') {
	if (strpos($key, $separator) === false) {
		return array($key => $value);
	}
	$key = explode($separator, $key);
	$pre = array_shift($key);
	$return = array($pre => string_to_nested_array(implode($separator, $key), $value, $separator));
	return $return;
}

/**
 * Check if the specified ip is part of a range.
 *
 * @param string $ip The ip to check.
 * @param string $range The range to compare against.
 * <p>A range can be:</p>
 * <ul>
 * <li>A normal ip, example: 127.0.0.1</li>
 * <li>A ip with wildcards: 172.16.17.* or 172.16.17.0</li>
 * <li>A netmask: 172.16.17.0/16</li>
 * <li>From - to: 172.16.17.1-172.16.17.32</li>
 * </ul>
 *
 * @return boolean true or false if the ip was found in the range.
 */
function ip_in_range ($ip, $range) {
	if (strpos($range, '/') !== false) {
		list($range, $netmask) = explode('/', $range, 2);
		if (strpos($netmask, '.') !== false) {
			$netmask = str_replace('*', '0', $netmask);
			$netmaskdec = ip2long($netmask);
			$bitip = (ip2long($ip) & $netmaskdec);
			$bitrange = (ip2long($range) & $netmaskdec);
			if ($bitip == $bitrange) {
				return true;
			}
		}
		else {
			$x = explode('.', $range);
			while (count($x) < 4) {
				$x[] = '0';
			}
			list($a, $b, $c, $d) = $x;
			$range = sprintf('%u.%u.%u.%u', (empty($a) ? '0' : $a), (empty($b) ? '0' : $b), (empty($c) ? '0' : $c), (empty($d) ? '0' : $d));
			$rangedec = ip2long($range);
			$ipdec = ip2long($ip);

			$wildcarddec = pow(2, (32 - $netmask)) - 1;
			$netmaskdec = ~$wildcarddec;
			$bitip = ($ipdec & $netmaskdec);
			$bitrange = ($rangedec & $netmaskdec);
			if ($bitip == $bitrange) {
				return true;
			}
		}
	}
	else {
		if (strpos($range, '*') !== false) {
			$lower = str_replace('*', '0', $range);
			$upper = str_replace('*', '255', $range);
			$range = $lower . '-' . $upper;
		}

		if (strpos($range, '-') !== false) {
			list($lower, $upper) = explode('-', $range, 2);
			$lowerdec = (float) sprintf('%u', ip2long($lower));
			$upperdec = (float) sprintf('%u', ip2long($upper));
			$ipdec = (float) sprintf('%u', ip2long($ip));
			if (($ipdec >= $lowerdec) && ($ipdec <= $upperdec)) {
				return true;
			}
		}
		elseif ($ip === $range) {
			return true;
		}
	}
	return false;
}

/**
 * Check if one of the specified ips is part of a range.
 *
 * @see ip_in_range
 *
 * @param array $ips The ips to check.
 * @param string $range The range to compare against.
 * @return boolean true or false if any of the ips was found in the range.
 */
function ips_in_range ($ips, $range) {
	if (!empty($ips)) {
		foreach ($ips as $ip) {
			if (ip_in_range($ip, $range)) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Check if the specified ip is part of any of the ranges.
 *
 * @see ip_in_range
 *
 * @param string $ip The ip to check.
 * @param array $ranges An array of ranges to compare against.
 * @return boolean true or false if the ip was found in any of the ranges.
 */
function ip_in_ranges ($ip, $ranges) {
	if (!empty($ranges)) {
		foreach ($ranges as $range) {
			if (ip_in_range($ip, $range)) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Parse a ini or php config file and keep typecasting.
 *
 * For ini files, this is similar to the parse_ini_file function, but keeps typecasting and require "" around strings.
 * For php files this function will look for a variable called $config, and return it.
 *
 * @param string $filename the full path to the file to parse.
 * @return mixed array or false. Array with the read configuration file, or false upon failure.
 */
function parse_config_file ($filename) {
	if (!file_exists($filename)) {
		return false;
	}
	if (substr($filename, -4, 4) === '.ini') {
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
					if (isset($value)) {
						unset($value);
					}
					if ((substr($line[1], 0, 1) === '"') && (substr($line[1], -1, 1) === '"')) {
						$value = str_replace(array('\\"', '\\\\'), array('"', '\\'), substr($line[1], 1, -1));
					}
					elseif ((ctype_digit($line[1])) || ((substr($line[1], 0, 1) === '-') && (ctype_digit(substr($line[1], 1))))) {
						$num = $line[1];
						if (substr($num, 0, 1) === '-') {
							$num = substr($line[1], 1);
						}
						if (substr($num, 0, 1) === '0') {
							if (substr($line[1], 0, 1) === '-') {
								$value = -octdec($line[1]);
							}
							else {
								$value = octdec($line[1]);
							}
						}
						else {
							$value = (int)$line[1];
						}
						unset($num);
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
					elseif (preg_match('/^0[xX][0-9a-fA-F]+$/', $line[1])) {
						$value = hexdec(substr($line[1], 2));
					}
					elseif (preg_match('/^\-0[xX][0-9a-fA-F]+$/', $line[1])) {
						$value = -hexdec(substr($line[1], 3));
					}
					elseif (preg_match('/^0b[01]+$/', $line[1])) {
						$value = bindec(substr($line[1], 2));
					}
					elseif (preg_match('/^\-0b[01]+$/', $line[1])) {
						$value = -bindec(substr($line[1], 3));
					}
					elseif (filter_var($line[1], FILTER_VALIDATE_FLOAT) !== false) {
						$value = (float)$line[1];
					}
					elseif (defined($line[1])) {
						$value = constant($line[1]);
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
	elseif (substr($filename, -4, 4) === '.php') {
		include $filename;
		if ((isset($config)) && (is_array($config))) {
			return $config;
		}
	}
	return false;
}

if ((CORE_DIR !== SITE_DIR) && (!$config = parse_config_file(CORE_DIR . 'config.ini'))) {
	$config = array();
}
if ($file = parse_config_file(SITE_DIR . 'config.php')) {
	$config = array_merge_recursive_distinct($config, $file, true);
}
if ($file = parse_config_file(SITE_DIR . 'config.ini')) {
	$config = array_merge_recursive_distinct($config, $file, true);
}
unset($file);

if ((!isset($_SERVER['PATH_INFO'])) && ((!isset($config['path_info_override'])) || ($config['path_info_override'] === true))) {
	$_SERVER['PATH_INFO'] = '';
}

if (isset($config['core'])) {
	unset($config['core']);
}
if (isset($config['timezone'])) {
	date_default_timezone_set($config['timezone']);
	unset($config['timezone']);
}
else {
	date_default_timezone_set('CET');
}

if (isset($config['encoding'])) {
	mb_internal_encoding($config['encoding']);
	unset($config['encoding']);
}
else {
	mb_internal_encoding('utf-8');
}

$env_add = ((PHP_SAPI === 'cli') ? ENV_CLI : ENV_WEB);
if (isset($config['env_level'])) {
	define('ENV_LEVEL', $config['env_level'] | $env_add);
	unset($config['env_level']);
}
else {
	/**
	 * The current env level.
	 *
	 * Default value is ENV_LIVE.
	 * ENV_CLI or ENV_WEB will automatically be added.
	 *
	 * <p>Example defining in config.ini</p>
	 * <code>env_level = ENV_DEV</code>
	 *
	 * <p>Example defining in config.php</p>
	 * <code>$config['env_level'] = ENV_DEV;</code>
	 *
	 * <p>Example checking if current env level is cli mode.</p>
	 * <code>if (ENV_LEVEL & ENV_CLI) {
	 * <span style="white-space: pre; font-size: 50%;">&Tab;</span>// Code for cli.
	 * }</code>
	 *
	 * <p>Example checking if current env level is development or test.</p>
	 * <code>if (ENV_LEVEL & (ENV_DEV | ENV_TEST)) {
	 * <span style="white-space: pre; font-size: 50%;">&Tab;</span>// Code for development and test.
	 * }</code>
	 *
	 * <p>Example checking if current env level is live and web.</p>
	 * <code>if ((ENV_LEVEL & ENV_LIVE) && (ENV_LEVEL & ENV_WEB)) {
	 * <span style="white-space: pre; font-size: 50%;">&Tab;</span>// Code for live web.
	 * }</code>
	 *
	 * <p>Example checking if current env level is not development.</p>
	 * <code>if ((ENV_LEVEL | ENV_DEV) !== ENV_LEVEL) {
	 * <span style="white-space: pre; font-size: 50%;">&Tab;</span>// Code for anything but development.
	 * }</code>
	 *
	 * @var int
	 */
	define('ENV_LEVEL', ENV_LIVE | $env_add);
}
unset($env_add);

if ((isset($config['admin']['ips'])) && (isset($_SERVER['REMOTE_ADDR'])) && (ip_in_ranges($_SERVER['REMOTE_ADDR'], $config['admin']['ips']))) {
	define('FROM_ADMIN_IP', true);
}
else {
	/**
	 * Check if the requestor comes from an ip defined in the config.admin.ips array.
	 *
	 * <p>Example of config.ini</p>
	 * <code>[admin.ips]
	 * localhost = "127.0.0.1"
	 * localnet  = "172.16.17.*"</code>
	 *
	 * <p>Example of config.php</p>
	 * <code>$config['admin']['ips']['localhost'] = '127.0.0.1';
	 * $config['admin']['ips']['localnet']  = '172.16.17.*';</code>
	 *
	 * @var bool
	 */
	define('FROM_ADMIN_IP', false);
}

if (ENV_LEVEL & ENV_CLI) {
	ini_set('html_errors', false);
}
if ((isset($config['server']['override'])) && (is_array($config['server']['override'])) && (!empty($config['server']['override']))) {
	if ((ENV_LEVEL & ENV_WEB) && (isset($config['server']['override']['html_errors']))) {
		ini_set('html_errors', $config['server']['override']['html_errors']);
	}
	if (isset($config['server']['override']['error_reporting'])) {
		ini_set('error_reporting', $config['server']['override']['error_reporting']);
		error_reporting($config['server']['override']['error_reporting']);
	}
	if (isset($config['server']['override']['max_execution_time'])) {
		ini_set('max_execution_time', $config['server']['override']['max_execution_time']);
	}
	if (isset($config['server']['override']['memory_limit'])) {
		ini_set('memory_limit', $config['server']['override']['memory_limit']);
	}
	if (ENV_LEVEL & ENV_CLI) {
		if (isset($config['server']['override']['error_reporting_cli'])) {
			ini_set('error_reporting', $config['server']['override']['error_reporting_cli']);
			error_reporting($config['server']['override']['error_reporting_cli']);
		}
		if (isset($config['server']['override']['max_execution_time_cli'])) {
			ini_set('max_execution_time', $config['server']['override']['max_execution_time_cli']);
		}
		if (isset($config['server']['override']['memory_limit_cli'])) {
			ini_set('memory_limit', $config['server']['override']['memory_limit_cli']);
		}
	}
}

if (!defined('TEMP_DIR')) {
	if (isset($config['temp'])) {
		define('TEMP_DIR', $config['temp']);
		unset($config['temp']);
	}
	else {
		/**
		 * The local absolute location of where temporary files should be stored.
		 *
		 * Can be set in the config.temp string or defined before gimle is loaded.
		 * The location should be absolute, and end with a trailing slash.
		 * This will default to the systems default temp directory.
		 *
		 * <p>Example of config.ini</p>
		 * <code>tmep_dir = "/tmp/"</code>
		 *
		 * <p>Example of config.php</p>
		 * <code>$config['tmep_dir'] = '/tmp/';</code>
		 *
		 * @var string
		 */
		define('TEMP_DIR', rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
	}
}

if ((ENV_LEVEL & ENV_WEB) && (!defined('BASE_PATH'))) {
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
		$host = explode(':', $_SERVER['HTTP_HOST']);
		$base .= $host[0] . $port . '/';
		unset($host, $port);

		$base .= ltrim($_SERVER['SCRIPT_NAME'], '/');
		if (mb_strlen(basename($_SERVER['SCRIPT_NAME'])) > 0) {
			$base = substr($base, 0, -mb_strlen(basename($base)));
		}

		if ((isset($config['base'])) && (!empty($config['base']))) {
			foreach ($config['base'] as $key => $value) {
				if (!is_array($value)) {
					if (isset($config['base']['path'])) {
						$base = $config['base']['path'];
					}
					if (isset($config['base']['live'])) {
						$live = $config['base']['live'];
					}
					break;
				}
				/**
				 * The absolute path to the base of each of the base paths defined in config.
				 *
				 * <p>When working with multiple bases in config, each will be assigned to their own constant, starting with BASE_</p>
				 */
				define('BASE_' . mb_strtoupper($key), $value['path']);
				if ((isset($value['path'])) && (!defined('BASE_PATH_KEY'))) {
					if (((isset($value['start'])) && ($value['start'] === substr($base, 0, strlen($value['start'])))) || ((isset($value['regex'])) && (preg_match($value['regex'], $base)))) {
						$base = $value['path'];
						if (isset($value['live'])) {
							$live = $value['live'];
						}
						/**
						 * The key to the currenty matched base path from config.
						 *
						 * <p>When working with multiple bases in config, this will contain the key of the matched block.</p>
						 */
						define('BASE_PATH_KEY', $key);
					}
				}
			}
			unset($value);
		}
		/**
		 * The public base path of the site.
		 *
		 * This can be set in a config file.
		 * When multiple domains is matched, it will match in the same order as in the config.
		 * The default value will be calculated automatically.
		 *
		 * <p>Example single domain as string in config.ini</p>
		 * <code>base = "http://example.com/"</code>
		 *
		 * <p>Example single domain as array in config.ini</p>
		 * <code>[base]
		 * path = "http://example.com/"</code>
		 *
		 * <p>Example multiple domain with string start match in config.ini</p>
		 * <code>[base.mobile]
		 * start = "http://m.";
		 * path = "http://m.example.com/"
		 *
		 * [base.default]
		 * start = "http://";
		 * path = "http://example.com/"</code>
		 * <p>To search with a regular expression, change the "start" keyword with "regex".</p>
		 *
		 * <p>Example single domain in config.php</p>
		 * <code>$config['base']['path'] = 'http://example.com/';</code>
		 *
		 * @var string
		 */
		define('BASE_PATH', $base);
		if (isset($live)) {
			define('BASE_PATH_LIVE', $live);
			unset($live);
		}
		else {
			/**
			 * The public live version of the base path of the site.
			 *
			 * This can be set when the config path is an array. looks for the "live" keyword.
			 *
			 * @see BASE_PATH
			 *
			 * @var string
			 */
			define('BASE_PATH_LIVE', BASE_PATH);
		}
		unset($base);
	}
}

if ((ENV_LEVEL & ENV_WEB) && (!defined('THIS_PATH'))) {
	$page = page();
	$page = implode('/', $page) . (!empty($page) ? '/' : '');
	/**
	 * The current public absolute path.
	 *
	 * Relies on the <em>BASE_PATH</em> to be set correctly.
	 *
	 * @var string
	 */
	define('THIS_PATH', BASE_PATH . $page);
	/**
	 * The live version of the current public absolute path.
	 *
	 * Relies on the <em>BASE_PATH_LIVE</em> to be set correctly.
	 *
	 * @var string
	 */
	define('THIS_PATH_LIVE', BASE_PATH_LIVE . $page);
	unset($page);
}

if (ENV_LEVEL & ENV_WEB) {
	header('Content-Type: text/html; charset=' . mb_internal_encoding());
}

if ((isset($config['extensions'])) && (!empty($config['extensions']))) {
	foreach ($config['extensions'] as $value) {
		if ((is_string($value)) && (file_exists($value . 'init.php'))) {
			include $value . 'init.php';
		}
	}
	unset($value);
}

if ((page(0) === 'favicon.ico') && (file_exists(CORE_DIR . 'favicon.ico'))) {
	$file = CORE_DIR . 'favicon.ico';
	header_remove('Expires');
	header_remove('Pragma');
	header_remove('Cache-Control');
	header_remove('X-Powered-By');
	header_remove('Set-Cookie');
	header_remove('Content-Language');
	header('Accept-Ranges: bytes');
	header('Server: ' . $_SERVER['SERVER_SOFTWARE']);
	header('ETag: "' . md5_file($file) . '"');
	header('Content-Length: ' . filesize($file));
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
	header('Content-Type: image/x-icon');
	readfile($file);
	exit();
}
