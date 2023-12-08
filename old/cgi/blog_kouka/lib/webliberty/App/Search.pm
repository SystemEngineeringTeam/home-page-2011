#webliberty::App::Search.pm (2007/12/15)
#Copyright(C) 2002-2007 Knight, All rights reserved.

package webliberty::App::Search;

use strict;
use base qw(webliberty::Basis);
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

	$self->output_form;

	return;
}

### 検索フォーム
sub output_form {
	my $self = shift;

	my $plugin_ins;
	if (!$self->{update}->{plugin}) {
		$plugin_ins = new webliberty::Plugin($self->{init}, $self->{config}, $self->{query});
		%{$self->{plugin}} = $plugin_ins->run;
	}

	my $skin_ins = new webliberty::Skin;
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_header}", available => 'header');
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_search}");
	$skin_ins->parse_skin("$self->{init}->{skin_dir}$self->{init}->{skin_footer}", available => 'footer');

	my $diary_ins = new webliberty::App::Diary($self->{init}, $self->{config}, $self->{query});

	$skin_ins->replace_skin(
		$diary_ins->info,
		%{$self->{plugin}}
	);

	my(%field, $form_field, $form_field_start, $form_field_end, $i);
	if ($self->{config}->{use_field}) {
		open(FH, $self->{init}->{data_field}) or $self->error("Read Error : $self->{init}->{data_field}");
		while (<FH>) {
			chomp;
			my($field, $child) = split(/<>/);

			$field{++$i} = $_;

			if ($child) {
				$field = "└ $child";
			}

			if ($self->{query}->{field} eq $_ or $self->{query}->{field} == $i) {
				$form_field .= "<option value=\"$i\" selected=\"selected\">$field</option>";
			} else {
				$form_field .= "<option value=\"$i\">$field</option>";
			}
		}
		close(FH);
	} else {
		$form_field_start = '<!--';
		$form_field_end   = '-->';
	}

	my($cond_and, $cond_or);
	if ($self->{query}->{cond} eq 'or') {
		$cond_and = '';
		$cond_or  = ' selected="selected"';
	} else {
		$cond_and = ' selected="selected"';
		$cond_or  = '';
	}

	$self->{html}->{header}   = $skin_ins->get_data('header');
	$self->{html}->{contents} = $skin_ins->get_replace_data(
		'contents',
		FORM_WORD        => $self->{query}->{word},
		FORM_FIELD       => $form_field,
		FORM_FIELD_START => $form_field_start,
		FORM_FIELD_END   => $form_field_end,
		FORM_COND_AND    => $cond_and,
		FORM_COND_OR     => $cond_or
	);
	$self->{html}->{footer} = $skin_ins->get_data('footer');

	if ($self->{query}->{word}) {
		my $words = $self->{query}->{word};
		$words =~ s/　/ /g;
		my @words = split(/\s+/, $words);

		opendir(DIR, $self->{init}->{data_diary_dir}) or $self->error("Read Error : $self->{init}->{data_diary_dir}");
		my @dir = sort { $b <=> $a } readdir(DIR);
		closedir(DIR);

		my $flag = 1;

		$self->{html}->{navi}  = $skin_ins->get_data('navi_head');
		$self->{html}->{navi} .= $skin_ins->get_data('navi');
		$self->{html}->{navi} .= $skin_ins->get_data('navi_foot');

		$self->{html}->{diary} = $skin_ins->get_data('diary_head');

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
					if ($self->{query}->{field} and $field and $field{$self->{query}->{field}} ne $field) {
						next;
					}

					my $show_flag;

					foreach my $word (@words) {
						$word = lc($word);
						my $string = lc("$no\t$id\t$date\t$name\t$subj\t$text");

						if (index($string, $word) >= 0) {
							$show_flag = 1;
							if ($self->{query}->{cond} eq 'or') {
								last;
							}
						} else {
							if ($self->{query}->{cond} eq 'and') {
								$show_flag = 0;

								last;
							}
						}
					}

					if ($show_flag) {
						$self->{html}->{diary} .= $skin_ins->get_replace_data(
							'diary',
							$diary_ins->diary_article($no, $id, $stat, $break, $comt, $tb, $field, $date, $name, $subj, $text, $color, $icon, $file, $host)
						);
					}
				}
				close(FH);
			}
		}

		$self->{html}->{diary} .= $skin_ins->get_data('diary_foot');
	}

	print $self->header;
	foreach ($skin_ins->get_list) {
		print $self->{html}->{$_};
	}

	if (!$self->{update}->{plugin}) {
		$plugin_ins->complete;
		$self->{update}->{plugin} = 1;
	}

	return;
}

1;
