#webliberty::App::Profile.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Profile;

use strict;
use base qw(webliberty::Basis);
use webliberty::String;
use webliberty::Skin;
use webliberty::Plugin;
use webliberty::App::Diary;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init   => shift,
		config => shift,
		query  => shift,
		plugin => undef,
		update => undef
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

	$self->output;

	return;
}

### プロフィール表示
sub output {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_profile}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	my(%name, %text);

	open(FH, $self->{init}->{data_profile}) or $self->error("Read Error : $self->{init}->{data_profile}");
	while (<FH>) {
		chomp;
		my($user, $name, $text) = split(/\t/);

		if (!$name) {
			$name = 'admin';
		}

		$name{$user} = $name;
		$text{$user} = $text;
	}
	close(FH);

	if (!$name{$self->{query}->{user}}) {
		$name{$self->{query}->{user}} = $self->{query}->{user};
	}
	if (!$text{$self->{query}->{user}}) {
		$text{$self->{query}->{user}} = 'プロフィールは設定されていません。';

		if (!$self->{config}->{profile_break}) {
			$text{$self->{query}->{user}} = '<p>' . $text{$self->{query}->{user}} . '</p>';
		}
	}

	my $user_ins = new webliberty::String($self->{query}->{user});
	my $name_ins = new webliberty::String($name{$self->{query}->{user}});
	my $text_ins = new webliberty::String($text{$self->{query}->{user}});

	if (!$self->{config}->{profile_break}) {
		$text_ins->replace_string('<br />', "\n");
	}

	$text_ins->permit_html;
	if ($self->{config}->{profile_break} and $self->{config}->{paragraph_mode}) {
		$text_ins->replace_string('<br /><br />', '</p><p>');
	}

	if ($self->{config}->{profile_break}) {
		$text_ins->set_string('<p>' . $text_ins->get_string . '</p>');
	}

	if ($self->{config}->{autolink_mode}) {
		$text_ins->create_link($self->{config}->{autolink_attribute});
	}

	print $self->header;
	print $skin_ins->get_data('header');
	print $skin_ins->get_replace_data(
		'contents',
		PROFILE_USER => $user_ins->create_line,
		PROFILE_NAME => $name_ins->create_line,
		PROFILE_TEXT => $text_ins->get_string
	);
	print $skin_ins->get_data('footer');

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

1;
