#webliberty::Date.pm (2006/11/14)
#Copyright(C) 2002-2006 Knight, All rights reserved.

package webliberty::Date;

use strict;

### コンストラクタ
sub new {
	my $class = shift;

	my $self = {
	};
	bless $self, $class;

	return $self;
}

### 時間取得
sub get_time {
	my $self = shift;

	if ($_[0] =~ /^(\d\d\d\d)-(\d\d)-(\d\d)\s(\d\d)\:(\d\d)\:(\d\d)$/) {
		my($year, $mon, $day, $hour, $min, $sec) = ($1, $2, $3, $4, $5, $6);

		my $time = 60 * 60 * 24 * ((365 * ($year - 1) + int(($year - 1) / 4) - int(($year - 1) / 100) + int(($year - 1) / 400)) - (365 * 1969 + int(1969 / 4) - int(1969 / 100) + int(1969 / 400)));

		foreach (1 .. $mon - 1) {
			$time += 60 * 60 * 24 * ((31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31)[$_ - 1] + ($_ == 2 and (($year % 4 == 0 and $year % 100 != 0) or $year % 400 == 0)));
		}
		$time += 60 * 60 * 24 * ($day - 1);
		$time += 60 * 60 * $hour;
		$time += 60 * $min;
		$time += $sec;

		$time -= 60 * 60 * (localtime(0))[2];
		$time -= 60 * (localtime(0))[1];

		return $time;
	} else {
		return;
	}
}

### 日数取得
sub get_interval {
	my $self = shift;

	my($date1, $date2);
	if ($_[0] =~ /^(\d\d\d\d)-(\d\d)-(\d\d)$/) {
		$date1 = $self->get_time("$1-$2-$3 00:00:00");
	} else {
		return;
	}
	if ($_[1] =~ /^(\d\d\d\d)-(\d\d)-(\d\d)$/) {
		$date2 = $self->get_time("$1-$2-$3 00:00:00");
	} else {
		return;
	}

	return int(abs($date1 - $date2) / (60 * 60 * 24));
}

### 曜日取得
sub get_week {
	my $self = shift;

	if ($_[0] =~ /^(\d\d\d\d)-(\d\d)-(\d\d)$/) {
		my($year, $month, $day) = ($1, $2, $3);

		if ($month == 1 or $month == 2) {
			$year--;
			$month += 12;
		}

		return int($year + int($year / 4) - int($year / 100) + int($year / 400) + int ((13 * $month + 8) / 5) + $day) % 7;
	} else {
		return;
	}
}

1;
