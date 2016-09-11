# analysis of the get\_iss\_list\_html() & get\_series\_issue\_list() functions

from the get\_cvr\_dt.php script

## get\_iss\_list\_html()

This function uses curl to download the specified URL and saving the response headers into a tempoarary file.  
The html is returned as a string by the funciton

### sys\_get\_temp\_dir()

Returns directory path used for temporary files  

### tempnam( string $dir , string $prefix )

Create file with unique file name
dir  
The directory where the temporary filename will be created.  
prefix  
The prefix of the generated temporary filename.  

called with params of: sys\_get\_temp\_dir(), "http"
used to create a temp file to write the response headers to.

### curl_setopt()

CURLOPT\_USERAGENT	
The contents of the "User-Agent: " header to be used in a HTTP request.  
set to --> 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10\_6\_6; en-us) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4');

CURLOPT\_RETURNTRANSFER	 
TRUE to return the transfer as a string of the return value of curl\_exec() instead of outputting it out directly.  
set to --> true

CURLOPT_WRITEHEADER	 
The file that the header part of the transfer is written to.  
set to --> file stream resource opened with fopen()

### logic

1. a temp file is created to write the response headers to
2. curl\_setopt() is used to set the options above
3. curl\_exec() is called to get the html file
4. all \n and \r characters are removed from the html
5. read the headers into an array & check the 1st one (index 0) to verify the http status code is 200

**step 4 above is very important so that we can user regex later to search across all text**

## get\_series\_issue\_list()

uses php regular expression and matching functions

#### preg\_match

preg\_match ( string $pattern , string $subject [, array &$matches [, int $flags = 0 [, int $offset = 0 ]]] )

pattern  
The pattern to search for, as a string.

subject  
The input string.

matches  
If matches is provided, then it is filled with the results of search. $matches[0] will contain the text that matched the full pattern, $matches[1] will have the text that matched the first captured parenthesized subpattern, and so on.

flags  
flags can be the following flag:

PREG_OFFSET_CAPTURE  
If this flag is passed, for every occurring match the appendant string offset will also be returned. Note that this changes the value of matches into an array where every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
offset
Normally, the search starts from the beginning of the subject string. The optional parameter offset can be used to specify the alternate place from which to start the search (in bytes).

### get the table html for the issue rows

preg\_match('/\<table border="0" cellpadding="1" cellspacing="0">(.*)\<\\/table>\<br>\<br>\<br>/', $html, $matches);  

if the the regex does not match then  
exits the scrpt  
else  
$table\_html = $matches[1];  
end if

### put all html issue rows into an array

preg\_match\_all('/\<tr>(.*?)\<\\/tr>/', $table_html, $matches);

$rows\_html\_array = $matches[1];  
array\_shift($rows\_html\_array);

### loop over each issue row in $rows\_html\_array

#### parse out each html cell from the html issue row

preg_match_all('/\<td.*?' . '>(.*?)\<\\/td>/', $row_html, $matches);  
$cells\_html\_array = $matches[1];

$row\_type\_ind = null;
$cbdb\_iss\_id = null;
$var\_desc = 'Std Variant';
$date = null;


#### process cell #1: $cells\_html\_array[0]

preg\_match('/\<a href="issue.php\\?ID=(\\d*)".*>(.*?)\<\\/a>|&nbsp;/', $cells_html_array[0], $matches);  

if count($matches) == 3, then  
non-varient issue (NV)  
$var\_seq = 1;  
$cbdb_\iss\_id = $matches[1];  
$iss\_num = $matches[2];  
$iss\_num = calc_iss_num($iss\_num, $cbdb\_iss\_id, $dups\_ctr);  
$story\_arc\_id = null;  
$story\_arc\_name = null;  
if count($matches) == 1, then no-op  
else error

#### process cell #3: $cells\_html\_array[2]

preg\_match('/\<a href="issue.php\\?ID=(\\d*)".*>\\(?(.*?)\\)?\<\\/a>|&nbsp;/', $cells_html_array[2], $matches);  

if count($matches) == 3, then  
if non-varient issue then  
$iss\_name = $matches[2];  
else  
varient issue (VAR)  
$cbdb\_iss\_id = $matches[1];  
$var\_desc = $matches[2];  
if count($matches) == 1, then  
multiple story arcs (MSA)  
$var\_desc = "";  
else error

#### process cell #5: $cells\_html\_array[4]

preg\_match('/\<a href="storyarc.php\\?ID=(\\d*)".*>(.*?)\<\\/a>|&nbsp;/', $cells_html_array[4], $matches);

if count($matches) == 3, then  
$story\_arc\_id = $matches[1];  
$story\_arc\_name = $matches[2];   
if count($matches) == 1, then no-op  
else error

#### process cell #7: $cells\_html\_array[6]

if ('NV' || 'VAR') {  
$date\_str = (strtoupper(substr($cells\_html\_array[6], -4)) == '\<BR>' ?  
substr($cells\_html\_array[6], 0, -4) :   
$cells\_html\_array[6]);  
$date\_array = explode(' ', $date\_str);  


			
				// strip of the trailing <BR> tag if there is one
               $date_str = (strtoupper(substr($cells_html_array[6], -4)) == '<BR>' ? 
                       trim(substr($cells_html_array[6], 0, -4)) : 
                       trim($cells_html_array[6]));
						
				// break the string into its components 
				// if 2 elements --> month (or another word) & year
				// if 3 elements --> {early | late }, month & year
				$date_array = explode(' ', $date_str);
			
				// if only 1 element exists it is the year
				if (count($date_array) == 1) {
					$date_disp = $date_array[0];
					$date = "$date_disp-01-01";
				} elseif (count($date_array) == 3) {
					$day = ( strtoupper($date_array[0]) == 'LATE' ? 15 : 1 );
					$time_stamp = strtotime("{$date_array[1]} {$date_array[2]}");
					$date = date('Y-m', $time_stamp) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
					$date_disp = $date_array[0] . ' ' . date('M-Y', $time_stamp);
				// else if the first word is annual then we only have the year
				} elseif (strtoupper($date_array[0]) == 'ANNUAL') {
					$date_disp = $date_array[1];
					$date = "$date_disp-01-01";
				// else have just month and year
				} else {
					$time_stamp = strtotime($date_str);
					$date = date('Y-m', $time_stamp) . '-01';
					$date_disp = date('M-Y', $time_stamp);
				}
}  
			
if (MSA)  
continue

			$issues_list[] = array(
				'isi_issue_id'			=> $cbdb_iss_id,
				'isi_issue_type'		=> $row_type_ind,
				'isi_issue_num'			=> $iss_num,
				'isi_issue_name'		=> ( strlen($iss_name) == 0 ? null : $iss_name ),
				'isi_variant_seq'		=> $var_seq++,
				'isi_variant_desc'		=> ( strlen($var_desc) == 0 ? null : $var_desc ),
				'isi_cover_dt_disp'		=> $date_disp,
				'isi_cover_dt'			=> $date,
				'isi_story_arc_id'		=> ( strlen($story_arc_id)   == 0 ? null : $story_arc_id ),
				'isi_story_arc_name'	=> ( strlen($story_arc_name) == 0 ? null : $story_arc_name )
			);
