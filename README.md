# Ứng dụng quản lý thư viện — Bản PHP (XAMPP + MySQL)

Bản này viết lại bằng PHP thuần + MySQL, dùng chung được với XAMPP —
không kèm dữ liệu mẫu, bạn tự nhập sách và thành viên qua giao diện.

## Yêu cầu
- Đã cài XAMPP (gồm Apache + MySQL + phpMyAdmin): tải tại https://www.apachefriends.org

## Cách chạy

1. Copy toàn bộ thư mục `library-php` vào trong `C:\xampp\htdocs\`,
   có thể đổi tên thư mục thành `library-app` cho gọn.
2. Mở XAMPP Control Panel, bấm **Start** ở dòng Apache và dòng MySQL.
3. Mở trình duyệt vào `http://localhost/phpmyadmin`.
4. Vào tab **SQL**, dán toàn bộ nội dung file `schema.sql` vào rồi bấm **Go**
   để tạo database `library_db` và các bảng cần thiết (chưa có dữ liệu mẫu).
5. Mở trình duyệt vào `http://localhost/library-app/` (đổi tên cho đúng
   thư mục bạn đã đặt ở bước 1).

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

