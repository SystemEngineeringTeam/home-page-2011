#!/usr/bin/perl

#
# メーリングリスト.
#

chdir("/home/kousato/catchmail/");

our $VERSION = "1.0";
our $RELEASE = "2009/11/30";

use strict "vars";
use strict "subs";
use MIME::Parser;
use HTML::Template::Pro;
use MIME::Base64;
use Jcode;
require "koulib_5.6.pl";

my $lib = new KouLib(
		'set_version' => {
				'CGI_CODENAME' => 'set.ml_list','CGI_VERISON' => $VERSION ,'CGI_RELEASE' => $RELEASE,
				'CGI_NAME' => 'AIT System Engineering Team - Admin Group - Mailing-List LIST??'
			},
	);
my %conf	= $lib->read_config("./main.conf");
my $mls		= $lib->read_serialize("$conf{'file'}{'save_dir'}/$conf{'file'}{'mls'}");
my $items = ['from','to','date','subject','filenum'];

my $self_uri = "./maillist.cgi";

$lib->cgi_initialize();
&main();

sub main() {
	my $mode = $lib->get_query('mode');
	
	if ($mode eq 'list') {
		&show_maillist();
	} elsif ($mode eq 'show') {
		&show_mailbody();
	} else {
		&show_ml_list();
	}
}
sub show_ml_list() {
	my $tmpl = &HTML_Template('mlselect');
	my $mkstr = $lib->make_string($lib->{'data_query'},'mode',$self_uri);
	
	my @loop_data = ();
	foreach my $name (sort keys %$mls) {
		push(@loop_data,{
				'name'		=> sprintf("%s (%s)",$conf{'ml_names'}{$name},$name),
				'uri'		=> $mkstr->make('list',{'mlname' => $name}),
				'postcnt'	=> $mls->{$name}{'postcnt'},
				'lastpost'	=> $lib->spformat("&dateweektime",$mls->{$name}{'lastpost'}),
			});
	}
	$tmpl->param('MAIL_LIST' => \@loop_data);
	print "Content-Type: text/html\n\n" , $tmpl->output;
}#sub end.
sub show_maillist() {
	my $ml_name = $lib->get_query('mlname');
	$lib->error_page("$ml_name というメーリングリストは存在しません.") unless (ref $mls->{$ml_name});
	
	my $tmpl = &HTML_Template('maillist');
	my $mkstr	= $lib->make_string($lib->{'data_query'},'mode',$self_uri);
	my $read	= $lib->open('ro',"$conf{'file'}{'save_dir'}/${ml_name}.idx");
	
	$tmpl->param('ML_DISPNAME' => $conf{'ml_names'}{$ml_name} , 'ML_NAME' => $ml_name , 'BACK_URI' => $mkstr->make(undef(),undef(),['mlname']) );
	
	my @loop_data = ();
	foreach my $in ($read->readline("\t",$items)) {
		push(@loop_data,{
				'from'		=> $in->{'from'},
				'uri'		=> $mkstr->make('show',{'num' => $in->{'filenum'}}),
				'subject'	=> $in->{'subject'},
				'date'		=> $in->{'date'}
			});
	}
	$tmpl->param('OLD_MAIL_LIST' => \@loop_data);
	$read->close();
	print "Content-Type: text/html\n\n" , $tmpl->output;
}#sub end.
sub show_mailbody {
	my($ml_name,$filenum) = $lib->get_query('mlname','num');
	
	$lib->error_page("$ml_name というメーリングリストは存在しません.") unless (ref $mls->{$ml_name});
	$lib->error_page("$filenum というメールは存在しません.") if (!$filenum || !-r "$conf{'file'}{'save_dir'}/${filenum}.eml");
	
	my $tmpl = &HTML_Template('mailviewer');
	my $mail_buf = $lib->open('r',"$conf{'file'}{'save_dir'}/${filenum}.eml",'raw') ||
			$lib->error_page("メールファイルを開けませんでした. File : $conf{'file'}{'save_dir'}/${filenum}.eml");
	my $mkstr	= $lib->make_string($lib->{'data_query'},'mode',$self_uri);
	
	my $parse = new MIME::Parser;
	$parse->output_to_core(1);
	$parse->decode_headers(1);
	my $entity = $parse->parse_data($mail_buf);
	my $head = $entity->head;
	
	my $charset = 
		($head->get('Content-Type') =~ m|Text/plain; +charset=(.+)|i)? $1:
		'jis';
	
	$tmpl->param('ML_DISPNAME' => $conf{'ml_names'}{$ml_name} , 'ML_NAME' => $ml_name , 'BACK_URI' => $mkstr->make('list',undef(),['num']));
	my @loop_data = ();
	foreach ('From','Date','To','Subject') {
		$tmpl->param( lc($_) => jcode($head->get( lc $_ ),$charset)->sjis());
		push(@loop_data,{'name' => $_ , 'value' => jcode($head->get( lc $_ ),$charset)->sjis() } );
	}

	my $body = 
		($head->get('Content-Transfer-Encoding') =~ /base64/ )? jcode( decode_base64 ( join("" , @{$entity->body}) ) , $charset)->sjis() :
		jcode( join("" , @{$entity->body}) , $charset)->sjis();
	
	$tmpl->param(
			'mail_header'	=> \@loop_data,
			'mail_body'		=> $lib->strbuf( $body )->tagclash()->autolink()->newlinecode_to_tag()->print()
		);
	print "Content-Type: text/html\n\n" , $tmpl->output;
}#sub end.
sub HTML_Template {
	my $Template = shift;
	local $@;
	my $fname = "$conf{'template'}{'tmpl_dir'}/${Template}.tmpl";
	$lib->error_page("HTML::Template エラー. テンプレートファイルが存在しません. Template : [$fname]",1) if (!-r $fname || $fname eq '/');
	my $oTemplate = HTML::Template->new(
			filename => $fname, strict=> 0 , die_on_bad_params => 0 , html_template_root => $conf{'template'}{'tmpl_dir'},
			filter => sub {my $tmp = shift; $$tmp =~ s/___(.*?)___/<TMPL_VAR NAME="$1">/g;}
		);
	
	# パラメータ出力.
#	$oTemplate->param( 'CGI_VERSION'=>$lib->get_version('CGI_VER_HTML'), 'SELF_URI' => $self_uri, 'ERROR_MSG' => $lib->get_sysmsg() );
#	$oTemplate->param( 'DEBUG_VALUES'  =>  &::debug(-1) , 'DEBUG_MODE' => 1 ) if ($::DEBUG_LEVEL);
	return $oTemplate;
}#sub end.
