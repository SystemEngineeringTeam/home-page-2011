<?php
// PukiWiki - Yet another WikiWikiWeb clone.
//
// PukiWiki original skin "GS2" 1.5.3
//     by yiza < http://www.yiza.net/ >
// Config file
//
// []はデフォルト設定を表します

/////////////////////////////////////////////////
// GS2スキンの色テーマ設定
// 
// 色テーマとしては以下のものが用意されています
// - black       黒を基調とした暗い感じ
// - blue        青を基調とした明るい感じ
// - green       緑を基調としたかなり明るい感じ
// - neongreen   黒背景に鮮やかな緑が光る
// - neonorange  黒背景に鮮やかな橙色が光る
// - print       印刷用モノクロ
// - red         淡い赤を基調とした落ち着いた感じ
// - sepia       淡い色を基調としたセピアな感じ
// - silver      灰色を基調とした落ち着いた感じ
// - sky         青空のように淡く広がる感じ
// - violet      淡い青紫を基調とした落ち着いた感じ
// - white       silverよりさらに白色系に近づいた
// - winter      雪が降っているような淡い色遣い
// - yellow      淡い黄色を基調とした落ち着いた感じ
// テーマは以上から選択するか、自作したテーマを
// 指定することもできます

// 画面表示時のテーマ色を設定します [blue]
if (! defined('PKWK_SKIN_GS2_CSS_COLOR'))
	define('PKWK_SKIN_GS2_CSS_COLOR', 'blue');

// 印刷時のテーマ色を設定します [print]
if (! defined('PKWK_SKIN_GS2_CSSPRINT_COLOR'))
	define('PKWK_SKIN_GS2_CSSPRINT_COLOR', 'print');

/////////////////////////////////////////////////
// GS2スキンのフォントサイズ設定 [12px]
if (! defined('PKWK_SKIN_GS2_FONTSIZE_NORMAL'))
	define('PKWK_SKIN_GS2_FONTSIZE_NORMAL', '12px');

/////////////////////////////////////////////////
// GS2スキンの各種動作設定

// スクロールバーにもスタイルを適用するかどうかを指定します
if (! defined('PKWK_SKIN_GS2_SCROLLBAR_CSS'))
	define('PKWK_SKIN_GS2_SCROLLBAR_CSS', 1); // [1], 0

// 3段組にする場合は1、2段組にする場合は0を指定します
if (! defined('PKWK_SKIN_GS2_3COLUMN'))
	define('PKWK_SKIN_GS2_3COLUMN', 0); // 1, [0]

// 2段組のとき絶対位置によるレイアウトを採用します
// - メイン部分の横幅が長いときにメニュー下に表示されるのを防ぎます
// - 横スクロールバーが表示される場合があります
if (! defined('PKWK_SKIN_GS2_2C_ABSOLUTE'))
	define('PKWK_SKIN_GS2_2C_ABSOLUTE', 1); // [1], 0

// 本文横幅が長すぎる場合にボックス内で自動調節します
// - メイン部分の横幅が長いときにメニュー下に表示されるのを防ぎます
// - 横スクロールバーが表示される場合があります
if (! defined('PKWK_SKIN_GS2_OVERFLOW_AUTO'))
	define('PKWK_SKIN_GS2_OVERFLOW_AUTO', 0); // 1, [0]

// 左のメニュー部にカウンタプラグインを埋め込みます
// - counterプラグインがインストールされている必要があります
if (! defined('PKWK_SKIN_SHOW_COUNTER'))
	define('PKWK_SKIN_SHOW_COUNTER', 1); // [1], 0

// カラーチェンジャー機能をヘッダに表示します
if (! defined('PKWK_SKIN_SHOW_COLORCHANGER'))
	define('PKWK_SKIN_SHOW_COLORCHANGER', 0); // 1, [0]

// ページ右上に検索フォームを表示します
if (! defined('PKWK_SKIN_SHOW_SEARCH'))
	define('PKWK_SKIN_SHOW_SEARCH', 1); // [1], 0

// ページ最下部にQRコードを表示します
// - QRコードの表示にはqrcodeプラグインが必要です
if (! defined('PKWK_SKIN_SHOW_QRCODE'))
	define('PKWK_SKIN_SHOW_QRCODE', 0); // 1, [0]

// "FrontPage"の代わりにサイト名を表示します
if (! defined('PKWK_SKIN_ALT_FRONTPAGE'))
	define('PKWK_SKIN_ALT_FRONTPAGE', 1); // [1], 0

// ヘッダにサイトロゴを表示します
if (! defined('PKWK_SKIN_SHOW_LOGO'))
	define('PKWK_SKIN_SHOW_LOGO', 0); // 1, [0]

// サイトロゴを表示する場合の画像ファイルやサイズを指定します
if (! defined('PKWK_SKIN_LOGO_FILENAME'))
	define('PKWK_SKIN_LOGO_FILENAME', 'pukiwiki.png'); // ['pukiwiki.png']
if (! defined('PKWK_SKIN_LOGO_WIDTH'))
	define('PKWK_SKIN_LOGO_WIDTH', 80); // [80]
if (! defined('PKWK_SKIN_LOGO_HEIGHT'))
	define('PKWK_SKIN_LOGO_HEIGHT', 80); // [80]

// ページ名をヘッダではなく本文上部に表示します
// - 有効にした場合、ヘッダには常にサイト名が表示されます
if (! defined('PKWK_SKIN_SHOW_PAGENAME_ABOVEBODY'))
	define('PKWK_SKIN_SHOW_PAGENAME_ABOVEBODY', 0); // 1, [0]

/////////////////////////////////////////////////
// PukiWikiの設定

// タイトル周りのナビゲーションバーを表示します
// 注意：この設定は表示の切り替えだけで機能を無効にするわけではありません
if (! defined('PKWK_SKIN_SHOW_NAVBAR'))
	define('PKWK_SKIN_SHOW_NAVBAR', 1); // [1], 0

// ページ最下部のツールバーを表示します
// 注意：この設定は表示の切り替えだけで機能を無効にするわけではありません
if (! defined('PKWK_SKIN_SHOW_TOOLBAR'))
	define('PKWK_SKIN_SHOW_TOOLBAR', 1); // [1], 0

?>
