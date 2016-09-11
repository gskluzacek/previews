# analysis of the get\_iss\_list\_html() & get\_series\_issue\_list() functions

from the get\_cvr\_dt.php script

## get\_iss\_list\_html()

This function uses curl to download the specified URL and saves it into a tempoarary file

### sys\_get\_temp\_dir()

Returns directory path used for temporary files  

### tempnam( string $dir , string $prefix )

Create file with unique file name
dir  
The directory where the temporary filename will be created.  
prefix  
The prefix of the generated temporary filename.  

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

## get\_series\_issue\_list()
