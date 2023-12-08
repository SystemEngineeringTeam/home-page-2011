<?php
// PukiWiki - Yet another WikiWikiWeb clone.
//
// PukiWiki original skin "GS2" 1.5.3
//     by yiza < http://www.yiza.net/ >
// Config file ( English version )
//
// [] is default setting

/////////////////////////////////////////////////
// Color theme of GS2 skin settings
//
// Color themes are exist as below by default :
// - black
// - blue
// - green
// - neongreen
// - neonorange
// - print
// - red
// - sepia
// - silver
// - sky
// - violet
// - white
// - winter
// - yellow
// You can choose color theme as above
//  or choose color theme you made.

// Color theme when page is displayed by screen [blue]
if (! defined('PKWK_SKIN_GS2_CSS_COLOR'))
	define('PKWK_SKIN_GS2_CSS_COLOR', 'blue');

// Color theme when page is printed [print]
if (! defined('PKWK_SKIN_GS2_CSSPRINT_COLOR'))
	define('PKWK_SKIN_GS2_CSSPRINT_COLOR', 'print');

/////////////////////////////////////////////////
// GS2 skin font size [12px]
if (! defined('PKWK_SKIN_GS2_FONTSIZE_NORMAL'))
	define('PKWK_SKIN_GS2_FONTSIZE_NORMAL', '12px');

/////////////////////////////////////////////////
// GS2 skin settings

// Scrollbar is applied of skin color settings
if (! defined('PKWK_SKIN_GS2_SCROLLBAR_CSS'))
	define('PKWK_SKIN_GS2_SCROLLBAR_CSS', 1); // [1], 0

// Use 3 columns layout
if (! defined('PKWK_SKIN_GS2_3COLUMN'))
	define('PKWK_SKIN_GS2_3COLUMN', 0); // 1, [0]

// Use absolute position when 2 columns layout is used
// - This prevent main box from dropping below menu
// - This may produce horizonal scrollbar
if (! defined('PKWK_SKIN_GS2_2C_ABSOLUTE'))
	define('PKWK_SKIN_GS2_2C_ABSOLUTE', 1); // [1], 0

// Adjust box size automatically when length of text is too long
// - This prevent main box from dropping below menu
// - This may produce horizonal scrollbar
if (! defined('PKWK_SKIN_GS2_OVERFLOW_AUTO'))
	define('PKWK_SKIN_GS2_OVERFLOW_AUTO', 0); // 1, [0]

// Use counter plugin below 'MenuBar'
// - This requires counter plugin
if (! defined('PKWK_SKIN_SHOW_COUNTER'))
	define('PKWK_SKIN_SHOW_COUNTER', 1); // [1], 0

// Show color changer on header
if (! defined('PKWK_SKIN_SHOW_COLORCHANGER'))
	define('PKWK_SKIN_SHOW_COLORCHANGER', 0); // 1, [0]

// Show search form on header
if (! defined('PKWK_SKIN_SHOW_SEARCH'))
	define('PKWK_SKIN_SHOW_SEARCH', 1); // [1], 0

// Show QR-code on footer
// - This requires QR code plugin
if (! defined('PKWK_SKIN_SHOW_QRCODE'))
	define('PKWK_SKIN_SHOW_QRCODE', 0); // 1, [0]

// Use site name instead of 'FrontPage'
if (! defined('PKWK_SKIN_ALT_FRONTPAGE'))
	define('PKWK_SKIN_ALT_FRONTPAGE', 1); // [1], 0

// Show logo of site on header
if (! defined('PKWK_SKIN_SHOW_LOGO'))
	define('PKWK_SKIN_SHOW_LOGO', 1); // 1, [0]

// File name and image size of site-logo
if (! defined('PKWK_SKIN_LOGO_FILENAME'))
	define('PKWK_SKIN_LOGO_FILENAME', 'pukiwiki.png'); // ['pukiwiki.png']
if (! defined('PKWK_SKIN_LOGO_WIDTH'))
	define('PKWK_SKIN_LOGO_WIDTH', 80); // [80]
if (! defined('PKWK_SKIN_LOGO_HEIGHT'))
	define('PKWK_SKIN_LOGO_HEIGHT', 80); // [80]

// Don't show page name on header but above body
// - This forces site name to be shown on header
if (! defined('PKWK_SKIN_SHOW_PAGENAME_ABOVEBODY'))
	define('PKWK_SKIN_SHOW_PAGENAME_ABOVEBODY', 0); // 1, [0]

/////////////////////////////////////////////////
// PukiWiki settings

// Show / Hide navigation bar UI at your choice
// NOTE: This is not stop their functionalities!
if (! defined('PKWK_SKIN_SHOW_NAVBAR'))
	define('PKWK_SKIN_SHOW_NAVBAR', 1); // [1], 0

// Show / Hide toolbar UI at your choice
// NOTE: This is not stop their functionalities!
if (! defined('PKWK_SKIN_SHOW_TOOLBAR'))
	define('PKWK_SKIN_SHOW_TOOLBAR', 1); // [1], 0

?>
