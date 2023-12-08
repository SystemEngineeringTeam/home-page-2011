#webliberty::App::Icon.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Icon;

use strict;
use base qw(webliberty::Basis);
use webliberty::String;
use webliberty::File;
use webliberty::Skin;
use webliberty::Plugin;
use webliberty::App::Diary;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init    => shift,
		config  => shift,
		query   => shift,
		plugin  => undef,
		html    => undef,
		message => undef,
		update  => undef
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

	$self->output_icon;

	return;
}

### アイコン一覧
sub output_icon {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_icon}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('list_head');

	my($list, $i) = 0;

	open(FH, $self->{init}->{data_icon}) or $self->error("Read Error : $self->{init}->{data_icon}");
	while (<FH>) {
		$list++;
	}
	seek(FH, 0, 0);
	while (<FH>) {
		chomp;
		my($file, $name, $field, $user, $pwd) = split(/\t/);

		my $file_path;
		if ($self->{init}->{data_icon_path}) {
			$file_path = $self->{init}->{data_icon_path};
		} else {
			$file_path = $self->{init}->{data_icon_dir};
		}
		my $image = "<img src=\"$file_path$file\" alt=\"$file\" />";

		print $skin_ins->get_replace_data(
			'list',
			ICON_FILE  => $file,
			ICON_IMAGE => $image,
			ICON_NAME  => $name,
			ICON_USER  => $user
		);

		$i++;
		if ($i % 5 == 0 and $i != $list) {
			print $skin_ins->get_data('list_delimiter');
		}
	}
	close(FH);

	print $skin_ins->get_data('list_foot');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### アイコン並び替え
sub _sort_icon {
	my $self = shift;

	my(@normal, @personal, @names);

	foreach (@_) {
		chomp;
		my($file, $name, $field, $user, $pwd) = split(/\t/);

		if ($user) {
			push(@personal, "$_\n");
		} else {
			push(@normal, "$_\n");
		}
	}

	@names  = map { (split(/\t/))[1] } @normal;
	@normal = @normal[sort { $names[$a] cmp $names[$b] } (0 .. $#names)];

	@names    = map { (split(/\t/))[1] } @personal;
	@personal = @personal[sort { $names[$a] cmp $names[$b] } (0 .. $#names)];

	return(@normal, @personal);
}

1;
