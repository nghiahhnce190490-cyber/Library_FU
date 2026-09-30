// Ô chat trợ lý AI LibGo. Nhúng vào index.html:
//   <link rel="stylesheet" href="assistant-widget.css" />
//   <script src="assistant-widget.js" defer></script>
// Chỉ hiện với sinh viên đã đăng nhập (API trả 401 thì hiện lời nhắc đăng nhập).
(function () {
  const history = []; // lịch sử hội thoại trong phiên hiện tại: [{role, text}]

  const root = document.createElement("div");
  root.className = "lg-assistant";
  root.hidden = true; // ẩn cho tới khi xác nhận đã đăng nhập
  root.innerHTML = `
    <button class="lg-assistant__toggle" type="button" aria-expanded="false" aria-controls="lg-assistant-panel" aria-label="Mở trợ lý">
      <span class="lg-assistant__bubble" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="4" y="8" width="16" height="12" rx="2.5"/>
          <path d="M12 8V4.5"/>
          <circle cx="12" cy="3.2" r="1.1"/>
          <path d="M2 13.5v2"/>
          <path d="M22 13.5v2"/>
          <circle cx="9" cy="14" r="1.3"/>
          <circle cx="15" cy="14" r="1.3"/>
          <path d="M9.5 17.2h5"/>
        </svg>
      </span>
      <span class="lg-assistant__hint" aria-hidden="true">Tôi có thể giúp gì cho bạn?</span>
    </button>
    <section class="lg-assistant__panel" id="lg-assistant-panel" hidden>
      <header class="lg-assistant__head">
        <strong>Trợ lý LibGo</strong>
        <button type="button" class="lg-assistant__close" aria-label="Đóng">×</button>
      </header>
      <div class="lg-assistant__log" aria-live="polite"></div>
      <div class="lg-assistant__input">
        <textarea rows="1" maxlength="500" placeholder="Ví dụ: Còn sách nào cho môn MAS291 không?"></textarea>
        <button type="button" class="lg-assistant__send">Gửi</button>
      </div>
    </section>`;
  document.body.appendChild(root);

  // Chỉ ẩn trợ lý ở màn hình đăng nhập. Nhận biết bằng cách nhìn giao diện:
  // khi màn hình ứng dụng (#appScreen) đang hiện tức là đã vào trong.
  function isInsideApp() {
    const app = document.getElementById("appScreen");
    if (app && getComputedStyle(app).display !== "none") return true;
    // Dự phòng: nếu không có #appScreen, coi như hiện khi màn đăng nhập đang ẩn
    const login = document.getElementById("loginScreen");
    if (login && getComputedStyle(login).display !== "none") return false;
    return !!app;
  }
  function refreshVisibility() {
    const loggedIn = isInsideApp();
    root.hidden = !loggedIn;
    if (!loggedIn) {
      setOpen(false);
      clearConversation(); // đăng xuất -> xóa sạch hội thoại
      root.classList.remove("lg-peek");
    }
  }

  // Xóa toàn bộ hội thoại (dùng khi đăng xuất)
  function clearConversation() {
    history.length = 0;
    log.innerHTML = "";
  }

  // Chu kỳ chữ: hiện 5 giây, ẩn 2 giây, lặp lại.
  // Chỉ chạy khi trợ lý đang hiện và khung chat đang đóng.
  function peekCycle() {
    if (root.hidden || !panel.hidden) {
      root.classList.remove("lg-peek");
      setTimeout(peekCycle, 2000);
      return;
    }
    const showing = !root.classList.contains("lg-peek");
    root.classList.toggle("lg-peek", showing);
    setTimeout(peekCycle, showing ? 5000 : 2000);
  }

  const toggle = root.querySelector(".lg-assistant__toggle");
  const panel = root.querySelector(".lg-assistant__panel");
  const log = root.querySelector(".lg-assistant__log");
  const textarea = root.querySelector("textarea");
  const sendBtn = root.querySelector(".lg-assistant__send");

  function setOpen(open) {
    panel.hidden = !open;
    toggle.setAttribute("aria-expanded", String(open));
    if (open) {
      root.classList.remove("lg-peek");
      if (!log.children.length) {
        addBubble("assistant", "Chào bạn! Mình tìm sách, xem sách bạn đang mượn, gia hạn và đăng ký hàng chờ giúp bạn được.");
      }
      textarea.focus();
    }
  }
  toggle.addEventListener("click", () => setOpen(panel.hidden));
  root.querySelector(".lg-assistant__close").addEventListener("click", () => setOpen(false));

  // Luôn dùng textContent để chống XSS, không chèn HTML từ AI
  function addBubble(role, text) {
    const div = document.createElement("div");
    div.className = `lg-msg lg-msg--${role}`;
    div.textContent = text;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
    return div;
  }

  function addBooks(books) {
    books.slice(0, 5).forEach((b) => {
      const card = document.createElement("div");
      card.className = "lg-book";
      const title = document.createElement("strong");
      title.textContent = b.title;
      const meta = document.createElement("span");
      const status = Number(b.available_qty) > 0 ? `Còn ${b.available_qty}/${b.total_qty}` : "Đang hết";
      meta.textContent = `${status}, kệ ${b.shelf_location || "chưa cập nhật"}`;
      card.append(title, meta);
      log.appendChild(card);
    });
  }

  function addActions(actions) {
    actions.forEach((a) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "lg-action";
      btn.textContent = a.label;
      btn.addEventListener("click", async () => {
        btn.disabled = true;
        try {
          const res = await fetch("api/assistant_confirm.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action_id: a.id }),
          });
          const data = await res.json();
          addBubble("system", res.ok ? data.message : data.error);
          if (res.ok) btn.textContent = "Đã xác nhận";
          else btn.disabled = false;
        } catch {
          addBubble("system", "Mất kết nối, bạn thử lại nhé.");
          btn.disabled = false;
        }
      });
      log.appendChild(btn);
    });
    log.scrollTop = log.scrollHeight;
  }

  async function send() {
    const text = textarea.value.trim();
    if (!text || sendBtn.disabled) return;
    textarea.value = "";
    addBubble("user", text);
    sendBtn.disabled = true;
    const typing = addBubble("assistant", "Đang tra cứu...");
    typing.classList.add("lg-msg--typing");

    try {
      const res = await fetch("api/assistant.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message: text, history }),
      });
      const data = await res.json();
      typing.remove();
      if (!res.ok) {
        addBubble("system", res.status === 401 ? "Bạn cần đăng nhập bằng tài khoản sinh viên để dùng trợ lý." : data.error);
        return;
      }
      addBubble("assistant", data.reply);
      if (data.books && data.books.length) addBooks(data.books);
      if (data.actions && data.actions.length) addActions(data.actions);
      history.push({ role: "user", text }, { role: "assistant", text: data.reply });
      if (history.length > 10) history.splice(0, history.length - 10);
    } catch {
      typing.remove();
      addBubble("system", "Máy chủ đang khởi động hoặc mất kết nối, bạn thử lại sau ít giây.");
    } finally {
      sendBtn.disabled = false;
      textarea.focus();
    }
  }

  sendBtn.addEventListener("click", send);
  textarea.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      send();
    }
  });

  // Kiểm tra lúc tải trang, rồi kiểm tra lại mỗi 1,5 giây để tự ẩn/hiện
  // ngay khi đăng nhập hoặc đăng xuất (nhìn theo giao diện, không gọi server).
  refreshVisibility();
  setInterval(refreshVisibility, 1500);
  setTimeout(peekCycle, 2000); // bắt đầu chu kỳ nhấp nháy chữ
})();