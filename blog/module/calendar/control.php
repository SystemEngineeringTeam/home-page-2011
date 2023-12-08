<?php
//--------------------------------------------------------------------
// Weblog PHP script BlognPLUS
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//--------------------------------------------------------------------
// *** Calender Module ***
//
// control.php
//
// LAST UPDATE 2007/01/11
//
// ・「第○週月曜日」から「第○月曜日」に表示を変更
//
//--------------------------------------------------------------------
$blogn_skin = file("./".BLOGN_MODDIR."calendar/setting.html");
$blogn_skin = implode("",$blogn_skin);

switch($qry_action) {
	case "set":
		$error = blogn_mod_calendar_file_update($_POST["blogn_wday"]);
		// インフォメーション表示
		$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
		break;
	case "add":
		if (!$_POST["blogn_holiday_type"]) {
			$blogn_holiday_day = $_POST["blogn_holiday_day"];
		}else{
			$blogn_holiday_day = $_POST["blogn_holiday_wday"];
		}
		$error = blogn_mod_calendar_file_add($_POST["blogn_holiday_month"], $blogn_holiday_day);
		// インフォメーション表示
		$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
		break;
	case "delete":
		$error = blogn_mod_calendar_file_del($_POST["blogn_mod_calendar_id"]);
		// インフォメーション表示
		$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
		break;
	default:
		// ini.cgiファイルチェック
		$error = blogn_mod_calendar_ini_check();

		// インフォメーション表示
		$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
		break;
}

$inilist = blogn_mod_calendar_ini_load();
if (!$inilist["wday"]) {
	$blogn_skin = str_replace ("{BLOGN_WDAY0}", " checked", $blogn_skin);
}else{
	$blogn_skin = str_replace ("{BLOGN_WDAY1}", " checked", $blogn_skin);
}

if (!$inilist["holiday"][0]) {
	$blogn_skin = preg_replace("/\{BLOGN_MOD_CALENDAR_LIST\}[\w\W]+?\{\/BLOGN_MOD_CALENDAR_LIST\}/", $blogn_skin_list_all, $blogn_skin);
}else{
	$blogn_skin = str_replace ("{BLOGN_MOD_CALENDAR_LIST}", "", $blogn_skin);
	$blogn_skin = str_replace ("{/BLOGN_MOD_CALENDAR_LIST}", "", $blogn_skin);

	preg_match("/\{BLOGN_MOD_CALENDAR_LIST_LOOP\}([\w\W]+?)\{\/BLOGN_MOD_CALENDAR_LIST_LOOP\}/", $blogn_skin, $blogn_reg);
	$blogn_mod_calendar_list_all = "";
	while(list($key, $val) = each($inilist["holiday"])) {
		$blogn_mod_calendar_list = $blogn_reg[0];
		$blogn_mod_calendar_list = str_replace ("{BLOGN_MOD_CALENDAR_LIST_LOOP}", "", $blogn_mod_calendar_list);
		$blogn_mod_calendar_list = str_replace ("{/BLOGN_MOD_CALENDAR_LIST_LOOP}", "", $blogn_mod_calendar_list);

		list($blogn_mod_calendar_month, $blogn_mod_calendar_day) = explode(",", $val);
		$blogn_mod_calendar_month = trim($blogn_mod_calendar_month);
		$blogn_mod_calendar_day = trim($blogn_mod_calendar_day);
		switch ($blogn_mod_calendar_day) {
			case "w1":
				$blogn_mod_calendar_holiday = $blogn_mod_calendar_month."月 第1月曜日";
				break;
			case "w2":
				$blogn_mod_calendar_holiday = $blogn_mod_calendar_month."月 第2月曜日";
				break;
			case "w3":
				$blogn_mod_calendar_holiday = $blogn_mod_calendar_month."月 第3月曜日";
				break;
			case "w4":
				$blogn_mod_calendar_holiday = $blogn_mod_calendar_month."月 第4月曜日";
				break;
			default:
				$blogn_mod_calendar_holiday = $blogn_mod_calendar_month."月 ".$blogn_mod_calendar_day."日";
				break;
		}
		$blogn_mod_calendar_list = str_replace ("{BLOGN_MOD_CALENDAR_HOLIDAY}", $blogn_mod_calendar_holiday, $blogn_mod_calendar_list);

		$blogn_mod_calendar_list = str_replace ("{BLOGN_MOD_CALENDAR_ID}", $key, $blogn_mod_calendar_list);

		$blogn_mod_calendar_list_all .= $blogn_mod_calendar_list;
	}
	$blogn_skin = preg_replace("/\{BLOGN_MOD_CALENDAR_LIST_LOOP\}[\w\W]+?\{\/BLOGN_MOD_CALENDAR_LIST_LOOP\}/", $blogn_mod_calendar_list_all, $blogn_skin);
}

echo $blogn_skin;
// -------------------------------------------------------


function blogn_mod_calendar_ini_check() {
	// ファイルが存在しない場合、新規作成
	$inidir = "./".BLOGN_MODDIR."calendar/ini.cgi";
	if (!$fp1 = @fopen($inidir, "r+")) {
		$oldmask = umask();
		umask(000);
		if (!$fp1 = @fopen($inidir, "w")) {
			umask($oldmask);
			$errdata[0] = false;
			$errdata[1] = "設定ファイルが作成できません。カレンダーモジュールのパーミッションを確認してください。";
			$errdata[2] = "ini.cgi";
			return $errdata;
		}else{
			umask($oldmask);
			$errdata[0] = true;
			$errdata[1] = "設定ファイルを新しく作成しました。";
			$errdata[2] = "ini.cgi";
			return $errdata;
		}
	}
	$errdata[0] = true;
	$errdata[1] = "設定ファイルをを読み込みました。";
	$errdata[2] = "ini.cgi";
	return $errdata;

}


function blogn_mod_calendar_file_update($wday) {
	$inidir = "./".BLOGN_MODDIR."calendar/ini.cgi";
	$inifile = file($inidir);
	$fp1 = @fopen($inidir, "w");

	// ファイルのロック
	if (!$lockkey = blogn_mod_calendar_file_lock()) {
		$error[0] = false;
		$error[1] = "ファイルはビジーです。少し待ってから実行してください。";
		return $error;
	}

	if ($inifile) {
		$inifile[0] = $wday.",\n";
		fputs($fp1, implode('', $inifile));
	}else{
		$inifile = $wday.",\n";
		fputs($fp1, $inifile);
	}
	fclose($fp1);

	// ロック解除
	if (!blogn_mod_calendar_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = "致命的なエラー。ファイルのロックが解除できませんでした。";
		return $error;
	}

	$errdata[0] = true;
	$errdata[1] = "設定は更新されました。";
	$errdata[2] = $id;
	return $errdata;

}


function blogn_mod_calendar_file_add($month, $day) {
	$inidir = "./".BLOGN_MODDIR."calendar/ini.cgi";
	$inifile = file($inidir);

	$newfile = $month.", ".$day.",\n";

	//ジャンル別処理のジャンル追加処理
	while(list($key, $val) = each($inifile)) {
		if ($val == $newfile) {
			$errdata[0] = false;
			$errdata[1] = "その指定日はすでに登録されています。";
			return $errdata;
		}
	}


	$fp1 = @fopen($inidir, "w");

	// ファイルのロック
	if (!$lockkey = blogn_mod_calendar_file_lock()) {
		$error[0] = false;
		$error[1] = "ファイルはビジーです。少し待ってから実行してください。";
		return $error;
	}

	if (!$inifile) {
		$inifile[0] = "0,\n";
	}
	$inifile[] = $newfile;

	natsort($inifile);
	fputs($fp1, implode('', $inifile));

	fclose($fp1);

	// ロック解除
	if (!blogn_mod_calendar_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = "致命的なエラー。ファイルのロックが解除できませんでした。";
		return $error;
	}

	$errdata[0] = true;
	$errdata[1] = "設定は更新されました。";
	$errdata[2] = $id;
	return $errdata;
}


function blogn_mod_calendar_file_del($id) {
	$inidir = "./".BLOGN_MODDIR."calendar/ini.cgi";
	$inifile = file($inidir);

	$fp1 = @fopen($inidir, "w");

	// ファイルのロック
	if (!$lockkey = blogn_mod_calendar_file_lock()) {
		$error[0] = false;
		$error[1] = "ファイルはビジーです。少し待ってから実行してください。";
		return $error;
	}

	// レコードから指定IDを削除
	$delrec = (INT)$id + 1;
	array_splice($inifile, $delrec, 1);

	$newinifile = implode('', $inifile);

	fputs($fp1, $newinifile);
	ftruncate($fp1, strlen($newinifile));
	fclose($fp1);

	// ロック解除
	if (!blogn_mod_calendar_file_unlock($lockkey)) {
		$error[0] = false;
		$error[1] = "致命的なエラー。ファイルのロックが解除できませんでした。";
		return $error;
	}

	$errdata[0] = true;
	$errdata[1] = "指定された祝日を削除しました。";
	$errdata[2] = $id;
	return $errdata;
}


function blogn_mod_calendar_file_lock() {
	$id = uniqid('lock');
	for ($i = 0; $i < 5; $i++) {
		if (@rename("./".BLOGN_MODDIR.'/calendar/lock', "./".BLOGN_MODDIR.'/calendar/'.$id)) {
			return $id;
		}
		sleep(1);
	}
	return false;
}

function blogn_mod_calendar_file_unlock($id) {
	if (@rename("./".BLOGN_MODDIR.'/calendar/'.$id, "./".BLOGN_MODDIR.'/calendar/lock')) {
		return true;
	}else{
		return false;
	}
}

?>
