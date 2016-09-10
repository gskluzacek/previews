# Analysis: get_cvr_dt.php

no included files, so the script is stand alone

## the real meat of this script 

is the get\_iss\_list\_html() & get\_series\_issue\_list() functions

## usage - from the internal documentation

called from command line

get\_cvr\_dt.php -s##  
get\_cvr\_dt.php -r##

where  
-s##  is the internet source id to use when performing an initial processing of a  
      invoice file  
-r##  is the execution id to reprocess an invoice file when all series/issue numbers  
      were not matched

## common

regardless if the execution mode is initial processing or reprocess, the script attempts 
to update the execution status (execution\_history.exec_status) to INVOICE GENERATED

## reprocess logic

before you can reprocess, you must manually update the status 
(execution\_history.exec_status) from FAILED to READY FOR REPROCESSING  
status cannot be IN PROCESS  
if status is INVOICE GENERATED, just prints a warning

status is then updated to IN PROCESS

per the design doc: "the re-run mode will by-pass loading the collectorz invoice input 
file and downloading the HTML"

**it doesn't look like the actual logic to reprocess has ben impemented....**

## initial Processing logic

- gets correspondign internet\_sources record for is\_id passed in
- inserts a record into the execution\_history table for the current execution
- inserts contenst of the cz\_issues\_input.txt file into the collectorz\_invoice\_input table
   - reads the cz\_issues\_input.txt file from the same directory as the script
     into an array then loops over it
   - breaks the line into fields by using the PHP str\_getcsv() function with a ';' separator
   - inserts data into collectorz\_invoice\_input (eh\_id groups records by execution)
- select record set from collectorz\_invoice\_input, series, is\_series\_ids & 
execution\_history tables for the current execution history id
- the record set includes the series id for both the webpage (issi\_series\_id), the local 
db (ser\_id) and comic collectorz (cii\_series)
- for each series record
   - insert a record into the is\_series\_header table
   - using the issi\_series\_id download the the html for the series's issue listing by 
calling the **get\_iss\_list\_html() function**
   - insert the html into the is\_html table
   - parse the issue numbers and related info from the html by calling the 
**get\_series\_issue\_list() function**
   - insert a record into the is\_series\_index table for each issue parsed
- after all series have been processed, generate the output file
   - open the invoice\_ouput.csv file in the same directory as the script for output
   - select record set from fnl\_rpt\_1
   - for each record: call fputcsv() function & add an entry into the lookup\_sts multi-key 
MAP (keys of: cii\_series & cii\_issue)
- for each series in the lookup\_sts map, and for each issue in the series entry update the 
collectorz\_invoice\_input table setting the status, cover\_dt & cover\_dt\_disp

## input file layout

semi-colon delimted file  
all fields are enclosed in double quotes

0. CC release date
1. CC cover date
2. CC series
3. CC issue number full
4. CC issue number
5. CC issue number extension
6. CC additional info -- edition
7. CC additional info -- full title

## databaase info

uses a sqlite3 database  
the database file is named: comics_invoicing.db  
the database file should be located in the same directory as the php script  
using foreign key support

### a view ???

fnl\_rpt_1

### tables used in the script

#### execution\_history

posible values for exec_status:
- FAILED
- IN PROCESS
- READY FOR REPROCESSING
- INVOICE GENERATED
- UNMATCHED ISSUE NUMBERS

### collectorz\_invoice\_input

- cii\_id
- eh\_id
- lookup\_status
- computed\_issue_num
- cii\_release\_dt
- cii\_cover\_dt\_disp
- cii\_cover\_dt
- cii\_series
- cii\_issue
- cii\_issue\_no
- cii\_issue\_ext
- cii\_edition
- cii\_full\_title

### internet\_sources
### series
### is\_series\_ids
### is\_series\_header
### is\_series\_index
