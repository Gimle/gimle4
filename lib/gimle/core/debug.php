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

		$fixDumpString = function ($name, $value, $htmlspecial = true) {
			if (in_array($name, array('[\'pass\']', '[\'password\']', '[\'PHP_AUTH_PW\']'))) {
				$value = '********';
			}
			else {
				$fix = array(
					"\r\n" => call_user_func(__CLASS__ . '::color', '¤¶', 'gray') . "\n", // Windows linefeed.
					"\n\r" => call_user_func(__CLASS__ . '::color', '¶¤', 'gray') . "\n\n", // Erronumous (might be interpeted as double) linefeed.
					"\n"   => call_user_func(__CLASS__ . '::color', '¶', 'gray') . "\n", // UNIX linefeed.
					"\r"   => call_user_func(__CLASS__ . '::color', '¤', 'gray') . "\n" // Old mac linefeed.
				);
				$value = strtr(($htmlspecial ? htmlspecialchars($value) : $value), $fix);
			}
			return $value;
		};

		$dodump = function ($var, $var_name = null, $indent = 0, $params = array()) use (&$dodump, &$fixDumpString) {
			if (strstr(print_r($var, true), '*RECURSION*') == true) {
				echo call_user_func(__CLASS__ . '::color', 'Recursion detected, performing normal var_dump:', 'orange') . ' ';
				echo $var_name . ' => ';
				var_dump($var);
				return;
			}
			$doDump_indent = call_user_func(__CLASS__ . '::color', '|', 'lightgray') . '   ';
			echo str_repeat($doDump_indent, $indent) . htmlentities($var_name);

			if (is_array($var)) {
				echo ' => ' . call_user_func(__CLASS__ . '::color', 'Array (' . count($var) . ')', 'gray') . "\n" . str_repeat($doDump_indent, $indent) . "(\n";
				foreach ($var as $key => $value) {
					$dodump($value, '[\'' . $key . '\']', $indent + 1);
				}
				echo str_repeat($doDump_indent, $indent) . ')';
			}
			elseif (is_string($var)) {
				if ((isset($params['error'])) && ($params['error'] === true)) {
					echo ' = ' . call_user_func(__CLASS__ . '::color', 'Error: ' . $fixDumpString($var_name, $var, false), 'pink');
				}
				else {
					echo ' = ' . call_user_func(__CLASS__ . '::color', 'String(' . strlen($var) . ')', 'gray') . ' ' . call_user_func(__CLASS__ . '::color', '\'' . $fixDumpString($var_name, $var) . '\'', 'green');
				}
			}
			elseif (is_int($var)) {
				echo ' = ' . call_user_func(__CLASS__ . '::color', 'Integer(' . strlen($var) . ')', 'gray') . ' ' . call_user_func(__CLASS__ . '::color', $var, 'red');
			}
			elseif (is_bool($var)) {
				echo ' = ' . call_user_func(__CLASS__ . '::color', 'Boolean', 'gray') . ' ' . call_user_func(__CLASS__ . '::color', ($var === true ? 'true' : 'false'), 'purple');
			}
			elseif (is_object($var)) {
				$class = new \ReflectionObject($var);
				$parents = '';
				if ($parent = $class->getParentClass()) {
					$parents .= ' extends ' . $class->getParentClass()->name;
				}
				unset($parent);
				$interfaces = $class->getInterfaces();
				if (!empty($interfaces)) {
					$parents .= ' implements ' . implode(', ', array_keys($interfaces));
				}
				unset($interfaces);

				if ($var instanceof Iterator) {
					echo ' => ' . call_user_func(__CLASS__ . '::color', $class->getName() . ' Object (Iterator)' . $parents, 'gray') . "\n" . str_repeat($doDump_indent, $indent) . "(\n";
					var_dump($var);
				}
				else {
					echo ' => ' . call_user_func(__CLASS__ . '::color', $class->getName() . ' Object' . $parents , 'gray') . "\n" . str_repeat($doDump_indent, $indent) . "(\n";

					$dblcheck = array();
					foreach ((array)$var as $key => $value) {
						if (!property_exists($var, $key)) {
							$key = ltrim($key, "\x0*");
							if (substr($key, 0, strlen($class->getName())) == $class->getName()) {
								$key = substr($key, (strlen($class->getName()) + 1));
							}
						}
						$dblcheck[$key] = $value;
					}

					$reflect = new \ReflectionClass($var);

					$constants = $reflect->getConstants();
					if (!empty($constants)) {
						foreach ($constants as $key => $value) {
							$dodump($value, $key, $indent + 1);
						}
					}
					unset($constants);

					$props = $reflect->getProperties();
					if (!empty($props)) {
						foreach ($props as $prop) {
							$append = '';
							$error = false;
							if ($prop->isPrivate()) {
								$append .= ' private';
							}
							elseif ($prop->isProtected()) {
								$append .= ' protected';
							}
							$prop->setAccessible(true);
							if ($prop->isStatic()) {
								$value = $prop->getValue();
								$append .= ' static';
							}
							else {
								set_error_handler(function ($errno, $errstr) { throw new \Exception($errstr); });
								try {
									$value = $prop->getValue($var);
								}
								catch (\Exception $e) {
									$value = $e->getMessage();
									$append .= ' error';
									$error = true;
								}
								restore_error_handler();
							}
							if (array_key_exists($prop->name, $dblcheck)) {
								unset($dblcheck[$prop->name]);
							}
							$dodump($value, '[\'' . $prop->name . '\'' . $append . ']', $indent + 1, array('error' => $error));
						}
					}
					unset($props, $reflect);
					if (!empty($dblcheck)) {
						foreach ($dblcheck as $key => $value) {
							$dodump($value, '[\'' . $key . '\' magic]', $indent + 1);
						}
					}
				}
				unset($class);
				echo str_repeat($doDump_indent, $indent) . ')';
			}
			elseif (is_null($var)) {
				echo ' = ' . call_user_func(__CLASS__ . '::color', 'null', 'black');
			}
			elseif (is_float($var)) {
				echo ' = ' . call_user_func(__CLASS__ . '::color', 'Float(' . strlen($var) . ')', 'gray') . ' ' . call_user_func(__CLASS__ . '::color', $var, 'cyan');
			}
			elseif (is_resource($var)) {
				echo ' = ' . call_user_func(__CLASS__ . '::color', 'Resource', 'gray') . ' ' . $var;
			}
			else {
				echo ' = ' . call_user_func(__CLASS__ . '::color', 'Unknown', 'gray') . ' ' . $var;
			}
			echo "\n";
		};

		$prefix = 'unique';
		$suffix = 'value';

		if ($return == true) {
			ob_start();
		}
		echo '<pre style="line-height: 120%; margin: 0px 0px 10px 0px; display: block; background: white; color: black; border: 1px solid #cccccc; padding: 5px; font-size: 10px;">';

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
		echo "</pre>\n";
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
	public static function color ($content, $color) {
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
		if ($color === 'purple') {
			return sprintf($template, 'purple', $content);
		}
		if ($color === 'cyan') {
			return sprintf($template, 'dodgerblue', $content);
		}
		if ($color === 'pink') {
			return sprintf($template, 'deeppink', $content);
		}
		if ($color === 'orange') {
			return sprintf($template, 'darkorange', $content);
		}
		else {
			return $content;
		}
	}
}
