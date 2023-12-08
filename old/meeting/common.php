<?php
//-------------------------------------------------------------------------
// Weblog PHP script Blogn（ぶろぐん）
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// 共通ルーチン
//
// LAST UPDATE 2007/04/09
//
// ・表記バージョンの書き換え
// ・環境設定の修正
// ・その他細かい点の修正
//
//-------------------------------------------------------------------------
/* ===== Blogn（ぶろぐん）バージョン === */
define(BLOGN_VERSION,"Version 1.9.7");

/* 環境設定 */

ini_set("mbstring.encoding_translation", "Off"); 
ini_set("mbstring.http_input", "pass");
if (!ini_set("mbstring.http_output", "pass")) mb_http_output('pass');
if (!ini_set("mbstring.internal_encoding", "EUC-JP")) mb_internal_encoding('EUC-JP');

/* ===== phpバージョンチェック ===== */
if(phpversion()>="4.1.0"){
  extract($_GET);
  extract($_POST);
  extract($_COOKIE);
  extract($_SERVER);
}

// PHP_SELFを取得できないサーバー対策
if ($_SERVER["PHP_SELF"]) {
	define("PHP_SELF", $_SERVER["PHP_SELF"]);
}else{
	define("PHP_SELF", $_SERVER["SCRIPT_NAME"]);
}

/* ===== jcodeファイル読み込み ===== */
require_once("./jcode/jcode.php");
require_once("./jcode/code_table.jis2ucs");
require_once("./jcode/code_table.ucs2jis");


/* HOMELINK、TRACKBACKADDRの設定 */
$urilen = strlen(PHP_SELF);
$reqfile = substr(strrchr(PHP_SELF, "/"),1);
$reqfilelen = strlen($reqfile);
$reqdir = substr( PHP_SELF, 0, $urilen - $reqfilelen) ;
$current = "http://".$_SERVER["HTTP_HOST"] . $reqdir; //カレントURL
define("HOMELINK", $current);
define("TRACKBACKADDR", $current."tb.php");


/* ログ保存フォルダ */
define("LOGDIR", './log/');
/* 画像保存フォルダ */
define("PICDIR", './pic/');
/* 絵文字保存フォルダ */
define("ICONDIR", './ico/');
/* スキン保存フォルダ */
define("SKINDIR", './skin/');
/* スキン用画像保存フォルダ */
define("SKINPICDIR", './skin/images/');


if (file_exists(LOGDIR."conf.dat")){
	$conf = file(LOGDIR."conf.dat");
	//$initから改行コード削除
	$conf[0] = ereg_replace( "\n$", "", $conf[0]);
	$conf[0] = ereg_replace( "\r$", "", $conf[0]);
	list($c_sitename,$c_sitedesc,$c_width,$c_height,$c_logcount,$c_arcount,$c_necount,$c_rccount,$c_rtcount,$c_imcount,$c_tz,$c_charset,$c_address,$c_cok_send,$c_tok_send, $c_maxsize, $c_maxtime, $c_tracktype) = explode("<>", $conf[0]);
}
/* サイトタイトル */
define("SITENAME", $c_sitename);
/* サイト名 */
define("SITEDESC", $c_sitedesc);
/* ログ表示数 */
define("LOGCOUNT", $c_logcount);
/* ARCHIVES一覧数 */
define("ARCOUNT", $c_arcount);
/* NEW ENTORIES一覧数 */
define("NECOUNT", $c_necount);
/* RECENT COMMENTS一覧数 */
define("RCCOUNT", $c_rccount);
/* RECENT TRACKBACK一覧数 */
define("RTCOUNT", $c_rtcount);
/* RECENT TRACKBACK一覧数 */
define("IMCOUNT", $c_imcount);
/* 画像サイズ */
define("MAXWIDTH", $c_width);
define("MAXHEIGHT", $c_height);
/* タイムゾーン設定 */
define("TIMEZONE", $c_tz * 60 * 60);
/* 文字コード設定 */
define("CHARSET", $c_charset);
/* 通知メールアドレス設定 */
define("MADDRESS", $c_address);
/* コメント通知設定 */
define("CINFO", $c_cok_send);
/* トラックバック通知設定 */
define("TINFO", $c_tok_send);
/* コメント投稿最大文字数 */
define("CSIZEMAX", $c_maxsize);
/* コメント連続投稿制限時間 */
define("CTIMEMAX", $c_maxtime);
/* トラックバック設定 */
define("TTYPE", $c_tracktype);

/* マルチバイト関数の有無 */
if (extension_loaded("mbstring")) {
	define("MBS", 1);	// mbstring on
}else{
	define("MBS", 0);	// mbstring off
}
if ($safe = ini_get('safe_mode')) {
	define("SMODE", 1);	// safe mode on
}else{
	define("SMODE", 0);	// safe mode off
}


/* \r\n | \n  → <br /> 変換 */
function rntobr($str) {
	$str = str_replace( "\r\n",  "\n", $str);		// 改行を統一する
	$str = str_replace( "\r",  "\n", $str);
	$str = nl2br($str);													// 改行文字の前に<br>を代入する
	$str = str_replace("\n",  "", $str);				// \nを文字列から消す。
	return $str;
}


/* テキスト整形 */
function CleanStr($str){
  $str = trim($str);//先頭と末尾の空白除去
  if (get_magic_quotes_gpc()) {
    $str = stripslashes($str);				//￥を削除
  }
  $str = htmlspecialchars($str);			//タグ禁止
  $str = ereg_replace("&amp;", "&", $str);	//特殊文字
  return ereg_replace(",", "&#44;", $str);	//カンマを変換
}

/* ----- アイコン整形 ----- */
function IconStr($str){
	$icon = file(LOGDIR."icon.dat");
	for ( $i = 0; $i < count( $icon ); $i++ ) {
		$icon[$i] = ereg_replace( "\n$", "", $icon[$i] );
		$icon[$i] = ereg_replace( "\r$", "", $icon[$i] );
		list($filename, $icon_key) = explode("<>", $icon[$i]);
		if (strstr($str, $icon_key)){
			if (IKEY == 1) {
				if (PNGKEY == 0) {
					$icon_file = ICONDIR.substr($filename, 0, strlen($filename) - 3)."gif";
				}else{
					$icon_file = ICONDIR.substr($filename, 0, strlen($filename) - 3)."png";
				}
			}else{
				$icon_file = ICONDIR.$filename;
			}
			$size = @getimagesize($icon_file);
			$str = str_replace($icon_key,"<img src=\"$icon_file\" ".$size[3].">",$str);
		}
	}
	return $str;
}


/* ----- HTMLタグ整形 ----- */
function tagreplaceStr($str){
	$str = str_replace("{", "&#123;", $str);		//テンプレート処理で誤動作しないように処理
	$str = str_replace("}", "&#125;", $str);		//　　　　　〃
	$str = str_replace("&quot;", "\"", $str);		//  ”にもどす
	$str = preg_replace("/&lt;b&gt;/i", "<b>", $str);		// 太字
	$str = preg_replace("/&lt;\/b&gt;/i", "</b>", $str);
	$str = preg_replace("/&lt;i&gt;/i", "<i>", $str);		// 斜体
	$str = preg_replace("/&lt;\/i&gt;/i", "</i>", $str);
	$str = preg_replace("/&lt;u&gt;/i", "<u>", $str);		// 下線
	$str = preg_replace("/&lt;\/u&gt;/i", "</u>", $str);
	$str = preg_replace("/&lt;s&gt;/i", "<s>", $str);		// 取消線
	$str = preg_replace("/&lt;\/s&gt;/i", "</s>", $str);
	$str = preg_replace("/&lt;p&gt;/i", "<p>", $str);		// 段落
	$str = preg_replace("/&lt;\/p&gt;/i", "</p>", $str);
	$str = preg_replace("/&lt;blockquote&gt;/i", "<blockquote>", $str);		// 引用文
	$str = preg_replace("/&lt;\/blockquote&gt;/i", "</blockquote>", $str);
	$str = preg_replace("/(&lt;a)([\w\W]+?)(&gt;)/i","<a\\2>",$str);		// リンク
	$str = preg_replace("/&lt;\/a&gt;/i", "</a>", $str);
	$str = preg_replace("/(&lt;img)([\w\W]+?)(&gt;)/i","<img\\2>",$str);		// イメージ
	$str = preg_replace("/(&lt;span)([\w\W]+?)(&gt;)/i","<span\\2>",$str);		//
	$str = preg_replace("/&lt;\/span&gt;/i", "</span>", $str);
	$str = preg_replace("/(&lt;div)([\w\W]+?)(&gt;)/i","<div\\2>",$str);		//
	$str = preg_replace("/&lt;\/div&gt;/i", "</div>", $str);
	return $str;
}

/* HTML→テキスト変換 */
function CleanHtml($str){
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
function mbConv($val,$in,$out) {
	if (MBS) {
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


/* ----- マルチバイト対応トリミング処理（EUC） ----- */
function mbtrim($val,$nstr) { 
	// $val は、トリミングしたい文字列 
	// $nstr は、トリミングしたい文字数 
	$lenstr = strlen($val); 
	if ($lenstr <= $nstr) { 
		return $val; 
	}else{ 
		$val = substr($val,0,$nstr); 
		$out = preg_replace("/[\x01-\x7E]+/","",$val); 
		$out = preg_replace("/[\x8F]+/","",$out); 
		$e = strlen($out) % 2; 
		if ($e) { 
			$val = substr($val,0,strlen($val)-1); 
		} 
		return $val; 
	} 
}

/* ----- ディレクトリ・ファイル検索してなければ作成 ----- */
function FileSearch($str) {
	// $str: 検索文字（ファイル名）
	// 例）log200407.dat
	// 戻り値: 0 セーフモード、ディレクトリ作成不可
	//         1 ファイル／ディレクトリ作成
	//        -1 ファイル作成失敗
	$filetype = substr($str,0,3);	// log, cmt, trk の3種類
	$datedir = trim(substr($str,3,4));	// 例）2004 ←年フォルダ用

	//logファイルの検索時は年フォルダの有無をチェック
	if ($filetype == "log" && !FileCheck($datedir, 0, LOGDIR)) {
		if(SMODE) {
			return 0;
		}else{
			if(@mkdir(LOGDIR.$datedir) && @chmod(LOGDIR.$datedir, 0777)) {
				if($fp = @fopen(LOGDIR.$datedir."/".$str, "w+")) {
					fclose($fp);
					@chmod(LOGDIR.$datedir."/".$str, 0666);
					return 1;
				}else{
					return -1;
				}
			}else{
				return -1;
			}
		}
	}elseif (!FileCheck($str, 1, LOGDIR.$datedir)){
		if($fp = @fopen(LOGDIR.$datedir."/".$str, "w+")) {
			fclose($fp);
			@chmod(LOGDIR.$datedir."/".$str, 0666);
			return 1;
		}else{
			return -1;
		}
	}else{
		return 1;
	}
}

/* ----- ディレクトリ・ファイル検索 ----- */
function FileCheck($str, $flg, $dir) {
	// $str: 検索文字（ディレクトリ名/ファイル名）
	// $flg: 0 = ディレクトリ その他 = ファイル名
	// $dir: 検索ディレクトリ（場所）
	if(!$bufdir = @dir($dir)) return false;
	while (($ent = $bufdir->read()) !== false) {
		if ($ent != "." && $ent != "..") {
			if ($flg == 0) {
				if (is_dir($dir."/".$ent) && $ent == $str) return true;
			}else{
				if (is_file($dir."/".$ent) && $ent == $str) return true;
			}
		}
	}
	return false;
}

/* ----- 指定年月日時分秒をファイルのどの位置に挿入すればいいか検索 ----- */
function DateSearch($datetime, $log) {
	// $datetime: 検索記事DateTime
	// $log: ログ（配列）
	// 戻り値: 挿入する行数 $logが0件ならflaseを返す
	if (count($log) == 0) return $key = -1;
	$datetime_array = $log;
	if(array_walk($datetime_array, 'datetime_callback')) {
		while (list($key,$val) = each($datetime_array)){
			if ($datetime >= $val) {
				return $key;
			}
		}
		return count($datetime_array);
	}
}
function datetime_callback(&$value, $key) {
	list(, $valuedate, $valuetime) = explode("<>",$value,4);
	$value = $valuedate.$valuetime;
}


/* ----- 指定EIDがファイルのどの位置にあるか検索 ------ */
function IDSearch($eid, $log) {
	// $eid: 検索記事EID
	// $log: ログ（配列）
	// 戻り値: 見つかった行数
	// 注）EIDが複数ある場合は最初の行を返す
	$id_array = $log;
	if(array_walk($id_array, 'eid_callback')) $result = array_search($eid, $id_array);
	return $result;
}


/* ----- 指定EIDがどのファイルにあるか検索 ----- */
function IDCheck($eid, $flg) {
	// $eid: 検索記事EID
	// $flg: 0=log, 1=cmt, 2=trk
	// 戻り値: ログファイル名（配列）
	// 例）[0]:cmt200406.dat [1]:cmt200407.dat

	// 年フォルダ検索
	$bufdir = dir(LOGDIR);
	while (($ent = $bufdir->read()) !== false) {
		if ($ent != "." && $ent != "..") {
			if (is_dir(LOGDIR.$ent)) $result[] = $ent;
		}
	}
	$matchlist = array();
	for ($i = 0; $i < count($result); $i++) {
		$bufdir = dir(LOGDIR.$result[$i]);
		while (($ent = $bufdir->read()) !== false) {
			if (($flg == 0 && substr($ent,0,3) == "log") || ($flg == 1 && substr($ent,0,3) == "cmt") || ($flg == 2 && substr($ent,0,3) == "trk")) {
				if (is_file(LOGDIR.$result[$i]."/".$ent)) {
					$dat = file(LOGDIR.$result[$i]."/".$ent);
					$id_array = $dat;
					if(array_walk($id_array, 'eid_callback')){
						if (in_array($eid, $id_array)) $matchlist[] = $ent;
					}
				}
			}
		}
	}
	if (count($matchlist) == 0) {
		return false;
	}else{
		return $matchlist;
	}
}
function eid_callback(&$value, $key) {
	list($value) = explode("<>",$value,2);
}


/* ----- 投稿記事数（カテゴリ別） ----- */
function CategoryCount() {
	// 戻り値: 例）[0]10 [1]2 [3]4
	//             [CID]件数

	// 年フォルダ検索
	$result = array();
	$bufdir = dir(LOGDIR);
	while (($ent = $bufdir->read()) !== false) {
		if ($ent != "." && $ent != "..") {
			if (is_dir(LOGDIR.$ent)) $result[] = $ent;
		}
	}
	$id_merge = array();
	for ($i = 0; $i < count($result); $i++) {
		$bufdir = dir(LOGDIR.$result[$i]);
		while (($ent = $bufdir->read()) !== false) {
			if (substr($ent,0,3) == "log") {
				if (is_file(LOGDIR.$result[$i]."/".$ent)) {
					$dat = array();
					$dat = file(LOGDIR.$result[$i]."/".$ent);
					$id_array = $dat;
					if(array_walk($id_array, 'cid_callback')){
						while (list($key, $val) = each ($id_array)) {
							$id_merge[] = $val;
						}
					}
				}
			}
		}
	}
	$matchlist = array_count_values($id_merge);
	ksort($matchlist);
	if (count($matchlist) == 0) {
		return false;
	}else{
		return $matchlist;
	}
}
function cid_callback(&$value, $key) {
	list(,,, $value) = explode("<>",$value,5);
}


/* ----- 投稿記事数（月別） ----- */
function archive_count() {
	// 戻り値: 例）[200405]20 [200406]24 [200407]10 ...
	//             [年月]件数
	// 年フォルダ検索
	$result = array();
	$bufdir = dir(LOGDIR);
	while (($ent = $bufdir->read()) !== false) {
		if ($ent != "." && $ent != "..") {
			if (is_dir(LOGDIR.$ent)) $result[] = $ent;
		}
	}
	$logcount = array();
	for ($i = 0; $i < count($result); $i++) {
		$bufdir = dir(LOGDIR.$result[$i]);
		while (($ent = $bufdir->read()) !== false) {
			if (substr($ent,0,3) == "log") {
				if (is_file(LOGDIR.$result[$i]."/".$ent)) {
					$dat = file(LOGDIR.$result[$i]."/".$ent);
					$logcount[substr($ent,3,6)] = count($dat);
				}
			}
		}
	}
	@krsort($logcount);
	if (count($logcount) == 0) {
		return false;
	}else{
		return $logcount;
	}
}


/* ----- 指定IDのコメント記事数 ----- */
function CommentCount($eid) {
	// $eid: 検索記事EID
	// 戻り値: 記事数

	if ($cmtlist = IDCheck($eid, 1)) {
		$cmtcount = 0;
		for ($i = 0; $i < count($cmtlist); $i++) {
			$cmt = file(LOGDIR.substr($cmtlist[$i],3,4)."/".$cmtlist[$i]);
			for ($j = 0; $j < count($cmt); $j++) {
				list($c_eid) = explode("<>", $cmt[$j], 2);
				if ($eid == $c_eid) $cmtcount++;
			}
		}
		return $cmtcount;
	}else{
		return 0;
	}
}


/* ----- 指定IDのトラックバック記事数 ----- */
function TrackbackCount($eid) {
	// $eid: 検索記事EID
	// 戻り値: 記事数

	if ($trklist = IDCheck($eid, 2)) {
		$trkcount = 0;
		for ($i = 0; $i < count($trklist); $i++) {
			$trk = file(LOGDIR.substr($trklist[$i],3,4)."/".$trklist[$i]);
			for ($j = 0; $j < count($trk); $j++) {
				list($t_eid) = explode("<>", $trk[$j], 2);
				if ($eid == $t_eid) $trkcount++;
			}
		}
		return $trkcount;
	}else{
		return 0;
	}
}


/* ----- 全ログファイル一覧 ----- */
function LogFileList($flg) {
	// $flg:  0=log, 1=mor, 2=cmt, 3=trk
	// 戻り値: 例) [0] log200306.dat [1] log200305.dat ...
	$dirname = array();
	$bufdir = dir(LOGDIR);
	while (($ent = $bufdir->read()) !== false) {
		if ($ent != "." && $ent != "..") {
			if (is_dir(LOGDIR.$ent)) $dirname[] = $ent;
		}
	}
	$filename = array();
	for ($i = 0; $i < count($dirname); $i++) {
		$bufdir = dir(LOGDIR.$dirname[$i]);
		while (($ent = $bufdir->read()) !== false) {
			if (($flg == 0 && substr($ent,0,3) == "log") || ($flg == 1 && substr($ent,0,3) == "mor") || ($flg == 2 && substr($ent,0,3) == "cmt") || ($flg == 3 && substr($ent,0,3) == "trk")) {
				if (is_file(LOGDIR.$dirname[$i]."/".$ent)) {
					if (filesize(LOGDIR.$dirname[$i]."/".$ent) != 0) $filename[] = $ent;
				}
			}
		}
	}
	@rsort($filename);
	if (count($filename) == 0) {
		return false;
	}else{
		return $filename;
	}
}

/* ----- アクセス制限のあるIPを遮断 ----- */
function ip_check_deny($ip) {
	$deny_ip = file(LOGDIR."ip.dat");
	//$deny_ipから改行コード削除
	for ( $i = 0; $i < count( $deny_ip ); $i++ ) {
		$deny_ip[$i] = preg_replace( "/\n$/", "", $deny_ip[$i] );
		$deny_ip[$i] = preg_replace( "/\r$/", "", $deny_ip[$i] );
		list($key1, $key2, $key3, $key4) = explode(".",$ip);
		list($ipaddr, $ipdate) = explode("<>", $deny_ip[$i]);
		list($deny_key1, $deny_key2, $deny_key3, $deny_key4) = explode(".",$ipaddr);
		if ($deny_key1 == "*") $key1 = "*";
		if ($deny_key2 == "*") $key2 = "*";
		if ($deny_key3 == "*") $key3 = "*";
		if ($deny_key4 == "*") $key4 = "*";
		if ($deny_key1 == $key1 && $deny_key2 == $key2 && $deny_key3 == $key3 && $deny_key4 == $key4) {
			header("HTTP/1.0 404 Not Found");
			exit;
		}
	}
}


?>
