<?php namespace gimle\core;
/**
 * This files holds the System class.
 *
 * @package core
 */
/**
 * System class.
 */
class System {
	/**
	 * Array containing the search paths for autoloading.
	 *
	 * @var array
	 */
	public static $autoloadPrependPaths = array(SITE_DIR, CORE_DIR);

	/**
	 * Autoload.
	 *
	 * @param string $name
	 */
	public static function autoload ($name) {
		foreach (static::$autoloadPrependPaths as $autoloadPrependPath) {
			$file = $autoloadPrependPath . 'lib' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, strtolower($name)) . '.php';
			if (file_exists($file)) {
				require $file;
				if (method_exists($name, 'initialize')) {
					call_user_func(array($name, 'initialize'));
				}
				break;
			}
		}
	}

	/**
	 * Parse a ini file and keep typecasting.
	 *
	 * @param string $filename
	 */
	public static function parseIniFile ($filename) {
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
					else {
						trigger_error('Unknown value in ini file on line ' . ($linenum + 1) . ': ' . $linestr, E_USER_WARNING);
					}
					if (isset($value)) {
						if (!isset($lastkey)) {
							$return[$key] = $value;
						}
						else {
							$return = ArrayUtils::mergeRecursiveDistinct($return, ArrayUtils::dotStringKeyToNested($lastkey, array($key => $value)));
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

	/**
	 * Get the time since the script was started.
	 *
	 * @return string Human readable time string.
	 */
	public static function runTime () {
		$microtime = microtime(true) - TIME_START;
		$ttr = gCon::time((int)$microtime);
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

	/**
	 * Generate a human readable random password string.
	 *
	 * @param int $length Number of characters.
	 * @return string
	 */
	public static function password ($length = 8) {
		$var = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$len = strlen($var);
		$return = '';
		for ($i = 0; $i < $length; $i++) {
			$return .= $var[rand(0, $len - 1)];
		}
		return $return;
	}

	/**
	 * Execute an external program.
	 *
	 * @param string $exec Command
	 * @return array
	 */
	public static function run ($exec) {
		$filename = tempnam(TEMP_DIR, 'tmp_');
		touch($filename);
		exec($exec . ' 2> ' . $filename, $stout, $return);
		$sterr = explode("\n", trim(file_get_contents($filename)));
		unlink($filename);
		return array('command' => $exec, 'stout' => $stout, 'sterr' => $sterr, 'return' => $return);
	}

	/**
	 * Checks for the maximum size uploads.
	 *
	 * @return int Maximum number of bytes.
	 */
	public static function getUploadLimit () {
		return (int)min(self::toBytes(ini_get('memory_limit')), self::toBytes(ini_get('post_max_size')), self::toBytes(ini_get('upload_max_filesize')));
	}

	/**
	 * Converts a config file formatted filesize value to bytes.
	 *
	 * @param string $size
	 * @return int Number of bytes.
	 */
	public static function toBytes ($size) {
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
	 * Check if the specified ip is part of a range.
	 *
	 * @param string $ip
	 * @param string $range
	 * @return boolean
	 */
	public static function ipInRange ($ip, $range) {
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
	public static function ipsInRange (array $ips, $range) {
		if (!empty($ips)) {
			foreach ($ips as $ip) {
				if (self::ipInRange($ip, $range)) {
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
	public static function ipInRanges ($ip, array $ranges) {
		if (!empty($ranges)) {
			foreach ($ranges as $range) {
				if (self::ipInRange($ip, $range)) {
					return true;
				}
			}
		}
		return false;
	}
}
