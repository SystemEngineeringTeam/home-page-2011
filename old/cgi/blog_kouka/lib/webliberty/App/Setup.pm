#webliberty::App::Setup.pm (2007/06/17)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Setup;

use strict;
use Jcode;
use base qw(webliberty::Basis);
use webliberty::String;
use webliberty::App::Init;
use webliberty::App::Admin;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init    => shift,
		config  => shift,
		query   => shift,
		message => undef
	};
	bless $self, $class;

	return $self;
}

### メイン処理
sub run {
	my $self = shift;

	if (-e $self->{init}->{data_config} and $self->{query}->{mode} eq 'setup') {
		$self->versionup;
		$self->update;
	} elsif ($self->{query}->{exec_setup}) {
		$self->setup;
	}
	$self->output;

	return;
}

### バージョンアップ
sub versionup {
	my $self = shift;

	if (!-e $self->{init}->{data_user}) {
		my $pwd_ins = new webliberty::String('1234');

		open(FH, ">$self->{init}->{data_user}") or $self->error("Write Error : $self->{init}->{data_user}");
		print FH "admin\t" . $pwd_ins->create_password . "\troot\n";
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_user}) or $self->error("Chmod Error : $self->{init}->{data_user}");
			} else {
				chmod(0666, $self->{init}->{data_user}) or $self->error("Chmod Error : $self->{init}->{data_user}");
			}
		}

		push(@{$self->{message}}, '管理者パスワードを<em>1234</em>に再設定しました。');
	}

	if (!-e $self->{init}->{data_profile}) {
		open(FH, ">$self->{init}->{data_profile}") or $self->error("Write Error : $self->{init}->{data_profile}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_profile}) or $self->error("Chmod Error : $self->{init}->{data_profile}");
			} else {
				chmod(0666, $self->{init}->{data_profile}) or $self->error("Chmod Error : $self->{init}->{data_profile}");
			}
		}

		push(@{$self->{message}}, 'プロフィール情報保存ファイルを作成しました。');
	}

	if (!-e $self->{init}->{data_record}) {
		open(FH, ">$self->{init}->{data_record}") or $self->error("Write Error : $self->{init}->{data_record}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_record}) or $self->error("Chmod Error : $self->{init}->{data_record}");
			} else {
				chmod(0666, $self->{init}->{data_record}) or $self->error("Chmod Error : $self->{init}->{data_record}");
			}
		}

		push(@{$self->{message}}, '管理機能操作履歴保存ファイルを作成しました。');
	}

	if (!-e $self->{init}->{data_field}) {
		open(FH, ">$self->{init}->{data_field}") or $self->error("Write Error : $self->{init}->{data_field}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_field}) or $self->error("Chmod Error : $self->{init}->{data_field}");
			} else {
				chmod(0666, $self->{init}->{data_field}) or $self->error("Chmod Error : $self->{init}->{data_field}");
			}
		}

		push(@{$self->{message}}, '分類情報保存ファイルを作成しました。');
	}

	if (!-e $self->{init}->{data_icon_dir}) {
		if ($self->{init}->{suexec_mode}) {
			mkdir($self->{init}->{data_icon_dir}, 0705);
		} else {
			mkdir($self->{init}->{data_icon_dir}, 0777);
		}

		push(@{$self->{message}}, 'アイコン保存ディレクトリを作成しました。');
	}
	if (!-e $self->{init}->{data_icon}) {
		open(FH, ">$self->{init}->{data_icon}") or $self->error("Write Error : $self->{init}->{data_icon}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_icon}) or $self->error("Chmod Error : $self->{init}->{data_icon}");
			} else {
				chmod(0666, $self->{init}->{data_icon}) or $self->error("Chmod Error : $self->{init}->{data_icon}");
			}
		}

		push(@{$self->{message}}, 'アイコン保存ファイルを作成しました。');
	}

	if (!-e $self->{init}->{data_top}) {
		open(FH, ">$self->{init}->{data_top}") or $self->error("Write Error : $self->{init}->{data_top}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_top}) or $self->error("Chmod Error : $self->{init}->{data_top}");
			} else {
				chmod(0666, $self->{init}->{data_top}) or $self->error("Chmod Error : $self->{init}->{data_top}");
			}
		}

		push(@{$self->{message}}, 'インデックスページ情報保存ファイルを作成しました。');
	}

	if (!-e $self->{init}->{data_menu}) {
		open(FH, ">$self->{init}->{data_menu}") or $self->error("Write Error : $self->{init}->{data_menu}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_menu}) or $self->error("Chmod Error : $self->{init}->{data_menu}");
			} else {
				chmod(0666, $self->{init}->{data_menu}) or $self->error("Chmod Error : $self->{init}->{data_menu}");
			}
		}

		push(@{$self->{message}}, 'コンテンツ情報保存ファイルを作成しました。');
	}

	if (!-e $self->{init}->{data_link}) {
		open(FH, ">$self->{init}->{data_link}") or $self->error("Write Error : $self->{init}->{data_link}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_link}) or $self->error("Chmod Error : $self->{init}->{data_link}");
			} else {
				chmod(0666, $self->{init}->{data_link}) or $self->error("Chmod Error : $self->{init}->{data_link}");
			}
		}

		push(@{$self->{message}}, 'リンク集情報保存ファイルを作成しました。');
	}

	if (!-e $self->{init}->{data_upfile_dir}) {
		if ($self->{init}->{suexec_mode}) {
			mkdir($self->{init}->{data_upfile_dir}, 0705);
		} else {
			mkdir($self->{init}->{data_upfile_dir}, 0777);
		}

		push(@{$self->{message}}, 'アップロードファイル保存ディレクトリを作成しました。');
	}

	if (!-e $self->{init}->{data_image_dir}) {
		if ($self->{init}->{suexec_mode}) {
			mkdir($self->{init}->{data_image_dir}, 0705);
		} else {
			mkdir($self->{init}->{data_image_dir}, 0777);
		}

		push(@{$self->{message}}, 'イメージ画像保存ディレクトリを作成しました。');
	}

	if (!-e $self->{init}->{data_thumbnail_dir}) {
		if ($self->{init}->{suexec_mode}) {
			mkdir($self->{init}->{data_thumbnail_dir}, 0705);
		} else {
			mkdir($self->{init}->{data_thumbnail_dir}, 0777);
		}

		push(@{$self->{message}}, 'サムネイル画像保存ディレクトリを作成しました。');
	}

	if (!-e $self->{init}->{paint_dir}) {
		if ($self->{init}->{suexec_mode}) {
			mkdir($self->{init}->{paint_dir}, 0705);
		} else {
			mkdir($self->{init}->{paint_dir}, 0777);
		}

		push(@{$self->{message}}, 'イラスト保存ディレクトリを作成しました。');
	}
	if (!-e $self->{init}->{pch_dir}) {
		if ($self->{init}->{suexec_mode}) {
			mkdir($self->{init}->{pch_dir}, 0705);
		} else {
			mkdir($self->{init}->{pch_dir}, 0777);
		}

		push(@{$self->{message}}, 'PCH保存ディレクトリを作成しました。');
	}

	if (!-e $self->{init}->{html_file}) {
		open(FH, ">$self->{init}->{html_file}") or $self->error("Write Error : $self->{init}->{html_file}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0604, $self->{init}->{html_file}) or $self->error("Chmod Error : $self->{init}->{html_file}");
			} else {
				chmod(0666, $self->{init}->{html_file}) or $self->error("Chmod Error : $self->{init}->{html_file}");
			}
		}

		push(@{$self->{message}}, '書き出し用HTMLファイルを作成しました。');
	}
	if (!-e $self->{init}->{archive_dir}) {
		if ($self->{init}->{suexec_mode}) {
			mkdir($self->{init}->{archive_dir}, 0705);
		} else {
			mkdir($self->{init}->{archive_dir}, 0777);
		}

		push(@{$self->{message}}, 'アーカイブ保存ディレクトリを作成しました。');
	}

	if (!-e $self->{init}->{data_diary_dir}) {
		if ($self->{init}->{suexec_mode}) {
			mkdir($self->{init}->{data_diary_dir}, 0700);
		} else {
			mkdir($self->{init}->{data_diary_dir}, 0777);
		}

		push(@{$self->{message}}, '記事保存ディレクトリを作成しました。');
	}
	if (!-e $self->{init}->{data_diary_index}) {
		open(FH, ">$self->{init}->{data_diary_index}") or $self->error("Write Error : $self->{init}->{data_diary_index}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_diary_index}) or $self->error("Chmod Error : $self->{init}->{data_diary_index}");
			} else {
				chmod(0666, $self->{init}->{data_diary_index}) or $self->error("Chmod Error : $self->{init}->{data_diary_index}");
			}
		}

		push(@{$self->{message}}, '記事用インデックス保存ファイルを作成しました。');
	}

	if (!-e $self->{init}->{data_comt_dir}) {
		if ($self->{init}->{suexec_mode}) {
			mkdir($self->{init}->{data_comt_dir}, 0700);
		} else {
			mkdir($self->{init}->{data_comt_dir}, 0777);
		}

		push(@{$self->{message}}, 'コメント保存ディレクトリを作成しました。');
	}
	if (!-e $self->{init}->{data_comt_index}) {
		open(FH, ">$self->{init}->{data_comt_index}") or $self->error("Write Error : $self->{init}->{data_comt_index}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_comt_index}) or $self->error("Chmod Error : $self->{init}->{data_comt_index}");
			} else {
				chmod(0666, $self->{init}->{data_comt_index}) or $self->error("Chmod Error : $self->{init}->{data_comt_index}");
			}
		}

		push(@{$self->{message}}, 'コメント用インデックス保存ファイルを作成しました。');
	}

	if (!-e $self->{init}->{data_tb_dir}) {
		if ($self->{init}->{suexec_mode}) {
			mkdir($self->{init}->{data_tb_dir}, 0700);
		} else {
			mkdir($self->{init}->{data_tb_dir}, 0777);
		}

		push(@{$self->{message}}, 'トラックバック保存ディレクトリを作成しました。');
	}
	if (!-e $self->{init}->{data_tb_index}) {
		open(FH, ">$self->{init}->{data_tb_index}") or $self->error("Write Error : $self->{init}->{data_tb_index}");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, $self->{init}->{data_tb_index}) or $self->error("Chmod Error : $self->{init}->{data_tb_index}");
			} else {
				chmod(0666, $self->{init}->{data_tb_index}) or $self->error("Chmod Error : $self->{init}->{data_tb_index}");
			}
		}

		push(@{$self->{message}}, 'トラックバック用インデックス保存ファイルを作成しました。');
	}

	return;
}

### アップデート
sub update {
	my $self = shift;

	my %config;

	open(FH, $self->{init}->{data_config}) or $self->error("Read Error : $self->{init}->{data_config}");
	while (<FH>) {
		chomp;
		my($key, $value) = split(/=/, $_, 2);

		$config{$key} = $value;
	}
	close(FH);

	my $flag;

	my $init_ins = new webliberty::App::Init;
	my %default = %{$init_ins->get_config};

	foreach (keys %default) {
		if (!exists($config{$_})) {
			$config{$_} = $default{$_};
			$flag = 1;
		}
	}

	if ($flag) {
		my $new_data;

		foreach (keys %config) {
			$new_data .= "$_=$config{$_}\n";
		}

		open(FH, ">$self->{init}->{data_config}") or $self->error("Write Error : $self->{init}{data_config}");
		print FH $new_data;
		close(FH);

		push(@{$self->{message}}, '設定項目を最新の状態に更新しました。');
	}

	my $app_ins = new webliberty::App::Admin($self->{init}, $self->{config}, $self->{query});
	if ($app_ins->check_password) {
		my @index;

		opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
		my @dir = sort { $b <=> $a } readdir(DIR);
		closedir(DIR);

		foreach my $entry (@dir) {
			if ($entry !~ /^\d\d\d\d\d\d\.$self->{init}->{data_ext}$/) {
				next;
			}

			open(FH, "$self->{init}->{data_diary_dir}$entry") or $self->error("Read Error : $self->{init}->{data_diary_dir}$entry");
			while (<FH>) {
				chomp;
				my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

				push(@index, "$date\t$no\t$id\t$stat\t$field\t$name\n");
			}
		}

		@index = sort { $b <=> $a } @index;

		open(FH, ">$self->{init}->{data_diary_index}") or $self->error("Write Error : $self->{init}->{data_diary_index}");
		print FH @index;
		close(FH);

		push(@{$self->{message}}, '記事用インデックス保存ファイルを更新しました。');

		my @index;

		opendir(DIR, $self->{init}->{data_comt_dir}) or $self->error("Read Error : $self->{init}->{data_comt_dir}");
		my @dir = sort { $b <=> $a } readdir(DIR);
		closedir(DIR);

		foreach my $entry (@dir) {
			if ($entry !~ /^\d+\.$self->{init}->{data_ext}$/) {
				next;
			}

			open(FH, "$self->{init}->{data_comt_dir}$entry") or $self->error("Read Error : $self->{init}->{data_comt_dir}$entry");
			while (<FH>) {
				chomp;
				my($no, $pno, $stat, $date, $name, $mail, $url, $subj, $text, $color, $icon, $file, $rank, $pwd, $host) = split(/\t/);

				push(@index, "$no\t$pno\t$stat\t$date\t$name\t$subj\t$host\n");
			}
		}

		@index = sort { $b <=> $a } @index;

		open(FH, ">$self->{init}->{data_comt_index}") or $self->error("Write Error : $self->{init}->{data_comt_index}");
		print FH @index;
		close(FH);

		push(@{$self->{message}}, 'コメント用インデックス保存ファイルを更新しました。');

		my @index;

		opendir(DIR, $self->{init}->{data_tb_dir}) or $self->error("Read Error : $self->{init}->{data_tb_dir}");
		my @dir = sort { $b <=> $a } readdir(DIR);
		closedir(DIR);

		foreach my $entry (@dir) {
			if ($entry !~ /^\d+\.$self->{init}->{data_ext}$/) {
				next;
			}

			open(FH, "$self->{init}->{data_tb_dir}$entry") or $self->error("Read Error : $self->{init}->{data_tb_dir}$entry");
			while (<FH>) {
				chomp;
				my($no, $pno, $stat, $date, $blog, $title, $url, $excerpt) = split(/\t/);

				push(@index, "$no\t$pno\t$stat\t$date\t$blog\t$title\t$url\n");
			}
		}

		@index = sort { $b <=> $a } @index;

		open(FH, ">$self->{init}->{data_tb_index}") or $self->error("Write Error : $self->{init}->{data_tb_index}");
		print FH @index;
		close(FH);

		push(@{$self->{message}}, 'トラックバック用インデックス保存ファイルを更新しました。');
	}

	return;
}

### 初期設定
sub setup {
	my $self = shift;

	my $new_pwd_ins   = new webliberty::String($self->{query}->{new_pwd});
	my $cfm_pwd_ins   = new webliberty::String($self->{query}->{cfm_pwd});

	if (!$new_pwd_ins->get_string) {
		$self->{message} = '管理者用パスワードを入力してください。';
		return;
	}
	if (!$cfm_pwd_ins->get_string) {
		$self->{message} = '確認用パスワードを入力してください。';
		return;
	}
	if ($new_pwd_ins->get_string ne $cfm_pwd_ins->get_string) {
		$self->{message} = '管理者用パスワードと確認用パスワードは、同じものを入力してください。';
		return;
	}
	if ($new_pwd_ins->check_length < 4) {
		$self->{message} = '管理者用パスワードは4文字以上を指定してください。';
		return;
	}

	if ($self->{init}->{chmod_mode}) {
		if ($self->{init}->{suexec_mode}) {
			chmod(0604, $self->{init}->{html_file}) or $self->error("Chmod Error : $self->{init}->{html_file}");
			chmod(0705, $self->{init}->{data_dir}) or $self->error("Chmod Error : $self->{init}->{data_dir}");
		} else {
			chmod(0666, $self->{init}->{html_file}) or $self->error("Chmod Error : $self->{init}->{html_file}");
			chmod(0777, $self->{init}->{data_dir}) or $self->error("Chmod Error : $self->{init}->{data_dir}");
		}
	}

	open(FH, ">$self->{init}->{data_user}") or $self->error("Write Error : $self->{init}->{data_user}");
	print FH "admin\t" . $new_pwd_ins->create_password . "\troot\n";
	close(FH);

	if (!-e $self->{init}->{data_config}) {
		my $init_ins = new webliberty::App::Init;
		my %default = %{$init_ins->get_config};
		my $config;

		foreach (keys %default) {
			$config .= "$_=$default{$_}\n";
		}

		open(FH, ">$self->{init}->{data_config}") or $self->error("Write Error : $self->{init}->{data_config}");
		print FH $config;
		close(FH);
	}

	open(FH, ">$self->{init}->{data_profile}") or $self->error("Write Error : $self->{init}->{data_profile}");
	close(FH);

	open(FH, ">$self->{init}->{data_record}") or $self->error("Write Error : $self->{init}->{data_record}");
	close(FH);

	open(FH, ">$self->{init}->{data_field}") or $self->error("Write Error : $self->{init}->{data_field}");
	close(FH);

	open(FH, ">$self->{init}->{data_icon}") or $self->error("Write Error : $self->{init}->{data_icon}");
	close(FH);

	open(FH, ">$self->{init}->{data_top}") or $self->error("Write Error : $self->{init}->{data_top}");
	close(FH);

	open(FH, ">$self->{init}->{data_menu}") or $self->error("Write Error : $self->{init}->{data_menu}");
	close(FH);

	open(FH, ">$self->{init}->{data_link}") or $self->error("Write Error : $self->{init}->{data_link}");
	close(FH);

	if ($self->{init}->{suexec_mode}) {
		mkdir($self->{init}->{archive_dir}, 0705);
		mkdir($self->{init}->{data_diary_dir}, 0700);
		mkdir($self->{init}->{data_comt_dir}, 0700);
		mkdir($self->{init}->{data_tb_dir}, 0700);
		mkdir($self->{init}->{data_upfile_dir}, 0705);
		mkdir($self->{init}->{data_thumbnail_dir}, 0705);
		mkdir($self->{init}->{data_image_dir}, 0705);
		mkdir($self->{init}->{data_icon_dir}, 0705);
		mkdir($self->{init}->{paint_dir}, 0705);
		mkdir($self->{init}->{pch_dir}, 0705);
	} else {
		mkdir($self->{init}->{archive_dir}, 0777);
		mkdir($self->{init}->{data_diary_dir}, 0777);
		mkdir($self->{init}->{data_comt_dir}, 0777);
		mkdir($self->{init}->{data_tb_dir}, 0777);
		mkdir($self->{init}->{data_upfile_dir}, 0777);
		mkdir($self->{init}->{data_thumbnail_dir}, 0777);
		mkdir($self->{init}->{data_image_dir}, 0777);
		mkdir($self->{init}->{data_icon_dir}, 0777);
		mkdir($self->{init}->{paint_dir}, 0777);
		mkdir($self->{init}->{pch_dir}, 0777);
	}

	if (!-e $self->{init}->{data_diary_index}) {
		open(FH, ">$self->{init}->{data_diary_index}") or $self->error("Write Error : $self->{init}->{data_diary_index}");
		close(FH);
	}
	if (!-e $self->{init}->{data_comt_index}) {
		open(FH, ">$self->{init}->{data_comt_index}") or $self->error("Write Error : $self->{init}->{data_comt_index}");
		close(FH);
	}
	if (!-e $self->{init}->{data_tb_index}) {
		open(FH, ">$self->{init}->{data_tb_index}") or $self->error("Write Error : $self->{init}->{data_tb_index}");
		close(FH);
	}

	if ($self->{init}->{chmod_mode}) {
		if ($self->{init}->{suexec_mode}) {
			chmod(0600, $self->{init}->{data_user}) or $self->error("Chmod Error : $self->{init}->{data_user}");
			chmod(0600, $self->{init}->{data_profile}) or $self->error("Chmod Error : $self->{init}->{data_profile}");
			chmod(0600, $self->{init}->{data_record}) or $self->error("Chmod Error : $self->{init}->{data_record}");
			chmod(0600, $self->{init}->{data_field}) or $self->error("Chmod Error : $self->{init}->{data_field}");
			chmod(0600, $self->{init}->{data_icon}) or $self->error("Chmod Error : $self->{init}->{data_icon}");
			chmod(0600, $self->{init}->{data_top}) or $self->error("Chmod Error : $self->{init}->{data_top}");
			chmod(0600, $self->{init}->{data_menu}) or $self->error("Chmod Error : $self->{init}->{data_menu}");
			chmod(0600, $self->{init}->{data_link}) or $self->error("Chmod Error : $self->{init}->{data_link}");
			chmod(0600, $self->{init}->{data_config}) or $self->error("Chmod Error : $self->{init}->{data_config}");
			chmod(0600, $self->{init}->{data_diary_index}) or $self->error("Chmod Error : $self->{init}->{data_diary_index}");
			chmod(0600, $self->{init}->{data_comt_index}) or $self->error("Chmod Error : $self->{init}->{data_comt_index}");
			chmod(0600, $self->{init}->{data_tb_index}) or $self->error("Chmod Error : $self->{init}->{data_tb_index}");
		} else {
			chmod(0666, $self->{init}->{data_user}) or $self->error("Chmod Error : $self->{init}->{data_user}");
			chmod(0666, $self->{init}->{data_profile}) or $self->error("Chmod Error : $self->{init}->{data_profile}");
			chmod(0666, $self->{init}->{data_record}) or $self->error("Chmod Error : $self->{init}->{data_record}");
			chmod(0666, $self->{init}->{data_field}) or $self->error("Chmod Error : $self->{init}->{data_field}");
			chmod(0666, $self->{init}->{data_icon}) or $self->error("Chmod Error : $self->{init}->{data_icon}");
			chmod(0666, $self->{init}->{data_top}) or $self->error("Chmod Error : $self->{init}->{data_top}");
			chmod(0666, $self->{init}->{data_menu}) or $self->error("Chmod Error : $self->{init}->{data_menu}");
			chmod(0666, $self->{init}->{data_link}) or $self->error("Chmod Error : $self->{init}->{data_link}");
			chmod(0666, $self->{init}->{data_config}) or $self->error("Chmod Error : $self->{init}->{data_config}");
			chmod(0666, $self->{init}->{data_diary_index}) or $self->error("Chmod Error : $self->{init}->{data_diary_index}");
			chmod(0666, $self->{init}->{data_comt_index}) or $self->error("Chmod Error : $self->{init}->{data_comt_index}");
			chmod(0666, $self->{init}->{data_tb_index}) or $self->error("Chmod Error : $self->{init}->{data_tb_index}");
		}
	}

	return;
}

### 初期設定画面
sub output {
	my $self = shift;

	print $self->header;
	print <<"_HTML_";
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<title>Setup</title>
<style type="text/css">

* {
	margin: 0px;
	padding: 0px;

	font-size: 13px;
	font-style: normal;
	font-family: 'ＭＳ Ｐゴシック', 'MS UI Gothic', Osaka, sans-serif;
	text-align: left;
}

body {
	padding: 10px;

	background-color: #FFFFFF;

	text-align: center;
}
h1 {
	margin-bottom: 30px;

	font-size: 15px;
	text-align: center;
}
p, li, dt, dd, address {
	color: #000000;

	line-height: 1.4;
}
ul {
	margin: 0px 0px 20px 20px;
}
dl {
	margin-bottom: 20px;
}
dd {
	margin-bottom: 10px;
}
address {
	margin-top: 30px;

	text-align: center;
}

fieldset {
	border: 0px solid #000000;
}
legend {
	display: none;
}
input {
	padding: 1px;

	font-size: 90%;
	font-family: Verdana, Arial, sans-serif;
}
form p input {
	text-align: center;
}

a {
	color: #0000CC;

	text-decoration: underline;
}

div#container {
	width: 400px;

	margin: 0px auto;
	padding: 20px;
	border: 1px solid #666680;

	background-color: #FFFFFF;
}

</style>
</head>
<body>
<div id="container">
	<h1>Setup</h1>
	<ul>
_HTML_

	if (-e $self->{init}->{data_config} and $self->{query}->{mode} eq 'setup') {
		if ($self->{message}) {
			foreach (@{$self->{message}}) {
				print "\t\t<li>$_</li>\n";
			}
		} else {
			print "\t\t<li>設定項目とファイル構成は最新の状態です。</li>\n";
		}
		print "\t</ul>\n";
	} else {
		if ($self->{query}->{exec_setup} and !$self->{message}) {
			print "\t\t<li>初期設定が完了しました。より詳細な設定は、管理者用ページの環境設定ページから設定する事ができます。</li>\n";
			print "\t\t<li><a href=\"$self->{init}->{script_file}\">ブログを表示する。</a></li>\n";
			print "\t</ul>\n";
		} else {
			my $flag;

			if (!-e $self->{init}->{data_dir}) {
				print "\t\t<li>データファイル格納ディレクトリ（<em>$self->{init}->{data_dir}</em>）が存在しません。</li>\n";
				$flag = 1;
			}
			if (!-e $self->{init}->{skin_dir}) {
				print "\t\t<li>スキンファイル格納ディレクトリ（<em>$self->{init}->{skin_dir}</em>）が存在しません。</li>\n";
				$flag = 1;
			}

			if (!$flag) {
				print "\t\t<li>CGIの初期設定を行います。</li>\n";

				if ($self->{message}) {
					print "\t\t<li>$self->{message}</li>\n";
				} else {
					print "\t\t<li>管理者用パスワードを入力してください。</li>\n";
				}

				print <<"_HTML_";
	</ul>
	<form method="post" action="$self->{init}->{script_file}">
		<fieldset>
			<legend>パスワード設定フォーム</legend>
			<input type="hidden" name="exec_setup" value="on" />
			<dl>
				<dt>管理者用パスワード</dt>
					<dd><input type="password" name="new_pwd" size="20" value="$self->{query}->{new_pwd}" /></dd>
				<dt>確認のために再度入力</dt>
					<dd><input type="password" name="cfm_pwd" size="20" value="$self->{query}->{cfm_pwd}" /></dd>
			</dl>
			<p><input type="submit" value="設定する" /></p>
		</fieldset>
	</form>
_HTML_
			} else {
				print "\t\t<li>セットアップができません。</li>\n";
				print "\t</ul>\n";
			}
		}
	}

	print <<"_HTML_";
	<address><a href="http://www.web-liberty.net/">Web Liberty</a></address>
</div>
</body>
</html>
_HTML_

	return;
}

1;
