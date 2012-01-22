<?php
/**
 * This file loads some functions that is not included in older / any php versions.
 */
if (!function_exists('mb_ucfirst')) {
	/**
	 * Make a string's first character uppercase.
	 *
	 * @param string $string The input string.
	 * @return string The resulting string.
	 */
	function mb_ucfirst ($string) {
		$return = '';
		$fc = mb_strtoupper(mb_substr($string, 0, 1));
		$return .= $fc . mb_substr($string, 1, mb_strlen($string));
		return $return;
	}
}

if (!function_exists('mb_str_pad')) {
	/**
	 * Pad a string to a certain length with another string.
	 *
	 * If the value of $pad_length is negative, less than, or equal to the length of the input string, no padding takes place.
	 * The $pad_string may be truncated if the required number of padding characters can't be evenly divided by the pad_string's length.
	 *
	 * @param string $input The input string.
	 * @param int $pad_length Pad length.
	 * @param string $pad_string Pad string.
	 * @param constant $pad_type Can be STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH.
	 * @param string $encoding The character encoding to use.
	 * @return string The padded string.
	 */
	function mb_str_pad ($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT, $encoding = null) {
		$diff = strlen($input) - mb_strlen($input, $encoding);
		return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
	}
}

if (!function_exists('is_binary')) {
	/**
	 * Checks if the input is binary.
	 *
	 * @param string $value The input string.
	 * @return bool True if binary, otherwise false.
	 */
	function is_binary ($value) {
		$filename = tempnam($_SERVER['temp'], 'tmp_');
		file_put_contents($filename, $value);
		exec('cd ' . $_SERVER['temp'] . '; file -i ' . $filename, $match);
		unlink($filename);
		$len = strlen($filename . ': ');
		$desc = substr($match[0], $len);
		if (substr($desc, 0, 4) == 'text') {
			return false;
		}
		return true;
	}
}

if (!function_exists('array_merge_recursive_distinct')) {
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
}

if (!function_exists('string_to_nested_array')) {
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
}

if (!function_exists('array_key')) {
	/**
	 * Find a key in array.
	 *
	 * Search from the end of an arrey by searching for a negative key.
	 *
	 * @param array $arr The array to find a key in.
	 * @param int $which Which key to look for.
	 * @return bool|string key as string, or false if fail.
	 */
	function array_key (array $arr = array (), $which = 0) {
		$keys = array_keys($arr);
		if ($which < 0) {
			$keys = array_reverse($keys);
			$which = ~$which;
		}
		if (isset($keys[$which])) {
			return $keys[$which];
		}
		return false;
	}
}

if (!function_exists('array_value')) {
	/**
	 * Find a value in a array.
	 *
	 * Search from the end of an arrey by searching for a negative key.
	 * The return value will be false both if the value of the key is the boolean value false, or if the lookup failed.
	 *
	 * @param array $arr The array to find a value in.
	 * @param int $which Which key to look for.
	 * @return bool|mixed value, or false if fail.
	 */
	function array_value (array $arr = array (), $which = 0) {
		if ($key = array_key($arr, $which)) {
			return $arr[$key];
		}
		return false;
	}
}

if (!function_exists('generate_password')) {
	/**
	 * Generate a human readable random password string.
	 *
	 * @param int $length Number of characters.
	 * @return string
	 */
	function generate_password ($length = 8) {
		$var = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$len = strlen($var);
		$return = '';
		for ($i = 0; $i < $length; $i++) {
			$return .= $var[rand(0, $len - 1)];
		}
		return $return;
	}
}

if (!function_exists('string_to_bytes')) {
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
}

if (!function_exists('get_upload_limit')) {
	/**
	 * Checks for the maximum size uploads.
	 *
	 * @return int Maximum number of bytes.
	 */
	function get_upload_limit () {
		return (int)min(string_to_bytes(ini_get('memory_limit')), string_to_bytes(ini_get('post_max_size')), string_to_bytes(ini_get('upload_max_filesize')));
	}
}

if (!function_exists('ip_in_range')) {
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
}

if (!function_exists('ips_in_range')) {
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
}

if (!function_exists('ip_in_ranges')) {
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
}

if (!function_exists('gcd')) {
	/**
	 * Calculates the greatest common divisor of $a and $b
	 *
	 * @param int $a Non-zero integer.
	 * @param int $b Non-zero integer.
	 * @return int
	 */
	function gcd ($a, $b) {
    	$b = ($a == 0) ? 0 : $b;
    	return (($a % $b) ? self::gcd($b, abs($a - $b)) : $b);
	}
}

if (!function_exists('seconds_to_array')) {
	/**
	 * Convert seconds to grouped array.
	 *
	 * @param int $time Number of seconds.
	 * @param bool $weeks Telling if weeks should be included. (Default is false)
	 * @return array
	 */
	function seconds_to_array ($time, $weeks = false) {
		$time = str_replace(',', '.', $time);
		$value['years'] = 0;
		if ($weeks === true) {
			$value['weeks'] = 0;
		}
		$value = array_merge($value, array('days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0));
		if ($time >= 31556926) {
			$value['years'] = (int)floor($time / 31556926);
			$time = ($time % 31556926);
		}
		if (($time >= 604800) && ($weeks === true)) {
			$value['weeks'] = (int)floor($time / 604800);
			$time = ($time % 604800);
		}
		if ($time >= 86400) {
			$value['days'] = (int)floor($time / 86400);
			$time = ($time % 86400);
		}
		if ($time >= 3600) {
			$value['hours'] = (int)floor($time / 3600);
			$time = ($time % 3600);
		}
		if ($time >= 60) {
			$value['minutes'] = (int)floor($time / 60);
			$time = ($time % 60);
		}
		$value['seconds'] = (int)floor($time);
		return $value;
	}
}

if (!function_exists('run_time')) {
	/**
	 * Get the time since the script was started.
	 *
	 * @return string Human readable time string.
	 */
	function run_time () {
		$microtime = microtime(true) - TIME_START;
		$ttr = seconds_to_array($microtime);
		$microtime = str_replace(',', '.', $microtime);
		$time = '';
		if ($ttr['years'] != 0) {
			$time .= $ttr['years'] . ' year' . (($ttr['years'] > 1) ? 's' : '') . ' ';
			$decimals = 0;
		}
		if ($ttr['days'] != 0) {
			$time .= $ttr['days'] . ' day' . (($ttr['days'] > 1) ? 's' : '') . ' ';
			$decimals = 0;
		}
		if ($ttr['hours'] != 0) {
			$time .= $ttr['hours'] . ' hour' . (($ttr['hours'] > 1) ? 's' : '') . ' ';
			$decimals = 0;
		}
		if ($ttr['minutes'] != 0) {
			$time .= $ttr['minutes'] . ' minute' . (($ttr['minutes'] > 1) ? 's' : '') . ' ';
			$decimals = 2;
		}
		if (!isset($decimals)) {
			$decimals = 6;
		}
		$time .= $ttr['seconds'];
		$time .= (($decimals > 0) ? ',' . substr($microtime, strpos($microtime, '.') + 1, $decimals) : '') . ' second' . (($ttr['seconds'] != 1) ? 's' : '');
		return $time;
	}
}

if (!function_exists('number_to_roman')) {
	/**
	 * Convert integer to roman number.
	 *
	 * @param int $num Number.
	 * @return string Roman number.
	 */
	function number_to_roman ($num) {
		$numbers = array(
			'M'  => 1000,
			'CM' => 900,
			'D'  => 500,
			'CD' => 400,
			'C'  => 100,
			'XC' => 90,
			'L'  => 50,
			'XL' => 40,
			'X'  => 10,
			'IX' => 9,
			'V'  => 5,
			'IV' => 4,
			'I' => 1
		);

		$return = '';
		foreach ($numbers as $key => $value) {
			$matches = (int)$num / $value;
			$return .= str_repeat($key, $matches);
			$num = $num % $value;
		}
		return $return;
	}
}

if (!function_exists('bytes_to_array')) {
	/**
	 * Convert bytes to readable number.
	 *
	 * @param int $filesize Number of bytes.
	 * @param int $decimals optional Number of decimals to include in string.
	 * @return array containing prefix, float value and readable string.
	 */
	function bytes_to_array ($filesize = 0, $decimals = 2) {
		$return = array();
		$count = 0;
		$units = array('', 'k', 'M', 'T', 'P', 'E', 'Z', 'Y');
		while ((($filesize / 1024) >= 1) && ($count < (count($units) - 1))) {
			$filesize = $filesize / 1024;
			$count++;
		}
		if (round($filesize, $decimals) === (float)1024) {
			$filesize = $filesize / 1024;
			$count++;
		}
		$return['units']  = $units[$count];
		$return['value']  = (float)$filesize;
		$return['string'] = round($filesize, $decimals) . (($count > 0) ? ' ' . $units[$count] : '');
		return $return;
	}
}

if (!function_exists('cut_string')) {
	/**
	 * Cut a string to desired length.
	 *
	 * @param input
	 * @param length
	 * @param bool cut by word, will never overflow desired length
	 * @param cutted string ending
	 * @return result
	 */
	function cut_string ($string, $limit, $byword = true, $ending = '…') {
		if (mb_strlen($string) > $limit + 1) {
			$string = mb_substr($string, 0, $limit - 1);
			$string = rtrim($string);
			if ($byword) {
				$len = mb_strrchr($string, ' ');
				if ($len) {
					$len = mb_strlen($len);
					$string = mb_substr($string, 0, -$len);
				}
			}
			$string .= $ending;
		}
		return $string;
	}
}

if (!function_exists('parse_config_file')) {
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
}

if (!function_exists('page')) {
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
}
