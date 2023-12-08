#webliberty::App::Trackback.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Trackback;

use strict;
use base qw(webliberty::Basis);
use webliberty::Lock;
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
		index  => undef,
		html   => undef,
		update => undef
	};
	bless $self, $class;

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if ($stat and $self->{query}->{no} == $no) {
			$self->{index}->{date} = $date;
			$self->{index}->{no}   = $no;
			$self->{index}->{all}  = $_;

			last;
		}
	}
	close(FH);

	return $self;
}

### メイン処理
sub run {
	my $self = shift;

	if ($self->{init}->{rewrite_mode}) {
		my $diary_ins = new webliberty::App::Diary($self->{init}, '', $self->{query});
		$self->{init} = $diary_ins->rewrite(%{$self->{init}->{rewrite}}, data => $self->{index}->{all});
	}

	$self->output_list;

	return;
}

### トラックバック一覧
sub output_list {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{config}->{site_url}) {
		$self->error('サイトのURLが設定されていません。');
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_diary}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_trackback}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		TRACKBACK_NO => $self->{query}->{no}
	);

	if (!$self->{index}->{date} or !$self->{index}->{no}) {
		$self->error('指定された記事は存在しません。');
	}

	my $data_file;
	if ($self->{index}->{date} =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
		$data_file = "$1$2\.$self->{init}->{data_ext}";
	}

	$self->{html}->{header} = $skin_ins->get_data('header');

	if ($self->{config}->{show_navigation} == 2) {
		$self->{init}->{js_navi_start_file} =~ s/^\.\///;
		$self->{html}->{diary} = "<script type=\"text/javascript\" src=\"$self->{config}->{site_url}$self->{init}->{js_navi_start_file}\"></script>\n";
	}

	$self->{html}->{diary} .= $skin_ins->get_data('diary_head');

	my $diary_subj;

	open(FH, "$self->{init}->{data_diary_dir}$data_file") or $self->error("Read Error : $self->{init}->{data_diary_dir}$data_file");
	while (<FH>) {
		chomp;
		my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

		if ($self->{index}->{no} == $no) {
			if (!$tb) {
				$self->error('この記事にトラックバックを送信することはできません。');
			}

			$diary_subj = $subj;

			$self->{html}->{diary} .= $skin_ins->get_replace_data(
				'diary',
				$diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host)
			);
			$self->{html}->{diary} .= $skin_ins->get_data('diary_delimiter');

			last;
		}
	}
	close(FH);

	$self->{html}->{diary} .= $skin_ins->get_data('diary_foot');

	if ($self->{config}->{title_mode} == 3) {
		$self->{html}->{header} =~ s/<title(.*)>(.+)<\/title>/<title$1>$diary_subj<\/title>/i;
	} elsif ($self->{config}->{title_mode} == 2) {
		$self->{html}->{header} =~ s/<title(.*)>(.+)<\/title>/<title$1>$diary_subj \- $2<\/title>/i;
	} elsif ($self->{config}->{title_mode} == 1) {
		$self->{html}->{header} =~ s/<title(.*)>(.+)<\/title>/<title$1>$2 \- $diary_subj<\/title>/i;
	}

	$self->{html}->{contents} = $skin_ins->get_data('contents');

	$self->{html}->{contents} .= $skin_ins->get_replace_data(
		'trackback_head',
		ARTICLE_TRACKBACK_START => '<!--',
		ARTICLE_TRACKBACK_END   => '-->',
	);

	if (-e "$self->{init}->{data_tb_dir}$self->{query}->{no}\.$self->{init}->{data_ext}") {
		open(FH, "$self->{init}->{data_tb_dir}$self->{query}->{no}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_tb_dir}$self->{query}->{no}\.$self->{init}->{data_ext}");
		while (<FH>) {
			chomp;
			my($no, $pno, $stat, $date, $blog, $title, $url, $excerpt) = split(/\t/);

			$self->{html}->{contents} .= $skin_ins->get_replace_data(
				'trackback',
				$diary_ins->trackback_article($no, $pno, $stat, $date, $blog, $title, $url, $excerpt)
			);
		}
		close(FH);
	}

	$self->{html}->{contents} .= $skin_ins->get_replace_data(
		'trackback_foot',
		ARTICLE_TRACKBACK_START => '<!--',
		ARTICLE_TRACKBACK_END   => '-->',
	);

	if ($self->{config}->{show_navigation} == 2) {
		$self->{init}->{js_navi_end_file} =~ s/^\.\///;
		$self->{html}->{footer} = "<script type=\"text/javascript\" src=\"$self->{config}->{site_url}$self->{init}->{js_navi_end_file}\"></script>\n";
	}

	$self->{html}->{footer} .= $skin_ins->get_data('footer');

	print $self->header;
	foreach ($skin_ins->get_list) {
		print $self->{html}->{$_};
	}

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

1;
