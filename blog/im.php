<?php
//------------------------------------------------------------------------
// Weblog PHP script BlognPlus（ぶろぐん＋）
// http://www.blogn.org/
// Copyright Shoichi Takahashi
//
//------------------------------------------------------------------------
// 携帯閲覧用画像変換
//
// LAST UPDATE 2005/02/08
//
// ・携帯閲覧時の画像受け渡しデータ処理を変更
//
//------------------------------------------------------------------------
include("./conf.php");

$max_size = 5;
$max_width = 96;
$max_height = 72;
$color_size = 256;

$mobilelist = file(BLOGN_INIDIR."/mobilelist.cgi");
while(list($key, $val) = each($mobilelist)) {
	list($name, $uadata, $size, $width, $height,$color) = explode(",", $val);
	$ua1 = str_replace(array('.','/','(',')'), array('\.','\/','\(','\)'), $uadata);
	if (preg_match("/$ua1/", $_SERVER["HTTP_USER_AGENT"])) {
		$max_size = $size;
		$max_width = $width;
		$max_height = $height;
		$color_size = $color;
		break;
	}
}

$ua = explode("/",$_SERVER["HTTP_USER_AGENT"]);
if ($ua[0] == 'J-PHONE') {
	$pngkey = 1;
}else{
	$pngkey = 0;
}

$qry = $_SERVER['QUERY_STRING'];
$qry = htmlspecialchars(urldecode($qry));

$fsize = round(filesize($qry) / 1024, 1);
$im_info = getimagesize($qry);

$erkey = 0;
if ($im_info[2] == 1) {
	// GIF
	if (!(imagetypes() & IMG_GIF)) {
		$erkey = 1;
	}else{
		if ($pngkey == 1) {
			$src_im = imagecreatefrompng($qry);
		}else{
			$src_im = imagecreatefromgif($qry);
		}
	}
}elseif ($im_info[2] == 2) {
	// JPEG
	if (!(imagetypes() & IMG_JPG)) {
		$erkey = 1;
	}else{
		$src_im = imagecreatefromjpeg($qry);
	}
}elseif ($im_info[2] == 3) {
	//PNG
	if (!(imagetypes() & IMG_PNG)) {
		$erkey = 1;
	}else{
		$src_im = imagecreatefrompng($qry);
	}
}

if ($erkey == 0) {
	if ($fsize < $max_size) {
		$width = $im_info[0];
		$height = $im_info[1];
	}else{
		if ($im_info[0] > $max_width || $im_info[1] > $max_height) {
			$ratio1 = $max_width / $im_info[0];
			$ratio2 = $max_height / $im_info[1];
			if ($ratio1 < $ratio2) {
				$ratio = $ratio1;
			}else{
				$ratio = $ratio2;
			}
			$width = round($im_info[0] * $ratio);
			$height = round($im_info[1] * $ratio);
		}else{
			$width = $im_info[0];
			$height = $im_info[1];
		}
	}

	if(!$dst_im = @imagecreatetruecolor($width, $height)) {
		if(!$dst_im = @imagecreate($width, $height)) {
			echo '<html><body>このサーバでは画像の変換が出来ませんでした。</body></html>';
			exit;
		}else{
			imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $width, $height,$im_info[0], $im_info[1]);
		}
	}else{
		imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $width, $height,$im_info[0], $im_info[1]);
		imagetruecolortopalette($dst_im, TRUE, $color_size);
	}
	header('Content-Type: image/jpeg');
	imagejpeg($dst_im);
	imagedestroy($dst_im);
}else{
	echo '<html><body>このサーバでは画像の変換が出来ませんでした。</body></html>';
}
?>
