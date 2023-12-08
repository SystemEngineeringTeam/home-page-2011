<?php
//------------------------------------------------------------------------
// Weblog PHP script Blogn（ぶろぐん）
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// 本体
//
// LAST UPDATE 2007/07/19 (1.9.7)
//
// ・一部携帯電話からのコメント投稿が出来なかった問題を修正
// ・ヘッダー出力を追加
//
//------------------------------------------------------------------------
/* ===== タイムアタック ===== */
$timestart = explode(" ",microtime());

/* ===== 共有ファイル読み込み */
require_once("./common.php");

/* ===== IPチェック ===== */
$ip = $_SERVER["REMOTE_ADDR"];
ip_check_deny($ip);

/* ===== 携帯投稿チェック ===== */
// メールへのアクセス頻度を指定間隔にする
	$now = time();
	$fp = @fopen(LOGDIR."access.dat", "w+");
	$lastaccess = fgets($fp, 16);
  $mobile = file(LOGDIR."mobile.dat");

	if(time() > $lastaccess + $mobile[5] * 60){
		mobile_check();
		$access = time()."\r\n";
		fseek($fp, 0);
		fputs($fp, $access);
	}
	fclose($fp);


/* ----- プロフィールファイル読み込み ----- */
if (file_exists(LOGDIR."profile.dat")){
	$profile =  file(LOGDIR."profile.dat");
	//$profileから改行コード削除
	$profile[0] = preg_replace( "/\n$/", "", $profile[0]);
	$profile[0] = preg_replace( "/\r$/", "", $profile[0]);
}


/* ----- カテゴリーファイル読み込み ----- */
if (file_exists(LOGDIR."category.dat")){
	$category = file(LOGDIR."category.dat");
	if ($category != "") {
		for ($i = 0; $i < count( $category ); $i++) {
			//$c_nameが「-」の場合は表示しない
			$category[$i] = preg_replace( "/\n$/", "", $category[$i] );
			$category[$i] = preg_replace( "/\r$/", "", $category[$i] );
		}
	}
}


/* ----- ログ表示処理部 ----- */
$qry = $_SERVER['QUERY_STRING'];
list($qry_key,$qry_data) = explode("=", $qry);
if (!$_GET["month"] && !$_GET["day"]) $qry_data = date("Ym");

$qry_search = @$_GET["search"];
$qry_month = @$_GET["month"];
$qry_day = @$_GET["day"];
$qry_eid = @$_GET["eid"];
$qry_cid = @$_GET["cid"];
$qry_pid = @$_GET["pid"];
$qry_mode = @$_GET["mode"];
$qry_page = @$_GET["page"];


/* ===== 使用ブラウザチェック ===== */
$ua = explode("/",$_SERVER["HTTP_USER_AGENT"]);
if ($ua[0] == 'ASTEL' || 
		$ua[0] == 'UP.Browser' ||
		preg_match("/^KDDI/","$ua[0]") ||
		$ua[0] == 'PDXGW' ||
		$ua[0] == 'DoCoMo' ||
		$ua[0] == 'J-PHONE' ||
		$ua[0] == 'Vodafone' ||
		preg_match("/^MOT/", "$ua[0]") ||
		$ua[0] == 'L-mode') {
	if ($ua[0] == 'J-PHONE') {
		define(PNGKEY,1);
	}else{
		define(PNGKEY,0);
	}
	// スキンファイル読み込み
	$skin = file("./skin/iskin.html");
	$skin = implode("",$skin);
	$skin = mbConv($skin,0,1);
	define(IKEY,1);
}else{
	// スキンファイル読み込み
	if ($skinview = file(LOGDIR."skinview.dat")) {
		for ($i = 0; $i < count($skinview); $i++) {
			$skinview[$i] = preg_replace( "/\n$/", "", $skinview[$i] );
			$skinview[$i] = preg_replace( "/\r$/", "", $skinview[$i] );
			list($vid, $v_sid[$i], $v_cid[$i]) = explode("<>",$skinview[$i]);
		}
	}
	if ($vid == 0) {
		// ノーマル表示
		if ($skins = file(LOGDIR."skin.dat")) {
			for ($i = 0; $i <count($skins); $i++) {
				$skins[$i] = preg_replace( "/\n$/", "", $skins[$i] );
				$skins[$i] = preg_replace( "/\r$/", "", $skins[$i] );
				list($sid, $s_title, $htm_name, $css_name) = explode("<>",$skins[$i]);
				if ($v_sid[0] == $sid) {
					$skin = file(SKINDIR.$htm_name);
					$skin = implode("",$skin);
					$skin = mbConv($skin,0,1);
					$skin = preg_replace ("/\{CSSNAME\}/", SKINDIR.$css_name , $skin);
					break;
				}
			}
		}
	}elseif ($vid == 1) {
		// ランダム表示
		list($mses, $sec) = split(" ", microtime());
		mt_srand($sec*100000);

		if ($skins = file(LOGDIR."skin.dat")) {
			$m = count($v_sid) - 1;
			$r = mt_rand(0,$m);
			for ($i = 0; $i <count($skins); $i++) {
				$skins[$i] = preg_replace( "/\n$/", "", $skins[$i] );
				$skins[$i] = preg_replace( "/\r$/", "", $skins[$i] );
				list($sid, $s_title, $htm_name, $css_name) = explode("<>",$skins[$i]);
				if ($v_sid[$r] == $sid) {
					$skin = file(SKINDIR.$htm_name);
					$skin = implode("",$skin);
					$skin = mbConv($skin,0,1);
					$skin = preg_replace ("/\{CSSNAME\}/", SKINDIR.$css_name , $skin);
					break;
				}
			}
		}
	}elseif ($vid == 2) {
		// ジャンル別表示
		if ($skins = file(LOGDIR."skin.dat")) {
			for ($i = 0; $i <count($skins); $i++) {
				$skins[$i] = preg_replace( "/\n$/", "", $skins[$i] );
				$skins[$i] = preg_replace( "/\r$/", "", $skins[$i] );
				list($sid, $s_title, $htm_name, $css_name) = explode("<>",$skins[$i]);
				//サーチ画面
				if ($qry_key == "search" && $v_sid[1] == $sid) {
					break;
				//プロフィール画面
				}elseif ($qry_key == "pid" && $v_sid[2] == $sid) {
					break;
				//指定記事画面
				}elseif ($qry_key == "eid" && $v_sid[3] == $sid) {
					break;
				//月別画面
				}elseif ($qry_key == "month") {
					$mkey = (int)substr($qry_month,4,2);
					if ($v_sid[$mkey+3] == $sid) {
						break;
					}
				//日別画面
				}elseif ($qry_key == "day") {
					$mkey = (int)substr($qry_day,4,2);
					if ($v_sid[$mkey+3] == $sid) {
						break;
					}
				//カテゴリ別画面
				}elseif ($qry_key == "cid") {
					$breakkey = 0;
					for ($j = 16; $j < count($v_sid); $j++) {
						if ($qry_cid  == $v_cid[$j] && $v_sid[$j] == $sid) {
							$breakkey = 1;
							break;
						}
					}
					if ($breakkey == 1) break;
				}elseif ($v_sid[0] == $sid) {
					break;
				}
			}
			$skin = file(SKINDIR.$htm_name);
			$skin = implode("",$skin);
					$skin = mbConv($skin,0,1);
			$skin = preg_replace ("/\{CSSNAME\}/", SKINDIR.$css_name , $skin);
		}
	}else{
		// 未設定時はエラー
		exit;
	}

	define(IKEY,0);
}

$skin = preg_replace ("/\{HOMELINK\}/", HOMELINK , $skin);
$skin = preg_replace ("/\{SITENAME\}/", SITENAME , $skin);
$skin = preg_replace ("/\{SITEDESC\}/", SITEDESC , $skin);
$skin = preg_replace ("/\{VERSION\}/", BLOGN_VERSION , $skin);


if (IKEY == 0) {
	$skin = calendar_call($skin, $qry_data);
	$skin = newentries_call($skin);
	$skin = recomments_call($skin);
	$skin = retrackback_call($skin);
	$skin = categorylist_call($skin, $category);
	$skin = archives_call($skin);
	$skin = linkslist_call($skin);
	$skin = profilelist_call($skin, $profile);
}

switch ($qry_key) {
	case "search":		//サイト内検索
		$skin = str_replace ("{SITETITLE}", SITENAME."::サイト内検索結果" , $skin);
		$skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $skin);
		$skin = preg_replace("/\{LOGLOOP\}[\w\W]+?\{\/LOGLOOP\}/", "", $skin);
		$skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $skin);
		$skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $skin);
		$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
		$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
		$skin = search_log($skin, $qry_search);
		break;
	case "pid":			//プロフィール表示
		$skin = str_replace ("{SITETITLE}", SITENAME."::プロフィール" , $skin);
		$skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $skin);
		$skin = preg_replace("/\{LOGLOOP\}[\w\W]+?\{\/LOGLOOP\}/", "", $skin);
		$skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $skin);
		$skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $skin);
		$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
		$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
		$skin = profile_log($skin, $profile, $qry_pid);
		break;
	case "mode":			//コメント入力
		if ($qry_mode == "comment") {
			$err = input_comment($c_eid, $c_name, @$c_email, @$c_url, $c_mes, @$set_cookie, $_SERVER["REMOTE_ADDR"], $_SERVER["HTTP_USER_AGENT"]);
			//HTTPヘッダ送信
			$link_url = HOMELINK."index.php?eid=".$c_eid."#comments";
			if (IKEY == 0) {
				if (CHARSET == 0) {
					$chars = "Shift_JIS";
				}elseif (CHARSET == 1) {
					$chars = "EUC-JP";
				}elseif (CHARSET == 2) {
					$chars = "UTF-8";
				}
				$dat = '<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
			}else{
				$chars = "Shift_JIS";
			}
			if ($err == 0) {
				$dat .= '
				<html lang="ja">
				<head>
				<meta http-equiv="refresh"content="1;URL='.$link_url.'">
				<meta http-equiv=content-type content="text/html; charset='.$chars.'">
				</head>
				<body>
				<div align="center">コメント投稿有難うございます。<br><br>
				画面が自動的に切り替わらない場合は<br>
				<a href="'.$link_url.'">こちら</a>をクリックしてください。</div>
				</body>
				</html>
				';
			}elseif ($err == 1) {
				$dat = '
				<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
				<html lang="ja">
				<head>
				<meta http-equiv="refresh"content="10;URL='.$link_url.'">
				<meta http-equiv=content-type content="text/html; charset='.$chars.'">
				</head>
				<body>
				<div align="center">コメントが投稿できませんでした。<br>
				サイト管理人へディレクトリのパーミッションを確認してください。<br><br>
				画面が自動的に切り替わらない場合は<br>
				<a href="'.$link_url.'">こちら</a>をクリックしてください。</div>
				</body>
				</html>
				';
			}elseif ($err == 2) {
				$dat = '
				<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
				<html lang="ja">
				<head>
				<meta http-equiv="refresh"content="10;URL='.$link_url.'">
				<meta http-equiv=content-type content="text/html; charset='.$chars.'">
				</head>
				<body>
				<div align="center">このサイトには連続コメント投稿制限がかかっています。<br>
				しばらくたってから再度投稿してください。<br><br>
				画面が自動的に切り替わらない場合は<br>
				<a href="'.$link_url.'">こちら</a>をクリックしてください。</div>
				</body>
				</html>
				';
			}
			if (IKEY == 0) {
				if (CHARSET == 0) {
					// Shift_JIS表示
					header("Content-Type: text/html; charset=Shift_JIS");
					echo mbConv($dat,1,2);
				}elseif (CHARSET == 1) {
					// EUC-JP表示
					header("Content-Type: text/html; charset=EUC-JP");
					echo $dat;
				}elseif (CHARSET == 2) {
					// UFT-8表示
					header("Content-Type: text/html; charset=UTF-8");
					echo mbConv($dat,1,4);
				}
			}else{
				header("Content-Type: text/html; charset=Shift_JIS");
				echo mbConv($dat,1,2);
			}
			exit;
		}elseif ($qry_mode == "rss") {
			rss_view($profile);
			exit;
		}else{
			exit;
		}
		break;
	case "eid":			//ID別表示
		$skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $skin);
		$skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $skin);
		$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
		$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
		$skin = id_log($skin, $qry_eid);
		break;
	case "month":			//月別表示
		$skin = str_replace ("{SITETITLE}", SITENAME."::".substr($qry_data,0,4)."年".substr($qry_data,4,2)."月" , $skin);
		$skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $skin);
		$skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $skin);
		$skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $skin);
		$skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $skin);
		$skin = month_log($skin, $qry_month, $qry_page);
		break;
	case "day":			//日別表示
		$skin = str_replace ("{SITETITLE}", SITENAME."::".substr($qry_data,0,4)."年".substr($qry_data,4,2)."月".substr($qry_data,6,2)."日" , $skin);
		$skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $skin);
		$skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $skin);
		$skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $skin);
		$skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $skin);
		$skin = day_log($skin, $qry_day, $qry_page);
		break;
	case "cid":			//カテゴリ別表示
		$skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $skin);
		$skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $skin);
		$skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $skin);
		$skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $skin);
		$skin = category_log($skin, $qry_cid, $qry_page);
		break;
	default:				//ノーマル表示
		$skin = str_replace ("{SITETITLE}", SITENAME, $skin);
		$skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $skin);
		$skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $skin);
		$skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $skin);
		$skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $skin);
		$skin = normal_log($skin, $qry_page);
		break;
}


/* ===== HTML出力 ====== */
$timeend = explode(" ",microtime());
$times = ($timeend[0] - $timestart[0]) + ($timeend[1] - $timestart[1]);
$timeattack = "処理時間 ".$times."秒";
$skin = preg_replace ("/\{TIMEATTACK\}/", $timeattack, $skin);
if (IKEY == 0) {
	if (CHARSET == 0) {
		// Shift_JIS表示
		$skin = preg_replace ("/\{CHARSET\}/", "Shift_JIS" , $skin);
		$skin = mbConv($skin,1,2);
	}elseif (CHARSET == 1) {
		// EUC-JP表示
		$skin = preg_replace ("/\{CHARSET\}/", "EUC-JP" , $skin);
	}elseif (CHARSET == 2) {
		// UFT-8表示
		$skin = preg_replace ("/\{CHARSET\}/", "UTF-8" , $skin);
		$skin = mbConv($skin,1,4);
	}
}else{
	$skin = mbConv($skin,1,2);
	$skin = preg_replace_callback('/<a[^>]+?><img src=\"([\w\W]+?)\"[^>]*?><\/a>/i', 'im_callback', $skin);
	$skin = preg_replace_callback('/<img src=\"([\w\W]+?)\"[^>]*?>/i', 'im_callback', $skin);
}
get_skin_php ($skin);
exit;
/* ================================================================= */
function im_callback($matches) {
	if ($fsize = @round(filesize($matches[1]) / 1024, 1)) {
		if ($fsize < 0.4) {
			$result = '<img src="'.$matches[1].'">';
		}else{
			$result = '<a href="'.HOMELINK.'im.php?'.htmlentities(urlencode($matches[1])).'">[PIC]</a>';
		}
	}else{
		$result = '<img src="'.$matches[1].'">';
	}
	return $result;
}

/* ----- カレンダー表示処理部 ----- */
function calendar_call($skin, $qry_data) {
	$d_year = gmdate ("Y", time()+TIMEZONE);
	$d_month = gmdate ("m", time()+TIMEZONE);
	$d_day = gmdate ("d", time()+TIMEZONE);
	$yr = substr($qry_data,0,4);
	$mon = substr($qry_data,4,2);
	$f_today = getdate(mktime(0,0,0,$mon,1,$yr));
	$wday = $f_today[wday];
	$prev_month = date("Ym", mktime(0,0,0,$mon,0,$yr));
	$next_month = date("Ym", mktime(0,0,0,$mon+1,1,$yr));
	$datedir = substr($qry_data,0,4);
	$logname = "log".substr($qry_data,0,6).".dat";
	if (FileCheck($logname, 1, LOGDIR.$datedir)) {
		$dat = file(LOGDIR.$datedir."/".$logname);
		$update = $dat;
		array_walk($update, 'date_callback');
	}else{
		$update = array();
	}

	$skin = preg_replace('/\{CDCTRLBACK\}([\w\W]+?)\{\/CDCTRLBACK\}/','<a href="'.PHP_SELF.'?month='.$prev_month.'">\\1</a>',$skin);
	$skin = preg_replace('/\{CDCTRLNEXT\}([\w\W]+?)\{\/CDCTRLNEXT\}/','<a href="'.PHP_SELF.'?month='.$next_month.'">\\1</a>',$skin);
	if (preg_match("/\{CDYM\}/",$skin) && preg_match("/\{\/CDYM\}/",$skin)) {
		list($skin1,$buf,$skin2) = word_sepa("{CDYM}", "{/CDYM}", $skin);
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
		for ($i=0; $i<$wday; $i++) { // Blank
			$calendar .= "<td class=cell>&nbsp;</td>\n"; 
		}
	}
	$day = 1;
	while(checkdate($mon,$day,$yr)){
		$link = sprintf("%4d%02d%02d", $yr, $mon, $day);
		$t_link = sprintf("%4d%02d%02d", $d_year, $d_month, $d_day);

		if(($day == $d_day) && ($mon == $d_month) && ($yr == $d_year)){
			//  Today
			if(in_array($link,$update)){
				$calendar .= "<td class=cell_today><a href=".PHP_SELF."?day=".$link.">".$day."</a></td>\n"; 
			}else{
				$calendar .= "<td class=cell_today>".$day."</td>\n"; 
			}
		}elseif($wday == 0){ 
			//  Sunday
			if(in_array($link,$update)){
				$calendar .= "<td class=cell_sunday><a href=".PHP_SELF."?day=".$link.">".$day."</a></td>\n"; 
			}else{
				$calendar .= "<td class=cell_sunday>".$day."</td>\n"; 
			}
		}elseif($wday == 6){ 
			//  Saturday
			if(in_array($link,$update)){
				$calendar .= "<td class=cell_saturday><a href=".PHP_SELF."?day=".$link.">".$day."</a></td>\n"; 
			}else{
				$calendar .= "<td class=cell_saturday>".$day."</td>\n"; 
			}
		}else{ 
			if(in_array($link,$update)){
				$calendar .= "<td class=cell><a href=".PHP_SELF."?day=".$link.">".$day."</a></td>\n"; 
			}else{
				$calendar .= "<td class=cell>".$day."</td>\n"; 
			}
		}
		if ($caflg == 0) {
			// 改行
			if($wday == 6) $calendar .= "</tr><tr align=center>";
		}elseif ($caflg == 1) {
			$calendar .= "</tr><tr align=center>";
		}elseif ($caflg == 2) {
		}
		$day++;
		$wday++;
		$wday = $wday % 7;
	}
	if ($caflg == 0) {
		if($wday > 0){
			while($wday < 7) { // Blank
				$calendar .= "<td class=cell>&nbsp;</td>\n";
				$wday++;
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
function date_callback(&$value, $key) {
	list(,$value) = explode("<>",$value,3);
}


/* ----- 最近の更新 ----- */
function newentries_call($skin) {
	if (!preg_match("/\{NE\}/",$skin) || !preg_match("/\{\/NE\}/",$skin)) return $skin;

	list($skin1,$buf,$skin2) = word_sepa("{NELOOP}", "{/NELOOP}", $skin);
	$tmpbuf = $buf;
	$skin3 = "";

	$breakkey = 0;
	if ($filelist = LogFileList(0)) {
		$i = 0;
		while (list($key, $val) = each($filelist)) {
			if ($breakkey == 1) break;
			$log = file(LOGDIR.substr($val,3,4)."/".$val);
			while (list($logkey, $logval) = each($log)) {
				list($eid, $d_date, $d_time, , , $d_title, , , ,) = explode("<>", $logval);
				$NEtitle = $d_title;
				$NElink = "<a href=".PHP_SELF."?eid=".$eid.">";
				$NElinke = "</a>";
				$buf = preg_replace ("/\{NETITLE\}/", $NEtitle, $tmpbuf);
				$buf = preg_replace ("/\{NELINK\}/", $NElink, $buf);
				$buf = preg_replace ("/\{\/NELINK\}/", $NElinke, $buf);
				if (strstr($buf,"{NEYMD}") && strstr($buf,"{/NEYMD}")) {
					list($dat1,$dd,$dat2) = word_sepa("{NEYMD}", "{/NEYMD}", $buf);
					$buf = $dat1.date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$dat2;
				}
				if (strstr($buf,"{NEHMS}") && strstr($buf,"{/NEHMS}")) {
					list($dat1,$dd,$dat2) = word_sepa("{NEHMS}", "{/NEHMS}", $buf);
					$buf = $dat1.date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$dat2;
				}
				$skin3 .= $buf;
				$i++;
				if ($i >= NECOUNT){
					$breakkey = 1;
					break;
				}
			}
		}
	}
	if ($skin3 == "") {
		$skin = preg_replace("/\{NE\}[\w\W]+?\{\/NE\}/", "", $skin);
	}else{
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{NE\}/", "", $skin);
		$skin = preg_replace("/\{\/NE\}/", "", $skin);
	}
	return $skin;
}


/* ----- 最近のコメント ----- */
function recomments_call($skin) {
	if (!preg_match("/\{RC\}/",$skin) || !preg_match("/\{\/RC\}/",$skin)) return $skin;

	$breakkey = 0;
	if ($filelist = LogFileList(2)) {
		$i = 0;
		while (list($key, $val) = each($filelist)) {
			if ($breakkey == 1) break;
			$cmt = file(LOGDIR.substr($val,3,4)."/".$val);
			while (list($cmtkey, $cmtval) = each($cmt)) {
				list($c_eid, $c_date, $c_time, $c_name, , , ,) = explode("<>", $cmtval);
				if ($matchlog = IDCheck($c_eid, 0)) {
					$log = file(LOGDIR.substr($matchlog[0],3,4)."/".$matchlog[0]);
					list($d_eid, , , , , $d_title, , , ,) = explode("<>", $log[IDSearch($c_eid, $log)]);
					$RCC[$d_eid.$d_title] = $d_eid.$d_title;
					$RCT[$i] = $d_eid.$d_title;
					$RC[$i] = $d_title."<>".$c_date."<>".$c_time."<>".$c_name."<>".$c_eid;
					$i++;
					if ($i >= RCCOUNT){
						$breakkey = 1;
						break;
					}
				}
			}
		}
	}

	$skin3 = "";
	list($skin1,$buf,$skin2) = word_sepa("{RCLOOP1}", "{/RCLOOP1}", $skin);
	list($buf1,$buf2,$buf3) = word_sepa("{RCLOOP2}", "{/RCLOOP2}", $buf);

	if (count($RC) != 0) {
		$RCS = array();
		while(list($key,$val) = each($RCC)) {
			for ($j = 0; $j < count($RC); $j++) {
				if ($val == $RCT[$j]) {
					$RCS[] = $RC[$j];
				}
			}
		}

		$oldtitle = "";
		for ($k = 0; $k < count($RCS); $k++) {
			list($d_title, $c_date, $c_time, $c_name, $c_eid) = explode("<>",$RCS[$k]);
			if ($oldtitle != "" && $oldtitle != $d_title) {
				$skin3 .= $buf3;
			}
			if ($oldtitle != $d_title) {
				$oldtitle = $d_title;
				$skin3 .= preg_replace("/\{RCTITLE\}/", $d_title, $buf1);
			}
			$tmpbuf = preg_replace("/\{RCNAME\}/", $c_name, $buf2);
			$RClink = "<a href=".PHP_SELF."?eid=".$c_eid."#comments>";
			$RClinke = "</a>";
			$tmpbuf = preg_replace ("/\{RCLINK\}/", $RClink, $tmpbuf);
			$tmpbuf = preg_replace ("/\{\/RCLINK\}/", $RClinke, $tmpbuf);
			if (preg_match("/\{RCYMD\}/",$tmpbuf) && preg_match("/\{\/RCYMD\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = word_sepa("{RCYMD}", "{/RCYMD}", $tmpbuf);
				$tmpbuf = $dat1.date($dd,mktime(substr($c_time,0,2),substr($c_time,2,2),substr($c_time,4,2),substr($c_date,4,2), substr($c_date,6,2), substr($c_date,0,4))).$dat2;
			}
			if (preg_match("/\{RCHMS\}/",$tmpbuf) && preg_match("/\{\/RCHMS\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = word_sepa("{RCHMS}", "{/RCHMS}", $tmpbuf);
				$tmpbuf = $dat1.date($dd,mktime(substr($c_time,0,2),substr($c_time,2,2),substr($c_time,4,2),substr($c_date,4,2), substr($c_date,6,2), substr($c_date,0,4))).$dat2;
			}
			$skin3 .= $tmpbuf;
		}
		$skin3 .= $buf3;
	}
	
	if ($skin3 == "") {
		$skin = preg_replace("/\{RC\}[\w\W]+?\{\/RC\}/", "", $skin);
	}else{
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{RC\}/", "", $skin);
		$skin = preg_replace("/\{\/RC\}/", "", $skin);
	}
	return $skin;
}


/* ----- 最近のトラックバック ----- */
function retrackback_call($skin) {
	if (!preg_match("/\{RT\}/",$skin) || !preg_match("/\{\/RT\}/",$skin)) return $skin;

	$breakkey = 0;
	if ($filelist = LogFileList(3)) {
		$i = 0;
		while (list($key, $val) = each($filelist)) {
			if ($breakkey == 1) break;
			$trk = file(LOGDIR.substr($val,3,4)."/".$val);
			while (list($trkkey, $trkval) = each($trk)) {
				list($t_eid, $blog_name, $t_title, , , $t_date, $t_time, ,) = explode("<>", $trkval);
				if ($matchlog = IDCheck($t_eid, 0)) {
					$log = file(LOGDIR.substr($matchlog[0],3,4)."/".$matchlog[0]);
					list($d_eid, , , , , $d_title, , , ,) = explode("<>", $log[IDSearch($t_eid, $log)]);
					$RTC[$d_eid.$d_title] = $d_eid.$d_title;
					$RTT[$i] = $d_eid.$d_title;
					$RT[$i] = $d_title."<>".$t_date."<>".$t_time."<>".$blog_name."<>".$t_title."<>".$t_eid;

					$i++;
					if ($i >= RTCOUNT){
						$breakkey = 1;
						break;
					}
				}
			}
		}
	}




	$skin3 = "";
	list($skin1,$buf,$skin2) = word_sepa("{RTLOOP1}", "{/RTLOOP1}", $skin);
	list($buf1,$buf2,$buf3) = word_sepa("{RTLOOP2}", "{/RTLOOP2}", $buf);

	if (count($RT) !=0) {
		$RTS = array();
		while(list($key,$val) = each($RTC)) {
			for ($j = 0; $j < count($RT); $j++) {
				if ($val == $RTT[$j]) {
					$RTS[] = $RT[$j];
				}
			}
		}

		$oldtitle = "";
		for ($k = 0; $k < count($RTS); $k++) {
			list($d_title, $t_date, $t_time, $blog_name, $t_title, $t_eid) = explode("<>",$RTS[$k]);
			if ($oldtitle != "" && $oldtitle != $d_title) {
				$skin3 .= $buf3;
			}
			if ($oldtitle != $d_title) {
				$oldtitle = $d_title;
				$skin3 .= preg_replace("/\{RTTITLE\}/", $d_title, $buf1);
			}

			$RTlink = "<a href=".PHP_SELF."?eid=".$t_eid."#trackback>";
			$RTlinke = "</a>";
			$tmpbuf = preg_replace ("/\{RTNAME\}/", $blog_name, $buf2);
			$tmpbuf = preg_replace ("/\{RTLINK\}/", $RTlink, $tmpbuf);
			$tmpbuf = preg_replace ("/\{\/RTLINK\}/", $RTlinke, $tmpbuf);
			if (preg_match("/\{RTYMD\}/",$tmpbuf) && preg_match("/\{\/RTYMD\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = word_sepa("{RTYMD}", "{/RTYMD}", $tmpbuf);
				$tmpbuf = $dat1.date($dd,mktime(substr($t_time,0,2),substr($t_time,2,2),substr($t_time,4,2),substr($t_date,4,2), substr($t_date,6,2), substr($t_date,0,4))).$dat2;
			}
			if (preg_match("/\{RTHMS\}/",$tmpbuf) && preg_match("/\{\/RTHMS\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = word_sepa("{RTHMS}", "{/RTHMS}", $tmpbuf);
				$tmpbuf = $dat1.date($dd,mktime(substr($t_time,0,2),substr($t_time,2,2),substr($t_time,4,2),substr($t_date,4,2), substr($t_date,6,2), substr($t_date,0,4))).$dat2;
			}
			$skin3 .= $tmpbuf;
		}
		$skin3 .= $buf3;
	}
	if ($skin3 == "") {
		$skin = preg_replace("/\{RT\}[\w\W]+?\{\/RT\}/", "", $skin);
	}else{
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{RT\}/", "", $skin);
		$skin = preg_replace("/\{\/RT\}/", "", $skin);
	}
	return $skin;
}


/* ----- カテゴリ ----- */
function categorylist_call($skin, $category) {
	if (!preg_match("/\{CA\}/",$skin) || !preg_match("/\{\/CA\}/",$skin)) return $skin;

	list($skin1,$buf,$skin2) = word_sepa("{CALOOP}", "{/CALOOP}", $skin);
	$tmpbuf = $buf;
	$skin3 = "";
	if ($category != "") {
		$c_count = CategoryCount();

		for ($i = 0; $i < count( $category ); $i++) {
			//$c_nameが「-」の場合は表示しない
			list($c_id, $c_name) = explode("<>", $category[$i]);
			if ($c_name != "-" && $c_count[$c_id] != 0) {
				$CAtitle = $c_name;
				$CAlink = "<a href=".PHP_SELF."?cid=".$c_id.">";
				$CAlinke = "</a>";
				$CAcount = sprintf("%s", $c_count[$c_id]);
				$buf = preg_replace ("/\{CATITLE\}/", $CAtitle, $tmpbuf);
				$buf = preg_replace ("/\{CALINK\}/", $CAlink, $buf);
				$buf = preg_replace ("/\{\/CALINK\}/", $CAlinke, $buf);
				$buf = preg_replace ("/\{CACOUNT\}/", $CAcount, $buf);
				$skin3 .= $buf;
			}
		}
	}
	if ($skin3 == "") {
		$skin = preg_replace("/\{CA\}[\w\W]+?\{\/CA\}/", "", $skin);
	}else{
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{CA\}/", "", $skin);
		$skin = preg_replace("/\{\/CA\}/", "", $skin);
	}
	return $skin;
}


/* ----- 月別合計 ----- */
function archives_call($skin) {
	if (!preg_match("/\{AR\}/",$skin) || !preg_match("/\{\/AR\}/",$skin)) return $skin;

	list($skin1,$buf,$skin2) = word_sepa("{ARLOOP}", "{/ARLOOP}", $skin);
	$tmpbuf = $buf;
	$skin3 = "";
	$i = 0;
	if ($a_count = archive_count()){
		while(list ($key, $val) = each($a_count)) {
			if ($val != 0) {
				$ARlink = "<a href=".PHP_SELF."?month=".$key.">";
				$ARlinke = "</a>";
				$ARcount = sprintf("%s", $val);
				$buf = preg_replace ("/\{ARCOUNT\}/", $ARcount, $tmpbuf);
				if (strstr($buf,"{ARYM}") && strstr($buf,"{/ARYM}")) {
					list($dat1,$dd,$dat2) = word_sepa("{ARYM}", "{/ARYM}", $buf);
					$buf = $dat1.date($dd,mktime(0,0,0,substr($key,4,2), 1, substr($key,0,4))).$dat2;
				}
				$buf = preg_replace ("/\{ARLINK\}/", $ARlink, $buf);
				$buf = preg_replace ("/\{\/ARLINK\}/", $ARlinke, $buf);
				$skin3 .= $buf;
				$i++;
				if ($i >= ARCOUNT){
					break;
				}
			}
		}
	}
	if ($skin3 == "") {
		$skin = preg_replace("/\{AR\}[\w\W]+?\{\/AR\}/", "", $skin);
	}else{
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{AR\}/", "", $skin);
		$skin = preg_replace("/\{\/AR\}/", "", $skin);
	}
	return $skin;
}


/* ----- リンクリスト ----- */
function linkslist_call($skin) {
	if (!preg_match("/\{LI\}/",$skin) || !preg_match("/\{\/LI\}/",$skin)) return $skin;

	$skin3 = "";
	list($skin1,$buf,$skin2) = word_sepa("{LILOOP1}", "{/LILOOP1}", $skin);
	list($buf1,$buf2,$buf3) = word_sepa("{LILOOP2}", "{/LILOOP2}", $buf);

	$link = file(LOGDIR."link.dat");
	//$linkから改行コード削除
	for ( $i = 0; $i < count( $link ); $i++ ) {
		$link[$i] = preg_replace( "/\n$/", "", $link[$i] );
		$link[$i] = preg_replace( "/\r$/", "", $link[$i] );
		list($l_name, $l_url, $l_category) = explode("<>", $link[$i]);
		if ($i == 0 && $l_category != 1) {
			$skin3 .= preg_replace ("/\{LICATEGORY\}/", "Default", $buf1);
			$LIname = "<a href=".$l_url." target='_blank'>".$l_name."</a>";
			$skin3 .= preg_replace ("/\{LINAME\}/", $LIname, $buf2);
		}elseif ($i == 0 && $l_category == 1) {
			$skin3 .= preg_replace ("/\{LICATEGORY\}/", $l_name, $buf1);
		}elseif ($l_category == 1) {
			$skin3 .= $buf3;
			$skin3 .= preg_replace ("/\{LICATEGORY\}/", $l_name, $buf1);
		}elseif ($l_category != 1) {
			$LIname = "<a href=".$l_url." target='_blank'>".$l_name."</a>";
			$skin3 .= preg_replace ("/\{LINAME\}/", $LIname, $buf2);
		}
	}
	$skin3 .= $buf3;
	$skin = $skin1.$skin3.$skin2;
	$skin = preg_replace("/\{LI\}/", "", $skin);
	$skin = preg_replace("/\{\/LI\}/", "", $skin);
	return $skin;
}


/* ----- プロフィール ----- */
function profilelist_call($skin, $profile) {
	if (!preg_match("/\{PR\}/",$skin) || !preg_match("/\{\/PR\}/",$skin)) return $skin;

	$prlink = $prlinke = $p_name = $proavatar = "";
	if ($profile != "") {
		list($pid, $p_name, , , $p_img) = explode("<>", $profile[0]);
		$prlink = "<a href=\"".PHP_SELF."?pid=".$pid."\">";
		$prlinke = "</a>";
		If ($p_img != "") $proavatar = "<img src=\"".PICDIR.$p_img."\">";
	}
	$skin = preg_replace ("/\{PRLINK\}/", $prlink, $skin);
	$skin = preg_replace ("/\{\/PRLINK\}/", $prlinke, $skin);
	$skin = preg_replace ("/\{PRNAME\}/", $p_name, $skin);
	$skin = preg_replace ("/\{PRAVATAR\}/", $proavatar, $skin);
	$skin = preg_replace("/\{PR\}/", "", $skin);
	$skin = preg_replace("/\{\/PR\}/", "", $skin);
	return $skin;
}


/* ----- サーチデータ一覧表示 ----- */
function search_log($skin, $qry_search) {
	if (CHARSET != 1) $qry_search = mbConv($qry_search,0,1);
	$skin = preg_replace ("/\{SEARCH\}/", "", $skin);
	$skin = preg_replace ("/\{\/SEARCH\}/", "", $skin);
	list($skin1,$buf,$skin2) = word_sepa("{SEARCHLOOP}", "{/SEARCHLOOP}", $skin);
	$tmpbuf = $buf;
	$skin3 = "";
	if (trim($qry_search) != "") {
		$loglist = LogFileList(0);
		while (list($key, $val) = each($loglist)) {
			$log = file(LOGDIR.substr($val,3,4)."/".$val);
			while (list($logkey, $logval) = each($log)) {
				list($eid, $d_date, $d_time, , , $d_title, $d_mes, $d_more, ,) = explode("<>", $logval);
				$daytime = date("Y/m/d h:i A", mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)));
				$searchkey = $daytime.",".$d_title.",".$d_mes.",".$d_more;
				if (stristr($searchkey, $qry_search)) {
					$searchdata = "<a href=\"".PHP_SELF."?eid=".$eid."\">".$d_title."(".$daytime.")</a>";
					$buf = str_replace ("{SEARCHLIST}", $searchdata, $tmpbuf);
					$skin3 .= $buf;
				}
				if ($cmtlist = IDCheck($eid, 1)) {
					while (list($key1, $val1) = each($cmtlist)) {
						$cmt = file(LOGDIR.substr($val1,3,4)."/".$val1);
						while (list($cmtkey, $cmtval) = each($cmt)) {
							list($c_eid, $c_date, $c_time, $c_name, , $c_mes, ,) = explode("<>", $cmtval);
							if ($eid == $c_eid) {
								$daytime = date("Y/m/d h:i A", mktime(substr($c_time,0,2),substr($c_time,2,2),0,substr($c_date,4,2), substr($c_date,6,2), substr($c_date,0,4)));
								$searchkey = $daytime.",".$c_name.",".$c_mes;
								if (stristr($searchkey, $qry_search)) {
									$searchdata = "<a href=\"".PHP_SELF."?eid=".$eid."#comment\">".$d_title."⇒".$c_name."(".$daytime.")</a>";
									$buf = str_replace ("{SEARCHLIST}", $searchdata, $tmpbuf);
									$skin3 .= $buf;
								}
							}
						}
					}
				}
				if ($trklist = IDCheck($eid, 2)) {
					while (list($key1, $val1) = each($trklist)) {
						$trk = file(LOGDIR.substr($val1,3,4)."/".$val1);
						while (list($trkkey, $trkval) = each($trk)) {
							list($t_eid, $blog_name, $t_title, , $t_excerpt, $t_date, $t_time, ,) = explode("<>", $trkval);
							if ($eid == $t_eid) {
								$daytime = date("Y/m/d h:i A", mktime(substr($t_time,0,2),substr($t_time,2,2),0,substr($t_date,4,2), substr($t_date,6,2), substr($t_date,0,4)));
								$searchkey = $daytime.",".$blog_name.",".$t_title.",".$t_excerpt;
								if (stristr($searchkey, $qry_search)) {
									$searchdata = "<a href=\"".PHP_SELF."?eid=".$eid."#trackback\">".$t_title."⇒".$blog_name."(".$daytime.")</a>";
									$buf = str_replace ("{SEARCHLIST}", $searchdata, $tmpbuf);
									$skin3 .= $buf;
								}
							}
						}
					}
				}
			}
		}
	}
	$skin = $skin1.$skin3.$skin2;
	return $skin;
}


/* ----- プロフィール処理 ----- */
function profile_log($skin, $profile, $qry_data) {
	list($skin1,$buf,$skin2) = word_sepa("{PROFILES}", "{/PROFILES}", $skin);
	list($pid, $p_name, $p_email, $p_data, $p_img) = explode("<>", $profile[$qry_data]);
	if (!strlen($p_email)) {
		$profilename = $p_name;
	}else{
		$profilename = "<a href=\"mailto:".$p_email."\">".$p_name."</a>";
	}
	if ($p_img != "") $proavatar = "<img src=\"".PICDIR.$p_img."\">";

	$buf = str_replace ("{PROFILENAME}", $profilename, $buf);
	$buf = str_replace ("{PROFILEMES}", $p_data, $buf);
	$buf = str_replace ("{PROFILEAVATAR}", $proavatar, $buf);
	$skin = $skin1.$buf.$skin2;
	return $skin;
}


/* ----- コメント入力 ----- */
function input_comment($c_eid, $c_name, $c_email, $c_url, $c_mes, $set_cookie, $ip_addr, $user_agent){
	$entry_url = HOMELINK."index.php?eid=".$c_eid;
	if (!$c_eid || !trim($c_name) || !trim($c_mes)) {
		return 1;
	}elseif (!IKEY && $_SERVER["HTTP_REFERER"] != $entry_url) {
		return 1;
	}

	//ログファイルチェック
	$dir = gmdate ("Y", time()+TIMEZONE);
	$cmt = "cmt".gmdate("Ym", time()+TIMEZONE).".dat";
	$err = FileSearch($cmt);
	if ($err == -1 || $err == 0) {
		return 1;
	}
	$oldcomment = file(LOGDIR.$dir."/".$cmt);

	if (CTIMEMAX != 0) {
		while(list($key,$val) = each($oldcomment)) {
			list(,$Ymd,$Hi,,,,$ip,,) = explode("<>",$val);
			$limittime = mktime(substr($Hi,0,2),substr($Hi,2,2)+CTIMEMAX,0,substr($Ymd,4,2), substr($Ymd,6,2), substr($Ymd,0,4));
			if ($limittime <= time()) break;
			if ($ip_addr == $ip) {
				return 2;
			}
		}
	}
	if (CHARSET != 1 || IKEY == 1) {
		$c_name = mbConv($c_name,0,1);
		$c_mes = mbConv($c_mes,0,1);
	}
	//クッキー登録
	if ($set_cookie == on) {
		setcookie("name",$c_name, time() + 3600 * 24 * 7);
		setcookie("email",$c_email, time() + 3600 * 24 * 7);
		setcookie("url",$c_url, time() + 3600 * 24 * 7);
	}
	//テキスト整形
	$c_name = CleanStr($c_name);
	$c_email = CleanStr($c_email);
	$c_url = CleanStr($c_url);
	$c_mes = CleanStr($c_mes);
	if (CSIZEMAX) $c_mes = mbtrim($c_mes,CSIZEMAX);
	// 改行文字の統一。 
	$c_mes = rntobr($c_mes);

	$newcomment = $c_eid."<>".gmdate("Ymd", time()+TIMEZONE)."<>".gmdate("Hi", time()+TIMEZONE)."<>".$c_name."<>".$c_email."<>".$c_mes."<>".$ip_addr."<>".$user_agent."<>".$c_url."\r\n";

	//ログファイル書き込み
	$fp = fopen(LOGDIR.$dir."/".$cmt, "w");
	flock($fp, LOCK_EX);
	if (strlen($newcomment)){
		fputs($fp, $newcomment);
	}
	fputs($fp, implode('', $oldcomment));
	fclose($fp);
	
	//コメント受信時に指定メールアドレスへ連絡
	if (CINFO == 1 && trim(MADDRESS) != "") {
		if ($loglist = IDCheck($c_eid,0)) {
			$log = file(LOGDIR.substr($loglist[0],3,4)."/".$loglist[0]);
			list($eid, , , , , $d_title, , , , , ) = explode("<>", $log[IDSearch($c_eid, $log)]);
			$sub = "コメントを受信しました"; 
			$sub = mbConv($sub,0,3); 
			$sub = "=?iso-2022-jp?B?".base64_encode($sub)."?="; 
			$mes = "件名:".$d_title."\n"; 
			$mes .= "投稿者名:".$c_name."\n"; 
			$mes .= "URL:".HOMELINK."index.php?eid=".$eid."#comments\n"; 
			$mes .= "※このメールアドレスには返信しないでください。"; 
			$mes = mbConv($mes,0,3); 
			$from = SITENAME; 
			$from = mbConv($from,0,3); 
			$from = "=?iso-2022-jp?B?".base64_encode($from)."?="; 
			$from = "From: $from <blogn@localhost>\nContent-Type: text/plain; charset=\"iso-2022-jp\""; 
			@mail(MADDRESS, $sub, $mes, $from); 
		}
	}
	return 0;
}


/* ----- ログ表示処理（ID別表示） ----- */
function id_log($skin, $qry_data) {
	list($skin1,$buf1,$skin2) = word_sepa("{LOGLOOP}", "{/LOGLOOP}", $skin);

	if ($loglist = IDCheck($qry_data,0)) {
		$log = file(LOGDIR.substr($loglist[0],3,4)."/".$loglist[0]);
		$key = IDSearch($qry_data, $log);
		$log[$key] = preg_replace( "/\n$/", "", $log[$key] );
		$log[$key] = preg_replace( "/\r$/", "", $log[$key] );
		list($eid, $d_date, $d_time, $cid, $pid, $d_title, $d_mes, $d_more, $d_cok, $d_tok) = explode("<>", $log[$key]);

		$buf1 = str_replace ("{LOGTITLE}", $d_title, $buf1);
		if (strstr($buf1,"{LOGYMD}") && strstr($buf1,"{/LOGYMD}")) {
			list($dat1,$dd,$dat2) = word_sepa("{LOGYMD}", "{/LOGYMD}", $buf1);
			$buf1 = $dat1.date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$dat2;
		}
		$p_name = profile_call($pid);
		$logauthor = "<a href=\"".PHP_SELF."?pid=".$pid."\">".$p_name."</a>";
		$buf1 = str_replace ("{LOGAUTHOR}", $logauthor, $buf1);
		$d_mes = IconStr($d_mes);
		$d_mes = tagreplaceStr($d_mes);
		if (IKEY == 1) $d_mes = strip_tags($d_mes,"<br><img><a><blockquote>");

		$buf1 = str_replace ("{LOGBODY}", $d_mes, $buf1);
		$buf1 = preg_replace("/\{MOREMARK\}[\w\W]+?\{\/MOREMARK\}/", "", $buf1);

		if (trim($d_more) != "") {
			$d_more = IconStr($d_more);
			$d_more = tagreplaceStr($d_more);

			if (IKEY == 1) $d_more = strip_tags($d_more,"<br><img><a><blockquote>");

			$buf1 = str_replace("{LOGMORE}", $d_more, $buf1);
		}else{
			$buf1 = str_replace("{LOGMORE}", "", $buf1);
		}

		if ($cid == -1) {
			$logcategory = "-";
		}else{
			$c_name = category_call($cid);
			$logcategory = "<a href=\"".PHP_SELF."?cid=".$cid."\">".$c_name."</a>";
		}
		$buf1 = str_replace ("{LOGCATEGORY}", $logcategory , $buf1);
		if (strstr($buf1,"{LOGHMS}") && strstr($buf1,"{/LOGHMS}")) {
			list($dat1,$dd,$dat2) = word_sepa("{LOGHMS}", "{/LOGHMS}", $buf1);
			$buf1 = $dat1."<a href=\"".PHP_SELF."?eid=".$eid."\">".date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."</a>".$dat2;
		}
		if (IKEY == 1) {
			$c = "C";
		}else{
			$c = "comments ";
		}
		if ($d_cok == "1") {
			$logcomment = $c."(x)";
		}else{
			$comment_cnt = CommentCount($eid);
			$logcomment = "<a href=\"".PHP_SELF."?eid=".$eid."#comments\">".$c."(".$comment_cnt.")</a>";
		}
		$buf1 = str_replace ("{LOGCOMMENT}", $logcomment, $buf1);

		if (IKEY == 1) {
			$t = "T";
		}else{
			$t = "trackback ";
		}
		if ($d_tok == "1") {
			$logtrackback = $t."(x)";
		}else{
			$trackback_cnt = TrackbackCount($eid);
			$logtrackback = "<a href=\"".PHP_SELF."?eid=".$eid."#trackback\">".$t."(".$trackback_cnt.")</a>";
		}
		$buf1 = str_replace ("{LOGTRACKBACK}", $logtrackback, $buf1);
		if ($d_tok == "0") {
			$about = HOMELINK."index.php?eid=".$eid;
			$identifier = $about;
			$rss_tzd = date("O", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)));
			$rss_tzd = substr($rss_tzd,0,3).":".substr($rss_tzd,3,2);
			$date = date("Y-m-d", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."T".date("H:i:s", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$rss_tzd;
			if (TTYPE != 1) {
				$trackback = TRACKBACKADDR."/".$eid;
			}else{
				$trackback = TRACKBACKADDR."?".$eid;
			}
			$buf1 .= rdf_make($about, $identifier, $d_title, $d_mes, $p_name, $date, $trackback);
		}
		$skin = $skin1.$buf1.$skin2;

		list($skin1,$buf2,$skin2) = word_sepa("{COMMENTLOOP}", "{/COMMENTLOOP}", $skin);
		$tmpbuf = $buf2;
		$skin3 = "";
		if ($d_cok == "1") {
			$skin = $skin1.$skin3.$skin2;
			$skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $skin);
		}else{
			if ($cmtlist = IDCheck($eid, 1)) {
				@sort($cmtlist);
				for ($i = 0; $i < count($cmtlist); $i++ ) {
					$cmt = file(LOGDIR.substr($cmtlist[$i], 3, 4)."/".$cmtlist[$i]);
					krsort($cmt);
					while (list($cmtkey, $cmtval) = each($cmt)) {
						$cmtval = preg_replace( "/\n$/", "", $cmtval );
						$cmtval = preg_replace( "/\r$/", "", $cmtval );
						list($c_eid, $c_date, $c_time, $c_name, $c_email, $c_mes, $ip_addr, ,$c_url) = explode("<>", $cmtval);
						if ($qry_data == $c_eid) {
							$c_mes = str_replace("{", "&#123", $c_mes);		//テンプレート処理で誤動作しないように変換
							$c_mes = str_replace("}", "&#125", $c_mes);		//                  〃
							$buf2 = str_replace ("{COMMENTBODY}", $c_mes, $tmpbuf);
							if (!strlen($c_email)) {
								$buf2 = str_replace ("{COMMENTEMAIL}", "", $buf2);
								$buf2 = str_replace ("{/COMMENTEMAIL}", "", $buf2);
							}else{
								$commentemail = "<a href=\"mailto:".$c_email."\">";
								$commentemaile = "</a>";
								$buf2 = str_replace ("{COMMENTEMAIL}", $commentemail, $buf2);
								$buf2 = str_replace ("{/COMMENTEMAIL}", $commentemaile, $buf2);
							}
							if (!strlen($c_url)) {
								$buf2 = str_replace ("{COMMENTURL}", "", $buf2);
								$buf2 = str_replace ("{/COMMENTURL}", "", $buf2);
							}else{
								$commenturl = "<a href=\"".$c_url."\" target=\"_blank\">";
								$commenturle = "</a>";
								$buf2 = str_replace ("{COMMENTURL}", $commenturl, $buf2);
								$buf2 = str_replace ("{/COMMENTURL}",  $commenturle, $buf2);
							}
							$buf2 = str_replace ("{COMMENTUSER}", $c_name, $buf2);
							if (strstr($buf2,"{COMMENTYMD}") && strstr($buf2,"{/COMMENTYMD}")) {
								list($dat1,$dd,$dat2) = word_sepa("{COMMENTYMD}", "{/COMMENTYMD}", $buf2);
								$buf2 = $dat1.date($dd,mktime(substr($c_time,0,2),substr($c_time,2,2),substr($c_time,4,2),substr($c_date,4,2), substr($c_date,6,2), substr($c_date,0,4))).$dat2;
							}
							if (strstr($buf2,"{COMMENTHMS}") && strstr($buf2,"{/COMMENTHMS}")) {
								list($dat1,$dd,$dat2) = word_sepa("{COMMENTHMS}", "{/COMMENTHMS}", $buf2);
								$buf2 = $dat1.date($dd,mktime(substr($c_time,0,2),substr($c_time,2,2),substr($c_time,4,2),substr($c_date,4,2), substr($c_date,6,2), substr($c_date,0,4))).$dat2;
							}

							$commentid = crypt_key($ip_addr);
							$buf2 = str_replace ("{COMMENTID}", $commentid, $buf2);
							$skin3 .= $buf2;
						}
					}
				}
			}else{
				$buf2 = str_replace ("{COMMENTBODY}", "コメントはありません。", $tmpbuf);
				$buf2 = str_replace ("{COMMENTURL}", "", $buf2);
				$buf2 = str_replace ("{/COMMENTURL}", "", $buf2);
				$buf2 = str_replace ("{COMMENTEMAIL}", "", $buf2);
				$buf2 = str_replace ("{/COMMENTEMAIL}", "", $buf2);
				$buf2 = str_replace ("{COMMENTUSER}", "", $buf2);
				$buf2 = preg_replace ("/\{COMMENTYMD\}[\w\W]+?\{\/COMMENTYMD\}/", "", $buf2);
				$buf2 = preg_replace ("/\{COMMENTHMS\}[\w\W]+?\{\/COMMENTHMS\}/", "", $buf2);
				$buf2 = str_replace ("{COMMENTID}", "", $buf2);
				$skin3 = $buf2;
			}
			$skin = $skin1.$skin3.$skin2;
			$skin = str_replace ("{CEID}", $qry_data, $skin);
			$skin = str_replace ("{CNAME}", $_COOKIE["name"], $skin);
			$skin = str_replace ("{CEMAIL}", $_COOKIE["email"], $skin);
			$skin = str_replace ("{CURL}", $_COOKIE["url"], $skin);
			$skin = str_replace ("{COMMENT}", "", $skin);
			$skin = str_replace ("{/COMMENT}", "", $skin);
		}

		list($skin1,$buf2,$skin2) = word_sepa("{TRACKBACKLOOP}", "{/TRACKBACKLOOP}", $skin);
		$tmpbuf = $buf2;
		$skin3 = "";
		if ($d_tok == "1") {
			$skin = $skin1.$skin3.$skin2;
			$skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $skin);
		}else{
			if ($trklist = IDCheck($eid, 2)) {
				@sort($trklist);
				for ($i = 0; $i < count($trklist); $i++ ) {
					$trk = file(LOGDIR.substr($trklist[$i], 3, 4)."/".$trklist[$i]);
					krsort($trk);
					while (list($trkkey, $trkval) = each($trk)) {
						$trkval = preg_replace( "/\n$/", "", $trkval );
						$trkval = preg_replace( "/\r$/", "", $trkval );
						list($t_eid, $blog_name, $t_title, $t_url, $t_excerpt, $t_date, $t_time, , ) = explode("<>", $trkval);
						if ($qry_data == $t_eid) {
							$buf2 = str_replace ("{TRACKBACKTITLE}", $t_title, $tmpbuf);
							$buf2 = str_replace ("{TRACKBACKBODY}", $t_excerpt, $buf2);
							$trackbackuser = "<a href=\"".$t_url."\" target=\"_blank\">".$blog_name."</a>";
							$buf2 = str_replace ("{TRACKBACKUSER}", $trackbackuser, $buf2);

							if (strstr($buf2,"{TRACKBACKYMD}") && strstr($buf2,"{/TRACKBACKYMD}")) {
								list($dat1,$dd,$dat2) = word_sepa("{TRACKBACKYMD}", "{/TRACKBACKYMD}", $buf2);
								$buf2 = $dat1.date($dd,mktime(substr($t_time,0,2),substr($t_time,2,2),substr($t_time,4,2),substr($t_date,4,2), substr($t_date,6,2), substr($t_date,0,4))).$dat2;
							}
							if (strstr($buf2,"{TRACKBACKHMS}") && strstr($buf2,"{/TRACKBACKHMS}")) {
								list($dat1,$dd,$dat2) = word_sepa("{TRACKBACKHMS}", "{/TRACKBACKHMS}", $buf2);
								$buf2 = $dat1.date($dd,mktime(substr($t_time,0,2),substr($t_time,2,2),substr($t_time,4,2),substr($t_date,4,2), substr($t_date,6,2), substr($t_date,0,4))).$dat2;
							}

							$skin3 = $skin3.$buf2;
						}
					}
				}
			}else{
				$buf2 = str_replace ("{TRACKBACKTITLE}", "", $tmpbuf);
				$buf2 = str_replace ("{TRACKBACKBODY}", "トラックバックはありません。", $buf2);
				$buf2 = str_replace ("{TRACKBACKUSER}", "", $buf2);

				$buf2 = preg_replace ("/\{TRACKBACKYMD\}[\w\W]+?\{\/TRACKBACKYMD\}/", "", $buf2);
				$buf2 = preg_replace ("/\{TRACKBACKHMS\}[\w\W]+?\{\/TRACKBACKHMS\}/", "", $buf2);
				$skin3 = $buf2;
			}
			$skin = $skin1.$skin3.$skin2;
			if (TTYPE != 1) {
				$trackbackurl = TRACKBACKADDR."/".$qry_data;
			}else{
				$trackbackurl = TRACKBACKADDR."?".$qry_data;
			}
			$skin = str_replace ("{TRACKBACKURL}", $trackbackurl, $skin);
			$skin = str_replace ("{TRACKBACK}", "", $skin);
			$skin = str_replace ("{/TRACKBACK}", "", $skin);
		}
	}else{
		$skin = $skin1.$skin2;
		$skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $skin);
		$skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $skin);
	}
	$skin = str_replace ("{SITETITLE}", SITENAME."::".$d_title , $skin);
	return $skin;
}


/* ----- ログ表示処理（月別表示） ----- */
function month_log($skin, $qry_data, $qry_page) {
	if ($qry_page == "") $qry_page = 1;
	if (IKEY == 0) {
		$page_st = LOGCOUNT * ($qry_page - 1) + 1;
		$page_ed = $page_st + LOGCOUNT;
	}else{
		$page_st = IMCOUNT * ($qry_page - 1) + 1;
		$page_ed = $page_st + IMCOUNT;
	}
	list($skin1,$buf,$skin2) = word_sepa("{LOGLOOP}", "{/LOGLOOP}", $skin);
	$tmpbuf = $buf;
	$skin3 = "";
	$i = 1;

	$datedir = substr($qry_data,0,4);
	$logname = "log".$qry_data.".dat";
	if (FileCheck($logname, 1, LOGDIR.$datedir)) {
		$log = file(LOGDIR.$datedir."/".$logname);
		while(list ($key, $val) = each($log)) {
			$val = preg_replace( "/\n$/", "", $val );
			$val = preg_replace( "/\r$/", "", $val );
			list($eid, $d_date, $d_time, $cid, $pid, $d_title, $d_mes, $d_more, $d_cok, $d_tok) = explode("<>", $val);
			if ($page_st <= $i && $page_ed > $i) {
				$buf = str_replace ("{LOGTITLE}", $d_title, $tmpbuf);
				if (strstr($buf,"{LOGYMD}") && strstr($buf,"{/LOGYMD}")) {
					list($dat1,$dd,$dat2) = word_sepa("{LOGYMD}", "{/LOGYMD}", $buf);
					$buf = $dat1.date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$dat2;
				}
				$p_name = profile_call($pid);
				$logauthor = "<a href=\"".PHP_SELF."?pid=".$pid."\">".$p_name."</a>";
				$buf = str_replace("{LOGAUTHOR}", $logauthor, $buf);
				$d_mes = IconStr($d_mes);
				$d_mes = tagreplaceStr($d_mes);

				if (IKEY == 1) $d_mes = strip_tags($d_mes,"<br><img><a><blockquote>");

				$buf = str_replace("{LOGBODY}", $d_mes, $buf);
				$buf = str_replace("{LOGMORE}", "", $buf);
				if (trim($d_more) != "") {
					$cont = '<a href="'.HOMELINK.'index.php?eid='.$eid.'" title="続きを読む">';
					$buf = str_replace("{MOREMARK}", $cont, $buf);
					$buf = str_replace("{/MOREMARK}", "</a>", $buf);
				}else{
					$buf = preg_replace("/\{MOREMARK\}[\w\W]+?\{\/MOREMARK\}/", "", $buf);
				}
				if ($cid == -1) {
					$logcategory = "-";
				}else{
					$c_name = category_call($cid);
					$logcategory = "<a href=\"".PHP_SELF."?cid=".$cid."\">".$c_name."</a>";
				}
				$buf = str_replace ("{LOGCATEGORY}", $logcategory , $buf);

				if (strstr($buf,"{LOGHMS}") && strstr($buf,"{/LOGHMS}")) {
					list($dat1,$dd,$dat2) = word_sepa("{LOGHMS}", "{/LOGHMS}", $buf);
					$buf = $dat1."<a href=\"".PHP_SELF."?eid=".$eid."\">".date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."</a>".$dat2;
				}

				if (IKEY == 1) {
					$c = "C";
				}else{
					$c = "comments ";
				}
				if ($d_cok == "1") {
					$logcomment = $c."(x)";
				}else{
					$comment_cnt = CommentCount($eid);
					$logcomment = "<a href=\"".PHP_SELF."?eid=".$eid."#comments\">".$c."(".$comment_cnt.")</a>";
				}
				$buf = str_replace ("{LOGCOMMENT}", $logcomment, $buf);
				if (IKEY == 1) {
					$t = "T";
				}else{
					$t = "trackback ";
				}
				if ($d_tok == "1") {
					$logtrackback = $t."(x)";
				}else{
					$trackback_cnt = TrackbackCount($eid);
					$logtrackback = "<a href=\"".PHP_SELF."?eid=".$eid."#trackback\">".$t."(".$trackback_cnt.")</a>";
				}
				$buf = str_replace ("{LOGTRACKBACK}", $logtrackback, $buf);
				if ($d_tok == "0") {
					$about = HOMELINK."index.php?eid=".$eid;
					$identifier = $about;
					$rss_tzd = date("O", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)));
					$rss_tzd = substr($rss_tzd,0,3).":".substr($rss_tzd,3,2);
					$date = date("Y-m-d", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."T".date("H:i:s", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$rss_tzd;
					if (TTYPE != 1) {
						$trackback = TRACKBACKADDR."/".$eid;
					}else{
						$trackback = TRACKBACKADDR."?".$eid;
					}
					$buf .= rdf_make($about, $identifier, $d_title, $d_mes, $p_name, $date, $trackback);
				}
				$skin3 .= $buf;
			}
			$i++;
		}
	}
	$skin = $skin1.$skin3.$skin2;

	$nextpage = $qry_page - 1;
	$backpage = $qry_page + 1;
	if ($qry_page == 1) {
		$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
	}else{
		$skin = preg_replace("/(\{NEXTPAGE\})([\w\W]+?)(\{\/NEXTPAGE\})/", "<a href=\"".PHP_SELF."?month=".$qry_data."&page=".$nextpage."\">\\2</a>", $skin);
	}
	if (IKEY == 0) {
		$maxpage = ceil(($i - 1) / LOGCOUNT);
	}else{
		$maxpage = ceil(($i - 1) / IMCOUNT);
	}
	if ($maxpage < $backpage) {
		$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
	}else{
		$skin = preg_replace("/(\{BACKPAGE\})([\w\W]+?)(\{\/BACKPAGE\})/", "<a href=\"".PHP_SELF."?month=".$qry_data."&page=".$backpage."\">\\2</a>", $skin);
	}
	return $skin;
}


/* ----- ログ表示処理（日別表示） ----- */
function day_log($skin, $qry_data, $qry_page) {
	if ($qry_page == "") $qry_page = 1;
	if (IKEY == 0) {
		$page_st = LOGCOUNT * ($qry_page - 1) + 1;
		$page_ed = $page_st + LOGCOUNT;
	}else{
		$page_st = IMCOUNT * ($qry_page - 1) + 1;
		$page_ed = $page_st + IMCOUNT;
	}
	list($skin1,$buf,$skin2) = word_sepa("{LOGLOOP}", "{/LOGLOOP}", $skin);
	$tmpbuf = $buf;
	$skin3 = "";
	$i = 1;

	if ($log = file(LOGDIR.substr($qry_data,0,4)."/log".substr($qry_data,0,6).".dat")) {
		while(list ($key, $val) = each($log)) {
			$val = preg_replace( "/\n$/", "", $val );
			$val = preg_replace( "/\r$/", "", $val );
			list($eid, $d_date, $d_time, $cid, $pid, $d_title, $d_mes, $d_more, $d_cok, $d_tok) = explode("<>", $val);
			if ($qry_data == $d_date) {
				if ($page_st <= $i && $page_ed > $i) {
					$buf = str_replace ("{LOGTITLE}", $d_title, $tmpbuf);
					if (strstr($buf,"{LOGYMD}") && strstr($buf,"{/LOGYMD}")) {
						list($dat1,$dd,$dat2) = word_sepa("{LOGYMD}", "{/LOGYMD}", $buf);
						$buf = $dat1.date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$dat2;
					}
					$p_name = profile_call($pid);
					$logauthor = "<a href=\"".PHP_SELF."?pid=".$pid."\">".$p_name."</a>";
					$buf = str_replace ("{LOGAUTHOR}", $logauthor, $buf);
					$d_mes = IconStr($d_mes);
					$d_mes = tagreplaceStr($d_mes);

					if (IKEY == 1) $d_mes = strip_tags($d_mes,"<br><img><a><blockquote>");

					$buf = str_replace("{LOGBODY}", $d_mes, $buf);
					$buf = str_replace("{LOGMORE}", "", $buf);
					if (trim($d_more) != "") {
						$cont = '<a href="'.HOMELINK.'index.php?eid='.$eid.'" title="続きを読む">';
						$buf = str_replace("{MOREMARK}", $cont, $buf);
						$buf = str_replace("{/MOREMARK}", "</a>", $buf);
					}else{
						$buf = preg_replace("/\{MOREMARK\}[\w\W]+?\{\/MOREMARK\}/", "", $buf);
					}

					if ($cid == -1) {
						$logcategory = "-";
					}else{
						$c_name = category_call($cid);
						$logcategory = "<a href=\"".PHP_SELF."?cid=".$cid."\">".$c_name."</a>";
					}
					$buf = str_replace ("{LOGCATEGORY}", $logcategory , $buf);

					if (strstr($buf,"{LOGHMS}") && strstr($buf,"{/LOGHMS}")) {
						list($dat1,$dd,$dat2) = word_sepa("{LOGHMS}", "{/LOGHMS}", $buf);
						$buf = $dat1."<a href=\"".PHP_SELF."?eid=".$eid."\">".date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."</a>".$dat2;
					}

					if (IKEY == 1) {
						$c = "C";
					}else{
						$c = "comments ";
					}
					if ($d_cok == "1") {
						$logcomment = $c."(x)";
					}else{
						$comment_cnt = CommentCount($eid);
						$logcomment = "<a href=\"".PHP_SELF."?eid=".$eid."#comments\">".$c."(".$comment_cnt.")</a>";
					}
					$buf = str_replace("{LOGCOMMENT}", $logcomment, $buf);
					if (IKEY == 1) {
						$t = "T";
					}else{
						$t = "trackback ";
					}
					if ($d_tok == "1") {
						$logtrackback = $t."(x)";
					}else{
						$trackback_cnt = TrackbackCount($eid);
						$logtrackback = "<a href=\"".PHP_SELF."?eid=".$eid."#trackback\">".$t."(".$trackback_cnt.")</a>";
					}
					$buf = str_replace("{LOGTRACKBACK}", $logtrackback, $buf);
					if ($d_tok == "0") {
						$about = HOMELINK."index.php?eid=".$eid;
						$identifier = $about;
						$rss_tzd = date("O", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)));
						$rss_tzd = substr($rss_tzd,0,3).":".substr($rss_tzd,3,2);
						$date = date("Y-m-d", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."T".date("H:i:s", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$rss_tzd;
						if (TTYPE != 1) {
							$trackback = TRACKBACKADDR."/".$eid;
						}else{
							$trackback = TRACKBACKADDR."?".$eid;
						}
						$buf .= rdf_make($about, $identifier, $d_title, $d_mes, $p_name, $date, $trackback);
					}
					$skin3 .= $buf;
				}
				$i++;
			}
		}
	}
	$skin = $skin1.$skin3.$skin2;

	$nextpage = $qry_page - 1;
	$backpage = $qry_page + 1;
	if ($qry_page == 1) {
		$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
	}else{
		$skin = preg_replace("/(\{NEXTPAGE\})([\w\W]+?)(\{\/NEXTPAGE\})/", "<a href=\"".PHP_SELF."?day=".$qry_data."&page=".$nextpage."\">\\2</a>", $skin);
	}
	if (IKEY == 0) {
		$maxpage = ceil(($i - 1) / LOGCOUNT);
	}else{
		$maxpage = ceil(($i - 1) / IMCOUNT);
	}
	if ($maxpage < $backpage) {
		$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
	}else{
		$skin = preg_replace("/(\{BACKPAGE\})([\w\W]+?)(\{\/BACKPAGE\})/", "<a href=\"".PHP_SELF."?day=".$qry_data."&page=".$backpage."\">\\2</a>", $skin);
	}
	return $skin;
}


/* ----- ログ表示処理（カテゴリ別表示） ----- */
function category_log($skin, $qry_data, $qry_page) {
	if ($qry_page == "") $qry_page = 1;
	if (IKEY == 0) {
		$page_st = LOGCOUNT * ($qry_page - 1) + 1;
		$page_ed = $page_st + LOGCOUNT;
	}else{
		$page_st = IMCOUNT * ($qry_page - 1) + 1;
		$page_ed = $page_st + IMCOUNT;
	}
	list($skin1,$buf,$skin2) = word_sepa("{LOGLOOP}", "{/LOGLOOP}", $skin);
	$tmpbuf = $buf;
	$skin3 = "";
	$i = 1;

	
	if ($loglist = LogFileList(0)){
		while(list ($key, $val) = each($loglist)) {
			$log = file(LOGDIR.substr($val,3,4)."/".$val);
			while (list($logkey, $logval) = each($log)) {
				$logval = preg_replace( "/\n$/", "", $logval );
				$logval = preg_replace( "/\r$/", "", $logval );
				list($eid, $d_date, $d_time, $cid, $pid, $d_title, $d_mes, $d_more, $d_cok, $d_tok) = explode("<>", $logval);
				if ($qry_data == $cid) {
					if ($page_st <= $i && $page_ed > $i) {
						$buf = str_replace ("{LOGTITLE}", $d_title, $tmpbuf);
						if (strstr($buf,"{LOGYMD}") && strstr($buf,"{/LOGYMD}")) {
							list($dat1,$dd,$dat2) = word_sepa("{LOGYMD}", "{/LOGYMD}", $buf);
							$buf = $dat1.date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$dat2;
						}
						$p_name = profile_call($pid);
						$logauthor = "<a href=\"".PHP_SELF."?pid=".$pid."\">".$p_name."</a>";
						$buf = str_replace ("{LOGAUTHOR}", $logauthor, $buf);
						$d_mes = IconStr($d_mes);
						$d_mes = tagreplaceStr($d_mes);

						if (IKEY == 1) $d_mes = strip_tags($d_mes,"<br><img><a><blockquote>");

						$buf = str_replace("{LOGBODY}", $d_mes, $buf);
						$buf = str_replace("{LOGMORE}", "", $buf);
						if (trim($d_more) != "") {
							$cont = '<a href="'.HOMELINK.'index.php?eid='.$eid.'" title="続きを読む">';
							$buf = str_replace("{MOREMARK}", $cont, $buf);
							$buf = str_replace("{/MOREMARK}", "</a>", $buf);
						}else{
							$buf = preg_replace("/\{MOREMARK\}[\w\W]+?\{\/MOREMARK\}/", "", $buf);
						}
						if ($cid == -1) {
							$logcategory = "-";
						}else{
							$c_name = category_call($cid);
							$logcategory = "<a href=\"".PHP_SELF."?cid=".$cid."\">".$c_name."</a>";
						}
						$buf = ereg_replace ("\{LOGCATEGORY\}", $logcategory , $buf);

						if (strstr($buf,"{LOGHMS}") && strstr($buf,"{/LOGHMS}")) {
							list($dat1,$dd,$dat2) = word_sepa("{LOGHMS}", "{/LOGHMS}", $buf);
							$buf = $dat1."<a href=\"".PHP_SELF."?eid=".$eid."\">".date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."</a>".$dat2;
						}

						if (IKEY == 1) {
							$c = "C";
						}else{
							$c = "comments ";
						}
						if ($d_cok == "1") {
							$logcomment = $c."(x)";
						}else{
							$comment_cnt = CommentCount($eid);
							$logcomment = "<a href=\"".PHP_SELF."?eid=".$eid."#comments\">".$c."(".$comment_cnt.")</a>";
						}
						$buf = str_replace ("{LOGCOMMENT}", $logcomment, $buf);
						if (IKEY == 1) {
							$t = "T";
						}else{
							$t = "trackback ";
						}
						if ($d_tok == "1") {
							$logtrackback = $t."(x)";
						}else{
							$trackback_cnt = TrackbackCount($eid);
							$logtrackback = "<a href=\"".PHP_SELF."?eid=".$eid."#trackback\">".$t."(".$trackback_cnt.")</a>";
						}
						$buf = str_replace ("{LOGTRACKBACK}", $logtrackback, $buf);
						if ($d_tok == "0") {
							$about = HOMELINK."index.php?eid=".$eid;
							$identifier = $about;
							$rss_tzd = date("O", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)));
							$rss_tzd = substr($rss_tzd,0,3).":".substr($rss_tzd,3,2);
							$date = date("Y-m-d", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."T".date("H:i:s", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$rss_tzd;
							if (TTYPE != 1) {
								$trackback = TRACKBACKADDR."/".$eid;
							}else{
								$trackback = TRACKBACKADDR."?".$eid;
							}
							$buf .= rdf_make($about, $identifier, $d_title, $d_mes, $p_name, $date, $trackback);
						}
						$skin3 .= $buf;
					}
					$i++;
				}
			}
		}
	}
	$skin = $skin1.$skin3.$skin2;

	$nextpage = $qry_page - 1;
	$backpage = $qry_page + 1;
	if ($qry_page == 1) {
		$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
	}else{
		$skin = preg_replace("/(\{NEXTPAGE\})([\w\W]+?)(\{\/NEXTPAGE\})/", "<a href=\"".PHP_SELF."?cid=".$qry_data."&page=".$nextpage."\">\\2</a>", $skin);
	}
	if (IKEY == 0) {
		$maxpage = ceil(($i - 1) / LOGCOUNT);
	}else{
		$maxpage = ceil(($i - 1) / IMCOUNT);
	}
	if ($maxpage < $backpage) {
		$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
	}else{
		$skin = preg_replace("/(\{BACKPAGE\})([\w\W]+?)(\{\/BACKPAGE\})/", "<a href=\"".PHP_SELF."?cid=".$qry_data."&page=".$backpage."\">\\2</a>", $skin);
	}
	$skin = str_replace ("{SITETITLE}", SITENAME."::".$c_name , $skin);
	return $skin;
}


/* ----- ログ表示処理（初期表示） ----- */
function normal_log($skin, $qry_page) {
	if ($qry_page == "") $qry_page = 1;
	if (IKEY == 0) {
		$page_st = LOGCOUNT * ($qry_page - 1) + 1;
		$page_ed = $page_st + LOGCOUNT;
	}else{
		$page_st = IMCOUNT * ($qry_page - 1) + 1;
		$page_ed = $page_st + IMCOUNT;
	}
	list($skin1,$buf,$skin2) = word_sepa("{LOGLOOP}", "{/LOGLOOP}", $skin);
	$tmpbuf = $buf;
	$skin3 = "";
	$i = 1;

	if ($loglist = LogFileList(0)){
		while(list ($key, $val) = each($loglist)) {
			$log = file(LOGDIR.substr($val,3,4)."/".$val);
			while (list($logkey, $logval) = each($log)) {
				if ($page_st <= $i && $page_ed > $i) {
					$logval = preg_replace( "/\n$/", "", $logval );
					$logval = preg_replace( "/\r$/", "", $logval );
					list($eid, $d_date, $d_time, $cid, $pid, $d_title, $d_mes, $d_more, $d_cok, $d_tok) = explode("<>", $logval);
					$buf = str_replace ("{LOGTITLE}", $d_title, $tmpbuf);
					if (strstr($buf,"{LOGYMD}") && strstr($buf,"{/LOGYMD}")) {
						list($dat1,$dd,$dat2) = word_sepa("{LOGYMD}", "{/LOGYMD}", $buf);
						$buf = $dat1.date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$dat2;
					}
					$p_name = profile_call($pid);
					$logauthor = "<a href=\"".PHP_SELF."?pid=".$pid."\">".$p_name."</a>";
					$buf = ereg_replace ("\{LOGAUTHOR\}", $logauthor, $buf);
					if ($cid == -1) {
						$logcategory = "-";
					}else{
						$c_name = category_call($cid);
						$logcategory = "<a href=\"".PHP_SELF."?cid=".$cid."\">".$c_name."</a>";
					}
					$buf = str_replace ("{LOGCATEGORY}", $logcategory , $buf);

					if (strstr($buf,"{LOGHMS}") && strstr($buf,"{/LOGHMS}")) {
						list($dat1,$dd,$dat2) = word_sepa("{LOGHMS}", "{/LOGHMS}", $buf);
						$buf = $dat1."<a href=\"".PHP_SELF."?eid=".$eid."\">".date($dd,mktime(substr($d_time,0,2),substr($d_time,2,2),substr($d_time,4,2),substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."</a>".$dat2;
					}

					if (IKEY == 1) {
						$c = "C";
					}else{
						$c = "comments ";
					}
					if ($d_cok == "1") {
						$logcomment = $c."(x)";
					}else{
						$comment_cnt = CommentCount($eid);
						$logcomment = "<a href=\"".PHP_SELF."?eid=".$eid."#comments\">".$c."(".$comment_cnt.")</a>";
					}
					$buf = str_replace ("{LOGCOMMENT}", $logcomment, $buf);
					if (IKEY == 1) {
						$t = "T";
					}else{
						$t = "trackback ";
					}
					if ($d_tok == "1") {
						$logtrackback = $t."(x)";
					}else{
						$trackback_cnt = TrackbackCount($eid);
						$logtrackback = "<a href=\"".PHP_SELF."?eid=".$eid."#trackback\">".$t."(".$trackback_cnt.")</a>";
					}
					$buf = str_replace ("{LOGTRACKBACK}", $logtrackback, $buf);
					$d_mes = IconStr($d_mes);
					$d_mes = tagreplaceStr($d_mes);

					if (IKEY == 1) $d_mes = strip_tags($d_mes,"<br><img><a><blockquote>");
					$buf = str_replace("{LOGBODY}", $d_mes, $buf);
					$buf = str_replace("{LOGMORE}", "", $buf);
					if (trim($d_more) != "") {
						$cont = '<a href="'.HOMELINK.'index.php?eid='.$eid.'" title="続きを読む">';
						$buf = str_replace("{MOREMARK}", $cont, $buf);
						$buf = str_replace("{/MOREMARK}", "</a>", $buf);
					}else{
						$buf = preg_replace("/\{MOREMARK\}[\w\W]+?\{\/MOREMARK\}/", "", $buf);
					}

					if ($d_tok == "0") {
						$about = HOMELINK."index.php?eid=".$eid;
						$identifier = $about;
						$rss_tzd = date("O", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)));
						$rss_tzd = substr($rss_tzd,0,3).":".substr($rss_tzd,3,2);
						$date = date("Y-m-d", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."T".date("H:i:s", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$rss_tzd;
						if (TTYPE != 1) {
							$trackback = TRACKBACKADDR."/".$eid;
						}else{
							$trackback = TRACKBACKADDR."?".$eid;
						}
						$buf .= rdf_make($about, $identifier, $d_title, $d_mes, $p_name, $date, $trackback);
					}
					$skin3 .= $buf;
				}
				$i++;
			}
		}
	}
	$skin = $skin1.$skin3.$skin2;
	$nextpage = $qry_page - 1;
	$backpage = $qry_page + 1;
	if ($qry_page == 1) {
		$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
	}else{
		$skin = preg_replace("/(\{NEXTPAGE\})([\w\W]+?)(\{\/NEXTPAGE\})/", "<a href=\"".PHP_SELF."?page=".$nextpage."\">\\2</a>", $skin);
	}
	if (IKEY == 0) {
		$maxpage = ceil(($i - 1) / LOGCOUNT);
	}else{
		$maxpage = ceil(($i - 1) / IMCOUNT);
	}
	if ($maxpage < $backpage) {
		$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
	}else{
		$skin = preg_replace("/(\{BACKPAGE\})([\w\W]+?)(\{\/BACKPAGE\})/", "<a href=\"".PHP_SELF."?page=".$backpage."\">\\2</a>", $skin);
	}
	return $skin;
}


/* ----- criptによるキー生成 ----- */
function crypt_key($ip_addr) {
	if ($ip_addr == "") {
		$return_key = "";
	}else{
		list($key1, $key2, $key3) = explode(".", $ip_addr);
		$key1 = substr(sprintf("%03d", $key1), 1, 2);
		$key2 = substr(sprintf("%03d", $key2), 1, 2);
		$key3 = substr(sprintf("%03d", $key3), 1, 2);
		$key = $key1.$key2.$key3;
		$cryptkey = crypt($key, "jd");
		$return_key = substr($cryptkey, -8);
	}
	return $return_key;
}


/* ----- プロフィールネーム取得 ----- */
function profile_call($p_id){
	if (file_exists(LOGDIR."profile.dat")){
		$profile = file(LOGDIR."profile.dat");
		//$profileから改行コード削除
		for ( $k = 0; $k < count( $profile ); $k++ ) {
			$profile[$k] = preg_replace( "/\n$/", "", $profile[$k] );
			$profile[$k] = preg_replace( "/\r$/", "", $profile[$k] );
			list($pid, $p_name, $p_data, $p_img) = explode("<>", $profile[$k]);
			if ($p_id == $pid) {
				$p_data = $p_name;
				break;
			}else{
				$p_data = -1;
			}
		}
  }else{
		$p_data = -1;
	}
	return $p_data;
}


/* ----- カテゴリー取得 ----- */
function category_call($c_id){
	if (file_exists(LOGDIR."category.dat")){
		$category = file(LOGDIR."category.dat");
		//$categoryから改行コード削除
		if (count($category) != 0) {
			for ( $k = 0; $k < count( $category ); $k++ ) {
				$category[$k] = preg_replace( "/\n$/", "", $category[$k] );
				$category[$k] = preg_replace( "/\r$/", "", $category[$k] );
				list($cid, $c_name) = explode("<>", $category[$k]);
				if ($c_id == $cid) {
					$c_data = $c_name;
					break;
				}else{
					$c_data = "-";
			  }
			}
		}else{
			$c_data = "-";
		}
  }else{
		$c_data = "-";
	}
	return $c_data;
}


/* ----- セパレーター ----- */
function word_sepa($key1, $key2, $val) {
	list($newval[0],$buf) = explode($key1, $val, 2);
	list($newval[1], $newval[2]) = explode($key2, $buf, 2);
	return $newval;
}


/* ----- RDF生成 ----- */
function rdf_make($about, $identifier, $title, $description, $creator, $date, $trackback){
	$title = htmlspecialchars($title);
	if (strlen($title) > 100) $title = substr($title,0,100);
	$description = str_replace("&lt;", "<", $description);
	$description = str_replace("&gt;", ">", $description);
	$description = str_replace("&quot;", "\"", $description);		//  ”にもどす
	$description = preg_replace("/(<img src=)\"[\.\/]*?([^<>]+[[:alnum:]\/\"]+\")(>)/i", "\\1\"".HOMELINK."\\2 />", $description);
	$description = preg_replace("/(<a href src=)\"[\.\/]*?([^<>]+[[:alnum:]\/\"]+\")(>)/i", "\\1\"".HOMELINK."\\2>", $description);


	$description = CleanHtml($description);
	$description = htmlspecialchars($description);
	if (strlen($description) > 500) $description = substr($description,0,500);

	$rdffile = '<!--
	<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	         xmlns:dc="http://purl.org/dc/elements/1.1/"
	         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
	<rdf:Description
	   rdf:about="'.$about.'"
	   dc:identifier="'.$identifier.'"
	   dc:title="'.$title.'"
	   dc:description="'.$description.'"
	   dc:creator="'.$creator.'"
	   dc:date="'.$date.'"
	   trackback:ping="'.$trackback.'" />
	</rdf:RDF>
	-->';
	return $rdffile;
}



/* ----- スキンファイルを表示（PHPスクリプト検出＆実行） ----- */
function get_skin_php ($skin) {
	$target = 0;
	$i = 0;
	$tmpbuf = $skin;
	while ($target == 0) {
		if (strstr($tmpbuf, "{INCLUDE}") && strstr($tmpbuf, "{/INCLUDE}")) {
			list($buf[$i], $p_buf[$i], $tmpbuf) = word_sepa("{INCLUDE}", "{/INCLUDE}", $tmpbuf);
			$i++;
		}else{
			$buf[$i] = $tmpbuf;
			break;
		}
	}
	if (IKEY == 0) {
		if (CHARSET == 0) {
			header("Content-Type: text/html; charset=Shift_JIS"); 
		}elseif (CHARSET == 1) {
			header("Content-Type: text/html; charset=EUC-JP"); 
		}elseif (CHARSET == 2) {
			header("Content-Type: text/html; charset=UTF-8"); 
		}
	}else{
		header("Content-Type: text/html; charset=Shift_JIS"); 
	}
	for ($i = 0; $i < count($buf); $i++) {
		echo $buf[$i];
		if ($i < count($p_buf)) include($p_buf[$i]);
	}
}


/* ----- RSS生成 ----- */
function rss_view($profile){
	if ($filelist = LogFileList(0)) {
		list($pid, $p_name, $p_email, $p_data, $p_img) = explode("<>", $profile[0]);
		$rssdata =  '<?xml version="1.0" encoding="UTF-8" ?>
		<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
		         xmlns="http://purl.org/rss/1.0/"
		         xmlns:dc="http://purl.org/dc/elements/1.1/"
		         xmlns:content="http://purl.org/rss/1.0/modules/content/"
		         xmlns:cc="http://web.resource.org/cc/" xml:lang="ja">
		<channel rdf:about="'.HOMELINK.'index.php?mode=rss">
		<title>'.SITENAME.'</title>
		<link>'.HOMELINK.'</link>
		<description>'.SITEDESC.'</description>
		<dc:language>ja</dc:language>
		<items>
		<rdf:Seq>';
		$i = 0;
		$breakkey = 0;
		while (list($key, $val) = each($filelist)) {
			if ($breakkey == 1) break;
			$log = file(LOGDIR.substr($val,3,4)."/".$val);
			while (list($logkey, $logval) = each($log)) {
				list($eid) = explode("<>", $logval, 2);
				$rssdata .= '<rdf:li rdf:resource="'.HOMELINK.'index.php?eid='.$eid.'" />';
				$i++;
				if (LOGCOUNT <= $i) {
					$breakkey = 1;
					break;
				}
			}
		}
		$rssdata .= '
		</rdf:Seq>
		</items>
		</channel>
		';
		$filelist = LogFileList(0);
		$i = 0;
		$breakkey = 0;
		while (list($key, $val) = each($filelist)) {
			if ($breakkey == 1) break;
			$log = file(LOGDIR.substr($val,3,4)."/".$val);
			while (list($logkey, $logval) = each($log)) {
				list($eid, $d_date, $d_time, $cid, $pid, $d_title, $d_mes, $d_more, $d_cok, $d_tok) = explode("<>", $logval);
				$rss_tzd = date("O", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)));
				$rss_tzd = substr($rss_tzd,0,3).":".substr($rss_tzd,3,2);
				$date = date("Y-m-d", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4)))."T".date("H:i:s", mktime(substr($d_time,0,2), substr($d_time,2,2), substr($d_time,4,2), substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).$rss_tzd;
				$d_mes = str_replace("&lt;", "<", $d_mes);
				$d_mes = str_replace("&gt;", ">", $d_mes);
				$d_mes = str_replace("&quot;", "\"", $d_mes);		//  ”にもどす
				$d_mes = preg_replace("/(<img src=)\"[\.\/]*([^<>]+[[:alnum:]\/\"]+\")(>)/i", "\\1\"".HOMELINK."\\2 />", $d_mes);
				$d_mes = preg_replace("/(<a href=)\"[\.\/]*([^<>]+[[:alnum:]\/\"]+\")(>)/i", "\\1\"".HOMELINK."\\2>", $d_mes);

				$desc = CleanHtml($d_mes);
				$desc = htmlspecialchars($desc);
				$desc = mbtrim($desc,500);
				$d_title = htmlspecialchars($d_title);
				$d_title = mbtrim($d_title,100);
				$rssdata .= '<item rdf:about="'.HOMELINK.'index.php?eid='.$eid.'"><link>'.HOMELINK.'index.php?eid='.$eid.'</link><title>'.$d_title.'</title><description>'.$desc.'</description><content:encoded><![CDATA['.$d_mes.']]></content:encoded><dc:subject /><dc:date>'.$date.'</dc:date><dc:creator>'.$p_name.'</dc:creator><dc:publisher>Blogn</dc:publisher><dc:rights>'.$p_name.'</dc:rights></item>';
				$i++;
				if (LOGCOUNT <= $i) {
					$breakkey = 1;
					break;
				}
			}
		}
		$rssdata .= '</rdf:RDF>';
		$rssdata = mbConv($rssdata,1,4);
		header("Content-Type: application/xml; charset=UTF-8"); 
		print $rssdata;
	}
}


/* ----- 携帯からの投稿処理 ----- */
function mobile_check() {
	$mobile = file(LOGDIR."mobile.dat");
	$receive_addr = preg_replace( "/\r\n$/", "", $mobile[0]);
	$receive_addr = preg_replace( "/\n$/", "", $receive_addr);
	$send_addr = preg_replace( "/\r\n$/", "", $mobile[1]);
	$send_addr = preg_replace( "/\n$/", "", $send_addr);
	$pop = preg_replace( "/\r\n$/", "", $mobile[2]);
	$pop = preg_replace( "/\n$/", "", $pop);
	$loginid = preg_replace( "/\r\n$/", "", $mobile[3]);
	$loginid = preg_replace( "/\n$/", "", $loginid);
	$loginpass = preg_replace( "/\r\n$/", "", $mobile[4]);
	$loginpass = preg_replace( "/\n$/", "", $loginpass);
	$apop = preg_replace( "/\r\n$/", "", $mobile[6]);
	$apop = preg_replace( "/\n$/", "", $apop);
	$mobile_cok = preg_replace( "/\r\n$/", "", $mobile[7]);
	$mobile_cok = preg_replace( "/\n$/", "", $mobile_cok);
	$mobile_tok = preg_replace( "/\r\n$/", "", $mobile[8]);
	$mobile_tok = preg_replace( "/\n$/", "", $mobile_tok);
	$mobile_category = preg_replace( "/\r\n$/", "", $mobile[9]);
	$mobile_category = preg_replace( "/\n$/", "", $mobile_category);

	if (!strlen($receive_addr) || !strlen($send_addr) || !strlen($pop) || !strlen($loginid) || !strlen($loginpass) || !strlen($apop) || !strlen($mobile_cok) || !strlen($mobile_tok) || !strlen($mobile_category)) return;

	if (!$fp = fsockopen ($pop, 110, $errno, $errstr, 30)) return;	// 繋がらなかったらreturn

	$buf = fgets($fp, 512);
	if(substr($buf, 0, 3) != '+OK') die($buf);
	if($apop == 1) {
		$arraybuf = explode(" ", trim($buf));
		$md5pass = md5($arraybuf[count($arraybuf) - 1].$loginpass);
		$buf = send_cmd($fp, "APOP $loginid $md5pass");
	} else {
		$buf = send_cmd($fp, "USER $loginid");
		$buf = send_cmd($fp, "PASS $loginpass");
	}
	$buf = send_cmd($fp, "STAT");		// +OK $num $size
	sscanf($buf, '+OK %d %d', $num, $size);
	if ($num == "0") {
		$buf = send_cmd($fp, "QUIT");
		fclose($fp);
		return;	// 受信件数0ならreturn
	}
	//メールデータ取得
	$cnt = 0;
	for($i = 1 ; $i <= $num ; $i++ ) {
		$line = send_cmd($fp, "RETR $i");
		while (!preg_match("/^\.\r\n/",$line)) {
			$line = fgets($fp, 512);
			$tmpdata[$i].= $line;
		}

		list($head, $body) = split("\r\n\r\n", $tmpdata[$i], 2);
		$body = preg_replace("/\r\n[\t ]+/", " ", $body);

		// 送信者アドレスの抽出
		if (eregi("From:[ \t]*([^\r\n]+)", $head, $freg)) {
			$from = addr_search($freg[1]);
		} elseif (eregi("Reply-To:[ \t]*([^\r\n]+)", $head, $freg)) {
			$from = addr_search($freg[1]);
		} elseif (eregi("Return-Path:[ \t]*([^\r\n]+)", $head, $freg)) {
			$from = addr_search($freg[1]);
		}
		// 送信者アドレスが登録アドレスの場合
		if ($from == $send_addr) {
			$cnt++;
			$receive_data[$cnt] = $tmpdata[$i];
			$buf = send_cmd($fp, "DELE $i");
		}
	}
	$buf = send_cmd($fp, "QUIT"); 
	fclose($fp);


	for ($i = 1 ; $i <= $cnt ; $i++ ) {
		$subject = $from = $text = $atta = $part = $attach = "";

		list($head, $body) = split("\r\n\r\n", $receive_data[$i], 2);
		$body = preg_replace("/\r\n[\t ]+/", " ", $body);

		// 日付の取得
		eregi("Date:[ \t]*([^\r\n]+)", $head, $tmp_date);
		// タイムゾーンの取得と加減算
		$tmp_date[1] = ereg_replace("[ ]{2}"," 0",$tmp_date[1]); // <- Docomo 写メール時の処理
		$tmpdate1_array = explode(" ", $tmp_date[1]);
		$tmp_tz_str = $tmpdate1_array[5];
		$tmp_tz_hour = (int)substr($tmp_tz_str, 1, 2);
		$tmp_tz_minute = (int)substr($tmp_tz_str, 3, 2);
		$tmp_tz = $tmp_tz_hour * 3600 + $tmp_tz_minute * 60;
		if (substr($tmp_tz_str, 0, 1) == "+") {
			$now = strtotime($tmp_date[1]) + $tmp_tz;
		} else {
			$now = strtotime($tmp_date[1]) - $tmp_tz;
		}
		if ($now == -1) $now = time()+TIMEZONE;

		$head = preg_replace("/\r\n? /", "", $head);
		// サブジェクトの取得
		if (eregi("\nSubject:[ \t]*([^\r\n]+)", $head, $tmp_sub)) {
			$subject = $tmp_sub[1];
			while (eregi("(.*)=\?iso-2022-jp\?B\?([^\?]+)\?=(.*)",$subject,$regs)) {
				$subject = $regs[1].base64_decode($regs[2]).$regs[3];
			}
			while (eregi("(.*)=\?iso-2022-jp\?Q\?([^\?]+)\?=(.*)",$subject,$regs)) {
				$subject = $regs[1].quoted_printable_decode($regs[2]).$regs[3];
			}
			$subject = htmlspecialchars(mbConv($subject,0,1));
		}
		if (eregi("\nContent-type:.*multipart/",$head)) {
			eregi('boundary="([^"]+)"', $head, $boureg);
			$body = str_replace($boureg[1], urlencode($boureg[1]), $body);
			$part = split("\r\n--".urlencode($boureg[1])."-?-?",$body);
			if (eregi('boundary="([^"]+)"', $body, $boureg2)) {
				$body = str_replace($boureg2[1], urlencode($boureg2[1]), $body);
				$body = preg_replace("/\r\n--/".urlencode($boureg[1])."-?-?\r\n","",$body);
				$part = split("\r\n--".urlencode($boureg2[1])."-?-?",$body);
			}
		} else {
			$part[0] = $receive_data[$i];
		}

		// ログデータ読み込み
		$old_eid = file(LOGDIR."id.dat");
		$old_eid[0] = preg_replace("/\n$/", "", $old_eid[0]);
		$old_eid[0] = preg_replace("/\r$/", "", $old_eid[0]);
		$d_eid = $old_eid[0] + 1;

		foreach ($part as $multi) {
			list($m_head, $m_body) = split("\r\n\r\n", $multi, 2);
			$m_body = preg_replace("/\r\n[\t ]+/", " ", $m_body);
			$m_body = preg_replace("/\r\n\.\r\n$/", "", $m_body);

			if (!eregi("Content-type: *([^;\n]+)", $m_head, $type)) continue;
			list($main, $sub) = explode("/", $type[1]);
			// 本文をデコード
			if (strtolower($main) == "text") {
				if (eregi("Content-Transfer-Encoding:.*base64", $m_head)) $m_body = base64_decode($m_body);
				if (eregi("Content-Transfer-Encoding:.*quoted-printable", $m_head)) $m_body = quoted_printable_decode($m_body);
				$text = mbConv($m_body,0,1);
				if ($sub == "html") $text = strip_tags($text);
				// 電話番号削除
				$text = preg_replace("/([[:digit:]]{11})|([[:digit:]\-]{13})/", "", $text);
				// 下線削除
				$text = preg_replace("/[_]{25,}/", "", $text);
				// mac削除
				$text = preg_replace("/Content-type: multipart\/appledouble;[[:space:]]boundary=(.*)/","",$text);

				$text = str_replace(">","&gt;",$text);
				$text = str_replace("<","&lt;",$text);
				$text = str_replace("\r\n", "\r",$text);
				$text = str_replace("\r", "\n",$text);
				$text = preg_replace("/\n{2,}/", "\n\n", $text);
				$text = str_replace("\n", "<br>", $text);
			}
			// 添付データがある場合
			if (eregi("name=\"?([^\"\n]+)\"?",$m_head, $filereg)) {
				$filename = preg_replace("/[\t\r\n]/", "", $filereg[1]);
				$finfo = pathinfo($filename);
				$upfile_name = strtolower($d_eid.".".$finfo["extension"]);
			}
			$subtype = "gif|jpe?g|png";
			if (eregi("Content-Transfer-Encoding:.*base64", $m_head) && eregi($subtype, $finfo["extension"])) {
				$upfile = base64_decode($m_body);
				$fp = fopen(PICDIR.$upfile_name, "w");
				fputs($fp, $upfile);
				fclose($fp);
			}
		}
		if ($upfile_name != "") {
			$dest = PICDIR.$upfile_name;
			$size = @getimagesize($dest);
			if ($size[0] > MAXWIDTH || $size[1] > MAXHEIGHT) {
				$ratio1 = MAXWIDTH / $size[0];
				$ratio2 = MAXHEIGHT / $size[1];
				if ($ratio1 < $ratio2) {
					$ratio = $ratio1;
				}else{
					$ratio = $ratio2;
				}
				$width = round($size[0] * $ratio);
				$height = round($size[1] * $ratio);
				$text = "<a href=\"".PICDIR.$upfile_name."\" target=\"_blank\"><img src=\"".PICDIR.$upfile_name."\" width=\"".$width."\" height=\"".$height."\"></a><br>".$text;
			}else{
				$text = "<img src=\"".PICDIR.$upfile_name."\" ".$size[3]."><br>".$text;
			}
		}
		$d_date = gmdate("Ymd",$now);
		$d_time = gmdate("His",$now);
		$log = $d_eid."<>".$d_date."<>".$d_time."<>".$mobile_category."<>0<>".$subject."<>".$text."<><>".$mobile_cok."<>".$mobile_tok."\r\n";

		//ログファイル書き込み
		$logname = "log".substr($d_date, 0, 6).".dat";
		$err = FileSearch($logname);
		if ($err == -1 || $err == 0) {
			exit;
		}else{
			$newlog = @file(LOGDIR.substr($d_date, 0, 4)."/".$logname);
			$datetime = $d_date.$d_time;
			$newkey = DateSearch($datetime, $newlog);
			if ($newkey == -1) {
				$fp = fopen(LOGDIR.substr($d_date, 0, 4)."/".$logname, "w");
				flock($fp, LOCK_EX);
				fputs($fp, $log);
				fclose($fp);
			}else{
				array_splice($newlog, $newkey, 0, $log);
				$fp = fopen(LOGDIR.substr($d_date, 0, 4)."/".$logname, "w");
				flock($fp, LOCK_EX);
				fputs($fp, implode('',$newlog));
				fclose($fp);
			}
		}
		$fp = fopen(LOGDIR."id.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, $d_eid);
		fclose($fp);
	}
}


/* ----- コマンド送信 ----- */
function send_cmd($fp, $cmd) {
	fputs($fp, $cmd."\r\n");
	$buf = fgets($fp, 512);
	if(substr($buf, 0, 3) == '+OK') {
		return $buf;
	} else {
		die($buf);
	}
	return false;
}


/* ----- メールアドレスを抽出する ----- */
function addr_search($addr) {
	if (eregi("[-!#$%&\'*+\\./0-9A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+", $addr, $fromreg)) {
		return $fromreg[0];
	} else {
		return false;
	}
}



?>
