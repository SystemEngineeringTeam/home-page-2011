<?php
//--------------------------------------------------------------------
// *** Recent Comments Control Module ***
// LAST UPDATE: 2006/12/30
// Version    : 1.10
// Copyright  : nJOY
// http://njoy.pekori.to/
//--------------------------------------------------------------------
//
// control.php
//
//--------------------------------------------------------------------

global $blogn_admin;
if ($blogn_admin) {

require "./".BLOGN_MODDIR."rccm/function.php";

if ($_POST["action"] == "delete") {
	blogn_mod_db_comment_delete($_POST["cmtid"]);
}

echo '
<!-- 参考： http://www9.plala.or.jp/oyoyon/html/script/hidden01.html -->
<script type="text/javascript">
<!--
window.onload = function() {
	display_tag = document.getElementsByTagName("blockquote");
}
function show(a) {
	var ele = display_tag.item(a);
	ele.style.display = (ele.style.display == "none") ? "block" : "none";
}
// -->
</script>

<div class="blogn_main">
<h1 style="font-size:150%; text-align: center; margin:0;">最新コメント管理モジュール (ver1.10)</h1>
<div class="blogn_bar">コメント最新'.RCCM_VIEW_NUMBER.'件</div>';

$cmtlist = blogn_mod_db_comment_load_for_new($user, 0, RCCM_VIEW_NUMBER);
if ($cmtlist[0]) {
	$i = 0;
	while (list($cmtkey, $cmtval) = each($cmtlist[1])) {
		$cmtc[$cmtval["entry_id"]] = $cmtval["entry_id"];
		$cmtt[$i] = $cmtval["entry_id"];
		$cmt[$i] = $cmtval;
		$i++;
	}
	$cmt_count = count($cmt);
	while (list($cmtkey, $cmtval) = each($cmtc)) {
		for ($i = 0; $i < $cmt_count; $i++) {
			if ($cmtval == $cmtt[$i]) $cmts[] = $cmt[$i];
		}
	}
	reset($cmts);
	$j = 0;
	while (list($cmtkey, $cmtval) = each($cmts)) {

	$name = get_magic_quotes_gpc() ? stripslashes($cmtval["name"]) : $cmtval["name"];
	$date = rccm_convert_date($cmtval["date"]);

	$logdata = blogn_mod_db_log_load_for_editor($cmtval["entry_id"]);
	$your_title = blogn_html_tag_convert($logdata[1]["title"]);
	$your_mes = strip_tags($logdata[1]["mes"], "<br>");
	$your_mes = blogn_mbtrim($your_mes, 255);
	$your_date = rccm_convert_date($logdata[1]["date"]);

	echo '
<div style="border:solid 1px #cccccc; padding:0 0 1em 0;">
<h2 style="background-color: #dcdcdc; font-size:120%; font-weight: normal; margin:0; padding: 0.5em;"><strong>'.$name.'</strong> ';

	if ($cmtval["url"]) echo '<a href="'.$cmtval["url"].'" target="_blank"><img src="./images/url.gif" alt="サイトへ" /></a> ';
	if ($cmtval["email"]) echo '<a href="mailto:'.$cmtval["email"].'"><img src="./images/email.gif" alt="メール" /></a> ';
	
echo '受信日時：'.$date.'<br />IP: '.$cmtval["ip"].', ブラウザ: '.$cmtval["agent"].'</h2>
<form action="./admin.php?mode=module&action=edit" method="post" style="float:right; margin: 1em 1em 0 0;">
<input type="hidden" name="blogn_module_name" value="rccm">
<input type="hidden" name="cmtid" value="'.$cmtval["id"].'">
<input type="hidden" name="action" value="delete">
<input type="submit" value="削除">
</form>
<p style="width:600px; padding-left:3em;">'.$cmtval["comment"].'</p>
<h3 style="font-size:120%; padding-left:1em; margin-bottom: 0;">[<a href="javascript:show('.$j.');" >＋</a>] 
元記事 [ID:'.$logdata[1]["id"].'] <a href="'.BLOGN_HOMELINK.'index.php?e='.$logdata[1]["id"].'" style="color:#636563;" target="_blank">'.$your_title.'</a> <span style="font-weight:normal;">('.$your_date.')</span></h3>
<blockquote style="width:600px; padding-left:3em; margin:1em 0 0 0;display:none;">'.$your_mes.'</p>
</div>
	';
	$j++;
	}
}

}

?>
