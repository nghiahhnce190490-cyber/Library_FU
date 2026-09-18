# LibGo — Ứng dụng quản lý thư viện (PHP + MySQL)

Ứng dụng web hỗ trợ sinh viên tìm và mượn sách, theo dõi hạn trả; hỗ trợ thủ thư
quản lý sách, sinh viên và phiếu mượn.

🌐 **Bản chạy thật:** https://thuvienfpt.id.vn  (dự phòng: https://library-fu.onrender.com)

---

## Chức năng

| Vai trò | Chức năng |
|---|---|
| **Chưa đăng nhập** | Chỉ thấy màn hình đăng nhập |
| **Sinh viên** | Tìm sách theo tên / tác giả / mã môn · xem vị trí kệ, số lượng còn, link tài liệu điện tử · mượn sách · xem "Sách của tôi" (số ngày còn lại, cảnh báo sắp đến hạn / quá hạn) |
| **Thủ thư** | Tất cả chức năng tìm sách · **mượn hộ** sinh viên tại quầy · thêm / **sửa / xóa sách** · thêm sinh viên · **danh sách sinh viên** (đang mượn, quá hạn, tiền phạt) · **khóa / mở khóa quyền mượn** · đặt lại mật khẩu · xem thống kê · lọc / tìm phiếu mượn · **xác nhận trả sách** |

**Quy định nghiệp vụ**

- Hạn mượn: **90 ngày** (đổi ở `LOAN_DAYS` trong `api/checkout.php`)
- Phí trễ hạn: **5.000đ / ngày** (đổi ở `FINE_PER_DAY` trong `api/checkin.php`)
- Chỉ thủ thư được xác nhận trả sách (nhận sách tận tay rồi mới bấm)
- Phiếu quá hạn được tự động đánh dấu khi có người xem danh sách phiếu mượn
- Sinh viên bị khóa vẫn đăng nhập và xem được, nhưng không mượn thêm được
- Không xóa được sách khi còn người đang mượn; xóa sách thì lịch sử mượn đã trả của sách đó cũng bị xóa
- Không giảm được tổng số lượng sách xuống dưới số cuốn đang được mượn

---

## Công nghệ

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP 8.2 thuần, PDO, các API trả JSON trong thư mục `api/` |
| Cơ sở dữ liệu | MariaDB / MySQL — bản thật chạy trên SkySQL Serverless (kết nối SSL) |
| Giao diện | HTML, CSS, JavaScript thuần, responsive, font Be Vietnam Pro |
| Đóng gói | Docker (`php:8.2-apache`) |
| Hosting | Render — tự deploy lại mỗi khi có code mới trên nhánh `main` |
| Tên miền | `thuvienfpt.id.vn` (PA Vietnam) |
| Chạy ở máy | XAMPP (Apache + MySQL + phpMyAdmin) |

---

## Cấu trúc dự án

```
library-php/
├── index.html            # Toàn bộ giao diện (đăng nhập, tìm sách, sách của tôi, quản lý)
├── style.css             # Giao diện; màu sắc gom ở đầu file (phần :root)
├── app.js                # Logic phía trình duyệt, gọi các API trong api/
├── config.php            # Kết nối CSDL (đọc biến môi trường), múi giờ Việt Nam, session
├── schema.sql            # Tạo database + các bảng: books, members, loans, admins, login_attempts
├── Dockerfile            # Đóng gói để chạy trên Render
├── .dockerignore         # Loại file nội bộ (.git, README, schema.sql) khỏi bản deploy
├── ca-cert.pem           # Chứng chỉ SSL của SkySQL (chỉ cần khi kết nối SkySQL)
└── api/
    ├── auth_login.php        # Đăng nhập chung (thủ thư hoặc sinh viên)
    ├── session_check.php     # Kiểm tra đang đăng nhập với vai trò gì
    ├── logout.php            # Đăng xuất
    ├── books.php             # GET: tìm · POST: thêm · PUT: sửa · DELETE: xóa sách
    ├── members.php           # GET: danh sách · POST: thêm · PUT: khóa / mở khóa sinh viên
    ├── member_password.php   # Thủ thư đặt lại mật khẩu sinh viên
    ├── checkout.php          # Mượn sách (sinh viên tự mượn / thủ thư mượn hộ)
    ├── checkin.php           # Trả sách, tự tính phí trễ hạn
    └── loans.php             # Danh sách phiếu mượn
```

---

## Chạy ở máy bằng XAMPP

1. Copy thư mục `library-php` vào `C:\xampp\htdocs\`.
2. Mở XAMPP Control Panel, bấm **Start** ở dòng **Apache** và **MySQL**.
3. Vào `http://localhost/phpmyadmin` → tab **SQL** → dán toàn bộ `schema.sql` → **Go**.
4. **Tạo tài khoản thủ thư.** Mở PowerShell, tạo mã băm mật khẩu:
   ```powershell
   C:\xampp\php\php.exe -r "echo password_hash('MatKhauCuaBan', PASSWORD_DEFAULT);"
   ```
   Rồi chạy trong phpMyAdmin (dán chuỗi `$2y$...` vừa tạo):
   ```sql
   USE library_db;
   INSERT INTO admins (username, password_hash) VALUES ('admins', '$2y$10$...');
   ```
5. Mở `http://localhost/library-php/`, đăng nhập bằng tài khoản thủ thư,
   vào tab **Quản lý** để thêm sách và sinh viên.

Khi không có biến môi trường, `config.php` tự dùng MySQL của XAMPP:
`localhost:3306`, user `root`, không mật khẩu, database `library_db`, và tự bỏ qua SSL
(SSL chỉ bật khi kết nối SkySQL).

---

## Triển khai (Render + SkySQL)

1. Code đẩy lên GitHub → Render tự build bằng `Dockerfile` và deploy lại (3–5 phút).
2. Trên Render, mục **Environment**, đặt các biến:

   | Biến | Ý nghĩa |
   |---|---|
   | `DB_HOST` | Host SkySQL |
   | `DB_PORT` | Cổng SkySQL |
   | `DB_NAME` | `library_db` |
   | `DB_USER` | User SkySQL |
   | `DB_PASS` | Mật khẩu SkySQL |

3. Tên miền: bản ghi DNS tại PA Vietnam — `@` loại A → `216.24.57.1`,
   `www` loại CNAME → `library-fu.onrender.com`.

> ⚠️ **Không ghi mật khẩu thật vào bất kỳ file nào trong repo.** Mật khẩu chỉ đặt trong
> biến môi trường trên Render.

> ℹ️ Gói Free của Render tạm dừng sau 15 phút không có truy cập; lần mở đầu tiên sau đó
> mất khoảng 30–60 giây.

**Kết nối SkySQL bằng dòng lệnh** (để chạy SQL trên CSDL thật):

```powershell
C:\xampp\mysql\bin\mysql.exe --host <DB_HOST> --port <DB_PORT> --user <DB_USER> -p --ssl-ca=C:\xampp\htdocs\library-php\ca-cert.pem --ssl-verify-server-cert
```

---

## Danh sách API

Tất cả API nhận và trả JSON. Quyền được kiểm tra ở máy chủ trên từng API.

| Method | Endpoint | Quyền | Mô tả |
|---|---|---|---|
| POST | `api/auth_login.php` | Ai cũng gọi được | Đăng nhập `{username, password}` → `{role: "admin" \| "student", name}` |
| GET | `api/session_check.php` | Ai cũng gọi được | Trạng thái đăng nhập hiện tại |
| POST | `api/logout.php` | Đã đăng nhập | Đăng xuất |
| GET | `api/books.php?search=&subject=` | Sinh viên, thủ thư | Tìm sách theo tên/tác giả và mã môn |
| POST | `api/books.php` | Thủ thư | Thêm sách |
| PUT | `api/books.php` | Thủ thư | Sửa sách `{id, title, author, subject_code, book_link, shelf_location, total_qty}` |
| DELETE | `api/books.php?id=` | Thủ thư | Xóa sách (không được khi còn người mượn) |
| GET | `api/members.php` | Thủ thư | Danh sách sinh viên kèm số đang mượn / quá hạn / tiền phạt (không trả mật khẩu) |
| PUT | `api/members.php` | Thủ thư | Khóa / mở khóa quyền mượn `{id, status: "active" \| "locked"}` |
| POST | `api/members.php` | Thủ thư | Thêm sinh viên `{student_code, name, class_name, contact, password}` |
| POST | `api/member_password.php` | Thủ thư | Đặt lại mật khẩu `{student_code, password}` |
| POST | `api/checkout.php` | Sinh viên, thủ thư | Mượn sách. Sinh viên: `{book_id}`. Thủ thư mượn hộ: `{book_id, student_code}` |
| POST | `api/checkin.php` | Thủ thư | Xác nhận trả `{loan_id}`, trả về tiền phạt |
| GET | `api/loans.php?status=borrowed,overdue` | Sinh viên (chỉ của mình), thủ thư (tất cả) | Danh sách phiếu mượn |

---

## Bảo mật đã áp dụng

- Mật khẩu sinh viên và thủ thư mã hóa bằng **bcrypt**; đăng nhập bằng session phía máy chủ.
- Cookie phiên bật **HttpOnly, Secure, SameSite=Lax**.
- Sai mật khẩu **5 lần trong 15 phút** thì tài khoản đó bị tạm khóa 15 phút (bảng `login_attempts`).
- **Phân quyền ở máy chủ**, không chỉ ẩn trên giao diện.
- Truy vấn **tham số hóa** (prepared statement) → chống SQL injection.
- Dữ liệu hiển thị được **mã hóa ký tự** → chống chèn mã (XSS).
- Mượn / trả chạy trong **transaction có khóa dòng** → 2 người không thể cùng mượn cuốn cuối, 1 phiếu không bị trả 2 lần.
- Thông tin CSDL lấy từ **biến môi trường**; `.dockerignore` không đưa file nội bộ lên web.

---

## Dành cho bạn làm giao diện

- Chỉ cần sửa **`index.html`**, **`style.css`**, và `app.js` nếu cần.
- **Giữ nguyên các `id`** mà `app.js` đang dùng (ví dụ `loginForm`, `bookList`, `loanList`,
  `allLoanList`, `adminBorrowBox`, `borrowStudentCode`, `addBookForm`, `addMemberForm`,
  `resetPasswordForm`, `editBookDialog`, `editBookForm`, `memberList`, `memberSearch`...). Đổi `id` thì chức năng sẽ hỏng.
- Màu sắc, bo góc, đổ bóng gom ở phần `:root` đầu `style.css` — đổi một chỗ là cả trang đổi theo.
- Luôn **lấy bản mới nhất trên GitHub trước khi sửa**, và upload `index.html`, `style.css`,
  `app.js` **cùng lúc** để tránh lệch phiên bản.
- Sau khi deploy, bấm **Ctrl+F5** để trình duyệt tải bản mới. Khi sửa CSS/JS, tăng số
  phiên bản trong `index.html` (hiện là `?v=3`; lần sửa sau đổi thành `?v=4`).

---

## Sao lưu database

Dùng file `backup-libgo.bat` (để **ngoài** thư mục dự án, không đưa lên GitHub). Chạy mỗi tuần một lần;
bản sao lưu lưu tại `Documents\LibGo-backup`. Bản sao lưu chứa dữ liệu cá nhân của sinh viên,
không chia sẻ công khai.

---

## Việc cần làm tiếp

- [ ] Đặt trước sách
- [ ] Gia hạn theo điều kiện
- [ ] Trang chi tiết sách riêng
- [ ] Mã QR cho từng sách
- [ ] Đăng nhập bằng tài khoản Google của trường (OAuth 2.0)
- [ ] Đồng bộ sinh viên và danh mục sách từ API của trường (chờ nhà trường cung cấp API)
- [ ] Kiểm thử với sinh viên thật và thu thập phản hồi