<?php

require('/home/bakers/github/trob/trob.class.php');
$trob = new trob();

if ($_POST['username']) { process_login($_POST); }
if ($_GET['logout']) {
	session_destroy();
	header("Location: login.php");
}

$trob->display();

///////////////////////////////////////////////////////////////////

function process_login($i) {
	$un  = $_POST['username'];
	$pwd = $_POST['password'];

	if (is_valid_login($un,$pwd)) {
		header("Location: index.php");
	}

	global $trob;
	$trob->assign('error','Username/Password not correct');
}

function is_valid_login($un,$trobwd) {
	global $trob;

	$_SESSION['username'] = $un;
	$_SESSION['cust_id']  = $data[0]['CustID'];

	return true;
}
