(function () {
  "use strict";
  document.body.classList.add("aa-js");
  const toggle = document.querySelector(".menu-toggle");
  const nav = document.getElementById("site-navigation");
  function closeMenu() {
    if (!nav || !toggle) return;
    nav.classList.remove("open");
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-label", "Open navigation");
  }
  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      const open = nav.classList.toggle("open");
      toggle.setAttribute("aria-expanded", String(open));
      if (open) {
        const firstLink = nav.querySelector("a");
        if (firstLink) firstLink.focus();
      }
      toggle.setAttribute(
        "aria-label",
        open ? "Close navigation" : "Open navigation",
      );
    });
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && nav.classList.contains("open")) {
        closeMenu();
        toggle.focus();
      }
    });
    nav.addEventListener("click", function (event) {
      if (event.target.closest("a")) closeMenu();
    });
    const desktop = window.matchMedia("(min-width: 851px)");
    desktop.addEventListener("change", function (event) {
      if (event.matches) closeMenu();
    });
  }
  document.querySelectorAll(".print-policy").forEach(function (button) {
    button.addEventListener("click", function () {
      window.print();
    });
  });
})();
