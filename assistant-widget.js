// Ô chat trợ lý AI LibGo. Nhúng vào index.html:
//   <link rel="stylesheet" href="assistant-widget.css" />
//   <script src="assistant-widget.js" defer></script>
// Chỉ hiện với sinh viên đã đăng nhập (API trả 401 thì hiện lời nhắc đăng nhập).
(function () {
  const history = []; // lịch sử hội thoại trong phiên hiện tại: [{role, text}]

  const root = document.createElement("div");
  root.className = "lg-assistant";
  root.innerHTML = `
    <button class="lg-assistant__toggle" type="button" aria-expanded="false" aria-controls="lg-assistant-panel">
      Hỏi trợ lý
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

  const toggle = root.querySelector(".lg-assistant__toggle");
  const panel = root.querySelector(".lg-assistant__panel");
  const log = root.querySelector(".lg-assistant__log");
  const textarea = root.querySelector("textarea");
  const sendBtn = root.querySelector(".lg-assistant__send");

  function setOpen(open) {
    panel.hidden = !open;
    toggle.setAttribute("aria-expanded", String(open));
    if (open) {
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
})();
