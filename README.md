***

# File Catalog

**this is a list of directories and files contained in this repo a long with a description of what is in each**

***

# dir: /other/get title index

## file: working python code snipits.txt

some code and functions that look like they were used for parsing HTML from the comic book db website, which is no longer operational.

## file: title_index.py

a class which is given an id (then uses requests to pull HTML source ) or HTML source from the comic book db website for a given comic book series / Title and parses the following elements

* issue_id
* issue_num
* issue_title
* var_desc
* cover_date
* story_arc
* story_arc_id

would then print all the values that were parsed

apparently there is a sample file located: `/old_code/Preview Parsing/sample.html`

# dir: /old_code/Get Cover Date/get comic book cover date from cb db

## file: get_cvr_dt.php

This PHP script takes an input file (semi-colon delimited generated from Comic Collectorz) and gets the cover date from http://www.comicbookdb.com for each issue listed in the input file. it genearates an output with relevent match information.

_see the design.txt file for additional details._ 

### Files Used:
* input file is named:  `cz_issues_input.txt`  
* output file is named: `invoice_ouput.csv`  
* log file is named:    `out.txt`  

### Database:
apparently there is a SQLite3 database associated with the PHP script: `comics_invoicing.db`

_it would be interesting to look at this data..._

### other files:

* design.txt
  * has details 
  * on the process steps
  * steps to add a new series to the database
  * list of database tables and fields
  * description of the program logic
* cbdb example series index page.png
  * a screen shot of the web page
* create database script.sql
  * sql statements to create the database tables and associated indexes
  * insert statements for some tables
    * internet_sources
    * is_series_ids
    * series
    * sqlite_sequence
* table_create_stmts.sql
  * more sql statement to create tables and insert data into select tables
* some big ass sql queries, I think this may have been used to develop a view that is defined in the db?
  * final report query - bad.sql
  * final report query - good.sql
  * final report query.sql
* series table initial load data.xlsx
  * insert statements generated from data in the excel spreadsheet for 42 series
* invoice_ouput.xlsx
  * looks to be invoice_ouput.csv file saved in the Excel format
* 20150117 - sample comic book purchase document.xls
  * I used to create "invoices" each time I bought comic books
  * this is an esample template that I would use
* additional `cz_issues_input.txt` input files used for testing/old files?
  * cz_issues_input_test.txt
  * cz_issues_inputxx.txt
* out.html
  * appeares to be the series index for the title: Ten Grand
  * Ten Grand (2013) - Comic Book DB

## dir: /old_code/Get Cover Date/get comic book cover date from cb db/temp
### file: FF.txt
this appears to be HTML series index source saved with a text file extension for: FF (2013) - Comic Book DB

## dir: /old_code/Get Cover Date/get comic book cover date from cb db/old code
contains:
* dir: cbdb
  * publishers.php
  * series.php
  * perl script st.pl - used in series.php due to some issue with PHP regex library
* dir: includes
  * php include files used by the above
  * appl_init_inc.php
  * cbdb_inc.php
  * config_inc.php
  * countries.php
  * database_inc.php
  * i_inc.php
  * inc1.php
  * search_inc.php
  * utility.php

_Note: by looking at the includes/database_inc.php file, looks like this was a database app that used MySQL. However, I am unable to determine the db name or find any sql create table statements in the code._

# dir: /old_code/Preview Parsing

the following files are already pretty descriptive of what is in this directroy. Please refer to them for additional details.
* analysis meat.md
* analysis.md
* directory_contents.md

## dir: old_code/Preview Parsing/previews parsing

### contains the following PHP scripts:
* lf.php
* pp.php
* tt.php
* db_common.inc.php
* str2dt.php
* dates.php
* t.php

### file: bipolar_2014_08_16.sql
`old_code/Preview Parsing/previews parsing/bipolar_2014_08_16.sql`

we should load this data into a database...

contains create table statements and insert statements for tables:
* code_attributes (no inserts)
* code_hdr
* code_values
* pl_publishers
* pl_series
* previews_hdg_lvls (I)
* previews_hdr (I)
* previews_lines (I)
* previews_raw (MI)
* pull_lists
* users

(I) - interesting table  
(MI) - most interesting table

_see `../directory_contents.md` for table columns listings  
refer to the notes for pv_type(s), looks to have record counts, values for pv_type and description/logic_

### other files
* numerous MMMYY_COF.txt files
  * Archive.zip contains the same file names (don't know about state) as the cof files in the same diretory (except AUG13 & AUG14)
    * not sure of the state of these
    * have all of
      * 2009
      * 2010
      * 2011
      * 2012
    * partial
      * 2013: Jan Feb Mar Apr Aug
      * 2014: Aug
* headings.xlsx
  * a listing of the headings in hierarchical order
* titles and token parsing.xlsx
  * looks to be a dump of one of the database tables for analysis purposes
* solicitations text codes.txt
  * interesting notes regarding codes that appear in the previews text and details about collected works as GN, TPB, HC, etc.
* output files
  * pp_out.txt  
  appears to be a print out of the "PREVIEWS JAN VOL 19 #01" COF text File.  
  with each line read in, then split into an array & printed out with a line counter 
  printed before the array Contents  
  lines have either: 1 or 7 elements.  
  for lines with 7 elements:  
  appears to be the contents of each column from the COF  
  the last element is either blank or `=$`
  * tt_out.txt
    * appears to be a tab delimited file with the columns from the NOV09_COF.txt file?

### dir: /old_code/Preview Parsing/previews parsing/originals
the directory (Archive 2), the zip file and the AUG14 cof file seem to be the same as the files in the parent directory.

# dir: /slowly_changing_demensions/BiTemporal

this appears to be a Django app from a windows PC (most likely one of my old nielsen work laptops)

I must have been exploring bi-temporal tables (different from slowly changing dimensions) with this Django app. there are some very useful comments in temporal.py with regards to use cases.

see: https://en.wikipedia.org/wiki/Temporal_database

  _note: PostgreSQL version 9.2 added native ranged data types that are capable of implementing all of the features of the pgFoundry temporal contributed extension.[11][12] The PostgreSQL range types are supported by numerous native operators and functions._

it appears that an additional section has been added to this wiki page since my initial exploration of bi-temporal tables: `Using three axes: valid time, decision time, and transaction time` see: https://en.wikipedia.org/wiki/Temporal_database#Tritemporal_relations 

also see: https://en.wikipedia.org/wiki/Slowly_changing_dimension

the db.sqlite3 database is for the internal Django state management and doesn't have anything to do with the actual application

in the ./BiTemporal subdirectory, the setting.py file seems to indicate that there may have been a MYsql database named, bi_temp_1, that was locally hosted on the PC.

## subdir: /slowly_changing_demensions/BiTemporal/BiTemp1

### file: temporal.py
`/slowly_changing_demensions/BiTemporal/BiTemp1/temporal.py`

lots of good comments in this file regarding use cases and more

### file: models.py
`/slowly_changing_demensions/BiTemporal/BiTemp1/models.py`

provides details of the various tables that were to be maintained by the Django app.  
particularly interesting are the comments for the `Publishers` table

## subdir: /slowly_changing_demensions/BiTemporal/BiTemporal
minor tidbits in settings.py file, see above under parent directory...

## subdir: conf
nothing good here

## subdir: egg_cache
nothing good here


# dir: /
nothing useful except this file: `file_catalog.md`

# dir: /doc
does not contain anything meaningful

# dir: /tests
does not contain anything meaningful

# dir: /parse_previews
does not contain anything meaningful
