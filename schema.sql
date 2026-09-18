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
