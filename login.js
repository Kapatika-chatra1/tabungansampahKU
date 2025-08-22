function showForm(formType) {
  const loginForm = document.getElementById("loginForm");
  const registerForm = document.getElementById("registerForm");
  const tabs = document.querySelectorAll(".tab");

  if (formType === "login") {
    loginForm.classList.add("active");
    registerForm.classList.remove("active");
    tabs[0].classList.add("active");
    tabs[1].classList.remove("active");
  } else {
    registerForm.classList.add("active");
    loginForm.classList.remove("active");
    tabs[1].classList.add("active");
    tabs[0].classList.remove("active");
  }
}
