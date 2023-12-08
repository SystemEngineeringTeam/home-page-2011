<?php
//--------------------------------------------------------------------
// *** PostIt ***
// LAST UPDATE: 2007/01/27
// Version    : 2.00
// Copyright  : nJOY
// http://njoy.pekori.to/
//--------------------------------------------------------------------
//
// postit.php
//
//--------------------------------------------------------------------

require "./".BLOGN_MODDIR."postit/function.php";

$inifile = BLOGN_MODDIR."postit/config.cgi";
$inis = file($inifile);
list($ini["break"], $ini["emoji"], $ini["datefmt"], $ini["number"]) = explode(",", $inis[0]);
$i = 0;
for ($i = 1; $i < $ini["number"]; $i++) {
	list($ini[$i]["use"], $ini[$i]["home"], $ini[$i]["entry"], $ini[$i]["month"], $ini[$i]["day"], $ini[$i]["category"], $ini[$i]["search"], $ini[$i]["user"], $ini[$i]["profile"]) = explode(",", $inis[$i]);
}

switch ($blogn_view_mode) {
	case "e":
		for ($i = 1; $i < $ini["number"]; $i++) {
			$view[$i] = $ini[$i]["entry"];
		}
		break;
	case "cl":
	case "c":
		for ($i = 1; $i < $ini["number"]; $i++) {
			$view[$i] = $ini[$i]["category"];
		}
		break;
	case "ul":
	case "u":
		for ($i = 1; $i < $ini["number"]; $i++) {
			$view[$i] = $ini[$i]["user"];
		}
		break;
	case "p":
		for ($i = 1; $i < $ini["number"]; $i++) {
			$view[$i] = $ini[$i]["profile"];
		}
		break;
	case "ml":
	case "m":
		for ($i = 1; $i < $ini["number"]; $i++) {
			$view[$i] = $ini[$i]["month"];
		}
		break;
	case "d":
		for ($i = 1; $i < $ini["number"]; $i++) {
			$view[$i] = $ini[$i]["day"];
		}
		break;
	case "s":
		for ($i = 1; $i < $ini["number"]; $i++) {
			$view[$i] = $ini[$i]["search"];
		}
		break;
	default:
		for ($i = 1; $i < $ini["number"]; $i++) {
			$view[$i] = $ini[$i]["home"];
		}
		break;
}

$datefmt = date_from_ini($ini["datefmt"]);

for ($i = 1; $i < $ini["number"]; $i++) {
	if ($ini[$i]["use"] == "1") {
		if (!$view[$i]) {
			$blogn_skin = str_replace("{POSTIT$i}", "", $blogn_skin);
		} else {
			$logfile = BLOGN_MODDIR."postit/postit$i.cgi";
			$lastmod = filemtime($logfile);
			$date = date($datefmt, $lastmod);
			$mes = postit_get_message($logfile, $date);
			if ($view[$i] == "1") {
				$blogn_skin = str_replace("{POSTIT$i}", $mes, $blogn_skin);
			} elseif ($view[$i] == "2" && $_GET["page"] < 2 ) {
				$blogn_skin = str_replace("{POSTIT$i}", $mes, $blogn_skin);
			} else {
				$blogn_skin = str_replace("{POSTIT$i}", "", $blogn_skin);
			}
		}
/*
		if ($view[$i] == "1") {
			$mes = postit_get_message($logfile, $date);
			$blogn_skin = str_replace("{POSTIT$i}", $mes, $blogn_skin);
		} elseif ($view[$i] == "2" && $_GET["page"] < 2 ) {
			$mes = postit_get_message($logfile, $date);
			$blogn_skin = str_replace("{POSTIT$i}", $mes, $blogn_skin);
		} else {
			$blogn_skin = str_replace("{POSTIT$i}", "", $blogn_skin);
		}
*/
	} else {
		$blogn_skin = str_replace("{POSTIT$i}", "", $blogn_skin);
	}
}

return $blogn_skin;

function postit_get_message($log, $date) {
	$message = file_get_contents($log);
	$message = blogn_IconStr($message);
	$message = str_replace("%%DATE%%", $date, $message);
	return $message;
}

?>