<?php
//--------------------------------------------------------------------
// *** PostIt ***
// LAST UPDATE: 2007/01/27
// Version    : 2.00
// Copyright  : nJOY
// http://njoy.pekori.to/
//--------------------------------------------------------------------
//
// update.php
//
//--------------------------------------------------------------------

echo '
<div class="blogn_main"><h1 id="blogn_bar" style="font-size:150%;">お知らせ表示モジュール ver2.00</h1>
<p>最新版は「<a href="http://njoy.pekori.to/blog/" target="_blank">nJOY BLOG</a>」にて確認してください。</p>

<h2 style="font-size:150%;">インストール方法</h2>
<ol>
<li>拡張子が「*.cgi」のファイル（全11個）のパーミッションを 666 等に変更する。</li>
<li>「お知らせ１」を表示したい場合、スキンの好きな場所に {POSTIT1} と記述する。<br />お知らせは全部で10個設置可能（{POSTIT1} ～ {POSTIT10}）</li>
<li>管理画面で出力したい内容を書き込み、表示に関する設定を行う。</li>
</ol>


<h2 style="font-size:150%;">アンインストール方法</h2>
<p style="margin-left:2em;">インストール方法で追加した記述をスキンファイル内から削除後、「modlue」ディレクトリから「postit」フォルダを削除してください。</p>

</div>
';

?>
