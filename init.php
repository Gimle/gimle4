<?php namespace gimle\core;
/**
 * @copyright Copyright (c) 2012, Tux Solbakk
 * @license http://opensource.org/licenses/bsd-license.php BSD 2-Clause License
 * @version 4.0 alpha
 * @package core
 */

define('TIME_START', microtime(true));

define('CORE_DIR', __DIR__ . DIRECTORY_SEPARATOR);

if (PHP_SAPI === 'cli') {
	define('ENV_CLI', true);
	define('ENV_WEB', false);
}
else {
	define('ENV_CLI', false);
	define('ENV_WEB', true);
}

define('ENV_DEV', 1);
define('ENV_TEST', 2);
define('ENV_PREPROD', 4);
define('ENV_LIVE', 8);

if (!defined('SITE_DIR')) {
	if (getenv('SITE_DIR') !== false) {
		define('SITE_DIR', getenv('SITE_DIR'));
	}
	else {
		$cutpoint = strrpos(dirname($_SERVER['SCRIPT_FILENAME']), DIRECTORY_SEPARATOR);;
		define('SITE_DIR', substr($_SERVER['SCRIPT_FILENAME'], 0, $cutpoint) . DIRECTORY_SEPARATOR);
		unset($cutpoint);
	}
}

/**
 * Retrieve the current page from the url.
 *
 * @param int $part Optional
 * @return array|string
 */
function page ($part = false) {
	$path = array();
	if ((isset($_SERVER['PATH_INFO'])) && (trim($_SERVER['PATH_INFO'], '/') != '')) {
		$path = explode('/', trim($_SERVER['PATH_INFO'], '/'));
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
 *
 * @param array $array1 Initial array to merge.
 * @param array $… [optional] Variable list of arrays to recursively merge.
 * @return array The merged array.
 */
function array_merge_recursive_distinct (array $array1) {
	$arrays = func_get_args();
	if (count($arrays) > 1) {
		array_shift($arrays);
		foreach ($arrays as $array2) {
			if (!empty($array2)) {
				foreach ($array2 as $key => $val) {
					if (is_array($array2[$key])) {
						$array1[$key] = ((isset($array1[$key])) && (is_array($array1[$key])) ? array_merge_recursive_distinct($array1[$key], $array2[$key]) : $array2[$key]);
					}
					else {
						$array1[$key] = $val;
					}
				}
			}
		}
	}
	return $array1;
}

/**
 * Convert a separated string to a nested array.
 *
 * @param string $key
 * @param mixed $value
 * @param string $separator
 * @return array
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
 * @param string $ip
 * @param string $range
 * @return boolean
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
		elseif ($ip == $range) {
			return true;
		}
	}
	return false;
}

/**
 * Check if one of the specified ips is part of a range.
 *
 * @param array $ips
 * @param string $range
 * @return boolean
 */
function ips_in_range (array $ips, $range) {
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
 * @param string $ip
 * @param string $ranges
 * @return boolean
 */
function ip_in_ranges ($ip, array $ranges) {
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
 * @return array|bool array with the read configuration file, or false upon failure.
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
		require $filename;
		if ((isset($config)) && (is_array($config))) {
			return $config;
		}
	}
	return false;
}

if ((ENV_WEB) && (page(0) === 'favicon.ico') && (file_exists(CORE_DIR . 'favicon.ico'))) {
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
	header('X-Pad: avoid browser bug');
	readfile($file);
	exit();
}

if ((CORE_DIR !== SITE_DIR) && (!$config = parse_config_file(CORE_DIR . 'config.ini'))) {
	$config = array();
}
if ($file = parse_config_file(SITE_DIR . 'config.php')) {
	$config = array_merge_recursive_distinct($config, $file);
}
if ($file = parse_config_file(SITE_DIR . 'config.ini')) {
	$config = array_merge_recursive_distinct($config, $file);
}
unset($file);

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

if (!defined('ENV_LEVEL')) {
	if (isset($config['env_level'])) {
		define('ENV_LEVEL', $config['env_level']);
		unset($config['env_level']);
	}
	else {
		define('ENV_LEVEL', ENV_LIVE);
	}
}

if ((isset($config['admin']['ips'])) && (isset($_SERVER['REMOTE_ADDR'])) && (ip_in_ranges($_SERVER['REMOTE_ADDR'], $config['admin']['ips']))) {
	define('FROM_ADMIN_IP', true);
}
else {
	define('FROM_ADMIN_IP', false);
}

if (ENV_CLI) {
	ini_set('html_errors', false);
}
if ((isset($config['server']['override'])) && (is_array($config['server']['override'])) && (!empty($config['server']['override']))) {
	if ((ENV_WEB) && (isset($config['server']['override']['html_errors'])) && (is_bool($config['server']['override']['html_errors']))) {
		ini_set('html_errors', $config['server']['override']['html_errors']);
	}
	if ((isset($config['server']['override']['error_reporting'])) && (ctype_digit($config['server']['override']['error_reporting']))) {
		ini_set('error_reporting', $config['server']['override']['error_reporting']);
		error_reporting($config['server']['override']['error_reporting']);
	}
	if ((isset($config['server']['override']['max_execution_time'])) && (ctype_digit($config['server']['override']['max_execution_time']))) {
		ini_set('max_execution_time', $config['server']['override']['max_execution_time']);
	}
	if ((isset($config['server']['override']['memory_limit'])) && (ctype_digit($config['server']['override']['memory_limit']))) {
		ini_set('memory_limit', $config['server']['override']['memory_limit']);
	}
	if (ENV_CLI) {
		if ((isset($config['server']['override']['error_reporting_cli'])) && (ctype_digit($config['server']['override']['error_reporting_cli']))) {
			ini_set('error_reporting', $config['server']['override']['error_reporting_cli']);
			error_reporting($config['server']['override']['error_reporting_cli']);
		}
		if ((isset($config['server']['override']['max_execution_time_cli'])) && (ctype_digit($config['server']['override']['max_execution_time_cli']))) {
			ini_set('max_execution_time', $config['server']['override']['max_execution_time_cli']);
		}
		if ((isset($config['server']['override']['memory_limit_cli'])) && (ctype_digit($config['server']['override']['memory_limit_cli']))) {
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

if ((ENV_WEB) && (!defined('BASE_PATH'))) {
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
		unset($host, $port);

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
			unset($value);
		}
		define('BASE_PATH', $base);
		if (isset($live)) {
			define('BASE_PATH_LIVE', $live);
			unset($live);
		}
		else {
			define('BASE_PATH_LIVE', BASE_PATH);
		}
		unset($base);
	}
}

if ((ENV_WEB) && (!defined('THIS_PATH'))) {
	$page = page();
	$page = implode('/', $page) . (!empty($page) ? '/' : '');
	define('THIS_PATH', BASE_PATH . $page);
	define('THIS_PATH_LIVE', BASE_PATH_LIVE . $page);
	unset($page);
}

if (ENV_WEB) {
	header('Content-Type: text/html; charset=' . mb_internal_encoding());
}

if ((isset($config['extensions'])) && (!empty($config['extensions']))) {
	foreach ($config['extensions'] as $value) {
		if (file_exists($value . 'init.php')) {
			include $value . 'init.php';
		}
	}
	unset($value);
}
