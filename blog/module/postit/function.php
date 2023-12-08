<?php
//--------------------------------------------------------------------
// *** PostIt ***
// LAST UPDATE: 2007/01/27
// Version    : 2.00
// Copyright  : nJOY
// http://njoy.pekori.to/
//--------------------------------------------------------------------
//
// function.php
//
//--------------------------------------------------------------------


function date_from_ini($str) {
	$str = str_replace("%%#40%%", "(", $str);
	$str = str_replace("%%#41%%", ")", $str);
	$str = str_replace("%%#124%%", "|", $str);
	return $str;
}

?>
