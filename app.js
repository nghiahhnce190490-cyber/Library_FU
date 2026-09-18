// ---------- Trạng thái đăng nhập ----------
let currentStudent = null; // { id, name, student_code } hoặc null
let isAdmin = false;

// ---------- Tiện ích ----------
// Chống chèn mã HTML (XSS) khi hiển thị dữ liệu người dùng nhập
function esc(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function showToast(msg) {
  const t = document.getElementById("toast");
  t.textContent = msg;
  t.classList.add("show");
  setTimeout(() => t.classList.remove("show"), 3000);
}

async function postJSON(url, payload) {
  const res = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  return { ok: res.ok, data };
}

// ---------- Chuyển tab ----------
function openTab(tab) {
  document.querySelectorAll("nav .tab-btn").forEach((b) => b.classList.toggle("active", b.dataset.tab === tab));
  document.querySelectorAll("#appScreen .tab-panel").forEach((p) => p.classList.toggle("active", p.id === "tab-" + tab));
  if (tab === "search") loadBooks();
  if (tab === "return") loadMyLoans();
  if (tab === "admin") loadAllLoans();
}

document.querySelectorAll("nav .tab-btn").forEach((btn) => {
  btn.addEventListener("click", () => openTab(btn.dataset.tab));
});

// ---------- Session: quyết định hiện màn hình nào ----------
async function refreshSession() {
  const res = await fetch("api/session_check.php");
  const data = await res.json();

  isAdmin = !!data.loggedIn;
  currentStudent = data.student;
  const role = isAdmin ? "admin" : currentStudent ? "student" : null;

  // Chưa đăng nhập: chỉ hiện form đăng nhập
  document.getElementById("loginScreen").style.display = role ? "none" : "block";
  document.getElementById("appScreen").style.display = role ? "block" : "none";
  document.getElementById("navBar").style.display = role ? "flex" : "none";
  if (!role) return;

  document.getElementById("userName").textContent = isAdmin
    ? data.username + " (thủ thư)"
    : `${currentStudent.name} (${currentStudent.student_code})`;

  // Ô nhập mã học sinh để mượn hộ: chỉ thủ thư thấy
  document.getElementById("adminBorrowBox").style.display = isAdmin ? "flex" : "none";

  // Chỉ hiện các tab dành cho vai trò hiện tại
  document.querySelectorAll("nav .tab-btn").forEach((btn) => {
    btn.style.display = btn.dataset.role.split(" ").includes(role) ? "" : "none";
  });

  openTab("search");
}

async function logout() {
  await fetch("api/logout.php", { method: "POST" });
  isAdmin = false;
  currentStudent = null;
  document.getElementById("bookList").innerHTML = "";
  document.getElementById("loanList").innerHTML = "";
  document.getElementById("allLoanList").innerHTML = "";
  await refreshSession();
}

// ---------- Đăng nhập chung ----------
document.getElementById("loginForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const { ok, data } = await postJSON("api/auth_login.php", {
    username: form.username.value,
    password: form.password.value,
  });
  const errorEl = document.getElementById("loginError");
  if (!ok) {
    errorEl.textContent = data.error || "Đăng nhập thất bại";
    return;
  }
  errorEl.textContent = "";
  form.reset();
  refreshSession();
});

// ---------- Tìm sách & mượn ----------
async function loadBooks() {
  const search = document.getElementById("searchInput").value;
  const subject = document.getElementById("subjectInput").value;
  const params = new URLSearchParams();
  if (search) params.set("search", search);
  if (subject) params.set("subject", subject);

  const res = await fetch("api/books.php?" + params.toString());
  const books = res.ok ? await res.json() : [];
  const container = document.getElementById("bookList");

  if (books.length === 0) {
    container.innerHTML = `<p>Không tìm thấy sách phù hợp.</p>`;
    return;
  }

  container.innerHTML = books
    .map((b) => {
      const statusBadge =
        b.available_qty > 0
          ? `<span class="badge ok">Còn ${esc(b.available_qty)}/${esc(b.total_qty)}</span>`
          : `<span class="badge danger">Hết sách</span>`;
      // Học sinh mượn cho mình, thủ thư mượn hộ học sinh
      const borrowBtn = currentStudent || isAdmin
        ? `<button ${b.available_qty > 0 ? "" : "disabled"} onclick="checkout(${Number(b.id)})">Xác nhận mượn</button>`
        : "";
      return `
        <div class="card">
          <div class="info">
            <strong>${esc(b.title)}</strong> ${statusBadge}
            <div class="meta">
              Tác giả: ${esc(b.author) || "—"} · Mã môn: ${esc(b.subject_code) || "—"} · Vị trí: ${esc(b.shelf_location) || "—"}
              ${b.book_link ? ` · <a href="${esc(b.book_link)}" target="_blank" rel="noopener">Xem link sách</a>` : ""}
            </div>
          </div>
          ${borrowBtn}
        </div>
      `;
    })
    .join("");
}

async function checkout(bookId) {
  const payload = { book_id: bookId };
  if (isAdmin) {
    const code = document.getElementById("borrowStudentCode").value.trim();
    if (!code) {
      showToast("Nhập mã học sinh mượn sách ở ô phía trên trước");
      document.getElementById("borrowStudentCode").focus();
      return;
    }
    payload.student_code = code;
  }
  const { ok, data } = await postJSON("api/checkout.php", payload);
  if (!ok) {
    showToast(data.error || "Mượn sách thất bại");
    return;
  }
  showToast(data.message);
  loadBooks();
}

// ---------- Phiếu mượn ----------
function renderLoans(loans, withReturnButton) {
  if (loans.length === 0) return `<p>Không có phiếu mượn nào.</p>`;
  return loans
    .map((l) => {
      let badge = `<span class="badge ok">Đang mượn</span>`;
      if (l.status === "overdue") badge = `<span class="badge danger">Quá hạn</span>`;
      if (l.status === "returned") badge = `<span class="badge warn">Đã trả</span>`;
      return `
        <div class="card">
          <div class="info">
            <strong>${esc(l.book_title)}</strong> ${badge}
            <div class="meta">
              Người mượn: ${esc(l.member_name)} (${esc(l.student_code)}) · Vị trí: ${esc(l.shelf_location)}<br/>
              Ngày mượn: ${esc(l.borrow_date)} · Hạn trả: ${esc(l.due_date)}
              ${l.return_date ? ` · Ngày trả: ${esc(l.return_date)}` : ""}
              ${l.fine > 0 ? ` · Phạt: ${Number(l.fine).toLocaleString()} VND` : ""}
            </div>
          </div>
          ${withReturnButton && l.status !== "returned"
            ? `<button onclick="checkin(${Number(l.id)})">Xác nhận trả sách</button>`
            : ""}
        </div>
      `;
    })
    .join("");
}

// Học sinh: chỉ xem sách của mình, không có nút trả
async function loadMyLoans() {
  const res = await fetch("api/loans.php?status=borrowed,overdue");
  const loans = res.ok ? await res.json() : [];
  document.getElementById("loanList").innerHTML = renderLoans(loans, false);
}

// Thủ thư: xem toàn bộ, có nút xác nhận trả
async function loadAllLoans() {
  const res = await fetch("api/loans.php");
  const loans = res.ok ? await res.json() : [];
  document.getElementById("allLoanList").innerHTML = renderLoans(loans, true);
}

async function checkin(loanId) {
  const { ok, data } = await postJSON("api/checkin.php", { loan_id: loanId });
  if (!ok) {
    showToast(data.error || "Trả sách thất bại");
    return;
  }
  showToast(data.message);
  loadAllLoans();
}

// ---------- Thủ thư: thêm sách ----------
document.getElementById("addBookForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const { ok, data } = await postJSON("api/books.php", {
    title: form.title.value,
    author: form.author.value,
    subject_code: form.subject_code.value,
    book_link: form.book_link.value,
    shelf_location: form.shelf_location.value,
    total_qty: Number(form.total_qty.value),
  });
  if (!ok) {
    showToast(data.error || "Thêm sách thất bại");
    return;
  }
  showToast("Đã thêm sách mới");
  form.reset();
});

// ---------- Thủ thư: thêm thành viên ----------
document.getElementById("addMemberForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const { ok, data } = await postJSON("api/members.php", {
    student_code: form.student_code.value,
    name: form.name.value,
    class_name: form.class_name.value,
    contact: form.contact.value,
    password: form.password.value,
  });
  if (!ok) {
    showToast(data.error || "Thêm thành viên thất bại");
    return;
  }
  showToast("Đã thêm thành viên");
  form.reset();
});

// ---------- Thủ thư: đặt lại mật khẩu học sinh ----------
document.getElementById("resetPasswordForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const { ok, data } = await postJSON("api/member_password.php", {
    student_code: form.student_code.value,
    password: form.password.value,
  });
  showToast(ok ? data.message : data.error || "Đặt lại mật khẩu thất bại");
  if (ok) form.reset();
});

// ---------- Khởi động ----------
refreshSession();
