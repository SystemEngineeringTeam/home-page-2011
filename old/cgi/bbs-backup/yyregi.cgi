#!/usr/bin/perl

#��������������������������������������������������������������������
#��  YY-BOARD - yyregi.cgi - 2003/11/08
#��  Copyright (c) KentWeb
#��  webmaster@kent-web.com
#��  http://www.kent-web.com/
#��������������������������������������������������������������������

# �O���t�@�C���捞
require './jcode.pl';
require './yyini.cgi';

# ���C������
&decode;
&axsCheck;
if ($mode eq "dele") { &dele; }
elsif ($mode eq "edit") { &edit; }
elsif ($mode eq "regist") { &regist; }
elsif ($mode eq "past") { &past; }
elsif ($mode eq "admin") { &admin; }
&error("�s���ȏ����ł�");

#-------------------------------------------------
#  �L���o�^
#-------------------------------------------------
sub regist {
	local($flag,$oyaChk,@lines,@data,@new,@tmp);

	# �t�H�[�����̓`�F�b�N
	&formCheck;

	# ���Ԏ擾
	&get_time;

	# �t�@�C�����b�N
	if ($lockkey) { &lock; }

	# ���O���J��
	open(IN,"$logfile") || &error("Open Error: $logfile");
	@lines = <IN>;
	close(IN);

	# �L��NO����
	$top = shift(@lines);
	local($no,$ip,$tim) = split(/<>/, $top);
	$no++;

	# �A�����e�`�F�b�N
	$flag=0;
	if ($regCtl == 1) {
		if ($addr eq $ip && $times - $tim < $wait) { $flag=1; }
	} elsif ($regCtl == 2) {
		if ($times - $tim < $wait) { $flag=1; }
	}
	if ($flag) {
		&error("���ݓ��e�������ł��B�������΂炭�����Ă��瓊�e�����肢���܂�");
	}

	# URL���������N
	if ($autolink) { &auto_link($in{'comment'}); }

	# �d���`�F�b�N
	$flag=0;
	foreach (@lines) {
		local($no2,$re,$dat,$nam,$eml,$sub,$com) = split(/<>/);
		if ($in{'name'} eq $nam && $in{'comment'} eq $com) {
			$flag=1; last;
		}
	}
	if ($flag) { &error("�d�����e�̂��ߏ����𒆒f���܂���"); }

	# �Ï؃L�[���Í���
	if ($in{'pwd'} ne "") { $pwd = &encrypt($in{'pwd'}); }

	# �e�L���̏ꍇ
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

		# �ߋ����O�X�V
		if (@data > 0) { &pastlog(@data); }

		# �X�V
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);

	# ���X�L���̏ꍇ�F�g�b�v�\�[�g����
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
		if ($f) { &error("�s���ȕԐM�v���ł�"); }
		if (!$oyaChk) { &error("�e�L�������݂��܂���"); }

		if ($match == 1) {
			push(@new,"$no<>$in{'reno'}<>$date<>$in{'name'}<>$in{'email'}<>$in{'sub'}<>$in{'comment'}<>$in{'url'}<>$host<>$pwd<>$col[$in{'color'}]<>$in{'icon'}<>\n");
		}
		push(@new,@tmp);

		# �X�V
		unshift(@new,"$no<>$addr<>$times<>\n");
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);

	# ���X�L���̏ꍇ�F�g�b�v�\�[�g�Ȃ�
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
		if ($f) { &error("�s���ȕԐM�v���ł�"); }
		if (!$oyaChk) { &error("�e�L�������݂��܂���"); }

		if ($match == 1) {
			push(@new,"$no<>$in{'reno'}<>$date<>$in{'name'}<>$in{'email'}<>$in{'sub'}<>$in{'comment'}<>$in{'url'}<>$host<>$pwd<>$col[$in{'color'}]<>$in{'icon'}<>\n");
		}

		# �X�V
		unshift(@new,"$no<>$addr<>$times<>\n");
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);
	}

	# ���b�N����
	if ($lockkey) { &unlock; }

	# �N�b�L�[���s
	&set_cookie($in{'name'},$in{'email'},$in{'url'},$in{'pwd'},$in{'icon'},$in{'color'});

	# ���[������
	if ($mailing == 1 && $in{'email'} ne $mailto) { &mail_to; }
	elsif ($mailing == 2) { &mail_to; }

	# �����[�h
	if ($location) {
		if ($ENV{'PERLXS'} eq "PerlIS") {
			print "HTTP/1.0 302 Temporary Redirection\r\n";
			print "Content-type: text/html\n";
		}
		print "Location: $location?\n\n";
		exit;

	} else {
		&message('���e�͐���ɏ�������܂���');
	}
}

#-------------------------------------------------
#  �L���폜
#-------------------------------------------------
sub dele {
	local($flag,$check,$no,$reno,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,@new);

	# POST����
	if ($postonly && !$post_flag) { &error("�s���ȃA�N�Z�X�ł�"); }

	if ($in{'no'} eq '' || $in{'pwd'} eq '')
		{ &error("�L��No�܂��͈Ï؃L�[�����̓����ł�"); }

	# ���b�N����
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
	if (!$flag) { &error("�Y���̋L������������܂���"); }
	if ($pw2 eq "") { &error("�Ï؃L�[���ݒ肳��Ă��܂���"); }

	$check = &decrypt($in{'pwd'}, $pw2);
	if ($check != 1) { &error("�Ï؃L�[���Ⴂ�܂�"); }

	unshift(@new,$top);
	open(OUT,">$logfile") || &error("Write Error: $logfile");
	print OUT @new;
	close(OUT);

	# ���b�N����
	&unlock if ($lockkey);

	# �������b�Z�[�W
	&message("�폜���������܂���");
}

#-------------------------------------------------
#  �L���C��
#-------------------------------------------------
sub edit {
	local($top,$flag,$pattern,$no,$reno,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico);

	if ($in{'no'} eq '' || $in{'pwd'} eq '')
		{ &error("�L��No�܂��͈Ï؃L�[�����̓����ł�"); }

	# �C�����s
	if ($in{'job'} eq "edit") {

		# �t�H�[�����̓`�F�b�N
		&formCheck('edit');

		if ($autolink) { &auto_link($in{'comment'}); }

		# ���b�N����
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
		if (!$flag) { &error("�Y���̋L������������܂���"); }
		if ($pw2 eq "") { &error("�Ï؃L�[���ݒ肳��Ă��܂���"); }

		$check = &decrypt($in{'pwd'}, $pw2);
		if ($check != 1) { &error("�Ï؃L�[���Ⴂ�܂�"); }

		unshift(@new,$top);
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);

		# ���b�N����
		&unlock if ($lockkey);

		# �������b�Z�[�W
		&message("�C�����������܂���");
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
	if (!$flag) { &error("�Y���̋L������������܂���"); }
	if ($pw2 eq "") { &error("�Ï؃L�[���ݒ肳��Ă��܂���"); }

	$check = &decrypt($in{'pwd'}, $pw2);
	if ($check != 1) { &error("�Ï؃L�[���Ⴂ�܂�"); }

	$com =~ s/<br>/\n/g;
	$pattern = 'https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+';
	$com =~ s/<a href="$pattern" target="_blank">($pattern)<\/a>/$1/go;

	if ($ImageView == 1) { &header('ImageUp'); }
	else { &header; }

	print <<EOM;
<form>
<input type=button value="�O��ʂɖ߂�" onClick="history.back()">
</form>
���ύX���镔���̂ݏC�����đ��M�{�^���������ĉ������B
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
#  ��������
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
<input type=submit value="�f���ɖ߂�">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  ���͊m�F
#-------------------------------------------------
sub formCheck {
	local($task) = @_;
	local($ref);

	# POST����
	if ($postonly && !$post_flag) { &error("�s���ȃA�N�Z�X�ł�"); }

	# ���T�C�g����̃A�N�Z�X�r��
	if ($task ne 'edit' && $baseUrl) {
		$ref = $ENV{'HTTP_REFERER'};
		$ref =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("H2", $1)/eg;
		$baseUrl =~ s/(\W)/\\$1/g;
		if ($ref && $ref !~ /$baseUrl/i) { &error("�s���ȃA�N�Z�X�ł�"); }
	}

	# ���O�ƃR�����g�͕K�{
	if ($in{'name'} eq "") { &error("���O�����͂���Ă��܂���"); }
	if ($in{'comment'} eq "") { &error("�R�����g�����͂���Ă��܂���"); }
	if ($in_email && $in{'email'} !~ /[\w\.\-]+\@[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
		&error("�d���[���̓��͓��e������������܂���");
	}

	if ($iconMode) {
		@ico1 = split(/\s+/, $ico1);
		@ico2 = split(/\s+/, $ico2);
		if ($my_icon) { push(@ico1,$my_gif); }
		if ($in{'icon'} =~ /\D/ || $in{'icon'} < 0 || $in{'icon'} > @ico1) {
			&error("�A�C�R����񂪕s���ł�");
		}
		$in{'icon'} = $ico1[$in{'icon'}];

		# �Ǘ��A�C�R���`�F�b�N
		if ($my_icon && $in{'icon'} eq $my_gif && $in{'pwd'} ne $pass) {
			&error("�Ǘ��p�A�C�R���͊Ǘ��Ґ�p�ł�");
		}
	}

	@col = split(/\s+/, $color);
	if ($in{'color'} =~ /\D/ || $in{'color'} < 0 || $in{'color'} > @col) {
		&error("�����F��񂪕s���ł�");
	}

	# URL
	if ($in{'url'} eq "http://") { $in{'url'} = ""; }
}

#-------------------------------------------------
#  �Ǘ����[�h
#-------------------------------------------------
sub admin {
	local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$next,$back,$top,$i);

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("�p�X���[�h���Ⴂ�܂�"); }

	# �C�����
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

		# �C���t�H�[��
		&edit_form(@f);

	# �C�����s
	} elsif ($in{'job'} eq "edit2" && $in{'no'}) {

		local(@col,@ico,@new);
		if ($in{'url'} eq "http://") { $in{'url'} = ''; }

		@col = split(/\s+/, $color);
		@ico1 = split(/\s+/, $ico1);
		if ($my_icon) { push(@ico1,$my_gif); }
		$in{'icon'} = $ico1[$in{'icon'}];

		# URL���������N
		if ($autolink) { &auto_link($in{'comment'}); }

		# ���b�N�J�n
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

		# �X�V
		unshift(@new,$top);
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);

		# ���b�N����
		if ($lockkey) { &unlock; }

	# �폜
	} elsif ($in{'job'} eq "dele" && $in{'no'}) {

		# ���b�N�J�n
		if ($lockkey) { &lock; }

		# �폜�����}�b�`���O
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

		# �X�V
		unshift(@new,$top);
		open(OUT,">$logfile") || &error("Write Error: $logfile");
		print OUT @new;
		close(OUT);

		# ���b�N����
		if ($lockkey) { &unlock; }
	}

	&header;
	print <<EOM;
<form action="$script">
<input type=submit value="�f���ɖ߂�">
</form>
<UL>
<LI>������I�����A�L�����`�F�b�N���đ��M�{�^���������ĉ������B
<LI>�e�L�����폜����ƃ��X�L�����ꊇ���č폜����܂��B
</UL>
<form action="$regist" method="POST">
<input type=hidden name=mode value="admin">
<input type=hidden name=page value="$page">
<input type=hidden name=pass value="$in{'pass'}">
<select name=job>
<option value="edit">�C��
<option value="dele">�폜
</select>
<input type=submit value="���M����">
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

		# �폜�`�F�b�N�{�b�N�X
		if (!$res) { print "<dt><hr>"; } else { print "<dd>"; }
		print "<input type=checkbox name=no value=\"$no\">";
		print "[<b>$no</b>] <b style='color:$subCol'>$sub</b>\n";
		print "���e�ҁF$nam ���e���F$dat �y$hos�z\n";
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
		print "<input type=submit value=\"�O��$pastView�g\"></td></form>\n";
	}
	if ($next < $i) {
		print "<td><form action=\"$regist\" method=\"POST\">\n";
		print "<input type=hidden name=page value=\"$next\">\n";
		print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
		print "<input type=hidden name=mode value=\"admin\">\n";
		print "<input type=submit value=\"����$pastView�g\"></td></form>\n";
	}

	print <<EOM;
</tr></table>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  �C�����
#-------------------------------------------------
sub edit_form {
	local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = @_;

	$com =~ s/<br>/\n/g;

	if ($ImageView == 1) { &header('ImageUp'); }
	else { &header; }
	print <<EOM;
<form>
<input type=button value="�O��ʂɖ߂�" onClick="history.back()">
</form>
���ύX���镔���̂ݏC�����đ��M�{�^���������ĉ������B
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
#  �������
#-------------------------------------------------
sub enter {
	&header;
	print <<EOM;
<div align="center">
<h4>�p�X���[�h����͂��Ă�������</h4>
<form action="$regist" method="POST">
<input type=hidden name=mode value="admin">
<input type=password name=pass size=8 class=f>
<input type=submit value=" �F�� ">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  ���Ԏ擾
#-------------------------------------------------
sub get_time {
	$ENV{'TZ'} = "JST-9";
	$times = time;
	($sec,$min,$hour,$mday,$mon,$year,$wday) = localtime($times);
	local(@week) = ('Sun','Mon','Tue','Wed','Thu','Fri','Sat');

	# �����̃t�H�[�}�b�g
	$date = sprintf("%04d/%02d/%02d(%s) %02d:%02d",
			$year+1900,$mon+1,$mday,$week[$wday],$hour,$min);
}

#-------------------------------------------------
#  ���[�����M
#-------------------------------------------------
sub mail_to {
	local($msub,$mbody,$email);

	# �L���̉��s�E�^�O�𕜌�
	$com  = $in{'comment'};
	$com =~ s/<br>/\n/g;
	$com =~ s/&lt;/</g;
	$com =~ s/&gt;/>/g;
	$com =~ s/&quot;/"/g;
	$com =~ s/&amp;/&/g;

	# ���[���{�����`
	$mbody = <<EOM;
���e�����F$date
�z�X�g���F$host
�u���E�U�F$ENV{'HTTP_USER_AGENT'}

���e�Җ��F$in{'name'}
�d���[���F$in{'email'}
�Q�Ɛ�  �F$in{'url'}
�^�C�g���F$in{'sub'}

$com
EOM

	# �薼��BASE64��
	$msub = &base64("$title (No.$no)");

	# ���[���A�h���X���Ȃ��ꍇ�͊Ǘ��҃A�h���X�ɒu������
	if ($in{'email'} eq "") { $email = $mailto; }
	else { $email = $in{'email'}; }

	open(MAIL,"| $sendmail -t") || &error("���[�����M���s");
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
#  BASE64�ϊ�
#-------------------------------------------------
#		�Ƃقق�WWW����Ō��J����Ă��郋�[�`����
#		�Q�l�ɂ��܂����B( http://tohoho.wakusei.ne.jp/ )
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
#  crypt�Í�
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
#  crypt�ƍ�
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
#  ����URL�����N
#-------------------------------------------------
sub auto_link {
	$_[0] =~ s/([^=^\"]|^)(https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+)/$1<a href=\"$2\" target=\"_blank\">$2<\/a>/g;
}

#-------------------------------------------------
#  �ߋ����O����
#-------------------------------------------------
sub pastlog {
	local(@data) = @_;
	local($count,$pastfile,$i,$f,@past);

	# �ߋ����ONo�t�@�C��
	open(NO,"$nofile") || &error("Open Error: $nofile");
	$count = <NO>;
	close(NO);
	$pastfile = sprintf("%s%04d\.cgi", $pastdir,$count);

	# �ߋ����O���J��
	$i=0; $f=0;
	open(IN,"$pastfile") || &error("Open Error: $pastfile");
	while (<IN>) {
		$i++;
		push(@past,$_);
		if ($i >= $pastmax) { $f++; last; }
	}
	close(IN);

	# �K��̍s�����I�[�o�[����Ǝ��t�@�C������������
	if ($f) {
		# �J�E���g�t�@�C���X�V
		open(NO,">$nofile") || &error("Write Error: $nofile");
		print NO ++$count;
		close(NO);

		$pastfile = sprintf("%s%04d\.cgi", $pastdir,$count);
		@past = @data;
	} else {
		unshift(@past,@data);
	}

	# �ߋ����O�X�V
	open(OUT,">$pastfile") || &error("Write Error: $pastfile");
	print OUT @past;
	close(OUT);

	if ($f) { chmod(0666, $pastfile); }
}

#-------------------------------------------------
#  �ߋ����O
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
<input type=submit value="�f���ɖ߂�"></form>
<form action="$regist" method="POST">
<input type=hidden name=mode value=past>
<table border=0>
<tr><td><b>�ߋ����O</b> <select name=pastlog class=f>
EOM

	# �ߋ����O�I��
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
<input type=submit value="�ړ�"></td></form>
<td width=15></td><td>
<form action="$regist" method="POST">
<input type=hidden name=mode value="past">
<input type=hidden name=pastlog value="$in{'pastlog'}">
�L�[���[�h <input type=text name=word size=35 value="$in{'word'}" class=f>
���� <select name=cond class=f>
EOM

	if (!$in{'cond'}) { $in{'cond'} = "AND"; }
	foreach ("AND", "OR") {
		if ($in{'cond'} eq $_) {
			print "<option value=\"$_\" selected>$_\n";
		} else {
			print "<option value=\"$_\">$_\n";
		}
	}

	print "</select> �\\�� <select name=view class=f>\n";

	if (!$in{'view'}) { $in{'view'} = 10; }
	foreach (10,15,20,25) {
		if ($in{'view'} == $_) {
			print "<option value=\"$_\" selected>$_��\n";
		} else {
			print "<option value=\"$_\">$_��\n";
		}
	}

	print <<EOM;
</select>
<input type=submit value="����"></td>
</form>
</tr></table>
EOM

	$file = sprintf("%s%04d\.cgi", $pastdir,$in{'pastlog'});

	# ��������
	if ($in{'word'} ne "") {

		($i,$next,$back) = &search($file,$in{'word'},$in{'view'},$in{'cond'});

		$enwd = &url_enc($in{'word'});
		if ($back >= 0) {
			print "[<a href=\"$regist?mode=past&pastlog=$in{'pastlog'}&page=$back&word=$enwd&view=$in{'view'}&cond=$in{'cond'}\">�O��$in{'view'}��</a>]\n";
		}
		if ($next < $i) {
			print "[<a href=\"$regist?mode=past&pastlog=$in{'pastlog'}&page=$next&word=$enwd&view=$in{'view'}&cond=$in{'cond'}\">����$in{'view'}��</a>]\n";
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

	# �y�[�W�ړ��{�^���\��
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

