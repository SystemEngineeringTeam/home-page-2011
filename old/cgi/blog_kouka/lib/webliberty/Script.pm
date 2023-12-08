#webliberty::Script.pm (2007/03/01)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::Script;

use strict;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
	};
	bless $self, $class;

	return $self;
}

### データ作成
sub create_jscript {
	my $self = shift;
	my %args = @_;

	my $file     = $args{'file'};
	my $contents = $args{'contents'};
	my $break    = $args{'break'};

	$contents =~ s/\\/\\\\/g;
	$contents =~ s/'/\\'/g;

	my $script;
	foreach (split(/\n/, $contents)) {
		if ($break) {
			$script .= "document.write('$_\\n');\n";
		} else {
			$script .= "document.write('$_');\n";
		}
	}

	open(webliberty_Script, ">$file") or return(0, "Write Error : $file");
	print webliberty_Script $script;
	close(webliberty_Script);

	return 1;
}

1;
