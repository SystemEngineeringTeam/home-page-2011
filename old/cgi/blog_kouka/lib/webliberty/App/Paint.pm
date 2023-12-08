#webliberty::App::Paint.pm (2008/07/05)
#Copyright(C) 2002-2008 Knight, All rights reserved.

package webliberty::App::Paint;

use strict;
use base qw(webliberty::Basis);
use webliberty::Host;
use webliberty::Configure;
use webliberty::Plugin;
use webliberty::App::Init;
use webliberty::App::Admin;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init   => undef,
		config => undef,
		query  => undef,
		update => undef
	};
	bless $self, $class;

	my $init_ins = new webliberty::App::Init;
	$self->{init} = $init_ins->get_init;

	my $config_ins = new webliberty::Configure($self->{init}->{data_config});
	$self->{config} = $config_ins->get_config;

	return $self;
}

### メイン処理
sub run {
	my $self = shift;

	$self->regist;

	return;
}

### イラスト投稿
sub regist {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $flag;

	if ($ENV{'REQUEST_METHOD'} ne 'POST') {
		$flag = 1;
	}
	if ($ENV{'HTTP_REFERER'} and $self->{config}->{base_url} and $ENV{'HTTP_REFERER'} !~ $self->{config}->{base_url}) {
		$flag = 1;
	}
	if (!$self->{config}->{proxy_mode} and ($ENV{'HTTP_VIA'} or $ENV{'HTTP_FORWARDED'} or $ENV{'HTTP_X_FORWARDED_FOR'})) {
		$flag = 1;
	}

	my $host_ins = new webliberty::Host;
	foreach (split(/<>/, $self->{config}->{black_list})) {
		$_ = quotemeta($_);

		if ($host_ins->get_host =~ /$_/i) {
			$flag = 1;
			last;
		}
	}

	if ($flag) {
		$self->error('不正なアクセスです。');
	}

	binmode(STDIN);

	my $content_length = $ENV{'CONTENT_LENGTH'};
	my $read_length = 0;
	my $first_data  = 0;

	if (read(STDIN, $first_data, 1) != 1) {
		$self->error('STDINからデータを読み込めませんでした。');
	}
	$content_length--;
	$read_length++;

	#アプレット判別
	my $pch_ext;
	if ($first_data eq 'P') {
		$pch_ext = 'pch';
	} else {
		$pch_ext = 'spch';
	}

	#拡張ヘッダ取得
	my $exh_length = 0;
	my $exthead;
	read(STDIN, $exh_length, 8);
	$exh_length += 0;
	if ($exh_length > 0) {
		read(STDIN, $exthead, $exh_length);
	}
	$content_length -= ($exh_length + 8);
	$read_length    += (length($exthead) + 8);

	#画像データ取得
	my($img_size, $thm_length) = (0, 0);
	my $img_data;
	read(STDIN, $img_size, 8);
	$img_size += 0;
	if ($img_size <= 0) {
		$self->error('画像データがありません。');
	}
	read(STDIN, $thm_length, 2);
	if ($img_size > 0){
		read(STDIN, $img_data, $img_size);
	}
	$content_length -= ($img_size + 10);
	$read_length    += (length($img_data) + 10);

	#サムネイル・PCHデータ取得
	my($thm_data1, $thm_data2);
	if (read(STDIN, $thm_length, 8)) {
		$thm_length += 0;
	 	if ($thm_length > 0) {
			read(STDIN, $thm_data1, $thm_length);
	 	}
		$content_length -= ($thm_length + 8);
		$read_length    += (length($thm_data1) + 8);
	}
	if (read(STDIN, $thm_length, 8)) {
		$thm_length += 0;
	 	if($thm_length > 0){
			read(STDIN, $thm_data2, $thm_length);
	 	}
		$content_length -= ($thm_length + 8);
		$read_length    += (length($thm_data2) + 8);
	}

	my($pch_data, $thm_data);
	if ($thm_data1) {
		if ($thm_data1 =~ /^\xff\xd8\xff/ or $thm_data1 =~ /^\x89PNG\r\n\x1a/) {
			$thm_data = $thm_data1;
			if ($thm_data2) {
				$pch_data = $thm_data2;
			}
		} else {
			$pch_data = $thm_data1;
			if ($thm_data2) {
				$thm_data = $thm_data2;
			}
		}
		$thm_data1 = '';
		$thm_data2 = '';
	}

	#データ送信チェック
	if ($read_length ne $ENV{'CONTENT_LENGTH'}) {
		$self->error('投稿データが正常に送信されませんでした。');
	}

	#拡張ヘッダ取得
	my %ex;
	foreach (split(/&/, $exthead)) {
		my($key, $value) = split(/=/, $_, 2);
		$value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack('C', hex($1))/eg;
		$ex{$key} = $value;
	}

	if ($ex{'image_type'} =~ /png/i) {
		$ex{'ext'} = 'png';
	} elsif ($ex{'image_type'} =~ /jpeg/i) {
		$ex{'ext'} = 'jpg';
	} else {
		if ($img_data =~ /^PNG/) {
			$ex{'ext'} = 'png';
		} else {
			$ex{'ext'} = 'jpg';
		}
	}

	my $dammy;
	$dammy->{query}->{admin_user} = $ex{'admin_user'};
	$dammy->{query}->{admin_pwd}  = $ex{'admin_pwd'};

	my $app_ins = new webliberty::App::Admin($self->{init}, $self->{config}, $dammy->{query});
	if (!$app_ins->check_password) {
		$self->error('パスワードが違います。');
	}

	my $file_name;
	if ($ex{'pch'}) {
		$file_name = $ex{'pch'};
	} else {
		opendir(DIR, $self->{init}->{pch_dir}) or $self->error("Read Error : $self->{init}->{pch_dir}");
		my @dir = sort { $b <=> $a } readdir(DIR);
		closedir(DIR);

		$file_name = $dir[0] + 1;
	}

	if ($img_data ne '') {
		opendir(DIR, $self->{init}->{paint_dir}) or $self->error("Read Error : $self->{init}->{paint_dir}");
		my @files = sort { $a <=> $b } grep { m/\d+\.\w+/g } readdir(DIR);
		close(DIR);

		foreach (@files) {
			if ($_ =~ /(\d+)\.\w+/ and $1 eq $file_name) {
				unlink("$self->{init}->{paint_dir}$_");
			}
		}

		open(IMG, ">$self->{init}->{paint_dir}$file_name\.$ex{'ext'}") or $self->error("Write Error : $self->{init}->{paint_dir}$file_name\.$ex{'ext'}");
		binmode(IMG);
		print IMG $img_data;
		close(IMG);
	}
	if ($pch_data ne '') {
		open(PCH, ">$self->{init}->{pch_dir}$file_name\.$pch_ext") or $self->error("Write Error : $self->{init}->{pch_dir}$file_name\.$pch_ext");
		binmode(PCH);
		print PCH $pch_data;
		close(PCH);
	}

	if ($ex{'pch'}) {
		$app_ins->record_log("No.$ex{'pch'}のイラストを編集しました。");
	} else {
		$app_ins->record_log("イラストを新規に投稿しました。");
	}

	print "Content-type: text/plain\n\nok";

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### エラー出力
sub error {
	my $self    = shift;
	my $message = shift;

	print "Content-type: text/plain\n\nerror\nエラーが発生しました。\n$message";

	exit;
}

1;
