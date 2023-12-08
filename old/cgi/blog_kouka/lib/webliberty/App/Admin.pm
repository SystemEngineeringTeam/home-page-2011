#webliberty::App::Admin.pm (2008/07/05)
#Copyright(C) 2002-2008 Knight, All rights reserved.

package webliberty::App::Admin;

use strict;
use base qw(webliberty::Basis Exporter);
use vars qw(@EXPORT_OK);
use webliberty::String;
use webliberty::Decoration;
use webliberty::File;
use webliberty::Host;
use webliberty::Cookie;
use webliberty::Lock;
use webliberty::Skin;
use webliberty::Trackback;
use webliberty::Ping;
use webliberty::Plugin;
use webliberty::App::Init;
use webliberty::App::Diary;

@EXPORT_OK = qw(check_password get_user get_authority set_user record_log check_access check regist edit del del_trackback);

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init    => shift,
		config  => shift,
		query   => shift,
		plugin  => undef,
		login   => undef,
		html    => undef,
		message => undef,
		update  => undef
	};
	bless $self, $class;

	my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_admin}, $self->{init}->{des_key});

	my %query;
	if ($self->{query}) {
		%query = %{$self->{query}};
	}

	my($admin_user, $admin_pwd);
	if (exists $query{'admin_user'}) {
		$admin_user = $self->{query}->{admin_user};
	} else {
		$admin_user = $cookie_ins->get_cookie('admin_user');
	}
	if (exists $query{'admin_pwd'}) {
		$admin_pwd = $self->{query}->{admin_pwd};
	} else {
		$admin_pwd = $cookie_ins->get_cookie('admin_pwd');
	}

	if ($admin_pwd) {
		my(%pwd, %authority, $default_user);

		open(USER, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
		while (<USER>) {
			chomp;
			my($user, $pwd, $authority) = split(/\t/);

			if (!$default_user) {
				$default_user = $user;
			}

			$pwd{$user}       = $pwd;
			$authority{$user} = $authority;
		}
		close(USER);

		if (!$admin_user) {
			$admin_user = $default_user;
		}

		my $pwd_ins = new webliberty::String($admin_pwd);
		if ($pwd_ins->get_string and $pwd_ins->check_password($pwd{$admin_user})) {
			$self->{login}->{user}      = $admin_user;
			$self->{login}->{pwd}       = $pwd{$admin_user};
			$self->{login}->{authority} = $authority{$admin_user};

			if ($self->{query}->{admin_pwd}) {
				if ($self->{query}->{hold}) {
					$cookie_ins->set_holddays(3650);
				}
				$cookie_ins->set_cookie(
					admin_user => $admin_user,
					admin_pwd  => $admin_pwd
				);
			}

			$self->{login}->{stat} = 1;
		} else {
			$self->{login}->{stat} = 0;
		}
	} else {
		$self->{login}->{stat} = 0;
	}

	return $self;
}

### メイン処理
sub run {
	my $self = shift;

	if ($self->{init}->{rewrite_mode}) {
		my $diary_ins = new webliberty::App::Diary($self->{init}, '', $self->{query});
		$self->{init} = $diary_ins->rewrite(%{$self->{init}->{rewrite}});
	}

	if ($self->check_password) {
		if ($self->{query}->{work} eq 'new') {
			if ($self->{query}->{exec_regist}) {
				$self->check_access;
				$self->check;
				$self->regist;
				$self->output_edit;
			} elsif ($self->{query}->{exec_preview}) {
				$self->check_access;
				$self->check;
				$self->output_preview;
			} else {
				$self->output_form;
			}
		} elsif ($self->{query}->{work} eq 'edit') {
			if ($self->{query}->{exec_regist}) {
				$self->check_access;
				$self->check;
				$self->edit;
			} elsif ($self->{query}->{exec_del}) {
				$self->check_access;
				$self->del;
			}
			if ($self->{query}->{exec_preview}) {
				$self->check;
				$self->output_preview;
			} elsif ($self->{query}->{exec_form}) {
				$self->output_form;
			} elsif ($self->{query}->{exec_confirm}) {
				$self->output_confirm;
			} else {
				$self->output_edit;
			}
		} elsif ($self->{query}->{work} eq 'comment') {
			require webliberty::App::Edit;
			my $app_ins = new webliberty::App::Edit($self->{init}, $self->{config}, $self->{query});
			if ($self->{query}->{exec_regist}) {
				$app_ins->edit;
				$self->{message} = $app_ins->{message};
				$self->record_log($self->{message});
			} elsif ($self->{query}->{exec_del}) {
				$app_ins->del;
				$self->{message} = $app_ins->{message};
				$self->record_log($self->{message});
			} elsif ($self->{query}->{exec_stat}) {
				$self->check_access;
				$self->stat_comment;
			}
			if ($self->{query}->{exec_preview}) {
				$app_ins->output_preview;
			} elsif ($self->{query}->{exec_form}) {
				$app_ins->output_form;
			} elsif ($self->{query}->{exec_confirm}) {
				$self->output_confirm;
			} elsif ($self->{query}->{exec_view}) {
				$self->output_view;
			} else {
				$self->output_comment;
			}
		} elsif ($self->{query}->{work} eq 'trackback') {
			if ($self->{query}->{exec_del}) {
				$self->check_access;
				$self->del_trackback;
			} elsif ($self->{query}->{exec_stat}) {
				$self->check_access;
				$self->stat_trackback;
			}
			if ($self->{query}->{exec_confirm}) {
				$self->output_confirm;
			} elsif ($self->{query}->{exec_view}) {
				$self->output_view;
			} else {
				$self->output_trackback;
			}
		} elsif ($self->{query}->{work} eq 'field') {
			if ($self->{query}->{exec_add}) {
				$self->check_access;
				$self->add_field;
			} elsif ($self->{query}->{exec_edit}) {
				$self->check_access;
				$self->edit_field;
			}
			$self->output_field;
		} elsif ($self->{query}->{work} eq 'icon') {
			if ($self->{query}->{exec_add}) {
				$self->check_access;
				$self->add_icon;
			} elsif ($self->{query}->{exec_del}) {
				$self->check_access;
				$self->del_icon;
			}
			$self->output_icon;
		} elsif ($self->{query}->{work} eq 'top') {
			if ($self->{query}->{exec_top}) {
				$self->check_access;
				$self->top;
			}
			$self->output_top;
		} elsif ($self->{query}->{work} eq 'menu') {
			if ($self->{query}->{exec_add}) {
				$self->check_access;
				$self->add_menu;
			} elsif ($self->{query}->{exec_edit}) {
				$self->check_access;
				$self->edit_menu;
			}
			$self->output_menu;
		} elsif ($self->{query}->{work} eq 'link') {
			if ($self->{query}->{exec_add}) {
				$self->check_access;
				$self->add_link;
			} elsif ($self->{query}->{exec_edit}) {
				$self->check_access;
				$self->edit_link;
			}
			$self->output_link;
		} elsif ($self->{query}->{work} eq 'profile') {
			if ($self->{query}->{exec_profile}) {
				$self->check_access;
				$self->profile;
			}
			$self->output_profile;
		} elsif ($self->{query}->{work} eq 'pwd') {
			if ($self->{query}->{exec_pwd}) {
				$self->check_access;
				$self->pwd;
			}
			$self->output_pwd;
		} elsif ($self->{query}->{work} eq 'env') {
			if ($self->{query}->{exec_env} or $self->{query}->{exec_default}) {
				$self->check_access;
				$self->env;
			}
			if ($self->{query}->{exec_confirm}) {
				$self->output_confirm;
			} else {
				$self->output_env;
			}
		} elsif ($self->{query}->{work} eq 'paint') {
			if ($self->{query}->{exec_del}) {
				$self->check_access;
				$self->del_paint;
			}
			if ($self->{query}->{exec_paint}) {
				$self->output_canvas;
			} elsif ($self->{query}->{exec_view}) {
				$self->output_illust;
			} elsif ($self->{query}->{exec_confirm}) {
				$self->output_confirm;
			} else {
				$self->output_paint;
			}
		} elsif ($self->{query}->{work} eq 'build') {
			if ($self->{query}->{exec_build}) {
				$self->check_access;
				$self->build;
			}
			$self->output_build;
		} elsif ($self->{query}->{work} eq 'user') {
			if ($self->{query}->{exec_add}) {
				$self->check_access;
				$self->add_user;
			} elsif ($self->{query}->{exec_edit}) {
				$self->check_access;
				$self->edit_user;
			}
			$self->output_user;
		} elsif ($self->{query}->{work} eq 'record') {
			$self->output_record;
		} elsif ($self->{query}->{work} eq 'logout') {
			$self->logout;
			$self->output_login;
		} else {
			$self->output_status;
		}
	} else {
		$self->output_login;
	}

	return;
}

### パスワード認証結果チェック
sub check_password {
	my $self = shift;

	if (!$self->{login}->{stat}) {
		my %query;
		if ($self->{query}) {
			%query = %{$self->{query}};
		}

		my $admin_user = $self->{query}->{admin_user};
		my $admin_pwd  = $self->{query}->{admin_pwd};

		if ($admin_pwd) {
			my(%pwd, %authority, $default_user);

			open(USER, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
			while (<USER>) {
				chomp;
				my($user, $pwd, $authority) = split(/\t/);

				if (!$default_user) {
					$default_user = $user;
				}

				$pwd{$user}       = $pwd;
				$authority{$user} = $authority;
			}
			close(USER);

			if (!$admin_user) {
				$admin_user = $default_user;
			}

			my $pwd_ins = new webliberty::String($admin_pwd);
			if ($pwd_ins->get_string and $pwd_ins->check_password($pwd{$admin_user})) {
				$self->{login}->{user}      = $admin_user;
				$self->{login}->{pwd}       = $pwd{$admin_user};
				$self->{login}->{authority} = $authority{$admin_user};

				$self->{login}->{stat} = 1;
			} else {
				$self->{login}->{stat} = 0;
			}
		} else {
			$self->{login}->{stat} = 0;
		}
	}

	return $self->{login}->{stat};
}

### ログインユーザー名取得
sub get_user {
	my $self = shift;

	return $self->{login}->{user};
}

### ログインユーザー権限取得
sub get_authority {
	my $self = shift;

	return $self->{login}->{authority};
}

### ログインユーザー名設定
sub set_user {
	my $self = shift;
	my $user = shift;

	$self->{login}->{user} = $user;

	return;
}

### 操作履歴保存
sub record_log {
	my $self = shift;
	my $log  = shift;

	my $new_data;
	my $i;

	open(FH, $self->{init}->{data_record}) or $self->error("Read Error : $self->{init}->{data_record}");
	while (<FH>) {
		$i++;
		if ($i >= $self->{config}->{record_size}) {
			last;
		}

		$new_data .= $_;
	}
	close(FH);

	my $host_ins = new webliberty::Host;

	$new_data = time . "\t" . $self->get_user . "\t$log\t" . $host_ins->get_host . "\n$new_data";

	open(FH, ">$self->{init}->{data_record}") or $self->error("Write Error : $self->{init}->{data_record}");
	print FH $new_data;
	close(FH);

	return;
}

### アクセスチェック
sub check_access {
	my $self  = shift;
	my $agent = shift;

	my $flag;

	if ($agent ne 'mobile' and $ENV{'REQUEST_METHOD'} ne 'POST') {
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

	return;
}

### 入力内容チェック
sub check {
	my $self = shift;

	my $edit_ins = new webliberty::String($self->{query}->{edit});
	my $id_ins   = new webliberty::String($self->{query}->{id});
	my $subj_ins = new webliberty::String($self->{query}->{subj});
	my $text_ins = new webliberty::String($self->{query}->{text});

	my $year_ins   = new webliberty::String($self->{query}->{year});
	my $month_ins  = new webliberty::String($self->{query}->{month});
	my $day_ins    = new webliberty::String($self->{query}->{day});
	my $hour_ins   = new webliberty::String($self->{query}->{hour});
	my $minute_ins = new webliberty::String($self->{query}->{minute});

	my $init_ins = new webliberty::App::Init;
	my %label = %{$init_ins->get_label};

	if (!$subj_ins->get_string) {
		$self->error("$label{'pc_subj'}が入力されていません。");
	}
	if (!$text_ins->get_string) {
		$self->error("$label{'pc_text'}が入力されていません。");
	}

	if ($id_ins->get_string) {
		if ($id_ins->get_string =~ /[^\w\d\-\_]/) {
			$self->error("$label{'pc_id'}は半角英数字で入力してください。");
		} elsif ($id_ins->get_string !~ /[a-zA-Z]/) {
			$self->error("$label{'pc_id'}は英字を含む値を指定してください。");
		}
	}
	if (!($year_ins->get_string =~ /^\d\d\d\d$/)) {
		$self->error("$label{'pc_date'}の年が正しく指定されていません。");
	}
	if (!($month_ins->get_string =~ /^\d\d$/)) {
		$self->error("$label{'pc_date'}の月が正しく指定されていません。");
	}
	if (!($day_ins->get_string =~ /^\d\d$/)) {
		$self->error("$label{'pc_date'}の日が正しく指定されていません。");
	}
	if (!($hour_ins->get_string =~ /^\d\d$/)) {
		$self->error("$label{'pc_date'}の時間が正しく指定されていません。");
	}
	if (!($minute_ins->get_string =~ /^\d\d$/)) {
		$self->error("$label{'pc_date'}の分が正しく指定されていません。");
	}

	if ($id_ins->get_string) {
		open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
		while (<FH>) {
			chomp;
			my($date, $no, $id, $stat, $field, $name) = split(/\t/);

			if ($self->{query}->{work} eq 'new' and $id_ins->get_string eq $id) {
				$self->error("$label{'pc_id'}はすでに使用されています。");
			}
			if ($self->{query}->{work} eq 'edit' and $edit_ins->get_string != $no and $id_ins->get_string eq $id) {
				$self->error("$label{'pc_id'}はすでに使用されています。");
			}
		}
		close(FH);
	}

	if ($self->{query}->{image}) {
		my $file_ins = new webliberty::File($self->{query}->{image}->{file_name});
		if ($file_ins->get_ext ne 'gif' and $file_ins->get_ext ne 'png' and $file_ins->get_ext ne 'jpeg' and $file_ins->get_ext ne 'jpg' and $file_ins->get_ext ne 'jpe') {
			$self->error("$label{'pc_image'}は画像ファイル（GIF、PNG、JPEG）を指定してください。");
		}
	}

	return;
}

### 新規登録
sub regist {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $id_ins    = new webliberty::String($self->{query}->{id});
	my $stat_ins  = new webliberty::String($self->{query}->{stat});
	my $break_ins = new webliberty::String($self->{query}->{break});
	my $comt_ins  = new webliberty::String($self->{query}->{comt});
	my $tb_ins    = new webliberty::String($self->{query}->{tb});
	my $name_ins  = new webliberty::String($self->{query}->{name});
	my $subj_ins  = new webliberty::String($self->{query}->{subj});
	my $text_ins  = new webliberty::String($self->{query}->{text});
	my $color_ins = new webliberty::String($self->{query}->{color});
	my $icon_ins  = new webliberty::String($self->{query}->{icon});
	my $tburl_ins = new webliberty::String($self->{query}->{tb_url});

	$id_ins->create_line;
	$stat_ins->create_number;
	$break_ins->create_number;
	$comt_ins->create_number;
	$tb_ins->create_number;
	$name_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;
	$color_ins->create_line;
	$icon_ins->create_line;
	$tburl_ins->create_text;

	my $log_file = "$self->{init}->{data_diary_dir}$self->{query}->{year}$self->{query}->{month}\.$self->{init}->{data_ext}";
	my $now      = "$self->{query}->{year}$self->{query}->{month}$self->{query}->{day}$self->{query}->{hour}$self->{query}->{minute}";
	my @logs;

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	#記事記録ファイルオープン
	if (-e $log_file) {
		open(FH, $log_file) or $self->error("Read Error : $log_file");
		@logs = <FH>;
		close(FH);
	} else {
		open(FH, ">$log_file") or $self->error("Write Error : $log_file");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, "$log_file") or $self->error("Chmod Error : $log_file");
			} else {
				chmod(0666, "$log_file") or $self->error("Chmod Error : $log_file");
			}
		}
	}

	#記録済みデータ読み込み
	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	my @index = <FH>;
	close(FH);

	my @numbers = map { (split(/\t/))[1] } @index;
	@index = @index[sort { $numbers[$b] <=> $numbers[$a] } (0 .. $#numbers)];

	my $new_no   = (split(/\t/, $index[0]))[1] + 1;
	my $host_ins = new webliberty::Host;

	my($new_field, $i);

	open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
	while (<FH>) {
		chomp;

		if ($self->{query}->{field} == ++$i) {
			$new_field = $_;
		}
	}
	close(FH);

	if (!$name_ins->get_string) {
		$name_ins->set_string($self->get_user);
	}

	#アップロードファイル保存
	my(%file, $files, $flag);

	my $file_name;
	if ($id_ins->get_string) {
		$file_name = $id_ins->get_string;
	} else {
		$file_name = $new_no;
	}

	foreach (1 .. 5) {
		if ($self->{query}->{'file' . $_} or $self->{query}->{'ext' . $_}) {
			$flag = 1;
		}
	}
	if ($flag) {
		foreach (1 .. 5) {
			if ($self->{query}->{'file' . $_}) {
				my $file_ins = new webliberty::File($self->{query}->{'file' . $_}->{file_name});
				if ($id_ins->get_string and $_ == 1) {
					$file{$_} = "$file_name\." . $file_ins->get_ext;
				} else {
					$file{$_} = "$file_name\-$_\." . $file_ins->get_ext;
				}

				open(FH, ">$self->{init}->{data_upfile_dir}$file{$_}") or $self->error("Write Error : $self->{init}->{data_upfile_dir}$file{$_}");
				binmode(FH);
				print FH $self->{query}->{'file' . $_}->{file_data};
				close(FH);
			} elsif ($self->{query}->{'ext' . $_}) {
				if ($id_ins->get_string and $_ == 1) {
					$file{$_} = "$file_name\.$self->{query}->{'ext' . $_}";
				} else {
					$file{$_} = "$file_name\-$_\.$self->{query}->{'ext' . $_}";
				}
				rename("$self->{init}->{data_upfile_dir}$self->{init}->{data_tmp_file}" . $self->get_user . $_, "$self->{init}->{data_upfile_dir}$file{$_}");
			}
		}

		foreach (1 .. 5) {
			if ($_ > 1) {
				$files .= '<>';
			}
			$files .= "$file{$_}";
		}
	}

	#ミニ画像保存
	if ($self->{query}->{image}) {
		my $file_ins = new webliberty::File($self->{query}->{image}->{file_name});
		my $file = "$file_name\." . $file_ins->get_ext;

		open(FH, ">$self->{init}->{data_image_dir}$file") or $self->error("Write Error : $self->{init}->{data_image_dir}$file");
		binmode(FH);
		print FH $self->{query}->{image}->{file_data};
		close(FH);
	} elsif ($self->{query}->{image_ext}) {
		rename("$self->{init}->{data_image_dir}$self->{init}->{data_tmp_file}" . $self->get_user, "$self->{init}->{data_image_dir}$file_name\.$self->{query}->{image_ext}");
	}

	#記録用データ作成
	my $diary = "$new_no\t" . $id_ins->get_string . "\t" . $stat_ins->get_string . "\t" . $break_ins->get_string . "\t" . $comt_ins->get_string . "\t" . $tb_ins->get_string . "\t$new_field\t$now\t" . $name_ins->get_string . "\t" . $subj_ins->get_string . "\t" . $text_ins->get_string . "\t" . $color_ins->get_string . "\t" . $icon_ins->get_string . "\t$files\t" . $host_ins->get_host;

	my($new_data, $flag);

	open(FH, $log_file) or $self->error("Read Error : $log_file");
	while (<FH>) {
		chomp;
		my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

		if (!$flag and $now > $date) {
			$new_data .= "$diary\n";
			$flag      = 1;
		}
		$new_data .= "$_\n";
	}
	if (!$flag) {
		$new_data .= "$diary\n";
	}
	close(FH);

	#記事登録
	open(FH, ">$log_file") or $self->error("Write Error : $log_file");
	print FH $new_data;
	close(FH);

	#インデックス用データ作成
	my $index = "$now\t$new_no\t" . $id_ins->get_string . "\t" . $stat_ins->get_string . "\t$new_field\t" . $name_ins->get_string;

	my($new_index, $flag);

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if (!$flag and $now > $date) {
			$new_index .= "$index\n";
			$flag       = 1;
		}
		$new_index .= "$_\n";
	}
	if (!$flag) {
		$new_index .= "$index\n";
	}
	close(FH);

	#インデックス登録
	open(FH, ">$self->{init}->{data_diary_index}") or $self->error("Write Error : $self->{init}->{data_diary_index}");
	print FH $new_index;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	$self->{update}->{query}->{no} = $new_no;

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	my $alert_message;

	#トラックバック送信
	if ($stat_ins->get_string and $tburl_ins->get_string) {
		my $article_url;
		if ($id_ins->get_string) {
			$article_url = $id_ins->get_string;
		} else {
			$article_url = $new_no;
		}

		if ($self->{config}->{html_archive_mode}) {
			if ($self->{init}->{archive_dir} =~ /([^\/\\]*\/)$/) {
				$article_url = "$self->{config}->{site_url}$1$article_url\.$self->{init}->{archive_ext}";
			}
		} else {
			if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
				if ($id_ins->get_string) {
					$article_url = "$self->{config}->{site_url}$1?id=$article_url";
				} else {
					$article_url = "$self->{config}->{site_url}$1?no=$article_url";
				}
			}
		}

		my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
		my %article = $diary_ins->diary_article($new_no, $id_ins->get_string, $stat_ins->get_string, $break_ins->get_string, $comt_ins->get_string, $tb_ins->get_string, $new_field, $now, $name_ins->get_string, $subj_ins->get_string, $text_ins->get_string, $color_ins->get_string, $icon_ins->get_string, $files, $host_ins->get_host);
		$text_ins->set_string($article{'ARTICLE_TEXT'});

		my $trackback_ins = new webliberty::Trackback;
		foreach (split(/<br \/>/, $tburl_ins->get_string)) {
			if (!$_) {
				next;
			}
			my($flag, $message) = $trackback_ins->send_trackback(
				trackback_url => $_,
				title         => $subj_ins->get_string,
				url           => $article_url,
				excerpt       => $text_ins->get_string,
				blog_name     => $self->{config}->{site_title},
				user_agent    => $self->{init}->{script} . '/' . $self->{init}->{version}
			);
			if (!$flag) {
				$alert_message .= "<strong>$_ へのトラックバック送信に失敗しました。$message</strong>";
			}
		}
	}

	#更新PING送信
	if ($stat_ins->get_string and $self->{query}->{ping}) {
		my $ping_ins = new webliberty::Ping;
		foreach (split(/<>/, $self->{config}->{ping_list})) {
			if (!$_) {
				next;
			}
			my($flag, $message) = $ping_ins->send_ping(
				ping_url  => $_,
				url       => $self->{config}->{site_url},
				blog_name => $self->{config}->{site_title}
			);
			if (!$flag) {
				$alert_message .= "<strong>$_ への更新PING送信に失敗しました。$message</strong>";
			}
		}
	}

	$self->{message} = "記事を新規に投稿しました。$alert_message";

	$self->record_log($self->{message});

	return;
}

### 記事編集
sub edit {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $edit_ins  = new webliberty::String($self->{query}->{edit});
	my $id_ins    = new webliberty::String($self->{query}->{id});
	my $stat_ins  = new webliberty::String($self->{query}->{stat});
	my $break_ins = new webliberty::String($self->{query}->{break});
	my $comt_ins  = new webliberty::String($self->{query}->{comt});
	my $tb_ins    = new webliberty::String($self->{query}->{tb});
	my $name_ins  = new webliberty::String($self->{query}->{name});
	my $subj_ins  = new webliberty::String($self->{query}->{subj});
	my $text_ins  = new webliberty::String($self->{query}->{text});
	my $color_ins = new webliberty::String($self->{query}->{color});
	my $icon_ins  = new webliberty::String($self->{query}->{icon});
	my $tburl_ins = new webliberty::String($self->{query}->{tb_url});

	$edit_ins->create_number;
	$id_ins->create_line;
	$stat_ins->create_number;
	$break_ins->create_number;
	$comt_ins->create_number;
	$tb_ins->create_number;
	$name_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;
	$color_ins->create_line;
	$icon_ins->create_line;
	$tburl_ins->create_text;

	if (!$edit_ins->get_string) {
		$self->error('編集したい記事を選択してください。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	#記録用データ作成
	my($new_field, $i);

	open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
	while (<FH>) {
		chomp;

		if ($self->{query}->{field} == ++$i) {
			$new_field = $_;
		}
	}
	close(FH);

	my $new_date = "$self->{query}->{year}$self->{query}->{month}$self->{query}->{day}$self->{query}->{hour}$self->{query}->{minute}";

	#インデックス用データ作成
	my(@new_index, $edit_date);

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if ($edit_ins->get_string == $no) {
			$edit_date = $date;

			if (!$name_ins->get_string) {
				$name_ins->set_string($name);
			}

			push(@new_index, "$new_date\t$no\t" . $id_ins->get_string . "\t" . $stat_ins->get_string . "\t$new_field\t" . $name_ins->get_string . "\n");
		} else {
			push(@new_index, "$_\n");
		}
	}
	close(FH);

	#編集データ検索
	my $edit_file;
	if ($edit_date =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
		$edit_file = "$self->{init}->{data_diary_dir}$1$2\.$self->{init}->{data_ext}";
	}

	my($new_data, $org_id, $org_name, $org_files, $flag);

	open(FH, $edit_file) or $self->error("Read Error : $edit_file");
	while (<FH>) {
		chomp;
		my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

		if ($edit_ins->get_string == $no) {
			$org_id    = $id;
			$org_name  = $name;
			$org_files = $file;
			$flag      = 1;
		} else {
			$new_data .= "$_\n";
		}
	}
	close(FH);

	if (!$flag) {
		$self->error('指定された記事は存在しません。');
	}

	if (!$name_ins->get_string) {
		$name_ins->set_string($org_name);
	}

	#アップロードファイル保存
	my(%file, $files);
	$flag = '';

	my $file_name;
	if ($org_id) {
		$file_name = $org_id;
	} else {
		$file_name = $edit_ins->get_string;
	}

	foreach (1 .. 5) {
		if ($self->{query}->{'file' . $_} or $self->{query}->{'ext' . $_} or $self->{query}->{'delfile' . $_}) {
			$flag = 1;
		}
	}
	if ($flag) {
		my @org_files = split(/<>/, $org_files);

		foreach (1 .. 5) {
			my $file;

			if ($self->{query}->{'delfile' . $_}) {
				if (-e $self->{init}->{data_thumbnail_dir} . $org_files[$_ - 1]) {
					unlink($self->{init}->{data_thumbnail_dir} . $org_files[$_ - 1]);
				}
				unlink($self->{init}->{data_upfile_dir} . $org_files[$_ - 1]);
			} elsif ($self->{query}->{'file' . $_}) {
				if (-e $self->{init}->{data_thumbnail_dir} . $org_files[$_ - 1]) {
					unlink($self->{init}->{data_thumbnail_dir} . $org_files[$_ - 1]);
				}

				my $file_ins = new webliberty::File($self->{query}->{'file' . $_}->{file_name});
				if ($org_id and $_ == 1) {
					$file{$_} = "$file_name\." . $file_ins->get_ext;
				} else {
					$file{$_} = "$file_name\-$_\." . $file_ins->get_ext;
				}

				open(FH, ">$self->{init}->{data_upfile_dir}$file{$_}") or $self->error("Write Error : $self->{init}->{data_upfile_dir}$file{$_}");
				binmode(FH);
				print FH $self->{query}->{'file' . $_}->{file_data};
				close(FH);
			} elsif ($self->{query}->{'ext' . $_}) {
				if ($org_id and $_ == 1) {
					$file{$_} = "$self->{query}->{edit}\.$self->{query}->{'ext' . $_}";
				} else {
					$file{$_} = "$self->{query}->{edit}\-$_\.$self->{query}->{'ext' . $_}";
				}
				rename("$self->{init}->{data_upfile_dir}$self->{init}->{data_tmp_file}" . $self->get_user . $_, "$self->{init}->{data_upfile_dir}$file{$_}");
			} elsif ($org_files[$_ - 1]) {
				$file{$_} = $org_files[$_ - 1];
			}
		}

		foreach (1 .. 5) {
			if ($_ > 1) {
				$files .= '<>';
			}
			$files .= "$file{$_}";
		}
	} else {
		$files = $org_files;
	}
	if (($id_ins->get_string or $org_id) and $id_ins->get_string ne $org_id) {
		my @files = split(/<>/, $files);

		$files = '';
		foreach (1 .. 5) {
			my $file_ins = new webliberty::File($files[$_ - 1]);

			my $new_file;
			if ($files[$_ - 1]) {
				if ($id_ins->get_string) {
					if ($_ == 1) {
						$new_file = $id_ins->get_string . '.' . $file_ins->get_ext;
					} else {
						$new_file = $id_ins->get_string . "\-$_\." . $file_ins->get_ext;
					}
				} else {
					$new_file = $edit_ins->get_string . "\-$_\." . $file_ins->get_ext;
				}

				rename($self->{init}->{data_upfile_dir} . $files[$_ - 1], "$self->{init}->{data_upfile_dir}$new_file");
			}

			if ($_ > 1) {
				$files .= '<>';
			}
			$files .= $new_file;
		}
	}

	#ミニ画像保存
	if ($self->{query}->{delimage} or $self->{query}->{image}) {
		opendir(DIR, $self->{init}->{data_image_dir}) or $self->error("Read Error : $self->{init}->{data_image_dir}");
		my @files = sort { $a <=> $b } grep { m/\w+\.\w+/g } readdir(DIR);
		close(DIR);

		foreach (@files) {
			my $file_ins = new webliberty::File("$self->{init}->{data_image_dir}$_");

			if ($edit_ins->get_string eq $file_ins->get_name or $org_id eq $file_ins->get_name) {
				unlink("$self->{init}->{data_image_dir}$_");
			}
		}
	}
        if ($self->{query}->{image}) {
		my $file_ins = new webliberty::File($self->{query}->{image}->{file_name});
		my $file = "$file_name\." . $file_ins->get_ext;

		open(FH, ">$self->{init}->{data_image_dir}$file") or $self->error("Write Error : $self->{init}->{data_image_dir}$file");
		binmode(FH);
		print FH $self->{query}->{image}->{file_data};
		close(FH);
	} elsif ($self->{query}->{image_ext}) {
		rename("$self->{init}->{data_image_dir}$self->{init}->{data_tmp_file}" . $self->get_user, "$self->{init}->{data_image_dir}" . $edit_ins->get_string . "\.$self->{query}->{image_ext}");
	} elsif (($id_ins->get_string or $org_id) and $id_ins->get_string ne $org_id) {
		opendir(DIR, $self->{init}->{data_image_dir}) or $self->error("Read Error : $self->{init}->{data_image_dir}");
		my @files = sort { $a <=> $b } grep { m/\w+\.\w+/g } readdir(DIR);
		close(DIR);

		foreach (@files) {
			my $file_ins = new webliberty::File("$self->{init}->{data_image_dir}$_");

			if ($edit_ins->get_string eq $file_ins->get_name or $org_id eq $file_ins->get_name) {
				if ($id_ins->get_string) {
					rename("$self->{init}->{data_image_dir}$_", "$self->{init}->{data_image_dir}" . $id_ins->get_string . '.' . $file_ins->get_ext);
				} else {
					rename("$self->{init}->{data_image_dir}$_", "$self->{init}->{data_image_dir}" . $edit_ins->get_string . '.' . $file_ins->get_ext);
				}
			}
		}
	}

	if ($self->{config}->{html_archive_mode}) {
		unlink("$self->{init}->{archive_dir}$file_name\.$self->{init}->{archive_ext}");
	}

	#編集データ登録
	open(FH, ">$edit_file") or $self->error("Write Error : $edit_file");
	print FH $new_data;
	close(FH);

	#記録用データ作成
	my $host_ins = new webliberty::Host;

	my $diary  = $edit_ins->get_string . "\t" . $id_ins->get_string . "\t" . $stat_ins->get_string . "\t" . $break_ins->get_string . "\t" . $comt_ins->get_string . "\t" . $tb_ins->get_string . "\t$new_field\t$new_date\t" . $name_ins->get_string . "\t" . $subj_ins->get_string . "\t" . $text_ins->get_string . "\t" . $color_ins->get_string . "\t" . $icon_ins->get_string . "\t$files\t" . $host_ins->get_host;
	$edit_file = "$self->{init}->{data_diary_dir}$self->{query}->{year}$self->{query}->{month}\.$self->{init}->{data_ext}";

	if (!-e $edit_file) {
		open(FH, ">$edit_file") or $self->error("Write Error : $edit_file");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, "$edit_file") or $self->error("Chmod Error : $edit_file");
			} else {
				chmod(0666, "$edit_file") or $self->error("Chmod Error : $edit_file");
			}
		}
	}

	my $record_data;
	$flag = '';

	open(FH, $edit_file) or $self->error("Read Error : $edit_file");
	while (<FH>) {
		chomp;
		my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

		if (!$flag and (($new_date == $date and $edit_ins->get_string < $no) or $new_date > $date)) {
			$record_data .= "$diary\n";
			$flag         = 1;
		}
		$record_data .= "$_\n";
	}
	if (!$flag) {
		$record_data .= "$diary\n";
	}

	#記事登録
	open(FH, ">$edit_file") or $self->error("Write Error : $edit_file");
	print FH $record_data;
	close(FH);

	#インデックス登録
	open(FH, ">$self->{init}->{data_diary_index}") or $self->error("Write Error : $self->{init}->{data_diary_index}");
	print FH sort { $b <=> $a } @new_index;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	$self->{update}->{query}->{no} = $edit_ins->get_string;

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	my $alert_message;

	#トラックバック送信
	if ($stat_ins->get_string and $tburl_ins->get_string) {
		my $article_url;
		if ($id_ins->get_string) {
			$article_url = $id_ins->get_string;
		} else {
			$article_url = $edit_ins->get_string;
		}

		if ($self->{config}->{html_archive_mode}) {
			if ($self->{init}->{archive_dir} =~ /([^\/\\]*\/)$/) {
				$article_url = "$self->{config}->{site_url}$1$article_url\.$self->{init}->{archive_ext}";
			}
		} else {
			if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
				if ($id_ins->get_string) {
					$article_url = "$self->{config}->{site_url}$1?id=$article_url";
				} else {
					$article_url = "$self->{config}->{site_url}$1?no=$article_url";
				}
			}
		}

		my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
		my %article = $diary_ins->diary_article($edit_ins->get_string, $id_ins->get_string, $stat_ins->get_string, $break_ins->get_string, $comt_ins->get_string, $tb_ins->get_string, $new_field, $new_date, $name_ins->get_string, $subj_ins->get_string, $text_ins->get_string, $color_ins->get_string, $icon_ins->get_string, $files, $host_ins->get_host);
		$text_ins->set_string($article{'ARTICLE_TEXT'});

		my $trackback_ins = new webliberty::Trackback;
		foreach (split(/<br \/>/, $tburl_ins->get_string)) {
			if (!$_) {
				next;
			}
			my($flag, $message) = $trackback_ins->send_trackback(
				trackback_url => $_,
				title         => $subj_ins->get_string,
				url           => $article_url,
				excerpt       => $text_ins->get_string,
				blog_name     => $self->{config}->{site_title},
				user_agent    => $self->{init}->{script} . '/' . $self->{init}->{version}
			);
			if (!$flag) {
				$alert_message .= "<strong>$_ へのトラックバック送信に失敗しました。$message</strong>";
			}
		}
	}

	#更新PING送信
	if ($stat_ins->get_string and $self->{query}->{ping}) {
		my $ping_ins = new webliberty::Ping;
		foreach (split(/<>/, $self->{config}->{ping_list})) {
			if (!$_) {
				next;
			}
			my($flag, $message) = $ping_ins->send_ping(
				ping_url  => $_,
				url       => $self->{config}->{site_url},
				blog_name => $self->{config}->{site_title}
			);
			if (!$flag) {
				$alert_message .= "<strong>$_ への更新PING送信に失敗しました。$message</strong>";
			}
		}
	}

	$self->{message} = "No." . $edit_ins->get_string . "の記事を編集しました。$alert_message";

	$self->record_log($self->{message});

	return;
}

### 記事削除
sub del {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{query}->{del}) {
		$self->error('削除したい記事を選択してください。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	#削除データ検索
	my($new_index, %del_file, $del_id, $del_name);

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if ($self->{query}->{del} =~ /(^|\n)$no(\n|$)/) {
			if ($date =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
				$del_file{"$self->{init}->{data_diary_dir}$1$2\.$self->{init}->{data_ext}"} = 1;
			}
			$del_id   = $id;
			$del_name = $name;
		} else {
			$new_index .= "$_\n";
		}
	}
	close(FH);

	if ($self->get_authority ne 'root' and $self->get_user ne $del_name) {
		$self->error('他のユーザーの記事は削除できません。');
	}

	#記事削除
	my $flag;

	foreach my $entry (keys %del_file) {
		my $new_data;

		open(FH, $entry) or $self->error("Read Error : $entry");
		while (<FH>) {
			chomp;
			my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

			if ($self->{query}->{del} =~ /(^|\n)$no(\n|$)/) {
				if ($file) {
					foreach my $del_file (split(/<>/, $file)) {
						if ($del_file) {
							if (-e "$self->{init}->{data_thumbnail_dir}$del_file") {
								unlink("$self->{init}->{data_thumbnail_dir}$del_file");
							}
							unlink("$self->{init}->{data_upfile_dir}$del_file");
						}
					}
				}

				$flag = 1;
			} else {
				$new_data .= "$_\n";
			}
		}
		close(FH);

		open(FH, ">$entry") or $self->error("Write Error : $entry");
		print FH $new_data;
		close(FH);
	}

	if (!$flag) {
		$self->error('指定された記事は存在しません。');
	}

	#ミニ画像削除
	opendir(DIR, $self->{init}->{data_image_dir}) or $self->error("Read Error : $self->{init}->{data_image_dir}");
	my @files = sort { $a <=> $b } grep { m/\w+\.\w+/g } readdir(DIR);
	close(DIR);

	foreach (@files) {
		my $file_ins = new webliberty::File("$self->{init}->{data_image_dir}$_");

		if ($self->{query}->{del} eq $file_ins->get_name or $del_id eq $file_ins->get_name) {
			unlink("$self->{init}->{data_image_dir}$_");
		}
	}

	#コメント削除
	my $comt_index;
	open(FH, $self->{init}->{data_comt_index}) or $self->error("Read Error : $self->{init}->{data_comt_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $subj, $host) = split(/\t/);

		if ($self->{query}->{del} =~ /(^|\n)$pno(\n|$)/) {
			if (-e "$self->{init}->{data_comt_dir}$pno\.$self->{init}->{data_ext}") {
				unlink("$self->{init}->{data_comt_dir}$pno\.$self->{init}->{data_ext}");
			}
		} else {
			$comt_index .= "$_\n";
		}
	}
	close(FH);

	#コメントインデックス登録
	open(FH, ">$self->{init}->{data_comt_index}") or $self->error("Write Error : $self->{init}->{data_comt_index}");
	print FH $comt_index;
	close(FH);

	#トラックバック削除
	my $tb_index;
	open(FH, $self->{init}->{data_tb_index}) or $self->error("Read Error : $self->{init}->{data_tb_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $blog, $title, $url) = split(/\t/);

		if ($self->{query}->{del} =~ /(^|\n)$pno(\n|$)/) {
			if (-e "$self->{init}->{data_tb_dir}$pno\.$self->{init}->{data_ext}") {
				unlink("$self->{init}->{data_tb_dir}$pno\.$self->{init}->{data_ext}");
			}
		} else {
			$tb_index .= "$_\n";
		}
	}
	close(FH);

	#トラックバックインデックス登録
	open(FH, ">$self->{init}->{data_tb_index}") or $self->error("Write Error : $self->{init}->{data_tb_index}");
	print FH $tb_index;
	close(FH);

	#インデックス登録
	open(FH, ">$self->{init}->{data_diary_index}") or $self->error("Write Error : $self->{init}->{data_diary_index}");
	print FH $new_index;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	my $del_list;
	foreach (split(/\n/, $self->{query}->{del})) {
		if ($del_list) {
			$del_list .= '、';
		}
		$del_list .= "No.$_";
	}

	if ($self->{config}->{html_archive_mode}) {
		if ($del_id) {
			unlink("$self->{init}->{archive_dir}$del_id\.$self->{init}->{archive_ext}");
		} else {
			unlink("$self->{init}->{archive_dir}$self->{query}->{del}\.$self->{init}->{archive_ext}");
		}
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = "$del_listの記事を削除しました。";

	$self->record_log($self->{message});

	return;
}

### コメントステータス変更
sub stat_comment {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my($new_data, $flag);

	open(FH, "$self->{init}->{data_comt_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

		if ($self->{query}->{stat} == $no) {
			if ($stat) {
				$new_data .= "$no\t$pno\t0\t$date\t$name\t$mail\t$url\t$subj\t$text\t$color\t$icon\t$file\t$rank\t$pwd\t$host\n";
			} else {
				$new_data .= "$no\t$pno\t1\t$date\t$name\t$mail\t$url\t$subj\t$text\t$color\t$icon\t$file\t$rank\t$pwd\t$host\n";
			}

			$flag = 1;
		} else {
			$new_data .= "$_\n";
		}
	}
	close(FH);

	if (!$flag) {
		$self->error('指定されたコメントは存在しません。');
	}

	my($index_data, $flag);

	open(FH, $self->{init}->{data_comt_index}) or $self->error("Read Error : $self->{init}->{data_comt_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $subj, $host) = split(/\t/);

		if ($self->{query}->{stat} == $no) {
			if ($stat) {
				$index_data .= "$no\t$pno\t0\t$date\t$name\t$subj\t$host\n";
			} else {
				$index_data .= "$no\t$pno\t1\t$date\t$name\t$subj\t$host\n";
			}

			$flag = 1;
		} else {
			$index_data .= "$_\n";
		}
	}
	close(FH);

	if (!$flag) {
		$self->error('指定されたインデックスは存在しません。');
	}

	open(FH, ">$self->{init}->{data_comt_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}") or $self->error("Write Error : $self->{init}->{data_comt_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}");
	print FH $new_data;
	close(FH);

	open(FH, ">$self->{init}->{data_comt_index}") or $self->error("Write Error : $self->{init}->{data_comt_index}");
	print FH $index_data;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	$self->{update}->{query}->{no} = $self->{query}->{pno};

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = "No.$self->{query}->{stat}のコメントのステータスを変更しました。";

	$self->record_log($self->{message});

	return;
}

### トラックバック削除
sub del_trackback {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{query}->{del}) {
		$self->error('削除したいトラックバックを選択してください。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my($new_index, $diary_no, %del_file);

	#削除データ検索
	open(FH, $self->{init}->{data_tb_index}) or $self->error("Read Error : $self->{init}->{data_tb_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $blog, $title, $url) = split(/\t/);

		if ($self->{query}->{del} =~ /(^|\n)$no(\n|$)/) {
			$del_file{"$self->{init}->{data_tb_dir}$pno\.$self->{init}->{data_ext}"} = 1;
		} else {
			$new_index .= "$_\n";
		}
	}
	close(FH);

	#トラックバック削除
	my $flag;

	foreach my $entry (keys %del_file) {
		my $new_data;

		open(FH, $entry) or $self->error("Read Error : $entry");
		while (<FH>) {
			chomp;
			my($no, $pno, $stat, $date, $blog, $title, $url, $excerpt) = split(/\t/);

			if ($self->{query}->{del} =~ /(^|\n)$no(\n|$)/) {
				$diary_no = $pno;
				$flag = 1;
			} else {
				$new_data .= "$_\n";
			}
		}
		close(FH);

		open(FH, ">$entry") or $self->error("Write Error : $entry");
		print FH $new_data;
		close(FH);
	}

	if (!$flag) {
		$self->error('指定されたトラックバックは存在しません。');
	}

	#インデックス登録
	open(FH, ">$self->{init}->{data_tb_index}") or $self->error("Write Error : $self->{init}->{data_tb_index}");
	print FH $new_index;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	$self->{update}->{query}->{no} = $diary_no;

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	my $del_list;
	foreach (split(/\n/, $self->{query}->{del})) {
		$del_list .= "No.$_ ";
	}

	$self->{message} = "$del_listのトラックバックを削除しました。";

	$self->record_log($self->{message});

	return;
}

### トラックバックステータス変更
sub stat_trackback {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my($new_data, $flag);

	open(FH, "$self->{init}->{data_tb_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_tb_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $blog, $title, $url, $excerpt) = split(/\t/);

		if ($self->{query}->{stat} == $no) {
			if ($stat) {
				$new_data .= "$no\t$pno\t0\t$date\t$blog\t$title\t$url\t$excerpt\n";
			} else {
				$new_data .= "$no\t$pno\t1\t$date\t$blog\t$title\t$url\t$excerpt\n";
			}

			$flag = 1;
		} else {
			$new_data .= "$_\n";
		}
	}
	close(FH);

	if (!$flag) {
		$self->error('指定されたトラックバックは存在しません。');
	}

	my($index_data, $flag);

	open(FH, $self->{init}->{data_tb_index}) or $self->error("Read Error : $self->{init}->{data_tb_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $blog, $title, $url) = split(/\t/);

		if ($self->{query}->{stat} == $no) {
			if ($stat) {
				$index_data .= "$no\t$pno\t0\t$date\t$blog\t$title\t$url\n";
			} else {
				$index_data .= "$no\t$pno\t1\t$date\t$blog\t$title\t$url\n";
			}

			$flag = 1;
		} else {
			$index_data .= "$_\n";
		}
	}
	close(FH);

	if (!$flag) {
		$self->error('指定されたインデックスは存在しません。');
	}

	open(FH, ">$self->{init}->{data_tb_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}") or $self->error("Write Error : $self->{init}->{data_tb_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}");
	print FH $new_data;
	close(FH);

	open(FH, ">$self->{init}->{data_tb_index}") or $self->error("Write Error : $self->{init}->{data_tb_index}");
	print FH $index_data;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	$self->{update}->{query}->{no} = $self->{query}->{pno};

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = "No.$self->{query}->{stat}のトラックバックのステータスを変更しました。";

	$self->record_log($self->{message});

	return;
}

### 分類追加
sub add_field {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_field}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $field_ins  = new webliberty::String($self->{query}->{field});
	my $parent_ins = new webliberty::String($self->{query}->{parent});

	$field_ins->create_line;
	$parent_ins->create_line;

	if (!$field_ins->get_string) {
		$self->error('分類名を入力してください。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	if ($parent_ins->get_string) {
		my($new_data, $flag);

		open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
		while (<FH>) {
			chomp;
			my($field, $child) = split(/<>/);

			if ($parent_ins->get_string eq $field) {
				$flag = 1;
			} elsif ($flag and $parent_ins->get_string ne $field) {
				$new_data .= $parent_ins->get_string . '<>' . $field_ins->get_string . "\n";
				$flag = 0;
			}

			$new_data .= "$_\n";
		}
		if ($flag) {
			$new_data .= $parent_ins->get_string . '<>' . $field_ins->get_string . "\n";
		}
		close(FH);

		open(FH, ">$self->{init}->{data_field}") or $self->error("Write Error : $self->{init}->{data_field}");
		print FH $new_data;
		close(FH);
	} else {
		open(FH, ">>$self->{init}->{data_field}") or $self->error("Write Error : $self->{init}->{data_field}");
		print FH "$self->{query}->{parent}$self->{query}->{field}\n";
		close(FH);
	}

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = '分類を追加しました。';

	$self->record_log($self->{message});

	return;
}

### 分類編集
sub edit_field {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_field}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my(@fields, %field, %parent, $new_data, $parent, $flag);
	my $i = 0;

	open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
	while (<FH>) {
		chomp;
		my($field, $child) = split(/<>/);

		if (!$child) {
			$parent{$field} = $field;
		}
		$field{$_} = $_;

		push(@fields, $_);
	}
	seek(FH, 0, 0);
	$i = 0;
	while (<FH>) {
		chomp;
		my($field, $child) = split(/<>/);

		if ($self->{query}->{from} ne '' and $self->{query}->{to} ne '') {
			if ($self->{query}->{to} == $i) {
				$new_data .= $fields[$self->{query}->{from}] . "\n";
			}
			if ($self->{query}->{from} != $i) {
				$new_data .= "$_\n";
			}
		} elsif (!$self->{query}->{'del' . $i}) {
			my $field_ins  = new webliberty::String($self->{query}->{'field' . $i});
			my $parent_ins = new webliberty::String($self->{query}->{'parent' . $i});

			$field_ins->create_line;
			$parent_ins->create_line;

			if ($parent and $child) {
				if ($_ ne $parent{$parent_ins->get_string} . '<>' . $field_ins->get_string) {
					$flag = 1;
				}

				$field{$_} = $parent{$parent_ins->get_string} . '<>' . $field_ins->get_string;

				$new_data .= "$field{$_}\n";
			} else {
				if ($_ ne $field_ins->get_string) {
					$flag = 1;
				}

				$parent     = $field_ins->get_string;
				$parent{$_} = $parent;
				$field{$_}  = $parent;

				$new_data .= "$parent{$_}\n";
			}
		}

		$i++;
	}
	close(FH);

	open(FH, ">$self->{init}->{data_field}") or $self->error("Write Error : $self->{init}->{data_field}");
	print FH $new_data;
	close(FH);

	if ($flag) {
		#ログ更新
		opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
		my @dir = sort { $b <=> $a } readdir(DIR);
		closedir(DIR);

		foreach my $entry (@dir) {
			if ($entry !~ /^\d\d\d\d\d\d\.$self->{init}->{data_ext}$/) {
				next;
			}

			my $new_data;

			open(FH, "$self->{init}->{data_diary_dir}$entry") or $self->error("Read Error : $self->{init}->{data_diary_dir}$entry");
			while (<FH>) {
				chomp;
				my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

				if ($field =~ /^.+<>.+$/) {
					$field = $field{$field};
				} else {
					$field = $parent{$field};
				}

				$new_data .= "$no\t$id\t$stat\t$break\t$comt\t$tb\t$field\t$date\t$name\t$subj\t$text\t$color\t$icon\t$file\t$host\n";
			}
			close(FH);

			open(FH, ">$self->{init}->{data_diary_dir}$entry") or $self->error("Write Error : $self->{init}->{data_diary_dir}$entry");
			print FH $new_data;
			close(FH);
		}

		#インデックス更新
		my $new_index;

		open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
		while (<FH>) {
			chomp;
			my($date, $no, $id, $stat, $field, $name) = split(/\t/);

			if ($field =~ /^.+<>.+$/) {
				$field = $field{$field};
			} else {
				$field = $parent{$field};
			}

			$new_index .= "$date\t$no\t$id\t$stat\t$field\t$name\n";
		}
		close(FH);

		open(FH, ">$self->{init}->{data_diary_index}") or $self->error("Write Error : $self->{init}->{data_diary_index}");
		print FH $new_index;
		close(FH);
	}

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = '分類を編集しました。';

	$self->record_log($self->{message});

	return;
}

### アイコン追加
sub add_icon {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_icon}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $init_ins = new webliberty::App::Init;
	my %label = %{$init_ins->get_label};

	if (!$self->{query}->{file}) {
		$self->error('ファイルを選択してください。');
	}

	my $file_ins = new webliberty::File($self->{query}->{file}->{file_name});
	my $name_ins = new webliberty::String($self->{query}->{name});

	$name_ins->create_line;

	if ($file_ins->get_name =~ /[^\w\-\_]/) {
		$self->error('ファイル名は半角英数字で指定してください。');
	}
	if ($file_ins->get_ext ne 'gif' and $file_ins->get_ext ne 'jpeg' and $file_ins->get_ext ne 'jpg' and $file_ins->get_ext ne 'jpe' and $file_ins->get_ext ne 'png') {
		$self->error('アップロードできるファイル形式は、<em>GIF</em>、<em>JPEG</em>、<em>PNG</em>です。');
	}
	if (!$name_ins->get_string) {
		$self->error("$label{'pc_icon'}名を入力してください。");
	}

	if ($name_ins->check_length > 20 * 2) {
		$self->error("$label{'pc_icon'}名の長さは全角20文字までにしてください。");
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my $file = $file_ins->get_name . '.' . $file_ins->get_ext;

	open(FILE, ">$self->{init}->{data_icon_dir}$file") or $self->error("アップロードファイルが保存できません。");
	binmode(FILE);
	print FILE $self->{query}->{file}->{file_data};
	close(FILE);

	open(FH, $self->{init}->{data_icon}) or $self->error("Read Error : $self->{init}->{data_icon}");
	my @icon = <FH>;
	close(FH);

	push(@icon, "$file\t" . $name_ins->get_string . "\t\t\t\n");

	open(FH, ">$self->{init}->{data_icon}") or $self->error("Write Error : $self->{init}->{data_icon}");
	print FH $self->_sort_icon(@icon);
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	$self->{message} = "$label{'pc_icon'}を新規に登録しました。";

	$self->record_log($self->{message});

	return;
}

### アイコン削除
sub del_icon {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_icon}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $init_ins = new webliberty::App::Init;
	my %label = %{$init_ins->get_label};

	if (!$self->{query}->{edit}) {
		$self->error("削除したい$label{'pc_icon'}を選択してください。");
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my $new_data;

	open(FH, $self->{init}->{data_icon}) or $self->error("Read Error : $self->{init}->{data_icon}");
	while (<FH>) {
		chomp;
		my($file, $name, $field, $user, $pwd) = split(/\t/);

		if ($self->{query}->{edit} eq $file) {
			unlink("$self->{init}->{data_icon_dir}$file");
		} else {
			$new_data .= "$_\n";
		}
	}
	close(FH);

	open(FH, ">$self->{init}->{data_icon}") or $self->error("Write Error : $self->{init}->{data_icon}");
	print FH $new_data;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	$self->{message} = "$label{'pc_icon'}$self->{query}->{edit}を削除しました。";

	$self->record_log($self->{message});

	return;
}

### インデックスページ設定
sub top {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_top}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $text_ins = new webliberty::String($self->{query}->{text});

	open(FH, ">$self->{init}->{data_top}") or $self->error("Write Error : $self->{init}->{data_top}");
	print FH $text_ins->create_text;
	close(FH);

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = 'インデックスページのテキストを設定しました。';

	$self->record_log($self->{message});

	return;
}

### コンテンツ追加
sub add_menu {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_menu}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $field_ins = new webliberty::String($self->{query}->{field});
	my $name_ins  = new webliberty::String($self->{query}->{name});
	my $url_ins   = new webliberty::String($self->{query}->{url});

	$field_ins->create_line;
	$name_ins->create_line;
	$url_ins->create_line;

	if (!$name_ins->get_string) {
		$self->error('コンテンツ名を入力してください。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my($new_data, $flag);

	open(FH, "$self->{init}->{data_menu}") or $self->error("Read Error : $self->{init}->{data_menu}");
	my @menus = <FH>;
	close(FH);

	push(@menus, $field_ins->get_string . "\t" . $name_ins->get_string . "\t" . $url_ins->get_string . "\n");

	open(FH, ">$self->{init}->{data_menu}") or $self->error("Write Error : $self->{init}->{data_menu}");
	print FH $self->_sort_item(@menus);
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = 'コンテンツを追加しました。';

	$self->record_log($self->{message});

	return;
}

### コンテンツ編集
sub edit_menu {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_menu}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my(@menus, $new_data, $field);
	my $i = 0;

	open(FH, $self->{init}->{data_menu}) or $self->error("Read Error : $self->{init}->{data_menu}");
	while (<FH>) {
		push(@menus, $_);
	}
	seek(FH, 0, 0);
	while (<FH>) {
		chomp;
		my($field, $name, $url) = split(/\t/);

		if ($self->{query}->{from} ne '' and $self->{query}->{to} ne '') {
			if ($self->{query}->{to} == $i) {
				$new_data .= $menus[$self->{query}->{from}];
			}
			if ($self->{query}->{from} != $i) {
				$new_data .= "$_\n";
			}
		} elsif (!$self->{query}->{'del' . $i}) {
			my $field_ins = new webliberty::String($self->{query}->{'field' . $i});
			my $name_ins  = new webliberty::String($self->{query}->{'name' . $i});
			my $url_ins   = new webliberty::String($self->{query}->{'url' . $i});

			$name_ins->create_line;
			$field_ins->create_line;
			$url_ins->create_line;

			$new_data .= $field_ins->get_string . "\t" . $name_ins->get_string . "\t" . $url_ins->get_string . "\n";
		}

		$i++;
	}
	close(FH);

	open(FH, ">$self->{init}->{data_menu}") or $self->error("Write Error : $self->{init}->{data_menu}");
	print FH $new_data;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = 'コンテンツを編集しました。';

	$self->record_log($self->{message});

	return;
}

### リンク追加
sub add_link {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_link}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $field_ins = new webliberty::String($self->{query}->{field});
	my $name_ins  = new webliberty::String($self->{query}->{name});
	my $url_ins   = new webliberty::String($self->{query}->{url});

	$field_ins->create_line;
	$name_ins->create_line;
	$url_ins->create_line;

	if (!$name_ins->get_string) {
		$self->error('サイト名を入力してください。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	open(FH, $self->{init}->{data_link}) or $self->error("Read Error : $self->{init}->{data_link}");
	my @links = <FH>;
	close(FH);

	push(@links, $field_ins->get_string . "\t" . $name_ins->get_string . "\t" . $url_ins->get_string . "\n");

	open(FH, ">$self->{init}->{data_link}") or $self->error("Write Error : $self->{init}->{data_link}");
	print FH $self->_sort_item(@links);
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = 'サイトを追加しました。';

	$self->record_log($self->{message});

	return;
}

### リンク編集
sub edit_link {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_link}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my(@links, $new_data, $field);
	my $i = 0;

	open(FH, $self->{init}->{data_link}) or $self->error("Read Error : $self->{init}->{data_link}");
	while (<FH>) {
		push(@links, $_);
	}
	seek(FH, 0, 0);
	while (<FH>) {
		chomp;
		my($field, $name, $url) = split(/\t/);

		if ($self->{query}->{from} ne '' and $self->{query}->{to} ne '') {
			if ($self->{query}->{to} == $i) {
				$new_data .= $links[$self->{query}->{from}];
			}
			if ($self->{query}->{from} != $i) {
				$new_data .= "$_\n";
			}
		} elsif (!$self->{query}->{'del' . $i}) {
			my $field_ins = new webliberty::String($self->{query}->{'field' . $i});
			my $name_ins  = new webliberty::String($self->{query}->{'name' . $i});
			my $url_ins   = new webliberty::String($self->{query}->{'url' . $i});

			$name_ins->create_line;
			$field_ins->create_line;
			$url_ins->create_line;

			$new_data .= $field_ins->get_string . "\t" . $name_ins->get_string . "\t" . $url_ins->get_string . "\n";
		}

		$i++;
	}
	close(FH);

	open(FH, ">$self->{init}->{data_link}") or $self->error("Write Error : $self->{init}->{data_link}");
	print FH $new_data;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = 'リンク集を編集しました。';

	$self->record_log($self->{message});

	return;
}

### プロフィール設定
sub profile {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $edit_ins = new webliberty::String($self->{query}->{edit});
	my $name_ins = new webliberty::String($self->{query}->{name});
	my $text_ins = new webliberty::String($self->{query}->{text});

	my($new_data, $flag);

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	open(FH, $self->{init}->{data_profile}) or $self->error("Read Error : $self->{init}->{data_profile}");
	while (<FH>) {
		chomp;
		my($user, $name, $text) = split(/\t/);

		if ($self->get_authority eq 'root' and $edit_ins->get_string) {
			if ($edit_ins->get_string eq $user) {
				$new_data .= "$user\t" . $name_ins->create_line . "\t" . $text_ins->create_text . "\n";

				$flag = 1;
			} else {
				$new_data .= "$user\t$name\t$text\n";
			}
		} else {
			if ($self->get_user eq $user) {
				$new_data .= "$user\t" . $name_ins->create_line . "\t" . $text_ins->create_text . "\n";

				$flag = 1;
			} else {
				$new_data .= "$user\t$name\t$text\n";
			}
		}
	}
	close(FH);

	if (!$flag) {
		if ($self->get_authority eq 'root' and $edit_ins->get_string) {
			$new_data .= $edit_ins->create_line . "\t" . $name_ins->create_line . "\t" . $text_ins->create_text . "\n";
		} else {
			$new_data .= $self->get_user . "\t" . $name_ins->create_line . "\t" . $text_ins->create_text . "\n";
		}
	}

	open(FH, ">$self->{init}->{data_profile}") or $self->error("Write Error : $self->{init}->{data_profile}");
	print FH $new_data;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	if ($self->get_authority eq 'root' and $edit_ins->get_string) {
		$self->{message} = $edit_ins->get_string . 'のプロフィールを設定しました。';
	} else {
		$self->{message} = 'プロフィールを設定しました。';
	}

	$self->record_log($self->{message});

	return;
}

### ログイン用パスワード設定
sub pwd {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $old_pwd_ins = new webliberty::String($self->{query}->{old_pwd});
	my $new_pwd_ins = new webliberty::String($self->{query}->{new_pwd});
	my $cfm_pwd_ins = new webliberty::String($self->{query}->{cfm_pwd});

	if (!$old_pwd_ins->get_string) {
		$self->error('以前のパスワードを入力してください。');
	}
	if (!$new_pwd_ins->get_string) {
		$self->error('新しく設定したいパスワードを入力してください。');
	}
	if (!$cfm_pwd_ins->get_string) {
		$self->error('確認用パスワードを入力してください。');
	}
	if ($new_pwd_ins->get_string ne $cfm_pwd_ins->get_string) {
		$self->error('新しいパスワードと確認用パスワードは、同じものを入力してください。');
	}
	if ($new_pwd_ins->check_length < 4) {
		$self->error('ログイン用パスワードは4文字以上を指定してください。');
	}

	my $flag;

	open(FH, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
	while (<FH>) {
		chomp;
		my($user, $pwd, $authority) = split(/\t/);

		if ($self->get_user eq $user and $old_pwd_ins->check_password($pwd)) {
			$flag = 1;
		}
	}
	close(FH);

	if (!$flag) {
		$self->error('以前のパスワードが違います。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my $new_data;

	open(FH, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
	while (<FH>) {
		chomp;
		my($user, $pwd, $authority) = split(/\t/);

		if ($self->get_user eq $user) {
			$new_data .= "$user\t" . $new_pwd_ins->create_password . "\t$authority\n";
		} else {
			$new_data .= "$user\t$pwd\t$authority\n";
		}
	}
	close(FH);

	open(FH, ">$self->{init}->{data_user}") or $self->error("Write Error : $self->{init}->{data_user}");
	print FH $new_data;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_admin}, $self->{init}->{des_key});
	$cookie_ins->set_cookie(
		admin_user => $self->get_user,
		admin_pwd  => $cfm_pwd_ins->get_string
	);

	$self->{message} = 'パスワードを設定しました。';

	$self->record_log($self->{message});

	return;
}

### 環境設定
sub env {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root') {
		$self->error('この操作を行う権限が与えられていません。');
	}
	if ($self->{query}->{env_site_url} and $self->{query}->{env_site_url} !~ /\/$/) {
		$self->error('サイトのURLはディレクトリまでを指定してください。');
	}

	my $new_data;
	if ($self->{query}->{exec_default}) {
		my $init_ins = new webliberty::App::Init;
		my %default  = %{$init_ins->get_config};

		foreach (keys %default) {
			$new_data .= "$_=$default{$_}\n";
		}
	} else {
		foreach (keys %{$self->{query}}) {
			if ($_ =~ /^env_/) {
				my $key   = $_;
				my $value = $self->{query}->{$_};

				$key   =~ s/^env_//;
				$value =~ s/\r?\n/\r/g;
				$value =~ s/\r/<>/g;

				$new_data .= "$key=$value\n";
			}
		}
	}

	open(FH, ">$self->{init}->{data_config}") or $self->error("Write Error : $self->{init}->{data_config}");
	print FH $new_data;
	close(FH);

	my $config_ins = new webliberty::Configure($self->{init}->{data_config});
	$self->{config} = $config_ins->get_config;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	if ($self->{query}->{exec_default}) {
		$self->{message} = '環境設定を初期値に戻しました。';
	} else {
		$self->{message} = '環境設定を実行しました。';
	}

	$self->record_log($self->{message});

	if ($self->{config}->{html_index_mode} or $self->{config}->{html_archive_mode}) {
		$self->{message} .= "<strong>外観に関する設定を変更した場合、<a href=\"$self->{init}->{script_file}?mode=admin&amp;work=build\">サイトの再構築</a>を行ってください。</strong>";
	}

	return;
}

### イラスト削除
sub del_paint {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_paint}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	opendir(DIR, $self->{init}->{paint_dir}) or $self->error("Read Error : $self->{init}->{paint_dir}");
	my @files = sort { $a <=> $b } grep { m/\d+\.\w+/g } readdir(DIR);
	close(DIR);

	my $flag;
	foreach (@files) {
		my $file_ins = new webliberty::File("$self->{init}->{paint_dir}$_");

		if ($self->{query}->{del} eq $file_ins->get_name) {
			unlink($self->{init}->{paint_dir} . $file_ins->get_name . '.' . $file_ins->get_ext);
			$flag = 1;
		}
	}
	if (!$flag) {
		$self->error('指定されたイラストは存在しません。');
	}

	opendir(DIR, $self->{init}->{pch_dir}) or $self->error("Read Error : $self->{init}->{pch_dir}");
	@files = sort { $a <=> $b } grep { m/\w+\.\w+/g } readdir(DIR);
	close(DIR);

	$flag = 0;
	foreach (@files) {
		my $file_ins = new webliberty::File("$self->{init}->{pch_dir}$_");

		if ($self->{query}->{del} eq $file_ins->get_name) {
			unlink($self->{init}->{pch_dir} . $file_ins->get_name . '.' . $file_ins->get_ext);
			$flag = 1;
		}
	}
	if (!$flag) {
		$self->error('指定されたPCHファイルは存在しません。');
	}

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	$self->{message} = "No.$self->{query}->{del}のイラストを削除しました。";

	$self->record_log($self->{message});

	return;
}

### サイト再構築
sub build {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	require webliberty::App::List;
	my $stdout = *STDOUT;

	#各分類を構築
	if ($self->{config}->{html_field_mode} and ($self->{query}->{build} eq 'index' or $self->{query}->{build} eq 'all')) {
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
	if ($self->{config}->{html_archive_mode} and ($self->{query}->{build} =~ /^\d+$/ or $self->{query}->{build} eq 'all')) {
		my($from, $to);
		if ($self->{query}->{build} =~ /^(\d+)$/) {
			$from = $1;
			$to   = $from + 50 - 1;
		}

		open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
		while (<FH>) {
			chomp;
			my($date, $no, $id, $stat, $field, $name) = split(/\t/);

			if ($self->{query}->{build} ne 'all' and ($no < $from or $no > $to)) {
				next;
			}

			my $file_name;
			if ($id) {
				$file_name = $id;
			} else {
				$file_name = $no;
			}

			my $dammy;
			if ($self->{init}->{rewrite_mode}) {
				my $init_ins = new webliberty::App::Init;
				$dammy->{init} = $init_ins->get_init;
			} else {
				$dammy->{init} = $self->{init};
			}
			$dammy->{query}->{no} = $no;

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
		close(FH);
	}

	#インデックスを構築
	if ($self->{config}->{html_index_mode} and ($self->{query}->{build} eq 'index' or $self->{query}->{build} eq 'all')) {
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

	#ナビゲーションを構築
	if ($self->{config}->{show_navigation}) {
		my $dammy;
		if ($self->{init}->{rewrite_mode}) {
			my $init_ins = new webliberty::App::Init;
			$dammy->{init} = $init_ins->get_init;
		} else {
			$dammy->{init} = $self->{init};
		}

		my $app_ins   = new webliberty::App::List($dammy->{init}, $self->{config});
		my $diary_ins = new webliberty::App::Diary($dammy->{init}, $self->{config});

		my $skin_ins = new webliberty::Skin;
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_diary}");
		$skin_ins->replace_skin(
			$diary_ins->info,
			%{$self->{plugin}}
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

	*STDOUT = $stdout;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	$self->{message} = 'サイトを再構築しました。';

	$self->record_log($self->{message});

	return;
}

### ユーザー追加
sub add_user {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root') {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $user_ins      = new webliberty::String($self->{query}->{user});
	my $pwd_ins       = new webliberty::String($self->{query}->{pwd});
	my $cfm_ins       = new webliberty::String($self->{query}->{cfm});
	my $authority_ins = new webliberty::String($self->{query}->{authority});

	$user_ins->create_line;
	$pwd_ins->create_line;
	$cfm_ins->create_line;
	$authority_ins->create_line;

	if (!$user_ins->get_string) {
		$self->error('ユーザー名を入力してください。');
	}
	if ($user_ins->get_string =~ /[^\w\d\-\_]/) {
		$self->error("ユーザー名は半角英数字で入力してください。");
	}
	if (!$pwd_ins->get_string) {
		$self->error('パスワードを入力してください。');
	}
	if (!$cfm_ins->get_string) {
		$self->error('確認用パスワードを入力してください。');
	}
	if ($pwd_ins->get_string ne $cfm_ins->get_string) {
		$self->error('パスワードと確認用パスワードは、同じものを入力してください。');
	}
	if ($pwd_ins->check_length < 4) {
		$self->error('パスワードは4文字以上を指定してください。');
	}

	$pwd_ins->create_password;

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	open(FH, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
	my @users = <FH>;
	close(FH);

	foreach (@users) {
		my($user, $pwd, $authority) = split(/\t/);

		if ($user_ins->get_string eq $user) {
			$self->error("ユーザー名 $user は、すでに使用されています。");
		}
	}

	push(@users, $user_ins->get_string . "\t" . $pwd_ins->get_string . "\t" . $authority_ins->get_string . "\n");

	open(FH, ">$self->{init}->{data_user}") or $self->error("Write Error : $self->{init}->{data_user}");
	print FH sort { $a cmp $b } @users;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = 'ユーザー' . $user_ins->get_string . 'を追加しました。';

	$self->record_log($self->{message});

	if ($self->{config}->{html_index_mode} or $self->{config}->{profile_mode}) {
		$self->{message} .= "このユーザーの<a href=\"$self->{init}->{script_file}?mode=admin&amp;work=profile\">プロフィールの設定</a>を行う事ができます。</em>";
	}

	return;
}

### ユーザー編集
sub edit_user {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root') {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my(@users, $new_data, $del_data);
	my $i = 0;

	open(FH, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
	while (<FH>) {
		chomp;
		my($user, $pwd, $authority) = split(/\t/);

		if ($self->{query}->{'del' . $i}) {
			if ($user eq $self->get_user) {
				$self->error('ログイン中のユーザーは削除できません。');
			}

			$del_data .= "$user\n";
		} else {
			my $authority_ins = new webliberty::String($self->{query}->{'authority' . $i});
			$authority_ins->create_line;

			if ($user eq $self->get_user and $authority_ins->get_string ne $authority) {
				$self->error('ログインユーザーの権限は変更できません。');
			}

			$new_data .= "$user\t$pwd\t" . $authority_ins->get_string . "\n";
		}

		$i++;
	}
	close(FH);

	open(FH, ">$self->{init}->{data_user}") or $self->error("Write Error : $self->{init}->{data_user}");
	print FH $new_data;
	close(FH);

	$new_data = '';

	open(FH, $self->{init}->{data_profile}) or $self->error("Read Error : $self->{init}->{data_profile}");
	while (<FH>) {
		chomp;
		my($user, $name, $text) = split(/\t/);

		if ($del_data !~ /$user\n/) {
			$new_data .= "$_\n"
		}
	}
	close(FH);

	open(FH, ">$self->{init}->{data_profile}") or $self->error("Write Error : $self->{init}->{data_profile}");
	print FH $new_data;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	#データ更新
	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = 'ユーザー情報を編集しました。';

	$self->record_log($self->{message});

	return;
}

### ログアウト
sub logout {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_admin}, $self->{init}->{des_key});
	$cookie_ins->set_cookie(
		admin_user => '',
		admin_pwd  => ''
	);

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 投稿フォーム表示
sub output_form {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{message}) {
		if ($self->{query}->{edit}) {
			$self->{message} = "No.$self->{query}->{edit}の記事を入力し、<em>投稿ボタン</em>を押してください。";
		} else {
			$self->{message} = '記事を入力し、<em>投稿ボタン</em>を押してください。';
		}
	}

	my($paint, $pch);
	if ($self->{query}->{exec_paint}) {
		opendir(DIR, $self->{init}->{paint_dir}) or $self->error("Read Error : $self->{init}->{paint_dir}");
		my @files = sort { $b <=> $a } grep { m/\d+\.\w+/g } readdir(DIR);
		close(DIR);

		my $file_ins = new webliberty::String($files[0]);
		$file_ins->create_number;

		$paint = '$PAINT' . $file_ins->get_string;
		$pch   = '$PCH' . $file_ins->get_string;

		if (-e $self->{init}->{pch_jar}) {
			$self->{message} .= "<em>$paint</em>の部分には投稿したイラストが、<em>$pch</em>の部分には描画アニメーションへのリンクが表示されます。";

			$paint .= "\n$pch";
		} else {
			$self->{message} .= "<em>$paint</em>の部分には投稿したイラストが表示されます。";
		}
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_form}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	my(%form, $form_label);

	if ($self->{query}->{work} eq 'edit') {
		if (!$self->{query}->{edit}) {
			$self->error('編集したい記事を選択してください。');
		}

		my($edit_date, $edit_name);
		open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
		while (<FH>) {
			chomp;
			my($date, $no, $id, $stat, $field, $name) = split(/\t/);

			if ($self->{query}->{edit} == $no) {
				$edit_date = $date;
				$edit_name = $name;

				last;
			}
		}
		close(FH);

		if ($self->get_authority ne 'root' and $self->get_user ne $edit_name) {
			$self->error('他のユーザーの記事は編集できません。');
		}

		my $edit_file;
		if ($edit_date =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
			$edit_file = "$self->{init}->{data_diary_dir}$1$2\.$self->{init}->{data_ext}";
		}

		open(FH, "$edit_file") or $self->error("Read Error : $edit_file");
		while (<FH>) {
			chomp;
			my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

			if ($self->{query}->{edit} == $no) {
				%form = $diary_ins->diary_form($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host);
			}
		}
		close(FH);

		$form_label = '記事編集';
	} else {
		my($sec, $min, $hour, $day, $mon, $year) = localtime(time);
		my $date = sprintf("%04d%02d%02d%02d%02d", $year + 1900, $mon + 1, $day, $hour, $min);

		%form = $diary_ins->diary_form('', '', $self->{config}->{default_stat}, $self->{config}->{default_break}, $self->{config}->{default_comt}, $self->{config}->{default_tb}, '', $date, '', '', $paint, '', '', '', '');

		$form_label = '新規投稿';
	}

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');
	print $skin_ins->get_replace_data(
		'form',
		%form,
		FORM_LABEL => $form_label,
		FORM_WORK  => $self->{query}->{work},
		FORM_EXT1  => '',
		FORM_EXT2  => '',
		FORM_EXT3  => '',
		FORM_EXT4  => '',
		FORM_EXT5  => '',
		FORM_TBURL => $self->{query}->{tb_url},
		FORM_PING  => ' checked="checked"'
	);
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### プレビュー表示
sub output_preview {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $date = "$self->{query}->{year}$self->{query}->{month}$self->{query}->{day}$self->{query}->{hour}$self->{query}->{minute}";

	my($new_field, $i);

	open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
	while (<FH>) {
		chomp;

		if ($self->{query}->{field} == ++$i) {
			$new_field = $_;
		}
	}
	close(FH);

	my(%file, %ext, $files, $flag);

	foreach (1 .. 5) {
		if ($self->{query}->{work} eq 'edit' or $self->{query}->{'file' . $_} or $self->{query}->{'ext' . $_}) {
			$flag = 1;
		}
	}

	if ($flag) {
		my $org_files;

		if ($self->{query}->{work} eq 'edit') {
			my $edit_file;
			if ($date =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
				$edit_file = "$self->{init}->{data_diary_dir}$1$2\.$self->{init}->{data_ext}";
			}

			open(FH, $edit_file) or $self->error("Read Error : $edit_file");
			while (<FH>) {
				chomp;
				my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

				if ($self->{query}->{edit} == $no) {
					$org_files = $file;
					last;
				}
			}
			close(FH);
		}

		foreach (1 .. 5) {
			my @org_files = split(/<>/, $org_files);

			if ($self->{query}->{'file' . $_}) {
				my $file_ins = new webliberty::File($self->{query}->{'file' . $_}->{file_name});
				$file{$_} = $self->{init}->{data_tmp_file};
				$ext{$_}  = $file_ins->get_ext;

				open(FH, ">$self->{init}->{data_upfile_dir}$self->{init}->{data_tmp_file}" . $self->get_user . $_) or $self->error("Write Error : $self->{init}->{data_upfile_dir}$self->{init}->{data_tmp_file}" . $self->get_user . $_);
				binmode(FH);
				print FH $self->{query}->{'file' . $_}->{file_data};
				close(FH);
			} elsif ($self->{query}->{'ext' . $_}) {
				$file{$_} = $self->{init}->{data_tmp_file};
				$ext{$_}  = $self->{query}->{'ext' . $_};
			} elsif ($org_files[$_ - 1]) {
				$file{$_} = $org_files[$_ - 1];
			}
		}

		foreach (1 .. 5) {
			if ($_ > 1) {
				$files .= '<>';
			}
			$files .= "$file{$_}";
		}
	}

	if ($self->{query}->{image}) {
		open(FH, ">$self->{init}->{data_image_dir}$self->{init}->{data_tmp_file}" . $self->get_user) or $self->error("Write Error : $self->{init}->{data_image_dir}$self->{init}->{data_tmp_file}" . $self->get_user);
		binmode(FH);
		print FH $self->{query}->{image}->{file_data};
		close(FH);
	}

	my $form_ping;
	if ($self->{query}->{ping}) {
		$form_ping  = ' checked="checked"';
	} else {
		$form_ping  = '';
	}

	if (!$self->{message}) {
		$self->{message} = 'この内容で投稿します。よろしければ<em>投稿ボタン</em>を押してください。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_form}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_diary}", available => 'diary_head,diary,diary_delimiter,diary_foot');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	my $name_ins = new webliberty::String($self->{query}->{name});
	my $subj_ins = new webliberty::String($self->{query}->{subj});
	my $text_ins = new webliberty::String($self->{query}->{text});

	$name_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');
	print $skin_ins->get_replace_data(
		'form',
		$diary_ins->diary_form($self->{query}->{edit}, $self->{query}->{id}, $self->{query}->{stat}, $self->{query}->{break}, $self->{query}->{comt}, $self->{query}->{tb}, $self->{query}->{field}, $date, $name_ins->get_string, $subj_ins->get_string, $text_ins->get_string, $self->{query}->{color}, $self->{query}->{icon}, $files, ''),
		FORM_LABEL => 'プレビュー',
		FORM_WORK  => $self->{query}->{work},
		FORM_EXT1  => $ext{'1'},
		FORM_EXT2  => $ext{'2'},
		FORM_EXT3  => $ext{'3'},
		FORM_EXT4  => $ext{'4'},
		FORM_EXT5  => $ext{'5'},
		FORM_TBURL => $self->{query}->{tb_url},
		FORM_PING  => $form_ping
	);
	print $skin_ins->get_data('diary_head');
	print $skin_ins->get_replace_data(
		'diary',
		$diary_ins->diary_article(0, '', $self->{query}->{stat}, $self->{query}->{break}, $self->{query}->{comt}, $self->{query}->{tb}, $new_field, $date, '', $subj_ins->get_string, $text_ins->get_string, $self->{query}->{color}, $self->{query}->{icon}, '', '')
	);
	print $skin_ins->get_data('diary_delimiter');
	print $skin_ins->get_data('diary_foot');
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 投稿記事一覧表示
sub output_edit {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{message}) {
		$self->{message} = '記事を選択し、<em>編集ボタン</em>か<em>削除ボタン</em>を押してください。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_edit}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	$self->{html}->{header}    = $skin_ins->get_data('header');
	$self->{html}->{work_head} = $skin_ins->get_data('work_head');
	$self->{html}->{work}      = $self->work_navi($skin_ins);
	$self->{html}->{work_foot} = $skin_ins->get_data('work_foot');
	$self->{html}->{contents}  = $skin_ins->get_data('contents');

	my($index_size, $index_date, $index_no);

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if ($index_size == $self->{query}->{page} * $self->{config}->{admin_size}) {
			$index_date = $date;
			$index_no   = $no;
		}

		$index_size++;
	}
	close(FH);

	my $start_file;
	if ($index_date =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
		$start_file = "$self->{init}->{data_diary_dir}$1$2\.$self->{init}->{data_ext}";
	}

	opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
	my @dir = sort { $b <=> $a } readdir(DIR);
	closedir(DIR);

	my($dir_flag, $file_flag, $i);

	$self->{html}->{diary_head} = $skin_ins->get_data('diary_head');
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

				if ($no == $index_no) {
					$file_flag = 1;
				}
				if ($file_flag) {
					$i++;
					if ($i > $self->{config}->{admin_size}) {
						$file_flag = 0;
						last;
					}

					$self->{html}->{diary} .= $skin_ins->get_replace_data(
						'diary',
						$diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host)
					);
				}
			}
			close(FH);
		}
	}
	$self->{html}->{diary_foot} = $skin_ins->get_data('diary_foot');

	my $page_list;
	foreach (0 .. int(($index_size - 1) / $self->{config}->{admin_size})) {
		if ($_ == $self->{query}->{page}) {
			$page_list .= "<option value=\"$_\" selected=\"selected\">ページ" . ($_ + 1) . "</option>";
		} else {
			$page_list .= "<option value=\"$_\">ページ" . ($_ + 1) . "</option>";
		}
	}
	$self->{html}->{page} = $skin_ins->get_replace_data(
		'page',
		PAGE_LIST => $page_list
	);

	$self->{html}->{navi}   = $skin_ins->get_data('navi');
	$self->{html}->{footer} = $skin_ins->get_data('footer');

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

### コメント一覧表示
sub output_comment {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_comment}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	if (!$self->{message}) {
		$self->{message} = 'コメントを選択し、<em>編集ボタン</em>か<em>削除ボタン</em>を押してください。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_comment}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	$self->{html}->{header}    = $skin_ins->get_data('header');
	$self->{html}->{work_head} = $skin_ins->get_data('work_head');
	$self->{html}->{work}      = $self->work_navi($skin_ins);
	$self->{html}->{work_foot} = $skin_ins->get_data('work_foot');
	$self->{html}->{contents}  = $skin_ins->get_data('contents');

	$self->{html}->{diary_head} = $skin_ins->get_data('diary_head');

	my($index_size, $i);

	my $comt_start = $self->{config}->{admin_size} * $self->{query}->{page};
	my $comt_end   = $comt_start + $self->{config}->{admin_size};

	open(FH, $self->{init}->{data_comt_index}) or $self->error("Read Error : $self->{init}->{data_comt_index}");
	while (<FH>) {
		$index_size++;
	}
	seek(FH, 0, 0);
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $subj, $host) = split(/\t/);

		$i++;
		if ($i <= $comt_start) {
			next;
		} elsif ($i > $comt_end) {
			last;
		}

		$self->{html}->{comment} .= $skin_ins->get_replace_data(
			'comment',
			$diary_ins->comment_article($no, $pno, $stat, $date, $name, '', '', $subj, '', '', '', '', '', '', $host)
		);
	}
	close(FH);

	$self->{html}->{diary_foot} = $skin_ins->get_data('diary_foot');

	my $page_list;
	foreach (0 .. int(($index_size - 1) / $self->{config}->{admin_size})) {
		if ($_ == $self->{query}->{page}) {
			$page_list .= "<option value=\"$_\" selected=\"selected\">ページ" . ($_ + 1) . "</option>";
		} else {
			$page_list .= "<option value=\"$_\">ページ" . ($_ + 1) . "</option>";
		}
	}
	$self->{html}->{page} = $skin_ins->get_replace_data(
		'page',
		PAGE_LIST => $page_list
	);

	$self->{html}->{navi}   = $skin_ins->get_data('navi');
	$self->{html}->{footer} = $skin_ins->get_data('footer');

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

### トラックバック一覧表示
sub output_trackback {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_trackback}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	if (!$self->{message}) {
		$self->{message} = 'トラックバックを選択し、<em>削除ボタン</em>を押してください。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_trackback}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	$self->{html}->{header}    = $skin_ins->get_data('header');
	$self->{html}->{work_head} = $skin_ins->get_data('work_head');
	$self->{html}->{work}      = $self->work_navi($skin_ins);
	$self->{html}->{work_foot} = $skin_ins->get_data('work_foot');
	$self->{html}->{contents}  = $skin_ins->get_data('contents');

	$self->{html}->{trackback_head} = $skin_ins->get_data('trackback_head');

	my($index_size, $i);

	my $tb_start = $self->{config}->{admin_size} * $self->{query}->{page};
	my $tb_end   = $tb_start + $self->{config}->{admin_size};

	open(FH, $self->{init}->{data_tb_index}) or $self->error("Read Error : $self->{init}->{data_tb_index}");
	while (<FH>) {
		$index_size++;
	}
	seek(FH, 0, 0);
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $blog, $title, $url) = split(/\t/);

		$i++;
		if ($i <= $tb_start) {
			next;
		} elsif ($i > $tb_end) {
			last;
		}

		$self->{html}->{trackback} .= $skin_ins->get_replace_data(
			'trackback',
			$diary_ins->trackback_article($no, $pno, $stat, $date, $blog, $title, $url, '')
		);
	}
	close(FH);

	$self->{html}->{trackback_foot} = $skin_ins->get_data('trackback_foot');

	my $page_list;
	foreach (0 .. int(($index_size - 1) / $self->{config}->{admin_size})) {
		if ($_ == $self->{query}->{page}) {
			$page_list .= "<option value=\"$_\" selected=\"selected\">ページ" . ($_ + 1) . "</option>";
		} else {
			$page_list .= "<option value=\"$_\">ページ" . ($_ + 1) . "</option>";
		}
	}
	$self->{html}->{page} = $skin_ins->get_replace_data(
		'page',
		PAGE_LIST => $page_list
	);

	$self->{html}->{navi}   = $skin_ins->get_data('navi');
	$self->{html}->{footer} = $skin_ins->get_data('footer');

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

### 処理内容確認表示
sub output_confirm {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my($info_heading, $info_message);
	if ($self->{query}->{work} eq 'edit') {
		$info_heading = '記事削除';
		$info_message = '記事';
	} elsif ($self->{query}->{work} eq 'comment') {
		if ($self->{query}->{del}) {
			$info_heading = 'コメント削除';
		} else {
			$info_heading = 'コメント承認';
		}
		$info_message = 'コメント';
	} elsif ($self->{query}->{work} eq 'trackback') {
		if ($self->{query}->{del}) {
			$info_heading = 'トラックバック削除';
		} else {
			$info_heading = 'トラックバック承認';
		}
		$info_message = 'トラックバック';
	} elsif ($self->{query}->{work} eq 'env') {
		$info_heading = '環境設定';
	} elsif ($self->{query}->{work} eq 'paint') {
		$info_heading = 'イラスト削除';
		$info_message = 'イラスト';
		$self->{query}->{del} = $self->{query}->{pch};
	}

	if ($self->{query}->{work} ne 'env' and !$self->{query}->{del} and !$self->{query}->{stat}) {
		$self->error("作業対象を選択してください。");
	}

	if (!$self->{message}) {
		if ($self->{query}->{work} eq 'env') {
			$self->{message} = "環境設定内容を初期値に戻します。よろしければ、<em>実行ボタン</em>を押してください。";
		} elsif ($self->{query}->{del}) {
			if ($self->{query}->{work} eq 'edit') {
				my $flag;

				open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
				while (<FH>) {
					chomp;
					my($date, $no, $id, $stat, $field, $name) = split(/\t/);

					if ($self->{query}->{del} =~ /(^|\n)$no(\n|$)/) {
						if ($self->get_authority ne 'root' and $self->get_user ne $name) {
							$flag = 1;

							last;
						}
					}
				}
				close(FH);

				if ($flag) {
					$self->error('他のユーザーの記事は削除できません。');
				}
			}

			my $del_list;
			foreach (split(/\n/, $self->{query}->{del})) {
				if ($del_list) {
					$del_list .= '、';
				}
				$del_list .= "No.$_";
			}

			$self->{message} = "$del_listの$info_messageを削除します。よろしければ、<em>実行ボタン</em>を押してください。";
		} else {
			my $flag;

			if ($self->{query}->{work} eq 'comment') {
				open(FH, "$self->{init}->{data_comt_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}");
				while (<FH>) {
					chomp;
					my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

					if ($self->{query}->{stat} == $no) {
						if ($stat == 2) {
							$flag = 2;
						} elsif ($stat) {
							$flag = 1;
						}

						last;
					}
				}
				close(FH);
			} else {
				open(FH, "$self->{init}->{data_tb_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_tb_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}");
				while (<FH>) {
					chomp;
					my($no, $pno, $stat, $date, $blog, $title, $url, $excerpt) = split(/\t/);

					if ($self->{query}->{stat} == $no) {
						if ($stat) {
							$flag = 1;
						}

						last;
					}
				}
				close(FH);
			}

			if ($flag == 2) {
				$self->error("No.$self->{query}->{stat}の$info_messageは管理者にのみ公開されています。承認/未承認の設定はできません。");
			} elsif ($flag == 1) {
				$self->{message} = "No.$self->{query}->{stat}の$info_messageを未承認にします。よろしければ、<em>実行ボタン</em>を押してください。";
			} else {
				$self->{message} = "No.$self->{query}->{stat}の$info_messageを承認します。よろしければ、<em>実行ボタン</em>を押してください。";
			}
		}
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_confirm}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	my $confirm_data;
	if ($self->{query}->{work} eq 'env') {
		$confirm_data = "<input type=\"hidden\" name=\"exec_default\" value=\"on\" />";
	} elsif ($self->{query}->{del}) {
		$confirm_data = "<input type=\"hidden\" name=\"exec_del\" value=\"on\" />";

		foreach (split(/\n/, $self->{query}->{del})) {
			$confirm_data .= "<input type=\"hidden\" name=\"del\" value=\"$_\" />";
		}
	} else {
		$confirm_data  = "<input type=\"hidden\" name=\"exec_stat\" value=\"on\" />";
		$confirm_data .= "<input type=\"hidden\" name=\"stat\" value=\"$self->{query}->{stat}\" />";
		$confirm_data .= "<input type=\"hidden\" name=\"pno\" value=\"$self->{query}->{pno}\" />";
	}

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	$self->{html}->{header}    = $skin_ins->get_data('header');
	$self->{html}->{work_head} = $skin_ins->get_data('work_head');
	$self->{html}->{work}      = $self->work_navi($skin_ins);
	$self->{html}->{work_foot} = $skin_ins->get_data('work_foot');

	$self->{html}->{contents} = $skin_ins->get_replace_data(
		'contents',
		CONFIRM_HEADING => $info_heading,
		CONFIRM_WORK    => $self->{query}->{work},
		CONFIRM_DATA    => $confirm_data
	);

	$self->{html}->{navi}   = $skin_ins->get_data('navi');
	$self->{html}->{footer} = $skin_ins->get_data('footer');

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

### データ確認
sub output_view {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{query}->{no}) {
		if ($self->{query}->{work} eq 'comment') {
			$self->error('表示したいコメントを選択してください。');
		} else {
			$self->error('表示したいトラックバックを選択してください。');
		}
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_diary}", available => 'comment_head,comment,comment_foot,trackback_head,trackback,trackback_foot');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	$self->{html}->{header}    = $skin_ins->get_data('header');
	$self->{html}->{work_head} = $skin_ins->get_data('work_head');
	$self->{html}->{work}      = $self->work_navi($skin_ins);
	$self->{html}->{work_foot} = $skin_ins->get_data('work_foot');

	if ($self->{query}->{work} eq 'comment') {
		$self->{html}->{comment} = $skin_ins->get_replace_data(
			'comment_head',
			ARTICLE_COMMENT_START => '<!--',
			ARTICLE_COMMENT_END   => '-->',
		);

		my $flag;

		open(FH, "$self->{init}->{data_comt_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}");
		while (<FH>) {
			chomp;
			my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

			if ($self->{query}->{no} == $no) {
				$self->{html}->{comment} .= $skin_ins->get_replace_data(
					'comment',
					$diary_ins->comment_article($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host)
				);

				$flag = 1;
			}
		}
		close(FH);

		$self->{html}->{comment} .= $skin_ins->get_replace_data(
			'comment_foot',
			ARTICLE_COMMENT_START => '<!--',
			ARTICLE_COMMENT_END   => '-->',
		);

		if (!$flag) {
			$self->error('指定されたコメントは存在しません。');
		}
	} else {
		$self->{html}->{trackback} .= $skin_ins->get_replace_data(
			'trackback_head',
			ARTICLE_TRACKBACK_START => '<!--',
			ARTICLE_TRACKBACK_END   => '-->',
		);

		my $flag;

		open(FH, "$self->{init}->{data_tb_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_tb_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}");
		while (<FH>) {
			chomp;
			my($no, $pno, $stat, $date, $blog, $title, $url, $excerpt) = split(/\t/);

			if ($self->{query}->{no} == $no) {
				$self->{html}->{trackback} .= $skin_ins->get_replace_data(
					'trackback',
					$diary_ins->trackback_article($no, $pno, $stat, $date, $blog, $title, $url, $excerpt)
				);

				$flag = 1;
			}
		}
		close(FH);

		$self->{html}->{trackback} .= $skin_ins->get_replace_data(
			'trackback_foot',
			ARTICLE_TRACKBACK_START => '<!--',
			ARTICLE_TRACKBACK_END   => '-->',
		);

		if (!$flag) {
			$self->error('指定されたトラックバックは存在しません。');
		}
	}

	$self->{html}->{navi}   = $skin_ins->get_data('navi');
	$self->{html}->{footer} = $skin_ins->get_data('footer');

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

### 分類設定画面
sub output_field {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_field}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	if (!$self->{message}) {
		$self->{message} = '投稿時の記事分類を設定する事ができます。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_field}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');

	my @parents;

	my $form_parent;
	open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
	while (<FH>) {
		chomp;
		my($field, $child) = split(/<>/);

		if (!$child) {
			$form_parent .= "<option value=\"$field\">$field</option>";

			push(@parents, $field);
		}
	}
	close(FH);

	print $skin_ins->get_replace_data(
		'contents',
		FORM_PARENT => $form_parent
	);
	print $skin_ins->get_data('field_head');

	my $i = 0;

	open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
	while (<FH>) {
		chomp;
		my($field, $child) = split(/<>/);

		if ($child) {
			my $field_list;
			foreach (@parents) {
				if ($field eq $_) {
					$field_list .= "<option value=\"$_\" selected=\"selected\">$_</option>";
				} else {
					$field_list .= "<option value=\"$_\">$_</option>";
				}
			}
			$field_list = "<select name=\"parent$i\" xml:lang=\"ja\" lang=\"ja\"><option value=\"\">なし</option>$field_list</select>";

			print $skin_ins->get_replace_data(
				'child',
				FIELD_NAME   => $child,
				FIELD_PARENT => $field_list,
				FIELD_NO     => $i
			);
		} else {
			print $skin_ins->get_replace_data(
				'parent',
				FIELD_NAME => $field,
				FIELD_NO   => $i
			);
		}

		$i++;
	}
	close(FH);

	print $skin_ins->get_data('field_foot');
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### アイコン設定画面表示
sub output_icon {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_icon}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	if (!$self->{message}) {
		$self->{message} = 'アイコンを登録する事ができます。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_icon}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');

	print $skin_ins->get_replace_data(
		'contents',
		FORM_EDIT => '',
		FORM_FILE => '',
		FORM_NAME => '',
		FORM_USER => ''
	);
	print $skin_ins->get_data('icon_head');

	my $i = 0;

	open(FH, $self->{init}->{data_icon}) or $self->error("Read Error : $self->{init}->{data_icon}");
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
			'icon',
			ICON_FILE  => $file,
			ICON_IMAGE => $image,
			ICON_NAME  => $name,
			ICON_USER  => $user
		);
	}
	close(FH);

	print $skin_ins->get_data('icon_foot');
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### インデックスページ設定画面
sub output_top {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_top}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	if (!$self->{message}) {
		$self->{message} = 'インデックスページに表示するテキストを設定します。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_top}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	open(FH, $self->{init}->{data_top}) or $self->error("Read Error : $self->{init}->{data_top}");
	my $text = <FH>;
	close(FH);

	my $text_ins = new webliberty::String($text);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');
	print $skin_ins->get_replace_data(
		'contents',
		FORM_TEXT => $text_ins->create_plain
	);
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### コンテンツ設定画面表示
sub output_menu {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_menu}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	if (!$self->{message}) {
		$self->{message} = 'コンテンツを設定する事ができます。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_menu}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');

	my $form_field;
	foreach (split(/<>/, $self->{config}->{menu_list})) {
		$form_field .= "<option value=\"$_\">$_</option>";
	}

	print $skin_ins->get_replace_data(
		'contents',
		FORM_FIELD => $form_field
	);
	print $skin_ins->get_data('menu_head');

	my $i = 0;

	open(FH, $self->{init}->{data_menu}) or $self->error("Read Error : $self->{init}->{data_menu}");
	while (<FH>) {
		chomp;
		my($field, $name, $url) = split(/\t/);

		my $field_list;
		foreach (split(/<>/, $self->{config}->{menu_list})) {
			if ($field eq $_) {
				$field_list .= "<option value=\"$_\" selected=\"selected\">$_</option>";
			} else {
				$field_list .= "<option value=\"$_\">$_</option>";
			}
		}
		$field_list = "<select name=\"field$i\" xml:lang=\"ja\" lang=\"ja\"><option value=\"\">なし</option>$field_list</select>";

		print $skin_ins->get_replace_data(
			'menu',
			MENU_FIELD => $field_list,
			MENU_NAME  => $name,
			MENU_URL   => $url,
			MENU_NO    => $i
		);

		$i++;
	}
	close(FH);

	print $skin_ins->get_data('menu_foot');
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### リンク集設定画面表示
sub output_link {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_link}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	if (!$self->{message}) {
		$self->{message} = 'リンク集を設定する事ができます。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_link}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');

	my $form_field;
	foreach (split(/<>/, $self->{config}->{link_list})) {
		$form_field .= "<option value=\"$_\">$_</option>";
	}

	print $skin_ins->get_replace_data(
		'contents',
		FORM_FIELD => $form_field
	);
	print $skin_ins->get_data('link_head');

	my $i = 0;

	open(FH, $self->{init}->{data_link}) or $self->error("Read Error : $self->{init}->{data_link}");
	while (<FH>) {
		chomp;
		my($field, $name, $url) = split(/\t/);

		my $field_list;
		foreach (split(/<>/, $self->{config}->{link_list})) {
			if ($field eq $_) {
				$field_list .= "<option value=\"$_\" selected=\"selected\">$_</option>";
			} else {
				$field_list .= "<option value=\"$_\">$_</option>";
			}
		}
		$field_list = "<select name=\"field$i\" xml:lang=\"ja\" lang=\"ja\"><option value=\"\">なし</option>$field_list</select>";

		print $skin_ins->get_replace_data(
			'link',
			LINK_FIELD => $field_list,
			LINK_NAME  => $name,
			LINK_URL   => $url,
			LINK_NO    => $i
		);

		$i++;
	}
	close(FH);

	print $skin_ins->get_data('link_foot');
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### プロフィール設定画面
sub output_profile {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $edit_ins = new webliberty::String($self->{query}->{edit});
	$edit_ins->create_line;

	if (!$self->{message}) {
		if ($self->get_authority eq 'root' and $edit_ins->get_string) {
			$self->{message} = '<em>' . $edit_ins->get_string . '</em>のプロフィールを設定します。';
		} else {
			$self->{message} = 'プロフィールを設定します。';
		}
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_profile}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	my(%name, %text);

	open(FH, $self->{init}->{data_profile}) or $self->error("Read Error : $self->{init}->{data_profile}");
	while (<FH>) {
		chomp;
		my($user, $name, $text) = split(/\t/);

		$name{$user} = $name;
		$text{$user} = $text;
	}
	close(FH);

	my($name_ins, $text_ins);

	if ($self->get_authority eq 'root' and $edit_ins->get_string) {
		$name_ins = new webliberty::String($name{$edit_ins->get_string});
		$text_ins = new webliberty::String($text{$edit_ins->get_string});
	} else {
		$name_ins = new webliberty::String($name{$self->get_user});
		$text_ins = new webliberty::String($text{$self->get_user});
	}

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');
	print $skin_ins->get_replace_data(
		'contents',
		FORM_USER => $edit_ins->get_string,
		FORM_NAME => $name_ins->create_plain,
		FORM_TEXT => $text_ins->create_plain
	);

	if ($self->{config}->{user_mode} and $self->get_authority eq 'root') {
		print $skin_ins->get_data('profile_head');

		open(FH, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
		while (<FH>) {
			chomp;
			my($user, $pwd, $authority) = split(/\t/);

			my $authority_list;
			if ($authority eq 'root') {
				$authority = 'システム管理者';
			} else {
				$authority = 'ゲストユーザー';
			}

			print $skin_ins->get_replace_data(
				'profile',
				PROFILE_USER      => $user,
				PROFILE_PWD       => $pwd,
				PROFILE_AUTHORITY => $authority,
				PROFILE_NAME      => $name{$user},
				PROFILE_TEXT      => $text{$user}
			);
		}
		close(FH);

		print $skin_ins->get_data('profile_foot');
	}

	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### ログインパスワード設定画面
sub output_pwd {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{message}) {
		$self->{message} = 'ログイン用パスワードを設定します。以前のパスワードと新しく設定したいパスワードを入力してください。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_pwd}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');
	print $skin_ins->get_data('contents');
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 環境設定画面表示
sub output_env {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root') {
		$self->error('この操作を行う権限が与えられていません。');
	}

	if (!$self->{message}) {
		$self->{message} = '設定を変更し、<em>設定ボタン</em>を押してください。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_env}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	my $info_path;
	if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
		$info_path = $1;
	}

	my $show_comt;
	if ($self->{config}->{show_comt} eq '1') {
		$show_comt = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$show_comt = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $show_tb;
	if ($self->{config}->{show_tb} eq '1') {
		$show_tb = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$show_tb = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $rss_mode;
	if ($self->{config}->{rss_mode} eq '2') {
		$rss_mode = '<option value="2" selected="selected">全文を配信（ファイルも配信）</option><option value="1">全文を配信</option><option value="0">概要を配信</option>';
	} elsif ($self->{config}->{rss_mode} eq '1') {
		$rss_mode = '<option value="2">全文を配信（ファイルも配信）</option><option value="1" selected="selected">全文を配信</option><option value="0">概要を配信</option>';
	} else {
		$rss_mode = '<option value="2">全文を配信（ファイルも配信）</option><option value="1">全文を配信</option><option value="0" selected="selected">概要を配信</option>';
	}

	my $use_field;
	if ($self->{config}->{use_field} eq '1') {
		$use_field = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$use_field = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $use_icon;
	if ($self->{config}->{use_icon} eq '1') {
		$use_icon = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$use_icon = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $use_color;
	if ($self->{config}->{use_color} eq '1') {
		$use_color = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$use_color = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $use_file;
	if ($self->{config}->{use_file} eq '1') {
		$use_file = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$use_file = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $use_image;
	if ($self->{config}->{use_image} eq '1') {
		$use_image = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$use_image = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $use_id;
	if ($self->{config}->{use_id} eq '1') {
		$use_id = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$use_id = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $use_tburl;
	if ($self->{config}->{use_tburl} eq '1') {
		$use_tburl = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$use_tburl = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $default_stat;
	if ($self->{config}->{default_stat} eq '1') {
		$default_stat = '<option value="1" selected="selected">公開する</option><option value="0">公開しない</option>';
	} else {
		$default_stat = '<option value="1">公開する</option><option value="0" selected="selected">公開しない</option>';
	}

	my $default_break;
	if ($self->{config}->{default_break} eq '1') {
		$default_break = '<option value="1" selected="selected">変換する</option><option value="0">変換しない</option>';
	} else {
		$default_break = '<option value="1">変換する</option><option value="0" selected="selected">変換しない</option>';
	}

	my $default_comt;
	if ($self->{config}->{default_comt} eq '1') {
		$default_comt = '<option value="1" selected="selected">受け付ける</option><option value="0">受け付けない</option>';
	} else {
		$default_comt = '<option value="1">受け付ける</option><option value="0" selected="selected">受け付けない</option>';
	}

	my $default_tb;
	if ($self->{config}->{default_tb} eq '1') {
		$default_tb = '<option value="1" selected="selected">受け付ける</option><option value="0">受け付けない</option>';
	} else {
		$default_tb = '<option value="1">受け付ける</option><option value="0" selected="selected">受け付けない</option>';
	}

	my $comt_stat;
	if ($self->{config}->{comt_stat} eq '1') {
		$comt_stat = '<option value="1" selected="selected">承認済み</option><option value="0">未承認</option>';
	} else {
		$comt_stat = '<option value="1">承認済み</option><option value="0" selected="selected">未承認</option>';
	}

	my $tb_stat;
	if ($self->{config}->{tb_stat} eq '1') {
		$tb_stat = '<option value="1" selected="selected">承認済み</option><option value="0">未承認</option>';
	} else {
		$tb_stat = '<option value="1">承認済み</option><option value="0" selected="selected">未承認</option>';
	}

	my $title_mode;
	if ($self->{config}->{title_mode} eq '3') {
		$title_mode = '<option value="0">ブログタイトルのみ表示</option><option value="1">ブログタイトル＋記事の題名</option><option value="2">記事の題名＋ブログタイトル</option><option value="3" selected="selected">記事の題名のみ表示</option>';
	} elsif ($self->{config}->{title_mode} eq '2') {
		$title_mode = '<option value="0">ブログタイトルのみ表示</option><option value="1">ブログタイトル＋記事の題名</option><option value="2" selected="selected">記事の題名＋ブログタイトル</option><option value="3">記事の題名のみ表示</option>';
	} elsif ($self->{config}->{title_mode} eq '1') {
		$title_mode = '<option value="0">ブログタイトルのみ表示</option><option value="1" selected="selected">ブログタイトル＋記事の題名</option><option value="2">記事の題名＋ブログタイトル</option><option value="3">記事の題名のみ表示</option>';
	} else {
		$title_mode = '<option value="0" selected="selected">ブログタイトルのみ表示</option><option value="1">ブログタイトル＋記事の題名</option><option value="2">記事の題名＋ブログタイトル</option><option value="3">記事の題名のみ表示</option>';
	}

	my $paragraph_mode;
	if ($self->{config}->{paragraph_mode} eq '1') {
		$paragraph_mode = '<option value="1" selected="selected">変換する</option><option value="0">変換しない</option>';
	} else {
		$paragraph_mode = '<option value="1">変換する</option><option value="0" selected="selected">変換しない</option>';
	}

	my $autolink_mode;
	if ($self->{config}->{autolink_mode} eq '1') {
		$autolink_mode = '<option value="1" selected="selected">リンクする</option><option value="0">リンクしない</option>';
	} else {
		$autolink_mode = '<option value="1">リンクする</option><option value="0" selected="selected">リンクしない</option>';
	}

	my $decoration_mode;
	if ($self->{config}->{decoration_mode} eq '1') {
		$decoration_mode = '<option value="1" selected="selected">装飾する</option><option value="0">装飾しない</option>';
	} else {
		$decoration_mode = '<option value="1">装飾する</option><option value="0" selected="selected">装飾しない</option>';
	}

	my $thumbnail_mode;
	if ($self->{config}->{thumbnail_mode} eq '2') {
		$thumbnail_mode = '<option value="2" selected="selected">repng2jpegで作成</option><option value="1">ImageMagickで作成</option><option value="0">作成しない</option>';
	} elsif ($self->{config}->{thumbnail_mode} eq '1') {
		$thumbnail_mode = '<option value="2">repng2jpegで作成</option><option value="1" selected="selected">ImageMagickで作成</option><option value="0">作成しない</option>';
	} else {
		$thumbnail_mode = '<option value="2">repng2jpegで作成</option><option value="1">ImageMagickで作成</option><option value="0" selected="selected">作成しない</option>';
	}

	my $whisper_mode;
	if ($self->{config}->{whisper_mode} eq '1') {
		$whisper_mode = '<option value="1" selected="selected">許可する</option><option value="0">許可しない</option>';
	} else {
		$whisper_mode = '<option value="1">許可する</option><option value="0" selected="selected">許可しない</option>';
	}

	my $album_target;
	if ($self->{config}->{album_target} eq 'music') {
		$album_target = '<option value="">すべて表示</option><option value="image">画像ファイルのみ表示</option><option value="mini">ミニ画像のみ表示</option><option value="music" selected="selected">音楽ファイルのみ表示</option>';
	} elsif ($self->{config}->{album_target} eq 'mini') {
		$album_target = '<option value="">すべて表示</option><option value="image">画像ファイルのみ表示</option><option value="mini" selected="selected">ミニ画像のみ表示</option><option value="music">音楽ファイルのみ表示</option>';
	} elsif ($self->{config}->{album_target} eq 'image') {
		$album_target = '<option value="">すべて表示</option><option value="image" selected="selected">画像ファイルのみ表示</option><option value="mini">ミニ画像のみ表示</option><option value="music">音楽ファイルのみ表示</option>';
	} else {
		$album_target = '<option value="" selected="selected">すべて表示</option><option value="image">画像ファイルのみ表示</option><option value="mini">ミニ画像のみ表示</option><option value="music">音楽ファイルのみ表示</option>';
	}

	my $top_mode;
	if ($self->{config}->{top_mode} eq '1') {
		$top_mode = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$top_mode = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $top_field;
	if ($self->{config}->{top_field} eq '1') {
		$top_field = '<option value="1" selected="selected">分類別表示</option><option value="0">通常表示</option>';
	} else {
		$top_field = '<option value="1">分類別表示</option><option value="0" selected="selected">通常表示</option>';
	}

	my $top_break;
	if ($self->{config}->{top_break} eq '1') {
		$top_break = '<option value="1" selected="selected">変換する</option><option value="0">変換しない</option>';
	} else {
		$top_break = '<option value="1">変換する</option><option value="0" selected="selected">変換しない</option>';
	}

	my $show_calendar;
	if ($self->{config}->{show_calendar} eq '1') {
		$show_calendar = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$show_calendar = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $show_field;
	if ($self->{config}->{show_field} eq '1') {
		$show_field = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$show_field = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $show_search;
	if ($self->{config}->{show_search} eq '1') {
		$show_search = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$show_search = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $show_past;
	if ($self->{config}->{show_past} eq '1') {
		$show_past = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$show_past = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $show_menu;
	if ($self->{config}->{show_menu} eq '1') {
		$show_menu = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$show_menu = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $show_link;
	if ($self->{config}->{show_link} eq '1') {
		$show_link = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$show_link = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $date_navigation;
	if ($self->{config}->{date_navigation} eq '1') {
		$date_navigation = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$date_navigation = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $field_navigation;
	if ($self->{config}->{field_navigation} eq '1') {
		$field_navigation = '<option value="1" selected="selected">表示する</option><option value="0">表示しない</option>';
	} else {
		$field_navigation = '<option value="1">表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $show_navigation;
	if ($self->{config}->{show_navigation} eq '2') {
		$show_navigation = '<option value="2" selected="selected">常に表示する</option><option value="1">固定URLページで表示する</option><option value="0">表示しない</option>';
	} elsif ($self->{config}->{show_navigation} eq '1') {
		$show_navigation = '<option value="2">常に表示する</option><option value="1" selected="selected">固定URLページで表示する</option><option value="0">表示しない</option>';
	} else {
		$show_navigation = '<option value="2">常に表示する</option><option value="1">固定URLページで表示する</option><option value="0" selected="selected">表示しない</option>';
	}

	my $pos_navigation;
	if ($self->{config}->{pos_navigation} eq '1') {
		$pos_navigation = '<option value="1" selected="selected">記事の前に出力</option><option value="0">記事の後に出力</option>';
	} else {
		$pos_navigation = '<option value="1">記事の前に出力</option><option value="0" selected="selected">記事の後に出力</option>';
	}

	my $profile_mode;
	if ($self->{config}->{profile_mode} eq '1') {
		$profile_mode = '<option value="1" selected="selected">ON</option><option value="0">OFF</option>';
	} else {
		$profile_mode = '<option value="1">ON</option><option value="0" selected="selected">OFF</option>';
	}

	my $profile_break;
	if ($self->{config}->{profile_break} eq '1') {
		$profile_break = '<option value="1" selected="selected">変換する</option><option value="0">変換しない</option>';
	} else {
		$profile_break = '<option value="1">変換する</option><option value="0" selected="selected">変換しない</option>';
	}

	my $user_mode;
	if ($self->{config}->{user_mode} eq '1') {
		$user_mode = '<option value="1" selected="selected">ON</option><option value="0">OFF</option>';
	} else {
		$user_mode = '<option value="1">ON</option><option value="0" selected="selected">OFF</option>';
	}

	my $auth_comment;
	if ($self->{config}->{auth_comment} eq '1') {
		$auth_comment = '<input type="checkbox" name="env_auth_comment" id="auth_comment_checkbox" value="1" checked="checked" /> <label for="auth_comment_checkbox">コメントの管理</label>';
	} else {
		$auth_comment = '<input type="checkbox" name="env_auth_comment" id="auth_comment_checkbox" value="1" /> <label for="auth_comment_checkbox">コメントの管理</label>';
	}

	my $auth_trackback;
	if ($self->{config}->{auth_trackback} eq '1') {
		$auth_trackback = '<input type="checkbox" name="env_auth_trackback" id="auth_trackback_checkbox" value="1" checked="checked" /> <label for="auth_trackback_checkbox">トラックバックの管理</label>';
	} else {
		$auth_trackback = '<input type="checkbox" name="env_auth_trackback" id="auth_trackback_checkbox" value="1" /> <label for="auth_trackback_checkbox">トラックバックの管理</label>';
	}

	my $auth_field;
	if ($self->{config}->{auth_field} eq '1') {
		$auth_field = '<input type="checkbox" name="env_auth_field" id="auth_field_checkbox" value="1" checked="checked" /> <label for="auth_field_checkbox">分類の管理</label>';
	} else {
		$auth_field = '<input type="checkbox" name="env_auth_field" id="auth_field_checkbox" value="1" /> <label for="auth_field_checkbox">分類の管理</label>';
	}

	my $auth_icon;
	if ($self->{config}->{auth_icon} eq '1') {
		$auth_icon = '<input type="checkbox" name="env_auth_icon" id="auth_icon_checkbox" value="1" checked="checked" /> <label for="auth_icon_checkbox">アイコンの管理</label>';
	} else {
		$auth_icon = '<input type="checkbox" name="env_auth_icon" id="auth_icon_checkbox" value="1" /> <label for="auth_icon_checkbox">アイコンの管理</label>';
	}

	my $auth_top;
	if ($self->{config}->{auth_top} eq '1') {
		$auth_top = '<input type="checkbox" name="env_auth_top" id="auth_top_checkbox" value="1" checked="checked" /> <label for="auth_top_checkbox">インデックスページの管理</label>';
	} else {
		$auth_top = '<input type="checkbox" name="env_auth_top" id="auth_top_checkbox" value="1" /> <label for="auth_top_checkbox">インデックスページの管理</label>';
	}

	my $auth_menu;
	if ($self->{config}->{auth_menu} eq '1') {
		$auth_menu = '<input type="checkbox" name="env_auth_menu" id="auth_menu_checkbox" value="1" checked="checked" /> <label for="auth_menu_checkbox">コンテンツの管理</label>';
	} else {
		$auth_menu = '<input type="checkbox" name="env_auth_menu" id="auth_menu_checkbox" value="1" /> <label for="auth_menu_checkbox">コンテンツの管理</label>';
	}

	my $auth_link;
	if ($self->{config}->{auth_link} eq '1') {
		$auth_link = '<input type="checkbox" name="env_auth_link" id="auth_link_checkbox" value="1" checked="checked" /> <label for="auth_link_checkbox">リンク集の管理</label>';
	} else {
		$auth_link = '<input type="checkbox" name="env_auth_link" id="auth_link_checkbox" value="1" /> <label for="auth_link_checkbox">リンク集の管理</label>';
	}

	my $auth_paint;
	if (-e $self->{init}->{spainter_jar} or -e $self->{init}->{paintbbs_jar}) {
		if ($self->{config}->{auth_paint} eq '1') {
			$auth_paint = '<br /><input type="checkbox" name="env_auth_paint" id="auth_paint_checkbox" value="1" checked="checked" /> <label for="auth_paint_checkbox">イラストの管理</label>';
		} else {
			$auth_paint = '<br /><input type="checkbox" name="env_auth_paint" id="auth_paint_checkbox" value="1" /> <label for="auth_paint_checkbox">イラストの管理</label>';
		}
	} else {
		if ($self->{config}->{auth_paint} eq '1') {
			$auth_paint = '<input type="hidden" name="env_auth_paint" value="1" />';
		} else {
			$auth_paint = '<input type="hidden" name="env_auth_paint" value="0" />';
		}
	}

	my $sendmail_cmt_mode;
	if ($self->{config}->{sendmail_cmt_mode} eq '1') {
		$sendmail_cmt_mode = '<option value="1" selected="selected">通知する</option><option value="0">通知しない</option>';
	} else {
		$sendmail_cmt_mode = '<option value="1">通知する</option><option value="0" selected="selected">通知しない</option>';
	}

	my $sendmail_tb_mode;
	if ($self->{config}->{sendmail_tb_mode} eq '1') {
		$sendmail_tb_mode = '<option value="1" selected="selected">通知する</option><option value="0">通知しない</option>';
	} else {
		$sendmail_tb_mode = '<option value="1">通知する</option><option value="0" selected="selected">通知しない</option>';
	}

	my $sendmail_detail;
	if ($self->{config}->{sendmail_detail} eq '1') {
		$sendmail_detail = '<option value="1" selected="selected">通知する</option><option value="0">通知しない</option>';
	} else {
		$sendmail_detail = '<option value="1">通知する</option><option value="0" selected="selected">通知しない</option>';
	}

	my $receive_mode;
	if ($self->{config}->{receive_mode} eq '1') {
		$receive_mode = '<option value="1" selected="selected">ON</option><option value="0">OFF</option>';
	} else {
		$receive_mode = '<option value="1">ON</option><option value="0" selected="selected">OFF</option>';
	}

	my $receive_field;
	open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
	while (<FH>) {
		chomp;
		my($field, $child) = split(/<>/);

		if ($self->{config}->{receive_field} eq $_) {
			if ($child) {
				$receive_field .= "<option value=\"$field\n$child\" selected=\"selected\">└ $child</option>";
			} else {
				$receive_field .= "<option value=\"$field\" selected=\"selected\">$field</option>";
			}
		} else {
			if ($child) {
				$receive_field .= "<option value=\"$field\n$child\">└ $child</option>";
			} else {
				$receive_field .= "<option value=\"$field\">$field</option>";
			}
		}
	}
	close(FH);
	if (!$receive_field) {
		$receive_field = '<option value="">分類が登録されていません</option>';
	} else {
		$receive_field = "<option value=\"\">選択してください</option>$receive_field";
	}

	my $ping_mode;
	if ($self->{config}->{ping_mode} eq '1') {
		$ping_mode = '<option value="1" selected="selected">通知する</option><option value="0">通知しない</option>';
	} else {
		$ping_mode = '<option value="1">通知する</option><option value="0" selected="selected">通知しない</option>';
	}

	my $paint_tool;
	if (-e $self->{init}->{spainter_jar} or -e $self->{init}->{paintbbs_jar}) {
		if ($self->{config}->{paint_tool} eq 'paintbbs') {
			$paint_tool = '<option value="paintbbs" selected="selected">PaintBBS</option><option value="shipainter">しぃペインター</option><option value="shipainterpro">しぃペインタープロ</option>';
		} elsif ($self->{config}->{paint_tool} eq 'shipainterpro') {
			$paint_tool = '<option value="paintbbs">PaintBBS</option><option value="shipainter">しぃペインター</option><option value="shipainterpro" selected="selected">しぃペインタープロ</option>';
		} else {
			$paint_tool = '<option value="paintbbs">PaintBBS</option><option value="shipainter" selected="selected">しぃペインター</option><option value="shipainterpro">しぃペインタープロ</option>';
		}
	}

	my $paint_link;
	if (-e $self->{init}->{spainter_jar} or -e $self->{init}->{paintbbs_jar}) {
		if ($self->{config}->{paint_link} eq '1') {
			$paint_link = '<option value="1" selected="selected">描画画面へリンク</option><option value="0">イラスト管理画面へリンク</option>';
		} else {
			$paint_link = '<option value="1">描画画面へリンク</option><option value="0" selected="selected">イラスト管理画面へリンク</option>';
		}
	}

	my $html_index_mode;
	if ($self->{config}->{html_index_mode} eq '1') {
		$html_index_mode = '<option value="1" selected="selected">ON</option><option value="0">OFF</option>';
	} else {
		$html_index_mode = '<option value="1">ON</option><option value="0" selected="selected">OFF</option>';
	}

	my $html_archive_mode;
	if ($self->{config}->{html_archive_mode} eq '1') {
		$html_archive_mode = '<option value="1" selected="selected">ON</option><option value="0">OFF</option>';
	} else {
		$html_archive_mode = '<option value="1">ON</option><option value="0" selected="selected">OFF</option>';
	}

	my $html_field_mode;
	if ($self->{config}->{html_field_mode} eq '1') {
		$html_field_mode = '<option value="1" selected="selected">ON</option><option value="0">OFF</option>';
	} else {
		$html_field_mode = '<option value="1">ON</option><option value="0" selected="selected">OFF</option>';
	}

	my $js_title_mode;
	if ($self->{config}->{js_title_mode} eq '1') {
		$js_title_mode = '<option value="1" selected="selected">ON</option><option value="0">OFF</option>';
	} else {
		$js_title_mode = '<option value="1">ON</option><option value="0" selected="selected">OFF</option>';
	}

	my $js_title_field_mode;
	if ($self->{config}->{js_title_field_mode} eq '1') {
		$js_title_field_mode = '<option value="1" selected="selected">ON</option><option value="0">OFF</option>';
	} else {
		$js_title_field_mode = '<option value="1">ON</option><option value="0" selected="selected">OFF</option>';
	}

	my $js_text_mode;
	if ($self->{config}->{js_text_mode} eq '1') {
		$js_text_mode = '<option value="1" selected="selected">ON</option><option value="0">OFF</option>';
	} else {
		$js_text_mode = '<option value="1">ON</option><option value="0" selected="selected">OFF</option>';
	}

	my $js_text_field_mode;
	if ($self->{config}->{js_text_field_mode} eq '1') {
		$js_text_field_mode = '<option value="1" selected="selected">ON</option><option value="0">OFF</option>';
	} else {
		$js_text_field_mode = '<option value="1">ON</option><option value="0" selected="selected">OFF</option>';
	}

	my $proxy_mode;
	if ($self->{config}->{proxy_mode} eq '1') {
		$proxy_mode = '<option value="1" selected="selected">許可</option><option value="0">禁止</option>';
	} else {
		$proxy_mode = '<option value="1">許可</option><option value="0" selected="selected">禁止</option>';
	}

	my $need_japanese;
	if ($self->{config}->{need_japanese} eq '1') {
		$need_japanese = '<option value="1" selected="selected">必須</option><option value="0">任意</option>';
	} else {
		$need_japanese = '<option value="1">必須</option><option value="0" selected="selected">任意</option>';
	}

	my $need_japanese_tb;
	if ($self->{config}->{need_japanese_tb} eq '1') {
		$need_japanese_tb = '<option value="1" selected="selected">必須</option><option value="0">任意</option>';
	} else {
		$need_japanese_tb = '<option value="1">必須</option><option value="0" selected="selected">任意</option>';
	}

	my $need_link_tb;
	if ($self->{config}->{need_link_tb} eq '1') {
		$need_link_tb = '<option value="1" selected="selected">必須</option><option value="0">任意</option>';
	} else {
		$need_link_tb = '<option value="1">必須</option><option value="0" selected="selected">任意</option>';
	}

	my $rss_field_list = $self->{config}->{rss_field_list};
	$rss_field_list =~ s/<>/\n/g;

	my $text_color = $self->{config}->{text_color};
	$text_color =~ s/<>/\n/g;

	my $top_field_list = $self->{config}->{top_field_list};
	$top_field_list =~ s/<>/\n/g;

	my $menu_list = $self->{config}->{menu_list};
	$menu_list =~ s/<>/\n/g;

	my $link_list = $self->{config}->{link_list};
	$link_list =~ s/<>/\n/g;

	my $sendmail_list = $self->{config}->{sendmail_list};
	$sendmail_list =~ s/<>/\n/g;

	my $sendmail_admin = $self->{config}->{sendmail_admin};
	$sendmail_admin =~ s/<>/\n/g;

	my $receive_list = $self->{config}->{receive_list};
	$receive_list =~ s/<>/\n/g;

	my $ping_list = $self->{config}->{ping_list};
	$ping_list =~ s/<>/\n/g;

	my $html_field_list = $self->{config}->{html_field_list};
	$html_field_list =~ s/<>/\n/g;

	my $js_title_field_list = $self->{config}->{js_title_field_list};
	$js_title_field_list =~ s/<>/\n/g;

	my $js_text_field_list = $self->{config}->{js_text_field_list};
	$js_text_field_list =~ s/<>/\n/g;

	my $black_list = $self->{config}->{black_list};
	$black_list =~ s/<>/\n/g;

	my $ng_word = $self->{config}->{ng_word};
	$ng_word =~ s/<>/\n/g;

	my $need_word = $self->{config}->{need_word};
	$need_word =~ s/<>/\n/g;

	my $black_list_tb = $self->{config}->{black_list_tb};
	$black_list_tb =~ s/<>/\n/g;

	my $ng_word_tb = $self->{config}->{ng_word_tb};
	$ng_word_tb =~ s/<>/\n/g;

	my $need_word_tb = $self->{config}->{need_word_tb};
	$need_word_tb =~ s/<>/\n/g;

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');
	print $skin_ins->get_data('contents_head');

	my @envlist;

	push(@envlist, 'env_basic',      '基本設定');
	push(@envlist, 'env_log',        'ログの表示設定');
	push(@envlist, 'env_rss',        'RSSの設定');
	push(@envlist, 'env_form',       '投稿画面の表示設定');
	push(@envlist, 'env_default',    '投稿記事の初期設定');
	push(@envlist, 'env_show',       '投稿記事の表示設定');
	push(@envlist, 'env_album',      'アルバムページの設定');
	push(@envlist, 'env_top',        'インデックスページの設定');
	push(@envlist, 'env_navigation', 'ナビゲーションの表示設定');
	push(@envlist, 'env_profile',    'プロフィールの設定');
	push(@envlist, 'env_user',       'ユーザー管理の設定');
	push(@envlist, 'env_sendmail',   'メール通知の設定');
	push(@envlist, 'env_receive',    'メール更新の設定');
	push(@envlist, 'env_ping',       '更新PINGの設定');
	push(@envlist, 'env_paint',      'イラスト投稿の設定') if (-e $self->{init}->{spainter_jar} or -e $self->{init}->{paintbbs_jar});
	push(@envlist, 'env_cookie',     'Cookieの設定');
	push(@envlist, 'env_html',       'HTMLファイル書き出しの設定');
	push(@envlist, 'env_js',         'JSファイル書き出しの設定');
	push(@envlist, 'env_access',     '投稿制限の設定');

	print $skin_ins->get_data('envlist_head');
	my $i = 0;
	foreach (0 .. ($#envlist / 2)) {
		print $skin_ins->get_replace_data(
			'envlist',
			ENV_ID    => $envlist[$i++],
			ENV_TITLE => $envlist[$i++]
		);
	}
	print $skin_ins->get_data('envlist_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => '基本設定',
		ENV_ID    => 'env_basic'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'ブログタイトル（<em>必ず設定してください</em>）',
		ENV_VALUE => "<input type=\"text\" name=\"env_site_title\" size=\"30\" value=\"$self->{config}->{site_title}\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'ブログからの戻り先',
		ENV_VALUE => "<input type=\"text\" name=\"env_back_url\" size=\"50\" value=\"$self->{config}->{back_url}\" style=\"ime-mode:disabled;\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'ブログタイトル（携帯モード時）',
		ENV_VALUE => "<input type=\"text\" name=\"env_mobile_site_title\" size=\"30\" value=\"$self->{config}->{mobile_site_title}\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'ブログからの戻り先（携帯モード時）',
		ENV_VALUE => "<input type=\"text\" name=\"env_mobile_back_url\" size=\"50\" value=\"$self->{config}->{mobile_back_url}\" style=\"ime-mode:disabled;\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'サイトの概要',
		ENV_VALUE => "<input type=\"text\" name=\"env_site_description\" size=\"50\" value=\"$self->{config}->{site_description}\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => "サイトのURL（<code>$info_path</code> を格納しているディレクトリのURL / <em>必ず設定してください</em>）",
		ENV_VALUE => "<input type=\"text\" name=\"env_site_url\" size=\"50\" value=\"$self->{config}->{site_url}\" />"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'ログの表示設定',
		ENV_ID    => 'env_log'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '1ページの記事表示件数',
		ENV_VALUE => "<input type=\"text\" name=\"env_page_size\" size=\"5\" value=\"$self->{config}->{page_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'ページナビゲーションの表示ページ数（0にすると非表示）',
		ENV_VALUE => "<input type=\"text\" name=\"env_navi_size\" size=\"5\" value=\"$self->{config}->{navi_size}\" style=\"ime-mode:disabled;\" />ページまで"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '記事一覧にコメントの内容を表示',
		ENV_VALUE => "<select name=\"env_show_comt\" xml:lang=\"ja\" lang=\"ja\">$show_comt</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '記事一覧にトラックバックの内容を表示',
		ENV_VALUE => "<select name=\"env_show_tb\" xml:lang=\"ja\" lang=\"ja\">$show_tb</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '新着記事の表示件数（0にすると非表示）',
		ENV_VALUE => "<input type=\"text\" name=\"env_list_size\" size=\"5\" value=\"$self->{config}->{list_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '新着ミニ画像の表示件数（0にすると非表示）',
		ENV_VALUE => "<input type=\"text\" name=\"env_image_size\" size=\"5\" value=\"$self->{config}->{image_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '新着コメントの表示件数（0にすると非表示）',
		ENV_VALUE => "<input type=\"text\" name=\"env_cmtlist_size\" size=\"5\" value=\"$self->{config}->{cmtlist_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '新着トラックバックの表示件数（0にすると非表示）',
		ENV_VALUE => "<input type=\"text\" name=\"env_tblist_size\" size=\"5\" value=\"$self->{config}->{tblist_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '1ページの記事表示件数（管理モード時）',
		ENV_VALUE => "<input type=\"text\" name=\"env_admin_size\" size=\"5\" value=\"$self->{config}->{admin_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '1ページの記事表示件数（携帯モード時）',
		ENV_VALUE => "<input type=\"text\" name=\"env_mobile_page_size\" size=\"5\" value=\"$self->{config}->{mobile_page_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '新着コメントの表示件数（携帯モード時 / 0にすると非表示）',
		ENV_VALUE => "<input type=\"text\" name=\"env_mobile_cmtlist_size\" size=\"5\" value=\"$self->{config}->{mobile_cmtlist_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '新着トラックバックの表示件数（携帯モード時 / 0にすると非表示）',
		ENV_VALUE => "<input type=\"text\" name=\"env_mobile_tblist_size\" size=\"5\" value=\"$self->{config}->{mobile_tblist_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'RSSの設定',
		ENV_ID    => 'env_rss'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'RSSの配信内容',
		ENV_VALUE => "<select name=\"env_rss_mode\" xml:lang=\"ja\" lang=\"ja\">$rss_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '概要の配信サイズ',
		ENV_VALUE => "<input type=\"text\" name=\"env_rss_length\" size=\"5\" value=\"$self->{config}->{rss_length}\" style=\"ime-mode:disabled;\" />byte"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'RSSの配信件数',
		ENV_VALUE => "<input type=\"text\" name=\"env_rss_size\" size=\"5\" value=\"$self->{config}->{rss_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '配信する記事の分類（改行区切りで複数指定可能 / 空欄にするとすべて配信）',
		ENV_VALUE => "<textarea name=\"env_rss_field_list\" cols=\"30\" rows=\"5\">$rss_field_list</textarea>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => '投稿画面の表示設定',
		ENV_ID    => 'env_form'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '分類選択項目の表示',
		ENV_VALUE => "<select name=\"env_use_field\" xml:lang=\"ja\" lang=\"ja\">$use_field</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'アイコン選択項目の表示',
		ENV_VALUE => "<select name=\"env_use_icon\" xml:lang=\"ja\" lang=\"ja\">$use_icon</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '文字色選択項目の表示',
		ENV_VALUE => "<select name=\"env_use_color\" xml:lang=\"ja\" lang=\"ja\">$use_color</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'ファイル選択項目の表示',
		ENV_VALUE => "<select name=\"env_use_file\" xml:lang=\"ja\" lang=\"ja\">$use_file</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'ミニ画像選択項目の表示',
		ENV_VALUE => "<select name=\"env_use_image\" xml:lang=\"ja\" lang=\"ja\">$use_image</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '記事ID入力項目の表示',
		ENV_VALUE => "<select name=\"env_use_id\" xml:lang=\"ja\" lang=\"ja\">$use_id</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'トラックバックURL入力項目の表示',
		ENV_VALUE => "<select name=\"env_use_tburl\" xml:lang=\"ja\" lang=\"ja\">$use_tburl</select>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => '投稿記事の初期設定',
		ENV_ID    => 'env_default'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '記事の公開',
		ENV_VALUE => "<select name=\"env_default_stat\" xml:lang=\"ja\" lang=\"ja\">$default_stat</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '改行の変換',
		ENV_VALUE => "<select name=\"env_default_break\" xml:lang=\"ja\" lang=\"ja\">$default_break</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'コメントの受付',
		ENV_VALUE => "<select name=\"env_default_comt\" xml:lang=\"ja\" lang=\"ja\">$default_comt</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'トラックバックの受付',
		ENV_VALUE => "<select name=\"env_default_tb\" xml:lang=\"ja\" lang=\"ja\">$default_tb</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '受信コメントの初期状態',
		ENV_VALUE => "<select name=\"env_comt_stat\" xml:lang=\"ja\" lang=\"ja\">$comt_stat</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '受信トラックバックの初期状態',
		ENV_VALUE => "<select name=\"env_tb_stat\" xml:lang=\"ja\" lang=\"ja\">$tb_stat</select>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => '投稿記事の表示設定',
		ENV_ID    => 'env_show'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '各記事ページでのタイトル表記',
		ENV_VALUE => "<select name=\"env_title_mode\" xml:lang=\"ja\" lang=\"ja\">$title_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '空行を段落に変換',
		ENV_VALUE => "<select name=\"env_paragraph_mode\" xml:lang=\"ja\" lang=\"ja\">$paragraph_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '本文のURLとメールアドレスに自動的にリンク',
		ENV_VALUE => "<select name=\"env_autolink_mode\" xml:lang=\"ja\" lang=\"ja\">$autolink_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '自動リンク時に付加する属性',
		ENV_VALUE => "<input type=\"text\" name=\"env_autolink_attribute\" size=\"30\" value=\"$self->{config}->{autolink_attribute}\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '省略本文表示用リンクのテキスト',
		ENV_VALUE => "<input type=\"text\" name=\"env_continue_text\" size=\"30\" value=\"$self->{config}->{continue_text}\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '新着マーク表示日数',
		ENV_VALUE => "<input type=\"text\" name=\"env_new_days\" size=\"5\" value=\"$self->{config}->{new_days}\" style=\"ime-mode:disabled;\" />日間"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '本文の文字色（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_text_color\" cols=\"30\" rows=\"10\">$text_color</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '本文の装飾',
		ENV_VALUE => "<select name=\"env_decoration_mode\" xml:lang=\"ja\" lang=\"ja\">$decoration_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'アップロードファイルの最大表示横幅',
		ENV_VALUE => "<input type=\"text\" name=\"env_img_maxwidth\" size=\"5\" value=\"$self->{config}->{img_maxwidth}\" style=\"ime-mode:disabled;\" />px"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'サムネイル専用画像を自動作成（<em>ImageMagickかrepng2jpegが必須</em>）',
		ENV_VALUE => "<select name=\"env_thumbnail_mode\" xml:lang=\"ja\" lang=\"ja\">$thumbnail_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'アップロードファイルへのリンク時に付加する属性',
		ENV_VALUE => "<input type=\"text\" name=\"env_file_attribute\" size=\"30\" value=\"$self->{config}->{file_attribute}\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '引用文字色',
		ENV_VALUE => "<input type=\"text\" name=\"env_quotation_color\" size=\"10\" value=\"$self->{config}->{quotation_color}\" style=\"ime-mode:disabled;\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '管理者宛コメントの投稿',
		ENV_VALUE => "<select name=\"env_whisper_mode\" xml:lang=\"ja\" lang=\"ja\">$whisper_mode</select>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'アルバムページの設定',
		ENV_ID    => 'env_album'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '1ページの表示件数',
		ENV_VALUE => "<input type=\"text\" name=\"env_album_size\" size=\"5\" value=\"$self->{config}->{album_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '表示分割件数',
		ENV_VALUE => "<input type=\"text\" name=\"env_album_delimiter_size\" size=\"5\" value=\"$self->{config}->{album_delimiter_size}\" style=\"ime-mode:disabled;\" />件ずつ"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '一覧に表示するファイルの種類',
		ENV_VALUE => "<select name=\"env_album_target\" xml:lang=\"ja\" lang=\"ja\">$album_target</select>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'インデックスページの設定',
		ENV_ID    => 'env_top'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'インデックス専用ページの表示',
		ENV_VALUE => "<select name=\"env_top_mode\" xml:lang=\"ja\" lang=\"ja\">$top_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'インデックスページに表示する記事の件数',
		ENV_VALUE => "<input type=\"text\" name=\"env_top_size\" size=\"5\" value=\"$self->{config}->{top_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '表示分割件数',
		ENV_VALUE => "<input type=\"text\" name=\"env_top_delimiter_size\" size=\"5\" value=\"$self->{config}->{top_delimiter_size}\" style=\"ime-mode:disabled;\" />件ずつ"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'インデックスページの記事表示方法',
		ENV_VALUE => "<select name=\"env_top_field\" xml:lang=\"ja\" lang=\"ja\">$top_field</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'インデックスページに表示する記事の分類（改行区切りで複数指定可能 / 空欄にするとすべて表示）',
		ENV_VALUE => "<textarea name=\"env_top_field_list\" cols=\"30\" rows=\"5\">$top_field_list</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'インデックスページテキストの改行の変換',
		ENV_VALUE => "<select name=\"env_top_break\" xml:lang=\"ja\" lang=\"ja\">$top_break</select>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'ナビゲーションの表示設定',
		ENV_ID    => 'env_navigation'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'カレンダーの表示',
		ENV_VALUE => "<select name=\"env_show_calendar\" xml:lang=\"ja\" lang=\"ja\">$show_calendar</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '分類一覧の表示',
		ENV_VALUE => "<select name=\"env_show_field\" xml:lang=\"ja\" lang=\"ja\">$show_field</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '検索フォームの表示',
		ENV_VALUE => "<select name=\"env_show_search\" xml:lang=\"ja\" lang=\"ja\">$show_search</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '過去ログ一覧の表示',
		ENV_VALUE => "<select name=\"env_show_past\" xml:lang=\"ja\" lang=\"ja\">$show_past</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'コンテンツの表示',
		ENV_VALUE => "<select name=\"env_show_menu\" xml:lang=\"ja\" lang=\"ja\">$show_menu</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'コンテンツの分類（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_menu_list\" cols=\"30\" rows=\"5\">$menu_list</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'リンク集の表示',
		ENV_VALUE => "<select name=\"env_show_link\" xml:lang=\"ja\" lang=\"ja\">$show_link</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'リンク集の分類（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_link_list\" cols=\"30\" rows=\"5\">$link_list</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '日時別ページでのナビゲーション表示',
		ENV_VALUE => "<select name=\"env_date_navigation\" xml:lang=\"ja\" lang=\"ja\">$date_navigation</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '分類別ページでのナビゲーション表示',
		ENV_VALUE => "<select name=\"env_field_navigation\" xml:lang=\"ja\" lang=\"ja\">$field_navigation</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '各記事ページでのナビゲーション表示（JavaScriptが必要）',
		ENV_VALUE => "<select name=\"env_show_navigation\" xml:lang=\"ja\" lang=\"ja\">$show_navigation</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'ナビゲーションの表示位置',
		ENV_VALUE => "<select name=\"env_pos_navigation\" xml:lang=\"ja\" lang=\"ja\">$pos_navigation</select>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'プロフィールの設定',
		ENV_ID    => 'env_profile'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'プロフィールの管理',
		ENV_VALUE => "<select name=\"env_profile_mode\" xml:lang=\"ja\" lang=\"ja\">$profile_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'プロフィールテキストの改行の変換',
		ENV_VALUE => "<select name=\"env_profile_break\" xml:lang=\"ja\" lang=\"ja\">$profile_break</select>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'ユーザー管理の設定',
		ENV_ID    => 'env_user'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '複数ユーザーの管理',
		ENV_VALUE => "<select name=\"env_user_mode\" xml:lang=\"ja\" lang=\"ja\">$user_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'ゲストユーザーに許可する操作',
		ENV_VALUE => "$auth_comment<br />$auth_trackback<br />$auth_field<br />$auth_icon<br />$auth_top<br />$auth_menu<br />$auth_link$auth_paint"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '管理機能操作履歴の保存件数',
		ENV_VALUE => "<input type=\"text\" name=\"env_record_size\" size=\"5\" value=\"$self->{config}->{record_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'メール通知の設定',
		ENV_ID    => 'env_sendmail'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'コメントを受信するとメールで通知',
		ENV_VALUE => "<select name=\"env_sendmail_cmt_mode\" xml:lang=\"ja\" lang=\"ja\">$sendmail_cmt_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'トラックバックを受信するとメールで通知',
		ENV_VALUE => "<select name=\"env_sendmail_tb_mode\" xml:lang=\"ja\" lang=\"ja\">$sendmail_tb_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'Sendmailのパス',
		ENV_VALUE => "<input type=\"text\" name=\"env_sendmail_path\" size=\"30\" value=\"$self->{config}->{sendmail_path}\" style=\"ime-mode:disabled;\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'メールの通知先（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_sendmail_list\" cols=\"50\" rows=\"5\" style=\"ime-mode:disabled;\">$sendmail_list</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '通知しないコメント投稿者名（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_sendmail_admin\" cols=\"30\" rows=\"5\">$sendmail_admin</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '通知する投稿内容の最大量（超えた分は省略表示）',
		ENV_VALUE => "<input type=\"text\" name=\"env_sendmail_length\" size=\"5\" value=\"$self->{config}->{sendmail_length}\" style=\"ime-mode:disabled;\" />byte"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '受信記事のURLと投稿者情報の通知',
		ENV_VALUE => "<select name=\"env_sendmail_detail\" xml:lang=\"ja\" lang=\"ja\">$sendmail_detail</select>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'メール更新の設定',
		ENV_ID    => 'env_receive'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'メールでの記事投稿',
		ENV_VALUE => "<select name=\"env_receive_mode\" xml:lang=\"ja\" lang=\"ja\">$receive_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'POPサーバーのアドレス',
		ENV_VALUE => "<input type=\"text\" name=\"env_pop_server\" size=\"30\" value=\"$self->{config}->{pop_server}\" style=\"ime-mode:disabled;\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'POPサーバーのユーザー名',
		ENV_VALUE => "<input type=\"text\" name=\"env_pop_user\" size=\"30\" value=\"$self->{config}->{pop_user}\" style=\"ime-mode:disabled;\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'POPサーバーのパスワード',
		ENV_VALUE => "<input type=\"text\" name=\"env_pop_pwd\" size=\"30\" value=\"$self->{config}->{pop_pwd}\" style=\"ime-mode:disabled;\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '投稿用メールアドレス（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_receive_list\" cols=\"50\" rows=\"5\">$receive_list</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '受信した記事の分類',
		ENV_VALUE => "<select name=\"env_receive_field\" xml:lang=\"ja\" lang=\"ja\">$receive_field</select> <a href=\"$self->{init}->{script_file}?mode=admin&amp;work=field\">分類設定</a>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => '更新PINGの設定',
		ENV_ID    => 'env_ping'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '更新PINGの送信',
		ENV_VALUE => "<select name=\"env_ping_mode\" xml:lang=\"ja\" lang=\"ja\">$ping_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '更新PINGの通知先（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_ping_list\" cols=\"50\" rows=\"5\" style=\"ime-mode:disabled;\">$ping_list</textarea>"
	);
	print $skin_ins->get_data('env_foot');

	if (-e $self->{init}->{spainter_jar} or -e $self->{init}->{paintbbs_jar}) {
		print $skin_ins->get_replace_data(
			'env_title',
			ENV_TITLE => 'イラスト投稿の設定',
			ENV_ID    => 'env_paint'
		);
		print $skin_ins->get_data('env_head');
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => '使用ツールの初期設定',
			ENV_VALUE => "<select name=\"env_paint_tool\" xml:lang=\"ja\" lang=\"ja\">$paint_tool</select>"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => 'キャンバスの初期サイズ',
			ENV_VALUE => "<input type=\"text\" name=\"env_paint_image_width\" size=\"5\" value=\"$self->{config}->{paint_image_width}\" style=\"ime-mode:disabled;\" />px × <input type=\"text\" name=\"env_paint_image_height\" size=\"5\" value=\"$self->{config}->{paint_image_height}\" style=\"ime-mode:disabled;\" />px"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => 'クオリティ値',
			ENV_VALUE => "<input type=\"text\" name=\"env_paint_quality\" size=\"5\" value=\"$self->{config}->{paint_quality}\" style=\"ime-mode:disabled;\" />"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => 'Jpegで保存するファイルの容量',
			ENV_VALUE => "<input type=\"text\" name=\"env_paint_image_size\" size=\"5\" value=\"$self->{config}->{paint_image_size}\" style=\"ime-mode:disabled;\" />KB以上"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => 'Jpegの圧縮率',
			ENV_VALUE => "<input type=\"text\" name=\"env_paint_compress_level\" size=\"5\" value=\"$self->{config}->{paint_compress_level}\" style=\"ime-mode:disabled;\" />"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => 'イラスト編集リンク',
			ENV_VALUE => "<select name=\"env_paint_link\" xml:lang=\"ja\" lang=\"ja\">$paint_link</select>"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => 'イラストの最大表示横幅',
			ENV_VALUE => "<input type=\"text\" name=\"env_paint_maxwidth\" size=\"5\" value=\"$self->{config}->{paint_maxwidth}\" style=\"ime-mode:disabled;\" />px"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => '描画アニメーション表示用リンクのテキスト',
			ENV_VALUE => "<input type=\"text\" name=\"env_animation_text\" size=\"30\" value=\"$self->{config}->{animation_text}\" />"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => '描画アニメーション表示用リンクに付加する属性',
			ENV_VALUE => "<input type=\"text\" name=\"env_animation_attribute\" size=\"30\" value=\"$self->{config}->{animation_attribute}\" />"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => 'イラスト一覧での1ページの表示件数',
			ENV_VALUE => "<input type=\"text\" name=\"env_gallery_size\" size=\"5\" value=\"$self->{config}->{gallery_size}\" style=\"ime-mode:disabled;\" />件"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => 'イラスト一覧での表示分割件数',
			ENV_VALUE => "<input type=\"text\" name=\"env_gallery_delimiter_size\" size=\"5\" value=\"$self->{config}->{gallery_delimiter_size}\" style=\"ime-mode:disabled;\" />件ずつ"
		);
		print $skin_ins->get_replace_data(
			'env',
			ENV_TITLE => 'イラスト一覧での最大画像横幅',
			ENV_VALUE => "<input type=\"text\" name=\"env_gallery_maxwidth\" size=\"5\" value=\"$self->{config}->{gallery_maxwidth}\" style=\"ime-mode:disabled;\" />px"
		);
		print $skin_ins->get_data('env_foot');
	} else {
		print "<input type=\"hidden\" name=\"env_paint_tool\" value=\"$self->{config}->{paint_tool}\" />";
		print "<input type=\"hidden\" name=\"env_paint_image_width\" value=\"$self->{config}->{paint_image_width}\" />";
		print "<input type=\"hidden\" name=\"env_paint_image_height\" value=\"$self->{config}->{paint_image_height}\" />";
		print "<input type=\"hidden\" name=\"env_paint_quality\" value=\"$self->{config}->{paint_quality}\" />";
		print "<input type=\"hidden\" name=\"env_paint_image_size\" value=\"$self->{config}->{paint_image_size}\" />";
		print "<input type=\"hidden\" name=\"env_paint_compress_level\" value=\"$self->{config}->{paint_compress_level}\" />";
		print "<input type=\"hidden\" name=\"env_paint_link\" value=\"$self->{config}->{paint_link}\" />";
		print "<input type=\"hidden\" name=\"env_paint_maxwidth\" value=\"$self->{config}->{paint_maxwidth}\" />";
		print "<input type=\"hidden\" name=\"env_animation_text\" value=\"$self->{config}->{animation_text}\" />";
		print "<input type=\"hidden\" name=\"env_animation_attribute\" value=\"$self->{config}->{animation_attribute}\" />";
		print "<input type=\"hidden\" name=\"env_gallery_size\" value=\"$self->{config}->{gallery_size}\" />";
		print "<input type=\"hidden\" name=\"env_gallery_delimiter_size\" value=\"$self->{config}->{gallery_delimiter_size}\" />";
		print "<input type=\"hidden\" name=\"env_gallery_maxwidth\" value=\"$self->{config}->{gallery_maxwidth}\" />";
	}

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'Cookieの設定',
		ENV_ID    => 'env_cookie'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '投稿者情報Cookieの識別名',
		ENV_VALUE => "<input type=\"text\" name=\"env_cookie_id\" size=\"30\" value=\"$self->{config}->{cookie_id}\" style=\"ime-mode:disabled;\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '投稿者情報の保存日数',
		ENV_VALUE => "<input type=\"text\" name=\"env_cookie_holddays\" size=\"5\" value=\"$self->{config}->{cookie_holddays}\" style=\"ime-mode:disabled;\" />日間"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'ログイン情報Cookieの識別名',
		ENV_VALUE => "<input type=\"text\" name=\"env_cookie_admin\" size=\"30\" value=\"$self->{config}->{cookie_admin}\" style=\"ime-mode:disabled;\" />"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'HTMLファイル書き出しの設定',
		ENV_ID    => 'env_html'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'トップページをHTMLファイルに書き出し',
		ENV_VALUE => "<select name=\"env_html_index_mode\" xml:lang=\"ja\" lang=\"ja\">$html_index_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '各記事をHTMLファイルに書き出し',
		ENV_VALUE => "<select name=\"env_html_archive_mode\" xml:lang=\"ja\" lang=\"ja\">$html_archive_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '各分類をHTMLファイルに書き出し',
		ENV_VALUE => "<select name=\"env_html_field_mode\" xml:lang=\"ja\" lang=\"ja\">$html_field_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '書き出す分類の設定（書き出し先と分類をコンマ区切りで指定）',
		ENV_VALUE => "<textarea name=\"env_html_field_list\" cols=\"50\" rows=\"5\">$html_field_list</textarea>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => 'JSファイル書き出しの設定',
		ENV_ID    => 'env_js'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '題名一覧をJSファイルに書き出し',
		ENV_VALUE => "<select name=\"env_js_title_mode\" xml:lang=\"ja\" lang=\"ja\">$js_title_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '題名一覧を分類別に書き出し',
		ENV_VALUE => "<select name=\"env_js_title_field_mode\" xml:lang=\"ja\" lang=\"ja\">$js_title_field_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '書き出す分類の設定（書き出し先と分類をコンマ区切りで指定）',
		ENV_VALUE => "<textarea name=\"env_js_title_field_list\" cols=\"50\" rows=\"5\">$js_title_field_list</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '題名一覧をJSファイルに書き出す件数',
		ENV_VALUE => "<input type=\"text\" name=\"env_js_title_size\" size=\"5\" value=\"$self->{config}->{js_title_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '最近の記事をJSファイルに書き出し',
		ENV_VALUE => "<select name=\"env_js_text_mode\" xml:lang=\"ja\" lang=\"ja\">$js_text_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '最近の記事を分類別に書き出し',
		ENV_VALUE => "<select name=\"env_js_text_field_mode\" xml:lang=\"ja\" lang=\"ja\">$js_text_field_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '書き出す分類の設定（書き出し先と分類をコンマ区切りで指定）',
		ENV_VALUE => "<textarea name=\"env_js_text_field_list\" cols=\"50\" rows=\"5\">$js_text_field_list</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '最近の記事をJSファイルに書き出す件数',
		ENV_VALUE => "<input type=\"text\" name=\"env_js_text_size\" size=\"5\" value=\"$self->{config}->{js_text_size}\" style=\"ime-mode:disabled;\" />件"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_replace_data(
		'env_title',
		ENV_TITLE => '投稿制限の設定',
		ENV_ID    => 'env_access'
	);
	print $skin_ins->get_data('env_head');
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '自サイトのURL(このURLを含まないサイトからの投稿を拒否)',
		ENV_VALUE => "<input type=\"text\" name=\"env_base_url\" size=\"50\" value=\"$self->{config}->{base_url}\" style=\"ime-mode:disabled;\" />"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => '投稿制限対象ホスト（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_black_list\" cols=\"30\" rows=\"5\" style=\"ime-mode:disabled;\">$black_list</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'Proxy経由の投稿',
		ENV_VALUE => "<select name=\"env_proxy_mode\" xml:lang=\"ja\" lang=\"ja\">$proxy_mode</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'コメントの投稿禁止ワード（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_ng_word\" cols=\"30\" rows=\"5\">$ng_word</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'コメントの投稿必須ワード（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_need_word\" cols=\"30\" rows=\"5\">$need_word</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'コメントでの日本語の利用',
		ENV_VALUE => "<select name=\"env_need_japanese\" xml:lang=\"ja\" lang=\"ja\">$need_japanese</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'コメントに記述できるURLの最大数（0にすると無制限）',
		ENV_VALUE => "<input type=\"text\" name=\"env_max_link\" size=\"5\" value=\"$self->{config}->{max_link}\" style=\"ime-mode:disabled;\" />個"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'コメントの連続投稿を拒否する時間',
		ENV_VALUE => "<input type=\"text\" name=\"env_wait_time\" size=\"5\" value=\"$self->{config}->{wait_time}\" style=\"ime-mode:disabled;\" />秒"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'トラックバックの投稿制限対象サイト（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_black_list_tb\" cols=\"30\" rows=\"5\" style=\"ime-mode:disabled;\">$black_list_tb</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'トラックバックの投稿禁止ワード（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_ng_word_tb\" cols=\"30\" rows=\"5\">$ng_word_tb</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'トラックバックの投稿必須ワード（改行区切りで複数指定可能）',
		ENV_VALUE => "<textarea name=\"env_need_word_tb\" cols=\"30\" rows=\"5\">$need_word_tb</textarea>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'トラックバックでの日本語の利用',
		ENV_VALUE => "<select name=\"env_need_japanese_tb\" xml:lang=\"ja\" lang=\"ja\">$need_japanese_tb</select>"
	);
	print $skin_ins->get_replace_data(
		'env',
		ENV_TITLE => 'トラックバックでの引用リンク',
		ENV_VALUE => "<select name=\"env_need_link_tb\" xml:lang=\"ja\" lang=\"ja\">$need_link_tb</select>"
	);
	print $skin_ins->get_data('env_foot');

	print $skin_ins->get_data('contents_foot');
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### ユーザー管理画面表示
sub output_user {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root') {
		$self->error('この操作を行う権限が与えられていません。');
	}

	if (!$self->{message}) {
		$self->{message} = '管理機能を利用するユーザーを設定する事ができます。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_user}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');
	print $skin_ins->get_replace_data(
		'contents',
		FORM_AUTHORITY => '<option value="guest">ゲストユーザー</option><option value="root">システム管理者</option>'
	);
	print $skin_ins->get_data('user_head');

	my $i = 0;

	open(FH, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
	while (<FH>) {
		chomp;
		my($user, $pwd, $authority) = split(/\t/);

		my $authority_list;
		if ($authority eq 'root') {
			$authority_list = '<option value="guest">ゲストユーザー</option><option value="root" selected="selected">システム管理者</option>';
		} else {
			$authority_list = '<option value="guest" selected="selected">ゲストユーザー</option><option value="root">システム管理者</option>';
		}
		$authority_list = "<select name=\"authority$i\" xml:lang=\"ja\" lang=\"ja\">$authority_list</select>";

		print $skin_ins->get_replace_data(
			'user',
			USER_NAME      => $user,
			USER_PWD       => $pwd,
			USER_AUTHORITY => $authority_list,
			USER_NO        => $i
		);

		$i++;
	}
	close(FH);

	print $skin_ins->get_data('user_foot');
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### キャンバス表示
sub output_canvas {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_paint}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_paint}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	my $info_exit;
	if ($self->{query}->{pch}) {
		$info_exit = "$self->{init}->{script_file}?mode=admin&amp;work=paint&amp;time=" . time;
	} else {
		$info_exit = "$self->{init}->{script_file}?mode=admin&amp;work=new&amp;exec_paint=on&amp;time=" . time;
	}

	my($info_ext, $info_pch, $info_image, $info_width, $info_height);
	if ($self->{query}->{file}) {
		my $file_ins = new webliberty::File($self->{query}->{file}->{file_name});

		$info_ext = $file_ins->get_ext;
		my $file  = "$self->{init}->{paint_tmp_file}\.$info_ext";

		if ($info_ext eq 'pch' or $info_ext eq 'spch') {
			open(FH, ">$self->{init}->{pch_dir}$file") or $self->error("Write Error : $self->{init}->{pch_dir}$file");
			binmode(FH);
			print FH $self->{query}->{file}->{file_data};
			close(FH);

			$info_pch = "$self->{init}->{pch_dir}$file";
		} else {
			open(FH, ">$self->{init}->{paint_dir}$file") or $self->error("Write Error : $self->{init}->{paint_dir}$file");
			binmode(FH);
			print FH $self->{query}->{file}->{file_data};
			close(FH);

			$file_ins = new webliberty::File("$self->{init}->{paint_dir}$file");
			($info_width, $info_height) = $file_ins->get_size;

			$info_image = "$self->{init}->{paint_dir}$file";
		}
	} else {
		$info_width  = $self->{query}->{width};
                $info_height = $self->{query}->{height};
	}

	if ($self->{query}->{pch}) {
		opendir(DIR, $self->{init}->{pch_dir}) or $self->error("Read Error : $self->{init}->{pch_dir}");
		my @files = sort { $a <=> $b } grep { m/\d+\.\w+/g } readdir(DIR);
		close(DIR);

		foreach (@files) {
			my $file_ins  = new webliberty::File("$self->{init}->{paint_dir}$_");

			if ($self->{query}->{pch} == $file_ins->get_name) {
				$info_ext = $file_ins->get_ext;

				last;
			}
		}

		if ($info_ext eq 'pch') {
			if ($self->{query}->{tool} ne 'paintbbs') {
				$self->{query}->{tool} = 'paintbbs';
			}
		} else {
			if ($self->{query}->{tool} eq 'paintbbs') {
				$self->{query}->{tool} = 'shipainter';
			}
		}

		$info_pch = "$self->{init}->{pch_dir}$self->{query}->{pch}\.$info_ext";
	}

	my($info_code, $info_archive, $info_tools, $info_layer, $info_resource, $info_reszip, $info_ttzip);
	if ($self->{query}->{tool} eq 'paintbbs') {
		$info_code     = 'pbbs.PaintBBS.class';
		$info_archive  = $self->{init}->{paintbbs_jar};
		$info_tools    = '';
		$info_layer    = '';
		$info_resource = '';
		$info_reszip   = '';
		$info_ttzip    = '';
	} elsif ($self->{query}->{tool} eq 'shipainterpro') {
		$info_code     = 'c.ShiPainter.class';
		$info_archive  = "$self->{init}->{spainter_jar},$self->{init}->{resource_dir}pro.zip";
		$info_tools    = 'pro';
		$info_layer    = '3';
		$info_resource = $self->{init}->{resource_dir};
		$info_reszip   = $self->{init}->{resource_dir} . 'res_pro.zip';
		$info_ttzip    = $self->{init}->{resource_dir} . 'tt.zip';
	} else {
		$info_code     = 'c.ShiPainter.class';
		$info_archive  = "$self->{init}->{spainter_jar},$self->{init}->{resource_dir}normal.zip";
		$info_tools    = 'normal';
		$info_layer    = '3';
		$info_resource = $self->{init}->{resource_dir};
		$info_reszip   = $self->{init}->{resource_dir} . 'res_normal.zip';
		$info_ttzip    = $self->{init}->{resource_dir} . 'tt.zip';
	}

	my $info_size;
	if ($self->{query}->{type} eq 'png') {
		$info_size = '0';
	} elsif ($self->{query}->{type} eq 'jpeg') {
		$info_size = '1';
	} else {
		$info_size = $self->{config}->{paint_image_size};
	}

	if (!$info_width or !$info_height) {
		$info_width  = $self->{config}->{paint_image_width};
		$info_height = $self->{config}->{paint_image_height};
	}

	my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_admin}, $self->{init}->{des_key});

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');
	print $skin_ins->get_replace_data(
		'canvas',
		CANVAS_CODE           => $info_code,
		CANVAS_ARCHIVE        => $info_archive,
		CANVAS_EXIT           => $info_exit,
		CANVAS_TOOLS          => $info_tools,
		CANVAS_LAYER          => $info_layer,
		CANVAS_RESOURCE       => $info_resource,
		CANVAS_RESZIP         => $info_reszip,
		CANVAS_TTZIP          => $info_ttzip,
		CANVAS_QUALITY        => $self->{config}->{paint_quality},
		CANVAS_IMAGE_SIZE     => $info_size,
		CANVAS_COMPRESS_LEVEL => $self->{config}->{paint_compress_level},
		CANVAS_USER           => $cookie_ins->get_cookie('admin_user'),
		CANVAS_PWD            => $cookie_ins->get_cookie('admin_pwd'),
		CANVAS_NO             => $self->{query}->{pch},
		CANVAS_PCH            => $info_pch,
		CANVAS_IMAGE          => $info_image,
		CANVAS_WIDTH          => $info_width,
		CANVAS_HEIGHT         => $info_height
	);
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### イラスト表示
sub output_illust {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_paint}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_image}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	opendir(DIR, $self->{init}->{paint_dir}) or $self->error("Read Error : $self->{init}->{paint_dir}");
	my @files = sort { $a <=> $b } grep { m/\d+\.\w+/g } readdir(DIR);
	close(DIR);

	my($file_image, $file_name, $file_width, $file_height, $flag);

	foreach (@files) {
		my $file_ins  = new webliberty::File("$self->{init}->{paint_dir}$_");
		$file_name = $file_ins->get_name . '.' . $file_ins->get_ext;

		if ($self->{query}->{pch} eq $file_ins->get_name) {
			($file_width, $file_height) = $file_ins->get_size;

			$flag = 1;

			last;
		}
	}
	if (!$flag) {
		$self->error('指定されたイラストは存在しません。');
	}

	my $file_path;
	if ($self->{init}->{paint_path}) {
		$file_path = $self->{init}->{paint_path};
	} else {
		$file_path = $self->{init}->{paint_dir};
	}

	my $file_image = "<img src=\"$file_path$file_name\" alt=\"ペイントファイル $file_name\" width=\"$file_width\" height=\"$file_height\" />";

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

### イラスト管理表示
sub output_paint {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root' and !$self->{config}->{auth_paint}) {
		$self->error('この操作を行う権限が与えられていません。');
	}

	if (!$self->{message}) {
		$self->{message} = 'イラストを投稿するには、作業対象を選択して<em>ペイントボタン</em>を押してください。';
	}

	opendir(DIR, $self->{init}->{pch_dir}) or $self->error("Read Error : $self->{init}->{pch_dir}");
	my @dir = sort { $b <=> $a } readdir(DIR);
	closedir(DIR);

	my $pch_list;
	foreach my $entry (@dir) {
		if ($entry =~ /^(\d+)\.\w+$/) {
			if ($self->{query}->{pch} == $1) {
				$pch_list .= "<option value=\"$1\" selected=\"selected\">No.$1のイラスト</option>";
			} else {
				$pch_list .= "<option value=\"$1\">No.$1のイラスト</option>";
			}
		}
	}

	my $tool_list;
	if (-e $self->{init}->{paintbbs_jar}) {
		if ($self->{config}->{paint_tool} eq 'paintbbs') {
			$tool_list .= "<option value=\"paintbbs\" selected=\"selected\">PaintBBS</option>";
		} else {
			$tool_list .= "<option value=\"paintbbs\">PaintBBS</option>";
		}
	}
	if (-e $self->{init}->{spainter_jar}) {
		if ($self->{config}->{paint_tool} eq 'shipainter') {
			$tool_list .= "<option value=\"shipainter\" selected=\"selected\">しぃペインター</option>";
		} else {
			$tool_list .= "<option value=\"shipainter\">しぃペインター</option>";
		}
		if ($self->{config}->{paint_tool} eq 'shipainterpro') {
			$tool_list .= "<option value=\"shipainterpro\" selected=\"selected\">しぃペインタープロ</option>";
		} else {
			$tool_list .= "<option value=\"shipainterpro\">しぃペインタープロ</option>";
		}
	}

	my($viewer_start, $viewer_end);
	if (!-e $self->{init}->{pch_jar}) {
		$viewer_start = '<!--';
                $viewer_end   = '-->';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_paint}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	my($canvas_width, $canvas_height, $width_flag, $height_flag);
	foreach (100, 150, 200, 250, 300, 350, 400, 450, 500, 550, 600, 650, 700, 750, 800) {
		if ($self->{config}->{paint_image_width} == $_) {
			$canvas_width  .= "<option value=\"$_\" selected=\"selected\">${_}px</option>";
			$width_flag = 1;
		} else {
			$canvas_width  .= "<option value=\"$_\">${_}px</option>";
		}
		if ($self->{config}->{paint_image_height} == $_) {
			$canvas_height .= "<option value=\"$_\" selected=\"selected\">${_}px</option>";
			$height_flag = 1;
		} else {
			$canvas_height .= "<option value=\"$_\">${_}px</option>";
		}
	}
	if (!$width_flag) {
		$canvas_width  .= "<option value=\"$self->{config}->{paint_image_width}\" selected=\"selected\">$self->{config}->{paint_image_width}px</option>";
	}
	if (!$height_flag) {
		$canvas_height .= "<option value=\"$self->{config}->{paint_image_height}\" selected=\"selected\">$self->{config}->{paint_image_height}px</option>";
	}

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');
	print $skin_ins->get_replace_data(
		'contents',
		PAINT_VIEWER_START => $viewer_start,
		PAINT_VIEWER_END   => $viewer_end,
		FORM_PCH           => $pch_list,
		FORM_TOOL          => $tool_list,
		FORM_WIDTH         => $canvas_width,
		FORM_HEIGHT        => $canvas_height
	);
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 再構築設定表示
sub output_build {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{message}) {
		$self->{message} = '作業内容を選択し、<em>実行ボタン</em>を押してください。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_build}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	my @index = <FH>;
	close(FH);

	my @numbers = map { (split(/\t/))[1] } @index;
	@index = @index[sort { $numbers[$b] <=> $numbers[$a] } (0 .. $#numbers)];
	my $max_no = (split(/\t/, $index[0]))[1];

	my $form_list = "<option value=\"all\">すべてを構築</option>";
	if ($self->{config}->{html_index_mode}) {
		$form_list .= "<option value=\"index\">インデックスを構築</option>";
	}
	if ($self->{config}->{html_archive_mode}) {
		foreach (0 .. int(($max_no - 1) / 50)) {
			my $from = $_ * 50 + 1;
			my $to;
			if ($_ == int(($max_no - 1) / 50)) {
				$to = $from + ($max_no - 1) % 50;
			} else {
				$to = $from + 50 - 1;
			}
			$form_list .= "<option value=\"$from\">アーカイブ（No.$from～No.$to）を構築</option>";
		}
	}

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');
	print $skin_ins->get_replace_data(
		'contents',
		FORM_LIST => $form_list
	);
	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 操作履歴表示
sub output_record {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->get_authority ne 'root') {
		$self->error('この操作を行う権限が与えられていません。');
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_record}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	$self->{html}->{header}    = $skin_ins->get_data('header');
	$self->{html}->{work_head} = $skin_ins->get_data('work_head');
	$self->{html}->{work}      = $self->work_navi($skin_ins);
	$self->{html}->{work_foot} = $skin_ins->get_data('work_foot');
	$self->{html}->{contents}  = $skin_ins->get_data('contents');

	$self->{html}->{record_head} = $skin_ins->get_data('record_head');

	my($record_size, $i);

	my $record_start = $self->{config}->{admin_size} * $self->{query}->{page};
	my $record_end   = $record_start + $self->{config}->{admin_size};

	open(FH, $self->{init}->{data_record}) or $self->error("Read Error : $self->{init}->{data_record}");
	while (<FH>) {
		$record_size++;
	}
	seek(FH, 0, 0);
	while (<FH>) {
		chomp;
		my($date, $user, $text, $host) = split(/\t/);

		$i++;
		if ($i <= $record_start) {
			next;
		} elsif ($i > $record_end) {
			last;
		}

		my($sec, $min, $hour, $day, $mon, $year, $week) = localtime($date);

		my $record_year   = sprintf("%02d", $year + 1900);
		my $record_month  = sprintf("%02d", $mon + 1);
		my $record_day    = sprintf("%02d", $day);
		my $record_hour   = sprintf("%02d", $hour);
		my $record_minute = sprintf("%02d", $min);
		my $record_week   = ${$self->{init}->{weeks}}[$week];

		$date = "$record_year年$record_month月$record_day日($record_week)$record_hour時$record_minute分";

		$self->{html}->{record} .= $skin_ins->get_replace_data(
			'record',
			RECORD_DATE   => $date,
			RECORD_YEAR   => $record_year,
			RECORD_MONTH  => $record_month,
			RECORD_DAY    => $record_day,
			RECORD_HOUR   => $record_hour,
			RECORD_MINUTE => $record_minute,
			RECORD_WEEK   => $record_week,
			RECORD_USER   => $user,
			RECORD_TEXT   => $text,
			RECORD_HOST   => $host
		);
	}
	close(FH);

	$self->{html}->{record_foot} = $skin_ins->get_data('record_foot');

	my $page_list;
	foreach (0 .. int(($record_size - 1) / $self->{config}->{admin_size})) {
		if ($_ == $self->{query}->{page}) {
			$page_list .= "<option value=\"$_\" selected=\"selected\">ページ" . ($_ + 1) . "</option>";
		} else {
			$page_list .= "<option value=\"$_\">ページ" . ($_ + 1) . "</option>";
		}
	}
	$self->{html}->{page} = $skin_ins->get_replace_data(
		'page',
		PAGE_LIST => $page_list
	);

	$self->{html}->{navi}   = $skin_ins->get_data('navi');
	$self->{html}->{footer} = $skin_ins->get_data('footer');

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

### ステータス画面
sub output_status {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my($diary_size, $diary_show_size, $diary_hidden_size);

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if ($stat) {
			$diary_show_size++;
		} else {
			$diary_hidden_size++;
		}
		$diary_size++;
	}
	close(FH);

	my($comt_size, $comt_show_size, $comt_hidden_size);

	open(FH, $self->{init}->{data_comt_index}) or $self->error("Read Error : $self->{init}->{data_comt_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $subj, $host) = split(/\t/);

		if ($stat) {
			$comt_show_size++;
		} else {
			$comt_hidden_size++;
		}
		$comt_size++;
	}
	close(FH);

	my($tb_size, $tb_show_size, $tb_hidden_size);

	open(FH, $self->{init}->{data_tb_index}) or $self->error("Read Error : $self->{init}->{data_tb_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $blog, $title, $url) = split(/\t/);

		if ($stat) {
			$tb_show_size++;
		} else {
			$tb_hidden_size++;
		}
		$tb_size++;
	}
	close(FH);

	my($diary_file_size, $comt_file_size, $tb_file_size, $upfile_file_size, $archive_file_size, $paint_file_size, $pch_file_size);

	opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
	foreach my $file (readdir(DIR)) {
		if ($file ne '.' and $file ne '..') {
			$diary_file_size += (-s "$self->{init}->{data_diary_dir}$file");
		}
	}
	closedir(DIR);
	opendir(DIR, $self->{init}->{data_comt_dir}) or $self->error("Read Error : $self->{init}->{data_comt_dir}");
	foreach my $file (readdir(DIR)) {
		if ($file ne '.' and $file ne '..') {
			$comt_file_size += (-s "$self->{init}->{data_comt_dir}$file");
		}
	}
	closedir(DIR);

	opendir(DIR, $self->{init}->{data_tb_dir}) or $self->error("Read Error : $self->{init}->{data_tb_dir}");
	foreach my $file (readdir(DIR)) {
		if ($file ne '.' and $file ne '..') {
			$tb_file_size += (-s "$self->{init}->{data_tb_dir}$file");
		}
	}
	closedir(DIR);

	opendir(DIR, "$self->{init}->{data_upfile_dir}") or $self->error("Read Error : $self->{init}->{data_upfile_dir}");
	foreach my $file (readdir(DIR)) {
		if ($file ne '.' and $file ne '..') {
			$upfile_file_size += (-s "$self->{init}->{data_upfile_dir}$file");
		}
	}
	closedir(DIR);

	opendir(DIR, "$self->{init}->{archive_dir}") or $self->error("Read Error : $self->{init}->{archive_dir}");
	foreach my $file (readdir(DIR)) {
		if ($file ne '.' and $file ne '..') {
			$archive_file_size += (-s "$self->{init}->{archive_dir}$file");
		}
	}
	closedir(DIR);

	opendir(DIR, $self->{init}->{paint_dir}) or $self->error("Read Error : $self->{init}->{paint_dir}");
	foreach my $file (readdir(DIR)) {
		if ($file ne '.' and $file ne '..') {
			$paint_file_size += (-s "$self->{init}->{paint_dir}$file");
		}
	}
	closedir(DIR);

	opendir(DIR, $self->{init}->{pch_dir}) or $self->error("Read Error : $self->{init}->{pch_dir}");
	foreach my $file (readdir(DIR)) {
		if ($file ne '.' and $file ne '..') {
			$pch_file_size += (-s "$self->{init}->{pch_dir}$file");
		}
	}
	closedir(DIR);

	$diary_file_size   = sprintf("%.1f", $diary_file_size / 1024);
	$comt_file_size    = sprintf("%.1f", $comt_file_size / 1024);
	$tb_file_size      = sprintf("%.1f", $tb_file_size / 1024);
	$upfile_file_size  = sprintf("%.1f", $upfile_file_size / 1024);
	$archive_file_size = sprintf("%.1f", $archive_file_size / 1024);
	$paint_file_size   = sprintf("%.1f", $paint_file_size / 1024);
	$pch_file_size     = sprintf("%.1f", $pch_file_size / 1024);

	my($archive_start, $archive_end);
	if (!$self->{config}->{html_archive_mode}) {
		$archive_start = '<!--';
		$archive_end   = '-->';
	}

	my($paint_start, $paint_end);
	if (!-e $self->{init}->{spainter_jar} and !-e $self->{init}->{paintbbs_jar}) {
		$paint_start = '<!--';
		$paint_end   = '-->';
	}

	my($root_start, $root_end);
	if ($self->get_authority ne 'root') {
		$root_start = '<!--';
		$root_end   = '-->';
	}

	my($guest_start, $guest_end);
	if ($self->get_authority eq 'root') {
		$guest_start = '<!--';
		$guest_end   = '-->';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_status}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('work_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('work_foot');

	print $skin_ins->get_replace_data(
		'contents',
		STATUS_DIARY_SIZE        => $diary_size || 0,
		STATUS_DIARY_SHOW_SIZE   => $diary_show_size || 0,
		STATUS_DIARY_HIDDEN_SIZE => $diary_hidden_size || 0,
		STATUS_DIARY_FILE_SIZE   => $diary_file_size || 0,
		STATUS_COMT_SIZE         => $comt_size || 0,
		STATUS_COMT_SHOW_SIZE    => $comt_show_size || 0,
		STATUS_COMT_HIDDEN_SIZE  => $comt_hidden_size || 0,
		STATUS_COMT_FILE_SIZE    => $comt_file_size || 0,
		STATUS_TB_SIZE           => $tb_size || 0,
		STATUS_TB_SHOW_SIZE      => $tb_show_size || 0,
		STATUS_TB_HIDDEN_SIZE    => $tb_hidden_size || 0,
		STATUS_TB_FILE_SIZE      => $tb_file_size || 0,
		STATUS_UPFILE_FILE_SIZE  => $upfile_file_size || 0,
		STATUS_ARCHIVE_FILE_SIZE => $archive_file_size || 0,
		STATUS_PAINT_FILE_SIZE   => $paint_file_size || 0,
		STATUS_PCH_FILE_SIZE     => $pch_file_size || 0,
		STATUS_ARCHIVE_START     => $archive_start,
		STATUS_ARCHIVE_END       => $archive_end,
		STATUS_PAINT_START       => $paint_start,
		STATUS_PAINT_END         => $paint_end,
		STATUS_ROOT_START        => $root_start,
		STATUS_ROOT_END          => $root_end,
		STATUS_GUEST_START       => $guest_start,
		STATUS_GUEST_END         => $guest_end
	);

	print $skin_ins->get_data('navi');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 認証画面表示
sub output_login {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->{query}->{admin_pwd}) {
		if ($self->{config}->{user_mode}) {
			$self->error('ユーザー名またはパスワードが違います。');
		} else {
			$self->error('パスワードが違います。');
		}
	}

	my($user_start, $user_end);
	if (!$self->{config}->{user_mode}) {
		$user_start = '<!--';
                $user_end   = '-->';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_USER_START => $user_start,
		INFO_USER_END   => $user_end
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('contents_head');
	print $self->work_navi($skin_ins);
	print $skin_ins->get_data('contents_foot');
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 作業内容一覧
sub work_navi {
	my $self     = shift;
	my $skin_ins = shift;

	my $work_data;

	$work_data  = "\t投稿\n";
	$work_data .= "new\t新規投稿\n";
	$work_data .= "edit\t記事編集\n";
	if (!$self->get_authority or $self->get_authority eq 'root' or $self->{config}->{auth_comment} or $self->{config}->{auth_trackback}) {
		$work_data .= "\tコミュニティ\n";
		if (!$self->get_authority or $self->get_authority eq 'root' or $self->{config}->{auth_comment}) {
			$work_data .= "comment\tコメント管理\n";
		}
		if (!$self->get_authority or $self->get_authority eq 'root' or $self->{config}->{auth_trackback}) {
			$work_data .= "trackback\tトラックバック管理\n";
		}
	}

	$work_data .= "\t各種設定\n";
	if ($self->{config}->{use_field} and (!$self->get_authority or $self->get_authority eq 'root' or $self->{config}->{auth_field})) {
		$work_data .= "field\t分類設定\n";
	}
	if ($self->{config}->{use_icon} and (!$self->get_authority or $self->get_authority eq 'root' or $self->{config}->{auth_icon})) {
		$work_data .= "icon\tアイコン設定\n";
	}
	if ($self->{config}->{top_mode} and (!$self->get_authority or $self->get_authority eq 'root' or $self->{config}->{auth_top})) {
		$work_data .= "top\tインデックスページ管理\n";
	}
	if ($self->{config}->{show_menu} and (!$self->get_authority or $self->get_authority eq 'root' or $self->{config}->{auth_menu})) {
		$work_data .= "menu\tコンテンツ設定\n";
	}
	if ($self->{config}->{show_link} and (!$self->get_authority or $self->get_authority eq 'root' or $self->{config}->{auth_link})) {
		$work_data .= "link\tリンク集設定\n";
	}
	if ($self->{config}->{profile_mode}) {
		$work_data .= "profile\tプロフィール設定\n";
	}
	$work_data .= "pwd\tパスワード設定\n";
	if (!$self->get_authority or $self->get_authority eq 'root') {
		$work_data .= "env\t環境設定\n";
	}

	$work_data .= "\tユーティリティ\n";
	if ((-e $self->{init}->{spainter_jar} or -e $self->{init}->{paintbbs_jar}) and (!$self->get_authority or $self->get_authority eq 'root' or $self->{config}->{auth_paint})) {
		$work_data .= "paint\tイラスト管理\n";
	}
	if ($self->{config}->{html_index_mode} or $self->{config}->{html_archive_mode}) {
		$work_data .= "build\tサイト再構築\n";
	}
	if ($self->{config}->{user_mode} and (!$self->get_authority or $self->get_authority eq 'root')) {
		$work_data .= "user\tユーザー管理\n";
	}
	if (!$self->get_authority or $self->get_authority eq 'root') {
		$work_data .= "record\t操作履歴表示\n";
	}
	$work_data .= "status\tステータス表示\n";

	my($work_list, $flag);

	foreach (split(/\n/, $work_data)) {
		my($work_id, $work_name) = split(/\t/);

		if ($work_id) {
			if ($self->{query}->{work} eq $work_id) {
				$work_list .= $skin_ins->get_replace_data(
					'work_selected',
					WORK_ID   => $work_id,
					WORK_NAME => $work_name
				);
			} else {
				$work_list .= $skin_ins->get_replace_data(
					'work',
					WORK_ID   => $work_id,
					WORK_NAME => $work_name
				);
			}
		} else {
			if ($flag) {
				$work_list .= $skin_ins->get_data('work_delimiter');
				$flag = 0;
			}

			$work_list .= $skin_ins->get_replace_data(
				'work_title',
				WORK_NAME => $work_name
			);

			$flag = 1;
		}
	}
	if ($flag) {
		$work_list .= $skin_ins->get_data('work_delimiter');
	}

	return $work_list;
}

### エラー出力
sub error {
	my $self    = shift;
	my $message = shift;

	if ($self->{query}->{exec_regist} or $self->{query}->{exec_preview}) {
		$self->{message} = $message;

		my $skin_ins = new webliberty::Skin;
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_form}");
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

		my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

		$skin_ins->replace_skin(
			$diary_ins->info,
			%{$self->{plugin}},
			INFO_MESSAGE => $self->{message}
		);

		my $subj_ins = new webliberty::String($self->{query}->{subj});
		my $text_ins = new webliberty::String($self->{query}->{text});

		$subj_ins->create_line;
		$text_ins->create_text;

		my $date = "$self->{query}->{year}$self->{query}->{month}$self->{query}->{day}$self->{query}->{hour}$self->{query}->{minute}";

		my $form_ping;
		if ($self->{query}->{ping}) {
			$form_ping  = ' checked="checked"';
		} else {
			$form_ping  = '';
		}

		print $self->header;
		print $skin_ins->get_data('header');
		print $skin_ins->get_data('work_head');
		print $self->work_navi($skin_ins);
		print $skin_ins->get_data('work_foot');
		print $skin_ins->get_replace_data(
			'form',
			$diary_ins->diary_form($self->{query}->{edit}, $self->{query}->{id}, $self->{query}->{stat}, $self->{query}->{break}, $self->{query}->{comt}, $self->{query}->{tb}, '', $date, '', $subj_ins->get_string, $text_ins->get_string, '', '', '', ''),
			FORM_LABEL => 'エラー',
			FORM_WORK  => $self->{query}->{work},
			FORM_EXT1  => '',
			FORM_EXT2  => '',
			FORM_EXT3  => '',
			FORM_EXT4  => '',
			FORM_EXT5  => '',
			FORM_TBURL => $self->{query}->{tb_url},
			FORM_PING  => $form_ping
		);
		print $skin_ins->get_data('navi');
		print $skin_ins->get_data('footer');

		exit;
	}

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->error($message);

	exit;
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

### アイテム並び替え
sub _sort_item {
	my $self = shift;
	my @item = @_;

	my @fields = map { (split(/\t/))[0] } @item;
	@item = @item[sort { $fields[$a] cmp $fields[$b] } (0 .. $#fields)];

	return(@item);
}

1;
