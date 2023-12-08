<?php
//-------------------------------------------------------------------------
// Weblog PHP script BlognPlus（ぶろぐん＋）
// http://www.rone.jp/~blogn/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// テキスト保存タイプ
//
// LAST UPDATE 2007/02/01
//
// ・予約投稿処理の不具合を修正
// ・記事編集でカテゴリを変更した際の処理の不具合を修正
// ・トラックバック一覧取得の処理を修正
//
//-------------------------------------------------------------------------

/* エラーメッセージデータ集 */
include("db_errmes.php");

define("BLOGN_MOD_DB_ID_USER", 1);
define("BLOGN_MOD_DB_ID_PING", 2);
define("BLOGN_MOD_DB_ID_LINKGROUP", 3);
define("BLOGN_MOD_DB_ID_LINKLIST", 4);
define("BLOGN_MOD_DB_ID_CATEGORY1", 5);
define("BLOGN_MOD_DB_ID_CATEGORY2", 6);
define("BLOGN_MOD_DB_ID_FILES", 7);
define("BLOGN_MOD_DB_ID_INIT", 8);
define("BLOGN_MOD_DB_ID_LOG", 9);
define("BLOGN_MOD_DB_ID_COMMENT", 10);
define("BLOGN_MOD_DB_ID_TRACKBACK", 11);
define("BLOGN_MOD_DB_ID_SKIN", 12);
define("BLOGN_MOD_DB_ID_DENYIP", 13);


// レコード末尾から改行コード除去
function blogn_mod_db_rn_remove($val) {
	$val = ereg_replace( "\n$", "", $val);
	$val = ereg_replace( "\r$", "", $val);
	return $val;
}

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
	$str = blogn_html_tag_convert($str);
	if (!get_magic_quotes_gpc()) $str = addslashes($str);
	return $str;
}


function blogn_mod_db_file_lock() {
	$id = uniqid('lock');
	for ($i = 0; $i < 5; $i++) {
		if (@rename(BLOGN_INIDIR.'/lock', BLOGN_INIDIR.'/'.$id)) {
			return $id;
		}
		sleep(1);
	}
	return false;
}

function blogn_mod_db_file_unlock($id) {
	if (@rename(BLOGN_INIDIR.'/'.$id, BLOGN_INIDIR.'/lock')) {
		return true;
	}else{
		return false;
	}
}

/* ----- データ読み込み ----- */
function blogn_mod_db_RecordLoad($table) {
	// $table: データタイプ＆ファイル名（配列）
	//       : [0] log|cmt|trk|ini [1] ファイル

	// ディレクトリ選択
	switch ($table[0]) {
		case "log":
			$logdir = BLOGN_LOGDIR;
			break;
		case "cmt":
			$logdir = BLOGN_CMTDIR;
			break;
		case "trk":
			$logdir = BLOGN_TRKDIR;
			break;
		case "ini":
			$logdir = BLOGN_INIDIR;
			break;
	}
	// ユーザーリスト取得
	if (file_exists($logdir.$table[1])) {
		$error[0] = true;
		$error[1] = file($logdir.$table[1]);
	}else{
		$error[0] = false;
		$error[1] = BLOGN_MOD_DB_MES_03;
		$error[2] = $table[1];
	}
	return $error;
}


/* ----- データ追加 ----- */
function blogn_mod_db_RecordAdd($table, $record, $updown) {
	// $table: データタイプ＆ファイル名（配列）
	//       : [0] log|cmt|trk|ini [1] ファイル [2] id カウント有無 0=無し 1～=有り
	// $record: 保存データ（配列）
	//        : [0]名前 [1] 日付 ... 等
	// $updown: 0=down 1=up

	// ディレクトリ選択
	switch ($table[0]) {
		case "log":
			$logdir = BLOGN_LOGDIR;
			break;
		case "cmt":
			$logdir = BLOGN_CMTDIR;
			break;
		case "trk":
			$logdir = BLOGN_TRKDIR;
			break;
		case "ini":
			$logdir = BLOGN_INIDIR;
			break;
	}
	// ファイルが存在しない場合、新規作成
	if (!$fp1 = @fopen($logdir.$table[1], "r+")) {
		$oldmask = umask();
		umask(000);
		if (!$fp1 = @fopen($logdir.$table[1], "w")) {
			umask($oldmask);
			$errdata[0] = false;
			$errdata[1] = BLOGN_MOD_DB_MES_02;
			$errdata[2] = $table[1];
			return $errdata;
		}
		umask($oldmask);
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file($logdir.$table[1]);

	// id カウント
	if ($table[2]) {
		if (!$fp2 = @fopen(BLOGN_INIDIR."id.cgi", "r+")) {
			$oldmask = umask();
			umask(000);
			if (!$fp2 = @fopen(BLOGN_INIDIR."id.cgi", "w")) {
				fclose($fp1);

				// ロック解除
				if (!blogn_mod_db_file_unlock($lockkey)) {
					$error[0] = false;
					$error[1] = BLOGN_MOD_BD_MES_25;
					return $error;
				}
				umask($oldmask);
				$errdata[0] = false;
				$errdata[1] = BLOGN_MOD_DB_MES_02;
				$errdata[2] = "id.cgi";
				return $errdata;
			}
			umask($oldmask);
		}
		$ids = file(BLOGN_INIDIR."id.cgi");

		$key = explode(",", $ids[0]);
		$key[$table[2] - 1]++;
		$id = $key[$table[2] - 1];
		$ids[0] = implode(",", $key);
		fputs($fp2, implode('', $ids));
		fclose($fp2);
		$newrecord = $id.",";
	}else{
		$newrecord = "";
	}

	// 追加データ整形
	while(list($key, $val) = each($record)) {
		$val = blogn_mod_db_comma_change($val);
		$newrecord .= $val.",";
	}
	$newrecord .= "\n";

	// 先頭に新規データ追加
	if ($updown) fputs($fp1, $newrecord);
	if (count($oldrecord) != 0) fputs($fp1, implode('', $oldrecord));
	// 最後に新規データ追加
	if (!$updown) fputs($fp1, $newrecord);
	fclose($fp1);

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $id;
	return $errdata;
}


/* ----- データ変更 ----- */
function blogn_mod_db_RecordChange($table, $id, $record) {
	// $table: データ保存ディレクトリ＆ファイル名（配列）
	//       : [0] ディレクトリ [1] ファイル
	// $id: 変更ID
	// $record: 保存データ（配列）
	//        : [0]id [1] 日付 ... 等

	// ディレクトリ選択
	switch ($table[0]) {
		case "log":
			$logdir = BLOGN_LOGDIR;
			break;
		case "cmt":
			$logdir = BLOGN_CMTDIR;
			break;
		case "trk":
			$logdir = BLOGN_TRKDIR;
			break;
		case "ini":
			$logdir = BLOGN_INIDIR;
			break;
	}
	// ファイルが存在しない場合エラー
	if (!file_exists($logdir.$table[1])) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_03;
		$errdata[2] = $table[1];
		return $errdata;
	}
	if (!$fp = @fopen($logdir.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file($logdir.$table[1]);

	$newrecord = $id.",";
	// 変更データ整形
	while(list($key, $val) = each($record)) {
		$val = blogn_mod_db_comma_change($val);
		$newrecord .= $val.",";
	}
	$newrecord .= "\n";
	// データの変更
	while (list($key, $val) = each($oldrecord)) {
		list($checkid,) = explode(",", $val);
		if ($id == $checkid) {
			$oldrecord[$key] = $newrecord;
			break;
		}
	}
	$ioldrecord = implode('', $oldrecord);
	fputs($fp, $ioldrecord);
	ftruncate($fp, strlen($ioldrecord));
	fclose($fp);

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_05;
	$errdata[2] = $table[1];
	return $errdata;
}


/* ----- データ削除 ----- */
function blogn_mod_db_RecordDelete($table, $id) {
	// $table: データ保存ディレクトリ＆ファイル名（配列）
	//       : [0] ディレクトリ [1] ファイル
	// $id: 削除ID

	// ディレクトリ選択
	switch ($table[0]) {
		case "log":
			$logdir = BLOGN_LOGDIR;
			break;
		case "cmt":
			$logdir = BLOGN_CMTDIR;
			break;
		case "trk":
			$logdir = BLOGN_TRKDIR;
			break;
		case "ini":
			$logdir = BLOGN_INIDIR;
			break;
	}
	// ファイルが存在しない場合エラー
	if (!file_exists($logdir.$table[1])) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_03;
		$errdata[2] = $table[1];
		return $errdata;
	}
	if (!$fp = @fopen($logdir.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}
	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file($logdir.$table[1]);
	$cnt = count($oldrecord);

	// レコードから指定IDを削除
	while (list($key, $val) = each($oldrecord)) {
		list($checkid,) = explode(",",$val);
		if ($id == $checkid) {
			array_splice($oldrecord, $key, 1);
			break;
		}
	}

	$cntnew = count($oldrecord);

	if ($cnt == $cntnew) {
		fclose($fp);

		// ロック解除
		if (!blogn_mod_db_file_unlock($lockkey)) {
			$error[0] = false;
			$error[1] = BLOGN_MOD_BD_MES_25;
			return $error;
		}

		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_19;
		$errdata[2] = $table[1];
		return $errdata;
	}

	$ioldrecord = implode('', $oldrecord);
	fputs($fp, $ioldrecord);
	ftruncate($fp, strlen($ioldrecord));
	fclose($fp);

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_04;
	$errdata[2] = $table[1];
	return $errdata;

}


/* ----- 記事URL取得(NEXT & BACK) ----- */
function blogn_mod_db_log_nextback_url($user, $key_id) {
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	$nowdate = gmdate("YmdHis",time() + BLOGN_TIMEZONE);
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if (!$reserve || ($reserve && $nowdate > $date)) {
			if ($user || !$secret) {
				$list[$date.sprintf("%06d",$id)] = $val;
			}
		}
	}
	krsort($list);
	$flg = $nextflg = $backflg = false;
	while(list($key, $val) = each($list)) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if ($key_id == $id) {
			$flg = true;
		}elseif ($flg == true) {
			$backid = $id;
			$backflg = true;
			break;
		}else{
			$nextid = $id;
			$nextflg = true;
		}
	}
	$url[0] = $flg ;
	if ($nextflg) {
		$url[1] = $nextid;
	}else{
		$url[1] = -1;
	}
	if ($backflg) {
		$url[2] = $backid;
	}else{
		$url[2] = -1;
	}
	return $url;
}


/* ----- 記事件数ロード ----- */
function blogn_mod_db_archive_count_load($user, $key_count) {
	$log = @file(BLOGN_LOGDIR."log_key.cgi");
	if (!$log) {
		$error[0] = false;
		return $error;
	}
	$list = array();
	$list[0] = true;
	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	while(list($key, $val) = each($log)) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",",$val);
		if (!$reserve || ($reserve && $nowdate > $date)) {
			if ($user || !$secret) {
				$date = substr($date,0,6);
				if ($oldlist[$date]) {
					$oldlist[$date]++;
				}else{
					$oldlist[$date] = 1;
				}
			}
		}
	}

	if (!count($oldlist)) return $error[0] = false;

	krsort($oldlist);
	$i = 0;
	while(list($key, $val) = each($oldlist)) {
		$list[1][$key] = $val;
		$i++;
		if ($key_count == $i && $key_count != 0) break;
	}
	return $list;
}


/* ----- モード別記事件数ロード ----- */
function blogn_mod_db_log_count_load($user, $mode, $key_id) {
	$log = @file(BLOGN_LOGDIR."log_key.cgi");
	if (!$log) return 0;
	$totalcount = 0;
	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	switch ($mode) {
		case "normal":
			while(list($key, $val) = each($log)) {
				list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",",$val);
				if (!$reserve || ($reserve && $nowdate > $date)) {
					if ($user) {
						$totalcount = count($log);
					}else{
						if (!$secret) $totalcount++;
					}
				}
			}
			break;
		case "month":
			$checkdate = substr($key_id,0,6);
			while(list($key, $val) = each($log)) {
				list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",",$val);
				$reservedate = substr($date,0,6);
				if (!$reserve || ($reserve && $nowdate > $date)) {
					if ($user) {
						if ($checkdate == $reservedate) $totalcount++;
					}else{
						if ($checkdate == $reservedate && !$secret) $totalcount++;
					}
				}
			}
			break;
		case "day":
			$checkdate = substr($key_id,0,8);
			while(list($key, $val) = each($log)) {
				list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",",$val);
				$reservedate = substr($date,0,8);
				if (!$reserve || ($reserve && $nowdate > $date)) {
					if ($user) {
						if ($checkdate == $reservedate) $totalcount++;
					}else{
						if ($checkdate == $reservedate && !$secret) $totalcount++;
					}
				}
			}
			break;
		case "category":
			list($check_id_1, $check_id_2) = explode("-", $key_id);
			while(list($key, $val) = each($log)) {
				list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",",$val);
				if (!$reserve || ($reserve && $nowdate > $date)) {
					list($id_1, $id_2) = explode("|", $category);
					if (!trim($check_id_2)) {
						if ($user) {
							if ($check_id_1 == $id_1) $totalcount++;
						}else{
							if ($check_id_1 == $id_1 && !$secret) $totalcount++;
						}
					}else{
						if ($user) {
							if ($check_id_1 == $id_1 && $check_id_2 == $id_2) $totalcount++;
						}else{
							if ($check_id_1 == $id_1 && $check_id_2 == $id_2 && !$secret) $totalcount++;
						}
					}
				}
			}
			break;
		case "user":
			while(list($key, $val) = each($log)) {
				list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",",$val);
				if (!$reserve || ($reserve && $nowdate > $date)) {
					if ($user) {
						if ($user_id == $key_id) $totalcount++;
					}else{
						if ($user_id == $key_id && !$secret) $totalcount++;
					}
				}
			}
			break;
	}
	return $totalcount;
}


/* ----- 記事ロード編集用（指定記事用） ----- */
function blogn_mod_db_log_load_for_editor($req_id) {
	// 一覧リスト取得
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if ($req_id == $id) {
			$key_date = substr($date,0,6);
			break;
		}
	}

	$table[1] = "log".$key_date.".cgi";
	$loglist = blogn_mod_db_RecordLoad($table);

	if (!$loglist[0]) {
		$error[0] = false;
		$error[1] = $loglist[1];
		$error[2] = $loglist[2];
		return $error;
	}
	while(list($logkey, $logval) = each($loglist[1])) {
		list($logid, $logdate, $reserve, $secret, $user_id, $category, $comment_ok, $trackback_ok, $title, $mes, $more, $br_change, ) = explode(",", $logval);
		if ($req_id == $logid) {
			$newlog[0] = true;
			$newlog[1]["id"] = $logid;
			$newlog[1]["date"] = $date;
			$newlog[1]["reserve"] = $reserve;
			$newlog[1]["secret"] = $secret;
			$newlog[1]["user_id"] = $user_id;
			$newlog[1]["category"] = $category;
			$newlog[1]["comment_ok"] = $comment_ok;
			$newlog[1]["trackback_ok"] = $trackback_ok;
			$newlog[1]["title"] = blogn_mod_db_comma_restore($title);
			$newlog[1]["mes"] = blogn_mod_db_comma_restore($mes);
			$newlog[1]["more"] = blogn_mod_db_comma_restore($more);
			$newlog[1]["br_change"] = $br_change;
			return $newlog;
			break;
		}
	}
	$newlog[0] = false;
	$newlog[1] = BLOGN_MOD_DB_MES_19;
	return $newlog;
}

/* ----- 記事ロード表示用（指定記事用） ----- */
function blogn_mod_db_log_load_for_entory($user, $req_id) {
	// 一覧リスト取得
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if ($req_id == $id) {
			$key_date = substr($date,0,6);
			break;
		}
	}

	$table[1] = "log".$key_date.".cgi";
	$loglist = blogn_mod_db_RecordLoad($table);

	if (!$loglist[0]) {
		$error[0] = false;
		$error[1] = $loglist[1];
		$error[2] = $loglist[2];
		return $error;
	}
	while(list($logkey, $logval) = each($loglist[1])) {
		list($logid, $logdate, $reserve, $secret, $user_id, $category, $comment_ok, $trackback_ok, $title, $mes, $more, $br_change, ) = explode(",", $logval);
		if ($req_id == $logid) {
			if ($user || !$secret) {
				$newlog[0] = true;
				$newlog[1]["id"] = $logid;
				$newlog[1]["date"] = $date;
				$newlog[1]["reserve"] = $reserve;
				$newlog[1]["secret"] = $secret;
				$newlog[1]["user_id"] = $user_id;
				$newlog[1]["category"] = $category;
				$newlog[1]["comment_ok"] = $comment_ok;
				$newlog[1]["trackback_ok"] = $trackback_ok;
				$newlog[1]["title"] = blogn_mod_db_comma_restore($title);
				$newlog[1]["mes"] = blogn_mod_db_comma_restore($mes);
				$newlog[1]["more"] = blogn_mod_db_comma_restore($more);
				$newlog[1]["br_change"] = $br_change;
				return $newlog;
				break;
			}
		}
	}
	$newlog[0] = false;
	$newlog[1] = BLOGN_MOD_DB_MES_19;
	return $newlog;
}


/* ----- 記事ロード（エクスポート用） ----- */
function blogn_mod_db_log_load_for_all() {
	// 一覧リスト取得
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}

	$userdata = blogn_mod_db_user_profile_load($uid);

	$list = array();
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		$list[$date.sprintf("%06d",$id)] = $val;
	}
	ksort($list);
	$listtotal = count($list);
	$i = 0;
	while(list($key, $val) = each($list)) {
		list($id, $date,,,,, ) = explode(",", $val);
		$filename = "log".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$loglist = blogn_mod_db_RecordLoad($table);
		}
		reset($loglist[1]);
		while(list($logkey, $logval) = each($loglist[1])) {
			list($logid, $logdate, $reserve, $secret, $user_id, $category, $comment_ok, $trackback_ok, $title, $mes, $more, $br_change, ) = explode(",", $logval);
			if ($id == $logid) {
				$newlog[1][$i]["id"] = $logid;
				$newlog[1][$i]["date"] = $date;
				$newlog[1][$i]["reserve"] = $reserve;
				$newlog[1][$i]["secret"] = $secret;
				$newlog[1][$i]["user_id"] = $user_id;
				$newlog[1][$i]["category"] = $category;
				$newlog[1][$i]["comment_ok"] = $comment_ok;
				$newlog[1][$i]["trackback_ok"] = $trackback_ok;
				$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($title);
				$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($mes);
				$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($more);
				$newlog[1][$i]["br_change"] = $br_change;
				$i++;
				break;
			}
		}
	}
	if ($i > 0) {
		$newlog[0] = true;
		$newlog[2] = $listtotal;
	}else{
		$newlog[0] = false;
	}
	return $newlog;
}


/* ----- 記事ロード（一覧用） ----- */
function blogn_mod_db_log_load_for_list($uid, $start_key, $key_count) {
	// 一覧リスト取得
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}

	$userdata = blogn_mod_db_user_profile_load($uid);

	$list = array();
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if ($userdata["admin"] || $uid == $user_id) {
			$list[$date.sprintf("%06d",$id)] = $val;
		}
	}
	krsort($list);
	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, $date,,,,, ) = explode(",", $val);
		$filename = "log".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$loglist = blogn_mod_db_RecordLoad($table);
		}
		reset($loglist[1]);
		while(list($logkey, $logval) = each($loglist[1])) {
			list($logid, $logdate, $reserve, $secret, $user_id, $category, $comment_ok, $trackback_ok, $title, $mes, $more, $br_change, ) = explode(",", $logval);
			if ($id == $logid) {
				$newlog[1][$i]["id"] = $logid;
				$newlog[1][$i]["date"] = $date;
				$newlog[1][$i]["reserve"] = $reserve;
				$newlog[1][$i]["secret"] = $secret;
				$newlog[1][$i]["user_id"] = $user_id;
				$newlog[1][$i]["category"] = $category;
				$newlog[1][$i]["comment_ok"] = $comment_ok;
				$newlog[1][$i]["trackback_ok"] = $trackback_ok;
				$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($title);
				$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($mes);
				$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($more);
				$newlog[1][$i]["br_change"] = $br_change;
				$i++;
				break;
			}
		}
	}
	if ($i > 0) {
		$newlog[0] = true;
		$newlog[2] = $listtotal;
	}else{
		$newlog[0] = false;
	}
	return $newlog;
}


/* ----- 記事ロード（指定カテゴリ用） ----- */
function blogn_mod_db_log_load_list_for_category($user, $start_key, $key_count, $key_category) {
	// 一覧リスト取得
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();

	$userdata = blogn_mod_db_user_profile_load($user);

	list($key_category_1, $key_category_2) = explode("-", $key_category);
	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if ($userdata["admin"] || $user == $user_id) {
			list($category_1, $category_2) = explode("|", $category);
			if (!$key_category_2) {
				if ($key_category_1 == $category_1) $list[$date.sprintf("%06d",$id)] = $val;
			}else{
				if ($key_category_1 == $category_1 && $key_category_2 == $category_2) $list[$date.sprintf("%06d",$id)] = $val;
			}
		}
	}
	krsort($list, SORT_NUMERIC);

	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, $date,,,,, ) = explode(",", $val);
		$filename = "log".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$loglist = blogn_mod_db_RecordLoad($table);
		}
		reset($loglist[1]);
		while(list($logkey, $logval) = each($loglist[1])) {
			list($logid, $logdate, $reserve, $secret, $user_id, $category, $comment_ok, $trackback_ok, $title, $mes, $more, $br_change, ) = explode(",", $logval);
			if ($id == $logid) {
				$newlog[1][$i]["id"] = $logid;
				$newlog[1][$i]["date"] = $logdate;
				$newlog[1][$i]["reserve"] = $reserve;
				$newlog[1][$i]["secret"] = $secret;
				$newlog[1][$i]["user_id"] = $user_id;
				$newlog[1][$i]["category"] = $category;
				$newlog[1][$i]["comment_ok"] = $comment_ok;
				$newlog[1][$i]["trackback_ok"] = $trackback_ok;
				$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($title);
				$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($mes);
				$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($more);
				$newlog[1][$i]["br_change"] = $br_change;
				$i++;
				break;
			}
		}
	}
	if ($i > 0) {
		$newlog[0] = true;
		$newlog[2] = $listtotal;
	}else{
		$newlog[0] = false;
	}
	return $newlog;
}


/* ----- 記事ロード（一般用） ----- */
function blogn_mod_db_log_load_for_viewer($user, $start_key, $key_count) {
	// 一覧リスト取得
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if (!$reserve || ($reserve && $nowdate > $date)) {
			if ($user || !$secret) {
				$list[$date.sprintf("%06d",$id)] = $val;
			}
		}
	}
	krsort($list);
	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, $date,,,,, ) = explode(",", $val);
		$filename = "log".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$loglist = blogn_mod_db_RecordLoad($table);
		}
		reset($loglist[1]);
		while(list($logkey, $logval) = each($loglist[1])) {
			list($logid, $logdate, $reserve, $secret, $user_id, $category, $comment_ok, $trackback_ok, $title, $mes, $more, $br_change, ) = explode(",", $logval);
			if ($id == $logid) {
				$newlog[1][$i]["id"] = $logid;
				$newlog[1][$i]["date"] = $date;
				$newlog[1][$i]["reserve"] = $reserve;
				$newlog[1][$i]["secret"] = $secret;
				$newlog[1][$i]["user_id"] = $user_id;
				$newlog[1][$i]["category"] = $category;
				$newlog[1][$i]["comment_ok"] = $comment_ok;
				$newlog[1][$i]["trackback_ok"] = $trackback_ok;
				$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($title);
				$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($mes);
				$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($more);
				$newlog[1][$i]["br_change"] = $br_change;
				$i++;
				break;
			}
		}
	}
	if ($i > 0) {
		$newlog[0] = true;
		$newlog[2] = $listtotal;
	}else{
		$newlog[0] = false;
	}
	return $newlog;
}


/* ----- 記事ロード（指定月用） ----- */
function blogn_mod_db_log_load_for_month($user, $start_key, $key_count, $key_date, $vorder) {
	// 一覧リスト取得
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if (!$reserve || ($reserve && $nowdate > $date)) {
			if (($user || !$secret) && $key_date == substr($date,0,6)) {
				$list[$date.sprintf("%06d",$id)] = $val;
			}
		}
	}
	if ($vorder) {
		ksort($list);
	}else{
		krsort($list);
	}
	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, $date,,,,, ) = explode(",", $val);
		$filename = "log".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$loglist = blogn_mod_db_RecordLoad($table);
		}
		reset($loglist[1]);
		while(list($logkey, $logval) = each($loglist[1])) {
			list($logid, $logdate, $reserve, $secret, $user_id, $category, $comment_ok, $trackback_ok, $title, $mes, $more, $br_change, ) = explode(",", $logval);
			if ($id == $logid) {
				$newlog[1][$i]["id"] = $logid;
				$newlog[1][$i]["date"] = $logdate;
				$newlog[1][$i]["reserve"] = $reserve;
				$newlog[1][$i]["secret"] = $secret;
				$newlog[1][$i]["user_id"] = $user_id;
				$newlog[1][$i]["category"] = $category;
				$newlog[1][$i]["comment_ok"] = $comment_ok;
				$newlog[1][$i]["trackback_ok"] = $trackback_ok;
				$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($title);
				$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($mes);
				$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($more);
				$newlog[1][$i]["br_change"] = $br_change;
				$i++;
				break;
			}
		}
	}
	if ($i > 0) {
		$newlog[0] = true;
		$newlog[2] = $listtotal;
	}else{
		$newlog[0] = false;
	}
	return $newlog;


}


/* ----- 記事ロード（指定日用） ----- */
function blogn_mod_db_log_load_for_day($user, $start_key, $key_count, $key_date) {
	// 一覧リスト取得
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if (!$reserve || ($reserve && $nowdate > $date)) {
			if (($user || !$secret) && $key_date == substr($date,0,8)) {
				$list[$date.sprintf("%06d",$id)] = $val;
			}
		}
	}
	krsort($list);
	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, $date,,,,, ) = explode(",", $val);
		$filename = "log".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$loglist = blogn_mod_db_RecordLoad($table);
		}
		reset($loglist[1]);
		while(list($logkey, $logval) = each($loglist[1])) {
			list($logid, $logdate, $reserve, $secret, $user_id, $category, $comment_ok, $trackback_ok, $title, $mes, $more, $br_change, ) = explode(",", $logval);
			if ($id == $logid) {
				$newlog[1][$i]["id"] = $logid;
				$newlog[1][$i]["date"] = $logdate;
				$newlog[1][$i]["reserve"] = $reserve;
				$newlog[1][$i]["secret"] = $secret;
				$newlog[1][$i]["user_id"] = $user_id;
				$newlog[1][$i]["category"] = $category;
				$newlog[1][$i]["comment_ok"] = $comment_ok;
				$newlog[1][$i]["trackback_ok"] = $trackback_ok;
				$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($title);
				$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($mes);
				$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($more);
				$newlog[1][$i]["br_change"] = $br_change;
				$i++;
				break;
			}
		}
	}
	if ($i > 0) {
		$newlog[0] = true;
		$newlog[2] = $listtotal;
	}else{
		$newlog[0] = false;
	}
	return $newlog;
}


/* ----- 記事ロード（指定カテゴリ用） ----- */
function blogn_mod_db_log_load_for_category($user, $start_key, $key_count, $key_category, $vorder) {
	// 一覧リスト取得
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();

	list($key_category_1, $key_category_2) = explode("-", $key_category);
	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if (!$reserve || ($reserve && $nowdate > $date)) {
			if ($user || !$secret) {
				list($category_1, $category_2) = explode("|", $category);
				if (!$key_category_2) {
					if ($key_category_1 == $category_1) $list[$date.sprintf("%06d",$id)] = $val;
				}else{
					if ($key_category_1 == $category_1 && $key_category_2 == $category_2) $list[$date.sprintf("%06d",$id)] = $val;
				}
			}
		}
	}
	if ($vorder) {
		ksort($list, SORT_NUMERIC);
	}else{
		krsort($list, SORT_NUMERIC);
	}
	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, $date,,,,, ) = explode(",", $val);
		$filename = "log".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$loglist = blogn_mod_db_RecordLoad($table);
		}
		reset($loglist[1]);
		while(list($logkey, $logval) = each($loglist[1])) {
			list($logid, $logdate, $reserve, $secret, $user_id, $category, $comment_ok, $trackback_ok, $title, $mes, $more, $br_change, ) = explode(",", $logval);
			if ($id == $logid) {
				$newlog[1][$i]["id"] = $logid;
				$newlog[1][$i]["date"] = $logdate;
				$newlog[1][$i]["reserve"] = $reserve;
				$newlog[1][$i]["secret"] = $secret;
				$newlog[1][$i]["user_id"] = $user_id;
				$newlog[1][$i]["category"] = $category;
				$newlog[1][$i]["comment_ok"] = $comment_ok;
				$newlog[1][$i]["trackback_ok"] = $trackback_ok;
				$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($title);
				$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($mes);
				$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($more);
				$newlog[1][$i]["br_change"] = $br_change;
				$i++;
				break;
			}
		}
	}
	if ($i > 0) {
		$newlog[0] = true;
		$newlog[2] = $listtotal;
	}else{
		$newlog[0] = false;
	}
	return $newlog;
}


/* ----- 記事ロード（指定ユーザー用） ----- */
function blogn_mod_db_log_load_for_user($user, $start_key, $key_count, $key_user) {
	// 一覧リスト取得
	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	$nowdate = gmdate("YmdHis", time() + BLOGN_TIMEZONE);
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date, $reserve, $secret, $user_id, $category, ) = explode(",", $val);
		if (!$reserve || ($reserve && $nowdate > $date)) {
			if (($user || !$secret) && ($key_user == $user_id)) {
				$list[$date.sprintf("%06d",$id)] = $val;
			}
		}
	}
	krsort($list);
	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, $date,,,,, ) = explode(",", $val);
		$filename = "log".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$loglist = blogn_mod_db_RecordLoad($table);
		}
		reset($loglist[1]);
		while(list($logkey, $logval) = each($loglist[1])) {
			list($logid, $logdate, $reserve, $secret, $user_id, $category, $comment_ok, $trackback_ok, $title, $mes, $more, $br_change, ) = explode(",", $logval);
			if ($id == $logid) {
				$newlog[1][$i]["id"] = $logid;
				$newlog[1][$i]["date"] = $logdate;
				$newlog[1][$i]["reserve"] = $reserve;
				$newlog[1][$i]["secret"] = $secret;
				$newlog[1][$i]["user_id"] = $user_id;
				$newlog[1][$i]["category"] = $category;
				$newlog[1][$i]["comment_ok"] = $comment_ok;
				$newlog[1][$i]["trackback_ok"] = $trackback_ok;
				$newlog[1][$i]["title"] = blogn_mod_db_comma_restore($title);
				$newlog[1][$i]["mes"] = blogn_mod_db_comma_restore($mes);
				$newlog[1][$i]["more"] = blogn_mod_db_comma_restore($more);
				$newlog[1][$i]["br_change"] = $br_change;
				$i++;
				break;
			}
		}
	}
	if ($i > 0) {
		$newlog[0] = true;
		$newlog[2] = $listtotal;
	}else{
		$newlog[0] = false;
	}
	return $newlog;
}


/* ----- 記事件数 ----- */
function blogn_mod_db_log_count() {
	$log = @file(BLOGN_LOGDIR."log_key.cgi");
	if (!$log) {
		$count = 0;
	}else{
		$count = count($log);
	}
	return $count;
}


/* ----- 記事登録 ----- */
function blogn_mod_db_log_add($user_id, $date, $reserve, $secret, $comment_ok, $trackback_ok, $category, $title, $mes, $more, $br_change) {
	$table[0] = "log";
	$table[1] = "log".substr($date,0,6).".cgi";
	$table[2] = BLOGN_MOD_DB_ID_LOG;
	$record[0] = $date;
	$record[1] = $reserve;
	$record[2] = $secret;
	$record[3] = $user_id;
	$record[4] = $category;
	$record[5] = $comment_ok;
	$record[6] = $trackback_ok;
	$record[7] = $title;
	$record[8] = $mes;
	$record[9] = $more;
	$record[10] = $br_change;
	$updown = 1;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	if (!$errdata[0]) return $errdata;

	$record = array();
	// 記事履歴保存
	// ファイルが存在しない場合、新規作成
	if (!$fp = @fopen(BLOGN_LOGDIR."log_key.cgi", "r+")) {
		$oldmask = umask();
		umask(000);
		if (!$fp = @fopen(BLOGN_LOGDIR."log_key.cgi", "w")) {
			umask($oldmask);
			$errdata[0] = false;
			$errdata[1] = BLOGN_MOD_DB_MES_02;
			$errdata[2] = $table[1];
			return $errdata;
		}
		umask($oldmask);
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file(BLOGN_LOGDIR."log_key.cgi");

	$record[0] = $errdata[2];
	$record[1] = $date;
	$record[2] = $reserve;
	$record[3] = $secret;
	$record[4] = $user_id;
	$record[5] = $category;
	// 追加データ整形
	$newrecord = "";
	while(list($key, $val) = each($record)) {
		$val = blogn_mod_db_comma_change($val);
		$tmprecord .= $val.",";
	}
	$tmprecord .= "\n";
	$oldrecord[] = $tmprecord;

	if (count($oldrecord) > 1) {
		// log_key ソート
		while(list($key, $val) = each($oldrecord)) {
			list($id, $date) = explode(",", $val, 2);
			$newrecord[$date.sprintf("%06d",$id)] = $val;
		}
		krsort($newrecord);
		fputs($fp, implode('', $newrecord));
	}else{
		fputs($fp, $tmprecord);
	}
	fclose($fp);

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $errdata[2];
	return $errdata;
}


/* ----- 記事更新 ----- */
function blogn_mod_db_log_change($id, $date, $reserve, $secret, $comment_ok, $trackback_ok, $category, $title, $mes, $more, $br_change) {
	$table[0] = "log";
	$table[1] = "log_key.cgi";

	// ファイルが存在しない場合エラー
	if (!file_exists(BLOGN_LOGDIR.$table[1])) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_03;
		$errdata[2] = $table[1];
		return $errdata;
	}
	if (!$fp = @fopen(BLOGN_LOGDIR.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file(BLOGN_LOGDIR.$table[1]);
	$found = false;
	// レコードから指定IDを検索
	while (list($key, $val) = each($oldrecord)) {
		list($checkid, $checkdate, $checkreserve, $checksecret, $checkuserid,) = explode(",",$val);
		if ($id == $checkid) {
			$oldrecord[$key] = $id.",". $date.",".$reserve.",".$secret.",".$checkuserid.",".$category.",\n";
			$oldfiledate = substr($checkdate,0,6);
			$newfiledate = substr($date,0,6);
			$found = true;
			break;
		}
	}
	if (!$found) {
		fclose($fp);

		// ロック解除
		if (!blogn_mod_db_file_unlock($lockkey)) {
			$error[0] = false;
			$error[1] = BLOGN_MOD_BD_MES_25;
			return $error;
		}

		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_19;
		$errdata[2] = $table[1];
		return $errdata;
	}

	$ioldrecord = implode('', $oldrecord);
	fputs($fp, $ioldrecord);
	ftruncate($fp, strlen($ioldrecord));

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$table[1] = "log".$newfiledate.".cgi";
	$table[2] = 0;

	// 更新データの月が変更されているかチェック
	if ($oldfiledate == $newfiledate) {
		// 同じであれば同一ファイル内でデータ更新
		$record[0] = $date;
		$record[1] = $reserve;
		$record[2] = $secret;
		$record[3] = $checkuserid;
		$record[4] = $category;
		$record[5] = $comment_ok;
		$record[6] = $trackback_ok;
		$record[7] = $title;
		$record[8] = $mes;
		$record[9] = $more;
		$record[10] = $br_change;

		$errdata = blogn_mod_db_RecordChange($table, $id, $record);
		return $errdata;
	}else{
		// 異なれば旧データは削除して新データを追加（IDは引継ぎ）
		$record[0] = $id;
		$record[1] = $date;
		$record[2] = $reserve;
		$record[3] = $secret;
		$record[4] = $checkuserid;
		$record[5] = $category;
		$record[6] = $comment_ok;
		$record[7] = $trackback_ok;
		$record[8] = $title;
		$record[9] = $mes;
		$record[10] =$more;
		$record[11] = $br_change;

		$updown = 1;
		$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);

		$table[1] = "log".$oldfiledate.".cgi";
		$errdata = blogn_mod_db_RecordDelete($table, $id);

		$errdata[0] = true;
		$errdata[1] = BLOGN_MOD_DB_MES_05;
		return $errdata;
	}
}


/* ----- 記事更新（タイトル＆カテゴリーのみ） ----- */
function blogn_mod_db_log_title_change($id, $category, $title) {
	$table[0] = "log";
	$table[1] = "log_key.cgi";

	// ファイルが存在しない場合エラー
	if (!file_exists(BLOGN_LOGDIR.$table[1])) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_03;
		$errdata[2] = $table[1];
		return $errdata;
	}
	if (!$fp = @fopen(BLOGN_LOGDIR.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file(BLOGN_LOGDIR.$table[1]);
	$found = false;
	// レコードから指定IDを検索
	while (list($key, $val) = each($oldrecord)) {
		list($checkid, $checkdate, $checkreserve, $checksecret, $checkuserid,) = explode(",",$val);
		if ($id == $checkid) {
			$date = $checkdate;
			$reserve = $checkreserve;
			$secret = $checksecret;
			$oldrecord[$key] = $id.",". $date.",".$reserve.",".$secret.",".$checkuserid.",".$category.",\n";
			$newfiledate = substr($checkdate,0,6);
			$found = true;
			break;
		}
	}
	if (!$found) {
		fclose($fp);

		// ロック解除
		if (!blogn_mod_db_file_unlock($lockkey)) {
			$error[0] = false;
			$error[1] = BLOGN_MOD_BD_MES_25;
			return $error;
		}

		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_19;
		$errdata[2] = $table[1];
		return $errdata;
	}

	$ioldrecord = implode('', $oldrecord);
	fputs($fp, $ioldrecord);
	ftruncate($fp, strlen($ioldrecord));

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$table[1] = "log".$newfiledate.".cgi";
	$table[2] = 0;

	$tmprecord = blogn_mod_db_log_load_for_editor($id);

	$record[0] = $tmprecord[1]["date"];
	$record[1] = $tmprecord[1]["reserve"];
	$record[2] = $tmprecord[1]["secret"];
	$record[3] = $checkuserid;
	$record[4] = $category;
	$record[5] = $tmprecord[1]["comment_ok"];
	$record[6] = $tmprecord[1]["trackback_ok"];
	$record[7] = $title;
	$record[8] = $tmprecord[1]["mes"];
	$record[9] = $tmprecord[1]["more"];
	$record[10] = $tmprecord[1]["br_change"];

	$errdata = blogn_mod_db_RecordChange($table, $id, $record);
	return $errdata;
}


/* ----- 記事削除 ----- */
function blogn_mod_db_log_delete($id) {

	$table[0] = "log";
	$table[1] = "log_key.cgi";

	// ファイルが存在しない場合エラー
	if (!file_exists(BLOGN_LOGDIR.$table[1])) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_03;
		$errdata[2] = $table[1];
		return $errdata;
	}
	if (!$fp = @fopen(BLOGN_LOGDIR.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file(BLOGN_LOGDIR.$table[1]);
	$cnt = count($oldrecord);
	// レコードから指定IDを削除
	while (list($key, $val) = each($oldrecord)) {
		list($checkid, $date, ) = explode(",",$val);
		if ($id == $checkid) {
			$filedate = substr($date,0,6);
			array_splice($oldrecord, $key, 1);
			break;
		}
	}
	$cntnew = count($oldrecord);

	if ($cnt == $cntnew) {
		fclose($fp);

		// ロック解除
		if (!blogn_mod_db_file_unlock($lockkey)) {
			$error[0] = false;
			$error[1] = BLOGN_MOD_BD_MES_25;
			return $error;
		}

		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_19;
		$errdata[2] = $table[1];
		return $errdata;
	}

	$ioldrecord = implode('', $oldrecord);
	fputs($fp, $ioldrecord);
	ftruncate($fp, strlen($ioldrecord));
	fclose($fp);

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$table[1] = "log".$filedate.".cgi";

	$errdata = blogn_mod_db_RecordDelete($table, $id);
	return $errdata;
}


/* ----- コメント削除（指定ログID用） ----- */
function blogn_mod_db_log_comment_delete($ent_id) {
	// 一覧リスト取得
	$table[0] = "cmt";
	$table[1] = "cmt_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	while(list($key, $val) = each($logkey[1])) {
		list($id, $eid, $date, $name, ) = explode(",", $val);
		if ($ent_id == $eid) {
			$error = blogn_mod_db_comment_delete($id);
		}
	}
}


/* ----- トラックバック削除（指定ログID用） ----- */
function blogn_mod_db_log_trackback_delete($ent_id) {
	// 一覧リスト取得
	$table[0] = "trk";
	$table[1] = "trk_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	while(list($key, $val) = each($logkey[1])) {
		list($id, $eid, $date, $name, $title, ) = explode(",", $val);
		if ($ent_id == $eid) {
			$error = blogn_mod_db_trackback_delete($id);
		}
	}
}


/* ----- コメント数ロード（一覧用） ----- */
function blogn_mod_db_comment_count() {
	// 一覧リスト取得
	$table[0] = "cmt";
	$table[1] = "cmt_key.cgi";
	$cmtkey = blogn_mod_db_RecordLoad($table);
	if (!$cmtkey[0]) {
		$count = 0;
	}else{
		$count = count($cmtkey[1]);
	}
	return $count;
}


/* ----- コメント数ロード（一覧用） ----- */
function blogn_mod_db_comment_count_load($ent_id) {
	// 一覧リスト取得
	$table[0] = "cmt";
	$table[1] = "cmt_key.cgi";
	$cmtkey = blogn_mod_db_RecordLoad($table);

	if (!$cmtkey[0]) {
		$error[0] = true;
		$error[1] = 0;
		return $error;
	}
	$list = array();
	while(list($key, $val) = each($cmtkey[1])) {
		list($id, $eid, $secret, $date, $name, ) = explode(",", $val);
		if ($ent_id == $eid) {
			$list[$date.sprintf("%06d",$id)] = $val;
		}
	}
	$listcount[0] = true;
	$listcount[1] = count($list);
	return $listcount;
}


/* ----- コメントロード（一覧用） ----- */
function blogn_mod_db_comment_load_for_list($ent_id, $start_key, $key_count) {
	// 一覧リスト取得
	$table[0] = "cmt";
	$table[1] = "cmt_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	while(list($key, $val) = each($logkey[1])) {
		list($id, $eid, $secret, $date, $name, ) = explode(",", $val);
		if ($ent_id == $eid) {
			$list[$date.sprintf("%06d",$id)] = $val;
		}
	}
	krsort($list);
	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, , ,$date, ) = explode(",", $val);
		$filename = "cmt".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$cmtlist = blogn_mod_db_RecordLoad($table);
		}
		reset($cmtlist[1]);
		while(list($cmtkey, $cmtval) = each($cmtlist[1])) {
			list($cmtid, $entid, $cmtsecret, $logdate, $name, $email, $url, $comment, $ip, $agent,) = explode(",", $cmtval);
			if ($id == $cmtid) {
				$newcmt[1][$i]["id"] = $cmtid;
				$newcmt[1][$i]["entry_id"] = $entid;
				$newcmt[1][$i]["secret"] = $cmtsecret;
				$newcmt[1][$i]["date"] = $logdate;
				$newcmt[1][$i]["name"] = $name;
				$newcmt[1][$i]["email"] = $email;
				$newcmt[1][$i]["url"] = $url;
				$newcmt[1][$i]["comment"] = $comment;
				$newcmt[1][$i]["ip"] = $ip;
				$newcmt[1][$i]["agent"] = $agent;
				$i++;
				break;
			}
		}
	}
	if ($i > 0) {
		$newcmt[0] = true;
		$newcmt[2] = $listtotal;
	}else{
		$newcmt[0] = false;
	}
	return $newcmt;
}


/* ----- コメントロード（新着一覧用） ----- */
function blogn_mod_db_comment_load_for_new($user, $start_key, $key_count) {
	// 一覧リスト取得
	$table[0] = "cmt";
	$table[1] = "cmt_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = false;
		$error[1] = $logkey[1];
		$error[2] = $logkey[2];
		return $error;
	}
	$list = array();
	while(list($key, $val) = each($logkey[1])) {
		list($id, $eid, $secret, $date, $name) = explode(",", $val);
		if ($user || !$secret) $list[$date.sprintf("%06d",$id)] = $val;
	}
	krsort($list);
	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, , ,$date, ) = explode(",", $val);
		$filename = "cmt".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$cmtlist = blogn_mod_db_RecordLoad($table);
		}
		reset($cmtlist[1]);
		while(list($cmtkey, $cmtval) = each($cmtlist[1])) {
			list($cmtid, $entid, $cmtsecret, $logdate, $name, $email, $url, $comment, $ip, $agent,) = explode(",", $cmtval);
			if ($id == $cmtid) {
				$newcmt[1][$i]["id"] = $cmtid;
				$newcmt[1][$i]["entry_id"] = $entid;
				$newcmt[1][$i]["secret"] = $cmtsecret;
				$newcmt[1][$i]["date"] = $logdate;
				$newcmt[1][$i]["name"] = $name;
				$newcmt[1][$i]["email"] = $email;
				$newcmt[1][$i]["url"] = $url;
				$newcmt[1][$i]["comment"] = $comment;
				$newcmt[1][$i]["ip"] = $ip;
				$newcmt[1][$i]["agent"] = $agent;
				$i++;
				break;
			}
		}
	}
	if ($i > 0) {
		$newcmt[0] = true;
		$newcmt[2] = $listtotal;
	}else{
		$newcmt[0] = false;
	}
	return $newcmt;
}


/* ----- コメント登録 ----- */
function blogn_mod_db_comment_add($entid, $secret, $date, $name, $email, $url, $comment, $ip, $agent) {

	$table[0] = "cmt";
	$table[1] = "cmt".substr($date,0,6).".cgi";
	$table[2] = BLOGN_MOD_DB_ID_COMMENT;
	$record[0] = $entid;
	$record[1] = $secret;
	$record[2] = $date;
	$record[3] = $name;
	$record[4] = $email;
	$record[5] = $url;
	$record[6] = blogn_mod_db_rn2br($comment);
	$record[7] = $ip;
	$record[8] = $agent;
	$updown = 1;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	if (!$errdata[0]) return $errdata;

	$record = array();
	// 記事履歴保存
	// ファイルが存在しない場合、新規作成
	if (!$fp = @fopen(BLOGN_CMTDIR."cmt_key.cgi", "r+")) {
		$oldmask = umask();
		umask(000);
		if (!$fp = @fopen(BLOGN_CMTDIR."cmt_key.cgi", "w")) {
			umask($oldmask);
			$errdata[0] = false;
			$errdata[1] = BLOGN_MOD_DB_MES_02;
			$errdata[2] = $table[1];
			return $errdata;
		}
		umask($oldmask);
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file(BLOGN_CMTDIR."cmt_key.cgi");

	$record[0] = $errdata[2];
	$record[1] = $entid;
	$record[2] = $secret;
	$record[3] = $date;
	$record[4] = $name;
	// 追加データ整形
	$newrecord = "";
	while(list($key, $val) = each($record)) {
		$val = blogn_mod_db_comma_change($val);
		$newrecord .= $val.",";
	}
	$newrecord .= "\n";

	// 先頭に新規データ追加
	fputs($fp, $newrecord);
	if (count($oldrecord) != 0) fputs($fp, implode('', $oldrecord));

	fclose($fp);

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $errdata[2];
	return $errdata;
}


/* ----- コメント削除 ----- */
function blogn_mod_db_comment_delete($id) {

	$table[0] = "cmt";
	$table[1] = "cmt_key.cgi";

	// ファイルが存在しない場合エラー
	if (!file_exists(BLOGN_CMTDIR.$table[1])) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_03;
		$errdata[2] = $table[1];
		return $errdata;
	}
	if (!$fp = @fopen(BLOGN_CMTDIR.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file(BLOGN_CMTDIR.$table[1]);
	$cnt = count($oldrecord);
	// レコードから指定IDを削除
	while (list($key, $val) = each($oldrecord)) {
		list($checkid, $checkentid, , $date, ) = explode(",",$val);
		if ($id == $checkid) {
			$filedate = substr($date,0,6);
			array_splice($oldrecord, $key, 1);
			break;
		}
	}
	$cntnew = count($oldrecord);

	if ($cnt == $cntnew) {
		fclose($fp);

		// ロック解除
		if (!blogn_mod_db_file_unlock($lockkey)) {
			$error[0] = false;
			$error[1] = BLOGN_MOD_BD_MES_25;
			return $error;
		}

		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_19;
		$errdata[2] = $table[1];
		return $errdata;
	}

	$ioldrecord = implode('', $oldrecord);
	fputs($fp, $ioldrecord);
	ftruncate($fp, strlen($ioldrecord));
	fclose($fp);

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$table[1] = "cmt".$filedate.".cgi";
	$errdata = blogn_mod_db_RecordDelete($table, $id);
	return $errdata;
}


/* ----- トラックバック数ロード（一覧用） ----- */
function blogn_mod_db_trackback_count() {
	// 一覧リスト取得
	$table[0] = "trk";
	$table[1] = "trk_key.cgi";
	$trkkey = blogn_mod_db_RecordLoad($table);
	if (!$trkkey[0]) {
		$count = 0;
	}else{
		$count = count($trkkey[1]);
	}
	return $count;
}


/* ----- トラックバック数ロード（一覧用） ----- */
function blogn_mod_db_trackback_count_load($ent_id) {
	// 一覧リスト取得
	$table[0] = "trk";
	$table[1] = "trk_key.cgi";
	$trkkey = blogn_mod_db_RecordLoad($table);

	if (!$trkkey[0]) {
		$error[0] = true;
		$error[1] = 0;
		return $error;
	}
	$list = array();
	while(list($key, $val) = each($trkkey[1])) {
		list($id, $eid, $date, $name, $title, ) = explode(",", $val);
		if ($ent_id == $eid) {
			$list[$date.sprintf("%06d",$id)] = $val;
		}
	}
	$listcount[0] = true;
	$listcount[1] = count($list);
	return $listcount;
}


/* ----- トラックバックロード（一覧用） ----- */
function blogn_mod_db_trackback_load_for_list($ent_id, $start_key, $key_count) {
	// 一覧リスト取得
	$table[0] = "trk";
	$table[1] = "trk_key.cgi";
	$trkkey = blogn_mod_db_RecordLoad($table);

	if (!$trkkey[0]) {
		$error[0] = false;
		$error[1] = $trkkey[1];
		$error[2] = $trkkey[2];
		return $error;
	}
	$list = array();
	while(list($key, $val) = each($trkkey[1])) {
		list($id, $eid, $date, $name, $title, ) = explode(",", $val);
		if ($ent_id == $eid) {
			$list[$date.sprintf("%06d",$id)] = $val;
		}
	}
	krsort($list);
	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, ,$date, ) = explode(",", $val);
		$filename = "trk".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$trklist = blogn_mod_db_RecordLoad($table);
		}
		if ($trklist[0]) {
			reset($trklist[1]);
			while(list($trkkey, $trkval) = each($trklist[1])) {
				list($trkid, $entid, $logdate, $name, $title, $url, $mes, $ip, $agent,) = explode(",", $trkval);
				if ($id == $trkid) {
					$newtrk[1][$i]["id"] = $trkid;
					$newtrk[1][$i]["entry_id"] = $entid;
					$newtrk[1][$i]["date"] = $logdate;
					$newtrk[1][$i]["name"] = $name;
					$newtrk[1][$i]["title"] = $title;
					$newtrk[1][$i]["url"] = $url;
					$newtrk[1][$i]["mes"] = $mes;
					$newtrk[1][$i]["ip"] = $ip;
					$newtrk[1][$i]["agent"] = $agent;
					$i++;
					break;
				}
			}
		}
	}
	if ($i > 0) {
		$newtrk[0] = true;
		$newtrk[2] = $listtotal;
	}else{
		$newtrk[0] = false;
	}
	return $newtrk;
}


/* ----- トラックバックロード（新着一覧用） ----- */
function blogn_mod_db_trackback_load_for_new($start_key, $key_count) {
	// 一覧リスト取得
	$table[0] = "trk";
	$table[1] = "trk_key.cgi";
	$trkkey = blogn_mod_db_RecordLoad($table);

	if (!$trkkey[0]) {
		$error[0] = false;
		$error[1] = $trkkey[1];
		$error[2] = $trkkey[2];
		return $error;
	}
	$list = array();
	while(list($key, $val) = each($trkkey[1])) {
		list($id, $eid, $date, $name, $title, ) = explode(",", $val);
		$list[$date.sprintf("%06d",$id)] = $val;
	}
	krsort($list);
	$listtotal = count($list);
	// $key_count = 0 の場合は全件取得
	if ($key_count != 0) {
		$newlist = array_slice ($list, $start_key, $key_count);
	}else{
		$newlist = $list;
	}
	$i = 0;
	while(list($key, $val) = each($newlist)) {
		list($id, ,$date, ) = explode(",", $val);
		$filename = "trk".substr($date,0,6).".cgi";
		if($table[1] != $filename) {
			$table[1] = $filename;
			$trklist = blogn_mod_db_RecordLoad($table);
		}
		if ($trklist[0]) {
			reset($trklist[1]);
			while(list($trkkey, $trkval) = each($trklist[1])) {
				list($trkid, $entid, $logdate, $name, $title, $url, $mes, $ip, $agent,) = explode(",", $trkval);
				if ($id == $trkid) {
					$newtrk[1][$i]["id"] = $trkid;
					$newtrk[1][$i]["entry_id"] = $entid;
					$newtrk[1][$i]["date"] = $logdate;
					$newtrk[1][$i]["name"] = $name;
					$newtrk[1][$i]["title"] = $title;
					$newtrk[1][$i]["url"] = $url;
					$newtrk[1][$i]["mes"] = $mes;
					$newtrk[1][$i]["ip"] = $ip;
					$newtrk[1][$i]["agent"] = $agent;
					$i++;
					break;
				}
			}
		}
	}
	if ($i > 0) {
		$newtrk[0] = true;
		$newtrk[2] = $listtotal;
	}else{
		$newtrk[0] = false;
	}
	return $newtrk;
}


/* ----- トラックバック登録 ----- */
function blogn_mod_db_trackback_add($entid, $date, $blogname, $title, $url, $trackback, $ip, $agent) {

	$table[0] = "trk";
	$table[1] = "trk".substr($date,0,6).".cgi";
	$table[2] = BLOGN_MOD_DB_ID_TRACKBACK;
	$record[0] = $entid;
	$record[1] = $date;
	$record[2] = $blogname;
	$record[3] = $title;
	$record[4] = $url;
	$record[5] = blogn_mod_db_rn2br($trackback);
	$record[6] = $ip;
	$record[7] = $agent;
	$updown = 1;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	if (!$errdata[0]) return $errdata;

	$record = array();
	// 記事履歴保存
	// ファイルが存在しない場合、新規作成
	if (!$fp = @fopen(BLOGN_TRKDIR."trk_key.cgi", "r+")) {
		$oldmask = umask();
		umask(000);
		if (!$fp = @fopen(BLOGN_TRKDIR."trk_key.cgi", "w")) {
			umask($oldmask);
			$errdata[0] = false;
			$errdata[1] = BLOGN_MOD_DB_MES_02;
			$errdata[2] = $table[1];
			return $errdata;
		}
		umask($oldmask);
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file(BLOGN_TRKDIR."trk_key.cgi");

	$record[0] = $errdata[2];
	$record[1] = $entid;
	$record[2] = $date;
	$record[3] = $blogname;
	$record[4] = $title;
	// 追加データ整形
	$newrecord = "";
	while(list($key, $val) = each($record)) {
		$val = blogn_mod_db_comma_change($val);
		$newrecord .= $val.",";
	}
	$newrecord .= "\n";

	// 先頭に新規データ追加
	fputs($fp, $newrecord);
	if (count($oldrecord) != 0) fputs($fp, implode('', $oldrecord));

	fclose($fp);

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	$errdata[2] = $errdata[2];
	return $errdata;
}


/* ----- トラックバック削除 ----- */
function blogn_mod_db_trackback_delete($id) {

	$table[0] = "trk";
	$table[1] = "trk_key.cgi";

	// ファイルが存在しない場合エラー
	if (!file_exists(BLOGN_TRKDIR.$table[1])) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_03;
		$errdata[2] = $table[1];
		return $errdata;
	}
	if (!$fp = @fopen(BLOGN_TRKDIR.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}
	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldrecord = file(BLOGN_TRKDIR.$table[1]);
	$cnt = count($oldrecord);
	// レコードから指定IDを削除
	while (list($key, $val) = each($oldrecord)) {
		list($checkid, $checkentid, $date, $title, ) = explode(",",$val);
		if ($id == $checkid) {
			$filedate = substr($date,0,6);
			array_splice($oldrecord, $key, 1);
			break;
		}
	}
	$cntnew = count($oldrecord);

	if ($cnt == $cntnew) {
		fclose($fp);

		// ロック解除
		if (!blogn_mod_db_file_unlock($lockkey)) {
			$error[0] = false;
			$error[1] = BLOGN_MOD_BD_MES_25;
			return $error;
		}

		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_19;
		$errdata[2] = $table[1];
		return $errdata;
	}

	$ioldrecord = implode('', $oldrecord);
	fputs($fp, $ioldrecord);
	ftruncate($fp, strlen($ioldrecord));
	fclose($fp);

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$table[1] = "trk".$filedate.".cgi";

	$errdata = blogn_mod_db_RecordDelete($table, $id);
	return $errdata;
}


/* ----- ファイルリストロード ----- */
function blogn_mod_db_file_load($admin, $uid, $start_key, $end_key) {

	// ファイルリスト取得
	$table[0] = "ini";
	$table[1] = "filelist.cgi";
	$filelist = blogn_mod_db_RecordLoad($table);

	if (!$filelist[0]) {
		$error[0] = false;
		$error[1] = $filelist[1];
		$error[2] = $filelist[2];
		return $error;
	}

	// ファイルリスト取得
	$i == 0;
	$j == 0;
	while(list($key, $val) = each($filelist[1])) {
		list($id, $user_id, $file_name, $comment, ) = explode(",", $val);
		if ($admin || $uid == $user_id) {
			if ($start_key <= $i && $end_key > $i) {
				$list[1][$id]["user_id"] = $user_id;
				$list[1][$id]["file_name"] = blogn_mod_db_comma_restore($file_name);
				$list[1][$id]["comment"] = blogn_mod_db_comma_restore($comment);
				$j++;
			}
			$i++;
		}
	}
	if ($i == 0) {
		$list[0] = false;
		$list[2] = 0;
	}elseif ($j == 0) {
		$list[0] = false;
		$list[2] = 0;
	}else{
		$list[0] = true;
		$list[2] = $i;
	}
	return $list;
}


/* ----- ファイルリスト登録 ----- */
function blogn_mod_db_file_add($user_id, $file_name, $comment) {

	$table[0] = "ini";
	$table[1] = "filelist.cgi";
	$table[2] = BLOGN_MOD_DB_ID_FILES;
	$record[0] = $user_id;
	$record[1] = $file_name;
	$record[2] = $comment;
	$updown = 1;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	return $errdata;
}


/* ----- ファイルリスト更新 ----- */
function blogn_mod_db_file_list_edit($req_id, $comment) {

	// ファイルリスト取得
	$table[0] = "ini";
	$table[1] = "filelist.cgi";
	$filelist = blogn_mod_db_RecordLoad($table);

	if (!$filelist[0]) {
		$error[0] = false;
		$error[1] = $filelist[1];
		$error[2] = $filelist[2];
		return $error;
	}

	while(list($key, $val) = each($filelist[1])) {
		list($id, $user_id, $file_name,) = explode(",", $val);
		if ($req_id == $id) {
			$record[0] = $user_id;
			$record[1] = $file_name;
			$record[2] = $comment;
			$errdata = blogn_mod_db_RecordChange($table, $req_id, $record);
			return $errdata;
		}
	}
	$error[0] = false;
	$error[1] = BLOGN_MOD_DB_MES_18;
	return $error;
}


/* ----- ファイルリスト削除 ----- */
function blogn_mod_db_file_list_delete($req_id) {
	// ファイルリスト取得
	$table[0] = "ini";
	$table[1] = "filelist.cgi";
	$filelist = blogn_mod_db_RecordLoad($table);

	if (!$filelist[0]) {
		$error[0] = false;
		$error[1] = $filelist[1];
		$error[2] = $filelist[2];
		return $error;
	}

	while(list($key, $val) = each($filelist[1])) {
		list($id, $user_id, $file_name, $comment, ) = explode(",", $val);
		if ($req_id == $id) {
			$errdata = blogn_mod_db_RecordDelete($table, $id);
			if (!@unlink(BLOGN_FILEDIR.$file_name)) {
				$errdata[0] = false;
				$errdata[1] = BLOGN_MOD_DB_MES_17;
				return $errdata;
			}
			return $errdata;
		}
	}
}


/* ----- カテゴリ１リストロード ----- */
function blogn_mod_db_category1_load() {
	// カテゴリ１リスト取得
	$table[0] = "ini";
	$table[1] = "category1.cgi";
	$category1 = blogn_mod_db_RecordLoad($table);

	if (!$category1[0]) {
		$error[0] = false;
		$error[1] = $category1[1];
		$error[2] = $category1[2];
		return $error;
	}

	// カテゴリ１リスト取得
	$list[0] = false;
	while(list($key, $val) = each($category1[1])) {
		list($id, $category_name, $view_mode, ) = explode(",", $val);
		$list[0] = true;
		$list[1][$id]["name"] = blogn_mod_db_comma_restore($category_name);
		$list[1][$id]["view"] = $view_mode;
	}
	return $list;
}


/* ----- カテゴリ２リストロード ----- */
function blogn_mod_db_category2_load() {
	// カテゴリ２リスト
	$table[0] = "ini";
	$table[1] = "category2.cgi";
	$category2 = blogn_mod_db_RecordLoad($table);

	if (!$category2[0]) {
		$error[0] = false;
		$error[1] = $category2[1];
		$error[2] = $category2[2];
		return $error;
	}


		$list[0] = false;
	// リンクリスト取得
	while(list($key, $val) = each($category2[1])) {
		list($id, $cid, $category_name, $view_mode, ) = explode(",", $val);
		$list[0] = true;
		$list[1][$id]["id"] = $cid;
		$list[1][$id]["name"] = blogn_mod_db_comma_restore($category_name);
		$list[1][$id]["view"] = $view_mode;
	}
	return $list;
}


/* ----- カテゴリ１リスト登録 ----- */
function blogn_mod_db_category1_add($category_name) {

	$table[0] = "ini";
	$table[1] = "category1.cgi";
	$table[2] = BLOGN_MOD_DB_ID_CATEGORY1;
	$record[0] = $category_name;
	$recoed[1] = "1";
	$updown = 0;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	return $errdata;
}


/* ----- カテゴリ２リスト登録 ----- */
function blogn_mod_db_category2_add($id, $category_name) {

	$table[0] = "ini";
	$table[1] = "category2.cgi";
	$table[2] = BLOGN_MOD_DB_ID_CATEGORY2;
	$record[0] = $id;
	$record[1] = $category_name;
	$record[2] = "1";
	$updown = 0;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	return $errdata;
}


/* ----- カテゴリ１リスト更新 ----- */
function blogn_mod_db_category1_edit($id, $category_name, $view_mode) {

	$table[0] = "ini";
	$table[1] = "category1.cgi";
	$record[0] = $category_name;
	$record[1] = $view_mode;
	$errdata = blogn_mod_db_RecordChange($table, $id, $record);

	if (!$view_mode) {
		// カテゴリ２も全て表示／非表示連動
		$c2 = blogn_mod_db_category2_load();
		if ($c2[0]) {
			while(list($key, $val) = each($c2[1])) {
				if ($id == $val["id"]) {
					$table[0] = "ini";
					$table[1] = "category2.cgi";
					$record[0] = $val["id"];
					$record[1] = $val["name"];
					$record[2] = $view_mode;
					$errdata = blogn_mod_db_RecordChange($table, $key, $record);
				}
			}
		}
	}

	return $errdata;
}


/* ----- カテゴリ２リスト更新 ----- */
function blogn_mod_db_category2_edit($id, $cid, $category_name, $view_mode) {

	$table[0] = "ini";
	$table[1] = "category2.cgi";
	$record[0] = $cid;
	$record[1] = $category_name;
	$record[2] = $view_mode;

	$errdata = blogn_mod_db_RecordChange($table, $id, $record);

	// カテゴリ２を表示にした場合、カテゴリ１も連動
	if ($view_mode) {
		$c1 = blogn_mod_db_category1_load();
		while(list($key, $val) = each($c1[1])) {
			if ($cid == $key) {
				$table[0] = "ini";
				$table[1] = "category1.cgi";
				$record[0] = $val["name"];
				$record[1] = $view_mode;
				$errdata = blogn_mod_db_RecordChange($table, $cid, $record);
				break;
			}
		}
	}
	return $errdata;
}


/* ----- カテゴリ１削除 ----- */
function blogn_mod_db_category1_delete($id) {

	$table[0] = "ini";
	$table[1] = "category1.cgi";
	$errdata = blogn_mod_db_RecordDelete($table, $id);

	// 対応するカテゴリ２も全て削除
	$category2 = blogn_mod_db_category2_load();
	if (!$category2[0]) return $errdata;
	reset($category2[1]);
	while (list($key, $val) = each($category2[1])) {
		if ($val["id"] == $id) {
			$error = blogn_mod_db_category2_delete($key);
			if (!$error[0]) return $error;
		}
	}
	return $errdata;

}


/* ----- カテゴリ２削除 ----- */
function blogn_mod_db_category2_delete($id) {

	$table[0] = "ini";
	$table[1] = "category2.cgi";
	$errdata = blogn_mod_db_RecordDelete($table, $id);
	return $errdata;

}


/* ----- カテゴリ１順番切り替え ----- */
function blogn_mod_db_category1_change($id, $updown) {

	// リンクグループ取得
	$table[1] = "category1.cgi";

	if (!file_exists(BLOGN_INIDIR.$table[1])) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_DB_MES_03;
		$error[2] = $table[1];
		return $error;
	}
	if (!$fp = @fopen(BLOGN_INIDIR.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$category1 = file(BLOGN_INIDIR.$table[1]);

	if ($updown == "up") {
		// 順位を上にする場合は降順ソートする
		$category1 = array_reverse($category1);
	}
	// カテゴリ１取得
	while(list($key, $val) = each($category1)) {
		list($category_id,) = explode(",", $val);
		if ($id == $category_id) {
			$from = $category1[$key];
			$to = $category1[$key + 1];
			$category1[$key] = $to;
			$category1[$key + 1] = $from;
			if ($updown == "up") {
				// ソートを戻す
				$category1 = array_reverse($category1);
			}

			fputs($fp, implode('', $category1));
			fclose($fp);

		// ロック解除
		if (!blogn_mod_db_file_unlock($lockkey)) {
			$error[0] = false;
			$error[1] = BLOGN_MOD_BD_MES_25;
			return $error;
		}

			$error[0] = true;
			$error[1] = BLOGN_MOD_DB_MES_16;
			return $error;
		}
	}
	$error[0] = false;
	$error[1] = BLOGN_MOD_DB_MES_15;
	$error[2] = $id;
	return $error;

}


/* ----- カテゴリ２順番切り替え ----- */
function blogn_mod_db_category2_change($c1_id, $id, $updown) {
	// リンクグループ取得
	$table[1] = "category2.cgi";

	if (!file_exists(BLOGN_INIDIR.$table[1])) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_DB_MES_03;
		$error[2] = $table[1];
		return $error;
	}
	if (!$fp = @fopen(BLOGN_INIDIR.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$category2 = file(BLOGN_INIDIR.$table[1]);

	$find_flg = false;
	if ($updown == "down") {
		// リンクグループ取得
		for ($key = 0; $key < count($category2); $key++) {
			list($c2id, $c1id,) = explode(",", $category2[$key]);
			if ($id == $c2id) {
				$find_flg = true;
				$fromkey = $key;
				$from = $category2[$key];
			}elseif ($find_flg && $c1_id == $c1id) {
				$category2[$fromkey] = $category2[$key];
				$category2[$key] = $from;

				fputs($fp, implode('', $category2));
				fclose($fp);

				// ロック解除
				if (!blogn_mod_db_file_unlock($lockkey)) {
					$error[0] = false;
					$error[1] = BLOGN_MOD_BD_MES_25;
					return $error;
				}

				$error[0] = true;
				$error[1] = BLOGN_MOD_DB_MES_16;
				return $error;
			}
		}
	}else{
		for ($key = count($category2) - 1; $key >= 0; $key--) {
			list($c2id, $c1id,) = explode(",", $category2[$key]);
			if ($id == $c2id) {
				$find_flg = true;
				$fromkey = $key;
				$from = $category2[$key];
			}elseif ($find_flg && $c1_id == $c1id) {
				$category2[$fromkey] = $category2[$key];
				$category2[$key] = $from;

				fputs($fp, implode('', $category2));
				fclose($fp);

				// ロック解除
				if (!blogn_mod_db_file_unlock($lockkey)) {
					$error[0] = false;
					$error[1] = BLOGN_MOD_BD_MES_25;
					return $error;
				}

				$error[0] = true;
				$error[1] = BLOGN_MOD_DB_MES_16;
				return $error;
			}
		}
	}
	$error[0] = false;
	$error[1] = BLOGN_MOD_DB_MES_15;
	$error[2] = $id;
	return $error;
}


/* ----- リンクグループロード ----- */
function blogn_mod_db_link_group_load() {
	// リンクグループ取得
	$table[0] = "ini";
	$table[1] = "linkgroup.cgi";
	$linkgroup = blogn_mod_db_RecordLoad($table);

	if (!$linkgroup[0]) {
		$error[0] = false;
		$error[1] = $linkgroup[1];
		$error[2] = $linkgroup[2];
		return $error;
	}

	// リンクグループ取得
	$list[0] = false;
	while(list($key, $val) = each($linkgroup[1])) {
		list($id, $group_name,) = explode(",", $val);
		$list[0] = true;
		$list[1][$id]["name"] = blogn_mod_db_comma_restore($group_name);
	}
	return $list;
}


/* ----- リンクリストロード ----- */
function blogn_mod_db_link_load() {
	// リンクリスト取得
	$table[0] = "ini";
	$table[1] = "linklist.cgi";
	$linklist = blogn_mod_db_RecordLoad($table);

	if (!$linklist[0]) {
		$error[0] = false;
		$error[1] = $linklist[1];
		$error[2] = $linklist[2];
		return $error;
	}


	// リンクリスト取得
	$list[0] = false;
	while(list($key, $val) = each($linklist[1])) {
		list($id, $group_id, $link_name, $link_url,) = explode(",", $val);
		$list[0] = true;
		$list[1][$id]["group"] = $group_id;
		$list[1][$id]["name"] = blogn_mod_db_comma_restore($link_name);
		$list[1][$id]["url"] = blogn_mod_db_comma_restore($link_url);
	}
	return $list;
}


/* ----- リンクグループ登録 ----- */
function blogn_mod_db_link_group_add($group_name) {

	$table[0] = "ini";
	$table[1] = "linkgroup.cgi";
	$table[2] = BLOGN_MOD_DB_ID_LINKGROUP;
	$record[0] = $group_name;
	$updown = 0;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	return $errdata;
}


/* ----- リンクリスト登録 ----- */
function blogn_mod_db_link_list_add($group_id, $link_name, $link_url) {

	$table[0] = "ini";
	$table[1] = "linklist.cgi";
	$table[2] = BLOGN_MOD_DB_ID_LINKLIST;
	$record[0] = $group_id;
	$record[1] = $link_name;
	$record[2] = $link_url;
	$updown = 0;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	return $errdata;
}


/* ----- リンクグループ更新 ----- */
function blogn_mod_db_link_group_edit($id, $group_name) {

	$table[0] = "ini";
	$table[1] = "linkgroup.cgi";
	$record[0] = $group_name;

	$errdata = blogn_mod_db_RecordChange($table, $id, $record);
	return $errdata;
}


/* ----- リンクリスト更新 ----- */
function blogn_mod_db_link_list_edit($id, $group_id, $link_name, $link_url) {

	$table[0] = "ini";
	$table[1] = "linklist.cgi";
	$record[0] = $group_id;
	$record[1] = $link_name;
	$record[2] = $link_url;

	$errdata = blogn_mod_db_RecordChange($table, $id, $record);
	return $errdata;
}


/* ----- リンクグループ削除 ----- */
function blogn_mod_db_link_group_delete($id) {

	$table[0] = "ini";
	$table[1] = "linkgroup.cgi";
	$errdata = blogn_mod_db_RecordDelete($table, $id);

	// 対応するリンクリストも全て削除
	$linklist = blogn_mod_db_link_load();
	if (!$linklist[0]) return $errdata;
	reset($linklist[1]);
	while (list($key, $val) = each($linklist[1])) {
		if ($val["group"] == $id) {
			$error = blogn_mod_db_link_list_delete($key);
			if (!$error[0]) return $error;
		}
	}
	return $errdata;
}


/* ----- リンクリスト削除 ----- */
function blogn_mod_db_link_list_delete($id) {

	$table[0] = "ini";
	$table[1] = "linklist.cgi";
	$errdata = blogn_mod_db_RecordDelete($table, $id);
	return $errdata;

}


/* ----- リンクグループ順番切り替え ----- */
function blogn_mod_db_link_group_change($id, $updown) {

	// リンクグループ取得
	$table[1] = "linkgroup.cgi";

	if (!file_exists(BLOGN_INIDIR.$table[1])) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_DB_MES_03;
		$error[2] = $table[1];
		return $error;
	}
	if (!$fp = @fopen(BLOGN_INIDIR.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$linkgroup = file(BLOGN_INIDIR.$table[1]);

	if ($updown == "up") {
		// 順位を上にする場合は降順ソートする
		$linkgroup = array_reverse($linkgroup);
	}
	// リンクグループ取得
	while(list($key, $val) = each($linkgroup)) {
		list($group_id,) = explode(",", $val);
		if ($id == $group_id) {
			$from = $linkgroup[$key];
			$to = $linkgroup[$key + 1];
			$linkgroup[$key] = $to;
			$linkgroup[$key + 1] = $from;

			if ($updown == "up") {
				// ソートを戻す
				$linkgroup = array_reverse($linkgroup);
			}

			fputs($fp, implode('', $linkgroup));
			fclose($fp);

			// ロック解除
			if (!blogn_mod_db_file_unlock($lockkey)) {
				$error[0] = false;
				$error[1] = BLOGN_MOD_BD_MES_25;
				return $error;
			}

			$error[0] = true;
			$error[1] = BLOGN_MOD_DB_MES_16;
			return $error;
		}
	}
	$error[0] = false;
	$error[1] = BLOGN_MOD_DB_MES_15;
	$error[2] = $id;
	return $error;

}


/* ----- リンクリスト順番切り替え ----- */
function blogn_mod_db_link_list_change($group_id, $id, $updown) {
	// リンクグループ取得
	$table[1] = "linklist.cgi";

	if (!file_exists(BLOGN_INIDIR.$table[1])) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_DB_MES_03;
		$error[2] = $table[1];
		return $error;
	}
	if (!$fp = @fopen(BLOGN_INIDIR.$table[1], "r+")) {
		$errdata[0] = false;
		$errdata[1] = BLOGN_MOD_DB_MES_02;
		$errdata[2] = $table[1];
		return $errdata;
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$linklist = file(BLOGN_INIDIR.$table[1]);

	$find_flg = false;
	if ($updown == "down") {
		// リンクグループ取得
		for ($key = 0; $key < count($linklist); $key++) {
			list($list_id, $list_group_id, $group_name,) = explode(",", $linklist[$key]);
			if ($id == $list_id) {
				$find_flg = true;
				$fromkey = $key;
				$from = $linklist[$key];
			}elseif ($find_flg && $group_id == $list_group_id) {
				$linklist[$fromkey] = $linklist[$key];
				$linklist[$key] = $from;

				fputs($fp, implode('', $linklist));
				fclose($fp);

				// ロック解除
				if (!blogn_mod_db_file_unlock($lockkey)) {
					$error[0] = false;
					$error[1] = BLOGN_MOD_BD_MES_25;
					return $error;
				}

				$error[0] = true;
				$error[1] = BLOGN_MOD_DB_MES_16;
				return $error;
			}
		}
	}else{
		for ($key = count($linklist) - 1; $key >= 0; $key--) {
			list($list_id, $list_group_id, $group_name,) = explode(",", $linklist[$key]);
			if ($id == $list_id) {
				$find_flg = true;
				$fromkey = $key;
				$from = $linklist[$key];
			}elseif ($find_flg && $group_id == $list_group_id) {
				$linklist[$fromkey] = $linklist[$key];
				$linklist[$key] = $from;

				fputs($fp, implode('', $linklist));
				fclose($fp);

				// ロック解除
				if (!blogn_mod_db_file_unlock($lockkey)) {
					$error[0] = false;
					$error[1] = BLOGN_MOD_BD_MES_25;
					return $error;
				}

				$error[0] = true;
				$error[1] = BLOGN_MOD_DB_MES_16;
				return $error;
			}
		}
	}
	$error[0] = false;
	$error[1] = BLOGN_MOD_DB_MES_15;
	$error[2] = $id;
	return $error;
}


/* ----- PINGリストロード ----- */
function blogn_mod_db_ping_load() {
	// PINGリスト取得
	$table[0] = "ini";
	$table[1] = "pinglist.cgi";
	$pinglist = blogn_mod_db_RecordLoad($table);

	if (!$pinglist[0]) {
		$error[0] = false;
		$error[1] = $pinglist[1];
		$error[2] = $pinglist[2];
		return $error;
	}

	// PINGリスト取得
	$list[0] = false;
	while(list($key, $val) = each($pinglist[1])) {
		list($id, $ping_name, $ping_url, $ping_default, ) = explode(",", $val);
		$list[0] = true;
		$list[1][$id]["name"] = blogn_mod_db_comma_restore($ping_name);
		$list[1][$id]["url"] = blogn_mod_db_comma_restore($ping_url);
		$list[1][$id]["default"] = $ping_default;
	}
	return $list;
}


/* ----- PING送信先登録 ----- */
function blogn_mod_db_ping_add($ping_name, $ping_url, $ping_default) {

	$table[0] = "ini";
	$table[1] = "pinglist.cgi";
	$table[2] = BLOGN_MOD_DB_ID_PING;
	$record[0] = blogn_mod_db_comma_change($ping_name);
	$record[1] = blogn_mod_db_comma_change($ping_url);
	$record[2] = $ping_default;
	$updown = 0;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	return $errdata;
}


/* ----- PING送信先更新 ----- */
function blogn_mod_db_ping_edit($id, $ping_name, $ping_url, $ping_default) {

	$table[0] = "ini";
	$table[1] = "pinglist.cgi";
	$record[0] = $ping_name;
	$record[1] = $ping_url;
	$record[2] = $ping_default;

	$errdata = blogn_mod_db_RecordChange($table, $id, $record);
	return $errdata;

}


/* ----- PING送信先削除 ----- */
function blogn_mod_db_ping_delete($id) {

	$table[0] = "ini";
	$table[1] = "pinglist.cgi";
	$errdata = blogn_mod_db_RecordDelete($table, $id);
	return $errdata;

}


/* ----- ユーザーログインチェック ----- */
function blogn_mod_db_user_check($loginid, $loginpw) {
	// $id: ユーザーID
	// $pw: ユーザーパスワード

	$table[0] = "ini";
	$table[1] = "userlist.cgi";
	$userlist = blogn_mod_db_RecordLoad($table);

	if (!$userlist[0]) {
		$error[0] = false;
		$error[1] = $userlist[1];
		$error[2] = $userlist[2];
		return $error;
	}
	// ユーザーチェック
	while(list($key, $val) = each($userlist[1])) {
		list($id, $admin, $user_id, $user_pw, , $user_active,) = explode(",", $val);
		if ($user_id == $loginid && $user_pw == md5($loginpw) && $user_active) {
			$error[0] = true;
			$error[1] = BLOGN_MOD_DB_MES_09;
			$error[2] = $admin ? true : false;
			$error[3] = $user_active;
			return $error;
		}
	}
		$error[0] = false;
		$error[1] = BLOGN_MOD_DB_MES_10;
		return $error;
}


/* ----- ユーザーリストロード ----- */
function blogn_mod_db_user_load() {

	$table[0] = "ini";
	$table[1] = "userlist.cgi";
	$userlist = blogn_mod_db_RecordLoad($table);

	if (!$userlist[0]) {
		$error[0] = false;
		$error[1] = $userlist[1];
		$error[2] = $userlist[2];
		return $error;
	}

	// ユーザーリスト取得
	while(list($key, $val) = each($userlist[1])) {
		list($id, $admin, $user_id, $user_pw, $user_name, $user_active, $user_profile, $init_comment_ok, $init_trackback_ok, $init_category, $init_icon_ok, $receive_mail_address, $receive_mail_pop3, $receive_mail_user_id, $receive_mail_user_pw, $receive_mail_apop, $access_time, $send_mail_address, $mobile_category, $mobile_comment_ok, $mobile_trackback_ok, $information_mail_address, $information_comment, $information_trackback, $user_mail_address, $br_change, ) = explode(",", $val);
		$ulist[$id]["admin"] = $admin;
		$ulist[$id]["id"] = $user_id;
		$ulist[$id]["pw"] = $user_pw;
		$ulist[$id]["name"] = blogn_mod_db_comma_restore($user_name);
		$ulist[$id]["active"] = $user_active;
		$ulist[$id]["profile"] = $user_profile;
		$ulist[$id]["init_comment_ok"] = $init_comment_ok;
		$ulist[$id]["init_trackback_ok"] = $init_trackback_ok;
		$ulist[$id]["init_category"] = $init_category;
		$ulist[$id]["init_icon_ok"] = $init_icon_ok;
		$ulist[$id]["receive_mail_address"] = $receive_mail_address;
		$ulist[$id]["receive_mail_pop3"] = $receive_mail_pop3;
		$ulist[$id]["receive_mail_user_id"] = $receive_mail_user_id;
		$ulist[$id]["receive_mail_user_pw"] = $receive_mail_user_pw;
		$ulist[$id]["receive_mail_apop"] = $receive_mail_apop;
		$ulist[$id]["access_time"] = $access_time;
		$ulist[$id]["send_mail_address"] = $send_mail_address;
		$ulist[$id]["mobile_category"] = $mobile_category;
		$ulist[$id]["mobile_comment_ok"] = $mobile_comment_ok;
		$ulist[$id]["mobile_trackback_ok"] = $mobile_trackback_ok;
		$ulist[$id]["information_mail_address"] = $information_mail_address;
		$ulist[$id]["information_comment"] = $information_comment;
		$ulist[$id]["information_trackback"] = $information_trackback;
		$ulist[$id]["user_mail_address"] = $user_mail_address;
		$ulist[$id]["br_change"] = $br_change;
	}
	return $ulist;
}


/* ----- 新規ユーザー登録 ----- */
function blogn_mod_db_user_add($loginid, $loginpw, $name, $mailaddress, $useradmin, $useractive) {
	// $id: 新規ユーザーID
	// $pw: 新規ユーザーパスワード

	$table[0] = "ini";
	$table[1] = "userlist.cgi";
	$userlist = blogn_mod_db_RecordLoad($table);

	if ($userlist[0]) {
		// ユーザーチェック
		while(list($key, $val) = each($userlist[1])) {
			list($id, $admin, $user_id,) = explode(",", $val);
			if ($user_id == $loginid) {
				$error[0] = false;
				$error[1] = BLOGN_MOD_DB_MES_06;
				$error[2] = $admin ? true : false;
				return $error;
			}
		}
	}

	$table[0] = "ini";
	$table[1] = "userlist.cgi";
	$table[2] = BLOGN_MOD_DB_ID_USER;
	$record[0] = $useradmin;
	$record[1] = $loginid;
	$record[2] = md5($loginpw);
	$record[3] = $name;
	$record[4] = $useractive;
	$record[5] = "";
	$record[6] = $record[7] = 1;
	$record[8] = "";
	$record[9] = 1;
	$record[10] = $record[11] = $record[12] = $record[13] = "";
	$record[14] = 0;
	$record[15] = 60;
	$record[16] = $record[17] = "";
	$record[18] = $record[19] = 0;
	$record[20] = $record[21] = $record[22] = "";
	$record[23] = $mailaddress;
	$record[24] = 1;
	$updown = 0;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	return $errdata;

}


/* ----- ユーザー状態変更 ----- */
function blogn_mod_db_user_active($req_id, $active_mode) {

	$table[0] = "ini";
	$table[1] = "userlist.cgi";
	$userlist = blogn_mod_db_RecordLoad($table);

	if (!$userlist[0]) {
		$error[0] = false;
		$error[1] = $userlist[1];
		$error[2] = $userlist[2];
		return $error;
	}
	// ユーザーチェック
	while(list($key, $val) = each($userlist[1])) {
		// データ取得
		list($id, $admin, $user_id, $user_pw, $user_name, $user_active, $user_profile, $init_comment_ok, $init_trackback_ok, $init_category, $init_icon_ok, $receive_mail_address, $receive_mail_pop3, $receive_mail_user_id, $receive_mail_user_pw, $receive_mail_apop, $access_time, $send_mail_address, $mobile_category, $mobile_comment_ok, $mobile_trackback_ok, $information_mail_address, $information_comment, $information_trackback, $user_mail_address, $br_change, ) = explode(",", $val);
		if ($id == $req_id) {
			if ($admin) {
				$errdata[0] = false;
				$errdata[1] = BLOGN_MOD_DB_MES_11;
				$errdata[2] = $user_id;
				return $errdata;
			}else{
				$table[0] = "ini";
				$table[1] = "userlist.cgi";
				$record[0] = $admin;
				$record[1] = $user_id;
				$record[2] = $user_pw;
				$record[3] = $user_name;
				$record[4] = $active_mode;
				$record[5] = $user_profile;
				$record[6] = $init_comment_ok;
				$record[7] = $init_trackback_ok;
				$record[8] = $init_category;
				$record[9] = $init_icon_ok;
				$record[10] = $receive_mail_address;
				$record[11] = $receive_mail_pop3;
				$record[12] = $receive_mail_user_id;
				$record[13] = $receive_mail_user_pw;
				$record[14] = $receive_mail_apop;
				$record[15] = $access_time;
				$record[16] = $send_mail_address;
				$record[17] = $mobile_category;
				$record[18] = $mobile_comment_ok;
				$record[19] = $mobile_trackback_ok;
				$record[20] = $information_mail_address;
				$record[21] = $information_comment;
				$record[22] = $information_trackback;
				$record[23] = $user_mail_address;
				$record[24] = $br_change;

				$errdata = blogn_mod_db_RecordChange($table, $req_id, $record);
				if ($errdata[0]) $errdata[2] = $user_id;
				return $errdata;

			}
		}
	}

	$errdata[0] = false;
	$errdata[1] = BLOGN_MOD_DB_MES_08;
	$errdata[2] = $table[1];
	return $errdata;
}


/* ----- ユーザープロフィール取得 ----- */
function blogn_mod_db_user_profile_load($req_id) {
	$table[0] = "ini";
	$table[1] = "userlist.cgi";
	$userlist = blogn_mod_db_RecordLoad($table);

	if (!$userlist[0]) {
		$error[0] = false;
		$error[1] = $userlist[1];
		$error[2] = $userlist[2];
		return $error;
	}
	// ユーザーチェック
	while(list($key, $val) = each($userlist[1])) {
		list($id, $admin, $user_id, $user_pw, $user_name, $user_active, $user_profile, $init_comment_ok, $init_trackback_ok, $init_category, $init_icon_ok, $receive_mail_address, $receive_mail_pop3, $receive_mail_user_id, $receive_mail_user_pw, $receive_mail_apop, $access_time, $send_mail_address, $mobile_category, $mobile_comment_ok, $mobile_trackback_ok, $information_mail_address, $information_comment, $information_trackback, $user_mail_address, $br_change, ) = explode(",", $val);
		if ($req_id == $id) {
			$ulist["id"] = $user_id;
			$ulist["admin"] = $admin;
			$ulist["name"] = $user_name;
			$ulist["profile"] = $user_profile;
			$ulist["init_comment_ok"] = $init_comment_ok;
			$ulist["init_trackback_ok"] = $init_trackback_ok;
			$ulist["init_category"] = $init_category;
			$ulist["init_icon_ok"] = $init_icon_ok;
			$ulist["receive_mail_address"] = $receive_mail_address;
			$ulist["receive_mail_pop3"] = $receive_mail_pop3;
			$ulist["receive_mail_user_id"] = $receive_mail_user_id;
			$ulist["receive_mail_user_pw"] = $receive_mail_user_pw;
			$ulist["receive_mail_apop"] = $receive_mail_apop;
			$ulist["access_time"] = $access_time;
			$ulist["send_mail_address"] = $send_mail_address;
			$ulist["mobile_category"] = $mobile_category;
			$ulist["mobile_comment_ok"] = $mobile_comment_ok;
			$ulist["mobile_trackback_ok"] = $mobile_trackback_ok;
			$ulist["information_mail_address"] = $information_mail_address;
			$ulist["information_comment"] = $information_comment;
			$ulist["information_trackback"] = $information_trackback;
			$ulist["user_mail_address"] = $user_mail_address;
			$ulist["br_change"] = $br_change;
			return $ulist;
		}
	}
	$errdata[0] = false;
	$errdata[1] = BLOGN_MOD_DB_MES_08;
	$errdata[2] = "userlist.php";
	return $errdata;
}


/* ----- ユーザー情報更新 ----- */
function blogn_mod_db_user_profile_update($req_id, $user_id, $user_pw, $user_name, $user_profile, $init_comment_ok, $init_trackback_ok, $init_category, $init_icon_ok, $receive_mail_address, $receive_mail_pop3, $receive_mail_user_id, $receive_mail_user_pw, $receive_mail_apop, $access_time, $send_mail_address, $mobile_category, $mobile_comment_ok, $mobile_trackback_ok, $information_mail_address, $information_comment, $information_trackback, $user_mail_address, $br_change) {
	$table[0] = "ini";
	$table[1] = "userlist.cgi";
	$userlist = blogn_mod_db_RecordLoad($table);

	if (!$userlist[0]) {
		$error[0] = false;
		$error[1] = $userlist[1];
		$error[2] = $userlist[2];
		return $error;
	}
	// ユーザーチェック
	while(list($key, $val) = each($userlist[1])) {
		list($id, $admin,,$pw,,$user_active,) = explode(",", $val);
		if ($req_id == $id) {
			$req_admin = $admin;
			$req_user_active = $user_active;

			$table[0] = "ini";
			$table[1] = "userlist.cgi";
			$record[0] = $req_admin;
			$record[1] = $user_id;
			if ($user_pw) {
				$record[2] = md5($user_pw);
			}else{
				$record[2] = $pw;
			}
			$record[3] = $user_name;
			$record[4] = $user_active;
			$record[5] = $user_profile;
			$record[6] = $init_comment_ok;
			$record[7] = $init_trackback_ok;
			$record[8] = $init_category;
			$record[9] = $init_icon_ok;
			$record[10] = $receive_mail_address;
			$record[11] = $receive_mail_pop3;
			$record[12] = $receive_mail_user_id;
			$record[13] = $receive_mail_user_pw;
			$record[14] = $receive_mail_apop;
			$record[15] = $access_time;
			$record[16] = $send_mail_address;
			$record[17] = $mobile_category;
			$record[18] = $mobile_comment_ok;
			$record[19] = $mobile_trackback_ok;
			$record[20] = $information_mail_address;
			$record[21] = $information_comment;
			$record[22] = $information_trackback;
			$record[23] = $user_mail_address;
			$record[24] = $br_change;

			$errdata = blogn_mod_db_RecordChange($table, $req_id, $record);
			return $errdata;
		}
	}

}


/* ----- ユーザー削除 ----- */
function blogn_mod_db_user_delete($ent_id) {
	$userlist = blogn_mod_db_user_load();
	while(list($key, $val) = each($userlist)) {
		if ($ent_id == $key && $val["admin"] != 0) {
			$error[0] = false;
			$error[1] = BLOGN_MOD_DB_MES_21;
			return $error;
		}
	}

	$table[0] = "ini";
	$table[1] = "userlist.cgi";
	$errdata = blogn_mod_db_RecordDelete($table, $ent_id);

	if (!$errdata[0]) return $error;

	$table[0] = "log";
	$table[1] = "log_key.cgi";
	$logkey = blogn_mod_db_RecordLoad($table);

	if (!$logkey[0]) {
		$error[0] = true;
		$error[1] = BLOGN_MOD_DB_MES_20;
		return $error;
	}
	while(list($key, $val) = each($logkey[1])) {
		list($id, $date,,,$user_id,, ) = explode(",", $val);
		if ($ent_id == $user_id) {
			blogn_mod_db_log_delete($id);
			blogn_mod_db_log_comment_delete($id);
			blogn_mod_db_log_trackback_delete($id);
		}
	}
	$error[0] = true;
	$error[1] = BLOGN_MOD_DB_MES_20;
	return $error;
}


/* ----- 初期データロード ----- */
function blogn_mod_db_init_load() {
	$table[0] = "ini";
	$table[1] = "init.cgi";
	$initlist = blogn_mod_db_RecordLoad($table);

	if (!$initlist[0]) {
		$error[0] = false;
		$error[1] = $initlist[1];
		$error[2] = $initlist[2];
		return $error;
	}

	// 初期データ取得
	while(list($key, $val) = each($initlist[1])) {
		list($id, $sitename, $sitedesc, $timezone, $charset, $max_filesize, $permit_file_type, $max_view_width, $max_view_height, $permit_html_tag, $comment_size, $trackback_slash_type, $log_view_count, $mobile_view_count, $new_entry_view_count, $archive_view_count, $comment_view_count, $trackback_view_count, $comment_list_topview_on, $trackback_list_topview_on, $session_time, $cookie_time, $limit_comment, $limit_trackback, $monthly_view_mode, $category_view_mode, ) = explode(",", $val);
		$list["id"] = $id;
		$list["sitename"] = blogn_mod_db_comma_restore($sitename);
		$list["sitedesc"] = blogn_mod_db_comma_restore($sitedesc);
		$list["timezone"] = $timezone;
		$list["charset"] = $charset;
		$list["max_filesize"] = $max_filesize;
		$list["permit_file_type"] = blogn_mod_db_comma_restore($permit_file_type);
		$list["max_view_width"] = $max_view_width;
		$list["max_view_height"] = $max_view_height;
		$list["permit_html_tag"] = blogn_mod_db_comma_restore($permit_html_tag);
		$list["comment_size"] = $comment_size;
		$list["trackback_slash_type"] = $trackback_slash_type;
		$list["log_view_count"] = $log_view_count;
		$list["mobile_view_count"] = $mobile_view_count;
		$list["new_entry_view_count"] = $new_entry_view_count;
		$list["archive_view_count"] = $archive_view_count;
		$list["comment_view_count"] = $comment_view_count;
		$list["trackback_view_count"] = $trackback_view_count;
		$list["comment_list_topview_on"] = $comment_list_topview_on;
		$list["trackback_list_topview_on"] = $trackback_list_topview_on;
		$list["session_time"] = $session_time;
		$list["cookie_time"] = $cookie_time;
		$list["limit_comment"] = $limit_comment;
		$list["limit_trackback"] = $limit_trackback;
		$list["monthly_view_mode"] = $monthly_view_mode;
		$list["category_view_mode"] = $category_view_mode;
	}
	return $list;
}


/* ----- 初期データ更新 ----- */
function blogn_mod_db_init_Change($id, $sitename, $sitedesc, $timezone, $charset, $max_filesize, $permit_file_type, $max_view_width, $max_view_height, $permit_html_tag, $comment_size, $trackback_slash_type, $log_view_count, $mobile_view_count, $new_entry_view_count, $archive_view_count, $comment_view_count, $trackback_view_count, $comment_list_topview_on, $trackback_list_topview_on, $session_time, $cookie_time, $limit_comment, $limit_trackback, $monthly_view_mode, $category_view_mode) {

	$table[0] = "ini";
	$table[1] = "init.cgi";
	$table[2] = BLOGN_MOD_DB_ID_INIT;
	$record[0] = $sitename;
	$record[1] = $sitedesc;
	$record[2] = $timezone;
	$record[3] = $charset;
	$record[4] = $max_filesize;
	$record[5] = $permit_file_type;
	$record[6] = $max_view_width;
	$record[7] = $max_view_height;
	$record[8] = $permit_html_tag;
	$record[9] = $comment_size;
	$record[10] = $trackback_slash_type;
	$record[11] = $log_view_count;
	$record[12] = $mobile_view_count;
	$record[13] = $new_entry_view_count;
	$record[14] = $archive_view_count;
	$record[15] = $comment_view_count;
	$record[16] = $trackback_view_count;
	$record[17] = $comment_list_topview_on;
	$record[18] = $trackback_list_topview_on;
	$record[19] = $session_time;
	$record[20] = $cookie_time;
	$record[21] = $limit_comment;
	$record[22] = $limit_trackback;
	$record[23] = $monthly_view_mode;
	$record[24] = $category_view_mode;

	$errdata = blogn_mod_db_RecordChange($table, $id, $record);
	return $errdata;
}


/* ----- メールサーバへのアクセス時間チェック ----- */
function blogn_mod_db_mobile_access() {
	// ファイルが存在しない場合、新規作成
	if (!$fp1 = @fopen(BLOGN_INIDIR."access.cgi", "r+")) {
		$oldmask = umask();
		umask(000);
		if (!$fp1 = @fopen(BLOGN_INIDIR."access.cgi", "w")) {
			umask($oldmask);
			$errdata[0] = false;
			$errdata[1] = BLOGN_MOD_DB_MES_02;
			$errdata[2] = "access.cgi";
			return $errdata;
		}
		umask($oldmask);
	}

	// ファイルのロック
	if (!$lockkey = blogn_mod_db_file_lock()) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_24;
		return $error;
	}

	$oldaccess = file(BLOGN_INIDIR."access.cgi");

	$newtime = time();
	$update[0] = false;
	$userlist = blogn_mod_db_user_load();
	while(list($key, $val) = each($userlist)) {
		if ($val["receive_mail_user_pw"]) {
			$mailcheck = $found = false;
			@reset($oldaccess);
			while(list($access_key, $access_val) = each($oldaccess)) {
				list($uid, $time,) = explode(",",$access_val);
				$checktime = $time + $val["access_time"] * 60;
				if ($key == $uid) {
					$found = true;
					if ($newtime > $checktime) {
						$mailcheck = true;
						$oldaccess[$access_key] = $uid.",".$newtime.",\n";
					}
					break;
				}
			}
			if (!$found) {
				$mailcheck = true;
				$oldaccess[] = $key.",".$newtime.",\n";
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

	fputs($fp1, implode('', $oldaccess));
	fclose($fp1);

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	return $update;
}


/* ----- スキンリストロード ----- */
function blogn_mod_db_skin_load() {

	$table[0] = "ini";
	$table[1] = "skinlist.cgi";
	$skinlist = blogn_mod_db_RecordLoad($table);

	if (!$skinlist[0]) {
		$error[0] = false;
		$error[1] = $skinlist[1];
		$error[2] = $skinlist[2];
		return $error;
	}

	// スキンリスト取得
	$slist[0] = false;
	while(list($key, $val) = each($skinlist[1])) {
		list($id, $skin_name, $html_file_name, $css_file_name) = explode(",", $val);
		$slist[0] = true;
		$slist[1][$id]["skin_name"] = $skin_name;
		$slist[1][$id]["html_name"] = $html_file_name;
		$slist[1][$id]["css_name"] = $css_file_name;
	}
	return $slist;
}


/* ----- スキン登録 ----- */
function blogn_mod_db_skin_add($skin_name, $html_file_name, $css_file_name) {

	$table[0] = "ini";
	$table[1] = "skinlist.cgi";
	$table[2] = BLOGN_MOD_DB_ID_SKIN;
	$record[0] = $skin_name;
	$record[1] = $html_file_name;
	$record[2] = $css_file_name;
	$updown = 0;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	return $errdata;
}


/* ----- スキン名更新 ----- */
function blogn_mod_db_skin_edit($id, $skin_name, $html_file_name, $css_file_name) {

	$table[0] = "ini";
	$table[1] = "skinlist.cgi";
	$record[0] = $skin_name;
	$record[1] = $html_file_name;
	$record[2] = $css_file_name;

	$errdata = blogn_mod_db_RecordChange($table, $id, $record);
	return $errdata;
}


/* ----- スキンリスト削除 ----- */
function blogn_mod_db_skin_delete($id) {

	$table[0] = "ini";
	$table[1] = "skinlist.cgi";
	$errdata = blogn_mod_db_RecordDelete($table, $id);
	return $errdata;

}


/* ----- 表示スキンリストロード ----- */
function blogn_mod_db_viewskin_load() {

	$table[0] = "ini";
	$table[1] = "viewskin.cgi";
	$viewskin = blogn_mod_db_RecordLoad($table);
	if (!$viewskin[0]) {
		$error[0] = false;
		$error[1] = $viewskin[1];
		$error[2] = $viewskin[2];
		return $error;
	}

	// スキンリスト取得
	while(list($key, $val) = each($viewskin[1])) {
		list($id, $view_type, $category_id, $section_id, $skin_id,) = explode(",", $val);
		$vsort[$key] = $category_id."::".$section_id;
	}
	@asort($vsort);
	while(list($key, $val) = each($vsort)) {
		list($id, $view_type, $category_id, $section_id, $skin_id,) = explode(",", $viewskin[1][$key]);
		$vlist[0] = true;
		$vlist[1][$id]["view_type"] = trim($view_type);
		$vlist[1][$id]["category_id"] = trim($category_id);
		$vlist[1][$id]["section_id"] = trim($section_id);
		$vlist[1][$id]["skin_id"] = trim($skin_id);
	}
	return $vlist;
}


/* ----- 表示スキン登録 ----- */
function blogn_mod_db_viewskin_add($view_type, $category_id, $section_id, $skin_id) {

	switch ($view_type) {
		case "0":
			$table[0] = "ini";
			$table[1] = "viewskin.cgi";

			// ファイルが存在しない場合、新規作成
			$oldmask = umask();
			umask(000);
			if (!$fp1 = @fopen(BLOGN_INIDIR.$table[1], "w")) {
				umask($oldmask);
				$errdata[0] = false;
				$errdata[1] = BLOGN_MOD_DB_MES_02;
				$errdata[2] = $table[1];
				return $errdata;
			}
			umask($oldmask);

			// ファイルのロック
			if (!$lockkey = blogn_mod_db_file_lock()) {
				$error[0] = false;
				$error[1] = BLOGN_MOD_BD_MES_24;
				return $error;
			}
			$newrecord = "0, 0, 0, 0,".$skin_id.",\n";
			// 先頭に新規データ追加
			fputs($fp1, $newrecord);
			fclose($fp1);
			break;
		case "1":
			$table[0] = "ini";
			$table[1] = "viewskin.cgi";

			// ファイルが存在しない場合、新規作成
			$oldmask = umask();
			umask(000);
			if (!$fp1 = @fopen(BLOGN_INIDIR.$table[1], "w")) {
				umask($oldmask);
				$errdata[0] = false;
				$errdata[1] = BLOGN_MOD_DB_MES_02;
				$errdata[2] = $table[1];
				return $errdata;
			}
			umask($oldmask);

			// ファイルのロック
			if (!$lockkey = blogn_mod_db_file_lock()) {
				$error[0] = false;
				$error[1] = BLOGN_MOD_BD_MES_24;
				return $error;
			}
			$i = 0;
			while(list($id, $null) = each($skin_id)) {
				$newrecord[$i] = "{$i}, 1, {$category_id[$id]}, {$section_id[$id]}, {$skin_id[$id]},\n";
				$i++;
			}
			fputs($fp1, implode("", $newrecord));
			fclose($fp1);
			break;
		case "2":
			$skinlist = blogn_mod_db_viewskin_load();

			$table[0] = "ini";
			$table[1] = "viewskin.cgi";

			// ファイルが存在しない場合、新規作成
			$oldmask = umask();
			umask(000);
			if (!$fp1 = @fopen(BLOGN_INIDIR.$table[1], "w")) {
				umask($oldmask);
				$errdata[0] = false;
				$errdata[1] = BLOGN_MOD_DB_MES_02;
				$errdata[2] = $table[1];
				return $errdata;
			}
			umask($oldmask);

			// ファイルのロック
			if (!$lockkey = blogn_mod_db_file_lock()) {
				$error[0] = false;
				$error[1] = BLOGN_MOD_BD_MES_24;
				return $error;
			}
			if ($skinlist[0]){
				if ($skinlist[1][0]["view_type"] == 2) {
					$skinlist[1][0]["skin_id"] = $skin_id;
					$i = 0;
					while(list($id, $val) = each($skinlist[1])) {
						$newrecord[$i] = "{$i}, 2, {$val['category_id']}, {$val['section_id']}, {$val['skin_id']},\n";
						$i++;
					}
					fputs($fp1, implode("", $newrecord));
				}else{
					$newrecord = "0, 2, 0, 0, {$skin_id},\n";
					fputs($fp1, $newrecord);
				}
			}else{
				$newrecord = "0, 2, 0, 0, {$skin_id},\n";
				fputs($fp1, $newrecord);
			}
			fclose($fp1);
			break;
		case "3":
			$skinlist = blogn_mod_db_viewskin_load();

			//ジャンル別処理のジャンル追加処理
			while(list($id, $val) = each($skinlist[1])) {
				if ($val["category_id"] == $category_id && $val["section_id"] == $section_id) {
					$errdata[0] = false;
					$errdata[1] = BLOGN_MOD_BD_MES_26;
					return $errdata;
				}
			}

			$table[0] = "ini";
			$table[1] = "viewskin.cgi";

			// ファイルが存在しない場合、新規作成
			$oldmask = umask();
			umask(000);
			if (!$fp1 = @fopen(BLOGN_INIDIR.$table[1], "w")) {
				umask($oldmask);
				$errdata[0] = false;
				$errdata[1] = BLOGN_MOD_DB_MES_02;
				$errdata[2] = $table[1];
				return $errdata;
			}
			umask($oldmask);

			// ファイルのロック
			if (!$lockkey = blogn_mod_db_file_lock()) {
				$error[0] = false;
				$error[1] = BLOGN_MOD_BD_MES_24;
				return $error;
			}

			reset($skinlist[1]);
			$i = 0;
			while(list($id, $val) = each($skinlist[1])) {
				$newrecord[$i] = "{$i}, 2, {$val['category_id']}, {$val['section_id']}, {$val['skin_id']},\n";
				$i++;
			}
			$newrecord[$i] = "{$i}, 2, {$category_id}, {$section_id}, {$skin_id},\n";
			fputs($fp1, implode("", $newrecord));
			fclose($fp1);
			break;
	}

	// ロック解除
	if (!blogn_mod_db_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = BLOGN_MOD_BD_MES_25;
		return $error;
	}

	$errdata[0] = true;
	$errdata[1] = BLOGN_MOD_DB_MES_01;
	return $errdata;
}


function blogn_mod_db_viewskin_del($id) {
	$table[0] = "ini";
	$table[1] = "viewskin.cgi";
	$errdata = blogn_mod_db_RecordDelete($table, $id);
	return $errdata;
}

/* ----- アクセス制限のあるIPを遮断 ----- */
function blogn_mod_db_ip_check($ip) {
	if (!file_exists(BLOGN_INIDIR."denyip.cgi")) return true;

	$deny_ip = file(BLOGN_INIDIR."denyip.cgi");
	//$deny_ipから改行コード削除
	for ( $i = 0; $i < count( $deny_ip ); $i++ ) {
		list($key1, $key2, $key3, $key4,) = explode(".",$ip);
		list($id, $ipaddr, $ipdate,) = explode(",", $deny_ip[$i]);
		list($deny_key1, $deny_key2, $deny_key3, $deny_key4) = explode(".",$ipaddr);
		if ($deny_key1 == "*") $key1 = "*";
		if ($deny_key2 == "*") $key2 = "*";
		if ($deny_key3 == "*") $key3 = "*";
		if ($deny_key4 == "*") $key4 = "*";
		if ($deny_key1 == $key1 && $deny_key2 == $key2 && $deny_key3 == $key3 && $deny_key4 == $key4) {
			return false;
		}
	}
	return true;
}


/* ----- アクセス制限IPリストロード ----- */
function blogn_mod_db_denyip_load() {

	$table[0] = "ini";
	$table[1] = "denyip.cgi";
	$denyip = blogn_mod_db_RecordLoad($table);

	if (!$denyip[0]) {
		$error[0] = false;
		$error[1] = $denyip[1];
		$error[2] = $denyip[2];
		return $error;
	}

	// リスト取得
	while(list($key, $val) = each($denyip[1])) {
		list($id, $date, $ip,) = explode(",", $val);
		$ilist[0] = true;
		$ilist[1][$id]["date"] = $date;
		$ilist[1][$id]["ip"] = $ip;
	}

	return $ilist;
}


/* ----- アクセス制限IP登録 ----- */
function blogn_mod_db_denyip_add($date, $ip) {

	$table[0] = "ini";
	$table[1] = "denyip.cgi";
	$table[2] = BLOGN_MOD_DB_ID_DENYIP;
	$record[0] = $date;
	$record[1] = $ip;
	$updown = 0;

	$errdata = blogn_mod_db_RecordAdd($table, $record, $updown);
	return $errdata;
}


/* ----- アクセス制限IP削除 ----- */
function blogn_mod_db_denyip_delete($id) {

	$table[0] = "ini";
	$table[1] = "denyip.cgi";
	$errdata = blogn_mod_db_RecordDelete($table, $id);
	return $errdata;

}
?>
