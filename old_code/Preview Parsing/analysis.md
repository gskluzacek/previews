# Analysis: get_cvr_dt.php

no included files, so the script is stand alone

## usage - from the internal documentation

called from command line

get_cvr_dt.php -s##
get_cvr_dt.php -r##

where
-s##  is the internet source id to use when performing an initial processing of a 
      invoice file
-r##  is the execution id to reprocess an invoice file when all series/issue numbers 
      were not matched

## common

regardless if the execution mode is original or reprocess, the script attempts to update 
the execution status (execution\_history.exec_status) to INVOICE GENERATED

## reprocess logic

before you can reprocess, must update the status (execution\_history.exec_status) from 
FAILED to READY FOR REPROCESSING  
status cannot be IN PROCESS  
if status is INVOICE GENERATED, just prints a warning

status is then updated to IN PROCESS

__it doesn't look like the actual logic to reprocess has ben impemented....__

## databaase info

uses a sqlite3 database  
the database file is named: comics_invoicing.db  
the database file should be located in the same directory as the php script  
using foreign key support

### tables used in the script

- execution\_history
- internet_sources

