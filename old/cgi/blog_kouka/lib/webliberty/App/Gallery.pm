#webliberty::App::Gallery.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Gallery;

use strict;
use base qw(webliberty::Basis);
use webliberty::File;
use webliberty::Skin;
use webliberty::Cookie;
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
	if (!-e $self->{init}->{spainter_jar} and !-e $self->{init}->{paintbbs_jar}) {
		$self->error('不正なアクセスです。');
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

	my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_admin}, $self->{init}->{des_key});
	my $info_user  = $cookie_ins->get_cookie('admin_user');

	my $page_ins  = new webliberty::String($self->{query}->{page});
	$page_ins->create_number;

	#イラストの有無をチェック
	opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
	my @dir = sort { $b <=> $a } readdir(DIR);
	closedir(DIR);

	my(%display, %subj, $size);

	foreach my $entry (@dir) {
		if ($entry !~ /^\d\d\d\d\d\d\.$self->{init}->{data_ext}$/) {
			next;
		}

		open(FH, "$self->{init}->{data_diary_dir}$entry") or $self->error("Read Error : $self->{init}->{data_diary_dir}$entry");
		while (<FH>) {
			chomp;
			my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

			if ($stat and $text =~ /\$PAINT(\d+)/) {
				if (!$display{$1}) {
					if ($id) {
						$display{$1} = $id;
					} else {
						$display{$1} = $no;
					}

					$size++;
				}
				if (!$subj{$1}) {
					$subj{$1} = $subj;
				}
			}
		}
		close(FH);
	}

	#ページ表示準備
	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_gallery}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	my($info_user_start, $info_user_end);
	if (!$info_user) {
		$info_user_start = '<!--';
		$info_user_end   = '-->';
	}

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_USER       => $info_user,
		INFO_USER_START => $info_user_start,
		INFO_USER_END   => $info_user_end
	);

	my $file_path;
	if ($self->{init}->{paint_path}) {
		$file_path = $self->{init}->{paint_path};
	} else {
		$file_path = $self->{init}->{paint_dir};
	}

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('gallery_head');

	#イラスト一覧表示
	opendir(DIR, $self->{init}->{paint_dir}) or $self->error("Read Error : $self->{init}->{paint_dir}");
	my @files = sort { $b <=> $a } grep { m/\d+\.\w+/g } readdir(DIR);
	closedir(DIR);

	my $page_start = $self->{config}->{gallery_size} * $page_ins->get_string;
	my $page_end   = $page_start + $self->{config}->{gallery_size};

	my $i;

	foreach my $file (@files) {
		if ($file =~ /^(\d+)\.\w+$/) {
			my $display = $display{$1};
			my $subj    = $subj{$1};
			my $article_url;

			if ($display{$1} or $info_user) {
				$i++;
				if ($i <= $page_start) {
					next;
				} elsif ($i > $page_end) {
					last;
				}

				if (!$display{$1}) {
					if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
						$article_url = "$self->{config}->{site_url}$1?mode=admin&amp;work=paint";
					}
					$subj = '非公開';
				} elsif ($self->{config}->{html_archive_mode}) {
					if ($self->{init}->{archive_path}) {
						$article_url = "$self->{init}->{archive_path}$display\.$self->{init}->{archive_ext}";
					} elsif ($self->{init}->{archive_dir} =~ /([^\/\\]*\/)$/) {
						$article_url = "$self->{config}->{site_url}$1$display\.$self->{init}->{archive_ext}";
					}
				} else {
					if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
						if ($display{$1} =~ /^\d/) {
							$article_url = "$self->{config}->{site_url}$1?id=$display";
						} else {
							$article_url = "$self->{config}->{site_url}$1?no=$display";
						}
					}
				}
			} else {
				next;
			}

			my $file_ins  = new webliberty::File("$self->{init}->{paint_dir}$file");
			my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;
			my($file_width, $file_height) = $file_ins->get_size;

			my $org_width  = $file_width;
			my $org_height = $file_height;

			if ($file_width > $self->{config}->{gallery_maxwidth}) {
				$file_height = int($file_height / ($file_width / $self->{config}->{gallery_maxwidth}));
				$file_width  = $self->{config}->{gallery_maxwidth};
			}

			my $image = "<a href=\"$article_url\"><img src=\"$file_path$file_name\" alt=\"$subj （$file_name）\" width=\"$file_width\" height=\"$file_height\" /></a>";

			print $skin_ins->get_replace_data(
				'gallery',
				GALLERY_IMAGE  => $image,
				GALLERY_FILE   => $file_name,
				GALLERY_WIDTH  => $org_width,
				GALLERY_HEIGHT => $org_height,
				GALLERY_SUBJ   => $subj,
				GALLERY_URL    => $article_url
			);

			if ($i % $self->{config}->{gallery_delimiter_size} == 0 and $i - $page_ins->get_string * $self->{config}->{gallery_size} != $self->{config}->{gallery_size}) {
				print $skin_ins->get_data('gallery_delimiter');
			}
		}
	}

	print $skin_ins->get_data('gallery_foot');

	my $info_path;
	if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
		$info_path = "$self->{config}->{site_url}$1";
	}

	my($prev_start, $prev_end, $next_start, $next_end);
	if ($page_ins->get_string > 0) {
		$prev_start = "<a href=\"$info_path?mode=gallery&amp;page=" . ($page_ins->get_string - 1) . "\">";
		$prev_end   = "</a>";
	}
	if (int(($size - 1) / $self->{config}->{gallery_size}) > $page_ins->get_string) {
		$next_start = "<a href=\"$info_path?mode=gallery&amp;page=" . ($page_ins->get_string + 1) . "\">";
		$next_end   = "</a>";
	}

	print $skin_ins->get_replace_data(
		'page',
		PAGE_PREV_START => $prev_start,
		PAGE_PREV_END   => $prev_end,
		PAGE_NEXT_START => $next_start,
		PAGE_NEXT_END   => $next_end
	);

	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

1;
