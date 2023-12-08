<?php
//--------------------------------------------------------------------
// Weblog PHP script BlognPlus
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//--------------------------------------------------------------------
// admin.php
//
// LAST UPDATE 2007/02/23
//
// ・記事作成（編集）時のプレビューが正しく表示されない不具合を修正
// ・初期設定で更新ボタンを押してもデータが表示上反映されていないように
//   みえる不具合を修正
// ・ユーザー情報でのパスワード文字数を４～１２文字までに制限
// ・トラックバック送信処理を一部変更
//
//--------------------------------------------------------------------


//-------------------------------------------------------------------- 初期設定

/* ===== 初期設定ファイル読み込み ===== */
include("./conf.php");
include("./common.php");

define("BLOGN_USERAGENT", $_SERVER["HTTP_USER_AGENT"]);

/* ===== オートログイン処理 ===== */
if ($blogn_req_auto_login == "on") {
	setcookie("blogn_cookie_id", $blogn_req_id, time() + BLOGN_COOKIE_TIME);
	setcookie("blogn_cookie_pw", $blogn_req_pw, time() + BLOGN_COOKIE_TIME);
}

/* ===== セッションスタート ===== */
session_set_cookie_params(0, BLOGN_REQUESTDIR);
session_start();
session_register("blogn_session_id");
session_register("blogn_session_pw");

//-------------------------------------------------------------------- ログイン処理

/* ===== 認証 ===== */
$blogn_admin = false;
if (@$_GET["forgetpass"]) {
	$blogn_error = blogn_request_new_password($_GET["userid"], $_GET["forgetpass"]);
	blogn_login_form($blogn_error);
	exit;
}elseif (isset($_POST["blogn_req_id"]) && isset($_POST["blogn_req_pw"])) {
	// ユーザーチェック
	$blogn_error = blogn_mod_db_user_check($_POST["blogn_req_id"], $_POST["blogn_req_pw"]);
	if ($blogn_error[0] && $blogn_error[3] != "0" && $blogn_error[3] != "1") {
		$_SESSION["blogn_session_id"] = $_POST["blogn_req_id"];
		$_SESSION["blogn_session_pw"] = $_POST["blogn_req_pw"];
		$blogn_admin = $blogn_error[2];
	}else{
		blogn_login_form("ユーザーIDまたはパスワードが違います。");
		exit;
	}
}else{
	// オートログインが有効か確認（クッキーにデータがあるか確認）
	if (!$_COOKIE["blogn_cookie_pw"]) {
		// クッキーにデータが無い場合セッションチェック
		if (!isset($_SESSION["blogn_session_id"])) {
			// IDが無ければログインフォーム表示
				blogn_login_form();
				exit;
		}else{
			// ログインIDがある場合、認証
			// ユーザーチェック
			$blogn_error = blogn_mod_db_user_check($_SESSION["blogn_session_id"], $_SESSION["blogn_session_pw"]);
			if ($blogn_error[0] && $blogn_error[3] != "0" && $blogn_error[3] != "1") {
				$blogn_admin = $blogn_error[2];
			}else{
				blogn_login_form("ユーザーIDまたはパスワードが違います。");
				exit;
			}
		}
	}else{
		// クッキーにデータがある場合、認証
		// 追加ユーザー用チェック
		$blogn_error = blogn_mod_db_user_check($_COOKIE["blogn_cookie_id"], $_COOKIE["blogn_cookie_pw"]);
		if ($blogn_error[0] && $blogn_error[3] != "0" && $blogn_error[3] != "1") {
			$blogn_admin = $blogn_error[2];
		}else{
			blogn_login_form("ユーザーIDまたはパスワードが違います。");
			exit;
		}
	}
}

//-------------------------------------------------------------------- メニュー処理
define("BLOGN_JAVA_MODE_NONE", 0);
define("BLOGN_JAVA_MODE_EDITOR", 1);
define("BLOGN_JAVA_MODE_YESNO", 2);

define("BLOGN_ACTION_MODE_TOP", 0);
define("BLOGN_ACTION_MODE_NEW", 1);
define("BLOGN_ACTION_MODE_LIST", 2);
define("BLOGN_ACTION_MODE_FILES", 3);
define("BLOGN_ACTION_MODE_CATEGORY", 4);
define("BLOGN_ACTION_MODE_LINK", 5);
define("BLOGN_ACTION_MODE_PING", 6);
define("BLOGN_ACTION_MODE_PROFILE", 7);
define("BLOGN_ACTION_MODE_USER", 8);
define("BLOGN_ACTION_MODE_INIT", 9);
define("BLOGN_ACTION_MODE_DATA",10);
define("BLOGN_ACTION_MODE_SKINMAKE", 11);
define("BLOGN_ACTION_MODE_SKINFILE", 12);
define("BLOGN_ACTION_MODE_SKINSET", 13);
define("BLOGN_ACTION_MODE_MODULE", 14);
define("BLOGN_ACTION_MODE_SECURITY", 15);
define("BLOGN_ACTION_MODE_LOGOUT", 16);

/* ----- モード ----- */
$blogn_qry_mode = "";
$blogn_qry_mode = @$_GET["mode"];
$blogn_qry_action = @$_GET["action"];

if ($blogn_qry_mode == "user" && $_POST["blogn_user_edit"]) $blogn_qry_mode = "profile";

/* ===== モジュール一覧取得 ===== */
$blogn_modules = blogn_module_load();
if ($blogn_module[0]) {
	while (list($key, $val) = each($blogn_modules[1])) {
		include_once(BLOGN_MODDIR.$key."/".$val["function"]);
	}
}

// 各管理画面表示
switch ($blogn_qry_mode) {
	// 新規記事投稿（ユーザー）
	case "new":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_NEW, $blogn_admin, BLOGN_JAVA_MODE_EDITOR);
		if (!$blogn_admin) {
			if (!$blogn_user_id) $blogn_user_id = $_COOKIE["blogn_cookie_pw"] ? $_COOKIE["blogn_cookie_id"] : $_SESSION["blogn_session_id"];
		}else{
			if (!$blogn_user_id) $blogn_user_id = $_COOKIE["blogn_cookie_pw"] ? $_COOKIE["blogn_cookie_id"] : $_SESSION["blogn_session_id"];
		}
		if (!$_POST["blogn_user_key"]) {
			$blogn_userlist = blogn_mod_db_user_load();
			while (list($key, $val) = each($blogn_userlist)) {
				if ($blogn_user_id == $val["id"]) {
					$blogn_user_key = $key;
					break;
				}
			}
		}
		if ($_POST["blogn_preview"]) $blogn_qry_action = "preview";

		$blogn_new_post = @$_POST["blogn_new_post"];
		$blogn_ent_id = @$_POST["blogn_ent_id"] ? $_POST["blogn_ent_id"] : $_GET["blogn_ent_id"];
		$blogn_ent_year = @$_POST["blogn_ent_year"];
		$blogn_ent_month = @$_POST["blogn_ent_month"];
		$blogn_ent_day = @$_POST["blogn_ent_day"];
		$blogn_ent_hour = @$_POST["blogn_ent_hour"];
		$blogn_ent_minutes = @$_POST["blogn_ent_minutes"];
		$blogn_ent_second = @$_POST["blogn_ent_second"];
		$blogn_reserve = @$_POST["blogn_reserve"];
		$blogn_secret = @$_POST["blogn_secret"];
		$blogn_comment_ok = @$_POST["blogn_comment_ok"];
		$blogn_trackback_ok = @$_POST["blogn_trackback_ok"];
		$blogn_category = @$_POST["blogn_category"];
		$blogn_title = @$_POST["blogn_title"];
		$blogn_mes = @$_POST["blogn_mes"];
		$blogn_more = @$_POST["blogn_more"];
		$blogn_send_trackback = @$_POST["blogn_send_trackback"];
		$blogn_send_ping_url = @$_POST["blogn_send_ping_url"];
		$blogn_br_change = @$_POST["blogn_br_change"];
		blogn_blog_new($blogn_admin, $blogn_qry_action, $blogn_user_key, $blogn_new_post, $blogn_ent_id, $blogn_ent_year, $blogn_ent_month, $blogn_ent_day, $blogn_ent_hour, $blogn_ent_minutes, $blogn_ent_second, $blogn_user_key, $blogn_reserve, $blogn_secret, $blogn_comment_ok, $blogn_trackback_ok, $blogn_category, $blogn_title, $blogn_mes, $blogn_more, $blogn_send_trackback, $blogn_send_ping_url, $blogn_br_change);
		break;

	// 記事一覧（ユーザー別）
	case "list":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_LIST, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		if (!$blogn_admin) {
			if (!$blogn_user_id) $blogn_user_id = $_COOKIE["blogn_cookie_pw"] ? $_COOKIE["blogn_cookie_id"] : $_SESSION["blogn_session_id"];
		}else{
			if (!$blogn_user_id) $blogn_user_id = $_COOKIE["blogn_cookie_pw"] ? $_COOKIE["blogn_cookie_id"] : $_SESSION["blogn_session_id"];
		}
		$blogn_userlist = blogn_mod_db_user_load();
		while (list($key, $val) = each($blogn_userlist)) {
			if ($blogn_user_id == $val["id"]) {
				$blogn_user_key = $key;
				break;
			}
		}
		$blogn_page = @$_GET["page"];
		if ($blogn_qry_action == "log_delete" || $blogn_qry_action == "cmt_delete" || $blogn_qry_action == "trk_delete") {
			$blogn_id = @$_GET["id"];
		}elseif ($blogn_qry_action == "log_select"){

			$blogn_id = $_POST["blogn_check"];

			if (@$_POST["blogn_select_change_x"]) {
				$blogn_qry_action = "log_select_change";
				$blogn_log_title = $_POST["blogn_log_title"];
				$blogn_log_category = $_POST["blogn_log_category"];
			}else{
				$blogn_qry_action = "log_select_delete";
			}
		}elseif ($blogn_qry_action == "update") {
			$blogn_id = $_GET["blogn_ent_id"];
			$blogn_log_title = rawurldecode($_GET["blogn_title"]);
			$blogn_log_category = $_GET["blogn_category"];
		}
		if ($_GET["type"] == "comment") {
			$blogn_log_id = $_GET["id"];
			if (@$_POST["cmtid"]) {
				$blogn_cmt_id = $_POST["cmtid"];
				$blogn_qry_action = "select_delete";
			}else{
				$blogn_cmt_id = $_GET["cmtid"];
			}
			blogn_blog_comment_control($blogn_admin, $blogn_qry_action, $blogn_page, $blogn_log_id, $blogn_cmt_id);
		}elseif ($_GET["type"] == "trackback") {
			$blogn_log_id = $_GET["id"];
			if (@$_POST["trkid"]) {
				$blogn_trk_id = $_POST["trkid"];
				$blogn_qry_action = "select_delete";
			}else{
				$blogn_trk_id = $_GET["trkid"];
			}
			blogn_blog_trackback_control($blogn_admin, $blogn_qry_action, $blogn_page, $blogn_log_id, $blogn_trk_id);
		}else{
			$blogn_view_category = @$_POST["blogn_category"] ? $_POST["blogn_category"] : @$_GET["category"];
			blogn_blog_list_control($blogn_admin, $blogn_qry_action, $blogn_page, $blogn_id, $blogn_user_key, $blogn_view_category, $blogn_log_title, $blogn_log_category);
		}
		break;

	// ファイル一覧（ユーザー別）
	case "files":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_FILES, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		$blogn_upfile = @$_FILES["blogn_upload_file"];
		if (!$blogn_admin) {
			if (!$blogn_user_id) $blogn_user_id = $_COOKIE["blogn_cookie_pw"] ? $_COOKIE["blogn_cookie_id"] : $_SESSION["blogn_session_id"];
		}else{
			if (!$blogn_user_id) $blogn_user_id = $_COOKIE["blogn_cookie_pw"] ? $_COOKIE["blogn_cookie_id"] : $_SESSION["blogn_session_id"];
		}
		$blogn_userlist = blogn_mod_db_user_load();
		while (list($key, $val) = each($blogn_userlist)) {
			if ($blogn_user_id == $val["id"]) {
				$blogn_user_key = $key;
				break;
			}
		}
		$blogn_page = @$_GET["page"];
		if ($blogn_qry_action == "delete") {
			$blogn_id = @$_GET["id"];
		}else{
			$blogn_id = @$_POST["blogn_id"];
			$blogn_comment = @$_POST["blogn_comment"];
		}
		blogn_files_control($blogn_admin, $blogn_qry_action, $blogn_page, $blogn_id, $blogn_user_key, $blogn_comment, $blogn_upfile);
		break;

	// カテゴリ一覧（管理者）
	case "category":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_CATEGORY, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		if ($blogn_qry_action == "category1_delete") {
			$blogn_category1_id = @$_GET["id"];
		}elseif ($blogn_qry_action == "category2_delete") {
			$blogn_category2_id = @$_GET["id"];
		}elseif ($blogn_qry_action == "category1_change") {
			$blogn_category1_id = @$_GET["id"];
			$blogn_updown = @$_GET["updown"];
		}elseif ($blogn_qry_action == "category2_change") {
			$blogn_category1_id = @$_GET["cid"];
			$blogn_category2_id = @$_GET["id"];
			$blogn_updown = @$_GET["updown"];
		}else{
			$blogn_category1_id = @$_POST["blogn_category1_id"];
			$blogn_category1_name = @$_POST["blogn_category1_name"];
			$blogn_category2_id = @$_POST["blogn_category2_id"];
			$blogn_category2_name = @$_POST["blogn_category2_name"];
			$blogn_view_mode = @$_POST["blogn_category_view_mode"];
		}
		blogn_category_control($blogn_admin, $blogn_qry_action, $blogn_category1_id, $blogn_category1_name, $blogn_category2_id, $blogn_category2_name, $blogn_updown, $blogn_view_mode);
		break;

	// リンク一覧（管理者）
	case "link":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_LINK, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		if ($blogn_qry_action == "group_delete" || $blogn_qry_action == "link_delete") {
			$blogn_id = @$_GET["id"];
		}elseif ($blogn_qry_action == "group_change" || $blogn_qry_action == "link_change") {
			$blogn_id = @$_GET["id"];
			$blogn_group_id = @$_GET["gid"];
			$blogn_updown = @$_GET["updown"];
		}else{
			$blogn_id = @$_POST["blogn_id"];
			$blogn_group_id = @$_POST["blogn_group_id"];
			$blogn_link_name = @$_POST["blogn_link_name"];
			$blogn_link_url = @$_POST["blogn_link_url"];
		}
		blogn_link_control($blogn_admin, $blogn_qry_action, $blogn_id, $blogn_group_id, $blogn_link_name, $blogn_link_url, $blogn_updown);
		break;

	// 更新Ping一覧（管理者）
	case "ping":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_PING, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		if ($blogn_qry_action == "delete") {
			$blogn_id = @$_GET["id"];
		}else{
			$blogn_id = @$_POST["blogn_id"];
			$blogn_ping_name = @$_POST["blogn_ping_name"];
			$blogn_ping_url = @$_POST["blogn_ping_url"];
			$blogn_ping_default = @$_POST["blogn_ping_default"];
		}

		blogn_ping_control($blogn_admin, $blogn_qry_action, $blogn_id, $blogn_ping_name, $blogn_ping_url, $blogn_ping_default);
		break;

	// プロフィール（ユーザー別）
	case "profile":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_PROFILE, $blogn_admin, BLOGN_JAVA_MODE_EDITOR);
		if (!$blogn_admin) {
			if (!$_POST["blogn_user_id"]) {
				$blogn_user_id = $_COOKIE["blogn_cookie_pw"] ? $_COOKIE["blogn_cookie_id"] : $_SESSION["blogn_session_id"];
			}else{
				$blogn_user_id = $_POST["blogn_user_id"];
			}
		}else{
			if (!$_POST["blogn_user_id"]) {
				$blogn_user_id = $_COOKIE["blogn_cookie_pw"] ? $_COOKIE["blogn_cookie_id"] : $_SESSION["blogn_session_id"];
			}else{
				$blogn_user_id = $_POST["blogn_user_id"];
			}
		}

		if ($_POST["blogn_mailcheck"]) $blogn_qry_action = "mailcheck";

		$blogn_id = @$_POST["blogn_id"];
		$blogn_user_pw = @$_POST["blogn_user_pw"];
		$blogn_user_retype_pw = @$_POST["blogn_user_retype_pw"];
		$blogn_user_name = @$_POST["blogn_user_name"];
		$blogn_user_profile = @$_POST["blogn_user_profile"];
		$blogn_init_comment_ok = @$_POST["blogn_init_comment_ok"];
		$blogn_init_trackback_ok = @$_POST["blogn_init_trackback_ok"];
		$blogn_init_category = @$_POST["blogn_init_category"];
		$blogn_init_icon_ok = @$_POST["blogn_init_icon_ok"];
		$blogn_receive_mail_address = @$_POST["blogn_receive_mail_address"];
		$blogn_receive_mail_pop3 = @$_POST["blogn_receive_mail_pop3"];
		$blogn_receive_mail_user_id = @$_POST["blogn_receive_mail_user_id"];
		$blogn_receive_mail_user_pw = @$_POST["blogn_receive_mail_user_pw"];
		$blogn_receive_mail_apop = @$_POST["blogn_receive_mail_apop"];
		$blogn_access_time = @$_POST["blogn_access_time"];
		$blogn_send_mail_address = @$_POST["blogn_send_mail_address"];
		$blogn_mobile_category = @$_POST["blogn_mobile_category"];
		$blogn_mobile_comment_ok = @$_POST["blogn_mobile_comment_ok"];
		$blogn_mobile_trackback_ok = @$_POST["blogn_mobile_trackback_ok"];
		$blogn_information_mail_address = @$_POST["blogn_information_mail_address"];
		$blogn_information_comment = @$_POST["blogn_information_comment"];
		$blogn_information_trackback = @$_POST["blogn_information_trackback"];
		$blogn_user_mail_address = @$_POST["blogn_user_mail_address"];
		$blogn_br_change = @$_POST["blogn_br_change"] ? (INT)$_POST["blogn_br_change"] : 0;
		blogn_profile_control($blogn_admin, $blogn_qry_action, $blogn_id, $blogn_user_id, $blogn_user_pw, $blogn_user_retype_pw, $blogn_user_name, $blogn_user_profile, $blogn_init_comment_ok, $blogn_init_trackback_ok, $blogn_init_category, $blogn_init_icon_ok, $blogn_receive_mail_address, $blogn_receive_mail_pop3, $blogn_receive_mail_user_id, $blogn_receive_mail_user_pw, $blogn_receive_mail_apop, $blogn_access_time, $blogn_send_mail_address, $blogn_mobile_category, $blogn_mobile_comment_ok, $blogn_mobile_trackback_ok, $blogn_information_mail_address, $blogn_information_comment, $blogn_information_trackback, $blogn_user_mail_address, $blogn_br_change);
		break;

	// ユーザー管理（管理者）
	case "user":
		// 管理画面ヘッダ表示
		if ($_POST["blogn_user_add"]) {
			$blogn_qry_action = "add";
		}elseif ($_POST["blogn_user_active"]) {
			$blogn_qry_action = "active";
		}elseif ($_POST["blogn_user_del"]) {
			$blogn_qry_action = "del";
		}else{
			$blogn_qry_action = "";
		}
		blogn_admin_menu_header(BLOGN_ACTION_MODE_USER, $blogn_admin, BLOGN_JAVA_MODE_YESNO);

		$blogn_id = @$_POST["blogn_id"];
		$blogn_user_id = @$_POST["blogn_user_id"];
		$blogn_user_pw = @$_POST["blogn_user_pw"];
		$blogn_user_retype_pw = @$_POST["blogn_user_retype_pw"];
		$blogn_user_name = @$_POST["blogn_user_name"];
		$blogn_mail_address = @$_POST["blogn_mail_address"];
		$blogn_user_active = @$_POST["blogn_user_active"];
		$blogn_user_active_mode[$blogn_id] = @$_POST["blogn_user_active_mode"][$blogn_id];
		blogn_user_control($blogn_admin, $blogn_qry_action, $blogn_id, $blogn_user_id, $blogn_user_pw, $blogn_user_retype_pw, $blogn_user_name, $blogn_mail_address, $blogn_user_active, $blogn_user_active_mode[$blogn_id]);
		break;

	// 初期設定（管理者）
	case "init":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_INIT, $blogn_admin, BLOGN_JAVA_MODE_YESNO);

		$blogn_sitename = @$_POST["blogn_sitename"];
		$blogn_sitedesc = @$_POST["blogn_sitedesc"];
		$blogn_timezone = @$_POST["blogn_timezone"];
		$blogn_charset = @$_POST["blogn_charset"];
		$blogn_max_filesize = @$_POST["blogn_max_filesize"];
		$blogn_max_view_width = @$_POST["blogn_max_view_width"];
		$blogn_max_view_height = @$_POST["blogn_max_view_height"];
		$blogn_comment_size = @$_POST["blogn_comment_size"];
		$blogn_trackback_slash_type = @$_POST["blogn_trackback_slash_type"];
		$blogn_log_view_count = @$_POST["blogn_log_view_count"];
		$blogn_mobile_view_count = @$_POST["blogn_mobile_view_count"];
		$blogn_new_entry_view_count = @$_POST["blogn_new_entry_view_count"];
		$blogn_archive_view_count = @$_POST["blogn_archive_view_count"];
		$blogn_comment_view_count = @$_POST["blogn_comment_view_count"];
		$blogn_trackback_view_count = @$_POST["blogn_trackback_view_count"];
		$blogn_comment_list_topview_on = @$_POST["blogn_comment_list_topview_on"];
		$blogn_trackback_list_topview_on = @$_POST["blogn_trackback_list_topview_on"];
		$blogn_session_time = @$_POST["blogn_session_time"];
		$blogn_cookie_time = @$_POST["blogn_cookie_time"];

		$blogn_limit_comment = @$_POST["blogn_limit_comment"];
		$blogn_limit_trackback = @$_POST["blogn_limit_trackback"];

		$blogn_monthly_view_mode = @$_POST["blogn_monthly_view_mode"];
		$blogn_category_view_mode = @$_POST["blogn_category_view_mode"];

		if ($_POST["blogn_permit_file_add"]) {
			$blogn_permit_file_type = @$_POST["blogn_permit_file_add_text"];
			$blogn_qry_action = "file_add";
		}elseif ($_POST["blogn_permit_html_add"]) {
			$blogn_permit_html_tag = @$_POST["blogn_permit_html_add_text"];
			$blogn_qry_action = "html_add";
		}elseif ($_POST["blogn_permit_file_del"]) {
			$blogn_permit_file_type = @$_POST["blogn_permit_file_type"];
			$blogn_qry_action = "file_del";
		}elseif ($_POST["blogn_permit_html_del"]) {
			$blogn_permit_html_tag = @$_POST["blogn_permit_html_tag"];
			$blogn_qry_action = "html_del";
		}else{
			$blogn_permit_file_type = "";
			$blogn_permit_html_tag = "";
		}


		blogn_init_control($blogn_admin, $blogn_qry_action, $blogn_sitename, $blogn_sitedesc, $blogn_timezone, $blogn_charset, $blogn_max_filesize, $blogn_permit_file_type, $blogn_max_view_width, $blogn_max_view_height, $blogn_permit_html_tag, $blogn_comment_size, $blogn_trackback_slash_type, $blogn_log_view_count, $blogn_mobile_view_count, $blogn_new_entry_view_count, $blogn_archive_view_count, $blogn_comment_view_count, $blogn_trackback_view_count, $blogn_comment_list_topview_on, $blogn_trackback_list_topview_on, $blogn_session_time, $blogn_cookie_time, $blogn_limit_comment, $blogn_limit_trackback, $blogn_monthly_view_mode, $blogn_category_view_mode);
		break;

	// データ管理（管理者）
	case "data":
		// 管理画面ヘッダ表示
		$blogn_export_data = "";
		if ($blogn_qry_action == "export") $blogn_export_data = blogn_data_export($blogn_admin, $_POST["blogn_file_charset"]);
		blogn_admin_menu_header(BLOGN_ACTION_MODE_DATA, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		if ($blogn_qry_action == "import") $blogn_upfile = $_FILES["blogn_upfile"];
		blogn_data_control($blogn_admin, $blogn_qry_action, $blogn_upfile, $blogn_export_data);
		break;

	// スキン追加（管理人）
	case "skinmake":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_SKINMAKE, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		if ($blogn_qry_action == "edit" || $blogn_qry_action == "delete") {
			$blogn_skin_id = @$_GET["id"];
		}else{
			$blogn_skin_id = @$_POST["blogn_skin_id"];
		}
		$blogn_skin_name = @$_POST["blogn_skin_name"];

		if ($blogn_qry_action == "edit" ) {
			if ($_POST["blogn_qry_html"]) $blogn_qry_action = "html_update";
			if ($_POST["blogn_qry_css"]) $blogn_qry_action = "css_update";

			$blogn_html_name = @$_POST["blogn_html_name"];
			$blogn_css_name = @$_POST["blogn_css_name"];
			$blogn_html = @$_POST["blogn_html"];
			$blogn_css = @$_POST["blogn_css"];

			blogn_skin_editor($blogn_admin, $blogn_qry_action, $blogn_skin_id, $blogn_skin_name, $blogn_html_name, $blogn_css_name, $blogn_html, $blogn_css);
		}else{

			$blogn_html_file_name = @$_POST["blogn_html_file_name"];
			$blogn_css_file_name = @$_POST["blogn_css_file_name"];

			$blogn_html_upfile = @$_FILES["blogn_html_upload_file"];
			$blogn_css_upfile = @$_FILES["blogn_css_upload_file"];
			blogn_skin_control($blogn_admin, $blogn_qry_action, $blogn_skin_id, $blogn_skin_name, $blogn_html_file_name, $blogn_css_file_name, $blogn_html_upfile, $blogn_css_upfile);
		}
		break;

	// スキン画像アップロード（管理人）
	case "skinfile":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_SKINFILE, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		$blogn_upfile = @$_FILES["blogn_upload_file"];
		$blogn_page = @$_GET["page"];
		$blogn_file_name = @$_POST["blogn_file_name"];
		blogn_skin_file_control($blogn_admin, $blogn_qry_action, $blogn_page, $blogn_file_name, $blogn_upfile);
		break;

	// スキン登録（ユーザー別）
	case "skinset":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_SKINSET, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		if ($_GET["type"] != "") {
			$blogn_view_type = $_GET["type"];
		}else{
			$blogn_view_type = $_POST["blogn_view_type"];
		}
		if ($blogn_qry_action == "update") {
			$blogn_view_category = @$_POST["blogn_view_category"];
			$blogn_view_section = @$_POST["blogn_view_section"];
			$blogn_view_skin = @$_POST["blogn_view_skin"];
		}elseif ($blogn_qry_action == "add") {
			$blogn_view_category = @$_POST["blogn_new_category"];
			$blogn_view_section = @$_POST["blogn_new_section"];
			$blogn_view_skin = @$_POST["blogn_view_skin"];
		}elseif ($blogn_qry_action == "delete") {
			$blogn_id = $_GET["id"];
		}
		blogn_skin_changer_control($blogn_admin, $blogn_qry_action, $blogn_view_type, $blogn_view_category, $blogn_view_section, $blogn_view_skin, $blogn_id);
		break;

	// モジュール一覧
	case "module":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_MODULE, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		if ($blogn_qry_action == "update") {
			$blogn_module_name = $_GET["name"];
		}else{
			$blogn_module_name = $_POST["blogn_module_name"];
		}
		blogn_module_control($blogn_admin, $blogn_modules, $blogn_qry_action, $blogn_module_name);
		break;

	// アクセス制限（管理人）
	case "security":
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_SECURITY, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		if ($blogn_qry_action == "delete") {
			$blogn_id = $_GET["id"];
		}else{
			$blogn_id = @$_POST["blogn_id"];
		}
		$blogn_deny_ip = @$_POST["blogn_deny_ip"];

		blogn_security_control($blogn_admin, $blogn_qry_action, $blogn_id, $blogn_deny_ip);
		break;

	// ログアウト
	case "logout":
		session_destroy();		// セッションの破棄
		setcookie("blogn_cookie_pw");	// クッキーの破棄
		$blogn_error = "ログアウトしました。";
		blogn_login_form($blogn_error);
		exit;

	// トップ画面
	default:
		// 管理画面ヘッダ表示
		blogn_admin_menu_header(BLOGN_ACTION_MODE_TOP, $blogn_admin, BLOGN_JAVA_MODE_YESNO);
		blogn_top_view($admin);
		break;
}

// 管理画面フッタ表示
blogn_admin_menu_footer();

exit;

//-------------------------------------------------------------------- ログイン画面表示

/* +++++ ログイン画面 +++++ */
function blogn_login_form($error = ""){
	$blogn_skin = file("./template/login.html");
	$blogn_skin = implode("",$blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_ERROR}", $error, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ID}", $_COOKIE["blogn_id"], $blogn_skin);

	header("Content-Type: text/html; charset=UTF-8");
	echo $blogn_skin;
}


//-------------------------------------------------------------------- パスワード再発行

/* +++++ パスワード再発行処理 +++++ */
function blogn_request_new_password($userid, $mailaddress) {
	$userlist = blogn_mod_db_user_load();
	$find = false;
	reset($userlist);
	while (list($key, $val) = each($userlist)) {
		if (trim($val["user_mail_address"]) && $val["user_mail_address"] == $mailaddress && $val["id"] == $userid) {
			// メールアドレスがマッチした場合、ランダムなパスワードを生成＆メール送信
			$find = true;
			$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
			$password = '';
			mt_srand ((double) microtime() * 1000000);
			for ($i = 0; $i < 8; $i++) {
				$password .= substr($chars, mt_rand(0, strlen($chars)), 1);
			}

			$error = blogn_mod_db_user_profile_update($key, $val["id"], $password, $val["name"], $val["profile"], $val["init_comment_ok"], $val["init_trackback_ok"], $val["init_category"], $val["init_icon_ok"], $val["receive_mail_address"], $val["receive_mail_pop3"], $val["receive_mail_user_id"], $val["receive_mail_user_pw"], $val["receive_mail_apop"], $val["access_time"], $val["send_mail_address"], $val["mobile_category"], $val["mobile_comment_ok"], $val["mobile_trackback_ok"], $val["information_mail_address"], $val["information_comment"], $val["information_trackback"], $val["user_mail_address"], $val["br_change"]);

			$sub = "[BlognPlus]新しいパスワードの発行";
			$sub = blogn_mbConv($sub, 4, 3);
			$sub = "=?iso-2022-jp?B?".base64_encode($sub)."?="; 
			$mes = "新しいパスワードを発行しました。\n\n"; 
			$mes .= "User ID: ".$val["id"]."\n\n";
			$mes .= $password."\n";
			$mes .= "--------\n\n";
			$mes .= "ログイン後はパスワードの変更をお勧めいたします。\n\n";
			$mes .= "※このメールアドレスには返信しないでください。"; 
			$mes = blogn_mbConv($mes, 4, 3); 
			$from = BLOGN_SITENAME; 
			$from = blogn_mbConv($from, 4, 3); 
			$from = "=?iso-2022-jp?B?".base64_encode($from)."?="; 
			$from = "From: $from <blognplus@localhost>\nContent-Type: text/plain; charset=\"iso-2022-jp\""; 
			mail($mailaddress, $sub, $mes, $from); 
			break;
		}
	}
	if ($find) {
		$error = "新しいパスワードをメールで送信しました。";
	}else{
		$error = "入力されたメールアドレスは登録されていません。";
	}
	return $error;
}
//-------------------------------------------------------------------- ヘッダ＆フッタ表示

/* ----- 管理画面ヘッダ表示 ----- */
function blogn_admin_menu_header($mode = 0, $admin = "false", $java_type = 0) {

	$blogn_skin = file("./template/header.html");
	$blogn_skin = implode("",$blogn_skin);

	for ($i = 0; $i > 16; $i++) {
		$blogn_here[$i] = "";
	}
	$blogn_here[$mode] = ' id="here"';

	if ($java_type) {
		$blogn_skin = str_replace ("{BLOGN_JS_ON}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_JS_ON}", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_JS_ON\}[\w\W]+?\{\/BLOGN_JS_ON\}/", "", $blogn_skin);
	}

	if ($java_type == 1) {
		$blogn_skin = str_replace ("{BLOGN_JS_TYPE_1}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_JS_TYPE_1}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_JS_TYPE_2\}[\w\W]+?\{\/BLOGN_JS_TYPE_2\}/", "", $blogn_skin);
	}elseif ($java_type == 2) {
		$blogn_skin = preg_replace("/\{BLOGN_JS_TYPE_1\}[\w\W]+?\{\/BLOGN_JS_TYPE_1\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_JS_TYPE_2}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_JS_TYPE_2}", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_JS_TYPE_1\}[\w\W]+?\{\/BLOGN_JS_TYPE_1\}/", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_JS_TYPE_2\}[\w\W]+?\{\/BLOGN_JS_TYPE_2\}/", "", $blogn_skin);
	}

	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_TOP}", $blogn_here[BLOGN_ACTION_MODE_TOP], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_NEW}", $blogn_here[BLOGN_ACTION_MODE_NEW], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_LIST}", $blogn_here[BLOGN_ACTION_MODE_LIST], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_FILES}", $blogn_here[BLOGN_ACTION_MODE_FILES], $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_CATEGORY}", $blogn_here[BLOGN_ACTION_MODE_CATEGORY], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_LINK}", $blogn_here[BLOGN_ACTION_MODE_LINK], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_PING}", $blogn_here[BLOGN_ACTION_MODE_PING], $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_PROFILE}", $blogn_here[BLOGN_ACTION_MODE_PROFILE], $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_USER}", $blogn_here[BLOGN_ACTION_MODE_USER], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_INIT}", $blogn_here[BLOGN_ACTION_MODE_INIT], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_DATA}", $blogn_here[BLOGN_ACTION_MODE_DATA], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_SKINMAKE}", $blogn_here[BLOGN_ACTION_MODE_SKINMAKE], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_SKINFILE}", $blogn_here[BLOGN_ACTION_MODE_SKINFILE], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_SKINSET}", $blogn_here[BLOGN_ACTION_MODE_SKINSET], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_MODULE}", $blogn_here[BLOGN_ACTION_MODE_MODULE], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_SECURITY}", $blogn_here[BLOGN_ACTION_MODE_SECURITY], $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_ACTION_MODE_LOGOUT}", $blogn_here[BLOGN_ACTION_MODE_LOGOUT], $blogn_skin);

	if ($admin) {
		$blogn_skin = str_replace("{BLOGN_ADMIN}", "", $blogn_skin);
		$blogn_skin = str_replace("{/BLOGN_ADMIN}", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_ADMIN\}[\w\W]+?\{\/BLOGN_ADMIN\}/", "", $blogn_skin);
	}

	$blogn_skin = str_replace ("{BLOGN_VERSION}", BLOGN_VERSION, $blogn_skin);

	header("Content-Type: text/html; charset=UTF-8");
	echo $blogn_skin;
}

/* ----- 管理画面フッタ表示 ----- */
function blogn_admin_menu_footer() {
	$blogn_skin = file("./template/footer.html");
	$blogn_skin = implode("",$blogn_skin);
	echo $blogn_skin;
}


//-------------------------------------------------------------------- インフォメーションバー表示
function blogn_information_bar($flag, $information){
	// $flag : true = 正常
	//         false = エラー
	// $information : インフォメーションメッセージ
	$echo_val = '<div class="blogn_information">';
	if ($flag) {
		$echo_val .= '<font color="blue">◎ </font>';
	}else{
		$echo_val .= '<font color="red">× </font>';
	}
	$echo_val .= $information.'</div>';
	return $echo_val;
}


//-------------------------------------------------------------------- 絵文字処理ツール表示
function blogn_icon_toolbar($mode) {

	$icon = file(BLOGN_INIDIR."icon.cgi");
	$echo_val = '
<table summary="絵文字" style="margin:0px;padding:5px;">
<tr><td>';
	for ($i = 0; $i < 100; $i++) {
		$icon[$i] = ereg_replace( "\n$", "", $icon[$i] );
		$icon[$i] = ereg_replace( "\r$", "", $icon[$i] );
		list($i_pic, $i_data) = explode("<>", $icon[$i]);
		$size = @getimagesize(BLOGN_ICONDIR.$i_pic);
		$echo_val .= "<img src=\"".BLOGN_ICONDIR.$i_pic."\" onclick=\"icon('$i_data','$mode')\" onkeypress=\"icon('$i_data','$mode')\" ".$size[3]." alt=\"$i_data\" style=\"border:0px;\">\n";
	}
	$echo_val .= "</td></tr></table>";
	return $echo_val;
}

//-------------------------------------------------------------------- タグ処理ツール表示
function blogn_tag_toolbar($mode, $iconmode) {
	$font_size = array("7","8","9","10","12","14","16","18","20","22","24","26","28","30","36","40");
	$echo_val = "<div style=\"margin:0px;padding:5px 0px 5px 0px;\">\n
	<table summary=\"タグ\" style=\"background-color: #b5b5b5;\">\n
	<tr><td>\n";

	if ($mode == 0) {
		$echo_val .= "<select name=\"font_profile\" onChange=\"ins(0,'',$mode)\" title=\"文字サイズ\">\n";
	}elseif ($mode == 1) {
		$echo_val .= "<select name=\"font_mes\" onChange=\"ins(0,'',$mode)\" title=\"文字サイズ\">\n";
	}elseif ($mode == 2) {
		$echo_val .= "<select name=\"font_more\" onChange=\"ins(0,'',$mode)\" title=\"文字サイズ\">\n";
	}

	while(list($key, $val) = each($font_size)) {
		if ($val == 12) {
			$echo_val .= "<option value=\"$val\" selected>$val</option>\n";
		}else{
			$echo_val .= "<option value=\"$val\">$val</option>\n";
		}
	}
	$echo_val .= "
</select>
</td><td><img src=\"./images/blank.gif\" width=\"4\" height=\"1\" alt=\"\"></td>
<td><img src=\"./images/blank.gif\" onclick=\"ins(1,0,$mode)\" onkeypress=\"ins(1,0,$mode)\" style=\"background-color:black;width:15px;height:15px;border:1px solid #000;\" alt=\"黒\"></td>\n
<td><img src=\"./images/blank.gif\" onclick=\"ins(1,1,$mode)\" onkeypress=\"ins(1,1,$mode)\" style=\"background-color:brown;width:15px;height:15px;border:1px solid #000;\" alt=\"茶\"></td>\n
<td><img src=\"./images/blank.gif\" onclick=\"ins(1,2,$mode)\" onkeypress=\"ins(1,2,$mode)\" style=\"background-color:red;width:15px;height:15px;border:1px solid #000;\" alt=\"赤\"></td>\n
<td><img src=\"./images/blank.gif\" onclick=\"ins(1,3,$mode)\" onkeypress=\"ins(1,3,$mode)\" style=\"background-color:orange;width:15px;height:15px;border:1px solid #000;\" alt=\"橙\"></td>\n
<td><img src=\"./images/blank.gif\" onclick=\"ins(1,4,$mode)\" onkeypress=\"ins(1,4,$mode)\" style=\"background-color:yellow;width:15px;height:15px;border:1px solid #000;\" alt=\"黄\"></td>\n
<td><img src=\"./images/blank.gif\" onclick=\"ins(1,5,$mode)\" onkeypress=\"ins(1,5,$mode)\" style=\"background-color:green;width:15px;height:15px;border:1px solid #000;\" alt=\"緑\"></td>\n
<td><img src=\"./images/blank.gif\" onclick=\"ins(1,6,$mode)\" onkeypress=\"ins(1,6,$mode)\" style=\"background-color:blue;width:15px;height:15px;border:1px solid #000;\" alt=\"青\"></td>\n
<td><img src=\"./images/blank.gif\" onclick=\"ins(1,7,$mode)\" onkeypress=\"ins(1,7,$mode)\" style=\"background-color:violet;width:15px;height:15px;border:1px solid #000;\" alt=\"紫\"></td>\n
<td><img src=\"./images/blank.gif\" onclick=\"ins(1,8,$mode)\" onkeypress=\"ins(1,8,$mode)\" style=\"background-color:gray;width:15px;height:15px;border:1px solid #000;\" alt=\"灰\"></td>\n
<td><img src=\"./images/blank.gif\" onclick=\"ins(1,9,$mode)\" onkeypress=\"ins(1,9,$mode)\" style=\"background-color:white;width:15px;height:15px;border:1px solid #000;\" alt=\"白\"></td>\n
<td><img src=\"./images/blank.gif\" width=\"4\" height=\"1\" alt=\"\"></td>\n
<td><img src=\"./images/b.gif\" onclick=\"ins(2,0,$mode)\" onkeypress=\"ins(2,0,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"太字\"></td>\n
<td><img src=\"./images/i.gif\" onclick=\"ins(2,1,$mode)\" onkeypress=\"ins(2,1,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"斜体\"></td>\n
<td><img src=\"./images/u.gif\" onclick=\"ins(2,2,$mode)\" onkeypress=\"ins(2,2,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"下線\"></td>\n
<td><img src=\"./images/s.gif\" onclick=\"ins(2,3,$mode)\" onkeypress=\"ins(2,3,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"取消線\"></td>\n
<td><img src=\"./images/blank.gif\" width=\"4\" height=\"1\" alt=\"\"></td>\n
<td><img src=\"./images/left.gif\" onclick=\"ins(2,4,$mode)\" onkeypress=\"ins(2,4,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"左揃え\"></td>\n
<td><img src=\"./images/center.gif\" onclick=\"ins(2,5,$mode)\" onkeypress=\"ins(2,5,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"中央揃え\"></td>\n
<td><img src=\"./images/right.gif\" onclick=\"ins(2,6,$mode)\" onkeypress=\"ins(2,6,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"右揃え\"></td>\n
<td><img src=\"./images/p.gif\" onclick=\"ins(2,7,$mode)\" onkeypress=\"ins(2,7,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"段落\"></td>\n
<td><img src=\"./images/blockquote.gif\" onclick=\"ins(2,8,$mode)\" onkeypress=\"ins(2,8,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"引用文\"></td>\n
<td><img src=\"./images/pre.gif\" onclick=\"ins(2,9,$mode)\" onkeypress=\"ins(2,9,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"そのまま表示\"></td>\n
<td><img src=\"./images/blank.gif\" width=\"4\" height=\"1\" alt=\"\"></td>\n
<td><img src=\"./images/lt.gif\" onclick=\"icon('&amp;lt;',$mode)\" onkeypress=\"icon('&amp;lt;',$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"&amp;lt;\"></td>\n
<td><img src=\"./images/gt.gif\" onclick=\"icon('&amp;gt;',$mode)\" onkeypress=\"icon('&amp;gt;',$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"&amp;gt;\"></td>\n
<td><img src=\"./images/blank.gif\" width=\"4\" height=\"1\" alt=\"\"></td>\n
<td><img src=\"./images/link.gif\" onclick=\"linkins(0,$mode)\" onkeypress=\"linkins(0,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"リンク\"></td>\n
<td><img src=\"./images/mail.gif\" onclick=\"linkins(1,$mode)\" onkeypress=\"linkins(1,$mode)\" class=\"blogn_image_tag\" width=\"16\" height=\"16\" alt=\"メール\"></td>\n
";
if ($mode == 0) {
	$echo_val .= '<td><img src="./images/pict.gif" onclick="wopen_pict_profile()" onkeypress="wopen_pict_profile()" class="blogn_image_tag" width="16" height="16" alt="画像"></td>';
}else{
	$echo_val .= '<td><img src="./images/pict.gif" onclick="wopen_pict_message()" onkeypress="wopen_pict_message()" class="blogn_image_tag" width="16" height="16" alt="画像"></td>';
}
if ($iconmode) {
	if ($mode == 0) {
		$echo_val .= '<td><img src="./images/icon.gif" onclick="wopen_icon_profile()" onkeypress="wopen_icon_profile()" class="blogn_image_tag" width="16" height="16" alt="絵文字"></td>';
	}else{
		$echo_val .= '<td><img src="./images/icon.gif" onclick="wopen_icon_message()" onkeypress="wopen_icon_message()" class="blogn_image_tag" width="16" height="16" alt="絵文字"></td>';
	}
}
$echo_val .= '
</tr>
</table>
</div>
	';
	return $echo_val;
}


//-------------------------------------------------------------------- \r\n → <br> 変換
function blogn_br_change($val) {
	$val = str_replace( "\r\n",  "\n", $val);		// 改行を統一する
	$val = nl2br($val);													// 改行文字の前に<br>を代入する
	$val = str_replace("\n",  "", $val);				// \nを文字列から消す。
	return $val;
}


//-------------------------------------------------------------------- <br> → \r\n 変換
function blogn_rn_change($val) {
	$val = str_replace( "<br>",  "\n", $val);		// 改行を統一する
	$val = str_replace( "<br />",  "\n", $val);		// 改行を統一する
	return $val;
}


//-------------------------------------------------------------------- 管理画面TOP表示

/* ----- TOPメニュー ----- */
function blogn_top_view($admin){

	$blogn_skin = file("./template/top.html");
	$blogn_skin = implode("",$blogn_skin);

	$release = date("Y年m月d日", filemtime("conf.php"));
	$totalentry = blogn_mod_db_log_count();
	$totalcomment = blogn_mod_db_comment_count();
	$totaltrackback = blogn_mod_db_trackback_count();

	$blogn_skin = str_replace ("{BLOGN_RELEASE}", $release, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_TOTAL_ENTRY}", $totalentry, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_TOTAL_COMMENT}", $totalcomment, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_TOTAL_TRACKBACK}", $totaltrackback, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_SITENAME}", BLOGN_SITENAME, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_SITEDESC}", BLOGN_SITEDESC, $blogn_skin);

	if (is_file("install.php")) {
		$blogn_skin = str_replace ("{BLOGN_INSTALL_FOUND}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_INSTALL_FOUND}", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_INSTALL_FOUND\}[\w\W]+?\{\/BLOGN_INSTALL_FOUND\}/", "", $blogn_skin);
	}
	if (is_file("update.php")) {
		$blogn_skin = str_replace ("{BLOGN_UPDATE_FOUND}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_UPDATE_FOUND}", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_UPDATE_FOUND\}[\w\W]+?\{\/BLOGN_UPDATE_FOUND\}/", "", $blogn_skin);
	}

	if (BLOGN_OS_TYPE) {
		if (is_writable("conf.php")) {
			$blogn_skin = str_replace ("{BLOGN_CONF_OVERWRITE}", "", $blogn_skin);
			$blogn_skin = str_replace ("{/BLOGN_CONF_OVERWRITE}", "", $blogn_skin);
		}else{
			$blogn_skin = preg_replace("/\{BLOGN_CONF_OVERWRITE\}[\w\W]+?\{\/BLOGN_CONF_OVERWRITE\}/", "", $blogn_skin);
		}
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_CONF_OVERWRITE\}[\w\W]+?\{\/BLOGN_CONF_OVERWRITE\}/", "", $blogn_skin);
	}

	$blogn_skin = str_replace ("{BLOGN_VERSION}", BLOGN_VERSION, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_DB_TYPE}", BLOGN_DB_TYPE, $blogn_skin);

	echo $blogn_skin;
}


//-------------------------------------------------------------------- プレビュー表示

/* ----- プレビュー表示 ----- */
function blogn_preview($val){
	$val = blogn_IconStr($val);
	return $val;
}


//-------------------------------------------------------------------- 新規投稿表示
/* ----- 記事の入力、更新 ----- */
function blogn_blog_new($admin = "false", $action = "", $user_key, $new_post, $ent_id, $ent_year, $ent_month, $ent_day, $ent_hour, $ent_minutes, $ent_second, $user_key, $reserve, $secret, $comment_ok, $trackback_ok, $category, $title, $mes, $more, $send_trackback, $send_ping_url, $br_change) {

	$blogn_skin = file("./template/new.html");
	$blogn_skin = implode("",$blogn_skin);


	// 日付チェック
	if ($action == "post" || $action == "update" || $action == "preview") {
		// 日付、時間の結合
		$ent_date = sprintf("%4d%02d%02d", $ent_year, $ent_month, $ent_day);
		$ent_time = sprintf("%02d%02d%02d", $ent_hour, $ent_minutes, $ent_second);
	}

	if (!$br_change) $br_change = 0;

	// 処理選択
	switch ($action) {
		case "post":			// ----- 新規投稿
			$mes = blogn_html_tag_restore($mes);
			$more = blogn_html_tag_restore($more);

			$title = blogn_magic_quotes($title);				//￥を削除
			$mes = blogn_magic_quotes($mes);						//￥を削除
			$more = blogn_magic_quotes($more);					//￥を削除

			if (!$br_change) {
				$mes = blogn_rn2rntag($mes);
				$more = blogn_rn2rntag($more);
			}else{
				$mes = blogn_mod_db_rn2br($mes);
				$more = blogn_mod_db_rn2br($more);
			}
			if (!checkdate($ent_month, $ent_day, $ent_year)) {
				$error[0] = false;
				$error[1] = "日付入力ミス。投稿年月日を確認してください。";
			}else{
				$table = "log";
				$date = sprintf("%4d%02d%02d%02d%02d%02d", $ent_year, $ent_month, $ent_day, $ent_hour, $ent_minutes, $ent_second);
				$error = blogn_mod_db_log_add($user_key, $date, $reserve, $secret, $comment_ok, $trackback_ok, $category, $title, $mes, $more, $br_change);
				$logid = $error[2];
			}
			if (!$br_change) {
				$mes = blogn_rntag2rn($mes);
				$more = blogn_rntag2rn($more);
			}

			//trackback送信
			if (strlen($send_trackback) != 0) {
				$tb = blogn_trackback_send($send_trackback,BLOGN_HOMELINK."?e=".$logid, $title, $mes);
				$error[1] .= $tb;
			}
			//ping送信
			if (count($send_ping_url) != 0) {
				$pu = blogn_ping_send($send_ping_url);
				$error[1] .= $pu;
			}

			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "edit":		// ----- 編集表示
			$error = blogn_mod_db_log_load_for_editor($ent_id);
			if (!$error[0]) {
				// インフォメーション表示
				echo blogn_information_bar($error[0], $error[1]);
			}else{
				$logid = $error[1]["id"];
				$date = $error[1]["date"];
				$reserve = $error[1]["reserve"];
				$secret = $error[1]["secret"];
				$user_id = $error[1]["user_id"];
				$category = $error[1]["category"];
				$comment_ok = $error[1]["comment_ok"];
				$trackback_ok = $error[1]["trackback_ok"];
				$title = blogn_magic_quotes($error[1]["title"]);				//￥を削除
				$mes = blogn_magic_quotes($error[1]["mes"]);				//￥を削除
				$more = blogn_magic_quotes($error[1]["more"]);				//￥を削除
				$mes = blogn_html_tag_restore($mes);
				$more = blogn_html_tag_restore($more);
				$br_change = $error[1]["br_change"];
				if (!$br_change) {
					$mes = blogn_rntag2rn($mes);
					$more = blogn_rntag2rn($more);
				}
			}
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
		case "update":	// ----- 更新投稿
			$mes = blogn_html_tag_restore($mes);
			$more = blogn_html_tag_restore($more);

			$title = blogn_magic_quotes($title);				//￥を削除
			$mes = blogn_magic_quotes($mes);				//￥を削除
			$more = blogn_magic_quotes($more);				//￥を削除
			if (!$br_change) {
				$mes = blogn_rn2rntag($mes);
				$more = blogn_rn2rntag($more);
			}else{
				$mes = blogn_mod_db_rn2br($mes);
				$more = blogn_mod_db_rn2br($more);
			}
			if (!checkdate($ent_month, $ent_day, $ent_year)) {
				$error[0] = false;
				$error[1] = "日付入力ミス。投稿年月日を確認してください。";
			}else{
				$table = "log";
				$logid = $ent_id;
				$date = sprintf("%4d%02d%02d%02d%02d%02d", $ent_year, $ent_month, $ent_day, $ent_hour, $ent_minutes, $ent_second);
				$error = blogn_mod_db_log_change($logid, $date, $reserve, $secret, $comment_ok, $trackback_ok, $category, $title, $mes, $more, $br_change);
			}
			if (!$br_change) {
				$mes = blogn_rntag2rn($mes);
				$more = blogn_rntag2rn($more);
			}

			//trackback送信
			if (strlen($send_trackback) != 0) {
				$tb = blogn_trackback_send($send_trackback,BLOGN_HOMELINK."?e=".$logid, $title, $mes);
				$error[1] .= $tb;
			}
			//ping送信
			if (count($send_ping_url) != 0) {
				$pu = blogn_ping_send($send_ping_url);
				$error[1] .= $pu;
			}

			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "preview":	// ----- プレビュー
			if ($ent_id) {
				$logid = $ent_id;
				$newpost = "old";
			}
			$title = blogn_magic_quotes($title);				//￥を削除
			$mes = blogn_magic_quotes($mes);						//￥を削除
			$more = blogn_magic_quotes($more);					//￥を削除
			if ($br_change) {
				$mes = blogn_mod_db_rn2br($mes);
				$more = blogn_mod_db_rn2br($more);
			}
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
		default:				// ----- 新規表示
			$br_change = 1;
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}
	$send_trackback = "";
	$userdata = blogn_mod_db_user_profile_load($user_key);

	if (!$action) $action = "new";
	$preview_mes = blogn_permit_html_tag_restore($mes);
	$preview_more = blogn_permit_html_tag_restore($more);

	if ($action == "post" || $action == "edit" || $action == "update" || $action == "preview") {
		$blogn_skin = str_replace ("{BLOGN_ACTION_PEUP}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_ACTION_PEUP}", "", $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_PREVIEW_TITLE}", blogn_preview($title), $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_PREVIEW_MES}", blogn_preview($preview_mes), $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_PREVIEW_MORE}", blogn_preview($preview_more), $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_ACTION_PEUP\}[\w\W]+?\{\/BLOGN_ACTION_PEUP\}/", "", $blogn_skin);
	}

	if ($action == "post" || $action == "edit" || $action == "update" || $newpost == "old") {
		$blogn_skin = str_replace ("{BLOGN_ACTION_PEUO}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_ACTION_PEUO_ELSE\}[\w\W]+?\{\/BLOGN_ACTION_PEUO\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_LOG_ID}", $logid, $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_ACTION_PEUO\}[\w\W]+?\{BLOGN_ACTION_PEUO_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_ACTION_PEUO}", "", $blogn_skin);
	}

	$category1 = blogn_mod_db_category1_load();
	$category2 = blogn_mod_db_category2_load();

	$echo_category = "";
	if ($category1[0]) {
		@reset($category1[1]);
		while (list($c1key, $c1val) = each($category1[1])) {
			if (($action == "new" && $userdata["init_category"] == $c1key."|") || $category == $c1key."|") {
				$select = " selected";
			}else{
				$select = "";
			}
			$c1name = get_magic_quotes_gpc() ? stripslashes($c1val["name"]) : $c1val["name"];				//￥を削除
			$c1name = htmlspecialchars($c1name);
			$echo_category .= "<option value=\"$c1key|\"$select>$c1name</option>\n";
			@reset($category2[1]);
			while (list($c2key, $c2val) = @each($category2[1])) {
				if ($c1key == $c2val["id"]) {
					if (($action == "new" && $userdata["init_category"] == $c1key."|".$c2key) || $category == $c1key."|".$c2key) {
						$select = " selected";
					}else{
						$select = "";
					}
					$c2name = get_magic_quotes_gpc() ? stripslashes($c2val["name"]) : $c2val["name"];				//￥を削除
					$c2name = htmlspecialchars($c2name);
					$echo_category .= "<option value=\"$c1key|$c2key\"$select>└$c1name::$c2name</option>\n";
				}
			}
		}
	}

	$blogn_skin = str_replace ("{BLOGN_CATEGORY}", $echo_category, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_TITLE}", htmlspecialchars($title), $blogn_skin);


	if ($userdata["init_icon_ok"]) {
		$blogn_skin = str_replace ("{BLOGN_ICON_TOOLBAR_1}", blogn_icon_toolbar(1), $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_TAG_TOOLBAR_1}", blogn_tag_toolbar(1,0), $blogn_skin);
	}else{
		$blogn_skin = str_replace ("{BLOGN_ICON_TOOLBAR_1}", "", $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_TAG_TOOLBAR_1}", blogn_tag_toolbar(1,1), $blogn_skin);
	}

	if ($br_change) {
		$textarea_mes = blogn_rn_change($mes);
	}else{
		$textarea_mes = $mes;
	}
	$blogn_skin = str_replace ("{BLOGN_MES}", blogn_html_tag_convert($textarea_mes), $blogn_skin);

	if ($userdata["init_icon_ok"]) {
		$blogn_skin = str_replace ("{BLOGN_ICON_TOOLBAR_2}", blogn_icon_toolbar(2), $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_TAG_TOOLBAR_2}", blogn_tag_toolbar(2,0), $blogn_skin);
	}else{
		$blogn_skin = str_replace ("{BLOGN_ICON_TOOLBAR_2}", "", $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_TAG_TOOLBAR_2}", blogn_tag_toolbar(2,1), $blogn_skin);
	}

	if ($br_change) {
		$textarea_more = blogn_rn_change($more);
	}else{
		$textarea_more = $more;
	}
	$blogn_skin = str_replace ("{BLOGN_MORE}", blogn_html_tag_convert($textarea_more), $blogn_skin);

	if ($action == "new") {
		$ent_year = gmdate ("Y", time() + BLOGN_TIMEZONE);
		$ent_month = gmdate ("m", time() + BLOGN_TIMEZONE);
		$ent_day = gmdate ("d", time() + BLOGN_TIMEZONE);
		$ent_hour = gmdate ("H", time() + BLOGN_TIMEZONE);
		$ent_minutes = gmdate ("i", time() + BLOGN_TIMEZONE);
		$ent_second = gmdate ("s", time() + BLOGN_TIMEZONE);
	}elseif ($action == "edit") {
		$ent_year =  substr($date, 0, 4);
		$ent_month = substr($date, 4, 2);
		$ent_day = substr($date, 6, 2);
		$ent_hour = substr($date, 8, 2);
		$ent_minutes = substr($date, 10, 2);
		$ent_second = substr($date, 12, 2);
	}

	$oldyear = 1970;
	$newyear = gmdate ("Y", time() + BLOGN_TIMEZONE) + 1;

	if ($reserve) {
		$check = " checked";
	}else{
		$check = "";
	}
	$blogn_skin = str_replace ("{BLOGN_RESERVE_CHECK}", $check, $blogn_skin);

	if ($br_change) {
		$check = " checked";
	}else{
		$check = "";
	}
	$blogn_skin = str_replace ("{BLOGN_BR_CHECK}", $check, $blogn_skin);



	$echo_val = "";
	for ( $i = $newyear; $i >= $oldyear; $i-- ) {
		if ($ent_year == $i) {
			$echo_val .= "<option value=\"$i\" selected>$i</option>\n";
		}else{
			$echo_val .= "<option value=\"$i\">$i</option>\n";
		}
	}
	$blogn_skin = str_replace ("{BLOGN_ENT_YEAR}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ( $i = 1; $i <= 12; $i++ ) {
		if ($ent_month == $i) {
			$echo_val .= "<option value=\"$i\" selected>$i</option>\n";
		}else{
			$echo_val .= "<option value=\"$i\">$i</option>\n";
		}
	}
	$blogn_skin = str_replace ("{BLOGN_ENT_MONTH}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ( $i = 1; $i <= 31; $i++ ) {
		if ($ent_day == $i) {
			$echo_val .= "<option value=\"$i\" selected>$i</option>\n";
		}else{
			$echo_val .= "<option value=\"$i\">$i</option>\n";
		}
	}
	$blogn_skin = str_replace ("{BLOGN_ENT_DAY}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ( $i = 0; $i <= 23; $i++ ) {
		if ($ent_hour == $i) {
			$echo_val .= "<option value=\"$i\" selected>$i</option>\n";
		}else{
			$echo_val .= "<option value=\"$i\">$i</option>\n";
		}
	}
	$blogn_skin = str_replace ("{BLOGN_ENT_HOUR}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ( $i = 0; $i <= 59; $i++ ) {
		if ($ent_minutes == $i) {
			$echo_val .= "<option value=\"$i\" selected>$i</option>\n";
		}else{
			$echo_val .= "<option value=\"$i\">$i</option>\n";
		}
	}
	$blogn_skin = str_replace ("{BLOGN_ENT_MINUTES}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ( $i = 0; $i <= 59; $i++ ) {
		if ($ent_second == $i) {
			$echo_val .= "<option value=\"$i\" selected>$i</option>\n";
		}else{
			$echo_val .= "<option value=\"$i\">$i</option>\n";
		}
	}
	$blogn_skin = str_replace ("{BLOGN_ENT_SECOND}", $echo_val, $blogn_skin);


	$comment_check = $trackback_check = $secret_check = "";
	if (($action == "new" && $userdata["init_comment_ok"]) || $comment_ok) {
		$comment_check = " checked";
	}
	if (($action == "new" && $userdata["init_trackback_ok"]) || $trackback_ok) {
		$trackback_check = " checked";
	}
	if ($secret) {
		$secret_check = " checked";
	}

	$blogn_skin = str_replace ("{BLOGN_COMMENT_CHECK}", $comment_check, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_TRACKBACK_CHECK}", $trackback_check, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_SECRET_CHECK}", $secret_check, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_SEND_TRACKBACK}", $send_trackback, $blogn_skin);

	$echo_val = "";
	$pinglist = blogn_mod_db_ping_load();
	if ($pinglist[0]) {
		while(list($key, $val) = each($pinglist[1])) {
			if ($action == "post" || $action == "edit" || $action == "update" || $newpost == "old") {
				$check = "";
			}else{
				if ($val["default"]) {
					$check = " checked";
				}else{
					$check = "";
				}
			}
			$echo_val .= "<input type=\"checkbox\" name=\"blogn_send_ping_url[]\" value=\"".$val["url"]."\" id=\"ping$key\" $check><label for=\"ping$key\">".$val["name"]."</label>\n
		 ( <a href=\"".$val["url"]."\" target=\"_blank\">".$val["url"]."</a> )<br>\n";
		}
	}else{
		$echo_val .= "登録されているPing URLはありません。";
	}

	$blogn_skin = str_replace ("{BLOGN_SEND_PING}", $echo_val, $blogn_skin);

	echo $blogn_skin;
}


/* トラックバック送信 */
function blogn_trackback_send($ping_url, $client_url, $title, $excerpt) {
	$return_code = "";
	$blog_name = BLOGN_SITENAME;
		// 改行文字の統一。 
	$excerpt = str_replace( "<br>",  "\n", $excerpt); 
	$excerpt = str_replace( "<br />",  "\n", $excerpt);

	$excerpt = blogn_CleanHtml($excerpt);

	if (strlen($excerpt) > 255) $excerpt = blogn_mbtrim($excerpt,255);	// Movable Type仕様。255バイト以上の場合省略
	$post = "title=".urlencode($title)."&url=".urlencode($client_url)."&blog_name=".urlencode($blog_name)."&excerpt=".urlencode($excerpt)."&charset=utf-8";

	$ping_url = str_replace("\r$", "", $ping_url);
	$ping_url = ereg_replace("\n$", "", $ping_url);
	$ping_urls = explode("\n", $ping_url);
	$return_code = "\n";
	for ($i = 0; $i < count($ping_urls); $i++) {
		$cnt = $i + 1;
		$ping = parse_url(trim($ping_urls[$i]));
		$req  = "POST ".$ping_urls[$i]." HTTP/1.0\n";
		$req .= "Host: ".$ping['host']."\n";
 		$req .= "User-Agent: PHP/".phpversion()."\n";
		$req .= "Content-Type: application/x-www-form-urlencoded\n";
		$req .= "Content-Length: ".strlen($post)."\n";
		$req .= "Accept: */*\n";
		$req .= "\n";
		$req .= $post."\n";
		if (!$ping["port"]) $ping["port"] = 80;
		$fp = @fsockopen($ping["host"], $ping["port"], $errno, $errstr, 10);
		if ($fp) {
			fputs ($fp, $req);
			$res = "";
			while (!feof($fp)) {
				$res .= fgets($fp, 4096);
			}
			fclose($fp);
			$str = blogn_mbConv($res, 0, 4);
			if(preg_match("/<error>0<\/error>/",$str)){
				$return_code .= "トラックバック送信".$cnt."件目：送信完了\n";
			}else{
//				$req  = "GET ".trim($ping_urls[$i])."?".$post. " HTTP/1.0\n";
//				$req .= "Host: ".$ping['host']."\n";
//    		$req .= "User-Agent: PHP/".phpversion()."\n\n";
//				$fp = @fsockopen($ping["host"], $ping["port"], $errno, $errstr, 10);
//				$req = trim($ping_urls[$i])."?".$post;
//				$fp = @fopen($req, "r");
//				if ($fp) {
//					fputs ($fp, $req);
//					$res = "";
//					while (!feof($fp)) {
//						$res .= fgets($fp, 4096);
//					}
//					fclose($fp);
//					$str = $res;
//					if(preg_match("/<error>0<\/error>/",$str)){
//						$return_code .= "トラックバック送信".$cnt."件目：送信完了\n";
//					}
//				}else{
				$return_code .= "トラックバック送信".$cnt."件目：送信エラー\n";
//				}
			}
		}else{
			$return_code .= "トラックバック送信".$cnt."件目：接続エラー\n";
		}
	}
	return $return_code;

}


/* weblogUpdates.ping 送信 */
function blogn_ping_send($ping_url){
	$return_code = "\n";
	$blog_name = BLOGN_SITENAME;
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
<value>'.BLOGN_HOMELINK.'</value>
</param>
</params>
</methodCall>';
		$post = blogn_mbConv($post,0,4);
		$ping = parse_url($ping_url[$i]);
		$req  = "POST ".$ping_url[$i]." HTTP/1.0\n";
		$req .= "Host: ".$ping['host']."\n";
		$req .= "User-Agent:blogn-send-ping\n";
		$req .= "Content-Type: text/xml\n";
		$req .= "Content-Length: ".strlen($post)."\n";
		$req .= "\n";
		$req .= $post."\n";
		$fp = @fsockopen($ping["host"], $port, $errno, $errstr, 10);
		if ($fp) {
			fputs ($fp, $req);
			while (!feof($fp)) {
				$res = fgets($fp, 4096);
			}
			fclose($fp);
			$return_code .= "Ping送信".$cnt."件目：送信完了\n";
		}else{
			$return_code .= "Ping送信".$cnt."件目：接続エラー\n";
		}
	}
	return $return_code;
}


//-------------------------------------------------------------------- 記事一覧管理

/* ----- 記事一覧管理表示 ----- */
function blogn_blog_list_control($admin, $action, $page, $id, $user_id, $category, $log_title, $log_category) {
	$blogn_skin = file("./template/list.html");
	$blogn_skin = implode("",$blogn_skin);

	if (!$page) $page = 1;
	// 処理選択
	switch ($action) {
		case "log_delete":			// ----- 記事・コメント・トラックバック削除
			$error = blogn_mod_db_log_delete($id);
			if ($error) {
				$error1 = blogn_mod_db_log_comment_delete($id);
				$error2 = blogn_mod_db_log_trackback_delete($id);
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "log_select_delete":			// ----- 選択した記事・コメント・トラックバックの全削除
			while(list($key, $val) = each($id)) {
				$error = blogn_mod_db_log_delete($val);
				if ($error) {
					$error1 = blogn_mod_db_log_comment_delete($val);
					$error2 = blogn_mod_db_log_trackback_delete($val);
				}
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "log_select_change":
			while(list($key, $val) = each($id)) {
				$error = blogn_mod_db_log_title_change($val, $log_category[$key], $log_title[$key]);
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "update":
			$error = blogn_mod_db_log_title_change($id, $log_category, $log_title);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	// 1ページの表示数
	$pagecount = 10;

	$echo_val = "";
	if ($category == "") {
		$select = " selected";
	}else{
		$select = "";
	}
	$echo_val .= "<option value=\"\"{$select}>*** 全ての記事を表示する ***</option>\n";

	$category1 = blogn_mod_db_category1_load();
	$category2 = blogn_mod_db_category2_load();

	if ($category1[0]) {
		reset($category1[1]);
		while (list($c1key, $c1val) = each($category1[1])) {
			if ($category == $c1key."-") {
				$select = " selected";
			}else{
				$select = "";
			}
			$c1name = get_magic_quotes_gpc() ? stripslashes($c1val["name"]) : $c1val["name"];				//￥を削除
			$c1name = htmlspecialchars($c1name);
			$echo_val .= "<option value=\"{$c1key}-\"{$select}>{$c1name}</option>\n";
			@reset($category2[1]);
			while (list($c2key, $c2val) = @each($category2[1])) {
				if ($c1key == $c2val["id"]) {
					if ($category == $c1key."-".$c2key) {
						$select = " selected";
					}else{
						$select = "";
					}
					$c2name = get_magic_quotes_gpc() ? stripslashes($c2val["name"]) : $c2val["name"];				//￥を削除
					$c2name = htmlspecialchars($c2name);
					$echo_val .= "<option value=\"{$c1key}-{$c2key}\"{$select}>└{$c1name}::{$c2name}</option>\n";
				}
			}
		}
	}
	$blogn_skin = str_replace ("{BLOGN_CATEGORIES}", $echo_val, $blogn_skin);


	$start_key = ($page - 1) * $pagecount;

	$userlist = blogn_mod_db_user_load();
	if ($category) {
		$loglist = blogn_mod_db_log_load_list_for_category($user_id, $start_key, $pagecount, $category);
	}else{
		$loglist = blogn_mod_db_log_load_for_list($user_id, $start_key, $pagecount);
	}

	$blogn_skin = str_replace ("{BLOGN_SELECT_CATEGORY}", $category, $blogn_skin);


	$log_cnt = 0;
	if ($loglist[0]) {
		preg_match("/\{BLOGN_LIST_LOOP\}([\w\W]+?)\{\/BLOGN_LIST_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_list_all = "";
		while (list($key, $val) = each($loglist[1])) {
			$log_cnt++;
			$blogn_list = $blogn_reg[0];
			$blogn_list = str_replace ("{BLOGN_LIST_LOOP}", "", $blogn_list);
			$blogn_list = str_replace ("{/BLOGN_LIST_LOOP}", "", $blogn_list);

			$blogn_list = str_replace ("{BLOGN_CHECK_CNT}", $log_cnt, $blogn_list);

			reset($userlist);
			while (list($user_key, $user_val) = each($userlist)) {
				if ($val["user_id"] == $user_key) {
					$user_name = $user_val["name"];
					break;
				}
			}

			$echo_val = "";
			if ($category1[0]) {
				reset($category1[1]);
				while (list($c1key, $c1val) = each($category1[1])) {
					if ($val["category"] == $c1key."|") {
						$select = " selected";
					}else{
						$select = "";
					}
					$c1name = get_magic_quotes_gpc() ? stripslashes($c1val["name"]) : $c1val["name"];				//￥を削除
					$c1name = htmlspecialchars($c1name);
					$echo_val .= "<option value=\"{$c1key}|\"{$select}>{$c1name}</option>\n";
					@reset($category2[1]);
					while (list($c2key, $c2val) = @each($category2[1])) {
						if ($c1key == $c2val["id"]) {
							if ($val["category"] == $c1key."|".$c2key) {
								$select = " selected";
							}else{
								$select = "";
							}
							$c2name = get_magic_quotes_gpc() ? stripslashes($c2val["name"]) : $c2val["name"];				//￥を削除
							$c2name = htmlspecialchars($c2name);
							$echo_val .= "<option value=\"{$c1key}|{$c2key}\"{$select}>└{$c1name}::{$c2name}</option>\n";
						}
					}
				}
			}

			$blogn_list = str_replace ("{BLOGN_CATEGORY}", $echo_val, $blogn_list);


			$cmtcount = blogn_mod_db_comment_count_load($val["id"]);
			if ($cmtcount[0]) {
				if ($cmtcount[1] == 0) {
					$cmt_count = '-';
				}else{
					$cmt_count = '<a href="admin.php?mode=list&amp;type=comment&amp;id='.$val["id"].'">'.$cmtcount[1].' 件</a>';
				}
			}else{
				$cmt_count = '-';
			}
			$trkcount = blogn_mod_db_trackback_count_load($val["id"]);
			if ($trkcount[0]) {
				if ($trkcount[1] == 0) {
					$trk_count = '-';
				}else{
					$trk_count = '<a href="admin.php?mode=list&amp;type=trackback&amp;id='.$val["id"].'">'.$trkcount[1].' 件</a>';
				}
			}else{
				$trk_count = '-';
			}
			$title = $val["title"] ? $val["title"] : "** タイトル無し **";
			$delref = "admin.php?mode=list&amp;category=".$category."&amp;action=log_delete&amp;id=".$val["id"];

			$blogn_list = str_replace ("{BLOGN_TITLE}", blogn_magic_quotes($title), $blogn_list);
			$blogn_list = str_replace ("{BLOGN_DATE}", date("Y/m/d H:i:s", mktime(substr($val["date"],8,2), substr($val["date"],10,2), substr($val["date"],12,2), substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4))), $blogn_list);
			$blogn_list = str_replace ("{BLOGN_ID}", $val["id"], $blogn_list);
			$blogn_list = str_replace ("{BLOGN_DELREF}", $delref, $blogn_list);
			$blogn_list = str_replace ("{BLOGN_USERNAME}", blogn_magic_quotes($user_name), $blogn_list);
			$blogn_list = str_replace ("{BLOGN_CMT_CNT}", $cmt_count, $blogn_list);
			$blogn_list = str_replace ("{BLOGN_TRK_CNT}", $trk_count, $blogn_list);
			$blogn_list_all .= $blogn_list;
		}
		$blogn_skin = str_replace ("{BLOGN_LOG_CNT}", $log_cnt, $blogn_skin);

		$blogn_skin = preg_replace("/\{BLOGN_LIST_LOOP\}[\w\W]+?\{\/BLOGN_LIST_LOOP\}/", $blogn_list_all, $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_LIST}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_LIST_ELSE\}[\w\W]+?\{\/BLOGN_LIST\}/", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_LIST\}[\w\W]+?\{BLOGN_LIST_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_LIST}", "", $blogn_skin);
	}

	// ページ処理
	if ($loglist[2] != 0) {
		$max_page = ceil($loglist[2] / $pagecount);
	}else{
		$max_page = 0;
	}

	$echo_val = "";
	if ($loglist[2] > $pagecount) {
		$echo_val .= "<div>Page: ";
		for ($i = 0; $i < $max_page; $i++) {
			$j = $i + 1;
			if ($j == $page) {
				$echo_val .= "<a href=\"admin.php?mode=list&amp;category=".$category."&amp;page=".$j."\">[".$j."]</a> ";
			}else{
				$echo_val .= "<a href=\"admin.php?mode=list&amp;category=".$category."&amp;page=".$j."\">".$j."</a> ";
			}
		}
		$echo_val .= "</div>";
	}
	$blogn_skin = str_replace ("{BLOGN_PAGE}", $echo_val, $blogn_skin);

	echo $blogn_skin;
}


//-------------------------------------------------------------------- コメント一覧管理

/* ----- コメント一覧管理表示 ----- */
function blogn_blog_comment_control($admin, $action, $page, $logid, $cmtid) {
	$blogn_skin = file("./template/comment.html");
	$blogn_skin = implode("",$blogn_skin);

	if (!$page) $page = 1;
	// 処理選択
	switch ($action) {
		case "delete":
			$error = blogn_mod_db_comment_delete($cmtid);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "select_delete":
			while(list($key, $val) = each($cmtid)) {
				$error = blogn_mod_db_comment_delete($val);
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
	}

	// 1ページの表示数
	$pagecount = 5;
	$start_key = ($page - 1) * $pagecount;

	$logdata = blogn_mod_db_log_load_for_editor($logid);
	$cmtlist = blogn_mod_db_comment_load_for_list($logid, $start_key, $pagecount);

	$blogn_skin = str_replace ("{BLOGN_ID}", $logdata[1]["id"], $blogn_skin);
	$logdata[1]["title"] = $logdata[1]["title"] ? $logdata[1]["title"] : "** タイトル無し **";
	$blogn_skin = str_replace ("{BLOGN_TITLE}", $logdata[1]["title"], $blogn_skin);


	if ($cmtlist[0]) {
		preg_match("/\{BLOGN_COMMENT_LOOP\}([\w\W]+?)\{\/BLOGN_COMMENT_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_comment_all = "";

		$check_cnt = 0;
		while(list($key, $val) = each($cmtlist[1])) {
			$check_cnt++;
			$blogn_comment = $blogn_reg[0];
			$blogn_comment = str_replace ("{BLOGN_COMMENT_LOOP}", "", $blogn_comment);
			$blogn_comment = str_replace ("{/BLOGN_COMMENT_LOOP}", "", $blogn_comment);

			if ($val["url"]) {
				$url = '<a href="'.$val["url"].'" target="_blank"><img src="./images/url.gif"></a>';
			}else{
				$url = '<img src="./images/blank.gif" width="1" height="1" alt="">';
			}
			if ($val["email"]) {
				$email = '<a href="mailto:'.$val["email"].'"><img src="./images/email.gif" alt="メール"></a>';
			}else{
				$email = '<img src="./images/blank.gif" width="1" height="1" alt="">';
			}
			$mes = ereg_replace("<br />", "<br>", $val["comment"]);

			$del = '<a href="admin.php?mode=list&amp;type=comment&amp;action=delete&amp;id='.$logid.'&amp;cmtid='.$val["id"].'" title="削除"><img src="./images/trash.gif" border="0" alt="削除"></a>';

			$blogn_comment = str_replace ("{BLOGN_CMT_ID}", $val["id"], $blogn_comment);
			$blogn_comment = str_replace ("{BLOGN_CHECK_CNT}", $check_cnt, $blogn_comment);

			$blogn_comment = str_replace ("{BLOGN_NAME}", blogn_magic_quotes($val["name"]), $blogn_comment);
			$blogn_comment = str_replace ("{BLOGN_IP}", $val["ip"], $blogn_comment);
			$blogn_comment = str_replace ("{BLOGN_AGENT}", $val["agent"], $blogn_comment);
			$blogn_comment = str_replace ("{BLOGN_EMAIL}", $email, $blogn_comment);
			$blogn_comment = str_replace ("{BLOGN_URL}", $url, $blogn_comment);
			$blogn_comment = str_replace ("{BLOGN_DATE}", date("Y/m/d H:i:s", mktime(substr($val["date"],8,2), substr($val["date"],10,2), substr($val["date"],12,2), substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4))), $blogn_comment);
			$blogn_comment = str_replace ("{BLOGN_MES}", blogn_magic_quotes($mes), $blogn_comment);
			$blogn_comment = str_replace ("{BLOGN_DEL}", $del, $blogn_comment);
			$blogn_comment_all .= $blogn_comment;
		}
		$blogn_skin = preg_replace("/\{BLOGN_COMMENT_LOOP\}[\w\W]+?\{\/BLOGN_COMMENT_LOOP\}/", $blogn_comment_all, $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_COMMENT}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_COMMENT_ELSE\}[\w\W]+?\{\/BLOGN_COMMENT\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_CMT_CNT}", $check_cnt, $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_COMMENT\}[\w\W]+?\{BLOGN_COMMENT_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_COMMENT}", "", $blogn_skin);
	}

	// ページ処理
	if ($cmtlist[2] != 0) {
		$max_page = ceil($cmtlist[2] / $pagecount);
	}else{
		$max_page = 0;
	}
	if ($cmtlist[2] > $pagecount) {
		$echo_val = '<div>Page: ';
		for ($i = 0; $i < $max_page; $i++) {
			$j = $i + 1;
			if ($j == $page) {
				$echo_val .= '<a href="admin.php?mode=list&amp;type=comment&amp;id='.$logid.'&amp;page='.$j.'">['.$j.']</a> ';
			}else{
				$echo_val .= '<a href="admin.php?mode=list&amp;type=comment&amp;id='.$logid.'&amp;page='.$j.'">'.$j.'</a> ';
			}
		}
		$echo_val .= '</div>';
	}
	$blogn_skin = str_replace ("{BLOGN_PAGE}", $echo_val, $blogn_skin);

	echo $blogn_skin;
}


//-------------------------------------------------------------------- トラックバック一覧管理

/* ----- トラックバック一覧管理表示 ----- */
function blogn_blog_trackback_control($admin, $action, $page, $logid, $trkid) {
	$blogn_skin = file("./template/trackback.html");
	$blogn_skin = implode("",$blogn_skin);

	if (!$page) $page = 1;
	// 処理選択
	switch ($action) {
		case "delete":
			$error = blogn_mod_db_trackback_delete($trkid);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "select_delete":
			while(list($key, $val) = each($trkid)) {
				$error = blogn_mod_db_trackback_delete($val);
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
	}

	// 1ページの表示数
	$pagecount = 5;
	$start_key = ($page - 1) * $pagecount;

	$logdata = blogn_mod_db_log_load_for_editor($logid);
	$trklist = blogn_mod_db_trackback_load_for_list($logid, $start_key, $pagecount);


	$blogn_skin = str_replace ("{BLOGN_ID}", $logdata[1]["id"], $blogn_skin);
	$logdata[1]["title"] = $logdata[1]["title"] ? $logdata[1]["title"] : "** タイトル無し **";
	$blogn_skin = str_replace ("{BLOGN_TITLE}", $logdata[1]["title"], $blogn_skin);

	if ($trklist[0]) {
		preg_match("/\{BLOGN_TRACKBACK_LOOP\}([\w\W]+?)\{\/BLOGN_TRACKBACK_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_trackback_all = "";
		$check_cnt = 0;
		while(list($key, $val) = each($trklist[1])) {
			$check_cnt++;
			$blogn_trackback = $blogn_reg[0];
			$blogn_trackback = str_replace ("{BLOGN_TRACKBACK_LOOP}", "", $blogn_trackback);
			$blogn_trackback = str_replace ("{/BLOGN_TRACKBACK_LOOP}", "", $blogn_trackback);

			if ($val["title"]) {
				$title = '<a href="'.$val["url"].'" target="_blank">'.$val["title"].'</a>';
			}else{
				$title = '** タイトル無し **';
			}
			$mes = ereg_replace("<br />", "<br>", $val["mes"]);
			$del = '<a href="admin.php?mode=list&amp;type=trackback&amp;action=delete&amp;id='.$logid.'&amp;trkid='.$val["id"].'" title="削除"><img src="./images/trash.gif" border="0" alt="削除"></a>';

			$blogn_trackback = str_replace ("{BLOGN_TRK_ID}", $val["id"], $blogn_trackback);
			$blogn_trackback = str_replace ("{BLOGN_CHECK_CNT}", $check_cnt, $blogn_trackback);

			$blogn_trackback = str_replace ("{BLOGN_NAME}", blogn_magic_quotes($val["name"]), $blogn_trackback);
			$blogn_trackback = str_replace ("{BLOGN_IP}", $val["ip"], $blogn_trackback);
			$blogn_trackback = str_replace ("{BLOGN_DATE}", date("Y/m/d H:i:s", mktime(substr($val["date"],8,2), substr($val["date"],10,2), substr($val["date"],12,2), substr($val["date"],4,2), substr($val["date"],6,2), substr($val["date"],0,4))), $blogn_trackback);
			$blogn_trackback = str_replace ("{BLOGN_TITLE}", blogn_magic_quotes($title), $blogn_trackback);
			$blogn_trackback = str_replace ("{BLOGN_MES}", blogn_magic_quotes($mes), $blogn_trackback);
			$blogn_trackback = str_replace ("{BLOGN_DEL}", $del, $blogn_trackback);
			$blogn_trackback_all .= $blogn_trackback;
		}
		$blogn_skin = preg_replace("/\{BLOGN_TRACKBACK_LOOP\}[\w\W]+?\{\/BLOGN_TRACKBACK_LOOP\}/", $blogn_trackback_all, $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_TRACKBACK}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_TRACKBACK_ELSE\}[\w\W]+?\{\/BLOGN_TRACKBACK\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_TRK_CNT}", $check_cnt, $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_TRACKBACK\}[\w\W]+?\{BLOGN_TRACKBACK_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_TRACKBACK}", "", $blogn_skin);
	}


	// ページ処理
	if ($trklist[2] != 0) {
		$max_page = ceil($trklist[2] / $pagecount);
	}else{
		$max_page = 0;
	}
	if ($trklist[2] > $pagecount) {
		$echo_val = '<div>Page: ';
		for ($i = 0; $i < $max_page; $i++) {
			$j = $i + 1;
			if ($j == $page) {
				$echo_val .= '<a href="admin.php?mode=list&amp;type=trackback&amp;id='.$logid.'&amp;page='.$j.'">['.$j.']</a> ';
			}else{
				$echo_val .= '<a href="admin.php?mode=list&amp;type=trackback&amp;id='.$logid.'&amp;page='.$j.'">'.$j.'</a> ';
			}
		}
		$echo_val .= '</div>';
	}
	$blogn_skin = str_replace ("{BLOGN_PAGE}", $echo_val, $blogn_skin);

	echo $blogn_skin;
}


//-------------------------------------------------------------------- ファイル管理

/* ----- ファイル管理表示 ----- */
function blogn_files_control($admin, $action, $page, $id, $user_id, $comment, $upfile) {
	$blogn_skin = file("./template/files.html");
	$blogn_skin = implode("",$blogn_skin);

	if (!$page) $page = 1;
	// 処理選択
	switch ($action) {
		case "new":
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
							$error = blogn_mod_db_file_add($user_id, $file_name, $comment);
							@chmod($dest,0666);
						}
					umask($oldmask);
				}
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "edit":
			$error = blogn_mod_db_file_list_edit($id, $comment);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "delete":
			// ファイル削除
			$error = blogn_mod_db_file_list_delete($id);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "add":
			// 初期表示
			$files = blogn_mod_db_file_load($admin, $user_id, 0, 1);
			if ($files[0]) {
				$files = blogn_mod_db_file_load($admin, $user_id, 0, $files[2]);
				$dir = dir(BLOGN_FILEDIR);
				$i = 0;
				while (($list = $dir -> read()) !== false) {
					if ($list != "." && $list != "..") {
						reset($files[1]);
						$found = false;
						while (list($key, $val) = each($files[1])) {
							if ($list == $val["file_name"]){
								$found = true;
							}
						}
						if (!$found) {
							$i++;
							blogn_mod_db_file_add($user_id, $list, "");
						}
					}
				}
			}
			if ($i) {
				$error[0] = true;
				$error[1] = $i."件のファイルをデータベースに反映しました。";
			}else{
				$error[0] = false;
				$error[1] = "未反映のファイルはありませんでした。";
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default;
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	// 1ページの表示数
	$pagecount = 5;

	$blogn_skin = str_replace ("{BLOGN_NEW_ID}", $userid, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_PERMIT_FILE_TYPE}", BLOGN_PERMIT_FILE_TYPE, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_MAX_FILESIZE}", BLOGN_MAX_FILESIZE, $blogn_skin);


	$start_key = ($page - 1) * $pagecount;
	$end_key = $start_key + $pagecount;

	$userlist = blogn_mod_db_user_load();
	$filelist = blogn_mod_db_file_load($admin, $user_id, $start_key, $end_key);

	if ($filelist[0]) {
		preg_match("/\{BLOGN_FILES_LOOP\}([\w\W]+?)\{\/BLOGN_FILES_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_files_all = "";
		while (list($key, $val) = each($filelist[1])) {
			$blogn_files = $blogn_reg[0];
			$blogn_files = str_replace ("{BLOGN_FILES_LOOP}", "", $blogn_files);
			$blogn_files = str_replace ("{/BLOGN_FILES_LOOP}", "", $blogn_files);

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
			$comment = get_magic_quotes_gpc() ? stripslashes($val["comment"]) : $val["comment"];				//￥を削除
			$comment = htmlspecialchars($comment);

			$blogn_files = str_replace ("{BLOGN_NOW_PAGE}", $page, $blogn_files);

			$blogn_files = str_replace ("{BLOGN_ID}", $key, $blogn_files);
			$blogn_files = str_replace ("{BLOGN_FILE_LINK}", BLOGN_HOMELINK.BLOGN_FILEDIR.$val["file_name"], $blogn_files);
			if ($fmtime = @filemtime(BLOGN_FILEDIR.$val["file_name"])) {
				$blogn_files = str_replace ("{BLOGN_FILE_DATE}", date("Y/m/d H:i:s", $fmtime), $blogn_files);
			}else{
				$blogn_files = str_replace ("{BLOGN_FILE_DATE}", "<font color='red'>不明（ファイルが存在しない可能性があります。）</font>", $blogn_files);
			}
			$blogn_files = str_replace ("{BLOGN_FILE_AUTHOR}", $user_name, $blogn_files);
			$blogn_files = str_replace ("{BLOGN_FILE_COMMENT}", $comment, $blogn_files);
			$blogn_files = str_replace ("{BLOGN_FILE_NAME}", $val["file_name"], $blogn_files);
			if ($fsize = @filesize(BLOGN_FILEDIR.$val["file_name"])) {
				$blogn_files = str_replace ("{BLOGN_FILE_SIZE}", round($fsize / 1024, 2), $blogn_files);
			}else{
				$blogn_files = str_replace ("{BLOGN_FILE_SIZE}", "???", $blogn_files);
			}
			$blogn_files = str_replace ("{BLOGN_FILE_IMAGE}", $imageurl, $blogn_files);
			$blogn_files = str_replace ("{BLOGN_FILE_WIDTH}", $width, $blogn_files);
			$blogn_files = str_replace ("{BLOGN_FILE_HEIGHT}", $height, $blogn_files);
			$blogn_files_all .= $blogn_files;
		}
		$blogn_skin = preg_replace("/\{BLOGN_FILES_LOOP\}[\w\W]+?\{\/BLOGN_FILES_LOOP\}/", $blogn_files_all, $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_FILES}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_FILES_ELSE\}[\w\W]+?\{\/BLOGN_FILES\}/", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_FILES\}[\w\W]+?\{BLOGN_FILES_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_FILES}", "", $blogn_skin);
	}

	// ページ処理
	if ($filelist[2] != 0) {
		$max_page = ceil($filelist[2] / $pagecount);
	}else{
		$max_page = 0;
	}

	$echo_val = "";
	if ($filelist[2] > $pagecount) {
		$echo_val .= "<div>Page: ";
		for ($i = 0; $i < $max_page; $i++) {
			$j = $i + 1;
			if ($j == $page) {
				$echo_val .= "<a href=\"admin.php?mode=files&amp;page=$j\">[$j]</a> ";
			}else{
				$echo_val .= "<a href=\"admin.php?mode=files&amp;page=$j\">$j</a> ";
			}
		}
		$echo_val .= "</div>";
	}
	$blogn_skin = str_replace ("{BLOGN_PAGE}", $echo_val, $blogn_skin);

	if ($admin) {
		$blogn_skin = str_replace ("{BLOGN_ADMIN}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_ADMIN}", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_ADMIN\}[\w\W]+?\{\/BLOGN_ADMIN\}/", "", $blogn_skin);
	}

	echo $blogn_skin;
}


//-------------------------------------------------------------------- カテゴリー管理

/* ----- カテゴリー管理表示 ----- */
function blogn_category_control($admin, $action = "", $category1_id = "", $category1_name = "", $category2_id = "", $category2_name = "", $updown, $view_mode) {
	$blogn_skin = file("./template/category.html");
	$blogn_skin = implode("",$blogn_skin);

	// 処理選択
	switch ($action) {
		case "category1_new":
			$error = blogn_mod_db_category1_add($category1_name);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "category2_new":
			$error = blogn_mod_db_category2_add($category1_id, $category2_name);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "category1_edit":
			$error = blogn_mod_db_category1_edit($category1_id, $category1_name, $view_mode);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "category2_edit":
			$error = blogn_mod_db_category2_edit($category2_id, $category1_id, $category2_name, $view_mode);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "category1_delete":
			$error = blogn_mod_db_category1_delete($category1_id);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "category2_delete":
			$error = blogn_mod_db_category2_delete($category2_id);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "category1_change":
			$error = blogn_mod_db_category1_change($category1_id, $updown);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "category2_change":
			$error = blogn_mod_db_category2_change($category1_id, $category2_id, $updown);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	$category1 = blogn_mod_db_category1_load();
	$category2 = blogn_mod_db_category2_load();


	if ($category1[0]) {
		$blogn_skin = str_replace ("{BLOGN_CATEGORY2_LIST}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_CATEGORY2_LIST}", "", $blogn_skin);
		$category1_option = "";
		while(list($key, $val) = each($category1[1])) {
			$name = get_magic_quotes_gpc() ? stripslashes($val["name"]) : $val["name"];				//￥を削除
			$name = htmlspecialchars($name);
			if ($key == 0) {
				$category1_option .= '<option value="'.$key.'" selected>'.$name.'</option>';
			}else{
				$category1_option .= '<option value="'.$key.'">'.$name.'</option>';
			}
		}
		$blogn_skin = str_replace ("{BLOGN_CATEGORY1_OPTION}", $category1_option, $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_CATEGORY2_LIST\}[\w\W]+?\{\/BLOGN_CATEGORY2_LIST\}/", "", $blogn_skin);
	}

	if ($category1[0]) {
		$c1 = 0;
		reset($category1[1]);
		$blogn_skin = str_replace ("{BLOGN_CATEGORY}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_CATEGORY_ELSE}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_CATEGORY_ELSE\}[\w\W]+?\{\/BLOGN_CATEGORY\}/", "", $blogn_skin);
		preg_match("/\{BLOGN_CATEGORY1_LOOP\}([\w\W]+?)\{\/BLOGN_CATEGORY1_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_category1_all = "";
		while(list($key, $val) = each($category1[1])) {
			$blogn_category1 = $blogn_reg[0];
			$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_LOOP}", "", $blogn_category1);
			$blogn_category1 = str_replace ("{/BLOGN_CATEGORY1_LOOP}", "", $blogn_category1);

			if ($val["view"]) {
				$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_VIEW_SELECT}", " selected", $blogn_category1);
			}else{
				$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_NOVIEW_SELECT}", "", $blogn_category1);
			}

			$c1c2 = 0;
			if ($category2[0]) {
				reset($category2[1]);
				while(list($list_key, $list_val) = each($category2[1])) {
					if ($key == $list_val["id"]) $c1c2++;
				}
			}
			$delref = "./admin.php?mode=category&amp;action=category1_delete&amp;id=".$key;
			$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_DELETE}", $delref, $blogn_category1);

			$name = get_magic_quotes_gpc() ? stripslashes($val["name"]) : $val["name"];				//￥を削除
			$name = htmlspecialchars($name);
			$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_NAME}", $name, $blogn_category1);

			$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_ID}", $key, $blogn_category1);

			if ($c1c2 == 0) {
				$rowspan = '';
			}else{
				$tmp = $c1c2 + 1;
				$rowspan = ' rowspan="'.$tmp.'"';
			}
			$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_ROWSPAN}", $rowspan, $blogn_category1);

			if ($c1 == 0 && count($category1[1]) == 1) {
				$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_TYPE1}", "", $blogn_category1);
				$blogn_category1 = str_replace ("{/BLOGN_CATEGORY1_TYPE1}", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE2\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE2\}/", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE3\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE3\}/", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE4\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE4\}/", "", $blogn_category1);
			}elseif ($c1 == 0) {
				$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_TYPE2}", "", $blogn_category1);
				$blogn_category1 = str_replace ("{/BLOGN_CATEGORY1_TYPE2}", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE1\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE1\}/", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE3\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE3\}/", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE4\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE4\}/", "", $blogn_category1);
			}elseif ($c1 == count($category1[1]) - 1) {
				$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_TYPE3}", "", $blogn_category1);
				$blogn_category1 = str_replace ("{/BLOGN_CATEGORY1_TYPE3}", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE1\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE1\}/", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE2\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE2\}/", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE4\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE4\}/", "", $blogn_category1);
			}else{
				$blogn_category1 = str_replace ("{BLOGN_CATEGORY1_TYPE4}", "", $blogn_category1);
				$blogn_category1 = str_replace ("{/BLOGN_CATEGORY1_TYPE4}", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE1\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE1\}/", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE2\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE2\}/", "", $blogn_category1);
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY1_TYPE3\}[\w\W]+?\{\/BLOGN_CATEGORY1_TYPE3\}/", "", $blogn_category1);
			}

			if ($category2[0]) {
				$blogn_reg2 = array();
				preg_match("/\{BLOGN_CATEGORY2_LOOP\}([\w\W]+?)\{\/BLOGN_CATEGORY2_LOOP\}/", $blogn_category1, $blogn_reg2);
				$blogn_category2_all = "";
				reset($category2[1]);
				$c2 = 0;
				while(list($list_key, $list_val) = each($category2[1])) {
					$blogn_category2 = $blogn_reg2[0];
					$blogn_category2 = str_replace ("{BLOGN_CATEGORY2_LOOP}", "", $blogn_category2);
					$blogn_category2 = str_replace ("{/BLOGN_CATEGORY2_LOOP}", "", $blogn_category2);

					$name = get_magic_quotes_gpc() ? stripslashes($list_val["name"]) : $list_val["name"];				//￥を削除
					$name = htmlspecialchars($name);
					$blogn_category2 = str_replace ("{BLOGN_CATEGORY2_NAME}", $name, $blogn_category2);

					$blogn_category2 = str_replace ("{BLOGN_CATEGORY2_ID}", $list_key, $blogn_category2);

					if ($list_val["view"]) {
						$blogn_category2 = str_replace ("{BLOGN_CATEGORY2_VIEW_SELECT}", " selected", $blogn_category2);
					}else{
						$blogn_category2 = str_replace ("{BLOGN_CATEGORY2_NOVIEW_SELECT}", "", $blogn_category2);
					}

					if ($key == $list_val["id"]) {
						if ($c2 == 0 && $c1c2 == 1) {
							$blogn_category2 = str_replace ("{BLOGN_CATEGORY2_TYPE1}", "", $blogn_category2);
							$blogn_category2 = str_replace ("{/BLOGN_CATEGORY2_TYPE1}", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE2\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE2\}/", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE3\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE3\}/", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE4\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE4\}/", "", $blogn_category2);
						}elseif ($c2 == 0) {
							$blogn_category2 = str_replace ("{BLOGN_CATEGORY2_TYPE2}", "", $blogn_category2);
							$blogn_category2 = str_replace ("{/BLOGN_CATEGORY2_TYPE2}", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE1\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE1\}/", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE3\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE3\}/", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE4\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE4\}/", "", $blogn_category2);
						}elseif ($c2 == $c1c2 - 1) {
							$blogn_category2 = str_replace ("{BLOGN_CATEGORY2_TYPE3}", "", $blogn_category2);
							$blogn_category2 = str_replace ("{/BLOGN_CATEGORY2_TYPE3}", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE1\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE1\}/", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE2\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE2\}/", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE4\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE4\}/", "", $blogn_category2);
						}else{
							$blogn_category2 = str_replace ("{BLOGN_CATEGORY2_TYPE4}", "", $blogn_category2);
							$blogn_category2 = str_replace ("{/BLOGN_CATEGORY2_TYPE4}", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE1\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE1\}/", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE2\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE2\}/", "", $blogn_category2);
							$blogn_category2 = preg_replace("/\{BLOGN_CATEGORY2_TYPE3\}[\w\W]+?\{\/BLOGN_CATEGORY2_TYPE3\}/", "", $blogn_category2);
						}
						$c2++;
						$blogn_category2_all .= $blogn_category2;
					}
				}
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY2_LOOP\}[\w\W]+?\{\/BLOGN_CATEGORY2_LOOP\}/", $blogn_category2_all, $blogn_category1);
			}else{
				$blogn_category1 = preg_replace("/\{BLOGN_CATEGORY2_LOOP\}[\w\W]+?\{\/BLOGN_CATEGORY2_LOOP\}/", "", $blogn_category1);
			}
			$c1++;
			$blogn_category1_all .= $blogn_category1;
		}
		$blogn_skin = preg_replace("/\{BLOGN_CATEGORY1_LOOP\}[\w\W]+?\{\/BLOGN_CATEGORY1_LOOP\}/", $blogn_category1_all, $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_CATEGORY1}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_CATEGORY_ELSE\}[\w\W]+?\{\/BLOGN_CATEGORY\}/", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_CATEGORY\}[\w\W]+?\{BLOGN_CATEGORY_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_CATEGORY}", "", $blogn_skin);
	}
	echo $blogn_skin;
}


//-------------------------------------------------------------------- リンク管理

/* ----- リンク管理表示 ----- */
function blogn_link_control($admin, $action = "", $id = "", $group_id = "", $name, $link_url, $updown) {
	$blogn_skin = file("./template/link.html");
	$blogn_skin = implode("",$blogn_skin);

	// 処理選択
	switch ($action) {
		case "group_new":
			$error = blogn_mod_db_link_group_add($name);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "link_new":
			$error = blogn_mod_db_link_list_add($id, $name, $link_url);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "group_edit":
			$error = blogn_mod_db_link_group_edit($id, $name);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "link_edit":
			$error = blogn_mod_db_link_list_edit($id, $group_id, $name, $link_url);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "group_delete":
			$error = blogn_mod_db_link_group_delete($id);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "link_delete":
			$error = blogn_mod_db_link_list_delete($id);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "group_change":
			$error = blogn_mod_db_link_group_change($id, $updown);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "link_change":
			$error = blogn_mod_db_link_list_change($group_id, $id, $updown);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}


	// リンク表示
	$linkgroup = blogn_mod_db_link_group_load();
	$linklist = blogn_mod_db_link_load();
	$link1_option = "";
	if ($linkgroup[0]) {
		$blogn_skin = str_replace ("{BLOGN_LINK2_LIST}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_LINK2_LIST}", "", $blogn_skin);
		while(list($key, $val) = each($linkgroup[1])) {
			$name = get_magic_quotes_gpc() ? stripslashes($val["name"]) : $val["name"];				//￥を削除
			$name = htmlspecialchars($name);
			if ($key == 0) {
				$link1_option .= '<option value="'.$key.'" selected>'.$name.'</option>';
			}else{
				$link1_option .= '<option value="'.$key.'">'.$name.'</option>';
			}
		}
		$blogn_skin = str_replace ("{BLOGN_LINK1_OPTION}", $link1_option, $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_LINK2_LIST\}[\w\W]+?\{\/BLOGN_LINK2_LIST\}/", "", $blogn_skin);
	}

	if ($linkgroup[0]) {
		$l1 = 0;
		reset($linkgroup[1]);
		$blogn_skin = str_replace ("{BLOGN_LINK}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_LINK_ELSE}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_LINK_ELSE\}[\w\W]+?\{\/BLOGN_LINK\}/", "", $blogn_skin);
		preg_match("/\{BLOGN_LINK1_LOOP\}([\w\W]+?)\{\/BLOGN_LINK1_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_link1_all = "";
		while(list($key, $val) = each($linkgroup[1])) {
			$blogn_link1 = $blogn_reg[0];
			$blogn_link1 = str_replace ("{BLOGN_LINK1_LOOP}", "", $blogn_link1);
			$blogn_link1 = str_replace ("{/BLOGN_LINK1_LOOP}", "", $blogn_link1);

			$l1l2 = 0;
			if ($linklist[0]) {
				reset($linklist[1]);
				while(list($list_key, $list_val) = each($linklist[1])) {
					if ($key == $list_val["group"]) $l1l2++;
				}
			}
			$delref = "./admin.php?mode=link&amp;action=group_delete&amp;id=".$key;
			$blogn_link1 = str_replace ("{BLOGN_LINK1_DELETE}", $delref, $blogn_link1);

			$name = get_magic_quotes_gpc() ? stripslashes($val["name"]) : $val["name"];				//￥を削除
			$name = htmlspecialchars($name);
			$blogn_link1 = str_replace ("{BLOGN_LINK1_NAME}", $name, $blogn_link1);

			$blogn_link1 = str_replace ("{BLOGN_LINK1_ID}", $key, $blogn_link1);

			if ($l1l2 == 0) {
				$rowspan = '';
			}else{
				$tmp = $l1l2 + 1;
				$rowspan = ' rowspan="'.$tmp.'"';
			}
			$blogn_link1 = str_replace ("{BLOGN_LINK1_ROWSPAN}", $rowspan, $blogn_link1);

			if ($l1 == 0 && count($linkgroup[1]) == 1) {
				$blogn_link1 = str_replace ("{BLOGN_LINK1_TYPE1}", "", $blogn_link1);
				$blogn_link1 = str_replace ("{/BLOGN_LINK1_TYPE1}", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE2\}[\w\W]+?\{\/BLOGN_LINK1_TYPE2\}/", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE3\}[\w\W]+?\{\/BLOGN_LINK1_TYPE3\}/", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE4\}[\w\W]+?\{\/BLOGN_LINK1_TYPE4\}/", "", $blogn_link1);
			}elseif ($l1 == 0) {
				$blogn_link1 = str_replace ("{BLOGN_LINK1_TYPE2}", "", $blogn_link1);
				$blogn_link1 = str_replace ("{/BLOGN_LINK1_TYPE2}", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE1\}[\w\W]+?\{\/BLOGN_LINK1_TYPE1\}/", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE3\}[\w\W]+?\{\/BLOGN_LINK1_TYPE3\}/", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE4\}[\w\W]+?\{\/BLOGN_LINK1_TYPE4\}/", "", $blogn_link1);
			}elseif ($l1 == count($linkgroup[1]) - 1) {
				$blogn_link1 = str_replace ("{BLOGN_LINK1_TYPE3}", "", $blogn_link1);
				$blogn_link1 = str_replace ("{/BLOGN_LINK1_TYPE3}", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE1\}[\w\W]+?\{\/BLOGN_LINK1_TYPE1\}/", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE2\}[\w\W]+?\{\/BLOGN_LINK1_TYPE2\}/", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE4\}[\w\W]+?\{\/BLOGN_LINK1_TYPE4\}/", "", $blogn_link1);
			}else{
				$blogn_link1 = str_replace ("{BLOGN_LINK1_TYPE4}", "", $blogn_link1);
				$blogn_link1 = str_replace ("{/BLOGN_LINK1_TYPE4}", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE1\}[\w\W]+?\{\/BLOGN_LINK1_TYPE1\}/", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE2\}[\w\W]+?\{\/BLOGN_LINK1_TYPE2\}/", "", $blogn_link1);
				$blogn_link1 = preg_replace("/\{BLOGN_LINK1_TYPE3\}[\w\W]+?\{\/BLOGN_LINK1_TYPE3\}/", "", $blogn_link1);
			}

			// サイト一覧
			if ($linklist[0]) {
				$blogn_reg2 = array();
				preg_match("/\{BLOGN_LINK2_LOOP\}([\w\W]+?)\{\/BLOGN_LINK2_LOOP\}/", $blogn_link1, $blogn_reg2);
				$blogn_link2_all = "";
				reset($linklist[1]);
				$l2 = 0;
				while(list($list_key, $list_val) = each($linklist[1])) {
					$blogn_link2 = $blogn_reg2[0];
					$blogn_link2 = str_replace ("{BLOGN_LINK2_LOOP}", "", $blogn_link2);
					$blogn_link2 = str_replace ("{/BLOGN_LINK2_LOOP}", "", $blogn_link2);

					$name = get_magic_quotes_gpc() ? stripslashes($list_val["name"]) : $list_val["name"];				//￥を削除
					$name = htmlspecialchars($name);
					$blogn_link2 = str_replace ("{BLOGN_LINK2_NAME}", $name, $blogn_link2);

					$blogn_link2 = str_replace ("{BLOGN_LINK2_ID}", $list_key, $blogn_link2);

					$url = get_magic_quotes_gpc() ? stripslashes($list_val["url"]) : $list_val["url"];				//￥を削除
					$url = htmlspecialchars($url);
					$blogn_link2 = str_replace ("{BLOGN_LINK2_URL}", $url, $blogn_link2);

					if ($key == $list_val["group"]) {
						if ($l2 == 0 && $l1l2 == 1) {
							$blogn_link2 = str_replace ("{BLOGN_LINK2_TYPE1}", "", $blogn_link2);
							$blogn_link2 = str_replace ("{/BLOGN_LINK2_TYPE1}", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE2\}[\w\W]+?\{\/BLOGN_LINK2_TYPE2\}/", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE3\}[\w\W]+?\{\/BLOGN_LINK2_TYPE3\}/", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE4\}[\w\W]+?\{\/BLOGN_LINK2_TYPE4\}/", "", $blogn_link2);
						}elseif ($l2 == 0) {
							$blogn_link2 = str_replace ("{BLOGN_LINK2_TYPE2}", "", $blogn_link2);
							$blogn_link2 = str_replace ("{/BLOGN_LINK2_TYPE2}", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE1\}[\w\W]+?\{\/BLOGN_LINK2_TYPE1\}/", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE3\}[\w\W]+?\{\/BLOGN_LINK2_TYPE3\}/", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE4\}[\w\W]+?\{\/BLOGN_LINK2_TYPE4\}/", "", $blogn_link2);
						}elseif ($l2 == $l1l2 - 1) {
							$blogn_link2 = str_replace ("{BLOGN_LINK2_TYPE3}", "", $blogn_link2);
							$blogn_link2 = str_replace ("{/BLOGN_LINK2_TYPE3}", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE1\}[\w\W]+?\{\/BLOGN_LINK2_TYPE1\}/", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE2\}[\w\W]+?\{\/BLOGN_LINK2_TYPE2\}/", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE4\}[\w\W]+?\{\/BLOGN_LINK2_TYPE4\}/", "", $blogn_link2);
						}else{
							$blogn_link2 = str_replace ("{BLOGN_LINK2_TYPE4}", "", $blogn_link2);
							$blogn_link2 = str_replace ("{/BLOGN_LINK2_TYPE4}", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE1\}[\w\W]+?\{\/BLOGN_LINK2_TYPE1\}/", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE2\}[\w\W]+?\{\/BLOGN_LINK2_TYPE2\}/", "", $blogn_link2);
							$blogn_link2 = preg_replace("/\{BLOGN_LINK2_TYPE3\}[\w\W]+?\{\/BLOGN_LINK2_TYPE3\}/", "", $blogn_link2);
						}
						$l2++;
						$blogn_link2_all .= $blogn_link2;
					}
				}
				$blogn_link1 = preg_replace("/\{BLOGN_LINK2_LOOP\}[\w\W]+?\{\/BLOGN_LINK2_LOOP\}/", $blogn_link2_all, $blogn_link1);
			}else{
				$blogn_link1 = preg_replace("/\{BLOGN_LINK2_LOOP\}[\w\W]+?\{\/BLOGN_LINK2_LOOP\}/", "", $blogn_link1);
			}
			$l1++;
			$blogn_link1_all .= $blogn_link1;
		}
		$blogn_skin = preg_replace("/\{BLOGN_LINK1_LOOP\}[\w\W]+?\{\/BLOGN_LINK1_LOOP\}/", $blogn_link1_all, $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_LINK1}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_LINK_ELSE\}[\w\W]+?\{\/BLOGN_LINK\}/", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_LINK\}[\w\W]+?\{BLOGN_LINK_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_LINK}", "", $blogn_skin);
	}
	echo $blogn_skin;
}


//-------------------------------------------------------------------- 更新PING管理

/* ----- 更新PING管理画面表示 ----- */
function blogn_ping_control($admin, $action = "", $id, $name, $url, $ping_default) {
	$blogn_skin = file("./template/ping.html");
	$blogn_skin = implode("",$blogn_skin);

	if (!$admin) return;
	// 処理選択
	switch ($action) {
		case "new":
			$error = blogn_mod_db_ping_add($name, $url, $ping_default);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "edit":
			$error = blogn_mod_db_ping_edit($id, $name, $url, $ping_default);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "delete":
			$error = blogn_mod_db_ping_delete($id);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default;
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	$pinglist = blogn_mod_db_ping_load();

	if ($pinglist[0]) {
		$blogn_skin = preg_replace("/\{BLOGN_PING_ELSE\}[\w\W]+?\{\/BLOGN_PING\}/", "", $blogn_skin);
		preg_match("/\{BLOGN_PING_LOOP\}([\w\W]+?)\{\/BLOGN_PING_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_ping_all = "";
		while(list($key, $val) = each($pinglist[1])) {
			$blogn_ping = $blogn_reg[0];
			$blogn_ping = str_replace ("{BLOGN_PING_LOOP}", "", $blogn_ping);
			$blogn_ping = str_replace ("{/BLOGN_PING_LOOP}", "", $blogn_ping);

			$name = get_magic_quotes_gpc() ? stripslashes($val["name"]) : $val["name"];				//￥を削除
			$name = htmlspecialchars($name);
			$blogn_ping = str_replace ("{BLOGN_PING_NAME}", $name, $blogn_ping);

			$blogn_ping = str_replace ("{BLOGN_PING_ID}", $key, $blogn_ping);

			$blogn_ping = str_replace ("{BLOGN_PING_URL}", $val["url"], $blogn_ping);

			if ($val["default"]) {
				$blogn_ping_option = '<option value="0">チェックを入れない</option><option value="1" selected>チェックを入れる</option>';
			}else{
				$blogn_ping_option = '<option value="0" selected>チェックを入れない</option><option value="1">チェックを入れる</option>';
			}
			$blogn_ping = str_replace ("{BLOGN_PING_OPTION}", $blogn_ping_option, $blogn_ping);
			$blogn_ping_all .= $blogn_ping;
		}
		$blogn_skin = preg_replace("/\{BLOGN_PING_LOOP\}[\w\W]+?\{\/BLOGN_PING_LOOP\}/", $blogn_ping_all, $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_PING}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_PING_ELSE\}[\w\W]+?\{\/BLOGN_PING\}/", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_PING\}[\w\W]+?\{BLOGN_PING_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_PING}", "", $blogn_skin);
	}
	echo $blogn_skin;
}


//-------------------------------------------------------------------- ユーザー情報

/* ----- コマンド送信 ----- */
function blogn_send_cmd($fp, $cmd) {
	fputs($fp, $cmd."\r\n");
	$buf = fgets($fp, 512);
	if(substr($buf, 0, 3) == '+OK') {
		return $buf;
	}
	return false;
}

/* ----- ユーザー情報画面表示 ----- */
function blogn_profile_control($admin, $action = "", $id = "", $user_id = "", $user_pw = "", $retype_user_pw = "", $user_name = "", $user_profile = "", $init_comment_ok, $init_trackback_ok, $init_category, $init_icon_ok, $receive_mail_address, $receive_mail_pop3, $receive_mail_user_id, $receive_mail_user_pw, $receive_mail_apop, $accsess_time, $send_mail_address, $mobile_category, $mobile_comment_ok, $mobile_trackback_ok, $information_mail_address, $information_comment, $information_trackback, $user_mail_address, $br_change) {
	$blogn_skin = file("./template/userdetail.html");
	$blogn_skin = implode("",$blogn_skin);
	// 処理選択
	switch ($action) {
		case "mailcheck":
			if ($fp = @fsockopen ($receive_mail_pop3, 110, $errno, $errstr, 30)) {
				$buf = fgets($fp, 512);
				if(substr($buf, 0, 3) == '+OK') {
					if($receive_mail_apop == 1) {
						$arraybuf = explode(" ", trim($buf));
						$md5pass = md5($arraybuf[count($arraybuf) - 1].$receive_mail_user_pw);
						$buf = blogn_send_cmd($fp, "APOP {$loginid} {$md5pass}");
					} else {
						$buf = blogn_send_cmd($fp, "USER {$receive_mail_user_id}");
						$buf = blogn_send_cmd($fp, "PASS {$receive_mail_user_pw}");
					}
					if ($buf) {
						$error[0] = true;
						$error[1] = "受信メールアドレスに接続できました。受信メールアドレスは正しく設定されています。";
					}else{
						$error[0] = false;
						$error[1] = "受信メールアドレスに接続できませんでした。受信メール用ログインID又は受信メール用パスワードを確認してください。";
					}
				}
				$buf = blogn_send_cmd($fp, "QUIT");
			}else{
				$error[0] = false;
				$error[1] = "メールサーバーに接続できませんでした。受信メールPOP3サーバーを確認してください。";
			}
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "update":
			if ($user_pw && $user_pw != $retype_user_pw) {
				$error[0] = false;
				$error[1] = "パスワード入力ミスです。";
			}elseif (strlen($user_pw) < 4 || strlen($user_pw) > 12){
				$error[0] = false;
				$error[1] = "パスワードにする文字数は４～１２文字までで設定してください。";
			}else{
				$user_profile = blogn_html_tag_restore($user_profile);
				$user_profile = blogn_magic_quotes($user_profile);						//￥を削除

				if (!$br_change) {
					$user_profile = blogn_rn2rntag($user_profile);
				}else{
					$user_profile = blogn_mod_db_rn2br($user_profile);
				}
				$error = blogn_mod_db_user_profile_update($id, $user_id, $user_pw, $user_name, $user_profile, $init_comment_ok, $init_trackback_ok, $init_category, $init_icon_ok, $receive_mail_address, $receive_mail_pop3, $receive_mail_user_id, $receive_mail_user_pw, $receive_mail_apop, $accsess_time, $send_mail_address, $mobile_category, $mobile_comment_ok, $mobile_trackback_ok, $information_mail_address, $information_comment, $information_trackback, $user_mail_address, $br_change);
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			if (!$id) {
				$userlist = blogn_mod_db_user_load();
				while (list($key, $val) = each($userlist)) {
					if ($user_id == $val["id"]) {
						$id = $key;
						break;
					}
				}
			}
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	if (!$userlist = blogn_mod_db_user_profile_load($id)) {
		$information = "ユーザー情報の取得に失敗しました。データが破損している可能性があります。";
		$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar(false, $information), $blogn_skin);
	}else{
		$user_id = $userlist["id"];
		$user_name = $userlist["name"];
		$user_profile = $userlist["profile"];
		if (get_magic_quotes_gpc()) {
			$user_profile = stripslashes($user_profile);				//￥を削除
		}
		$init_comment_ok = $userlist["init_comment_ok"];
		$init_trackback_ok = $userlist["init_trackback_ok"];
		$init_category = $userlist["init_category"];
		$init_icon_ok = $userlist["init_icon_ok"];
		$receive_mail_address = $userlist["receive_mail_address"];
		$receive_mail_pop3 = $userlist["receive_mail_pop3"];
		$receive_mail_user_id = $userlist["receive_mail_user_id"];
		$receive_mail_user_pw = $userlist["receive_mail_user_pw"];
		$receive_mail_apop = $userlist["receive_mail_apop"];
		$access_time = $userlist["access_time"];
		$send_mail_address = $userlist["send_mail_address"];
		$mobile_category = $userlist["mobile_category"];
		$mobile_comment_ok = $userlist["mobile_comment_ok"];
		$mobile_trackback_ok = $userlist["mobile_trackback_ok"];
		$information_mail_address = $userlist["information_mail_address"];
		$information_comment = $userlist["information_comment"];
		$information_trackback = $userlist["information_trackback"];
		$user_mail_address = $userlist["user_mail_address"];
		$br_change = $userlist["br_change"];
	}

	$category1 = blogn_mod_db_category1_load();
	$category2 = blogn_mod_db_category2_load();


	// ユーザープロフィール表示
	if ($init_icon_ok) {
		$blogn_skin = str_replace ("{BLOGN_ICON_TOOLBAR}", blogn_icon_toolbar(0), $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_TAG_TOOLBAR}", blogn_tag_toolbar(0,0), $blogn_skin);
	}else{
		$blogn_skin = str_replace ("{BLOGN_ICON_TOOLBAR}", "", $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_TAG_TOOLBAR}", blogn_tag_toolbar(0,1), $blogn_skin);
	}

	if (!$br_change) {
		$user_profile = blogn_rntag2rn($user_profile);
		$textarea_profile = $user_profile;
	}else{
		$textarea_profile = blogn_rn_change($user_profile);
	}
	$blogn_skin = str_replace ("{BLOGN_USER_PROFILE}", blogn_html_tag_convert($textarea_profile), $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_USER_ID}", $id, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_LOGIN_ID}", $user_id, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_USER_NAME}", $user_name, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_USER_MAIL}", $user_mail_address, $blogn_skin);

	if ($br_change) {
		$br_check = " checked";
	}else{
		$br_check = "";
	}
	$blogn_skin = str_replace ("{BLOGN_BR_CHECK}", $br_check, $blogn_skin);

	if ($init_comment_ok) {
		$echo_val = "<option value=\"1\" selected>受付を許可</option>
		<option value=\"0\">受付を拒否</option>";
	}else{
		$echo_val = "<option value=\"1\">受付を許可</option>
		<option value=\"0\" selected>受付を拒否</option>";
	}
	$blogn_skin = str_replace ("{BLOGN_INIT_COMMENT}", $echo_val, $blogn_skin);

	if ($init_trackback_ok) {
		$echo_val = "<option value=\"1\" selected>受付を許可</option>
		<option value=\"0\">受付を拒否</option>";
	}else{
		$echo_val = "<option value=\"1\">受付を許可</option>
		<option value=\"0\" selected>受付を拒否</option>";
	}
	$blogn_skin = str_replace ("{BLOGN_INIT_TRACKBACK}", $echo_val, $blogn_skin);

	$echo_val = "";
	if ($category1[0]) {
		$i = 0;
		reset($category1[1]);
		$blogn_option = "";
		while (list($c1key, $c1val) = each($category1[1])) {
			if ($init_category == "" && $i == 0) {
				$select = " selected";
			}elseif ($init_category == $c1key."|") {
				$select = " selected";
			}else{
				$select = "";
			}
			$c1name = get_magic_quotes_gpc() ? stripslashes($c1val["name"]) : $c1val["name"];				//￥を削除
			$c1name = htmlspecialchars($c1name);
			$echo_val .= "<option value=\"{$c1key}|\"{$select}>{$c1name}</option>";
			if ($category2[0]) {
				reset($category2[1]);
				while (list($c2key, $c2val) = each($category2[1])) {
					if ($c1key == $c2val["id"]) {
						if ($init_category == $c1key."|".$c2key) {
							$select = " selected";
						}else{
							$select = "";
						}
						$c2name = get_magic_quotes_gpc() ? stripslashes($c2val["name"]) : $c2val["name"];				//￥を削除
						$c2name = htmlspecialchars($c2name);
						$echo_val .= "<option value=\"{$c1key}|{$c2key}\"{$select}>└{$c1name}::{$c2name}</option>";
					}
				}
				$i++;
			}
		}
	}
	$blogn_skin = str_replace ("{BLOGN_INIT_CATEGORY}", $echo_val, $blogn_skin);


	if ($init_icon_ok) {
		$echo_val = "<option value=\"1\" selected>表示する</option>
		<option value=\"0\">表示しない</option>";
	}else{
		$echo_val = "<option value=\"1\">表示する</option>
		<option value=\"0\" selected>表示しない</option>";
	}
	$blogn_skin = str_replace ("{BLOGN_INIT_ICON_OK}", $echo_val, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_RECEIVE_ADDRESS}", $receive_mail_address, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_RECEIVE_POP3}", $receive_mail_pop3, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_RECEIVE_MAIL_USER_ID}", $receive_mail_user_id, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_RECEIVE_MAIL_USER_PW}", $receive_mail_user_pw, $blogn_skin);

	$check[] = array();
	if ($receive_mail_apop == 0) {
		$check[0] = " checked";
		$check[1] = "";
	}else{
		$check[0] = "";
		$check[1] = " checked";
	}
	$echo_val  = "<input type=\"radio\" name=\"blogn_receive_mail_apop\" value=\"0\"{$check[0]}>標準";
	$echo_val .= "<input type=\"radio\" name=\"blogn_receive_mail_apop\" value=\"1\"{$check[1]}>APOP";
	$blogn_skin = str_replace ("{BLOGN_PASSWORD_TYPE}", $echo_val, $blogn_skin);


	$echo_val = "";
	$mail_access_time = array(1,2,3,4,5,6,7,8,9,10,15,30,60); 
	foreach ($mail_access_time as $i) {
		if ($access_time == $i) {
			$echo_val .= '<option value="'.$i.'" selected>'.$i.'</option>';
		}else{
			$echo_val .= '<option value="'.$i.'">'.$i.'</option>';
		}
	}
	$blogn_skin = str_replace ("{BLOGN_ACCESS_TIME}", $echo_val, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_SEND_MAIL}", $send_mail_address, $blogn_skin);


	$echo_val = "";
	if ($category1[0]) {
		$i = 0;
		reset($category1[1]);
		while (list($c1key, $c1val) = each($category1[1])) {
			if ($mobile_category == "" && $i == 0) {
				$select = " selected";
			}elseif ($mobile_category == $c1key."|") {
				$select = " selected";
			}else{
				$select = "";
			}
			$c1name = get_magic_quotes_gpc() ? stripslashes($c1val["name"]) : $c1val["name"];				//￥を削除
			$c1name = htmlspecialchars($c1name);
			$echo_val .= "<option value=\"{$c1key}|\"{$select}>{$c1name}</option>";
			if ($category2[0]) {
				reset($category2[1]);
				while (list($c2key, $c2val) = each($category2[1])) {
					if ($c1key == $c2val["id"]) {
						if ($mobile_category == $c1key."|".$c2key) {
							$select = " selected";
						}else{
							$select = "";
						}
						$c2name = get_magic_quotes_gpc() ? stripslashes($c2val["name"]) : $c2val["name"];				//￥を削除
						$c2name = htmlspecialchars($c2name);
						$echo_val .= "<option value=\"{$c1key}|{$c2key}\"{$select}>└{$c1name}::{$c2name}</option>";
					}
				}
				$i++;
			}
		}
	}
	$blogn_skin = str_replace ("{BLOGN_MOBILE_CATEGORY}", $echo_val, $blogn_skin);

	if ($mobile_comment_ok) {
		$echo_val = "<option value=\"1\" selected>受付を許可</option>
		<option value=\"0\">受付を拒否</option>";
	}else{
		$echo_val = "<option value=\"1\">受付を許可</option>
		<option value=\"0\" selected>受付を拒否</option>";
	}
	$blogn_skin = str_replace ("{BLOGN_MOBILE_COMMENT_OK}", $echo_val, $blogn_skin);

	if ($mobile_trackback_ok) {
		$echo_val = "<option value=\"1\" selected>受付を許可</option>
		<option value=\"0\">受付を拒否</option>";
	}else{
		$echo_val = "<option value=\"1\">受付を許可</option>
		<option value=\"0\" selected>受付を拒否</option>";
	}
	$blogn_skin = str_replace ("{BLOGN_MOBILE_TRACKBACK_OK}", $echo_val, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_INFORMATION_MAIL}", $information_mail_address, $blogn_skin);

	if ($information_comment != 1) {
		$echo_checked = "";
	}else{
		$echo_checked = " checked";
	}
	$blogn_skin = str_replace ("{BLOGN_INFORMATION_COMMENT}", $echo_checked, $blogn_skin);

	if ($information_trackback != 1) {
		$echo_checked = "";
	}else{
		$echo_checked = " checked";
	}
	$blogn_skin = str_replace ("{BLOGN_INFORMATION_TRACKBACK}", $echo_checked, $blogn_skin);

	echo $blogn_skin;
}


//-------------------------------------------------------------------- ユーザー管理

/* ----- ユーザー管理画面表示 ----- */
function blogn_user_control($admin, $action = "", $id, $user_id = "", $user_pw = "", $retype_user_pw = "", $user_name = "", $mailaddress = "", $user_active = "", $user_active_mode) {
	if (!$admin) return;
	$blogn_skin = file("./template/userlist.html");
	$blogn_skin = implode("",$blogn_skin);

	// 処理選択
	switch ($action) {
		case "add":			// ----- ユーザー追加
			if(!$user_id || !$user_pw || !$retype_user_pw || !$user_name) {
				$error[0] = false;
				$error[1] = "追加ユーザー情報に未入力箇所があります。";
			}elseif ($user_pw != $retype_user_pw) {
				$error[0] = false;
				$error[1] = "パスワード入力ミスです。";
			}else{
				$error = blogn_mod_db_user_add($user_id, $user_pw, $user_name, $mailaddress, 0, 0);
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "active":	// ----- ユーザー状態変更
			$error = blogn_mod_db_user_active($id, $user_active_mode);
			if ($error[0]) $error[1] = $error[1]."ID:".$error[2];
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "del":		// ----- ユーザー削除
			$error = blogn_mod_db_user_delete($id);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:				// ----- ユーザー一覧
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	$blogn_skin = str_replace ("{BLOGN_USER_ID}", $user_id, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_USER_NAME}", $user_name, $blogn_skin);

	$userlist = blogn_mod_db_user_load();
	if (count($userlist) != 0) {
		$blogn_skin = preg_replace("/\{BLOGN_USER_LIST_ELSE\}[\w\W]+?\{\/BLOGN_USER_LIST\}/", "", $blogn_skin);
		preg_match("/\{BLOGN_USER_LIST_LOOP\}([\w\W]+?)\{\/BLOGN_USER_LIST_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_user_list_all = "";
		$i = 0;
		while(list($key, $val) = each($userlist)) {
			$blogn_user_list = $blogn_reg[0];
			$blogn_user_list = str_replace ("{BLOGN_USER_LIST_LOOP}", "", $blogn_user_list);
			$blogn_user_list = str_replace ("{/BLOGN_USER_LIST_LOOP}", "", $blogn_user_list);

			$blogn_user_list = str_replace ("{BLOGN_LABEL_ID}", $i, $blogn_user_list);
			$blogn_user_list = str_replace ("{BLOGN_ID}", $key, $blogn_user_list);
			$blogn_user_list = str_replace ("{BLOGN_LABEL_ID}", $i, $blogn_user_list);

			if ($i == 0) {
				$echo_val = ' checked';
			}else{
				$echo_val = '';
			}
			$blogn_user_list = str_replace ("{BLOGN_CHECKED}", $echo_val, $blogn_user_list);

			$blogn_user_list = str_replace ("{BLOGN_USER_LIST_ID}", $val["id"], $blogn_user_list);
			$blogn_user_list = str_replace ("{BLOGN_USER_LIST_NAME}", $val["name"], $blogn_user_list);

			$active0 = $active1 = $active2 = $active3 = "";
			if ($val["admin"]) {
				$active3 = " selected";
			}elseif ($val["active"] == "0") {
				$active0 = " selected";
			}elseif ($val["active"] == "1") {
				$active1 = " selected";
			}elseif ($val["active"] == "2") {
				$active2 = " selected";
			}
			$blogn_user_list = str_replace ("{BLOGN_USER_LIST_SELECT0}", $active0, $blogn_user_list);
			$blogn_user_list = str_replace ("{BLOGN_USER_LIST_SELECT1}", $active1, $blogn_user_list);
			$blogn_user_list = str_replace ("{BLOGN_USER_LIST_SELECT2}", $active2, $blogn_user_list);
			$blogn_user_list = str_replace ("{BLOGN_USER_LIST_SELECT3}", $active3, $blogn_user_list);

			$blogn_user_list_all .= $blogn_user_list;

			$i++;
		}
		$blogn_skin = preg_replace("/\{BLOGN_USER_LIST_LOOP\}[\w\W]+?\{\/BLOGN_USER_LIST_LOOP\}/", $blogn_user_list_all, $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_USER_LIST}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_USER_LIST_ELSE\}[\w\W]+?\{\/BLOGN_USER_LIST\}/", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_USER_LIST\}[\w\W]+?\{BLOGN_USER_LIST_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_USER_LIST}", "", $blogn_skin);
	}
	echo $blogn_skin;
}



//-------------------------------------------------------------------- 初期データ管理

function blogn_init_control($admin, $action, $sitename, $sitedesc, $timezone, $charset, $max_filesize, $permit_file_type, $max_view_width, $max_view_height, $permit_html_tag, $comment_size, $trackback_slash_type, $log_view_count, $mobile_view_count, $new_entry_view_count, $archive_view_count, $comment_view_count, $trackback_view_count, $comment_list_topview_on, $trackback_list_topview_on, $session_time, $cookie_time, $limit_comment, $limit_trackback, $monthly_view_mode, $category_view_mode) {
/* ----- 初期データ表示 ----- */
	if (!$admin) return;
	$blogn_skin = file("./template/init.html");
	$blogn_skin = implode("",$blogn_skin);

	$initlist = blogn_mod_db_init_load();

	// 処理選択
	switch($action) {
		case "update":
			$permit_file_type = $initlist["permit_file_type"];
			$permit_html_tag = $initlist["permit_html_tag"];
			$error = blogn_mod_db_init_Change(0, $sitename, $sitedesc, $timezone, $charset, $max_filesize, $permit_file_type, $max_view_width, $max_view_height, $permit_html_tag, $comment_size, $trackback_slash_type, $log_view_count, $mobile_view_count, $new_entry_view_count, $archive_view_count, $comment_view_count, $trackback_view_count, $comment_list_topview_on, $trackback_list_topview_on, $session_time, $cookie_time, $limit_comment, $limit_trackback, $monthly_view_mode, $category_view_mode);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "file_add":
			$permit_html_tag = $initlist["permit_html_tag"];
			$old_permit_file_type = explode(",",$initlist["permit_file_type"]);
			$new_permit_file_type = array();
			if ($old_permit_file_type) {
				while (list($key, $val) = each($old_permit_file_type)) {
					if (trim($val)) {
						$new_permit_file_type[] = trim($val);
					}
				}
			}
			$check_file = explode(",",$permit_file_type);
			if ($check_file[0]) {
				while(list($key, $val) = each($check_file)) {
					if (trim($val)) $new_permit_file_type[] = trim($val);
				}
				$permit_file_type = implode(",",$new_permit_file_type);
				$error = blogn_mod_db_init_Change(0, $sitename, $sitedesc, $timezone, $charset, $max_filesize, $permit_file_type, $max_view_width, $max_view_height, $permit_html_tag, $comment_size, $trackback_slash_type, $log_view_count, $mobile_view_count, $new_entry_view_count, $archive_view_count, $comment_view_count, $trackback_view_count, $comment_list_topview_on, $trackback_list_topview_on, $session_time, $cookie_time, $limit_comment, $limit_trackback, $monthly_view_mode, $category_view_mode);
			}else{
				$error[0] = false;
				$error[1] = "許可するファイルの種類を書いてから追加ボタンを押してください。";
				$permit_file_type = $initlist["permit_file_type"];
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "file_del":
			$old_permit_file_type = explode(",",$initlist["permit_file_type"]);
			$new_permit_file_type = array();
			if ($permit_file_type) {
				$find_flg = true;
			}else{
				$find_flg = false;
			}
			while(list($key, $val) = each($old_permit_file_type)) {
				if (trim($val)) {
					if (!@in_array($val, $permit_file_type)) $new_permit_file_type[] = $val;
				}
			}
			$permit_file_type = implode(",",$new_permit_file_type);
			$permit_html_tag = $initlist["permit_html_tag"];
			if ($find_flg) {
				$error = blogn_mod_db_init_Change(0, $sitename, $sitedesc, $timezone, $charset, $max_filesize, $permit_file_type, $max_view_width, $max_view_height, $permit_html_tag, $comment_size, $trackback_slash_type, $log_view_count, $mobile_view_count, $new_entry_view_count, $archive_view_count, $comment_view_count, $trackback_view_count, $comment_list_topview_on, $trackback_list_topview_on, $session_time, $cookie_time, $limit_comment, $limit_trackback, $monthly_view_mode, $category_view_mode);
			}else{
				$error[0] = false;
				$error[1] = "削除するファイルタイプを選択してください。";
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "html_add":
			$permit_file_type = $initlist["permit_file_type"];
			$old_permit_html_tag = explode(",",$initlist["permit_html_tag"]);
			$new_permit_html_tag = array();
			if ($old_permit_html_tag) {
				while (list($key, $val) = each($old_permit_html_tag)) {
					if (trim($val)) {
						$new_permit_html_tag[] = trim($val);
					}
				}
			}
			$check_tag = explode(",",$permit_html_tag);
			if ($check_tag[0]) {
				while(list($key, $val) = each($check_tag)) {
					if (trim($val)) $new_permit_html_tag[] = trim($val);
				}
				$permit_html_tag = implode(",",$new_permit_html_tag);
				$error = blogn_mod_db_init_Change(0, $sitename, $sitedesc, $timezone, $charset, $max_filesize, $permit_file_type, $max_view_width, $max_view_height, $permit_html_tag, $comment_size, $trackback_slash_type, $log_view_count, $mobile_view_count, $new_entry_view_count, $archive_view_count, $comment_view_count, $trackback_view_count, $comment_list_topview_on, $trackback_list_topview_on, $session_time, $cookie_time, $limit_comment, $limit_trackback, $monthly_view_mode, $category_view_mode);
			}else{
				$error[0] = false;
				$error[1] = "有効にするHTMLタグを書いてから追加ボタンを押してください。";
				$permit_html_tag = $initlist["permit_html_tag"];
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "html_del":
			$permit_file_type = $initlist["permit_file_type"];
			$old_permit_html_tag = explode(",",$initlist["permit_html_tag"]);
			$new_permit_html_tag = array();
			if ($permit_html_tag) {
				$find_flg = true;
			}else{
				$find_flg = false;
			}
			while(list($key, $val) = each($old_permit_html_tag)) {
				if (trim($val)) {
					if (!@in_array($val, $permit_html_tag)) $new_permit_html_tag[] = $val;
				}
			}
			$permit_html_tag = implode(",",$new_permit_html_tag);
			if ($find_flg) {
				$error = blogn_mod_db_init_Change(0, $sitename, $sitedesc, $timezone, $charset, $max_filesize, $permit_file_type, $max_view_width, $max_view_height, $permit_html_tag, $comment_size, $trackback_slash_type, $log_view_count, $mobile_view_count, $new_entry_view_count, $archive_view_count, $comment_view_count, $trackback_view_count, $comment_list_topview_on, $trackback_list_topview_on, $session_time, $cookie_time, $limit_comment, $limit_trackback, $monthly_view_mode, $category_view_mode);
			}else{
				$error[0] = false;
				$error[1] = "削除するHTMLタグを選択してください。";
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			$permit_file_type = $initlist["permit_file_type"];
			$permit_html_tag = $initlist["permit_html_tag"];
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
	}

	$initlist = blogn_mod_db_init_load();

	$sitename = htmlspecialchars($initlist["sitename"]);
	$sitedesc = htmlspecialchars($initlist["sitedesc"]);
	$timezone = $initlist["timezone"];
	$charset = $initlist["charset"];
	$max_filesize = $initlist["max_filesize"];
	$max_view_width = $initlist["max_view_width"];
	$max_view_height = $initlist["max_view_height"];
	$comment_size = (int)$initlist["comment_size"];
	$trackback_slash_type = (int)$initlist["trackback_slash_type"];
	$log_view_count = (int)$initlist["log_view_count"];
	$mobile_view_count = (int)$initlist["mobile_view_count"];
	$new_entry_view_count = (int)$initlist["new_entry_view_count"];
	$archive_view_count = (int)$initlist["archive_view_count"];
	$comment_view_count = (int)$initlist["comment_view_count"];
	$trackback_view_count = (int)$initlist["trackback_view_count"];
	$comment_list_topview_on = (int)$initlist["comment_list_topview_on"];
	$trackback_list_topview_on = (int)$initlist["trackback_list_topview_on"];
	$session_time = (int)$initlist["session_time"];
	$cookie_time = (int)$initlist["cookie_time"];

	$limit_comment = (int)$initlist["limit_comment"];
	$limit_trackback = (int)$initlist["limit_trackback"];

	$monthly_view_mode = (int)$initlist["monthly_view_mode"];
	$category_view_mode = (int)$initlist["category_view_mode"];

	$blogn_skin = str_replace ("{BLOGN_SITENAME}", $sitename, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_SITEDESC}", $sitedesc, $blogn_skin);

	$echo_val = "";
	for ($i = -12; $i <= 13; $i++) {
		if ($i == $timezone) {
			$select = " selected";
		}else{
			$select = "";
		}
		$echo_val .= '<option value="'.$i.'"'.$select.'>GMT ';
		if ($i < 0) {
			$echo_val .= $i.' Hours</option>';
		}elseif ($i == 0) {
			$echo_val .= '</option>';
		}elseif ($i == 9) {
			$echo_val .= '+'.$i.' Hours [東京・大阪・札幌]</option>';
		}else{
			$echo_val .= '+'.$i.' Hours</option>';
		}
	}

	$blogn_skin = str_replace ("{BLOGN_TIMEZONE}", $echo_val, $blogn_skin);


	$select1 = $select2 = $select3 = "";
	if ($charset == 0) {
		$select1 = " selected";
	}elseif ($charset == 1) {
		$select2 = " selected";
	}elseif ($charset == 2) {
		$select3 = " selected";
	}
	$echo_val = '
		<option value="0"'.$select1.'>Shift_JIS</option>
		<option value="1"'.$select2.'>EUC-JP</option>
		<option value="2"'.$select3.'>UTF-8</option>
	';

	$blogn_skin = str_replace ("{BLOGN_CHARSET}", $echo_val, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_MAX_FILESIZE}", $max_filesize, $blogn_skin);

	$permit_files = explode(",",$permit_file_type);
	@sort($permit_files);
	$echo_val = "";
	while(list($key, $val) = each($permit_files)) {
		if (trim($val)) $echo_val .= "<option value='{$val}'>{$val}</option>\n";
	}
	$blogn_skin = str_replace ("{BLOGN_PERMIT_FILE_TYPE}", $echo_val, $blogn_skin);

	$permit_htmls = explode(",",$permit_html_tag);
	@sort($permit_htmls);
	$echo_val = "";
	while(list($key, $val) = each($permit_htmls)) {
		if (trim($val)) $echo_val .= "<option value='{$val}'>{$val}</option>\n";
	}
	$blogn_skin = str_replace ("{BLOGN_PERMIT_HTML_TAG}", $echo_val, $blogn_skin);


	$blogn_skin = str_replace ("{BLOGN_MAX_VIEW_WIDTH}", $max_view_width, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_MAX_VIEW_HEIGHT}", $max_view_height, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_COMMENT_SIZE}", $comment_size, $blogn_skin);

	if ($trackback_slash_type) {
		$echo_val = ' checked';
	}else{
		$echo_val = '';
	}
	$blogn_skin = str_replace ("{BLOGN_TRACKBACK_SLASH_CHECK}", $echo_val, $blogn_skin);

	$tmp_limit_comment = array(0 => "無期限", 1 => "1日", 2 => "2日", 3 => "3日", 4 => "4日", 5 => "5日", 6 => "6日", 7 => "7日", 8 => "8日", 9 => "9日", 10 => "10日", 15 => "15日", 30 => "30日", 60 => "60日", 90 => "90日", 120 => "120日", 240 => "240日", 365 => "1年");
	$echo_val = "";
	while(list($key, $val) = each($tmp_limit_comment)) {
		if ($key == $limit_comment) {
			$select = " selected";
		}else{
			$select = "";
		}
		$echo_val .= '<option value="'.$key.'"'.$select.'>'.$val.'</option>';
	}
	$blogn_skin = str_replace ("{BLOGN_LIMIT_COMMENT}", $echo_val, $blogn_skin);


	$tmp_limit_trackback = array(0 => "無期限", 1 => "1日", 2 => "2日", 3 => "3日", 4 => "4日", 5 => "5日", 6 => "6日", 7 => "7日", 8 => "8日", 9 => "9日", 10 => "10日", 15 => "15日", 30 => "30日", 60 => "60日", 90 => "90日", 120 => "120日", 240 => "240日", 365 => "1年");
	$echo_val = "";
	while(list($key, $val) = each($tmp_limit_trackback)) {
		if ($key == $limit_trackback) {
			$select = " selected";
		}else{
			$select = "";
		}
		$echo_val .= '<option value="'.$key.'"'.$select.'>'.$val.'</option>';
	}
	$blogn_skin = str_replace ("{BLOGN_LIMIT_TRACKBACK}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ($i = 1;$i < 11; $i++) {
		if ($i == $log_view_count) {
			$select = " selected";
		}else{
			$select = "";
		}
		$echo_val .= '<option value="'.$i.'"'.$select.'>'.$i.'件</option>';
	}
	$blogn_skin = str_replace ("{BLOGN_LOG_VIEW_COUNT}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ($i = 1;$i < 11; $i++) {
		if ($i == $mobile_view_count) {
			$select = " selected";
		}else{
			$select = "";
		}
		$echo_val .= '<option value="'.$i.'"'.$select.'>'.$i.'件</option>';
	}
	$blogn_skin = str_replace ("{BLOGN_MOBILE_VIEW_COUNT}", $echo_val, $blogn_skin);

	$echo_val = "";
	if ($monthly_view_mode) {
		$select0 = "";
		$select1 = " selected";
	}else{
		$select0 = " selected";
		$select1 = "";
	}
	$echo_val .= '<option value="0"'.$select0.'>降順</option>';
	$echo_val .= '<option value="1"'.$select1.'>昇順</option>';
	$blogn_skin = str_replace ("{BLOGN_MONTHLY_VIEW_MODE}", $echo_val, $blogn_skin);

	$echo_val = "";
	if ($category_view_mode) {
		$select0 = "";
		$select1 = " selected";
	}else{
		$select0 = " selected";
		$select1 = "";
	}
	$echo_val .= '<option value="0"'.$select0.'>降順</option>';
	$echo_val .= '<option value="1"'.$select1.'>昇順</option>';
	$blogn_skin = str_replace ("{BLOGN_CATEGORY_VIEW_MODE}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ($i = 1;$i < 21; $i++) {
		if ($i == $new_entry_view_count) {
			$select = " selected";
		}else{
			$select = "";
		}
		$echo_val .= '<option value="'.$i.'"'.$select.'>'.$i.'件</option>';
	}
	$blogn_skin = str_replace ("{BLOGN_NEW_ENTRY_VIEW_COUNT}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ($i = 0;$i < 13; $i++) {
		if ($i == $archive_view_count) {
			$select = " selected";
		}else{
			$select = "";
		}
		if ($i == 0) {
			$j = "無制限";
		}else{
			$j = $i.'件';
		}
		$echo_val .= '<option value="'.$i.'"'.$select.'>'.$j.'</option>';
	}
	$blogn_skin = str_replace ("{BLOGN_ARCHIVE_VIEW_COUNT}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ($i = 1;$i < 31; $i++) {
		if ($i == $comment_view_count) {
			$select = " selected";
		}else{
			$select = "";
		}
		$echo_val .= '<option value="'.$i.'"'.$select.'>'.$i.'件</option>';
	}
	$blogn_skin = str_replace ("{BLOGN_COMMENT_VIEW_COUNT}", $echo_val, $blogn_skin);

	$echo_val = "";
	for ($i = 1;$i < 31; $i++) {
		if ($i == $trackback_view_count) {
			$select = " selected";
		}else{
			$select = "";
		}
		$echo_val .= '<option value="'.$i.'"'.$select.'>'.$i.'件</option>';
	}
	$blogn_skin = str_replace ("{BLOGN_TRACKBACK_VIEW_COUNT}", $echo_val, $blogn_skin);

	$select0 = $select1 = $select2 = $select3 = "";
	if ($comment_list_topview_on == 0) {
		$select0 = " selected";
	}elseif ($comment_list_topview_on == 1){
		$select1 = " selected";
	}elseif ($comment_list_topview_on == 2){
		$select2 = " selected";
	}elseif ($comment_list_topview_on == 3){
		$select3 = " selected";
	}
	$echo_val = '
<option value="0"'.$select0.'>件数表示</option>
<option value="1"'.$select1.'>一覧表示</option>
<option value="2"'.$select2.'>最新5件表示</option>
';
	$blogn_skin = str_replace ("{BLOGN_COMMENT_LIST_TOPVIEW_ON}", $echo_val, $blogn_skin);

	$select0 = $select1 = $select2 = $select3 = "";
	if ($trackback_list_topview_on == 0) {
		$select0 = " selected";
	}elseif ($trackback_list_topview_on == 1){
		$select1 = " selected";
	}elseif ($trackback_list_topview_on == 2){
		$select2 = " selected";
	}elseif ($trackback_list_topview_on == 3){
		$select3 = " selected";
	}
	$echo_val = '
<option value="0"'.$select0.'>件数表示</option>
<option value="1"'.$select1.'>一覧表示</option>
<option value="2"'.$select2.'>最新5件表示</option>
';

	$blogn_skin = str_replace ("{BLOGN_TRACKBACK_LIST_TOPVIEW_ON}", $echo_val, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_SESSION_TIME}", $session_time, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_COOKIE_TIME}", $cookie_time, $blogn_skin);

	echo $blogn_skin;
}


//-------------------------------------------------------------------- データ管理管理

function blogn_data_control($admin, $action, $upfile, $export_data) {
	if (!$admin) return;
	$blogn_skin = file("./template/data.html");
	$blogn_skin = implode("",$blogn_skin);

	if ($action == "import") {
		$error = blogn_data_import($admin, $upfile);
		// インフォメーション表示
		$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
	}else{
		$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
	}

	echo $blogn_skin;
}


//-------------------------------------------------------------------- データインポート処理
function blogn_data_import($admin, $upfile) {
	if (move_uploaded_file($upfile["tmp_name"],BLOGN_INIDIR."importfile.dat")) {
		$newrecord = file(BLOGN_INIDIR."importfile.dat");
		unlink(BLOGN_INIDIR."importfile.dat");
	}else{
		$error[0] = false;
		$error[1] = "アップロードファイルの書き込みに失敗しました。[BLOGN_INIDIR]ディレクトリの属性を確認してください。";
		return $error;
	}
	if (!$newrecord) {
		$error[0] = false;
		$error[1] = "インポートするファイルを選んでから実行してください。";
		return $error;
	}
	$flg = $c_flg = 0;
	$body_flg = $ex_body_flg = false;
	$logdata = $cmtdata = $trkdata = array();
	$cmtflg = $trkflg = -1;
	while(list($key, $val) = each($newrecord)) {
		$val = blogn_mbConv($val, 0, 4);
		if (ereg("^--------\n", $val)) {
			$val = "";
			$flg = 3;
		}elseif (ereg("^BODY:", $val)) {
			$val = "";
			$body_flg = true;
		}elseif (ereg("^EXTENDED BODY:", $val)) {
			$val = "";
			$ex_body_flg = true;
		}elseif (ereg("^COMMENT:", $val)) {
			$val = "";
			$flg = 1;
			$cmt_flg++;
		}elseif (ereg("^PING:", $val)) {
			$val = "";
			$flg = 2;
			$trk_flg++;
		}
		switch ($flg) {
			case "0":
				// 記事処理
				if (!$body_flg && !$ex_body_flg) $val = ereg_replace("\n$", "", $val);	// 記事本文以外は改行削除
				if (ereg("^TITLE: ", $val)) $logdata["title"] = ereg_replace("^TITLE: ", "", $val);
				if (ereg("^AUTHOR: ", $val)) $logdata["author"] = ereg_replace("^AUTHOR: ", "", $val);
				if (ereg("^DATE: ", $val)) $logdata["date"] = ereg_replace("^DATE: ", "", $val);
				if (ereg("^PRIMARY CATEGORY: ", $val)) {
					$logdata["p_category"] = ereg_replace("PRIMARY CATEGORY: ", "", $val);
					$logdata["category"] = "";
					$c_flg = 1;
				}
				if (ereg("^CATEGORY: ", $val)) {
					if ($c_flg == 0) {
						$logdata["p_category"] = ereg_replace("CATEGORY: ", "", $val);
						$logdata["category"] = "";
						$c_flg = 1;
					}else{
						$logdata["category"] = ereg_replace("CATEGORY: ", "", $val);
					}
				}
				if (ereg("^ALLOW COMMENTS: ", $val)) $logdata["comment_ok"] = ereg_replace("^ALLOW COMMENTS: ", "", $val);
				if (ereg("^ALLOW PINGS: ", $val)) $logdata["trackback_ok"] = ereg_replace("^ALLOW PINGS: ", "", $val);
				if (ereg("^STATUS: ", $val)) $logdata["status"] = ereg_replace("^STATUS: ", "", $val);
				if ($body_flg) {
					if (ereg("-----\n", $val)) {
						$body_flg = false;
					}else{
						$logdata["body"] .= $val;
					}
				}
				if ($ex_body_flg) {
					if (ereg("-----\n", $val)) {
						$ex_body_flg = false;
					}else{
						$logdata["ex_body"] .= $val;
					}
				}
				break;
			case "1":
				// コメント処理
				if (!ereg("^AUTHOR: ", $val) && !ereg("^DATE: ", $val) && !ereg("^IP: ", $val) && !ereg("^AGENT: ", $val) && !ereg("^EMAIL: ", $val) && !ereg("^URL: ", $val) && !ereg("^-----\n", $val)) {
					$cmtdata[$cmt_flg]["comment"] .= $val;
				}else{
					$val = ereg_replace("\n$", "", $val);
				}
				if (ereg("^AUTHOR: ", $val)) $cmtdata[$cmt_flg]["author"] = ereg_replace("^AUTHOR: ", "", $val);
				if (ereg("^DATE: ", $val)) $cmtdata[$cmt_flg]["date"] = ereg_replace("^DATE: ", "", $val);
				if (ereg("^IP: ", $val)) $cmtdata[$cmt_flg]["ip"] = ereg_replace("^IP: ", "", $val);
				if (ereg("^AGENT: ", $val)) $cmtdata[$cmt_flg]["agent"] = ereg_replace("^AGENT: ", "", $val);
				if (ereg("^EMAIL: ", $val)) $cmtdata[$cmt_flg]["email"] = ereg_replace("^EMAIL: ", "", $val);
				if (ereg("^URL: ", $val)) $cmtdata[$cmt_flg]["url"] = ereg_replace("^URL: ", "", $val);
				break;
			case "2":
				// トラックバック処理
				if (!ereg("^TITLE: ", $val) && !ereg("^URL: ", $val) && !ereg("^IP: ", $val) && !ereg("^BLOG NAME: ", $val) && !ereg("^DATE: ", $val) && !ereg("^-----\n", $val)) {
					$trkdata[$trk_flg]["trackback"] .= $val;
				}else{
					$val = ereg_replace("\n$", "", $val);
				}
				if (ereg("^TITLE: ", $val)) $trkdata[$trk_flg]["title"] = ereg_replace("^TITLE: ", "", $val);
				if (ereg("^URL: ", $val)) $trkdata[$trk_flg]["url"] = ereg_replace("^URL: ", "", $val);
				if (ereg("^IP: ", $val)) $trkdata[$trk_flg]["ip"] = ereg_replace("^IP: ", "", $val);
				if (ereg("^BLOG NAME: ", $val)) $trkdata[$trk_flg]["blogname"] = ereg_replace("^BLOG NAME: ", "", $val);
				if (ereg("^DATE: ", $val)) $trkdata[$trk_flg]["date"] = ereg_replace("^DATE: ", "", $val);
				break;
			case "3":
				// 各種データ保存
				/* ユーザー検索 */
				$userlist = blogn_mod_db_user_load();
				$user_found = false;
				reset($userlist);
				$i = 0;
				while(list($userkey, $userval) = each($userlist)) {
					if ($i == 0) $newuserid = $userkey;
					if ($logdata["author"] == $userval["name"]) {
						$user_id = $userkey;
						$user_found = true;
						break;
					}
					$i++;
				}
				if (!$user_found) $user_id = $newuserid;
				/* 投稿日処理 */
				list($tmp_date, $tmp_time, $ampm) = explode(" ", $logdata["date"]);
				list($month, $day, $year) = explode("/", $tmp_date);
				list($hour, $minutes, $second) = explode(":", $tmp_time);
				if (trim($ampm) == "PM") $hour = $hour + 12;
				$date = sprintf("%4d%02d%02d%02d%02d%02d", $year, $month, $day, $hour, $minutes, $second);
				if (eregi("draft", $logdata["status"])) {
					$secret = "1";
					$logdata["trackback_ok"] = "0";
				}else{
					 $secret = "0";
				}
				/* カテゴリー検索 */
				$c1list = blogn_mod_db_category1_load();
				$c2list = blogn_mod_db_category2_load();
				$c1_found = false;

				// MT用処理（サブカテゴリが無い場合、MTはメイン、サブ共に同じカテゴリ名を吐く為）
				if ($logdata["p_category"] == $logdata["category"]) {
					$logdata["category"] = "";
				}

				if ($c1list[0]) {
					reset($c1list[1]);
					while (list($c1key, $c1val) = each($c1list[1])) {
						if ($logdata["p_category"] == $c1val["name"]) {
							$c1id = $c1key;
							$c1_found = true;
							break;
						}
					}
				}
				if (!$c1_found) {
					$error = blogn_mod_db_category1_add($logdata["p_category"]);
					$c1id = $error[2];
					if ($logdata["category"] != "") {
						$error = blogn_mod_db_category2_add($c1id, $logdata["category"]);
						$c2id = $error[2];
					}
				}else{
					if ($logdata["category"] != "") {
						$c2_found = false;
						if ($c2list[0]) {
						reset($c2list[1]);
							while (list($c2key, $c2val) = each($c2list[1])) {
								if ($logdata["category"] == $c2val["name"]) {
									$c2id = $c2key;
									$c2_found = true;
									break;
								}
							}
						}
						if (!$c2_found) {
							$error = blogn_mod_db_category2_add($c1id, $logdata["category"]);
							$c2id = $error[2];
						}
					}
				}
				$category_id = $c1id."|";
				if ($logdata["category"]) $category_id .= $c2id;

				// ex_body 中身チェック
				if (!$checklog = trim(ereg_replace("\n", "", $logdata["ex_body"]))) $logdata["ex_body"] = "";

				/* 記事書き込み */
				$error = blogn_mod_db_log_add($user_id, $date, "", $secret, $logdata["comment_ok"], $logdata["trackback_ok"], $category_id, $logdata["title"], blogn_mod_db_rn2br($logdata["body"]), blogn_mod_db_rn2br($logdata["ex_body"]), 1);
				$log_id = $error[2];

				if ($cmt_flg > -1) {
					while(list($cmtkey, $cmtval) = each($cmtdata)) {
						list($tmp_date, $tmp_time, $ampm) = explode(" ", $cmtval["date"]);
						list($month, $day, $year) = explode("/", $tmp_date);
						list($hour, $minutes, $second) = explode(":", $tmp_time);
						if (trim($ampm) == "PM") $hour = $hour + 12;
						$date = sprintf("%4d%02d%02d%02d%02d%02d", $year, $month, $day, $hour, $minutes, $second);
						$error = blogn_mod_db_comment_add($log_id, 0, $date, $cmtval["author"], $cmtval["email"], $cmtval["url"], blogn_mod_db_rn2br($cmtval["comment"]), $cmtval["ip"], $cmtval["agent"]);
					}
				}
				if ($trk_flg > -1) {
					while(list($trkkey, $trkval) = each($trkdata)) {
						list($tmp_date, $tmp_time, $ampm) = explode(" ", $trkval["date"]);
						list($month, $day, $year) = explode("/", $tmp_date);
						list($hour, $minutes, $second) = explode(":", $tmp_time);
						if (trim($ampm) == "PM") $hour = $hour + 12;
						$date = sprintf("%4d%02d%02d%02d%02d%02d", $year, $month, $day, $hour, $minutes, $second);
						$error = blogn_mod_db_trackback_add($log_id, $date, $trkval["blogname"], $trkval["title"], $trkval["url"], blogn_mod_db_rn2br($trkval["trackback"]), $trkval["ip"], "");
					}
				}


				$flg = $c_flg = 0;
				$cmt_flg = $trk_flg = -1;
				$logdata = $cmtdata = $trkdata = array();
				break;
		}
	}
	$error[0] = true;
	$error[1] = "データの取り込みに成功しました。";
	return $error;
}


//-------------------------------------------------------------------- データエクスポート処理

function blogn_data_export($admin, $file_charset) {
	$convdata = "";
	$loglist = blogn_mod_db_log_load_for_all();
	if (!$loglist[0]) return;
	$c1list = blogn_mod_db_category1_load();
	$c2list = blogn_mod_db_category2_load();
	while (list($key, $val) = each($loglist[1])) {
		$convdata .= "TITLE: ".$val["title"]."\n";
		$userdata = blogn_mod_db_user_profile_load($val["user_id"]);
		$convdata .= "AUTHOR: ".$userdata["name"]."\n";
		$convdata .= "DATE: ".substr($val["date"],4,2)."/".substr($val["date"],6,2)."/".substr($val["date"],0,4)." ".substr($val["date"],8,2).":".substr($val["date"],10,2).":".substr($val["date"],12,2)."\n";
		if ($val["secret"] == 0) {
			$status = "Publish";
		}else{
			$status = "Draft";
		}
		$convdata .= "STATUS: ".$status."\n";

		$convdata .= "CONVERT BREAKS: __default__\n";

		list($c1, $c2) = explode("|", $val["category"]);
		if ($c2 != "") {
			$convdata .= "PRIMARY CATEGORY: ".$c1list[1][$c1]["name"]."\n";
			$convdata .= "CATEGORY: ".$c2list[1][$c2]["name"]."\n";
		}else{
			$convdata .= "CATEGORY: ".$c1list[1][$c1]["name"]."\n";
		}
		if ($val["comment_ok"] == "1") {
			$convdata .= "ALLOW COMMENTS: 1\n";
		}else{
			$convdata .= "ALLOW COMMENTS: 0\n";
		}
		if ($val["trackback_ok"] == "1") {
			$convdata .= "ALLOW PINGS: 1\n";
		}else{
			$convdata .= "ALLOW PINGS: 0\n";
		}
		$convdata .= "-----\n";
		$mes = str_replace("<br />", "\n", $val["mes"]);
		$mes = str_replace("<br>", "\n", $mes);
		$convdata .= "BODY:\n".$mes."\n";
		$convdata .= "-----\n";
		$more = str_replace("<br />", "\n", $val["more"]);
		$more = str_replace("<br>", "\n", $more);
		$convdata .= "EXTENDED BODY:\n";
		if ($more) $convdata .= $more."\n";
		$convdata .= "-----\n";
		$convdata .= "EXCERPT:\n";
		$convdata .= "-----\n";
		$convdata .= "KEYWORDS:\n";
		$convdata .= "-----\n";

		$cmtlist = blogn_mod_db_comment_load_for_list($val["id"], 0, 0);
		if ($cmtlist[0]) {
			while(list($cmtkey, $cmtval) = each($cmtlist[1])) {
				$convdata .= "COMMENT:\n";
				$convdata .= "AUTHOR: ".$cmtval["name"]."\n";
				$convdata .= "DATE: ".substr($cmtval["date"],4,2)."/".substr($cmtval["date"],6,2)."/".substr($cmtval["date"],0,4)." ".substr($cmtval["date"],8,2).":".substr($cmtval["date"],10,2).":".substr($cmtval["date"],12,2)."\n";
				if ($cmtval["ip"]) $convdata .= "IP: ".$cmtval["ip"]."\n";
				if ($cmtval["email"]) $convdata .= "EMAIL: ".$cmtval["email"]."\n";
				if ($cmtval["url"]) $convdata .= "URL: ".$cmtval["url"]."\n";
				$comment = str_replace("<br />", "\n", $cmtval["comment"]);
				$comment = str_replace("<br>", "\n", $comment);
				$convdata .= $comment."\n";
				$convdata .= "-----\n";
			}
		}

		$trklist = blogn_mod_db_trackback_load_for_list($val["id"], 0, 0);
		if ($trklist[0]) {
			while(list($trkkey, $trkval) = each($trklist[1])) {
				$convdata .= "PING:\n";
				$convdata .= "TITLE: ".$trkval["title"]."\n";
				$convdata .= "URL: ".$trkval["url"]."\n";
				if ($trkval["ip"]) $convdata .= "IP: ".$trkval["ip"]."\n";
				$convdata .= "BLOG NAME: ".$trkval["name"]."\n";
				$convdata .= "DATE: ".substr($trkval["date"],4,2)."/".substr($trkval["date"],6,2)."/".substr($trkval["date"],0,4)." ".substr($trkval["date"],8,2).":".substr($trkval["date"],10,2).":".substr($trkval["date"],12,2)."\n";
				$excerpt = str_replace("<br />", "\n", $trkval["mes"]);
				$excerpt = str_replace("<br>", "\n", $excerpt);
				$convdata .= $excerpt."\n";
				$convdata .= "-----\n";
			}
		}
		$convdata .= "--------\n";
	}

	if ($file_charset != "4") $convdata = blogn_mbConv($convdata, 4, $file_charset);

	header("Content-type: text/plain name=log_mt_mode.txt"); 
	header("Content-Disposition: attachment; filename=log_mt_mode.txt"); 
	echo $convdata;
	flush();
	exit;
}


//-------------------------------------------------------------------- スキン追加／編集管理

function blogn_skin_control($admin, $action, $skin_id, $skin_name, $html_file_name, $css_file_name, $html_upfile, $css_upfile) {
	if (!$admin) return;
	$blogn_skin = file("./template/skinlist.html");
	$blogn_skin = implode("",$blogn_skin);

	// 処理選択
	switch ($action) {
		case "new":
			if (empty($html_upfile) || empty($css_upfile)) {
				$error[0] = false;
				$error[1] = "アップロードするファイル名を入力してから追加ボタンを押してください。";
			}elseif (ereg("[\xA1-\xFE]", blogn_mbConv($html_upfile["name"], 4, 1)) || ereg("[\xA1-\xFE]", blogn_mbConv($css_upfile["name"], 4, 1))) {
				$error[0] = false;
				$error[1] = "日本語文字を含むファイル名はアップロードできません。半角英数文字でアップロードしてください。";
			}else{
				$html_file_name = $html_upfile["name"];
				$css_file_name = $css_upfile["name"];
				$html_dest = BLOGN_SKINDIR.$html_file_name;
				$css_dest = BLOGN_SKINDIR.$css_file_name;
				$html_pathname = pathinfo($html_dest);
				$css_pathname = pathinfo($css_dest);

				$html_check_ext = "htm|html";
				$css_check_ext = "css";
				if (!eregi($html_check_ext, $html_pathname['extension']) || !eregi($css_check_ext, $css_pathname['extension'])) {
					$error[0] = false;
					$error[1] = "許可されていないファイルタイプです。アップロードを中止します。";
				}else{
					if (file_exists($html_dest)) {
						$html_file_name = gmdate("YmdHis",time() + BLOGN_TIMEZONE).".".$html_pathname['extension'];
						$html_dest = BLOGN_SKINDIR.$html_file_name;
					}
					if (file_exists($css_dest)) {
						$css_file_name = gmdate("YmdHis",time() + BLOGN_TIMEZONE).".".$css_pathname['extension'];
						$css_dest = BLOGN_SKINDIR.$css_file_name;
					}
					$oldmask = umask();
					umask(000);
					if (!$error = @move_uploaded_file($html_upfile["tmp_name"], $html_dest)) {
						$error[0] = false;
						$error[1] = BLOGN_SKINDIR." ディレクトリにファイルを保存できませんでした。パーミッションを確認してください。";
					}elseif (!$error = @move_uploaded_file($css_upfile["tmp_name"], $css_dest)) {
						$error[0] = false;
						$error[1] = BLOGN_SKINDIR." ディレクトリにファイルを保存できませんでした。パーミッションを確認してください。";
					}else{
						$error = blogn_mod_db_skin_add($skin_name, $html_file_name, $css_file_name);
						chmod($html_dest,0666);
					}
					umask($oldmask);
				}
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "update":
			$error = blogn_mod_db_skin_edit($skin_id, $skin_name, $html_file_name, $css_file_name);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "delete":
			$skinlist = blogn_mod_db_skin_load();
			$error = blogn_mod_db_skin_delete($skin_id);
			if ($error[0]) {
				if (!@unlink(BLOGN_SKINDIR.$skinlist[1][$skin_id]["html_name"]) || !@unlink(BLOGN_SKINDIR.$skinlist[1][$skin_id]["css_name"])) {
					$error[0] = false;
					$error[1] = "スキンの削除に失敗しました。";
				}
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default;
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	$skinlist = blogn_mod_db_skin_load();
	if ($skinlist[0]) {
		$blogn_skin = preg_replace("/\{BLOGN_SKIN_LIST_ELSE\}[\w\W]+?\{\/BLOGN_SKIN_LIST\}/", "", $blogn_skin);
		preg_match("/\{BLOGN_SKIN_LIST_LOOP\}([\w\W]+?)\{\/BLOGN_SKIN_LIST_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_skin_list_all = "";
		while (list($key, $val) = each($skinlist[1])) {
			$blogn_skin_list = $blogn_reg[0];
			$blogn_skin_list = str_replace ("{BLOGN_SKIN_LIST_LOOP}", "", $blogn_skin_list);
			$blogn_skin_list = str_replace ("{/BLOGN_SKIN_LIST_LOOP}", "", $blogn_skin_list);

			$htm_time = date('Y/m/d H:i', filemtime(BLOGN_SKINDIR.$val["html_name"]));
			$css_time = date('Y/m/d H:i', filemtime(BLOGN_SKINDIR.$val["css_name"]));

			$blogn_skin_list = str_replace ("{BLOGN_SKIN_ID}", $key, $blogn_skin_list);
			$blogn_skin_list = str_replace ("{BLOGN_SKIN_NAME}", $val["skin_name"], $blogn_skin_list);
			$blogn_skin_list = str_replace ("{BLOGN_HTML_NAME}", $val["html_name"], $blogn_skin_list);
			$blogn_skin_list = str_replace ("{BLOGN_HTML_TIME}", $htm_time, $blogn_skin_list);
			$blogn_skin_list = str_replace ("{BLOGN_CSS_NAME}", $val["css_name"], $blogn_skin_list);
			$blogn_skin_list = str_replace ("{BLOGN_CSS_TIME}", $css_time, $blogn_skin_list);
			$blogn_skin_list_all .= $blogn_skin_list;
		}
		$blogn_skin = preg_replace("/\{BLOGN_SKIN_LIST_LOOP\}[\w\W]+?\{\/BLOGN_SKIN_LIST_LOOP\}/", $blogn_skin_list_all, $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_SKIN_LIST}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_SKIN_LIST_ELSE\}[\w\W]+?\{\/BLOGN_SKIN_LIST\}/", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_SKIN_LIST\}[\w\W]+?\{BLOGN_SKIN_LIST_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_SKIN_LIST}", "", $blogn_skin);
	}
	echo $blogn_skin;
}


//-------------------------------------------------------------------- スキン編集画面

function blogn_skin_editor($admin, $action, $skin_id, $skin_name, $html_name, $css_name, $html_file_data, $css_file_data) {
	if (!$admin) return;
	$blogn_skin = file("./template/skinedit.html");
	$blogn_skin = implode("",$blogn_skin);

	//処理選択
	switch ($action) {
		case "html_update":
			$html_file_data = get_magic_quotes_gpc() ? stripslashes($html_file_data) : $html_file_data;				//￥を削除
			$css_file_data = get_magic_quotes_gpc() ? stripslashes($css_file_data) : $css_file_data;				//￥を削除

			if ($fp = @fopen(BLOGN_SKINDIR.$html_name, "w")) {
				flock($fp, LOCK_EX);
				fputs($fp, $html_file_data);
				fclose($fp);
				$error[0] = true;
				$error[1] = "HTMLを更新しました。";
			}else{
				$error[0] = false;
				$error[1] = "HTMLの更新に失敗しました。";
			}
			// インフォメーション表示
			$html_file_data = blogn_html_tag_convert($html_file_data);
			$css_file_data = blogn_html_tag_convert($css_file_data);

			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "css_update":
			$html_file_data = get_magic_quotes_gpc() ? stripslashes($html_file_data) : $html_file_data;				//￥を削除
			$css_file_data = get_magic_quotes_gpc() ? stripslashes($css_file_data) : $css_file_data;				//￥を削除

			//
			if (BLOGN_CHARSET == 0) {
				//S-JIS
				$save_css_file = blogn_mbConv($css_file_data, 4, 2);
				$save_css_file = stripslashes($save_css_file);				//￥を削除
			}elseif (BLOGN_CHARSET == 1) {
				//EUC-JP
				$save_css_file = blogn_mbConv($css_file_data, 4, 1);
			}else{
				//UTF-8
				$save_css_file = $css_file_data;
			}

			if ($fp = @fopen(BLOGN_SKINDIR.$css_name, "w")) {
				flock($fp, LOCK_EX);
				fputs($fp, $save_css_file);
				fclose($fp);
				$error[0] = true;
				$error[1] = "CSSを更新しました。";
			}else{
				$error[0] = false;
				$error[1] = "CSSの更新に失敗しました。";
			}
			// インフォメーション表示
			$html_file_data = blogn_html_tag_convert($html_file_data);
			$css_file_data = blogn_html_tag_convert($css_file_data);

			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			$skinlist = blogn_mod_db_skin_load();
			$skin_name = $skinlist[1][$skin_id]["skin_name"];
			$html_name = $skinlist[1][$skin_id]["html_name"];
			$css_name = $skinlist[1][$skin_id]["css_name"];

			$html_file_array = file(BLOGN_SKINDIR.$html_name);
			$html_file_data = blogn_mbConv(implode("", $html_file_array), 0, 4);
			$html_file_data = preg_replace('/[\t]+?/', '  ', $html_file_data);		// TAB変換

			$css_file_array = file(BLOGN_SKINDIR.$css_name);
			$css_file_data = blogn_mbConv(implode("", $css_file_array), 0, 4);
			$css_file_data = preg_replace('/[\t]+?/', '  ', $css_file_data);			// TAB変換

			$html_file_data = blogn_html_tag_convert($html_file_data);
			$css_file_data = blogn_html_tag_convert($css_file_data);

			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	$html_time = date('Y/m/d H:i:s', filemtime(BLOGN_SKINDIR.$html_name));
	$css_time = date('Y/m/d H:i:s', filemtime(BLOGN_SKINDIR.$css_name));

	$blogn_skin = str_replace ("{BLOGN_SKIN_ID}", $skin_id, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_SKIN_NAME}", $skin_name, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_HTML_TIME}", $html_time, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_HTML_NAME}", $html_name, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_HTML_FILE}", $html_file_data, $blogn_skin);

	$blogn_skin = str_replace ("{BLOGN_CSS_TIME}", $css_time, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_CSS_NAME}", $css_name, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_CSS_FILE}", $css_file_data, $blogn_skin);

	echo $blogn_skin;
}


//-------------------------------------------------------------------- スキン用画像一覧画面

function blogn_skin_file_control($admin, $action, $page, $file_name, $upfile) {
	if (!$admin) return;
	$blogn_skin = file("./template/skinfiles.html");
	$blogn_skin = implode("",$blogn_skin);

	switch ($action) {
		case"post":
			$error[0] = true;
			$i = 0;
			while(list($key, $val) = each($upfile)) {
				if ($i == 0) {
					$uploadfile["name"] = $val;
				}elseif ($i == 2) {
					$uploadfile["tmp_name"] = $val;
				}
				$i++;
			}

			if ($error[0]) {
				while(list($key, $val) = each($uploadfile["name"])) {
					if(ereg("[\xA1-\xFE]", $val)) {
						$error[0] = false;
						$error[1] = "日本語文字を含むファイル名はアップロードできません。";
					}else{
						if (!empty($val)) {
							$dest = BLOGN_SKINPICDIR.$val;
							if (file_exists($dest)) {
								$pathname = pathinfo($dest);
								$dest = BLOGN_SKINPICDIR.gmdate("YmdHis",time() + BLOGN_TIMEZONE).$pathname['extension'];
							}
							$oldmask = umask();
							umask(000);
								if ($err = @move_uploaded_file($uploadfile["tmp_name"][$key], $dest)) {
									$error[0] = true;
									$error[1] = "画像のアップロードに成功しました。";
								}else{
									$error[0] = false;
									$error[1] = "画像のアップロードに失敗しました。";
								}
								@chmod($dest,0666);
							umask($oldmask);
						}
					}
				}
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "delete":
			if (!@unlink(BLOGN_SKINPICDIR.$file_name)) {
				$error[0] = false;
				$error[1] = "ファイルの削除に失敗しました。";
			}else{
				$error[0] = true;
				$error[1] = "ファイルを削除しました。";
			}
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	if ($page == "") $page = 1;

	//$pageから表示開始位置を算出
	$pagecount = 5;

	$page_st = ($page - 1) * $pagecount;
	$page_ed = $page_st + $pagecount;

	$dir_name = dir(BLOGN_SKINPICDIR);
	$fname = array();
	$fsize = array();
	$ftime = array();
	$width = array();
	$i = 0;
	while($file_name = $dir_name->read()) {
		if (!is_dir($file_name)) {
			if ($size = @getimagesize(BLOGN_SKINPICDIR.$file_name)) {
				if ($size[2] == 1 || $size[2] == 2 || $size[2] == 3) {
					if ($size[0] < 80 && $size[1] < 80) {
						$width[$i] = $size[0];
						$height[$i] = $size[1];
					}elseif ($size[0] > $size[1]) {
						$width[$i] = 80;
						$height[$i] = round(80 * $size[1] / $size[0]);
					}else{
						$width[$i] = round(80 * $size[0] / $size[1]);
						$height[$i] = 80;
					}
					$fname[$i] = $file_name;
					$fsize[$i] = round(filesize(BLOGN_SKINPICDIR.$file_name) / 1024, 2);
					$ftime[$i] = date("Y/m/d H:i:s", filemtime(BLOGN_SKINPICDIR.$file_name));
					$i++;
				}
			}
		}
	}
	arsort($ftime);
	if (count($fname) != 0) {
		$blogn_skin = preg_replace("/\{BLOGN_SKIN_FILES_ELSE\}[\w\W]+?\{\/BLOGN_SKIN_FILES\}/", "", $blogn_skin);
		preg_match("/\{BLOGN_SKIN_FILES_LOOP\}([\w\W]+?)\{\/BLOGN_SKIN_FILES_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_skin_files_all = "";
		$i = 0;
		reset($ftime);
		while(list($key, $val) = each($ftime)) {
			if ($i >= $page_st && $i < $page_ed) {
				$blogn_skin_files = $blogn_reg[0];
				$blogn_skin_files = str_replace ("{BLOGN_SKIN_FILES_LOOP}", "", $blogn_skin_files);
				$blogn_skin_files = str_replace ("{/BLOGN_SKIN_FILES_LOOP}", "", $blogn_skin_files);

				$blogn_skin_files = str_replace ("{BLOGN_FILE_NAME}", $fname[$key], $blogn_skin_files);
				$blogn_skin_files = str_replace ("{BLOGN_FILE_URL}", BLOGN_SKINPICDIR.$fname[$key], $blogn_skin_files);
				$blogn_skin_files = str_replace ("{BLOGN_FILE_WIDTH}", $width[$key], $blogn_skin_files);
				$blogn_skin_files = str_replace ("{BLOGN_FILE_HEIGHT}", $height[$key], $blogn_skin_files);

				$blogn_skin_files = str_replace ("{BLOGN_FILE_SIZE}", $fsize[$key], $blogn_skin_files);
				$blogn_skin_files = str_replace ("{BLOGN_FILE_TIME}", $val, $blogn_skin_files);

				$blogn_skin_files_all .= $blogn_skin_files;
			}
			$i++;
		}
		$blogn_skin = preg_replace("/\{BLOGN_SKIN_FILES_LOOP\}[\w\W]+?\{\/BLOGN_SKIN_FILES_LOOP\}/", $blogn_skin_files_all, $blogn_skin);
		$blogn_skin = str_replace ("{BLOGN_SKIN_FILES}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_SKIN_FILES_ELSE\}[\w\W]+?\{\/BLOGN_SKIN_FILES\}/", "", $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_SKIN_FILES\}[\w\W]+?\{BLOGN_SKIN_FILES_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_SKIN_FILES}", "", $blogn_skin);
	}

	// ページ処理
	if (count($fname) != 0) {
		$max_page = ceil(count($fname) / $pagecount);
	}else{
		$max_page = 0;
	}

	if (count($fname) > $pagecount) {
		$echo_val = '<div>Page: ';
		for ($i = 0; $i < $max_page; $i++) {
			$j = $i + 1;
			if ($j == $page) {
				$echo_val .= '<a href="admin.php?mode=skinfile&amp;page='.$j.'">['.$j.']</a> ';
			}else{
				$echo_val .= '<a href="admin.php?mode=skinfile&amp;page='.$j.'">'.$j.'</a> ';
			}
		}
	}

	$blogn_skin = str_replace ("{BLOGN_PAGE}", $echo_val, $blogn_skin);

	echo $blogn_skin;
}


//-------------------------------------------------------------------- 表示スキン設定画面

function blogn_skin_changer_control($admin, $action, $view_type, $view_category, $view_section, $view_skin, $id) {
	if (!$admin) return;
	$blogn_skin = file("./template/skinview.html");
	$blogn_skin = implode("",$blogn_skin);
	// 処理選択
	switch ($action) {
		case "update":
			if ($view_type == 1 && !$view_skin) {
				$error[0] = false;
				$error[1] = "表示するスキンを最低１つ選んでください。";
			}else{
				$error = blogn_mod_db_viewskin_add($view_type, $view_category, $view_section, $view_skin);
			}
			// インフォメーション表示
			$viewskin = blogn_mod_db_viewskin_load();
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "add":
			// 一時的にview_typeを3に設定して追加処理を行う
			$error = blogn_mod_db_viewskin_add( 3, $view_category, $view_section, $view_skin);

			// インフォメーション表示
			$viewskin = blogn_mod_db_viewskin_load();
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "delete":
			$error = blogn_mod_db_viewskin_del($id);
			// インフォメーション表示
			$view_type = 2;
			$viewskin = blogn_mod_db_viewskin_load();
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			$viewskin = blogn_mod_db_viewskin_load();
			if ($view_type == "") {
				if ($viewskin[0]) {
					$view_type = $viewskin[1][0]["view_type"];
				}else{
					$view_type = 0;
					$viewskin = Array();
					$viewskin[1][0]["view_type"] = 0;
				}
			}else{
				if (!$viewskin[0]) {
					$viewskin = Array();
					$viewskin[1][0]["view_type"] = $viewtype;
				}
			}
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	$skinlist = blogn_mod_db_skin_load();
	if (!$skinlist[0]) {
		$blogn_skin = preg_replace("/\{BLOGN_SKIN\}[\w\W]+?\{BLOGN_SKIN_ELSE\}/", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_SKIN}", "", $blogn_skin);
		return;
	}


	for ($i = 0; $i > 2; $i++) {
		$blogn_here[$i] = "";
	}
	$blogn_here[$view_type] = ' id="here"';
	if ($viewskin[1][0]["view_type"] == 0) {
		$skinmode = "ノーマル表示";
	}elseif ($viewskin[1][0]["view_type"] == 1) {
		$skinmode = "ランダム表示";
	}elseif ($viewskin[1][0]["view_type"] == 2) {
		$skinmode = "ジャンル別表示";
	}

	$blogn_skin = str_replace ("{BLOGN_SKIN_MODE}", $skinmode, $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_SKIN_HERE0}", $blogn_here[0], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_SKIN_HERE1}", $blogn_here[1], $blogn_skin);
	$blogn_skin = str_replace ("{BLOGN_SKIN_HERE2}", $blogn_here[2], $blogn_skin);

	switch ($view_type) {
		case 0:
			$blogn_skin = preg_replace("/\{BLOGN_SKIN_ELSE\}[\w\W]+?\{\/BLOGN_SKIN\}/", "", $blogn_skin);
			$blogn_skin = preg_replace("/\{BLOGN_SKIN_TYPE_RANDOM\}[\w\W]+?\{\/BLOGN_SKIN_TYPE_CATEGORY\}/", "", $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_SKIN}", "", $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_SKIN_TYPE_NORMAL}", "", $blogn_skin);
			$blogn_skin = str_replace ("{/BLOGN_SKIN_TYPE_NORMAL}", "", $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_SKIN_MODE_NOW}", "ノーマル表示", $blogn_skin);

			$echo_val = "";
			reset($skinlist[1]);
			while(list($key, $val) = each($skinlist[1])) {
				if ($viewskin[1][0]["view_type"] == 0 && $viewskin[1][0]["skin_id"] == $key) {
					$select = " selected";
				}else{
					$select = "";
				}
				$echo_val .= '<option value="'.$key.'"'.$select.'>'.$val["skin_name"].'</option>';
			}
			$blogn_skin = str_replace ("{BLOGN_SKIN_OPTION}", $echo_val, $blogn_skin);
			break;
		case 1:
			$blogn_skin = preg_replace("/\{BLOGN_SKIN_ELSE\}[\w\W]+?\{\/BLOGN_SKIN\}/", "", $blogn_skin);
			$blogn_skin = preg_replace("/\{BLOGN_SKIN_TYPE_NORMAL\}[\w\W]+?\{\/BLOGN_SKIN_TYPE_NORMAL\}/", "", $blogn_skin);
			$blogn_skin = preg_replace("/\{BLOGN_SKIN_TYPE_CATEGORY\}[\w\W]+?\{\/BLOGN_SKIN_TYPE_CATEGORY\}/", "", $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_SKIN}", "", $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_SKIN_TYPE_RANDOM}", "", $blogn_skin);
			$blogn_skin = str_replace ("{/BLOGN_SKIN_TYPE_RANDOM}", "", $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_SKIN_MODE_NOW}", "ランダム表示", $blogn_skin);

			reset($skinlist[1]);
			$i = 0;
			preg_match("/\{BLOGN_SKIN_LOOP\}([\w\W]+?)\{\/BLOGN_SKIN_LOOP\}/", $blogn_skin, $blogn_reg);
			$blogn_skin_list_all = "";
			while(list($key, $val) = each($skinlist[1])) {
				$blogn_skin_list = $blogn_reg[0];
				$blogn_skin_list = str_replace ("{BLOGN_SKIN_LOOP}", "", $blogn_skin_list);
				$blogn_skin_list = str_replace ("{/BLOGN_SKIN_LOOP}", "", $blogn_skin_list);

				$check = "";
				if ($viewskin[0]) {
					reset($viewskin[1]);
					while(list($skinkey, $skinval) = each($viewskin[1])) {
						if ($viewskin[1][0]["view_type"] == 1 && $skinval["category_id"] == $i) {
							$check = " checked";
							break;
						}
					}
				}
				$blogn_skin_list = str_replace ("{BLOGN_SKIN_NAME}", $val["skin_name"], $blogn_skin_list);
				$blogn_skin_list = str_replace ("{BLOGN_SKIN_NO}", $i, $blogn_skin_list);
				$blogn_skin_list = str_replace ("{BLOGN_SKIN_KEY}", $key, $blogn_skin_list);
				$blogn_skin_list = str_replace ("{BLOGN_SKIN_CHECKED}", $check, $blogn_skin_list);
				$blogn_skin_list_all .= $blogn_skin_list;
				$i++;
			}
			$blogn_skin = preg_replace("/\{BLOGN_SKIN_LOOP\}[\w\W]+?\{\/BLOGN_SKIN_LOOP\}/", $blogn_skin_list_all, $blogn_skin);
			break;
		case 2:
			$blogn_skin = preg_replace("/\{BLOGN_SKIN_ELSE\}[\w\W]+?\{\/BLOGN_SKIN\}/", "", $blogn_skin);
			$blogn_skin = preg_replace("/\{BLOGN_SKIN_TYPE_NORMAL\}[\w\W]+?\{\/BLOGN_SKIN_TYPE_RANDOM\}/", "", $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_SKIN}", "", $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_SKIN_TYPE_CATEGORY}", "", $blogn_skin);
			$blogn_skin = str_replace ("{/BLOGN_SKIN_TYPE_CATEGORY}", "", $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_SKIN_MODE_NOW}", "ジャンル別表示", $blogn_skin);

			$echo_init = "";
			$echo_init .= "document.addform.blogn_new_section.options[0] = new Option('初期表示', 1);\r\n";
			$echo_init .= "document.addform.blogn_new_section.options[1] = new Option('サイト内検索表示', 2);\r\n";
			$blogn_skin = str_replace ("{BLOGN_JS_CATEGORY_1}", $echo_init, $blogn_skin);

			$echo_month = "";
			for ($i = 0; $i < 12; $i++) {
				$mon = $i + 1;
				$echo_month .= "document.addform.blogn_new_section.options[{$i}] = new Option('{$mon}月', '{$mon}');\r\n";
			}
			$blogn_skin = str_replace ("{BLOGN_JS_CATEGORY_2}", $echo_month, $blogn_skin);


			$echo_user = "";
			$userlist = blogn_mod_db_user_load();
			$i = 0;
			@reset($userlist);
			while (list($key, $val) = each($userlist)) {
				$echo_user .= "document.addform.blogn_new_section.options[{$i}] = new Option('".$val["name"]."', '{$key}');\r\n";
				$i++;
			}
			$blogn_skin = str_replace ("{BLOGN_JS_CATEGORY_3}", $echo_user, $blogn_skin);

			$category1 = blogn_mod_db_category1_load();
			$category2 = blogn_mod_db_category2_load();
			$echo_category = "";
			if ($category1[0]) {
				@reset($category1[1]);
				$i = 0;
				while (list($c1key, $c1val) = each($category1[1])) {
					$c1name = get_magic_quotes_gpc() ? stripslashes($c1val["name"]) : $c1val["name"];				//￥を削除
					$c1name = htmlspecialchars($c1name);
					$echo_category .= "document.addform.blogn_new_section.options[{$i}] = new Option('{$c1name}', '{$c1key}|');\r\n";
					$i++;
					@reset($category2[1]);
					while (list($c2key, $c2val) = @each($category2[1])) {
						if ($c1key == $c2val["id"]) {
							$c2name = get_magic_quotes_gpc() ? stripslashes($c2val["name"]) : $c2val["name"];				//￥を削除
							$c2name = htmlspecialchars($c2name);
							$echo_category .= "document.addform.blogn_new_section.options[{$i}] = new Option('{$c1name}::{$c2name}', '{$c1key}|{$c2key}');\r\n";
							$i++;
						}
					}
				}
			}
			$blogn_skin = str_replace ("{BLOGN_JS_CATEGORY_4}", $echo_category, $blogn_skin);

			$blogn_skin = str_replace ("{BLOGN_JS_CATEGORY_GROUP}", $echo_category_group, $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_JS_CATEGORY_NAME}", $echo_category_name, $blogn_skin);


			if ($viewskin[1][0]["view_type"] != 2) {
				$blogn_skin = preg_replace("/\{BLOGN_CATEGORY_SELECT\}[\w\W]+?\{\/BLOGN_CATEGORY_SELECT\}/", "", $blogn_skin);
			}else{
				$category_list = array(1 => "初期／サイト内検索", 2 => "月別", 3 => "ユーザー別", 4 => "カテゴリー別");
				while(list($key, $val) = each($category_list)) {
					$echo_val .= "<option value=\"{$key}\">{$val}</option>\r\n";
				}
				$blogn_skin = str_replace ("{BLOGN_CATEGORY_OPTION}", $echo_val, $blogn_skin);

				$echo_val  = "<option value=\"1\">初期表示</option>\r\n";
				$echo_val .= "<option value=\"2\">サイト内検索表示</option>\r\n";
				$blogn_skin = str_replace ("{BLOGN_SECTION_OPTION}", $echo_val, $blogn_skin);

				if (count($viewskin[1]) > 1) {
					$blogn_skin = str_replace ("{BLOGN_CATEGORY_SKIN_LIST}", "", $blogn_skin);
					$blogn_skin = str_replace ("{/BLOGN_CATEGORY_SKIN_LIST}", "", $blogn_skin);

					preg_match("/\{BLOGN_CATEGORY_SKIN_LOOP\}([\w\W]+?)\{\/BLOGN_CATEGORY_SKIN_LOOP\}/", $blogn_skin, $blogn_reg);
					$blogn_skin_list_all = "";
					@reset($viewskin[1]);
					while(list($key, $val) = each($viewskin[1])) {
						if ($key) {
							$blogn_skin_list = $blogn_reg[0];
							$blogn_skin_list = str_replace ("{BLOGN_CATEGORY_SKIN_LOOP}", "", $blogn_skin_list);
							$blogn_skin_list = str_replace ("{/BLOGN_CATEGORY_SKIN_LOOP}", "", $blogn_skin_list);

							$blogn_skin_list = str_replace ("{BLOGN_CATEGORY_GROUP}", $category_list[$val["category_id"]], $blogn_skin_list);
							switch ($val["category_id"]) {
								case "1":
									$sectarray = array(1 => "初期表示", 2 => "サイト内検索表示");
									$blogn_skin_list = str_replace ("{BLOGN_CATEGORY_NAME}", $sectarray[$val["section_id"]], $blogn_skin_list);
									break;
								case "2":
									$sectarray = array(1 => "1月", 2 => "2月", 3 => "3月", 4 => "4月", 5 => "5月", 6 => "6月", 7 => "7月", 8 => "8月", 9 => "9月", 10 => "10月", 11 => "11月", 12 => "12月");
									$blogn_skin_list = str_replace ("{BLOGN_CATEGORY_NAME}", $sectarray[$val["section_id"]], $blogn_skin_list);
									break;
								case "3":
									@reset($userlist);
									while (list($ukey, $uval) = each($userlist)) {
										if ($ukey == $val["section_id"]) {
											$blogn_skin_list = str_replace ("{BLOGN_CATEGORY_NAME}", $uval["name"], $blogn_skin_list);
											break;
										}
									}
									break;
								case "4":
									if ($category1[0]) {
										@reset($category1[1]);
										$breakflg = false;
										while (list($c1key, $c1val) = each($category1[1])) {
											$chkc1key = $c1key."|";
											if ($chkc1key == $val["section_id"]) {
												$blogn_skin_list = str_replace ("{BLOGN_CATEGORY_NAME}", $c1val["name"], $blogn_skin_list);
												break;
											}
											@reset($category2[1]);
											while (list($c2key, $c2val) = @each($category2[1])) {
												if ($c1key == $c2val["id"]) {
													$chkc2key = $chkc1key.$c2key;
													if ($chkc2key == $val["section_id"]) {
														$blogn_skin_list = str_replace ("{BLOGN_CATEGORY_NAME}", $c1val["name"].":".$c2val["name"], $blogn_skin_list);
														$breakflg = true;
														break;
													}
												}
											}
											if ($breakflg) break;
										}
									}
									break;
							}
							$check = "";
							if ($skinlist[0]) {
								@reset($skinlist[1]);
								while(list($skinkey, $skinval) = each($skinlist[1])) {
									if ($val["skin_id"] == $skinkey) {
										$blogn_skin_list = str_replace ("{BLOGN_SKIN_NAME}", $skinval["skin_name"], $blogn_skin_list);
										break;
									}
								}
							}
							$blogn_skin_list = str_replace ("{BLOGN_ID}", $key, $blogn_skin_list);
							$blogn_skin_list_all .= $blogn_skin_list;
						}
					}
					$blogn_skin = preg_replace("/\{BLOGN_CATEGORY_SKIN_LOOP\}[\w\W]+?\{\/BLOGN_CATEGORY_SKIN_LOOP\}/", $blogn_skin_list_all, $blogn_skin);
				}else{
					$blogn_skin = preg_replace("/\{BLOGN_CATEGORY_SKIN_LIST\}[\w\W]+?\{\/BLOGN_CATEGORY_SKIN_LIST\}/", "", $blogn_skin);
				}
				$blogn_skin = str_replace ("{BLOGN_CATEGORY_SELECT}", "", $blogn_skin);
				$blogn_skin = str_replace ("{/BLOGN_CATEGORY_SELECT}", "", $blogn_skin);
			}

			$echo_val = $echo_val2 = "";
			reset($skinlist[1]);
			while(list($key, $val) = each($skinlist[1])) {
				if ($viewskin[1][0]["view_type"] == 2 && $viewskin[1][0]["skin_id"] == $key) {
					$select = " selected";
				}else{
					$select = "";
				}
				$echo_val .= '<option value="'.$key.'"'.$select.'>'.$val["skin_name"].'</option>';
				$echo_val2 .= '<option value="'.$key.'">'.$val["skin_name"].'</option>';
			}
			$blogn_skin = str_replace ("{BLOGN_SKIN_OPTION}", $echo_val, $blogn_skin);
			$blogn_skin = str_replace ("{BLOGN_NEW_SKIN_OPTION}", $echo_val2, $blogn_skin);






			break;
	}
	echo $blogn_skin;
	return;

}


//-------------------------------------------------------------------- モジュール管理画面

function blogn_module_control($admin, $modules, $qry_action, $module_name) {
	if (!$admin) return;
	if ($module_name) {
		if ($qry_action == "update") {
			include_once(BLOGN_MODDIR.$module_name."/".$modules[1][$module_name]["function"]);
			include_once(BLOGN_MODDIR.$module_name."/".$modules[1][$module_name]["update"]);
		}else{
			include_once(BLOGN_MODDIR.$module_name."/".$modules[1][$module_name]["function"]);
			include_once(BLOGN_MODDIR.$module_name."/".$modules[1][$module_name]["control"]);
		}
	}else{
		blogn_module_list($modules);
	}
}


function blogn_module_list($modules) {
	$blogn_skin = file("./template/module.html");
	$blogn_skin = implode("",$blogn_skin);

	if ($modules[0]) {
		$blogn_skin = str_replace ("{BLOGN_MODULE}", "", $blogn_skin);
		$blogn_skin = str_replace ("{/BLOGN_MODULE}", "", $blogn_skin);
		preg_match("/\{BLOGN_MODULE_LOOP\}([\w\W]+?)\{\/BLOGN_MODULE_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_module_list_all = "";

		reset($modules[1]);
		while(list($key, $val) = each($modules[1])) {
			$blogn_module_list = $blogn_reg[0];
			$blogn_module_list = str_replace ("{BLOGN_MODULE_LOOP}", "", $blogn_module_list);
			$blogn_module_list = str_replace ("{/BLOGN_MODULE_LOOP}", "", $blogn_module_list);

			$blogn_module_list = str_replace ("{BLOGN_ID}", $key, $blogn_module_list);

			if ($val["control"]) {
				$blogn_module_list = str_replace ("{BLOGN_MODULE_CONTROL}", "", $blogn_module_list);
				$blogn_module_list = str_replace ("{/BLOGN_MODULE_CONTROL}", "", $blogn_module_list);
			}else{
				$blogn_module_list = preg_replace("/\{BLOGN_MODULE_CONTROL\}[\w\W]+?\{\/BLOGN_MODULE_CONTROL\}/", "", $blogn_skin);
			}
			$blogn_module_list = str_replace ("{BLOGN_MODULE_NAME}", $val["name"], $blogn_module_list);
			$blogn_module_list = str_replace ("{BLOGN_MODULE_DESC}", $val["desc"], $blogn_module_list);

			$blogn_module_list_all .= $blogn_module_list;
		}
		$blogn_skin = preg_replace("/\{BLOGN_MODULE_LOOP\}[\w\W]+?\{\/BLOGN_MODULE_LOOP\}/", $blogn_module_list_all, $blogn_skin);
	}else{
		$blogn_skin = preg_replace("/\{BLOGN_MODULE\}[\w\W]+?\{\/BLOGN_MODULE\}/", "", $blogn_skin);
	}
	echo $blogn_skin;
}


//-------------------------------------------------------------------- アクセス制限設定画面

function blogn_security_control($admin, $action, $id, $deny_ip) {
	if (!$admin) return;
	$blogn_skin = file("./template/access.html");
	$blogn_skin = implode("",$blogn_skin);

	switch ($action) {
		case "add":
			$date = time() + BLOGN_TIMEZONE;
			$error = blogn_mod_db_denyip_add($date, $deny_ip);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		case "delete":
			$error = blogn_mod_db_denyip_delete($id);
			// インフォメーション表示
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", blogn_information_bar($error[0], $error[1]), $blogn_skin);
			break;
		default:
			$blogn_skin = str_replace ("{BLOGN_INFORMATION}", "", $blogn_skin);
			break;
	}

	$denylist = blogn_mod_db_denyip_load();


	if ($denylist[0]) {
		$blogn_skin = str_replace ("{BLOGN_ACCESS}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_ACCESS_ELSE\}[\w\W]+?\{\/BLOGN_ACCESS\}/", "", $blogn_skin);
		preg_match("/\{BLOGN_ACCESS_LOOP\}([\w\W]+?)\{\/BLOGN_ACCESS_LOOP\}/", $blogn_skin, $blogn_reg);
		$blogn_access_list_all = "";
		while (list($key, $val) = each($denylist[1])) {
			$blogn_access_list = $blogn_reg[0];
			$blogn_access_list = str_replace ("{BLOGN_ACCESS_LOOP}", "", $blogn_access_list);
			$blogn_access_list = str_replace ("{/BLOGN_ACCESS_LOOP}", "", $blogn_access_list);
			$date = date("Y/m/d H:i:s", $val["date"]);
			$blogn_access_list = str_replace ("{BLOGN_IP}", $val["ip"], $blogn_access_list);
			$blogn_access_list = str_replace ("{BLOGN_DATE}", $date, $blogn_access_list);
			$blogn_access_list = str_replace ("{BLOGN_ID}", $key, $blogn_access_list);
			$blogn_access_list_all .= $blogn_access_list;
		}
		$blogn_skin = preg_replace("/\{BLOGN_ACCESS_LOOP\}[\w\W]+?\{\/BLOGN_ACCESS_LOOP\}/", $blogn_access_list_all, $blogn_skin);
	}else{
		$blogn_skin = str_replace ("{/BLOGN_ACCESS}", "", $blogn_skin);
		$blogn_skin = preg_replace("/\{BLOGN_ACCESS\}[\w\W]+?\{BLOGN_ACCESS_ELSE\}/", "", $blogn_skin);
	}

	echo $blogn_skin;
}

?>
