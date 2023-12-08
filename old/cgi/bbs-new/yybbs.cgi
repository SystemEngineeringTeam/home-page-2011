#!/usr/bin/perl
#┌─────────────────────────────────
#│  YY-BOARD - yybbs.cgi - 2004/09/01
#│  Copyright (c) KentWeb
#│  webmaster@kent-web.com
#│  http://www.kent-web.com/
#└─────────────────────────────────

# 外部ファイル取込
require './jcode.pl';
require './yyini.cgi';

# メイン処理
&decode;
&axsCheck;
if ($mode eq "howto") { &howto; }
elsif ($mode eq "find") { &find; }
elsif ($mode eq "res") { &resForm; }
elsif ($mode eq "image") { &image; }
elsif ($mode eq "check") { &check; }
&logView;

#-------------------------------------------------
#  記事表示部
#-------------------------------------------------
sub logView {
	local($next,$back,$i,$flag);

	# クッキー取得
	local($cnam,$ceml,$curl,$cpwd,$cico,$ccol) = &get_cookie;
	$curl ||= 'http://';

	# ヘッダを出力
	if ($ImageView == 1) { &header('ImageUp'); }
	else { &header; }

	# カウンタ処理
	if ($counter) { &counter; }

	# タイトル部
	print "<div align=\"center\">\n";
	if ($banner1 ne "<!-- 上部 -->") { print "$banner1<p>\n"; }
	if ($t_img eq '') {
		print "<b style='color:$tCol; font-size:$tSize;'>$title</b>\n";
	} else {
		print "<img src=\"$t_img\" width=\"$t_w\" height=\"$t_h\" alt=\"$title\">\n";
	}

	print <<EOM;
<hr width="90%">
[<a href="../../index.html" target="_top"><B>トップに戻る</B></a>]
[<a href="$script?mode=howto"><B>留意事項</B></a>]
[<a href="$script?mode=find"><B>ワード検索</B></a>]
EOM

	# 過去ログのリンク部を表示
	if ($pastkey) {	print "[<a href=\"$regist?mode=past\">過去ログ</a>]\n"; }

	print <<EOM;
[<a href="$regist?mode=admin"><B>管理用</B></a>]
<hr width="90%"></div>
<blockquote>
<form method="POST" action="$regist">
<input type=hidden name=mode value="regist">
<input type=hidden name=page value="$page">
EOM

	&form($cnam,$ceml,$curl,$cpwd,$cico,$ccol,'','');

	print <<EOM;
</form>
</blockquote>
<br>
<div align="center">
EOM

	local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico);

	# 記事を展開
	$i=0;
	$flag=0;
	open(IN,"$logfile") || &error("Open Error: $logfile");
	$top = <IN>;
	while (<IN>) {
		($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);

		if ($re eq "") { $i++; }
		if ($i < $page + 1) { next; }
		if ($i > $page + $pageView) { next; }

		# 題名の長さ
		if (length($sub) > $sub_len*2) {
			$sub = substr($sub,0,$sub_len*2);
			$sub .= "...";
		}

		if ($eml) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }
		if ($home_icon && $url) {
			$url = "<a href=\"$url\" target=\"_blank\"><img src=\"$imgurl$home_gif\" border=0 align=top alt='HomePage' width=\"$home_wid\" height=\"$home_hei\"></a>";
		} elsif (!$home_icon && $url) {
			$url = "&lt;<a href=\"$url\" target=\"_blank\">Home</a>&gt;";
		}
		if (!$iconMode) { $com = "<blockquote>$com</blockquote>"; }

		if (!$re && $flag) {
			print "</TD></TR></TABLE><br><br>\n";
			$flag=1;
		}
		if (!$re) {
			print "<TABLE BORDER=1 WIDTH='90%' BGCOLOR=\"$tblCol\" CELLSPACING=0 CELLPADDING=2><TR><TD>\n";
			$flag=1;
		}

		if ($re) { print "<hr noshade size=1 width='85%'>\n"; }
		print "<table border=0 cellpadding=2><tr>\n";
		if ($re) { print "<td rowspan=2 width=40><br></td>"; }

		print "<td valign=top nowrap><font color=\"$subCol\"><b>$sub</b></font>　";

		if (!$re) { print "投稿者：<b>$nam</b> 投稿日：$dat "; }
		else { print "<b>$nam</b> - $dat "; }

		print "<font color=\"$subCol\">No\.$no</font></td>";
		print "<td valign=top nowrap> &nbsp; $url </td><td valign=top>\n";

		if (!$re) {
			print "<form action=\"$script\">\n";
			print "<input type=hidden name=mode value=res>\n";
			print "<input type=hidden name=no value=\"$no\">\n";
			print "<input type=submit value='返信'></td></form>\n";
		} else {
			print "<br></td>\n";
		}

		print "</tr></table><table border=0 cellpadding=5><tr>\n";
		if ($re) { print "<td width=32><br></td>\n"; }

		# アイコンモード
		if ($iconMode) { print "<td><img src=\"$imgurl$ico\" alt=\"$ico\"></td>"; }

		print "<td><font color=\"$col\">$com</font></td></tr></table>\n";
	}
	close(IN);

	print "</TD></TR></TABLE>\n";

	# ページ移動ボタン表示
	if ($page - $pageView >= 0 || $page + $pageView < $i) {
		print "<p><table width=\"90%\"><tr><td>\n";
		&mvbtn("$script?page=", $i, $pageView);
		print "</td></tr></table>\n";
	}

	# 著作権表示（削除不可）: 但し、MakiMakiさんの画像を使用しない場合に限り、
	# MakiMakiさんのリンクを外すことは可能です。
	print <<EOM;
<form action="$regist" method="POST">
<select name=mode class=f>
<option value="edit">修正
<option value="dele">削除
</select>
<span class=n>
NO:<input type=text name=no size=3 class=f>
PASS:<input type=password name=pwd size=6 maxlength=8 class=f>
</span>
<input type=submit value="送信" class=f></form>
$banner2
<p>
<!-- $ver -->
<span style="font-size:10px; font-family:Verdana,Helvetica,Arial;">
- <a href="http://www.aitech.ac.jp/" target="_top">愛知工業大学</a> & 
<a href="../../index.html" target="_top">システム工学研究会</a> -
</span>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  返信フォーム
#-------------------------------------------------
sub resForm {
	local($f,$no,$reno,$dat,$nam,$eml,$sub,$com,$url);

	# クッキーを取得
	local($cnam,$ceml,$curl,$cpwd,$cico,$ccol) = &get_cookie;
	if (!$curl) { $curl = 'http://'; }

	# ログを読み込み
	$f=0;
	open(IN,"$logfile") || &error("Open Error: $logfile");
	$top = <IN>;

	# ヘッダを出力
	if ($ImageView == 1) { &header('ImageUp'); }
	else { &header; }

	# 関連記事出力
	print <<EOM;
<form>
<input type="button" value="前画面に戻る" onClick="history.back()">
</form>
▽以下は記事NO. <B>$in{'no'}</B> に関する<a href='#RES'>返信フォーム</a>です。
<hr>
EOM

	while (<IN>) {
		($no,$reno,$dat,$nam,$eml,$sub,$com,$url) = split(/<>/);
		if ($in{'no'} == $no && $reno) { $f++; }
		if ($in{'no'} == $no || $in{'no'} == $reno) {

			if (length($sub) > $sub_len*2) {
				$sub = substr($sub,0,$sub_len*2-4);
				$sub .= "...";
			}
			if ($in{'no'} == $no) { $resub = $sub; }
			if ($url) { $url = "&lt;<a href=\"$url\">Home</a>&gt;"; }
			if ($reno) { print '&nbsp;&nbsp;'; }

			print "<font color=\"$subCol\"><b>$sub</b></font>\n";
			print "投稿者：<b>$nam</b> 投稿日：$dat $url ";
			print "<font color=\"$subCol\">No\.$no</font><br>";
			print "<blockquote>$com</blockquote><hr>\n";
		}
	}
	close(IN);
	if ($f) { &error("不正な返信要求です"); }

	# タイトル名
	if ($resub !~ /^Re\:/) { $resub = "Re: $resub"; }

	print <<"EOM";
<a name="RES"></a>
<form action="$regist" method="POST">
<input type=hidden name=mode value="regist">
<input type=hidden name=reno value="$in{'no'}">
<input type=hidden name=page value="$page">
<blockquote>
EOM

	&form($cnam,$ceml,$curl,$cpwd,$cico,$ccol,$resub,'');

	print <<EOM;
</form>
</blockquote>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  留意事項
#-------------------------------------------------
sub howto {
	&header;
	print <<"EOM";
<div align="center">
<table width="90%" border=1 cellpadding=10>
<tr><td bgcolor="$tblCol">
<h3>留意事項</h3>
<ol>
<li>この掲示板は<b>クッキー対応</b>です。1度記事を投稿いただくと、お名前、Ｅメール、参照先、暗証キーの情報は2回目以降は自動入力されます。（ただし利用者のブラウザがクッキー対応の場合）
<li>投稿内容には、<b>タグは一切使用できません。</b>
<li>記事を投稿する上での必須入力項目は<b>「お名前」</b>と<b>「メッセージ」</b>です。Ｅメール、参照先、題名、暗証キーは任意です。
<li>記事には、<b>半角カナは一切使用しないで下さい。</b>文字化けの原因となります。
<li>記事の投稿時に<b>「暗証キー」</b>に任意のパスワード（英数字で8文字以内）を入れておくと、その記事は次回<b>暗証キー</b>によって修正及び削除することができます。
<li>記事の保持件数は<b>最大$max件</b>です。それを超えると古い順に自動削除されます。
<li>既存の記事に<b>「返信」</b>をすることができます。各記事の上部にある<b>「返信」</b>ボタンを押すと返信用フォームが現れます。
<li>過去の投稿記事から<b>「キーワード」によって簡易検索ができます。</b>トップメニューの<a href="$script?mode=find">「ワード検索」</a>のリンクをクリックすると検索モードとなります。
<li>管理者が著しく不利益と判断する記事や他人を誹謗中傷する記事は予\告なく削除することがあります。
</ol>
</td></tr></table>
<p>
<form>
<input type=button value="掲示板に戻る" onClick="history.back()">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  ワード検索
#-------------------------------------------------
sub find {
	&header;
	print <<EOM;
<form action="$script">
<input type=submit value="掲示板に戻る"></form>
<ul>
<li>キーワードを入力し、「条件」「表\示」を選択して検索ボタンを押して下さい。
<li>キーワードはスペースで区切って複数指定することができます。
<p>
<form action="$script" method="POST">
<input type=hidden name=mode value="find">
キーワード <input type=text name=word size=35 value="$in{'word'}" class=f>
条件 <select name=cond class=f>
EOM

	if (!$in{'cond'}) { $in{'cond'} = "AND"; }
	foreach ("AND", "OR") {
		if ($in{'cond'} eq $_) {
			print "<option value=\"$_\" selected>$_\n";
		} else {
			print "<option value=\"$_\">$_\n";
		}
	}

	if (!$in{'view'}) { $in{'view'} = 10; }
	print "</select> 表\示 <select name=view class=f>\n";
	foreach (10,15,20,25) {
		if ($in{'view'} == $_) {
			print "<option value=\"$_\" selected>$_件\n";
		} else {
			print "<option value=\"$_\">$_件\n";
		}
	}

	print <<EOM;
</select>
<input type=submit value="検索">
</form>
</ul>
EOM

	# 検索実行
	if ($in{'word'} ne "") {
		($i,$next,$back) = &search($logfile,$in{'word'},$in{'view'},$in{'cond'});

		$enwd = &url_enc($in{'word'});
		if ($back >= 0) {
			print "[<a href=\"$script?mode=find&page=$back&word=$enwd&view=$in{'view'}&cond=$in{'cond'}\">前の$in{'view'}件</a>]\n";
		}
		if ($next < $i) {
			print "[<a href=\"$script?mode=find&page=$next&word=$enwd&view=$in{'view'}&cond=$in{'cond'}\">次の$in{'view'}件</a>]\n";
		}
	}

	print "</body></html>\n";
	exit;
}

#-------------------------------------------------
#  カウンタ処理
#-------------------------------------------------
sub counter {
	local($count,$cntup,@count);

	# 閲覧時のみカウントアップ
	if ($mode eq '') { $cntup=1; } else { $cntup=0; }

	# カウントファイルを読みこみ
	open(IN,"$cntfile") || &error("Open Error: $cntfile");
	eval "flock(IN, 1);";
	$count = <IN>;
	close(IN);

	# IPチェックとログ破損チェック
	local($cnt, $ip) = split(/:/, $count);
	if ($addr eq $ip || $cnt eq "") { $cntup=0; }

	# カウントアップ
	if ($cntup) {
		$cnt++;
		open(OUT,"+< $cntfile") || &error("Write Error: $cntfile");
		eval "flock(OUT, 2);";
		truncate(OUT, 0);
		seek(OUT, 0, 0);
		print OUT "$cnt\:$addr";
		close(OUT);
	}

	# 桁数調整
	while(length($cnt) < $mini_fig) { $cnt = '0' . $cnt; }
	@count = split(//, $cnt);

	# GIFカウンタ表示
	if ($counter == 2) {
		foreach (0 .. $#count) {
			print "<img src=\"$gif_path$count[$_]\.gif\" alt=\"$count[$_]\" width=\"$mini_w\" height=\"$mini_h\">";
		}
	# テキストカウンタ表示
	} else {
		print "<font color=\"$cntCol\" face=\"Verdana,Helvetica,Arial\">$cnt</font><br>\n";
	}
}

#-------------------------------------------------
#  画像イメージ表示
#-------------------------------------------------
sub image {
	local($i,$j,$stop);

	&header;
	print <<EOM;
<div align="center">
<h4>画像イメージ</h4>
<table border=1 cellpadding=5 cellspacing=0 bgcolor="$tblCol">
<tr>
EOM

	@ico1 = split(/\s+/, $ico1);
	@ico2 = split(/\s+/, $ico2);

	$i=0; $j=0;
	$stop = @ico1;
	foreach (0 .. $#ico1) {
		$i++; $j++;
		print "<th><img src=\"$imgurl$ico1[$_]\" ALIGN=middle alt=\"$ico1[$_]\">
		$ico2[$_]</th>\n";

		if ($j != $stop && $i >= 5) {
			print "</tr><tr>\n";
			$i=0;

		} elsif ($j == $stop) {
			if ($i == 0) { last; }
			while ($i < 5) { print "<th><br></th>"; $i++; }
		}
	}

	print <<EOM;
</tr></table><br>
<form>
<input type=button value="ウィンドウを閉じる" onClick="top.close();">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  チェックモード
#-------------------------------------------------
sub check {
	&header;
	print <<EOM;
<h2>Check Mode</h2>
<ul>
EOM

	# ログパス
	if (-e $logfile) { print "<li>ログファイルのパス：OK\n"; }
	else { print "<li>ログファイルのパス：NG → $logfile\n"; }

	# ログパーミッション
	if (-r $logfile && -w $logfile) { print "<li>ログファイルのパーミッション：OK\n"; }
	else { print "<li>ログファイルのパーミッション：NG\n"; }

	# カウンタログ
	print "<li>カウンタ：";
	if ($counter) {
		print "設定あり\n";
		if (-e $cntfile) { print "<li>カウンタログファイルのパス：OK\n"; }
		else { print "<li>カウンタログファイルのパス：NG → $cntfile\n"; }
	}
	else { print "設定なし\n"; }

	# ロックディレクトリ
	print "<li>ロック形式：";
	if ($lockkey == 0) { print "ロック設定なし\n"; }
	else {
		if ($lockkey == 1) { print "symlink\n"; }
		else { print "mkdir\n"; }

		($lockdir) = $lockfile =~ /(.*)[\\\/].*$/;
		print "<li>ロックディレクトリ：$lockdir\n";

		if (-d $lockdir) { print "<li>ロックディレクトリのパス：OK\n"; }
		else { print "<li>ロックディレクトリのパス：NG → $lockdir\n"; }

		if (-r $lockdir && -w $lockdir && -x $lockdir) {
			print "<li>ロックディレクトリのパーミッション：OK\n";
		} else {
			print "<li>ロックディレクトリのパーミッション：NG → $lockdir\n";
		}
	}

	# 過去ログ
	print "<li>過去ログ：";
	if ($pastkey == 0) { print "設定なし\n"; }
	else {
		print "設定あり\n";

		# NOファイル
		if (-e $nofile) { print "<li>NOファイルパス：OK\n"; }
		else { print "<li>NOファイルのパス：NG → $nofile\n"; }
		if (-r $nofile && -w $nofile) { print "<li>NOファイルパーミッション：OK\n"; }
		else { print "<li>NOファイルパーミッション：NG → $nofile\n"; }

		# ディレクトリ
		if (-d $pastdir) { print "<li>過去ログディレクトリパス：OK\n"; }
		else { print "<li>過去ログディレクトリのパス：NG → $pastdir\n"; }
		if (-r $pastdir && -w $pastdir && -x $pastdir) {
			print "<li>過去ログディレクトリパーミッション：OK\n";
		} else {
			print "<li>過去ログディレクトリパーミッション：NG → $pastdir\n";
		}
	}

	print <<EOM;
</ul>
</body>
</html>
EOM
	exit;
}


__END__

