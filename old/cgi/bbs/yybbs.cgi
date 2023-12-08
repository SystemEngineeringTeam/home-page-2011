#!/usr/local/bin/perl

#┌─────────────────────────────────
#│ YY-BOARD
#│ yybbs.cgi - 2004/09/01
#│ Copyright (c) KentWeb
#│ webmaster@kent-web.com
#│ http://www.kent-web.com/
#│
#│ YY-BOARD v5.5用携帯電話対応スクリプト
#│ 2005/1/4　湯一路　http://www.url-battle.com/cgi/
#│
#│ Modified by isso. August, 2006
#│ http://swanbay-web.hp.infoseek.co.jp/index.html
#└─────────────────────────────────

# 外部ファイル取込
require './jcode.pl';
#require './keitai.cgi';
require './yyini.cgi';

if($writevalue eq $postvalue) {
	&error("\$writevalueと\$postvalueの文字は同じにしないでください"); }

# メイン処理
&decode;
&axsCheck;

$sortnew = ($mode eq "sort");
$preview = ($mode eq "preview");
$msgview = ($mode eq "msgview");

if ($mode eq "imode") { &imode_write; }
elsif ($mode eq "howto") { &howto; }
elsif ($mode eq "find") { &find; }
elsif ($mode eq "res") { &resForm; }
elsif ($mode eq "image") { &image; }
elsif ($mode eq "check") { &check; }
elsif ($mode eq "noscript") { &noscript; }
elsif ($mode eq "idel") { &imode_del; }
elsif ($mode eq "iadmin") { &imode_admin; }
elsif ($mode eq "mobile_mail") { &mobile_mail; }
elsif ($mode eq "mobile_sendmail") { &mobile_sendmail; }
elsif ($mode eq "sendmail") { &send_mail; }

if ($imode && $imode_out && !$sortnew && !$preview && !$msgview) {$preview = 1;}
if ($sortnew || $preview) {$p_log = $new_log;$pageView = $p_log;}
if(!$imode_out && $imode && !$sortnew && !$preview){$pageView = $imodenum;}

&logView;

#-------------------------------------------------
#  記事表示部
#-------------------------------------------------
sub logView {
	local($next,$back,$i,$flag);

	# クッキー取得
	local($cnam,$ceml,$curl,$cpwd,$cico,$ccol,$caikotoba) = &get_cookie;
#	$curl ||= 'http://';

	# ヘッダを出力
	if ($ImageView == 1) { &header('ImageUp'); }
	else { &header; }

	# カウンタ処理
	if ($counter) { &counter; }

	if (!$imode){

	# タイトル部
	print "<div align=\"center\">\n";
	if ($banner1 ne "<!-- 上部 -->") { print "$banner1<p>\n"; }
	if ($t_img eq '') {
		print "<b style='color:$tCol; font-size:$tSize;'>$title</b>\n";
	} else {
		print "<img src=\"$t_img\" width=\"$t_w\" height=\"$t_h\" alt=\"$title\">\n";
	}

	local($access) = &encode_bbsmode();
	if ( (-s $spamlogfile) > $spamlog_maxfile ) {
		print "<br>\n<br>\n<b style='color:#FF0000'>投稿拒否ログ(スパムログ)のファイルサイズが大きくなりました。<br>",
		"至急、管理モードから投稿拒否ログを削除して下さい。</b><br>\n<br>\n";
	}

	print "$imode_msg\n";

	print <<EOM;
<hr width="90%">
[<a href="$homepage" target="_top">トップに戻る</a>]
[<a href="$script?mode=howto">留意事項</a>]
[<a href="$script?mode=find">ワード検索</a>]
EOM

	# 過去ログのリンク部を表示
	if ($pastkey) {	print "[<a href=\"$regist?mode=past\">過去ログ</a>]\n"; }
	# 掲示板アドレスメール送信機能を表示
	if ($send_mail) {	print "[<a href=\"$script?mode=mobile_mail\">携帯に掲示板アドレスを送信</a>]\n"; }

	print <<EOM;
[<a href="$regist?mode=admin">管理用</a>]
<hr width="90%"></div>
<blockquote>
<form method="$method" action="$regist">
EOM
	if (!$imode) {
		if ($javascriptpost) {
			print <<EOM;
<script type="text/javascript">
<!-- //
fcheck("me=$bbscheckmode val","<inpu","t type=hidden na","ue=$access>");
// -->
</script>
EOM
		} else { print "<input type=hidden name=$bbscheckmode value=$access>\n"; }
	} else { print "<input type=hidden name=$bbscheckmode value=$access>\n"; }
	print <<EOM;
<!-- //
<input type=hidden name=mode value="write">
// -->
<input type=hidden name=page value="$page">
EOM

	&form($cnam,$ceml,$curl,$cpwd,$cico,$ccol,'','');

	print <<EOM;
</form>
</blockquote>
<br>
<div align="center">
EOM

	}else{
		if ($title_gif eq '') {
			print "$title<br>\n";
		}else{
			print "<img src=\"$title_gif\"><br>\n";
		}
		print "<a href=\"$script?mode=imode\">書</a>";

		if (!$preview){
			print "/<a href=\"$script?mode=preview\">一覧</a>";
		}

		if ($ihomepage eq ""){
		}else{
			print "/<a href=\"$ihomepage\">戻</a>";
		}
		if ($newok){
			if ($preview && !$imode_out){
				print "/<a href=\"$script?mode=sort\">新</a>/<a href=\"$script\">標</a>";
			}elsif(!$sortnew){
				print "/<a href=\"$script?mode=sort\">新</a>";
			}else{
				print "/<a href=\"$script\">標</a>";
			}
		}

		if ($imode_del){
			print "/<a href=\"$script?mode=idel\">消</a>";
		}
		if ($imode_admin){
			print "/<a href=\"$script?mode=iadmin\">管</a>";
		}

	}

	#新着順ソートルーチン
	if ($sortnew) {
		# 記事を展開
		open(IN,"$logfile") || &error("Open Error : $logfile");
			@logdata = <IN>;
		close(IN);
		$temp = shift(@logdata);

		#投稿Noでソート
		@tmp = map {(split /,/)[0]} @logdata;
		@logdata = @logdata[sort {$tmp[$b] <=> $tmp[$a]} 0 .. $#tmp];
		foreach $log (@logdata) {
			local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/,$log);

			$i++;
			if ($i < $page + 1) { next; }
			if ($i > $page + $pageView) { next; }

			#imode用
			if ($eml && $mailview) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }

			#時間変換
			&time_change;

			print "<hr>[$no]【$sub】<br>\n";

			if (!$re) {
				print "TO:$nam<br>$dat<br>\n";
				if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}
				print "[<a href=\"$script?mode=imode&re=$no\">返信</a>] &nbsp; <br>";
}
			else {
				 print "→TO:$nam<br>$dat<br>\n";
				if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}
				print "[<a href=\"$script?mode=imode&re=$re\">返信</a>] &nbsp; <br>";
			}

			print "$com\n";

		}
	} elsif ($msgview){

		# 記事を展開
		open(IN,"$logfile") || &error("Open Error : $logfile");
		$top = <IN>;

		while (<IN>) {
		local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);
		if ($in{'no'} eq "$no" || $in{'no'} eq "$re") {
				#時間変換
				&time_change;
				if ($eml && $mailview) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }

				if (!$re) {
					print "<hr size=2>[$no]【$sub】<br>\n";

					print "TO:$nam<br>$dat<br>\n";
					if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}
					print "[<a href=\"$script?mode=imode&re=$no\">返信</a>] &nbsp; <br>";
				}else{
					print "<hr size=1>[$no]【$sub】<br>\n";

					print "→TO:$nam<br>$dat<br>\n";
					if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}
#					print "[<a href=\"$script?mode=imode&re=$re\">返信</a>] &nbsp; <br>";
				}

				print "$com\n";
				#last;
			}
		}
		print "<hr>[<a href=\"$script?mode=imode&re=$in{'no'}\">返信</a>] &nbsp; <br>";
		if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}

		print "[<a href=\"$script?mode=preview\">一覧へ戻る</a>]\n";
		close(IN);

	}else{

	local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico);

	# 記事を展開
	$i=0;
	$flag=0;
	open(IN,"$logfile") || &error("Open Error: $logfile");
	$top = <IN>;
	while (<IN>) {
		($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);

		if ($re eq "") {
			#レスの数を計算して表示
			if ($preview && $i >= $page && $i <= $page + $pageView){
				if ($rcount > 0){
					print "($rcount)\n";
				}
				if ($i){
					print "<br>\n";
				}else{
					print "<hr>\n";
				}
			}
			$i++;
		}
		if ($i < $page + 1) { next; }
		if ($i > $page + $pageView) { next; }

		if ($imode == 0){

		# 題名の長さ
		if (length($sub) > $sub_len*2) {
			$sub = substr($sub,0,$sub_len*2);
			$sub .= "...";
		}

#		if ($eml) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }

			if (!$imode && $eml) { ($em0,$em1) = split(/\@/,$eml);
			$em1 =~ s/\./&\#46\;/g;
			$nam = "<script type=\"text/javascript\">\n<!-- //\n".
			"address(\"$em0\",\"$nam\",\"$em1\");\n// -->\n</script>\n".
			"<noscript><a href=\"$script?mode=noscript&page=$page\">$nam</a></noscript>\n";
			}

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

		}else{
			if (!$re && $flag) {
				$flag=1;
			}
			if (!$re) {
				$flag=1;
			}

			#時間変換
			&time_change;

			if (!$preview){
				if ($eml && $mailview) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }
				if (!$re) {
					print "<hr size=2>[$no]【$sub】<br>\n";

					print "TO:$nam<br>$dat<br>\n";
					if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}

					print "[<a href=\"$script?mode=imode&re=$no\">返信</a>] &nbsp; <br>";
				} else {
					print "<hr  size=1>[$no]【$sub】<br>\n";
					print "→TO:$nam<br>$dat<br>\n";
					if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}

				}

				print "$com\n";

			}else{
				#一覧表示　投稿番号下3桁＋タイトル全角9文字＋/＋名前１文字
				if (length($sub) > 18){$sub = substr($sub,0,18);}
				if (length($nam) > 6){$nam = substr($nam,0,6);}

				#$sub = byte_check($sub);

				#全角が分断されていないかチェック
				#$name = byte_check($name);

				$temp = sprintf("%-8s..%-4s", $sub, $nam);

				$tempno = $no;
				if (length($tempno) > 3){$name = substr($tempno,0,3)}
				$tempno = sprintf("%-3d", $tempno);

				if (!$re) {
					print "<a href=\"$script?mode=msgview&no=$no\">$tempno</a>$temp\n";
					$rcount = 0;
				}else{
					$rcount++;
				}

			}


		}
	}
	close(IN);

	#レスの数を計算して表示(末尾用)
	if ($preview && $i >= $page && $i <= $page + $pageView){
		if ($rcount > 0){
			print "($rcount)<br>\n";
		}
	}


	if (!$imode){print "</TD></TR></TABLE>\n";}
	}

	if (!$imode){
		# ページ移動ボタン表示
		if ($page - $pageView >= 0 || $page + $pageView < $i) {
			print "<p><table width=\"90%\"><tr><td>\n";
			&mvbtn("$script?page=", $i, $pageView);
			print "</td></tr></table>\n";
		}
	}else{
		# ページ移動ボタン表示
		if ($page - $pageView >= 0 || $page + $pageView < $i) {
			print "<hr>\n";
			if ($sortnew) {
				&mvbtn("$script?mode=sort&page=", $i, $pageView);
			} else {
				&mvbtn("$script?page=", $i, $pageView);
			}
		}
	}

	if (!$imode){
	# 著作権表示（削除不可）: 但し、MakiMakiさんの画像を使用しない場合に限り、
	# MakiMakiさんのリンクを外すことは可能です。
	print <<EOM;
<form action="$regist" method="$method">
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
- <a href="http://www.kent-web.com/" target="_top">KENT</a> &amp; 
<a href="http://homepage3.nifty.com/makiz/" target="_top">MakiMaki</a> -
<br>携帯用改造：<a href='http://www.url-battle.com/cgi/' target='_top'>湯一路</a>
&nbsp;&amp;&nbsp;
<a href='http://swanbay-web.hp.infoseek.co.jp/index.html' target='_top'>isso</a>
</span>
</div>
EOM

	}else{
	print <<EOM;
<hr>オリジナル：KENT<br>
携帯用改造：<a href='http://202.212.214.232/bbs/keitai.shtml' target='_top'>湯一路</a><br>
スパム対策：isso
EOM
	}

	print <<EOM;
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
	local($cnam,$ceml,$curl,$cpwd,$cico,$ccol,$caikotoba) = &get_cookie;
#	if (!$curl) { $curl = 'http://'; }

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
	local($access) = &encode_bbsmode();

	print <<"EOM";
<a name="RES"></a>
<form action="$regist" method="$method">
EOM
	if (!$imode) {
		if ($javascriptpost) {
			print <<EOM;
<script type="text/javascript">
<!-- //
fcheck("me=$bbscheckmode val","<inpu","t type=hidden na","ue=$access>");
// -->
</script>
EOM
		} else { print "<input type=hidden name=$bbscheckmode value=$access>\n"; }
	} else { print "<input type=hidden name=$bbscheckmode value=$access>\n"; }
	print <<EOM;
<!-- //
<input type=hidden name=mode value="write">
// -->
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
<form action="$script" method="$method">
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

#------------------#
#  JavaScript無効  #
#------------------#
sub noscript {
	&header;
	print <<"EOM";
<table width="100%"><tr><th bgcolor="#008080">
  <font color="#FFFFFF">JavaScriptを利用したメールアドレス表\示について</font>
</th></tr></table>
<P><div align="center">
スパム(一方的迷惑メール)およびウイルス対策のため、JavaScriptを利用したメールアドレス表\示を採用しています。<br>
お手数をおかけしますが、投稿者のメールアドレスを表\示させるためには、JavaScriptを有効にしてください。<br>
<br>
<form action="$script" target="_top">
<input type=hidden name=page value="$page">
<input type=submit value="掲示板へ戻る">
</form>
</div>
<br><hr>
</body>
</html>
EOM
	exit;
}


__END__

