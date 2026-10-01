-- Chạy MỘT LẦN trên cả CSDL local (phpMyAdmin) và SkySQL.
-- Nhớ sao lưu (backup-libgo.bat) trước khi chạy trên SkySQL.
-- Chạy lại nhiều lần cũng không lỗi nhờ "IF NOT EXISTS" (MariaDB).

USE library_db;

ALTER TABLE books
  ADD COLUMN IF NOT EXISTS isbn VARCHAR(13) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS publisher VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS publish_year SMALLINT NULL,
  ADD COLUMN IF NOT EXISTS cover_url VARCHAR(500) NULL;

-- Mỗi ISBN chỉ ứng với một đầu sách. Sách không có ISBN để NULL (được phép trùng NULL).
CREATE UNIQUE INDEX IF NOT EXISTS uq_books_isbn ON books (isbn);
