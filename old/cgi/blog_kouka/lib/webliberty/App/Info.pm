#webliberty::App::Info.pm (2007/03/11)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Info;

use strict;
use base qw(webliberty::Basis);
use webliberty::Plugin;
use webliberty::App::Diary;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init   => shift,
		config => shift,
		update => undef
	};
	bless $self, $class;

	return $self;
}

### メイン処理
sub run {
	my $self = shift;

	if ($self->{init}->{rewrite_mode}) {
		my $diary_ins = new webliberty::App::Diary($self->{init}, '', $self->{query});
		$self->{init} = $diary_ins->rewrite(%{$self->{init}->{rewrite}});
	}

	$self->output;

	return;
}

### 設定表示
sub output {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $script      = $self->{init}->{script};
	my $version     = $self->{init}->{version};
	my $copyright   = $self->{init}->{copyright};
	my $script_file = $self->{init}->{script_file};
	my $tb_file     = $self->{init}->{tb_file};
	my $paint_file  = $self->{init}->{paint_file};
	my $html_file   = $self->{init}->{html_file};

	my $spainter_jar;
	if (-e $self->{init}->{spainter_jar}) {
		$spainter_jar = $self->{init}->{spainter_jar};
	} else {
		$spainter_jar = 'Not Found';
	}

	my $paintbbs_jar;
	if (-e $self->{init}->{paintbbs_jar}) {
		$paintbbs_jar = $self->{init}->{paintbbs_jar};
	} else {
		$paintbbs_jar = 'Not Found';
	}

	my $pch_jar;
	if (-e $self->{init}->{pch_jar}) {
		$pch_jar = $self->{init}->{pch_jar};
	} else {
		$pch_jar = 'Not Found';
	}

	my $jcode_mode;
	if ($self->{init}->{jcode_mode}) {
		$jcode_mode = 'ON';
	} else {
		$jcode_mode = 'OFF';
	}

	my $chmod_mode;
	if ($self->{init}->{chmod_mode}) {
		$chmod_mode = 'ON';
	} else {
		$chmod_mode = 'OFF';
	}

	my $suexec_mode;
	if ($self->{init}->{suexec_mode}) {
		$suexec_mode = 'ON';
	} else {
		$suexec_mode = 'OFF';
	}

	my $site_url;
	if ($self->{config}->{site_url}) {
		$site_url = $self->{config}->{site_url};
	} else {
		$site_url = '未指定';
	}

	print $self->header;
	print <<"_HTML_";
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<title>System Information</title>
<style type="text/css">

* {
	margin: 0px;
	padding: 0px;

	font-size: 13px;
	font-style: normal;
	font-family: 'ＭＳ Ｐゴシック', 'MS UI Gothic', Osaka, sans-serif;
	text-align: left;
}

body {
	padding: 10px;

	background-color: #FFFFFF;

	text-align: center;
}
h1 {
	margin-bottom: 30px;

	font-size: 15px;
	text-align: center;
}
p, li, dt, dd, address {
	color: #000000;

	line-height: 1.4;
}
ul {
	margin: 0px 0px 20px 20px;
}
dl {
	margin-bottom: 20px;
}
dd {
	margin: 0px 0px 10px 30px;
}
em {
	font-weight: bold;
}
address {
	margin-top: 30px;

	text-align: center;
}

a {
	color: #0000CC;

	text-decoration: underline;
}

div#container {
	width: 400px;

	margin: 0px auto;
	padding: 20px;
	border: 1px solid #666680;

	background-color: #FFFFFF;
}

</style>
</head>
<body>
<div id="container">
	<h1>システム情報</h1>
	<dl>
		<dt>スクリプト</dt>
			<dd><em>$script</em></dd>
		<dt>バージョン</dt>
			<dd><em>$version</em></dd>
		<dt>著作権</dt>
			<dd><em>$copyright</em></dd>
	</dl>
	<dl>
		<dt>CGIファイル</dt>
			<dd><em>$script_file</em></dd>
		<dt>トラックバック受信ファイル</dt>
			<dd><em>$tb_file</em></dd>
		<dt>イラスト受信ファイル</dt>
			<dd><em>$paint_file</em></dd>
		<dt>HTMLファイル</dt>
			<dd><em>$html_file</em></dd>
		<dt>しぃペインター</dt>
			<dd><em>$spainter_jar</em></dd>
		<dt>PaintBBS</dt>
			<dd><em>$paintbbs_jar</em></dd>
		<dt>PCHViewer</dt>
			<dd><em>$pch_jar</em></dd>
	</dl>
	<dl>
		<dt>文字コード変換</dt>
			<dd><em>$jcode_mode</em></dd>
		<dt>パーミッション自動設定</dt>
			<dd><em>$chmod_mode</em></dd>
		<dt>suEXECモード</dt>
			<dd><em>$suexec_mode</em></dd>
		<dt>サイトのURL</dt>
			<dd><em>$site_url</em></dd>
	</dl>
	<address><a href="http://www.web-liberty.net/">Web Liberty</a></address>
</div>
</body>
</html>
_HTML_

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

1;
