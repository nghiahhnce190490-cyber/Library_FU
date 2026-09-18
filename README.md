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
├── config.php        # Thông tin kết nối MySQL (user root, không mật khẩu — mặc định XAMPP)
├── schema.sql         # Câu lệnh tạo database + bảng, không có dữ liệu mẫu
├── api/
│   ├── books.php       # GET: tìm sách theo mã môn/tên/tác giả · POST: thêm sách
│   ├── members.php      # GET: danh sách thành viên · POST: thêm thành viên
│   ├── checkout.php      # POST: mượn sách
│   ├── checkin.php        # POST: trả sách (tự tính phạt nếu trễ)
│   └── loans.php           # GET: danh sách phiếu mượn (tự đánh dấu quá hạn)
├── index.html
├── style.css
└── app.js
```

## Vì sao chưa có dữ liệu mẫu

Theo yêu cầu, bản này để trống — bạn cần tự thêm sách và thành viên qua
giao diện web (tab "Quản lý") trước khi thử mượn/trả, hoặc nhập trực tiếp
qua phpMyAdmin nếu muốn thêm nhanh nhiều dòng cùng lúc.

## Những phần cần làm thêm để dùng thật

1. **Đăng nhập bằng tài khoản trường**: hiện đang chọn thành viên qua dropdown
   để test nhanh. Cần thay bằng màn hình đăng nhập thật.
2. **Đồng bộ dữ liệu học sinh từ API trường**: hiện `api/members.php` cho thêm
   thủ công. Cần viết thêm một script PHP chạy định kỳ (cron job trên
   server thật) gọi API của trường và cập nhật lại bảng `members`.
3. **Phân quyền**: tách riêng khu vực "Quản lý" cho thủ thư, không cho học
   sinh truy cập được.
4. **Giữ chỗ tạm thời khi xác nhận mượn**: nên thêm bước giữ chỗ 15–30 phút
   trước khi trừ kho hẳn, tránh 2 người cùng đặt 1 cuốn cuối.
5. **Triển khai thật**: đưa lên hosting hỗ trợ PHP + MySQL (ví dụ Hostinger,
   000webhost cho bản miễn phí, hoặc VPS riêng).

## Danh sách API

| Method | Endpoint                          | Mô tả                              |
|--------|------------------------------------|-------------------------------------|
| GET    | api/books.php?search=&subject=      | Tìm sách                           |
| POST   | api/books.php                        | Thêm sách mới                      |
| GET    | api/members.php                       | Danh sách thành viên              |
| POST   | api/members.php                        | Thêm thành viên                   |
| POST   | api/checkout.php                        | Mượn sách `{book_id, member_id}`  |
| POST   | api/checkin.php                          | Trả sách `{loan_id}`              |
| GET    | api/loans.php?status=                     | Danh sách phiếu mượn             |
