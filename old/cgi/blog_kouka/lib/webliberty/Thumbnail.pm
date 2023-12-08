#webliberty::Thumbnail.pm (2006/05/15)
#Copyright(C) 2002-2006 Knight, All rights reserved.

package webliberty::Thumbnail;

use strict;
use webliberty::File;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		resize_pl     => undef,
		file_dir      => undef,
		thumbnail_dir => undef,
		img_max_width => undef,
		limit         => undef
	};
	bless $self, $class;

	return $self;
}

### サムネイル画像作成
sub create_thumbnail {
	my $self = shift;
	my %args = @_;

	$self->{resize_pl}     = $args{'resize_pl'};
	$self->{file_dir}      = $args{'file_dir'};
	$self->{thumbnail_dir} = $args{'thumbnail_dir'};
	$self->{img_max_width} = $args{'img_max_width'};
	$self->{limit}         = $args{'limit'};

	if ($self->{resize_pl}) {
		require $self->{resize_pl};
	} else {
		require Image::Magick;
	}

	opendir(DIR, $self->{file_dir}) or return(0, "Read Error : $self->{file_dir}");
	my @dir = sort { $b <=> $a } readdir(DIR);
	closedir(DIR);

	foreach (@dir) {
		my $file_ins  = new webliberty::File("$self->{file_dir}$_");
		my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;
		my($width, $height) = $file_ins->get_size;

		if ($file_ins->get_ext ne 'gif' and $file_ins->get_ext ne 'jpeg' and $file_ins->get_ext ne 'jpg' and $file_ins->get_ext ne 'jpe' and $file_ins->get_ext ne 'png') {
			next;
		}
		if (-e "$self->{thumbnail_dir}$file_name") {
			next;
		}
		if ($width < $self->{img_max_width}) {
			next;
		}
		if ($self->{limit}-- < 0) {
			last
		}

		if ($self->{resize_pl}) {
			my $flag = imgbbs::imgresize("$self->{file_dir}$file_name", "$self->{thumbnail_dir}$file_name", $self->{img_max_width}, int($height / ($width / $self->{img_max_width})));
			if (!$flag) {
				return(0, "Resize Error : $file_name");
			}
		} else {
			my $image_ins = new Image::Magick;
			$image_ins->Read("$self->{file_dir}$file_name");
			$image_ins = $image_ins->Transform(geometry => $self->{img_max_width});
			$image_ins->Write("$self->{thumbnail_dir}$file_name");
		}
	}

	return 1;
}

1;
