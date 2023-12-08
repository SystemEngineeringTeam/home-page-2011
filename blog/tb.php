<?php
//--------------------------------------------------------------------
// Weblog PHP script BlognPlus
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//--------------------------------------------------------------------
// tb.php
//
// LAST UPDATE 2007/02/22
//
// ・サーバーレスポンス処理を修正
//
//--------------------------------------------------------------------


//-------------------------------------------------------------------- 初期設定

/* ===== 初期設定ファイル読み込み ===== */
include("./conf.php");
include("./common.php");

$tmode = $_GET["__mode"];
if (!$tmode) {
	/* ----- Trackback受信メインルーチン ----- */
	if (!get_tb_id()) {
		res_xml(1,"No TrackBack ID (tb_id)");	// TrackBack IDが無い場合はエラー終了
	}else{
		$no = get_tb_id();
	}

	// ログファイル読み込み
	$log = blogn_mod_db_log_load_for_editor($no);

	/* トラックバック許可確認 */
	if ($log[1]["trackback_ok"] != "1") res_xml(1,"TrackBack is not Permitted");

	/* 有効期限の確認 */
	$diffdays = blogn_date_diff($log[1]["date"]);
	if (BLOGN_LIMIT_TRACKBACK && BLOGN_LIMIT_TRACKBACK < $diffdays) res_xml(1,"TrackBack is not Permitted");

	if (!$_POST["url"]) {
		res_xml(1,"No URL (url)");	// urlが無い場合はエラー終了
	}else{
		$url = $_POST["url"];
	}
	if (!$_POST["title"]) {
		$title = $url;
	}else{
		$title = $_POST["title"];
	}
	$title = blogn_mbConv($title, 0, 4);
	$excerpt = ($_POST["excerpt"] ? $_POST["excerpt"] : "");
	$excerpt = blogn_mbConv($excerpt, 0, 4);
	$excerpt = blogn_CleanHtml($excerpt);
	if (strlen($excerpt) > 255) $excerpt = blogn_mbtrim($excerpt,252)."...";	// Movable Type仕様。255バイト以上の場合省略
	$excerpt = strip_tags($excerpt);
	$blog_name = ($_POST["blog_name"] ? $_POST["blog_name"] : "");
	$blog_name = blogn_mbConv($blog_name, 0, 4);
	$ip_addr = $_SERVER["REMOTE_ADDR"];
	$user_agent = $_SERVER["HTTP_USER_AGENT"];
	$user_agent = blogn_mbConv($user_agent, 0, 4);
	$date = gmdate("YmdHis",time() + BLOGN_TIMEZONE);

	$trackback = blogn_mod_db_trackback_load_for_new(0, 10);
	// 重複投稿チェック
	$errflg = false;
	if ($trackback[0]) {
		while (list($key, $val) = each($trackback[1])) {
			if ($val["name"] == $blog_name && $val["url"] == $url) {
				$errflg = true;
				break;
			}
		}
	}

	if (!$errflg) {
		$error = blogn_mod_db_trackback_add($no, $date, blogn_html_tag_convert($blog_name), blogn_html_tag_convert($title), $url, blogn_html_tag_convert($excerpt), $ip_addr, $user_agent);
		if ($error[0]) {
			//トラックバック受信時に指定メールアドレスへ連絡
			$userlist = blogn_mod_db_user_load();

			$logdata = blogn_mod_db_log_load_for_editor($no);
			$sub = "トラックバックを受信しました";
			$sub = blogn_mbConv($sub, 4, 3);
			$sub = "=?iso-2022-jp?B?".base64_encode($sub)."?="; 
			$mes = "件名:".$logdata[1]["title"]."\n"; 
			$mes .= "投稿サイト名:".$blog_name."\n"; 
			$mes .= "URL:".BLOGN_HOMELINK."index.php?e=".$no."#trk".$error[2]."\n"; 
			$mes .= "※このメールアドレスには返信しないでください。"; 
			$mes = blogn_mbConv($mes, 4, 3); 
			$from = BLOGN_SITENAME; 
			$from = blogn_mbConv($from, 4, 3); 
			$from = "=?iso-2022-jp?B?".base64_encode($from)."?="; 
			$from = "From: $from <blognplus@localhost>\nContent-Type: text/plain; charset=\"iso-2022-jp\"";

			while (list($key, $val) = each($userlist)) {
				if ($val["information_trackback"]) @mail($val["information_mail_address"], $sub, $mes, $from);
			}
		}
		res_xml(0,"");
	}else{
		res_xml(1,"NO TrackBack ID(BLOG ID)");
	}
}elseif ($tmode == "rss") {
	if (!get_tb_id()) {
		res_xml(1,"No TrackBack ID (tb_id)");	// TrackBack IDが無い場合はエラー終了
	}else{
		$no = get_tb_id();
	}

	// ログファイル読み込み
	$log = blogn_mod_db_log_load_for_editor($no);

	/* トラックバック許可確認 */
	if ($log[1]["trackback_ok"] != "1") res_xml(1,"TrackBack is not Permitted");
	$urilen = strlen(BLOGN_HOMELINK);
	$reqfile = substr(strrchr(substr(BLOGN_HOMELINK, 0, $urilen - 1), "/"),1);
	$reqfilelen = strlen($reqfile);
	$reqdir = substr(BLOGN_HOMELINK, 0, $urilen - $reqfilelen - 1) ;
	$val = '<rss version="0.91"><channel><title>'.$log[1]["title"].'</title><link>'.$reqdir.'?e='.$eid.'</link><description>'.$log[1]["mes"].'</description><language>ja</language></channel></rss>';
	res_xml(0,$val);
	res_xml(1,"NO TrackBack ID(BLOG ID)");
}
exit;


/* ----- トラックバックID取得 ----- */
function get_tb_id() {
	if (!BLOGN_TRACKBACK_SLASH_TYPE) {
		if ($pi = $_SERVER['PATH_INFO']) {
			$tb_id = substr($pi, strrpos($pi, "/") + 1, strlen($pi));
		}
	}else{
		$tb_id = $_SERVER['QUERY_STRING'];
	}
	return $tb_id;
}


/* ----- サーバーレスポンス ----- */
function res_xml($key,$val) {
	$dat = '<?xml version="1.0" encoding="UTF-8" ?><response>';
	if($key == 1) {
		$dat .= '<error>1</error><br /><message>'.$val.'</message>';
	}else{
		$dat .= '<error>0</error>'.($val ? $val : '');
	}
	$dat .= '</response>';
	header("Content-Type: text/html; charset=UTF-8"); 
	echo $dat;
	exit;
}

?>
