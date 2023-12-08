#!/usr/local/bin/perl

print "Content-Type: text/html\n\n";
while(@in = each(%ENV)){ printf("%s = %s\n",$in[0],$in[1]);}
