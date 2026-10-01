// =========================================================
// Mục "Nhập sách" bằng mã vạch — chỉ tải ở trang /admin/ (sau app.js)
// Luồng: quét ISBN -> api/isbn_lookup.php -> điền form -> api/isbn_save.php
// =========================================================
(function () {
  if (window.LIBGO_ADMIN_PAGE !== true) return;

  const $ = (id) => document.getElementById(id);
  const form = $("intakeForm");
  if (!form) return; // index.html chưa có tab Nhập sách

  const isbnInput = $("isbnInput");
  const scanbar = $("scanbar");
  const statusEl = $("intakeStatus");
  const saveBtn = $("intakeSave");
  const lookupBtn = $("lookupBtn");

  // mode: "found" (tra được trên mạng) | "manual" (nhập tay) | "exists" (đã có, chỉ thêm bản)
  let mode = null;
  let currentIsbn = "";
  let currentCover = "";
  let sessionTotal = 0;
  let optionsLoaded = false;

  const toast = (msg, type) => (typeof showToast === "function" ? showToast(msg, type) : alert(msg));

  async function api(path, options = {}) {
    const res = await fetch("api/" + path, options);
    if (res.status === 401) {
      location.replace("./"); // hết phiên -> về trang chính để đăng nhập lại
      throw new Error("Phiên đăng nhập đã hết hạn");
    }
    let data = {};
    try { data = await res.json(); } catch (e) { /* không phải JSON */ }
    if (!res.ok) throw new Error(data.error || "Lỗi máy chủ (" + res.status + ")");
    return data;
  }

  function setStatus(text, kind = "", barState = "idle") {
    statusEl.textContent = text;
    statusEl.className = "intake-status " + kind;
    scanbar.dataset.state = barState;
  }

  function setCover(url) {
    const box = $("intakeCover");
    box.replaceChildren();
    box.classList.remove("has-img");
    form.classList.toggle("no-cover", !url);
    if (!url) return;
    const img = document.createElement("img");
    img.alt = "Ảnh bìa";
    img.src = url;
    img.onerror = () => form.classList.add("no-cover"); // link ảnh hỏng thì ẩn khung bìa
    box.append(img);
    box.classList.add("has-img");
  }

  // ---------- Hiện form theo từng trường hợp ----------
  function showForm(newMode, book = {}) {
    mode = newMode;
    const f = form.elements;
    const keep = $("keepPlace").checked;
    const subject = f.subject_code.value;
    const shelf = f.shelf_location.value;

    form.reset();
    $("keepPlace").checked = keep;
    if (keep) {
      f.subject_code.value = subject;
      f.shelf_location.value = shelf;
    }

    const isExists = newMode === "exists";
    form.classList.toggle("is-exists", isExists);
    $("intakeInfo").hidden = isExists;
    form.querySelectorAll("[data-place]").forEach((el) => (el.hidden = isExists));
    $("keepPlaceLabel").hidden = isExists;
    $("existsBox").hidden = !isExists;
    saveBtn.textContent = isExists ? "Thêm bản" : "Lưu sách";

    if (isExists) {
      $("existsTitle").textContent = book.title;
      $("existsMeta").textContent = [
        book.author || "Không rõ tác giả",
        `đang có ${book.total_qty} bản (còn ${book.available_qty} trên kệ)`,
        book.shelf_location ? `vị trí: ${book.shelf_location}` : "",
      ].filter(Boolean).join(", ");
      const img = $("existsCover");
      img.hidden = !book.cover_url;
      img.onerror = () => (img.hidden = true); // link ảnh hỏng thì ẩn
      if (book.cover_url) img.src = book.cover_url;
    } else {
      f.title.value = book.title || "";
      f.author.value = book.author || "";
      f.publisher.value = book.publisher || "";
      f.publish_year.value = book.publish_year || "";
      currentCover = book.cover_url || "";
      setCover(currentCover);
    }

    form.hidden = false;

    // Đưa con trỏ tới ô cần điền tiếp theo
    let next;
    if (isExists) next = f.qty;
    else if (!f.title.value) next = f.title;
    else if (!f.subject_code.value) next = f.subject_code;
    else if (!f.shelf_location.value) next = f.shelf_location;
    else next = f.qty;
    next.focus();
    if (next.select) next.select();
  }

  function resetIntake(message = "") {
    mode = null;
    currentIsbn = "";
    currentCover = "";
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
    setStatus("Đang tra cứu...", "", "searching");
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
        setStatus("Không tìm thấy thông tin trên mạng. Nhập tay một lần, lần sau quét lại sẽ ra ngay.", "", "idle");
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
    setStatus("Nhập thông tin sách không có mã ISBN.", "", "idle");
    showForm("manual");
  });

  // ---------- Lưu ----------
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const f = form.elements;
    f.title.classList.remove("invalid");

    const qty = Number(f.qty.value);
    if (!Number.isInteger(qty) || qty < 1 || qty > 500) {
      toast("Số lượng phải từ 1 đến 500", "error");
      f.qty.focus();
      return;
    }

    let payload;
    if (mode === "exists") {
      payload = { isbn: currentIsbn, qty };
    } else {
      if (!f.title.value.trim()) {
        f.title.classList.add("invalid");
        toast("Chưa có tên sách", "error");
        f.title.focus();
        return;
      }
      payload = {
        isbn: currentIsbn,
        title: f.title.value,
        author: f.author.value,
        publisher: f.publisher.value,
        publish_year: f.publish_year.value,
        cover_url: currentCover,
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
      toast(data.message, "success");
      resetIntake(data.message + " Quét cuốn tiếp theo.");
      loadOptions(true);
    } catch (err) {
      toast(err.message, "error");
    } finally {
      saveBtn.disabled = false;
    }
  });

  $("intakeCancel").addEventListener("click", () => resetIntake());
  form.addEventListener("keydown", (e) => {
    if (e.key === "Escape") resetIntake();
  });

  // ---------- Danh sách vừa nhập ----------
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
  async function loadOptions(force) {
    if (optionsLoaded && !force) return;
    try {
      const data = await api("book_options.php");
      fillDatalist("subjectList", data.subjects);
      fillDatalist("shelfList", data.shelves);
      optionsLoaded = true;
    } catch (e) { /* không có gợi ý thì vẫn gõ tay được */ }
  }
  function fillDatalist(id, values) {
    $(id).replaceChildren(...values.map((v) => {
      const o = document.createElement("option");
      o.value = v;
      return o;
    }));
  }

  // ---------- Quét bằng camera ----------
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
    btn.disabled = true;
    try {
      if (!window.Html5Qrcode) await loadScript("https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js");
      box.hidden = false;
      camera = new Html5Qrcode("cameraBox", {
        formatsToSupport: [Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8],
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
        () => {} // khung hình này chưa đọc được mã, bỏ qua
      );
      btn.textContent = "Tắt camera";
    } catch (err) {
      box.hidden = true;
      camera = null;
      toast((err && err.message) || "Không mở được camera. Kiểm tra quyền camera của trình duyệt.", "error");
    } finally {
      btn.disabled = false;
    }
  }

  async function stopCamera() {
    if (camera) {
      try { await camera.stop(); } catch (e) { /* đã tắt */ }
      camera = null;
    }
    $("cameraBox").hidden = true;
    $("cameraBtn").textContent = "📷 Quét bằng camera";
  }

  $("cameraBtn").addEventListener("click", () => (camera ? stopCamera() : startCamera()));

  // ---------- Gọi từ app.js khi mở tab "Nhập sách" ----------
  window.LibgoIntake = {
    open() {
      loadOptions(false);
      if (form.hidden) setTimeout(() => isbnInput.focus(), 50);
    },
  };
})();
