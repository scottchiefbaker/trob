<?PHP

$conf_dir = __DIR__;

require("$conf_dir/include/smarty/Smarty.class.php");
require("$conf_dir/include/krumo/class.krumo.php");
require("$conf_dir/include/db_query/db_query.inc.php");

class page {
	public $require_https = false;
	public $require_login = false;

	function page($opts = array()) {
		$this->smarty = new Smarty();
		$this->start_time = microtime(1);
		$this->dbq        = new db_query();

		$this->base_dir = __DIR__ . "/";
		$this->skin_dir = $this->base_dir . "/skins/";

		session_start();

		$this->smarty->template_dir = "tpls/";
		$this->smarty->compile_dir  = "tpls/compiled/";
		$this->smarty->config_dir   = $this->base_dir . "/smarty/configs/";
		$this->smarty->cache_dir    = $this->base_dir . "/smarty/cache/";

		if ($opts['require_https'] && $_SERVER['HTTPS'] != 'on') {
			print "You must access this site with SSL";
			exit;
		}

		if ($opts['require_login'] && !$this->is_logged_in()) {
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
		if (!$tpl) {
			$i = pathinfo($_SERVER['SCRIPT_NAME']);
			$tpl = $i['filename'] . ".stpl";

			$tpl = $this->tpl_dir . "$tpl";
		}

		$debug = $_GET['debug'];

		$this->assign('_js_scripts',$this->_js);
		$this->assign('_css_scripts',$this->_css);

		$this->end_time = microtime(1);

		$total_time = sprintf("%.3f",$this->end_time - $this->start_time);
		$this->assign('total_time',$total_time);
		$this->assign('logged_in_user',$_SESSION['username']);

		if ($debug) {
			$smarty_tpl_vars = $this->smarty->getTemplateVars();

			ob_start();
			krumo($smarty_tpl_vars);
			$debug_html = ob_get_contents();
			ob_end_clean();

			$this->assign('template_variable_debug',$debug_html);
		}

		if (!is_writeable($this->smarty->compile_dir)) {
			$path = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'] . $this->smarty->compile_dir;
			$path = preg_replace("/\/\//","/",$path);

			$str  = "Cannot write to the compiled directory";
			$str .= "<p><b>Fix:</b> <code>chmod a+w $path</code></p>";
			$this->error_out($str);
		}

		// Actually send the HTML to the browser
		$this->assign('template_file',$tpl);
		$this->smarty->display($this->skin_dir . "/global.stpl");
	}

	function error_out($str,$num = '') {
		if ($num) {
			$str .= " (ErrorNumber: $num)";
		}

		print "<p><b>Error:</b> $str</p>";

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

}

?>
