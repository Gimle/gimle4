<?php namespace gimle\core;
/**
 * This files handles Debug Utilities.
 *
 * @package utilities
 */
/**
 * Debug Utilities class.
 */
class Debug {
	/**
	 * Dumps a varialble from the global scope.
	 *
	 * @todo Stop dump on live / preprod server.
	 *
	 * @param mixed $var The variable to dump.
	 * @param bool $return Return output? (Default: false)
	 * @param string|bool Alternate title for the dump, or false to backtrace.
	 * @return void|string
	 */
	public static function dump ($var, $return = false, $title = false, $mode = 'auto') {
//		@todo Some options to protect dump from happening on live server.
//		Should be possible for a dev to override based on ip or setting.
//		if (Options::enable('dump') !== true) {
//			return;
//		}

		$fixDumpString = function ($name, $value) {
			if (in_array($name, array('[\'pass\']', '[\'password\']', '[\'PHP_AUTH_PW\']'))) {
				$value = '********';
			}
			else {
				$fix = array(
					"\r\n" => self::color('¤¶', 'gray') . "\n", // Windows linefeed.
					"\n\r" => self::color('¶¤', 'gray') . "\n\n", // Erronumous (might be interpeted as double) linefeed.
					"\n"   => self::color('¶', 'gray') . "\n", // UNIX linefeed.
					"\r"   => self::color('¤', 'gray') . "\n" // Old mac linefeed.
				);
				$value = strtr(htmlspecialchars($value), $fix);
			}
			return $value;
		};

		$dodump = function ($var, $var_name = null, $indent = 0) use (&$dodump, &$fixDumpString) {
			if (strstr(print_r($var, true), '*RECURSION*') == true) {
				echo call_user_func(__CLASS__ . '::color', 'Recursion detected, performing normal var_dump:', 'orange') . ' ';
				echo $var_name . ' => ';
				var_dump($var);
				return;
			}
			$doDump_indent = call_user_func(__CLASS__ . '::color', '|', 'lightgray') . ' &nbsp;&nbsp; ';
			echo str_repeat($doDump_indent, $indent) . htmlentities($var_name);

			if (is_array($var)) {
				echo ' => ' . call_user_func(__CLASS__ . '::color', 'Array (' . count($var) . ')', 'gray') . "\n" . str_repeat($doDump_indent, $indent) . "(\n";
				foreach ($var as $key => $value) {
					$dodump($value, '[\'' . $key . '\']', $indent + 1);
				}
				echo str_repeat($doDump_indent, $indent) . ')';
			}
			elseif (is_string($var)) {
				echo ' = ' . call_user_func(__CLASS__ . '::color', 'String(' . strlen($var) . ')', 'gray') . ' ' . call_user_func(__CLASS__ . '::color', '\'' . $fixDumpString($var_name, $var) . '\'', 'green');
			}
			elseif (is_int($var)) {
				echo ' = <span style="color: #a2a2a2;">Integer(' . strlen($var) . ')</span> ' . call_user_func(__CLASS__ . '::color', $var, 'red');
			}
			elseif (is_bool($var)) {
				echo ' = <span style="color: #a2a2a2;">Boolean</span> <span style="color: #92008d;">' . ($var === true ? 'true' : 'false') . '</span>';
			}
			elseif (is_object($var)) {
				$class = new \ReflectionObject($var);
				$parents = '';
				if ($value = $class->getParentClass()) {
					$parents .= ' extends ' . $value->name;
				}
				$interfaces = $class->getInterfaces();
				if (!empty($interfaces)) {
					$parents .= ' implements ' . implode(', ', array_keys($interfaces));
				}

				if ($var instanceof Iterator) {
					echo ' => <span style="color: #a2a2a2;">' . get_class($var) . $parents . ' object (Iterator)' . "</span>\n" . str_repeat($doDump_indent, $indent) . "(\n";
					var_dump($var);
				}
				else {
					echo ' => <span style="color: #a2a2a2;">' . get_class($var) . $parents . ' object (' . count((array) $var) . ')' . "</span>\n" . str_repeat($doDump_indent, $indent) . "(\n";
					$reflect = new \ReflectionClass($var);
					$constants = $reflect->getConstants();
					if (!empty($constants)) {
						foreach ($constants as $key => $value) {
							$dodump($value, $key, $indent + 1);
						}
					}
					$static = $reflect->getStaticProperties();
					if (!empty($static)) {
						foreach ($static as $key => $value) {
							$visability = '';
							if (!isset($var::$$key)) {
								$visability = 'private|protected ';
							}
							$dodump($value, '[\'' . $key . '\': ' . $visability . 'static]', $indent + 1);
						}
					}
					$namespace = $reflect->getNamespaceName();
					foreach ((array) $var as $key => $value) {
						if (!property_exists($var, $key)) {
							if ((string) substr($key, 1, strlen(get_class($var))) == (string) get_class($var)) {
								$key = substr($key, (strlen((string) get_class($var)) + 1));
								$keytype = ': private';
							}
							else {
								$key = substr($key, 2);
								$keytype = ': protected';
							}
						}
						else {
							$keytype = '';
						}
						$dodump($value, '[\'' . $key . '\'' . $keytype . ']', $indent + 1);
					}
				}
				echo str_repeat($doDump_indent, $indent) . ')';
			}
			elseif (is_null($var)) {
				echo ' = <span style="color: black;">null</span>';
			}
			elseif (is_float($var)) {
				echo ' = <span style="color: #a2a2a2;">Float(' . strlen($var) . ')</span> <span style="color: #0099c5;">' . $var . '</span>';
			}
			elseif (is_resource($var)) {
				echo ' = <span style="color: #a2a2a2;">Resource</span> ' . $var;
			}
			else {
				echo ' = <span style="color: #a2a2a2;">Unknown</span> ' . $var;
			}
			echo "\n";
		};

		$prefix = 'unique';
		$suffix = 'value';

		if ($return == true) {
			ob_start();
		}
		echo "<!-- Dumping -->\n" . '<pre style="line-height: 120%; margin: 0px 0px 10px 0px; display: block; background: white; color: black; border: 1px solid #cccccc; padding: 5px; font-size: 10px;">';

		if ($title === false) {
			$backtrace = debug_backtrace();
			if (substr($backtrace[0]['file'], -13) == 'eval()\'d code') {
				$title = 'eval()';
			}
			else {
				$con = explode("\n", file_get_contents($backtrace[0]['file']));
				$callee = $con[$backtrace[0]['line'] - 1];
				preg_match('/([a-zA-Z\\\\]+)::dump\((.*)/', $callee, $matches);
				$i = 0;
				$title = '';
				foreach (str_split($matches[0], 1) as $value) {
					if ($value === '(') {
						$i++;
					}
					if (($i === 0) && ($value === ',')) {
						break;
					}
					if ($value === ')') {
						$i--;
					}
					if (($i === 0) && ($value === ')')) {
						$title .= $value;
						break;
					}
					$title .= $value;
				}
			}
		}
		$dodump($var, $title);
		echo "</pre>\n<!-- Dump done -->\n";
		if ($return == true) {
			$out = ob_get_contents();
			ob_end_clean();
			return $out;
		}
	}

	/**
	 * Colorize a string according to the envoriment settings.
	 *
	 * @todo Check enviroment settings.
	 *
	 * @param string $content
	 * @param string $color
	 * @return string
	 */
	private static function color ($content, $color) {
		$template = '<span style="color: %s;">%s</span>';
		if ($color === 'gray') {
			return sprintf($template, 'gray', $content);
		}
		if ($color === 'green') {
			return sprintf($template, 'green', $content);
		}
		if ($color === 'red') {
			return sprintf($template, 'red', $content);
		}
		if ($color === 'lightgray') {
			return sprintf($template, 'lightgray', $content);
		}
		if ($color === 'orange') {
			return sprintf($template, 'darkorange', $content);
		}
		else {
			return $content;
		}
	}
}
