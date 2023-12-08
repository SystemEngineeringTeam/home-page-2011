<?php
//-------------------------------------------------------------------------
// Weblog PHP script BlognPlus（ぶろぐん＋）
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// PostgreSQL保存タイプ
//
// LAST UPDATE 2006/10/18
//
// ・記事のタイトル・カテゴリー更新処理を追加
// ・表示スキン登録処理を変更
// ・表示スキン削除処理を追加
// ・アクセス制限処理を修正
// ・カテゴリー管理に表示非表示項目を追加
//
//-------------------------------------------------------------------------


/* エラーメッセージデータ集 */
include("db_errmes.php");

// , → #44 文字変換
function blogn_mod_db_comma_change($val) {
	$val = ereg_replace(",", "&#44;", $val);
	return $val;
}

// #44 → , 文字変換
function blogn_mod_db_comma_restore($val) {
	$val = ereg_replace("&#44;", ",", $val);
	return $val;
}


/* \r\n | \n  → <br> 変換 */
function blogn_mod_db_rn2br($str) {
	$str = str_replace( "\r\n",  "\n", $str);		// 改行を統一する
	$str = str_replace( "\r",  "\n", $str);
	$str = nl2br($str);													// 改行文字の前に<br>を代入する
	$str = str_replace("\n",  "", $str);				// \nを文字列から消す。
	return $str;
}


function blogn_mod_db_cnv_dbstr($str) {
//	$str = blogn_html_tag_convert($str);
//	if (!get_magic_quotes_gpc()) $str = addslashes($str);
	$str = addslashes($str);
	return $str;
}




/* ----- 記事URL取得(NEXT & BACK) ----- */
function blogn_mod_db_log_nextback_url($user, $key_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT date FROM ".BLOGN_DB_PREFIX."_loglist WHERE id = ".$key_id;
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$date = $row["date"];

	if ($user) {
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist WHERE date > '".$date."' ORDER BY date ASC LIMIT 1";
	}else{
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist WHERE date > '".$date."' AND secret = 0 ORDER BY date ASC LIMIT 1";
	}
	$sqllog = pg_query($sql_connect, $qry);
	$next = pg_fetch_array($sqllog);

	if ($user) {
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist WHERE date < '".$date."' ORDER BY date DESC LIMIT 1";
	}else{
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist WHERE date < '".$date."' AND secret = 0 ORDER BY date DESC LIMIT 1";
	}
	$sqllog = pg_query($sql_connect, $qry);
	$back = pg_fetch_array($sqllog);

	if ($next || $back) {
		$url[0] = true;
	}else{
		$url[0] = false;
	}
	if ($next) {
		$url[1] = $next["id"];
	}else{
		$url[1] = -1;
	}
	if ($back) {
		$url[2] = $back["id"];
	}else{
		$url[2] = -1;
	}
	return $url;
}


/* ----- 記事件数ロード ----- */
function blogn_mod_db_archive_count_load($user, $key_count) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	if ($user) {
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist WHERE reserve = 0 OR (reserve = 1 AND date < '$nowdate') ORDER BY date DESC";
	}else{
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist WHERE secret = 0 AND (reserve = 0 OR (reserve = 1 AND date < '$nowdate')) ORDER BY date DESC";
	}
	$result = pg_query($sql_connect, $qry);
	$list = array();
	while ($row = pg_fetch_array($result)) {
		$date = substr($row["date"],0,6);
		if ($oldlist[$date]) {
			$oldlist[$date]++;
		}else{
			$oldlist[$date] = 1;
		}
	}
	if ($oldlist) {
		$list[0] = true;
		$i = 0;
		while(list($key, $val) = each($oldlist)) {
			$list[1][$key] = $val;
			$i++;
			if ($key_count == $i && $key_count != 0) break;
		}
	}else{
		$list[0] = false;
	}
	return $list;
}


/* ----- モード別記事件数ロード ----- */
function blogn_mod_db_log_count_load($user, $mode, $key_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$nowdate = date("YmdHis" ,time() + BLOGN_TIMEZONE);

	switch ($mode) {
		case "normal":
			if ($user) {
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE reserve = 0 OR (reserve = 1 AND date < '$nowdate')";
			}else{
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE secret = 0 AND (reserve = 0 OR (reserve = 1 AND date < '$nowdate'))";
			}
			break;
		case "month":
			$date = substr($key_id,0,6)."%";
			if ($user) {
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE date LIKE '".$date."' AND (reserve = 0 OR (reserve = 1 AND date < '$nowdate'))";
			}else{
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE date LIKE '".$date."' AND secret = 0 AND (reserve = 0 OR (reserve = 1 AND date < '$nowdate'))";
			}
			break;
		case "day":
			$date = substr($key_id,0,8)."%";
			if ($user) {
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE date LIKE '".$date."' AND (reserve = 0 OR (reserve = 1 AND date < '$nowdate'))";
			}else{
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE date LIKE '".$date."' AND secret = 0 AND (reserve = 0 OR (reserve = 1 AND date < '$nowdate'))";
			}
			break;
		case "category":
			list($check_id_1, $check_id_2) = explode("-", $key_id);
			if (!$check_id_2) $check_id_2 = "%";
			$check_id = $check_id_1."|".$check_id_2;
			if ($user) {
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE category_id LIKE '$check_id' AND (reserve = 0 OR (reserve = 1 AND date < '$nowdate'))";
			}else{
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE category_id LIKE '$check_id' AND secret = 0 AND (reserve = 0 OR (reserve = 1 AND date < '$nowdate'))";
			}
			break;
		case "cate_list":
			list($check_id_1, $check_id_2) = explode("-", $key_id);
			if (!$check_id_2) $check_id_2 = "%";
			$check_id = $check_id_1."|".$check_id_2;
			if ($user == -1) {
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE category_id LIKE '$check_id'";
			}else{
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE category_id LIKE '$check_id' AND userid = $user";
			}
			break;
		case "user":
			if ($user) {
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE user_id = '$key_id' AND (reserve = 0 OR (reserve = 1 AND date < '$nowdate'))";
			}else{
				$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist WHERE user_id = '$key_id' AND secret = 0 AND (reserve = 0 OR (reserve = 1 AND date < '$nowdate'))";
			}
			break;
	}
	$result = pg_query($sql_connect, $qry);
	$row = @pg_fetch_array($result);
	if ($row) {
		$listcount = $row[0];
	}else{
		$listcount = 0;
	}
	return $listcount;
}


/* ----- 記事ロード編集用（指定記事用） ----- */
function blogn_mod_db_log_load_for_editor($req_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE ".BLOGN_DB_PREFIX."_loglist.id = $req_id";
	$result = pg_query($sql_connect, $qry);

	$row = pg_fetch_array($result);
	if ($row[0]) {
		$newlog[0] = true;
		$newlog[1]["id"] = $row["id"];
		$newlog[1]["date"] = $row["date"];
		$newlog[1]["reserve"] = $row["reserve"];
		$newlog[1]["secret"] = $row["secret"];
		$newlog[1]["user_id"] = $row["user_id"];
		$newlog[1]["category"] = $row["category_id"];
		$newlog[1]["comment_ok"] = $row["comment_ok"];
		$newlog[1]["trackback_ok"] = $row["trackback_ok"];
		$newlog[1]["title"] = blogn_mod_db_comma_restore($row["title"]);
		$newlog[1]["mes"] = blogn_mod_db_comma_restore($row["mes"]);
		$newlog[1]["more"] = blogn_mod_db_comma_restore($row["more"]);
		$newlog[1]["br_change"] = $row["br_change"];
		return $newlog;
	}else{
		$newlog[0] = false;
		$newlog[1] = BLOGN_MOD_DB_MES_19;
		return $newlog;
	}
}

/* ----- 記事ロード表示用（指定記事用） ----- */
function blogn_mod_db_log_load_for_entory($user, $req_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	if ($user) {
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE ".BLOGN_DB_PREFIX."_loglist.id = $req_id";
	}else{
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE ".BLOGN_DB_PREFIX."_loglist.id = $req_id AND ".BLOGN_DB_PREFIX."_loglist.secret = 0";
	}
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	if ($row[0]) {
		$newlog[0] = true;
		$newlog[1]["id"] = $row["id"];
		$newlog[1]["date"] = $row["date"];
		$newlog[1]["reserve"] = $row["reserve"];
		$newlog[1]["secret"] = $row["secret"];
		$newlog[1]["user_id"] = $row["user_id"];
		$newlog[1]["category"] = $row["category_id"];
		$newlog[1]["comment_ok"] = $row["comment_ok"];
		$newlog[1]["trackback_ok"] = $row["trackback_ok"];
		$newlog[1]["title"] = blogn_mod_db_comma_restore($row["title"]);
		$newlog[1]["mes"] = blogn_mod_db_comma_restore($row["mes"]);
		$newlog[1]["more"] = blogn_mod_db_comma_restore($row["more"]);
		$newlog[1]["br_change"] = $row["br_change"];
		return $newlog;
	}else{
		$newlog[0] = false;
		$newlog[1] = BLOGN_MOD_DB_MES_19;
		return $newlog;
	}
}


/* ----- 記事ロード（エクスポート用） ----- */
function blogn_mod_db_log_load_for_all() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);
	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id ORDER BY ".BLOGN_DB_PREFIX."_loglist.date ASC";
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	while($row = pg_fetch_array($result)) {
		$newlog[0] = true;
		$newlog[1][$i]["id"] = $row["id"];
		$newlog[1][$i]["date"] = $row["date"];
		$newlog[1][$i]["reserve"] = $row["reserve"];
		$newlog[1][$i]["secret"] = $row["secret"];
		$newlog[1][$i]["user_id"] = $row["user_id"];
		$newlog[1][$i]["category"] = $row["category_id"];
		$newlog[1][$i]["comment_ok"] = $row["comment_ok"];
		$newlog[1][$i]["trackback_ok"] = $row["trackback_ok"];
		$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($row["title"]);
		$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($row["mes"]);
		$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($row["more"]);
		$newlog[1][$i]["br_change"] = $row["br_change"];
		$i++;
	}
	$newlog[2] = blogn_mod_db_log_count_load(1, "normal","");
	return $newlog;
}


/* ----- 記事ロード（一覧用） ----- */
function blogn_mod_db_log_load_for_list($uid, $start_key, $key_count) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$userdata = blogn_mod_db_user_profile_load($uid);
	if ($userdata["admin"]) {
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC OFFSET $start_key LIMIT $key_count";
	}else{
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE ".BLOGN_DB_PREFIX."_loglist.user_id = $uid ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC OFFSET $start_key LIMIT $key_count";
	}
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	$newlog[0] = false;
	while($row = pg_fetch_array($result)) {
		$newlog[0] = true;
		$newlog[1][$i]["id"] = $row["id"];
		$newlog[1][$i]["date"] = $row["date"];
		$newlog[1][$i]["reserve"] = $row["reserve"];
		$newlog[1][$i]["secret"] = $row["secret"];
		$newlog[1][$i]["user_id"] = $row["user_id"];
		$newlog[1][$i]["category"] = $row["category_id"];
		$newlog[1][$i]["comment_ok"] = $row["comment_ok"];
		$newlog[1][$i]["trackback_ok"] = $row["trackback_ok"];
		$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($row["title"]);
		$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($row["mes"]);
		$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($row["more"]);
		$newlog[1][$i]["br_change"] = $row["br_change"];
		$i++;
	}
	if ($newlog[0]) {
		if ($userdata["admin"]) {
			$newlog[2] = blogn_mod_db_log_count_load(1, "normal","");
		}else{
			$newlog[2] = blogn_mod_db_log_count_load(1, "user",$uid);
		}
	}else{
		$newlog[2] = 0;
	}
	return $newlog;
}


/* ----- 記事ロード（指定カテゴリ用） ----- */
function blogn_mod_db_log_load_list_for_category($user, $start_key, $key_count, $key_category) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$userdata = blogn_mod_db_user_profile_load($user);
	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	list($key_category_1, $key_category_2) = explode("-", $key_category);
	if ($key_category_2) {
		$check_key = $key_category_1."|".$key_category_2;
	}else{
		$check_key = $key_category_1."|%";
	}
	if ($userdata["admin"]) {
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE ".BLOGN_DB_PREFIX."_loglist.category_id LIKE '$check_key' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE ".BLOGN_DB_PREFIX."_loglist.category_id LIKE '$check_key' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC";
		}
	}else{
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE ".BLOGN_DB_PREFIX."_loglist.user_id = $user AND ".BLOGN_DB_PREFIX."_loglist.category_id LIKE '$check_key' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE ".BLOGN_DB_PREFIX."_loglist.user_id = $user AND ".BLOGN_DB_PREFIX."_loglist.category_id LIKE '$check_key' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC";
		}
	}
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	$newlog[0] = false;
	while($row = pg_fetch_array($result)) {
		$newlog[0] = true;
		$newlog[1][$i]["id"] = $row["id"];
		$newlog[1][$i]["date"] = $row["date"];
		$newlog[1][$i]["reserve"] = $row["reserve"];
		$newlog[1][$i]["secret"] = $row["secret"];
		$newlog[1][$i]["user_id"] = $row["user_id"];
		$newlog[1][$i]["category"] = $row["category_id"];
		$newlog[1][$i]["comment_ok"] = $row["comment_ok"];
		$newlog[1][$i]["trackback_ok"] = $row["trackback_ok"];
		$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($row["title"]);
		$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($row["mes"]);
		$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($row["more"]);
		$newlog[1][$i]["br_change"] = $row["br_change"];
		$i++;
	}
	if ($newlog[0]) {
		if ($userdata["admin"]) {
			$newlog[2] = blogn_mod_db_log_count_load(-1, "cate_list", $key_category);
		}else{
			$newlog[2] = blogn_mod_db_log_count_load($user, "cate_list", $key_category);
		}
	}else{
		$newlog[2] = 0;
	}
	return $newlog;
}


/* ----- 記事ロード（一般用） ----- */
function blogn_mod_db_log_load_for_viewer($user, $start_key, $key_count) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	if ($user) {
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC";
		}
	}else{
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.secret = 0 ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.secret = 0 ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC";
		}
	}
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	while($row = pg_fetch_array($result)) {
		$newlog[0] = true;
		$newlog[1][$i]["id"] = $row["id"];
		$newlog[1][$i]["date"] = $row["date"];
		$newlog[1][$i]["reserve"] = $row["reserve"];
		$newlog[1][$i]["secret"] = $row["secret"];
		$newlog[1][$i]["user_id"] = $row["user_id"];
		$newlog[1][$i]["category"] = $row["category_id"];
		$newlog[1][$i]["comment_ok"] = $row["comment_ok"];
		$newlog[1][$i]["trackback_ok"] = $row["trackback_ok"];
		$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($row["title"]);
		$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($row["mes"]);
		$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($row["more"]);
		$newlog[1][$i]["br_change"] = $row["br_change"];
		$i++;
	}
	return $newlog;
}


/* ----- 記事ロード（指定月用） ----- */
function blogn_mod_db_log_load_for_month($user, $start_key, $key_count, $key_date, $vorder) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	$checkdate = $key_date."%";
	if ($vorder) {
		$order_mode = "ASC";
	}else{
		$order_mode = "DESC";
	}
	if ($user) {
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.date LIKE '$checkdate' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date $order_mode OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.date LIKE '$checkdate' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date $order_mode";
		}
	}else{
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.secret = 0 AND ".BLOGN_DB_PREFIX."_loglist.date LIKE '$checkdate' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date $order_mode OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.secret = 0 AND ".BLOGN_DB_PREFIX."_loglist.date LIKE '$checkdate' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date $order_mode";
		}
	}
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	while($row = pg_fetch_array($result)) {
		$newlog[0] = true;
		$newlog[1][$i]["id"] = $row["id"];
		$newlog[1][$i]["date"] = $row["date"];
		$newlog[1][$i]["reserve"] = $row["reserve"];
		$newlog[1][$i]["secret"] = $row["secret"];
		$newlog[1][$i]["user_id"] = $row["user_id"];
		$newlog[1][$i]["category"] = $row["category_id"];
		$newlog[1][$i]["comment_ok"] = $row["comment_ok"];
		$newlog[1][$i]["trackback_ok"] = $row["trackback_ok"];
		$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($row["title"]);
		$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($row["mes"]);
		$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($row["more"]);
		$newlog[1][$i]["br_change"] = $row["br_change"];
		$i++;
	}
	return $newlog;
}


/* ----- 記事ロード（指定日用） ----- */
function blogn_mod_db_log_load_for_day($user, $start_key, $key_count, $key_date) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	$checkdate = $key_date."%";
	if ($user) {
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.date LIKE '$checkdate' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.date LIKE '$checkdate' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC";
		}
	}else{
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.secret = 0 AND ".BLOGN_DB_PREFIX."_loglist.date LIKE '$checkdate' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.secret = 0 AND ".BLOGN_DB_PREFIX."_loglist.date LIKE '$checkdate' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC";
		}
	}
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	while($row = pg_fetch_array($result)) {
		$newlog[0] = true;
		$newlog[1][$i]["id"] = $row["id"];
		$newlog[1][$i]["date"] = $row["date"];
		$newlog[1][$i]["reserve"] = $row["reserve"];
		$newlog[1][$i]["secret"] = $row["secret"];
		$newlog[1][$i]["user_id"] = $row["user_id"];
		$newlog[1][$i]["category"] = $row["category_id"];
		$newlog[1][$i]["comment_ok"] = $row["comment_ok"];
		$newlog[1][$i]["trackback_ok"] = $row["trackback_ok"];
		$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($row["title"]);
		$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($row["mes"]);
		$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($row["more"]);
		$newlog[1][$i]["br_change"] = $row["br_change"];
		$i++;
	}
	return $newlog;
}


/* ----- 記事ロード（指定カテゴリ用） ----- */
function blogn_mod_db_log_load_for_category($user, $start_key, $key_count, $key_category, $vorder) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	list($key_category_1, $key_category_2) = explode("-", $key_category);
	if ($key_category_2) {
		$check_key = $key_category_1."|".$key_category_2;
	}else{
		$check_key = $key_category_1."|%";
	}
	if ($vorder) {
		$order_mode = "ASC";
	}else{
		$order_mode = "DESC";
	}
	if ($user) {
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.category_id LIKE '$check_key' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date $order_mode OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.category_id LIKE '$check_key' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date $order_mode";
		}
	}else{
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.secret = 0 AND ".BLOGN_DB_PREFIX."_loglist.category_id LIKE '$check_key' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date $order_mode OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.secret = 0 AND ".BLOGN_DB_PREFIX."_loglist.category_id LIKE '$check_key' ORDER BY ".BLOGN_DB_PREFIX."_loglist.date $order_mode";
		}
	}
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	$newlog[0] = false;
	while($row = pg_fetch_array($result)) {
		$newlog[0] = true;
		$newlog[1][$i]["id"] = $row["id"];
		$newlog[1][$i]["date"] = $row["date"];
		$newlog[1][$i]["reserve"] = $row["reserve"];
		$newlog[1][$i]["secret"] = $row["secret"];
		$newlog[1][$i]["user_id"] = $row["user_id"];
		$newlog[1][$i]["category"] = $row["category_id"];
		$newlog[1][$i]["comment_ok"] = $row["comment_ok"];
		$newlog[1][$i]["trackback_ok"] = $row["trackback_ok"];
		$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($row["title"]);
		$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($row["mes"]);
		$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($row["more"]);
		$newlog[1][$i]["br_change"] = $row["br_change"];
		$i++;
	}
	if ($newlog[0]) $newlog[2] = blogn_mod_db_log_count_load(1, "category", $key_category);
	return $newlog;
}


/* ----- 記事ロード（指定ユーザー用） ----- */
function blogn_mod_db_log_load_for_user($user, $start_key, $key_count, $key_user) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	if ($user) {
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.user_id = $key_user ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.user_id = $key_user ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC";
		}
	}else{
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.secret = 0 AND ".BLOGN_DB_PREFIX."_loglist.user_id = $key_user ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_loglist INNER JOIN ".BLOGN_DB_PREFIX."_logdata ON ".BLOGN_DB_PREFIX."_loglist.id = ".BLOGN_DB_PREFIX."_logdata.id WHERE (".BLOGN_DB_PREFIX."_loglist.reserve = 0 OR (".BLOGN_DB_PREFIX."_loglist.reserve = 1 AND ".BLOGN_DB_PREFIX."_loglist.date < '$nowdate')) AND ".BLOGN_DB_PREFIX."_loglist.secret = 0 AND ".BLOGN_DB_PREFIX."_loglist.user_id = $key_user ORDER BY ".BLOGN_DB_PREFIX."_loglist.date DESC";
		}
	}
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	while($row = pg_fetch_array($result)) {
		$newlog[0] = true;
		$newlog[1][$i]["id"] = $row["id"];
		$newlog[1][$i]["date"] = $row["date"];
		$newlog[1][$i]["reserve"] = $row["reserve"];
		$newlog[1][$i]["secret"] = $row["secret"];
		$newlog[1][$i]["user_id"] = $row["user_id"];
		$newlog[1][$i]["category"] = $row["category_id"];
		$newlog[1][$i]["comment_ok"] = $row["comment_ok"];
		$newlog[1][$i]["trackback_ok"] = $row["trackback_ok"];
		$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($row["title"]);
		$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($row["mes"]);
		$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($row["more"]);
		$newlog[1][$i]["br_change"] = $row["br_change"];
		$i++;
	}
	return $newlog;
}


/* ----- 記事件数 ----- */
function blogn_mod_db_log_count() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT COUNT(*) FROM ".BLOGN_DB_PREFIX."_loglist";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	return $row[0];
}

/* ----- 記事登録 ----- */
function blogn_mod_db_log_add($user_id, $date, $reserve, $secret, $comment_ok, $trackback_ok, $category, $title, $mes, $more, $br_change) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_loglist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_logdata IN ACCESS EXCLUSIVE MODE");

	if (!$reserve) $reserve = "0";
	if (!$secret) $secret = "0";
	if (!$comment_ok) $comment_ok = "0";
	if (!$trackback_ok) $trackback_ok = "0";
	if (!$br_change) $br_change = "0";

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_loglist(date,reserve,secret,user_id,category_id,comment_ok,trackback_ok,br_change) ";
	$qry .= "VALUES('{$date}',{$reserve},{$secret},{$user_id},'{$category}',{$comment_ok},{$trackback_ok},{$br_change})";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "SELECT last_value FROM ".BLOGN_DB_PREFIX."_loglist_id_seq");
	$array = pg_fetch_array($result);
	$id = $array[0];

	$title = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($title));
	$mes = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($mes));
	$more= blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($more));

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_logdata(id,title,mes,more) ";
	$qry .= "VALUES({$id},'{$title}','{$mes}','{$more}')";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $id;
	return $errdata;
}


/* ----- 記事更新 ----- */
function blogn_mod_db_log_change($id, $date, $reserve, $secret, $comment_ok, $trackback_ok, $category, $title, $mes, $more, $br_change) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_loglist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_logdata IN ACCESS EXCLUSIVE MODE");

	if (!$reserve) $reserve = "0";
	if (!$secret) $secret = "0";
	if (!$comment_ok) $comment_ok = "0";
	if (!$trackback_ok) $trackback_ok = "0";
	if (!$br_change) $br_change = "0";

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_loglist ";
	$qry .= "SET date = '{$date}',reserve = {$reserve},secret = {$secret},category_id = '{$category}',comment_ok = {$comment_ok},trackback_ok = {$trackback_ok},br_change = {$br_change} WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$title = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($title));
	$mes = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($mes));
	$more= blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($more));


	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_logdata ";
	$qry .= "SET title = '{$title}',mes = '{$mes}',more = '{$more}' WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $id;
	return $errdata;
}


/* ----- 記事更新（タイトル＆カテゴリーのみ） ----- */
function blogn_mod_db_log_title_change($id, $category, $title) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_loglist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_logdata IN ACCESS EXCLUSIVE MODE");

	$category = str_replace("-", "|", $category);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_loglist SET category_id = '{$category}' WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$title = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($title));

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_logdata SET title = '{$title}' WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $id;
	return $errdata;
}


/* ----- 記事削除 ----- */
function blogn_mod_db_log_delete($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_loglist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_logdata IN ACCESS EXCLUSIVE MODE");

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_loglist WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_logdata WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- コメント削除（指定ログID用） ----- */
function blogn_mod_db_log_comment_delete($ent_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_commentlist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_commentdata IN ACCESS EXCLUSIVE MODE");

	$qry = "SELECT id FROM ".BLOGN_DB_PREFIX."_commentlist WHERE entry_id = $ent_id";
	$cmt = pg_query($sql_connect, $qry);
	while ($row = pg_fetch_array($cmt)) {
		$cmt_id = $row["id"];
		$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_commentdata WHERE id = $cmt_id";
		$cmtdel = pg_query($sql_connect, $qry);
		$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_commentlist WHERE id = $cmt_id";
		$cmtdel = pg_query($sql_connect, $qry);
	}

	$result = pg_query($sql_connect, "COMMIT WORK");

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- トラックバック削除（指定ログID用） ----- */
function blogn_mod_db_log_trackback_delete($ent_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_trackback WHERE entry_id = $ent_id";
	$trkdel = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- コメント数ロード（一覧用） ----- */
function blogn_mod_db_comment_count() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT count(*) FROM ".BLOGN_DB_PREFIX."_commentlist";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$listcount = $row[0];
	return $listcount;
}


/* ----- コメント数ロード（一覧用） ----- */
function blogn_mod_db_comment_count_load($ent_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT count(*) FROM ".BLOGN_DB_PREFIX."_commentlist WHERE entry_id = '$ent_id'";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$listcount[0] = true;
	$listcount[1] = $row[0];
	return $listcount;
}


/* ----- コメントロード（一覧用） ----- */
function blogn_mod_db_comment_load_for_list($ent_id, $start_key, $key_count) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	if ($key_count != 0) {
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_commentlist INNER JOIN ".BLOGN_DB_PREFIX."_commentdata ON ".BLOGN_DB_PREFIX."_commentlist.id = ".BLOGN_DB_PREFIX."_commentdata.id WHERE ".BLOGN_DB_PREFIX."_commentlist.entry_id = '$ent_id' ORDER BY ".BLOGN_DB_PREFIX."_commentlist.date DESC OFFSET $start_key LIMIT $key_count";
	}else{
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_commentlist INNER JOIN ".BLOGN_DB_PREFIX."_commentdata ON ".BLOGN_DB_PREFIX."_commentlist.id = ".BLOGN_DB_PREFIX."_commentdata.id WHERE ".BLOGN_DB_PREFIX."_commentlist.entry_id = '$ent_id' ORDER BY ".BLOGN_DB_PREFIX."_commentlist.date DESC";
	}
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	$newcmt[0] = false;
	while($row = pg_fetch_array($result)) {
		$newcmt[0] = true;
		$newcmt[1][$i]["id"] = $row["id"];
		$newcmt[1][$i]["entry_id"] = $row["entry_id"];
		$newcmt[1][$i]["secret"] = $row["secret"];
		$newcmt[1][$i]["date"] = $row["date"];
		$newcmt[1][$i]["name"] = $row["name"];
		$newcmt[1][$i]["email"] = $row["email"];
		$newcmt[1][$i]["url"] = $row["url"];
		$newcmt[1][$i]["comment"] = $row["comment"];
		$newcmt[1][$i]["ip"] = $row["ip"];
		$newcmt[1][$i]["agent"] = $row["agent"];
		$i++;
	}
	$count = blogn_mod_db_comment_count_load($ent_id);
	$newcmt[2] = $count[1];
	return $newcmt;
}


/* ----- コメントロード（新着一覧用） ----- */
function blogn_mod_db_comment_load_for_new($user, $start_key, $key_count) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	if ($user) {
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_commentlist INNER JOIN ".BLOGN_DB_PREFIX."_commentdata ON ".BLOGN_DB_PREFIX."_commentlist.id = ".BLOGN_DB_PREFIX."_commentdata.id  ORDER BY ".BLOGN_DB_PREFIX."_commentlist.date DESC OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_commentlist INNER JOIN ".BLOGN_DB_PREFIX."_commentdata ON ".BLOGN_DB_PREFIX."_commentlist.id = ".BLOGN_DB_PREFIX."_commentdata.id  ORDER BY ".BLOGN_DB_PREFIX."_commentlist.date DESC";
		}
	}else{
		if ($key_count != 0) {
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_commentlist INNER JOIN ".BLOGN_DB_PREFIX."_commentdata ON ".BLOGN_DB_PREFIX."_commentlist.id = ".BLOGN_DB_PREFIX."_commentdata.id WHERE ".BLOGN_DB_PREFIX."_commentlist.secret = 0 ORDER BY ".BLOGN_DB_PREFIX."_commentlist.date DESC OFFSET $start_key LIMIT $key_count";
		}else{
			$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_commentlist INNER JOIN ".BLOGN_DB_PREFIX."_commentdata ON ".BLOGN_DB_PREFIX."_commentlist.id = ".BLOGN_DB_PREFIX."_commentdata.id WHERE ".BLOGN_DB_PREFIX."_commentlist.secret = 0 ORDER BY ".BLOGN_DB_PREFIX."_commentlist.date DESC";
		}
	}
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	$newcmt[0] = false;
	while($row = pg_fetch_array($result)) {
		$newcmt[0] = true;
		$newcmt[1][$i]["id"] = $row["id"];
		$newcmt[1][$i]["entry_id"] = $row["entry_id"];
		$newcmt[1][$i]["secret"] = $row["secret"];
		$newcmt[1][$i]["date"] = $row["date"];
		$newcmt[1][$i]["name"] = $row["name"];
		$newcmt[1][$i]["email"] = $row["email"];
		$newcmt[1][$i]["url"] = $row["url"];
		$newcmt[1][$i]["comment"] = $row["comment"];
		$newcmt[1][$i]["ip"] = $row["ip"];
		$newcmt[1][$i]["agent"] = $row["agent"];
		$i++;
	}
	return $newcmt;
}


/* ----- コメント登録 ----- */
function blogn_mod_db_comment_add($entid, $secret, $date, $name, $email, $url, $comment, $ip, $agent) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_commentlist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_commentdata IN ACCESS EXCLUSIVE MODE");

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_commentlist(entry_id,secret,date,ip,agent,name,email,url) ";
	$qry .= "VALUES(";
	$qry .= $entid.",";
	if (!$secret) {
		$qry .= "0,";
	}else{
		$qry .= $secret.",";
	}
	$qry .= "'$date',";
	$qry .= "'$ip',";
	$qry .= "'$agent',";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($name))."',";
	if (!$email) {
		$qry .= "NULL,";
	}else{
		$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($email))."',";
	}
	if (!$url) {
		$qry .= "NULL";
	}else{
		$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($url))."'";
	}
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "SELECT last_value FROM ".BLOGN_DB_PREFIX."_commentlist_id_seq");
	$array = pg_fetch_array($result);
	$id = $array[0];
	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_commentdata(id,comment) ";
	$qry .= "VALUES(";
	$qry .= "$id,";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_rn2br(blogn_mod_db_cnv_dbstr($comment)))."'";
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $id;
	return $errdata;
}


/* ----- コメント削除 ----- */
function blogn_mod_db_comment_delete($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_commentlist WHERE id = $id";
	$trkdel = pg_query($sql_connect, $qry);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_commentdata WHERE id = $id";
	$trkdel = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- トラックバック数ロード（一覧用） ----- */
function blogn_mod_db_trackback_count() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT count(*) FROM ".BLOGN_DB_PREFIX."_trackback";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$listcount = $row[0];
	return $listcount;
}


/* ----- トラックバック数ロード（一覧用） ----- */
function blogn_mod_db_trackback_count_load($ent_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT count(*) FROM ".BLOGN_DB_PREFIX."_trackback WHERE entry_id = '$ent_id'";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$listcount[0] = true;
	$listcount[1] = $row[0];
	return $listcount;
}


/* ----- トラックバックロード（一覧用） ----- */
function blogn_mod_db_trackback_load_for_list($ent_id, $start_key, $key_count) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	if ($key_count != 0) {
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_trackback WHERE entry_id = '$ent_id' ORDER BY date DESC OFFSET $start_key LIMIT $key_count";
	}else{
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_trackback WHERE entry_id = '$ent_id' ORDER BY date DESC";
	}
	$result = pg_query($sql_connect, $qry);
	$i = 0;
	$newtrk[0] = false;
	while($row = pg_fetch_array($result)) {
		$newtrk[0] = true;
		$newtrk[1][$i]["id"] = $row["id"];
		$newtrk[1][$i]["entry_id"] = $row["entry_id"];
		$newtrk[1][$i]["date"] = $row["date"];
		$newtrk[1][$i]["name"] = $row["blog_name"];
		$newtrk[1][$i]["title"] = $row["title"];
		$newtrk[1][$i]["url"] = $row["url"];
		$newtrk[1][$i]["mes"] = $row["trackback"];
		$newtrk[1][$i]["ip"] = $row["ip"];
		$newtrk[1][$i]["agent"] = $row["agent"];
		$i++;
	}
	$count = blogn_mod_db_trackback_count_load($ent_id);
	$newtrk[2] = $count[1];
	return $newtrk;
}


/* ----- トラックバックロード（新着一覧用） ----- */
function blogn_mod_db_trackback_load_for_new($start_key, $key_count) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	if ($key_count != 0) {
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_trackback ORDER BY date DESC OFFSET $start_key LIMIT $key_count";
	}else{
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_trackback ORDER BY date DESC";
	}

	$result = pg_query($sql_connect, $qry);
	$i = 0;
	$newtrk[0] = false;
	while($row = pg_fetch_array($result)) {
		$newtrk[0] = true;
		$newtrk[1][$i]["id"] = $row["id"];
		$newtrk[1][$i]["entry_id"] = $row["entry_id"];
		$newtrk[1][$i]["date"] = $row["date"];
		$newtrk[1][$i]["name"] = $row["blog_name"];
		$newtrk[1][$i]["title"] = $row["title"];
		$newtrk[1][$i]["url"] = $row["url"];
		$newtrk[1][$i]["mes"] = $row["trackback"];
		$newtrk[1][$i]["ip"] = $row["ip"];
		$newtrk[1][$i]["agent"] = $row["agent"];
		$i++;
	}
	return $newtrk;
}


/* ----- トラックバック登録 ----- */
function blogn_mod_db_trackback_add($entid, $date, $blogname, $title, $url, $trackback, $ip, $agent) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_trackback(entry_id,date,blog_name,title,url,trackback,ip,agent) ";
	$qry .= "VALUES(";
	$qry .= $entid.",";
	$qry .= "'$date',";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($blogname))."',";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($title))."',";
	if (!$url) {
		$qry .= "NULL,";
	}else{
		$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($url))."',";
	}
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_rn2br(blogn_mod_db_cnv_dbstr($trackback)))."',";
	$qry .= "'$ip',";
	$qry .= "'$agent'";
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $id;
	return $errdata;
}


/* ----- トラックバック削除 ----- */
function blogn_mod_db_trackback_delete($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_trackback WHERE id = $id";
	$trkdel = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- ファイルリストロード ----- */
function blogn_mod_db_file_load($admin, $uid, $start_key, $end_key) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$count = $end_key - $start_key;

	if ($admin) {
		$qry = "SELECT count(*) FROM ".BLOGN_DB_PREFIX."_filelist";
	}else{
		$qry = "SELECT count(*) FROM ".BLOGN_DB_PREFIX."_filelist WHERE user_id = $uid";
	}
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$list[2] = $row[0];

	if ($admin) {
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_filelist ORDER BY id DESC OFFSET $start_key LIMIT $count";
	}else{
		$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_filelist WHERE user_id = $uid ORDER BY id DESC OFFSET $start_key LIMIT $count";
	}
	$result = pg_query($sql_connect, $qry);
	$list[0] = false;
	while($row = pg_fetch_array($result)) {
		$list[0] = true;
		$list[1][$row["id"]]["user_id"] = $row["user_id"];
		$list[1][$row["id"]]["file_name"] = blogn_mod_db_comma_restore($row["file_name"]);
		$list[1][$row["id"]]["comment"] = blogn_mod_db_comma_restore($row["comment"]);
	}
	return $list;
}


/* ----- ファイルリスト登録 ----- */
function blogn_mod_db_file_add($user_id, $file_name, $comment) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_filelist(user_id, file_name, comment) ";
	$qry .= "VALUES(";
	$qry .= $user_id.",";
	$qry .= "'".$file_name."',";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($comment))."'";
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- ファイルリスト更新 ----- */
function blogn_mod_db_file_list_edit($req_id, $comment) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_filelist ";
	$qry .= "SET ";
	$comment = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($comment));
	$qry .= "comment = '$comment'";
	$qry .= " WHERE id = $req_id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- ファイルリスト削除 ----- */
function blogn_mod_db_file_list_delete($req_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_filelist WHERE id = $req_id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;

	if (!@unlink(BLOGN_FILEDIR.$file_name)) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_17;
		return $errdata;
	}
	return $errdata;
}


/* ----- カテゴリ１リストロード ----- */
function blogn_mod_db_category1_load() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_categorygroup ORDER BY updownid ASC";
	$result = pg_query($sql_connect, $qry);

	$list[0] = false;
	while($row = pg_fetch_array($result)) {
		$list[0] = true;
		$list[1][$row["id"]]["name"] = blogn_mod_db_comma_restore($row["category_name"]);
		$list[1][$row["id"]]["view"] = $row["view_mode"];
	}
	return $list;
}


/* ----- カテゴリ２リストロード ----- */
function blogn_mod_db_category2_load() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_categorylist ORDER BY updownid ASC";
	$result = pg_query($sql_connect, $qry);

	$list[0] = false;
	while($row = pg_fetch_array($result)) {
		$list[0] = true;
		$list[1][$row["id"]]["id"] = $row["group_id"];
		$list[1][$row["id"]]["name"] = blogn_mod_db_comma_restore($row["category_name"]);
		$list[1][$row["id"]]["view"] = $row["view_mode"];
	}
	return $list;
}


/* ----- カテゴリ１リスト登録 ----- */
function blogn_mod_db_category1_add($category_name) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_categorygroup(updownid, category_name) ";
	$qry .= "VALUES(";
	$qry .= "0, '".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($category_name))."'";
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "SELECT last_value FROM ".BLOGN_DB_PREFIX."_categorygroup_id_seq");
	$array = pg_fetch_array($result);
	$id = $array[0];
	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_categorygroup ";
	$qry .= "SET ";
	$qry .= "updownid = {$id}";
	$qry .= " WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $id;
	return $errdata;
}


/* ----- カテゴリ２リスト登録 ----- */
function blogn_mod_db_category2_add($group_id, $category_name) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_categorylist(updownid, group_id, category_name) ";
	$qry .= "VALUES(";
	$qry .= "0, ".$group_id.", ";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($category_name))."'";
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "SELECT last_value FROM ".BLOGN_DB_PREFIX."_categorylist_id_seq");
	$array = pg_fetch_array($result);
	$id = $array[0];
	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_categorylist ";
	$qry .= "SET ";
	$qry .= "updownid = {$id}";
	$qry .= " WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $id;
	return $errdata;
}


/* ----- カテゴリ１リスト更新 ----- */
function blogn_mod_db_category1_edit($id, $category_name, $view_mode) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_categorygroup ";
	$qry .= "SET ";
	$category_name = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($category_name));
	$qry .= "category_name = '{$category_name}',";
	$qry .= "view_mode = {$view_mode}";
	$qry .= " WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	// カテゴリ２も全て表示／非表示連動
	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_categorylist ";
	$qry .= "SET ";
	$qry .= "view_mode = {$view_mode}";
	$qry .= " WHERE group_id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- カテゴリ２リスト更新 ----- */
function blogn_mod_db_category2_edit($id, $cid, $category_name, $view_mode) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_categorylist ";
	$qry .= "SET ";
	$qry .= "group_id = $cid,";
	$category_name = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($category_name));
	$qry .= "category_name = '{$category_name}',";
	$qry .= "view_mode = {$view_mode}";
	$qry .= " WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	// カテゴリ２を表示にした場合、カテゴリ１も連動
	if ($view_mode) {
		$qry  = "UPDATE ".BLOGN_DB_PREFIX."_categorygroup ";
		$qry .= "SET ";
		$qry .= "view_mode = {$view_mode}";
		$qry .= " WHERE id = {$cid}";
		$result = pg_query($sql_connect, $qry);
	}

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- カテゴリ１削除 ----- */
function blogn_mod_db_category1_delete($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_categorygroup WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_categorylist WHERE group_id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- カテゴリ２削除 ----- */
function blogn_mod_db_category2_delete($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_categorylist WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- カテゴリ１順番切り替え ----- */
function blogn_mod_db_category1_change($id, $updown) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_categorygroup IN ACCESS EXCLUSIVE MODE");

	$qry = "SELECT updownid FROM ".BLOGN_DB_PREFIX."_categorygroup WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$from_updownid = $row["updownid"];

	if ($updown == "up") {
		$qry = "SELECT id, updownid FROM ".BLOGN_DB_PREFIX."_categorygroup WHERE updownid < {$from_updownid} ORDER BY updownid DESC LIMIT 1";
	}else{
		$qry = "SELECT id, updownid FROM ".BLOGN_DB_PREFIX."_categorygroup WHERE updownid > {$from_updownid} ORDER BY updownid ASC LIMIT 1";
	}
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);

	$to_id = $row["id"];
	$to_updownid = $row["updownid"];

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_categorygroup ";
	$qry .= "SET ";
	$qry .= "updownid = {$to_updownid}";
	$qry .= " WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_categorygroup ";
	$qry .= "SET ";
	$qry .= "updownid = {$from_updownid}";
	$qry .= " WHERE id = {$to_id}";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$error[0] = true;
	$error[1] = BLOGN_MOD_DB_MES_16;
	return $error;
}


/* ----- カテゴリ２順番切り替え ----- */
function blogn_mod_db_category2_change($c1_id, $id, $updown) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_categorylist IN ACCESS EXCLUSIVE MODE");

	$qry = "SELECT updownid FROM ".BLOGN_DB_PREFIX."_categorylist WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$from_updownid = $row["updownid"];

	if ($updown == "up") {
		$qry = "SELECT id, updownid FROM ".BLOGN_DB_PREFIX."_categorylist WHERE group_id = {$c1_id} AND updownid < {$from_updownid} ORDER BY updownid DESC LIMIT 1";
	}else{
		$qry = "SELECT id, updownid FROM ".BLOGN_DB_PREFIX."_categorylist WHERE group_id = {$c1_id} AND updownid > {$from_updownid} ORDER BY updownid ASC LIMIT 1";
	}
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);

	$to_id = $row["id"];
	$to_updownid = $row["updownid"];

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_categorylist ";
	$qry .= "SET ";
	$qry .= "updownid = {$to_updownid}";
	$qry .= " WHERE id = {$id}";
	$result = pg_query($sql_connect, $qry);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_categorylist ";
	$qry .= "SET ";
	$qry .= "updownid = {$from_updownid}";
	$qry .= " WHERE id = {$to_id}";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$error[0] = true;
	$error[1] = BLOGN_MOD_DB_MES_16;
	return $error;
}


/* ----- リンクグループロード ----- */
function blogn_mod_db_link_group_load() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_linkgroup ORDER BY updownid ASC";
	$result = pg_query($sql_connect, $qry);

	$list[0] = false;
	while($row = pg_fetch_array($result)) {
		$list[0] = true;
		$list[1][$row["id"]]["name"] = blogn_mod_db_comma_restore($row["group_name"]);
	}
	return $list;
}


/* ----- リンクリストロード ----- */
function blogn_mod_db_link_load() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_linklist ORDER BY updownid ASC";
	$result = pg_query($sql_connect, $qry);

	$list[0] = false;
	while($row = pg_fetch_array($result)) {
		$list[0] = true;
		$list[1][$row["id"]]["group"] = $row["group_id"];
		$list[1][$row["id"]]["name"] = blogn_mod_db_comma_restore($row["link_name"]);
		$list[1][$row["id"]]["url"] = blogn_mod_db_comma_restore($row["link_url"]);
	}
	return $list;
}


/* ----- リンクグループ登録 ----- */
function blogn_mod_db_link_group_add($group_name) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_linkgroup(updownid, group_name) ";
	$qry .= "VALUES(";
	$qry .= "0, '".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($group_name))."'";
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "SELECT last_value FROM ".BLOGN_DB_PREFIX."_linkgroup_id_seq");
	$array = pg_fetch_array($result);
	$id = $array[0];
	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_linkgroup ";
	$qry .= "SET ";
	$qry .= "updownid = $id";
	$qry .= " WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- リンクリスト登録 ----- */
function blogn_mod_db_link_list_add($group_id, $link_name, $link_url) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_linklist(updownid, group_id, link_name, link_url) ";
	$qry .= "VALUES(";
	$qry .= "0, ".$group_id.", ";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($link_name))."',";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($link_url))."'";
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "SELECT last_value FROM ".BLOGN_DB_PREFIX."_linklist_id_seq");
	$array = pg_fetch_array($result);
	$id = $array[0];
	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_linklist ";
	$qry .= "SET ";
	$qry .= "updownid = $id";
	$qry .= " WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- リンクグループ更新 ----- */
function blogn_mod_db_link_group_edit($id, $group_name) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_linkgroup ";
	$qry .= "SET ";
	$group_name = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($group_name));
	$qry .= "group_name = '$group_name'";
	$qry .= " WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- リンクリスト更新 ----- */
function blogn_mod_db_link_list_edit($id, $group_id, $link_name, $link_url) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_linklist ";
	$qry .= "SET ";
	$qry .= "group_id = $group_id,";
	$link_name = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($link_name));
	$link_url = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($link_url));
	$qry .= "link_name = '$link_name',";
	$qry .= "link_url = '$link_url'";
	$qry .= " WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- リンクグループ削除 ----- */
function blogn_mod_db_link_group_delete($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_linkgroup WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_linklist WHERE group_id = $id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- リンクリスト削除 ----- */
function blogn_mod_db_link_list_delete($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_linklist WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- リンクグループ順番切り替え ----- */
function blogn_mod_db_link_group_change($id, $updown) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_linkgroup IN ACCESS EXCLUSIVE MODE");

	$qry = "SELECT updownid FROM ".BLOGN_DB_PREFIX."_linkgroup WHERE id = $id";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$from_updownid = $row["updownid"];

	if ($updown == "up") {
		$qry = "SELECT id, updownid FROM ".BLOGN_DB_PREFIX."_linkgroup WHERE updownid < $from_updownid ORDER BY updownid DESC LIMIT 1";
	}else{
		$qry = "SELECT id, updownid FROM ".BLOGN_DB_PREFIX."_linkgroup WHERE updownid > $from_updownid ORDER BY updownid ASC LIMIT 1";
	}
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);

	$to_id = $row["id"];
	$to_updownid = $row["updownid"];

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_linkgroup ";
	$qry .= "SET ";
	$qry .= "updownid = $to_updownid";
	$qry .= " WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_linkgroup ";
	$qry .= "SET ";
	$qry .= "updownid = $from_updownid";
	$qry .= " WHERE id = $to_id";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$error[0] = true;
	$error[1] = BLOGN_MOD_DB_MES_16;
	return $error;
}


/* ----- リンクリスト順番切り替え ----- */
function blogn_mod_db_link_list_change($group_id, $id, $updown) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_linklist IN ACCESS EXCLUSIVE MODE");

	$qry = "SELECT updownid FROM ".BLOGN_DB_PREFIX."_linklist WHERE id = $id";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$from_updownid = $row["updownid"];

	if ($updown == "up") {
		$qry = "SELECT id, updownid FROM ".BLOGN_DB_PREFIX."_linklist WHERE group_id = $group_id AND updownid < $from_updownid ORDER BY updownid DESC LIMIT 1";
	}else{
		$qry = "SELECT id, updownid FROM ".BLOGN_DB_PREFIX."_linklist WHERE group_id = $group_id AND updownid > $from_updownid ORDER BY updownid ASC LIMIT 1";
	}
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);

	$to_id = $row["id"];
	$to_updownid = $row["updownid"];

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_linklist ";
	$qry .= "SET ";
	$qry .= "updownid = $to_updownid";
	$qry .= " WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_linklist ";
	$qry .= "SET ";
	$qry .= "updownid = $from_updownid";
	$qry .= " WHERE id = $to_id";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$error[0] = true;
	$error[1] = BLOGN_MOD_DB_MES_16;
	return $error;
}


/* ----- PINGリストロード ----- */
function blogn_mod_db_ping_load() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_pinglist";
	$result = pg_query($sql_connect, $qry);

	$list[0] = false;
	while($row = pg_fetch_array($result)) {
		$list[0] = true;
		$list[1][$row["id"]]["name"] = blogn_mod_db_comma_restore($row["ping_name"]);
		$list[1][$row["id"]]["url"] = blogn_mod_db_comma_restore($row["ping_url"]);
		$list[1][$row["id"]]["default"] = $row["ping_default"];
	}
	return $list;
}


/* ----- PING送信先登録 ----- */
function blogn_mod_db_ping_add($ping_name, $ping_url, $ping_default) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_pinglist(ping_name,ping_url,ping_default) ";
	$qry .= "VALUES(";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($ping_name))."',";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($ping_url))."',";
	if (!$ping_default) {
		$qry .= "0";
	}else{
		$qry .= $ping_default;
	}
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- PING送信先更新 ----- */
function blogn_mod_db_ping_edit($id, $ping_name, $ping_url, $ping_default) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_pinglist ";
	$qry .= "SET ";

	$ping_name = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($ping_name));
	$ping_url = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($ping_url));

	$qry .= "ping_name = '$ping_name',";
	$qry .= "ping_url = '$ping_url',";
	if (!$ping_default) {
		$qry .= "ping_default = 0";
	}else{
		$qry .= "ping_default = $ping_default";
	}
	$qry .= " WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- PING送信先削除 ----- */
function blogn_mod_db_ping_delete($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_pinglist WHERE id = $id";
	$result = pg_query($sql_connect, $qry);
	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- ユーザーログインチェック ----- */
function blogn_mod_db_user_check($loginid, $loginpw) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	// ユーザー検索
	$checkpw = md5($loginpw);
	$qry = "SELECT admin, active_mode FROM ".BLOGN_DB_PREFIX."_userlist WHERE user_id = '$loginid' AND user_pw = '$checkpw' AND active_mode != 0";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	if ($row) {
		$errdata[0] = true;
		$errdata[1] = BLOGN_MOD_DB_MES_09;
		$errdata[2] = $row["admin"];
		$errdata[3] = $row["active_mode"];
	}else{
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_10;
	}
	return $errdata;
}


/* ----- ユーザーリストロード ----- */
function blogn_mod_db_user_load() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_userlist INNER JOIN ".BLOGN_DB_PREFIX."_profile ON ".BLOGN_DB_PREFIX."_userlist.id = ".BLOGN_DB_PREFIX."_profile.id";
	$result = pg_query($sql_connect, $qry);
	while($row = pg_fetch_array($result)) {
		$ulist[$row["id"]]["admin"] = $row["admin"];
		$ulist[$row["id"]]["id"] = $row["user_id"];
		$ulist[$row["id"]]["pw"] = $row["user_pw"];
		$ulist[$row["id"]]["name"] = blogn_mod_db_comma_restore($row["user_name"]);
		$ulist[$row["id"]]["active"] = $row["active_mode"];
		$ulist[$row["id"]]["profile"] = $row["user_profile"];
		$ulist[$row["id"]]["init_comment_ok"] = $row["init_comment_ok"];
		$ulist[$row["id"]]["init_trackback_ok"] = $row["init_trackback_ok"];
		$ulist[$row["id"]]["init_category"] = $row["init_category"];
		$ulist[$row["id"]]["init_icon_ok"] = $row["init_icon_ok"];
		$ulist[$row["id"]]["receive_mail_address"] = $row["receive_mail_address"];
		$ulist[$row["id"]]["receive_mail_pop3"] = $row["receive_mail_pop3"];
		$ulist[$row["id"]]["receive_mail_user_id"] = $row["receive_mail_user_id"];
		$ulist[$row["id"]]["receive_mail_user_pw"] = $row["receive_mail_user_pw"];
		$ulist[$row["id"]]["receive_mail_apop"] = $row["receive_mail_apop"];
		$ulist[$row["id"]]["access_time"] = $row["access_time"];
		$ulist[$row["id"]]["send_mail_address"] = $row["send_mail_address"];
		$ulist[$row["id"]]["mobile_category"] = $row["mobile_category"];
		$ulist[$row["id"]]["mobile_comment_ok"] = $row["mobile_comment_ok"];
		$ulist[$row["id"]]["mobile_trackback_ok"] = $row["mobile_trackback_ok"];
		$ulist[$row["id"]]["information_mail_address"] = $row["information_mail_address"];
		$ulist[$row["id"]]["information_comment"] = $row["information_comment"];
		$ulist[$row["id"]]["information_trackback"] = $row["information_trackback"];
		$ulist[$row["id"]]["user_mail_address"] = $row["user_mail_address"];
		$ulist[$row["id"]]["br_change"] = $row["br_change"];
	}
	return $ulist;
}


/* ----- 新規ユーザー登録 ----- */
function blogn_mod_db_user_add($loginid, $loginpw, $name, $mailaddress, $useradmin, $useractive) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_userlist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_profile IN ACCESS EXCLUSIVE MODE");

	// ユーザー名検索
	$qry = "SELECT count(*) FROM ".BLOGN_DB_PREFIX."_userlist WHERE user_id = '$loginid'";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	if ($row[0]) {
		$result = pg_query($sql_connect, "COMMIT WORK");
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_06;
		$errdata[2] = $loginid;
		return $errdata;
	}
	$useradmin = (INT)$useradmin;
	$loginpw = md5($loginpw);
	$useractive = (INT)$useractive;
	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_userlist(admin,user_id,user_pw,user_name,user_mail_address,active_mode) ";
	$qry .= "VALUES({$useradmin},'{$loginid}','{$loginpw}','{$name}','{$mailaddress}',{$useractive})";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "SELECT last_value FROM ".BLOGN_DB_PREFIX."_userlist_id_seq");
	$array = pg_fetch_array($result);
	$id = $array[0];

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_profile(id,user_profile) ";
	$qry .= "VALUES({$id},NULL)";
	$result = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $loginid;
	return $errdata;
}


/* ----- ユーザー状態変更 ----- */
function blogn_mod_db_user_active($req_id, $active_mode) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	// ユーザーチェック
	$qry = "SELECT admin,user_id FROM ".BLOGN_DB_PREFIX."_userlist WHERE id = $req_id";
	$result = pg_query($sql_connect, $qry);
	if ($result) {
		$row = pg_fetch_array($result);
		$user_id = $row["user_id"];
		if ($row["admin"]) {
			$errdata[0] = false;
			$errdata[1] = BLOGN_MOD_DB_MES_11;
			$errdata[2] = $user_id;
			return $errdata;
		}
	}

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_userlist ";
	$qry .= "SET active_mode = {$active_mode} WHERE id = {$req_id}";
	$result = pg_query($sql_connect, $qry);
	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_05;
	$errdata[2] = $user_id;
	return $errdata;
}


/* ----- ユーザープロフィール取得 ----- */
function blogn_mod_db_user_profile_load($req_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_userlist INNER JOIN ".BLOGN_DB_PREFIX."_profile ON ".BLOGN_DB_PREFIX."_userlist.id = ".BLOGN_DB_PREFIX."_profile.id WHERE ".BLOGN_DB_PREFIX."_userlist.id = $req_id";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$ulist["id"] = $row["user_id"];
	$ulist["admin"] = $row["admin"];
	$ulist["name"] = $row["user_name"];
	$ulist["profile"] = $row["user_profile"];
	$ulist["init_comment_ok"] = $row["init_comment_ok"];
	$ulist["init_trackback_ok"] = $row["init_trackback_ok"];
	$ulist["init_category"] = $row["init_category"];
	$ulist["init_icon_ok"] = $row["init_icon_ok"];
	$ulist["receive_mail_address"] = $row["receive_mail_address"];
	$ulist["receive_mail_pop3"] = $row["receive_mail_pop3"];
	$ulist["receive_mail_user_id"] = $row["receive_mail_user_id"];
	$ulist["receive_mail_user_pw"] = $row["receive_mail_user_pw"];
	$ulist["receive_mail_apop"] = $row["receive_mail_apop"];
	$ulist["access_time"] = $row["access_time"];
	$ulist["send_mail_address"] = $row["send_mail_address"];
	$ulist["mobile_category"] = $row["mobile_category"];
	$ulist["mobile_comment_ok"] = $row["mobile_comment_ok"];
	$ulist["mobile_trackback_ok"] = $row["mobile_trackback_ok"];
	$ulist["information_mail_address"] = $row["information_mail_address"];
	$ulist["information_comment"] = $row["information_comment"];
	$ulist["information_trackback"] = $row["information_trackback"];
	$ulist["user_mail_address"] = $row["user_mail_address"];
	$ulist["br_change"] = $row["br_change"];
	return $ulist;
}


/* ----- ユーザー情報更新 ----- */
function blogn_mod_db_user_profile_update($req_id, $user_id, $user_pw, $user_name, $user_profile, $init_comment_ok, $init_trackback_ok, $init_category, $init_icon_ok, $receive_mail_address, $receive_mail_pop3, $receive_mail_user_id, $receive_mail_user_pw, $receive_mail_apop, $access_time, $send_mail_address, $mobile_category, $mobile_comment_ok, $mobile_trackback_ok, $information_mail_address, $information_comment, $information_trackback, $user_mail_address, $br_change) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_userlist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_profile IN ACCESS EXCLUSIVE MODE");

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_userlist ";
	$qry .= "SET ";
	$qry .= "user_id = '{$user_id}',";
	if ($user_pw) $qry .= "user_pw = '".md5($user_pw)."',";
	$qry .= "user_name = '{$user_name}',";
	$qry .= "init_comment_ok = {$init_comment_ok},";
	$qry .= "init_trackback_ok = {$init_trackback_ok},";
	if (!$init_category) {
		$qry .= "init_category = NULL,";
	}else{
		$qry .= "init_category = '{$init_category}',";
	}
	if (!$init_icon_ok) {
		$qry .= "init_icon_ok = 0,";
	}else{
		$qry .= "init_icon_ok = {$init_icon_ok},";
	}
	if (!$receive_mail_address) {
		$qry .= "receive_mail_address = NULL,";
	}else{
		$qry .= "receive_mail_address = '{$receive_mail_address}',";
	}
	if (!$receive_mail_pop3) {
		$qry .= "receive_mail_pop3 = NULL,";
	}else{
		$qry .= "receive_mail_pop3 = '{$receive_mail_pop3}',";
	}
	if (!$receive_mail_user_id) {
		$qry .= "receive_mail_user_id = NULL,";
	}else{
		$qry .= "receive_mail_user_id = '{$receive_mail_user_id}',";
	}
	if (!$receive_mail_user_pw) {
		$qry .= "receive_mail_user_pw = NULL,";
	}else{
		$qry .= "receive_mail_user_pw = '{$receive_mail_user_pw}',";
	}
	$qry .= "receive_mail_apop = {$receive_mail_apop},";
	$qry .= "access_time = {$access_time},";
	if (!$send_mail_address) {
		$qry .= "send_mail_address = NULL,";
	}else{
		$qry .= "send_mail_address = '{$send_mail_address}',";
	}
	if (!$mobile_category) {
		$qry .= "mobile_category = NULL,";
	}else{
		$qry .= "mobile_category = '{$mobile_category}',";
	}
	$qry .= "mobile_comment_ok = {$mobile_comment_ok},";
	$qry .= "mobile_trackback_ok = {$mobile_trackback_ok},";
	if (!$information_mail_address) {
		$qry .= "information_mail_address = NULL,";
	}else{
		$qry .= "information_mail_address = '{$information_mail_address}',";
	}
	if (!$information_comment) {
		$qry .= "information_comment = 0,";
	}else{
		$qry .= "information_comment = {$information_comment},";
	}
	if (!$information_trackback) {
		$qry .= "information_trackback = 0,";
	}else{
		$qry .= "information_trackback = {$information_trackback},";
	}
	$qry .= "user_mail_address = '{$user_mail_address}',";
	$qry .= "br_change = {$br_change}";
	$qry .= " WHERE id = $req_id";
	$result = pg_query($sql_connect, $qry);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_profile ";
	$qry .= "SET ";
	if (!$user_profile) {
		$qry .= "user_profile = NULL";
	}else{
		$user_profile = blogn_mod_db_comma_change(blogn_mod_db_rn2br(blogn_mod_db_cnv_dbstr($user_profile)));
		$qry .= "user_profile = '{$user_profile}'";
	}
	$qry .= " WHERE id = {$req_id}";
	$result = pg_query($sql_connect, $qry);
	$result = pg_query($sql_connect, "COMMIT WORK");

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_05;
	return $errdata;
}


/* ----- ユーザー削除 ----- */
function blogn_mod_db_user_delete($ent_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	// 管理者権限IDは削除不可
	$qry = "SELECT admin FROM ".BLOGN_DB_PREFIX."_userlist WHERE id = {$ent_id}";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	if ($row["admin"] != 0) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_DB_MES_21;
		return $error;
	}

	$result = pg_query($sql_connect, "BEGIN WORK");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_userlist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_profile IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_loglist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_logdata IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_commentlist IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_commentdata IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, "LOCK TABLE ".BLOGN_DB_PREFIX."_trackback IN ACCESS EXCLUSIVE MODE");
	$result = pg_query($sql_connect, $qry);

	// ユーザーの削除
	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_userlist WHERE id = {$ent_id}";
	$result = pg_query($sql_connect, $qry);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_profile WHERE id = {$ent_id}";
	$result = pg_query($sql_connect, $qry);

	// ユーザーの投稿を削除
	$qry = "SELECT id FROM ".BLOGN_DB_PREFIX."_loglist WHERE user_id = {$ent_id}";
	$log = pg_query($sql_connect, $qry);
	while ($row = pg_fetch_array($log)) {
		$entry_id = $row["id"];

		$qry = "SELECT id FROM ".BLOGN_DB_PREFIX."_commentlist WHERE entry_id = {$entry_id}";
		$cmt = pg_query($sql_connect, $qry);
		while ($row = pg_fetch_array($cmt)) {
			$cmt_id = $row["id"];
			$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_commentdata WHERE id = {$cmt_id}";
			$cmtdel = pg_query($sql_connect, $qry);
			$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_commentlist WHERE id = {$cmt_id}";
			$cmtdel = pg_query($sql_connect, $qry);
		}
		$qry = "SELECT id FROM ".BLOGN_DB_PREFIX."_trackback WHERE entry_id = {$entry_id}";
		$trk = pg_query($sql_connect, $qry);
		while ($row = pg_fetch_array($trk)) {
			$trk_id = $row["id"];
			$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_trackback WHERE id = {$trk_id}";
			$trkdel = pg_query($sql_connect, $qry);
		}
		$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_logdata WHERE id = {$entry_id}";
		$logdel = pg_query($sql_connect, $qry);
	}
	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_loglist WHERE user_id = {$ent_id}";
	$logdel = pg_query($sql_connect, $qry);

	$result = pg_query($sql_connect, "COMMIT WORK");

	$error[0] = true;
	$error[1] = BLOGN_MOD_DB_MES_20;
	return $error;
}


/* ----- 初期データロード ----- */
function blogn_mod_db_init_load() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_init WHERE id = 0";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	$list["id"] = $row["id"];
	$list["sitename"] = blogn_mod_db_comma_restore($row["site_name"]);
	$list["sitedesc"] = blogn_mod_db_comma_restore($row["site_desc"]);
	$list["timezone"] = $row["timezone"];
	$list["charset"] = $row["charset"];
	$list["max_filesize"] = $row["max_filesize"];
	$list["permit_file_type"] = blogn_mod_db_comma_restore($row["permit_filetype"]);
	$list["max_view_width"] = $row["maxview_width"];
	$list["max_view_height"] = $row["maxview_height"];
	$list["permit_html_tag"] = blogn_mod_db_comma_restore($row["permit_htmltag"]);
	$list["comment_size"] = $row["comment_size"];
	$list["trackback_slash_type"] = $row["trackback_slashtype"];
	$list["log_view_count"] = $row["log_view_count"];
	$list["mobile_view_count"] = $row["mobile_view_count"];
	$list["new_entry_view_count"] = $row["newentry_view_count"];
	$list["archive_view_count"] = $row["archive_view_count"];
	$list["comment_view_count"] = $row["comment_view_count"];
	$list["trackback_view_count"] = $row["trackback_view_count"];
	$list["comment_list_topview_on"] = $row["comment_list_topview_on"];
	$list["trackback_list_topview_on"] = $row["trackback_list_topview_on"];
	$list["session_time"] = $row["session_time"];
	$list["cookie_time"] = $row["cookie_time"];
	$list["limit_comment"] = $row["limit_comment"];
	$list["limit_trackback"] = $row["limit_trackback"];
	$list["monthly_view_mode"] = $row["monthly_view_mode"];
	$list["category_view_mode"] = $row["category_view_mode"];
	return $list;
}


/* ----- 初期データ更新 ----- */
function blogn_mod_db_init_Change($id, $sitename, $sitedesc, $timezone, $charset, $max_filesize, $permit_file_type, $max_view_width, $max_view_height, $permit_html_tag, $comment_size, $trackback_slash_type, $log_view_count, $mobile_view_count, $new_entry_view_count, $archive_view_count, $comment_view_count, $trackback_view_count, $comment_list_topview_on, $trackback_list_topview_on, $session_time, $cookie_time, $limit_comment, $limit_trackback, $monthly_view_mode, $category_view_mode) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT count(*) FROM ".BLOGN_DB_PREFIX."_init";
	$result = pg_query($sql_connect, $qry);
	$row = pg_fetch_array($result);
	if ($row[0]) {
		$permit_filetype = blogn_mod_db_comma_change($permit_file_type);
		$permit_htmltag = blogn_mod_db_comma_change($permit_html_tag);
		$trackback_slashtype = $trackback_slash_type ? $trackback_slash_type : 0;
		// データが存在する場合は更新
		$qry  = "UPDATE ".BLOGN_DB_PREFIX."_init ";
		$qry .= "SET ";
		$qry .= "site_name = '$sitename',";
		$qry .= "site_desc = '$sitedesc',";
		$qry .= "timezone = $timezone,";
		$qry .= "charset = $charset,";
		$qry .= "max_filesize = $max_filesize,";
		$qry .= "permit_filetype = '$permit_filetype',";
		$qry .= "maxview_width = $max_view_width,";
		$qry .= "maxview_height = $max_view_height,";
		$qry .= "permit_htmltag = '$permit_htmltag',";
		$qry .= "comment_size = $comment_size,";
		$qry .= "trackback_slashtype = $trackback_slashtype,";
		$qry .= "log_view_count = $log_view_count,";
		$qry .= "mobile_view_count = $mobile_view_count,";
		$qry .= "newentry_view_count = $new_entry_view_count,";
		$qry .= "archive_view_count = $archive_view_count,";
		$qry .= "comment_view_count = $comment_view_count,";
		$qry .= "trackback_view_count = $trackback_view_count,";
		$qry .= "comment_list_topview_on = $comment_list_topview_on,";
		$qry .= "trackback_list_topview_on = $trackback_list_topview_on,";
		$qry .= "session_time = $session_time,";
		$qry .= "cookie_time = $cookie_time,";
		$qry .= "limit_comment = $limit_comment,";
		$qry .= "limit_trackback = $limit_trackback,";
		$qry .= "monthly_view_mode = $monthly_view_mode,";
		$qry .= "category_view_mode = $category_view_mode";
		$qry .= " WHERE id = 0";
	}else{
		// データが無い場合は新規
		$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_init ";
		$qry .= "VALUES(";
		$qry .= "$id,";
		$qry .= "'$sitename',";
		$qry .= "'$sitedesc',";
		$qry .= "$timezone,";
		$qry .= "$charset,";
		$qry .= "$max_filesize,";
		$qry .= "'$permit_file_type',";
		$qry .= "$max_view_width,";
		$qry .= "$max_view_height,";
		$qry .= "'$permit_html_tag',";
		$qry .= "$comment_size,";
		$qry .= "$trackback_slash_type,";
		$qry .= "$log_view_count,";
		$qry .= "$mobile_view_count,";
		$qry .= "$new_entry_view_count,";
		$qry .= "$archive_view_count,";
		$qry .= "$comment_view_count,";
		$qry .= "$trackback_view_count,";
		$qry .= "$comment_list_topview_on,";
		$qry .= "$trackback_list_topview_on,";
		$qry .= "$session_time,";
		$qry .= "$cookie_time,";
		$qry .= "$limit_comment,";
		$qry .= "$limit_trackback,";
		$qry .= "monthly_view_mode,";
		$qry .= "category_view_mode";
		$qry .= ")";
	}
	$result = pg_query($sql_connect, $qry);
	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $loginid;
	return $errdata;
}


/* ----- メールサーバへのアクセス時間チェック ----- */
function blogn_mod_db_mobile_access() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$result = pg_query($sql_connect, "BEGIN WORK");
	$qry  = "LOCK TABLE ".BLOGN_DB_PREFIX."_access IN ACCESS EXCLUSIVE MODE";
	$result = pg_query($sql_connect, $qry);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_access";
	$result = pg_query($sql_connect, $qry);

	$newtime = time();
	$update[0] = false;
	$userlist = blogn_mod_db_user_load();
	$access = array();
	while(list($key, $val) = each($userlist)) {
		if ($val["receive_mail_user_pw"]) {
			$mailcheck = $found = false;
			@reset($result);
			while ($row = pg_fetch_array($result)) {
				$checktime = $row["time"] + $val["access_time"] * 60;
				if ($key == $row["id"]) {
					$found = true;
					if ($newtime > $checktime) {
						$mailcheck = true;
						$qry  = "UPDATE ".BLOGN_DB_PREFIX."_access ";
						$qry .= "SET ";
						$qry .= "time = '$newtime'";
						$qry .= " WHERE id = ".$row["id"];
						pg_query($sql_connect, $qry);
					}
					break;
				}
			}
			if (!$found) {
				$mailcheck = true;
				$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_access ";
				$qry .= "VALUES(";
				$qry .= $key.",";
				$qry .= "'$newtime'";
				$qry .= ")";
				pg_query($sql_connect, $qry);
			}

			if ($mailcheck) {
				$update[0] = true;
				$update[1][$key]["receive_mail_address"] = $val["receive_mail_address"];
				$update[1][$key]["receive_mail_pop3"] = $val["receive_mail_pop3"];
				$update[1][$key]["receive_mail_user_id"] = $val["receive_mail_user_id"];
				$update[1][$key]["receive_mail_user_pw"] = $val["receive_mail_user_pw"];
				$update[1][$key]["receive_mail_apop"] = $val["receive_mail_apop"];
				$update[1][$key]["send_mail_address"] = $val["send_mail_address"];
				$update[1][$key]["mobile_category"] = $val["mobile_category"];
				$update[1][$key]["mobile_comment_ok"] = $val["mobile_comment_ok"];
				$update[1][$key]["mobile_trackback_ok"] = $val["mobile_trackback_ok"];
			}
		}
	}
	$qry  = "COMMIT WORK";
	$result = pg_query($sql_connect, $qry);

	return $update;
}


/* ----- スキンリストロード ----- */
function blogn_mod_db_skin_load() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_skinlist";
	$result = pg_query($sql_connect, $qry);

	$list[0] = false;
	while($row = pg_fetch_array($result)) {
		$list[0] = true;
		$list[1][$row["id"]]["skin_name"] = blogn_mod_db_comma_restore($row["skin_name"]);
		$list[1][$row["id"]]["html_name"] = $row["html_name"];
		$list[1][$row["id"]]["css_name"] = $row["css_name"];
	}
	return $list;
}


/* ----- スキン登録 ----- */
function blogn_mod_db_skin_add($skin_name, $html_file_name, $css_file_name) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_skinlist(skin_name, html_name, css_name) ";
	$qry .= "VALUES(";
	$qry .= "'".blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($skin_name))."',";
	$qry .= "'".$html_file_name."',";
	$qry .= "'".$css_file_name."'";
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- スキン名更新 ----- */
function blogn_mod_db_skin_edit($id, $skin_name, $html_file_name, $css_file_name) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "UPDATE ".BLOGN_DB_PREFIX."_skinlist ";
	$qry .= "SET ";
	$skin_name = blogn_mod_db_comma_change(blogn_mod_db_cnv_dbstr($skin_name));
	$qry .= "skin_name = '$skin_name'";
	$qry .= " WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- スキンリスト削除 ----- */
function blogn_mod_db_skin_delete($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_skinlist WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- 表示スキンリストロード ----- */
function blogn_mod_db_viewskin_load() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_viewskin ORDER BY category_id, section_id";
	$result = pg_query($sql_connect, $qry);

	$list[0] = false;
	while($row = pg_fetch_array($result)) {
		$list[0] = true;
		$list[1][$row["id"]]["view_type"] = $row["view_type"];
		$list[1][$row["id"]]["category_id"] = $row["category_id"];
		$list[1][$row["id"]]["section_id"] = $row["section_id"];
		$list[1][$row["id"]]["skin_id"] = $row["skin_id"];
	}
	return $list;
}


/* ----- 表示スキン登録 ----- */
function blogn_mod_db_viewskin_add($view_type, $category_id, $section_id, $skin_id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	switch ($view_type) {
		case "0":
			$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_viewskin";
			$result = pg_query($sql_connect, $qry);
			$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_viewskin(id, view_type, category_id, section_id, skin_id) ";
			$qry .= "VALUES(0, 0, 0, 0, {$skin_id})";
			$result = pg_query($sql_connect, $qry);
			break;
		case "1":
			$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_viewskin";
			$result = pg_query($sql_connect, $qry);
			$i = 0;
			while(list($id, $null) = each($skin_id)) {
				$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_viewskin(id, view_type, category_id, section_id, skin_id) ";
				$qry .= "VALUES({$i}, 1,";
				if ($category_id[$id]) {
					$qry .= $category_id[$id].",";
				}else{
					$qry .= "0,";
				}
				if ($section_id[$id]) {
					$qry .= "'$section_id[$id]',";
				}else{
					$qry .= "'0',";
				}
				$qry .= "{$skin_id[$id]})";
				$result = pg_query($sql_connect, $qry);
				$i++;
			}
			break;
		case "2":
			$skinlist = blogn_mod_db_viewskin_load();
			if ($skinlist[0]){
				if ($skinlist[1][0]["view_type"] == 2) {
					$qry = "UPDATE ".BLOGN_DB_PREFIX."_viewskin ";
					$qry .= "SET skin_id = {$skin_id} WHERE id = 0";
					$result = pg_query($sql_connect, $qry);
				}else{
					$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_viewskin";
					$result = pg_query($sql_connect, $qry);
					$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_viewskin(id, view_type, category_id, section_id, skin_id) ";
					$qry .= "VALUES(0, 2, 0, '0', {$skin_id})";
					$result = pg_query($sql_connect, $qry);
				}
			}else{
				$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_viewskin(id, view_type, category_id, section_id, skin_id) ";
				$qry .= "VALUES(0, 2, 0, '0', {$skin_id})";
				$result = pg_query($sql_connect, $qry);
			}
			break;
		case "3":
			//ジャンル別処理のジャンル追加処理
			$qry = "SELECT id FROM ".BLOGN_DB_PREFIX."_viewskin WHERE category_id = {$category_id} AND section_id = '{$section_id}'";
			$result = pg_query($sql_connect, $qry);
			if ($row = pg_fetch_array($result)) {
				$errdata[0] = false;
				$errdata[1] = BLOGN_MOD_BD_MES_26;
				return $errdata;
			}

			$qry = "SELECT id FROM ".BLOGN_DB_PREFIX."_viewskin ORDER BY id DESC LIMIT 1";
			$result = pg_query($sql_connect, $qry);
			$row = pg_fetch_array($result);
			$id = (INT)$row["id"] + 1;

			$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_viewskin(id, view_type, category_id, section_id, skin_id) ";
			$qry .= "VALUES({$id}, 2, {$category_id}, '{$section_id}', {$skin_id})";
			$result = pg_query($sql_connect, $qry);
			break;
	}
	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


function blogn_mod_db_viewskin_del($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_viewskin WHERE id = '{$id}'";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}


/* ----- アクセス制限のあるIPを遮断 ----- */
function blogn_mod_db_ip_check($ip) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_denyip";
	$result = pg_query($sql_connect, $qry);

	while($row = pg_fetch_array($result)) {
		list($key1, $key2, $key3, $key4,) = explode(".",$ip);
		list($deny_key1, $deny_key2, $deny_key3, $deny_key4) = explode(".",$row["ip"]);
		if ($deny_key1 == "*") $key1 = "*";
		if ($deny_key2 == "*") $key2 = "*";
		if ($deny_key3 == "*") $key3 = "*";
		if ($deny_key4 == "*") $key4 = "*";
		if ($deny_key1 == $key1 && $deny_key2 == $key2 && $deny_key3 == $key3 && $deny_key4 == $key4) return false;
	}
	return true;
}


/* ----- アクセス制限IPリストロード ----- */
function blogn_mod_db_denyip_load() {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "SELECT * FROM ".BLOGN_DB_PREFIX."_denyip";
	$result = pg_query($sql_connect, $qry);

	$list[0] = false;
	while($row = pg_fetch_array($result)) {
		$list[0] = true;
		$list[1][$row["id"]]["date"] = mktime(substr($row["date"],8,2),substr($row["date"],10,2),substr($row["date"],12,2),substr($row["date"],4,2),substr($row["date"],6,2),substr($row["date"],0,4));
		$list[1][$row["id"]]["ip"] = $row["ip"];
	}
	return $list;
}


/* ----- アクセス制限IP登録 ----- */
function blogn_mod_db_denyip_add($date, $ip) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry  = "INSERT INTO ".BLOGN_DB_PREFIX."_denyip(date, ip) ";
	$qry .= "VALUES(";
	$qry .= "'".gmdate("YmdHis",$date)."',";
	$qry .= "'".$ip."'";
	$qry .= ")";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


/* ----- アクセス制限IP削除 ----- */
function blogn_mod_db_denyip_delete($id) {
	$sql_connect = @pg_connect ("host=".BLOGN_DB_HOST." port=".BLOGN_DB_PORT." dbname=".BLOGN_DB_NAME." user=".BLOGN_DB_USER." password=".BLOGN_DB_PASS);

	$qry = "DELETE FROM ".BLOGN_DB_PREFIX."_denyip WHERE id = $id";
	$result = pg_query($sql_connect, $qry);

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	return $errdata;
}






?>
