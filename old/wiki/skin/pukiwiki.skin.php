<?php
// PukiWiki - Yet another WikiWikiWeb clone.
//
// PukiWiki original skin "GS2" 1.5.3
//     by yiza < http://www.yiza.net/ >

// Load settiings of GS2 skin
require('pukiwiki_gs2.ini.php');

// Set site logo
$_IMAGE['skin']['logo']        = PKWK_SKIN_LOGO_FILENAME;
$_IMAGE['skin']['logo_width']  = PKWK_SKIN_LOGO_WIDTH;
$_IMAGE['skin']['logo_height'] = PKWK_SKIN_LOGO_HEIGHT;
$_IMAGE['skin']['favicon']     = ''; // Sample: 'image/favicon.ico';

// ------------------------------------------------------------
// Code start

// Prohibit direct access
if (! defined('UI_LANG')) die('UI_LANG is not set');
if (! isset($_LANG)) die('$_LANG is not set');
if (! defined('PKWK_READONLY')) die('PKWK_READONLY is not set');

$lang  = & $_LANG['skin'];
$link  = & $_LINK;
$image = & $_IMAGE['skin'];
$rw    = ! PKWK_READONLY;

// Decide charset for CSS
$css_charset = 'iso-8859-1';
switch(UI_LANG){
	case 'ja': $css_charset = 'Shift_JIS'; break;
}

// ------------------------------------------------------------
// Output

// HTTP headers
pkwk_common_headers();
header('Cache-control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=' . CONTENT_CHARSET);

// HTML DTD, <html>, and receive content-type
if (isset($pkwk_dtd)) {
	$meta_content_type = pkwk_output_dtd($pkwk_dtd);
} else {
	$meta_content_type = pkwk_output_dtd();
}

// Use 'absolute layout' or not
$ab_lay = !PKWK_SKIN_GS2_3COLUMN && PKWK_SKIN_GS2_2C_ABSOLUTE;

?>
<head>
 <?php echo $meta_content_type ?>
 <meta http-equiv="content-style-type" content="text/css" />
<?php if ($nofollow || ! $is_read)  { ?> <meta name="robots" content="NOINDEX,NOFOLLOW" /><?php } ?>
<?php if (PKWK_ALLOW_JAVASCRIPT && isset($javascript)) { ?> <meta http-equiv="Content-Script-Type" content="text/javascript" /><?php } ?>

 <title><?php echo $title ?> - <?php echo $page_title ?></title>
 <link rel="SHORTCUT ICON" href="<?php echo $image['favicon'] ?>" />
<?php
	$gs2color = "";
	if (defined('PKWK_SKIN_GS2_INDEX_COLOR'))
		$gs2color = PKWK_SKIN_GS2_INDEX_COLOR;
	echo <<<EOD
 <link rel="stylesheet" type="text/css" media="screen" href="skin/pukiwiki_gs2.css.php?charset=$css_charset&amp;gs2color=$gs2color" charset="$css_charset" />
EOD;
?>
 <link rel="stylesheet" type="text/css" media="print" href="skin/pukiwiki_gs2.css.php?charset=<?php echo $css_charset ?>&amp;media=print" charset="<?php echo $css_charset ?>" />
  <link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo $link['rss'] ?>" /><?php // RSS auto-discovery ?>

<?php if (PKWK_ALLOW_JAVASCRIPT && $trackback_javascript) { ?> <script type="text/javascript" src="skin/trackback.js"></script><?php } ?>

<?php echo $head_tag ?>
</head>
<body>

<?php 
	global $showsidebar, $vars;
	$showsidebar = !(
		arg_check('paint') ||
		arg_check('map') ||
		arg_check('referer') ||
		arg_check('backup') ||
		arg_check('diff') ||
		arg_check('filelist') ||
		arg_check('deleted') ||
		arg_check('search')  );
	$showrightbar = ( PKWK_SKIN_GS2_3COLUMN
		&& $showsidebar && !arg_check('edit') );
?>

<!--Header-->
<div id="header">

<!-- Header/Search -->
<?php
if (PKWK_SKIN_SHOW_SEARCH) {
	global $script, $_btn_and, $_btn_or, $_btn_search, $_title_site_search, $s_word;

	echo <<<EOD
<form action="$script?cmd=search" method="post" id="head_search">
 <div>
  $_btn_search
  <input type="text"  name="word" value="$s_word" size="25" />
  <input type="radio" name="type" value="AND" class="radio" checked="checked" />$_btn_and
  <input type="radio" name="type" value="OR" class="radio" />$_btn_or
  &nbsp;<input type="submit" value="$_btn_search" />
 </div>
</form>
EOD;
}
?>

<?php if (PKWK_SKIN_SHOW_LOGO) { ?>
<a href="<?php echo $link['top'] ?>"><img id="logo" src="<?php echo IMAGE_DIR . $image['logo'] ?>" width="<?php echo $image['logo_width'] ?>" height="<?php echo $image['logo_height'] ?>" alt="[<?php echo $title ?>]" title="[<?php echo $title ?>]" /></a>
<?php } ?>

<div id="navigator">
<?php
function _navigator($key, $value = '', $javascript = ''){
	$lang = & $GLOBALS['_LANG']['skin'];
	$link = & $GLOBALS['_LINK'];
	if (! isset($lang[$key])) { echo 'LANG NOT FOUND'; return FALSE; }
	if (! isset($link[$key])) { echo 'LINK NOT FOUND'; return FALSE; }
	if (! PKWK_ALLOW_JAVASCRIPT) $javascript = '';

	echo '<a href="' . $link[$key] . '" ' . $javascript . '>' .
		(($value === '') ? $lang[$key] : $value) .
		'</a>';

	return TRUE;
}

function _colorchanger($colorname, $samplecolor){
	$script_url = $GLOBALS['_LINK']['reload'];

	$col_url = preg_replace('/index(\.)?([a-z]+)?\.php/',
			'index.'.$colorname.'.php',$script_url);
	
	echo '<a href="' . $col_url .
	'" style="background-color:'. $samplecolor .
	'" title="'. $colorname .
	'">&nbsp;&nbsp;</a>';
}
?>
 <?php _navigator('top') ?>
 | <?php _navigator('reload') ?>
<?php if(PKWK_SKIN_SHOW_NAVBAR) { ?>
 <?php if ($rw) { ?>
 | <?php _navigator('new') ?>
 <?php } ?>
 | <?php _navigator('list') ?>
 <?php if (arg_check('list')) { ?>
 | <?php _navigator('filelist') ?>
 <?php } ?>
 | <?php _navigator('search') ?>
 | <?php _navigator('recent') ?>
 | <?php _navigator('help')   ?>
<?php 
	if(PKWK_SKIN_SHOW_COLORCHANGER) {
		echo(" | Color: ");
		_colorchanger('black','#101010');
		_colorchanger('blue','#769BC0');
		_colorchanger('green','#2FB35B');
		_colorchanger('neongreen','#00FF00');
		_colorchanger('neonorange','#FF9900');
		_colorchanger('red','#C9336A');
		_colorchanger('sepia','#DCD7C2');
		_colorchanger('silver','#999999');
		_colorchanger('sky','#C2CDDC');
		_colorchanger('violet','#D0D0DF');
		_colorchanger('white','#E0E0E0');
		_colorchanger('winter','#AAAAAA');
		_colorchanger('yellow','#F5F5CF');
	}
?>
<?php } ?>

</div>

<h1 class="title"><?php
global $defaultpage;
$ptitle = "";
if ( arg_check('read') || arg_check('edit')) {
	if(PKWK_SKIN_ALT_FRONTPAGE && !PKWK_SKIN_SHOW_PAGENAME_ABOVEBODY &&
		isset($vars["page"]) && $vars["page"] == $defaultpage){
			$ptitle = $page_title;
	}else{
		$ptitle = '<span class="small">';
		require_once(PLUGIN_DIR.'topicpath.inc.php');
		$ptitle .= plugin_topicpath_convert();
		if ( preg_match('/\//',$title) == TRUE ) {
			$ptitle .= '/';
		}
		$ptitle .= '</span>';
		$ptitle .= preg_replace('/.*\//','',$title);
	}
} else {
	$ptitle = $title;
}

if (PKWK_SKIN_SHOW_PAGENAME_ABOVEBODY){
	echo $page_title;
}else{
	echo $ptitle;
} ?></h1>

<?php if(PKWK_SKIN_SHOW_NAVBAR && $is_page) { ?>
<div class="pageinfo">
 Last update on <?php echo $lastmodified ?>
 <?php if ($rw) { ?>
 | <?php _navigator('edit') ?>
 <?php if ($is_read && $function_freeze) { ?>
 | <?php (! $is_freeze) ? _navigator('freeze') : _navigator('unfreeze') ?>
 <?php } ?>
 | <?php _navigator('copy') ?>
 | <?php _navigator('rename') ?>
 <?php } ?>
 | <?php _navigator('diff') ?>
 <?php if ($do_backup) { ?>
 | <?php _navigator('backup') ?>
 <?php } ?>
 <?php if ($rw && (bool)ini_get('file_uploads')) { ?>
 | <?php _navigator('upload') ?>
 <?php } ?>

<?php if ($trackback) { ?>
 | <?php _navigator('trackback', $lang['trackback'] . '(' . tb_count($_page) . ')',
 	($trackback_javascript == 1) ? 'onclick="OpenTrackback(this.href); return false"' : '') ?> ]
<?php } ?>
<?php if ($referer)   { ?>
 | <?php _navigator('refer') ?>
<?php } ?>
</div>
<?php } // PKWK_SKIN_SHOW_NAVBAR ?>

</div>

<?php if($ab_lay) { ?><div id="container"><?php } ?>

<!--Left Box-->
<?php if ( exist_plugin_convert('menu') && $showsidebar) { ?>
 <?php if($ab_lay) { ?><div id="leftbox2">
 <?php } else { ?> <div id="leftbox"> <?php } ?>
  <div class="menubar">
    <?php echo do_plugin_convert('menu'); ?>
    <?php if (PKWK_SKIN_SHOW_COUNTER) {
    echo $hr; ?>
	<ul><li><?php
		require_once(PLUGIN_DIR.'counter.inc.php');
		echo ('Total:');
		echo plugin_counter_inline('total');
		echo ('/Today:');
		echo plugin_counter_inline('today');
	?></li></ul>
	<?php } ?>
  </div>
</div>
<?php } ?>

<!--Center Box-->
<?php if($showsidebar && $showrightbar) { ?>
<div id="centerbox">
<?php } else if($showsidebar) { ?>
 <?php if($ab_lay) { ?><div id="centerbox_noright2">
 <?php } else { ?> <div id="centerbox_noright"> <?php } ?>
<?php } else { ?>
<div id="centerbox_noside">
<?php } ?>

<?php
if(PKWK_SKIN_SHOW_PAGENAME_ABOVEBODY){
	echo("<h1>$ptitle</h1>");
}
?>

<div id="contents">
<?php echo $body ?>

<p class="clear" />

<?php if ($notes != '') { ?>
<div id="note" class="footbox"><?php echo $notes ?></div>
<?php } ?>

<?php if ($attaches != '') { ?>
<div id="attach" class="footbox"><?php echo $attaches ?></div>
<?php } ?>

<?php if ($related != '') { ?>
<div id="related" class="footbox">Link: <?php echo $related ?></div>
<?php } ?>

</div>

<?php if(! $ab_lay) { ?></div><?php } ?>

<!--Right Box-->
<?php if (exist_plugin_convert('menu2') && $showrightbar) { ?>
<div id="rightbox">
  <div class="menubar">
    <?php echo do_plugin_convert('menu2'); ?>

  <!--insert ad here-->
  </div>
</div>
<?php } ?>

<div id="footer">

<?php if (PKWK_SKIN_SHOW_QRCODE) { ?>
<div id="qrcode">
<?php
  require_once(PLUGIN_DIR.'qrcode.inc.php');
  echo plugin_qrcode_inline(2, $link['reload']);
?>
</div>
<?php } ?>

<?php if (PKWK_SKIN_SHOW_TOOLBAR) { ?>
<!-- Toolbar -->
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

</div>
<?php } // PKWK_SKIN_SHOW_TOOLBAR ?>

 <?php echo S_COPYRIGHT ?>.<br />
 Skin "GS2" is designed by <a href="http://www.yiza.net/">yiza</a>.<br />
 Powered by PHP <?php echo PHP_VERSION ?>. HTML convert time: <?php echo $taketime ?> sec.

</div>

<?php if($ab_lay) { ?></div></div><?php } ?>


</body>
</html>
