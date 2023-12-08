<?php
//--------------------------------------------------------------------
// Weblog PHP script BlognPLUS
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//--------------------------------------------------------------------
// conf.php
//
// LAST UPDATE 2005/09/29
//
// ・処理の一部をcommon.phpに移動
//
//--------------------------------------------------------------------
/* BlognPlus home url */
define('BLOGN_HOMELINK', '/blog/');
define('BLOGN_REQUESTDIR', '/blog/');
//
//--------------------------------------------------------------------
//
//
// PHP_SELFを取得できないサーバー対策
if ($_SERVER["PHP_SELF"]) {
	define('BLOGN_PHP_SELF', $_SERVER["PHP_SELF"]);
}else{
	define('BLOGN_PHP_SELF', $_SERVER["SCRIPT_NAME"]);
}
/* mbstringの有無 */
if (extension_loaded("mbstring")) {
	define('BLOGN_MBS', 1);	// mbstring on
}else{
	define('BLOGN_MBS', 0);	// mbstring off
	/* jcode */
	require_once("./jcode/jcode.php");
	require_once("./jcode/code_table.jis2ucs");
	require_once("./jcode/code_table.ucs2jis");
}
/* PHP server セーフモードの有無 */
if ($safe = ini_get('safe_mode')) {
	define('BLOGN_SMODE', 1);	// safe mode on
}else{
	define('BLOGN_SMODE', 0);	// safe mode off
}

/* trackback address */
define('BLOGN_TRACKBACKADDR', BLOGN_HOMELINK."tb.php");
//--------------------------------------------------------------------
// Database
//
/* file, mysql, postgresql */
define('BLOGN_DB_TYPE', 'text');
/* モジュールディレクトリ */
define('BLOGN_MODDIR', 'module/');
/* file type 設定ファイルディレクトリ */
define('BLOGN_INIDIR', './dat/');
/* file type ログ、コメント、トラックバックディレクトリ */
define('BLOGN_LOGDIR', './dat/log/');
define('BLOGN_CMTDIR', './dat/cmt/');
define('BLOGN_TRKDIR', './dat/trk/');

/* データベース接頭語 */
define('BLOGN_DB_PREFIX', '');
/* データベースサーバのホスト名またはIPアドレス */
define('BLOGN_DB_HOST', '');
/* データベースサーバのポートアドレス */
define('BLOGN_DB_PORT', '');
/* データベースユーザー名 */
define('BLOGN_DB_USER', '');
/* データベースユーザーパスワード */
define('BLOGN_DB_PASS', '');
/* データベース名 */
define('BLOGN_DB_NAME', '');
//--------------------------------------------------------------------
// アップロードファイルディレクトリ
//
/* picture directory */
define('BLOGN_FILEDIR', 'files/');
/* icon  directory */
define('BLOGN_ICONDIR', 'ico/');
/* skin directory */
define('BLOGN_SKINDIR', 'skin/');
/* skin picture directory */
define('BLOGN_SKINPICDIR', 'skin/images/');


?>
