<?php
//------------------------------------------------------------------------
// Weblog PHP script BlognPlus（ぶろぐん＋）
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// pict.php
//
// LAST UPDATE 2006/09/21
//
// ・コメントが投稿できなかった問題を修正
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
$page = @$_GET["page"];
$action = @$_GET["action"]; 
if (!$page) $page = 1;

// 1ページの表示数
$pagecount = 5;


$start_key = ($page - 1) * $pagecount;
$end_key = $start_key + $pagecount;

//画像投稿処理
if ($action == "new") {
	$upfile = @$_FILES["blogn_upload_file"];
	if (empty($upfile)) {
		$error[0] = false;
		$error[1] = "アップロードするファイル名を入力してから追加ボタンを押してください。";
	}elseif (ereg("[\xA1-\xFE]", blogn_mbConv($upfile["name"], 4, 1))) {
		$error[0] = false;
		$error[1] = "日本語文字を含むファイル名はアップロードできません。半角英数文字でアップロードしてください。";
	}else{
		$file_name = $upfile["name"];
		$dest = BLOGN_FILEDIR.$file_name;
		$pathname = pathinfo($dest);
		$check_ext = str_replace(",", "|", BLOGN_PERMIT_FILE_TYPE);
		if (!eregi($check_ext, $pathname['extension'])) {
			$error[0] = false;
			$error[1] = "許可されていないファイルタイプです。アップロードを中止します。";
		}elseif ($upfile["size"] > BLOGN_MAX_FILESIZE * 1024) {
			$error[0] = false;
			$error[1] = "ファイルが指定されたサイズよりも大きいためアップロードを中止します。";
		}else{
			if (file_exists($dest)) {
				$file_name = gmdate("YmdHis",time() + BLOGN_TIMEZONE).".".$pathname['extension'];
				$dest = BLOGN_FILEDIR.$file_name;
			}
			$oldmask = umask();
			umask(000);
			if (!$error = @move_uploaded_file($upfile["tmp_name"], $dest)) {
				$error[0] = false;
				$error[1] = BLOGN_FILEDIR." ディレクトリにファイルを保存できませんでした。パーミッションを確認してください。";
			}else{
				$error = blogn_mod_db_file_add($user_id, $file_name, $_POST["blogn_comment"]);
				@chmod($dest,0666);
			}
			umask($oldmask);
		}
	}
}




$userlist = blogn_mod_db_user_load();
$filelist = blogn_mod_db_file_load(false, $user_id, $start_key, $end_key);

header("Content-Type: text/html; charset=UTF-8");
blogn_html_header();

reset($filelist[1]);
$i = 0;
while(list($key, $val) = each($filelist[1])) {
	echo 'picdir['.$i.'] = "'.BLOGN_FILEDIR.$val["file_name"].'";';
	echo 'picalt['.$i.'] = "'.$val["comment"].'";';

	if ($size = @getimagesize(BLOGN_FILEDIR.$val["file_name"])) {
		if ($size[0] > BLOGN_MAXWIDTH || $size[1] > BLOGN_MAXHEIGHT) {
			$ratio1 = BLOGN_MAXWIDTH / $size[0];
			$ratio2 = BLOGN_MAXHEIGHT / $size[1];
			if ($ratio1 < $ratio2) {
				$ratio = $ratio1;
			}else{
				$ratio = $ratio2;
			}
			$rwidth = round($size[0] * $ratio);
			$rheight = round($size[1] * $ratio);
			echo 'picwh['.$i.'] = "width=\"'.$rwidth.'\" height=\"'.$rheight.'\"";';
			echo 'picbs['.$i.'] = true;';
		}else{
			echo 'picwh['.$i.'] = "width=\"'.$size[0].'\" height=\"'.$size[1].'\"";';
			echo 'picbs['.$i.'] = false;';
		}
	}else{
		echo 'picwh['.$i.'] = "noimage";';
	}
	$i++;
}

blogn_html_java($type);

if ($action == "new") {
	// インフォメーション表示
	blogn_information_bar($error[0], $error[1]);
}

	echo '<div class="blogn_pict">';
if ($type == "p") {
	echo '<form action="./pict.php?type=p&action=new" enctype="multipart/form-data" method="post">';
}else{
	echo '<form action="./pict.php?action=new" enctype="multipart/form-data" method="post">';
}
echo '
<table class="blogn_user_list">
<tr bgcolor="#dcdcdc"><th width="50%">ファイル</th><th width="50%">コメント</th></tr>
<tr valign="top"><td width="50%"><input type="hidden" name="blogn_id" value="'.$user_id.'" /><input type="file" name="blogn_upload_file" size=30 style="width:96%" /><div class="blogn_comment">※投稿可能なファイルタイプは <b>'.BLOGN_PERMIT_FILE_TYPE.'</b> です。<br />※投稿可能なファイルの最大サイズは <b>'.BLOGN_MAX_FILESIZE.' KB</b> です。</div></td><td width="50%"><input type="text" name="blogn_comment" maxlength="50" style="width:96%;" /><div class="blogn_comment">※コメント欄はタグ使用不可です。</div></td></tr>
</table>
<div class="blogn_submit_type"><input type="submit" name="blogn_upload_file_add" value="追加"></div>
</form>
<br />
<form name="pict">
<table class="blogn_user_list">
<tr><th bgcolor="#dcdcdc">挿入</th><td>';
if ($type == "p") {
	echo 'プロフィール</td>';
}else{
	echo '<input type="radio" name="pins" value="mes" id="mes" checked><label for="mes">本文</label><input type="radio" name="pins" value="more" id="more"><label for="more">続き</label></td>';
}
echo '<th bgcolor="#dcdcdc">回り込み</th><td><input type="radio" name="pfloat" value="none" id="none" checked><label for="none">無し</label>
<input type="radio" name="pfloat" value="left" id="left"><label for="left">左回り</label>
<input type="radio" name="pfloat" value="right" id="right"><label for="right">右回り</label></td></tr>
</table>
<table class="blogn_user_list">
';

	if ($filelist[0]) {
		$i = 0;
		reset($filelist[1]);
		while (list($key, $val) = each($filelist[1])) {
			// ファイルの種類チェック
			if ($imagefile = @getimagesize(BLOGN_FILEDIR.$val["file_name"])) {
				// 画像の場合、リサイズ表示
				if ($imagefile[0] < 80 && $imagefile[1] < 80) {
					$width = $imagefile[0];
					$height = $imagefile[1];
				}elseif ($imagefile[0] > $imagefile[1]) {
					$width = 80;
					$height = round(80 * $imagefile[1] / $imagefile[0]);
				}else{
					$width = round(80 * $imagefile[0] / $imagefile[1]);
					$height = 80;
				}
				$imageurl = BLOGN_FILEDIR.$val["file_name"];
			}else{
				// それ以外の場合
				$width = 80;
				$height = 80;
				$imageurl = "./images/file.gif";
			}
			// ユーザー情報取得
			reset($userlist);
			while (list($user_key, $user_val) = each($userlist)) {
				if ($val["user_id"] == $user_key) {
					$user_name = $user_val["name"];
					break;
				}
			}

			echo '<tr><td width="100" align="center" style="border-right:0;">
<a href="javascript:pins('.$i.');">
<img src="'.$imageurl.'" width="'.$width.'" height="'.$height.'" border="0" />
</a>
</td>
<td width="*" style="border-left:0;border-right:0;">
<div style="font-size:16px;background-color:#e9e9e9;margin:0 0 12px 0;">
<a href="'.BLOGN_HOMELINK.BLOGN_FILEDIR.$val["file_name"].'" target="_blank">'.blogn_mbtrim(BLOGN_HOMELINK.BLOGN_FILEDIR.$val["file_name"],40).'</a>
</div>
<div style="font-size:12px;">サイズ：'.round(filesize(BLOGN_FILEDIR.$val["file_name"]) / 1024, 2).' KB</div>
<div style="font-size:12px;">投稿者：'.$user_name.'</div>
<div style="font-size:12px;">投稿日：'.date("Y/m/d H:i:s", filemtime(BLOGN_FILEDIR.$val["file_name"])).'</div>
<div style="font-size:16px;border:solid 1px;padding:2px;">'.$val["comment"].'</div>
</td></tr>';

			$i++;
		}
	}else{
		echo '<tr><td align="center" colspan="2">登録されたファイルはありません。</td></tr>';
	}
	echo '</table></form>';

	// ページ処理
	if ($filelist[2] != 0) {
		$max_page = ceil($filelist[2] / $pagecount);
	}else{
		$max_page = 0;
	}
	$typeurl = "";
	if ($type == "p") $typeurl = "&type=p";

	if ($filelist[2] > $pagecount) {
		echo '<div align="venter">Page: ';
		for ($i = 0; $i < $max_page; $i++) {
			$j = $i + 1;
			if ($j == $page) {
				echo '<a href="pict.php?page='.$j.$typeurl.'">['.$j.']</a> ';
			}else{
				echo '<a href="pict.php?page='.$j.$typeurl.'">'.$j.'</a> ';
			}
		}
		echo '</div>';
		
	}
	echo '</div>
<div align="center"><form><input type="button" value="閉じる" onclick="window.close()"></form></div>
</body>
</html>
';


//-----------------------------------------------------------------------------------------------
function blogn_html_header() {
	echo '
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
<meta http-equiv=content-type content="text/html; charset=UTF-8">
<title>画像挿入</title>
<meta name="copyright" content="blogn">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="Content-Style-Type" content="text/css">
<link rel="stylesheet" type="text/css" media="screen" href="admin.css">
<script language="JavaScript">
<!--
picdir = new Array();
picalt = new Array();
picwh = new Array();
picbs = new Array();
';
}

//-----------------------------------------------------------------------------------------------
function blogn_html_java($type) {
	if ($type == "p") {
		echo '
function pins(t) {
	var txt = window.opener.document.post.blogn_user_profile;
';
	}else{
		echo '
function pins(t) {
	if (document.pict.pins[0].checked) {
		var txt = window.opener.document.post.blogn_mes;
	}else{
		var txt = window.opener.document.post.blogn_more_mes;
	}
';
	}
echo <<<JAVA
	if (picwh[t] == "noimage") {
		var text = '<a href="' + picdir[t] + '">' + picalt[t] + '</a>';
	} else if (document.pict.pfloat[0].checked) {
		if (picbs[t]) {
				var text = '<a href="' + picdir[t] + '" target="_blank"><img src="' + picdir[t] + '" ' + picwh[t] + ' alt="' + picalt[t] + '"></a>';
		}else{
			var text = '<img src="' + picdir[t] + '" ' + picwh[t] + ' alt="' + picalt[t] + '">';
		}
	} else if (document.pict.pfloat[1].checked) {
		if (picbs[t]) {
			var text = '<a href="' + picdir[t] + '" target="_blank"><img src="' + picdir[t] + '" ' + picwh[t] + ' alt="' + picalt[t] + '" style="float:left;"></a>';
		}else{
			var text = '<img src="' + picdir[t] + '" ' + picwh[t] + ' alt="' + picalt[t] + '" style="float:left;">';
		}
	}else{
		if (picbs[t]) {
			var text = '<a href="' + picdir[t] + '" target="_blank"><img src="' + picdir[t] + '" ' + picwh[t] + ' alt="' + picalt[t] + '" style="float:right;"></a>';
		}else{
			var text = '<img src="' + picdir[t] + '" ' + picwh[t] + ' alt="' + picalt[t] + '" style="float:right;">';
		}
	}
	txt.focus();
	if (txt.createTextRange && txt.lastCaretPos) {
		var lastCaretPos = txt.lastCaretPos;
		lastCaretPos.text = lastCaretPos.text + text;
		txt.focus();
	} else if (txt.selectionStart) {
		txt.value = (txt.value).substring(0,txt.selectionStart) + text + (txt.value).substring(txt.selectionEnd, txt.textLength);
	}else{
		txt.value  += text;
		txt.focus();
	}
}
//-->
</script>
</head>
<body>
JAVA;

}

//-----------------------------------------------------------------------------------------------
function blogn_information_bar($flag, $information){
	// $flag : true = 正常
	//         false = エラー
	// $information : インフォメーションメッセージ
	echo '<div class="blogn_information">';
	if ($flag) {
		echo '<font color="blue">◎ </font>';
	}else{
		echo '<font color="red">× </font>';
	}
	echo $information.'</div>';
}
?>
