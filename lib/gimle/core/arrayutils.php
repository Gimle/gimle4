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
}
