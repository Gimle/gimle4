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
