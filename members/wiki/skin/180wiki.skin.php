<?php
///////////////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: 180wiki.skin.php,v 0.4 2007/11/29 07:07:07 hrmz Exp $
//
///////////////////////////////////////////////////////////

// ------------------------------------------------------------
// Settings (define before here, if you want)

// Set site identities
$_IMAGE['skin']['logo']     = 'pukiwiki.png';
$_IMAGE['skin']['favicon']  = ''; // Sample: 'image/favicon.ico';

// SKIN_DEFAULT_DISABLE_TOPICPATH
//   1 = Show reload URL
//   0 = Show topicpath
if (! defined('SKIN_DEFAULT_DISABLE_TOPICPATH'))
	define('SKIN_DEFAULT_DISABLE_TOPICPATH', 1); // 1, 0

// Show / Hide navigation bar UI at your choice
// NOTE: This is not stop their functionalities!
if (! defined('PKWK_SKIN_SHOW_NAVBAR'))
	define('PKWK_SKIN_SHOW_NAVBAR', 1); // 1, 0

// Show / Hide toolbar UI at your choice
// NOTE: This is not stop their functionalities!
if (! defined('PKWK_SKIN_SHOW_TOOLBAR'))
	define('PKWK_SKIN_SHOW_TOOLBAR', 1); // 1, 0

// ------------------------------------------------------------

// Prohibit direct access
if (! defined('UI_LANG')) die('UI_LANG is not set');
if (! isset($_LANG)) die('$_LANG is not set');
if (! defined('PKWK_READONLY')) die('PKWK_READONLY is not set');

$link  = & $_LINK;
$image = & $_IMAGE['skin'];
$rw    = ! PKWK_READONLY;

// Decide charset for CSS
// $css_charset = 'iso-8859-1';
$css_charset = 'utf-8';
switch(UI_LANG){
        case 'ja_JP': $css_charset = 'Shift_JIS'; break;
}

// Output HTTP headers
pkwk_common_headers();
header('Cache-Control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=' . CONTENT_CHARSET);

// Output HTML DTD, <html>, and receive content-type
if (isset($pkwk_dtd)) {
	$meta_content_type = pkwk_output_dtd($pkwk_dtd);
} else {
	$meta_content_type = pkwk_output_dtd();
}

?>
<head>
 <?php echo $meta_content_type ?>
 <meta http-equiv="content-style-type" content="text/css" />
 <?php if ($nofollow || ! $is_read)  { ?> <meta name="robots" content="NOINDEX,NOFOLLOW" /><?php } ?>
 <?php if (PKWK_ALLOW_JAVASCRIPT && isset($javascript)) { ?> <meta http-equiv="Content-Script-Type" content="text/javascript" /><?php } ?>

 <title><?php echo $title ?> - <?php echo $page_title ?></title>

 <link rel="SHORTCUT ICON" href="<?php echo $image['favicon'] ?>" />
 <link rel="stylesheet" href="skin/180wiki.css" title="180wiki" type="text/css" charset="<?php echo $css_charset ?>" />
 <link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo $link['rss'] ?>" />

 <?php if (PKWK_ALLOW_JAVASCRIPT && $trackback_javascript) { ?> <script type="text/javascript" src="skin/trackback.js"></script><?php } ?>

<?php echo $head_tag ?>
</head>
<body>
<div id="wrapper"><!-- ■BEGIN id:wrapper -->
<!-- ◆ Header ◆ ========================================================== -->
<div id="header">
<div id="logo"><a href="<?php echo $link_top ?>"><?php echo $page_title ?></a></div>
</div>

<!-- ◆ Content ◆ ========================================================= -->
<div id="main"><!-- ■BEGIN id:main -->
<div id="wrap_content"><!-- ■BEGIN id:wrap_content -->

<!-- ◆ anchor ◆ -->
<div id="navigator"></div>

<!-- ◆ Toolbar ◆ -->
<?php if (PKWK_SKIN_SHOW_TOOLBAR) { ?>
<!-- ■BEGIN id:toolbar -->
<div id="toolbar">
<?php

// Set toolbar-specific images
$_IMAGE['skin']['reload']   = 'reload.png';
$_IMAGE['skin']['new']      = 'new.png';
$_IMAGE['skin']['edit']     = 'edit.png';
$_IMAGE['skin']['freeze']   = 'freeze.png';
$_IMAGE['skin']['unfreeze'] = 'unfreeze.png';
$_IMAGE['skin']['diff']     = 'diff.png';
$_IMAGE['skin']['upload']   = 'file.png';
$_IMAGE['skin']['copy']     = 'copy.png';
$_IMAGE['skin']['rename']   = 'rename.png';
$_IMAGE['skin']['top']      = 'top.png';
$_IMAGE['skin']['list']     = 'list.png';
$_IMAGE['skin']['search']   = 'search.png';
$_IMAGE['skin']['recent']   = 'recentchanges.png';
$_IMAGE['skin']['backup']   = 'backup.png';
$_IMAGE['skin']['help']     = 'help.png';
$_IMAGE['skin']['rss']      = 'rss.png';
$_IMAGE['skin']['rss10']    = & $_IMAGE['skin']['rss'];
$_IMAGE['skin']['rss20']    = 'rss20.png';
$_IMAGE['skin']['rdf']      = 'rdf.png';

function _toolbar($key, $x = 20, $y = 20){
	$lang  = & $GLOBALS['_LANG']['skin'];
	$link  = & $GLOBALS['_LINK'];
	$image = & $GLOBALS['_IMAGE']['skin'];
	if (! isset($lang[$key]) ) { echo 'LANG NOT FOUND';  return FALSE; }
	if (! isset($link[$key]) ) { echo 'LINK NOT FOUND';  return FALSE; }
	if (! isset($image[$key])) { echo 'IMAGE NOT FOUND'; return FALSE; }

	echo '<a href="' . $link[$key] . '">' .
		'<img src="' . IMAGE_DIR . $image[$key] . '" width="' . $x . '" height="' . $y . '" ' .
			'alt="' . $lang[$key] . '" title="' . $lang[$key] . '" />' .
		'</a>';
	return TRUE;
}
?>
 <?php _toolbar('top') ?>

<?php if ($is_page) { ?>
 &nbsp;
 <?php if ($rw) { ?>
	<?php _toolbar('edit') ?>
	<?php if ($is_read && $function_freeze) { ?>
		<?php if (! $is_freeze) { _toolbar('freeze'); } else { _toolbar('unfreeze'); } ?>
	<?php } ?>
 <?php } ?>
 <?php _toolbar('diff') ?>
<?php if ($do_backup) { ?>
	<?php _toolbar('backup') ?>
<?php } ?>
<?php if ($rw) { ?>
	<?php if ((bool)ini_get('file_uploads')) { ?>
		<?php _toolbar('upload') ?>
	<?php } ?>
	<?php _toolbar('copy') ?>
	<?php _toolbar('rename') ?>
<?php } ?>
 <?php _toolbar('reload') ?>
<?php } ?>
 &nbsp;
<?php if ($rw) { ?>
	<?php _toolbar('new') ?>
<?php } ?>
 <?php _toolbar('list')   ?>
 <?php _toolbar('search') ?>
 <?php _toolbar('recent') ?>
 &nbsp; <?php _toolbar('help') ?>
 &nbsp; <?php _toolbar('rss10', 36, 14) ?>
</div><!-- □END id:toolbar -->
<?php } // PKWK_SKIN_SHOW_TOOLBAR ?>

<div id="content"><!-- ■BEGIN id:content -->
<h1 class="title"><?php echo(($newtitle!='' && $is_read)?$newtitle:$page) ?></h1>
<?php if ($lastmodified != '') { ?><!-- ■BEGIN id:lastmodified -->
<div id="lastmodified">Last-modified: <?php echo $lastmodified ?></div>
<?php } ?><!-- □END id:lastmodified -->
<div id="topicpath"><!-- ■BEGIN id:topicpath -->
<?php if ($is_page and exist_plugin_convert('topicpath')) { echo do_plugin_convert('topicpath'); } ?>
</div><!-- □END id:topicpath -->
<div id="body"><!-- ■BEGIN id:body -->
<?php echo $body ?>
</div><!-- □END id:body -->
<div id="summary"><!-- ■BEGIN id:summary -->
<?php if ($notes != '') { ?><!-- ■BEGIN id:note -->
<div id="note">
<?php echo $notes ?>
</div>
<?php } ?><!-- □END id:note -->
<div id="trackback"><!-- ■BEGIN id:trackback -->
<?php if ($trackback) {
    $tb_id = tb_get_id($_page);
?>
<a href="<?php echo "$script?plugin=tb&amp;__mode=view&amp;tb_id=$tb_id" ?>">TrackBack(<?php echo tb_count($_page) ?>)</a> | 
<?php } ?>

<?php if ($referer) { ?>
<a href="<?php echo "$script?plugin=referer&amp;page=$r_page" ?>">外部リンク元</a>
<?php } ?>
</div><!-- □ END id:trackback -->
<?php if ($related != '') { ?><!-- ■ BEGIN id:related -->
<div id="related">
Link: <?php echo $related ?>
</div>
<?php } ?><!-- □ END id:related -->
<?php if ($attaches != '') { ?><!-- ■ BEGIN id:attach -->
<div id="attach">
<?php echo $hr ?>
<?php echo $attaches ?>
</div>
<?php } ?><!-- □ END id:attach -->
</div><!-- □ END id:summary -->
</div><!-- □END id:content -->
</div><!-- □ END id:wrap_content -->
<!-- ◆sidebar◆ ========================================================== -->
<div id="wrap_sidebar"><!-- ■BEGIN id:wrap_sidebar -->
<div id="sidebar">

<?php if (arg_check('read') && exist_plugin_convert('menu')) { ?>

<!-- ■BEGIN id:menubar -->
<div id="menubar" >
<?php echo do_plugin_convert('menu') ?>
</div>
<?php } ?><!-- □END id:menubar -->

</div><!-- □END id:sidebar -->
</div><!-- □END id:wrap_sidebar -->
</div><!-- □END id:main -->
<!-- ◆ Footer ◆ ========================================================== -->
<div id="footer"><!-- ■BEGIN id:footer -->
 Site admin: <a href="<?php echo $modifierlink ?>"><?php echo $modifier ?></a><p />
 <?php echo S_COPYRIGHT ?>.
 Designed by <a href="http://180.style.coocan.jp/wiki/">180.style</a>. 
 Powered by PHP . 
 HTML convert time: <?php echo $taketime ?> sec.
</div><!-- □END id:footer -->
<!-- ◆ END ◆ ============================================================= -->
</div><!-- □END id:wrapper -->

</body>
</html>
