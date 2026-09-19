-- =========================================================
-- DỮ LIỆU MẪU LIBGO — GỘP CHUNG 1 FILE
--   Phần 1: 180 đầu sách cho 6 ngành (mỗi cuốn số lượng 10)
--   Phần 2: 20 giáo trình mở OpenStax (miễn phí, đọc online toàn bộ)
--   Phần 3: 37 sinh viên lớp EXE101_G11 (mật khẩu ban đầu 123456)
-- An toàn khi chạy nhiều lần:
--   - Sách trùng tên được bỏ qua
--   - Sinh viên đã có: cập nhật họ tên / lớp / email, KHÔNG ghi đè mật khẩu đang dùng
-- File có email sinh viên -> chỉ để trong repo PRIVATE.
-- =========================================================
SET NAMES utf8mb4;
USE library_db;

-- Cột ảnh bìa + mức đọc online (database cũ chưa có thì tự thêm)
ALTER TABLE books ADD COLUMN IF NOT EXISTS cover_url VARCHAR(500) NULL;
ALTER TABLE books ADD COLUMN IF NOT EXISTS read_access VARCHAR(10) NULL;


-- #########################################################
-- PHẦN 1: SÁCH 6 NGÀNH
-- #########################################################
-- =========================================================
-- THÊM SÁCH MẪU CHO 6 NGÀNH (180 đầu sách, mỗi cuốn số lượng 10)
--   Công nghệ thông tin (30) · Ngôn ngữ Hàn (30) · Ngôn ngữ Nhật (30)
--   Ngôn ngữ Anh (30) · Marketing (30) · Tài chính - Ngân hàng (30)
-- An toàn khi chạy nhiều lần: sách đã có (trùng tên) sẽ được bỏ qua.
-- Mã môn là mã đặt tạm để tìm kiếm; sửa theo mã môn thật của trường nếu cần.
-- =========================================================

-- Bảng tạm chứa danh sách sách, tự mất khi thoát
DROP TEMPORARY TABLE IF EXISTS tmp_books;
CREATE TEMPORARY TABLE tmp_books LIKE books;  -- cùng cấu trúc và bảng mã với bảng books

-- ================= CÔNG NGHỆ THÔNG TIN (Khu A–E, Tầng 1–2) =================
INSERT INTO tmp_books (title, author, subject_code, shelf_location, total_qty, available_qty) VALUES
-- Khu A: Lập trình
('Lập trình C cơ bản',                          'Phạm Văn Ất',                         'PRF192',  'Kệ A1 - Tầng 1', 10, 10),
('Lập trình hướng đối tượng với Java',          'Nguyễn Văn Hiệp',                     'PRO192',  'Kệ A1 - Tầng 1', 10, 10),
('Head First Java',                              'Kathy Sierra, Bert Bates',            'PRO192',  'Kệ A1 - Tầng 1', 10, 10),
('Cấu trúc dữ liệu và giải thuật',              'Đỗ Xuân Lôi',                         'CSD201',  'Kệ A2 - Tầng 1', 10, 10),
('Introduction to Algorithms',                   'Thomas H. Cormen và cộng sự',         'CSD201',  'Kệ A2 - Tầng 1', 10, 10),
('Clean Code',                                   'Robert C. Martin',                    'LAB211',  'Kệ A2 - Tầng 1', 10, 10),
('Lập trình Web với Java Servlet & JSP',        'Nguyễn Minh Đạo',                     'PRJ301',  'Kệ A3 - Tầng 1', 10, 10),
('HTML & CSS: Design and Build Websites',        'Jon Duckett',                         'WED201c', 'Kệ A3 - Tầng 1', 10, 10),
('JavaScript: The Good Parts',                   'Douglas Crockford',                   'WED201c', 'Kệ A3 - Tầng 1', 10, 10),
('Lập trình C# và .NET',                        'Phạm Hữu Khang',                      'PRN211',  'Kệ A4 - Tầng 1', 10, 10),

-- Khu B: Cơ sở dữ liệu, mạng, hệ thống
('Cơ sở dữ liệu',                               'Đỗ Trung Tuấn',                       'DBI202',  'Kệ B1 - Tầng 1', 10, 10),
('Database System Concepts',                     'Abraham Silberschatz và cộng sự',     'DBI202',  'Kệ B1 - Tầng 1', 10, 10),
('Mạng máy tính',                               'Andrew S. Tanenbaum',                 'NWC203c', 'Kệ B2 - Tầng 1', 10, 10),
('Computer Networking: A Top-Down Approach',     'James Kurose, Keith Ross',            'NWC203c', 'Kệ B2 - Tầng 1', 10, 10),
('Hệ điều hành',                                'Abraham Silberschatz và cộng sự',     'OSG202',  'Kệ B3 - Tầng 1', 10, 10),
('Kiến trúc máy tính',                          'William Stallings',                   'CEA201',  'Kệ B3 - Tầng 1', 10, 10),
('Internet of Things: Nguyên lý và ứng dụng',   'Nhiều tác giả',                       'IOT102',  'Kệ B4 - Tầng 1', 10, 10),

-- Khu C: Kỹ thuật phần mềm
('Nhập môn Công nghệ phần mềm',                 'Ian Sommerville',                     'SWE201c', 'Kệ C1 - Tầng 2', 10, 10),
('Phân tích và đặc tả yêu cầu phần mềm',        'Karl Wiegers, Joy Beatty',            'SWR302',  'Kệ C1 - Tầng 2', 10, 10),
('Kiểm thử phần mềm',                           'Ron Patton',                          'SWT301',  'Kệ C2 - Tầng 2', 10, 10),
('Design Patterns',                              'Erich Gamma và cộng sự',              'SWD392',  'Kệ C2 - Tầng 2', 10, 10),
('Quản lý dự án phần mềm',                      'Nhiều tác giả',                       'SWP391',  'Kệ C3 - Tầng 2', 10, 10),

-- Khu D: Toán và trí tuệ nhân tạo
('Toán cao cấp (Giải tích)',                    'Nguyễn Đình Trí',                     'MAE101',  'Kệ D1 - Tầng 2', 10, 10),
('Toán rời rạc và ứng dụng',                    'Kenneth H. Rosen',                    'MAD101',  'Kệ D1 - Tầng 2', 10, 10),
('Xác suất thống kê',                           'Đào Hữu Hồ',                          'MAS291',  'Kệ D2 - Tầng 2', 10, 10),
('Trí tuệ nhân tạo: Một cách tiếp cận hiện đại', 'Stuart Russell, Peter Norvig',       'AIL303m', 'Kệ D2 - Tầng 2', 10, 10),

-- Khu E: Ngoại ngữ, kỹ năng, lý luận
('Tiếng Anh chuyên ngành Công nghệ thông tin',  'Nhiều tác giả',                       'ENW492c', 'Kệ E1 - Tầng 2', 10, 10),
('Tiếng Nhật sơ cấp - Minna no Nihongo 1',      '3A Corporation',                      'JPD113',  'Kệ E1 - Tầng 2', 10, 10),
('Kỹ năng giao tiếp và làm việc nhóm',          'Nhiều tác giả',                       'SSG104',  'Kệ E2 - Tầng 2', 10, 10),
('Giáo trình Triết học Mác - Lênin',            'Bộ Giáo dục và Đào tạo',              'MLN111',  'Kệ E2 - Tầng 2', 10, 10);

-- ---------- NGÔN NGỮ HÀN (Khu F, Tầng 3) ----------
INSERT INTO tmp_books (title, author, subject_code, shelf_location, total_qty, available_qty) VALUES
('Tiếng Hàn tổng hợp dành cho người Việt Nam - Sơ cấp 1', 'Cho Hang Rok, Lee Mi Hye', 'KOR101', 'Kệ F1 - Tầng 3', 10, 10),
('Tiếng Hàn tổng hợp dành cho người Việt Nam - Sơ cấp 2', 'Cho Hang Rok, Lee Mi Hye', 'KOR102', 'Kệ F1 - Tầng 3', 10, 10),
('Tiếng Hàn tổng hợp dành cho người Việt Nam - Trung cấp 3', 'Cho Hang Rok, Lee Mi Hye', 'KOR201', 'Kệ F1 - Tầng 3', 10, 10),
('Tiếng Hàn tổng hợp dành cho người Việt Nam - Trung cấp 4', 'Cho Hang Rok, Lee Mi Hye', 'KOR202', 'Kệ F1 - Tầng 3', 10, 10),
('Tiếng Hàn tổng hợp dành cho người Việt Nam - Cao cấp 5', 'Cho Hang Rok, Lee Mi Hye', 'KOR301', 'Kệ F1 - Tầng 3', 10, 10),
('Tiếng Hàn tổng hợp dành cho người Việt Nam - Cao cấp 6', 'Cho Hang Rok, Lee Mi Hye', 'KOR302', 'Kệ F1 - Tầng 3', 10, 10),
('Sách bài tập Tiếng Hàn tổng hợp - Sơ cấp 1', 'Cho Hang Rok, Lee Mi Hye', 'KOR101', 'Kệ F1 - Tầng 3', 10, 10),
('Sách bài tập Tiếng Hàn tổng hợp - Sơ cấp 2', 'Cho Hang Rok, Lee Mi Hye', 'KOR102', 'Kệ F1 - Tầng 3', 10, 10),
('Seoul University Korean 1A', 'Viện Giáo dục Ngôn ngữ - ĐH Quốc gia Seoul', 'KOR101', 'Kệ F1 - Tầng 3', 10, 10),
('Seoul University Korean 1B', 'Viện Giáo dục Ngôn ngữ - ĐH Quốc gia Seoul', 'KOR102', 'Kệ F1 - Tầng 3', 10, 10),
('Seoul University Korean 2A', 'Viện Giáo dục Ngôn ngữ - ĐH Quốc gia Seoul', 'KOR201', 'Kệ F2 - Tầng 3', 10, 10),
('Korean Grammar in Use - Beginning', 'Ahn Jean-myung, Lee Kyung-ah, Han Hoo-young', 'KOR103', 'Kệ F2 - Tầng 3', 10, 10),
('Korean Grammar in Use - Intermediate', 'Min Jin-young, Ahn Jean-myung', 'KOR203', 'Kệ F2 - Tầng 3', 10, 10),
('Korean Grammar in Use - Advanced', 'Ahn Jean-myung và cộng sự', 'KOR303', 'Kệ F2 - Tầng 3', 10, 10),
('Luyện thi TOPIK I', 'Nhiều tác giả', 'KOR210', 'Kệ F2 - Tầng 3', 10, 10),
('Luyện thi TOPIK II - Đọc hiểu', 'Nhiều tác giả', 'KOR310', 'Kệ F2 - Tầng 3', 10, 10),
('Luyện thi TOPIK II - Viết', 'Nhiều tác giả', 'KOR310', 'Kệ F2 - Tầng 3', 10, 10),
('Luyện nghe tiếng Hàn sơ - trung cấp', 'Nhiều tác giả', 'KOR104', 'Kệ F2 - Tầng 3', 10, 10),
('Phát âm tiếng Hàn chuẩn', 'Nhiều tác giả', 'KOR100', 'Kệ F2 - Tầng 3', 10, 10),
('2000 từ vựng tiếng Hàn theo chủ đề', 'Nhiều tác giả', 'KOR105', 'Kệ F2 - Tầng 3', 10, 10),
('Từ điển Hàn - Việt', 'Nhiều tác giả', 'KOR100', 'Kệ F3 - Tầng 3', 10, 10),
('Tiếng Hàn thương mại', 'Nhiều tác giả', 'KOR320', 'Kệ F3 - Tầng 3', 10, 10),
('Tiếng Hàn văn phòng', 'Nhiều tác giả', 'KOR321', 'Kệ F3 - Tầng 3', 10, 10),
('Tiếng Hàn du lịch - khách sạn', 'Nhiều tác giả', 'KOR322', 'Kệ F3 - Tầng 3', 10, 10),
('Lý thuyết và thực hành biên dịch Hàn - Việt', 'Nhiều tác giả', 'KOR330', 'Kệ F3 - Tầng 3', 10, 10),
('Phiên dịch Hàn - Việt, Việt - Hàn', 'Nhiều tác giả', 'KOR331', 'Kệ F3 - Tầng 3', 10, 10),
('Văn hóa Hàn Quốc', 'Nhiều tác giả', 'KOR140', 'Kệ F3 - Tầng 3', 10, 10),
('Lịch sử Hàn Quốc', 'Nhiều tác giả', 'KOR141', 'Kệ F3 - Tầng 3', 10, 10),
('Văn học Hàn Quốc', 'Nhiều tác giả', 'KOR240', 'Kệ F3 - Tầng 3', 10, 10),
('Đất nước học Hàn Quốc', 'Nhiều tác giả', 'KOR142', 'Kệ F3 - Tầng 3', 10, 10);
-- ---------- NGÔN NGỮ NHẬT (Khu G, Tầng 3) ----------
INSERT INTO tmp_books (title, author, subject_code, shelf_location, total_qty, available_qty) VALUES
('Minna no Nihongo Sơ cấp I - Bản tiếng Nhật', '3A Corporation', 'JPN101', 'Kệ G1 - Tầng 3', 10, 10),
('Minna no Nihongo Sơ cấp I - Bản dịch và giải thích ngữ pháp', '3A Corporation', 'JPN101', 'Kệ G1 - Tầng 3', 10, 10),
('Minna no Nihongo Sơ cấp II - Bản tiếng Nhật', '3A Corporation', 'JPN102', 'Kệ G1 - Tầng 3', 10, 10),
('Minna no Nihongo Sơ cấp II - Bản dịch và giải thích ngữ pháp', '3A Corporation', 'JPN102', 'Kệ G1 - Tầng 3', 10, 10),
('Minna no Nihongo Trung cấp I', '3A Corporation', 'JPN201', 'Kệ G1 - Tầng 3', 10, 10),
('Minna no Nihongo Trung cấp II', '3A Corporation', 'JPN202', 'Kệ G1 - Tầng 3', 10, 10),
('Genki I - An Integrated Course in Elementary Japanese', 'Eri Banno và cộng sự', 'JPN101', 'Kệ G1 - Tầng 3', 10, 10),
('Genki II - An Integrated Course in Elementary Japanese', 'Eri Banno và cộng sự', 'JPN102', 'Kệ G1 - Tầng 3', 10, 10),
('Tobira - Gateway to Advanced Japanese', 'Mayumi Oka và cộng sự', 'JPN301', 'Kệ G1 - Tầng 3', 10, 10),
('Soumatome N3 - Ngữ pháp', 'Sasaki Hitoko, Matsumoto Noriko', 'JPN210', 'Kệ G1 - Tầng 3', 10, 10),
('Soumatome N3 - Từ vựng', 'Sasaki Hitoko, Matsumoto Noriko', 'JPN210', 'Kệ G2 - Tầng 3', 10, 10),
('Soumatome N2 - Đọc hiểu', 'Sasaki Hitoko, Matsumoto Noriko', 'JPN310', 'Kệ G2 - Tầng 3', 10, 10),
('Shin Kanzen Master N2 - Ngữ pháp', 'Nhiều tác giả', 'JPN310', 'Kệ G2 - Tầng 3', 10, 10),
('Shin Kanzen Master N2 - Nghe hiểu', 'Nhiều tác giả', 'JPN310', 'Kệ G2 - Tầng 3', 10, 10),
('Shin Kanzen Master N1 - Đọc hiểu', 'Nhiều tác giả', 'JPN410', 'Kệ G2 - Tầng 3', 10, 10),
('Kanji Look and Learn', 'Eri Banno và cộng sự', 'JPN103', 'Kệ G2 - Tầng 3', 10, 10),
('Basic Kanji Book - Vol. 1', 'Chieko Kano và cộng sự', 'JPN103', 'Kệ G2 - Tầng 3', 10, 10),
('Basic Kanji Book - Vol. 2', 'Chieko Kano và cộng sự', 'JPN203', 'Kệ G2 - Tầng 3', 10, 10),
('Luyện nghe tiếng Nhật sơ cấp', 'Nhiều tác giả', 'JPN104', 'Kệ G2 - Tầng 3', 10, 10),
('Hội thoại tiếng Nhật giao tiếp hằng ngày', 'Nhiều tác giả', 'JPN105', 'Kệ G2 - Tầng 3', 10, 10),
('Từ điển Nhật - Việt', 'Nhiều tác giả', 'JPN100', 'Kệ G3 - Tầng 3', 10, 10),
('Tiếng Nhật thương mại (Business Japanese)', 'Nhiều tác giả', 'JPN320', 'Kệ G3 - Tầng 3', 10, 10),
('Kính ngữ trong tiếng Nhật', 'Nhiều tác giả', 'JPN321', 'Kệ G3 - Tầng 3', 10, 10),
('Tiếng Nhật IT - Từ vựng chuyên ngành công nghệ thông tin', 'Nhiều tác giả', 'JPN322', 'Kệ G3 - Tầng 3', 10, 10),
('Lý thuyết và thực hành biên dịch Nhật - Việt', 'Nhiều tác giả', 'JPN330', 'Kệ G3 - Tầng 3', 10, 10),
('Phiên dịch Nhật - Việt, Việt - Nhật', 'Nhiều tác giả', 'JPN331', 'Kệ G3 - Tầng 3', 10, 10),
('Văn hóa Nhật Bản', 'Nhiều tác giả', 'JPN140', 'Kệ G3 - Tầng 3', 10, 10),
('Lịch sử Nhật Bản', 'Nhiều tác giả', 'JPN141', 'Kệ G3 - Tầng 3', 10, 10),
('Văn học Nhật Bản', 'Nhiều tác giả', 'JPN240', 'Kệ G3 - Tầng 3', 10, 10),
('Đất nước học Nhật Bản', 'Nhiều tác giả', 'JPN142', 'Kệ G3 - Tầng 3', 10, 10);
-- ---------- NGÔN NGỮ ANH (Khu H, Tầng 3) ----------
INSERT INTO tmp_books (title, author, subject_code, shelf_location, total_qty, available_qty) VALUES
('English Grammar in Use', 'Raymond Murphy', 'ENG101', 'Kệ H1 - Tầng 3', 10, 10),
('Advanced Grammar in Use', 'Martin Hewings', 'ENG201', 'Kệ H1 - Tầng 3', 10, 10),
('Practical English Usage', 'Michael Swan', 'ENG201', 'Kệ H1 - Tầng 3', 10, 10),
('English Vocabulary in Use - Upper-intermediate', 'Michael McCarthy, Felicity O''Dell', 'ENG102', 'Kệ H1 - Tầng 3', 10, 10),
('English Pronunciation in Use - Intermediate', 'Mark Hancock', 'ENG103', 'Kệ H1 - Tầng 3', 10, 10),
('Ship or Sheep? - An Intermediate Pronunciation Course', 'Ann Baker', 'ENG103', 'Kệ H1 - Tầng 3', 10, 10),
('Oxford Advanced Learner''s Dictionary', 'A. S. Hornby', 'ENG100', 'Kệ H1 - Tầng 3', 10, 10),
('Oxford Collocations Dictionary for Students of English', 'Oxford University Press', 'ENG100', 'Kệ H1 - Tầng 3', 10, 10),
('Longman Dictionary of Contemporary English', 'Pearson Longman', 'ENG100', 'Kệ H1 - Tầng 3', 10, 10),
('Writing Academic English', 'Alice Oshima, Ann Hogue', 'ENG210', 'Kệ H1 - Tầng 3', 10, 10),
('The Elements of Style', 'William Strunk Jr., E. B. White', 'ENG210', 'Kệ H2 - Tầng 3', 10, 10),
('Collins Writing for IELTS', 'Anneli Williams', 'ENG220', 'Kệ H2 - Tầng 3', 10, 10),
('The Art of Public Speaking', 'Stephen E. Lucas', 'ENG230', 'Kệ H2 - Tầng 3', 10, 10),
('The Study of Language', 'George Yule', 'ENG301', 'Kệ H2 - Tầng 3', 10, 10),
('An Introduction to Language', 'Victoria Fromkin, Robert Rodman, Nina Hyams', 'ENG301', 'Kệ H2 - Tầng 3', 10, 10),
('English Phonetics and Phonology', 'Peter Roach', 'ENG302', 'Kệ H2 - Tầng 3', 10, 10),
('What is Morphology?', 'Mark Aronoff, Kirsten Fudeman', 'ENG303', 'Kệ H2 - Tầng 3', 10, 10),
('English Syntax: An Introduction', 'Jong-Bok Kim, Peter Sells', 'ENG304', 'Kệ H2 - Tầng 3', 10, 10),
('Semantics', 'John I. Saeed', 'ENG305', 'Kệ H2 - Tầng 3', 10, 10),
('Pragmatics', 'George Yule', 'ENG306', 'Kệ H2 - Tầng 3', 10, 10),
('Discourse Analysis', 'Gillian Brown, George Yule', 'ENG307', 'Kệ H3 - Tầng 3', 10, 10),
('An Introduction to Sociolinguistics', 'Janet Holmes', 'ENG308', 'Kệ H3 - Tầng 3', 10, 10),
('How Languages are Learned', 'Patsy M. Lightbown, Nina Spada', 'ENG310', 'Kệ H3 - Tầng 3', 10, 10),
('How to Teach English', 'Jeremy Harmer', 'ENG311', 'Kệ H3 - Tầng 3', 10, 10),
('A Course in Language Teaching', 'Penny Ur', 'ENG311', 'Kệ H3 - Tầng 3', 10, 10),
('In Other Words: A Coursebook on Translation', 'Mona Baker', 'ENG320', 'Kệ H3 - Tầng 3', 10, 10),
('Introducing Translation Studies', 'Jeremy Munday', 'ENG320', 'Kệ H3 - Tầng 3', 10, 10),
('Communication Between Cultures', 'Larry A. Samovar, Richard E. Porter', 'ENG330', 'Kệ H3 - Tầng 3', 10, 10),
('Business Vocabulary in Use - Intermediate', 'Bill Mascull', 'ENG340', 'Kệ H3 - Tầng 3', 10, 10),
('An Outline of American Literature', 'Peter B. High', 'ENG350', 'Kệ H3 - Tầng 3', 10, 10);
-- ---------- MARKETING (Khu I, Tầng 4) ----------
INSERT INTO tmp_books (title, author, subject_code, shelf_location, total_qty, available_qty) VALUES
('Principles of Marketing', 'Philip Kotler, Gary Armstrong', 'MKT101', 'Kệ I1 - Tầng 4', 10, 10),
('Marketing Management', 'Philip Kotler, Kevin Lane Keller', 'MKT201', 'Kệ I1 - Tầng 4', 10, 10),
('Marketing 4.0: Moving from Traditional to Digital', 'Philip Kotler, Hermawan Kartajaya, Iwan Setiawan', 'MKT202', 'Kệ I1 - Tầng 4', 10, 10),
('Marketing 5.0: Technology for Humanity', 'Philip Kotler, Hermawan Kartajaya, Iwan Setiawan', 'MKT202', 'Kệ I1 - Tầng 4', 10, 10),
('Consumer Behavior: Buying, Having, and Being', 'Michael R. Solomon', 'MKT210', 'Kệ I1 - Tầng 4', 10, 10),
('Marketing Research: An Applied Orientation', 'Naresh K. Malhotra', 'MKT220', 'Kệ I1 - Tầng 4', 10, 10),
('Strategic Brand Management', 'Kevin Lane Keller', 'MKT230', 'Kệ I1 - Tầng 4', 10, 10),
('Building Strong Brands', 'David A. Aaker', 'MKT230', 'Kệ I1 - Tầng 4', 10, 10),
('Positioning: The Battle for Your Mind', 'Al Ries, Jack Trout', 'MKT231', 'Kệ I1 - Tầng 4', 10, 10),
('The 22 Immutable Laws of Marketing', 'Al Ries, Jack Trout', 'MKT231', 'Kệ I1 - Tầng 4', 10, 10),
('Advertising and Promotion: An Integrated Marketing Communications Perspective', 'George E. Belch, Michael A. Belch', 'MKT240', 'Kệ I2 - Tầng 4', 10, 10),
('Ogilvy on Advertising', 'David Ogilvy', 'MKT241', 'Kệ I2 - Tầng 4', 10, 10),
('Hey Whipple, Squeeze This', 'Luke Sullivan', 'MKT241', 'Kệ I2 - Tầng 4', 10, 10),
('Digital Marketing', 'Dave Chaffey, Fiona Ellis-Chadwick', 'MKT250', 'Kệ I2 - Tầng 4', 10, 10),
('Content Inc.', 'Joe Pulizzi', 'MKT251', 'Kệ I2 - Tầng 4', 10, 10),
('Everybody Writes', 'Ann Handley', 'MKT251', 'Kệ I2 - Tầng 4', 10, 10),
('Jab, Jab, Jab, Right Hook', 'Gary Vaynerchuk', 'MKT252', 'Kệ I2 - Tầng 4', 10, 10),
('Growth Hacker Marketing', 'Ryan Holiday', 'MKT253', 'Kệ I2 - Tầng 4', 10, 10),
('Services Marketing: People, Technology, Strategy', 'Christopher Lovelock, Jochen Wirtz', 'MKT260', 'Kệ I2 - Tầng 4', 10, 10),
('Customer Relationship Management', 'Francis Buttle', 'MKT261', 'Kệ I2 - Tầng 4', 10, 10),
('Global Marketing Management', 'Warren J. Keegan', 'MKT270', 'Kệ I3 - Tầng 4', 10, 10),
('The Strategy and Tactics of Pricing', 'Thomas T. Nagle', 'MKT280', 'Kệ I3 - Tầng 4', 10, 10),
('SPIN Selling', 'Neil Rackham', 'MKT290', 'Kệ I3 - Tầng 4', 10, 10),
('Blue Ocean Strategy', 'W. Chan Kim, Renée Mauborgne', 'MKT301', 'Kệ I3 - Tầng 4', 10, 10),
('Influence: The Psychology of Persuasion', 'Robert B. Cialdini', 'MKT210', 'Kệ I3 - Tầng 4', 10, 10),
('Contagious: Why Things Catch On', 'Jonah Berger', 'MKT252', 'Kệ I3 - Tầng 4', 10, 10),
('Made to Stick', 'Chip Heath, Dan Heath', 'MKT240', 'Kệ I3 - Tầng 4', 10, 10),
('Hooked: How to Build Habit-Forming Products', 'Nir Eyal', 'MKT253', 'Kệ I3 - Tầng 4', 10, 10),
('Purple Cow', 'Seth Godin', 'MKT231', 'Kệ I3 - Tầng 4', 10, 10),
('Building a StoryBrand', 'Donald Miller', 'MKT232', 'Kệ I3 - Tầng 4', 10, 10);
-- ---------- TÀI CHÍNH - NGÂN HÀNG (Khu K, Tầng 4) ----------
INSERT INTO tmp_books (title, author, subject_code, shelf_location, total_qty, available_qty) VALUES
('Corporate Finance', 'Stephen A. Ross, Randolph W. Westerfield, Jeffrey Jaffe', 'FIN201', 'Kệ K1 - Tầng 4', 10, 10),
('Principles of Corporate Finance', 'Richard A. Brealey, Stewart C. Myers, Franklin Allen', 'FIN201', 'Kệ K1 - Tầng 4', 10, 10),
('Investments', 'Zvi Bodie, Alex Kane, Alan J. Marcus', 'FIN210', 'Kệ K1 - Tầng 4', 10, 10),
('The Economics of Money, Banking and Financial Markets', 'Frederic S. Mishkin', 'FIN101', 'Kệ K1 - Tầng 4', 10, 10),
('Options, Futures, and Other Derivatives', 'John C. Hull', 'FIN310', 'Kệ K1 - Tầng 4', 10, 10),
('Risk Management and Financial Institutions', 'John C. Hull', 'FIN320', 'Kệ K1 - Tầng 4', 10, 10),
('Financial Institutions Management', 'Anthony Saunders, Marcia Millon Cornett', 'FIN321', 'Kệ K1 - Tầng 4', 10, 10),
('Bank Management & Financial Services', 'Peter S. Rose, Sylvia C. Hudgins', 'BNK201', 'Kệ K1 - Tầng 4', 10, 10),
('Nghiệp vụ ngân hàng thương mại', 'Nhiều tác giả', 'BNK201', 'Kệ K1 - Tầng 4', 10, 10),
('Thanh toán quốc tế', 'Nhiều tác giả', 'BNK210', 'Kệ K1 - Tầng 4', 10, 10),
('Tín dụng và thẩm định tín dụng ngân hàng', 'Nhiều tác giả', 'BNK220', 'Kệ K2 - Tầng 4', 10, 10),
('Luật Các tổ chức tín dụng (văn bản hợp nhất)', 'Quốc hội Việt Nam', 'BNK230', 'Kệ K2 - Tầng 4', 10, 10),
('Financial Statement Analysis', 'K. R. Subramanyam', 'FIN220', 'Kệ K2 - Tầng 4', 10, 10),
('Valuation: Measuring and Managing the Value of Companies', 'McKinsey & Company - Tim Koller và cộng sự', 'FIN301', 'Kệ K2 - Tầng 4', 10, 10),
('International Financial Management', 'Jeff Madura', 'FIN330', 'Kệ K2 - Tầng 4', 10, 10),
('Financial Accounting', 'Jerry J. Weygandt, Paul D. Kimmel, Donald E. Kieso', 'ACC101', 'Kệ K2 - Tầng 4', 10, 10),
('Managerial Accounting', 'Ray H. Garrison, Eric W. Noreen, Peter C. Brewer', 'ACC201', 'Kệ K2 - Tầng 4', 10, 10),
('Principles of Economics', 'N. Gregory Mankiw', 'ECO101', 'Kệ K2 - Tầng 4', 10, 10),
('Macroeconomics', 'N. Gregory Mankiw', 'ECO111', 'Kệ K2 - Tầng 4', 10, 10),
('Introductory Econometrics: A Modern Approach', 'Jeffrey M. Wooldridge', 'ECO201', 'Kệ K2 - Tầng 4', 10, 10),
('Tài chính công', 'Nhiều tác giả', 'FIN230', 'Kệ K3 - Tầng 4', 10, 10),
('Thuế', 'Nhiều tác giả', 'FIN231', 'Kệ K3 - Tầng 4', 10, 10),
('Thẩm định dự án đầu tư', 'Nhiều tác giả', 'FIN240', 'Kệ K3 - Tầng 4', 10, 10),
('The FinTech Book', 'Susanne Chishti, Janos Barberis', 'FIN350', 'Kệ K3 - Tầng 4', 10, 10),
('The Intelligent Investor', 'Benjamin Graham', 'FIN211', 'Kệ K3 - Tầng 4', 10, 10),
('Security Analysis', 'Benjamin Graham, David Dodd', 'FIN211', 'Kệ K3 - Tầng 4', 10, 10),
('A Random Walk Down Wall Street', 'Burton G. Malkiel', 'FIN212', 'Kệ K3 - Tầng 4', 10, 10),
('Common Stocks and Uncommon Profits', 'Philip A. Fisher', 'FIN212', 'Kệ K3 - Tầng 4', 10, 10),
('Thinking, Fast and Slow', 'Daniel Kahneman', 'FIN360', 'Kệ K3 - Tầng 4', 10, 10),
('The Psychology of Money', 'Morgan Housel', 'FIN360', 'Kệ K3 - Tầng 4', 10, 10);

-- Chỉ thêm những sách chưa có trong thư viện
INSERT INTO books (title, author, subject_code, shelf_location, total_qty, available_qty)
SELECT t.title, t.author, t.subject_code, t.shelf_location, t.total_qty, t.available_qty
FROM tmp_books t
WHERE NOT EXISTS (SELECT 1 FROM books b WHERE b.title = t.title);

DROP TEMPORARY TABLE tmp_books;


-- #########################################################
-- PHẦN 2: GIÁO TRÌNH MỞ OPENSTAX
-- #########################################################
-- =========================================================
-- THÊM 20 GIÁO TRÌNH MỞ OPENSTAX (miễn phí 100%, hợp pháp, đọc online toàn bộ)
--   Nguồn: https://openstax.org (Rice University) — giấy phép Creative Commons, ai cũng được đọc
--   Mỗi cuốn có link tới trang sách trên OpenStax, bấm "View online / Xem online" để đọc
-- An toàn khi chạy nhiều lần: sách đã có (trùng tên) sẽ được bỏ qua.
-- Mã môn là mã đặt tạm để tìm kiếm; sửa theo mã môn thật của trường nếu cần.
-- =========================================================


DROP TEMPORARY TABLE IF EXISTS tmp_books;
CREATE TEMPORARY TABLE tmp_books LIKE books;

INSERT INTO tmp_books (title, author, subject_code, book_link, read_access, shelf_location, total_qty, available_qty) VALUES
-- ---------- Quản trị – Kinh doanh ----------
('Introduction to Business (OpenStax)',                          'OpenStax', 'BUS101', 'https://openstax.org/details/books/introduction-business',              'full', 'Kệ O1 - Tầng 1', 10, 10),
('Principles of Management (OpenStax)',                          'OpenStax', 'MGT103', 'https://openstax.org/details/books/principles-management',              'full', 'Kệ O1 - Tầng 1', 10, 10),
('Organizational Behavior (OpenStax)',                           'OpenStax', 'MGT201', 'https://openstax.org/details/books/organizational-behavior',            'full', 'Kệ O1 - Tầng 1', 10, 10),
('Entrepreneurship (OpenStax)',                                  'OpenStax', 'ENT301', 'https://openstax.org/details/books/entrepreneurship',                   'full', 'Kệ O1 - Tầng 1', 10, 10),
('Business Ethics (OpenStax)',                                   'OpenStax', 'ETH201', 'https://openstax.org/details/books/business-ethics',                    'full', 'Kệ O1 - Tầng 1', 10, 10),
('Business Law I Essentials (OpenStax)',                         'OpenStax', 'LAW102', 'https://openstax.org/details/books/business-law-i-essentials',          'full', 'Kệ O1 - Tầng 1', 10, 10),
('Introduction to Intellectual Property (OpenStax)',             'OpenStax', 'LAW201', 'https://openstax.org/details/books/introduction-intellectual-property', 'full', 'Kệ O1 - Tầng 1', 10, 10),
-- ---------- Marketing ----------
('Principles of Marketing (OpenStax)',                           'OpenStax', 'MKT101', 'https://openstax.org/details/books/principles-marketing',               'full', 'Kệ O2 - Tầng 1', 10, 10),
-- ---------- Tài chính – Kế toán – Kinh tế ----------
('Principles of Finance (OpenStax)',                             'OpenStax', 'FIN202', 'https://openstax.org/details/books/principles-finance',                 'full', 'Kệ O2 - Tầng 1', 10, 10),
('Principles of Accounting, Volume 1: Financial Accounting (OpenStax)',  'OpenStax', 'ACC101', 'https://openstax.org/details/books/principles-financial-accounting', 'full', 'Kệ O2 - Tầng 1', 10, 10),
('Principles of Accounting, Volume 2: Managerial Accounting (OpenStax)', 'OpenStax', 'ACC102', 'https://openstax.org/details/books/principles-managerial-accounting','full', 'Kệ O2 - Tầng 1', 10, 10),
('Principles of Economics 3e (OpenStax)',                        'OpenStax', 'ECO101', 'https://openstax.org/details/books/principles-economics-3e',            'full', 'Kệ O2 - Tầng 1', 10, 10),
('Principles of Microeconomics 3e (OpenStax)',                   'OpenStax', 'ECO111', 'https://openstax.org/details/books/principles-microeconomics-3e',       'full', 'Kệ O2 - Tầng 1', 10, 10),
('Principles of Macroeconomics 3e (OpenStax)',                   'OpenStax', 'ECO121', 'https://openstax.org/details/books/principles-macroeconomics-3e',       'full', 'Kệ O2 - Tầng 1', 10, 10),
-- ---------- Công nghệ thông tin ----------
('Introduction to Computer Science (OpenStax)',                  'OpenStax', 'CSI104', 'https://openstax.org/details/books/introduction-computer-science',      'full', 'Kệ O3 - Tầng 1', 10, 10),
('Introduction to Python Programming (OpenStax)',                'OpenStax', 'PFP191', 'https://openstax.org/details/books/introduction-python-programming',    'full', 'Kệ O3 - Tầng 1', 10, 10),
('Principles of Data Science (OpenStax)',                        'OpenStax', 'DAP391', 'https://openstax.org/details/books/principles-data-science',            'full', 'Kệ O3 - Tầng 1', 10, 10),
('Foundations of Information Systems (OpenStax)',                'OpenStax', 'MIS101', 'https://openstax.org/details/books/foundations-information-systems',    'full', 'Kệ O3 - Tầng 1', 10, 10),
('Workplace Software and Skills (OpenStax)',                     'OpenStax', 'OSS101', 'https://openstax.org/details/books/workplace-software-skills',          'full', 'Kệ O3 - Tầng 1', 10, 10),
('Introductory Statistics 2e (OpenStax)',                        'OpenStax', 'MAS291', 'https://openstax.org/details/books/introductory-statistics-2e',         'full', 'Kệ O3 - Tầng 1', 10, 10);

INSERT INTO books (title, author, subject_code, book_link, read_access, shelf_location, total_qty, available_qty)
SELECT t.title, t.author, t.subject_code, t.book_link, t.read_access, t.shelf_location, t.total_qty, t.available_qty
FROM tmp_books t
WHERE NOT EXISTS (SELECT 1 FROM books b WHERE b.title = t.title);

DROP TEMPORARY TABLE IF EXISTS tmp_books;


-- #########################################################
-- PHẦN 3: SINH VIÊN LỚP EXE101_G11
-- #########################################################
-- =========================================================
-- Thêm 37 sinh viên lớp EXE101_G11 (nguồn: DSSV CHIA NHÓM EXE101_THOLCB.xlsx)
-- Mật khẩu ban đầu: 123456 (đã mã hóa bcrypt, mỗi người một mã băm riêng)
-- An toàn khi chạy nhiều lần:
--   - Sinh viên mới: được thêm vào
--   - Mã học sinh đã có: cập nhật họ tên, lớp, email;
--     CHỈ đặt mật khẩu 123456 nếu tài khoản đó chưa có mật khẩu (không ghi đè mật khẩu đang dùng)
-- =========================================================

INSERT INTO members (student_code, name, class_name, contact, status, password_hash) VALUES
('CA190221', 'Nguyễn Thị Như Ý', 'EXE101_G11', 'yntn.ca190221@gmail.com', 'active', '$2y$10$0q4BqNBQEJPIVwcbVOKsoubW0v0mAGR1qsGMsKR.G8VmAZHBa1Fum'),
('CA181901', 'Nguyễn Thị Trúc Ly', 'EXE101_G11', 'LyNTTCA181901@fpt.edu.vn', 'active', '$2y$10$kfoecv5MkUbOcSlog1ZuKOmOC355XgHk5VcQX/1Epy6/0av6CeDji'),
('CE190931', 'Tạ Phượng Quyên', 'EXE101_G11', 'quyentp.ce190931@gmail.com', 'active', '$2y$10$J9gdNhuiPyLcPuHQS8tEvO56z4Rg35NdTFogtEY4748U/JcIhWOy.'),
('CS191317', 'Nguyễn Võ Ngọc Thanh', 'EXE101_G11', 'thanhnvn.cs191317@gmail.com', 'active', '$2y$10$iWkLefnDFZLStiLUJQiaZe7BIlnVlW38wtbCyzryvnc1Mlu4gV3Dq'),
('CE191247', 'Nguyễn Tuấn Thanh', 'EXE101_G11', 'ThanhNT.CE191247@gmail.com', 'active', '$2y$10$/irbeqNo7vPy/M920YPzp.VF4lL0pju6PPoFlbc2PKR96blhPrlte'),
('CE191636', 'Dương Trọng Khải', 'EXE101_G11', 'Khaidt.ce191636@gmail.com', 'active', '$2y$10$feyEIOt3ANVk7sKhXOoxlez53NsjZ5.kL3jfUQxyd7LGo0orl5Ghm'),
('CE191344', 'Lê Thiên Phúc', 'EXE101_G11', 'PhucLT.CE191344@gmail.com', 'active', '$2y$10$3BgccwCGPXZke1Fd9NAgFuKPReRsKj6X6J7WXVXO/Fnwpv0WQeK96'),
('CS190349', 'Trần Đặng Quỳnh Mai', 'EXE101_G11', 'tdqmai.cs190349@gmail.com', 'active', '$2y$10$31MX0Swe6SpDfJ1tAe5EqO23daPYfsqoZfzh.LqIzyeZhCZ33pF7i'),
('CS190786', 'Đặng Ngọc Lan Vy', 'EXE101_G11', 'Vydnl.cs190786@gmail.com', 'active', '$2y$10$6WGy/mp7RdxbqM/nAhTOd.XJIRm5CgDZ6mroCckFwMhahpvDlaaG.'),
('CS190840', 'Trần Thị Minh Nguyệt', 'EXE101_G11', 'Nguyetttm.cs190840@gmail.com', 'active', '$2y$10$5A.3D56GPFRWRS7mcPEEYOUUPMas7QbIix20O8QV92tNw9vpqgV2K'),
('CE190315', 'Phạm Úy Thương', 'EXE101_G11', 'chodao24705@gmail.com', 'active', '$2y$10$JSoseHKeOzhqaSN2BfZTheMD2Ahqu2nxAGKPB07Vr7aOP6TrsvsIO'),
('CS180050', 'Phạm Tuấn Vũ', 'EXE101_G11', 'VuPTCS180050@fpt.edu.vn', 'active', '$2y$10$hxIEQ9FtExWnkg2OgQrn/OeaHw.Nzp91Vfidbyl3tlRtINZtODuza'),
('CE190069', 'Nguyễn Trung Hậu', 'EXE101_G11', 'hauntce190069@gmail.com', 'active', '$2y$10$zndWl22aoYoOzF7ZYRfKpusICECz32xqD/jojIuekjVwmCRdH/rNi'),
('CE190411', 'Trần Thị Kim Ngân', 'EXE101_G11', 'kimngantt.ce190411@gmail.com', 'active', '$2y$10$xqgMoHVN1RIbi0xYdua4suwfgV54bhvoaOOKAUfIM7jHirsyaXfzK'),
('CE191041', 'Dương Ngọc Diễm Trân', 'EXE101_G11', 'trandnd.ce191041@gmail.com', 'active', '$2y$10$o8e7jEXf.lhzMlwpMyEP/.q/g0fbxERWfVgB8BN8P0zeD0PWHLEGm'),
('CS180373', 'Phan Ngọc Quí Châu', 'EXE101_G11', 'ChauPNQCS180373@fpt.edu.vn', 'active', '$2y$10$nAchuGezRTW15tbpLSZlaOnLrNduiFkt4VcKrAqCB8vSWtd6Is2J.'),
('CS190253', 'Nguyễn Quang Minh', 'EXE101_G11', 'Minhnq.cs190253@gmail.com', 'active', '$2y$10$qCjX.Wx8iFlyL1NzMfFEM.ik.NUPCbrZFPhCgXej6ctwE6TuHNx5u'),
('CS191501', 'Đặng Thị Bích Trăm', 'EXE101_G11', 'TramDTB.CS191501@gmail.com', 'active', '$2y$10$EoOEwz8q8k7hAz9luYKXUObviJKxE5srgfBccLzsIm2PaB6czhRFK'),
('CA181144', 'Nguyễn Thị Kim Ngọc', 'EXE101_G11', 'NgocNTKCA181144@fpt.edu.vn', 'active', '$2y$10$O7jC9C.FxiWFR4vhOLrRMeyHedwZZPNKAxL09AFAFQs44LPuU10ee'),
('CE190030', 'Quách Hữu Bằng', 'EXE101_G11', 'bangqh.ce190030@gmail.com', 'active', '$2y$10$vjXzP/1MlmH.V2GRwOZqcO8AiLSH/19lv39.jqIFL.JER6KjTJ1Xa'),
('CE191328', 'Nguyễn Trường Thái', 'EXE101_G11', 'thaint.ce191328@gmail.com', 'active', '$2y$10$06.6vliNsGpanM7vGwlHM.TZ0h8mKRao0S41.JF1QCLkDnq6w95O6'),
('CS191149', 'Hồ Ánh Sao Băng', 'EXE101_G11', 'Banghascs191149@gmail.com', 'active', '$2y$10$T8r/e.ODzMYuGRHn1fs68eco4hVQRp9Tl6qigDZWXktL3GtNSE3Oq'),
('CS191283', 'Trần Thái Gia Huy', 'EXE101_G11', 'huyttg.cs191283@gmail.com', 'active', '$2y$10$IpER7h.fwkDZtAoZK9Tyd.w.zt1Yptuc.0hLibdZ.YLOoKq0ZR7dG'),
('CE190490', 'Hứa Hồ Nhân Nghĩa', 'EXE101_G11', 'nghiahhn.ce190490@gmail.com', 'active', '$2y$10$w6NAJCg4hVcjJlERl0G8debHr/UObXYX5a1U.6/D73IjvvLRf.aOO'),
('CE190570', 'Ngô Đức Thiện', 'EXE101_G11', 'thiennd.ce190570@gmail.com', 'active', '$2y$10$DNtZMJ/bk30tvJ5WWwnU9uJtuOom/F1DHPMnlZbWfbVZtzTJbjQVa'),
('CE191171', 'Lý Lê Dung Ngọc', 'EXE101_G11', 'ngoclld.ce191171@gmail.com', 'active', '$2y$10$tso218U4yRkAYTQvSW5dp.TZlqUYwlpKN7.N7ypSNoFEzY3Ke/PZ.'),
('CS190265', 'Nguyễn Tuấn Kiệt', 'EXE101_G11', 'kietnt.cs190265@gmail.com', 'active', '$2y$10$Z6OHTeoIOExZzTNpcB36qOihdkIwJplBv..Fhppp.e9J9bYdRQb1G'),
('CS190949', 'Nguyễn Thị Yến Nhi', 'EXE101_G11', 'Nhinty.cs190949@gmail.com', 'active', '$2y$10$NOTHeCfbIlXERNTrEG3ijeS.qJzvAvMBURMbEBmMbtRU86pP6qEqq'),
('CA191248', 'Nguyễn Minh Trí', 'EXE101_G11', 'TriNM.CA191248@gmail.com', 'active', '$2y$10$7T9SQRgQqiEkB7j46FjRDufUeKK05dRUwWRFqNv23vW6x.7E/snJO'),
('CA190300', 'Huỳnh Thị Thảo Quyên', 'EXE101_G11', 'quyenhtt.ca190300@gmail.com', 'active', '$2y$10$wt7Evh3rrsqe8pcPU/AiQ.uul82tBlbPtMffjxi.xeTi.apXKWsMK'),
('CA190953', 'Ngũ Minh Anh', 'EXE101_G11', 'anhnm.ca190953@gmail.com', 'active', '$2y$10$s7ZEKyRMculE/x4V8V3pVOXun4v/Ss0T1voBKw8/QWwkmAI.lnR/a'),
('CS191374', 'Đặng Bảo Ngọc', 'EXE101_G11', 'dangbaongoc.cs191374@gmail.com', 'active', '$2y$10$knEKhuS7AYv5NZnf/iD0xeKt/2cJ5V.Q5er5THV.Vlm43m7FeBle.'),
('CS191768', 'Huỳnh Thị Tâm Như', 'EXE101_G11', 'nhuhtt.cs191768@gmail.com', 'active', '$2y$10$Z2Ayzt6xqTa4NeEza/fVOe6.90gA2FBepmsdNpQnVWhqiZ/shX6tG'),
('CA190323', 'Nguyễn Thị Tuyết Ngọc', 'EXE101_G11', 'ngocntt.ca190323@gmail.com', 'active', '$2y$10$19U5gD2euFLQlW81uG308O89beF1t0sRO.cNU9AcaThmH4iuf7qDq'),
('CE180783', 'Trần Đình Phương Ngân', 'EXE101_G11', 'NganTDPCE180783@fpt.edu.vn', 'active', '$2y$10$RrzVz3T0KkNHM3s753qUeOki4nP1yXcwzj1ecCxEYB4HGW0E5NiQm'),
('CS190704', 'Đặng Thanh Tuấn', 'EXE101_G11', 'tuandt.cs190704@gmail.com', 'active', '$2y$10$3Xx3Na6VGezKmGEbI6.wOOluboF8zSWraEz4Boy6XFv8g6o0bxTEO'),
('CS190797', 'Nguyễn Thị Thúy Liễu', 'EXE101_G11', 'lieuntt.cs190797@gmail.com', 'active', '$2y$10$LcPT/UUrnHm8h6sdAlB7eOaUcpjkc3YYo1RncelvAy9jNylHmdFei')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  class_name = VALUES(class_name),
  contact = VALUES(contact),
  password_hash = COALESCE(password_hash, VALUES(password_hash));


-- ================= KẾT QUẢ =================
SELECT
  (SELECT COUNT(*) FROM books)                              AS tong_dau_sach,
  (SELECT COUNT(*) FROM books WHERE read_access = 'full')   AS sach_doc_online_toan_bo,
  (SELECT COUNT(*) FROM members WHERE class_name = 'EXE101_G11') AS sinh_vien_G11;
