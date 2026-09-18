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
document.querySelectorAll(".tab-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    document.querySelectorAll(".tab-btn").forEach((b) => b.classList.remove("active"));
    document.querySelectorAll(".tab-panel").forEach((p) => p.classList.remove("active"));
    btn.classList.add("active");
    document.getElementById("tab-" + btn.dataset.tab).classList.add("active");
    if (btn.dataset.tab === "return") loadMyLoans();
    if (btn.dataset.tab === "admin") refreshSession();
  });
});

// ---------- Session ----------
async function refreshSession() {
  const res = await fetch("api/session_check.php");
  const data = await res.json();

  isAdmin = !!data.loggedIn;
  currentStudent = data.student;

  // Khu học sinh
  document.getElementById("studentLogin").style.display = currentStudent ? "none" : "block";
  document.getElementById("studentInfo").style.display = currentStudent ? "flex" : "none";
  if (currentStudent) {
    document.getElementById("studentName").textContent = currentStudent.name;
    document.getElementById("studentCode").textContent = currentStudent.student_code;
  }

  // Khu thủ thư
  document.getElementById("adminLogin").style.display = isAdmin ? "none" : "block";
  document.getElementById("adminPanel").style.display = isAdmin ? "block" : "none";
  if (isAdmin) {
    document.getElementById("adminUsername").textContent = data.username;
    loadAllLoans();
  }
}

async function logout() {
  await fetch("api/logout.php", { method: "POST" });
  await refreshSession();
  loadBooks();
  showToast("Đã đăng xuất");
}

// ---------- Đăng nhập học sinh ----------
document.getElementById("studentLoginForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const { ok, data } = await postJSON("api/student_login.php", {
    student_code: form.student_code.value,
    password: form.password.value,
  });
  const errorEl = document.getElementById("studentLoginError");
  if (!ok) {
    errorEl.textContent = data.error || "Đăng nhập thất bại";
    return;
  }
  errorEl.textContent = "";
  form.reset();
  await refreshSession();
  loadBooks();
});

// ---------- Đăng nhập thủ thư ----------
document.getElementById("adminLoginForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const { ok, data } = await postJSON("api/login.php", {
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
  const books = await res.json();
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
      const canBorrow = currentStudent && b.available_qty > 0;
      return `
        <div class="card">
          <div class="info">
            <strong>${esc(b.title)}</strong> ${statusBadge}
            <div class="meta">
              Tác giả: ${esc(b.author) || "—"} · Mã môn: ${esc(b.subject_code) || "—"} · Vị trí: ${esc(b.shelf_location) || "—"}
              ${b.book_link ? ` · <a href="${esc(b.book_link)}" target="_blank" rel="noopener">Xem link sách</a>` : ""}
            </div>
          </div>
          <button ${canBorrow ? "" : "disabled"} onclick="checkout(${Number(b.id)})"
            title="${currentStudent ? "" : "Đăng nhập để mượn sách"}">Xác nhận mượn</button>
        </div>
      `;
    })
    .join("");
}

async function checkout(bookId) {
  if (!currentStudent) {
    showToast("Vui lòng đăng nhập bằng mã học sinh trước khi mượn");
    return;
  }
  const { ok, data } = await postJSON("api/checkout.php", { book_id: bookId });
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
  const container = document.getElementById("loanList");
  if (!currentStudent) {
    container.innerHTML = `<p>Vui lòng đăng nhập ở tab "Tìm & mượn sách" để xem sách bạn đang mượn.</p>`;
    return;
  }
  const res = await fetch("api/loans.php?status=borrowed,overdue");
  const loans = res.ok ? await res.json() : [];
  container.innerHTML = renderLoans(loans, false);
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
  loadBooks();
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
  loadBooks();
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
refreshSession().then(loadBooks);