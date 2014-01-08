<?PHP
	class db_query extends db_core {

		public function __construct($dsn = "", $user = "", $pass = "") {
			$this->init_db_core();

			if ($dsn) {
				$ret = $this->db_connect($dsn,$user,$pass);

				return $ret;
			}

			return true;
		}

		public function db_connect($name,$u = "",$p = "") {
			$name = strtolower($name);

			$this->debug_log("db_connect() on '$name'");

			/////////////////////////////////////////////////////////
			// If all you connect to is a single DB then just
			// use 'default' as your connect name and return 
			// an array of the DSN object and the name of the DB
			//
			// return array(new PDO('sqlite:/path/mydb.sqlite'),array('default'));
			//
			// If you use multiple DBs then break each connect
			// apart in to different functions and connect with a different
			// name.
			//
			// db_query will cache the DB connections and switch between
			// them appropriately when requested

			if ($name == 'rats' || $name == 'default') {
				$dbh = $this->rats_connect($u,$p);
				$name = array('rats','default');
			} elseif ($name == 'omnia') {
				$dbh = $this->omnia_connect($u,$p);
			} elseif ($name == 'oui') {
				$dbh = new PDO('sqlite:' . RATS_VAR_DIR . '/oui.db','','');
			} elseif ($name == 'lunch') {
				$dbh = $this->lunch_connect();
			} elseif (preg_match("/\w+:/",$name)) { // Raw DSN
				$dbh = new PDO($name,$u,$p);
				$name = "raw_dsn";
			} else {
				$this->error_out("Unknown database $name");
			}
			
			// Build an array of the DB names to cache
			if (!is_array($name)) { $name = array($name); }

			$this->dbh = $dbh;

			return array($dbh,$name);
		}

		private function rats_connect($u = "", $p = "") {
			$host = DB_HOST;
			$db = DB;

			$user = $u;
			$user || $user = DB_USER;
			$pass = $p;
			$pass || $pass = DB_PASS;

			$dbh = new PDO("mysql:host=$host;dbname=$db",$user,$pass,array(PDO::ATTR_PERSISTENT => true));

			return $dbh;
		}

		private function omnia_connect($user="",$pass="") {
			putenv('FREETDSCONF=/etc/freetds.conf');
			$db = 'OMNIA_E01_PROD_CBY_CM';
			$user || $user = BILLING_DB_USER;
			$pass || $pass = BILLING_DB_PASS;

			$dsn = "dblib:host=Omnia;dbname=$db";

			try {
				$dbh = new PDO($dsn, $user, $pass);
			} catch (PDOException $e) {
				echo 'Unable to connect to Omnia: ' . $e->getMessage();
				exit;
			}

			return $dbh;
		}

		private function lunch_connect() {
			$dir = $_SERVER['RATS_BASE_PATH'];
			$dbh = new PDO("sqlite:$dir/../lunch/lunch.bin");
			
			return $dbh;
		}
	}

?>
