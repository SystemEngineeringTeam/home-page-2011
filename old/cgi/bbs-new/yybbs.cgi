#!/usr/bin/perl
#��������������������������������������������������������������������
#��  YY-BOARD - yybbs.cgi - 2004/09/01
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
if ($mode eq "howto") { &howto; }
elsif ($mode eq "find") { &find; }
elsif ($mode eq "res") { &resForm; }
elsif ($mode eq "image") { &image; }
elsif ($mode eq "check") { &check; }
&logView;

#-------------------------------------------------
#  �L���\����
#-------------------------------------------------
sub logView {
	local($next,$back,$i,$flag);

	# �N�b�L�[�擾
	local($cnam,$ceml,$curl,$cpwd,$cico,$ccol) = &get_cookie;
	$curl ||= 'http://';

	# �w�b�_���o��
	if ($ImageView == 1) { &header('ImageUp'); }
	else { &header; }

	# �J�E���^����
	if ($counter) { &counter; }

	# �^�C�g����
	print "<div align=\"center\">\n";
	if ($banner1 ne "<!-- �㕔 -->") { print "$banner1<p>\n"; }
	if ($t_img eq '') {
		print "<b style='color:$tCol; font-size:$tSize;'>$title</b>\n";
	} else {
		print "<img src=\"$t_img\" width=\"$t_w\" height=\"$t_h\" alt=\"$title\">\n";
	}

	print <<EOM;
<hr width="90%">
[<a href="../../index.html" target="_top"><B>�g�b�v�ɖ߂�</B></a>]
[<a href="$script?mode=howto"><B>���ӎ���</B></a>]
[<a href="$script?mode=find"><B>���[�h����</B></a>]
EOM

	# �ߋ����O�̃����N����\��
	if ($pastkey) {	print "[<a href=\"$regist?mode=past\">�ߋ����O</a>]\n"; }

	print <<EOM;
[<a href="$regist?mode=admin"><B>�Ǘ��p</B></a>]
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

	# �L����W�J
	$i=0;
	$flag=0;
	open(IN,"$logfile") || &error("Open Error: $logfile");
	$top = <IN>;
	while (<IN>) {
		($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);

		if ($re eq "") { $i++; }
		if ($i < $page + 1) { next; }
		if ($i > $page + $pageView) { next; }

		# �薼�̒���
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

		print "<td valign=top nowrap><font color=\"$subCol\"><b>$sub</b></font>�@";

		if (!$re) { print "���e�ҁF<b>$nam</b> ���e���F$dat "; }
		else { print "<b>$nam</b> - $dat "; }

		print "<font color=\"$subCol\">No\.$no</font></td>";
		print "<td valign=top nowrap> &nbsp; $url </td><td valign=top>\n";

		if (!$re) {
			print "<form action=\"$script\">\n";
			print "<input type=hidden name=mode value=res>\n";
			print "<input type=hidden name=no value=\"$no\">\n";
			print "<input type=submit value='�ԐM'></td></form>\n";
		} else {
			print "<br></td>\n";
		}

		print "</tr></table><table border=0 cellpadding=5><tr>\n";
		if ($re) { print "<td width=32><br></td>\n"; }

		# �A�C�R�����[�h
		if ($iconMode) { print "<td><img src=\"$imgurl$ico\" alt=\"$ico\"></td>"; }

		print "<td><font color=\"$col\">$com</font></td></tr></table>\n";
	}
	close(IN);

	print "</TD></TR></TABLE>\n";

	# �y�[�W�ړ��{�^���\��
	if ($page - $pageView >= 0 || $page + $pageView < $i) {
		print "<p><table width=\"90%\"><tr><td>\n";
		&mvbtn("$script?page=", $i, $pageView);
		print "</td></tr></table>\n";
	}

	# ���쌠�\���i�폜�s�j: �A���AMakiMaki����̉摜���g�p���Ȃ��ꍇ�Ɍ���A
	# MakiMaki����̃����N���O�����Ƃ͉\�ł��B
	print <<EOM;
<form action="$regist" method="POST">
<select name=mode class=f>
<option value="edit">�C��
<option value="dele">�폜
</select>
<span class=n>
NO:<input type=text name=no size=3 class=f>
PASS:<input type=password name=pwd size=6 maxlength=8 class=f>
</span>
<input type=submit value="���M" class=f></form>
$banner2
<p>
<!-- $ver -->
<span style="font-size:10px; font-family:Verdana,Helvetica,Arial;">
- <a href="http://www.aitech.ac.jp/" target="_top">���m�H�Ƒ�w</a> & 
<a href="../../index.html" target="_top">�V�X�e���H�w������</a> -
</span>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  �ԐM�t�H�[��
#-------------------------------------------------
sub resForm {
	local($f,$no,$reno,$dat,$nam,$eml,$sub,$com,$url);

	# �N�b�L�[���擾
	local($cnam,$ceml,$curl,$cpwd,$cico,$ccol) = &get_cookie;
	if (!$curl) { $curl = 'http://'; }

	# ���O��ǂݍ���
	$f=0;
	open(IN,"$logfile") || &error("Open Error: $logfile");
	$top = <IN>;

	# �w�b�_���o��
	if ($ImageView == 1) { &header('ImageUp'); }
	else { &header; }

	# �֘A�L���o��
	print <<EOM;
<form>
<input type="button" value="�O��ʂɖ߂�" onClick="history.back()">
</form>
���ȉ��͋L��NO. <B>$in{'no'}</B> �Ɋւ���<a href='#RES'>�ԐM�t�H�[��</a>�ł��B
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
			print "���e�ҁF<b>$nam</b> ���e���F$dat $url ";
			print "<font color=\"$subCol\">No\.$no</font><br>";
			print "<blockquote>$com</blockquote><hr>\n";
		}
	}
	close(IN);
	if ($f) { &error("�s���ȕԐM�v���ł�"); }

	# �^�C�g����
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
#  ���ӎ���
#-------------------------------------------------
sub howto {
	&header;
	print <<"EOM";
<div align="center">
<table width="90%" border=1 cellpadding=10>
<tr><td bgcolor="$tblCol">
<h3>���ӎ���</h3>
<ol>
<li>���̌f����<b>�N�b�L�[�Ή�</b>�ł��B1�x�L���𓊍e���������ƁA�����O�A�d���[���A�Q�Ɛ�A�Ï؃L�[�̏���2��ڈȍ~�͎������͂���܂��B�i���������p�҂̃u���E�U���N�b�L�[�Ή��̏ꍇ�j
<li>���e���e�ɂ́A<b>�^�O�͈�؎g�p�ł��܂���B</b>
<li>�L���𓊍e�����ł̕K�{���͍��ڂ�<b>�u�����O�v</b>��<b>�u���b�Z�[�W�v</b>�ł��B�d���[���A�Q�Ɛ�A�薼�A�Ï؃L�[�͔C�ӂł��B
<li>�L���ɂ́A<b>���p�J�i�͈�؎g�p���Ȃ��ŉ������B</b>���������̌����ƂȂ�܂��B
<li>�L���̓��e����<b>�u�Ï؃L�[�v</b>�ɔC�ӂ̃p�X���[�h�i�p������8�����ȓ��j�����Ă����ƁA���̋L���͎���<b>�Ï؃L�[</b>�ɂ���ďC���y�э폜���邱�Ƃ��ł��܂��B
<li>�L���̕ێ�������<b>�ő�$max��</b>�ł��B����𒴂���ƌÂ����Ɏ����폜����܂��B
<li>�����̋L����<b>�u�ԐM�v</b>�����邱�Ƃ��ł��܂��B�e�L���̏㕔�ɂ���<b>�u�ԐM�v</b>�{�^���������ƕԐM�p�t�H�[��������܂��B
<li>�ߋ��̓��e�L������<b>�u�L�[���[�h�v�ɂ���ĊȈՌ������ł��܂��B</b>�g�b�v���j���[��<a href="$script?mode=find">�u���[�h�����v</a>�̃����N���N���b�N����ƌ������[�h�ƂȂ�܂��B
<li>�Ǘ��҂��������s���v�Ɣ��f����L���⑼�l���排�������L���͗\\���Ȃ��폜���邱�Ƃ�����܂��B
</ol>
</td></tr></table>
<p>
<form>
<input type=button value="�f���ɖ߂�" onClick="history.back()">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  ���[�h����
#-------------------------------------------------
sub find {
	&header;
	print <<EOM;
<form action="$script">
<input type=submit value="�f���ɖ߂�"></form>
<ul>
<li>�L�[���[�h����͂��A�u�����v�u�\\���v��I�����Č����{�^���������ĉ������B
<li>�L�[���[�h�̓X�y�[�X�ŋ�؂��ĕ����w�肷�邱�Ƃ��ł��܂��B
<p>
<form action="$script" method="POST">
<input type=hidden name=mode value="find">
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

	if (!$in{'view'}) { $in{'view'} = 10; }
	print "</select> �\\�� <select name=view class=f>\n";
	foreach (10,15,20,25) {
		if ($in{'view'} == $_) {
			print "<option value=\"$_\" selected>$_��\n";
		} else {
			print "<option value=\"$_\">$_��\n";
		}
	}

	print <<EOM;
</select>
<input type=submit value="����">
</form>
</ul>
EOM

	# �������s
	if ($in{'word'} ne "") {
		($i,$next,$back) = &search($logfile,$in{'word'},$in{'view'},$in{'cond'});

		$enwd = &url_enc($in{'word'});
		if ($back >= 0) {
			print "[<a href=\"$script?mode=find&page=$back&word=$enwd&view=$in{'view'}&cond=$in{'cond'}\">�O��$in{'view'}��</a>]\n";
		}
		if ($next < $i) {
			print "[<a href=\"$script?mode=find&page=$next&word=$enwd&view=$in{'view'}&cond=$in{'cond'}\">����$in{'view'}��</a>]\n";
		}
	}

	print "</body></html>\n";
	exit;
}

#-------------------------------------------------
#  �J�E���^����
#-------------------------------------------------
sub counter {
	local($count,$cntup,@count);

	# �{�����̂݃J�E���g�A�b�v
	if ($mode eq '') { $cntup=1; } else { $cntup=0; }

	# �J�E���g�t�@�C����ǂ݂���
	open(IN,"$cntfile") || &error("Open Error: $cntfile");
	eval "flock(IN, 1);";
	$count = <IN>;
	close(IN);

	# IP�`�F�b�N�ƃ��O�j���`�F�b�N
	local($cnt, $ip) = split(/:/, $count);
	if ($addr eq $ip || $cnt eq "") { $cntup=0; }

	# �J�E���g�A�b�v
	if ($cntup) {
		$cnt++;
		open(OUT,"+< $cntfile") || &error("Write Error: $cntfile");
		eval "flock(OUT, 2);";
		truncate(OUT, 0);
		seek(OUT, 0, 0);
		print OUT "$cnt\:$addr";
		close(OUT);
	}

	# ��������
	while(length($cnt) < $mini_fig) { $cnt = '0' . $cnt; }
	@count = split(//, $cnt);

	# GIF�J�E���^�\��
	if ($counter == 2) {
		foreach (0 .. $#count) {
			print "<img src=\"$gif_path$count[$_]\.gif\" alt=\"$count[$_]\" width=\"$mini_w\" height=\"$mini_h\">";
		}
	# �e�L�X�g�J�E���^�\��
	} else {
		print "<font color=\"$cntCol\" face=\"Verdana,Helvetica,Arial\">$cnt</font><br>\n";
	}
}

#-------------------------------------------------
#  �摜�C���[�W�\��
#-------------------------------------------------
sub image {
	local($i,$j,$stop);

	&header;
	print <<EOM;
<div align="center">
<h4>�摜�C���[�W</h4>
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
<input type=button value="�E�B���h�E�����" onClick="top.close();">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  �`�F�b�N���[�h
#-------------------------------------------------
sub check {
	&header;
	print <<EOM;
<h2>Check Mode</h2>
<ul>
EOM

	# ���O�p�X
	if (-e $logfile) { print "<li>���O�t�@�C���̃p�X�FOK\n"; }
	else { print "<li>���O�t�@�C���̃p�X�FNG �� $logfile\n"; }

	# ���O�p�[�~�b�V����
	if (-r $logfile && -w $logfile) { print "<li>���O�t�@�C���̃p�[�~�b�V�����FOK\n"; }
	else { print "<li>���O�t�@�C���̃p�[�~�b�V�����FNG\n"; }

	# �J�E���^���O
	print "<li>�J�E���^�F";
	if ($counter) {
		print "�ݒ肠��\n";
		if (-e $cntfile) { print "<li>�J�E���^���O�t�@�C���̃p�X�FOK\n"; }
		else { print "<li>�J�E���^���O�t�@�C���̃p�X�FNG �� $cntfile\n"; }
	}
	else { print "�ݒ�Ȃ�\n"; }

	# ���b�N�f�B���N�g��
	print "<li>���b�N�`���F";
	if ($lockkey == 0) { print "���b�N�ݒ�Ȃ�\n"; }
	else {
		if ($lockkey == 1) { print "symlink\n"; }
		else { print "mkdir\n"; }

		($lockdir) = $lockfile =~ /(.*)[\\\/].*$/;
		print "<li>���b�N�f�B���N�g���F$lockdir\n";

		if (-d $lockdir) { print "<li>���b�N�f�B���N�g���̃p�X�FOK\n"; }
		else { print "<li>���b�N�f�B���N�g���̃p�X�FNG �� $lockdir\n"; }

		if (-r $lockdir && -w $lockdir && -x $lockdir) {
			print "<li>���b�N�f�B���N�g���̃p�[�~�b�V�����FOK\n";
		} else {
			print "<li>���b�N�f�B���N�g���̃p�[�~�b�V�����FNG �� $lockdir\n";
		}
	}

	# �ߋ����O
	print "<li>�ߋ����O�F";
	if ($pastkey == 0) { print "�ݒ�Ȃ�\n"; }
	else {
		print "�ݒ肠��\n";

		# NO�t�@�C��
		if (-e $nofile) { print "<li>NO�t�@�C���p�X�FOK\n"; }
		else { print "<li>NO�t�@�C���̃p�X�FNG �� $nofile\n"; }
		if (-r $nofile && -w $nofile) { print "<li>NO�t�@�C���p�[�~�b�V�����FOK\n"; }
		else { print "<li>NO�t�@�C���p�[�~�b�V�����FNG �� $nofile\n"; }

		# �f�B���N�g��
		if (-d $pastdir) { print "<li>�ߋ����O�f�B���N�g���p�X�FOK\n"; }
		else { print "<li>�ߋ����O�f�B���N�g���̃p�X�FNG �� $pastdir\n"; }
		if (-r $pastdir && -w $pastdir && -x $pastdir) {
			print "<li>�ߋ����O�f�B���N�g���p�[�~�b�V�����FOK\n";
		} else {
			print "<li>�ߋ����O�f�B���N�g���p�[�~�b�V�����FNG �� $pastdir\n";
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

