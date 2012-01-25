<?php namespace gimle\core;
/**
 * This files handles MySQL Utilities.
 *
 * @package data_utilities
 */
/**
 * MySQL Utilities class.
 */
class Mysql extends \mysqli {
	private $queryCache = array();

	public function __construct (array $params = array ()) {
		parent::init();

		$params['pass'] = (isset($params['pass']) ? $params['pass'] : '');
		$params['user'] = (isset($params['user']) ? $params['user'] : 'root');
		$params['host'] = (isset($params['host']) ? $params['host'] : '127.0.0.1');
		$params['port'] = (isset($params['port']) ? $params['port'] : 3306);
		$params['timeout'] = (isset($params['timeout']) ? $params['timeout'] : 30);
		$params['charset'] = (isset($params['charset']) ? $params['charset'] : 'utf8');
		$params['database'] = (isset($params['database']) ? $params['database'] : false);

		parent::options(MYSQLI_OPT_CONNECT_TIMEOUT, $params['timeout']);

		parent::real_connect($params['host'], $params['user'], $params['pass'], $params['database'], $params['port']);

		if ($this->errno === 0) {
			$this->set_charset($params['charset']);

			if ((isset($params['cache'])) && ($params['cache'] === false)) {
				$this->cache(false);
			}
		}
	}

	public function cache ($mode = null) {
		if ($mode === true) {
			return parent::query("SET SESSION query_cache_type = ON;");
		}
		elseif ($mode === false) {
			return parent::query("SET SESSION query_cache_type = OFF;");
		}
		else {
			return parent::query("SHOW VARIABLES LIKE 'query_cache_type';")->fetch_assoc();
		}
	}

	public function query ($query, $resultmode = null) {
		$t = microtime(true);
		$error = false;
		if (!$result = parent::query($query, $resultmode)) {
			$append = self::debug_backtrace('query');
			trigger_error('MySQL query error: (' . $this->errno . ') ' . $this->error . ' in "' . $query . '".' . $append);
			$error = array('errno' => $this->errno, 'error' => $this->error);
		}
		$mysqliresult = (is_bool($result) ? $result : new mysqliresult($result));
		$t = microtime(true) - $t;
		$this->queryCache[] = array('query' => $query, 'time' => $t, 'rows' => $this->affected_rows, 'error' => $error);

		return $mysqliresult;
	}

	public function explain () {
		$colorfunction = __NAMESPACE__ . '\System::colorize';
		$background = 'black';

		$return = '';
		$sqlnum = 0;
		$sqltime = 0;
		$doubles = array();
		foreach ($this->queryCache as $query) {
			$doubles[] = $query['query'];
			$sqltime += $query['time'];
			$sqlnum++;

			$query['time'] = call_user_func($colorfunction, $query['time'], 'range:{"type": "alert", "max":0.09, "value":' . str_replace(',', '.', $query['time']) . '}', $background);

			$return .= '<table style="background-color: #000;">';
			$return .= '<tr><td colspan=12>' . call_user_func($colorfunction, $query['query'], 'black', $background) . '</td></tr>';
			$return .= '<tr><td colspan="12">' . call_user_func($colorfunction, 'Affected rows: ' . $query['rows'] . ', Query Time: ', 'black', $background) . $query['time'] . '</td></tr><tr>';
			$temp = '';
			if (($query['error'] === false) && (preg_match('/^SELECT/i', $query['query']) > 0)) {
				$res = $this->query('EXPLAIN ' . $query['query']);
				$fields = $res->fetch_fields();
				foreach ($fields as $field) {
					$return .= '<th>' . $field->name . '</th>';
				}
				$return .= '</tr>';
				while ($row = $res->fetch_assoc()) {
					$temp .= '<tr><td>' . join('</td><td>', $row) . '</td></tr>';
				}
				if ($temp === '') {
					if (preg_match('/^SELECT/i', $query['query']) > 0) {
						$return .= '<tr><td colspan="12">' . call_user_func($colorfunction, 'Erronymous query ' . $query['rows'] . ' rows affected', 'error', $background) . '</td></tr>';
					}
					else {
						$return .= '<tr><td colspan="12">' . call_user_func($colorfunction, 'Unknown query ' . $query['rows'] . ' rows affected', 'error', $background) . '</td></tr>';
					}
				}
				else {
					$return .= $temp;
				}
			}
			elseif ($query['error'] !== false) {
				$return .= '<tr><td colspan="12">' . call_user_func($colorfunction, 'Error (' . $query['error']['errno'] . '): ' . $query['error']['error'], 'error', $background) . '</td></tr>';
			}
			else {
				$return .= $temp;
			}
			$return .= '</table><br>';
		}
		if (count(array_unique($doubles)) < count($doubles)) {
			$return .= call_user_func($colorfunction, 'You have duplicate queries!', 'error', $background) . '<br>';
		}
		$return .= 'Total sql time: ' . call_user_func($colorfunction, $query['time'], 'range:{"type": "alert", "max":0.3, "value":' . $sqltime . '}', $background) . '<br>';
		$return .= 'Total sql queries: ' . $sqlnum;
		return $return;
	}

	private function debug_backtrace ($function) {
		$backtrace = debug_backtrace();
		foreach ($backtrace as $key => $value)
		{
			if (isset($value['args']))
			{
				foreach ($value['args'] as $key2 => $value2)
				{
					if ((is_array($value2)) && (isset($value2['GLOBALS'])))
					{
						$backtrace[$key]['args'][$key2] = 'Globals vars removed';
					}
				}
			}
		}
		$return = '';
		foreach ($backtrace as $value) {
			if ((isset($value['function'])) && ($value['function'] === $function)) {
				$return .= ' in <b>' . $value['file'] . '</b> on line <b>' . $value['line'] . '</b>';
			}
		}
		return $return;
	}
}

class mysqliresult {
	private $result;

	public function __construct (\mysqli_result $result) {
		$this->result = $result;
	}

	public function get_assoc () {
		for ($i = 0; $i < $this->field_count; $i++) {
			$tmp = $this->fetch_field_direct($i);
			$finfo[$tmp->name] = $tmp->type;
			unset($tmp);
		}
		$return = array();
		while ($result = $this->fetch_assoc()) {
			foreach ($result as $key => $value) {
				if ($result[$key] === null) {
				}
				elseif (in_array($finfo[$key], array(1, 2, 3, 8, 9))) {
					$result[$key] = (int)$result[$key];
				}
				elseif (in_array($finfo[$key], array(4, 5, 246))) {
					$result[$key] = (float)$result[$key];
				}
			}
			$return[] = $result;
		}
		return $return;
	}

	public function __call ($name, $arguments) {
		return call_user_func_array(array($this->result, $name), $arguments);
	}

	public function __set ($name, $value) {
		$this->result->$name = $value;
	}

	public function __get ($name) {
		return $this->result->$name;
	}
}
