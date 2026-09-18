-- Chạy file này trong phpMyAdmin (tab SQL) để tạo database và các bảng cần thiết.

CREATE DATABASE IF NOT EXISTS library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_db;

CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255),
  subject_code VARCHAR(50),
  book_link VARCHAR(500),
  shelf_location VARCHAR(100),
  total_qty INT NOT NULL DEFAULT 1,
  available_qty INT NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_code VARCHAR(50) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  class_name VARCHAR(100),
  contact VARCHAR(255),
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  password_hash VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  member_id INT NOT NULL,
  borrow_date DATE NOT NULL,
  due_date DATE NOT NULL,
  return_date DATE NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'borrowed',
  fine INT NOT NULL DEFAULT 0,
  FOREIGN KEY (book_id) REFERENCES books(id),
  FOREIGN KEY (member_id) REFERENCES members(id)
);

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL
);

-- Ghi lại các lần đăng nhập sai để tạm khóa khi sai quá nhiều (api/auth_login.php cũng tự tạo bảng này)
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  attempted_at DATETIME NOT NULL,
  INDEX idx_user_time (username, attempted_at)
);

-- Phiên đăng nhập (config.php cũng tự tạo bảng này). Giúp không bị đăng xuất khi Render deploy lại.
CREATE TABLE IF NOT EXISTS sessions (
  id VARCHAR(128) NOT NULL PRIMARY KEY,
  data MEDIUMTEXT NOT NULL,
  last_access INT UNSIGNED NOT NULL,
  INDEX idx_last_access (last_access)
);

-- Mã xác nhận quên mật khẩu (api/forgot_password.php cũng tự tạo bảng này)
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_member (member_id, created_at)
);