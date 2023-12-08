#webliberty::App::Receive.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Receive;

use strict;
use base qw(webliberty::Basis Exporter);
use vars qw(@EXPORT_OK);
use webliberty::Skin;
use webliberty::POP3;
use webliberty::Plugin;
use webliberty::App::Diary;

@EXPORT_OK = qw(receive);

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init    => shift,
		config  => shift,
		query   => shift,
		plugin  => undef,
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
	if (!$self->{config}->{receive_mode}) {
		$self->error('不正なアクセスです。');
	}

	$self->receive;
	$self->output;

	return;
}

### メール受信
sub receive {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $pop3_ins = new webliberty::POP3($self->{config}->{pop_server});

	my $flag = $pop3_ins->login(pop_user => $self->{config}->{pop_user}, pop_pwd => $self->{config}->{pop_pwd});
	if (!$flag) {
		$self->error('POP3サーバーにログインできません。');
	}

	my $all  = $pop3_ins->get_number;
	my @mail = $pop3_ins->get_mail(max_size => 256) if ($all);
	$pop3_ins->logout;

	my($mail, $trash);
	if ($all) {
		eval "use webliberty::App::Admin qw(get_user set_user record_log check regist);";

		foreach (@mail) {
			my($admin_user, $flag);
			foreach my $list (split(/<>/, $self->{config}->{receive_list})) {
				my($address, $user) = split(/,/, $list);
				if ($_->{address} eq $address) {
					$admin_user = $user;
					$flag       = 1;

					last;
				}
			}
			if (!$flag) {
				$trash++;

				next;
			}

			if (!$admin_user) {
				open(FH, $self->{init}->{data_user}) or $self->error("Read Error : $self->{init}->{data_user}");
				$admin_user = (split(/\t/, <FH>))[0];
				close(FH);
			}

			if ($_->{date} =~ /^(\d\d\d\d)\-(\d\d)\-(\d\d)\s(\d\d)\:(\d\d)\:\d\d$/) {
				$self->{query}->{year}   = $1;
				$self->{query}->{month}  = $2;
				$self->{query}->{day}    = $3;
				$self->{query}->{hour}   = $4;
				$self->{query}->{minute} = $5;
			} else {
				my($sec, $min, $hour, $day, $mon, $year, $week) = localtime(time);

				$self->{query}->{year}   = sprintf("%04d", $year + 1900);
				$self->{query}->{month}  = sprintf("%02d", $mon + 1);
				$self->{query}->{day}    = sprintf("%02d", $day);
				$self->{query}->{hour}   = sprintf("%02d", $hour);
				$self->{query}->{minute} = sprintf("%02d", $min);
			}

			$self->{query}->{stat}  = $self->{config}->{default_stat};
			$self->{query}->{break} = $self->{config}->{default_break};
			$self->{query}->{comt}  = $self->{config}->{default_comt};
			$self->{query}->{tb}    = $self->{config}->{default_tb};

			my $i;

			open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
			while (my $field = <FH>) {
				chomp($field);

				$i++;

				if ($self->{config}->{receive_field} eq $field) {
					last;
				}
			}
			close(FH);

			$self->{query}->{field} = $i;
			$self->{query}->{name}  = $admin_user;
			$self->{query}->{subj}  = $_->{subject} || 'No Subject';
			$self->{query}->{text}  = $_->{text} || 'No Message.';

			my @filename = @{$_->{filename}};
			my @filedata = @{$_->{filedata}};

			foreach my $no (0 .. 4) {
				if ($filename[$no] and $filedata[$no]) {
					$self->{query}->{'file' . ($no + 1)}->{file_name} = $filename[$no];
					$self->{query}->{'file' . ($no + 1)}->{file_data} = $filedata[$no];
				} else {
					$self->{query}->{'file' . ($no + 1)} = '';
					$self->{query}->{'file' . ($no + 1)} = '';
				}
			}

			if ($self->{query}->{name}) {
				$self->set_user($self->{query}->{name});
			}
			$self->check;
			$self->regist;

			$mail++;
		}
	}

	if ($mail) {
		$self->{message} = "$mail件のメールを受信しました。";
	} else {
		$self->{message} = '新着メールはありません。';
	}
	if ($trash) {
		$self->{message} .= "$trash件のメールを無効なメールとして処理しました。";
	}

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return $mail;
}

### 処理完了画面表示
sub output {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_receive}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}},
		INFO_MESSAGE => $self->{message}
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

1;
