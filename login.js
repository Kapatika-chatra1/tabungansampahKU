/* ==== Fungsi inti (dipertahankan) ==== */
function showForm(formType) {
  const loginForm    = document.getElementById("loginForm");
  const registerForm = document.getElementById("registerForm");
  const tabs         = document.querySelectorAll(".tab");

  if (formType === "login") {
    loginForm?.classList.add("active");
    registerForm?.classList.remove("active");
    tabs[0]?.classList.add("active");
    tabs[1]?.classList.remove("active");
    document.getElementById("no_hp")?.focus();
    localStorage.setItem("activeTab","login");
    window.setTabIndicator?.(0);
  } else {
    registerForm?.classList.add("active");
    loginForm?.classList.remove("active");
    tabs[1]?.classList.add("active");
    tabs[0]?.classList.remove("active");
    document.querySelector("input[name='nama']")?.focus();
    localStorage.setItem("activeTab","register");
    window.setTabIndicator?.(1);
  }
}

function togglePassword(id) {
  const input = document.getElementById(id);
  if (input) input.type = input.type === "password" ? "text" : "password";
}

/* ==== Enhancement & Effects ==== */
document.addEventListener("DOMContentLoaded", () => {
  // 1) Tab indicator (pakai CSS var --i)
  const tabsEl = document.querySelector(".tabs");
  function setIndicator(idx){ if(tabsEl) tabsEl.style.setProperty("--i", idx); }
  window.setTabIndicator = setIndicator;

  // 2) Tentukan tab awal
  const params       = new URLSearchParams(location.search);
  const fromRegister = params.get('registered') === '1';
  if (fromRegister) {
    localStorage.setItem("activeTab", "login");
    showForm("login");
  } else {
    const saved = localStorage.getItem("activeTab");
    saved === "register" ? showForm("register") : showForm("login");
  }

  // 3) Link “login sekarang” pada alert
  document.getElementById("goToLoginLink")?.addEventListener("click", (e) => {
    e.preventDefault();
    localStorage.setItem("activeTab","login");
    showForm("login");
  });

  // 4) Ripple effect
  document.querySelectorAll(".btn, .tab, .back-btn").forEach(el => {
    el.addEventListener("click", (e) => {
      const rect = el.getBoundingClientRect();
      const ripple = document.createElement("span");
      const size = Math.max(rect.width, rect.height);
      ripple.className = "ripple";
      ripple.style.width = ripple.style.height = size + "px";
      ripple.style.left = (e.clientX - rect.left - size/2) + "px";
      ripple.style.top  = (e.clientY - rect.top  - size/2) + "px";
      el.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 5) Eye toggle untuk input password
  document.querySelectorAll("form input[type='password']").forEach((inp, idx) => {
    const wrap = document.createElement("div");
    wrap.className = "input-wrap";
    inp.parentNode.insertBefore(wrap, inp);
    wrap.appendChild(inp);

    if (!inp.id) inp.id = "pwd-"+idx;

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "eye-btn";
    btn.setAttribute("aria-label","Lihat/Sembunyikan password");
    btn.innerHTML = "👁️";
    btn.addEventListener("click", () => {
      inp.type = (inp.type === "password") ? "text" : "password";
      btn.innerHTML = (inp.type === "password") ? "👁️" : "🙈";
      inp.focus();
    });
    wrap.appendChild(btn);
  });

  // 6) Enter pada field terakhir => klik tombol
  document.querySelectorAll(".form").forEach(form => {
    const inputs = form.querySelectorAll("input");
    if (!inputs.length) return;
    inputs[inputs.length-1].addEventListener("keydown", (e) => {
      if (e.key === "Enter") form.querySelector(".btn")?.click();
    });
  });
});

// Expose
window.showForm = showForm;
window.togglePassword = togglePassword;
