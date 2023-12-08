#webliberty::App.pm (2006/11/14)
#Copyright(C) 2002-2006 Knight, All rights reserved.

package webliberty::App;

use strict;
use base qw(webliberty::Basis);
use webliberty::Parser;
use webliberty::Configure;
use webliberty::App::Init;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
	};
	bless $self, $class;

	return $self;
}

### メイン処理
sub run {
	my $self = shift;

	my $init_ins   = new webliberty::App::Init;
	my $parser_ins = new webliberty::Parser(max => $init_ins->get_init('parse_size'), jcode => $init_ins->get_init('jcode_mode'));

	if (-e $init_ins->get_init('data_config')) {
		my $config_ins = new webliberty::Configure($init_ins->get_init('data_config'));

		if ($parser_ins->get_query('plugin')) {
			require webliberty::Plugin;
			my $app_ins = new webliberty::Plugin($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
			$app_ins->run;
		}

		if ($ENV{'HTTP_USER_AGENT'} =~ /(DoCoMo|SoftBank|Vodafone|J-PHONE|KDDI-|UP\.Browser|DDIPOCKET|WILLCOM)/i) {
			require webliberty::App::Mobile;
			my $app_ins = new webliberty::App::Mobile($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
			$app_ins->run;
		} else {
			if ($parser_ins->get_query('mode') eq 'setup') {
				require webliberty::App::Setup;
				my $app_ins = new webliberty::App::Setup($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'info') {
				require webliberty::App::Info;
				my $app_ins = new webliberty::App::Info($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'admin') {
				require webliberty::App::Admin;
				my $app_ins = new webliberty::App::Admin($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'pch') {
				require webliberty::App::Pch;
				my $app_ins = new webliberty::App::Pch($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'gallery') {
				require webliberty::App::Gallery;
				my $app_ins = new webliberty::App::Gallery($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'album') {
				require webliberty::App::Album;
				my $app_ins = new webliberty::App::Album($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'receive') {
				require webliberty::App::Receive;
				my $app_ins = new webliberty::App::Receive($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'rss') {
				require webliberty::App::Rss;
				my $app_ins = new webliberty::App::Rss($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'profile') {
				require webliberty::App::Profile;
				my $app_ins = new webliberty::App::Profile($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'search') {
				require webliberty::App::Search;
				my $app_ins = new webliberty::App::Search($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'edit') {
				require webliberty::App::Edit;
				my $app_ins = new webliberty::App::Edit($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'icon') {
				require webliberty::App::Icon;
				my $app_ins = new webliberty::App::Icon($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'image') {
				require webliberty::App::Image;
				my $app_ins = new webliberty::App::Image($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'trackback') {
				require webliberty::App::Trackback;
				my $app_ins = new webliberty::App::Trackback($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} elsif ($parser_ins->get_query('mode') eq 'comment') {
				require webliberty::App::Comment;
				my $app_ins = new webliberty::App::Comment($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			} else {
				require webliberty::App::List;
				my $app_ins = new webliberty::App::List($init_ins->get_init, $config_ins->get_config, $parser_ins->get_query);
				$app_ins->run;
			}
		}
	} else {
		require webliberty::App::Setup;
		my $app_ins = new webliberty::App::Setup($init_ins->get_init, '', $parser_ins->get_query);
		$app_ins->run;
	}

	return;
}

1;
