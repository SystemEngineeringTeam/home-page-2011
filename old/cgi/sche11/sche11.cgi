#!/usr/bin/perl

###############################################
#   sche11.cgi
#      V2.0 (2004.9.23)
#                     Copyright(C) CGI-design
###############################################

$script = 'sche11.cgi';
$base = './schedata';				#データ格納ディレクトリ
$nofile = "$base/no.txt";			#記事番号
$recfile = "$base/rec.txt";			#カラー情報
$opfile = "$base/option.txt";		#オプション

@fc = ('#000000','#800000','#0000ff','#008040','#ff0000','#c100c1','#ff80c0');
@bc = ('#d3e3fe','#e6edff','#d5fdec','#e6ffff','#ffd9ec','#ff9f9f','#be8eb5','#fcc592','#fee9c5','#f8ddcf','#fef5da','#ffffff');
@wcolor = ("#ff0000","#000000","#000000","#000000","#000000","#000000","#0000ff");
@week = ('日','月','火','水','木','金','土');
@mdays = (31,28,31,30,31,30,31,31,30,31,30,31);

open (IN,"$opfile") || &error("OPEN ERROR");	$opdata = <IN>;		close IN;
if (!$opdata) {
	$pass = &crypt('cgi');
	chmod(0666,$opfile);	open (OUT,">$opfile") || &error("OPEN ERROR");
	print OUT "$pass<>スケジュール<><>$base/home.gif<>$base/last.gif<>$base/next.gif<>$base/sche.gif<>$base/new.gif<>$base/holiday.gif<><>#ffffff<>#000000<>#800000<>#0000ff<>#ffffff<>3";
	close OUT;
	chmod(0666,$recfile);	open (OUT,">$recfile") || &error("OPEN ERROR");	print OUT "$fc[2]<>$bc[0]";	close OUT;
	chmod(0666,$nofile);
}

##### メイン処理 #####
if ($ENV{'REQUEST_METHOD'} eq "POST") {read(STDIN,$in,$ENV{'CONTENT_LENGTH'});} else {$in = $ENV{'QUERY_STRING'};}
foreach (split(/&/,$in)) {
	($n,$val) = split(/=/);
	$val =~ tr/+/ /;
	$val =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
	$in{$n} = $val;
}
$mode = $in{'mode'};

open (IN,"$opfile") || &error("OPEN ERROR");
($pass,$title,$home,$home_icon,$last_icon,$next_icon,$sche_icon,$new_icon,$holi_icon,$bg_img,$bg_color,$text_color,$title_color,$sub_color,$sche_color,$newday) = split(/<>/,<IN>);
close IN;

$nowtime = time;
($sec,$min,$hour,$nowday,$nowmon,$nowyear) = localtime;
$nowyear += 1900;
$nowmon++;
$newtime = $nowtime - $newday * 24 * 3600;

$logyear = $in{'year'};
$logmon = $in{'mon'};
if (!$logyear) {$logyear = $nowyear; $logmon = $nowmon;}
$logfile = "$base/$logyear$logmon.txt";

if ($mode eq 'search') {&search;}
elsif ($mode eq 'admin') {&admin;}
else {&main;}

print "</center></body></html>\n";
exit;

###
sub header {
	print "Content-type: text/html\n\n";
	print "<html><head><META HTTP-EQUIV=\"Content-type\" CONTENT=\"text/html; charset=Shift_JIS\">\n";
	print "<title>Schedule</title><link rel=\"stylesheet\" type=\"text/css\" href=\"$base/style.css\"></head>\n";
	$head = 1;
}

###
sub main {
	&header;
	print "<body background=\"$bg_img\" bgcolor=\"$bg_color\" text=\"$text_color\"><center>\n";
	print "<table width=750><tr><td width=100 valign=top>\n";
	if ($home) {if ($home_icon) {print "<a href=\"$home\"><img src=\"$home_icon\" border=0></a>";} else {print "<a href=\"$home\">[HOME]</a>";}}
	print "</td><td align=center>";
	if ($title =~ /http:/) {print "<img src=\"$title\">\n";} else {print "<font color=\"$title_color\" size=\"+1\"><b>$title</b></font>\n";}
	print "</td><td width=100 align=right>| <a href=\"$script?mode=search\">検索</a> | <a href=\"$script?mode=admin\">編集</a> |</td></tr></table>\n";
	&dsp;
	# 次の行は著作権表示ですので削除しないで下さい。#
	print "<a href=\"http://merlion.cool.ne.jp/cgi/\" target=\"_blank\">CGI-design</a>\n";
}

###
sub dsp {
	@data=@lognum=@logno=();
	if (-e $logfile) {
		open (IN,"$logfile") || &error("OPEN ERROR");
		while (<IN>) {
			push (@data,$_);
			($no,$time,$day) = split(/<>/);
			$lognum[$day]++;
			if (!$logno[$day]) {$logno[$day] = $no;}
		}
		close IN;
	}
	$mdays = $mdays[$logmon - 1];
	if ($logmon == 2 && $logyear % 4 == 0) {$mdays = 29;}

	print "<table bgcolor=\"#ccccff\" cellspacing=15 cellpadding=0><tr align=center valign=top><td>\n";
	print "<table width=100%><tr><td width=60>　<b>$logyear年</b></td><td align=right>\n";
	$mon = $logmon - 1;
	if ($mon < 1) {$mon = 12; $year = $logyear - 1;} else {$year = $logyear;}
	print "<form action=\"$script\" method=\"POST\">\n";
	print "<input type=hidden name=mode value=\"$mode\">\n";
	print "<input type=hidden name=pass value=\"$inpass\">\n";
	print "<input type=hidden name=year value=\"$year\">\n";
	print "<input type=hidden name=mon value=\"$mon\">\n";
	print "<input type=image src=\"$last_icon\"></td></form>\n";
	print "<td width=60 align=center><font size=\"+2\"><b>$logmon月</b></font></td>\n";
	$mon = $logmon + 1;
	if (12 < $mon) {$mon = 1; $year = $logyear + 1;} else {$year = $logyear;}
	print "<td><form action=\"$script\" method=\"POST\">\n";
	print "<input type=hidden name=mode value=\"$mode\">\n";
	print "<input type=hidden name=pass value=\"$inpass\">\n";
	print "<input type=hidden name=year value=\"$year\">\n";
	print "<input type=hidden name=mon value=\"$mon\">\n";
	print "<input type=image src=\"$next_icon\"></td></form><td width=60></td></tr></table>\n";

	print "<table bgcolor=\"#ffffff\" bordercolor=\"#aaaaaa\" border=1 cellspacing=0 cellpadding=4 style=\"border-collapse: collapse\">\n";
	print "<tr bgcolor=\"#e6edff\" align=center>\n";
	for (0 .. 6) {print "<td width=35><font color=\"$wcolor[$_]\"><b>$week[$_]</b></font></td>\n";}
	print "</tr>\n";

	&holi_set;
	$wday = &get_date($logyear,$logmon,1);
	$w=$n=0;
	$k=1;
	for (0 .. 41) {
		if (!$w) {print "<tr>";}
		if ($wday <= $_ && $k <= $mdays) {
			if ($w == 1) {$n++;}
			$wcolor = $wcolor[$w];
			if (2002 < $logyear) {
				&get_holiday($logmon,$k,$w,$n);
				if ($holiday) {$wcolor = $wcolor[0];}
			}
			if ($logyear == $nowyear && $logmon == $nowmon && $k == $nowday) {$bc = '#dFFF00';}
			elsif ($holiday || !$w) {$bc = '#fef0ef';}
			elsif ($w == 6) {$bc = '#eeffff';}
			else {$bc = '#ffffe8';}
			if ($k < 10) {$day = "&nbsp;$k";} else {$day = $k;}

			print "<td bgcolor=\"$bc\" height=30 valign=top><font color=\"$wcolor\"><b>$day</b></font>\n";
			if ($holiday && $holi_icon) {print " <img src=\"$holi_icon\" alt=\"$holiday\">";}
			print "<br>　";
			if ($lognum[$k]) {for (1 .. $lognum[$k]) {print "<a href=\"\#$logno[$k]\"><img src=\"$sche_icon\" alt=\"詳細\" border=0></a>";}}
			print "</td>\n";
			$k++;
		} else {print "<td></td>\n";}
		$w++;
		if ($w == 7) {
			print "</tr>\n";
			if ($mdays < $k) {last;}
			$w = 0;
		}
	}
	print "</table></td><td><table width=400 cellspacing=1 cellpadding=1>\n";
	print "<tr bgcolor=\"#e6edff\" align=center><td width=90>日　付</td><td>題　名</td></tr>\n";
	foreach (@data) {
		($no,$time,$day,$wday,$sub,$com,$ftcolor,$frcolor) = split(/<>/);
		if ($logyear == $nowyear && $logmon == $nowmon && $day == $nowday) {$bc = '#dfff00';} else {$bc = '#ffffff';}
		print "<tr bgcolor=\"$bc\"><td>&nbsp;&nbsp;$logmon月$day日<font color=\"$wcolor[$wday]\">($week[$wday])</font></td><td>　<a href=\"\#$no\">$sub</a>";
		if ($newtime < $time) {print "&nbsp;<img src=\"$new_icon\">";}
		print "</td></tr>\n";
	}
	print "</table></td></tr></table><br>\n";
	foreach (@data) {
		($no,$time,$day,$wday,$sub,$com,$ftcolor,$frcolor) = split(/<>/);
		&dsp_log;
	}
}

###
sub dsp_log {
	$com =~ s/([^=^\"]|^)(http\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+)/$1<a href=\"$2\" target=\"_blank\">$2<\/a>/g;
	print "<a name=\"$no\"></a><table width=650 bgcolor=\"$frcolor\" cellspacing=4 cellpadding=0>\n";
	print "<tr><td height=20>　<b>$logyear年$logmon月$day日<font color=\"$wcolor[$wday]\">($week[$wday])</font></b>　　<font color=\"$sub_color\"><b>$sub</b></font></td>\n";
	if ($mode eq 'admin') {
		print "<td align=right><form action=\"$script\" method=\"POST\">\n";
		print "<input type=hidden name=mode value=\"admin\">\n";
		print "<input type=hidden name=pass value=\"$inpass\">\n";
		print "<input type=hidden name=act value=\"edt\">\n";
		print "<input type=hidden name=no value=\"$no\">\n";
		print "<input type=hidden name=year value=\"$logyear\">\n";
		print "<input type=hidden name=mon value=\"$logmon\">\n";
		print "<input type=submit value=\"修正\"></td></form>\n";
	}
	print "</tr><tr><td colspan=2><table width=100% bgcolor=\"$sche_color\" cellspacing=8><tr><td><font color=\"$ftcolor\">$com</font></td></tr></table></td></tr></table>\n";
	print "<table width=630 cellpadding=0><tr><td align=right><a href=\"\#top\">▲top</a></td></tr></table>\n";
}

###
sub get_date {
	my($y,$m,$d) = @_;
	if ($m < 3){$y--; $m+=12;}
	return ($y+int($y/4)-int($y/100)+int($y/400)+int((13*$m+8)/5)+$d)%7;
}

###
sub holi_set {
	$def = 0.242194*($logyear-1980)-int(($logyear-1980)/4);
	$spr = int(20.8431+$def);
	$aut = int(23.2488+$def);
	%holi_d = ('0101','元日','0211','建国記念の日',"03$spr",'春分の日','0429','みどりの日','0503','憲法記念日','0505','こどもの日',"09$aut",'秋分の日','1103','文化の日','1123','勤労感謝の日','1223','天皇誕生日');
	%holi_w = ('012','成人の日','073','海の日','093','敬老の日','102','体育の日');
}

###
sub get_holiday {
	$sm = sprintf("%02d%02d",$_[0],$_[1]);
	$holiday = $holi_d{$sm};
	if ($sm eq '0504' && 1 < $_[2]) {$holiday = '国民の休日';}
	if ($holiday && !$_[2]) {$hflag = 1;}
	if (!$holiday && $_[2] == 1) {
		$smw = sprintf("%02d$_[3]",$_[0]);
		$holiday = $holi_w{$smw};
		if ($hflag) {$holiday = '振替休日'; $hflag = 0;}
	}
}

###
sub search {
	&header;
	print "<body background=\"$bg_img\" bgcolor=\"$bg_color\" text=\"$text_color\"><center>\n";
	print "<table width=97%><tr><td><a href=\"$script\">[Return]</a></td></tr></table>\n";
	print "キーワードを入力し「検索」をクリックして下さい。キーワードを複数指定する場合はスペースで区切って下さい。\n";
	print "<form action=\"$script\" method=POST>\n";
	print "<input type=hidden name=mode value=\"search\">\n";
	print "キーワード <input type=text name=word size=50 value=\"$in{'word'}\">\n";
	print "　<select name=year>";
	for (2004 .. $nowyear+1) {
		if ($_ == $logyear) {$sel = ' selected';} else {$sel = '';}
		print "<option value=\"$_\"$sel>$_</option>\n";
	}
	print "</select>年\n";
	print "　　<input type=submit value=\" 検索 \"></form>\n";
	if (!$in{'word'}) {return;}

	$in{'word'} =~ s/　/ /g;
	@word = split(/\s+/,$in{'word'});
	$m = 0;
	for (1 .. 12) {
		$logmon = $_;
		$logfile = "$base/$logyear$logmon.txt";
		if (!-e $logfile) {next;}
		@data = ();
		open (IN,"$logfile") || &error("OPEN ERROR");
		while (<IN>) {
			$find = 0;
			foreach $word (@word) {if (0 <= index($_,$word)) {$find = 1;} else {$find = 0; last;}}
			if ($find) {push(@data,$_); $m++;}
		}
		close IN;
		if (!$data[0]) {next;}

		print "<table width=750><tr><td><b>$logmon月</b></td></tr></table>\n";
		foreach (@data) {
			($no,$time,$day,$wday,$sub,$com,$ftcolor,$frcolor) = split(/<>/);
			&dsp_log;
		}
	}
	print "検索結果： 計 <b>$m</b>件\n";
}

###
sub admin {
	&header;
	print "<body><center>\n";
	$inpass = $in{'pass'};
	if ($inpass eq '') {
		print "<table width=97%><tr><td><a href=\"$script\">[Return]</a></td></tr></table>\n";
		print "<br><br><br><br><h4>パスワードを入力して下さい</h4>\n";
		print "<form action=\"$script\" method=POST>\n";
		print "<input type=hidden name=mode value=\"admin\">\n";
		print "<input type=password name=pass size=10 maxlength=8>\n";
		print "<input type=submit value=\"認証\"></form>\n";
		print "</center></body></html>\n";
		exit;
	}
	$mat = &decrypt($inpass,$pass);
	if (!$mat) {&error("パスワードが違います");}

	print "<table width=95% bgcolor=\"#8c4600\"><tr><td>　<a href=\"$script\"><font color=\"#ffffff\"><b>Return</b></font></a></td>\n";
	print "<td align=right><form action=\"$script\" method=POST>\n";
	print "<input type=hidden name=mode value=\"admin\">\n";
	print "<input type=hidden name=pass value=\"$inpass\">\n";
	print "<input type=submit value=\"　編集　\">\n";
	print "<input type=submit name=set value=\"　設定　\"></td></form><td width=10></td></tr></table><br>\n";

	$act = $in{'act'};
	if ($in{'set'}) {&setup;} else {&edt;}
}

###
sub edt {
	if ($in{'newwrt'}) {&newwrt;}
	elsif ($in{'edtwrt'}) {&edtwrt;}
	elsif ($in{'delwrt'}) {&delwrt;}

	&in_form;
	print "<a name=\"top\"></a><hr width=90%>記事を修正・削除する場合は[修正]をクリックして下さい。<br><br>\n";
	&dsp;
}

###
sub in_form {
	print "<table bgcolor=\"#edefde\" cellspacing=8><tr><td><table cellspacing=2 cellpadding=0>\n";
	print "<form action=\"$script\" method=POST>\n";
	print "<input type=hidden name=mode value=\"admin\">\n";
	print "<input type=hidden name=pass value=\"$inpass\">\n";
	if (!$act) {
		print "<tr><td>日付</td><td><select name=year>\n";
		for (2004 .. $nowyear+1) {
			if ($_ == $nowyear) {$sel = ' selected';} else {$sel = '';}
			print "<option value=\"$_\"$sel>$_</option>\n";
		}
		print "</select>年 <select name=mon>\n";
		for (1 .. 12) {
			if ($_ == $nowmon) {$sel = ' selected';} else {$sel = '';}
			print "<option value=\"$_\"$sel>$_</option>\n";
		}
		print "</select>月 <select name=day>\n";
		for (1 .. 31) {
			if ($_ == $nowday) {$sel = ' selected';} else {$sel = '';}
			print "<option value=\"$_\"$sel>$_</option>\n";
		}
		print "</select>日</td></tr>\n";
		$sub=$com='';
		open (IN,"$recfile") || &error("OPEN ERROR");	($ftcolor,$frcolor) = split(/<>/,<IN>);		close IN;
	} else {
		print "<input type=hidden name=year value=\"$logyear\">\n";
		print "<input type=hidden name=mon value=\"$logmon\">\n";
		print "<input type=hidden name=no value=\"$in{'no'}\">\n";
		open (IN,"$logfile") || &error("OPEN ERROR");
		while (<IN>) {
			($no,$time,$day,$wday,$sub,$com,$ftcolor,$frcolor) = split(/<>/);
			if ($no eq $in{'no'}) {last;}
		}
		close IN;
		$com =~ s/<br>/\r/g;
		print "<tr><td>日付</td><td>&nbsp;<b>$logyear年$logmon月$day日<font color=\"$wcolor[$wday]\">($week[$wday])</font></b></td></tr>\n";
	}
	print "<tr><td>題名</td><td><input type=text name=sub size=50 value=\"$sub\"></td></tr>\n";
	print "<tr><td valign=top><br>内容</td><td><textarea cols=80 rows=20 name=com wrap=\"soft\">$com</textarea></td></tr>\n";
	print "<tr><td>文字色</td><td><table cellspacing=0 cellpadding=0><tr align=center>\n";
	foreach (@fc) {
		if ($_ eq $ftcolor) {$chk = ' checked';} else {$chk = '';}
		print "<td width=35 bgcolor=\"$_\"><input type=radio name=ftcolor value=\"$_\"$chk></td>\n";
	}
	print "</tr></table></td></tr>\n";
	print "<tr><td>枠色</td><td><table cellspacing=0 cellpadding=0><tr align=center>\n";
	foreach (@bc) {
		if ($_ eq $frcolor) {$chk = ' checked';} else {$chk = '';}
		print "<td width=35 bgcolor=\"$_\"><input type=radio name=frcolor value=\"$_\"$chk></td>\n";
	}
	print "</tr></table></td></tr>\n";
	print "<tr><td></td><td>";
	if (!$act) {print "<input type=submit name=newwrt value=\"登録する\">";}
	else {
		print "<table width=100% cellspacing=0 cellpadding=2><tr><td><input type=submit name=edtwrt value=\"修正する\"></td>\n";
		print "<td width=40 bgcolor=red align=center><input type=submit name=delwrt value=\"削除\"></td></tr></table>\n";
	}
	print "</td></tr></table></td></tr></table></form>\n";
}

###
sub newwrt {
	$in{'com'} =~ s/\r\n|\r|\n/<br>/g;
	$wday = &get_date($logyear,$logmon,$in{'day'});

	open (IN,"$nofile") || &error("OPEN ERROR"); 		$no = <IN>; 		close IN;
	$no++;
	open (OUT,">$nofile") || &error("OPEN ERROR");		print OUT $no;		close OUT;
	$newdata = "$no<>$nowtime<>$in{'day'}<>$wday<>$in{'sub'}<>$in{'com'}<>$in{'ftcolor'}<>$in{'frcolor'}<>\n";

	if (-e $logfile) {
		@new = ();
		$flag = 0;
		open (IN,"$logfile") || &error("OPEN ERROR");
		while (<IN>) {
			($no,$time,$day) = split(/<>/);
			if (!$flag && $in{'day'} < $day) {push(@new,$newdata); $flag = 1;}
			push(@new,$_);
		}
		close IN;
		if (!$flag) {push(@new,$newdata);}
		open (OUT,">$logfile") || &error("OPEN ERROR");		print OUT @new;			close OUT;
	} else {
		open (OUT,">$logfile") || &error("OPEN ERROR");		print OUT $newdata;		close OUT;		chmod(0666,$logfile);
	}
	open (OUT,">$recfile") || &error("OPEN ERROR");			print OUT "$in{'ftcolor'}<>$in{'frcolor'}";		close OUT;
}

###
sub edtwrt {
	$in{'com'} =~ s/\r\n|\r|\n/<br>/g;
	@new = ();
	open (IN,"$logfile") || &error("OPEN ERROR");
	while (<IN>) {
		($no,$time,$day,$wday) = split(/<>/);
		if ($no eq $in{'no'}) {push(@new,"$no<>$time<>$day<>$wday<>$in{'sub'}<>$in{'com'}<>$in{'ftcolor'}<>$in{'frcolor'}<>\n");}
		else {push(@new,$_);}
	}
	close IN;
	open (OUT,">$logfile") || &error("OPEN ERROR");		print OUT @new;		close OUT;
}

###
sub delwrt {
	@new = ();
	open (IN,"$logfile") || &error("OPEN ERROR");
	while (<IN>) {
		($no) = split(/<>/);
		if ($no ne $in{'no'}) {push(@new,$_);}
	}
	close IN;
	open (OUT,">$logfile") || &error("OPEN ERROR");		print OUT @new;		close OUT;
}

###
sub setup {
	if ($in{'wrt'}) {
		if ($in{'newpass'} ne '') {$pass = &crypt($in{'newpass'});}
		$title = $in{'title'};
		$home = $in{'home'};
		$home_icon = $in{'home_icon'};
		$last_icon = $in{'last_icon'};
		$next_icon = $in{'next_icon'};
		$sche_icon = $in{'sche_icon'};
		$new_icon = $in{'new_icon'};
		$holi_icon = $in{'holi_icon'};
		$bg_img = $in{'bg_img'};
		$newday = $in{'newday'};

		$bg_color = $in{'color0'};
		$text_color = $in{'color1'};
		$title_color = $in{'color2'};
		$sub_color = $in{'color3'};
		$sche_color = $in{'color4'};

		open (OUT,">$opfile") || &error("OPEN ERROR");
		print OUT "$pass<>$title<>$home<>$home_icon<>$last_icon<>$next_icon<>$sche_icon<>$new_icon<>$holi_icon<>$bg_img<>$bg_color<>$text_color<>$title_color<>$sub_color<>$sche_color<>$newday";
		close OUT;
	}

	print "<form action=\"$script\" method=\"POST\">\n";
	print "<input type=hidden name=mode value=\"admin\">\n";
	print "<input type=hidden name=pass value=\"$inpass\">\n";
	print "<input type=hidden name=set value=\"1\">\n";
	print "<input type=submit name=wrt value=\"設定する\"><br><br>\n";

	print "<table bgcolor=\"#dddddd\" cellspacing=10><tr><td><table cellspacing=1 cellpadding=0>\n";
	print "<tr><td><b>タイトル</b></td><td><input type=text name=title size=60 value=\"$title\"></td></tr>\n";
	print "<tr><td><b>ホームURL</b></td><td><input type=text size=60 name=home value=\"$home\"></td></tr>\n";
	print "<tr><td><b>ホームアイコン</b></td><td><input type=text size=60 name=home_icon value=\"$home_icon\">\n";
	if ($home_icon) {print "　<img src=\"$home_icon\">";}
	print "</td></tr>\n";
	print "<tr><td><b>LASTアイコン</b></td><td><input type=text size=60 name=last_icon value=\"$last_icon\">　<img src=\"$last_icon\"></td></tr>\n";
	print "<tr><td><b>NEXTアイコン</b></td><td><input type=text size=60 name=next_icon value=\"$next_icon\">　<img src=\"$next_icon\"></td></tr>\n";
	print "<tr><td nowrap><b>スケジュールアイコン</b></td><td><input type=text size=60 name=sche_icon value=\"$sche_icon\">　<img src=\"$sche_icon\"></td></tr>\n";
	print "<tr><td><b>NEWアイコン</b></td><td><input type=text size=60 name=new_icon value=\"$new_icon\">　<img src=\"$new_icon\" align=middle></td></tr>\n";
	print "<tr><td><b>休日アイコン</b></td><td><input type=text size=60 name=holi_icon value=\"$holi_icon\">\n";
	if ($holi_icon) {print "　<img src=\"$holi_icon\" align=middle>";}
	print "</td></tr>\n";
	print "<tr><td><b>壁紙</b></td><td><input type=text size=60 name=bg_img value=\"$bg_img\">\n";
	if ($bg_img) {print "　<img src=\"$bg_img\" width=30 align=middle>";}
	print "</td></tr>\n";

	print "<tr><td></td><td><a href=\"$base/color.htm\" target=\"_blank\">カラーコード</a></td></tr>\n";
	@name = ('基本背景色','基本文字色','タイトル色','題名色','内容背景色');
	@data = ($bg_color,$text_color,$title_color,$sub_color,$sche_color);
	for (0 .. $#name) {
		print "<tr><td><b>$name[$_]</b></td><td><table cellspacing=0 cellpadding=0><tr>\n";
		print "<td><input type=text name=color$_ size=10 value=\"$data[$_]\"></td>\n";
		print "<td width=5></td><td width=80 bgcolor=\"$data[$_]\"></td></tr></table></td></tr>\n";
	}
	print "<tr><td nowrap><img src=\"$new_icon\"><b>の表\示日数</b></td><td><input type=text name=newday size=3 value=\"$newday\" style=\"text-align: right\">日間</td></tr>\n";
	print "<tr><td><b>パスワード変更</b></td><td><input type=password name=newpass size=10 maxlength=8> （英数8文字以内）</td></tr>\n";
	print "</table></td></tr></table></form>\n";
}

###
sub crypt {
	@salt = ('a' .. 'z','A' .. 'Z','0' .. '9');
	srand;
	$salt = "$salt[int(rand($#salt))]$salt[int(rand($#salt))]";
	return crypt($_[0],$salt);
}

###
sub decrypt {
	$salt = $_[1] =~ /^\$1\$(.*)\$/ && $1 || substr($_[1],0,2);
	if (crypt($_[0],$salt) eq $_[1] || crypt($_[0],'$1$' . $salt) eq $_[1]) {return 1;}
	return 0;
}

###
sub error {
	if (!$head) {&header; print "<body><center>\n";}
	print "<br><br><br><br><h3>ERROR !!</h3><font color=red><b>$_[0]</b></font>\n";
	print "</center></body></html>\n";
	exit;
}
