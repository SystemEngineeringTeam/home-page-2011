#webliberty::App::Tb.pm (2008/07/05)
#Copyright(C) 2002-2008 Knight, All rights reserved.

package webliberty::App::Tb;

use strict;
use LWP::UserAgent;
use base qw(webliberty::Basis);
use webliberty::Parser;
use webliberty::String;
use webliberty::Configure;
use webliberty::Lock;
use webliberty::Sendmail;
use webliberty::Plugin;
use webliberty::App::Init;
use webliberty::App::Diary;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init   => undef,
		config => undef,
		query  => undef,
		update => undef
	};
	bless $self, $class;

	my $init_ins = new webliberty::App::Init;
	$self->{init} = $init_ins->get_init;

	my $query_ins = new webliberty::Parser(max => $self->{init}->{parse_size}, jcode => 1);
	$self->{query} = $query_ins->get_query;

	my $config_ins = new webliberty::Configure($self->{init}->{data_config});
	$self->{config} = $config_ins->get_config;

	return $self;
}

### メイン処理
sub run {
	my $self = shift;

	$self->regist;
	$self->output_complete;

	return;
}

### トラックバック受信
sub regist {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $tb_id;
	if ($ENV{'PATH_INFO'} =~ /\/(\d+)$/) {
		$tb_id = $1;
	} else {
		$self->error('No TrackBack ID (tb_id)');
	}

	my $title_ins   = new webliberty::String($self->{query}->{title});
	my $url_ins     = new webliberty::String($self->{query}->{url});
	my $excerpt_ins = new webliberty::String($self->{query}->{excerpt});
	my $blog_ins    = new webliberty::String($self->{query}->{blog_name});

	$title_ins->create_line;
	$url_ins->create_line;
	$excerpt_ins->create_line;
	$blog_ins->create_line;

	my $flag;
	foreach (split(/<>/, $self->{config}->{black_list_tb})) {
		$_ = quotemeta($_);

		if ($url_ins->get_string =~ /$_/i) {
			$flag = 1;
			last;
		}
	}
	if ($flag) {
		$self->error('Forbidden URL (url)');
	}
	if ($self->{config}->{ng_word_tb}) {
		foreach (split(/<>/, $self->{config}->{ng_word_tb})) {
			if ($_ and $excerpt_ins->get_string =~ /$_/) {
				$self->error("'$_' is NG Word (excerpt)");
			}
		}
	}
	if ($self->{config}->{need_word_tb}) {
		my $flag;
		foreach (split(/<>/, $self->{config}->{need_word_tb})) {
			if ($_ and $excerpt_ins->get_string =~ /$_/) {
				$flag = 1;
			}
		}
		if (!$flag) {
			$self->error('Required Word No Exist (excerpt)');
		}
	}
	if ($self->{config}->{need_japanese_tb} and $excerpt_ins->get_string !~ /[\x80-\xFF]/) {
		$self->error('No Japanese (excerpt)');
	}

	$url_ins->replace_string('~', '%7E');

	if (!$url_ins->get_string) {
		$self->error('No URL (url)');
	}

	if ($self->{config}->{need_link_tb}) {
		my $useragent_ins = new LWP::UserAgent;
		my $request_ins   = new HTTP::Request(GET => $url_ins->get_string);

		my $response_ins = $useragent_ins->request($request_ins);

		if ($response_ins->is_success) {
			if ($response_ins->content !~ m/$self->{config}->{site_url}/s) {
				$self->error("Site URL is Not Found in '" . $url_ins->get_string . "'");
			}
		} else {
			$self->error("Request failed to '" . $url_ins->get_string . "'");
		}
	}

	my $lock_ins = new webliberty::Lock($self->{init}->{data_lock});
	if (!$lock_ins->file_lock) {
		$self->error('Now Locking');
	}

	open(FH, $self->{init}->{data_tb_index}) or $self->error("Read Error : $self->{init}->{data_tb_index}");
	my @index = sort { $b <=> $a } <FH>;
	close(FH);

	my $new_no         = (split(/\t/, $index[0]))[0] + 1;
	my $trackback_file = $self->{init}->{data_tb_dir} . $tb_id . '.' . $self->{init}->{data_ext};

	if (!-e $trackback_file) {
		open(FH, ">$trackback_file") or $self->error("Write Error : $trackback_file");
		close(FH);

		if ($self->{init}->{chmod_mode}) {
			if ($self->{init}->{suexec_mode}) {
				chmod(0600, "$trackback_file") or $self->error("Chmod Error : $trackback_file");
			} else {
				chmod(0666, "$trackback_file") or $self->error("Chmod Error : $trackback_file");
			}
		}
	}

	open(FH, ">>$trackback_file") or $self->error("Write Error : $trackback_file");
	print FH "$new_no\t$tb_id\t$self->{config}->{tb_stat}\t" . time . "\t" . $blog_ins->get_string . "\t" . $title_ins->get_string . "\t" . $url_ins->get_string . "\t" . $excerpt_ins->get_string . "\n";
	close(FH);

	unshift(@index, "$new_no\t$tb_id\t$self->{config}->{tb_stat}\t" . time . "\t" . $blog_ins->get_string . "\t" . $title_ins->get_string . "\t" . $url_ins->get_string . "\n");

	open(FH, ">$self->{init}->{data_tb_index}") or $self->error("Write Error : $self->{init}->{data_tb_index}");
	print FH @index;
	close(FH);

	$lock_ins->file_unlock;

	$self->{update}->{query}->{no} = $tb_id;

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{update}->{query});
	$diary_ins->update;

	if ($self->{config}->{sendmail_tb_mode}) {
		my $mail_body;
		$mail_body  = "$self->{config}->{site_title}が以下のトラックバックを受信しました。\n";
		$mail_body .= "\n";
		$mail_body .= "送信元：" . $blog_ins->get_string . "\n";
		if ($self->{config}->{sendmail_detail}) {
			if ($url_ins->get_string) {
				$mail_body .= "送信元URL：" . $url_ins->get_string . "\n";
			}
			if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
				$mail_body .= "記事URL：$self->{config}->{site_url}$1?no=$tb_id\n";
			}
		}
		$mail_body .= "\n";
		$mail_body .= $excerpt_ins->trim_string($self->{config}->{sendmail_length}, '...');

		my $sendmail_ins = new webliberty::Sendmail($self->{config}->{sendmail_path});
		foreach (split(/<>/, $self->{config}->{sendmail_list})) {
			if (!$_) {
				next;
			}

			my($flag, $message) = $sendmail_ins->sendmail(
				send_to => $_,
				subject => "$self->{config}->{site_title}がトラックバックを受信しました",
				name    => $blog_ins->get_string,
				message => $mail_body
			);
			if (!$flag) {
				$self->error($message);
			}
		}
	}

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

### 受信完了画面表示
sub output_complete {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	print <<"_XML_";
Content-Type: text/xml

<?xml version="1.0" encoding="iso-8859-1"?>
<response>
	<error>0</error>
</response>
_XML_

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

	print <<"_XML_";
Content-Type: text/xml

<?xml version="1.0" encoding="iso-8859-1"?>
<response>
	<error>1</error>
	<message>$message</message>
</response>
_XML_

	exit;
}

1;
