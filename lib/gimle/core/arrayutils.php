<?php namespace gimle\core;
/**
 * This files handles Array Utilities.
 *
 * @package utilities
 */
/**
 * Array Utilities class.
 */
class ArrayUtils {
	//@todo Should it be possible to query with int?
	/**
	 * Returns the first or last key in the array.
	 *
	 * @param array $arr Array to look in.
	 * @param string|int $which What to look for, first, last, 1, -1.
	 * @return mixed|boolean The key, or false on failure.
	 */
	public static function getKey (array $arr = array (), $which) {
		if (!empty($arr)) {
			$keys = array_keys($arr);
			if (($which === 'first') || ($which === 1)) {
				return array_shift($keys);
			}
			elseif (($which === 'last') || ($which === -1)) {
				return array_pop($keys);
			}
		}
		return false;
	}

	//@todo Should it be possible to query with int?
	/**
	 * Returns the first or last value in the array.
	 *
	 * @param array $arr Array to look in.
	 * @param string|int $which What to look for, first, last, 1, -1.
	 * @return mixed|boolean The value, or false on failure.
	 */
	public static function getValue (array $arr = array (), $which) {
		if (!empty($arr)) {
			if (($which === 'first') || ($which === 1)) {
				reset($arr);
			}
			elseif (($which === 'last') || ($which === -1)) {
				end($arr);
			}
			else {
				return false;
			}
			return current($arr);
		}
		return false;
	}

	//@todo Look over the parameter list, what do do with variable number of parameters? Also, should the function somehow check that every parameter really is an array?
	/**
	 * Merge two or more arrays recursivly and preserve keys.
	 *
	 * Values will overwrite previous array for every additional array passed to the method.
	 *
	 * @param array $array1 Initial array to merge.
	 * @param array $… [optional] Variable list of arrays to recursively merge.
	 * @return array The merged array.
	 */
	public static function mergeRecursiveDistinct (array $array1) {
		$arrays = func_get_args();
		if (count($arrays) > 1) {
			array_shift($arrays);
			foreach ($arrays as $array2) {
				if (!empty($array2)) {
					foreach ($array2 as $key => $val) {
						if (is_array($array2[$key])) {
							$array1[$key] = ((isset($array1[$key])) && (is_array($array1[$key])) ? self::mergeRecursiveDistinct($array1[$key], $array2[$key]) : $array2[$key]);
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

	//@todo Name, and documentation.
	/**
	 * Convert a dot separated string to a nested array.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return array
	 */
	public static function dotStringKeyToNested ($key, $value) {
		if (strpos($key, '.') === false) {
			return array($key => $value);
		}
		$key = explode('.', $key);
		$pre = array_shift($key);
		$return = array($pre => self::dotStringKeyToNested(implode('.', $key), $value));
		return $return;
	}
}
