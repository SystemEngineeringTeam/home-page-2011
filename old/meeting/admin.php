<?php
//------------------------------------------------------------------------
// Weblog PHP script Blogn（ぶろぐん）
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// 管理人用
//
// LAST UPDATE 2007/04/04
//
// ・記事編集削除処理にIP制限登録を追加
// ・アクセス制限したIPのトラックバックを一括削除する処理を追加
//
//------------------------------------------------------------------------
/* ===== 共有ファイル読み込み */
require_once("./common.php");
//認証
$error = "";
$loginid = escapeshellcmd(CleanHtml($loginid));
if ($flg = @$loginid) {
	setcookie("blognid",$loginid, time() + 3600 * 24 * 7);		// １週間クッキー保存
	if ($flg = @$alogin) {
		setcookie("blognpw",$loginpw, time() + 3600 * 24 * 7);	// １週間クッキー保存
	}
}
session_start();
if (!$PHPSESSID) {
	session_register("blogni");
	session_register("blognp");
	$_SESSION['blogni'] = $_COOKIE['blognid'];
	if ($flg = $_COOKIE['blognpw']) {
		$_SESSION['blognp'] = md5($_COOKIE['blognpw']);
	}
}
if (!logincheck($loginid, md5($loginpw))) {
	if(!logincheck($_SESSION['blogni'], $_SESSION['blognp'])) {
		$error = "ログインユーザー名、パスワードを入力してください。";
		loginform($error);
		exit;
	}
}



$upfile_name = @$_FILES["d_img"]["name"];
$upfile = @$_FILES["d_img"]["tmp_name"];
$htm_name = @$_FILES["s_htm"]["name"];
$css_name = @$_FILES["s_css"]["name"];
$uphtm = @$_FILES["s_htm"]["tmp_name"];
$upcss = @$_FILES["s_css"]["tmp_name"];
$qry_sid = @$_GET["sid"];
$qry_mode = @$_GET["mode"];
$qry_action = @$_GET["action"];
$qry_lid = @$_GET["lid"];
$qry_uid = @$_GET["uid"];
$qry_page = @$_GET["page"];
$qry_comment = @$_GET["comment"];
$qry_trackback = @$_GET["trackback"];
$qry_coid = @$_GET["coid"];
$qry_toid = @$_GET["toid"];
$qry_eid = @$_GET["eid"];
$qry_file = @$_GET["file"];
$qry_ip = @$_GET["ip"];
$qry_pos = @$_GET["pos"];
$qry_updown = @$_GET["updown"];
$deny_ip = @$_POST["deny_ip"] ? $_POST["deny_ip"] : $_GET["deny_ip"];

/* 各画面切り替え */
	switch ($qry_mode) {
		case "log":			//記事の投稿・更新
			headhtml(1);
			contentshtml();
			if ($preview != NULL) $qry_action = "preview";
			blog($qry_action, $newpost, $d_eid, $d_year, $d_month, $d_day, $d_hour, $d_minutes, $d_second, $d_cid, $pid, $d_title, $d_mes, $d_more, $cok, $tok, $d_trackback, $upfile, $upfile_name, $ping_url);
			foothtml();
			break;
		case "list":			//記事の一覧編集削除
			mes_list($qry_action, $qry_page, $qry_comment, $qry_trackback, $qry_eid, $qry_coid, $qry_toid);
			foothtml();
			break;
		case "file":		//ファイルのアップロード
			headhtml(0);
			contentshtml();
			file_edit($qry_action, $qry_page, $upfile, $upfile_name, $qry_file);
			foothtml();
			break;
		case "category":		//カテゴリの編集
			headhtml(0);
			contentshtml();
			category($qry_action, $cid, $c_name, $qry_pos, $qry_updown);
			foothtml();
			break;
		case "link":			//リンクの編集
			headhtml(0);
			contentshtml();
			linklist($qry_action, $qry_lid, $s_name, $s_url, $qry_pos, $qry_updown);
			foothtml();
			break;
		case "ping":
			headhtml(0);
			contentshtml();
			pinglist($qry_action, $qry_uid, $u_name, $u_url);
			foothtml();
			break;
		case "profile": 		//プロフィールの編集
			headhtml(0);
			contentshtml();
			profile($p_name, $p_email, $p_data, $p_oldimg, $p_delimg, $upfile, $upfile_name);
			foothtml();
			break;
		case "init":		// ログインID,PASSの設定
			headhtml(0);
			contentshtml();
			init_edit($newloginid, $newloginpw, $checkpw);
			foothtml();
			break;
		case "config":		// ブログの初期設定
			headhtml(0);
			contentshtml();
			conf_edit($qry_action, $sitename, $sitedesc, $width, $height, $logcount, $arcount, $necount, $rccount, $rtcount, $imcount, $tz, $charset, $address, $cok_send, $tok_send, $comment_maxsize, $comment_maxtime, $trackback_type);
			foothtml();
			break;
		case "ip":					//アクセス制限
			headhtml(0);
			contentshtml();
			ip_check($qry_action, $deny_ip, $iid, $qry_ip);
			foothtml();
			break;
		case "mobile":			//モブログ設定
			headhtml(0);
			contentshtml();
			mobile($qry_action, $receive, $send, $pop, $mobile_id, $mobile_pass, $from, $to, $access_time, $apop, $mobile_cok, $mobile_tok, $mobile_category);
			foothtml();
			break;
		case "logout":
			session_destroy();		// セッションの破棄
			setcookie("blognpw");	// クッキーの破棄
			$error = "ログアウトしました。";
			loginform($error);
			exit;
		case "skinup":			//スキンの追加
			headhtml(0);
			contentshtml();
			skinup($action, $page, $s_title, $uphtm, $upcss, $htm_name, $css_name, $qry_sid);
			foothtml();
			break;
		case "skinfile":		//スキン用画像の登録
			headhtml(0);
			contentshtml();
			skinfile($qry_action, $qry_page, $upfile, $upfile_name, $qry_file);
			foothtml();
			break;
		case "skinlist":			//スキンのカスタマイズ
			headhtml(0);
			contentshtml();
			skinlist($action, $htm_text, $css_text, $s_title, $qry_sid);
			foothtml();
			break;
		case "skinset":			//使用スキンの登録
			headhtml(0);
			contentshtml();
			skinset($action, $vid, $nskin, $rskin, $dskin, $sskin, $pskin, $iskin, $mskin, $cskin);
			foothtml();
			break;
		case "convert":			//データの出力
			if ($action == "export") data_export();
			headhtml(0);
			contentshtml();
			data_convert();
			foothtml();
			break;
		default:						//TOP画面表示
			headhtml(0);
			contentshtml();
			up_mes($qry_info);
			top_view();
			foothtml();
			break;
	}
exit;


/* ログイン画面 */
function loginform($error){
	headhtml(0);
	echo '
	<td width="10" background="./images/left.gif"></td>
	<td align="center" valign="center"><img src="./images/blank.gif" width="1" height="100">
	<p class="textlink">'.$error.'</p>
	<form action="'.PHP_SELF.'?mode=login" method=post>
	<table class="logbody">
		<tr align="left"><td>ユーザー名</td><td><input type="text" name=loginid value="'.$_COOKIE["blognid"].'" style="width:150px;"></td></tr>
		<tr align="left"><td>パスワード</td><td><input type="password" name=loginpw  style="width:150px;"></td></tr>
		<tr align="center"><td colspan="2"><input type="checkbox" name=alogin value="on" id="alogin"><label for="alogin">自動ログインを有効にする</label></td></tr>
	</table>
	<br>
	<input type="submit" value="LOGIN">
	</form>
	<img src="./images/blank.gif" width="1" height="100">
	</td>
	';
	foothtml();
}


/* ログインチェック */
function logincheck($loginid, $loginpw){
	if (file_exists(LOGDIR."init.dat")){
		$init = file(LOGDIR."init.dat");
		//$initから改行コード削除
		$init[0] = ereg_replace( "\n$", "", $init[0]);
		$init[0] = ereg_replace( "\r$", "", $init[0]);
		list($id,$pw) = explode("<>", $init[0]);
		if ($loginid == $id && $loginpw == $pw) {
			$_SESSION['blogni'] = $loginid;
			$_SESSION['blognp'] = $loginpw;
			return true;
		}else{
			return false;
		}
	}else{
		$error = "ファイルオープンエラー：init.dat<br>ファイルを確認してください。";
		loginform($error);
		exit;
	}
}


/* html（ヘッダー） */
function headhtml($key) {
header("Content-Type: text/html; charset=EUC-JP");
echo '
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
<title>管理画面</title>
<meta http-equiv=content-type content="text/html; charset=EUC-JP">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<style type="text/css">
<!--
body {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	margin: 0px;
	background-color: #FFFF;
}
#auto {overflow: auto;}

a:link    {color:#414D7B; text-decoration: underline;}
a:visited {color:#775E81; text-decoration: none;}
a:active  {color:#FFCC33; text-decoration: underline;}
a:hover   {color:#FFCC33; text-decoration: none;}

hr {
	margin: 2px 0px 2px 0px;
	border: 1px solid #CCCCCC;
}

blockquote {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 11px;
	color: #333333;
	margin: 10px 10px 10px 10px;
	padding: 10px;
	border: 1px solid #CCCCCC;
}

.btn_font{
 font-size: 11px;
 border-width: 1px;
 border-style: solid;
 background-color: #FFFFFF;
}

.link_tag:link {
	text-decoration: none;
	color: #000000;
	font-size: 10px;
	border-width: 2px;
	border-style: outset;
	border-color: #CCCCCC;
	background-color: #CCCCCC;
	padding: 2px;
	margin: 1px;
}

.link_tag:visited {
	text-decoration: none;
	color: #000000;
	font-size: 10px;
	border-width: 2px;
	border-style: outset;
	border-color: #CCCCCC;
	background-color: #CCCCCC;
	padding: 2px;
	margin: 1px;
}

.link_tag:hover {
	text-decoration: none;
	color: #000000;
	font-size: 10px;
	border-width: 2px;
	border-style: outset;
	border-color: #CC3300;
	background-color: #CCCCCC;
	padding: 2px;
	margin: 1px;
}

.link_tag:active {
	text-decoration: none;
	color: #000000;
	font-size: 10px;
	border-width: 2px;
	border-style: inset;
	border-color: #CCCCCC;
	background-color: #CCCCCC;
	padding: 2px;
	margin: 1px;
}

.mainbody {
	margin: 0px 10px 0px 10px;
}

.maintitle {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 14px;
	font-weight: bolder;
	color: #414D7B;
	border-bottom: 1px dotted #CCCCCC;
}

.maininfo {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 12px;
	font-weight: bolder;
	color: #414D7B;
	border-left: 10px solid #CCCCCC;
	border-bottom: 1px solid #CCCCCC;
	padding: 5px 0px 5px 5px;
}

.mainstate {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 10px;
	color: #999999;
}

.logtitle {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 12px;
	font-weight: bolder;
	color: #000000;
	}

.logbody {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 12px;
	color: #666666;
}

.linktitle {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 12px;
	font-weight: bolder;
	color: #000000;
	margin: 0px 0px 0px 0px;
}
.linktext {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 10px;
	color: #999999;
	margin: 0px;
}

.inputarea {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 10px;
	color: #000000;
	margin: 0px;

}

.contentsbody {
	margin: 0px 10px 0px 10px;
}

.copyright {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 11px;
	color: #333333;
	text-align: right;
	margin: 0px 10px 0px 0px;
}
-->
</style>
';

if ($key == 1) {
	echo '
<script language="JavaScript">
<!--
var c_pc = navigator.userAgent.toLowerCase();
var c_ver = parseInt(navigator.appVersion);
var ie = ((c_pc.indexOf("msie") != -1) && (c_pc.indexOf("opera") == -1));
var win = ((c_pc.indexOf("win")!=-1) || (c_pc.indexOf("16bit") != -1));

optags = new Array("<span style=\"font-size:7px\">","<span style=\"font-size:9px\">","<span style=\"font-size:12px\">","<span style=\"font-size:18px\">","<span style=\"font-size:24px\">","<b>","<i>","<u>","<s>","<div style=\"text-align:left\">","<div style=\"text-align:center\">","<div style=\"text-align:right\">","<span style=\"color:black\">","<span style=\"color:brown\">","<span style=\"color:red\">","<span style=\"color:orange\">","<span style=\"color:yellow\">","<span style=\"color:green\">","<span style=\"color:blue\">","<span style=\"color:violet\">","<span style=\"color:gray\">","<span style=\"color:white\">","<p>","<blockquote>");
cltags = new Array("<\/span>","<\/span>","<\/span>","<\/span>","<\/span>","<\/b>","<\/i>","<\/u>","<\/s>","<\/div>","<\/div>","<\/div>","<\/span>","<\/span>","<\/span>","<\/span>","<\/span>","<\/span>","<\/span>","<\/span>","<\/span>","<\/span>","<\/p>","<\/blockquote>");
linktags1 = new Array("<a href=\"","<a href=\"");
linktags2 = new Array("\" target=\"_blank\">","\">");
linktags3 = new Array("<\/a>","<\/a>");
ictags = new Array();
';

$icon = file(LOGDIR."icon.dat");
for ($i = 0; $i < 100; $i++) {
	$icon[$i] = ereg_replace( "\n$", "", $icon[$i] );
	$icon[$i] = ereg_replace( "\r$", "", $icon[$i] );
	list($i_pic, $i_data) = explode("<>", $icon[$i]);
	echo 'ictags['.$i.'] = "'.$i_data.'";
';
}

echo '
function iconview(v) {
	if (v == 0) {
		document.getElementById("MesIconOn").style.display = "block";
		document.getElementById("MesIconOff").style.display = "none";
		document.getElementById("MoreIconOn").style.display = "block";
	}else{
		document.getElementById("MesIconOn").style.display = "none";
		document.getElementById("MesIconOff").style.display = "block";
		document.getElementById("MoreIconOn").style.display = "none";
	}
}

function wopen_pict() {
	window.open("pict.php","WindowOpen1","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=480,height=350")
}

function linkins(tags,d) {
	if (d == 0) {
		var txt = document.post.d_mes;
	}else{
		var txt = document.post.d_more;
	}
	txt.focus();
	if (tags == 0) {
		msg = prompt("リンクアドレス入力フォーム","http://");
	}else{
		msg = prompt("メールアドレス入力フォーム","mailto:");
	}
	if (!msg) return;
	t0 = false;
	if ((c_ver >= 4) && ie && win) {
		if (t0 = document.selection.createRange().text) {
			document.selection.createRange().text = linktags1[tags] + msg + linktags2[tags]+ t0 + linktags3[tags];
			t0 = "";
			txt.focus();
			return;
		}else{
			txt.focus();
			tag = document.selection.createRange();
			tag.text = linktags1[tags] + msg + linktags2[tags] + linktags3[tags];
			return;
		}
	} else if (txt.selectionEnd && (txt.selectionEnd - txt.selectionStart > 0)) {
		if (txt.selectionEnd == 1 || txt.selectionEnd == 2) txt.selectionEnd = txt.textLength;
		txt.value = (txt.value).substring(0,txt.selectionStart) + linktags1[tags] + msg + linktags2[tags] + (txt.value).substring(txt.selectionStart, txt.selectionEnd) + linktags3[tags] + (txt.value).substring(txt.selectionEnd, txt.textLength);
		txt.focus();
		return;
	} else if (txt.selectionStart) {
		txt.value = (txt.value).substring(0,txt.selectionStart) + linktags1[tags] + msg + linktags2[tags] + linktags3[tags] + (txt.value).substring(txt.selectionEnd, txt.textLength);
		return;
	}
	txt.value += linktags1[tags] + msg + linktags2[tags] + linktags3[tags];
	txt.focus();
}

function ins(tags,d) {
	if (d == 0) {
		var txt = document.post.d_mes;
	}else{
		var txt = document.post.d_more;
	}
	txt.focus();
	t0 = false;
	if ((c_ver >= 4) && ie && win) {
		if (t0 = document.selection.createRange().text) {
			document.selection.createRange().text = optags[tags] + t0 + cltags[tags];
			t0 = "";
			txt.focus();
			return;
		}else{
			txt.focus();
			tag = document.selection.createRange();
			if (msg = prompt("入力フォーム","")) {
				tag.text = optags[tags] + msg + cltags[tags];
			}else{
				tag.text = optags[tags] + cltags[tags];
			}
			return;
		}
	} else if (txt.selectionEnd && (txt.selectionEnd - txt.selectionStart > 0)) {
		if (txt.selectionEnd == 1 || txt.selectionEnd == 2) txt.selectionEnd = txt.textLength;
		txt.value = (txt.value).substring(0,txt.selectionStart) + optags[tags] + (txt.value).substring(txt.selectionStart, txt.selectionEnd) + cltags[tags] + (txt.value).substring(txt.selectionEnd, txt.textLength);
		txt.focus();
		return;
	} else if (txt.selectionStart) {
		if (msg = prompt("入力フォーム","")) {
			txt.value = (txt.value).substring(0,txt.selectionStart) + optags[tags] + msg + cltags[tags] + (txt.value).substring(txt.selectionEnd, txt.textLength);
		}else{
			txt.value = (txt.value).substring(0,txt.selectionStart) + optags[tags] + cltags[tags] + (txt.value).substring(txt.selectionEnd, txt.textLength);
		}
		return;
	}
	if (msg = prompt("入力フォーム","")) {
		txt.value += optags[tags] + msg + cltags[tags];
	}else{
		txt.value += optags[tags] + cltags[tags];
	}
	txt.focus();
	pos(txt);
}

function icon(t1,d) {
	if (d == 0) {
		var txt = document.post.d_mes;
	}else{
		var txt = document.post.d_more;
	}
	if (document.selection) {
		txt.focus();
		sel = document.selection.createRange();
		sel.text = ictags[t1];
	} else if (txt.selectionStart) {
		txt.value = (txt.value).substring(0,txt.selectionStart) + ictags[t1] + (txt.value).substring(txt.selectionEnd, txt.textLength);
	}else{
		txt.value  += ictags[t1];
	}
	t1 = "";
	txt.focus();
	return;
}

function dnow(){
	now = new Date();
	document.post.d_year.selectedIndex=1;
	document.post.d_month.selectedIndex=now.getMonth();
	document.post.d_day.selectedIndex=now.getDate()-1;
	document.post.d_hour.selectedIndex=now.getHours();
	document.post.d_minutes.selectedIndex=now.getMinutes();
	document.post.d_second.selectedIndex=now.getSeconds();
}
//-->
</script>';
}elseif ($key == 2) {
	echo '
<script language="JavaScript">
<!--
function delcheck(ref) {
	if (confirm("削除してよろしいですか")) {
		window.location.href=ref;
	}
}

//-->
</script>';
}

echo '
</head>
<body style="margin:0px;">
<a name="top"></a>
<table width="720" border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
<td width="10" background="./images/left.gif"><img src="./images/blank.gif" width="1" height="50"></td>
<td width="700" height="50" background="./images/admin.jpg" align="right" valign="top"><span class="copyright">Blogn '.BLOGN_VERSION.'</span></td>
<td width="10" background="./images/right.gif"><img src="./images/blank.gif" width="1" height="50"></td>
</tr>
</table>
<table width="720" border="0" cellspacing="0" cellpadding="0" align="center">
<tr valign="top">
';
}

/* html（フッター） */
function foothtml() {
echo '
<td width="10" background="./images/right.gif"><img src="./images/blank.gif" width="1" height="150"></td>
</tr>
</table>
<table width="720" border="0" cellspacing="0" cellpadding="0" align="center">
<tr valign="top">
<td width="10" background="./images/left.gif"><img src="./images/blank.gif" width="1" height="100"></td>
<td width="700">
<div class="copyright">Copyright &copy; 2004 Blogn All Rights Reserved.</div>
</td>
<td width="10" background="./images/right.gif"><img src="./images/blank.gif" width="1" height="100"></td>
</tr>
</table>
</body>
</html>
';
}


/* html（コンテンツ） */
function contentshtml() {
echo '
<td width="10" background="./images/left.gif"><img src="./images/blank.gif" width="1" height="150"></td>
<td width="140">
<br>
<div class="contentsbody">
<div class="linktitle">■ 編集メニュー ■</div>
<div class="linktext">
<hr>
<a href="'.PHP_SELF.'?mode=top">[TOP]</a><br>
<a href="./index.php" target="_blank">[ブログを見る]</a><br>
<hr>
<a href="'.PHP_SELF.'?mode=log">[記事の新規投稿]</a><br>
<a href="'.PHP_SELF.'?mode=list">[記事の編集削除]</a><br>
<hr>
<a href="'.PHP_SELF.'?mode=file">[画像のアップロード]</a><br>
<a href="'.PHP_SELF.'?mode=category">[カテゴリの管理]</a><br>
<a href="'.PHP_SELF.'?mode=ping">[Ping送信先の管理]</a><br>
<a href="'.PHP_SELF.'?mode=link">[リンクの管理]</a><br>
<a href="'.PHP_SELF.'?mode=profile">[プロフィールの管理]</a><br>
<hr>
<a href="'.PHP_SELF.'?mode=skinup">[スキンの追加編集]</a><br>
<a href="'.PHP_SELF.'?mode=skinfile">[スキン用画像の登録]</a><br>
<a href="'.PHP_SELF.'?mode=skinset">[使用スキンの登録]</a><br>
<hr>
<a href="'.PHP_SELF.'?mode=convert">[データ管理]</a><br>
<hr>
<a href="'.PHP_SELF.'?mode=init">[ログインの設定]</a><br>
<a href="'.PHP_SELF.'?mode=config">[ブログの初期設定]</a><br>
<a href="'.PHP_SELF.'?mode=mobile">[モバイルの投稿設定]</a><br>
<hr>
<a href="'.PHP_SELF.'?mode=ip">[アクセス制限]</a><br>
<hr>
<a href="'.PHP_SELF.'?mode=logout">[ログアウト]</a><br>
<hr>
</div></div>
</td>
';
}


/* インフォメーション画面表示 */
function up_mes($inform){
	echo '<td width="560">';
	if (trim($inform) != "") {
		echo '<br><table width="550" border="0"  cellspacing="0" cellpadding="0">
		<tr><td>
		<div class="mainbody">
		<div class="maininfo">'.$inform.'</div>
		</div>
		</td></tr>
		</table>';
	}
}


/* TOP画面表示 */
function top_view(){
	echo '<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">TOP</div>
	<div class="logbody" style="text-align:right;"><a href="http://www.blogn.org" target="_blank">&gt;&gt;Blogn最新情報はこちら&lt;&lt;</a></div>
	';
	if($safe = ini_get('safe_mode')) {
		echo '
		<br>
		<div class="logbody">ご使用のサーバーはセーフモードが有効になっています。<br>
		<br>
		※セーフモードが有効になっているサーバーでは一部の機能が制限されます。<br>
		<br>
		・/logディレクトリ以下に手動で年ディレクトリを作成しなければならない。<br>
		・作成した年ディレクトリのパーミッションを７７７（７０７）に変更しなければならない。
		</div>
		';
	}
	echo '
	</div>
	</td></tr></table>
	</td>';
}


/* 管理画面ログイン設定 */
function init_edit($loginid, $loginpw, $checkpw){
	if (file_exists(LOGDIR."init.dat")){
//		$oldinit = file(LOGDIR."init.dat");
		if ($loginid) {
			if (!CheckIDPW($loginid) || !CheckIDPW($loginpw)) {
				$inform = "ID及びPASSには半角英数字を使用ください。";
			}else{
				if ($loginpw != $checkpw) {
					$inform = "ログインPASSと確認用PASSが同じではありません。<br>もう一度入力してください。";
				}else{
					$_SESSION["blognp"] = $loginpw;
					$newinit = $loginid."<>".md5($loginpw)."\r\n";
					$fp = fopen(LOGDIR."init.dat", "w");
					flock($fp, LOCK_EX);
					fputs($fp, $newinit);
					fclose($fp);
					$inform = "ログインID,PASSを更新しました。";
				}
			}
		}
	}else{
		$inform = "ファイルオープンエラー：init.dat<br>ファイルを確認してください。";
	}
	up_mes($inform);
	if (file_exists(LOGDIR."init.dat")){
		$init = file(LOGDIR."init.dat");
		//$initから改行コード削除
		$init[0] = ereg_replace( "\n$", "", $init[0]);
		$init[0] = ereg_replace( "\r$", "", $init[0]);
		list($loginid,$loginpw) = explode("<>", $init[0]);
	}
	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">ログインの設定</div><br>

	<table border="0" cellspacing="0" cellpadding="0" class="logbody">
	<form action="'.PHP_SELF.'?mode=init" method=post>
	<tr><td>
	<table border="1" cellspacing="0" cellpadding="0" class="logbody">
	<tr>
	<td width="150" bgcolor="#82BE7D"><label for=loginid>ログインID</label></td>
	<td width="250"><input type="text" id=loginid name=newloginid size=10 value="'.$loginid.'"></td>
	</tr>
	<tr>
	<td width="150" bgcolor="#82BE7D"><label for=loginpw>ログインPASS</label></td>
	<td width="250"><input type="password" id=loginpw name=newloginpw size=10 value="'.$_SESSION["blognp"].'"></td>
	</tr>
	<tr>
	<td width="150" bgcolor="#82BE7D"><label for=checkpw>確認用（再入力）</label></td>
	<td width="250"><input type="password" id=checkpw name=checkpw size=8></td>
	</tr>
	</table>
	</td></tr>
	<tr><td align="right">
	<input type="submit" value="更新する">
	</td></tr>
	</form>
	</table>
	</div>
	</td></tr>
	</table>
	</td>
	';
}


function data_convert() {
	echo '
	<td>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">データ出力</div><br>
<form action="./admin.php?mode=convert&action=export" method="post">
<div class="logbody">ログのエクスポート ※Movable Type形式（一部拡張）<input type="submit" value="出力する"></div>
</form>



	</td></tr>
	</table>
	</div>
	</td>
	';
}


function data_export() {
	$convdata = "";
	if ($loglist = LogFileList(0)){
		sort($loglist);	// 全ログファイル名を昇順ソート（古い月順）
		while(list ($key, $val) = each($loglist)) {
			$log = file(LOGDIR.substr($val,3,4)."/".$val);
			krsort($log);	// 月別ログファイルのデータをキーで降順ソート（古い日付順）
			while (list($logkey, $logval) = each($log)) {
				$logval = preg_replace( "/\n$/", "", $logval );
				$logval = preg_replace( "/\r$/", "", $logval );
				list($eid, $d_date, $d_time, $cid, $pid, $d_title, $d_mes, $d_more, $d_cok, $d_tok) = explode("<>", $logval);

				$convdata .= "TITLE: ".$d_title."\n";
				$p_name = profile_call($pid);
				$convdata .= "AUTHOR: ".$p_name."\n";
				$convdata .= "DATE: ".substr($d_date,4,2)."/".substr($d_date,6,2)."/".substr($d_date,0,4)." ".substr($d_time,0,2).":".substr($d_time,2,2).":".substr($d_time,4,2)."\n";
				$c_name = category_call($cid);
				$convdata .= "CATEGORY: ".$c_name."\n";
				if ($d_cok != "1") {
					$convdata .= "ALLOW COMMENTS: 1\n";
				}
				if ($d_tok != "1") {
					$convdata .= "ALLOW PINGS: 1\n";
				}
				$convdata .= "-----\n";
				$d_mes = tagreplaceStr($d_mes);
				$d_mes = str_replace("<br />", "\n", $d_mes);
				$d_mes = str_replace("<br>", "\n", $d_mes);
				$convdata .= "BODY:\n".$d_mes."\n";
				$convdata .= "-----\n";
				$d_more = tagreplaceStr($d_more);
				$d_more = str_replace("<br />", "\n", $d_more);
				$d_more = str_replace("<br>", "\n", $d_more);
				$convdata .= "EXTENDED BODY:\n";
				if ($d_more) $convdata .= $d_more."\n";
				$convdata .= "-----\n";
				$convdata .= "EXCERPT:\n";
				$convdata .= "-----\n";
				$convdata .= "KEYWORDS:\n";
				$convdata .= "-----\n";

				if ($cmtlist = IDCheck($eid, 1)) {
					@sort($cmtlist);
					for ($i = 0; $i < count($cmtlist); $i++ ) {
						$cmt = file(LOGDIR.substr($cmtlist[$i], 3, 4)."/".$cmtlist[$i]);
						krsort($cmt);
						while (list($cmtkey, $cmtval) = each($cmt)) {
							$cmtval = preg_replace( "/\n$/", "", $cmtval );
							$cmtval = preg_replace( "/\r$/", "", $cmtval );
							list($c_eid, $c_date, $c_time, $c_name, $c_email, $c_mes, $c_ip_addr, $c_user_agent, $c_url) = explode("<>", $cmtval);
							if ($eid == $c_eid) {
								$convdata .= "COMMENT:\n";
								$convdata .= "AUTHOR: ".$c_name."\n";
								$convdata .= "DATE: ".substr($c_date,4,2)."/".substr($c_date,6,2)."/".substr($c_date,0,4)." ".substr($c_time,0,2).":".substr($c_time,2,2).":00\n";
								if ($c_ip_addr) $convdata .= "IP: ".$c_ip_addr."\n";
								if ($c_user_agent) $convdata .= "AGENT: ".$c_user_agent."\n";
								if ($c_email) $convdata .= "EMAIL: ".$c_email."\n";
								if ($c_url) $convdata .= "URL: ".$c_url."\n";
								$c_mes = str_replace("<br />", "\n", $c_mes);
								$c_mes = str_replace("<br>", "\n", $c_mes);
								$convdata .= $c_mes."\n";
								$convdata .= "-----\n";
							}
						}
					}
				}
				if ($trklist = IDCheck($eid, 2)) {
					@sort($trklist);
					for ($i = 0; $i < count($trklist); $i++ ) {
						$trk = file(LOGDIR.substr($trklist[$i], 3, 4)."/".$trklist[$i]);
						krsort($trk);
						while (list($trkkey, $trkval) = each($trk)) {
							$trkval = preg_replace( "/\n$/", "", $trkval );
							$trkval = preg_replace( "/\r$/", "", $trkval );
							list($t_eid, $blog_name, $t_title, $t_url, $t_excerpt, $t_date, $t_time, $t_ip_addr, $t_user_agent ) = explode("<>", $trkval);
							if ($eid == $t_eid) {
								$convdata .= "PING:\n";
								$convdata .= "TITLE: ".$t_title."\n";
								$convdata .= "URL: ".$t_url."\n";
								if ($t_ip_addr) $convdata .= "IP: ".$t_ip_addr."\n";
								$convdata .= "BLOG NAME: ".$blog_name."\n";
								$convdata .= "DATE: ".substr($t_date,4,2)."/".substr($t_date,6,2)."/".substr($t_date,0,4)." ".substr($t_time,0,2).":".substr($t_time,2,2).":00\n";
								$t_excerpt = str_replace("<br />", "\n", $t_excerpt);
								$t_excerpt = str_replace("<br>", "\n", $t_excerpt);
								$convdata .= $t_excerpt."\n";
								$convdata .= "-----\n";
							}
						}
					}
				}
			$convdata .= "--------\n";
			}
		}
	}
	header("Content-type: text/plain name=log_mt_mode.txt"); 
	header("Content-Disposition: attachment; filename=log_mt_mode.txt"); 

	echo $convdata;
	exit;
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


function conf_edit($action, $sitename, $sitedesc, $width, $height, $logcount, $arcount, $necount, $rccount, $rtcount, $imcount, $tz, $charset, $address, $cok_send, $tok_send, $comment_maxsize, $comment_maxtime, $trackback_type){
	if ($action == "set"){
		if (file_exists(LOGDIR."conf.dat")) {
			$oldconf = file(LOGDIR."conf.dat");
			$conf[0] = ereg_replace( "\n$", "", $conf[0]);
			$conf[0] = ereg_replace( "\r$", "", $conf[0]);
			list(,,,,,,,,,,,$old_charset,,,,,,) = explode("<>", $conf[0]);

			if (!$sitename) {
				$inform = "サイト名が記入されていません。";
			}elseif (!$sitedesc) {
				$inform = "サイトの説明が記入されていません。";
			}elseif (!is_numeric($width) || !is_numeric($height)) {
				$inform = "表示画像サイズが適切ではありません。";
			}else{
				$sitename = CleanStr($sitename);
				$sitedesc = CleanStr($sitedesc);
				$newconf = $sitename."<>".$sitedesc."<>".$width."<>".$height."<>".$logcount."<>".$arcount."<>".$necount."<>".$rccount."<>".$rtcount."<>".$imcount."<>".$tz."<>".$charset."<>".$address."<>".$cok_send."<>".$tok_send."<>".$comment_maxsize."<>".$comment_maxtime."<>".$trackback_type."\r\n";

				$fp = fopen(LOGDIR."conf.dat", "w");
				flock($fp, LOCK_EX);
				fputs($fp, $newconf);
				fclose($fp);
				$inform = "初期設定を更新しました。";
				if ($old_charset != $charset) {
					if ($charset == 0) {
						$convkey = 2;
					}elseif ($charset == 1) {
						$convkey = 1;
					}elseif ($charset == 2) {
						$convkey = 4;
					}
					if ($skin = file(LOGDIR."skin.dat")) {
						for ($i = 0; $i <count($skin); $i++) {
							$skin[$i] = ereg_replace( "\n$", "", $skin[$i] );
							$skin[$i] = ereg_replace( "\r$", "", $skin[$i] );
							list($sid, $title, $htm_name, $css_name) = explode("<>",$skin[$i]);

							if ($htm_skin = file(SKINDIR.$htm_name)) {
								$htm_text = implode('', $htm_skin);
								$htm_text = mbConv($htm_text,0,$convkey);
								if (unlink(SKINDIR.$htm_name)) {
									$fp = fopen(SKINDIR.$htm_name, "w");
									flock($fp, LOCK_EX);
									fputs($fp, $htm_text);
									fclose($fp);
								}
							}
							if ($css_skin = file(SKINDIR.$css_name)) {
								$css_text = implode('', $css_skin);
								$css_text = mbConv($css_text,0,$convkey);
								if (unlink(SKINDIR.$css_name)) {
									$fp = fopen(SKINDIR.$css_name, "w");
									flock($fp, LOCK_EX);
									fputs($fp, $css_text);
									fclose($fp);
								}
							}
						}
					}
				}
			}
		}else{
			$inform = "ファイルオープンエラー：conf.dat<br>ファイルを確認してください。";
		}
	}
	up_mes($inform);
	
	if (file_exists(LOGDIR."conf.dat")){
		$conf = file(LOGDIR."conf.dat");
		//$initから改行コード削除
		$conf[0] = ereg_replace( "\n$", "", $conf[0]);
		$conf[0] = ereg_replace( "\r$", "", $conf[0]);
		list($sitename,$sitedesc,$width,$height,$logcount,$arcount,$necount,$rccount,$rtcount,$imcount,$tz,$charset, $address, $cok_send, $tok_send, $comment_maxsize, $comment_maxtime, $trackback_type) = explode("<>", $conf[0]);
	}
	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">ブログの初期設定</div><br>
	<table border="0"  cellspacing="0" cellpadding="0" class="logbody">
	<form action="'.PHP_SELF.'?mode=config&action=set" method=post>
	<tr><td>
	<table border="1"  cellspacing="0" cellpadding="0" class="logbody">
	<tr>
	<td width="200" bgcolor="#82BE7D"><label for=sitename>サイト名</label></td>
	<td width="300"><input type="text" id=sitename name=sitename size=40 value="'.$sitename.'"></td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D"><label for=sitedesc>サイトの説明</label></td>
	<td width="300"><input type="text" id=sitedesc name=sitedesc size=50 value="'.$sitedesc.'"></td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D"><label for=width>表示画像サイズ 横</label></td>
	<td width="300"><input type="text" id=width name=width size=8 value="'.$width.'"> pixel</td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D"><label for=height>表示画像サイズ 縦</label></td>
	<td width="300"><input type="text" id=height name=height size=8 value="'.$height.'"> pixel</td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D">記事表示件数</td>
	<td width="300"><select name=logcount>';
	for ($i = 1; $i <= 10; $i++) {
		if ($i == $logcount) {
			echo '<option value="'.$i.'" selected>'.$i.'件</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'件</option>';
		}
	}
	echo '
	</select></td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D">月別記事一覧表示件数</td>
	<td width="300"><select name=arcount>';
	for ($i = 1; $i <= 10; $i++) {
		if ($i == $arcount) {
			echo '<option value="'.$i.'" selected>'.$i.'件</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'件</option>';
		}
	}
	echo '
	</select></td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D">最新記事一覧表示件数</td>
	<td width="300"><select name=necount>';
	for ($i = 1; $i <= 10; $i++) {
		if ($i == $necount) {
			echo '<option value="'.$i.'" selected>'.$i.'件</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'件</option>';
		}
	}
	echo '
	</select></td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D">最新コメント一覧表示件数</td>
	<td width="300"><select name=rccount>';
	for ($i = 1; $i <= 10; $i++) {
		if ($i == $rccount) {
			echo '<option value="'.$i.'" selected>'.$i.'件</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'件</option>';
		}
	}
	echo '
	</select></td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D">最新トラックバック一覧表示件数</td>
	<td width="240"><select name=rtcount>';
	for ($i = 1; $i <= 10; $i++) {
		if ($i == $rtcount) {
			echo '<option value="'.$i.'" selected>'.$i.'件</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'件</option>';
		}
	}
	echo '
	</select></td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D">携帯での表示件数</td>
	<td width="300"><select name=imcount>';
	for ($i = 1; $i <= 5; $i++) {
		if ($i == $imcount) {
			echo '<option value="'.$i.'" selected>'.$i.'件</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'件</option>';
		}
	}
	echo '
	</select></td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D">タイムゾーンの設定</td>
	<td width="300"><select name=tz>';
	for ($i = -12; $i <= 13; $i++) {
		if ($i == $tz) {
			echo '<option value="'.$i.'" selected>GMT ';
			if ($i < 0) {
				echo $i.' Hours</option>';
			}elseif ($i == 0) {
				echo '</option>';
			}elseif ($i == 9) {
				echo '+'.$i.' Hours [東京・大阪・札幌]</option>';
			}else{
				echo '+'.$i.' Hours</option>';
			}
		}else{
			echo '<option value="'.$i.'">GMT ';
			if ($i < 0) {
				echo $i.' Hours</option>';
			}elseif ($i == 0) {
				echo '</option>';
			}elseif ($i == 9) {
				echo '+'.$i.' Hours [東京・大阪・札幌]</option>';
			}else{
				echo '+'.$i.' Hours</option>';
			}
		}
	}
	echo '
	</select></td>
	</tr>
	<tr>
	<td width="200" bgcolor="#82BE7D">出力文字コード</td>
	<td width="300"><select name=charset>';
	if ($charset == 0) {
		echo '
			<option value="0" selected>Shift_JIS</option>
			<option value="1">EUC-JP</option>
			<option value="2">UTF-8</option>
		';
	}elseif ($charset == 1) {
		echo '
			<option value="0">Shift_JIS</option>
			<option value="1" selected>EUC-JP</option>
			<option value="2">UTF-8</option>
		';
	}elseif ($charset == 2) {
		echo '
			<option value="0">Shift_JIS</option>
			<option value="1">EUC-JP</option>
			<option value="2" selected>UTF-8</option>
		';
	}
	echo '
	</select></td>
	</tr>
  <tr>
	<td width="200" rowspan="3" bgcolor="#82BE7D">メール通知機能<br>（登録したメールアドレスに通知）</td>
	<td width="300">E-Mail:
	<input type="text" name=address size=30 value="'.$address.'">
	</td></tr>
	<tr>
	<td width="300">
	';
	if ($cok_send != 1) {
		echo '<input type="checkbox" name="cok_send" value=1 id="cok">';
	}else{
		echo '<input type="checkbox" name="cok_send" value=1 checked id="cok">';
	}
	echo '<label for=cok>コメントがあればメールで連絡</label>
	</td></tr>
	<tr>
	<td width="300">
	';
	if ($tok_send != 1) {
		echo '<input type="checkbox" name="tok_send" value=1 id="tok">';
	}else{
		echo '<input type="checkbox" name="tok_send" value=1 checked id="tok">';
	}
	echo '<label for=tok>トラックバックがあればメールで連絡</label>
	</td></tr>
	<tr><td rowspan="2" bgcolor="#82BE7D">コメント投稿制限
	</td><td>最大文字数（半角） <input type="text" name=comment_maxsize size=4 value="'.$comment_maxsize.'"> 文字
	</td></tr>
	<tr><td>連続投稿制限（分） <select name=comment_maxtime> 
	';
	$maxtime = array("制限無し","1分","2分","3分","4分","5分"); 
	while(list($key,$val) = each($maxtime)) { 
		if ($comment_maxtime == $key) {
			echo '<option value="'.$key.'" selected>'.$val.'</option>';
		}else{
			echo '<option value="'.$key.'">'.$val.'</option>';
		}
	}
	echo '
	</select>
	</td></tr>
	<tr><td bgcolor="#82BE7D">トラックバック設定
</td>
	<td>
';
	if ($trackback_type != 1) {
		echo '<input type="checkbox" name="trackback_type" value=1 id="trackbacktype">';
	}else{
		echo '<input type="checkbox" name="trackback_type" value=1 checked id="trackbacktype">';
	}
	echo '<label for=trackbacktype>トラックバック受信方法を[／]⇒[？]に変更</label>
<div class="mainstate">※ トラックバックが受信できない場合はチェックを入れてください。</div>
	</td></tr>
	</table>
	</td></tr>
	<tr><td align="right">
	<input type="submit" value="更新する">
	</form>
	</td></tr>
	</table>
	</div>
	</td></tr>
	</table>
	</div>
	</td>
	';
}


function CheckIDPW ($str){
	if (!ereg("[^[:alnum:]]",$str)) {
		return true;
	}else{
		return false;
	}
}


/* プロフィールの更新処理 */
function profile($p_name, $p_email, $p_data, $p_oldimg, $p_delimg, $upfile, $upfile_name){
	if ($p_name) {
		//テキスト整形
		$p_name = CleanStr($p_name);
		$p_email = CleanStr($p_email);
		$p_data = CleanStr($p_data);
		// 改行文字の統一。 
		$p_data = str_replace( "\r\n",  "\n", $p_data);
		$p_data = str_replace( "\r",  "\n", $p_data);
		$p_data = nl2br($p_data);						//改行文字の前に<br>を代入する
		$p_data = str_replace("\n",  "", $p_data);		//\nを文字列から消す。

		if(file_exists($upfile)){
			$p_delimg = "del";
		}
		if ($p_delimg == "del") {
			$dest = PICDIR.$p_oldimg;
			@unlink($dest);
			$p_oldimg = "";
		}
		if(file_exists($upfile)){
			if(ereg("[\xA1-\xFE]", $upfile_name)) {
				$pathname = pathinfo($dest);
				$upfile_name = gmdate("YmdHis",time() + TIMEZONE).$pathname['extension'];
			}
			$dest = PICDIR.$upfile_name;
			copy($upfile, $dest);
			if(!is_file($dest)) error("アップロードに失敗しました。<br>サーバがサポートしていない可能性があります");
			$size = @getimagesize($dest);
			if($size[2]=="") error("アップロードに失敗しました。<br>画像ファイル以外は受け付けません");
			$newprofile = "0<>".$p_name."<>".$p_email."<>".$p_data."<>".$upfile_name."\r\n";
		}else{
			$newprofile = "0<>".$p_name."<>".$p_email."<>".$p_data."<>".$p_oldimg."\r\n";
		}
		//ログファイル書き込み
		$fp = fopen(LOGDIR."profile.dat", "w");
		flock($fp, LOCK_EX);
		if (strlen($newprofile)){
			fputs($fp, $newprofile);
		}
		fclose($fp);
		$inform = "プロフィールを更新しました。";
	}else{
		$inform = "";
	}
	up_mes($inform);
	if (file_exists(LOGDIR."profile.dat")){
		$profile = file(LOGDIR."profile.dat");
		//$profileから改行コード削除
		$profile[0] = ereg_replace( "\n$", "", $profile[0]);
		$profile[0] = ereg_replace( "\r$", "", $profile[0]);
		list($pid, $p_name, $p_email, $p_data, $p_img) = explode("<>", $profile[0]);
		//$p_dataから<br>を改行コードに置換
		$p_data = str_replace("<br />", "\n", $p_data);
  }else{
    $pid = 0;
    $p_name = "";
    $p_email = "";
    $p_data = "";
    $p_img = "";
	}
	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">プロフィールの管理</div><br>
	<div class="linktitle">■ プロフィールの編集 ■</div>
	<form action="'.PHP_SELF.'?mode=profile" enctype="multipart/form-data" method=post>
	<table border="0"  cellspacing="0" cellpadding="0" class="logbody">
	<tr><td width="50%" valign="top">
	<label for=p_name>ユーザー名</label><br>
	<input id=p_name name=p_name value="'.$p_name.'" style="width:200px;"><br>
	<label for=p_email>メールアドレス</label><br>
	<input id=p_email name=p_email value="'.$p_email.'" style="width:200px;"><br>
	</td><td width="50%" valign="top">
	<label for=d_img>アバターファイルアップロード</label><br>
	<input type="file" name=d_img accept="image/jpeg,image/gif,image/png" size=30 style="width:200px;"><br>
	<input type="checkbox" name=p_delimg value="del" id="c1"><label for="c1">アバターを削除</label><br>
	';
	if ($p_img != "") {
		echo '<img src="'.PICDIR.$p_img.'" alt="'.$p_img.'">
		<input type="hidden" name="p_oldimg" value="'.$p_img.'">';
	}else{
		echo 'NO AVATAR
		<input type="hidden" name="p_oldimg" value="">';
	}
	echo'
	</td></tr>
	<tr><td colspan="2">
	<label for=p_data>ユーザーの説明</label><br>
	<textarea id=p_data style="WIDTH: 500px;font-size: 12px;" name=p_data rows=10 cols=50>'.$p_data.'</textarea><br><br>
	</td></tr>
	<tr><td colspan="2" align="right">
	<input type="submit" value="更新する">
	</td></tr>
	</table>
	</form>
	</div>
	</td></tr>
	</table>
	</td>
	';
}


/* リンク管理画面表示 */
function linklist($action, $lid, $s_name, $s_url, $pos, $updown){
	if ($action == "cpost") {		// リンクカテゴリの追加
		if (file_exists(LOGDIR."link.dat")){
			$oldlink = file(LOGDIR."link.dat");
			$lid = count($oldlink);
		}else{
			$oldlink = "";
			$lid = 0;
		}
		//テキスト整形
		$s_name = CleanStr($s_name);
		$newlink = $s_name."<><>1\r\n";
		//ログファイル書き込み
		$fp = fopen(LOGDIR."link.dat", "w");
		flock($fp, LOCK_EX);
		if (strlen($newlink)){
			fputs($fp, $newlink);
		}
		fputs($fp, implode('', $oldlink));
		fclose($fp);
		$inform = "リンクサイトを追加しました。";
	}elseif ($action == "post") {		// リンクサイトの追加
		if (file_exists(LOGDIR."link.dat")){
			$oldlink = file(LOGDIR."link.dat");
			$lid = count($oldlink);
		}else{
			$oldlink = "";
			$lid = 0;
		}
		//テキスト整形
		$s_name = CleanStr($s_name);
		$s_url = CleanStr($s_url);
		$newlink = $s_name."<>".$s_url."<>0\r\n";
		//ログファイル書き込み
		$fp = fopen(LOGDIR."link.dat", "w");
		flock($fp, LOCK_EX);
		if (strlen($newlink)){
			fputs($fp, $newlink);
		}
		fputs($fp, implode('', $oldlink));
		fclose($fp);
		$inform = "リンクサイトを追加しました。";
	}elseif ($action == "edit") {		// リンクの更新
		$linklist = file(LOGDIR."link.dat");
		list($old_name, $old_url, $old_category) = explode("<>",$linklist[$lid]);
		$old_category = ereg_replace( "\n$", "", $old_category);
		$old_category = ereg_replace( "\r$", "", $old_category);
		//テキスト整形
		$s_name = CleanStr($s_name);
		if ($old_category != 1) {
			$s_url = CleanStr($s_url);
			$linklist[$lid] = $s_name."<>".$s_url."<>0\r\n";
		}else{
			$linklist[$lid] = $s_name."<><>1\r\n";
		}
		//ログファイル書き込み
		$fp = fopen(LOGDIR."link.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $linklist));
		fclose($fp);
		$inform = "リンクを更新しました。";
	}elseif ($action == "delete") {		// リンクの削除
		$linklist = file(LOGDIR."link.dat");
		array_splice($linklist, $lid, 1);
		//ログファイル書き込み
		$fp = fopen(LOGDIR."link.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $linklist));
		fclose($fp);
		$inform = "リンクを削除しました。";
	//------------------------------------------------------------------
	}elseif ($action == "change") {
		$linklist = file(LOGDIR."link.dat");
		if ($updown == "up") {
			$tmplink1 = $linklist[$pos];
			$tmplink2 = $linklist[$pos - 1];
			$linklist[$pos] = $tmplink2;
			$linklist[$pos - 1] = $tmplink1;
		}else{
			$tmplink1 = $linklist[$pos];
			$tmplink2 = $linklist[$pos + 1];
			$linklist[$pos] = $tmplink2;
			$linklist[$pos + 1] = $tmplink1;
		}
		$fp = fopen(LOGDIR."link.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $linklist));
		fclose($fp);
		$inform = "リンクの順番を変更しました。";
	//------------------------------------------------------------------
	}else{
		$inform = "";
	}
	up_mes($inform);
	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">リンクの管理</div><br>
	<div class="linktitle">■ リンクカテゴリ追加 ■</div>
	<table border="0" cellspacing="0" cellpadding="0" class="logbody">
	<form action="'.PHP_SELF.'?mode=link&action=cpost" method=post>
	<tr><td>
	<table border="1" cellspacing="0" cellpadding="5" class="logbody">
	<tr><td width=100 align="center" bgcolor="#82BE7D"><label for=s_name>カテゴリ名</label></td><td width="400"><input id=s_name tabIndex=1 name=s_name size=60 style="width:390px;"></td></tr>
	</table>
	</td></tr>
	<tr><td align="right">
  <input type="submit" value="追加する">
	</td></tr>
  </form>
	</table><br>
	<div class="linktitle">■ リンクサイト追加 ■</div>
	<table border="0" cellspacing="0" cellpadding="0" class="logbody">
	<form action="'.PHP_SELF.'?mode=link&action=post" method=post>
	<tr><td>
	<table border="1" cellspacing="0" cellpadding="5" class="logbody">
	<tr><td width=100 align="center" bgcolor="#82BE7D"><label for=s_name>サイト名</label></td><td width="400"><input id=s_name tabIndex=1 name=s_name size=60 style="width:390px;"></td></tr>
	<tr><td width=100 align="center" bgcolor="#82BE7D"><label for=s_url>URL</label></td><td width="400"><input id=s_url tabIndex=2 name=s_url size=60 style="width:390px;"></td></tr>
	</table>
	</td></tr>
	<tr><td align="right">
	<input type="submit" value="追加する">
	</td></tr>
	</form>
	</table><br>
	<div class="linktitle">■ 登録サイト ■</div>
	<table border="1" cellspacing="0" cellpadding="5" class="logbody">
	<tr align="center" bgcolor="#82BE7D"><td width="200" colspan="2">サイト名</td><td width="150">URL</td><td width="50">変更</td><td width="50">削除</td><td colspan="2" width="50">並び替え</td></tr>
	';
  if (file_exists(LOGDIR."link.dat")){
    $link = file(LOGDIR."link.dat");
    //$linkから改行コード削除
		if (count($link) == 0) {
			echo '<tr><td colspan="7" align="center">リンクは登録されていません。</td></tr>';
		}else{
			for ( $i = 0; $i < count( $link ); $i++ ) {
				$link[$i] = ereg_replace( "\n$", "", $link[$i] );
				$link[$i] = ereg_replace( "\r$", "", $link[$i] );
				list($l_name, $l_url, $l_category) = explode("<>", $link[$i]);
				echo '<form action="'.PHP_SELF.'?mode=link&action=edit&lid='.$i.'" method=post>';
				if ($l_category != 1) {
					echo '<tr><td>+</td><td><input id=s_name name=s_name size=18 value="'.$l_name.'" style="width:140px;"></td><td><input id=s_url name=s_url size=28 value="'.$l_url.'" style="width:140px;"></td><td class="entry_body" align ="center"><input type="image" src="./images/update.gif" name="submit" alt="更新"></td><td align ="center"><a href="'.PHP_SELF.'?mode=link&action=delete&lid='.$i.'"><img src="./images/trash.gif" border="0"></a></td>';
				}else{
					echo '<tr><td colspan="3" bgcolor="#cccccc">カテゴリ名 <input id=s_name name=s_name size=18 value="'.$l_name.'" style="width:260px;"></td><td class="entry_body" align ="center"><input type="image" src="./images/update.gif" name="submit" alt="更新"></td><td align ="center"><a href="'.PHP_SELF.'?mode=link&action=delete&lid='.$i.'"><img src="./images/trash.gif" border="0"></a></td>';
				}
				if ($i == 0 && $i == count($link) - 1) {
					echo '<td align="center" width="25">×</td><td align="center" width="25">×</td>';
				}elseif ($i == 0) {
					echo '<td align="center" width="25">×</td><td align="center" width="25"><a href="'.PHP_SELF.'?mode=link&action=change&pos='.$i.'&updown=down"><img src="./images/down.gif" width="20" height="20" border="0"></a></td>';
				}elseif ($i == count($link) - 1) {
					echo '<td align="center" width="25"><a href="'.PHP_SELF.'?mode=link&action=change&pos='.$i.'&updown=up"><img src="./images/up.gif" width="20" height="20" border="0"></a></td><td align="center" width="25">×</td>';
				}else{
					echo '<td align="center" width="25"><a href="'.PHP_SELF.'?mode=link&action=change&pos='.$i.'&updown=up"><img src="./images/up.gif" width="20" height="20" border="0"></a></td><td align="center" width="25"><a href="'.PHP_SELF.'?mode=link&action=change&pos='.$i.'&updown=down"><img src="./images/down.gif" width="20" height="20" border="0"></a></td>';
				}
				echo '</tr></form>';
			}
		}
	}
	echo '
	</table>
	</div>
	</td></tr>
	</table>
	</td>
	';
}


/* Ping送信先管理画面表示 */
function pinglist($action, $uid, $u_name, $u_url){
	if ($action == "post") {		// 追加
		if (file_exists(LOGDIR."ping.dat")){
			$oldping = file(LOGDIR."ping.dat");
			$uid = count($oldping);
		}else{
			$olduid = "";
			$uid = 0;
		}
		//テキスト整形
		$u_name = CleanStr($u_name);
		$u_url = CleanStr($u_url);
		$newping = $u_name."<>".$u_url."\r\n";
		//ログファイル書き込み
		$fp = fopen(LOGDIR."ping.dat", "w");
		flock($fp, LOCK_EX);
		if (strlen($newping)){
			fputs($fp, $newping);
		}
		fputs($fp, implode('', $oldping));
		fclose($fp);
		$inform = "Ping送信先を追加しました。";
	}elseif ($action == "edit") {		// 更新
		$pinglist = file(LOGDIR."ping.dat");
		//テキスト整形
		$u_name = CleanStr($u_name);
		$u_url = CleanStr($u_url);
		$pinglist[$uid] = $u_name."<>".$u_url."\r\n";
		//ログファイル書き込み
		$fp = fopen(LOGDIR."ping.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $pinglist));
		fclose($fp);
		$inform = "Ping送信先を更新しました。";
	}elseif ($action == "delete") {		// 削除
		$pinglist = file(LOGDIR."ping.dat");
		array_splice($pinglist, $uid, 1);
		//ログファイル書き込み
		$fp = fopen(LOGDIR."ping.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $pinglist));
		fclose($fp);
		$inform = "Ping送信先を削除しました。";
	}else{
		$inform = "";
	}
	up_mes($inform);
  echo '
  <br>
  <table width="550" border="0" cellspacing="0" cellpadding="0">
  <tr><td>
  <div class="mainbody">
  <div class="maintitle">Ping送信先の管理</div><br>
  <div class="linktitle">■ Ping送信先追加 ■</div>
  <table border="0" cellspacing="0" cellpadding="0" class="logbody">
  <form action="'.PHP_SELF.'?mode=ping&action=post" method=post>
	<tr><td>
  <table border="1" cellspacing="0" cellpadding="5" class="logbody">
    <tr><td width=100 align="center" bgcolor="#82BE7D"><label for=u_name>送信先名</label></td><td width="400"><input id=u_name tabIndex=1 name=u_name size=60 style="width:390px;"></td></tr>
    <tr><td width=100 align="center" bgcolor="#82BE7D"><label for=u_url>URL</label></td><td width="400"><input id=u_url tabIndex=2 name=u_url size=60 style="width:390px;"></td></tr>
  </table>
	</td></tr>
	<tr><td align="right">
  <input type="submit" value="追加する">
	</td></tr>
  </form>
	</table>
	<br>
  <div class="linktitle">■ 登録サイト ■</div>
  <table border="1" cellspacing="0" cellpadding="5" class="logbody">
  <tr align="center" bgcolor="#82BE7D"><td width="170">送信先名</td><td width="230">URL</td><td width="50">変更</td><td width="50">削除</td></tr>
  ';
  if (file_exists(LOGDIR."ping.dat")){
    $ping = file(LOGDIR."ping.dat");
    //$pingから改行コード削除
		if (count($ping) == 0) {
			echo '<tr><td colspan="4" align="center">Ping送信先は登録されていません。</td></tr>';
		}else{
			for ( $i = 0; $i < count( $ping ); $i++ ) {
				$ping[$i] = ereg_replace( "\n$", "", $ping[$i] );
				$ping[$i] = ereg_replace( "\r$", "", $ping[$i] );
				list($u_name, $u_url) = explode("<>", $ping[$i]);
				echo '
				<form action="'.PHP_SELF.'?mode=ping&action=edit&uid='.$i.'" method=post>
				<tr><td><input name=u_name size="22" value="'.$u_name.'" style="width:160px;"></td><td><input name=u_url size="30" value="'.$u_url.'" style="width:220px;"></td><td class="entry_body" align ="center"><input type="image" src="./images/update.gif" name="submit" alt="更新"></td><td align ="center"><a href="'.PHP_SELF.'?mode=ping&action=delete&uid='.$i.'"><img src="./images/trash.gif" border="0"></a></td></tr>
				</form>
				';
			}
		}
	}
	echo '
	</table>
	</div>
	</td></tr>
	</table>
	</td>
	';
}


/* カテゴリ管理画面表示 */
function category($action, $cid, $c_name, $pos, $updown){
	//------------------------------------------------------------------
	if ($action == "post") {
		if (file_exists(LOGDIR."category.dat")){
			$oldcategory = file(LOGDIR."category.dat");
			$cid = count($oldcategory);
		}else{
			$oldcategory = "";
			$cid = 0;
		}
		//テキスト整形
		$c_name = CleanStr($c_name);
		$newcategory = $cid."<>".$c_name."\r\n";
		//ログファイル書き込み
		$fp = fopen(LOGDIR."category.dat", "w");
		flock($fp, LOCK_EX);
		if ($oldcategory != 0) fputs($fp, implode('', $oldcategory));
		fputs($fp, $newcategory);
		fclose($fp);
		$inform = "カテゴリを追加しました。";
	//------------------------------------------------------------------
	}elseif ($action == "edit") {
		$category = file(LOGDIR."category.dat");
		//テキスト整形
		$c_name = CleanStr($c_name);

		for ( $i = 0; $i < count( $category ); $i++ ) {
			//$categoryから改行コード削除
			$tmp = ereg_replace( "\n$", "", $category[$i] );
			$tmp = ereg_replace( "\r$", "", $tmp);
			list($old_cid, $old_name) = explode("<>", $tmp);
			if ($cid == $old_cid) {
				$category[$i] = $cid."<>".$c_name."\r\n";
				break;
			}
		}
		//ログファイル書き込み
		$fp = fopen(LOGDIR."category.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $category));
		fclose($fp);
		$inform = "カテゴリを更新しました。";
	//------------------------------------------------------------------
	}elseif ($action == "change") {
		$category = file(LOGDIR."category.dat");
		if ($updown == "up") {
			$tmpcategory1 = $category[$pos];
			$tmpcategory2 = $category[$pos - 1];
			$category[$pos] = $tmpcategory2;
			$category[$pos - 1] = $tmpcategory1;
		}else{
			$tmpcategory1 = $category[$pos];
			$tmpcategory2 = $category[$pos + 1];
			$category[$pos] = $tmpcategory2;
			$category[$pos + 1] = $tmpcategory1;
		}
		$fp = fopen(LOGDIR."category.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $category));
		fclose($fp);
		$inform = "カテゴリの順番を変更しました。";
	//------------------------------------------------------------------
	}else{
		$inform = "";
	}
	up_mes($inform);
	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">カテゴリの管理</div><br>
	<div class="linktitle">■ カテゴリ追加 ■</div>
	<table border="0" cellspacing="0" cellpadding="0" class="logbody">
	<tr><td>
	<form action="'.PHP_SELF.'?mode=category&action=post" method=post>
	<table border="1" cellspacing="0" cellpadding="5" class="logbody">
	<tr class="entry_body"><td width="100" align="center" bgcolor="#82BE7D"><label for=c_name>カテゴリ名</label></td><td width="400"><input id=c_name tabIndex=1 name=c_name size=50 style="width:390px;"></td></tr>
	</table>
	</td></tr>
	<tr><td align="right">
	<input type="submit" value="追加する">
	</td></tr>
	</form>
	</table>
	<br>
  <div class="linktitle">■ 登録カテゴリ ■</div>
  <table border="1" cellspacing="0" cellpadding="5" class="logbody">
  <tr align="center" bgcolor="#82BE7D" class="entry_body"><td>カテゴリ名</td><td width="50">変更</td><td colspan="2">並び替え</td></tr>
  ';
	if (file_exists(LOGDIR."category.dat")){
		$category = file(LOGDIR."category.dat");
		if (count($category) == 0) {
			echo '<tr class="entry_body"><td colspan="4" align="center">登録されているカテゴリーありません。</td></tr>';
		}else{
			for ( $i = 0; $i < count( $category ); $i++ ) {
				//$linkから改行コード削除
				$category[$i] = ereg_replace( "\n$", "", $category[$i] );
				$category[$i] = ereg_replace( "\r$", "", $category[$i] );
				list($cid, $c_name) = explode("<>", $category[$i]);
				echo '
				<form action="'.PHP_SELF.'?mode=category&action=edit&cid='.$cid.'" method=post>
				<tr class="entry_body"><td width="400"><input id=c_name name=c_name size=50 value="'.$c_name.'" style="width:390px;"></td><td width="50" align ="center"><input type="image" src="./images/update.gif" name="submit" alt="更新"></td>';
				if ($i == 0 && $i == count($category) - 1) {
					echo '<td align="center" width="25">×</td><td align="center" width="25">×</td>';
				}elseif ($i == 0) {
					echo '<td align="center" width="25">×</td><td align="center" width="25"><a href="'.PHP_SELF.'?mode=category&action=change&pos='.$i.'&updown=down"><img src="./images/down.gif" width="20" height="20" border="0"></a></td>';
				}elseif ($i == count($category) - 1) {
					echo '<td align="center" width="25"><a href="'.PHP_SELF.'?mode=category&action=change&pos='.$i.'&updown=up"><img src="./images/up.gif" width="20" height="20" border="0"></a></td><td align="center" width="25">×</td>';
				}else{
					echo '<td align="center" width="25"><a href="'.PHP_SELF.'?mode=category&action=change&pos='.$i.'&updown=up"><img src="./images/up.gif" width="20" height="20" border="0"></a></td><td align="center" width="25"><a href="'.PHP_SELF.'?mode=category&action=change&pos='.$i.'&updown=down"><img src="./images/down.gif" width="20" height="20" border="0"></a></td>';
				}
				echo '</tr></form>';
			}
		}
	}
	echo '
	</table>
	<div class="mainstate">
	※カテゴリ自体を削除することは出来ません。<br>
	※カテゴリを削除したい場合はカテゴリ名を - として更新してください。
	</div>
	</div>
	</td></tr>
	</table>
	</td>
	';
}


/* ----- 記事の入力・更新 ----- */
function blog($action, $newpost, $d_eid, $d_year, $d_month, $d_day, $d_hour, $d_minutes, $d_second, $d_cid, $pid, $d_title, $d_mes, $d_more, $cok, $tok, $d_trackback, $upfile, $upfile_name, $ping_url) {
	$inform = "";
// -------------------------------------------------------------------
	if ($action == "post") {
		$d_date = sprintf("%4d%02d%02d", $d_year, $d_month, $d_day);
		$d_time = sprintf("%02d%02d%02d", $d_hour, $d_minutes, $d_second);
		// 日付チェック
		if (!checkdate($d_month, $d_day, $d_year)) {
			$inform = "年月日入力ミス。<br>投稿年月日を確認してください。";
		}else{
			//eidチェック
			$oldid = file(LOGDIR."id.dat");
			$oldid[0] = ereg_replace( "\n$", "", $oldid[0] );
			$oldid[0] = ereg_replace( "\r$", "", $oldid[0] );
			$oldid[0]++;
			if ($d_eid != $oldid[0]) $d_eid = $oldid[0];
			// プロフィールID設定
			$pid = 0;
			//テキスト整形
			$d_title  = CleanStr($d_title);
			$d_mes  = CleanStr($d_mes);
			$d_more  = CleanStr($d_more);
			// 改行文字の統一。 
			$d_mes = rntobr($d_mes);
			$d_mes = str_replace( "&nbsp;", " ", $d_mes);		// &nbsp をスペースに変換する
			$d_more = rntobr($d_more);
			$d_more = str_replace( "&nbsp;", " ", $d_more);		// &nbsp をスペースに変換する
			$log = $d_eid."<>".$d_date."<>".$d_time."<>".$d_cid."<>".$pid."<>".$d_title."<>".$d_mes."<>".$d_more."<>".$cok."<>".$tok."\r\n";

			//ログファイル書き込み
			$logname = "log".substr($d_date, 0, 6).".dat";
			$err = FileSearch($logname);
			if ($err == -1) {
				$inform = "記事の書き込みに失敗しました。<br>".LOGDIR."ディレクトリのパーミッションを確認してください。";
			}elseif ($err == 0) {
				$inform = "このサーバーはセーフモードです。<br>".LOGDIR."以下に年ディレクトリを手動で作成してください。";
			}elseif ($err == 1) {
				if ($newlog = @file(LOGDIR.substr($d_date, 0, 4)."/".$logname)) {
					$datetime = $d_date.$d_time;
					$newkey = DateSearch($datetime, $newlog);
					array_splice($newlog, $newkey, 0, $log);
					$fp = fopen(LOGDIR.substr($d_date, 0, 4)."/".$logname, "w");
					flock($fp, LOCK_EX);
					fputs($fp, implode('',$newlog));
					fclose($fp);
				}else{
					$fp = fopen(LOGDIR.substr($d_date, 0, 4)."/".$logname, "w");
					flock($fp, LOCK_EX);
					fputs($fp, $log);
					fclose($fp);
				}
				$inform .= "記事を投稿しました。";

				//eid保存
				if ($fp = fopen(LOGDIR."id.dat", "w")) {
					flock($fp, LOCK_EX);
					fputs($fp, $d_eid);
					fclose($fp);
				}else{
					$inform .= "<br>IDを更新出来ませんでした。";
				}
				//trackback送信
				if (strlen($d_trackback) != 0) {
					$tb = trackback_send($d_trackback,HOMELINK."?eid=".$d_eid, $d_title, $d_mes);
					$inform .= $tb;
				}
				//ping送信
				if (count($ping_url) != 0) {
					$pu = ping_send($ping_url);
					$inform .= $pu;
				}
			}
		}
		//$mesから<br>を改行コードに置換
		$d_mes = str_replace("<br />", "\n", $d_mes);
		$d_mes = str_replace("<br>", "\n", $d_mes);
		$d_more = str_replace("<br />", "\n", $d_more);	// <----- v1.3.1 追加
		$d_more = str_replace("<br>", "\n", $d_more);		// <----- v1.3.1 追加
// -------------------------------------------------------------------
	}elseif ($action == "edit") {
		// ログデータ読み込み
		$logfile = IDCheck($d_eid, 0);
		$log = file(LOGDIR.substr($logfile[0], 3, 4)."/".$logfile[0]);
		$tmplog = ereg_replace( "\n$", "", $log[IDSearch($d_eid, $log)] );
		$tmplog = ereg_replace( "\r$", "", $tmplog );
		list($d_eid, $d_date, $d_time, $d_cid, $pid, $d_title, $d_mes, $d_more, $cok, $tok) = explode("<>", $tmplog);

		//$mesから<br>を改行コードに置換
		$d_mes = str_replace("<br />", "\n", $d_mes);
		$d_mes = str_replace("<br>", "\n", $d_mes);
		$d_more = str_replace("<br />", "\n", $d_more);	// <----- v1.3.1 追加
		$d_more = str_replace("<br>", "\n", $d_more);		// <----- v1.3.1 追加

// -------------------------------------------------------------------
	}elseif ($action == "update") {
		$d_date = sprintf("%4d%02d%02d", $d_year, $d_month, $d_day);
		$d_time = sprintf("%02d%02d%02d", $d_hour, $d_minutes, $d_second);
		// 日付設定
		if (!checkdate($d_month, $d_day, $d_year)) {
			$inform = "年月日入力ミス。<br>投稿年月日を確認してください。";
		}else{
			// プロフィールID設定
			$pid = 0;
			// $eid設定
			$logfile = IDCheck($d_eid, 0);
			$oldlog = file(LOGDIR.substr($logfile[0], 3, 4)."/".$logfile[0]);
			$key = IDSearch($d_eid, $oldlog);

			//テキスト整形
			$d_title  = CleanStr($d_title);
			$d_mes  = CleanStr($d_mes);
			$d_more  = CleanStr($d_more);
			// 改行文字の統一。 
			$d_mes = rntobr($d_mes);
			$d_mes = str_replace( "&nbsp;", " ", $d_mes);		// &nbsp をスペースに変換する
			$d_more = rntobr($d_more);
			$d_more = str_replace( "&nbsp;", " ", $d_more);		// &nbsp をスペースに変換する
			$finfo = pathinfo($upfile_name);
			$log = $d_eid."<>".$d_date."<>".$d_time."<>".$d_cid."<>".$pid."<>".$d_title."<>".$d_mes."<>".$d_more."<>".$cok."<>".$tok."\r\n";

			list($old_eid, $old_date, $old_time) = explode("<>", $oldlog[$key], 4);
			$logname = "log".substr($d_date, 0, 6).".dat";
			$oldlogname = "log".substr($old_date, 0, 6).".dat";

			if (substr($d_date, 0, 6) != substr($old_date, 0, 6)) {
				$err = FileSearch($logname);
				if ($err == -1) {
					$inform = "記事の書き込みに失敗しました。<br>".LOGDIR."ディレクトリのパーミッションを確認してください。";
				}elseif ($err == 0) {
					$inform = "このサーバーはセーフモードです。<br>".LOGDIR."以下に年ディレクトリを手動で作成してください。";
				}elseif ($err == 1) {
					if ($newlog = @file(LOGDIR.substr($d_date, 0, 4)."/".$logname)) {
						$datetime = $d_date.$d_time;
						$newkey = DateSearch($datetime, $newlog);
						array_splice($newlog, $newkey, 0, $log);
						$fp = fopen(LOGDIR.substr($d_date, 0, 4)."/".$logname, "w");
						flock($fp, LOCK_EX);
						fputs($fp, implode('',$newlog));
						fclose($fp);
					}else{
						$fp = fopen(LOGDIR.substr($d_date, 0, 4)."/".$logname, "w");
						flock($fp, LOCK_EX);
						fputs($fp, $log);
						fclose($fp);
					}
					array_splice($oldlog, $key, 1);
					$fp = fopen(LOGDIR.substr($old_date, 0, 4)."/".$oldlogname, "w");
					flock($fp, LOCK_EX);
					fputs($fp, implode('', $oldlog));
					fclose($fp);
					$inform .= "記事を更新しました。";
				}
			}else{
				if ($d_date == $old_date && $d_time == $old_time) {
					$oldlog[$key] = $log;
				}else{
					array_splice($oldlog, $key, 1);
					$datetime = $d_date.$d_time;
					$newkey = DateSearch($datetime, $oldlog);
					if ($newkey == -1) {
						$oldlog[0] = $log;
					}else{
						array_splice($oldlog, $newkey, 0, $log);
					}
				}
				//ログファイル書き込み
				//投稿日時が変更されていない、または同一年月の場合更新
				//それ以外の場合は変更前データを削除
				$fp = fopen(LOGDIR.substr($old_date, 0, 4)."/".$oldlogname, "w");
				flock($fp, LOCK_EX);
				fputs($fp, implode('', $oldlog));
				fclose($fp);
				$inform .= "記事を更新しました。";
			}
			//trackback送信
			if (strlen($d_trackback) != 0) {
				$tb = trackback_send($d_trackback,HOMELINK."?eid=".$d_eid, $d_title, $d_mes);
				$inform .= $tb;
			}
			//ping送信
			if (count($ping_url) != 0) {
				$pu = ping_send($ping_url);
				$inform .= $pu;
			}
		}
		//$mesから<br>を改行コードに置換
		$d_mes = str_replace("<br />", "\n", $d_mes);
		$d_mes = str_replace("<br>", "\n", $d_mes);
		$d_more = str_replace("<br />", "\n", $d_more);	// <----- v1.3.1 追加
		$d_more = str_replace("<br>", "\n", $d_more);		// <----- v1.3.1 追加
// -------------------------------------------------------------------
	}elseif ($action == "preview") {
		$d_mes  = CleanStr($d_mes);
		$d_more  = CleanStr($d_more);
		// 改行文字の統一。 
		$d_mes = str_replace( "&nbsp;", " ", $d_mes);		// &nbsp をスペースに変換する
		$d_more = str_replace( "&nbsp;", " ", $d_more);		// &nbsp をスペースに変換する
		$d_date = sprintf("%4d%02d%02d", $d_year, $d_month, $d_day);
		$d_time = sprintf("%02d%02d%02d", $d_hour, $d_minutes, $d_second);
// -------------------------------------------------------------------
	}else{
		//eidチェック
		$oldid = file(LOGDIR."id.dat");
		$oldid[0] = ereg_replace( "\n$", "", $oldid[0] );
		$oldid[0] = ereg_replace( "\r$", "", $oldid[0] );
		$oldid[0]++;
		$d_eid = $oldid[0];


		$d_cid = "-1";
		$d_title = "";
		$d_mes = "";
		$d_more = "";		// <----- v1.3.1 追加
		$cok = "0";
		$tok = "0";
	}
	up_mes($inform);
	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">';
	if ($action == "edit" || $action == "update" || $action == "post" || $action == "preview") {
		$pre_mes = IconStr($d_mes);
		$pre_mes = tagreplaceStr($pre_mes);
		$pre_mes = rntobr($pre_mes);
		$pre_more = IconStr($d_more);
		$pre_more = tagreplaceStr($pre_more);
		$pre_more = rntobr($pre_more);
		echo '<br>
		<div class="maintitle">記事のプレビュー</div>
		<br>
		<div id="auto" style="width: 530px; height: 200px; background: #dcdcdc; padding: 5px; border: solid 1px #000000;">
		<div class="logtitle">'.$d_title.'</div>
		<br>
		<div class="logbody">'.$pre_mes.'</div>
		<hr>
		<div class="logbody">'.$pre_more.'</div>
		</div>
		<br>
		';
	}

	if ($action == "edit" || $action == "update" || $action == "post" || $newpost == "old") {
		echo '
		<div class="maintitle">記事の編集  <span class="mainstate">記事ID:['.$d_eid.']</span></div><br>
		<table border="0" cellspacing="0" cellpadding="0">
		<form action="'.PHP_SELF.'?mode=log&action=update" enctype="multipart/form-data" method=post name=post>
		';
	}else{
		echo '
		<div class="maintitle">記事の新規投稿  <span class="mainstate">記事ID:['.$d_eid.']</span></div><br>
		<table border="0" cellspacing="0" cellpadding="0">
		<form action="'.PHP_SELF.'?mode=log&action=post" enctype="multipart/form-data" method=post name=post>
		';
	}
	echo '
	<tr><td valign="top" colspan="2" width="270">
	<div class="linktitle">■ 記事のタイトル ■</div>
	<div class="inputarea"><input name=d_title size=30 value="'.$d_title.'" style="width:300px"></div>
	<br>
	</td>
	<td>
</td>
</tr>
	<tr><td valign="top" width="100">
	<div class="linktitle">■ コメント ■</div>
	<div class="inputarea"><select name=cok>';
	if ($cok != "1") {
		echo '<option value="0" selected>受付を許可</option>
		<option value="1">受付を拒否</option>';
	}else{
		echo '<option value="0">受付を許可</option>
		<option value="1" selected>受付を拒否</option>';
	}
	echo '</select></div></td><td width="130">
	<div class="linktitle">■ トラックバック ■</div>
	<div class="inputarea"><select name=tok>';
	if ($tok != "1") {
		echo '<option value="0" selected>受付を許可</option>
		<option value="1">受付を拒否</option>';
	}else{
		echo '<option value="0">受付を許可</option>
		<option value="1" selected>受付を拒否</option>';
	}
	echo '</select></div></td><td>
	<div class="linktitle">■ カテゴリ ■</div>
	<div class="inputarea"><select name=d_cid>
	';
	if ($d_cid == "-1") {
		echo '<option value="-1" selected>指定無し</option>';
	}else{
		echo '<option value="-1">指定無し</option>';
	}
	if (file_exists(LOGDIR."category.dat")){
		$category = file(LOGDIR."category.dat");
		//$categoryから改行コード削除
		for ( $i = 0; $i < count( $category ); $i++ ) {
			$category[$i] = ereg_replace( "\n$", "", $category[$i] );
			$category[$i] = ereg_replace( "\r$", "", $category[$i] );
			list($c_cid, $c_name) = explode("<>", $category[$i]);
			if ($d_cid == $c_cid) {
				echo '<option value="'.$c_cid.'" selected>'.$c_name.'</option>';
			}else{
				echo '<option value="'.$c_cid.'">'.$c_name.'</option>';
			}
		}
	}
	echo '</select></div>
	</td>
	</tr>
	</table>
	<br>
	<table width="540" border="0" cellspacing="0" cellpadding="0">
	<tr><td valign="top">
	<div class="linktitle">■ 記事の内容 ■</div>
	<div id="MesIconOff" style="margin: 0px 20px 5px 0px;text-align:right;"><a href="javascript:;" onclick=iconview(0) style="font-size:12px;border:solid 1px;padding: 1px 5px 1px 5px;">絵文字表示</a> <a href="javascript:;" onclick=wopen_pict() style="font-size:12px;border:solid 1px;padding: 1px 5px 1px 5px;">画像追加挿入</a></div>
	<div id="MesIconOn" style="display:none;"><div style="margin: 0px 20px 5px 0px;text-align:right;"><a href="javascript:;" onclick=iconview(1) style="font-size:12px;border:solid 1px;padding: 1px 5px 1px 5px;">絵文字非表示</a> <a href="javascript:;" onclick=wopen_pict() style="font-size:12px;border:solid 1px;padding: 1px 5px 1px 5px;">画像追加挿入</a></div>
	<table border="1" cellspacing="0" cellpadding="2">
	<tr><td>
	<table border="0" cellspacing="0" cellpadding="2">
	<tr>
	';
	$icon = file(LOGDIR."icon.dat");
	for ($i = 0; $i < 100; $i++) {
		$icon[$i] = ereg_replace( "\n$", "", $icon[$i] );
		$icon[$i] = ereg_replace( "\r$", "", $icon[$i] );
		list($i_pic, $i_data) = explode("<>", $icon[$i]);
		$size = @getimagesize(ICONDIR.$i_pic);
		echo '<td><a href="javascript:;" onclick=icon('.$i.',0)><img src="'.ICONDIR.$i_pic.'" '.$size[3].' alt="'.$i_data.'" border="0"></a></td>';
		if ($i == 24 || $i == 49 || $i == 74) {
			echo '</tr><tr>';
		}
	}
	echo "
	</tr></table>
	</td><tr></table>
	</div>
	<table border=\"0\" cellspacing=\"1\" cellpadding=\"3\" class=\"inputarea\">
	<tr><td>| 文字サイズ:<select name=\"font_mes\" onChange=\"ins(this.form.font_mes.selectedIndex,0)\" class=\"btn_font\">
		<option value=\"7\">7 px</option>
		<option value=\"9\">9 px</option>
		<option value=\"12\"selected>12 px</option>
		<option value=\"18\">18 px</option>
		<option value=\"24\">24 px</option>
	</select>
	| <a href=\"javascript:;\" onclick=\"ins(5,0)\" class=\"link_tag\">太字</a>
	<a href=\"javascript:;\" onclick=\"ins(6,0)\" class=\"link_tag\">斜体</a>
	<a href=\"javascript:;\" onclick=\"ins(7,0)\" class=\"link_tag\">下線</a>
	<a href=\"javascript:;\" onclick=\"ins(8,0)\" class=\"link_tag\">取消線</a> |
	<a href=\"javascript:;\" onclick=\"ins(9,0)\" class=\"link_tag\")>左揃え</a>
	<a href=\"javascript:;\" onclick=\"ins(10,0)\" class=\"link_tag\")>中央揃え</a>
	<a href=\"javascript:;\" onclick=\"ins(11,0)\" class=\"link_tag\")>右揃え</a> |</td></tr>
	<tr><td>| 文字色:
	<a href=\"javascript:;\" onclick=\"ins(12,0)\" style=\"background-color:black;border:solid 1px black;\")><img src=\"./images/blank.gif\" width=\"10\" height=\"11\" border=\"0\"></a>
	<a href=\"javascript:;\" onclick=\"ins(13,0)\" style=\"background-color:brown;border:solid 1px black;\")><img src=\"./images/blank.gif\" width=\"10\" height=\"11\" border=\"0\"></a>
	<a href=\"javascript:;\" onclick=\"ins(14,0)\" style=\"background-color:red;border:solid 1px black;\"><img src=\"./images/blank.gif\" width=\"10\" height=\"11\" border=\"0\"></a>
	<a href=\"javascript:;\" onclick=\"ins(15,0)\" style=\"background-color:orange;border:solid 1px black;\"><img src=\"./images/blank.gif\" width=\"10\" height=\"11\" border=\"0\"></a>
	<a href=\"javascript:;\" onclick=\"ins(16,0)\" style=\"background-color:yellow;border:solid 1px black;\"><img src=\"./images/blank.gif\" width=\"10\" height=\"11\" border=\"0\"></a>
	<a href=\"javascript:;\" onclick=\"ins(17,0)\" style=\"background-color:green;border:solid 1px black;\"><img src=\"./images/blank.gif\" width=\"10\" height=\"11\" border=\"0\"></a>
	<a href=\"javascript:;\" onclick=\"ins(18,0)\" style=\"background-color:blue;border:solid 1px black;\"><img src=\"./images/blank.gif\" width=\"10\" height=\"11\" border=\"0\"></a>
	<a href=\"javascript:;\" onclick=\"ins(19,0)\" style=\"background-color:violet;border:solid 1px black;\"><img src=\"./images/blank.gif\" width=\"10\" height=\"11\" border=\"0\"></a>
	<a href=\"javascript:;\" onclick=\"ins(20,0)\" style=\"background-color:gray;border:solid 1px black;\"><img src=\"./images/blank.gif\" width=\"10\" height=\"11\" border=\"0\"></a>
	<a href=\"javascript:;\" onclick=\"ins(21,0)\" style=\"background-color:white;border:solid 1px black;\"><img src=\"./images/blank.gif\" width=\"10\" height=\"11\" border=\"0\"></a> | 
	<a href=\"javascript:;\" onclick=\"linkins(0,0)\" class=\"link_tag\">リンク</a>
	<a href=\"javascript:;\" onclick=\"linkins(1,0)\" class=\"link_tag\">メール</a> |
	<a href=\"javascript:;\" onclick=\"ins(22,0)\" class=\"link_tag\">段落</a>
	<a href=\"javascript:;\" onclick=\"ins(23,0)\" class=\"link_tag\">引用文</a> |</td></tr>
	</table>
	<table border=\"0\" cellspacing=\"1\" cellpadding=\"3\" class=\"inputarea\">
	<tr><td>
	<textarea style=\"WIDTH: 520px;font-size:12px;\" id=\"d_mes\" name=\"d_mes\" rows=\"10\" cols=\"50\" wrap=\"virtual\">".tagreplaceStr($d_mes)."</textarea><br><br>
	</td></tr>
	</table>";
	if ($action == "edit" || $action == "update" || $action == "post" || $newpost == "old") {
		echo '<div align="center"><input type=submit name="preview" value="プレビュー"><input type=submit name="post" value="更新する"></div>';
	}else{
		echo '<div align="center"><input type=submit name="preview" value="プレビュー"><input type=submit name="post" value="投稿する"></div>';
	}

	echo '
	<div class="linktitle">■ 記事の続き ■</div>
	<div id="MoreIconOn" style="display:none;">
	<table border="1" cellspacing="0" cellpadding="2">
	<tr><td>
	<table border="0" cellspacing="0" cellpadding="2">
	<tr>';
	$icon = file(LOGDIR."icon.dat");
	for ($i = 0; $i < 100; $i++) {
		$icon[$i] = ereg_replace( "\n$", "", $icon[$i] );
		$icon[$i] = ereg_replace( "\r$", "", $icon[$i] );
		list($i_pic, $i_data) = explode("<>", $icon[$i]);
		$size = @getimagesize(ICONDIR.$i_pic);
		echo '<td><a href="javascript:;" onclick=icon('.$i.',1)><img src="'.ICONDIR.$i_pic.'" '.$size[3].' alt="'.$i_data.'" border="0"></a></td>';
		if ($i == 24 || $i == 49 || $i == 74) {
			echo '</tr><tr>';
		}
	}
	echo '</tr></table>
	</td><tr></table>
	</div>
	<table border="0" cellspacing="1" cellpadding="3" class="inputarea">
	<tr><td>| 文字サイズ:<select name="font_more" onChange=ins(this.form.font_more.selectedIndex,1) class="btn_font">
		<option value="7">7 px</option>
		<option value="9">9 px</option>
		<option value="12"selected>12 px</option>
		<option value="18">18 px</option>
		<option value="24">24 px</option>
	</select>
	| <a href="javascript:;" onclick=ins(5,1) class="link_tag">太字</a>
	<a href="javascript:;" onclick=ins(6,1) class="link_tag">斜体</a>
	<a href="javascript:;" onclick=ins(7,1) class="link_tag">下線</a>
	<a href="javascript:;" onclick=ins(8,1) class="link_tag">取消線</a> |
	<a href="javascript:;" onclick=ins(9,1) class="link_tag")>左揃え</a>
	<a href="javascript:;" onclick=ins(10,1) class="link_tag")>中央揃え</a>
	<a href="javascript:;" onclick=ins(11,1) class="link_tag")>右揃え</a> |</td></tr>
	<tr><td>| 文字色:
	<a href="javascript:;" onclick=ins(12,1) style="background-color:black;border:solid 1px black;")><img src="./images/blank.gif" width="10" height="11" border="0"></a>
	<a href="javascript:;" onclick=ins(13,1) style="background-color:brown;border:solid 1px black;")><img src="./images/blank.gif" width="10" height="11" border="0"></a>
	<a href="javascript:;" onclick=ins(14,1) style="background-color:red;border:solid 1px black;"><img src="./images/blank.gif" width="10" height="11" border="0"></a>
	<a href="javascript:;" onclick=ins(15,1) style="background-color:orange;border:solid 1px black;"><img src="./images/blank.gif" width="10" height="11" border="0"></a>
	<a href="javascript:;" onclick=ins(16,1) style="background-color:yellow;border:solid 1px black;"><img src="./images/blank.gif" width="10" height="11" border="0"></a>
	<a href="javascript:;" onclick=ins(17,1) style="background-color:green;border:solid 1px black;"><img src="./images/blank.gif" width="10" height="11" border="0"></a>
	<a href="javascript:;" onclick=ins(18,1) style="background-color:blue;border:solid 1px black;"><img src="./images/blank.gif" width="10" height="11" border="0"></a>
	<a href="javascript:;" onclick=ins(19,1) style="background-color:violet;border:solid 1px black;"><img src="./images/blank.gif" width="10" height="11" border="0"></a>
	<a href="javascript:;" onclick=ins(20,1) style="background-color:gray;border:solid 1px black;"><img src="./images/blank.gif" width="10" height="11" border="0"></a>
	<a href="javascript:;" onclick=ins(21,1) style="background-color:white;border:solid 1px black;"><img src="./images/blank.gif" width="10" height="11" border="0"></a> | 
	<a href="javascript:;" onclick=linkins(0,1) class="link_tag">リンク</a>
	<a href="javascript:;" onclick=linkins(1,1) class="link_tag">メール</a> |
	<a href="javascript:;" onclick=ins(22,1) class="link_tag">段落</a>
	<a href="javascript:;" onclick=ins(23,1) class="link_tag">引用文</a> |</td></tr>
	</table>
	<table border="0" cellspacing="1" cellpadding="3" class="inputarea">
	<tr><td>
	<textarea style="WIDTH: 520px;font-size:12px;" id="d_more" name="d_more" rows="10" cols="50" wrap="virtual">'.tagreplaceStr($d_more).'</textarea><br><br>
	</td></tr>
	</table>
	';

	if ($action == "edit" || $action == "update" || $action == "post" || $action == "preview") {
		$d_year =  substr($d_date, 0, 4);
		$d_month = substr($d_date, 4, 2);
		$d_day = substr($d_date, 6, 2);
		$d_hour = substr($d_time, 0, 2);
		$d_minutes = substr($d_time, 2, 2);
		$d_second = substr($d_time, 4, 2);
	}else{
		$d_year = gmdate ("Y", time()+TIMEZONE);
		$d_month = gmdate ("m", time()+TIMEZONE);
		$d_day = gmdate ("d", time()+TIMEZONE);
		$d_hour = gmdate ("H", time()+TIMEZONE);
		$d_minutes = gmdate ("i", time()+TIMEZONE);
		$d_second = gmdate ("s", time()+TIMEZONE);

	}
	$oldyear = 1970;
	$newyear = gmdate ("Y", time()+TIMEZONE) + 1;
	echo '
	</td>
	</tr>
	</table>
	<div class="linktitle">■ 記事の日付 ■</div>
	<div class="inputarea">
	<select name="d_year">';
	for ( $i = $newyear; $i >= $oldyear; $i-- ) {
		if ($d_year == $i) {
			echo '<option value="'.$i.'" selected>'.$i.'</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'</option>';
		}
	}
	echo '</select>';
	echo ' 年 ';
	echo '<select name="d_month">';
	for ( $i = 1; $i <= 12; $i++ ) {
		if ($d_month == $i) {
			echo '<option value="'.$i.'" selected>'.$i.'</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'</option>';
		}
	}
	echo '</select>';
	echo ' 月 ';
	echo '<select name="d_day">';
	for ( $i = 1; $i <= 31; $i++ ) {
		if ($d_day == $i) {
			echo '<option value="'.$i.'" selected>'.$i.'</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'</option>';
		}
	}
	echo '</select>';
	echo ' 日   ';
	echo '<select name="d_hour">';
	for ( $i = 0; $i <= 23; $i++ ) {
		if ($d_hour == $i) {
			echo '<option value="'.$i.'" selected>'.$i.'</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'</option>';
		}
	}
	echo '</select>';
	echo ' 時 ';
	echo '<select name="d_minutes">';
	for ( $i = 0; $i <= 59; $i++ ) {
		if ($d_minutes == $i) {
			echo '<option value="'.$i.'" selected>'.$i.'</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'</option>';
		}
	}
	echo '</select>';
	echo ' 分 ';
	echo '<select name="d_second">';
	for ( $i = 0; $i <= 59; $i++ ) {
		if ($d_second == $i) {
			echo '<option value="'.$i.'" selected>'.$i.'</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'</option>';
		}
	}
	echo '</select>';
	echo ' 秒 ';


	echo '<input type="button" name="datenow" value="現在時刻を取得" onclick="dnow()">';


	echo '</div>
	<br><br>
	<div class="linktitle">■ トラックバックを送信する ■</div>
	<textarea style="WIDTH: 500px;font-size:12px;" name=d_trackback rows=4 cols=50>'.$d_trackback.'</textarea><br><br>
	<div class="linktitle">■ 更新Pingを送信する ■</div>
	<div class="inputarea">';
	if (file_exists(LOGDIR."ping.dat")){
		$ping = file(LOGDIR."ping.dat");
		if (count($ping) == 0) {
			echo '※ Ping送信先は登録されていません。<br>';
		}else{
			for ( $i = 0; $i < count( $ping ); $i++ ) {
				//$pingから改行コード削除
				$ping[$i] = ereg_replace( "\n$", "", $ping[$i] );
				$ping[$i] = ereg_replace( "\r$", "", $ping[$i] );
				list($u_name, $u_url) = explode("<>", $ping[$i]);
				echo '<input type="checkbox" name="ping_url[]" value="'.$u_url.'" id="p'.$i.'">';
				echo '<label for="p'.$i.'">'.$u_name.'</label><br>';
			}
		}
	}
	echo '<br>';
	if ($action == "edit" || $action == "update" || $action == "post" || $newpost == "old") {
		echo '<INPUT type="hidden" value="old" name=newpost><INPUT type="hidden" value="'.$d_eid.'" name=d_eid><div align="center"><input type=submit name="preview" value="プレビュー"><input type=submit name="post" value="更新する"></div>';
	}else{
		echo '<INPUT type="hidden" value="new" name=newpost><INPUT type="hidden" value="'.$d_eid.'" name=d_eid><div align="center"><input type=submit name="preview" value="プレビュー"><input type=submit name="post" value="投稿する"></div>';
	}
	echo '
	</form>
	</div></div>
	</td></tr>
	</table>
	</td>
	';
}


/* 記事の修正削除画面 */
function mes_list($action, $page, $comment_eid, $trackback_eid, $qry_eid, $qry_coid, $qry_toid) {
	if ($action == "cm_delete") {
		// ----- コメントの削除処理
		$cmtlist = IDCheck($qry_eid, 1);
		@sort($cmtlist);
		$cnt = 0;
		for ($i = 0; $i < count($cmtlist); $i++ ) {
			$cmt = file(LOGDIR.substr($cmtlist[$i], 3, 4)."/".$cmtlist[$i]);
			$cmt = array_reverse($cmt);
			while (list($cmtkey, $cmtval) = each($cmt)) {
				$cmtval = ereg_replace( "\n$", "", $cmtval );
				$cmtval = ereg_replace( "\r$", "", $cmtval );
				list($c_eid) = explode("<>", $cmtval, 2);
				if ($c_eid == $qry_eid) {
					if ($qry_coid == $cnt) {
						array_splice($cmt, $cmtkey, 1);
						$cmt = array_reverse($cmt);
						$fp = fopen(LOGDIR.substr($cmtlist[$i], 3, 4)."/".$cmtlist[$i], "w");
						flock($fp, LOCK_EX);
						fputs($fp, implode('', $cmt));
						fclose($fp);
						$comment_eid = $qry_eid;
						$link_url = PHP_SELF.'?mode=list&page='.$page.'&comment='.$comment_eid.'#comments';
						echo '<html><head><META HTTP-EQUIV="refresh"content="1;URL='.$link_url.'"></head>
						<body><div style="text-align:center;margin-top:10px;">コメントを削除しました。<br>
						画面が自動的に切り替わらない場合は<br>
						<a href="'.$link_url.'">こちら</a>をクリックしてください。</div></body></html>';
						exit;
					}
					$cnt++;
				}
			}
		}
	}elseif ($action == "tb_delete") {
		// ----- トラックバックの削除
		$trklist = IDCheck($qry_eid, 2);
		@sort($trklist);
		$cnt = 0;
		for ($i = 0; $i < count($trklist); $i++ ) {
			$trk = file(LOGDIR.substr($trklist[$i], 3, 4)."/".$trklist[$i]);
			$trk = array_reverse($trk);
			while (list($trkkey, $trkval) = each($trk)) {
				$trkval = ereg_replace( "\n$", "", $trkval );
				$trkval = ereg_replace( "\r$", "", $trkval );
				list($t_eid) = explode("<>", $trkval, 2);
				if ($t_eid == $qry_eid) {
					if ($qry_toid == $cnt) {
						array_splice($trk, $trkkey, 1);
						$trk = array_reverse($trk);
						$fp = fopen(LOGDIR.substr($trklist[$i], 3, 4)."/".$trklist[$i], "w");
						flock($fp, LOCK_EX);
						fputs($fp, implode('', $trk));
						fclose($fp);
						$trackback_eid = $qry_eid;
						$link_url = PHP_SELF.'?mode=list&page='.$page.'&trackback='.$trackback_eid.'#trackback';
						echo '<html><head><META HTTP-EQUIV="refresh"content="1;URL='.$link_url.'"></head>
						<body><div style="text-align:center;margin-top:10px;">トラックバックを削除しました。<br>
						画面が自動的に切り替わらない場合は<br>
						<a href="'.$link_url.'">こちら</a>をクリックしてください。</div></body></html>';
						exit;
					}
					$cnt++;
				}
			}
		}
	}elseif ($action == "delete") {
		// ----- 親記事の削除
		if ($loglist = IDCheck($qry_eid,0)) {
			$log = file(LOGDIR.substr($loglist[0],3,4)."/".$loglist[0]);
			$key = IDSearch($qry_eid, $log);
			array_splice($log, $key, 1);
			//ログファイル書き込み
			$fp = fopen(LOGDIR.substr($loglist[0],3,4)."/".$loglist[0], "w");
			flock($fp, LOCK_EX);
			fputs($fp, implode('', $log));
			fclose($fp);
		}
		// ----- 指定IDのコメント一括削除
		if($cmtlist = IDCheck($qry_eid, 1)) {
			for ($i = 0; $i < count($cmtlist); $i++ ) {
				$cmt = file(LOGDIR.substr($cmtlist[$i], 3, 4)."/".$cmtlist[$i]);
				$cnt = 0;
				$newcomment = array();
				while (list($cmtkey, $cmtval) = each($cmt)) {
					list($c_eid) = explode("<>", $cmtval, 2);
					if ($c_eid != $qry_eid) {
						$newcomment[$cnt] = $cmtval;
						$cnt++;
					}
				}
				//コメントファイル書き込み
				$fp = fopen(LOGDIR.substr($cmtlist[$i], 3, 4)."/".$cmtlist[$i], "w");
				flock($fp, LOCK_EX);
				fputs($fp, implode('', $newcomment));
				fclose($fp);
			}
		}
		// ----- 指定IDのトラックバック一括削除
		if ($trklist = IDCheck($qry_eid, 2)) {
			for ($i = 0; $i < count($trklist); $i++ ) {
				$trk = file(LOGDIR.substr($trklist[$i], 3, 4)."/".$trklist[$i]);
				$cnt = 0;
				$newtrackback = array();
				while (list($trkkey, $trkval) = each($trk)) {
					list($t_eid) = explode("<>", $trkval, 2);
					if ($t_eid != $qry_eid) {
						$newtrackback[$cnt] = $trkval;
						$cnt++;
					}
				}
				//コメントファイル書き込み
				$fp = fopen(LOGDIR.substr($trklist[$i], 3, 4)."/".$trklist[$i], "w");
				flock($fp, LOCK_EX);
				fputs($fp, implode('', $newtrackback));
				fclose($fp);
			}
		}
		if (!$page) $page = 0;
		$link_url = PHP_SELF.'?mode=list&page='.$page;
		echo '<html><head><META HTTP-EQUIV="refresh"content="1;URL='.$link_url.'"></head>
		<body><div style="text-align:center;margin-top:10px;">記事を削除しました。<br>
		画面が自動的に切り替わらない場合は<br>
		<a href="'.$link_url.'">こちら</a>をクリックしてください。</div></body></html>';
		exit;
	}else{
		$inform = "";
	}

	headhtml(2);
	contentshtml();

	up_mes($inform);
	if ($page == "") $page = 0;

	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">記事の編集・削除</div><br>';
	
	if ($loglist = LogFileList(0)){
		echo '
		<div class="linktitle">■ 記事一覧 ■</div>
		<table width="500" border="1" cellspacing="0" cellpadding="0" class="logbody">
		<tr align="center" bgcolor="#82BE7D"><td width="50" rowspan="2">削除</td><td colspan="2">タイトル</td><td colspan="2">カテゴリ</td><td rowspan="2">編集</td></tr>
		<tr align="center" bgcolor="#82BE7D"><td width="50">ID</td><td>日付</td><td width="50">コメ</td><td width="50">トラ</td></tr>';

		$category = file(LOGDIR."category.dat");
		for ( $i = 0; $i < count( $category ); $i++ ) {
			//$categoryから改行コード削除
			$category[$i] = ereg_replace( "\n$", "", $category[$i] );
			$category[$i] = ereg_replace( "\r$", "", $category[$i]);
		}

		//$pageから表示開始位置を算出
		$pcnt = $page * 10;	//10件で1ページ表示
		$page_st = 10 * $page + 1;
		$page_ed = $page_st + 10;
		$i = 1;
		$loopcnt = 0;
		$col = 0;
		$exitkey = 0;
		while(list ($key, $val) = each($loglist)) {
			$log = file(LOGDIR.substr($val,3,4)."/".$val);
			if ($exitkey == 1) break;
			while (list($logkey, $logval) = each($log)) {
				if ($page_st <= $i && $page_ed > $i) {
					$logval = ereg_replace( "\n$", "", $logval );
					$logval = ereg_replace( "\r$", "", $logval );
					list($eid, $d_date, $d_time, $cid, $pid, $d_title, , , $d_cok, $d_tok) = explode("<>", $logval);

					$comment_cnt = CommentCount($eid);

					$trackback_cnt = TrackbackCount($eid);

					$c_name = "-";
					for ( $j = 0; $j < count( $category ); $j++ ) {
						list($tmp_cid, $tmp_name) = explode("<>", $category[$j]);
						if ($cid == $tmp_cid) {
							$c_name = $tmp_name;
							break;
						}
					}

					echo '<form action="'.PHP_SELF.'?mode=log&action=edit" method="post">';
					echo '<INPUT type="hidden" value="'.$eid.'" name="d_eid">';
					if ($col == 0){
						echo '<tr bgcolor="#F5F5F5">';
					}else{
						echo '<tr>';
					}
					if (trim($d_title) == "") {
						$d_title = "** 無題 **";
					}
					$delref = PHP_SELF.'?mode=list&action=delete&eid='.$eid;
					echo '<td align ="center" width="50" rowspan="2"><a href=Javascript:delcheck("'.$delref.'");><img src="./images/trash.gif" border="0"></a></td><td colspan="2"><a href="'.HOMELINK.'?eid='.$eid.'" target="_blank"><input type="text" value="'.$d_title.'" size="42" readonly="readonly" style="width:290px;cursor: pointer;"></a></td><td colspan="2" align="center">'.$c_name.'</td>';
					echo '<td align ="center" width="50" rowspan="2"><input type="image" src="./images/edit.gif" name="submit" alt="編集"></td></tr>';
					if ($col == 0){
						echo '<tr bgcolor="#F5F5F5">';
						$col = 1;
					}else{
						echo '<tr>';
						$col = 0;
					}
					echo '<td align="center" width="50">'.$eid.'</td><td align="center" width="250">'.date("Y/m/d h:i A", mktime(substr($d_time,0,2),substr($d_time,2,2),0,substr($d_date,4,2), substr($d_date,6,2), substr($d_date,0,4))).'</td>';
					if ($comment_cnt == 0) {
						echo '<td align="center" width="50">0</td>';
					}else{
						echo '<td align="center" width="50"><a href='.PHP_SELF.'?mode=list&page='.$page.'&comment='.$eid.'#comments>'.$comment_cnt.'</a></td>';
					}
					if ($trackback_cnt == 0) {
						echo '<td align="center" width="50">0</td>';
					}else{
						echo '<td align="center" width="50"><a href='.PHP_SELF.'?mode=list&page='.$page.'&trackback='.$eid.'#trackback>'.$trackback_cnt.'</a></td>';
					}
					echo '</tr>';
					echo '</form>';
					$loopcnt++;
					if ($loopcnt >= 10) {
						$exitkey = 1;
						break;
					}
				}
				$i++;
			}
		}
		echo '</table>';
	}else{
		echo '
		<div class="linktitle">■ 記事一覧 ■</div>
		<div class="logbody">記事はありません</div>
		';
	}

	$pcnt_start = $pcnt + 1;  		// 表示開始位置
	$pcnt_end = $pcnt + $loopcnt;		// 表示終了位置
	$bpage = $page - 1;			// 前ページ
	$npage = $page + 1;			// 次ページ
	if (!$logcnt = @array_sum(archive_count())) {
		$logcnt = 0;
		$mpage = 0;
	}else{
		$mpage = ceil($logcnt / 10);
	}
	// 前ページ・次ページ表示用リンク
	
	if ($logcnt != 0) {
		echo '<table width="500" border="0" cellspacing="0" cellpadding="0" class="logbody">';
		if ($page > 0 && $i < $logcnt) {
			echo '<tr><td align="left" width="100"><a href='.PHP_SELF.'?mode=list&page='.$bpage.'>&lt;&lt;前のページ</a></td><td align="center">全 '.$logcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><a href='.PHP_SELF.'?mode=list&page='.$npage.'>次のページ&gt;&gt;</a></td></tr>';
		}elseif($page > 0 && $i >= $logcnt) { 
			echo '<tr><td align="left" width="100"><a href='.PHP_SELF.'?mode=list&page='.$bpage.'>&lt;&lt;前のページ</a></td><td align="center">全 '.$logcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><img src="./images/blank.gif" width="1" height="1"></td></tr>';
		}elseif($page == 0 && $i < $logcnt) { 
			echo '<tr><td align="left" width="100"><img src="./images/blank.gif" width="1" height="1"></td><td align="center">全 '.$logcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><a href='.PHP_SELF.'?mode=list&page='.$npage.'>次のページ&gt;&gt;</a></td></tr>';
		}elseif($page == 0 && $i > $logcnt) { 
			echo '<tr><td align="left" width="100"><img src="./images/blank.gif" width="1" height="1"></td><td align="center">全 '.$logcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><img src="./images/blank.gif" width="1" height="1"></td></tr>';
		}
		echo '</table><br>';
		echo '<table width="500" border="0" cellspacing="0" cellpadding="0" class="logbody">';
		echo '<tr><td align="center">Page: ';
		for ($i = 0; $i < $mpage; $i++) {
			$j = $i + 1;
			if ($i == $page) {
				echo '<a href='.PHP_SELF.'?mode=list&page='.$i.'>['.$j.']</a> ';
			}else{
				echo '<a href='.PHP_SELF.'?mode=list&page='.$i.'>'.$j.'</a> ';
			}
		}
		echo '</td></tr></table><br>';
	}

	if ($comment_eid != "") {
		if ($cmtlist = IDCheck($comment_eid, 1)) {
			echo '
			<a name="comments"></a>
			<div class="linktitle">■ コメント一覧 ■</div>';
			@sort($cmtlist);
			$cnt = 0;
			for ($i = 0; $i < count($cmtlist); $i++ ) {
				$cmt = file(LOGDIR.substr($cmtlist[$i], 3, 4)."/".$cmtlist[$i]);
				$cmt = array_reverse($cmt);
				while (list($cmtkey, $cmtval) = each($cmt)) {
					$cmtval = ereg_replace( "\n$", "", $cmtval );
					$cmtval = ereg_replace( "\r$", "", $cmtval );
					list($c_eid, $c_date, $c_time, $c_name, $c_email, $c_mes, $ip_addr, $user_agent, $c_url) = explode("<>", $cmtval);
					if ($c_eid == $comment_eid) {
						$c_mes = rntobr($c_mes);
						echo '
						<table width="500" border="1" cellspacing="0" cellpadding="0"  class="logbody">
						<tr bgcolor="#82BE7D" align="center"><td width="50">ID</td><td width="150">日付</td><td width="150">投稿者</td><td width="50">URL</td><td width="50">MAIL</td><td width="50">削除</td></tr>
						<tr align="center"><td bgcolor="#82BE7D">'.$cnt.'</td><td>'.date("Y/m/d h:i A", mktime(substr($c_time,0,2),substr($c_time,2,2),0,substr($c_date,4,2), substr($c_date,6,2), substr($c_date,0,4))).'</td><td>'.$c_name.'</td><td>
						';
						if ($c_url == "") {
							echo "-";
						}else{
							echo '<a href="'.$c_url.'"><img src="./images/url.gif" border="0"></a>';
						}
						echo '</td><td>';
						if ($c_email == "") {
							echo "-";
						}else{
							echo '<a href="mailto:'.$c_email.'"><img src="./images/mail.gif" border="0"></a>';
						}
						$delref = PHP_SELF.'?mode=list&action=cm_delete&page='.$page.'&eid='.$comment_eid.'&coid='.$cnt.'#comments';
						echo '
						</td><td><a href=Javascript:delcheck("'.$delref.'"); class="button"><img src="./images/trash.gif" border="0"></a></td></tr>
						<tr><td bgcolor="#82BE7D" align="center">概要</td><td colspan="5">'.$c_mes.'</td></tr>
						<tr><td bgcolor="#82BE7D" align="center">IP</td><td colspan="3">'.$ip_addr.'</td><td bgcolor="#82BE7D" align="center">IP制限</td><td align="center"><a href="./admin.php?mode=ip&amp;action=post&amp;deny_ip='.$ip_addr.'"><img src="./images/deny.gif" border="0"></a></td></tr>
						<tr><td bgcolor="#82BE7D" align="center">ブラウザ</td><td colspan="5">'.$user_agent.'</td></tr>
						</table><br>
						';
						$cnt++;
					}
				}
			}
		}
	}
	if ($trackback_eid != "") {
		if ($trklist = IDCheck($trackback_eid, 2)) {
			echo '
			<a name="trackback"></a>
			<div class="linktitle">■ トラックバック一覧 ■</div>';
			@sort($trklist);
			$cnt = 0;
			for ($i = 0; $i < count($trklist); $i++ ) {
				$trk = file(LOGDIR.substr($trklist[$i], 3, 4)."/".$trklist[$i]);
				$trk = array_reverse($trk);
				while (list($trkkey, $trkval) = each($trk)) {
					$trkval = ereg_replace( "\n$", "", $trkval );
					$trkval = ereg_replace( "\r$", "", $trkval );
						list($t_eid, $blog_name, $t_title, $t_url, $t_excerpt, $t_date, $t_time, $ip_addr, $user_agent) = explode("<>", $trkval);
						if ($t_eid == $trackback_eid) {
						$t_excerpt = str_replace( "\r\n",  "\n", $t_excerpt); 
						$t_excerpt = str_replace( "\r",  "\n", $t_excerpt);
						$t_excerpt = nl2br($t_excerpt);				//改行文字の前に<br>を代入する
						$t_excerpt = str_replace("\n",  "", $t_excerpt);		//\nを文字列から消す。
						echo '
						<table width="500" border="1" cellspacing="0" cellpadding="0"  class="logbody">
						<tr bgcolor="#82BE7D" align="center"><td width="50">ID</td><td width="150">日付</td><td width="200">サイト名</td><td width="50">URL</td><td width="50">削除</td></tr>
						<tr align="center"><td bgcolor="#82BE7D">'.$cnt.'</td><td>'.date("Y/m/d h:i A", mktime(substr($t_time,0,2),substr($t_time,2,2),substr($t_time,4,2),substr($t_date,4,2), substr($t_date,6,2), substr($t_date,0,4))).'</td><td>'.$blog_name.'</td><td>
						';
						echo '<a href="'.$t_url.'"><img src="./images/url.gif" border="0"></a>';
						$delref = PHP_SELF.'?mode=list&action=tb_delete&page='.$page.'&eid='.$trackback_eid.'&toid='.$cnt.'#trackback';
						echo '
						</td><td><a href=Javascript:delcheck("'.$delref.'"); class="button"><img src="./images/trash.gif" border="0"></a></td></tr>
						<tr><td bgcolor="#82BE7D" align="center">概要</td><td colspan="4">'.$t_excerpt.'</td></tr>
						<tr><td bgcolor="#82BE7D" align="center">IP</td><td colspan="2">'.$ip_addr.'</td><td bgcolor="#82BE7D" align="center">IP制限</td><td align="center"><a href="./admin.php?mode=ip&amp;action=post&amp;deny_ip='.$ip_addr.'"><img src="./images/deny.gif" border="0"></a></td></tr>
						<tr><td bgcolor="#82BE7D" align="center">ブラウザ</td><td colspan="4">'.$user_agent.'</td></tr>
						</table><br>
						';
						$cnt++;
					}
				}
			}
		}
	}

	echo '</div></td></tr></table></td>';
}


/* ファイルのアップロード画面 */
function file_edit($action, $page, $upfile, $upfile_name, $qry_file) {
	$inform = "";
	if ($action == "post") {
		if (!$upfile_name) {
			$inform = "アップロードするファイル名を入力してから追加ボタンを押してください。";
		}elseif(ereg("[\xA1-\xFE]", $upfile_name)) {
			$inform = "日本語文字を含むファイル名はアップロードできません。<br>半角英数文字でアップロードしてください。";
		}else{
			$dest = PICDIR.$upfile_name;
			if (file_exists($dest)) {
				$pathname = pathinfo($dest);
				$dest = PICDIR.gmdate("YmdHis",time() + TIMEZONE).$pathname['extension'];
				$inform = "重複したファイル名があった為、ファイル名を変更しました。<br>";
			}
			copy($upfile, $dest);
			$size = @getimagesize($dest);
			if($size[2] != 1 && $size[2] != 2 && $size[2] != 3){
				@unlink($dest);
				$inform = "アップロードに失敗しました。<br>指定画像タイプ以外は受け付けません<br>";
			}else{
				$finfo = pathinfo($upfile_name);
				if(file_exists($upfile)){
					if(!is_file($dest)) {
						$inform = "アップロードに失敗しました。<br>サーバがサポートしていない可能性があります<br>";
					}else{
						$inform .= "画像をアップロードしました。";
					}
				}
			}
		}
	}elseif ($action == "delete") {
		if (!unlink(PICDIR.$qry_file)) {
			$inform = "ファイルの削除に失敗しました。";
		}else{
			$inform = "ファイルを削除しました。";
		}
	}else{
		$inform = "";
	}
	up_mes($inform);
	if ($page == "") $page = 0;
	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">画像のアップロード</div><br>
	<div class="linktitle">■ 画像アップロード ■</div>
	<table border="0" cellspacing="0" cellpadding="3" class="logbody">
	<form action="'.PHP_SELF.'?mode=file&action=post" enctype="multipart/form-data" method=post>
	<tr><td>
	<input type="file" name=d_img accept="image/jpeg,image/gif,image/png" size=30>
	</td></tr>
	<tr><td>
	<input type="submit" value="追加する">
	</td></tr>
	<tr><td>
	<div class="mainstate">
	※ ファイル形式は .GIF　.JPG　.PNG でお願いします。<br>
	</div>
	</td></tr>
	</form>
	</table>
  <br>
	<div class="linktitle">■ アップロードされている画像一覧 ■</div>
	<table border="1" cellspacing="0" cellpadding="5" class="logbody">
	<tr align="center" bgcolor="#82BE7D"><td width="200">画像名</td><td width="100">サイズ</td><td width="100">登録日</td><td width="50">削除</td></tr>
	';

	//$pageから表示開始位置を算出
	$pcnt = $page * 10;	//10件で1ページ表示
	$page_st = 10 * $page + 1;
	$page_ed = $page_st + 10;
	$i = 1;
	$exitkey = 0;

	$dir_name = dir(PICDIR);
	$fname = array();
	$fsize = array();
	$ftime = array();
	$width = array();
	$height = 30;
	while($file_name = $dir_name->read()) {
		if (!is_dir($file_name)) {
			if ($size = @getimagesize(PICDIR.$file_name)) {
				if ($size[2] == 1 || $size[2] == 2 || $size[2] == 3) {
					$width[] = 30 * $size[0] / $size[1];
					$fname[] = $file_name;
					$fsize[] = round(filesize(PICDIR.$file_name) / 1024, 2);
					$ftime[] = date("Y/m/d H:i:s", filemtime(PICDIR.$file_name));
					$i++;
				}
			}
		}
	}
	arsort($ftime);
	$loopcount = 0;
	if (count($fname) == 0) {
		echo '<tr><td colspan="4" align="center">登録されている画像はありません。</td></tr>';
	}else{
		$j = 1;
		while(list($key, $val) = each($ftime)) {
			if ($j >= $page_st && $j < $page_ed) {
				echo '
				<form action="'.PHP_SELF.'?mode=file&action=delete&file='.$fname[$key].'" method=post>
				<tr><td><img src="'.PICDIR.$fname[$key].'" width="'.$width[$key].'" height="'.$height.'" style="border:solid 1px;float:left;"><a href="'.PICDIR.$fname[$key].'" target="_blank">'.PICDIR.$fname[$key].'</a></td><td align="right">'.$fsize[$key].'KB</td><td align="center">'.substr($val,0,10).'</td><td align="center"><input type="image" src="./images/trash.gif" name="submit" alt="削除"></td></tr>
				</form>
				';
				$loopcnt++;
//				if ($j >= $i-1) break;
			}
			$j++;
		}
	}
	echo '</table>';

	$pcnt_start = $pcnt + 1;  		// 表示開始位置
	$pcnt_end = $pcnt + $loopcnt;		// 表示終了位置
	$bpage = $page - 1;			// 前ページ
	$npage = $page + 1;			// 次ページ
	$imgcnt = $i - 1;
	if ($imgcnt != 0) {
		$mpage = ceil($imgcnt / 10);
	}else{
		$mpage = 0;
	}

	if ($imgcnt != 0) {
		echo '<table width="500" border="0" cellspacing="0" cellpadding="0" class="logbody">';
		if ($page > 0 && $page < $mpage - 1) {
			echo '<tr><td align="left" width="100"><a href='.PHP_SELF.'?mode=file&page='.$bpage.'>&lt;&lt;前のページ</a></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><a href='.PHP_SELF.'?mode=file&page='.$npage.'>次のページ&gt;&gt;</a></td></tr>';
		}elseif($page > 0 && $page == $mpage - 1) { 
			echo '<tr><td align="left" width="100"><a href='.PHP_SELF.'?mode=file&page='.$bpage.'>&lt;&lt;前のページ</a></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><img src="./images/blank.gif" width="1" height="1"></td></tr>';
		}elseif($page == 0 && $page < $mpage - 1) { 
			echo '<tr><td align="left" width="100"><img src="./images/blank.gif" width="1" height="1"></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><a href='.PHP_SELF.'?mode=file&page='.$npage.'>次のページ&gt;&gt;</a></td></tr>';
		}elseif($page == 0 && $page == $mpage - 1) { 
			echo '<tr><td align="left" width="100"><img src="./images/blank.gif" width="1" height="1"></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><img src="./images/blank.gif" width="1" height="1"></td></tr>';
		}
		echo '</table><br>';
		echo '<table width="500" border="0" cellspacing="0" cellpadding="0" class="logbody">';
		echo '<tr><td align="center">Page: ';
		for ($i = 0; $i < $mpage; $i++) {
			$j = $i + 1;
			if ($i == $page) {
				echo '<a href='.PHP_SELF.'?mode=file&page='.$i.'>['.$j.']</a> ';
			}else{
				echo '<a href='.PHP_SELF.'?mode=file&page='.$i.'>'.$j.'</a> ';
			}
		}
		echo '</td></tr></table><br>';
	}
	
	echo '
	</div>
	</td></tr>
	</table>
	</td>
	';
}


/* アクセス制限 */
function ip_check($action, $deny_ip, $iid, $ipaddr) {
	if($action == "post") {
		$oldip = file(LOGDIR."ip.dat");
		$ipdate = sprintf("%4d%02d%02d", gmdate ("Y", time()+TIMEZONE), gmdate ("m", time()+TIMEZONE), gmdate ("d", time()+TIMEZONE));
		//テキスト整形
		$deny_ip = CleanStr($deny_ip);
		$newip = $deny_ip."<>".$ipdate."\r\n";
		//ログファイル書き込み
		$fp = fopen(LOGDIR."ip.dat", "w");
		flock($fp, LOCK_EX);
		if (strlen($newip)){
			fputs($fp, $newip);
		}
		fputs($fp, implode('', $oldip));
		fclose($fp);
		$inform = "アクセス制限を追加しました。";
	}elseif($action == "delete") {
		$oldip = file(LOGDIR."ip.dat");
		array_splice($oldip, $key, 1);
		//ログファイル書き込み
		$fp = fopen(LOGDIR."ip.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $oldip));
		fclose($fp);
		$inform = "アクセス制限を解除しました。";
	}elseif($action == "cm_delete") {
		list($deny_key1, $deny_key2, $deny_key3, $deny_key4) = explode(".",$ipaddr);

		$bufdir = dir(LOGDIR);
		while (($ent = $bufdir->read()) !== false) {
			if ($ent != "." && $ent != "..") {
				if (is_dir(LOGDIR.$ent)) $result[] = $ent;
			}
		}
		for ($i = 0; $i < count($result); $i++) {
			$bufdir = dir(LOGDIR.$result[$i]);
			while (($ent = $bufdir->read()) !== false) {
				if (substr($ent,0,3) == "cmt") {
					if (is_file(LOGDIR.$result[$i]."/".$ent)) {
						$cnt = 0;
						$cmt = file(LOGDIR.$result[$i]."/".$ent);
						$newcomment = array();
						while (list($key, $val) = each($cmt)) {
							list(, , , , , , $ip_addr,) = explode("<>",$val);
							list($key1, $key2, $key3, $key4) = explode(".",$ip_addr);
							if ($deny_key1 == "*") $key1 = "*";
							if ($deny_key2 == "*") $key2 = "*";
							if ($deny_key3 == "*") $key3 = "*";
							if ($deny_key4 == "*") $key4 = "*";
							if ($deny_key1 != $key1 || $deny_key2 != $key2 || $deny_key3 != $key3 || $deny_key4 != $key4) {
								$newcomment[$cnt] = $cmt[$key];
								$cnt++;
							}
						}
						if ($cnt != count($cmt)) {
							//ログファイル書き込み
							$fp = fopen(LOGDIR.$result[$i]."/".$ent, "w");
							flock($fp, LOCK_EX);
							fputs($fp, implode('', $newcomment));
							fclose($fp);
						}
					}
				}
			}
		}
		$inform = "アクセス制限IPのコメントを削除しました。";

	}elseif($action == "tb_delete") {
		list($deny_key1, $deny_key2, $deny_key3, $deny_key4) = explode(".",$ipaddr);

		$bufdir = dir(LOGDIR);
		while (($ent = $bufdir->read()) !== false) {
			if ($ent != "." && $ent != "..") {
				if (is_dir(LOGDIR.$ent)) $result[] = $ent;
			}
		}
		for ($i = 0; $i < count($result); $i++) {
			$bufdir = dir(LOGDIR.$result[$i]);
			while (($ent = $bufdir->read()) !== false) {
				if (substr($ent,0,3) == "trk") {
					if (is_file(LOGDIR.$result[$i]."/".$ent)) {
						$cnt = 0;
						$trk = file(LOGDIR.$result[$i]."/".$ent);
						$newtrackback = array();
						while (list($key, $val) = each($trk)) {
							list(, , , , , , , $ip_addr,) = explode("<>",$val);
							list($key1, $key2, $key3, $key4) = explode(".",$ip_addr);
							if ($deny_key1 == "*") $key1 = "*";
							if ($deny_key2 == "*") $key2 = "*";
							if ($deny_key3 == "*") $key3 = "*";
							if ($deny_key4 == "*") $key4 = "*";
							if ($deny_key1 != $key1 || $deny_key2 != $key2 || $deny_key3 != $key3 || $deny_key4 != $key4) {
								$newtrackback[$cnt] = $trk[$key];
								$cnt++;
							}
						}
						if ($cnt != count($trk)) {
							//ログファイル書き込み
							$fp = fopen(LOGDIR.$result[$i]."/".$ent, "w");
							flock($fp, LOCK_EX);
							fputs($fp, implode('', $newtrackback));
							fclose($fp);
						}
					}
				}
			}
		}
		$inform = "アクセス制限IPのトラックバックを削除しました。";
	}
	up_mes($inform);
  echo '
  <br>
  <table width="550" border="0" cellspacing="0" cellpadding="0">
  <tr><td>
  <div class="mainbody">
  <div class="maintitle">アクセス制限</div><br>
  <div class="linktitle">■ 拒否IP追加 ■</div>
  <table border="0" cellspacing="0" cellpadding="0" class="logbody">
	<tr><td>
  <form action="'.PHP_SELF.'?mode=ip&action=post" method=post>
  <table border="1" cellspacing="0" cellpadding="5" class="logbody">
  <tr><td width="50" align="center" bgcolor="#82BE7D">IP</td><td><input type="text" name=deny_ip size=30></td>
	</tr>
	</table>
	</td>
	<td> <input type="submit" value="追加する"></td>
	</tr>
	</form>
	</table>
<div class="mainstate">
※ IPアドレスにはワイルドカード(*)が使用できます。<br>
※ 使用例）192.168.0.* で 192.168.0.0〜192.168.0.255までのIPを拒否します。
</div>
<br>
	<div class="linktitle">■ 拒否IPリスト ■</div>
	<table border="1" cellspacing="0" cellpadding="5" class="logbody">
	<tr align="center" bgcolor="#82BE7D"><td width="75">IP</td><td width="75">登録日</td><td width="100">コメント一括削除</td><td width="150">トラックバック一括削除</td><td width="50">IP削除</td></tr>
	';
	$ip = file(LOGDIR."ip.dat");
	if (count($ip) == 0) {
		echo '<tr align="center"><td colspan="5">禁止IPは登録されていません</td></tr>';
	}else{
		for ( $i = 0; $i < count( $ip ); $i++ ) {
      $ip[$i] = ereg_replace( "\n$", "", $ip[$i] );
      $ip[$i] = ereg_replace( "\r$", "", $ip[$i] );
      list($ipaddr, $ipdate) = explode("<>", $ip[$i]);
      echo '
      
      <tr align="center">
				<td>'.$ipaddr.'</td>
				<td>'.date("Y/m/d", mktime(0,0,0,substr($ipdate,4,2), substr($ipdate,6,2), substr($ipdate,0,4))).'</td>
				<td align ="center">
					<form action="'.PHP_SELF.'?mode=ip&action=cm_delete&ip='.$ipaddr.'" method="post" style="margin:0px;padding:0px;">
						<input type="image" src="./images/trashl.gif" name="submit" alt="一括削除">
		      </form>
				</td>
				<td align ="center">
					<form action="'.PHP_SELF.'?mode=ip&action=tb_delete&ip='.$ipaddr.'" method="post" style="margin:0px;padding:0px;">
						<input type="image" src="./images/trashl.gif" name="submit" alt="一括削除">
		      </form>
				</td>
				<td align ="center"><a href="'.PHP_SELF.'?mode=ip&action=delete&iid='.$i.'"><img src="./images/trash.gif" border="0"></a></td>
			</tr>
      ';
    }
  }
  echo '
  </table>
<div class="mainstate">
※ コメント一括削除 - 特定のIPからのコメントを全て削除します。<br>
※ トラックバック一括削除 - 特定のIPからのトラックバックを全て削除します。<br>
※ IP削除 - 拒否IPリストからIPを削除します。
</div>
  </div>
	</td></tr>
  </table>
  </td>
  ';
}


/* モバイル投稿設定 */
function mobile($qry_action, $receive, $send, $pop, $mobile_id, $mobile_pass, $from, $to, $access_time, $apop, $mobile_cok, $mobile_tok, $mobile_category) {
	if ($qry_action == "edit") {
		//テキスト整形
		$receive = CleanStr($receive)."\r\n";
		$send = CleanStr($send)."\r\n";
		$pop = CleanStr($pop)."\r\n";
		$mobile_id = CleanStr($mobile_id)."\r\n";
		$mobile_pass = CleanStr($mobile_pass)."\r\n";
		$access_time = $access_time."\r\n";
		$apop = $apop."\r\n";
		$mobile_cok = $mobile_cok."\r\n";
		$mobile_tok = $mobile_tok."\r\n";
		$mobile_category = $mobile_category."\r\n";

		//ログファイル書き込み
		$fp = fopen(LOGDIR."mobile.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, $receive);								// [0]
		fputs($fp, $send);									// [1]
		fputs($fp, $pop);										// [2]
		fputs($fp, $mobile_id);							// [3]
		fputs($fp, $mobile_pass);						// [4]
		fputs($fp, $access_time);						// [5]
		fputs($fp, $apop);									// [6]
		fputs($fp, $mobile_cok);						// [7]
		fputs($fp, $mobile_tok);						// [8]
		fputs($fp, $mobile_category);				// [9]

		fclose($fp);
		$inform = "携帯のアドレス登録及びアクセス時間の設定を変更しました。";
	}elseif($qry_action == "send"){
		$sub = "Blogn（ぶろぐん）からのメッセージ";
		$sub = mbConv($sub,0,2);
		$sub = "=?iso-2022-jp?B?".base64_encode($sub)."?=";
		$mes = $from."\n";
		$mes = $mes."携帯を使用して上記アドレスから写メール投稿が出来ます。\n";
		$mes = $mes."※このメールアドレスには返信しないでください。\n";
		$mes = mbConv($mes,0,2);
		$from = "From:blogn";
		if (!trim($to)) {
			$inform = "メール送信先アドレスが登録されていません。";
		}elseif (!mail($to, $sub, $mes, $from)) {
			$inform = "メールが送信できませんでした。";
		}else{
			$inform = "メールを送信しました。";
		}
	}else{
		$inform = "";
	}

	up_mes($inform);

  $mobile = file(LOGDIR."mobile.dat");
  if (count($mobile) == 0) $mobile = "";

  echo '
  <br>
  <table width="550" border="0" cellspacing="0" cellpadding="0">
  <tr><td>
  <div class="mainbody">
  <div class="maintitle">モバイルの投稿設定</div><br>
  <div class="linktitle">■ 専用メールアドレス ■</div>
  <table border="0" cellspacing="0" cellpadding="0" class="logbody">
  <form action="'.PHP_SELF.'?mode=mobile&action=edit" method=post>
	<tr><td>
  <table border="1" cellspacing="0" cellpadding="5" class="logbody">
  <tr><td width="150" align="center" bgcolor="#82BE7D">受信アドレス登録</td><td width="350"><input type="text" name=receive size=50 value='.$mobile[0].'></td></tr>
  <tr><td width="150" align="center" bgcolor="#82BE7D">POP3サーバー</td><td width="350"><input type="text" name=pop size=50 value='.$mobile[2].'></td></tr>
  <tr><td width="150" align="center" bgcolor="#82BE7D">ユーザーID</td><td width="350"><input type="text" name=mobile_id size=50 value='.$mobile[3].'></td></tr>
  <tr><td width="150" align="center" bgcolor="#82BE7D">パスワード</td><td width="350"><input type="password" name=mobile_pass size=10 value='.$mobile[4].'></td></tr>
  <tr><td width="150" align="center" bgcolor="#82BE7D">送信アドレス登録</td><td width="350"><input type="text" name=send size=50 value='.$mobile[1].'></td></tr>
  <tr><td width="150" align="center" bgcolor="#82BE7D">メールアクセス間隔(分)</td><td width="350">
	<select name="access_time">';
	$access_time = array(1,2,3,4,5,6,7,8,9,10,15,30,60); 
	foreach ($access_time as $i) { 
		if ($mobile[5] == $i) {
			echo '<option value="'.$i.'" selected>'.$i.'</option>';
		}else{
			echo '<option value="'.$i.'">'.$i.'</option>';
		}
	}
	echo '
</select>
</td></tr>
	<tr><td width="150" align="center" bgcolor="#82BE7D">認証方式</td><td width="350">
	<input type="radio" name="apop" value="0"';
	if ($mobile[6] == 0) {
		echo " checked";
	}
	echo '>標準
	<input type="radio" name="apop" value="1"';
	if ($mobile[6] == 1) {
		echo "checked";
	}
	echo '>APOP
</td></tr>
	<tr><td width="150" align="center" bgcolor="#82BE7D">コメント</td><td width="350">
	<select name=mobile_cok>';
	if ($mobile[7] != 1) {
		echo '<option value="0" selected>受付を許可</option>
		<option value="1">受付を拒否</option>';
	}else{
		echo '<option value="0">受付を許可</option>
		<option value="1" selected>受付を拒否</option>';
	}
	echo '</select></td></tr>
	<tr><td width="150" align="center" bgcolor="#82BE7D">トラックバック</td><td width="350">
	<select name=mobile_tok>';
	if ($mobile[8] != 1) {
		echo '<option value="0" selected>受付を許可</option>
		<option value="1">受付を拒否</option>';
	}else{
		echo '<option value="0">受付を許可</option>
		<option value="1" selected>受付を拒否</option>';
	}
	echo '</select></td></tr>
	<tr><td width="150" align="center" bgcolor="#82BE7D">カテゴリ</td><td width="350">
 	<select name=mobile_category>';
	if (file_exists(LOGDIR."category.dat")){
		$category = file(LOGDIR."category.dat");
		echo '<option value="-1">指定なし</option>';
		if (count($category) > 0) {
			for ( $i = 0; $i < count( $category ); $i++ ) {
				$category[$i] = ereg_replace( "\n$", "", $category[$i] );
				$category[$i] = ereg_replace( "\r$", "", $category[$i] );
				list($cid, $c_name) = explode("<>", $category[$i]);
				echo '<option value="'.$cid.'"';
				if (trim((string)$mobile[9]) == $cid) echo ' selected';
				echo '>'.$c_name.'</option>';
			}
		}
	}
	echo '</select>
</td></tr>
  </table>
	</td></tr>
	<tr><td align="right">
  <input type="submit" value="登録する">
	</td></tr>
  </form>
	</table>
	<br>
  <div class="linktitle">■ 携帯に登録アドレスを転送する ■</div>
  <table border="0" cellspacing="0" cellpadding="5" class="logbody">
  <form action="'.PHP_SELF.'?mode=mobile&action=send" method=post>
	<tr><td>
  <table border="1" cellspacing="0" cellpadding="5" class="logbody">
  <input type="hidden" name=from value="'.$mobile[0].'">
  <input type="hidden" name=to value="'.$mobile[1].'">
  <tr><td width="150" align="center" bgcolor="#82BE7D">送信先アドレス</td><td width="350">'.$mobile[1].'</td></tr>
  </table>
	</td></tr>
	<tr><td align="right">
  <input type="submit" value="送信する">
	</td></tr>
  </form>
	</table>
  </div>
	</td></tr>
  </table>
  </td>
  ';
}


/* スキンの追加 */
function skinup($action, $page, $s_title, $uphtm, $upcss, $htm_name, $css_name, $qry_sid) {
	if ($action == "post") {
		$inform = "";
		if ($skin = file(LOGDIR."skin.dat")) {
			list($sid) = explode("<>",$skin[0],2);
			$sid++;
		}else{
			$sid = 0;
		}
		if($htm_name && $css_name) {
			//htmlファイルのアップロード
			$finfo = pathinfo($htm_name);
			if ($finfo["extension"] == "html" || $finfo["extension"] == "htm"){
				$htm_name = strtolower($sid.".html");
				$dest = SKINDIR.$htm_name;
				copy($uphtm, $dest);
				if(!is_file($dest)) {
					$inform = "アップロードに失敗しました。<br>サーバがサポートしていない可能性があります。";
				}else{
					//cssファイルのアップロード
					$finfo = pathinfo($css_name);
					if ($finfo["extension"] == "css"){
						$css_name = strtolower($sid.".css");
						$dest = SKINDIR.$css_name;
						copy($upcss, $dest);
						if(!is_file($dest)) {
							$inform = "アップロードに失敗しました。<br>サーバがサポートしていない可能性があります。";
						}else{
							
							//ログファイル書き込み
							if (trim($s_title) == "") {
								$s_title = "スキン_".$sid;
								$inform = "スキン名が未記入の為、自動でスキン名を作成いたしました。<br>";
							}
							$newskin = $sid."<>".$s_title."<>".$htm_name."<>".$css_name."\r\n";
							$fp = fopen(LOGDIR."skin.dat", "w");
							flock($fp, LOCK_EX);
							fputs($fp, $newskin);
							if ($sid != 0) {
								fputs($fp, implode('', $skin));
							}
							fclose($fp);
							$inform .= "ファイルをアップロードしました。";
						}
					}else{
						$inform = "アップロードに失敗しました。<br>CSSファイル以外は受け付けません。";
					}
				}
			}else{
				$inform = "アップロードに失敗しました。<br>HTMLファイル以外は受け付けません。";
			}
		}else{
			$inform = "アップロードに失敗しました。<br>ファイルが選択されていません。";
		}
	}elseif ($action == "delete") {
		if ($skin = file(LOGDIR."skin.dat")) {
			$newskin = array();
			for ($i = 0; $i <count($skin); $i++) {
				list($sid) = explode("<>",$skin[$i],2);
				if ($sid != $qry_sid) $newskin[] = $skin[$i];
			}
			$fp = fopen(LOGDIR."skin.dat", "w");
			flock($fp, LOCK_EX);
			if (count($newskin) != 0) {
				fputs($fp, implode('', $newskin));
			}else{
				fputs($fp, "");
			}
			fclose($fp);
		}
		if (!unlink(SKINDIR.$qry_sid.".html") || !unlink(SKINDIR.$qry_sid.".css")) {
			$inform = "スキンファイルの削除に失敗しました。";
		}else{
			$inform = "スキンファイルを削除しました。";
		}
	}else{
		$inform = "";
	}
	up_mes($inform);
	if ($page == "") $page = 0;
	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">スキンの追加編集</div><br>
	<table width="500" border="0" cellspacing="0" cellpadding="3" class="logbody">
	<form action="'.PHP_SELF.'?mode=skinup&action=post" enctype="multipart/form-data" method=post>
	<tr>
	<td colspan="2">
	<div class="linktitle">■ スキン名 ■</div>
	<input type="text" name=s_title size=50>
	</td></tr>
	<tr>
	<td width="250">
	<div class="linktitle">■ HTMLアップロード ■</div>
	<input type="file" name=s_htm accept="text/html" size=30>
	</td>
	<td width="250">
	<div class="linktitle">■ CSSアップロード ■</div>
	<input type="file" name=s_css accept="text/css" size=30>
	</td>
	</tr>
	<tr><td>
	<div class="mainstate">※ ファイル形式は .html　.htm でお願いします。</div>
	</td>
	<td>
	<div class="mainstate">※ ファイル形式は .css でお願いします。</div>
	</td></tr>
	<tr><td colspan="2">
	<input type="submit" value="追加する">
	</td></tr>
	</form>
	</table>
  <br>
	<div class="linktitle">■ アップロードされているスキン一覧 ■</div>
	<table border="1" cellspacing="0" cellpadding="5" class="logbody">
	<tr align="center" bgcolor="#82BE7D"><td width="100">スキン名</td><td width="125">HTML更新日</td><td width="125">CSS更新日</td><td width="50">編集</td><td width="50">削除</td></tr>
	';

	//$pageから表示開始位置を算出
	$pcnt = $page * 10;	//10件で1ページ表示
	$page_st = 10 * $page + 1;
	$page_ed = $page_st + 10;
	$loopcount = 0;
	if ($skin = file(LOGDIR."skin.dat")) {
		for ($j = $page_st; $j < $page_ed; $j++) {
			$skin[$j-1] = ereg_replace( "\n$", "", $skin[$j-1] );
			$skin[$j-1] = ereg_replace( "\r$", "", $skin[$j-1] );
			list($sid, $s_title, $htm_name, $css_name) = explode("<>",$skin[$j-1]);
			$htm_time = date('Y/m/d H:i', filemtime(SKINDIR.$htm_name));
			$css_time = date('Y/m/d H:i', filemtime(SKINDIR.$css_name));
			echo '
			<form action="'.PHP_SELF.'?mode=skinlist&action=edit&sid='.$sid.'" method=post>
			<tr><td><input type="text" style="width:90px;" name="s_title" readonly="readonly" value="'.$s_title.'"></td><td align="center">'.$htm_time.'</td><td align="center">'.$css_time.'</td><td align="center"><input type="image" src="./images/edit.gif" name="submit" alt="編集"></td><td align="center"><a href="'.PHP_SELF.'?mode=skinup&action=delete&sid='.$sid.'"><img src="./images/trash.gif" border="0"></a></td></tr>
			</form>
			';
			$loopcnt++;
			if ($j >= count($skin)) break;
		}
	}else{
		echo '<tr><td colspan="5" align="center">登録されているスキンはありません。</td></tr>';
	}
	echo '</table>';

	$pcnt_start = $pcnt + 1;  		// 表示開始位置
	$pcnt_end = $pcnt + $loopcnt;		// 表示終了位置
	$bpage = $page - 1;			// 前ページ
	$npage = $page + 1;			// 次ページ
	if (count($skin) != 0) {
		$mpage = ceil(count($skin) / 10);
	}else{
		$mpage = 0;
	}

	if (count($skin) != 0) {
		echo '<table width="500" border="0" cellspacing="0" cellpadding="0" class="logbody">';
		if ($page > 0 && $page < $mpage - 1) {
			echo '<tr><td align="left" width="100"><a href='.PHP_SELF.'?mode=skinup&page='.$bpage.'>&lt;&lt;前のページ</a></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><a href='.PHP_SELF.'?mode=skinup&page='.$npage.'>次のページ&gt;&gt;</a></td></tr>';
		}elseif($page > 0 && $page == $mpage - 1) { 
			echo '<tr><td align="left" width="100"><a href='.PHP_SELF.'?mode=skinup&page='.$bpage.'>&lt;&lt;前のページ</a></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><img src="./images/blank.gif" width="1" height="1"></td></tr>';
		}elseif($page == 0 && $page < $mpage - 1) { 
			echo '<tr><td align="left" width="100"><img src="./images/blank.gif" width="1" height="1"></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><a href='.PHP_SELF.'?mode=skinup&page='.$npage.'>次のページ&gt;&gt;</a></td></tr>';
		}elseif($page == 0 && $page == $mpage - 1) { 
			echo '<tr><td align="left" width="100"><img src="./images/blank.gif" width="1" height="1"></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><img src="./images/blank.gif" width="1" height="1"></td></tr>';
		}
		echo '</table><br>';
		echo '<table width="500" border="0" cellspacing="0" cellpadding="0" class="logbody">';
		echo '<tr><td align="center">Page: ';
		for ($i = 0; $i < $mpage; $i++) {
			$j = $i + 1;
			if ($i == $page) {
				echo '<a href='.PHP_SELF.'?mode=skinup&page='.$i.'>['.$j.']</a> ';
			}else{
				echo '<a href='.PHP_SELF.'?mode=skinup&page='.$i.'>'.$j.'</a> ';
			}
		}
		echo '</td></tr></table><br>';
	}
	
	echo '
	</div>
	</td></tr>
	</table>
	</td>
	';
}


/* スキン用画像のアップロード */
function skinfile($action, $page, $upfile, $upfile_name, $qry_file) {
	$inform = "";
	if ($action == "post") {
		if (!$upfile_name) {
			$inform = "アップロードするファイル名を入力してから追加ボタンを押してください。";
		}elseif(ereg("[\xA1-\xFE]", $upfile_name)) {
			$inform = "日本語文字を含むファイル名はアップロードできません。<br>半角英数文字でアップロードしてください。";
		}else{
			$dest = SKINPICDIR.$upfile_name;
			if (file_exists($dest)) {
				$pathname = pathinfo($dest);
				$dest = SKINPICDIR.gmdate("YmdHis",time() + TIMEZONE).$pathname['extension'];
				$inform = "重複したファイル名があった為、ファイル名を変更しました。<br>";
			}
			copy($upfile, $dest);
			$size = @getimagesize($dest);
			if($size[2] != 1 && $size[2] != 2 && $size[2] != 3){
				@unlink($dest);
				$inform = "アップロードに失敗しました。<br>指定画像タイプ以外は受け付けません<br>";
			}else{
				$finfo = pathinfo($upfile_name);
				if(file_exists($upfile)){
					if(!is_file($dest)) {
						$inform = "アップロードに失敗しました。<br>サーバがサポートしていない可能性があります<br>";
					}else{
						$inform .= "画像をアップロードしました。";
					}
				}
			}
		}
	}elseif ($action == "delete") {
		if (!unlink(SKINPICDIR.$qry_file)) {
			$inform = "ファイルの削除に失敗しました。";
		}else{
			$inform = "ファイルを削除しました。";
		}
	}else{
		$inform = "";
	}
	up_mes($inform);
	if ($page == "") $page = 0;
	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">スキン用画像のアップロード</div><br>
	<div class="linktitle">■ 画像アップロード ■</div>
	<table border="0" cellspacing="0" cellpadding="3" class="logbody">
	<form action="'.PHP_SELF.'?mode=skinfile&action=post" enctype="multipart/form-data" method=post>
	<tr><td>
	<input type="file" name=d_img accept="image/jpeg,image/gif,image/png" size=30>
	</td></tr>
	<tr><td>
	<input type="submit" value="追加する">
	</td></tr>
	<tr><td>
	<div class="mainstate">
	※ ファイル形式は .GIF　.JPG　.PNG でお願いします。<br>
	</div>
	</td></tr>
	</form>
	</table>
  <br>
	<div class="linktitle">■ アップロードされている画像一覧 ■</div>
	<table border="0" cellspacing="0" cellpadding="0" class="logbody">
	<tr align="center">
	';

	//$pageから表示開始位置を算出
	$pcnt = $page * 5;	//10件で1ページ表示
	$page_st = 5 * $page + 1;
	$page_ed = $page_st + 5;
	$i = 1;
	$exitkey = 0;

	$dir_name = dir(SKINPICDIR);
	$fname = array();
	$fsize = array();
	$ftime = array();
	$width = array();
	while($file_name = $dir_name->read()) {
		if (!is_dir($file_name)) {
			if ($size = @getimagesize(SKINPICDIR.$file_name)) {
				if ($size[2] == 1 || $size[2] == 2 || $size[2] == 3) {
					if ($size[0] < 80 && $size[1] < 80) {
						$width[] = $size[0];
						$height[] = $size[1];
					}elseif ($size[0] > $size[1]) {
						$width[] = 80;
						$height[] = round(80 * $size[1] / $size[0]);
					}else{
						$width[] = round(80 * $size[0] / $size[1]);
						$height[] = 80;
					}
					$fname[] = $file_name;
					$fsize[] = round(filesize(SKINPICDIR.$file_name) / 1024, 2);
					$ftime[] = date("Y/m/d", filemtime(SKINPICDIR.$file_name));
					$i++;
				}
			}
		}
	}
	arsort($ftime);
	$loopcount = 0;
	if (count($fname) == 0) {
		echo '<td align="center">登録されている画像はありません。</td>';
	}else{
		$j = 1;
		while(list($key, $val) = each($ftime)) {
			if ($j >= $page_st && $j < $page_ed) {
				echo '
				<form action="'.PHP_SELF.'?mode=skinfile&action=delete&file='.$fname[$key].'" method=post>
				<td width="100" align="center" style="border:solid 1px;">
				<table border="0" cellspacing="0" cellpadding="0"><tr><td align="center" valign="center" style="border:solid 1px;width:90px;height:90px"><img src="'.SKINPICDIR.$fname[$key].'" width="'.$width[$key].'" height="'.$height[$key].'" style="border:0px;"></td></tr></table>
				<a href="'.SKINPICDIR.$fname[$key].'" target="_blank"><input type="text" value="'.$fname[$key].'" readonly="readonly" style="border:0px;width:90px;height:20px;cursor: pointer;"></a>
				<div style="height:20px;">'.$fsize[$key].'KB</div>
				<div style="height:20px;">'.$val.'</div>
				<input type="image" src="./images/trash.gif" name="submit" alt="削除">
				</td>
				</form>
				';
				$loopcnt++;
//				if ($j >= $i-1) break;
			}
			$j++;
		}
	}
	echo '</tr></table>';

	$pcnt_start = $pcnt + 1;  		// 表示開始位置
	$pcnt_end = $pcnt + $loopcnt;		// 表示終了位置
	$bpage = $page - 1;			// 前ページ
	$npage = $page + 1;			// 次ページ
	$imgcnt = $i - 1;
	if ($imgcnt != 0) {
		$mpage = ceil($imgcnt / 5);
	}else{
		$mpage = 0;
	}

	if ($imgcnt != 0) {
		echo '<table width="500" border="0" cellspacing="0" cellpadding="0" class="logbody">';
		if ($page > 0 && $page < $mpage - 1) {
			echo '<tr><td align="left" width="100"><a href='.PHP_SELF.'?mode=skinfile&page='.$bpage.'>&lt;&lt;前のページ</a></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><a href='.PHP_SELF.'?mode=skinfile&page='.$npage.'>次のページ&gt;&gt;</a></td></tr>';
		}elseif($page > 0 && $page == $mpage - 1) { 
			echo '<tr><td align="left" width="100"><a href='.PHP_SELF.'?mode=skinfile&page='.$bpage.'>&lt;&lt;前のページ</a></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><img src="./images/blank.gif" width="1" height="1"></td></tr>';
		}elseif($page == 0 && $page < $mpage - 1) { 
			echo '<tr><td align="left" width="100"><img src="./images/blank.gif" width="1" height="1"></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><a href='.PHP_SELF.'?mode=skinfile&page='.$npage.'>次のページ&gt;&gt;</a></td></tr>';
		}elseif($page == 0 && $page == $mpage - 1) { 
			echo '<tr><td align="left" width="100"><img src="./images/blank.gif" width="1" height="1"></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><img src="./images/blank.gif" width="1" height="1"></td></tr>';
		}
		echo '</table><br>';
		echo '<table width="500" border="0" cellspacing="0" cellpadding="0" class="logbody">';
		echo '<tr><td align="center">Page: ';
		for ($i = 0; $i < $mpage; $i++) {
			$j = $i + 1;
			if ($i == $page) {
				echo '<a href='.PHP_SELF.'?mode=skinfile&page='.$i.'>['.$j.']</a> ';
			}else{
				echo '<a href='.PHP_SELF.'?mode=skinfile&page='.$i.'>'.$j.'</a> ';
			}
		}
		echo '</td></tr></table><br>';
	}
	
	echo '
	</div>
	</td></tr>
	</table>
	</td>
	';
}


/* スキンのカスタマイズ */
function skinlist($action, $htm_text, $css_text, $s_title, $qry_sid) {
	if ($action == "htmcss") {
		if ($skin = file(LOGDIR."skin.dat")) {
			for ($i = 0; $i <count($skin); $i++) {
				list($sid) = explode("<>",$skin[$i],2);
				if ($sid == $qry_sid) {
					$skin[$i] = ereg_replace( "\n$", "", $skin[$i] );
					$skin[$i] = ereg_replace( "\r$", "", $skin[$i] );
					list($old_sid, $old_title, $htm_name, $css_name) = explode("<>",$skin[$i]);
					$skin[$i] = $sid."<>".$s_title."<>".$htm_name."<>".$css_name."\r\n";
					break;
				}
			}
			$fp = fopen(LOGDIR."skin.dat", "w");
			flock($fp, LOCK_EX);
			fputs($fp, implode('', $skin));
			fclose($fp);
		}

		$s_htm  = stripslashes($htm_text);
		$s_css  = stripslashes($css_text);
		if (CHARSET == 0) {
			// Shift_JIS
			$s_htm = mbConv($s_htm,1,2);
			$s_css = mbConv($s_css,1,2);
		}elseif (CHARSET == 1) {
			// EUC-JP
			$s_htm = mbConv($s_htm,1,1);
			$s_css = mbConv($s_css,1,1);
		}elseif (CHARSET == 2) {
			// UTF-8
			$s_htm = mbConv($s_htm,1,4);
			$s_css = mbConv($s_css,1,4);
		}

		$htmstr = $qry_sid.".html";
		if (unlink(SKINDIR.$htmstr)) {
			$fp = fopen(SKINDIR.$htmstr, "w");
			flock($fp, LOCK_EX);
			fputs($fp, $s_htm);
			fclose($fp);
		}
		$cssstr = $qry_sid.".css";
		if (unlink(SKINDIR.$cssstr)) {
			$fp = fopen(SKINDIR.$cssstr, "w");
			flock($fp, LOCK_EX);
			fputs($fp, $s_css);
			fclose($fp);
		}

		$inform .= "HTML/CSSファイルを更新しました。";

		$htm_text  = CleanStr($htm_text);
		$css_text  = CleanStr($css_text);
		// 改行文字の統一。 
		$htm_text = str_replace( "&nbsp;", " ", $htm_text);		// &nbsp をスペースに変換する
		$css_text = str_replace( "&nbsp;", " ", $css_text);		// &nbsp をスペースに変換する

	}elseif($action == "edit") {
		if ($htm_skin = file(SKINDIR.$qry_sid.".html")) {
			$htm_text = implode('', $htm_skin);
			$htm_text = mbConv($htm_text,0,1);
		}else{
			$htm_text = "";
		}
		if ($css_skin = file(SKINDIR.$qry_sid.".css")) {
			$css_text = implode('', $css_skin);
			$css_text = mbConv($css_text,0,1);
		}else{
			$css_text = "";
		}
		$htm_text  = CleanStr($htm_text);
		$css_text  = CleanStr($css_text);
		// TABを半角スペース2文字に変換
		$htm_text = preg_replace('/[\t]+?/', '  ',$htm_text);
		$css_text = preg_replace('/[\t]+?/', '  ',$css_text);
		// 改行文字の統一。 
		$htm_text = str_replace( "&nbsp;", " ", $htm_text);		// &nbsp をスペースに変換する
		$css_text = str_replace( "&nbsp;", " ", $css_text);		// &nbsp をスペースに変換する
	}
	up_mes($inform);

	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0" class="logbody">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">スキンの編集</div><br>
	<table width="500" border="0" cellspacing="0" cellpadding="3">
	<form action="'.PHP_SELF.'?mode=skinlist&action=htmcss&sid='.$qry_sid.'" method=post>
	<tr><td>
	<div class="linktitle">■ スキン名 ■</div>
	<input type="text" name=s_title size=50 value="'.$s_title.'">
	</td></tr>
	<tr><td><br>
	<div class="linktitle">■ HTML ■</div>
	<textarea style="WIDTH: 500px;font-size:12px;" id="htm_text" name="htm_text" rows="15" cols="50" wrap="virtual">'.tagreplaceStr($htm_text).'</textarea>
	</td></tr>
	<tr><td><br>
	<div class="linktitle">■ CSS ■</div>
	<textarea style="WIDTH: 500px;font-size:12px;" id="css_text" name="css_text" rows="15" cols="50" wrap="virtual">'.tagreplaceStr($css_text).'</textarea>
	<div align="center"><input type="submit" name="htmcss" value="HTML/CSSを更新する"></div>
	</td></tr>
	</form>
	</table>
	</td></tr>
	</table>
	';
}


/* 使用スキンの登録 */
function skinset($action, $vid, $nskin, $rskin, $dskin, $sskin, $pskin, $iskin, $mskin, $cskin) {
	$inform = "";
	if ($action == "set") {
		$skinview = array();
		if ($vid == 0) {
			// ノーマル表示用
			$skinview[0] = "0<>".$nskin."<>\r\n";
			$inform = "使用スキンの登録をしました。：ノーマル表示";
		}elseif ($vid == 1) {
			// ランダム表示用
			$i = 0;
			while(list($key,$val) = each($rskin)) {
				$skinview[$i] = "1<>".$key."<>\r\n";
				$i++;
			}
			$inform = "使用スキンの登録をしました。：ランダム表示";
		}elseif ($vid == 2) {
			// ジャンル別表示用
			$skinview[0] = "2<>".$dskin."<>\r\n";
			$skinview[1] = "2<>".$sskin."<>\r\n";
			$skinview[2] = "2<>".$pskin."<>\r\n";
			$skinview[3] = "2<>".$iskin."<>\r\n";
			for ($i = 0; $i < 12; $i++) {
				$skinview[$i+4] = "2<>".$mskin[$i]."<>\r\n";
			}
			$i = 16;
			while(list($key,$val) = each($cskin)) {
				$skinview[$i] = "2<>".$val."<>".$key."\r\n";
				$i++;
			}
			$inform = "使用スキンの登録をしました。：ジャンル別表示";
		}
		$fp = fopen(LOGDIR."skinview.dat", "w");
		flock($fp, LOCK_EX);
		fputs($fp, implode("",$skinview));
		fclose($fp);
	}

	if ($skinview = file(LOGDIR."skinview.dat")) {
		for ($i = 0; $i < count($skinview); $i++) {
			$skinview[$i] = ereg_replace( "\n$", "", $skinview[$i] );
			$skinview[$i] = ereg_replace( "\r$", "", $skinview[$i] );
			list($vid, $v_sid[$i], $v_cid[$i]) = explode("<>",$skinview[$i]);
		}
	}
	if ($skin = file(LOGDIR."skin.dat")) {
		for ($i = 0; $i <count($skin); $i++) {
			list($sid[$i],$sname[$i]) = explode("<>",$skin[$i],3);
		}
	}else{
		$inform .= "スキンが登録されていません。<br>スキンを追加してから使用スキンを登録してください。";
	}
	up_mes($inform);

	echo '
	<br>
	<table width="550" border="0" cellspacing="0" cellpadding="0" class="logbody">
	<tr><td>
	<div class="mainbody">
	<div class="maintitle">使用スキンの登録</div><br>
	<table width="500" border="0" cellspacing="0" cellpadding="3">
	<tr><td>
	<form action="'.PHP_SELF.'?mode=skinset&action=set" method=post>
	<table border="0" cellspacing="0" cellpadding="3" class="logbody">
	<tr><td>
	<div class="linktitle">
	';
	if ($vid == 0) {
		echo '<input type="radio" name="vid" value=0 id="normal" checked>';
	}else{
		echo '<input type="radio" name="vid" value=0 id="normal">';
	}
	echo '
	<label for="normal">■ ノーマル表示 ■</label>
	</div>
	<div style="margin-left:20px;">
	<table width="240" border="1" cellspacing="0" cellpadding="3" class="logbody">
	<tr><td width="50" align="center" bgcolor="#82BE7D">
	基本
	</td><td>
	<select name=nskin style="width:150px;">
	';
	for ($i = 0; $i <count($sid); $i++) {
		if ($vid == 0) {
			if ($v_sid[0] == $sid[$i]) {
				echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
			}else{
				echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
			}
		}elseif ($i == 0) {
			echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
		}else{
			echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
		}
	}
	echo '
	</select>
	</td></tr>
	</table>
	</div>
	</td></tr>
	<tr><td>
	<div class="linktitle">
	';
	if ($vid == 1) {
		echo '<input type="radio" name="vid" value=1 id="random" checked>';
	}else{
		echo '<input type="radio" name="vid" value=1 id="random">';
	}
	echo '
	<label for="random">■ ランダム表示 ■</label>
	</div>
	<div style="margin-left:20px;">
	<table width="480" border="1" cellspacing="0" cellpadding="3" class="logbody">
	';
	for ($i = 0; $i <count($sid); $i++) {
		echo '<tr><td width="230" bgcolor="#82BE7D">'.$sname[$i].'</td><td>';
		
		if ($vid == 1) {
			$vkey = 0;
			foreach($v_sid as $val) {
				if ($val == $sid[$i]) {
					echo '<input type="checkbox" name="rskin['.$sid[$i].']" value=1 id="rskin'.$i.'" checked>';
					$vkey = 1;
				}
			}
			if ($vkey == 0) {
				echo '<input type="checkbox" name="rskin['.$sid[$i].']" value=1 id="rskin'.$i.'">';
			}
		}else{
			echo '<input type="checkbox" name="rskin['.$sid[$i].']" value=1 id="rskin'.$i.'">';
		}
		echo '<label for="rskin'.$i.'">表示する</label></td></tr>';
	}
	echo '
	</table>
	</div>
	</td></tr>
	<tr><td>
	<div class="linktitle">
	';
	if ($vid == 2) {
		echo '<input type="radio" name="vid" value=2 id="calendar" checked>';
	}else{
		echo '<input type="radio" name="vid" value=2 id="calendar">';
	}
	echo '
	<label for="calendar">■ ジャンル別表示 ■</label>
	</div>
	<div style="margin-left:20px;">
	<table width="480" border="1" cellspacing="0" cellpadding="3" class="logbody">
		<tr><td colspan="2">初期表示／サイト内検索表示／プロフィール表示／指定記事表示</td></tr>
	<tr><td width="150" bgcolor="#82BE7D">
	初期表示
	</td><td>
	<select name=dskin style="width:150px;">
	';
	for ($i = 0; $i <count($sid); $i++) {
		if ($vid == 2) {
			if ($v_sid[0] == $sid[$i]) {
				echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
			}else{
				echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
			}
		}elseif ($i == 0) {
			echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
		}else{
			echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
		}
	}
	echo '
	</select>
	</td></tr>
	<tr><td width="150" bgcolor="#82BE7D">
	サイト内検索表示
	</td><td>
	<select name=sskin style="width:150px;">
	';
	for ($i = 0; $i <count($sid); $i++) {
		if ($vid == 2) {
			if ($v_sid[1] == $sid[$i]) {
				echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
			}else{
				echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
			}
		}elseif ($i == 0) {
			echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
		}else{
			echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
		}
	}
	echo '
	</select>
	</td></tr>
	<tr><td width="150" bgcolor="#82BE7D">
	プロフィール表示
	</td><td>
	<select name=pskin style="width:150px;">
	';
	for ($i = 0; $i <count($sid); $i++) {
		if ($vid == 2) {
			if ($v_sid[2] == $sid[$i]) {
				echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
			}else{
				echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
			}
		}elseif ($i == 0) {
			echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
		}else{
			echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
		}
	}
	echo '
	</select>
	</td></tr>
	<tr><td width="150" bgcolor="#82BE7D">
	指定記事表示
	</td><td>
	<select name=iskin style="width:150px;">
	';
	for ($i = 0; $i <count($sid); $i++) {
		if ($vid == 2) {
			if ($v_sid[3] == $sid[$i]) {
				echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
			}else{
				echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
			}
		}elseif ($i == 0) {
			echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
		}else{
			echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
		}
	}
	echo '
	</select>
	</td></tr>
	</table>
	<table width="480" border="1" cellspacing="0" cellpadding="3" class="logbody">
		<tr><td colspan="4">月別表示</td></tr>
	';

	for ($m = 0; $m < 12; $m++) {
		if ($m == 0 || $m == 2 || $m == 4 || $m == 6 || $m == 8 || $m == 10) {
			echo '<tr><td width="50" align="center" bgcolor="#82BE7D">';
		}else{
			echo '<td width="50" align="center" bgcolor="#82BE7D">';
		}
		$month = $m + 1;
		echo '
		'.$month.'月
		</td><td>
		<select name=mskin['.$m.'] style="width:150px;">
		';
		for ($i = 0; $i < count($sid); $i++) {
			if ($vid == 2) {
				if ($v_sid[$m+4] == $sid[$i]) {
					echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
				}else{
					echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
				}
			}elseif ($i == 0) {
				echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
			}else{
				echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
			}
		}
		echo '
		</select>
		';
		if ($m == 0 || $m == 2 || $m == 4 || $m == 6 || $m == 8 || $m == 10) {
			echo '</td>';
		}else{
			echo '</td></tr>';
		}
	}

	echo '
	</table>
	<table width="480" border="1" cellspacing="0" cellpadding="3" class="logbody">
		<tr><td colspan="2">カテゴリ別表示</td></tr>
	';

	if (file_exists(LOGDIR."category.dat")){
		$category = file(LOGDIR."category.dat");
	}

	for ($c = -1; $c < count($category); $c++) {
		if ($c == -1) {
			$cid = -1;
			$cname = "指定無し";
		}else{
			$category[$c] = ereg_replace( "\n$", "", $category[$c] );
			$category[$c] = ereg_replace( "\r$", "", $category[$c] );
			list($cid, $cname) = explode("<>",$category[$c]);
		}
		echo '
		<tr><td width="230" bgcolor="#82BE7D">'.$cname.'
		</td><td>
		<select name=cskin['.$cid.'] style="width:150px;">
		';
		for ($i = 0; $i < count($sid); $i++) {
			if ($vid == 2) {
				if ($v_sid[$c+17] == $sid[$i]) {
					echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
				}else{
					echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
				}
			}elseif ($i == 0) {
				echo '<option value="'.$sid[$i].'" selected>'.$sname[$i].'</option>';
			}else{
				echo '<option value="'.$sid[$i].'">'.$sname[$i].'</option>';
			}
		}
		echo '
		</select>
		</td></tr>
		';
	}

	echo '
	</table>
	</div><br>
	<div align="center"><input type="submit" value="スキンを登録する"></div>
	</td></tr>
	</table>
	</form>
	</td></tr>
	</table>
	</td></tr>
	</table>
	';
}


/* トラックバック送信 */
function trackback_send($ping_url, $client_url, $title, $excerpt) {
	$return_code = "";
	$blog_name = SITENAME;
		// 改行文字の統一。 
	$excerpt = str_replace( "<br>",  "\r\n", $excerpt); 
	$excerpt = str_replace( "<br />",  "\r\n", $excerpt);

	$excerpt = CleanHtml($excerpt);

//	$excerpt = nl2br($excerpt);
//	$excerpt = str_replace("\n", "", $excerpt);
	if (strlen($excerpt) > 255) $excerpt = mbtrim($excerpt,255);	// Movable Type仕様。255バイト以上の場合省略
	$title = mbConv($title,0,4);
	$excerpt = mbConv($excerpt,0,4);
	$blog_name = mbConv($blog_name,0,4);
	$post = "title=".urlencode($title)."&url=".$client_url."&excerpt=".urlencode($excerpt)."&blog_name=".urlencode($blog_name)."&charset=utf-8";

	$ping_url = str_replace("\r$", "", $ping_url);
	$ping_url = ereg_replace("\n$", "", $ping_url);
	$ping_urls = explode("\n", $ping_url);
	$return_code = "";
	for ($i = 0; $i < count($ping_urls); $i++) {
		$cnt = $i + 1;
		$ping = parse_url($ping_urls[$i]);
		$req  = "POST ".$ping_urls[$i]." HTTP/1.0\r\n";
		$req .= "Host: ".$ping['host']."\r\n";
		$req .= "User-Agent:blogn-trackback\r\n";
		$req .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$req .= "Content-Length: ".strlen($post)."\r\n";
		$req .= "\r\n";
		$req .= $post."\r\n";
		if (!$ping["port"]) $ping["port"] = 80;
		$fp = fsockopen($ping["host"], $ping["port"], $errno, $errstr, 10);
		if ($fp) {
			fputs ($fp, $req);
			while (!feof($fp)) {
				$res = fgets($fp, 4096);
			}
			fclose($fp);
			$str = $res;
			if(preg_match("/<error>0<\/error>/",$str)){
				$return_code .= "<br>トラックバック送信".$cnt."件目：送信完了";
			}else{
//				$req  = "GET ".trim($ping_urls[$i])."?".$post. " HTTP/1.0\r\n";
//				$req .= "Host: ".$ping['host']."\r\n";
//    		$req .= "User-Agent: PHP/".phpversion()."\r\n\r\n";
//				$fp = @fsockopen($ping["host"], $ping["port"], $errno, $errstr, 10);
				$req = trim($ping_urls[$i])."?".$post;
				$fp = @fopen($req, "r");
				if ($fp) {
					fputs ($fp, $req);
					$res = "";
					while (!feof($fp)) {
						$res .= fgets($fp, 4096);
					}
					fclose($fp);
					$str = $res;
					if(preg_match("/<error>0<\/error>/",$str)){
						$return_code .= "<br>トラックバック送信".$cnt."件目：送信完了\r\n";
					}
				}else{
					$return_code .= "<br>トラックバック送信".$cnt."件目：CONNECTION ERROR\r\n";
				}
			}
		}else{
			$return_code .= "<br>トラックバック送信".$cnt."件目：CONNECTION ERROR";
		}
	}
	return $return_code;

}


/* weblogUpdates.ping 送信 */
function ping_send($ping_url){
	$return_code = "";
	$blog_name = SITENAME;
	$port = 80;
	$return_code = "";
	for ($i = 0; $i < count($ping_url); $i++) {
		$ping_url[$i] = str_replace("\r$", "", $ping_url[$i]);
		$ping_url[$i] = ereg_replace("\n$", "", $ping_url[$i]);
		$cnt = $i + 1;
		$post = '<?xml version="1.0" encoding="UTF-8" ?>
<methodCall>
<methodName>weblogUpdates.ping</methodName>
<params>
<param>
<value>'.$blog_name.'</value>
</param>
<param>
<value>'.HOMELINK.'</value>
</param>
</params>
</methodCall>';
		$post = mbConv($post,0,4);
		$ping = parse_url($ping_url[$i]);
		$req  = "POST ".$ping_url[$i]." HTTP/1.0\r\n";
		$req .= "Host: ".$ping['host']."\r\n";
		$req .= "User-Agent:blogn-send-ping\r\n";
		$req .= "Content-Type: text/xml\r\n";
		$req .= "Content-Length: ".strlen($post)."\r\n";
		$req .= "\r\n";
		$req .= $post."\r\n";
		$fp = fsockopen($ping["host"], $port, $errno, $errstr, 10);
		if ($fp) {
			fputs ($fp, $req);
			while (!feof($fp)) {
				$res = fgets($fp, 4096);
			}
			fclose($fp);
			$return_code .= "<br>Ping送信".$cnt."件目：送信完了";
		}else{
			$return_code .= "<br>Ping送信".$cnt."件目：CONNECTION ERROR";
		}
	}
	return $return_code;
}


?>
