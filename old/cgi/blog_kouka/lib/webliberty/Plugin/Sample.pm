#Sampleプラグイン Ver 1.00 (2006/06/11)
#Copyright(C) 2002-2006 Knight, All rights reserved.

package webliberty::Plugin::Sample;

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

### メイン処理
sub run {
	my $self = shift;

	my $result;

	if ($self->{query}->{plugin}) {
		print $self->header;
		print "<html>\n";
		print "<head><title>サンプル</title></head>\n";
		print "<body>\n";
		print "<p>ページ表示サンプル。</p>\n";
		print "</body>\n";
		print "</html>\n";
		exit;
	} else {
		$result = '埋め込み表示サンプル。';
	}

	return $result;
}

1;
