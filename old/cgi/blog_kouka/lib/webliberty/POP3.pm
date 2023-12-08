#webliberty::POP3.pm (2007/06/17)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::POP3;

use strict;
use Jcode;
use Socket;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		pop_server  => shift,
		pop_user    => undef,
		pop_pwd     => undef,
		pop_port    => undef,
		mail_number => undef
	};
	bless $self, $class;

	binmode(STDOUT);

	return $self;
}

### ログイン
sub login {
	my $self = shift;
	my %args = @_;

	$self->{pop_user} = $args{'pop_user'};
	$self->{pop_pwd}  = $args{'pop_pwd'};
	$self->{pop_port} = $args{'pop_port'};

	if (!$self->{pop_server} or !$self->{pop_user} or !$self->{pop_pwd}) {
		return 0;
	}
	if (!$self->{pop_port}) {
		$self->{pop_port} = '110';
	}

	socket(webliberty_POP3, PF_INET, SOCK_STREAM, getprotobyname('tcp'));
	my $server_ip   = gethostbyname($self->{pop_server});
	my $server_addr = pack('Sna4x8', AF_INET, $self->{pop_port}, $server_ip);

	my $socket_mesg;

	connect(webliberty_POP3, $server_addr) or return 0;
	recv(webliberty_POP3, $socket_mesg, 512, 0);

	$socket_mesg .= $self->_sendrecv("USER $self->{pop_user}\n");
	$socket_mesg .= $self->_sendrecv("PASS $self->{pop_pwd}\n");
	if ($socket_mesg =~ /\-ERR/) {
		return 0;
	}

	if ($self->_sendrecv("STAT\n") =~ /^\+OK\s+(\d+)\s+\d+/) {
		$self->{mail_number} = $1;
	} else {
		$self->{mail_number} = 0;
	}

	return 1;
}

### ログアウト
sub logout {
	my $self = shift;

	$self->_sendrecv("QUIT\n");
	close(webliberty_POP3);

	return;
}

### メール件数取得
sub get_number {
	my $self = shift;

	return $self->{mail_number};
}

### メール受信
sub get_mail {
	my $self = shift;
	my %args = @_;

	$self->{max_size} = $args{'max_size'};

	if (!$self->{max_size}) {
		$self->{max_size} = 64;
	}

	my @mails;
	foreach (1 .. $self->{mail_number}) {
		my $mail_size = 0;
		if ($self->_sendrecv("LIST $_\n") =~ /^\+OK\s+\d+\s+(\d+)/) {
			$mail_size = $1;
		} else {
			return;
		}

		if ($mail_size < $self->{max_size} * 1024) {
			my $read_mail = $self->_sendrecv("RETR $_\n");

			while (my $message = <webliberty_POP3>) {
				if ($message =~ /^\.\r?\n$/) {
					last;
				}

				$message =~ s/^\.\.\n/.\n/;

				$read_mail .= $message;
			}

			push(@mails, $self->_parse_mail($read_mail));
		} else {
			next;
		}

		if ($self->_sendrecv("DELE $_\n") !~ /^\+OK/) {
			return;
		}
	}

	return @mails;
}

### サーバー通信
sub _sendrecv {
	my $self      = shift;
	my $send_data = shift;

	my $recv_data;

	if ($send_data) {
		send(webliberty_POP3, $send_data, 0);
	}
	recv(webliberty_POP3, $recv_data, 512, 0);

	return $recv_data;
}

### メール解析
sub _parse_mail {
	my $self      = shift;
	my $mail_data = shift;

	$mail_data =~ s/\r?\n/\r/g;
	$mail_data =~ s/\r/\n/g;

	my($mail_header, $mail_body) = split(/\n\n/, $mail_data, 2);

	$mail_header =~ s/\n[\t ]+/ /g;
	$mail_header =~ s/\t//g;
	$mail_body   =~ s/\t//g;

	my $subject = $self->_get_subject($mail_header);
	my $address = $self->_get_address($mail_header);
	my $name    = $self->_get_name($mail_header);
	my $date    = $self->_get_date($mail_header);

	my($text, @filename, @filedata);

	if ($mail_header =~ /Content-type:\s*multipart\//i or $mail_header =~ /Content\-Transfer\-Encoding:\s*base64/i) {
		#添付ファイル有りのメール
		my $boundary;
		if ($mail_header =~ /boundary\=\"([^\"]+)\"/i) {
			$boundary = $1;
		} else {
			$boundary = '';
		}

		if ($boundary) {
			#バウンダリ文字列有りのメール
			my @body_parts = split(/\n*--$boundary-?-?/, $mail_body);
			my $message;

			#添付ファイル取得
			foreach (1 .. $#body_parts) {
				if ($body_parts[$_] =~ /Content-Type:\s*text\/plain/) {
					$message = $body_parts[$_];
				} else {
					my $filename = $self->_get_filename($body_parts[$_]);
					my $filedata = $self->_get_filedata($body_parts[$_]);

					if ($filename and $filedata) {
						push(@filename, $filename);
						push(@filedata, $filedata);
					}
				}
			}

			#本文取得
			if ($message) {
				my @bodys = split(/\n\n/, $message, 2);

				foreach (split(/\n/, $bodys[1])) {
					$_ = Jcode->new($_)->utf8;

					$_ =~ s/&/&amp;/g;
					$_ =~ s/</&lt;/g;
					$_ =~ s/>/&gt;/g;
					$_ =~ s/"/&quot;/g;

					$text .= "$_\n";
				}

				$text =~ s/^\n+//;
				$text =~ s/\n+$//;
				$text =~ s/\n/<br \/>/g;
			} else {
				$text = '';
			}
		} else {
			#バウンダリ文字列無しのメール
			my $filename = $self->_get_filename($mail_header);
			my $filedata = $self->_get_filedata($mail_body);

			if ($filename and $filedata) {
				push(@filename, $filename);
				push(@filedata, $filedata);
			}
		}
	} else {
		#添付ファイル無しのメール
		$text = Jcode->new($mail_body)->utf8;

		$text =~ s/^\n+//;
		$text =~ s/\n+$//;
		$text =~ s/\n/<br \/>/g;
	}

	return{
		subject  => $subject,
		address  => $address,
		name     => $name,
		date     => $date,
		text     => $text,
		filename => [@filename],
		filedata => [@filedata]
	};
}

### メール件名取得
sub _get_subject {
	my $self   = shift;
	my $header = shift;

	$header = "\n$header";

	my $subject;
	if ($header =~ /\nSubject: *([^\n]*)/) {
		$subject = $1;

		if ($subject =~ /=\?ISO-2022-JP\?B\?([^\?]+)\?=/i) {
			$subject = $self->_base64_dencode($1);
		}

		$subject = Jcode->new($subject)->utf8;

		$subject =~ s/&/&amp;/g;
		$subject =~ s/</&lt;/g;
		$subject =~ s/>/&gt;/g;
		$subject =~ s/"/&quot;/g;
	} else {
		$subject = '';
	}

	return $subject;
}

### 送信元メールアドレス取得
sub _get_address {
	my $self   = shift;
	my $header = shift;

	$header = "\n$header";

	my $address;
	if ($header =~ /\nFrom: *([^\n]*)/) {
		my $from = $1;

		if ($from =~ /([\w\.\d\+\-\_]+\@[\w\.\d\+\-\_]+)/) {
			$address = $1;
		} else {
			$address = '';
		}
	} else {
		$address = '';
	}

	return $address;
}

### 送信者名取得
sub _get_name {
	my $self   = shift;
	my $header = shift;

	$header = "\n$header";

	my $name;
	if ($header =~ /\nFrom: *([^\n]*)/) {
		my $from = $1;

		if ($from =~ /=\?ISO-2022-JP\?B\?([^\?]+)\?=/i) {
			$name = $self->_base64_dencode($1);

			$name = Jcode->new($name)->utf8;

			$name =~ s/<[^>]*>//g;
			$name =~ s/&/&amp;/g;
			$name =~ s/</&lt;/g;
			$name =~ s/>/&gt;/g;
			$name =~ s/"/&quot;/g;
		} else {
			$name = '';
		}
	} else {
		$name = '';
	}

	return $name;
}

### メール送信日時取得
sub _get_date {
	my $self   = shift;
	my $header = shift;

	my %month = ('Jan'=>'01', 'Feb'=>'02', 'Mar'=>'03', 'Apr'=>'04', 'May'=>'05', 'Jun'=>'06', 'Jul'=>'07', 'Aug'=>'08', 'Sep'=>'09', 'Oct'=>'10', 'Nov'=>'11', 'Dec'=>'12');

	$header = "\n$header";

	my $date;
	if ($header =~ /\nDate: *([^\n]*)/) {
		$date = $1;

		if ($date =~ /(\d\d)\s(\w\w\w)\s(\d\d\d\d)\s(\d\d)\:(\d\d)\:(\d\d)/) {
			$date = "$3-$month{$2}-$1 $4:$5:$6";
		} else {
			$date = '';
		}
	} else {
		$date = '';
	}

	return $date;
}

### 添付ファイル名取得
sub _get_filename {
	my $self = shift;
	my $info = shift;

	$info =~ s/filename\*\d\*=/filename=/g;
	$info =~ s/\'ja\'/\?B\?/g;

	my $filename;
	if ($info =~ /name=\"?([^\"\n]+)\"?/i) {
		$filename = $1;

		if ($filename =~ /=\?ISO-2022-JP\?B\?([^\?]+)\?=/i) {
			$filename = $self->_base64_dencode($1);
		}

		$filename = Jcode->new($filename)->utf8;

		$filename =~ s/^\s+//;
		$filename =~ s/\s+$//;

		if ($filename =~ /([^\/\\]*\.[^.\/\\]*)$/) {
			$filename = lc($1);
		} else {
			$filename = '';
		}
	} else {
		$filename = '';
	}

	return $filename;
}

### 添付ファイル取得
sub _get_filedata {
	my $self = shift;
	my $info = shift;

	my @files = split(/\n\n/, $info, 2);

	if ($files[0] =~ /Content\-Transfer\-Encoding:\s*base64/i) {
		$files[1] = $self->_base64_dencode($files[1]);
	}

	return $files[1];
}

### BASE64デコード
sub _base64_dencode {
	my $self   = shift;
	my $string = shift;

	$string =~ tr/A-Za-z0-9+\///cd;
	$string =~ tr/A-Za-z0-9+\//\x00-\x3f/;
	$string = unpack('B*', $string);
	$string =~ s/(..)(......)/$2/g;
	$string =~ s/((........)*)(.*)/$1/;
	$string = pack('B*', $string);

	return $string;
}

1;
