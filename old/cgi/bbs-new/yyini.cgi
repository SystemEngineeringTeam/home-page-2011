#��������������������������������������������������������������������
#��  YY-BOARD v5.33 - 2004/09/01
#��  Copyright (c) KentWeb
#��  webmaster@kent-web.com
#��  http://www.kent-web.com/
#��������������������������������������������������������������������
$ver = 'YY-BOARD v5.33';
#��������������������������������������������������������������������
#�� [���ӎ���]
#�� 1. ���̃X�N���v�g�̓t���[�\�t�g�ł��B���̃X�N���v�g���g�p����
#��    �����Ȃ鑹�Q�ɑ΂��č�҂͈�؂̐ӔC�𕉂��܂���B
#�� 2. �ݒu�Ɋւ��鎿��̓T�|�[�g�f���ɂ��肢�������܂��B
#��    ���ڃ��[���ɂ�鎿��͈�؂��󂯂������Ă���܂���B
#�� 3. �Y�t�� home.gif �� L.O.V.E �� mayuRin ����ɂ��摜�ł��B
#��������������������������������������������������������������������
#
# �y�t�@�C���\����z
#
#  public_html (�z�[���f�B���N�g��)
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
# ���ݒ荀��
#-------------------------------------------------

# �^�C�g����
$title = "SET�����o�[BBS";

# �^�C�g�������F
$tCol = "#005170";

# �^�C�g���T�C�Y
$tSize = '26px';

# �{�������t�H���g
$bFace = "MS UI Gothic, Osaka, �l�r �o�S�V�b�N";

# �{�������T�C�Y
$bSize = '15px';

# �ǎ����w�肷��ꍇ�ihttp://����w��j
$backgif = "";

# �w�i�F���w��
$bgcolor = "#4baabe";

# �����F���w��
$text = "#000000";

# �����N�F���w��
$link  = "#0000FF";	# ���K��
$vlink = "#800080";	# �K���
$alink = "#FF0000";	# �K�⒆

# �߂���URL (index.html�Ȃ�)
$homepage = "../index.html";

# �ő�L����
$max = 100;

# �Ǘ��җp�p�X���[�h (�p�����łW�����ȓ�)
$pass = 'admin';

# �A�C�R���摜�̂���f�B���N�g��
# �� �t���p�X�Ȃ� http:// ����L�q����
# �� �Ō�͕K�� / �ŕ���
$imgurl = "./img/";

# �A�C�R�����`
# ���@�㉺�͕K���y�A�ɂ��āA�X�y�[�X�ŋ�؂�
$ico1 = 'bear.gif cat.gif cow.gif dog.gif fox.gif hituji.gif monkey.gif zou.gif mouse.gif panda.gif pig.gif usagi.gif';
$ico2 = '���� �˂� ���� ���� ���� �Ђ� ���� ���� �˂��� �p���_ �Ԃ� ������';

# �Ǘ��Ґ�p�A�C�R���@�\ (0=no 1=yes)
# (�g����) �L�����e���Ɂu�Ǘ��҃A�C�R���v��I�����A�Ï؃L�[��
#         �u�Ǘ��p�X���[�h�v����͂��ĉ������B
$my_icon = 0;

# �Ǘ��Ґ�p�A�C�R���́u�t�@�C�����v���w��
$my_gif  = 'admin.gif';

# �A�C�R�����[�h (0=no 1=yes)
$iconMode = 0;

# �ԐM�����Ɛe�L�����g�b�v�ֈړ� (0=no 1=yes)
$topsort = 1;

# �^�C�g����GIF�摜���g�p���鎞 (http://����L�q)
$t_img = "";
$t_w = 150;	# �摜�̕� (�s�N�Z��)
$t_h = 50;	#   �V  ���� (�s�N�Z��)

# �t�@�C�����b�N�`��
#  �� 0=no 1=symlink�֐� 2=mkdir�֐�
$lockkey = 0;

# ���b�N�t�@�C����
$lockfile = './lock/yybbs.lock';

# �~�j�J�E���^�̐ݒu
#  �� 0=no 1=�e�L�X�g 2=�摜
$counter = 1;

# �~�j�J�E���^�̌���
$mini_fig = 6;

# �e�L�X�g�̂Ƃ��F�~�j�J�E���^�̐F
$cntCol = "#145170";

# �摜�̂Ƃ��F�摜�f�B���N�g�����w��
#  �� �Ō�͕K�� / �ŕ���
$gif_path = "./img/";
$mini_w = 8;		# �摜�̉��T�C�Y
$mini_h = 12;		# �摜�̏c�T�C�Y

# �J�E���^�t�@�C��
$cntfile = './count.dat';

# �{�̃t�@�C��URL
$script = './yybbs.cgi';

# �X�V�t�@�C��URL
$regist = './yyregi.cgi';

# ���O�t�@�C��
$logfile = './yylog.cgi';

# ���[���A�h���X�̓��͕K�{ (0=no 1=yes)
$in_email = 0;

# �L�� [�^�C�g��] ���̒��� (�S�p�������Z)
$sub_len = 12;

# �L���� [�^�C�g��] ���̐F
$subCol = "#4c5970";

# �L���\�����̉��n�̐F
$tblCol = "#fafaff";

# ���e�t�H�[���y�у{�^���̕����F
$formCol1 = "#F7FAFD";	# ���n�̐F
$formCol2 = "#000000";	# �����̐F

# �ƃA�C�R���̎g�p (0=no 1=yes)
$home_icon = 1;
$home_gif = "home.gif";	# �ƃA�C�R���̃t�@�C����
$home_wid = 16;		# �摜�̉��T�C�Y
$home_hei = 20;		#   �V  �c�T�C�Y

# �C���[�W�Q�Ɖ�ʂ̕\���`��
#  1 : JavaScript�ŕ\��
#  2 : HTML�ŕ\��
$ImageView = 1;

# �C���[�W�Q�Ɖ�ʂ̃T�C�Y (JavaScript�̏ꍇ)
$img_w = 550;	# ����
$img_h = 450;	# ����

# �P�y�[�W������̋L���\���� (�e�L��)
$pageView = 20;

# ���e������ƃ��[���ʒm���� (sendmail�K�{)
#  0 : �ʒm���Ȃ�
#  1 : �ʒm���邪�A�����̓��e�L���͒ʒm���Ȃ��B
#  2 : ���ׂĒʒm����B
$mailing = 0;

# ���[���A�h���X(���[���ʒm���鎞)
$mailto = 'xxx@xxx.xxx';

# sendmail�p�X�i���[���ʒm���鎞�j
$sendmail = '/usr/lib/sendmail';

# �����F�̐ݒ�
#  ���@�X�y�[�X�ŋ�؂�
$color = '#000000 #595959 #387a11 #05007f #C40026 #f200f2 #FF8040 #C100C1';

# URL�̎��������N (0=no 1=yes)
$autolink = 1;

# �^�O�L���}���I�v�V����
#  �� <!-- �㕔 --> <!-- ���� --> �̑���Ɂu�L���^�O�v��}��
#  �� �L���^�O�ȊO�ɁAMIDI�^�O �� LimeCounter���̃^�O�ɂ��g�p�\
$banner1 = '<!-- �㕔 -->';	# �f���㕔�ɑ}��
$banner2 = '<!-- ���� -->';	# �f�������ɑ}��

# �z�X�g�擾���@
# 0 : gethostbyaddr�֐����g��Ȃ�
# 1 : gethostbyaddr�֐����g��
$gethostbyaddr = 0;

# �A�N�Z�X�����i���p�X�y�[�X�ŋ�؂�j
#  �� ���ۂ���z�X�g������IP�A�h���X���L�q�i�A�X�^���X�N�j
#  �� �L�q�� $deny = '*.anonymizer.com 211.154.120.*';
$denyHost = '';

# �L���̍X�V�� method=POST ���肷��ꍇ�i�Z�L�����e�B�΍�j
#  �� 0=no 1=yes
$postonly = 1;

# ���T�C�g���瓊�e�r�����Ɏw�肷��ꍇ�i�Z�L�����e�B�΍�j
#  �� �f����URL��http://���珑��
$baseUrl = '';

# ���e�����i�Z�L�����e�B�΍�j
#  0 : ���Ȃ�
#  1 : ����IP�A�h���X����̓��e�Ԋu�𐧌�����
#  2 : �S�Ă̓��e�Ԋu�𐧌�����
$regCtl = 1;

# �������e�Ԋu�i�b���j
#  �� $regCtl �ł̓��e�Ԋu
$wait = 8;

# ���e��̏���
#  �� �f�����g��URL���L�q���Ă����ƁA���e�ナ���[�h���܂�
#  �� �u���E�U���ēǂݍ��݂��Ă���d���e����Ȃ��[�u�B
#  �� Location�w�b�_�̎g�p�\�ȃT�[�o�̂�
$location = '';

#---(�ȉ��́u�ߋ����O�v�@�\���g�p����ꍇ�̐ݒ�ł�)---#
#
# �ߋ����O���� (0=no 1=yes)
$pastkey = 0;

# �ߋ����O�pNO�t�@�C��
$nofile = './pastno.dat';

# �ߋ����O�̃f�B���N�g��
#  �� �t���p�X�Ȃ� / ����L�q�ihttp://����ł͂Ȃ��j
#  �� �Ō�͕K�� / �ŕ���
$pastdir = './past/';

# �ߋ����O�P�t�@�C���̍s��
#  �� ���̍s���𒴂���Ǝ��y�[�W�������������܂�
$pastmax = 650;

# �P�y�[�W������̋L���\���� (�e�L��)
$pastView = 10;

#-------------------------------------------------
# ���ݒ芮��
#-------------------------------------------------

#-------------------------------------------------
#  ���e���
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
  <td><b>�����O</b></td>
  <td><input type=text name=name size=28 value="$nam" class=f></td>
</tr>
<tr>
  <td><b>�d���[��</b></td>
  <td><input type=text name=email size=28 value="$eml" class=f></td>
</tr>
<tr>
  <td><b>�^�C�g��</b></td>
  <td>
    	<input type=text name=sub size=36 value="$sub" class=f>
	<input type=submit value="���e����"><input type=reset value="���Z�b�g">
  </td>
</tr>
<tr>
  <td colspan=2>
    <b>���b�Z�[�W</b><br>
    <textarea cols=56 rows=7 name=comment wrap="soft" class=f>$com</textarea>
  </td>
</tr>
EOM

	# �Ǘ��҃A�C�R����z��ɕt��
	@ico1 = split(/\s+/, $ico1);
	@ico2 = split(/\s+/, $ico2);
	if ($my_icon) {
		push(@ico1,$my_gif);
		push(@ico2,"�Ǘ��җp");
	}
	if ($iconMode) {
		print "<tr><td><b>�C���[�W</b></td>
		<td><select name=icon class=f>\n";
		foreach(0 .. $#ico1) {
			if ($ico eq $ico1[$_]) {
				print "<option value=\"$_\" selected>$ico2[$_]\n";
			} else {
				print "<option value=\"$_\">$ico2[$_]\n";
			}
		}
		print "</select> &nbsp;\n";

		# �C���[�W�Q�Ƃ̃����N
		if ($ImageView == 1) {
			print "[<a href=\"javascript:ImageUp()\">�C���[�W�Q��</a>]";
		} else {
			print "[<a href=\"$script?mode=image\" target=\"_blank\">�C���[�W�Q��</a>]";
		}
		print "</td></tr>\n";
	}

	if ($pwd ne "??") {
		print "<tr><td><b>�Ï؃L�[</b></td>";
		print "<td><input type=password name=pwd size=8 maxlength=8 value=\"$pwd\" class=f>\n";
		print "(�p������8�����ȓ�)</td></tr>\n";
	}
	print "<tr><td><b>�����F</b></td><td>";

	# �F���
	@col = split(/\s+/, $color);
	if ($col eq "") { $col = 0; }
	foreach (0 .. $#col) {
		if ($col eq $col[$_] || $col eq $_) {
			print "<input type=radio name=color value=\"$_\" checked>";
			print "<font color=\"$col[$_]\">��</font>\n";
		} else {
			print "<input type=radio name=color value=\"$_\">";
			print "<font color=\"$col[$_]\">��</font>\n";
		}
	}

	print <<EOM;
</td></tr></table>
EOM
}

#-------------------------------------------------
#  �A�N�Z�X����
#-------------------------------------------------
sub axsCheck {
	# IP,�z�X�g�擾
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
	if ($flag) { &error("�A�N�Z�X��������Ă��܂���"); }
}

#-------------------------------------------------
#  �f�R�[�h����
#-------------------------------------------------
sub decode {
	local($buf,$key,$val);
	undef(%in);

	if ($ENV{'REQUEST_METHOD'} eq "POST") {
		$post_flag=1;
		if ($ENV{'CONTENT_LENGTH'} > 51200) { &error("���e�ʂ��傫�����܂�"); }
		read(STDIN, $buf, $ENV{'CONTENT_LENGTH'});
	} else {
		$post_flag=0;
		$buf = $ENV{'QUERY_STRING'};
	}

	foreach ( split(/&/, $buf) ) {
		($key, $val) = split(/=/);
		$val =~ tr/+/ /;
		$val =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("H2", $1)/eg;

		# S-JIS�R�[�h�ϊ�
		&jcode'convert(*val, "sjis", "", "z");

		# �G�X�P�[�v
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
	if ($in{'sub'} eq "") { $in{'sub'} = "����"; }
	$page = $in{'page'};
	$page =~ s/\D//g;
	if ($page < 0) { $page = 0; }
	$mode = $in{'mode'};

	$lockflag=0;
	$headflag=0;
}

#-------------------------------------------------
#  �G���[����
#-------------------------------------------------
sub error {
	# ���b�N���ł���Ή���
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
<input type=button value="�O��ʂɖ߂�" onClick="history.back()">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  HTML�w�b�_
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
	# JavaScript�w�b�_
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
#  ���b�N����
#-------------------------------------------------
sub lock {
	# ���g���C��
	local($retry)=5;

	# �Â����b�N�͍폜����
	if (-e $lockfile) {
		local($mtime) = (stat($lockfile))[9];
		if ($mtime < time - 30) { &unlock; }
	}

	# symlink�֐������b�N
	if ($lockkey == 1) {
		while (!symlink(".", $lockfile)) {
			if (--$retry <= 0) { &error('LOCK is BUSY'); }
			sleep(1);
		}

	# mkdir�֐������b�N
	} elsif ($lockkey == 2) {
		while (!mkdir($lockfile, 0755)) {
			if (--$retry <= 0) { &error('LOCK is BUSY'); }
			sleep(1);
		}
	}
	$lockflag=1;
}

#-------------------------------------------------
#  ���b�N����
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
#  �N�b�L�[���s
#-------------------------------------------------
sub set_cookie {
	local(@cook) = @_;
	local($gmt, $cook, @t, @m, @w);

	@t = gmtime(time + 60*24*60*60);
	@m = ('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
	@w = ('Sun','Mon','Tue','Wed','Thu','Fri','Sat');

	# ���ەW�������`
	$gmt = sprintf("%s, %02d-%s-%04d %02d:%02d:%02d GMT",
			$w[$t[6]], $t[3], $m[$t[4]], $t[5]+1900, $t[2], $t[1], $t[0]);

	# �ۑ��f�[�^��URL�G���R�[�h
	foreach (@cook) {
		s/(\W)/sprintf("%%%02X", unpack("C", $1))/eg;
		$cook .= "$_<>";
	}

	# �i�[
	print "Set-Cookie: YY_BOARD=$cook; expires=$gmt\n";
}

#-------------------------------------------------
#  �N�b�L�[�擾
#-------------------------------------------------
sub get_cookie {
	local($key, $val, *cook);

	# �N�b�L�[�擾
	$cook = $ENV{'HTTP_COOKIE'};

	# �Y��ID�����o��
	foreach ( split(/;/, $cook) ) {
		($key, $val) = split(/=/);
		$key =~ s/\s//g;
		$cook{$key} = $val;
	}

	# �f�[�^��URL�f�R�[�h���ĕ���
	@cook=();
	foreach ( split(/<>/, $cook{'YY_BOARD'}) ) {
		s/%([0-9A-Fa-f][0-9A-Fa-f])/pack("H2", $1)/eg;

		push(@cook,$_);
	}
	return (@cook);
}

#-------------------------------------------------
#  �ړ��{�^��
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
		# ���y�[�W
		if ($page == $y) {

			print "| <b style='color:red' class=n>$x</b>\n";

		# �ؑփy�[�W
		} elsif ($x >= $start && $x <= $end) {

			print "| <a href=\"$link$y&bl=$in{'bl'}\" class=n>$x</a>\n";

		# �O�u���b�N
		} elsif ($x == $start-1) {

			$bk_bl = $in{'bl'}-1;
			print "| <a href=\"$link$y&bl=$bk_bl\">��</a>\n";

		# ���u���b�N
		} elsif ($x == $end+1) {

			$fw_bl = $in{'bl'}+1;
			print "| <a href=\"$link$y&bl=$fw_bl\">��</a>\n";

		}

		$x++;
		$y += $view;
		$i -= $view;
	}

	print "|\n";
}

#-------------------------------------------------
#  ��������
#-------------------------------------------------
sub search {
	local($file,$word,$view,$cond) = @_;
	local($i,$f,$top,$wd,$next,$back,@wd);

	# �L�[���[�h��z��
	$word =~ s/\x81\x40/ /g;
	@wd = split(/\s+/, $word);

	# �t�@�C���W�J
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

		# �q�b�g�����ꍇ
		if ($f) {
			$i++;
			next if ($i < $page + 1);
			next if ($i > $page + $view);

			($no,$reno,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);
			if ($eml) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }
			if ($url) { $url = "&lt;<a href=\"$url\" target=\"_blank\">Home</a>&gt;"; }
			# ���ʂ�\��
			print "<dt><hr>[<b>$no</b>] <b style=\"color:$subCol\">$sub</b> ";
			print "���e�ҁF<b>$nam</b> ���e���F$dat $url<br><br>\n";
			print "<dd style=\"color:$col\">$com\n";
		}
	}
	close(IN);

	print <<EOM;
<dt><hr>
�������ʁF<b>$i</b>��
</dl>
EOM
	$next = $page + $view;
	$back = $page - $view;
	return ($i, $next, $back);
}

#-------------------------------------------------
#  URL�G���R�[�h
#-------------------------------------------------
sub url_enc {
	local($_) = @_;

	s/(\W)/'%' . unpack('H2', $1)/eg;
	s/\s/+/g;
	$_;
}


1;

__END__

