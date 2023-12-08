#webliberty::Decoration.pm (2007/03/26)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::Decoration;

use strict;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		string    => shift,
		article   => undef,
		paragraph => undef,
		color     => undef,
		heading   => 'h1,h2,h3'
	};
	bless $self, $class;

	return $self;
}

### 装飾設定
sub init_decoration {
	my $self = shift;
	my %args = @_;

	if ($args{'article'}) {
		$self->{article} = $args{'article'};
	}
	if ($args{'paragraph'}) {
		$self->{paragraph} = $args{'paragraph'};
	}
	if ($args{'color'}) {
		$self->{color} = " style=\"color:$args{'color'}\"";
	}
	if ($args{'heading'}) {
		$self->{heading} = $args{'heading'};
	}

	return;
}

### 装飾実行
sub create_decoration {
	my $self = shift;

	my @heading = split(/,/, $self->{heading});

	my($text, $flag);

	foreach (split("\n", $self->{string})) {
		if ($flag) {
			if ($_ eq '|<') {
				$text .= "</pre>";
				$flag = 0;
			} elsif ($_ eq '||<') {
				if ($flag == 3) {
					$text .= "</span><br /><br />";
					$flag = 0;
				} else {
					$text .= "</code></pre>";
					$flag = 0;
				}
			} else {
				if ($flag == 2 or $flag == 3) {
					$_ =~ s/&/&amp;/g;
					$_ =~ s/</&lt;/g;
					$_ =~ s/>/&gt;/g;
					$_ =~ s/"/&quot;/g;

					$_ =~ s/\(\(/<\a>/g;
					$_ =~ s/\)\)/<\/\a>/g;
				}
				if ($flag == 3) {
					$text .= "$_<br />\t";
				} else {
					$text .= "$_\t";
				}
			}
		} else {
			if ($_ eq '>|') {
				$text .= "<pre>";
				$flag = 1;
			} elsif ($_ eq '>||') {
				$text .= "<pre><code>";
				$flag = 2;
			} elsif ($_ eq '>|aa|') {
				$text .= "<br /><br /><span style=\"font-size:12pt;line-height:18px;font-family:'Mona','IPA MONAPGOTHIC','MS PGothic','ＭＳ Ｐゴシック',sans-serif;\">";
				$flag = 3;
			} elsif ($_ eq '>>') {
				$text .= "<blockquote>";
			} elsif ($_ eq '<<') {
				$text .= "</blockquote>";
			} elsif ($_ =~ s/^\|(.+)\|$//) {
				my $row;
				foreach my $cel (split(/\|/, $1)) {
					if ($cel =~ s/^\*(.+)//) {
						$row .= "<th$self->{color}>$1</th>";
					} else {
						$row .= "<td$self->{color}>$cel</td>";
					}
				}
				$text .= "<tr>$row</tr>";
			} elsif ($_ =~ s/^\:([^\:]+)\:(.+)$//) {
				$text .= "<dt$self->{color}>$1</dt><dd$self->{color}>$2</dd>";
			} elsif ($_ =~ s/^\*\*\*(.+)//) {
				$text .= "<$heading[2]$self->{color}>$1</$heading[2]>";
			} elsif ($_ =~ s/^\*\*(.+)//) {
				$text .= "<$heading[1]$self->{color}>$1</$heading[1]>";
			} elsif ($_ =~ s/^\*(.+)//) {
				$text .= "<$heading[0]$self->{color}>$1</$heading[0]>";
			} elsif ($_ =~ s/^\-(.+)//) {
				$text .= "<li$self->{color}>$1</li>";
			} else {
				$text .= "$_<br />";
			}
		}
	}

	my $dammy = $text;
	my $note  = 1;
	my @note;
	while ($dammy =~ s/(.*)\(\((.+)\)\)(.*)//) {
		my $dammy1 = $1;
		my $dammy2 = $2;
		my $dammy3 = $3;
		if ($dammy1 !~ s/\)$// or $dammy3 !~ s/^\(//) {
			$note++;
		}
		$dammy = "$dammy1$dammy3";
	}
	while ($text =~ s/(.*)\(\((.+)\)\)(.*)//) {
		my $text1 = $1;
		my $text2 = $2;
		my $text3 = $3;
		if ($text1 =~ s/\)$// and $text3 =~ s/^\(//) {
			$text = "$text1(\a($text2)\a)$text3";
		} else {
			$note--;

			unshift(@note, "<a href=\"#$self->{article}text$note\" id=\"$self->{article}note$note\">*$note</a>：$text2");

			$text2 =~ s/<[^>]*>//g;
			$text  = "$text1<a href=\"#$self->{article}note$note\" id=\"$self->{article}text$note\" title=\"$text2\">*$note</a>$text3";
		}
	}
	$text =~ s/<\a>/((/g;
	$text =~ s/<\/\a>/))/g;
	$text =~ s/\a//g;

	$text =~ s/\t/\n/g;
	$text = "<p$self->{color}><br />$text<br /></p>";

	$text =~ s/<pre>/<\/p><pre>/g;
	$text =~ s/\n<\/pre>/<\/pre><p$self->{color}>/g;
	$text =~ s/\n<\/code><\/pre>/<\/code><\/pre><p$self->{color}>/g;

	$text =~ s/<blockquote>/<\/p><blockquote><p$self->{color}>/g;
	$text =~ s/<\/blockquote>/<\/p><\/blockquote><p$self->{color}>/g;

	$text =~ s/<br \/><tr>/<\/p><table><tr>/g;
	$text =~ s/<p$self->{color}><tr>/<table><tr>/g;
	$text =~ s/<\/tr><br \/>/<\/tr><\/table><p$self->{color}>/g;
	$text =~ s/<\/tr><\/p>/<\/tr><\/table>/g;

	$text =~ s/<br \/><dt$self->{color}>/<\/p><dl><dt$self->{color}>/g;
	$text =~ s/<p$self->{color}><dt$self->{color}>/<dl><dt$self->{color}>/g;
	$text =~ s/<\/dd><br \/>/<\/dd><\/dl><p$self->{color}>/g;
	$text =~ s/<\/dd><\/p>/<\/dd><\/dl>/g;

	$text =~ s/<$heading[2]$self->{color}>/<\/p><$heading[2]$self->{color}>/g;
	$text =~ s/<\/$heading[2]>/<\/$heading[2]><p$self->{color}>/g;

	$text =~ s/<$heading[1]$self->{color}>/<\/p><$heading[1]$self->{color}>/g;
	$text =~ s/<\/$heading[1]>/<\/$heading[1]><p$self->{color}>/g;

	$text =~ s/<$heading[0]$self->{color}>/<\/p><$heading[0]$self->{color}>/g;
	$text =~ s/<\/$heading[0]>/<\/$heading[0]><p$self->{color}>/g;

	$text =~ s/<br \/><li$self->{color}>/<\/p><ul><li$self->{color}>/g;
	$text =~ s/<p$self->{color}><li$self->{color}>/<ul><li$self->{color}>/g;
	$text =~ s/<\/li><br \/>/<\/li><\/ul><p$self->{color}>/g;
	$text =~ s/<\/li><\/p>/<\/li><\/ul>/g;

	if ($self->{paragraph}) {
		$text =~ s/<br \/><br \/>/<\/p><p$self->{color}>/g;
	}

	$text =~ s/<p$self->{color}><br \/>/<p$self->{color}>/g;
	$text =~ s/<br \/><\/p>/<\/p>/g;
	$text =~ s/<p$self->{color}><\/p>//g;

	if ($note[0]) {
		$text .= "<p$self->{color}>" . join('<br />', @note) . '</p>';
	}

	$self->{string} = $text;

	return $self->{string};
}

### データ取得
sub get_string {
	my $self = shift;

	return $self->{string};
}

1;
