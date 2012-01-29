<?php namespace gimle\core;
/**
 * More functions.
 */

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
 * Converts a config file formatted filesize string to bytes.
 *
 * @param string $size
 * @return int Number of bytes.
 */
function string_to_bytes ($size) {
	$size = trim($size);
	$last = strtolower(substr($size, -1));
	$size = (int)$size;
	switch ($last) {
		case 'g':
			$size *= 1024;
		case 'm':
			$size *= 1024;
		case 'k':
			$size *= 1024;
	}
	return $size;
}

/**
 * Checks for the maximum size uploads.
 *
 * @return int Maximum number of bytes.
 */
function get_upload_limit () {
	return (int)min(string_to_bytes(ini_get('memory_limit')), string_to_bytes(ini_get('post_max_size')), string_to_bytes(ini_get('upload_max_filesize')));
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
