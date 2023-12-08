#!/usr/local/bin/perl

#��������������������������������������������������������������������
#�� YY-BOARD
#�� yybbs.cgi - 2004/09/01
#�� Copyright (c) KentWeb
#�� webmaster@kent-web.com
#�� http://www.kent-web.com/
#��
#�� YY-BOARD v5.5�p�g�ѓd�b�Ή��X�N���v�g
#�� 2005/1/4�@����H�@http://www.url-battle.com/cgi/
#��
#�� Modified by isso. August, 2006
#�� http://swanbay-web.hp.infoseek.co.jp/index.html
#��������������������������������������������������������������������

# �O���t�@�C���捞
require './jcode.pl';
#require './keitai.cgi';
require './yyini.cgi';

if($writevalue eq $postvalue) {
	&error("\$writevalue��\$postvalue�̕����͓����ɂ��Ȃ��ł�������"); }

# ���C������
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
#  �L���\����
#-------------------------------------------------
sub logView {
	local($next,$back,$i,$flag);

	# �N�b�L�[�擾
	local($cnam,$ceml,$curl,$cpwd,$cico,$ccol,$caikotoba) = &get_cookie;
#	$curl ||= 'http://';

	# �w�b�_���o��
	if ($ImageView == 1) { &header('ImageUp'); }
	else { &header; }

	# �J�E���^����
	if ($counter) { &counter; }

	if (!$imode){

	# �^�C�g����
	print "<div align=\"center\">\n";
	if ($banner1 ne "<!-- �㕔 -->") { print "$banner1<p>\n"; }
	if ($t_img eq '') {
		print "<b style='color:$tCol; font-size:$tSize;'>$title</b>\n";
	} else {
		print "<img src=\"$t_img\" width=\"$t_w\" height=\"$t_h\" alt=\"$title\">\n";
	}

	local($access) = &encode_bbsmode();
	if ( (-s $spamlogfile) > $spamlog_maxfile ) {
		print "<br>\n<br>\n<b style='color:#FF0000'>���e���ۃ��O(�X�p�����O)�̃t�@�C���T�C�Y���傫���Ȃ�܂����B<br>",
		"���}�A�Ǘ����[�h���瓊�e���ۃ��O���폜���ĉ������B</b><br>\n<br>\n";
	}

	print "$imode_msg\n";

	print <<EOM;
<hr width="90%">
[<a href="$homepage" target="_top">�g�b�v�ɖ߂�</a>]
[<a href="$script?mode=howto">���ӎ���</a>]
[<a href="$script?mode=find">���[�h����</a>]
EOM

	# �ߋ����O�̃����N����\��
	if ($pastkey) {	print "[<a href=\"$regist?mode=past\">�ߋ����O</a>]\n"; }
	# �f���A�h���X���[�����M�@�\��\��
	if ($send_mail) {	print "[<a href=\"$script?mode=mobile_mail\">�g�тɌf���A�h���X�𑗐M</a>]\n"; }

	print <<EOM;
[<a href="$regist?mode=admin">�Ǘ��p</a>]
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
		print "<a href=\"$script?mode=imode\">��</a>";

		if (!$preview){
			print "/<a href=\"$script?mode=preview\">�ꗗ</a>";
		}

		if ($ihomepage eq ""){
		}else{
			print "/<a href=\"$ihomepage\">��</a>";
		}
		if ($newok){
			if ($preview && !$imode_out){
				print "/<a href=\"$script?mode=sort\">�V</a>/<a href=\"$script\">�W</a>";
			}elsif(!$sortnew){
				print "/<a href=\"$script?mode=sort\">�V</a>";
			}else{
				print "/<a href=\"$script\">�W</a>";
			}
		}

		if ($imode_del){
			print "/<a href=\"$script?mode=idel\">��</a>";
		}
		if ($imode_admin){
			print "/<a href=\"$script?mode=iadmin\">��</a>";
		}

	}

	#�V�����\�[�g���[�`��
	if ($sortnew) {
		# �L����W�J
		open(IN,"$logfile") || &error("Open Error : $logfile");
			@logdata = <IN>;
		close(IN);
		$temp = shift(@logdata);

		#���eNo�Ń\�[�g
		@tmp = map {(split /,/)[0]} @logdata;
		@logdata = @logdata[sort {$tmp[$b] <=> $tmp[$a]} 0 .. $#tmp];
		foreach $log (@logdata) {
			local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/,$log);

			$i++;
			if ($i < $page + 1) { next; }
			if ($i > $page + $pageView) { next; }

			#imode�p
			if ($eml && $mailview) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }

			#���ԕϊ�
			&time_change;

			print "<hr>[$no]�y$sub�z<br>\n";

			if (!$re) {
				print "TO:$nam<br>$dat<br>\n";
				if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}
				print "[<a href=\"$script?mode=imode&re=$no\">�ԐM</a>] &nbsp; <br>";
}
			else {
				 print "��TO:$nam<br>$dat<br>\n";
				if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}
				print "[<a href=\"$script?mode=imode&re=$re\">�ԐM</a>] &nbsp; <br>";
			}

			print "$com\n";

		}
	} elsif ($msgview){

		# �L����W�J
		open(IN,"$logfile") || &error("Open Error : $logfile");
		$top = <IN>;

		while (<IN>) {
		local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);
		if ($in{'no'} eq "$no" || $in{'no'} eq "$re") {
				#���ԕϊ�
				&time_change;
				if ($eml && $mailview) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }

				if (!$re) {
					print "<hr size=2>[$no]�y$sub�z<br>\n";

					print "TO:$nam<br>$dat<br>\n";
					if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}
					print "[<a href=\"$script?mode=imode&re=$no\">�ԐM</a>] &nbsp; <br>";
				}else{
					print "<hr size=1>[$no]�y$sub�z<br>\n";

					print "��TO:$nam<br>$dat<br>\n";
					if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}
#					print "[<a href=\"$script?mode=imode&re=$re\">�ԐM</a>] &nbsp; <br>";
				}

				print "$com\n";
				#last;
			}
		}
		print "<hr>[<a href=\"$script?mode=imode&re=$in{'no'}\">�ԐM</a>] &nbsp; <br>";
		if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}

		print "[<a href=\"$script?mode=preview\">�ꗗ�֖߂�</a>]\n";
		close(IN);

	}else{

	local($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico);

	# �L����W�J
	$i=0;
	$flag=0;
	open(IN,"$logfile") || &error("Open Error: $logfile");
	$top = <IN>;
	while (<IN>) {
		($no,$re,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);

		if ($re eq "") {
			#���X�̐����v�Z���ĕ\��
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

		# �薼�̒���
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

		}else{
			if (!$re && $flag) {
				$flag=1;
			}
			if (!$re) {
				$flag=1;
			}

			#���ԕϊ�
			&time_change;

			if (!$preview){
				if ($eml && $mailview) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }
				if (!$re) {
					print "<hr size=2>[$no]�y$sub�z<br>\n";

					print "TO:$nam<br>$dat<br>\n";
					if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}

					print "[<a href=\"$script?mode=imode&re=$no\">�ԐM</a>] &nbsp; <br>";
				} else {
					print "<hr  size=1>[$no]�y$sub�z<br>\n";
					print "��TO:$nam<br>$dat<br>\n";
					if ($urlview && $url){print "<a href=\"$url\">[HOME]</a><br>\n";}

				}

				print "$com\n";

			}else{
				#�ꗗ�\���@���e�ԍ���3���{�^�C�g���S�p9�����{/�{���O�P����
				if (length($sub) > 18){$sub = substr($sub,0,18);}
				if (length($nam) > 6){$nam = substr($nam,0,6);}

				#$sub = byte_check($sub);

				#�S�p�����f����Ă��Ȃ����`�F�b�N
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

	#���X�̐����v�Z���ĕ\��(�����p)
	if ($preview && $i >= $page && $i <= $page + $pageView){
		if ($rcount > 0){
			print "($rcount)<br>\n";
		}
	}


	if (!$imode){print "</TD></TR></TABLE>\n";}
	}

	if (!$imode){
		# �y�[�W�ړ��{�^���\��
		if ($page - $pageView >= 0 || $page + $pageView < $i) {
			print "<p><table width=\"90%\"><tr><td>\n";
			&mvbtn("$script?page=", $i, $pageView);
			print "</td></tr></table>\n";
		}
	}else{
		# �y�[�W�ړ��{�^���\��
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
	# ���쌠�\���i�폜�s�j: �A���AMakiMaki����̉摜���g�p���Ȃ��ꍇ�Ɍ���A
	# MakiMaki����̃����N���O�����Ƃ͉\�ł��B
	print <<EOM;
<form action="$regist" method="$method">
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
- <a href="http://www.kent-web.com/" target="_top">KENT</a> &amp; 
<a href="http://homepage3.nifty.com/makiz/" target="_top">MakiMaki</a> -
<br>�g�їp�����F<a href='http://www.url-battle.com/cgi/' target='_top'>����H</a>
&nbsp;&amp;&nbsp;
<a href='http://swanbay-web.hp.infoseek.co.jp/index.html' target='_top'>isso</a>
</span>
</div>
EOM

	}else{
	print <<EOM;
<hr>�I���W�i���FKENT<br>
�g�їp�����F<a href='http://202.212.214.232/bbs/keitai.shtml' target='_top'>����H</a><br>
�X�p���΍�Fisso
EOM
	}

	print <<EOM;
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
	local($cnam,$ceml,$curl,$cpwd,$cico,$ccol,$caikotoba) = &get_cookie;
#	if (!$curl) { $curl = 'http://'; }

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
<form action="$script" method="$method">
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

#------------------#
#  JavaScript����  #
#------------------#
sub noscript {
	&header;
	print <<"EOM";
<table width="100%"><tr><th bgcolor="#008080">
  <font color="#FFFFFF">JavaScript�𗘗p�������[���A�h���X�\\���ɂ���</font>
</th></tr></table>
<P><div align="center">
�X�p��(����I���f���[��)����уE�C���X�΍�̂��߁AJavaScript�𗘗p�������[���A�h���X�\\�����̗p���Ă��܂��B<br>
���萔�����������܂����A���e�҂̃��[���A�h���X��\\�������邽�߂ɂ́AJavaScript��L���ɂ��Ă��������B<br>
<br>
<form action="$script" target="_top">
<input type=hidden name=page value="$page">
<input type=submit value="�f���֖߂�">
</form>
</div>
<br><hr>
</body>
</html>
EOM
	exit;
}


__END__

