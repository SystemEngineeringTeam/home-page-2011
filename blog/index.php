<?php
//--------------------------------------------------------------------
// Weblog PHP script BlognPlus
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//--------------------------------------------------------------------
// index.php
//
// LAST UPDATE 2007/01/19
//
// ・携帯投稿での不具合を修正
// ・コメント投稿処理表示を修正
//
//--------------------------------------------------------------------


//-------------------------------------------------------------------- 初期設定
/* ===== タイムアタック ===== */
$blogn_timestart = explode(" ",microtime());

/* ===== 初期設定ファイル読み込み ===== */
include("./conf.php");
include("./common.php");

//-------------------------------------------------------------------- アクセス禁止処理
if (!blogn_mod_db_ip_check($_SERVER["REMOTE_ADDR"])) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

/* ===== セッションスタート ===== */
session_set_cookie_params(0, BLOGN_REQUESTDIR);
session_start();
session_register("blogn_session_id");
session_register("blogn_session_pw");

//-------------------------------------------------------------------- ログイン処理
$blogn_login_error = "";
if (isset($_POST["blogn_req_id"]) && isset($_POST["blogn_req_pw"])) {
	// ユーザーチェック
	$blogn_error = blogn_mod_db_user_check($_POST["blogn_req_id"], $_POST["blogn_req_pw"]);
	if ($blogn_error[0]) {
		$_SESSION["blogn_session_id"] = $_POST["blogn_req_id"];
		$_SESSION["blogn_session_pw"] = $_POST["blogn_req_pw"];
		$blogn_user = true;
	}else{
		$blogn_user = false;
		$blogn_login_error = "ユーザーIDまたはパスワードが違います。";
	}
}else{
	if (!$_COOKIE["blogn_cookie_pw"]) {
		if (!isset($_SESSION["blogn_session_id"])) {
			$blogn_user = false;
		}else{
			$blogn_error = blogn_mod_db_user_check($_SESSION["blogn_session_id"], $_SESSION["blogn_session_pw"]);
			if (!$blogn_error[0]) {
				$blogn_user = false;
			}else{
				$blogn_user = true;
			}
		}
	}else{
		$blogn_error = blogn_mod_db_user_check($_COOKIE["blogn_cookie_id"], $_COOKIE["blogn_cookie_pw"]);
		if (!$blogn_error[0]) {
			$blogn_user = false;
		}else{
			$blogn_user = true;
		}
	}
}

//-------------------------------------------------------------------- 携帯投稿処理
$mobile_users = blogn_mod_db_mobile_access();

if ($mobile_users[0]) {
	$error = blogn_mobile_blog_new($mobile_users[1]);
}


//-------------------------------------------------------------------- URLリクエスト
if ($_POST['mode'] == "comment") {
	$blogn_view_mode = "mode";
}else{
	$blogn_qry = $_SERVER['QUERY_STRING'];
	list($blogn_view_mode, $blogn_qry_data) = explode("=", $blogn_qry, 2);
}

//-------------------------------------------------------------------- スキン選択処理
if ($_GET["e"] != "") $blogn_entry_flag = $_GET["e"];
if ($_GET["m"] != "") $blogn_date_flag = $_GET["m"];
if ($_GET["d"] != "") $blogn_date_flag = $_GET["d"];
if ($_GET["p"] != "") $blogn_user_flag = $_GET["p"];
if ($_GET["u"] != "") $blogn_user_flag = $_GET["u"];
if ($_GET["c"] != "") {
	if (ereg("-", $_GET["c"])) {
		$blogn_category_flag = str_replace("-", "|", $_GET["c"]);
	}else{
		$blogn_category_flag = $_GET["c"]."|";
	}
}
$ua = explode("/",$_SERVER["HTTP_USER_AGENT"]);
$blogn_skin = blogn_skin_selector($blogn_view_mode, $blogn_entry_flag, $blogn_date_flag, $blogn_user_flag, $blogn_category_flag, $ua);
if (!$blogn_skin) {
	$error = "表示するスキンが登録されていません。";
	header("Content-Type: text/html; charset=UTF-8"); 
	echo $error;
	exit;
}

//-------------------------------------------------------------------- モジュール処理

$blogn_modules = blogn_module_load();
if ($blogn_modules[0]) {
	while(list($key, $val) = each($blogn_modules[1])) {
		if ($val["viewer"]) {
			include(BLOGN_MODDIR.$key."/".$val["function"]);
			include(BLOGN_MODDIR.$key."/".$val["viewer"]);
		}
	}
}

//-------------------------------------------------------------------- 表示処理
$blogn_skin = preg_replace ("/\{HOMELINK\}/", BLOGN_HOMELINK , $blogn_skin);
$blogn_skin = preg_replace ("/\{SITENAME\}/", BLOGN_SITENAME , $blogn_skin);
$blogn_skin = preg_replace ("/\{SITEDESC\}/", BLOGN_SITEDESC , $blogn_skin);
$blogn_skin = preg_replace ("/\{VERSION\}/", BLOGN_VERSION , $blogn_skin);

if ($blogn_user) {
	$blogn_skin = preg_replace("/\{LOGINUSER\}/", "ログインモード", $blogn_skin);
}else{
	$blogn_skin = preg_replace("/\{LOGINUSER\}/", "ゲストモード", $blogn_skin);
}

$blogn_skin = preg_replace("/\{LOGINERROR\}/", $blogn_login_error, $blogn_skin);



if ($_GET["page"] != "") {
	$blogn_qry_page = @$_GET["page"];
}else{
	$blogn_qry_page = 1;
}

/* サイドバー表示 */
$blogn_skin = blogn_newentries_call($blogn_user, $blogn_skin);
$blogn_skin = blogn_recomments_call($blogn_user, $blogn_skin);
$blogn_skin = blogn_retrackback_call($blogn_user, $blogn_skin);
$blogn_skin = blogn_categorylist_call($blogn_user, $blogn_skin);
$blogn_skin = blogn_archives_call($blogn_user, $blogn_skin);
$blogn_skin = blogn_linkslist_call($blogn_skin);
$blogn_skin = blogn_profilelist_call($blogn_user, $blogn_skin);


/* メイン表示 */
switch ($blogn_view_mode) {
	case "s":
		$blogn_qry_search = @$_GET["s"];
		$blogn_skin = str_replace ("{SITETITLE}", BLOGN_SITENAME."::サイト内検索結果" , $blogn_skin);
		$blogn_skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{LOG\}[\w\W]+?\{\/LOG\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $blogn_skin);
		$blogn_skin = blogn_search_log($blogn_user, $blogn_skin, $blogn_qry_search);
		break;
	case "p":
		$blogn_user_name = blogn_mod_db_user_profile_load($_GET["p"]);
		$blogn_skin = str_replace ("{SITETITLE}", BLOGN_SITENAME."::".$blogn_user_name["name"]."のプロフィール" , $blogn_skin);
		$blogn_skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{LOG\}[\w\W]+?\{\/LOG\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $blogn_skin);
		$blogn_skin = blogn_profile_log($blogn_skin, $_GET["p"]);
		break;
	case "m":
		$blogn_month = @$_GET["m"];
		$blogn_skin = str_replace ("{SITETITLE}", BLOGN_SITENAME."::".substr($_GET["m"],0,4)."年".substr($_GET["m"],4,2)."月" , $blogn_skin);
		$blogn_skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $blogn_skin);
		$blogn_skin = blogn_view($blogn_user, $blogn_skin, $blogn_qry_page, "month", $blogn_month);
		break;
	case "d":
		$blogn_day = @$_GET["d"];
		$blogn_skin = str_replace ("{SITETITLE}", BLOGN_SITENAME."::".substr($_GET["d"],0,4)."年".substr($_GET["d"],4,2)."月".substr($_GET["d"],6,2)."日" , $blogn_skin);
		$blogn_skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $blogn_skin);
		$blogn_skin = blogn_view($blogn_user, $blogn_skin, $blogn_qry_page, "day", $blogn_day);
		break;
	case "c":
		$blogn_category = @$_GET["c"];
		$blogn_skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $blogn_skin);
		$blogn_skin = blogn_view($blogn_user, $blogn_skin, $blogn_qry_page, "category", $blogn_category);
		break;
	case "u":
		$blogn_user_id = @$_GET["u"];
		$blogn_skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $blogn_skin);
		$blogn_skin = blogn_view($blogn_user, $blogn_skin, $blogn_qry_page, "user", $blogn_user_id);
		break;
	case "e":
		$blogn_entry_id = @$_GET["e"];
		$blogn_skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{COMMENTLIST\}[\w\W]+?\{\/COMMENTLIST\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{COMMENTNEW\}[\w\W]+?\{\/COMMENTNEW\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{TRACKBACKLIST\}[\w\W]+?\{\/TRACKBACKLIST\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{TRACKBACKNEW\}[\w\W]+?\{\/TRACKBACKNEW\}/", "", $blogn_skin);
		$blogn_skin = blogn_entry_view($blogn_user, $blogn_skin, $blogn_entry_id);
		break;
	case "mode":
		if ($_GET["mode"] == "comment" || $_POST["mode"] == "comment") {
			blogn_input_comment($blogn_user, $_POST["blogn_cid"], $_POST["blogn_cname"], $_POST["blogn_cemail"], $_POST["blogn_curl"], $_POST["blogn_cmes"], $_POST["set_cookie"], $_SERVER["REMOTE_ADDR"], $_SERVER["HTTP_USER_AGENT"]);
			exit;
		}elseif ($_GET["mode"] == "rss") {
			header("Content-Type: application/xml; charset=UTF-8"); 
			blogn_rss_view($blogn_user);
			exit;
		}else{
			exit;
		}
		break;
	default:
		$blogn_skin = str_replace ("{SITETITLE}", BLOGN_SITENAME, $blogn_skin);
		$blogn_skin = preg_replace("/\{SEARCH\}[\w\W]+?\{\/SEARCH\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{PROFILES\}[\w\W]+?\{\/PROFILES\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $blogn_skin);
		$blogn_skin = blogn_view($blogn_user, $blogn_skin, $blogn_qry_page, "normal", "");
		break;
}


//-------------------------------------------------------------------- HTML出力処理
$blogn_timeend = explode(" ",microtime());
$blogn_times = ($blogn_timeend[0] - $blogn_timestart[0]) + ($blogn_timeend[1] - $blogn_timestart[1]);
$blogn_timeattack = "処理時間 ".$blogn_times."秒";
$blogn_skin = preg_replace ("/\{TIMEATTACK\}/", $blogn_timeattack, $blogn_skin);

$blogn_skin = ereg_replace("<br />", "<br>", $blogn_skin);
// brタグの後ろに改行コードを入れる
$blogn_skin = eregi_replace("<br>", "<br>\n", $blogn_skin);

if (BLOGN_MOBILE_KEY == 0) {
	switch (BLOGN_CHARSET) {
		case "0":
			// Shift_JIS表示
			$blogn_skin = preg_replace ("/\{CHARSET\}/", "Shift_JIS" , $blogn_skin);
			$blogn_skin = blogn_mbConv($blogn_skin,4,2);
			break;
		case "1":
			// EUC-JP表示
			$blogn_skin = preg_replace ("/\{CHARSET\}/", "EUC-JP" , $blogn_skin);
			$blogn_skin = blogn_mbConv($blogn_skin,4,1);
			break;
		case "2":
			// UFT-8表示
			$blogn_skin = preg_replace ("/\{CHARSET\}/", "UTF-8" , $blogn_skin);
			break;
	}
}else{
	$blogn_skin = preg_replace_callback('/<a[^>]+?><img src=\"([\w\W]+?)\"[^>]*?><\/a>/i', 'blogn_im_callback', $blogn_skin);
	$blogn_skin = preg_replace_callback('/<img src=\"([\w\W]+?)\"[^>]*?>/i', 'blogn_im_callback', $blogn_skin);
	$blogn_skin = blogn_mbConv($blogn_skin, 4, 2);
}


blogn_get_skin_php($blogn_skin);
exit;


//--------------------------------------------------------------------
// メインルーチン終わり
//--------------------------------------------------------------------


//-------------------------------------------------------------------- コメント入力処理
function blogn_input_comment($user, $entry_id, $name, $email = "", $url = "", $mes, $set_cookie, $ip, $agent) {
	$errflg = false;
	$utf_name = blogn_mbConv($name, 0, 4);
	$utf_email = blogn_mbConv($email, 0, 4);
	$utf_url = blogn_mbConv($url, 0, 4);
	$utf_mes = blogn_mbConv($mes, 0, 4);
	$cmtcheck = stristr($_SERVER['HTTP_REFERER'], BLOGN_HOMELINK);
	if(!cmtcheck)$utf_mes = "";
	$blogn_entry_url = BLOGN_HOMELINK."index.php?e=".$entry_id;
//HTTP_REFERER check is delete [utf_mes || $_SERVER["HTTP_REFERER"] != $blogn_entry_url]
	if (!$entry_id || !$utf_name || !$utf_mes) {
		// エラー処理
		//HTTPヘッダ送信
		$link_url = $blogn_entry_url."#comments";
		$info = 'コメント投稿エラー[未記入の箇所があるか、不正な投稿です。]<br><br>画面が自動的に切り替わらない場合は<br><a href="'.$link_url.'">こちら</a>をクリックしてください。';
		$errflg = true;
	}else{
		$logdata = blogn_mod_db_log_load_for_editor($entry_id);
		$diffdays = blogn_date_diff($logdata[1]["date"]);
		if ((BLOGN_LIMIT_COMMENT && BLOGN_LIMIT_COMMENT < $diffdays) || $logdata[1]["comment_ok"] != 1) {
			//HTTPヘッダ送信
			$link_url = $blogn_entry_url."#comments";
			$info = 'コメント投稿エラー[コメント投稿制限がかかっています。]<br><br>画面が自動的に切り替わらない場合は<br><a href="'.$link_url.'">こちら</a>をクリックしてください。';
			$errflg = true;
		}else{
			if (strlen($utf_mes) > BLOGN_COMMENT_SIZE && BLOGN_COMMENT_SIZE != 0) $utf_mes = blogn_mbtrim($utf_mes,BLOGN_COMMENT_SIZE);
			if ($user) {
				$comment = blogn_mod_db_comment_load_for_new(1, 0, 10);
			}else{
				$comment = blogn_mod_db_comment_load_for_new(0, 0, 10);
			}
			// 重複投稿チェック
				if ($comment[0]) {
				while (list($key, $val) = each($comment[1])) {
					if ($val["name"] == $utf_name && $val["comment"] == blogn_mod_db_rn2br($utf_mes)) {
						$link_url = $blogn_entry_url."#comments";
						$info = 'コメント投稿エラー[重複した記事を投稿しようとしています。]<br><br>画面が自動的に切り替わらない場合は<br><a href="'.$link_url.'">こちら</a>をクリックしてください。';
						$errflg = true;
						break;
					}
				}
			}
		}
	}
	if (!$errflg){
		$date = gmdate("YmdHis",time() + BLOGN_TIMEZONE);
		$error = blogn_mod_db_comment_add($entry_id, $logdata[1]["secret"], $date, blogn_html_tag_convert($utf_name), blogn_html_tag_convert($utf_email), blogn_html_tag_convert($utf_url), blogn_html_tag_convert($utf_mes), $ip, $agent);
	}


	if ($error[0]) {
		$link_url = $blogn_entry_url."#cmt".$error[2];
		$info = 'コメントを投稿しました。<br><br>画面が自動的に切り替わらない場合は<br><a href="'.$link_url.'">こちら</a>をクリックしてください。';
		$userlist = blogn_mod_db_user_load();

		$logdata = blogn_mod_db_log_load_for_editor($entry_id);
		$sub = "コメントを受信しました";
		$sub = blogn_mbConv($sub, 4, 3);
		$sub = "=?iso-2022-jp?B?".base64_encode($sub)."?="; 
		$mes = "件名:".$logdata[1]["title"]."\n"; 
		$mes .= "投稿者名:".$utf_name."\n"; 
		$mes .= "URL:".$blogn_entry_url."#cmt".$error[2]."\n"; 
		$mes .= "※このメールアドレスには返信しないでください。"; 
		$mes = blogn_mbConv($mes, 4, 3); 
		$from = BLOGN_SITENAME; 
		$from = blogn_mbConv($from, 4, 3); 
		$from = "=?iso-2022-jp?B?".base64_encode($from)."?="; 
		$from = "From: $from <blognplus@localhost>\nContent-Type: text/plain; charset=\"iso-2022-jp\""; 

		while (list($key, $val) = each($userlist)) {
			if ($val["information_comment"]) @mail($val["information_mail_address"], $sub, $mes, $from); 
		}
	}

	$errdata = file("./template/info.html");
	$errdata = implode("",$errdata);

	if (BLOGN_CHARSET == 0 || BLOGN_MOBILE_KEY == 1) {
		$charset = "Shift_JIS";
		$outkey = 2;
	}elseif (BLOGN_CHARSET == 1) {
		$charset = "EUC-JP";
		$outkey = 1;
	}elseif (BLOGN_CHARSET == 2) {
		$charset = "UTF-8";
		$outkey = 4;
	}

	$errdata = str_replace("{META_LINK}", "<meta http-equiv='refresh' content='2;URL={$link_url}'>", $errdata);
	$errdata = str_replace("{CHARSET}", $charset, $errdata);
	$errdata = str_replace("{INFO}", $info, $errdata);


	if ($set_cookie == "on") {
		setcookie("name", blogn_mbConv($name, 0, 4), time() + BLOGN_TIMEZONE + 604800);
		setcookie("email", blogn_mbConv($email, 0, 4), time() + BLOGN_TIMEZONE + 604800);
		setcookie("url", blogn_mbConv($url, 0, 4), time() + BLOGN_TIMEZONE + 604800);
	}

	header("Content-Type: text/html; charset=$charset");
	if ($outkey != 4) $errdata = blogn_mbConv($errdata, 4, $outkey);
	if ($outkey == 2) $errdata = blogn_magic_quotes($errdata);
	echo $errdata;
	exit;
}


//-------------------------------------------------------------------- 最新記事表示処理
function blogn_newentries_call($user, $skin) {
	if (!preg_match("/\{NE\}/",$skin) || !preg_match("/\{\/NE\}/",$skin)) return $skin;

	list($skin1,$buf,$skin2) = blogn_word_sepa("{NELOOP}", "{/NELOOP}", $skin);
	$skin3 = "";
	$loglist = blogn_mod_db_log_load_for_viewer($user, 0, BLOGN_NEW_ENTRY_VIEW_COUNT);
	if ($loglist[0]) {
		while (list($key, $val) = each($loglist[1])) {
			$tmpbuf = $buf;
			$NEtitle = $val["title"];
			$NElink = '<a href="index.php?e='.$val["id"].'">';
			$NElinke = '</a>';
			$tmpbuf = preg_replace ("/\{NETITLE\}/", $NEtitle, $tmpbuf);
			$tmpbuf = preg_replace ("/\{NELINK\}/", $NElink, $tmpbuf);
			$tmpbuf = preg_replace ("/\{\/NELINK\}/", $NElinke, $tmpbuf);
			if (preg_match("/\{NEYMD\}/",$tmpbuf) && preg_match("/\{\/NEYMD\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = blogn_word_sepa("{NEYMD}", "{/NEYMD}", $tmpbuf);
				$tmpbuf = $dat1.date($dd, mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4))).$dat2;
			}
			if (preg_match("/\{NEHMS\}/",$tmpbuf) && preg_match("/\{\/NEHMS\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = blogn_word_sepa("{NEHMS}", "{/NEHMS}", $tmpbuf);
				$tmpbuf = $dat1.date($dd, mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4))).$dat2;
			}
			$skin3 .= $tmpbuf;
		}
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{NE\}/", "", $skin);
		$skin = preg_replace("/\{\/NE\}/", "", $skin);
	}else{
		$skin = preg_replace("/\{NE\}[\w\W]+?\{\/NE\}/", "", $skin);
	}
	return $skin;
}


//-------------------------------------------------------------------- 最新コメント表示処理
function blogn_recomments_call($user, $skin) {
	if (!preg_match("/\{RC\}/",$skin) || !preg_match("/\{\/RC\}/",$skin)) return $skin;

	$cmtlist = blogn_mod_db_comment_load_for_new($user, 0, BLOGN_COMMENT_VIEW_COUNT);
	if ($cmtlist[0]) {
		$i = 0;
		while (list($cmtkey, $cmtval) = each($cmtlist[1])) {
			$cmtc[$cmtval["entry_id"]] = $cmtval["entry_id"];
			$cmtt[$i] = $cmtval["entry_id"];
			$cmt[$i] = $cmtval;
			$i++;
		}

		while (list($cmtkey, $cmtval) = each($cmtc)) {
			for ($i = 0; $i < count($cmt); $i++) {
				if ($cmtval == $cmtt[$i]) $cmts[] = $cmt[$i];
			}
		}
		list($skin1,$buf,$skin2) = blogn_word_sepa("{RCLOOP1}", "{/RCLOOP1}", $skin);
		list($buf1,$buf2,$buf3) = blogn_word_sepa("{RCLOOP2}", "{/RCLOOP2}", $buf);
		$skin3 = "";

		$oldid = "";
		reset($cmts);
		while (list($cmtkey, $cmtval) = each($cmts)) {
			if ($oldid != "" && $oldid != $cmtval["entry_id"]) {
				$skin3 .= $buf3;
			}
			if ($oldid != $cmtval["entry_id"]) {
				$oldid = $cmtval["entry_id"];
				$logdata = blogn_mod_db_log_load_for_editor($cmtval["entry_id"]);
				$skin3 .= preg_replace("/\{RCTITLE\}/", blogn_html_tag_convert($logdata[1]["title"]), $buf1);
			}
			$tmpbuf = $buf2;
			$name = get_magic_quotes_gpc() ? stripslashes($cmtval["name"]) : $cmtval["name"];				//￥を削除
			$tmpbuf = preg_replace("/\{RCNAME\}/", blogn_html_tag_convert($name), $tmpbuf);
			$RClink = '<a href="index.php?e='.$cmtval["entry_id"].'#cmt'.$cmtval["id"].'">';
			$RClinke = '</a>';
			$tmpbuf = preg_replace ("/\{RCLINK\}/", $RClink, $tmpbuf);
			$tmpbuf = preg_replace ("/\{\/RCLINK\}/", $RClinke, $tmpbuf);
			if (preg_match("/\{RCYMD\}/",$tmpbuf) && preg_match("/\{\/RCYMD\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = blogn_word_sepa("{RCYMD}", "{/RCYMD}", $tmpbuf);
				$tmpbuf = $dat1.date($dd, mktime(substr($cmtval["date"],8,2),substr($cmtval["date"],10,2),substr($cmtval["date"],12,2),substr($cmtval["date"],4,2), substr($cmtval["date"],6,2), substr($cmtval["date"],0,4))).$dat2;
			}
			if (preg_match("/\{RCHMS\}/",$tmpbuf) && preg_match("/\{\/RCHMS\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = blogn_word_sepa("{RCHMS}", "{/RCHMS}", $tmpbuf);
				$tmpbuf = $dat1.date($dd, mktime(substr($cmtval["date"],8,2),substr($cmtval["date"],10,2),substr($cmtval["date"],12,2),substr($cmtval["date"],4,2), substr($cmtval["date"],6,2), substr($cmtval["date"],0,4))).$dat2;
			}
			$skin3 .= $tmpbuf;
		}
		$skin3 .= $buf3;
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{RC\}/", "", $skin);
		$skin = preg_replace("/\{\/RC\}/", "", $skin);
	}else{
		$skin = preg_replace("/\{RC\}[\w\W]+?\{\/RC\}/", "", $skin);
	}
	return $skin;
}


//-------------------------------------------------------------------- 最新トラックバック表示処理
function blogn_retrackback_call($user, $skin) {
	if (!preg_match("/\{RT\}/",$skin) || !preg_match("/\{\/RT\}/",$skin)) return $skin;

	$trklist = blogn_mod_db_trackback_load_for_new(0, BLOGN_TRACKBACK_VIEW_COUNT);
	if ($trklist[0]) {
		$i = 0;
		while (list($trkkey, $trkval) = each($trklist[1])) {
			$trkc[$trkval["entry_id"]] = $trkval["entry_id"];
			$trkt[$i] = $trkval["entry_id"];
			$trk[$i] = $trkval;
			$i++;
		}

		while (list($trkkey, $trkval) = each($trkc)) {
			for ($i = 0; $i < count($trk); $i++) {
				if ($trkval == $trkt[$i]) $trks[] = $trk[$i];
			}
		}
		list($skin1,$buf,$skin2) = blogn_word_sepa("{RTLOOP1}", "{/RTLOOP1}", $skin);
		list($buf1,$buf2,$buf3) = blogn_word_sepa("{RTLOOP2}", "{/RTLOOP2}", $buf);
		$skin3 = "";

		$oldid = "";
		reset($trks);
		while (list($trkkey, $trkval) = each($trks)) {
			if ($oldid != "" && $oldid != $trkval["entry_id"]) {
				$skin3 .= $buf3;
			}
			if ($oldid != $trkval["entry_id"]) {
				$oldid = $trkval["entry_id"];
				$logdata = blogn_mod_db_log_load_for_editor($trkval["entry_id"]);
				$skin3 .= preg_replace("/\{RTTITLE\}/", blogn_html_tag_convert($logdata[1]["title"]), $buf1);
			}

			$RTlink = '<a href="index.php?e='.$trkval["entry_id"].'#trk'.$trkval["id"].'">';
			$RTlinke = '</a>';
			$name = get_magic_quotes_gpc() ? stripslashes($trkval["name"]) : $trkval["name"];				//￥を削除
			$tmpbuf = preg_replace ("/\{RTNAME\}/", blogn_html_tag_convert($name), $buf2);
			$tmpbuf = preg_replace ("/\{RTLINK\}/", $RTlink, $tmpbuf);
			$tmpbuf = preg_replace ("/\{\/RTLINK\}/", $RTlinke, $tmpbuf);
			if (preg_match("/\{RTYMD\}/",$tmpbuf) && preg_match("/\{\/RTYMD\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = blogn_word_sepa("{RTYMD}", "{/RTYMD}", $tmpbuf);
				$tmpbuf = $dat1.date($dd, mktime(substr($trkval["date"],8,2),substr($trkval["date"],10,2),substr($trkval["date"],12,2),substr($trkval["date"],4,2), substr($trkval["date"],6,2), substr($trkval["date"],0,4))).$dat2;
			}
			if (preg_match("/\{RTHMS\}/",$tmpbuf) && preg_match("/\{\/RTHMS\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = blogn_word_sepa("{RTHMS}", "{/RTHMS}", $tmpbuf);
				$tmpbuf = $dat1.date($dd, mktime(substr($trkval["date"],8,2),substr($trkval["date"],10,2),substr($trkval["date"],12,2),substr($trkval["date"],4,2), substr($trkval["date"],6,2), substr($trkval["date"],0,4))).$dat2;
			}
			$skin3 .= $tmpbuf;
		}
		$skin3 .= $buf3;
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{RT\}/", "", $skin);
		$skin = preg_replace("/\{\/RT\}/", "", $skin);
	}else{
		$skin = preg_replace("/\{RT\}[\w\W]+?\{\/RT\}/", "", $skin);
	}
	return $skin;
}


//-------------------------------------------------------------------- カテゴリー一覧表示処理
function blogn_categorylist_call($user, $skin) {
	if (!preg_match("/\{CA\}/",$skin) || !preg_match("/\{\/CA\}/",$skin)) return $skin;

	$category1 = blogn_mod_db_category1_load();
	if ($category1[0]) {
		$category2 = blogn_mod_db_category2_load();

		list($skin1,$buf,$skin2) = blogn_word_sepa("{CALOOP1}", "{/CALOOP1}", $skin);
		list($buf1,$buf2,$buf3) = blogn_word_sepa("{CALOOP2}", "{/CALOOP2}", $buf);
		$skin3 = "";
		while (list($c1key, $c1val) = each($category1[1])) {
			$tmpbuf = $buf1;
			$c1id = $c1key."-";
			$count = blogn_mod_db_log_count_load($user, "category", $c1id);
			if ($count && $c1val["view"]) {
				$CAtitle = get_magic_quotes_gpc() ? stripslashes($c1val["name"]) : $c1val["name"];				//￥を削除
				$CAlink = '<a href="index.php?c='.$c1id.'">';
				$CAlinke = '</a>';
				$tmpbuf = preg_replace ("/\{CATITLE1\}/", $CAtitle, $tmpbuf);
				$tmpbuf = preg_replace ("/\{CALINK1\}/", $CAlink, $tmpbuf);
				$tmpbuf = preg_replace ("/\{\/CALINK1\}/", $CAlinke, $tmpbuf);
				$tmpbuf = preg_replace ("/\{CACOUNT1\}/", $count, $tmpbuf);
				$skin3 .= $tmpbuf;

				if ($category2[0]) {
					reset($category2[1]);
					while (list($c2key, $c2val) = each($category2[1])) {
						if ($c1key == $c2val["id"] && $c2val["view"]) {
							$tmpbuf = $buf2;
							$c1c2id = $c1id.$c2key;
							$count = blogn_mod_db_log_count_load($user, "category", $c1c2id);
							if ($count) {
								$CAtitle = get_magic_quotes_gpc() ? stripslashes($c2val["name"]) : $c2val["name"];				//￥を削除
								$CAlink = '<a href="index.php?c='.$c1c2id.'">';
								$CAlinke = '</a>';
								$CAcount = (int)$count;
								$tmpbuf = preg_replace ("/\{CATITLE2\}/", $CAtitle, $tmpbuf);
								$tmpbuf = preg_replace ("/\{CALINK2\}/", $CAlink, $tmpbuf);
								$tmpbuf = preg_replace ("/\{\/CALINK2\}/", $CAlinke, $tmpbuf);
								$tmpbuf = preg_replace ("/\{CACOUNT2\}/", $count, $tmpbuf);
								$skin3 .= $tmpbuf;
							}
						}
					}
				}
				$skin3 .= $buf3;
			}
		}
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{CA\}/", "", $skin);
		$skin = preg_replace("/\{\/CA\}/", "", $skin);
	}else{
		$skin = preg_replace("/\{CA\}[\w\W]+?\{\/CA\}/", "", $skin);
	}
	return $skin;
}


//-------------------------------------------------------------------- 月一覧処理
function blogn_archives_call($user, $skin) {
	if (!preg_match("/\{AR\}/",$skin) || !preg_match("/\{\/AR\}/",$skin)) return $skin;

	$archivelist = blogn_mod_db_archive_count_load($user, BLOGN_ARCHIVE_VIEW_COUNT);

	if (preg_match("/\{ARLOOP\}/",$skin) && preg_match("/\{\/ARLOOP\}/",$skin)) {
		list($skin1,$buf,$skin2) = blogn_word_sepa("{ARLOOP}", "{/ARLOOP}", $skin);
	}elseif (preg_match("/\{ARLOOPASC\}/",$skin) && preg_match("/\{\/ARLOOPASC\}/",$skin)) {
		list($skin1,$buf,$skin2) = blogn_word_sepa("{ARLOOPASC}", "{/ARLOOPASC}", $skin);
		ksort($archivelist[1]);
	}
	$skin3 = "";
	if ($archivelist[0]) {
		while (list($key, $val) = each($archivelist[1])) {
			$tmpbuf = $buf;
			$ARlink = '<a href="index.php?m='.$key.'">';
			$ARlinke = '</a>';
			$ARcount = (int)$val;
			$tmpbuf = preg_replace ("/\{ARCOUNT\}/", $ARcount, $tmpbuf);
			if (preg_match("/\{ARYM\}/",$tmpbuf) && preg_match("/\{\/ARYM\}/",$tmpbuf)) {
				list($dat1,$dd,$dat2) = blogn_word_sepa("{ARYM}", "{/ARYM}", $tmpbuf);
				$tmpbuf = $dat1.date($dd,mktime(0,0,0,substr($key,4,2), 1, substr($key,0,4))).$dat2;
			}
			$tmpbuf = preg_replace ("/\{ARLINK\}/", $ARlink, $tmpbuf);
			$tmpbuf = preg_replace ("/\{\/ARLINK\}/", $ARlinke, $tmpbuf);
			$skin3 .= $tmpbuf;
		}
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{AR\}/", "", $skin);
		$skin = preg_replace("/\{\/AR\}/", "", $skin);
	}else{
		$skin = preg_replace("/\{AR\}[\w\W]+?\{\/AR\}/", "", $skin);
	}
	return $skin;
}


//-------------------------------------------------------------------- リンクリスト処理
function blogn_linkslist_call($skin) {
	if (!preg_match("/\{LI\}/",$skin) || !preg_match("/\{\/LI\}/",$skin)) return $skin;
	list($skin1,$buf,$skin2) = blogn_word_sepa("{LILOOP1}", "{/LILOOP1}", $skin);
	list($buf1,$buf2,$buf3) = blogn_word_sepa("{LILOOP2}", "{/LILOOP2}", $buf);
	$skin3 = "";

	$linkgroup = blogn_mod_db_link_group_load();
	$linklist = blogn_mod_db_link_load();
	if ($linkgroup[0]) {
		while (list($key, $val) = each($linkgroup[1])) {
			$name = get_magic_quotes_gpc() ? stripslashes($val["name"]) : $val["name"];				//￥を削除
			$skin3 .= preg_replace ("/\{LICATEGORY\}/", $name, $buf1);
			if ($linklist[0]) {
				reset($linklist[1]);
				while (list($linkkey, $linkval) = each($linklist[1])) {
					if ($key == $linkval["group"]) {
						$tmpbuf = $buf2;
						$name = get_magic_quotes_gpc() ? stripslashes($linkval["name"]) : $linkval["name"];				//￥を削除
						$LIname = '<a href="'.$linkval["url"].'" target="_blank">'.$name.'</a>';
						$tmpbuf = preg_replace ("/\{LINAME\}/", $LIname, $tmpbuf);
						$skin3 .= $tmpbuf;
					}
				}
			}
			$skin3 .= $buf3;
		}
		$skin = $skin1.$skin3.$skin2;
		$skin = preg_replace("/\{LI\}/", "", $skin);
		$skin = preg_replace("/\{\/LI\}/", "", $skin);
	}else{
		$skin = preg_replace("/\{LI\}[\w\W]+?\{\/LI\}/", "", $skin);
	}
	return $skin;
}


//-------------------------------------------------------------------- プロフィールリスト処理
function blogn_profilelist_call($user, $skin) {
	if (!preg_match("/\{PR\}/",$skin) || !preg_match("/\{\/PR\}/",$skin)) return $skin;
	list($skin1,$buf,$skin2) = blogn_word_sepa("{PRLOOP}", "{/PRLOOP}", $skin);
	$skin3 = "";
	$userlist = blogn_mod_db_user_load();
	while(list($key, $val) = each($userlist)) {
		$count = blogn_mod_db_log_count_load($user, "user", $key);
		if ($count) {
			$tmpbuf = $buf;
			$prcount = '<a href="index.php?u='.$key.'">'.$count.'</a>';
			$prlink = '<a href="index.php?p='.$key.'">';
			$prlinke = "</a>";
			$tmpbuf = preg_replace ("/\{PRLINK\}/", $prlink, $tmpbuf);
			$tmpbuf = preg_replace ("/\{\/PRLINK\}/", $prlinke, $tmpbuf);
			$tmpbuf = preg_replace ("/\{PRNAME\}/", $val["name"], $tmpbuf);
			$tmpbuf = preg_replace ("/\{PRCOUNT\}/", $prcount, $tmpbuf);
			$skin3 .= $tmpbuf;
		}
	}
	$skin = $skin1.$skin3.$skin2;
	$skin = preg_replace("/\{PR\}/", "", $skin);
	$skin = preg_replace("/\{\/PR\}/", "", $skin);
	return $skin;
}


//-------------------------------------------------------------------- 全文検索処理
function blogn_search_log($user, $skin, $search_key) {
	if (BLOGN_CHARSET != 2) $search_key = blogn_mbConv($search_key, 0, 4);
	$skin = preg_replace ("/\{SEARCH\}/", "", $skin);
	$skin = preg_replace ("/\{\/SEARCH\}/", "", $skin);
	list($skin_top, $buf, $skin_end) = blogn_word_sepa("{SEARCHLOOP}", "{/SEARCHLOOP}", $skin);
	$skin_center = "";

	// 全ログデータ検索
	if (trim($search_key) != "") {
		$loglist = blogn_mod_db_log_load_for_viewer($user, 0, 0);
		while (list($key, $val) = each($loglist[1])) {
			$tmpbuf = $buf;
			$date = date("Y/m/d H:i:s", mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4)));

			$search_data = $date.",".$val["title"].",".$val["mes"].",".$val["more"];
			if (stristr($search_data, $search_key)) {
				$foundurl = '<a href="index.php?e='.$val["id"].'">'.$val["title"].'('.$date.')</a>';
				$tmpbuf = str_replace ("{SEARCHLIST}", $foundurl, $tmpbuf);
				$skin_center .= $tmpbuf;
			}
			$cmtlist = blogn_mod_db_comment_load_for_list($val["id"], 0, 0);
			if ($cmtlist[0]) {
				while (list($cmtkey, $cmtval) = each($cmtlist[1])) {
					$tmpbuf = $buf;
					$cmtdate = date("Y/m/d H:i:s", mktime(substr($cmtval["date"],8,2),substr($cmtval["date"],10,2),substr($cmtval["date"],12,2),substr($cmtval["date"],4,2), substr($cmtval["date"],6,2), substr($cmtval["date"],0,4)));
					$search_data = $cmtdate.",".$cmtval["name"].",".$cmtval["comment"];
					if (stristr($search_data, $search_key)) {
						$foundurl = '<a href="index.php?e='.$val["id"].'#cmt'.$cmtval["id"].'">'.$val["title"].' ⇒ '.$cmtval["name"].'('.$cmtdate.')</a>';
						$tmpbuf = str_replace ("{SEARCHLIST}", $foundurl, $tmpbuf);
						$skin_center .= $tmpbuf;
					}
				}
			}
			$trklist = blogn_mod_db_trackback_load_for_list($val["id"], 0, 0);
			if ($trklist[0]) {
				while (list($trkkey, $trkval) = each($trklist[1])) {
					$tmpbuf = $buf;
					$trkdate = date("Y/m/d H:i:s", mktime(substr($trkval["date"],8,2),substr($trkval["date"],10,2),substr($trkval["date"],12,2),substr($trkval["date"],4,2), substr($trkval["date"],6,2), substr($trkval["date"],0,4)));
					$search_data = $trkdate.",".$trkval["name"].",".$trkval["title"].",".$trkval["mes"];
					if (stristr($search_data, $search_key)) {
						$foundurl = '<a href="index.php?e='.$val["id"].'#trk'.$trkval["id"].'">'.$val["title"].' ⇒ '.$trkval["title"].'('.$trkdate.')</a>';
						$tmpbuf = str_replace ("{SEARCHLIST}", $foundurl, $tmpbuf);
						$skin_center .= $tmpbuf;
					}
				}
			}
		}
	}
	$skin = $skin_top.$skin_center.$skin_end;
	return $skin;
}


//-------------------------------------------------------------------- プロフィール表示処理
function blogn_profile_log($skin, $profile_id) {
	list($skin1,$buf,$skin2) = blogn_word_sepa("{PROFILES}", "{/PROFILES}", $skin);
	$userdata = blogn_mod_db_user_profile_load($profile_id);

	$name = get_magic_quotes_gpc() ? stripslashes($userdata["name"]) : $userdata["name"];				//￥を削除
	$profile = get_magic_quotes_gpc() ? stripslashes($userdata["profile"]) : $userdata["profile"];				//￥を削除
	$profile = blogn_IconStr($profile);

	$buf = str_replace ("{PROFILENAME}", $name, $buf);
	// br_change　チェック
	if (!$userdata["br_change"]) {
		$profile = blogn_rntag2rn($profile);
	}
	$buf = str_replace ("{PROFILEMES}", $profile, $buf);
	$skin = $skin1.$buf.$skin2;
	return $skin;
}


//-------------------------------------------------------------------- 表示処理（指定ID）
function blogn_entry_view($user, $skin, $entry_id) {
	$skin = preg_replace("/\{LOG\}/", "", $skin);
	$skin = preg_replace("/\{LOG[ ]+([\w\W]+?)\}/", "", $skin);
	$skin = preg_replace("/\{\/LOG\}/", "", $skin);
	$nextbackurl = blogn_mod_db_log_nextback_url($user, $entry_id);
	if ($nextbackurl[0]) {
		if ($nextbackurl[1] != -1) {
			$skin = preg_replace("/(\{NEXTPAGE\})([\w\W]+?)(\{\/NEXTPAGE\})/", "<a href=\"index.php?e=".$nextbackurl[1]."\">\\2</a>", $skin);
		}else{
			$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
		}
		if ($nextbackurl[2] != -1) {
			$skin = preg_replace("/(\{BACKPAGE\})([\w\W]+?)(\{\/BACKPAGE\})/", "<a href=\"index.php?e=".$nextbackurl[2]."\">\\2</a>", $skin);
		}else{
			$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
		}
	}else{
		$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
		$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
	}

	list($skin_top, $buf, $skin_end) = blogn_word_sepa("{LOGLOOP}", "{/LOGLOOP}", $skin);
	$skin_center = "";
	$userlist = blogn_mod_db_user_load();
	$category1 = blogn_mod_db_category1_load();
	$category2 = blogn_mod_db_category2_load();

	$logdata = blogn_mod_db_log_load_for_entory($user, $entry_id);

	$nowdate = gmdate("YmdHis",time() + BLOGN_TIMEZONE);
	if ($logdata[0]) {
		if (!$logdata[1]["reserve"] || $logdata[1]["reserve"] && $nowdate > $logdata[1]["date"]) {
			$buf = blogn_log_list_skin_replace($buf, $logdata[1]["id"], $logdata[1], $userlist, $category1[1], $category2[1], 0);

			// 携帯閲覧時はコメント、トラックバックの表示数を新着5件に制限する
			$vcnt = BLOGN_MOBILE_KEY == 0 ? $vcnt = 0 : $vcnt = 5;
			// 携帯処理
			$vtype = "all";

			if ($logdata[1]["comment_ok"]) {
				$buf = blogn_comment_new_skin_replace($buf, $logdata[1]["id"], $vcnt, $vtype, $logdata[1]["date"]);
			}else{
				$buf = preg_replace("/\{COMMENT\}[\w\W]+?\{\/COMMENT\}/", "", $buf);
			}
			if ($logdata[1]["trackback_ok"]) {
				$buf = blogn_trackback_new_skin_replace($buf, $logdata[1]["id"], $vcnt, $vtype, $logdata[1]["date"]);
			}else{
				$buf = preg_replace("/\{TRACKBACK\}[\w\W]+?\{\/TRACKBACK\}/", "", $buf);
			}
			$skin_center .= $buf;
		}
	}
	$skin = $skin_top.$skin_center.$skin_end;
	$skin = str_replace ("{SITETITLE}", BLOGN_SITENAME."::".$logdata[1]["title"] , $skin);

	return $skin;
}


//-------------------------------------------------------------------- 表示処理（月／日／カテゴリー／ユーザー）
function blogn_view($user, $skin, $qry_page, $mode, $key_id){
	$totalentry = blogn_mod_db_log_count_load($user, $mode, $key_id);
	if (!$totalentry) {
		$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
		$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
		$skin = preg_replace("/\{LOG\}[\w\W]+?\{\/LOG\}/", "", $skin);
		return $skin;
	}
	$skin = preg_replace("/\{LOG\}/", "", $skin);
	$skin = preg_replace("/\{\/LOG\}/", "", $skin);

	/* 指定記事表示処理 */
	if (preg_match ("/\{LOG[ ]+([\w\W]+?)\}/", $skin, $regs)) {
		$skin = preg_replace("/\{LOG[ ]+([\w\W]+?)\}/", "", $skin);
		$select_log = explode(",",$regs[1]);
	}else{
		$select_log = "";
	}

	$userlist = blogn_mod_db_user_load();
	$category1 = blogn_mod_db_category1_load();
	$category2 = blogn_mod_db_category2_load();

	if (BLOGN_MOBILE_KEY == 0) {
		$start_key = BLOGN_LOG_VIEW_COUNT * ($qry_page - 1);
		$count = BLOGN_LOG_VIEW_COUNT;
		$maxpage = ceil($totalentry / BLOGN_LOG_VIEW_COUNT);
	}else{
		$start_key = BLOGN_MOBILE_VIEW_COUNT * ($qry_page - 1);
		$count = BLOGN_MOBILE_VIEW_COUNT;
		$maxpage = ceil($totalentry / BLOGN_MOBILE_VIEW_COUNT);
	}
	
	$nextpage = $qry_page - 1;
	$backpage = $qry_page + 1;

	switch ($mode) {
		case "month":
			$url = "?m=".$key_id."&";
			$loglist = blogn_mod_db_log_load_for_month($user, $start_key, $count, $key_id, BLOGN_MONTHLY_VIEW_MODE);
			break;
		case "day":
			$url = "?d=".$key_id."&";
			$loglist = blogn_mod_db_log_load_for_day($user, $start_key, $count, $key_id);
			break;
		case "category":
			$url = "?c=".$key_id."&";
			$loglist = blogn_mod_db_log_load_for_category($user, $start_key, $count, $key_id, BLOGN_CATEGORY_VIEW_MODE);

			reset($loglist[1]);
			$logdata = current($loglist[1]);
			list($c1, $c2) = explode("-", $key_id);
			$category = $category1[1][$c1]["name"];
			if ($c2) $category .= "::".$category2[1][$c2]["name"];
			$skin = str_replace ("{SITETITLE}", BLOGN_SITENAME."::".$category , $skin);

			break;
		case "user":
			$url = "?u=".$key_id."&";
			$loglist = blogn_mod_db_log_load_for_user($user, $start_key, $count, $key_id);

			reset($loglist[1]);
			$logdata = current($loglist[1]);
			$skin = str_replace ("{SITETITLE}", BLOGN_SITENAME."::".$userlist[$logdata["user_id"]]["name"] , $skin);

			break;
		default:
			$url = "?";
			if (!$select_log) {
				$loglist = blogn_mod_db_log_load_for_viewer($user, $start_key, $count);
			}else{
				$loglist = array();
				$i = 0;
				while(list($key, $val) = each($select_log)) {
					$newlog = blogn_mod_db_log_load_for_entory($user, $val);
					$loglist[0] = true;
					$loglist[1][$i]["id"] = $newlog[1]["id"];
					$loglist[1][$i]["date"] = $newlog[1]["date"];
					$loglist[1][$i]["reserve"] = $newlog[1]["reserve"];
					$loglist[1][$i]["secret"] = $newlog[1]["secret"];
					$loglist[1][$i]["user_id"] = $newlog[1]["user_id"];
					$loglist[1][$i]["category"] = $newlog[1]["category"];
					$loglist[1][$i]["comment_ok"] = $newlog[1]["comment_ok"];
					$loglist[1][$i]["trackback_ok"] = $newlog[1]["trackback_ok"];
					$loglist[1][$i]["title"] = $newlog[1]["title"];
					$loglist[1][$i]["mes"] = $newlog[1]["mes"];
					$loglist[1][$i]["more"] = $newlog[1]["more"];
					$loglist[1][$i]["br_change"] = $newlog[1]["br_change"];
					$i++;
				}
			}
			break;
	}
	if ($qry_page == 1 || $select_log) {
		$skin = preg_replace("/\{NEXTPAGE\}[\w\W]+?\{\/NEXTPAGE\}/", "", $skin);
	}else{
		$skin = preg_replace("/(\{NEXTPAGE\})([\w\W]+?)(\{\/NEXTPAGE\})/", "<a href=\"index.php".$url."page=".$nextpage."\">\\2</a>", $skin);
	}
	if ($maxpage < $backpage || $select_log) {
		$skin = preg_replace("/\{BACKPAGE\}[\w\W]+?\{\/BACKPAGE\}/", "", $skin);
	}else{
		$skin = preg_replace("/(\{BACKPAGE\})([\w\W]+?)(\{\/BACKPAGE\})/", "<a href=\"index.php".$url."page=".$backpage."\">\\2</a>", $skin);
	}

	list($skin_top, $buf, $skin_end) = blogn_word_sepa("{LOGLOOP}", "{/LOGLOOP}", $skin);
	$skin_center = "";
	if ($loglist[0]) {
		reset($loglist[1]);
		while (list($key, $val) = each($loglist[1])) {
			$tmpbuf = $buf;
			$tmpbuf = blogn_log_list_skin_replace($tmpbuf, $key, $val, $userlist, $category1[1], $category2[1], 1);
			switch (BLOGN_COMMENT_LIST_TOPVIEW_ON) {
				case "0":		// 件数表示
					// 処理無し
					$tmpbuf = preg_replace("/\{COMMENTLIST\}[\w\W]+?\{\/COMMENTLIST\}/", "", $tmpbuf);
					$tmpbuf = preg_replace("/\{COMMENTNEW\}[\w\W]+?\{\/COMMENTNEW\}/", "", $tmpbuf);
					break;
				case "1":		// 一覧表示
					$tmpbuf = preg_replace("/\{COMMENTNEW\}[\w\W]+?\{\/COMMENTNEW\}/", "", $tmpbuf);
					$tmpbuf = blogn_comment_list_skin_replace($tmpbuf, $val["id"]);
					break;
				case "2":		// 最新5件表示
					$tmpbuf = preg_replace("/\{COMMENTLIST\}[\w\W]+?\{\/COMMENTLIST\}/", "", $tmpbuf);
					// 携帯処理
					$vtype = "5";
					$tmpbuf = blogn_comment_new_skin_replace($tmpbuf, $val["id"], 5, $vtype, "");
					break;
			}

			switch (BLOGN_TRACKBACK_LIST_TOPVIEW_ON) {
				case "0":		// 件数表示
					// 処理無し
					$tmpbuf = preg_replace("/\{TRACKBACKLIST\}[\w\W]+?\{\/TRACKBACKLIST\}/", "", $tmpbuf);
					$tmpbuf = preg_replace("/\{TRACKBACKNEW\}[\w\W]+?\{\/TRACKBACKNEW\}/", "", $tmpbuf);
					break;
				case "1":		// 一覧表示
					$tmpbuf = preg_replace("/\{TRACKBACKNEW\}[\w\W]+?\{\/TRACKBACKNEW\}/", "", $tmpbuf);
					$tmpbuf = blogn_trackback_list_skin_replace($tmpbuf, $val["id"]);
					break;
				case "2":		// 最新5件表示
					$tmpbuf = preg_replace("/\{TRACKBACKLIST\}[\w\W]+?\{\/TRACKBACKLIST\}/", "", $tmpbuf);
					// 携帯処理
					$vtype = "5";
					$tmpbuf = blogn_trackback_new_skin_replace($tmpbuf, $val["id"], 5, $vtype, "");
					break;
			}

			$skin_center .= $tmpbuf;
		}
	}
	$skin = $skin_top.$skin_center.$skin_end;
	return $skin;
}


/* ----- ログリスト用スキン変換 ----- */
function blogn_log_list_skin_replace($tmpbuf, $key, $val, $userlist, $category1, $category2, $mode) {
	$linkurl = '<a href="index.php?e='.$val["id"].'">'.$val["title"].'</a>';
	$tmpbuf = str_replace("{LOGTITLE}", $linkurl, $tmpbuf);

	$logurl = BLOGN_HOMELINK.'index.php?e='.$val["id"]; 
	$tmpbuf = str_replace ("{LOGURL}", $logurl, $tmpbuf);

	if ($val["secret"]) {
		$tmpbuf = preg_replace ("/\{LOGMODE \"(.*?)\"\,\"(.*?)\"\}/", "\\2", $tmpbuf);
	}else{
		$tmpbuf = preg_replace ("/\{LOGMODE \"(.*?)\"\,\"(.*?)\"\}/", "\\1", $tmpbuf);
	}

	list($s1,$dd,$s2) = blogn_word_sepa("{LOGYMD}", "{/LOGYMD}", $tmpbuf);
	$tmpbuf = $s1.date($dd, mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4))).$s2;

	$author = '<a href="index.php?p='.$val["user_id"].'">'.$userlist[$val["user_id"]]["name"].'</a>';
	$tmpbuf = ereg_replace("\{LOGAUTHOR\}", $author, $tmpbuf);

	list($c1, $c2) = explode("|", $val["category"]);

	$category = get_magic_quotes_gpc() ? stripslashes($category1[$c1]["name"]) : $category1[$c1]["name"];				//￥を削除

	if ($c2) {
		$category .= "::";
		$category .= get_magic_quotes_gpc() ? stripslashes($category2[$c2]["name"]) : $category2[$c2]["name"];				//￥を削除
	}
	$categorylink = '<a href="index.php?c='.$c1.'-'.$c2.'">'.$category.'</a>';

	$tmpbuf = str_replace ("{LOGCATEGORY}", $categorylink , $tmpbuf);

	$mes = get_magic_quotes_gpc() ? stripslashes($val["mes"]) : $val["mes"];				//￥を削除
	$mes = blogn_html_tag_restore($mes);
	$mes = blogn_IconStr($mes);
	$mes = blogn_permit_html_tag_restore($mes);

	// br_change　チェック
	if (!$val["br_change"]) {
		$mes = blogn_rntag2rn($mes);
	}
	$tmpbuf = str_replace("{LOGBODY}", $mes, $tmpbuf);
	if ($mode) {
		$tmpbuf = str_replace("{LOGMORE}", "", $tmpbuf);
		if (trim($val["more"]) != "") {
			$cont = '<a href="index.php?e='.$val["id"].'#more" title="続きを読む">';
			$tmpbuf = str_replace("{MOREMARK}", $cont, $tmpbuf);
			$tmpbuf = str_replace("{/MOREMARK}", "</a>", $tmpbuf);
		}else{
			$tmpbuf = preg_replace("/\{MOREMARK\}[\w\W]+?\{\/MOREMARK\}/", "", $tmpbuf);
		}
	}else{
		$more = get_magic_quotes_gpc() ? stripslashes($val["more"]) : $val["more"];				//￥を削除
		$more = blogn_html_tag_restore($more);
		$more = blogn_IconStr($more);
		$more = blogn_permit_html_tag_restore($more);
		$more = '<a name="more"></a>'.$more;

		// br_change　チェック
		if (!$val["br_change"]) {
			$more = blogn_rntag2rn($more);
		}
		$tmpbuf = str_replace("{LOGMORE}", $more, $tmpbuf);
		$tmpbuf = preg_replace("/\{MOREMARK\}[\w\W]+?\{\/MOREMARK\}/", "", $tmpbuf);
	}
	list($s1,$dd,$s2) = blogn_word_sepa("{LOGHMS}", "{/LOGHMS}", $tmpbuf);
	$tmpbuf = $s1.date($dd, mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4))).$s2;

	if (BLOGN_MOBILE_KEY == 1) {
		$c = "C";
	}else{
		$c = "comments ";
	}
	if ($val["comment_ok"] == "1") {
		$comment_cnt = blogn_mod_db_comment_count_load($val["id"]);
		if ($comment_cnt[0]) $logcomment = '<a href="index.php?e='.$val["id"].'#comments">'.$c.'('.$comment_cnt[1].')</a>';
	}else{
		$logcomment = $c.'(x)';
	}
	$tmpbuf = str_replace ("{LOGCOMMENT}", $logcomment, $tmpbuf);

	if (BLOGN_MOBILE_KEY == 1) {
		$t = "T";
	}else{
		$t = "trackback ";
	}
	if ($val["trackback_ok"] == "1") {
		$trackback_cnt = blogn_mod_db_trackback_count_load($val["id"]);
		if ($trackback_cnt[0]) $logtrackback = '<a href="index.php?e='.$val["id"].'#trackback">'.$t.'('.$trackback_cnt[1].')</a>';
	}else{
		$logtrackback = $t.'(x)';
	}
	$tmpbuf = str_replace ("{LOGTRACKBACK}", $logtrackback, $tmpbuf);

	if ($val["trackback_ok"] == "1") {
		$about = BLOGN_HOMELINK."index.php?e=".$val["id"];
		$identifier = $about;
		$rss_tzd = date("O", mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4)));
		$rss_tzd = substr($rss_tzd,0,3).":".substr($rss_tzd,3,2);
		$date = date("Y-m-d", mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4)))."T".date("H:i:s", mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4))).$rss_tzd;
		if (BLOGN_TRACKBACK_SLASH_TYPE != 1) {
			$trackbackurl = BLOGN_TRACKBACKADDR."/".$val["id"];
		}else{
			$trackbackurl = BLOGN_TRACKBACKADDR."?".$val["id"];
		}
		if (BLOGN_MOBILE_KEY != 1) $tmpbuf .= blogn_rdf_make($about, $identifier, $val["title"], $val["mes"], $category, $userlist[$val["user_id"]]["name"], $date, $trackbackurl);
	}
	return $tmpbuf;
}


/* ----- コメントリスト用スキン変換 ----- */
function blogn_comment_list_skin_replace($tmpbuf, $id) {
	$cmtlist = blogn_mod_db_comment_load_for_list($id, 0, 0);
	if ($cmtlist[0]) {
		list($cmt_skin_top,$cmt_buf,$cmt_skin_end) = blogn_word_sepa("{COMMENTLIST}", "{/COMMENTLIST}", $tmpbuf);
		if (eregi("\{COMMENTLISTLOOP\}", $cmt_buf)) {
			list($cmt_buf_top,$cmt_buf_center,$cmt_buf_end) = blogn_word_sepa("{COMMENTLISTLOOP}", "{/COMMENTLISTLOOP}", $cmt_buf);
		}elseif (eregi("\{COMMENTLISTLOOPASC\}", $cmt_buf)) {
			list($cmt_buf_top,$cmt_buf_center,$cmt_buf_end) = blogn_word_sepa("{COMMENTLISTLOOPASC}", "{/COMMENTLISTLOOPASC}", $cmt_buf);
			$cmtlist[1] = array_reverse($cmtlist[1]);
		}
		$cmt_skin_center = "";
		while(list($cmtkey, $cmtval) = each($cmtlist[1])) {
			$cmt_tmp_buf = $cmt_buf_center;
			if (!$cmtval["email"]) {
				$cmt_tmp_buf = str_replace ("{COMMENTEMAIL}", "", $cmt_tmp_buf);
				$cmt_tmp_buf = str_replace ("{/COMMENTEMAIL}", "", $cmt_tmp_buf);
			}else{
				$commentemail = '<a href="mailto:'.$cmtval["email"].'">';
				$commentemaile = '</a>';
				$cmt_tmp_buf = str_replace ("{COMMENTEMAIL}", $commentemail, $cmt_tmp_buf);
				$cmt_tmp_buf = str_replace ("{/COMMENTEMAIL}", $commentemaile, $cmt_tmp_buf);
			}
			if (!$cmtval["url"]) {
				$cmt_tmp_buf = str_replace ("{COMMENTURL}", "", $cmt_tmp_buf);
				$cmt_tmp_buf = str_replace ("{/COMMENTURL}", "", $cmt_tmp_buf);
			}else{
				$commenturl = '<a href="'.$cmtval["url"].'" target="_blank">';
				$commenturle = '</a>';
				$cmt_tmp_buf = str_replace ("{COMMENTURL}", $commenturl, $cmt_tmp_buf);
				$cmt_tmp_buf = str_replace ("{/COMMENTURL}",  $commenturle, $cmt_tmp_buf);
			}
			$name = get_magic_quotes_gpc() ? stripslashes($cmtval["name"]) : $cmtval["name"];				//￥を削除
			$cmt_tmp_buf = str_replace ("{COMMENTUSER}", $name, $cmt_tmp_buf);
			list($s1,$dd,$s2) = blogn_word_sepa("{COMMENTYMD}", "{/COMMENTYMD}", $cmt_tmp_buf);
			$cmt_tmp_buf = $s1.date($dd, mktime(substr($cmtval["date"],8,2),substr($cmtval["date"],10,2),substr($cmtval["date"],12,2),substr($cmtval["date"],4,2), substr($cmtval["date"],6,2), substr($cmtval["date"],0,4))).$s2;

			list($s1,$dd,$s2) = blogn_word_sepa("{COMMENTHMS}", "{/COMMENTHMS}", $cmt_tmp_buf); 
			$cmt_tmp_buf = $s1.date($dd, mktime(substr($cmtval["date"],8,2),substr($cmtval["date"],10,2),substr($cmtval["date"],12,2),substr($cmtval["date"],4,2), substr($cmtval["date"],6,2), substr($cmtval["date"],0,4))).$s2; 

			$commentid = blogn_crypt_key($cmtval["ip"]);
			$cmt_tmp_buf = str_replace ("{COMMENTID}", $commentid, $cmt_tmp_buf);

			$cmt_skin_center .= $cmt_tmp_buf;
		}
		$tmpbuf = $cmt_skin_top.$cmt_buf_top.$cmt_skin_center.$cmt_buf_end.$cmt_skin_end;
	}else{
		$tmpbuf = preg_replace("/\{COMMENTLIST\}[\w\W]+?\{\/COMMENTLIST\}/", "", $tmpbuf);
	}
	return $tmpbuf;
}


/* ----- コメント表示用スキン変換 ----- */
function blogn_comment_new_skin_replace($tmpbuf, $id, $count, $type, $log_date) {
	$cmtlist = blogn_mod_db_comment_load_for_list($id, 0, $count);
	$tmpbuf = str_replace ("{CEID}", $id, $tmpbuf);
	$tmpbuf = str_replace ("{CNAME}", $_COOKIE["name"], $tmpbuf);
	$tmpbuf = str_replace ("{CEMAIL}", $_COOKIE["email"], $tmpbuf);
	$tmpbuf = str_replace ("{CURL}", $_COOKIE["url"], $tmpbuf);
	if ($cmtlist[0]) {
		if ($type == "all") {
			list($cmt_skin_top,$cmt_buf,$cmt_skin_end) = blogn_word_sepa("{COMMENT}", "{/COMMENT}", $tmpbuf);
			if (eregi("\{COMMENTLOOP\}", $cmt_buf)) {
				list($cmt_buf_top,$cmt_buf_center,$cmt_buf_end) = blogn_word_sepa("{COMMENTLOOP}", "{/COMMENTLOOP}", $cmt_buf);
			}elseif (eregi("\{COMMENTLOOPASC\}", $cmt_buf)) {
				list($cmt_buf_top,$cmt_buf_center,$cmt_buf_end) = blogn_word_sepa("{COMMENTLOOPASC}", "{/COMMENTLOOPASC}", $cmt_buf);
				$cmtlist[1] = array_reverse($cmtlist[1]);
			}
		}else{
			list($cmt_skin_top,$cmt_buf,$cmt_skin_end) = blogn_word_sepa("{COMMENTNEW}", "{/COMMENTNEW}", $tmpbuf);
			if (eregi("\{COMMENTNEWLOOP\}", $cmt_buf)) {
				list($cmt_buf_top,$cmt_buf_center,$cmt_buf_end) = blogn_word_sepa("{COMMENTNEWLOOP}", "{/COMMENTNEWLOOP}", $cmt_buf);
			}elseif (eregi("\{COMMENTNEWLOOPASC\}", $cmt_buf)) {
				list($cmt_buf_top,$cmt_buf_center,$cmt_buf_end) = blogn_word_sepa("{COMMENTNEWLOOPASC}", "{/COMMENTNEWLOOPASC}", $cmt_buf);
				$cmtlist[1] = array_reverse($cmtlist[1]);
			}
		}
		$cmt_skin_center = "";
		while(list($cmtkey, $cmtval) = each($cmtlist[1])) {
			$cmt_tmp_buf = $cmt_buf_center;
			$cmt_tmp_buf = '<a name="cmt'.$cmtval["id"].'"></a>'.$cmt_tmp_buf;
			if (!$cmtval["email"]) {
				$cmt_tmp_buf = str_replace ("{COMMENTEMAIL}", "", $cmt_tmp_buf);
				$cmt_tmp_buf = str_replace ("{/COMMENTEMAIL}", "", $cmt_tmp_buf);
			}else{
				$commentemail = '<a href="mailto:'.$cmtval["email"].'">';
				$commentemaile = '</a>';
				$cmt_tmp_buf = str_replace ("{COMMENTEMAIL}", $commentemail, $cmt_tmp_buf);
				$cmt_tmp_buf = str_replace ("{/COMMENTEMAIL}", $commentemaile, $cmt_tmp_buf);
			}
			if (!$cmtval["url"]) {
				$cmt_tmp_buf = str_replace ("{COMMENTURL}", "", $cmt_tmp_buf);
				$cmt_tmp_buf = str_replace ("{/COMMENTURL}", "", $cmt_tmp_buf);
			}else{
				$cmturl = ereg_replace("^http://", "", $cmtval["url"]);
				$commenturl = '<a href="http://'.$cmturl.'" target="_blank">';
				$commenturle = '</a>';
				$cmt_tmp_buf = str_replace ("{COMMENTURL}", $commenturl, $cmt_tmp_buf);
				$cmt_tmp_buf = str_replace ("{/COMMENTURL}",  $commenturle, $cmt_tmp_buf);
			}
			$name = get_magic_quotes_gpc() ? stripslashes($cmtval["name"]) : $cmtval["name"];				//￥を削除
			$cmt_tmp_buf = str_replace ("{COMMENTUSER}", $name, $cmt_tmp_buf);
			list($s1,$dd,$s2) = blogn_word_sepa("{COMMENTYMD}", "{/COMMENTYMD}", $cmt_tmp_buf);
			$cmt_tmp_buf = $s1.date($dd, mktime(substr($cmtval["date"],8,2),substr($cmtval["date"],10,2),substr($cmtval["date"],12,2),substr($cmtval["date"],4,2), substr($cmtval["date"],6,2), substr($cmtval["date"],0,4))).$s2;

			list($s1,$dd,$s2) = blogn_word_sepa("{COMMENTHMS}", "{/COMMENTHMS}", $cmt_tmp_buf); 
			$cmt_tmp_buf = $s1.date($dd, mktime(substr($cmtval["date"],8,2),substr($cmtval["date"],10,2),substr($cmtval["date"],12,2),substr($cmtval["date"],4,2), substr($cmtval["date"],6,2), substr($cmtval["date"],0,4))).$s2; 

			$commentid = blogn_crypt_key($cmtval["ip"]);
			$cmt_tmp_buf = str_replace ("{COMMENTID}", $commentid, $cmt_tmp_buf);

			$comment = get_magic_quotes_gpc() ? stripslashes($cmtval["comment"]) : $cmtval["comment"];				//￥を削除
			$comment = preg_replace("/&lt;(br)&gt;/i", "<br>", $comment);
			$comment = preg_replace("/&lt;(br)([ ]+[\w\W]+?)&gt;/i", "<br\\2>", $comment);

			$cmt_tmp_buf = str_replace ("{COMMENTBODY}", $comment, $cmt_tmp_buf);

			$cmt_skin_center .= $cmt_tmp_buf;
		}
		$tmpbuf = $cmt_skin_top.$cmt_buf_top.$cmt_skin_center.$cmt_buf_end.$cmt_skin_end;
	}else{
		if ($type == "all") {
			$tmpbuf = str_replace ("{COMMENT}", "", $tmpbuf);
			$tmpbuf = str_replace ("{/COMMENT}", "", $tmpbuf);
			$tmpbuf = preg_replace ("/\{COMMENTLOOP\}[\w\W]+?\{\/COMMENTLOOP\}/", "", $tmpbuf);
			$tmpbuf = preg_replace ("/\{COMMENTLOOPASC\}[\w\W]+?\{\/COMMENTLOOPASC\}/", "", $tmpbuf);

		}else{
			$tmpbuf = preg_replace("/\{COMMENTNEW\}[\w\W]+?\{\/COMMENTNEW\}/", "", $tmpbuf);
		}
	}


	$diffdays = blogn_date_diff($log_date);
	if (BLOGN_LIMIT_COMMENT && BLOGN_LIMIT_COMMENT < $diffdays) {
		$tmpbuf = str_replace ("{COMMENTNOINPUT}", "", $tmpbuf);
		$tmpbuf = str_replace ("{/COMMENTNOINPUT}", "", $tmpbuf);
		$tmpbuf = preg_replace ("/\{COMMENTINPUT\}[\w\W]+?\{\/COMMENTINPUT\}/", "", $tmpbuf);
	}else{
		$tmpbuf = str_replace ("{COMMENTINPUT}", "", $tmpbuf);
		$tmpbuf = str_replace ("{/COMMENTINPUT}", "", $tmpbuf);
		$tmpbuf = preg_replace ("/\{COMMENTNOINPUT\}[\w\W]+?\{\/COMMENTNOINPUT\}/", "", $tmpbuf);
	}
	return $tmpbuf;

}


/* ----- トラックバックリスト用スキン変換 ----- */
function blogn_trackback_list_skin_replace($tmpbuf, $id) {
	$trklist = blogn_mod_db_trackback_load_for_list($id, 0, 0);
	if ($trklist[0]) {
		if (BLOGN_TRACKBACK_SLASH_TYPE != 1) {
			$trackbackurl = BLOGN_TRACKBACKADDR."/".$id;
		}else{
			$trackbackurl = BLOGN_TRACKBACKADDR."?".$id;
		}
		$tmpbuf = str_replace ("{TRACKBACKURL}", $trackbackurl, $tmpbuf);
		list($trk_skin_top,$trk_buf,$trk_skin_end) = blogn_word_sepa("{TRACKBACKLIST}", "{/TRACKBACKLIST}", $tmpbuf);
		if (eregi("\{TRACKBACKLISTLOOP\}", $trk_buf)) {
			list($trk_buf_top,$trk_buf_center,$trk_buf_end) = blogn_word_sepa("{TRACKBACKLISTLOOP}", "{/TRACKBACKLISTLOOP}", $trk_buf);
		}elseif (eregi("\{TRACKBACKLISTLOOPASC\}", $trk_buf)) {
			list($trk_buf_top,$trk_buf_center,$trk_buf_end) = blogn_word_sepa("{TRACKBACKLISTLOOPASC}", "{/TRACKBACKLISTLOOPASC}", $trk_buf);
			$trklist[1] = array_reverse($trklist[1]);
		}
		$trk_skin_center = "";
		while(list($trkkey, $trkval) = each($trklist[1])) {
			$trk_tmp_buf = $trk_buf_center;

			$title = get_magic_quotes_gpc() ? stripslashes($trkval["title"]) : $trkval["title"];				//￥を削除
			$trackbactitle = '<a href="'.$trkval["url"].'">'.$title.'</a>';
			$trk_tmp_buf = str_replace ("{TRACKBACKTITLE}", $trackbactitle, $trk_tmp_buf);
			$name = get_magic_quotes_gpc() ? stripslashes($trkval["name"]) : $trkval["name"];				//￥を削除
			$trk_tmp_buf = str_replace ("{TRACKBACKUSER}", blogn_html_tag_convert($name), $trk_tmp_buf);
			list($s1,$dd,$s2) = blogn_word_sepa("{TRACKBACKYMD}", "{/TRACKBACKYMD}", $trk_tmp_buf);
			$trk_tmp_buf = $s1.date($dd, mktime(substr($trkval["date"],8,2),substr($trkval["date"],10,2),substr($trkval["date"],12,2),substr($trkval["date"],4,2), substr($trkval["date"],6,2), substr($trkval["date"],0,4))).$s2;

			list($s1,$dd,$s2) = blogn_word_sepa("{TRACKBACKHMS}", "{/TRACKBACKHMS}", $trk_tmp_buf); 
			$trk_tmp_buf = $s1.date($dd, mktime(substr($trkval["date"],8,2),substr($trkval["date"],10,2),substr($trkval["date"],12,2),substr($trkval["date"],4,2), substr($trkval["date"],6,2), substr($trkval["date"],0,4))).$s2;

			$trk_skin_center .= $trk_tmp_buf;
		}
		$tmpbuf = $trk_skin_top.$trk_buf_top.$trk_skin_center.$trk_buf_end.$trk_skin_end;
	}else{
		$tmpbuf = preg_replace("/\{TRACKBACKLIST\}[\w\W]+?\{\/TRACKBACKLIST\}/", "", $tmpbuf);
	}
	return $tmpbuf;
}


/* ----- トラックバック表示用スキン変換 ----- */
function blogn_trackback_new_skin_replace($tmpbuf,  $id, $count, $type, $log_date) {
	$trklist = blogn_mod_db_trackback_load_for_list($id, 0, $count);
	if (BLOGN_TRACKBACK_SLASH_TYPE != 1) {
		$trackbackurl = BLOGN_TRACKBACKADDR."/".$id;
	}else{
		$trackbackurl = BLOGN_TRACKBACKADDR."?".$id;
	}
	$tmpbuf = str_replace ("{TRACKBACKURL}", $trackbackurl, $tmpbuf);
	if ($trklist[0]) {
		if ($type == "all") {
			list($trk_skin_top, $trk_buf, $trk_skin_end) = blogn_word_sepa("{TRACKBACK}", "{/TRACKBACK}", $tmpbuf);
			if (eregi("\{TRACKBACKLOOP\}", $trk_buf)) {
				list($trk_buf_top,$trk_buf_center,$trk_buf_end) = blogn_word_sepa("{TRACKBACKLOOP}", "{/TRACKBACKLOOP}", $trk_buf);
			}elseif (eregi("\{TRACKBACKLOOPASC\}", $trk_buf)) {
				list($trk_buf_top,$trk_buf_center,$trk_buf_end) = blogn_word_sepa("{TRACKBACKLOOPASC}", "{/TRACKBACKLOOPASC}", $trk_buf);
				$trklist[1] = array_reverse($trklist[1]);
			}
		}else{
			list($trk_skin_top, $trk_buf, $trk_skin_end) = blogn_word_sepa("{TRACKBACKNEW}", "{/TRACKBACKNEW}", $tmpbuf);
			if (eregi("\{TRACKBACKNEWLOOP\}", $trk_buf)) {
				list($trk_buf_top,$trk_buf_center,$trk_buf_end) = blogn_word_sepa("{TRACKBACKNEWLOOP}", "{/TRACKBACKNEWLOOP}", $trk_buf);
			}elseif (eregi("\{TRACKBACKNEWLOOPASC\}", $trk_buf)) {
				list($trk_buf_top,$trk_buf_center,$trk_buf_end) = blogn_word_sepa("{TRACKBACKNEWLOOPASC}", "{/TRACKBACKNEWLOOPASC}", $trk_buf);
				$trklist[1] = array_reverse($trklist[1]);
			}
		}
		$trk_skin_center = "";
		while(list($trkkey, $trkval) = each($trklist[1])) {
			$trk_tmp_buf = $trk_buf_center;
			$trk_tmp_buf = '<a name="trk'.$trkval["id"].'"></a>'.$trk_tmp_buf;
			$title = get_magic_quotes_gpc() ? stripslashes($trkval["title"]) : $trkval["title"];				//￥を削除
			$trackbactitle = '<a href="'.$trkval["url"].'">'.$title.'</a>';
			$trk_tmp_buf = str_replace ("{TRACKBACKTITLE}", $trackbactitle, $trk_tmp_buf);
			$name = get_magic_quotes_gpc() ? stripslashes($trkval["name"]) : $trkval["name"];				//￥を削除
			$trk_tmp_buf = str_replace ("{TRACKBACKUSER}", blogn_html_tag_convert($name), $trk_tmp_buf);
			list($s1,$dd,$s2) = blogn_word_sepa("{TRACKBACKYMD}", "{/TRACKBACKYMD}", $trk_tmp_buf);
			$trk_tmp_buf = $s1.date($dd, mktime(substr($trkval["date"],8,2),substr($trkval["date"],10,2),substr($trkval["date"],12,2),substr($trkval["date"],4,2), substr($trkval["date"],6,2), substr($trkval["date"],0,4))).$s2;

			list($s1,$dd,$s2) = blogn_word_sepa("{TRACKBACKHMS}", "{/TRACKBACKHMS}", $trk_tmp_buf); 
			$trk_tmp_buf = $s1.date($dd, mktime(substr($trkval["date"],8,2),substr($trkval["date"],10,2),substr($trkval["date"],12,2),substr($trkval["date"],4,2), substr($trkval["date"],6,2), substr($trkval["date"],0,4))).$s2;

			$trackback = get_magic_quotes_gpc() ? stripslashes($trkval["mes"]) : $trkval["mes"];				//￥を削除
			$trackback = preg_replace("/&lt;(br)&gt;/i", "<br>", $trackback);
			$trackback = preg_replace("/&lt;(br)([ ]+[\w\W]+?)&gt;/i", "<br\\2>", $trackback);

			$trk_tmp_buf = str_replace ("{TRACKBACKBODY}", $trackback, $trk_tmp_buf);

			$trk_skin_center .= $trk_tmp_buf;
		}
		$tmpbuf = $trk_skin_top.$trk_buf_top.$trk_skin_center.$trk_buf_end.$trk_skin_end;
	}else{
		if ($type == "all") {
			$tmpbuf = str_replace ("{TRACKBACK}", "", $tmpbuf);
			$tmpbuf = str_replace ("{/TRACKBACK}", "", $tmpbuf);
			$tmpbuf = preg_replace("/\{TRACKBACKLOOP\}[\w\W]+?\{\/TRACKBACKLOOP\}/", "", $tmpbuf);
			$tmpbuf = preg_replace ("/\{TRACKBACKLOOPASC\}[\w\W]+?\{\/TRACKBACKLOOPASC\}/", "", $tmpbuf);
		}else{
			$tmpbuf = preg_replace("/\{TRACKBACKNEW\}[\w\W]+?\{\/TRACKBACKNEW\}/", "", $tmpbuf);
		}
	}
	$diffdays = blogn_date_diff($log_date);
	if (BLOGN_LIMIT_TRACKBACK && BLOGN_LIMIT_TRACKBACK < $diffdays) {
		$tmpbuf = str_replace ("{TRACKBACKNOINPUT}", "", $tmpbuf);
		$tmpbuf = str_replace ("{/TRACKBACKNOINPUT}", "", $tmpbuf);
		$tmpbuf = preg_replace ("/\{TRACKBACKINPUT\}[\w\W]+?\{\/TRACKBACKINPUT\}/", "", $tmpbuf);
	}else{
		$tmpbuf = str_replace ("{TRACKBACKINPUT}", "", $tmpbuf);
		$tmpbuf = str_replace ("{/TRACKBACKINPUT}", "", $tmpbuf);
		$tmpbuf = preg_replace ("/\{TRACKBACKNOINPUT\}[\w\W]+?\{\/TRACKBACKNOINPUT\}/", "", $tmpbuf);
	}
	return $tmpbuf;

}


/* ----- criptによるキー生成 ----- */
function blogn_crypt_key($ip_addr) {
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


/* ----- RSS生成 ----- */
function blogn_rss_view($user){
	$filelist = blogn_mod_db_log_load_for_viewer($user, 0, BLOGN_LOG_VIEW_COUNT);
	if ($filelist[0]) {
		$rssdata =  '<?xml version="1.0" encoding="UTF-8" ?>
		<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
		         xmlns="http://purl.org/rss/1.0/"
		         xmlns:dc="http://purl.org/dc/elements/1.1/"
		         xmlns:content="http://purl.org/rss/1.0/modules/content/"
		         xmlns:cc="http://web.resource.org/cc/" xml:lang="ja">
		<channel rdf:about="'.BLOGN_HOMELINK.'index.php?mode=rss">
		<title>'.htmlspecialchars(BLOGN_SITENAME).'</title>
		<link>'.BLOGN_HOMELINK.'</link>
		<description>'.htmlspecialchars(BLOGN_SITEDESC).'</description>
		<dc:language>ja</dc:language>
		<items>
		<rdf:Seq>';
		while (list($key, $val) = each($filelist[1])) {
			$rssdata .= '
		<rdf:li rdf:resource="'.BLOGN_HOMELINK.'index.php?e='.$val["id"].'" />';
		}
		$rssdata .= '
		</rdf:Seq>
		</items>
		</channel>
		';
		$category1 = blogn_mod_db_category1_load();
		$category2 = blogn_mod_db_category2_load();

		reset($filelist[1]);
		while (list($key, $val) = each($filelist[1])) {
			$rss_tzd = date("O", mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4)));
			$rss_tzd = substr($rss_tzd,0,3).":".substr($rss_tzd,3,2);
			$date = date("Y-m-d", mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4)))."T".date("H:i:s", mktime(substr($val["date"],8,2),substr($val["date"],10,2),substr($val["date"],12,2),substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4))).$rss_tzd;
			$d_mes = preg_replace_callback("/(<img src=\")(http?s)*([\.+\/]*)([^<>]+[[:alnum:]\/\"^ .-]+\")(>)/i", blogn_change_url, $val["mes"]);
			$d_mes = preg_replace_callback("/(<a href=\")(http?s|ftp)*([\.+\/]*)([^<>]+[[:alnum:]\/\"^ .-]+\")(>)/i", blogn_change_url, $d_mes);

			$desc = blogn_mbtrim($d_mes,500);
			$desc = blogn_CleanHtml($desc);
			$desc = htmlspecialchars($desc);
			$d_title = $val["title"];
			$d_title = blogn_mbtrim($d_title,100);
			$d_title = blogn_CleanHtml($d_title);
			$d_title = htmlspecialchars($d_title);
			$profile = blogn_mod_db_user_profile_load($val["user_id"]);
			list($c1, $c2) = explode("|", $val["category"]);
			$c_name = $category1[1][$c1]["name"];
			if ($c2) $c_name .= "::".$category2[1][$c2]["name"];
			$rssdata .= '
		<item rdf:about="'.BLOGN_HOMELINK.'index.php?e='.$val["id"].'"><link>'.BLOGN_HOMELINK.'index.php?e='.$val["id"].'</link><title>'.$d_title.'</title><description>'.$desc.'</description><content:encoded><![CDATA['.$d_mes.']]></content:encoded><dc:subject>'.$c_name.'</dc:subject><dc:date>'.$date.'</dc:date><dc:creator>'.$profile["name"].'</dc:creator><dc:publisher>BlognPlus</dc:publisher><dc:rights>'.$profile["name"].'</dc:rights></item>';
		}
		$rssdata .= '</rdf:RDF>';
		$rssdata = ereg_replace("<br>", "<br />", $rssdata);
		print $rssdata;
	}
}


/* 相対パス → 絶対パス変換 */
function blogn_change_url($matches) {
	if ($matches[1] == "<a href=\"") {
		$endtag = ">";
	}else{
		$endtag = " />";
	}
	if ($matches[2]) {
		return $matches[1].$matches[2].$matches[4].$endtag;
	}else{
		return $matches[1].BLOGN_HOMELINK.$matches[4].$endtag;
	}
}


/* ----- RDF生成 ----- */
function blogn_rdf_make($about, $identifier, $title, $description, $subject, $creator, $date, $trackback){
	$title = htmlspecialchars($title);
	$title = blogn_mbtrim($title, 100);
	$description = str_replace("&lt;", "<", $description);
	$description = str_replace("&gt;", ">", $description);
	$description = str_replace("&quot;", "\"", $description);		//  ”にもどす
	$description = preg_replace_callback("/(<img src=\")([http?s]*)([\.+\/]*)([^<>]+[[:alnum:]\/\"^ .-]+\")(>)/i", blogn_change_url, $description);
	$description = preg_replace_callback("/(<a href=\")([http?s|ftp]*)([\.+\/]*)([^<>]+[[:alnum:]\/\"^ .-]+\")(>)/i", blogn_change_url, $description);

	$description = blogn_CleanHtml($description);
	$description = htmlspecialchars($description);
	$description = blogn_mbtrim($description, 500);
	$rdffile = '<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
<rdf:Description
   rdf:about="'.$about.'"
   dc:identifier="'.$identifier.'"
   dc:title="'.$title.'"
   dc:description="'.$description.'"
   dc:subject="'.$subject.'"
   dc:creator="'.$creator.'"
   dc:date="'.$date.'"
   trackback:ping="'.$trackback.'" />
</rdf:RDF>
-->';
	return $rdffile;
}


//-------------------------------------------------------------------- 携帯閲覧時の処理
function blogn_im_callback($matches) {
	if ($fsize = @round(filesize($matches[1]) / 1024, 1)) {
		if ($fsize < 0.4) {
			$result = '<img src="'.$matches[1].'">';
		}else{
			$result = '<a href="'.BLOGN_HOMELINK.'im.php?'.htmlentities(urlencode($matches[1])).'">[PIC]</a>';
		}
	}else{
		$result = '<img src="'.$matches[1].'">';
	}
	return $result;
}

//-------------------------------------------------------------------- セパレーター
function blogn_word_sepa($key1, $key2, $val) {
	list($newval[0],$buf) = explode($key1, $val, 2);
	list($newval[1], $newval[2]) = explode($key2, $buf, 2);
	return $newval;
}

//-------------------------------------------------------------------- 外部PHPスクリプト処理
function blogn_get_skin_php ($skin) {
	$target = 0;
	$i = 0;
	$tmpbuf = $skin;
	while ($target == 0) {
		if (eregi("\{INCLUDE\}", $tmpbuf) && eregi("\{\/INCLUDE\}", $tmpbuf)) {
			list($buf[$i], $p_buf[$i], $tmpbuf) = blogn_word_sepa("{INCLUDE}", "{/INCLUDE}", $tmpbuf);
			$i++;
		}else{
			$buf[$i] = $tmpbuf;
			break;
		}
	}
	if (BLOGN_MOBILE_KEY == 0) {
		if (BLOGN_CHARSET == 0) {
			header("Content-Type: text/html; charset=Shift_JIS"); 
		}elseif (BLOGN_CHARSET == 1) {
			header("Content-Type: text/html; charset=EUC-JP"); 
		}elseif (BLOGN_CHARSET == 2) {
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


//-------------------------------------------------------------------- スキン選択処理
function blogn_skin_selector($mode, $entry_id, $date, $user_id, $category, $ua) {
	if ($ua[0] == 'ASTEL' || $ua[0] == 'UP.Browser' || preg_match("/^KDDI/","$ua[0]") || $ua[0] == 'PDXGW' || $ua[0] == 'DoCoMo' || $ua[0] == 'J-PHONE' || $ua[0] == 'Vodafone' ||  preg_match("/^MOT/", "$ua[0]") || preg_match("/DDIPOCKET/","$ua[1]") || $ua[0] == 'L-mode') {
		define("BLOGN_MOBILE_KEY", 1);
		if ($ua[0] == 'J-PHONE') {
			define("BLOGN_PNG_KEY", 1);
		}else{
			define("BLOGN_PNG_KEY", 0);
		}
		// スキンファイル読み込み
		$skin = file(BLOGN_SKINDIR."iskin.html");
		$skin = implode("",$skin);
		$skin = blogn_mbConv($skin, 0, 4);
	}else{
		define(BLOGN_MOBILE_KEY, 0);
		// スキンファイル読み込み
		$skinview = blogn_mod_db_viewskin_load();
		$skinlist = blogn_mod_db_skin_load();

		if (!$skinview[0] || !$skinlist[0]) {
			// 未設定時はエラー
			return false;
		}
		if ($skinview[1][0]["view_type"] == 0) {
			// ノーマル表示
			$skin = file(BLOGN_SKINDIR.$skinlist[1][$skinview[1][0]["skin_id"]]["html_name"]);
			$skin = implode("",$skin);
			$skin = blogn_mbConv($skin,0,4);
			$skin = preg_replace ("/\{CSSNAME\}/", BLOGN_SKINDIR.$skinlist[1][$skinview[1][0]["skin_id"]]["css_name"], $skin);

		}elseif ($skinview[1][0]["view_type"] == 1) {
			// ランダム表示
			list($mses, $sec) = split(" ", microtime());
			mt_srand($sec*100000);

			$m = count($skinview[1]) - 1;
			$r = mt_rand(0,$m);

			$skin = file(BLOGN_SKINDIR.$skinlist[1][$skinview[1][$r]["skin_id"]]["html_name"]);
			$skin = implode("",$skin);
			$skin = blogn_mbConv($skin,0,4);
			$skin = preg_replace ("/\{CSSNAME\}/", BLOGN_SKINDIR.$skinlist[1][$skinview[1][$r]["skin_id"]]["css_name"], $skin);

		}elseif ($skinview[1][0]["view_type"] == 2) {
			// ジャンル別表示
			$findflg = false;
			switch ($mode) {
				case "":				//初期表示画面
					@reset($skinview[1]);
					while(list($key, $val) = each($skinview[1])) {
						if ($val["category_id"] == "1" && $val["section_id"] == "1") {
							$findflg = $key;
							break;
						}
					}
					break;
				case "s":				//サーチ画面
					@reset($skinview[1]);
					while(list($key, $val) = each($skinview[1])) {
						if ($val["category_id"] == "1" && $val["section_id"] == "2") {
							$findflg = $key;
							break;
						}
					}
					break;
				case "m":				//月別画面
				case "d":				//日別画面（月指定スキンを使用）
					$mkey = (int)substr($date,4,2);
					@reset($skinview[1]);
					while(list($key, $val) = each($skinview[1])) {
						if ($val["category_id"] == "2" && $val["section_id"] == $mkey) {
							$findflg = $key;
							break;
						}
					}
					break;
				case "p":				//プロフィール画面（ユーザー別）
				case "u":				//ユーザー別画面
					@reset($skinview[1]);
					while(list($key, $val) = each($skinview[1])) {
						if ($val["category_id"] == "3" && $val["section_id"] == $userid) {
							$findflg = $key;
							break;
						}
					}
					break;
				case "e":				//指定記事画面（記事カテゴリーに依存）
					$logdata = blogn_mod_db_log_load_for_editor($entry_id);
					$category = $logdata[1]["category"];
				case "c":				//カテゴリ別画面
					@reset($skinview[1]);
					while(list($key, $val) = each($skinview[1])) {
						if ($val["category_id"] == "4" && $val["section_id"] == $category) {
							$findflg = $key;
							break;
						}
					}
					break;
			}
			if ($findflg) {
				$skin = file(BLOGN_SKINDIR.$skinlist[1][$skinview[1][$findflg]["skin_id"]]["html_name"]);
				$skin = implode("",$skin);
				$skin = blogn_mbConv($skin,0,4);
				$skin = preg_replace ("/\{CSSNAME\}/", BLOGN_SKINDIR.$skinlist[1][$skinview[1][$findflg]["skin_id"]]["css_name"], $skin);
			}else{
				$skin = file(BLOGN_SKINDIR.$skinlist[1][$skinview[1][0]["skin_id"]]["html_name"]);
				$skin = implode("",$skin);
				$skin = blogn_mbConv($skin,0,4);
				$skin = preg_replace ("/\{CSSNAME\}/", BLOGN_SKINDIR.$skinlist[1][$skinview[1][0]["skin_id"]]["css_name"], $skin);
			}



		}
	}
	return $skin;
}


/* ----- 携帯投稿 ----- */
function blogn_mobile_blog_new($m_users) {
	while (list($key, $val) = each($m_users)) {
		// $key : $user_id
		$receive_addr = $val["receive_mail_address"];
		$pop = $val["receive_mail_pop3"];
		$apop = $val["receive_mail_apop"];
		$loginid = $val["receive_mail_user_id"];
		$loginpass = $val["receive_mail_user_pw"];
		$send_addr = $val["send_mail_address"];
		$mobile_category = $val["mobile_category"];
		$mobile_cok = $val["mobile_comment_ok"];
		$mobile_tok = $val["mobile_trackback_ok"];

		if (strlen($receive_addr) && strlen($send_addr) && strlen($pop) && strlen($loginid) && strlen($loginpass) && strlen($apop)) {
			if ($fp = @fsockopen ($pop, 110, $errno, $errstr, 30)) {
				$buf = fgets($fp, 512);
				if(substr($buf, 0, 3) == '+OK') {
					if($apop == 1) {
						$arraybuf = explode(" ", trim($buf));
						$md5pass = md5($arraybuf[count($arraybuf) - 1].$loginpass);
						$buf = blogn_send_cmd($fp, "APOP $loginid $md5pass");
					} else {
						$buf = blogn_send_cmd($fp, "USER $loginid");
						$buf = blogn_send_cmd($fp, "PASS $loginpass");
					}
					$buf = blogn_send_cmd($fp, "STAT");		// +OK $num $size
					sscanf($buf, '+OK %d %d', $num, $size);
					$cnt = 0;
					if ($num == "0") {
						$buf = blogn_send_cmd($fp, "QUIT");
						fclose($fp);
					}else{
						//メールデータ取得
						$tmpdata = array();
						for($i = 1 ; $i <= $num ; $i++ ) {
							$line = blogn_send_cmd($fp, "RETR $i");
							while (!preg_match("/^\.\r\n/",$line)) {
								$line = fgets($fp, 512);
								$tmpdata[$i].= $line;
							}

							list($head, $body) = split("\r\n\r\n", $tmpdata[$i], 2);
							$body = preg_replace("/\r\n[\t ]+/", " ", $body);

							// 送信者アドレスの抽出
							if (eregi("Return-Path:[ \t]*([^\r\n]+)", $head, $freg)) {
								$from = $freg[1];
							} elseif (eregi("Reply-To:[ \t]*([^\r\n]+)", $head, $freg)) {
								$from = $freg[1];
							} elseif (eregi("From:[ \t]*([^\r\n]+)", $head, $freg)) {
								$from = $freg[1];
							}
							// 送信者アドレスが登録アドレスの場合
							if (($match = strpos($from, $send_addr)) !== false) {
								$cnt++;
								$receive_data[$cnt] = $tmpdata[$i];
								$buf = blogn_send_cmd($fp, "DELE $i");
							}
						}
						$buf = blogn_send_cmd($fp, "QUIT"); 
						fclose($fp);
					}
					// 取得データ処理
					for ($i = 1 ; $i <= $cnt ; $i++ ) {
						$subject = $from = $text = $atta = $part = $attach = "";

						list($head, $body) = split("\r\n\r\n", $receive_data[$i], 2);
						$body = preg_replace("/\r\n[\t ]+/", " ", $body);

						// 日付の取得
						eregi("Date:[ \t]*([^\r\n]+)", $head, $tmp_date);
						// タイムゾーンの取得と加減算
						if (!$tmp_date[1] || ($timestamp = strtotime($tmp_date[1])) === -1) { 
							$now = gmdate("YmdHis",time() + BLOGN_TIMEZONE); 
						}else{ 
							$now = gmdate("YmdHis",$timestamp + BLOGN_TIMEZONE); 
						}
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
							$subject = htmlspecialchars(blogn_mbConv($subject,0,4));
						}
						if (eregi("\nContent-type:.*multipart/",$head)) {
							eregi('boundary="([^"]+)"', $head, $boureg);
							$body = str_replace($boureg[1], urlencode($boureg[1]), $body);
							$part = split("\r\n--".urlencode($boureg[1])."-?-?",$body);
							if (eregi('boundary="([^"]+)"', $body, $boureg2)) {
								$body = str_replace($boureg2[1], urlencode($boureg2[1]), $body);
								$body = preg_replace("/\r\n--".urlencode($boureg[1])."-?-?\r\n/","",$body);
								$part = split("\r\n--".urlencode($boureg2[1])."-?-?",$body);
							}
						} else {
							$part[0] = $receive_data[$i];
						}
						foreach ($part as $pic_cnt => $multi) { 
							list($m_head, $m_body) = split("\r\n\r\n", $multi, 2);
							$m_body = preg_replace("/\r\n[\t ]+/", " ", $m_body);
							$m_body = preg_replace("/\r\n\.\r\n$/", "", $m_body);

							if (!eregi("Content-type: *([^;\n]+)", $m_head, $type)) continue;
							list($main, $sub) = explode("/", $type[1]);
							// 本文をデコード
							if (strtolower($main) == "text") {
								if (eregi("Content-Transfer-Encoding:.*base64", $m_head)) $m_body = base64_decode($m_body);
								if (eregi("Content-Transfer-Encoding:.*quoted-printable", $m_head)) $m_body = quoted_printable_decode($m_body);
								$text = blogn_mbConv($m_body,0,4);
								if ($sub == "html") $text = strip_tags($text);
								// 電話番号削除
								$text = preg_replace("/([[:digit:]]{11})|([[:digit:]\-]{13})/", "", $text);
								// 下線削除
								$text = preg_replace("/[_]{25,}/", "", $text);
								// mac削除
								$text = preg_replace("/Content-type: multipart\/appledouble;[[:space:]]boundary=(.*)/i","",$text);

								$text = str_replace("\r\n", "\r",$text);
								$text = str_replace("\r", "\n",$text);
								$text = preg_replace("/\n{2,}/", "\n\n", $text);
								$text = str_replace("\n", "<br>", $text);
							}
							// 添付データがある場合
							if (eregi("name=\"?([^\"\n]+)\"?",$m_head, $filereg)) {
								$filename = preg_replace("/[\t\r\n]/", "", $filereg[1]);
								$finfo = pathinfo($filename);
								$piccnt++;
								$upfile_name = "m".$key.$i.$pic_cnt.$now.".".strtolower($finfo["extension"]);
							}
							$subtype = "gif|jpe?g|png";
							if (eregi("Content-Transfer-Encoding:.*base64", $m_head) && eregi($subtype, $finfo["extension"])) {
								$upfile = base64_decode($m_body);
								$oldmask = umask();
								umask(000);
									$fp = fopen(BLOGN_FILEDIR.$upfile_name, "w");
									fputs($fp, $upfile);
									fclose($fp);
								umask($oldmask);
								blogn_mod_db_file_add($key, $upfile_name, $filename);
							}
						}
						if ($upfile_name != "") {
							$dest = BLOGN_FILEDIR.$upfile_name;
							$size = @getimagesize($dest);
							if ($size[0] > BLOGN_MAXWIDTH || $size[1] > BLOGN_MAXHEIGHT) {
								$ratio1 = BLOGN_MAXWIDTH / $size[0];
								$ratio2 = BLOGN_MAXHEIGHT / $size[1];
								if ($ratio1 < $ratio2) {
									$ratio = $ratio1;
								}else{
									$ratio = $ratio2;
								}
								$width = round($size[0] * $ratio);
								$height = round($size[1] * $ratio);
								$text = '<a href="'.BLOGN_FILEDIR.$upfile_name.'" target="_blank"><img src="'.BLOGN_FILEDIR.$upfile_name.'" width="'.$width.'" height="'.$height.'"></a><br>'.$text;
							}else{
								$text = '<img src="'.BLOGN_FILEDIR.$upfile_name.'" '.$size[3].'><br>'.$text;
							}
						}
						// 携帯用独自タグ処理
						if (eregi("\{cmtok\}",$text)) {
							$text = eregi_replace("\{cmtok\}", "", $text);
							$mobile_cok = true;
						}
						if (eregi("\{cmtng\}",$text)) {
							$text = eregi_replace("\{cmtng\}", "", $text);
							$mobile_cok = false;
						}
						if (eregi("\{trkok\}",$text)) {
							$text = eregi_replace("\{trkok\}", "", $text);
							$mobile_tok = true;
						}
						if (eregi("\{trkng\}",$text)) {
							$text = eregi_replace("\{trkng\}", "", $text);
							$mobile_tok = false;
						}
						if (eregi("\{secret\}",$text)) {
							$text = eregi_replace("\{secret\}", "", $text);
							$mobile_secret = true;
						}else{
							$mobile_secret = false;
						}
						if (preg_match("/\{category([\w\W]+?)\}/i", $text, $cat)) {
							list($key_cat1, $key_cat2) = explode("-", trim($cat[1]));
							$text = preg_replace("/\{category([\w\W]+?)\}/i", "", $text);
							$category1 = blogn_mod_db_category1_load();
							$category2 = blogn_mod_db_category2_load();
							if (@$category1[1][$key_cat1]["name"] && @$category2[1][$key_cat2]["name"]) {
								$mobile_category = $key_cat1."|".$key_cat2;
							}elseif (@$category1[1][$key_cat1]["name"]) {
								$mobile_category = $key_cat1."|";
							}
						}
						if (preg_match("/\{reserve([\w\W]+?)\}/i", $text, $res)) {
							list($key_res_date, $key_res_time) = explode(" ", trim($res[1]));
							list($key_res_year, $key_res_month, $key_res_day) = explode("/", $key_res_date);
							$text = preg_replace("/\{reserve([\w\W]+?)\}/i", "", $text);
							if (checkdate($key_res_month, $key_res_day, $key_res_year)) {
								if (trim($key_res_time) != "") {
									list($key_res_hour, $key_res_minutes, $key_res_second) = explode(":", trim($key_res_time));
									$key_res = $key_res_year.$key_res_month.$key_res_day.$key_res_hour.$key_res_minutes.$key_res_second;
								}else{
									$key_res = $key_res_year.$key_res_month.$key_res_day."000000";
								}
								if ($key_res > $now) {
									$mobile_reserve = true;
								}else{
									$mobile_reserve = false;
								}
								$now = $key_res;
							}else{
								// 日付が判別できない場合、非公開にする
								$mobile_reserve = false;
								$mobile_secret = true;
							}
						}
						if (eregi("\{more\}",$text)) {
							list($text, $more) = explode("{more}", $text);
						}else{
							$more = "";
						}
						blogn_mod_db_log_add($key, $now, $mobile_reserve, $mobile_secret, $mobile_cok, $mobile_tok, $mobile_category, $subject, $text, $more, 1);
					}
				}
			}
		}
	}
}

/* ----- コマンド送信 ----- */
function blogn_send_cmd($fp, $cmd) {
	fputs($fp, $cmd."\r\n");
	$buf = fgets($fp, 512);
	if(substr($buf, 0, 3) == '+OK') {
		return $buf;
	}
	return false;
}


?>
