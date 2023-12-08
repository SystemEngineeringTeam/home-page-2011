#webliberty::App::Rss.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Rss;

use strict;
use base qw(webliberty::Basis);
use webliberty::Plugin;
use webliberty::App::Diary;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
		init   => shift,
		config => shift,
		query  => shift,
		html   => undef,
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

### RSS表示
sub output {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	if (!$self->{config}->{site_url}) {
		my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});
		$self->error('サイトのURLが設定されていません。');
	}

	opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
	my @dir = sort { $b <=> $a } readdir(DIR);
	closedir(DIR);

	my $info_path;
	if ($self->{config}->{html_archive_mode}) {
		if ($self->{init}->{archive_dir} =~ /([^\/\\]*\/)$/) {
			$info_path = "$self->{config}->{site_url}$1";
		}
	} else {
		if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
			$info_path = "$self->{config}->{site_url}$1";
		}
	}

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	my(%name, @items, $last_date, $i);

	#プロフィールから名前を取得
	if ($self->{config}->{profile_mode}) {
		open(FH, $self->{init}->{data_profile}) or $self->error("Read Error : $self->{init}->{data_profile}");
		while (<FH>) {
			chomp;
			my($user, $name, $text) = split(/\t/);

			$name{$user} = $name;
		}
		close(FH);
	}

	my $flag = 1;

	#記事データ取得
	foreach my $entry (@dir) {
		if ($entry !~ /^\d\d\d\d\d\d\.$self->{init}->{data_ext}$/) {
			next;
		}
		if ($flag) {
			open(FH, "$self->{init}->{data_diary_dir}$entry") or $self->error("Read Error : $self->{init}->{data_diary_dir}$entry");
			while (<FH>) {
				chomp;
				my($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host) = split(/\t/);

				if (!$stat) {
					next;
				}

				if ($self->{config}->{rss_field_list}) {
					my $flag;
					foreach my $field_list (split(/<>/, $self->{config}->{rss_field_list})) {
						if (!$field_list) {
							next;
						}

						if ($field_list =~ /^(.+)::(.+)$/) {
							if ($field eq "$1<>$2") {
								$flag = 1;
								last;
							}
						} else {
							if ($field =~ /^$field_list(<>.+)?$/) {
								$flag = 1;
								last;
							}
						}
					}
					if (!$flag) {
						next;
					}
				}

				$i++;
				if ($i > $self->{config}->{rss_size}) {
					$flag = 0;
					last;
				}

				if (!$last_date) {
					$last_date = $date;
				}
				if ($name{$name}) {
					$name = $name{$name};
				}

				my $link = $id ? $id : $no;
				if ($self->{config}->{html_archive_mode}) {
					if ($self->{init}->{archive_path}) {
						$link = "$self->{init}->{archive_path}$link\.$self->{init}->{archive_ext}";
					} else {
						$link = "$info_path$link\.$self->{init}->{archive_ext}";
					}
				} else {
					if ($id) {
						$link = "$info_path?id=$link";
					} else {
						$link = "$info_path?no=$link";
					}
				}

				if ($self->{config}->{rss_mode} != 2) {
					$text =~ s/\$FILE(\w+)/FILE/g;
					$text =~ s/\$PAINT(\w+)/PAINT/g;
					$file = '';
				}

				my %article = $diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host);

				if ($article{'ARTICLE_FILES'}) {
					$article{'ARTICLE_TEXT'} = '<p>' . $article{'ARTICLE_FILES'} . '</p>' . $article{'ARTICLE_TEXT'};
				}

				my $item;
				$item->{no}   = $no;
				$item->{name} = $name;
				$item->{subj} = $subj;
				$item->{link} = $link;
				$item->{text} = $article{'ARTICLE_TEXT'};
				$item->{date} = $date;

				push(@items, $item);
			}
			close(FH);
		}
	}

	my $rdf_about;
	if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
		$rdf_about = "$self->{config}->{site_url}$1?mode=rss";
		$rdf_about =~ s/~/%7E/g;
	}

	my $rdf_link;
	if ($self->{config}->{html_index_mode}) {
		$rdf_link = $self->{config}->{site_url};
	} else {
		if ($self->{init}->{script_file} =~ /([^\/\\]*)$/) {
			$rdf_link = $self->{config}->{site_url} . $1;
		}
	}

	if ($last_date =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
		$last_date = "$1-$2-$3" . "T$4:$5:00+09:00";
	}

	#データ出力
	print <<"_RSS_";
Content-Type: text/xml; charset=utf-8

<?xml version="1.0" encoding="utf-8"?>
<rdf:RDF
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns="http://purl.org/rss/1.0/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:cc="http://web.resource.org/cc/"
	xml:lang="ja">
	<channel rdf:about="$rdf_about">
		<title>$self->{config}->{site_title}</title>
		<link>$rdf_link</link>
		<description>$self->{config}->{site_description}</description>
		<dc:language>ja</dc:language>
		<dc:date>$last_date</dc:date>
		<items>
			<rdf:Seq>
_RSS_

	foreach (@items) {
		$_->{link} =~ s/~/%7E/g;

		print <<"_RSS_";
			<rdf:li rdf:resource="$_->{link}" />
_RSS_
	}

	print <<"_RSS_";
			</rdf:Seq>
		</items>
	</channel>
_RSS_

	foreach (@items) {
		my $string_ins = new webliberty::String($_->{text});
		$string_ins->replace_string('<[^>]*>', '');
		my $description = $string_ins->trim_string($self->{config}->{rss_length}, '...');

		$description =~ s/&/&amp;/g;
		$description =~ s/</&lt;/g;
		$description =~ s/>/&gt;/g;

		if ($_->{date} =~ /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/) {
			$_->{date} = "$1-$2-$3" . "T$4:$5:00+09:00";
		}

		print <<"_RSS_";
	<item rdf:about="$_->{link}">
		<title>$_->{subj}</title>
		<link>$_->{link}</link>
		<description>$description</description>
_RSS_

		if ($self->{config}->{rss_mode}) {
			print <<"_RSS_";
		<content:encoded><![CDATA[$_->{text}]]></content:encoded>
_RSS_
		}

		print <<"_RSS_";
		<dc:date>$_->{date}</dc:date>
_RSS_

		if ($self->{config}->{user_mode}) {
			print <<"_RSS_";
		<dc:creator>$_->{name}</dc:creator>
_RSS_
		}

			print <<"_RSS_";
	</item>
_RSS_
	}

	print <<"_RSS_";
</rdf:RDF>
_RSS_

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

1;
