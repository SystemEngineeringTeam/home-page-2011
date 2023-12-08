#webliberty::App::Album.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Album;

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

	$self->output;

	return;
}

### ファイル表示
sub output {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_admin}, $self->{init}->{des_key});
	my $info_user  = $cookie_ins->get_cookie('admin_user');

	my $page_ins    = new webliberty::String($self->{query}->{page});
	$page_ins->create_number;

	#ファイルの有無をチェック
	opendir(DIR, $self->{init}->{data_image_dir}) or $self->error("Read Error : $self->{init}->{data_image_dir}");
	my @files = sort { $a <=> $b } grep { m/\w+\.\w+/g } readdir(DIR);
	close(DIR);

	my %image;
	foreach (@files) {
		my $file_ins  = new webliberty::File("$self->{init}->{data_image_dir}$_");
		my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;

		$image{$file_ins->get_name} = $file_ins->get_ext;
	}

	#イラストの有無をチェック
	opendir(DIR, $self->{init}->{paint_dir}) or $self->error("Read Error : $self->{init}->{paint_dir}");
	@files = sort { $a <=> $b } grep { m/\d+\.\w+/g } readdir(DIR);
	close(DIR);

	my %paint;
	foreach (@files) {
		my $file_ins  = new webliberty::File("$self->{init}->{paint_dir}$_");
		my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;

		$paint{$file_ins->get_name} = $file_ins->get_ext;
	}

	#ページ表示準備
	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_album}");
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

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('album_head');

	#ファイル一覧表示
	opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
	my @dir = sort { $b <=> $a } readdir(DIR);
	closedir(DIR);

	my $page_start = $self->{config}->{album_size} * $page_ins->get_string;
	my $page_end   = $page_start + $self->{config}->{album_size};

	my(%display, $i);

	my $upfile_path;
	if ($self->{init}->{data_upfile_path}) {
		$upfile_path = $self->{init}->{data_upfile_path};
	} else {
		$upfile_path = $self->{init}->{data_upfile_dir};
	}

	my $thumbnail_path;
	if ($self->{init}->{data_thumbnail_path}) {
		$thumbnail_path = $self->{init}->{data_thumbnail_path};
	} else {
		$thumbnail_path = $self->{init}->{data_thumbnail_dir};
	}

	my $paint_path;
	if ($self->{init}->{paint_path}) {
		$paint_path = $self->{init}->{paint_path};
	} else {
		$paint_path = $self->{init}->{paint_dir};
	}

	foreach my $entry (@dir) {
		if ($entry !~ /^\d\d\d\d\d\d\.$self->{init}->{data_ext}$/) {
			next;
		}

		open(FH, "$self->{init}->{data_diary_dir}$entry") or $self->error("Read Error : $self->{init}->{data_diary_dir}$entry");
		while (<FH>) {
			chomp;
			my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

			if (!$stat and !$info_user) {
				next;
			}
			if (!$file and (($id and !$image{$id}) or (!$id and !$image{$no})) and $text !~ /\$PAINT(\d+)/) {
				next;
			}

			my %flag;

			if ($file) {
				$file = (split(/<>/, $file))[0];

				my $file_ins = new webliberty::File("$self->{init}->{data_upfile_dir}$file");
				my($width, $height) = $file_ins->get_size;

				my $file_path;

				if ($width > $self->{config}->{img_maxwidth}) {
					$height = int($height / ($width / $self->{config}->{img_maxwidth}));
					$width  = $self->{config}->{img_maxwidth};

					if ($self->{config}->{thumbnail_mode}) {
						$file_path = $thumbnail_path;
					} else {
						$file_path = $upfile_path;
					}
				} else {
					my $ext = $file_ins->get_ext;

					$file_path = $upfile_path;

					if ($ext eq 'midi' or $ext eq 'mid' or $ext eq 'mp3' or $ext eq 'wav') {
						$flag{'music'} = 1;
					}
				}

				if ($width > 0 and $height > 0) {
					$file = "<img src=\"$file_path$file\" alt=\"No.$no $subj\" width=\"$width\" height=\"$height\" />";

					$flag{'image'} = 1;
				}
			}

			if ($text =~ /\$PAINT(\d+)/) {
				my $file_ins  = new webliberty::File("$self->{init}->{paint_dir}$1\.$paint{$1}");
				my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;
				my($width, $height) = $file_ins->get_size;

				if ($width > $self->{config}->{img_maxwidth}) {
					$height = int($height / ($width / $self->{config}->{img_maxwidth}));
					$width  = $self->{config}->{img_maxwidth};
				}

				$file = "<img src=\"$paint_path$file_name\" alt=\"No.$no $subj\" width=\"$width\" height=\"$height\" />";

				$flag{'image'} = 1;
			}

			my $image;
			if ($id and $image{$id}) {
				$image = $id . '.' . $image{$id};
			} elsif ($image{$no}) {
				$image = $no . '.' . $image{$no};
			}
			if ($image) {
				$image = "<img src=\"$self->{init}->{data_image_dir}$image\" alt=\"No.$no $subj\" />";

				$flag{'mini'}  = 1;
				$flag{'image'} = 1;
			}

			if ($self->{config}->{album_target} and !$flag{$self->{config}->{album_target}}) {
				next;
			}

			$i++;
			if ($i <= $page_start) {
				next;
			} elsif ($i > $page_end) {
				last;
			}

			my $info;
			if ($image) {
				$info = $image;
			} elsif ($file) {
				$info = $file;
			}

			print $skin_ins->get_replace_data(
				'album',
				$diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host),
				ALBUM_FILE  => $file,
				ALBUM_IMAGE => $image,
				ALBUM_INFO  => $info
			);

			if ($i % $self->{config}->{album_delimiter_size} == 0 and $i - $page_ins->get_string * $self->{config}->{album_size} != $self->{config}->{album_size}) {
				print $skin_ins->get_data('album_delimiter');
			}
		}
		close(FH);
	}

	print $skin_ins->get_data('album_foot');

	my $info_path;
	if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
		$info_path = "$self->{config}->{site_url}$1";
	}

	my($prev_start, $prev_end, $next_start, $next_end);
	if ($page_ins->get_string > 0) {
		$prev_start = "<a href=\"$info_path?mode=album&amp;target=" . $self->{config}->{album_target} . "&amp;page=" . ($page_ins->get_string - 1) . "\">";
		$prev_end   = "</a>";
	}
	if (int(($i - 1) / $self->{config}->{album_size}) > $page_ins->get_string) {
		$next_start = "<a href=\"$info_path?mode=album&amp;target=" . $self->{config}->{album_target} . "&amp;page=" . ($page_ins->get_string + 1) . "\">";
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
