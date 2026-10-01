<?php
// Chặn ngay ở server: chưa đăng nhập thủ thư thì không gửi giao diện này đi.
// Nếu auth_login.php lưu vai trò bằng key khác, sửa điều kiện bên dưới
// (và hàm require_admin() trong api/isbn_common.php) cho khớp.
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) {
    header('Location: ../');
    exit;
}
$adminName = htmlspecialchars($_SESSION['admin_username'] ?? 'Thủ thư', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>LibGo · Thủ thư</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="admin.css?v=1" />
</head>
<body>
  <header class="topbar">
    <div class="brand">LibGo <span>Thủ thư</span></div>
    <nav class="topnav">
      <a href="#" class="active">Nhập sách</a>
      <!-- Các mục khác (Mượn/trả, Danh sách sách, Sinh viên) chuyển từ trang cũ sang đây -->
    </nav>
    <div class="user">
      <span><?= $adminName ?></span>
      <button type="button" class="btn-ghost" id="logoutBtn">Đăng xuất</button>
    </div>
  </header>

  <main class="layout">
    <section class="intake" aria-labelledby="intakeTitle">
      <h1 id="intakeTitle">Nhập sách</h1>
      <p class="hint">Quét mã vạch sau bìa sách. Thông tin sẽ tự điền, bạn chỉ cần thêm mã môn, vị trí kệ và số lượng.</p>

      <!-- Ô quét: máy quét USB gõ mã rồi nhấn Enter -->
      <div class="scanbar" id="scanbar" data-state="idle">
        <label for="isbnInput" class="sr-only">Mã ISBN</label>
        <input id="isbnInput" inputmode="numeric" autocomplete="off" spellcheck="false"
               placeholder="Quét hoặc gõ mã ISBN" autofocus />
        <button type="button" id="lookupBtn">Tra cứu</button>
      </div>
      <div class="scan-actions">
        <button type="button" class="btn-link" id="cameraBtn">Quét bằng camera</button>
        <button type="button" class="btn-link" id="noIsbnBtn">Sách không có ISBN</button>
      </div>
      <div id="cameraBox" class="camera" hidden></div>

      <p class="status" id="status" role="status" aria-live="polite"></p>

      <!-- Sách đã có trong thư viện -->
      <div class="exists" id="existsBox" hidden>
        <img id="existsCover" alt="" hidden />
        <div>
          <strong id="existsTitle"></strong>
          <p id="existsMeta"></p>
        </div>
      </div>

      <form id="bookForm" class="book-form" hidden novalidate>
        <div class="cover-col">
          <div class="cover" id="coverBox"><span>Chưa có ảnh bìa</span></div>
        </div>

        <div class="fields">
          <div class="info-fields" id="infoFields">
            <label class="span2">Tên sách *
              <input name="title" required maxlength="255" />
            </label>
            <label class="span2">Tác giả
              <input name="author" maxlength="255" />
            </label>
            <label>Nhà xuất bản
              <input name="publisher" maxlength="255" />
            </label>
            <label>Năm xuất bản
              <input name="publish_year" inputmode="numeric" maxlength="4" />
            </label>
            <label class="span2">Link tài liệu điện tử
              <input name="book_link" type="url" maxlength="500" placeholder="https://..." />
            </label>
          </div>

          <div class="place-fields">
            <label>Mã môn
              <input name="subject_code" list="subjectList" maxlength="50" placeholder="vd: MAE101" />
            </label>
            <label>Vị trí kệ
              <input name="shelf_location" list="shelfList" maxlength="100" placeholder="vd: Kệ A1 - Tầng 1" />
            </label>
            <label>Số lượng *
              <input name="qty" type="number" min="1" max="500" value="1" required />
            </label>
          </div>

          <div class="form-foot">
            <label class="check">
              <input type="checkbox" id="keepPlace" checked />
              Giữ mã môn và kệ cho cuốn tiếp theo
            </label>
            <div class="buttons">
              <button type="button" class="btn-ghost" id="cancelBtn">Hủy</button>
              <button type="submit" id="saveBtn">Lưu sách</button>
            </div>
          </div>
        </div>
      </form>

      <datalist id="subjectList"></datalist>
      <datalist id="shelfList"></datalist>
    </section>

    <aside class="session" aria-labelledby="sessionTitle">
      <h2 id="sessionTitle">Vừa nhập</h2>
      <p class="session-count" id="sessionCount">Chưa nhập cuốn nào trong lần này.</p>
      <ol id="sessionList" class="session-list"></ol>
    </aside>
  </main>

  <div id="toast" role="alert"></div>

  <script src="admin.js?v=1"></script>
</body>
</html>
