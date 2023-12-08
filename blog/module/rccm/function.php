<?php
//--------------------------------------------------------------------
// *** Recent Comments Control Module ***
// LAST UPDATE: 2006/12/30
// Version    : 1.10
// Copyright  : nJOY
// http://njoy.pekori.to/
//--------------------------------------------------------------------
//
// functiont.php
//
//--------------------------------------------------------------------

define("RCCM_VIEW_NUMBER", "20");

function rccm_convert_date($date) {
	$date = substr($date, 0,4).'/'.substr($date,4,2).'/'.substr($date,6,2).' '.substr($date,8,2).':'.substr($date,10,2).':'.substr($date,12,2);
	return $date;
}

?>
