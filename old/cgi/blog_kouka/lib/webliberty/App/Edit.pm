#webliberty::App::Edit.pm (2008/07/05)
#Copyright(C) 2002-2008 Knight, All rights reserved.

package webliberty::App::Edit;

use strict;
use base qw(webliberty::Basis Exporter);
use vars qw(@EXPORT_OK);
use webliberty::String;
use webliberty::Host;
use webliberty::Cookie;
use webliberty::Lock;
use webliberty::Skin;
use webliberty::Plugin;
use webliberty::App::Init;
use webliberty::App::Diary;

@EXPORT_OK = qw(check_password edit del);

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

	if ($self->{query}->{work}) {
		if ($self->{query}->{work} eq 'edit') {
			eval "use webliberty::App::Comment qw(check);";
			$self->check;
			if ($self->{query}->{exec_preview}) {
				$self->output_preview;
			} else {
				$self->edit;
				$self->output_login;
			}
		} elsif ($self->{query}->{work} eq 'del') {
			$self->del;
			$self->output_login;
		} else {
			$self->output_form;
		}
	} else {
		$self->output_login;
	}

	return;
}

### コメント編集
sub edit {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $name_ins  = new webliberty::String($self->{query}->{name});
	my $mail_ins  = new webliberty::String($self->{query}->{mail});
	my $url_ins   = new webliberty::String($self->{query}->{url});
	my $subj_ins  = new webliberty::String($self->{query}->{subj});
	my $text_ins  = new webliberty::String($self->{query}->{text});
	my $color_ins = new webliberty::String($self->{query}->{color});
	my $icon_ins  = new webliberty::String($self->{query}->{icon});
	my $pwd_ins   = new webliberty::String($self->{query}->{pwd});

	$name_ins->create_line;
	$mail_ins->create_line;
	$url_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;
	$color_ins->create_line;
	$icon_ins->create_line;
	$pwd_ins->create_line;

	if ($url_ins->get_string eq 'http://') {
		$url_ins->set_string('');
	}
	if ($pwd_ins->get_string) {
		$pwd_ins->create_password;
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my $init_ins = new webliberty::App::Init;
	my %label = %{$init_ins->get_label};
	my $host_ins = new webliberty::Host;

	my($new_data, $flag);

	open(FH, "$self->{init}->{data_comt_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}$self->{query}->{pno}\.$self->{init}->{data_ext}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

		if ($self->{query}->{no} == $no) {
			if ($self->{query}->{mode} eq 'admin') {
				$new_data .= "$no\t$pno\t$stat\t$date\t" . $name_ins->get_string . "\t" . $mail_ins->get_string . "\t" . $url_ins->get_string . "\t" . $subj_ins->get_string . "\t" . $text_ins->get_string . "\t" . $color_ins->get_string . "\t" . $icon_ins->get_string . "\t$file\t$rank\t$pwd\t$host\n";
			} else {
				my $key_ins = new webliberty::String($self->{query}->{key});
				if (!$pwd or !$key_ins->check_password($pwd)) {
					$lock_ins->file_unlock;
					$self->error("$label{'pc_pwd'}が違います。");
				}

				if ($self->{query}->{whisper}) {
					$stat = 2;
				} elsif ($stat == 2) {
					$stat = $self->{config}->{comt_stat};
				}

				$new_data .= "$no\t$pno\t$stat\t$date\t" . $name_ins->get_string . "\t" . $mail_ins->get_string . "\t" . $url_ins->get_string . "\t" . $subj_ins->get_string . "\t" . $text_ins->get_string . "\t" . $color_ins->get_string . "\t" . $icon_ins->get_string . "\t$file\t$rank\t" . $pwd_ins->get_string . "\t" . $host_ins->get_host . "\n";
			}

			$flag = 1;
		} else {
			$new_data .= "$_\n";
		}
	}
	close(FH);

	if (!$flag) {
		$self->error('指定された記事は存在しません。');
	}

	my($index_data, $flag);

	open(FH, $self->{init}->{data_comt_index}) or $self->error("Read Error : $self->{init}->{data_comt_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $subj, $host) = split(/\t/);

		if ($self->{query}->{no} == $no) {
			if ($self->{query}->{whisper}) {
				$stat = 2;
			} elsif ($stat == 2) {
				$stat = $self->{config}->{comt_stat};
			}

			$index_data .= "$no\t$pno\t$stat\t$date\t" . $name_ins->get_string . "\t" . $subj_ins->get_string . "\t" . $host_ins->get_host . "\n";
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

	$self->{update}->{query}->{no} = $self->{query}->{pno};

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	$self->{message} = "No.$self->{query}->{no}のコメントを編集しました。";

	return;
}

### コメント削除
sub del {
	my $self  = shift;
	my $agent = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

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

	if ($self->{query}->{mode} eq 'admin') {
		$self->{query}->{no} = $self->{query}->{del};
	}
	if (!$self->{query}->{no}) {
		$self->error('削除したい記事を選択してください。');
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	my($new_index, $diary_no, %del_file);

	open(FH, $self->{init}->{data_comt_index}) or $self->error("Read Error : $self->{init}->{data_comt_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $subj, $host) = split(/\t/);

		if ($self->{query}->{no} =~ /(^|\n)$no(\n|$)/) {
			$diary_no = $pno;
			$del_file{"$self->{init}->{data_comt_dir}$pno\.$self->{init}->{data_ext}"} = 1;
		} else {
			$new_index .= "$_\n";
		}
	}
	close(FH);

	my $init_ins = new webliberty::App::Init;
	my %label = %{$init_ins->get_label};

	my $flag;

	foreach my $entry (keys %del_file) {
		my $new_data;

		open(FH, $entry) or $self->error("Read Error : $entry");
		while (<FH>) {
			chomp;
			my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

			if ($self->{query}->{no} =~ /(^|\n)$no(\n|$)/) {
				if ($self->{query}->{mode} ne 'admin') {
					my $pwd_ins = new webliberty::String($self->{query}->{pwd});
					if (!$pwd or !$pwd_ins->check_password($pwd)) {
						$lock_ins->file_unlock;
						$self->error("$label{'pc_pwd'}が違います。");
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

	open(FH, ">$self->{init}->{data_comt_index}") or $self->error("Write Error : $self->{init}->{data_comt_index}");
	print FH $new_index;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	$self->{update}->{query}->{no} = $diary_no;

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	my $del_list = $self->{query}->{no};
	$del_list =~ s/\n/、/g;

	$self->{message} = "No.$del_listのコメントを削除しました。";

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

	if (!$self->{message}) {
		$self->{message} = 'この内容で投稿します。よろしければ<em>投稿ボタン</em>を押してください。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	if ($self->{query}->{mode} eq 'admin') {
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	}
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_comment}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_diary}", available => 'comment_head,comment,comment_foot');
	if ($self->{query}->{mode} eq 'admin') {
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	}
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	my $date_ins  = new webliberty::String($self->{query}->{date});
	my $name_ins  = new webliberty::String($self->{query}->{name});
	my $mail_ins  = new webliberty::String($self->{query}->{mail});
	my $url_ins   = new webliberty::String($self->{query}->{url});
	my $subj_ins  = new webliberty::String($self->{query}->{subj});
	my $text_ins  = new webliberty::String($self->{query}->{text});
	my $color_ins = new webliberty::String($self->{query}->{color});
	my $icon_ins  = new webliberty::String($self->{query}->{icon});
	my $pwd_ins   = new webliberty::String($self->{query}->{pwd});

	$date_ins->create_line;
	$name_ins->create_line;
	$mail_ins->create_line;
	$url_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;
	$color_ins->create_line;
	$icon_ins->create_line;
	$pwd_ins->create_line;

	my($pwd_start, $pwd_end);
	if ($self->{query}->{mode} eq 'admin') {
		$pwd_start = '<!--';
		$pwd_end   = '-->';
	}

	print $self->header;
	print $skin_ins->get_data('header');

	if ($self->{query}->{mode} eq 'admin') {
		my $admin_ins = new webliberty::App::Admin($self->{init}, $self->{config}, $self->{query});
		$admin_ins->check_password;

		print $skin_ins->get_data('work_head');
		print $admin_ins->work_navi($skin_ins);
		print $skin_ins->get_data('work_foot');
	}

	if ($self->{config}->{whisper_mode} and $self->{query}->{whisper}) {
		$self->{query}->{stat} = 2;
	}

	print $skin_ins->get_replace_data(
		'form',
		$diary_ins->comment_form($self->{query}->{no}, $self->{query}->{pno}, $self->{query}->{stat}, $self->{query}->{date}, $name_ins->get_string, $mail_ins->get_string, $url_ins->get_string, $subj_ins->get_string, $text_ins->get_string, $color_ins->get_string, $icon_ins->get_string, '', '', $pwd_ins->get_string, ''),
		FORM_MODE          => $self->{query}->{mode},
		FORM_WORK          => $self->{query}->{work},
		FORM_SAVE_START    => '<!--',
		FORM_SAVE_END      => '-->',
		FORM_COMMENT_START => '<!--',
		FORM_COMMENT_END   => '-->',
		FORM_EDIT_START    => '<!--',
		FORM_EDIT_END      => '-->',
		FORM_ERROR_START   => '<!--',
		FORM_ERROR_END     => '-->',
		FORM_PWD_START     => $pwd_start,
		FORM_PWD_END       => $pwd_end
	);
	print $skin_ins->get_replace_data(
		'comment_head',
		ARTICLE_COMMENT_START => '<!--',
		ARTICLE_COMMENT_END   => '-->',
	);
	print $skin_ins->get_replace_data(
		'comment',
		$diary_ins->comment_article($self->{query}->{no}, $self->{query}->{pno}, 1, $self->{query}->{date}, $name_ins->get_string, '', '', $subj_ins->get_string, $text_ins->get_string, $color_ins->get_string, $icon_ins->get_string, '', '', '', '')
	);
	print $skin_ins->get_data('comment_foot');
	if ($self->{query}->{mode} eq 'admin') {
		print $skin_ins->get_data('navi');
	}
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 編集フォーム表示
sub output_form {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{message}) {
		$self->{message} = '記事を入力し、<em>投稿ボタン</em>を押してください。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	if ($self->{query}->{mode} eq 'admin') {
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_work}");
	}
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_comment}");
	if ($self->{query}->{mode} eq 'admin') {
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_admin_navi}");
	}
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	if ($self->{query}->{mode} eq 'admin') {
		$self->{query}->{no} = $self->{query}->{edit};
	}
	if (!$self->{query}->{no}) {
		$self->error('編集したい記事を選択してください。');
	}

	my $edit_pno;

	open(FH, $self->{init}->{data_comt_index}) or $self->error("Read Error : $self->{init}->{data_comt_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $subj, $host) = split(/\t/);

		if ($self->{query}->{no} == $no) {
			$edit_pno = $pno;
			last;
		}
	}
	close(FH);

	my(%form, $flag);

	open(FH, "$self->{init}->{data_comt_dir}$edit_pno\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}$edit_pno\.$self->{init}->{data_ext}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

		if ($self->{query}->{no} == $no) {
			if ($self->{query}->{mode} ne 'admin') {
				my $pwd_ins = new webliberty::String($self->{query}->{pwd});
				if (!$pwd or !$pwd_ins->check_password($pwd)) {
					$self->error('削除キーが違います。');
				}
			}

			%form = $diary_ins->comment_form($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $self->{query}->{pwd}, $host);

			$flag = 1;

			last;
		}
	}
	close(FH);

	if (!$flag) {
		$self->error('指定された記事は存在しません。');
	}

	my $work;
	if ($self->{query}->{mode} eq 'admin') {
		$work = 'comment';
	} else {
		$work = 'edit';
	}

	my($pwd_start, $pwd_end);
	if ($self->{query}->{mode} eq 'admin') {
		$pwd_start = '<!--';
		$pwd_end   = '-->';
	}

	print $self->header;
	print $skin_ins->get_data('header');

	if ($self->{query}->{mode} eq 'admin') {
		my $admin_ins = new webliberty::App::Admin($self->{init}, $self->{config}, $self->{query});
		$admin_ins->check_password;

		print $skin_ins->get_data('work_head');
		print $admin_ins->work_navi($skin_ins);
		print $skin_ins->get_data('work_foot');
	}

	print $skin_ins->get_replace_data(
		'form',
		%form,
		FORM_MODE          => $self->{query}->{mode},
		FORM_WORK          => $work,
		FORM_SAVE_START    => '<!--',
		FORM_SAVE_END      => '-->',
		FORM_COMMENT_START => '<!--',
		FORM_COMMENT_END   => '-->',
		FORM_PREVIEW_START => '<!--',
		FORM_PREVIEW_END   => '-->',
		FORM_ERROR_START   => '<!--',
		FORM_ERROR_END     => '-->',
		FORM_PWD_START     => $pwd_start,
		FORM_PWD_END       => $pwd_end
	);
	if ($self->{query}->{mode} eq 'admin') {
		print $skin_ins->get_data('navi');
	}
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

	if (!$self->{message}) {
		$self->{message} = '投稿時に設定した削除キーを入力してください。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_edit}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_replace_data(
		'contents',
		FORM_NO   => $self->{query}->{no},
		FORM_LIST => $self->_work_list
	);
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### エラー出力
sub error {
	my $self    = shift;
	my $message = shift;

	if ($self->{query}->{work} eq 'edit') {
		$self->{message} = $message;

		my $skin_ins = new webliberty::Skin;
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_comment}");
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

		my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

		$skin_ins->replace_skin(
			$diary_ins->info,
			%{$self->{plugin}},
			INFO_ERROR => $self->{message}
		);

		my $subj_ins = new webliberty::String($self->{query}->{subj});
		my $text_ins = new webliberty::String($self->{query}->{text});

		$subj_ins->create_line;
		$text_ins->create_text;

		my $date = "$self->{query}->{year}$self->{query}->{month}$self->{query}->{day}$self->{query}->{hour}$self->{query}->{minute}";

		print $self->header;
		print $skin_ins->get_data('header');
		print $skin_ins->get_data('work');
		print $skin_ins->get_replace_data(
			'form',
			$diary_ins->comment_form($self->{query}->{no}, $self->{query}->{pno}, '', $self->{query}->{date}, $self->{query}->{name}, '', '', $subj_ins->get_string, $text_ins->get_string, '', '', '', '', $self->{query}->{key}, ''),
			FORM_MODE          => 'edit',
			FORM_WORK          => 'edit',
			FORM_SAVE_START    => '<!--',
			FORM_SAVE_END      => '-->',
			FORM_COMMENT_START => '<!--',
			FORM_COMMENT_END   => '-->',
			FORM_PREVIEW_START => '<!--',
			FORM_PREVIEW_END   => '-->',
			FORM_EDIT_START    => '<!--',
			FORM_EDIT_END      => '-->'
		);
		print $skin_ins->get_data('footer');

		exit;
	}

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->error($message);

	exit;
}

### 作業内容一覧
sub _work_list {
	my $self = shift;

	my $work_list;

	$work_list  = "<option value=\"form\">記事編集</option>";
	$work_list .= "<option value=\"del\">記事削除</option>";

	return $work_list;
}

1;
