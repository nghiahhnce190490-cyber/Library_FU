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

SELECT COUNT(*) AS so_sinh_vien_lop_G11 FROM members WHERE class_name = 'EXE101_G11';
