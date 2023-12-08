#webliberty::App::List.pm (2009/04/06)
#Copyright(C) 2002-2009 Knight, All rights reserved.

package webliberty::App::List;

use strict;
use base qw(webliberty::Basis);
use webliberty::Encoder;
use webliberty::File;
use webliberty::Date;
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
		field  => undef,
		index  => undef,
		tmp    => undef,
		past   => undef,
		html   => undef,
		update => undef
	};
	bless $self, $class;

	my $i;

	open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
	while (<FH>) {
		chomp;

		$self->{field}->{++$i} = $_;

		if ($self->{query}->{field} =~ /[^\d]/ or $self->{query}->{target} =~ /[^\d]/) {
			$self->{tmp}->{field} = $_;

			$_ =~ s/&/&amp;/g;
			$_ =~ s/</&lt;/g;
			$_ =~ s/>/&gt;/g;

			if ($self->{query}->{field} eq $_) {
				$self->{query}->{field} = $i;
			}
			if ($self->{query}->{target} eq $_) {
				$self->{query}->{target} = $i;
			}
		}
	}
	close(FH);

	if ($self->{query}->{field} and $self->{query}->{field} =~ /[^\d]/) {
		$self->error('指定された分類は存在しません。');
	}
	if ($self->{query}->{target} and $self->{query}->{target} =~ /[^\d]/) {
		$self->error('指定された分類は存在しません。');
	}

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if (!$stat) {
			next;
		}

		if ($self->{query}->{no} == $no) {
			$self->{index}->{date} = $date;
			$self->{index}->{no}   = $no;
			$self->{index}->{all}  = $_;
		} elsif ($self->{query}->{id} and $id and $self->{query}->{id} eq $id) {
			$self->{index}->{date} = $date;
			$self->{index}->{no}   = $no;
			$self->{index}->{id}   = $id;
			$self->{index}->{all}  = $_;
		} elsif ($self->{index}->{size} == $self->{query}->{page} * $self->{config}->{page_size}) {
			$self->{index}->{date} = $date;
			$self->{index}->{no}   = $no;
		}

		my $esc_field = quotemeta($self->{field}->{$self->{query}->{field}});
		if (!$self->{query}->{field} or $field =~ /^$esc_field(<|$)/) {
			$self->{index}->{size}++;
		}

		if ($field =~ /<>/) {
			$self->{index}->{field}->{$field}++;
			$self->{index}->{field}->{(split(/<>/, $field))[0]}++;
		} else {
			$self->{index}->{field}->{$field}++;
		}

		$self->{index}->{name}->{$name}++;

		if ($date =~ /^(\d\d\d\d)(\d\d)(\d\d)\d\d\d\d$/) {
			$self->{index}->{calendar}->{"$1$2$3"} = 1;
			$self->{past}->{"$1$2"}++;
		}
	}
	close(FH);

	return $self;
}

### メイン処理
sub run {
	my $self = shift;

	my $tmp = $self->{query}->{field};
	if ($self->{tmp}->{field}) {
		$self->{query}->{field} = $self->{tmp}->{field};
	}

	if ($self->{init}->{rewrite_mode}) {
		my $diary_ins = new webliberty::App::Diary($self->{init}, '', $self->{query});
		$self->{init} = $diary_ins->rewrite(%{$self->{init}->{rewrite}}, data => $self->{index}->{all});
	}

	if ($self->{tmp}->{field}) {
		$self->{query}->{field} = $tmp;
	}

	$self->output_list;

	return;
}

### 記事表示
sub output_list {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $start_file;
	if ($self->{query}->{date} =~ /^(\d\d\d\d)(\d\d)/) {
		$start_file = "$self->{init}->{data_diary_dir}$1$2\.$self->{init}->{data_ext}";
	} elsif ($self->{index}->{date} =~ /^(\d\d\d\d)(\d\d)\d\d\d\d\d\d$/) {
		$start_file = "$self->{init}->{data_diary_dir}$1$2\.$self->{init}->{data_ext}";
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}");
	if ($self->{config}->{pos_navigation}) {
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_navigation}");
	}
	if ($self->{config}->{top_mode} and !$self->{query}->{no} and !$self->{query}->{id} and !$self->{query}->{date} and !$self->{query}->{field} and !$self->{query}->{user}) {
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_top}");
	} elsif (($self->{query}->{date} and $self->{query}->{date} !~ /^\d\d\d\d\d\d\d\d$/) or $self->{query}->{user}) {
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_list}");
	} else {
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_diary}");
	}
	if (!$self->{config}->{pos_navigation}) {
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_navigation}");
	}
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	$self->{html}->{header} = $skin_ins->get_data('header');

	my $navigation_flag;
	if (!$self->{query}->{no} and !$self->{query}->{id}) {
		$navigation_flag = 1;
	}
	if (!$self->{config}->{date_navigation} and $self->{query}->{date}) {
		$navigation_flag = 0;
	}
	if (!$self->{config}->{field_navigation} and $self->{query}->{field}) {
		$navigation_flag = 0;
	}

	if ($navigation_flag) {
		$self->{html}->{logs_head} = $skin_ins->get_data('logs_head');

		if ($self->{config}->{top_mode} and !$self->{query}->{no} and !$self->{query}->{id} and !$self->{query}->{date} and !$self->{query}->{field} and !$self->{query}->{user}) {
			open(FH, $self->{init}->{data_top}) or $self->error("Read Error : $self->{init}->{data_top}");
			my $text = <FH>;
			close(FH);

			my $text_ins = new webliberty::String($text);

			if (!$self->{config}->{top_break}) {
				$text_ins->replace_string('<br />', "\n");
			}

			$text_ins->permit_html;
			if ($self->{config}->{top_break} and $self->{config}->{paragraph_mode}) {
				$text_ins->replace_string('<br /><br />', '</p><p>');
			}

			if ($text_ins->get_string) {
				if ($self->{config}->{top_break}) {
					$text_ins->set_string('<p>' . $text_ins->get_string . '</p>');
				}
			} else {
				$text_ins->set_string('<p>インデックスページのテキストが設定されていません。</p>');
			}

			if ($self->{config}->{autolink_mode}) {
				$text_ins->create_link($self->{config}->{autolink_attribute});
			}

			$self->{html}->{top} = $skin_ins->get_replace_data(
				'top',
				TOP_TEXT => $text_ins->get_string
			);

			$self->{config}->{page_size} = $self->{config}->{top_size};
		}
	} elsif ($self->{config}->{show_navigation}) {
		$self->{init}->{js_navi_start_file} =~ s/^\.\///;
		$self->{html}->{logs_head} = "<script type=\"text/javascript\" src=\"$self->{config}->{site_url}$self->{init}->{js_navi_start_file}\"></script>\n";
	}

	$self->_diary_list($skin_ins, $diary_ins, $start_file);

	if (!$self->{query}->{no} and !$self->{query}->{id} and !$self->{query}->{date}) {
		$self->_page_list($skin_ins);
		$self->_navi_list($skin_ins);
	}
	if ($navigation_flag) {
		$self->{html}->{logs_foot} = $skin_ins->get_data('logs_foot');
	} elsif ($self->{config}->{show_navigation}) {
		$self->{init}->{js_navi_end_file} =~ s/^\.\///;
		$self->{html}->{logs_foot} = "<script type=\"text/javascript\" src=\"$self->{config}->{site_url}$self->{init}->{js_navi_end_file}\"></script>\n";
	}

	if ($navigation_flag) {
		$self->{html}->{navigation_head} = $skin_ins->get_data('navigation_head');
		$self->{html}->{navigation_foot} = $skin_ins->get_data('navigation_foot');

		$self->{html}->{information_head} = $skin_ins->get_data('information_head');
		$self->{html}->{information_foot} = $skin_ins->get_data('information_foot');

		$self->_calendar_navi($skin_ins);
		$self->_menu_navi($skin_ins);
		$self->_field_navi($skin_ins);
		$self->_search_navi($skin_ins);
		$self->_record_navi($skin_ins, $diary_ins);
		$self->_image_navi($skin_ins, $diary_ins);
		$self->_comment_navi($skin_ins, $diary_ins);
		$self->_trackback_navi($skin_ins, $diary_ins);
		$self->_past_navi($skin_ins, $diary_ins);
		$self->_link_navi($skin_ins);
		$self->_profile_navi($skin_ins, $diary_ins);
	}

	$self->{html}->{footer} = $skin_ins->get_data('footer');

	if (*STDOUT eq "*main::STDOUT") {
		print $self->header;
	}
	foreach ($skin_ins->get_list) {
		print $self->{html}->{$_};
	}

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### ナビゲーション取得
sub get_navi {
	my $self = shift;

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_navigation}");

	my $diary_ins  = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	my $plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});

	$diary_ins->{admin} = 0;

	$skin_ins->replace_skin(
		$diary_ins->info,
		$plugin_ins->run
	);

	$self->{html}->{navigation_head} = $skin_ins->get_data('navigation_head');
	$self->{html}->{navigation_foot} = $skin_ins->get_data('navigation_foot');

	$self->{html}->{information_head} = $skin_ins->get_data('information_head');
	$self->{html}->{information_foot} = $skin_ins->get_data('information_foot');

	$self->_calendar_navi($skin_ins);
	$self->_menu_navi($skin_ins);
	$self->_field_navi($skin_ins);
	$self->_search_navi($skin_ins);
	$self->_record_navi($skin_ins, $diary_ins);
	$self->_image_navi($skin_ins, $diary_ins);
	$self->_comment_navi($skin_ins, $diary_ins);
	$self->_trackback_navi($skin_ins, $diary_ins);
	$self->_past_navi($skin_ins, $diary_ins);
	$self->_link_navi($skin_ins);
	$self->_profile_navi($skin_ins, $diary_ins);

	my $navi_data;
	foreach ($skin_ins->get_list) {
		$navi_data .= $self->{html}->{$_};
	}

	return $navi_data;
}

### カレンダー
sub _calendar_navi {
	my $self     = shift;
	my $skin_ins = shift;

	if (!$self->{config}->{show_calendar}) {
		return;
	}

	my $info_path;
	if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
		$info_path = "$self->{config}->{site_url}$1";
	}

	my $this_year;
	my $this_month;
	my $this_day;
	if ($self->{query}->{date} =~ /^(\d\d\d\d)(\d\d)(\d\d)$/) {
		$this_year  = $1;
		$this_month = $2;
		$this_day   = $3;
	} elsif ($self->{query}->{date} =~ /^(\d\d\d\d)(\d\d)$/) {
		$this_year  = $1;
		$this_month = $2;
		$this_day   = 1;
	} elsif ($self->{query}->{date} =~ /^(\d\d\d\d)$/) {
		$this_year  = $1;
		$this_month = 1;
		$this_day   = 1;
	} else {
		my($sec, $min, $hour, $day, $mon, $year) = localtime(time);
		$this_year  = $year + 1900;
		$this_month = $mon + 1;
		$this_day   = $day;
	}

	my $prev_month = $this_month - 1;
	my $prev_year  = $this_year;
	if ($prev_month < 1) {
		$prev_month = 12;
		$prev_year--;
	}

	my $next_month = $this_month + 1;
	my $next_year  = $this_year;
	if ($next_month > 12) {
		$next_month = 1;
		$next_year++;
	}

	$skin_ins->replace_skin(
		CALENDAR_THIS_YEAR  => int($this_year),
		CALENDAR_THIS_MONTH => ${$self->{init}->{months}}[int($this_month) - 1],
		CALENDAR_THIS_DAY   => int($this_day),
		CALENDAR_PREV_YEAR  => sprintf("%04d", $prev_year),
		CALENDAR_PREV_MONTH => sprintf("%02d", $prev_month),
		CALENDAR_NEXT_YEAR  => sprintf("%04d", $next_year),
		CALENDAR_NEXT_MONTH => sprintf("%02d", $next_month)
	);

	#祝日定義（2000年～2020年）
	my %holidays = (
		'2000' => '0101,0110,0211,0320,0429,0503,0504,0505,0717,0918,0923,1009,1103,1123,1223',
		'2001' => '0101,0108,0211,0212,0320,0429,0430,0503,0504,0505,0716,0917,0923,0924,1008,1103,1123,1223,1224',
		'2002' => '0101,0114,0211,0321,0429,0503,0504,0505,0506,0715,0916,0923,1014,1103,1104,1123,1223',
		'2003' => '0101,0113,0211,0321,0429,0503,0504,0505,0721,0915,0923,1013,1103,1123,1124,1223',
		'2004' => '0101,0112,0211,0320,0429,0503,0504,0505,0719,0920,0923,1011,1103,1123,1223',
		'2005' => '0101,0110,0211,0320,0321,0429,0503,0504,0505,0718,0919,0923,1010,1103,1123,1223',
		'2006' => '0101,0102,0109,0211,0321,0429,0503,0504,0505,0717,0918,0923,1009,1103,1123,1223',
		'2007' => '0101,0108,0211,0212,0321,0429,0430,0503,0504,0505,0716,0917,0923,0924,1008,1103,1123,1223,1224',
		'2008' => '0101,0114,0211,0320,0429,0503,0504,0505,0506,0721,0915,0923,1013,1103,1123,1124,1223',
		'2009' => '0101,0112,0211,0320,0429,0503,0504,0505,0506,0720,0921,0922,0923,1012,1103,1123,1223',
		'2010' => '0101,0111,0211,0321,0322,0429,0503,0504,0505,0719,0920,0923,1011,1103,1123,1223',
		'2011' => '0101,0110,0211,0321,0429,0503,0504,0505,0718,0919,0923,1010,1103,1123,1223',
		'2012' => '0101,0102,0109,0211,0320,0429,0430,0503,0504,0505,0716,0917,0922,1008,1103,1123,1223,1224',
		'2013' => '0101,0114,0211,0320,0429,0503,0504,0505,0506,0715,0916,0923,1014,1103,1104,1123,1223',
		'2014' => '0101,0113,0211,0321,0429,0503,0504,0505,0506,0721,0915,0923,1013,1103,1123,1124,1223',
		'2015' => '0101,0112,0211,0321,0429,0503,0504,0505,0506,0720,0921,0922,0923,1012,1103,1123,1223',
		'2016' => '0101,0111,0211,0320,0321,0429,0503,0504,0505,0718,0919,0922,1010,1103,1123,1223',
		'2017' => '0101,0102,0109,0211,0320,0429,0503,0504,0505,0717,0918,0923,1009,1103,1123,1223',
		'2018' => '0101,0108,0211,0212,0321,0429,0430,0503,0504,0505,0716,0917,0923,0924,1008,1103,1123,1223,1224',
		'2019' => '0101,0114,0211,0321,0429,0503,0504,0505,0506,0715,0916,0923,1014,1103,1104,1123,1223',
		'2020' => '0101,0113,0211,0320,0429,0503,0504,0505,0506,0720,0921,0922,1012,1103,1123,1223',
	);

	my $day_ins  = new webliberty::Date;
	my $week_key = $day_ins->get_week(sprintf("%04d-%02d-01", $this_year, $this_month));
	my $last_day = (31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31)[$this_month - 1] + ($this_month == 2 and (($this_year % 4 == 0 and $this_year % 100 != 0) or $this_year % 400 == 0));
	$self->{html}->{calendar_head} = $skin_ins->get_data('calendar_head');

	my($show_flag, $holi_flag, $stat, $day);

	foreach (1 .. 42) {
		if (($_ - 1) % 7 == $week_key) {
			$show_flag = 1;
		}
		if ($day == $last_day) {
			$show_flag = 0;
		}

		if ($_ % 7 == 1) {
			$self->{html}->{calendar_head} .= $skin_ins->get_data('calendar_weekhead');
		}

		if ($show_flag) {
			$day++;

			my $date = sprintf("%04d%02d%02d", $this_year, $this_month, $day);

			if ($_ % 7 == 1) {
				$stat = 'sunday';
			} elsif ($_ % 7 == 0) {
				$stat = 'satday';
			} else {
				$stat = 'day';
			}

			if ($holidays{sprintf("%04d", $this_year)} and index($holidays{sprintf("%04d", $this_year)}, sprintf("%02d%02d", $this_month, $day)) >= 0) {
				$stat = 'sunday';
			}

			my($day_start, $day_end);
			if ($self->{index}->{calendar}->{$date}) {
				$day_start = "<a href=\"$info_path?date=$date\">";
				$day_end   = "</a>";
			} else {
				$day_start = '';
				$day_end   = '';
			}

			if ($stat eq 'sunday') {
				$self->{html}->{calendar_head} .= $skin_ins->get_replace_data(
					'calendar_sunday',
					CALENDAR_DAY       => $day,
					CALENDAR_DAY_START => $day_start,
					CALENDAR_DAY_END   => $day_end,
					CALENDAR_CODE      => $date
				);
			} elsif ($stat eq 'satday') {
				$self->{html}->{calendar_head} .= $skin_ins->get_replace_data(
					'calendar_satday',
					CALENDAR_DAY       => $day,
					CALENDAR_DAY_START => $day_start,
					CALENDAR_DAY_END   => $day_end,
					CALENDAR_CODE      => $date
				);
			} else {
				$self->{html}->{calendar_head} .= $skin_ins->get_replace_data(
					'calendar_day',
					CALENDAR_DAY       => $day,
					CALENDAR_DAY_START => $day_start,
					CALENDAR_DAY_END   => $day_end,
					CALENDAR_CODE      => $date
				);
			}
		} else {
			$self->{html}->{calendar_head} .= $skin_ins->get_data('calendar_void');
		}

		if ($_ % 7 == 0) {
			$self->{html}->{calendar_head} .= $skin_ins->get_data('calendar_weekfoot');
		}
	}

	$self->{html}->{calendar_head} .= $skin_ins->get_data('calendar_foot');

	return;
}

### コンテンツ一覧
sub _menu_navi {
	my $self     = shift;
	my $skin_ins = shift;

	if (!$self->{config}->{show_menu}) {
		return;
	}

	$self->{html}->{menu} = $skin_ins->get_data('menulist_head');

	open(FH, $self->{init}->{data_menu}) or $self->error("Read Error : $self->{init}->{data_menu}");
	my @menus = <FH>;
	close(FH);

	foreach (@menus) {
		chomp;
		my($field, $name, $url) = split(/\t/);

		if (!$field) {
			$self->{html}->{menu} .= $skin_ins->get_replace_data(
				'menu',
				MENU_NAME => $name,
				MENU_URL  => $url
			);
		}
	}

	foreach my $menu_list (split(/<>/, $self->{config}->{menu_list})) {
		$self->{html}->{menu} .= $skin_ins->get_replace_data(
			'menulist_field',
			MENU_FIELD => $menu_list
		);

		foreach (@menus) {
			chomp;
			my($field, $name, $url) = split(/\t/);

			if ($menu_list eq $field) {
				$self->{html}->{menu} .= $skin_ins->get_data('menu_head');

				$self->{html}->{menu} .= $skin_ins->get_replace_data(
					'menu',
					MENU_NAME => $name,
					MENU_URL  => $url
				);

				$self->{html}->{menu} .= $skin_ins->get_data('menu_foot');
			}
		}

		$self->{html}->{menu} .= $skin_ins->get_data('menulist_delimiter');
	}

	$self->{html}->{menu} .= $skin_ins->get_data('menulist_foot');

	return;
}

### 分類一覧
sub _field_navi {
	my $self     = shift;
	my $skin_ins = shift;

	if (!$self->{config}->{show_field}) {
		return;
	}

	$self->{html}->{field} = $skin_ins->get_data('field_head');

	my($parent_flag, $child_flag, $i);

	open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
	while (<FH>) {
		chomp;

		my $fcode_ins = new webliberty::Encoder($_);

		if ($_ =~ /^(.+)<>(.+)$/) {
			if (!$child_flag) {
				$self->{html}->{field} .= $skin_ins->get_data('child_head');
				$child_flag = 1;
			}

			$self->{html}->{field} .= $skin_ins->get_replace_data(
				'child',
				FIELD_NAME   => $2,
				FIELD_PARENT => $1,
				FIELD_NO     => ++$i,
				FIELD_CODE   => $fcode_ins->url_encode,
				FIELD_SIZE   => $self->{index}->{field}->{$_} || 0
			);
		} else {
			if ($child_flag) {
				$self->{html}->{field} .= $skin_ins->get_data('child_foot');
				$child_flag = 0;
			}
			if ($parent_flag) {
				$self->{html}->{field} .= $skin_ins->get_data('field_delimiter');
			}

			$self->{html}->{field} .= $skin_ins->get_replace_data(
				'field',
				FIELD_NAME => $_,
				FIELD_NO   => ++$i,
				FIELD_CODE => $fcode_ins->url_encode,
				FIELD_SIZE => $self->{index}->{field}->{$_} || 0
			);

			$parent_flag = 1;
		}
	}
	close(FH);

	if ($child_flag) {
		$self->{html}->{field} .= $skin_ins->get_data('child_foot');
	}
	$self->{html}->{field} .= $skin_ins->get_data('field_delimiter');
	$self->{html}->{field} .= $skin_ins->get_data('field_foot');

	return;
}

### 検索フォーム
sub _search_navi {
	my $self     = shift;
	my $skin_ins = shift;

	if (!$self->{config}->{show_search}) {
		return;
	}

	$self->{html}->{search} = $skin_ins->get_data('search');

	return;
}

### 最近の記事一覧
sub _record_navi {
	my $self       = shift;
	my $skin_ins   = shift;
	my $diary_ins  = shift;

	if (!$self->{config}->{list_size}) {
		return;
	}

	opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
	my @dir = sort { $b <=> $a } readdir(DIR);
	closedir(DIR);

	my $flag = 1;
	my $i;

	$self->{html}->{list} = $skin_ins->get_data('list_head');

	foreach my $entry (@dir) {
		if ($entry !~ /^\d\d\d\d\d\d\.$self->{init}->{data_ext}$/) {
			next;
		}
		if ($flag) {
			open(FH, "$self->{init}->{data_diary_dir}$entry") or $self->error("Read Error : $self->{init}->{data_diary_dir}$entry");
			while (<FH>) {
				chomp;
				my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

				if (!$stat) {
					next;
				}

				$i++;
				if ($i > $self->{config}->{list_size}) {
					$flag = 0;
					last;
				}

				$self->{html}->{list} .= $skin_ins->get_replace_data(
					'list',
					$diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, '', '', $icon, '', $host)
				);
			}
			close(FH);
		}
	}

	$self->{html}->{list} .= $skin_ins->get_data('list_foot');

	return;
}

### 最近の画像一覧
sub _image_navi {
	my $self       = shift;
	my $skin_ins   = shift;
	my $diary_ins  = shift;

	if (!$self->{config}->{image_size} or !$self->{config}->{use_image}) {
		return;
	}

	opendir(DIR, $self->{init}->{data_image_dir}) or $self->error("Read Error : $self->{init}->{data_image_dir}");
	my @files = sort { $a <=> $b } grep { m/\w+\.\w+/g } readdir(DIR);
	close(DIR);

	my %image;
	foreach (@files) {
		my $file_ins  = new webliberty::File("$self->{init}->{data_image_dir}$_");
		my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;

		$image{$file_ins->get_name} = $file_ins->get_ext;
	}

	$self->{html}->{image} = $skin_ins->get_data('image_head');

	my $i;

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if (!$stat) {
			next;
		}

		if (($id and !$image{$id}) or (!$id and !$image{$no})) {
			next;
		}

		$i++;
		if ($i > $self->{config}->{image_size}) {
			last;
		}

		$self->{html}->{image} .= $skin_ins->get_replace_data(
			'image',
			$diary_ins->diary_article($no, $id, $stat, '', '', '', $field, $date, $name, "No.$no", '', '', '', '', '')
		);
	}
	close(FH);

	$self->{html}->{image} .= $skin_ins->get_data('image_foot');

	return;
}

### コメント一覧
sub _comment_navi {
	my $self      = shift;
	my $skin_ins  = shift;
	my $diary_ins = shift;

	if (!$self->{config}->{cmtlist_size}) {
		return;
	}

	$self->{html}->{cmtlist} = $skin_ins->get_data('cmtlist_head');

	my $i;

	open(FH, $self->{init}->{data_comt_index}) or $self->error("Read Error : $self->{init}->{data_comt_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $subj, $host) = split(/\t/);

		$i++;
		if ($i > $self->{config}->{cmtlist_size}) {
			last;
		}

		$self->{html}->{cmtlist} .= $skin_ins->get_replace_data(
			'cmtlist',
			$diary_ins->comment_article($no, $pno, $stat, $date, $name, '', '', $subj, '', '', '', '', '', '', $host)
		);
	}
	close(FH);

	$self->{html}->{cmtlist} .= $skin_ins->get_data('cmtlist_foot');

	return;
}

### トラックバック一覧
sub _trackback_navi {
	my $self      = shift;
	my $skin_ins  = shift;
	my $diary_ins = shift;

	if (!$self->{config}->{tblist_size}) {
		return;
	}

	$self->{html}->{tblist} = $skin_ins->get_data('tblist_head');

	my $i;

	open(FH, $self->{init}->{data_tb_index}) or $self->error("Read Error : $self->{init}->{data_tb_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $blog, $title, $url) = split(/\t/);

		$i++;
		if ($i > $self->{config}->{tblist_size}) {
			last;
		}

		$self->{html}->{tblist} .= $skin_ins->get_replace_data(
			'tblist',
			$diary_ins->trackback_article($no, $pno, $stat, $date, $blog, $title, $url, '')
		);
	}
	close(FH);

	$self->{html}->{tblist} .= $skin_ins->get_data('tblist_foot');

	return;
}

### 過去ログ一覧
sub _past_navi {
	my $self       = shift;
	my $skin_ins   = shift;
	my $diary_ins  = shift;

	if (!$self->{config}->{show_past}) {
		return;
	}

	$self->{html}->{past} = $skin_ins->get_data('past_head');

	foreach my $past (sort { $b <=> $a } keys %{$self->{past}}) {
		if ($past =~ /^(\d\d\d\d)(\d\d)$/) {
			my $past_year  = $1;
			my $past_month = $2;

			$self->{html}->{past} .= $skin_ins->get_replace_data(
				'past',
				PAST_YEAR  => $past_year,
				PAST_MONTH => $past_month,
				PAST_SIZE  => $self->{past}->{$past}
			);
		}
	}

	$self->{html}->{past} .= $skin_ins->get_data('past_foot');

	return;
}

### リンク集
sub _link_navi {
	my $self     = shift;
	my $skin_ins = shift;

	if (!$self->{config}->{show_link}) {
		return;
	}

	$self->{html}->{link} = $skin_ins->get_data('linklist_head');

	open(FH, $self->{init}->{data_link}) or $self->error("Read Error : $self->{init}->{data_link}");
	my @links = <FH>;
	close(FH);

	foreach (@links) {
		chomp;
		my($field, $name, $url) = split(/\t/);

		if (!$field) {
			$self->{html}->{link} .= $skin_ins->get_replace_data(
				'link',
				LINK_NAME => $name,
				LINK_URL  => $url
			);
		}
	}

	foreach my $link_list (split(/<>/, $self->{config}->{link_list})) {
		$self->{html}->{link} .= $skin_ins->get_replace_data(
			'linklist_field',
			LINK_FIELD => $link_list
		);

		foreach (@links) {
			chomp;
			my($field, $name, $url) = split(/\t/);

			if ($link_list eq $field) {
				$self->{html}->{link} .= $skin_ins->get_data('link_head');

				$self->{html}->{link} .= $skin_ins->get_replace_data(
					'link',
					LINK_NAME => $name,
					LINK_URL  => $url
				);

				$self->{html}->{link} .= $skin_ins->get_data('link_foot');
			}
		}

		$self->{html}->{link} .= $skin_ins->get_data('linklist_delimiter');
	}

	$self->{html}->{link} .= $skin_ins->get_data('linklist_foot');

	return;
}

### プロフィール一覧
sub _profile_navi {
	my $self     = shift;
	my $skin_ins = shift;

	if (!$self->{config}->{profile_mode}) {
		return;
	}

	$self->{html}->{profile} = $skin_ins->get_data('profile_head');

	open(FH, $self->{init}->{data_profile}) or $self->error("Read Error : $self->{init}->{data_profile}");
	while (<FH>) {
		chomp;
		my($user, $name, $text) = split(/\t/);

		$self->{html}->{profile} .= $skin_ins->get_replace_data(
			'profile',
			PROFILE_USER => $user,
			PROFILE_NAME => $name,
			PROFILE_TEXT => $text,
			PROFILE_SIZE => $self->{index}->{name}->{$user} || 0
		);
	}
	close(FH);

	$self->{html}->{profile} .= $skin_ins->get_data('profile_foot');

	return;
}

### 記事一覧
sub _diary_list {
	my $self       = shift;
	my $skin_ins   = shift;
	my $diary_ins  = shift;
	my $start_file = shift;

	my $no_ins     = new webliberty::String($self->{query}->{no});
	my $id_ins     = new webliberty::String($self->{query}->{id});
	my $date_ins   = new webliberty::String($self->{query}->{date});
	my $field_ins  = new webliberty::String($self->{query}->{field});
	my $user_ins   = new webliberty::String($self->{query}->{user});
	my $target_ins = new webliberty::String($self->{query}->{target});

	$no_ins->create_number;
	$id_ins->create_line;
	$date_ins->create_number;
	$field_ins->create_number;
	$target_ins->create_number;

	opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
	my @dir = sort { $b <=> $a } readdir(DIR);
	closedir(DIR);

	my($dir_flag, $file_flag, $show_flag, $comt_flag, $tb_flag, $diary_subj, %field_i, $i);

	if (!$self->{config}->{top_mode} or !$self->{config}->{top_field} or $no_ins->get_string or $id_ins->get_string or $date_ins->get_string or $field_ins->get_string or $user_ins->get_string) {
		$self->{html}->{diary} = $skin_ins->get_replace_data(
			'diary_head',
			DIARY_TITLE_START => '',
			DIARY_TITLE_END   => '',
			DIARY_FIELD_START => '<!--',
			DIARY_FIELD_END   => '-->'
		);
	}

	foreach my $entry (@dir) {
		if ($entry !~ /^\d\d\d\d\d\d\.$self->{init}->{data_ext}$/) {
			next;
		}
		if ($start_file eq "$self->{init}->{data_diary_dir}$entry") {
			$dir_flag = 1;
		}
		if ($dir_flag) {
			open(FH, "$self->{init}->{data_diary_dir}$entry") or $self->error("Read Error : $self->{init}->{data_diary_dir}$entry");
			while (<FH>) {
				chomp;
				my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

				if ($date_ins->get_string or $user_ins->get_string or $no == $self->{index}->{no}) {
					$file_flag = 1;
				}
				if ($file_flag and $stat) {
					if ($self->{config}->{top_mode} and $self->{config}->{top_field_list} and !$self->{query}->{no} and !$self->{query}->{id} and !$self->{query}->{date} and !$self->{query}->{field} and !$self->{query}->{user}) {
						my $flag;
						foreach my $field_list (split(/<>/, $self->{config}->{top_field_list})) {
							if ($field_list =~ /^(.+)::(.+)$/) {
								if ($field eq "$1<>$2") {
									$flag = 1;
									last;
								}
							} else {
								if ($field =~ /^$field_list(<>.+)?$/) {
									$flag = 1;
									last;
								}
							}
						}
						if (!$flag) {
							next;
						}
					}

					my $esc_field  = quotemeta($self->{field}->{$self->{query}->{field}});
					my $esc_target = quotemeta($self->{field}->{$self->{query}->{target}});

					if ($no_ins->get_string and $no != $no_ins->get_string) {
						next;
					}
					if ($id_ins->get_string and $id ne $id_ins->get_string) {
						next;
					}
					if ($date_ins->get_string and $date !~ /^$self->{query}->{date}/) {
						next;
					}
					if ($field_ins->get_string and $field !~ /^$esc_field(<|$)/) {
						next;
					}
					if ($user_ins->get_string and $name ne $user_ins->get_string) {
						next;
					}
					if ($target_ins->get_string and $field !~ /^$esc_target(<|$)/) {
						next;
					}
					if (!$field_ins->get_string or $field =~ /^$esc_field(<|$)/) {
						$i++;
					}

					my $top_field;
					if ($field =~ /^(.+)<>.+$/) {
						$top_field = $1;
					} else {
						$top_field = $field;
					}
					if ($self->{config}->{top_mode} and $self->{config}->{top_field} and !$no_ins->get_string and !$id_ins->get_string and !$date_ins->get_string and !$field_ins->get_string and !$user_ins->get_string) {
						$field_i{$top_field}++;

						if (!$target_ins->get_string and $field_i{$top_field} > $self->{config}->{page_size}) {
							next;
						}
					}

					if ($self->{config}->{top_mode} and $self->{config}->{top_field} and !$no_ins->get_string and !$id_ins->get_string and !$date_ins->get_string and !$field_ins->get_string and !$user_ins->get_string) {
						$self->{html}->{'diary' . $top_field} .= $skin_ins->get_replace_data(
							'diary',
							$diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host)
						);
					} else {
						$self->{html}->{diary} .= $skin_ins->get_replace_data(
							'diary',
							$diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host)
						);
					}

					$show_flag = 1;

					if (!$no_ins->get_string and !$id_ins->get_string) {
						if ($self->{config}->{show_tb} and $tb) {
							if (-s "$self->{init}->{data_tb_dir}$no\.$self->{init}->{data_ext}") {
								$self->{html}->{diary} .= $skin_ins->get_replace_data(
									'tb_head',
									ARTICLE_NO => $no
								);

								open(TB, "$self->{init}->{data_tb_dir}$no\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_tb_dir}$self->{index}->{no}\.$self->{init}->{data_ext}");
								while (<TB>) {
									chomp;
									my($no, $pno, $stat, $date, $blog, $title, $url, $excerpt) = split(/\t/);

									$self->{html}->{diary} .= $skin_ins->get_replace_data(
										'tb',
										$diary_ins->trackback_article($no, $pno, $stat, $date, $blog, $title, $url, $excerpt)
									);
								}
								close(TB);

								$self->{html}->{diary} .= $skin_ins->get_replace_data(
									'tb_foot',
									ARTICLE_NO => $no
								);
							}
						}
						if ($self->{config}->{show_comt} and $comt) {
							if (-s "$self->{init}->{data_comt_dir}$no\.$self->{init}->{data_ext}") {
								$self->{html}->{diary} .= $skin_ins->get_replace_data(
									'cmt_head',
									ARTICLE_NO => $no
								);

								open(COMT, "$self->{init}->{data_comt_dir}$no\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}$self->{index}->{no}\.$self->{init}->{data_ext}");
								while (<COMT>) {
									chomp;
									my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

									$self->{html}->{diary} .= $skin_ins->get_replace_data(
										'cmt',
										$diary_ins->comment_article($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host)
									);
								}
								close(COMT);

								$self->{html}->{diary} .= $skin_ins->get_replace_data(
									'cmt_foot',
									ARTICLE_NO => $no
								);
							}
						}
					}

					if (!$self->{config}->{top_mode} or $no_ins->get_string or $id_ins->get_string or $date_ins->get_string or $field_ins->get_string or $user_ins->get_string) {
						$self->{html}->{diary} .= $skin_ins->get_replace_data('diary_delimiter');
					}

					if (!$date_ins->get_string and !$user_ins->get_string and !$target_ins->get_string) {
						if ($no_ins->get_string or $id_ins->get_string or $i >= $self->{config}->{page_size}) {
							if ($no_ins->get_string or $id_ins->get_string or $field_ins->get_string or !$self->{config}->{top_mode} or !$self->{config}->{top_field}) {
								if ($no_ins->get_string or $id_ins->get_string) {
									$comt_flag  = $comt;
									$tb_flag    = $tb;
									$diary_subj = $subj;
								}

								$dir_flag  = 0;
								$file_flag = 0;

								last;
							}
						}
					}
				}
			}
			close(FH);
		}
	}
	if (!$show_flag) {
		$self->{html}->{diary} .= $skin_ins->get_replace_data(
			'diary',
			$diary_ins->diary_article(0, '', 1, 1, 0, 0, '未分類', '', '', 'No Data', '該当する記事はありません。', '', '', '', '')
		);
	}

	if ($show_flag and ($no_ins->get_string or $id_ins->get_string)) {
		if ($self->{config}->{title_mode} == 3) {
			$self->{html}->{header} =~ s/<title(.*)>(.+)<\/title>/<title$1>$diary_subj<\/title>/i;
		} elsif ($self->{config}->{title_mode} == 2) {
			$self->{html}->{header} =~ s/<title(.*)>(.+)<\/title>/<title$1>$diary_subj \- $2<\/title>/i;
		} elsif ($self->{config}->{title_mode} == 1) {
			$self->{html}->{header} =~ s/<title(.*)>(.+)<\/title>/<title$1>$2 \- $diary_subj<\/title>/i;
		}
	}

	if ($show_flag and ($no_ins->get_string or $id_ins->get_string) and $tb_flag) {
		$self->{html}->{trackback} = $skin_ins->get_replace_data(
			'trackback_head',
			ARTICLE_NO => $self->{index}->{no}
		);

		if (-s "$self->{init}->{data_tb_dir}$self->{index}->{no}\.$self->{init}->{data_ext}") {
			open(FH, "$self->{init}->{data_tb_dir}$self->{index}->{no}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_tb_dir}$self->{index}->{no}\.$self->{init}->{data_ext}");
			while (<FH>) {
				chomp;
				my($no, $pno, $stat, $date, $blog, $title, $url, $excerpt) = split(/\t/);

				$self->{html}->{trackback} .= $skin_ins->get_replace_data(
					'trackback',
					$diary_ins->trackback_article($no, $pno, $stat, $date, $blog, $title, $url, $excerpt)
				);
			}
			close(FH);
		}

		$self->{html}->{trackback} .= $skin_ins->get_replace_data(
			'trackback_foot',
			ARTICLE_NO => $self->{index}->{no}
		);
	}

	if ($show_flag and ($no_ins->get_string or $id_ins->get_string) and $comt_flag) {
		$self->{html}->{comment} = $skin_ins->get_replace_data(
			'comment_head',
			ARTICLE_NO => $self->{index}->{no}
		);

		if (-s "$self->{init}->{data_comt_dir}$self->{index}->{no}\.$self->{init}->{data_ext}") {
			open(FH, "$self->{init}->{data_comt_dir}$self->{index}->{no}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}$self->{index}->{no}\.$self->{init}->{data_ext}");
			while (<FH>) {
				chomp;
				my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

				$self->{html}->{comment} .= $skin_ins->get_replace_data(
					'comment',
					$diary_ins->comment_article($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host)
				);
			}
			close(FH);
		}

		$self->{html}->{comment} .= $skin_ins->get_replace_data(
			'comment_foot',
			ARTICLE_NO => $self->{index}->{no}
		);
	}

	if (!$self->{config}->{top_mode} or !$self->{config}->{top_field} or $no_ins->get_string or $id_ins->get_string or $date_ins->get_string or $field_ins->get_string or $user_ins->get_string) {
		$self->{html}->{diary} .= $skin_ins->get_data('diary_foot');
	}

	if ($self->{config}->{top_mode} and $self->{config}->{top_field} and !$no_ins->get_string and !$id_ins->get_string and !$date_ins->get_string and !$field_ins->get_string and !$user_ins->get_string) {
		if ($self->{html}->{diary}) {
			$self->{html}->{diary} = $skin_ins->get_replace_data(
				'diary_head',
				DIARY_TITLE_START => '<!--',
				DIARY_TITLE_END   => '-->',
				DIARY_FIELD       => '未分類',
				DIARY_FNO         => '',
				DIARY_FIELD_START => '',
				DIARY_FIELD_END   => ''
			) . $self->{html}->{diary} . $skin_ins->get_replace_data(
				'diary_foot',
				DIARY_TITLE_START => '<!--',
				DIARY_TITLE_END   => '-->',
				DIARY_FIELD       => '未分類',
				DIARY_FNO         => '',
				DIARY_FIELD_START => '',
				DIARY_FIELD_END   => ''
			);
		}

		my $i;

		open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
		while (<FH>) {
			chomp;

			$i++;

			if ($_ =~ /^.+<>.+$/) {
				next;
			}
			if ($target_ins->get_string and $self->{field}->{$self->{query}->{target}} !~ /^$_(<|$)/) {
				next;
			}
			if (!$self->{html}->{'diary' . $_}) {
				next;
			}

			my $show_field;
			if ($self->{field}->{$self->{query}->{target}} =~ /^(.+)<>(.+)$/) {
				$show_field = "$1::$2";
			} else {
				$show_field = $_;
			}

			my $fcode_ins = new webliberty::Encoder($_);

			my($diary_target_start, $diary_target_end);
			if ($target_ins->get_string) {
				$diary_target_start = '<!--';
				$diary_target_end   = '-->';
			}

			$self->{html}->{diary} .= $skin_ins->get_replace_data(
				'diary_head',
				DIARY_TITLE_START  => '<!--',
				DIARY_TITLE_END    => '-->',
				DIARY_FIELD        => $show_field,
				DIARY_FNO          => $i,
				DIARY_FCODE        => $fcode_ins->url_encode,
				DIARY_FIELD_START  => '',
				DIARY_FIELD_END    => '',
				DIARY_TARGET_START => $diary_target_start,
				DIARY_TARGET_END   => $diary_target_end
			);
			$self->{html}->{diary} .= $self->{html}->{'diary' . $_};
			$self->{html}->{diary} .= $skin_ins->get_replace_data(
				'diary_foot',
				DIARY_TITLE_START  => '<!--',
				DIARY_TITLE_END    => '-->',
				DIARY_FIELD        => $show_field,
				DIARY_FNO          => $i,
				DIARY_FCODE        => $fcode_ins->url_encode,
				DIARY_FIELD_START  => '',
				DIARY_FIELD_END    => '',
				DIARY_TARGET_START => $diary_target_start,
				DIARY_TARGET_END   => $diary_target_end
			);
		}
		close(FH);
	}

	return;
}

### ページ移動リンク一覧
sub _page_list {
	my $self     = shift;
	my $skin_ins = shift;

	if (!$self->{config}->{page_size}) {
		return;
	}

	my $field_ins = new webliberty::String($self->{query}->{field});
	my $page_ins  = new webliberty::String($self->{query}->{page});

	$field_ins->create_number;
	$page_ins->create_number;

	if ($self->{tmp}->{field}) {
		my $fcode_ins = new webliberty::Encoder($self->{tmp}->{field});
		$field_ins->set_string($fcode_ins->url_encode);
	}

	my $info_path;
	if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
		$info_path = "$self->{config}->{site_url}$1";
	}

	my($field_link, $prev_start, $prev_end, $next_start, $next_end);

	if ($field_ins->get_string) {
		$field_link = '&amp;field=' . $field_ins->get_string;
	}

	if ($page_ins->get_string > 0) {
		$prev_start = "<a href=\"$info_path?page=" . ($page_ins->get_string - 1) . "$field_link\">";
		$prev_end   = "</a>";
	}
	if (int(($self->{index}->{size} - 1) / $self->{config}->{page_size}) > $page_ins->get_string) {
		$next_start = "<a href=\"$info_path?page=" . ($page_ins->get_string + 1) . "$field_link\">";
		$next_end   = "</a>";
	}

	$self->{html}->{page} = $skin_ins->get_replace_data(
		'page',
		PAGE_PREV_START => $prev_start,
		PAGE_PREV_END   => $prev_end,
		PAGE_NEXT_START => $next_start,
		PAGE_NEXT_END   => $next_end
	);

	return;
}

### ナビゲーション一覧
sub _navi_list {
	my $self     = shift;
	my $skin_ins = shift;

	if (!$self->{config}->{page_size} or !$self->{config}->{navi_size}) {
		return;
	}

	my $field_ins = new webliberty::String($self->{query}->{field});
	my $page_ins  = new webliberty::String($self->{query}->{page});

	$field_ins->create_number;
	$page_ins->create_number;

	if ($self->{tmp}->{field}) {
		my $fcode_ins = new webliberty::Encoder($self->{tmp}->{field});
		$field_ins->set_string($fcode_ins->url_encode);
	}

	my $info_path;
	if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
		$info_path = "$self->{config}->{site_url}$1";
	}

	my $all_page = int(($self->{index}->{size} - 1) / $self->{config}->{page_size});

	my($start_page, $end_page);
	if ($all_page > $self->{config}->{navi_size}) {
		$start_page = $page_ins->get_string - int($self->{config}->{navi_size} / 2) + 1;
		$end_page   = $start_page + $self->{config}->{navi_size} - 1;
		if ($start_page < 0) {
			$start_page = 0;
			$end_page   = $self->{config}->{navi_size} - 1;
		}
		if ($end_page > $all_page) {
			$start_page = $all_page - $self->{config}->{navi_size} + 1;
			$end_page   = $all_page;
		}
	} else {
		$start_page = 0;
		$end_page   = $all_page;
	}

	$self->{html}->{navi} = $skin_ins->get_data('navi_head');

	foreach ($start_page .. $end_page) {
		my($field_link, $navi_start, $navi_end);

		if ($field_ins->get_string) {
			$field_link = '&amp;field=' . $field_ins->get_string;
		}

		if ($_ != $page_ins->get_string) {
			$navi_start = "<a href=\"$info_path?page=$_$field_link\">";
			$navi_end   = "</a>";
		}

		if ($_ == $start_page and $start_page != 0) {
			$self->{html}->{navi} .= $skin_ins->get_replace_data(
				'navi_more',
				NAVI_START => "<a href=\"$info_path?page=" . ($_ - 1) . "$field_link\">",
				NAVI_END   => "</a>",
			);
		}

		$self->{html}->{navi} .= $skin_ins->get_replace_data(
			'navi',
			NAVI_NO    => $_ + 1,
			NAVI_START => $navi_start,
			NAVI_END   => $navi_end,
		);

		if ($_ == $end_page and $end_page != $all_page) {
			$self->{html}->{navi} .= $skin_ins->get_replace_data(
				'navi_more',
				NAVI_START => "<a href=\"$info_path?page=" . ($_ + 1) . "$field_link\">",
				NAVI_END   => "</a>",
			);
		}
	}

	$self->{html}->{navi} .= $skin_ins->get_data('navi_foot');

	return;
}

1;
