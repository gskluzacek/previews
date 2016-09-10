select cii.cii_id, cii.cii_series, cii.cii_issue, cii.cii_edition, cii.computed_issue_num, cii.lookup_status, 
        ser.ser_name, ser.ser_year, ser.ser_cover_price, 
        ish.*,
        issi.issi_series_id,
--      isi.isi_issue_id,  isi.isi_issue_type, isi.isi_variant_seq, isi.isi_variant_desc
        isi2.isi_issue_num, isi2.isi_cover_dt_disp, isi2.isi_cover_dt, ifnull(isi2.rec_type, 'Not Matched') as rec_type, isi2.rec_cnt, isi2.isi_issue_name, isi2.isi_story_arc, isi2.isi_issue_id, isi2.isi_issue_type, isi2.isi_variant_seq, isi2.isi_variant_desc
from collectorz_invoice_input cii
 join series ser on ser.cii_series = cii.cii_series
 join is_series_header ish on ish.ser_id = ser.ser_id and ish.eh_id = cii.eh_id
 join is_series_ids issi on issi.ser_id = ser.ser_id
-- left join is_series_index isi on isi.ish_id = ish.ish_id and isi.isi_issue_num = cii.computed_issue_num
left join (
------
-- ====== single date (i.e., one issue with no variants)
select isi.ish_id, isi.isi_issue_num, t.rec_cnt, isi.isi_cover_dt_disp, isi.isi_cover_dt, 'Single Date' as rec_type, isi.isi_issue_name, isi.isi_story_arc, null as isi_issue_id, null as isi_issue_type, null as isi_variant_seq, null as isi_variant_desc
from is_series_index isi 
join (
        select count(*) as rec_cnt, isi.ish_id, isi.isi_issue_num
        from is_series_index isi
        join is_series_header ish on ish.ish_id = isi.ish_id
        where ish.eh_id = 50
        group by isi.ish_id, isi.isi_issue_num
        having count(*) = 1
) t on t.ish_id = isi.ish_id and t.isi_issue_num = isi.isi_issue_num
-- ====== single distinct date (i.e., an issue with one or more variants, but all variants have the same cover date)
union
select distinct isi.ish_id, isi.isi_issue_num, t.rec_cnt, isi.isi_cover_dt_disp, isi.isi_cover_dt, 'Single Distinct Date' as rec_type, isi.isi_issue_name, isi.isi_story_arc, null as isi_issue_id, null as isi_issue_type, null as isi_variant_seq, null as isi_variant_desc 
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
                        where ish.eh_id = 50
                        group by isi.ish_id, isi.isi_issue_num
                        having count(*) != 1
                ) as gt on gt.ish_id = isi.ish_id and gt.isi_issue_num = isi.isi_issue_num
        ) ft
        group by ft.ish_id, ft.isi_issue_num
        having count(*) = 1
) t on t.ish_id = isi.ish_id and t.isi_issue_num = isi.isi_issue_num
-- ====== multiple distinct dates (i.e., an issue with one or more variants, and somve variants have different cover date)
union
select isi.ish_id, isi.isi_issue_num, tt.rec_cnt, isi.isi_cover_dt_disp, isi.isi_cover_dt, 'Multiple Dates' as rec_type, isi.isi_issue_name, isi.isi_story_arc, isi.isi_issue_id,  isi.isi_issue_type, isi.isi_variant_seq, isi.isi_variant_desc
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
                        where ish.eh_id = 50
                        group by isi.ish_id, isi.isi_issue_num
                        having count(*) != 1
                ) as gt on gt.ish_id = isi.ish_id and gt.isi_issue_num = isi.isi_issue_num
        ) ft
        group by ft.ish_id, ft.isi_issue_num
        having count(*) != 1
) tt on tt.ish_id = isi.ish_id and tt.isi_issue_num = isi.isi_issue_num
/*select ft.ish_id, ft.isi_issue_num, count(*) as rec_cnt, null as isi_cover_dt_disp, null as isi_cover_dt, 'Multiple Dates' as rec_type, ft.isi_issue_name, ft.isi_story_arc, null as isi_issue_id, null as isi_issue_type, null as isi_variant_seq, null as isi_variant_desc 
from (
        select distinct isi.ish_id, isi.isi_issue_num, isi.isi_cover_dt, isi.isi_issue_name, isi.isi_story_arc 
        from is_series_index isi
        join (
                select count(*), isi.ish_id, isi.isi_issue_num
                from is_series_index isi
                join is_series_header ish on ish.ish_id = isi.ish_id
                where ish.eh_id = 50
                group by isi.ish_id, isi.isi_issue_num
                having count(*) != 1
        ) as gt on gt.ish_id = isi.ish_id and gt.isi_issue_num = isi.isi_issue_num
) ft
group by ft.ish_id, ft.isi_issue_num, ft.isi_issue_name, ft.isi_story_arc
having count(*) != 1*/
----------
) isi2 on isi2.ish_id = ish.ish_id and isi2.isi_issue_num = cii.computed_issue_num
where ish.eh_id = 50
order by ser.ser_name, ser.ser_year, cii.cii_issue;
