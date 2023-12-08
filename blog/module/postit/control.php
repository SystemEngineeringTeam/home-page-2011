<?php
//--------------------------------------------------------------------
// *** PostIt ***
// LAST UPDATE: 2007/01/27
// Version    : 2.00
// Copyright  : nJOY
// http://njoy.pekori.to/
//--------------------------------------------------------------------
//
// control.php
//
//--------------------------------------------------------------------

global $blogn_admin;
if ($blogn_admin) {

require "./".BLOGN_MODDIR."postit/function.php";
$inifile = BLOGN_MODDIR."postit/config.cgi";
$inis = file($inifile);
list($ini["break"], $ini["emoji"], $ini["datefmt"], $ini["number"], $ini["tatool"]) = explode(",", $inis[0]);

$i = 0;
for ($i = 1; $i < 11; $i++) {
	$infofile[$i] = BLOGN_MODDIR.'postit/postit'.$i.'.cgi';
}

if ($_POST["number"]) $number = $_POST["number"];
switch($_POST["method"]) {
	case "config":
		$ini["break"]    = $_POST["break"];
		$ini["emoji"]    = $_POST["emoji"];
		$ini["datefmt"]  = date_to_ini($_POST["datefmt"]);
		$ini["number"]   = $_POST["number"];
		$ini["tatool"]   = $_POST["tatool"];
		for ($i = 1; $i < 11; $i++) {
			$ini[$i] = array($_POST["use$i"], $_POST["home$i"], $_POST["entry$i"], $_POST["month$i"], $_POST["day$i"], $_POST["category$i"], $_POST["search$i"], $_POST["user$i"], $_POST["profile$i"]);
		}

		$fp = fopen($inifile, "w");
		$output = "";
		$output = $ini["break"].','.$ini["emoji"].','.$ini["datefmt"].','.$ini["number"].','.$_POST["tatool"].',';
		fwrite ($fp, "$output\r\n");
		for ($i = 1; $i < 11; $i++) {
			$output = $ini[$i][0].','.$ini[$i][1].','.$ini[$i][2].','.$ini[$i][3].','.$ini[$i][4].','.$ini[$i][5].','.$ini[$i][6].','.$ini[$i][7].','.$ini[$i][8].','.$ini[$i][9];
			fwrite($fp, "$output\r\n");
		}
		fclose($fp);

		$message = "設定を変更しました";
		break;
	case "post[$number]":
		$content = $_POST["mes$number"];
		$content = get_magic_quotes_gpc_ex($content);
		if ($ini["break"] == "br") {
			$content = str_replace("\r\n", "<br />", $content);
		}
		$fp = fopen($infofile[$number], "w");
		fputs ($fp, $content);
		fclose ($fp);
		break;	
	default:
		$message = "";
}

$inis = file($inifile);
list($ini["break"], $ini["emoji"], $ini["datefmt"], $ini["number"], $ini["tatool"]) = explode(",", $inis[0]);
$i = 0;
for ($i = 1; $i < 11; $i++) {
	list($ini[$i]["use"], $ini[$i]["home"], $ini[$i]["entry"], $ini[$i]["month"], $ini[$i]["day"], $ini[$i]["category"], $ini[$i]["search"], $ini[$i]["user"], $ini[$i]["profile"]) = explode(",", $inis[$i]); 
}
echo '<script type="text/javascript" src="'.BLOGN_HOMELINK.'module/postit/postit.js"></script>';
if ($ini["tatool"] == "1") echo '<script type="text/javascript" src="'.BLOGN_HOMELINK.'module/postit/resizable.js"></script>';
echo '<div class="blogn_main">
<h1 style="font-size:150%; text-align:center;">お知らせ表示モジュール ver2.00</h1>';

//------------------------------------------------------------------------------
//  お知らせ
//------------------------------------------------------------------------------

$mes = "";
for ($i = 1; $i < $ini["number"]; $i++) {
	if ($ini[$i]["use"] == "1") {
		$mes = file_get_contents($infofile[$i]);
		if ($ini["break"] == "br") {
			$mes = str_replace("<br />", "\r\n", $mes);
		}
		echo '
		<div class="blogn_bar">お知らせ '.$i.'</div>
		<div style="border:1px solid #b5b5b5; width:95%; padding:10px;">';

		if ($ini["emoji"] == "show") insert_emoji($i);
		insert_form($i, $mes, $ini["tatool"]);
	}
}

//------------------------------------------------------------------------------
//  設定画面
//------------------------------------------------------------------------------

echo '<hr />

<form method="post" action="./admin.php?mode=module&action=edit"">
<input type="hidden" name="blogn_module_name" value="postit" />

<div class="blogn_bar">現在の設定</div>
<table border="1" cellspacing="0" cellpadding="3" width="700">
<tr><td bgcolor="#dcdcdc" colspan="6"><strong>エディタ（全共通）</strong></td></tr>

<tr><td bgcolor="#e9e9e9">改行処理</td><td colspan="5">';
if ($ini["break"] == "br") {
	echo '
	<input type="radio" name="break" value="br" checked="checked" />自動改行する
	<input type="radio" name="break" value="rn" />自動改行しない';
} else {
	echo '
	<input type="radio" name="break" value="br" />自動改行する
	<input type="radio" name="break" value="rn" checked="checked" />自動改行しない';
}
echo '</td></tr>

<tr><td bgcolor="#e9e9e9">絵文字表示</td><td colspan="5">';
if ($ini["emoji"] == "show") {
	echo '
	<input type="radio" name="emoji" value="show" checked="checked" />表示する
	<input type="radio" name="emoji" value="hide" />表示しない';
} else {
	echo '
	<input type="radio" name="emoji" value="show" />表示する
	<input type="radio" name="emoji" value="hide" checked="checked" />表示しない';
}
echo '</td></tr>

<tr><td bgcolor="#e9e9e9">Textarea Tools</td><td colspan="5">';
if ($ini["tatool"] == "1") {
	echo '
	<input type="radio" name="tatool" value="1" checked="checked" />有効（お知らせ記入欄を拡大・縮小出来る）
	<input type="radio" name="tatool" value="0" />無効';
} else {
	echo '
	<input type="radio" name="tatool" value="1" />有効（お知らせ記入欄を拡大・縮小出来る）
	<input type="radio" name="tatool" value="0" checked="checked" />無効';
}
echo '</td></tr>

<tr><td bgcolor="#e9e9e9">使用するお知らせ</td><td colspan="5">';
if ($ini["number"] == "6") {
	echo '
	<input type="radio" name="number" value="6" checked="checked" />5個（1～5）
	<input type="radio" name="number" value="11" />全て（1～10）';
} else {
	echo '
	<input type="radio" name="number" value="6" />5個（1～5）
	<input type="radio" name="number" value="11" checked="checked" />全て（1～10）';
}
echo '</td></tr>

<tr><td bgcolor="#e9e9e9">日付フォーマット</td>
<td colspan="5"><input type="text" name="datefmt" value="'.date_from_ini($ini["datefmt"]).'" size="20" />（ %%DATE%% と記述する事でお知らせの最終更新日時を表示）</td></tr>

<tr><td bgcolor="#dcdcdc" colspan="6"><strong>表示設定（1～5）</strong></td></tr>
<tr bgcolor="#e9e9e9"><td><br /></td><td align="center"><b>お知らせ１<br />{POSTIT1}</b></td><td align="center"><b>お知らせ２<br />{POSTIT2}</b></td><td align="center"><b>お知らせ３<br />{POSTIT3}</b></td><td align="center"><b>お知らせ４<br />{POSTIT4}</b></td><td align="center"><b>お知らせ５<br />{POSTIT5}</b></td></tr>


<tr><td bgcolor="#e9e9e9">マスタースイッチ</td>';
for ($i = 1; $i < 6; $i++) {
	postit_mswitch($ini, $i);
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">初期表示</td>';
for ($i = 1; $i < 6; $i++) {
	postit_select3($ini, $i, "home");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">個別記事表示</td>';
for ($i = 1; $i < 6; $i++) {
	postit_select2($ini, $i, "entry");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">カテゴリー別表示</td>';
for ($i = 1; $i < 6; $i++) {
	postit_select3($ini, $i, "category");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">月別表示</td>';
for ($i = 1; $i < 6; $i++) {
	postit_select3($ini, $i, "month");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">日別初期表示</td>';
for ($i = 1; $i < 6; $i++) {
	postit_select3($ini, $i, "day");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">ユーザー表示</td>';
for ($i = 1; $i < 6; $i++) {
	postit_select3($ini, $i, "user");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">プロフィール</td>';
for ($i = 1; $i < 6; $i++) {
	postit_select2($ini, $i, "profile");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">検索表示</td>';
for ($i = 1; $i < 6; $i++) {
	postit_select2($ini, $i, "search");
}

echo '</tr>';

if ($ini["number"] == 11) {
	echo '
<tr><td bgcolor="#dcdcdc" colspan="6"><strong>表示設定（6～10）</strong></td></tr>
<tr bgcolor="#e9e9e9"><td><br /></td><td align="center"><b>お知らせ６<br />{POSTIT6}</b></td><td align="center"><b>お知らせ７<br />{POSTIT7}</b></td><td align="center"><b>お知らせ８<br />{POSTIT8}</b></td><td align="center"><b>お知らせ９<br />{POSTIT9}</b></td><td align="center"><b>お知らせ10<br />{POSTIT10}</b></td></tr>

<tr><td bgcolor="#e9e9e9">マスタースイッチ</td>';
for ($i = 6; $i < 11; $i++) {
	postit_mswitch($ini, $i);
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">初期表示</td>';
for ($i = 6; $i < 11; $i++) {
	postit_select3($ini, $i, "home");
}
echo'
</tr>

<tr><td bgcolor="#e9e9e9">個別記事表示</td>';
for ($i = 6; $i < 11; $i++) {
	postit_select2($ini, $i, "entry");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">カテゴリー別表示</td>';
for ($i = 6; $i < 11; $i++) {
	postit_select3($ini, $i, "category");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">月別表示</td>';
for ($i = 6; $i < 11; $i++) {
	postit_select3($ini, $i, "month");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">日別初期表示</td>';
for ($i = 6; $i < 11; $i++) {
	postit_select3($ini, $i, "day");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">ユーザー表示</td>';
for ($i = 6; $i < 11; $i++) {
	postit_select3($ini, $i, "user");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">プロフィール</td>';
for ($i = 6; $i < 11; $i++) {
	postit_select2($ini, $i, "profile");
}
echo '</tr>

<tr><td bgcolor="#e9e9e9">検索表示</td>';
for ($i = 6; $i < 11; $i++) {
	postit_select2($ini, $i, "search");
}
echo '</tr>';

}
echo'
</table>

<input type="hidden" name="method" value="config" />
<input type="submit" value="設定変更" style="margin: 0.5em;" /> <span style="color: #ff0000;">'.$message.'</span>
</form>

<p style="margin-left:2em;">※ 「自動改行する」から「自動改行しない」に設定を変更すると、改行のあった部分に &lt;br /&gt; が入ります。<br />※ 「Textarea Tools」は一部のブラウザ（例：Opera）で不具合が出ます。不具合が出る場合は、この機能を切ってください。</p>
</div>
';
}

function insert_emoji($n) {
	echo '<table summary="絵文字" style="margin:0px;padding:5px;">
<tr><td><img src="ico/face_c01.gif" onclick="icon(\'[:にこっ:]\',\''.$n.'\')" onkeypress="icon(\'[:にこっ:]\',\''.$n.'\')" width="13" height="13" alt="[:にこっ:]" style="border:0px;" />
<img src="ico/face_c02.gif" onclick="icon(\'[:にぱっ:]\',\''.$n.'\')" onkeypress="icon(\'[:にぱっ:]\',\''.$n.'\')" width="13" height="13" alt="[:にぱっ:]" style="border:0px;" />
<img src="ico/face_c03.gif" onclick="icon(\'[:にかっ:]\',\''.$n.'\')" onkeypress="icon(\'[:にかっ:]\',\''.$n.'\')" width="13" height="13" alt="[:にかっ:]" style="border:0px;" />
<img src="ico/face_c04.gif" onclick="icon(\'[:ぎょ:]\',\''.$n.'\')" onkeypress="icon(\'[:ぎょ:]\',\''.$n.'\')" width="13" height="13" alt="[:ぎょ:]" style="border:0px;" />
<img src="ico/face_c05.gif" onclick="icon(\'[:がーん:]\',\''.$n.'\')" onkeypress="icon(\'[:がーん:]\',\''.$n.'\')" width="13" height="13" alt="[:がーん:]" style="border:0px;" />
<img src="ico/face_c06.gif" onclick="icon(\'[:あうっ:]\',\''.$n.'\')" onkeypress="icon(\'[:あうっ:]\',\''.$n.'\')" width="13" height="13" alt="[:あうっ:]" style="border:0px;" />
<img src="ico/face_c07.gif" onclick="icon(\'[:きゅー:]\',\''.$n.'\')" onkeypress="icon(\'[:きゅー:]\',\''.$n.'\')" width="13" height="13" alt="[:きゅー:]" style="border:0px;" />
<img src="ico/face_c08.gif" onclick="icon(\'[:しくしく:]\',\''.$n.'\')" onkeypress="icon(\'[:しくしく:]\',\''.$n.'\')" width="13" height="13" alt="[:しくしく:]" style="border:0px;" />
<img src="ico/face_c09.gif" onclick="icon(\'[:はうー:]\',\''.$n.'\')" onkeypress="icon(\'[:はうー:]\',\''.$n.'\')" width="13" height="13" alt="[:はうー:]" style="border:0px;" />
<img src="ico/face_c10.gif" onclick="icon(\'[:ぎょーん:]\',\''.$n.'\')" onkeypress="icon(\'[:ぎょーん:]\',\''.$n.'\')" width="13" height="13" alt="[:ぎょーん:]" style="border:0px;" />
<img src="ico/face_c11.gif" onclick="icon(\'[:ぽわわ:]\',\''.$n.'\')" onkeypress="icon(\'[:ぽわわ:]\',\''.$n.'\')" width="13" height="13" alt="[:ぽわわ:]" style="border:0px;" />
<img src="ico/face_c12.gif" onclick="icon(\'[:ぽっ:]\',\''.$n.'\')" onkeypress="icon(\'[:ぽっ:]\',\''.$n.'\')" width="13" height="13" alt="[:ぽっ:]" style="border:0px;" />
<img src="ico/face_c13.gif" onclick="icon(\'[:てへっ:]\',\''.$n.'\')" onkeypress="icon(\'[:てへっ:]\',\''.$n.'\')" width="13" height="13" alt="[:てへっ:]" style="border:0px;" />
<img src="ico/face_c14.gif" onclick="icon(\'[:しょぼん:]\',\''.$n.'\')" onkeypress="icon(\'[:しょぼん:]\',\''.$n.'\')" width="13" height="13" alt="[:しょぼん:]" style="border:0px;" />
<img src="ico/face_c15.gif" onclick="icon(\'[:ぷん:]\',\''.$n.'\')" onkeypress="icon(\'[:ぷん:]\',\''.$n.'\')" width="13" height="13" alt="[:ぷん:]" style="border:0px;" />
<img src="ico/face_c16.gif" onclick="icon(\'[:ぷんすか:]\',\''.$n.'\')" onkeypress="icon(\'[:ぷんすか:]\',\''.$n.'\')" width="13" height="13" alt="[:ぷんすか:]" style="border:0px;" />
<img src="ico/face_c17.gif" onclick="icon(\'[:にひひ:]\',\''.$n.'\')" onkeypress="icon(\'[:にひひ:]\',\''.$n.'\')" width="13" height="13" alt="[:にひひ:]" style="border:0px;" />
<img src="ico/face_c18.gif" onclick="icon(\'[:むむっ:]\',\''.$n.'\')" onkeypress="icon(\'[:むむっ:]\',\''.$n.'\')" width="13" height="13" alt="[:むむっ:]" style="border:0px;" />
<img src="ico/face_c19.gif" onclick="icon(\'[:ニヒル:]\',\''.$n.'\')" onkeypress="icon(\'[:ニヒル:]\',\''.$n.'\')" width="13" height="13" alt="[:ニヒル:]" style="border:0px;" />
<img src="ico/face_c20.gif" onclick="icon(\'[:女性:]\',\''.$n.'\')" onkeypress="icon(\'[:女性:]\',\''.$n.'\')" width="13" height="13" alt="[:女性:]" style="border:0px;" />
<img src="ico/hand_11.gif" onclick="icon(\'[:パー:]\',\''.$n.'\')" onkeypress="icon(\'[:パー:]\',\''.$n.'\')" width="13" height="13" alt="[:パー:]" style="border:0px;" />
<img src="ico/hand_13.gif" onclick="icon(\'[:グー:]\',\''.$n.'\')" onkeypress="icon(\'[:グー:]\',\''.$n.'\')" width="13" height="13" alt="[:グー:]" style="border:0px;" />
<img src="ico/hand_15.gif" onclick="icon(\'[:チョキ:]\',\''.$n.'\')" onkeypress="icon(\'[:チョキ:]\',\''.$n.'\')" width="13" height="13" alt="[:チョキ:]" style="border:0px;" />
<img src="ico/hand_09.gif" onclick="icon(\'[:オッケー:]\',\''.$n.'\')" onkeypress="icon(\'[:オッケー:]\',\''.$n.'\')" width="13" height="13" alt="[:オッケー:]" style="border:0px;" />
<img src="ico/hand_12.gif" onclick="icon(\'[:パンチ:]\',\''.$n.'\')" onkeypress="icon(\'[:パンチ:]\',\''.$n.'\')" width="13" height="13" alt="[:パンチ:]" style="border:0px;" />
<img src="ico/mark_01.gif" onclick="icon(\'[:love:]\',\''.$n.'\')" onkeypress="icon(\'[:love:]\',\''.$n.'\')" width="13" height="13" alt="[:love:]" style="border:0px;" />
<img src="ico/mark_05.gif" onclick="icon(\'[:ハート:]\',\''.$n.'\')" onkeypress="icon(\'[:ハート:]\',\''.$n.'\')" width="13" height="13" alt="[:ハート:]" style="border:0px;" />
<img src="ico/mark_06.gif" onclick="icon(\'[:ダイヤ:]\',\''.$n.'\')" onkeypress="icon(\'[:ダイヤ:]\',\''.$n.'\')" width="13" height="13" alt="[:ダイヤ:]" style="border:0px;" />
<img src="ico/mark_07.gif" onclick="icon(\'[:スペード:]\',\''.$n.'\')" onkeypress="icon(\'[:スペード:]\',\''.$n.'\')" width="13" height="13" alt="[:スペード:]" style="border:0px;" />
<img src="ico/mark_08.gif" onclick="icon(\'[:クラブ:]\',\''.$n.'\')" onkeypress="icon(\'[:クラブ:]\',\''.$n.'\')" width="13" height="13" alt="[:クラブ:]" style="border:0px;" />
<img src="ico/mark_09.gif" onclick="icon(\'[:！:]\',\''.$n.'\')" onkeypress="icon(\'[:！:]\',\''.$n.'\')" width="13" height="13" alt="[:！:]" style="border:0px;" />
<img src="ico/mark_10.gif" onclick="icon(\'[:初心者:]\',\''.$n.'\')" onkeypress="icon(\'[:初心者:]\',\''.$n.'\')" width="13" height="13" alt="[:初心者:]" style="border:0px;" />
<img src="ico/mark_11.gif" onclick="icon(\'[:メモ:]\',\''.$n.'\')" onkeypress="icon(\'[:メモ:]\',\''.$n.'\')" width="13" height="13" alt="[:メモ:]" style="border:0px;" />
<img src="ico/mark_13.gif" onclick="icon(\'[:汗:]\',\''.$n.'\')" onkeypress="icon(\'[:汗:]\',\''.$n.'\')" width="13" height="13" alt="[:汗:]" style="border:0px;" />
<img src="ico/mark_14.gif" onclick="icon(\'[:Zzz:]\',\''.$n.'\')" onkeypress="icon(\'[:Zzz:]\',\''.$n.'\')" width="13" height="13" alt="[:Zzz:]" style="border:0px;" />
<img src="ico/mark_16.gif" onclick="icon(\'[:ダッシュ:]\',\''.$n.'\')" onkeypress="icon(\'[:ダッシュ:]\',\''.$n.'\')" width="13" height="13" alt="[:ダッシュ:]" style="border:0px;" />
<img src="ico/mark_17.gif" onclick="icon(\'[:怒:]\',\''.$n.'\')" onkeypress="icon(\'[:怒:]\',\''.$n.'\')" width="13" height="13" alt="[:怒:]" style="border:0px;" />
<img src="ico/mark_21.gif" onclick="icon(\'[:プラス:]\',\''.$n.'\')" onkeypress="icon(\'[:プラス:]\',\''.$n.'\')" width="13" height="13" alt="[:プラス:]" style="border:0px;" />
<img src="ico/mark_22.gif" onclick="icon(\'[:マイナス:]\',\''.$n.'\')" onkeypress="icon(\'[:マイナス:]\',\''.$n.'\')" width="13" height="13" alt="[:マイナス:]" style="border:0px;" />
<img src="ico/mark_23.gif" onclick="icon(\'[:○:]\',\''.$n.'\')" onkeypress="icon(\'[:○:]\',\''.$n.'\')" width="13" height="13" alt="[:○:]" style="border:0px;" />
<img src="ico/mark_24.gif" onclick="icon(\'[:×:]\',\''.$n.'\')" onkeypress="icon(\'[:×:]\',\''.$n.'\')" width="13" height="13" alt="[:×:]" style="border:0px;" />
<img src="ico/mark_25.gif" onclick="icon(\'[:△:]\',\''.$n.'\')" onkeypress="icon(\'[:△:]\',\''.$n.'\')" width="13" height="13" alt="[:△:]" style="border:0px;" />
<img src="ico/mark_26.gif" onclick="icon(\'[:□:]\',\''.$n.'\')" onkeypress="icon(\'[:□:]\',\''.$n.'\')" width="13" height="13" alt="[:□:]" style="border:0px;" />
<img src="ico/mark_32.gif" onclick="icon(\'[:右:]\',\''.$n.'\')" onkeypress="icon(\'[:右:]\',\''.$n.'\')" width="13" height="13" alt="[:右:]" style="border:0px;" />
<img src="ico/mark_33.gif" onclick="icon(\'[:上:]\',\''.$n.'\')" onkeypress="icon(\'[:上:]\',\''.$n.'\')" width="13" height="13" alt="[:上:]" style="border:0px;" />
<img src="ico/mark_34.gif" onclick="icon(\'[:左:]\',\''.$n.'\')" onkeypress="icon(\'[:左:]\',\''.$n.'\')" width="13" height="13" alt="[:左:]" style="border:0px;" />
<img src="ico/mark_35.gif" onclick="icon(\'[:下:]\',\''.$n.'\')" onkeypress="icon(\'[:下:]\',\''.$n.'\')" width="13" height="13" alt="[:下:]" style="border:0px;" />
<img src="ico/mark_37.gif" onclick="icon(\'[:音符:]\',\''.$n.'\')" onkeypress="icon(\'[:音符:]\',\''.$n.'\')" width="13" height="13" alt="[:音符:]" style="border:0px;" />
<img src="ico/mark_41.gif" onclick="icon(\'[:ボックス１:]\',\''.$n.'\')" onkeypress="icon(\'[:ボックス１:]\',\''.$n.'\')" width="13" height="13" alt="[:ボックス１:]" style="border:0px;" />
<img src="ico/mark_43.gif" onclick="icon(\'[:ボックス２:]\',\''.$n.'\')" onkeypress="icon(\'[:ボックス２:]\',\''.$n.'\')" width="13" height="13" alt="[:ボックス２:]" style="border:0px;" />
<img src="ico/weather_01.gif" onclick="icon(\'[:太陽:]\',\''.$n.'\')" onkeypress="icon(\'[:太陽:]\',\''.$n.'\')" width="13" height="13" alt="[:太陽:]" style="border:0px;" />
<img src="ico/weather_02.gif" onclick="icon(\'[:雲:]\',\''.$n.'\')" onkeypress="icon(\'[:雲:]\',\''.$n.'\')" width="13" height="13" alt="[:雲:]" style="border:0px;" />
<img src="ico/weather_03.gif" onclick="icon(\'[:曇り:]\',\''.$n.'\')" onkeypress="icon(\'[:曇り:]\',\''.$n.'\')" width="13" height="13" alt="[:曇り:]" style="border:0px;" />
<img src="ico/weather_04.gif" onclick="icon(\'[:晴れのち曇り:]\',\''.$n.'\')" onkeypress="icon(\'[:晴れのち曇り:]\',\''.$n.'\')" width="13" height="13" alt="[:晴れのち曇り:]" style="border:0px;" />
<img src="ico/weather_06.gif" onclick="icon(\'[:曇りのち雨:]\',\''.$n.'\')" onkeypress="icon(\'[:曇りのち雨:]\',\''.$n.'\')" width="13" height="13" alt="[:曇りのち雨:]" style="border:0px;" />
<img src="ico/weather_07.gif" onclick="icon(\'[:雨:]\',\''.$n.'\')" onkeypress="icon(\'[:雨:]\',\''.$n.'\')" width="13" height="13" alt="[:雨:]" style="border:0px;" />
<img src="ico/weather_09.gif" onclick="icon(\'[:雷:]\',\''.$n.'\')" onkeypress="icon(\'[:雷:]\',\''.$n.'\')" width="13" height="13" alt="[:雷:]" style="border:0px;" />
<img src="ico/weather_10.gif" onclick="icon(\'[:雪:]\',\''.$n.'\')" onkeypress="icon(\'[:雪:]\',\''.$n.'\')" width="13" height="13" alt="[:雪:]" style="border:0px;" />
<img src="ico/weather_11.gif" onclick="icon(\'[:月:]\',\''.$n.'\')" onkeypress="icon(\'[:月:]\',\''.$n.'\')" width="13" height="13" alt="[:月:]" style="border:0px;" />
<img src="ico/weather_12.gif" onclick="icon(\'[:星:]\',\''.$n.'\')" onkeypress="icon(\'[:星:]\',\''.$n.'\')" width="13" height="13" alt="[:星:]" style="border:0px;" />
<img src="ico/animal_01.gif" onclick="icon(\'[:ねこ:]\',\''.$n.'\')" onkeypress="icon(\'[:ねこ:]\',\''.$n.'\')" width="13" height="13" alt="[:ねこ:]" style="border:0px;" />
<img src="ico/animal_02.gif" onclick="icon(\'[:いぬ:]\',\''.$n.'\')" onkeypress="icon(\'[:いぬ:]\',\''.$n.'\')" width="13" height="13" alt="[:いぬ:]" style="border:0px;" />
<img src="ico/animal_04.gif" onclick="icon(\'[:にわとり:]\',\''.$n.'\')" onkeypress="icon(\'[:にわとり:]\',\''.$n.'\')" width="13" height="13" alt="[:にわとり:]" style="border:0px;" />
<img src="ico/animal_06.gif" onclick="icon(\'[:ひよこ:]\',\''.$n.'\')" onkeypress="icon(\'[:ひよこ:]\',\''.$n.'\')" width="13" height="13" alt="[:ひよこ:]" style="border:0px;" />
<img src="ico/animal_07.gif" onclick="icon(\'[:パンダ:]\',\''.$n.'\')" onkeypress="icon(\'[:パンダ:]\',\''.$n.'\')" width="13" height="13" alt="[:パンダ:]" style="border:0px;" />
<img src="ico/animal_08.gif" onclick="icon(\'[:ねずみ:]\',\''.$n.'\')" onkeypress="icon(\'[:ねずみ:]\',\''.$n.'\')" width="13" height="13" alt="[:ねずみ:]" style="border:0px;" />
<img src="ico/animal_09.gif" onclick="icon(\'[:きつね:]\',\''.$n.'\')" onkeypress="icon(\'[:きつね:]\',\''.$n.'\')" width="13" height="13" alt="[:きつね:]" style="border:0px;" />
<img src="ico/animal_11.gif" onclick="icon(\'[:うし:]\',\''.$n.'\')" onkeypress="icon(\'[:うし:]\',\''.$n.'\')" width="13" height="13" alt="[:うし:]" style="border:0px;" />
<img src="ico/animal_12.gif" onclick="icon(\'[:ぶた:]\',\''.$n.'\')" onkeypress="icon(\'[:ぶた:]\',\''.$n.'\')" width="13" height="13" alt="[:ぶた:]" style="border:0px;" />
<img src="ico/animal_13.gif" onclick="icon(\'[:うさぎ:]\',\''.$n.'\')" onkeypress="icon(\'[:うさぎ:]\',\''.$n.'\')" width="13" height="13" alt="[:うさぎ:]" style="border:0px;" />
<img src="ico/build_01.gif" onclick="icon(\'[:家:]\',\''.$n.'\')" onkeypress="icon(\'[:家:]\',\''.$n.'\')" width="13" height="13" alt="[:家:]" style="border:0px;" />
<img src="ico/build_03.gif" onclick="icon(\'[:病院:]\',\''.$n.'\')" onkeypress="icon(\'[:病院:]\',\''.$n.'\')" width="13" height="13" alt="[:病院:]" style="border:0px;" />
<img src="ico/build_05.gif" onclick="icon(\'[:工場:]\',\''.$n.'\')" onkeypress="icon(\'[:工場:]\',\''.$n.'\')" width="13" height="13" alt="[:工場:]" style="border:0px;" />
<img src="ico/build_06.gif" onclick="icon(\'[:ビル:]\',\''.$n.'\')" onkeypress="icon(\'[:ビル:]\',\''.$n.'\')" width="13" height="13" alt="[:ビル:]" style="border:0px;" />
<img src="ico/build_07.gif" onclick="icon(\'[:郵便局:]\',\''.$n.'\')" onkeypress="icon(\'[:郵便局:]\',\''.$n.'\')" width="13" height="13" alt="[:郵便局:]" style="border:0px;" />
<img src="ico/vehicle_01.gif" onclick="icon(\'[:車:]\',\''.$n.'\')" onkeypress="icon(\'[:車:]\',\''.$n.'\')" width="13" height="13" alt="[:車:]" style="border:0px;" />
<img src="ico/vehicle_05.gif" onclick="icon(\'[:バス:]\',\''.$n.'\')" onkeypress="icon(\'[:バス:]\',\''.$n.'\')" width="13" height="13" alt="[:バス:]" style="border:0px;" />
<img src="ico/vehicle_06.gif" onclick="icon(\'[:電車:]\',\''.$n.'\')" onkeypress="icon(\'[:電車:]\',\''.$n.'\')" width="13" height="13" alt="[:電車:]" style="border:0px;" />
<img src="ico/vehicle_07.gif" onclick="icon(\'[:新幹線:]\',\''.$n.'\')" onkeypress="icon(\'[:新幹線:]\',\''.$n.'\')" width="13" height="13" alt="[:新幹線:]" style="border:0px;" />
<img src="ico/vehicle_08.gif" onclick="icon(\'[:バイク:]\',\''.$n.'\')" onkeypress="icon(\'[:バイク:]\',\''.$n.'\')" width="13" height="13" alt="[:バイク:]" style="border:0px;" />
<img src="ico/flour_02.gif" onclick="icon(\'[:花:]\',\''.$n.'\')" onkeypress="icon(\'[:花:]\',\''.$n.'\')" width="13" height="13" alt="[:花:]" style="border:0px;" />
<img src="ico/flour_04.gif" onclick="icon(\'[:チューリップ:]\',\''.$n.'\')" onkeypress="icon(\'[:チューリップ:]\',\''.$n.'\')" width="13" height="13" alt="[:チューリップ:]" style="border:0px;" />
<img src="ico/flour_08.gif" onclick="icon(\'[:四葉:]\',\''.$n.'\')" onkeypress="icon(\'[:四葉:]\',\''.$n.'\')" width="13" height="13" alt="[:四葉:]" style="border:0px;" />
<img src="ico/drink_01.gif" onclick="icon(\'[:ビール:]\',\''.$n.'\')" onkeypress="icon(\'[:ビール:]\',\''.$n.'\')" width="13" height="13" alt="[:ビール:]" style="border:0px;" />
<img src="ico/drink_03.gif" onclick="icon(\'[:お酒:]\',\''.$n.'\')" onkeypress="icon(\'[:お酒:]\',\''.$n.'\')" width="13" height="13" alt="[:お酒:]" style="border:0px;" />
<img src="ico/drink_04.gif" onclick="icon(\'[:お茶:]\',\''.$n.'\')" onkeypress="icon(\'[:お茶:]\',\''.$n.'\')" width="13" height="13" alt="[:お茶:]" style="border:0px;" />
<img src="ico/drink_05.gif" onclick="icon(\'[:コーヒー:]\',\''.$n.'\')" onkeypress="icon(\'[:コーヒー:]\',\''.$n.'\')" width="13" height="13" alt="[:コーヒー:]" style="border:0px;" />
<img src="ico/drink_07.gif" onclick="icon(\'[:ジュース:]\',\''.$n.'\')" onkeypress="icon(\'[:ジュース:]\',\''.$n.'\')" width="13" height="13" alt="[:ジュース:]" style="border:0px;" />
<img src="ico/drink_08.gif" onclick="icon(\'[:ワイン:]\',\''.$n.'\')" onkeypress="icon(\'[:ワイン:]\',\''.$n.'\')" width="13" height="13" alt="[:ワイン:]" style="border:0px;" />
<img src="ico/drink_09.gif" onclick="icon(\'[:カクテル:]\',\''.$n.'\')" onkeypress="icon(\'[:カクテル:]\',\''.$n.'\')" width="13" height="13" alt="[:カクテル:]" style="border:0px;" />
<img src="ico/food_02.gif" onclick="icon(\'[:おにぎり:]\',\''.$n.'\')" onkeypress="icon(\'[:おにぎり:]\',\''.$n.'\')" width="13" height="13" alt="[:おにぎり:]" style="border:0px;" />
<img src="ico/food_03.gif" onclick="icon(\'[:ハンバーガー:]\',\''.$n.'\')" onkeypress="icon(\'[:ハンバーガー:]\',\''.$n.'\')" width="13" height="13" alt="[:ハンバーガー:]" style="border:0px;" />
<img src="ico/food_06.gif" onclick="icon(\'[:肉:]\',\''.$n.'\')" onkeypress="icon(\'[:肉:]\',\''.$n.'\')" width="13" height="13" alt="[:肉:]" style="border:0px;" />
<img src="ico/food_09.gif" onclick="icon(\'[:りんご:]\',\''.$n.'\')" onkeypress="icon(\'[:りんご:]\',\''.$n.'\')" width="13" height="13" alt="[:りんご:]" style="border:0px;" />
<img src="ico/food_23.gif" onclick="icon(\'[:プリン:]\',\''.$n.'\')" onkeypress="icon(\'[:プリン:]\',\''.$n.'\')" width="13" height="13" alt="[:プリン:]" style="border:0px;" />
<img src="ico/food_26.gif" onclick="icon(\'[:ケーキ:]\',\''.$n.'\')" onkeypress="icon(\'[:ケーキ:]\',\''.$n.'\')" width="13" height="13" alt="[:ケーキ:]" style="border:0px;" />
<img src="ico/food_29.gif" onclick="icon(\'[:パン:]\',\''.$n.'\')" onkeypress="icon(\'[:パン:]\',\''.$n.'\')" width="13" height="13" alt="[:パン:]" style="border:0px;" />
<img src="ico/food_30.gif" onclick="icon(\'[:カレー:]\',\''.$n.'\')" onkeypress="icon(\'[:カレー:]\',\''.$n.'\')" width="13" height="13" alt="[:カレー:]" style="border:0px;" />
<img src="ico/food_33.gif" onclick="icon(\'[:ラーメン:]\',\''.$n.'\')" onkeypress="icon(\'[:ラーメン:]\',\''.$n.'\')" width="13" height="13" alt="[:ラーメン:]" style="border:0px;" />
<img src="ico/item_36.gif" onclick="icon(\'[:にくきゅう:]\',\''.$n.'\')" onkeypress="icon(\'[:にくきゅう:]\',\''.$n.'\')" width="13" height="13" alt="[:にくきゅう:]" style="border:0px;" />
</td></tr></table>';
}

function insert_form($n, $message, $tatool) {
	echo '
<table summary="タグ" style="background-color:#cccccc;margin-bottom:0.5em;padding:5px;">
<tr><td>
<img src="./images/blank.gif" onclick="ins(1,0,'.$n.')" onkeypress="ins(1,0,'.$n.')" style="background-color:black;width:16px;height:16px;border:1px solid #000;" alt="黒" />
<img src="./images/blank.gif" onclick="ins(1,1,'.$n.')" onkeypress="ins(1,1,'.$n.')" style="background-color:brown;width:16px;height:16px;border:1px solid #000;" alt="茶" />
<img src="./images/blank.gif" onclick="ins(1,2,'.$n.')" onkeypress="ins(1,2,'.$n.')" style="background-color:red;width:16px;height:16px;border:1px solid #000;" alt="赤" />
<img src="./images/blank.gif" onclick="ins(1,3,'.$n.')" onkeypress="ins(1,3,'.$n.')" style="background-color:orange;width:16px;height:16px;border:1px solid #000;" alt="橙" />
<img src="./images/blank.gif" onclick="ins(1,4,'.$n.')" onkeypress="ins(1,4,'.$n.')" style="background-color:yellow;width:16px;height:16px;border:1px solid #000;" alt="黄" />
<img src="./images/blank.gif" onclick="ins(1,5,'.$n.')" onkeypress="ins(1,5,'.$n.')" style="background-color:green;width:16px;height:16px;border:1px solid #000;" alt="緑" />
<img src="./images/blank.gif" onclick="ins(1,6,'.$n.')" onkeypress="ins(1,6,'.$n.')" style="background-color:blue;width:16px;height:16px;border:1px solid #000;" alt="青" />
<img src="./images/blank.gif" onclick="ins(1,7,'.$n.')" onkeypress="ins(1,7,'.$n.')" style="background-color:violet;width:16px;height:16px;border:1px solid #000;" alt="紫" />
<img src="./images/blank.gif" onclick="ins(1,8,'.$n.')" onkeypress="ins(1,8,'.$n.')" style="background-color:gray;width:16px;height:16px;border:1px solid #000;" alt="灰" />
<img src="./images/blank.gif" onclick="ins(1,9,'.$n.')" onkeypress="ins(1,9,'.$n.')" style="background-color:white;width:16px;height:16px;border:1px solid #000;" alt="白" />
</td>
<td>
<img src="./images/blank.gif" width="4" height="1" alt="" />
<img src="./images/b.gif" onclick="ins(2,0,'.$n.')" onkeypress="ins(2,0,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="太字" />
<img src="./images/i.gif" onclick="ins(2,1,'.$n.')" onkeypress="ins(2,1,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="斜体" />
<img src="./images/u.gif" onclick="ins(2,2,'.$n.')" onkeypress="ins(2,2,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="下線" />
<img src="./images/s.gif" onclick="ins(2,3,'.$n.')" onkeypress="ins(2,3,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="取消線" />
<img src="./images/blank.gif" width="4" height="1" alt="" />
<img src="./images/left.gif" onclick="ins(2,4,'.$n.')" onkeypress="ins(2,4,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="左揃え" />
<img src="./images/center.gif" onclick="ins(2,5,'.$n.')" onkeypress="ins(2,5,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="中央揃え" />
<img src="./images/right.gif" onclick="ins(2,6,'.$n.')" onkeypress="ins(2,6,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="右揃え" />
<img src="./images/blank.gif" width="4" height="1" alt="" />
<img src="./images/p.gif" onclick="ins(2,7,'.$n.')" onkeypress="ins(2,7,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="段落" />
<img src="'.BLOGN_MODDIR.'postit/br.gif" onclick="icon(\'<br />\','.$n.')" onkeypress="icon(\'<br />\','.$n.')" class="blogn_image_tag" width="16" height="16" alt="改行" />
<img src="./images/blank.gif" width="4" height="1" alt="" />
<img src="./images/blockquote.gif" onclick="ins(2,8,'.$n.')" onkeypress="ins(2,8,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="引用文" />
<img src="./images/pre.gif" onclick="ins(2,9,'.$n.')" onkeypress="ins(2,9,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="そのまま表示" />
<img src="./images/blank.gif" width="4" height="1" alt="" />
<img src="./images/link.gif" onclick="linkins(0,'.$n.')" onkeypress="linkins(0,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="リンク" />
<img src="./images/mail.gif" onclick="linkins(1,'.$n.')" onkeypress="linkins(1,'.$n.')" class="blogn_image_tag" width="16" height="16" alt="メール" />
</td></tr>
</table>';

if ($tatool == "1") {
	$height = "100";
} else {
	$height = "160";
} 

echo '<form method="post" action="./admin.php?mode=module&action=edit" name="postit'.$n.'">
<input type="hidden" name="blogn_module_name" value="postit" />
<textarea cols="40" rows="8" style="width:95%;height:'.$height.'px;float:left;" id="mes'.$n.'" name="mes'.$n.'" wrap="virtual" class="resizable">'.htmlspecialchars($message).'</textarea>
<input type="hidden" name="method" value="post['.$n.']" /><br clear="left" />
<input type="hidden" name="number" value="'.$n.'" />
<input type="submit" value="お知らせ'.$n.'変更" style="margin: 0.5em;" />
</form>
</div>
';
}

function postit_select3($ini, $i, $select) {
	if ($ini[$i]["$select"]) {
		if ($ini[$i]["use"]) {
			$bgcolor = 'background-color:#ffff66;';
		} else {
			$bgcolor = 'background-color:#cccccc;';
		}
	}
	echo '
<td><select name="'.$select.$i.'" style="width:8em;margin:0;'.$bgcolor.'">';
	$selection = array("", "", "");
	if ($ini[$i]["$select"] == 1) {
		$selected[1] = ' selected="selected"';
	} elseif ($ini[$i]["$select"] == 2) {
		$selected[2] = ' selected="selected"';
	} else {
		$selected[0] = ' selected="selected"';
	}
	echo '
	<option value="0"'.$selected[0].'>非表示</option>
	<option value="1"'.$selected[1].'>表示（全頁）</option>
	<option value="2"'.$selected[2].'>表示（１頁）</option>
</select></td>';
}

function postit_select2($ini, $i, $select) {
	if ($ini[$i]["$select"]) {
		if ($ini[$i]["use"]) {
			$bgcolor = 'background-color:#ffff66;';
		} else {
			$bgcolor = 'background-color:#cccccc;';
		}
	}
	echo '
<td><select name="'.$select.$i.'" style="width:8em;margin:0;'.$bgcolor.'">';
	$selection = array("", "", "");
	if ($ini[$i]["$select"] == 1) {
		$selected[1] = ' selected="selected"';
	} else {
		$selected[0] = ' selected="selected"';
	}
	echo '
	<option value="0"'.$selected[0].'>非表示</option>
	<option value="1"'.$selected[1].'>表示</option>
</select></td>';
}

function postit_mswitch($ini, $i) {
	if ($ini[$i]["use"] == "1") {
		echo '
<td>
	<input type="radio" name="use'.$i.'" value="1" checked="checked" /><b style="color:#ff0000;">ON</b> 
	<input type="radio" name="use'.$i.'" value="0" />OFF
</td>';
	} else {
		echo '
<td>
	<input type="radio" name="use'.$i.'" value="1" />ON 
	<input type="radio" name="use'.$i.'" value="0" checked="checked" />OFF
</td>';
	}
}

function get_magic_quotes_gpc_ex($data) {
	if (get_magic_quotes_gpc()) {
		return stripslashes($data);
	}else{
		return $data;
	}
}

function date_to_ini($str) {
	$str = str_replace("(", "%%#40%%", $str);
	$str = str_replace(")", "%%#41%%", $str);
	$str = str_replace("|", "%%#124%%", $str);
	$str = str_replace(";", "", $str);
	$str = str_replace("\"", "", $str);
	$str = str_replace("\'", "", $str);
	$str = str_replace("\\", "", $str);
	return $str;
}

?>
