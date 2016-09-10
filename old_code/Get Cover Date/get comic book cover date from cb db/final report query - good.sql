select rec_type, ser_name, ser_year, null as notes, isi_cover_dt_disp, isi_cover_dt, computed_issue_num, ser_cover_price, 
cii_cover_dt_disp, cii_release_dt, cii_series, cii_issue, cii_edition, cii_full_title, isi_variant_desc, 
isi_issue_name, isi_story_arc, issi_series_id, isi_issue_id
from fnl_rpt_1 where eh_id = 1
order by ser_name_sort, computed_issue_num, isi_variant_seq;

select * from fnl_rpt_1;

drop view fnl_rpt_1;

create view fnl_rpt_1 as
select 
--        cii.cii_id, 
        cii.cii_release_dt, 
        cii.cii_cover_dt_disp, 
        cii.cii_cover_dt, 
        cii.cii_series, 
        cii.cii_issue, 
        cii.cii_edition, 
        cii.cii_full_title,
        cii.lookup_status, 
        ser.ser_id, 
        ser.ser_name, 
        ser.ser_name_sort, 
        ser.ser_year, 
        ser.ser_cover_price, 
        isi2.isi_issue_num, 
        isi2.isi_cover_dt_disp, 
        cii.computed_issue_num, 
        isi2.isi_cover_dt, 
        ifnull(isi2.rec_type, 'Not Matched') as rec_type, 
--        isi2.rec_cnt, 
        isi2.isi_issue_name, 
        isi2.isi_story_arc, 
--        isi2.isi_issue_type, 
        isi2.isi_variant_seq, 
        isi2.isi_variant_desc,
        issi.issi_series_id,
        isi2.isi_issue_id, 
        isi2.isi_story_arc_id,
        ish.ish_id,
        ish.eh_id
from collectorz_invoice_input cii
 join series ser on ser.cii_series = cii.cii_series
 join is_series_header ish on ish.ser_id = ser.ser_id and ish.eh_id = cii.eh_id
 join is_series_ids issi on issi.ser_id = ser.ser_id
left join (
-- ====== single date (i.e., one issue with no variants)
select isi.ish_id, isi.isi_issue_num, t.rec_cnt, isi.isi_cover_dt_disp, isi.isi_cover_dt, 'Single Date' as rec_type, isi.isi_issue_name, isi.isi_story_arc, isi.isi_story_arc_id, isi.isi_issue_id, isi.isi_issue_type, isi.isi_variant_seq, isi.isi_variant_desc
from is_series_index isi 
join (
        select count(*) as rec_cnt, isi.ish_id, isi.isi_issue_num
        from is_series_index isi
        join is_series_header ish on ish.ish_id = isi.ish_id
--        where ish.eh_id = 50
        group by isi.ish_id, isi.isi_issue_num
        having count(*) = 1
) t on t.ish_id = isi.ish_id and t.isi_issue_num = isi.isi_issue_num
-- ====== single distinct date (i.e., an issue with one or more variants, but all variants have the same cover date)
union
select distinct isi.ish_id, isi.isi_issue_num, t.rec_cnt, isi.isi_cover_dt_disp, isi.isi_cover_dt, 'Single Distinct Date' as rec_type, isi.isi_issue_name, isi.isi_story_arc, isi.isi_story_arc_id, isi.isi_issue_id, isi.isi_issue_type, isi.isi_variant_seq, isi.isi_variant_desc 
from is_series_index isi 
join (
        select count(*) as rec_cnt, ft.ish_id, ft.isi_issue_num
        from (
                select distinct isi.ish_id, isi.isi_issue_num, isi.isi_cover_dt 
                from is_series_index isi
                join (
                        select count(*), isi.ish_id, isi.isi_issue_num
                        from is_series_index isi
                        join is_series_header ish on ish.ish_id = isi.ish_id
  --                      where ish.eh_id = 50
                        group by isi.ish_id, isi.isi_issue_num
                        having count(*) != 1
                ) as gt on gt.ish_id = isi.ish_id and gt.isi_issue_num = isi.isi_issue_num
        ) ft
        group by ft.ish_id, ft.isi_issue_num
        having count(*) = 1
) t on t.ish_id = isi.ish_id and t.isi_issue_num = isi.isi_issue_num
where isi.isi_variant_seq = 1
-- ====== multiple distinct dates (i.e., an issue with one or more variants, and somve variants have different cover date)
union
select isi.ish_id, isi.isi_issue_num, tt.rec_cnt, isi.isi_cover_dt_disp, isi.isi_cover_dt, 'Multiple Dates' as rec_type, isi.isi_issue_name, isi.isi_story_arc, isi.isi_story_arc_id, isi.isi_issue_id,  isi.isi_issue_type, isi.isi_variant_seq, isi.isi_variant_desc
from is_series_index isi
join (
        select ft.ish_id, ft.isi_issue_num, count(*) as rec_cnt
        from (
                select distinct isi.ish_id, isi.isi_issue_num, isi.isi_cover_dt
                from is_series_index isi
                join (
                        select count(*), isi.ish_id, isi.isi_issue_num
                        from is_series_index isi
                        join is_series_header ish on ish.ish_id = isi.ish_id
--                        where ish.eh_id = 50
                        group by isi.ish_id, isi.isi_issue_num
                        having count(*) != 1
                ) as gt on gt.ish_id = isi.ish_id and gt.isi_issue_num = isi.isi_issue_num
        ) ft
        group by ft.ish_id, ft.isi_issue_num
        having count(*) != 1
) tt on tt.ish_id = isi.ish_id and tt.isi_issue_num = isi.isi_issue_num
-- ======
) isi2 on isi2.ish_id = ish.ish_id and isi2.isi_issue_num = cii.computed_issue_num;
--where ish.eh_id = 2
--order by ser.ser_name_sort, ser.ser_year, cii.cii_issue, isi2.isi_variant_seq;
