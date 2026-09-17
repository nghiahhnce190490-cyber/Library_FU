// ---------- Tab switching ----------
document.querySelectorAll(".tab-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    document.querySelectorAll(".tab-btn").forEach((b) => b.classList.remove("active"));
    document.querySelectorAll(".tab-panel").forEach((p) => p.classList.remove("active"));
    btn.classList.add("active");
    document.getElementById("tab-" + btn.dataset.tab).classList.add("active");
    if (btn.dataset.tab === "return") loadLoans("loanList", "borrowed,overdue");
    if (btn.dataset.tab === "admin") loadLoans("allLoanList", "");
  });
});

function showToast(msg) {
  const t = document.getElementById("toast");
  t.textContent = msg;
  t.classList.add("show");
  setTimeout(() => t.classList.remove("show"), 3000);
}

// ---------- Members dropdown ----------
async function loadMembers() {
  const res = await fetch("api/members.php");
  const members = await res.json();
  const select = document.getElementById("memberSelect");
  select.innerHTML = members
    .map((m) => `<option value="${m.id}">${m.student_code} — ${m.name} (${m.class_name || ""})</option>`)
    .join("");
}

// ---------- Books search & checkout ----------
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
          ? `<span class="badge ok">Còn ${b.available_qty}/${b.total_qty}</span>`
          : `<span class="badge danger">Hết sách</span>`;
      return `
        <div class="card">
          <div class="info">
            <strong>${b.title}</strong> ${statusBadge}
            <div class="meta">
              Tác giả: ${b.author || "—"} · Mã môn: ${b.subject_code || "—"} · Vị trí: ${b.shelf_location || "—"}
              ${b.book_link ? ` · <a href="${b.book_link}" target="_blank">Xem link sách</a>` : ""}
            </div>
          </div>
          <button ${b.available_qty < 1 ? "disabled" : ""} onclick="checkout(${b.id})">Xác nhận mượn</button>
        </div>
      `;
    })
    .join("");
}

async function checkout(bookId) {
  const memberId = document.getElementById("memberSelect").value;
  if (!memberId) {
    showToast("Vui lòng chọn tài khoản trước khi mượn");
    return;
  }
  const res = await fetch("api/checkout.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ book_id: bookId, member_id: Number(memberId) }),
  });
  const data = await res.json();
  if (!res.ok) {
    showToast(data.error);
    return;
  }
  showToast(data.message);
  loadBooks();
}

// ---------- Loans / checkin ----------
async function loadLoans(targetId, statusFilter) {
  const container = document.getElementById(targetId);
  const statuses = statusFilter ? statusFilter.split(",") : [""];
  let allLoans = [];
  for (const s of statuses) {
    const res = await fetch("api/loans.php" + (s ? `?status=${s}` : ""));
    allLoans = allLoans.concat(await res.json());
  }
  const seen = new Set();
  allLoans = allLoans.filter((l) => (seen.has(l.id) ? false : seen.add(l.id)));

  if (allLoans.length === 0) {
    container.innerHTML = `<p>Không có phiếu mượn nào.</p>`;
    return;
  }

  container.innerHTML = allLoans
    .map((l) => {
      let badge = `<span class="badge ok">Đang mượn</span>`;
      if (l.status === "overdue") badge = `<span class="badge danger">Quá hạn</span>`;
      if (l.status === "returned") badge = `<span class="badge warn">Đã trả</span>`;

      return `
        <div class="card">
          <div class="info">
            <strong>${l.book_title}</strong> ${badge}
            <div class="meta">
              Người mượn: ${l.member_name} (${l.student_code}) · Vị trí: ${l.shelf_location}<br/>
              Ngày mượn: ${l.borrow_date} · Hạn trả: ${l.due_date}
              ${l.return_date ? ` · Ngày trả: ${l.return_date}` : ""}
              ${l.fine > 0 ? ` · Phạt: ${Number(l.fine).toLocaleString()} VND` : ""}
            </div>
          </div>
          ${l.status !== "returned" ? `<button onclick="checkin(${l.id})">Xác nhận trả sách</button>` : ""}
        </div>
      `;
    })
    .join("");
}

async function checkin(loanId) {
  const res = await fetch("api/checkin.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ loan_id: loanId }),
  });
  const data = await res.json();
  if (!res.ok) {
    showToast(data.error);
    return;
  }
  showToast(data.message);
  loadLoans("loanList", "borrowed,overdue");
  loadLoans("allLoanList", "");
}

// ---------- Admin: add book ----------
document.getElementById("addBookForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const payload = {
    title: form.title.value,
    author: form.author.value,
    subject_code: form.subject_code.value,
    book_link: form.book_link.value,
    shelf_location: form.shelf_location.value,
    total_qty: Number(form.total_qty.value),
  };
  const res = await fetch("api/books.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if (!res.ok) {
    showToast(data.error);
    return;
  }
  showToast("Đã thêm sách mới");
  form.reset();
  loadBooks();
});

// ---------- Admin: add member ----------
document.getElementById("addMemberForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const payload = {
    student_code: form.student_code.value,
    name: form.name.value,
    class_name: form.class_name.value,
    contact: form.contact.value,
  };
  const res = await fetch("api/members.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if (!res.ok) {
    showToast(data.error);
    return;
  }
  showToast("Đã thêm thành viên");
  form.reset();
  loadMembers();
});

// ---------- Init ----------
loadMembers();
loadBooks();
