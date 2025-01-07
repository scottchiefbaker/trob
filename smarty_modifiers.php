<?php

///////////////////////////////////////////////////////////////////////////////

// Pass these PHP functions directly through to the TPL renderer
$pass_thru = ['preg_match', 'str_contains', 'strtolower', 'strtoupper'];

foreach ($pass_thru as $func) {
	$this->smarty->registerPlugin("modifier", $func, $func);
}

// Register custome modifiers here:
//$this->smarty->registerPlugin("modifier", "tpl_function", "strtoupper");
//$this->smarty->registerPlugin("function", "show_date", "my_date");

///////////////////////////////////////////////////////////////////////////////

//function my_date() {
//    $ret = date("Y-m-d");

//    return $ret;
//}

// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
