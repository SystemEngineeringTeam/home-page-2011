<?php
//-------------------------------------------------------------------------
// Weblog PHP script Blogn�ʤ֤����
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// ���̥롼����
//
// LAST UPDATE 2007/04/09
//
// ��ɽ���С������ν񤭴���
// ���Ķ�����ν���
// ������¾�٤������ν���
//
//-------------------------------------------------------------------------
/* ===== Blogn�ʤ֤���˥С������ === */
define(BLOGN_VERSION,"Version 1.9.7");

/* �Ķ����� */

ini_set("mbstring.encoding_translation", "Off"); 
ini_set("mbstring.http_input", "pass");
if (!ini_set("mbstring.http_output", "pass")) mb_http_output('pass');
if (!ini_set("mbstring.internal_encoding", "EUC-JP")) mb_internal_encoding('EUC-JP');

/* ===== php�С����������å� ===== */
if(phpversion()>="4.1.0"){
  extract($_GET);
  extract($_POST);
  extract($_COOKIE);
  extract($_SERVER);
}

// PHP_SELF������Ǥ��ʤ������С��к�
if ($_SERVER["PHP_SELF"]) {
	define("PHP_SELF", $_SERVER["PHP_SELF"]);
}else{
	define("PHP_SELF", $_SERVER["SCRIPT_NAME"]);
}

/* ===== jcode�ե������ɤ߹��� ===== */
require_once("./jcode/jcode.php");
require_once("./jcode/code_table.jis2ucs");
require_once("./jcode/code_table.ucs2jis");


/* HOMELINK��TRACKBACKADDR������ */
$urilen = strlen(PHP_SELF);
$reqfile = substr(strrchr(PHP_SELF, "/"),1);
$reqfilelen = strlen($reqfile);
$reqdir = substr( PHP_SELF, 0, $urilen - $reqfilelen) ;
$current = "http://".$_SERVER["HTTP_HOST"] . $reqdir; //������URL
define("HOMELINK", $current);
define("TRACKBACKADDR", $current."tb.php");


/* ����¸�ե���� */
define("LOGDIR", './log/');
/* ������¸�ե���� */
define("PICDIR", './pic/');
/* ��ʸ����¸�ե���� */
define("ICONDIR", './ico/');
/* ��������¸�ե���� */
define("SKINDIR", './skin/');
/* �������Ѳ�����¸�ե���� */
define("SKINPICDIR", './skin/images/');


if (file_exists(LOGDIR."conf.dat")){
	$conf = file(LOGDIR."conf.dat");
	//$init������ԥ����ɺ��
	$conf[0] = ereg_replace( "\n$", "", $conf[0]);
	$conf[0] = ereg_replace( "\r$", "", $conf[0]);
	list($c_sitename,$c_sitedesc,$c_width,$c_height,$c_logcount,$c_arcount,$c_necount,$c_rccount,$c_rtcount,$c_imcount,$c_tz,$c_charset,$c_address,$c_cok_send,$c_tok_send, $c_maxsize, $c_maxtime, $c_tracktype) = explode("<>", $conf[0]);
}
/* �����ȥ����ȥ� */
define("SITENAME", $c_sitename);
/* ������̾ */
define("SITEDESC", $c_sitedesc);
/* ��ɽ���� */
define("LOGCOUNT", $c_logcount);
/* ARCHIVES������ */
define("ARCOUNT", $c_arcount);
/* NEW ENTORIES������ */
define("NECOUNT", $c_necount);
/* RECENT COMMENTS������ */
define("RCCOUNT", $c_rccount);
/* RECENT TRACKBACK������ */
define("RTCOUNT", $c_rtcount);
/* RECENT TRACKBACK������ */
define("IMCOUNT", $c_imcount);
/* ���������� */
define("MAXWIDTH", $c_width);
define("MAXHEIGHT", $c_height);
/* �����ॾ�������� */
define("TIMEZONE", $c_tz * 60 * 60);
/* ʸ������������ */
define("CHARSET", $c_charset);
/* ���Υ᡼�륢�ɥ쥹���� */
define("MADDRESS", $c_address);
/* �������������� */
define("CINFO", $c_cok_send);
/* �ȥ�å��Хå��������� */
define("TINFO", $c_tok_send);
/* ��������ƺ���ʸ���� */
define("CSIZEMAX", $c_maxsize);
/* ������Ϣ³������»��� */
define("CTIMEMAX", $c_maxtime);
/* �ȥ�å��Хå����� */
define("TTYPE", $c_tracktype);

/* �ޥ���Х��ȴؿ���̵ͭ */
if (extension_loaded("mbstring")) {
	define("MBS", 1);	// mbstring on
}else{
	define("MBS", 0);	// mbstring off
}
if ($safe = ini_get('safe_mode')) {
	define("SMODE", 1);	// safe mode on
}else{
	define("SMODE", 0);	// safe mode off
}


/* \r\n | \n  �� <br /> �Ѵ� */
function rntobr($str) {
	$str = str_replace( "\r\n",  "\n", $str);		// ���Ԥ����줹��
	$str = str_replace( "\r",  "\n", $str);
	$str = nl2br($str);													// ����ʸ��������<br>����������
	$str = str_replace("\n",  "", $str);				// \n��ʸ���󤫤�ä���
	return $str;
}


/* �ƥ��������� */
function CleanStr($str){
  $str = trim($str);//��Ƭ�������ζ������
  if (get_magic_quotes_gpc()) {
    $str = stripslashes($str);				//�����
  }
  $str = htmlspecialchars($str);			//�����ػ�
  $str = ereg_replace("&amp;", "&", $str);	//�ü�ʸ��
  return ereg_replace(",", "&#44;", $str);	//����ޤ��Ѵ�
}

/* ----- ������������ ----- */
function IconStr($str){
	$icon = file(LOGDIR."icon.dat");
	for ( $i = 0; $i < count( $icon ); $i++ ) {
		$icon[$i] = ereg_replace( "\n$", "", $icon[$i] );
		$icon[$i] = ereg_replace( "\r$", "", $icon[$i] );
		list($filename, $icon_key) = explode("<>", $icon[$i]);
		if (strstr($str, $icon_key)){
			if (IKEY == 1) {
				if (PNGKEY == 0) {
					$icon_file = ICONDIR.substr($filename, 0, strlen($filename) - 3)."gif";
				}else{
					$icon_file = ICONDIR.substr($filename, 0, strlen($filename) - 3)."png";
				}
			}else{
				$icon_file = ICONDIR.$filename;
			}
			$size = @getimagesize($icon_file);
			$str = str_replace($icon_key,"<img src=\"$icon_file\" ".$size[3].">",$str);
		}
	}
	return $str;
}


/* ----- HTML�������� ----- */
function tagreplaceStr($str){
	$str = str_replace("{", "&#123;", $str);		//�ƥ�ץ졼�Ƚ����Ǹ�ư��ʤ��褦�˽���
	$str = str_replace("}", "&#125;", $str);		//������������
	$str = str_replace("&quot;", "\"", $str);		//  �ɤˤ�ɤ�
	$str = preg_replace("/&lt;b&gt;/i", "<b>", $str);		// ����
	$str = preg_replace("/&lt;\/b&gt;/i", "</b>", $str);
	$str = preg_replace("/&lt;i&gt;/i", "<i>", $str);		// ����
	$str = preg_replace("/&lt;\/i&gt;/i", "</i>", $str);
	$str = preg_replace("/&lt;u&gt;/i", "<u>", $str);		// ����
	$str = preg_replace("/&lt;\/u&gt;/i", "</u>", $str);
	$str = preg_replace("/&lt;s&gt;/i", "<s>", $str);		// �����
	$str = preg_replace("/&lt;\/s&gt;/i", "</s>", $str);
	$str = preg_replace("/&lt;p&gt;/i", "<p>", $str);		// ����
	$str = preg_replace("/&lt;\/p&gt;/i", "</p>", $str);
	$str = preg_replace("/&lt;blockquote&gt;/i", "<blockquote>", $str);		// ����ʸ
	$str = preg_replace("/&lt;\/blockquote&gt;/i", "</blockquote>", $str);
	$str = preg_replace("/(&lt;a)([\w\W]+?)(&gt;)/i","<a\\2>",$str);		// ���
	$str = preg_replace("/&lt;\/a&gt;/i", "</a>", $str);
	$str = preg_replace("/(&lt;img)([\w\W]+?)(&gt;)/i","<img\\2>",$str);		// ���᡼��
	$str = preg_replace("/(&lt;span)([\w\W]+?)(&gt;)/i","<span\\2>",$str);		//
	$str = preg_replace("/&lt;\/span&gt;/i", "</span>", $str);
	$str = preg_replace("/(&lt;div)([\w\W]+?)(&gt;)/i","<div\\2>",$str);		//
	$str = preg_replace("/&lt;\/div&gt;/i", "</div>", $str);
	return $str;
}

/* HTML���ƥ������Ѵ� */
function CleanHtml($str){
	$search = array ("'&(quot|#34);'i",											// html����ƥ��ƥ����ִ�
                   "'&(amp|#38);'i",
                   "'&(lt|#60);'i",
                   "'&(gt|#62);'i",
                   "'&(nbsp|#160);'i",
                   "'&(iexcl|#161);'i",
                   "'&(cent|#162);'i",
                   "'&(pound|#163);'i",
                   "'&(copy|#169);'i",
                   "'<script[^>]*?>.*?</script>'si",		// javascript����
                   "'<[\/\!]*?[^<>]*?>'si");								// html��������

$replace = array ("\"",
                  "&",
                  "<",
                  ">",
                  " ",
                  chr(161),
                  chr(162),
                  chr(163),
                  chr(169),
                  "",
                  "",);
$str = preg_replace ($search, $replace, $str);
return $str;
}


/* ----- ʸ���������Ѵ� ----- */
function mbConv($val,$in,$out) {
	if (MBS) {
		if ($in == 0) {
			$inenc = 'ASCII, JIS, UTF-8, EUC-JP, SJIS';
		}elseif ($in == 1) {
			$inenc = 'EUC-JP';
		}elseif ($in == 2) {
			$inenc = 'SJIS';
		}elseif ($in == 3) {
			$inenc = 'JIS';
		}elseif ($in == 4) {
			$inenc = 'UTF-8';
		}
		if ($out == 1) {
			$outenc = 'EUC-JP';
		}elseif ($out == 2) {
			$outenc = 'SJIS';
		}elseif ($out == 3) {
			$outenc = 'JIS';
		}elseif ($out == 4) {
			$outenc = 'UTF-8';
		}
		$val = mb_convert_encoding($val, $outenc, $inenc);
	}else{
		$val = JcodeConvert($val, $in, $out);
	}
	return $val;
}


/* ----- �ޥ���Х����б��ȥ�ߥ󥰽�����EUC�� ----- */
function mbtrim($val,$nstr) { 
	// $val �ϡ��ȥ�ߥ󥰤�����ʸ���� 
	// $nstr �ϡ��ȥ�ߥ󥰤�����ʸ���� 
	$lenstr = strlen($val); 
	if ($lenstr <= $nstr) { 
		return $val; 
	}else{ 
		$val = substr($val,0,$nstr); 
		$out = preg_replace("/[\x01-\x7E]+/","",$val); 
		$out = preg_replace("/[\x8F]+/","",$out); 
		$e = strlen($out) % 2; 
		if ($e) { 
			$val = substr($val,0,strlen($val)-1); 
		} 
		return $val; 
	} 
}

/* ----- �ǥ��쥯�ȥꡦ�ե����븡�����Ƥʤ���к��� ----- */
function FileSearch($str) {
	// $str: ����ʸ���ʥե�����̾��
	// ���log200407.dat
	// �����: 0 �����ե⡼�ɡ��ǥ��쥯�ȥ�����Բ�
	//         1 �ե����롿�ǥ��쥯�ȥ����
	//        -1 �ե������������
	$filetype = substr($str,0,3);	// log, cmt, trk ��3����
	$datedir = trim(substr($str,3,4));	// ���2004 ��ǯ�ե������

	//log�ե�����θ�������ǯ�ե������̵ͭ������å�
	if ($filetype == "log" && !FileCheck($datedir, 0, LOGDIR)) {
		if(SMODE) {
			return 0;
		}else{
			if(@mkdir(LOGDIR.$datedir) && @chmod(LOGDIR.$datedir, 0777)) {
				if($fp = @fopen(LOGDIR.$datedir."/".$str, "w+")) {
					fclose($fp);
					@chmod(LOGDIR.$datedir."/".$str, 0666);
					return 1;
				}else{
					return -1;
				}
			}else{
				return -1;
			}
		}
	}elseif (!FileCheck($str, 1, LOGDIR.$datedir)){
		if($fp = @fopen(LOGDIR.$datedir."/".$str, "w+")) {
			fclose($fp);
			@chmod(LOGDIR.$datedir."/".$str, 0666);
			return 1;
		}else{
			return -1;
		}
	}else{
		return 1;
	}
}

/* ----- �ǥ��쥯�ȥꡦ�ե����븡�� ----- */
function FileCheck($str, $flg, $dir) {
	// $str: ����ʸ���ʥǥ��쥯�ȥ�̾/�ե�����̾��
	// $flg: 0 = �ǥ��쥯�ȥ� ����¾ = �ե�����̾
	// $dir: �����ǥ��쥯�ȥ�ʾ���
	if(!$bufdir = @dir($dir)) return false;
	while (($ent = $bufdir->read()) !== false) {
		if ($ent != "." && $ent != "..") {
			if ($flg == 0) {
				if (is_dir($dir."/".$ent) && $ent == $str) return true;
			}else{
				if (is_file($dir."/".$ent) && $ent == $str) return true;
			}
		}
	}
	return false;
}

/* ----- ����ǯ������ʬ�ä�ե�����Τɤΰ��֤���������Ф��������� ----- */
function DateSearch($datetime, $log) {
	// $datetime: ��������DateTime
	// $log: ���������
	// �����: ��������Կ� $log��0��ʤ�flase���֤�
	if (count($log) == 0) return $key = -1;
	$datetime_array = $log;
	if(array_walk($datetime_array, 'datetime_callback')) {
		while (list($key,$val) = each($datetime_array)){
			if ($datetime >= $val) {
				return $key;
			}
		}
		return count($datetime_array);
	}
}
function datetime_callback(&$value, $key) {
	list(, $valuedate, $valuetime) = explode("<>",$value,4);
	$value = $valuedate.$valuetime;
}


/* ----- ����EID���ե�����Τɤΰ��֤ˤ��뤫���� ------ */
function IDSearch($eid, $log) {
	// $eid: ��������EID
	// $log: ���������
	// �����: ���Ĥ��ä��Կ�
	// ���EID��ʣ��������Ϻǽ�ιԤ��֤�
	$id_array = $log;
	if(array_walk($id_array, 'eid_callback')) $result = array_search($eid, $id_array);
	return $result;
}


/* ----- ����EID���ɤΥե�����ˤ��뤫���� ----- */
function IDCheck($eid, $flg) {
	// $eid: ��������EID
	// $flg: 0=log, 1=cmt, 2=trk
	// �����: ���ե�����̾�������
	// ���[0]:cmt200406.dat [1]:cmt200407.dat

	// ǯ�ե��������
	$bufdir = dir(LOGDIR);
	while (($ent = $bufdir->read()) !== false) {
		if ($ent != "." && $ent != "..") {
			if (is_dir(LOGDIR.$ent)) $result[] = $ent;
		}
	}
	$matchlist = array();
	for ($i = 0; $i < count($result); $i++) {
		$bufdir = dir(LOGDIR.$result[$i]);
		while (($ent = $bufdir->read()) !== false) {
			if (($flg == 0 && substr($ent,0,3) == "log") || ($flg == 1 && substr($ent,0,3) == "cmt") || ($flg == 2 && substr($ent,0,3) == "trk")) {
				if (is_file(LOGDIR.$result[$i]."/".$ent)) {
					$dat = file(LOGDIR.$result[$i]."/".$ent);
					$id_array = $dat;
					if(array_walk($id_array, 'eid_callback')){
						if (in_array($eid, $id_array)) $matchlist[] = $ent;
					}
				}
			}
		}
	}
	if (count($matchlist) == 0) {
		return false;
	}else{
		return $matchlist;
	}
}
function eid_callback(&$value, $key) {
	list($value) = explode("<>",$value,2);
}


/* ----- ��Ƶ������ʥ��ƥ����̡� ----- */
function CategoryCount() {
	// �����: ���[0]10 [1]2 [3]4
	//             [CID]���

	// ǯ�ե��������
	$result = array();
	$bufdir = dir(LOGDIR);
	while (($ent = $bufdir->read()) !== false) {
		if ($ent != "." && $ent != "..") {
			if (is_dir(LOGDIR.$ent)) $result[] = $ent;
		}
	}
	$id_merge = array();
	for ($i = 0; $i < count($result); $i++) {
		$bufdir = dir(LOGDIR.$result[$i]);
		while (($ent = $bufdir->read()) !== false) {
			if (substr($ent,0,3) == "log") {
				if (is_file(LOGDIR.$result[$i]."/".$ent)) {
					$dat = array();
					$dat = file(LOGDIR.$result[$i]."/".$ent);
					$id_array = $dat;
					if(array_walk($id_array, 'cid_callback')){
						while (list($key, $val) = each ($id_array)) {
							$id_merge[] = $val;
						}
					}
				}
			}
		}
	}
	$matchlist = array_count_values($id_merge);
	ksort($matchlist);
	if (count($matchlist) == 0) {
		return false;
	}else{
		return $matchlist;
	}
}
function cid_callback(&$value, $key) {
	list(,,, $value) = explode("<>",$value,5);
}


/* ----- ��Ƶ������ʷ��̡� ----- */
function archive_count() {
	// �����: ���[200405]20 [200406]24 [200407]10 ...
	//             [ǯ��]���
	// ǯ�ե��������
	$result = array();
	$bufdir = dir(LOGDIR);
	while (($ent = $bufdir->read()) !== false) {
		if ($ent != "." && $ent != "..") {
			if (is_dir(LOGDIR.$ent)) $result[] = $ent;
		}
	}
	$logcount = array();
	for ($i = 0; $i < count($result); $i++) {
		$bufdir = dir(LOGDIR.$result[$i]);
		while (($ent = $bufdir->read()) !== false) {
			if (substr($ent,0,3) == "log") {
				if (is_file(LOGDIR.$result[$i]."/".$ent)) {
					$dat = file(LOGDIR.$result[$i]."/".$ent);
					$logcount[substr($ent,3,6)] = count($dat);
				}
			}
		}
	}
	@krsort($logcount);
	if (count($logcount) == 0) {
		return false;
	}else{
		return $logcount;
	}
}


/* ----- ����ID�Υ����ȵ����� ----- */
function CommentCount($eid) {
	// $eid: ��������EID
	// �����: ������

	if ($cmtlist = IDCheck($eid, 1)) {
		$cmtcount = 0;
		for ($i = 0; $i < count($cmtlist); $i++) {
			$cmt = file(LOGDIR.substr($cmtlist[$i],3,4)."/".$cmtlist[$i]);
			for ($j = 0; $j < count($cmt); $j++) {
				list($c_eid) = explode("<>", $cmt[$j], 2);
				if ($eid == $c_eid) $cmtcount++;
			}
		}
		return $cmtcount;
	}else{
		return 0;
	}
}


/* ----- ����ID�Υȥ�å��Хå������� ----- */
function TrackbackCount($eid) {
	// $eid: ��������EID
	// �����: ������

	if ($trklist = IDCheck($eid, 2)) {
		$trkcount = 0;
		for ($i = 0; $i < count($trklist); $i++) {
			$trk = file(LOGDIR.substr($trklist[$i],3,4)."/".$trklist[$i]);
			for ($j = 0; $j < count($trk); $j++) {
				list($t_eid) = explode("<>", $trk[$j], 2);
				if ($eid == $t_eid) $trkcount++;
			}
		}
		return $trkcount;
	}else{
		return 0;
	}
}


/* ----- �����ե�������� ----- */
function LogFileList($flg) {
	// $flg:  0=log, 1=mor, 2=cmt, 3=trk
	// �����: ��) [0] log200306.dat [1] log200305.dat ...
	$dirname = array();
	$bufdir = dir(LOGDIR);
	while (($ent = $bufdir->read()) !== false) {
		if ($ent != "." && $ent != "..") {
			if (is_dir(LOGDIR.$ent)) $dirname[] = $ent;
		}
	}
	$filename = array();
	for ($i = 0; $i < count($dirname); $i++) {
		$bufdir = dir(LOGDIR.$dirname[$i]);
		while (($ent = $bufdir->read()) !== false) {
			if (($flg == 0 && substr($ent,0,3) == "log") || ($flg == 1 && substr($ent,0,3) == "mor") || ($flg == 2 && substr($ent,0,3) == "cmt") || ($flg == 3 && substr($ent,0,3) == "trk")) {
				if (is_file(LOGDIR.$dirname[$i]."/".$ent)) {
					if (filesize(LOGDIR.$dirname[$i]."/".$ent) != 0) $filename[] = $ent;
				}
			}
		}
	}
	@rsort($filename);
	if (count($filename) == 0) {
		return false;
	}else{
		return $filename;
	}
}

/* ----- �����������¤Τ���IP����� ----- */
function ip_check_deny($ip) {
	$deny_ip = file(LOGDIR."ip.dat");
	//$deny_ip������ԥ����ɺ��
	for ( $i = 0; $i < count( $deny_ip ); $i++ ) {
		$deny_ip[$i] = preg_replace( "/\n$/", "", $deny_ip[$i] );
		$deny_ip[$i] = preg_replace( "/\r$/", "", $deny_ip[$i] );
		list($key1, $key2, $key3, $key4) = explode(".",$ip);
		list($ipaddr, $ipdate) = explode("<>", $deny_ip[$i]);
		list($deny_key1, $deny_key2, $deny_key3, $deny_key4) = explode(".",$ipaddr);
		if ($deny_key1 == "*") $key1 = "*";
		if ($deny_key2 == "*") $key2 = "*";
		if ($deny_key3 == "*") $key3 = "*";
		if ($deny_key4 == "*") $key4 = "*";
		if ($deny_key1 == $key1 && $deny_key2 == $key2 && $deny_key3 == $key3 && $deny_key4 == $key4) {
			header("HTTP/1.0 404 Not Found");
			exit;
		}
	}
}


?>
