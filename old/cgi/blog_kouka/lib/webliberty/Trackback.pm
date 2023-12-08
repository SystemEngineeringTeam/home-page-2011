#webliberty::Trackback.pm (2007/01/26)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::Trackback;

use strict;
use LWP::UserAgent;
use webliberty::Encoder;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		trackback_url => undef,
		title         => undef,
		url           => undef,
		excerpt       => undef,
		blog_name     => undef,
		user_agent    => undef
	};
	bless $self, $class;

	return $self;
}

### トラックバック送信
sub send_trackback {
	my $self = shift;
	my %args = @_;

	$self->{trackback_url} = $args{'trackback_url'};
	$self->{title}         = $args{'title'};
	$self->{url}           = $args{'url'};
	$self->{excerpt}       = $args{'excerpt'};
	$self->{blog_name}     = $args{'blog_name'};
	$self->{user_agent}    = $args{'user_agent'};

	if (!$self->{user_agent}) {
		$self->{user_agent} = 'Web Liberty';
	}

	foreach ($self->{title}, $self->{url}, $self->{excerpt}, $self->{blog_name}) {
		$_ =~ s/&lt;/</g;
		$_ =~ s/&gt;/>/g;
		$_ =~ s/<[^>]*>//g;
		$_ =~ s/</&lt;/g;
		$_ =~ s/>/&gt;/g;
		$_ =~ s/&amp;/&/g;

		my $string_ins = new webliberty::String($_);
		$_ = $string_ins->trim_string(252, '...');

		my $encoder_ins = new webliberty::Encoder($_);
		$_ = $encoder_ins->url_encode;
	}

	my $request_ins = new HTTP::Request(POST => $self->{trackback_url});
	$request_ins->content_type('application/x-www-form-urlencoded');
	$request_ins->content("title=$self->{title}&url=$self->{url}&excerpt=$self->{excerpt}&blog_name=$self->{blog_name}");

	my $useragent_ins = new LWP::UserAgent;
	$useragent_ins->agent($self->{user_agent});
	my $response_ins = $useragent_ins->request($request_ins);

	if ($response_ins->is_success) {
		if ($response_ins->content =~ m/<error>1<\/error>.*<message>(.+)<\/message>/s) {
			return (0, $1);
		}
	} else {
		return (0, 'No Response');
	}

	return 1;
}

1;
