#webliberty::App::Mobile.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Mobile;

use strict;
use Jcode;
use base qw(webliberty::Basis);
use webliberty::Parser;
use webliberty::String;
use webliberty::Encoder;
use webliberty::Plugin;
use webliberty::App::Init;
use webliberty::App::Diary;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init    => shift,
		config  => shift,
		query   => shift,
		plugin  => undef,
		field   => undef,
		index   => undef,
		info    => undef,
		html    => undef,
		message => undef,
		update  => undef
	};
	bless $self, $class;

	if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
		$self->{info}->{script_url} = "$self->{config}->{site_url}$1";
	}

	if ($ENV{'HTTP_USER_AGENT'} =~ /(J-PHONE|KDDI-|UP\.Browser)/i) {
		$self->{info}->{method} = 'get';
	} else {
		$self->{info}->{method} = 'post';
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

	if ($self->{query}->{exec_regist}) {
		$self->{query}->{name} = Jcode->new($self->{query}->{name}, 'sjis')->utf8;
		$self->{query}->{mail} = Jcode->new($self->{query}->{mail}, 'sjis')->utf8;
		$self->{query}->{url}  = Jcode->new($self->{query}->{url},  'sjis')->utf8;
		$self->{query}->{subj} = Jcode->new($self->{query}->{subj}, 'sjis')->utf8;
		$self->{query}->{text} = Jcode->new($self->{query}->{text}, 'sjis')->utf8;
		$self->{query}->{pwd}  = Jcode->new($self->{query}->{pwd},  'sjis')->utf8;
	}
	if ($self->{query}->{mode} eq 'search') {
		$self->{query}->{word} = Jcode->new($self->{query}->{word}, 'sjis')->utf8;
	}

	if ($self->{query}->{mode} eq 'admin') {
		if ($self->{query}->{admin_pwd}) {
			eval "use webliberty::App::Admin qw(check_password get_user get_authority record_log check_access check regist edit del del_trackback);";
			if (!$self->check_password) {
				$self->error('パスワードが違います。');
			}

			if ($self->{query}->{work} eq 'new') {
				if ($self->{query}->{exec_regist}) {
					$self->check_access('mobile');
					$self->check;
					$self->regist;
					$self->output_admin_edit;
				} else {
					$self->output_admin_form;
				}
			} elsif ($self->{query}->{work} eq 'edit') {
				if ($self->{query}->{exec_regist}) {
					$self->check_access('mobile');
					$self->check;
					$self->edit;
					$self->output_admin_edit;
				} elsif ($self->{query}->{exec_del}) {
					$self->check_access('mobile');
					$self->del;
					$self->output_admin_edit;
				} elsif ($self->{query}->{edit}) {
					$self->output_admin_form;
				} else {
					$self->output_admin_edit;
				}
			} elsif ($self->{query}->{work} eq 'comment') {
				if ($self->{query}->{exec_regist}) {
					eval "use webliberty::App::Edit qw(edit);";
					$self->edit;
					$self->record_log($self->{message});
					$self->output_admin_comment;
				} elsif ($self->{query}->{exec_del}) {
					eval "use webliberty::App::Edit qw(del);";
					$self->del('mobile');
					$self->record_log($self->{message});
					$self->output_admin_comment;
				} elsif ($self->{query}->{edit}) {
					$self->output_form;
				} else {
					$self->output_admin_comment;
				}
			} elsif ($self->{query}->{work} eq 'trackback') {
				if ($self->{query}->{exec_del}) {
					$self->check_access('mobile');
					$self->del_trackback;
					$self->output_admin_trackback;
				} else {
					$self->output_admin_trackback;
				}
			} else {
				$self->error('不正なアクセスです。');
			}
		} else {
			$self->output_admin;
		}
	} elsif ($self->{query}->{mode} eq 'receive') {
		eval "use webliberty::App::Receive qw(receive);";
		eval "use webliberty::App::Admin qw(get_user set_user record_log check regist);";
		$self->receive;
		$self->output_complete;
	} elsif ($self->{query}->{mode} eq 'edit') {
		if ($self->{query}->{work}) {
			if ($self->{query}->{work} eq 'del') {
				eval "use webliberty::App::Edit qw(del);";
				$self->del('mobile');
				$self->output_complete;
			} elsif ($self->{query}->{work} eq 'edit') {
				eval "use webliberty::App::Comment qw(check);";
				eval "use webliberty::App::Edit qw(edit);";
				$self->edit;
				$self->output_complete;
			} else {
				$self->output_form;
			}
		} else {
			$self->output_edit;
		}
	} elsif ($self->{query}->{mode} eq 'comment') {
		if ($self->{query}->{exec_regist}) {
			eval "use webliberty::App::Comment qw(check regist);";
			$self->check('mobile');
			$self->regist;
			$self->output_complete;
		} else {
			$self->output_form;
		}
	} elsif ($self->{query}->{mode} eq 'search') {
		$self->output_search;
	} elsif ($self->{query}->{mode} eq 'comtnavi') {
		$self->output_comtnavi;
	} elsif ($self->{query}->{mode} eq 'tbnavi') {
		$self->output_tbnavi;
	} elsif ($self->{query}->{comment}) {
		$self->output_comment;
	} elsif ($self->{query}->{no} or $self->{query}->{id}) {
		$self->output_view;
	} else {
		$self->output_list;
	}

	my($html, $length) = $self->get_mobile_data($self->{html});

	print "Content-Type: text/html; charset=Shift_JIS\n";
	if ($ENV{'HTTP_USER_AGENT'} =~ /KDDI-|UP\.Browser/i) {
		print "Pragma: no-cache\n";
		print "Cache-Control: no-cache\n\n";
	} else {
		print "Content-Length: $length\n\n";
	}
	print $html;

	return;
}

### 管理者ページ
sub output_admin {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	my $default_user;
	if (!$self->{config}->{user_mode}) {
		open(FH, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
		$default_user = (split(/\t/, <FH>))[0];
		close(FH);
	}

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= "<hr>\n";
	$self->{html} .= "■管理者用\n";
	$self->{html} .= "<hr>\n";
	$self->{html} .= "管理者パスワードを入力してください。\n";
	$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
	if (!$self->{config}->{user_mode}) {
		$self->{html} .= "<input type=\"hidden\" name=\"admin_user\" value=\"$default_user\">\n";
	}
	$self->{html} .= "作業内容<br>\n";
	$self->{html} .= "<select name=\"work\">\n";
	$self->{html} .= "<option value=\"new\">新規投稿</option>\n";
	$self->{html} .= "<option value=\"edit\">記事編集</option>\n";
	$self->{html} .= "<option value=\"comment\">コメント管理</option>\n";
	$self->{html} .= "<option value=\"trackback\">トラックバック管理</option>\n";
	$self->{html} .= "</select><br>\n";
	if ($self->{config}->{user_mode}) {
		$self->{html} .= "ﾕｰｻﾞｰ<br>\n";
		$self->{html} .= "<input type=\"text\" name=\"admin_user\"><br>\n";
	}
	$self->{html} .= "ﾊﾟｽﾜｰﾄﾞ<br>\n";
	$self->{html} .= "<input type=\"text\" name=\"admin_pwd\"><br>\n";
	$self->{html} .= "<br>\n";
	$self->{html} .= "<input type=\"submit\" value=\"認証する\">\n";
	$self->{html} .= "</form>\n";
	$self->{html} .= "<hr>\n";
	$self->{html} .= "▲<a href=\"$self->{info}->{script_url}\">戻る</a>\n";
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 記事投稿フォーム
sub output_admin_form {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_form}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_USER   => $self->{query}->{admin_user},
		INFO_PWD    => $self->{query}->{admin_pwd},
		INFO_METHOD => $self->{info}->{method}
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

		%form = $diary_ins->diary_form('', '', $self->{config}->{default_stat}, 1, $self->{config}->{default_comt}, $self->{config}->{default_tb}, '', $date, '', '', '', '', '', '', '');

		$form_label = '新規投稿';
	}

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= $skin_ins->get_replace_data(
		'form',
		%form,
		FORM_LABEL => $form_label,
		FORM_WORK  => $self->{query}->{work},
		FORM_TBURL => $self->{query}->{tb_url},
		FORM_PING  => ' checked="checked"'
	);
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 記事編集フォーム
sub output_admin_edit {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{message}) {
		$self->{message} = '投稿記事を編集・削除する事ができます。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	my $init_ins = new webliberty::App::Init;
	my %label = %{$init_ins->get_label};

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= "<hr>\n";
	$self->{html} .= "■記事編集\n";
	$self->{html} .= "<hr>\n";
	$self->{html} .= "$self->{message}\n";
	$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"admin_user\" value=\"$self->{query}->{admin_user}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"admin_pwd\" value=\"$self->{query}->{admin_pwd}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"work\" value=\"edit\">\n";
	$self->{html} .= "<input type=\"submit\" name=\"exec_form\" value=\"編集する\">\n";
	$self->{html} .= "<input type=\"submit\" name=\"exec_del\" value=\"削除する\">\n";
	$self->{html} .= "<ul>\n";

	my($index_size, $index_date, $index_no);

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if ($index_size == $self->{query}->{page} * $self->{config}->{mobile_page_size}) {
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
					if ($i > $self->{config}->{mobile_page_size}) {
						$file_flag = 0;
						last;
					}

					my %diary_article = $diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, '', '', $icon, '', $host);

					$self->{html} .= "<li>\n";
					$self->{html} .= "<input type=\"radio\" name=\"edit\" value=\"$diary_article{ARTICLE_NO}\">編集\n";
					$self->{html} .= "<input type=\"checkbox\" name=\"del\" value=\"$diary_article{ARTICLE_NO}\">削除\n";
					$self->{html} .= "... No.$diary_article{ARTICLE_NO} $diary_article{ARTICLE_SUBJ}\n";
					$self->{html} .= "</li>\n";
				}
			}
			close(FH);
		}
	}

	$self->{html} .= "</ul>\n";
	$self->{html} .= "</form>\n";

	if ($self->{query}->{page} > 0) {
		$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_user\" value=\"$self->{query}->{admin_user}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_pwd\" value=\"$self->{query}->{admin_pwd}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"work\" value=\"edit\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"page\" value=\"" . ($self->{query}->{page} - 1) . "\">\n";
		$self->{html} .= "<input type=\"submit\" value=\"前のページ\">\n";
		$self->{html} .= "</form>\n";
	}
	if (int(($index_size - 1) / $self->{config}->{mobile_page_size}) > $self->{query}->{page}) {
		$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_user\" value=\"$self->{query}->{admin_user}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_pwd\" value=\"$self->{query}->{admin_pwd}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"work\" value=\"edit\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"page\" value=\"" . ($self->{query}->{page} + 1) . "\">\n";
		$self->{html} .= "<input type=\"submit\" value=\"次のページ\">\n";
		$self->{html} .= "</form>\n";
	}

	$self->{html} .= "<hr>\n";
	$self->{html} .= "▲<a href=\"$self->{info}->{script_url}\">戻る</a>\n";
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### コメント管理フォーム
sub output_admin_comment {
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
		$self->{message} = 'コメントを編集・削除する事ができます。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	my $init_ins = new webliberty::App::Init;
	my %label = %{$init_ins->get_label};

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= "<hr>\n";
	$self->{html} .= "■コメント管理\n";
	$self->{html} .= "<hr>\n";
	$self->{html} .= "$self->{message}\n";
	$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"admin_user\" value=\"$self->{query}->{admin_user}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"admin_pwd\" value=\"$self->{query}->{admin_pwd}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"work\" value=\"comment\">\n";
	$self->{html} .= "<input type=\"submit\" name=\"exec_form\" value=\"編集する\">\n";
	$self->{html} .= "<input type=\"submit\" name=\"exec_del\" value=\"削除する\">\n";
	$self->{html} .= "<ul>\n";

	my($index_size, $i);

	my $comt_start = $self->{config}->{mobile_page_size} * $self->{query}->{page};
	my $comt_end   = $comt_start + $self->{config}->{mobile_page_size};

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

		my %comment_article = $diary_ins->comment_article($no, $pno, $stat, $date, $name, $subj, $host);

		$self->{html} .= "<li>\n";
		$self->{html} .= "<input type=\"radio\" name=\"edit\" value=\"$comment_article{ARTICLE_NO}\">編集\n";
		$self->{html} .= "<input type=\"checkbox\" name=\"del\" value=\"$comment_article{ARTICLE_NO}\">削除\n";
		$self->{html} .= "... No.$comment_article{ARTICLE_NO} $comment_article{ARTICLE_NAME}\n";
		$self->{html} .= "</li>\n";
	}
	close(FH);

	$self->{html} .= "</ul>\n";
	$self->{html} .= "</form>\n";

	if ($self->{query}->{page} > 0) {
		$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_user\" value=\"$self->{query}->{admin_user}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_pwd\" value=\"$self->{query}->{admin_pwd}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"work\" value=\"comment\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"page\" value=\"" . ($self->{query}->{page} - 1) . "\">\n";
		$self->{html} .= "<input type=\"submit\" value=\"前のページ\">\n";
		$self->{html} .= "</form>\n";
	}
	if (int(($index_size - 1) / $self->{config}->{mobile_page_size}) > $self->{query}->{page}) {
		$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_user\" value=\"$self->{query}->{admin_user}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_pwd\" value=\"$self->{query}->{admin_pwd}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"work\" value=\"comment\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"page\" value=\"" . ($self->{query}->{page} + 1) . "\">\n";
		$self->{html} .= "<input type=\"submit\" value=\"次のページ\">\n";
		$self->{html} .= "</form>\n";
	}

	$self->{html} .= "<hr>\n";
	$self->{html} .= "▲<a href=\"$self->{info}->{script_url}\">戻る</a>\n";
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### トラックバック管理フォーム
sub output_admin_trackback {
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
		$self->{message} = 'トラックバックを削除する事ができます。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	my $init_ins = new webliberty::App::Init;
	my %label = %{$init_ins->get_label};

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= "<hr>\n";
	$self->{html} .= "■トラックバック管理\n";
	$self->{html} .= "<hr>\n";
	$self->{html} .= "$self->{message}\n";
	$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"admin_user\" value=\"$self->{query}->{admin_user}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"admin_pwd\" value=\"$self->{query}->{admin_pwd}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"work\" value=\"trackback\">\n";
	$self->{html} .= "<input type=\"submit\" name=\"exec_del\" value=\"削除する\">\n";
	$self->{html} .= "<ul>\n";

	my($index_size, $i);

	my $tb_start = $self->{config}->{mobile_page_size} * $self->{query}->{page};
	my $tb_end   = $tb_start + $self->{config}->{mobile_page_size};

	open(FH, $self->{init}->{data_tb_index}) or $self->error("Read Error : $self->{init}->{data_tb_index}");
	while (<FH>) {
		$index_size++;
	}
	seek(FH, 0, 0);
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $subj, $host) = split(/\t/);

		$i++;
		if ($i <= $tb_start) {
			next;
		} elsif ($i > $tb_end) {
			last;
		}

		my %trackback_article = $diary_ins->trackback_article($no, $pno, $stat, $date, $name, $subj, $host);

		$self->{html} .= "<li>\n";
		$self->{html} .= "<input type=\"checkbox\" name=\"del\" value=\"$trackback_article{TRACKBACK_NO}\">削除\n";
		$self->{html} .= "... No.$trackback_article{TRACKBACK_NO} $trackback_article{TRACKBACK_BLOG}\n";
		$self->{html} .= "</li>\n";
	}
	close(FH);

	$self->{html} .= "</ul>\n";
	$self->{html} .= "</form>\n";

	if ($self->{query}->{page} > 0) {
		$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_user\" value=\"$self->{query}->{admin_user}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_pwd\" value=\"$self->{query}->{admin_pwd}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"work\" value=\"trackback\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"page\" value=\"" . ($self->{query}->{page} - 1) . "\">\n";
		$self->{html} .= "<input type=\"submit\" value=\"前のページ\">\n";
		$self->{html} .= "</form>\n";
	}
	if (int(($index_size - 1) / $self->{config}->{mobile_page_size}) > $self->{query}->{page}) {
		$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_user\" value=\"$self->{query}->{admin_user}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"admin_pwd\" value=\"$self->{query}->{admin_pwd}\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"work\" value=\"trackback\">\n";
		$self->{html} .= "<input type=\"hidden\" name=\"page\" value=\"" . ($self->{query}->{page} + 1) . "\">\n";
		$self->{html} .= "<input type=\"submit\" value=\"次のページ\">\n";
		$self->{html} .= "</form>\n";
	}

	$self->{html} .= "<hr>\n";
	$self->{html} .= "▲<a href=\"$self->{info}->{script_url}\">戻る</a>\n";
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### コメント編集フォーム
sub output_edit {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{message}) {
		$self->{message} = '投稿記事を削除する事ができます。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	my $init_ins = new webliberty::App::Init;
	my %label = %{$init_ins->get_label};

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= "<hr>\n";
	$self->{html} .= "■記事編集\n";
	$self->{html} .= "<hr>\n";
	$self->{html} .= "$self->{message}\n";
	$self->{html} .= "<form action=\"$self->{info}->{script_url}\" method=\"$self->{info}->{method}\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"mode\" value=\"edit\">\n";
	$self->{html} .= "<input type=\"hidden\" name=\"no\" value=\"$self->{query}->{no}\">\n";
	$self->{html} .= "$label{'mobile_pwd'}<br>\n";
	$self->{html} .= "<input type=\"text\" name=\"pwd\" size=\"6\"><br>\n";
	$self->{html} .= "作業内容<br>\n";
	$self->{html} .= "<select name=\"work\">\n";
	$self->{html} .= "<option value=\"form\">記事編集</option>\n";
	$self->{html} .= "<option value=\"del\">記事削除</option>\n";
	$self->{html} .= "</select><br>\n";
	$self->{html} .= "<br>\n";
	$self->{html} .= "<input type=\"submit\" name=\"exec_del\" value=\"削除する\">\n";
	$self->{html} .= "</form>\n";
	$self->{html} .= "<hr>\n";
	$self->{html} .= "▲<a href=\"$self->{info}->{script_url}\">戻る</a>\n";
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### コメント投稿フォーム
sub output_form {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if ($self->{query}->{mode} eq 'admin') {
		$self->{query}->{no} = $self->{query}->{edit};
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_comment}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_USER   => $self->{query}->{admin_user},
		INFO_PWD    => $self->{query}->{admin_pwd},
		INFO_METHOD => $self->{info}->{method}
	);

	my(%form, $form_label, $form_mode, $form_work, $diary_subj, $form_pwd_start, $form_pwd_end);

	if ($self->{query}->{mode} eq 'admin' or $self->{query}->{mode} eq 'edit') {
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

		my $flag;

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

		if ($self->{query}->{mode} eq 'admin') {
			$form_label = 'コメント編集';
			$form_mode  = 'admin';
			$form_work  = 'comment';

			$form_pwd_start = '<!--';
			$form_pwd_end   = '-->';
		} else {
			$form_label = 'コメント編集';
			$form_mode  = 'edit';
			$form_work  = 'edit';
		}
	} else {
		my($index_date, $index_no, $flag);

		open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
		while (<FH>) {
			chomp;
			my($date, $no, $id, $stat, $field, $name) = split(/\t/);

			if ($self->{query}->{no} == $no) {
				$index_date = $date;
				$index_no   = $no;

				$flag = 1;

				last;
			}
		}
		close(FH);

		if (!$flag) {
			$self->error('指定された記事は存在しません。');
		}

		my $data_file;
		if ($index_date =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
			$data_file = "$1$2\.$self->{init}->{data_ext}";
		}

		open(FH, "$self->{init}->{data_diary_dir}$data_file") or $self->error("Read Error : $self->{init}->{data_diary_dir}$data_file");
		while (<FH>) {
			chomp;
			my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

			if ($index_no == $no) {
				$diary_subj = $subj;

				last;
			}
		}
		close(FH);

		%form = $diary_ins->comment_form($self->{query}->{no}, $self->{query}->{no}, '', '', '', '', '', "Re:$diary_subj", '', '', '', '', '', '', ''),

		$form_label = 'コメント投稿';
		$form_mode  = 'comment';
		$form_work  = 'regist';
	}

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= $skin_ins->get_replace_data(
		'form',
		%form,
		FORM_LABEL     => $form_label,
		FORM_MODE      => $form_mode,
		FORM_WORK      => $form_work,
		FORM_PWD_START => $form_pwd_start,
		FORM_PWD_END   => $form_pwd_end
	);
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### コメント表示
sub output_comment {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_comment}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= $skin_ins->get_data('comment_head');

	if (-e "$self->{init}->{data_comt_dir}$self->{query}->{no}\.$self->{init}->{data_ext}") {
		open(FH, "$self->{init}->{data_comt_dir}$self->{query}->{no}\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}$self->{query}->{no}\.$self->{init}->{data_ext}");
		while (<FH>) {
			chomp;
			my($no, $pno, $stat, $field, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

			if ($self->{query}->{comment} == $no) {
				$self->{html} .= $skin_ins->get_replace_data(
					'comment',
					$diary_ins->comment_article($no, $pno, $stat, $field, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host)
				);

				last;
			}
		}
		close(FH);
	}

	$self->{html} .= $skin_ins->get_data('comment_foot');
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 記事表示
sub output_view {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my($index_date, $index_no);

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if ($stat and (($self->{query}->{no} and $self->{query}->{no} == $no) or ($self->{query}->{id} and $self->{query}->{id} eq $id))) {
			$index_date = $date;
			$index_no   = $no;

			last;
		}
	}
	close(FH);

	my $start_file;
	if ($index_date =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
		$start_file = "$self->{init}->{data_diary_dir}$1$2\.$self->{init}->{data_ext}";
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_view}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= $skin_ins->get_data('list_head');

	opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
	my @dir = sort { $b <=> $a } readdir(DIR);
	closedir(DIR);

	my($dir_flag, $file_flag, $comt_flag, $i);

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

				if (!$stat) {
					next;
				}

				if ($no == $index_no) {
					$file_flag = 1;
				}
				if ($file_flag) {
					if ($self->{query}->{field} and $self->{field}->{$self->{query}->{field}} ne $field) {
						next;
					}
					if (!$self->{query}->{field} or $self->{field}->{$self->{query}->{field}} eq $field) {
						$i++;
					}

					$self->{html} .= $skin_ins->get_replace_data(
						'list',
						$diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host)
					);

					$dir_flag  = 0;
					$file_flag = 0;
					$comt_flag = $comt;

					last;
				}
			}
			close(FH);
		}
	}

	if (-e "$self->{init}->{data_tb_dir}$index_no\.$self->{init}->{data_ext}") {
		$self->{html} .= $skin_ins->get_data('tbnavi_head');

		open(FH, "$self->{init}->{data_tb_dir}$index_no\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_tb_dir}$index_no\.$self->{init}->{data_ext}");
		while (<FH>) {
			chomp;
			my($no, $pno, $stat, $date, $blog, $title, $url) = split(/\t/);

			$self->{html} .= $skin_ins->get_replace_data(
				'tbnavi',
				$diary_ins->trackback_article($no, $pno, $stat, $date, $blog, $title, $url, '')
			);
		}
		close(FH);

		$self->{html} .= $skin_ins->get_data('tbnavi_foot');
	}

	if (-e "$self->{init}->{data_comt_dir}$index_no\.$self->{init}->{data_ext}") {
		$self->{html} .= $skin_ins->get_data('comtnavi_head');

		open(FH, "$self->{init}->{data_comt_dir}$index_no\.$self->{init}->{data_ext}") or $self->error("Read Error : $self->{init}->{data_comt_dir}$index_no\.$self->{init}->{data_ext}");
		while (<FH>) {
			chomp;
			my($no, $pno, $stat, $field, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

			$self->{html} .= $skin_ins->get_replace_data(
				'comtnavi',
				$diary_ins->comment_article($no, $pno, $stat, $field, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host)
			);
		}
		close(FH);

		$self->{html} .= $skin_ins->get_data('comtnavi_foot');
	}

	my($form_start, $form_end);
	if (!$comt_flag) {
		$form_start = '<!--';
		$form_end   = '-->';
	}

	$self->{html} .= $skin_ins->get_replace_data(
		'list_foot',
		LIST_FORM_START => $form_start,
		LIST_FORM_END   => $form_end,
		ARTICLE_NO      => $index_no
	);

	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### タイトル一覧
sub output_list {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $i;

	open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
	while (<FH>) {
		chomp;

		$self->{field}->{++$i} = $_;

		if ($self->{query}->{field} =~ /[^\d]/) {
			$_ =~ s/<>/&lt;&gt;/g;

			if ($self->{query}->{field} eq $_) {
				$self->{query}->{field} = $i;
			}
		}
	}
	close(FH);

	my($index_size, $index_date, $index_no);

	open(FH, $self->{init}->{data_diary_index}) or $self->error("Read Error : $self->{init}->{data_diary_index}");
	while (<FH>) {
		chomp;
		my($date, $no, $id, $stat, $field, $name) = split(/\t/);

		if ($stat and $index_size == $self->{query}->{page} * $self->{config}->{mobile_page_size}) {
			$index_date = $date;
			$index_no   = $no;
		}

		if ($stat and (!$self->{query}->{field} or $field =~ /^$self->{field}->{$self->{query}->{field}}(<|$)/)) {
			$index_size++;
		}

		if ($field =~ /<>/) {
			$self->{index}->{field}->{$field}++;
			$self->{index}->{field}->{(split(/<>/, $field))[0]}++;
		} else {
			$self->{index}->{field}->{$field}++;
		}
	}
	close(FH);

	my $start_file;
	if ($index_date =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
		$start_file = "$self->{init}->{data_diary_dir}$1$2\.$self->{init}->{data_ext}";
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_list}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	my $list_title;
	if ($self->{field}->{$self->{query}->{field}}) {
		$list_title = $self->{field}->{$self->{query}->{field}};
		$list_title =~ s/<>/::/g;
	} else {
		$list_title = 'タイトル一覧';
	}

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= $skin_ins->get_replace_data(
		'list_head',
		LIST_TITLE => $list_title
	);

	opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
	my @dir = sort { $b <=> $a } readdir(DIR);
	closedir(DIR);

	my($dir_flag, $file_flag, $comt_flag, $i);

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

				if (!$stat) {
					next;
				}

				if ($no == $index_no) {
					$file_flag = 1;
				}
				if ($file_flag) {
					if ($self->{query}->{field} and $field !~ /^$self->{field}->{$self->{query}->{field}}(<|$)/) {
						next;
					}
					$i++;

					$self->{html} .= $skin_ins->get_replace_data(
						'list',
						$diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host)
					);

					if ($i >= $self->{config}->{mobile_page_size}) {
						$dir_flag  = 0;
						$file_flag = 0;
						$comt_flag = $comt;
						last;
					}
				}
			}
			close(FH);
		}
	}

	my($field_link, $prev_start, $prev_end, $next_start, $next_end);

	if ($self->{query}->{field}) {
		$field_link = '&amp;field=' . $self->{query}->{field};
	}

	if ($self->{query}->{page} > 0) {
		$prev_start = "<a href=\"$self->{info}->{script_url}?page=" . ($self->{query}->{page} - 1) . "$field_link\">";
		$prev_end   = "</a>";
	} else {
		$prev_start = '<!--';
		$prev_end   = '-->';
	}
	if (int(($index_size - 1) / $self->{config}->{mobile_page_size}) > $self->{query}->{page}) {
		$next_start = "<a href=\"$self->{info}->{script_url}?page=" . ($self->{query}->{page} + 1) . "$field_link\">";
		$next_end   = "</a>";
	} else {
		$next_start = '<!--';
		$next_end   = '-->';
	}

	$self->{html} .= $skin_ins->get_replace_data(
		'page',
		PAGE_PREV_START => $prev_start,
		PAGE_PREV_END   => $prev_end,
		PAGE_NEXT_START => $next_start,
		PAGE_NEXT_END   => $next_end
	);

	if ($self->{config}->{show_field}) {
		$i = 0;

		$self->{html} .= $skin_ins->get_data('field_head');

		open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
		while (<FH>) {
			chomp;

			my $fcode_ins = new webliberty::Encoder($_);

			if ($_ =~ /^(.+)<>(.+)$/) {
				$self->{html} .= $skin_ins->get_replace_data(
					'child',
					FIELD_NAME   => $2,
					FIELD_PARENT => $1,
					FIELD_NO     => ++$i,
					FIELD_CODE   => $fcode_ins->url_encode,
					FIELD_SIZE   => $self->{index}->{field}->{$_} || 0
				);
			} else {
				$self->{html} .= $skin_ins->get_replace_data(
					'field',
					FIELD_NAME => $_,
					FIELD_NO   => ++$i,
					FIELD_CODE => $fcode_ins->url_encode,
					FIELD_SIZE   => $self->{index}->{field}->{$_} || 0
				);
			}
		}
		close(FH);

		$self->{html} .= $skin_ins->get_data('field_foot');
	}

	my($cmtlist_start, $cmtlist_end, $tblist_start, $tblist_end);
	if (!$self->{config}->{mobile_cmtlist_size}) {
		$cmtlist_start = '<!--';
		$cmtlist_end   = '-->';
	}
	if (!$self->{config}->{mobile_tblist_size}) {
		$tblist_start = '<!--';
		$tblist_end   = '-->';
	}

	$self->{html} .= $skin_ins->get_replace_data(
		'list_foot',
		LIST_COMTNAVI_START => $cmtlist_start,
		LIST_COMTNAVI_END   => $cmtlist_end,
		LIST_TBNAVI_START   => $tblist_start,
		LIST_TBNAVI_END     => $tblist_end
	);
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### ログ検索
sub output_search {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_search}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= $skin_ins->get_replace_data(
		'search_head',
		FORM_WORD => $self->{query}->{word}
	);

	if ($self->{query}->{word}) {
		my $words = $self->{query}->{word};
		$words =~ s/　/ /g;
		my @words = split(/\s+/, $words);

		opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
		my @dir = sort { $b <=> $a } readdir(DIR);
		closedir(DIR);

		my $page_start = $self->{config}->{mobile_page_size} * $self->{query}->{page};
		my $page_end   = $page_start + $self->{config}->{mobile_page_size};

		my $flag = 1;
		my $i;

		foreach my $entry (@dir) {
			if ($entry !~ /^\d\d\d\d\d\d\.$self->{init}->{data_ext}$/) {
				next;
			}
			if ($flag) {
				open(FH, "$self->{init}->{data_diary_dir}$entry") or $self->error("Read Error : $self->{init}->{data_diary_dir}$entry");
				while (<FH>) {
					chomp;
					my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

					my $show_flag;

					foreach my $word (@words) {
						$word = lc($word);
						my $string = lc("$no\t$id\t$date\t$name\t$subj\t$text");

						if (index($string, $word) >= 0) {
							$show_flag = 1;
						} else {
							$show_flag = 0;

							last;
						}
					}

					if ($show_flag) {
						$i++;
						if ($i <= $page_start) {
							next;
						} elsif ($i > $page_end) {
							next;
						}

						$self->{html} .= $skin_ins->get_replace_data(
							'search',
							$diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host)
						);
					}
				}
				close(FH);
			}
		}

		my($prev_start, $prev_end, $next_start, $next_end);

		my $word_ins    = new Jcode($self->{query}->{word}, 'utf8');
		my $encoder_ins = new webliberty::Encoder($word_ins->sjis);
		$encoder_ins->url_encode;

		if ($self->{query}->{page} > 0) {
			$prev_start = "<a href=\"$self->{info}->{script_url}?mode=search&amp;word=" . $encoder_ins->get_string . "&amp;page=" . ($self->{query}->{page} - 1) . "\">";
			$prev_end   = "</a>";
		} else {
			$prev_start = '<!--';
			$prev_end   = '-->';
		}
		if (int(($i - 1) / $self->{config}->{mobile_page_size}) > $self->{query}->{page}) {
			$next_start = "<a href=\"$self->{info}->{script_url}?mode=search&amp;word=" . $encoder_ins->get_string . "&amp;page=" . ($self->{query}->{page} + 1) . "\">";
			$next_end   = "</a>";
		} else {
			$next_start = '<!--';
			$next_end   = '-->';
		}

		$self->{html} .= $skin_ins->get_replace_data(
			'page',
			PAGE_PREV_START => $prev_start,
			PAGE_PREV_END   => $prev_end,
			PAGE_NEXT_START => $next_start,
			PAGE_NEXT_END   => $next_end
		);
	}

	$self->{html} .= $skin_ins->get_data('search_foot');
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### コメント一覧
sub output_comtnavi {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_comtnavi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= $skin_ins->get_data('comtnavi_head');

	my $i;

	open(FH, $self->{init}->{data_comt_index}) or $self->error("Read Error : $self->{init}->{data_comt_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $name, $subj, $host) = split(/\t/);

		$i++;
		if ($i > $self->{config}->{mobile_cmtlist_size}) {
			last;
		}

		$self->{html} .= $skin_ins->get_replace_data(
			'comtnavi',
			$diary_ins->comment_article($no, $pno, $stat, $date, $name, '', '', $subj, '', '', '', '', '', '', $host)
		);
	}
	close(FH);

	$self->{html} .= $skin_ins->get_data('comtnavi_foot');
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### トラックバック一覧
sub output_tbnavi {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_tbnavi}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= $skin_ins->get_data('tbnavi_head');

	my $i;

	open(FH, $self->{init}->{data_tb_index}) or $self->error("Read Error : $self->{init}->{data_tb_index}");
	while (<FH>) {
		chomp;
		my($no, $pno, $stat, $date, $blog, $title, $url) = split(/\t/);

		$i++;
		if ($i > $self->{config}->{mobile_tblist_size}) {
			last;
		}

		$self->{html} .= $skin_ins->get_replace_data(
			'tbnavi',
			$diary_ins->trackback_article($no, $pno, $stat, $date, $blog, $title, $url, '')
		);
	}
	close(FH);

	$self->{html} .= $skin_ins->get_data('tbnavi_foot');
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 処理完了画面
sub output_complete {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{message}) {
		$self->{message} = '処理が完了しました。';
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= "<hr>\n";
	$self->{html} .= "■処理完了\n";
	$self->{html} .= "<hr>\n";
	$self->{html} .= "$self->{message}<br>\n";
	$self->{html} .= "<hr>\n";
	$self->{html} .= "▲<a href=\"$self->{info}->{script_url}\">戻る</a>\n";
	$self->{html} .= $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### エラー
sub error {
	my $self    = shift;
	my $message = shift;

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_header}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_mobile_footer}");

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
	$diary_ins->set_agent('mobile');

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	$self->{html} = '';

	$self->{html} .= $skin_ins->get_data('header');
	$self->{html} .= "<hr>ｴﾗｰ:<br>$message\n";
	$self->{html} .= $skin_ins->get_data('footer');

	my($html, $length) = $self->get_mobile_data($self->{html});

	print "Content-Type: text/html; charset=Shift_JIS\n";
	if ($ENV{'HTTP_USER_AGENT'} =~ /KDDI-|UP\.Browser/i) {
		print "Pragma: no-cache\n";
		print "Cache-Control: no-cache\n\n";
	} else {
		print "Content-Length: $length\n\n";
	}
	print $html;

	exit;
}

### 携帯用データ作成
sub get_mobile_data {
	my $self = shift;
	my $data = shift;

	$data =~ s/\xEF\xBD\x9E/\xE3\x80\x9C/g;
	$data =~ s/\xEF\xBC\x8D/\a/g;
	$data = Jcode->new($data, 'utf8')->sjis;
	$data =~ s/\a/\x81\x7C/g;

	return($data, length($data) + ($data =~ s/\n/\n/g));
}

1;
