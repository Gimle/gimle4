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
