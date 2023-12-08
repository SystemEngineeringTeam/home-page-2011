#webliberty::Ping.pm (2007/03/01)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::Ping;

use strict;
use LWP::UserAgent;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		ping_url  => undef,
		url       => undef,
		blog_name => undef
	};
	bless $self, $class;

	return $self;
}

### 更新PING送信
sub send_ping {
	my $self = shift;
	my %args = @_;

	$self->{ping_url}  = $args{'ping_url'};
	$self->{url}       = $args{'url'};
	$self->{blog_name} = $args{'blog_name'};

	my $ping_date = <<"_PING_";
<?xml version="1.0"?>
<methodCall>
	<methodName>weblogUpdates.ping</methodName>
	<params>
		<param>
			<value>$self->{blog_name}</value>
		</param>
		<param>
			<value>$self->{url}</value>
		</param>
	</params>
</methodCall>
_PING_

	my $request_ins = new HTTP::Request(POST => $self->{ping_url});
	$request_ins->content_type('text/xml');
	$request_ins->content("$ping_date");

	my $useragent_ins = new LWP::UserAgent;
	my $response_ins = $useragent_ins->request($request_ins);

	if ($response_ins->is_success) {
		if ($response_ins->content =~ m/<name>flerror<\/name>.*<boolean>1<\/boolean>.*<name>message<\/name>\s*<value>(.+)<\/value>/s) {
			return (0, $1);
		}
	} else {
		return (0, 'No Response');
	}

	return 1;
}

1;
