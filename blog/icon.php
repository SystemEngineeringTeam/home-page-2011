<?php
//------------------------------------------------------------------------
// Weblog PHP script BlognPlus（ぶろぐん＋）
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// icon.php
//
// LAST UPDATE 2004/12/25
//
//------------------------------------------------------------------------
/* ===== 初期設定ファイル読み込み ===== */
include("./conf.php");
include("./common.php");

session_start();

if (!$_COOKIE["blogn_cookie_pw"]) {
	$blogn_error = blogn_mod_db_user_check($_SESSION["blogn_session_id"], $_SESSION["blogn_session_pw"]);
	if (!$blogn_error[0]) {
		header("Content-Type: text/html; charset=UTF-8");
		echo 'セッションエラー。管理画面に入りなおしてください。';
		exit;
	}
}else{
	$blogn_error = blogn_mod_db_user_check($_COOKIE["blogn_cookie_id"], $_COOKIE["blogn_cookie_pw"]);
	if (!$blogn_error[0]) {
		header("Content-Type: text/html; charset=UTF-8");
		echo 'セッションエラー。管理画面に入りなおしてください。';
		exit;
	}
}

$blogn_user_id = $_COOKIE["blogn_cookie_pw"] ? $_COOKIE["blogn_cookie_id"] : $_SESSION["blogn_session_id"];
$userlist = blogn_mod_db_user_load();
while (list($key, $val) = each($userlist)) {
	if ($blogn_user_id == $val["id"]) {
		$user_id = $key;
		break;
	}
}

$type = @$_GET["type"];

blogn_html_header();


echo '
<script language="JavaScript">
<!--
function icon(t1) {
';

if ($type == "p") {
	echo 'var txt = window.opener.document.post.blogn_user_profile;';
}else{
	echo '
	if (document.icon.iins[0].checked) {
		var txt = window.opener.document.post.blogn_mes;
	}else{
		var txt = window.opener.document.post.blogn_more_mes;
	}
';
}

echo '
	txt.focus();
	if (txt.createTextRange && txt.lastCaretPos) {
		var lastCaretPos = txt.lastCaretPos;
		lastCaretPos.text = lastCaretPos.text + t1;
		txt.focus();
	} else if (txt.selectionStart) {
		txt.value = (txt.value).substring(0,txt.selectionStart) + t1 + (txt.value).substring(txt.selectionEnd, txt.textLength);
	}else{
		txt.value  += t1;
		txt.focus();
	}
}
-->
</script>
</head>
<body>
';

$page = @$_GET["page"];


$icon = file(BLOGN_INIDIR."icon.cgi");
echo '<form name="icon">';
if ($type != "p") {
	echo '
<input type="radio" name="iins" value="mes" id="mes" checked><label for="mes">本文に挿入</label>
<input type="radio" name="iins" value="more" id="more"><label for="more">続きに挿入</label>
';
}

echo '<div style="margin:0;padding:5px;">
<table cellpadding="1" cellspacing="0">
<tr><td>';
	for ($i = 0; $i < 100; $i++) {
		$icon[$i] = ereg_replace( "\n$", "", $icon[$i] );
		$icon[$i] = ereg_replace( "\r$", "", $icon[$i] );
		list($i_pic, $i_data) = explode("<>", $icon[$i]);
		$size = @getimagesize(BLOGN_ICONDIR.$i_pic);
		echo '<a href=javascript:icon("'.$i_data.'")><img src="'.BLOGN_ICONDIR.$i_pic.'" '.$size[3].' alt="'.$i_data.'" border="0"></a> ';
	}
	echo '</td></tr></table></form>
<div align="center"><form><input type="button" value="閉じる" onclick="window.close()"></form></div>
</body></html>';


//-----------------------------------------------------------------------------------------------
function blogn_html_header() {
echo '
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
<meta http-equiv=content-type content="text/html; charset=UTF-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<title>BlognPlus - 画像挿入</title>
<meta name="copyright" content="blogn" />
<meta http-equiv="pragma" content="no-cache" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<link rel="stylesheet" type="text/css" media="screen" href="admin.css" />
';
}


?>
