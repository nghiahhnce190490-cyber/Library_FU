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

// Máy chủ trả 401 trong khi giao diện vẫn nghĩ đang đăng nhập -> phiên đã hết hạn.
// Kiểm tra lại phiên; nếu đúng là đã mất thì đưa về màn hình đăng nhập.
let checkingSession = false;
async function handleUnauthorized(url) {
  if (url.includes("auth_login.php") || checkingSession || (!isAdmin && !currentStudent)) return;
  checkingSession = true;
  try {
    const res = await fetch("api/session_check.php");
    const s = await res.json();
    if (!s.loggedIn && !s.student) {
      isAdmin = false;
      currentStudent = null;
      await refreshSession();
      document.getElementById("loginError").textContent = "Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.";
    }
  } catch (e) { /* bỏ qua lỗi mạng */ }
  checkingSession = false;
}

async function sendJSON(method, url, payload) {
  const res = await fetch(url, {
    method,
    headers: { "Content-Type": "application/json" },
    body: payload === undefined ? undefined : JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (res.status === 401) handleUnauthorized(url);
  return { ok: res.ok, data };
}
const postJSON = (url, payload) => sendJSON("POST", url, payload);

async function getJSON(url) {
  const res = await fetch(url);
  if (res.status === 401) handleUnauthorized(url);
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

// Nút đọc online trên thẻ sách, ghi rõ đọc được bao nhiêu
const ACCESS_LABEL = { full: "📖 Đọc toàn bộ", partial: "📖 Đọc thử", none: "ℹ️ Thông tin sách" };
// Link đọc cũ dạng play.google.com (bắt đăng nhập) -> đổi sang trình đọc của books.google.com
function readerUrl(url) {
  const m = /^https:\/\/play\.google\.com\/books\/reader\?id=([^&]+)/.exec(url || "");
  return m ? `https://books.google.com/books?id=${m[1]}&printsec=frontcover` : url;
}
function readButton(b) {
  const link = safeUrl(readerUrl(b.book_link));
  if (!link) return "";
  const label = ACCESS_LABEL[b.read_access] || "📖 Đọc online";
  return `<a class="tag read ${esc(b.read_access || "unknown")}" href="${esc(link)}" target="_blank" rel="noopener">${label} ↗</a>`;
}
const ACCESS_RANK = { full: 3, partial: 2, none: 1 };

// Chỉ dùng link http(s) — chặn link độc hại kiểu "javascript:..."
function safeUrl(url) {
  const u = String(url || "").trim();
  return /^https?:\/\//i.test(u) ? u : "";
}

// Bìa sách: có link ảnh thì hiện ảnh, không có (hoặc ảnh lỗi) thì hiện bìa màu tự tạo
function coverHtml(title, url, extraCls = "") {
  const src = safeUrl(url);
  return `<div class="cover ${extraCls} ${src ? "has-img" : ""}" style="--h:${hueOf(title)}">${initial(title)}${
    src
      ? `<img src="${esc(src)}" alt="" loading="lazy" referrerpolicy="no-referrer"
             onerror="this.parentNode.classList.remove('has-img'); this.remove();">`
      : ""
  }</div>`;
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
  document.getElementById("loginError").textContent = "";
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

// Nhóm ngành theo tiền tố mã môn (VD: KOR105 -> Tiếng Hàn)
const CATEGORY_PREFIX = {
  eng: ["ENG", "ENW"],
  jpn: ["JPN", "JPD"],
  kor: ["KOR"],
  biz: ["BUS", "MGT", "ENT", "ETH", "LAW"],
  mkt: ["MKT"],
  fin: ["FIN", "BNK", "ACC", "ECO"],
};
function categoryOf(b) {
  const prefix = String(b.subject_code || "").toUpperCase().replace(/[^A-Z].*$/, "");
  for (const [cat, list] of Object.entries(CATEGORY_PREFIX)) if (list.includes(prefix)) return cat;
  return "it";
}

const PAGE_SIZE = 12;       // mỗi lần hiện 12 cuốn, bấm "Xem thêm" để hiện tiếp
let allBooks = [];
let bookCategory = "";
let booksShown = 0;

async function loadBooks() {
  const search = document.getElementById("searchInput").value.trim();
  const subject = document.getElementById("subjectInput").value.trim();
  const params = new URLSearchParams();
  if (search) params.set("search", search);
  if (subject) params.set("subject", subject);

  const list = document.getElementById("bookList");
  document.getElementById("bookCount").textContent = "";
  document.getElementById("loadMoreWrap").hidden = true;
  list.innerHTML = skeleton(3);

  allBooks = await getJSON("api/books.php?" + params.toString());
  allBooks.forEach((b) => (booksById[b.id] = b));
  renderBookList();
}

function filteredBooks() {
  if (bookCategory === "read") return allBooks.filter((b) => safeUrl(b.book_link) && ["full", "partial"].includes(b.read_access));
  return bookCategory ? allBooks.filter((b) => categoryOf(b) === bookCategory) : allBooks;
}

function renderBookList() {
  const search = document.getElementById("searchInput").value.trim();
  const subject = document.getElementById("subjectInput").value.trim();
  const list = document.getElementById("bookList");
  const count = document.getElementById("bookCount");
  const books = filteredBooks();

  booksShown = Math.min(PAGE_SIZE, books.length);
  if (books.length === 0) {
    count.textContent = "";
    document.getElementById("loadMoreWrap").hidden = true;
    list.innerHTML = search || subject || bookCategory
      ? emptyState("🔍", "Không tìm thấy sách phù hợp", "Thử từ khóa khác hoặc bỏ bớt điều kiện lọc.")
      : emptyState("📚", "Thư viện chưa có sách", isAdmin ? "Vào tab Quản lý để thêm sách đầu tiên." : "Hãy quay lại sau nhé.");
    return;
  }

  count.textContent = `${books.length} đầu sách${search || subject || bookCategory ? " phù hợp" : ""}`;
  list.innerHTML = books.slice(0, booksShown).map(renderBook).join("");
  updateLoadMore(books.length);
}

function updateLoadMore(total) {
  const left = total - booksShown;
  document.getElementById("loadMoreWrap").hidden = left <= 0;
  document.getElementById("loadMoreBtn").textContent = `Xem thêm (${left} cuốn)`;
}

document.getElementById("loadMoreBtn").addEventListener("click", () => {
  const books = filteredBooks();
  const next = books.slice(booksShown, booksShown + PAGE_SIZE);
  document.getElementById("bookList").insertAdjacentHTML("beforeend", next.map(renderBook).join(""));
  booksShown += next.length;
  updateLoadMore(books.length);
});

document.querySelectorAll("#catChips .chip").forEach((chip) => {
  chip.addEventListener("click", () => {
    document.querySelectorAll("#catChips .chip").forEach((c) => c.classList.remove("active"));
    chip.classList.add("active");
    bookCategory = chip.dataset.cat;
    renderBookList();
  });
});

function renderBook(b) {
  const avail = Number(b.available_qty);
  const total = Number(b.total_qty) || 1;
  const pct = Math.max(0, Math.min(100, (avail / total) * 100));
  const barCls = avail === 0 ? "out" : avail / total <= 0.34 ? "low" : "";
  const canBorrow = currentStudent || isAdmin;

  return `
    <article class="book">
      ${coverHtml(b.title, b.cover_url)}
      <div class="book-body">
        <h3>${esc(b.title)}</h3>
        <div class="author">${b.author ? esc(b.author) : "Chưa rõ tác giả"}</div>
        <div class="chips">
          ${b.subject_code ? `<span class="tag subject">${esc(b.subject_code)}</span>` : ""}
          ${b.shelf_location ? `<span class="tag">📍 ${esc(b.shelf_location)}</span>` : ""}
          ${readButton(b)}
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
      ${coverHtml(l.book_title, booksById[l.book_id]?.cover_url, "sm")}
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
  f.read_access.value = b.read_access || "";
  f.cover_url.value = b.cover_url || "";
  updateCoverPreview(f);
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
      read_access: f.read_access.value,
      cover_url: f.cover_url.value,
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
        <button class="btn btn-outline btn-xs" onclick="openEditMember(${Number(m.id)})">✏️ Sửa</button>
        <button class="btn btn-danger-ghost btn-xs" onclick="deleteMember(${Number(m.id)}, this)">🗑 Xóa</button>
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

function openEditMember(id) {
  const m = allMembers.find((x) => Number(x.id) === Number(id));
  if (!m) return;
  const f = document.getElementById("editMemberForm");
  f.id.value = m.id;
  f.student_code.value = m.student_code || "";
  f.name.value = m.name || "";
  f.class_name.value = m.class_name || "";
  f.contact.value = m.contact || "";
  document.getElementById("editMemberDialog").showModal();
}
function closeEditMember() {
  document.getElementById("editMemberDialog").close();
}

document.getElementById("editMemberForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target;
  await withLoading(f.querySelector("button[type=submit]"), async () => {
    const { ok, data } = await sendJSON("PUT", "api/members.php", {
      id: Number(f.id.value),
      student_code: f.student_code.value,
      name: f.name.value,
      class_name: f.class_name.value,
      contact: f.contact.value,
    });
    if (!ok) {
      showToast(data.error || "Sửa thông tin thất bại", "error");
      return;
    }
    closeEditMember();
    showToast(data.message || "Đã cập nhật", "success");
    loadMembers();
    renderAdminLoansFresh();
  });
});

async function deleteMember(id, btn) {
  const m = allMembers.find((x) => Number(x.id) === Number(id));
  const name = m ? `${m.name} (${m.student_code})` : "sinh viên này";
  if (!confirm(`Xóa sinh viên ${name}?\n\nLịch sử mượn đã trả của sinh viên này cũng sẽ bị xóa. Không thể hoàn tác.`)) return;
  await withLoading(btn, async () => {
    const { ok, data } = await sendJSON("DELETE", "api/members.php?id=" + Number(id));
    if (!ok) {
      showToast(data.error || "Xóa thất bại", "error");
      return;
    }
    showToast(data.message || "Đã xóa", "success");
  });
  loadAdmin();
}

// Tải lại phiếu mượn (tên/mã sinh viên trong phiếu có thể vừa đổi)
async function renderAdminLoansFresh() {
  allLoans = await getJSON("api/loans.php");
  renderAdminLoans();
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
  read_access: f.read_access.value,
  cover_url: f.cover_url.value,
  shelf_location: f.shelf_location.value,
  total_qty: Number(f.total_qty.value),
}), "Đã thêm sách mới", () => {
  loadAdmin();
  updateCoverPreview(document.getElementById("addBookForm"));
  document.getElementById("quickFindResults").innerHTML = "";
});

// Xem trước ảnh bìa khi thủ thư dán link
function updateCoverPreview(form) {
  const formId = form.getAttribute("id"); // (form.id bị ô ẩn name="id" che mất)
  const box = document.querySelector(`[data-preview-for="${formId}"]`);
  if (!box) return;
  const title = form.title.value || "?";
  box.outerHTML = coverHtml(title, form.cover_url.value, "cover-preview").replace(
    'class="cover', `data-preview-for="${formId}" class="cover`
  );
}
["addBookForm", "editBookForm"].forEach((id) => {
  const f = document.getElementById(id);
  f.cover_url.addEventListener("input", () => updateCoverPreview(f));
  f.title.addEventListener("input", () => updateCoverPreview(f));
});

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

// =========================================================
// QUÊN MẬT KHẨU (sinh viên) — gửi mã qua email rồi đặt mật khẩu mới
// =========================================================
let forgotCode = "";      // mã học sinh đang đặt lại
let forgotEmail = "";     // email đã nhập
let resendTimer = null;

function openForgot() {
  const s1 = document.getElementById("forgotStep1");
  const s2 = document.getElementById("forgotStep2");
  s1.reset(); s2.reset();
  s1.style.display = ""; s2.style.display = "none";
  document.getElementById("forgotError1").textContent = "";
  document.getElementById("forgotError2").textContent = "";
  // Điền sẵn mã học sinh nếu đã gõ ở ô đăng nhập
  s1.student_code.value = document.querySelector("#loginForm [name=username]").value.trim();
  document.getElementById("forgotDialog").showModal();
}
function closeForgot() {
  clearInterval(resendTimer);
  document.getElementById("forgotDialog").close();
}

function startResendCountdown(sec = 60) {
  const btn = document.getElementById("resendBtn");
  clearInterval(resendTimer);
  btn.disabled = true;
  let left = sec;
  btn.textContent = `Gửi lại mã (${left}s)`;
  resendTimer = setInterval(() => {
    left -= 1;
    if (left <= 0) {
      clearInterval(resendTimer);
      btn.disabled = false;
      btn.textContent = "Gửi lại mã";
    } else {
      btn.textContent = `Gửi lại mã (${left}s)`;
    }
  }, 1000);
}

async function requestResetCode(code, email, btn, errEl) {
  errEl.textContent = "";
  let result;
  await withLoading(btn, async () => {
    result = await postJSON("api/forgot_password.php", { action: "request", student_code: code, email });
  });
  if (!result.ok) {
    errEl.textContent = result.data.error || "Không gửi được mã";
    return false;
  }
  forgotCode = code;
  forgotEmail = email;
  document.getElementById("forgotSentMsg").textContent = result.data.message;
  startResendCountdown();
  return true;
}

document.getElementById("forgotStep1").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target;
  const ok = await requestResetCode(f.student_code.value.trim(), f.email.value.trim(), f.querySelector("button[type=submit]"), document.getElementById("forgotError1"));
  if (ok) {
    f.style.display = "none";
    const s2 = document.getElementById("forgotStep2");
    s2.style.display = "";
    s2.code.focus();
  }
});

function resendCode() {
  requestResetCode(forgotCode, forgotEmail, document.getElementById("resendBtn"), document.getElementById("forgotError2"));
}

document.getElementById("forgotStep2").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target;
  const errEl = document.getElementById("forgotError2");
  errEl.textContent = "";
  if (f.new_password.value !== f.confirm_password.value) {
    errEl.textContent = "Hai mật khẩu mới không giống nhau";
    return;
  }
  await withLoading(f.querySelector("button[type=submit]"), async () => {
    const { ok, data } = await postJSON("api/forgot_password.php", {
      action: "reset",
      student_code: forgotCode,
      code: f.code.value.trim(),
      new_password: f.new_password.value,
    });
    if (!ok) {
      errEl.textContent = data.error || "Đặt lại mật khẩu thất bại";
      return;
    }
    closeForgot();
    document.querySelector("#loginForm [name=username]").value = forgotCode;
    document.querySelector("#loginForm [name=password]").value = "";
    document.querySelector("#loginForm [name=password]").focus();
    document.getElementById("loginError").textContent = "";
    showToast(data.message, "success");
  });
});

// =========================================================
// ĐỔI MẬT KHẨU (đã đăng nhập — sinh viên hoặc thủ thư)
// =========================================================
function openChangePw() {
  const f = document.getElementById("changePwForm");
  f.reset();
  document.getElementById("changePwError").textContent = "";
  document.getElementById("changePwDialog").showModal();
}
function closeChangePw() {
  document.getElementById("changePwDialog").close();
}

document.getElementById("changePwForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target;
  const errEl = document.getElementById("changePwError");
  errEl.textContent = "";
  if (f.new_password.value !== f.confirm_password.value) {
    errEl.textContent = "Hai mật khẩu mới không giống nhau";
    return;
  }
  await withLoading(f.querySelector("button[type=submit]"), async () => {
    const { ok, data } = await postJSON("api/change_password.php", {
      current_password: f.current_password.value,
      new_password: f.new_password.value,
    });
    if (!ok) {
      errEl.textContent = data.error || "Đổi mật khẩu thất bại";
      return;
    }
    closeChangePw();
    showToast(data.message || "Đã đổi mật khẩu", "success");
  });
});

// =========================================================
// THỦ THƯ: TỰ TÌM ẢNH BÌA (Google Books / Open Library)
// Chạy ngay trên trình duyệt của thủ thư, chỉ lưu những ảnh được chọn.
// =========================================================
function normTitle(t) {
  return String(t || "")
    .normalize("NFD").replace(/[̀-ͯ]/g, "").replace(/đ/gi, "d")
    .toLowerCase().replace(/[^a-z0-9]+/g, " ").trim();
}
// Tên tìm được phải khớp phần lớn các từ trong tên sách của mình
function titleMatches(mine, found) {
  const a = normTitle(mine).split(" ").filter((w) => w.length > 1);
  const b = new Set(normTitle(found).split(" "));
  if (a.length === 0) return false;
  const hit = a.filter((w) => b.has(w)).length;
  return hit / a.length >= 0.6;
}
function firstAuthor(author) {
  const a = String(author || "").split(/,|&| và /)[0].trim();
  return /nhiều tác giả|chưa rõ/i.test(a) ? "" : a;
}

// Tìm trên Google Books. Trả về danh sách {title, authors, year, cover, link}
async function searchGoogleBooks(q, max = 5) {
  const url = "https://www.googleapis.com/books/v1/volumes?printType=books&maxResults=" + max
    + "&fields=items(id,volumeInfo(title,subtitle,authors,publishedDate,imageLinks),accessInfo(viewability,webReaderLink))&q="
    + encodeURIComponent(q);
  const res = await fetch(url);
  if (!res.ok) throw new Error("google " + res.status);
  const data = await res.json();
  return (data.items || []).map((it) => {
    const v = it.volumeInfo || {};
    const img = v.imageLinks && (v.imageLinks.thumbnail || v.imageLinks.smallThumbnail);
    const view = (it.accessInfo || {}).viewability;
    const access = view === "ALL_PAGES" ? "full" : view === "PARTIAL" ? "partial" : "none";
    const id = encodeURIComponent(it.id || "");
    // Đọc được -> mở thẳng trang đọc (không cần đăng nhập Google); không đọc được -> trang thông tin sách
    const link = !id ? "" : access === "none"
      ? `https://books.google.com/books?id=${id}`
      : `https://books.google.com/books?id=${id}&printsec=frontcover`;
    return {
      title: v.title || "",
      fullTitle: [v.title, v.subtitle].filter(Boolean).join(" "),
      authors: (v.authors || []).join(", "),
      year: (v.publishedDate || "").slice(0, 4),
      cover: img ? img.replace(/^http:/, "https:").replace("&edge=curl", "") : "",
      link,
      access,
      source: "Google Books",
    };
  });
}

// Tìm trên Open Library (dự phòng khi Google không có / bị giới hạn)
async function searchOpenLibrary(params, max = 5) {
  const p = new URLSearchParams({ ...params, limit: String(max), fields: "key,title,author_name,first_publish_year,cover_i,ebook_access" });
  const res = await fetch("https://openlibrary.org/search.json?" + p.toString());
  if (!res.ok) throw new Error("openlibrary " + res.status);
  const data = await res.json();
  return (data.docs || []).map((d) => ({
    title: d.title || "",
    fullTitle: d.title || "",
    authors: (d.author_name || []).slice(0, 3).join(", "),
    year: d.first_publish_year ? String(d.first_publish_year) : "",
    cover: d.cover_i ? `https://covers.openlibrary.org/b/id/${d.cover_i}-M.jpg` : "",
    link: d.key ? `https://openlibrary.org${d.key}` : "",
    access: d.ebook_access === "public" ? "full" : "none",   // "public" = sách tự do, đọc miễn phí toàn bộ
    source: "Open Library",
  }));
}

// Tìm ảnh bìa + link đọc tốt nhất cho 1 cuốn đã có trong thư viện (tên phải khớp)
async function findCover(b) {
  const author = firstAuthor(b.author);
  const matches = [];
  try {
    matches.push(...(await searchGoogleBooks(`intitle:${b.title}` + (author ? ` inauthor:${author}` : ""), 10)));
  } catch (e) { /* thử nguồn khác */ }
  const best = () => {
    const ok = matches.filter((x) => titleMatches(b.title, x.fullTitle));
    const cover = ok.find((x) => x.cover);
    // Ưu tiên bản đọc được nhiều nhất: đọc toàn bộ > đọc thử > chỉ thông tin
    const read = ok.filter((x) => x.link).sort((a, c) => ACCESS_RANK[c.access] - ACCESS_RANK[a.access])[0];
    return { cover, read };
  };
  let r = best();
  // Google không có ảnh hoặc không cho đọc -> thử thêm Open Library
  if (!r.cover || !r.read || r.read.access !== "full") {
    try {
      matches.push(...(await searchOpenLibrary(author ? { title: b.title, author } : { title: b.title }, 5)));
      r = best();
    } catch (e) { /* bỏ qua */ }
  }
  if (!r.cover && !r.read) return null;
  return {
    url: r.cover ? r.cover.cover : "",
    link: r.read ? r.read.link : "",
    access: r.read ? r.read.access : "",
    source: (r.read || r.cover).source,
  };
}

// Link đang có là do web tự tìm (được phép thay bằng link tốt hơn)?
function isAutoLink(url) {
  return !url || /^https:\/\/(books\.google\.com|play\.google\.com|openlibrary\.org)\//.test(url);
}

let coverCandidates = [];

document.getElementById("coverFindBtn").addEventListener("click", async (e) => {
  const btn = e.currentTarget;
  const status = document.getElementById("coverStatus");
  const results = document.getElementById("coverResults");
  const actions = document.getElementById("coverActions");
  btn.disabled = true;
  actions.hidden = true;
  results.innerHTML = "";
  coverCandidates = [];

  const books = (await getJSON("api/books.php")).filter(
    (b) => !safeUrl(b.cover_url) || (isAutoLink(b.book_link) && !b.read_access)
  );
  if (books.length === 0) {
    status.textContent = "· Tất cả sách đã có ảnh bìa và link";
    btn.disabled = false;
    return;
  }

  let done = 0;
  const queue = books.slice();
  async function worker() {
    while (queue.length) {
      const b = queue.shift();
      const c = await findCover(b);
      done++;
      status.textContent = `· Đang tìm ${done}/${books.length}…`;
      if (!c) continue;
      // Chỉ lấy phần sách đang thiếu
      const addCover = !safeUrl(b.cover_url) && c.url ? c.url : "";
      const canReplace = isAutoLink(b.book_link) && c.link && c.link !== b.book_link
        && (ACCESS_RANK[c.access] || 0) >= (ACCESS_RANK[b.read_access] || 0);
      const addLink = canReplace ? c.link : "";
      if (!addCover && !addLink) continue;
      coverCandidates.push({ book: b, url: addCover, link: addLink, access: c.access, source: c.source });
      const linkWhat = { full: "Đọc toàn bộ", partial: "Đọc thử", none: "Link thông tin" }[c.access] || "Link";
      const what = addCover && addLink ? `Ảnh + ${linkWhat}` : addCover ? "Ảnh bìa" : linkWhat;
      const shown = addCover || safeUrl(b.cover_url);
      results.insertAdjacentHTML("beforeend", `
        <label class="cover-cand" data-id="${Number(b.id)}">
          <input type="checkbox" checked />
          ${shown
            ? `<img src="${esc(shown)}" alt="" loading="lazy" referrerpolicy="no-referrer"
                    onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'cover-cand-noimg',textContent:'Không tải được ảnh'}));">`
            : `<div class="cover-cand-noimg">Chỉ có link</div>`}
          <span class="cover-cand-title">${esc(b.title)}</span>
          <span class="muted small">${what} · ${esc(c.source)}</span>
          ${addLink ? `<a class="small link" href="${esc(addLink)}" target="_blank" rel="noopener" onclick="event.stopPropagation()">Xem link ↗</a>` : ""}
        </label>`);
    }
  }
  await Promise.all([worker(), worker(), worker()]); // tìm 3 cuốn cùng lúc

  const found = results.querySelectorAll(".cover-cand").length;
  status.textContent = `· Tìm được ${found}/${books.length} sách. Sách không tìm thấy vẫn giữ như cũ.`;
  actions.hidden = found === 0;
  btn.disabled = false;
});

document.getElementById("coverToggleAll").addEventListener("click", (e) => {
  const boxes = [...document.querySelectorAll("#coverResults input[type=checkbox]")];
  const anyChecked = boxes.some((x) => x.checked);
  boxes.forEach((x) => (x.checked = !anyChecked));
  e.currentTarget.textContent = anyChecked ? "Chọn tất cả" : "Bỏ chọn tất cả";
});

document.getElementById("coverSaveBtn").addEventListener("click", async (e) => {
  const chosen = [...document.querySelectorAll("#coverResults .cover-cand")]
    .filter((el) => el.querySelector("input").checked)
    .map((el) => coverCandidates.find((c) => String(c.book.id) === el.dataset.id))
    .filter(Boolean);
  if (chosen.length === 0) {
    showToast("Chưa chọn ảnh nào", "error");
    return;
  }
  await withLoading(e.currentTarget, async () => {
    let ok = 0;
    for (const c of chosen) {
      const r = await sendJSON("PUT", "api/books.php", { id: Number(c.book.id), cover_url: c.url, book_link: c.link, read_access: c.access, only_cover: true });
      if (r.ok) ok++;
    }
    showToast(`Đã lưu ảnh bìa / link cho ${ok} cuốn sách`, "success");
  });
  document.getElementById("coverResults").innerHTML = "";
  document.getElementById("coverActions").hidden = true;
  document.getElementById("coverStatus").textContent = "";
  loadBooks();
});

// =========================================================
// THỦ THƯ: TÌM NHANH ĐỂ TỰ ĐIỀN KHI THÊM SÁCH
// =========================================================
let quickFindItems = [];

async function quickFind() {
  const input = document.getElementById("quickFindInput");
  const box = document.getElementById("quickFindResults");
  const q = input.value.trim();
  if (q.length < 2) {
    showToast("Nhập tên sách hoặc mã ISBN để tìm", "error");
    input.focus();
    return;
  }
  const isbn = q.replace(/[-\s]/g, "");
  const isIsbn = /^(\d{9}[\dXx]|\d{13})$/.test(isbn);

  box.innerHTML = `<p class="muted small">Đang tìm…</p>`;
  let items = [];
  try {
    items = await searchGoogleBooks(isIsbn ? `isbn:${isbn}` : q, 6);
  } catch (e) { /* thử nguồn khác */ }
  if (items.length === 0) {
    try { items = await searchOpenLibrary(isIsbn ? { isbn } : { q }, 6); } catch (e) { /* bỏ qua */ }
  }
  // Bản đọc được xếp lên trước
  items.sort((a, c) => (ACCESS_RANK[c.access] || 0) - (ACCESS_RANK[a.access] || 0));
  quickFindItems = items;

  if (items.length === 0) {
    box.innerHTML = `<p class="muted small">Không tìm thấy. Bạn nhập tay các thông tin bên dưới nhé.</p>`;
    return;
  }
  box.innerHTML = items.map((it, i) => `
    <button type="button" class="qf-item" data-i="${i}">
      ${safeUrl(it.cover)
        ? `<img src="${esc(it.cover)}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.visibility='hidden'">`
        : `<span class="qf-noimg">📘</span>`}
      <span class="qf-text">
        <strong>${esc(it.title)}</strong>
        <span class="muted small">${esc(it.authors || "Chưa rõ tác giả")}${it.year ? " · " + esc(it.year) : ""} · ${esc(it.source)}</span>
        <span class="qf-access ${esc(it.access)}">${esc({ full: "📖 Đọc toàn bộ", partial: "📖 Đọc thử", none: "Chỉ có thông tin" }[it.access] || "")}</span>
      </span>
    </button>`).join("");
}

document.getElementById("quickFindBtn").addEventListener("click", quickFind);
document.getElementById("quickFindInput").addEventListener("keydown", (e) => {
  if (e.key === "Enter") { e.preventDefault(); quickFind(); }   // không gửi form thêm sách
});
document.getElementById("quickFindResults").addEventListener("click", (e) => {
  const btn = e.target.closest(".qf-item");
  if (!btn) return;
  const it = quickFindItems[Number(btn.dataset.i)];
  const f = document.getElementById("addBookForm");
  f.title.value = it.title;
  f.author.value = it.authors;
  f.cover_url.value = safeUrl(it.cover);
  f.book_link.value = safeUrl(it.link);
  f.read_access.value = it.link ? it.access || "" : "";
  updateCoverPreview(f);
  document.getElementById("quickFindResults").innerHTML =
    `<p class="notice-text">✅ Đã điền sẵn tên sách, tác giả, ảnh bìa và link đọc. Nhập thêm mã môn, vị trí kệ, số lượng rồi bấm “Thêm sách”.</p>`;
  f.subject_code.focus();
});