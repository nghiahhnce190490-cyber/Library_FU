-- =========================================================
-- THÊM SÁCH MẪU CHO 6 NGÀNH (180 đầu sách, mỗi cuốn số lượng 10)
--   Công nghệ thông tin (30) · Ngôn ngữ Hàn (30) · Ngôn ngữ Nhật (30)
--   Ngôn ngữ Anh (30) · Marketing (30) · Tài chính - Ngân hàng (30)
-- An toàn khi chạy nhiều lần: sách đã có (trùng tên) sẽ được bỏ qua.
-- Mã môn là mã đặt tạm để tìm kiếm; sửa theo mã môn thật của trường nếu cần.
-- =========================================================
SET NAMES utf8mb4;
USE library_db;

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

SELECT ROW_COUNT() AS so_sach_vua_them;
SELECT COUNT(*) AS tong_dau_sach, SUM(total_qty) AS tong_so_cuon FROM books;
DROP TEMPORARY TABLE tmp_books;
