// =============== LibGo · Trang thủ thư · Nhập sách bằng mã vạch ===============
const API = "../api/";

const $ = (id) => document.getElementById(id);
const isbnInput = $("isbnInput");
const scanbar = $("scanbar");
const statusEl = $("status");
const form = $("bookForm");
const infoFields = $("infoFields");
const saveBtn = $("saveBtn");
const lookupBtn = $("lookupBtn");

// mode: "found" (tra được trên mạng) | "manual" (nhập tay) | "exists" (đã có, chỉ thêm bản)
let mode = null;
let currentIsbn = "";
let sessionTotal = 0;

// ---------- Tiện ích ----------
function toast(msg) {
  const t = $("toast");
  t.textContent = msg;
  t.classList.add("show");
  clearTimeout(toast._t);
  toast._t = setTimeout(() => t.classList.remove("show"), 3000);
}

function setStatus(text, kind = "", barState = "idle") {
  statusEl.textContent = text;
  statusEl.className = "status " + kind;
  scanbar.dataset.state = barState;
}

async function api(path, options = {}) {
  const res = await fetch(API + path, options);
  if (res.status === 401) {
    location.href = "../"; // hết phiên đăng nhập
    throw new Error("Hết phiên đăng nhập");
  }
  let data = {};
  try { data = await res.json(); } catch { /* phản hồi không phải JSON */ }
  if (!res.ok) throw new Error(data.error || "Lỗi máy chủ (" + res.status + ")");
  return data;
}

function placeLabel(name) {
  return form.elements[name].closest("label");
}

function setCover(url) {
  const box = $("coverBox");
  box.replaceChildren();
  box.classList.remove("has-img");
  if (url) {
    const img = document.createElement("img");
    img.alt = "Ảnh bìa";
    img.src = url;
    img.onerror = () => form.classList.add("no-cover"); // link ảnh hỏng thì ẩn khung bìa
    box.append(img);
    box.classList.add("has-img");
  } else {
    const span = document.createElement("span");
    span.textContent = "Chưa có ảnh bìa";
    box.append(span);
  }
}

// ---------- Hiển thị form theo từng trường hợp ----------
function showForm(newMode, book = {}) {
  mode = newMode;
  const keep = $("keepPlace").checked;
  const subject = form.elements.subject_code.value;
  const shelf = form.elements.shelf_location.value;

  form.reset();
  if (keep) {
    form.elements.subject_code.value = subject;
    form.elements.shelf_location.value = shelf;
  }
  $("keepPlace").checked = keep;

  const isExists = newMode === "exists";
  form.classList.toggle("is-exists", isExists);
  infoFields.hidden = isExists;
  placeLabel("subject_code").hidden = isExists;
  placeLabel("shelf_location").hidden = isExists;
  $("keepPlace").closest("label").hidden = isExists;
  $("existsBox").hidden = !isExists;
  saveBtn.textContent = isExists ? "Thêm bản" : "Lưu sách";

  if (isExists) {
    $("existsTitle").textContent = book.title;
    const parts = [
      book.author || "Không rõ tác giả",
      `đang có ${book.total_qty} bản (còn ${book.available_qty} trên kệ)`,
      book.shelf_location ? `vị trí: ${book.shelf_location}` : "",
    ].filter(Boolean);
    $("existsMeta").textContent = parts.join(", ");
    const img = $("existsCover");
    img.hidden = !book.cover_url;
    if (book.cover_url) img.src = book.cover_url;
  } else {
    form.elements.title.value = book.title || "";
    form.elements.author.value = book.author || "";
    form.elements.publisher.value = book.publisher || "";
    form.elements.publish_year.value = book.publish_year || "";
    form.dataset.cover = book.cover_url || "";
    setCover(book.cover_url || "");
    form.classList.toggle("no-cover", !book.cover_url);
  }

  form.hidden = false;

  // Đưa con trỏ đến ô cần điền tiếp theo
  let next;
  if (isExists) next = form.elements.qty;
  else if (!form.elements.title.value) next = form.elements.title;
  else if (!form.elements.subject_code.value) next = form.elements.subject_code;
  else if (!form.elements.shelf_location.value) next = form.elements.shelf_location;
  else next = form.elements.qty;
  next.focus();
  if (next.select) next.select();
}

function resetIntake(message = "") {
  mode = null;
  currentIsbn = "";
  form.hidden = true;
  $("existsBox").hidden = true;
  isbnInput.value = "";
  setStatus(message, message ? "ok" : "", "idle");
  isbnInput.focus();
}

// ---------- Tra cứu ISBN ----------
async function lookup() {
  const raw = isbnInput.value.trim();
  if (!raw) { isbnInput.focus(); return; }

  form.hidden = true;
  $("existsBox").hidden = true;
  setStatus("Đang tra cứu...", "muted", "searching");
  lookupBtn.disabled = true;

  try {
    const data = await api("isbn_lookup.php?isbn=" + encodeURIComponent(raw));
    currentIsbn = data.book.isbn;
    isbnInput.value = currentIsbn;

    if (data.status === "exists") {
      setStatus("Sách này đã có trong thư viện. Nhập số bản muốn thêm.", "warn", "exists");
      showForm("exists", data.book);
    } else if (data.status === "found") {
      setStatus(`Đã tìm thấy trên ${data.source}. Kiểm tra lại thông tin rồi thêm mã môn và kệ.`, "ok", "found");
      showForm("found", data.book);
    } else {
      setStatus("Không tìm thấy thông tin trên mạng. Nhập tay một lần, lần sau quét lại sẽ ra ngay.", "muted", "idle");
      showForm("manual", data.book);
    }
  } catch (err) {
    setStatus(err.message, "err", "error");
    isbnInput.select();
  } finally {
    lookupBtn.disabled = false;
  }
}

isbnInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter") { // máy quét USB tự nhấn Enter sau khi gõ mã
    e.preventDefault();
    lookup();
  }
});
lookupBtn.addEventListener("click", lookup);

$("noIsbnBtn").addEventListener("click", () => {
  currentIsbn = "";
  isbnInput.value = "";
  setStatus("Nhập thông tin sách không có mã ISBN.", "muted", "idle");
  showForm("manual");
});

// ---------- Lưu ----------
form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = form.elements;
  f.title.classList.remove("invalid");

  const qty = Number(f.qty.value);
  if (!Number.isInteger(qty) || qty < 1 || qty > 500) {
    toast("Số lượng phải từ 1 đến 500");
    f.qty.focus();
    return;
  }

  let payload;
  if (mode === "exists") {
    payload = { isbn: currentIsbn, qty };
  } else {
    if (!f.title.value.trim()) {
      f.title.classList.add("invalid");
      toast("Chưa có tên sách");
      f.title.focus();
      return;
    }
    payload = {
      isbn: currentIsbn,
      title: f.title.value,
      author: f.author.value,
      publisher: f.publisher.value,
      publish_year: f.publish_year.value,
      cover_url: form.dataset.cover || "",
      subject_code: f.subject_code.value,
      shelf_location: f.shelf_location.value,
      book_link: f.book_link.value,
      qty,
    };
  }

  saveBtn.disabled = true;
  try {
    const data = await api("isbn_save.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    addToSession(data);
    toast(data.message);
    resetIntake(data.message + " Quét cuốn tiếp theo.");
    loadOptions();
  } catch (err) {
    toast(err.message);
  } finally {
    saveBtn.disabled = false;
  }
});

$("cancelBtn").addEventListener("click", () => resetIntake());
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && !form.hidden) resetIntake();
});

// ---------- Danh sách vừa nhập trong lần này ----------
function addToSession(data) {
  sessionTotal += data.added;
  const li = document.createElement("li");
  li.className = "new";
  const title = document.createElement("strong");
  title.textContent = data.title;
  const meta = document.createElement("span");
  meta.textContent = data.action === "created"
    ? `Sách mới, ${data.added} bản`
    : `Thêm ${data.added} bản, nay có ${data.total_qty} bản`;
  li.append(title, meta);
  $("sessionList").prepend(li);
  $("sessionCount").textContent = `Đã nhập ${sessionTotal} cuốn trong lần này.`;
}

// ---------- Gợi ý mã môn & kệ ----------
async function loadOptions() {
  try {
    const data = await api("book_options.php");
    fillDatalist("subjectList", data.subjects);
    fillDatalist("shelfList", data.shelves);
  } catch { /* không có gợi ý thì vẫn nhập tay được */ }
}
function fillDatalist(id, values) {
  $(id).replaceChildren(...values.map((v) => {
    const o = document.createElement("option");
    o.value = v;
    return o;
  }));
}

// ---------- Quét bằng camera (điện thoại / laptop) ----------
let camera = null;

function loadScript(src) {
  return new Promise((resolve, reject) => {
    const s = document.createElement("script");
    s.src = src;
    s.onload = resolve;
    s.onerror = () => reject(new Error("Không tải được thư viện quét camera"));
    document.head.append(s);
  });
}

async function startCamera() {
  const box = $("cameraBox");
  const btn = $("cameraBtn");
  try {
    if (!window.Html5Qrcode) {
      btn.disabled = true;
      await loadScript("https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js");
    }
    box.hidden = false;
    camera = new Html5Qrcode("cameraBox", {
      formatsToSupport: [
        Html5QrcodeSupportedFormats.EAN_13,
        Html5QrcodeSupportedFormats.EAN_8,
      ],
      verbose: false,
    });
    await camera.start(
      { facingMode: "environment" },
      { fps: 10, qrbox: { width: 280, height: 120 } },
      async (text) => {
        await stopCamera();
        isbnInput.value = text;
        lookup();
      },
      () => {} // chưa đọc được khung hình này, bỏ qua
    );
    btn.textContent = "Tắt camera";
  } catch (err) {
    box.hidden = true;
    camera = null;
    toast(err.message || "Không mở được camera. Kiểm tra quyền truy cập camera của trình duyệt.");
  } finally {
    btn.disabled = false;
  }
}

async function stopCamera() {
  if (camera) {
    try { await camera.stop(); } catch { /* camera đã tắt */ }
    camera = null;
  }
  $("cameraBox").hidden = true;
  $("cameraBtn").textContent = "Quét bằng camera";
}

$("cameraBtn").addEventListener("click", () => (camera ? stopCamera() : startCamera()));

// ---------- Đăng xuất ----------
$("logoutBtn").addEventListener("click", async () => {
  try { await fetch(API + "logout.php", { method: "POST" }); } catch {}
  location.href = "../";
});

// ---------- Khởi động ----------
loadOptions();
isbnInput.focus();
