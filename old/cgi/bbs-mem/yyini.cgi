#┌─────────────────────────────────
#│  YY-BOARD v5.33 - 2004/09/01
#│  Copyright (c) KentWeb
#│  webmaster@kent-web.com
#│  http://www.kent-web.com/
#└─────────────────────────────────
$ver = 'YY-BOARD v5.33';
#┌─────────────────────────────────
#│ [注意事項]
#│ 1. このスクリプトはフリーソフトです。このスクリプトを使用した
#│    いかなる損害に対して作者は一切の責任を負いません。
#│ 2. 設置に関する質問はサポート掲示板にお願いいたします。
#│    直接メールによる質問は一切お受けいたしておりません。
#│ 3. 添付の home.gif は L.O.V.E の mayuRin さんによる画像です。
#└─────────────────────────────────
#
# 【ファイル構成例】
#
#  public_html (ホームディレクトリ)
#      |
#      +-- yybbs / yybbs.cgi  [705]
#            |     yyregi.cgi [705]
#            |     yyini.cgi  [604]
#            |     yylog.cgi  [606]
#            |     count.dat  [606]
#            |     jcode.pl   [604]
#            |     pastno.dat [606]
#            |
#            +-- img / home.gif, bear.gif, ...
#            |
#            +-- lock [707] /
#            |
#            +-- past [707] / 0001.cgi [606] ...

#-------------------------------------------------
# ▼設定項目
#-------------------------------------------------

# タイトル名
$title = "SETメンバーBBS";

# タイトル文字色
$tCol = "#005170";

# タイトルサイズ
$tSize = '26px';

# 本文文字フォント
$bFace = "MS UI Gothic, Osaka, ＭＳ Ｐゴシック";

# 本文文字サイズ
$bSize = '15px';

# 壁紙を指定する場合（http://から指定）
$backgif = "";

# 背景色を指定
$bgcolor = "#4baabe";

# 文字色を指定
$text = "#000000";

# リンク色を指定
$link  = "#0000FF";	# 未訪問
$vlink = "#800080";	# 訪問済
$alink = "#FF0000";	# 訪問中

# 戻り先のURL (index.htmlなど)
$homepage = "../index.html";

# 最大記事数
$max = 100;

# 管理者用パスワード (英数字で８文字以内)
$pass = 'admin';

# アイコン画像のあるディレクトリ
# → フルパスなら http:// から記述する
# → 最後は必ず / で閉じる
$imgurl = "./img/";

# アイコンを定義
# →　上下は必ずペアにして、スペースで区切る
$ico1 = 'bear.gif cat.gif cow.gif dog.gif fox.gif hituji.gif monkey.gif zou.gif mouse.gif panda.gif pig.gif usagi.gif';
$ico2 = 'くま ねこ うし いぬ きつね ひつじ さる ぞう ねずみ パンダ ぶた うさぎ';

# 管理者専用アイコン機能 (0=no 1=yes)
# (使い方) 記事投稿時に「管理者アイコン」を選択し、暗証キーに
#         「管理パスワード」を入力して下さい。
$my_icon = 0;

# 管理者専用アイコンの「ファイル名」を指定
$my_gif  = 'admin.gif';

# アイコンモード (0=no 1=yes)
$iconMode = 0;

# 返信がつくと親記事をトップへ移動 (0=no 1=yes)
$topsort = 1;

# タイトルにGIF画像を使用する時 (http://から記述)
$t_img = "";
$t_w = 150;	# 画像の幅 (ピクセル)
$t_h = 50;	#   〃  高さ (ピクセル)

# ファイルロック形式
#  → 0=no 1=symlink関数 2=mkdir関数
$lockkey = 0;

# ロックファイル名
$lockfile = './lock/yybbs.lock';

# ミニカウンタの設置
#  → 0=no 1=テキスト 2=画像
$counter = 1;

# ミニカウンタの桁数
$mini_fig = 6;

# テキストのとき：ミニカウンタの色
$cntCol = "#145170";

# 画像のとき：画像ディレクトリを指定
#  → 最後は必ず / で閉じる
$gif_path = "./img/";
$mini_w = 8;		# 画像の横サイズ
$mini_h = 12;		# 画像の縦サイズ

# カウンタファイル
$cntfile = './count.dat';

# 本体ファイルURL
$script = './yybbs.cgi';

# 更新ファイルURL
$regist = './yyregi.cgi';

# ログファイル
$logfile = './yylog.cgi';

# メールアドレスの入力必須 (0=no 1=yes)
$in_email = 0;

# 記事 [タイトル] 部の長さ (全角文字換算)
$sub_len = 12;

# 記事の [タイトル] 部の色
$subCol = "#4c5970";

# 記事表示部の下地の色
$tblCol = "#fafaff";

# 投稿フォーム及びボタンの文字色
$formCol1 = "#F7FAFD";	# 下地の色
$formCol2 = "#000000";	# 文字の色

# 家アイコンの使用 (0=no 1=yes)
$home_icon = 1;
$home_gif = "home.gif";	# 家アイコンのファイル名
$home_wid = 16;		# 画像の横サイズ
$home_hei = 20;		#   〃  縦サイズ

# イメージ参照画面の表示形態
#  1 : JavaScriptで表示
#  2 : HTMLで表示
$ImageView = 1;

# イメージ参照画面のサイズ (JavaScriptの場合)
$img_w = 550;	# 横幅
$img_h = 450;	# 高さ

# １ページ当たりの記事表示数 (親記事)
$pageView = 20;

# 投稿があるとメール通知する (sendmail必須)
#  0 : 通知しない
#  1 : 通知するが、自分の投稿記事は通知しない。
#  2 : すべて通知する。
$mailing = 0;

# メールアドレス(メール通知する時)
$mailto = 'xxx@xxx.xxx';

# sendmailパス（メール通知する時）
$sendmail = '/usr/lib/sendmail';

# 文字色の設定
#  →　スペースで区切る
$color = '#000000 #595959 #387a11 #05007f #C40026 #f200f2 #FF8040 #C100C1';

# URLの自動リンク (0=no 1=yes)
$autolink = 1;

# タグ広告挿入オプション
#  → <!-- 上部 --> <!-- 下部 --> の代わりに「広告タグ」を挿入
#  → 広告タグ以外に、MIDIタグ や LimeCounter等のタグにも使用可能
$banner1 = '<!-- 上部 -->';	# 掲示板上部に挿入
$banner2 = '<!-- 下部 -->';	# 掲示板下部に挿入

# ホスト取得方法
# 0 : gethostbyaddr関数を使わない
# 1 : gethostbyaddr関数を使う
$gethostbyaddr = 0;

# アクセス制限（半角スペースで区切る）
#  → 拒否するホスト名又はIPアドレスを記述（アスタリスク可）
#  → 記述例 $deny = '*.anonymizer.com 211.154.120.*';
$denyHost = '';

# 記事の更新は method=POST 限定する場合（セキュリティ対策）
#  → 0=no 1=yes
$postonly = 1;

# 他サイトから投稿排除時に指定する場合（セキュリティ対策）
#  → 掲示板のURLをhttp://から書く
$baseUrl = '';

# 投稿制限（セキュリティ対策）
#  0 : しない
#  1 : 同一IPアドレスからの投稿間隔を制限する
#  2 : 全ての投稿間隔を制限する
$regCtl = 1;

# 制限投稿間隔（秒数）
#  → $regCtl での投稿間隔
$wait = 8;

# 投稿後の処理
#  → 掲示板自身のURLを記述しておくと、投稿後リロードします
#  → ブラウザを再読み込みしても二重投稿されない措置。
#  → Locationヘッダの使用可能なサーバのみ
$location = '';

#---(以下は「過去ログ」機能を使用する場合の設定です)---#
#
# 過去ログ生成 (0=no 1=yes)
$pastkey = 0;

# 過去ログ用NOファイル
$nofile = './pastno.dat';

# 過去ログのディレクトリ
#  → フルパスなら / から記述（http://からではない）
#  → 最後は必ず / で閉じる
$pastdir = './past/';

# 過去ログ１ファイルの行数
#  → この行数を超えると次ページを自動生成します
$pastmax = 650;

# １ページ当たりの記事表示数 (親記事)
$pastView = 10;

#-------------------------------------------------
# ▲設定完了
#-------------------------------------------------

#-------------------------------------------------
#  投稿画面
#-------------------------------------------------
sub form {
	local($nam,$eml,$url,$pwd,$ico,$col,$sub,$com) = @_;
	local(@ico1,@ico2,@col);

	if ($url eq "") { $url = 'http://'; }
	$pattern = 'https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+';
	$com =~ s/<a href="$pattern" target="_blank">($pattern)<\/a>/$1/go;

	print <<EOM;
<table border=0 cellspacing=1>
<tr>
  <td><b>お名前</b></td>
  <td><input type=text name=name size=28 value="$nam" class=f></td>
</tr>
<tr>
  <td><b>Ｅメール</b></td>
  <td><input type=text name=email size=28 value="$eml" class=f></td>
</tr>
<tr>
  <td><b>タイトル</b></td>
  <td>
    	<input type=text name=sub size=36 value="$sub" class=f>
	<input type=submit value="投稿する"><input type=reset value="リセット">
  </td>
</tr>
<tr>
  <td colspan=2>
    <b>メッセージ</b><br>
    <textarea cols=56 rows=7 name=comment wrap="soft" class=f>$com</textarea>
  </td>
</tr>
EOM

	# 管理者アイコンを配列に付加
	@ico1 = split(/\s+/, $ico1);
	@ico2 = split(/\s+/, $ico2);
	if ($my_icon) {
		push(@ico1,$my_gif);
		push(@ico2,"管理者用");
	}
	if ($iconMode) {
		print "<tr><td><b>イメージ</b></td>
		<td><select name=icon class=f>\n";
		foreach(0 .. $#ico1) {
			if ($ico eq $ico1[$_]) {
				print "<option value=\"$_\" selected>$ico2[$_]\n";
			} else {
				print "<option value=\"$_\">$ico2[$_]\n";
			}
		}
		print "</select> &nbsp;\n";

		# イメージ参照のリンク
		if ($ImageView == 1) {
			print "[<a href=\"javascript:ImageUp()\">イメージ参照</a>]";
		} else {
			print "[<a href=\"$script?mode=image\" target=\"_blank\">イメージ参照</a>]";
		}
		print "</td></tr>\n";
	}

	if ($pwd ne "??") {
		print "<tr><td><b>暗証キー</b></td>";
		print "<td><input type=password name=pwd size=8 maxlength=8 value=\"$pwd\" class=f>\n";
		print "(英数字で8文字以内)</td></tr>\n";
	}
	print "<tr><td><b>文字色</b></td><td>";

	# 色情報
	@col = split(/\s+/, $color);
	if ($col eq "") { $col = 0; }
	foreach (0 .. $#col) {
		if ($col eq $col[$_] || $col eq $_) {
			print "<input type=radio name=color value=\"$_\" checked>";
			print "<font color=\"$col[$_]\">■</font>\n";
		} else {
			print "<input type=radio name=color value=\"$_\">";
			print "<font color=\"$col[$_]\">■</font>\n";
		}
	}

	print <<EOM;
</td></tr></table>
EOM
}

#-------------------------------------------------
#  アクセス制限
#-------------------------------------------------
sub axsCheck {
	# IP,ホスト取得
	$host = $ENV{'REMOTE_HOST'};
	$addr = $ENV{'REMOTE_ADDR'};
	if ($gethostbyaddr && ($host eq "" || $host eq $addr)) {
		$host = gethostbyaddr(pack("C4", split(/\./, $addr)), 2);
	}
	if ($host eq "") { $host = $addr; }

	local($flag)=0;
	foreach ( split(/\s+/, $denyHost) ) {
		s/(\W)/\\$1/g;
		s/\*/\.\*/g;
		if ($host =~ /$_/i || $addr =~ /$_/i) { $flag=1; last; }
	}
	if ($flag) { &error("アクセスを許可されていません"); }
}

#-------------------------------------------------
#  デコード処理
#-------------------------------------------------
sub decode {
	local($buf,$key,$val);
	undef(%in);

	if ($ENV{'REQUEST_METHOD'} eq "POST") {
		$post_flag=1;
		if ($ENV{'CONTENT_LENGTH'} > 51200) { &error("投稿量が大きすぎます"); }
		read(STDIN, $buf, $ENV{'CONTENT_LENGTH'});
	} else {
		$post_flag=0;
		$buf = $ENV{'QUERY_STRING'};
	}

	foreach ( split(/&/, $buf) ) {
		($key, $val) = split(/=/);
		$val =~ tr/+/ /;
		$val =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("H2", $1)/eg;

		# S-JISコード変換
		&jcode'convert(*val, "sjis", "", "z");

		# エスケープ
		$val =~ s/&/&amp;/g;
		$val =~ s/"/&quot;/g;
		$val =~ s/</&lt;/g;
		$val =~ s/>/&gt;/g;
		$val =~ s/\0//g;
		$val =~ s/\r\n/<br>/g;
		$val =~ s/\r/<br>/g;
		$val =~ s/\n/<br>/g;
		$val =~ s/[\x00-\x20]+/ /g;

		$in{$key} .= "\0" if (defined($in{$key}));
		$in{$key} .= $val;
	}
	if ($in{'sub'} eq "") { $in{'sub'} = "無題"; }
	$page = $in{'page'};
	$page =~ s/\D//g;
	if ($page < 0) { $page = 0; }
	$mode = $in{'mode'};

	$lockflag=0;
	$headflag=0;
}

#-------------------------------------------------
#  エラー処理
#-------------------------------------------------
sub error {
	# ロック中であれば解除
	if ($lockflag) { &unlock; }

	&header if (!$headflag);
	print <<EOM;
<div align="center">
<hr width=400><h3>ERROR !</h3>
<font color="red">$_[0]</font>
<p>
<hr width=400>
<p>
<form>
<input type=button value="前画面に戻る" onClick="history.back()">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  HTMLヘッダ
#-------------------------------------------------
sub header {
	$headflag=1;
	print "Content-type: text/html\n\n";
	print <<"EOM";
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=Shift_JIS">
<META HTTP-EQUIV="Content-Style-Type" content="text/css">
<STYLE type="text/css">
<!--
body,td,th { font-size:$bSize; font-family:"$bFace"; }
a { text-decoration:none; }
a:hover { text-decoration:underline; color:$alink; }
.n { font-family:Verdana,Helvetica,Arial; }
.b {
	background-color:$formCol1;
	color:$formCol2;
	font-family:Verdana,Helvetica,Arial;
	}
.f {
	background-color:$formCol1;
	color:$formCol2;
	}
-->
</STYLE>
EOM
	# JavaScriptヘッダ
	if ($ImageView == 1 && $_[0] eq "ImageUp") {
		print "<META http-equiv=\"Content-Script-Type\" content=\"text/javascript\">\n";
		print "<SCRIPT type=\"text/javascript\">\n";
		print "<!--\nfunction ImageUp() {\n";
		print "window.open(\"$script?mode=image\",\"window1\",\"width=$img_w,height=$img_h,scrollbars=1\");\n}\n//-->\n</SCRIPT>\n";
	}

	print "<title>$title</title></head>\n";
	if ($backgif) {
		print "<body background=\"$backgif\" bgcolor=\"$bgcolor\" text=\"$text\" link=\"$link\" vlink=\"$vlink\" alink=\"$alink\">\n";
	} else {
		print "<body bgcolor=\"$bgcolor\" text=\"$text\" link=\"$link\" vlink=\"$vlink\" alink=\"$alink\">\n";
	}
}

#-------------------------------------------------
#  ロック処理
#-------------------------------------------------
sub lock {
	# リトライ回数
	local($retry)=5;

	# 古いロックは削除する
	if (-e $lockfile) {
		local($mtime) = (stat($lockfile))[9];
		if ($mtime < time - 30) { &unlock; }
	}

	# symlink関数式ロック
	if ($lockkey == 1) {
		while (!symlink(".", $lockfile)) {
			if (--$retry <= 0) { &error('LOCK is BUSY'); }
			sleep(1);
		}

	# mkdir関数式ロック
	} elsif ($lockkey == 2) {
		while (!mkdir($lockfile, 0755)) {
			if (--$retry <= 0) { &error('LOCK is BUSY'); }
			sleep(1);
		}
	}
	$lockflag=1;
}

#-------------------------------------------------
#  ロック解除
#-------------------------------------------------
sub unlock {
	if ($lockkey == 1) {
		unlink($lockfile);
	} elsif ($lockkey == 2) {
		rmdir($lockfile);
	}

	$lockflag=0;
}

#-------------------------------------------------
#  クッキー発行
#-------------------------------------------------
sub set_cookie {
	local(@cook) = @_;
	local($gmt, $cook, @t, @m, @w);

	@t = gmtime(time + 60*24*60*60);
	@m = ('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
	@w = ('Sun','Mon','Tue','Wed','Thu','Fri','Sat');

	# 国際標準時を定義
	$gmt = sprintf("%s, %02d-%s-%04d %02d:%02d:%02d GMT",
			$w[$t[6]], $t[3], $m[$t[4]], $t[5]+1900, $t[2], $t[1], $t[0]);

	# 保存データをURLエンコード
	foreach (@cook) {
		s/(\W)/sprintf("%%%02X", unpack("C", $1))/eg;
		$cook .= "$_<>";
	}

	# 格納
	print "Set-Cookie: YY_BOARD=$cook; expires=$gmt\n";
}

#-------------------------------------------------
#  クッキー取得
#-------------------------------------------------
sub get_cookie {
	local($key, $val, *cook);

	# クッキー取得
	$cook = $ENV{'HTTP_COOKIE'};

	# 該当IDを取り出す
	foreach ( split(/;/, $cook) ) {
		($key, $val) = split(/=/);
		$key =~ s/\s//g;
		$cook{$key} = $val;
	}

	# データをURLデコードして復元
	@cook=();
	foreach ( split(/<>/, $cook{'YY_BOARD'}) ) {
		s/%([0-9A-Fa-f][0-9A-Fa-f])/pack("H2", $1)/eg;

		push(@cook,$_);
	}
	return (@cook);
}

#-------------------------------------------------
#  移動ボタン
#-------------------------------------------------
sub mvbtn {
	local($link,$i,$view) = @_;
	local($start,$end,$x,$y,$bk_bl,$fw_bl);

#	if ($in{'view'}) { $view = $in{'view'}; }

	if ($in{'bl'}) {
		$start = $in{'bl'}*10 + 1;
		$end   = $start + 9;
	} else {
		$in{'bl'} = 0;
		$start = 1;
		$end   = 10;
	}

	$x=1; $y=0;
	while ($i > 0) {
		# 当ページ
		if ($page == $y) {

			print "| <b style='color:red' class=n>$x</b>\n";

		# 切替ページ
		} elsif ($x >= $start && $x <= $end) {

			print "| <a href=\"$link$y&bl=$in{'bl'}\" class=n>$x</a>\n";

		# 前ブロック
		} elsif ($x == $start-1) {

			$bk_bl = $in{'bl'}-1;
			print "| <a href=\"$link$y&bl=$bk_bl\">←</a>\n";

		# 次ブロック
		} elsif ($x == $end+1) {

			$fw_bl = $in{'bl'}+1;
			print "| <a href=\"$link$y&bl=$fw_bl\">→</a>\n";

		}

		$x++;
		$y += $view;
		$i -= $view;
	}

	print "|\n";
}

#-------------------------------------------------
#  検索処理
#-------------------------------------------------
sub search {
	local($file,$word,$view,$cond) = @_;
	local($i,$f,$top,$wd,$next,$back,@wd);

	# キーワードを配列化
	$word =~ s/\x81\x40/ /g;
	@wd = split(/\s+/, $word);

	# ファイル展開
	print "<dl>\n";
	$i=0;
	open(IN,"$file") || &error("Open Error: $file");
	$top = <IN> if ($mode ne "past");
	while (<IN>) {
		$f=0;
		foreach $wd (@wd) {
			if (index($_,$wd) >= 0) {
				$f++;
				if ($cond eq 'OR') { last; }
			} else {
				if ($cond eq 'AND') { $f=0; last; }
			}
		}

		# ヒットした場合
		if ($f) {
			$i++;
			next if ($i < $page + 1);
			next if ($i > $page + $view);

			($no,$reno,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);
			if ($eml) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }
			if ($url) { $url = "&lt;<a href=\"$url\" target=\"_blank\">Home</a>&gt;"; }
			# 結果を表示
			print "<dt><hr>[<b>$no</b>] <b style=\"color:$subCol\">$sub</b> ";
			print "投稿者：<b>$nam</b> 投稿日：$dat $url<br><br>\n";
			print "<dd style=\"color:$col\">$com\n";
		}
	}
	close(IN);

	print <<EOM;
<dt><hr>
検索結果：<b>$i</b>件
</dl>
EOM
	$next = $page + $view;
	$back = $page - $view;
	return ($i, $next, $back);
}

#-------------------------------------------------
#  URLエンコード
#-------------------------------------------------
sub url_enc {
	local($_) = @_;

	s/(\W)/'%' . unpack('H2', $1)/eg;
	s/\s/+/g;
	$_;
}


1;

__END__

