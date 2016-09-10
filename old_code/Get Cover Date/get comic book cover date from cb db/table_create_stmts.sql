drop table if exists series;

CREATE TABLE
    series
    (
        ser_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        ser_name TEXT NOT NULL,
        ser_name_sort TEXT,
        ser_year INTEGER NOT NULL,
        ser_cover_price REAL NOT NULL,
        cii_series TEXT not null,
        crt_dt TEXT NOT NULL,
        updt_dt TEXT NOT NULL,
        CONSTRAINT series_ix1 UNIQUE (ser_name, ser_year),
        CONSTRAINT series_ix2 UNIQUE (cii_series)
    );

INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (1, '3 Guns', '3 guns', 2013, 3.99, '3 Guns', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (2, 'Age of Ultron', 'age of ultron', 2013, 3.99, 'Age of Ultron', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (3, 'All New X-Men', 'all new xmen', 2013, 3.99, 'All-New X-Men', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (4, 'Avengers', 'avengers', 2013, 3.99, 'Avengers, Vol. 5', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (5, 'Battlestar Galactica: Classic', 'battlestar galactica classic', 2013, 3.99, 'Battlestar Galactica: The Original', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (6, 'Cable And X-Force', 'cable and xforce', 2013, 3.99, 'Cable and X-Force', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (7, 'Danger Girl: Trinity', 'danger girl trinity', 2013, 3.99, 'Danger Girl: Trinity', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (8, 'Daredevil', 'daredevil', 2011, 2.99, 'Daredevil, Vol. 3', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (9, 'Daredevil Dark Nights', 'daredevil dark nights', 2013, 2.99, 'Daredevil: Dark Nights', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (10, 'Daredevil End Of Days', 'daredevil end of days', 2012, 3.99, 'Daredevil: End of Days', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (11, 'Dark Tower: The Gunslinger - Evil Ground', 'dark tower gunslinger the evil ground', 2013, 3.99, 'The Dark Tower: The Gunslinger: Evil Ground', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (12, 'Dark Tower: The Gunslinger - So Fell Lord Perth', 'dark tower gunslinger the so fell lord perth', 2013, 3.99, 'The Dark Tower: The Gunslinger: So Fell Lord Perth', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (13, 'Deadpool', 'deadpool', 2013, 2.99, 'Deadpool, Vol. 4', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (14, 'Deadpool Kills Deadpool', 'deadpool kills deadpool', 2013, 2.99, 'Deadpool Kills Deadpool', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (15, 'Deadpool Killustrated', 'deadpool killustrated', 2013, 2.99, 'Deadpool Killustrated', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (16, 'Fantastic Four', 'fantastic four', 2013, 2.99, 'Fantastic Four, Vol. 4', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (17, 'FF', 'ff', 2013, 2.99, 'FF, Vol. 2', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (18, 'Guardians Of The Galaxy', 'guardians of the galaxy', 2013, 3.99, 'Guardians of the Galaxy, Vol. 3', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (19, 'Judge Dredd', 'judge dredd', 2012, 3.99, 'Judge Dredd, Vol. 4', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (20, 'Justice League of America', 'justice league of america', 2013, 3.99, 'Justice League of America, Vol. 3', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (21, 'Killjoys', 'killjoys', 2013, 3.99, 'True Lives of the Fabulous Killjoys', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (22, 'Lazarus', 'lazarus', 2013, 2.99, 'Lazarus (2013)', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (23, 'Locke & Key Alpha', 'locke and key alpha', 2013, 7.99, 'Locke & Key: Alpha', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (24, 'Powers Bureau', 'powers bureau', 2013, 3.95, 'Powers: Bureau', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (25, 'Suicide Risk', 'suicide risk', 2013, 3.99, 'Suicide Risk', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (26, 'Superior Carnage', 'superior carnage', 2013, 3.99, 'Superior Carnage', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (27, 'Superior Spider-Man Team-Up', 'superior spider man team up', 2013, 3.99, 'Superior Spider-Man Team-Up', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (28, 'Ten Grand', 'ten grand', 2013, 2.99, 'Ten Grand', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (29, 'Thanos Rising', 'thanos rising', 2013, 3.99, 'Thanos Rising', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (30, 'The Red Ten', 'red ten the', 2011, 3.99, 'The Red Ten', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (31, 'The Superior Foes of Spider-Man', 'superior foes of spider-man the', 2013, 2.99, 'The Superior Foes of Spider-Man', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (32, 'The Superior Spider-Man', 'superior spider-man the', 2013, 3.99, 'Superior Spider-Man', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (33, 'The Walking Dead', 'walking dead the', 2003, 2.99, 'The Walking Dead', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (34, 'Thumbprint', 'thumbprint', 2013, 3.99, 'Joe Hill''s Thumbprint', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (35, 'Uncanny Avengers', 'uncanny avengers', 2012, 3.99, 'Uncanny Avengers', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (36, 'Uncanny X-Force', 'uncanny xforce', 2013, 3.99, 'Uncanny X-Force, Vol. 2', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (37, 'Uncanny X-Men', 'uncanny xmen', 2013, 3.99, 'Uncanny X-Men, Vol. 3', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (38, 'X-Factor', 'xfactor', 1986, 2.99, 'X-Factor, Vol. 3', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (39, 'X-Men', 'xmen', 2010, 2.99, 'X-Men, Vol. 2', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (40, 'X-Men', 'xmen', 2013, 3.99, 'X-Men, Vol. 3', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (41, 'X-Men Legacy', 'xmen legacy', 2013, 2.99, 'X-Men Legacy, Vol. 2', '2013-09-15 18:05:48', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (42, 'Jupiter''s Legacy', 'jupiters legacy', 2013, 2.99, 'Jupiter''s Legacy', '2013-09-15 18:07:18', '2013-09-26 22:23:01');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (43, 'Locke & Key TPB', 'locke and key tpb', 2008, 19.99, 'Locke & Key', '2013-09-28 03:25:37', '2013-09-28 03:25:37');
INSERT INTO series (ser_id, ser_name, ser_name_sort, ser_year, ser_cover_price, cii_series, crt_dt, updt_dt) VALUES (44, 'Satellite Sam', 'satellite sam', 2013, 3.5, 'Satellite Sam', '2013-09-28 03:29:15', '2013-09-28 03:29:15');

drop table if exists internet_sources;

CREATE TABLE
    internet_sources
    (
        is_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        is_name TEXT NOT NULL,
        is_base_url TEXT NOT NULL,
        is_series_index_page TEXT NOT NULL,
        crt_dt TEXT NOT NULL,
        updt_dt TEXT NOT NULL,
        CONSTRAINT internet_sources_ix1 UNIQUE (is_name),
        CONSTRAINT internet_sources_ix2 UNIQUE (is_base_url, is_series_index_page)
    );

INSERT INTO internet_sources (is_id, is_name, is_base_url, is_series_index_page, crt_dt, updt_dt) VALUES (1, 'Comic Book db', 'http://www.comicbookdb.com', '/title.php?ID=', '2013-09-15 15:58:39', '2013-09-15 15:58:39');

drop table if exists is_series_ids;

CREATE TABLE
    is_series_ids
    (
        issi_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        ser_id INTEGER NOT NULL,
        is_id INTEGER NOT NULL,
        issi_series_id INTEGER NOT NULL,
        crt_dt TEXT NOT NULL,
        updt_dt TEXT NOT NULL,
        FOREIGN KEY (ser_id) REFERENCES series (ser_id) on delete cascade,
        FOREIGN KEY (is_id) REFERENCES internet_sources (is_id) on delete cascade,
        CONSTRAINT series_ix2 UNIQUE (ser_id, is_id),
        CONSTRAINT series_ix1 UNIQUE (is_id, issi_series_id)
    );

CREATE INDEX issi_is_id ON is_series_ids (is_id);
CREATE INDEX issi_ser_id ON is_series_ids (ser_id);

INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (1, 1, 1, 41047, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (2, 2, 1, 39620, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (3, 3, 1, 38270, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (4, 4, 1, 38938, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (5, 5, 1, 40265, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (6, 6, 1, 38962, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (7, 7, 1, 40028, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (8, 8, 1, 33952, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (9, 9, 1, 40473, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (10, 10, 1, 38487, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (11, 11, 1, 39959, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (12, 12, 1, 41120, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (13, 13, 1, 38742, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (14, 14, 1, 40708, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (15, 15, 1, 39212, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (16, 16, 1, 38769, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (17, 17, 1, 38866, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (18, 18, 1, 39666, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (19, 19, 1, 38837, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (20, 42, 1, 40078, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (21, 20, 1, 39591, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (22, 21, 1, 274586, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (23, 22, 1, 40660, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (24, 23, 1, 41413, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (25, 24, 1, 39538, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (26, 25, 1, 40136, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (27, 26, 1, 40904, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (28, 27, 1, 40898, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (29, 28, 1, 40127, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (30, 29, 1, 39956, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (31, 30, 1, 35290, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (32, 31, 1, 40715, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (33, 32, 1, 39035, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (34, 33, 1, 457, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (35, 34, 1, 40531, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (36, 35, 1, 38519, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (37, 36, 1, 39208, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (38, 37, 1, 39501, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (39, 38, 1, 577, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (40, 39, 1, 29569, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (41, 40, 1, 40345, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (42, 41, 1, 38777, '2013-09-15 18:27:28', '2013-09-15 18:27:28');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (43, 43, 1, 22187, '2013-09-28 03:27:29', '2013-09-28 03:27:29');
INSERT INTO is_series_ids (issi_id, ser_id, is_id, issi_series_id, crt_dt, updt_dt) VALUES (44, 44, 1, 40718, '2013-09-28 03:29:25', '2013-09-28 03:29:25');

drop table if exists execution_history;

CREATE TABLE
    execution_history
    (
        eh_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        exec_dt TEXT NOT NULL,
        is_id INTEGER NOT NULL,
        exec_status TEXT NOT NULL,
        FOREIGN KEY (is_id) REFERENCES internet_sources (is_id) on delete cascade
    );

CREATE INDEX eh_is_id ON execution_history (is_id);
    
drop table if exists collectorz_invoice_input;

CREATE TABLE
    collectorz_invoice_input
    (
        cii_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        eh_id INTEGER NOT NULL,
        cii_release_dt TEXT,
        cii_cover_dt_disp TEXT,
        cii_cover_dt TEXT,
        cii_series TEXT NOT NULL,
        cii_issue TEXT,
        cii_issue_no TEXT,
        cii_issue_ext TEXT,
        cii_edition TEXT,
        cii_full_title TEXT,
        computed_issue_num TEXT NOT NULL,
        lookup_status TEXT,
        isi_cover_dt_disp TEXT,
        isi_cover_dt TEXT,
        FOREIGN KEY (eh_id) REFERENCES execution_history (eh_id) on delete cascade
    );

CREATE INDEX cii_eh_id ON collectorz_invoice_input (eh_id);

drop table if exists is_series_header;

CREATE TABLE
    is_series_header
    (
        ish_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        eh_id INTEGER NOT NULL,
        ser_id INTEGER NOT NULL,
        FOREIGN KEY (eh_id) REFERENCES execution_history(eh_id) on delete cascade,
        FOREIGN KEY (ser_id) REFERENCES series (ser_id) on delete cascade,
        CONSTRAINT series_ix1 UNIQUE (eh_id, ser_id)
    );

CREATE INDEX ish_ser_id ON is_series_header (ser_id);
CREATE INDEX ish_eh_id ON is_series_header (eh_id);

drop table if exists is_series_index;

CREATE TABLE
    is_series_index
    (
        isi_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        ish_id INTEGER NOT NULL,
        isi_issue_id INTEGER NOT NULL,
        isi_issue_type TEXT NOT NULL,
        isi_issue_num TEXT NOT NULL,
        isi_issue_name TEXT,
        isi_variant_seq INTEGER not null,
        isi_variant_desc TEXT,
        isi_cover_dt_disp TEXT,
        isi_cover_dt TEXT,
        isi_story_arc TEXT,
        isi_story_arc_id INTEGER,
        FOREIGN KEY (ish_id) REFERENCES is_series_header (ish_id) ON DELETE CASCADE,
        UNIQUE (ish_id, isi_issue_id),
        UNIQUE (ish_id, isi_issue_num, isi_variant_seq)
    );

CREATE INDEX isi_ish_id ON is_series_index (ish_id);

drop table if exists is_html;

CREATE TABLE
    is_html
    (
        ih_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        ish_id INTEGER NOT NULL,
        html_text BLOB,
        FOREIGN KEY (ish_id) REFERENCES is_series_header (ish_id) ON DELETE CASCADE
    );

CREATE INDEX ih_ish_id ON is_html (ish_id);

