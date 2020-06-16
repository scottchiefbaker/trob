<?PHP

require('easy_page/easy_page.class.php');
$p = new page();

if ($_POST['username']) { process_login($_POST); }
if ($_GET['logout']) { 
	session_destroy(); 
	header("Location: login.php");
}

$p->display();

#################################################################

function process_login($i) {
	$un = $_POST['username'];
	$pwd = $_POST['password'];

	if (is_valid_login($un,$pwd)) {
		header("Location: index.php");
	}

	global $p;
	$p->assign('error','Username/Password not correct');
}

function is_valid_login($un,$pwd) {
	global $p;

	$_SESSION['username'] = $un;
	$_SESSION['cust_id'] = $data[0]['CustID'];

	return true;
}

?>
