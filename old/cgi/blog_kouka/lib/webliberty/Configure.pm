#webliberty::Configure.pm (2007/03/01)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::Configure;

use strict;
use base qw(webliberty::Basis);

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		file   => shift,
		config => undef
	};
	bless $self, $class;

	$self->{config} = $self->_parse_config($self->{file});

	return $self;
}

### 設定取得
sub get_config {
	my $self = shift;
	my $name = shift;

	my $config;

	if ($name) {
		$config = $self->{config}->{$name};
	} else {
		$config = $self->{config};
	}

	return $config;
}

### 設定ファイル解析
sub _parse_config {
	my $self = shift;
	my $file = shift;

	my $config;

	open(webliberty_Configure, $file) or $self->error("Read Error : $file");
	while (<webliberty_Configure>) {
		chomp;
		my($key, $value) = split(/=/, $_, 2);

		$config->{$key} = $value;
	}
	close(webliberty_Configure);

	return $config;
}

1;
