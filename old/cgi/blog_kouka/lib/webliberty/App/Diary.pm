#webliberty::App::Diary.pm (2008/07/05)
#Copyright(C) 2002-2008 Knight, All rights reserved.

package webliberty::App::Diary;

use strict;
use base qw(webliberty::Basis Exporter);
use vars qw(@EXPORT);
use webliberty::String;
use webliberty::Decoration;
use webliberty::Encoder;
use webliberty::File;
use webliberty::Date;
use webliberty::Cookie;
use webliberty::Skin;
use webliberty::Script;
use webliberty::Plugin;
use webliberty::App::Init;

@EXPORT = qw(error);

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init   => shift,
		config => shift,
		query  => shift,
		agent  => undef,
		field  => undef,
		user   => undef,
		image  => undef,
		admin  => undef
	};
	bless $self, $class;

	if ($self->{config}) {
		my $i;

		open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
		while (<FH>) {
			chomp;

			$self->{field}->{$_} = ++$i;
		}
		close(FH);

		if ($self->{config}->{user_mode}) {
			open(FH, $self->{init}->{data_profile}) or $self->error("Read Error : $self->{init}->{data_profile}");
			while (<FH>) {
				chomp;
				my($user, $name, $text) = split(/\t/);
		
				$self->{user}->{$user} = $name;
			}
			close(FH);
		}

		if ($self->{config}->{use_image}) {
			opendir(DIR, $self->{init}->{data_image_dir}) or $self->error("Read Error : $self->{init}->{data_image_dir}");
			my @files = sort { $a <=> $b } grep { m/\w+\.\w+/g } readdir(DIR);
			close(DIR);

			foreach (@files) {
				my $file_ins  = new webliberty::File("$self->{init}->{data_image_dir}$_");
				my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;

				$self->{image}->{$file_ins->get_name} = $file_ins->get_ext;
			}
		}

		my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_admin}, $self->{init}->{des_key});
		if ($cookie_ins->get_cookie('admin_user')) {
			my %pwd;

			open(FH, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
			while (<FH>) {
				chomp;
				my($user, $pwd, $authority) = split(/\t/);

				$pwd{$user} = $pwd;
			}
			close(FH);

			my $pwd_ins = new webliberty::String($cookie_ins->get_cookie('admin_pwd'));
			if ($pwd_ins->get_string and $pwd_ins->check_password($pwd{$cookie_ins->get_cookie('admin_user')})) {
				$self->{admin} = 1;
			}
		}
	}

	return $self;
}

### 閲覧環境設定
sub set_agent {
	my $self  = shift;
	my $agent = shift;

	$self->{agent} = $agent;

	return;
}

### 基本情報作成
sub info {
	my $self = shift;

	my $page_ins = new webliberty::String($self->{query}->{page});
	$page_ins->create_number;

	my $info_path;
	if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
		$info_path = "$self->{config}->{site_url}$1";
	}

	my $info_tbpath;
	if ($self->{init}->{tb_file} =~ /([^\/\\]*)$/) {
		$info_tbpath = "$self->{config}->{site_url}$1";
	}

	my $info_paintpath;
	if ($self->{init}->{paint_file} =~ /([^\/\\]*)$/) {
		$info_paintpath = "$self->{config}->{site_url}$1";
	}

	my($info_user, $info_user_start, $info_user_end);
	if ($self->{config}->{user_mode} and $self->{query}->{mode} eq 'admin') {
		my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_admin}, $self->{init}->{des_key});
		$info_user = $cookie_ins->get_cookie('admin_user');

		if (!$info_user) {
			$info_user = $self->{query}->{admin_user};
		}
	} else {
		$info_user_start = '<!--';
		$info_user_end   = '-->';
	}

	return(
		INFO_SCRIPT      => $self->{init}->{script},
		INFO_VERSION     => $self->{init}->{version},
		INFO_COPYRIGHT   => $self->{init}->{copyright},
		INFO_FILE        => $self->{init}->{script_file},
		INFO_TITLE       => $self->{config}->{site_title},
		INFO_BACK        => $self->{config}->{back_url},
		INFO_MOBILETITLE => $self->{config}->{mobile_site_title},
		INFO_MOBILEBACK  => $self->{config}->{mobile_back_url},
		INFO_DESCRIPTION => $self->{config}->{site_description},
		INFO_URL         => $self->{config}->{site_url},
		INFO_PATH        => $info_path,
		INFO_TBPATH      => $info_tbpath,
		INFO_PAINTPATH   => $info_paintpath,
		INFO_PAGE        => $page_ins->get_string,
		INFO_USER        => $info_user,
		INFO_USER_START  => $info_user_start,
		INFO_USER_END    => $info_user_end,
		INFO_TIMESTAMP   => time
	);
}

### 記事フォーム作成
sub diary_form {
	my $self = shift;
	my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = @_;

	my $no_ins    = new webliberty::String($no);
	my $id_ins    = new webliberty::String($id);
	my $stat_ins  = new webliberty::String($stat);
	my $break_ins = new webliberty::String($break);
	my $comt_ins  = new webliberty::String($comt);
	my $tb_ins    = new webliberty::String($tb);
	my $field_ins = new webliberty::String($field);
	my $date_ins  = new webliberty::String($date);
	my $name_ins  = new webliberty::String($name);
	my $subj_ins  = new webliberty::String($subj);
	my $text_ins  = new webliberty::String($text);
	my $color_ins = new webliberty::String($color);
	my $icon_ins  = new webliberty::String($icon);
	my $file_ins  = new webliberty::String($file);
	my $host_ins  = new webliberty::String($host);

	$no_ins->create_number;
	$id_ins->create_line;
	$stat_ins->create_number;
	$break_ins->create_number;
	$comt_ins->create_number;
	$tb_ins->create_number;
	$field_ins->create_line;
	$date_ins->create_line;
	$name_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;
	$color_ins->create_line;
	$icon_ins->create_line;
	$file_ins->create_line;
	$host_ins->create_line;

	$id_ins->create_plain;
	$field_ins->create_plain;
	$date_ins->create_plain;
	$name_ins->create_plain;
	$subj_ins->create_plain;
	$text_ins->create_plain;
	$color_ins->create_plain;
	$icon_ins->create_plain;
	$file_ins->create_plain;
	$host_ins->create_plain;

	my($form_id_start, $form_id_end);
	if (!$self->{config}->{use_id}) {
		$form_id_start = '<!--';
		$form_id_end   = '-->';
	}

	my $form_stat;
	if ($stat_ins->get_string == 1) {
		$form_stat = ' checked="checked"';
	} else {
		$form_stat = '';
	}

	my $form_break;
	if ($break_ins->get_string == 1) {
		$form_break = ' checked="checked"';
	} else {
		$form_break = '';
	}

	my $form_comt;
	if ($comt_ins->get_string == 1) {
		$form_comt = ' checked="checked"';
	} else {
		$form_comt = '';
	}

	my $form_tb;
	if ($tb_ins->get_string == 1) {
		$form_tb = ' checked="checked"';
	} else {
		$form_tb = '';
	}

	my($form_field, $form_field_start, $form_field_end, $i);
	if ($self->{config}->{use_field}) {
		open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
		while (<FH>) {
			chomp;
			my($field, $child) = split(/<>/);

			$i++;

			if ($child) {
				$field = "└ $child";
			}

			if ($field_ins->get_string eq $_ or ($self->{query}->{exec_preview} and $field_ins->get_string == $i)) {
				$form_field .= "<option value=\"$i\" selected=\"selected\">$field</option>";
			} else {
				$form_field .= "<option value=\"$i\">$field</option>";
			}
		}
		close(FH);

		if (!$form_field) {
			$form_field = '<option value="">分類が登録されていません</option>';
		} else {
			$form_field = "<option value=\"\">選択してください</option>$form_field";
		}
		$form_field = "<select name=\"field\" xml:lang=\"ja\" lang=\"ja\">$form_field</select>";
	} else {
		$form_field_start = '<!--';
		$form_field_end   = '-->';
	}

	my($form_date, $form_year, $form_month, $form_day, $form_hour, $form_minute);

	if ($date_ins->get_string =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
		$form_year   = $1;
		$form_month  = $2;
		$form_day    = $3;
		$form_hour   = $4;
		$form_minute = $5;
	}

	if ($self->{agent} eq 'mobile') {
		$form_date .= "<input type=\"text\" name=\"year\" size=\"4\" value=\"$form_year\">年";
		$form_date .= "<input type=\"text\" name=\"month\" size=\"2\" value=\"$form_month\">月";
		$form_date .= "<input type=\"text\" name=\"day\" size=\"2\" value=\"$form_day\">日";
		$form_date .= "<input type=\"text\" name=\"hour\" size=\"2\" value=\"$form_hour\">時";
		$form_date .= "<input type=\"text\" name=\"minute\" size=\"2\" value=\"$form_minute\">分";
	} else {
		$form_date .= '<select name="year" xml:lang="ja" lang="ja">';
		foreach ($form_year - 5 .. $form_year + 5) {
			if ($form_year == $_) {
				$form_date .= "<option value=\"$_\" selected=\"selected\">$_年</option>";
			} else {
				$form_date .= "<option value=\"$_\">$_年</option>";
			}
		}
		$form_date .= '</select>';

		$form_date .= '<select name="month" xml:lang="ja" lang="ja">';
		foreach (1 .. 12) {
			my $month = sprintf("%02d", $_);
			if ($month == $form_month) {
				$form_date .= "<option value=\"$month\" selected=\"selected\">$_月</option>";
			} else {
				$form_date .= "<option value=\"$month\">$_月</option>";
			}
		}
		$form_date .= '</select>';

		$form_date .= '<select name="day" xml:lang="ja" lang="ja">';
		foreach (1 .. 31) {
			my $day = sprintf("%02d", $_);
			if ($day == $form_day) {
				$form_date .= "<option value=\"$day\" selected=\"selected\">$_日</option>";
			} else {
				$form_date .= "<option value=\"$day\">$_日</option>";
			}
		}
		$form_date .= '</select>';

		$form_date .= '<select name="hour" xml:lang="ja" lang="ja">';
		foreach (0 .. 23) {
			my $hour = sprintf("%02d", $_);
			if ($hour == $form_hour) {
				$form_date .= "<option value=\"$hour\" selected=\"selected\">$_時</option>";
			} else {
				$form_date .= "<option value=\"$hour\">$_時</option>";
			}
		}
		$form_date .= '</select>';

		$form_date .= '<select name="minute" xml:lang="ja" lang="ja">';
		foreach (0 .. 59) {
			my $minute = sprintf("%02d", $_);
			if ($minute == $form_minute) {
				$form_date .= "<option value=\"$minute\" selected=\"selected\">$_分</option>";
			} else {
				$form_date .= "<option value=\"$minute\">$_分</option>";
			}
		}
		$form_date .= '</select>';
	}

	my($form_name, $form_name_start, $form_name_end);
	if ($self->{config}->{user_mode}) {
		my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_admin}, $self->{init}->{des_key});
		my $info_user = $cookie_ins->get_cookie('admin_user');

		my $flag;

		open(FH, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
		while (<FH>) {
			chomp;
			my($user, $pwd, $authority) = split(/\t/);

			my $name;
			if ($self->{user}->{$user}) {
				$name = "$user（$self->{user}->{$user}）";
			} else {
				$name = $user;
			}

			if ($user eq $name_ins->get_string) {
				$form_name .= "<option value=\"$user\" selected=\"selected\">$name</option>";
			} else {
				$form_name .= "<option value=\"$user\">$name</option>";
			}
			if ($user eq $info_user and $authority eq 'root') {
				$flag = 1;
			}
		}
		close(FH);

		if ($flag) {
			$form_name = "<select name=\"name\" xml:lang=\"ja\" lang=\"ja\">$form_name</select>";
		} else {
			$form_name = '';
			$form_name_start = '<!--';
			$form_name_end   = '-->';
		}
	} else {
		$form_name_start = '<!--';
		$form_name_end   = '-->';
	}

	my($form_color, $form_color_start, $form_color_end);
	if ($self->{config}->{use_color}) {
		foreach (split(/<>/, $self->{config}->{text_color})) {
			my $id = $_;
			$id =~ s/\#//;
			if ($color_ins->get_string eq $_) {
				$form_color .= "<input type=\"radio\" name=\"color\" id=\"color_${id}_radio\" value=\"$_\" checked=\"checked\" /><label for=\"color_${id}_radio\" style=\"color:$_;\">■</label>";
			} else {
				$form_color .= "<input type=\"radio\" name=\"color\" id=\"color_${id}_radio\" value=\"$_\" /><label for=\"color_${id}_radio\" style=\"color:$_;\">■</label>";
			}
		}
	} else {
		$form_color_start = '<!--';
		$form_color_end   = '-->';
	}

	my($form_icon, $form_icon_start, $form_icon_end);
	if ($self->{config}->{use_icon}) {
		open(FH, $self->{init}->{data_icon}) or $self->error("Read Error : $self->{init}->{data_icon}");
		while (<FH>) {
			chomp;
			my($file, $name, $field, $user, $pwd) = split(/\t/);

			if ($icon_ins->get_string eq $file) {
				$form_icon .= "<option value=\"$file\" selected=\"selected\">$name</option>";
			} else {
				$form_icon .= "<option value=\"$file\">$name</option>";
			}
		}
		close(FH);

		if ($form_icon) {
			$form_icon = "<option value=\"\">選択してください</option>$form_icon";
		} else {
			$form_icon = '<option value="">アイコンが登録されていません</option>';
		}
		$form_icon = "<select name=\"icon\" xml:lang=\"ja\" lang=\"ja\">$form_icon</select>";
	} else {
		$form_icon_start = '<!--';
		$form_icon_end   = '-->';
	}

	my($form_file1, $form_file2, $form_file3, $form_file4, $form_file5) = split(/<>/, $file_ins->get_string);
	my($form_file_start, $form_file_end);
	if ($self->{config}->{use_file}) {
		if ($self->{query}->{work} eq 'edit') {
			my($check1, $check2, $check3, $check4, $check5);

			if ($self->{query}->{delfile1}) {
				$check1 = ' checked="checked"';
			}
			if ($self->{query}->{delfile2}) {
				$check2 = ' checked="checked"';
			}
			if ($self->{query}->{delfile3}) {
				$check3 = ' checked="checked"';
			}
			if ($self->{query}->{delfile4}) {
				$check4 = ' checked="checked"';
			}
			if ($self->{query}->{delfile5}) {
				$check5 = ' checked="checked"';
			}

			if ($form_file1) {
				$form_file1 = "<input type=\"checkbox\" name=\"delfile1\" id=\"delfile1_checkbox\" value=\"on\"$check1 /> <label for=\"delfile1_checkbox\">$form_file1を削除</label>";
			}
			if ($form_file2) {
				$form_file2 = "<input type=\"checkbox\" name=\"delfile2\" id=\"delfile2_checkbox\" value=\"on\"$check2 /> <label for=\"delfile2_checkbox\">$form_file2を削除</label>";
			}
			if ($form_file3) {
				$form_file3 = "<input type=\"checkbox\" name=\"delfile3\" id=\"delfile3_checkbox\" value=\"on\"$check3 /> <label for=\"delfile3_checkbox\">$form_file3を削除</label>";
			}
			if ($form_file4) {
				$form_file4 = "<input type=\"checkbox\" name=\"delfile4\" id=\"delfile4_checkbox\" value=\"on\"$check4 /> <label for=\"delfile4_checkbox\">$form_file4を削除</label>";
			}
			if ($form_file5) {
				$form_file5 = "<input type=\"checkbox\" name=\"delfile5\" id=\"delfile5_checkbox\" value=\"on\"$check5 /> <label for=\"delfile5_checkbox\">$form_file5を削除</label>";
			}
		}
	} else {
		$form_file_start = '<!--';
		$form_file_end   = '-->';
	}

	my($form_image, $form_image_start, $form_image_end);
	if ($self->{config}->{use_image}) {
		if ($no_ins->get_string) {
			opendir(DIR, $self->{init}->{data_image_dir}) or $self->error("Read Error : $self->{init}->{data_image_dir}");
			my @files = sort { $a <=> $b } grep { m/\w+\.\w+/g } readdir(DIR);
			close(DIR);

			foreach (@files) {
				my $file_ins  = new webliberty::File("$self->{init}->{data_image_dir}$_");
				my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;

				if ($no_ins->get_string eq $file_ins->get_name or $id_ins->get_string eq $file_ins->get_name) {
					my $check;
					if ($self->{query}->{delimage}) {
						$check = ' checked="checked"';
					}

					$form_image = "<input type=\"checkbox\" name=\"delimage\" id=\"delimage_checkbox\" value=\"on\"$check> <label for=\"delimage_checkbox\">$file_nameを削除</label>";
				}
			}
		}
		if ($self->{query}->{image}) {
			my $file_ins = new webliberty::File($self->{query}->{image}->{file_name});

			$form_image = $self->{init}->{data_tmp_file} . '<input type="hidden" name="image_ext" value="' . $file_ins->get_ext . '" />';
		} elsif ($self->{query}->{image_ext}) {
			$form_image = $self->{init}->{data_tmp_file} . '<input type="hidden" name="image_ext" value="' . $self->{query}->{image_ext} . '" />';
		}
	} else {
		$form_image_start = '<!--';
		$form_image_end   = '-->';
	}

	my($form_tburl_start, $form_tburl_end);
	if (!$self->{config}->{use_tburl}) {
		$form_tburl_start = '<!--';
		$form_tburl_end   = '-->';
	}

	my($form_ping_start, $form_ping_end);
	if (!$self->{config}->{ping_mode}) {
		$form_ping_start = '<!--';
		$form_ping_end   = '-->';
	}

	return(
		FORM_NO          => $no_ins->get_string,
		FORM_ID          => $id_ins->get_string,
		FORM_ID_START    => $form_id_start,
		FORM_ID_END      => $form_id_end,
		FORM_STAT        => $form_stat,
		FORM_BREAK       => $form_break,
		FORM_COMT        => $form_comt,
		FORM_TB          => $form_tb,
		FORM_FIELD       => $form_field,
		FORM_FIELD_START => $form_field_start,
		FORM_FIELD_END   => $form_field_end,
		FORM_DATE        => $form_date,
		FORM_NAME        => $form_name,
		FORM_NAME_START  => $form_name_start,
		FORM_NAME_END    => $form_name_end,
		FORM_SUBJ        => $subj_ins->get_string,
		FORM_TEXT        => $text_ins->get_string,
		FORM_COLOR       => $form_color,
		FORM_COLOR_START => $form_color_start,
		FORM_COLOR_END   => $form_color_end,
		FORM_ICON        => $form_icon,
		FORM_ICON_START  => $form_icon_start,
		FORM_ICON_END    => $form_icon_end,
		FORM_FILE1       => $form_file1,
		FORM_FILE2       => $form_file2,
		FORM_FILE3       => $form_file3,
		FORM_FILE4       => $form_file4,
		FORM_FILE5       => $form_file5,
		FORM_FILE_START  => $form_file_start,
		FORM_FILE_END    => $form_file_end,
		FORM_IMAGE       => $form_image,
		FORM_IMAGE_START => $form_image_start,
		FORM_IMAGE_END   => $form_image_end,
		FORM_HOST        => $host_ins->get_string,
		FORM_TBURL_START => $form_tburl_start,
		FORM_TBURL_END   => $form_tburl_end,
		FORM_PING_START  => $form_ping_start,
		FORM_PING_END    => $form_ping_end
	);
}

### コメントフォーム作成
sub comment_form {
	my $self = shift;
	my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = @_;

	my $no_ins    = new webliberty::String($no);
	my $pno_ins   = new webliberty::String($pno);
	my $stat_ins  = new webliberty::String($stat);
	my $date_ins  = new webliberty::String($date);
	my $name_ins  = new webliberty::String($name);
	my $mail_ins  = new webliberty::String($mail);
	my $url_ins   = new webliberty::String($url);
	my $subj_ins  = new webliberty::String($subj);
	my $text_ins  = new webliberty::String($text);
	my $color_ins = new webliberty::String($color);
	my $icon_ins  = new webliberty::String($icon);
	my $file_ins  = new webliberty::String($file);
	my $rank_ins  = new webliberty::String($rank);
	my $pwd_ins   = new webliberty::String($pwd);
	my $host_ins  = new webliberty::String($host);

	$no_ins->create_number;
	$pno_ins->create_number;
	$stat_ins->create_number;
	$date_ins->create_line;
	$name_ins->create_line;
	$mail_ins->create_line;
	$url_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;
	$color_ins->create_line;
	$icon_ins->create_line;
	$file_ins->create_line;
	$rank_ins->create_line;
	$pwd_ins->create_line;
	$host_ins->create_line;

	$date_ins->create_plain;
	$name_ins->create_plain;
	$mail_ins->create_plain;
	$url_ins->create_plain;
	$subj_ins->create_plain;
	$text_ins->create_plain;
	$color_ins->create_plain;
	$icon_ins->create_plain;
	$file_ins->create_plain;
	$rank_ins->create_plain;
	$pwd_ins->create_plain;
	$host_ins->create_plain;

	my($form_stat, $form_stat_start, $form_stat_end);
	if ($self->{config}->{whisper_mode} and $self->{query}->{mode} ne 'admin') {
		if ($stat_ins->get_string == 2) {
			$form_stat = '<option value="">全体に公開</option><option value="1" selected="selected">管理者のみに公開</option>';
		} else {
			$form_stat = '<option value="" selected="selected">全体に公開</option><option value="1">管理者のみに公開</option>';
		}
		$form_stat = "<select name=\"whisper\" xml:lang=\"ja\" lang=\"ja\">$form_stat</select>";
	} else {
		$form_stat_start = '<!--';
		$form_stat_end   = '-->';
	}

	if (!$url_ins->get_string) {
		$url_ins->set_string('http://');
	}

	my($form_color, $form_color_start, $form_color_end);
	if ($self->{config}->{use_color}) {
		foreach (split(/<>/, $self->{config}->{text_color})) {
			my $id = $_;
			$id =~ s/\#//;
			if ($color_ins->get_string eq $_) {
				$form_color .= "<input type=\"radio\" name=\"color\" id=\"color_${id}_radio\" value=\"$_\" checked=\"checked\" /><label for=\"color_${id}_radio\" style=\"color:$_;\">■</label>";
			} else {
				$form_color .= "<input type=\"radio\" name=\"color\" id=\"color_${id}_radio\" value=\"$_\" /><label for=\"color_${id}_radio\" style=\"color:$_;\">■</label>";
			}
		}
	} else {
		$form_color_start = '<!--';
		$form_color_end   = '-->';
	}

	my($form_icon, $form_icon_start, $form_icon_end);
	if ($self->{config}->{use_icon}) {
		open(FH, $self->{init}->{data_icon}) or $self->error("Read Error : $self->{init}->{data_icon}");
		while (<FH>) {
			chomp;
			my($file, $name, $field, $user, $pwd) = split(/\t/);

			if ($icon_ins->get_string eq $file) {
				$form_icon .= "<option value=\"$file\" selected=\"selected\">$name</option>";
			} else {
				$form_icon .= "<option value=\"$file\">$name</option>";
			}
		}
		close(FH);

		if ($form_icon) {
			$form_icon = "<option value=\"\">選択してください</option>$form_icon";
		} else {
			$form_icon = '<option value="">アイコンが登録されていません</option>';
		}
		$form_icon = "<select name=\"icon\" xml:lang=\"ja\" lang=\"ja\">$form_icon</select>";
	} else {
		$form_icon_start = '<!--';
		$form_icon_end   = '-->';
	}

	return(
		FORM_NO          => $no_ins->get_string,
		FORM_PNO         => $pno_ins->get_string,
		FORM_STAT        => $form_stat,
		FORM_STAT_START  => $form_stat_start,
		FORM_STAT_END    => $form_stat_end,
		FORM_DATE        => $date_ins->get_string,
		FORM_NAME        => $name_ins->get_string,
		FORM_MAIL        => $mail_ins->get_string,
		FORM_URL         => $url_ins->get_string,
		FORM_SUBJ        => $subj_ins->get_string,
		FORM_TEXT        => $text_ins->get_string,
		FORM_COLOR       => $form_color,
		FORM_COLOR_START => $form_color_start,
		FORM_COLOR_END   => $form_color_end,
		FORM_ICON        => $form_icon,
		FORM_ICON_START  => $form_icon_start,
		FORM_ICON_END    => $form_icon_end,
		FORM_FILE        => $file_ins->get_string,
		FORM_PWD         => $pwd_ins->get_string,
		FORM_HOST        => $host_ins->get_string
	);
}

### 記事データ作成
sub diary_article {
	my $self = shift;
	my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = @_;

	my $no_ins    = new webliberty::String($no);
	my $id_ins    = new webliberty::String($id);
	my $stat_ins  = new webliberty::String($stat);
	my $break_ins = new webliberty::String($break);
	my $comt_ins  = new webliberty::String($comt);
	my $tb_ins    = new webliberty::String($tb);
	my $field_ins = new webliberty::String($field);
	my $date_ins  = new webliberty::String($date);
	my $name_ins  = new webliberty::String($name);
	my $subj_ins  = new webliberty::String($subj);
	my $text_ins  = new webliberty::String($text);
	my $color_ins = new webliberty::String($color);
	my $icon_ins  = new webliberty::String($icon);
	my $file_ins  = new webliberty::String($file);
	my $host_ins  = new webliberty::String($host);

	$no_ins->create_number;
	$id_ins->create_line;
	$stat_ins->create_number;
	$break_ins->create_number;
	$comt_ins->create_number;
	$tb_ins->create_number;
	$field_ins->create_line;
	$date_ins->create_line;
	$name_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;
	$color_ins->create_line;
	$icon_ins->create_line;
	$file_ins->create_line;
	$host_ins->create_line;

	if ($stat_ins->get_string == 1) {
		$stat_ins->set_string('公開');
	} else {
		$stat_ins->set_string('下書き');
	}

	my($article_new_start, $article_new_end);
	if ($date_ins->get_string =~ /^(\d\d\d\d)(\d\d)(\d\d)\d\d\d\d$/) {
		my($sec, $min, $hour, $day, $mon, $year, $week) = localtime(time);
		my $day_ins = new webliberty::Date;

		if ($day_ins->get_interval(sprintf("%04d-%02d-%02d", $year + 1900, $mon + 1, $day), "$1-$2-$3") >= $self->{config}->{new_days}) {
			$article_new_start = '<!--';
			$article_new_end   = '-->';
		}
	}

	my($article_year, $article_month, $article_day, $article_hour, $article_minute, $article_week, $article_date_start, $article_date_end);
	if ($date_ins->get_string =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
		my $day_ins  = new webliberty::Date;
		my $week = $day_ins->get_week("$1-$2-$3");

		$article_year   = $1;
		$article_month  = $2;
		$article_day    = $3;
		$article_hour   = $4;
		$article_minute = $5;
		$article_week   = ${$self->{init}->{weeks}}[$week];

		if ($self->{agent} eq 'mobile') {
			$date_ins->set_string("$article_month-$article_day $article_hour:$article_minute");
		} else {
			$date_ins->set_string("$article_year-$article_month-$article_day $article_hour:$article_minute");
		}
	} else {
		$article_date_start = '<!--';
		$article_date_end   = '-->';
	}

	my $fcode_ins = new webliberty::Encoder($field_ins->get_string);
	if ($field_ins->get_string =~ /^(.+)<>(.+)$/) {
		$field_ins->set_string("$1::$2");
	} elsif (!$field_ins->get_string) {
		$field_ins->set_string('未分類');
	}
	my($article_field_start, $article_field_end);
	if (!$self->{config}->{use_field}) {
		$article_field_start = '<!--';
		$article_field_end   = '-->';
	}

	my($article_name_start, $article_name_end);
	if (!$self->{config}->{user_mode}) {
		$article_name_start = '<!--';
		$article_name_end   = '-->';
	}
	my $article_user = $name_ins->get_string;

	if ($self->{config}->{user_mode}) {
		if ($self->{user}->{$name_ins->get_string}) {
			$name_ins->set_string($self->{user}->{$name_ins->get_string});
		} else {
			$name_ins->set_string($name_ins->get_string);
		}
	} else {
		$name_ins->set_string('管理者');
	}

	if (!$subj_ins->get_string) {
		$subj_ins->set_string('No Title');
	}

	$text_ins->replace_string('<br />', "\n");
	$text_ins->permit_html;

	my $info_path;
	if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
		$info_path = "$self->{config}->{site_url}$1";
	}

	my $paint_info;
	if ($text_ins->get_string =~ /\$PAINT(\d+)/) {
		my $info_paint_path;
		if ($self->{init}->{paint_path}) {
			$info_paint_path = $self->{init}->{paint_path};
		} else {
			$self->{init}->{paint_dir} =~ s/^\.\///;
			$info_paint_path = "$self->{config}->{site_url}$self->{init}->{paint_dir}";
		}

		opendir(DIR, $self->{init}->{paint_dir}) or $self->error("Read Error : $self->{init}->{paint_dir}");
		my @files = sort { $a <=> $b } grep { m/\d+\.\w+/g } readdir(DIR);
		close(DIR);

		my %paint;
		foreach (@files) {
			my $file_ins  = new webliberty::File("$self->{init}->{paint_dir}$_");
			my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;

			$paint{$file_ins->get_name} = $file_ins->get_ext;
		}

		for (my $i = $files[$#files] + 0; $i > 0; $i--) {
			if ($text_ins->get_string =~ /\$PAINT$i/ and $paint{$i}) {
				my $file_ins  = new webliberty::File("$self->{init}->{paint_dir}$i\." . $paint{$i});
				my $file_name = $file_ins->get_name . '.' . $file_ins->get_ext;
				my($width, $height) = $file_ins->get_size;

				my $flag;
				if ($width > $self->{config}->{paint_maxwidth}) {
					$height = int($height / ($width / $self->{config}->{paint_maxwidth}));
					$width  = $self->{config}->{paint_maxwidth};

					$flag = 1;
				}

				my $file;
				if ($self->{agent} eq 'mobile') {
					$file = "<a href=\"$info_paint_path$i\.$paint{$i}\">イラスト $i\.$paint{$i}</a>";
				} elsif ($flag) {
					my $target = " $self->{config}->{file_attribute}";
					$target =~ s/&quot;/"/g;

					$file = "<a href=\"$info_path?mode=image&amp;paint=$i\.$paint{$i}\"$target><img src=\"$info_paint_path$i\.$paint{$i}\" alt=\"イラスト $i\.$paint{$i}\" width=\"$width\" height=\"$height\" /></a>";
				} else {
					$file = "<img src=\"$info_paint_path$i\.$paint{$i}\" alt=\"イラスト $i\.$paint{$i}\" width=\"$width\" height=\"$height\" />";
				}

				if ($text_ins->get_string =~ /\$PAINT${i}_path/) {
					$file = "$info_paint_path$i\.$paint{$i}";

					$text_ins->replace_string('\$PAINT' . $i . '_path', $file);
				} elsif ($text_ins->get_string =~ /\$PAINT${i}_l(\([^\)]+\))?/) {
					if ($1 =~ /\((.+)\)/) {
						my $alt = $1;
						$file =~ s/イラスト $i\.$paint{$i}/$alt/;
					}

					$file =~ s/ \/>/ style="float:left" \/>/;

					$text_ins->replace_string('\$PAINT' . $i . '_l(\([^\)]+\))?', $file);
				} elsif ($text_ins->get_string =~ /\$PAINT${i}_r(\([^\)]+\))?/) {
					if ($1 =~ /\((.+)\)/) {
						my $alt = $1;
						$file =~ s/イラスト $i\.$paint{$i}/$alt/;
					}

					$file =~ s/ \/>/ style="float:right" \/>/;

					$text_ins->replace_string('\$PAINT' . $i . '_r(\([^\)]+\))?', $file);
				} elsif ($text_ins->get_string =~ /\$PAINT${i}_c(\([^\)]+\))?/) {
					if ($1 =~ /\((.+)\)/) {
						my $alt = $1;
						$file =~ s/イラスト $i\.$paint{$i}/$alt/;
					}

					$file = "<span style=\"text-align:center;display:block;\">$file</span>";

					$text_ins->replace_string('\$PAINT' . $i . '_c(\([^\)]+\))?', $file);
				} elsif ($text_ins->get_string =~ /\$PAINT$i(\([^\)]+\))?/) {
					if ($1 =~ /\((.+)\)/) {
						my $alt = $1;
						$file =~ s/イラスト $i\.$paint{$i}/$alt/;
					}

					$text_ins->replace_string('\$PAINT' . $i . '(\([^\)]+\))?', $file);
				}

				if ($paint_info) {
					$paint_info = 'multi';
				} else {
					$paint_info = $i;
				}
			}
		}
	}

	if ($text_ins->get_string =~ /\$PCH\d+/) {
		my $target = " $self->{config}->{animation_attribute}";
		$target =~ s/&quot;/"/g;

		my $text = $text_ins->get_string;
		$text =~ s/\$PCH(\d+)/<a href=\"$info_path?mode=pch&amp;file=$1\"$target>$self->{config}->{animation_text}<\/a>/g;
		$text_ins->set_string($text);
	}

	my($article_icon_start, $article_icon_end);
	if ($icon_ins->get_string) {
		my $file_path;
		if ($self->{init}->{data_icon_path}) {
			$file_path = $self->{init}->{data_icon_path};
		} else {
			$self->{init}->{data_icon_dir} =~ s/^\.\///;
			$file_path = "$self->{config}->{site_url}$self->{init}->{data_icon_dir}";
		}
		$icon_ins->set_string("<img src=\"$file_path" . $icon_ins->get_string . "\" alt=\"アイコン\" />");
	} else {
		$article_icon_start = '<!--';
		$article_icon_end   = '-->';
	}

	my($article_files, $article_file_start, $article_file_end);
	my($article_file1, $article_file2, $article_file3, $article_file4, $article_file5) = split(/<>/, $file_ins->get_string);
	my($article_file1_start, $article_file1_end, $article_file2_start, $article_file2_end, $article_file3_start, $article_file3_end, $article_file4_start, $article_file4_end, $article_file5_start, $article_file5_end);

	$self->{init}->{data_upfile_dir}    =~ s/^\.\///;
	$self->{init}->{data_thumbnail_dir} =~ s/^\.\///;

	my $info_upfile_path    = "$self->{config}->{site_url}$self->{init}->{data_upfile_dir}";
	my $info_thumbnail_path = "$self->{config}->{site_url}$self->{init}->{data_thumbnail_dir}";

	my $target = " $self->{config}->{file_attribute}";
	$target =~ s/&quot;/"/g;

	if ($article_file1 or $article_file2 or $article_file3 or $article_file4 or $article_file5) {
		my $i;

		foreach ($article_file1, $article_file2, $article_file3, $article_file4, $article_file5) {
			my $file_ins = new webliberty::File("$self->{init}->{data_upfile_dir}$_");
			my($width, $height) = $file_ins->get_size;

			my $flag;
			if ($width > $self->{config}->{img_maxwidth}) {
				$height = int($height / ($width / $self->{config}->{img_maxwidth}));
				$width  = $self->{config}->{img_maxwidth};

				$flag = 1;
			}

			if ($self->{init}->{data_upfile_path}) {
				$info_upfile_path = $self->{init}->{data_upfile_path};
			}
			if ($self->{init}->{data_thumbnail_path}) {
				$info_thumbnail_path = $self->{init}->{data_thumbnail_path};
			}

			my($file_path, $file);
			if ($self->{config}->{thumbnail_mode} and $flag) {
				$file_path = $info_thumbnail_path;
			} else {
				$file_path = $info_upfile_path;
			}

			if ($_) {
				if ($self->{agent} eq 'mobile' and $width > 0 and $height > 0) {
					$file = "<a href=\"$file_path$_\">ファイル $_</a><br />";
				} elsif ($flag and $width > 0 and $height > 0) {
					$file = "<a href=\"$info_path?mode=image&amp;upfile=$_\"$target><img src=\"$file_path$_\" alt=\"ファイル $_\" width=\"$width\" height=\"$height\" /></a>";
				} elsif ($width > 0 and $height > 0) {
					$file = "<img src=\"$file_path$_\" alt=\"ファイル $_\" width=\"$width\" height=\"$height\" />";
				} else {
					$file = "<a href=\"$info_upfile_path$_\"$target>ファイル $_</a><br />";
				}
			}

			$i++;

			if ($text_ins->get_string =~ /\$FILE${i}_path/) {
				$file = "$info_upfile_path$_";

				$text_ins->replace_string('\$FILE' . $i . '_path', $file);
			} elsif ($text_ins->get_string =~ /\$FILE${i}_full(\([^\)]+\))?/) {
				if ($self->{agent} ne 'mobile' and $flag and $width > 0 and $height > 0) {
					my($width, $height) = $file_ins->get_size;

					$file = "<img src=\"$info_upfile_path$_\" alt=\"ファイル $_\" width=\"$width\" height=\"$height\" />";
				}

				if ($1 =~ /\((.+)\)/) {
					my $alt = $1;
					$file =~ s/ファイル $_/$alt/;
				}

				$text_ins->replace_string('\$FILE' . $i . '_full(\([^\)]+\))?', $file);
			} elsif ($text_ins->get_string =~ /\$FILE${i}_l(\([^\)]+\))?/) {
				if ($1 =~ /\((.+)\)/) {
					my $alt = $1;
					$file =~ s/ファイル $_/$alt/;
				}

				$file =~ s/ \/>/ style="float:left" \/>/;

				$text_ins->replace_string('\$FILE' . $i . '_l(\([^\)]+\))?', $file);
			} elsif ($text_ins->get_string =~ /\$FILE${i}_r(\([^\)]+\))?/) {
				if ($1 =~ /\((.+)\)/) {
					my $alt = $1;
					$file =~ s/ファイル $_/$alt/;
				}

				$file =~ s/ \/>/ style="float:right" \/>/;

				$text_ins->replace_string('\$FILE' . $i . '_r(\([^\)]+\))?', $file);
			} elsif ($text_ins->get_string =~ /\$FILE${i}_c(\([^\)]+\))?/) {
				if ($1 =~ /\((.+)\)/) {
					my $alt = $1;
					$file =~ s/ファイル $_/$alt/;
				}

				$file = "<span style=\"text-align:center;display:block;\">$file</span>";

				$text_ins->replace_string('\$FILE' . $i . '_c(\([^\)]+\))?', $file);
			} elsif ($text_ins->get_string =~ /\$FILE$i(\([^\)]+\))?/) {
				if ($1 =~ /\((.+)\)/) {
					my $alt = $1;
					$file =~ s/ファイル $_/$alt/;
				}

				$text_ins->replace_string('\$FILE' . $i . '(\([^\)]+\))?', $file);
			} else {
				$article_files .= $file;
			}

			$_ = $file;
		}
	}
	if (!$article_files) {
		$article_file_start = '<!--';
		$article_file_end   = '-->';
	}
	if (!$article_file1) {
		$article_file1_start = '<!--';
		$article_file1_end   = '-->';
	}
	if (!$article_file2) {
		$article_file2_start = '<!--';
		$article_file2_end   = '-->';
	}
	if (!$article_file3) {
		$article_file3_start = '<!--';
		$article_file3_end   = '-->';
	}
	if (!$article_file4) {
		$article_file4_start = '<!--';
		$article_file4_end   = '-->';
	}
	if (!$article_file5) {
		$article_file5_start = '<!--';
		$article_file5_end   = '-->';
	}

	my($image, $article_image, $article_image_start, $article_image_end);
	if ($id_ins->get_string) {
		$image = $id_ins->get_string;
	} else {
		$image = $no_ins->get_string;
	}
	if ($self->{image}->{$image}) {
		my $article_file = $image . '.' . $self->{image}->{$image};
		my $article_alt  = $subj_ins->get_string;

		my $file_path;
		if ($self->{init}->{data_image_path}) {
			$file_path = $self->{init}->{data_image_path};
		} else {
			$self->{init}->{data_image_dir} =~ s/^\.\///;
			$file_path = "$self->{config}->{site_url}$self->{init}->{data_image_dir}";
		}

		$article_image = "<img src=\"$file_path$article_file\" alt=\"$article_alt\" />";
	} else {
		$article_image_start = '<!--';
		$article_image_end   = '-->';
	}

	if ($text_ins->get_string =~ /(^|[^-]+)(-----)[^-]+/) {
		my $spliter = quotemeta($2);

		my $continue;
		if ($text_ins->get_string =~ /(^|[^-]+)(-----)(.+)(-----)[^-]+/) {
			$spliter  = quotemeta("$2$3$4");
			$continue = $3;
		} else {
			$continue = $self->{config}->{continue_text};
		}

		my $info_path;
		if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
			$info_path = "$self->{config}->{site_url}$1";
		}

		if ($self->{query}->{mode} or $self->{query}->{continue}) {
			if ($self->{query}->{mode} eq 'rss') {
				$text = (split(/$spliter/, $text_ins->get_string, 2))[0];
				$text_ins->set_string("$text");
			} elsif ($self->{agent} eq 'mobile') {
				$text = (split(/$spliter/, $text_ins->get_string, 2))[1];
				$text_ins->set_string("<a href=\"$info_path?no=$no\">前に戻る</a>$text");
			} else {
				$text_ins->replace_string($spliter, '<span id="continue" ><span style="display:none;">続き</span></span>');
			}
		} else {
			$text = (split(/$spliter/, $text_ins->get_string, 2))[0];
			if ($self->{agent} eq 'mobile') {
				$text_ins->set_string("$text<a href=\"$info_path?mode=continue&amp;no=$no\">$continue</a>");
			} else {
				if ($id_ins->get_string) {
					$text_ins->set_string("$text<a href=\"$info_path?id=$id&amp;continue=on#continue\">$continue</a>");
				} else {
					$text_ins->set_string("$text<a href=\"$info_path?no=$no&amp;continue=on#continue\">$continue</a>");
				}
			}

			if (!$break_ins->get_string) {
				$text_ins->set_string($text_ins->get_string . '</p>');
			}
		}
	}

	if ($break_ins->get_string) {
		if ($self->{config}->{decoration_mode}) {
			my $color_info;
			if ($self->{config}->{use_color} and $color_ins->get_string) {
				$color_info = $color_ins->get_string;
			}

			my $decoration_ins = new webliberty::Decoration($text_ins->get_string);
			$decoration_ins->init_decoration(
				'article'   => 'no' . $no_ins->get_string .'_',
				'paragraph' => $self->{config}->{paragraph_mode},
				'color'     => $color_info,
				'heading'   => 'h4,h5,h6'
			);
			$text_ins->set_string($decoration_ins->create_decoration);
		} else {
			my $color_info;
			if ($self->{config}->{use_color} and $color_ins->get_string) {
				$color_info = ' style="color:' . $color_ins->get_string . '"';
			}

			if ($self->{config}->{paragraph_mode}) {
				$text_ins->replace_string("\n\n", "</p><p$color_info>");
			}
			$text_ins->set_string("<p$color_info>" . $text_ins->get_string . '</p>');
			$text_ins->replace_string("\n", '<br />');
		}
	}
	if ($self->{config}->{autolink_mode}) {
		$text_ins->create_link($self->{config}->{autolink_attribute});
	}

	my $article_url;
	if ($no_ins->get_string) {
		if ($id_ins->get_string) {
			$article_url = $id_ins->get_string;
		} else {
			$article_url = $no_ins->get_string;
		}

		if ($self->{config}->{html_archive_mode} and $self->{agent} ne 'mobile') {
			if ($self->{init}->{archive_path}) {
				$article_url = $self->{init}->{archive_path} . $article_url . "\.$self->{init}->{archive_ext}";
			} elsif ($self->{init}->{archive_dir} =~ /([^\/\\]*\/)$/) {
				$article_url = "$self->{config}->{site_url}$1" . $article_url . "\.$self->{init}->{archive_ext}";
			}
		} else {
			if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
				if ($id_ins->get_string) {
					$article_url = "$self->{config}->{site_url}$1?id=" . $article_url;
				} else {
					$article_url = "$self->{config}->{site_url}$1?no=" . $article_url;
				}
			}
		}
	} else {
		if ($self->{init}->{archive_path}) {
			$article_url = $self->{init}->{archive_path};
		} else {
			$article_url = $self->{config}->{site_url};
		}
	}

	my $article_info;
	if ($article_image) {
		$article_info = $article_image;
	} elsif ($icon_ins->get_string) {
		$article_info = $icon_ins->get_string;
	} else {
		$article_info = $subj_ins->get_string;
	}

	my($article_comment, $article_comment_start, $article_comment_end);
	if (-s $self->{init}->{data_comt_dir} . $no_ins->get_string . "\.$self->{init}->{data_ext}") {
		open(COMT, $self->{init}->{data_comt_dir} . $no_ins->get_string . "\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}" . $no_ins->get_string . "\.$self->{init}->{data_ext}");
		while (<COMT>) {
			$article_comment++;
		}
		close(COMT);
	} else {
		$article_comment = 0;
	}
	if ($self->{query}->{mode} or !$comt_ins->get_string) {
		$article_comment_start = '<!--';
		$article_comment_end   = '-->';
	}

	my($article_trackback, $article_trackback_start, $article_trackback_end);
	if (-s $self->{init}->{data_tb_dir} . $no_ins->get_string . "\.$self->{init}->{data_ext}") {
		open(TB, $self->{init}->{data_tb_dir} . $no_ins->get_string . "\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}" . $no_ins->get_string . "\.$self->{init}->{data_ext}");
		while (<TB>) {
			$article_trackback++;
		}
		close(TB);
	} else {
		$article_trackback = 0;
	}
	if ($self->{query}->{mode} or !$tb_ins->get_string) {
		$article_trackback_start = '<!--';
		$article_trackback_end   = '-->';
	}

	my($article_paint, $article_paint_start, $article_paint_end);
	if ($self->{query}->{mode} or *STDOUT ne "*main::STDOUT" or !$self->{admin} or !$no_ins->get_string or !$paint_info) {
		$article_paint_start = '<!--';
		$article_paint_end   = '-->';
	} elsif ($paint_info ne 'multi') {
		if ($self->{config}->{paint_link}) {
			$article_paint = "$paint_info&amp;exec_paint=on";
		} else {
			$article_paint = "$paint_info";
		}
	}

	my($article_admin_start, $article_admin_end);
	if ($self->{query}->{mode} or *STDOUT ne "*main::STDOUT" or !$self->{admin} or !$no_ins->get_string) {
		$article_admin_start = '<!--';
		$article_admin_end   = '-->';
	}

	my $plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
	my %plugin = $plugin_ins->article(
		'type'  => 'diary',
		'date'  => $date_ins->get_string,
		'no'    => $no_ins->get_string,
		'id'    => $id_ins->get_string,
		'stat'  => $stat_ins->get_string,
		'field' => $field_ins->get_string,
		'name'  => $name_ins->get_string
	);

	return(
		ARTICLE_NO              => $no_ins->get_string,
		ARTICLE_ID              => $id_ins->get_string,
		ARTICLE_STAT            => $stat_ins->get_string,
		ARTICLE_FIELD           => $field_ins->get_string,
		ARTICLE_FNO             => $self->{field}->{$field},
		ARTICLE_FCODE           => $fcode_ins->url_encode,
		ARTICLE_FIELD_START     => $article_field_start,
		ARTICLE_FIELD_END       => $article_field_end,
		ARTICLE_DATE            => $date_ins->get_string,
		ARTICLE_DATE_START      => $article_date_start,
		ARTICLE_DATE_END        => $article_date_end,
		ARTICLE_YEAR            => $article_year,
		ARTICLE_MONTH           => $article_month,
		ARTICLE_DAY             => $article_day,
		ARTICLE_HOUR            => $article_hour,
		ARTICLE_MINUTE          => $article_minute,
		ARTICLE_WEEK            => $article_week,
		ARTICLE_NAME            => $name_ins->get_string,
		ARTICLE_NAME_START      => $article_name_start,
		ARTICLE_NAME_END        => $article_name_end,
		ARTICLE_USER            => $article_user,
		ARTICLE_SUBJ            => $subj_ins->get_string,
		ARTICLE_TEXT            => $text_ins->get_string,
		ARTICLE_COLOR           => $color_ins->get_string,
		ARTICLE_ICON            => $icon_ins->get_string,
		ARTICLE_ICON_START      => $article_icon_start,
		ARTICLE_ICON_END        => $article_icon_end,
		ARTICLE_FILE1           => $article_file1,
		ARTICLE_FILE1_START     => $article_file1_start,
		ARTICLE_FILE1_END       => $article_file1_end,
		ARTICLE_FILE2           => $article_file2,
		ARTICLE_FILE2_START     => $article_file2_start,
		ARTICLE_FILE2_END       => $article_file2_end,
		ARTICLE_FILE3           => $article_file3,
		ARTICLE_FILE3_START     => $article_file3_start,
		ARTICLE_FILE3_END       => $article_file3_end,
		ARTICLE_FILE4           => $article_file4,
		ARTICLE_FILE4_START     => $article_file4_start,
		ARTICLE_FILE4_END       => $article_file4_end,
		ARTICLE_FILE5           => $article_file5,
		ARTICLE_FILE5_START     => $article_file5_start,
		ARTICLE_FILE5_END       => $article_file5_end,
		ARTICLE_FILES           => $article_files,
		ARTICLE_FILE_START      => $article_file_start,
		ARTICLE_FILE_END        => $article_file_end,
		ARTICLE_IMAGE           => $article_image,
		ARTICLE_IMAGE_START     => $article_image_start,
		ARTICLE_IMAGE_END       => $article_image_end,
		ARTICLE_HOST            => $host_ins->get_string,
		ARTICLE_URL             => $article_url,
		ARTICLE_INFO            => $article_info,
		ARTICLE_NEW_START       => $article_new_start,
		ARTICLE_NEW_END         => $article_new_end,
		ARTICLE_COMMENT         => $article_comment,
		ARTICLE_COMMENT_START   => $article_comment_start,
		ARTICLE_COMMENT_END     => $article_comment_end,
		ARTICLE_TRACKBACK       => $article_trackback,
		ARTICLE_TRACKBACK_START => $article_trackback_start,
		ARTICLE_TRACKBACK_END   => $article_trackback_end,
		ARTICLE_PAINT           => $article_paint,
		ARTICLE_PAINT_START     => $article_paint_start,
		ARTICLE_PAINT_END       => $article_paint_end,
		ARTICLE_ADMIN_START     => $article_admin_start,
		ARTICLE_ADMIN_END       => $article_admin_end,
		%plugin
	);
}

### コメントデータ作成
sub comment_article {
	my $self = shift;
	my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = @_;

	my $no_ins    = new webliberty::String($no);
	my $pno_ins   = new webliberty::String($pno);
	my $stat_ins  = new webliberty::String($stat);
	my $date_ins  = new webliberty::String($date);
	my $name_ins  = new webliberty::String($name);
	my $mail_ins  = new webliberty::String($mail);
	my $url_ins   = new webliberty::String($url);
	my $subj_ins  = new webliberty::String($subj);
	my $text_ins  = new webliberty::String($text);
	my $color_ins = new webliberty::String($color);
	my $icon_ins  = new webliberty::String($icon);
	my $file_ins  = new webliberty::String($file);
	my $rank_ins  = new webliberty::String($rank);
	my $pwd_ins   = new webliberty::String($pwd);
	my $host_ins  = new webliberty::String($host);

	$no_ins->create_number;
	$pno_ins->create_number;
	$stat_ins->create_number;
	$date_ins->create_line;
	$name_ins->create_line;
	$mail_ins->create_line;
	$url_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;
	$color_ins->create_line;
	$icon_ins->create_line;
	$file_ins->create_line;
	$rank_ins->create_line;
	$pwd_ins->create_line;
	$host_ins->create_line;

	my($article_new_start, $article_new_end);
	if (time - $date_ins->get_string > 60 * 60 * 24 * $self->{config}->{new_days}) {
		$article_new_start = '<!--';
		$article_new_end   = '-->';
	}

	my($sec, $min, $hour, $day, $mon, $year, $week) = localtime($date_ins->get_string);

	my $article_year   = sprintf("%04d", $year + 1900);
	my $article_month  = sprintf("%02d", $mon + 1);
	my $article_day    = sprintf("%02d", $day);
	my $article_hour   = sprintf("%02d", $hour);
	my $article_minute = sprintf("%02d", $min);
	my $article_week   = ${$self->{init}->{weeks}}[$week];

	if ($self->{agent} eq 'mobile') {
		$date_ins->set_string("$article_month-$article_day $article_hour:$article_minute");
	} else {
		$date_ins->set_string("$article_year-$article_month-$article_day $article_hour:$article_minute");
	}

	if (!$subj_ins->get_string) {
		$subj_ins->set_string('No Title');
	}
	if ($url_ins->get_string eq 'http://') {
		$url_ins->set_string('');
	}

	my($article_mail_start, $article_mail_end);
	if ($mail_ins->get_string) {
		$article_mail_start = '<a href="mailto:' . $mail_ins->get_string . '">';
		$article_mail_end   = '</a>';
	} else {
		$article_mail_start = '<!--';
		$article_mail_end   = '-->';
	}

	my($article_url_start, $article_url_end);
	if ($url_ins->get_string) {
		my $target = " $self->{config}->{autolink_attribute}";
		$target =~ s/&quot;/"/g;

		$article_url_start = '<a href="' . $url_ins->get_string . "\"$target>";
		$article_url_end   = '</a>';
	} else {
		$article_url_start = '<!--';
		$article_url_end   = '-->';
	}

	if ($self->{config}->{use_color} and $color_ins->get_string) {
		if ($self->{config}->{paragraph_mode}) {
			$text_ins->replace_string('<br /><br />', "</p><p style=\"color:" . $color_ins->get_string . "\">");
		}
		$text_ins->set_string("<p style=\"color:" . $color_ins->get_string . "\">" . $text_ins->get_string . '</p>');
	} else {
		if ($self->{config}->{paragraph_mode}) {
			$text_ins->replace_string('<br /><br />', '</p><p>');
		}
		$text_ins->set_string('<p>' . $text_ins->get_string . '</p>');
	}

	if ($self->{config}->{quotation_color}) {
		$text = $text_ins->get_string;

		my $quotation_color = $self->{config}->{quotation_color};

		$text =~ s/([\>]|^)(&gt;|＞)([^<]*)/$1<span style=\"color:$quotation_color;\">$2$3<\/span>/g;

		$text_ins->set_string($text);
	}
	if ($self->{config}->{autolink_mode}) {
		$text_ins->create_link($self->{config}->{autolink_attribute});
	}

	my($article_icon_start, $article_icon_end);
	if ($icon_ins->get_string) {
		my $file_path;
		if ($self->{init}->{data_icon_path}) {
			$file_path = $self->{init}->{data_icon_path};
		} else {
			$self->{init}->{data_icon_dir} =~ s/^\.\///;
			$file_path = "$self->{config}->{site_url}$self->{init}->{data_icon_dir}";
		}
		$icon_ins->set_string("<img src=\"$file_path" . $icon_ins->get_string . "\" alt=\"アイコン\" />");
	} else {
		$article_icon_start = '<!--';
		$article_icon_end   = '-->';
	}

	my($article_edit_start, $article_edit_end);
	if ($self->{query}->{exec_preview}) {
		$article_edit_start = '<!--';
		$article_edit_end   = '-->';
	}

	if ($stat_ins->get_string == 1) {
		$stat_ins->set_string('承認');
	} elsif ($stat_ins->get_string == 2) {
		$stat_ins->set_string('非公開');

		if (*STDOUT eq "*main::STDOUT" and $self->{admin}) {
			$text_ins->set_string('<p><strong>このコメントは管理者宛てです。全体には公開されていません。</strong></p>' . $text_ins->get_string);
		} else {
			if ($self->{query}->{mode} ne 'admin' and $self->{query}->{mode} ne 'edit') {
				$name_ins->set_string('非公開');
				$mail_ins->set_string('');
				$url_ins->set_string('');
				$subj_ins->set_string('非公開');
				$text_ins->set_string('<p>管理者にのみ公開されます。</p>');
				$file_ins->set_string('');

				$article_mail_start = '<!--';
				$article_mail_end   = '-->';

				$article_url_start = '<!--';
				$article_url_end   = '-->';
			}
		}
	} else {
		$stat_ins->set_string('未承認');

		if (*STDOUT eq "*main::STDOUT" and $self->{admin}) {
			$text_ins->set_string('<p><strong>このコメントは未承認のため、管理者にのみ公開されています。</strong></p>' . $text_ins->get_string);
		} else {
			if ($self->{query}->{mode} ne 'admin' and $self->{query}->{mode} ne 'edit') {
				$name_ins->set_string('未承認');
				$mail_ins->set_string('');
				$url_ins->set_string('');
				$subj_ins->set_string('未承認');
				$text_ins->set_string('<p>管理者に承認されるまで内容は表示されません。</p>');
				$file_ins->set_string('');

				$article_mail_start = '<!--';
				$article_mail_end   = '-->';

				$article_url_start = '<!--';
				$article_url_end   = '-->';
			}
		}
	}

	my $plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
	my %plugin = $plugin_ins->article(
		'type' => 'comment',
		'no'   => $no_ins->get_string,
		'pno'  => $pno_ins->get_string,
		'stat' => $stat_ins->get_string,
		'date' => $date_ins->get_string,
		'name' => $name_ins->get_string,
		'subj' => $subj_ins->get_string,
		'host' => $host_ins->get_string
	);

	return(
		ARTICLE_NO         => $no_ins->get_string,
		ARTICLE_PNO        => $pno_ins->get_string,
		ARTICLE_STAT       => $stat_ins->get_string,
		ARTICLE_DATE       => $date_ins->get_string,
		ARTICLE_YEAR       => $article_year,
		ARTICLE_MONTH      => $article_month,
		ARTICLE_DAY        => $article_day,
		ARTICLE_HOUR       => $article_hour,
		ARTICLE_MINUTE     => $article_minute,
		ARTICLE_WEEK       => $article_week,
		ARTICLE_NAME       => $name_ins->get_string,
		ARTICLE_MAIL       => $mail_ins->get_string,
		ARTICLE_MAIL_START => $article_mail_start,
		ARTICLE_MAIL_END   => $article_mail_end,
		ARTICLE_URL        => $url_ins->get_string,
		ARTICLE_URL_START  => $article_url_start,
		ARTICLE_URL_END    => $article_url_end,
		ARTICLE_SUBJ       => $subj_ins->get_string,
		ARTICLE_TEXT       => $text_ins->get_string,
		ARTICLE_COLOR      => $color_ins->get_string,
		ARTICLE_ICON       => $icon_ins->get_string,
		ARTICLE_ICON_START => $article_icon_start,
		ARTICLE_ICON_END   => $article_icon_end,
		ARTICLE_FILE       => $file_ins->get_string,
		ARTICLE_RANK       => $rank_ins->get_string,
		ARTICLE_PWD        => $pwd_ins->get_string,
		ARTICLE_HOST       => $host_ins->get_string,
		ARTICLE_NEW_START  => $article_new_start,
		ARTICLE_NEW_END    => $article_new_end,
		ARTICLE_EDIT_START => $article_edit_start,
		ARTICLE_EDIT_END   => $article_edit_end,
		%plugin
	);
}

### トラックバックデータ作成
sub trackback_article {
	my $self = shift;
	my($no, $pno, $stat, $date, $blog, $title, $url, $excerpt) = @_;

	my $no_ins      = new webliberty::String($no);
	my $pno_ins     = new webliberty::String($pno);
	my $stat_ins    = new webliberty::String($stat);
	my $date_ins    = new webliberty::String($date);
	my $blog_ins    = new webliberty::String($blog);
	my $title_ins   = new webliberty::String($title);
	my $url_ins     = new webliberty::String($url);
	my $excerpt_ins = new webliberty::String($excerpt);

	$no_ins->create_number;
	$pno_ins->create_number;
	$stat_ins->create_number;
	$date_ins->create_line;
	$blog_ins->create_line;
	$title_ins->create_line;
	$url_ins->create_line;
	$excerpt_ins->create_line;

	my($article_new_start, $article_new_end);
	if (time - $date_ins->get_string > 60 * 60 * 24 * $self->{config}->{new_days}) {
		$article_new_start = '<!--';
		$article_new_end   = '-->';
	}

	my($sec, $min, $hour, $day, $mon, $year, $week) = localtime($date_ins->get_string);

	my $article_year   = sprintf("%02d", $year + 1900);
	my $article_month  = sprintf("%02d", $mon + 1);
	my $article_day    = sprintf("%02d", $day);
	my $article_hour   = sprintf("%02d", $hour);
	my $article_minute = sprintf("%02d", $min);
	my $article_week   = ${$self->{init}->{weeks}}[$week];

	if ($self->{agent} eq 'mobile') {
		$date_ins->set_string("$article_month-$article_day $article_hour:$article_minute");
	} else {
		$date_ins->set_string("$article_year-$article_month-$article_day $article_hour:$article_minute");
	}

	$excerpt_ins->replace_string('&amp;', '&');
	$excerpt_ins->replace_string('&lt;', '<');
	$excerpt_ins->replace_string('&gt;', '>');
	$excerpt_ins->replace_string('<[^>]*>', '');
	$excerpt_ins->replace_string('<', '&lt;');
	$excerpt_ins->replace_string('>', '&gt;');
	$excerpt_ins->replace_string('&', '&amp');

	$excerpt_ins->trim_string(300, '...');

	if ($stat_ins->get_string == 1) {
		$stat_ins->set_string('承認');
	} else {
		$stat_ins->set_string('未承認');

		if (*STDOUT eq "*main::STDOUT" and $self->{admin}) {
			$excerpt_ins->set_string('<strong>このトラックバックは未承認のため、管理者にのみ公開されています。</strong><br />' . $excerpt_ins->get_string);
		} else {
			if ($self->{query}->{mode} ne 'admin') {
				$blog_ins->set_string('未承認');
				$title_ins->set_string('未承認');
				$url_ins->set_string('');
				$excerpt_ins->set_string('管理者に承認されるまで内容は表示されません。');
			}
		}
	}

	my $plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
	my %plugin = $plugin_ins->article(
		'type'  => 'trackback',
		'no'    => $no_ins->get_string,
		'pno'   => $pno_ins->get_string,
		'stat'  => $stat_ins->get_string,
		'date'  => $date_ins->get_string,
		'blog'  => $blog_ins->get_string,
		'title' => $title_ins->get_string,
		'url'   => $url_ins->get_string,
	);

	return(
		TRACKBACK_NO        => $no_ins->get_string,
		TRACKBACK_PNO       => $pno_ins->get_string,
		TRACKBACK_STAT      => $stat_ins->get_string,
		TRACKBACK_DATE      => $date_ins->get_string,
		TRACKBACK_YEAR      => $article_year,
		TRACKBACK_MONTH     => $article_month,
		TRACKBACK_DAY       => $article_day,
		TRACKBACK_HOUR      => $article_hour,
		TRACKBACK_MINUTE    => $article_minute,
		TRACKBACK_WEEK      => $article_week,
		TRACKBACK_BLOG      => $blog_ins->get_string,
		TRACKBACK_TITLE     => $title_ins->get_string,
		TRACKBACK_URL       => $url_ins->get_string,
		TRACKBACK_EXCERPT   => $excerpt_ins->get_string,
		TRACKBACK_NEW_START => $article_new_start,
		TRACKBACK_NEW_END   => $article_new_end,
		%plugin
	);
}

### データ更新
sub update {
	my $self = shift;

	if ($self->{config}->{html_index_mode} or $self->{config}->{html_archive_mode} or $self->{config}->{html_field_mode} or $self->{config}->{show_navigation}) {
		require webliberty::App::List;
	}

	if ($self->{config}->{html_index_mode} or $self->{config}->{html_archive_mode} or $self->{config}->{html_field_mode}) {
		my $stdout = *STDOUT;

		#各分類を構築
		if ($self->{config}->{html_field_mode}) {
			foreach (split(/<>/, $self->{config}->{html_field_list})) {
				my($file, $field) = split(/,/, $_, 2);

				$field =~ s/&/&amp;/g;
				$field =~ s/::/&lt;&gt;/g;

				my $dammy;
				if ($self->{init}->{rewrite_mode}) {
					my $init_ins = new webliberty::App::Init;
					$dammy->{init} = $init_ins->get_init;
				} else {
					$dammy->{init} = $self->{init};
				}
				$dammy->{query}->{field} = $field;

				open(HTML, ">$file") or $self->error("Write Error : $file");
				*STDOUT = *HTML;
				my $app_ins = new webliberty::App::List($dammy->{init}, $self->{config}, $dammy->{query});
				$app_ins->run;
				close(HTML);

				if ($self->{init}->{chmod_mode}) {
					if ($self->{init}->{suexec_mode}) {
						chmod(0604, "$file") or $self->error("Chmod Error : $file");
					} else {
						chmod(0666, "$file") or $self->error("Chmod Error : $file");
					}
				}
			}
		}

		#アーカイブを構築
		if ($self->{config}->{html_archive_mode}) {
			my %index;
			open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
			while (<FH>) {
				chomp;
				my($date, $no, $id, $stat, $field, $name) = split(/\t/);

				$index{$no} = $id;
			}
			close(FH);

			foreach (split(/\n/, $self->{query}->{no})) {
				my $file_name;
				if ($index{$_}) {
					$file_name = $index{$_};
				} else {
					$file_name = $_;
				}

				my $dammy;
				if ($self->{init}->{rewrite_mode}) {
					my $init_ins = new webliberty::App::Init;
					$dammy->{init} = $init_ins->get_init;
				} else {
					$dammy->{init} = $self->{init};
				}
				$dammy->{query}->{no} = $_;

				open(HTML, ">$self->{init}->{archive_dir}$file_name\.$self->{init}->{archive_ext}") or $self->error("Write Error : $self->{init}->{archive_dir}$file_name\.$self->{init}->{archive_ext}");
				*STDOUT = *HTML;
				my $app_ins = new webliberty::App::List($dammy->{init}, $self->{config}, $dammy->{query});
				$app_ins->run;
				close(HTML);

				if ($self->{init}->{chmod_mode}) {
					if ($self->{init}->{suexec_mode}) {
						chmod(0604, "$self->{init}->{archive_dir}$file_name\.$self->{init}->{archive_ext}") or $self->error("Chmod Error : $self->{init}->{archive_dir}$file_name\.$self->{init}->{archive_ext}");
					} else {
						chmod(0666, "$self->{init}->{archive_dir}$file_name\.$self->{init}->{archive_ext}") or $self->error("Chmod Error : $self->{init}->{archive_dir}$file_name\.$self->{init}->{archive_ext}");
					}
				}
			}
		}

		#インデックスを構築
		if ($self->{config}->{html_index_mode}) {
			my $dammy;
			if ($self->{init}->{rewrite_mode}) {
				my $init_ins = new webliberty::App::Init;
				$dammy->{init} = $init_ins->get_init;
			} else {
				$dammy->{init} = $self->{init};
			}

			open(HTML, ">$self->{init}->{html_file}") or $self->error("Write Error : $self->{init}->{html_file}");
			*STDOUT = *HTML;
			my $app_ins = new webliberty::App::List($dammy->{init}, $self->{config});
			$app_ins->run;
			close(HTML);
		}

		*STDOUT = $stdout;
	}

	#ナビゲーションを構築
	if ($self->{config}->{show_navigation}) {
		my $app_ins    = new webliberty::App::List($self->{init}, $self->{config});
		my $diary_ins  = new webliberty::App::Diary($self->{init}, $self->{config});
		my $plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});

		my $skin_ins = new webliberty::Skin;
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_diary}");
		$skin_ins->replace_skin(
			$diary_ins->info,
			$plugin_ins->run
		);

		my($navi_start, $navi_end);
		if ($self->{config}->{pos_navigation}) {
			$navi_start = $app_ins->get_navi . $skin_ins->get_data('logs_head');
			$navi_end   = $skin_ins->get_data('logs_foot');
		} else {
			$navi_start = $skin_ins->get_data('logs_head');
			$navi_end   = $skin_ins->get_data('logs_foot') . $app_ins->get_navi;
		}

		my $script_ins = new webliberty::Script;

		my($flag, $message) = $script_ins->create_jscript(
			file     => $self->{init}->{js_navi_start_file},
			contents => $navi_start,
			break    => 1
		);
		if (!$flag) {
			$self->error($message);
		}

		my($flag, $message) = $script_ins->create_jscript(
			file     => $self->{init}->{js_navi_end_file},
			contents => $navi_end,
			break    => 1
		);
		if (!$flag) {
			$self->error($message);
		}
	}

	#サムネイル画像を作成
	if ($self->{config}->{thumbnail_mode}) {
		my $resize_pl;
		if ($self->{config}->{thumbnail_mode} == 2) {
			$resize_pl = $self->{init}->{resize_pl};
		}

		require webliberty::Thumbnail;
		my $thumbnail_ins = new webliberty::Thumbnail;
		my($flag, $message) = $thumbnail_ins->create_thumbnail(
			resize_pl     => $resize_pl,
			file_dir      => $self->{init}->{data_upfile_dir},
			thumbnail_dir => $self->{init}->{data_thumbnail_dir},
			img_max_width => $self->{config}->{img_maxwidth},
			limit         => 10
		);
		if (!$flag) {
			$self->error($message);
		}
	}

	#JSファイルを作成
	if ($self->{config}->{js_title_mode} or $self->{config}->{js_title_field_mode} or $self->{config}->{js_text_mode} or $self->{config}->{js_text_field_mode}) {
		opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
		my @dir = sort { $b <=> $a } readdir(DIR);
		closedir(DIR);

		my $info_archive_path;
		if ($self->{config}->{html_archive_mode}) {
			if ($self->{init}->{archive_dir} =~ /([^\/\\]*\/)$/) {
				$info_archive_path = "$self->{config}->{site_url}$1";
			}
		} else {
			if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
				$info_archive_path = "$self->{config}->{site_url}$1";
			}
		}

		my %title_field;
		if ($self->{config}->{js_title_field_mode}) {
			foreach (split(/<>/, $self->{config}->{js_title_field_list})) {
				my($file, $field) = split(/,/, $_, 2);

				$title_field{$field} = 0;
			}
		}

		my %text_field;
		if ($self->{config}->{js_text_field_mode}) {
			foreach (split(/<>/, $self->{config}->{js_text_field_list})) {
				my($file, $field) = split(/,/, $_, 2);

				$text_field{$field} = 0;
			}
		}

		my $skin_ins = new webliberty::Skin;
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_js_title}");
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_js_text}");
		$skin_ins->replace_skin(
			$self->info
		);

		my($js_title_data, $js_text_data, %js_title_data, %js_text_data, $i);

		foreach my $entry (@dir) {
			if ($entry !~ /^\d\d\d\d\d\d\.$self->{init}->{data_ext}$/) {
				next;
			}
			open(FH, "$self->{init}->{data_diary_dir}$entry") or $self->error("Read Error : $self->{init}->{data_diary_dir}$entry");
			while (<FH>) {
				chomp;
				my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

				if (!$stat) {
					next;
				}

				$i++;
				if (!$self->{config}->{js_title_field_mode} and !$self->{config}->{js_text_field_mode} and $i > $self->{config}->{js_title_size} and $i > $self->{config}->{js_text_size}) {
					last;
				}

				my %article = $self->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host);

				my $title_data = $skin_ins->get_replace_data(
					'jstitle',
					%article
				);
				my $text_data = $skin_ins->get_replace_data(
					'jstext',
					%article
				);

				if (exists($title_field{$article{'ARTICLE_FIELD'}})) {
					$title_field{$article{'ARTICLE_FIELD'}}++;

					if ($title_field{$article{'ARTICLE_FIELD'}} <= $self->{config}->{js_title_size}) {
						$js_title_data{$article{'ARTICLE_FIELD'}} .= $title_data;
					}
				}
				if (exists($text_field{$article{'ARTICLE_FIELD'}})) {
					$text_field{$article{'ARTICLE_FIELD'}}++;

					if ($text_field{$article{'ARTICLE_FIELD'}} <= $self->{config}->{js_text_size}) {
						$js_text_data{$article{'ARTICLE_FIELD'}} .= $text_data;
					}
				}

				if ($field =~ /^(.+)<>.+$/) {
					my $parent = $1;

					if (exists($title_field{$parent})) {
						$title_field{$parent}++;

						if ($title_field{$parent} <= $self->{config}->{js_title_size}) {
							$js_title_data{$parent} .= $title_data;
						}
					}
					if (exists($text_field{$parent})) {
						$text_field{$parent}++;

						if ($text_field{$parent} <= $self->{config}->{js_text_size}) {
							$js_text_data{$parent} .= $text_data;
						}
					}
				}

				if ($self->{config}->{js_title_size} and $i <= $self->{config}->{js_title_size}) {
					$js_title_data .= $title_data;
				}
				if ($self->{config}->{js_text_size} and $i <= $self->{config}->{js_text_size}) {
					$js_text_data .= $text_data;
				}
			}
			close(FH);
		}

		my $script_ins = new webliberty::Script;

		if ($self->{config}->{js_title_mode}) {
			my($flag, $message) = $script_ins->create_jscript(
				file     => $self->{init}->{js_title_file},
				contents => $skin_ins->get_data('jstitle_head') . $js_title_data . $skin_ins->get_data('jstitle_foot'),
				break    => 1
			);
			if (!$flag) {
				$self->error($message);
			}
		}
		if ($self->{config}->{js_text_mode}) {
			my($flag, $message) = $script_ins->create_jscript(
				file     => $self->{init}->{js_text_file},
				contents => $skin_ins->get_data('jstext_head') . $js_text_data . $skin_ins->get_data('jstext_foot'),
				break    => 1
			);
			if (!$flag) {
				$self->error($message);
			}
		}

		if ($self->{config}->{js_title_field_mode}) {
			foreach (split(/<>/, $self->{config}->{js_title_field_list})) {
				my($file, $field) = split(/,/, $_, 2);

				my($flag, $message) = $script_ins->create_jscript(
					file     => $file,
					contents => $skin_ins->get_data('jstitle_head') . $js_title_data{$field} . $skin_ins->get_data('jstitle_foot'),
					break    => 1
				);
				if (!$flag) {
					$self->error($message);
				}
			}
		}
		if ($self->{config}->{js_text_field_mode}) {
			foreach (split(/<>/, $self->{config}->{js_text_field_list})) {
				my($file, $field) = split(/,/, $_, 2);

				my($flag, $message) = $script_ins->create_jscript(
					file     => $file,
					contents => $skin_ins->get_data('jstext_head') . $js_text_data{$field} . $skin_ins->get_data('jstext_foot'),
					break    => 1
				);
				if (!$flag) {
					$self->error($message);
				}
			}
		}
	}

	return;
}

### 初期設定変更
sub rewrite {
	my $self = shift;
	my %args = @_;

	my %data;
	if ($args{'data'}) {
		($data{'date'}, $data{'no'}, $data{'id'}, $data{'stat'}, $data{'field'}, $data{'name'}) = split(/\t/, $args{'data'});
		$data{'field'} =~ s/<>/::/;
	}

	foreach my $rewrite (%args) {
		if ($rewrite and $args{$rewrite}) {
			my $flag = 1;

			foreach my $data (split(/&/, $rewrite)) {
				my($key, $value) = split(/=/, $data);

				if ($key =~ /^\{([^\}]+)\}\{([^\}]+)\}$/ and $1 eq 'query') {
					my $query = $2;
					if ($value =~ /^\{([^\}]*)\}$/ and $self->{query}->{$query} !~ /^$1$/) {
						$flag = 0;

						last;
					}
				} elsif ($key =~ /^\{([^\}]+)\}\{([^\}]+)\}$/ and $1 eq 'data') {
					my $data = $2;
					if ($value =~ /^\{([^\}]*)\}$/ and $data{$data} !~ /^$1$/) {
						$flag = 0;

						last;
					}
				}
			}
			if ($flag) {
				foreach my $data (split(/&/, $args{$rewrite})) {
					my($key, $value) = split(/=/, $data);

					if ($key =~ /^\{([^\}]+)\}$/) {
						$key = $1;
					}
					if ($value =~ /^\{([^\}]+)\}$/) {
						$value = $1;
					}
					$self->{init}->{$key} = $value;
				}
			}
		}
	}

	return $self->{init};
}

### エラー出力
sub error {
	my $self    = shift;
	my $message = shift;

	if (open(FH, "$self->{init}->{skin_dir}$self->{init}->{skin_error}")) {
		print $self->header;
		while (<FH>) {
			s/\$\{INFO_ERROR\}/$message/g;
			print;
		}
		close(FH);
	} else {
		$self->SUPER::error($message);
	}

	exit;
}

1;
