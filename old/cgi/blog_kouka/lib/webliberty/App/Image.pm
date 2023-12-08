#webliberty::App::Image.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Image;

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

### 画像表示
sub output {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_image}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	my $target_file;
	if ($self->{query}->{upfile}) {
		$target_file = $self->{init}->{data_upfile_dir} . $self->{query}->{upfile};
	} else {
		$target_file = $self->{init}->{paint_dir} . $self->{query}->{paint};
	}

	my $file_ins  = new webliberty::File("$target_file");
	my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;
	my($file_width, $file_height) = $file_ins->get_size;

	if (!-e $target_file) {
		$self->error('指定されたファイルは存在しません。');
	}

	my $file_path;
	if ($self->{query}->{upfile}) {
		if ($self->{init}->{data_upfile_path}) {
			$file_path = $self->{init}->{data_upfile_path};
		} else {
			$file_path = $self->{init}->{data_upfile_dir};
		}
	} else {
		if ($self->{init}->{paint_path}) {
			$file_path = $self->{init}->{paint_path};
		} else {
			$file_path = $self->{init}->{paint_dir};
		}
	}

	my $file_image = "<img src=\"$file_path$file_name\" alt=\"$file_name\" width=\"$file_width\" height=\"$file_height\" />";

	print $self->header;
	print $skin_ins->get_replace_data(
		'_all',
		INFO_IMAGE      => $file_image,
		INFO_FILENAME   => $file_name,
		INFO_FILEWIDTH  => $file_width,
		INFO_FILEHEIGHT => $file_height
	);

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

1;
