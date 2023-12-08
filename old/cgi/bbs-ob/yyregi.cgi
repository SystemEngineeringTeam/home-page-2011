#!/usr/bin/perl

#┌─────────────────────────────────
#│  YY-BOARD - yyregi.cgi - 2003/11/08
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
if ($mode eq "dele") { &dele; }
elsif ($mode eq "edit") { &edit; }
elsif ($mode eq "regist") { &regist; }
elsif ($mode eq "past") { &past; }
elsif ($mode eq "admin") { &admin; }
&error("不明な処理です");

#-------------------------------------------------
#  記事登録
#-------------------------------------------------
sub regist {
	local($flag,$oyaChk,@lines,@data,@new,@tmp);

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

	# URL自動リンク
	if ($autolink) { &auto_link($in{'comment'}); }

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
	if ($in{'pwd'} ne "") { $pwd = &encrypt($in{'pwd'}); }

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

		# 更新
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);

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

		# 更新
		unshift(@new,"$no<>$addr<>$times<>\n");
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);

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

		# 更新
		unshift(@new,"$no<>$addr<>$times<>\n");
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);
	}

	# ロック解除
	if ($lockkey) { &unlock; }

	# クッキー発行
	&set_cookie($in{'name'},$in{'email'},$in{'url'},$in{'pwd'},$in{'icon'},$in{'color'});

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
<form action="$regist" method="POST">
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
	if ($in_email && $in{'email'} !~ /[\w\.\-]+\@[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
		&error("Ｅメールの入力内容が正しくありません");
	}

	if ($iconMode) {
		@ico1 = split(/\s+/, $ico1);
		@ico2 = split(/\s+/, $ico2);
		if ($my_icon) { push(@ico1,$my_gif); }
		if ($in{'icon'} =~ /\D/ || $in{'icon'} < 0 || $in{'icon'} > @ico1) {
			&error("アイコン情報が不正です");
		}
		$in{'icon'} = $ico1[$in{'icon'}];

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
<UL>
<LI>処理を選択し、記事をチェックして送信ボタンを押して下さい。
<LI>親記事を削除するとレス記事も一括して削除されます。
</UL>
<form action="$regist" method="POST">
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

	$next = $page + $pastView;
	$back = $page - $pastView;

	print "<p><table cellspacing=0 cellpadding=0><tr><td></td>\n";
	if ($back >= 0) {
		print "<td><form action=\"$regist\" method=\"POST\">\n";
		print "<input type=hidden name=page value=\"$back\">\n";
		print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
		print "<input type=hidden name=mode value=\"admin\">\n";
		print "<input type=submit value=\"前の$pastView組\"></td></form>\n";
	}
	if ($next < $i) {
		print "<td><form action=\"$regist\" method=\"POST\">\n";
		print "<input type=hidden name=page value=\"$next\">\n";
		print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
		print "<input type=hidden name=mode value=\"admin\">\n";
		print "<input type=submit value=\"次の$pastView組\"></td></form>\n";
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
<form action="$regist" method="POST">
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
<form action="$regist" method="POST">
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
	$ENV{'TZ'} = "JST-9";
	$times = time;
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
	local($msub,$mbody,$email);

	# 記事の改行・タグを復元
	$com  = $in{'comment'};
	$com =~ s/<br>/\n/g;
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

	local $salt = $dec =~ /^\$1\$(.*)\$/ && $1 || substr($dec, 0, 2);
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
	$_[0] =~ s/([^=^\"]|^)(https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+)/$1<a href=\"$2\" target=\"_blank\">$2<\/a>/g;
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
<form action="$regist" method="POST">
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
<form action="$regist" method="POST">
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

		if ($eml) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }
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


__END__

