#!/usr/local/bin/perl

#┌─────────────────────────────────
#│ YY-BOARD
#│ yyregi.cgi - 2005/11/20
#│ Copyright (c) KentWeb
#│ webmaster@kent-web.com
#│ http://www.kent-web.com/
#│
#│ YY-BOARD v5.5用携帯電話対応スクリプト
#│ 2005/1/4　湯一路　http://www.url-battle.com/cgi/
#│
#│ Antispam Version Modified by isso. August, 2006
#│ http://swanbay-web.hp.infoseek.co.jp/index.html
#└─────────────────────────────────

# 外部ファイル取込
require './jcode.pl';
require './yyini.cgi';

# メイン処理
&decode;
&axsCheck;
&previewcheck;

if ($mode eq "dele") { &dele; }
elsif ($mode eq "edit") { &edit; }
elsif ($mode eq "$writevalue" && $in{'pview'} ne "on") { &regist; }
elsif ($mode eq "$postvalue" && $in{'pview'} eq "on") { &regist; }
elsif ($mode eq "$writevalue" && $in{'pview'} eq "on") { &error("$spammsg"); }
elsif ($mode eq "previewmode") { &previewmode("$timecheck"); }
elsif ($mode eq "past") { &past; }
elsif ($mode eq "admin") { &admin; }
elsif ($mode eq "spam") { &spam; }
elsif ($mode eq "spammsg") { &spammsg; }
elsif ($mode eq "spamclear") { &spamclear; }
elsif ($mode eq "spamdata") { &spamdata; }
elsif ($mode eq "editspam") { &editspam; }
elsif ($mode eq "admin_repost_form") { &admin_repost_form; }
elsif ($in{'pass'} eq $pass && $mode eq "admin_repost") { &regist; }
&error("不明な処理です");

#-------------------------------------------------
#  記事登録
#-------------------------------------------------
sub regist {
	local($flag,$oyaChk,@lines,@data,@new,@tmp);
	local($cnam,$ceml,$curl,$cpwd,$cico,$ccol,$caikotoba) = &get_cookie;

	# 拡張オプションチェック
	if ($mode ne "admin_repost") {
		&option_check($in{'pwd'},$in{'email'},$in{'comment'},$in{'url'});
	}
	if ($in{'email'} && $in{'email'} =~ /＠/) { $in{'email'} =~ s/＠/\@/; }

	# 合い言葉をチェック
	if ($aikotoba) {
		if ($in{'aikotoba'} ne $aikotoba) { &error("合い言葉が不正です"); }
	}

	# フォーム入力チェック
	&formCheck;

	# 時間取得
	&get_time;

	# ファイルロック
	if ($lockkey) { &lock; }

	# ログを開く
	open(IN,"$logfile") || &error("Open Error: $logfile");
	@lines = <IN>;
	close(IN);

	# 記事NO処理
	$top = shift(@lines);
	local($no,$ip,$tim) = split(/<>/, $top);
	$no++;

	# 連続投稿チェック
	$flag=0;
	if ($regCtl == 1) {
		if ($addr eq $ip && $times - $tim < $wait) { $flag=1; }
	} elsif ($regCtl == 2) {
		if ($times - $tim < $wait) { $flag=1; }
	}
	if ($flag) {
		&error("現在投稿制限中です。もうしばらくたってから投稿をお願いします");
	}

	# 禁止ワードチェック
	if ($deny_word) {
		&deny_word($in{'name'});
		&deny_word($in{'comment'});
	}

	# 重複チェック
	$flag=0;
	foreach (@lines) {
		local($no2,$re,$dat,$nam,$eml,$sub,$com) = split(/<>/);
		if ($in{'name'} eq $nam && $in{'comment'} eq $com) {
			$flag=1; last;
		}
	}
	if ($flag) { &error("重複投稿のため処理を中断しました"); }

	# 暗証キーを暗号化
	if ($mode ne "admin_repost") {
		if ($in{'pwd'} ne "") { $pwd = &encrypt($in{'pwd'}); }
	}

	#携帯チェック
	$agent  = $ENV{HTTP_USER_AGENT};
	if ($imode == 5){
		($carrier,$ver,$host2,$sub ) = split( "/",$agent);
		$host2 =~ s/ .*$//;# J-SH51 SH（メーカー名削除）
		$host2 =~ s/_[a-z]$//;# J-DN03（末尾削除）
	}
	if ($imode == 2){
		$host2 = ( $agent =~ m#^[^\-]+\-([A-Z]\w+)#i )[0];
	}
	if ($imode == 1){
		($docomo, $ver, $host2, $sub ) = split( /[\/\s\(\)]+/, $agent );
	}

	# 改行・ダブルクオート復元
	if ($in{'pview'} eq "on") {
		$in{'comment'} =~ s/&lt;br&gt;/<br>/g;
		$in{'comment'} =~ s/&quot;/"/g;
	}

	# スパム投稿チェック
	($spam,$reason) = &spam_check($in{'name'},$in{'url2'},$in{"$bbscheckmode"},$in{'comment'},
	$in{'reno'},$in{'url'},$in{'email'},$in{'sub'},$in{'mail'},$in{"$formcheck"},$cnam,
	$in{'subject'},$in{'title'},$in{'theme'},$ENV{'HTTP_ACCEPT_LANGUAGE'},$ENV{'HTTP_USER_AGENT'});

	# プレビュー＆スパムログの削除
	if ($in{'pview'} eq "on" || $mode eq "admin_repost") {
		if ($spamlog) { &del_spamlog("$in{\"$bbscheckmode\"}"); }
	}

	# スパム投稿処理
	if ($spam && $mode ne "admin_repost") {
		# 投稿拒否ログの記録
		if ($spamlog) { &write_spamlog; }
		if ($spamresult) {
			# エラー表示
			if ($spamresult eq '1')  { &error("迷惑投稿のため処理を中断しました"); }
			else { sleep($spamresult); &error("迷惑投稿のため処理を中断しました"); }
		} elsif ($spammsg) { &message("$spammsg");
		} else { &access_error; }
	}

	# URL自動リンク
	if ($autolink) { &auto_link($in{'comment'}); }

	# 復活処理
	if ($mode eq "admin_repost") {
		$date= $in{'date'};
		$host= $in{'host'};
		$pwd = $in{'pwd'};
	}

	# 親記事の場合
	if ($in{'reno'} eq "") {

		$i=0;
		$stop=0;
		foreach (@lines) {
			($no2,$reno2) = split(/<>/);
			$i++;
			if ($i > $max-1 && $reno2 eq "") { $stop=1; }
			if (!$stop) { push(@new,$_); }
			elsif ($stop && $pastkey) { push(@data,$_); }
		}
		unshift(@new,"$no<><>$date<>$in{'name'}<>$in{'email'}<>$in{'sub'}<>$in{'comment'}<>$in{'url'}<>$host<>$pwd<>$col[$in{'color'}]<>$in{'icon'}<>\n");
		unshift(@new,"$no<>$addr<>$times<>\n");

		# 過去ログ更新
		if (@data > 0) { &pastlog(@data); }

		# 更新(一時ファイルを作成)
		if (!$logbackup) { $tempfile = $logfile; }
		open(OUT,">$tempfile") || &error("Write Error : ログファイルに書き込みができません。<BR>掲示板設置ディレクトリのパーミッション設定を変更してください。");
		chmod (0606,$tempfile);
		print OUT @new;
		close(OUT);
		# 一時ファイル正常更新時にログファイルにリネーム
		if ( $logbackup && (-s $tempfile) > 100 ) { rename ($tempfile,$logfile) || &error("Rename Error"); }

	# レス記事の場合：トップソートあり
	} elsif ($in{'reno'} && $topsort) {

		$f=0;
		$oyaChk=0;
		$match=0;
		@new=();
		@tmp=();
		foreach (@lines) {
			($no2,$reno2) = split(/<>/);

			if ($in{'reno'} == $no2) {
				if ($reno2) { $f++; last; }
				$oyaChk++;
				$match=1;
				push(@new,$_);

			} elsif ($in{'reno'} == $reno2) {
				push(@new,$_);

			} elsif ($match == 1 && $in{'reno'} != $reno2) {
				$match=2;
				push(@new,"$no<>$in{'reno'}<>$date<>$in{'name'}<>$in{'email'}<>$in{'sub'}<>$in{'comment'}<>$in{'url'}<>$host<>$pwd<>$col[$in{'color'}]<>$in{'icon'}<>\n");
				push(@tmp,$_);

			} else { push(@tmp,$_); }
		}
		if ($f) { &error("不正な返信要求です"); }
		if (!$oyaChk) { &error("親記事が存在しません"); }

		if ($match == 1) {
			push(@new,"$no<>$in{'reno'}<>$date<>$in{'name'}<>$in{'email'}<>$in{'sub'}<>$in{'comment'}<>$in{'url'}<>$host<>$pwd<>$col[$in{'color'}]<>$in{'icon'}<>\n");
		}
		push(@new,@tmp);

		# 更新(一時ファイルを作成)
		unshift(@new,"$no<>$addr<>$times<>\n");
		if (!$logbackup) { $tempfile = $logfile; }
		open(OUT,">$tempfile") || &error("Write Error : ログファイルに書き込みができません。<BR>掲示板設置ディレクトリのパーミッション設定を変更してください。");
		chmod (0606,$tempfile);
		print OUT @new;
		close(OUT);
		# 一時ファイル正常更新時にログファイルにリネーム
		if ( $logbackup && (-s $tempfile) > 100 ) { rename ($tempfile,$logfile) || &error("Rename Error"); }

	# レス記事の場合：トップソートなし
	} else {

		$f=0;
		$oyaChk=0;
		$match=0;
		@new=();
		foreach (@lines) {
			($no2,$reno2) = split(/<>/);

			if ($in{'reno'} == $no2) { $oyaChk++; }
			if ($match == 0 && $in{'reno'} == $no2) {
				if ($reno2) { $f++; last; }
				$match=1;

			} elsif ($match == 1 && $in{'reno'} != $reno2) {
				$match=2;
				push(@new,"$no<>$in{'reno'}<>$date<>$in{'name'}<>$in{'email'}<>$in{'sub'}<>$in{'comment'}<>$in{'url'}<>$host<>$pwd<>$col[$in{'color'}]<>$in{'icon'}<>\n");
			}
			push(@new,$_);
		}
		if ($f) { &error("不正な返信要求です"); }
		if (!$oyaChk) { &error("親記事が存在しません"); }

		if ($match == 1) {
			push(@new,"$no<>$in{'reno'}<>$date<>$in{'name'}<>$in{'email'}<>$in{'sub'}<>$in{'comment'}<>$in{'url'}<>$host<>$pwd<>$col[$in{'color'}]<>$in{'icon'}<>\n");
		}

		# 更新(一時ファイルを作成)
		unshift(@new,"$no<>$addr<>$times<>\n");
		open(OUT,">$tempfile") || &error("Write Error : ログファイルに書き込みができません。<BR>掲示板設置ディレクトリのパーミッション設定を変更してください。");
		chmod (0606,$tempfile);
		print OUT @new;
		close(OUT);
		# 一時ファイル正常更新時にログファイルにリネーム
		if ( (-s $tempfile) > 20 ) { rename ($tempfile,$logfile) || &error("Rename Error"); }
	}

	# ロック解除
	if ($lockkey) { &unlock; }

	# クッキー発行
	if ($mode ne "admin_repost") {
		if ($no_email == 2 && !$imode) { $in{'email'} =~ s/\@/＠/; }
		&set_cookie($in{'name'},$in{'email'},$in{'url'},$in{'pwd'},$in{'icon'},$in{'color'},$in{'aikotoba'});
		if ($no_email == 2 && !$imode) { $in{'email'} =~ s/＠/\@/; }
	}

	# メール処理
	if ($mailing == 1 && $in{'email'} ne $mailto) { &mail_to; }
	elsif ($mailing == 2) { &mail_to; }

	# リロード
	if ($location) {
		if ($ENV{'PERLXS'} eq "PerlIS") {
			print "HTTP/1.0 302 Temporary Redirection\r\n";
			print "Content-type: text/html\n";
		}
		print "Location: $location?\n\n";
		exit;

	} else {
		&message('投稿は正常に処理されました');
	}
}

#-------------------------------------------------
#  記事削除
#-------------------------------------------------
sub dele {
	local($flag,$check,$no,$reno,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,@new);

	# POST限定
	if ($postonly && !$post_flag) { &error("不正なアクセスです"); }

	if ($in{'no'} eq '' || $in{'pwd'} eq '')
		{ &error("記事Noまたは暗証キーが入力モレです"); }

	# ロック処理
	&lock if ($lockkey);

	$flag=0;
	@new=();
	open(IN,"$logfile") || &error("Open Error: $logfile");
	$top = <IN>;
	while (<IN>) {
		($no,$reno,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw) = split(/<>/);

		if ($in{'no'} == $no) {
			$flag++;
			$pw2 = $pw;
			next;
		} elsif ($in{'no'} == $reno) {
			next;
		}
		push(@new,$_);
	}
	close(IN);
	if (!$flag) { &error("該当の記事が見当たりません"); }
	if ($pw2 eq "") { &error("暗証キーが設定されていません"); }

	$check = &decrypt($in{'pwd'}, $pw2);
	if ($check != 1) { &error("暗証キーが違います"); }

	unshift(@new,$top);
	open(OUT,">$logfile") || &error("Write Error: $logfile");
	print OUT @new;
	close(OUT);

	# ロック解除
	&unlock if ($lockkey);

	# 完了メッセージ
	&message("削除が完了しました");
}

#-------------------------------------------------
#  記事修正
#-------------------------------------------------
sub edit {
	local($top,$flag,$pattern,$no,$reno,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico);

	if ($in{'no'} eq '' || $in{'pwd'} eq '')
		{ &error("記事Noまたは暗証キーが入力モレです"); }

	# 修正実行
	if ($in{'job'} eq "edit") {

		# フォーム入力チェック
		&formCheck('edit');

		# 禁止ワードチェック
		if ($deny_word) {
			&deny_word($in{'name'});
			&deny_word($in{'comment'});
		}

		if ($autolink) { &auto_link($in{'comment'}); }

		# ロック処理
		&lock if ($lockkey);

		$flag=0;
		open(IN,"$logfile") || &error("Open Error: $logfile");
		$top = <IN>;
		while (<IN>) {
			($no,$reno,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);
			if ($in{'no'} == $no) {
				$flag++;
				$pw2 = $pw;
				$_ = "$no<>$reno<>$dat<>$in{'name'}<>$in{'email'}<>$in{'sub'}<>$in{'comment'}<>$in{'url'}<>$hos<>$pw<>$col[$in{'color'}]<>$in{'icon'}<>\n";
			}
			push(@new,$_);
		}
		close(IN);
		if (!$flag) { &error("該当の記事が見当たりません"); }
		if ($pw2 eq "") { &error("暗証キーが設定されていません"); }

		$check = &decrypt($in{'pwd'}, $pw2);
		if ($check != 1) { &error("暗証キーが違います"); }

		unshift(@new,$top);
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);

		# ロック解除
		&unlock if ($lockkey);

		# 完了メッセージ
		&message("修正が完了しました");
	}

	$flag=0;
	open(IN,"$logfile") || &error("Open Error: $logfile");
	$top = <IN>;
	while (<IN>) {
		($no,$reno,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);
		if ($in{'no'} == $no) {
			$pw2 = $pw;
			$flag=1;
			last;
		}
	}
	close(IN);
	if (!$flag) { &error("該当の記事が見当たりません"); }
	if ($pw2 eq "") { &error("暗証キーが設定されていません"); }

	$check = &decrypt($in{'pwd'}, $pw2);
	if ($check != 1) { &error("暗証キーが違います"); }

	$com =~ s/<br>/\n/g;
	$pattern = 'https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+';
	$com =~ s/<a href="$pattern" target="_blank">($pattern)<\/a>/$1/go;

	if ($ImageView == 1) { &header('ImageUp'); }
	else { &header; }

	print <<EOM;
<form>
<input type=button value="前画面に戻る" onClick="history.back()">
</form>
▽変更する部分のみ修正して送信ボタンを押して下さい。
<p>
<form action="$regist" method="$method">
<input type=hidden name=mode value="edit">
<input type=hidden name=job value="edit">
<input type=hidden name=pwd value="$in{'pwd'}">
<input type=hidden name=no value="$in{'no'}">
EOM

	&form($nam,$eml,$url,'??',$ico,$col,$sub,$com);

	print <<EOM;
</form>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  完了文言
#-------------------------------------------------
sub message {
	local($msg) = @_;

	&header;

	if (!$imode){

	print <<EOM;
<div align="center">
<hr width=400>
<h3>$msg</h3>
<hr width=400>
<p>
<form action="$script">
<input type=submit value="掲示板に戻る">
</form>
</div>
</body>
</html>
EOM
	}else{
		print "$msg<br>\n";
		print "<a href=$script>掲示板へ戻る</a>\n";
	}

	exit;
}

#-------------------------------------------------
#  入力確認
#-------------------------------------------------
sub formCheck {
	local($task) = @_;
	local($ref);

	# POST限定
	if ($postonly && !$post_flag) { &error("不正なアクセスです"); }

	# 他サイトからのアクセス排除
	if ($task ne 'edit' && $baseUrl) {
		$ref = $ENV{'HTTP_REFERER'};
		$ref =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("H2", $1)/eg;
		$baseUrl =~ s/(\W)/\\$1/g;
		if ($ref && $ref !~ /$baseUrl/i) { &error("不正なアクセスです"); }
	}

	# 名前とコメントは必須
	if ($in{'name'} eq "") { &error("名前が入力されていません"); }
	if ($in{'comment'} eq "") { &error("コメントが入力されていません"); }
	if ($in_email && $in{'email'} !~ /^[\w\.\-]+\@[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
		&error("Ｅメールの入力内容が正しくありません");
	}
	if (!$in_email && $in{'email'}) {
		if ($in{'email'} !~ /https?\:\/\//i) {
			if ($in{'email'} !~ /^[\w\.\-]+\@[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
				&error("Ｅメールの入力内容が正しくありません");
			}
		}
	}

	if ($iconMode) {
		if (!$imode){
			@ico1 = split(/\s+/, $ico1);
			@ico2 = split(/\s+/, $ico2);
			if ($my_icon) { push(@ico1,$my_gif); }
			if ($in{'icon'} =~ /\D/ || $in{'icon'} < 0 || $in{'icon'} > @ico1) {
				&error("アイコン情報が不正です");
			}
			$in{'icon'} = $ico1[$in{'icon'}];
		}
		# 管理アイコンチェック
		if ($my_icon && $in{'icon'} eq $my_gif && $in{'pwd'} ne $pass) {
			&error("管理用アイコンは管理者専用です");
		}
	}

	@col = split(/\s+/, $color);
	if ($in{'color'} =~ /\D/ || $in{'color'} < 0 || $in{'color'} > @col) {
		&error("文字色情報が不正です");
	}

	# URL
	if ($in{'url'} eq "http://") { $in{'url'} = ""; }

	# タイトルチェック
	if (!$in{'sub'}) {
		if ($suberror) { &error("タイトルが入力されていません"); } else { $in{'sub'} = "無題"; } 
	} elsif ($suberror == 2) {
		if ($in{'sub'} !~ /[^0-9]/ || $in{'sub'} =~ /http\:\/\//i) { &error("タイトルが不正です"); }
	}
}

#-------------------------------------------------
#  管理モード
#-------------------------------------------------
sub admin {
	local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$next,$back,$top,$i);

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("パスワードが違います"); }

	# 修正画面
	if ($in{'job'} eq "edit" && $in{'no'}) {

		local(@num,@f);
		@num = split(/\0/, $in{'no'});

		open(IN,"$logfile") || &error("Open Error: $logfile");
		$top = <IN>;
		while (<IN>) {
			@f = split(/<>/);

			if ($f[0] == $num[0]) { last; }
		}
		close(IN);

		# 修正フォーム
		&edit_form(@f);

	# 修正実行
	} elsif ($in{'job'} eq "edit2" && $in{'no'}) {

		local(@col,@ico,@new);
		if ($in{'url'} eq "http://") { $in{'url'} = ''; }

		@col = split(/\s+/, $color);
		@ico1 = split(/\s+/, $ico1);
		if ($my_icon) { push(@ico1,$my_gif); }
		$in{'icon'} = $ico1[$in{'icon'}];

		# URL自動リンク
		if ($autolink) { &auto_link($in{'comment'}); }

		# ロック開始
		if ($lockkey) { &lock; }

		open(IN,"$logfile") || &error("Open Error: $logfile");
		$top = <IN>;
		while (<IN>) {
			($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);
			if ($no == $in{'no'}) {
				$_="$no<>$re<>$dat<>$in{'name'}<>$in{'email'}<>$in{'sub'}<>$in{'comment'}<>$in{'url'}<>$hos<>$pw<>$col[$in{'color'}]<>$in{'icon'}<>\n";
			}
			push(@new,$_);
		}
		close(IN);

		# 更新
		unshift(@new,$top);
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);

		# ロック解除
		if ($lockkey) { &unlock; }

	# 削除
	} elsif ($in{'job'} eq "dele" && $in{'no'}) {

		# ロック開始
		if ($lockkey) { &lock; }

		# 削除情報をマッチング
		local(@new)=();
		open(IN,"$logfile") || &error("Open Error: $logfile");
		$top = <IN>;
		while (<IN>) {
			$flag=0;
			($no,$re) = split(/<>/);
			foreach $del ( split(/\0/, $in{'no'}) ) {
				if ($no == $del || $re == $del) {
					$flag=1; last;
				}
			}
			if ($flag == 0) { push(@new,$_); }
		}
		close(IN);

		# 更新
		unshift(@new,$top);
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);

		# ロック解除
		if ($lockkey) { &unlock; }
	}

	&header;
	print <<EOM;
<form action="$script">
<input type=submit value="掲示板に戻る">
</form>
</UL>
EOM

	if (-e $spamdata && !$imode) {
	print <<EOM;
<hr>
<UL>
<li>NGワードの一括編集
<form action="$regist" method="$method">
<input type=hidden name=mode value="spamdata">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value='NGワードの一括編集'><br>
</form>
</ul>
EOM
	}

	if(-e $spamlogfile && !$imode) {
	print <<EOM;
<UL>
<LI>投稿拒否された迷惑投稿を閲覧できます。
<form action="$regist" method="$method">
<input type=hidden name=mode value="spam">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value="迷惑投稿を閲覧">
</form>
</UL>
EOM
	}

	if (!$imode){
		print <<EOM;
<hr>
<UL>
<LI>処理を選択し、記事をチェックして送信ボタンを押して下さい。
<LI>親記事を削除するとレス記事も一括して削除されます。
</UL>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=page value="$page">
<input type=hidden name=pass value="$in{'pass'}">
<select name=job>
<option value="edit">修正
<option value="dele">削除
</select>
<input type=submit value="送信する">
<dl>
EOM

	$pastView *= 2;

	$i=0;
	open(IN,"$logfile") || &error("Open Error: $logfile");
	$top = <IN>;
	while (<IN>) {
		($no,$res,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw) = split(/<>/);

		if ($res eq "") { $i++; }
		if ($i < $page + 1) { next; }
		if ($i > $page + $pastView) { last; }

		if ($eml) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }
		($dat) = split(/\(/, $dat);

		$com =~ s/<[^>]*(>|$)//g;
		if (length($com) > 40) {
			$com = substr($com,0,40) . "...";
		}

		# 削除チェックボックス
		if (!$res) { print "<dt><hr>"; } else { print "<dd>"; }
		print "<input type=checkbox name=no value=\"$no\">";
		print "[<b>$no</b>] <b style='color:$subCol'>$sub</b>\n";
		print "投稿者：$nam 投稿日：$dat 【$hos】\n";
		print "<dd style='font-size:11px'>$com</dd>\n";
	}
	close(IN);

	print <<EOM;
<dt><hr>
</dl>
</form>
EOM
	}else{
		#携帯の処理
	print <<EOM;
削除したい記事をﾁｪｯｸして送信ﾎﾞﾀﾝを押してください<br>
親記事を削除するとｽﾚｯﾄﾞ全てが削除されます<br>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=page value="$page">
<input type=hidden name=pass value="$in{'pass'}">
<input type=hidden name=job value="dele">
EOM

#	$pastView *= 2;

	$i=0;
	open(IN,"$logfile") || &error("Open Error: $logfile");
	$top = <IN>;
	while (<IN>) {
		($no,$res,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw) = split(/<>/);

		if ($res eq "") { $i++; }
		if ($i < $page + 1) { next; }
		if ($i > $page + $pastView) { last; }

		($dat) = split(/\(/, $dat);

		$com =~ s/<[^>]*(>|$)//g;
		if (length($sub) > 10) {
			$sub = substr($sub,0,10) . "...";
		}
		if (length($com) > 10) {
			$com = substr($com,0,10) . "...";
		}

		# 削除チェックボックス
		if (!$res) { print "<hr>"; }
		print "<input type=checkbox name=no value=\"$no\">";
		print "[$no] $sub\n";
		print "/$nam\n";
		print "/$com<br>\n";
	}
	close(IN);
	print <<EOM;
<input type=submit value="送信する">
</form>
EOM
	}

	$next = $page + $pastView;
	$back = $page - $pastView;

	if (!$imode){
		print "<p><table cellspacing=0 cellpadding=0><tr><td></td>\n";
		if ($back >= 0) {
			print "<td><form action=\"$regist\" method=\"$method\">\n";
			print "<input type=hidden name=page value=\"$back\">\n";
			print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
			print "<input type=hidden name=mode value=\"admin\">\n";
			print "<input type=submit value=\"前の$pastView組\"></td></form>\n";
		}
		if ($next < $i) {
			print "<td><form action=\"$regist\" method=\"$method\">\n";
			print "<input type=hidden name=page value=\"$next\">\n";
			print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
			print "<input type=hidden name=mode value=\"admin\">\n";
			print "<input type=submit value=\"次の$pastView組\"></td></form>\n";
		}
	}else{
		#携帯の処理
		if ($back >= 0) {
			print "<form action=\"$regist\" method=\"$method\">\n";
			print "<input type=hidden name=page value=\"$back\">\n";
			print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
			print "<input type=hidden name=mode value=\"admin\">\n";
			print "<input type=submit value=\"前の$pastView組\"></form>\n";
		}
		if ($next < $i) {
			print "<form action=\"$regist\" method=\"$method\">\n";
			print "<input type=hidden name=page value=\"$next\">\n";
			print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
			print "<input type=hidden name=mode value=\"admin\">\n";
			print "<input type=submit value=\"次の$pastView組\"></form>\n";
		}
	}
	print <<EOM;
</tr></table>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  修正画面
#-------------------------------------------------
sub edit_form {
	local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = @_;

	$com =~ s/<br>/\n/g;

	if ($ImageView == 1) { &header('ImageUp'); }
	else { &header; }
	print <<EOM;
<form>
<input type=button value="前画面に戻る" onClick="history.back()">
</form>
▽変更する部分のみ修正して送信ボタンを押して下さい。
<p>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=job value="edit2">
<input type=hidden name=pass value="$in{'pass'}">
<input type=hidden name=no value="$no">
EOM

	&form($nam,$eml,$url,'??',$ico,$col,$sub,$com);

	print <<EOM;
</form>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  入室画面
#-------------------------------------------------
sub enter {
	&header;
	print <<EOM;
<div align="center">
<h4>パスワードを入力してください</h4>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=password name=pass size=8 class=f>
<input type=submit value=" 認証 ">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  時間取得
#-------------------------------------------------
sub get_time {
	$times = shift;
	if (!$times) { $ENV{'TZ'} = "JST-9"; $times = time; }

	($sec,$min,$hour,$mday,$mon,$year,$wday) = localtime($times);
	local(@week) = ('Sun','Mon','Tue','Wed','Thu','Fri','Sat');

	# 日時のフォーマット
	$date = sprintf("%04d/%02d/%02d(%s) %02d:%02d",
			$year+1900,$mon+1,$mday,$week[$wday],$hour,$min);
}

#-------------------------------------------------
#  メール送信
#-------------------------------------------------
sub mail_to {
	local($msub,$mbody,$email,$ptn);

	# 記事の改行・タグを復元
	$com  = $in{'comment'};
	$com =~ s/<br>/\n/g;
	$ptn = 'https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+';
	$com =~ s/<a href="$ptn" target="_blank">($ptn)<\/a>/$1/go;
	$com =~ s/&lt;/</g;
	$com =~ s/&gt;/>/g;
	$com =~ s/&quot;/"/g;
	$com =~ s/&amp;/&/g;

	# メール本文を定義
	$mbody = <<EOM;
投稿日時：$date
ホスト名：$host
ブラウザ：$ENV{'HTTP_USER_AGENT'}

投稿者名：$in{'name'}
Ｅメール：$in{'email'}
参照先  ：$in{'url'}
タイトル：$in{'sub'}

$com
EOM

	# 題名をBASE64化
	$msub = &base64("$title (No.$no)");

	# メールアドレスがない場合は管理者アドレスに置き換え
	if ($in{'email'} eq "") { $email = $mailto; }
	else { $email = $in{'email'}; }

	open(MAIL,"| $sendmail -t") || &error("メール送信失敗");
	print MAIL "To: $mailto\n";
	print MAIL "From: $email\n";
	print MAIL "Subject: $msub\n";
	print MAIL "MIME-Version: 1.0\n";
	print MAIL "Content-type: text/plain; charset=ISO-2022-JP\n";
	print MAIL "Content-Transfer-Encoding: 7bit\n";
	print MAIL "X-Mailer: $ver\n\n";
	print MAIL "--------------------------------------------------------\n";
	foreach ( split(/\n/, $mbody) ) {
		&jcode'convert(*_, 'jis', 'sjis');
		print MAIL $_, "\n";
	}
	print MAIL "--------------------------------------------------------\n";
	close(MAIL);
}

#-------------------------------------------------
#  BASE64変換
#-------------------------------------------------
#		とほほのWWW入門で公開されているルーチンを
#		参考にしました。( http://tohoho.wakusei.ne.jp/ )
sub base64 {
	local($sub) = $_[0];
	&jcode'convert(*sub, 'jis', 'sjis');

	$sub =~ s/\x1b\x28\x42/\x1b\x28\x4a/g;
	$sub = "=?iso-2022-jp?B?" . &b64enc($sub) . "?=";
	$sub;
}
sub b64enc {
	local($ch)="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
	local($x, $y, $z, $i);
	$x = unpack("B*", $_[0]);
	for ($i=0; $y=substr($x,$i,6); $i+=6) {
		$z .= substr($ch, ord(pack("B*", "00" . $y)), 1);
		if (length($y) == 2) {
			$z .= "==";
		} elsif (length($y) == 4) {
			$z .= "=";
		}
	}
	$z;
}

#-------------------------------------------------
#  crypt暗号
#-------------------------------------------------
sub encrypt {
	local($inpw) = @_;
	local(@char, $salt, $encrypt);

	@char = ('a'..'z', 'A'..'Z', '0'..'9', '.', '/');
	srand;
	$salt = $char[int(rand(@char))] . $char[int(rand(@char))];
	$encrypt = crypt($inpw, $salt) || crypt ($inpw, '$1$' . $salt);
	$encrypt;
}

#-------------------------------------------------
#  crypt照合
#-------------------------------------------------
sub decrypt {
	local($in, $dec) = @_;

	local($salt) = $dec =~ /^\$1\$(.*)\$/ && $1 || substr($dec, 0, 2);
	if (crypt($in, $salt) eq $dec || crypt($in, '$1$' . $salt) eq $dec) {
		return (1);
	} else {
		return (0);
	}
}

#-------------------------------------------------
#  自動URLリンク
#-------------------------------------------------
sub auto_link {
	if ($comment_url) {
		$_[0] =~ s/http/ttp/g;
		$_[0] =~ s/([^=^\"]|^)(ttps?\:[\w\.\~\-\/\?\&\+\=\@\;\#\:\%\,]+)/$1<a href=\"h$2\" target=\"_blank\">h$2<\/a>/g;
	} else {
		$_[0] =~ s/([^=^\"]|^)(https?\:[\w\.\~\-\/\?\&\+\=\@\;\#\:\%\,]+)/$1<a href=\"$2\" target=\"_blank\">$2<\/a>/g;
	}
}

#-------------------------------------------------
#  過去ログ生成
#-------------------------------------------------
sub pastlog {
	local(@data) = @_;
	local($count,$pastfile,$i,$f,@past);

	# 過去ログNoファイル
	open(NO,"$nofile") || &error("Open Error: $nofile");
	$count = <NO>;
	close(NO);
	$pastfile = sprintf("%s%04d\.cgi", $pastdir,$count);

	# 過去ログを開く
	$i=0; $f=0;
	open(IN,"$pastfile") || &error("Open Error: $pastfile");
	while (<IN>) {
		$i++;
		push(@past,$_);
		if ($i >= $pastmax) { $f++; last; }
	}
	close(IN);

	# 規定の行数をオーバーすると次ファイルを自動生成
	if ($f) {
		# カウントファイル更新
		open(NO,">$nofile") || &error("Write Error: $nofile");
		print NO ++$count;
		close(NO);

		$pastfile = sprintf("%s%04d\.cgi", $pastdir,$count);
		@past = @data;
	} else {
		unshift(@past,@data);
	}

	# 過去ログ更新
	open(OUT,">$pastfile") || &error("Write Error: $pastfile");
	print OUT @past;
	close(OUT);

	if ($f) { chmod(0666, $pastfile); }
}

#-------------------------------------------------
#  過去ログ
#-------------------------------------------------
sub past {
	open(IN,"$nofile") || &error("Open Error: $nofile");
	$no = <IN>;
	close(IN);

	$in{'pastlog'} =~ s/\D//g;
	if (!$in{'pastlog'}) { $in{'pastlog'} = $no; }

	&header;
	print <<"EOM";
<form action="$script">
<input type=submit value="掲示板に戻る"></form>
<form action="$regist" method="$method">
<input type=hidden name=mode value=past>
<table border=0>
<tr><td><b>過去ログ</b> <select name=pastlog class=f>
EOM

	# 過去ログ選択
	for ($i=$no; $i>0; --$i) {
		$i = sprintf("%04d", $i);
		next unless (-e "$pastdir$i\.cgi");
		if ($in{'pastlog'} == $i) {
			print "<option value=\"$i\" selected>$i\n";
		} else {
			print "<option value=\"$i\">$i\n";
		}
	}

	print <<EOM;
</select>
<input type=submit value="移動"></td></form>
<td width=15></td><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="past">
<input type=hidden name=pastlog value="$in{'pastlog'}">
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

	print "</select> 表\示 <select name=view class=f>\n";

	if (!$in{'view'}) { $in{'view'} = 10; }
	foreach (10,15,20,25) {
		if ($in{'view'} == $_) {
			print "<option value=\"$_\" selected>$_件\n";
		} else {
			print "<option value=\"$_\">$_件\n";
		}
	}

	print <<EOM;
</select>
<input type=submit value="検索"></td>
</form>
</tr></table>
EOM

	$file = sprintf("%s%04d\.cgi", $pastdir,$in{'pastlog'});

	# 検索処理
	if ($in{'word'} ne "") {

		($i,$next,$back) = &search($file,$in{'word'},$in{'view'},$in{'cond'});

		$enwd = &url_enc($in{'word'});
		if ($back >= 0) {
			print "[<a href=\"$regist?mode=past&pastlog=$in{'pastlog'}&page=$back&word=$enwd&view=$in{'view'}&cond=$in{'cond'}\">前の$in{'view'}件</a>]\n";
		}
		if ($next < $i) {
			print "[<a href=\"$regist?mode=past&pastlog=$in{'pastlog'}&page=$next&word=$enwd&view=$in{'view'}&cond=$in{'cond'}\">次の$in{'view'}件</a>]\n";
		}
		print "</body></html>\n";
		exit;
	}

	print "<dl>\n";
	$i=0;
	open(IN,"$file") || &error("Open Error: $file");
	while (<IN>) {
		($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);

		if ($re eq "") { $i++; }
		if ($i < $page + 1) { next; }
		if ($i > $page + $pastView) { next; }

#		if ($eml) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }

		if (!$imode && $eml) { ($em0,$em1) = split(/\@/,$eml);
			$em1 =~ s/\./&#46;/g;
			$nam = "<script type=\"text/javascript\">\n<!-- //\n".
			"address(\"$em0\",\"$nam\",\"$em1\");\n// -->\n</script>\n".
			"<noscript><a href=\"$script?mode=noscript&page=$page\">$nam</a></noscript>\n";
		}

		if ($url) { $url = "&lt;<a href=\"$url\" target=\"_blank\">URL</a>&gt;"; }

		if ($re eq "") { print "<dt><hr>"; } else { print "<dd>"; }

		print "<b style='color:$subCol'>$sub</b>&nbsp;
		<b>$nam</b> - $dat $url <span style='color:$subCol'>No.$no</span><br><br>\n";

		if ($ico) {
			print "<table><tr><td><img src=\"$imgurl$ico\"></td>
			<td width=10></td><td style='color:$col'>$com</td>
			</tr></table><br>\n";
		} else {
			print "<dd style='color:$col'>$com</dd>\n";
		}
	}
	close(IN);

	print <<EOM;
<dt><hr>
</dl>
EOM

	# ページ移動ボタン表示
	if ($page - $pastView >= 0 || $page + $pastView < $i) {
		&mvbtn("$regist?mode=past&pastlog=$in{'pastlog'}&page=", $i, $pastView);
	}

	print <<EOM;
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  禁止ワード
#-------------------------------------------------
sub deny_word {
	local($word) = @_;

	local($flg);
	foreach ( split(/,+/, $deny_word) ) {
		if (index($word,$_) >= 0) { $flg=1; last; }
	}
	if ($flg) { &error("不適切な投稿のため受理できません"); }
}

#-------------------------------------------------
#  スパム拡張オプションチェック
#-------------------------------------------------
sub option_check {
	local ($pw,$em,$cm,$ur) = @_;

	# 暗証キーをチェック
	local($pwdflag) = 0;
	if ($ng_pass && $pw) {
		if ($pw =~ /\s/) { $pwdflag = 1; }
		if ($pw eq reverse($pw)) { $pwdflag = 1; }
	}
	if ($pwdflag) { &error("暗証キーが不正です。"); }

	# メールアドレスをチェック
	if ($no_email == 1 && $em) { &error("メールアドレスは入力禁止です。"); }
	if ($no_email == 2 && $em && $em!~ /^[\w\.\-]+＠[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
		&error("アットマーク ＠ は全角で入力して下さい。"); }

	# URLの直接書き込みをチェック
	if ($comment_url) { 
		$urlnum = ($cm =~ s/http/http/ig);
		if ($urlnum) { &error("ＵＲＬは先頭のｈを抜いて書き込んで下さい。"); }
	}

	# URL転送・短縮URLをチェック
	$shorturlcheck = 0;
	if ($shorturl) { 
		if ($cm =~ /https?\:\/\/[\w\-]{1,10}?\.[\w\-]{2,5}?\//i || 
			$ur =~ /https?\:\/\/[\w\-]{1,10}?\.[\w\-]{2,5}?\//i) {
			local($html) = $';
			if ($html =~ /^[\w\?]+?/)  {
				if ($html !~ /^index\.htm/i) { $shorturlcheck = 1; }
			}
		}
		if (!$shorturlcheck) {
			if ($cm =~ /https?\:\/\/([\w\-]{1,5}\.)?(\d+)\.[a-z]{2,4}\/?/i || 
				$ur =~ /https?\:\/\/([\w\-]{1,5}\.)?(\d+)\.[a-z]{2,4}\/?/i)
				{ $shorturlcheck = 2; }
		}
		if ($shorturlcheck) { &error("URLの記載は禁止されています。"); }
	}
}

#-------------------------------------------------
#  スパムチェック
#-------------------------------------------------
sub spam_check{
	local ($na,$u2,$bt,$cm,$re,$ur,$em,$sb,$ad,$fc,$cn,$sb2,$sb3,$sb4,$lng,$ua) = @_;
	$spam = 0;

	if ($u2 || $sb2 || $sb3 || $sb4) {
		$spam=1; $reason = "プログラム投稿(非ブラウザ)"; }

	if (!$spam) {
		if (!$bt || !$fc || !$ad) {
			$spam=1; $reason = "プログラム投稿(非フォーム投稿)"; }
	}

	if(!$spam) {
		if($ipcheckmode) {
			local($enadr) = &encode_addr($addr);
			if ($ad ne $enadr) { $spam=1; $reason = "プログラム投稿(IP不一致)"; }
		} else {
			if ($ad =~ /\@/) { $spam=1; $reason = "プログラム投稿(IPデータ不正)"; }
		}
	}

	if (!$spam) {
		local($posttime2) = time;
		local($timecheck2) = $posttime2 - $bt;
		if ($timecheck2 < 0) { $timecheck2 = 0 - $timecheck2; }
		if ($mintime && $timecheck2 < $mintime) {
			$spam=1; $reason = "プログラム投稿(投稿まで$timecheck2秒)"; }
		if (!$cn || !$cookiecheck) {
			if ($maxtime && $timecheck2 > $maxtime) {
				$spam=1; $reason = "プログラム投稿(投稿まで$timecheck2秒)"; }
		}
	}

	# 日本語環境チェック
	if (!$spam) {
		if ($japanese) {
			if ($lng !~ /ja/i && $ua !~ /ja/i) {
				$spam=1; $reason = "不正ブラウザ(非日本語環境)"; }
		}
	}

	# 携帯からの投稿を除外
	if (!$keitaicheck && $imode) { $spam = 0; }

	if(!$spam) {
		if ($em && $em =~ /https?\:\/\//) {
			$spam=1; $reason = "プログラム投稿(email/URL不正)"; }
	}

	if(!$spam) {
		if ($ur && $ur =~ /^[\w\.\-]+\@[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
			$spam=1; $reason = "プログラム投稿(email/URL不正)"; }
	}

	if(!$spam) {
		if (length($cm) < length($na)) {
			&error("コメント・メッセージが短すぎます。"); }
	}

	if(!$spam) {
		if ($na =~ /https?\:\/\//i) {
			$spam=1; $reason = "プログラム投稿(name/comment不正)"; }
	}

	# スパム投稿チェック(多数URL記述対応)
	if (!$spam) {
		$urlnum = ($cm =~ s/http/http/ig);
		if ($spamurlnum && ($urlnum >= $spamurlnum)) { $spam=1; $reason = "URLの書き込みが$urlnum個"; }
	}

	# URL以外の文字数をチェック
	if(!$spam) {
		if ($characheck) {
			if ($cm =~ /(https?\:\/\/[\w\.\~\-\/\?\&\=\;\#\:\%\+\@\,]+)/ || $ur) {
				local($charamsg) = $cm;
				$charamsg =~ s/(https?\:\/\/[\w\.\~\-\/\?\&\=\;\#\:\%\+\@\,]+)//g;
				$charamsg =~ s/[\s\n\r\t]//g;
				$charamsg =~ s/<br>//ig;
				$msgnum = length($charamsg);
				if ($msgnum < $characheck) {
					 $spam=1; $reason = "コメントの文字数が$msgnumバイトと少ない";
				}
			}
		}
	}

	# 全角文字(日本語)チェック
	if(!$spam) {
		if ($asciicheck) {
			if ($cm !~ /(\x82[\x9F-\xF2])|(\x83[\x40-\x96])/) {
				$spam=1; $reason = "コメントに日本語(ひらがな/カタカナ)がない";
			}
		}
	}

	if(!$spam) {
		if (-e $spamdata) {
			if ($spamdatacheck || !$re) {
				# 禁止URLデータをロード
				open(SPAM,"$spamdata") || &error("Open Error : $spamdata");
				$SPM = <SPAM>;
				close(SPAM);
				# 禁止URLの書き込みをチェック
				foreach (split(/\,/, $SPM)) {
					if(length($_) > 1) {
#fs0x7f-costom
						($cm_ = $cm) =~ s/(\s|　|\r|\n)//g;
						($na_ = $na) =~ s/(\s|　|\r|\n)//g;
						($ur_ = $ur) =~ s/(\s|　|\r|\n)//g;
						($em_ = $em) =~ s/(\s|　|\r|\n)//g;
						($sb_ = $sb) =~ s/(\s|　|\r|\n)//g;
						if ($cm_ =~ /\Q$_\E/i) {
							$spam=1; $reason = "名前/コメント内に禁止語句$_を含む投稿"; last; }
						if (!$spam && $na_ =~ /\Q$_\E/i) {
							$spam=1; $reason = "名前/コメント内に禁止語句$_を含む投稿"; last; }
						if (!$spam && $ur_ =~ /\Q$_\E/i) {
							$spam=1; $reason = "URLに禁止語句$_を含む投稿"; last; }
						if (!$spam && $ngmail && $em_ =~ /\Q$_\E/i) {
							$spam=1; $reason = "メールアドレスに禁止語句$_を含む投稿"; last; }
						if (!$spam && $ngtitle && $sb_ =~ /\Q$_\E/i) {
							$spam=1; $reason = "タイトルに禁止語句$_を含む投稿"; last; }
#fs0x7f-costom
#**original
#						if ($cm =~ /\Q$_\E/i) {
#							$spam=1; $reason = "名前/コメント内に禁止語句$_を含む投稿"; last; }
#						if (!$spam && $na =~ /\Q$_\E/i) {
#							$spam=1; $reason = "名前/コメント内に禁止語句$_を含む投稿"; last; }
#						if (!$spam && $ur =~ /\Q$_\E/i) {
#							$spam=1; $reason = "URLに禁止語句$_を含む投稿"; last; }
#						if (!$spam && $ngmail && $em =~ /\Q$_\E/i) {
#							$spam=1; $reason = "メールアドレスに禁止語句$_を含む投稿"; last; }
#						if (!$spam && $ngtitle && $sb =~ /\Q$_\E/i) {
#							$spam=1; $reason = "タイトルに禁止語句$_を含む投稿"; last; }
#**original
					}
				}
			}
		}
	}

	if(!$spam) {
		if ($urlcheck) {
			if ($urlcheck eq 2 || !$re) {
				# URLのコメントへの重複書き込みをチェック
				if($ur) {
					$ur =~ s/\/$//;
					if ($cm =~ /\Q$ur\E/i) {
						if ($' !~ /(^\/?[\w\?]+?)/)  {
							$spam=1; $reason = "コメント内にURL欄と同じURLを含む投稿";
						}
					}
				}
			}
		}
	}

	return ($spam,$reason);
}

#-------------------------------------------------
#  スパムログ記録
#-------------------------------------------------
sub write_spamlog {
	local($log_times,$num);
	local($log_comment) = $in{'comment'};
	local($log_name)    = $in{'name'};
	local($log_email)   = $in{'email'};
	local($log_url)     = $in{'url'};
	if (length($log_comment) < length($log_name)) {
		($log_comment,$log_name) = ($log_name,$log_comment);
	}
	if ($log_url =~ /\@/ || $log_email && $log_email !~ /\@/) {
		($log_email,$log_url)=($log_url,$log_email);
	}

	$num = ($log_comment =~ s/http/http/ig);
	if($num >= $maxurl) { $log_comment ="メッセージ内のURL数が$num個と多いため、メッセージ本文削除"; }
	$log_comment =~ s/"/&quot;/g;

	$times = time;
	if (!$in{"$bbscheckmode"}) { $log_times = $times; } else { $log_times = $in{"$bbscheckmode"}; }
	push (@spamlog,"$no<>$in{'reno'}<>$date<>$log_name<>$log_email<>$in{'sub'}<>$log_comment<>$log_url<>$host<>$pwd<>$col[$in{'color'}]<>$in{'icon'}<>$reason<>$log_times<>$ENV{'HTTP_REFERER'}<>$ENV{'HTTP_USER_AGENT'}<>$times<>\n");
	if (-e $spamlogfile) {
		open(OUT,">>$spamlogfile") || &error("Write Error");
		print OUT @spamlog;
		close(OUT);
	} else {
		open(OUT,">$spamlogfile");
		chmod (0606,"$spamlogfile");
		print OUT @spamlog;
		close(OUT);
	}

	# 古いスパムログを削除
	if ($spamlog_max) {
		while ((-s $spamlogfile) > $spamlog_maxfile ) {
			open(IN,"$spamlogfile") || &error("Open Error : >$spamlogfile");
			@spamlog = <IN>;
			close(IN);

			shift(@spamlog);

			open(OUT,">$spamlogfile") || &error("Write Error : $spamlogfile");
			print OUT @spamlog;
			close(OUT);
		}
	}

	# ロック解除
	&unlock if ($lockkey);
}

#-------------------------------------------------
#  スパムログ削除
#-------------------------------------------------
sub del_spamlog {
	local($checktime) = shift;
	$i=0;
	$flag=0;
	@newspm = ();
	open(IN,"$spamlogfile") || &error("Open Error: $spamlogfile");
	while (<IN>) {
		$i++;
		local ($no,$reno,$date,$name,$email,$sub,$msg,$url,$host,$pwd,$color,
		$icon,$reason,$fcheck,$referer,$useragent) = split(/<>/);
		if ($fcheck eq $checktime) {
			$flag=1;
			$_ = "";
		} else {
			$_ = "$no<>$reno<>$date<>$name<>$email<>$sub<>$msg<>$url<>$host<>$pwd<>$color<>$icon<>$reason<>$fcheck<>$referer<>$useragent<>\n";
		}
		push(@newspm,"$_");
	}
	close(IN);

	open(OUT,">$spamlogfile") || &error("Write Error : $spamlogfile");
	print OUT @newspm;
	close(OUT);
}

#-------------------------------------------------
#  プレビューチェック
#-------------------------------------------------
sub previewcheck {
	$in{"$bbscheckmode"} = &decode_bbsmode($in{"$bbscheckmode"});
	if ($mode eq "$writevalue") {
		if ($keychange) { ($in{'email'},$in{'url'})=($in{'url'},$in{'email'});
			($in{'comment'},$in{'name'}) = ($in{'name'},$in{'comment'}); }
		if ($previewtime) {
			if ($in{'pview'} ne "on") {
				local($posttime) = time;
				$timecheck = $posttime - $in{"$bbscheckmode"};
				if ($timecheck < 0) { $timecheck2 = 0 - $timecheck; }
				if ($timecheck <= $maxtime) {
					if ($timecheck > $previewmax) { $mode = "previewmode"; }
				}
				if ($timecheck >= $mintime) {
					if ($timecheck < $previewmin) { $mode = "previewmode"; }
				}
			}
		}
	}
}

#-------------------------------------------------
#  プレビュー画面
#-------------------------------------------------
sub previewmode {
	local($timecheck) = shift;
	$time     = time;
	$date     = &get_time($time);
	if ($in{'pwd'} ne "") { $pwd = &encrypt($in{'pwd'}); }
	$reason   = "プレビューモードトラップ($timecheck秒)";

	# プレビューログの記録
	if ($spamlog) { &write_spamlog; }
	$in{"$bbscheckmode"} = &encode_bbsmode($in{"$bbscheckmode"});

	# URL
	if ($in{'url'} eq "http://") { $in{'url'} = ""; }

	&header;
	print <<EOM;
<form action="$regist" method="$method">
<input type=hidden name=$bbscheckmode value=$in{"$bbscheckmode"}>
<!--//
<input type=hidden name=mode value="write">
//-->
<input type=hidden name=pwd value="$in{'pwd'}">
<input type=hidden name=name value="$in{'name'}">
<input type=hidden name=mail value="$in{'mail'}">
<input type=hidden name=email value="$in{'email'}">
<input type=hidden name=url value="$in{'url'}">
<input type=hidden name=reno value="$in{'reno'}">
<input type=hidden name=sub value="$in{'sub'}">
<input type=hidden name=icon value="$in{'icon'}">
<input type=hidden name=comment value="$in{'comment'}">
<input type=hidden name=color value="$in{'color'}">
<input type=hidden name=pview value="on">
<input type=hidden name=$formcheck value=$in{"$formcheck"}>
<input type=hidden name=aikotoba value="$in{'aikotoba'}">
EOM
	$in{'name'} =~ s/"/&quot;/g;
	$in{'sub'}  =~ s/"/&quot;/g;
	$in{'comment'} =~ s/"/&quot;/g;

	# 日本語チェック
	if ($in{'comment'} =~ /(\x82[\x9F-\xF2])/) {
		$checked0 = "checked"; $checked1 = "";
		$in{'url'} =~ s/\/$//;
		$in{'email'} =~ s/\/$//;
		# URL重複チェック
		if ($in{'url'} && $in{'comment'} =~ /\Q$in{'url'}\E/i ||
			$in{'email'} && $in{'comment'} =~ /\Q$in{'email'}\E/i) {
			if ($' !~ /(^\/?[\w\?]+?)/)  {
				$checked0 = ""; $checked1 = "checked";
			}
		}
	} else { $checked0 = ""; $checked1 = "checked"; }

	if (!$imode) {
		print <<EOM;
<div align=center>
▼ 内容を確認し、"投稿する"をチェックして投稿して下さい。<br>
<br>
<table border=1 width='90%' cellspacing=0 cellpadding=10>
<tr><td bgcolor="$tblCol">
<table>
<tr>
  <td><b>お名前</b></td>
  <td>$in{'name'}</td>
</tr>
<tr>
  <td><b>Ｅメール</b></td>
  <td>$in{'email'}</td>
</tr>
<tr>
  <td><b>タイトル</b></td>
  <td>$in{'sub'}</td>
</tr>
<tr>
  <td><b>参照先</b></td>
  <td>$in{'url'}</td>
</tr>
<tr>
  <td><b>メッセージ</b></td>
  <td></td>
</tr>
</table>
<blockquote>
<table cellspacing=10>
<tr>
<td valign=top>$in{'comment'}</td>
</tr></blockquote>
</table>
</table>
<p>
<table cellpadding=5>
<tr>
  <td colspan=2>
  <input type=radio name=mode value=$postvalue $checked0>投稿する&nbsp;&nbsp;
  <input type=radio name=mode value=regist $checked1><font color=#FF0000>投稿をやめる</font>
  </td>
</tr>
<tr>
  <td><div align=right>
   <input type=submit value=" 実行 ">
   </form></div></td>
   <td><form><div align=left>
     <input type=button value="前画面に戻る" onClick="history.back()">
     </div></form>
  </td>
</tr>
</table>
</form>
</div>
</body>
</html>
EOM
	  } else {
		print <<EOM;
▼ 内容を確認し、投稿を実行して下さい。
<hr>
おなまえ: 
<b style='color:#0000FF'>$in{'name'}</b><br>
題名: 
<b style='color:#0000FF'>$in{'sub'}</b><br>
Ｅメール: 
<b style='color:#0000FF'>$in{'email'}</b><br>
コメント<br>
<b style='color:#0000FF'>$in{'comment'}</b><br>
<hr>
<input type=radio name=mode value=$postvalue $checked0>投稿する
<br>
<input type=radio name=mode value=regist $checked1><font color=#FF0000>投稿をやめる</font>
<br>
<input type=submit value=" 実行 ">
</form>
<form>
<input type=button value="前画面に戻る" onClick="history.back()">
EOM
	}
	exit;
}

#-------------------------------------------------
#  スパムログ
#-------------------------------------------------
sub spam {
	# POST限定
	if ($postonly && !$post_flag) { &error("不正なアクセスです"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("パスワードが違います"); }

	&header;
	print <<EOM;
<UL>
<table border=0>
<tr><td><form action="$script">
<input type=submit value="掲示板に戻る">
</form>
</td><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" 管理画面に戻る ">
</form>
</td></tr>
</table></div>
</UL>
<UL><li>投稿拒否ログ<br>
「再投稿処理」をクリックすると誤ってスパム投稿として拒否された投稿を復活させることができます。<br>
必用な投稿を復活させたあとは、「投稿拒否ログを削除」しておいて下さい。
<form action="$regist" method="$method">
<input type=hidden name=mode value="spamclear">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" 投稿拒否ログを削除する ">
</form>
EOM
	open(IN,"$spamlogfile") || &error("Open Error : $spamlogfile");
	$i=0;
	while (<IN>) {
		local ($no,$reno,$date,$name,$email,$sub,$msg,$url,$host,$pwd,$color,
		$icon,$reason,$fcheck,$referer,$useragent) = split(/<>/);
		$reno{$i}      = $reno;
		$date{$i}      = $date;
		$name{$i}      = $name;
		$email{$i}     = $email;
		$sub{$i}       = $sub;
		$url{$i}       = $url;
		$msg{$i}       = $msg;
		$host{$i}      = $host;
		$pwd{$i}       = $pwd;
		$color{$i}     = $color;
		$icon{$i}      = $icon;
		$reason{$i}    = $reason;
		$timecheck{$i} = &encode_bbsmode($fcheck);
		if ($fcheck) { $fcheck{$i} = &get_time($fcheck); }
		else { $fcheck{$i} = "アクセス記録なし"; }
		$useragent{$i} = $useragent;
		if ($keychange) {
			if ($url{$i} && $url{$i} =~ /\@/) { ($email{$i},$url{$i})=($url{$i},$email{$i}); }
			elsif ($email{$i} && $email{$i} !~ /\@/) { ($email{$i},$url{$i})=($url{$i},$email{$i}); }
		}
		$i++;
	}
	close(IN);

	# ソート処理
	$j=0;
	$x=0;
	$page = $in{'page'};
	foreach (sort { ($date{$b} cmp $date{$a}) } keys(%date)) {
		$j++;
		if ($j < $page + 1) { next; }
		if ($j > $page + $spamlog_page) { next; }

		$useragent = "<small>$useragents</small>";
		print "<P><table border='1'>\n<tr>";
		print "<tr><td>投稿日時</td><td>$date{$_}</td><td>タイトル</td><td>$sub{$_}</td></tr>",
		"<tr><td>アクセス日時</td><td>$fcheck{$_}</td><td>投稿拒否理由</td><td>$reason{$_}</td></tr>",
		"<tr><td>投稿者名</td><td>$name{$_}</td><td>URL</td><td>$url{$_}</td></tr>",
		"<tr><td>ホストアドレス</td><td>$host{$_}</td><td>ブラウザ</td><td>$useragent{$_}</td></tr>",
		"<tr><td>メールアドレス</td><td>$email{$_}</td><td>投稿内容</td><td> ";
	print <<EOM;
<form action="$regist" method="$method">
<input type=hidden name=mode value="spammsg">
<input type=hidden name=pass value="$in{'pass'}">
<input type=hidden name=msg value="$msg{$_}">
<input type=submit value="投稿内容を閲覧">
</form></td></tr></table>
<table border=0><tr><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin_repost_form">
<input type=hidden name=pass value="$in{'pass'}">
<input type=hidden name=reno  value="$reno{$_}">
<input type=hidden name=date  value="$date{$_}">
<input type=hidden name=name  value="$name{$_}">
<input type=hidden name=email value="$email{$_}">
<input type=hidden name=sub   value="$sub{$_}">
<input type=hidden name=msg   value="$msg{$_}">
<input type=hidden name=url   value="$url{$_}">
<input type=hidden name=host  value="$host{$_}">
<input type=hidden name=pwd   value="$pwd{$_}">
<input type=hidden name=color value="$color{$_}">
<input type=hidden name=icon  value="$icon{$_}">
<input type=hidden name=$bbscheckmode value="$timecheck{$_}">
<input type=hidden name=reason value="$reason{$_}">
<input type=submit value="再投稿処理">
</form></td><td>(上記の投稿を復活させることができます)</td></tr></table>
EOM
	}

	print "</table><br>\n";
	$next = $page + $spamlog_page;
	$back = $page - $spamlog_page;

	print "<table><tr>\n";
	if ($back >= 0) {
		print "<td><form action=\"$regist\" method=\"POST\">\n";
		print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
		print "<input type=hidden name=mode value=\"$in{'mode'}\">\n";
		print "<input type=hidden name=page value=\"$back\">\n";
		print "<input type=submit value=\"前画面\"></form></td>\n";
	}
	if ($next < $i) {
		print "<td><form action=\"$regist\" method=\"POST\">\n";
		print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
		print "<input type=hidden name=mode value=\"$in{'mode'}\">\n";
		print "<input type=hidden name=page value=\"$next\">\n";
		print "<input type=submit value=\"次画面\"></form></td>\n";
	}
	print "</tr></table>\n";
	print <<EOM;
<form action="$regist" method="$method">
<input type=hidden name=mode value="spamclear">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" 投稿拒否ログを削除する ">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  投稿拒否ログ初期化
#-------------------------------------------------
sub spamclear {
	# POST限定
	if ($postonly && !$post_flag) { &error("不正なアクセスです"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("パスワードが違います"); }

	# 投稿拒否ログの初期化
	open(OUT,">$spamlogfile");
	chmod (0606,"$spamlogfile");
	print OUT "";
	close(OUT);

	&header();
	print <<EOM;
<div align="center">
<h4>投稿拒否ログを削除しました</h4>
<table border=0>
<tr><td><form action="$script">
<input type=submit value="掲示板に戻る">
</form>
</td><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" 管理画面に戻る ">
</form>
</td></tr>
</table></div>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  投稿拒否コメント
#-------------------------------------------------
sub spammsg {
	# POST限定
	if ($postonly && !$post_flag) { &error("不正なアクセスです"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("パスワードが違います"); }

	# エスケープ
	$in{'msg'} =~ s/"/&quot;/g;
	$in{'msg'} =~ s/</&lt;/g;
	$in{'msg'} =~ s/>/&gt;/g;
	# 改行処理
	$in{'msg'} =~ s/&lt;br&gt;/<br>/ig;

	&header();
	print <<EOM;
<div align="center">
<h4>コメント</h4>
<div align'left'>
<P><table border='1'>
<tr><td>$in{'msg'}</td></tr>
</table><BR>
<table border=0>
<tr><td><form action="$regist" method="$method">
<input type=hidden name=mode value="spam">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" 投稿拒否ログ閲覧に戻る ">
</form>
</td><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" 管理画面に戻る ">
</form>
</td></tr>
</table></div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  NGワード編集
#-------------------------------------------------
sub spamdata {
	# POST限定
	if ($postonly && !$post_flag) { &error("不正なアクセスです"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("パスワードが違います"); }

	&header;
	print <<EOM;
<div align="left">
<table border=0>
<tr><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" 管理画面に戻る ">
</form>
</td></tr>
</table></div>
<BR>
<li>NGワードを一括登録できます(半角のカンマで区切る)。<br>
たとえば <b>http://www.example.com/sample/inde.cgi?mode=test</b> を<br>
拒否したい場合は<b> www.example.com </b>を登録します。
<form action="$regist" method="$method">
<input type=hidden name=mode value="editspam">
<input type=hidden name=pass value="$in{'pass'}">
EOM
	if (-e $spamdata) {
		open(IN,"$spamdata");
		$SPMLST = <IN>;
		close(IN);
	}

	print <<EOM;
<textarea name=SPMLST rows=30 cols=80 wrap=soft>$SPMLST</textarea><br>
<br>
<input type=submit value="更新する">
</form>
</ul>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  NGワード更新
#-------------------------------------------------
sub editspam {

	# POST限定
	if ($postonly && !$post_flag) { &error("不正なアクセスです"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("パスワードが違います"); }

	$SPMLST = $in{"SPMLST"};

	# 空データ・改行・空白を削除
	$SPMLST =~ s/，/\,/g;
	$SPMLST =~ s/<br>//ig;
	$SPMLST =~ s/\n//g;
	$SPMLST =~ s/\r//g;
	$SPMLST =~ s/　//g;
	$SPMLST =~ s/\,{2,}/\,/g;
	$SPMLST =~ s/^\,{1,}//;

	open(OUT,">$spamdata") || &error("Write Error");
	print OUT $SPMLST;
	close(OUT);

	&header;

	print <<EOM;
<div align="center">
<h4>NGワードを更新しました</h4>
<BR>
<table border=0>
<tr><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" 管理画面に戻る ">
</form>
</td></tr>
</table></div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  管理者再投稿画面
#-------------------------------------------------
sub admin_repost_form {

	# POST限定
	if ($postonly && !$post_flag) { &error("不正なアクセスです"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("パスワードが違います"); }

	$in{'msg'} =~ s/<br>/\n/g;
	$in{'msg'} =~ s/&lt;br&gt;/\n/g;
	$in{"$bbscheckmode"} = &encode_bbsmode($in{"$bbscheckmode"});
	$f_c_d = int(rand(5E07)) + 11E08;

	local($iflag) = 0;
	local($i) = 0;
	foreach(split(/\s+/, $ico1)) {
		if ($in{'icon'} =~ /$_/) {
			$iflag = 1; $in{'icon'} = $i; last;
		}
		$i++;
	}
	if(!$iflag) { $in{'icon'} = 0; }

	local($cflag) = 0;
	local($j) = 0;
	foreach(split(/\s+/, $color)) {
		if ($in{'color'} =~ /\Q$_\E/) {
			$cflag = 1; $col = $j; last;
		}
		$j++;
	}
	if(!$cflag) { $col = 0; }

	&header;
	print <<EOM;
<h3>スパム投稿として処理された下記の投稿を復活させます。</h3>
<hr>
<table border=0 cellspacing=1>
<form action="$regist" method="$method">
<input type=hidden name=$bbscheckmode value=$in{"$bbscheckmode"}>
<input type=hidden name=mode value="admin_repost">
<input type=hidden name=pass value="$in{'pass'}">
<input type=hidden name=reno value="$in{'reno'}">
<input type=hidden name=date value="$in{'date'}">
<input type=hidden name=host value="$in{'host'}">
<input type=hidden name=pwd value="$in{'pwd'}">
<input type=hidden name=color value="$col">
<input type=hidden name=icon value="$in{'icon'}">
<tr>
  <td><b style='color:#FF0000'>投稿拒否理由&nbsp;:&nbsp;$in{'reason'}</b><br><br></td>
</tr>
<tr>
  <td><b>お名前</b>&nbsp;:&nbsp;
    <input type=hidden name=name value="$in{'name'}" class=f>$in{'name'}</td>
</tr>
<tr>
  <td><b>Ｅメール</b>&nbsp;:&nbsp;
    <input type=text name=email size=28 value="$in{'email'}"></td>
</tr>
<tr>
  <td><b>タイトル</b>&nbsp;:&nbsp;
    <input type=hidden name=sub value="$in{'sub'}" class=f>$in{'sub'}
  </td>
</tr>
<tr>
  <td>
    <b>メッセージ</b><br>
    <textarea cols=56 rows=7 name=comment wrap="soft" class=f>$in{'msg'}</textarea>
  </td>
</tr>
<tr>
  <td><input type=hidden name=$formcheck value="$f_c_d">
  </td>
</tr>
<tr>
  <td><b>参照先</b>&nbsp;:&nbsp;
  <input type=text name=url size=50 value="$in{'url'}" class=f></td>
</tr>
</table>
<table><tr><td>
<input type=submit value="投稿復活処理する">
</form>
</td>
<td><form action="$regist" method="$method">
<input type=hidden name=mode value="spam">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" 投稿拒否ログ閲覧に戻る ">
</form>
</td></tr></table>
</body>
</html>
EOM
	exit;
}

__END__

