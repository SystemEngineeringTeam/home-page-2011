#!/usr/local/bin/perl

print "Content-type: text/plain\r\n\r\n";


while (@in = each %ENV) {
	print "$in[0]\t$in[1]\r\n";
}
