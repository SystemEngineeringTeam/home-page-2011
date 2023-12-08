<?php
//--------------------------------------------------------------------
// Weblog PHP script BlognPLUS
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//--------------------------------------------------------------------
// *** Calender Module ***
//
// function.phpi•\Ž¦ˆ—^ŠÇ—‰æ–Ê‹¤’Êj
//
// LAST UPDATE 2006/10/05
//
//--------------------------------------------------------------------

function blogn_mod_calendar_ini_load() {
	$ini = file("./".BLOGN_MODDIR."calendar/ini.cgi");
	if (!$ini) {
		$inilist["wday"] = 0;
		$inilist["holiday"][0] = "";
	}else{
		list($inilist["wday"],) = explode(",", $ini[0]);;
		reset($ini);
		$i = 0;
		while (list($key, $val) = each($ini)) {
			if ($key > 0) {
				$inilist["holiday"][$i] = $val;
				$i++;
			}
		}
	}
return $inilist;
}

?>
