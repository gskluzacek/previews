#!/usr/bin/perl

my $data = do { local $/; <STDIN> };
print ($data =~ /(<strong>Skip to: <\/strong>.*?<table(.*?)<\/table>)|(<table border="0" cellpadding="0" cellspacing="0" width="100%">      <tr>        <td align="left" valign="top" width="49%" nowrap>    (No results found.)<br><br>        <\/td>      <\/tr>    <\/table>)/ ? ($4 == '' ? "$2" : '') : 'ERROR');

#$data =~ /<strong>Skip to: <\/strong>.*?<table(.*?)<\/table>/;
#print "$1\n";
