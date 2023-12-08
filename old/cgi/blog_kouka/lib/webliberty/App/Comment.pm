#webliberty::App::Comment.pm (2008/07/05)
#Copyright(C) 2002-2008 Knight, All rights reserved.

package webliberty::App::Comment;

use strict;
use base qw(webliberty::Basis Exporter);
use vars qw(@EXPORT_OK);
use webliberty::Host;
use webliberty::Cookie;
use webliberty::Lock;
use webliberty::Skin;
use webliberty::Sendmail;
use webliberty::Plugin;
use webliberty::App::Init;
use webliberty::App::Diary;

@EXPORT_OK = qw(check regist);

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

	if ($self->{query}->{work} eq 'regist') {
		$self->check;
		if ($self->{query}->{exec_preview}) {
			$self->output_preview;
		} else {
			$self->regist;
			$self->output_complete;
		}
	} else {
		$self->output_list;
	}

	return;
}

### 入力内容チェック
sub check {
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

	my $name_ins  = new webliberty::String($self->{query}->{name});
	my $mail_ins  = new webliberty::String($self->{query}->{mail});
	my $url_ins   = new webliberty::String($self->{query}->{url});
	my $subj_ins  = new webliberty::String($self->{query}->{subj});
	my $text_ins  = new webliberty::String($self->{query}->{text});
	my $pwd_ins   = new webliberty::String($self->{query}->{pwd});

	$name_ins->create_line;
	$mail_ins->create_line;
	$url_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;
	$pwd_ins->create_line;

	my $init_ins = new webliberty::App::Init;
	my %label = %{$init_ins->get_label};

	if (!$name_ins->get_string) {
		$self->error("$label{'pc_name'}が入力されていません。");
	}
	if (!$subj_ins->get_string) {
		$self->error("$label{'pc_subj'}が入力されていません。");
	}
	if (!$text_ins->get_string) {
		$self->error("$label{'pc_text'}が入力されていません。");
	}

	if ($name_ins->check_length > 20 * 2) {
		$self->error("$label{'pc_name'}の長さは全角20文字までにしてください。");
	}
	if ($mail_ins->check_length > 50) {
		$self->error("$label{'pc_mail'}の長さは半角50文字までにしてください。");
	}
	if ($url_ins->check_length > 255) {
		$self->error("$label{'pc_url'}の長さは半角255文字までにしてください。");
	}
	if ($text_ins->check_length > 2000 * 2) {
		$self->error("$label{'pc_text'}の長さは全角2000文字までにしてください。");
	}
	if ($text_ins->check_line > 200) {
		$self->error("$label{'pc_text'}は200行までにしてください。");
	}
	if ($pwd_ins->get_string and ($pwd_ins->check_length < 4 or $pwd_ins->check_length > 10)) {
		$self->error("$label{'pc_pwd'}の長さは半角4文字以上10文字以内にしてください。");
	}

	if ($mail_ins->get_string and ($mail_ins->get_string =~ /[^\w\.\@\d\+\-\_]/ or $mail_ins->get_string !~ /(.+)\@(.+)\.(.+)/)) {
		$self->error("$label{'pc_mail'}の入力内容が正しくありません。");
	}

	if ($self->{config}->{ng_word}) {
		foreach (split(/<>/, $self->{config}->{ng_word})) {
			if ($_ and $text_ins->get_string =~ /$_/) {
				$self->error("「$_」は投稿禁止ワードに設定されています。");
			}
		}
	}
	if ($self->{config}->{need_word}) {
		my $flag;
		foreach (split(/<>/, $self->{config}->{need_word})) {
			if ($_ and $text_ins->get_string =~ /$_/) {
				$flag = 1;
			}
		}
		if (!$flag) {
			$self->error("$label{'pc_text'}に投稿必須ワードが含まれていません。");
		}
	}
	if ($self->{config}->{need_japanese} and $text_ins->get_string !~ /[\x80-\xFF]/) {
		$self->error("日本語を含まない$label{'pc_text'}は投稿できません。");
	}
	if ($self->{config}->{max_link} and $self->{config}->{max_link} < $text_ins->check_count('http://')) {
		$self->error("$label{'pc_text'}にURLを$self->{config}->{max_link}個以上書く事はできません。");
	}

	return;
}

### コメント投稿
sub regist {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $pno_ins   = new webliberty::String($self->{query}->{pno});
	my $name_ins  = new webliberty::String($self->{query}->{name});
	my $mail_ins  = new webliberty::String($self->{query}->{mail});
	my $url_ins   = new webliberty::String($self->{query}->{url});
	my $subj_ins  = new webliberty::String($self->{query}->{subj});
	my $text_ins  = new webliberty::String($self->{query}->{text});
	my $color_ins = new webliberty::String($self->{query}->{color});
	my $icon_ins  = new webliberty::String($self->{query}->{icon});
	my $pwd_ins   = new webliberty::String($self->{query}->{pwd});
	my $save_ins  = new webliberty::String($self->{query}->{save});

	$pno_ins->create_number;
	$name_ins->create_line;
	$mail_ins->create_line;
	$url_ins->create_line;
	$subj_ins->create_line;
	$text_ins->create_text;
	$color_ins->create_line;
	$icon_ins->create_line;
	$pwd_ins->create_line;
	$save_ins->create_line;

	if ($url_ins->get_string eq 'http://') {
		$url_ins->set_string('');
	}

	if ($save_ins->get_string) {
		my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_id}, $self->{init}->{des_key});
		$cookie_ins->set_holddays($self->{config}->{cookie_holddays});
		$cookie_ins->set_cookie(
			name  => $name_ins->get_string,
			mail  => $mail_ins->get_string,
			url   => $url_ins->get_string,
			color => $color_ins->get_string,
			icon  => $icon_ins->get_string,
			pwd   => $pwd_ins->get_string
		);
	} else {
		my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_id}, $self->{init}->{des_key});
		$cookie_ins->set_cookie;
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('ファイルがロックされています。時間をおいてもう一度投稿してください。');
	}

	open(FH, $self->{init}->{data_comt_index}) or $self->error("Read Error : $self->{init}->{data_comt_index}");
	my @index = sort { $b <=> $a } <FH>;
	close(FH);

	my $new_no       = (split(/\t/, $index[0]))[0] + 1;
	my $comment_file = $self->{init}->{data_comt_dir} . $pno_ins->get_string . "\.$self->{init}->{data_ext}";

	if (!-e $comment_file) {
		open(FH, ">$comment_file") or $self->error("Write Error : $comment_file");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, "$comment_file") or $self->error("Chmod Error : $comment_file");
			} else {
				chmod(0666, "$comment_file") or $self->error("Chmod Error : $comment_file");
			}
		}
	}

	if ($pwd_ins->get_string) {
		$pwd_ins->create_password;
	}
	my $host_ins = new webliberty::Host;

	my($last_date, $last_host) = (split(/\t/, $index[0]))[3, 6];
	chomp($last_host);
	if (time - $last_date < $self->{config}->{wait_time} and $host_ins->get_host eq $last_host) {
		$lock_ins->file_unlock;
		$self->error("連続投稿は$self->{config}->{wait_time}秒以上時間をあけてください。");
	}

	my $comt_stat = $self->{config}->{comt_stat};
	if ($self->{config}->{whisper_mode} and $self->{query}->{whisper}) {
		$comt_stat = 2;
	}

	open(FH, ">>$comment_file") or $self->error("Write Error : $comment_file");
	print FH "$new_no\t" . $pno_ins->get_string . "\t$comt_stat\t" . time . "\t" . $name_ins->get_string . "\t" . $mail_ins->get_string . "\t" . $url_ins->get_string . "\t" . $subj_ins->get_string . "\t" . $text_ins->get_string . "\t" . $color_ins->get_string . "\t" . $icon_ins->get_string . "\t\t\t" . $pwd_ins->get_string . "\t" . $host_ins->get_host . "\n";
	close(FH);

	unshift(@index, "$new_no\t" . $pno_ins->get_string . "\t$comt_stat\t" . time . "\t" . $name_ins->get_string . "\t" . $subj_ins->get_string . "\t" . $host_ins->get_host . "\n");

	open(FH, ">$self->{init}->{data_comt_index}") or $self->error("Write Error : $self->{init}->{data_comt_index}");
	print FH @index;
	close(FH);

	$lock_ins->file_unlock;

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	$self->{update}->{query}->{no} = $pno_ins->get_string;

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	if ($self->{config}->{sendmail_cmt_mode}) {
		my $flag;
		foreach (split(/<>/, $self->{config}->{sendmail_admin})) {
			if ($name_ins->get_string eq $_) {
				$flag = 1;
				last;
			}
		}

		my $mail_body;
		$mail_body  = "$self->{config}->{site_title}に以下のコメントが投稿されました。\n";
		$mail_body .= "\n";
		$mail_body .= "投稿者：" . $name_ins->get_string . "\n";
		if ($self->{config}->{sendmail_detail}) {
			if ($host_ins->get_host) {
				$mail_body .= "ホスト：" . $host_ins->get_host . "\n";
			}
			if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
				$mail_body .= "記事URL：$self->{config}->{site_url}$1?no=" . $pno_ins->get_string . "\n";
			}
		}
		$mail_body .= "\n";
		$mail_body .= $text_ins->trim_string($self->{config}->{sendmail_length}, '...');

		if (!$flag) {
			my $sendmail_ins = new webliberty::Sendmail($self->{config}->{sendmail_path});
			foreach (split(/<>/, $self->{config}->{sendmail_list})) {
				if (!$_) {
					next;
				}

				my($flag, $message) = $sendmail_ins->sendmail(
					send_to => $_,
					subject => "$self->{config}->{site_title}にコメントの投稿がありました",
					name    => $name_ins->get_string,
					message => $mail_body
				);
				if (!$flag) {
					$self->error($message);
				}
			}
		}
	}

	return;
}

### 投稿完了画面表示
sub output_complete {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_complete}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_NO => $self->{query}->{no}
	);

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_data('contents');
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

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_diary}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_comment}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	$self->{html}->{header} = $skin_ins->get_data('header');

	if ($self->{config}->{show_navigation} == 2) {
		$self->{init}->{js_navi_start_file} =~ s/^\.\///;
		$self->{html}->{comment_head} = "<script type=\"text/javascript\" src=\"$self->{config}->{site_url}$self->{init}->{js_navi_start_file}\"></script>\n";
	}

	$self->{html}->{comment_head} .= $skin_ins->get_replace_data(
		'comment_head',
		ARTICLE_COMMENT_START => '<!--',
		ARTICLE_COMMENT_END   => '-->',
	);
	$self->{html}->{comment} .= $skin_ins->get_replace_data(
		'comment',
		$diary_ins->comment_article($self->{query}->{pno}, $self->{query}->{pno}, 1, time, $self->{query}->{name}, $self->{query}->{mail}, $self->{query}->{url}, $self->{query}->{subj}, $self->{query}->{text}, $self->{query}->{color}, $self->{query}->{icon}, '', '', $self->{query}->{pwd}, '')
	);
	$self->{html}->{comment_foot} = $skin_ins->get_data('comment_foot');

	my $form_stat = 1;
	if ($self->{config}->{whisper_mode} and $self->{query}->{whisper}) {
		$form_stat = 2;
	}

	my $form_save;
	if ($self->{query}->{save}) {
		$form_save = ' checked="checked"';
	}

	$self->{html}->{form} = $skin_ins->get_replace_data(
		'form',
		$diary_ins->comment_form($self->{query}->{pno}, $self->{query}->{pno}, $form_stat, '', $self->{query}->{name}, $self->{query}->{mail}, $self->{query}->{url}, $self->{query}->{subj}, $self->{query}->{text}, $self->{query}->{color}, $self->{query}->{icon}, '', '', $self->{query}->{pwd}, ''),
		FORM_MODE          => 'comment',
		FORM_WORK          => 'regist',
		FORM_SAVE          => $form_save,
		FORM_COMMENT_START => '<!--',
		FORM_COMMENT_END   => '-->',
		FORM_EDIT_START    => '<!--',
		FORM_EDIT_END      => '-->',
		FORM_ERROR_START   => '<!--',
		FORM_ERROR_END     => '-->'
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

### 記事一覧
sub output_list {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_diary}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_comment}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
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

	my $flag;
	my $diary_subj;

	open(FH, "$self->{init}->{data_diary_dir}$data_file") or $self->error("Read Error : $self->{init}->{data_diary_dir}$data_file");
	while (<FH>) {
		chomp;
		my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

		if ($self->{index}->{no} == $no) {
			if ($comt) {
				$flag = 1;
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

	if ($flag) {
		$self->{html}->{comment} = $skin_ins->get_replace_data(
			'comment_head',
			ARTICLE_COMMENT_START => '<!--',
			ARTICLE_COMMENT_END   => '-->',
		);

		if (-e "$self->{init}->{data_comt_dir}$self->{query}->{no}\.$self->{init}->{data_ext}") {
			open(FH, "$self->{init}->{data_comt_dir}$self->{query}->{no}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}$self->{query}->{no}\.$self->{init}->{data_ext}");
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
			ARTICLE_COMMENT_START => '<!--',
			ARTICLE_COMMENT_END   => '-->',
		);

		my $cookie_ins = new webliberty::Cookie($self->{config}->{cookie_id}, $self->{init}->{des_key});

		my $form_save;
		if ($cookie_ins->get_cookie('name')) {
			$form_save = ' checked="checked"';
		}

		$self->{html}->{form} = $skin_ins->get_replace_data(
			'form',
			$diary_ins->comment_form($self->{query}->{no}, $self->{query}->{no}, 1, '', $cookie_ins->get_cookie('name'), $cookie_ins->get_cookie('mail'), $cookie_ins->get_cookie('url'), "Re:$diary_subj", '', $cookie_ins->get_cookie('color'), $cookie_ins->get_cookie('icon'), '', '', $cookie_ins->get_cookie('pwd'), ''),
			FORM_MODE          => 'comment',
			FORM_WORK          => 'regist',
			FORM_SAVE          => $form_save,
			FORM_PREVIEW_START => '<!--',
			FORM_PREVIEW_END   => '-->',
			FORM_EDIT_START    => '<!--',
			FORM_EDIT_END      => '-->',
			FORM_ERROR_START   => '<!--',
			FORM_ERROR_END     => '-->'
		);
	}

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

### エラー出力
sub error {
	my $self    = shift;
	my $message = shift;

	if ($self->{query}->{exec_regist} or $self->{query}->{exec_preview}) {
		my $skin_ins = new webliberty::Skin;
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_diary}");
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_comment}");
		$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

		my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

		$skin_ins->replace_skin(
			$diary_ins->info,
			%{$self->{plugin}},
			INFO_ERROR => $message
		);

		my $subj_ins = new webliberty::String($self->{query}->{subj});
		my $text_ins = new webliberty::String($self->{query}->{text});

		$subj_ins->create_line;
		$text_ins->create_text;

		my $form_stat = 1;
		if ($self->{config}->{whisper_mode} and $self->{query}->{whisper}) {
			$form_stat = 2;
		}

		my $form_save;
		if ($self->{query}->{save}) {
			$form_save = ' checked="checked"';
		}

		print $self->header;
		print $skin_ins->get_data('header');
		print $skin_ins->get_replace_data(
			'form',
			$diary_ins->comment_form($self->{query}->{no}, $self->{query}->{no}, $form_stat, '', $self->{query}->{name}, $self->{query}->{mail}, $self->{query}->{url}, $self->{query}->{subj}, $self->{query}->{text}, '', '', '', '', $self->{query}->{pwd}, ''),
			FORM_MODE          => 'comment',
			FORM_WORK          => 'regist',
			FORM_SAVE          => $form_save,
			FORM_COMMENT_START => '<!--',
			FORM_COMMENT_END   => '-->',
			FORM_PREVIEW_START => '<!--',
			FORM_PREVIEW_END   => '-->',
			FORM_EDIT_START    => '<!--',
			FORM_EDIT_END      => '-->',
		);
		print $skin_ins->get_data('footer');

		exit;
	}

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->error($message);

	exit;
}

1;
