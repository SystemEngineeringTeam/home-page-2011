<?php
//-------------------------------------------------------------------------
// Weblog PHP script BlognPlus
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// common.php
//
// LAST UPDATE 2007/01/19
//
// ・バージョン表記を更新
// ・エラー表示処理を追加
//
//-------------------------------------------------------------------------

// Blogn Version
//
define("BLOGN_VERSION","version 2.4.1");


/* 環境設定 */
ini_set("mbstring.encoding_translation", "Off"); 
ini_set("mbstring.http_input", "pass");
ini_set("mbstring.http_output", "pass");

/* 内部文字エンコーディングをUTF-8に設定 */
mb_internal_encoding( "UTF-8" );


//--------------------------------------------------------------------
// OS判別
//
$blogn_php_ini_path = ini_get('include_path');
if (ereg("[\\]+", $blogn_php_ini_path)) {
	define("BLOGN_OS_TYPE", 0);	// windows
}else{
	define("BLOGN_OS_TYPE", 1);	// linux
}

//--------------------------------------------------------------------
// データベース（ファイル）タイプ確認＆読み込み
//
switch (BLOGN_DB_TYPE) {
	case "text":
		include("database/db_text.php");
		break;
	case "mysql":
		include("database/db_mysql.php");
		break;
	case "postgresql";
		include("database/db_postgresql.php");
		break;
	default:
		$errdata = file("./template/info.html");
		$errdata = implode("",$errdata);

		if (BLOGN_CHARSET == 0) {
			$charset = "Shift_JIS";
		}elseif (BLOGN_CHARSET == 1) {
			$charset = "EUC-JP";
		}elseif (BLOGN_CHARSET == 2) {
			$charset = "UTF-8";
		}

		if ($_SERVER["PHP_SELF"]) {
			$php_self = $_SERVER["PHP_SELF"];
		}else{
			$php_self = $_SERVER["SCRIPT_NAME"];
		}
		$blogn_urilen = strlen($php_self);
		$blogn_reqfile = substr(strrchr($php_self, "/"),1);
		$blogn_reqfilelen = strlen($blogn_reqfile);
		$blogn_reqdir = substr($php_self, 0, $blogn_urilen - $blogn_reqfilelen) ;
		$link_url = "http://".$_SERVER["HTTP_HOST"].$blogn_reqdir; //current URL
		$link_url = $link_url."install.php";
		$info = "インストール作業が終了していないか、<br>conf.phpファイルが破損しています。<br><br>もしインストール作業がまだの場合は<br>以下のURLからインストール作業を行ってください。<br><a href='{$link_url}'>{$link_url}</a>";
		$errdata = str_replace("{META_LINK}", "", $errdata);
		$errdata = str_replace("{CHARSET}", $charset, $errdata);
		$errdata = str_replace("{INFO}", $info, $errdata);

		echo $errdata;
		exit;
}

//--------------------------------------------------------------------
// サイト設定
//
$blogn_init = blogn_mod_db_init_load();

/* site name */
define('BLOGN_SITENAME', $blogn_init["sitename"]);
/* site description */
define('BLOGN_SITEDESC', $blogn_init["sitedesc"]);
/* timezone */
define('BLOGN_TIMEZONE', $blogn_init["timezone"] * 3600);
/* charset */
define('BLOGN_CHARSET', $blogn_init["charset"]);
/* max filesize */
define('BLOGN_MAX_FILESIZE', $blogn_init["max_filesize"]);
/* permit file type */
define('BLOGN_PERMIT_FILE_TYPE', $blogn_init["permit_file_type"]);
/* maxinum picture size */
define('BLOGN_MAXWIDTH', $blogn_init["max_view_width"]);
define('BLOGN_MAXHEIGHT', $blogn_init["max_view_height"]);
/* permit html tag */
define('BLOGN_PERMIT_HTML_TAG', $blogn_init["permit_html_tag"]);
/* comment max size */
define('BLOGN_COMMENT_SIZE', $blogn_init["comment_size"]);
/* trackback slash type */
define('BLOGN_TRACKBACK_SLASH_TYPE', $blogn_init["trackback_slash_type"]);
/* log view count */
define('BLOGN_LOG_VIEW_COUNT', $blogn_init["log_view_count"]);
/* mobile count */
define('BLOGN_MOBILE_VIEW_COUNT', $blogn_init["mobile_view_count"]);
/* new entories count */
define('BLOGN_NEW_ENTRY_VIEW_COUNT', $blogn_init["new_entry_view_count"]);
/* archives count */
define('BLOGN_ARCHIVE_VIEW_COUNT', $blogn_init["archive_view_count"]);
/* recent comments count */
define('BLOGN_COMMENT_VIEW_COUNT', $blogn_init["comment_view_count"]);
/* recent trackback count */
define('BLOGN_TRACKBACK_VIEW_COUNT', $blogn_init["trackback_view_count"]);
/* comments list on/offt */
define('BLOGN_COMMENT_LIST_TOPVIEW_ON', $blogn_init["comment_list_topview_on"]);
/* trackback list on/off */
define('BLOGN_TRACKBACK_LIST_TOPVIEW_ON', $blogn_init["trackback_list_topview_on"]);
/* session time */
ini_set("session.gc_maxlifetime", $blogn_init["session_time"]);
define('BLOGN_SESSION_TIME', $blogn_init["session_time"]);
/* cookie time */
define('BLOGN_COOKIE_TIME', $blogn_init["cookie_time"]);
/* comment limit time */
define('BLOGN_LIMIT_COMMENT', $blogn_init["limit_comment"] * 86400);
/* trackback limit time */
define('BLOGN_LIMIT_TRACKBACK', $blogn_init["limit_trackback"] * 86400);
/* monthly view mode */
define('BLOGN_MONTHLY_VIEW_MODE', (int)$blogn_init["monthly_view_mode"]);
/* category view mode */
define('BLOGN_CATEGORY_VIEW_MODE', (int)$blogn_init["category_view_mode"]);

/* ----- モジュール一覧読み込み ----- */
function blogn_module_load() {
	// モジュールディレクトリの有無チェック
	if (!$moduledir = @dir(BLOGN_MODDIR)) {
		$error[0] = false;
		return $error;
	}
	// モジュール一覧取得
	$modules[0] = false;
	while (($modulelist = $moduledir -> read()) !== false) {
		if ($modulelist != "." && $modulelist != ".." && $modulelist != "index.html") {
			include_once(BLOGN_MODDIR.$modulelist."/info.php");
			$modules[1][$modulelist]["name"] = $blogn_mod_name;
			$modules[1][$modulelist]["desc"] = $blogn_mod_desc;
			$modules[1][$modulelist]["update"] = $blogn_mod_update;
			$modules[1][$modulelist]["control"] = $blogn_mod_control;
			$modules[1][$modulelist]["function"] = $blogn_mod_function;
			$modules[1][$modulelist]["viewer"] = $blogn_mod_viewer;
			$modules[0] = true;
		}
	}
	return $modules;
}


/* ----- アイコン整形 ----- */
function blogn_IconStr($str){
	$icon = file(BLOGN_INIDIR."icon.cgi");
	for ( $i = 0; $i < count( $icon ); $i++ ) {
		$icon[$i] = ereg_replace( "\n$", "", $icon[$i] );
		$icon[$i] = ereg_replace( "\r$", "", $icon[$i] );
		list($filename, $icon_key) = explode("<>", $icon[$i]);
		if (strstr($str, $icon_key)){
			if (BLOGN_MOBILE_KEY == 1) {
				if (BLOGN_PNG_KEY == 0) {
					$icon_file = BLOGN_ICONDIR.substr($filename, 0, strlen($filename) - 3)."gif";
				}else{
					$icon_file = BLOGN_ICONDIR.substr($filename, 0, strlen($filename) - 3)."png";
				}
			}else{
				$icon_file = BLOGN_ICONDIR.$filename;
			}
			$size = @getimagesize($icon_file);
			$icon_name = preg_replace("/\[:([\w\W]+?):\]/", "\\1", $icon_key);
			$str = str_replace($icon_key, '<img src="'.$icon_file.'" '.$size[3].' alt="'.$icon_name.'">', $str);
		}
	}
	return $str;
}


/* ----- 実体参照化 ----- */
function blogn_html_tag_convert($str) {
	$str = ereg_replace("&", "&amp;", $str);
	$str = ereg_replace("<", "&lt;", $str);
	$str = ereg_replace(">", "&gt;", $str);
	return $str;
}


/* ----- 実体参照復元 ----- */
function blogn_html_tag_restore($str) {
	$str = ereg_replace("&lt;", "<", $str);
	$str = ereg_replace("&gt;", ">", $str);
	$str = ereg_replace("&amp;", "&", $str);
	return $str;
}


/* ----- 有効HTMLタグ変換 ----- */
function blogn_permit_html_tag_restore($str) {
	$str = ereg_replace("&", "&amp;", $str);
	$str = ereg_replace("<", "&lt;", $str);
	$str = ereg_replace(">", "&gt;", $str);

	$permit_tag = explode(",", BLOGN_PERMIT_HTML_TAG);
	while (list($key, $val) = each($permit_tag)) {
		$str = preg_replace("/&lt;($val)&gt;/i", "<$val>", $str);
		$str = preg_replace("/&lt;\/($val)&gt;/i", "</$val>", $str);
		$str = preg_replace("/&lt;($val)([ ]+[\w\W]+?)&gt;/i", "<$val\\2>", $str);
	}
	$str = preg_replace("/&lt;(br)&gt;/i", "<br>", $str);
	$str = preg_replace("/&lt;(br)([ ]+[\w\W]+?)&gt;/i", "<br\\2>", $str);
	$str = ereg_replace("&amp;", "&", $str);
	return $str;
}


/* HTML→テキスト変換 */
function blogn_CleanHtml($str){
	$search = array ("'&(quot|#34);'i",											// htmlエンティティを置換
                   "'&(amp|#38);'i",
                   "'&(lt|#60);'i",
                   "'&(gt|#62);'i",
                   "'&(nbsp|#160);'i",
                   "'&(iexcl|#161);'i",
                   "'&(cent|#162);'i",
                   "'&(pound|#163);'i",
                   "'&(copy|#169);'i",
                   "'<script[^>]*?>.*?</script>'si",		// javascriptを削除
                   "'<[\/\!]*?[^<>]*?>'si");								// htmlタグを削除

$replace = array ("\"",
                  "&",
                  "<",
                  ">",
                  " ",
                  chr(161),
                  chr(162),
                  chr(163),
                  chr(169),
                  "",
                  "",);
$str = preg_replace ($search, $replace, $str);
return $str;
}


/* ----- 文字コード変換 ----- */
function blogn_mbConv($val,$in,$out) {
	if (BLOGN_MBS) {
		if ($in == 0) {
			$inenc = 'ASCII, JIS, UTF-8, EUC-JP, SJIS';
		}elseif ($in == 1) {
			$inenc = 'EUC-JP';
		}elseif ($in == 2) {
			$inenc = 'SJIS';
		}elseif ($in == 3) { 
			$inenc = 'JIS'; 
		}elseif ($in == 4) {
			$inenc = 'UTF-8';
		}
		if ($out == 1) {
			$outenc = 'EUC-JP';
		}elseif ($out == 2) {
			$outenc = 'SJIS';
		}elseif ($out == 3) { 
			$outenc = 'JIS'; 
		}elseif ($out == 4) {
			$outenc = 'UTF-8';
		}
		$val = mb_convert_encoding($val, $outenc, $inenc);
	}else{
		$val = JcodeConvert($val, $in, $out);
	}
	return $val;
}


/* ----- マルチバイト対応トリミング処理（UTF-8） ----- */
function blogn_mbtrim($val,$nstr) { 
	// $val は、トリミングしたい文字列 
	// $nstr は、トリミングしたい文字数 
	$lenstr = strlen($val); 
	if ($lenstr <= $nstr) { 
		return $val; 
//	} else if (BLOGN_MBS) { 
//		$val = mb_substr($val, 0, $nstr);
//		return $val;
	}else{ 
		$val = substr($val,0,$nstr); 
		$val = preg_replace("/[\xC0-\xFD]$/","",$val); 
		$val = preg_replace("/[\xE0-\xFD][\x80-\xBF]$/","",$val); 
		$val = preg_replace("/[\xE0-\xFD][\x80-\xBF]{2}$/","",$val); 
		return $val; 
	} 
}


/* ----- マジッククォート ----- */
function blogn_magic_quotes($val) { 
	$val = get_magic_quotes_gpc() ? stripslashes($val) : $val;
	return $val;
}


/* -----  日付比較（現在と指定日の差） ----- */
function blogn_date_diff($date1) { 
	// $date1 is subtracted from $date2. 
	// if $date2 is not specified, then current date is assumed.

	//Splits date apart
	$date1_year = substr($date1, 0, 4);
	$date1_month = substr($date1, 4, 2);
	$date1_day = substr($date1, 6, 2);
	$date1_hour = substr($date1, 8, 2);
	$date1_minutes = substr($date1, 10, 2);
	$date1_seconds = substr($date1, 12, 2);

	$date2_year = gmdate("Y", time() + BLOGN_TIMEZONE); //Gets Current Year
	$date2_month = date("m",time() + BLOGN_TIMEZONE); //Gets Current Month
	$date2_day = date("d",time() + BLOGN_TIMEZONE); //Gets Current Day
	$date2_hour = date("H",time() + BLOGN_TIMEZONE);
	$date2_minutes = date("i",time() + BLOGN_TIMEZONE);
	$date2_seconds = date("s",time() + BLOGN_TIMEZONE);


	$date1 = mktime($date1_hour, $date1_minutes, $date1_seconds, $date1_month, $date1_day, $date1_year); //Gets Unix timestamp for $date1
	$date2 = mktime($date2_hour, $date2_minutes, $date2_seconds, $date2_month, $date2_day, $date2_year); //Gets Unix timestamp for $date2

	$difference = $date2 - $date1; //Calcuates Difference
	return $difference; //Calculates Days Old
}


// \r\n -> {:rn:}
function blogn_rn2rntag($str) {
	$str = str_replace( "\r\n",  "\n", $str);		// 改行を統一する
	$str = str_replace( "\r",  "\n", $str);
	$str = str_replace("\n",  "{:rn:}", $str);				// \n -> {:rn:} に変換
	return $str;
}

// {:rn:} -> \r\n
function blogn_rntag2rn($str) {
	$str = str_replace("{:rn:}", "\r\n", $str);				// {:rn:} -> \n に変換
	return $str;
}
?>
