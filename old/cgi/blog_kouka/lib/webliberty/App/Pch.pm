#webliberty::App::Pch.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Pch;

use strict;
use base qw(webliberty::Basis);
use webliberty::File;
use webliberty::Skin;
use webliberty::Plugin;
use webliberty::App::Diary;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init   => shift,
		config => shift,
		query  => shift,
		plugin => undef,
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

### PCH表示
sub output {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!-e $self->{init}->{pch_jar}) {
		$self->error('PCHViewer を読み込めません。');
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_pch}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_RESOURCE => $self->{init}->{resource_dir}
	);

	opendir(DIR, $self->{init}->{pch_dir}) or $self->error("Read Error : $self->{init}->{pch_dir}");
	my @files = sort { $a <=> $b } grep { m/\d+\.\w+/g } readdir(DIR);
	close(DIR);

	my $info_ext;
	foreach (@files) {
		my $file_ins  = new webliberty::File("$self->{init}->{paint_dir}$_");

		if ($self->{query}->{file} == $file_ins->get_name) {
			$info_ext = $file_ins->get_ext;

			last;
		}
	}
	my $file_name = "$self->{init}->{pch_dir}$self->{query}->{file}\.$info_ext";

	if (!-e $file_name) {
		$self->error('指定されたファイルは存在しません。');
	}

	my($info_code, $info_archive, $info_reszip, $info_ttzip);
	if ($info_ext eq 'pch') {
		$info_code    = 'pch.PCHViewer.class';
		$info_archive = $self->{init}->{pch_jar};
		$info_reszip  = '';
		$info_ttzip   = '';
	} else {
		$info_code    = 'pch2.PCHViewer.class';
		$info_archive = $self->{init}->{pch_jar};
		$info_reszip  = $self->{init}->{resource_dir} . 'res_normal.zip';
		$info_ttzip   = $self->{init}->{resource_dir} . 'tt.zip';
	}

	print $self->header;
	print $skin_ins->get_replace_data(
		'_all',
		CANVAS_CODE    => $info_code,
		CANVAS_ARCHIVE => $info_archive,
		CANVAS_PCH     => $file_name,
		CANVAS_RESZIP  => $info_reszip,
		CANVAS_TTZIP   => $info_ttzip
	);

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

1;
