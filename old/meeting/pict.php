<?php
//------------------------------------------------------------------------
// Weblog PHP script Blogn（ぶろぐん）
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// 投稿用画像一覧
//
// LAST UPDATE 2007/07/19 (1.9.7)
//
// ・ヘッダー出力を追加
//
//------------------------------------------------------------------------
/* ===== 共有ファイル読み込み */
require_once("./common.php");

session_start();
if(!logincheck($_SESSION['blogni'], $_SESSION['blognp'])) {
	echo 'セッションエラー。管理画面に入りなおしてください。';
	exit;
}

$upfile_name = @$_FILES["d_img"]["name"];
$upfile = @$_FILES["d_img"]["tmp_name"];

//画像投稿処理
if ($action == "post") {
	$inform = "";
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
}

if ($page == "") $page = 0;
//$pageから表示開始位置を算出
$pcnt = $page * 5;	//5件で1ページ表示
$page_st = 5 * $page + 1;
$page_ed = $page_st + 5;
$i = 0;
$exitkey = 0;

$dir_name = dir(PICDIR);
while($file_name = $dir_name->read()) {
	if (!is_dir($file_name)) {
		if ($size = @getimagesize(PICDIR.$file_name)) {
			if ($size[2] == 1 || $size[2] == 2 || $size[2] == 3) {
				if ($size[0] < 80 && $size[1] < 80) {
					$width = $size[0];
					$height = $size[1];
				}elseif ($size[0] > $size[1]) {
					$width = 80;
					$height = round(80 * $size[1] / $size[0]);
				}else{
					$width = round(80 * $size[0] / $size[1]);
					$height = 80;
				}
				$picdir[$i] = PICDIR.$file_name;
				$picwh[$i] = 'width="'.$width.'" height="'.$height.'"';
				$ftime[$i] = date("Y/m/d H:i:s", filemtime(PICDIR.$file_name));


				if ($size[0] > MAXWIDTH || $size[1] > MAXHEIGHT) {
					$ratio1 = MAXWIDTH / $size[0];
					$ratio2 = MAXHEIGHT / $size[1];
					if ($ratio1 < $ratio2) {
						$ratio = $ratio1;
					}else{
						$ratio = $ratio2;
					}
					$rwidth = round($size[0] * $ratio);
					$rheight = round($size[1] * $ratio);
					$picwhjs[$i] = 'width=\"'.$rwidth.'\" height=\"'.$rheight.'\"';
					$picbsjs[$i] = true;
				}else{
					$picwhjs[$i] = 'width=\"'.$size[0].'\" height=\"'.$size[1].'\"';
					$picbsjs[$i] = false;
				}

				$i++;
			}
		}
	}
}
@arsort($ftime);
header("Content-Type: text/html; charset=EUC-JP");
echo '
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
<title>画像挿入</title>
<meta http-equiv=content-type content="text/html; charset=EUC-JP">
<style type="text/css">
<!--
body {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	margin: 10px;
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
.maintitle {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 14px;
	font-weight: bolder;
	color: #414D7B;
	border-bottom: 1px dotted #CCCCCC;
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
.inputarea {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 10px;
	color: #000000;
	margin: 0px;
}
.mainstate {
	font-family: "Verdana", "Helvetica", "ＭＳ ゴシック", "Osaka", "ヒラギノ角ゴ Pro W3";
	font-size: 10px;
	color: #999999;
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
-->
</style>
<script language="JavaScript">
<!--
';
if (count($picdir) != 0) {
	$k = 0;
	echo '
		picdir = new Array();
		picwh = new Array();
		picbs = new Array();
	';
	$j = 1;
	while(list($key, $val) = each($ftime)) {
		if ($j >= $page_st && $j < $page_ed) {
			echo '
			picdir['.$k.'] = "'.$picdir[$key].'";
			picwh['.$k.'] = "'.$picwhjs[$key].'";
			picbs['.$k.'] = "'.$picbsjs[$key].'";
			';
			$k++;
//			if ($j >= $i) break;
		}
		$j++;
	}
}

echo '
function pins(t) {
	if (document.pict.pins[0].checked) {
		var txt = window.opener.document.post.d_mes;
	}else{
		var txt = window.opener.document.post.d_more;
	}
	if (document.pict.pfloat[0].checked) {
		if (picbs[t]) {
			var text = "<a href=\"" + picdir[t] + "\" target=\"_blank\"><img src=\"" + picdir[t] + "\" " + picwh[t] + "></a>";
		}else{
			var text = "<img src=\"" + picdir[t] + "\" " + picwh[t] + ">";
		}
	} else if (document.pict.pfloat[1].checked) {
		if (picbs[t]) {
			var text = "<a href=\"" + picdir[t] + "\" target=\"_blank\"><img src=\"" + picdir[t] + "\" " + picwh[t] + " style=\"float:left;\"></a>";
		}else{
			var text = "<img src=\"" + picdir[t] + "\" " + picwh[t] + " style=\"float:left;\">";
		}
	}else{
		if (picbs[t]) {
			var text = "<a href=\"" + picdir[t] + "\" target=\"_blank\"><img src=\"" + picdir[t] + "\" " + picwh[t] + " style=\"float:right;\"></a>";
		}else{
			var text = "<img src=\"" + picdir[t] + "\" " + picwh[t] + " style=\"float:right;\">";
		}
	}
	txt.focus();
	if (txt.createTextRange && txt.caretPos) {
		var caretPos = txt.caretPos;
		caretPos.text = caretPos.text + text;
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
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
';
if ($inform != "") {
	echo '<div class="maininfo">'.$inform.'</div>';
}
echo '
<div class="mainbody">
	<div class="maintitle">画像の追加挿入</div>
	<div class="linktitle">■ 画像アップロード ■</div>
	<table border="0" cellspacing="0" cellpadding="3" class="logbody">
		<form action="'.PHP_SELF.'?action=post" enctype="multipart/form-data" method=post>
		<tr>
			<td>
				<input type="file" name=d_img accept="image/jpeg,image/gif,image/png" size=30>
				<input type="submit" value="追加する">
			</td>
		</tr>
		</form>
	</table>
	<div class="mainstate">※ ファイル形式は .GIF　.JPG　.PNG でお願いします。</div>

	<table border="0" cellspacing="0" cellpadding="3" class="logbody">
		<form name=pict>
		<tr>
			<td>
				<div class="linktitle">■ アップロードされている画像一覧 ■</div>
				| 画像の挿入
				<input type="radio" name="pins" value="mes" id="mes" checked><label for="mes">本文</label>
				<input type="radio" name="pins" value="more" id="more"><label for="more">続き</label>
				 | 画像の回り込み
				<input type="radio" name="pfloat" value="none" id="none" checked><label for="none">無し</label>
				<input type="radio" name="pfloat" value="left" id="left"><label for="left">左回り</label>
				<input type="radio" name="pfloat" value="right" id="right"><label for="right">右回り</label> |
';

$loopcnt = 0;
if (count($picdir) == 0) {
	echo '登録されている画像はありません。';
}else{
	echo '<table border="0" cellspacing="0" cellpadding="0"><tr>';
	$j = 1;
	reset($ftime);
	while(list($key, $val) = each($ftime)) {
		if ($j >= $page_st && $j < $page_ed) {
			echo '
				<td align="center" valign="center" style="border:solid 1px;width:90px;height:90px">
				<a href="javascript:pins('.$loopcnt.');">
				<img src="'.$picdir[$key].'" '.$picwh[$key].' style="border:0px;">
				</a>
				</td>
			';
			$loopcnt++;
//			if ($j >= $i) break;
		}
		$j++;
	}
	echo '</tr></form></table>';
}
$pcnt_start = $pcnt + 1;  		// 表示開始位置
$pcnt_end = $pcnt + $loopcnt;		// 表示終了位置
$bpage = $page - 1;			// 前ページ
$npage = $page + 1;			// 次ページ
$imgcnt = $i;
if ($imgcnt != 0) {
	$mpage = ceil($imgcnt / 5);
}else{
	$mpage = 0;
}

if ($imgcnt != 0) {
	echo '<table width="450" border="0" cellspacing="0" cellpadding="0" class="logbody">';
	if ($page > 0 && $page < $mpage - 1) {
		echo '<tr><td align="left" width="100"><a href='.PHP_SELF.'?page='.$bpage.'>&lt;&lt;前のページ</a></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><a href='.PHP_SELF.'?page='.$npage.'>次のページ&gt;&gt;</a></td></tr>';
	}elseif($page > 0 && $page == $mpage - 1) { 
		echo '<tr><td align="left" width="100"><a href='.PHP_SELF.'?page='.$bpage.'>&lt;&lt;前のページ</a></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><img src="./images/blank.gif" width="1" height="1"></td></tr>';
	}elseif($page == 0 && $page < $mpage - 1) { 
		echo '<tr><td align="left" width="100"><img src="./images/blank.gif" width="1" height="1"></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><a href='.PHP_SELF.'?page='.$npage.'>次のページ&gt;&gt;</a></td></tr>';
	}elseif($page == 0 && $page == $mpage - 1) { 
		echo '<tr><td align="left" width="100"><img src="./images/blank.gif" width="1" height="1"></td><td align="center">全 '.$imgcnt.' 件中 '.$pcnt_start.' - '.$pcnt_end.' 件表示</td><td align="right" width="100"><img src="./images/blank.gif" width="1" height="1"></td></tr>';
	}
	echo '</table><br>';
	echo '<table width="450" border="0" cellspacing="0" cellpadding="0" class="logbody">';
	echo '<tr><td align="center">Page: ';
	for ($i = 0; $i < $mpage; $i++) {
		$j = $i + 1;
		if ($i == $page) {
			echo '<a href='.PHP_SELF.'?page='.$i.'>['.$j.']</a> ';
		}else{
			echo '<a href='.PHP_SELF.'?page='.$i.'>'.$j.'</a> ';
		}
	}
	echo '</td></tr></table>';
}
	
echo '
			</td>
		</tr>
	</table>
<div align="center"><form><input type="button" value="閉じる" onclick="window.close()"></form></div>
</div>
</body>
</html>
';

exit;


/* ログインチェック */
function logincheck($loginid, $loginpw){
	if (file_exists(LOGDIR."init.dat")){
		$init = file(LOGDIR."init.dat");
		//$initから改行コード削除
		$init[0] = ereg_replace( "\n$", "", $init[0]);
		$init[0] = ereg_replace( "\r$", "", $init[0]);
		list($id,$pw) = explode("<>", $init[0]);
		if ($loginid == $id && $loginpw == $pw) {
			return true;
		}else{
			return false;
		}
	}else{
		echo 'ファイルオープンエラー';
		exit;
	}
}

?>
