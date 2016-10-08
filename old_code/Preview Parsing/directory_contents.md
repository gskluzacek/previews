# directory Contents

**For Directory:** /previews/old_code/Preview Parsing/previews parsing

## This directory contains several folders and many Files

**folders:**
* originals
* pdf
* temp
* test
* yyyy-mm_links 

## Most useful looking files:

* headings.xlsx
* lf.PHP
* pp.PHP		see output file: pp_out.txt
* tt.php		see output file: tt_out.txt
* file: `bipolar_2014_08_16.sql`
* 	table: previews_hdg_lvls
* 	table: previews_raw
* 	table: previews_lines
* 	table: previews_hdr

***

## PHP source code - main directory

**Most interesting PHP scripts**
* lf.PHP
* pp.PHP
* tt.php


### db_common.inc.php
contains a single function named `crtsql()` which will take an input sql string with place
holders and return a SQL statement with the 


### lf.php
reads all the COF files and loads them into the database

**loads the folloing tables:**
* reviews_hdr   (period_dt, period_str, ident_str, local_file, url_to_cof) 
* previews_lines (pvh_id, pvl_seq, line_text)

one line of text _(unaltered)_ from the COF file is inserted per record into the line_text column.


### tt.php
_see output file: tt_out.txt_

looks like this code is for proto-typing the parsing of the solicitation text

it reads the sol_text field from the previews_raw table.

It then parses it and populates various fields and prints its output to STDOUT
1. title
1. issue_num
1. total_issues
1. title_type
1. printing
1. caution_code
1. advisory_code
1. sol_info_code
1. other_designations
1. cover_variant
1. prev_sol_code
1. packaged_set
1. other_tokens


### pp.php
_see output file: pp_out.txt_

This program reads the previews_lines table _(by pvh_id)_ and splits it into fields _(tab 
delimited)_. It sets the sol_text to `[ndx-2]` _(aka the 3rd field)_ without any processing or 
parsing. It then performs minor parsing to populate the other fields.

`select pvh_id from previews_hdr where proc_status in ('NEW', 'REPROCESS') order by period_dt
for each pvh_id
  select pvl_id, pvl_seq, line_text, override_pvhl_id from previews_lines where pvh_id = ? order by pvl_seq
  for each line
  	parse the line
  	insert into the previews_raw table populating the following columns:
  		- pvr_id [auuto increment]
  		- pvh_id
  		- pv_seq
  		- pvl_id
  		- pv_type
  		- pv_value
  		- h1_pvhl_id
  		- h2_pvhl_id
  		- h3_pvhl_id
  		- pv_source
  		- sol_page
  		- sol_code
  		- sol_text
  		- release_dt
  		- unit_price
  		- pi_ind
`

str2dt.php
-----
looks like some isolated code that was being developed and tested...

takes a string and attempts to convert it to a date


dates.php
-----
looks like some isolated code that was being developed and tested...

processes a PHP array litteral that contains the COF text file names (without .txt suffix)
for each item, it converts it to a date (fmt: Y-m-d) and stores it in an array
sorts the array by key, and prints it out


t.php
-----
looks like some isolated code that was being developed and tested...
the code is using the mysqli MySQL db interface to execute some sql that contains place holders
I think, I was trying to figure out how to use the mysqli MySQL db interface
place holders replaced with escaped values




-------------------------------------------------
SQL Files - main directory
-------------------------------------------------
file: `bipolar_2014_08_16.sql`

This is a dump of a MySQL database.
Containing Table Structures and data for:

note: PL stands for pull list


tables
------
code_hdr
code_values
code_attribs

users
pull_lists
pl_series
pl_publishers

previews_hdg_lvls
previews_hdr
previews_lines
previews_raw



all tables except as noted have the following columns
-----------------------------------------------------
crt_dt
crt_id
updt_dt (*)
updt_id



Most interesting tables
-----------------------
previews_hdg_lvls
previews_raw
previews_lines
previews_hdr


code_attribs
------------
ca_id			PK
cv_id			FK -- code_values
attrib_name
attrib_value

code_hdr
--------
ch_id			PK
code_type
code_desc

code_values
-----------
cv_id			PK
ch_id			FK -- code_hdr
code_grp
code_key
code_val

pl_publishers
-------------
ppub_id			PK
pub_abrev
pub_short_name
pub_long_name
UNIQUE on:: pub_abrev, pub_short_name

pl_series
---------
pser_id			PK
ppub_id			FK -- pl_publishers
ser_display_name
ser_sort_name
ser_year
indicia_name
ser_group
ser_url

previews_hdg_lvls *
-----------------
pvhl_id			PK
pvhl_level
parent_pvhl_id	FK -- previews_hdg_lvls
pull_list_ind
heading_name
UNIQUE on:: parent_pvhl_id, heading_name

What is pull_list_ind and how is it used?

previews_hdr *
------------
pvh_id			PK
period_dt
period_str
ident_str
local_file
url_to_cof
proc_status

a table of when each COF file was processed


previews_lines *
--------------
pvl_id			PK
pvh_id			FK -- previews_hdr
pvl_seq
line_text
override_pvhl_id		if set and is not 0, then use revers heading lookup (??? need more info on what this is)
UNIQUE on:: pvh_id, pvl_seq

What is override_pvhl_id and how is it used?

previews_raw *
------------
pvr_id			PK
pvh_id			FK -- previews_hdr (redundent)
pv_seq				incremented for each line processed`
pvl_id			FK -- previews_lines
pv_type				* see below
pv_value			[ndx-0]
h1_pvhl_id		FK -- previews_hdg_lvls		heading lookup on pv_value
h2_pvhl_id		FK -- previews_hdg_lvls		heading lookup on pv_value
h3_pvhl_id		FK -- previews_hdg_lvls		heading lookup on pv_value
pv_source			heading 1 / heading 2 / heading 3
sol_page			* pv_value
sol_code			[ndx-1] non-blank for ITEM pv_type(s), blank for all other pv_type(s)
sol_text			[ndx-2]
release_dt			[ndx-3]
unit_price			[ndx-4] unit_price & pi_ind are mutually exclusive. Unit price should have a leading 'SRP' or 'MSRP' unit price type
pi_ind				'please inquire (on price)' indicator - can be NULL, Y or E (invalid unit price type)
title
sub_title
title_vol
title_type
title_status
title_designations
book_type
book_vol
book_designations
issue_num
total_issues
printing
cover_variant
cover_type
advisory_code
caution_code
sol_info_codes
prev_sol_code
edition
other_designations

pv_type(s)
~~~~~~~~~~~~~~~~~~~~~~~
102,774		ITEM			if the sol_code is populated, its an ITEM
    799		BLANK			sol_code & pv_vale are both blank
 23,017		PAGE			sol_code is blank and pv_value = PAGE
	 52		IDENT			if the solicitation code is blank and the value matches the reglare expression for the identificaiton line, then it is a identification line
  1,135		H3				if none of the above and if lvl 2 is set - check for lvl 3
  8,864		H2				if none of the above and if lvl 1 or 2 is set - check for lvl 2
    702		H1				if none of the above - check for lvl 2
  2,094		NOTFOUND		if none of the above then not found





pull_lists
----------
pl_id			PK
usr_id			FK -- users
pser_id			FK -- pl_series
iss_num_start
variant_ind
variant_qty
notes
status


users
-----
usr_id			PK
login
first_nm
last_nm
email
email_sts
password
last_login_dt
acct_sts
role
twitter
facebook
bio
phone_home
phone_mobile
phone_work
address_1
address_2
city
state
zip
county
country
sec_question
sec_answer
pull_list_sts
resume_dt
pull_list_notes
UNIQUE on:: login
UNIQUE on:: email






-------------------------------------------------
TEXT Files - main directory
-------------------------------------------------
dates.txt
---------
a PHP script (with out the <?php & ?> tags) with an array litteral 
that contains the COF text file names (without .txt suffix)

pp_out.txt
----------
appears to be a print out of the "PREVIEWS JAN VOL 19 #01" COF text File
with each line read in, then split into an array & printed out with a line counter 
printed before the array Contents

lines have either: 1 or 7 elements
-- for lines with 7 elements:
. appears to be the contents of each column from the COF
. the last element is either blank or `=$`

solicitations text codes.txt
----------------------------
appears to be some notes and examples on parsing the details of the COF

tt_out.txt
----------
appears to be a tab delimited file with the columns from the NOV09_COF.txt file?



-------------------------------------------------
COF Text Files - main directory
-------------------------------------------------
with the exception of the 1 file below, the other COF file names match the ones in the 
`Archive.zip` file and in the `originals/Archive 2` directory.

AUG14_COF.txt



-------------------------------------------------
Excel workbooks - main directory
-------------------------------------------------
headings.xlsx
-------------
This is a listing (from what?) of all the headings within a COF. There are up to 
3 levels of headings. 

Heading of interest are: 

COMICS
With level 2 headings of the various publishers. Some of the publishers under 
the Comics level 1 heading, have additional level 3 sub-headings.

PREMIER PUBLISHERS
with 1 or 2 level 2 headings per publisher. Most if not all Premier publishers will
have multiple level 3 sub-Heading.

PREVIEWS PUBLICATIONS ???
- contains info on stuff that Black Diamond publishes.


titles and token parsing.xlsx
-----------------------------
looks to be extracts used parsing analysis

row count: 26,365
columns:
test	
title	
issue	
of_num	
printing	
caution	
advisory	
sol info	
other	
variants	
prev sol	
sub title	
x9

~$titles and token parsing.xlsx
-------------------------------
appears to be a working file for excel.
removing from GitHub repo


-------------------------------------------------
Archive.zip - main directory
-------------------------------------------------
contains 52 items

JAN09_COF.txt
FEB09_COF.txt
MAR09_COF.txt
APR09_COF.txt
MAY09_COF.txt
JUN09_COF.txt
JUL09_COF.txt
AUG09_COF.txt
SEP09_COF.txt
OCT09_COF.txt
NOV09_COF.txt
DEC09_COF.txt

JAN10_COF.txt
FEB10_COF.txt
MAR10_COF.txt
APR10_COF.txt
MAY10_COF.txt
JUN10_COF.txt
JUL10_COF.txt
AUG10_COF.txt
SEP10_COF.txt
OCT10_COF.txt
NOV10_COF.txt
DEC10_COF.txt

JAN11_COF.txt
FEB11_COF.txt
MAR11_COF.txt
APR11_COF.txt
MAY11_COF.txt
JUN11_COF.txt
JUL11_COF.txt
AUG11_COF.txt
SEP11_COF.txt
OCT11_COF.txt
NOV11_COF.txt
DEC11_COF.txt

JAN12_COF.txt
FEB12_COF.txt
MAR12_COF.txt
APR12_COF.txt
MAY12_COF.txt
JUN12_COF.txt
JUL12_COF.txt
AUG12_COF.txt
SEP12_COF.txt
OCT12_COF.txt
NOV12_COF.txt
DEC12_COF.txt

JAN13_COF.txt
FEB13_COF.txt
MAR13_COF.txt
APR13_COF.txt



-------------------------------------------------
folder:: originals - main directory
-------------------------------------------------
contains the following items:

a folder named `Archive 2`
a zip file named `Archive 2.zip`
a text file named `AUG14_COF.txt`

the contents of the folder & zip file look to be the same as Archive.zip in the main directory
the `AUG14_COF.txt` should be moved into the `Archive 2` directory



-------------------------------------------------
folder:: pdf - main directory
-------------------------------------------------
contains 39 items
missing most of 2011 and last 2 month of 2012

JAN09_COF.pdf
FEB09_COF.pdf
MAR09_COF.pdf
APR09_COF.pdf
MAY09_COF.pdf
JUN09_COF.pdf
JUL09_COF.pdf
AUG09_COF.pdf
SEP09_COF.pdf
OCT09_COF.pdf
NOV09_COF.pdf
DEC09_COF.pdf

JAN10_COF.pdf
FEB10_COF.pdf
MAR10_COF.pdf
APR10_COF.pdf
MAY10_COF.pdf
JUN10_COF.pdf
JUL10_COF.pdf
AUG10_COF.pdf
SEP10_COF.pdf
OCT10_COF.pdf
NOV10_COF.pdf
DEC10_COF.pdf

SEP11_COF.pdf

JAN12_COF.pdf
FEB12_COF.pdf
MAR12_COF.pdf
APR12_COF.pdf
MAY12_COF.pdf
JUN12_COF.pdf
JUL12_COF.pdf
AUG12_COF.pdf
SEP12_COF.pdf
OCT12_COF.pdf

JAN13_COF.pdf
FEB13_COF.pdf
APR13_COF.pdf
MAR13_COF.pdf


-------------------------------------------------
folder:: temp - main directory
-------------------------------------------------
contains 2 excel workbooks
looks to be extracts used to resolve parsing issues ???

Workbook6.xlsx
--------------
row count: 400
columns:
pv_source	
sol_text

Workbook2.xlsx
--------------
row count: 24,061
columns:
test	
title	
issue	
of_num	
printing	
caution	
advisory	
sol info	
other	
variants	
prev sol	
sub title



-------------------------------------------------
folder:: test - main directory
-------------------------------------------------
contains 1 excel workbook
looks to be extracts used to resolve parsing issues ???

test1.xlsx
----------
row count: 24,328
columns:
sol text	
title	
num	
tot iss	
ttl type	
printing	
caution	
advisor	
sol info	
other desg	
variant	
prev sol	
other	
x1	
x2	
x3



-------------------------------------------------
folder:: yyyy-mm_links - main directory
-------------------------------------------------
This folder previously held a set of symbolic links back to the originals folder
the links were renamed to be formated as Year Month Day, for easy sorting

however, when this folder was checked into GitHub, the links became non-functioning
hence removing these from GitHub repo
