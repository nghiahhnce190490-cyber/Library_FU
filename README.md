# Hệ thống Quản lý Thư viện — Checkin/Checkout

Ứng dụng web quản lý mượn/trả sách cho thư viện trường học, xây dựng bằng **PHP thuần + MySQL/MariaDB**, giao diện HTML/CSS/JavaScript thuần (không dùng framework).

**Demo trực tuyến:** https://library-fu.onrender.com

## Tính năng chính

- Tìm kiếm sách theo tên, tác giả, mã môn học
- Mượn sách theo tài khoản học sinh (mã số học sinh)
- Trả sách, tự động tính phạt nếu trễ hạn
- Tự động đánh dấu phiếu mượn quá hạn
- Khu vực Quản lý (thủ thư) có đăng nhập riêng:
- Thêm sách mới, thêm thành viên
- Xem toàn bộ lịch sử mượn/trả

## Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP 8.2 (PDO, không dùng framework) |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, JavaScript thuần (Fetch API) |
| Triển khai | Docker, Render (Web Service) |
| Database hosting | SkySQL (MariaDB Cloud, miễn phí) |

## Cấu trúc dự án

## Cấu trúc dự án

```
library-php/
├── config.php # Kết nối database (đọc từ biến môi trường hoặc mặc định local)
├── schema.sql # Script tạo database và toàn bộ bảng
├── Dockerfile # Cấu hình build container để deploy
├── ca-cert.pem # Chứng chỉ SSL để kết nối SkySQL
├── api/
│ ├── books.php # GET: tìm sách · POST: thêm sách (yêu cầu đăng nhập)
│ ├── members.php # GET: danh sách thành viên · POST: thêm thành viên (yêu cầu đăng nhập)
│ ├── checkout.php # POST: mượn sách
│ ├── checkin.php # POST: trả sách (tự tính phạt nếu trễ)
│ ├── loans.php # GET: danh sách phiếu mượn (tự đánh dấu quá hạn)
│ ├── login.php # POST: đăng nhập quản lý (thủ thư)
│ ├── logout.php # POST: đăng xuất
│ └── session_check.php # GET: kiểm tra trạng thái đăng nhập
├── index.html
├── style.css
└── app.js


## Chạy trên máy local (XAMPP)

1. Đã cài [XAMPP](https://www.apachefriends.org) (gồm Apache + MySQL + phpMyAdmin).
2. Copy toàn bộ thư mục `library-php` vào `C:\xampp\htdocs\`.
3. Mở XAMPP Control Panel, bấm **Start** ở dòng Apache và MySQL.
4. Vào `http://localhost/phpmyadmin`, tab **SQL**, dán toàn bộ nội dung `schema.sql`, bấm **Go** để tạo database `library_db` và các bảng.
5. Mở `http://localhost/library-php/`.

Local mặc định dùng MySQL root không mật khẩu (theo cấu hình gốc của XAMPP) — không cần cấu hình thêm gì.

## Triển khai lên Production (Render + SkySQL)

Dự án đang chạy thật tại: **https://library-fu.onrender.com**

`config.php` ưu tiên đọc các biến môi trường sau (đặt trong phần *Environment* của Render):

| Biến | Ý nghĩa |
|---|---|
| `DB_HOST` | Địa chỉ máy chủ database |
| `DB_PORT` | Cổng kết nối |
| `DB_NAME` | Tên database |
| `DB_USER` | Tên đăng nhập database |
| `DB_PASS` | Mật khẩu database |

Nếu không có biến môi trường nào, hệ thống tự dùng cấu hình local (XAMPP).

Mỗi khi push code mới lên nhánh `main` trên GitHub, Render tự động build lại (dựa vào `Dockerfile`) và deploy — không cần thao tác thủ công.

## Tài khoản quản lý mặc định

Thông tin tài khoản quản lý (thủ thư) được lưu trong bảng `admins` của database, không công khai trong mã nguồn vì lý do bảo mật (repo này ở chế độ Public). Liên hệ trực tiếp thành viên phụ trách backend để lấy thông tin đăng nhập.

## Danh sách API

| Method | Endpoint | Mô tả | Cần đăng nhập |
|---|---|---|---|
| GET | `api/books.php?search=&subject=` | Tìm sách | Không |
| POST | `api/books.php` | Thêm sách mới | Có |
| GET | `api/members.php` | Danh sách thành viên | Không |
| POST | `api/members.php` | Thêm thành viên | Có |
| POST | `api/checkout.php` | Mượn sách `{book_id, member_id}` | Không |
| POST | `api/checkin.php` | Trả sách `{loan_id}` | Không |
| GET | `api/loans.php?status=` | Danh sách phiếu mượn | Không |
| POST | `api/login.php` | Đăng nhập `{username, password}` | — |
| POST | `api/logout.php` | Đăng xuất | — |
| GET | `api/session_check.php` | Kiểm tra trạng thái đăng nhập | — |

