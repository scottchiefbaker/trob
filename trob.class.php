<?php

$conf_dir = __DIR__;

require("$conf_dir/include/smarty/libs/Smarty.class.php");
require("$conf_dir/include/krumo/class.krumo.php");
require("$conf_dir/include/db_query/db_query.class.php");

class trob {
	public $require_https = false;
	public $require_login = false;
	public $plugins       = array();
	public $plugin_dir    = "";
	public $page_title    = "";
	public $_css          = [];
	public $_js           = [];
	public $template_file = "";

	function __construct($opts = array()) {
		session_start();

		$this->start_time  = microtime(1);
		$this->base_dir    = __DIR__ . "/";
		$this->config_file = $opts['config_file'] ?? $this->base_dir . "trob.ini";
		$this->config      = $this->load_config($this->config_file);

		$config           = $this->config['trob'] ?? [];
		$this->plugin_dir = $this->get_config_entry('plugin_dir', $config, $opts, __DIR__ . "/plugins/");
		$this->skin_dir   = $this->get_config_entry('skin_dir', $config, $opts, __DIR__ . "/skins/");

		$db_config = $this->config['database'] ?? [];

		if ($db_config) {
			$dsn  = $db_config['dsn']      ?? "";
			$user = $db_config['username'] ?? null;
			$pass = $db_config['password'] ?? null;

			// If it's a SQLite DSN check to see if the file is readable
			// This prevents a vanilla PDO error if the file cannot be read
			if (preg_match("/sqlite2?:(.+)/", $dsn, $m)) {
				$file = trim($m[1]);

				if ($file !== ":memory:" && !is_readable($file)) {
					$this->error_out("Unable to read SQLite database '$file'", 23812);
				}
			}

			$this->dbq = new DBQuery($dsn, $user, $pass);
		}

		// Note: the TPL/Compiled directory are relative to the .php file
		$this->smarty               = new Smarty();
		$this->smarty->template_dir = $this->get_config_entry('tpl_dir', $config, $opts, "tpls/");
		$this->smarty->compile_dir  = $this->get_config_entry('compiled_dir', $config, $opts, "tpls/compiled");
		$this->smarty->config_dir   = $this->base_dir . "/smarty/configs/";
		$this->smarty->cache_dir    = $this->base_dir . "/smarty/cache/";

		// Don't show missing template variables as E_NOTICE
		// https://github.com/smarty-php/smarty/blob/master/README#L14
		$this->smarty->error_reporting = E_ALL & ~E_NOTICE;

		if (isset($opts['require_https']) && $_SERVER['HTTPS'] != 'on') {
			print "You must access this site with SSL";
			exit;
		}

		if (isset($opts['require_login']) && !$this->is_logged_in()) {
			print "<h1>404 - Page not found</h1>";
		}
	}

	function is_logged_in() {
		$realm_name = "Easy Page Login";
		$remote_ip  = $_SERVER['REMOTE_ADDR'];

		$user = $_SERVER['PHP_AUTH_USER'];
		$pass = $_SERVER['PHP_AUTH_PW'];

		$auth_pass = 'cd57aca0fa2b063517c6c102a3359c21';

		if ($_COOKIE['EasyPageAuth'] == $auth_pass) {
			return 1;
		} elseif (md5($pass) == $auth_pass) {
			$time = time() + 86400 * 30;
			setcookie("EasyPageAuth", $auth_pass, $time);

			return 1;
		} elseif ($this->is_trusted_ip($remote_ip)) {
			return 1;
		} else {
			header("WWW-Authenticate: Basic realm=\"$realm_name\"");
			header('HTTP/1.0 401 Unauthorized');
			echo 'Text to send if user hits Cancel button';
			exit;
		}

		return 0;
	}

	function is_trusted_ip($ip) {
		if (preg_match("/^192\.168\.5\./",$ip)) {
			return 1;
		}

		return 0;
	}

	function assign($k = "", $v = "") {
		$this->smarty->assign($k,$v);
	}

	function display($tpl = "") {
		// If no template is explicitly set use the base filename with a .stpl extension
		if (empty($this->template_file)) {
			$i = pathinfo($_SERVER['SCRIPT_NAME']);
			$tpl = $i['filename'] . ".stpl";

			$tpl = $this->tpl_dir . $tpl;
		// There is an override to the default so we use that
		} else {
			$tpl = $this->tpl_dir . $this->template_file;
		}

		$tpl_file = $this->smarty->template_dir[0] . $tpl;
		if (!is_readable($tpl_file)) {
			$this->error_out("Cannot read template file \"$tpl_file\"",59186);
		}

		$debug          = $_GET['debug']        ?? 0;
		$logged_in_user = $_SESSION['username'] ?? "";
		$this->end_time = microtime(1);
		$total_time     = sprintf("%.3f",$this->end_time - $this->start_time);

		$this->assign('_js_scripts', $this->_js);
		$this->assign('_css_scripts', $this->_css);
		$this->assign('page_title', $this->page_title);
		$this->assign('total_time', $total_time);
		$this->assign('logged_in_user', $logged_in_user);

		if ($debug) {
			$smarty_tpl_vars = $this->smarty->getTemplateVars();

			ob_start();
			krumo($smarty_tpl_vars);
			$debug_html = ob_get_contents();
			ob_end_clean();

			$this->assign('template_variable_debug',$debug_html);
		}

		if (!is_dir($this->smarty->compile_dir)) {
			$path = $this->smarty->compile_dir;

			$str  = "Compiled template directory not present";
			$str .= "<p><b>Fix:</b> <code>mkdir -p $path; chmod a+rwx $path</code></p>";
			$this->error_out($str,38913);
		}

		if (!is_writeable($this->smarty->compile_dir)) {
			$path = realpath($this->smarty->compile_dir);

			$str  = "Cannot write to the compiled directory";
			$str .= "<p><b>Fix:</b> <code>chmod a+rwx $path</code></p>";
			$this->error_out($str,35146);
		}

		// Actually send the HTML to the browser
		$this->assign('template_file',$tpl);
		$skin_file = $this->skin_file ?? "default.stpl";
		$this->smarty->display($this->skin_dir . $skin_file);

		exit;
	}

	function error_out($str,$num = '') {
		if ($num) {
			print "<p><b>Error #$num:</b> $str</p>";
		} else {
			print "<p><b>Error:</b> $str</p>";
		}

		exit;
	}

	function calculate_relative_path($file, $return_dir = 0) {
		// We find the document root of the webserver
		$doc_root = $_SERVER['DOCUMENT_ROOT'];

		// Remove the document root, from the FULL absolute path of the
		// file we're looking for
		$ret = "/" . str_replace($doc_root,"",$file,$ok);
		if (!$ok) { return '/krumo/'; }

		// If they want the path to the dir, only return the dir part
		if ($return_dir) { $ret = dirname($ret) . "/"; }

		#print "$file => $ret"; exit;

		return $ret;
	}

	function test() {
		return "foobar " . $this->foo;
	}

	// Load a plugin
	function plugin_load($class_name, $opts = "") {
		$plugin_file = $this->plugin_dir . "/$class_name.inc.php";
		include_once($plugin_file);

		if ($opts) {
			$this->$class_name = new $class_name($this,$opts);
		} else {
			$this->$class_name = new $class_name($this);
		}

		//print "Loaded plugin $class_name<br />";

		$this->plugins[$class_name] = 1;
	}

	function __get($name) {
		$plugin_file = $this->plugin_dir . "/$name.inc.php";

		if (is_readable($plugin_file)) {
			$this->plugin_load($name);

			return $this->$name;
		} else {
			return null;
		}
	}

	function load_config($file) {
		if (!is_readable($file)) {
			return null;
		}

		$ret = parse_ini_file($file, true);

		return $ret;
	}

	// Fetch an item from the config, then opts, and finally a default value
	function get_config_entry($section, $config, $opts, $default_value) {
		// Look for configuration informat in the following order
		// 1. Config file
		// 2. Options passed to the constructor at runtime
		// 3. Default value

		if (isset($config[$section])) {
			$ret = $config[$section];
			//k("Got $section from config");
		} elseif (isset($opts[$section])) {
			$ret = $opts[$section];
			//k("Got $section from runtime options");
		} else {
			$ret = $default_value;
			//k("Using the default value for $section");
		}

		return $ret;
	}
}
