<?php
// PukiWiki - Yet another WikiWikiWeb clone.
//
// PukiWiki original skin "GS2" 1.5.3
//     by yiza < http://www.yiza.net/ >

// Send header
header('Content-Type: text/css');
$matches = array();
if(ini_get('zlib.output_compression') && preg_match('/\b(gzip|deflate)\b/i', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)) {
	header('Content-Encoding: ' . $matches[1]);
	header('Vary: Accept-Encoding');
}

// Default charset
$charset = isset($_GET['charset']) ? $_GET['charset']  : '';
switch ($charset) {
	case 'Shift_JIS': break; /* this @charset is for Mozilla`s bug */
	default: $charset ='iso-8859-1';
}

// Media
$media   = isset($_GET['media'])   ? $_GET['media']    : '';
if ($media != 'print') $media = 'screen';


// Load settiings
require('pukiwiki_gs2.ini.php');

// Color settings
if ($media == 'print') {
	require ('gs2_color/pukiwiki_gs2_color_'.PKWK_SKIN_GS2_CSSPRINT_COLOR.'.php');
} else {
	$gs2_color = isset($_GET['gs2color']) ? $_GET['gs2color'] : '';
	if ($gs2_color == '')
		$gs2_color = PKWK_SKIN_GS2_CSS_COLOR;
	require ('gs2_color/pukiwiki_gs2_color_'.$gs2_color.'.php');
}

// Output CSS ----
?>
@charset "<?php echo $charset ?>";

pre, dl, ol, p, blockquote {
	line-height:175%;
}

blockquote { margin-left:32px; }

body {
	color:<?php echo SKIN_CSS_FGCOLOR; ?>;
	background-color:<?php echo SKIN_CSS_BGCOLOR; ?>;
	margin:0%;
	padding:1%;
	font-size:<?php echo PKWK_SKIN_GS2_FONTSIZE_NORMAL; ?>;
	letter-spacing:1px;
	font-family:Verdana, Sans-Serif;
}

td, th {
	color:<?php echo SKIN_CSS_FGCOLOR; ?>;
	background-color:<?php echo SKIN_CSS_BGCOLOR; ?>;
	font-size:<?php echo PKWK_SKIN_GS2_FONTSIZE_NORMAL; ?>;
	letter-spacing:1px;
	font-family:Verdana, Sans-Serif;
	
}

<?php
if(PKWK_SKIN_GS2_SCROLLBAR_CSS)
{
?>
body {
	scrollbar-face-color:<?php echo SKIN_CSS_BGCOLOR; ?>;
	scrollbar-track-color:<?php echo SKIN_CSS_BGCOLOR; ?>;
	scrollbar-3dlight-color:<?php echo SKIN_CSS_CTS_BDCOLOR; ?>;
	scrollbar-base-color:<?php echo SKIN_CSS_CTS_BDCOLOR; ?>;
	scrollbar-darkshadow-color:<?php echo SKIN_CSS_CTS_BDCOLOR; ?>;
	scrollbar-highlight-color:<?php echo SKIN_CSS_CTS_BDCOLOR; ?>;
	scrollbar-shadow-color:<?php echo SKIN_CSS_CTS_BDCOLOR; ?>;
	scrollbar-arrow-color:<?php echo SKIN_CSS_CTS_BDCOLOR; ?>;
}
<?php   } ?>

<?php
if(PKWK_SKIN_GS2_2C_ABSOLUTE)
{
?>
div#container {
	width:100%;
	position:relative;
}

div#leftbox2 {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	position:absolute;
	width:18%;
	margin:0px;
	padding:0px;
<?php   } ?>
}

div#centerbox_noright2 {
<?php   if ($media == 'print') { ?>
	width:100%;
<?php   } else { ?>
	position:absolute;
	width:80%;
	left:19%;
<?php   	if (PKWK_SKIN_GS2_OVERFLOW_AUTO) { ?>
	overflow:auto;
<?php   	} ?>
<?php   } ?>
	top:0;
	margin:0px;
	padding:0px;
}

<?php
} // endif PKWK_SKIN_GS2_2C_ABSOLUTE
?>


div#rightbox {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	width:18%;
	float:right;
	margin:0px;
	padding:0px;
<?php   } ?>
}
	div.adsky {
		text-align:center;
	}

div#centerbox {
<?php   if ($media == 'print') { ?>
	width:100%;
<?php   } else { ?>
	width:63%;
	float:left;
<?php   	if (PKWK_SKIN_GS2_OVERFLOW_AUTO) { ?>
	overflow:auto;
<?php   	} ?>
<?php   } ?>
	margin:0px;
	padding:0px;

}

div#centerbox_noside {
	float:left;
	width:100%;
	margin:0px;
	padding:0px;
}

div#centerbox_noright {
<?php   if ($media == 'print') { ?>
	width:100%;
<?php   } else { ?>
	float:left;
	width:81%;
<?php   } ?>
	margin:0px;
	padding:0px;
<?php	if (PKWK_SKIN_GS2_OVERFLOW_AUTO) { ?>
	overflow:auto;
<?php   } ?>
}

div#topbox {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	width:100%;
	padding:5px;
<?php   } ?>
}

div#header {
	padding:5px;
	margin:0px 0px 10px 0px;
	background-color: <?php echo SKIN_CSS_BOX_BGCOLOR; ?>;
	border: 2px solid <?php echo SKIN_CSS_BOX_BDCOLOR; ?>;
}

	img#logo {
<?php   if ($media == 'print') { ?>
		display:none;
<?php   } else { ?>
		float:left;
		margin-right:20px;
		background-color: #FFFFFF;
		border-color: #C0C0C0;
		border-width: 2px;
		border-style: solid;
<?php   } ?>
	}

	h1.title {
		font-size: 220%;
		font-family: 'Trebuchet MS';
		font-weight: bold;
		letter-spacing: 3px;
		color:<?php echo SKIN_CSS_H1_FGCOLOR ?>;
		background-color: <?php echo SKIN_CSS_H1_BGCOLOR; ?>;
		border-style: solid;
		border-color: <?php echo SKIN_CSS_H1_BDCOLOR; ?>;
		border-width: 2px 4px 4px 2px;
<?php   if ($media == 'print') { ?>
		margin: 5px;
<?php   } else if (PKWK_SKIN_SHOW_LOGO == 1) { ?>
		margin: 5px 5px 5px <?php echo( PKWK_SKIN_LOGO_WIDTH + 10 ); ?>px;
<?php   } else { ?>
		margin: 5px 5px 5px 5px;
<?php   } ?>
	}

	form#head_search
	{
<?php   if ($media == 'print') { ?>
		display:none;
<?php   } else { ?>
		font-size: 85%;
		padding:2px 8px;
		margin:0px;
		float:right;
<?php   } ?>
	}

	div#navigator {
<?php   if ($media == 'print') { ?>
		display:none;
<?php   } else { ?>
		font-size: 90%;
		padding:2px;
		margin:0px;
<?php   } ?>
	}

	div.pageinfo
	{
<?php   if ($media == 'print') { ?>
		display:none;
<?php   } else { ?>
		font-size: 90%;
		padding:2px;
		margin:0px;
		text-align:right;
<?php   } ?>
	}

div#contents {
	padding:12px;
	background-color:<?php echo SKIN_CSS_CTS_BGCOLOR; ?>;
	border:3px solid <?php echo SKIN_CSS_CTS_BDCOLOR; ?>;
}

	.footbox
	{
		clear:both;
		padding:3px;
		margin:6px 1px 1px 1px;
		border:dotted 1px <?php echo SKIN_CSS_BOX_BDCOLOR; ?>;
		background-color: <?php echo SKIN_CSS_BOX_BGCOLOR; ?>;
		font-size:90%;
		line-height:180%;
	}

	div#note {
		font-size:105%;
	}

	div#attach {
	<?php   if ($media == 'print') { ?>
		display:none;
	<?php   } else { ?>
	<?php   } ?>
	}
	
	div#related {
	<?php   if ($media == 'print') { ?>
	        display:none;
	<?php   } else { ?>
	<?php   } ?>
	}

div#toolbar {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	padding:0px;
	margin-bottom:10px;
	text-align:right;
<?php   } ?>
}

div#footer {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	clear: both;
	font-size:80%;
	padding:0px;
	margin:16px 0px 0px 0px;
<?php   } ?>
}

div#qrcode {
	float:left;
	margin:0px 10px 0px 10px;
}


div#leftbox {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	width:18%;
	float:left;
	margin:0px;
	padding:0px;
<?php   } ?>
}

	div.menubar {
		margin: 0px 8px;
		padding: 3px;
		word-break:break-all;
		overflow:hidden;
		letter-spacing: 0.5px;
		font-size: <?php echo PKWK_SKIN_GS2_FONTSIZE_NORMAL; ?>;
	}

	div.menubar ul li {
		line-height:160%;
	}
	
	div.menubar h1 ,
	div.menubar h2 ,
	div.menubar h3 ,
	div.menubar h4 ,
	div.menubar h5 {
		font-size: 120%;
		border: 2px solid <?php echo SKIN_CSS_BOX_BDCOLOR; ?>;
		background-color: <?php echo SKIN_CSS_BOX_BGCOLOR; ?>;
		background-image: none;
		margin-top:10px;
	}
	
	div.menubar .anchor_super,
	div.menubar .jumpmenu {
		display:none;
	}
	
	div.menubar td {
		padding:0px;
	}


a:link {
<?php	if ($media == 'print') { ?>
	text-decoration: underline;
<?php	} else { ?>
	color:<?php echo SKIN_CSS_A_LINK; ?>;
	text-decoration:none;
<?php	} ?>
}

a:active {
	color:<?php echo SKIN_CSS_A_ACTIVE; ?>;
	text-decoration:none;
}

a:visited {
<?php	if ($media == 'print') { ?>
	text-decoration: underline;
<?php	} else { ?>
	color:<?php echo SKIN_CSS_A_VISITED; ?>;
	text-decoration:none;
<?php	} ?>
}

a:hover {
	color:<?php echo SKIN_CSS_A_HOVER; ?>;
	text-decoration:underline;
}

h1, h2 {
	font-size:150%;
	color:<?php echo SKIN_CSS_H2_FGCOLOR; ?>;
	background-color:<?php echo SKIN_CSS_H2_BGCOLOR; ?>;
	padding:3px;
	border-style:solid;
	border-color:<?php echo SKIN_CSS_H2_BDCOLOR ?>;
	border-width:3px 3px 6px 20px;
	margin:0px 0px 5px 0px;
}
h3 {
	font-size:140%;
	color:<?php echo SKIN_CSS_H3_FGCOLOR ?>;
	background-color:<?php echo SKIN_CSS_H3_BGCOLOR; ?>;
	padding:3px;
	border-style: solid;
	border-color:<?php echo SKIN_CSS_H3_BDCOLOR; ?>;
	border-width: 1px 1px 5px 12px;
	margin:0px 0px 5px 0px;
}
h4 {
	font-size:130%;
	color:<?php echo SKIN_CSS_H4_FGCOLOR; ?>;
	background-color:<?php echo SKIN_CSS_H4_BGCOLOR; ?>;
	padding:3px;
	border-style: solid;
	border-color:<?php echo SKIN_CSS_H4_BDCOLOR; ?>;
	border-width: 0px 6px 1px 7px;
	margin:0px 0px 5px 0px;
}
h5 {
	font-size:120%;
	color:<?php echo SKIN_CSS_H5_FGCOLOR; ?>;
	background-color:<?php echo SKIN_CSS_H5_BGCOLOR; ?>;
	padding:3px;
	border-style: solid;
	border-color:<?php echo SKIN_CSS_H5_BDCOLOR; ?>;
	border-width: 0px 0px 1px 6px;
	margin:0px 0px 5px 0px;
}

h6 {
	font-size:110%;
	color:<?php echo SKIN_CSS_H6_FGCOLOR; ?>;
	background-color:<?php echo SKIN_CSS_H6_BGCOLOR; ?>;
	padding:3px;
	border-style: solid;
	border-color:<?php echo SKIN_CSS_H6_BDCOLOR; ?>;
	border-width: 0px 5px 1px 0px;
	margin:0px 0px 5px 0px;
}


dt {
	font-weight:bold;
	margin-top:1em;
	margin-left:1em;
}

pre {
	border:<?php echo SKIN_CSS_PRE_BDCOLOR; ?> 1px solid;
	padding:.5em;
	margin-left:1em;
	margin-right:2em;
	font-size: <?php echo PKWK_SKIN_GS2_FONTSIZE_NORMAL; ?>;
	white-space:pre;
	word-break:break-all;
	letter-spacing:0px;

	color:<?php echo SKIN_CSS_FGCOLOR; ?>;
	background-color:<?php echo SKIN_CSS_PRE_BGCOLOR; ?>;
}

img {
	border:none;
	vertical-align:middle;
}

ul {
	margin:0px 0px 0px 6px;
	padding:0px 0px 0px 10px;
	line-height:160%;
}

li {
	margin: 3px 0px;
}

em { font-style:italic; }

strong { font-weight:bold; }

input, textarea {
	color:<?php echo SKIN_CSS_FGCOLOR; ?>;
	background-color:<?php echo SKIN_CSS_BGCOLOR; ?>;
	border-style: solid;
	border-color: <?php echo SKIN_CSS_BOX_BDCOLOR; ?>;
	border-width: 1px;
	font-size:<?php echo PKWK_SKIN_GS2_FONTSIZE_NORMAL; ?>;
}

input.radio {
	background-color:transparent;
	border-width: 0;
}

thead td.style_td,
tfoot td.style_td {
	color:inherit;
	background-color:<?php echo SKIN_CSS_BGCOLOR; ?>;
}
thead th.style_th,
tfoot th.style_th {
	color:inherit;
	background-color:<?php echo SKIN_CSS_BOX_BGCOLOR; ?>;
}
.style_table {
	padding:0px;
	border:0px;
	margin:auto;
	text-align:left;
	color:inherit;
	background-color:<?php echo SKIN_CSS_BOX_BDCOLOR; ?>;
}
.style_th, th {
	padding:5px;
	margin:1px;
	text-align:center;
	color:inherit;
	background-color:<?php echo SKIN_CSS_BGCOLOR; ?>;
	vertical-align:bottom;
}
.style_td, td {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:<?php echo SKIN_CSS_CTS_BGCOLOR; ?>;
	vertical-align:middle;
}

ul.list1 { list-style-type:disc; }
ul.list2 { list-style-type:circle; }
ul.list3 { list-style-type:square; }
ol.list1 { list-style-type:decimal; }
ol.list2 { list-style-type:lower-roman; }
ol.list3 { list-style-type:lower-alpha; }

div.ie5 { text-align:center; }

span.noexists {
	color:#000000;
	background-color:#FFFACC;
}

.small { font-size:85%; }

.super_index {
	color:#DD3333;
	background-color:inherit;
	font-weight:bold;
	font-size:60%;
	vertical-align:super;
}

a.note_super {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	color:#DD3333;
	background-color:inherit;
	font-weight:bold;
	font-size:70%;
<?php   } ?>
}

div.jumpmenu {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	font-size:70%;
	text-align:right;
<?php   } ?>
}

hr.full_hr {
	border-style:ridge;
	border-color:<?php echo SKIN_CSS_PRE_BDCOLOR; ?>;
	border-width:1px 0px;
}

span.size1 {
	font-size:xx-small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
span.size2 {
	font-size:x-small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
span.size3 {
	font-size:small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
span.size4 {
	font-size:medium;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
span.size5 {
	font-size:large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
span.size6 {
	font-size:x-large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
span.size7 {
	font-size:xx-large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}

/* html.php/catbody() */
strong.word0 {
	background-color:#FFFF66;
	color:black;
}
strong.word1 {
	background-color:#A0FFFF;
	color:black;
}
strong.word2 {
	background-color:#99FF99;
	color:black;
}
strong.word3 {
	background-color:#FF9999;
	color:black;
}
strong.word4 {
	background-color:#FF66FF;
	color:black;
}
strong.word5 {
	background-color:#880000;
	color:white;
}
strong.word6 {
	background-color:#00AA00;
	color:white;
}
strong.word7 {
	background-color:#886800;
	color:white;
}
strong.word8 {
	background-color:#004699;
	color:white;
}
strong.word9 {
	background-color:#990099;
	color:white;
}

/* html.php/edit_form() */
.edit_form { clear:both; }

/* pukiwiki.skin.php */
div#preview {
	color:inherit;
	background-color:<?php echo SKIN_CSS_CTS_BGCOLOR; ?>;
}

/* aname.inc.php */
.anchor {}
.anchor_super {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	font-size:70%;
	vertical-align:super;
<?php   } ?>
}

/* br.inc.php */
br.spacer {}

/* calendar*.inc.php */
.style_calendar {
	padding:0px;
	border:0px;
	margin:3px;
	color:inherit;
	background-color:<?php echo SKIN_CSS_BOX_BDCOLOR; ?>;
	text-align:center;
}

.style_calendar td {
	padding:4px;
	margin:1px;
	text-align:center;
	color:inherit;
}

.style_td_today {
	background-color:#CCFFDD;
}
.style_td_sat {
	background-color:#DDE5FF;
}
.style_td_sun {
	background-color:#FFEEEE;
}
.style_td_caltop,
.style_td_week {
	background-color:<?php echo SKIN_CSS_BGCOLOR; ?>;
	font-weight:bold;
}

/* calendar_viewer.inc.php */
div.calendar_viewer {
	color:inherit;
	background-color:inherit;
	margin-top:20px;
	margin-bottom:10px;
	padding-bottom:10px;
}
span.calendar_viewer_left {
	color:inherit;
	background-color:inherit;
	float:left;
}
span.calendar_viewer_right {
	color:inherit;
	background-color:inherit;
	float:right;
}

/* clear.inc.php */
.clear {
	margin:0px;
	clear:both;
}

/* counter.inc.php */
div.counter { font-size:90%; }

/* diff.inc.php */
span.diff_added {
	color:blue;
	background-color:inherit;
}

span.diff_removed {
	color:red;
	background-color:inherit;
}

/* hr.inc.php */
hr.short_line {
	text-align:center;
	width:80%;
	border-style:solid;
	border-color:#AAAAAA;
	border-width:1px 0px;
}

/* include.inc.php */
h5.side_label { text-align:center; }

/* navi.inc.php */
ul.navi {
	font-size:80%;
	margin:0px;
	padding:0px;
	text-align:center;
}
li.navi_none {
	display:inline;
	float:none;
}
li.navi_left {
	display:inline;
	float:left;
	text-align:left;
}
li.navi_right {
	display:inline;
	float:right;
	text-align:right;
}

/* new.inc.php */
span.comment_date { font-size:90%; }
span.new1 {
	color:red;
	background-color:transparent;
	font-size:90%;
}
span.new5 {
	color:green;
	background-color:transparent;
	font-size:90%;
}

/* popular.inc.php */
span.counter { font-size:80%; }
ul.popular_list {
<?php
/*
	padding:0px;
	border:0px;
	margin:0px 0px 0px 1em;
	word-wrap:break-word;
	word-break:break-all;
*/
?>
}

/* recent.inc.php,showrss.inc.php */
ul.recent_list {
<?php
/*
	padding:0px;
	border:0px;
	margin:0px 0px 0px 1em;
	word-wrap:break-word;
	word-break:break-all;
*/
?>
}

/* ref.inc.php */
div.img_margin {
	margin-left:32px;
	margin-right:32px;
}

/* vote.inc.php */
td.vote_label {
	color:#000000;
	background-color:#FFCCCC;
}
td.vote_td1 {
	color:#000000;
	background-color:#DDE5FF;
}
td.vote_td2 {
	color:#000000;
	background-color:#EEF5FF;
}

