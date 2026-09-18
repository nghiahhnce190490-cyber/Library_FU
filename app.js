// =========================================================
// Thư viện số — logic giao diện
// Gọi các API trong thư mục api/ (không đổi so với bản trước)
// =========================================================

// ---------- Trạng thái ----------
let currentStudent = null; // { id, name, student_code } hoặc null
let isAdmin = false;
let allLoans = [];         // cache phiếu mượn cho tab Quản lý
let loanFilter = "";       // "", "borrowed", "overdue", "returned"
let allMembers = [];       // cache danh sách sinh viên cho tab Quản lý
let memberFilter = "";     // "", "active", "locked", "overdue"
const booksById = {};      // cache sách đang hiển thị (dùng cho nút Sửa)

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

let toastTimer;
function showToast(msg, type = "info") {
  const t = document.getElementById("toast");
  t.textContent = msg;
  t.className = "show " + type;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (t.className = type), 3200);
}

async function sendJSON(method, url, payload) {
  const res = await fetch(url, {
    method,
    headers: { "Content-Type": "application/json" },
    body: payload === undefined ? undefined : JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  return { ok: res.ok, data };
}
const postJSON = (url, payload) => sendJSON("POST", url, payload);

async function getJSON(url) {
  const res = await fetch(url);
  return res.ok ? res.json() : [];
}

// Hiện vòng xoay trên nút trong lúc chờ
async function withLoading(btn, fn) {
  if (btn) { btn.classList.add("loading"); btn.disabled = true; }
  try { return await fn(); }
  finally { if (btn) { btn.classList.remove("loading"); btn.disabled = false; } }
}

// Màu bìa sách cố định theo tên sách
function hueOf(text) {
  let h = 0;
  for (const c of String(text)) h = (h * 31 + c.charCodeAt(0)) % 360;
  return h;
}
function initial(text) {
  const s = String(text || "?").trim();
  return esc(s.charAt(0).toUpperCase());
}

function formatDate(iso) {
  if (!iso) return "—";
  const [y, m, d] = iso.split("-");
  return `${d}/${m}/${y}`;
}
function daysUntil(iso) {
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const [y, m, d] = iso.split("-").map(Number);
  return Math.round((new Date(y, m - 1, d) - today) / 86400000);
}
function money(n) {
  return Number(n || 0).toLocaleString("vi-VN") + "đ";
}

function togglePassword(btn) {
  const input = btn.parentElement.querySelector("input");
  const show = input.type === "password";
  input.type = show ? "text" : "password";
  btn.textContent = show ? "Ẩn" : "Hiện";
}

function emptyState(emoji, title, text) {
  return `<div class="empty"><div class="emoji">${emoji}</div><h4>${title}</h4><p class="muted small">${text}</p></div>`;
}
function skeleton(n, cls = "skeleton") {
  return Array.from({ length: n }, () => `<div class="${cls}"></div>`).join("");
}

// ---------- Chuyển tab ----------
function openTab(tab) {
  document.querySelectorAll("#mainNav .tab-btn").forEach((b) => b.classList.toggle("active", b.dataset.tab === tab));
  document.querySelectorAll("#appScreen .tab-panel").forEach((p) => p.classList.toggle("active", p.id === "tab-" + tab));
  if (tab === "search") loadBooks();
  if (tab === "return") loadMyLoans();
  if (tab === "admin") loadAdmin();
  window.scrollTo({ top: 0, behavior: "smooth" });
}
document.querySelectorAll("#mainNav .tab-btn").forEach((btn) => {
  btn.addEventListener("click", () => openTab(btn.dataset.tab));
});

// ---------- Phiên đăng nhập ----------
async function refreshSession() {
  let data = {};
  try {
    const res = await fetch("api/session_check.php");
    data = await res.json();
  } catch (e) { /* mạng lỗi: coi như chưa đăng nhập */ }

  isAdmin = !!data.loggedIn;
  currentStudent = data.student || null;
  const role = isAdmin ? "admin" : currentStudent ? "student" : null;

  document.getElementById("loginScreen").style.display = role ? "none" : "grid";
  document.getElementById("appScreen").style.display = role ? "block" : "none";
  if (!role) return;

  const name = isAdmin ? data.username : currentStudent.name;
  document.getElementById("userName").textContent = name;
  document.getElementById("userRole").textContent = isAdmin ? "Thủ thư" : "Học sinh · " + currentStudent.student_code;
  document.getElementById("userAvatar").textContent = String(name || "?").trim().charAt(0).toUpperCase();

  document.getElementById("adminBorrowBox").style.display = isAdmin ? "flex" : "none";
  document.querySelectorAll("#mainNav .tab-btn").forEach((btn) => {
    btn.style.display = btn.dataset.role.split(" ").includes(role) ? "" : "none";
  });

  openTab("search");
}

async function logout() {
  await fetch("api/logout.php", { method: "POST" });
  isAdmin = false;
  currentStudent = null;
  allLoans = [];
  ["bookList", "loanList", "allLoanList", "adminStats", "mySummary", "bookCount"].forEach(
    (id) => (document.getElementById(id).innerHTML = "")
  );
  await refreshSession();
  showToast("Đã đăng xuất");
}

document.getElementById("loginForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const errorEl = document.getElementById("loginError");
  errorEl.textContent = "";
  await withLoading(document.getElementById("loginBtn"), async () => {
    const { ok, data } = await postJSON("api/auth_login.php", {
      username: form.username.value,
      password: form.password.value,
    });
    if (!ok) {
      errorEl.textContent = data.error || "Đăng nhập thất bại";
      return;
    }
    form.reset();
    await refreshSession();
    showToast(`Xin chào, ${data.name}!`, "success");
  });
});

// =========================================================
// TÌM & MƯỢN SÁCH
// =========================================================
document.getElementById("searchForm").addEventListener("submit", (e) => {
  e.preventDefault();
  loadBooks();
});

async function loadBooks() {
  const search = document.getElementById("searchInput").value.trim();
  const subject = document.getElementById("subjectInput").value.trim();
  const params = new URLSearchParams();
  if (search) params.set("search", search);
  if (subject) params.set("subject", subject);

  const list = document.getElementById("bookList");
  const count = document.getElementById("bookCount");
  list.innerHTML = skeleton(3);
  count.textContent = "";

  const books = await getJSON("api/books.php?" + params.toString());

  if (books.length === 0) {
    count.textContent = "";
    list.innerHTML = search || subject
      ? emptyState("🔍", "Không tìm thấy sách phù hợp", "Thử từ khóa khác hoặc bỏ bớt điều kiện lọc.")
      : emptyState("📚", "Thư viện chưa có sách", isAdmin ? "Vào tab Quản lý để thêm sách đầu tiên." : "Hãy quay lại sau nhé.");
    return;
  }

  count.textContent = `${books.length} đầu sách${search || subject ? " phù hợp" : ""}`;
  books.forEach((b) => (booksById[b.id] = b));
  list.innerHTML = books.map(renderBook).join("");
}

function renderBook(b) {
  const avail = Number(b.available_qty);
  const total = Number(b.total_qty) || 1;
  const pct = Math.max(0, Math.min(100, (avail / total) * 100));
  const barCls = avail === 0 ? "out" : avail / total <= 0.34 ? "low" : "";
  const canBorrow = currentStudent || isAdmin;

  return `
    <article class="book">
      <div class="cover" style="--h:${hueOf(b.title)}">${initial(b.title)}</div>
      <div class="book-body">
        <h3>${esc(b.title)}</h3>
        <div class="author">${b.author ? esc(b.author) : "Chưa rõ tác giả"}</div>
        <div class="chips">
          ${b.subject_code ? `<span class="tag subject">${esc(b.subject_code)}</span>` : ""}
          ${b.shelf_location ? `<span class="tag">📍 ${esc(b.shelf_location)}</span>` : ""}
          ${b.book_link ? `<a class="tag link" href="${esc(b.book_link)}" target="_blank" rel="noopener">Bản điện tử ↗</a>` : ""}
        </div>
        ${isAdmin
          ? `<div class="book-admin">
               <button class="btn btn-outline btn-xs" onclick="openEditBook(${Number(b.id)})">✏️ Sửa</button>
               <button class="btn btn-danger-ghost btn-xs" onclick="deleteBook(${Number(b.id)}, this)">🗑 Xóa</button>
             </div>`
          : ""}
        <div class="book-foot">
          <div class="stock">
            <div class="stock-bar ${barCls}"><span style="width:${pct}%"></span></div>
            <small>${avail > 0 ? `Còn ${avail}/${total} cuốn` : "Đã hết sách"}</small>
          </div>
          ${canBorrow
            ? `<button class="btn ${avail > 0 ? "btn-primary" : "btn-outline"} btn-sm" ${avail > 0 ? "" : "disabled"}
                 onclick="checkout(${Number(b.id)}, this)">${avail > 0 ? "Mượn sách" : "Hết sách"}</button>`
            : ""}
        </div>
      </div>
    </article>`;
}

async function checkout(bookId, btn) {
  const payload = { book_id: bookId };
  if (isAdmin) {
    const input = document.getElementById("borrowStudentCode");
    const code = input.value.trim();
    if (!code) {
      showToast("Nhập mã học sinh ở ô “Chế độ mượn hộ” trước", "error");
      input.focus();
      return;
    }
    payload.student_code = code;
  }
  await withLoading(btn, async () => {
    const { ok, data } = await postJSON("api/checkout.php", payload);
    if (!ok) {
      showToast(data.error || "Mượn sách thất bại", "error");
      return;
    }
    showToast(data.message, "success");
  });
  loadBooks();
}

// =========================================================
// PHIẾU MƯỢN
// =========================================================
function dueInfo(l) {
  if (l.status === "returned") return { cls: "", text: `Đã trả ${formatDate(l.return_date)}` };
  const d = daysUntil(l.due_date);
  if (d < 0) return { cls: "danger", text: `Quá hạn ${-d} ngày` };
  if (d === 0) return { cls: "warn", text: "Hạn trả hôm nay" };
  if (d <= 3) return { cls: "warn", text: `Còn ${d} ngày` };
  return { cls: "ok", text: `Còn ${d} ngày` };
}
function statusBadge(status) {
  if (status === "overdue") return `<span class="badge danger">Quá hạn</span>`;
  if (status === "returned") return `<span class="badge muted">Đã trả</span>`;
  return `<span class="badge ok">Đang mượn</span>`;
}

function renderLoan(l, asAdmin) {
  const due = dueInfo(l);
  const overdue = l.status !== "returned" && daysUntil(l.due_date) < 0;
  return `
    <div class="loan ${overdue ? "overdue" : ""}">
      <div class="cover sm" style="--h:${hueOf(l.book_title)}">${initial(l.book_title)}</div>
      <div class="loan-main">
        <div class="loan-title"><strong>${esc(l.book_title)}</strong> ${statusBadge(overdue ? "overdue" : l.status)}</div>
        <div class="loan-meta">
          ${asAdmin ? `<span>👤 <b>${esc(l.member_name)}</b> · ${esc(l.student_code)}</span>` : ""}
          <span>📍 ${esc(l.shelf_location) || "—"}</span>
          <span>Mượn ${formatDate(l.borrow_date)}</span>
          <span>Hạn ${formatDate(l.due_date)}</span>
        </div>
      </div>
      <div class="loan-side">
        <span class="due ${due.cls}">${due.text}</span>
        ${Number(l.fine) > 0 ? `<span class="fine">Phạt ${money(l.fine)}</span>` : ""}
        ${asAdmin && l.status !== "returned"
          ? `<button class="btn btn-dark btn-sm" onclick="checkin(${Number(l.id)}, this)">Xác nhận trả</button>`
          : ""}
      </div>
    </div>`;
}

function statTile(icon, color, value, label) {
  return `<div class="stat"><div class="stat-icon ${color}">${icon}</div>
    <div><div class="stat-value">${value}</div><div class="stat-label">${label}</div></div></div>`;
}

// ---------- Học sinh: sách của tôi ----------
async function loadMyLoans() {
  const list = document.getElementById("loanList");
  list.innerHTML = skeleton(2);
  const loans = await getJSON("api/loans.php?status=borrowed,overdue");

  const overdue = loans.filter((l) => daysUntil(l.due_date) < 0).length;
  const soon = loans.filter((l) => { const d = daysUntil(l.due_date); return d >= 0 && d <= 3; }).length;
  document.getElementById("mySummary").innerHTML =
    statTile("📖", "blue", loans.length, "Đang mượn") +
    statTile("⏳", "orange", soon, "Sắp đến hạn (≤ 3 ngày)") +
    statTile("⚠️", "red", overdue, "Quá hạn");

  list.innerHTML = loans.length
    ? loans.map((l) => renderLoan(l, false)).join("")
    : emptyState("🎉", "Bạn không mượn cuốn nào", "Vào tab “Tìm & mượn sách” để tìm sách bạn cần.");
}

// ---------- Thủ thư: tổng quan + phiếu mượn ----------
async function loadAdmin() {
  document.getElementById("allLoanList").innerHTML = skeleton(3);
  const [books, loans] = await Promise.all([getJSON("api/books.php"), getJSON("api/loans.php")]);
  allLoans = loans;

  const totalCopies = books.reduce((s, b) => s + Number(b.total_qty || 0), 0);
  const borrowing = loans.filter((l) => l.status !== "returned").length;
  const overdue = loans.filter((l) => l.status !== "returned" && daysUntil(l.due_date) < 0).length;
  const fines = loans.reduce((s, l) => s + Number(l.fine || 0), 0);

  document.getElementById("adminStats").innerHTML =
    statTile("📚", "blue", books.length, `Đầu sách · ${totalCopies} cuốn`) +
    statTile("📖", "green", borrowing, "Đang được mượn") +
    statTile("⚠️", "red", overdue, "Quá hạn") +
    statTile("💰", "orange", money(fines), "Tiền phạt đã ghi nhận");

  renderAdminLoans();
  loadMembers();
}

// =========================================================
// THỦ THƯ: SỬA / XÓA SÁCH
// =========================================================
function openEditBook(id) {
  const b = booksById[id];
  if (!b) return;
  const f = document.getElementById("editBookForm");
  f.id.value = b.id;
  f.title.value = b.title || "";
  f.author.value = b.author || "";
  f.subject_code.value = b.subject_code || "";
  f.shelf_location.value = b.shelf_location || "";
  f.total_qty.value = b.total_qty;
  f.book_link.value = b.book_link || "";
  const borrowed = Number(b.total_qty) - Number(b.available_qty);
  f.total_qty.min = Math.max(1, borrowed);
  document.getElementById("editBookHint").textContent = borrowed > 0
    ? `Đang có ${borrowed} cuốn được mượn, tổng số lượng không thể nhỏ hơn ${borrowed}.`
    : "";
  document.getElementById("editBookDialog").showModal();
}
function closeEditBook() {
  document.getElementById("editBookDialog").close();
}

document.getElementById("editBookForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target;
  await withLoading(f.querySelector("button[type=submit]"), async () => {
    const { ok, data } = await sendJSON("PUT", "api/books.php", {
      id: Number(f.id.value),
      title: f.title.value,
      author: f.author.value,
      subject_code: f.subject_code.value,
      shelf_location: f.shelf_location.value,
      total_qty: Number(f.total_qty.value),
      book_link: f.book_link.value,
    });
    if (!ok) {
      showToast(data.error || "Sửa sách thất bại", "error");
      return;
    }
    closeEditBook();
    showToast(data.message || "Đã cập nhật sách", "success");
    loadBooks();
  });
});

async function deleteBook(id, btn) {
  const b = booksById[id];
  const name = b ? b.title : "cuốn sách này";
  if (!confirm(`Xóa sách "${name}"?\n\nLịch sử mượn đã trả của sách này cũng sẽ bị xóa. Không thể hoàn tác.`)) return;
  await withLoading(btn, async () => {
    const { ok, data } = await sendJSON("DELETE", "api/books.php?id=" + Number(id));
    if (!ok) {
      showToast(data.error || "Xóa sách thất bại", "error");
      return;
    }
    delete booksById[id];
    showToast(data.message || "Đã xóa sách", "success");
  });
  loadBooks();
}

// =========================================================
// THỦ THƯ: DANH SÁCH SINH VIÊN + KHÓA / MỞ KHÓA
// =========================================================
async function loadMembers() {
  document.getElementById("memberList").innerHTML = skeleton(2);
  allMembers = await getJSON("api/members.php");
  renderMembers();
}

function renderMembers() {
  const q = document.getElementById("memberSearch").value.trim().toLowerCase();
  const list = allMembers.filter((m) => {
    if (memberFilter === "active" && m.status !== "active") return false;
    if (memberFilter === "locked" && m.status === "active") return false;
    if (memberFilter === "overdue" && Number(m.overdue) === 0) return false;
    if (!q) return true;
    return [m.name, m.student_code, m.class_name].some((v) => String(v || "").toLowerCase().includes(q));
  });

  const locked = allMembers.filter((m) => m.status !== "active").length;
  document.getElementById("memberCount").textContent =
    `· ${allMembers.length} sinh viên${locked ? `, ${locked} đang khóa` : ""}`;

  document.getElementById("memberList").innerHTML = list.length
    ? list.map(renderMember).join("")
    : emptyState("👥", "Không có sinh viên nào", q || memberFilter ? "Thử đổi bộ lọc hoặc từ khóa." : "Thêm sinh viên ở khung “Thêm học sinh” phía trên.");
}

function renderMember(m) {
  const isLocked = m.status !== "active";
  const borrowing = Number(m.borrowing);
  const overdue = Number(m.overdue);
  const fine = Number(m.total_fine);
  return `
    <div class="member ${isLocked ? "locked" : ""}">
      <div class="member-avatar" style="--h:${hueOf(m.name)}">${initial(m.name)}</div>
      <div>
        <div class="member-name">${esc(m.name)}
          ${isLocked ? `<span class="badge danger">Đã khóa</span>` : ""}
          ${Number(m.has_password) ? "" : `<span class="badge warn">Chưa có mật khẩu</span>`}
        </div>
        <div class="member-meta">
          <span>🎓 ${esc(m.student_code)}</span>
          ${m.class_name ? `<span>${esc(m.class_name)}</span>` : ""}
          ${m.contact ? `<span>${esc(m.contact)}</span>` : ""}
        </div>
      </div>
      <div class="member-stats">
        <span class="pill ${borrowing ? "blue" : ""}">Đang mượn ${borrowing}</span>
        ${overdue ? `<span class="pill red">Quá hạn ${overdue}</span>` : ""}
        ${fine ? `<span class="pill orange">Phạt ${money(fine)}</span>` : ""}
      </div>
      <div class="member-actions">
        ${isLocked
          ? `<button class="btn btn-success-ghost btn-xs" onclick="setMemberStatus(${Number(m.id)}, 'active', this)">🔓 Mở khóa</button>`
          : `<button class="btn btn-danger-ghost btn-xs" onclick="setMemberStatus(${Number(m.id)}, 'locked', this)">🔒 Khóa mượn</button>`}
      </div>
    </div>`;
}

async function setMemberStatus(id, status, btn) {
  const m = allMembers.find((x) => Number(x.id) === Number(id));
  if (status === "locked" && !confirm(`Khóa quyền mượn sách của ${m ? m.name : "sinh viên này"}?\n\nSinh viên vẫn đăng nhập và xem sách được, nhưng không mượn thêm được cho tới khi mở khóa.`)) return;
  await withLoading(btn, async () => {
    const { ok, data } = await sendJSON("PUT", "api/members.php", { id, status });
    if (!ok) {
      showToast(data.error || "Cập nhật thất bại", "error");
      return;
    }
    showToast(data.message, "success");
  });
  loadMembers();
}

document.querySelectorAll("#memberFilter .chip").forEach((chip) => {
  chip.addEventListener("click", () => {
    document.querySelectorAll("#memberFilter .chip").forEach((c) => c.classList.remove("active"));
    chip.classList.add("active");
    memberFilter = chip.dataset.filter;
    renderMembers();
  });
});
document.getElementById("memberSearch").addEventListener("input", renderMembers);

function renderAdminLoans() {
  const q = document.getElementById("loanSearch").value.trim().toLowerCase();
  const list = allLoans.filter((l) => {
    const overdue = l.status !== "returned" && daysUntil(l.due_date) < 0;
    const st = overdue ? "overdue" : l.status;
    if (loanFilter && st !== loanFilter) return false;
    if (!q) return true;
    return [l.member_name, l.student_code, l.book_title].some((v) => String(v || "").toLowerCase().includes(q));
  });
  document.getElementById("allLoanList").innerHTML = list.length
    ? list.map((l) => renderLoan(l, true)).join("")
    : emptyState("🧾", "Không có phiếu mượn nào", q || loanFilter ? "Thử đổi bộ lọc hoặc từ khóa." : "Phiếu mượn sẽ hiện ở đây khi có người mượn sách.");
}

document.querySelectorAll("#loanFilter .chip").forEach((chip) => {
  chip.addEventListener("click", () => {
    document.querySelectorAll("#loanFilter .chip").forEach((c) => c.classList.remove("active"));
    chip.classList.add("active");
    loanFilter = chip.dataset.filter;
    renderAdminLoans();
  });
});
document.getElementById("loanSearch").addEventListener("input", renderAdminLoans);

async function checkin(loanId, btn) {
  await withLoading(btn, async () => {
    const { ok, data } = await postJSON("api/checkin.php", { loan_id: loanId });
    if (!ok) {
      showToast(data.error || "Trả sách thất bại", "error");
      return;
    }
    showToast(data.message, data.fine > 0 ? "error" : "success");
  });
  loadAdmin();
}

// =========================================================
// FORM QUẢN LÝ
// =========================================================
function bindForm(id, url, build, successMsg, after) {
  document.getElementById(id).addEventListener("submit", async (e) => {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector("button[type=submit]");
    await withLoading(btn, async () => {
      const { ok, data } = await postJSON(url, build(form));
      if (!ok) {
        showToast(data.error || "Có lỗi xảy ra", "error");
        return;
      }
      showToast(typeof successMsg === "function" ? successMsg(data) : successMsg, "success");
      form.reset();
      if (after) after();
    });
  });
}

bindForm("addBookForm", "api/books.php", (f) => ({
  title: f.title.value,
  author: f.author.value,
  subject_code: f.subject_code.value,
  book_link: f.book_link.value,
  shelf_location: f.shelf_location.value,
  total_qty: Number(f.total_qty.value),
}), "Đã thêm sách mới", loadAdmin);

bindForm("addMemberForm", "api/members.php", (f) => ({
  student_code: f.student_code.value,
  name: f.name.value,
  class_name: f.class_name.value,
  contact: f.contact.value,
  password: f.password.value,
}), "Đã thêm học sinh", loadMembers);

bindForm("resetPasswordForm", "api/member_password.php", (f) => ({
  student_code: f.student_code.value,
  password: f.password.value,
}), (d) => d.message || "Đã đặt lại mật khẩu");

// ---------- Khởi động ----------
refreshSession();