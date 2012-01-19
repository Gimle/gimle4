<?php namespace gimle\core;
/**
 * The initialization script.
 *
 * Included first in any project and acts like a custom bootstrap.
 */
/**#@+
 * @ignore
 */

define('TIME_START', microtime(true));
session_start();

define('CORE_DIR', __DIR__ . DIRECTORY_SEPARATOR);
define('ENV_DEV', 1);
define('ENV_TEST', 2);
define('ENV_PREPROD', 4);
define('ENV_LIVE', 8);

if (PHP_SAPI === 'cli') {
	define('ENV_CLI', true);
	define('ENV_WEB', false);
}
else {
	define('ENV_CLI', false);
	define('ENV_WEB', true);
}

require CORE_DIR . 'lib' . DIRECTORY_SEPARATOR . 'functions.php';
require CORE_DIR . 'lib' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, __NAMESPACE__) . DIRECTORY_SEPARATOR . 'system.php';

spl_autoload_register(__NAMESPACE__ . '\System::autoload');

Config::parse();

if (ENV_WEB) {
	header('Content-Type: text/html; charset=' . mb_internal_encoding());
	header('Last-Modified: ' . date('r', TIME_START));

	if (isset($_SESSION['gimlePostLoader'])) {
		$_POST = $_SESSION['gimlePostLoader'];
		unset($_SESSION['gimlePostLoader']);
	}
}

if ((isset(Options::$config['extensions'])) && (!empty(Options::$config['extensions']))) {
	foreach (Options::$config['extensions'] as $value) {
		if (file_exists($value . 'init.php')) {
			include $value . 'init.php';
		}
	}
	unset($value);
}
if (file_exists(SITE_DIR . 'init.php')) {
	include SITE_DIR . 'init.php';
}

if ((ENV_WEB) && (Server::page(0)) && (in_array(Server::page(0), array('load', 'css', 'js', 'favicon.ico')))) {
	include CORE_DIR . 'lib' . DIRECTORY_SEPARATOR . 'specialurls.php';
	exit();
}

//@todo: Only start ob if what?
ob_start();
