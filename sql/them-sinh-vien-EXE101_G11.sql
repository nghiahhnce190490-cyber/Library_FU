-- =========================================================
-- Thêm 37 sinh viên lớp EXE101_G11 (nguồn: DSSV CHIA NHÓM EXE101_THOLCB.xlsx)
-- Mật khẩu ban đầu: 123456 (đã mã hóa bcrypt, mỗi người một mã băm riêng)
-- An toàn khi chạy nhiều lần:
--   - Sinh viên mới: được thêm vào
--   - Mã học sinh đã có: cập nhật họ tên, lớp, email;
--     CHỈ đặt mật khẩu 123456 nếu tài khoản đó chưa có mật khẩu (không ghi đè mật khẩu đang dùng)
-- =========================================================
SET NAMES utf8mb4;
USE library_db;

INSERT INTO members (student_code, name, class_name, contact, status, password_hash) VALUES
('CA190221', 'Nguyễn Thị Như Ý', 'EXE101_G11', 'yntn.ca190221@gmail.com', 'active', '$2y$12$X25qjJcW3RO0fsctZSWBMe.mFZKsXhTsfjNBQOKt8A/tisnSNLDMu'),
('CA181901', 'Nguyễn Thị Trúc Ly', 'EXE101_G11', 'LyNTTCA181901@fpt.edu.vn', 'active', '$2y$12$9bC9Iy1Ah7pGWvJFth5xk.WyaV.pAUjHuk1aaJFTJoKQxpxmkH8d6'),
('CE190931', 'Tạ Phượng Quyên', 'EXE101_G11', 'quyentp.ce190931@gmail.com', 'active', '$2y$12$o/Q/gYRurYrvBGdJpv0FY.m8cCh4BlFcj0/H0sYbfujqi7863Lp4q'),
('CS191317', 'Nguyễn Võ Ngọc Thanh', 'EXE101_G11', 'thanhnvn.cs191317@gmail.com', 'active', '$2y$12$bmu3S/BwxJDGLtSsGCditeWRCCAXA1BwqCnfOkAECUv02/jUkggj6'),
('CE191247', 'Nguyễn Tuấn Thanh', 'EXE101_G11', 'ThanhNT.CE191247@gmail.com', 'active', '$2y$12$hKE09rjAiuShefklI74cv.sqoXXuGjN9453LwCirlKGnyx7JMKPxW'),
('CE191636', 'Dương Trọng Khải', 'EXE101_G11', 'Khaidt.ce191636@gmail.com', 'active', '$2y$12$KFoyLdlFCo9/ucs5WFnNS.jOHn7o2pTqxujXSmqYDOmSPpNRdt1c6'),
('CE191344', 'Lê Thiên Phúc', 'EXE101_G11', 'PhucLT.CE191344@gmail.com', 'active', '$2y$12$5O1DpBbHqWe8fS5Rgj07jO4Az67GFaEN9q5fxPqHr3qzbfDCwpFfq'),
('CS190349', 'Trần Đặng Quỳnh Mai', 'EXE101_G11', 'tdqmai.cs190349@gmail.com', 'active', '$2y$12$FCB/BQq/qxYG51Yepe9gIOWr3gPbikDBEK5H3hYCOaulWg6aloSnG'),
('CS190786', 'Đặng Ngọc Lan Vy', 'EXE101_G11', 'Vydnl.cs190786@gmail.com', 'active', '$2y$12$ApXNqdWKq2i1MN6.gdY0VuJSsiKaHDSmP2Ha0LzT/j6fQ.m4T/2sy'),
('CS190840', 'Trần Thị Minh Nguyệt', 'EXE101_G11', 'Nguyetttm.cs190840@gmail.com', 'active', '$2y$12$hrKiiQlvnHq9qkOppQXZ3eXNBr.EwyE1GlAisCHJfRYqRU7S3JLRy'),
('CE190315', 'Phạm Úy Thương', 'EXE101_G11', 'chodao24705@gmail.com', 'active', '$2y$12$h234N4Tk7q3ovqkmFS44RudvOwO2CThLZMiOu8GJ4sLiUOihrcuT6'),
('CS180050', 'Phạm Tuấn Vũ', 'EXE101_G11', 'VuPTCS180050@fpt.edu.vn', 'active', '$2y$12$tHV5ihTXPs2rbeE9vbwBFefA/AnlZs7V4JSseYmjbVzd6j6OUb1PG'),
('CE190069', 'Nguyễn Trung Hậu', 'EXE101_G11', 'hauntce190069@gmail.com', 'active', '$2y$12$H0mPGqbgudFqjHhk/0Q09O.JFcCYPfXXjkTEFIT712thM1QHYrKpK'),
('CE190411', 'Trần Thị Kim Ngân', 'EXE101_G11', 'kimngantt.ce190411@gmail.com', 'active', '$2y$12$k.5nrWEItcKct5fXULsLzuFRDeycN43uj20Wt3IjUk0FxXp5LOHWe'),
('CE191041', 'Dương Ngọc Diễm Trân', 'EXE101_G11', 'trandnd.ce191041@gmail.com', 'active', '$2y$12$y4Ag4DhC9oVumzydGEreJ.FxrkBLA7XSYB.IiF/e3M2UQCFauJjzW'),
('CS180373', 'Phan Ngọc Quí Châu', 'EXE101_G11', 'ChauPNQCS180373@fpt.edu.vn', 'active', '$2y$12$GAEzvJOXYl5OyePM5tvrieLOEjoBHBlsBKVJlCKXfQepXVlTlW40K'),
('CS190253', 'Nguyễn Quang Minh', 'EXE101_G11', 'Minhnq.cs190253@gmail.com', 'active', '$2y$12$ouxy0O/kO93ZLYLkMMiXFuzJl8PUTmVzexqD1f1EJTkolaUSqIfYe'),
('CS191501', 'Đặng Thị Bích Trăm', 'EXE101_G11', 'TramDTB.CS191501@gmail.com', 'active', '$2y$12$uNmQah9VZ1VN8X3JlQeI9eIdhyX0nVecU9UGIFRvUvjDYdpbUJvUi'),
('CA181144', 'Nguyễn Thị Kim Ngọc', 'EXE101_G11', 'NgocNTKCA181144@fpt.edu.vn', 'active', '$2y$12$20Y/ri5XCyjCzbKdYq8X9OGrmLCzCeMHqSXk.A.tBVlPDmTgbag8a'),
('CE190030', 'Quách Hữu Bằng', 'EXE101_G11', 'bangqh.ce190030@gmail.com', 'active', '$2y$12$Ji93wSMMY8qB04SWpGybu.7hfcINZUuaeqdgBhj9ojxV1yJvC8tSa'),
('CE191328', 'Nguyễn Trường Thái', 'EXE101_G11', 'thaint.ce191328@gmail.com', 'active', '$2y$12$Z.7iKkZfOzOWi5w.h8ovZ.LtrL7dgLwqWqXzl8SjREahm.rXGrbUy'),
('CS191149', 'Hồ Ánh Sao Băng', 'EXE101_G11', 'Banghascs191149@gmail.com', 'active', '$2y$12$5zjJcA3o7vtqIJpChfx4R.j7/jo1HwoX2fHxTfZUj83xYcYq7hMnm'),
('CS191283', 'Trần Thái Gia Huy', 'EXE101_G11', 'huyttg.cs191283@gmail.com', 'active', '$2y$12$Z.Gs9DfB2.Zq3yQuFvCsXeXFuKQxOV8Bzz4eIWUVBa8M1lOalXU6e'),
('CE190490', 'Hứa Hồ Nhân Nghĩa', 'EXE101_G11', 'nghiahhn.ce190490@gmail.com', 'active', '$2y$12$ZRV8KHaLtx2RF4XZkWn3leaR6uQAakNIao.fz07foX4zTx0tCUbXe'),
('CE190570', 'Ngô Đức Thiện', 'EXE101_G11', 'thiennd.ce190570@gmail.com', 'active', '$2y$12$oqxm.dNc9.qiRwcKrD/JA.LegDUNF9EQe7t1UQoxrEsiGvv4xAovi'),
('CE191171', 'Lý Lê Dung Ngọc', 'EXE101_G11', 'ngoclld.ce191171@gmail.com', 'active', '$2y$12$8oX9lHucx/qC8ARw6aSgteod5dSAbiMpxmCGtYta8dh6Krn5uXLtC'),
('CS190265', 'Nguyễn Tuấn Kiệt', 'EXE101_G11', 'kietnt.cs190265@gmail.com', 'active', '$2y$12$odv93jsTsDy3p9W57Wsf4./KOpT3f2h72ooIEA8vf9e4qzSmVtGXW'),
('CS190949', 'Nguyễn Thị Yến Nhi', 'EXE101_G11', 'Nhinty.cs190949@gmail.com', 'active', '$2y$12$GYGlMQFaH2Q5g1ZXws10geh2U9mbUGpzqVQT.cls78NDE6NICfiZK'),
('CA191248', 'Nguyễn Minh Trí', 'EXE101_G11', 'TriNM.CA191248@gmail.com', 'active', '$2y$12$RiZtz4IdspkfhU0E0ab24uHfLm3TKxP0C4UJ6pHHisH0b3gE9q8a6'),
('CA190300', 'Huỳnh Thị Thảo Quyên', 'EXE101_G11', 'quyenhtt.ca190300@gmail.com', 'active', '$2y$12$NZtxMhHi7jH0Nj0XNp/P5.lG//4fPUDwyjSHLbLtJSrzCigNGgrDK'),
('CA190953', 'Ngũ Minh Anh', 'EXE101_G11', 'anhnm.ca190953@gmail.com', 'active', '$2y$12$T9SQx03r/HYy0BDbRBFVjexUHmvb9ReW.VkeesnupKQTLq6Yi56xu'),
('CS191374', 'Đặng Bảo Ngọc', 'EXE101_G11', 'dangbaongoc.cs191374@gmail.com', 'active', '$2y$12$lslNqqciPjmTmKS76cycweCBgH3GFfry5nxb8q9es.jeA9RJJDc8O'),
('CS191768', 'Huỳnh Thị Tâm Như', 'EXE101_G11', 'nhuhtt.cs191768@gmail.com', 'active', '$2y$12$CIJioS.BCnPG6n0uVIu0Nu.sXh4SLxNEtmBed3SS9tBxgGvPKDeqO'),
('CA190323', 'Nguyễn Thị Tuyết Ngọc', 'EXE101_G11', 'ngocntt.ca190323@gmail.com', 'active', '$2y$12$DCM05yraPyPKkk3/kW95BeZavH4gfmPSNVvQJHJBhjEwaGJ3rtvOq'),
('CE180783', 'Trần Đình Phương Ngân', 'EXE101_G11', 'NganTDPCE180783@fpt.edu.vn', 'active', '$2y$12$uTJ/i6i6ZBqXsACyZCAstePfEfzf7uN0.mJGVAfxz4y8aIfB5lUQO'),
('CS190704', 'Đặng Thanh Tuấn', 'EXE101_G11', 'tuandt.cs190704@gmail.com', 'active', '$2y$12$IPOf9/b8wzcpdDW4BNhne.rjSXqKrTQcJUD0D28pVQI8nb0jLCdPa'),
('CS190797', 'Nguyễn Thị Thúy Liễu', 'EXE101_G11', 'lieuntt.cs190797@gmail.com', 'active', '$2y$12$2IM4bG2MRMTVc8XBv3/BIOKLwpCAG6/L5VnlcAnLH.wYyYz4nPbMa')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  class_name = VALUES(class_name),
  contact = VALUES(contact),
  password_hash = COALESCE(password_hash, VALUES(password_hash));

SELECT COUNT(*) AS so_sinh_vien_lop_G11 FROM members WHERE class_name = 'EXE101_G11';
