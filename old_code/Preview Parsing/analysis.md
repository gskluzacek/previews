# Analysis: get\_cvr\_dt.php

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

### relationships

- execution\_history has a record for each time the program is executed (eh_id)
- it records the date, is\_id, and status

- collectorz\_invoice\_input contains a record (cii_id) for each issue from a given 
purchase to have its cover date looked up.
- this table is loaded with the cz\_issues\_input.txt file (exported from collectorz)
- the records for a given purchase are grouped by its execution run (eh\_id)

- internet\_sources contains a record for each internet source (is\_id) along with the 
corresponding base url & title index web page (the page that lists all of a series issues)

- the series table has an id (ser\_id) for each series that I collect
- I track the series name (ser\_name) [as I like it] and the series year (ser\_year)
- this maps to the series name (cii_series) from collectorz

- the is\_series\_ids has an id (issi\_id) that maps my series id (ser\_id) to each 
internet source's (is\_id) corresponding series id (issi\_series\_id)

- is\_series\_header (ish\_id) records each ser\_id to be processed for a given execution 
(eh\_id)

- is\_html has a record for each ser\_id to be processed (ish\_id)

- is\_series\_index has a record for each issue parsed from the htm series index that was
downloaded

### tables 

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

### a view

fnl\_rpt_1

CREATE VIEW fnl\_rpt\_1 as
select 
        cii.cii\_release\_dt, 
        cii.cii\_cover\_dt\_disp, 
        cii.cii\_cover\_dt, 
        cii.cii\_series, 
        cii.cii\_issue, 
        cii.cii\_edition, 
        cii.cii\_full\_title,
        cii.lookup\_status, 
        ser.ser\_id, 
        ser.ser\_name, 
        ser.ser\_name\_sort, 
        ser.ser\_year, 
        ser.ser\_cover\_price, 
        isi2.isi\_issue\_num, 
        isi2.isi\_cover\_dt\_disp, 
        cii.computed\_issue\_num, 
        isi2.isi\_cover\_dt, 
        ifnull(isi2.rec\_type, 'Not Matched') as rec\_type, 
        isi2.isi\_issue\_name, 
        isi2.isi\_story\_arc, 
        isi2.isi\_variant\_seq, 
        isi2.isi\_variant\_desc,
        issi.issi\_series\_id,
        isi2.isi\_issue\_id, 
        isi2.isi\_story\_arc\_id,
        ish.ish\_id,
        ish.eh\_id
from collectorz\_invoice\_input cii
join series ser 
        on ser.cii\_series  = cii.cii\_series
join is\_series\_header ish 
        on ish.ser\_id      = ser.ser\_id 
       and ish.eh\_id       = cii.eh\_id
join is\_series\_ids issi 
        on issi.ser\_id     = ser.ser\_id
left join (
    select 
            isi.ish\_id, 
            isi.isi\_issue\_num, 
            t.rec\_cnt, 
            isi.isi\_cover\_dt\_disp, 
            isi.isi\_cover\_dt, 
            'Single Date' as rec\_type, 
            isi.isi\_issue\_name, 
            isi.isi\_story\_arc, 
            isi.isi\_story\_arc\_id, 
            isi.isi\_issue\_id, 
            isi.isi\_issue\_type, 
            isi.isi\_variant\_seq, 
            isi.isi\_variant\_desc
    from is\_series\_index isi 
    join (
        select 
                count(*) as rec\_cnt, 
                isi.ish\_id, 
                isi.isi\_issue\_num
        from is\_series\_index isi
        join is\_series\_header ish 
                on ish.ish\_id = isi.ish\_id
        group by isi.ish\_id, isi.isi\_issue\_num
        having count(*) = 1
    ) t 
            on t.ish\_id = isi.ish\_id 
           and t.isi\_issue\_num = isi.isi\_issue\_num
    union
    select distinct 
            isi.ish\_id, 
            isi.isi\_issue\_num, 
            t.rec\_cnt, 
            isi.isi\_cover\_dt\_disp, 
            isi.isi\_cover\_dt, 
            'Single Distinct Date' as rec\_type, 
            isi.isi\_issue\_name, 
            isi.isi\_story\_arc, 
            isi.isi\_story\_arc\_id, 
            isi.isi\_issue\_id, 
            isi.isi\_issue\_type, 
            isi.isi\_variant\_seq, 
            isi.isi\_variant\_desc 
    from is\_series\_index isi 
    join (
        select 
                count(*) as rec\_cnt, 
                ft.ish\_id, 
                ft.isi\_issue\_num
        from (
            select distinct 
                    isi.ish\_id, 
                    isi.isi\_issue\_num, 
                    isi.isi\_cover\_dt 
            from is\_series\_index isi
            join (
                    select 
                            count(*), 
                            isi.ish\_id, 
                            isi.isi\_issue\_num
                    from is\_series\_index isi
                    join is\_series\_header ish 
                            on ish.ish\_id = isi.ish\_id
                    group by isi.ish\_id, isi.isi\_issue\_num
                    having count(*) != 1
            ) as gt 
                    on gt.ish\_id = isi.ish\_id 
                   and gt.isi\_issue\_num = isi.isi\_issue\_num
        ) ft
        group by ft.ish\_id, ft.isi\_issue\_num
        having count(*) = 1
    ) t 
            on t.ish\_id = isi.ish\_id 
           and t.isi\_issue\_num = isi.isi\_issue\_num
    where isi.isi\_variant\_seq = 1
    union
    select 
            isi.ish\_id, 
            isi.isi\_issue\_num, 
            tt.rec\_cnt, 
            isi.isi\_cover\_dt\_disp, 
            isi.isi\_cover\_dt, 
            'Multiple Dates' as rec\_type, 
            isi.isi\_issue\_name, 
            isi.isi\_story\_arc, 
            isi.isi\_story\_arc\_id, 
            isi.isi\_issue\_id,  
            isi.isi\_issue\_type, 
            isi.isi\_variant\_seq, 
            isi.isi\_variant\_desc
    from is\_series\_index isi
    join (
        select 
                ft.ish\_id, 
                ft.isi\_issue\_num, 
                count(*) as rec\_cnt
        from (
            select distinct 
                    isi.ish\_id, 
                    isi.isi\_issue\_num, 
                    isi.isi\_cover\_dt
            from is\_series\_index isi
            join (
                select 
                        count(*), 
                        isi.ish\_id, 
                        isi.isi\_issue\_num
                from is\_series\_index isi
                join is\_series\_header ish 
                        on ish.ish\_id = isi.ish\_id
                group by isi.ish\_id, isi.isi\_issue\_num
                having count(*) != 1
            ) as gt 
                    on gt.ish\_id = isi.ish\_id 
                   and gt.isi\_issue\_num = isi.isi\_issue\_num
        ) ft
        group by ft.ish\_id, ft.isi\_issue\_num
        having count(*) != 1
    ) tt 
            on tt.ish\_id = isi.ish\_id 
           and tt.isi\_issue\_num = isi.isi\_issue\_num
) isi2 
        on isi2.ish\_id = ish.ish\_id 
       and isi2.isi\_issue\_num = cii.computed\_issue\_num;

