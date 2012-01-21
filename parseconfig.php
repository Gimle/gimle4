<?php namespace gimle\core;
if (!$config = parse_config_file(CORE_DIR . 'config.ini')) {
	$config = array();
}
if ($file = parse_config_file(SITE_DIR . 'config.php')) {
	$config = array_merge_recursive_distinct($config, $file);
}
if ($file = parse_config_file(SITE_DIR . 'config.ini')) {
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

if (!defined('THIS_PATH')) {
	$page = page();
	$page = implode('/', $page) . (!empty($page) ? '/' : '');
	define('THIS_PATH', BASE_PATH . $page);
	define('THIS_PATH_LIVE', BASE_PATH_LIVE . $page);
}

mb_internal_encoding('utf-8');
