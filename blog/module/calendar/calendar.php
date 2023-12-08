<?php
//--------------------------------------------------------------------
// Weblog PHP script BlognPLUS
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//--------------------------------------------------------------------
// *** Calender Module ***
//
// calendar.php v1.2
//
// LAST UPDATE 2007/01/11
//
// ・「第○月曜日」で設定した祝日表示がおかしかったのを修正
//
//--------------------------------------------------------------------
if ($_GET["m"] != "") {
	$blogn_mod_calendar_date = $_GET["m"];
}else{
	$blogn_mod_calendar_date = gmdate("Ym", time() + BLOGN_TIMEZONE);
}
$blogn_skin = blogn_mod_calendar_viewer($blogn_user, $blogn_skin, $blogn_mod_calendar_date);


//-------------------------------------------------------------------- カレンダー表示処理
function blogn_mod_calendar_viewer($user, $skin, $date) {
	if (!preg_match("/\{CALENDARBOX\}/",$skin) && !preg_match("/\{CALENDARVER\}/",$skin) && !preg_match("/\{CALENDARHOR\}/",$skin)) return $skin;

	$calendar_ini = blogn_mod_calendar_ini_load();
	list($start_wday, ) = explode(",",$calendar_ini["wday"]);
	$i = 0;
	while(list($key, $val) = each($calendar_ini["holiday"])) {
		list($holiday_month, $holiday_day, ) = explode(",",$val);
		$holiday_month = trim($holiday_month);
		$holiday_day = trim($holiday_day);
		$holiday_md[$i] = sprintf("%02d%02s", $holiday_month, $holiday_day);
		$i++;
	}
	$d_year = gmdate ("Y", time()+BLOGN_TIMEZONE);
	$d_month = gmdate ("m", time()+BLOGN_TIMEZONE);
	$d_day = gmdate ("d", time()+BLOGN_TIMEZONE);
	$yr = substr($date,0,4);
	$mon = substr($date,4,2);
	$f_today = getdate(mktime(0,0,0,$mon,1,$yr));
	$wday = $f_today["wday"];
	$prev_month = date("Ym", mktime(0,0,0,$mon,0,$yr));
	$next_month = date("Ym", mktime(0,0,0,$mon+1,1,$yr));

	$loglist = blogn_mod_db_log_load_for_month($user, 0, 0, $date, 0);
	if ($loglist[0]) {
		while (list($key, $val) = each($loglist[1])) {
			$update[] = substr($val["date"],0,8);
		}
	}else{
		$update = array();
	}

	$skin = preg_replace('/\{CDCTRLBACK\}([\w\W]+?)\{\/CDCTRLBACK\}/','<a href="index.php?m='.$prev_month.'">\\1</a>',$skin);
	$skin = preg_replace('/\{CDCTRLNEXT\}([\w\W]+?)\{\/CDCTRLNEXT\}/','<a href="index.php?m='.$next_month.'">\\1</a>',$skin);
	if (preg_match("/\{CDYM\}/",$skin) && preg_match("/\{\/CDYM\}/",$skin)) {
		list($skin1,$buf,$skin2) = blogn_word_sepa("{CDYM}", "{/CDYM}", $skin);
		$skin = $skin1.date($buf,mktime(0,0,0,$mon,1,$yr)).$skin2;
	}
	$calendar = '<table class="calendar"><tr align=center>';
	if (preg_match("/\{CALENDARBOX\}/",$skin)) {
		$caflg = 0;
	}elseif (preg_match("/\{CALENDARVER\}/",$skin)) {
		$caflg = 1;
	}elseif (preg_match("/\{CALENDARHOR\}/",$skin)) {
		$caflg = 2;
	}else{
		return $skin;
	}
	if ($caflg == 0) {
		if ($start_wday == 0) {
			for ($i=0; $i<$wday; $i++) { // Blank
				$calendar .= "<td class='cell'>&nbsp;</td>\n"; 
			}
		}else{
			$tmpwday = $wday - 1;
			if ($tmpwday == -1) $tmpwday = 6;
			for ($i=0; $i<$tmpwday; $i++) {
				$calendar .= "<td class='cell'>&nbsp;</td>\n"; 
			}
		}
	}
	$day = 1;
	$weekcounter = 0;
	while(checkdate($mon,$day,$yr)){
		if ($wday == 1) {
			$weekcounter++;
		}
		$link = sprintf("%4d%02d%02d", $yr, $mon, $day);
		$t_link = sprintf("%4d%02d%02d", $d_year, $d_month, $d_day);

		$now_md = sprintf("%02d%02d", $mon, $day);
		$now_mwd = sprintf("%02d", $mon)."w".sprintf("%1d", $weekcounter);

		if(($day == $d_day) && ($mon == $d_month) && ($yr == $d_year)){
			//  Today
			if(in_array($link,$update)){
				$calendar .= '<td class="cell_today"><a href="index.php?d='.$link.'">'.$day.'</a></td>'; 
			}else{
				$calendar .= '<td class="cell_today">'.$day.'</td>'; 
			}
		}elseif($wday == 0 || preg_grep("/{$now_md}/", $holiday_md) || (preg_grep("/{$now_mwd}/", $holiday_md) && $wday == 1)){ 
			//  Sunday or holiday
			if(in_array($link,$update)){
				$calendar .= '<td class="cell_sunday"><a href="index.php?d='.$link.'">'.$day.'</a></td>'; 
			}else{
				$calendar .= '<td class="cell_sunday">'.$day.'</td>'; 
			}
		}elseif($wday == 6){ 
			//  Saturday
			if(in_array($link,$update)){
				$calendar .= '<td class="cell_saturday"><a href="index.php?d='.$link.'">'.$day.'</a></td>'; 
			}else{
				$calendar .= '<td class="cell_saturday">'.$day.'</td>'; 
			}
		}else{ 
			if(in_array($link,$update)){
				$calendar .= '<td class="cell"><a href="index.php?d='.$link.'">'.$day.'</a></td>'; 
			}else{
				$calendar .= '<td class="cell">'.$day.'</td>'; 
			}
		}
		$calendar .= "\n";
		if ($caflg == 0) {
			// 改行
			if ($start_wday == 0 && $wday == 6) $calendar .= '</tr><tr align="center">';
			if ($start_wday == 1 && $wday == 0) $calendar .= '</tr><tr align="center">';
		}elseif ($caflg == 1) {
			$calendar .= '</tr><tr align="center">';
		}elseif ($caflg == 2) {
		}
		$day++;
		$wday++;
		$wday = $wday % 7;

	}
	if ($caflg == 0) {
		if ($start_wday == 0) {
			if($wday > 0){
				while($wday < 7) { // Blank
					$calendar .= '<td class="cell">&nbsp;</td>';
					$wday++;
				}
			}
		}else{
			$tmpwday = $wday - 1;
			if ($tmpwday == -1) $tmpwday = 6;
			if ($tmpwday > 0) {
				while($tmpwday < 7) { // Blank
					$calendar .= '<td class="cell">&nbsp;</td>';
					$tmpwday++;
				}
			}
		}
	}
	$calendar .= '</tr></table>';
	if ($caflg == 0) {
		$skin = preg_replace ("/\{CALENDARBOX\}/", $calendar, $skin);
	}elseif ($caflg == 1) {
		$skin = preg_replace ("/\{CALENDARVER\}/", $calendar, $skin);
	}elseif ($caflg == 2) {
		$skin = preg_replace ("/\{CALENDARHOR\}/", $calendar, $skin);
	}
	return $skin;
}


?>
