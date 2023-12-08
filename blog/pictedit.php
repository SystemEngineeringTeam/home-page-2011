<?php
//------------------------------------------------------------------------
// Weblog PHP script BlognPlus（ぶろぐん＋）
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// pictedit.php
//
// LAST UPDATE 2005/09/29
//
// ・新規作成
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

$fname = @$_GET["name"];
$action = @$_GET["action"]; 


$filename = BLOGN_FILEDIR.$fname;
$pathname = pathinfo($filename);
$check_ext = "gif|png|jpg|jpeg";
if (!eregi($check_ext, $pathname['extension'])) {
	$error[0] = false;
	$error[1] = "GIF、PNG、JPGファイル以外の編集は出来ません。";
}else{
	list($src_w, $src_h)=getimagesize($filename);
	//画像投稿処理
	switch ($action) {
		case "turn":
			$degrees = $_POST["degrees"];
			$bcolor = $_POST["bcolor"];
			$col_r = hexdec(substr($_POST["bcolor"], 0, 2)); 
			$col_g = hexdec(substr($_POST["bcolor"], 2, 2)); 
			$col_b = hexdec(substr($_POST["bcolor"], 4, 2)); 

			if (eregi("gif", $pathname['extension'])) {
				$src_img = imagecreatefromgif($filename);
			}elseif (eregi("png", $pathname['extension'])) {
				$src_img = imagecreatefrompng($filename);
			}elseif (eregi("jpg|jpeg", $pathname['extension'])) {
				$src_img = imagecreatefromjpeg($filename);
			}
			$color = imagecolorallocate($src_img, $col_r, $col_g, $col_b);

			$src_img = imagerotate($src_img, $degrees, $color);
			if (eregi("gif", $pathname['extension'])) {
				$out_img = imagegif($src_img, $filename);
			}elseif (eregi("png", $pathname['extension'])) {
				$out_img = imagepng($src_img, $filename);
			}elseif (eregi("jpg|jpeg", $pathname['extension'])) {
				$out_img = imagejpeg($src_img, $filename);
			}
			imagedestroy($src_img); //メモリ解放
			$error[0] = true;
			$error[1] = "画像の回転に成功しました。";
			break;
		case "trim":
			$fup = (INT)$_POST["fup"];
			$fdown = (INT)$_POST["fdown"];
			$fleft = (INT)$_POST["fleft"];
			$fright = (INT)$_POST["fright"];
			if (eregi("gif", $pathname['extension'])) {
				$src_img = imagecreatefromgif($filename);
			}elseif (eregi("png", $pathname['extension'])) {
				$src_img = imagecreatefrompng($filename);
			}elseif (eregi("jpg|jpeg", $pathname['extension'])) {
				$src_img = imagecreatefromjpeg($filename);
			}
			$dst_w = $src_w - $fleft - $fright;
			$dst_h = $src_h - $fup - $fdown;

			$dst_img = imagecreatetruecolor($dst_w, $dst_h);
			imagecopy($dst_img, $src_img, 0, 0, $fleft, $fup, $src_w , $src_h);
			if (eregi("gif", $pathname['extension'])) {
				$out_img = imagegif($dst_img, $filename);
			}elseif (eregi("png", $pathname['extension'])) {
				$out_img = imagepng($dst_img, $filename);
			}elseif (eregi("jpg|jpeg", $pathname['extension'])) {
				$out_img = imagejpeg($dst_img, $filename);
			}
			imagedestroy($src_img); //メモリ解放
			imagedestroy($dst_img); //メモリ解放
			$error[0] = true;
			$error[1] = "画像のトリムに成功しました。";
			break;
		case "frame":
			$fup = (INT)$_POST["fup"];
			$fdown = (INT)$_POST["fdown"];
			$fleft = (INT)$_POST["fleft"];
			$fright = (INT)$_POST["fright"];
			$col_r = hexdec(substr($_POST["fcolor"], 0, 2)); 
			$col_g = hexdec(substr($_POST["fcolor"], 2, 2)); 
			$col_b = hexdec(substr($_POST["fcolor"], 4, 2)); 

			if (eregi("gif", $pathname['extension'])) {
				$src_img = imagecreatefromgif($filename);
			}elseif (eregi("png", $pathname['extension'])) {
				$src_img = imagecreatefrompng($filename);
			}elseif (eregi("jpg|jpeg", $pathname['extension'])) {
				$src_img = imagecreatefromjpeg($filename);
			}
			$dst_w = $src_w + $fleft + $fright;
			$dst_h = $src_h + $fup + $fdown;

			$dst_img = imagecreatetruecolor($dst_w, $dst_h);
			$color = imagecolorallocate($dst_img, $col_r, $col_g, $col_b);
			ImageFilledRectangle($dst_img, 0, 0, $dst_w, $dst_h, $color); 
			imagecopy($dst_img, $src_img, $fleft, $fup, 0, 0, $src_w , $src_h);
			if (eregi("gif", $pathname['extension'])) {
				$out_img = imagegif($dst_img, $filename);
			}elseif (eregi("png", $pathname['extension'])) {
				$out_img = imagepng($dst_img, $filename);
			}elseif (eregi("jpg|jpeg", $pathname['extension'])) {
				$out_img = imagejpeg($dst_img, $filename);
			}
			imagedestroy($src_img); //メモリ解放
			imagedestroy($dst_img); //メモリ解放
			$error[0] = true;
			$error[1] = "フレーム作成に成功しました。";
			break;
	}
}




header("Content-Type: text/html; charset=UTF-8");
blogn_html_header();


if ($action != "") {
	// インフォメーション表示
	blogn_information_bar($error[0], $error[1]);
}

	echo '<div class="blogn_pict">';
echo '
<table class="blogn_user_list">
';

// ファイルの種類チェック
if ($imagefile = @getimagesize(BLOGN_FILEDIR.$fname)) {
	// 画像の場合、リサイズ表示
	if ($imagefile[0] < 160 && $imagefile[1] < 160) {
		$width = $imagefile[0];
		$height = $imagefile[1];
	}elseif ($imagefile[0] > $imagefile[1]) {
		$width = 160;
		$height = round(160 * $imagefile[1] / $imagefile[0]);
	}else{
		$width = round(160 * $imagefile[0] / $imagefile[1]);
		$height = 160;
	}
	$imageurl = BLOGN_FILEDIR.$fname;
}else{
	// それ以外の場合
	$width = 160;
	$height = 160;
	$imageurl = "./images/file.gif";
}
// ユーザー情報取得
list($usec, $sec) = explode(" ", microtime()); 
$dummy = (float)$usec + (float)$sec; 

echo '<tr>
<th bgcolor="#dcdcdc" width="400" align="center" colspan="2">画像編集</th>
</tr>
<tr>
<td colspan="2" style="border-bottom:0;">
<div style="font-size:16px;background-color:#e9e9e9;margin:0;border-bottom:0;">
<a href="'.BLOGN_HOMELINK.BLOGN_FILEDIR.$fname.'" target="_blank">'.blogn_mbtrim(BLOGN_HOMELINK.BLOGN_FILEDIR.$fname,60).'</a>
</div>
</td>
</tr><tr>
<td style="width:180px;text-align:center;border-top:0;border-right:0;">
<img src="'.$imageurl.'?dummy='.$dummy.'" width="'.$width.'" height="'.$height.'" border="0" />
</td>
<td style="width:220px;vertical-align:top;text-align:left;border-top:0;border-left:0;">
<div style="font-size:12px;width:215px;border:1px solid #cccccc;margin:5px;padding:5px;">
<form action="./pictedit.php?action=turn&amp;name='.$fname.'" name="pict" method="post">
角度：<input type="text" name="degrees" value="90" style="width:25px;" /> ° <span class="blogn_comment">※反時計回りです</span><br />
背景色： #<input type="text" name="bcolor" value="ffffff" style="width:50px;" /><br />
<div style="text-align:right;"><input type="submit" value="回転"></div>
</form>
</div>
<div style="font-size:12px;width:215px;border:1px solid #cccccc;margin:5px;padding:5px;">
<form action="./pictedit.php?action=trim&amp;name='.$fname.'" name="pict" method="post">
上：<input type="text" name="fup" value="1" style="width:25px;" /> 下：<input type="text" name="fdown" value="1" style="width:25px;" /> 左：<input type="text" name="fleft" value="1" style="width:25px;" /> 右：<input type="text" name="fright" value="1" style="width:25px;" /><br />
<div style="text-align:right;"><input type="submit" value="トリム"></div>
</form>
</div>
<div style="font-size:12px;width:215px;border:1px solid #cccccc;margin:5px;padding:5px;">
<form action="./pictedit.php?action=frame&amp;name='.$fname.'" name="pict" method="post">
上：<input type="text" name="fup" value="1" style="width:25px;" /> 下：<input type="text" name="fdown" value="1" style="width:25px;" /> 左：<input type="text" name="fleft" value="1" style="width:25px;" /> 右：<input type="text" name="fright" value="1" style="width:25px;" /><br />
色： #<input type="text" name="fcolor" value="000000" style="width:50px;" /><br />
<div style="text-align:right;"><input type="submit" value="フレーム"></div>
</form>
</div>
</td></tr>
</table>';

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
';
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
