#webliberty::Plugin.pm (2007/03/11)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::Plugin;

use strict;
use base qw(webliberty::Basis);

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init   => shift,
		config => shift,
		query  => shift
	};
	bless $self, $class;

	return $self;
}

### 処理実行時
sub run {
	my $self = shift;

	my %result;

	if ($self->{query}->{plugin}) {
		my $plugin = 'webliberty::Plugin::' . $self->{query}->{plugin};

		eval "require $plugin;";
		if ($@) {
			$self->error("Load Error : $plugin");
		} else {
			if (eval "defined(\&$plugin\:\:run)") {
				$plugin->new($self->{init}, $self->{config}, $self->{query})->run;
			}
		}
	} else {
		opendir(DIR, $self->{init}->{plugin_dir}) or $self->error("Read Error : $self->{init}->{plugin_dir}");
		my @plugins = sort { $a cmp $b } grep { m/\w+\.pm/g } readdir(DIR);
		closedir(DIR);

		foreach (@plugins) {
			if ($_ =~ /(\w+)\.pm/) {
				my $plugin = 'webliberty::Plugin::' . $1;

				eval "require $plugin;";
				if ($@) {
					$self->error("Load Error : $plugin");
				} else {
					if (eval "defined(\&$plugin\:\:run)") {
						$result{$1} = $plugin->new($self->{init}, $self->{config}, $self->{query})->run;
					}
				}
			}
		}
	}

	return %result;
}

### 処理完了時
sub complete {
	my $self = shift;

	opendir(DIR, $self->{init}->{plugin_dir}) or $self->error("Read Error : $self->{init}->{plugin_dir}");
	my @plugins = sort { $a cmp $b } grep { m/\w+\.pm/g } readdir(DIR);
	closedir(DIR);

	foreach (@plugins) {
		if ($_ =~ /(\w+)\.pm/) {
			my $plugin = 'webliberty::Plugin::' . $1;

			eval "require $plugin;";
			if ($@) {
				$self->error("Load Error : $plugin");
			} else {
				if (eval "defined(\&$plugin\:\:complete)") {
					$plugin->new($self->{init}, $self->{config}, $self->{query})->complete;
				}
			}
		}
	}

	return;
}

### データ表示時
sub article {
	my $self = shift;
	my %args = @_;

	my %result;

	opendir(DIR, $self->{init}->{plugin_dir}) or $self->error("Read Error : $self->{init}->{plugin_dir}");
	my @plugins = sort { $a cmp $b } grep { m/\w+\.pm/g } readdir(DIR);
	closedir(DIR);

	foreach (@plugins) {
		if ($_ =~ /(\w+)\.pm/) {
			my $plugin = 'webliberty::Plugin::' . $1;

			eval "require $plugin;";
			if ($@) {
				$self->error("Load Error : $plugin");
			} else {
				if (eval "defined(\&$plugin\:\:article)") {
					$result{'ARTICLE_' . $1} = $plugin->new($self->{init}, $self->{config}, $self->{query})->article(%args);
					if ($result{'ARTICLE_' . $1}) {
						$result{'ARTICLE_' . $1 . '_START'} = '';
						$result{'ARTICLE_' . $1 . '_END'}   = '';
					} else {
						$result{'ARTICLE_' . $1 . '_START'} = '<!--';
						$result{'ARTICLE_' . $1 . '_END'}   = '-->';
					}
				}
			}
		}
	}

	return %result;
}

1;
