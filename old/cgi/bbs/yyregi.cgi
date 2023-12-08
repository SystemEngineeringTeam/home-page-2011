#!/usr/local/bin/perl

#��������������������������������������������������������������������
#�� YY-BOARD
#�� yyregi.cgi - 2005/11/20
#�� Copyright (c) KentWeb
#�� webmaster@kent-web.com
#�� http://www.kent-web.com/
#��
#�� YY-BOARD v5.5�p�g�ѓd�b�Ή��X�N���v�g
#�� 2005/1/4�@����H�@http://www.url-battle.com/cgi/
#��
#�� Antispam Version Modified by isso. August, 2006
#�� http://swanbay-web.hp.infoseek.co.jp/index.html
#��������������������������������������������������������������������

# �O���t�@�C���捞
require './jcode.pl';
require './yyini.cgi';

# ���C������
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
&error("�s���ȏ����ł�");

#-------------------------------------------------
#  �L���o�^
#-------------------------------------------------
sub regist {
	local($flag,$oyaChk,@lines,@data,@new,@tmp);
	local($cnam,$ceml,$curl,$cpwd,$cico,$ccol,$caikotoba) = &get_cookie;

	# �g���I�v�V�����`�F�b�N
	if ($mode ne "admin_repost") {
		&option_check($in{'pwd'},$in{'email'},$in{'comment'},$in{'url'});
	}
	if ($in{'email'} && $in{'email'} =~ /��/) { $in{'email'} =~ s/��/\@/; }

	# �������t���`�F�b�N
	if ($aikotoba) {
		if ($in{'aikotoba'} ne $aikotoba) { &error("�������t���s���ł�"); }
	}

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

	# �֎~���[�h�`�F�b�N
	if ($deny_word) {
		&deny_word($in{'name'});
		&deny_word($in{'comment'});
	}

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
	if ($mode ne "admin_repost") {
		if ($in{'pwd'} ne "") { $pwd = &encrypt($in{'pwd'}); }
	}

	#�g�у`�F�b�N
	$agent  = $ENV{HTTP_USER_AGENT};
	if ($imode == 5){
		($carrier,$ver,$host2,$sub ) = split( "/",$agent);
		$host2 =~ s/ .*$//;# J-SH51 SH�i���[�J�[���폜�j
		$host2 =~ s/_[a-z]$//;# J-DN03�i�����폜�j
	}
	if ($imode == 2){
		$host2 = ( $agent =~ m#^[^\-]+\-([A-Z]\w+)#i )[0];
	}
	if ($imode == 1){
		($docomo, $ver, $host2, $sub ) = split( /[\/\s\(\)]+/, $agent );
	}

	# ���s�E�_�u���N�I�[�g����
	if ($in{'pview'} eq "on") {
		$in{'comment'} =~ s/&lt;br&gt;/<br>/g;
		$in{'comment'} =~ s/&quot;/"/g;
	}

	# �X�p�����e�`�F�b�N
	($spam,$reason) = &spam_check($in{'name'},$in{'url2'},$in{"$bbscheckmode"},$in{'comment'},
	$in{'reno'},$in{'url'},$in{'email'},$in{'sub'},$in{'mail'},$in{"$formcheck"},$cnam,
	$in{'subject'},$in{'title'},$in{'theme'},$ENV{'HTTP_ACCEPT_LANGUAGE'},$ENV{'HTTP_USER_AGENT'});

	# �v���r���[���X�p�����O�̍폜
	if ($in{'pview'} eq "on" || $mode eq "admin_repost") {
		if ($spamlog) { &del_spamlog("$in{\"$bbscheckmode\"}"); }
	}

	# �X�p�����e����
	if ($spam && $mode ne "admin_repost") {
		# ���e���ۃ��O�̋L�^
		if ($spamlog) { &write_spamlog; }
		if ($spamresult) {
			# �G���[�\��
			if ($spamresult eq '1')  { &error("���f���e�̂��ߏ����𒆒f���܂���"); }
			else { sleep($spamresult); &error("���f���e�̂��ߏ����𒆒f���܂���"); }
		} elsif ($spammsg) { &message("$spammsg");
		} else { &access_error; }
	}

	# URL���������N
	if ($autolink) { &auto_link($in{'comment'}); }

	# ��������
	if ($mode eq "admin_repost") {
		$date= $in{'date'};
		$host= $in{'host'};
		$pwd = $in{'pwd'};
	}

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

		# �X�V(�ꎞ�t�@�C�����쐬)
		if (!$logbackup) { $tempfile = $logfile; }
		open(OUT,">$tempfile") || &error("Write Error : ���O�t�@�C���ɏ������݂��ł��܂���B<BR>�f���ݒu�f�B���N�g���̃p�[�~�b�V�����ݒ��ύX���Ă��������B");
		chmod (0606,$tempfile);
		print OUT @new;
		close(OUT);
		# �ꎞ�t�@�C������X�V���Ƀ��O�t�@�C���Ƀ��l�[��
		if ( $logbackup && (-s $tempfile) > 100 ) { rename ($tempfile,$logfile) || &error("Rename Error"); }

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

		# �X�V(�ꎞ�t�@�C�����쐬)
		unshift(@new,"$no<>$addr<>$times<>\n");
		if (!$logbackup) { $tempfile = $logfile; }
		open(OUT,">$tempfile") || &error("Write Error : ���O�t�@�C���ɏ������݂��ł��܂���B<BR>�f���ݒu�f�B���N�g���̃p�[�~�b�V�����ݒ��ύX���Ă��������B");
		chmod (0606,$tempfile);
		print OUT @new;
		close(OUT);
		# �ꎞ�t�@�C������X�V���Ƀ��O�t�@�C���Ƀ��l�[��
		if ( $logbackup && (-s $tempfile) > 100 ) { rename ($tempfile,$logfile) || &error("Rename Error"); }

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

		# �X�V(�ꎞ�t�@�C�����쐬)
		unshift(@new,"$no<>$addr<>$times<>\n");
		open(OUT,">$tempfile") || &error("Write Error : ���O�t�@�C���ɏ������݂��ł��܂���B<BR>�f���ݒu�f�B���N�g���̃p�[�~�b�V�����ݒ��ύX���Ă��������B");
		chmod (0606,$tempfile);
		print OUT @new;
		close(OUT);
		# �ꎞ�t�@�C������X�V���Ƀ��O�t�@�C���Ƀ��l�[��
		if ( (-s $tempfile) > 20 ) { rename ($tempfile,$logfile) || &error("Rename Error"); }
	}

	# ���b�N����
	if ($lockkey) { &unlock; }

	# �N�b�L�[���s
	if ($mode ne "admin_repost") {
		if ($no_email == 2 && !$imode) { $in{'email'} =~ s/\@/��/; }
		&set_cookie($in{'name'},$in{'email'},$in{'url'},$in{'pwd'},$in{'icon'},$in{'color'},$in{'aikotoba'});
		if ($no_email == 2 && !$imode) { $in{'email'} =~ s/��/\@/; }
	}

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

		# �֎~���[�h�`�F�b�N
		if ($deny_word) {
			&deny_word($in{'name'});
			&deny_word($in{'comment'});
		}

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
#  ��������
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
<input type=submit value="�f���ɖ߂�">
</form>
</div>
</body>
</html>
EOM
	}else{
		print "$msg<br>\n";
		print "<a href=$script>�f���֖߂�</a>\n";
	}

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
	if ($in_email && $in{'email'} !~ /^[\w\.\-]+\@[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
		&error("�d���[���̓��͓��e������������܂���");
	}
	if (!$in_email && $in{'email'}) {
		if ($in{'email'} !~ /https?\:\/\//i) {
			if ($in{'email'} !~ /^[\w\.\-]+\@[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
				&error("�d���[���̓��͓��e������������܂���");
			}
		}
	}

	if ($iconMode) {
		if (!$imode){
			@ico1 = split(/\s+/, $ico1);
			@ico2 = split(/\s+/, $ico2);
			if ($my_icon) { push(@ico1,$my_gif); }
			if ($in{'icon'} =~ /\D/ || $in{'icon'} < 0 || $in{'icon'} > @ico1) {
				&error("�A�C�R����񂪕s���ł�");
			}
			$in{'icon'} = $ico1[$in{'icon'}];
		}
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

	# �^�C�g���`�F�b�N
	if (!$in{'sub'}) {
		if ($suberror) { &error("�^�C�g�������͂���Ă��܂���"); } else { $in{'sub'} = "����"; } 
	} elsif ($suberror == 2) {
		if ($in{'sub'} !~ /[^0-9]/ || $in{'sub'} =~ /http\:\/\//i) { &error("�^�C�g�����s���ł�"); }
	}
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
</UL>
EOM

	if (-e $spamdata && !$imode) {
	print <<EOM;
<hr>
<UL>
<li>NG���[�h�̈ꊇ�ҏW
<form action="$regist" method="$method">
<input type=hidden name=mode value="spamdata">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value='NG���[�h�̈ꊇ�ҏW'><br>
</form>
</ul>
EOM
	}

	if(-e $spamlogfile && !$imode) {
	print <<EOM;
<UL>
<LI>���e���ۂ��ꂽ���f���e���{���ł��܂��B
<form action="$regist" method="$method">
<input type=hidden name=mode value="spam">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value="���f���e���{��">
</form>
</UL>
EOM
	}

	if (!$imode){
		print <<EOM;
<hr>
<UL>
<LI>������I�����A�L�����`�F�b�N���đ��M�{�^���������ĉ������B
<LI>�e�L�����폜����ƃ��X�L�����ꊇ���č폜����܂��B
</UL>
<form action="$regist" method="$method">
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
	}else{
		#�g�т̏���
	print <<EOM;
�폜�������L�����������đ��M���݂������Ă�������<br>
�e�L�����폜����ƽگ�ޑS�Ă��폜����܂�<br>
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

		# �폜�`�F�b�N�{�b�N�X
		if (!$res) { print "<hr>"; }
		print "<input type=checkbox name=no value=\"$no\">";
		print "[$no] $sub\n";
		print "/$nam\n";
		print "/$com<br>\n";
	}
	close(IN);
	print <<EOM;
<input type=submit value="���M����">
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
			print "<input type=submit value=\"�O��$pastView�g\"></td></form>\n";
		}
		if ($next < $i) {
			print "<td><form action=\"$regist\" method=\"$method\">\n";
			print "<input type=hidden name=page value=\"$next\">\n";
			print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
			print "<input type=hidden name=mode value=\"admin\">\n";
			print "<input type=submit value=\"����$pastView�g\"></td></form>\n";
		}
	}else{
		#�g�т̏���
		if ($back >= 0) {
			print "<form action=\"$regist\" method=\"$method\">\n";
			print "<input type=hidden name=page value=\"$back\">\n";
			print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
			print "<input type=hidden name=mode value=\"admin\">\n";
			print "<input type=submit value=\"�O��$pastView�g\"></form>\n";
		}
		if ($next < $i) {
			print "<form action=\"$regist\" method=\"$method\">\n";
			print "<input type=hidden name=page value=\"$next\">\n";
			print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
			print "<input type=hidden name=mode value=\"admin\">\n";
			print "<input type=submit value=\"����$pastView�g\"></form>\n";
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
#  �������
#-------------------------------------------------
sub enter {
	&header;
	print <<EOM;
<div align="center">
<h4>�p�X���[�h����͂��Ă�������</h4>
<form action="$regist" method="$method">
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
	$times = shift;
	if (!$times) { $ENV{'TZ'} = "JST-9"; $times = time; }

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
	local($msub,$mbody,$email,$ptn);

	# �L���̉��s�E�^�O�𕜌�
	$com  = $in{'comment'};
	$com =~ s/<br>/\n/g;
	$ptn = 'https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+';
	$com =~ s/<a href="$ptn" target="_blank">($ptn)<\/a>/$1/go;
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

	local($salt) = $dec =~ /^\$1\$(.*)\$/ && $1 || substr($dec, 0, 2);
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
	if ($comment_url) {
		$_[0] =~ s/http/ttp/g;
		$_[0] =~ s/([^=^\"]|^)(ttps?\:[\w\.\~\-\/\?\&\+\=\@\;\#\:\%\,]+)/$1<a href=\"h$2\" target=\"_blank\">h$2<\/a>/g;
	} else {
		$_[0] =~ s/([^=^\"]|^)(https?\:[\w\.\~\-\/\?\&\+\=\@\;\#\:\%\,]+)/$1<a href=\"$2\" target=\"_blank\">$2<\/a>/g;
	}
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
<form action="$regist" method="$method">
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
<form action="$regist" method="$method">
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

#-------------------------------------------------
#  �֎~���[�h
#-------------------------------------------------
sub deny_word {
	local($word) = @_;

	local($flg);
	foreach ( split(/,+/, $deny_word) ) {
		if (index($word,$_) >= 0) { $flg=1; last; }
	}
	if ($flg) { &error("�s�K�؂ȓ��e�̂��ߎ󗝂ł��܂���"); }
}

#-------------------------------------------------
#  �X�p���g���I�v�V�����`�F�b�N
#-------------------------------------------------
sub option_check {
	local ($pw,$em,$cm,$ur) = @_;

	# �Ï؃L�[���`�F�b�N
	local($pwdflag) = 0;
	if ($ng_pass && $pw) {
		if ($pw =~ /\s/) { $pwdflag = 1; }
		if ($pw eq reverse($pw)) { $pwdflag = 1; }
	}
	if ($pwdflag) { &error("�Ï؃L�[���s���ł��B"); }

	# ���[���A�h���X���`�F�b�N
	if ($no_email == 1 && $em) { &error("���[���A�h���X�͓��͋֎~�ł��B"); }
	if ($no_email == 2 && $em && $em!~ /^[\w\.\-]+��[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
		&error("�A�b�g�}�[�N �� �͑S�p�œ��͂��ĉ������B"); }

	# URL�̒��ڏ������݂��`�F�b�N
	if ($comment_url) { 
		$urlnum = ($cm =~ s/http/http/ig);
		if ($urlnum) { &error("�t�q�k�͐擪�̂��𔲂��ď�������ŉ������B"); }
	}

	# URL�]���E�Z�kURL���`�F�b�N
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
		if ($shorturlcheck) { &error("URL�̋L�ڂ͋֎~����Ă��܂��B"); }
	}
}

#-------------------------------------------------
#  �X�p���`�F�b�N
#-------------------------------------------------
sub spam_check{
	local ($na,$u2,$bt,$cm,$re,$ur,$em,$sb,$ad,$fc,$cn,$sb2,$sb3,$sb4,$lng,$ua) = @_;
	$spam = 0;

	if ($u2 || $sb2 || $sb3 || $sb4) {
		$spam=1; $reason = "�v���O�������e(��u���E�U)"; }

	if (!$spam) {
		if (!$bt || !$fc || !$ad) {
			$spam=1; $reason = "�v���O�������e(��t�H�[�����e)"; }
	}

	if(!$spam) {
		if($ipcheckmode) {
			local($enadr) = &encode_addr($addr);
			if ($ad ne $enadr) { $spam=1; $reason = "�v���O�������e(IP�s��v)"; }
		} else {
			if ($ad =~ /\@/) { $spam=1; $reason = "�v���O�������e(IP�f�[�^�s��)"; }
		}
	}

	if (!$spam) {
		local($posttime2) = time;
		local($timecheck2) = $posttime2 - $bt;
		if ($timecheck2 < 0) { $timecheck2 = 0 - $timecheck2; }
		if ($mintime && $timecheck2 < $mintime) {
			$spam=1; $reason = "�v���O�������e(���e�܂�$timecheck2�b)"; }
		if (!$cn || !$cookiecheck) {
			if ($maxtime && $timecheck2 > $maxtime) {
				$spam=1; $reason = "�v���O�������e(���e�܂�$timecheck2�b)"; }
		}
	}

	# ���{����`�F�b�N
	if (!$spam) {
		if ($japanese) {
			if ($lng !~ /ja/i && $ua !~ /ja/i) {
				$spam=1; $reason = "�s���u���E�U(����{���)"; }
		}
	}

	# �g�т���̓��e�����O
	if (!$keitaicheck && $imode) { $spam = 0; }

	if(!$spam) {
		if ($em && $em =~ /https?\:\/\//) {
			$spam=1; $reason = "�v���O�������e(email/URL�s��)"; }
	}

	if(!$spam) {
		if ($ur && $ur =~ /^[\w\.\-]+\@[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
			$spam=1; $reason = "�v���O�������e(email/URL�s��)"; }
	}

	if(!$spam) {
		if (length($cm) < length($na)) {
			&error("�R�����g�E���b�Z�[�W���Z�����܂��B"); }
	}

	if(!$spam) {
		if ($na =~ /https?\:\/\//i) {
			$spam=1; $reason = "�v���O�������e(name/comment�s��)"; }
	}

	# �X�p�����e�`�F�b�N(����URL�L�q�Ή�)
	if (!$spam) {
		$urlnum = ($cm =~ s/http/http/ig);
		if ($spamurlnum && ($urlnum >= $spamurlnum)) { $spam=1; $reason = "URL�̏������݂�$urlnum��"; }
	}

	# URL�ȊO�̕��������`�F�b�N
	if(!$spam) {
		if ($characheck) {
			if ($cm =~ /(https?\:\/\/[\w\.\~\-\/\?\&\=\;\#\:\%\+\@\,]+)/ || $ur) {
				local($charamsg) = $cm;
				$charamsg =~ s/(https?\:\/\/[\w\.\~\-\/\?\&\=\;\#\:\%\+\@\,]+)//g;
				$charamsg =~ s/[\s\n\r\t]//g;
				$charamsg =~ s/<br>//ig;
				$msgnum = length($charamsg);
				if ($msgnum < $characheck) {
					 $spam=1; $reason = "�R�����g�̕�������$msgnum�o�C�g�Ə��Ȃ�";
				}
			}
		}
	}

	# �S�p����(���{��)�`�F�b�N
	if(!$spam) {
		if ($asciicheck) {
			if ($cm !~ /(\x82[\x9F-\xF2])|(\x83[\x40-\x96])/) {
				$spam=1; $reason = "�R�����g�ɓ��{��(�Ђ炪��/�J�^�J�i)���Ȃ�";
			}
		}
	}

	if(!$spam) {
		if (-e $spamdata) {
			if ($spamdatacheck || !$re) {
				# �֎~URL�f�[�^�����[�h
				open(SPAM,"$spamdata") || &error("Open Error : $spamdata");
				$SPM = <SPAM>;
				close(SPAM);
				# �֎~URL�̏������݂��`�F�b�N
				foreach (split(/\,/, $SPM)) {
					if(length($_) > 1) {
#fs0x7f-costom
						($cm_ = $cm) =~ s/(\s|�@|\r|\n)//g;
						($na_ = $na) =~ s/(\s|�@|\r|\n)//g;
						($ur_ = $ur) =~ s/(\s|�@|\r|\n)//g;
						($em_ = $em) =~ s/(\s|�@|\r|\n)//g;
						($sb_ = $sb) =~ s/(\s|�@|\r|\n)//g;
						if ($cm_ =~ /\Q$_\E/i) {
							$spam=1; $reason = "���O/�R�����g���ɋ֎~���$_���܂ޓ��e"; last; }
						if (!$spam && $na_ =~ /\Q$_\E/i) {
							$spam=1; $reason = "���O/�R�����g���ɋ֎~���$_���܂ޓ��e"; last; }
						if (!$spam && $ur_ =~ /\Q$_\E/i) {
							$spam=1; $reason = "URL�ɋ֎~���$_���܂ޓ��e"; last; }
						if (!$spam && $ngmail && $em_ =~ /\Q$_\E/i) {
							$spam=1; $reason = "���[���A�h���X�ɋ֎~���$_���܂ޓ��e"; last; }
						if (!$spam && $ngtitle && $sb_ =~ /\Q$_\E/i) {
							$spam=1; $reason = "�^�C�g���ɋ֎~���$_���܂ޓ��e"; last; }
#fs0x7f-costom
#**original
#						if ($cm =~ /\Q$_\E/i) {
#							$spam=1; $reason = "���O/�R�����g���ɋ֎~���$_���܂ޓ��e"; last; }
#						if (!$spam && $na =~ /\Q$_\E/i) {
#							$spam=1; $reason = "���O/�R�����g���ɋ֎~���$_���܂ޓ��e"; last; }
#						if (!$spam && $ur =~ /\Q$_\E/i) {
#							$spam=1; $reason = "URL�ɋ֎~���$_���܂ޓ��e"; last; }
#						if (!$spam && $ngmail && $em =~ /\Q$_\E/i) {
#							$spam=1; $reason = "���[���A�h���X�ɋ֎~���$_���܂ޓ��e"; last; }
#						if (!$spam && $ngtitle && $sb =~ /\Q$_\E/i) {
#							$spam=1; $reason = "�^�C�g���ɋ֎~���$_���܂ޓ��e"; last; }
#**original
					}
				}
			}
		}
	}

	if(!$spam) {
		if ($urlcheck) {
			if ($urlcheck eq 2 || !$re) {
				# URL�̃R�����g�ւ̏d���������݂��`�F�b�N
				if($ur) {
					$ur =~ s/\/$//;
					if ($cm =~ /\Q$ur\E/i) {
						if ($' !~ /(^\/?[\w\?]+?)/)  {
							$spam=1; $reason = "�R�����g����URL���Ɠ���URL���܂ޓ��e";
						}
					}
				}
			}
		}
	}

	return ($spam,$reason);
}

#-------------------------------------------------
#  �X�p�����O�L�^
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
	if($num >= $maxurl) { $log_comment ="���b�Z�[�W����URL����$num�Ƒ������߁A���b�Z�[�W�{���폜"; }
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

	# �Â��X�p�����O���폜
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

	# ���b�N����
	&unlock if ($lockkey);
}

#-------------------------------------------------
#  �X�p�����O�폜
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
#  �v���r���[�`�F�b�N
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
#  �v���r���[���
#-------------------------------------------------
sub previewmode {
	local($timecheck) = shift;
	$time     = time;
	$date     = &get_time($time);
	if ($in{'pwd'} ne "") { $pwd = &encrypt($in{'pwd'}); }
	$reason   = "�v���r���[���[�h�g���b�v($timecheck�b)";

	# �v���r���[���O�̋L�^
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

	# ���{��`�F�b�N
	if ($in{'comment'} =~ /(\x82[\x9F-\xF2])/) {
		$checked0 = "checked"; $checked1 = "";
		$in{'url'} =~ s/\/$//;
		$in{'email'} =~ s/\/$//;
		# URL�d���`�F�b�N
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
�� ���e���m�F���A"���e����"���`�F�b�N���ē��e���ĉ������B<br>
<br>
<table border=1 width='90%' cellspacing=0 cellpadding=10>
<tr><td bgcolor="$tblCol">
<table>
<tr>
  <td><b>�����O</b></td>
  <td>$in{'name'}</td>
</tr>
<tr>
  <td><b>�d���[��</b></td>
  <td>$in{'email'}</td>
</tr>
<tr>
  <td><b>�^�C�g��</b></td>
  <td>$in{'sub'}</td>
</tr>
<tr>
  <td><b>�Q�Ɛ�</b></td>
  <td>$in{'url'}</td>
</tr>
<tr>
  <td><b>���b�Z�[�W</b></td>
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
  <input type=radio name=mode value=$postvalue $checked0>���e����&nbsp;&nbsp;
  <input type=radio name=mode value=regist $checked1><font color=#FF0000>���e����߂�</font>
  </td>
</tr>
<tr>
  <td><div align=right>
   <input type=submit value=" ���s ">
   </form></div></td>
   <td><form><div align=left>
     <input type=button value="�O��ʂɖ߂�" onClick="history.back()">
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
�� ���e���m�F���A���e�����s���ĉ������B
<hr>
���Ȃ܂�: 
<b style='color:#0000FF'>$in{'name'}</b><br>
�薼: 
<b style='color:#0000FF'>$in{'sub'}</b><br>
�d���[��: 
<b style='color:#0000FF'>$in{'email'}</b><br>
�R�����g<br>
<b style='color:#0000FF'>$in{'comment'}</b><br>
<hr>
<input type=radio name=mode value=$postvalue $checked0>���e����
<br>
<input type=radio name=mode value=regist $checked1><font color=#FF0000>���e����߂�</font>
<br>
<input type=submit value=" ���s ">
</form>
<form>
<input type=button value="�O��ʂɖ߂�" onClick="history.back()">
EOM
	}
	exit;
}

#-------------------------------------------------
#  �X�p�����O
#-------------------------------------------------
sub spam {
	# POST����
	if ($postonly && !$post_flag) { &error("�s���ȃA�N�Z�X�ł�"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("�p�X���[�h���Ⴂ�܂�"); }

	&header;
	print <<EOM;
<UL>
<table border=0>
<tr><td><form action="$script">
<input type=submit value="�f���ɖ߂�">
</form>
</td><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" �Ǘ���ʂɖ߂� ">
</form>
</td></tr>
</table></div>
</UL>
<UL><li>���e���ۃ��O<br>
�u�ē��e�����v���N���b�N����ƌ���ăX�p�����e�Ƃ��ċ��ۂ��ꂽ���e�𕜊������邱�Ƃ��ł��܂��B<br>
�K�p�ȓ��e�𕜊����������Ƃ́A�u���e���ۃ��O���폜�v���Ă����ĉ������B
<form action="$regist" method="$method">
<input type=hidden name=mode value="spamclear">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" ���e���ۃ��O���폜���� ">
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
		else { $fcheck{$i} = "�A�N�Z�X�L�^�Ȃ�"; }
		$useragent{$i} = $useragent;
		if ($keychange) {
			if ($url{$i} && $url{$i} =~ /\@/) { ($email{$i},$url{$i})=($url{$i},$email{$i}); }
			elsif ($email{$i} && $email{$i} !~ /\@/) { ($email{$i},$url{$i})=($url{$i},$email{$i}); }
		}
		$i++;
	}
	close(IN);

	# �\�[�g����
	$j=0;
	$x=0;
	$page = $in{'page'};
	foreach (sort { ($date{$b} cmp $date{$a}) } keys(%date)) {
		$j++;
		if ($j < $page + 1) { next; }
		if ($j > $page + $spamlog_page) { next; }

		$useragent = "<small>$useragents</small>";
		print "<P><table border='1'>\n<tr>";
		print "<tr><td>���e����</td><td>$date{$_}</td><td>�^�C�g��</td><td>$sub{$_}</td></tr>",
		"<tr><td>�A�N�Z�X����</td><td>$fcheck{$_}</td><td>���e���ۗ��R</td><td>$reason{$_}</td></tr>",
		"<tr><td>���e�Җ�</td><td>$name{$_}</td><td>URL</td><td>$url{$_}</td></tr>",
		"<tr><td>�z�X�g�A�h���X</td><td>$host{$_}</td><td>�u���E�U</td><td>$useragent{$_}</td></tr>",
		"<tr><td>���[���A�h���X</td><td>$email{$_}</td><td>���e���e</td><td> ";
	print <<EOM;
<form action="$regist" method="$method">
<input type=hidden name=mode value="spammsg">
<input type=hidden name=pass value="$in{'pass'}">
<input type=hidden name=msg value="$msg{$_}">
<input type=submit value="���e���e���{��">
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
<input type=submit value="�ē��e����">
</form></td><td>(��L�̓��e�𕜊������邱�Ƃ��ł��܂�)</td></tr></table>
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
		print "<input type=submit value=\"�O���\"></form></td>\n";
	}
	if ($next < $i) {
		print "<td><form action=\"$regist\" method=\"POST\">\n";
		print "<input type=hidden name=pass value=\"$in{'pass'}\">\n";
		print "<input type=hidden name=mode value=\"$in{'mode'}\">\n";
		print "<input type=hidden name=page value=\"$next\">\n";
		print "<input type=submit value=\"�����\"></form></td>\n";
	}
	print "</tr></table>\n";
	print <<EOM;
<form action="$regist" method="$method">
<input type=hidden name=mode value="spamclear">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" ���e���ۃ��O���폜���� ">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  ���e���ۃ��O������
#-------------------------------------------------
sub spamclear {
	# POST����
	if ($postonly && !$post_flag) { &error("�s���ȃA�N�Z�X�ł�"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("�p�X���[�h���Ⴂ�܂�"); }

	# ���e���ۃ��O�̏�����
	open(OUT,">$spamlogfile");
	chmod (0606,"$spamlogfile");
	print OUT "";
	close(OUT);

	&header();
	print <<EOM;
<div align="center">
<h4>���e���ۃ��O���폜���܂���</h4>
<table border=0>
<tr><td><form action="$script">
<input type=submit value="�f���ɖ߂�">
</form>
</td><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" �Ǘ���ʂɖ߂� ">
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
#  ���e���ۃR�����g
#-------------------------------------------------
sub spammsg {
	# POST����
	if ($postonly && !$post_flag) { &error("�s���ȃA�N�Z�X�ł�"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("�p�X���[�h���Ⴂ�܂�"); }

	# �G�X�P�[�v
	$in{'msg'} =~ s/"/&quot;/g;
	$in{'msg'} =~ s/</&lt;/g;
	$in{'msg'} =~ s/>/&gt;/g;
	# ���s����
	$in{'msg'} =~ s/&lt;br&gt;/<br>/ig;

	&header();
	print <<EOM;
<div align="center">
<h4>�R�����g</h4>
<div align'left'>
<P><table border='1'>
<tr><td>$in{'msg'}</td></tr>
</table><BR>
<table border=0>
<tr><td><form action="$regist" method="$method">
<input type=hidden name=mode value="spam">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" ���e���ۃ��O�{���ɖ߂� ">
</form>
</td><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" �Ǘ���ʂɖ߂� ">
</form>
</td></tr>
</table></div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  NG���[�h�ҏW
#-------------------------------------------------
sub spamdata {
	# POST����
	if ($postonly && !$post_flag) { &error("�s���ȃA�N�Z�X�ł�"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("�p�X���[�h���Ⴂ�܂�"); }

	&header;
	print <<EOM;
<div align="left">
<table border=0>
<tr><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" �Ǘ���ʂɖ߂� ">
</form>
</td></tr>
</table></div>
<BR>
<li>NG���[�h���ꊇ�o�^�ł��܂�(���p�̃J���}�ŋ�؂�)�B<br>
���Ƃ��� <b>http://www.example.com/sample/inde.cgi?mode=test</b> ��<br>
���ۂ������ꍇ��<b> www.example.com </b>��o�^���܂��B
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
<input type=submit value="�X�V����">
</form>
</ul>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  NG���[�h�X�V
#-------------------------------------------------
sub editspam {

	# POST����
	if ($postonly && !$post_flag) { &error("�s���ȃA�N�Z�X�ł�"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("�p�X���[�h���Ⴂ�܂�"); }

	$SPMLST = $in{"SPMLST"};

	# ��f�[�^�E���s�E�󔒂��폜
	$SPMLST =~ s/�C/\,/g;
	$SPMLST =~ s/<br>//ig;
	$SPMLST =~ s/\n//g;
	$SPMLST =~ s/\r//g;
	$SPMLST =~ s/�@//g;
	$SPMLST =~ s/\,{2,}/\,/g;
	$SPMLST =~ s/^\,{1,}//;

	open(OUT,">$spamdata") || &error("Write Error");
	print OUT $SPMLST;
	close(OUT);

	&header;

	print <<EOM;
<div align="center">
<h4>NG���[�h���X�V���܂���</h4>
<BR>
<table border=0>
<tr><td>
<form action="$regist" method="$method">
<input type=hidden name=mode value="admin">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" �Ǘ���ʂɖ߂� ">
</form>
</td></tr>
</table></div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  �Ǘ��ҍē��e���
#-------------------------------------------------
sub admin_repost_form {

	# POST����
	if ($postonly && !$post_flag) { &error("�s���ȃA�N�Z�X�ł�"); }

	if ($in{'pass'} eq "") { &enter; }
	elsif ($in{'pass'} ne $pass) { &error("�p�X���[�h���Ⴂ�܂�"); }

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
<h3>�X�p�����e�Ƃ��ď������ꂽ���L�̓��e�𕜊������܂��B</h3>
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
  <td><b style='color:#FF0000'>���e���ۗ��R&nbsp;:&nbsp;$in{'reason'}</b><br><br></td>
</tr>
<tr>
  <td><b>�����O</b>&nbsp;:&nbsp;
    <input type=hidden name=name value="$in{'name'}" class=f>$in{'name'}</td>
</tr>
<tr>
  <td><b>�d���[��</b>&nbsp;:&nbsp;
    <input type=text name=email size=28 value="$in{'email'}"></td>
</tr>
<tr>
  <td><b>�^�C�g��</b>&nbsp;:&nbsp;
    <input type=hidden name=sub value="$in{'sub'}" class=f>$in{'sub'}
  </td>
</tr>
<tr>
  <td>
    <b>���b�Z�[�W</b><br>
    <textarea cols=56 rows=7 name=comment wrap="soft" class=f>$in{'msg'}</textarea>
  </td>
</tr>
<tr>
  <td><input type=hidden name=$formcheck value="$f_c_d">
  </td>
</tr>
<tr>
  <td><b>�Q�Ɛ�</b>&nbsp;:&nbsp;
  <input type=text name=url size=50 value="$in{'url'}" class=f></td>
</tr>
</table>
<table><tr><td>
<input type=submit value="���e������������">
</form>
</td>
<td><form action="$regist" method="$method">
<input type=hidden name=mode value="spam">
<input type=hidden name=pass value="$in{'pass'}">
<input type=submit value=" ���e���ۃ��O�{���ɖ߂� ">
</form>
</td></tr></table>
</body>
</html>
EOM
	exit;
}

__END__
